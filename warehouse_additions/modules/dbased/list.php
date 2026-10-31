<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dbased', 'view');

$db = getDB();

// ── Filters ────────────────────────────────────────────────────────────────
$filterCoordinator = (int)($_GET['coordinator_id'] ?? 0);
$filterStatus      = trim($_GET['status']    ?? '');
$filterFacility    = trim($_GET['facility']  ?? '');
$filterSearch      = trim($_GET['search']    ?? '');
$filterDateFrom    = getDateParam('date_from');
$filterDateTo      = getDateParam('date_to');

$where  = ['1=1'];
$params = [];

if ($filterCoordinator > 0) {
    $where[]  = 'po.coordinator_id = ?';
    $params[] = $filterCoordinator;
}
if ($filterStatus !== '' && in_array($filterStatus, ['NEW','WIP','COMPLETED','CANCELLED'], true)) {
    $where[]  = 'po.status = ?';
    $params[] = $filterStatus;
}
if ($filterFacility !== '') {
    $where[]  = 'po.facility = ?';
    $params[] = $filterFacility;
}
if ($filterSearch !== '') {
    $where[]  = '(po.po_number LIKE ? OR po.style_no LIKE ? OR po.ticket_no LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterDateFrom !== '') {
    $where[]  = 'po.po_date >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $where[]  = 'po.po_date <= ?';
    $params[] = $filterDateTo;
}

$whereClause = implode(' AND ', $where);

// ── KPI totals (filtered) ──────────────────────────────────────────────────
$kpiSql = "
    SELECT
        COUNT(*)                                               AS total_pos,
        COALESCE(SUM(po.po_qty), 0)                          AS total_po_qty,
        COALESCE(SUM(COALESCE(grn_agg.total_received, 0)), 0) AS total_received
    FROM purchase_orders po
    LEFT JOIN (
        SELECT po_id, SUM(total_qty_received) AS total_received
        FROM grn WHERE status != 'DRAFT'
        GROUP BY po_id
    ) grn_agg ON grn_agg.po_id = po.po_id
    WHERE {$whereClause}
";
$kpiStmt = $db->prepare($kpiSql);
$kpiStmt->execute($params);
$kpi = $kpiStmt->fetch();
$kpiTotalPos      = (int)($kpi['total_pos']      ?? 0);
$kpiTotalQty      = (int)($kpi['total_po_qty']   ?? 0);
$kpiTotalReceived = (int)($kpi['total_received']  ?? 0);
$kpiBalance       = $kpiTotalQty - $kpiTotalReceived;

// ── Base SQL (reused for list + CSV export) ────────────────────────────────
$baseSql = "
    SELECT
        po.*,
        c.coordinator_name                                  AS coordinator_name,
        sc.subcon_name,
        COALESCE(grn_agg.total_received, 0)                AS total_received,
        grn_agg.last_grn_date,
        (po.po_qty - COALESCE(grn_agg.total_received, 0))  AS po_balance,
        DATEDIFF(grn_agg.last_grn_date, po.po_date)        AS tat_days,
        pda.qty_local,
        pda.qty_sg,
        pda.qty_malaysia,
        pda.qty_indo,
        pda.qty_sm_baliwag
    FROM purchase_orders po
    LEFT JOIN coordinators c
           ON c.coordinator_id = po.coordinator_id
    LEFT JOIN subcons sc
           ON sc.subcon_id = po.subcon_id
    LEFT JOIN (
        SELECT po_id,
               SUM(total_qty_received)  AS total_received,
               MAX(grn_date)            AS last_grn_date
        FROM grn
        WHERE status != 'DRAFT'
        GROUP BY po_id
    ) grn_agg ON grn_agg.po_id = po.po_id
    LEFT JOIN po_destination_allocations pda
           ON pda.po_id = po.po_id
    WHERE {$whereClause}
    ORDER BY
        CASE WHEN po.receiving_priority IS NULL THEN 1 ELSE 0 END,
        po.receiving_priority ASC,
        po.po_id DESC
";

