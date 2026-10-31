<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && currentUser()) {
    verifyCsrf();
    logout();
}
redirect('admin/login.php');
