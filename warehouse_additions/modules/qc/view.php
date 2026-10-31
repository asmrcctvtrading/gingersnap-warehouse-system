<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('qc', 'view');

$db    = getDB();
$grnId = (int)($_GET['grn_id'] ?? 0);

if ($grnId <= 0) {
    redirect('/modules/qc/list.php');
}

// ── Load GRN header with ticket join ──────────────────────────────────────
$stGrn = $db->prepare(
    "SELECT g.*,
            po.po_number,
            sc.subcon_name,
            u.full_name AS created_by_name,
            t.ticket_no,
            t.priority_no,
            t.polybag_qty AS ticket_polybag_qty
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id    = g.po_id
     LEFT JOIN subcons sc         ON sc.subcon_id = g.subcon_id
     LEFT JOIN users u            ON u.user_id    = g.created_by
     LEFT JOIN tickets t          ON t.ticket_id  = g.ticket_id
     WHERE g.grn_id = ?"
);
$stGrn->execute([$grnId]);
$grn = $stGrn->fetch();

if (!$grn) {
    setFlash('error', 'GRN not found.');
    redirect('/modules/qc/list.php');
}

// ── Load QC inspection records for this GRN ────────────────────────────────
$stQc = $db->prepare(
    "SELECT
       qi.*,
       gi.sku_code,
       gi.item_name,
       gi.color,
       gi.size,
       gi.brand,
       gi.qty_received,
       u.full_name AS inspector_name
     FROM qc_inspection qi
     LEFT JOIN grn_items gi ON gi.grn_item_id = qi.grn_item_id
     LEFT JOIN users u      ON u.user_id      = qi.inspected_by
     WHERE qi.grn_id = ?
     ORDER BY qi.grn_item_id ASC"
);
$stQc->execute([$grnId]);
$qcRows = $stQc->fetchAll();

// ── Status badge helpers ───────────────────────────────────────────────────
$grnStatusBadge = match($grn['status']) {
    'DRAFT'          => 'secondary',
    'RECEIVED'       => 'primary',
    'QC_IN_PROGRESS' => 'warning',
    'COMPLETED'      => 'success',
    'REJECTED'       => 'danger',
    default          => 'secondary',
};

$qcItemBadge = fn($s) => match($s) {
    'PASSED'      => 'success',
    'FAILED'      => 'danger',
    'PARTIAL'     => 'info',
    'IN_PROGRESS' => 'warning',
    'PENDING'     => 'secondary',
    default       => 'secondary',
};

$dispositionBadge = fn($d) => match($d ?? 'PENDING') {
    'PUTAWAY'         => ['Putaway',         'success'],
    'RSS'             => ['RSS',             'warning'],
    'IN_HOUSE_REPAIR' => ['In-House Repair', 'info'],
    'SMART_BUYS'      => ['Smart Buys',      'danger'],
    'PENDING'         => ['Pending',         'secondary'],
    default           => [$d ?? 'Pending',   'secondary'],
};

$putawayStatusBadge = match($grn['putaway_status'] ?? 'PENDING') {
    'IN_PROGRESS' => ['In Progress', 'warning'],
    'COMPLETED'   => ['Completed',   'success'],
    default       => ['Pending',     'secondary'],
};

// ── Summary totals ─────────────────────────────────────────────────────────
$totalInspected = 0;
$totalPassed    = 0;
$totalFailed    = 0;
foreach ($qcRows as $r) {
    $totalInspected += (int)$r['inspected_qty'];
    $totalPassed    += (int)$r['passed_qty'];
    $totalFailed    += (int)$r['failed_qty'];
}

$putawayStatus  = $grn['putaway_status'] ?? 'PENDING';
$changeSlipRows = array_filter($qcRows, fn($r) => ($r['disposition'] ?? '') === 'IN_HOUSE_REPAIR');

