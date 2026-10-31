<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('roles', 'view');

$db = getDB();

// Load all roles ordered by role_id
$roles = $db->query(
    "SELECT role_id, role_name FROM roles ORDER BY role_id"
)->fetchAll();

// Load all permissions ordered by module then action
$perms = $db->query(
    "SELECT permission_id, module_key, action_key, label
     FROM permissions
     ORDER BY module_key ASC, action_key ASC"
)->fetchAll();

// Load all role_permissions into a fast lookup set: "role_id_permission_id" => true
$rpRows = $db->query("SELECT role_id, permission_id FROM role_permissions")->fetchAll();
$rpSet  = [];
foreach ($rpRows as $rp) {
    $rpSet[$rp['role_id'] . '_' . $rp['permission_id']] = true;
}

// Group permissions by module_key
$byModule = [];
foreach ($perms as $p) {
    $byModule[$p['module_key']][] = $p;
}

$pageTitle = 'Roles & Permissions';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-shield-check me-1"></i>Roles &amp; Permissions</h5>
    <?php if (can('roles', 'edit')): ?>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($roles as $r): ?>
        <?php if ((int)$r['role_id'] !== 1): ?>
        <a href="form.php?role_id=<?= (int)$r['role_id'] ?>"
           class="btn btn-outline-dark btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit <?= h($r['role_name']) ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── RBAC Matrix Table ─────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0 align-middle" style="font-size:13px;">
            <thead class="table-dark">
                <tr>
                    <th style="min-width:240px">Permission</th>
                    <?php foreach ($roles as $r): ?>
                    <th class="text-center" style="min-width:120px">
                        <?= h($r['role_name']) ?>
                        <?php if ((int)$r['role_id'] === 1): ?>
                        <br><span class="badge bg-warning text-dark" style="font-size:10px;">Admin</span>
                        <?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($byModule as $moduleName => $modulePerms): ?>
            <!-- Module group header row -->
            <tr class="table-secondary">
                <td colspan="<?= count($roles) + 1 ?>"
                    class="fw-semibold text-uppercase small py-1 ps-3">
                    <i class="bi bi-grid me-1"></i><?= h($moduleName) ?>
                </td>
            </tr>
            <?php foreach ($modulePerms as $perm): ?>
            <tr>
                <td class="ps-4">
                    <?= h($perm['label']) ?>
                    <span class="text-muted ms-1" style="font-size:11px;">
                        (<?= h($perm['action_key']) ?>)
                    </span>
                </td>
                <?php foreach ($roles as $r): ?>
                <?php $hasIt = isset($rpSet[$r['role_id'] . '_' . $perm['permission_id']]); ?>
                <td class="text-center">
                    <?php if ($hasIt): ?>
                    <i class="bi bi-check-circle-fill text-success"></i>
                    <?php else: ?>
                    <i class="bi bi-dash text-muted"></i>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    Role 1 (Admin) always has all permissions and cannot be modified via this interface.
    Use the "Edit [Role]" buttons above to modify permissions for other roles.
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
