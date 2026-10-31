<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── Save QC Inspection ────────────────────────────────────────────────────

    case 'save_inspection':

        requirePermission('qc', 'inspect');

        $grnId               = (int)($_POST['grn_id']               ?? 0);
        $inspectionDate      = trim($_POST['inspection_date']        ?? '');
        $ticketId            = (int)($_POST['ticket_id']             ?? 0) ?: null;
        $polybagQtyAllocated = max(0, (int)($_POST['polybag_qty_allocated'] ?? 0));
        $userId              = (int)($_SESSION['user_id']            ?? 0);
        $rawItems            = $_POST['items'] ?? [];

        if ($grnId <= 0) {
            setFlash('error', 'Invalid GRN.');
            redirect('/modules/qc/list.php');
        }
        if ($inspectionDate === '') {
            setFlash('error', 'Inspection date is required.');
            redirect('/modules/qc/form.php?grn_id=' . $grnId);
        }
        if (empty($rawItems)) {
            setFlash('error', 'No items submitted.');
            redirect('/modules/qc/form.php?grn_id=' . $grnId);
        }

        // ── Verify GRN exists and is in an inspectable status ─────────────────
        $stGrn = $db->prepare(
            "SELECT grn_id, grn_code, status FROM grn WHERE grn_id = ?"
        );
        $stGrn->execute([$grnId]);
        $grn = $stGrn->fetch();

        if (!$grn) {
            setFlash('error', 'GRN not found.');
            redirect('/modules/qc/list.php');
        }
        if (!in_array($grn['status'], ['DRAFT', 'RECEIVED', 'QC_IN_PROGRESS'], true)) {
            setFlash('error', 'This GRN is not available for QC inspection.');
            redirect('/modules/qc/view.php?grn_id=' . $grnId);
        }

        // ── Pre-validate dispositions before opening transaction ──────────────
        $validDispositions = ['PENDING', 'PUTAWAY', 'RSS', 'IN_HOUSE_REPAIR', 'SMART_BUYS'];
        foreach ($rawItems as $row) {
            $grnItemId   = (int)($row['grn_item_id'] ?? 0);
            if ($grnItemId <= 0) {
                continue;
            }
            $failedQty   = max(0, (int)($row['failed_qty'] ?? 0));
            $disposition = trim($row['disposition'] ?? 'PENDING');
            $changeSlipNo = trim($row['change_slip_no'] ?? '');
            if ($failedQty > 0
                && $disposition === 'IN_HOUSE_REPAIR'
                && $changeSlipNo === ''
            ) {
                setFlash('error', 'Change Slip No is required for items with In-House Repair disposition.');
                redirect('/modules/qc/form.php?grn_id=' . $grnId);
            }
        }

        try {
            $db->beginTransaction();

            // ── Upsert one qc_inspection row per grn_item ──────────────────────
            $stUpsert = $db->prepare(
                "INSERT INTO qc_inspection
                   (grn_id, grn_item_id, item_id, inspection_date,
                    inspected_qty, passed_qty, failed_qty,
                    qc_notes, inspected_by, status,
                    disposition, change_slip_no, change_slip_date)
                 VALUES
                   (:grn_id, :grn_item_id, :item_id, :inspection_date,
                    :inspected_qty, :passed_qty, :failed_qty,
                    :qc_notes, :inspected_by, :status,
                    :disposition, :change_slip_no, :change_slip_date)
                 ON DUPLICATE KEY UPDATE
                   grn_id          = VALUES(grn_id),
                   item_id         = VALUES(item_id),
                   inspection_date = VALUES(inspection_date),
                   inspected_qty   = VALUES(inspected_qty),
                   passed_qty      = VALUES(passed_qty),
                   failed_qty      = VALUES(failed_qty),
                   qc_notes        = VALUES(qc_notes),
                   inspected_by    = VALUES(inspected_by),
                   status          = VALUES(status),
                   disposition     = VALUES(disposition),
                   change_slip_no  = VALUES(change_slip_no),
                   change_slip_date = VALUES(change_slip_date)"
            );

            foreach ($rawItems as $row) {
                $grnItemId = (int)($row['grn_item_id'] ?? 0);
                $itemId    = (int)($row['item_id']     ?? 0) ?: null;
                $passedQty = max(0, (int)($row['passed_qty'] ?? 0));
                $failedQty = max(0, (int)($row['failed_qty'] ?? 0));
                $qcNotes   = trim($row['qc_notes'] ?? '') ?: null;

                if ($grnItemId <= 0) {
                    continue;
                }

                $inspectedQty = $passedQty + $failedQty;

                if ($failedQty === 0 && $passedQty > 0) {
                    $itemStatus = 'PASSED';
                } elseif ($passedQty === 0 && $failedQty > 0) {
                    $itemStatus = 'FAILED';
                } elseif ($passedQty > 0 && $failedQty > 0) {
                    $itemStatus = 'PARTIAL';
                } else {
                    $itemStatus = 'PENDING';
                }

                $disposition = trim($row['disposition'] ?? 'PENDING');
                if (!in_array($disposition, $validDispositions, true)) {
                    $disposition = 'PENDING';
                }
                // failed_qty == 0 always means PUTAWAY regardless of submitted value
                if ($failedQty === 0) {
                    $disposition = 'PUTAWAY';
                }

                $changeSlipNo   = trim($row['change_slip_no']   ?? '') ?: null;
                $changeSlipDate = trim($row['change_slip_date']  ?? '') ?: null;
                // Clear change slip data for non-repair dispositions
                if ($disposition !== 'IN_HOUSE_REPAIR') {
                    $changeSlipNo   = null;
                    $changeSlipDate = null;
                }

                $stUpsert->execute([
                    'grn_id'           => $grnId,
                    'grn_item_id'      => $grnItemId,
                    'item_id'          => $itemId,
                    'inspection_date'  => $inspectionDate,
                    'inspected_qty'    => $inspectedQty,
                    'passed_qty'       => $passedQty,
                    'failed_qty'       => $failedQty,
                    'qc_notes'         => $qcNotes,
                    'inspected_by'     => $userId,
                    'status'           => $itemStatus,
                    'disposition'      => $disposition,
                    'change_slip_no'   => $changeSlipNo,
                    'change_slip_date' => $changeSlipDate,
                ]);
            }

            // ── Aggregate results for this GRN ────────────────────────────────
            $stAgg = $db->prepare(
                "SELECT
                   COUNT(*)                                       AS total_items,
                   SUM(status = 'PASSED')                        AS passed_count,
                   SUM(status = 'FAILED')                        AS failed_count,
                   SUM(status = 'PARTIAL')                       AS partial_count,
                   SUM(failed_qty)                               AS total_failed_qty
                 FROM qc_inspection
                 WHERE grn_id = ?"
            );
            $stAgg->execute([$grnId]);
            $agg = $stAgg->fetch();

            $totalItems   = (int)$agg['total_items'];
            $passedCount  = (int)$agg['passed_count'];
            $failedCount  = (int)$agg['failed_count'];
            $totalFailed  = (int)$agg['total_failed_qty'];

            if ($totalItems > 0 && $passedCount === $totalItems) {
                $newStatus = 'COMPLETED';
            } elseif ($totalItems > 0 && $failedCount === $totalItems) {
                $newStatus = 'REJECTED';
            } else {
                $newStatus = 'QC_IN_PROGRESS';
            }

            // ── Update GRN status, rejected qty, ticket, and polybag ──────────
            $stUpdGrn = $db->prepare(
                "UPDATE grn
                 SET status               = :status,
                     total_qty_rejected   = :rejected,
                     ticket_id            = :tid,
                     polybag_qty_allocated = :pqa
                 WHERE grn_id = :grn_id"
            );
            $stUpdGrn->execute([
                'status'   => $newStatus,
                'rejected' => $totalFailed,
                'tid'      => $ticketId,
                'pqa'      => $polybagQtyAllocated,
                'grn_id'   => $grnId,
            ]);

            $db->commit();

            logActivity('EDIT', 'qc_inspection', $grnId, 'QC inspection saved for GRN ' . $grn['grn_code']);

            // Auto-route SMART_BUYS disposition items to smart_buys table
            $smartBuyItems = [];
            foreach ($rawItems as $row) {
                $disposition = $row['disposition'] ?? 'PENDING';
                $failedQty   = max(0, (int)($row['failed_qty'] ?? 0));
                if ($disposition === 'SMART_BUYS' && $failedQty > 0) {
                    $grnItemId     = (int)($row['grn_item_id'] ?? 0);
                    $smartBuyItems[] = ['grn_item_id' => $grnItemId, 'failed_qty' => $failedQty];
                }
            }

            if ($smartBuyItems) {
                foreach ($smartBuyItems as $sbi) {
                    $stSbChk = $db->prepare(
                        "SELECT qi.inspection_id, qi.item_id,
                                gi.sku_code, gi.item_name, gi.color, gi.size, gi.brand
                         FROM qc_inspection qi
                         LEFT JOIN grn_items gi ON gi.grn_item_id = qi.grn_item_id
                         WHERE qi.grn_item_id = ? AND qi.grn_id = ?"
                    );
                    $stSbChk->execute([$sbi['grn_item_id'], $grnId]);
                    $sbRow = $stSbChk->fetch();
                    if (!$sbRow) { continue; }

                    $stSbUps = $db->prepare(
                        "INSERT INTO smart_buys
                           (grn_id, grn_item_id, qc_item_id, item_id,
                            sku_code, item_name, color, size, brand,
                            qty, condition_code, source, status, created_by)
                         VALUES
                           (:gid, :giid, :qcid, :iid,
                            :sku, :nm, :co, :sz, :br,
                            :qty, 'DEFECTIVE', 'QC_FAIL', 'PENDING', :cb)
                         ON DUPLICATE KEY UPDATE
                           qty = VALUES(qty)"
                    );
                    try {
                        $stSbUps->execute([
                            'gid'  => $grnId,
                            'giid' => $sbi['grn_item_id'],
                            'qcid' => (int)($sbRow['inspection_id'] ?? 0),
                            'iid'  => $sbRow['item_id'] ?: null,
                            'sku'  => $sbRow['sku_code'],
                            'nm'   => $sbRow['item_name'],
                            'co'   => $sbRow['color'],
                            'sz'   => $sbRow['size'],
                            'br'   => $sbRow['brand'],
                            'qty'  => $sbi['failed_qty'],
                            'cb'   => $userId,
                        ]);
                    } catch (Exception $e) {
                        // Smart_buys routing is best-effort; do not abort the QC save
                    }
                }
            }

            setFlash('success', 'QC inspection saved successfully.');
            redirect('/modules/qc/view.php?grn_id=' . $grnId);

        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error', 'Error saving QC inspection: ' . $e->getMessage());
            redirect('/modules/qc/form.php?grn_id=' . $grnId);
        }
        break;

    // ── Approve GRN (QC approve permission required) ──────────────────────────

    case 'approve_grn':

        requirePermission('qc', 'approve');

        $grnId = (int)($_POST['grn_id'] ?? 0);
        if ($grnId <= 0) {
            setFlash('error', 'Invalid GRN.');
            redirect('/modules/qc/list.php');
        }

        $stGrn = $db->prepare(
            "SELECT grn_id, grn_code, status FROM grn WHERE grn_id = ?"
        );
        $stGrn->execute([$grnId]);
        $grn = $stGrn->fetch();

        if (!$grn) {
            setFlash('error', 'GRN not found.');
            redirect('/modules/qc/list.php');
        }
        if ($grn['status'] !== 'QC_IN_PROGRESS') {
            setFlash('error', 'Only GRNs in QC_IN_PROGRESS status can be approved.');
            redirect('/modules/qc/view.php?grn_id=' . $grnId);
        }

        $db->prepare("UPDATE grn SET status = 'COMPLETED' WHERE grn_id = ?")
           ->execute([$grnId]);

        logActivity('EDIT', 'qc_inspection', $grnId, 'GRN approved: ' . $grn['grn_code']);
        setFlash('success', 'GRN ' . $grn['grn_code'] . ' approved and marked as Completed.');
        redirect('/modules/qc/view.php?grn_id=' . $grnId);
        break;

    // ── Advance Putaway ───────────────────────────────────────────────────────

    case 'advance_putaway':

        requirePermission('qc', 'approve');

        $grnId = (int)($_POST['grn_id'] ?? 0);
        if ($grnId <= 0) {
            setFlash('error', 'Invalid GRN.');
            redirect('/modules/qc/list.php');
        }

        $stGrn = $db->prepare(
            "SELECT grn_id, grn_code, status, putaway_status FROM grn WHERE grn_id = ?"
        );
        $stGrn->execute([$grnId]);
        $grn = $stGrn->fetch();

        if (!$grn) {
            setFlash('error', 'GRN not found.');
            redirect('/modules/qc/list.php');
        }
        if ($grn['status'] !== 'COMPLETED') {
            setFlash('error', 'Putaway can only be advanced for COMPLETED GRNs.');
            redirect('/modules/qc/view.php?grn_id=' . $grnId);
        }
        if (($grn['putaway_status'] ?? 'PENDING') === 'COMPLETED') {
            setFlash('error', 'Putaway is already marked as complete.');
            redirect('/modules/qc/view.php?grn_id=' . $grnId);
        }

        $db->prepare(
            "UPDATE grn
             SET putaway_status      = 'COMPLETED',
                 putaway_completed_at = NOW()
             WHERE grn_id = ?"
        )->execute([$grnId]);

        logActivity('EDIT', 'grn', $grnId, 'Putaway marked complete for GRN ' . $grn['grn_code']);
        setFlash('success', 'Putaway marked complete.');
        redirect('/modules/qc/view.php?grn_id=' . $grnId);
        break;

    default:
        redirect('/modules/qc/list.php');
}
