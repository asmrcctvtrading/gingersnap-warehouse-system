<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dc_putaway', 'view');

$db = getDB();
$today = date('Y-m-d');
$hourNow = (int)date('H');

// KPIs
try {
    $kpiPending       = (int)$db->query("SELECT COUNT(*) FROM dc_putaways WHERE status='PENDING'")->fetchColumn();
    $kpiDoneToday     = (int)$db->query("SELECT COUNT(*) FROM dc_putaways WHERE status='COMPLETED' AND putaway_date='$today'")->fetchColumn();
    $kpiQtyToday      = (int)$db->query("SELECT COALESCE(SUM(total_qty),0) FROM dc_putaways WHERE status='COMPLETED' AND putaway_date='$today'")->fetchColumn();
    $kpiDcWaiting     = (int)$db->query("SELECT COUNT(*) FROM dc_receivings WHERE status IN ('RECEIVED','PUTAWAY_PENDING')")->fetchColumn();
} catch (Exception $e) {
    $kpiPending = $kpiDoneToday = $kpiQtyToday = $kpiDcWaiting = 0;
}

// Filters
$filterType   = $_GET['warehouse_type'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterFrom   = $_GET['date_from'] ?? '';
$filterTo     = $_GET['date_to'] ?? '';
$filterGp     = trim($_GET['gp_code'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($filterType)   { $where[] = 'dcp.warehouse_type = ?'; $params[] = $filterType; }
if ($filterStatus) { $where[] = 'dcp.status = ?';         $params[] = $filterStatus; }
if ($filterFrom)   { $where[] = 'dcp.putaway_date >= ?';  $params[] = $filterFrom; }
if ($filterTo)     { $where[] = 'dcp.putaway_date <= ?';  $params[] = $filterTo; }
if ($filterGp)     { $where[] = 'dcp.gp_code LIKE ?';     $params[] = '%'.$filterGp.'%'; }

$whereSql = implode(' AND ', $where);

$stCount = $db->prepare("SELECT COUNT(*) FROM dc_putaways dcp WHERE $whereSql");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();

$stList = $db->prepare(
    "SELECT dcp.* FROM dc_putaways dcp
     WHERE $whereSql
     ORDER BY dcp.dcp_id DESC
     LIMIT $perPage OFFSET $offset"
);
$stList->execute($params);
$rows = $stList->fetchAll();

$totalPages = max(1, (int)ceil($total / $perPage));
$pageTitle  = 'DC Putaway';
include __DIR__ . '/../../includes/header.php';
$flash = getFlash();
?>
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3">
    <?= h($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($hourNow >= 14 && $kpiPending > 0): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    DC Putaway cutoff (2PM) has passed — <?= $kpiPending ?> putaway(s) still pending.
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
            <div class="card-body py-3">
                <div class="text-muted small">Pending</div>
                <div class="fs-4 fw-bold text-warning"><?= $kpiPending ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #22c55e!important">
            <div class="card-body py-3">
                <div class="text-muted small">Completed Today</div>
                <div class="fs-4 fw-bold text-success"><?= $kpiDoneToday ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $kpiQtyToday > 4000 ? '#dc2626' : '#0891b2' ?>!important">
            <div class="card-body py-3">
                <div class="text-muted small">Total Qty Today</div>
                <div class="fs-4 fw-bold <?= $kpiQtyToday > 4000 ? 'text-danger' : '' ?>"><?= number_format($kpiQtyToday) ?></div>
                <?php if ($kpiQtyToday > 4000): ?><div class="small text-danger">Over 4,000 cutoff</div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7c3aed!important">
            <div class="card-body py-3">
                <div class="text-muted small">DC Receiving Awaiting</div>
                <div class="fs-4 fw-bold"><?= $kpiDcWaiting ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <select name="warehouse_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="LOCAL"       <?= $filterType==='LOCAL'       ? 'selected' : '' ?>>LOCAL</option>
                    <option value="EXPORT"      <?= $filterType==='EXPORT'      ? 'selected' : '' ?>>EXPORT</option>
                    <option value="SMART_BUYS"  <?= $filterType==='SMART_BUYS'  ? 'selected' : '' ?>>SMART BUYS</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="PENDING"   <?= $filterStatus==='PENDING'   ? 'selected' : '' ?>>PENDING</option>
                    <option value="COMPLETED" <?= $filterStatus==='COMPLETED' ? 'selected' : '' ?>>COMPLETED</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= h($filterFrom) ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= h($filterTo) ?>" placeholder="To">
            </div>
            <div class="col-md-2">
                <input type="text" name="gp_code" class="form-control form-control-sm" value="<?= h($filterGp) ?>" placeholder="GP Code">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>DC Putaway <span class="badge bg-secondary"><?= $total ?></span></h5>
    <?php if (can('dc_putaway','create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg me-1"></i>New Putaway</a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>GP Code</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Person</th>
                    <th class="text-end">Total Qty</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No DC putaway records found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r):
                $typeBadge = match($r['warehouse_type']) {
                    'LOCAL'      => 'primary',
                    'EXPORT'     => 'success',
                    'SMART_BUYS' => 'danger',
                    default      => 'secondary',
                };
                $stBadge = $r['status'] === 'COMPLETED' ? 'success' : 'warning text-dark';
            ?>
            <tr>
                <td><?= $r['dcp_id'] ?></td>
                <td><a href="view.php?id=<?= $r['dcp_id'] ?>"><?= $r['gp_code'] ? h($r['gp_code']) : '&mdash;' ?></a></td>
                <td><span class="badge bg-<?= $typeBadge ?>"><?= h($r['warehouse_type']) ?></span></td>
                <td><?= h($r['putaway_date']) ?></td>
                <td><?= $r['putaway_person'] ? h($r['putaway_person']) : '&mdash;' ?></td>
                <td class="text-end <?= (int)$r['total_qty'] > 4000 ? 'text-danger fw-bold' : '' ?>"><?= number_format((int)$r['total_qty']) ?></td>
                <td><span class="badge bg-<?= $stBadge ?>"><?= h($r['status']) ?></span></td>
                <td>
                    <a href="view.php?id=<?= $r['dcp_id'] ?>" class="btn btn-outline-secondary btn-sm py-0">View</a>
                    <?php if ($r['status'] === 'PENDING' && can('dc_putaway','edit')): ?>
                    <a href="form.php?id=<?= $r['dcp_id'] ?>" class="btn btn-outline-primary btn-sm py-0">Edit</a>
                    <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete this record?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_dcp">
                        <input type="hidden" name="id" value="<?= $r['dcp_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0">Del</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $p ?>&warehouse_type=<?= urlencode($filterType) ?>&status=<?= urlencode($filterStatus) ?>&date_from=<?= urlencode($filterFrom) ?>&date_to=<?= urlencode($filterTo) ?>&gp_code=<?= urlencode($filterGp) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
