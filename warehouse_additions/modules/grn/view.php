<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('grn', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/grn/list.php'); }

// Load GRN header
$stmt = $db->prepare(
    "SELECT g.*,
            po.po_number,
            sc.subcon_name,
            u.full_name AS created_by_name
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id     = g.po_id
     LEFT JOIN subcons sc         ON sc.subcon_id  = g.subcon_id
     LEFT JOIN users u            ON u.user_id     = g.created_by
     WHERE g.grn_id = ?"
);
$stmt->execute([$id]);
$grn = $stmt->fetch();
if (!$grn) {
    setFlash('error', 'GRN not found.');
    redirect('/modules/grn/list.php');
}

// Load line items
$stItems = $db->prepare(
    "SELECT * FROM grn_items WHERE grn_id = ? ORDER BY grn_item_id ASC"
);
$stItems->execute([$id]);
$items = $stItems->fetchAll();

$isDraft = $grn['status'] === 'DRAFT';

$statusBadge = match($grn['status']) {
    'DRAFT'          => 'secondary',
    'RECEIVED'       => 'primary',
    'QC_IN_PROGRESS' => 'warning',
    'COMPLETED'      => 'success',
    'REJECTED'       => 'danger',
    default          => 'secondary',
};

// Totals
$totalReceived = 0;
$totalRejected = 0;
foreach ($items as $item) {
    $totalReceived += (int)$item['qty_received'];
    $totalRejected += (int)$item['qty_rejected'];
}

$pageTitle = 'GRN: ' . $grn['grn_code'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($grn['discrepancy_type']) && $grn['discrepancy_type'] !== 'NONE'): ?>
<div class="alert alert-<?= $grn['discrepancy_type'] === 'SHORTAGE' ? 'warning' : 'danger' ?> d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong><?= h($grn['discrepancy_type']) ?></strong>
        <?php if ($grn['discrepancy_type'] === 'SHORTAGE'): ?>
        — Pending qty: <strong><?= number_format((int)$grn['shortage_qty']) ?></strong> units still expected from subcontractor.
        <?php elseif ($grn['discrepancy_type'] === 'EXCESS'): ?>
        — Excess receipt detected. Discrepancy report required.
        <?php elseif ($grn['discrepancy_type'] === 'DAMAGED'): ?>
        — Damaged goods recorded. Discrepancy report required.
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0">
            <i class="bi bi-box-arrow-in-down me-1"></i><?= h($grn['grn_code']) ?>
        </h5>
        <span class="badge bg-<?= $statusBadge ?>"><?= h($grn['status']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($isDraft && can('grn', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>

        <?php if ($isDraft && can('grn', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Delete GRN <?= h(addslashes($grn['grn_code'])) ?>? This cannot be undone.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_grn">
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Delete
            </button>
        </form>
        <?php endif; ?>

        <?php if (in_array($grn['status'], ['DRAFT','RECEIVED','QC_IN_PROGRESS'], true) && can('qc', 'inspect')): ?>
        <a href="<?= BASE_URL ?>/modules/qc/form.php?grn_id=<?= $id ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clipboard-check me-1"></i>Inspect (QC)
        </a>
        <?php endif; ?>

        <?php if (in_array($grn['status'], ['QC_IN_PROGRESS','COMPLETED','REJECTED'], true) && can('qc', 'view')): ?>
        <a href="<?= BASE_URL ?>/modules/qc/view.php?grn_id=<?= $id ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clipboard-data me-1"></i>QC Results
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── GRN Details Card ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>GRN Details
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">GRN Code</dt>
                    <dd class="col-7 fw-semibold"><?= h($grn['grn_code']) ?></dd>

                    <dt class="col-5 text-muted">PO Number</dt>
                    <dd class="col-7">
                        <?php if ($grn['po_number']): ?>
                        <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= (int)$grn['po_id'] ?>"
                           class="text-decoration-none"><?= h($grn['po_number']) ?></a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Subcontractor</dt>
                    <dd class="col-7"><?= $grn['subcon_name'] ? h($grn['subcon_name']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">GRN Date</dt>
                    <dd class="col-7"><?= $grn['grn_date'] ? h($grn['grn_date']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Collection</dt>
                    <dd class="col-7"><?= !empty($grn['collection_name']) ? h($grn['collection_name']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">CTRL #</dt>
                    <dd class="col-7 fw-semibold font-monospace"><?= !empty($grn['ctrl_no']) ? h($grn['ctrl_no']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Invoice No</dt>
                    <dd class="col-7"><?= $grn['invoice_no'] ? h($grn['invoice_no']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Invoice Date</dt>
                    <dd class="col-7"><?= $grn['invoice_date'] ? h($grn['invoice_date']) : '<span class="text-muted">—</span>' ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Gate Entry</dt>
                    <dd class="col-7"><?= $grn['gate_entry'] ? h($grn['gate_entry']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7"><span class="badge bg-<?= $statusBadge ?>"><?= h($grn['status']) ?></span></dd>

                    <dt class="col-5 text-muted">Receiving Person</dt>
                    <dd class="col-7"><?= !empty($grn['receiving_person']) ? h($grn['receiving_person']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Time In</dt>
                    <dd class="col-7"><?= !empty($grn['time_in']) ? h($grn['time_in']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Time Start</dt>
                    <dd class="col-7"><?= !empty($grn['time_start']) ? h($grn['time_start']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Total Received</dt>
                    <dd class="col-7 fw-semibold"><?= number_format((int)$grn['total_qty_received']) ?></dd>

                    <dt class="col-5 text-muted">Total Rejected</dt>
                    <dd class="col-7 <?= (int)$grn['total_qty_rejected'] > 0 ? 'text-danger fw-semibold' : '' ?>">
                        <?= number_format((int)$grn['total_qty_rejected']) ?>
                    </dd>

                    <dt class="col-5 text-muted">Created By</dt>
                    <dd class="col-7"><?= $grn['created_by_name'] ? h($grn['created_by_name']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Created At</dt>
                    <dd class="col-7"><?= $grn['created_at'] ? h($grn['created_at']) : '<span class="text-muted">—</span>' ?></dd>
                </dl>
            </div>
            <?php if ($grn['notes']): ?>
            <div class="col-12">
                <span class="text-muted small">Notes:</span>
                <p class="mb-0 small"><?= h($grn['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Line Items ────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
        <i class="bi bi-list-ul me-1"></i>Line Items
        <span class="badge bg-secondary"><?= count($items) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>SKU Code</th>
                    <th>Item Name</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>Brand</th>
                    <th class="text-end">Qty Received</th>
                    <th class="text-end">Qty Rejected</th>
                    <th>Rejection Reason</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">No line items recorded.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="fw-semibold small"><?= $item['sku_code'] ? h($item['sku_code']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $item['item_name'] ? h($item['item_name']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $item['color'] ? h($item['color']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $item['size'] ? h($item['size']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $item['brand'] ? h($item['brand']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$item['qty_received']) ?></td>
                <td class="text-end <?= (int)$item['qty_rejected'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                    <?= number_format((int)$item['qty_rejected']) ?>
                </td>
                <td class="small text-muted">
                    <?= $item['rejection_reason'] ? h($item['rejection_reason']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($items): ?>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="5" class="text-end small text-muted">Totals:</td>
                    <td class="text-end"><?= number_format($totalReceived) ?></td>
                    <td class="text-end <?= $totalRejected > 0 ? 'text-danger' : '' ?>"><?= number_format($totalRejected) ?></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
