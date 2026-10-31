<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();

// ── Filters ────────────────────────────────────────────────────────────────
$filterStatus   = $_GET['status']     ?? '';         // '' = NEW+WIP default, specific = single status
$filterSubcon   = (int)($_GET['subcon_id'] ?? 0);
$filterAgeBucket = $_GET['age_bucket'] ?? 'all';     // all / lt30 / 30to60 / 60to90 / gt90
$isExport       = isset($_GET['export']) && $_GET['export'] === 'csv';

$validStatuses    = ['', 'NEW', 'WIP', 'COMPLETED', 'CANCELLED'];
$validAgeBuckets  = ['all', 'lt30', '30to60', '60to90', 'gt90'];

if (!in_array($filterStatus, $validStatuses, true))       $filterStatus    = '';
if (!in_array($filterAgeBucket, $validAgeBuckets, true))  $filterAgeBucket = 'all';

// ── Subcon list for filter dropdown ───────────────────────────────────────
$subcons = $db->query(
    "SELECT subcon_id, subcon_name FROM subcons ORDER BY subcon_name"
)->fetchAll();

// ── Build WHERE ────────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($filterStatus === '') {
    // Default: open POs only
    $where[]  = "po.status IN ('NEW','WIP')";
} else {
    $where[]  = 'po.status = ?';
    $params[] = $filterStatus;
}

if ($filterSubcon) {
    $where[]  = 'po.subcon_id = ?';
    $params[] = $filterSubcon;
}

if ($filterAgeBucket !== 'all') {
    switch ($filterAgeBucket) {
        case 'lt30':
            $where[]  = 'DATEDIFF(CURDATE(), po.po_date) < 30';
            break;
        case '30to60':
            $where[]  = 'DATEDIFF(CURDATE(), po.po_date) BETWEEN 30 AND 60';
            break;
        case '60to90':
            $where[]  = 'DATEDIFF(CURDATE(), po.po_date) BETWEEN 61 AND 90';
            break;
        case 'gt90':
            $where[]  = 'DATEDIFF(CURDATE(), po.po_date) > 90';
            break;
    }
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT po.po_id, po.po_number, po.style_no, sc.subcon_name,
               po.po_qty, po.po_date, po.delivery_date, po.status, po.approval_status,
               DATEDIFF(CURDATE(), po.po_date)      AS age_days,
               DATEDIFF(po.delivery_date, CURDATE()) AS days_until_due,
               CASE WHEN po.delivery_date < CURDATE() AND po.status IN ('NEW','WIP')
                         THEN 'OVERDUE'
                    WHEN po.delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                         AND po.status IN ('NEW','WIP')
                         THEN 'DUE_SOON'
                    ELSE 'OK' END AS urgency
        FROM purchase_orders po
        LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
        WHERE {$whereClause}
        ORDER BY
            FIELD(CASE WHEN po.delivery_date < CURDATE() AND po.status IN ('NEW','WIP') THEN 'OVERDUE'
                       WHEN po.delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND po.status IN ('NEW','WIP') THEN 'DUE_SOON'
                       ELSE 'OK' END, 'OVERDUE','DUE_SOON','OK'),
            po.delivery_date ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── CSV export ─────────────────────────────────────────────────────────────
if ($isExport) {
    $filename = 'po_aging_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['PO Number', 'Style No', 'Subcontractor', 'Qty', 'PO Date', 'Delivery Date', 'Age (Days)', 'Days Until Due', 'Status', 'Approval', 'Urgency']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['po_number'],
            $r['style_no'],
            $r['subcon_name'] ?? '',
            $r['po_qty'],
            $r['po_date'],
            $r['delivery_date'],
            $r['age_days'],
            $r['days_until_due'],
            $r['status'],
            $r['approval_status'],
            $r['urgency'],
        ]);
    }
    fclose($out);
    exit;
}

// ── Summary card values ────────────────────────────────────────────────────
$totalOpen   = count($rows);
$overdueCount = 0;
$dueSoonCount = 0;
$totalAgeDays = 0;

foreach ($rows as $r) {
    if ($r['urgency'] === 'OVERDUE')  $overdueCount++;
    if ($r['urgency'] === 'DUE_SOON') $dueSoonCount++;
    $totalAgeDays += (int)$r['age_days'];
}

$avgAge = $totalOpen > 0 ? round($totalAgeDays / $totalOpen) : 0;

