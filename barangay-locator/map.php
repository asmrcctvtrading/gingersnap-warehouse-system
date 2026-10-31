<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$cats = []; $err = false;
try { $cats = repo_categories(); } catch (Throwable $e) { error_log($e->getMessage()); $err = true; }

// Prefer a vendored Leaflet (assets/vendor/leaflet/) when present; otherwise the pinned CDN build with SRI.
$localLeaflet = is_file(__DIR__ . '/assets/vendor/leaflet/leaflet.js');
if ($localLeaflet) {
    $head = '<link rel="stylesheet" href="' . h(url('assets/vendor/leaflet/leaflet.css')) . '">';
    $leaf = '<script src="' . h(url('assets/vendor/leaflet/leaflet.js')) . '"></script>';
} else {
    $head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">';
    $leaf = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>';
}
$scripts = $leaf . '<script src="' . h(url('assets/js/map.js')) . '"></script>';

page_header(t('map.title'), 'map', ['head' => $head]);
?>
<h1><?= h(t('map.title')) ?></h1>
<form class="filters" id="map-filters" role="search">
  <input type="search" name="q" placeholder="<?= h(t('home.search_ph')) ?>" aria-label="<?= h(t('home.search_ph')) ?>" maxlength="100">
  <select name="category_id" aria-label="<?= h(t('est.category')) ?>">
    <option value=""><?= h(t('search.all_categories')) ?></option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
  </select>
  <button type="submit"><?= h(t('home.search_btn')) ?></button>
  <button type="button" class="secondary" id="locate-btn">📍 <?= h(t('map.locate')) ?></button>
</form>
<div id="map-msg" class="notice info" role="status" hidden></div>
<div id="boundary-note" class="notice info" hidden></div>
<div id="tile-warn" class="notice" hidden><?= h(t('map.tiles_dev')) ?></div>
<div class="toolbar mobile-only"><button type="button" class="secondary" id="toggle-view"><?= h(t('map.list_view')) ?></button></div>
<div class="mapwrap" data-focus="<?= isset($_GET['focus']) && ctype_digit((string)$_GET['focus']) ? (int)$_GET['focus'] : 0 ?>"
     data-autolocate="<?= !empty($_GET['locate']) ? '1' : '0' ?>"
     data-t-loading="<?= h(t('map.loading')) ?>" data-t-none="<?= h(t('map.no_points')) ?>"
     data-t-denied="<?= h(t('map.locate_denied')) ?>" data-t-unavail="<?= h(t('map.locate_unavailable')) ?>"
     data-t-straight="<?= h(t('map.straight_line')) ?>" data-t-err="<?= h(t('search.error')) ?>"
     data-t-details="<?= h(t('est.view_map')) ?>" data-t-open="<?= h(t('est.details')) ?>"data-t-dir="<?= h(t('est.directions')) ?>"
     data-t-boundary="<?= h(t('map.boundary_note', ['source' => '{source}'])) ?>"
     data-t-list="<?= h(t('map.list_view')) ?>" data-t-map="<?= h(t('map.map_view')) ?>">
  <aside class="maplist" id="maplist" aria-label="<?= h(t('search.title')) ?>"></aside>
  <div id="map" role="application" aria-label="<?= h(t('map.title')) ?>"><noscript>Map needs JavaScript. Use the Search page instead.</noscript></div>
</div>
<style>.mobile-only{display:none}@media (max-width:820px){.mobile-only{display:flex}.mapwrap.show-map .maplist{display:none}.mapwrap.show-list #map{display:none}}</style>
<?php page_footer(['scripts' => $scripts]); ?>
