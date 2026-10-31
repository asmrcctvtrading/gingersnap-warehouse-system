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
    requirePermission('rss', 'delete');
} else {
    requirePermission('rss', 'edit');
}

// CSRF check
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid CSRF token';
    redirect('list.php');
}

try {
    if ($action === 'create') {
        requirePermission('rss', 'create');

        $rss_type = sanitizeInput($_POST['rss_type'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'PENDING');
        $grn_id = (int)($_POST['grn_id'] ?? 0);
        $po_id = (int)($_POST['po_id'] ?? 0);
        $item_id = (int)($_POST['item_id'] ?? 0);
        $qty_out = (int)($_POST['qty_out'] ?? 0);
        $qty_in = (int)($_POST['qty_in'] ?? 0);
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        $created_by = (int)($_POST['created_by'] ?? $_SESSION['user_id']);

        $stmt = $db->prepare("
            INSERT INTO rss (grn_id, item_id, po_id, qty_out, qty_in, status, rss_type, remarks, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$grn_id, $item_id, $po_id, $qty_out, $qty_in, $status, $rss_type, $remarks, $created_by]);

        $rss_id = $db->lastInsertId();

        logActivity('RSS', $rss_id, 'Created new RSS record', $created_by);

        $_SESSION['success'] = 'RSS record created successfully';

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid record ID');
        }

        $existing = $db->query("SELECT * FROM rss WHERE rss_id = $id")->fetch();
        if (!$existing) {
            throw new Exception('Record not found');
        }

        $rss_type = sanitizeInput($_POST['rss_type'] ?? $existing['rss_type']);
        $status = sanitizeInput($_POST['status'] ?? $existing['status']);
        $grn_id = (int)($_POST['grn_id'] ?? $existing['grn_id']);
        $po_id = (int)($_POST['po_id'] ?? $existing['po_id']);
        $item_id = (int)($_POST['item_id'] ?? $existing['item_id']);
        $qty_out = (int)($_POST['qty_out'] ?? $existing['qty_out']);
        $qty_in = (int)($_POST['qty_in'] ?? $existing['qty_in']);
        $remarks = sanitizeInput($_POST['remarks'] ?? $existing['remarks']);

        $stmt = $db->prepare("
            UPDATE rss
            SET grn_id = ?, item_id = ?, po_id = ?, qty_out = ?, qty_in = ?,
                status = ?, rss_type = ?, remarks = ?, updated_at = NOW()
            WHERE rss_id = ?
        ");
        $stmt->execute([$grn_id, $item_id, $po_id, $qty_out, $qty_in, $status, $rss_type, $remarks, $id]);

        logActivity('RSS', $id, 'Updated RSS record', $_SESSION['user_id']);

        $_SESSION['success'] = 'RSS record updated successfully';

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid record ID');
        }

        $existing = $db->query("SELECT * FROM rss WHERE rss_id = $id")->fetch();
        if (!$existing) {
            throw new Exception('Record not found');
        }

        $stmt = $db->prepare("DELETE FROM rss WHERE rss_id = ?");
        $stmt->execute([$id]);

        logActivity('RSS', $id, 'Deleted RSS record', $_SESSION['user_id']);

        $_SESSION['success'] = 'RSS record deleted successfully';
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

redirect('list.php');
