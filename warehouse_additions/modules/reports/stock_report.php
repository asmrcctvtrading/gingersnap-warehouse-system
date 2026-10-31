<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('reports', 'view');

$db = getDB();

// ── Filters ───────────────────────────────────────────────────────────────────

$filterLocation = (int)($_GET['location_id'] ?? 0);
$filterZone     = trim($_GET['zone']         ?? '');
$filterSearch   = trim($_GET['search']       ?? '');
$isExport       = isset($_GET['export']) && $_GET['export'] === '1';

// Location dropdown
$locationsList = $db->query(
    "SELECT location_id, location_code, zone, description
     FROM warehouse_locations
     ORDER BY zone, location_code"
)->fetchAll();

// Distinct zones
$zones = $db->query(
    "SELECT DISTINCT zone FROM warehouse_locations
     WHERE zone IS NOT NULL AND zone <> ''
     ORDER BY zone"
)->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE clause ────────────────────────────────────────────────────────

$wheres = ['sp.qty_cartons > 0'];
$params = [];

if ($filterLocation > 0) {
    $wheres[] = 'l.location_id = ?';
    $params[] = $filterLocation;
}
if ($filterZone !== '') {
    $wheres[] = 'l.zone = ?';
    $params[] = $filterZone;
}
if ($filterSearch !== '') {
    $wheres[] = '(i.item_name LIKE ? OR i.sku_code LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}

$whereClause = implode(' AND ', $wheres);

// ── Main query ────────────────────────────────────────────────────────────────

$sql = "SELECT
            l.location_id,
            l.location_code,
            l.zone,
            l.description,
            i.item_id,
            i.item_name,
            i.sku_code,
            i.brand,
            i.color,
            i.size,
            sp.qty_cartons,
            sp.updated_at
        FROM stock_positions sp
        LEFT JOIN warehouse_locations l ON l.location_id = sp.location_id
        LEFT JOIN items               i ON i.item_id     = sp.item_id
        WHERE {$whereClause}
        ORDER BY l.zone, l.location_code, i.sku_code";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── CSV Export ────────────────────────────────────────────────────────────────

if ($isExport) {
    if (!can('reports', 'export')) {
        http_response_code(403);
        exit('Access denied.');
    }
    $filename = 'stock_report_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Location Code', 'Zone', 'Description',
        'Item Name', 'SKU', 'Brand', 'Color', 'Size',
        'Qty Cartons', 'Last Updated'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['location_code'] ?? '',
            $r['zone']          ?? '',
            $r['description']   ?? '',
            $r['item_name']     ?? '',
            $r['sku_code']      ?? '',
            $r['brand']         ?? '',
            $r['color']         ?? '',
            $r['size']          ?? '',
            $r['qty_cartons'],
            $r['updated_at']    ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Summary KPIs ──────────────────────────────────────────────────────────────

$kpiTotalLocations = count(array_unique(array_column($rows, 'location_id')));
$kpiTotalSkus      = count(array_unique(array_column($rows, 'item_id')));
$kpiTotalCartons   = array_sum(array_column($rows, 'qty_cartons'));

// Group rows by location for subtotals
$byLocation = [];
foreach ($rows as $r) {
    $key = $r['location_code'] ?? '—';
    $byLocation[$key]['meta']  = [
        'location_id'   => $r['location_id'],
        'location_code' => $r['location_code'],
        'zone'          => $r['zone'],
        'description'   => $r['description'],
    ];
    $byLocation[$key]['rows'][]         = $r;
    $byLocation[$key]['subtotal']       = ($byLocation[$key]['subtotal'] ?? 0) + (int)$r['qty_cartons'];
}

// ── Export URL ────────────────────────────────────────────────────────────────

$exportParams = array_filter([
    'location_id' => $filterLocation ?: null,
    'zone'        => $filterZone     ?: null,
    'search'      => $filterSearch   ?: null,
    'export'      => '1',
]);
$exportQs = http_build_query($exportParams);

$pageTitle = 'Stock Report';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-boxes me-2"></i>Stock Report</h5>
    <?php if (can('reports', 'export')): ?>
    <a href="stock_report.php?<?= $exportQs ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<form method="get" action="stock_report.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-6 col-md-3">
            <label class="form-label small mb-1">Location</label>
            <select name="location_id" class="form-select form-select-sm">
                <option value="">All Locations</option>
                <?php foreach ($locationsList as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"
                    <?= $filterLocation === (int)$loc['location_id'] ? 'selected' : '' ?>>
                    <?= h($loc['location_code']) ?>
                    <?= $loc['description'] ? ' — ' . h($loc['description']) : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-md-3">
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
        <div class="col-sm-6 col-md-4">
            <label class="form-label small mb-1">Item Name / SKU</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="Search by item name or SKU code..."
                   value="<?= h($filterSearch) ?>">
        </div>
        <div class="col-sm-6 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="stock_report.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<!-- Summary KPIs -->
<div class="row g-3 mb-3">
    <div class="col-6 col-sm-4">
        <div class="kpi-card">
            <div class="kpi-label">Total Locations</div>
            <div class="kpi-value"><?= number_format($kpiTotalLocations) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <div class="kpi-card" style="border-left-color:#6f42c1;">
            <div class="kpi-label">Total SKUs In Stock</div>
            <div class="kpi-value"><?= number_format($kpiTotalSkus) ?></div>
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <div class="kpi-card" style="border-left-color:#198754;">
            <div class="kpi-label">Total Cartons</div>
            <div class="kpi-value"><?= number_format($kpiTotalCartons) ?></div>
        </div>
    </div>
</div>

<!-- Data Table with location subtotals -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
        <span class="fw-semibold">Stock Snapshot</span>
        <span class="badge bg-secondary"><?= number_format(count($rows)) ?> rows</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Location Code</th>
                    <th>Zone</th>
                    <th>Description</th>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th>Brand</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th class="text-end">Qty Cartons</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="10" class="text-center text-muted py-4">
                    No stock found matching the current filters.
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($byLocation as $locCode => $locData):
                $meta     = $locData['meta'];
                $locRows  = $locData['rows'];
                $subtotal = $locData['subtotal'];
            ?>
            <?php foreach ($locRows as $idx => $r): ?>
            <tr>
                <?php if ($idx === 0): ?>
                <td rowspan="<?= count($locRows) ?>" class="fw-semibold align-top pt-2">
                    <?= h($r['location_code'] ?? '—') ?>
                </td>
                <td rowspan="<?= count($locRows) ?>" class="small align-top pt-2 text-muted">
                    <?= h($r['zone'] ?? '—') ?>
                </td>
                <td rowspan="<?= count($locRows) ?>" class="small align-top pt-2 text-muted">
                    <?= h($r['description'] ?? '—') ?>
                </td>
                <?php endif; ?>
                <td class="small"><?= h($r['item_name'] ?? '—') ?></td>
                <td class="small fw-semibold"><?= h($r['sku_code'] ?? '—') ?></td>
                <td class="small"><?= h($r['brand'] ?? '—') ?></td>
                <td class="small"><?= h($r['color'] ?? '—') ?></td>
                <td class="small"><?= h($r['size'] ?? '—') ?></td>
                <td class="text-end fw-semibold"><?= number_format((int)$r['qty_cartons']) ?></td>
                <td class="small text-muted">
                    <?= $r['updated_at'] ? date('d M Y', strtotime($r['updated_at'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <!-- Subtotal row for this location (10 columns total) -->
            <tr class="table-light">
                <td colspan="8" class="text-end small fw-semibold text-muted">
                    Subtotal &mdash; <?= h($locCode) ?>
                </td>
                <td class="text-end fw-bold"><?= number_format($subtotal) ?></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($rows): ?>
            <tfoot class="table-light">
                <tr class="border-top border-2">
                    <td colspan="8" class="fw-bold text-end">Grand Total</td>
                    <td class="text-end fw-bold"><?= number_format($kpiTotalCartons) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
