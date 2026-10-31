<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('settings.manage');   // admin only

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['op'] ?? '') === 'remove_sample') {
        if (($_POST['confirm'] ?? '') !== 'REMOVE SAMPLE DATA') {
            flash('error', 'Type REMOVE SAMPLE DATA exactly to confirm.');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $n1 = $pdo->exec('DELETE FROM establishments WHERE is_sample = 1');
                $n2 = $pdo->exec('DELETE FROM barangays WHERE is_sample = 1 AND NOT EXISTS (SELECT 1 FROM (SELECT barangay_id FROM establishments) x WHERE x.barangay_id = barangays.barangay_id)');
                $pdo->commit();
                logAudit('remove_sample', 'settings', null, "establishments=$n1 barangays=$n2");
                flash('ok', "Removed $n1 sample establishment(s) and $n2 sample barangay(s).");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log($e->getMessage());
                flash('error', 'Could not remove sample data (real records may still reference the sample barangay).');
            }
        }
        redirect('admin/settings.php');
    }

    $errs = [];
    $new = [];
    $new['site_title'] = clean_str($_POST['site_title'] ?? null, 120) ?? 'Barangay Establishment Locator';
    $new['default_language'] = ($_POST['default_language'] ?? 'en') === 'tl' ? 'tl' : 'en';
    $new['public_show_unverified'] = !empty($_POST['public_show_unverified']) ? '1' : '0';
    $new['plot_unverified_coords'] = !empty($_POST['plot_unverified_coords']) ? '1' : '0';
    $new['show_sample_banner'] = !empty($_POST['show_sample_banner']) ? '1' : '0';
    $new['kiosk_idle_seconds'] = (string)max(15, min(900, (int)($_POST['kiosk_idle_seconds'] ?? 90)));
    $new['reports_per_ip_per_day'] = (string)max(1, min(100, (int)($_POST['reports_per_ip_per_day'] ?? 5)));

    $tile = trim((string)($_POST['tile_url'] ?? ''));
    if (!preg_match('#^https://#', $tile) || strpos($tile, '{z}') === false || strpos($tile, '{x}') === false || strpos($tile, '{y}') === false) {
        $errs[] = 'Tile URL must be https and contain {z}, {x} and {y}.';
    } else { $new['tile_url'] = $tile; }
    $new['tile_attribution'] = mb_substr(trim((string)($_POST['tile_attribution'] ?? '')), 0, 500);
    if ($new['tile_attribution'] === '') { $errs[] = 'Tile attribution is required by every tile provider.'; }
    $new['tile_is_production_ok'] = !empty($_POST['tile_is_production_ok']) ? '1' : '0';

    $new['routing_provider'] = in_array($_POST['routing_provider'] ?? '', ['none', 'external_link'], true) ? $_POST['routing_provider'] : 'external_link';
    $ext = trim((string)($_POST['external_nav_url'] ?? ''));
    if ($ext !== '' && !valid_http_url(str_replace(['{from_lat}', '{from_lng}', '{to_lat}', '{to_lng}'], '0', $ext))) { $errs[] = 'External navigation URL is not valid.'; }
    else { $new['external_nav_url'] = $ext; }

    if ($errs) { foreach ($errs as $m) { flash('error', $m); } }
    else {
        foreach ($new as $k => $v) { set_setting($k, $v); }
        logAudit('update', 'settings', null, implode(',', array_keys($new)));
        flash('ok', 'Settings saved.');
    }
    redirect('admin/settings.php');
}

$s = static fn(string $k, string $d = '') => h(setting($k, $d) ?? $d);
$sample = (int)db()->query('SELECT COUNT(*) FROM establishments WHERE is_sample = 1')->fetchColumn();

