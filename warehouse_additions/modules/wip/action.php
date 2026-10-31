<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

// ─── Back-redirect URL (preserves date filter) ────────────────────────────────
$rawFrom = $_POST['date_from'] ?? '';
$rawTo   = $_POST['date_to']   ?? '';
$backFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
$backTo   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
$backUrl  = '/modules/wip/list.php?date_from=' . urlencode($backFrom) . '&date_to=' . urlencode($backTo);

switch ($action) {

    // ── Save WIP Entry (create or update) ────────────────────────────────────
    case 'save_wip_entry':
        requirePermission('wip', 'create');

        $id        = (int)($_POST['id'] ?? 0);
        $txDate    = trim($_POST['transaction_date'] ?? '');
        $stage     = $_POST['stage']     ?? '';
        $direction = $_POST['direction'] ?? '';
        $qty       = (int)($_POST['qty'] ?? 0);

        // Validate required fields
        $errors = [];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txDate)) {
            $errors[] = 'Transaction date is required and must be a valid date (YYYY-MM-DD).';
        }
        if (!in_array($stage, ['RECEIVING', 'QC', 'DISPATCHING'], true)) {
            $errors[] = 'Stage must be RECEIVING, QC, or DISPATCHING.';
        }
        if (!in_array($direction, ['IN', 'OUT'], true)) {
            $errors[] = 'Direction must be IN or OUT.';
        }
        if ($qty <= 0) {
            $errors[] = 'Qty must be greater than 0.';
        }

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            redirect($backUrl);
        }

        // Optional fields
        $ctrlNumber     = trim($_POST['ctrl_number']     ?? '') ?: null;
        $ticketNumber   = trim($_POST['ticket_number']   ?? '') ?: null;
        $drNumber       = trim($_POST['dr_number']       ?? '') ?: null;
        $subconName     = trim($_POST['subcon_name']     ?? '') ?: null;
        $collectionName = trim($_POST['collection_name'] ?? '') ?: null;
        $styleCode      = trim($_POST['style_code']      ?? '') ?: null;
        $poNumber       = trim($_POST['po_number']       ?? '') ?: null;
        $localExport    = in_array($_POST['local_export'] ?? '', ['LOCAL', 'EXPORT'], true)
                          ? $_POST['local_export'] : null;
        $remarks        = trim($_POST['remarks'] ?? '') ?: null;
        $userId         = $_SESSION['user_id'] ?? null;

        try {
            $db->beginTransaction();

            if ($id > 0) {
                // ── UPDATE existing entry ─────────────────────────────────────
                $stmt = $db->prepare(
                    "UPDATE wip_transactions
                     SET transaction_date = :td,
                         stage            = :st,
                         direction        = :dr,
                         qty              = :qty,
                         ctrl_number      = :cn,
                         ticket_number    = :tn,
                         dr_number        = :dn,
                         subcon_name      = :sn,
                         collection_name  = :col,
                         style_code       = :sc,
                         po_number        = :po,
                         local_export     = :le,
                         remarks          = :rm
                     WHERE id = :id"
                );
                $stmt->execute([
                    'td'  => $txDate,
                    'st'  => $stage,
                    'dr'  => $direction,
                    'qty' => $qty,
                    'cn'  => $ctrlNumber,
                    'tn'  => $ticketNumber,
                    'dn'  => $drNumber,
                    'sn'  => $subconName,
                    'col' => $collectionName,
                    'sc'  => $styleCode,
                    'po'  => $poNumber,
                    'le'  => $localExport,
                    'rm'  => $remarks,
                    'id'  => $id,
                ]);

                $db->commit();
                logActivity('UPDATE', 'wip', $id,
                    "{$stage} {$direction} qty={$qty} date={$txDate}");
                setFlash('success', 'WIP entry updated.');

            } else {
                // ── INSERT new entry ─────────────────────────────────────────
                $stmtIns = $db->prepare(
                    "INSERT INTO wip_transactions
                     (transaction_date, stage, direction, qty,
                      ctrl_number, ticket_number, dr_number,
                      subcon_name, collection_name, style_code,
                      po_number, local_export, remarks, created_by)
                     VALUES
                     (:td, :st, :dr, :qty,
                      :cn, :tn, :dn,
                      :sn, :col, :sc,
                      :po, :le, :rm, :cb)"
                );
                $stmtIns->execute([
                    'td'  => $txDate,
                    'st'  => $stage,
                    'dr'  => $direction,
                    'qty' => $qty,
                    'cn'  => $ctrlNumber,
                    'tn'  => $ticketNumber,
                    'dn'  => $drNumber,
                    'sn'  => $subconName,
                    'col' => $collectionName,
                    'sc'  => $styleCode,
                    'po'  => $poNumber,
                    'le'  => $localExport,
                    'rm'  => $remarks,
                    'cb'  => $userId,
                ]);
                $newId = (int)$db->lastInsertId();

                // Auto-insert QC IN when saving RECEIVING OUT
                if ($stage === 'RECEIVING' && $direction === 'OUT') {
                    $autoRemarks = '[Auto] From RECEIVING OUT #' . $newId
                                 . ($remarks ? ' — ' . $remarks : '');
                    $stmtQc = $db->prepare(
                        "INSERT INTO wip_transactions
                         (transaction_date, stage, direction, qty,
                          ctrl_number, ticket_number, dr_number,
                          subcon_name, collection_name, style_code,
                          po_number, local_export, remarks, created_by)
                         VALUES
                         (:td, 'QC', 'IN', :qty,
                          :cn, :tn, :dn,
                          :sn, :col, :sc,
                          :po, :le, :rm, :cb)"
                    );
                    $stmtQc->execute([
                        'td'  => $txDate,
                        'qty' => $qty,
                        'cn'  => $ctrlNumber,
                        'tn'  => $ticketNumber,
                        'dn'  => $drNumber,
                        'sn'  => $subconName,
                        'col' => $collectionName,
                        'sc'  => $styleCode,
                        'po'  => $poNumber,
                        'le'  => $localExport,
                        'rm'  => $autoRemarks,
                        'cb'  => $userId,
                    ]);
                }

                $db->commit();
                logActivity('CREATE', 'wip', $newId,
                    "{$stage} {$direction} qty={$qty} date={$txDate}");

                $qcNote = ($stage === 'RECEIVING' && $direction === 'OUT')
                          ? ' (QC IN entry auto-created)' : '';
                setFlash('success', 'WIP entry saved.' . $qcNote);
            }

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Database error: ' . $e->getMessage());
        }

        redirect($backUrl);
        break;

    // ── Delete WIP Entry ─────────────────────────────────────────────────────
    case 'delete_wip_entry':
        requirePermission('wip', 'create');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid entry ID.');
            redirect($backUrl);
        }

        // Fetch the record before deleting to know what to cascade-delete
        $stCheck = $db->prepare("SELECT * FROM wip_transactions WHERE id = ? LIMIT 1");
        $stCheck->execute([$id]);
        $tx = $stCheck->fetch();

        if (!$tx) {
            setFlash('error', 'WIP entry not found.');
            redirect($backUrl);
        }

        try {
            $db->beginTransaction();

            $db->prepare("DELETE FROM wip_transactions WHERE id = ?")->execute([$id]);

            // If this was a RECEIVING OUT, also delete the auto-generated QC IN.
            // The auto-entry's remarks are stamped as "[Auto] From RECEIVING OUT #<id>…"
            if ((string)$tx['stage'] === 'RECEIVING' && (string)$tx['direction'] === 'OUT') {
                $db->prepare(
                    "DELETE FROM wip_transactions
                     WHERE stage     = 'QC'
                       AND direction = 'IN'
                       AND qty       = ?
                       AND transaction_date = ?
                       AND remarks LIKE ?
                     LIMIT 1"
                )->execute([
                    (int)$tx['qty'],
                    $tx['transaction_date'],
                    '[Auto] From RECEIVING OUT #' . $id . '%',
                ]);
            }

            $db->commit();
            logActivity('DELETE', 'wip', $id,
                $tx['stage'] . ' ' . $tx['direction'] . ' qty=' . $tx['qty']
                . ' date=' . $tx['transaction_date']);
            setFlash('success', 'WIP entry deleted.');

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            setFlash('error', 'Delete failed: ' . $e->getMessage());
        }

        redirect($backUrl);
        break;

    // ── Save Snapshot ────────────────────────────────────────────────────────
    case 'save_snapshot':
        requirePermission('wip', 'create');

        $snapDate = trim($_POST['snapshot_date'] ?? '');
        $fromDate = trim($_POST['date_from']     ?? date('Y-m-01'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $snapDate)) {
            setFlash('error', 'Invalid snapshot date.');
            redirect($backUrl);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $fromDate = date('Y-m-01');
        }

        // Helper closure: compute EWIP for a stage as of snapDate
        // using fromDate as the period start (for snapshot lookup).
        // $matrix must be initialised before the closure so the &reference is valid.
        $matrix      = [];
        $computeEwip = function (string $stage, bool $qcMode = false) use ($db, $fromDate, $snapDate, &$matrix): int {
            if ($qcMode) {
                // QC BWIP = RECEIVING EWIP (already computed above)
                $bwip = $matrix['RECEIVING'];
            } else {
                $snapPrev = date('Y-m-d', strtotime($fromDate . ' -1 day'));
                $prevSnap = $db->prepare(
                    "SELECT ewip_qty FROM wip_period_snapshots
                     WHERE snapshot_date = ? AND stage = ? LIMIT 1"
                );
                $prevSnap->execute([$snapPrev, $stage]);
                $row = $prevSnap->fetch();

                if ($row !== false) {
                    $bwip = (int)$row['ewip_qty'];
                } else {
                    $fallback = $db->prepare(
                        "SELECT COALESCE(SUM(CASE WHEN direction='IN' THEN qty ELSE -qty END), 0)
                         FROM wip_transactions
                         WHERE stage = ? AND transaction_date < ?"
                    );
                    $fallback->execute([$stage, $fromDate]);
                    $bwip = (int)$fallback->fetchColumn();
                }
            }

            $period = $db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN direction='IN' THEN qty ELSE -qty END), 0)
                 FROM wip_transactions
                 WHERE stage = ? AND transaction_date BETWEEN ? AND ?"
            );
            $period->execute([$stage, $fromDate, $snapDate]);
            $net = (int)$period->fetchColumn();

            return $bwip + $net;
        };

        // Compute in dependency order: RECEIVING first (QC depends on it)
        $matrix['RECEIVING']   = $computeEwip('RECEIVING');
        $matrix['QC']          = $computeEwip('QC', true);
        $matrix['DISPATCHING'] = $computeEwip('DISPATCHING');

        try {
            $upsert = $db->prepare(
                "INSERT INTO wip_period_snapshots (snapshot_date, stage, ewip_qty)
                 VALUES (:sd, :st, :qty)
                 ON DUPLICATE KEY UPDATE
                     ewip_qty   = VALUES(ewip_qty),
                     created_at = CURRENT_TIMESTAMP"
            );

            foreach ($matrix as $stg => $ewipQty) {
                $upsert->execute([
                    'sd'  => $snapDate,
                    'st'  => $stg,
                    'qty' => $ewipQty,
                ]);
            }

            logActivity('SNAPSHOT', 'wip', null,
                sprintf(
                    'Snapshot %s → RCV=%d, QC=%d, DSP=%d',
                    $snapDate,
                    $matrix['RECEIVING'],
                    $matrix['QC'],
                    $matrix['DISPATCHING']
                )
            );
            setFlash('success',
                'Snapshot saved for ' . date('d M Y', strtotime($snapDate)) . '. '
                . 'RCV: ' . number_format($matrix['RECEIVING'])
                . ' / QC: ' . number_format($matrix['QC'])
                . ' / DSP: ' . number_format($matrix['DISPATCHING'])
            );

        } catch (Throwable $e) {
            setFlash('error', 'Snapshot failed: ' . $e->getMessage());
        }

        redirect($backUrl);
        break;

    default:
        redirect('/modules/wip/list.php');
}
