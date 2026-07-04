<?php
namespace App;

class AuditLogger {
    public static function log($action, $userId = null, $data = null) {
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, data, ip) VALUES (:u,:a,:d,:ip)');
        $stmt->execute([':u'=>$userId,':a'=>$action,':d'=>$data,':ip'=>$_SERVER['REMOTE_ADDR'] ?? '']);
    }
}
