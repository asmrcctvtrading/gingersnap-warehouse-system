<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'move' || $action === 'save_movement') {
    requirePermission('stock', 'create');

    $movType    = $_POST['movement_type']    ?? '';
    $fromLocId  = (int)($_POST['from_location_id'] ?? 0) ?: null;
    $toLocId    = (int)($_POST['to_location_id']   ?? 0) ?: null;
    $itemId     = (int)($_POST['item_id']           ?? 0) ?: null;
    $poId       = (int)($_POST['po_id']             ?? 0) ?: null;
    $qtyCartons = (int)($_POST['qty_cartons']       ?? 0);
    $qtyPieces  = (int)($_POST['qty_pieces']        ?? 0);
    $movDate    = trim($_POST['movement_date']       ?? '');
    $refDoc     = trim($_POST['reference_doc']      ?? '')
                  ?: ($movDate !== '' ? 'Date: ' . $movDate : null);
    $notes      = trim($_POST['notes']              ?? '') ?: null;
    $userId     = $_SESSION['user_id'] ?? null;

    // Determine the back-URL depending on which form was used
    $backUrl = $action === 'save_movement' ? '/modules/stock/form.php' : '/modules/stock/move.php';

    $validTypes = ['PUTAWAY', 'RELOCATION', 'PICK', 'ADJUST', 'DISPATCH'];
    if (!in_array($movType, $validTypes, true)) {
        setFlash('error', 'Invalid movement type.');
        redirect($backUrl);
    }

    // item_id is required when posting from move.php (full form),
    // but optional when posting from form.php (GRN-level putaway)
    if ($action === 'move' && $itemId === null) {
        setFlash('error', 'Item is required.');
        redirect($backUrl);
    }

    try {
        $db->beginTransaction();

        // 1. Insert movement record (always — full audit trail)
        $stmt = $db->prepare(
            "INSERT INTO stock_movements
             (movement_type, from_location_id, to_location_id, item_id, po_id,
              qty_cartons, qty_pieces, reference_doc, notes, created_by)
             VALUES (:mt, :fl, :tl, :ii, :pi, :qc, :qp, :rd, :nt, :cb)"
        );
        $stmt->execute([
            'mt' => $movType,
            'fl' => $fromLocId,
            'tl' => $toLocId,
            'ii' => $itemId,
            'pi' => $poId,
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'rd' => $refDoc,
            'nt' => $notes,
            'cb' => $userId,
        ]);
        $movId = (int)$db->lastInsertId();

        // 2. Update stock_positions snapshot
        if ($movType === 'ADJUST') {
            // ADJUST: set exact qty at from_location for item+po
            if ($fromLocId !== null) {
                setPositionQty($db, $fromLocId, $itemId, $poId, $qtyCartons, $qtyPieces, $notes, $userId);
            }
        } else {
            // Decrement from source
            if ($fromLocId !== null) {
                adjustPosition($db, $fromLocId, $itemId, $poId, -$qtyCartons, -$qtyPieces, $userId);
            }
            // Increment destination
            if ($toLocId !== null) {
                adjustPosition($db, $toLocId, $itemId, $poId, $qtyCartons, $qtyPieces, $userId);
            }
        }

        $db->commit();

        logActivity('CREATE', 'stock_movements', $movId, $movType . ($refDoc ? ' ' . $refDoc : ''));
        setFlash('success', 'Movement recorded successfully.');

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Error recording movement: ' . $e->getMessage());
    }

    redirect('/modules/stock/list.php');

} elseif ($action === 'delete_movement') {
    requirePermission('stock', 'delete');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('error', 'Invalid movement ID.');
        redirect('/modules/stock/ledger.php');
    }
    $db->prepare("DELETE FROM stock_movements WHERE movement_id = ?")->execute([$id]);
    logActivity('DELETE', 'stock_movements', $id, null);
    setFlash('success', 'Movement record deleted. Note: stock positions were NOT reversed.');
    redirect('/modules/stock/ledger.php');

} else {
    redirect('/modules/stock/list.php');
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Find an existing stock_positions row for a given location+item+po combination.
 * Uses IS NULL checks to handle nullable item_id / po_id correctly,
 * because MySQL treats NULLs as distinct in composite unique indexes.
 */
function findPosition(PDO $db, int $locationId, ?int $itemId, ?int $poId): ?array
{
    $stmt = $db->prepare(
        "SELECT position_id, qty_cartons, qty_pieces
         FROM stock_positions
         WHERE location_id = :li
           AND (item_id = :ii OR (item_id IS NULL AND :ii2 IS NULL))
           AND (po_id   = :pi OR (po_id   IS NULL AND :pi2 IS NULL))
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

/**
 * Increment or decrement qty at a location (pass negative delta to decrement).
 * Deletes the row when both qtys reach zero or below.
 */
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
        // Only insert if delta is positive (no point in negative phantom row)
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

/**
 * ADJUST: set the exact qty for a location+item+po combination.
 * Inserts a new row if none exists; deletes if both qtys are 0.
 */
function setPositionQty(
    PDO $db,
    int $locationId,
    ?int $itemId,
    ?int $poId,
    int $qtyCartons,
    int $qtyPieces,
    ?string $notes,
    ?int $userId
): void {
    $existing = findPosition($db, $locationId, $itemId, $poId);

    if ($qtyCartons <= 0 && $qtyPieces <= 0) {
        // Zeroed out — remove the row
        if ($existing) {
            $db->prepare("DELETE FROM stock_positions WHERE position_id = ?")
               ->execute([$existing['position_id']]);
        }
        return;
    }

    if ($existing) {
        $db->prepare(
            "UPDATE stock_positions
             SET qty_cartons = :qc, qty_pieces = :qp, notes = :nt, updated_by = :ub
             WHERE position_id = :id"
        )->execute([
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'nt' => $notes,
            'ub' => $userId,
            'id' => $existing['position_id'],
        ]);
    } else {
        $db->prepare(
            "INSERT INTO stock_positions
             (location_id, item_id, po_id, qty_cartons, qty_pieces, notes, updated_by)
             VALUES (:li, :ii, :pi, :qc, :qp, :nt, :ub)"
        )->execute([
            'li' => $locationId,
            'ii' => $itemId,
            'pi' => $poId,
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'nt' => $notes,
            'ub' => $userId,
        ]);
    }
}
