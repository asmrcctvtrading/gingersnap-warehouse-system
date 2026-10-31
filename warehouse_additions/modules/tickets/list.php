<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('tickets', 'view');

$db = getDB();

$status    = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$validStatuses = ['OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];

$where  = [];
$params = [];

if ($status && in_array($status, $validStatuses, true)) {
    $where[]  = 't.status = ?';
    $params[] = $status;
}

if ($dateFilter) {
    $where[]  = 't.ticket_date = ?';
    $params[] = $dateFilter;
}

$sql = "SELECT t.*, po.po_number, po.style_no, s.subcon_name
        FROM tickets t
        LEFT JOIN purchase_orders po ON po.po_id = t.po_id
        LEFT JOIN subcons s ON s.subcon_id = po.subcon_id";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY t.priority_no ASC, t.ticket_date DESC LIMIT 300';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$now          = new DateTime();
$nowTime      = $now->format('H:i:s');
$todayDate    = $now->format('Y-m-d');
$past3PM      = $nowTime >= '15:00:00';

$openTodayCount = 0;
if ($past3PM) {
    $countStmt = $db->prepare(
        "SELECT COUNT(*) FROM tickets WHERE ticket_date = ? AND status IN ('OPEN','IN_PROGRESS')"
    );
    $countStmt->execute([$todayDate]);
    $openTodayCount = (int)$countStmt->fetchColumn();
}

$pageTitle = 'Tickets';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($past3PM && $openTodayCount > 0): ?>
<div class="alert alert-warning mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    Merged Report deadline (3PM) has passed &mdash; <?= $openTodayCount ?> ticket(s) still in progress.
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">Tickets</h5>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <?php if (can('tickets', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Ticket</a>
    <?php endif; ?>
</div>

<form method="get" action="list.php" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Statuses</option>
            <?php foreach ($validStatuses as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <input type="date" name="date" class="form-control form-control-sm"
               value="<?= h($dateFilter) ?>">
    </div>
    <div class="col-auto">
        <a href="list.php?date=<?= $todayDate ?>" class="btn btn-outline-secondary btn-sm">Today</a>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Priority</th>
                    <th>Ticket #</th>
                    <th>PO #</th>
                    <th>Style</th>
                    <th>Subcon</th>
                    <th>Date</th>
                    <th class="text-end">Polybag Qty</th>
                    <th>Handoff Time</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $badge = match($r['status']) {
                    'OPEN'        => 'secondary',
                    'IN_PROGRESS' => 'warning',
                    'COMPLETED'   => 'success',
                    'CANCELLED'   => 'danger',
                    default       => 'secondary',
                };
                $prioBadge = ((int)$r['priority_no'] <= 5) ? 'bg-dark' : 'bg-secondary';
                $handoffRed = $r['handoff_time']
                    && $r['ticket_date'] === $todayDate
                    && $r['handoff_time'] <= $nowTime;
            ?>
            <tr>
                <td>
                    <span class="badge <?= $prioBadge ?>"><?= (int)$r['priority_no'] ?></span>
                </td>
                <td class="fw-semibold">
                    <a href="view.php?id=<?= (int)$r['ticket_id'] ?>" class="text-decoration-none text-dark">
                        <?= h($r['ticket_no']) ?>
                    </a>
                </td>
                <td>
                    <?php if ($r['po_id']): ?>
                    <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= (int)$r['po_id'] ?>">
                        <?= h($r['po_number']) ?>
                    </a>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= h($r['style_no']) ?></td>
                <td class="small"><?= h($r['subcon_name']) ?></td>
                <td class="small"><?= h($r['ticket_date']) ?></td>
                <td class="text-end"><?= (int)$r['polybag_qty'] ?></td>
                <td class="small <?= $handoffRed ? 'text-danger fw-semibold' : '' ?>">
                    <?= h($r['handoff_time'] ?? '') ?>
                </td>
                <td><span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span></td>
                <td class="text-end">
                    <?php if (can('tickets', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$r['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('tickets', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete ticket <?= h($r['ticket_no']) ?>?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['ticket_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">No tickets found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
