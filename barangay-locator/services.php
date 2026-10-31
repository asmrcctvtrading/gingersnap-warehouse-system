<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$ess = ['items' => []]; $em = ['items' => []]; $err = false;
try {
    $ess = repo_search(['essential' => true], 100, 0);
    $em  = repo_search(['emergency' => true], 100, 0);   // only facilities with emergency_verified_at set
} catch (Throwable $e) { error_log($e->getMessage()); $err = true; }

page_header(t('svc.title'), 'services');
?>
<h1><?= h(t('svc.title')) ?></h1>
<?php if ($err): ?><div class="notice error" role="alert"><?= h(t('search.error')) ?></div><?php endif; ?>

<section id="emergency" aria-labelledby="em-h">
  <h2 id="em-h">🚨 <?= h(t('home.emergency')) ?></h2>
  <p class="muted"><?= h(t('svc.emergency_note')) ?></p>
  <?php if (!$em['items']): ?>
    <div class="notice info"><?= h(t('svc.emergency_none')) ?></div>
  <?php else: ?>
    <div class="grid"><?php foreach ($em['items'] as $e) { render_card($e); } ?></div>
  <?php endif; ?>
</section>

<section aria-labelledby="ess-h">
  <h2 id="ess-h"><?= h(t('home.essential')) ?></h2>
  <?php if (!$ess['items']): ?>
    <div class="notice info"><?= h(t('search.none')) ?></div>
  <?php else: ?>
    <div class="grid"><?php foreach ($ess['items'] as $e) { render_card($e); } ?></div>
  <?php endif; ?>
</section>
<?php page_footer(); ?>
