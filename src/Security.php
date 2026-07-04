<?php
namespace App;

class Security {
    public static function csrfToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrf($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return !empty($token) && !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'],$token);
    }

    public static function e($v) {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function validateInput($value, $maxLen = 2000) {
        $v = trim($value ?? '');
        if ($v === '') return '';
        if (strlen($v) > $maxLen) return substr($v,0,$maxLen);
        return $v;
    }

    public static function cspNonce() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csp_nonce'])) {
            $_SESSION['_csp_nonce'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csp_nonce'];
    }
}
