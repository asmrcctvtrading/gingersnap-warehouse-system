<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

$validStatuses = ['OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];

function parsePriority(): int {
    $v = (int)($_POST['priority_no'] ?? 999);
    return ($v >= 1 && $v <= 999) ? $v : 999;
}

function parseHandoff(): ?string {
    $v = trim($_POST['handoff_time'] ?? '');
    return $v !== '' ? $v : null;
}

function parsePolybag(): int {
    $v = (int)($_POST['polybag_qty'] ?? 0);
    return $v >= 0 ? $v : 0;
}

function parseStatus(array $valid): string {
    $v = $_POST['status'] ?? 'OPEN';
    return in_array($v, $valid, true) ? $v : 'OPEN';
}

if ($action === 'create') {
    requirePermission('tickets', 'create');

    $coordinator_id = $_POST['coordinator_id'] ? (int)$_POST['coordinator_id'] : null;

    $stmt = $db->prepare(
        "INSERT INTO tickets
             (ticket_no, po_id, ticket_date, qty, status, notes, created_by,
              priority_no, handoff_time, polybag_qty, merged_report_time, coordinator_id)
         VALUES
             (:tn, :po, :td, :qty, :st, :nt, :cb,
              :prio, :ho, :pb, '15:00:00', :cid)"
    );
    $stmt->execute([
        'tn'   => trim($_POST['ticket_no'] ?? ''),
        'po'   => $_POST['po_id'] ?: null,
        'td'   => $_POST['ticket_date'] ?: null,
        'qty'  => (int)($_POST['qty'] ?? 0),
        'st'   => parseStatus($validStatuses),
        'nt'   => trim($_POST['notes'] ?? '') ?: null,
        'cb'   => $_SESSION['user_id'] ?? null,
        'prio' => parsePriority(),
        'ho'   => parseHandoff(),
        'pb'   => parsePolybag(),
        'cid'  => $coordinator_id,
    ]);
    $newId = (int)$db->lastInsertId();
    logActivity('CREATE', 'tickets', $newId, trim($_POST['ticket_no'] ?? ''));
    setFlash('success', 'Ticket created.');
    redirect('/modules/tickets/view.php?id=' . $newId);

} elseif ($action === 'update') {
    requirePermission('tickets', 'edit');

    $coordinator_id = $_POST['coordinator_id'] ? (int)$_POST['coordinator_id'] : null;

    $stmt = $db->prepare(
        "UPDATE tickets
         SET ticket_no=:tn, po_id=:po, ticket_date=:td, qty=:qty, status=:st, notes=:nt,
             priority_no=:prio, handoff_time=:ho, polybag_qty=:pb, coordinator_id=:cid
         WHERE ticket_id=:id"
    );
    $stmt->execute([
        'tn'   => trim($_POST['ticket_no'] ?? ''),
        'po'   => $_POST['po_id'] ?: null,
        'td'   => $_POST['ticket_date'] ?: null,
        'qty'  => (int)($_POST['qty'] ?? 0),
        'st'   => parseStatus($validStatuses),
        'nt'   => trim($_POST['notes'] ?? '') ?: null,
        'prio' => parsePriority(),
        'ho'   => parseHandoff(),
        'pb'   => parsePolybag(),
        'cid'  => $coordinator_id,
        'id'   => $id,
    ]);
    logActivity('UPDATE', 'tickets', $id, trim($_POST['ticket_no'] ?? ''));
    setFlash('success', 'Ticket updated.');
    redirect('/modules/tickets/view.php?id=' . $id);

} elseif ($action === 'delete') {
    requirePermission('tickets', 'delete');

    $chk = $db->prepare("SELECT status FROM tickets WHERE ticket_id = ?");
    $chk->execute([$id]);
    $row = $chk->fetch();

    if (!$row || $row['status'] !== 'OPEN') {
        setFlash('error', 'Only OPEN tickets can be deleted.');
        redirect('/modules/tickets/list.php');
    }

    $db->prepare("DELETE FROM tickets WHERE ticket_id = ?")->execute([$id]);
    logActivity('DELETE', 'tickets', $id, null);
    setFlash('success', 'Ticket deleted.');
    redirect('/modules/tickets/list.php');

} else {
    redirect('/modules/tickets/list.php');
}
