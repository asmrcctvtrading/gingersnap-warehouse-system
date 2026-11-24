<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
require_login();

$page = preg_replace('/[^a-z_]/', '', $_GET['p'] ?? 'dashboard');
$file = __DIR__ . '/pages/' . $page . '.php';
if (!is_file($file)) { $page = 'dashboard'; $file = __DIR__ . '/pages/dashboard.php'; }
if (is_post()) post_guard();
require $file;
