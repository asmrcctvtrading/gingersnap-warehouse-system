<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if ($action === 'create') {
    requirePermission('output', 'create');

    $stmt = $db->prepare(
        "INSERT INTO output_records (po_id, output_date, completed_qty, packed_qty, notes, created_by)
         VALUES (:po, :od, :cq, :pq, :nt, :cb)"
    );
    $stmt->execute([
        'po' => (int)$_POST['po_id'],
        'od' => $_POST['output_date'],
        'cq' => (int)($_POST['completed_qty'] ?? 0),
        'pq' => (int)($_POST['packed_qty'] ?? 0),
        'nt' => trim($_POST['notes'] ?? '') ?: null,
        'cb' => $_SESSION['user_id'] ?? null,
    ]);
    logActivity('CREATE', 'output', (int)$db->lastInsertId(),
        'PO ' . (int)$_POST['po_id'] . ' — ' . $_POST['output_date']);
    setFlash('success', 'Output entry saved.');

} elseif ($action === 'update') {
    requirePermission('output', 'edit');

    $stmt = $db->prepare(
        "UPDATE output_records
         SET po_id=:po, output_date=:od, completed_qty=:cq, packed_qty=:pq, notes=:nt
         WHERE output_id=:id"
    );
    $stmt->execute([
        'po' => (int)$_POST['po_id'],
        'od' => $_POST['output_date'],
        'cq' => (int)($_POST['completed_qty'] ?? 0),
        'pq' => (int)($_POST['packed_qty'] ?? 0),
        'nt' => trim($_POST['notes'] ?? '') ?: null,
        'id' => $id,
    ]);
    logActivity('UPDATE', 'output', $id,
        'PO ' . (int)$_POST['po_id'] . ' — ' . $_POST['output_date']);
    setFlash('success', 'Output entry updated.');

} elseif ($action === 'delete') {
    requirePermission('output', 'delete');

    $db->prepare("DELETE FROM output_records WHERE output_id = ?")->execute([$id]);
    logActivity('DELETE', 'output', $id, null);
    setFlash('success', 'Output entry deleted.');
}

redirect('/modules/output/list.php');
