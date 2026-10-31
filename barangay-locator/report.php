<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';
public_boot();

$type = in_array($_GET['type'] ?? '', ['new', 'correction', 'report_incorrect'], true) ? $_GET['type'] : 'new';
$estId = isset($_GET['establishment_id']) && ctype_digit((string)$_GET['establishment_id']) ? (int)$_GET['establishment_id'] : 0;
$cats = []; $barangays = []; $est = null;
try {
    $cats = repo_categories();
    $barangays = repo_barangays();
    if ($estId) { $est = repo_find($estId); }
} catch (Throwable $e) { error_log($e->getMessage()); }
if ($type !== 'new' && !$est) { $type = 'new'; $estId = 0; }

page_header(t('report.title'), '');
?>
<h1><?= h(t('report.title')) ?></h1>
<p class="muted"><?= h(t('report.intro')) ?></p>
<?php if ($est): ?><div class="notice info"><strong><?= h($est['name']) ?></strong></div><?php endif; ?>

<form id="report-form" method="post" action="#" novalidate>
  <?php if (!$est): ?>
  <label for="request_type"><?= h(t('report.type')) ?></label>
  <select id="request_type" name="request_type">
    <option value="new" selected><?= h(t('report.type_new')) ?></option>
  </select>
  <label for="barangay_id"><?= h(t('report.barangay')) ?></label>
  <select id="barangay_id" name="barangay_id">
    <?php foreach ($barangays as $b): ?><option value="<?= (int)$b['id'] ?>"><?= h($b['name']) ?></option><?php endforeach; ?>
  </select>
  <?php else: ?>
  <input type="hidden" name="establishment_id" value="<?= (int)$est['id'] ?>">
  <label for="request_type"><?= h(t('report.type')) ?></label>
  <select id="request_type" name="request_type">
    <option value="correction" <?= $type === 'correction' ? 'selected' : '' ?>><?= h(t('report.type_correction')) ?></option>
    <option value="report_incorrect" <?= $type === 'report_incorrect' ? 'selected' : '' ?>><?= h(t('report.type_incorrect')) ?></option>
  </select>
  <?php endif; ?>

  <label for="name"><?= h(t('report.name')) ?></label>
  <input type="text" id="name" name="name" maxlength="160" value="<?= h($est['name'] ?? '') ?>">
  <label for="category_id"><?= h(t('report.category')) ?></label>
  <select id="category_id" name="category_id">
    <option value="">—</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= ($est && $est['category']['id'] === $c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
  </select>
  <label for="address"><?= h(t('report.address')) ?></label>
  <input type="text" id="address" name="address" maxlength="255" value="<?= h($est['address'] ?? '') ?>">
  <label for="landmark"><?= h(t('report.landmark')) ?></label>
  <input type="text" id="landmark" name="landmark" maxlength="255" value="<?= h($est['landmark'] ?? '') ?>">
  <label for="zone"><?= h(t('report.zone')) ?></label>
  <input type="text" id="zone" name="zone" maxlength="80" value="<?= h($est['zone'] ?? '') ?>">
  <label for="phone"><?= h(t('report.phone')) ?></label>
  <input type="tel" id="phone" name="phone" maxlength="60">
  <label for="message"><?= h(t('report.message')) ?></label>
  <textarea id="message" name="message" rows="4" maxlength="1000"></textarea>
  <label for="submitter_name"><?= h(t('report.your_name')) ?></label>
  <input type="text" id="submitter_name" name="submitter_name" maxlength="120">
  <label for="submitter_contact"><?= h(t('report.your_contact')) ?></label>
  <input type="text" id="submitter_contact" name="submitter_contact" maxlength="190">
  <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
  <div id="report-msg" class="notice" role="status" hidden></div>
  <p><button type="submit" id="report-submit" data-sending="<?= h(t('report.sending')) ?>"><?= h(t('report.submit')) ?></button></p>
</form>
<?php page_footer(['scripts' => '<script src="' . h(url('assets/js/report.js')) . '"></script>']); ?>