// ── CSV Export (runs before any HTML output) ──────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $db->prepare($baseSql);
    $stmt->execute($params);
    $allRows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="dbased_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

    fputcsv($out, [
        '#','Coordinator','PO Date','Status','PO Number','Style No','Subcon',
        'Collection ID','PO Qty','Total Received','PO Balance',
        'Recv Priority','Insp Priority',
        'LOCAL','SG','MY','INDO','SM BALIWAG',
        'TAT (days)',
    ]);

    $i = 1;
    foreach ($allRows as $r) {
        fputcsv($out, [
            $i++,
            $r['coordinator_name'] ?? '',
            $r['po_date']          ?? '',
            $r['status']           ?? '',
            $r['po_number']        ?? '',
            $r['style_no']         ?? '',
            $r['subcon_name']      ?? '',
            $r['collection_id']    ?? '',
            $r['po_qty']           ?? 0,
            $r['total_received']   ?? 0,
            $r['po_balance']       ?? 0,
            $r['receiving_priority']   ?? '',
            $r['inspection_priority']  ?? '',
            $r['qty_local']       ?? '',
            $r['qty_sg']          ?? '',
            $r['qty_malaysia']    ?? '',
            $r['qty_indo']        ?? '',
            $r['qty_sm_baliwag']  ?? '',
            $r['tat_days']        ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ── Pagination ─────────────────────────────────────────────────────────────
$perPage    = 50;
$page       = max(1, (int)($_GET['page'] ?? 1));
$offset     = ($page - 1) * $perPage;

$cntSql  = "SELECT COUNT(*)
            FROM purchase_orders po
            LEFT JOIN coordinators c ON c.coordinator_id = po.coordinator_id
            LEFT JOIN subcons sc ON sc.subcon_id = po.subcon_id
            WHERE {$whereClause}";
$cntStmt = $db->prepare($cntSql);
$cntStmt->execute($params);
$totalRows  = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$listSql  = $baseSql . " LIMIT {$perPage} OFFSET {$offset}";
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

// ── Coordinators for filter dropdown ──────────────────────────────────────
try {
    $coordinators = $db->query(
        "SELECT coordinator_id, coordinator_name FROM coordinators WHERE is_active = 1 ORDER BY coordinator_name"
    )->fetchAll();
} catch (Throwable $e) {
    $coordinators = [];
}

// ── Render ─────────────────────────────────────────────────────────────────
$pageTitle = 'DBASED — PO Master Register';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0"><i class="bi bi-table me-1"></i>DBASED</h5>
        <span class="badge bg-secondary"><?= $totalRows ?></span>
    </div>
    <?php
    $csvQs = http_build_query(array_merge(
        array_filter([
            'coordinator_id' => $filterCoordinator ?: null,
            'status'         => $filterStatus,
            'facility'       => $filterFacility,
            'search'         => $filterSearch,
            'date_from'      => $filterDateFrom,
            'date_to'        => $filterDateTo,
        ]),
        ['export' => 'csv']
    ));
    ?>
    <a href="list.php?<?= $csvQs ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- ── KPI Cards ───────────────────────────────────────────────────────── -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="kpi-card shadow-sm" style="border-left-color:#1e3a5f">
            <div class="kpi-label">Total POs</div>
            <div class="kpi-value text-dark"><?= number_format($kpiTotalPos) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card shadow-sm" style="border-left-color:#0d6efd">
            <div class="kpi-label">Total PO Qty</div>
            <div class="kpi-value text-primary"><?= number_format($kpiTotalQty) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card shadow-sm" style="border-left-color:#198754">
            <div class="kpi-label">Total Received</div>
            <div class="kpi-value text-success"><?= number_format($kpiTotalReceived) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card shadow-sm" style="border-left-color:#dc3545">
            <div class="kpi-label">Pending Balance</div>
            <div class="kpi-value <?= $kpiBalance > 0 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($kpiBalance) ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Filter Bar ──────────────────────────────────────────────────────── -->
<form method="get" action="list.php" class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-sm-2">
            <label class="form-label small mb-1">Coordinator</label>
            <select name="coordinator_id" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach ($coordinators as $crd): ?>
                <option value="<?= (int)$crd['coordinator_id'] ?>"
                    <?= $filterCoordinator === (int)$crd['coordinator_id'] ? 'selected' : '' ?>>
                    <?= h($crd['coordinator_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <?php foreach (['NEW','WIP','COMPLETED','CANCELLED'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Facility</label>
            <select name="facility" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="WHL" <?= $filterFacility === 'WHL' ? 'selected' : '' ?>>WHL</option>
                <option value="WFR" <?= $filterFacility === 'WFR' ? 'selected' : '' ?>>WFR</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="<?= h($filterSearch) ?>" placeholder="PO / Style / Ticket">
        </div>
        <div class="col-sm-1">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="<?= h($filterDateFrom) ?>">
        </div>
        <div class="col-sm-1">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="<?= h($filterDateTo) ?>">
        </div>
        <div class="col-sm-2 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- ── Showing / Pagination info ───────────────────────────────────────── -->
<?php
$showFrom = $totalRows > 0 ? $offset + 1        : 0;
$showTo   = min($offset + $perPage, $totalRows);
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <small class="text-muted">
        Showing <?= number_format($showFrom) ?>–<?= number_format($showTo) ?>
        of <?= number_format($totalRows) ?> records
    </small>
</div>

<!-- ── Main Table ─────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle" style="font-size:0.82rem; min-width:1400px">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width:36px">#</th>
                    <th style="min-width:90px">COOR</th>
                    <th style="min-width:88px">DATE</th>
                    <th style="min-width:80px">STATUS</th>
                    <th style="min-width:120px">PO NUMBER</th>
                    <th style="min-width:100px">STYLE</th>
                    <th style="min-width:130px">SUBCON</th>
                    <th style="min-width:80px">COLLECTION</th>
                    <th class="text-end" style="min-width:75px">PO QTY</th>
                    <th class="text-end" style="min-width:75px">RECEIVED</th>
                    <th class="text-end" style="min-width:75px">BALANCE</th>
                    <th class="text-center" style="min-width:60px">RECV<br>PRIO</th>
                    <th class="text-center" style="min-width:60px">INSP<br>PRIO</th>
                    <th class="text-end" style="min-width:48px"><small>LOCAL</small></th>
                    <th class="text-end" style="min-width:40px"><small>SG</small></th>
                    <th class="text-end" style="min-width:40px"><small>MY</small></th>
                    <th class="text-end" style="min-width:40px"><small>INDO</small></th>
                    <th class="text-end" style="min-width:58px"><small>SM&nbsp;BAL</small></th>
                    <th class="text-end" style="min-width:55px">TAT</th>
                    <th style="min-width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="20" class="text-center text-muted py-4">
                    No records found. Adjust filters or add purchase orders.
                </td>
            </tr>
            <?php endif; ?>
            <?php
            $rowNum = $offset + 1;
            foreach ($rows as $po):
                $statusBadge = match($po['status']) {
                    'NEW'       => 'secondary',
                    'WIP'       => 'warning text-dark',
                    'COMPLETED' => 'success',
                    'CANCELLED' => 'danger',
                    default     => 'secondary',
                };

                $balance     = (int)($po['po_balance'] ?? 0);
                $received    = (int)($po['total_received'] ?? 0);
                $balanceClass = 'text-success fw-semibold';
                if ($balance > 0 && $received > 0) {
                    $balanceClass = 'text-warning fw-semibold';
                } elseif ($balance > 0 && $received === 0) {
                    $balanceClass = 'text-danger fw-semibold';
                }

                $tatDays = $po['tat_days'];
                $tatDisplay = ($tatDays !== null) ? $tatDays . 'd' : '<span class="text-muted">—</span>';

                $fmtDest = function(?int $qty): string {
                    return ($qty !== null && $qty !== '') ? number_format((int)$qty) : '<span class="text-muted">—</span>';
                };
            ?>
            <tr>
                <td class="text-center text-muted"><?= $rowNum++ ?></td>
                <td>
                    <?php if ($po['coordinator_name']): ?>
                    <span class="badge bg-info text-dark" style="font-size:0.7rem">
                        <?= h($po['coordinator_name']) ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <?= $po['po_date'] ? h(date('d M y', strtotime($po['po_date']))) : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <span class="badge bg-<?= $statusBadge ?>" style="font-size:0.7rem">
                        <?= h($po['status']) ?>
                    </span>
                </td>
                <td class="fw-semibold text-nowrap">
                    <a href="view.php?id=<?= (int)$po['po_id'] ?>" class="text-decoration-none">
                        <?= h($po['po_number']) ?>
                    </a>
                    <?php if (!empty($po['po_number_alias'])): ?>
                    <br><small class="text-muted"><?= h($po['po_number_alias']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= h($po['style_no'] ?? '') ?></td>
                <td class="text-nowrap">
                    <?= $po['subcon_name'] ? h($po['subcon_name']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td>
                    <?= $po['collection_id'] ? (int)$po['collection_id'] : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-end"><?= number_format((int)$po['po_qty']) ?></td>
                <td class="text-end"><?= number_format($received) ?></td>
                <td class="text-end <?= $balanceClass ?>">
                    <?= number_format($balance) ?>
                </td>
                <td class="text-center">
                    <?php if ($po['receiving_priority'] !== null && $po['receiving_priority'] !== ''): ?>
                    <span class="badge bg-primary" style="font-size:0.7rem">
                        <?= (int)$po['receiving_priority'] ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($po['inspection_priority'] !== null && $po['inspection_priority'] !== ''): ?>
                    <span class="badge bg-secondary" style="font-size:0.7rem">
                        <?= (int)$po['inspection_priority'] ?>
                    </span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end small"><?= $fmtDest($po['qty_local']     ?? null) ?></td>
                <td class="text-end small"><?= $fmtDest($po['qty_sg']        ?? null) ?></td>
                <td class="text-end small"><?= $fmtDest($po['qty_malaysia']  ?? null) ?></td>
                <td class="text-end small"><?= $fmtDest($po['qty_indo']      ?? null) ?></td>
                <td class="text-end small"><?= $fmtDest($po['qty_sm_baliwag']?? null) ?></td>
                <td class="text-end small text-muted"><?= $tatDisplay ?></td>
                <td class="text-end text-nowrap">
                    <a href="view.php?id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-sm btn-outline-secondary" title="View">
                        <i class="bi bi-eye"></i>
                    </a>
                    <?php if (can('dbased', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$po['po_id'] ?>"
                       class="btn btn-sm btn-outline-primary ms-1" title="Edit DBASED">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Pagination ─────────────────────────────────────────────────────── -->
<?php if ($totalPages > 1):
    $baseQs = array_filter([
        'coordinator_id' => $filterCoordinator ?: null,
        'status'         => $filterStatus,
        'facility'       => $filterFacility,
        'search'         => $filterSearch,
        'date_from'      => $filterDateFrom,
        'date_to'        => $filterDateTo,
    ]);
?>
<nav>
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $maxLinks = 10;
        $half     = (int)floor($maxLinks / 2);
        $start    = max(1, $page - $half);
        $end      = min($totalPages, $start + $maxLinks - 1);
        $start    = max(1, $end - $maxLinks + 1);

        if ($page > 1):
            $qs = http_build_query(array_merge($baseQs, ['page' => $page - 1]));
        ?>
        <li class="page-item">
            <a class="page-link" href="list.php?<?= $qs ?>">&laquo;</a>
        </li>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++):
            $qs = http_build_query(array_merge($baseQs, ['page' => $p]));
        ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="list.php?<?= $qs ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages):
            $qs = http_build_query(array_merge($baseQs, ['page' => $page + 1]));
        ?>
        <li class="page-item">
            <a class="page-link" href="list.php?<?= $qs ?>">&raquo;</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
