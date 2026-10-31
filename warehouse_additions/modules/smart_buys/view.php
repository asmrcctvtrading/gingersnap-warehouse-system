<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('smart_buys', 'view');

$db   = getDB();
$sbId = (int)($_GET['id'] ?? 0);

if ($sbId <= 0) {
    setFlash('error', 'Invalid record ID.');
    redirect('/modules/smart_buys/list.php');
}

$st = $db->prepare(
    "SELECT sb.*, g.grn_code, g.grn_date
     FROM smart_buys sb
     LEFT JOIN grn g ON g.grn_id = sb.grn_id
     WHERE sb.sb_id = ?"
);
$st->execute([$sbId]);
$sb = $st->fetch();

if (!$sb) {
    setFlash('error', 'SMART Buys record not found.');
    redirect('/modules/smart_buys/list.php');
}

$sourceBadge = match($sb['source']) {
    'QC_FAIL'      => 'danger',
    'DC_DEFECTIVE' => 'warning',
    default        => 'secondary',
};
$statusBadge = match($sb['status']) {
    'PENDING'  => 'secondary',
    'LISTED'   => 'primary',
    'SOLD'     => 'success',
    'DISPOSED' => 'dark',
    default    => 'secondary',
};

$flash = getFlash();
$pageTitle = 'SMART Buys #' . $sbId;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
    <a href="<?= BASE_URL ?>/modules/smart_buys/list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
    <h5 class="mb-0 me-auto">SMART Buys #<?= (int)$sb['sb_id'] ?></h5>

    <?php if (can('smart_buys', 'edit')): ?>

        <?php if ($sb['status'] === 'PENDING'): ?>
        <a href="<?= BASE_URL ?>/modules/smart_buys/form.php?id=<?= (int)$sb['sb_id'] ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>

        <?php if ($sb['status'] === 'PENDING'): ?>
        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php"
              class="d-flex align-items-center gap-1">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="mark_listed">
            <input type="hidden" name="sb_id" value="<?= (int)$sb['sb_id'] ?>">
            <input type="number" name="sale_price" class="form-control form-control-sm"
                   style="width:130px;" placeholder="Sale price" step="0.01" min="0.01" required>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-tag me-1"></i>List for Sale
            </button>
        </form>
        <?php endif; ?>

        <?php if ($sb['status'] === 'LISTED'): ?>
        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php"
              class="d-flex align-items-center gap-1">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="mark_sold">
            <input type="hidden" name="sb_id" value="<?= (int)$sb['sb_id'] ?>">
            <input type="date" name="sold_date" class="form-control form-control-sm"
                   style="width:160px;" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-bag-check me-1"></i>Mark Sold
            </button>
        </form>
        <?php endif; ?>

        <?php if (in_array($sb['status'], ['PENDING', 'LISTED'], true)): ?>
        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php"
              class="d-flex align-items-center gap-1"
              onsubmit="return confirm('Dispose this item?');">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="mark_disposed">
            <input type="hidden" name="sb_id" value="<?= (int)$sb['sb_id'] ?>">
            <input type="date" name="disposed_date" class="form-control form-control-sm"
                   style="width:160px;" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-trash me-1"></i>Dispose
            </button>
        </form>
        <?php endif; ?>

        <?php if ($sb['status'] === 'PENDING'): ?>
        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php"
              class="d-inline"
              onsubmit="return confirm('Delete this SMART Buys entry?');">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_sb">
            <input type="hidden" name="sb_id" value="<?= (int)$sb['sb_id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg me-1"></i>Delete
            </button>
        </form>
        <?php endif; ?>

    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm" style="max-width:820px;">
    <div class="card-header bg-white py-2">
        <span class="fw-semibold"><i class="bi bi-tags me-2"></i>SMART Buys Detail</span>
        <span class="badge bg-<?= $statusBadge ?> ms-2"><?= h($sb['status']) ?></span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">ID</dt>
            <dd class="col-sm-8"><?= (int)$sb['sb_id'] ?></dd>

            <dt class="col-sm-4">SKU Code</dt>
            <dd class="col-sm-8"><?= h($sb['sku_code'] ?: '—') ?></dd>

            <dt class="col-sm-4">Item Name</dt>
            <dd class="col-sm-8"><?= h($sb['item_name']) ?></dd>

            <dt class="col-sm-4">Color / Size / Brand</dt>
            <dd class="col-sm-8">
                <?= h(implode(' / ', array_filter([$sb['color'], $sb['size'], $sb['brand']])) ?: '—') ?>
            </dd>

            <dt class="col-sm-4">Qty</dt>
            <dd class="col-sm-8"><?= number_format((int)$sb['qty']) ?></dd>

            <dt class="col-sm-4">Condition</dt>
            <dd class="col-sm-8">
                <span class="badge bg-secondary"><?= h($sb['condition_code']) ?></span>
            </dd>

            <dt class="col-sm-4">Source</dt>
            <dd class="col-sm-8">
                <span class="badge bg-<?= $sourceBadge ?><?= $sb['source'] === 'DC_DEFECTIVE' ? ' text-dark' : '' ?>">
                    <?= h($sb['source']) ?>
                </span>
            </dd>

            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">
                <span class="badge bg-<?= $statusBadge ?>"><?= h($sb['status']) ?></span>
            </dd>

            <dt class="col-sm-4">GRN Reference</dt>
            <dd class="col-sm-8">
                <?php if ($sb['grn_code']): ?>
                <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$sb['grn_id'] ?>">
                    <?= h($sb['grn_code']) ?>
                </a>
                (<?= h($sb['grn_date'] ?? '') ?>)
                <?php else: ?>
                —
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Sale Price</dt>
            <dd class="col-sm-8">
                <?= $sb['sale_price'] !== null ? '&#8369;' . number_format((float)$sb['sale_price'], 2) : '—' ?>
            </dd>

            <dt class="col-sm-4">Sold Date</dt>
            <dd class="col-sm-8">
                <?= $sb['sold_date'] ? date('d M Y', strtotime($sb['sold_date'])) : '—' ?>
            </dd>

            <dt class="col-sm-4">Disposed Date</dt>
            <dd class="col-sm-8">
                <?= $sb['disposed_date'] ? date('d M Y', strtotime($sb['disposed_date'])) : '—' ?>
            </dd>

            <dt class="col-sm-4">Notes</dt>
            <dd class="col-sm-8"><?= $sb['notes'] ? nl2br(h($sb['notes'])) : '—' ?></dd>

            <dt class="col-sm-4">Created At</dt>
            <dd class="col-sm-8">
                <?= $sb['created_at'] ? date('d M Y H:i', strtotime($sb['created_at'])) : '—' ?>
            </dd>
        </dl>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
