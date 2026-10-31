<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('barangays.manage');   // admin only

/** Accept only GeoJSON Polygon/MultiPolygon (or Feature/FeatureCollection of them). Returns error or null. */
function geojson_error(string $raw): ?string
{
    if (strlen($raw) > 1024 * 1024) { return 'Boundary file is larger than 1 MB.'; }
    $j = json_decode($raw, true);
    if (!is_array($j) || !isset($j['type'])) { return 'Boundary is not valid GeoJSON.'; }
    $ok = static function ($g) {
        return is_array($g) && in_array($g['type'] ?? '', ['Polygon', 'MultiPolygon'], true) && !empty($g['coordinates']);
    };
    $t = $j['type'];
    if ($t === 'Feature') { return $ok($j['geometry'] ?? null) ? null : 'Feature must contain a Polygon or MultiPolygon.'; }
    if ($t === 'FeatureCollection') {
        foreach ($j['features'] ?? [] as $f) { if (!$ok($f['geometry'] ?? null)) { return 'Every feature must be a Polygon or MultiPolygon.'; } }
        return empty($j['features']) ? 'FeatureCollection is empty.' : null;
    }
    return $ok($j) ? null : 'Only Polygon / MultiPolygon boundaries are accepted.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $bid = isset($_POST['barangay_id']) && ctype_digit((string)$_POST['barangay_id']) ? (int)$_POST['barangay_id'] : 0;
    $name = clean_str($_POST['name'] ?? null, 120);
    [$lat, $lng, $ce] = validate_coords($_POST['center_lat'] ?? null, $_POST['center_lng'] ?? null);
    $zoom = max(3, min(19, (int)($_POST['default_zoom'] ?? 16)));
    $boundary = trim((string)($_POST['boundary_geojson'] ?? ''));
    $bsrc = clean_str($_POST['boundary_source'] ?? null, 255);
    $errs = [];
    if ($name === null) { $errs[] = 'Name is required.'; }
    if ($ce) { $errs[] = $ce; }
    if ($boundary !== '') {
        if ($bsrc === null) { $errs[] = 'A boundary needs a source (who supplied it).'; }
        if ($e = geojson_error($boundary)) { $errs[] = $e; }
    } else {
        $boundary = null; $bsrc = null;
    }
    if ($errs) {
        foreach ($errs as $m) { flash('error', $m); }
    } else {
        $bver = !empty($_POST['boundary_verified']) && $boundary !== null ? 1 : 0;
        try {
            if ($bid) {
                db()->prepare('UPDATE barangays SET name=?, municipality=?, province=?, region=?, center_lat=?, center_lng=?, default_zoom=?,
                    boundary_geojson=?, boundary_source=?, boundary_verified=?, hall_address=?, hall_phone=?, is_active=? WHERE barangay_id=?')
                    ->execute([$name, clean_str($_POST['municipality'] ?? null, 120), clean_str($_POST['province'] ?? null, 120), clean_str($_POST['region'] ?? null, 120),
                        $lat, $lng, $zoom, $boundary, $bsrc, $bver, clean_str($_POST['hall_address'] ?? null, 255), clean_str($_POST['hall_phone'] ?? null, 60),
                        !empty($_POST['is_active']) ? 1 : 0, $bid]);
                logAudit('update', 'barangay', $bid, $name);
            } else {
                db()->prepare('INSERT INTO barangays (name, municipality, province, region, center_lat, center_lng, default_zoom,
                    boundary_geojson, boundary_source, boundary_verified, hall_address, hall_phone, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)')
                    ->execute([$name, clean_str($_POST['municipality'] ?? null, 120), clean_str($_POST['province'] ?? null, 120), clean_str($_POST['region'] ?? null, 120),
                        $lat, $lng, $zoom, $boundary, $bsrc, $bver, clean_str($_POST['hall_address'] ?? null, 255), clean_str($_POST['hall_phone'] ?? null, 60)]);
                logAudit('create', 'barangay', (int)db()->lastInsertId(), $name);
            }
            flash('ok', 'Saved.');
        } catch (PDOException $e) {
            flash('error', $e->getCode() === '23000' ? 'A barangay with that name, municipality and province already exists.' : 'Could not save.');
        }
    }
    redirect('admin/barangays.php' . ($bid ? '?id=' . $bid : ''));
}

$editId = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
$cur = [];
if ($editId) {
    $st = db()->prepare('SELECT * FROM barangays WHERE barangay_id = ?');
    $st->execute([$editId]);
    $cur = $st->fetch() ?: [];
}
$rows = db()->query('SELECT b.*, (SELECT COUNT(*) FROM establishments e WHERE e.barangay_id = b.barangay_id) AS n FROM barangays b ORDER BY name')->fetchAll();
$g = static fn(string $k) => h((string)($cur[$k] ?? ''));

admin_header('Barangays', 'brgy');
?>
<h1>Barangays</h1>
<div class="tablewrap"><table class="data"><thead><tr><th>Name</th><th>Municipality</th><th>Records</th><th>Boundary</th><th>Active</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= h($r['name']) ?> <?= $r['is_sample'] ? '<span class="badge sample">SAMPLE</span>' : '' ?></td><td><?= h($r['municipality']) ?></td><td><?= (int)$r['n'] ?></td>
<td><?= $r['boundary_geojson'] ? ($r['boundary_verified'] ? 'supplied, verified' : 'supplied, unverified') : 'none' ?></td>
<td><?= $r['is_active'] ? 'yes' : 'no' ?></td><td><a href="?id=<?= (int)$r['barangay_id'] ?>">Edit</a></td></tr>
<?php endforeach; ?>
</tbody></table></div>

<h2><?= $editId ? 'Edit barangay' : 'Add barangay' ?></h2>
<p class="muted">Enter real values only. Leave the centre blank if unknown. Boundaries are never generated: paste GeoJSON only if an official source supplied it, and name that source.</p>
<form method="post">
  <?= csrfField() ?>
  <?php if ($editId): ?><input type="hidden" name="barangay_id" value="<?= $editId ?>"><?php endif; ?>
  <div class="formgrid">
    <div><label for="name">Barangay name *</label><input type="text" id="name" name="name" required maxlength="120" value="<?= $g('name') ?>"></div>
    <div><label for="municipality">City / municipality</label><input type="text" id="municipality" name="municipality" maxlength="120" value="<?= $g('municipality') ?>"></div>
    <div><label for="province">Province</label><input type="text" id="province" name="province" maxlength="120" value="<?= $g('province') ?>"></div>
    <div><label for="region">Region</label><input type="text" id="region" name="region" maxlength="120" value="<?= $g('region') ?>"></div>
    <div><label for="center_lat">Map centre latitude</label><input type="text" id="center_lat" name="center_lat" value="<?= $g('center_lat') ?>"></div>
    <div><label for="center_lng">Map centre longitude</label><input type="text" id="center_lng" name="center_lng" value="<?= $g('center_lng') ?>"></div>
    <div><label for="default_zoom">Default zoom (3-19)</label><input type="number" id="default_zoom" name="default_zoom" min="3" max="19" value="<?= $editId ? $g('default_zoom') : '16' ?>"></div>
    <div><label for="hall_address">Barangay hall address</label><input type="text" id="hall_address" name="hall_address" maxlength="255" value="<?= $g('hall_address') ?>"></div>
    <div><label for="hall_phone">Barangay hall phone</label><input type="text" id="hall_phone" name="hall_phone" maxlength="60" value="<?= $g('hall_phone') ?>"></div>
  </div>
  <label for="boundary_geojson">Boundary GeoJSON (optional, Polygon/MultiPolygon, max 1 MB)</label>
  <textarea id="boundary_geojson" name="boundary_geojson" rows="5"><?= $g('boundary_geojson') ?></textarea>
  <label for="boundary_source">Boundary source (required with a boundary)</label>
  <input type="text" id="boundary_source" name="boundary_source" maxlength="255" value="<?= $g('boundary_source') ?>" placeholder="e.g. file supplied by City Planning Office, 2026">
  <label class="check"><input type="checkbox" name="boundary_verified" value="1" <?= !empty($cur['boundary_verified']) ? 'checked' : '' ?>> Boundary confirmed by the barangay</label>
  <?php if ($editId): ?><label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($cur['is_active']) ? 'checked' : '' ?>> Active</label><?php endif; ?>
  <p class="toolbar"><button type="submit">Save</button><?php if ($editId): ?><a class="btn secondary" href="<?= h(url('admin/barangays.php')) ?>">New barangay</a><?php endif; ?></p>
</form>
<?php admin_footer(); ?>
