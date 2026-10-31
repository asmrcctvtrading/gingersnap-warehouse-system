<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('wip', 'view');

$db = getDB();

// ─── Date filter ─────────────────────────────────────────────────────────────
$dateFrom = getDateParam('date_from', date('Y-m-01'));
$dateTo   = getDateParam('date_to',   date('Y-m-d'));

// ─── Transaction log filters ──────────────────────────────────────────────────
$filterStage     = in_array($_GET['stage'] ?? '', ['RECEIVING','QC','DISPATCHING'], true)
                   ? $_GET['stage'] : '';
$filterDirection = in_array($_GET['dir'] ?? '', ['IN','OUT'], true)
                   ? $_GET['dir'] : '';
$perPage = 25;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// ─── WIP Matrix computation ───────────────────────────────────────────────────
// Returns ['bwip'=>int, 'new'=>int, 'output'=>int, 'ewip'=>int] for a given stage.
// BWIP: from snapshot on (date_from - 1 day), or fallback sum of all transactions before date_from.
function getStageMatrix(PDO $db, string $stage, string $dateFrom, string $dateTo): array
{
    $snapDate = date('Y-m-d', strtotime($dateFrom . ' -1 day'));

    $snapStmt = $db->prepare(
        "SELECT ewip_qty FROM wip_period_snapshots
         WHERE snapshot_date = ? AND stage = ? LIMIT 1"
    );
    $snapStmt->execute([$snapDate, $stage]);
    $snap = $snapStmt->fetch();

    if ($snap !== false) {
        $bwip = (int)$snap['ewip_qty'];
    } else {
        $fbStmt = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN direction='IN' THEN qty ELSE -qty END), 0)
             FROM wip_transactions
             WHERE stage = ? AND transaction_date < ?"
        );
        $fbStmt->execute([$stage, $dateFrom]);
        $bwip = (int)$fbStmt->fetchColumn();
    }

    $periodStmt = $db->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN direction='IN'  THEN qty ELSE 0 END), 0) AS new_qty,
            COALESCE(SUM(CASE WHEN direction='OUT' THEN qty ELSE 0 END), 0) AS out_qty
         FROM wip_transactions
         WHERE stage = ? AND transaction_date BETWEEN ? AND ?"
    );
    $periodStmt->execute([$stage, $dateFrom, $dateTo]);
    $period = $periodStmt->fetch();

    $new    = (int)$period['new_qty'];
    $output = (int)$period['out_qty'];
    $ewip   = $bwip + $new - $output;

    return compact('bwip', 'new', 'output', 'ewip');
}

// Compute RECEIVING and DISPATCHING independently
$matrix = [];
$matrix['RECEIVING']   = getStageMatrix($db, 'RECEIVING',   $dateFrom, $dateTo);
$matrix['DISPATCHING'] = getStageMatrix($db, 'DISPATCHING', $dateFrom, $dateTo);

// QC: BWIP is auto-derived = RECEIVING EWIP of same period; NEW/OUTPUT from QC transactions
$qcBwip   = $matrix['RECEIVING']['ewip'];
$qcPeriod = $db->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN direction='IN'  THEN qty ELSE 0 END), 0) AS new_qty,
        COALESCE(SUM(CASE WHEN direction='OUT' THEN qty ELSE 0 END), 0) AS out_qty
     FROM wip_transactions
     WHERE stage = 'QC' AND transaction_date BETWEEN ? AND ?"
);
$qcPeriod->execute([$dateFrom, $dateTo]);
$qcRow = $qcPeriod->fetch();
$qcNew    = (int)$qcRow['new_qty'];
$qcOutput = (int)$qcRow['out_qty'];
$qcEwip   = $qcBwip + $qcNew - $qcOutput;
$matrix['QC'] = ['bwip' => $qcBwip, 'new' => $qcNew, 'output' => $qcOutput, 'ewip' => $qcEwip];

// ─── Transaction log query ────────────────────────────────────────────────────
$where  = ['t.transaction_date BETWEEN :df AND :dt'];
$params = ['df' => $dateFrom, 'dt' => $dateTo];

if ($filterStage !== '') {
    $where[]         = 't.stage = :stage';
    $params['stage'] = $filterStage;
}
if ($filterDirection !== '') {
    $where[]       = 't.direction = :dir';
    $params['dir'] = $filterDirection;
}

