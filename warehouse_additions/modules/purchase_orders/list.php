<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('purchase_orders', 'view');

$db = getDB();

// ── Filters ────────────────────────────────────────────────────────────────
$filterStatus  = $_GET['status']       ?? '';
$filterSubcon  = trim($_GET['subcon']  ?? '');
$filterDateFrom = $_GET['date_from']   ?? '';
$filterDateTo   = $_GET['date_to']     ?? '';
$filterDest    = trim($_GET['destination'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filterStatus !== '') {
    $where[]  = 'po.status = ?';
    $params[] = $filterStatus;
}
if ($filterSubcon !== '') {
    $where[]  = 'sc.subcon_name LIKE ?';
    $params[] = '%' . $filterSubcon . '%';
}
if ($filterDest !== '' && in_array($filterDest, ['LOCAL','SG','MALAYSIA','INDO','SAUDI','SM BALIWAG'], true)) {
    $where[]  = 'po.export_destination = ?';
    $params[] = $filterDest;
}
if ($filterDateFrom !== '') {
    $where[]  = 'po.delivery_date >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[]  = 'po.delivery_date <= ?';
    $params[] = $filterDateTo;
}

$whereClause = implode(' AND ', $where);

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*)
             FROM purchase_orders po
             LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
             WHERE {$whereClause}";
$totalRows = (int)$db->prepare($countSql)->execute($params) ? null : 0;

$stCount = $db->prepare($countSql);
$stCount->execute($params);
$totalRows = (int)$stCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "SELECT po.*,
               sc.subcon_name
        FROM purchase_orders po
        LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
        WHERE {$whereClause}
        ORDER BY po.po_id DESC
        LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── Subcon list for reference (used only in filter, text search is fine) ──
$pageTitle = 'Purchase Orders';
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
        <h5 class="mb-0">Purchase Orders</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php if (can('purchase_orders', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New PO
    </a>
    <?php endif; ?>
</div>

<!-- ── Filters ─────────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach (['NEW', 'WIP', 'COMPLETED', 'CANCELLED'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Subcontractor</label>
            <input type="text" name="subcon" class="form-control form-control-sm"
                   value="<?= h($filterSubcon) ?>" placeholder="Search name…">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Destination</label>
            <select name="destination" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['LOCAL','SG','MALAYSIA','INDO','SAUDI','SM BALIWAG'] as $d): ?>
                <option value="<?= $d ?>" <?= $filterDest === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Delivery From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterDateFrom) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Delivery To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterDateTo) ?>">
        </div>
        <div class="col-sm-1 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- ── Table ───────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>PO Number</th>
                    <th>Style No</th>
                    <th>Destination</th>
                    <th>Subcontractor</th>
                    <th class="text-end">Qty</th>
                    <th>PO Date</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th>Approval</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">No purchase orders found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $po):
                $statusBadge = match($po['status']) {
                    'NEW'       => 'secondary',
                    'WIP'       => 'warning',
                    'COMPLETED' => 'success',
                    'CANCELLED' => 'danger',
                    default     => 'secondary',
                };
                $approvalBadge = $po['approval_status'] === 'approved' ? 'success' : 'warning';
                $isClosed = in_array($po['status'], ['COMPLETED', 'CANCELLED'], true);
            ?>
            <tr>
                <td class="fw-semibold">
                    <a href="view.php?id=<?= (int)$po['po_id'] ?>" class="text-decoration-none">
                        <?= h($po['po_number']) ?>
                    </a>
                </td>
                <td><?= h($po['style_no']) ?></td>
                <td>
                    <?php
                    $d = $po['export_destination'] ?? '';
                    $dc = match($d) {
                        'LOCAL'=>'primary','SG'=>'success','MALAYSIA'=>'info',
                        'INDO'=>'warning','SAUDI'=>'danger','SM BALIWAG'=>'secondary',
                        default=>null
                    };
                    echo $d && $dc ? '<span class="badge bg-'.$dc.'">'.$d.'</span>' : '<span class="text-muted">—</span>';
                    ?>
                </td>
                <td><?= $po['subcon_name'] ? h($po['subcon_name']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$po['po_qty']) ?></td>
                <td><?= $po['po_date'] ? h($po['po_date']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $po['delivery_date'] ? h($po['delivery_date']) : '<span class="text-muted">—</span>' ?></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span></td>
                <td><span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status']) ?></span></td>
                <td class="text-end" style="white-space:nowrap">
                    <a href="view.php?id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if (!$isClosed && can('purchase_orders', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($po['status'] === 'NEW' && can('purchase_orders', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete PO <?= h(addslashes($po['po_number'])) ?>?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_po">
                        <input type="hidden" name="id" value="<?= (int)$po['po_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++):
            $qs = http_build_query(array_merge($_GET, ['page' => $p]));
        ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="list.php?<?= $qs ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
