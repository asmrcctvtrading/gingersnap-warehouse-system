<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dc_putaway', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/dc_putaway/list.php'); }

$stDcp = $db->prepare(
    "SELECT dcp.*,
            dc.dc_type, dc.receiving_date, dc.total_qty AS dc_qty, dc.status AS dc_status,
            g.grn_code, g.grn_id,
            po.po_number, po.style_no
     FROM dc_putaways dcp
     LEFT JOIN dc_receivings dc ON dc.dc_id = dcp.dc_id
     LEFT JOIN grn g ON g.grn_id = dc.grn_id
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     WHERE dcp.dcp_id = ?"
);
$stDcp->execute([$id]);
$dcp = $stDcp->fetch();
if (!$dcp) { setFlash('error','Record not found.'); redirect('/modules/dc_putaway/list.php'); }

$stItems = $db->prepare(
    "SELECT dcpi.*, wl.location_code, wl.zone, wl.description AS loc_desc
     FROM dc_putaway_items dcpi
     LEFT JOIN warehouse_locations wl ON wl.location_id = dcpi.location_id
     WHERE dcpi.dcp_id = ?
     ORDER BY dcpi.dcpi_id"
);
$stItems->execute([$id]);
$items = $stItems->fetchAll();
$totalCartons = array_sum(array_column($items, 'qty_cartons'));

$typeBadge = match($dcp['warehouse_type']) {
    'LOCAL'      => 'primary',
    'EXPORT'     => 'success',
    'SMART_BUYS' => 'danger',
    default      => 'secondary',
};
$stBadge = $dcp['status'] === 'COMPLETED' ? 'success' : 'warning text-dark';
$isPending = $dcp['status'] === 'PENDING';

$putawayHour = $dcp['putaway_time'] ? (int)substr($dcp['putaway_time'], 0, 2) : null;
$pastCutoff  = $putawayHour !== null && $putawayHour >= 14;

$pageTitle = 'DC Putaway #'.$id;
include __DIR__ . '/../../includes/header.php';
$flash = getFlash();
?>
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3">
    <?= h($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i>DC Putaway #<?= $id ?></h5>
        <span class="badge bg-<?= $typeBadge ?>"><?= h($dcp['warehouse_type']) ?></span>
        <span class="badge bg-<?= $stBadge ?>"><?= h($dcp['status']) ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if ($isPending && can('dc_putaway','edit')): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>
        <?php if ($isPending && can('dc_putaway','putaway')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Confirm putaway? This will update stock positions and cannot be undone.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="confirm_putaway">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-check2-all me-1"></i>Confirm Putaway
            </button>
        </form>
        <?php endif; ?>
        <?php if ($isPending && can('dc_putaway','edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Delete this putaway record?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_dcp">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">Putaway Details</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">GP Code</dt>
                    <dd class="col-7 font-monospace fw-semibold"><?= $dcp['gp_code'] ? h($dcp['gp_code']) : '<span class="text-muted">&mdash;</span>' ?></dd>

                    <dt class="col-5 text-muted">Warehouse Type</dt>
                    <dd class="col-7"><span class="badge bg-<?= $typeBadge ?>"><?= h($dcp['warehouse_type']) ?></span></dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge bg-<?= $stBadge ?>"><?= h($dcp['status']) ?></span></dd>

                    <dt class="col-5 text-muted">Putaway Date</dt>
                    <dd class="col-7"><?= h($dcp['putaway_date']) ?></dd>

                    <dt class="col-5 text-muted">Putaway Time</dt>
                    <dd class="col-7">
                        <?php if ($dcp['putaway_time']): ?>
                            <?= h($dcp['putaway_time']) ?>
                            <?php if ($pastCutoff): ?>
                            <span class="badge bg-danger ms-1">Past Cutoff</span>
                            <?php endif; ?>
                        <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Putaway Person</dt>
                    <dd class="col-7"><?= $dcp['putaway_person'] ? h($dcp['putaway_person']) : '<span class="text-muted">&mdash;</span>' ?></dd>

                    <dt class="col-5 text-muted">Total Qty</dt>
                    <dd class="col-7 <?= (int)$dcp['total_qty'] > 4000 ? 'text-danger fw-bold' : 'fw-semibold' ?>">
                        <?= number_format((int)$dcp['total_qty']) ?>
                        <?php if ((int)$dcp['total_qty'] > 4000): ?>
                        <span class="badge bg-danger ms-1">Over Cutoff</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Notes</dt>
                    <dd class="col-7"><?= $dcp['notes'] ? h($dcp['notes']) : '<span class="text-muted">&mdash;</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-2">Linked Records</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">DC Receiving</dt>
                    <dd class="col-7">
                        <?php if ($dcp['dc_id']): ?>
                        <a href="<?= BASE_URL ?>/modules/dc_receiving/view.php?id=<?= $dcp['dc_id'] ?>">
                            DC-<?= $dcp['dc_id'] ?>
                        </a>
                        <span class="badge bg-secondary ms-1"><?= h($dcp['dc_type'] ?? '') ?></span>
                        <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">GRN</dt>
                    <dd class="col-7">
                        <?php if ($dcp['grn_id']): ?>
                        <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= $dcp['grn_id'] ?>"><?= h($dcp['grn_code']) ?></a>
                        <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">PO</dt>
                    <dd class="col-7">
                        <?php if ($dcp['po_number']): ?>
                        <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= (int)($dcp['dc_id'] ?? 0) ?>"><?= h($dcp['po_number']) ?></a>
                        &mdash; <?= h($dcp['style_no'] ?? '') ?>
                        <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">DC Type</dt>
                    <dd class="col-7"><?= $dcp['dc_type'] ? h($dcp['dc_type']) : '<span class="text-muted">&mdash;</span>' ?></dd>

                    <dt class="col-5 text-muted">Receiving Date</dt>
                    <dd class="col-7"><?= $dcp['receiving_date'] ? h($dcp['receiving_date']) : '<span class="text-muted">&mdash;</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold py-2 d-flex justify-content-between">
                Items
                <span class="text-muted small"><?= count($items) ?> row(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th class="text-end">Qty Cartons</th>
                            <th>To Location</th>
                            <th>Zone</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$items): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No items recorded.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="font-monospace small"><?= $item['sku_code'] ? h($item['sku_code']) : '&mdash;' ?></td>
                        <td><?= $item['item_name'] ? h($item['item_name']) : '&mdash;' ?></td>
                        <td class="text-end fw-semibold"><?= number_format((int)$item['qty_cartons']) ?></td>
                        <td><?= $item['location_code'] ? h($item['location_code']) : '<span class="text-muted">&mdash;</span>' ?></td>
                        <td class="small text-muted"><?= $item['zone'] ? h($item['zone']) : '&mdash;' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th class="text-end <?= $totalCartons > 4000 ? 'text-danger' : '' ?>"><?= number_format($totalCartons) ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
