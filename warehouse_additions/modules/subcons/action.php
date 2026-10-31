<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Create or update subcontractor ───────────────────────────────────────

    case 'save_subcon':
        $id     = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0;

        if ($isEdit) {
            requirePermission('subcons', 'edit');
        } else {
            requirePermission('subcons', 'create');
        }

        $subconName    = trim($_POST['subcon_name']    ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '') ?: null;
        $phone         = trim($_POST['phone']          ?? '') ?: null;
        $address       = trim($_POST['address']        ?? '') ?: null;
        $status        = in_array($_POST['status'] ?? '', ['active', 'inactive'])
                             ? $_POST['status'] : 'active';

        if ($subconName === '') {
            setFlash('error', 'Subcontractor name is required.');
            redirect($isEdit ? '/modules/subcons/form.php?id=' . $id : '/modules/subcons/form.php');
        }

        try {
            if ($isEdit) {
                // Verify record exists
                $stCheck = $db->prepare("SELECT subcon_id FROM subcons WHERE subcon_id = ?");
                $stCheck->execute([$id]);
                if (!$stCheck->fetch()) {
                    setFlash('error', 'Subcontractor not found.');
                    redirect('/modules/subcons/list.php');
                }

                $stmt = $db->prepare(
                    "UPDATE subcons
                     SET subcon_name    = :nm,
                         contact_person = :cp,
                         phone          = :ph,
                         address        = :ad,
                         status         = :st
                     WHERE subcon_id = :id"
                );
                $stmt->execute([
                    'nm' => $subconName,
                    'cp' => $contactPerson,
                    'ph' => $phone,
                    'ad' => $address,
                    'st' => $status,
                    'id' => $id,
                ]);
                logAudit($db, 'UPDATE', 'subcons', $id, $subconName);
                setFlash('success', 'Subcontractor "' . $subconName . '" updated.');
                redirect('/modules/subcons/list.php');

            } else {
                $stmt = $db->prepare(
                    "INSERT INTO subcons (subcon_name, contact_person, phone, address, status)
                     VALUES (:nm, :cp, :ph, :ad, :st)"
                );
                $stmt->execute([
                    'nm' => $subconName,
                    'cp' => $contactPerson,
                    'ph' => $phone,
                    'ad' => $address,
                    'st' => 'active',
                ]);
                $newId = (int)$db->lastInsertId();
                logAudit($db, 'CREATE', 'subcons', $newId, $subconName);
                setFlash('success', 'Subcontractor "' . $subconName . '" created.');
                redirect('/modules/subcons/list.php');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Database error: ' . $e->getMessage());
            redirect($isEdit ? '/modules/subcons/form.php?id=' . $id : '/modules/subcons/form.php');
        }
        break;

    // ── Toggle active / inactive status ─────────────────────────────────────

    case 'toggle_status':
        requirePermission('subcons', 'edit');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid subcontractor.');
            redirect('/modules/subcons/list.php');
        }

        $stCheck = $db->prepare("SELECT subcon_id, subcon_name, status FROM subcons WHERE subcon_id = ?");
        $stCheck->execute([$id]);
        $subcon = $stCheck->fetch();
        if (!$subcon) {
            setFlash('error', 'Subcontractor not found.');
            redirect('/modules/subcons/list.php');
        }

        $newStatus = $subcon['status'] === 'active' ? 'inactive' : 'active';
        $db->prepare("UPDATE subcons SET status = ? WHERE subcon_id = ?")
           ->execute([$newStatus, $id]);

        $action_label = $newStatus === 'inactive' ? 'deactivated' : 'reactivated';
        logAudit($db, 'TOGGLE_STATUS', 'subcons', $id,
            $subcon['subcon_name'] . ' → ' . $newStatus);
        setFlash('success', 'Subcontractor "' . $subcon['subcon_name'] . '" ' . $action_label . '.');
        redirect('/modules/subcons/list.php');
        break;

    default:
        redirect('/modules/subcons/list.php');
}
