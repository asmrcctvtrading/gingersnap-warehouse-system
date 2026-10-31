<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('gatepass', 'view');

$db = getDB();

// ── KPI counts ─────────────────────────────────────────────────────────────
$stKpi = $db->query(
    "SELECT
       COUNT(*)                                   AS total,
       SUM(gp_status IN ('OPEN','CREATED'))       AS open_count,
       SUM(gp_status = 'CLOSED')                  AS closed_count,
       SUM(gp_status = 'CANCELLED')               AS cancelled_count,
       SUM(gp_status = 'PENDING')                 AS pending_count
     FROM gatepasses"
);
$kpi = $stKpi->fetch();

// ── Filters ────────────────────────────────────────────────────────────────
$filterSearch   = trim($_GET['search']    ?? '');
$filterType     = $_GET['gp_type']        ?? '';
$filterStatus   = $_GET['status']         ?? '';
$filterDateFrom = $_GET['date_from']      ?? '';
$filterDateTo   = $_GET['date_to']        ?? '';

$validTypes = [
    'STOCK_TRANSFER', 'RETURNABLE',
    'OUTGOING', 'INCOMING', 'RETURN', 'TRANSFER', 'INTERNAL_CONSUMPTION',
];
$validStatuses = ['PENDING', 'OPEN', 'CLOSED', 'CANCELLED'];

$where  = ['1=1'];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(g.gp_code LIKE ? OR g.to_party LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterType !== '' && in_array($filterType, $validTypes, true)) {
    $where[]  = 'g.gp_type = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
    $where[]  = 'g.gp_status = ?';
    $params[] = $filterStatus;
}
if ($filterDateFrom !== '') {
    $where[]  = 'DATE(g.created_at) >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[]  = 'DATE(g.created_at) <= ?';
    $params[] = $filterDateTo;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stCount = $db->prepare(
    "SELECT COUNT(*)
     FROM gatepasses g
     WHERE {$whereClause}"
);
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "SELECT g.*,
               (SELECT COUNT(*) FROM gatepass_items gi WHERE gi.gp_id = g.gp_id) AS item_count,
               u.full_name AS created_by_name
        FROM gatepasses g
        LEFT JOIN users u ON u.user_id = g.created_by
        WHERE {$whereClause}
        ORDER BY g.gp_id DESC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Gate Passes';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0"><i class="bi bi-truck me-1"></i>Gate Passes</h5>
        <span class="badge bg-secondary"><?= number_format($totalRows) ?></span>
    </div>
    <?php if (can('gatepass', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Gate Pass
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md">
        <div class="kpi-card">
            <div class="kpi-label">Total GPs</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="border-left-color:#0dcaf0;">
            <div class="kpi-label">Pending</div>
            <div class="kpi-value text-info"><?= number_format((int)$kpi['pending_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">Open</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['open_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">Closed</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['closed_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="kpi-card" style="border-left-color:#ef4444;">
            <div class="kpi-label">Cancelled</div>
            <div class="kpi-value text-danger"><?= number_format((int)$kpi['cancelled_count']) ?></div>
        </div>
    </div>
</div>

<!-- ── Filters ─────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Search GP Code / Party</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="GP code or party name…">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Type</label>
            <select name="gp_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <?php foreach ($validTypes as $t): ?>
                <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach ($validStatuses as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Created From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterDateFrom) ?>">
        </div>
        <div class="col-sm-1">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterDateTo) ?>">
        </div>
        <div class="col-sm-2 d-flex gap-1">
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
                    <th>GP Code</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>To/From Party</th>
                    <th>Purpose</th>
                    <th>Reference</th>
                    <th class="text-center">Items</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No gate passes found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $gp):
                $typeBadge = match($gp['gp_type']) {
                    'OUTGOING'             => 'primary',
                    'INCOMING'             => 'info',
                    'RETURN'               => 'secondary',
                    'TRANSFER'             => 'dark',
                    'STOCK_TRANSFER'       => 'dark',
                    'INTERNAL_CONSUMPTION' => 'secondary',
                    'RETURNABLE'           => 'warning',
                    default                => 'secondary',
                };
                $statusBadge = match($gp['gp_status']) {
                    'OPEN'      => 'warning',
                    'CLOSED'    => 'success',
                    'CANCELLED' => 'danger',
                    'CREATED'   => 'warning',
                    'PENDING'   => 'info text-dark',
                    default     => 'secondary',
                };
                $isOpen = in_array($gp['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true);
            ?>
            <tr>
                <td class="fw-semibold">
                    <a href="view.php?id=<?= (int)$gp['gp_id'] ?>" class="text-decoration-none">
                        <?= h($gp['gp_code']) ?>
                    </a>
                </td>
                <td><span class="badge bg-<?= $typeBadge ?>"><?= h($gp['gp_type']) ?></span></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($gp['gp_status']) ?></span></td>
                <td class="small"><?= $gp['to_party'] ? h($gp['to_party']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?php
                    $purpose = $gp['purpose'] ?? '';
                    echo $purpose
                        ? h(mb_strlen($purpose) > 45 ? mb_substr($purpose, 0, 45) . '…' : $purpose)
                        : '<span class="text-muted">—</span>';
                    ?>
                </td>
                <td class="small text-muted"><?= $gp['reference'] ? h($gp['reference']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border"><?= (int)$gp['item_count'] ?></span>
                </td>
                <td class="small text-muted" style="white-space:nowrap">
                    <?= h(substr($gp['created_at'] ?? '', 0, 10)) ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <a href="view.php?id=<?= (int)$gp['gp_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($isOpen && can('gatepass', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$gp['gp_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($gp['gp_status'] === 'PENDING' && can('gatepass', 'close')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Confirm GP <?= h(addslashes($gp['gp_code'])) ?> and move to OPEN?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="confirm_gatepass">
                        <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info me-1" title="Confirm">
                            <i class="bi bi-check"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (in_array($gp['gp_status'], ['OPEN', 'CREATED'], true) && can('gatepass', 'close')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Close GP <?= h(addslashes($gp['gp_code'])) ?> and dispatch items from stock?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="close_gatepass">
                        <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Close">
                            <i class="bi bi-check-circle"></i>
                        </button>
                    </form>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Cancel GP <?= h(addslashes($gp['gp_code'])) ?>? No stock will be moved.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="cancel_gatepass">
                        <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </form>
                    <?php elseif ($gp['gp_status'] === 'PENDING' && can('gatepass', 'close')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Cancel GP <?= h(addslashes($gp['gp_code'])) ?>? No stock will be moved.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="cancel_gatepass">
                        <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </form>
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
