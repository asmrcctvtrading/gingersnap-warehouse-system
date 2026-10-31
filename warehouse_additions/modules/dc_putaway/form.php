<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('dc_putaway', 'edit');
    $stDcp = $db->prepare("SELECT * FROM dc_putaways WHERE dcp_id = ?");
    $stDcp->execute([$id]);
    $dcp = $stDcp->fetch();
    if (!$dcp) { setFlash('error','Record not found.'); redirect('/modules/dc_putaway/list.php'); }
    if ($dcp['status'] !== 'PENDING') { setFlash('error','Only PENDING records can be edited.'); redirect('/modules/dc_putaway/view.php?id='.$id); }

    $stItems = $db->prepare("SELECT * FROM dc_putaway_items WHERE dcp_id = ? ORDER BY dcpi_id");
    $stItems->execute([$id]);
    $existingItems = $stItems->fetchAll();
} else {
    requirePermission('dc_putaway', 'create');
    $dcp = ['gp_code'=>'','putaway_date'=>date('Y-m-d'),'putaway_time'=>'','putaway_person'=>'','warehouse_type'=>'LOCAL','dc_id'=>'','gp_id'=>'','notes'=>''];
    $existingItems = [];
}

$hourNow = (int)date('H');

// GP Code lookup
$gpInfo     = null;
$gpItems    = [];
$dcRecord   = null;
$gpCodeGet  = trim($_GET['gp_code'] ?? '');

if ($gpCodeGet !== '') {
    $stGp = $db->prepare("SELECT * FROM gatepasses WHERE gp_code = ?");
    $stGp->execute([$gpCodeGet]);
    $gpInfo = $stGp->fetch();

    if ($gpInfo && $gpInfo['gp_type'] === 'STOCK_TRANSFER') {
        $stGpItems = $db->prepare(
            "SELECT gi.*, i.sku_code, i.item_name
             FROM gatepass_items gi
             LEFT JOIN items i ON i.item_id = gi.item_id
             WHERE gi.gp_id = ?"
        );
        $stGpItems->execute([$gpInfo['gp_id']]);
        $gpItems = $stGpItems->fetchAll();

        if ($gpInfo['po_id']) {
            $stDc = $db->prepare(
                "SELECT dc.* FROM dc_receivings dc
                 LEFT JOIN grn g ON g.grn_id = dc.grn_id
                 WHERE g.po_id = ? AND dc.status IN ('RECEIVED','PUTAWAY_PENDING')
                 LIMIT 1"
            );
            $stDc->execute([$gpInfo['po_id']]);
            $dcRecord = $stDc->fetch();
        }

        if (!$isEdit) {
            $dcp['gp_code'] = $gpInfo['gp_code'];
            $dcp['gp_id']   = $gpInfo['gp_id'];
            if ($dcRecord) {
                $dcp['dc_id'] = $dcRecord['dc_id'];
                $typeMap = ['LOCAL'=>'LOCAL','EXPORT'=>'EXPORT','SMART_BUYS_DDF'=>'SMART_BUYS'];
                $dcp['warehouse_type'] = $typeMap[$dcRecord['dc_type']] ?? 'LOCAL';
            }
        }
    }
}

// DC Receiving dropdown
$stDcList = $db->prepare(
    "SELECT dc.dc_id, dc.dc_type, dc.total_qty, dc.receiving_date,
            g.grn_code, po.po_number, po.style_no
     FROM dc_receivings dc
     LEFT JOIN grn g ON g.grn_id = dc.grn_id
     LEFT JOIN purchase_orders po ON po.po_id = g.po_id
     WHERE dc.status IN ('RECEIVED','PUTAWAY_PENDING')
     ORDER BY dc.receiving_date DESC
     LIMIT 100"
);
$stDcList->execute();
$dcList = $stDcList->fetchAll();

// Locations
$stLoc = $db->prepare("SELECT location_id, location_code, zone, description FROM warehouse_locations WHERE status='active' ORDER BY zone, location_code");
$stLoc->execute();
$locations = $stLoc->fetchAll();

// Group locations by zone
$locationsByZone = [];
foreach ($locations as $loc) {
    $locationsByZone[$loc['zone'] ?? 'Other'][] = $loc;
}

$pageTitle = $isEdit ? 'Edit DC Putaway #'.$id : 'New DC Putaway';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i><?= $pageTitle ?></h5>
</div>

