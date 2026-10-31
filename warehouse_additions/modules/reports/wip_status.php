<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('reports', 'view');

$db = getDB();
$from = getDateParam('from', date('Y-m-d', strtotime('-7 days')));
$to = getDateParam('to', date('Y-m-d'));
$fromDT = $from . ' 00:00:00';
$toDT = $to . ' 23:59:59';

function scalarSum(PDO $db, string $sql, array $params): float
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (float)($stmt->fetch()['v'] ?? 0);
}

// --- Stage 1: Receiving ---
$recIn    = scalarSum($db, "SELECT COALESCE(SUM(po_qty),0) v FROM purchase_orders WHERE created_at BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$recOut   = scalarSum($db, "SELECT COALESCE(SUM(received_qty),0) v FROM receiving WHERE received_date BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$recBegin = scalarSum($db, "SELECT COALESCE(SUM(po_qty),0) v FROM purchase_orders WHERE created_at < :f", ['f' => $fromDT])
          - scalarSum($db, "SELECT COALESCE(SUM(received_qty),0) v FROM receiving WHERE received_date < :f", ['f' => $fromDT]);
$recEnd   = $recBegin + $recIn - $recOut;

// --- Stage 2: QC ---
$qcIn    = scalarSum($db, "SELECT COALESCE(SUM(received_qty),0) v FROM receiving WHERE received_date BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$qcOut   = scalarSum($db, "SELECT COALESCE(SUM(inspected_qty),0) v FROM qc_inspection WHERE inspection_date BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$qcBegin = scalarSum($db, "SELECT COALESCE(SUM(received_qty),0) v FROM receiving WHERE received_date < :f", ['f' => $fromDT])
         - scalarSum($db, "SELECT COALESCE(SUM(inspected_qty),0) v FROM qc_inspection WHERE inspection_date < :f", ['f' => $fromDT]);
$qcEnd   = $qcBegin + $qcIn - $qcOut;

// --- Stage 3: Dispatching (closed gatepasses = dispatched) ---
$dispIn    = scalarSum($db, "SELECT COALESCE(SUM(passed_qty),0) v FROM qc_inspection WHERE inspection_date BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$dispOut   = scalarSum($db, "SELECT COALESCE(SUM(gi.qty_cartons),0) v FROM gatepasses g JOIN gatepass_items gi ON gi.gp_id=g.gp_id WHERE g.closed_at BETWEEN :f AND :t", ['f' => $fromDT, 't' => $toDT]);
$dispBegin = scalarSum($db, "SELECT COALESCE(SUM(passed_qty),0) v FROM qc_inspection WHERE inspection_date < :f", ['f' => $fromDT])
           - scalarSum($db, "SELECT COALESCE(SUM(gi.qty_cartons),0) v FROM gatepasses g JOIN gatepass_items gi ON gi.gp_id=g.gp_id WHERE g.closed_at < :f", ['f' => $fromDT]);
$dispEnd   = $dispBegin + $dispIn - $dispOut;

$rows = [
    ['label' => 'Receiving',       'begin' => $recBegin,  'in' => $recIn,  'out' => $recOut,  'end' => $recEnd],
    ['label' => 'QC / Inspection', 'begin' => $qcBegin,   'in' => $qcIn,   'out' => $qcOut,   'end' => $qcEnd],
    ['label' => 'Dispatching',     'begin' => $dispBegin, 'in' => $dispIn, 'out' => $dispOut, 'end' => $dispEnd],
];
$totalBegin = $recBegin  + $qcBegin  + $dispBegin;
$totalIn    = $recIn     + $qcIn     + $dispIn;
$totalOut   = $recOut    + $qcOut    + $dispOut;
$totalEnd   = $recEnd    + $qcEnd    + $dispEnd;

$pageTitle = 'WIP Status Report';
include __DIR__ . '/../../includes/header.php';
?>
<h5 class="mb-3">WIP Status Report</h5>
<form method="get" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= h($from) ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= h($to) ?>">
    </div>
    <div class="col-auto"><button class="btn btn-dark btn-sm">Run Report</button></div>
</form>
<p class="text-muted small">Beginning = work-in-progress at each stage as of the start of the period. In/Out = activity during the period. Ending = Beginning + In − Out.</p>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th class="text-end">Beginning</th>
                    <th class="text-end">In</th>
                    <th class="text-end">Out</th>
                    <th class="text-end">Ending</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['label']) ?></td>
                    <td class="text-end"><?= fmtNum($r['begin']) ?></td>
                    <td class="text-end text-success">+<?= fmtNum($r['in']) ?></td>
                    <td class="text-end text-danger">-<?= fmtNum($r['out']) ?></td>
                    <td class="text-end fw-semibold"><?= fmtNum($r['end']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold border-top">
                    <td>Total</td>
                    <td class="text-end"><?= fmtNum($totalBegin) ?></td>
                    <td class="text-end text-success">+<?= fmtNum($totalIn) ?></td>
                    <td class="text-end text-danger">-<?= fmtNum($totalOut) ?></td>
                    <td class="text-end"><?= fmtNum($totalEnd) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
