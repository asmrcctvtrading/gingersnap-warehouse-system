<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('output', 'view');

$db = getDB();

$dateFrom = getDateParam('date_from', date('Y-m-01'));
$dateTo   = getDateParam('date_to',   date('Y-m-d'));
$poId     = (int)($_GET['po_id'] ?? 0);

$sql  = "SELECT o.*, po.po_number, po.style_no, s.subcon_name, u.username
         FROM output_records o
         JOIN purchase_orders po ON po.po_id = o.po_id
         LEFT JOIN subcons s ON s.subcon_id = po.subcon_id
         LEFT JOIN users u ON u.user_id = o.created_by
         WHERE o.output_date BETWEEN :df AND :dt";
$bind = ['df' => $dateFrom, 'dt' => $dateTo];

if ($poId) {
    $sql .= " AND o.po_id = :po";
    $bind['po'] = $poId;
}
$sql .= " ORDER BY o.output_date DESC, o.output_id DESC LIMIT 500";

$stmt = $db->prepare($sql);
$stmt->execute($bind);
$rows = $stmt->fetchAll();

$totalCompleted = array_sum(array_column($rows, 'completed_qty'));
$totalPacked    = array_sum(array_column($rows, 'packed_qty'));

$pos = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();

$pageTitle = 'Output Records';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Output Records</h5>
    <?php if (can('output', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Entry</a>
    <?php endif; ?>
</div>

<!-- KPI row -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card">
            <div class="text-muted small">Completed Pcs</div>
            <div class="fs-4 fw-bold" style="color:#1e3a5f"><?= fmtNum($totalCompleted) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#2c7a4b">
            <div class="text-muted small">Packed Pcs</div>
            <div class="fs-4 fw-bold" style="color:#2c7a4b"><?= fmtNum($totalPacked) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card" style="border-left-color:#6c757d">
            <div class="text-muted small">Entries</div>
            <div class="fs-4 fw-bold text-secondary"><?= count($rows) ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" class="d-flex flex-wrap gap-2 mb-3">
    <input type="date" name="date_from" class="form-control form-control-sm" style="width:160px"
           value="<?= h($dateFrom) ?>">
    <input type="date" name="date_to" class="form-control form-control-sm" style="width:160px"
           value="<?= h($dateTo) ?>">
    <select name="po_id" class="form-select form-select-sm" style="width:220px">
        <option value="">All POs</option>
        <?php foreach ($pos as $po): ?>
        <option value="<?= (int)$po['po_id'] ?>" <?= $poId === (int)$po['po_id'] ? 'selected' : '' ?>>
            <?= h($po['po_number']) ?><?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline-dark btn-sm"><i class="bi bi-funnel"></i> Filter</button>
    <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>PO #</th>
                    <th>Style</th>
                    <th>Subcon</th>
                    <th class="text-end">Completed</th>
                    <th class="text-end">Packed</th>
                    <th>Notes</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= fmtDate($r['output_date']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/modules/po/view.php?id=<?= (int)$r['po_id'] ?>">
                        <?= h($r['po_number']) ?>
                    </a>
                </td>
                <td class="small text-muted"><?= h($r['style_no']) ?></td>
                <td class="small"><?= h($r['subcon_name']) ?></td>
                <td class="text-end fw-semibold"><?= fmtNum($r['completed_qty']) ?></td>
                <td class="text-end"><?= fmtNum($r['packed_qty']) ?></td>
                <td class="small text-muted"><?= h($r['notes']) ?></td>
                <td class="small text-muted"><?= h($r['username']) ?></td>
                <td class="text-end">
                    <?php if (can('output', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$r['output_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('output', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete this output entry?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['output_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No output records for this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
