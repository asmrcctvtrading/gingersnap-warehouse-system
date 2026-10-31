<?php
declare(strict_types=1);

/**
 * Public read access to establishments. Every public API/page goes through here so the
 * visibility rules live in ONE place:
 *   - status 'verified' always visible; 'pending_verification' only if setting public_show_unverified=1 (labelled);
 *   - 'needs_review' and 'inactive' are never public;
 *   - coordinates are plotted only if present AND (coords_verified=1 OR setting plot_unverified_coords=1);
 *   - the emergency flag is exposed only when emergency_verified_at is set;
 *   - only is_public contacts are returned.
 */

function repo_public_status_list(): array
{
    return setting('public_show_unverified', '1') === '1'
        ? ['verified', 'pending_verification']
        : ['verified'];
}

function repo_plot_requires_verified(): bool
{
    return setting('plot_unverified_coords', '0') !== '1';
}

function like_escape(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}

/** Build WHERE + params for public listing filters. */
function repo_build_where(array $f, array &$params): string
{
    $statuses = repo_public_status_list();
    $ph = implode(',', array_fill(0, count($statuses), '?'));
    $where = ["e.status IN ($ph)"];
    foreach ($statuses as $s) {
        $params[] = $s;
    }
    $where[] = 'b.is_active = 1';

    if (!empty($f['barangay_id'])) {
        $where[] = 'e.barangay_id = ?';
        $params[] = (int)$f['barangay_id'];
    }
    if (!empty($f['category_id'])) {
        $where[] = '(e.primary_category_id = ? OR EXISTS (SELECT 1 FROM establishment_categories ec WHERE ec.establishment_id = e.establishment_id AND ec.category_id = ?))';
        $params[] = (int)$f['category_id'];
        $params[] = (int)$f['category_id'];
    }
    if (!empty($f['classification']) && in_array($f['classification'], ['public', 'private'], true)) {
        $where[] = 'e.classification = ?';
        $params[] = $f['classification'];
    }
    if (!empty($f['zone'])) {
        $where[] = 'e.zone = ?';
        $params[] = (string)$f['zone'];
    }
    if (!empty($f['essential'])) {
        $where[] = 'c.is_essential = 1';
    }
    if (!empty($f['emergency'])) {
        $where[] = 'EXISTS (SELECT 1 FROM facilities fx WHERE fx.establishment_id = e.establishment_id AND fx.is_emergency = 1 AND fx.emergency_verified_at IS NOT NULL)';
    }
    if (!empty($f['q'])) {
        $like = '%' . like_escape((string)$f['q']) . '%';
        $where[] = '(e.name LIKE ? OR e.description LIKE ? OR e.street_address LIKE ? OR e.landmark LIKE ? OR e.zone LIKE ? OR c.name_en LIKE ? OR c.name_tl LIKE ?)';
        for ($i = 0; $i < 7; $i++) {
            $params[] = $like;
        }
    }
    return implode(' AND ', $where);
}

const REPO_SELECT = "e.establishment_id, e.barangay_id, e.name, e.description, e.street_address, e.landmark, e.directions_text, e.zone,
    e.latitude, e.longitude, e.coords_verified, e.classification, e.status, e.last_verified_at, e.hours_note, e.photo_path,
    e.is_sample, e.osm_ref, e.primary_category_id,
    c.slug AS category_slug, c.name_en AS category_name_en, c.name_tl AS category_name_tl, c.icon AS category_icon,
    c.color AS category_color, c.is_essential AS category_is_essential,
    b.name AS barangay_name,
    f.is_emergency AS f_is_emergency, f.emergency_verified_at AS f_emergency_verified_at, f.facility_type AS f_type,
    f.services_offered AS f_services";

const REPO_FROM = "FROM establishments e
    JOIN categories c ON c.category_id = e.primary_category_id
    JOIN barangays b ON b.barangay_id = e.barangay_id
    LEFT JOIN facilities f ON f.establishment_id = e.establishment_id";

