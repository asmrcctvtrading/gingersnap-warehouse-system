<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();
$isEdit = isset($_GET['id']);
$dcId   = $isEdit ? (int) $_GET['id'] : 0;

if ($isEdit) {
    requirePermission('dc_receiving', 'edit');
    $stmt = $db->prepare(
        "SELECT dc.*, g.grn_code, g.grn_date, g.total_qty_received, g.ctrl_no,
                g.subcon_id AS grn_subcon_id,
                po.po_number, po.style_no, po.export_destination, po.jo_number,
                sc.subcon_name
         FROM dc_receivings dc
         LEFT JOIN grn g ON g.grn_id = dc.grn_id
         LEFT JOIN purchase_orders po ON po.po_id = g.po_id
         LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
         WHERE dc.dc_id = ?"
    );
    $stmt->execute([$dcId]);
    $dc = $stmt->fetch();
    if (!$dc) {
        setFlash('danger', 'DC Receiving record not found.');
        redirect('/modules/dc_receiving/list.php');
    }
    if ($dc['status'] !== 'RECEIVED') {
        setFlash('warning', 'Only RECEIVED records can be edited.');
        redirect('/modules/dc_receiving/view.php?id=' . $dcId);
    }
} else {
    requirePermission('dc_receiving', 'create');
    $dc = [
        'dc_id'           => 0,
        'grn_id'          => '',
        'receiving_date'  => date('Y-m-d'),
        'receiving_time'  => '',
        'receiving_person'=> '',
        'dc_type'         => 'LOCAL',
        'total_qty'       => 0,
        'defective_qty'   => 0,
        'status'          => 'RECEIVED',
        'notes'           => '',
    ];
}

// Eligible GRNs for new record
$eligibleGrns = [];
if (!$isEdit) {
    $grnStmt = $db->query(
        "SELECT g.grn_id, g.grn_code, g.grn_date, g.total_qty_received, g.ctrl_no,
                po.po_number, po.style_no, po.export_destination, po.jo_number,
                sc.subcon_name
         FROM grn g
         LEFT JOIN purchase_orders po ON po.po_id = g.po_id
         LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
         LEFT JOIN dc_receivings dc ON dc.grn_id = g.grn_id
         WHERE g.status = 'COMPLETED' AND dc.dc_id IS NULL
         ORDER BY g.grn_date DESC, g.grn_id DESC
         LIMIT 200"
    );
    $eligibleGrns = $grnStmt->fetchAll();
} else {
    // For edit: include the currently linked GRN
    if ($dc['grn_id']) {
        $grnStmt = $db->prepare(
            "SELECT g.grn_id, g.grn_code, g.grn_date, g.total_qty_received, g.ctrl_no,
                    po.po_number, po.style_no, po.export_destination, po.jo_number,
                    sc.subcon_name
             FROM grn g
             LEFT JOIN purchase_orders po ON po.po_id = g.po_id
             LEFT JOIN subcons sc ON sc.subcon_id = g.subcon_id
             WHERE g.grn_id = ?"
        );
        $grnStmt->execute([$dc['grn_id']]);
        $eligibleGrns = $grnStmt->fetchAll();
    }
}

// Build GRN data map for JS auto-fill
$grnMap = [];
foreach ($eligibleGrns as $g) {
    $dcTypeAuto = in_array($g['export_destination'], ['LOCAL', '']) ? 'LOCAL' : 'EXPORT';
    $grnMap[$g['grn_id']] = [
        'grn_code'          => $g['grn_code'],
        'grn_date'          => $g['grn_date'],
        'total_qty_received'=> (int)$g['total_qty_received'],
        'ctrl_no'           => $g['ctrl_no'],
        'subcon_name'       => $g['subcon_name'],
        'jo_number'         => $g['jo_number'],
        'export_destination'=> $g['export_destination'],
        'dc_type_auto'      => $dcTypeAuto,
    ];
}

