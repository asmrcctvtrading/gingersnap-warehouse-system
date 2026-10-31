<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dbased', 'view');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/dbased/list.php'); }

// ── Main PO row ────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT po.*,
            c.coordinator_name      AS coordinator_name,
            sc.subcon_name
     FROM purchase_orders po
     LEFT JOIN coordinators c  ON c.coordinator_id  = po.coordinator_id
     LEFT JOIN subcons sc      ON sc.subcon_id       = po.subcon_id
     WHERE po.po_id = ?"
);
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) {
    setFlash('error', 'PO not found.');
    redirect('/modules/dbased/list.php');
}

// ── Destination allocation ─────────────────────────────────────────────────
$pdaStmt = $db->prepare(
    "SELECT * FROM po_destination_allocations WHERE po_id = ? LIMIT 1"
);
$pdaStmt->execute([$id]);
$pda = $pdaStmt->fetch();

// ── GRN summary ────────────────────────────────────────────────────────────
$grnStmt = $db->prepare(
    "SELECT grn_id, grn_code, grn_date, total_qty_received, total_qty_rejected, status, ctrl_no
     FROM grn
     WHERE po_id = ? AND status != 'DRAFT'
     ORDER BY grn_date ASC, grn_id ASC"
);
$grnStmt->execute([$id]);
$grnRows = $grnStmt->fetchAll();

$sumReceived = 0;
$sumRejected = 0;
$lastGrnDate = null;
foreach ($grnRows as $g) {
    $sumReceived += (int)$g['total_qty_received'];
    $sumRejected += (int)$g['total_qty_rejected'];
    if ($g['grn_date'] && ($lastGrnDate === null || $g['grn_date'] > $lastGrnDate)) {
        $lastGrnDate = $g['grn_date'];
    }
}

// ── Last dispatched date (from gatepasses linked to this PO) ──────────────
$lastDispatchedAt = null;
try {
    $gpStmt = $db->prepare(
        "SELECT MAX(created_at) AS last_dispatched
         FROM gatepasses
         WHERE po_id = ?
           AND gp_status NOT IN ('OPEN', 'CREATED', 'PENDING', 'DRAFT')"
    );
    $gpStmt->execute([$id]);
    $gpRow = $gpStmt->fetch();
    $lastDispatchedAt = $gpRow['last_dispatched'] ?? null;
} catch (Throwable $e) {
    // gatepasses table may not have gp_status filter in older installs
}

// ── Dispatching priority (via ticket_no) ──────────────────────────────────
$dispatchPriority = null;
if (!empty($po['ticket_no'])) {
    try {
        $dpStmt = $db->prepare(
            "SELECT priority_rank, ctrl_number, gatepass_qty, status, remarks
             FROM dispatching_priority
             WHERE ctrl_number = ?
             LIMIT 1"
        );
        $dpStmt->execute([$po['ticket_no']]);
        $dispatchPriority = $dpStmt->fetch();
    } catch (Throwable $e) {
        // dispatching_priority table may not exist yet
    }
}

// ── TAT calculation ────────────────────────────────────────────────────────
$tatDays = null;
if ($lastDispatchedAt && $po['po_date']) {
    $tatDays = (int)round((strtotime($lastDispatchedAt) - strtotime($po['po_date'])) / 86400);
} elseif ($lastGrnDate && $po['po_date']) {
    $tatDays = (int)round((strtotime($lastGrnDate) - strtotime($po['po_date'])) / 86400);
}

// ── Derived display values ─────────────────────────────────────────────────
$statusBadge = match($po['status']) {
    'NEW'       => 'secondary',
    'WIP'       => 'warning text-dark',
    'COMPLETED' => 'success',
    'CANCELLED' => 'danger',
    default     => 'secondary',
};
$approvalBadge = ($po['approval_status'] ?? '') === 'approved' ? 'success' : 'warning text-dark';
$isClosed      = in_array($po['status'], ['COMPLETED', 'CANCELLED'], true);

$poBalance     = (int)$po['po_qty'] - $sumReceived;

