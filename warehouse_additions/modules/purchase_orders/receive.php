<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('purchase_orders', 'receive');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/purchase_orders/list.php'); }

// Load PO
$stmt = $db->prepare(
    "SELECT po.*, sc.subcon_name
     FROM purchase_orders po
     LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
     WHERE po.po_id = ?"
);
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) {
    setFlash('error', 'PO not found.');
    redirect('/modules/purchase_orders/list.php');
}

// Only allow receiving if NEW or WIP
if (!in_array($po['status'], ['NEW', 'WIP'], true)) {
    setFlash('error', 'This PO is not open for receiving (status: ' . $po['status'] . ').');
    redirect('/modules/purchase_orders/view.php?id=' . $id);
}

// Items for dropdown
$items = $db->query(
    "SELECT item_id, sku_code, item_name FROM items ORDER BY sku_code, item_name"
)->fetchAll();

// Locations grouped by zone
$locRows = $db->query(
    "SELECT wl.location_id, wl.location_code, wl.location_name,
            wz.zone_id, wz.zone_code, wz.zone_name
     FROM warehouse_locations wl
     JOIN warehouse_zones wz ON wz.zone_id = wl.zone_id
     WHERE wl.status = 'ACTIVE'
     ORDER BY wz.zone_code, wl.location_code"
)->fetchAll();

$locsByZone = [];
foreach ($locRows as $r) {
    $locsByZone[$r['zone_id']]['label'] = $r['zone_code'] . ' — ' . $r['zone_name'];
    $locsByZone[$r['zone_id']]['locs'][] = $r;
}

// Recent receipts for this PO (last 10)
$stRecent = $db->prepare(
    "SELECT sm.*,
            wl_to.location_code AS to_location_code,
            wl_to.location_name AS to_location_name,
            it.sku_code, it.item_name
     FROM stock_movements sm
     LEFT JOIN warehouse_locations wl_to ON wl_to.location_id = sm.to_location_id
     LEFT JOIN items it ON it.item_id = sm.item_id
     WHERE sm.po_id = ?
     ORDER BY sm.movement_id DESC
     LIMIT 10"
);
$stRecent->execute([$id]);
$recentMovements = $stRecent->fetchAll();

$statusBadge = match($po['status']) {
    'NEW'       => 'secondary',
    'WIP'       => 'warning',
    'COMPLETED' => 'success',
    'CANCELLED' => 'danger',
    default     => 'secondary',
};

$pageTitle = 'Receive Items — ' . $po['po_number'];
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
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">
        <i class="bi bi-box-arrow-in-down me-1"></i>Receive Items
    </h5>
    <span class="text-muted small"><?= h($po['po_number']) ?></span>
    <span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span>
</div>

<div class="row g-4">

<!-- ── Receive Form ────────────────────────────────────────────────────── -->
<div class="col-lg-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-plus-circle me-2"></i>Record Receipt
        </div>
        <div class="card-body">
            <!-- PO summary -->
            <div class="d-flex gap-3 mb-3 p-2 bg-light rounded small">
                <div>
                    <div class="text-muted">Style</div>
                    <div class="fw-semibold"><?= h($po['style_no']) ?></div>
                </div>
                <div>
                    <div class="text-muted">Subcon</div>
                    <div><?= $po['subcon_name'] ? h($po['subcon_name']) : '—' ?></div>
                </div>
                <div>
                    <div class="text-muted">PO Qty</div>
                    <div><?= number_format((int)$po['po_qty']) ?></div>
                </div>
            </div>

            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="receive_items">
                <input type="hidden" name="po_id"  value="<?= $id ?>">

                <!-- Item -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Item</label>
                    <select name="item_id" class="form-select">
                        <option value="">— none / generic —</option>
                        <?php foreach ($items as $it): ?>
                        <option value="<?= (int)$it['item_id'] ?>">
                            <?= h($it['sku_code']) ?> — <?= h($it['item_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Location -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                    <select name="to_location_id" class="form-select" required>
                        <option value="">— select location —</option>
                        <?php foreach ($locsByZone as $zg): ?>
                        <optgroup label="<?= h($zg['label']) ?>">
                            <?php foreach ($zg['locs'] as $loc): ?>
                            <option value="<?= (int)$loc['location_id'] ?>">
                                <?= h($loc['location_code']) ?>
                                <?= $loc['location_name'] ? ' — ' . h($loc['location_name']) : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quantity -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="qty" class="form-control"
                           required min="1" placeholder="0">
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" name="notes" class="form-control"
                           maxlength="255" placeholder="Optional notes">
                </div>

                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Confirm Receipt
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ── Recent Receipts ─────────────────────────────────────────────────── -->
<div class="col-lg-7">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history me-1"></i>Recent Receipts (last 10)
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Location</th>
                        <th class="text-end">Qty</th>
                        <th>Recorded At</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$recentMovements): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No receipts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentMovements as $m): ?>
                <tr>
                    <td class="small">
                        <?php if ($m['sku_code']): ?>
                            <span class="fw-semibold"><?= h($m['sku_code']) ?></span>
                            <br><span class="text-muted"><?= h($m['item_name']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?= $m['to_location_code']
                            ? h($m['to_location_code'])
                            : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-end"><?= number_format((int)$m['qty_cartons']) ?></td>
                    <td class="small text-muted"><?= h($m['created_at']) ?></td>
                    <td class="small text-muted"><?= $m['notes'] ? h($m['notes']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /row -->

<?php include __DIR__ . '/../../includes/footer.php'; ?>
