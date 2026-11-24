<?php
require __DIR__ . '/inc/bootstrap.php';
if (current_user()) redirect('index.php');
$err = '';
if (is_post()) {
    post_guard();
    $u = one('SELECT * FROM users WHERE email = ?', [strtolower(trim($_POST['email'] ?? ''))]);
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'role' => $u['role']];
        redirect('index.php');
    }
    usleep(600000);
    $err = 'Wrong email or password.';
}
?><!doctype html>
<html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Sign in</title>
<script>try{var t=localStorage.getItem('gsw:theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
<link rel="stylesheet" href="assets/app.css"></head>
<body class="authbody">
<form method="post" class="card auth">
  <div class="brand"><span class="logo">G</span><b><?= e($CFG['app_name'] ?? 'Gingersnap') ?></b></div>
  <?php if ($err): ?><div class="alert a-err"><?= e($err) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label>Email<input type="email" name="email" required autofocus></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn primary" type="submit">Sign in</button>
</form>
</body></html>