// ── Export URL ─────────────────────────────────────────────────────────────
$exportParams = array_filter([
    'status'     => $filterStatus !== '' ? $filterStatus : null,
    'subcon_id'  => $filterSubcon ?: null,
    'age_bucket' => $filterAgeBucket !== 'all' ? $filterAgeBucket : null,
    'export'     => 'csv',
]);
$exportQs = http_build_query($exportParams);
// Ensure export=csv is always in the URL
if (strpos($exportQs, 'export') === false) {
    $exportQs .= ($exportQs ? '&' : '') . 'export=csv';
}

$pageTitle = 'PO Aging';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">PO Aging</h5>
        <span class="badge bg-secondary"><?= count($rows) ?> POs</span>
    </div>
    <a href="po_aging.php?<?= $exportQs ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Total Matching POs</div>
            <div class="kpi-value"><?= number_format($totalOpen) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card" style="border-left-color:#dc3545">
            <div class="kpi-label">Overdue</div>
            <div class="kpi-value text-danger"><?= number_format($overdueCount) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card" style="border-left-color:#ffc107">
            <div class="kpi-label">Due This Week</div>
            <div class="kpi-value text-warning"><?= number_format($dueSoonCount) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Avg Age (Days)</div>
            <div class="kpi-value"><?= number_format($avgAge) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" action="po_aging.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="" <?= $filterStatus === '' ? 'selected' : '' ?>>Open (NEW + WIP)</option>
                <?php foreach (['NEW', 'WIP', 'COMPLETED', 'CANCELLED'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Subcontractor</label>
            <select name="subcon_id" class="form-select form-select-sm">
                <option value="">All Subcons</option>
                <?php foreach ($subcons as $sc): ?>
                <option value="<?= (int)$sc['subcon_id'] ?>"
                    <?= $filterSubcon === (int)$sc['subcon_id'] ? 'selected' : '' ?>>
                    <?= h($sc['subcon_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Age Bucket</label>
            <select name="age_bucket" class="form-select form-select-sm">
                <option value="all"    <?= $filterAgeBucket === 'all'    ? 'selected' : '' ?>>All Ages</option>
                <option value="lt30"   <?= $filterAgeBucket === 'lt30'   ? 'selected' : '' ?>>Less than 30 days</option>
                <option value="30to60" <?= $filterAgeBucket === '30to60' ? 'selected' : '' ?>>30 &ndash; 60 days</option>
                <option value="60to90" <?= $filterAgeBucket === '60to90' ? 'selected' : '' ?>>61 &ndash; 90 days</option>
                <option value="gt90"   <?= $filterAgeBucket === 'gt90'   ? 'selected' : '' ?>>Over 90 days</option>
            </select>
        </div>
        <div class="col-sm-3 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="po_aging.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Style</th>
                    <th>Subcontractor</th>
                    <th class="text-end">Qty</th>
                    <th>PO Date</th>
                    <th>Delivery Date</th>
                    <th class="text-end">Age (d)</th>
                    <th class="text-end">Until Due (d)</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th>Urgency</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="11" class="text-center text-muted py-4">No purchase orders match current filters.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($rows as $po):
                $rowClass = match($po['urgency']) {
                    'OVERDUE'  => 'table-danger',
                    'DUE_SOON' => 'table-warning',
                    default    => '',
                };
                $urgencyBadge = match($po['urgency']) {
                    'OVERDUE'  => 'danger',
                    'DUE_SOON' => 'warning',
                    default    => 'success',
                };
                $statusBadge = match($po['status']) {
                    'NEW'       => 'secondary',
                    'WIP'       => 'warning',
                    'COMPLETED' => 'success',
                    'CANCELLED' => 'danger',
                    default     => 'secondary',
                };
                $approvalBadge = $po['approval_status'] === 'approved' ? 'success' : 'warning';
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="fw-semibold">
                    <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= (int)$po['po_id'] ?>"
                       class="text-decoration-none">
                        <?= h($po['po_number']) ?>
                    </a>
                </td>
                <td><?= h($po['style_no'] ?? '—') ?></td>
                <td><?= $po['subcon_name'] ? h($po['subcon_name']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$po['po_qty']) ?></td>
                <td><?= $po['po_date'] ? h($po['po_date']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $po['delivery_date'] ? h($po['delivery_date']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= $po['age_days'] !== null ? number_format((int)$po['age_days']) : '—' ?></td>
                <td class="text-end">
                    <?php if ($po['days_until_due'] !== null): ?>
                    <span class="<?= (int)$po['days_until_due'] < 0 ? 'text-danger fw-semibold' : '' ?>">
                        <?= number_format((int)$po['days_until_due']) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span></td>
                <td><span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status']) ?></span></td>
                <td><span class="badge bg-<?= $urgencyBadge ?>"><?= h($po['urgency']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
