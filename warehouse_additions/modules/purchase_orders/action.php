<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create_po':
        requirePermission('purchase_orders', 'create');

        $poNumber = trim($_POST['po_number'] ?? '');
        $styleNo  = trim($_POST['style_no']  ?? '');
        $poQty    = (int)($_POST['po_qty']   ?? 0);

        if ($poNumber === '' || $styleNo === '' || $poQty < 1) {
            setFlash('error', 'PO Number, Style No, and Quantity (min 1) are required.');
            redirect('/modules/purchase_orders/form.php');
        }

        $coordinator_id = $_POST['coordinator_id'] ? (int)$_POST['coordinator_id'] : null;

        $stmt = $db->prepare(
            "INSERT INTO purchase_orders
             (po_number, style_no, subcon_id, collection_id, destination_id,
              facility, ticket_no, po_qty, po_date, delivery_date, status,
              supply_type, market_type, remarks, comments_shortfall, coordinator_id, created_by)
             VALUES
             (:pn, :sn, :sc, :col, :dst,
              :fac, :tkt, :qty, :pod, :del, 'NEW',
              :spt, :mkt, :rem, :cmt, :cid, :cb)"
        );
        $stmt->execute([
            'pn'  => $poNumber,
            'sn'  => $styleNo,
            'sc'  => (int)($_POST['subcon_id'] ?? 0)    ?: null,
            'col' => (int)($_POST['collection_id'] ?? 0) ?: null,
            'dst' => (int)($_POST['destination_id'] ?? 0) ?: null,
            'fac' => trim($_POST['facility'] ?? 'Q.C Warehouse') ?: 'Q.C Warehouse',
            'tkt' => trim($_POST['ticket_no'] ?? '') ?: null,
            'qty' => $poQty,
            'pod' => $_POST['po_date']       !== '' ? $_POST['po_date']       : null,
            'del' => $_POST['delivery_date'] !== '' ? $_POST['delivery_date'] : null,
            'spt' => in_array($_POST['supply_type'] ?? '', ['intrastate', 'interstate'], true)
                        ? $_POST['supply_type'] : 'intrastate',
            'mkt' => in_array($_POST['market_type'] ?? '', ['local', 'export'], true)
                        ? $_POST['market_type'] : 'local',
            'rem' => trim($_POST['remarks'] ?? 'GOODS') ?: 'GOODS',
            'cmt' => trim($_POST['comments_shortfall'] ?? '') ?: null,
            'cid' => $coordinator_id,
            'cb'  => $_SESSION['user_id'] ?? null,
        ]);
        $newId = (int)$db->lastInsertId();
        logActivity('CREATE', 'purchase_orders', $newId, $poNumber);
        setFlash('success', 'Purchase Order ' . $poNumber . ' created.');
        redirect('/modules/purchase_orders/view.php?id=' . $newId);
        break;

    case 'update_po':
        requirePermission('purchase_orders', 'edit');

        $id       = (int)($_POST['id'] ?? 0);
        $poNumber = trim($_POST['po_number'] ?? '');
        $styleNo  = trim($_POST['style_no']  ?? '');
        $poQty    = (int)($_POST['po_qty']   ?? 0);

        if ($id <= 0 || $poNumber === '' || $styleNo === '' || $poQty < 1) {
            setFlash('error', 'Invalid request.');
            redirect('/modules/purchase_orders/list.php');
        }

        $stCheck = $db->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
        $stCheck->execute([$id]);
        $current = $stCheck->fetch();
        if (!$current) {
            setFlash('error', 'PO not found.');
            redirect('/modules/purchase_orders/list.php');
        }
        if (in_array($current['status'], ['COMPLETED', 'CANCELLED'], true)) {
            setFlash('error', 'Cannot edit a closed PO.');
            redirect('/modules/purchase_orders/view.php?id=' . $id);
        }

        $newStatus = $_POST['status'] ?? $current['status'];
        if (!in_array($newStatus, ['NEW', 'WIP', 'COMPLETED', 'CANCELLED'], true)) {
            $newStatus = $current['status'];
        }

        $coordinator_id = $_POST['coordinator_id'] ? (int)$_POST['coordinator_id'] : null;

        $stmt = $db->prepare(
            "UPDATE purchase_orders SET
             po_number=:pn, style_no=:sn, subcon_id=:sc,
             collection_id=:col, destination_id=:dst,
             facility=:fac, ticket_no=:tkt, po_qty=:qty,
             po_date=:pod, delivery_date=:del, status=:st,
             supply_type=:spt, market_type=:mkt,
             remarks=:rem, comments_shortfall=:cmt,
             coordinator_id=:cid
             WHERE po_id=:id"
        );
        $stmt->execute([
            'pn'  => $poNumber,
            'sn'  => $styleNo,
            'sc'  => (int)($_POST['subcon_id'] ?? 0)     ?: null,
            'col' => (int)($_POST['collection_id'] ?? 0) ?: null,
            'dst' => (int)($_POST['destination_id'] ?? 0) ?: null,
            'fac' => trim($_POST['facility'] ?? 'Q.C Warehouse') ?: 'Q.C Warehouse',
            'tkt' => trim($_POST['ticket_no'] ?? '') ?: null,
            'qty' => $poQty,
            'pod' => $_POST['po_date']       !== '' ? $_POST['po_date']       : null,
            'del' => $_POST['delivery_date'] !== '' ? $_POST['delivery_date'] : null,
            'st'  => $newStatus,
            'spt' => in_array($_POST['supply_type'] ?? '', ['intrastate', 'interstate'], true)
                        ? $_POST['supply_type'] : 'intrastate',
            'mkt' => in_array($_POST['market_type'] ?? '', ['local', 'export'], true)
                        ? $_POST['market_type'] : 'local',
            'rem' => trim($_POST['remarks'] ?? 'GOODS') ?: 'GOODS',
            'cmt' => trim($_POST['comments_shortfall'] ?? '') ?: null,
            'cid' => $coordinator_id,
            'id'  => $id,
        ]);
        logActivity('UPDATE', 'purchase_orders', $id, $poNumber);
        setFlash('success', 'PO updated.');
        redirect('/modules/purchase_orders/view.php?id=' . $id);
        break;

    case 'delete_po':
        requirePermission('purchase_orders', 'delete');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid PO.');
            redirect('/modules/purchase_orders/list.php');
        }

        $stCheck = $db->prepare("SELECT po_number, status FROM purchase_orders WHERE po_id = ?");
        $stCheck->execute([$id]);
        $po = $stCheck->fetch();
        if (!$po) {
            setFlash('error', 'PO not found.');
            redirect('/modules/purchase_orders/list.php');
        }
        if ($po['status'] !== 'NEW') {
            setFlash('error', 'Only NEW POs can be deleted.');
            redirect('/modules/purchase_orders/view.php?id=' . $id);
        }

        $stMov = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE po_id = ?");
        $stMov->execute([$id]);
        if ((int)$stMov->fetchColumn() > 0) {
            setFlash('error', 'Cannot delete: this PO has stock movement records.');
            redirect('/modules/purchase_orders/view.php?id=' . $id);
        }

        $db->prepare("DELETE FROM purchase_orders WHERE po_id = ?")->execute([$id]);
        logActivity('DELETE', 'purchase_orders', $id, $po['po_number']);
        setFlash('success', 'PO ' . $po['po_number'] . ' deleted.');
        redirect('/modules/purchase_orders/list.php');
        break;

    case 'approve_po':
        requirePermission('purchase_orders', 'edit');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid PO.');
            redirect('/modules/purchase_orders/list.php');
        }

        $stmt = $db->prepare(
            "UPDATE purchase_orders
             SET approval_status='approved',
                 approved_by=:ab,
                 approved_at=NOW()
             WHERE po_id=:id"
        );
        $stmt->execute([
            'ab' => $_SESSION['user_id'] ?? null,
            'id' => $id,
        ]);
        logActivity('UPDATE', 'purchase_orders', $id, 'approved');
        setFlash('success', 'PO approved.');
        redirect('/modules/purchase_orders/view.php?id=' . $id);
        break;

    case 'receive_items':
        requirePermission('purchase_orders', 'receive');

        $poId      = (int)($_POST['po_id']          ?? 0);
        $itemId    = (int)($_POST['item_id']         ?? 0) ?: null;
        $locationId = (int)($_POST['to_location_id'] ?? 0) ?: null;
        $qty       = (int)($_POST['qty']             ?? 0);
        $notes     = trim($_POST['notes']            ?? '') ?: null;
        $userId    = $_SESSION['user_id'] ?? null;

        if ($poId <= 0 || $qty < 1) {
            setFlash('error', 'Quantity must be at least 1.');
            redirect('/modules/purchase_orders/receive.php?id=' . $poId);
        }
        if ($locationId === null) {
            setFlash('error', 'Location is required.');
            redirect('/modules/purchase_orders/receive.php?id=' . $poId);
        }

        $stPo = $db->prepare("SELECT status FROM purchase_orders WHERE po_id = ?");
        $stPo->execute([$poId]);
        $po = $stPo->fetch();
        if (!$po || !in_array($po['status'], ['NEW', 'WIP'], true)) {
            setFlash('error', 'PO is not open for receiving.');
            redirect('/modules/purchase_orders/list.php');
        }

        try {
            $db->beginTransaction();

            $stMov = $db->prepare(
                "INSERT INTO stock_movements
                 (movement_type, to_location_id, item_id, po_id, qty_cartons, qty_pieces, notes, created_by)
                 VALUES ('PUTAWAY', :tl, :ii, :pi, :qc, 0, :nt, :cb)"
            );
            $stMov->execute([
                'tl' => $locationId,
                'ii' => $itemId,
                'pi' => $poId,
                'qc' => $qty,
                'nt' => $notes,
                'cb' => $userId,
            ]);
            $movId = (int)$db->lastInsertId();

            adjustPosition($db, $locationId, $itemId, $poId, $qty, 0, $userId);

            if ($po['status'] === 'NEW') {
                $db->prepare(
                    "UPDATE purchase_orders SET status='WIP' WHERE po_id = ?"
                )->execute([$poId]);
            }

            $db->commit();
            logActivity('CREATE', 'stock_movements', $movId, 'PUTAWAY po_id=' . $poId);
            setFlash('success', 'Items received and stock updated.');

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error recording receipt: ' . $e->getMessage());
        }

        redirect('/modules/purchase_orders/receive.php?id=' . $poId);
        break;

    default:
        redirect('/modules/purchase_orders/list.php');
}

