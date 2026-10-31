<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('grn', 'view');

$db = getDB();

// ── KPI counts ─────────────────────────────────────────────────────────────
$stKpi = $db->query(
    "SELECT
       COUNT(*) AS total,
       SUM(status IN ('DRAFT','RECEIVED'))        AS pending,
       SUM(status = 'QC_IN_PROGRESS')             AS in_qc,
       SUM(status = 'COMPLETED')                  AS completed
     FROM grn"
);
$kpi = $stKpi->fetch();

// ── Filters ────────────────────────────────────────────────────────────────
$filterSearch   = trim($_GET['search']    ?? '');
$filterStatus   = $_GET['status']         ?? '';
$filterDateFrom = $_GET['date_from']      ?? '';
$filterDateTo   = $_GET['date_to']        ?? '';

$validStatuses = ['DRAFT', 'RECEIVED', 'QC_IN_PROGRESS', 'COMPLETED', 'REJECTED'];

$where  = ['1=1'];
$params = [];

if ($filterSearch !== '') {
    $where[]  = '(g.grn_code LIKE ? OR po.po_number LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterStatus !== '' && in_array($filterStatus, $validStatuses, true)) {
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
    "SELECT COUNT(*)
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
     WHERE {$whereClause}"
);
$stCount->execute($params);
$totalRows  = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "SELECT g.*,
               po.po_number,
               sc.subcon_name
        FROM grn g
        LEFT JOIN purchase_orders po ON po.po_id = g.po_id
        LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
        WHERE {$whereClause}
        ORDER BY g.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Goods Received Notes';
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
        <h5 class="mb-0"><i class="bi bi-box-arrow-in-down me-1"></i>Goods Received Notes</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php if (can('grn', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New GRN
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">Total GRNs</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">Pending</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['pending']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#3b82f6;">
            <div class="kpi-label">In QC</div>
            <div class="kpi-value text-primary"><?= number_format((int)$kpi['in_qc']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">Completed</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['completed']) ?></div>
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
                <?php foreach ($validStatuses as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
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
                    <th>Date</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Rejected</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No GRNs found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $grn):
                $statusBadge = match($grn['status']) {
                    'DRAFT'          => 'secondary',
                    'RECEIVED'       => 'primary',
                    'QC_IN_PROGRESS' => 'warning',
                    'COMPLETED'      => 'success',
                    'REJECTED'       => 'danger',
                    default          => 'secondary',
                };
                $isDraft = $grn['status'] === 'DRAFT';
            ?>
            <tr>
                <td class="fw-semibold">
                    <a href="view.php?id=<?= (int)$grn['grn_id'] ?>" class="text-decoration-none">
                        <?= h($grn['grn_code']) ?>
                    </a>
                </td>
                <td><?= $grn['po_number'] ? h($grn['po_number']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $grn['subcon_name'] ? h($grn['subcon_name']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $grn['grn_date'] ? h($grn['grn_date']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$grn['total_qty_received']) ?></td>
                <td class="text-end">
                    <?php if ((int)$grn['total_qty_rejected'] > 0): ?>
                    <span class="text-danger fw-semibold"><?= number_format((int)$grn['total_qty_rejected']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($grn['status']) ?></span></td>
                <td class="text-end" style="white-space:nowrap">
                    <a href="view.php?id=<?= (int)$grn['grn_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if ($isDraft && can('grn', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$grn['grn_id'] ?>"
                       class="btn btn-sm btn-outline-secondary" title="Edit">
                        <i class="bi bi-pencil"></i>
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
