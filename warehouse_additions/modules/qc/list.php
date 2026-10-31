<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('qc', 'view');

$db = getDB();

// ── KPI counts (from grn status, QC-scope rows only) ───────────────────────
$stKpi = $db->query(
    "SELECT
       SUM(status IN ('RECEIVED','QC_IN_PROGRESS','COMPLETED','REJECTED')) AS total,
       SUM(status = 'RECEIVED')       AS awaiting,
       SUM(status = 'QC_IN_PROGRESS') AS in_progress,
       SUM(status = 'COMPLETED')      AS passed,
       SUM(status = 'REJECTED')       AS failed,
       SUM(status = 'COMPLETED' AND (putaway_status IS NULL OR putaway_status != 'COMPLETED')) AS awaiting_putaway
     FROM grn
     WHERE status IN ('RECEIVED','QC_IN_PROGRESS','COMPLETED','REJECTED')"
);
$kpi = $stKpi->fetch();

// ── Filters ────────────────────────────────────────────────────────────────
$filterSearch   = trim($_GET['search']    ?? '');
$filterStatus   = $_GET['status']         ?? '';
$filterDateFrom = $_GET['date_from']      ?? '';
$filterDateTo   = $_GET['date_to']        ?? '';

$validStatuses = [
    'RECEIVED'       => 'Awaiting QC',
    'QC_IN_PROGRESS' => 'In Progress',
    'COMPLETED'      => 'Passed',
    'REJECTED'       => 'Failed',
];

$where  = ["g.status IN ('RECEIVED','QC_IN_PROGRESS','COMPLETED','REJECTED')"];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(g.grn_code LIKE ? OR po.po_number LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterStatus !== '' && array_key_exists($filterStatus, $validStatuses)) {
    $where[]  = 'g.status = ?';
    $params[] = $filterStatus;
}
if ($filterDateFrom !== '') {
    $where[]  = 'g.grn_date >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[]  = 'g.grn_date <= ?';
    $params[] = $filterDateTo;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$stCount = $db->prepare(
    "SELECT COUNT(DISTINCT g.grn_id)
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
     WHERE {$whereClause}"
);
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ── Main query — GRNs with aggregated QC inspection totals ─────────────────
$sql = "SELECT
          g.grn_id,
          g.grn_code,
          g.grn_date,
          g.status,
          g.putaway_status,
          g.total_qty_received,
          g.created_at,
          po.po_number,
          sc.subcon_name,
          COUNT(qi.grn_item_id)           AS inspected_items,
          COALESCE(SUM(qi.inspected_qty),0) AS total_inspected,
          COALESCE(SUM(qi.passed_qty),0)    AS total_passed,
          COALESCE(SUM(qi.failed_qty),0)    AS total_failed,
          MAX(qi.inspection_date)           AS last_inspection_date
        FROM grn g
        LEFT JOIN purchase_orders po ON po.po_id = g.po_id
        LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
        LEFT JOIN qc_inspection qi ON qi.grn_id = g.grn_id
        WHERE {$whereClause}
        GROUP BY g.grn_id, g.grn_code, g.grn_date, g.status,
                 g.putaway_status, g.total_qty_received, g.created_at,
                 po.po_number, sc.subcon_name
        ORDER BY g.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'QC Inspections';
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
        <h5 class="mb-0"><i class="bi bi-clipboard-check me-1"></i>QC Inspections</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <div class="kpi-card">
            <div class="kpi-label">Total GRNs</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card" style="border-left-color:#6b7280;">
            <div class="kpi-label">Awaiting QC</div>
            <div class="kpi-value text-secondary"><?= number_format((int)$kpi['awaiting']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">In Progress</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['in_progress']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">Passed</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['passed']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card" style="border-left-color:#ef4444;">
            <div class="kpi-label">Failed</div>
            <div class="kpi-value text-danger"><?= number_format((int)$kpi['failed']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card" style="border-left-color:#06b6d4;">
            <div class="kpi-label">Awaiting Putaway</div>
            <div class="kpi-value" style="color:#06b6d4"><?= number_format((int)$kpi['awaiting_putaway']) ?></div>
        </div>
    </div>
</div>

<!-- ── Filters ─────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="GRN code or PO number…">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach ($validStatuses as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filterStatus === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">GRN Date From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterDateFrom) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">GRN Date To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterDateTo) ?>">
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
                    <th>GRN Code</th>
                    <th>PO Number</th>
                    <th>Subcon</th>
                    <th>GRN Date</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Inspected</th>
                    <th class="text-end">Passed</th>
                    <th class="text-end">Failed</th>
                    <th>QC Status</th>
                    <th>Putaway</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="11" class="text-center text-muted py-4">No GRNs found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $row):
                $statusBadge = match($row['status']) {
                    'RECEIVED'       => ['Awaiting QC', 'secondary'],
                    'QC_IN_PROGRESS' => ['In Progress',  'warning'],
                    'COMPLETED'      => ['Passed',        'success'],
                    'REJECTED'       => ['Failed',        'danger'],
                    default          => [h($row['status']), 'secondary'],
                };
                $putawayBadge = match($row['putaway_status'] ?? 'PENDING') {
                    'IN_PROGRESS' => ['In Progress', 'warning'],
                    'COMPLETED'   => ['Completed',   'success'],
                    default       => ['Pending',     'secondary'],
                };
                $canInspect = in_array($row['status'], ['RECEIVED','QC_IN_PROGRESS'], true)
                              && can('qc', 'inspect');
            ?>
            <tr>
                <td class="fw-semibold">
                    <a href="view.php?grn_id=<?= (int)$row['grn_id'] ?>" class="text-decoration-none">
                        <?= h($row['grn_code']) ?>
                    </a>
                </td>
                <td><?= $row['po_number'] ? h($row['po_number']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $row['subcon_name'] ? h($row['subcon_name']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $row['grn_date'] ? h($row['grn_date']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$row['total_qty_received']) ?></td>
                <td class="text-end"><?= number_format((int)$row['total_inspected']) ?></td>
                <td class="text-end text-success fw-semibold"><?= number_format((int)$row['total_passed']) ?></td>
                <td class="text-end <?= (int)$row['total_failed'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                    <?= number_format((int)$row['total_failed']) ?>
                </td>
                <td>
                    <span class="badge bg-<?= $statusBadge[1] ?>"><?= $statusBadge[0] ?></span>
                </td>
                <td>
                    <?php if ($row['status'] === 'COMPLETED'): ?>
                    <span class="badge bg-<?= $putawayBadge[1] ?> small"><?= $putawayBadge[0] ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <a href="view.php?grn_id=<?= (int)$row['grn_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="View QC">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($canInspect): ?>
                    <a href="form.php?grn_id=<?= (int)$row['grn_id'] ?>"
                       class="btn btn-sm btn-outline-primary" title="Inspect">
                        <i class="bi bi-clipboard-check"></i>
                    </a>
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
