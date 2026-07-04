<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AuditLogger.php';
require __DIR__ . '/../src/Mailer.php';
require __DIR__ . '/../src/Security.php';
require __DIR__ . '/../src/Role.php';
use App\DB, App\Auth, App\AuditLogger, App\Mailer, App\Security, App\Role;

if (!Auth::user()) { header('Location: login.php'); exit; }
$pdo = DB::getPDO();
$user = Auth::user();
$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT r.*, m.name as machine_name FROM requests r JOIN machines m ON m.id = r.machine_id WHERE r.id = :id');
$stmt->execute([':id'=>$id]);
$req = $stmt->fetch();
if (!$req) { echo 'Not found'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Security::validateCsrf($_POST['_csrf'] ?? '')) { echo 'Invalid CSRF'; exit; }
    $action = $_POST['action'];
    $comment = trim($_POST['comment'] ?? '');
    $userRole = Role::getNameById($user['role_id']);
    if ($req['current_stage'] && $userRole !== $req['current_stage']) {
        if (!($req['current_stage'] === 'machine_owner' && Role::userHasRole($user,'machine_owner') && (int)$user['id'] === (int)$pdo->query("SELECT owner_user_id FROM machines WHERE id={$req['machine_id']}")->fetchColumn())) {
            echo 'You are not authorized to perform this action.'; exit;
        }
    }
    if ($action === 'approve') {
        $cfg = require __DIR__ . '/../config.php';
        $flow = $cfg['workflow'];
        $curIndex = array_search($req['current_stage'],$flow);
        if ($curIndex === false) $curIndex = 0;
        if ($curIndex < count($flow)-1) {
            $next = $flow[$curIndex+1];
            $status = 'pending';
            $stmt = $pdo->prepare('UPDATE requests SET current_stage = :n WHERE id = :id');
            $stmt->execute([':n'=>$next,':id'=>$id]);
            $notifyRole = $next;
        } else {
            $status = 'approved';
            $stmt = $pdo->prepare('UPDATE requests SET status = :s, current_stage = NULL WHERE id = :id');
            $stmt->execute([':s'=>$status,':id'=>$id]);
            $notifyRole = null;
        }
        $stmt2 = $pdo->prepare('INSERT INTO request_history (request_id,actor_user_id,action,comment) VALUES (:rid,:aid,:act,:com)');
        $stmt2->execute([':rid'=>$id,':aid'=>$user['id'],':act'=>'approved',':com'=>$comment]);
        AuditLogger::log('approve_request',$user['id'],json_encode(['request_id'=>$id,'next'=>$notifyRole]));
        if ($notifyRole) {
            $usersForRole = Role::getUsersByRoleName($notifyRole);
            $link = (isset($_SERVER['HTTP_HOST']) ? 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '').'://'.$_SERVER['HTTP_HOST'] : '') . '/request_view.php?id='.$id;
            foreach($usersForRole as $u) {
                Notification::enqueue($u['id'], $id, 'Request #'.$id.' needs your review. Link: '.$link);
            }
        } else {
            $stmt4 = $pdo->prepare('SELECT id,email FROM users WHERE id = :id');
            $stmt4->execute([':id'=>$req['requester_id']]);
            $toRow = $stmt4->fetch();
            if (!empty($toRow['id'])) Notification::enqueue($toRow['id'], $id, 'Your request #'.$id.' has been approved.');
        }
        header('Location: request_view.php?id='.$id);
        exit;
    } elseif ($action === 'deny') {
        $stmt = $pdo->prepare('UPDATE requests SET status = :s WHERE id = :id');
        $stmt->execute([':s'=>'denied',':id'=>$id]);
        $stmt2 = $pdo->prepare('INSERT INTO request_history (request_id,actor_user_id,action,comment) VALUES (:rid,:aid,:act,:com)');
        $stmt2->execute([':rid'=>$id,':aid'=>$user['id'],':act'=>'denied',':com'=>$comment]);
        AuditLogger::log('deny_request',$user['id'],json_encode(['request_id'=>$id]));
        $stmt4 = $pdo->prepare('SELECT id,email FROM users WHERE id = :id');
        $stmt4->execute([':id'=>$req['requester_id']]);
        $toRow = $stmt4->fetch();
        if (!empty($toRow['id'])) Notification::enqueue($toRow['id'], $id, 'Your request #'.$id.' has been denied.');
        header('Location: request_view.php?id='.$id);
        exit;
    }
}

