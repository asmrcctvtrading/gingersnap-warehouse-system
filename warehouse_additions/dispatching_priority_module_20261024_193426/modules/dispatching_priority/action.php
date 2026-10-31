<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$action = $_POST['action'] ?? '';
if (!in_array($action, ['create', 'update', 'delete'])) {
    redirect('list.php');
}

$db = getDB();

// Check permission based on action
if ($action === 'delete') {
    requirePermission('dispatching_priority', 'delete');
} else {
    requirePermission('dispatching_priority', 'edit');
}

// CSRF check
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid CSRF token';
    redirect('list.php');
}

try {
    if ($action === 'create') {
        requirePermission('dispatching_priority', 'create');

        $gatepass_order = (int)($_POST['gatepass_order'] ?? 0);
        $local_export = sanitizeInput($_POST['local_export'] ?? '');
        $ctrl_number = sanitizeInput($_POST['ctrl_number'] ?? '');
        $gatepass_created = sanitizeInput($_POST['gatepass_created'] ?? 'PENDING');
        $received_at_dc = sanitizeInput($_POST['received_at_dc'] ?? '');
        $qty_local = (int)($_POST['qty_local'] ?? 0);
        $qty_sg = (int)($_POST['qty_sg'] ?? 0);
        $qty_malaysia = (int)($_POST['qty_malaysia'] ?? 0);
        $qty_indo = (int)($_POST['qty_indo'] ?? 0);
        $qty_sm_baliwag = (int)($_POST['qty_sm_baliwag'] ?? 0);
        $qty_export_full = (int)($_POST['qty_export_full'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'NEW');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        $ticket_number = sanitizeInput($_POST['ticket_number'] ?? '');
        $subcon_name = sanitizeInput($_POST['subcon_name'] ?? '');
        $collection_name = sanitizeInput($_POST['collection_name'] ?? '');
        $priority_rank = $_POST['priority_rank'] === '' ? null : (int)$_POST['priority_rank'];
        $created_by = (int)($_POST['created_by'] ?? $_SESSION['user_id']);

        $stmt = $db->prepare("
            INSERT INTO dispatching_priority
            (gatepass_order, local_export, ctrl_number, gatepass_created, received_at_dc,
             qty_local, qty_sg, qty_malaysia, qty_indo, qty_sm_baliwag, qty_export_full,
             status, remarks, ticket_number, subcon_name, collection_name, priority_rank, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $gatepass_order, $local_export, $ctrl_number, $gatepass_created, $received_at_dc,
            $qty_local, $qty_sg, $qty_malaysia, $qty_indo, $qty_sm_baliwag, $qty_export_full,
            $status, $remarks, $ticket_number, $subcon_name, $collection_name, $priority_rank, $created_by
        ]);

        $id = $db->lastInsertId();

        logActivity('Dispatching Priority', $id, 'Created dispatching priority entry', $created_by);

        $_SESSION['success'] = 'Dispatching priority entry created successfully';

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid record ID');
        }

        $existing = $db->query("SELECT * FROM dispatching_priority WHERE id = $id")->fetch();
        if (!$existing) {
            throw new Exception('Record not found');
        }

        $gatepass_order = (int)($_POST['gatepass_order'] ?? $existing['gatepass_order']);
        $local_export = sanitizeInput($_POST['local_export'] ?? $existing['local_export']);
        $ctrl_number = sanitizeInput($_POST['ctrl_number'] ?? $existing['ctrl_number']);
        $gatepass_created = sanitizeInput($_POST['gatepass_created'] ?? $existing['gatepass_created']);
        $received_at_dc = sanitizeInput($_POST['received_at_dc'] ?? $existing['received_at_dc']);
        $qty_local = (int)($_POST['qty_local'] ?? $existing['qty_local']);
        $qty_sg = (int)($_POST['qty_sg'] ?? $existing['qty_sg']);
        $qty_malaysia = (int)($_POST['qty_malaysia'] ?? $existing['qty_malaysia']);
        $qty_indo = (int)($_POST['qty_indo'] ?? $existing['qty_indo']);
        $qty_sm_baliwag = (int)($_POST['qty_sm_baliwag'] ?? $existing['qty_sm_baliwag']);
        $qty_export_full = (int)($_POST['qty_export_full'] ?? $existing['qty_export_full']);
        $status = sanitizeInput($_POST['status'] ?? $existing['status']);
        $remarks = sanitizeInput($_POST['remarks'] ?? $existing['remarks']);
        $ticket_number = sanitizeInput($_POST['ticket_number'] ?? $existing['ticket_number']);
        $subcon_name = sanitizeInput($_POST['subcon_name'] ?? $existing['subcon_name']);
        $collection_name = sanitizeInput($_POST['collection_name'] ?? $existing['collection_name']);
        $priority_rank = $_POST['priority_rank'] === '' ? null : (int)$_POST['priority_rank'];

        $stmt = $db->prepare("
            UPDATE dispatching_priority
            SET gatepass_order = ?, local_export = ?, ctrl_number = ?, gatepass_created = ?, received_at_dc = ?,
                qty_local = ?, qty_sg = ?, qty_malaysia = ?, qty_indo = ?, qty_sm_baliwag = ?, qty_export_full = ?,
                status = ?, remarks = ?, ticket_number = ?, subcon_name = ?, collection_name = ?, priority_rank = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $gatepass_order, $local_export, $ctrl_number, $gatepass_created, $received_at_dc,
            $qty_local, $qty_sg, $qty_malaysia, $qty_indo, $qty_sm_baliwag, $qty_export_full,
            $status, $remarks, $ticket_number, $subcon_name, $collection_name, $priority_rank, $id
        ]);

        logActivity('Dispatching Priority', $id, 'Updated dispatching priority entry', $_SESSION['user_id']);

        $_SESSION['success'] = 'Dispatching priority entry updated successfully';

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid record ID');
        }

        $existing = $db->query("SELECT * FROM dispatching_priority WHERE id = $id")->fetch();
        if (!$existing) {
            throw new Exception('Record not found');
        }

        $stmt = $db->prepare("DELETE FROM dispatching_priority WHERE id = ?");
        $stmt->execute([$id]);

        logActivity('Dispatching Priority', $id, 'Deleted dispatching priority entry', $_SESSION['user_id']);

        $_SESSION['success'] = 'Dispatching priority entry deleted successfully';
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

redirect('list.php');
