<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
use App\DB;

$argv0 = array_shift($argv);
if (count($argv) < 3) {
    echo "Usage: php create_user.php username password role_name [email]\n";
    exit(1);
}
$username = $argv[0];
$password = $argv[1];
$roleName = $argv[2];
$email = $argv[3] ?? ($username . '@example.local');

$pdo = DB::getPDO();
$stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :n LIMIT 1');
$stmt->execute([':n'=>$roleName]);
$roleId = $stmt->fetchColumn();
if (!$roleId) {
    echo "Role not found: $roleName\n";
    exit(2);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $pdo->prepare('INSERT INTO users (username,password,email,role_id,full_name) VALUES (:u,:p,:e,:r,:f)');
$ins->execute([':u'=>$username,':p'=>$hash,':e'=>$email,':r'=>$roleId,':f'=>'']);
echo "Created user $username with role $roleName\n";
