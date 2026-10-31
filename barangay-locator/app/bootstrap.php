<?php
declare(strict_types=1);

// Single entry for every page and the API. Loads config, libs, session, security headers.
define('APP_ROOT', __DIR__ . '/..');
define('APP_DIR', __DIR__);

$GLOBALS['APP_CONFIG'] = null;
$cfgFile = APP_DIR . '/config.local.php';
if (is_file($cfgFile)) {
    $GLOBALS['APP_CONFIG'] = require $cfgFile;
}

function config(string $key, $default = null)
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
require_once APP_DIR . '/lib/i18n.php';
require_once APP_DIR . '/lib/repo/establishments.php';

if (config('debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
}

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('BRGYSESS');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Security headers. CSP allows only self + the pinned Leaflet CDN (if used) + the configured tile host (img-src https:).
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    // Tile servers (incl. OSM) require a Referer; send only the origin, never the full path, to other sites.
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; "
         . "style-src 'self' 'unsafe-inline' https://unpkg.com;script-src 'self' https://unpkg.com; connect-src 'self' https:; "
         . "frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}
