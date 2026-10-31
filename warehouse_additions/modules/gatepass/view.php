<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('gatepass', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/gatepass/list.php'); }

// ── Load gatepass header ────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT g.*, u.full_name AS created_by_name, po.po_number, grn.grn_code
     FROM gatepasses g
     LEFT JOIN users u              ON u.user_id  = g.created_by
     LEFT JOIN purchase_orders po   ON po.po_id   = g.po_id
     LEFT JOIN grn                  ON grn.grn_id = g.grn_id
     WHERE g.gp_id = ?"
);
$stmt->execute([$id]);
$gp = $stmt->fetch();

if (!$gp) {
    setFlash('error', 'Gate pass not found.');
    redirect('/modules/gatepass/list.php');
}

// ── Load items ──────────────────────────────────────────────────────────────
$stItems = $db->prepare(
    "SELECT gi.*,
            i.sku_code, i.item_name,
            wl.location_code, wl.zone AS location_zone
     FROM gatepass_items gi
     LEFT JOIN items i                 ON i.item_id      = gi.item_id
     LEFT JOIN warehouse_locations wl  ON wl.location_id = gi.from_location_id
     WHERE gi.gp_id = ?
     ORDER BY gi.gpi_id ASC"
);
$stItems->execute([$id]);
$items = $stItems->fetchAll();

$totalCartons = 0;
foreach ($items as $item) {
    $totalCartons += (int)$item['qty_cartons'];
}

