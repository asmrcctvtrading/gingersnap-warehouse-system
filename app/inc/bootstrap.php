<?php
define('APP', 1);
define('ROOT', dirname(__DIR__));
if (!is_file(ROOT . '/config.php')) {
    header('Location: install.php');
    exit;
}
$CFG = require ROOT . '/config.php';
date_default_timezone_set($CFG['timezone'] ?? 'UTC');
if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_name('gsw');
    session_start();
}
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
