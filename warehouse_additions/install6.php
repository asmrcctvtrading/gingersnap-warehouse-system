<?php
/**
 * Gingersnaps Warehouse — Module 6 Installer
 * Installs: Warehouse Locations, Stock Positions, Movement Ledger
 * Upload to /public_html/warehouse/install6.php
 * Visit while logged in as Admin, run migration6.sql first, then install.
 * Delete this file from the server after a successful install.
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

if (!can('users', 'view')) {
    die('Administrator access required.');
}

// ─── File definitions ──────────────────────────────────────────────────────
$files = [];

// ── modules/locations/list.php ────────────────────────────────────────────
$files['modules/locations/list.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('locations', 'view');

$db = getDB();

// Locations grouped by zone, with current carton sum from stock_positions
$rows = $db->query(
    "SELECT wl.*,
            wz.zone_code, wz.zone_name, wz.zone_type,
            COALESCE(SUM(sp.qty_cartons), 0) AS current_cartons
     FROM warehouse_locations wl
     JOIN warehouse_zones wz      ON wz.zone_id     = wl.zone_id
     LEFT JOIN stock_positions sp ON sp.location_id = wl.location_id
     GROUP BY wl.location_id
     ORDER BY wz.zone_code, wl.location_code"
)->fetchAll();

$byZone = [];
foreach ($rows as $r) {
    $zid = $r['zone_id'];
    if (!isset($byZone[$zid])) {
        $byZone[$zid] = [
            'zone'      => [
                'zone_code' => $r['zone_code'],
                'zone_name' => $r['zone_name'],
                'zone_type' => $r['zone_type'],
            ],
            'locations' => [],
        ];
    }
    $byZone[$zid]['locations'][] = $r;
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

<?php foreach ($byZone as $zoneId => $zoneData):
    $zone = $zoneData['zone'];
    $locs = $zoneData['locations'];
    $typeBadge = match($zone['zone_type']) {
        'RECEIVING' => 'info',
        'STORAGE'   => 'primary',
        'QC'        => 'warning',
        'STAGING'   => 'secondary',
        'DISPATCH'  => 'success',
        default     => 'secondary',
    };
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <span class="fw-bold"><?= h($zone['zone_code']) ?></span>
        <span class="text-muted"><?= h($zone['zone_name']) ?></span>
        <span class="badge bg-<?= $typeBadge ?> ms-auto"><?= h($zone['zone_type']) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Location Code</th>
                    <th>Location Name</th>
                    <th>Status</th>
                    <th class="text-end">Capacity</th>
                    <th class="text-end">Curr. Cartons</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($locs as $loc):
                $statusBadge = match($loc['status']) {
                    'ACTIVE'   => 'success',
                    'INACTIVE' => 'secondary',
                    'RESERVED' => 'warning',
                    default    => 'secondary',
                };
                $fillPct = ($loc['capacity_cartons'] && $loc['capacity_cartons'] > 0)
                    ? min(100, (int)round($loc['current_cartons'] / $loc['capacity_cartons'] * 100))
                    : null;
                $barColor = $fillPct !== null
                    ? ($fillPct >= 90 ? 'bg-danger' : ($fillPct >= 70 ? 'bg-warning' : 'bg-success'))
                    : 'bg-success';
            ?>
            <tr>
                <td class="fw-semibold"><?= h($loc['location_code']) ?></td>
                <td><?= h($loc['location_name']) ?></td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= h($loc['status']) ?></span></td>
                <td class="text-end">
                    <?= $loc['capacity_cartons'] !== null ? fmtNum($loc['capacity_cartons']) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="text-end">
                    <?= fmtNum($loc['current_cartons']) ?>
                    <?php if ($fillPct !== null): ?>
                    <div class="progress mt-1" style="height:4px;min-width:60px">
                        <div class="progress-bar <?= $barColor ?>" style="width:<?= $fillPct ?>%"></div>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= h($loc['notes']) ?></td>
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
            <tr><td colspan="7" class="text-center text-muted py-3">No locations in this zone.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/locations/form.php ────────────────────────────────────────────
$files['modules/locations/form.php'] = <<<'END_OF_FILE'
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
    requirePermission('locations', 'create');
    $loc = [
        'location_id'      => 0,
        'zone_id'          => (int)($_GET['zone_id'] ?? 0),
        'location_code'    => '',
        'location_name'    => '',
        'status'           => 'ACTIVE',
        'capacity_cartons' => '',
        'notes'            => '',
    ];
}

$zones = $db->query(
    "SELECT zone_id, zone_code, zone_name, zone_type, is_active
     FROM warehouse_zones
     ORDER BY zone_code"
)->fetchAll();

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

<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold py-2">
            <i class="bi bi-diagram-3 me-2"></i>Zones
        </div>
        <div class="card-body">
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

<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" action="action.php">
                <?= csrfField() ?>
                <input type="hidden" name="action"
                       value="<?= $isEdit ? 'update_location' : 'create_location' ?>">
                <input type="hidden" name="id" value="<?= (int)$loc['location_id'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Zone <span class="text-danger">*</span></label>
                    <select name="zone_id" class="form-select" required>
                        <option value="">— select zone —</option>
                        <?php foreach ($zones as $z): ?>
                        <option value="<?= (int)$z['zone_id'] ?>"
                            <?= (string)$loc['zone_id'] === (string)$z['zone_id'] ? 'selected' : '' ?>>
                            <?= h($z['zone_code']) ?> — <?= h($z['zone_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$zones): ?>
                    <div class="form-text text-danger">
                        No zones exist.
                        <?php if (can('locations', 'create')): ?>
                        <a href="form.php?zone_manage=1">Create a zone first.</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Location Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="location_code" class="form-control"
                           value="<?= h($loc['location_code']) ?>" required maxlength="50"
                           placeholder="e.g. A-01-01">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Location Name</label>
                    <input type="text" name="location_name" class="form-control"
                           value="<?= h($loc['location_name']) ?>" maxlength="100"
                           placeholder="e.g. Rack A, Row 1, Shelf 1">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['ACTIVE','INACTIVE','RESERVED'] as $s): ?>
                        <option value="<?= $s ?>" <?= $loc['status'] === $s ? 'selected' : '' ?>>
                            <?= $s ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Capacity (Cartons)</label>
                    <input type="number" name="capacity_cartons" class="form-control"
                           value="<?= h($loc['capacity_cartons']) ?>" min="0"
                           placeholder="Leave blank for unlimited">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Notes</label>
                    <input type="text" name="notes" class="form-control"
                           value="<?= h($loc['notes']) ?>" maxlength="255">
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
END_OF_FILE;

// ── modules/locations/action.php ──────────────────────────────────────────
$files['modules/locations/action.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

switch ($action) {

    case 'create_location':
        requirePermission('locations', 'create');
        $locCode = trim($_POST['location_code'] ?? '');
        if ($locCode === '') {
            setFlash('error', 'Location code is required.');
            redirect('/modules/locations/form.php');
        }
        $stmt = $db->prepare(
            "INSERT INTO warehouse_locations
             (zone_id, location_code, location_name, status, capacity_cartons, notes, created_by)
             VALUES (:zi, :lc, :ln, :st, :cap, :nt, :cb)"
        );
        $stmt->execute([
            'zi'  => (int)($_POST['zone_id'] ?? 0),
            'lc'  => $locCode,
            'ln'  => trim($_POST['location_name'] ?? '') ?: null,
            'st'  => $_POST['status'] ?? 'ACTIVE',
            'cap' => $_POST['capacity_cartons'] !== '' ? (int)$_POST['capacity_cartons'] : null,
            'nt'  => trim($_POST['notes'] ?? '') ?: null,
            'cb'  => $_SESSION['user_id'] ?? null,
        ]);
        logActivity('CREATE', 'warehouse_locations', (int)$db->lastInsertId(), $locCode);
        setFlash('success', 'Location created.');
        redirect('/modules/locations/list.php');
        break;

    case 'update_location':
        requirePermission('locations', 'edit');
        $id      = (int)($_POST['id'] ?? 0);
        $locCode = trim($_POST['location_code'] ?? '');
        if ($id <= 0 || $locCode === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/locations/list.php');
        }
        $stmt = $db->prepare(
            "UPDATE warehouse_locations
             SET zone_id=:zi, location_code=:lc, location_name=:ln,
                 status=:st, capacity_cartons=:cap, notes=:nt
             WHERE location_id=:id"
        );
        $stmt->execute([
            'zi'  => (int)($_POST['zone_id'] ?? 0),
            'lc'  => $locCode,
            'ln'  => trim($_POST['location_name'] ?? '') ?: null,
            'st'  => $_POST['status'] ?? 'ACTIVE',
            'cap' => $_POST['capacity_cartons'] !== '' ? (int)$_POST['capacity_cartons'] : null,
            'nt'  => trim($_POST['notes'] ?? '') ?: null,
            'id'  => $id,
        ]);
        logActivity('UPDATE', 'warehouse_locations', $id, $locCode);
        setFlash('success', 'Location updated.');
        redirect('/modules/locations/list.php');
        break;

    case 'delete_location':
        requirePermission('locations', 'delete');
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid location.');
            redirect('/modules/locations/list.php');
        }
        $db->prepare("DELETE FROM warehouse_locations WHERE location_id = ?")->execute([$id]);
        logActivity('DELETE', 'warehouse_locations', $id, null);
        setFlash('success', 'Location deleted.');
        redirect('/modules/locations/list.php');
        break;

    case 'create_zone':
        requirePermission('locations', 'create');
        $zoneCode = trim($_POST['zone_code'] ?? '');
        $zoneName = trim($_POST['zone_name'] ?? '');
        if ($zoneCode === '' || $zoneName === '') {
            setFlash('error', 'Zone code and name are required.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $stmt = $db->prepare(
            "INSERT INTO warehouse_zones (zone_code, zone_name, zone_type, notes, is_active, created_by)
             VALUES (:zc, :zn, :zt, :nt, 1, :cb)"
        );
        $stmt->execute([
            'zc' => $zoneCode,
            'zn' => $zoneName,
            'zt' => $_POST['zone_type'] ?? 'STORAGE',
            'nt' => trim($_POST['notes'] ?? '') ?: null,
            'cb' => $_SESSION['user_id'] ?? null,
        ]);
        logActivity('CREATE', 'warehouse_zones', (int)$db->lastInsertId(), $zoneCode);
        setFlash('success', 'Zone created.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    case 'update_zone':
        requirePermission('locations', 'edit');
        $zoneId   = (int)($_POST['zone_id'] ?? 0);
        $zoneCode = trim($_POST['zone_code'] ?? '');
        $zoneName = trim($_POST['zone_name'] ?? '');
        if ($zoneId <= 0 || $zoneCode === '' || $zoneName === '') {
            setFlash('error', 'Invalid request.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $stmt = $db->prepare(
            "UPDATE warehouse_zones
             SET zone_code=:zc, zone_name=:zn, zone_type=:zt, notes=:nt, is_active=:ia
             WHERE zone_id=:id"
        );
        $stmt->execute([
            'zc' => $zoneCode,
            'zn' => $zoneName,
            'zt' => $_POST['zone_type'] ?? 'STORAGE',
            'nt' => trim($_POST['notes'] ?? '') ?: null,
            'ia' => isset($_POST['is_active']) ? 1 : 0,
            'id' => $zoneId,
        ]);
        logActivity('UPDATE', 'warehouse_zones', $zoneId, $zoneCode);
        setFlash('success', 'Zone updated.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    case 'delete_zone':
        requirePermission('locations', 'delete');
        $zoneId = (int)($_POST['zone_id'] ?? 0);
        if ($zoneId <= 0) {
            setFlash('error', 'Invalid zone.');
            redirect('/modules/locations/form.php?zone_manage=1');
        }
        $db->prepare("DELETE FROM warehouse_zones WHERE zone_id = ?")->execute([$zoneId]);
        logActivity('DELETE', 'warehouse_zones', $zoneId, null);
        setFlash('success', 'Zone deleted.');
        redirect('/modules/locations/form.php?zone_manage=1');
        break;

    default:
        redirect('/modules/locations/list.php');
}
END_OF_FILE;

// ── modules/stock/list.php ────────────────────────────────────────────────
$files['modules/stock/list.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'view');

$db = getDB();

$filterZone = (int)($_GET['zone_id']    ?? 0);
$filterItem = trim($_GET['item_code']   ?? '');
$filterPo   = trim($_GET['po_number']   ?? '');

$wheres = [];
$params = [];

if ($filterZone) {
    $wheres[] = 'wz.zone_id = ?';
    $params[] = $filterZone;
}
if ($filterItem !== '') {
    $wheres[] = 'i.sku_code LIKE ?';
    $params[] = '%' . $filterItem . '%';
}
if ($filterPo !== '') {
    $wheres[] = 'po.po_number LIKE ?';
    $params[] = '%' . $filterPo . '%';
}

$where = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

$stmt = $db->prepare(
    "SELECT sp.*,
            wl.location_code,
            wz.zone_code, wz.zone_name,
            i.sku_code, i.item_name,
            po.po_number,
            u.full_name AS updated_by_name
     FROM stock_positions sp
     JOIN warehouse_locations wl  ON wl.location_id = sp.location_id
     JOIN warehouse_zones wz      ON wz.zone_id     = wl.zone_id
     LEFT JOIN items i            ON i.item_id      = sp.item_id
     LEFT JOIN purchase_orders po ON po.po_id       = sp.po_id
     LEFT JOIN users u            ON u.user_id      = sp.updated_by
     $where
     ORDER BY wz.zone_code, wl.location_code, i.sku_code"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$zones = $db->query(
    "SELECT zone_id, zone_code, zone_name FROM warehouse_zones ORDER BY zone_code"
)->fetchAll();

$pageTitle = 'Stock Positions';
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
        <h5 class="mb-0">Stock Positions</h5>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <div class="d-flex gap-2">
        <?php if (can('stock', 'create')): ?>
        <a href="<?= BASE_URL ?>/modules/stock/move.php" class="btn btn-dark btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i>Record Movement
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/modules/stock/ledger.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text me-1"></i>Ledger
        </a>
    </div>
</div>

<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label small mb-1">Zone</label>
                <select name="zone_id" class="form-select form-select-sm">
                    <option value="">All Zones</option>
                    <?php foreach ($zones as $z): ?>
                    <option value="<?= (int)$z['zone_id'] ?>"
                        <?= $filterZone === (int)$z['zone_id'] ? 'selected' : '' ?>>
                        <?= h($z['zone_code']) ?> — <?= h($z['zone_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm"
                       value="<?= h($filterItem) ?>" placeholder="Search item code...">
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1">PO Number</label>
                <input type="text" name="po_number" class="form-control form-control-sm"
                       value="<?= h($filterPo) ?>" placeholder="Search PO #...">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-dark btn-sm me-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="list.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Location</th>
                    <th>Zone</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>PO #</th>
                    <th class="text-end">Cartons</th>
                    <th class="text-end">Pieces</th>
                    <th>Last Updated</th>
                    <th>Updated By</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td class="fw-semibold">
                    <a href="<?= BASE_URL ?>/modules/locations/list.php" class="text-decoration-none">
                        <?= h($r['location_code']) ?>
                    </a>
                </td>
                <td><small class="text-muted"><?= h($r['zone_code']) ?></small></td>
                <td><?= h($r['sku_code'] ?? '—') ?></td>
                <td class="small text-muted"><?= h($r['item_name'] ?? '') ?></td>
                <td><?= h($r['po_number'] ?? '—') ?></td>
                <td class="text-end fw-semibold"><?= fmtNum($r['qty_cartons']) ?></td>
                <td class="text-end"><?= fmtNum($r['qty_pieces']) ?></td>
                <td class="small"><?= $r['updated_at'] ? fmtDate($r['updated_at']) : '—' ?></td>
                <td class="small text-muted"><?= h($r['updated_by_name'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    No stock positions found.
                    <?php if (can('stock', 'create')): ?>
                    <a href="<?= BASE_URL ?>/modules/stock/move.php">Record a PUTAWAY</a> to add stock.
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/stock/move.php ────────────────────────────────────────────────
$files['modules/stock/move.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'create');

$db = getDB();

$locations = $db->query(
    "SELECT wl.location_id, wl.location_code, wl.location_name, wz.zone_code
     FROM warehouse_locations wl
     JOIN warehouse_zones wz ON wz.zone_id = wl.zone_id
     WHERE wl.status = 'ACTIVE'
     ORDER BY wz.zone_code, wl.location_code"
)->fetchAll();

$items = $db->query(
    "SELECT item_id, sku_code, item_name FROM items ORDER BY sku_code"
)->fetchAll();

$pos = $db->query(
    "SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number"
)->fetchAll();

$pageTitle = 'Record Stock Movement';
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
    <h5 class="mb-0">Record Stock Movement</h5>
</div>

<div class="card border-0 shadow-sm" style="max-width:720px">
    <div class="card-body">
        <form method="post" action="action.php" id="moveForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="move">

            <div class="mb-4">
                <label class="form-label fw-semibold">Movement Type <span class="text-danger">*</span></label>
                <div class="d-flex gap-3 flex-wrap">
                    <?php
                    $typeDescriptions = [
                        'PUTAWAY'    => 'Incoming stock to a location',
                        'RELOCATION' => 'Move stock between locations',
                        'PICK'       => 'Remove stock from a location',
                        'ADJUST'     => 'Set exact quantity at a location',
                        'DISPATCH'   => 'Stock leaving the warehouse',
                    ];
                    foreach ($typeDescriptions as $mt => $desc):
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="movement_type"
                               id="mt_<?= $mt ?>" value="<?= $mt ?>"
                               <?= $mt === 'PUTAWAY' ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="mt_<?= $mt ?>">
                            <strong><?= $mt ?></strong>
                            <br><small class="text-muted"><?= $desc ?></small>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6" id="fromLocWrap">
                    <label class="form-label fw-semibold">
                        From Location
                        <span class="text-danger" id="fromLocRequired" style="display:none">*</span>
                    </label>
                    <select name="from_location_id" id="from_location_id" class="form-select">
                        <option value="">— none —</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc['location_id'] ?>">
                            [<?= h($loc['zone_code']) ?>] <?= h($loc['location_code']) ?>
                            <?= $loc['location_name'] ? ' — ' . h($loc['location_name']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" id="toLocWrap">
                    <label class="form-label fw-semibold">
                        To Location
                        <span class="text-danger" id="toLocRequired" style="display:none">*</span>
                    </label>
                    <select name="to_location_id" id="to_location_id" class="form-select">
                        <option value="">— none —</option>
                        <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc['location_id'] ?>">
                            [<?= h($loc['zone_code']) ?>] <?= h($loc['location_code']) ?>
                            <?= $loc['location_name'] ? ' — ' . h($loc['location_name']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Item <span class="text-danger">*</span></label>
                <select name="item_id" class="form-select" required>
                    <option value="">— select item —</option>
                    <?php foreach ($items as $item): ?>
                    <option value="<?= (int)$item['item_id'] ?>">
                        <?= h($item['sku_code']) ?>
                        <?= $item['item_name'] ? ' — ' . h($item['item_name']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Purchase Order <span class="text-muted">(optional)</span></label>
                <select name="po_id" class="form-select">
                    <option value="">— no PO —</option>
                    <?php foreach ($pos as $po): ?>
                    <option value="<?= (int)$po['po_id'] ?>">
                        <?= h($po['po_number']) ?>
                        <?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Qty Cartons <span class="text-danger">*</span></label>
                    <input type="number" name="qty_cartons" class="form-control"
                           value="0" min="0" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Qty Pieces</label>
                    <input type="number" name="qty_pieces" class="form-control"
                           value="0" min="0">
                </div>
            </div>

            <div id="adjustBanner" class="alert alert-info py-2 small mb-3" style="display:none">
                <i class="bi bi-info-circle me-1"></i>
                <strong>ADJUST</strong> sets the <em>exact</em> quantity for the selected
                location + item + PO. Use this to correct discrepancies found during stock take.
                The movement is still recorded in the ledger.
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Reference Document</label>
                <input type="text" name="reference_doc" class="form-control"
                       placeholder="e.g. GRN-001, DR-2026-001" maxlength="100">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <input type="text" name="notes" class="form-control" maxlength="255">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark">
                    <i class="bi bi-check-lg me-1"></i>Record Movement
                </button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var rules = {
        PUTAWAY:    { from: { vis: false, req: false }, to: { vis: true,  req: true  } },
        RELOCATION: { from: { vis: true,  req: true  }, to: { vis: true,  req: true  } },
        PICK:       { from: { vis: true,  req: true  }, to: { vis: false, req: false } },
        ADJUST:     { from: { vis: true,  req: true  }, to: { vis: false, req: false } },
        DISPATCH:   { from: { vis: true,  req: true  }, to: { vis: false, req: false } }
    };

    function applyRules(type) {
        var r   = rules[type] || rules.PUTAWAY;
        var fw  = document.getElementById('fromLocWrap');
        var tw  = document.getElementById('toLocWrap');
        var fs  = document.getElementById('from_location_id');
        var ts  = document.getElementById('to_location_id');
        var frq = document.getElementById('fromLocRequired');
        var trq = document.getElementById('toLocRequired');
        var adj = document.getElementById('adjustBanner');

        fw.style.opacity = r.from.vis ? '1' : '0.4';
        tw.style.opacity = r.to.vis   ? '1' : '0.4';
        fs.required = r.from.req;
        ts.required = r.to.req;
        frq.style.display = r.from.req ? 'inline' : 'none';
        trq.style.display = r.to.req   ? 'inline' : 'none';
        if (!r.from.vis) { fs.value = ''; }
        if (!r.to.vis)   { ts.value = ''; }
        adj.style.display = (type === 'ADJUST') ? 'block' : 'none';
    }

    document.querySelectorAll('input[name="movement_type"]').forEach(function (radio) {
        radio.addEventListener('change', function () { applyRules(this.value); });
    });

    var checked = document.querySelector('input[name="movement_type"]:checked');
    if (checked) { applyRules(checked.value); }
}());
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/stock/action.php ──────────────────────────────────────────────
$files['modules/stock/action.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';

if ($action === 'move') {
    requirePermission('stock', 'create');

    $movType    = $_POST['movement_type']    ?? '';
    $fromLocId  = (int)($_POST['from_location_id'] ?? 0) ?: null;
    $toLocId    = (int)($_POST['to_location_id']   ?? 0) ?: null;
    $itemId     = (int)($_POST['item_id']           ?? 0) ?: null;
    $poId       = (int)($_POST['po_id']             ?? 0) ?: null;
    $qtyCartons = (int)($_POST['qty_cartons']       ?? 0);
    $qtyPieces  = (int)($_POST['qty_pieces']        ?? 0);
    $refDoc     = trim($_POST['reference_doc']      ?? '') ?: null;
    $notes      = trim($_POST['notes']              ?? '') ?: null;
    $userId     = $_SESSION['user_id'] ?? null;

    $validTypes = ['PUTAWAY', 'RELOCATION', 'PICK', 'ADJUST', 'DISPATCH'];
    if (!in_array($movType, $validTypes, true)) {
        setFlash('error', 'Invalid movement type.');
        redirect('/modules/stock/move.php');
    }

    if ($itemId === null) {
        setFlash('error', 'Item is required.');
        redirect('/modules/stock/move.php');
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            "INSERT INTO stock_movements
             (movement_type, from_location_id, to_location_id, item_id, po_id,
              qty_cartons, qty_pieces, reference_doc, notes, created_by)
             VALUES (:mt, :fl, :tl, :ii, :pi, :qc, :qp, :rd, :nt, :cb)"
        );
        $stmt->execute([
            'mt' => $movType,
            'fl' => $fromLocId,
            'tl' => $toLocId,
            'ii' => $itemId,
            'pi' => $poId,
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'rd' => $refDoc,
            'nt' => $notes,
            'cb' => $userId,
        ]);
        $movId = (int)$db->lastInsertId();

        if ($movType === 'ADJUST') {
            if ($fromLocId !== null) {
                setPositionQty($db, $fromLocId, $itemId, $poId, $qtyCartons, $qtyPieces, $notes, $userId);
            }
        } else {
            if ($fromLocId !== null) {
                adjustPosition($db, $fromLocId, $itemId, $poId, -$qtyCartons, -$qtyPieces, $userId);
            }
            if ($toLocId !== null) {
                adjustPosition($db, $toLocId, $itemId, $poId, $qtyCartons, $qtyPieces, $userId);
            }
        }

        $db->commit();

        logActivity('CREATE', 'stock_movements', $movId, $movType . ($refDoc ? ' ' . $refDoc : ''));
        setFlash('success', 'Movement recorded successfully.');

    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Error recording movement: ' . $e->getMessage());
    }

    redirect('/modules/stock/list.php');

} elseif ($action === 'delete_movement') {
    requirePermission('stock', 'delete');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('error', 'Invalid movement ID.');
        redirect('/modules/stock/ledger.php');
    }
    $db->prepare("DELETE FROM stock_movements WHERE movement_id = ?")->execute([$id]);
    logActivity('DELETE', 'stock_movements', $id, null);
    setFlash('success', 'Movement record deleted. Note: stock positions were NOT reversed.');
    redirect('/modules/stock/ledger.php');

} else {
    redirect('/modules/stock/list.php');
}

function findPosition(PDO $db, int $locationId, ?int $itemId, ?int $poId): ?array
{
    $stmt = $db->prepare(
        "SELECT position_id, qty_cartons, qty_pieces
         FROM stock_positions
         WHERE location_id = :li
           AND (item_id = :ii OR (item_id IS NULL AND :ii2 IS NULL))
           AND (po_id   = :pi OR (po_id   IS NULL AND :pi2 IS NULL))
         LIMIT 1"
    );
    $stmt->execute([
        'li'  => $locationId,
        'ii'  => $itemId,
        'ii2' => $itemId,
        'pi'  => $poId,
        'pi2' => $poId,
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function adjustPosition(
    PDO $db,
    int $locationId,
    ?int $itemId,
    ?int $poId,
    int $deltaCartons,
    int $deltaPieces,
    ?int $userId
): void {
    $existing = findPosition($db, $locationId, $itemId, $poId);

    if ($existing) {
        $newCartons = $existing['qty_cartons'] + $deltaCartons;
        $newPieces  = $existing['qty_pieces']  + $deltaPieces;

        if ($newCartons <= 0 && $newPieces <= 0) {
            $db->prepare("DELETE FROM stock_positions WHERE position_id = ?")
               ->execute([$existing['position_id']]);
        } else {
            $db->prepare(
                "UPDATE stock_positions
                 SET qty_cartons = :qc, qty_pieces = :qp, updated_by = :ub
                 WHERE position_id = :id"
            )->execute([
                'qc' => max(0, $newCartons),
                'qp' => max(0, $newPieces),
                'ub' => $userId,
                'id' => $existing['position_id'],
            ]);
        }
    } else {
        if ($deltaCartons > 0 || $deltaPieces > 0) {
            $db->prepare(
                "INSERT INTO stock_positions
                 (location_id, item_id, po_id, qty_cartons, qty_pieces, updated_by)
                 VALUES (:li, :ii, :pi, :qc, :qp, :ub)"
            )->execute([
                'li' => $locationId,
                'ii' => $itemId,
                'pi' => $poId,
                'qc' => $deltaCartons,
                'qp' => $deltaPieces,
                'ub' => $userId,
            ]);
        }
    }
}

function setPositionQty(
    PDO $db,
    int $locationId,
    ?int $itemId,
    ?int $poId,
    int $qtyCartons,
    int $qtyPieces,
    ?string $notes,
    ?int $userId
): void {
    $existing = findPosition($db, $locationId, $itemId, $poId);

    if ($qtyCartons <= 0 && $qtyPieces <= 0) {
        if ($existing) {
            $db->prepare("DELETE FROM stock_positions WHERE position_id = ?")
               ->execute([$existing['position_id']]);
        }
        return;
    }

    if ($existing) {
        $db->prepare(
            "UPDATE stock_positions
             SET qty_cartons = :qc, qty_pieces = :qp, notes = :nt, updated_by = :ub
             WHERE position_id = :id"
        )->execute([
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'nt' => $notes,
            'ub' => $userId,
            'id' => $existing['position_id'],
        ]);
    } else {
        $db->prepare(
            "INSERT INTO stock_positions
             (location_id, item_id, po_id, qty_cartons, qty_pieces, notes, updated_by)
             VALUES (:li, :ii, :pi, :qc, :qp, :nt, :ub)"
        )->execute([
            'li' => $locationId,
            'ii' => $itemId,
            'pi' => $poId,
            'qc' => $qtyCartons,
            'qp' => $qtyPieces,
            'nt' => $notes,
            'ub' => $userId,
        ]);
    }
}
END_OF_FILE;

// ── modules/stock/ledger.php ──────────────────────────────────────────────
$files['modules/stock/ledger.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'view');

$db = getDB();

$perPage = 50;
$page    = max(1, (int)($_GET['page'] ?? 1));

$filterType     = $_GET['movement_type'] ?? '';
$filterItem     = trim($_GET['item_code']    ?? '');
$filterPo       = trim($_GET['po_number']    ?? '');
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to']   ?? '';

$wheres = [];
$params = [];

if ($filterType) {
    $wheres[] = 'sm.movement_type = ?';
    $params[] = $filterType;
}
if ($filterItem !== '') {
    $wheres[] = 'i.sku_code LIKE ?';
    $params[] = '%' . $filterItem . '%';
}
if ($filterPo !== '') {
    $wheres[] = 'po.po_number LIKE ?';
    $params[] = '%' . $filterPo . '%';
}
if ($filterDateFrom !== '') {
    $wheres[] = 'DATE(sm.created_at) >= ?';
    $params[] = $filterDateFrom;
}
if ($filterDateTo !== '') {
    $wheres[] = 'DATE(sm.created_at) <= ?';
    $params[] = $filterDateTo;
}

$where = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

$countStmt = $db->prepare(
    "SELECT COUNT(*)
     FROM stock_movements sm
     LEFT JOIN items i            ON i.item_id  = sm.item_id
     LEFT JOIN purchase_orders po ON po.po_id   = sm.po_id
     $where"
);
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $db->prepare(
    "SELECT sm.*,
            fl.location_code AS from_code,
            tl.location_code AS to_code,
            i.sku_code,
            po.po_number,
            u.full_name AS created_by_name
     FROM stock_movements sm
     LEFT JOIN warehouse_locations fl ON fl.location_id = sm.from_location_id
     LEFT JOIN warehouse_locations tl ON tl.location_id = sm.to_location_id
     LEFT JOIN items i                ON i.item_id      = sm.item_id
     LEFT JOIN purchase_orders po     ON po.po_id       = sm.po_id
     LEFT JOIN users u                ON u.user_id      = sm.created_by
     $where
     ORDER BY sm.movement_id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Stock Movement Ledger';
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
        <h5 class="mb-0">Stock Movement Ledger</h5>
        <span class="badge bg-secondary"><?= $totalRows ?> total</span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/modules/stock/list.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-boxes me-1"></i>Stock Positions
        </a>
        <?php if (can('stock', 'create')): ?>
        <a href="move.php" class="btn btn-dark btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Record Movement
        </a>
        <?php endif; ?>
    </div>
</div>

<form method="get" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-2">
                <label class="form-label small mb-1">Type</label>
                <select name="movement_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (['PUTAWAY','RELOCATION','PICK','ADJUST','DISPATCH'] as $mt): ?>
                    <option value="<?= $mt ?>" <?= $filterType === $mt ? 'selected' : '' ?>><?= $mt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm"
                       value="<?= h($filterItem) ?>" placeholder="Item code...">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">PO Number</label>
                <input type="text" name="po_number" class="form-control form-control-sm"
                       value="<?= h($filterPo) ?>" placeholder="PO #...">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= h($filterDateFrom) ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= h($filterDateTo) ?>">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-dark btn-sm me-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="ledger.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date / Time</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Item</th>
                    <th>PO #</th>
                    <th class="text-end">Cartons</th>
                    <th class="text-end">Pieces</th>
                    <th>Ref Doc</th>
                    <th>Notes</th>
                    <th>User</th>
                    <?php if (can('stock', 'delete')): ?><th></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $typeBadge = match($r['movement_type']) {
                    'PUTAWAY'    => 'primary',
                    'RELOCATION' => 'info',
                    'PICK'       => 'warning',
                    'ADJUST'     => 'secondary',
                    'DISPATCH'   => 'success',
                    default      => 'light',
                };
            ?>
            <tr>
                <td class="text-muted"><?= (int)$r['movement_id'] ?></td>
                <td><?= h($r['created_at']) ?></td>
                <td><span class="badge bg-<?= $typeBadge ?>"><?= h($r['movement_type']) ?></span></td>
                <td class="fw-semibold"><?= h($r['from_code'] ?? '—') ?></td>
                <td class="fw-semibold"><?= h($r['to_code']   ?? '—') ?></td>
                <td><?= h($r['sku_code'] ?? '—') ?></td>
                <td><?= h($r['po_number'] ?? '—') ?></td>
                <td class="text-end"><?= fmtNum($r['qty_cartons']) ?></td>
                <td class="text-end"><?= fmtNum($r['qty_pieces']) ?></td>
                <td class="text-muted"><?= h($r['reference_doc'] ?? '') ?></td>
                <td class="text-muted"><?= h($r['notes'] ?? '') ?></td>
                <td class="text-muted"><?= h($r['created_by_name'] ?? '') ?></td>
                <?php if (can('stock', 'delete')): ?>
                <td>
                    <form method="post" action="action.php" class="d-inline"
                          onsubmit="return confirm('Delete movement #<?= (int)$r['movement_id'] ?>?\nStock positions will NOT be reversed.')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="delete_movement">
                        <input type="hidden" name="id" value="<?= (int)$r['movement_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="13" class="text-center text-muted py-4">No movements found.</td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1):
    $qs = http_build_query(array_filter([
        'movement_type' => $filterType,
        'item_code'     => $filterItem,
        'po_number'     => $filterPo,
        'date_from'     => $filterDateFrom,
        'date_to'       => $filterDateTo,
    ]));
    $base = 'ledger.php?' . ($qs ? $qs . '&' : '');
?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page - 1 ?>">&laquo; Prev</a>
        </li>
        <?php
        $start = max(1, $page - 3);
        $end   = min($totalPages, $page + 3);
        if ($start > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= $base ?>page=1">1</a></li>
            <?php if ($start > 2): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= $base ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
            </li>
        <?php endif; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $base ?>page=<?= $page + 1 ?>">Next &raquo;</a>
        </li>
    </ul>
    <p class="text-center text-muted small">
        Page <?= $page ?> of <?= $totalPages ?> &mdash; <?= $totalRows ?> records
    </p>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── includes/header.php ───────────────────────────────────────────────────
$files['includes/header.php'] = <<<'END_OF_FILE'
<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$companyName = setting('company_name', 'Your Company');
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));

$canOperations = can('priorities','view') || can('purchase_orders','view') || can('receiving','view') || can('qc','view') || can('putaway','view') || can('gatepass','view') || can('rss','view') || can('subcons','view') || can('tickets','view') || can('output','view') || can('fob_shipment','view') || can('locations','view') || can('stock','view');
$canCatalog = can('items','view') || can('parties','view');
$canAdministration = can('users','view') || can('alerts','view') || can('backup','view') || can('performance','edit') || can('api','manage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> &middot; <?= h($companyName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f1f5f9; font-family: -apple-system, "Segoe UI", Roboto, sans-serif; }
.app-wrapper { min-height: 100vh; }
.icon-sidebar { width: 64px; background: #1e293b; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; padding: 12px 0; position: relative; z-index: 1000; }
.icon-sidebar .brand-icon { color: #fff; font-size: 24px; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid #334155; width: 48px; text-align: center; }
.nav-icon-wrap { position: relative; width: 100%; }
.nav-icon { display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; margin: 2px auto; border-radius: 10px; color: #94a3b8; font-size: 20px; text-decoration: none; }
.nav-icon:hover, .nav-icon.active { background: #334155; color: #fff; }
.flyout { display: none; position: absolute; left: 64px; top: 0; min-width: 220px; background: #1e293b; color: #e2e8f0; border-radius: 0 10px 10px 0; box-shadow: 4px 0 18px rgba(0,0,0,0.25); padding: 16px 0; z-index: 1001; }
.nav-icon-wrap:hover .flyout { display: flex; }
.flyout-title { color: #fff; font-weight: 700; font-size: 15px; padding: 0 20px 12px; margin-bottom: 4px; border-bottom: 1px solid #334155; width: 100%; }
.flyout-cols { display: flex; width: 100%; }
.flyout-col { min-width: 200px; padding: 4px 0; }
.flyout a { display: block; color: #cbd5e1; text-decoration: none; padding: 8px 20px; font-size: 14px; white-space: nowrap; }
.flyout a:hover { background: #334155; color: #fff; }
.flyout a.active { color: #fff; font-weight: 600; }
.app-main { flex: 1; min-width: 0; }
.topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 10px 20px; }
.kpi-card { background: #fff; border-radius: 10px; padding: 16px; border-left: 4px solid #1e3a5f; }
.kpi-card .kpi-label { font-size: 11px; text-transform: uppercase; color: #64748b; }
.kpi-card .kpi-value { font-size: 26px; font-weight: bold; }
</style>
</head>
<body>
<div class="app-wrapper d-flex">
<nav class="icon-sidebar">
<div class="brand-icon" title="<?= h($companyName) ?>"><i class="bi bi-box-seam"></i></div>

<?php if (can('dashboard','view')): ?>
<div class="nav-icon-wrap">
<a href="<?= BASE_URL ?>/index.php" class="nav-icon <?= $currentScript==='index.php'?'active':'' ?>" title="Dashboard"><i class="bi bi-speedometer2"></i></a>
</div>
<?php endif; ?>

<?php if ($canOperations): ?>
<div class="nav-icon-wrap">
<a href="#" class="nav-icon <?= $currentDir==='priorities'||$currentDir==='po'||$currentDir==='receiving'||$currentDir==='qc'||$currentDir==='putaway'||$currentDir==='gatepass'||$currentDir==='rss'||$currentDir==='subcon'||$currentDir==='tickets'||$currentDir==='output'||$currentDir==='fob'||$currentDir==='locations'||$currentDir==='stock'?'active':'' ?>" title="Operations"><i class="bi bi-gear-wide-connected"></i></a>
<div class="flyout">
<div class="flyout-title">Operations</div>
<div class="flyout-cols">
<div class="flyout-col">
<?php if (can('priorities','view')): ?><a href="<?= BASE_URL ?>/modules/priorities/list.php" class="<?= navActive('priorities','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-list-ol me-2"></i>Priority Queues</a><?php endif; ?>
<?php if (can('purchase_orders','view')): ?><a href="<?= BASE_URL ?>/modules/po/list.php" class="<?= navActive('po','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-file-earmark-text me-2"></i>Purchase Orders</a><?php endif; ?>
<?php if (can('receiving','view')): ?><a href="<?= BASE_URL ?>/modules/receiving/list.php" class="<?= navActive('receiving','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-box-arrow-in-down me-2"></i>Receiving / GRN</a><?php endif; ?>
<?php if (can('qc','view')): ?><a href="<?= BASE_URL ?>/modules/qc/list.php" class="<?= navActive('qc','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-clipboard-check me-2"></i>QC Inspection</a><?php endif; ?>
</div>
<div class="flyout-col">
<?php if (can('putaway','view')): ?><a href="<?= BASE_URL ?>/modules/putaway/list.php" class="<?= navActive('putaway','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-box-seam me-2"></i>Putaway</a><?php endif; ?>
<?php if (can('gatepass','view')): ?><a href="<?= BASE_URL ?>/modules/gatepass/list.php" class="<?= navActive('gatepass','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-truck me-2"></i>Gatepass</a><?php endif; ?>
<?php if (can('rss','view')): ?><a href="<?= BASE_URL ?>/modules/rss/list.php" class="<?= navActive('rss','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-arrow-repeat me-2"></i>RSS / Other-Process</a><?php endif; ?>
<?php if (can('subcons','view')): ?><a href="<?= BASE_URL ?>/modules/subcon/list.php" class="<?= navActive('subcon','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-buildings me-2"></i>Subcontractors</a><?php endif; ?>
</div>
<div class="flyout-col">
<?php if (can('tickets','view')): ?><a href="<?= BASE_URL ?>/modules/tickets/list.php" class="<?= navActive('tickets','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-ticket-perforated me-2"></i>Tickets</a><?php endif; ?>
<?php if (can('output','view')): ?><a href="<?= BASE_URL ?>/modules/output/list.php" class="<?= navActive('output','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-check2-circle me-2"></i>Output Records</a><?php endif; ?>
<?php if (can('fob_shipment','view')): ?><a href="<?= BASE_URL ?>/modules/fob/list.php" class="<?= navActive('fob','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-ship me-2"></i>FOB Shipment</a><?php endif; ?>
</div>
<div class="flyout-col">
<?php if (can('locations','view')): ?><a href="<?= BASE_URL ?>/modules/locations/list.php" class="<?= navActive('locations','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-geo me-2"></i>Locations</a><?php endif; ?>
<?php if (can('stock','view')): ?><a href="<?= BASE_URL ?>/modules/stock/list.php" class="<?= navActive('stock','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-boxes me-2"></i>Stock Positions</a><?php endif; ?>
<?php if (can('stock','view')): ?><a href="<?= BASE_URL ?>/modules/stock/ledger.php" class="<?= navActive('stock','ledger.php',$currentDir,$currentScript) ?>"><i class="bi bi-journal-text me-2"></i>Movement Ledger</a><?php endif; ?>
<?php if (can('stock','create')): ?><a href="<?= BASE_URL ?>/modules/stock/move.php" class="<?= navActive('stock','move.php',$currentDir,$currentScript) ?>"><i class="bi bi-arrow-left-right me-2"></i>Record Movement</a><?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>

<?php if (can('reports','view')): ?>
<div class="nav-icon-wrap">
<a href="#" class="nav-icon <?= navActive('reports','wip_status.php',$currentDir,$currentScript) ?>" title="Reports"><i class="bi bi-bar-chart"></i></a>
<div class="flyout">
<div class="flyout-title">Reports</div>
<div class="flyout-cols">
<div class="flyout-col">
<a href="<?= BASE_URL ?>/modules/reports/wip_status.php" class="<?= navActive('reports','wip_status.php',$currentDir,$currentScript) ?>"><i class="bi bi-bar-chart me-2"></i>WIP Status</a>
</div>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($canCatalog): ?>
<div class="nav-icon-wrap">
<a href="#" class="nav-icon <?= $currentDir==='items'||$currentDir==='parties'?'active':'' ?>" title="Catalog"><i class="bi bi-tags"></i></a>
<div class="flyout">
<div class="flyout-title">Catalog</div>
<div class="flyout-cols">
<div class="flyout-col">
<?php if (can('items','view')): ?><a href="<?= BASE_URL ?>/modules/items/list.php" class="<?= navActive('items','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-tags me-2"></i>Items / SKU Master</a><?php endif; ?>
<?php if (can('parties','view')): ?><a href="<?= BASE_URL ?>/modules/parties/list.php" class="<?= navActive('parties','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-geo-alt me-2"></i>Parties / Destinations</a><?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($canAdministration): ?>
<div class="nav-icon-wrap">
<a href="#" class="nav-icon <?= $currentDir==='admin'||$currentDir==='alerts'?'active':'' ?>" title="Administration"><i class="bi bi-shield-lock"></i></a>
<div class="flyout">
<div class="flyout-title">Administration</div>
<div class="flyout-cols">
<div class="flyout-col">
<?php if (can('users','view')): ?><a href="<?= BASE_URL ?>/modules/admin/users.php" class="<?= navActive('admin','users.php',$currentDir,$currentScript) ?>"><i class="bi bi-people me-2"></i>Users</a><?php endif; ?>
<?php if (can('alerts','view')): ?><a href="<?= BASE_URL ?>/modules/alerts/list.php" class="<?= navActive('alerts','list.php',$currentDir,$currentScript) ?>"><i class="bi bi-bell me-2"></i>Alerts</a><?php endif; ?>
<?php if (can('backup','view')): ?><a href="<?= BASE_URL ?>/modules/admin/backup.php" class="<?= navActive('admin','backup.php',$currentDir,$currentScript) ?>"><i class="bi bi-hdd-network me-2"></i>Backup &amp; Restore</a><?php endif; ?>
<?php if (can('performance','edit')): ?><a href="<?= BASE_URL ?>/modules/admin/criteria.php" class="<?= navActive('admin','criteria.php',$currentDir,$currentScript) ?>"><i class="bi bi-star me-2"></i>Performance Criteria</a><?php endif; ?>
<?php if (can('api','manage')): ?><a href="<?= BASE_URL ?>/modules/admin/api_tokens.php" class="<?= navActive('admin','api_tokens.php',$currentDir,$currentScript) ?>"><i class="bi bi-key me-2"></i>API Tokens</a><?php endif; ?>
<?php if (can('users','view')): ?><a href="<?= BASE_URL ?>/modules/admin/import.php" class="<?= navActive('admin','import.php',$currentDir,$currentScript) ?>"><i class="bi bi-upload me-2"></i>Import CSV</a><?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>

</nav>
<div class="app-main">
<div class="topbar d-flex justify-content-between align-items-center">
<span class="fw-semibold text-muted"><?= h($pageTitle) ?></span>
<div class="d-flex align-items-center gap-2">
<?php if (can('alerts','view')):
$unreadAlertCount = AlertManager::unreadCount(getDB());
?>
<a href="<?= BASE_URL ?>/modules/alerts/list.php" class="btn btn-sm btn-light position-relative">
<i class="bi bi-bell"></i>
<?php if ($unreadAlertCount > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;"><?= $unreadAlertCount > 99 ? '99+' : $unreadAlertCount ?></span><?php endif; ?>
</a>
<?php endif; ?>
<div class="dropdown">
<button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
<i class="bi bi-person-circle"></i>
<span><?= h($_SESSION['full_name'] ?? '') ?></span>
<span class="badge bg-secondary"><?= h($_SESSION['role_name'] ?? '') ?></span>
</button>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="<?= BASE_URL ?>/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
</ul>
</div>
</div>
</div>
<main class="p-3 p-md-4">
END_OF_FILE;

// ─── Installer logic ───────────────────────────────────────────────────────
$baseDir = __DIR__;
$errors  = [];
$done    = [];

if ($_POST['go'] ?? false) {
    foreach ($files as $relPath => $content) {
        $absPath = $baseDir . '/' . $relPath;
        $dir     = dirname($absPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Could not create directory: $dir";
                continue;
            }
        }

        if (file_put_contents($absPath, $content) === false) {
            $errors[] = "Could not write: $relPath";
        } else {
            $done[] = $relPath;
        }
    }
}

// ─── HTML output ──────────────────────────────────────────────────────────
$pageTitle = 'Module 6 Installer';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h5 class="mb-0"><i class="bi bi-download me-2"></i>Module 6 Installer — Locations &amp; Stock</h5>
</div>

<?php if ($done): ?>
<div class="alert alert-success">
    <strong><?= count($done) ?> file(s) written successfully.</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($done as $f): ?><li><code><?= h($f) ?></code></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong>Errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!$done): ?>
<div class="card border-0 shadow-sm mb-3" style="max-width:680px">
    <div class="card-body">
        <p class="mb-2">This will create or overwrite the following files:</p>
        <ul class="small mb-3">
            <?php foreach (array_keys($files) as $f): ?><li><code><?= h($f) ?></code></li><?php endforeach; ?>
        </ul>
        <div class="alert alert-warning mb-3">
            <strong>Run <code>migration6.sql</code> in phpMyAdmin first</strong> before clicking
            Install — it creates the four new tables and inserts the permissions.
        </div>
        <form method="post">
            <button type="submit" name="go" value="1" class="btn btn-dark"
                    onclick="return confirm('Write all Module 6 files to the server?')">
                <i class="bi bi-play-fill me-1"></i> Install Now
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($done && !$errors): ?>
<div class="alert alert-info">
    <strong>All done. Next steps:</strong>
    <ol class="mb-0 mt-2">
        <li>Confirm <code>migration6.sql</code> ran successfully in phpMyAdmin.</li>
        <li><strong>Delete <code>install6.php</code></strong> from the server root now.</li>
        <li>Open the Operations flyout — you will see <em>Locations</em>, <em>Stock Positions</em>,
            <em>Movement Ledger</em>, and <em>Record Movement</em>.</li>
        <li>Start by creating Zones (Manage Zones), then Locations, then record your first PUTAWAY.</li>
    </ol>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
