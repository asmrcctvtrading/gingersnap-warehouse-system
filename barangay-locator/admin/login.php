<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();

if (currentUser()) {
    redirect('admin/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    // Per-IP brake on top of per-account lockout (counts failed logins in the audit log for the last 10 minutes).
    $st = db()->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = 'login_failed' AND ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $st->execute([client_ip()]);
    if ((int)$st->fetchColumn() >= 20) {
        $error = 'Too many failed attempts from this address. Try again later.';
    } else {
        [$ok, $msg] = attemptLogin($username, $password);
        if ($ok) {
            redirect('admin/index.php');
        }
        $error = $msg;
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Staff login</title>
<link rel="stylesheet" href="<?= h(url('assets/css/app.css')) ?>"></head>
<body><main style="max-width:420px">
<h1>Staff login</h1>
<?php if ($error): ?><div class="notice error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
  <?= csrfField() ?>
  <label for="username">Username</label>
  <input type="text" id="username" name="username" required autofocus autocomplete="username">
  <label for="password">Password</label>
  <input type="password" id="password" name="password" required autocomplete="current-password">
  <p><button type="submit">Log in</button></p>
</form>
<p><a href="<?= h(url('index.php')) ?>">← Public site</a></p>
</main></body></html>
