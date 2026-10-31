<?php
declare(strict_types=1);

// Pure-PHP unit tests (no database). Run: php tests/run_tests.php   Exit code 0 = all passed.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

function config(string $key, mixed $default = null): mixed { return $default; }   // stub: bootstrap is not loaded
$_SESSION = [];

require_once __DIR__ . '/../app/lib/http.php';
require_once __DIR__ . '/../app/lib/csrf.php';
require_once __DIR__ . '/../app/lib/validate.php';
require_once __DIR__ . '/../app/lib/audit.php';

$pass = 0; $fail = 0;
function check(string $name, bool $cond): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $name\n"; } else { $fail++; echo "  FAIL $name\n"; }
}

echo "http\n";
check('h escapes tags and quotes', h('<a href="x">\'') === '&lt;a href=&quot;x&quot;&gt;&#039;');
check('url joins with single slash', url('/a.php') === '/a.php');

echo "validate\n";
check('clean_str trims', clean_str("  abc \t") === 'abc');
check('clean_str blank -> null', clean_str('   ') === null);
check('clean_str array -> null', clean_str(['x']) === null);
check('clean_str strips control chars', clean_str("a\x00b") === 'ab');
check('clean_str caps length', clean_str(str_repeat('x', 300), 10) === str_repeat('x', 10));
check('valid_username ok', valid_username('abreeza.ayala') === true);
check('valid_username rejects space', valid_username('a b') === false);
check('valid_username rejects too short', valid_username('ab') === false);
check('valid_code ok', valid_code('112191_WFR') === true);
check('valid_code rejects leading space', valid_code(' X1') === false);
check('valid_code rejects quote', valid_code("X'1") === false);
check('valid_code rejects too long', valid_code(str_repeat('A', 61)) === false);
check('parse_datetime ok', parse_datetime('2026-10-24 13:05:09') === '2026-10-24 13:05:09');
check('parse_datetime rejects 31 Feb', parse_datetime('2026-02-31 10:00:00') === null);
check('parse_datetime rejects hour 25', parse_datetime('2026-10-24 25:00:00') === null);
check('parse_datetime rejects date only', parse_datetime('2026-10-24') === null);
check('parse_datetime null -> null', parse_datetime(null) === null);
check('parse_positive_int ok', parse_positive_int('42') === 42);
check('parse_positive_int rejects 0', parse_positive_int('0') === null);
check('parse_positive_int rejects negative', parse_positive_int('-5') === null);
check('parse_positive_int rejects decimal', parse_positive_int('1.5') === null);
check('parse_positive_int rejects overflow', parse_positive_int('99999999999') === null);
check('valid_email ok', valid_email('a@b.co') === true);
check('valid_email rejects junk', valid_email('nope') === false);
check('in_list strict', in_list('1', ['1']) === true && in_list('2', ['1']) === false);
check('password too short', password_policy_error('abc123') !== null);
check('password no digit', password_policy_error('abcdefghijkl') !== null);
check('password no letter', password_policy_error('1234567890123') !== null);
check('password too long (>72 bytes)', password_policy_error(str_repeat('a1', 40)) !== null);
check('password ok', password_policy_error('warehouse2026ok') === null);

echo "csrf\n";
$_SESSION = [];
$t = csrf_token();
check('token is 64 hex chars', preg_match('/^[0-9a-f]{64}$/', $t) === 1);
check('token is stable within session', csrf_token() === $t);
check('valid token accepted', csrf_valid($t) === true);
check('wrong token rejected', csrf_valid(str_repeat('0', 64)) === false);
check('empty token rejected', csrf_valid('') === false);
check('null token rejected', csrf_valid(null) === false);

echo "audit\n";
$s = audit_scrub(['user' => 'x', 'password' => 'p', 'nested' => ['csrf_token' => 't', 'ok' => 1], 'Cookie' => 'c']);
check('scrub redacts password', $s['password'] === '[redacted]');
check('scrub redacts nested token', $s['nested']['csrf_token'] === '[redacted]' && $s['nested']['ok'] === 1);
check('scrub redacts cookie (case-insensitive)', $s['Cookie'] === '[redacted]');
check('scrub keeps normal keys', $s['user'] === 'x');

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
