<?php
declare(strict_types=1);

const ESTABLISHMENT_STATUSES = ['pending_verification', 'verified', 'needs_review', 'inactive'];

/** Trim, collapse control chars, cap length. Returns null for empty. */
function clean_str($v, int $max = 255): ?string
{
    if (!is_string($v) && !is_numeric($v)) {
        return null;
    }
    $s = trim((string)$v);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
    if ($s === '') {
        return null;
    }
    return mb_substr($s, 0, $max, 'UTF-8');
}

/** Returns float or null if blank/invalid. Range-checked for latitude. */
function parse_lat($v): ?float
{
    if ($v === null || $v === '') {
        return null;
    }
    if (!is_numeric($v)) {
        return null;
    }
    $f = (float)$v;
    return ($f >= -90.0 && $f <= 90.0) ? $f : null;
}

function parse_lng($v): ?float
{
    if ($v === null || $v === '') {
        return null;
    }
    if (!is_numeric($v)) {
        return null;
    }
    $f = (float)$v;
    return ($f >= -180.0 && $f <= 180.0) ? $f : null;
}

/**
 * Validate a lat/lng pair as entered. Returns [lat, lng, error].
 * Both blank = ok (no coordinates). One blank or out-of-range = error.
 */
function validate_coords($lat, $lng): array
{
    $blankLat = ($lat === null || trim((string)$lat) === '');
    $blankLng = ($lng === null || trim((string)$lng) === '');
    if ($blankLat && $blankLng) {
        return [null, null, null];
    }
    if ($blankLat || $blankLng) {
        return [null, null, 'Enter both latitude and longitude, or leave both blank.'];
    }
    $a = parse_lat($lat);
    $b = parse_lng($lng);
    if ($a === null || $b === null) {
        return [null, null, 'Latitude must be between -90 and 90 and longitude between -180 and 180.'];
    }
    return [$a, $b, null];
}

function valid_email(?string $v): bool
{
    return $v !== null && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_http_url(?string $v): bool
{
    if ($v === null) {
        return false;
    }
    $p = parse_url($v);
    return $p !== false && isset($p['scheme'], $p['host']) && in_array(strtolower($p['scheme']), ['http', 'https'], true);
}

function valid_hex_color(?string $v): bool
{
    return $v !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $v) === 1;
}
