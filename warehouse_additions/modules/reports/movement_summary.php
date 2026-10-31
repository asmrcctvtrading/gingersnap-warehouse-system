<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();

// ── Filters ────────────────────────────────────────────────────────────────
$defaultFrom    = date('Y-m-d', strtotime('-30 days'));
$defaultTo      = date('Y-m-d');

$filterFrom     = $_GET['date_from']     ?? $defaultFrom;
$filterTo       = $_GET['date_to']       ?? $defaultTo;
$filterType     = $_GET['movement_type'] ?? '';
$filterSku      = trim($_GET['sku_code'] ?? '');
$isExport       = isset($_GET['export']) && $_GET['export'] === 'csv';

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterFrom)) $filterFrom = $defaultFrom;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterTo))   $filterTo   = $defaultTo;
// Ensure from <= to
if ($filterFrom > $filterTo) { $tmp = $filterFrom; $filterFrom = $filterTo; $filterTo = $tmp; }

$validTypes = ['', 'PUTAWAY', 'RELOCATION', 'PICK', 'DISPATCH', 'ADJUST'];
if (!in_array($filterType, $validTypes, true)) $filterType = '';

// Date range as full-day boundaries
$dateFrom = $filterFrom . ' 00:00:00';
$dateTo   = $filterTo   . ' 23:59:59';

// ── Summary query (counts by movement type) ────────────────────────────────
$summaryWhere  = ['sm.created_at BETWEEN ? AND ?'];
$summaryParams = [$dateFrom, $dateTo];

if ($filterType !== '') {
    $summaryWhere[]  = 'sm.movement_type = ?';
    $summaryParams[] = $filterType;
}

$summaryWhereClause = implode(' AND ', $summaryWhere);

$summaryStmt = $db->prepare(
    "SELECT movement_type, COUNT(*) AS txn_count, COALESCE(SUM(qty_cartons), 0) AS total_cartons
     FROM stock_movements sm
     WHERE {$summaryWhereClause}
     GROUP BY movement_type
     ORDER BY movement_type"
);
$summaryStmt->execute($summaryParams);
$summaryRows = $summaryStmt->fetchAll();

// ── Detail query ───────────────────────────────────────────────────────────
$detailWhere  = ['sm.created_at BETWEEN ? AND ?'];
$detailParams = [$dateFrom, $dateTo];

if ($filterType !== '') {
    $detailWhere[]  = 'sm.movement_type = ?';
    $detailParams[] = $filterType;
}
if ($filterSku !== '') {
    $detailWhere[]  = 'i.sku_code LIKE ?';
    $detailParams[] = '%' . $filterSku . '%';
}

$detailWhereClause = implode(' AND ', $detailWhere);

$detailSqlBase = "SELECT sm.movement_id, sm.movement_type, sm.created_at,
                         i.sku_code, i.item_name,
                         fl.location_code AS from_loc,
                         tl.location_code AS to_loc,
                         sm.qty_cartons, sm.notes,
                         u.full_name AS by_user
                  FROM stock_movements sm
                  LEFT JOIN items i                  ON i.item_id       = sm.item_id
                  LEFT JOIN warehouse_locations fl   ON fl.location_id  = sm.from_location_id
                  LEFT JOIN warehouse_locations tl   ON tl.location_id  = sm.to_location_id
                  LEFT JOIN users u                  ON u.user_id       = sm.created_by
                  WHERE {$detailWhereClause}
                  ORDER BY sm.created_at DESC";

