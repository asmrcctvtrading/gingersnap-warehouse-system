<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db   = getDB();
$id   = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('grn', 'edit');

    $stmt = $db->prepare(
        "SELECT g.*, po.po_number, sc.subcon_name
         FROM grn g
         LEFT JOIN purchase_orders po ON po.po_id = g.po_id
         LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
         WHERE g.grn_id = ?"
    );
    $stmt->execute([$id]);
    $grn = $stmt->fetch();
    if (!$grn) { redirect('/modules/grn/list.php'); }

    if ($grn['status'] !== 'DRAFT') {
        setFlash('error', 'Only DRAFT GRNs can be edited.');
        redirect('/modules/grn/view.php?id=' . $id);
    }

    // Load existing line items
    $stItems = $db->prepare(
        "SELECT * FROM grn_items WHERE grn_id = ? ORDER BY grn_item_id ASC"
    );
    $stItems->execute([$id]);
    $existingItems = $stItems->fetchAll();

} else {
    requirePermission('grn', 'create');
    $grn = [
        'grn_id'           => 0,
        'grn_code'         => '',
        'grn_date'         => date('Y-m-d'),
        'po_id'            => '',
        'subcon_id'        => '',
        'invoice_no'       => '',
        'invoice_date'     => '',
        'gate_entry'       => '',
        'notes'            => '',
        'collection_name'  => '',
        'ctrl_no'          => '',
        'receiving_person' => '',
        'time_in'          => '',
        'time_start'       => '',
        'discrepancy_type' => 'NONE',
        'shortage_qty'     => 0,
        'status'           => 'DRAFT',
        'po_number'        => '',
        'subcon_name'      => '',
    ];
    $existingItems = [];
}

// Load POs for dropdown (non-cancelled)
$poList = $db->query(
    "SELECT po.po_id, po.po_number, po.subcon_id, sc.subcon_name
     FROM purchase_orders po
     LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
     WHERE po.status NOT IN ('CANCELLED','COMPLETED')
     ORDER BY po.po_number ASC"
)->fetchAll();

// Build PO→subcon map for JS auto-fill
$poSubconMap = [];
foreach ($poList as $po) {
    $poSubconMap[(int)$po['po_id']] = [
        'subcon_id'   => (int)$po['subcon_id'],
        'subcon_name' => $po['subcon_name'] ?? '',
    ];
}

$pageTitle = $isEdit ? 'Edit GRN: ' . $grn['grn_code'] : 'New GRN';
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
    <a href="<?= $isEdit ? 'view.php?id=' . (int)$grn['grn_id'] : 'list.php' ?>"
       class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-box-arrow-in-down me-1"></i>
        <?= $isEdit ? 'Edit GRN: ' . h($grn['grn_code']) : 'New GRN' ?>
    </h5>
</div>

