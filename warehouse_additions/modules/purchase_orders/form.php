<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('purchase_orders', 'edit');
    $stmt = $db->prepare("SELECT * FROM purchase_orders WHERE po_id = ?");
    $stmt->execute([$id]);
    $po = $stmt->fetch();
    if (!$po) { redirect('/modules/purchase_orders/list.php'); }

    // Block edit on closed POs
    if (in_array($po['status'], ['COMPLETED', 'CANCELLED'], true)) {
        setFlash('error', 'Cannot edit a closed PO.');
        redirect('/modules/purchase_orders/view.php?id=' . $id);
    }
} else {
    requirePermission('purchase_orders', 'create');
    $po = [
        'po_id'               => 0,
        'po_number'           => '',
        'style_no'            => '',
        'subcon_id'           => '',
        'collection_id'       => '',
        'destination_id'      => '',
        'facility'            => 'Q.C Warehouse',
        'ticket_no'           => '',
        'po_qty'              => '',
        'po_date'             => '',
        'delivery_date'       => '',
        'status'              => 'NEW',
        'supply_type'         => 'intrastate',
        'market_type'         => 'local',
        'remarks'             => 'GOODS',
        'comments_shortfall'  => '',
        'coordinator_id'      => '',
    ];
}

// Load subcons for dropdown
$subcons = $db->query(
    "SELECT subcon_id, subcon_name FROM subcons ORDER BY subcon_name"
)->fetchAll();

$coordinators = $db->query(
    "SELECT coordinator_id, coordinator_name FROM coordinators WHERE is_active=1 ORDER BY coordinator_name"
)->fetchAll();

$pageTitle = $isEdit ? 'Edit PO: ' . $po['po_number'] : 'New Purchase Order';
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
    <a href="<?= $isEdit ? 'view.php?id=' . (int)$po['po_id'] : 'list.php' ?>"
       class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-bag-check me-1"></i>
        <?= $isEdit ? 'Edit PO: ' . h($po['po_number']) : 'New Purchase Order' ?>
    </h5>
</div>

<div class="row">
<div class="col-lg-8">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update_po' : 'create_po' ?>">
            <input type="hidden" name="id" value="<?= (int)$po['po_id'] ?>">

            <div class="row g-3">

                <!-- PO Number -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">PO Number <span class="text-danger">*</span></label>
                    <input type="text" name="po_number" class="form-control"
                           value="<?= h($po['po_number']) ?>" required maxlength="100"
                           placeholder="e.g. IBPA0444_WFR-1">
                </div>

                <!-- Style No -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Style No <span class="text-danger">*</span></label>
                    <input type="text" name="style_no" class="form-control"
                           value="<?= h($po['style_no']) ?>" required maxlength="100"
                           placeholder="e.g. IBPA0444">
                </div>

                <!-- Subcontractor -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subcontractor</label>
                    <select name="subcon_id" class="form-select">
                        <option value="">- none -</option>
                        <?php foreach ($subcons as $sc): ?>
                        <option value="<?= (int)$sc['subcon_id'] ?>"
                            <?= (string)$po['subcon_id'] === (string)$sc['subcon_id'] ? 'selected' : '' ?>>
                            <?= h($sc['subcon_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quantity -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="po_qty" class="form-control"
                           value="<?= h($po['po_qty']) ?>" required min="1"
                           placeholder="0">
                </div>

                <!-- PO Date -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">PO Date</label>
                    <input type="date" name="po_date" class="form-control"
                           value="<?= h($po['po_date']) ?>">
                </div>

                <!-- Delivery Date -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control"
                           value="<?= h($po['delivery_date']) ?>">
                </div>

                <!-- Facility -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Facility</label>
                    <input type="text" name="facility" class="form-control"
                           value="<?= h($po['facility'] ?: 'Q.C Warehouse') ?>" maxlength="50"
                           placeholder="Q.C Warehouse">
                </div>

                <!-- Ticket No -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ticket No</label>
                    <input type="text" name="ticket_no" class="form-control"
                           value="<?= h($po['ticket_no']) ?>" maxlength="100">
                </div>

                <!-- Collection ID -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Collection ID</label>
                    <input type="number" name="collection_id" class="form-control"
                           value="<?= h($po['collection_id']) ?>" min="1"
                           placeholder="Leave blank if unknown">
                </div>

                <!-- Destination ID -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Destination ID</label>
                    <input type="number" name="destination_id" class="form-control"
                           value="<?= h($po['destination_id']) ?>" min="1"
                           placeholder="Leave blank if unknown">
                </div>

                <!-- Supply Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Supply Type</label>
                    <select name="supply_type" class="form-select">
                        <option value="intrastate" <?= $po['supply_type'] === 'intrastate' ? 'selected' : '' ?>>Intrastate</option>
                        <option value="interstate"  <?= $po['supply_type'] === 'interstate'  ? 'selected' : '' ?>>Interstate</option>
                    </select>
                </div>

                <!-- Market Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Market Type</label>
                    <select name="market_type" class="form-select">
                        <option value="local"  <?= $po['market_type'] === 'local'  ? 'selected' : '' ?>>Local</option>
                        <option value="export" <?= $po['market_type'] === 'export' ? 'selected' : '' ?>>Export</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input type="text" name="remarks" class="form-control"
                           value="<?= h($po['remarks'] ?: 'GOODS') ?>" maxlength="50">
                </div>

                <!-- Coordinator -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Coordinator</label>
                    <select name="coordinator_id" class="form-select">
                        <option value="">— Select Coordinator —</option>
                        <?php foreach ($coordinators as $c): ?>
                          <option value="<?= $c['coordinator_id'] ?>" <?= ($po['coordinator_id'] ?? '') == $c['coordinator_id'] ? 'selected' : '' ?>>
                            <?= h($c['coordinator_name']) ?>
                          </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isEdit): ?>
                <!-- Status - edit only -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['NEW', 'WIP', 'COMPLETED', 'CANCELLED'] as $s): ?>
                        <option value="<?= $s ?>" <?= $po['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Comments / Shortfall -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Comments / Shortfall</label>
                    <textarea name="comments_shortfall" class="form-control" rows="3"
                              maxlength="255"><?= h($po['comments_shortfall']) ?></textarea>
                </div>

            </div><!-- /row g-3 -->

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-dark">
                    <?= $isEdit ? 'Save Changes' : 'Create PO' ?>
                </button>
                <a href="<?= $isEdit ? 'view.php?id=' . (int)$po['po_id'] : 'list.php' ?>"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div><!-- /col -->
</div><!-- /row -->

<?php include __DIR__ . '/../../includes/footer.php'; ?>
