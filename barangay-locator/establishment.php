<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
$e = null; $err = false;
try {
    $e = $id ? repo_find($id) : null;
} catch (Throwable $ex) {
    error_log($ex->getMessage());
    $err = true;
}

if (!$e) {
    http_response_code($err ? 500 : 404);
    page_header(t('est.not_found'), 'search');
    echo '<div class="notice ' . ($err ? 'error' : 'info') . '" role="alert">' . h($err ? t('search.error') : t('est.not_found')) . '</div>';
    echo '<p><a class="btn secondary" href="' . h(url('search.php')) . '">' . h(t('common.back')) . '</a></p>';
    page_footer();
    exit;
}

page_header($e['name'], 'search');
?>
<p><a href="<?= h(url('search.php')) ?>">← <?= h(t('common.back')) ?></a></p>
<h1><?= h($e['name']) ?></h1>
<p class="badges">
  <span class="chip" style="--c:<?= h($e['category']['color']) ?>"><span aria-hidden="true"><?= icon_glyph($e['category']['icon']) ?></span> <?= h($e['category']['name']) ?></span>
  <?php if ($e['verified']): ?><span class="badge ok"><?= h(t('status.verified')) ?></span>
  <?php else: ?><span class="badge warn"><?= h(t('status.pending_verification')) ?></span><?php endif; ?>
  <?php if ($e['emergency']): ?><span class="badge danger"><?= h(t('est.emergency_badge')) ?></span><?php endif; ?>
  <?php if ($e['is_sample']): ?><span class="badge sample">SAMPLE</span><?php endif; ?>
</p>
<?php if ($e['description']): ?><p><?= nl2br(h($e['description'])) ?></p><?php endif; ?>

<dl class="facts">
  <?php if ($e['address']): ?><dt><?= h(t('est.address')) ?></dt><dd><?= h($e['address']) ?>, <?= h($e['barangay']) ?></dd><?php endif; ?>
  <?php if ($e['landmark']): ?><dt><?= h(t('est.landmark')) ?></dt><dd><?= h($e['landmark']) ?></dd><?php endif; ?>
  <?php if ($e['zone']): ?><dt><?= h(t('est.zone')) ?></dt><dd><?= h($e['zone']) ?></dd><?php endif; ?>
  <?php if ($e['services']): ?><dt><?= h(t('est.services')) ?></dt><dd><?= h($e['services']) ?></dd><?php endif; ?>
  <?php foreach ($e['contacts'] as $c): ?>
    <dt><?= h(t('est.contact')) ?></dt>
    <dd><?php
      $v = $c['contact_value'];
      if ($c['contact_type'] === 'email' && valid_email($v)) { echo '<a href="mailto:' . h($v) . '">' . h($v) . '</a>'; }
      elseif (in_array($c['contact_type'], ['phone', 'mobile'], true)) { echo '<a href="tel:' . h(preg_replace('/[^0-9+]/', '', $v)) . '">' . h($v) . '</a>'; }
      elseif (valid_http_url($v)) { echo '<a href="' . h($v) . '" rel="noopener noreferrer" target="_blank">' . h($v) . '</a>'; }
      else { echo h($v); }
    ?></dd>
  <?php endforeach; ?>
  <dt><?= h(t('est.last_verified')) ?></dt>
  <dd><?= $e['last_verified_at'] ? h($e['last_verified_at']) : h(t('est.never_verified')) ?></dd>
</dl>

<?php if ($e['directions']): ?>
  <h2><?= h(t('est.how_to_get_there')) ?></h2>
  <p><?= nl2br(h($e['directions'])) ?></p>
  <p class="muted"><?= h(t('est.how_to_note')) ?></p>
<?php endif; ?>

<h2><?= h(t('est.hours')) ?></h2>
<?php if ($e['hours']): ?>
  <table class="hours"><tbody>
  <?php foreach ($e['hours'] as $hr): ?>
    <tr><td><?= h(t('day.' . $hr['day'])) ?></td><td><?php
      if ($hr['closed']) { echo h(t('hours.closed')); }
      elseif ($hr['is_24h']) { echo h(t('hours.24h')); }
      else { echo h(($hr['opens'] ?? '?') . ' – ' . ($hr['closes'] ?? '?')); }
    ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
<?php else: ?>
  <p class="muted"><?= h($e['hours_note'] ?: t('est.hours_none')) ?></p>
<?php endif; ?>

<?php if ($e['plottable']): ?>
  <?php if (!$e['coords_verified']): ?><div class="notice"><?= h(t('est.unverified_coords')) ?></div><?php endif; ?>
  <div class="toolbar">
    <a class="btn" href="<?= h(url('map.php?focus=' . $e['id'])) ?>">🗺️ <?= h(t('est.view_map')) ?></a>
    <a class="btn secondary" href="<?= h(url('directions.php?id=' . $e['id'])) ?>"><?= h(t('est.directions')) ?></a>
  </div>
<?php else: ?>
  <div class="notice info"><?= h(t('est.no_coords')) ?></div>
<?php endif; ?>
<?php if ($e['osm_sourced']): ?><p class="muted">Place data from <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap contributors</a>.</p><?php endif; ?>
<p><a href="<?= h(url('report.php?type=report_incorrect&establishment_id=' . $e['id'])) ?>"><?= h(t('est.report')) ?></a></p>
<?php page_footer(); ?>