function findPosition(PDO $db, int $locationId, ?int $itemId, ?int $poId): ?array
{
    $stmt = $db->prepare(
        "SELECT position_id, qty_cartons, qty_pieces
         FROM stock_positions
         WHERE location_id = :li
           AND (item_id = :ii  OR (item_id IS NULL AND :ii2  IS NULL))
           AND (po_id   = :pi  OR (po_id   IS NULL AND :pi2  IS NULL))
         LIMIT 1"
    );
    $stmt->execute([
        'li'  => $locationId,
        'ii'  => $itemId,
        'ii2' => $itemId,
        'pi'  => $poId,
        'pi2' => $poId,
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adjustPosition(
    PDO $db,
    int $locationId,
    ?int $itemId,
    ?int $poId,
    int $deltaCartons,
    int $deltaPieces,
    ?int $userId
): void {
    $existing = findPosition($db, $locationId, $itemId, $poId);

    if ($existing) {
        $newCartons = $existing['qty_cartons'] + $deltaCartons;
        $newPieces  = $existing['qty_pieces']  + $deltaPieces;

        if ($newCartons <= 0 && $newPieces <= 0) {
            $db->prepare("DELETE FROM stock_positions WHERE position_id = ?")
               ->execute([$existing['position_id']]);
        } else {
            $db->prepare(
                "UPDATE stock_positions
                 SET qty_cartons = :qc, qty_pieces = :qp, updated_by = :ub
                 WHERE position_id = :id"
            )->execute([
                'qc' => max(0, $newCartons),
                'qp' => max(0, $newPieces),
                'ub' => $userId,
                'id' => $existing['position_id'],
            ]);
        }
    } else {
        if ($deltaCartons > 0 || $deltaPieces > 0) {
            $db->prepare(
                "INSERT INTO stock_positions
                 (location_id, item_id, po_id, qty_cartons, qty_pieces, updated_by)
                 VALUES (:li, :ii, :pi, :qc, :qp, :ub)"
            )->execute([
                'li' => $locationId,
                'ii' => $itemId,
                'pi' => $poId,
                'qc' => $deltaCartons,
                'qp' => $deltaPieces,
                'ub' => $userId,
            ]);
        }
    }
}
