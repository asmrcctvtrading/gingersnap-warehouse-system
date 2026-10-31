<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('settings.manage');   // admin only: bulk import

/*
 * Import named places near a point from OpenStreetMap (Overpass API).
 * Step 1: fetch + preview (nothing is saved). Step 2: import only the rows staff tick.
 * Imported rows are ALWAYS pending_verification with unchecked coordinates, so nothing becomes
 * public-as-verified or plotted (default setting) until a verifier confirms it.
 * OSM data is © OpenStreetMap contributors, ODbL: https://www.openstreetmap.org/copyright
 */

const OSM_MAX_ITEMS = 150;

/** Map OSM tags to one of our category slugs. Returns null when the object is not a useful establishment. */
function osm_category(array $t): ?string
{
    $name    = (string)($t['name'] ?? '');
    $amenity = (string)($t['amenity'] ?? '');
    $shop    = (string)($t['shop'] ?? '');
    $office  = (string)($t['office'] ?? '');
    $craft   = (string)($t['craft'] ?? '');

    if (preg_match('/(barangay|brgy\.?)\s*(hall|office|outpost)/i', $name)) { return 'barangay-office'; }

    $amenityMap = [
        'townhall' => 'city-gov-services', 'courthouse' => 'city-gov-services', 'post_office' => 'city-gov-services',
        'clinic' => 'health-clinic', 'doctors' => 'health-clinic', 'dentist' => 'health-clinic', 'health_post' => 'health-clinic',
        'hospital' => 'hospital-pharmacy', 'pharmacy' => 'hospital-pharmacy',
        'school' => 'school', 'kindergarten' => 'school', 'college' => 'school', 'university' => 'school', 'childcare' => 'school', 'library' => 'school',
        'police' => 'police', 'fire_station' => 'fire-station', 'place_of_worship' => 'worship',
        'restaurant' => 'food', 'fast_food' => 'food', 'cafe' => 'food', 'food_court' => 'food', 'bar' => 'food', 'ice_cream' => 'food', 'canteen' => 'food',
        'bank' => 'bank-atm', 'atm' => 'bank-atm',
        'money_transfer' => 'remittance', 'bureau_de_change' => 'remittance',
        'bus_station' => 'transport', 'taxi' => 'transport', 'ferry_terminal' => 'transport',
        'internet_cafe' => 'telecom', 'funeral_hall' => 'funeral', 'marketplace' => 'grocery',
        'community_centre' => 'other', 'social_facility' => 'other', 'fuel' => 'other',
    ];
    if ($amenity !== '' && isset($amenityMap[$amenity])) { return $amenityMap[$amenity]; }

    if ($shop !== '') {
        if (in_array($shop, ['supermarket', 'convenience', 'greengrocer', 'general', 'butcher', 'seafood', 'bakery', 'variety_store', 'grocery'], true)) { return 'grocery'; }
        if (strpos($shop, 'repair') !== false) { return 'repair'; }
        if ($shop === 'funeral_directors') { return 'funeral'; }
        if ($shop === 'water') { return 'water-refill'; }
        if ($shop === 'mobile_phone') { return 'telecom'; }
        return 'retail';
    }
    if ($office !== '') {
        if ($office === 'government') { return 'city-gov-services'; }
        if ($office === 'telecommunication') { return 'telecom'; }
        return 'other';
    }
    if ($craft !== '') { return strpos($craft, 'repair') !== false ? 'repair' : 'other'; }
    if (!empty($t['healthcare'])) { return 'health-clinic'; }
    return null;
}

/** POST an Overpass QL query. Returns [decoded array|null, error|null]. */
function osm_overpass(string $query): array
{
    $url  = 'https://overpass-api.de/api/interpreter';
    $ua   = 'BarangayEstablishmentLocator/1.0 (staff import; ' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ')';
    $body = http_build_query(['data' => $query]);
    $raw = false; $code = 0; $err = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 40, 'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: $ua\r\nAccept: application/json\r\n",
            'content' => $body,
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) { $code = (int)$m[1]; }
    } else {
        return [null, 'This server has neither cURL nor allow_url_fopen, so it cannot reach OpenStreetMap. Ask your host to enable cURL.'];
    }

    if ($raw === false || $raw === '') { return [null, 'Could not reach the OpenStreetMap service' . ($err !== '' ? ' (' . $err . ')' : '') . '. Your host may block outgoing connections.']; }
    if ($code === 429 || $code === 504) { return [null, 'The OpenStreetMap service is busy. Wait a minute and try again.']; }
    if ($code !== 200) { return [null, 'OpenStreetMap service returned HTTP ' . $code . '.']; }
    $j = json_decode((string)$raw, true);
    if (!is_array($j) || !isset($j['elements'])) { return [null, 'Unexpected response from OpenStreetMap.']; }
    return [$j, null];
}

