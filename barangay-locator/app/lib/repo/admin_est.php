<?php
declare(strict_types=1);

/** Staff-side establishment logic: validation, save, duplicate check, verification state machine. */

function est_norm_name(string $s): string
{
    return preg_replace('/[^a-z0-9]+/u', '', mb_strtolower($s, 'UTF-8')) ?? '';
}

function est_get(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM establishments WHERE establishment_id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/** Possible duplicates: same normalised name in the barangay, or another record within ~25 m. */
function est_find_duplicates(int $barangayId, string $name, ?float $lat, ?float $lng, ?int $excludeId): array
{
    $norm = est_norm_name($name);
    $st = db()->prepare('SELECT establishment_id, name, street_address, latitude, longitude, status FROM establishments
                         WHERE barangay_id = ? AND establishment_id <> ? AND status <> \'inactive\'');
    $st->execute([$barangayId, $excludeId ?? 0]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $sameName = $norm !== '' && est_norm_name($r['name']) === $norm;
        $close = false;
        if ($lat !== null && $lng !== null && $r['latitude'] !== null) {
            $dy = ($lat - (float)$r['latitude']) * 111320.0;
            $dx = ($lng - (float)$r['longitude']) * 111320.0 * cos(deg2rad($lat));
            $close = sqrt($dx * $dx + $dy * $dy) <= 25.0;
        }
        if ($sameName || $close) {
            $r['reason'] = $sameName ? 'same name' : 'within 25 m';
            $out[] = $r;
        }
    }
    return $out;
}

/**
 * Validate staff input. Returns [clean array, errors array].
 */
function est_validate(array $in): array
{
    $err = [];
    $d = [];
    $d['name'] = clean_str($in['name'] ?? null, 160);
    if ($d['name'] === null) { $err[] = 'Name is required.'; }

    $d['barangay_id'] = isset($in['barangay_id']) && ctype_digit((string)$in['barangay_id']) ? (int)$in['barangay_id'] : 0;
    if (!$d['barangay_id']) { $err[] = 'Barangay is required.'; }
    elseif (!canAccessBarangay($d['barangay_id'])) { $err[] = 'You cannot manage that barangay.'; }

    $d['primary_category_id'] = isset($in['primary_category_id']) && ctype_digit((string)$in['primary_category_id']) ? (int)$in['primary_category_id'] : 0;
    if (!$d['primary_category_id']) { $err[] = 'Category is required.'; }

    $d['description']    = clean_str($in['description'] ?? null, 2000);
    $d['street_address'] = clean_str($in['street_address'] ?? null, 255);
    $d['landmark']       = clean_str($in['landmark'] ?? null, 255);
    $d['directions_text'] = clean_str($in['directions_text'] ?? null, 1500);
    $d['zone']         = clean_str($in['zone'] ?? null, 80);
    $d['hours_note']     = clean_str($in['hours_note'] ?? null, 255);
    $d['data_source']    = clean_str($in['data_source'] ?? null, 255);
    $d['coords_source']  = clean_str($in['coords_source'] ?? null, 255);
    $d['classification'] = ($in['classification'] ?? '') === 'public' ? 'public' : 'private';

    [$lat, $lng, $ce] = validate_coords($in['latitude'] ?? null, $in['longitude'] ?? null);
    if ($ce) { $err[] = $ce; }
    $d['latitude'] = $lat;
    $d['longitude'] = $lng;

    // Contacts (public).
    $d['contacts'] = [];
    $phone = clean_str($in['phone'] ?? null, 60);
    if ($phone !== null) { $d['contacts'][] = ['phone', $phone]; }
    $mobile = clean_str($in['mobile'] ?? null, 60);
    if ($mobile !== null) { $d['contacts'][] = ['mobile', $mobile]; }
    $email = clean_str($in['email'] ?? null, 190);
    if ($email !== null) {
        if (!valid_email($email)) { $err[] = 'Email address is not valid.'; } else { $d['contacts'][] = ['email', $email]; }
    }
    $web = clean_str($in['website'] ?? null, 190);
    if ($web !== null) {
        if (!valid_http_url($web)) { $err[] = 'Website must start with http:// or https://'; } else { $d['contacts'][] = ['website', $web]; }
    }

    // Weekly hours: day => [open, close, closed, 24h]
    $d['hours'] = [];
    for ($i = 0; $i < 7; $i++) {
        $closed = !empty($in["h{$i}_closed"]);
        $h24    = !empty($in["h{$i}_24h"]);
        $o = trim((string)($in["h{$i}_open"] ?? ''));
        $c = trim((string)($in["h{$i}_close"] ?? ''));
        if ($closed) { $d['hours'][$i] = [null, null, 1, 0]; continue; }
        if ($h24)    { $d['hours'][$i] = [null, null, 0, 1]; continue; }
        if ($o === '' && $c === '') { continue; }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $o) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $c)) {
            $err[] = "Hours for day $i must be HH:MM."; continue;
        }
        $d['hours'][$i] = [$o . ':00', $c . ':00', 0, 0];
    }

    // Facility flags.
    $d['facility_type'] = in_array($in['facility_type'] ?? '', ['government','health','safety','education','evacuation','transport','utility','other'], true)
        ? $in['facility_type'] : null;
    $d['is_emergency']  = !empty($in['is_emergency']) ? 1 : 0;
    $d['services_offered'] = clean_str($in['services_offered'] ?? null, 2000);
    return [$d, $err];
}

