<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requireLogin();

$scope = userBarangayScope();
$w = $scope !== null ? ' WHERE barangay_id = ' . (int)$scope : '';
$counts = db()->query("SELECT status, COUNT(*) c FROM establishments$w GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$noCoords = (int)db()->query("SELECT COUNT(*) FROM establishments" . ($w ?: ' WHERE 1=1') . " AND status <> 'inactive' AND latitude IS NULL")->fetchColumn();
$unverCoords = (int)db()->query("SELECT COUNT(*) FROM establishments" . ($w ?: ' WHERE 1=1') . " AND status <> 'inactive' AND latitude IS NOT NULL AND coords_verified = 0")->fetchColumn();
$pendingReq = 0;
if (can('requests.review')) {
    $q = $scope !== null ? 'SELECT COUNT(*) FROM establishment_change_requests WHERE status = "pending" AND barangay_id = ' . (int)$scope
                         : 'SELECT COUNT(*) FROM establishment_change_requests WHERE status = "pending"';
    $pendingReq = (int)db()->query($q)->fetchColumn();
}

admin_header('Dashboard', 'dash');
?>
<h1>Dashboard</h1>
<div class="grid">
  <div class="card"><div class="stat"><?= (int)($counts['verified'] ?? 0) ?></div>Verified</div>
  <div class="card"><div class="stat"><?= (int)($counts['pending_verification'] ?? 0) ?></div>Awaiting verification</div>
  <div class="card"><div class="stat"><?= (int)($counts['needs_review'] ?? 0) ?></div>Needs review</div>
  <div class="card"><div class="stat"><?= (int)($counts['inactive'] ?? 0) ?></div>Inactive</div>
  <div class="card"><div class="stat"><?= $noCoords ?></div>No coordinates (not on map)</div>
  <div class="card"><div class="stat"><?= $unverCoords ?></div>Coordinates not yet checked</div>
  <?php if (can('requests.review')): ?>
  <a class="card" href="<?= h(url('admin/requests.php')) ?>" style="text-decoration:none;color:inherit"><div class="stat"><?= $pendingReq ?></div>Public requests waiting</a>
  <?php endif; ?>
</div>
<p class="toolbar">
  <?php if (can('establishments.create')): ?><a class="btn" href="<?= h(url('admin/establishment_edit.php')) ?>">+ Add establishment</a><?php endif; ?>
  <a class="btn secondary" href="<?= h(url('admin/establishments.php?status=pending_verification')) ?>">Review unverified</a>
</p>
<?php admin_footer(); ?>
