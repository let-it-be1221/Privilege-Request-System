<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AuditLogger.php';
use App\DB, App\Auth, App\AuditLogger;

if (!Auth::user()) { header('Location: login.php'); exit; }
$user = Auth::user();
$pdo = DB::getPDO();

// Simple dashboard: show my requests
$stmt = $pdo->prepare('SELECT r.*, m.name as machine_name FROM requests r JOIN machines m ON m.id = r.machine_id WHERE r.requester_id = :u ORDER BY r.created_at DESC');
$stmt->execute([':u'=>$user['id']]);
$myRequests = $stmt->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/../templates/header.php';
?>
<div class="page-hero">
  <div>
    <h2>Welcome back, <?=htmlspecialchars($user['username'])?></h2>
    <p>Track your access requests and keep approvals moving smoothly.</p>
  </div>
  <div class="table-actions">
    <a class="btn" href="request_create.php">Create Request</a>
    <a class="btn secondary" href="logout.php">Logout</a>
  </div>
</div>

<div class="summary-grid">
  <div class="summary-box">
    <span class="muted">Open requests</span>
    <strong><?=count($myRequests)?></strong>
  </div>
  <div class="summary-box">
    <span class="muted">Pending review</span>
    <strong><?=count(array_filter($myRequests, fn($r) => $r['status'] === 'pending'))?></strong>
  </div>
</div>

<div class="card">
  <h3>Your Requests</h3>
  <?php if (empty($myRequests)): ?>
    <div class="empty-state">No requests yet. Start by creating your first access request.</div>
  <?php else: ?>
    <table>
      <tr><th>ID</th><th>Machine</th><th>Status</th><th>Created</th><th>Action</th></tr>
      <?php foreach($myRequests as $r): ?>
        <tr>
          <td>#<?=$r['id']?></td>
          <td><?=htmlspecialchars($r['machine_name'])?></td>
          <td><span class="badge <?=htmlspecialchars($r['status'])?>"><?=htmlspecialchars($r['status'])?></span></td>
          <td><?=htmlspecialchars($r['created_at'])?></td>
          <td><a href="request_view.php?id=<?=$r['id']?>">View details</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
