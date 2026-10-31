<?php
declare(strict_types=1);
/**
 * Dependency-free unit tests. Run:  php tests/run_tests.php
 * Covers pure functions only (validators, SQL splitter, CSRF/i18n helpers).
 * Database-backed rules (public visibility, verification state machine) are covered by
 * tests/db_tests.php, which needs a throw-away MySQL database - see docs/TEST_PLAN.md.
 *
 * STATUS: written but NOT yet executed by the author (no PHP available in the build environment).
 */

$root = dirname(__DIR__);
require $root . '/app/lib/validate.php';

$pass = 0; $fail = 0;
function check(string $name, bool $cond): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $name\n"; } else { $fail++; echo "  FAIL $name\n"; }
}

echo "validators\n";
check('lat in range', parse_lat('14.5') === 14.5);
check('lat out of range', parse_lat('91') === null);
check('lat not numeric', parse_lat('abc') === null);
check('lng out of range', parse_lng('-181') === null);
check('coords both blank ok', validate_coords('', null) === [null, null, null]);
check('coords one blank error', validate_coords('14.6', '')[2] !== null);
check('coords valid', validate_coords('14.6', '121.1') === [14.6, 121.1, null]);
check('coords out of range', validate_coords('140', '121')[2] !== null);
check('clean_str empty -> null', clean_str("   ") === null);
check('clean_str strips control chars', clean_str("a\x00b") === 'ab');
check('clean_str caps length', mb_strlen((string)clean_str(str_repeat('x', 500), 10)) === 10);
check('email valid', valid_email('a@b.ph'));
check('email invalid', !valid_email('not-an-email'));
check('url http ok', valid_http_url('https://example.com/x'));
check('url javascript rejected', !valid_http_url('javascript:alert(1)'));
check('hex colour', valid_hex_color('#a1B2c3') && !valid_hex_color('red'));

echo "installer SQL splitter\n";
$src = file_get_contents($root . '/install/install.php');
if (preg_match('/function split_sql\(.*?\n}\n/s', $src, $m)) {
    eval($m[0]);
    $parts = split_sql("CREATE TABLE a (x INT);\n-- comment; with semicolon\nINSERT INTO a VALUES ('a;b');\nINSERT INTO a VALUES (2);");
    check('splits into 3 statements', count($parts) === 3);
    check('semicolon inside quotes preserved', isset($parts[1]) && strpos($parts[1], "'a;b'") !== false);
    check('comment dropped', strpos(implode(' ', $parts), 'comment') === false);
} else {
    check('split_sql found in install.php', false);
}

echo "i18n\n";
$en = require $root . '/app/lang/en.php';
$tl = require $root . '/app/lang/tl.php';
check('en table is array', is_array($en) && count($en) > 20);
$missing = array_diff(array_keys($tl), array_keys($en));
check('no Tagalog key lacks an English key', count($missing) === 0);

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
