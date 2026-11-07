<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dashboard', 'view');

$db = getDB();

// ── Date range filter ────────────────────────────────────────────────────────
$from = getDateParam('from', date('Y-m-01'));  // First day of current month
$to = getDateParam('to', date('Y-m-d'));
$fromDT = $from . ' 00:00:00';
$toDT = $to . ' 23:59:59';

// ── Row 1: KPI Cards ─────────────────────────────────────────────────────────

$kpiOpenPos = (int) $db->query(
    "SELECT COUNT(*) FROM purchase_orders WHERE status IN ('NEW','WIP')"
)->fetchColumn();

$kpiGrnsMonth = (int) $db->query(
    "SELECT COUNT(*) FROM grn
     WHERE MONTH(grn_date) = MONTH(CURDATE())
       AND YEAR(grn_date)  = YEAR(CURDATE())"
)->fetchColumn();

$kpiCartons = (int) $db->query(
    "SELECT COALESCE(SUM(qty_cartons), 0) FROM stock_positions"
)->fetchColumn();

$kpiOpenGp = (int) $db->query(
    "SELECT COUNT(*) FROM gatepasses WHERE gp_status = 'OPEN'"
)->fetchColumn();

$qcRow = $db->query(
    "SELECT COALESCE(SUM(passed_qty), 0)    AS total_passed,
            COALESCE(SUM(inspected_qty), 0) AS total_inspected
     FROM qc_inspection
     WHERE MONTH(inspection_date) = MONTH(CURDATE())
       AND YEAR(inspection_date)  = YEAR(CURDATE())"
)->fetch();
$kpiQcPassRate = ($qcRow['total_inspected'] > 0)
    ? round($qcRow['total_passed'] / $qcRow['total_inspected'] * 100, 1)
    : null;

$kpiSubcons = (int) $db->query(
    "SELECT COUNT(*) FROM subcons"
)->fetchColumn();

// ── Aged PO Alerts (> 60 days open) ──────────────────────────────────────────
$agedSubcon = $_GET['aged_subcon'] ?? '';
$agedPos = [];
try {
    $agedWhere = "po.status IN ('NEW','WIP') AND DATEDIFF(CURDATE(), po.po_date) > 60";
    $agedParams = [];
    if ($agedSubcon !== '') {
        $agedWhere .= " AND po.subcon_id = ?";
        $agedParams[] = (int)$agedSubcon;
    }
    $agedStmt = $db->prepare(
        "SELECT po.*, sc.subcon_name, DATEDIFF(CURDATE(), po.po_date) AS age_days
         FROM purchase_orders po
         LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
         WHERE $agedWhere
         ORDER BY age_days DESC
         LIMIT 10"
    );
    $agedStmt->execute($agedParams);
    $agedPos = $agedStmt->fetchAll();
} catch (Exception $e) {
    $agedPos = [];
}
$allSubcons = $db->query("SELECT subcon_id, subcon_name FROM subcons ORDER BY subcon_name")->fetchAll();

