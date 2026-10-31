<?php
declare(strict_types=1);

const PASSWORD_MIN_LENGTH = 10;
const PASSWORD_MAX_BYTES  = 72;   // bcrypt ignores anything beyond 72 bytes; reject instead of silently truncating

/** Trim, strip control chars, cap length. Returns null for empty or non-scalar input. */
function clean_str(mixed $v, int $max = 255): ?string
{
    if (!is_string($v) && !is_int($v) && !is_float($v)) {
        return null;
    }
    $s = trim((string)$v);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
    if ($s === '') {
        return null;
    }
    return mb_substr($s, 0, $max, 'UTF-8');
}

function valid_email(?string $v): bool
{
    return $v !== null && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}

/** Login usernames: 3-50 chars of letters, digits, . _ - */
function valid_username(?string $v): bool
{
    return $v !== null && preg_match('/^[A-Za-z0-9._-]{3,50}$/', $v) === 1;
}

/** Business codes (employee code, order number, SKU, task ref): no leading space, limited charset. */
function valid_code(?string $v, int $max = 60): bool
{
    return $v !== null && strlen($v) <= $max && preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-\/ ]*$/', $v) === 1;
}

/** Strict 'Y-m-d H:i:s' parse. Returns the normalised string, or null if blank/invalid (e.g. 2026-02-31 25:00:00). */
function parse_datetime(?string $v): ?string
{
    if ($v === null) {
        return null;
    }
    $v = trim($v);
    $d = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $v);
    if ($d === false || $d->format('Y-m-d H:i:s') !== $v) {
        return null;
    }
    return $v;
}

/** Positive integer from user input (digits only, 1..2^31-1). */
function parse_positive_int(mixed $v): ?int
{
    if (is_int($v)) {
        return $v > 0 ? $v : null;
    }
    if (!is_string($v) || preg_match('/^[0-9]{1,10}$/', trim($v)) !== 1) {
        return null;
    }
    $n = (int)trim($v);
    return ($n > 0 && $n <= 2147483647) ? $n : null;
}

/** @param list<string> $allowed */
function in_list(?string $v, array $allowed): bool
{
    return $v !== null && in_array($v, $allowed, true);
}

/** Password policy. Returns an error string, or null when acceptable. */
function password_policy_error(string $pw): ?string
{
    if (strlen($pw) < PASSWORD_MIN_LENGTH) {
        return 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if (strlen($pw) > PASSWORD_MAX_BYTES) {
        return 'Password must be at most ' . PASSWORD_MAX_BYTES . ' bytes.';
    }
    if (preg_match('/[A-Za-z]/', $pw) !== 1 || preg_match('/\d/', $pw) !== 1) {
        return 'Password must contain letters and numbers.';
    }
    return null;
}
