<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';

$u = requireLogin(true);
$forced = $u['must_change_password'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $cur  = is_string($_POST['current'] ?? null) ? $_POST['current'] : '';
    $new  = is_string($_POST['new'] ?? null) ? $_POST['new'] : '';
    $new2 = is_string($_POST['new2'] ?? null) ? $_POST['new2'] : '';

    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    $hash = (string)$st->fetchColumn();

    if (!password_verify($cur, $hash)) {
        $errors[] = 'Current password is incorrect.';
        logAudit('password_change_failed', 'user', $u['id'], ['reason' => 'bad_current_password']);
    }
    if (($e = password_policy_error($new)) !== null) {
        $errors[] = $e;
    }
    if ($new !== $new2) {
        $errors[] = 'The new passwords do not match.';
    }
    if ($new === $cur && $new !== '') {
        $errors[] = 'The new password must differ from the current one.';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        logAudit('password_change', 'user', $u['id']);
        flash('success', 'Password changed.');
        redirect('index.php');
    }
}

page_header('Change password', 'password');
?>
<div class="card card-narrow">
  <h1>Change password</h1>
  <?php if ($forced): ?><div class="alert alert-warning">You must set a new password before continuing.</div><?php endif; ?>
  <?php foreach ($errors as $e): ?><div class="alert alert-danger" role="alert"><?= h($e) ?></div><?php endforeach; ?>
  <form method="post" autocomplete="off">
    <?= csrfField() ?>
    <label for="current">Current password</label>
    <input class="form-control" id="current" name="current" type="password" autocomplete="current-password" required>
    <label for="new">New password (10+ characters, letters and numbers)</label>
    <input class="form-control" id="new" name="new" type="password" autocomplete="new-password" required>
    <label for="new2">Repeat new password</label>
    <input class="form-control" id="new2" name="new2" type="password" autocomplete="new-password" required>
    <button class="btn mt" type="submit">Change password</button>
  </form>
</div>
<?php page_footer();
