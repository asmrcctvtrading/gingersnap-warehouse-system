<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

// POST + CSRF only, so a third-party page cannot log users out with an image tag or link.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verifyCsrf();
currentUser();
logout();
session_start();
flash('info', 'You have been signed out.');
redirect('login.php');
