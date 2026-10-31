<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

page_header(t('about.title'), 'about');
?>
<h1><?= h(t('about.title')) ?></h1>
<p><?= h(t('about.body')) ?></p>
<p><a class="btn secondary" href="<?= h(url('report.php')) ?>"><?= h(t('nav.report')) ?></a></p>
<?php page_footer(); ?>
