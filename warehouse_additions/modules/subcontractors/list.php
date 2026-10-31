<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('subcontractors', 'view');

$db = getDB();

// Search / pagination
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$where  = $search !== '' ? 'WHERE s.subcon_name LIKE :q' : '';
$params = $search !== '' ? ['q' => '%' . $search . '%'] : [];

$totalStmt = $db->prepare("SELECT COUNT(*) FROM subcons s $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$listStmt = $db->prepare(
    "SELECT s.subcon_id, s.subcon_name,
            COUNT(po.po_id) AS po_count
     FROM subcons s
     LEFT JOIN purchase_orders po ON po.subcon_id = s.subcon_id
     $where
     GROUP BY s.subcon_id, s.subcon_name
     ORDER BY s.subcon_name
     LIMIT :lim OFFSET :off"
);
if ($search !== '') {
    $listStmt->bindValue('q', '%' . $search . '%');
}
$listStmt->bindValue('lim', $perPage, PDO::PARAM_INT);
$listStmt->bindValue('off', $offset, PDO::PARAM_INT);
$listStmt->execute();
$rows = $listStmt->fetchAll();

$pageTitle = 'Subcontractors';
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
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">Subcontractors</h5>
        <span class="badge bg-secondary"><?= $total ?></span>
    </div>
    <?php if (can('subcontractors', 'create')): ?>
    <a href="form.php" class="btn btn-dark btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Subcontractor
    </a>
    <?php endif; ?>
</div>

<!-- Search -->
<form method="get" class="mb-3 d-flex gap-2" style="max-width:400px">
    <input type="text" name="search" class="form-control form-control-sm"
           placeholder="Search subcontractor name..." value="<?= h($search) ?>">
    <button type="submit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-search"></i>
    </button>
    <?php if ($search !== ''): ?>
    <a href="list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-x-lg"></i>
    </a>
    <?php endif; ?>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Subcon Name</th>
                    <th class="text-center">PO Count</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td class="fw-semibold"><?= h($row['subcon_name']) ?></td>
                <td class="text-center">
                    <?php if ($row['po_count'] > 0): ?>
                    <span class="badge bg-primary"><?= (int)$row['po_count'] ?></span>
                    <?php else: ?>
                    <span class="text-muted">0</span>
                    <?php endif; ?>
                </td>
                <td class="text-end" style="white-space:nowrap">
                    <?php if (can('subcontractors', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$row['subcon_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('subcontractors', 'delete') && (int)$row['po_count'] === 0): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete subcontractor &quot;<?= h($row['subcon_name']) ?>&quot;?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_subcon">
                        <input type="hidden" name="id" value="<?= (int)$row['subcon_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php elseif (can('subcontractors', 'delete') && (int)$row['po_count'] > 0): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" disabled
                            title="Cannot delete: linked to <?= (int)$row['po_count'] ?> PO(s)">
                        <i class="bi bi-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="3" class="text-center text-muted py-4">
                    <?= $search !== '' ? 'No subcontractors match your search.' : 'No subcontractors found.' ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
