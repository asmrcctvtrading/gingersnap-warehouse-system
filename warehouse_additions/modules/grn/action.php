<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Save GRN (create or update) ───────────────────────────────────────────

    case 'save_grn':

        $id     = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0;

        if ($isEdit) {
            requirePermission('grn', 'edit');
        } else {
            requirePermission('grn', 'create');
        }

        // ── Collect and validate header fields ────────────────────────────────
        $poId        = (int)($_POST['po_id']        ?? 0);
        $subconId    = (int)($_POST['subcon_id']    ?? 0) ?: null;
        $grnDate     = trim($_POST['grn_date']      ?? '');
        $invoiceNo   = trim($_POST['invoice_no']    ?? '') ?: null;
        $invoiceDate = trim($_POST['invoice_date']  ?? '') ?: null;
        $gateEntry   = trim($_POST['gate_entry']    ?? '') ?: null;
        $notes       = trim($_POST['notes']         ?? '') ?: null;
        $collectionName  = trim($_POST['collection_name']  ?? '') ?: null;
        $receivingPerson = trim($_POST['receiving_person'] ?? '') ?: null;
        $timeIn          = trim($_POST['time_in']          ?? '') ?: null;
        $timeStart       = trim($_POST['time_start']       ?? '') ?: null;
        $userId      = (int)($_SESSION['user_id']   ?? 0);

        if ($poId <= 0 || $grnDate === '') {
            setFlash('error', 'Purchase Order and GRN Date are required.');
            redirect($isEdit ? '/modules/grn/form.php?id=' . $id : '/modules/grn/form.php');
        }

        // Validate PO exists
        $stPo = $db->prepare("SELECT po_id, status FROM purchase_orders WHERE po_id = ?");
        $stPo->execute([$poId]);
        $po = $stPo->fetch();
        if (!$po) {
            setFlash('error', 'Selected Purchase Order does not exist.');
            redirect($isEdit ? '/modules/grn/form.php?id=' . $id : '/modules/grn/form.php');
        }

        // ── Collect and validate line items ───────────────────────────────────
        $rawItems = $_POST['items'] ?? [];
        $lineItems = [];

        foreach ($rawItems as $row) {
            $qtyReceived = max(0, (int)($row['qty_received'] ?? 0));
            $qtyRejected = max(0, (int)($row['qty_rejected'] ?? 0));

            // Skip completely empty rows (all fields blank and qty = 0)
            $skuCode  = trim($row['sku_code']  ?? '');
            $itemName = trim($row['item_name'] ?? '');
            if ($skuCode === '' && $itemName === '' && $qtyReceived === 0 && $qtyRejected === 0) {
                continue;
            }

            $lineItems[] = [
                'sku_code'         => $skuCode ?: null,
                'item_name'        => $itemName ?: null,
                'color'            => trim($row['color'] ?? '') ?: null,
                'size'             => trim($row['size']  ?? '') ?: null,
                'brand'            => trim($row['brand'] ?? '') ?: null,
                'qty_received'     => $qtyReceived,
                'qty_rejected'     => $qtyRejected,
                'rejection_reason' => $qtyRejected > 0 ? (trim($row['rejection_reason'] ?? '') ?: null) : null,
            ];
        }

        // Compute totals
        $totalReceived = 0;
        $totalRejected = 0;
        foreach ($lineItems as $li) {
            $totalReceived += $li['qty_received'];
            $totalRejected += $li['qty_rejected'];
        }

        // Over-receipt guard: check received qty against PO outstanding
        $discrepancyType = 'NONE';
        $shortageQty     = 0;

        try {
            $stPoQty = $db->prepare(
                "SELECT COALESCE(po.po_qty, 0) AS po_qty,
                        COALESCE(SUM(CASE WHEN g.grn_id != :skip THEN g.total_qty_received ELSE 0 END), 0) AS already_received
                 FROM purchase_orders po
                 LEFT JOIN grn g ON g.po_id = po.po_id AND g.status != 'DRAFT'
                 WHERE po.po_id = :po
                 GROUP BY po.po_id"
            );
            $stPoQty->execute(['po' => $poId, 'skip' => $isEdit ? $id : 0]);
            $poQtyRow = $stPoQty->fetch();

            if ($poQtyRow) {
                $poQty           = (int)$poQtyRow['po_qty'];
                $alreadyReceived = (int)$poQtyRow['already_received'];
                $outstanding     = $poQty - $alreadyReceived;

                if ($poQty > 0) {
                    if ($totalReceived > $outstanding && $outstanding >= 0) {
                        // Excess receipt
                        setFlash('error', 'Excess receipt detected: PO outstanding is ' . number_format($outstanding) . ' units but you received ' . number_format($totalReceived) . '. Please review before saving.');
                        redirect($isEdit ? '/modules/grn/form.php?id=' . $id : '/modules/grn/form.php');
                    } elseif ($totalReceived < $outstanding && $totalReceived > 0) {
                        // Shortage — allow save, flag it
                        $discrepancyType = 'SHORTAGE';
                        $shortageQty     = $outstanding - $totalReceived;
                    }
                }
            }
        } catch (Exception $e) {
            // total_qty column may not exist yet; skip over-receipt check silently
            $discrepancyType = 'NONE';
            $shortageQty     = 0;
        }

        try {
            $db->beginTransaction();

            if ($isEdit) {
                // Verify GRN exists and is DRAFT
                $stCheck = $db->prepare("SELECT grn_id, status FROM grn WHERE grn_id = ?");
                $stCheck->execute([$id]);
                $existing = $stCheck->fetch();
                if (!$existing) {
                    $db->rollBack();
                    setFlash('error', 'GRN not found.');
                    redirect('/modules/grn/list.php');
                }
                if ($existing['status'] !== 'DRAFT') {
                    $db->rollBack();
                    setFlash('error', 'Only DRAFT GRNs can be edited.');
                    redirect('/modules/grn/view.php?id=' . $id);
                }

                $stUpd = $db->prepare(
                    "UPDATE grn SET
                       po_id               = :po,
                       subcon_id           = :sc,
                       grn_date            = :gd,
                       invoice_no          = :inv,
                       invoice_date        = :invd,
                       gate_entry          = :ge,
                       total_qty_received  = :tqr,
                       total_qty_rejected  = :tqj,
                       notes               = :nt,
                       collection_name     = :cn,
                       receiving_person    = :rp,
                       time_in             = :ti,
                       time_start          = :ts,
                       discrepancy_type    = :dt,
                       shortage_qty        = :sq
                     WHERE grn_id = :id"
                );
                $stUpd->execute([
                    'po'   => $poId,
                    'sc'   => $subconId,
                    'gd'   => $grnDate,
                    'inv'  => $invoiceNo,
                    'invd' => $invoiceDate,
                    'ge'   => $gateEntry,
                    'tqr'  => $totalReceived,
                    'tqj'  => $totalRejected,
                    'nt'   => $notes,
                    'cn'   => $collectionName,
                    'rp'   => $receivingPerson,
                    'ti'   => $timeIn,
                    'ts'   => $timeStart,
                    'dt'   => $discrepancyType,
                    'sq'   => $shortageQty,
                    'id'   => $id,
                ]);

                // If GRN was DRAFT and now has received qty, advance to RECEIVED
                if ($existing['status'] === 'DRAFT' && $totalReceived > 0) {
                    $db->prepare("UPDATE grn SET status = 'RECEIVED' WHERE grn_id = ?")->execute([$id]);
                }

                $grnId = $id;

            } else {
                // Generate GRN code: GRN-YYYYMMDD-NNNN
                $stSeq  = $db->query("SELECT COALESCE(MAX(grn_id), 0) + 1 FROM grn");
                $nextId = (int)$stSeq->fetchColumn();
                $grnCode = 'GRN-' . date('Ymd') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                $ctrlNo  = 'CTRL-' . str_pad($nextId, 7, '0', STR_PAD_LEFT);

                $stIns = $db->prepare(
                    "INSERT INTO grn
                       (grn_code, grn_date, po_id, subcon_id,
                        invoice_no, invoice_date, gate_entry,
                        total_qty_received, total_qty_rejected,
                        notes, status, created_by,
                        collection_name, ctrl_no, receiving_person,
                        time_in, time_start, discrepancy_type, shortage_qty)
                     VALUES
                       (:gc, :gd, :po, :sc,
                        :inv, :invd, :ge,
                        :tqr, :tqj,
                        :nt, :st, :cb,
                        :cn, :ctrl, :rp,
                        :ti, :ts, :dt, :sq)"
                );
                $stIns->execute([
                    'gc'   => $grnCode,
                    'gd'   => $grnDate,
                    'po'   => $poId,
                    'sc'   => $subconId,
                    'inv'  => $invoiceNo,
                    'invd' => $invoiceDate,
                    'ge'   => $gateEntry,
                    'tqr'  => $totalReceived,
                    'tqj'  => $totalRejected,
                    'nt'   => $notes,
                    'st'   => ($totalReceived > 0 ? 'RECEIVED' : 'DRAFT'),
                    'cb'   => $userId,
                    'cn'   => $collectionName,
                    'ctrl' => $ctrlNo,
                    'rp'   => $receivingPerson,
                    'ti'   => $timeIn,
                    'ts'   => $timeStart,
                    'dt'   => $discrepancyType,
                    'sq'   => $shortageQty,
                ]);
                $grnId = (int)$db->lastInsertId();
            }

            // ── Replace line items ─────────────────────────────────────────────
            $db->prepare("DELETE FROM grn_items WHERE grn_id = ?")->execute([$grnId]);

            if ($lineItems) {
                $stItem = $db->prepare(
                    "INSERT INTO grn_items
                       (grn_id, sku_code, item_name, color, size, brand,
                        qty_received, qty_rejected, rejection_reason)
                     VALUES
                       (:gi, :sk, :nm, :co, :sz, :br,
                        :qr, :qj, :rr)"
                );
                foreach ($lineItems as $li) {
                    $stItem->execute([
                        'gi' => $grnId,
                        'sk' => $li['sku_code'],
                        'nm' => $li['item_name'],
                        'co' => $li['color'],
                        'sz' => $li['size'],
                        'br' => $li['brand'],
                        'qr' => $li['qty_received'],
                        'qj' => $li['qty_rejected'],
                        'rr' => $li['rejection_reason'],
                    ]);
                }
            }

            // ── Sync receiving record ──────────────────────────────────────────
            // One receiving row per GRN; update if exists, insert if not.
            $stRecv = $db->prepare(
                "SELECT receiving_id FROM receiving WHERE grn_id = ? LIMIT 1"
            );
            $stRecv->execute([$grnId]);
            $recvRow = $stRecv->fetch();

            if ($recvRow) {
                $db->prepare(
                    "UPDATE receiving SET
                       po_id         = :po,
                       received_qty  = :qty,
                       received_date = :dt,
                       received_by   = :rb
                     WHERE receiving_id = :rid"
                )->execute([
                    'po'  => $poId,
                    'qty' => $totalReceived,
                    'dt'  => $grnDate,
                    'rb'  => $userId,
                    'rid' => (int)$recvRow['receiving_id'],
                ]);
            } else {
                $db->prepare(
                    "INSERT INTO receiving
                       (po_id, grn_id, received_qty, received_date, received_by)
                     VALUES (:po, :gi, :qty, :dt, :rb)"
                )->execute([
                    'po'  => $poId,
                    'gi'  => $grnId,
                    'qty' => $totalReceived,
                    'dt'  => $grnDate,
                    'rb'  => $userId,
                ]);
            }

            // ── Auto-advance PO to WIP ─────────────────────────────────────────
            if ($po['status'] === 'NEW') {
                $db->prepare(
                    "UPDATE purchase_orders SET status = 'WIP' WHERE po_id = ?"
                )->execute([$poId]);
            }

            $db->commit();

            $label = $isEdit ? 'updated' : 'created';
            logActivity('CREATE', 'grn', $grnId, $isEdit ? ('update grn_id=' . $grnId) : $grnCode);
            setFlash('success', 'GRN ' . $label . '.');
            redirect('/modules/grn/view.php?id=' . $grnId);

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error saving GRN: ' . $e->getMessage());
            redirect($isEdit ? '/modules/grn/form.php?id=' . $id : '/modules/grn/form.php');
        }
        break;

    // ── Delete GRN ────────────────────────────────────────────────────────────

    case 'delete_grn':
        requirePermission('grn', 'edit');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid GRN.');
            redirect('/modules/grn/list.php');
        }

        $stCheck = $db->prepare("SELECT grn_code, status FROM grn WHERE grn_id = ?");
        $stCheck->execute([$id]);
        $grn = $stCheck->fetch();

        if (!$grn) {
            setFlash('error', 'GRN not found.');
            redirect('/modules/grn/list.php');
        }
        if ($grn['status'] !== 'DRAFT') {
            setFlash('error', 'Only DRAFT GRNs can be deleted.');
            redirect('/modules/grn/view.php?id=' . $id);
        }

        // Remove receiving records linked to this GRN first (FK is ON DELETE SET NULL
        // but we update to NULL manually for clarity, then delete the GRN)
        $db->prepare(
            "UPDATE receiving SET grn_id = NULL WHERE grn_id = ?"
        )->execute([$id]);

        // Delete GRN — cascades to grn_items
        $db->prepare("DELETE FROM grn WHERE grn_id = ?")->execute([$id]);

        logActivity('DELETE', 'grn', $id, $grn['grn_code']);
        setFlash('success', 'GRN ' . $grn['grn_code'] . ' deleted.');
        redirect('/modules/grn/list.php');
        break;

    default:
        redirect('/modules/grn/list.php');
}