// RSS Shortage Pending: GRNs with shortage_qty > 0 (balance still to arrive from subcon)
try {
    $kpiRssPending = (int) $db->query(
        "SELECT COUNT(*) FROM grn WHERE discrepancy_type = 'SHORTAGE' AND shortage_qty > 0"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiRssPending = 0;
}

try {
    $kpiSmartBuysPending = (int) $db->query(
        "SELECT COUNT(*) FROM smart_buys WHERE status = 'PENDING'"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiSmartBuysPending = 0;
}

try {
    $kpiDcPending = (int) $db->query(
        "SELECT COUNT(*) FROM grn g
         LEFT JOIN dc_receivings dc ON dc.grn_id = g.grn_id
         WHERE g.status = 'COMPLETED' AND dc.dc_id IS NULL"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiDcPending = 0;
}

try {
    $kpiDcPutawayPending = (int) $db->query(
        "SELECT COUNT(*) FROM dc_putaways WHERE status = 'PENDING'"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiDcPutawayPending = 0;
}

// ── Row 1b KPIs ──────────────────────────────────────────────────────────────
// Pending POs (NEW/WIP status)
$kpiPendingPos = (int) $db->query(
    "SELECT COUNT(*) FROM purchase_orders WHERE status IN ('NEW','WIP')"
)->fetchColumn();

// Pending GRN (items received but no GRN created)
$kpiPendingGrn = (int) $db->query(
    "SELECT COUNT(DISTINCT po_id) FROM receiving
     WHERE grn_id IS NULL
     AND received_date IS NOT NULL"
)->fetchColumn();

// Pending QC (GRNs received but not yet inspected)
$kpiPendingQc = (int) $db->query(
    "SELECT COUNT(*) FROM grn WHERE status = 'RECEIVED'"
)->fetchColumn();

// Pending Putaway (QC completed but not yet put away)
$kpiPendingPutaway = (int) $db->query(
    "SELECT COUNT(DISTINCT grn_id) FROM grn
     WHERE status = 'COMPLETED'
     AND grn_id NOT IN (
         SELECT DISTINCT grn_id FROM stock_movements
         WHERE movement_type = 'PUTAWAY' AND grn_id IS NOT NULL
     )"
)->fetchColumn();

// RSS items (for RSS/Other-Process status)
try {
    $kpiRssOutPending = (int) $db->query(
        "SELECT COALESCE(SUM(qty_out), 0) FROM rss WHERE status IN ('PENDING','APPROVED')"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiRssOutPending = 0;
}

try {
    $kpiRssInPending = (int) $db->query(
        "SELECT COALESCE(SUM(qty_in), 0) FROM rss WHERE status IN ('PENDING','APPROVED')"
    )->fetchColumn();
} catch (Exception $e) {
    $kpiRssInPending = 0;
}

// Activity Log - last 10 activities
$recentActivities = $db->query(
    "SELECT al.*, u.full_name, u.username
     FROM activity_log al
     LEFT JOIN users u ON u.user_id = al.user_id
     ORDER BY al.created_at DESC
     LIMIT 10"
)->fetchAll();

// WIP pipeline counts
$wipAwaitingQc = (int) $db->query(
    "SELECT COUNT(*) FROM grn WHERE status = 'RECEIVED'"
)->fetchColumn();

$wipAwaitingPutaway = (int) $db->query(
    "SELECT COUNT(DISTINCT g.grn_id)
     FROM grn g
     WHERE g.status = 'COMPLETED'
       AND g.po_id NOT IN (
           SELECT DISTINCT sm.po_id
           FROM stock_movements sm
           WHERE sm.movement_type = 'PUTAWAY'
             AND sm.po_id IS NOT NULL
       )"
)->fetchColumn();

// ── Row 2 Left: Recent GRNs (last 10) ────────────────────────────────────────

$recentGrns = $db->query(
    "SELECT g.grn_id, g.grn_code, g.grn_date, g.status,
            po.po_number,
            sc.subcon_name
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     LEFT JOIN subcons sc          ON sc.subcon_id = g.subcon_id
     ORDER BY g.grn_date DESC, g.grn_id DESC
     LIMIT 10"
)->fetchAll();

// ── Row 2 Right: GRN Status Breakdown ────────────────────────────────────────

$grnStatusRows = $db->query(
    "SELECT status, COUNT(*) AS cnt
     FROM grn
     GROUP BY status
     ORDER BY FIELD(status,'DRAFT','RECEIVED','QC_IN_PROGRESS','COMPLETED','REJECTED')"
)->fetchAll();

$grnTotal = array_sum(array_column($grnStatusRows, 'cnt'));

// ── Row 3 Left: Recent Stock Movements (last 10) ──────────────────────────────

$recentMovements = $db->query(
    "SELECT sm.movement_id, sm.movement_type, sm.qty_cartons, sm.created_at,
            po.po_number,
            tl.location_code AS to_code,
            i.sku_code
     FROM stock_movements sm
     LEFT JOIN purchase_orders     po ON po.po_id       = sm.po_id
     LEFT JOIN warehouse_locations tl ON tl.location_id = sm.to_location_id
     LEFT JOIN items               i  ON i.item_id      = sm.item_id
     ORDER BY sm.created_at DESC
     LIMIT 10"
)->fetchAll();

// ── Row 3 Right: Open Gate Passes (max 10) ───────────────────────────────────

$openGatepasses = $db->query(
    "SELECT gp.gp_id, gp.gp_code, gp.gp_type, gp.to_party,
            gp.gp_status, gp.created_at,
            u.full_name AS created_by_name
     FROM gatepasses gp
     LEFT JOIN users u ON u.user_id = gp.created_by
     WHERE gp.gp_status = 'OPEN'
     ORDER BY gp.created_at DESC
     LIMIT 10"
)->fetchAll();

// ── Row 7: WIP Pipeline counts ───────────────────────────────────────────────

// POs with no GRN yet
$wipPendingReceipt = (int) $db->query(
    "SELECT COUNT(*)
     FROM purchase_orders po
     WHERE po.status IN ('NEW','WIP')
       AND po.po_id NOT IN (
           SELECT DISTINCT po_id FROM grn WHERE po_id IS NOT NULL
       )"
)->fetchColumn();

$wipInQc = (int) $db->query(
    "SELECT COUNT(*) FROM grn WHERE status = 'QC_IN_PROGRESS'"
)->fetchColumn();

$wipOpenGp = $kpiOpenGp; // reuse the value from KPI row

// ── Chart Data: Destination Allocations ──────────────────────────────────────
$destTotals = [
    'Local' => 0,
    'SG' => 0,
    'Malaysia' => 0,
    'Indo' => 0,
    'SM Baliwag' => 0,
    'Export Full' => 0
];
try {
    $destRow = $db->query(
        "SELECT
            SUM(qty_local) AS local,
            SUM(qty_sg) AS sg,
            SUM(qty_malaysia) AS malaysia,
            SUM(qty_indo) AS indo,
            SUM(qty_sm_baliwag) AS sm_baliwag,
            SUM(qty_export_full) AS export_full
         FROM po_destination_allocations"
    )->fetch();
    if ($destRow) {
        $destTotals['Local'] = (int)($destRow['local'] ?? 0);
        $destTotals['SG'] = (int)($destRow['sg'] ?? 0);
        $destTotals['Malaysia'] = (int)($destRow['malaysia'] ?? 0);
        $destTotals['Indo'] = (int)($destRow['indo'] ?? 0);
        $destTotals['SM Baliwag'] = (int)($destRow['sm_baliwag'] ?? 0);
        $destTotals['Export Full'] = (int)($destRow['export_full'] ?? 0);
    }
} catch (Exception $e) {}

// ── Chart Data: PO Status Breakdown ──────────────────────────────────────────
$poStatusCounts = ['NEW' => 0, 'WIP' => 0, 'COMPLETED' => 0, 'CANCELLED' => 0];
try {
    $statusRows = $db->query(
        "SELECT status, COUNT(*) as cnt FROM purchase_orders GROUP BY status"
    )->fetchAll();
    foreach ($statusRows as $sr) {
        if (isset($poStatusCounts[$sr['status']])) {
            $poStatusCounts[$sr['status']] = (int)$sr['cnt'];
        }
    }
} catch (Exception $e) {}

// ── Helpers ───────────────────────────────────────────────────────────────────

function grnStatusBadge(string $status): string {
    return match ($status) {
        'DRAFT'          => 'secondary',
        'RECEIVED'       => 'info',
        'QC_IN_PROGRESS' => 'warning',
        'COMPLETED'      => 'success',
        'REJECTED'       => 'danger',
        default          => 'secondary',
    };
}

function movementTypeBadge(string $type): string {
    return match ($type) {
        'PUTAWAY'    => 'success',
        'RELOCATION' => 'info',
        'PICK'       => 'warning',
        'DISPATCH'   => 'danger',
        'ADJUST'     => 'secondary',
        default      => 'secondary',
    };
}

$pageTitle = 'Dashboard';
include __DIR__ . '/../../includes/header.php';
?>

<!-- ── Date Filter ────────────────────────────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="get" class="d-flex flex-wrap gap-3 align-items-center">
                    <div>
                        <label class="form-label fw-semibold mb-1 small">From:</label>
                        <input type="date" name="from" class="form-control form-control-sm" value="<?= h($from) ?>">
                    </div>
                    <div>
                        <label class="form-label fw-semibold mb-1 small">To:</label>
                        <input type="date" name="to" class="form-control form-control-sm" value="<?= h($to) ?>">
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <?php if ($from !== date('Y-m-01') || $to !== date('Y-m-d')): ?>
                    <a href="?" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg me-1"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Automated PO Aging Alerts (> 60 Days Open) ─────────────────────────── -->
<?php if (!empty($agedPos) || $agedSubcon !== ''): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm border-start border-danger border-4">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center gap-2">
                <h6 class="mb-0 text-danger fw-bold me-auto"><i class="bi bi-exclamation-triangle-fill me-2"></i>Automated PO Aging Alerts (Open > 60 Days)</h6>
                <form method="get" class="d-flex align-items-center gap-2 mb-0">
                    <?php foreach ($_GET as $k => $v): if ($k === 'aged_subcon') continue; ?>
                    <input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>">
                    <?php endforeach; ?>
                    <select name="aged_subcon" class="form-select form-select-sm" style="min-width:160px;" onchange="this.form.submit()">
                        <option value="">All Subcons</option>
                        <?php foreach ($allSubcons as $sc): ?>
                        <option value="<?= (int)$sc['subcon_id'] ?>" <?= $agedSubcon == $sc['subcon_id'] ? 'selected' : '' ?>>
                            <?= h($sc['subcon_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($agedSubcon !== ''): ?>
                    <a href="?<?= http_build_query(array_diff_key($_GET, ['aged_subcon' => ''])) ?>" class="btn btn-sm btn-outline-secondary">×</a>
                    <?php endif; ?>
                </form>
                <span class="badge bg-danger rounded-pill"><?= count($agedPos) ?> Aged Orders</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>PO Number</th>
                            <th>PO Date</th>
                            <th>Subcon</th>
                            <th class="text-end">PO Qty</th>
                            <th class="text-end">Age</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agedPos as $apo): ?>
                        <tr>
                            <td class="fw-semibold">
                                <a href="../purchase_orders/view.php?id=<?= (int)$apo['po_id'] ?>" class="text-decoration-none">
                                    <?= h($apo['po_number']) ?>
                                </a>
                            </td>
                            <td><?= h($apo['po_date']) ?></td>
                            <td><?= h($apo['subcon_name'] ?? '—') ?></td>
                            <td class="text-end"><?= number_format((int)$apo['po_qty']) ?></td>
                            <td class="text-end">
                                <span class="badge bg-danger"><?= (int)$apo['age_days'] ?> days</span>
                            </td>
                            <td class="text-end">
                                <a href="../purchase_orders/view.php?id=<?= (int)$apo['po_id'] ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Row 1: KPI Cards ──────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100">
            <div class="kpi-label">Open POs</div>
            <div class="kpi-value"><?= number_format($kpiOpenPos) ?></div>
            <div class="small text-muted mt-1">NEW &amp; WIP</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#0dcaf0;">
            <div class="kpi-label">GRNs This Month</div>
            <div class="kpi-value"><?= number_format($kpiGrnsMonth) ?></div>
            <div class="small text-muted mt-1"><?= date('F Y') ?></div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#198754;">
            <div class="kpi-label">Cartons In Stock</div>
            <div class="kpi-value"><?= number_format($kpiCartons) ?></div>
            <div class="small text-muted mt-1">stock positions</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#ffc107;">
            <div class="kpi-label">Open Gate Passes</div>
            <div class="kpi-value"><?= number_format($kpiOpenGp) ?></div>
            <div class="small text-muted mt-1">awaiting close</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:<?= $kpiQcPassRate === null ? '#6c757d' : ($kpiQcPassRate >= 90 ? '#198754' : ($kpiQcPassRate >= 70 ? '#ffc107' : '#dc3545')) ?>;">
            <div class="kpi-label">QC Pass Rate</div>
            <div class="kpi-value">
                <?= $kpiQcPassRate !== null ? number_format($kpiQcPassRate, 1) . '%' : '<span class="fs-5 text-muted">N/A</span>' ?>
            </div>
            <div class="small text-muted mt-1">this month</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#6f42c1;">
            <div class="kpi-label">Total Subcontractors</div>
            <div class="kpi-value"><?= number_format($kpiSubcons) ?></div>
            <div class="small text-muted mt-1">registered</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#0891b2;">
            <div class="kpi-label">DC Pending</div>
            <div class="kpi-value <?= $kpiDcPending > 0 ? 'text-danger' : '' ?>"><?= number_format($kpiDcPending) ?></div>
            <div class="small text-muted mt-1">QC-done, awaiting DC recv</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-2">
        <div class="kpi-card h-100" style="border-left-color:#7c3aed;">
            <div class="kpi-label">Putaway Pending</div>
            <div class="kpi-value <?= $kpiDcPutawayPending > 0 ? 'text-danger' : '' ?>"><?= number_format($kpiDcPutawayPending) ?></div>
            <div class="small text-muted mt-1">DC putaways not confirmed</div>
        </div>
    </div>

</div>

<!-- ── Row 1b: Operational KPIs ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-md-4">
        <div class="kpi-card h-100" style="border-left-color:#fd7e14;">
            <div class="kpi-label">RSS Shortage Pending</div>
            <div class="kpi-value"><?= number_format($kpiRssPending) ?></div>
            <div class="small text-muted mt-1">GRNs with balance qty</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-4">
        <div class="kpi-card h-100" style="border-left-color:#0dcaf0;">
            <div class="kpi-label">Awaiting QC</div>
            <div class="kpi-value"><?= number_format($wipAwaitingQc) ?></div>
            <div class="small text-muted mt-1">GRNs received, QC not started</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#198754;">
            <div class="kpi-label">Awaiting Putaway</div>
            <div class="kpi-value"><?= number_format($wipAwaitingPutaway) ?></div>
            <div class="small text-muted mt-1">QC done, not yet put away</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#dc2626;">
            <div class="kpi-label">SMART Buys Pending</div>
            <div class="kpi-value"><?= number_format($kpiSmartBuysPending) ?></div>
            <div class="small text-muted mt-1">defective items to list/dispose</div>
        </div>
    </div>

</div>

<!-- ── Row 2: Recent GRNs + GRN Status Breakdown ─────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Recent GRNs -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-in-down text-muted"></i>
                Recent GRNs
                <a href="<?= BASE_URL ?>/modules/grn/list.php" class="btn btn-sm btn-outline-secondary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentGrns): ?>
                <p class="text-muted text-center py-4 mb-0">No GRNs found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>GRN Code</th>
                                <th>PO #</th>
                                <th>Subcon</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentGrns as $grn): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$grn['grn_id'] ?>"
                                   class="fw-semibold text-decoration-none">
                                    <?= h($grn['grn_code']) ?>
                                </a>
                            </td>
                            <td class="small"><?= h($grn['po_number'] ?? '—') ?></td>
                            <td class="small"><?= h($grn['subcon_name'] ?? '—') ?></td>
                            <td class="small text-muted">
                                <?= $grn['grn_date'] ? date('d M Y', strtotime($grn['grn_date'])) : '—' ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= grnStatusBadge($grn['status']) ?>">
                                    <?= h($grn['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- GRN Status Breakdown -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-muted"></i>GRN Status Breakdown
                <div class="btn-group btn-group-sm ms-auto" role="group">
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="grn" data-type="doughnut">Donut</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="grn" data-type="pie">Pie</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="grn" data-type="bar">Bar</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="grn" data-type="polarArea">Polar</button>
                </div>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center" style="min-height: 280px;">
                <?php if (!$grnStatusRows): ?>
                <p class="text-muted text-center py-3 mb-0">No GRNs in system yet.</p>
                <?php else: ?>
                <canvas id="grnChart" style="max-height: 250px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ── Row 3: Recent Stock Movements + Open Gate Passes ──────────────────── -->
<div class="row g-3 mb-4">

    <!-- Recent Stock Movements -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-right text-muted"></i>
                Recent Stock Movements
                <span class="badge bg-secondary ms-1">last 10</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentMovements): ?>
                <p class="text-muted text-center py-4 mb-0">No movements recorded yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>PO #</th>
                                <th>SKU</th>
                                <th class="text-end">Qty</th>
                                <th>To Location</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentMovements as $mv): ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?= movementTypeBadge($mv['movement_type']) ?>">
                                    <?= h($mv['movement_type']) ?>
                                </span>
                            </td>
                            <td class="small"><?= h($mv['po_number'] ?? '—') ?></td>
                            <td class="small"><?= h($mv['sku_code'] ?? '—') ?></td>
                            <td class="text-end small"><?= number_format((int)$mv['qty_cartons']) ?></td>
                            <td class="small"><?= h($mv['to_code'] ?? '—') ?></td>
                            <td class="small text-muted">
                                <?= $mv['created_at'] ? date('d M Y', strtotime($mv['created_at'])) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Open Gate Passes -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-truck text-muted"></i>
                Open Gate Passes
                <?php if ($kpiOpenGp > 0): ?>
                <span class="badge bg-warning text-dark ms-1"><?= number_format($kpiOpenGp) ?></span>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/modules/gatepass/list.php" class="btn btn-sm btn-outline-secondary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!$openGatepasses): ?>
                <p class="text-muted text-center py-4 mb-0">No open gate passes.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>GP Code</th>
                                <th>To Party</th>
                                <th>Type</th>
                                <th>Created</th>
                                <?php if (can('gatepass', 'close')): ?>
                                <th></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($openGatepasses as $gp): ?>
                        <tr>
                            <td class="fw-semibold small"><?= h($gp['gp_code']) ?></td>
                            <td class="small"><?= h($gp['to_party'] ?? '—') ?></td>
                            <td class="small"><?= h($gp['gp_type'] ?? '—') ?></td>
                            <td class="small text-muted">
                                <?= $gp['created_at'] ? date('d M Y', strtotime($gp['created_at'])) : '—' ?>
                            </td>
                            <?php if (can('gatepass', 'close')): ?>
                            <td>
                                <form method="post"
                                      action="<?= BASE_URL ?>/modules/gatepass/action.php"
                                      class="d-inline"
                                      onsubmit="return confirm('Close gate pass <?= h($gp['gp_code']) ?>?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="close_gatepass">
                                    <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-lock"></i> Close
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ── Row 4: Operational KPIs (from Google Sheet) ─────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#e11d48;">
            <div class="kpi-label">Pending POs</div>
            <div class="kpi-value"><?= number_format($kpiPendingPos) ?></div>
            <div class="small text-muted mt-1">NEW &amp; WIP status</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#eab308;">
            <div class="kpi-label">Pending GRN</div>
            <div class="kpi-value"><?= number_format($kpiPendingGrn) ?></div>
            <div class="small text-muted mt-1">items received, no GRN</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#3b82f6;">
            <div class="kpi-label">Pending QC</div>
            <div class="kpi-value"><?= number_format($kpiPendingQc) ?></div>
            <div class="small text-muted mt-1">GRNs received, not inspected</div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#10b981;">
            <div class="kpi-label">Pending Putaway</div>
            <div class="kpi-value"><?= number_format($kpiPendingPutaway) ?></div>
            <div class="small text-muted mt-1">QC done, not yet put away</div>
        </div>
    </div>

</div>

<!-- ── Row 5: RSS & Activity ──────────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- RSS Status -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-repeat text-muted"></i>
                RSS / Other-Process Status
                <a href="<?= BASE_URL ?>/modules/rss/list.php" class="btn btn-sm btn-outline-secondary ms-auto">View All</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fw-semibold text-danger"><?= number_format($kpiRssOutPending) ?></div>
                            <div class="small text-muted">RSS Out (Pending)</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded text-center">
                            <div class="fw-semibold text-success"><?= number_format($kpiRssInPending) ?></div>
                            <div class="small text-muted">RSS In (Pending)</div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 small text-muted text-center">
                    Items returned for processing or return to vendor
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-muted"></i>
                Recent Activity Log
                <a href="<?= BASE_URL ?>/modules/audit/list.php" class="btn btn-sm btn-outline-secondary ms-auto">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (!$recentActivities): ?>
                <p class="text-muted text-center py-4 mb-0">No activity recorded yet.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentActivities as $act): ?>
                        <tr>
                            <td class="small text-muted">
                                <?= $act['created_at'] ? date('H:i', strtotime($act['created_at'])) : '—' ?>
                            </td>
                            <td class="small">
                                <?= h($act['full_name'] ?? $act['username'] ?? '—') ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $act['action'] === 'DELETE' ? 'danger' : ($act['action'] === 'UPDATE' ? 'warning' : 'success') ?> me-1">
                                    <?= h($act['action']) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= h($act['module_key'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ── Row 7: WIP Pipeline ───────────────────────────────────────────────── -->
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold py-2">
                <i class="bi bi-diagram-3 text-muted me-2"></i>WIP Pipeline
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40%">Stage</th>
                                <th class="text-end" style="width:20%">In Queue</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <i class="bi bi-bag-check text-primary me-2"></i>
                                    <span class="fw-semibold">POs Pending Receipt</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary fs-6"><?= number_format($wipPendingReceipt) ?></span>
                                </td>
                                <td class="text-muted small">Open POs with no GRN created yet</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-box-arrow-in-down text-info me-2"></i>
                                    <span class="fw-semibold">GRNs Awaiting QC</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-info text-dark fs-6"><?= number_format($wipAwaitingQc) ?></span>
                                </td>
                                <td class="text-muted small">GRN status = RECEIVED, QC not yet started</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-clipboard-check text-warning me-2"></i>
                                    <span class="fw-semibold">In QC</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-warning text-dark fs-6"><?= number_format($wipInQc) ?></span>
                                </td>
                                <td class="text-muted small">GRN status = QC_IN_PROGRESS</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-box-seam text-success me-2"></i>
                                    <span class="fw-semibold">Awaiting Putaway</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success fs-6"><?= number_format($wipAwaitingPutaway) ?></span>
                                </td>
                                <td class="text-muted small">QC COMPLETED, no PUTAWAY movement recorded yet</td>
                            </tr>
                            <tr>
                                <td>
                                    <i class="bi bi-truck text-danger me-2"></i>
                                    <span class="fw-semibold">Open Gate Passes</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-danger fs-6"><?= number_format($wipOpenGp) ?></span>
                                </td>
                                <td class="text-muted small">Gate passes awaiting closure / dispatch</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Real-Time Visualizations (Charts) ────────────────────────────────── -->
<div class="row g-3 mb-4">
    <!-- Destination Breakdown Chart -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-muted"></i>Destination Allocation Quantities
                <div class="btn-group btn-group-sm ms-auto" role="group">
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="dest" data-type="doughnut">Donut</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="dest" data-type="pie">Pie</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="dest" data-type="bar">Bar</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="dest" data-type="polarArea">Polar</button>
                </div>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center" style="min-height: 280px;">
                <canvas id="destinationChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
    <!-- PO Status Breakdown Chart -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart text-muted"></i>Purchase Order Status Distribution
                <div class="btn-group btn-group-sm ms-auto" role="group">
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="po" data-type="doughnut">Donut</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="po" data-type="pie">Pie</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="po" data-type="bar">Bar</button>
                    <button type="button" class="btn btn-outline-secondary chart-type-btn" data-chart="po" data-type="polarArea">Polar</button>
                </div>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center" style="min-height: 280px;">
                <canvas id="poStatusChart" style="max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart data from PHP -->
<script>
const CHART_DATA = {
    grn: {
        labels: <?= json_encode(array_column($grnStatusRows ?: [], 'status')) ?>,
        values: <?= json_encode(array_map('intval', array_column($grnStatusRows ?: [], 'cnt'))) ?>,
        colors: <?= json_encode(array_map(function($row) {
            $map = ['DRAFT'=>'#6c757d','RECEIVED'=>'#0d6efd','QC_IN_PROGRESS'=>'#ffc107',
                    'QC_COMPLETED'=>'#198754','STORED'=>'#20c997','DISPATCHED'=>'#6f42c1','CANCELLED'=>'#dc3545'];
            return $map[$row['status']] ?? '#adb5bd';
        }, $grnStatusRows ?: [])) ?>
    },
    dest: {
        labels: ['Local','SG','Malaysia','Indo','SM Baliwag','Export Full'],
        values: [<?= (int)($destTotals['Local']??0) ?>,<?= (int)($destTotals['SG']??0) ?>,<?= (int)($destTotals['Malaysia']??0) ?>,<?= (int)($destTotals['Indo']??0) ?>,<?= (int)($destTotals['SM Baliwag']??0) ?>,<?= (int)($destTotals['Export Full']??0) ?>],
        colors: ['#1e3a5f','#0d6efd','#198754','#ffc107','#dc3545','#6c757d']
    },
    po: {
        labels: ['NEW','WIP','COMPLETED','CANCELLED'],
        values: [<?= (int)($poStatusCounts['NEW']??0) ?>,<?= (int)($poStatusCounts['WIP']??0) ?>,<?= (int)($poStatusCounts['COMPLETED']??0) ?>,<?= (int)($poStatusCounts['CANCELLED']??0) ?>],
        colors: ['#6c757d','#ffc107','#198754','#dc3545']
    }
};
</script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const charts = {};
const STORAGE_KEY = 'gs_chart_types';

function getSavedTypes() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; } catch(e) { return {}; }
}
function saveType(key, type) {
    const saved = getSavedTypes();
    saved[key] = type;
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(saved)); } catch(e) {}
}

function buildChartConfig(key, type) {
    const d = CHART_DATA[key];
    const isBar = type === 'bar';
    return {
        type: type,
        data: {
            labels: d.labels,
            datasets: [{
                label: 'Count',
                data: d.values,
                backgroundColor: d.colors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: !isBar,
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            scales: isBar ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {}
        }
    };
}

function initChart(canvasId, key, type) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    if (charts[key]) { charts[key].destroy(); }
    charts[key] = new Chart(ctx.getContext('2d'), buildChartConfig(key, type));
}

function switchChart(key, newType) {
    const canvasMap = { grn: 'grnChart', dest: 'destinationChart', po: 'poStatusChart' };
    initChart(canvasMap[key], key, newType);
    saveType(key, newType);
    document.querySelectorAll(`.chart-type-btn[data-chart="${key}"]`).forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === newType);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = getSavedTypes();
    const defaults = { grn: 'doughnut', dest: 'doughnut', po: 'bar' };

    ['grn','dest','po'].forEach(key => {
        const type = saved[key] || defaults[key];
        const canvasMap = { grn: 'grnChart', dest: 'destinationChart', po: 'poStatusChart' };
        initChart(canvasMap[key], key, type);
        document.querySelectorAll(`.chart-type-btn[data-chart="${key}"]`).forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
    });

    document.querySelectorAll('.chart-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchChart(this.dataset.chart, this.dataset.type);
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
