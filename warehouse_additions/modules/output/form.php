<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('output', 'edit');
    $stmt = $db->prepare("SELECT * FROM output_records WHERE output_id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) { redirect('/modules/output/list.php'); }
} else {
    requirePermission('output', 'create');
    $rec = [
        'po_id'         => $_GET['po_id'] ?? '',
        'output_date'   => date('Y-m-d'),
        'completed_qty' => 0,
        'packed_qty'    => 0,
        'notes'         => '',
    ];
}

$pos = $db->query(
    "SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number"
)->fetchAll();

$pageTitle = $isEdit ? 'Edit Output Entry' : 'New Output Entry';
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $isEdit ? 'Edit Output Entry' : 'New Output Entry' ?></h5>
</div>

<div class="card border-0 shadow-sm" style="max-width:560px">
    <div class="card-body">
        <form method="post" action="action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= (int)($rec['output_id'] ?? 0) ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Purchase Order<span class="text-danger">*</span></label>
                <select name="po_id" class="form-select" required>
                    <option value="">— select PO —</option>
                    <?php foreach ($pos as $po): ?>
                    <option value="<?= (int)$po['po_id'] ?>"
                        <?= (string)$rec['po_id'] === (string)$po['po_id'] ? 'selected' : '' ?>>
                        <?= h($po['po_number']) ?><?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Output Date<span class="text-danger">*</span></label>
                <input type="date" name="output_date" class="form-control"
                       value="<?= h($rec['output_date']) ?>" required>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Completed Qty<span class="text-danger">*</span></label>
                    <input type="number" name="completed_qty" class="form-control"
                           value="<?= (int)$rec['completed_qty'] ?>" min="0" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Packed Qty</label>
                    <input type="number" name="packed_qty" class="form-control"
                           value="<?= (int)$rec['packed_qty'] ?>" min="0">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <input type="text" name="notes" class="form-control"
                       value="<?= h($rec['notes']) ?>" maxlength="255">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark">
                    <?= $isEdit ? 'Save Changes' : 'Save Entry' ?>
                </button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
