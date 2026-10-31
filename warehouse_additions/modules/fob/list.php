<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('fob_shipment', 'view');

$db = getDB();

$status = $_GET['status'] ?? '';
$where  = $status ? "WHERE fs.status = " . $db->quote($status) : "";

$rows = $db->query(
    "SELECT fs.*,
            d.destination_name,
            COUNT(fi.fob_item_id)  AS po_count,
            SUM(fi.qty_shipped)    AS total_qty,
            SUM(fi.cartons)        AS total_cartons
     FROM fob_shipments fs
     LEFT JOIN destinations d  ON d.destination_id = fs.destination_id
     LEFT JOIN fob_shipment_items fi ON fi.fob_id = fs.fob_id
     $where
     GROUP BY fs.fob_id
     ORDER BY fs.fob_id DESC LIMIT 200"
)->fetchAll();

$pageTitle = 'FOB Shipments';
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <h5 class="mb-0">FOB Shipments</h5>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <div class="d-flex gap-2">
        <select onchange="location.href='list.php?status='+this.value" class="form-select form-select-sm" style="width:auto">
            <option value="">All Statuses</option>
            <?php foreach (['BOOKING','LOADED','IN_TRANSIT','ARRIVED','CANCELLED'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (can('fob_shipment', 'create')): ?>
        <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Shipment</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Booking Ref</th>
                    <th>Vessel / Flight</th>
                    <th>ETD</th>
                    <th>ETA</th>
                    <th>Destination</th>
                    <th>Buyer</th>
                    <th class="text-end">POs</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Cartons</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $badge = match($r['status']) {
                    'BOOKING'    => 'secondary',
                    'LOADED'     => 'info',
                    'IN_TRANSIT' => 'primary',
                    'ARRIVED'    => 'success',
                    'CANCELLED'  => 'danger',
                    default      => 'secondary',
                };
            ?>
            <tr>
                <td class="fw-semibold"><?= h($r['booking_ref']) ?></td>
                <td class="small"><?= h($r['vessel_flight']) ?></td>
                <td class="small"><?= fmtDate($r['etd']) ?></td>
                <td class="small"><?= fmtDate($r['eta']) ?></td>
                <td class="small"><?= h($r['destination_name']) ?></td>
                <td class="small"><?= h($r['buyer']) ?></td>
                <td class="text-end"><?= (int)$r['po_count'] ?></td>
                <td class="text-end"><?= fmtNum($r['total_qty']) ?></td>
                <td class="text-end"><?= fmtNum($r['total_cartons']) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span></td>
                <td class="text-end">
                    <?php if (can('fob_shipment', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$r['fob_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('fob_shipment', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete shipment <?= h($r['booking_ref']) ?>?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['fob_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="11" class="text-center text-muted py-4">No FOB shipments yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
