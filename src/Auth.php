<?php
namespace App;

class Auth {
    public static function attempt($username, $password) {
        $cfg = require __DIR__ . '/../config.php';
        $pdo = DB::getPDO();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // rate limit: max 5 failed attempts per 15 minutes per IP or per username
        $limit = 5;
        $window = 15; // minutes
        $stmtAttempts = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE (ip = :ip OR username = :u) AND success = 0 AND created_at > (NOW() - INTERVAL :mins MINUTE)');
        $stmtAttempts->execute([':ip'=>$ip,':u'=>$username,':mins'=>$window]);
        $failed = (int)$stmtAttempts->fetchColumn();
        if ($failed >= $limit) {
            return false;
        }
        // If LDAP is configured, attempt LDAP bind first
        if (!empty($cfg['use_ldap'])) {
            if (extension_loaded('ldap')) {
                $ldapConn = ldap_connect($cfg['ldap']['host']);
                if ($ldapConn) {
                    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    // Attempt to bind with the user's credentials (simple bind)
                    $userDn = $username; // in many environments you need full DN or user@domain
                    try {
                        $bind = @ldap_bind($ldapConn, $userDn, $password);
                    } catch (\Throwable $e) {
                        $bind = false;
                    }
                    if ($bind) {
                        // Ensure user exists in local DB, create if needed
                        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u');
                        $stmt->execute([':u'=>$username]);
                        $user = $stmt->fetch();
                        if (!$user) {
                            // create a local user record with default role 'employee'
                            $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = :n LIMIT 1');
                            $roleStmt->execute([':n'=>'employee']);
                            $roleId = $roleStmt->fetchColumn() ?: 1;
                            $ins = $pdo->prepare('INSERT INTO users (username,email,role_id) VALUES (:u,:e,:r)');
                            $ins->execute([':u'=>$username,':e'=>$username.'@example.local',':r'=>$roleId]);
                            $userId = $pdo->lastInsertId();
                            $user = ['id'=>$userId,'username'=>$username,'email'=>$username.'@example.local','role_id'=>$roleId];
                        }
                        session_regenerate_id(true);
                        $_SESSION['user'] = ['id'=>$user['id'],'username'=>$user['username'],'email'=>$user['email'],'role_id'=>$user['role_id']];
                        return true;
                    }
                }
            }
            // If LDAP configured but not successful, fall through to DB auth
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u');
        $stmt->execute([':u'=>$username]);
        $user = $stmt->fetch();
        if (!$user) return false;
        if ($user['password'] && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>$user['id'],'username'=>$user['username'],'email'=>$user['email'],'role_id'=>$user['role_id']];
            // mark success
            $ins = $pdo->prepare('INSERT INTO login_attempts (username,ip,success) VALUES (:u,:ip,1)');
            $ins->execute([':u'=>$username,':ip'=>$ip]);
            return true;
        }
        // record failed attempt
        $ins = $pdo->prepare('INSERT INTO login_attempts (username,ip,success) VALUES (:u,:ip,0)');
        $ins->execute([':u'=>$username,':ip'=>$ip]);
        return false;
    }

    public static function user() {
        return $_SESSION['user'] ?? null;
    }

    public static function logout(){
        unset($_SESSION['user']);
        session_destroy();
    }
}
