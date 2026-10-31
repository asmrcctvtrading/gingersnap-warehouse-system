<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('users.manage');   // admin only
$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $op = (string)($_POST['op'] ?? '');
    $uid = isset($_POST['user_id']) && ctype_digit((string)$_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $roleKey = (string)($_POST['role'] ?? '');
    $rq = db()->prepare('SELECT role_id FROM roles WHERE role_key = ?');
    $rq->execute([$roleKey]);
    $roleId = (int)$rq->fetchColumn();
    $brgy = isset($_POST['barangay_id']) && ctype_digit((string)$_POST['barangay_id']) && $_POST['barangay_id'] !== '' ? (int)$_POST['barangay_id'] : null;

    if ($op === 'create') {
        $username = trim((string)($_POST['username'] ?? ''));
        $full = clean_str($_POST['full_name'] ?? null, 120);
        $pw = (string)($_POST['password'] ?? '');
        $perr = password_policy_error($pw);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) { flash('error', 'Username: 3-50 letters, numbers, . _ -'); }
        elseif ($full === null) { flash('error', 'Full name is required.'); }
        elseif (!$roleId) { flash('error', 'Choose a role.'); }
        elseif ($perr) { flash('error', $perr); }
        else {
            try {
                db()->prepare('INSERT INTO users (username, full_name, password_hash, role_id, barangay_id) VALUES (?,?,?,?,?)')
                    ->execute([$username, $full, password_hash($pw, PASSWORD_DEFAULT), $roleId, $brgy]);
                logAudit('create', 'user', (int)db()->lastInsertId(), $username);
                flash('ok', 'User created.');
            } catch (PDOException $e) {
                flash('error', $e->getCode() === '23000' ? 'That username is taken.' : 'Could not create the user.');
            }
        }
    } elseif ($op === 'update' && $uid) {
        $status = ($_POST['status'] ?? '') === 'disabled' ? 'disabled' : 'active';
        if ($uid === $me['user_id'] && ($status === 'disabled' || $roleKey !== 'admin')) {
            flash('error', 'You cannot disable or demote your own account.');
        } elseif (!$roleId) {
            flash('error', 'Choose a role.');
        } else {
            db()->prepare('UPDATE users SET role_id = ?, barangay_id = ?, status = ?, failed_logins = 0, locked_until = NULL WHERE user_id = ?')
                ->execute([$roleId, $brgy, $status, $uid]);
            $newPw = (string)($_POST['new_password'] ?? '');
            if ($newPw !== '') {
                if ($e = password_policy_error($newPw)) { flash('error', $e); redirect('admin/users.php'); }
                db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')->execute([password_hash($newPw, PASSWORD_DEFAULT), $uid]);
            }
            logAudit('update', 'user', $uid, "role=$roleKey status=$status" . ($newPw !== '' ? ' password reset' : ''));
            flash('ok', 'Saved.');
        }
    }
    redirect('admin/users.php');
}

$users = db()->query('SELECT u.*, r.role_key FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.username')->fetchAll();
$roles = db()->query('SELECT role_key, label FROM roles ORDER BY role_id')->fetchAll();
$brgys = db()->query('SELECT barangay_id, name FROM barangays ORDER BY name')->fetchAll();

$roleSel = static function (string $cur) use ($roles): string {
    $o = '';
    foreach ($roles as $r) { $o .= '<option value="' . h($r['role_key']) . '"' . ($r['role_key'] === $cur ? ' selected' : '') . '>' . h($r['label']) . '</option>'; }
    return $o;
};
$brgySel = static function (?int $cur) use ($brgys): string {
    $o = '<option value="">All barangays</option>';
    foreach ($brgys as $b) { $o .= '<option value="' . (int)$b['barangay_id'] . '"' . ($cur === (int)$b['barangay_id'] ? ' selected' : '') . '>' . h($b['name']) . '</option>'; }
    return $o;
};

admin_header('Users', 'users');
?>
<h1>Users</h1>
<div class="tablewrap"><table class="data"><thead><tr><th>User</th><th>Role</th><th>Barangay scope</th><th>Status</th><th>New password</th><th>Last login</th><th></th></tr></thead><tbody>
<?php foreach ($users as $u): $fid = 'uf' . (int)$u['user_id']; ?>
<tr>
  <td><form method="post" id="<?= $fid ?>"><?= csrfField() ?><input type="hidden" name="op" value="update"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"></form>
      <?= h($u['username']) ?><br><span class="muted"><?= h($u['full_name']) ?></span></td>
  <td><select form="<?= $fid ?>" name="role" aria-label="Role"><?= $roleSel($u['role_key']) ?></select></td>
  <td><select form="<?= $fid ?>" name="barangay_id" aria-label="Barangay"><?= $brgySel($u['barangay_id'] !== null ? (int)$u['barangay_id'] : null) ?></select></td>
  <td><select form="<?= $fid ?>" name="status" aria-label="Status"><option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="disabled" <?= $u['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option></select></td>
  <td><input form="<?= $fid ?>" type="password" name="new_password" autocomplete="new-password" placeholder="leave blank" aria-label="New password"></td>
  <td><?= h($u['last_login_at'] ?? 'never') ?></td>
  <td><button form="<?= $fid ?>" type="submit">Save</button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<h2>Add user</h2>
<form method="post" class="formgrid" autocomplete="off">
  <?= csrfField() ?><input type="hidden" name="op" value="create">
  <div><label for="n_user">Username</label><input type="text" id="n_user" name="username" required maxlength="50"></div>
  <div><label for="n_name">Full name</label><input type="text" id="n_name" name="full_name" required maxlength="120"></div>
  <div><label for="n_role">Role</label><select id="n_role" name="role"><?= $roleSel('encoder') ?></select></div>
  <div><label for="n_brgy">Barangay scope</label><select id="n_brgy" name="barangay_id"><?= $brgySel(null) ?></select></div>
  <div><label for="n_pw">Password (10+ chars, letters and numbers)</label><input type="password" id="n_pw" name="password" required autocomplete="new-password"></div>
  <div><button type="submit">Create user</button></div>
</form>
<?php admin_footer(); ?>
