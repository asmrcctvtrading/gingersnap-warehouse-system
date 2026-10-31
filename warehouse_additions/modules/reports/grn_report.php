<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('reports', 'view');

$db = getDB();

// ── Filters ───────────────────────────────────────────────────────────────────

$filterFrom   = trim($_GET['date_from'] ?? '');
$filterTo     = trim($_GET['date_to']   ?? '');
$filterSubcon = (int)($_GET['subcon_id'] ?? 0);
$filterStatus = trim($_GET['status']    ?? '');
$filterPo     = trim($_GET['po_number'] ?? '');
$isExport     = isset($_GET['export']) && $_GET['export'] === '1';

// Validate status against known values
$validStatuses = ['DRAFT', 'RECEIVED', 'QC_IN_PROGRESS', 'COMPLETED', 'REJECTED'];
if (!in_array($filterStatus, $validStatuses, true)) {
    $filterStatus = '';
}

// Subcon dropdown
$subcons = $db->query(
    "SELECT subcon_id, subcon_name FROM subcons ORDER BY subcon_name"
)->fetchAll();

// ── Build WHERE clause ────────────────────────────────────────────────────────

$wheres = ['1=1'];
$params = [];

if ($filterFrom !== '') {
    $wheres[] = 'g.grn_date >= ?';
    $params[] = $filterFrom;
}
if ($filterTo !== '') {
    $wheres[] = 'g.grn_date <= ?';
    $params[] = $filterTo;
}
if ($filterSubcon > 0) {
    $wheres[] = 'g.subcon_id = ?';
    $params[] = $filterSubcon;
}
if ($filterStatus !== '') {
    $wheres[] = 'g.status = ?';
    $params[] = $filterStatus;
}
if ($filterPo !== '') {
    $wheres[] = 'po.po_number LIKE ?';
    $params[] = '%' . $filterPo . '%';
}

$whereClause = implode(' AND ', $wheres);

// ── Main query ────────────────────────────────────────────────────────────────

$sql = "SELECT
            g.grn_id,
            g.grn_code,
            g.grn_date,
            g.status,
            g.total_qty_received,
            g.total_qty_rejected,
            po.po_number,
            sc.subcon_name,
            (SELECT
                CASE
                    WHEN COUNT(*) = 0 THEN 'PENDING'
                    WHEN SUM(CASE WHEN qi.status = 'PASSED'  THEN 1 ELSE 0 END) = COUNT(*) THEN 'PASSED'
                    WHEN SUM(CASE WHEN qi.status = 'FAILED'  THEN 1 ELSE 0 END) > 0        THEN 'FAILED'
                    WHEN SUM(CASE WHEN qi.status = 'PARTIAL' THEN 1 ELSE 0 END) > 0        THEN 'PARTIAL'
                    ELSE 'IN_PROGRESS'
                END
             FROM qc_inspection qi WHERE qi.grn_id = g.grn_id) AS qc_status
        FROM grn g
        LEFT JOIN purchase_orders po ON po.po_id     = g.po_id
        LEFT JOIN subcons         sc ON sc.subcon_id = g.subcon_id
        WHERE {$whereClause}
        ORDER BY g.grn_date DESC, g.grn_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── CSV Export ────────────────────────────────────────────────────────────────

if ($isExport) {
    if (!can('reports', 'export')) {
        http_response_code(403);
        exit('Access denied.');
    }
    $filename = 'grn_report_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'GRN Code', 'PO Number', 'Subcontractor', 'GRN Date',
        'Status', 'Qty Received', 'Qty Rejected', 'Rejection %', 'QC Status'
    ]);
    foreach ($rows as $r) {
        $rejPct = $r['total_qty_received'] > 0
            ? round($r['total_qty_rejected'] / $r['total_qty_received'] * 100, 1)
            : 0;
        fputcsv($out, [
            $r['grn_code'],
            $r['po_number']    ?? '',
            $r['subcon_name']  ?? '',
            $r['grn_date']     ?? '',
            $r['status'],
            $r['total_qty_received'],
            $r['total_qty_rejected'],
            $rejPct . '%',
            $r['qc_status'],
        ]);
    }
    fclose($out);
    exit;
}

// ── Summary KPIs ──────────────────────────────────────────────────────────────

$kpiTotal    = count($rows);
$kpiReceived = array_sum(array_column($rows, 'total_qty_received'));
$kpiRejected = array_sum(array_column($rows, 'total_qty_rejected'));
$kpiRejRate  = $kpiReceived > 0 ? round($kpiRejected / $kpiReceived * 100, 1) : 0;

// ── Export URL ────────────────────────────────────────────────────────────────

