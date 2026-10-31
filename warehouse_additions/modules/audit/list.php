<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('audit', 'view');

$db = getDB();

// ── Filters ─────────────────────────────────────────────────────────────────
$filterSearch    = trim($_GET['search']    ?? '');
$filterModule    = trim($_GET['module']    ?? '');
$filterDateFrom  = trim($_GET['date_from'] ?? '');
$filterDateTo    = trim($_GET['date_to']   ?? '');

// Dropdown of distinct modules
$modStmt    = $db->query("SELECT DISTINCT module FROM audit_log ORDER BY module");
$allModules = $modStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE ──────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(al.username LIKE ? OR al.description LIKE ? OR al.action LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterModule !== '' && in_array($filterModule, $allModules, true)) {
    $where[]  = 'al.module = ?';
    $params[] = $filterModule;
}
if ($filterDateFrom !== '') {
    $where[]  = 'DATE(al.created_at) >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[]  = 'DATE(al.created_at) <= ?';
    $params[] = $filterDateTo;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ───────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stCount = $db->prepare("SELECT COUNT(*) FROM audit_log al WHERE {$whereClause}");
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stList = $db->prepare(
    "SELECT al.*
     FROM audit_log al
     WHERE {$whereClause}
     ORDER BY al.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stList->execute($params);
$rows = $stList->fetchAll();

$pageTitle = 'Audit Log';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-journal-text me-1"></i>Audit Log</h5>
    <span class="badge bg-secondary"><?= number_format($totalRows) ?></span>
    <span class="text-muted small ms-1">Read-only</span>
</div>

<!-- ── Filters ──────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="User, action, description…">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Module</label>
            <select name="module" class="form-select form-select-sm">
                <option value="">All Modules</option>
                <?php foreach ($allModules as $mod): ?>
                <option value="<?= h($mod) ?>" <?= $filterModule === $mod ? 'selected' : '' ?>><?= h($mod) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterDateFrom) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterDateTo) ?>">
        </div>
        <div class="col-sm-3 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- ── Table ────────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="white-space:nowrap;">Date / Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th class="text-end">Record ID</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No audit entries found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td style="white-space:nowrap;font-size:12px;">
                    <?= h(date('Y-m-d H:i:s', strtotime($row['created_at']))) ?>
                </td>
                <td><?= $row['username'] ? h($row['username']) : '<span class="text-muted">—</span>' ?></td>
                <td>
                    <?php
                    $actionBadge = match(true) {
                        str_starts_with($row['action'], 'CREATE') => 'success',
                        str_starts_with($row['action'], 'UPDATE') => 'primary',
                        str_starts_with($row['action'], 'DELETE') => 'danger',
                        str_starts_with($row['action'], 'TOGGLE') => 'warning',
                        str_starts_with($row['action'], 'CHANGE') => 'info',
                        str_starts_with($row['action'], 'LOGIN')  => 'secondary',
                        default => 'secondary',
                    };
                    ?>
                    <span class="badge bg-<?= $actionBadge ?>"><?= h($row['action']) ?></span>
                </td>
                <td><span class="badge bg-light text-dark border"><?= h($row['module']) ?></span></td>
                <td class="text-end text-muted" style="font-size:12px;">
                    <?= $row['record_id'] ? (int)$row['record_id'] : '—' ?>
                </td>
                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="<?= h($row['description'] ?? '') ?>">
                    <?= $row['description'] ? h($row['description']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td style="font-size:12px;"><?= $row['ip_address'] ? h($row['ip_address']) : '<span class="text-muted">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1):
    $queryBase = http_build_query(array_filter([
        'search'    => $filterSearch,
        'module'    => $filterModule,
        'date_from' => $filterDateFrom,
        'date_to'   => $filterDateTo,
    ]));
    $queryBase = $queryBase ? $queryBase . '&' : '';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
        if ($start > 1): ?>
        <li class="page-item disabled"><span class="page-link">…</span></li>
        <?php endif;
        for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor;
        if ($end < $totalPages): ?>
        <li class="page-item disabled"><span class="page-link">…</span></li>
        <li class="page-item">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
        </li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
