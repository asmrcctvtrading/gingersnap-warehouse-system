<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'view');

$db = getDB();

// ── Active tab ─────────────────────────────────────────────────────────────
$activeTab = $_GET['tab'] ?? 'movements';
if (!in_array($activeTab, ['movements', 'positions'], true)) {
    $activeTab = 'movements';
}

$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));

// ── KPI data (always loaded) ───────────────────────────────────────────────
$kpi = $db->query(
    "SELECT
       (SELECT COUNT(*) FROM stock_movements) AS total_movements,
       (SELECT COUNT(*)
        FROM stock_movements
        WHERE movement_type = 'PUTAWAY'
          AND YEAR(created_at)  = YEAR(NOW())
          AND MONTH(created_at) = MONTH(NOW())) AS putaway_month,
       (SELECT COUNT(*)
        FROM stock_movements
        WHERE movement_type = 'ADJUST'
          AND YEAR(created_at)  = YEAR(NOW())
          AND MONTH(created_at) = MONTH(NOW())) AS adjust_month,
       (SELECT COALESCE(SUM(qty_cartons), 0) FROM stock_positions) AS total_cartons"
)->fetch();

// ── MOVEMENTS TAB data ─────────────────────────────────────────────────────
$movRows    = [];
$movTotal   = 0;
$movPages   = 1;

$filterSearch = trim($_GET['search']        ?? '');
$filterType   = $_GET['movement_type']      ?? '';
$filterFrom   = $_GET['date_from']          ?? '';
$filterTo     = $_GET['date_to']            ?? '';

$validTypes = ['PUTAWAY', 'RELOCATION', 'PICK', 'ADJUST', 'DISPATCH'];

if ($activeTab === 'movements') {
    $mWheres = [];
    $mParams = [];

    if ($filterSearch !== '') {
        $mWheres[] = '(po.po_number LIKE ? OR i.item_name LIKE ?)';
        $mParams[] = '%' . $filterSearch . '%';
        $mParams[] = '%' . $filterSearch . '%';
    }
    if ($filterType !== '' && in_array($filterType, $validTypes, true)) {
        $mWheres[] = 'sm.movement_type = ?';
        $mParams[] = $filterType;
    }
    if ($filterFrom !== '') {
        $mWheres[] = 'DATE(sm.created_at) >= ?';
        $mParams[] = $filterFrom;
    }
    if ($filterTo !== '') {
        $mWheres[] = 'DATE(sm.created_at) <= ?';
        $mParams[] = $filterTo;
    }

    $mWhere = $mWheres ? 'WHERE ' . implode(' AND ', $mWheres) : '';

    $cntStmt = $db->prepare(
        "SELECT COUNT(*)
         FROM stock_movements sm
         LEFT JOIN purchase_orders po ON po.po_id   = sm.po_id
         LEFT JOIN items i            ON i.item_id  = sm.item_id
         {$mWhere}"
    );
    $cntStmt->execute($mParams);
    $movTotal = (int)$cntStmt->fetchColumn();
    $movPages = max(1, (int)ceil($movTotal / $perPage));
    $page     = min($page, $movPages);
    $mOffset  = ($page - 1) * $perPage;

    $movStmt = $db->prepare(
        "SELECT sm.movement_id, sm.movement_type, sm.qty_cartons, sm.created_at,
                fl.location_code AS from_code,
                tl.location_code AS to_code,
                po.po_number,
                i.item_name,
                u.full_name AS created_by_name
         FROM stock_movements sm
         LEFT JOIN warehouse_locations fl ON fl.location_id = sm.from_location_id
         LEFT JOIN warehouse_locations tl ON tl.location_id = sm.to_location_id
         LEFT JOIN purchase_orders po     ON po.po_id       = sm.po_id
         LEFT JOIN items i                ON i.item_id      = sm.item_id
         LEFT JOIN users u                ON u.user_id      = sm.created_by
         {$mWhere}
         ORDER BY sm.movement_id DESC
         LIMIT {$perPage} OFFSET {$mOffset}"
    );
    $movStmt->execute($mParams);
    $movRows = $movStmt->fetchAll();
}

// ── POSITIONS TAB data ─────────────────────────────────────────────────────
$posRows     = [];
$posTotal    = 0;
$posPages    = 1;
$locList     = [];

$filterItemSearch = trim($_GET['item_search'] ?? '');
$filterLocId      = (int)($_GET['location_id'] ?? 0);
$filterZone       = trim($_GET['zone']          ?? '');

