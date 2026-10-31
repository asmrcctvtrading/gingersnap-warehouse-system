<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('locations', 'view');

$db = getDB();

// Locations grouped by zone, with current carton sum from stock_positions
$rows = $db->query(
    "SELECT wl.*,
            COALESCE(SUM(sp.qty_cartons), 0) AS current_cartons
     FROM warehouse_locations wl
     LEFT JOIN stock_positions sp ON sp.location_id = wl.location_id
     GROUP BY wl.location_id
     ORDER BY wl.zone, wl.location_code"
)->fetchAll();

$byZone = [];
foreach ($rows as $r) {
    $zkey = $r['zone'] ?? '';
    if (!isset($byZone[$zkey])) {
        $byZone[$zkey] = [
            'zone_label' => $zkey,
            'locations'  => [],
        ];
    }
    $byZone[$zkey]['locations'][] = $r;
}

$pageTitle = 'Warehouse Locations';
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
        <h5 class="mb-0">Warehouse Locations</h5>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if (can('locations', 'edit') || can('locations', 'create')): ?>
        <a href="form.php?zone_manage=1" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i>Manage Zones
        </a>
        <?php endif; ?>
        <?php if (can('locations', 'create')): ?>
        <a href="form.php" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Location
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$byZone): ?>
<div class="alert alert-info">
    No locations found.
    <?php if (can('locations', 'create')): ?>
    <a href="form.php?zone_manage=1">Create a zone first</a>, then add locations.
    <?php endif; ?>
</div>
<?php endif; ?>

<?php foreach ($byZone as $zkey => $zoneData):
    $zoneLabel = $zoneData['zone_label'];
    $locs      = $zoneData['locations'];
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <span class="fw-bold"><?= h($zoneLabel !== '' ? $zoneLabel : '(No Zone)') ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Location Code</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th class="text-end">Curr. Cartons</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($locs as $loc):
                $statusBadge = match($loc['status']) {
                    'active'   => 'success',
                    'inactive' => 'secondary',
                    default    => 'secondary',
                };
            ?>
            <tr>
                <td class="fw-semibold"><?= h($loc['location_code']) ?></td>
                <td class="small text-muted"><?= h($loc['description'] ?? '') ?></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($loc['status']) ?></span></td>
                <td class="text-end"><?= fmtNum($loc['current_cartons']) ?></td>
                <td class="text-end">
                    <?php if (can('locations', 'edit')): ?>
                    <a href="form.php?id=<?= (int)$loc['location_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <?php endif; ?>
                    <?php if (can('locations', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete location <?= h($loc['location_code']) ?>? This will also remove stock position data.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_location">
                        <input type="hidden" name="id" value="<?= (int)$loc['location_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$locs): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">No locations in this zone.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
