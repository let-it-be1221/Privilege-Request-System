<?php
if (!isset($title)) $title = 'PrivAccess';
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=htmlspecialchars($title)?></title>
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    <header class="site-header">
      <div class="container">
        <div class="site-brand">
          <div class="brand-mark">P</div>
          <div>
            <h1>PrivAccess</h1>
            <div class="muted">Secure access requests</div>
          </div>
        </div>
        <nav>
          <a href="index.php" class="<?=($title === 'Dashboard' ? 'active' : '')?>">Dashboard</a>
          <a href="request_create.php" class="<?=($title === 'Create Request' ? 'active' : '')?>">Create</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>
    <main class="container">
