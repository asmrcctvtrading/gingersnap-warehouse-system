<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$cats = []; $err = false;
try { $cats = repo_categories(); } catch (Throwable $e) { error_log($e->getMessage()); $err = true; }

page_header(t('cat.title'), 'categories');
?>
<h1><?= h(t('cat.title')) ?></h1>
<?php if ($err): ?><div class="notice error" role="alert"><?= h(t('search.error')) ?></div><?php endif; ?>
<div class="grid">
  <?php foreach ($cats as $c): ?>
    <a class="tile" href="<?= h(url('search.php?category_id=' . $c['id'])) ?>"><span class="ico" aria-hidden="true"><?= icon_glyph($c['icon']) ?></span><span><?= h($c['name']) ?></span></a>
  <?php endforeach; ?>
</div>
<?php page_footer(); ?>
