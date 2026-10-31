<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('items', 'edit');
    $stmt = $db->prepare("SELECT * FROM items WHERE item_id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) { redirect('/modules/items/list.php'); }
} else {
    requirePermission('items', 'create');
    $item = [
        'item_id'     => 0,
        'sku_code'    => '',
        'item_name'   => '',
        'description' => '',
        'color'       => '',
        'size'        => '',
        'brand'       => '',
        'hsn_code'    => '',
        'uom'         => 'PCS',
        'status'      => 'active',
    ];
}

$pageTitle = $isEdit ? 'Edit Item' : 'New Item';
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
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <i class="bi bi-box-seam me-2"></i><?= $isEdit ? 'Edit Item: ' . h($item['sku_code']) : 'New Item' ?>
    </h5>
</div>

<div class="row">
<div class="col-lg-7">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update_item' : 'create_item' ?>">
                <input type="hidden" name="id" value="<?= (int)$item['item_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        SKU Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="sku_code" class="form-control"
                           value="<?= h($item['sku_code']) ?>"
                           maxlength="100"
                           <?= $isEdit ? 'readonly' : 'required' ?>
                           placeholder="e.g. GS-TEE-BLK-S">
                    <?php if ($isEdit): ?>
                    <div class="form-text text-muted">SKU Code cannot be changed after creation.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Item Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="item_name" class="form-control"
                           value="<?= h($item['item_name']) ?>"
                           maxlength="255" required
                           placeholder="e.g. Classic Tee Black Small">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Optional details…"><?= h($item['description']) ?></textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Color</label>
                        <input type="text" name="color" class="form-control"
                               value="<?= h($item['color']) ?>"
                               maxlength="50" placeholder="e.g. Black">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">Size</label>
                        <input type="text" name="size" class="form-control"
                               value="<?= h($item['size']) ?>"
                               maxlength="50" placeholder="e.g. S, M, L, XL">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-semibold">UOM</label>
                        <input type="text" name="uom" class="form-control"
                               value="<?= h($item['uom']) ?>"
                               maxlength="20" placeholder="PCS">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">Brand</label>
                        <input type="text" name="brand" class="form-control"
                               value="<?= h($item['brand']) ?>"
                               maxlength="100" placeholder="e.g. Gingersnaps">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-semibold">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control"
                               value="<?= h($item['hsn_code']) ?>"
                               maxlength="50" placeholder="e.g. 6109">
                    </div>
                </div>

                <?php if ($isEdit): ?>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $item['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $item['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <?= $isEdit ? 'Save Changes' : 'Create Item' ?>
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