<form method="post" action="action.php" id="grn-form">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_grn">
    <input type="hidden" name="id" value="<?= (int)$grn['grn_id'] ?>">

    <!-- ── GRN Header ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-info-circle me-2"></i>GRN Header
        </div>
        <div class="card-body">
            <div class="row g-3">

                <!-- GRN Code -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">GRN Code</label>
                    <input type="text" class="form-control"
                           value="<?= $isEdit ? h($grn['grn_code']) : '' ?>"
                           placeholder="Auto-generated on save" readonly
                           style="background:#f8f9fa;">
                </div>

                <!-- GRN Date -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">GRN Date <span class="text-danger">*</span></label>
                    <input type="date" name="grn_date" class="form-control"
                           value="<?= h($grn['grn_date']) ?>" required>
                </div>

                <!-- PO Number -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Purchase Order <span class="text-danger">*</span></label>
                    <select name="po_id" id="po-select" class="form-select" required>
                        <option value="">— Select PO —</option>
                        <?php foreach ($poList as $po): ?>
                        <option value="<?= (int)$po['po_id'] ?>"
                            <?= (string)$grn['po_id'] === (string)$po['po_id'] ? 'selected' : '' ?>>
                            <?= h($po['po_number']) ?><?= $po['subcon_name'] ? ' — ' . h($po['subcon_name']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Subcon (read-only display, filled by JS) -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Subcontractor</label>
                    <input type="text" id="subcon-display" class="form-control"
                           value="<?= h($grn['subcon_name'] ?? '') ?>"
                           placeholder="Auto-filled from PO" readonly
                           style="background:#f8f9fa;">
                    <input type="hidden" name="subcon_id" id="subcon-id"
                           value="<?= (int)($grn['subcon_id'] ?? 0) ?>">
                </div>

                <!-- Invoice No -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Invoice No</label>
                    <input type="text" name="invoice_no" class="form-control"
                           value="<?= h($grn['invoice_no']) ?>" maxlength="100"
                           placeholder="Supplier invoice number">
                </div>

                <!-- Invoice Date -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control"
                           value="<?= h($grn['invoice_date']) ?>">
                </div>

                <!-- Gate Entry -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Gate Entry No</label>
                    <input type="text" name="gate_entry" class="form-control"
                           value="<?= h($grn['gate_entry']) ?>" maxlength="100"
                           placeholder="Gate entry / challan no">
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"
                              placeholder="Any remarks for this GRN"><?= h($grn['notes']) ?></textarea>
                </div>

                <!-- Collection Name -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Collection Name</label>
                    <input type="text" name="collection_name" class="form-control"
                           value="<?= h($grn['collection_name'] ?? '') ?>" maxlength="255"
                           placeholder="e.g. SS2026, Holiday 2026">
                </div>

                <!-- CTRL No (auto-generated on save, read-only) -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">CTRL #</label>
                    <input type="text" class="form-control"
                           value="<?= $isEdit ? h($grn['ctrl_no'] ?? '') : '' ?>"
                           placeholder="Auto-generated on save" readonly
                           style="background:#f8f9fa;">
                </div>

                <!-- Receiving Person -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Receiving Person</label>
                    <input type="text" name="receiving_person" class="form-control"
                           value="<?= h($grn['receiving_person'] ?? '') ?>" maxlength="255"
                           placeholder="Name of receiving staff">
                </div>

                <!-- Time In -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Time In</label>
                    <input type="time" name="time_in" class="form-control"
                           value="<?= h($grn['time_in'] ?? '') ?>">
                </div>

                <!-- Time Start -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Time Start (Scanning)</label>
                    <input type="time" name="time_start" class="form-control"
                           value="<?= h($grn['time_start'] ?? '') ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ── Line Items ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-2"></i>Line Items</span>
            <button type="button" class="btn btn-outline-dark btn-sm" id="btn-add-row">
                <i class="bi bi-plus-lg me-1"></i>Add Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="items-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:110px">SKU Code</th>
                        <th style="min-width:160px">Item Name</th>
                        <th style="min-width:90px">Color</th>
                        <th style="min-width:80px">Size</th>
                        <th style="min-width:90px">Brand</th>
                        <th style="min-width:90px" class="text-end">Qty Received</th>
                        <th style="min-width:90px" class="text-end">Qty Rejected</th>
                        <th style="min-width:180px">Rejection Reason</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                <?php if ($existingItems): ?>
                    <?php foreach ($existingItems as $i => $item): ?>
                    <tr class="item-row">
                        <td><input type="text" name="items[<?= $i ?>][sku_code]"
                               class="form-control form-control-sm"
                               value="<?= h($item['sku_code']) ?>" maxlength="100"></td>
                        <td><input type="text" name="items[<?= $i ?>][item_name]"
                               class="form-control form-control-sm"
                               value="<?= h($item['item_name']) ?>" maxlength="255"></td>
                        <td><input type="text" name="items[<?= $i ?>][color]"
                               class="form-control form-control-sm"
                               value="<?= h($item['color']) ?>" maxlength="100"></td>
                        <td><input type="text" name="items[<?= $i ?>][size]"
                               class="form-control form-control-sm"
                               value="<?= h($item['size']) ?>" maxlength="50"></td>
                        <td><input type="text" name="items[<?= $i ?>][brand]"
                               class="form-control form-control-sm"
                               value="<?= h($item['brand']) ?>" maxlength="100"></td>
                        <td><input type="number" name="items[<?= $i ?>][qty_received]"
                               class="form-control form-control-sm text-end qty-received"
                               value="<?= (int)$item['qty_received'] ?>" min="0"></td>
                        <td><input type="number" name="items[<?= $i ?>][qty_rejected]"
                               class="form-control form-control-sm text-end qty-rejected"
                               value="<?= (int)$item['qty_rejected'] ?>" min="0"></td>
                        <td>
                            <input type="text" name="items[<?= $i ?>][rejection_reason]"
                               class="form-control form-control-sm rejection-reason"
                               value="<?= h($item['rejection_reason']) ?>"
                               placeholder="Reason"
                               <?= (int)$item['qty_rejected'] === 0 ? 'style="display:none"' : '' ?>>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items-row">
                        <td colspan="9" class="text-center text-muted py-3">
                            No items yet. Click <strong>Add Item</strong> to begin.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-dark">
            <i class="bi bi-save me-1"></i>Save GRN
        </button>
        <a href="<?= $isEdit ? 'view.php?id=' . (int)$grn['grn_id'] : 'list.php' ?>"
           class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<!-- ── Row template (hidden) ───────────────────────────────────────────── -->
<template id="item-row-tpl">
    <tr class="item-row">
        <td><input type="text" name="items[__IDX__][sku_code]"
               class="form-control form-control-sm" maxlength="100" placeholder="SKU"></td>
        <td><input type="text" name="items[__IDX__][item_name]"
               class="form-control form-control-sm" maxlength="255" placeholder="Item name"></td>
        <td><input type="text" name="items[__IDX__][color]"
               class="form-control form-control-sm" maxlength="100" placeholder="Color"></td>
        <td><input type="text" name="items[__IDX__][size]"
               class="form-control form-control-sm" maxlength="50" placeholder="Size"></td>
        <td><input type="text" name="items[__IDX__][brand]"
               class="form-control form-control-sm" maxlength="100" placeholder="Brand"></td>
        <td><input type="number" name="items[__IDX__][qty_received]"
               class="form-control form-control-sm text-end qty-received" value="0" min="0"></td>
        <td><input type="number" name="items[__IDX__][qty_rejected]"
               class="form-control form-control-sm text-end qty-rejected" value="0" min="0"></td>
        <td>
            <input type="text" name="items[__IDX__][rejection_reason]"
               class="form-control form-control-sm rejection-reason"
               placeholder="Reason" style="display:none">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
(function () {
    'use strict';

    // ── PO → subcon auto-fill ──────────────────────────────────────────
    var poMap = <?= json_encode($poSubconMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    document.getElementById('po-select').addEventListener('change', function () {
        var poId     = parseInt(this.value, 10);
        var display  = document.getElementById('subcon-display');
        var hidden   = document.getElementById('subcon-id');

        if (poId && poMap[poId]) {
            display.value = poMap[poId].subcon_name;
            hidden.value  = poMap[poId].subcon_id;
        } else {
            display.value = '';
            hidden.value  = '0';
        }
    });

    // ── Dynamic row index counter ──────────────────────────────────────
    var nextIdx = <?= $isEdit ? count($existingItems) : 0 ?>;

    // ── Add row ────────────────────────────────────────────────────────
    document.getElementById('btn-add-row').addEventListener('click', function () {
        var tpl   = document.getElementById('item-row-tpl');
        var clone = tpl.content.cloneNode(true);
        var row   = clone.querySelector('tr');

        // Replace placeholder index
        row.innerHTML = row.innerHTML.split('__IDX__').join(nextIdx);
        nextIdx++;

        // Wire rejection-reason toggle for new row
        wireRow(row);

        var body = document.getElementById('items-body');

        // Remove the "no items" placeholder row if present
        var placeholder = body.querySelector('.no-items-row');
        if (placeholder) { placeholder.remove(); }

        body.appendChild(clone);
    });

    // ── Remove row ─────────────────────────────────────────────────────
    document.getElementById('items-body').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-row');
        if (!btn) { return; }
        var row = btn.closest('tr');
        var body = document.getElementById('items-body');
        row.remove();

        if (body.querySelectorAll('.item-row').length === 0) {
            var placeholder = document.createElement('tr');
            placeholder.className = 'no-items-row';
            placeholder.innerHTML = '<td colspan="9" class="text-center text-muted py-3">No items yet. Click <strong>Add Item</strong> to begin.</td>';
            body.appendChild(placeholder);
        }
    });

    // ── Rejection reason visibility ────────────────────────────────────
    function wireRow(row) {
        var qtyRejInput = row.querySelector('.qty-rejected');
        var reasonInput = row.querySelector('.rejection-reason');
        if (!qtyRejInput || !reasonInput) { return; }
        qtyRejInput.addEventListener('input', function () {
            reasonInput.style.display = parseInt(this.value, 10) > 0 ? '' : 'none';
            if (parseInt(this.value, 10) === 0) { reasonInput.value = ''; }
        });
    }

    // Wire existing rows on page load
    document.querySelectorAll('#items-body .item-row').forEach(function (row) {
        wireRow(row);
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
