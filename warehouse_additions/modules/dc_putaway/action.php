<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);

switch ($action) {

    case 'save_dc_putaway':
        $id     = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0;

        if ($isEdit) { requirePermission('dc_putaway','edit'); }
        else         { requirePermission('dc_putaway','create'); }

        $dcId          = (int)($_POST['dc_id']          ?? 0) ?: null;
        $gpId          = (int)($_POST['gp_id']          ?? 0) ?: null;
        $gpCode        = trim($_POST['gp_code']         ?? '') ?: null;
        $putawayDate   = trim($_POST['putaway_date']    ?? '');
        $putawayTime   = trim($_POST['putaway_time']    ?? '') ?: null;
        $putawayPerson = trim($_POST['putaway_person']  ?? '') ?: null;
        $warehouseType = trim($_POST['warehouse_type']  ?? 'LOCAL');
        $notes         = trim($_POST['notes']           ?? '') ?: null;

        $allowedTypes = ['LOCAL','EXPORT','SMART_BUYS'];
        if (!in_array($warehouseType, $allowedTypes, true)) {
            setFlash('error','Invalid warehouse type.');
            redirect($isEdit ? '/modules/dc_putaway/form.php?id='.$id : '/modules/dc_putaway/form.php');
        }
        if ($putawayDate === '') {
            setFlash('error','Putaway date is required.');
            redirect($isEdit ? '/modules/dc_putaway/form.php?id='.$id : '/modules/dc_putaway/form.php');
        }

        $rawItems = $_POST['items'] ?? [];
        $lineItems = [];
        foreach ($rawItems as $row) {
            $qty = max(0, (int)($row['qty_cartons'] ?? 0));
            if ($qty === 0 && trim($row['sku_code'] ?? '') === '' && trim($row['item_name'] ?? '') === '') continue;
            $lineItems[] = [
                'location_id' => (int)($row['location_id'] ?? 0) ?: null,
                'item_id'     => (int)($row['item_id']     ?? 0) ?: null,
                'po_id'       => (int)($row['po_id']       ?? 0) ?: null,
                'sku_code'    => trim($row['sku_code']  ?? '') ?: null,
                'item_name'   => trim($row['item_name'] ?? '') ?: null,
                'qty_cartons' => $qty,
                'notes'       => trim($row['notes'] ?? '') ?: null,
            ];
        }

        if (empty($lineItems)) {
            setFlash('error','At least one item with qty > 0 is required.');
            redirect($isEdit ? '/modules/dc_putaway/form.php?id='.$id : '/modules/dc_putaway/form.php');
        }

        $totalQty = array_sum(array_column($lineItems, 'qty_cartons'));

        try {
            $db->beginTransaction();

            if ($isEdit) {
                $st = $db->prepare(
                    "SELECT dcp_id, status FROM dc_putaways WHERE dcp_id = ?"
                );
                $st->execute([$id]);
                $existing = $st->fetch();
                if (!$existing || $existing['status'] !== 'PENDING') {
                    $db->rollBack();
                    setFlash('error','Record not found or not editable.');
                    redirect('/modules/dc_putaway/list.php');
                }
                $db->prepare(
                    "UPDATE dc_putaways SET dc_id=:dc,gp_id=:gp,gp_code=:gc,putaway_date=:pd,putaway_time=:pt,
                     putaway_person=:pp,warehouse_type=:wt,total_qty=:tq,notes=:nt WHERE dcp_id=:id"
                )->execute(['dc'=>$dcId,'gp'=>$gpId,'gc'=>$gpCode,'pd'=>$putawayDate,'pt'=>$putawayTime,
                             'pp'=>$putawayPerson,'wt'=>$warehouseType,'tq'=>$totalQty,'nt'=>$notes,'id'=>$id]);
                $db->prepare("DELETE FROM dc_putaway_items WHERE dcp_id=?")->execute([$id]);
                $dcpId = $id;
            } else {
                $db->prepare(
                    "INSERT INTO dc_putaways (dc_id,gp_id,gp_code,putaway_date,putaway_time,putaway_person,
                     warehouse_type,total_qty,status,notes,created_by)
                     VALUES (:dc,:gp,:gc,:pd,:pt,:pp,:wt,:tq,'PENDING',:nt,:cb)"
                )->execute(['dc'=>$dcId,'gp'=>$gpId,'gc'=>$gpCode,'pd'=>$putawayDate,'pt'=>$putawayTime,
                             'pp'=>$putawayPerson,'wt'=>$warehouseType,'tq'=>$totalQty,'nt'=>$notes,'cb'=>$userId]);
                $dcpId = (int)$db->lastInsertId();
            }

            $stItem = $db->prepare(
                "INSERT INTO dc_putaway_items (dcp_id,location_id,item_id,po_id,sku_code,item_name,qty_cartons,notes)
                 VALUES (:did,:loc,:iid,:pid,:sk,:nm,:qty,:nt)"
            );
            foreach ($lineItems as $li) {
                $stItem->execute(['did'=>$dcpId,'loc'=>$li['location_id'],'iid'=>$li['item_id'],'pid'=>$li['po_id'],
                                  'sk'=>$li['sku_code'],'nm'=>$li['item_name'],'qty'=>$li['qty_cartons'],'nt'=>$li['notes']]);
            }

            $db->commit();
            logActivity('SAVE','dc_putaway',$dcpId,$isEdit?'update':'create');
            setFlash('success','DC Putaway '.($isEdit?'updated':'created').'.');
            redirect('/modules/dc_putaway/view.php?id='.$dcpId);
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error','Error saving: '.$e->getMessage());
            redirect($isEdit ? '/modules/dc_putaway/form.php?id='.$id : '/modules/dc_putaway/form.php');
        }
        break;

    case 'confirm_putaway':
        requirePermission('dc_putaway','putaway');
        $id = (int)($_POST['id'] ?? 0);

        $stDcp = $db->prepare("SELECT * FROM dc_putaways WHERE dcp_id = ?");
        $stDcp->execute([$id]);
        $dcp = $stDcp->fetch();

        if (!$dcp) { setFlash('error','Record not found.'); redirect('/modules/dc_putaway/list.php'); }
        if ($dcp['status'] !== 'PENDING') { setFlash('error','Already confirmed.'); redirect('/modules/dc_putaway/view.php?id='.$id); }

        $stItems = $db->prepare("SELECT * FROM dc_putaway_items WHERE dcp_id = ?");
        $stItems->execute([$id]);
        $items = $stItems->fetchAll();

        try {
            $db->beginTransaction();

            foreach ($items as $item) {
                if ((int)$item['qty_cartons'] <= 0) continue;

                // Insert stock movement
                $db->prepare(
                    "INSERT INTO stock_movements (movement_type,to_location_id,item_id,po_id,qty_cartons,qty_pieces,reference_doc,notes,created_by)
                     VALUES ('PUTAWAY',:loc,:iid,:pid,:qty,0,:ref,:nt,:cb)"
                )->execute([
                    'loc' => $item['location_id'],
                    'iid' => $item['item_id'],
                    'pid' => $item['po_id'],
                    'qty' => $item['qty_cartons'],
                    'ref' => $dcp['gp_code'],
                    'nt'  => 'DC Putaway #'.$id,
                    'cb'  => $userId,
                ]);

                // Upsert stock_positions
                if ($item['location_id']) {
                    $stPos = $db->prepare(
                        "SELECT position_id FROM stock_positions
                         WHERE location_id = :loc
                           AND (item_id = :iid OR (:iid IS NULL AND item_id IS NULL))
                           AND (po_id = :pid OR (:pid IS NULL AND po_id IS NULL))
                         LIMIT 1"
                    );
                    $stPos->execute(['loc'=>$item['location_id'],'iid'=>$item['item_id'],'pid'=>$item['po_id']]);
                    $pos = $stPos->fetch();

                    if ($pos) {
                        $db->prepare("UPDATE stock_positions SET qty_cartons=qty_cartons+:qty,updated_by=:ub WHERE position_id=:id")
                           ->execute(['qty'=>$item['qty_cartons'],'ub'=>$userId,'id'=>$pos['position_id']]);
                    } else {
                        $db->prepare(
                            "INSERT INTO stock_positions (location_id,item_id,po_id,qty_cartons,qty_pieces,updated_by)
                             VALUES (:loc,:iid,:pid,:qty,0,:ub)"
                        )->execute(['loc'=>$item['location_id'],'iid'=>$item['item_id'],'pid'=>$item['po_id'],
                                    'qty'=>$item['qty_cartons'],'ub'=>$userId]);
                    }
                }
            }

            // Mark putaway completed
            $db->prepare("UPDATE dc_putaways SET status='COMPLETED' WHERE dcp_id=?")->execute([$id]);

            // Update DC receiving
            if ($dcp['dc_id']) {
                $db->prepare("UPDATE dc_receivings SET status='PUTAWAY_COMPLETED' WHERE dc_id=?")->execute([$dcp['dc_id']]);
                // Update GRN putaway_status
                $stGrnId = $db->prepare("SELECT grn_id FROM dc_receivings WHERE dc_id=?");
                $stGrnId->execute([$dcp['dc_id']]);
                $grnId = $stGrnId->fetchColumn();
                if ($grnId) {
                    try {
                        $db->prepare("UPDATE grn SET putaway_status='COMPLETED',putaway_completed_at=NOW() WHERE grn_id=?")
                           ->execute([$grnId]);
                    } catch (Exception $e) { /* column may not exist */ }
                }
            }

            $db->commit();
            logActivity('CONFIRM','dc_putaway',$id,'confirmed');
            setFlash('success','Putaway confirmed. '.number_format((int)$dcp['total_qty']).' cartons putaway. Stock positions updated.');
            redirect('/modules/dc_putaway/view.php?id='.$id);
        } catch (Exception $e) {
            $db->rollBack();
            setFlash('error','Error confirming putaway: '.$e->getMessage());
            redirect('/modules/dc_putaway/view.php?id='.$id);
        }
        break;

    case 'delete_dcp':
        requirePermission('dc_putaway','edit');
        $id = (int)($_POST['id'] ?? 0);
        $stDcp = $db->prepare("SELECT status FROM dc_putaways WHERE dcp_id=?");
        $stDcp->execute([$id]);
        $dcp = $stDcp->fetch();
        if (!$dcp) { setFlash('error','Not found.'); redirect('/modules/dc_putaway/list.php'); }
        if ($dcp['status'] !== 'PENDING') { setFlash('error','Only PENDING records can be deleted.'); redirect('/modules/dc_putaway/view.php?id='.$id); }
        $db->prepare("DELETE FROM dc_putaway_items WHERE dcp_id=?")->execute([$id]);
        $db->prepare("DELETE FROM dc_putaways WHERE dcp_id=? AND status='PENDING'")->execute([$id]);
        logActivity('DELETE','dc_putaway',$id,'deleted');
        setFlash('success','Putaway record deleted.');
        redirect('/modules/dc_putaway/list.php');
        break;

    default:
        redirect('/modules/dc_putaway/list.php');
}