// ── CSV export (no limit) ─────────────────────────────────────────────────
if ($isExport) {
    $filename = 'movement_summary_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');

    $stmt = $db->prepare($detailSqlBase);
    $stmt->execute($detailParams);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Type', 'Date/Time', 'SKU Code', 'Item Name', 'From Location', 'To Location', 'Qty (Cartons)', 'Notes', 'By']);
    while ($r = $stmt->fetch()) {
        fputcsv($out, [
            $r['movement_id'],
            $r['movement_type'],
            $r['created_at'],
            $r['sku_code']  ?? '',
            $r['item_name'] ?? '',
            $r['from_loc']  ?? '',
            $r['to_loc']    ?? '',
            $r['qty_cartons'],
            $r['notes']     ?? '',
            $r['by_user']   ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Detail with 500 limit for HTML view ───────────────────────────────────
$detailStmt = $db->prepare($detailSqlBase . ' LIMIT 500');
$detailStmt->execute($detailParams);
$detailRows = $detailStmt->fetchAll();

// ── Export URL ─────────────────────────────────────────────────────────────
$exportParams = array_filter([
    'date_from'     => $filterFrom !== $defaultFrom ? $filterFrom : null,
    'date_to'       => $filterTo   !== $defaultTo   ? $filterTo   : null,
    'movement_type' => $filterType !== '' ? $filterType : null,
    'sku_code'      => $filterSku  !== '' ? $filterSku  : null,
]);
// Always include date range in export so it matches the view
$exportParams['date_from'] = $filterFrom;
$exportParams['date_to']   = $filterTo;
$exportParams['export']    = 'csv';
$exportQs = http_build_query($exportParams);

// ── Type badge colours ─────────────────────────────────────────────────────
$typeBadgeColour = [
    'PUTAWAY'    => 'primary',
    'RELOCATION' => 'info',
    'PICK'       => 'warning',
    'DISPATCH'   => 'danger',
    'ADJUST'     => 'secondary',
];

$pageTitle = 'Movement Summary';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">Movement Summary</h5>
        <span class="badge bg-secondary"><?= count($detailRows) ?> records<?= count($detailRows) >= 500 ? ' (limited to 500)' : '' ?></span>
    </div>
    <a href="movement_summary.php?<?= $exportQs ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Filters -->
<form method="get" action="movement_summary.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <label class="form-label small mb-1">Date From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterFrom) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Date To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterTo) ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Movement Type</label>
            <select name="movement_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <?php foreach (['PUTAWAY', 'RELOCATION', 'PICK', 'DISPATCH', 'ADJUST'] as $t): ?>
                <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">SKU Code</label>
            <input type="text" name="sku_code" class="form-control form-control-sm"
                   value="<?= h($filterSku) ?>" placeholder="Search SKU code...">
        </div>
        <div class="col-sm-2 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="movement_summary.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<!-- Summary Cards by Movement Type -->
<?php if ($summaryRows): ?>
<div class="row g-2 mb-3">
    <?php foreach ($summaryRows as $s):
        $colour = $typeBadgeColour[$s['movement_type']] ?? 'secondary';
    ?>
    <div class="col-6 col-sm-4 col-md-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1 h-100">
            <span class="badge bg-<?= $colour ?> mb-1 mx-auto"><?= h($s['movement_type']) ?></span>
            <div class="fw-bold fs-5"><?= number_format((int)$s['txn_count']) ?></div>
            <div class="text-muted small"><?= number_format((int)$s['total_cartons']) ?> cartons</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-light border mb-3">No movements found for this date range.</div>
<?php endif; ?>

<!-- Detail Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-end">Qty (Ctns)</th>
                    <th>Notes</th>
                    <th>By</th>
                    <th>Date / Time</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$detailRows): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">No movements found for current filters.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($detailRows as $r):
                $colour = $typeBadgeColour[$r['movement_type']] ?? 'secondary';
            ?>
            <tr>
                <td><span class="badge bg-<?= $colour ?>"><?= h($r['movement_type']) ?></span></td>
                <td class="fw-semibold"><?= $r['sku_code']  ? h($r['sku_code'])  : '<span class="text-muted">—</span>' ?></td>
                <td class="small text-muted"><?= $r['item_name'] ? h($r['item_name']) : '—' ?></td>
                <td class="small"><?= $r['from_loc'] ? h($r['from_loc']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $r['to_loc']   ? h($r['to_loc'])   : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end fw-semibold"><?= number_format((int)$r['qty_cartons']) ?></td>
                <td class="small text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= h($r['notes'] ?? '') ?>">
                    <?= $r['notes'] ? h($r['notes']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small"><?= $r['by_user'] ? h($r['by_user']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small text-muted" style="white-space:nowrap"><?= $r['created_at'] ? h($r['created_at']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($detailRows) >= 500): ?>
    <div class="card-footer text-muted small text-center py-2">
        Display limited to 500 records. Use Export CSV to download the full dataset.
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