/** Shape a DB row into the public JSON structure, enforcing the visibility rules. */
function repo_shape(array $r): array
{
    $hasCoords = $r['latitude'] !== null && $r['longitude'] !== null;
    $plottable = $hasCoords && ((int)$r['coords_verified'] === 1 || !repo_plot_requires_verified());
    $emergency = (int)($r['f_is_emergency'] ?? 0) === 1 && !empty($r['f_emergency_verified_at']);

    return [
        'id'              => (int)$r['establishment_id'],
        'name'            => $r['name'],
        'description'     => $r['description'],
        'barangay_id'     => (int)$r['barangay_id'],
        'barangay'        => $r['barangay_name'],
        'category'        => [
            'id'    => (int)$r['primary_category_id'],
            'slug'  => $r['category_slug'],
            'name'  => cat_name(['name_en' => $r['category_name_en'], 'name_tl' => $r['category_name_tl']]),
            'icon'  => $r['category_icon'],
            'color' => $r['category_color'],
            'essential' => (int)$r['category_is_essential'] === 1,
        ],
        'address'         => $r['street_address'],
        'landmark'        => $r['landmark'],
        'directions'      => $r['directions_text'],
        'zone'            => $r['zone'],
        'classification'  => $r['classification'],
        'status'          => $r['status'],
        'verified'        => $r['status'] === 'verified',
        'last_verified_at'=> $r['last_verified_at'],
        'hours_note'      => $r['hours_note'],
        'photo_url'       => $r['photo_path'] ? url('uploads/' . rawurlencode(basename((string)$r['photo_path']))) : null,
        'is_sample'       => (int)$r['is_sample'] === 1,
        'osm_sourced'     => !empty($r['osm_ref']),
        'has_coordinates' => $hasCoords,
        'plottable'       => $plottable,
        'coords_verified' => (int)$r['coords_verified'] === 1,
        // Coordinates are only sent when the point may be plotted; otherwise the record is list-only.
        'lat'             => $plottable ? (float)$r['latitude'] : null,
        'lng'             => $plottable ? (float)$r['longitude'] : null,
        'emergency'       => $emergency,
        'facility_type'   => $r['f_type'] ?? null,
        'services'        => $r['f_services'] ?? null,
    ];
}

function repo_search(array $f, int $limit = 50, int $offset = 0): array
{
    $params = [];
    $where = repo_build_where($f, $params);
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);

    $cnt = db()->prepare("SELECT COUNT(*) " . REPO_FROM . " WHERE $where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $sql = "SELECT " . REPO_SELECT . " " . REPO_FROM . " WHERE $where ORDER BY e.name ASC LIMIT $limit OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($params);
    return ['total' => $total, 'items' => array_map('repo_shape', $st->fetchAll())];
}

function repo_find(int $id): ?array
{
    $params = [];
    $where = repo_build_where([], $params) . ' AND e.establishment_id = ?';
    $params[] = $id;
    $st = db()->prepare("SELECT " . REPO_SELECT . " " . REPO_FROM . " WHERE $where");
    $st->execute($params);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }
    $o = repo_shape($r);

    $c = db()->prepare('SELECT contact_type, contact_value FROM establishment_contacts WHERE establishment_id = ? AND is_public = 1 ORDER BY contact_id');
    $c->execute([$id]);
    $o['contacts'] = $c->fetchAll();

    $h = db()->prepare('SELECT day_of_week, opens_at, closes_at, is_closed, is_24h FROM operating_hours WHERE establishment_id = ? ORDER BY day_of_week');
    $h->execute([$id]);
    $o['hours'] = array_map(static function ($x) {
        return [
            'day'    => (int)$x['day_of_week'],
            'opens'  => $x['opens_at'] ? substr($x['opens_at'], 0, 5) : null,
            'closes' => $x['closes_at'] ? substr($x['closes_at'], 0, 5) : null,
            'closed' => (int)$x['is_closed'] === 1,
            'is_24h' => (int)$x['is_24h'] === 1,
        ];
    }, $h->fetchAll());
    return $o;
}

/** Markers: only plottable records, minimal fields. */
function repo_markers(array $f, int $limit = 1000): array
{
    $params = [];
    $where = repo_build_where($f, $params) . ' AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL';
    if (repo_plot_requires_verified()) {
        $where .= ' AND e.coords_verified = 1';
    }
    $limit = max(1, min(2000, $limit));
    $st = db()->prepare("SELECT " . REPO_SELECT . " " . REPO_FROM . " WHERE $where ORDER BY e.name LIMIT $limit");
    $st->execute($params);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $s = repo_shape($r);
        $out[] = [
            'id' => $s['id'], 'name' => $s['name'], 'lat' => $s['lat'], 'lng' => $s['lng'],
            'category' => $s['category'], 'address' => $s['address'], 'verified' => $s['verified'],
            'emergency' => $s['emergency'], 'is_sample' => $s['is_sample'],
        ];
    }
    return $out;
}

