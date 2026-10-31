<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('subcons', 'edit');
    $stmt = $db->prepare("SELECT * FROM subcons WHERE subcon_id = ?");
    $stmt->execute([$id]);
    $subcon = $stmt->fetch();
    if (!$subcon) {
        setFlash('error', 'Subcontractor not found.');
        redirect('/modules/subcons/list.php');
    }
} else {
    requirePermission('subcons', 'create');
    $subcon = [
        'subcon_id'      => 0,
        'subcon_name'    => '',
        'contact_person' => '',
        'phone'          => '',
        'address'        => '',
        'status'         => 'active',
    ];
}

$pageTitle = $isEdit
    ? 'Edit Subcontractor: ' . $subcon['subcon_name']
    : 'New Subcontractor';
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
        <i class="bi bi-buildings me-1"></i>
        <?= $isEdit ? 'Edit Subcontractor: ' . h($subcon['subcon_name']) : 'New Subcontractor' ?>
    </h5>
</div>

<div class="row">
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_subcon">
                <input type="hidden" name="id" value="<?= (int)$subcon['subcon_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Subcon Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="subcon_name" class="form-control"
                           value="<?= h($subcon['subcon_name']) ?>"
                           maxlength="255" required
                           placeholder="e.g. Acme Sewing Co.">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control"
                           value="<?= h($subcon['contact_person'] ?? '') ?>"
                           maxlength="255"
                           placeholder="e.g. Juan dela Cruz">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= h($subcon['phone'] ?? '') ?>"
                           maxlength="50"
                           placeholder="e.g. 09171234567">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="3"
                              placeholder="Full address…"><?= h($subcon['address'] ?? '') ?></textarea>
                </div>

                <?php if ($isEdit): ?>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= ($subcon['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($subcon['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <?= $isEdit ? 'Save Changes' : 'Create Subcontractor' ?>
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