if ($activeTab === 'positions') {
    $pWheres = [];
    $pParams = [];

    if ($filterItemSearch !== '') {
        $pWheres[] = '(i.sku_code LIKE ? OR i.item_name LIKE ?)';
        $pParams[] = '%' . $filterItemSearch . '%';
        $pParams[] = '%' . $filterItemSearch . '%';
    }
    if ($filterLocId > 0) {
        $pWheres[] = 'sp.location_id = ?';
        $pParams[] = $filterLocId;
    }
    if ($filterZone !== '') {
        $pWheres[] = 'wl.zone = ?';
        $pParams[] = $filterZone;
    }

    $pWhere = $pWheres ? 'WHERE ' . implode(' AND ', $pWheres) : '';

    $cntStmt2 = $db->prepare(
        "SELECT COUNT(*)
         FROM stock_positions sp
         JOIN  warehouse_locations wl ON wl.location_id = sp.location_id
         LEFT JOIN items i            ON i.item_id      = sp.item_id
         {$pWhere}"
    );
    $cntStmt2->execute($pParams);
    $posTotal = (int)$cntStmt2->fetchColumn();
    $posPages = max(1, (int)ceil($posTotal / $perPage));
    $posPage  = min($page, $posPages);
    $pOffset  = ($posPage - 1) * $perPage;

    $posStmt = $db->prepare(
        "SELECT sp.position_id, sp.qty_cartons, sp.updated_at,
                wl.location_code, wl.zone, wl.description AS location_desc,
                i.sku_code, i.item_name, i.color, i.size
         FROM stock_positions sp
         JOIN  warehouse_locations wl ON wl.location_id = sp.location_id
         LEFT JOIN items i            ON i.item_id      = sp.item_id
         {$pWhere}
         ORDER BY wl.zone, wl.location_code, i.sku_code
         LIMIT {$perPage} OFFSET {$pOffset}"
    );
    $posStmt->execute($pParams);
    $posRows = $posStmt->fetchAll();

    $locList = $db->query(
        "SELECT location_id, location_code, description
         FROM warehouse_locations
         ORDER BY location_code"
    )->fetchAll();
}

