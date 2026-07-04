<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
require __DIR__ . '/../src/Auth.php';
require __DIR__ . '/../src/Security.php';
use App\Auth, App\Security;

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Security::validateCsrf($_POST['_csrf'] ?? '')) { $error = 'Invalid request'; }
  else {
    $username = trim($_POST['username'] ?? '');
    // quick rate-limit check to provide friendly message
    $pdo = App\DB::getPDO();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmtAttempts = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE (ip = :ip OR username = :u) AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)');
    $stmtAttempts->execute([':ip'=>$ip,':u'=>$username]);
    $failed = (int)$stmtAttempts->fetchColumn();
    if ($failed >= 5) {
      $error = 'Too many login attempts. Try again later.';
    } else {
      $user = Auth::attempt($username, $_POST['password'] ?? '');
      if ($user) {
        header('Location: index.php');
        exit;
      }
      $error = 'Invalid credentials';
    }
  }
}
?>
<?php $title = 'Login'; include __DIR__ . '/../templates/header.php'; ?>

<div class="auth-shell">
  <div class="card auth-card">
    <h2>Welcome back</h2>
    <p class="muted">Sign in to manage your privileged access requests.</p>
    <?php if ($error) echo '<p style="color:#dc2626;font-weight:600">'.htmlspecialchars($error).'</p>'; ?>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?=Security::csrfToken()?>">
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" autocomplete="username">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password">
      </div>
      <div style="margin-top:14px"><button type="submit">Login</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
