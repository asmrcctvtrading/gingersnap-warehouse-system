<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$cats = [];
$dbError = false;
try {
    $cats = repo_categories();
} catch (Throwable $e) {
    error_log($e->getMessage());
    $dbError = true;
}
$essential = array_values(array_filter($cats, static fn($c) => $c['essential']));

page_header(t('home.title'), 'home');
?>
<section class="hero">
  <h1><?= h(t('home.title')) ?></h1>
  <form class="searchbar" action="<?= h(url('search.php')) ?>" method="get" role="search">
    <input type="search" name="q" placeholder="<?= h(t('home.search_ph')) ?>" aria-label="<?= h(t('home.search_ph')) ?>" maxlength="100">
    <button type="submit"><?= h(t('home.search_btn')) ?></button>
  </form>
  <div class="toolbar">
    <a class="btn secondary" href="<?= h(url('map.php?locate=1')) ?>">📍 <?= h(t('home.near')) ?></a>
    <a class="btn secondary" href="<?= h(url('services.php#emergency')) ?>">🚨 <?= h(t('home.emergency')) ?></a>
  </div>
</section>

<?php if ($dbError): ?>
  <div class="notice error" role="alert"><?= h(t('search.error')) ?></div>
<?php else: ?>
<section aria-labelledby="ess">
  <h2 id="ess"><?= h(t('home.essential')) ?></h2>
  <div class="grid">
    <?php foreach ($essential as $c): ?>
      <a class="tile" href="<?= h(url('search.php?category_id=' . $c['id'])) ?>"><span class="ico" aria-hidden="true"><?= icon_glyph($c['icon']) ?></span><span><?= h($c['name']) ?></span></a>
    <?php endforeach; ?>
  </div>
</section>
<section aria-labelledby="all">
  <h2 id="all"><?= h(t('home.browse')) ?></h2>
  <div class="grid">
    <?php foreach ($cats as $c): if ($c['essential']) { continue; } ?>
      <a class="tile" href="<?= h(url('search.php?category_id=' . $c['id'])) ?>"><span class="ico" aria-hidden="true"><?= icon_glyph($c['icon']) ?></span><span><?= h($c['name']) ?></span></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php page_footer(); ?>
