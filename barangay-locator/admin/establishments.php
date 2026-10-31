<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('establishments.view');

$user = currentUser();

// Status / verification actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $needsVerify = ['verify', 'flag_review', 'deactivate', 'reactivate', 'coords_verified', 'emergency_verified'];
    if (in_array($action, $needsVerify, true)) {
        requirePermission('establishments.verify');
        [$ok, $msg] = est_apply_action($id, $action, $_POST['evidence'] ?? null, $user);
        flash($ok ? 'ok' : 'error', $msg);
    }
    redirect('admin/establishments.php?' . http_build_query(array_filter([
        'status' => $_POST['f_status'] ?? '', 'q' => $_POST['f_q'] ?? '',
    ], static fn($v) => $v !== '')));
}

$status = in_array($_GET['status'] ?? '', ESTABLISHMENT_STATUSES, true) ? $_GET['status'] : '';
$q = clean_str($_GET['q'] ?? null, 100) ?? '';
$where = ['1=1']; $params = [];
$scope = userBarangayScope();
if ($scope !== null) { $where[] = 'e.barangay_id = ?'; $params[] = $scope; }
if ($status !== '') { $where[] = 'e.status = ?'; $params[] = $status; }
if ($q !== '') {
    $where[] = '(e.name LIKE ? OR e.street_address LIKE ?)';
    $like = '%' . like_escape($q) . '%';
    array_push($params, $like, $like);
}
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per = 40;
$cnt = db()->prepare('SELECT COUNT(*) FROM establishments e WHERE ' . implode(' AND ', $where));
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$st = db()->prepare('SELECT e.*, c.name_en AS cat, b.name AS brgy, COALESCE(f.is_emergency,0) AS is_em, f.emergency_verified_at AS em_ver
    FROM establishments e JOIN categories c ON c.category_id = e.primary_category_id JOIN barangays b ON b.barangay_id = e.barangay_id
    LEFT JOIN facilities f ON f.establishment_id = e.establishment_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY e.updated_at DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
$st->execute($params);
$rows = $st->fetchAll();

admin_header('Establishments', 'est');
?>
<h1>Establishments <small class="muted">(<?= $total ?>)</small></h1>
<form method="get" class="filters">
  <input type="search" name="q" value="<?= h($q) ?>" placeholder="Name or address" aria-label="Search">
  <select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <?php foreach (ESTABLISHMENT_STATUSES as $s): ?><option value="<?= h($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= h(t('status.' . $s)) ?></option><?php endforeach; ?>
  </select>
  <button type="submit">Filter</button>
  <?php if (can('establishments.create')): ?><a class="btn secondary" href="<?= h(url('admin/establishment_edit.php')) ?>">+ Add</a><?php endif; ?>
</form>

<div class="tablewrap"><table class="data">
<thead><tr><th>Name</th><th>Category</th><th>Barangay</th><th>Status</th><th>Map</th><th>Actions</th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="6">No records.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<tr>
  <td><a href="<?= h(url('admin/establishment_edit.php?id=' . $r['establishment_id'])) ?>"><?= h($r['name']) ?></a>
      <?= $r['is_sample'] ? '<span class="badge sample">SAMPLE</span>' : '' ?>
      <?= $r['is_em'] ? ($r['em_ver'] ? '<span class="badge danger">Emergency ✓</span>' : '<span class="badge warn">Emergency (unverified)</span>') : '' ?></td>
  <td><?= h($r['cat']) ?></td>
  <td><?= h($r['brgy']) ?></td>
  <td><?= h(t('status.' . $r['status'])) ?><?= $r['last_verified_at'] ? '<br><span class="muted">' . h($r['last_verified_at']) . '</span>' : '' ?></td>
  <td><?php
    if ($r['latitude'] === null) { echo 'none'; }
    else { echo $r['coords_verified'] ? 'checked' : '<span class="badge warn">not checked</span>'; }
  ?></td>
  <td>
    <?php if (can('establishments.verify')): ?>
    <form method="post" class="row-actions">
      <?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['establishment_id'] ?>">
      <input type="hidden" name="f_status" value="<?= h($status) ?>"><input type="hidden" name="f_q" value="<?= h($q) ?>">
      <input type="text" name="evidence" placeholder="Evidence / note" maxlength="255" aria-label="Evidence" style="min-width:160px">
      <?php if ($r['status'] !== 'verified' && $r['status'] !== 'inactive'): ?><button name="action" value="verify">Verify</button><?php endif; ?>
      <?php if ($r['latitude'] !== null && !$r['coords_verified']): ?><button name="action" value="coords_verified" class="secondary">Confirm map position</button><?php endif; ?>
      <?php if ($r['is_em'] && !$r['em_ver']): ?><button name="action" value="emergency_verified" class="secondary">Confirm emergency role</button><?php endif; ?>
      <?php if ($r['status'] !== 'needs_review' && $r['status'] !== 'inactive'): ?><button name="action" value="flag_review" class="secondary">Flag</button><?php endif; ?>
      <?php if ($r['status'] === 'inactive'): ?><button name="action" value="reactivate" class="secondary">Reactivate</button>
      <?php else: ?><button name="action" value="deactivate" class="secondary">Deactivate</button><?php endif; ?>
    </form>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<?php $pages = (int)ceil($total / $per); if ($pages > 1): $qs = $_GET; unset($qs['page']); ?>
<div class="pager">
  <?php if ($page > 1): ?><a class="btn secondary" href="?<?= h(http_build_query($qs + ['page' => $page - 1])) ?>">Previous</a><?php else: ?><span></span><?php endif; ?>
  <span class="muted"><?= $page ?> / <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn secondary" href="?<?= h(http_build_query($qs + ['page' => $page + 1])) ?>">Next</a><?php endif; ?>
</div>
<?php endif; ?>
<?php admin_footer(); ?>