// ── Derived display values ──────────────────────────────────────────────────
$isOpen = in_array($gp['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true);

$typeBadge = match($gp['gp_type']) {
    'OUTGOING'             => 'primary',
    'INCOMING'             => 'info',
    'RETURN'               => 'secondary',
    'TRANSFER'             => 'dark',
    'STOCK_TRANSFER'       => 'dark',
    'INTERNAL_CONSUMPTION' => 'secondary',
    'RETURNABLE'           => 'warning',
    default                => 'secondary',
};

$statusBadge = match($gp['gp_status']) {
    'OPEN'      => 'warning',
    'CLOSED'    => 'success',
    'CANCELLED' => 'danger',
    'CREATED'   => 'warning',
    'PENDING'   => 'info text-dark',
    default     => 'secondary',
};

$pageTitle = 'GP: ' . $gp['gp_code'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page heading ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0">
            <i class="bi bi-truck me-1"></i><?= h($gp['gp_code']) ?>
        </h5>
        <span class="badge bg-<?= $typeBadge ?>"><?= h($gp['gp_type']) ?></span>
        <span class="badge bg-<?= $statusBadge ?>"><?= h($gp['gp_status']) ?></span>
    </div>

    <?php if ($isOpen): ?>
    <div class="d-flex gap-2 flex-wrap">

        <?php if ($gp['gp_status'] === 'PENDING' && can('gatepass', 'close')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Confirm this gate pass and move to OPEN?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="confirm_gatepass">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-info btn-sm">
                <i class="bi bi-check me-1"></i>Confirm
            </button>
        </form>
        <?php endif; ?>

        <?php if (can('gatepass', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>

        <?php if (in_array($gp['gp_status'], ['OPEN', 'CREATED'], true) && can('gatepass', 'close')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Close this gate pass and dispatch all items from stock?\n\nThis action cannot be undone.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="close_gatepass">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i>Close Gate Pass
            </button>
        </form>
        <?php endif; ?>

        <?php if (can('gatepass', 'close')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Cancel this gate pass?\n\nNo stock movement will be recorded.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="cancel_gatepass">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
        </form>
        <?php endif; ?>

        <?php if (can('gatepass', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Permanently delete this gate pass and all its items?\n\nThis cannot be undone.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_gatepass">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if ($gp['gp_status'] === 'CLOSED'): ?>
    <div class="d-flex gap-2">
        <a href="print.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-printer me-1"></i>Print Dispatch Packet
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ── Info card ─────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>Gate Pass Details
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">GP Code</dt>
                    <dd class="col-7 fw-semibold"><?= h($gp['gp_code']) ?></dd>

                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $typeBadge ?>"><?= h($gp['gp_type']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $statusBadge ?>"><?= h($gp['gp_status']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted">To/From Party</dt>
                    <dd class="col-7">
                        <?= $gp['to_party'] ? h($gp['to_party']) : '<span class="text-muted">—</span>' ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Reference</dt>
                    <dd class="col-7">
                        <?= $gp['reference'] ? h($gp['reference']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <?php if ($gp['gp_type'] === 'STOCK_TRANSFER' && $gp['po_id']): ?>
                    <dt class="col-5 text-muted">PO Reference</dt>
                    <dd class="col-7">
                        <a href="/modules/purchase_orders/view.php?id=<?= (int)$gp['po_id'] ?>">
                            <?= h($gp['po_number'] ?? 'PO #' . $gp['po_id']) ?>
                        </a>
                    </dd>
                    <?php endif; ?>

                    <dt class="col-5 text-muted">Created By</dt>
                    <dd class="col-7">
                        <?= $gp['created_by_name'] ? h($gp['created_by_name']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Created At</dt>
                    <dd class="col-7"><?= h($gp['created_at']) ?></dd>

                    <dt class="col-5 text-muted">Closed At</dt>
                    <dd class="col-7">
                        <?= $gp['closed_at'] ? h($gp['closed_at']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <?php if ($gp['confirmed_at'] ?? null): ?>
                    <dt class="col-5 text-muted">Confirmed At</dt>
                    <dd class="col-7"><?= h($gp['confirmed_at']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <?php if ($gp['purpose']): ?>
            <div class="col-12">
                <span class="text-muted small fw-semibold">Purpose:</span>
                <p class="mb-0 small"><?= h($gp['purpose']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($gp['gp_type'] === 'RETURNABLE'): ?>
<!-- ── RSS / Return Details ───────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-arrow-return-left me-2"></i>Return to Subcontractor (RSS) Details
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">RSS Reason Code</dt>
                    <dd class="col-7">
                        <?= ($gp['rss_reason_code'] ?? '') ? h($gp['rss_reason_code']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Vendor Code</dt>
                    <dd class="col-7">
                        <?= ($gp['vendor_code'] ?? '') ? h($gp['vendor_code']) : '<span class="text-muted">—</span>' ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Returnable Date</dt>
                    <dd class="col-7">
                        <?= ($gp['returnable_date'] ?? '') ? h($gp['returnable_date']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">GRN Reference</dt>
                    <dd class="col-7">
                        <?php if ($gp['grn_id'] ?? null): ?>
                        <a href="/modules/grn/view.php?id=<?= (int)$gp['grn_id'] ?>">
                            <?= h($gp['grn_code'] ?? 'GRN #' . $gp['grn_id']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Items table ───────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
        <i class="bi bi-list-ul me-1"></i>Items
        <span class="badge bg-secondary"><?= count($items) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>SKU</th>
                    <th>Item Name</th>
                    <th>From Location</th>
                    <th class="text-end">Qty (Cartons)</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
            <tr>
                <td colspan="5" class="text-center text-muted py-3">No items on this gate pass.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="fw-semibold small"><?= h($item['sku_code'] ?? '—') ?></td>
                <td class="small"><?= h($item['item_name'] ?? '—') ?></td>
                <td class="small">
                    <?php
                    $locCode = $item['location_code'] ?? '';
                    $locZone = $item['location_zone'] ?? '';
                    if ($locCode) {
                        echo h($locCode . ($locZone ? ' (' . $locZone . ')' : ''));
                    } else {
                        echo '<span class="text-muted">—</span>';
                    }
                    ?>
                </td>
                <td class="text-end"><?= number_format((int)$item['qty_cartons']) ?></td>
                <td class="small text-muted">
                    <?= $item['notes'] ? h($item['notes']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($items): ?>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="3" class="text-end">Total Cartons:</td>
                    <td class="text-end"><?= number_format($totalCartons) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
