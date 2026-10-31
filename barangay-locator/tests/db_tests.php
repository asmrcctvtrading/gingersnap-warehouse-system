<?php
declare(strict_types=1);
/**
 * Database-backed rule tests. Run against a THROW-AWAY database only:
 *   php tests/db_tests.php <host> <dbname> <user> <password>
 * It creates the schema, loads base seeds + sample data, then checks public visibility rules.
 * It refuses to run unless the database name contains "test".
 *
 * STATUS: written but NOT yet executed by the author.
 */

[$_, $host, $name, $user, $pass] = array_pad($argv, 5, '');
if ($name === '' || stripos($name, 'test') === false) {
    fwrite(STDERR, "Refusing to run: pass a database whose name contains 'test'.\n");
    exit(2);
}

$pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$root = dirname(__DIR__);

// Minimal bootstrap: reuse libs without the web bootstrap.
$GLOBALS['__test_pdo'] = $pdo;
function config(string $k = '', $d = null) { return $k === 'base_url' ? '' : $d; }
require $root . '/app/lib/http.php';
require $root . '/app/lib/validate.php';
require $root . '/app/lib/i18n.php';
function db(): PDO { return $GLOBALS['__test_pdo']; }
function setting(string $key, ?string $default = null): ?string
{
    $st = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : (string)$v;
}
function set_setting(string $key, string $value): void
{
    db()->prepare('REPLACE INTO app_settings (setting_key, setting_value) VALUES (?, ?)')->execute([$key, $value]);
}
require $root . '/app/lib/repo/establishments.php';

$src = file_get_contents($root . '/install/install.php');
preg_match('/function split_sql\(.*?\n}\n/s', $src, $m);
eval($m[0]);
foreach (['schema.sql', 'seeds/base.sql', 'seeds/sample_data.sql'] as $f) {
    foreach (split_sql(file_get_contents($root . '/database/' . $f)) as $stmt) { $pdo->exec($stmt); }
}

$pass = 0; $fail = 0;
function check(string $n, bool $c): void { global $pass, $fail; if ($c) { $pass++; echo "  ok   $n\n"; } else { $fail++; echo "  FAIL $n\n"; } }

// Read what the repository layer returns for the public. Adjust if repo_search's signature changes.
set_setting('public_show_unverified', '1');
set_setting('plot_unverified_coords', '0');
$items = repo_search([], 200)['items'];
$statuses = array_unique(array_column($items, 'status'));
check('sample data is loaded', count($items) > 0);
check('needs_review never public', !in_array('needs_review', $statuses, true));
check('inactive never public', !in_array('inactive', $statuses, true));
check('verified records are public', in_array('verified', $statuses, true));

// Unverified coordinates must not be exposed unless the admin setting allows it.
$leak = array_filter($items, fn($r) => $r['lat'] !== null && !$r['coords_verified']);
check('unverified coordinates withheld by default', count($leak) === 0);
$unplotted = array_filter($items, fn($r) => $r['has_coordinates'] && !$r['coords_verified']);
check('sample set contains a record with unchecked coords (test is meaningful)', count($unplotted) > 0);
check('markers only contain plottable points', count(repo_markers([])) === count(array_filter($items, fn($r) => $r['plottable'])));

// Emergency flag only when staff verified it.
$emerg = array_filter($items, fn($r) => $r['emergency']);
$unverifiedEmerg = $pdo->query("SELECT COUNT(*) FROM facilities WHERE is_emergency = 1 AND emergency_verified_at IS NULL")->fetchColumn();
check('sample set contains an unverified emergency flag (test is meaningful)', (int)$unverifiedEmerg > 0);
check('unverified emergency flag not exposed', count($emerg) === (int)$pdo->query("SELECT COUNT(*) FROM facilities f JOIN establishments e USING (establishment_id) WHERE f.is_emergency = 1 AND f.emergency_verified_at IS NOT NULL AND e.status IN ('verified','pending_verification')")->fetchColumn());

set_setting('public_show_unverified', '0');
$items = repo_search([], 200)['items'];
check('pending hidden when setting off', !in_array('pending_verification', array_column($items, 'status'), true));

set_setting('public_show_unverified', '1');
set_setting('plot_unverified_coords', '1');
$items = repo_search([], 200)['items'];
check('unchecked coords plotted only after admin opts in', count(array_filter($items, fn($r) => $r['lat'] !== null && !$r['coords_verified'])) > 0);

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
