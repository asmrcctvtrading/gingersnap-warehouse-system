<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('smart_buys', 'view');

$db = getDB();

// KPI counts
$kpiTotal    = (int) $db->query("SELECT COUNT(*) FROM smart_buys")->fetchColumn();
$kpiPending  = (int) $db->query("SELECT COUNT(*) FROM smart_buys WHERE status = 'PENDING'")->fetchColumn();
$kpiListed   = (int) $db->query("SELECT COUNT(*) FROM smart_buys WHERE status = 'LISTED'")->fetchColumn();
$kpiSold     = (int) $db->query("SELECT COUNT(*) FROM smart_buys WHERE status = 'SOLD'")->fetchColumn();
$kpiDisposed = (int) $db->query("SELECT COUNT(*) FROM smart_buys WHERE status = 'DISPOSED'")->fetchColumn();

// Filters
$fStatus    = trim($_GET['status']    ?? '');
$fSource    = trim($_GET['source']    ?? '');
$fSearch    = trim($_GET['search']    ?? '');
$fDateFrom  = trim($_GET['date_from'] ?? '');
$fDateTo    = trim($_GET['date_to']   ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 50;
$offset     = ($page - 1) * $perPage;

$validStatuses = ['PENDING', 'LISTED', 'SOLD', 'DISPOSED'];
$validSources  = ['QC_FAIL', 'DC_DEFECTIVE', 'MANUAL'];

if ($fStatus && !in_array($fStatus, $validStatuses, true)) { $fStatus = ''; }
if ($fSource && !in_array($fSource, $validSources, true))  { $fSource = ''; }

$where  = [];
$params = [];

if ($fStatus) { $where[] = 'sb.status = ?'; $params[] = $fStatus; }
if ($fSource) { $where[] = 'sb.source = ?'; $params[] = $fSource; }
if ($fSearch) {
    $where[]  = '(sb.sku_code LIKE ? OR sb.item_name LIKE ?)';
    $params[] = '%' . $fSearch . '%';
    $params[] = '%' . $fSearch . '%';
}
if ($fDateFrom) { $where[] = 'DATE(sb.created_at) >= ?'; $params[] = $fDateFrom; }
if ($fDateTo)   { $where[] = 'DATE(sb.created_at) <= ?'; $params[] = $fDateTo; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM smart_buys sb $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$dataStmt = $db->prepare(
    "SELECT sb.sb_id, sb.sku_code, sb.item_name, sb.color, sb.size, sb.qty,
            sb.condition_code, sb.source, sb.status, sb.sale_price, sb.created_at,
            g.grn_id, g.grn_code
     FROM smart_buys sb
     LEFT JOIN grn g ON g.grn_id = sb.grn_id
     $whereSql
     ORDER BY sb.sb_id DESC
     LIMIT ? OFFSET ?"
);
$dataStmt->execute(array_merge($params, [$perPage, $offset]));
$rows = $dataStmt->fetchAll();

$flash = getFlash();

$pageTitle = 'SMART Buys';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= h($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- KPI Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="kpi-card h-100">
            <div class="kpi-label">Total</div>
            <div class="kpi-value"><?= number_format($kpiTotal) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card h-100" style="border-left-color:#6c757d;">
            <div class="kpi-label">Pending</div>
            <div class="kpi-value text-secondary"><?= number_format($kpiPending) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#0d6efd;">
            <div class="kpi-label">Listed</div>
            <div class="kpi-value text-primary"><?= number_format($kpiListed) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kpi-card h-100" style="border-left-color:#198754;">
            <div class="kpi-label">Sold</div>
            <div class="kpi-value text-success"><?= number_format($kpiSold) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="kpi-card h-100" style="border-left-color:#212529;">
            <div class="kpi-label">Disposed</div>
            <div class="kpi-value text-dark"><?= number_format($kpiDisposed) ?></div>
        </div>
    </div>
</div>

<!-- Filters + New Entry -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-sm-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= h($s) ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Source</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All Sources</option>
                    <?php foreach ($validSources as $s): ?>
                    <option value="<?= h($s) ?>" <?= $fSource === $s ? 'selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Search (SKU / Item)</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= h($fSearch) ?>" placeholder="SKU or item name...">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= h($fDateFrom) ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= h($fDateTo) ?>">
            </div>
            <div class="col-sm-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= BASE_URL ?>/modules/smart_buys/list.php" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex align-items-center">
        <span class="fw-semibold"><i class="bi bi-tags me-2"></i>SMART Buys
            <span class="badge bg-secondary ms-1"><?= number_format($totalRows) ?></span>
        </span>
        <?php if (can('smart_buys', 'create')): ?>
        <a href="<?= BASE_URL ?>/modules/smart_buys/form.php" class="btn btn-sm btn-success ms-auto">
            <i class="bi bi-plus-lg me-1"></i>New Entry
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (!$rows): ?>
        <p class="text-muted text-center py-4 mb-0">No SMART Buys records found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th>Color / Size</th>
                        <th class="text-end">Qty</th>
                        <th>Condition</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>GRN</th>
                        <th class="text-end">Sale Price</th>
                        <th>Created At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                <?php
                    $sourceBadge = match($row['source']) {
                        'QC_FAIL'      => 'danger',
                        'DC_DEFECTIVE' => 'warning',
                        default        => 'secondary',
                    };
                    $statusBadge = match($row['status']) {
                        'PENDING'  => 'secondary',
                        'LISTED'   => 'primary',
                        'SOLD'     => 'success',
                        'DISPOSED' => 'dark',
                        default    => 'secondary',
                    };
                ?>
                <tr>
                    <td class="small text-muted"><?= (int)$row['sb_id'] ?></td>
                    <td class="small"><?= h($row['sku_code'] ?? '—') ?></td>
                    <td><?= h($row['item_name']) ?></td>
                    <td class="small"><?= h(implode(' / ', array_filter([$row['color'], $row['size']])) ?: '—') ?></td>
                    <td class="text-end"><?= number_format((int)$row['qty']) ?></td>
                    <td class="small"><?= h($row['condition_code']) ?></td>
                    <td>
                        <span class="badge bg-<?= $sourceBadge ?><?= $row['source'] === 'DC_DEFECTIVE' ? ' text-dark' : '' ?>">
                            <?= h($row['source']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $statusBadge ?>">
                            <?= h($row['status']) ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if ($row['grn_code']): ?>
                        <a href="<?= BASE_URL ?>/modules/grn/view.php?id=<?= (int)$row['grn_id'] ?>" class="text-decoration-none">
                            <?= h($row['grn_code']) ?>
                        </a>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td class="text-end small">
                        <?= $row['sale_price'] !== null ? '&#8369;' . number_format((float)$row['sale_price'], 2) : '—' ?>
                    </td>
                    <td class="small text-muted">
                        <?= $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '—' ?>
                    </td>
                    <td class="text-end" style="white-space:nowrap;">
                        <a href="<?= BASE_URL ?>/modules/smart_buys/view.php?id=<?= (int)$row['sb_id'] ?>"
                           class="btn btn-sm btn-outline-secondary py-0">View</a>
                        <?php if (can('smart_buys', 'edit') && $row['status'] === 'PENDING'): ?>
                        <a href="<?= BASE_URL ?>/modules/smart_buys/form.php?id=<?= (int)$row['sb_id'] ?>"
                           class="btn btn-sm btn-outline-primary py-0">Edit</a>
                        <form method="post" action="<?= BASE_URL ?>/modules/smart_buys/action.php"
                              class="d-inline"
                              onsubmit="return confirm('Delete this SMART Buys entry?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_sb">
                            <input type="hidden" name="sb_id" value="<?= (int)$row['sb_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0">Delete</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= number_format($totalRows) ?> records
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                        <?= $p ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
