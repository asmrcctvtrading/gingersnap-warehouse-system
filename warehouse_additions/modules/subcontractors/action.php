<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create_subcon':
        requirePermission('subcontractors', 'create');
        $name = trim($_POST['subcon_name'] ?? '');
        if ($name === '') {
            setFlash('error', 'Subcontractor name is required.');
            redirect('/modules/subcontractors/form.php');
        }
        try {
            $stmt = $db->prepare("INSERT INTO subcons (subcon_name) VALUES (:n)");
            $stmt->execute(['n' => $name]);
            $newId = (int)$db->lastInsertId();
            logActivity('CREATE', 'subcons', $newId, $name);
            setFlash('success', 'Subcontractor "' . $name . '" created.');
            redirect('/modules/subcontractors/list.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'A subcontractor with that name already exists.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect('/modules/subcontractors/form.php');
        }
        break;

    case 'update_subcon':
        requirePermission('subcontractors', 'edit');
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['subcon_name'] ?? '');
        if ($id <= 0 || $name === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/subcontractors/list.php');
        }
        try {
            $stmt = $db->prepare("UPDATE subcons SET subcon_name = :n WHERE subcon_id = :id");
            $stmt->execute(['n' => $name, 'id' => $id]);
            logActivity('UPDATE', 'subcons', $id, $name);
            setFlash('success', 'Subcontractor updated.');
            redirect('/modules/subcontractors/list.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('error', 'A subcontractor with that name already exists.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect('/modules/subcontractors/form.php?id=' . $id);
        }
        break;

    case 'delete_subcon':
        requirePermission('subcontractors', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid subcontractor.');
            redirect('/modules/subcontractors/list.php');
        }
        // Safety check: refuse if any POs reference this subcon
        $check = $db->prepare("SELECT COUNT(*) FROM purchase_orders WHERE subcon_id = ?");
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) {
            setFlash('error', 'Cannot delete: this subcontractor is linked to existing purchase orders.');
            redirect('/modules/subcontractors/list.php');
        }
        $db->prepare("DELETE FROM subcons WHERE subcon_id = ?")->execute([$id]);
        logActivity('DELETE', 'subcons', $id, null);
        setFlash('success', 'Subcontractor deleted.');
        redirect('/modules/subcontractors/list.php');
        break;

    default:
        redirect('/modules/subcontractors/list.php');
}
