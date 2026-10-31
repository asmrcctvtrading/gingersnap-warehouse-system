<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('dbased', 'edit');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('/modules/dbased/list.php'); }

// ── Load PO ────────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT po.*,
            sc.subcon_name,
            c.coordinator_name AS coordinator_name
     FROM purchase_orders po
     LEFT JOIN subcons sc     ON sc.subcon_id      = po.subcon_id
     LEFT JOIN coordinators c ON c.coordinator_id  = po.coordinator_id
     WHERE po.po_id = ?"
);
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) {
    setFlash('error', 'Purchase Order not found.');
    redirect('/modules/dbased/list.php');
}

// ── Load destination allocation (may not exist yet) ─────────────────────
$pdaStmt = $db->prepare(
    "SELECT * FROM po_destination_allocations WHERE po_id = ? LIMIT 1"
);
$pdaStmt->execute([$id]);
$pda = $pdaStmt->fetch() ?: [
    'qty_local'        => '',
    'qty_sg'           => '',
    'qty_malaysia'     => '',
    'qty_indo'         => '',
    'qty_other_export' => '',
    'qty_sm_baliwag'   => '',
    'qty_export_full'  => '',
    'notes'            => '',
];

// ── Load coordinators for dropdown ────────────────────────────────────────
try {
    $coordinators = $db->query(
        "SELECT coordinator_id, coordinator_name FROM coordinators WHERE is_active = 1 ORDER BY coordinator_name"
    )->fetchAll();
} catch (Throwable $e) {
    $coordinators = [];
}

// ── Pre-populate alias: strip trailing "-1" (or "-2" etc.) suffix ─────────
$defaultAlias = (string)($po['po_number_alias'] ?? '');
if ($defaultAlias === '') {
    $defaultAlias = preg_replace('/-\d+$/', '', (string)($po['po_number'] ?? ''));
}

// ── Active tab ────────────────────────────────────────────────────────────
$activeTab = (in_array($_GET['tab'] ?? '', ['details', 'allocation'], true))
           ? $_GET['tab']
           : 'details';

$pageTitle = 'DBASED Edit: ' . $po['po_number'];
include __DIR__ . '/../../includes/header.php';

$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Page header ─────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0">
        <i class="bi bi-pencil me-1"></i>DBASED Edit
        <span class="text-muted fw-normal">— <?= h($po['po_number']) ?></span>
    </h5>
    <?php
    $statusBadge = match($po['status']) {
        'NEW' => 'secondary', 'WIP' => 'warning text-dark',
        'COMPLETED' => 'success', 'CANCELLED' => 'danger', default => 'secondary',
    };
    ?>
    <span class="badge bg-<?= $statusBadge ?>"><?= h($po['status']) ?></span>
</div>

<!-- ── PO quick-info strip ─────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-3 bg-light">
    <div class="card-body py-2 px-3">
        <div class="row g-2 small text-muted">
            <div class="col-auto"><strong>Style:</strong> <?= h($po['style_no'] ?? '') ?></div>
            <div class="col-auto"><strong>Subcon:</strong> <?= h($po['subcon_name'] ?? '—') ?></div>
            <div class="col-auto"><strong>PO Qty:</strong> <?= number_format((int)$po['po_qty']) ?></div>
            <div class="col-auto">
                <strong>PO Date:</strong>
                <?= $po['po_date'] ? h(date('d M Y', strtotime($po['po_date']))) : '—' ?>
            </div>
            <div class="col-auto">
                <strong>Collection:</strong>
                <?= $po['collection_id'] ? (int)$po['collection_id'] : '—' ?>
            </div>
            <div class="col-auto"><strong>Facility:</strong> <?= h($po['facility'] ?? '') ?></div>
        </div>
    </div>
</div>

<!-- ── Tabs ────────────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-3" id="dbasedTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'details' ? 'active' : '' ?>"
                id="tab-details" data-bs-toggle="tab" data-bs-target="#pane-details"
                type="button" role="tab">
            <i class="bi bi-card-list me-1"></i>DBASED Details
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'allocation' ? 'active' : '' ?>"
                id="tab-alloc" data-bs-toggle="tab" data-bs-target="#pane-alloc"
                type="button" role="tab">
            <i class="bi bi-geo-alt me-1"></i>Destination Allocation
        </button>
    </li>
