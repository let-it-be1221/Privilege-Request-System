<?php
// Security bootstrap: session cookie settings, headers, autoload
if (session_status() !== PHP_SESSION_ACTIVE) {
    // detect HTTPS when behind proxies
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $secure = $isHttps;
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header('X-XSS-Protection: 1; mode=block');
// Add HSTS when running over HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if ($isHttps) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
// Tighten CSP and include per-session nonce for inline scripts
// Nonce value is generated in Security::cspNonce(). We set a placeholder here; pages should include the nonce attribute on inline scripts.
if (!function_exists('get_csp_nonce')) {
    function get_csp_nonce() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['_csp_nonce'])) {
            $_SESSION['_csp_nonce'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csp_nonce'];
    }
}
$nonce = get_csp_nonce();
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline';");

require __DIR__ . '/../vendor/autoload.php';
