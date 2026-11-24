<?php
defined('APP') || exit;
require_once __DIR__ . '/../inc/alerts.php';
$tab = in_array($_GET['tab'] ?? '', ['notifications', 'users', 'account'], true) ? $_GET['tab'] : 'general';

if (is_post()) {
    $act = $_POST['action'] ?? '';
    if ($act === 'general') {
        require_admin(); set_setting('company', trim($_POST['company'] ?? '') ?: 'Gingersnap'); flash('Saved.');
    } elseif ($act === 'notifications') {
        require_admin();
        $email = trim($_POST['alert_email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('That email address is not valid.', 'err'); redirect('index.php?p=settings&tab=notifications'); }
        set_setting('alert_low_stock', empty($_POST['alert_low_stock']) ? '0' : '1');
        set_setting('alert_expiry', empty($_POST['alert_expiry']) ? '0' : '1');
        set_setting('expiry_days', (string) max(1, min(365, (int) ($_POST['expiry_days'] ?? 30))));
        set_setting('alert_email', $email);
        flash('Notification settings saved.');
    } elseif ($act === 'send_now') {
        require_admin(); flash(send_alerts());
    } elseif ($act === 'user') {
        require_admin();
        $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8 || trim($_POST['name'] ?? '') === '') flash('Name, a valid email and a password of 8+ characters are required.', 'err');
        else {
            try { insert('INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,?)',
                [trim($_POST['name']), $email, password_hash($pw, PASSWORD_DEFAULT), ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'staff', now()]); flash('User added.'); }
            catch (PDOException $ex) { flash('That email is already registered.', 'err'); }
        }
    } elseif ($act === 'password') {
        $u = one('SELECT * FROM users WHERE id = ?', [current_user()['id']]);
        if (!$u || !password_verify($_POST['current'] ?? '', $u['password_hash'])) flash('Current password is wrong.', 'err');
        elseif (strlen($_POST['new'] ?? '') < 8) flash('New password must be at least 8 characters.', 'err');
        else { run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $u['id']]); flash('Password changed.'); }
    }
    redirect('index.php?p=settings&tab=' . $tab);
}
layout_start('Settings', 'settings');
$tabs = ['general' => 'General', 'notifications' => 'Notifications', 'users' => 'Users', 'account' => 'My account'];
?>
<div class="tabs"><?php foreach ($tabs as $k => $l): ?><a class="<?= $k === $tab ? 'on' : '' ?>" href="index.php?p=settings&tab=<?= $k ?>"><?= e($l) ?></a><?php endforeach; ?></div>

<?php if ($tab === 'general'): ?>
<form method="post" class="card form narrow"><h2>General</h2><?= csrf_field() ?><input type="hidden" name="action" value="general">
  <label>Company name<input name="company" value="<?= e(setting('company', 'Gingersnap')) ?>" <?= is_admin() ? '' : 'disabled' ?>></label>
  <?php if (is_admin()): ?><button class="btn primary">Save</button><?php else: ?><p class="muted">Only admins can change this.</p><?php endif; ?></form>

<?php elseif ($tab === 'notifications'): ?>
<form method="post" class="card form narrow"><h2>Email notifications</h2><?= csrf_field() ?><input type="hidden" name="action" value="notifications">
  <label class="chk"><input type="checkbox" name="alert_low_stock" value="1" <?= setting('alert_low_stock', '1') === '1' ? 'checked' : '' ?>> Warn when stock is at or below its reorder level</label>
  <label class="chk"><input type="checkbox" name="alert_expiry" value="1" <?= setting('alert_expiry', '1') === '1' ? 'checked' : '' ?>> Warn about batches close to expiry</label>
  <label>Days before expiry to warn<input type="number" min="1" max="365" name="expiry_days" value="<?= e(setting('expiry_days', '30')) ?>"></label>
  <label>Send alerts to<input type="email" name="alert_email" value="<?= e(setting('alert_email')) ?>" placeholder="you@example.com"></label>
  <?php if (is_admin()): ?><button class="btn primary">Save</button><?php endif; ?></form>
<?php if (is_admin()): ?>
<form method="post" class="card form narrow"><h2>Send now</h2><?= csrf_field() ?><input type="hidden" name="action" value="send_now">
  <p class="muted">Emails the current alerts using the saved settings.</p><button class="btn">Send alert email</button></form>
<div class="card form narrow"><h2>Automatic daily alerts</h2>
  <p class="muted">In cPanel open <b>Cron Jobs</b>, add a daily job with this command (change the path to where you uploaded the app):</p>
  <code class="block">/usr/local/bin/php <?= e(ROOT) ?>/cron/alerts.php</code></div>
<?php endif; ?>

<?php elseif ($tab === 'users'): require_admin(); $users = q('SELECT id, name, email, role, created_at FROM users ORDER BY name'); ?>
<div class="grid2 wide-left"><div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Added</th></tr></thead><tbody>
  <?php foreach ($users as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Add user</h2><?= csrf_field() ?><input type="hidden" name="action" value="user">
    <label>Name<input name="name" required></label><label>Email<input type="email" name="email" required></label>
    <label>Password (8+ characters)<input type="password" name="password" minlength="8" required></label>
    <label>Role<select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></label><button class="btn primary">Add user</button></form></div>

<?php else: ?>
<form method="post" class="card form narrow"><h2>Change my password</h2><?= csrf_field() ?><input type="hidden" name="action" value="password">
  <label>Current password<input type="password" name="current" required></label><label>New password (8+ characters)<input type="password" name="new" minlength="8" required></label>
  <button class="btn primary">Change password</button></form>
<?php endif; layout_end();
