<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dc_receiving', 'view');

$db   = getDB();
$dcId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT dc.*,
            g.grn_code, g.grn_date, g.total_qty_received, g.ctrl_no,
            g.receiving_person AS grn_receiver,
            po.po_number, po.style_no, po.export_destination, po.jo_number,
            sc.subcon_name
     FROM dc_receivings dc
     LEFT JOIN grn g ON g.grn_id = dc.grn_id
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
     WHERE dc.dc_id = ?"
);
$stmt->execute([$dcId]);
$dc = $stmt->fetch();

if (!$dc) {
    setFlash('danger', 'DC Receiving record not found.');
    redirect('/modules/dc_receiving/list.php');
}

$flash = getFlash();

$typeBadge = match ($dc['dc_type']) {
    'LOCAL'          => 'primary',
    'EXPORT'         => 'success',
    'SMART_BUYS_DDF' => 'danger',
    default          => 'secondary',
};

$statusBadge = match ($dc['status']) {
    'RECEIVED'          => 'warning',
    'PUTAWAY_PENDING'   => 'info',
    'PUTAWAY_COMPLETED' => 'success',
    default             => 'secondary',
};

$timeHour    = $dc['receiving_time'] ? (int) substr($dc['receiving_time'], 0, 2) : -1;
$pastCutoff  = $timeHour >= 11;

$canEdit = can('dc_receiving', 'edit');

$pageTitle = 'DC Receipt #' . $dcId;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Action Buttons -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="<?= BASE_URL ?>/modules/dc_receiving/list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>

    <?php if ($dc['status'] === 'RECEIVED' && $canEdit): ?>
    <a href="<?= BASE_URL ?>/modules/dc_receiving/form.php?id=<?= $dcId ?>" class="btn btn-sm btn-primary">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <?php endif; ?>

    <?php if ($dc['status'] === 'RECEIVED' && $canEdit): ?>
    <form method="post" action="<?= BASE_URL ?>/modules/dc_receiving/action.php" class="d-inline">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_putaway_pending">
        <input type="hidden" name="id" value="<?= $dcId ?>">
        <button type="submit" class="btn btn-sm btn-info text-white">
            <i class="bi bi-hourglass me-1"></i>Mark Putaway Pending
        </button>
    </form>
    <?php endif; ?>

    <?php if ($dc['status'] === 'PUTAWAY_PENDING' && $canEdit): ?>
    <form method="post" action="<?= BASE_URL ?>/modules/dc_receiving/action.php" class="d-inline">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_putaway_complete">
        <input type="hidden" name="id" value="<?= $dcId ?>">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="bi bi-check2-circle me-1"></i>Mark Putaway Complete
        </button>
    </form>
    <?php endif; ?>

    <?php if ($dc['status'] === 'RECEIVED' && $canEdit): ?>
    <form method="post" action="<?= BASE_URL ?>/modules/dc_receiving/action.php" class="d-inline"
          onsubmit="return confirm('Delete this DC Receiving record? This cannot be undone.');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete_dc">
        <input type="hidden" name="id" value="<?= $dcId ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i>Delete
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Detail Card -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
        <i class="bi bi-building-check text-muted"></i>
        DC Receipt #<?= $dcId ?>
        <span class="badge bg-<?= $typeBadge ?> ms-1"><?= h($dc['dc_type']) ?></span>
        <span class="badge bg-<?= $statusBadge ?> text-dark ms-1"><?= h($dc['status']) ?></span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Left column -->
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">DC ID</dt>
                    <dd class="col-7 small"><?= (int)$dc['dc_id'] ?></dd>

                    <dt class="col-5 text-muted small">GRN Code</dt>
                    <dd class="col-7 small">
                        <?php if ($dc['grn_id']): ?>
                        <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$dc['grn_id'] ?>"
                           class="fw-semibold text-decoration-none">
                            <?= h($dc['grn_code'] ?? '—') ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted small">PO Number</dt>
                    <dd class="col-7 small"><?= h($dc['po_number'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted small">Style No</dt>
                    <dd class="col-7 small"><?= h($dc['style_no'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted small">JO Number</dt>
                    <dd class="col-7 small"><?= h($dc['jo_number'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted small">Subcontractor</dt>
                    <dd class="col-7 small"><?= h($dc['subcon_name'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted small">Export Destination</dt>
                    <dd class="col-7 small"><?= h($dc['export_destination'] ?? '—') ?></dd>
                </dl>
            </div>

            <!-- Right column -->
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted small">DC Type</dt>
                    <dd class="col-7 small">
                        <span class="badge bg-<?= $typeBadge ?>"><?= h($dc['dc_type']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted small">Status</dt>
                    <dd class="col-7 small">
                        <span class="badge bg-<?= $statusBadge ?> text-dark"><?= h($dc['status']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted small">Receiving Date</dt>
                    <dd class="col-7 small">
                        <?= $dc['receiving_date'] ? date('d M Y', strtotime($dc['receiving_date'])) : '—' ?>
                    </dd>

                    <dt class="col-5 text-muted small">Receiving Time</dt>
                    <dd class="col-7 small">
                        <?php if ($dc['receiving_time']): ?>
                        <?= h(substr($dc['receiving_time'], 0, 5)) ?>
                        <?php if ($pastCutoff): ?>
                        <span class="badge bg-warning text-dark ms-1">Past Cutoff</span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted small">Receiving Person</dt>
                    <dd class="col-7 small"><?= h($dc['receiving_person'] ?? '—') ?></dd>

                    <dt class="col-5 text-muted small">Total Qty</dt>
                    <dd class="col-7 small <?= (int)$dc['total_qty'] > 6000 ? 'text-danger fw-bold' : '' ?>">
                        <?= number_format((int)$dc['total_qty']) ?>
                        <?php if ((int)$dc['total_qty'] > 6000): ?>
                        <span class="badge bg-danger ms-1">Over Cutoff</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted small">Defective Qty</dt>
                    <dd class="col-7 small"><?= number_format((int)$dc['defective_qty']) ?></dd>

                    <dt class="col-5 text-muted small">Notes</dt>
                    <dd class="col-7 small"><?= $dc['notes'] ? h($dc['notes']) : '<span class="text-muted">—</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-muted small py-2">
        Created <?= $dc['created_at'] ? date('d M Y H:i', strtotime($dc['created_at'])) : '—' ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
