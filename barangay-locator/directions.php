<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
$e = null;
try { $e = $id ? repo_find($id) : null; } catch (Throwable $ex) { error_log($ex->getMessage()); }

if (!$e || !$e['plottable']) {
    http_response_code($e ? 200 : 404);
    page_header(t('dir.title'), 'map');
    echo '<div class="notice info">' . h($e ? t('est.no_coords') : t('est.not_found')) . '</div>';
    echo '<p><a class="btn secondary" href="' . h(url('search.php')) . '">' . h(t('common.back')) . '</a></p>';
    page_footer();
    exit;
}

// Routing: v1 hands off to an external map app. Distances shown here are straight-line only.
$tpl = (string)setting('external_nav_url', '');
// Default: Google Maps. Without an origin, Google Maps asks for / uses the visitor's own start point.
$tpl = valid_http_url($tpl) ? $tpl : 'https://www.google.com/maps/dir/?api=1&origin={from_lat}%2C{from_lng}&destination={to_lat}%2C{to_lng}';
// Destination-only link (before the visitor shares a location): drop the origin/from parameter.
$destOnly = str_replace(['{to_lat}', '{to_lng}'], [(string)$e['lat'], (string)$e['lng']],
    preg_replace('/\b(?:origin|from)=\{from_lat\}%2C\{from_lng\}&?/', '', $tpl));

page_header(t('dir.title'), 'map');
?>
<p><a href="<?= h(url('establishment.php?id=' . $e['id'])) ?>">← <?= h(t('common.back')) ?></a></p>
<h1><?= h(t('dir.title')) ?>: <?= h($e['name']) ?></h1>
<?php if ($e['address']): ?><p class="muted"><?= h($e['address']) ?></p><?php endif; ?>
<?php if (!$e['coords_verified']): ?><div class="notice"><?= h(t('est.unverified_coords')) ?></div><?php endif; ?>
<?php if ($e['directions']): ?>
  <h2><?= h(t('est.how_to_get_there')) ?></h2>
  <p><?= nl2br(h($e['directions'])) ?></p>
  <p class="muted"><?= h(t('est.how_to_note')) ?></p>
<?php endif; ?>

<div id="dir" data-lat="<?= h((string)$e['lat']) ?>" data-lng="<?= h((string)$e['lng']) ?>" data-tpl="<?= h($tpl) ?>"
     data-t-straight="<?= h(t('dir.straight', ['m' => '{m}'])) ?>" data-t-denied="<?= h(t('map.locate_denied')) ?>" data-t-unavail="<?= h(t('map.locate_unavailable')) ?>">
  <div class="toolbar">
    <a class="btn" id="ext-link" href="<?= h($destOnly) ?>" rel="noopener noreferrer" target="_blank"><?= h(t('dir.external')) ?></a>
    <button type="button" class="secondary" id="dir-locate">📍 <?= h(t('map.locate')) ?></button>
  </div>
  <div id="dir-msg" class="notice info" role="status" hidden></div>
</div>
<p class="muted"><?= h(t('map.straight_line')) ?>.</p>
<?php page_footer(['scripts' => '<script src="' . h(url('assets/js/directions.js')) . '"></script>']); ?>