$hist = $pdo->prepare('SELECT h.*, u.username FROM request_history h JOIN users u ON u.id = h.actor_user_id WHERE h.request_id = :rid ORDER BY h.created_at');
$hist->execute([':rid'=>$id]);
$history = $hist->fetchAll();

?>
<?php $title = 'Request Details'; include __DIR__ . '/../templates/header.php'; ?>
<div class="page-hero">
  <div>
    <h2>Request #<?=$req['id']?></h2>
    <p>Review the request details and keep the approval trail visible.</p>
  </div>
  <a class="btn secondary" href="print_request.php?id=<?=$req['id']?>" target="_blank">Printable Form</a>
</div>

<div class="card">
  <div class="summary-grid">
    <div class="summary-box">
      <span class="muted">Platform</span>
      <strong><?=htmlspecialchars($req['machine_name'])?></strong>
    </div>
    <div class="summary-box">
      <span class="muted">Status</span>
      <strong><span class="badge <?=htmlspecialchars($req['status'])?>"><?=htmlspecialchars($req['status'])?></span></strong>
    </div>
    <div class="summary-box">
      <span class="muted">Stage</span>
      <strong><?=htmlspecialchars($req['current_stage'] ?: 'Completed')?></strong>
    </div>
  </div>

  <h3>Applicant Information</h3>
  <p><strong>Applicant:</strong> <?=htmlspecialchars($req['applicant_name'] ?: $req['requester_id'])?></p>
  <p><strong>Email:</strong> <?=htmlspecialchars($req['applicant_email'] ?: '')?></p>
  <p><strong>Department:</strong> <?=htmlspecialchars($req['department'] ?: 'Not provided')?></p>
  <p><strong>Reason:</strong><br><?=nl2br(htmlspecialchars($req['reason']))?></p>
  <p><strong>Request Type:</strong> <?=htmlspecialchars($req['request_type'] ?: 'Not specified')?></p>
  <p><strong>Duration:</strong> <?=htmlspecialchars(ucfirst($req['access_duration'] ?: 'permanent'))?><?=($req['access_start_date'] ? ' | Start: ' . htmlspecialchars($req['access_start_date']) : '')?><?=($req['access_end_date'] ? ' | End: ' . htmlspecialchars($req['access_end_date']) : '')?></p>

  <h3 style="margin-top:18px">Platform Information</h3>
  <p><strong>Device Name:</strong> <?=htmlspecialchars($req['device_name'] ?: 'Not provided')?></p>
  <p><strong>IP Address:</strong> <?=htmlspecialchars($req['ip_address'] ?: 'Not provided')?></p>
  <p><strong>Service:</strong> <?=htmlspecialchars($req['service'] ?: 'Not provided')?></p>
  <p><strong>Privilege:</strong> <?=htmlspecialchars($req['privilege'] ?: 'Not provided')?></p>
  <p><strong>Signature Date:</strong> <?=htmlspecialchars($req['signature_date'] ?: 'Not provided')?></p>
  <?php if (!empty($req['signature_path'])): ?>
    <p><strong>Signature:</strong></p>
    <img src="<?=htmlspecialchars($req['signature_path'])?>" alt="Applicant signature" style="max-width:260px;border:1px solid #e2e8f0;border-radius:10px;padding:8px;background:#fff">
  <?php endif; ?>
</div>

<div class="card">
  <h3>Approval history</h3>
  <?php if (empty($history)): ?>
    <div class="empty-state">No history yet.</div>
  <?php else: ?>
    <?php foreach($history as $h): ?>
      <div class="history-item">
        <strong><?=htmlspecialchars($h['action'])?></strong> by <?=htmlspecialchars($h['username'])?> at <?=$h['created_at']?><br>
        <span class="muted"><?=nl2br(htmlspecialchars($h['comment']))?></span>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($req['status'] === 'pending' && $req['current_stage']): ?>
  <div class="card">
    <h3>Take action as <?=htmlspecialchars($user['username'])?></h3>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?=Security::csrfToken()?>">
      <div class="field">
        <label for="comment">Comment</label>
        <textarea id="comment" name="comment" placeholder="Add an optional comment..."></textarea>
      </div>
      <div class="table-actions">
        <button name="action" value="approve" type="submit">Approve</button>
        <button name="action" value="deny" type="submit" class="btn secondary">Deny</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
