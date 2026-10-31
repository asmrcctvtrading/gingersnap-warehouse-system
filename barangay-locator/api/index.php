<?php
declare(strict_types=1);

// JSON API front controller. api/.htaccess rewrites /api/foo/bar -> index.php?r=foo/bar
// (calling index.php?r=foo/bar directly also works if mod_rewrite is unavailable).
require __DIR__ . '/../app/bootstrap.php';

if (!app_installed()) {
    json_error('System is not installed.', 503);
}

$route  = trim((string)($_GET['r'] ?? ''), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$parts  = $route === '' ? [] : explode('/', $route);

/** Collect and sanitise common filters from the query string. */
function api_filters(): array
{
    $f = [];
    foreach (['barangay_id', 'category_id'] as $k) {
        if (isset($_GET[$k]) && ctype_digit((string)$_GET[$k])) {
            $f[$k] = (int)$_GET[$k];
        }
    }
    if (isset($_GET['q'])) {
        $q = clean_str($_GET['q'], 100);
        if ($q !== null) {
            $f['q'] = $q;
        }
    }
    if (isset($_GET['zone'])) {
        $z = clean_str($_GET['zone'], 80);
        if ($z !== null) {
            $f['zone'] = $z;
        }
    }
    if (isset($_GET['classification']) && in_array($_GET['classification'], ['public', 'private'], true)) {
        $f['classification'] = $_GET['classification'];
    }
    foreach (['essential', 'emergency'] as $k) {
        if (!empty($_GET[$k]) && $_GET[$k] !== '0') {
            $f[$k] = true;
        }
    }
    return $f;
}

function api_int(string $k, int $def, int $min, int $max): int
{
    if (!isset($_GET[$k]) || !preg_match('/^-?\d+$/', (string)$_GET[$k])) {
        return $def;
    }
    return max($min, min($max, (int)$_GET[$k]));
}

try {
    if ($method === 'GET') {
        switch (true) {
            case $parts === ['establishments']:
                $res = repo_search(api_filters(), api_int('limit', 50, 1, 200), api_int('offset', 0, 0, 100000));
                json_out(['ok' => true, 'total' => $res['total'], 'items' => $res['items']]);

            case $parts === ['establishments', 'nearby']:
                $lat = parse_lat($_GET['lat'] ?? null);
                $lng = parse_lng($_GET['lng'] ?? null);
                if ($lat === null || $lng === null) {
                    json_error('lat and lng are required and must be valid coordinates.', 422);
                }
                $items = repo_nearby($lat, $lng, api_int('radius', 1000, 50, 20000), api_filters(), api_int('limit', 30, 1, 100));
                json_out(['ok' => true, 'distance_kind' => 'straight_line', 'items' => $items]);

            case count($parts) === 2 && $parts[0] === 'establishments' && ctype_digit($parts[1]):
                $e = repo_find((int)$parts[1]);
                if (!$e) {
                    json_error('Establishment not found.', 404);
                }
                json_out(['ok' => true, 'item' => $e]);

            case $parts === ['categories']:
                json_out(['ok' => true, 'items' => repo_categories()]);

            case $parts === ['barangays']:
                json_out(['ok' => true, 'items' => repo_barangays()]);

            case $parts === ['zones']:
                json_out(['ok' => true, 'items' => repo_zones(isset($_GET['barangay_id']) && ctype_digit((string)$_GET['barangay_id']) ? (int)$_GET['barangay_id'] : null)]);

            case $parts === ['facilities']:
                json_out(['ok' => true, 'items' => repo_facilities(api_filters())]);

            case $parts === ['map', 'markers']:
                json_out(['ok' => true, 'items' => repo_markers(api_filters())]);

            case $parts === ['config']:
                // Public client config: map tiles + routing mode. No secrets here.
                json_out(['ok' => true,
                    'site_title'  => setting('site_title', 'Barangay Establishment Locator'),
                    'tile_url'    => setting('tile_url'),
                    'tile_attribution' => setting('tile_attribution'),
                    'tile_is_production_ok' => setting('tile_is_production_ok', '0') === '1',
                    'routing_provider' => setting('routing_provider', 'external_link'),
                    'external_nav_url' => setting('external_nav_url'),
                    'kiosk_idle_seconds' => (int)setting('kiosk_idle_seconds', '90'),
                    'show_sample_banner' => setting('show_sample_banner', '1') === '1',
                ]);

            default:
                json_error('Unknown endpoint.', 404);
        }
    }

    if ($method === 'POST' && $parts === ['reports']) {
        handle_report();
    }

    json_error('Method not allowed.', 405);
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    json_error(config('debug', false) ? $e->getMessage() : 'Server error.', 500);
}

/**
 * Public report/suggestion. NEVER publishes anything: it only creates a pending change request
 * for staff to review. Protected by CSRF, a honeypot, per-IP daily limit and a payload size cap.
 */
function handle_report(): void
{
    $in = request_json();
    if (!$in) {
        $in = $_POST;
    }
    $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf'] ?? null);
    if (!csrf_valid(is_string($tok) ? $tok : null)) {
        json_error('Session expired. Refresh the page and try again.', 403);
    }
    if (!empty($in['website'] ?? '')) { // honeypot: humans leave this empty
        json_out(['ok' => true, 'message' => 'Thank you.']);
    }

    $limit = max(1, (int)setting('reports_per_ip_per_day', '5'));
    $st = db()->prepare('SELECT COUNT(*) FROM establishment_change_requests WHERE ip_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $st->execute([ip_hash()]);
    if ((int)$st->fetchColumn() >= $limit) {
        json_error('Too many submissions today. Please try again tomorrow.', 429);
    }

    $type = (string)($in['request_type'] ?? '');
    if (!in_array($type, ['new', 'correction', 'report_incorrect'], true)) {
        json_error('Invalid request type.', 422);
    }
    $estId = null;
    if ($type !== 'new') {
        if (!isset($in['establishment_id']) || !ctype_digit((string)$in['establishment_id'])) {
            json_error('establishment_id is required.', 422);
        }
        $estId = (int)$in['establishment_id'];
        $q = db()->prepare('SELECT barangay_id FROM establishments WHERE establishment_id = ?');
        $q->execute([$estId]);
        $barangayId = $q->fetchColumn();
        if ($barangayId === false) {
            json_error('Establishment not found.', 404);
        }
        $barangayId = (int)$barangayId;
    } else {
        if (!isset($in['barangay_id']) || !ctype_digit((string)$in['barangay_id'])) {
            json_error('barangay_id is required.', 422);
        }
        $barangayId = (int)$in['barangay_id'];
        $q = db()->prepare('SELECT 1 FROM barangays WHERE barangay_id = ? AND is_active = 1');
        $q->execute([$barangayId]);
        if (!$q->fetchColumn()) {
            json_error('Barangay not found.', 404);
        }
    }

    $payload = [
        'name'        => clean_str($in['name'] ?? null, 160),
        'category_id' => isset($in['category_id']) && ctype_digit((string)$in['category_id']) ? (int)$in['category_id'] : null,
        'address'     => clean_str($in['address'] ?? null, 255),
        'landmark'    => clean_str($in['landmark'] ?? null, 255),
        'zone'        => clean_str($in['zone'] ?? null, 80),
        'phone'       => clean_str($in['phone'] ?? null, 60),
        'message'     => clean_str($in['message'] ?? null, 1000),
    ];
    if ($type === 'new' && $payload['name'] === null) {
        json_error('Please enter the establishment name.', 422);
    }
    if ($type !== 'new' && $payload['message'] === null && $payload['name'] === null && $payload['address'] === null) {
        json_error('Please describe what is wrong or what should change.', 422);
    }
    [$lat, $lng, $coordErr] = validate_coords($in['lat'] ?? null, $in['lng'] ?? null);
    if ($coordErr) {
        json_error($coordErr, 422);
    }
    // Submitted coordinates are stored in the request for staff to check; they are never trusted as verified.
    $payload['lat'] = $lat;
    $payload['lng'] = $lng;

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false || strlen($json) > 8000) {
        json_error('Submission is too large.', 413);
    }

    $contact = clean_str($in['submitter_contact'] ?? null, 190);
    $ins = db()->prepare('INSERT INTO establishment_change_requests
        (establishment_id, barangay_id, request_type, payload, submitter_name, submitter_contact, ip_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$estId, $barangayId, $type, $json, clean_str($in['submitter_name'] ?? null, 120), $contact, ip_hash()]);

    json_out(['ok' => true, 'message' => 'Thank you. Your submission will be reviewed by barangay staff before anything is published.'], 201);
}