<?php if ($hourNow >= 14): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-clock-history me-2"></i>DC Putaway cutoff (2PM) has passed. Proceed with supervisor approval.
</div>
<?php endif; ?>

<!-- GP Lookup -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">GP Code Lookup</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <div class="col-md-4">
                <input type="text" name="gp_code" class="form-control" value="<?= h($gpCodeGet) ?>" placeholder="Enter Gatepass Code (e.g. GP-00001)">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Look Up</button>
            </div>
        </form>
        <?php if ($gpCodeGet && $gpInfo): ?>
        <div class="mt-2 p-2 bg-light rounded small">
            <strong><?= h($gpInfo['gp_code']) ?></strong> &mdash; <?= h($gpInfo['gp_type']) ?> /
            Status: <span class="badge bg-secondary"><?= h($gpInfo['gp_status']) ?></span>
            <?php if ($dcRecord): ?>
            | DC Receiving: <span class="badge bg-info text-dark"><?= h($dcRecord['dc_type']) ?></span> (<?= h($dcRecord['receiving_date']) ?>)
            <?php endif; ?>
        </div>
        <?php elseif ($gpCodeGet && !$gpInfo): ?>
        <div class="mt-2 text-danger small">Gatepass "<?= h($gpCodeGet) ?>" not found.</div>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="save_dc_putaway">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="gp_id" value="<?= h($dcp['gp_id'] ?? '') ?>">

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold py-2">Putaway Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">GP Code</label>
                        <input type="text" name="gp_code" class="form-control" value="<?= h($dcp['gp_code']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DC Receiving <small class="text-muted">(optional alternative lookup)</small></label>
                        <select name="dc_id" id="dcSelect" class="form-select">
                            <option value="">— select DC receiving —</option>
                            <?php foreach ($dcList as $dc): ?>
                            <option value="<?= $dc['dc_id'] ?>"
                                data-type="<?= h($dc['dc_type']) ?>"
                                <?= (string)($dcp['dc_id'] ?? '') === (string)$dc['dc_id'] ? 'selected' : '' ?>>
                                <?= h($dc['po_number'] ?? 'PO?') ?> — <?= h($dc['grn_code'] ?? 'GRN?') ?> | <?= h($dc['dc_type']) ?> | <?= h($dc['receiving_date']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Warehouse Type <span class="text-danger">*</span></label>
                        <select name="warehouse_type" id="warehouseType" class="form-select" required>
                            <option value="LOCAL"      <?= ($dcp['warehouse_type'] ?? 'LOCAL') === 'LOCAL'      ? 'selected' : '' ?>>LOCAL</option>
                            <option value="EXPORT"     <?= ($dcp['warehouse_type'] ?? '') === 'EXPORT'     ? 'selected' : '' ?>>EXPORT</option>
                            <option value="SMART_BUYS" <?= ($dcp['warehouse_type'] ?? '') === 'SMART_BUYS' ? 'selected' : '' ?>>SMART BUYS</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Putaway Date <span class="text-danger">*</span></label>
                            <input type="date" name="putaway_date" class="form-control" value="<?= h($dcp['putaway_date']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Putaway Time <small class="text-muted">cutoff: 2PM</small></label>
                            <input type="time" name="putaway_time" class="form-control" value="<?= h($dcp['putaway_time'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Putaway Person</label>
                        <input type="text" name="putaway_person" class="form-control" value="<?= h($dcp['putaway_person'] ?? '') ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= h($dcp['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold py-2 d-flex justify-content-between align-items-center">
                    Items
                    <div>
                        Total Qty: <span id="totalQtyDisplay" class="fw-bold">0</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="addItemRow()">
                            <i class="bi bi-plus-lg"></i> Add Row
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>SKU</th>
                                <th>Item Name</th>
                                <th style="width:100px">Qty Cartons</th>
                                <th>To Location</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        <?php
                        $initRows = $isEdit ? $existingItems : (count($gpItems) > 0 ? $gpItems : [[]]);
                        foreach ($initRows as $idx => $item):
                        ?>
                        <tr>
                            <td><input type="text" name="items[<?= $idx ?>][sku_code]"   class="form-control form-control-sm" value="<?= h($item['sku_code'] ?? '') ?>"></td>
                            <td><input type="text" name="items[<?= $idx ?>][item_name]"  class="form-control form-control-sm" value="<?= h($item['item_name'] ?? '') ?>"></td>
                            <td><input type="number" name="items[<?= $idx ?>][qty_cartons]" class="form-control form-control-sm qty-input" min="0" value="<?= (int)($item['qty_cartons'] ?? 0) ?>"></td>
                            <td>
                                <select name="items[<?= $idx ?>][location_id]" class="form-select form-select-sm location-select">
                                    <option value="">— select —</option>
                                    <?php foreach ($locationsByZone as $zone => $locs): ?>
                                    <optgroup label="<?= h($zone) ?>">
                                        <?php foreach ($locs as $loc): ?>
                                        <option value="<?= $loc['location_id'] ?>"
                                            data-zone="<?= h($loc['zone']) ?>"
                                            <?= isset($item['location_id']) && (int)$item['location_id'] === (int)$loc['location_id'] ? 'selected' : '' ?>>
                                            <?= h($loc['location_code']) ?><?= $loc['description'] ? ' — '.h($loc['description']) : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="hidden" name="items[<?= $idx ?>][item_id]" value="<?= (int)($item['item_id'] ?? 0) ?>">
                                <input type="hidden" name="items[<?= $idx ?>][po_id]"   value="<?= (int)($item['po_id']   ?? 0) ?>">
                                <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="this.closest('tr').remove(); updateQty()">&#x2715;</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark">Save Putaway</button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
let rowIdx = <?= count($initRows ?? [[]]) ?>;

function locationOptionsHtml(warehouseType, selectedId) {
    const locData = <?= json_encode($locationsByZone) ?>;
    let html = '<option value="">— select —</option>';
    for (const [zone, locs] of Object.entries(locData)) {
        html += `<optgroup label="${zone}">`;
        locs.forEach(loc => {
            const sel = loc.location_id == selectedId ? ' selected' : '';
            html += `<option value="${loc.location_id}" data-zone="${loc.zone}"${sel}>${loc.location_code}${loc.description ? ' — '+loc.description : ''}</option>`;
        });
        html += '</optgroup>';
    }
    return html;
}

function suggestLocation(selectEl, warehouseType) {
    const opts = selectEl.querySelectorAll('option[data-zone]');
    const keyword = warehouseType === 'SMART_BUYS' ? 'SMART' : warehouseType;
    for (const opt of opts) {
        if (opt.dataset.zone && opt.dataset.zone.toUpperCase().includes(keyword)) {
            selectEl.value = opt.value;
            return;
        }
    }
}

document.getElementById('warehouseType').addEventListener('change', function() {
    document.querySelectorAll('.location-select').forEach(sel => suggestLocation(sel, this.value));
});

document.getElementById('dcSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    const typeMap = {LOCAL:'LOCAL', EXPORT:'EXPORT', SMART_BUYS_DDF:'SMART_BUYS'};
    const wt = typeMap[opt.dataset.type] || 'LOCAL';
    document.getElementById('warehouseType').value = wt;
});

