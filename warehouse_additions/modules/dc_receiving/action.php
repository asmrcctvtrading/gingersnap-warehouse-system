<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($action === 'save_dc_receiving') {
    $dcId           = (int) ($_POST['dc_id'] ?? 0);
    $isEdit         = $dcId > 0;
    $grnId          = isset($_POST['grn_id']) && $_POST['grn_id'] !== '' ? (int) $_POST['grn_id'] : null;
    $receivingDate  = trim($_POST['receiving_date'] ?? '');
    $receivingTime  = trim($_POST['receiving_time'] ?? '');
    $receivingPerson= trim($_POST['receiving_person'] ?? '');
    $dcType         = trim($_POST['dc_type'] ?? '');
    $totalQty       = (int) ($_POST['total_qty'] ?? 0);
    $defectiveQty   = max(0, (int) ($_POST['defective_qty'] ?? 0));
    $notes          = trim($_POST['notes'] ?? '');

    $errors = [];

    if ($receivingDate === '') {
        $errors[] = 'Receiving date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $receivingDate)) {
        $errors[] = 'Receiving date format is invalid.';
    }

    if ($totalQty < 1) {
        $errors[] = 'Total qty must be at least 1.';
    }

    if (!in_array($dcType, ['LOCAL', 'EXPORT', 'SMART_BUYS_DDF'])) {
        $errors[] = 'Invalid DC type.';
    }

    if ($receivingTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $receivingTime)) {
        $errors[] = 'Receiving time format is invalid.';
    }

    if ($errors) {
        setFlash('danger', implode(' ', $errors));
        $back = $isEdit ? '/modules/dc_receiving/form.php?id=' . $dcId : '/modules/dc_receiving/form.php';
        redirect($back);
    }

    $timeVal = $receivingTime !== '' ? $receivingTime : null;

    if ($isEdit) {
        requirePermission('dc_receiving', 'edit');

        $chk = $db->prepare("SELECT status FROM dc_receivings WHERE dc_id = ?");
        $chk->execute([$dcId]);
        $existing = $chk->fetch();

        if (!$existing || $existing['status'] !== 'RECEIVED') {
            setFlash('warning', 'Only RECEIVED records can be edited.');
            redirect('/modules/dc_receiving/view.php?id=' . $dcId);
        }

        $upd = $db->prepare(
            "UPDATE dc_receivings
             SET receiving_date   = :rd,
                 receiving_time   = :rt,
                 receiving_person = :rp,
                 dc_type          = :dt,
                 total_qty        = :tq,
                 defective_qty    = :dq,
                 notes            = :nt
             WHERE dc_id = :id AND status = 'RECEIVED'"
        );
        $upd->execute([
            'rd'  => $receivingDate,
            'rt'  => $timeVal,
            'rp'  => $receivingPerson ?: null,
            'dt'  => $dcType,
            'tq'  => $totalQty,
            'dq'  => $defectiveQty,
            'nt'  => $notes ?: null,
            'id'  => $dcId,
        ]);

    } else {
        requirePermission('dc_receiving', 'create');

        if ($grnId !== null) {
            $dup = $db->prepare("SELECT dc_id FROM dc_receivings WHERE grn_id = ?");
            $dup->execute([$grnId]);
            if ($dup->fetch()) {
                setFlash('danger', 'A DC Receiving record already exists for that GRN.');
                redirect('/modules/dc_receiving/form.php');
            }
        }

        $ins = $db->prepare(
            "INSERT INTO dc_receivings
               (grn_id, receiving_date, receiving_time, receiving_person,
                dc_type, total_qty, defective_qty, notes, created_by)
             VALUES
               (:gid, :rd, :rt, :rp, :dt, :tq, :dq, :nt, :cb)"
        );
        $ins->execute([
            'gid' => $grnId,
            'rd'  => $receivingDate,
            'rt'  => $timeVal,
            'rp'  => $receivingPerson ?: null,
            'dt'  => $dcType,
            'tq'  => $totalQty,
            'dq'  => $defectiveQty,
            'nt'  => $notes ?: null,
            'cb'  => $userId ?: null,
        ]);
        $dcId = (int) $db->lastInsertId();
    }

    // Defective auto-routing to smart_buys
    if ($defectiveQty > 0 && $grnId !== null) {
        try {
            $db->prepare(
                "INSERT IGNORE INTO smart_buys
                   (grn_id, qty, condition_code, source, status, notes, created_by)
                 VALUES (:gid, :qty, 'DEFECTIVE', 'DC_DEFECTIVE', 'PENDING', :nt, :cb)"
            )->execute([
                'gid' => $grnId,
                'qty' => $defectiveQty,
                'nt'  => 'DC Receiving defective',
                'cb'  => $userId ?: null,
            ]);
        } catch (Exception $e) {
            // Best-effort; do not block main save
        }
    }

    logActivity('dc_receiving', $isEdit ? 'UPDATE' : 'CREATE', $dcId, 'DC receiving record saved.');

    $msg = 'DC receipt recorded.';
    if ($defectiveQty > 0) {
        $msg .= ' ' . number_format($defectiveQty) . ' defective pcs auto-routed to SMART Buys.';
    }
    setFlash('success', $msg);
    redirect('/modules/dc_receiving/view.php?id=' . $dcId);
}

if ($action === 'mark_putaway_pending') {
    requirePermission('dc_receiving', 'edit');
    $dcId = (int) ($_POST['id'] ?? 0);

    $db->prepare(
        "UPDATE dc_receivings SET status = 'PUTAWAY_PENDING' WHERE dc_id = ? AND status = 'RECEIVED'"
    )->execute([$dcId]);

    logActivity('dc_receiving', 'PUTAWAY_PENDING', $dcId, 'Marked putaway pending.');
    setFlash('success', 'Marked as Putaway Pending.');
    redirect('/modules/dc_receiving/view.php?id=' . $dcId);
}

if ($action === 'mark_putaway_complete') {
    requirePermission('dc_receiving', 'edit');
    $dcId = (int) ($_POST['id'] ?? 0);

    $db->prepare(
        "UPDATE dc_receivings SET status = 'PUTAWAY_COMPLETED' WHERE dc_id = ? AND status = 'PUTAWAY_PENDING'"
    )->execute([$dcId]);

    logActivity('dc_receiving', 'PUTAWAY_COMPLETED', $dcId, 'Marked putaway complete.');
    setFlash('success', 'DC Receiving marked Putaway Complete.');
    redirect('/modules/dc_receiving/view.php?id=' . $dcId);
}

if ($action === 'delete_dc') {
    requirePermission('dc_receiving', 'edit');
    $dcId = (int) ($_POST['id'] ?? 0);

    $chk = $db->prepare("SELECT status FROM dc_receivings WHERE dc_id = ?");
    $chk->execute([$dcId]);
    $row = $chk->fetch();

    if (!$row || $row['status'] !== 'RECEIVED') {
        setFlash('warning', 'Only RECEIVED records can be deleted.');
        redirect('/modules/dc_receiving/view.php?id=' . $dcId);
    }

    $db->prepare("DELETE FROM dc_receivings WHERE dc_id = ? AND status = 'RECEIVED'")->execute([$dcId]);
    logActivity('dc_receiving', 'DELETE', $dcId, 'DC receiving record deleted.');
    setFlash('success', 'DC Receiving record deleted.');
    redirect('/modules/dc_receiving/list.php');
}

setFlash('danger', 'Unknown action.');
redirect('/modules/dc_receiving/list.php');
