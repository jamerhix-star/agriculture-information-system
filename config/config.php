<?php
// App-wide settings. Loaded by every page (through src/bootstrap.php).

define('APP_NAME', 'Agriculture Information System');

$baseUrl = getenv('APP_BASE_URL');
define('BASE_URL', $baseUrl !== false && $baseUrl !== '' ? $baseUrl : '/');

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../src/bootstrap.php';
