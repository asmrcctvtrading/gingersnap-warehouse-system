<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('rss', 'view');

$db = getDB();

$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$where = [];
$params = [];

if ($status) {
    $where[] = "r.status = ?";
    $params[] = $status;
}
if ($type) {
    $where[] = "r.rss_type = ?";
    $params[] = $type;
}

$sql = "SELECT r.*,
        po.po_number,
        i.sku_code,
        i.item_name,
        u.full_name AS created_by_name,
        CASE WHEN r.qty_out > 0 THEN 'Out' ELSE 'In' END as movement_type
        FROM rss r
        LEFT JOIN purchase_orders po ON po.po_id = r.po_id
        LEFT JOIN items i ON i.item_id = r.item_id
        LEFT JOIN users u ON u.user_id = r.created_by";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY r.created_at DESC LIMIT 200";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Stats
$totalOut = (int) $db->query("SELECT COALESCE(SUM(qty_out), 0) FROM rss WHERE status IN ('PENDING','APPROVED')")->fetchColumn();
$totalIn = (int) $db->query("SELECT COALESCE(SUM(qty_in), 0) FROM rss WHERE status IN ('PENDING','APPROVED')")->fetchColumn();

$pageTitle = 'RSS / Other-Process';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">RSS / Other-Process Records</h5>
    <?php if (can('rss', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Record</a>
    <?php endif; ?>
</div>

<div class="alert alert-info small mb-3">
    <strong>Total Pending:</strong>
    <span class="text-danger me-3">Out: <?= number_format($totalOut) ?></span>
    <span class="text-success">In: <?= number_format($totalIn) ?></span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" class="d-flex flex-wrap gap-2 mb-3">
            <select name="status" class="form-select form-select-sm" style="width:150px">
                <option value="">All Statuses</option>
                <?php foreach (['PENDING','APPROVED','COMPLETED'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" class="form-select form-select-sm" style="width:180px">
                <option value="">All Types</option>
                <?php foreach (['RETURN_VEND','REPAIR','DISPOSE','OTHER'] as $t): ?>
                <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-dark btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>GRN</th>
                        <th>PO #</th>
                        <th>SKU</th>
                        <th>Qty Out</th>
                        <th>Qty In</th>
                        <th>Status</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $badge = match($r['status']) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'info',
                        'COMPLETED' => 'success',
                        default => 'secondary',
                    };
                    $typeBadge = match($r['rss_type']) {
                        'RETURN_VEND' => 'danger',
                        'REPAIR' => 'primary',
                        'DISPOSE' => 'dark',
                        default => 'secondary',
                    };
                ?>
                <tr>
                    <td class="small"><?= fmtDate($r['created_at']) ?></td>
                    <td><span class="badge bg-<?= $typeBadge ?>"><?= h($r['rss_type']) ?></span></td>
                    <td><?= $r['grn_id'] ? '<span class="small text-muted">GRN#'.(int)$r['grn_id'].'</span>' : '—' ?></td>
                    <td><?= $r['po_id'] ? '<span class="small text-muted">PO#'.(int)$r['po_id'].'</span>' : '—' ?></td>
                    <td class="small"><?= h($r['sku_code'] ?? '—') ?></td>
                    <td class="text-end text-danger fw-semibold"><?= number_format($r['qty_out']) ?></td>
                    <td class="text-end text-success fw-semibold"><?= number_format($r['qty_in']) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span></td>
                    <td class="small text-muted"><?= h($r['created_by_name'] ?? '—') ?></td>
                    <td class="text-end">
                        <?php if (can('rss', 'edit')): ?>
                        <a href="form.php?id=<?= (int)$r['rss_id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (can('rss', 'delete')): ?>
                        <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete this RSS record?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['rss_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No RSS records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
