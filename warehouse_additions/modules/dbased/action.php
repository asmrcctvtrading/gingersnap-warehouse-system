<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Save DBASED fields (coordinator, priorities, alias, remarks + allocation) ──

    case 'save_dbased':
        requirePermission('dbased', 'edit');

        $poId = (int)($_POST['po_id'] ?? 0);
        if ($poId <= 0) {
            setFlash('error', 'Invalid PO ID.');
            redirect('/modules/dbased/list.php');
        }

        // ── Verify the PO exists ──────────────────────────────────────────────
        $chk = $db->prepare("SELECT po_id, po_number, po_qty FROM purchase_orders WHERE po_id = ? LIMIT 1");
        $chk->execute([$poId]);
        $existingPo = $chk->fetch();
        if (!$existingPo) {
            setFlash('error', 'Purchase Order not found.');
            redirect('/modules/dbased/list.php');
        }

        // ── Sanitize inputs ───────────────────────────────────────────────────
        $coordinatorId      = (int)($_POST['coordinator_id'] ?? 0) ?: null;
        $receivingPriority  = trim($_POST['receiving_priority']  ?? '');
        $inspectionPriority = trim($_POST['inspection_priority'] ?? '');
        $poNumberAlias      = trim($_POST['po_number_alias']     ?? '');
        $remarks            = trim($_POST['remarks']             ?? '');

        // Convert empty strings to null for nullable INT columns
        $receivingPriority  = ($receivingPriority  !== '' && ctype_digit($receivingPriority))
                              ? (int)$receivingPriority  : null;
        $inspectionPriority = ($inspectionPriority !== '' && ctype_digit($inspectionPriority))
                              ? (int)$inspectionPriority : null;
        $poNumberAlias      = $poNumberAlias !== '' ? $poNumberAlias : null;
        $remarks            = $remarks       !== '' ? $remarks       : null;

        // ── Update purchase_orders DBASED columns ─────────────────────────────
        $upd = $db->prepare(
            "UPDATE purchase_orders
             SET coordinator_id      = ?,
                 receiving_priority  = ?,
                 inspection_priority = ?,
                 po_number_alias     = ?,
                 remarks             = ?
             WHERE po_id = ?"
        );
        $upd->execute([
            $coordinatorId,
            $receivingPriority,
            $inspectionPriority,
            $poNumberAlias,
            $remarks,
            $poId,
        ]);

        // ── Destination Allocation (UPSERT) ───────────────────────────────────
        // Only process if at least one alloc field was submitted (presence of qty_local key)
        $hasAllocData = isset($_POST['qty_local']);

        if ($hasAllocData) {
            $qtyLocal       = (int)($_POST['qty_local']        ?? 0);
            $qtySg          = (int)($_POST['qty_sg']           ?? 0);
            $qtyMalaysia    = (int)($_POST['qty_malaysia']     ?? 0);
            $qtyIndo        = (int)($_POST['qty_indo']         ?? 0);
            $qtyOtherExport = (int)($_POST['qty_other_export'] ?? 0);
            $qtySmBaliwag   = (int)($_POST['qty_sm_baliwag']   ?? 0);
            $qtyExportFull  = (int)($_POST['qty_export_full']  ?? 0);
            $allocNotes     = trim($_POST['alloc_notes']       ?? '') ?: null;

            // Ensure all quantities are non-negative
            $qtyLocal       = max(0, $qtyLocal);
            $qtySg          = max(0, $qtySg);
            $qtyMalaysia    = max(0, $qtyMalaysia);
            $qtyIndo        = max(0, $qtyIndo);
            $qtyOtherExport = max(0, $qtyOtherExport);
            $qtySmBaliwag   = max(0, $qtySmBaliwag);
            $qtyExportFull  = max(0, $qtyExportFull);

            // Check if an allocation row already exists for this PO
            $existsStmt = $db->prepare(
                "SELECT id FROM po_destination_allocations WHERE po_id = ? LIMIT 1"
            );
            $existsStmt->execute([$poId]);
            $existingAlloc = $existsStmt->fetch();

            if ($existingAlloc) {
                // UPDATE existing row
                $allocUpd = $db->prepare(
                    "UPDATE po_destination_allocations
                     SET qty_local        = ?,
                         qty_sg           = ?,
                         qty_malaysia     = ?,
                         qty_indo         = ?,
                         qty_other_export = ?,
                         qty_sm_baliwag   = ?,
                         qty_export_full  = ?,
                         notes            = ?
                     WHERE po_id = ?"
                );
                $allocUpd->execute([
                    $qtyLocal, $qtySg, $qtyMalaysia, $qtyIndo,
                    $qtyOtherExport, $qtySmBaliwag, $qtyExportFull,
                    $allocNotes, $poId,
                ]);
            } else {
                // INSERT new row
                $allocIns = $db->prepare(
                    "INSERT INTO po_destination_allocations
                         (po_id, qty_local, qty_sg, qty_malaysia, qty_indo,
                          qty_other_export, qty_sm_baliwag, qty_export_full, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $allocIns->execute([
                    $poId, $qtyLocal, $qtySg, $qtyMalaysia, $qtyIndo,
                    $qtyOtherExport, $qtySmBaliwag, $qtyExportFull, $allocNotes,
                ]);
            }

            logActivity('UPDATE', 'dbased', $poId, 'Updated DBASED coordinator/priorities/allocation for PO ' . $existingPo['po_number']);
        } else {
            logActivity('UPDATE', 'dbased', $poId, 'Updated DBASED coordinator/priorities for PO ' . $existingPo['po_number']);
        }

        setFlash('success', 'DBASED record updated for PO ' . $existingPo['po_number'] . '.');
        redirect('/modules/dbased/view.php?id=' . $poId);
        break;

    // ── Unknown action ─────────────────────────────────────────────────────────
    default:
        setFlash('error', 'Unknown action: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'));
        redirect('/modules/dbased/list.php');
}
