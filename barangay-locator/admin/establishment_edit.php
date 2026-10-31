<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : null;
requirePermission($id ? 'establishments.edit' : 'establishments.create');
$user = currentUser();

$rec = $id ? est_get($id) : null;
if ($id && (!$rec || !canAccessBarangay((int)$rec['barangay_id']))) {
    http_response_code(404);
    exit('Record not found.');
}

/** Validate and store an uploaded photo. Returns [filename|null, error|null]. */
function handle_photo_upload(): array
{
    if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) { return [null, 'Photo upload failed.']; }
    if ($f['size'] > 2 * 1024 * 1024) { return [null, 'Photo must be 2 MB or smaller.']; }
    if (!is_uploaded_file($f['tmp_name'])) { return [null, 'Invalid upload.']; }
    $fi = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fi->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if ($ext === null || @getimagesize($f['tmp_name']) === false) { return [null, 'Photo must be a JPG, PNG or WebP image.']; }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], APP_ROOT . '/uploads/' . $name)) { return [null, 'Could not store the photo.']; }
    return [$name, null];
}

$errors = [];
$dups = [];
$form = $rec ?: ['barangay_id' => userBarangayScope() ?? '', 'classification' => 'private'];
$contacts = []; $hours = []; $fac = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    [$d, $errors] = est_validate($_POST);
    $form = array_merge($form, $_POST);

    if (!$errors) {
        $dups = est_find_duplicates($d['barangay_id'], $d['name'], $d['latitude'], $d['longitude'], $id);
        if ($dups && empty($_POST['ack_duplicates'])) {
            $errors[] = 'Possible duplicate record(s) found. Review them below, then tick the confirmation box to save anyway.';
        }
    }
    [$photo, $perr] = $errors ? [null, null] : handle_photo_upload();
    if ($perr) { $errors[] = $perr; }

    if (!$errors) {
        try {
            $newId = est_save($d, $id, $user, can('establishments.verify'));
            if ($photo) {
                db()->prepare('UPDATE establishments SET photo_path = ? WHERE establishment_id = ?')->execute([$photo, $newId]);
            }
            logAudit($id ? 'update' : 'create', 'establishment', $newId, $d['name']);
            flash('ok', $id ? 'Saved.' : 'Created. It is listed as awaiting verification until a verifier checks it.');
            redirect('admin/establishment_edit.php?id=' . $newId);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $errors[] = 'Could not save. ' . (config('debug', false) ? $e->getMessage() : 'Please check the values and try again.');
        }
    }
} elseif ($id) {
    $c = db()->prepare('SELECT contact_type, contact_value FROM establishment_contacts WHERE establishment_id = ?');
    $c->execute([$id]);
    foreach ($c->fetchAll() as $r) { $form[$r['contact_type']] = $r['contact_value']; }
    $hq = db()->prepare('SELECT * FROM operating_hours WHERE establishment_id = ?');
    $hq->execute([$id]);
    foreach ($hq->fetchAll() as $r) {
        $i = (int)$r['day_of_week'];
        $form["h{$i}_open"] = $r['opens_at'] ? substr($r['opens_at'], 0, 5) : '';
        $form["h{$i}_close"] = $r['closes_at'] ? substr($r['closes_at'], 0, 5) : '';
        $form["h{$i}_closed"] = $r['is_closed'];
        $form["h{$i}_24h"] = $r['is_24h'];
    }
    $fq = db()->prepare('SELECT * FROM facilities WHERE establishment_id = ?');
    $fq->execute([$id]);
    $fac = $fq->fetch() ?: null;
    if ($fac) { $form['facility_type'] = $fac['facility_type']; $form['is_emergency'] = $fac['is_emergency']; $form['services_offered'] = $fac['services_offered']; }
    $form['latitude'] = $rec['latitude']; $form['longitude'] = $rec['longitude'];
}

$barangays = admin_barangays();
$cats = admin_categories();
$v = static fn(string $k) => h((string)($form[$k] ?? ''));

