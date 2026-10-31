<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('audit.view');

$action = clean_str($_GET['action'] ?? null, 60) ?? '';
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per = 100;
$params = []; $where = '1=1';
if ($action !== '') { $where .= ' AND action = ?'; $params[] = $action; }
$st = db()->prepare("SELECT * FROM audit_logs WHERE $where ORDER BY log_id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($params);
$rows = $st->fetchAll();
$actions = db()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

admin_header('Audit log', 'audit');
?>
<h1>Audit log</h1>
<form method="get" class="filters">
  <select name="action" aria-label="Action"><option value="">All actions</option>
    <?php foreach ($actions as $a): ?><option <?= $a === $action ? 'selected' : '' ?>><?= h($a) ?></option><?php endforeach; ?>
  </select><button type="submit">Filter</button>
</form>
<div class="tablewrap"><table class="data"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= h($r['created_at']) ?></td><td><?= h($r['username'] ?? '') ?></td><td><?= h($r['action']) ?></td>
<td><?= h($r['entity_type'] . ($r['entity_id'] ? ' #' . $r['entity_id'] : '')) ?></td><td><?= h($r['details'] ?? '') ?></td><td><?= h($r['ip_address'] ?? '') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div class="pager">
  <?php if ($page > 1): ?><a class="btn secondary" href="?<?= h(http_build_query(['action' => $action, 'page' => $page - 1])) ?>">Newer</a><?php else: ?><span></span><?php endif; ?>
  <?php if (count($rows) === $per): ?><a class="btn secondary" href="?<?= h(http_build_query(['action' => $action, 'page' => $page + 1])) ?>">Older</a><?php endif; ?>
</div>
<?php admin_footer(); ?>
