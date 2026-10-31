<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

define('GP_VALID_TYPES', [
    'OUTGOING', 'INCOMING', 'RETURN', 'TRANSFER',
    'STOCK_TRANSFER', 'INTERNAL_CONSUMPTION', 'RETURNABLE',
]);

switch ($action) {

    // ── Save Gate Pass (create or update header + items) ──────────────────────

    case 'save_gatepass':

        $id     = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0;

        if ($isEdit) {
            requirePermission('gatepass', 'edit');
        } else {
            requirePermission('gatepass', 'create');
        }

        // ── Collect and validate header ───────────────────────────────────────
        $gpType          = $_POST['gp_type']          ?? '';
        $toParty         = trim($_POST['to_party']         ?? '') ?: null;
        $purpose         = trim($_POST['purpose']          ?? '') ?: null;
        $reference       = trim($_POST['reference']        ?? '') ?: null;
        $rssReasonCode   = trim($_POST['rss_reason_code']  ?? '') ?: null;
        $vendorCode      = trim($_POST['vendor_code']      ?? '') ?: null;
        $returnableDate  = trim($_POST['returnable_date']  ?? '') ?: null;
        $poId            = (int)($_POST['po_id']           ?? 0) ?: null;
        $grnId           = (int)($_POST['grn_id']          ?? 0) ?: null;
        $userId          = (int)($_SESSION['user_id']      ?? 0);

        if (!in_array($gpType, GP_VALID_TYPES, true)) {
            setFlash('error', 'Invalid gate pass type.');
            redirect($isEdit ? '/modules/gatepass/form.php?id=' . $id : '/modules/gatepass/form.php');
        }

        // ── Collect line items ────────────────────────────────────────────────
        $rawItems  = $_POST['items'] ?? [];
        $lineItems = [];

        foreach ($rawItems as $row) {
            $itemId     = (int)($row['item_id']          ?? 0) ?: null;
            $locationId = (int)($row['from_location_id'] ?? 0) ?: null;
            $qty        = max(1, (int)($row['qty_cartons'] ?? 1));
            $notes      = trim($row['notes'] ?? '') ?: null;

            if ($itemId === null && $locationId === null) {
                continue;
            }

            $lineItems[] = [
                'item_id'          => $itemId,
                'from_location_id' => $locationId,
                'qty_cartons'      => $qty,
                'notes'            => $notes,
            ];
        }

        // ── RETURNABLE qty validation (pre-transaction) ───────────────────────
        $partialWarning = null;
        if ($gpType === 'RETURNABLE' && $grnId > 0) {
            $totalLineQty = 0;
            foreach ($lineItems as $li) {
                $totalLineQty += $li['qty_cartons'];
            }

            $stGrn = $db->prepare("SELECT total_qty_received FROM grn WHERE grn_id = ?");
            $stGrn->execute([$grnId]);
            $grnRow = $stGrn->fetch();

            if ($grnRow) {
                $grnQty = (int)$grnRow['total_qty_received'];
                if ($totalLineQty > $grnQty) {
                    setFlash('error', 'Excess return detected: cannot return more units than were received.');
                    redirect($isEdit ? '/modules/gatepass/form.php?id=' . $id : '/modules/gatepass/form.php');
                } elseif ($totalLineQty < $grnQty) {
                    $partialWarning = 'Partial return: ' . $totalLineQty . ' of ' . $grnQty . ' units.';
                }
            }
        }

        try {
            $db->beginTransaction();

            if ($isEdit) {
                $stCheck = $db->prepare(
                    "SELECT gp_code, gp_status FROM gatepasses WHERE gp_id = ?"
                );
                $stCheck->execute([$id]);
                $existing = $stCheck->fetch();

                if (!$existing) {
                    $db->rollBack();
                    setFlash('error', 'Gate pass not found.');
                    redirect('/modules/gatepass/list.php');
                }
                if (!in_array($existing['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
                    $db->rollBack();
                    setFlash('error', 'Only OPEN or PENDING gate passes can be edited.');
                    redirect('/modules/gatepass/view.php?id=' . $id);
                }

                $db->prepare(
                    "UPDATE gatepasses
                     SET gp_type          = :gt,
                         to_party         = :tp,
                         purpose          = :pu,
                         reference        = :rf,
                         rss_reason_code  = :rc,
                         vendor_code      = :vc,
                         returnable_date  = :rd,
                         po_id            = :pi,
                         grn_id           = :gi
                     WHERE gp_id = :id"
                )->execute([
                    'gt' => $gpType,
                    'tp' => $toParty,
                    'pu' => $purpose,
                    'rf' => $reference,
                    'rc' => $rssReasonCode,
                    'vc' => $vendorCode,
                    'rd' => $returnableDate,
                    'pi' => $poId,
                    'gi' => $grnId,
                    'id' => $id,
                ]);

                $gpId   = $id;
                $gpCode = $existing['gp_code'];

            } else {
                $stSeq  = $db->query(
                    "SELECT COALESCE(MAX(gp_id), 0) + 1 FROM gatepasses"
                );
                $nextId = (int)$stSeq->fetchColumn();
                $gpCode = 'GP-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

                $db->prepare(
                    "INSERT INTO gatepasses
                       (gp_code, gp_type, gp_status, to_party, purpose, reference,
                        rss_reason_code, vendor_code, returnable_date, po_id, grn_id, created_by)
                     VALUES
                       (:gc, :gt, 'PENDING', :tp, :pu, :rf, :rc, :vc, :rd, :pi, :gi, :cb)"
                )->execute([
                    'gc' => $gpCode,
                    'gt' => $gpType,
                    'tp' => $toParty,
                    'pu' => $purpose,
                    'rf' => $reference,
                    'rc' => $rssReasonCode,
                    'vc' => $vendorCode,
                    'rd' => $returnableDate,
                    'pi' => $poId,
                    'gi' => $grnId,
                    'cb' => $userId,
                ]);
                $gpId = (int)$db->lastInsertId();
            }

            // ── Replace line items (delete-then-insert) ───────────────────────
            $db->prepare(
                "DELETE FROM gatepass_items WHERE gp_id = ?"
            )->execute([$gpId]);

            if ($lineItems) {
                $stItem = $db->prepare(
                    "INSERT INTO gatepass_items
                       (gp_id, item_id, from_location_id, qty_cartons, notes)
                     VALUES (:gi, :ii, :fl, :qc, :nt)"
                );
                foreach ($lineItems as $li) {
                    $stItem->execute([
                        'gi' => $gpId,
                        'ii' => $li['item_id'],
                        'fl' => $li['from_location_id'],
                        'qc' => $li['qty_cartons'],
                        'nt' => $li['notes'],
                    ]);
                }
            }

            $db->commit();

            $label = $isEdit ? 'updated' : 'created';
            logActivity($isEdit ? 'UPDATE' : 'CREATE', 'gatepasses', $gpId, $gpCode);

            if ($partialWarning) {
                setFlash('warning', $partialWarning);
            } else {
                setFlash('success', 'Gate pass ' . $label . '.');
            }
            redirect('/modules/gatepass/view.php?id=' . $gpId);

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error saving gate pass: ' . $e->getMessage());
            redirect($isEdit ? '/modules/gatepass/form.php?id=' . $id : '/modules/gatepass/form.php');
        }
        break;

    // ── Confirm Gate Pass (PENDING → OPEN) ───────────────────────────────────

    case 'confirm_gatepass':
        requirePermission('gatepass', 'close');

        $id     = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid gate pass.');
            redirect('/modules/gatepass/list.php');
        }

        $stGp = $db->prepare("SELECT * FROM gatepasses WHERE gp_id = ?");
        $stGp->execute([$id]);
        $gp = $stGp->fetch();

        if (!$gp) {
            setFlash('error', 'Gate pass not found.');
            redirect('/modules/gatepass/list.php');
        }
        if ($gp['gp_status'] !== 'PENDING') {
            setFlash('error', 'Only PENDING gate passes can be confirmed.');
            redirect('/modules/gatepass/view.php?id=' . $id);
        }

        try {
            $db->beginTransaction();

            $db->prepare(
                "UPDATE gatepasses
                 SET gp_status = 'OPEN', confirmed_at = NOW(), confirmed_by = ?
                 WHERE gp_id = ? AND gp_status = 'PENDING'"
            )->execute([$userId, $id]);

            // ── Sync to dispatching_priority ──────────────────────────────────
            $poId = $gp['po_id'] ?? null;

            // Sum qty and collect item-level fields from gatepass_items in one query
            $stItems = $db->prepare(
                "SELECT COALESCE(SUM(qty_cartons), 0) AS total_qty,
                        MAX(ticket_number)             AS ticket_number,
                        MAX(subcon_name)               AS subcon_name,
                        MAX(collection_name)           AS collection_name,
                        MAX(local_export)              AS local_export
                 FROM gatepass_items
                 WHERE gp_id = ?"
            );
            $stItems->execute([$id]);
            $itemsSummary = $stItems->fetch();
            $totalQty     = (int)($itemsSummary['total_qty'] ?? 0);

            // Next priority rank among NEW rows
            $rankRow = (int)$db->query(
                "SELECT COALESCE(MAX(priority_rank), 0) + 1 FROM dispatching_priority WHERE status = 'NEW'"
            )->fetchColumn();

            // Destination allocation by PO (optional — falls back to 0 per field)
            $allocRow = [];
            if ($poId) {
                $stAlloc = $db->prepare(
                    "SELECT * FROM po_destination_allocations WHERE po_id = ?"
                );
                $stAlloc->execute([$poId]);
                $allocRow = $stAlloc->fetch() ?: [];
            }

            // Only insert if no record already exists for this ctrl_number (idempotent)
            $existsCheck = $db->prepare(
                "SELECT id FROM dispatching_priority WHERE ctrl_number = ? LIMIT 1"
            );
            $existsCheck->execute([$gp['gp_code']]);
            if (!$existsCheck->fetch()) {
                $stDp = $db->prepare(
                    "INSERT INTO dispatching_priority
                       (ctrl_number, gatepass_created, gatepass_qty, status,
                        ticket_number, subcon_name, collection_name, local_export,
                        qty_local, qty_sg, qty_malaysia, qty_indo, qty_sm_baliwag,
                        qty_export_full, priority_rank, created_by)
                     VALUES
                       (:ctrl, 'DONE', :qty, 'NEW',
                        :tkt, :sub, :col, :le,
                        :local, :sg, :my, :indo, :smb,
                        :exp, :rank, :cb)"
                );
                $stDp->execute([
                    'ctrl'  => $gp['gp_code'],
                    'qty'   => $totalQty,
                    'tkt'   => $itemsSummary['ticket_number']   ?? ($gp['ticket_no'] ?? $gp['reference'] ?? null),
                    'sub'   => $itemsSummary['subcon_name']     ?? null,
                    'col'   => $itemsSummary['collection_name'] ?? null,
                    'le'    => $itemsSummary['local_export']    ?? null,
                    'local' => $allocRow['qty_local']           ?? 0,
                    'sg'    => $allocRow['qty_sg']              ?? 0,
                    'my'    => $allocRow['qty_malaysia']        ?? 0,
                    'indo'  => $allocRow['qty_indo']            ?? 0,
                    'smb'   => $allocRow['qty_sm_baliwag']      ?? 0,
                    'exp'   => $allocRow['qty_export_full']     ?? 0,
                    'rank'  => $rankRow,
                    'cb'    => $userId,
                ]);
            }
            // ── End dispatching_priority sync ─────────────────────────────────

            $db->commit();

            logActivity('UPDATE', 'gatepasses', $id, 'CONFIRMED ' . $gp['gp_code']);
            setFlash('success', 'Gate pass confirmed and moved to OPEN.');

        } catch (Throwable $e) {
            $db->rollBack();
            error_log('confirm_gatepass failed for gp_id=' . $id . ': ' . $e->getMessage());
            setFlash('error', 'Error confirming gate pass: ' . $e->getMessage());
        }

        redirect('/modules/gatepass/view.php?id=' . $id);
        break;

    // ── Close Gate Pass ───────────────────────────────────────────────────────

    case 'close_gatepass':
        requirePermission('gatepass', 'close');

        $id     = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid gate pass.');
            redirect('/modules/gatepass/list.php');
        }

        $stGp = $db->prepare("SELECT * FROM gatepasses WHERE gp_id = ?");
        $stGp->execute([$id]);
        $gp = $stGp->fetch();

        if (!$gp) {
            setFlash('error', 'Gate pass not found.');
            redirect('/modules/gatepass/list.php');
        }
        if (!in_array($gp['gp_status'], ['OPEN', 'CREATED'], true)) {
            setFlash('error', 'Gate pass is already ' . strtolower($gp['gp_status']) . '. Confirm it first if PENDING.');
            redirect('/modules/gatepass/view.php?id=' . $id);
        }

        $stItems = $db->prepare("SELECT * FROM gatepass_items WHERE gp_id = ?");
        $stItems->execute([$id]);
        $gpItems = $stItems->fetchAll();

        if (empty($gpItems)) {
            setFlash('error', 'Cannot close a gate pass with no items.');
            redirect('/modules/gatepass/view.php?id=' . $id);
        }

        try {
            $db->beginTransaction();

            $stMov = $db->prepare(
                "INSERT INTO stock_movements
                   (movement_type, from_location_id, item_id, qty_cartons, qty_pieces,
                    reference_doc, notes, created_by)
                 VALUES ('DISPATCH', :fl, :ii, :qc, 0, :rd, :nt, :cb)"
            );

            foreach ($gpItems as $item) {
                $locationId = (int)($item['from_location_id'] ?? 0);
                $itemId     = (int)($item['item_id'] ?? 0);
                $qty        = (int)$item['qty_cartons'];

                $stMov->execute([
                    'fl' => $locationId ?: null,
                    'ii' => $itemId     ?: null,
                    'qc' => $qty,
                    'rd' => $gp['gp_code'],
                    'nt' => $item['notes'],
                    'cb' => $userId,
                ]);

                if ($locationId > 0 && $itemId > 0) {
                    gpAdjustPosition($db, $locationId, $itemId, -$qty, $userId);
                }
            }

            $db->prepare(
                "UPDATE gatepasses
                 SET gp_status = 'CLOSED', closed_at = NOW()
                 WHERE gp_id = ? AND gp_status IN ('OPEN','CREATED')"
            )->execute([$id]);

            $db->commit();

            logActivity('UPDATE', 'gatepasses', $id, 'CLOSED ' . $gp['gp_code']);
            setFlash('success', 'Gate pass ' . h($gp['gp_code']) . ' closed. Stock updated.');

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error closing gate pass: ' . $e->getMessage());
        }

        redirect('/modules/gatepass/view.php?id=' . $id);
        break;

    // ── Cancel Gate Pass ──────────────────────────────────────────────────────

    case 'cancel_gatepass':
        requirePermission('gatepass', 'close');

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid gate pass.');
            redirect('/modules/gatepass/list.php');
        }

        $stGp = $db->prepare(
            "SELECT gp_code, gp_status FROM gatepasses WHERE gp_id = ?"
        );
        $stGp->execute([$id]);
        $gp = $stGp->fetch();

        if (!$gp) {
            setFlash('error', 'Gate pass not found.');
            redirect('/modules/gatepass/list.php');
        }
        if (!in_array($gp['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
            setFlash('error', 'Only OPEN or PENDING gate passes can be cancelled.');
            redirect('/modules/gatepass/view.php?id=' . $id);
        }

        $db->prepare(
            "UPDATE gatepasses
             SET gp_status = 'CANCELLED'
             WHERE gp_id = ? AND gp_status IN ('OPEN','CREATED','PENDING')"
        )->execute([$id]);

        logActivity('UPDATE', 'gatepasses', $id, 'CANCELLED ' . $gp['gp_code']);
        setFlash('success', 'Gate pass ' . h($gp['gp_code']) . ' cancelled.');
        redirect('/modules/gatepass/view.php?id=' . $id);
        break;

    // ── Delete Gate Pass ──────────────────────────────────────────────────────

    case 'delete_gatepass':
        requirePermission('gatepass', 'edit');

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid gate pass.');
            redirect('/modules/gatepass/list.php');
        }

        $stGp = $db->prepare(
            "SELECT gp_code, gp_status FROM gatepasses WHERE gp_id = ?"
        );
        $stGp->execute([$id]);
        $gp = $stGp->fetch();

        if (!$gp) {
            setFlash('error', 'Gate pass not found.');
            redirect('/modules/gatepass/list.php');
        }
        if (!in_array($gp['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
            setFlash('error', 'Only OPEN or PENDING gate passes can be deleted.');
            redirect('/modules/gatepass/view.php?id=' . $id);
        }

        $db->prepare("DELETE FROM gatepasses WHERE gp_id = ?")->execute([$id]);

        logActivity('DELETE', 'gatepasses', $id, $gp['gp_code']);
        setFlash('success', 'Gate pass ' . h($gp['gp_code']) . ' deleted.');
        redirect('/modules/gatepass/list.php');
        break;

    // ── Add Single Item (from view.php quick-add form) ────────────────────────

    case 'add_item':
        requirePermission('gatepass', 'edit');

        $gpId       = (int)($_POST['gp_id']            ?? 0);
        $itemId     = (int)($_POST['item_id']           ?? 0) ?: null;
        $locationId = (int)($_POST['from_location_id']  ?? 0) ?: null;
        $qty        = max(1, (int)($_POST['qty_cartons'] ?? 1));
        $notes      = trim($_POST['notes']              ?? '') ?: null;

        if ($gpId <= 0 || $itemId === null || $locationId === null) {
            setFlash('error', 'Item and location are required.');
            redirect('/modules/gatepass/view.php?id=' . $gpId);
        }

        $stGp = $db->prepare(
            "SELECT gp_status FROM gatepasses WHERE gp_id = ?"
        );
        $stGp->execute([$gpId]);
        $gpRow = $stGp->fetch();

        if (!$gpRow || !in_array($gpRow['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
            setFlash('error', 'Gate pass is not open for editing.');
            redirect('/modules/gatepass/list.php');
        }

        $db->prepare(
            "INSERT INTO gatepass_items
               (gp_id, item_id, from_location_id, qty_cartons, notes)
             VALUES (:gp, :ii, :fl, :qc, :nt)"
        )->execute([
            'gp' => $gpId,
            'ii' => $itemId,
            'fl' => $locationId,
            'qc' => $qty,
            'nt' => $notes,
        ]);

        logActivity('CREATE', 'gatepass_items', (int)$db->lastInsertId(), 'gp_id=' . $gpId);
        setFlash('success', 'Item added.');
        redirect('/modules/gatepass/view.php?id=' . $gpId);
        break;

    // ── Remove Single Item ────────────────────────────────────────────────────

    case 'remove_item':
        requirePermission('gatepass', 'edit');

        $gpiId = (int)($_POST['gpi_id'] ?? 0);
        $gpId  = (int)($_POST['gp_id']  ?? 0);

        if ($gpiId <= 0 || $gpId <= 0) {
            setFlash('error', 'Invalid item reference.');
            redirect('/modules/gatepass/list.php');
        }

        $stGp = $db->prepare(
            "SELECT gp_status FROM gatepasses WHERE gp_id = ?"
        );
        $stGp->execute([$gpId]);
        $gpRow = $stGp->fetch();

        if (!$gpRow || !in_array($gpRow['gp_status'], ['OPEN', 'CREATED', 'PENDING'], true)) {
            setFlash('error', 'Cannot modify items on a closed gate pass.');
            redirect('/modules/gatepass/view.php?id=' . $gpId);
        }

        $db->prepare(
            "DELETE FROM gatepass_items WHERE gpi_id = ? AND gp_id = ?"
        )->execute([$gpiId, $gpId]);

        logActivity('DELETE', 'gatepass_items', $gpiId, 'gp_id=' . $gpId);
        setFlash('success', 'Item removed.');
        redirect('/modules/gatepass/view.php?id=' . $gpId);
        break;

    default:
        redirect('/modules/gatepass/list.php');
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function gpFindPosition(PDO $db, int $locationId, int $itemId): ?array
{
    $stmt = $db->prepare(
        "SELECT position_id, qty_cartons, qty_pieces
         FROM stock_positions
         WHERE location_id = ? AND item_id = ?
         LIMIT 1"
    );
    $stmt->execute([$locationId, $itemId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function gpAdjustPosition(PDO $db, int $locationId, int $itemId, int $deltaCartons, ?int $userId): void
{
    $existing = gpFindPosition($db, $locationId, $itemId);

    if (!$existing) {
        return;
    }

    $newCartons = (int)$existing['qty_cartons'] + $deltaCartons;
    $newPieces  = (int)$existing['qty_pieces'];

    if ($newCartons <= 0 && $newPieces <= 0) {
        $db->prepare(
            "DELETE FROM stock_positions WHERE position_id = ?"
        )->execute([$existing['position_id']]);
    } else {
        $db->prepare(
            "UPDATE stock_positions
             SET qty_cartons = :qc, updated_by = :ub
             WHERE position_id = :id"
        )->execute([
            'qc' => max(0, $newCartons),
            'ub' => $userId,
            'id' => $existing['position_id'],
        ]);
    }
}
