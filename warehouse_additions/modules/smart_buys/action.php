<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);

$validConditions = ['DEFECTIVE', 'DAMAGED', 'WRONG_ITEM', 'EXCESS'];
$validSources    = ['QC_FAIL', 'DC_DEFECTIVE', 'MANUAL'];

switch ($action) {

    case 'save_sb':

        $sbId      = (int)($_POST['sb_id'] ?? 0);
        $isEdit    = $sbId > 0;

        if ($isEdit) {
            requirePermission('smart_buys', 'edit');
        } else {
            requirePermission('smart_buys', 'create');
        }

        $itemName      = trim($_POST['item_name']      ?? '');
        $skuCode       = trim($_POST['sku_code']       ?? '') ?: null;
        $color         = trim($_POST['color']          ?? '') ?: null;
        $size          = trim($_POST['size']           ?? '') ?: null;
        $brand         = trim($_POST['brand']          ?? '') ?: null;
        $qty           = max(1, (int)($_POST['qty']    ?? 1));
        $conditionCode = trim($_POST['condition_code'] ?? 'DEFECTIVE');
        $source        = trim($_POST['source']         ?? 'MANUAL');
        $grnId         = (int)($_POST['grn_id']        ?? 0) ?: null;
        $notes         = trim($_POST['notes']          ?? '') ?: null;

        if ($itemName === '') {
            setFlash('error', 'Item Name is required.');
            redirect($isEdit ? '/modules/smart_buys/form.php?id=' . $sbId : '/modules/smart_buys/form.php');
        }
        if ($qty < 1) {
            setFlash('error', 'Qty must be at least 1.');
            redirect($isEdit ? '/modules/smart_buys/form.php?id=' . $sbId : '/modules/smart_buys/form.php');
        }
        if (!in_array($conditionCode, $validConditions, true)) {
            $conditionCode = 'DEFECTIVE';
        }
        if (!in_array($source, $validSources, true)) {
            $source = 'MANUAL';
        }

        if ($isEdit) {
            $stChk = $db->prepare("SELECT status, source FROM smart_buys WHERE sb_id = ?");
            $stChk->execute([$sbId]);
            $existing = $stChk->fetch();
            if (!$existing || $existing['status'] !== 'PENDING') {
                setFlash('error', 'Only PENDING items can be edited.');
                redirect('/modules/smart_buys/list.php');
            }
            // Preserve source for QC_FAIL records
            if ($existing['source'] === 'QC_FAIL') {
                $source = 'QC_FAIL';
            }

            $st = $db->prepare(
                "UPDATE smart_buys
                 SET sku_code = :sku, item_name = :nm, color = :co, size = :sz, brand = :br,
                     qty = :qty, condition_code = :cc, source = :src, grn_id = :gid, notes = :notes
                 WHERE sb_id = :id AND status = 'PENDING'"
            );
            $st->execute([
                'sku'   => $skuCode,
                'nm'    => $itemName,
                'co'    => $color,
                'sz'    => $size,
                'br'    => $brand,
                'qty'   => $qty,
                'cc'    => $conditionCode,
                'src'   => $source,
                'gid'   => $grnId,
                'notes' => $notes,
                'id'    => $sbId,
            ]);
            logActivity('EDIT', 'smart_buys', $sbId, 'Updated SMART Buys entry #' . $sbId);
            setFlash('success', 'SMART Buys entry updated.');
            redirect('/modules/smart_buys/view.php?id=' . $sbId);

        } else {
            $st = $db->prepare(
                "INSERT INTO smart_buys
                   (grn_id, sku_code, item_name, color, size, brand,
                    qty, condition_code, source, status, notes, created_by)
                 VALUES
                   (:gid, :sku, :nm, :co, :sz, :br,
                    :qty, :cc, :src, 'PENDING', :notes, :cb)"
            );
            $st->execute([
                'gid'   => $grnId,
                'sku'   => $skuCode,
                'nm'    => $itemName,
                'co'    => $color,
                'sz'    => $size,
                'br'    => $brand,
                'qty'   => $qty,
                'cc'    => $conditionCode,
                'src'   => $source,
                'notes' => $notes,
                'cb'    => $userId,
            ]);
            $newId = (int) $db->lastInsertId();
            logActivity('CREATE', 'smart_buys', $newId, 'Created SMART Buys entry #' . $newId);
            setFlash('success', 'SMART Buys entry created.');
            redirect('/modules/smart_buys/view.php?id=' . $newId);
        }
        break;

    case 'mark_listed':

        requirePermission('smart_buys', 'edit');

        $sbId      = (int)($_POST['sb_id'] ?? 0);
        $salePrice = (float)($_POST['sale_price'] ?? 0);

        if ($sbId <= 0) {
            setFlash('error', 'Invalid record.');
            redirect('/modules/smart_buys/list.php');
        }
        if ($salePrice <= 0) {
            setFlash('error', 'Sale price must be greater than zero.');
            redirect('/modules/smart_buys/view.php?id=' . $sbId);
        }

        $st = $db->prepare(
            "UPDATE smart_buys SET status = 'LISTED', sale_price = :sp
             WHERE sb_id = :id AND status = 'PENDING'"
        );
        $st->execute(['sp' => $salePrice, 'id' => $sbId]);

        logActivity('EDIT', 'smart_buys', $sbId, 'Marked LISTED at ₱' . number_format($salePrice, 2));
        setFlash('success', 'Item listed for SMART Buys sale at &#8369;' . number_format($salePrice, 2) . '.');
        redirect('/modules/smart_buys/view.php?id=' . $sbId);
        break;

    case 'mark_sold':

        requirePermission('smart_buys', 'edit');

        $sbId     = (int)($_POST['sb_id'] ?? 0);
        $soldDate = trim($_POST['sold_date'] ?? '');

        if ($sbId <= 0) {
            setFlash('error', 'Invalid record.');
            redirect('/modules/smart_buys/list.php');
        }
        if ($soldDate === '') {
            setFlash('error', 'Sold date is required.');
            redirect('/modules/smart_buys/view.php?id=' . $sbId);
        }

        $st = $db->prepare(
            "UPDATE smart_buys SET status = 'SOLD', sold_date = :sd
             WHERE sb_id = :id AND status = 'LISTED'"
        );
        $st->execute(['sd' => $soldDate, 'id' => $sbId]);

        logActivity('EDIT', 'smart_buys', $sbId, 'Marked SOLD on ' . $soldDate);
        setFlash('success', 'Item marked as SOLD.');
        redirect('/modules/smart_buys/view.php?id=' . $sbId);
        break;

    case 'mark_disposed':

        requirePermission('smart_buys', 'edit');

        $sbId         = (int)($_POST['sb_id'] ?? 0);
        $disposedDate = trim($_POST['disposed_date'] ?? '');

        if ($sbId <= 0) {
            setFlash('error', 'Invalid record.');
            redirect('/modules/smart_buys/list.php');
        }
        if ($disposedDate === '') {
            setFlash('error', 'Disposed date is required.');
            redirect('/modules/smart_buys/view.php?id=' . $sbId);
        }

        $st = $db->prepare(
            "UPDATE smart_buys SET status = 'DISPOSED', disposed_date = :dd
             WHERE sb_id = :id AND status IN ('PENDING','LISTED')"
        );
        $st->execute(['dd' => $disposedDate, 'id' => $sbId]);

        logActivity('EDIT', 'smart_buys', $sbId, 'Marked DISPOSED on ' . $disposedDate);
        setFlash('success', 'Item marked as DISPOSED.');
        redirect('/modules/smart_buys/view.php?id=' . $sbId);
        break;

    case 'delete_sb':

        requirePermission('smart_buys', 'edit');

        $sbId = (int)($_POST['sb_id'] ?? 0);

        if ($sbId <= 0) {
            setFlash('error', 'Invalid record.');
            redirect('/modules/smart_buys/list.php');
        }

        $st = $db->prepare("DELETE FROM smart_buys WHERE sb_id = ? AND status = 'PENDING'");
        $st->execute([$sbId]);

        if ($st->rowCount() === 0) {
            setFlash('error', 'Record not found or cannot be deleted (only PENDING items can be deleted).');
        } else {
            logActivity('DELETE', 'smart_buys', $sbId, 'Deleted SMART Buys entry #' . $sbId);
            setFlash('success', 'SMART Buys entry deleted.');
        }
        redirect('/modules/smart_buys/list.php');
        break;

    default:
        redirect('/modules/smart_buys/list.php');
}
