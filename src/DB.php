<?php
namespace App;

class DB {
    private static $pdo;
    public static function getPDO() {
        if (self::$pdo) return self::$pdo;
        $cfg = require __DIR__ . '/../config.php';
        $db = $cfg['db'];
        $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
        $opt = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];
        self::$pdo = new \PDO($dsn, $db['user'], $db['pass'], $opt);
        return self::$pdo;
    }
}