$whereClause = implode(' AND ', $where);

$countStmt = $db->prepare(
    "SELECT COUNT(*) FROM wip_transactions t WHERE {$whereClause}"
);
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listStmt = $db->prepare(
    "SELECT t.*, u.username
     FROM wip_transactions t
     LEFT JOIN users u ON u.user_id = t.created_by
     WHERE {$whereClause}
     ORDER BY t.transaction_date DESC, t.id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$listStmt->execute($params);
$txRows = $listStmt->fetchAll();

// ─── Page ─────────────────────────────────────────────────────────────────────
$pageTitle = 'WIP Matrix';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php
// Negative EWIP warning banners
$stageLabels = [
    'RECEIVING'   => 'Receiving',
    'QC'          => 'QC',
    'DISPATCHING' => 'Dispatching',
];
foreach ($matrix as $stageName => $data):
    if ($data['ewip'] < 0):
?>
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-start mb-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0" style="font-size:18px"></i>
    <div>
        <strong><?= h($stageLabels[$stageName]) ?> EWIP is negative
        (<?= fmtNum($data['ewip']) ?>)</strong>
        &mdash; check for unlogged
        <?= $stageName === 'DISPATCHING' ? 'dispatching IN' : 'receiving' ?> entries.
    </div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; endforeach; ?>

<!-- Page header -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0"><i class="bi bi-bar-chart-steps me-1"></i>WIP Matrix</h5>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (can('wip', 'create')): ?>
        <button type="button" class="btn btn-dark btn-sm"
                data-bs-toggle="modal" data-bs-target="#wipEntryModal"
                id="btnNewEntry">
            <i class="bi bi-plus-lg me-1"></i>Log WIP Entry
        </button>
        <form method="post" action="action.php" class="d-inline"
              onsubmit="return confirm('Save current EWIP values as a snapshot for <?= h(date('d M Y', strtotime($dateTo))) ?>?')">
            <?= csrfField() ?>
            <input type="hidden" name="action"        value="save_snapshot">
            <input type="hidden" name="snapshot_date" value="<?= h($dateTo) ?>">
            <input type="hidden" name="date_from"     value="<?= h($dateFrom) ?>">
            <input type="hidden" name="date_to"       value="<?= h($dateTo) ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-camera me-1"></i>Save Snapshot
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Date filter -->
<div class="card border-0 shadow-sm p-3 mb-3">
    <form method="get" id="filterForm">
        <input type="hidden" name="stage" value="<?= h($filterStage) ?>">
        <input type="hidden" name="dir"   value="<?= h($filterDirection) ?>">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group btn-group-sm" role="group" aria-label="Quick ranges">
                <button type="button" class="btn btn-outline-secondary" id="btnThisMonth">This Month</button>
                <button type="button" class="btn btn-outline-secondary" id="btnLastMonth">Last Month</button>
                <button type="button" class="btn btn-outline-secondary" id="btnToday">Today</button>
            </div>
            <input type="date" name="date_from" id="inputDateFrom"
                   class="form-control form-control-sm" style="width:160px"
                   value="<?= h($dateFrom) ?>">
            <span class="text-muted small">to</span>
            <input type="date" name="date_to" id="inputDateTo"
                   class="form-control form-control-sm" style="width:160px"
                   value="<?= h($dateTo) ?>">
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="bi bi-funnel me-1"></i>Apply
            </button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>
</div>

<!-- ── WIP Matrix Widget ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow mb-4">
    <div class="card-header py-2" style="background:#1e293b">
        <span class="fw-semibold text-white">
            <i class="bi bi-grid-3x3 me-1"></i>
            WIP Matrix &mdash;
            <?= h(date('d M Y', strtotime($dateFrom))) ?> &ndash;
            <?= h(date('d M Y', strtotime($dateTo))) ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table style="width:100%; border-collapse:collapse; min-width:560px">
                <thead>
                    <tr style="background:#334155; color:#cbd5e1; font-size:11px; text-transform:uppercase; letter-spacing:.06em;">
                        <th style="padding:10px 20px; font-weight:600; width:180px; border-right:1px solid #475569;">Stage</th>
                        <th style="padding:10px 20px; font-weight:600; text-align:right; width:160px; border-right:1px solid #475569;">BWIP</th>
                        <th style="padding:10px 20px; font-weight:600; text-align:right; width:160px; border-right:1px solid #475569;">NEW</th>
                        <th style="padding:10px 20px; font-weight:600; text-align:right; width:160px; border-right:1px solid #475569;">OUTPUT</th>
                        <th style="padding:10px 20px; font-weight:600; text-align:right; width:160px;">EWIP</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stageRowColors = [
                    'RECEIVING'   => '#1e3a5f',
                    'QC'          => '#5c3d00',
                    'DISPATCHING' => '#1a4731',
                ];
                foreach ($matrix as $stageName => $data):
                    $ewip = $data['ewip'];
                    if ($ewip < 0) {
                        $ewipBg    = '#dc3545';
                        $ewipColor = '#fff';
                    } elseif ($ewip > 0) {
                        $ewipBg    = '#198754';
                        $ewipColor = '#fff';
                    } else {
                        $ewipBg    = '#6c757d';
                        $ewipColor = '#fff';
                    }
                ?>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:18px 20px; background:#f8fafc; border-right:1px solid #e2e8f0;">
                        <span style="display:inline-flex; align-items:center; gap:6px; background:<?= $stageRowColors[$stageName] ?>; color:#fff; border-radius:5px; padding:3px 12px; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase;">
                            <?= h($stageName) ?>
                        </span>
                    </td>
                    <td style="padding:18px 20px; text-align:right; font-size:24px; font-weight:800; color:#334155; border-right:1px solid #e2e8f0; font-variant-numeric:tabular-nums;">
                        <?= fmtNum($data['bwip']) ?>
                    </td>
                    <td style="padding:18px 20px; text-align:right; font-size:24px; font-weight:800; color:#198754; border-right:1px solid #e2e8f0; font-variant-numeric:tabular-nums;">
                        <?= fmtNum($data['new']) ?>
                    </td>
                    <td style="padding:18px 20px; text-align:right; font-size:24px; font-weight:800; color:#dc3545; border-right:1px solid #e2e8f0; font-variant-numeric:tabular-nums;">
                        <?= fmtNum($data['output']) ?>
                    </td>
                    <td style="padding:18px 20px; text-align:right; background:<?= $ewipBg ?>; font-variant-numeric:tabular-nums;">
                        <span style="font-size:26px; font-weight:900; color:<?= $ewipColor ?>; display:block; line-height:1.1;">
                            <?= fmtNum($ewip) ?>
                        </span>
                        <?php if ($ewip < 0): ?>
                        <span style="font-size:10px; color:rgba(255,255,255,.85); font-weight:600; text-transform:uppercase; letter-spacing:.06em;">
                            NEGATIVE
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer py-2" style="background:#f8fafc; font-size:11px; color:#64748b;">
        <i class="bi bi-info-circle me-1"></i>
        EWIP = BWIP + NEW &minus; OUTPUT &nbsp;&middot;&nbsp;
        QC BWIP is auto-derived from RECEIVING EWIP &nbsp;&middot;&nbsp;
        RECEIVING OUT auto-creates a matching QC IN.
    </div>
</div>

<!-- ── Transaction Log ────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 text-muted fw-semibold">
        <i class="bi bi-list-ul me-1"></i>Transaction Log
    </h6>
    <small class="text-muted">
        <?= number_format($totalRows) ?> record<?= $totalRows !== 1 ? 's' : '' ?>
    </small>
</div>

<!-- Transaction log sub-filters -->
<form method="get" class="d-flex flex-wrap gap-2 mb-2 align-items-center">
    <input type="hidden" name="date_from" value="<?= h($dateFrom) ?>">
    <input type="hidden" name="date_to"   value="<?= h($dateTo) ?>">
    <select name="stage" class="form-select form-select-sm" style="width:160px">
        <option value="">All Stages</option>
        <?php foreach (['RECEIVING','QC','DISPATCHING'] as $s): ?>
        <option value="<?= $s ?>" <?= $filterStage === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir" class="form-select form-select-sm" style="width:130px">
        <option value="">All Directions</option>
        <option value="IN"  <?= $filterDirection === 'IN'  ? 'selected' : '' ?>>IN</option>
        <option value="OUT" <?= $filterDirection === 'OUT' ? 'selected' : '' ?>>OUT</option>
    </select>
    <button type="submit" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-funnel"></i> Filter
    </button>
    <?php if ($filterStage !== '' || $filterDirection !== ''): ?>
    <a href="list.php?date_from=<?= h($dateFrom) ?>&date_to=<?= h($dateTo) ?>"
       class="btn btn-outline-secondary btn-sm">Clear</a>
    <?php endif; ?>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle" style="font-size:12px;">
            <thead class="table-light">
                <tr>
                    <th style="white-space:nowrap">Date</th>
                    <th>Stage</th>
                    <th>Dir</th>
                    <th class="text-end">Qty</th>
                    <th>CTRL No.</th>
                    <th>Ticket No.</th>
                    <th>DR No.</th>
                    <th>Subcon</th>
                    <th>Style</th>
                    <th>PO</th>
                    <th>L/E</th>
                    <th style="min-width:100px">Remarks</th>
                    <th>By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$txRows): ?>
            <tr>
                <td colspan="14" class="text-center text-muted py-4">
                    No transactions for this period.
                </td>
            </tr>
            <?php endif; ?>
            <?php foreach ($txRows as $r):
                $dirBadge = $r['direction'] === 'IN' ? 'success' : 'danger';
                $stageBadge = match($r['stage']) {
                    'RECEIVING'   => 'primary',
                    'QC'          => 'warning',
                    'DISPATCHING' => 'success',
                    default       => 'secondary',
                };
                $rowData = json_encode([
                    'id'              => (int)$r['id'],
                    'transaction_date'=> $r['transaction_date'],
                    'stage'           => $r['stage'],
                    'direction'       => $r['direction'],
                    'qty'             => (int)$r['qty'],
                    'ctrl_number'     => (string)($r['ctrl_number']     ?? ''),
                    'ticket_number'   => (string)($r['ticket_number']   ?? ''),
                    'dr_number'       => (string)($r['dr_number']       ?? ''),
                    'subcon_name'     => (string)($r['subcon_name']     ?? ''),
                    'collection_name' => (string)($r['collection_name'] ?? ''),
                    'style_code'      => (string)($r['style_code']      ?? ''),
                    'po_number'       => (string)($r['po_number']       ?? ''),
                    'local_export'    => (string)($r['local_export']    ?? ''),
                    'remarks'         => (string)($r['remarks']         ?? ''),
                ]);
                $rowAttr = htmlspecialchars($rowData, ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
                <td style="white-space:nowrap"><?= fmtDate($r['transaction_date']) ?></td>
                <td>
                    <span class="badge bg-<?= $stageBadge ?><?= $stageBadge === 'warning' ? ' text-dark' : '' ?>"
                          style="font-size:10px">
                        <?= h($r['stage']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= $dirBadge ?>" style="font-size:10px">
                        <?= h($r['direction']) ?>
                    </span>
                </td>
                <td class="text-end fw-semibold"><?= fmtNum($r['qty']) ?></td>
                <td><?= $r['ctrl_number']   ? h($r['ctrl_number'])   : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['ticket_number'] ? h($r['ticket_number']) : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['dr_number']     ? h($r['dr_number'])     : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['subcon_name']   ? h($r['subcon_name'])   : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['style_code']    ? h($r['style_code'])    : '<span class="text-muted">—</span>' ?></td>
                <td><?= $r['po_number']     ? h($r['po_number'])     : '<span class="text-muted">—</span>' ?></td>
                <td>
                    <?php if ($r['local_export']): ?>
                    <span class="badge bg-secondary" style="font-size:10px">
                        <?= h($r['local_export']) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"
                    title="<?= h($r['remarks'] ?? '') ?>">
                    <?= $r['remarks'] ? h($r['remarks']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-muted"><?= h($r['username'] ?? '') ?></td>
                <td style="white-space:nowrap">
                    <?php if (can('wip', 'create')): ?>
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary me-1 btn-edit-wip"
                            data-bs-toggle="modal"
                            data-bs-target="#wipEntryModal"
                            data-row="<?= $rowAttr ?>"
                            title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete this WIP entry? This cannot be undone.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action"    value="delete_wip_entry">
                        <input type="hidden" name="id"        value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="date_from" value="<?= h($dateFrom) ?>">
                        <input type="hidden" name="date_to"   value="<?= h($dateTo) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1):
    $qBase = http_build_query(array_filter([
        'date_from' => $dateFrom,
        'date_to'   => $dateTo,
        'stage'     => $filterStage,
        'dir'       => $filterDirection,
    ]));
    $qBase = $qBase ? $qBase . '&' : '';
?>
<nav class="mt-3" aria-label="Transaction log pagination">
    <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>page=<?= $page - 1 ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= $qBase ?>page=<?= $page + 1 ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- ── WIP Entry Modal ────────────────────────────────────────────────────── -->
<?php if (can('wip', 'create')): ?>
<div class="modal fade" id="wipEntryModal" tabindex="-1"
     aria-labelledby="wipEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="action.php" id="wipEntryForm" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="action"    value="save_wip_entry">
                <input type="hidden" name="id"        id="fieldId"   value="0">
                <input type="hidden" name="date_from" value="<?= h($dateFrom) ?>">
                <input type="hidden" name="date_to"   value="<?= h($dateTo) ?>">

                <div class="modal-header" style="background:#1e293b">
                    <h5 class="modal-title text-white" id="wipEntryModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Log WIP Entry
                    </h5>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Row 1: Date / Stage / Direction / Qty -->
                    <div class="row g-3">
                        <div class="col-sm-3">
                            <label class="form-label fw-semibold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="transaction_date" id="fieldDate"
                                   class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback">Date is required.</div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label fw-semibold">
                                Stage <span class="text-danger">*</span>
                            </label>
                            <select name="stage" id="fieldStage" class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="RECEIVING">RECEIVING</option>
                                <option value="QC">QC</option>
                                <option value="DISPATCHING">DISPATCHING</option>
                            </select>
                            <div class="invalid-feedback">Stage is required.</div>
                            <div id="qcAutoNote" class="form-text text-warning small d-none">
                                <i class="bi bi-info-circle"></i>
                                QC entries are normally auto-created from RECEIVING OUT.
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label fw-semibold">
                                Direction <span class="text-danger">*</span>
                            </label>
                            <select name="direction" id="fieldDirection"
                                    class="form-select" required>
                                <option value="">— Select —</option>
                                <option value="IN">IN</option>
                                <option value="OUT">OUT</option>
                            </select>
                            <div class="invalid-feedback">Direction is required.</div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label fw-semibold">
                                Qty <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="qty" id="fieldQty"
                                   class="form-control" min="1" step="1" required
                                   placeholder="e.g. 120">
                            <div class="invalid-feedback">Qty must be a positive integer.</div>
                        </div>
                    </div>

                    <!-- Auto-action info banner -->
                    <div id="receivingOutBanner"
                         class="alert alert-info d-flex align-items-center mt-3 mb-0 d-none"
                         role="alert" style="font-size:13px">
                        <i class="bi bi-arrow-repeat me-2 flex-shrink-0" style="font-size:18px"></i>
                        <div>
                            <strong>Auto-action:</strong> Saving a <strong>RECEIVING OUT</strong>
                            entry will automatically create a matching
                            <strong>QC IN</strong> entry for the same qty and date.
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Row 2: Reference numbers -->
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label small">CTRL Number</label>
                            <input type="text" name="ctrl_number" id="fieldCtrl"
                                   class="form-control form-control-sm"
                                   maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Ticket Number</label>
                            <input type="text" name="ticket_number" id="fieldTicket"
                                   class="form-control form-control-sm"
                                   maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">DR Number</label>
                            <input type="text" name="dr_number" id="fieldDR"
                                   class="form-control form-control-sm"
                                   maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Subcon Name</label>
                            <input type="text" name="subcon_name" id="fieldSubcon"
                                   class="form-control form-control-sm"
                                   maxlength="150" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Collection Name</label>
                            <input type="text" name="collection_name" id="fieldCollection"
                                   class="form-control form-control-sm"
                                   maxlength="150" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Style Code</label>
                            <input type="text" name="style_code" id="fieldStyle"
                                   class="form-control form-control-sm"
                                   maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">PO Number</label>
                            <input type="text" name="po_number" id="fieldPO"
                                   class="form-control form-control-sm"
                                   maxlength="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small">Local / Export</label>
                            <select name="local_export" id="fieldLE"
                                    class="form-select form-select-sm">
                                <option value="">— Not Specified —</option>
                                <option value="LOCAL">LOCAL</option>
                                <option value="EXPORT">EXPORT</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Remarks</label>
                            <textarea name="remarks" id="fieldRemarks"
                                      class="form-control form-control-sm" rows="2"
                                      maxlength="500"
                                      placeholder="Optional notes…"></textarea>
                        </div>
                    </div>
                </div><!-- /.modal-body -->

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark" id="btnSaveEntry">
                        <i class="bi bi-floppy me-1"></i>Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    'use strict';

    // ── Quick date buttons ────────────────────────────────────────────────────
    var df   = document.getElementById('inputDateFrom');
    var dt   = document.getElementById('inputDateTo');
    var form = document.getElementById('filterForm');

    function fmtDate(d) {
        return d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
    }

    document.getElementById('btnThisMonth').addEventListener('click', function () {
        var now = new Date();
        df.value = fmtDate(new Date(now.getFullYear(), now.getMonth(), 1));
        dt.value = fmtDate(now);
        form.submit();
    });

    document.getElementById('btnLastMonth').addEventListener('click', function () {
        var now         = new Date();
        var firstOfThis = new Date(now.getFullYear(), now.getMonth(), 1);
        var lastOfPrev  = new Date(firstOfThis - 1);
        var firstOfPrev = new Date(lastOfPrev.getFullYear(), lastOfPrev.getMonth(), 1);
        df.value = fmtDate(firstOfPrev);
        dt.value = fmtDate(lastOfPrev);
        form.submit();
    });

    document.getElementById('btnToday').addEventListener('click', function () {
        var today = fmtDate(new Date());
        df.value = today;
        dt.value = today;
        form.submit();
    });

    // ── Modal logic ───────────────────────────────────────────────────────────
    var modalTitle = document.getElementById('wipEntryModalLabel');
    var fieldId    = document.getElementById('fieldId');

    if (!fieldId) return; // user has no wip.create permission — nothing more to do

    var fieldStage     = document.getElementById('fieldStage');
    var fieldDirection = document.getElementById('fieldDirection');
    var banner         = document.getElementById('receivingOutBanner');
    var qcNote         = document.getElementById('qcAutoNote');
    var wipForm        = document.getElementById('wipEntryForm');

    function updateBanners() {
        var stage = fieldStage.value;
        var dir   = fieldDirection.value;
        banner.classList.toggle('d-none', !(stage === 'RECEIVING' && dir === 'OUT'));
        qcNote.classList.toggle('d-none', stage !== 'QC');
    }

    fieldStage.addEventListener('change', updateBanners);
    fieldDirection.addEventListener('change', updateBanners);

    // "Log WIP Entry" → reset to blank
    var btnNew = document.getElementById('btnNewEntry');
    if (btnNew) {
        btnNew.addEventListener('click', function () {
            modalTitle.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Log WIP Entry';
            fieldId.value = '0';
            wipForm.reset();
            wipForm.classList.remove('was-validated');
            document.getElementById('fieldDate').value = '<?= date('Y-m-d') ?>';
            updateBanners();
        });
    }

    // Edit buttons → populate modal from data-row JSON
    document.querySelectorAll('.btn-edit-wip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = JSON.parse(this.getAttribute('data-row'));
            modalTitle.innerHTML = '<i class="bi bi-pencil me-2"></i>Edit WIP Entry';
            fieldId.value = row.id;
            document.getElementById('fieldDate').value       = row.transaction_date;
            fieldStage.value                                  = row.stage;
            fieldDirection.value                              = row.direction;
            document.getElementById('fieldQty').value        = row.qty;
            document.getElementById('fieldCtrl').value       = row.ctrl_number;
            document.getElementById('fieldTicket').value     = row.ticket_number;
            document.getElementById('fieldDR').value         = row.dr_number;
            document.getElementById('fieldSubcon').value     = row.subcon_name;
            document.getElementById('fieldCollection').value = row.collection_name;
            document.getElementById('fieldStyle').value      = row.style_code;
            document.getElementById('fieldPO').value         = row.po_number;
            document.getElementById('fieldLE').value         = row.local_export;
            document.getElementById('fieldRemarks').value    = row.remarks;
            wipForm.classList.remove('was-validated');
            updateBanners();
        });
    });

    // Bootstrap 5 HTML5 form validation
    wipForm.addEventListener('submit', function (e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        this.classList.add('was-validated');
    });

}());
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