$cutoffPassed = (int) date('H') >= 11;
$pageTitle = $isEdit ? 'Edit DC Receipt' : 'New DC Receipt';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($cutoffPassed): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-clock-history me-2"></i>
    DC Receiving cutoff (11AM) has passed. Proceed with supervisor approval.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:760px;">
    <div class="card-header bg-white fw-semibold py-2">
        <i class="bi bi-building-check me-2 text-muted"></i><?= h($pageTitle) ?>
    </div>
    <div class="card-body">
        <form method="post" action="<?= BASE_URL ?>/modules/dc_receiving/action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_dc_receiving">
            <input type="hidden" name="dc_id" value="<?= $isEdit ? (int)$dc['dc_id'] : 0 ?>">

            <!-- GRN Reference -->
            <div class="mb-3">
                <label class="form-label fw-semibold">GRN Reference</label>
                <select name="grn_id" id="grn_select" class="form-select" <?= $isEdit ? 'disabled' : '' ?>>
                    <option value="">— Select GRN —</option>
                    <?php foreach ($eligibleGrns as $g): ?>
                    <option value="<?= (int)$g['grn_id'] ?>"
                        <?= (string)($dc['grn_id'] ?? '') === (string)$g['grn_id'] ? 'selected' : '' ?>>
                        <?= h($g['grn_code']) ?> | <?= h($g['po_number'] ?? '') ?> / <?= h($g['style_no'] ?? '') ?> | <?= number_format((int)$g['total_qty_received']) ?> pcs
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?>
                <input type="hidden" name="grn_id" value="<?= (int)$dc['grn_id'] ?>">
                <?php endif; ?>
            </div>

            <!-- GRN Info Preview -->
            <div id="grn_info" class="bg-light rounded p-3 mb-3 small <?= empty($dc['grn_id']) ? 'd-none' : '' ?>">
                <div class="row g-2">
                    <div class="col-sm-4"><span class="text-muted">GRN Date:</span> <span id="info_grn_date"><?= h($dc['grn_date'] ?? '') ?></span></div>
                    <div class="col-sm-4"><span class="text-muted">Subcon:</span> <span id="info_subcon"><?= h($dc['subcon_name'] ?? '') ?></span></div>
                    <div class="col-sm-4"><span class="text-muted">Ctrl No:</span> <span id="info_ctrl_no"><?= h($dc['ctrl_no'] ?? '') ?></span></div>
                    <div class="col-sm-4"><span class="text-muted">JO Number:</span> <span id="info_jo"><?= h($dc['jo_number'] ?? '') ?></span></div>
                    <div class="col-sm-4"><span class="text-muted">Export Dest:</span> <span id="info_dest"><?= h($dc['export_destination'] ?? '') ?></span></div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Receiving Date -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Receiving Date <span class="text-danger">*</span></label>
                    <input type="date" name="receiving_date" class="form-control"
                           value="<?= h($dc['receiving_date']) ?>" required>
                </div>

                <!-- Receiving Time -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Receiving Time</label>
                    <input type="time" name="receiving_time" class="form-control"
                           value="<?= h($dc['receiving_time']) ?>">
                    <div class="form-text">Cutoff: 11:00 AM</div>
                </div>

                <!-- Receiving Person -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Receiving Person</label>
                    <input type="text" name="receiving_person" class="form-control"
                           value="<?= h($dc['receiving_person'] ?? '') ?>" maxlength="255">
                </div>

                <!-- DC Type -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">DC Type <span class="text-danger">*</span></label>
                    <select name="dc_type" id="dc_type" class="form-select" required>
                        <option value="LOCAL" <?= $dc['dc_type']==='LOCAL'?'selected':'' ?>>LOCAL</option>
                        <option value="EXPORT" <?= $dc['dc_type']==='EXPORT'?'selected':'' ?>>EXPORT</option>
                        <option value="SMART_BUYS_DDF" <?= $dc['dc_type']==='SMART_BUYS_DDF'?'selected':'' ?>>SMART BUYS / DDF</option>
                    </select>
                </div>

                <!-- Total Qty -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Total Qty <span class="text-danger">*</span></label>
                    <input type="number" name="total_qty" id="total_qty" class="form-control"
                           value="<?= (int)$dc['total_qty'] ?>" min="1" required>
                </div>

                <!-- Defective Qty -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Defective Qty</label>
                    <input type="number" name="defective_qty" id="defective_qty" class="form-control"
                           value="<?= (int)$dc['defective_qty'] ?>" min="0">
                    <div class="form-text">Qty found defective at DC; routes to SMART Buys.</div>
                    <div id="defective_note" class="text-danger small mt-1 <?= (int)$dc['defective_qty'] > 0 ? '' : 'd-none' ?>">
                        Defective qty will be auto-routed to SMART Buys on save.
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= h($dc['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Update' : 'Save' ?> DC Receipt
                </button>
                <a href="<?= BASE_URL ?>/modules/dc_receiving/list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const grnMap = <?= json_encode($grnMap, JSON_HEX_TAG) ?>;

document.getElementById('grn_select')?.addEventListener('change', function () {
    const id = this.value;
    const infoBox = document.getElementById('grn_info');
    if (!id || !grnMap[id]) {
        infoBox.classList.add('d-none');
        return;
    }
    const g = grnMap[id];
    document.getElementById('info_grn_date').textContent = g.grn_date || '—';
    document.getElementById('info_subcon').textContent   = g.subcon_name || '—';
    document.getElementById('info_ctrl_no').textContent  = g.ctrl_no || '—';
    document.getElementById('info_jo').textContent       = g.jo_number || '—';
    document.getElementById('info_dest').textContent     = g.export_destination || '—';
    document.getElementById('total_qty').value           = g.total_qty_received;
    const dcTypeEl = document.getElementById('dc_type');
    if (dcTypeEl.value !== 'SMART_BUYS_DDF') {
        dcTypeEl.value = g.dc_type_auto;
    }
    infoBox.classList.remove('d-none');
});

document.getElementById('defective_qty')?.addEventListener('input', function () {
    const note = document.getElementById('defective_note');
    if (parseInt(this.value, 10) > 0) {
        note.classList.remove('d-none');
    } else {
        note.classList.add('d-none');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
