<?php
namespace App;

class Notification {
    public static function enqueue($userId, $requestId, $message) {
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, request_id, message, is_read) VALUES (:u,:r,:m,0)');
        $stmt->execute([':u'=>$userId,':r'=>$requestId,':m'=>$message]);
    }

    public static function sendPending($limit = 50) {
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare('SELECT n.id,n.user_id,n.request_id,n.message,u.email FROM notifications n JOIN users u ON u.id = n.user_id WHERE n.is_read = 0 ORDER BY n.created_at ASC LIMIT :l');
        $stmt->bindValue(':l', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $sent = Mailer::send($r['email'], 'Notification - Request #'.$r['request_id'], $r['message']);
            if ($sent) {
                $u = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
                $u->execute([':id'=>$r['id']]);
            }
        }
        return count($rows);
    }
}
