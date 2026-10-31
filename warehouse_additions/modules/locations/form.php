<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db         = getDB();
$id         = (int)($_GET['id'] ?? 0);
$isEdit     = $id > 0;
$zoneManage = isset($_GET['zone_manage']);

if ($isEdit) {
    requirePermission('locations', 'edit');
    $stmt = $db->prepare("SELECT * FROM warehouse_locations WHERE location_id = ?");
    $stmt->execute([$id]);
    $loc = $stmt->fetch();
    if (!$loc) { redirect('/modules/locations/list.php'); }
} else {
    requirePermission($zoneManage ? 'locations' : 'locations', $zoneManage ? 'create' : 'create');
    $loc = [
        'location_id'   => 0,
        'location_code' => '',
        'zone'          => '',
        'description'   => '',
        'status'        => 'active',
    ];
}

$zones = $db->query(
    "SELECT zone_id, zone_code, zone_name, zone_type, is_active
     FROM warehouse_zones
     ORDER BY zone_code"
)->fetchAll();

// For inline zone edit
$editZoneId = (int)($_GET['edit_zone'] ?? 0);
$editZone   = null;
if ($editZoneId > 0 && $zoneManage) {
    $stmt = $db->prepare("SELECT * FROM warehouse_zones WHERE zone_id = ?");
    $stmt->execute([$editZoneId]);
    $editZone = $stmt->fetch();
}

$pageTitle = $zoneManage ? 'Manage Zones' : ($isEdit ? 'Edit Location' : 'New Location');
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">
        <?php if ($zoneManage): ?>
            <i class="bi bi-diagram-3 me-1"></i>Manage Zones
        <?php elseif ($isEdit): ?>
            Edit Location
        <?php else: ?>
            New Location
        <?php endif; ?>
    </h5>
</div>

<div class="row g-4">

<?php if ($zoneManage): ?>

<!-- ── Zone list + add form ──────────────────────────────────────────── -->
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-diagram-3 me-2"></i>Zones
        </div>
        <div class="card-body">
            <!-- Add zone form -->
            <?php if (can('locations', 'create')): ?>
            <form method="post" action="action.php" class="mb-4 border-bottom pb-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_zone">
                <p class="fw-semibold mb-2 small text-muted text-uppercase">Add New Zone</p>
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <input type="text" name="zone_code" class="form-control form-control-sm"
                               placeholder="Code (e.g. A)" maxlength="20" required>
                    </div>
                    <div class="col-8">
                        <input type="text" name="zone_name" class="form-control form-control-sm"
                               placeholder="Zone Name" maxlength="100" required>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-5">
                        <select name="zone_type" class="form-select form-select-sm">
                            <?php foreach (['RECEIVING','STORAGE','QC','STAGING','DISPATCH'] as $zt): ?>
                            <option value="<?= $zt ?>"><?= $zt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <input type="text" name="notes" class="form-control form-control-sm"
                               placeholder="Notes (optional)" maxlength="255">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-dark btn-sm w-100">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <!-- Zone table -->
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($zones as $z): ?>
                <tr class="<?= $editZoneId === (int)$z['zone_id'] ? 'table-primary' : '' ?>">
                    <td class="fw-semibold"><?= h($z['zone_code']) ?></td>
                    <td><?= h($z['zone_name']) ?></td>
                    <td><small class="text-muted"><?= h($z['zone_type']) ?></small></td>
                    <td>
                        <?= $z['is_active']
                            ? '<span class="badge bg-success">Yes</span>'
                            : '<span class="badge bg-secondary">No</span>' ?>
                    </td>
                    <td class="text-end" style="white-space:nowrap">
                        <?php if (can('locations', 'edit')): ?>
                        <a href="form.php?zone_manage=1&edit_zone=<?= (int)$z['zone_id'] ?>"
                           class="btn btn-sm btn-outline-secondary py-0 px-1 me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (can('locations', 'delete')): ?>
                        <form method="post" action="action.php" class="d-inline"
                              onsubmit="return confirm('Delete zone <?= h($z['zone_code']) ?>? Locations inside will also be deleted.')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_zone">
                            <input type="hidden" name="zone_id" value="<?= (int)$z['zone_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$zones): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No zones yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Inline zone edit panel ─────────────────────────────────────────── -->
<?php if ($editZone && can('locations', 'edit')): ?>
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-pencil me-2"></i>Edit Zone: <?= h($editZone['zone_code']) ?>
        </div>
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_zone">
                <input type="hidden" name="zone_id" value="<?= (int)$editZone['zone_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Zone Code</label>
                    <input type="text" name="zone_code" class="form-control"
                           value="<?= h($editZone['zone_code']) ?>" maxlength="20" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Zone Name</label>
                    <input type="text" name="zone_name" class="form-control"
                           value="<?= h($editZone['zone_name']) ?>" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Zone Type</label>
                    <select name="zone_type" class="form-select">
                        <?php foreach (['RECEIVING','STORAGE','QC','STAGING','DISPATCH'] as $zt): ?>
                        <option value="<?= $zt ?>" <?= $editZone['zone_type'] === $zt ? 'selected' : '' ?>>
                            <?= $zt ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" name="notes" class="form-control"
                           value="<?= h($editZone['notes']) ?>" maxlength="255">
                </div>
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" name="is_active"
                           id="is_active" value="1"
                           <?= $editZone['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Save Zone</button>
                    <a href="form.php?zone_manage=1" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

<!-- ── Location create / edit form ───────────────────────────────────── -->
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action"
                       value="<?= $isEdit ? 'update_location' : 'create_location' ?>">
                <input type="hidden" name="id"
                       value="<?= (int)$loc['location_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Zone <span class="text-danger">*</span></label>
                    <input type="text" name="zone" class="form-control"
                           value="<?= h($loc['zone']) ?>" required maxlength="50"
                           placeholder="e.g. Zone A, Receiving, Storage">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Location Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="location_code" class="form-control"
                           value="<?= h($loc['location_code']) ?>" required maxlength="30"
                           placeholder="e.g. A-01-01">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" class="form-control"
                           value="<?= h($loc['description'] ?? '') ?>" maxlength="150"
                           placeholder="e.g. Rack A, Row 1, Shelf 1">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['active','inactive'] as $s): ?>
                        <option value="<?= $s ?>"
                            <?= $loc['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <?= $isEdit ? 'Save Changes' : 'Create Location' ?>
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