function osm_first(array $t, array $keys): ?string
{
    foreach ($keys as $k) {
        if (!empty($t[$k])) { $v = trim(explode(';', (string)$t[$k])[0]); if ($v !== '') { return $v; } }
    }
    return null;
}

$pdo = db();
$state = $_SESSION['osm_import'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'fetch') {
        $bid = isset($_POST['barangay_id']) && ctype_digit((string)$_POST['barangay_id']) ? (int)$_POST['barangay_id'] : 0;
        $bst = $pdo->prepare('SELECT barangay_id, name, center_lat, center_lng FROM barangays WHERE barangay_id = ?');
        $bst->execute([$bid]);
        $b = $bst->fetch();
        $radius = isset($_POST['radius']) && ctype_digit((string)$_POST['radius']) ? (int)$_POST['radius'] : 800;
        $radius = max(100, min(2000, $radius));
        $latIn = trim((string)($_POST['lat'] ?? '')); $lngIn = trim((string)($_POST['lng'] ?? ''));
        if ($latIn === '' && $lngIn === '' && $b && $b['center_lat'] !== null) { $latIn = (string)$b['center_lat']; $lngIn = (string)$b['center_lng']; }
        [$lat, $lng, $ce] = validate_coords($latIn, $lngIn);

        if (!$b) { flash('error', 'Choose a barangay first (add it under Barangays).'); }
        elseif ($ce || $lat === null) { flash('error', $ce ?: 'Enter a centre latitude and longitude, or save a map centre on the barangay.'); }
        else {
            $la = number_format($lat, 6, '.', ''); $lo = number_format($lng, 6, '.', '');
            $around = "(around:$radius,$la,$lo)";
            $q = '[out:json][timeout:25];(';
            foreach (['shop', 'amenity', 'office', 'craft', 'healthcare'] as $k) { $q .= 'nwr' . $around . '["name"]["' . $k . '"];'; }
            $q .= ');out tags center ' . (OSM_MAX_ITEMS + 1) . ';';
            [$res, $err] = osm_overpass($q);
            if ($err) { flash('error', $err); }
            else {
                $items = []; $skipped = 0;
                $truncated = count($res['elements']) > OSM_MAX_ITEMS;
                foreach (array_slice($res['elements'], 0, OSM_MAX_ITEMS) as $el) {
                    $t = $el['tags'] ?? [];
                    $slug = osm_category($t);
                    $elLat = $el['lat'] ?? ($el['center']['lat'] ?? null);
                    $elLng = $el['lon'] ?? ($el['center']['lon'] ?? null);
                    $name = clean_str($t['name'] ?? null, 160);
                    if ($slug === null || $name === null || $elLat === null || $elLng === null || !isset($el['type'], $el['id'])) { $skipped++; continue; }
                    [$pl, $pn, $pe] = validate_coords((string)$elLat, (string)$elLng);
                    if ($pe || $pl === null) { $skipped++; continue; }

                    $street = trim(((string)($t['addr:housenumber'] ?? '')) . ' ' . ((string)($t['addr:street'] ?? '')));
                    $addr = trim($street . (!empty($t['addr:suburb']) ? ', ' . $t['addr:suburb'] : ''), ' ,');
                    $phone = osm_first($t, ['phone', 'contact:phone', 'contact:mobile']);
                    $web = osm_first($t, ['website', 'contact:website']);
                    $items[] = [
                        'ref'   => $el['type'] . '/' . $el['id'],
                        'name'  => $name,
                        'slug'  => $slug,
                        'lat'   => $pl, 'lng' => $pn,
                        'addr'  => $addr !== '' ? clean_str($addr, 255) : null,
                        'phone' => $phone !== null ? clean_str(preg_replace('/[^0-9+()\-\s]/', '', $phone), 60) : null,
                        'web'   => ($web !== null && valid_http_url($web)) ? clean_str($web, 190) : null,
                        'hours' => !empty($t['opening_hours']) ? clean_str('OSM opening_hours: ' . $t['opening_hours'], 255) : null,
                    ];
                }
                $_SESSION['osm_import'] = ['barangay_id' => $bid, 'barangay' => $b['name'], 'items' => $items, 'truncated' => $truncated,
                                           'skipped' => $skipped, 'radius' => $radius, 'lat' => $la, 'lng' => $lo, 'at' => date('Y-m-d H:i')];
                if (!$items) { flash('info', 'OpenStreetMap returned no usable named places within ' . $radius . ' m of that point. OSM may simply not have them mapped yet.'); }
            }
        }
        redirect('admin/osm_import.php');
    }

    if ($action === 'import' && is_array($state) && !empty($state['items'])) {
        $picked = array_map('intval', (array)($_POST['pick'] ?? []));
        $cats = [];
        foreach ($pdo->query('SELECT category_id, slug FROM categories')->fetchAll() as $c) { $cats[(string)$c['slug']] = (int)$c['category_id']; }
        $validCat = array_flip(array_map('intval', array_values($cats)));
        $uid = (int)(currentUser()['user_id'] ?? 0);
        $date = date('Y-m-d');
        $ins = $pdo->prepare('INSERT INTO establishments (barangay_id, name, primary_category_id, street_address, latitude, longitude, coords_verified,
            coords_source, classification, status, data_source, hours_note, osm_ref, created_by) VALUES (?,?,?,?,?,?,0,?,?,?,?,?,?,?)');
        $vr  = $pdo->prepare('INSERT INTO verification_records (establishment_id, action, to_status, note, user_id) VALUES (?,?,?,?,?)');
        $ct  = $pdo->prepare('INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value) VALUES (?,?,?)');
        $done = 0; $dups = 0;
        foreach ($picked as $i) {
            if (!isset($state['items'][$i])) { continue; }
            $it = $state['items'][$i];
            $catId = isset($_POST['cat'][$i]) && ctype_digit((string)$_POST['cat'][$i]) ? (int)$_POST['cat'][$i] : ($cats[$it['slug']] ?? 0);
            if (!isset($validCat[$catId])) { continue; }
            try {
                $pdo->beginTransaction();
                $ins->execute([(int)$state['barangay_id'], $it['name'], $catId, $it['addr'], $it['lat'], $it['lng'],
                    'OpenStreetMap ' . $it['ref'] . ' (imported ' . $date . '; position not checked on site)', 'private', 'pending_verification',
                    'OpenStreetMap contributors (ODbL), imported ' . $date . '. Not independently verified.', $it['hours'], $it['ref'], $uid]);
                $eid = (int)$pdo->lastInsertId();
                $vr->execute([$eid, 'created', 'pending_verification', 'Imported from OpenStreetMap ' . $it['ref'], $uid]);
                if ($it['phone']) {
                    $digits = preg_replace('/\D/', '', $it['phone']);
                    $ct->execute([$eid, preg_match('/^(63|0)9/', $digits) ? 'mobile' : 'phone', $it['phone']]);
                }
                if ($it['web']) { $ct->execute([$eid, 'website', $it['web']]); }
                $pdo->commit();
                $done++;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                if ($e->getCode() === '23000') { $dups++; } else { error_log($e->getMessage()); }
            }
        }
        logAudit('import', 'establishment', null, 'osm: ' . $done . ' records into barangay ' . (int)$state['barangay_id']);
        unset($_SESSION['osm_import']);
        flash('ok', "Imported $done place(s) as pending verification." . ($dups ? " $dups skipped (already imported)." : ''));
        redirect('admin/establishments.php');
    }

    if ($action === 'discard') { unset($_SESSION['osm_import']); redirect('admin/osm_import.php'); }
    redirect('admin/osm_import.php');
}

