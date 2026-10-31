<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'view');

$db = getDB();

$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$filterType     = $_GET['movement_type'] ?? '';
$filterItem     = trim($_GET['item_code']    ?? '');
$filterPo       = trim($_GET['po_number']    ?? '');
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to']   ?? '';

$wheres = [];
$params = [];

if ($filterType) {
    $wheres[] = 'sm.movement_type = ?';
    $params[] = $filterType;
}
if ($filterItem !== '') {
    $wheres[] = 'i.sku_code LIKE ?';
    $params[] = '%' . $filterItem . '%';
}
if ($filterPo !== '') {
    $wheres[] = 'po.po_number LIKE ?';
    $params[] = '%' . $filterPo . '%';
}
if ($filterDateFrom !== '') {
    $wheres[] = 'DATE(sm.created_at) >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $wheres[] = 'DATE(sm.created_at) <= ?';
    $params[] = $filterDateTo;
}

$where = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

// Total count for pagination
$countStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM stock_movements sm
     LEFT JOIN items i            ON i.item_id  = sm.item_id
     LEFT JOIN purchase_orders po ON po.po_id   = sm.po_id
     $where"
);
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT sm.*,
            fl.location_code AS from_code,
            tl.location_code AS to_code,
            i.sku_code,
            po.po_number,
            u.full_name AS created_by_name
     FROM stock_movements sm
     LEFT JOIN warehouse_locations fl ON fl.location_id = sm.from_location_id
     LEFT JOIN warehouse_locations tl ON tl.location_id = sm.to_location_id
     LEFT JOIN items i                ON i.item_id      = sm.item_id
     LEFT JOIN purchase_orders po     ON po.po_id       = sm.po_id
     LEFT JOIN users u                ON u.user_id      = sm.created_by
     $where
     ORDER BY sm.movement_id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Stock Movement Ledger';
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
        <h5 class="mb-0">Stock Movement Ledger</h5>
        <span class="badge bg-secondary"><?= $totalRows ?> total</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/stock/list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-boxes me-1"></i>Stock Positions
        </a>
        <?php if (can('stock', 'create')): ?>
        <a href="move.php" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Record Movement
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-2">
                <label class="form-label small mb-1">Type</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (['PUTAWAY','RELOCATION','PICK','ADJUST','DISPATCH'] as $mt): ?>
                    <option value="<?= $mt ?>" <?= $filterType === $mt ? 'selected' : '' ?>><?= $mt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm"
                       value="<?= h($filterItem) ?>" placeholder="Item code...">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">PO Number</label>
                <input type="text" name="po_number" class="form-control form-control-sm"
                       value="<?= h($filterPo) ?>" placeholder="PO #...">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= h($filterDateFrom) ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= h($filterDateTo) ?>">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-dark btn-sm me-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="ledger.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date / Time</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Item</th>
                    <th>PO #</th>
                    <th class="text-end">Cartons</th>
                    <th class="text-end">Pieces</th>
                    <th>Ref Doc</th>
                    <th>Notes</th>
                    <th>User</th>
                    <?php if (can('stock', 'delete')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
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
                <td><?= h($r['created_at']) ?></td>
                <td>
                    <span class="badge bg-<?= $typeBadge ?>">
                        <?= h($r['movement_type']) ?>
                    </span>
                </td>
                <td class="fw-semibold"><?= h($r['from_code'] ?? '—') ?></td>
                <td class="fw-semibold"><?= h($r['to_code']   ?? '—') ?></td>
                <td><?= h($r['sku_code'] ?? '—') ?></td>
                <td><?= h($r['po_number'] ?? '—') ?></td>
                <td class="text-end"><?= fmtNum($r['qty_cartons']) ?></td>
                <td class="text-end"><?= fmtNum($r['qty_pieces']) ?></td>
                <td class="text-muted"><?= h($r['reference_doc'] ?? '') ?></td>
                <td class="text-muted"><?= h($r['notes'] ?? '') ?></td>
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
            <?php if (!$rows): ?>
            <tr>
                <td colspan="13" class="text-center text-muted py-4">No movements found.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1):
    $qs = http_build_query(array_filter([
        'movement_type' => $filterType,
        'item_code'     => $filterItem,
        'po_number'     => $filterPo,
        'date_from'     => $filterDateFrom,
        'date_to'       => $filterDateTo,
    ]));
    $base = 'ledger.php?' . ($qs ? $qs . '&' : '');
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page - 1 ?>">&laquo; Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
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
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $base ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
            </li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page + 1 ?>">Next &raquo;</a>
        </li>
    </ul>
    <p class="text-center text-muted small">
        Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= $totalRows ?> records
    </p>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
