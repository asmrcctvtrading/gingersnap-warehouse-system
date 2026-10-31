<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('gatepass', 'edit');

    $st = $db->prepare("SELECT * FROM gatepasses WHERE gp_id = ?");
    $st->execute([$id]);
    $gp = $st->fetch();

    if (!$gp) {
        setFlash('error', 'Gate pass not found.');
        redirect('/modules/gatepass/list.php');
    }
    if (!in_array($gp['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
        setFlash('error', 'Only OPEN or PENDING gate passes can be edited.');
        redirect('/modules/gatepass/view.php?id=' . $id);
    }

    $stItems = $db->prepare(
        "SELECT * FROM gatepass_items WHERE gp_id = ? ORDER BY gpi_id ASC"
    );
    $stItems->execute([$id]);
    $existingItems = $stItems->fetchAll();

} else {
    requirePermission('gatepass', 'create');
    $gp = [
        'gp_id'           => 0,
        'gp_code'         => '',
        'gp_type'         => 'STOCK_TRANSFER',
        'gp_status'       => 'PENDING',
        'to_party'        => '',
        'purpose'         => '',
        'reference'       => '',
        'rss_reason_code' => '',
        'vendor_code'     => '',
        'returnable_date' => '',
        'po_id'           => null,
        'grn_id'          => null,
    ];
    $existingItems = [];
}

// Ensure new keys exist for edit mode (after migration columns added)
$gp['rss_reason_code'] = $gp['rss_reason_code'] ?? '';
$gp['vendor_code']     = $gp['vendor_code']     ?? '';
$gp['returnable_date'] = $gp['returnable_date'] ?? '';
$gp['po_id']           = $gp['po_id']           ?? null;
$gp['grn_id']          = $gp['grn_id']          ?? null;

// ── Load items for SELECT dropdown ─────────────────────────────────────────
$itemsList = $db->query(
    "SELECT item_id, sku_code, item_name FROM items ORDER BY sku_code ASC"
)->fetchAll();

// ── Load locations grouped by zone ─────────────────────────────────────────
$locationsList = $db->query(
    "SELECT location_id, location_code, zone
     FROM warehouse_locations
     WHERE status = 'ACTIVE'
     ORDER BY zone ASC, location_code ASC"
)->fetchAll();

$locationsByZone = [];
foreach ($locationsList as $loc) {
    $zone = trim($loc['zone'] ?? '') ?: 'Unassigned';
    $locationsByZone[$zone][] = $loc;
}

// ── Load purchase orders for STOCK_TRANSFER ─────────────────────────────────
$poList = $db->query(
    "SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number ASC"
)->fetchAll();

// ── Load GRNs for RETURNABLE ────────────────────────────────────────────────
$grnList = $db->query(
    "SELECT grn_id, grn_code, grn_date FROM grn ORDER BY grn_code ASC"
)->fetchAll();

// RSS cutoff: warn if current server time is past 14:00
$isPastCutoff = (int)date('H') >= 14;

$pageTitle = $isEdit
    ? 'Edit Gate Pass: ' . $gp['gp_code']
    : 'New Gate Pass';

include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger') ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= $isEdit ? 'view.php?id=' . (int)$gp['gp_id'] : 'list.php' ?>"
       class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-truck me-1"></i>
        <?= $isEdit ? 'Edit Gate Pass: ' . h($gp['gp_code']) : 'New Gate Pass' ?>
    </h5>
</div>

<!-- RSS cutoff warning (shown via JS when RETURNABLE type is selected past 14:00) -->
<div id="rss_cutoff_warning" class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="display:none!important">
    <i class="bi bi-clock-fill me-1"></i>
    <strong>RSS cutoff:</strong> Returnable gate passes must be created before 2PM.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<form method="post" action="action.php" id="gp-form">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_gatepass">
    <input type="hidden" name="id" value="<?= (int)$gp['gp_id'] ?>">

    <!-- ── Header ────────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-info-circle me-2"></i>Gate Pass Details
        </div>
        <div class="card-body">
            <div class="row g-3">

                <!-- GP Code -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">GP Code</label>
                    <input type="text" class="form-control"
                           value="<?= $isEdit ? h($gp['gp_code']) : 'Auto-generated' ?>"
                           readonly style="background:#f8f9fa;">
                </div>

                <!-- GP Type -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="gp_type" class="form-select" required id="gp_type">
                        <optgroup label="Phase 2">
                            <option value="STOCK_TRANSFER" <?= $gp['gp_type'] === 'STOCK_TRANSFER' ? 'selected' : '' ?>>STOCK_TRANSFER</option>
                            <option value="RETURNABLE"     <?= $gp['gp_type'] === 'RETURNABLE'     ? 'selected' : '' ?>>RETURNABLE</option>
                        </optgroup>
                        <optgroup label="Legacy">
                            <?php foreach (['OUTGOING','INCOMING','RETURN','TRANSFER','INTERNAL_CONSUMPTION'] as $t): ?>
                            <option value="<?= $t ?>" <?= $gp['gp_type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- To/From Party -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">To/From Party</label>
                    <input type="text" name="to_party" class="form-control"
                           value="<?= h($gp['to_party'] ?? '') ?>"
                           maxlength="255"
                           placeholder="Recipient or sender party name">
                </div>

                <!-- Purpose -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Purpose</label>
                    <input type="text" name="purpose" class="form-control"
                           value="<?= h($gp['purpose'] ?? '') ?>"
                           maxlength="255"
                           placeholder="Reason for this gate pass">
                </div>

                <!-- Reference -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reference</label>
                    <input type="text" name="reference" id="reference" class="form-control"
                           value="<?= h($gp['reference'] ?? '') ?>"
                           maxlength="255"
                           placeholder="Auto-filled from PO or GRN; or enter manually">
                </div>

                <!-- PO Dropdown (STOCK_TRANSFER only) -->
                <div class="col-md-6" id="po_section" style="display:none">
                    <label class="form-label fw-semibold">Purchase Order</label>
                    <select name="po_id" id="po_select" class="form-select">
                        <option value="">— Select PO —</option>
                        <?php foreach ($poList as $po): ?>
                        <option value="<?= (int)$po['po_id'] ?>"
                                data-ponumber="<?= h($po['po_number']) ?>"
                            <?= (int)($gp['po_id'] ?? 0) === (int)$po['po_id'] ? 'selected' : '' ?>>
                            <?= h($po['po_number'] . ' / ' . ($po['style_no'] ?? '')) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- GRN Dropdown (RETURNABLE only) -->
                <div class="col-md-6" id="grn_section" style="display:none">
                    <label class="form-label fw-semibold">GRN</label>
                    <select name="grn_id" id="grn_select" class="form-select">
                        <option value="">— Select GRN —</option>
                        <?php foreach ($grnList as $grn): ?>
                        <option value="<?= (int)$grn['grn_id'] ?>"
                                data-grncode="<?= h($grn['grn_code']) ?>"
                            <?= (int)($gp['grn_id'] ?? 0) === (int)$grn['grn_id'] ? 'selected' : '' ?>>
                            <?= h($grn['grn_code'] . ' (' . ($grn['grn_date'] ?? '') . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- RSS Fields (RETURNABLE only) -->
            <div id="rss_section" style="display:none">
                <hr class="my-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">RSS Reason Code</label>
                        <input type="text" name="rss_reason_code" class="form-control"
                               value="<?= h($gp['rss_reason_code'] ?? '') ?>"
                               maxlength="100"
                               placeholder="e.g. DEFECTIVE, WRONG_ITEM, EXCESS">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Vendor Code</label>
                        <input type="text" name="vendor_code" class="form-control"
                               value="<?= h($gp['vendor_code'] ?? '') ?>"
                               maxlength="100"
                               placeholder="Vendor / subcontractor code">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Returnable Date <span class="text-danger">*</span></label>
                        <input type="date" name="returnable_date" id="returnable_date" class="form-control"
                               value="<?= h($gp['returnable_date'] ?? '') ?>">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Line Items ─────────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold py-2 d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-2"></i>Items</span>
            <button type="button" class="btn btn-outline-dark btn-sm" id="btn-add-row">
                <i class="bi bi-plus-lg me-1"></i>Add Item
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="items-table">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:240px">Item</th>
                        <th style="min-width:200px">From Location</th>
                        <th style="min-width:100px" class="text-end">Qty (Cartons)</th>
                        <th style="min-width:160px">Notes</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                <?php if ($existingItems): ?>
                    <?php foreach ($existingItems as $i => $row): ?>
                    <tr class="item-row">
                        <td>
                            <select name="items[<?= $i ?>][item_id]" class="form-select form-select-sm">
                                <option value="">— Select Item —</option>
                                <?php foreach ($itemsList as $itm): ?>
                                <option value="<?= (int)$itm['item_id'] ?>"
                                    <?= (int)$itm['item_id'] === (int)$row['item_id'] ? 'selected' : '' ?>>
                                    <?= h($itm['sku_code'] . ' — ' . $itm['item_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="items[<?= $i ?>][from_location_id]" class="form-select form-select-sm">
                                <option value="">— Select Location —</option>
                                <?php foreach ($locationsByZone as $zoneName => $locs): ?>
                                <optgroup label="<?= h($zoneName) ?>">
                                    <?php foreach ($locs as $loc): ?>
                                    <option value="<?= (int)$loc['location_id'] ?>"
                                        <?= (int)$loc['location_id'] === (int)$row['from_location_id'] ? 'selected' : '' ?>>
                                        <?= h($loc['location_code']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[<?= $i ?>][qty_cartons]"
                                   class="form-control form-control-sm text-end"
                                   value="<?= (int)$row['qty_cartons'] ?>" min="1">
                        </td>
                        <td>
                            <input type="text" name="items[<?= $i ?>][notes]"
                                   class="form-control form-control-sm"
                                   value="<?= h($row['notes'] ?? '') ?>"
                                   maxlength="255" placeholder="Notes">
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
                        <td colspan="5" class="text-center text-muted py-3">
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
            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Gate Pass' ?>
        </button>
        <a href="<?= $isEdit ? 'view.php?id=' . (int)$gp['gp_id'] : 'list.php' ?>"
           class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<!-- ── Row template (hidden — PHP renders options into it server-side) ──── -->
<template id="item-row-tpl">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][item_id]" class="form-select form-select-sm">
                <option value="">— Select Item —</option>
                <?php foreach ($itemsList as $itm): ?>
                <option value="<?= (int)$itm['item_id'] ?>">
                    <?= h($itm['sku_code'] . ' — ' . $itm['item_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <select name="items[__IDX__][from_location_id]" class="form-select form-select-sm">
                <option value="">— Select Location —</option>
                <?php foreach ($locationsByZone as $zoneName => $locs): ?>
                <optgroup label="<?= h($zoneName) ?>">
                    <?php foreach ($locs as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>">
                        <?= h($loc['location_code']) ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="number" name="items[__IDX__][qty_cartons]"
                   class="form-control form-control-sm text-end" value="1" min="1">
        </td>
        <td>
            <input type="text" name="items[__IDX__][notes]"
                   class="form-control form-control-sm" maxlength="255" placeholder="Notes">
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

    var nextIdx     = <?= $isEdit ? count($existingItems) : 0 ?>;
    var isPastCutoff = <?= $isPastCutoff ? 'true' : 'false' ?>;

    // ── Type-dependent field visibility ──────────────────────────────────
    function updateTypeFields() {
        var type         = document.getElementById('gp_type').value;
        var isStock      = type === 'STOCK_TRANSFER';
        var isReturnable = type === 'RETURNABLE';

        document.getElementById('po_section').style.display  = isStock      ? ''     : 'none';
        document.getElementById('grn_section').style.display = isReturnable ? ''     : 'none';
        document.getElementById('rss_section').style.display = isReturnable ? ''     : 'none';

        var cutoffEl = document.getElementById('rss_cutoff_warning');
        cutoffEl.style.setProperty('display', (isReturnable && isPastCutoff) ? 'block' : 'none', 'important');

        // Returnable Date: required only for RETURNABLE
        var rdInput = document.getElementById('returnable_date');
        if (rdInput) {
            rdInput.required = isReturnable;
        }
    }

    document.getElementById('gp_type').addEventListener('change', updateTypeFields);
    updateTypeFields();

    // ── PO auto-fill reference ────────────────────────────────────────────
    document.getElementById('po_select').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        document.getElementById('reference').value = opt.dataset.ponumber || '';
    });

    // ── GRN auto-fill reference ───────────────────────────────────────────
    document.getElementById('grn_select').addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        document.getElementById('reference').value = opt.dataset.grncode || '';
    });

    // ── Add item row ──────────────────────────────────────────────────────
    document.getElementById('btn-add-row').addEventListener('click', function () {
        var tpl   = document.getElementById('item-row-tpl');
        var clone = tpl.content.cloneNode(true);
        var row   = clone.querySelector('tr');

        row.innerHTML = row.innerHTML.split('__IDX__').join(String(nextIdx));
        nextIdx++;

        var body        = document.getElementById('items-body');
        var placeholder = body.querySelector('.no-items-row');
        if (placeholder) { placeholder.remove(); }

        body.appendChild(clone);
    });

    // ── Remove item row ───────────────────────────────────────────────────
    document.getElementById('items-body').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-row');
        if (!btn) { return; }

        var row  = btn.closest('tr');
        var body = document.getElementById('items-body');
        row.remove();

        if (body.querySelectorAll('.item-row').length === 0) {
            var ph = document.createElement('tr');
            ph.className = 'no-items-row';
            ph.innerHTML = '<td colspan="5" class="text-center text-muted py-3">No items yet. Click <strong>Add Item</strong> to begin.</td>';
            body.appendChild(ph);
        }
    });
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
