<?php
namespace App;

class Role {
    public static function getNameById($id) {
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = :id');
        $stmt->execute([':id'=>$id]);
        $r = $stmt->fetch();
        return $r['name'] ?? null;
    }

    public static function getUsersByRoleName($roleName) {
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare('SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = :name');
        $stmt->execute([':name'=>$roleName]);
        return $stmt->fetchAll();
    }

    public static function userHasRole($user, $roleName) {
        if (!$user) return false;
        $r = self::getNameById($user['role_id']);
        return $r === $roleName;
    }
}
