<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dispatching_priority', 'view');

$db = getDB();

$status = $_GET['status'] ?? '';
$ctrl = $_GET['ctrl'] ?? '';
$where = [];
$params = [];

if ($status) {
    $where[] = "dp.status = ?";
    $params[] = $status;
}
if ($ctrl) {
    $where[] = "dp.ctrl_number LIKE ?";
    $params[] = "%$ctrl%";
}

$sql = "SELECT dp.*,
        u.full_name AS created_by_name
        FROM dispatching_priority dp
        LEFT JOIN users u ON u.user_id = dp.created_by";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY dp.priority_rank ASC, dp.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Stats
$totalNew = (int) $db->query("SELECT COUNT(*) FROM dispatching_priority WHERE status = 'NEW'")->fetchColumn();
$totalDone = (int) $db->query("SELECT COUNT(*) FROM dispatching_priority WHERE status = 'DONE'")->fetchColumn();
$totalCancelled = (int) $db->query("SELECT COUNT(*) FROM dispatching_priority WHERE status = 'CANCELLED'")->fetchColumn();

$pageTitle = 'Dispatching Priority';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Dispatching Priority Queue</h5>
    <?php if (can('dispatching_priority', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Entry</a>
    <?php endif; ?>
</div>

<div class="alert alert-info small mb-3">
    <strong>Summary:</strong>
    <span class="text-info me-3">New: <?= number_format($totalNew) ?></span>
    <span class="text-success me-3">Done: <?= number_format($totalDone) ?></span>
    <span class="text-muted">Cancelled: <?= number_format($totalCancelled) ?></span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" class="d-flex flex-wrap gap-2 mb-3">
            <select name="status" class="form-select form-select-sm" style="width:150px">
                <option value="">All Statuses</option>
                <?php foreach (['NEW','DONE','CANCELLED'] as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="ctrl" class="form-control form-control-sm" placeholder="Ctrl Number" value="<?= h($ctrl) ?>" style="width:200px">
            <button type="submit" class="btn btn-outline-dark btn-sm"><i class="bi bi-funnel"></i> Filter</button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Ctrl #</th>
                        <th>PO / Gatepass</th>
                        <th>Local</th>
                        <th>SG</th>
                        <th>Malaysia</th>
                        <th>Indo</th>
                        <th>SM Baliwag</th>
                        <th>Export</th>
                        <th>Status</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $badge = match($r['status']) {
                        'NEW' => 'info',
                        'DONE' => 'success',
                        'CANCELLED' => 'danger',
                        default => 'secondary',
                    };
                ?>
                <tr>
                    <td class="text-center">
                        <?php if ($r['priority_rank']): ?>
                        <span class="badge bg-primary rounded-pill"><?= (int)$r['priority_rank'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold"><?= h($r['ctrl_number'] ?? '-') ?></td>
                    <td><?php if ($r['gatepass_order']): ?><span class="small fw-semibold"><?= h($r['gatepass_order']) ?></span><?php endif; ?><?php if ($r['ticket_number']): ?><br><span class="small text-muted"><?= h($r['ticket_number']) ?></span><?php endif; ?><?php if (!$r['gatepass_order'] && !$r['ticket_number']): ?>—<?php endif; ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_local']) ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_sg']) ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_malaysia']) ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_indo']) ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_sm_baliwag']) ?></td>
                    <td class="text-end text-muted"><?= number_format($r['qty_export_full']) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span></td>
                    <td class="small text-muted"><?= h($r['created_by_name'] ?? '-') ?></td>
                    <td class="text-end">
                        <?php if (can('dispatching_priority', 'edit')): ?>
                        <a href="form.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if (can('dispatching_priority', 'delete')): ?>
                        <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete this priority entry?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                <tr><td colspan="12" class="text-center text-muted py-4">No dispatching priority records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
