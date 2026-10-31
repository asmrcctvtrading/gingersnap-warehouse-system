<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('subcons', 'view');

$db = getDB();

// ── KPI counts ──────────────────────────────────────────────────────────────
$kpiStmt = $db->query(
    "SELECT
       COUNT(*)                  AS total,
       SUM(status = 'active')    AS active_count,
       SUM(status = 'inactive')  AS inactive_count
     FROM subcons"
);
$kpi = $kpiStmt->fetch();

$openPoStmt = $db->query(
    "SELECT COUNT(DISTINCT s.subcon_id)
     FROM subcons s
     INNER JOIN purchase_orders po ON po.subcon_id = s.subcon_id
     WHERE po.status IN ('NEW', 'WIP')"
);
$kpi['with_open_pos'] = (int)$openPoStmt->fetchColumn();

// ── Filters ─────────────────────────────────────────────────────────────────
$filterSearch = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status']      ?? '';

$validStatuses = ['active', 'inactive'];

$where  = ['1=1'];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(s.subcon_name LIKE ? OR s.contact_person LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
    $where[]  = 's.status = ?';
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ───────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stCount = $db->prepare("SELECT COUNT(*) FROM subcons s WHERE {$whereClause}");
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stList = $db->prepare(
    "SELECT s.*,
            COALESCE(SUM(po.status IN ('NEW','WIP')), 0) AS open_pos
     FROM subcons s
     LEFT JOIN purchase_orders po ON po.subcon_id = s.subcon_id
     WHERE {$whereClause}
     GROUP BY s.subcon_id
     ORDER BY s.subcon_name
     LIMIT {$perPage} OFFSET {$offset}"
);
$stList->execute($params);
$rows = $stList->fetchAll();

$pageTitle = 'Subcontractors';
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
        <h5 class="mb-0"><i class="bi bi-buildings me-1"></i>Subcontractors</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php if (can('subcons', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Subcontractor
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">Total Subcontractors</div>
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
            <div class="kpi-value text-muted"><?= number_format((int)$kpi['inactive_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">With Open POs</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['with_open_pos']) ?></div>
        </div>
    </div>
</div>

<!-- ── Filters ──────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-5">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="Name or contact person…">
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active"   <?= $filterStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-sm-4 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- ── Table ────────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Subcon Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="text-center">Open POs</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No subcontractors found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
                $statusBadge = $row['status'] === 'active' ? 'success' : 'secondary';
                $openPos     = (int)$row['open_pos'];
            ?>
            <tr>
                <td class="fw-semibold"><?= h($row['subcon_name']) ?></td>
                <td><?= $row['contact_person'] ? h($row['contact_person']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $row['phone'] ? h($row['phone']) : '<span class="text-muted">—</span>' ?></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($row['status']) ?></span></td>
                <td class="text-center">
                    <?php if ($openPos > 0): ?>
                    <span class="badge bg-warning text-dark"><?= $openPos ?></span>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <?php if (can('subcons', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$row['subcon_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php if ($row['status'] === 'active'): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Deactivate &quot;<?= h($row['subcon_name']) ?>&quot;?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= (int)$row['subcon_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                            <i class="bi bi-toggle-on"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Reactivate &quot;<?= h($row['subcon_name']) ?>&quot;?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= (int)$row['subcon_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Activate">
                            <i class="bi bi-toggle-off"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1):
    $queryBase = http_build_query(array_filter(['search' => $filterSearch, 'status' => $filterStatus]));
    $queryBase = $queryBase ? $queryBase . '&' : '';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