$brgys = admin_barangays();
$catRows = admin_categories();
$catBySlug = [];
foreach ($pdo->query('SELECT category_id, slug FROM categories')->fetchAll() as $c) { $catBySlug[(string)$c['slug']] = (int)$c['category_id']; }
$existing = [];
if (!empty($state['items'])) {
    $refs = array_column($state['items'], 'ref');
    $ph = implode(',', array_fill(0, count($refs), '?'));
    $st = $pdo->prepare("SELECT osm_ref FROM establishments WHERE osm_ref IN ($ph)");
    $st->execute($refs);
    $existing = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
}

admin_header('OpenStreetMap import', 'osm');
?>
<h1>Import from OpenStreetMap</h1>
<p>Finds named shops, services and facilities near a point, from OpenStreetMap volunteers' data. Nothing is saved until you tick rows in the preview and confirm.</p>
<div class="notice info" role="note">
  <strong>Read before importing.</strong> The search covers a <em>circle</em> around your point, not the barangay boundary, so some places may belong to a neighbouring barangay: untick them. OSM may be incomplete or out of date. Imported records are saved as <strong>pending verification</strong> with <strong>unchecked map positions</strong>; a verifier must confirm each one before it is shown as verified. Data © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a> (ODbL).
</div>

<form method="post">
  <?= csrfField() ?><input type="hidden" name="action" value="fetch">
  <div class="formgrid">
    <div><label for="barangay_id">Import into barangay *</label>
      <select id="barangay_id" name="barangay_id" required>
        <?php foreach ($brgys as $b): ?><option value="<?= (int)$b['barangay_id'] ?>"><?= h($b['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div><label for="lat">Centre latitude</label><input type="text" inputmode="decimal" id="lat" name="lat" placeholder="blank = barangay's saved centre"></div>
    <div><label for="lng">Centre longitude</label><input type="text" inputmode="decimal" id="lng" name="lng" placeholder="blank = barangay's saved centre"></div>
    <div><label for="radius">Radius in metres (100–2000)</label><input type="number" id="radius" name="radius" min="100" max="2000" value="800"></div>
  </div>
  <p class="muted">Get the centre from openstreetmap.org: right-click the barangay hall → "Show address", then copy the two numbers.</p>
  <p class="toolbar"><button type="submit">Fetch preview</button></p>
</form>

<?php if (!empty($state['items'])): ?>
<h2>Preview: <?= count($state['items']) ?> place(s) for <?= h($state['barangay']) ?></h2>
<p class="muted">Fetched <?= h($state['at']) ?> · centre <?= h($state['lat']) ?>, <?= h($state['lng']) ?> · radius <?= (int)$state['radius'] ?> m
  <?= $state['skipped'] ? ' · ' . (int)$state['skipped'] . ' unnamed/unsupported object(s) ignored' : '' ?></p>
<?php if (!empty($state['truncated'])): ?><div class="notice">More than <?= OSM_MAX_ITEMS ?> places found; only the first <?= OSM_MAX_ITEMS ?> are shown. Use a smaller radius to see the rest.</div><?php endif; ?>
<form method="post">
  <?= csrfField() ?><input type="hidden" name="action" value="import">
  <div class="tablewrap"><table class="data"><thead><tr><th>Import</th><th>Name</th><th>Category</th><th>Address (from OSM)</th><th>OSM</th></tr></thead><tbody>
  <?php foreach ($state['items'] as $i => $it):
      $dup = isset($existing[$it['ref']]); $sel = $catBySlug[$it['slug']] ?? 0; ?>
    <tr>
      <td><input type="checkbox" name="pick[]" value="<?= (int)$i ?>" <?= $dup ? 'disabled' : 'checked' ?> aria-label="Import <?= h($it['name']) ?>"></td>
      <td><?= h($it['name']) ?><?= $dup ? ' <span class="badge">already imported</span>' : '' ?></td>
      <td><select name="cat[<?= (int)$i ?>]" aria-label="Category for <?= h($it['name']) ?>">
        <?php foreach ($catRows as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= (int)$c['category_id'] === $sel ? 'selected' : '' ?>><?= h($c['name_en']) ?></option><?php endforeach; ?>
      </select></td>
      <td><?= h((string)$it['addr']) ?></td>
      <td><a href="https://www.openstreetmap.org/<?= h($it['ref']) ?>" target="_blank" rel="noopener noreferrer"><?= h($it['ref']) ?></a></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <p class="toolbar"><button type="submit">Import ticked places as “pending verification”</button></p>
</form>
<form method="post"><?= csrfField() ?><input type="hidden" name="action" value="discard"><button type="submit" class="secondary">Discard preview</button></form>
<?php endif; ?>
<?php admin_footer(); ?>