admin_header('Settings', 'settings');
?>
<h1>Settings</h1>
<form method="post">
  <?= csrfField() ?>
  <div class="formgrid">
    <div><label for="site_title">Site title</label><input type="text" id="site_title" name="site_title" maxlength="120" value="<?= $s('site_title') ?>"></div>
    <div><label for="default_language">Default language</label>
      <select id="default_language" name="default_language"><option value="en">English</option><option value="tl" <?= setting('default_language') === 'tl' ? 'selected' : '' ?>>Tagalog</option></select></div>
    <div><label for="kiosk_idle_seconds">Kiosk idle reset (seconds)</label><input type="number" id="kiosk_idle_seconds" name="kiosk_idle_seconds" min="15" max="900" value="<?= $s('kiosk_idle_seconds', '90') ?>"></div>
    <div><label for="reports_per_ip_per_day">Public submissions per visitor per day</label><input type="number" id="reports_per_ip_per_day" name="reports_per_ip_per_day" min="1" max="100" value="<?= $s('reports_per_ip_per_day', '5') ?>"></div>
  </div>
  <label class="check"><input type="checkbox" name="public_show_unverified" value="1" <?= setting('public_show_unverified', '1') === '1' ? 'checked' : '' ?>> Show records awaiting verification publicly (labelled “Awaiting verification”)</label>
  <label class="check"><input type="checkbox" name="plot_unverified_coords" value="1" <?= setting('plot_unverified_coords', '0') === '1' ? 'checked' : '' ?>> Plot map positions that staff have not yet checked</label>
  <label class="check"><input type="checkbox" name="show_sample_banner" value="1" <?= setting('show_sample_banner', '1') === '1' ? 'checked' : '' ?>> Show the “demonstration data” banner while sample records exist</label>

  <fieldset><legend>Map tiles</legend>
    <p class="muted">The default is the public OpenStreetMap tile server, which is meant for light/development use. For a busy public site or kiosk, use a provider whose terms allow it (a paid/hosted tile service, or your own tile server) and keep its attribution.</p>
    <label for="tile_url">Tile URL</label><input type="text" id="tile_url" name="tile_url" value="<?= $s('tile_url') ?>">
    <label for="tile_attribution">Attribution (HTML allowed)</label><input type="text" id="tile_attribution" name="tile_attribution" value="<?= $s('tile_attribution') ?>">
    <label class="check"><input type="checkbox" name="tile_is_production_ok" value="1" <?= setting('tile_is_production_ok', '0') === '1' ? 'checked' : '' ?>> I confirm this tile provider permits our production use (hides the warning)</label>
  </fieldset>

  <fieldset><legend>Directions</legend>
    <label for="routing_provider">Mode</label>
    <select id="routing_provider" name="routing_provider">
      <option value="external_link" <?= setting('routing_provider') !== 'none' ? 'selected' : '' ?>>Open an external map app (default)</option>
      <option value="none" <?= setting('routing_provider') === 'none' ? 'selected' : '' ?>>Do not offer directions</option>
    </select>
    <label for="external_nav_url">External directions URL template</label>
    <input type="text" id="external_nav_url" name="external_nav_url" value="<?= $s('external_nav_url') ?>">
    <p class="muted">Leave empty to use Google Maps (no API key needed). Placeholders: {from_lat} {from_lng} {to_lat} {to_lng}. Built-in routing (OSRM) is not implemented in this version.</p>
  </fieldset>
  <p><button type="submit">Save settings</button></p>
</form>

<h2>Sample data</h2>
<?php if ($sample): ?>
<form method="post">
  <?= csrfField() ?><input type="hidden" name="op" value="remove_sample">
  <p><?= $sample ?> fictional sample establishment(s) exist. Remove them before real use.</p>
  <label for="confirm">Type REMOVE SAMPLE DATA to confirm</label>
  <input type="text" id="confirm" name="confirm" autocomplete="off" style="max-width:320px">
  <p><button type="submit" class="danger-btn">Remove sample data</button></p>
</form>
<?php else: ?><p class="muted">No sample data present.</p><?php endif; ?>
<?php admin_footer(); ?>
