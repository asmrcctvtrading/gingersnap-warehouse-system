<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'create');

$db    = getDB();
$grnId = (int)($_GET['grn_id'] ?? 0);

// ── Pre-fill from GRN if provided ─────────────────────────────────────────
$grn         = null;
$prefilledPo = 0;
$qcPassedQty = 0;

if ($grnId > 0) {
    $stGrn = $db->prepare(
        "SELECT g.grn_id, g.grn_code, g.grn_date, g.po_id,
                g.total_qty_received, g.total_qty_rejected, g.status,
                po.po_number, po.style_no
         FROM grn g
         LEFT JOIN purchase_orders po ON po.po_id = g.po_id
         WHERE g.grn_id = ?
         LIMIT 1"
    );
    $stGrn->execute([$grnId]);
    $grn = $stGrn->fetch();

    if ($grn) {
        $prefilledPo = (int)$grn['po_id'];
        $qcPassedQty = (int)$grn['total_qty_received'] - (int)$grn['total_qty_rejected'];
    }
}

// ── Reference data ─────────────────────────────────────────────────────────
$locations = $db->query(
    "SELECT location_id, location_code, zone, description
     FROM warehouse_locations
     WHERE status = 'active'
     ORDER BY zone, location_code"
)->fetchAll();

$pos = $db->query(
    "SELECT po_id, po_number, style_no
     FROM purchase_orders
     ORDER BY po_number"
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
    <a href="list.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">Record Stock Movement</h5>
</div>

<?php if ($grn): ?>
<!-- ── GRN Info Box ──────────────────────────────────────────────────────── -->
<div class="alert alert-info d-flex align-items-start gap-2 mb-3" style="max-width:720px">
    <i class="bi bi-box-arrow-in-right fs-5 mt-1 flex-shrink-0"></i>
    <div>
        <strong>Putaway from GRN: <?= h($grn['grn_code']) ?></strong><br>
        <span class="small">
            PO: <?= h($grn['po_number'] ?? '—') ?>
            <?= $grn['style_no'] ? ' / ' . h($grn['style_no']) : '' ?>
            &mdash;
            Received: <?= number_format((int)$grn['total_qty_received']) ?> cartons
            &mdash;
            QC Passed: <strong class="text-success"><?= number_format($qcPassedQty) ?></strong> cartons
        </span>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:720px">
    <div class="card-body">
        <form method="post" action="action.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_movement">
            <?php if ($grnId > 0): ?>
            <input type="hidden" name="grn_id" value="<?= $grnId ?>">
            <?php endif; ?>

            <!-- Movement Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Movement Type <span class="text-danger">*</span>
                </label>
                <select name="movement_type" id="movementType" class="form-select" required>
                    <?php
                    $typeOptions = [
                        'PUTAWAY'    => 'PUTAWAY — incoming stock to a location',
                        'RELOCATION' => 'RELOCATION — move stock between locations',
                        'PICK'       => 'PICK — remove stock from a location',
                        'ADJUST'     => 'ADJUST — set exact quantity at a location',
                        'DISPATCH'   => 'DISPATCH — stock leaving the warehouse',
                    ];
                    $defaultType = $grnId > 0 ? 'PUTAWAY' : 'PUTAWAY';
                    foreach ($typeOptions as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $val === $defaultType ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- PO Number -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Purchase Order</label>
                <select name="po_id" class="form-select">
                    <option value="">— no PO —</option>
                    <?php foreach ($pos as $po): ?>
                    <option value="<?= (int)$po['po_id'] ?>"
                        <?= (int)$po['po_id'] === $prefilledPo ? 'selected' : '' ?>>
                        <?= h($po['po_number']) ?>
                        <?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- From Location -->
            <div class="mb-3" id="fromLocWrap">
                <label class="form-label fw-semibold">
                    From Location
                    <span id="fromLocReq" class="text-danger" style="display:none">*</span>
                </label>
                <select name="from_location_id" id="fromLocSelect" class="form-select">
                    <option value="">— none —</option>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>">
                        [<?= h($loc['zone']) ?>] <?= h($loc['location_code']) ?>
                        <?= ($loc['description'] ?? '') !== '' ? ' — ' . h($loc['description']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text" id="fromLocHint">
                    Leave empty for PUTAWAY (first time stock enters a location).
                </div>
            </div>

            <!-- To Location -->
            <div class="mb-3" id="toLocWrap">
                <label class="form-label fw-semibold">
                    To Location
                    <span id="toLocReq" class="text-danger" style="display:none">*</span>
                </label>
                <select name="to_location_id" id="toLocSelect" class="form-select">
                    <option value="">— none —</option>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>">
                        [<?= h($loc['zone']) ?>] <?= h($loc['location_code']) ?>
                        <?= ($loc['description'] ?? '') !== '' ? ' — ' . h($loc['description']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Qty Cartons -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Qty Cartons <span class="text-danger">*</span>
                </label>
                <input type="number" name="qty_cartons" class="form-control"
                       value="<?= $qcPassedQty > 0 ? $qcPassedQty : 0 ?>"
                       min="-99999" required>
                <div class="form-text">
                    Use a negative number for ADJUST to reduce stock.
                </div>
            </div>

            <!-- Movement Date (reference) -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Movement Date</label>
                <input type="date" name="movement_date" class="form-control"
                       value="<?= date('Y-m-d') ?>">
                <div class="form-text text-muted">
                    For reference — actual timestamp is recorded automatically.
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="255"
                    placeholder="Optional notes about this movement"></textarea>
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
        PUTAWAY:    { from: { req: false }, to: { req: true  }, hint: 'Leave From empty for PUTAWAY.' },
        RELOCATION: { from: { req: true  }, to: { req: true  }, hint: '' },
        PICK:       { from: { req: true  }, to: { req: false }, hint: '' },
        ADJUST:     { from: { req: true  }, to: { req: false }, hint: 'Use From Location to identify where stock is adjusted.' },
        DISPATCH:   { from: { req: true  }, to: { req: false }, hint: '' }
    };

    function applyRules(type) {
        var r    = rules[type] || rules.PUTAWAY;
        var frq  = document.getElementById('fromLocReq');
        var trq  = document.getElementById('toLocReq');
        var fs   = document.getElementById('fromLocSelect');
        var ts   = document.getElementById('toLocSelect');
        var hint = document.getElementById('fromLocHint');

        fs.required = r.from.req;
        ts.required = r.to.req;

        frq.style.display = r.from.req ? 'inline' : 'none';
        trq.style.display = r.to.req   ? 'inline' : 'none';

        if (hint) hint.textContent = r.hint;

        if (!r.to.req)   { ts.value = ''; }
        if (!r.from.req) { fs.value = ''; }
    }

    var sel = document.getElementById('movementType');
    if (sel) {
        sel.addEventListener('change', function () { applyRules(this.value); });
        applyRules(sel.value);
    }
}());
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
