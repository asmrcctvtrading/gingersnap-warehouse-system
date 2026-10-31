<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db   = getDB();
$sbId = (int)($_GET['id'] ?? 0);
$isEdit = $sbId > 0;

if ($isEdit) {
    requirePermission('smart_buys', 'edit');
    $stLoad = $db->prepare("SELECT * FROM smart_buys WHERE sb_id = ?");
    $stLoad->execute([$sbId]);
    $sb = $stLoad->fetch();
    if (!$sb) {
        setFlash('error', 'SMART Buys record not found.');
        redirect('/modules/smart_buys/list.php');
    }
    if ($sb['status'] !== 'PENDING') {
        redirect('/modules/smart_buys/view.php?id=' . $sbId);
    }
} else {
    requirePermission('smart_buys', 'create');
    $sb = [
        'sb_id'          => null,
        'grn_id'         => null,
        'sku_code'       => '',
        'item_name'      => '',
        'color'          => '',
        'size'           => '',
        'brand'          => '',
        'qty'            => 1,
        'condition_code' => 'DEFECTIVE',
        'source'         => 'MANUAL',
        'status'         => 'PENDING',
        'notes'          => '',
    ];
}

// GRN dropdown
$grnList = $db->query(
    "SELECT grn_id, grn_code, grn_date FROM grn ORDER BY grn_date DESC, grn_id DESC LIMIT 200"
)->fetchAll();

$flash = getFlash();
$pageTitle = $isEdit ? 'Edit SMART Buys #' . $sbId : 'New SMART Buys Entry';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= BASE_URL ?>/modules/smart_buys/list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
    <h5 class="mb-0"><?= h($pageTitle) ?></h5>
</div>

<div class="card border-0 shadow-sm" style="max-width:720px;">
    <div class="card-body">
        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_sb">
            <?php if ($isEdit): ?>
            <input type="hidden" name="sb_id" value="<?= (int)$sb['sb_id'] ?>">
            <?php endif; ?>

            <div class="row g-3">

                <div class="col-sm-6">
                    <label class="form-label">SKU Code</label>
                    <input type="text" name="sku_code" class="form-control"
                           value="<?= h($sb['sku_code'] ?? '') ?>" maxlength="100">
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control"
                           value="<?= h($sb['brand'] ?? '') ?>" maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label">Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="item_name" class="form-control" required
                           value="<?= h($sb['item_name'] ?? '') ?>" maxlength="255">
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control"
                           value="<?= h($sb['color'] ?? '') ?>" maxlength="100">
                </div>

                <div class="col-sm-6">
                    <label class="form-label">Size</label>
                    <input type="text" name="size" class="form-control"
                           value="<?= h($sb['size'] ?? '') ?>" maxlength="50">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                    <input type="number" name="qty" class="form-control" required min="1"
                           value="<?= (int)($sb['qty'] ?? 1) ?>">
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Condition Code</label>
                    <select name="condition_code" class="form-select">
                        <?php foreach (['DEFECTIVE', 'DAMAGED', 'WRONG_ITEM', 'EXCESS'] as $cc): ?>
                        <option value="<?= h($cc) ?>" <?= ($sb['condition_code'] ?? '') === $cc ? 'selected' : '' ?>>
                            <?= h($cc) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-sm-4">
                    <label class="form-label">Source</label>
                    <?php if ($isEdit && ($sb['source'] ?? '') === 'QC_FAIL'): ?>
                    <input type="hidden" name="source" value="QC_FAIL">
                    <input type="text" class="form-control" value="QC_FAIL (auto-routed)" readonly>
                    <?php else: ?>
                    <select name="source" class="form-select">
                        <?php foreach (['QC_FAIL', 'DC_DEFECTIVE', 'MANUAL'] as $src): ?>
                        <option value="<?= h($src) ?>" <?= ($sb['source'] ?? 'MANUAL') === $src ? 'selected' : '' ?>>
                            <?= h($src) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label">GRN Reference (optional)</label>
                    <select name="grn_id" class="form-select">
                        <option value="">— None —</option>
                        <?php foreach ($grnList as $g): ?>
                        <option value="<?= (int)$g['grn_id'] ?>"
                            <?= ((int)($sb['grn_id'] ?? 0)) === (int)$g['grn_id'] ? 'selected' : '' ?>>
                            <?= h($g['grn_code']) ?> &mdash; <?= h($g['grn_date']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($isEdit): ?>
                <div class="col-12">
                    <label class="form-label">Status</label>
                    <div>
                        <span class="badge bg-secondary fs-6"><?= h($sb['status']) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= h($sb['notes'] ?? '') ?></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Entry' ?>
                    </button>
                    <a href="<?= BASE_URL ?>/modules/smart_buys/list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
