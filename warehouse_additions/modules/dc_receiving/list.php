<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dc_receiving', 'view');

$db = getDB();

$today = date('Y-m-d');
$currentHour = (int) date('H');

// KPI counts for today
$kpiRow = $db->prepare(
    "SELECT
        COUNT(*) AS total_count,
        COALESCE(SUM(total_qty), 0) AS total_qty,
        SUM(dc_type = 'LOCAL') AS local_count,
        SUM(dc_type = 'EXPORT') AS export_count,
        SUM(dc_type = 'SMART_BUYS_DDF') AS ddf_count
     FROM dc_receivings
     WHERE receiving_date = ?"
);
$kpiRow->execute([$today]);
$kpi = $kpiRow->fetch();

// Cutoff warning: past 11AM and there are unprocessed completed GRNs
$pendingDc = 0;
if ($currentHour >= 11) {
    $pendingDc = (int) $db->query(
        "SELECT COUNT(*)
         FROM grn g
         LEFT JOIN dc_receivings dc ON dc.grn_id = g.grn_id
         WHERE g.status = 'COMPLETED' AND dc.dc_id IS NULL"
    )->fetchColumn();
}

// Filters
$filterType   = $_GET['dc_type']   ?? '';
$filterStatus = $_GET['status']    ?? '';
$filterFrom   = $_GET['date_from'] ?? '';
$filterTo     = $_GET['date_to']   ?? '';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterType !== '') {
    $where[]  = 'dc.dc_type = ?';
    $params[] = $filterType;
}
if ($filterStatus !== '') {
    $where[]  = 'dc.status = ?';
    $params[] = $filterStatus;
}
if ($filterFrom !== '') {
    $where[]  = 'dc.receiving_date >= ?';
    $params[] = $filterFrom;
}
if ($filterTo !== '') {
    $where[]  = 'dc.receiving_date <= ?';
    $params[] = $filterTo;
}
if ($search !== '') {
    $where[]  = '(g.grn_code LIKE ? OR po.po_number LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$whereStr = implode(' AND ', $where);

$countStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM dc_receivings dc
     LEFT JOIN grn g ON g.grn_id = dc.grn_id
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     WHERE $whereStr"
);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$dataParams   = $params;
$dataParams[] = $perPage;
$dataParams[] = $offset;

$rows = $db->prepare(
    "SELECT dc.dc_id, dc.dc_type, dc.receiving_date, dc.receiving_person,
            dc.total_qty, dc.defective_qty, dc.status,
            g.grn_id, g.grn_code,
            po.po_number, po.style_no
     FROM dc_receivings dc
     LEFT JOIN grn g ON g.grn_id = dc.grn_id
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     WHERE $whereStr
     ORDER BY dc.receiving_date DESC, dc.dc_id DESC
     LIMIT ? OFFSET ?"
);
$rows->execute($dataParams);
$records = $rows->fetchAll();

$flash = getFlash();

