<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('tickets', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT t.*, po.po_number, po.style_no, s.subcon_name
     FROM tickets t
     LEFT JOIN purchase_orders po ON po.po_id = t.po_id
     LEFT JOIN subcons s ON s.subcon_id = po.subcon_id
     WHERE t.ticket_id = ?"
);
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) { redirect('/modules/tickets/list.php'); }

$grnStmt = $db->prepare(
    "SELECT g.grn_id, g.grn_code, g.grn_date, g.status, g.total_qty_received
     FROM grn g
     WHERE g.po_id = (SELECT po_id FROM tickets WHERE ticket_id = ?)"
);
$grnStmt->execute([$id]);
$grns = $grnStmt->fetchAll();

$now       = new DateTime();
$nowTime   = $now->format('H:i:s');
$todayDate = $now->format('Y-m-d');
$past3PM   = $nowTime >= '15:00:00';

$isClosed       = in_array($t['status'], ['COMPLETED', 'CANCELLED'], true);
$handoffPassed  = $t['handoff_time'] && $t['ticket_date'] === $todayDate && $t['handoff_time'] <= $nowTime;
$handoffSoon    = $t['handoff_time'] && $t['ticket_date'] === $todayDate && !$handoffPassed
                  && (strtotime($t['handoff_time']) - time()) <= 1800;
$reportOverdue  = $past3PM && $t['ticket_date'] === $todayDate && !$isClosed;

$statusBadge = match($t['status']) {
    'OPEN'        => 'secondary',
    'IN_PROGRESS' => 'warning',
    'COMPLETED'   => 'success',
    'CANCELLED'   => 'danger',
    default       => 'secondary',
};