/**
 * Nearby, ranked by STRAIGHT-LINE (great-circle) distance in metres. This is NOT walking or driving distance
 * and the API labels it so.
 */
function repo_nearby(float $lat, float $lng, int $radiusM = 1000, array $f = [], int $limit = 30): array
{
    $radiusM = max(50, min(20000, $radiusM));
    $limit = max(1, min(100, $limit));

    $dLat = $radiusM / 111320.0;
    $dLng = $radiusM / (111320.0 * max(0.01, cos(deg2rad($lat))));

    $params = [];
    $where = repo_build_where($f, $params)
        . ' AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL AND e.latitude BETWEEN ? AND ? AND e.longitude BETWEEN ? AND ?';
    if (repo_plot_requires_verified()) {
        $where .= ' AND e.coords_verified = 1';
    }
    array_push($params, $lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng);

    $dist = '(6371000 * 2 * ASIN(SQRT(POW(SIN(RADIANS(e.latitude - ?) / 2), 2) + COS(RADIANS(?)) * COS(RADIANS(e.latitude)) * POW(SIN(RADIANS(e.longitude - ?) / 2), 2))))';
    $sql = "SELECT " . REPO_SELECT . ", $dist AS straight_line_m " . REPO_FROM . " WHERE $where HAVING straight_line_m <= ? ORDER BY straight_line_m ASC LIMIT $limit";
    $all = array_merge([$lat, $lat, $lng], $params, [$radiusM]);
    $st = db()->prepare($sql);
    $st->execute($all);

    $out = [];
    foreach ($st->fetchAll() as $r) {
        $s = repo_shape($r);
        $s['straight_line_m'] = (int)round((float)$r['straight_line_m']);
        $s['distance_note'] = 'straight_line_not_route';
        $out[] = $s;
    }
    return $out;
}

function repo_categories(bool $onlyUsed = false): array
{
    $sql = 'SELECT category_id, slug, name_en, name_tl, icon, color, is_essential FROM categories WHERE is_active = 1 ORDER BY sort_order, name_en';
    $rows = db()->query($sql)->fetchAll();
    return array_map(static function ($r) {
        return [
            'id' => (int)$r['category_id'], 'slug' => $r['slug'], 'name' => cat_name($r),
            'name_en' => $r['name_en'], 'name_tl' => $r['name_tl'],
            'icon' => $r['icon'], 'color' => $r['color'], 'essential' => (int)$r['is_essential'] === 1,
        ];
    }, $rows);
}

function repo_barangays(): array
{
    $rows = db()->query('SELECT barangay_id, name, municipality, province, center_lat, center_lng, default_zoom,
                                boundary_geojson, boundary_source, boundary_verified, hall_address, hall_phone, is_sample
                         FROM barangays WHERE is_active = 1 ORDER BY name')->fetchAll();
    return array_map(static function ($r) {
        return [
            'id' => (int)$r['barangay_id'], 'name' => $r['name'], 'municipality' => $r['municipality'],
            'province' => $r['province'], 'center' => $r['center_lat'] !== null ? [(float)$r['center_lat'], (float)$r['center_lng']] : null,
            'zoom' => (int)$r['default_zoom'],
            // Boundary is returned only if an admin supplied it WITH a source. Never generated.
            'boundary' => ($r['boundary_geojson'] && $r['boundary_source']) ? json_decode($r['boundary_geojson'], true) : null,
            'boundary_source' => $r['boundary_source'],
            'boundary_verified' => (int)$r['boundary_verified'] === 1,
            'hall_address' => $r['hall_address'], 'hall_phone' => $r['hall_phone'],
            'is_sample' => (int)$r['is_sample'] === 1,
        ];
    }, $rows);
}

function repo_facilities(array $f = []): array
{
    $f['essential'] = $f['essential'] ?? false;
    $res = repo_search($f + [], 200, 0);
    return array_values(array_filter($res['items'], static fn($i) => $i['facility_type'] !== null || $i['category']['essential']));
}

function repo_zones(?int $barangayId = null): array
{
    $params = [];
    $sql = "SELECT DISTINCT zone FROM establishments WHERE zone IS NOT NULL AND zone <> '' AND status IN ('verified','pending_verification')";
    if ($barangayId) {
        $sql .= ' AND barangay_id = ?';
        $params[] = $barangayId;
    }
    $st = db()->prepare($sql . ' ORDER BY zone');
    $st->execute($params);
    return array_column($st->fetchAll(), 'zone');
}
