<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dispatching_priority', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// Fetch record if editing
$row = null;
if ($id > 0) {
    $row = $db->query("SELECT * FROM dispatching_priority WHERE id = $id")->fetch();
    if (!$row) {
        $_SESSION['error'] = 'Record not found';
        redirect('list.php');
    }
}

// Fetch PO options for gatepass_order
$pos = $db->query("SELECT po_id, po_number FROM purchase_orders WHERE status IN ('APPROVED','COMPLETED') ORDER BY po_number LIMIT 50")->fetchAll();

// Users for created_by
$users = $db->query("SELECT user_id, full_name, username FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll();

$pageTitle = $id ? 'Edit Dispatching Priority' : 'New Dispatching Priority';
include __DIR__ . '/../../includes/header.php';

$statuses = ['NEW' => 'New', 'DONE' => 'Done', 'CANCELLED' => 'Cancelled'];
$localExports = ['LOCAL' => 'Local', 'EXPORT' => 'Export'];
$receivedAtDC = ['Y' => 'Yes', 'N' => 'No'];
$gatepassCreated = ['DONE' => 'Done', 'PENDING' => 'Pending'];
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
                <label class="form-label">Priority Rank</label>
                <input type="number" name="priority_rank" class="form-control" value="<?= $row['priority_rank'] ?? '' ?>" placeholder="Lower number = higher priority">
                <small class="text-muted">Optional: Lower number gets priority in queue</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Ctrl Number</label>
                <input type="text" name="ctrl_number" class="form-control" value="<?= h($row['ctrl_number'] ?? '') ?>" placeholder="Ctrl number from gatepass">
            </div>

            <div class="col-md-6">
                <label class="form-label">Gatepass / PO</label>
                <select name="gatepass_order" class="form-select">
                    <option value="">Select PO</option>
                    <?php foreach ($pos as $p): ?>
                    <option value="<?= (int)$p['po_id'] ?>"<?= ($row['gatepass_order'] ?? '') == (int)$p['po_id'] ? ' selected' : '' ?>>
                        PO#<?= (int)$p['po_id'] ?> - <?= h($p['po_number']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Local / Export</label>
                <select name="local_export" class="form-select">
                    <option value="">Select Type</option>
                    <?php foreach ($localExports as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['local_export'] ?? '') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Gatepass Created</label>
                <select name="gatepass_created" class="form-select">
                    <?php foreach ($gatepassCreated as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['gatepass_created'] ?? 'PENDING') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Received at DC</label>
                <select name="received_at_dc" class="form-select">
                    <?php foreach ($receivedAtDC as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['received_at_dc'] ?? '') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <h6 class="mt-3 mb-3 border-bottom pb-2">Destination Quantities</h6>
            </div>

            <div class="col-md-4">
                <label class="form-label">Qty Local</label>
                <input type="number" name="qty_local" class="form-control" value="<?= $row['qty_local'] ?? 0 ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Qty SG</label>
                <input type="number" name="qty_sg" class="form-control" value="<?= $row['qty_sg'] ?? 0 ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Qty Malaysia</label>
                <input type="number" name="qty_malaysia" class="form-control" value="<?= $row['qty_malaysia'] ?? 0 ?>" min="0">
            </div>

            <div class="col-md-4">
                <label class="form-label">Qty Indo</label>
                <input type="number" name="qty_indo" class="form-control" value="<?= $row['qty_indo'] ?? 0 ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Qty SM Baliwag</label>
                <input type="number" name="qty_sm_baliwag" class="form-control" value="<?= $row['qty_sm_baliwag'] ?? 0 ?>" min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">Qty Export Full</label>
                <input type="number" name="qty_export_full" class="form-control" value="<?= $row['qty_export_full'] ?? 0 ?>" min="0">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ticket Number</label>
                <input type="text" name="ticket_number" class="form-control" value="<?= h($row['ticket_number'] ?? '') ?>" placeholder="Ticket reference">
            </div>
            <div class="col-md-6">
                <label class="form-label">Subcon Name</label>
                <input type="text" name="subcon_name" class="form-control" value="<?= h($row['subcon_name'] ?? '') ?>" placeholder="Subcontractor name">
            </div>

            <div class="col-md-6">
                <label class="form-label">Collection Name</label>
                <input type="text" name="collection_name" class="form-control" value="<?= h($row['collection_name'] ?? '') ?>" placeholder="Collection/batch name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $k => $v): ?>
                    <option value="<?= $k ?>"<?= ($row['status'] ?? 'NEW') === $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Remarks</label>
                <select name="remarks" class="form-select">
                    <option value=""<?= ($row['remarks'] ?? '') === '' ? ' selected' : '' ?>>No specific remark</option>
                    <option value="GOODS"<?= ($row['remarks'] ?? '') === 'GOODS' ? ' selected' : '' ?>>Goods</option>
                    <option value="RSS"<?= ($row['remarks'] ?? '') === 'RSS' ? ' selected' : '' ?>>RSS / Return</option>
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
        </div>
    </div>

    <div class="card-footer bg-transparent">
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-dark ms-2">
            <?= $id ? '<i class="bi bi-save"></i> Update' : '<i class="bi bi-plus-lg"></i> Create' ?>
        </button>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
