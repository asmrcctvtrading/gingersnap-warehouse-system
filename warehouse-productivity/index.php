<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';

$u = requireLogin();

$scope = userWarehouseScope();
if ($scope === null) {
    $scopeText = 'All warehouses';
} elseif ($scope === []) {
    $scopeText = 'No warehouse assigned - ask an administrator';
} else {
    $in = implode(',', array_fill(0, count($scope), '?'));
    $st = db()->prepare("SELECT name FROM warehouses WHERE id IN ($in) ORDER BY name");
    $st->execute($scope);
    $scopeText = implode(', ', $st->fetchAll(PDO::FETCH_COLUMN));
}

page_header('Dashboard', 'dashboard');
?>
<h1>Dashboard</h1>
<div class="card">
  <p>Signed in as <strong><?= h($u['full_name']) ?></strong> (<?= h($u['role']) ?>).</p>
  <p class="muted">Warehouse access: <?= h($scopeText) ?></p>
</div>
<div class="alert alert-info">
  Only sign-in, access control and password change are built so far. Employees, picking, packing, imports and reports are not available yet.
</div>
<?php page_footer();