$pageTitle = 'Ticket: ' . $t['ticket_no'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($handoffPassed): ?>
<div class="alert alert-danger mb-3" role="alert">
    <i class="bi bi-alarm-fill me-1"></i>
    Handoff time (<?= h($t['handoff_time']) ?>) has already passed today.
</div>
<?php elseif ($handoffSoon): ?>
<div class="alert alert-warning mb-3" role="alert">
    <i class="bi bi-alarm me-1"></i>
    Handoff time (<?= h($t['handoff_time']) ?>) is approaching within 30 minutes.
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0"><?= h($t['ticket_no']) ?></h5>
        <span class="badge bg-<?= $statusBadge ?>"><?= h($t['status']) ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$isClosed && can('tickets', 'edit')): ?>
        <a href="form.php?id=<?= (int)$t['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php endif; ?>

        <a href="print.php?id=<?= (int)$t['ticket_id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i>Print
        </a>

        <?php if ($t['status'] === 'OPEN' && can('tickets', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="action"           value="update">
            <input type="hidden" name="id"               value="<?= (int)$t['ticket_id'] ?>">
            <input type="hidden" name="ticket_no"        value="<?= h($t['ticket_no']) ?>">
            <input type="hidden" name="po_id"            value="<?= (int)($t['po_id'] ?? 0) ?>">
            <input type="hidden" name="ticket_date"      value="<?= h($t['ticket_date']) ?>">
            <input type="hidden" name="qty"              value="<?= (int)$t['qty'] ?>">
            <input type="hidden" name="notes"            value="<?= h($t['notes'] ?? '') ?>">
            <input type="hidden" name="priority_no"      value="<?= (int)$t['priority_no'] ?>">
            <input type="hidden" name="handoff_time"     value="<?= h($t['handoff_time'] ?? '') ?>">
            <input type="hidden" name="polybag_qty"      value="<?= (int)$t['polybag_qty'] ?>">
            <input type="hidden" name="status"           value="IN_PROGRESS">
            <button type="submit" class="btn btn-sm btn-warning text-dark">
                <i class="bi bi-play-fill"></i> Mark In Progress
            </button>
        </form>
        <?php endif; ?>

        <?php if ($t['status'] === 'IN_PROGRESS' && can('tickets', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="action"           value="update">
            <input type="hidden" name="id"               value="<?= (int)$t['ticket_id'] ?>">
            <input type="hidden" name="ticket_no"        value="<?= h($t['ticket_no']) ?>">
            <input type="hidden" name="po_id"            value="<?= (int)($t['po_id'] ?? 0) ?>">
            <input type="hidden" name="ticket_date"      value="<?= h($t['ticket_date']) ?>">
            <input type="hidden" name="qty"              value="<?= (int)$t['qty'] ?>">
            <input type="hidden" name="notes"            value="<?= h($t['notes'] ?? '') ?>">
            <input type="hidden" name="priority_no"      value="<?= (int)$t['priority_no'] ?>">
            <input type="hidden" name="handoff_time"     value="<?= h($t['handoff_time'] ?? '') ?>">
            <input type="hidden" name="polybag_qty"      value="<?= (int)$t['polybag_qty'] ?>">
            <input type="hidden" name="status"           value="COMPLETED">
            <button type="submit" class="btn btn-sm btn-success">
                <i class="bi bi-check-lg"></i> Mark Completed
            </button>
        </form>
        <?php endif; ?>

        <?php if (in_array($t['status'], ['OPEN', 'IN_PROGRESS'], true) && can('tickets', 'edit')): ?>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Cancel this ticket?')">
            <?= csrfField() ?>
            <input type="hidden" name="action"           value="update">
            <input type="hidden" name="id"               value="<?= (int)$t['ticket_id'] ?>">
            <input type="hidden" name="ticket_no"        value="<?= h($t['ticket_no']) ?>">
            <input type="hidden" name="po_id"            value="<?= (int)($t['po_id'] ?? 0) ?>">
            <input type="hidden" name="ticket_date"      value="<?= h($t['ticket_date']) ?>">
            <input type="hidden" name="qty"              value="<?= (int)$t['qty'] ?>">
            <input type="hidden" name="notes"            value="<?= h($t['notes'] ?? '') ?>">
            <input type="hidden" name="priority_no"      value="<?= (int)$t['priority_no'] ?>">
            <input type="hidden" name="handoff_time"     value="<?= h($t['handoff_time'] ?? '') ?>">
            <input type="hidden" name="polybag_qty"      value="<?= (int)$t['polybag_qty'] ?>">
            <input type="hidden" name="status"           value="CANCELLED">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-lg"></i> Cancel Ticket
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Ticket #</dt>
                    <dd class="col-7 fw-semibold"><?= h($t['ticket_no']) ?></dd>

                    <dt class="col-5 text-muted">Priority</dt>
                    <dd class="col-7">
                        <span class="badge fs-6 <?= ((int)$t['priority_no'] <= 5) ? 'bg-dark' : 'bg-secondary' ?>">
                            <?= (int)$t['priority_no'] ?>
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">PO #</dt>
                    <dd class="col-7">
                        <?php if ($t['po_id']): ?>
                        <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= (int)$t['po_id'] ?>">
                            <?= h($t['po_number']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Style</dt>
                    <dd class="col-7"><?= $t['style_no'] ? h($t['style_no']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Subcon</dt>
                    <dd class="col-7"><?= $t['subcon_name'] ? h($t['subcon_name']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Date</dt>
                    <dd class="col-7"><?= h($t['ticket_date']) ?></dd>

                    <dt class="col-5 text-muted">Qty</dt>
                    <dd class="col-7"><?= (int)$t['qty'] ?></dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $statusBadge ?>"><?= h($t['status']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted">Handoff Time</dt>
                    <dd class="col-7 <?= $handoffPassed ? 'text-danger fw-semibold' : '' ?>">
                        <?= $t['handoff_time'] ? h($t['handoff_time']) : '<span class="text-muted">—</span>' ?>
                        <?php if ($handoffPassed): ?>
                        <span class="badge bg-danger ms-1">Passed</span>
                        <?php elseif ($handoffSoon): ?>
                        <span class="badge bg-warning text-dark ms-1">Soon</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Polybag Qty</dt>
                    <dd class="col-7"><?= (int)$t['polybag_qty'] ?></dd>

                    <dt class="col-5 text-muted">Merged Report</dt>
                    <dd class="col-7">
                        <span class="badge <?= $reportOverdue ? 'bg-warning text-dark' : 'bg-light text-dark border' ?>">
                            15:00
                        </span>
                        <?php if ($reportOverdue): ?>
                        <span class="small text-warning ms-1">Overdue</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-5 text-muted">Notes</dt>
                    <dd class="col-7"><?= $t['notes'] ? h($t['notes']) : '<span class="text-muted">—</span>' ?></dd>

                    <dt class="col-5 text-muted">Created At</dt>
                    <dd class="col-7 small text-muted"><?= h($t['created_at'] ?? '') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<h6 class="mb-2">Related GRNs</h6>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>GRN Code</th>
                    <th>Date</th>
                    <th class="text-end">Qty Received</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($grns): ?>
            <?php foreach ($grns as $g): ?>
            <tr>
                <td><?= h($g['grn_code']) ?></td>
                <td class="small"><?= h($g['grn_date']) ?></td>
                <td class="text-end"><?= (int)$g['total_qty_received'] ?></td>
                <td><span class="badge bg-secondary"><?= h($g['status']) ?></span></td>
                <td>
                    <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$g['grn_id'] ?>"
                       class="btn btn-sm btn-outline-secondary">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No related GRNs yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