$pageTitle = 'DC Receiving';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($currentHour >= 11 && $pendingDc > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-clock-history fs-5"></i>
    <span>DC Receiving cutoff (11AM) has passed &mdash; <?= (int)$pendingDc ?> GRN(s) still pending DC receiving.</span>
</div>
<?php endif; ?>

<!-- KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="kpi-card h-100">
            <div class="kpi-label">Received Today</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total_count']) ?></div>
            <div class="small text-muted mt-1"><?= date('d M Y') ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="kpi-card h-100" style="border-left-color:<?= (int)$kpi['total_qty'] > 6000 ? '#dc3545' : '#198754' ?>;">
            <div class="kpi-label">Total Qty Today</div>
            <div class="kpi-value <?= (int)$kpi['total_qty'] > 6000 ? 'text-danger' : '' ?>">
                <?= number_format((int)$kpi['total_qty']) ?>
            </div>
            <div class="small text-muted mt-1">cutoff: 6,000 pcs</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="kpi-card h-100" style="border-left-color:#0d6efd;">
            <div class="kpi-label">LOCAL</div>
            <div class="kpi-value"><?= number_format((int)$kpi['local_count']) ?></div>
            <div class="small text-muted mt-1">today</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="kpi-card h-100" style="border-left-color:#198754;">
            <div class="kpi-label">EXPORT</div>
            <div class="kpi-value"><?= number_format((int)$kpi['export_count']) ?></div>
            <div class="small text-muted mt-1">today</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="kpi-card h-100" style="border-left-color:#dc3545;">
            <div class="kpi-label">SMART BUYS / DDF</div>
            <div class="kpi-value"><?= number_format((int)$kpi['ddf_count']) ?></div>
            <div class="small text-muted mt-1">today</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-2">
                <label class="form-label small mb-1">DC Type</label>
                <select name="dc_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="LOCAL" <?= $filterType==='LOCAL'?'selected':'' ?>>LOCAL</option>
                    <option value="EXPORT" <?= $filterType==='EXPORT'?'selected':'' ?>>EXPORT</option>
                    <option value="SMART_BUYS_DDF" <?= $filterType==='SMART_BUYS_DDF'?'selected':'' ?>>SMART BUYS / DDF</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="RECEIVED" <?= $filterStatus==='RECEIVED'?'selected':'' ?>>RECEIVED</option>
                    <option value="PUTAWAY_PENDING" <?= $filterStatus==='PUTAWAY_PENDING'?'selected':'' ?>>PUTAWAY PENDING</option>
                    <option value="PUTAWAY_COMPLETED" <?= $filterStatus==='PUTAWAY_COMPLETED'?'selected':'' ?>>PUTAWAY COMPLETED</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= h($filterFrom) ?>">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= h($filterTo) ?>">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="GRN or PO #" value="<?= h($search) ?>">
            </div>
            <div class="col-sm-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="<?= BASE_URL ?>/modules/dc_receiving/list.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">DC Receiving Records</span>
        <?php if (can('dc_receiving', 'create')): ?>
        <a href="<?= BASE_URL ?>/modules/dc_receiving/form.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>New DC Receipt
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!$records): ?>
        <p class="text-muted text-center py-4 mb-0">No DC receiving records found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>DC ID</th>
                        <th>GRN Code</th>
                        <th>PO Number</th>
                        <th>Style</th>
                        <th>Recv Date</th>
                        <th>Person</th>
                        <th>DC Type</th>
                        <th class="text-end">Total Qty</th>
                        <th class="text-end">Defective</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td class="text-muted small"><?= (int)$r['dc_id'] ?></td>
                    <td>
                        <?php if ($r['grn_id']): ?>
                        <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$r['grn_id'] ?>"
                           class="fw-semibold text-decoration-none">
                            <?= h($r['grn_code']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= h($r['po_number'] ?? '—') ?></td>
                    <td class="small"><?= h($r['style_no'] ?? '—') ?></td>
                    <td class="small text-muted"><?= $r['receiving_date'] ? date('d M Y', strtotime($r['receiving_date'])) : '—' ?></td>
                    <td class="small"><?= h($r['receiving_person'] ?? '—') ?></td>
                    <td>
                        <?php
                        $typeBadge = match ($r['dc_type']) {
                            'LOCAL'          => 'primary',
                            'EXPORT'         => 'success',
                            'SMART_BUYS_DDF' => 'danger',
                            default          => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $typeBadge ?>"><?= h($r['dc_type']) ?></span>
                    </td>
                    <td class="text-end small <?= (int)$r['total_qty'] > 6000 ? 'text-danger fw-bold' : '' ?>">
                        <?= number_format((int)$r['total_qty']) ?>
                    </td>
                    <td class="text-end small"><?= number_format((int)$r['defective_qty']) ?></td>
                    <td>
                        <?php
                        $statusBadge = match ($r['status']) {
                            'RECEIVED'           => 'warning',
                            'PUTAWAY_PENDING'     => 'info',
                            'PUTAWAY_COMPLETED'  => 'success',
                            default              => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $statusBadge ?> text-dark"><?= h($r['status']) ?></span>
                    </td>
                    <td class="text-nowrap">
                        <a href="<?= BASE_URL ?>/modules/dc_receiving/view.php?id=<?= (int)$r['dc_id'] ?>"
                           class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if ($r['status'] === 'RECEIVED' && can('dc_receiving', 'edit')): ?>
                        <a href="<?= BASE_URL ?>/modules/dc_receiving/form.php?id=<?= (int)$r['dc_id'] ?>"
                           class="btn btn-xs btn-outline-primary btn-sm py-0 px-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
        <span class="small text-muted">
            Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $totalRows)) ?> of <?= number_format($totalRows) ?>
        </span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
