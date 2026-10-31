<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('roles', 'edit');

$db     = getDB();
$roleId = (int)($_GET['role_id'] ?? 0);

if ($roleId <= 0) {
    setFlash('error', 'No role specified.');
    redirect('/modules/roles/list.php');
}
if ($roleId === 1) {
    setFlash('error', 'Admin role permissions cannot be modified via this interface.');
    redirect('/modules/roles/list.php');
}

// Load the role
$stRole = $db->prepare("SELECT role_id, role_name FROM roles WHERE role_id = ?");
$stRole->execute([$roleId]);
$role = $stRole->fetch();
if (!$role) {
    setFlash('error', 'Role not found.');
    redirect('/modules/roles/list.php');
}

// Load all permissions grouped by module
$perms = $db->query(
    "SELECT permission_id, module_key, action_key, label
     FROM permissions
     ORDER BY module_key ASC, action_key ASC"
)->fetchAll();

$byModule = [];
foreach ($perms as $p) {
    $byModule[$p['module_key']][] = $p;
}

// Load current permissions for this role into a lookup set
$stRp = $db->prepare(
    "SELECT permission_id FROM role_permissions WHERE role_id = ?"
);
$stRp->execute([$roleId]);
$currentPerms = array_flip($stRp->fetchAll(PDO::FETCH_COLUMN));

$pageTitle = 'Edit Permissions: ' . $role['role_name'];
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
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-shield-lock me-1"></i>
        Edit Permissions: <?= h($role['role_name']) ?>
    </h5>
</div>

<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action"  value="save_role_permissions">
    <input type="hidden" name="role_id" value="<?= (int)$roleId ?>">

    <?php foreach ($byModule as $moduleName => $modulePerms): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold py-2 text-uppercase small">
            <i class="bi bi-grid me-1"></i><?= h($moduleName) ?>
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
            <?php foreach ($modulePerms as $perm): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           name="perms[]"
                           value="<?= (int)$perm['permission_id'] ?>"
                           id="perm_<?= (int)$perm['permission_id'] ?>"
                           <?= isset($currentPerms[$perm['permission_id']]) ? 'checked' : '' ?>>
                    <label class="form-check-label small"
                           for="perm_<?= (int)$perm['permission_id'] ?>">
                        <?= h($perm['label']) ?>
                        <span class="text-muted">(<?= h($perm['action_key']) ?>)</span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-save me-1"></i>Save Permissions
        </button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