/** Persist. Returns the establishment id. Throws on DB errors. */
function est_save(array $d, ?int $id, array $user, bool $canVerify): int
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($id === null) {
            $st = $pdo->prepare('INSERT INTO establishments (barangay_id, name, description, primary_category_id, street_address, landmark, directions_text, zone,
                latitude, longitude, coords_verified, coords_source, classification, status, data_source, hours_note, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,0,?,?,\'pending_verification\',?,?,?)');
            $st->execute([$d['barangay_id'], $d['name'], $d['description'], $d['primary_category_id'], $d['street_address'], $d['landmark'], $d['directions_text'], $d['zone'],
                $d['latitude'], $d['longitude'], $d['coords_source'], $d['classification'], $d['data_source'], $d['hours_note'], $user['user_id']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO verification_records (establishment_id, action, to_status, user_id) VALUES (?, \'created\', \'pending_verification\', ?)')
                ->execute([$id, $user['user_id']]);
        } else {
            $old = est_get($id);
            if (!$old) { throw new RuntimeException('Record not found'); }
            // Coordinates changed => they must be re-checked. Compare both sides at the
            // column's precision (DECIMAL(10,7)): the raw stored string is not always 7 digits,
            // so formatting only the new value would mark every unchanged edit as a move and
            // silently drop coords_verified.
            $at7 = static fn($v): string => $v === null || $v === '' ? '' : number_format((float)$v, 7, '.', '');
            $moved = $at7($old['latitude']) !== $at7($d['latitude'])
                  || $at7($old['longitude']) !== $at7($d['longitude']);
            $coordsVerified = ($moved || $d['latitude'] === null) ? 0 : (int)$old['coords_verified'];
            // A non-verifier editing a verified record sends it back for verification.
            $status = $old['status'];
            if ($status === 'verified' && !$canVerify) { $status = 'pending_verification'; }

            $st = $pdo->prepare('UPDATE establishments SET barangay_id=?, name=?, description=?, primary_category_id=?, street_address=?, landmark=?, directions_text=?, zone=?,
                latitude=?, longitude=?, coords_verified=?, coords_source=?, classification=?, status=?, data_source=?, hours_note=? WHERE establishment_id=?');
            $st->execute([$d['barangay_id'], $d['name'], $d['description'], $d['primary_category_id'], $d['street_address'], $d['landmark'], $d['directions_text'], $d['zone'],
                $d['latitude'], $d['longitude'], $coordsVerified, $d['coords_source'], $d['classification'], $status, $d['data_source'], $d['hours_note'], $id]);
            $pdo->prepare('INSERT INTO verification_records (establishment_id, action, from_status, to_status, note, user_id) VALUES (?, \'edited\', ?, ?, ?, ?)')
                ->execute([$id, $old['status'], $status, $moved ? 'coordinates changed; re-check required' : null, $user['user_id']]);
        }

        // Replace contacts and hours (simple, small child sets).
        $pdo->prepare('DELETE FROM establishment_contacts WHERE establishment_id = ?')->execute([$id]);
        $ci = $pdo->prepare('INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value) VALUES (?,?,?)');
        foreach ($d['contacts'] as [$t, $v]) { $ci->execute([$id, $t, $v]); }

        $pdo->prepare('DELETE FROM operating_hours WHERE establishment_id = ?')->execute([$id]);
        $hi = $pdo->prepare('INSERT INTO operating_hours (establishment_id, day_of_week, opens_at, closes_at, is_closed, is_24h) VALUES (?,?,?,?,?,?)');
        foreach ($d['hours'] as $dow => [$o, $c, $closed, $h24]) { $hi->execute([$id, $dow, $o, $c, $closed, $h24]); }

        // Facility row: keep existing emergency verification unless the emergency flag was turned off.
        $ex = $pdo->prepare('SELECT is_emergency, emergency_verified_at FROM facilities WHERE establishment_id = ?');
        $ex->execute([$id]);
        $f = $ex->fetch();
        if ($d['facility_type'] !== null || $d['is_emergency'] || $d['services_offered'] !== null || $f) {
            $verifiedAt = ($f && $d['is_emergency'] && $f['emergency_verified_at']) ? $f['emergency_verified_at'] : null;
            $pdo->prepare('INSERT INTO facilities (establishment_id, facility_type, is_emergency, emergency_verified_at, services_offered)
                           VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE facility_type=VALUES(facility_type), is_emergency=VALUES(is_emergency),
                           emergency_verified_at=VALUES(emergency_verified_at), services_offered=VALUES(services_offered)')
                ->execute([$id, $d['facility_type'] ?? 'other', $d['is_emergency'], $verifiedAt, $d['services_offered']]);
        }

        // Keep the category link table in sync with the primary category.
        $pdo->prepare('DELETE FROM establishment_categories WHERE establishment_id = ?')->execute([$id]);
        $pdo->prepare('INSERT INTO establishment_categories (establishment_id, category_id) VALUES (?,?)')->execute([$id, $d['primary_category_id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
    return $id;
}

/**
 * Verification state machine. Returns [ok, message].
 * Every transition needs evidence text where it asserts something is true.
 */
function est_apply_action(int $id, string $action, ?string $evidence, array $user): array
{
    $e = est_get($id);
    if (!$e) { return [false, 'Record not found.']; }
    if (!canAccessBarangay((int)$e['barangay_id'])) { return [false, 'You cannot manage that barangay.']; }
    $pdo = db();
    $from = $e['status'];
    $evidence = $evidence !== null ? mb_substr(trim($evidence), 0, 255, 'UTF-8') : null;
    if ($evidence === '') { $evidence = null; }

    switch ($action) {
        case 'verify':
            if ($evidence === null) { return [false, 'Describe how this record was verified (e.g. "site visit 2026-10-20 by encoder").']; }
            $pdo->prepare('UPDATE establishments SET status=\'verified\', last_verified_at=NOW(), verified_by=? WHERE establishment_id=?')->execute([$user['user_id'], $id]);
            $to = 'verified'; $act = 'verify';
            break;
        case 'flag_review':
            $pdo->prepare('UPDATE establishments SET status=\'needs_review\' WHERE establishment_id=?')->execute([$id]);
            $to = 'needs_review'; $act = 'flag_review';
            break;
        case 'deactivate':
            $pdo->prepare('UPDATE establishments SET status=\'inactive\' WHERE establishment_id=?')->execute([$id]);
            $to = 'inactive'; $act = 'deactivate';
            break;
        case 'reactivate':
            $pdo->prepare('UPDATE establishments SET status=\'pending_verification\' WHERE establishment_id=?')->execute([$id]);
            $to = 'pending_verification'; $act = 'reactivate';
            break;
        case 'coords_verified':
            if ($e['latitude'] === null) { return [false, 'This record has no coordinates to verify.']; }
            if ($evidence === null) { return [false, 'Describe how the map position was checked.']; }
            $pdo->prepare('UPDATE establishments SET coords_verified=1, coords_source=COALESCE(?, coords_source) WHERE establishment_id=?')->execute([$evidence, $id]);
            $to = $from; $act = 'coords_verified';
            break;
        case 'emergency_verified':
            $f = $pdo->prepare('SELECT is_emergency FROM facilities WHERE establishment_id=?');
            $f->execute([$id]);
            if ((int)$f->fetchColumn() !== 1) { return [false, 'This record is not flagged as an emergency facility.']; }
            if ($evidence === null) { return [false, 'Describe how the emergency role was confirmed.']; }
            $pdo->prepare('UPDATE facilities SET emergency_verified_at=NOW(), emergency_verified_by=? WHERE establishment_id=?')->execute([$user['user_id'], $id]);
            $to = $from; $act = 'emergency_verified';
            break;
        default:
            return [false, 'Unknown action.'];
    }
    $pdo->prepare('INSERT INTO verification_records (establishment_id, action, from_status, to_status, evidence, user_id) VALUES (?,?,?,?,?,?)')
        ->execute([$id, $act, $from, $to, $evidence, $user['user_id']]);
    logAudit($act, 'establishment', $id, $evidence);
    return [true, 'Done.'];
}
