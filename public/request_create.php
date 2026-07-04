<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/AuditLogger.php';
require __DIR__ . '/../src/Security.php';
use App\DB, App\Auth, App\AuditLogger, App\Security;

if (!Auth::user()) { header('Location: login.php'); exit; }
$pdo = DB::getPDO();
$user = Auth::user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Security::validateCsrf($_POST['_csrf'] ?? '')) {
    echo 'Invalid CSRF token'; exit;
  }

  $machine_id = intval($_POST['machine_id'] ?? 0);
  $reason = trim($_POST['reason'] ?? '');
  $applicantName = trim($_POST['applicant_name'] ?? $user['username'] ?? '');
  $applicantEmail = trim($_POST['applicant_email'] ?? $user['email'] ?? '');
  $department = trim($_POST['department'] ?? '');
  $requestTypes = array_values(array_filter(array_map('trim', (array)($_POST['request_type'] ?? [])), 'strlen'));
  $requestTypeValue = implode(',', $requestTypes);
  $accessDuration = trim($_POST['access_duration'] ?? 'permanent');
  $startDate = trim($_POST['start_date'] ?? '');
  $endDate = trim($_POST['end_date'] ?? '');

  // Server-side validation for temporary duration
  if ($accessDuration === 'temporary') {
    if ($startDate === '' || $endDate === '') {
      $error = 'Start date and end date are required for temporary access.';
    } else {
      $s = strtotime($startDate);
      $e = strtotime($endDate);
      if ($s === false || $e === false) {
        $error = 'Invalid start or end date.';
      } elseif ($s > $e) {
        $error = 'Start date must be the same or before end date.';
      }
    }
  }
  $deviceName = trim($_POST['device_name'] ?? '');
  $ipAddress = trim($_POST['ip_address'] ?? '');
  $service = trim($_POST['service'] ?? '');
  $privilege = trim($_POST['privilege'] ?? '');
  $signatureDate = trim($_POST['signature_date'] ?? '');

  $cfg = require __DIR__ . '/../config.php';
  $flow = $cfg['workflow'];
  $initial_stage = 'manager';
  if (count($flow) >= 2 && $flow[0] === 'employee') $initial_stage = $flow[1];

  if (!$error) {
    $stmt = $pdo->prepare('INSERT INTO requests (requester_id,machine_id,reason,status,current_stage,applicant_name,applicant_email,department,request_type,access_duration,access_start_date,access_end_date,device_name,ip_address,service,privilege,signature_date) VALUES (:r,:m,:re,:s,:c,:an,:ae,:d,:rt,:ad,:sd,:ed,:dn,:ip,:svc,:pr,:sigd)');
    $stmt->execute([
      ':r' => $user['id'],
      ':m' => $machine_id,
      ':re' => Security::validateInput($reason, 2000),
      ':s' => 'pending',
      ':c' => $initial_stage,
      ':an' => Security::validateInput($applicantName, 255),
      ':ae' => Security::validateInput($applicantEmail, 255),
      ':d' => Security::validateInput($department, 100),
      ':rt' => Security::validateInput($requestTypeValue, 255),
      ':ad' => Security::validateInput($accessDuration, 20),
      ':sd' => $startDate !== '' ? $startDate : null,
      ':ed' => $endDate !== '' ? $endDate : null,
      ':dn' => Security::validateInput($deviceName, 255),
      ':ip' => Security::validateInput($ipAddress, 100),
      ':svc' => Security::validateInput($service, 50),
      ':pr' => Security::validateInput($privilege, 255),
      ':sigd' => $signatureDate !== '' ? $signatureDate : null,
    ]);

    $reqId = $pdo->lastInsertId();

  if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['signature']['tmp_name']);
    finfo_close($finfo);

    if (in_array($mime, $allowedTypes, true)) {
      $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION) ?: 'png';
      $safeName = 'sig_' . $reqId . '_' . time() . '.' . strtolower($ext);
      $targetPath = __DIR__ . '/uploads/signatures/' . $safeName;
      if (move_uploaded_file($_FILES['signature']['tmp_name'], $targetPath)) {
        $relativePath = 'uploads/signatures/' . $safeName;
        $stmtUpdate = $pdo->prepare('UPDATE requests SET signature_path = :p WHERE id = :id');
        $stmtUpdate->execute([':p' => $relativePath, ':id' => $reqId]);
      }
    }
  }

  $stmt2 = $pdo->prepare('INSERT INTO request_history (request_id,actor_user_id,action,comment) VALUES (:rid,:aid,:act,:com)');
  $stmt2->execute([':rid' => $reqId, ':aid' => $user['id'], ':act' => 'created', ':com' => $reason]);
  AuditLogger::log('create_request', $user['id'], json_encode(['request_id' => $reqId]));

  $approvers = \App\Role::getUsersByRoleName($initial_stage);
  $link = (isset($_SERVER['HTTP_HOST']) ? 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] : '') . '/request_view.php?id=' . $reqId;
  foreach ($approvers as $ap) {
    \App\Notification::enqueue($ap['id'], $reqId, 'Request #' . $reqId . ' needs your review. Link: ' . $link);
  }

  header('Location: request_view.php?id=' . $reqId);
  exit;

  }
}