</ul>

<form method="post" action="action.php" id="dbasedForm">
    <?= csrfField() ?>
    <input type="hidden" name="action"   value="save_dbased">
    <input type="hidden" name="po_id"    value="<?= $id ?>">
    <input type="hidden" name="active_tab" id="activeTabInput" value="<?= h($activeTab) ?>">

    <div class="tab-content">

        <!-- ── Tab 1: DBASED Details ──────────────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'details' ? 'show active' : '' ?>"
             id="pane-details" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3">

                        <!-- Coordinator -->
                        <div class="col-md-6">
                            <label class="form-label" for="coordinator_id">
                                Coordinator
                            </label>
                            <select name="coordinator_id" id="coordinator_id"
                                    class="form-select">
                                <option value="">— Unassigned —</option>
                                <?php foreach ($coordinators as $crd): ?>
                                <option value="<?= (int)$crd['coordinator_id'] ?>"
                                    <?= (int)($po['coordinator_id'] ?? 0) === (int)$crd['coordinator_id'] ? 'selected' : '' ?>>
                                    <?= h($crd['coordinator_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- PO Number Alias -->
                        <div class="col-md-6">
                            <label class="form-label" for="po_number_alias">
                                PO Number Alias
                                <small class="text-muted">(stripped suffix, e.g. PO-001)</small>
                            </label>
                            <input type="text" name="po_number_alias" id="po_number_alias"
                                   class="form-control font-monospace"
                                   value="<?= h($defaultAlias) ?>"
                                   maxlength="100">
                        </div>

                        <!-- Receiving Priority -->
                        <div class="col-md-3">
                            <label class="form-label" for="receiving_priority">
                                Receiving Priority No.
                                <small class="text-muted">(col E)</small>
                            </label>
                            <input type="number" name="receiving_priority" id="receiving_priority"
                                   class="form-control" min="0" max="9999"
                                   value="<?= $po['receiving_priority'] !== null ? (int)$po['receiving_priority'] : '' ?>"
                                   placeholder="e.g. 1">
                            <div class="form-text">Lower number = higher priority</div>
                        </div>

                        <!-- Inspection Priority -->
                        <div class="col-md-3">
                            <label class="form-label" for="inspection_priority">
                                Inspection Priority No.
                                <small class="text-muted">(col Z)</small>
                            </label>
                            <input type="number" name="inspection_priority" id="inspection_priority"
                                   class="form-control" min="0" max="9999"
                                   value="<?= $po['inspection_priority'] !== null ? (int)$po['inspection_priority'] : '' ?>"
                                   placeholder="e.g. 1">
                        </div>

                        <!-- Remarks -->
                        <div class="col-md-6">
                            <label class="form-label" for="remarks">Remarks</label>
                            <textarea name="remarks" id="remarks"
                                      class="form-control" rows="3"
                                      maxlength="500"><?= h($po['remarks'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /row -->
                </div><!-- /card-body -->
                <div class="card-footer bg-white d-flex gap-2 justify-content-end">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div><!-- /tab-pane details -->

        <!-- ── Tab 2: Destination Allocation ──────────────────────────── -->
        <div class="tab-pane fade <?= $activeTab === 'allocation' ? 'show active' : '' ?>"
             id="pane-alloc" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div id="allocTotalWarning" class="alert alert-warning d-none" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Allocation total (<strong id="allocTotalDisplay">0</strong>)
                        does not match PO Qty (<strong><?= (int)$po['po_qty'] ?></strong>).
                    </div>

                    <div class="row g-3">

                        <!-- LOCAL -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_local">LOCAL</label>
                            <input type="number" name="qty_local" id="qty_local"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_local'] !== '' ? (int)$pda['qty_local'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- SG -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_sg">SINGAPORE (SG)</label>
                            <input type="number" name="qty_sg" id="qty_sg"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_sg'] !== '' ? (int)$pda['qty_sg'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- MALAYSIA -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_malaysia">MALAYSIA</label>
                            <input type="number" name="qty_malaysia" id="qty_malaysia"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_malaysia'] !== '' ? (int)$pda['qty_malaysia'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- INDO -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_indo">INDONESIA (INDO)</label>
                            <input type="number" name="qty_indo" id="qty_indo"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_indo'] !== '' ? (int)$pda['qty_indo'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- Other Export -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_other_export">OTHER EXPORT</label>
                            <input type="number" name="qty_other_export" id="qty_other_export"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_other_export'] !== '' ? (int)$pda['qty_other_export'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- SM Baliwag -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_sm_baliwag">SM BALIWAG</label>
                            <input type="number" name="qty_sm_baliwag" id="qty_sm_baliwag"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_sm_baliwag'] !== '' ? (int)$pda['qty_sm_baliwag'] : '' ?>"
                                   placeholder="0">
                        </div>

                        <!-- Export Full Qty -->
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="qty_export_full">
                                EXPORT (FULL QTY)
                            </label>
                            <input type="number" name="qty_export_full" id="qty_export_full"
                                   class="form-control alloc-qty" min="0"
                                   value="<?= $pda['qty_export_full'] !== '' ? (int)$pda['qty_export_full'] : '' ?>"
                                   placeholder="0">
                            <div class="form-text">Full export allocation total</div>
                        </div>

                        <!-- Auto-computed Total -->
                        <div class="col-6 col-md-4 d-flex align-items-end">
                            <div class="w-100">
                                <label class="form-label text-muted">
                                    Computed Total
                                    <span class="badge bg-secondary ms-1">auto</span>
                                </label>
                                <div class="form-control bg-light fw-bold text-center"
                                     id="allocComputedTotal" style="font-size:1.1rem">
                                    0
                                </div>
                                <div class="form-text">
                                    PO Qty: <strong><?= number_format((int)$po['po_qty']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label" for="alloc_notes">Allocation Notes</label>
                            <textarea name="alloc_notes" id="alloc_notes"
                                      class="form-control" rows="2"
                                      maxlength="300"><?= h($pda['notes'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /row -->
                </div><!-- /card-body -->
                <div class="card-footer bg-white d-flex gap-2 justify-content-end">
                    <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Allocation
                    </button>
                </div>
            </div>
        </div><!-- /tab-pane allocation -->

    </div><!-- /tab-content -->
</form>

<script>
(function () {
    const PO_QTY       = <?= (int)$po['po_qty'] ?>;
    const totalDisplay = document.getElementById('allocComputedTotal');
    const warningBox   = document.getElementById('allocTotalWarning');
    const warningNum   = document.getElementById('allocTotalDisplay');
    const qtyInputs    = document.querySelectorAll('.alloc-qty');

    // Track which tab is active so it restores correctly on validation redirect
    document.querySelectorAll('#dbasedTabs button').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            const target = e.target.dataset.bsTarget;
            const tabName = target === '#pane-alloc' ? 'allocation' : 'details';
            document.getElementById('activeTabInput').value = tabName;
        });
    });

    function recalcTotal() {
        let sum = 0;
        qtyInputs.forEach(function (el) {
            const v = parseInt(el.value, 10);
            if (!isNaN(v) && v > 0) { sum += v; }
        });
        totalDisplay.textContent = sum.toLocaleString();
        warningNum.textContent   = sum.toLocaleString();

        if (sum !== PO_QTY) {
            warningBox.classList.remove('d-none');
            totalDisplay.classList.add('text-warning');
            totalDisplay.classList.remove('text-success');
        } else {
            warningBox.classList.add('d-none');
            totalDisplay.classList.add('text-success');
            totalDisplay.classList.remove('text-warning');
        }
    }

    qtyInputs.forEach(function (el) {
        el.addEventListener('input', recalcTotal);
    });

    // Run once on load to set initial state
    recalcTotal();
})();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
