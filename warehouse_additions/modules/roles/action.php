<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Save Role Permissions ─────────────────────────────────────────────────
    case 'save_role_permissions':
        requirePermission('roles', 'edit');

        $roleId = (int)($_POST['role_id'] ?? 0);
        if ($roleId <= 0) {
            setFlash('error', 'Invalid role.');
            redirect('/modules/roles/list.php');
        }

        // Hard guard: Admin (role_id = 1) permissions cannot be modified
        if ($roleId === 1) {
            setFlash('error', 'Admin role permissions cannot be modified.');
            redirect('/modules/roles/list.php');
        }

        // Verify the role exists
        $stRole = $db->prepare("SELECT role_id FROM roles WHERE role_id = ?");
        $stRole->execute([$roleId]);
        if (!$stRole->fetchColumn()) {
            setFlash('error', 'Role not found.');
            redirect('/modules/roles/list.php');
        }

        // Load all valid permission IDs to whitelist submitted values
        $validPerms = $db->query(
            "SELECT permission_id FROM permissions"
        )->fetchAll(PDO::FETCH_COLUMN);
        $validSet = array_flip($validPerms);

        // Collect and whitelist checked permission IDs
        $rawPerms   = $_POST['perms'] ?? [];
        $newPermIds = [];
        if (is_array($rawPerms)) {
            foreach ($rawPerms as $pid) {
                $pid = (int)$pid;
                if (isset($validSet[$pid])) {
                    $newPermIds[] = $pid;
                }
            }
        }

        try {
            $db->beginTransaction();

            // Remove all existing permissions for this role
            $db->prepare(
                "DELETE FROM role_permissions WHERE role_id = ?"
            )->execute([$roleId]);

            // Re-insert only the checked (and whitelisted) permissions
            if ($newPermIds) {
                $stIns = $db->prepare(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)"
                );
                foreach ($newPermIds as $pid) {
                    $stIns->execute([$roleId, $pid]);
                }
            }

            $db->commit();
            logActivity('UPDATE', 'roles', $roleId, 'permissions updated for role_id=' . $roleId);
            setFlash('success', 'Permissions updated successfully.');
            redirect('/modules/roles/list.php');

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error saving permissions: ' . $e->getMessage());
            redirect('/modules/roles/form.php?role_id=' . $roleId);
        }
        break;

    default:
        redirect('/modules/roles/list.php');
}