$pageTitle = 'Stock Ledger';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-boxes me-1"></i>Stock Ledger</h5>
    <?php if (can('stock', 'create')): ?>
    <a href="<?= BASE_URL ?>/modules/stock/form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Record Movement
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">Total Movements</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total_movements']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#3b82f6;">
            <div class="kpi-label">Putaway (This Month)</div>
            <div class="kpi-value text-primary"><?= number_format((int)$kpi['putaway_month']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#f59e0b;">
            <div class="kpi-label">Adjustments (This Month)</div>
            <div class="kpi-value text-warning"><?= number_format((int)$kpi['adjust_month']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">Total Cartons in Stock</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['total_cartons']) ?></div>
        </div>
    </div>
</div>

<!-- ── Bootstrap Tabs ───────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'movements' ? 'active' : '' ?>"
           href="list.php?tab=movements">
            <i class="bi bi-journal-text me-1"></i>Movements
            <span class="badge bg-secondary ms-1"><?= number_format($movTotal) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'positions' ? 'active' : '' ?>"
           href="list.php?tab=positions">
            <i class="bi bi-grid-3x3-gap me-1"></i>Stock Positions
            <span class="badge bg-secondary ms-1"><?= number_format($posTotal) ?></span>
        </a>
    </li>
</ul>

<?php if ($activeTab === 'movements'): ?>
<!-- ======== TAB: MOVEMENTS ================================================ -->

<!-- Filters -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <input type="hidden" name="tab" value="movements">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Search PO / Item</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="PO number or item name...">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Type</label>
            <select name="movement_type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <?php foreach ($validTypes as $mt): ?>
                <option value="<?= $mt ?>" <?= $filterType === $mt ? 'selected' : '' ?>><?= $mt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterFrom) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterTo) ?>">
        </div>
        <div class="col-sm-3 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="list.php?tab=movements" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>PO Number</th>
                    <th>From Location</th>
                    <th>To Location</th>
                    <th>Item</th>
                    <th class="text-end">Cartons</th>
                    <th>Date</th>
                    <th>Recorded By</th>
                    <?php if (can('stock', 'delete')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!$movRows): ?>
            <tr>
                <td colspan="<?= can('stock','delete') ? 10 : 9 ?>"
                    class="text-center text-muted py-4">
                    No movements found.
                    <?php if (can('stock', 'create')): ?>
                    <a href="form.php">Record a movement</a> to get started.
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($movRows as $r):
                $typeBadge = match($r['movement_type']) {
                    'PUTAWAY'    => 'primary',
                    'RELOCATION' => 'info',
                    'PICK'       => 'warning',
                    'ADJUST'     => 'secondary',
                    'DISPATCH'   => 'success',
                    default      => 'light',
                };
            ?>
            <tr>
                <td class="text-muted"><?= (int)$r['movement_id'] ?></td>
                <td>
                    <span class="badge bg-<?= $typeBadge ?>">
                        <?= h($r['movement_type']) ?>
                    </span>
                </td>
                <td><?= h($r['po_number'] ?? '—') ?></td>
                <td class="fw-semibold"><?= h($r['from_code'] ?? '—') ?></td>
                <td class="fw-semibold"><?= h($r['to_code']   ?? '—') ?></td>
                <td class="text-muted"><?= h($r['item_name'] ?? '—') ?></td>
                <td class="text-end fw-semibold"><?= fmtNum($r['qty_cartons']) ?></td>
                <td><?= $r['created_at'] ? h(fmtDate($r['created_at'])) : '—' ?></td>
                <td class="text-muted"><?= h($r['created_by_name'] ?? '') ?></td>
                <?php if (can('stock', 'delete')): ?>
                <td>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete movement #<?= (int)$r['movement_id'] ?>?\nStock positions will NOT be reversed.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_movement">
                        <input type="hidden" name="id" value="<?= (int)$r['movement_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($movPages > 1):
    $qsBase = http_build_query(array_filter([
        'tab'           => 'movements',
        'search'        => $filterSearch,
        'movement_type' => $filterType,
        'date_from'     => $filterFrom,
        'date_to'       => $filterTo,
    ]));
    $base = 'list.php?' . $qsBase . '&';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page - 1 ?>">&laquo; Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end   = min($movPages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $base ?>page=1">1</a></li>
            <?php if ($start > 2): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($end < $movPages): ?>
            <?php if ($end < $movPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $base ?>page=<?= $movPages ?>"><?= $movPages ?></a>
            </li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $movPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page + 1 ?>">Next &raquo;</a>
        </li>
    </ul>
    <p class="text-center text-muted small">
        Page <?= $page ?> of <?= $movPages ?> &mdash; <?= $movTotal ?> records
    </p>
</nav>
<?php endif; ?>

<?php else: ?>
<!-- ======== TAB: STOCK POSITIONS ========================================== -->

<!-- Filters -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <input type="hidden" name="tab" value="positions">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Item Name / SKU</label>
            <input type="text" name="item_search" class="form-control form-control-sm"
                   value="<?= h($filterItemSearch) ?>" placeholder="Search item...">
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Location</label>
            <select name="location_id" class="form-select form-select-sm">
                <option value="">All Locations</option>
                <?php foreach ($locList as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"
                    <?= $filterLocId === (int)$loc['location_id'] ? 'selected' : '' ?>>
                    <?= h($loc['location_code']) ?>
                    <?= ($loc['description'] ?? '') !== '' ? ' — ' . h($loc['description']) : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Zone</label>
            <input type="text" name="zone" class="form-control form-control-sm"
                   value="<?= h($filterZone) ?>" placeholder="Zone code...">
        </div>
        <div class="col-sm-4 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="list.php?tab=positions" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>SKU Code</th>
                    <th>Item Name</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>Location</th>
                    <th>Zone</th>
                    <th class="text-end">Qty Cartons</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$posRows): ?>
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    No stock positions found.
                    <?php if (can('stock', 'create')): ?>
                    <a href="form.php">Record a PUTAWAY</a> to add stock.
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($posRows as $r): ?>
            <tr>
                <td class="fw-semibold"><?= h($r['sku_code'] ?? '—') ?></td>
                <td class="small"><?= h($r['item_name'] ?? '—') ?></td>
                <td class="small text-muted"><?= h($r['color'] ?? '—') ?></td>
                <td class="small text-muted"><?= h($r['size'] ?? '—') ?></td>
                <td class="fw-semibold">
                    <a href="<?= BASE_URL ?>/modules/locations/list.php"
                       class="text-decoration-none">
                        <?= h($r['location_code']) ?>
                    </a>
                    <?php if (($r['location_desc'] ?? '') !== '' && $r['location_desc'] !== $r['location_code']): ?>
                    <small class="text-muted ms-1"><?= h($r['location_desc']) ?></small>
                    <?php endif; ?>
                </td>
                <td><small class="text-muted"><?= h($r['zone'] ?? '—') ?></small></td>
                <td class="text-end fw-semibold"><?= fmtNum($r['qty_cartons']) ?></td>
                <td class="small text-muted"><?= $r['updated_at'] ? fmtDate($r['updated_at']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($posPages > 1):
    $posPage = min($page, $posPages);
    $qsBase2 = http_build_query(array_filter([
        'tab'         => 'positions',
        'item_search' => $filterItemSearch,
        'location_id' => $filterLocId ?: '',
        'zone'        => $filterZone,
    ]));
    $base2 = 'list.php?' . $qsBase2 . '&';
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $posPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base2 ?>page=<?= $posPage - 1 ?>">&laquo; Prev</a>
        </li>
        <?php
        $start = max(1, $posPage - 3);
        $end   = min($posPages, $posPage + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $base2 ?>page=1">1</a></li>
            <?php if ($start > 2): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $posPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= $base2 ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($end < $posPages): ?>
            <?php if ($end < $posPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $base2 ?>page=<?= $posPages ?>"><?= $posPages ?></a>
            </li>
        <?php endif; ?>
        <li class="page-item <?= $posPage >= $posPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base2 ?>page=<?= $posPage + 1 ?>">Next &raquo;</a>
        </li>
    </ul>
    <p class="text-center text-muted small">
        Page <?= $posPage ?> of <?= $posPages ?> &mdash; <?= $posTotal ?> records
    </p>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