$pageTitle = 'QC: ' . $grn['grn_code'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0">
            <i class="bi bi-clipboard-check me-1"></i><?= h($grn['grn_code']) ?>
        </h5>
        <span class="badge bg-<?= $grnStatusBadge ?>"><?= h($grn['status']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (in_array($grn['status'], ['DRAFT','RECEIVED','QC_IN_PROGRESS'], true) && can('qc', 'inspect')): ?>
        <a href="form.php?grn_id=<?= $grnId ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clipboard-check me-1"></i>
            <?= $grn['status'] === 'QC_IN_PROGRESS' ? 'Re-inspect' : 'Inspect' ?>
        </a>
        <?php endif; ?>

        <?php if ($grn['status'] === 'QC_IN_PROGRESS' && can('qc', 'approve')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Approve GRN <?= h(addslashes($grn['grn_code'])) ?> and mark as Completed?')">
            <?= csrfField() ?>
            <input type="hidden" name="action"  value="approve_grn">
            <input type="hidden" name="grn_id"  value="<?= $grnId ?>">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i>Approve GRN
            </button>
        </form>
        <?php endif; ?>

        <?php if ($grn['status'] === 'COMPLETED' && $putawayStatus !== 'COMPLETED' && can('stock', 'create')): ?>
        <a href="<?= BASE_URL ?>/modules/stock/form.php?grn_id=<?= $grnId ?>"
           class="btn btn-success btn-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i>Putaway Stock
        </a>
        <?php endif; ?>

        <?php if ($grn['status'] === 'COMPLETED' && $putawayStatus !== 'COMPLETED' && can('qc', 'approve')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Mark putaway as complete for <?= h(addslashes($grn['grn_code'])) ?>?')">
            <?= csrfField() ?>
            <input type="hidden" name="action"  value="advance_putaway">
            <input type="hidden" name="grn_id"  value="<?= $grnId ?>">
            <button type="submit" class="btn btn-outline-success btn-sm">
                <i class="bi bi-check2-all me-1"></i>Advance Putaway
            </button>
        </form>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= $grnId ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-in-down me-1"></i>View GRN
        </a>
    </div>
</div>

<!-- ── GRN Header Summary Card ──────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>GRN Details
    </div>
    <div class="card-body py-2">
        <div class="row g-2 small">
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">GRN Code:</span>
                <strong class="ms-1"><?= h($grn['grn_code']) ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">PO Number:</span>
                <strong class="ms-1"><?= $grn['po_number'] ? h($grn['po_number']) : '—' ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Subcontractor:</span>
                <strong class="ms-1"><?= $grn['subcon_name'] ? h($grn['subcon_name']) : '—' ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">GRN Date:</span>
                <strong class="ms-1"><?= $grn['grn_date'] ? h($grn['grn_date']) : '—' ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Total Received:</span>
                <strong class="ms-1"><?= number_format((int)$grn['total_qty_received']) ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Total Rejected:</span>
                <strong class="ms-1 <?= (int)$grn['total_qty_rejected'] > 0 ? 'text-danger' : '' ?>">
                    <?= number_format((int)$grn['total_qty_rejected']) ?>
                </strong>
            </div>
            <?php if (!empty($grn['ticket_id'])): ?>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Ticket:</span>
                <a href="<?= BASE_URL ?>/modules/tickets/view.php?id=<?= (int)$grn['ticket_id'] ?>"
                   class="ms-1 fw-semibold text-decoration-none">
                    #<?= h($grn['ticket_no'] ?? $grn['ticket_id']) ?>
                </a>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Priority:</span>
                <strong class="ms-1"><?= $grn['priority_no'] ? h($grn['priority_no']) : '—' ?></strong>
            </div>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Polybag Allocated:</span>
                <strong class="ms-1"><?= number_format((int)$grn['polybag_qty_allocated']) ?></strong>
            </div>
            <?php endif; ?>
            <div class="col-sm-4 col-md-3">
                <span class="text-muted">Putaway Status:</span>
                <span class="badge ms-1 bg-<?= $putawayStatusBadge[1] ?>"><?= $putawayStatusBadge[0] ?></span>
                <?php if ($putawayStatus === 'COMPLETED' && !empty($grn['putaway_completed_at'])): ?>
                <small class="text-muted ms-1"><?= h($grn['putaway_completed_at']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── QC Results Table ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
        <i class="bi bi-list-check me-1"></i>QC Results
        <span class="badge bg-secondary"><?= count($qcRows) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th class="text-end">Received</th>
                    <th class="text-end">Inspected</th>
                    <th class="text-end">Passed</th>
                    <th class="text-end">Failed</th>
                    <th>Status</th>
                    <th>Disposition</th>
                    <th>Change Slip</th>
                    <th>Inspector</th>
                    <th>Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$qcRows): ?>
            <tr>
                <td colspan="14" class="text-center text-muted py-4">
                    No QC inspection records yet for this GRN.
                    <?php if (can('qc', 'inspect') && in_array($grn['status'], ['DRAFT','RECEIVED','QC_IN_PROGRESS'], true)): ?>
                    <a href="form.php?grn_id=<?= $grnId ?>" class="ms-2">Start Inspection</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($qcRows as $r):
                [$dispLabel, $dispColor] = $dispositionBadge($r['disposition'] ?? 'PENDING');
            ?>
            <tr>
                <td class="small fw-semibold">
                    <?= $r['item_name'] ? h($r['item_name']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small"><?= $r['sku_code'] ? h($r['sku_code']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $r['color'] ? h($r['color']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $r['size'] ? h($r['size']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end"><?= number_format((int)$r['qty_received']) ?></td>
                <td class="text-end"><?= number_format((int)$r['inspected_qty']) ?></td>
                <td class="text-end text-success fw-semibold"><?= number_format((int)$r['passed_qty']) ?></td>
                <td class="text-end <?= (int)$r['failed_qty'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                    <?= number_format((int)$r['failed_qty']) ?>
                </td>
                <td>
                    <span class="badge bg-<?= $qcItemBadge($r['status']) ?>">
                        <?= h($r['status']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= $dispColor ?>"><?= h($dispLabel) ?></span>
                </td>
                <td class="small">
                    <?php if (($r['disposition'] ?? '') === 'IN_HOUSE_REPAIR'): ?>
                    <?= $r['change_slip_no'] ? h($r['change_slip_no']) : '<span class="text-muted">—</span>' ?>
                    <?php if ($r['change_slip_date']): ?>
                    <br><span class="text-muted"><?= h($r['change_slip_date']) ?></span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= $r['inspector_name'] ? h($r['inspector_name']) : '—' ?></td>
                <td class="small"><?= $r['inspection_date'] ? h($r['inspection_date']) : '—' ?></td>
                <td class="small text-muted"><?= $r['qc_notes'] ? h($r['qc_notes']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($qcRows): ?>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td colspan="5" class="text-end small text-muted">Totals:</td>
                    <td class="text-end"><?= number_format($totalInspected) ?></td>
                    <td class="text-end text-success"><?= number_format($totalPassed) ?></td>
                    <td class="text-end <?= $totalFailed > 0 ? 'text-danger' : '' ?>"><?= number_format($totalFailed) ?></td>
                    <td colspan="6"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php if ($changeSlipRows): ?>
<!-- ── Change Slip Summary ────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-file-earmark-text me-2"></i>Change Slip Summary
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item Name</th>
                    <th>SKU</th>
                    <th class="text-end">Failed Qty</th>
                    <th>Change Slip No</th>
                    <th>Change Slip Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($changeSlipRows as $r): ?>
            <tr>
                <td class="small fw-semibold">
                    <?= $r['item_name'] ? h($r['item_name']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="small"><?= $r['sku_code'] ? h($r['sku_code']) : '<span class="text-muted">—</span>' ?></td>
                <td class="text-end text-danger fw-semibold"><?= number_format((int)$r['failed_qty']) ?></td>
                <td class="small"><?= $r['change_slip_no'] ? h($r['change_slip_no']) : '<span class="text-muted">—</span>' ?></td>
                <td class="small"><?= $r['change_slip_date'] ? h($r['change_slip_date']) : '<span class="text-muted">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
