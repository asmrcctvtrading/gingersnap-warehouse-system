<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create_item':
        requirePermission('items', 'create');
        $skuCode  = trim($_POST['sku_code']  ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        if ($skuCode === '' || $itemName === '') {
            setFlash('error', 'SKU Code and Item Name are required.');
            redirect('/modules/items/form.php');
        }
        try {
            $stmt = $db->prepare(
                "INSERT INTO items
                 (sku_code, item_name, description, color, size, brand, hsn_code, uom, status)
                 VALUES (:sc, :nm, :ds, :cl, :sz, :br, :hn, :um, 'active')"
            );
            $stmt->execute([
                'sc' => $skuCode,
                'nm' => $itemName,
                'ds' => trim($_POST['description'] ?? '') ?: null,
                'cl' => trim($_POST['color']       ?? '') ?: null,
                'sz' => trim($_POST['size']        ?? '') ?: null,
                'br' => trim($_POST['brand']       ?? '') ?: null,
                'hn' => trim($_POST['hsn_code']    ?? '') ?: null,
                'um' => trim($_POST['uom']         ?? '') ?: 'PCS',
            ]);
            $newId = (int)$db->lastInsertId();
            logActivity('CREATE', 'items', $newId, $skuCode);
            setFlash('success', 'Item "' . $skuCode . '" created successfully.');
            redirect('/modules/items/list.php');
        } catch (PDOException $e) {
            // Duplicate SKU
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate') !== false) {
                setFlash('error', 'SKU Code "' . $skuCode . '" already exists. Please use a unique SKU.');
            } else {
                setFlash('error', 'Database error: ' . $e->getMessage());
            }
            redirect('/modules/items/form.php');
        }
        break;

    case 'update_item':
        requirePermission('items', 'edit');
        $id       = (int)($_POST['id'] ?? 0);
        $itemName = trim($_POST['item_name'] ?? '');
        if ($id <= 0 || $itemName === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/items/list.php');
        }
        $stmt = $db->prepare(
            "UPDATE items
             SET item_name   = :nm,
                 description = :ds,
                 color       = :cl,
                 size        = :sz,
                 brand       = :br,
                 hsn_code    = :hn,
                 uom         = :um,
                 status      = :st
             WHERE item_id = :id"
        );
        $stmt->execute([
            'nm' => $itemName,
            'ds' => trim($_POST['description'] ?? '') ?: null,
            'cl' => trim($_POST['color']       ?? '') ?: null,
            'sz' => trim($_POST['size']        ?? '') ?: null,
            'br' => trim($_POST['brand']       ?? '') ?: null,
            'hn' => trim($_POST['hsn_code']    ?? '') ?: null,
            'um' => trim($_POST['uom']         ?? '') ?: 'PCS',
            'st' => in_array($_POST['status'] ?? '', ['active', 'inactive'])
                        ? $_POST['status'] : 'active',
            'id' => $id,
        ]);
        // Fetch sku for log label
        $skuRow = $db->prepare("SELECT sku_code FROM items WHERE item_id = ?");
        $skuRow->execute([$id]);
        $skuCode = $skuRow->fetchColumn() ?: (string)$id;
        logActivity('UPDATE', 'items', $id, $skuCode);
        setFlash('success', 'Item updated.');
        redirect('/modules/items/form.php?id=' . $id);
        break;

    case 'delete_item':
        requirePermission('items', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid item.');
            redirect('/modules/items/list.php');
        }
        // Guard: no stock positions
        $stmtSP = $db->prepare(
            "SELECT COUNT(*) FROM stock_positions WHERE item_id = ?"
        );
        $stmtSP->execute([$id]);
        if ((int)$stmtSP->fetchColumn() > 0) {
            setFlash('error', 'Cannot delete: item has stock position records.');
            redirect('/modules/items/list.php');
        }
        // Guard: no stock movements
        $stmtSM = $db->prepare(
            "SELECT COUNT(*) FROM stock_movements WHERE item_id = ?"
        );
        $stmtSM->execute([$id]);
        if ((int)$stmtSM->fetchColumn() > 0) {
            setFlash('error', 'Cannot delete: item has stock movement history.');
            redirect('/modules/items/list.php');
        }
        // Fetch sku for log
        $skuRow = $db->prepare("SELECT sku_code FROM items WHERE item_id = ?");
        $skuRow->execute([$id]);
        $skuCode = $skuRow->fetchColumn() ?: (string)$id;
        $db->prepare("DELETE FROM items WHERE item_id = ?")->execute([$id]);
        logActivity('DELETE', 'items', $id, $skuCode);
        setFlash('success', 'Item "' . $skuCode . '" deleted.');
        redirect('/modules/items/list.php');
        break;

    default:
        redirect('/modules/items/list.php');
}
