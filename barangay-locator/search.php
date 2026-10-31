<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$f = [];
$q = clean_str($_GET['q'] ?? null, 100);
if ($q !== null) { $f['q'] = $q; }
foreach (['category_id', 'barangay_id'] as $k) {
    if (isset($_GET[$k]) && ctype_digit((string)$_GET[$k])) { $f[$k] = (int)$_GET[$k]; }
}
$zone = clean_str($_GET['zone'] ?? null, 80);
if ($zone !== null) { $f['zone'] = $zone; }
if (isset($_GET['classification']) && in_array($_GET['classification'], ['public', 'private'], true)) {
    $f['classification'] = $_GET['classification'];
}
$perPage = 24;
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$res = null; $cats = []; $zones = []; $err = false;
try {
    $cats  = repo_categories();
    $zones = repo_zones($f['barangay_id'] ?? null);
    $res   = repo_search($f, $perPage, ($page - 1) * $perPage);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $err = true;
}

$qsBase = $_GET; unset($qsBase['page']);
page_header(t('search.title'), 'search');
?>
<h1><?= h(t('search.title')) ?></h1>
<form method="get" class="filters" role="search">
  <input type="search" name="q" value="<?= h($q ?? '') ?>" placeholder="<?= h(t('home.search_ph')) ?>" aria-label="<?= h(t('home.search_ph')) ?>" maxlength="100">
  <select name="category_id" aria-label="<?= h(t('est.category')) ?>">
    <option value=""><?= h(t('search.all_categories')) ?></option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (($f['category_id'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="zone" aria-label="<?= h(t('est.zone')) ?>">
    <option value=""><?= h(t('search.all_zones')) ?></option>
    <?php foreach ($zones as $z): ?><option <?= ($f['zone'] ?? '') === $z ? 'selected' : '' ?>><?= h($z) ?></option><?php endforeach; ?>
  </select>
  <select name="classification" aria-label="<?= h(t('search.any_type')) ?>">
    <option value=""><?= h(t('search.any_type')) ?></option>
    <option value="public" <?= ($f['classification'] ?? '') === 'public' ? 'selected' : '' ?>><?= h(t('search.public')) ?></option>
    <option value="private" <?= ($f['classification'] ?? '') === 'private' ? 'selected' : '' ?>><?= h(t('search.private')) ?></option>
  </select>
  <button type="submit"><?= h(t('home.search_btn')) ?></button>
</form>

<?php if ($err): ?>
  <div class="notice error" role="alert"><?= h(t('search.error')) ?></div>
<?php elseif (!$res['items']): ?>
  <div class="notice info"><?= h(t('search.none')) ?></div>
<?php else: ?>
  <p class="muted" aria-live="polite"><?= h(t('search.results', ['n' => $res['total']])) ?></p>
  <div class="grid">
    <?php foreach ($res['items'] as $e) { render_card($e); } ?>
  </div>
  <?php $pages = (int)ceil($res['total'] / $perPage); if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a class="btn secondary" href="?<?= h(http_build_query($qsBase + ['page' => $page - 1])) ?>"><?= h(t('search.prev')) ?></a><?php else: ?><span></span><?php endif; ?>
    <span class="muted"><?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn secondary" href="?<?= h(http_build_query($qsBase + ['page' => $page + 1])) ?>"><?= h(t('search.next')) ?></a><?php else: ?><span></span><?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php page_footer(); ?>