admin_header($id ? 'Edit establishment' : 'Add establishment', 'est');
?>
<p><a href="<?= h(url('admin/establishments.php')) ?>">← Establishments</a></p>
<h1><?= $id ? 'Edit establishment' : 'Add establishment' ?></h1>
<?php if ($rec): ?>
  <p class="muted">Status: <strong><?= h(t('status.' . $rec['status'])) ?></strong>
  · Map position: <?= $rec['latitude'] === null ? 'none' : ($rec['coords_verified'] ? 'checked' : 'not checked') ?>
  <?php if (!can('establishments.verify')): ?> · Editing a verified record sends it back for verification.<?php endif; ?></p>
<?php endif; ?>
<?php foreach ($errors as $m): ?><div class="notice error" role="alert"><?= h($m) ?></div><?php endforeach; ?>
<?php if ($dups): ?>
  <div class="notice"><strong>Possible duplicates:</strong><ul>
  <?php foreach ($dups as $d2): ?><li><a href="<?= h(url('admin/establishment_edit.php?id=' . $d2['establishment_id'])) ?>"><?= h($d2['name']) ?></a> — <?= h($d2['reason']) ?></li><?php endforeach; ?>
  </ul><label class="check"><input type="checkbox" name="ack_duplicates" value="1" form="est-form"> I checked these; this is a different place.</label></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="est-form" autocomplete="off">
  <?= csrfField() ?>
  <div class="formgrid">
    <div><label for="name">Name *</label><input type="text" id="name" name="name" required maxlength="160" value="<?= $v('name') ?>"></div>
    <div><label for="barangay_id">Barangay *</label>
      <select id="barangay_id" name="barangay_id" required>
        <?php foreach ($barangays as $b): ?><option value="<?= (int)$b['barangay_id'] ?>" <?= (string)($form['barangay_id'] ?? '') === (string)$b['barangay_id'] ? 'selected' : '' ?>><?= h($b['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="primary_category_id">Category *</label>
      <select id="primary_category_id" name="primary_category_id" required>
        <option value="">Select…</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (string)($form['primary_category_id'] ?? '') === (string)$c['category_id'] ? 'selected' : '' ?>><?= h($c['name_en']) ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="classification">Public or private</label>
      <select id="classification" name="classification">
        <option value="private" <?= ($form['classification'] ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
        <option value="public" <?= ($form['classification'] ?? '') === 'public' ? 'selected' : '' ?>>Public</option>
      </select></div>
    <div><label for="street_address">Street address</label><input type="text" id="street_address" name="street_address" maxlength="255" value="<?= $v('street_address') ?>"></div>
    <div><label for="landmark">Landmark</label><input type="text" id="landmark" name="landmark" maxlength="255" value="<?= $v('landmark') ?>"></div>
    <div><label for="zone">Zone / Purok</label><input type="text" id="zone" name="zone" maxlength="80" value="<?= $v('zone') ?>"></div>
    <div><label for="data_source">Where did this information come from?</label><input type="text" id="data_source" name="data_source" maxlength="255" value="<?= $v('data_source') ?>" placeholder="e.g. owner interview, barangay records"></div>
  </div>
  <label for="directions_text">How to get there (optional)</label>
  <textarea id="directions_text" name="directions_text" rows="4" maxlength="1500" placeholder="Plain-language route from a well-known starting point, e.g. the barangay hall or main road. Use only directions you or the barangay have confirmed."><?= $v('directions_text') ?></textarea>
  <label for="description">Description</label>
  <textarea id="description" name="description" rows="3" maxlength="2000"><?= $v('description') ?></textarea>

  <fieldset><legend>Map position</legend>
    <p class="muted">Leave both blank if the exact position is not known. Never guess. Changing these resets the “checked” status.</p>
    <div class="formgrid">
      <div><label for="latitude">Latitude</label><input type="text" inputmode="decimal" id="latitude" name="latitude" value="<?= $v('latitude') ?>" placeholder="e.g. 14.1234567"></div>
      <div><label for="longitude">Longitude</label><input type="text" inputmode="decimal" id="longitude" name="longitude" value="<?= $v('longitude') ?>" placeholder="e.g. 121.1234567"></div>
      <div><label for="coords_source">How the position was obtained</label><input type="text" id="coords_source" name="coords_source" maxlength="255" value="<?= $v('coords_source') ?>" placeholder="e.g. phone GPS at the entrance"></div>
    </div>
  </fieldset>

  <fieldset><legend>Contacts (shown publicly)</legend>
    <div class="formgrid">
      <div><label for="phone">Phone</label><input type="text" id="phone" name="phone" maxlength="60" value="<?= $v('phone') ?>"></div>
      <div><label for="mobile">Mobile</label><input type="text" id="mobile" name="mobile" maxlength="60" value="<?= $v('mobile') ?>"></div>
      <div><label for="email">Email</label><input type="text" id="email" name="email" maxlength="190" value="<?= $v('email') ?>"></div>
      <div><label for="website">Website</label><input type="text" id="website" name="website" maxlength="190" value="<?= $v('website') ?>" placeholder="https://"></div>
    </div>
  </fieldset>

  <fieldset><legend>Opening hours</legend>
    <div class="tablewrap"><table class="data"><thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th><th>24 h</th></tr></thead><tbody>
    <?php for ($i = 0; $i < 7; $i++): ?>
      <tr><td><?= h(t('day.' . $i)) ?></td>
        <td><input type="text" name="h<?= $i ?>_open" value="<?= $v("h{$i}_open") ?>" placeholder="08:00" maxlength="5" style="width:6rem"></td>
        <td><input type="text" name="h<?= $i ?>_close" value="<?= $v("h{$i}_close") ?>" placeholder="17:00" maxlength="5" style="width:6rem"></td>
        <td><input type="checkbox" name="h<?= $i ?>_closed" value="1" <?= !empty($form["h{$i}_closed"]) ? 'checked' : '' ?>></td>
        <td><input type="checkbox" name="h<?= $i ?>_24h" value="1" <?= !empty($form["h{$i}_24h"]) ? 'checked' : '' ?>></td></tr>
    <?php endfor; ?>
    </tbody></table></div>
    <label for="hours_note">Hours note (shown when no weekly hours are set)</label>
    <input type="text" id="hours_note" name="hours_note" maxlength="255" value="<?= $v('hours_note') ?>">
  </fieldset>

  <fieldset><legend>Facility / emergency</legend>
    <div class="formgrid">
      <div><label for="facility_type">Facility type</label>
        <select id="facility_type" name="facility_type">
          <option value="">—</option>
          <?php foreach (['government','health','safety','education','evacuation','transport','utility','other'] as $ft): ?>
            <option value="<?= $ft ?>" <?= ($form['facility_type'] ?? '') === $ft ? 'selected' : '' ?>><?= ucfirst($ft) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label class="check"><input type="checkbox" name="is_emergency" value="1" <?= !empty($form['is_emergency']) ? 'checked' : '' ?>> Emergency facility</label>
        <p class="muted">Shown publicly only after a verifier confirms the emergency role.
        <?= ($fac && $fac['emergency_verified_at']) ? 'Confirmed ' . h($fac['emergency_verified_at']) . '.' : '' ?></p></div>
    </div>
    <label for="services_offered">Services offered</label>
    <textarea id="services_offered" name="services_offered" rows="2" maxlength="2000"><?= $v('services_offered') ?></textarea>
  </fieldset>

  <label for="photo">Photo (JPG/PNG/WebP, max 2 MB)</label>
  <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
  <?php if (!empty($rec['photo_path'])): ?><p class="muted">Current: <?= h($rec['photo_path']) ?></p><?php endif; ?>

  <p class="toolbar"><button type="submit">Save</button><a class="btn secondary" href="<?= h(url('admin/establishments.php')) ?>">Cancel</a></p>
</form>

<?php if ($id): ?>
<h2>History</h2>
<?php
$hq = db()->prepare('SELECT v.*, u.username FROM verification_records v LEFT JOIN users u ON u.user_id = v.user_id WHERE v.establishment_id = ? ORDER BY v.record_id DESC LIMIT 30');
$hq->execute([$id]);
?>
<div class="tablewrap"><table class="data"><thead><tr><th>When</th><th>Action</th><th>Status</th><th>Evidence / note</th><th>By</th></tr></thead><tbody>
<?php foreach ($hq->fetchAll() as $r): ?>
  <tr><td><?= h($r['created_at']) ?></td><td><?= h($r['action']) ?></td><td><?= h(($r['from_status'] ?? '') . ' → ' . ($r['to_status'] ?? '')) ?></td><td><?= h($r['evidence'] ?? $r['note'] ?? '') ?></td><td><?= h($r['username'] ?? '') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php admin_footer(); ?>
