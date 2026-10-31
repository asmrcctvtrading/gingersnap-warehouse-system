<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('qc', 'inspect');

$db     = getDB();
$grnId  = (int)($_GET['grn_id'] ?? 0);

if ($grnId <= 0) {
    redirect('/modules/qc/list.php');
}

// ── Load GRN header ────────────────────────────────────────────────────────
$stGrn = $db->prepare(
    "SELECT g.*,
            po.po_number,
            sc.subcon_name
     FROM grn g
     LEFT JOIN purchase_orders po ON po.po_id    = g.po_id
     LEFT JOIN subcons sc         ON sc.subcon_id = g.subcon_id
     WHERE g.grn_id = ?"
);
$stGrn->execute([$grnId]);
$grn = $stGrn->fetch();

if (!$grn) {
    setFlash('error', 'GRN not found.');
    redirect('/modules/qc/list.php');
}

if (!in_array($grn['status'], ['DRAFT', 'RECEIVED', 'QC_IN_PROGRESS'], true)) {
    setFlash('error', 'This GRN is not available for QC inspection.');
    redirect('/modules/qc/view.php?grn_id=' . $grnId);
}

// ── Load linked ticket (if any) ────────────────────────────────────────────
$ticket    = null;
$ticketId  = (int)($grn['ticket_id'] ?? 0);
if ($ticketId > 0) {
    $stTicket = $db->prepare(
        "SELECT ticket_no, priority_no, polybag_qty FROM tickets WHERE ticket_id = ?"
    );
    $stTicket->execute([$ticketId]);
    $ticket = $stTicket->fetch() ?: null;
}

// ── Load WIP tickets for assignment dropdown ───────────────────────────────
$stWip = $db->prepare(
    "SELECT ticket_id, ticket_no, priority_no, polybag_qty
     FROM tickets
     WHERE status IN ('OPEN','IN_PROGRESS')
     ORDER BY priority_no ASC"
);
$stWip->execute();
$wipTickets = $stWip->fetchAll();

// ── Load grn_items with any existing QC data ───────────────────────────────
$stItems = $db->prepare(
    "SELECT
       gi.grn_item_id,
       gi.item_id,
       gi.sku_code,
       gi.item_name,
       gi.color,
       gi.size,
       gi.brand,
       gi.qty_received,
       COALESCE(qi.passed_qty,      0)         AS qc_passed,
       COALESCE(qi.failed_qty,      0)         AS qc_failed,
       COALESCE(qi.qc_notes,        '')        AS qc_notes,
       COALESCE(qi.inspection_date, CURDATE()) AS qc_date,
       qi.status                               AS qc_status,
       COALESCE(qi.disposition,     'PENDING') AS qc_disposition,
       qi.change_slip_no                       AS qc_change_slip_no,
       qi.change_slip_date                     AS qc_change_slip_date
     FROM grn_items gi
     LEFT JOIN qc_inspection qi ON qi.grn_item_id = gi.grn_item_id
     WHERE gi.grn_id = ?
     ORDER BY gi.grn_item_id ASC"
);
$stItems->execute([$grnId]);
$items = $stItems->fetchAll();

// ── Determine default inspection date ──────────────────────────────────────
$stDate = $db->prepare(
    "SELECT MAX(inspection_date) FROM qc_inspection WHERE grn_id = ?"
);
$stDate->execute([$grnId]);
$lastDate    = $stDate->fetchColumn();
$defaultDate = $lastDate ?: date('Y-m-d');

$pageTitle = 'QC Inspection: ' . $grn['grn_code'];
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
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">
        <i class="bi bi-clipboard-check me-1"></i>
        QC Inspection: <?= h($grn['grn_code']) ?>
    </h5>
</div>

<?php if (!$items): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>No items found for this GRN.</strong>
    Please complete GRN entry first before performing QC inspection.
</div>
<?php else: ?>

