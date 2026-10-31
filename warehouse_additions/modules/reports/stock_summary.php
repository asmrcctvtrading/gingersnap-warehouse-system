<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();

$filterZone   = trim($_GET['zone'] ?? '');
$filterStatus = $_GET['status_filter'] ?? 'all';
$isExport     = isset($_GET['export']) && $_GET['export'] === 'csv';

$validStatuses = ['all', 'with_stock', 'empty'];
if (!in_array($filterStatus, $validStatuses, true)) {
    $filterStatus = 'all';
}

// Distinct zones from warehouse_locations
$zones = $db->query(
    "SELECT DISTINCT zone FROM warehouse_locations
     WHERE zone IS NOT NULL AND zone <> ''
     ORDER BY zone"
)->fetchAll(PDO::FETCH_COLUMN);

// Build query
$wheres = ['l.status = ?'];
$params = ['active'];

if ($filterZone !== '') {
    $wheres[] = 'l.zone = ?';
    $params[] = $filterZone;
}

$having = '';
if ($filterStatus === 'with_stock') {
    $having = 'HAVING total_cartons > 0';
} elseif ($filterStatus === 'empty') {
    $having = 'HAVING total_cartons = 0';
}

$whereClause = implode(' AND ', $wheres);

$sql = "SELECT l.zone,
               l.location_id, l.location_code, l.description,
               COALESCE(SUM(sp.qty_cartons), 0) AS total_cartons,
               COUNT(DISTINCT sp.item_id)        AS unique_items
        FROM warehouse_locations l
        LEFT JOIN stock_positions sp ON sp.location_id = l.location_id
        WHERE {$whereClause}
        GROUP BY l.location_id
        {$having}
        ORDER BY l.zone, l.location_code";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// CSV export
if ($isExport) {
    $filename = 'stock_summary_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Zone', 'Location Code', 'Description', 'Stock (Cartons)', 'Unique SKUs']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['zone'],
            $r['location_code'],
            $r['description'],
            $r['total_cartons'],
            $r['unique_items'],
        ]);
    }
    fclose($out);
    exit;
}

$totalLocations = count($rows);
$totalCartons   = array_sum(array_column($rows, 'total_cartons'));

$uniqueSkuSQL = "SELECT COUNT(DISTINCT sp.item_id)
                 FROM warehouse_locations l
                 JOIN stock_positions sp ON sp.location_id = l.location_id
                 WHERE l.status = 'active'"
    . ($filterZone !== '' ? " AND l.zone = ?" : '');
$uParams = $filterZone !== '' ? [$filterZone] : [];
$uniqueSkuStmt = $db->prepare($uniqueSkuSQL);
$uniqueSkuStmt->execute($uParams);
$totalUniqueSkus = (int)$uniqueSkuStmt->fetchColumn();

// Group by zone text
$byZone = [];
foreach ($rows as $r) {
    $z = $r['zone'] ?: '(No Zone)';
    $byZone[$z][] = $r;
}

$exportParams = array_filter([
    'zone'          => $filterZone ?: null,
    'status_filter' => $filterStatus !== 'all' ? $filterStatus : null,
    'export'        => 'csv',
]);
$exportQs = http_build_query($exportParams);

$pageTitle = 'Stock Summary';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">Stock Summary</h5>
        <span class="badge bg-secondary"><?= count($rows) ?> locations</span>
    </div>
    <a href="stock_summary.php?<?= $exportQs ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Active Locations</div>
            <div class="kpi-value"><?= number_format($totalLocations) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Total Cartons</div>
            <div class="kpi-value"><?= number_format($totalCartons) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Unique SKUs in Stock</div>
            <div class="kpi-value"><?= number_format($totalUniqueSkus) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="kpi-card">
            <div class="kpi-label">Zones</div>
            <div class="kpi-value"><?= number_format(count($byZone)) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" action="stock_summary.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4">
            <label class="form-label small mb-1">Zone</label>
            <select name="zone" class="form-select form-select-sm">
                <option value="">All Zones</option>
                <?php foreach ($zones as $z): ?>
                <option value="<?= h($z) ?>" <?= $filterZone === $z ? 'selected' : '' ?>>
                    <?= h($z) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-4">
            <label class="form-label small mb-1">Stock Status</label>
            <select name="status_filter" class="form-select form-select-sm">
                <option value="all"        <?= $filterStatus === 'all'        ? 'selected' : '' ?>>All Locations</option>
                <option value="with_stock" <?= $filterStatus === 'with_stock' ? 'selected' : '' ?>>With Stock Only</option>
                <option value="empty"      <?= $filterStatus === 'empty'      ? 'selected' : '' ?>>Empty Locations</option>
            </select>
        </div>
        <div class="col-sm-4 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="stock_summary.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Location Code</th>
                    <th>Description</th>
                    <th class="text-end">Stock (Cartons)</th>
                    <th class="text-end">Unique SKUs</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No locations found matching current filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($byZone as $zoneName => $locs): ?>
            <tr class="table-secondary">
                <td colspan="4" class="fw-semibold small">
                    <i class="bi bi-geo-alt me-1"></i><?= h($zoneName) ?>
                    <span class="text-muted fw-normal ms-2">(<?= count($locs) ?> location<?= count($locs) !== 1 ? 's' : '' ?>)</span>
                </td>
            </tr>
            <?php foreach ($locs as $r): ?>
            <tr>
                <td class="fw-semibold ps-4"><?= h($r['location_code']) ?></td>
                <td class="text-muted small"><?= h($r['description']) ?></td>
                <td class="text-end fw-semibold"><?= number_format((int)$r['total_cartons']) ?></td>
                <td class="text-end"><?= number_format((int)$r['unique_items']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