$exportParams = array_filter([
    'date_from'  => $filterFrom   ?: null,
    'date_to'    => $filterTo     ?: null,
    'subcon_id'  => $filterSubcon ?: null,
    'status'     => $filterStatus ?: null,
    'po_number'  => $filterPo     ?: null,
    'export'     => '1',
]);
$exportQs = http_build_query($exportParams);

$pageTitle = 'GRN Report';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-box-arrow-in-down me-2"></i>GRN Report</h5>
    <?php if (can('reports', 'export')): ?>
    <a href="grn_report.php?<?= $exportQs ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="get" action="grn_report.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Date From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterFrom) ?>">
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Date To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterTo) ?>">
        </div>
        <div class="col-sm-6 col-md-3">
            <label class="form-label small mb-1">Subcontractor</label>
            <select name="subcon_id" class="form-select form-select-sm">
                <option value="">All Subcontractors</option>
                <?php foreach ($subcons as $sc): ?>
                <option value="<?= (int)$sc['subcon_id'] ?>"
                    <?= $filterSubcon === (int)$sc['subcon_id'] ? 'selected' : '' ?>>
                    <?= h($sc['subcon_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach ($validStatuses as $s): ?>
                <option value="<?= h($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
                    <?= h($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-2">
            <label class="form-label small mb-1">PO Number</label>
            <input type="text" name="po_number" class="form-control form-control-sm"
                   placeholder="Search PO..." value="<?= h($filterPo) ?>">
        </div>
        <div class="col-sm-6 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search"></i>
            </button>
            <a href="grn_report.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<!-- Summary KPIs -->
<div class="row g-3 mb-3">
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Total GRNs</div>
            <div class="kpi-value"><?= number_format($kpiTotal) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card" style="border-left-color:#198754;">
            <div class="kpi-label">Total Received</div>
            <div class="kpi-value"><?= number_format($kpiReceived) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card" style="border-left-color:#dc3545;">
            <div class="kpi-label">Total Rejected</div>
            <div class="kpi-value"><?= number_format($kpiRejected) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card" style="border-left-color:<?= $kpiRejRate > 10 ? '#dc3545' : ($kpiRejRate > 5 ? '#ffc107' : '#198754') ?>;">
            <div class="kpi-label">Rejection Rate</div>
            <div class="kpi-value"><?= number_format($kpiRejRate, 1) ?>%</div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
        <span class="fw-semibold">Results</span>
        <span class="badge bg-secondary"><?= number_format($kpiTotal) ?> GRNs</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>GRN Code</th>
                    <th>PO Number</th>
                    <th>Subcontractor</th>
                    <th>GRN Date</th>
                    <th>Status</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Rejected</th>
                    <th class="text-end">Rej %</th>
                    <th>QC Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No GRNs found matching the current filters.
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($rows as $r):
                $rejPct = $r['total_qty_received'] > 0
                    ? round($r['total_qty_rejected'] / $r['total_qty_received'] * 100, 1)
                    : 0;
                $grnStatusColor = match ($r['status']) {
                    'DRAFT'          => 'secondary',
                    'RECEIVED'       => 'info',
                    'QC_IN_PROGRESS' => 'warning',
                    'COMPLETED'      => 'success',
                    'REJECTED'       => 'danger',
                    default          => 'secondary',
                };
                $qcStatusColor = match ($r['qc_status']) {
                    'PASSED'      => 'success',
                    'FAILED'      => 'danger',
                    'PARTIAL'     => 'warning',
                    'IN_PROGRESS' => 'info',
                    default       => 'secondary',
                };
            ?>
            <tr>
                <td>
                    <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$r['grn_id'] ?>"
                       class="fw-semibold text-decoration-none">
                        <?= h($r['grn_code']) ?>
                    </a>
                </td>
                <td class="small"><?= h($r['po_number'] ?? '—') ?></td>
                <td class="small"><?= h($r['subcon_name'] ?? '—') ?></td>
                <td class="small text-muted">
                    <?= $r['grn_date'] ? date('d M Y', strtotime($r['grn_date'])) : '—' ?>
                </td>
                <td>
                    <span class="badge bg-<?= $grnStatusColor ?>">
                        <?= h($r['status']) ?>
                    </span>
                </td>
                <td class="text-end fw-semibold"><?= number_format((int)$r['total_qty_received']) ?></td>
                <td class="text-end"><?= number_format((int)$r['total_qty_rejected']) ?></td>
                <td class="text-end <?= $rejPct > 10 ? 'text-danger fw-semibold' : ($rejPct > 5 ? 'text-warning' : '') ?>">
                    <?= number_format($rejPct, 1) ?>%
                </td>
                <td>
                    <span class="badge bg-<?= $qcStatusColor ?>">
                        <?= h($r['qc_status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