$pageTitle = 'DBASED: ' . $po['po_number'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page header ─────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0">
            <i class="bi bi-table me-1"></i><?= h($po['po_number']) ?>
        </h5>
        <span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span>
        <span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status'] ?? '') ?></span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (can('dbased', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit DBASED
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/purchase_orders/view.php?id=<?= $id ?>"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bag-check me-1"></i>Full PO View
        </a>
    </div>
</div>

<!-- ── Card 1: PO Identification ──────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-info-circle me-2 text-primary"></i>PO Identification
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Coordinator</dt>
                    <dd class="col-7">
                        <?= $po['coordinator_name']
                            ? '<span class="badge bg-info text-dark">' . h($po['coordinator_name']) . '</span>'
                            : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">PO Number</dt>
                    <dd class="col-7 fw-semibold font-monospace"><?= h($po['po_number']) ?></dd>

                    <dt class="col-5 text-muted">PO Alias</dt>
                    <dd class="col-7">
                        <?= !empty($po['po_number_alias'])
                            ? h($po['po_number_alias'])
                            : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Style No.</dt>
                    <dd class="col-7"><?= h($po['style_no'] ?? '') ?></dd>

                    <dt class="col-5 text-muted">Subcontractor</dt>
                    <dd class="col-7">
                        <?= $po['subcon_name']
                            ? h($po['subcon_name'])
                            : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Collection ID</dt>
                    <dd class="col-7">
                        <?= $po['collection_id']
                            ? (int)$po['collection_id']
                            : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Facility</dt>
                    <dd class="col-7"><?= h($po['facility'] ?? '') ?></dd>

                    <dt class="col-5 text-muted">Ticket No.</dt>
                    <dd class="col-7">
                        <?= $po['ticket_no']
                            ? h($po['ticket_no'])
                            : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">PO Date</dt>
                    <dd class="col-7">
                        <?= $po['po_date'] ? h(date('d M Y', strtotime($po['po_date']))) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Delivery Date</dt>
                    <dd class="col-7">
                        <?= $po['delivery_date'] ? h(date('d M Y', strtotime($po['delivery_date']))) : '<span class="text-muted">—</span>' ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span>
                    </dd>

                    <dt class="col-5 text-muted">Approval Status</dt>
                    <dd class="col-7">
                        <span class="badge bg-<?= $approvalBadge ?>"><?= h($po['approval_status'] ?? '') ?></span>
                    </dd>

                    <dt class="col-5 text-muted">Supply Type</dt>
                    <dd class="col-7"><?= h($po['supply_type'] ?? '') ?></dd>

                    <dt class="col-5 text-muted">Market Type</dt>
                    <dd class="col-7"><?= h($po['market_type'] ?? '') ?></dd>

                    <dt class="col-5 text-muted">JO Number</dt>
                    <dd class="col-7 font-monospace">
                        <?= $po['jo_number'] ? h($po['jo_number']) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">JO Date</dt>
                    <dd class="col-7">
                        <?= $po['jo_date'] ? h(date('d M Y', strtotime($po['jo_date']))) : '<span class="text-muted">—</span>' ?>
                    </dd>

                    <dt class="col-5 text-muted">Export Destination</dt>
                    <dd class="col-7">
                        <?php
                        $dest      = $po['export_destination'] ?? '';
                        $destColor = match($dest) {
                            'LOCAL'      => 'primary',
                            'SG'         => 'success',
                            'MALAYSIA'   => 'info',
                            'INDO'       => 'warning',
                            'SAUDI'      => 'danger',
                            'SM BALIWAG' => 'secondary',
                            default      => null,
                        };
                        echo ($dest && $destColor)
                            ? '<span class="badge bg-' . $destColor . '">' . h($dest) . '</span>'
                            : '<span class="text-muted">—</span>';
                        ?>
                    </dd>

                    <dt class="col-5 text-muted">PO Qty</dt>
                    <dd class="col-7 fw-semibold"><?= number_format((int)$po['po_qty']) ?></dd>

                    <dt class="col-5 text-muted">Remarks</dt>
                    <dd class="col-7"><?= h($po['remarks'] ?? '') ?></dd>

                    <dt class="col-5 text-muted">Comments / Shortfall</dt>
                    <dd class="col-7">
                        <?= $po['comments_shortfall']
                            ? h($po['comments_shortfall'])
                            : '<span class="text-muted">—</span>' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- ── Card 2: Priority Tracking ──────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-list-ol me-2 text-warning"></i>Priority Tracking
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded p-3 text-center">
                    <div class="small text-muted mb-1">Receiving Priority No.</div>
                    <?php if ($po['receiving_priority'] !== null && $po['receiving_priority'] !== ''): ?>
                    <div class="fs-2 fw-bold text-primary"><?= (int)$po['receiving_priority'] ?></div>
                    <?php else: ?>
                    <div class="fs-4 text-muted">—</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 text-center">
                    <div class="small text-muted mb-1">Inspection Priority No.</div>
                    <?php if ($po['inspection_priority'] !== null && $po['inspection_priority'] !== ''): ?>
                    <div class="fs-2 fw-bold text-secondary"><?= (int)$po['inspection_priority'] ?></div>
                    <?php else: ?>
                    <div class="fs-4 text-muted">—</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 text-center">
                    <div class="small text-muted mb-1">Dispatching Priority</div>
                    <?php if ($dispatchPriority): ?>
                    <div class="fs-2 fw-bold text-success"><?= (int)$dispatchPriority['priority_rank'] ?></div>
                    <div class="small text-muted mt-1">
                        CTRL: <?= h($dispatchPriority['ctrl_number']) ?>
                        &nbsp;&bull;&nbsp;
                        <span class="badge bg-secondary"><?= h($dispatchPriority['status'] ?? '') ?></span>
                    </div>
                    <?php else: ?>
                    <div class="fs-4 text-muted">—</div>
                    <div class="small text-muted">No dispatching priority linked</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Card 3: Destination Allocation ─────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2 d-flex align-items-center justify-content-between">
        <span><i class="bi bi-geo-alt me-2 text-info"></i>Destination Allocation</span>
        <?php if (!$pda && can('dbased', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>&tab=allocation" class="btn btn-sm btn-outline-info">
            <i class="bi bi-plus me-1"></i>Set Allocation
        </a>
        <?php elseif (can('dbased', 'edit')): ?>
        <a href="form.php?id=<?= $id ?>&tab=allocation" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if ($pda): ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>LOCAL</th>
                        <th>SG</th>
                        <th>MALAYSIA</th>
                        <th>INDO</th>
                        <th>OTHER EXPORT</th>
                        <th>SM BALIWAG</th>
                        <th>EXPORT (FULL QTY)</th>
                        <th class="fw-bold">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                        $pdaCols = [
                            'qty_local', 'qty_sg', 'qty_malaysia', 'qty_indo',
                            'qty_other_export', 'qty_sm_baliwag', 'qty_export_full',
                        ];
                        $pdaTotal = 0;
                        foreach ($pdaCols as $col):
                            $v = $pda[$col] ?? null;
                            $pdaTotal += (int)$v;
                        ?>
                        <td><?= ($v !== null && $v !== '') ? number_format((int)$v) : '<span class="text-muted">—</span>' ?></td>
                        <?php endforeach; ?>
                        <td class="fw-bold <?= $pdaTotal === (int)$po['po_qty'] ? 'text-success' : 'text-warning' ?>">
                            <?= number_format($pdaTotal) ?>
                            <?php if ($pdaTotal !== (int)$po['po_qty']): ?>
                            <i class="bi bi-exclamation-triangle-fill ms-1 text-warning"
                               title="Total (<?= $pdaTotal ?>) does not match PO Qty (<?= (int)$po['po_qty'] ?>)"></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php if (!empty($pda['notes'])): ?>
        <div class="px-3 py-2 border-top small text-muted">
            <strong>Notes:</strong> <?= h($pda['notes']) ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="p-3 text-center text-muted small">
            No allocation set yet.
            <?php if (can('dbased', 'edit')): ?>
            <a href="form.php?id=<?= $id ?>&tab=allocation">Set it now</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Card 4: Receiving Summary (GRN) ────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-box-arrow-in-down me-2 text-success"></i>Receiving Summary
        <span class="badge bg-secondary ms-1"><?= count($grnRows) ?> GRN(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>GRN Code</th>
                    <th>GRN Date</th>
                    <th class="text-end">Received Qty</th>
                    <th class="text-end">Rejected Qty</th>
                    <th>Status</th>
                    <th>CTRL No.</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$grnRows): ?>
            <tr>
                <td colspan="6" class="text-center text-muted py-3">No GRN records yet.</td>
            </tr>
            <?php endif; ?>
            <?php foreach ($grnRows as $g): ?>
            <tr>
                <td class="font-monospace">
                    <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$g['grn_id'] ?>"
                       class="text-decoration-none">
                        <?= h($g['grn_code']) ?>
                    </a>
                </td>
                <td><?= $g['grn_date'] ? h(date('d M Y', strtotime($g['grn_date']))) : '—' ?></td>
                <td class="text-end fw-semibold text-success"><?= number_format((int)$g['total_qty_received']) ?></td>
                <td class="text-end <?= (int)$g['total_qty_rejected'] > 0 ? 'text-danger' : 'text-muted' ?>">
                    <?= number_format((int)$g['total_qty_rejected']) ?>
                </td>
                <td>
                    <span class="badge bg-<?= $g['status'] === 'POSTED' ? 'success' : 'secondary' ?>">
                        <?= h($g['status']) ?>
                    </span>
                </td>
                <td><?= $g['ctrl_no'] ? h($g['ctrl_no']) : '<span class="text-muted">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($grnRows): ?>
            <tfoot class="table-light">
                <tr class="fw-semibold">
                    <td colspan="2">Totals</td>
                    <td class="text-end text-success"><?= number_format($sumReceived) ?></td>
                    <td class="text-end text-danger"><?= number_format($sumRejected) ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr class="small">
                    <td colspan="2" class="text-muted">PO Balance</td>
                    <td class="text-end fw-bold <?= $poBalance <= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($poBalance) ?>
                    </td>
                    <td colspan="3" class="text-muted">
                        of <?= number_format((int)$po['po_qty']) ?> PO qty
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ── Card 5: TAT Calculation ────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-hourglass-split me-2 text-danger"></i>Turn Around Time (TAT)
    </div>
    <div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-md-3 text-muted">PO Date</dt>
            <dd class="col-md-9">
                <?= $po['po_date']
                    ? h(date('d M Y', strtotime($po['po_date'])))
                    : '<span class="text-muted">—</span>' ?>
            </dd>

            <dt class="col-md-3 text-muted">Last Received Date</dt>
            <dd class="col-md-9">
                <?= $lastGrnDate
                    ? h(date('d M Y', strtotime($lastGrnDate)))
                    : '<span class="text-muted">No GRN yet</span>' ?>
            </dd>

            <dt class="col-md-3 text-muted">Last Dispatched Date</dt>
            <dd class="col-md-9">
                <?php if ($lastDispatchedAt): ?>
                    <?= h(date('d M Y H:i', strtotime($lastDispatchedAt))) ?>
                    <span class="text-muted">(from gatepass)</span>
                <?php else: ?>
                    <span class="text-muted">No dispatch recorded</span>
                <?php endif; ?>
            </dd>

            <dt class="col-md-3 text-muted">Turn Around Time</dt>
            <dd class="col-md-9">
                <?php if ($tatDays !== null): ?>
                <span class="badge fs-6 bg-<?= $tatDays <= 14 ? 'success' : ($tatDays <= 30 ? 'warning text-dark' : 'danger') ?>">
                    <?= $tatDays ?> day<?= $tatDays !== 1 ? 's' : '' ?>
                </span>
                <small class="text-muted ms-2">
                    (from PO date to <?= $lastDispatchedAt ? 'last dispatch' : 'last GRN' ?>)
                </small>
                <?php else: ?>
                <span class="badge bg-secondary">Pending</span>
                <small class="text-muted ms-2">Awaiting receiving or dispatch</small>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
