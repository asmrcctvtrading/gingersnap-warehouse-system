<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('purchase_orders', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/purchase_orders/list.php'); }

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

// Related stock movements
$stMov = $db->prepare(
    "SELECT sm.*,
            wl_to.location_code  AS to_location_code,
            wl_to.description    AS to_location_name,
            wl_fr.location_code  AS from_location_code,
            wl_fr.description    AS from_location_name
     FROM stock_movements sm
     LEFT JOIN warehouse_locations wl_to ON wl_to.location_id = sm.to_location_id
     LEFT JOIN warehouse_locations wl_fr ON wl_fr.location_id = sm.from_location_id
     WHERE sm.po_id = ?
     ORDER BY sm.movement_id DESC"
);
$stMov->execute([$id]);
$movements = $stMov->fetchAll();

$statusBadge = match($po['status']) {
    'NEW'       => 'secondary',
    'WIP'       => 'warning',
    'COMPLETED' => 'success',
    'CANCELLED' => 'danger',
    default     => 'secondary',
};
$approvalBadge = $po['approval_status'] === 'approved' ? 'success' : 'warning';
$isClosed      = in_array($po['status'], ['COMPLETED', 'CANCELLED'], true);

$pageTitle = 'PO: ' . $po['po_number'];
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
        <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0">
            <i class="bi bi-bag-check me-1"></i><?= h($po['po_number']) ?>
        </h5>
        <span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span>
        <span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$isClosed && can('purchase_orders', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>

        <?php if ($po['approval_status'] === 'pending' && can('purchase_orders', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Approve this PO?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="approve_po">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i>Approve
            </button>
        </form>
        <?php endif; ?>

        <?php if ($po['status'] === 'NEW' && can('purchase_orders', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Mark this PO as WIP?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_po">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="po_number"      value="<?= h($po['po_number']) ?>">
            <input type="hidden" name="style_no"       value="<?= h($po['style_no']) ?>">
            <input type="hidden" name="po_qty"         value="<?= (int)$po['po_qty'] ?>">
            <input type="hidden" name="subcon_id"      value="<?= (int)$po['subcon_id'] ?>">
            <input type="hidden" name="collection_id"  value="<?= (int)$po['collection_id'] ?>">
            <input type="hidden" name="destination_id" value="<?= (int)$po['destination_id'] ?>">
            <input type="hidden" name="facility"       value="<?= h($po['facility']) ?>">
            <input type="hidden" name="ticket_no"      value="<?= h($po['ticket_no']) ?>">
            <input type="hidden" name="po_date"        value="<?= h($po['po_date']) ?>">
            <input type="hidden" name="delivery_date"  value="<?= h($po['delivery_date']) ?>">
            <input type="hidden" name="supply_type"        value="<?= h($po['supply_type']) ?>">
            <input type="hidden" name="market_type"        value="<?= h($po['market_type']) ?>">
            <input type="hidden" name="export_destination" value="<?= h($po['export_destination'] ?? '') ?>">
            <input type="hidden" name="jo_number"          value="<?= h($po['jo_number'] ?? '') ?>">
            <input type="hidden" name="jo_date"            value="<?= h($po['jo_date'] ?? '') ?>">
            <input type="hidden" name="remarks"            value="<?= h($po['remarks']) ?>">
            <input type="hidden" name="comments_shortfall" value="<?= h($po['comments_shortfall']) ?>">
            <input type="hidden" name="coordinator_id" value="<?= (int)($po['coordinator_id'] ?? 0) ?>">
            <input type="hidden" name="status" value="WIP">
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-play-circle me-1"></i>Mark WIP
            </button>
        </form>
        <?php endif; ?>

        <?php if ($po['status'] === 'WIP' && can('purchase_orders', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Mark this PO as Completed?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_po">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="po_number"      value="<?= h($po['po_number']) ?>">
            <input type="hidden" name="style_no"       value="<?= h($po['style_no']) ?>">
            <input type="hidden" name="po_qty"         value="<?= (int)$po['po_qty'] ?>">
            <input type="hidden" name="subcon_id"      value="<?= (int)$po['subcon_id'] ?>">
            <input type="hidden" name="collection_id"  value="<?= (int)$po['collection_id'] ?>">
            <input type="hidden" name="destination_id" value="<?= (int)$po['destination_id'] ?>">
            <input type="hidden" name="facility"       value="<?= h($po['facility']) ?>">
            <input type="hidden" name="ticket_no"      value="<?= h($po['ticket_no']) ?>">
            <input type="hidden" name="po_date"        value="<?= h($po['po_date']) ?>">
            <input type="hidden" name="delivery_date"  value="<?= h($po['delivery_date']) ?>">
            <input type="hidden" name="supply_type"        value="<?= h($po['supply_type']) ?>">
            <input type="hidden" name="market_type"        value="<?= h($po['market_type']) ?>">
            <input type="hidden" name="export_destination" value="<?= h($po['export_destination'] ?? '') ?>">
            <input type="hidden" name="jo_number"          value="<?= h($po['jo_number'] ?? '') ?>">
            <input type="hidden" name="jo_date"            value="<?= h($po['jo_date'] ?? '') ?>">
            <input type="hidden" name="remarks"            value="<?= h($po['remarks']) ?>">
            <input type="hidden" name="comments_shortfall" value="<?= h($po['comments_shortfall']) ?>">
            <input type="hidden" name="coordinator_id" value="<?= (int)($po['coordinator_id'] ?? 0) ?>">
            <input type="hidden" name="status" value="COMPLETED">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check2-all me-1"></i>Mark Completed
            </button>
        </form>
        <?php endif; ?>

        <?php if (in_array($po['status'], ['NEW', 'WIP'], true) && can('purchase_orders', 'receive')): ?>
        <a href="receive.php?id=<?= $id ?>" class="btn btn-dark btn-sm">
            <i class="bi bi-box-arrow-in-down me-1"></i>Receive Items
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── PO Details Card ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>PO Details
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">PO Number</dt>
                    <dd class="col-7 fw-semibold"><?= h($po['po_number']) ?></dd>

                    <dt class="col-5 text-muted">JO Number</dt>
                    <dd class="col-7 fw-semibold font-monospace"><?= $po['jo_number'] ? h($po['jo_number']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">JO Date</dt>
                    <dd class="col-7">
                        <?php if ($po['jo_date']): ?>
                            <?= h($po['jo_date']) ?>
                            <?php if ($po['po_date'] && $po['jo_date'] !== $po['po_date']): ?>
                            <span class="badge bg-warning text-dark ms-1" title="JO date differs from PO date">&#9888; date mismatch</span>
                            <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Style No</dt>
                    <dd class="col-7"><?= h($po['style_no']) ?></dd>

                    <dt class="col-5 text-muted">Subcontractor</dt>
                    <dd class="col-7"><?= $po['subcon_name'] ? h($po['subcon_name']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Quantity</dt>
                    <dd class="col-7"><?= number_format((int)$po['po_qty']) ?></dd>

                    <dt class="col-5 text-muted">PO Date</dt>
                    <dd class="col-7"><?= $po['po_date'] ? h($po['po_date']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Delivery Date</dt>
                    <dd class="col-7"><?= $po['delivery_date'] ? h($po['delivery_date']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Facility</dt>
                    <dd class="col-7"><?= h($po['facility']) ?></dd>

                    <dt class="col-5 text-muted">Ticket No</dt>
                    <dd class="col-7"><?= $po['ticket_no'] ? h($po['ticket_no']) : '<span class="text-muted">—</span>' ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span></dd>

                    <dt class="col-5 text-muted">Approval</dt>
                    <dd class="col-7"><span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status']) ?></span></dd>

                    <dt class="col-5 text-muted">Supply Type</dt>
                    <dd class="col-7"><?= h($po['supply_type']) ?></dd>

                    <dt class="col-5 text-muted">Market Type</dt>
                    <dd class="col-7"><?= h($po['market_type']) ?></dd>

                    <dt class="col-5 text-muted">Remarks</dt>
                    <dd class="col-7"><?= h($po['remarks']) ?></dd>

                    <dt class="col-5 text-muted">Collection ID</dt>
                    <dd class="col-7"><?= $po['collection_id'] ? (int)$po['collection_id'] : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Export Destination</dt>
                    <dd class="col-7">
                        <?php
                        $dest = $po['export_destination'] ?? '';
                        $destColor = match($dest) {
                            'LOCAL'      => 'primary',
                            'SG'         => 'success',
                            'MALAYSIA'   => 'info',
                            'INDO'       => 'warning',
                            'SAUDI'      => 'danger',
                            'SM BALIWAG' => 'secondary',
                            default      => null,
                        };
                        echo $dest && $destColor
                            ? '<span class="badge bg-' . $destColor . '">' . h($dest) . '</span>'
                            : '<span class="text-muted">—</span>';
                        ?>
                    </dd>

                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7"><?= $po['created_at'] ? h($po['created_at']) : '<span class="text-muted">—</span>' ?></dd>
                </dl>
            </div>
            <?php if ($po['comments_shortfall']): ?>
            <div class="col-12">
                <span class="text-muted small">Comments / Shortfall:</span>
                <p class="mb-0 small"><?= h($po['comments_shortfall']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Stock Movements ─────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
        <i class="bi bi-arrow-left-right me-1"></i>Stock Movements
        <span class="badge bg-secondary"><?= count($movements) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th class="text-end">Qty (Cartons)</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Recorded At</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$movements): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">No stock movements yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($movements as $m): ?>
            <tr>
                <td><span class="badge bg-secondary"><?= h($m['movement_type']) ?></span></td>
                <td class="text-end"><?= number_format((int)$m['qty_cartons']) ?></td>
                <td class="small">
                    <?= $m['from_location_code']
                        ? h($m['from_location_code'] . ' ' . $m['from_location_name'])
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small">
                    <?= $m['to_location_code']
                        ? h($m['to_location_code'] . ' ' . $m['to_location_name'])
                        : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small text-muted"><?= h($m['created_at']) ?></td>
                <td class="small text-muted"><?= $m['notes'] ? h($m['notes']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
