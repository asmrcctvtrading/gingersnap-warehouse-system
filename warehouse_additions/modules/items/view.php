<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('items', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('/modules/items/list.php');
}

// ── Load item ────────────────────────────────────────────────────────────────
$stItem = $db->prepare("SELECT * FROM items WHERE item_id = ?");
$stItem->execute([$id]);
$item = $stItem->fetch();
if (!$item) {
    setFlash('error', 'Item not found.');
    redirect('/modules/items/list.php');
}

// ── Stock positions ──────────────────────────────────────────────────────────
$stStock = $db->prepare(
    "SELECT sp.qty_cartons, sp.qty_pieces, sp.updated_at,
            wl.location_code, wl.zone, wl.description AS loc_desc
     FROM stock_positions sp
     JOIN warehouse_locations wl ON wl.location_id = sp.location_id
     WHERE sp.item_id = ?
       AND sp.qty_cartons > 0
     ORDER BY sp.qty_cartons DESC"
);
$stStock->execute([$id]);
$stockRows = $stStock->fetchAll();

// ── Recent GRN receipts (last 5) ─────────────────────────────────────────────
$stGrn = $db->prepare(
    "SELECT gi.qty_received, gi.qty_rejected, gi.color, gi.size,
            g.grn_id, g.grn_code, g.grn_date, g.created_at
     FROM grn_items gi
     JOIN grn g ON g.grn_id = gi.grn_id
     WHERE gi.sku_code = ?
     ORDER BY g.created_at DESC
     LIMIT 5"
);
$stGrn->execute([$item['sku_code']]);
$grnRows = $stGrn->fetchAll();

$pageTitle = 'Item: ' . $item['sku_code'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i><?= h($item['sku_code']) ?></h5>
    <?php if (can('items', 'edit')): ?>
    <a href="form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <?php endif; ?>
</div>

<div class="row g-3">

    <!-- ── Item Details ─────────────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Item Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">SKU Code</dt>
                    <dd class="col-sm-8 fw-semibold"><?= h($item['sku_code']) ?></dd>

                    <dt class="col-sm-4 text-muted">Item Name</dt>
                    <dd class="col-sm-8"><?= h($item['item_name']) ?></dd>

                    <dt class="col-sm-4 text-muted">Brand</dt>
                    <dd class="col-sm-8"><?= $item['brand'] ? h($item['brand']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-sm-4 text-muted">Color</dt>
                    <dd class="col-sm-8"><?= $item['color'] ? h($item['color']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-sm-4 text-muted">Size</dt>
                    <dd class="col-sm-8"><?= $item['size'] ? h($item['size']) : '<span class="text-muted">—</span>' ?></dd>

                    <?php if (!empty($item['uom'])): ?>
                    <dt class="col-sm-4 text-muted">UOM</dt>
                    <dd class="col-sm-8"><?= h($item['uom']) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])): ?>
                    <dt class="col-sm-4 text-muted">Description</dt>
                    <dd class="col-sm-8"><?= h($item['description']) ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= ($item['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                            <?= h($item['status'] ?? 'active') ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- ── Stock Positions ──────────────────────────────────────────────── -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                Stock Positions
                <?php
                $totalCartons = array_sum(array_column($stockRows, 'qty_cartons'));
                ?>
                <span class="badge bg-primary"><?= number_format($totalCartons) ?> ctn</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Location</th>
                            <th>Zone</th>
                            <th class="text-end">Cartons</th>
                            <th class="text-end">Pieces</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$stockRows): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No stock in any location.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($stockRows as $sp): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($sp['location_code']) ?></td>
                        <td><?= $sp['zone'] ? h($sp['zone']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-end"><?= number_format((int)$sp['qty_cartons']) ?></td>
                        <td class="text-end"><?= number_format((int)$sp['qty_pieces']) ?></td>
                        <td class="text-muted" style="font-size:13px;">
                            <?= $sp['updated_at'] ? h($sp['updated_at']) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Recent GRN Receipts ──────────────────────────────────────────── -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Recent GRN Receipts (last 5)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>GRN Code</th>
                            <th>GRN Date</th>
                            <th>Color</th>
                            <th>Size</th>
                            <th class="text-end">Received</th>
                            <th class="text-end">Rejected</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$grnRows): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No GRN records found for this item.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($grnRows as $grn): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?php if (can('grn', 'view')): ?>
                            <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$grn['grn_id'] ?? 0 ?>"
                               class="text-decoration-none"><?= h($grn['grn_code']) ?></a>
                            <?php else: ?>
                            <?= h($grn['grn_code']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $grn['grn_date'] ? h($grn['grn_date']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $grn['color'] ? h($grn['color']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $grn['size']  ? h($grn['size'])  : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-end"><?= number_format((int)$grn['qty_received']) ?></td>
                        <td class="text-end">
                            <?php if ((int)$grn['qty_rejected'] > 0): ?>
                            <span class="text-danger fw-semibold"><?= number_format((int)$grn['qty_rejected']) ?></span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
