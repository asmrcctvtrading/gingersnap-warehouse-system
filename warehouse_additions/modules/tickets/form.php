<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('tickets', 'edit');
    $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();
    if (!$ticket) { redirect('/modules/tickets/list.php'); }
} else {
    requirePermission('tickets', 'create');
    $ticket = [
        'ticket_id'    => 0,
        'ticket_no'    => '',
        'po_id'        => '',
        'ticket_date'  => date('Y-m-d'),
        'qty'          => 0,
        'status'       => 'OPEN',
        'notes'        => '',
        'priority_no'  => 999,
        'handoff_time' => '',
        'polybag_qty'  => 0,
        'coordinator_id' => '',
    ];
}

$pos = $db->query(
    "SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number"
)->fetchAll();

$coordinators = $db->query(
    "SELECT coordinator_id, coordinator_name FROM coordinators WHERE is_active=1 ORDER BY coordinator_name"
)->fetchAll();

$pageTitle = $isEdit ? 'Edit Ticket' : 'New Ticket';
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
    <h5 class="mb-0"><?= $isEdit ? 'Edit Ticket' : 'New Ticket' ?></h5>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px">
<div class="card-body">
<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int)($ticket['ticket_id'] ?? 0) ?>">

    <div class="mb-3">
        <label class="form-label fw-semibold">Ticket # <span class="text-danger">*</span></label>
        <input type="text" name="ticket_no" class="form-control"
               value="<?= h($ticket['ticket_no']) ?>" required maxlength="100"
               placeholder="e.g. TKT-2026-001">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Purchase Order</label>
        <select name="po_id" class="form-select">
            <option value="">— select PO —</option>
            <?php foreach ($pos as $po): ?>
            <option value="<?= (int)$po['po_id'] ?>" <?= (string)$ticket['po_id'] === (string)$po['po_id'] ? 'selected' : '' ?>>
                <?= h($po['po_number']) ?><?= $po['style_no'] ? ' / ' . h($po['style_no']) : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" name="ticket_date" class="form-control"
                   value="<?= h($ticket['ticket_date']) ?>">
        </div>
        <div class="col-6">
            <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
            <input type="number" name="qty" class="form-control"
                   value="<?= (int)$ticket['qty'] ?>" min="0" required>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label fw-semibold">Priority No</label>
            <input type="number" name="priority_no" class="form-control"
                   value="<?= (int)($ticket['priority_no'] ?? 999) ?>" min="1" max="999">
        </div>
        <div class="col-6">
            <label class="form-label fw-semibold">Handoff Time</label>
            <input type="time" name="handoff_time" class="form-control"
                   value="<?= h($ticket['handoff_time'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Polybag Qty</label>
        <input type="number" name="polybag_qty" class="form-control"
               value="<?= (int)($ticket['polybag_qty'] ?? 0) ?>" min="0">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
            <?php foreach (['OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'] as $s): ?>
            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Coordinator</label>
        <select name="coordinator_id" class="form-select">
            <option value="">— Select Coordinator —</option>
            <?php foreach ($coordinators as $c): ?>
              <option value="<?= $c['coordinator_id'] ?>" <?= ($ticket['coordinator_id'] ?? '') == $c['coordinator_id'] ? 'selected' : '' ?>>
                <?= h($c['coordinator_name']) ?>
              </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold">Notes</label>
        <input type="text" name="notes" class="form-control"
               value="<?= h($ticket['notes'] ?? '') ?>" maxlength="255">
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-dark"><?= $isEdit ? 'Save Changes' : 'Create Ticket' ?></button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
