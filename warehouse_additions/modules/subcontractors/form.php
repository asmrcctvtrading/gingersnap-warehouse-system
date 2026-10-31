<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('subcontractors', 'edit');
    $stmt = $db->prepare("SELECT * FROM subcons WHERE subcon_id = ?");
    $stmt->execute([$id]);
    $subcon = $stmt->fetch();
    if (!$subcon) { redirect('/modules/subcontractors/list.php'); }
} else {
    requirePermission('subcontractors', 'create');
    $subcon = [
        'subcon_id'   => 0,
        'subcon_name' => '',
    ];
}

$pageTitle = $isEdit ? 'Edit Subcontractor' : 'New Subcontractor';
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
        <i class="bi bi-people me-1"></i><?= $isEdit ? 'Edit Subcontractor' : 'New Subcontractor' ?>
    </h5>
</div>

<div class="row">
<div class="col-lg-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update_subcon' : 'create_subcon' ?>">
                <input type="hidden" name="id" value="<?= (int)$subcon['subcon_id'] ?>">

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Subcon Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="subcon_name" class="form-control"
                           value="<?= h($subcon['subcon_name']) ?>"
                           maxlength="255" required
                           placeholder="e.g. Acme Sewing Co.">
                </div>

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
