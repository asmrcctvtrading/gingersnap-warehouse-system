<?php
declare(strict_types=1);

// Single entry for every page. Loads config, libs, session, security headers.
define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);

$GLOBALS['APP_CONFIG'] = null;
$cfgFile = APP_DIR . '/config.local.php';
if (is_file($cfgFile)) {
    $GLOBALS['APP_CONFIG'] = require $cfgFile;
}

function config(string $key, mixed $default = null): mixed
{
    $c = $GLOBALS['APP_CONFIG'] ?? [];
    return $c[$key] ?? $default;
}

function app_installed(): bool
{
    return $GLOBALS['APP_CONFIG'] !== null && is_file(APP_ROOT . '/storage/installed.lock');
}

require_once APP_DIR . '/lib/http.php';
require_once APP_DIR . '/lib/db.php';
require_once APP_DIR . '/lib/csrf.php';
require_once APP_DIR . '/lib/validate.php';
require_once APP_DIR . '/lib/audit.php';
require_once APP_DIR . '/lib/auth.php';

if (!app_installed()) {
    http_response_code(503);
    exit('WPMS is not installed. Run install/install.php first.');
}

if (config('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    $logDir = storage_path('logs');
    if (is_dir($logDir) && is_writable($logDir)) {
        ini_set('error_log', $logDir . '/php-error.log');
    }
}

date_default_timezone_set((string)config('timezone', 'Asia/Manila'));

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('WPMSSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        // Scoped to this app so it never collides with the Warehouse Inventory System session.
        'path'     => base_url() === '' ? '/' : base_url() . '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; "
         . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    header('Cache-Control: no-store');
}