function addItemRow() {
    const wt = document.getElementById('warehouseType').value;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${rowIdx}][sku_code]"   class="form-control form-control-sm"></td>
        <td><input type="text" name="items[${rowIdx}][item_name]"  class="form-control form-control-sm"></td>
        <td><input type="number" name="items[${rowIdx}][qty_cartons]" class="form-control form-control-sm qty-input" min="0" value="0"></td>
        <td><select name="items[${rowIdx}][location_id]" class="form-select form-select-sm location-select">${locationOptionsHtml(wt, null)}</select></td>
        <td><input type="hidden" name="items[${rowIdx}][item_id]" value="0"><input type="hidden" name="items[${rowIdx}][po_id]" value="0">
            <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="this.closest('tr').remove();updateQty()">&#x2715;</button></td>
    `;
    tbody.appendChild(tr);
    suggestLocation(tr.querySelector('.location-select'), wt);
    rowIdx++;
    updateQty();
}

function updateQty() {
    let total = 0;
    document.querySelectorAll('.qty-input').forEach(i => total += (parseInt(i.value)||0));
    const el = document.getElementById('totalQtyDisplay');
    el.textContent = total.toLocaleString();
    el.className = 'fw-bold' + (total > 4000 ? ' text-danger' : '');
}
document.addEventListener('input', e => { if (e.target.classList.contains('qty-input')) updateQty(); });
updateQty();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
