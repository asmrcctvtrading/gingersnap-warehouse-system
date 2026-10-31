<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

function saveFobItems(PDO $db, int $fobId): void
{
    $poIds    = $_POST['item_po_id']    ?? [];
    $qtys     = $_POST['item_qty']      ?? [];
    $cartons  = $_POST['item_cartons']  ?? [];
    $notes    = $_POST['item_notes']    ?? [];

    $db->prepare("DELETE FROM fob_shipment_items WHERE fob_id = ?")->execute([$fobId]);

    $stmt = $db->prepare(
        "INSERT INTO fob_shipment_items (fob_id, po_id, qty_shipped, cartons, notes)
         VALUES (:fob, :po, :qty, :cart, :nt)"
    );
    foreach ($poIds as $i => $poId) {
        if (!$poId && !($qtys[$i] ?? 0)) { continue; } // skip fully blank rows
        $stmt->execute([
            'fob'  => $fobId,
            'po'   => $poId ?: null,
            'qty'  => (int)($qtys[$i]    ?? 0),
            'cart' => (int)($cartons[$i] ?? 0),
            'nt'   => trim($notes[$i] ?? '') ?: null,
        ]);
    }
}

if ($action === 'create') {
    requirePermission('fob_shipment', 'create');

    $stmt = $db->prepare(
        "INSERT INTO fob_shipments
            (booking_ref, vessel_flight, etd, eta, destination_id, buyer, status, remarks, created_by)
         VALUES (:br, :vf, :etd, :eta, :dest, :buyer, :st, :rem, :cb)"
    );
    $stmt->execute([
        'br'   => trim($_POST['booking_ref'] ?? ''),
        'vf'   => trim($_POST['vessel_flight'] ?? '') ?: null,
        'etd'  => $_POST['etd'] ?: null,
        'eta'  => $_POST['eta'] ?: null,
        'dest' => $_POST['destination_id'] ?: null,
        'buyer'=> trim($_POST['buyer'] ?? '') ?: null,
        'st'   => $_POST['status'] ?? 'BOOKING',
        'rem'  => trim($_POST['remarks'] ?? '') ?: null,
        'cb'   => $_SESSION['user_id'] ?? null,
    ]);
    $newId = (int)$db->lastInsertId();
    saveFobItems($db, $newId);
    logActivity('CREATE', 'fob_shipment', $newId, trim($_POST['booking_ref'] ?? ''));
    setFlash('success', 'FOB shipment created.');

} elseif ($action === 'update') {
    requirePermission('fob_shipment', 'edit');

    $stmt = $db->prepare(
        "UPDATE fob_shipments
         SET booking_ref=:br, vessel_flight=:vf, etd=:etd, eta=:eta,
             destination_id=:dest, buyer=:buyer, status=:st, remarks=:rem
         WHERE fob_id=:id"
    );
    $stmt->execute([
        'br'   => trim($_POST['booking_ref'] ?? ''),
        'vf'   => trim($_POST['vessel_flight'] ?? '') ?: null,
        'etd'  => $_POST['etd'] ?: null,
        'eta'  => $_POST['eta'] ?: null,
        'dest' => $_POST['destination_id'] ?: null,
        'buyer'=> trim($_POST['buyer'] ?? '') ?: null,
        'st'   => $_POST['status'] ?? 'BOOKING',
        'rem'  => trim($_POST['remarks'] ?? '') ?: null,
        'id'   => $id,
    ]);
    saveFobItems($db, $id);
    logActivity('UPDATE', 'fob_shipment', $id, trim($_POST['booking_ref'] ?? ''));
    setFlash('success', 'FOB shipment updated.');

} elseif ($action === 'delete') {
    requirePermission('fob_shipment', 'delete');

    $db->prepare("DELETE FROM fob_shipments WHERE fob_id = ?")->execute([$id]);
    logActivity('DELETE', 'fob_shipment', $id, null);
    setFlash('success', 'FOB shipment deleted.');
}

redirect('/modules/fob/list.php');
