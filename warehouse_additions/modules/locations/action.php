<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Location CRUD ─────────────────────────────────────────────────────

    case 'create_location':
        requirePermission('locations', 'create');
        $locCode = trim($_POST['location_code'] ?? '');
        if ($locCode === '') {
            setFlash('error', 'Location code is required.');
            redirect('/modules/locations/form.php');
        }
        $stmt = $db->prepare(
            "INSERT INTO warehouse_locations
             (location_code, zone, description, status)
             VALUES (:lc, :zn, :ds, :st)"
        );
        $stmt->execute([
            'lc' => $locCode,
            'zn' => trim($_POST['zone'] ?? ''),
            'ds' => trim($_POST['description'] ?? '') ?: null,
            'st' => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
        ]);
        logActivity('CREATE', 'warehouse_locations', (int)$db->lastInsertId(), $locCode);
        setFlash('success', 'Location created.');
        redirect('/modules/locations/list.php');
        break;

    case 'update_location':
        requirePermission('locations', 'edit');
        $id      = (int)($_POST['id'] ?? 0);
        $locCode = trim($_POST['location_code'] ?? '');
        if ($id <= 0 || $locCode === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/locations/list.php');
        }
        $stmt = $db->prepare(
            "UPDATE warehouse_locations
             SET location_code=:lc, zone=:zn, description=:ds, status=:st
             WHERE location_id=:id"
        );
        $stmt->execute([
            'lc' => $locCode,
            'zn' => trim($_POST['zone'] ?? ''),
            'ds' => trim($_POST['description'] ?? '') ?: null,
            'st' => in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active',
            'id' => $id,
        ]);
        logActivity('UPDATE', 'warehouse_locations', $id, $locCode);
        setFlash('success', 'Location updated.');
        redirect('/modules/locations/list.php');
        break;

    case 'delete_location':
        requirePermission('locations', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid location.');
            redirect('/modules/locations/list.php');
        }
        $db->prepare("DELETE FROM warehouse_locations WHERE location_id = ?")->execute([$id]);
        logActivity('DELETE', 'warehouse_locations', $id, null);
        setFlash('success', 'Location deleted.');
        redirect('/modules/locations/list.php');
        break;

    // ── Zone CRUD ─────────────────────────────────────────────────────────

    case 'create_zone':
        requirePermission('locations', 'create');
        $zoneCode = trim($_POST['zone_code'] ?? '');
        $zoneName = trim($_POST['zone_name'] ?? '');
        if ($zoneCode === '' || $zoneName === '') {
            setFlash('error', 'Zone code and name are required.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $stmt = $db->prepare(
            "INSERT INTO warehouse_zones (zone_code, zone_name, zone_type, notes, is_active, created_by)
             VALUES (:zc, :zn, :zt, :nt, 1, :cb)"
        );
        $stmt->execute([
            'zc' => $zoneCode,
            'zn' => $zoneName,
            'zt' => $_POST['zone_type'] ?? 'STORAGE',
            'nt' => trim($_POST['notes'] ?? '') ?: null,
            'cb' => $_SESSION['user_id'] ?? null,
        ]);
        logActivity('CREATE', 'warehouse_zones', (int)$db->lastInsertId(), $zoneCode);
        setFlash('success', 'Zone created.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    case 'update_zone':
        requirePermission('locations', 'edit');
        $zoneId   = (int)($_POST['zone_id'] ?? 0);
        $zoneCode = trim($_POST['zone_code'] ?? '');
        $zoneName = trim($_POST['zone_name'] ?? '');
        if ($zoneId <= 0 || $zoneCode === '' || $zoneName === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $stmt = $db->prepare(
            "UPDATE warehouse_zones
             SET zone_code=:zc, zone_name=:zn, zone_type=:zt, notes=:nt, is_active=:ia
             WHERE zone_id=:id"
        );
        $stmt->execute([
            'zc' => $zoneCode,
            'zn' => $zoneName,
            'zt' => $_POST['zone_type'] ?? 'STORAGE',
            'nt' => trim($_POST['notes'] ?? '') ?: null,
            'ia' => isset($_POST['is_active']) ? 1 : 0,
            'id' => $zoneId,
        ]);
        logActivity('UPDATE', 'warehouse_zones', $zoneId, $zoneCode);
        setFlash('success', 'Zone updated.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    case 'delete_zone':
        requirePermission('locations', 'delete');
        $zoneId = (int)($_POST['zone_id'] ?? 0);
        if ($zoneId <= 0) {
            setFlash('error', 'Invalid zone.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $db->prepare("DELETE FROM warehouse_zones WHERE zone_id = ?")->execute([$zoneId]);
        logActivity('DELETE', 'warehouse_zones', $zoneId, null);
        setFlash('success', 'Zone deleted.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    default:
        redirect('/modules/locations/list.php');
}
