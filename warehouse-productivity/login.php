<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';

if (currentUser() !== null) {
    redirect('index.php');
}

$error = null;
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = clean_str($_POST['username'] ?? null, 50) ?? '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        [$ok, $msg] = attemptLogin($username, $password);
        if ($ok) {
            redirect(currentUser()['must_change_password'] ? 'change_password.php' : 'index.php');
        }
        $error = $msg;
    }
}

page_header('Sign in');
?>
<div class="card card-narrow">
  <h1>Sign in</h1>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= h($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrfField() ?>
    <label for="username">Username</label>
    <input class="form-control" id="username" name="username" type="text" maxlength="50" value="<?= h($username) ?>" autofocus required>
    <label for="password">Password</label>
    <input class="form-control" id="password" name="password" type="password" maxlength="200" autocomplete="current-password" required>
    <button class="btn mt" type="submit">Sign in</button>
  </form>
</div>
<?php page_footer();
