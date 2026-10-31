<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('users', 'view');

$db = getDB();

// ── KPI counts ─────────────────────────────────────────────────────────────
$stKpi = $db->query(
    "SELECT
       COUNT(*)                   AS total,
       SUM(status = 'active')     AS active_count,
       SUM(status = 'inactive')   AS inactive_count,
       SUM(role_id = 1)           AS admin_count
     FROM users"
);
$kpi = $stKpi->fetch();

// ── Filters ────────────────────────────────────────────────────────────────
$filterSearch = trim($_GET['search']   ?? '');
$filterRole   = (int)($_GET['role_id'] ?? 0);
$filterStatus = $_GET['status']        ?? '';

$validStatuses = ['active', 'inactive'];

$where  = ['1=1'];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterRole > 0) {
    $where[]  = 'u.role_id = ?';
    $params[] = $filterRole;
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
    $where[]  = 'u.status = ?';
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stCount = $db->prepare(
    "SELECT COUNT(*) FROM users u WHERE {$whereClause}"
);
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stRows = $db->prepare(
    "SELECT u.user_id, u.username, u.full_name, u.email,
            u.role_id, u.status, u.created_at,
            r.role_name
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     WHERE {$whereClause}
     ORDER BY u.full_name ASC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stRows->execute($params);
$rows = $stRows->fetchAll();

// Load roles for the filter dropdown
$rolesAll = $db->query(
    "SELECT role_id, role_name FROM roles ORDER BY role_id"
)->fetchAll();

$myId      = (int)($_SESSION['user_id'] ?? 0);
$pageTitle = 'User Management';
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
        <h5 class="mb-0"><i class="bi bi-people me-1"></i>User Management</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php if (can('users', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New User
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">Total Users</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">Active</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['active_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#94a3b8;">
            <div class="kpi-label">Inactive</div>
            <div class="kpi-value text-secondary"><?= number_format((int)$kpi['inactive_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">Admin Count</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['admin_count']) ?></div>
        </div>
    </div>
</div>

<!-- ── Filters ─────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="Name, username, or email…">
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Role</label>
            <select name="role_id" class="form-select form-select-sm">
                <option value="0">All Roles</option>
                <?php foreach ($rolesAll as $r): ?>
                <option value="<?= (int)$r['role_id'] ?>"
                    <?= $filterRole === (int)$r['role_id'] ? 'selected' : '' ?>>
                    <?= h($r['role_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-sm-3 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- ── Table ───────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
                $isMe = ((int)$row['user_id'] === $myId);
            ?>
            <tr <?= $isMe ? 'class="table-info"' : '' ?>>
                <td class="fw-semibold">
                    <?= h($row['full_name']) ?>
                    <?php if ($isMe): ?>
                    <span class="badge bg-info text-dark ms-1" style="font-size:10px;">You</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted"><?= h($row['username']) ?></td>
                <td class="small text-muted">
                    <?= ($row['email'] ?? '') !== '' ? h($row['email']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <?= ($row['role_name'] ?? '') !== ''
                        ? h($row['role_name'])
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted">
                    <?= ($row['created_at'] ?? '') !== ''
                        ? h(date('Y-m-d', strtotime($row['created_at'])))
                        : '—' ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <?php if (can('users', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$row['user_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('users', 'delete') && !$isMe): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('<?= $row['status'] === 'active' ? 'Deactivate' : 'Activate' ?> user &quot;<?= h(addslashes($row['username'])) ?>&quot;?')">
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
                    <?php elseif ($isMe): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                            title="Cannot change your own status">
                        <i class="bi bi-person-slash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++):
            $qs = http_build_query(array_merge($_GET, ['page' => $p]));
        ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="list.php?<?= $qs ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