<!-- ── GRN Info Card (read-only) ────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2"></i>GRN Details
    </div>
    <div class="card-body py-2">
        <div class="row g-2 small">
            <div class="col-sm-3">
                <span class="text-muted">GRN Code:</span>
                <strong class="ms-1"><?= h($grn['grn_code']) ?></strong>
            </div>
            <div class="col-sm-3">
                <span class="text-muted">PO Number:</span>
                <strong class="ms-1"><?= $grn['po_number'] ? h($grn['po_number']) : '—' ?></strong>
            </div>
            <div class="col-sm-3">
                <span class="text-muted">Subcontractor:</span>
                <strong class="ms-1"><?= $grn['subcon_name'] ? h($grn['subcon_name']) : '—' ?></strong>
            </div>
            <div class="col-sm-3">
                <span class="text-muted">GRN Date:</span>
                <strong class="ms-1"><?= $grn['grn_date'] ? h($grn['grn_date']) : '—' ?></strong>
            </div>
            <div class="col-sm-3">
                <span class="text-muted">Total Received:</span>
                <strong class="ms-1"><?= number_format((int)$grn['total_qty_received']) ?></strong>
            </div>
            <div class="col-sm-3">
                <span class="text-muted">Status:</span>
                <span class="badge ms-1 bg-<?= match($grn['status']) {
                    'RECEIVED'       => 'primary',
                    'QC_IN_PROGRESS' => 'warning',
                    default          => 'secondary'
                } ?>"><?= h($grn['status']) ?></span>
            </div>
            <div class="col-12">
                <?php if ($ticket): ?>
                <div class="bg-light rounded p-2 mb-2">
                    <i class="bi bi-ticket me-1 text-primary"></i>
                    <strong>Ticket #<?= h($ticket['ticket_no']) ?></strong>
                    <span class="ms-3 text-muted">Priority:</span>
                    <strong class="ms-1"><?= h($ticket['priority_no']) ?></strong>
                    <span class="ms-3 text-muted">Polybag Qty:</span>
                    <strong class="ms-1"><?= number_format((int)$ticket['polybag_qty']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Inspection Form ──────────────────────────────────────────────────── -->
<form method="post" action="action.php" id="qc-form">
    <?= csrfField() ?>
    <input type="hidden" name="action"  value="save_inspection">
    <input type="hidden" name="grn_id"  value="<?= $grnId ?>">

    <!-- Ticket & Polybag assignment -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-ticket me-2"></i>Ticket &amp; Polybag Assignment
        </div>
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-sm-5">
                    <label class="form-label small mb-1">Link Ticket</label>
                    <select name="ticket_id" class="form-select form-select-sm">
                        <option value="">— None —</option>
                        <?php foreach ($wipTickets as $wt): ?>
                        <option value="<?= (int)$wt['ticket_id'] ?>"
                            <?= (int)($grn['ticket_id'] ?? 0) === (int)$wt['ticket_id'] ? 'selected' : '' ?>>
                            #<?= h($wt['ticket_no']) ?> | P<?= h($wt['priority_no']) ?> | Polybag: <?= number_format((int)$wt['polybag_qty']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label class="form-label small mb-1">Polybag Qty Allocated</label>
                    <input type="number" name="polybag_qty_allocated"
                           class="form-control form-control-sm"
                           value="<?= (int)($grn['polybag_qty_allocated'] ?? 0) ?>"
                           min="0">
                </div>
            </div>
        </div>
    </div>

    <!-- Inspection date (applies to all items in this batch) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-calendar3 me-2"></i>Inspection Date
        </div>
        <div class="card-body py-2">
            <div class="row">
                <div class="col-sm-3">
                    <label class="form-label fw-semibold">Inspection Date <span class="text-danger">*</span></label>
                    <input type="date" name="inspection_date" id="inspection_date"
                           class="form-control"
                           value="<?= h($defaultDate) ?>" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Per-item QC table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center gap-2">
            <i class="bi bi-list-check me-1"></i>Item Inspection
            <span class="badge bg-secondary"><?= count($items) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>Brand</th>
                        <th class="text-end" style="min-width:90px">Qty Received</th>
                        <th class="text-end" style="min-width:100px">Passed Qty <span class="text-danger">*</span></th>
                        <th class="text-end" style="min-width:100px">Failed Qty</th>
                        <th style="min-width:170px">Disposition</th>
                        <th style="min-width:260px">Change Slip</th>
                        <th style="min-width:180px">Notes</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr class="qc-item-row"
                    data-qty-received="<?= (int)$item['qty_received'] ?>">
                    <td class="small fw-semibold">
                        <input type="hidden" name="items[<?= $i ?>][grn_item_id]"
                               value="<?= (int)$item['grn_item_id'] ?>">
                        <input type="hidden" name="items[<?= $i ?>][item_id]"
                               value="<?= (int)($item['item_id'] ?? 0) ?>">
                        <?= $item['item_name'] ? h($item['item_name']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="small"><?= $item['sku_code'] ? h($item['sku_code']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= $item['color'] ? h($item['color']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= $item['size'] ? h($item['size']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= $item['brand'] ? h($item['brand']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end">
                        <span class="fw-semibold"><?= number_format((int)$item['qty_received']) ?></span>
                    </td>
                    <td>
                        <input type="number"
                               name="items[<?= $i ?>][passed_qty]"
                               class="form-control form-control-sm text-end passed-qty"
                               value="<?= (int)$item['qc_passed'] ?>"
                               min="0"
                               max="<?= (int)$item['qty_received'] ?>"
                               required>
                    </td>
                    <td>
                        <input type="number"
                               name="items[<?= $i ?>][failed_qty]"
                               class="form-control form-control-sm text-end failed-qty"
                               value="<?= (int)$item['qc_failed'] ?>"
                               min="0"
                               readonly
                               style="background:#f8f9fa;">
                    </td>
                    <td>
                        <select name="items[<?= $i ?>][disposition]"
                                class="form-select form-select-sm disp-select">
                            <option value="PUTAWAY"
                                <?= $item['qc_disposition'] === 'PUTAWAY' ? 'selected' : '' ?>>
                                Putaway
                            </option>
                            <option value="PENDING"
                                <?= $item['qc_disposition'] === 'PENDING' ? 'selected' : '' ?>>
                                — Choose —
                            </option>
                            <option value="RSS"
                                <?= $item['qc_disposition'] === 'RSS' ? 'selected' : '' ?>>
                                RSS (Return to Subcon)
                            </option>
                            <option value="IN_HOUSE_REPAIR"
                                <?= $item['qc_disposition'] === 'IN_HOUSE_REPAIR' ? 'selected' : '' ?>>
                                In-House Repair
                            </option>
                            <option value="SMART_BUYS"
                                <?= $item['qc_disposition'] === 'SMART_BUYS' ? 'selected' : '' ?>>
                                Smart Buys
                            </option>
                        </select>
                    </td>
                    <td>
                        <div class="change-slip-wrap"
                             style="display:<?= $item['qc_disposition'] === 'IN_HOUSE_REPAIR' ? '' : 'none' ?>">
                            <div class="d-flex gap-1">
                                <input type="text"
                                       name="items[<?= $i ?>][change_slip_no]"
                                       class="form-control form-control-sm"
                                       value="<?= h($item['qc_change_slip_no'] ?? '') ?>"
                                       placeholder="Change Slip #"
                                       style="min-width:120px">
                                <input type="date"
                                       name="items[<?= $i ?>][change_slip_date]"
                                       class="form-control form-control-sm"
                                       value="<?= h($item['qc_change_slip_date'] ?? '') ?>"
                                       style="min-width:130px">
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="text"
                               name="items[<?= $i ?>][qc_notes]"
                               class="form-control form-control-sm"
                               value="<?= h($item['qc_notes']) ?>"
                               maxlength="255"
                               placeholder="Notes…">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-save me-1"></i>Save Inspection
        </button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<?php endif; ?>

<script>
(function () {
    'use strict';

    document.querySelectorAll('.qc-item-row').forEach(function (row) {
        var qtyReceived    = parseInt(row.dataset.qtyReceived, 10) || 0;
        var passedInput    = row.querySelector('.passed-qty');
        var failedInput    = row.querySelector('.failed-qty');
        var dispSelect     = row.querySelector('.disp-select');
        var changeSlipWrap = row.querySelector('.change-slip-wrap');

        function updateChangeSlip() {
            if (changeSlipWrap) {
                changeSlipWrap.style.display = dispSelect.value === 'IN_HOUSE_REPAIR' ? '' : 'none';
            }
        }

        function updateDisposition() {
            var failed = parseInt(failedInput.value, 10) || 0;
            if (failed === 0) {
                dispSelect.value    = 'PUTAWAY';
                dispSelect.disabled = true;
            } else {
                dispSelect.disabled = false;
                if (dispSelect.value === 'PUTAWAY') {
                    dispSelect.value = 'PENDING';
                }
            }
            updateChangeSlip();
        }

        function recalcFailed() {
            var passed = Math.max(0, parseInt(passedInput.value, 10) || 0);
            if (passed > qtyReceived) {
                passedInput.value = qtyReceived;
                passed = qtyReceived;
            }
            failedInput.value = Math.max(0, qtyReceived - passed);
            updateDisposition();
        }

        recalcFailed();

        passedInput.addEventListener('input',  recalcFailed);
        passedInput.addEventListener('change', recalcFailed);

        if (dispSelect) {
            dispSelect.addEventListener('change', updateChangeSlip);
        }
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
