<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('fob_shipment', 'edit');
    $stmt = $db->prepare("SELECT * FROM fob_shipments WHERE fob_id = ?");
    $stmt->execute([$id]);
    $fob = $stmt->fetch();
    if (!$fob) { redirect('/modules/fob/list.php'); }

    $itemStmt = $db->prepare(
        "SELECT fi.*, po.po_number, po.style_no
         FROM fob_shipment_items fi
         LEFT JOIN purchase_orders po ON po.po_id = fi.po_id
         WHERE fi.fob_id = ?
         ORDER BY fi.fob_item_id"
    );
    $itemStmt->execute([$id]);
    $existingItems = $itemStmt->fetchAll();
} else {
    requirePermission('fob_shipment', 'create');
    $fob = [
        'booking_ref'    => '',
        'vessel_flight'  => '',
        'etd'            => '',
        'eta'            => '',
        'destination_id' => '',
        'buyer'          => '',
        'status'         => 'BOOKING',
        'remarks'        => '',
    ];
    $existingItems = [];
}

$destinations = $db->query("SELECT destination_id, destination_name FROM destinations ORDER BY destination_name")->fetchAll();
$pos          = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();

$pageTitle = $isEdit ? 'Edit FOB Shipment' : 'New FOB Shipment';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $isEdit ? 'Edit FOB Shipment' : 'New FOB Shipment' ?></h5>
</div>

<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int)($fob['fob_id'] ?? 0) ?>">

    <!-- Header fields -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header fw-semibold" style="background:#f8fafc">Shipment Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Booking Ref<span class="text-danger">*</span></label>
                    <input type="text" name="booking_ref" class="form-control"
                           value="<?= h($fob['booking_ref']) ?>" required maxlength="100"
                           placeholder="e.g. BKG-2026-001">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Vessel / Flight</label>
                    <input type="text" name="vessel_flight" class="form-control"
                           value="<?= h($fob['vessel_flight']) ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Buyer / Consignee</label>
                    <input type="text" name="buyer" class="form-control"
                           value="<?= h($fob['buyer']) ?>" maxlength="150">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">ETD</label>
                    <input type="date" name="etd" class="form-control" value="<?= h($fob['etd']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">ETA</label>
                    <input type="date" name="eta" class="form-control" value="<?= h($fob['eta']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Destination</label>
                    <select name="destination_id" class="form-select">
                        <option value="">— select —</option>
                        <?php foreach ($destinations as $d): ?>
                        <option value="<?= (int)$d['destination_id'] ?>"
                            <?= (string)$fob['destination_id'] === (string)$d['destination_id'] ? 'selected' : '' ?>>
                            <?= h($d['destination_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['BOOKING','LOADED','IN_TRANSIT','ARRIVED','CANCELLED'] as $s): ?>
                        <option value="<?= $s ?>" <?= $fob['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="<?= h($fob['remarks']) ?>" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <!-- PO Line Items -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center" style="background:#f8fafc">
            <span class="fw-semibold">Purchase Orders in This Shipment</span>
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="addItemRow()">
                <i class="bi bi-plus-lg"></i> Add PO
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35%">Purchase Order</th>
                            <th style="width:20%">Qty Shipped</th>
                            <th style="width:15%">Cartons</th>
                            <th>Notes</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                    <?php if ($existingItems): ?>
                    <?php foreach ($existingItems as $item): ?>
                    <tr class="item-row">
                        <td>
                            <select name="item_po_id[]" class="form-select form-select-sm">
                                <option value="">— PO —</option>
                                <?php foreach ($pos as $po): ?>
                                <option value="<?= (int)$po['po_id'] ?>"
                                    <?= (string)$item['po_id'] === (string)$po['po_id'] ? 'selected' : '' ?>>
                                    <?= h($po['po_number']) ?><?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="item_qty[]" class="form-control form-control-sm"
                                   value="<?= (int)$item['qty_shipped'] ?>" min="0"></td>
                        <td><input type="number" name="item_cartons[]" class="form-control form-control-sm"
                                   value="<?= (int)$item['cartons'] ?>" min="0"></td>
                        <td><input type="text" name="item_notes[]" class="form-control form-control-sm"
                                   value="<?= h($item['notes']) ?>" maxlength="255"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                            <i class="bi bi-x"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <!-- start with one blank row -->
                    <tr class="item-row">
                        <td>
                            <select name="item_po_id[]" class="form-select form-select-sm">
                                <option value="">— PO —</option>
                                <?php foreach ($pos as $po): ?>
                                <option value="<?= (int)$po['po_id'] ?>">
                                    <?= h($po['po_number']) ?><?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="0" min="0"></td>
                        <td><input type="number" name="item_cartons[]" class="form-control form-control-sm" value="0" min="0"></td>
                        <td><input type="text" name="item_notes[]" class="form-control form-control-sm" maxlength="255"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                            <i class="bi bi-x"></i></button></td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-dark">
            <?= $isEdit ? 'Save Changes' : 'Create Shipment' ?>
        </button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
// PO options HTML cached from server for new rows
const poOptions = <?= json_encode(
    '<option value="">— PO —</option>' .
    implode('', array_map(fn($po) =>
        '<option value="' . (int)$po['po_id'] . '">' .
        htmlspecialchars($po['po_number'], ENT_QUOTES) .
        ($po['style_no'] ? ' / ' . htmlspecialchars($po['style_no'], ENT_QUOTES) : '') .
        '</option>',
        $pos
    ))
) ?>;

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td><select name="item_po_id[]" class="form-select form-select-sm">${poOptions}</select></td>
        <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="0" min="0"></td>
        <td><input type="number" name="item_cartons[]" class="form-control form-control-sm" value="0" min="0"></td>
        <td><input type="text" name="item_notes[]" class="form-control form-control-sm" maxlength="255"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
            <i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
}

function removeRow(btn) {
    const rows = document.querySelectorAll('#itemsBody .item-row');
    if (rows.length > 1) {
        btn.closest('tr').remove();
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
