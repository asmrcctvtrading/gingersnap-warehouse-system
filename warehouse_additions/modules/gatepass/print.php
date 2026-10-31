<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/gatepass/list.php'); }

$stmt = $db->prepare(
    "SELECT g.*, u.full_name AS created_by_name, po.po_number, grn.grn_code
     FROM gatepasses g
     LEFT JOIN users u              ON u.user_id  = g.created_by
     LEFT JOIN purchase_orders po   ON po.po_id   = g.po_id
     LEFT JOIN grn                  ON grn.grn_id = g.grn_id
     WHERE g.gp_id = ?"
);
$stmt->execute([$id]);
$gp = $stmt->fetch();

if (!$gp) {
    redirect('/modules/gatepass/list.php');
}

$stItems = $db->prepare(
    "SELECT gi.*,
            i.sku_code, i.item_name,
            wl.location_code, wl.zone AS location_zone
     FROM gatepass_items gi
     LEFT JOIN items i                ON i.item_id      = gi.item_id
     LEFT JOIN warehouse_locations wl ON wl.location_id = gi.from_location_id
     WHERE gi.gp_id = ?
     ORDER BY gi.gpi_id ASC"
);
$stItems->execute([$id]);
$items = $stItems->fetchAll();

$totalCartons = 0;
foreach ($items as $item) {
    $totalCartons += (int)$item['qty_cartons'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Dispatch Packet — <?= h($gp['gp_code']) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: Arial, sans-serif;
        font-size: 10pt;
        color: #000;
        padding: 24px;
    }
    h3 { font-size: 16pt; margin-bottom: 2px; }
    h4 { font-size: 13pt; margin-bottom: 12px; color: #444; letter-spacing: 1px; }
    .header-section { margin-bottom: 16px; }
    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .meta-table td { padding: 3px 8px; vertical-align: top; }
    .meta-table .label { font-weight: bold; width: 180px; color: #555; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .items-table th, .items-table td {
        padding: 4px 8px;
        border: 1px solid #ccc;
        text-align: left;
    }
    .items-table th { background: #f0f0f0; font-weight: bold; }
    .items-table .text-right { text-align: right; }
    .items-table tfoot td { font-weight: bold; background: #f8f8f8; }
    .footer-section { margin-top: 32px; }
    .sig-line { border-top: 1px solid #000; width: 260px; display: inline-block; margin-top: 40px; }
    .divider { border: none; border-top: 1px solid #ccc; margin: 12px 0; }
    .rss-section { background: #fffbe6; border: 1px solid #f0d060; padding: 8px 12px; margin-bottom: 16px; border-radius: 4px; }
    .rss-section h5 { font-size: 10pt; margin-bottom: 6px; }
    @media print {
        body { padding: 0; }
    }
</style>
</head>
<body>

<div class="header-section">
    <h3>Gingersnaps</h3>
    <h4>DISPATCH PACKET</h4>
</div>

<hr class="divider">

<table class="meta-table">
    <tr>
        <td class="label">GP Code</td>
        <td><?= h($gp['gp_code']) ?></td>
        <td class="label">Type</td>
        <td><?= h($gp['gp_type']) ?></td>
    </tr>
    <tr>
        <td class="label">Status</td>
        <td><?= h($gp['gp_status']) ?></td>
        <td class="label">Created Date</td>
        <td><?= h(substr($gp['created_at'] ?? '', 0, 10)) ?></td>
    </tr>
    <tr>
        <td class="label">To/From Party</td>
        <td><?= h($gp['to_party'] ?? '—') ?></td>
        <td class="label">Reference</td>
        <td><?= h($gp['reference'] ?? '—') ?></td>
    </tr>
    <?php if ($gp['purpose']): ?>
    <tr>
        <td class="label">Purpose</td>
        <td colspan="3"><?= h($gp['purpose']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if ($gp['gp_type'] === 'STOCK_TRANSFER' && ($gp['po_number'] ?? '')): ?>
    <tr>
        <td class="label">PO Reference</td>
        <td colspan="3"><?= h($gp['po_number']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<?php if ($gp['gp_type'] === 'RETURNABLE'): ?>
<div class="rss-section">
    <h5>Return to Subcontractor (RSS)</h5>
    <table class="meta-table">
        <tr>
            <td class="label">RSS Reason Code</td>
            <td><?= h($gp['rss_reason_code'] ?? '—') ?></td>
            <td class="label">Vendor Code</td>
            <td><?= h($gp['vendor_code'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="label">Returnable Date</td>
            <td><?= h($gp['returnable_date'] ?? '—') ?></td>
            <td class="label">GRN Reference</td>
            <td><?= h($gp['grn_code'] ?? ($gp['grn_id'] ? 'GRN #' . $gp['grn_id'] : '—')) ?></td>
        </tr>
    </table>
</div>
<?php endif; ?>

<table class="items-table">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Item Name</th>
            <th>From Location</th>
            <th class="text-right">Qty (Cartons)</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$items): ?>
    <tr>
        <td colspan="5" style="text-align:center;color:#888;">No items.</td>
    </tr>
    <?php endif; ?>
    <?php foreach ($items as $item): ?>
    <tr>
        <td><?= h($item['sku_code'] ?? '—') ?></td>
        <td><?= h($item['item_name'] ?? '—') ?></td>
        <td>
            <?php
            $locCode = $item['location_code'] ?? '';
            $locZone = $item['location_zone'] ?? '';
            echo $locCode
                ? h($locCode . ($locZone ? ' (' . $locZone . ')' : ''))
                : '—';
            ?>
        </td>
        <td class="text-right"><?= number_format((int)$item['qty_cartons']) ?></td>
        <td><?= $item['notes'] ? h($item['notes']) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">Total Cartons:</td>
            <td class="text-right"><?= number_format($totalCartons) ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer-section">
    <p style="font-size:8pt;color:#666;">Printed: <?= date('Y-m-d H:i:s') ?></p>
    <p style="margin-top:8px;">Authorized signature:</p>
    <div class="sig-line"></div>
</div>

<script>window.print();</script>
</body>
</html>
