<?php
use PHPUnit\Framework\TestCase;

final class DBTest extends TestCase {
    public function testPDOConnection() {
        $cfg = require __DIR__ . '/../config.php';
        $pdo = new PDO("mysql:host={$cfg['db']['host']};dbname={$cfg['db']['dbname']};charset={$cfg['db']['charset']}", $cfg['db']['user'], $cfg['db']['pass']);
        $this->assertInstanceOf(PDO::class, $pdo);
    }
}
