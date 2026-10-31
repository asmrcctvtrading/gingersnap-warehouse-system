<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('rss', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Fetch record if editing
$row = null;
if ($id > 0) {
    $row = $db->query("SELECT * FROM rss WHERE rss_id = $id")->fetch();
    if (!$row) {
        $_SESSION['error'] = 'Record not found';
        redirect('list.php');
    }
}

// Fetch GRN options
$grns = $db->query("SELECT grn_id, grn_number, receiving_date FROM receiving WHERE status = 'COMPLETED' ORDER BY receiving_date DESC LIMIT 50")->fetchAll();

// Fetch PO options
$pos = $db->query("SELECT po_id, po_number FROM purchase_orders WHERE status IN ('APPROVED','COMPLETED') ORDER BY po_number LIMIT 50")->fetchAll();

// Fetch items
$items = $db->query("SELECT item_id, sku_code, item_name FROM items ORDER BY sku_code LIMIT 100")->fetchAll();

// Users for created_by
$users = $db->query("SELECT user_id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();

$pageTitle = $id ? 'Edit RSS Record' : 'New RSS Record';
include __DIR__ . '/../../includes/header.php';

$statuses = ['PENDING' => 'Pending', 'APPROVED' => 'Approved', 'COMPLETED' => 'Completed'];
$types = [
    'RETURN_VEND' => 'Return to Vendor',
    'REPAIR' => 'Repair',
    'DISPOSE' => 'Dispose',
    'OTHER' => 'Other'
];
?>
<form method="post" action="action.php" class="card border-0 shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $id ? 'update' : 'create' ?>">
    <?php if ($id): ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php endif; ?>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">RSS Type <span class="text-danger">*</span></label>
                <select name="rss_type" class="form-select" required>
                    <?php foreach ($types as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['rss_type'] ?? '') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <?php foreach ($statuses as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['status'] ?? 'PENDING') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">GRN</label>
                <select name="grn_id" class="form-select">
                    <option value="">Select GRN</option>
                    <?php foreach ($grns as $g): ?>
                    <option value="<?= (int)$g['grn_id'] ?>"<?= ($row['grn_id'] ?? '') == (int)$g['grn_id'] ? ' selected' : '' ?>>
                        GRN#<?= (int)$g['grn_id'] ?> - <?= h($g['grn_number']) ?> (<?= fmtDate($g['receiving_date']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Purchase Order</label>
                <select name="po_id" class="form-select">
                    <option value="">Select PO</option>
                    <?php foreach ($pos as $p): ?>
                    <option value="<?= (int)$p['po_id'] ?>"<?= ($row['po_id'] ?? '') == (int)$p['po_id'] ? ' selected' : '' ?>>
                        PO#<?= (int)$p['po_id'] ?> - <?= h($p['po_number']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Item</label>
                <select name="item_id" class="form-select">
                    <option value="">Select Item</option>
                    <?php foreach ($items as $i): ?>
                    <option value="<?= (int)$i['item_id'] ?>"<?= ($row['item_id'] ?? '') == (int)$i['item_id'] ? ' selected' : '' ?>>
                        <?= h($i['sku_code']) ?> - <?= h($i['item_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Created By</label>
                <select name="created_by" class="form-select">
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['user_id'] ?>"<?= ($row['created_by'] ?? $_SESSION['user_id']) == (int)$u['user_id'] ? ' selected' : '' ?>>
                        <?= h($u['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Qty Out (Outgoing) <span class="text-danger">*</span></label>
                <input type="number" name="qty_out" class="form-control" value="<?= $row['qty_out'] ?? 0 ?>" min="0" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Qty In (Incoming) <span class="text-danger">*</span></label>
                <input type="number" name="qty_in" class="form-control" value="<?= $row['qty_in'] ?? 0 ?>" min="0" required>
            </div>

            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Additional notes..."><?= h($row['remarks'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card-footer bg-transparent">
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-dark ms-2">
            <?= $id ? '<i class="bi bi-save"></i> Update' : '<i class="bi bi-plus-lg"></i> Create' ?>
        </button>
    </div>
</form>

<script>
// Auto-allocate items to GRN/PO selection
document.querySelectorAll('select[name="grn_id"], select[name="po_id"]').forEach(sel => {
    sel.addEventListener('change', () => {
        const grnId = document.querySelector('select[name="grn_id"]').value;
        const poId = document.querySelector('select[name="po_id"]').value;
        if (grnId && !poId) {
            // Optionally auto-select PO from GRN if GRN selected first
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
