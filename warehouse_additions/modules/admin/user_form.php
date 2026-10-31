<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('users', 'view');

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$rolesStmt = $db->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
$roles = $rolesStmt->fetchAll();

if ($isEdit) {
    $stmt = $db->prepare("SELECT user_id, username, full_name, role_id, status FROM users WHERE user_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        setFlash('error', 'User not found.');
        redirect('/modules/admin/users.php');
    }
} else {
    $user = [
        'user_id'   => 0,
        'username'  => '',
        'full_name' => '',
        'role_id'   => '',
        'status'    => 'active',
    ];
}

$pageTitle = $isEdit ? 'Edit User' : 'New User';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="users.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-person-gear me-1"></i><?= $isEdit ? 'Edit User' : 'New User' ?>
    </h5>
</div>

<div class="row">
<div class="col-lg-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="user_action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update_user' : 'create_user' ?>">
                <input type="hidden" name="id" value="<?= (int)$user['user_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Full Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= h($user['full_name']) ?>"
                           maxlength="150" required
                           placeholder="e.g. Juan dela Cruz">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Username <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="username" class="form-control"
                           value="<?= h($user['username']) ?>"
                           maxlength="80" required
                           placeholder="e.g. jdelacruz"
                           autocomplete="username">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Role</label>
                    <select name="role_id" class="form-select">
                        <option value="">— No Role —</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['role_id'] ?>"
                            <?= (string)$r['role_id'] === (string)$user['role_id'] ? 'selected' : '' ?>>
                            <?= h($r['role_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isEdit): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $user['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Password <?= $isEdit ? '<span class="text-muted fw-normal">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= $isEdit ? '' : 'required' ?>
                           autocomplete="new-password"
                           placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Enter password' ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <?= $isEdit ? 'Save Changes' : 'Create User' ?>
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
