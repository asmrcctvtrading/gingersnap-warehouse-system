<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('stock', 'create');

$db = getDB();

$locations = $db->query(
    "SELECT location_id, location_code, zone, description
     FROM warehouse_locations
     WHERE status = 'active'
     ORDER BY zone, location_code"
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

            <!-- Movement Type -->
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

            <!-- Location selectors -->
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
                            [<?= h($loc['zone']) ?>] <?= h($loc['location_code']) ?>
                            <?= ($loc['description'] ?? '') !== '' ? ' — ' . h($loc['description']) : '' ?>
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
                            [<?= h($loc['zone']) ?>] <?= h($loc['location_code']) ?>
                            <?= ($loc['description'] ?? '') !== '' ? ' — ' . h($loc['description']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Item -->
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

            <!-- PO -->
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

            <!-- Quantities -->
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

            <!-- ADJUST info banner (shown only for ADJUST type) -->
            <div id="adjustBanner" class="alert alert-info py-2 small mb-3" style="display:none">
                <i class="bi bi-info-circle me-1"></i>
                <strong>ADJUST</strong> sets the <em>exact</em> quantity for the selected
                location + item + PO. Use this to correct discrepancies found during stock take.
                The movement is still recorded in the ledger.
            </div>

            <!-- Reference Doc -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Reference Document</label>
                <input type="text" name="reference_doc" class="form-control"
                       placeholder="e.g. GRN-001, DR-2026-001" maxlength="100">
            </div>

            <!-- Notes -->
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
    // Rules: which locations are shown / required per movement type
    // from: { visible, required }  to: { visible, required }
    var rules = {
        PUTAWAY:    { from: { vis: false, req: false }, to: { vis: true,  req: true  } },
        RELOCATION: { from: { vis: true,  req: true  }, to: { vis: true,  req: true  } },
        PICK:       { from: { vis: true,  req: true  }, to: { vis: false, req: false } },
        ADJUST:     { from: { vis: true,  req: true  }, to: { vis: false, req: false } },
        DISPATCH:   { from: { vis: true,  req: true  }, to: { vis: false, req: false } }
    };

    function applyRules(type) {
        var r    = rules[type] || rules.PUTAWAY;
        var fw   = document.getElementById('fromLocWrap');
        var tw   = document.getElementById('toLocWrap');
        var fs   = document.getElementById('from_location_id');
        var ts   = document.getElementById('to_location_id');
        var frq  = document.getElementById('fromLocRequired');
        var trq  = document.getElementById('toLocRequired');
        var adj  = document.getElementById('adjustBanner');

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