$machines = $pdo->query('SELECT * FROM machines')->fetchAll();
$title = 'Create Request';
include __DIR__ . '/../templates/header.php';
?>
<div class="page-hero">
  <div>
    <h2>Create a new request</h2>
    <p>Submit the applicant detail, platform information, and signature for the requested privileged access.</p>
  </div>
</div>

<div class="card">
  <?php if (!empty($error)): ?>
    <p style="color:#dc2626;font-weight:600;margin-bottom:12px"><?=htmlspecialchars($error)?></p>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?=Security::csrfToken()?>">

    <h3>1. Applicant Information</h3>
    <div class="field-grid">
      <div class="field">
        <label for="applicant_name">Applicant Name</label>
        <input id="applicant_name" name="applicant_name" value="<?=htmlspecialchars($user['username'])?>" required>
      </div>
      <div class="field">
        <label for="applicant_email">Email</label>
        <input id="applicant_email" name="applicant_email" type="email" value="<?=htmlspecialchars($user['email'] ?? '')?>" required>
      </div>
      <div class="field">
        <label for="department">Department</label>
        <select id="department" name="department" required>
          <option value="">Select department</option>
          <option value="Infrastructure and Security">Infrastructure and Security</option>
          <option value="Core System">Core System</option>
          <option value="Digital">Digital</option>
          <option value="Support">Support</option>
        </select>
      </div>
    </div>

    <div class="field">
      <label for="reason">Why privileged access is required?</label>
      <textarea id="reason" name="reason" placeholder="Describe the reason for this access request..." required></textarea>
    </div>

    <div class="field">
      <label>Request Type</label>
      <div class="radio-row">
        <label><input type="radio" name="request_type" value="new_request" checked> New Request</label>
        <label><input type="radio" name="request_type" value="renewal_extension"> Renewal / Extension</label>
        <label><input type="radio" name="request_type" value="terminate_access"> Terminate Access</label>
      </div>
    </div>

    <div class="field">
      <label>Duration of Access</label>
      <div class="radio-row">
        <label><input type="radio" name="access_duration" value="permanent" checked> Permanent</label>
        <label><input type="radio" name="access_duration" value="temporary"> Temporary</label>
      </div>
      <div id="temporary-dates" class="field-grid" style="margin-top:10px; display:none;">
        <div class="field">
          <label for="start_date">Start Date</label>
          <input id="start_date" name="start_date" type="date">
        </div>
        <div class="field">
          <label for="end_date">End Date</label>
          <input id="end_date" name="end_date" type="date">
        </div>
      </div>
    </div>

    <h3 style="margin-top:24px">2. Platform Information</h3>
    <div class="field-grid">
      <div class="field">
        <label for="machine_id">Platform / Device</label>
        <select id="machine_id" name="machine_id" required>
          <?php foreach($machines as $m): ?>
            <option value="<?=$m['id']?>"><?=htmlspecialchars($m['name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="device_name">Device Name</label>
        <input id="device_name" name="device_name" placeholder="e.g. DB-Host-01" required>
      </div>
      <div class="field">
        <label for="ip_address">IP Address</label>
        <input id="ip_address" name="ip_address" placeholder="192.168.1.10" required>
      </div>
      <div class="field">
        <label for="service">Service</label>
        <select id="service" name="service" required>
          <option value="SSH">SSH</option>
          <option value="HTTP">HTTP</option>
          <option value="HTTPS">HTTPS</option>
          <option value="RDP">RDP</option>
        </select>
      </div>
      <div class="field">
        <label for="privilege">Privilege</label>
        <input id="privilege" name="privilege" placeholder="e.g. Root / Administrator" required>
      </div>
    </div>

    <h3 style="margin-top:24px">Applicant Signature</h3>
    <div class="field-grid">
      <div class="field">
        <label for="signature">Upload Signature Image</label>
        <input id="signature" name="signature" type="file" accept="image/*">
      </div>
      <div class="field">
        <label for="signature_date">Date</label>
        <input id="signature_date" name="signature_date" type="date" required>
      </div>
    </div>

    <div style="margin-top:16px">
      <button type="submit">Create request</button>
    </div>
  </form>
</div>

<script src="assets/js/request_create.js"></script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
