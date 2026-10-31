<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('items', 'view');

$db = getDB();

// ── KPI counts ──────────────────────────────────────────────────────────────
$kpiStmt = $db->query(
    "SELECT
       COUNT(*)                   AS total,
       COUNT(DISTINCT brand)      AS brand_count,
       COUNT(DISTINCT color)      AS color_count,
       SUM(EXISTS(
         SELECT 1 FROM stock_positions sp
         WHERE sp.item_id = i.item_id AND sp.qty_cartons > 0
       )) AS in_stock
     FROM items i"
);
$kpi = $kpiStmt->fetch();

// Filters
$fSku    = trim($_GET['sku']    ?? '');
$fName   = trim($_GET['name']   ?? '');
$fStatus = trim($_GET['status'] ?? '');
$fBrand  = trim($_GET['brand']  ?? '');

// Pagination
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Build WHERE clauses
$where  = [];
$params = [];

if ($fSku !== '') {
    $where[]  = 'i.sku_code LIKE :sku';
    $params['sku'] = '%' . $fSku . '%';
}
if ($fName !== '') {
    $where[]  = 'i.item_name LIKE :name';
    $params['name'] = '%' . $fName . '%';
}
if ($fStatus !== '') {
    $where[]  = 'i.status = :status';
    $params['status'] = $fStatus;
}
if ($fBrand !== '') {
    $where[]  = 'i.brand LIKE :brand';
    $params['brand'] = '%' . $fBrand . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count query
$stmtCount = $db->prepare(
    "SELECT COUNT(*) FROM items i $whereSql"
);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Main query with scalar subquery for stock
$stmt = $db->prepare(
    "SELECT i.*,
            COALESCE((SELECT SUM(qty_cartons) FROM stock_positions WHERE item_id = i.item_id), 0) AS stock_cartons
     FROM items i
     $whereSql
     ORDER BY i.sku_code
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue('limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$pageTitle = 'Items / SKU Master';
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
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Items / SKU Master</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php if (can('items', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Item
    </a>
    <?php endif; ?>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">Total SKUs</div>
            <div class="kpi-value"><?= number_format((int)$kpi['total']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#6366f1;">
            <div class="kpi-label">Brands</div>
            <div class="kpi-value" style="color:#6366f1;"><?= number_format((int)$kpi['brand_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#ec4899;">
            <div class="kpi-label">Colors</div>
            <div class="kpi-value" style="color:#ec4899;"><?= number_format((int)$kpi['color_count']) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#22c55e;">
            <div class="kpi-label">In Stock</div>
            <div class="kpi-value text-success"><?= number_format((int)$kpi['in_stock']) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">SKU Code</label>
                <input type="text" name="sku" class="form-control form-control-sm"
                       value="<?= h($fSku) ?>" placeholder="Search SKU…">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Item Name</label>
                <input type="text" name="name" class="form-control form-control-sm"
                       value="<?= h($fName) ?>" placeholder="Search name…">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Brand</label>
                <input type="text" name="brand" class="form-control form-control-sm"
                       value="<?= h($fBrand) ?>" placeholder="Search brand…">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active"   <?= $fStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $fStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-sm-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
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
                    <th>Brand</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>UOM</th>
                    <th>Status</th>
                    <th class="text-end">Stock (Ctn)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $statusBadge = $item['status'] === 'active' ? 'success' : 'secondary';
                $stock = (int)$item['stock_cartons'];
            ?>
            <tr>
                <td class="fw-semibold"><?= h($item['sku_code']) ?></td>
                <td><?= h($item['item_name']) ?></td>
                <td><?= h($item['brand']) ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= h($item['color']) ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= h($item['size'])  ?: '<span class="text-muted">—</span>' ?></td>
                <td><?= h($item['uom'])   ?: 'PCS' ?></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($item['status']) ?></span></td>
                <td class="text-end"><?= fmtNum($stock) ?></td>
                <td class="text-end" style="white-space:nowrap">
                    <?php if (can('items', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$item['item_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('stock', 'view')): ?>
                    <a href="<?= BASE_URL ?>/modules/stock/list.php?item_id=<?= (int)$item['item_id'] ?>"
                       class="btn btn-sm btn-outline-info me-1" title="View Stock">
                        <i class="bi bi-boxes"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('items', 'delete') && $stock === 0): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete item <?= h($item['sku_code']) ?>? This cannot be undone.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="id" value="<?= (int)$item['item_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No items found.
                    <?php if (can('items', 'create')): ?>
                    <a href="form.php">Add the first item.</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3 d-flex justify-content-center">
    <ul class="pagination pagination-sm mb-0">
        <?php
        $queryBase = http_build_query(array_filter([
            'sku'    => $fSku,
            'name'   => $fName,
            'status' => $fStatus,
            'brand'  => $fBrand,
        ]));
        $queryBase = $queryBase ? $queryBase . '&' : '';
        ?>
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $queryBase ?>page=<?= $page + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
