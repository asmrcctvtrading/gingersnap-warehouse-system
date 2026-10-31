<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('users', 'view');

$db = getDB();

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$where  = $search !== '' ? 'WHERE (u.full_name LIKE :q OR u.username LIKE :q)' : '';
$params = $search !== '' ? ['q' => '%' . $search . '%'] : [];

$totalStmt = $db->prepare("SELECT COUNT(*) FROM users u $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$listStmt = $db->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.status, u.created_at,
            r.role_name
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     $where
     ORDER BY u.full_name
     LIMIT :lim OFFSET :off"
);
if ($search !== '') {
    $listStmt->bindValue('q', '%' . $search . '%');
}
$listStmt->bindValue('lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue('off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

$rolesStmt = $db->query("SELECT role_id, role_name FROM roles ORDER BY role_name");
$roles = $rolesStmt->fetchAll();

$myId = (int)($_SESSION['user_id'] ?? 0);

$pageTitle = 'Manage Users';
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
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0"><i class="bi bi-person-gear me-1"></i>Manage Users</h5>
        <span class="badge bg-secondary"><?= $total ?></span>
    </div>
    <?php if (can('users', 'create') || can('users', 'view')): ?>
    <a href="user_form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add User
    </a>
    <?php endif; ?>
</div>

<!-- Search -->
<form method="get" class="mb-3 d-flex gap-2" style="max-width:420px">
    <input type="text" name="search" class="form-control form-control-sm"
           placeholder="Search name or username..." value="<?= h($search) ?>">
    <button type="submit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-search"></i>
    </button>
    <?php if ($search !== ''): ?>
    <a href="users.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-x-lg"></i>
    </a>
    <?php endif; ?>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
            <?php $isMe = ((int)$row['user_id'] === $myId); ?>
            <tr>
                <td class="fw-semibold">
                    <?= h($row['full_name']) ?>
                    <?php if ($isMe): ?>
                    <span class="badge bg-info text-dark ms-1" style="font-size:10px;">You</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted"><?= h($row['username']) ?></td>
                <td><?= h($row['role_name'] ?? '—') ?></td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <!-- Edit -->
                    <a href="user_form.php?id=<?= (int)$row['user_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <!-- Reset Password -->
                    <form method="post" action="user_action.php" class="d-inline"
                          onsubmit="return confirm('Reset password for &quot;<?= h($row['username']) ?>&quot; to the temporary password?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Reset Password">
                            <i class="bi bi-key"></i>
                        </button>
                    </form>
                    <!-- Toggle Status — blocked for own account -->
                    <?php if (!$isMe): ?>
                    <form method="post" action="user_action.php" class="d-inline"
                          onsubmit="return confirm('<?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?> user &quot;<?= h($row['username']) ?>&quot;?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= (int)$row['user_id'] ?>">
                        <?php if ($row['status'] === 'active'): ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                            <i class="bi bi-person-slash"></i>
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                            <i class="bi bi-person-check"></i>
                        </button>
                        <?php endif; ?>
                    </form>
                    <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot change your own status">
                        <i class="bi bi-person-slash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <?= $search !== '' ? 'No users match your search.' : 'No users found.' ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
