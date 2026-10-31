<?php
/**
 * Gingersnaps Warehouse — Missing Modules Installer
 * Upload to /public_html/warehouse/install.php
 * Visit the URL while logged in, confirm, then delete the file.
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

if (!can('users', 'view')) {
    die('Administrator access required.');
}

// ─── File definitions ──────────────────────────────────────────────────────
$files = [];

// ── modules/tickets/list.php ──────────────────────────────────────────────
$files['modules/tickets/list.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('tickets', 'view');

$db = getDB();

$status = $_GET['status'] ?? '';
$where  = $status ? "WHERE t.status = " . $db->quote($status) : "";

$rows = $db->query(
    "SELECT t.*, po.po_number, po.style_no, s.subcon_name
     FROM tickets t
     LEFT JOIN purchase_orders po ON po.po_id = t.po_id
     LEFT JOIN subcons s ON s.subcon_id = po.subcon_id
     $where
     ORDER BY t.ticket_id DESC LIMIT 300"
)->fetchAll();

$pageTitle = 'Tickets';
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
        <h5 class="mb-0">Tickets</h5>
        <span class="badge bg-secondary"><?= count($rows) ?></span>
    </div>
    <div class="d-flex gap-2">
        <select onchange="location.href='list.php?status='+this.value" class="form-select form-select-sm" style="width:auto">
            <option value="">All Statuses</option>
            <?php foreach (['OPEN','IN_PROGRESS','COMPLETED','CANCELLED'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (can('tickets', 'create')): ?>
        <a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Ticket</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Ticket #</th><th>PO #</th><th>Style</th><th>Subcon</th>
                    <th>Date</th><th class="text-end">Qty</th><th>Status</th><th>Notes</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $badge = match($r['status']) {
                    'OPEN'        => 'secondary',
                    'IN_PROGRESS' => 'primary',
                    'COMPLETED'   => 'success',
                    'CANCELLED'   => 'danger',
                    default       => 'secondary',
                };
            ?>
            <tr>
                <td class="fw-semibold"><?= h($r['ticket_no']) ?></td>
                <td><?php if ($r['po_id']): ?><a href="<?= BASE_URL ?>/modules/po/view.php?id=<?= (int)$r['po_id'] ?>"><?= h($r['po_number']) ?></a><?php endif; ?></td>
                <td class="small text-muted"><?= h($r['style_no']) ?></td>
                <td class="small"><?= h($r['subcon_name']) ?></td>
                <td class="small"><?= fmtDate($r['ticket_date']) ?></td>
                <td class="text-end"><?= fmtNum($r['qty']) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= h($r['status']) ?></span></td>
                <td class="small text-muted"><?= h($r['notes']) ?></td>
                <td class="text-end">
                    <?php if (can('tickets', 'edit')): ?><a href="form.php?id=<?= (int)$r['ticket_id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a><?php endif; ?>
                    <?php if (can('tickets', 'delete')): ?>
                    <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete ticket <?= h($r['ticket_no']) ?>?')">
                        <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['ticket_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No tickets yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/tickets/form.php ──────────────────────────────────────────────
$files['modules/tickets/form.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();
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
    $ticket = ['ticket_no'=>'','po_id'=>'','ticket_date'=>date('Y-m-d'),'qty'=>0,'status'=>'OPEN','notes'=>''];
}

$pos = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();
$pageTitle = $isEdit ? 'Edit Ticket' : 'New Ticket';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $isEdit ? 'Edit Ticket' : 'New Ticket' ?></h5>
</div>
<div class="card border-0 shadow-sm" style="max-width:600px">
<div class="card-body">
<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int)($ticket['ticket_id'] ?? 0) ?>">
    <div class="mb-3">
        <label class="form-label fw-semibold">Ticket #<span class="text-danger">*</span></label>
        <input type="text" name="ticket_no" class="form-control" value="<?= h($ticket['ticket_no']) ?>" required maxlength="100" placeholder="e.g. TKT-2026-001">
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
            <input type="date" name="ticket_date" class="form-control" value="<?= h($ticket['ticket_date']) ?>">
        </div>
        <div class="col-6">
            <label class="form-label fw-semibold">Qty<span class="text-danger">*</span></label>
            <input type="number" name="qty" class="form-control" value="<?= (int)$ticket['qty'] ?>" min="0" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
            <?php foreach (['OPEN','IN_PROGRESS','COMPLETED','CANCELLED'] as $s): ?>
            <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-4">
        <label class="form-label fw-semibold">Notes</label>
        <input type="text" name="notes" class="form-control" value="<?= h($ticket['notes']) ?>" maxlength="255">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-dark"><?= $isEdit ? 'Save Changes' : 'Create Ticket' ?></button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/tickets/action.php ────────────────────────────────────────────
$files['modules/tickets/action.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if ($action === 'create') {
    requirePermission('tickets', 'create');
    $stmt = $db->prepare("INSERT INTO tickets (ticket_no, po_id, ticket_date, qty, status, notes, created_by) VALUES (:tn, :po, :td, :qty, :st, :nt, :cb)");
    $stmt->execute(['tn'=>trim($_POST['ticket_no']??''),'po'=>$_POST['po_id']?:null,'td'=>$_POST['ticket_date']?:null,'qty'=>(int)($_POST['qty']??0),'st'=>$_POST['status']??'OPEN','nt'=>trim($_POST['notes']??'')?:null,'cb'=>$_SESSION['user_id']??null]);
    logActivity('CREATE', 'tickets', (int)$db->lastInsertId(), trim($_POST['ticket_no']??''));
    setFlash('success', 'Ticket created.');
} elseif ($action === 'update') {
    requirePermission('tickets', 'edit');
    $stmt = $db->prepare("UPDATE tickets SET ticket_no=:tn, po_id=:po, ticket_date=:td, qty=:qty, status=:st, notes=:nt WHERE ticket_id=:id");
    $stmt->execute(['tn'=>trim($_POST['ticket_no']??''),'po'=>$_POST['po_id']?:null,'td'=>$_POST['ticket_date']?:null,'qty'=>(int)($_POST['qty']??0),'st'=>$_POST['status']??'OPEN','nt'=>trim($_POST['notes']??'')?:null,'id'=>$id]);
    logActivity('UPDATE', 'tickets', $id, trim($_POST['ticket_no']??''));
    setFlash('success', 'Ticket updated.');
} elseif ($action === 'delete') {
    requirePermission('tickets', 'delete');
    $db->prepare("DELETE FROM tickets WHERE ticket_id = ?")->execute([$id]);
    logActivity('DELETE', 'tickets', $id, null);
    setFlash('success', 'Ticket deleted.');
}
redirect('/modules/tickets/list.php');
END_OF_FILE;

// ── modules/output/list.php ───────────────────────────────────────────────
$files['modules/output/list.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('output', 'view');

$db = getDB();
$dateFrom = getDateParam('date_from', date('Y-m-01'));
$dateTo   = getDateParam('date_to',   date('Y-m-d'));
$poId     = (int)($_GET['po_id'] ?? 0);

$sql  = "SELECT o.*, po.po_number, po.style_no, s.subcon_name, u.username FROM output_records o JOIN purchase_orders po ON po.po_id = o.po_id LEFT JOIN subcons s ON s.subcon_id = po.subcon_id LEFT JOIN users u ON u.user_id = o.created_by WHERE o.output_date BETWEEN :df AND :dt";
$bind = ['df' => $dateFrom, 'dt' => $dateTo];
if ($poId) { $sql .= " AND o.po_id = :po"; $bind['po'] = $poId; }
$sql .= " ORDER BY o.output_date DESC, o.output_id DESC LIMIT 500";
$stmt = $db->prepare($sql);
$stmt->execute($bind);
$rows = $stmt->fetchAll();

$totalCompleted = array_sum(array_column($rows, 'completed_qty'));
$totalPacked    = array_sum(array_column($rows, 'packed_qty'));
$pos = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();

$pageTitle = 'Output Records';
include __DIR__ . '/../../includes/header.php';
$flash = getFlash();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Output Records</h5>
    <?php if (can('output', 'create')): ?><a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Entry</a><?php endif; ?>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="kpi-card"><div class="text-muted small">Completed Pcs</div><div class="fs-4 fw-bold" style="color:#1e3a5f"><?= fmtNum($totalCompleted) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="kpi-card" style="border-left-color:#2c7a4b"><div class="text-muted small">Packed Pcs</div><div class="fs-4 fw-bold" style="color:#2c7a4b"><?= fmtNum($totalPacked) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="kpi-card" style="border-left-color:#6c757d"><div class="text-muted small">Entries</div><div class="fs-4 fw-bold text-secondary"><?= count($rows) ?></div></div></div>
</div>
<form method="get" class="d-flex flex-wrap gap-2 mb-3">
    <input type="date" name="date_from" class="form-control form-control-sm" style="width:160px" value="<?= h($dateFrom) ?>">
    <input type="date" name="date_to" class="form-control form-control-sm" style="width:160px" value="<?= h($dateTo) ?>">
    <select name="po_id" class="form-select form-select-sm" style="width:220px">
        <option value="">All POs</option>
        <?php foreach ($pos as $po): ?><option value="<?= (int)$po['po_id'] ?>" <?= $poId===(int)$po['po_id']?'selected':'' ?>><?= h($po['po_number']) ?><?= $po['style_no']?' / '.h($po['style_no']):'' ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-outline-dark btn-sm"><i class="bi bi-funnel"></i> Filter</button>
    <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear</a>
</form>
<div class="card border-0 shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
<thead class="table-light"><tr><th>Date</th><th>PO #</th><th>Style</th><th>Subcon</th><th class="text-end">Completed</th><th class="text-end">Packed</th><th>Notes</th><th>By</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= fmtDate($r['output_date']) ?></td>
    <td><a href="<?= BASE_URL ?>/modules/po/view.php?id=<?= (int)$r['po_id'] ?>"><?= h($r['po_number']) ?></a></td>
    <td class="small text-muted"><?= h($r['style_no']) ?></td>
    <td class="small"><?= h($r['subcon_name']) ?></td>
    <td class="text-end fw-semibold"><?= fmtNum($r['completed_qty']) ?></td>
    <td class="text-end"><?= fmtNum($r['packed_qty']) ?></td>
    <td class="small text-muted"><?= h($r['notes']) ?></td>
    <td class="small text-muted"><?= h($r['username']) ?></td>
    <td class="text-end">
        <?php if (can('output', 'edit')): ?><a href="form.php?id=<?= (int)$r['output_id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <?php if (can('output', 'delete')): ?>
        <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete this output entry?')">
            <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['output_id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No output records for this period.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/output/form.php ───────────────────────────────────────────────
$files['modules/output/form.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('output', 'edit');
    $stmt = $db->prepare("SELECT * FROM output_records WHERE output_id = ?");
    $stmt->execute([$id]);
    $rec = $stmt->fetch();
    if (!$rec) { redirect('/modules/output/list.php'); }
} else {
    requirePermission('output', 'create');
    $rec = ['po_id'=>$_GET['po_id']??'','output_date'=>date('Y-m-d'),'completed_qty'=>0,'packed_qty'=>0,'notes'=>''];
}
$pos = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();
$pageTitle = $isEdit ? 'Edit Output Entry' : 'New Output Entry';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $isEdit ? 'Edit Output Entry' : 'New Output Entry' ?></h5>
</div>
<div class="card border-0 shadow-sm" style="max-width:560px"><div class="card-body">
<form method="post" action="action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <input type="hidden" name="id" value="<?= (int)($rec['output_id'] ?? 0) ?>">
    <div class="mb-3">
        <label class="form-label fw-semibold">Purchase Order<span class="text-danger">*</span></label>
        <select name="po_id" class="form-select" required>
            <option value="">— select PO —</option>
            <?php foreach ($pos as $po): ?><option value="<?= (int)$po['po_id'] ?>" <?= (string)$rec['po_id']===(string)$po['po_id']?'selected':'' ?>><?= h($po['po_number']) ?><?= $po['style_no']?' / '.h($po['style_no']):'' ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Output Date<span class="text-danger">*</span></label>
        <input type="date" name="output_date" class="form-control" value="<?= h($rec['output_date']) ?>" required>
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label fw-semibold">Completed Qty<span class="text-danger">*</span></label><input type="number" name="completed_qty" class="form-control" value="<?= (int)$rec['completed_qty'] ?>" min="0" required></div>
        <div class="col-6"><label class="form-label fw-semibold">Packed Qty</label><input type="number" name="packed_qty" class="form-control" value="<?= (int)$rec['packed_qty'] ?>" min="0"></div>
    </div>
    <div class="mb-4"><label class="form-label fw-semibold">Notes</label><input type="text" name="notes" class="form-control" value="<?= h($rec['notes']) ?>" maxlength="255"></div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-dark"><?= $isEdit ? 'Save Changes' : 'Save Entry' ?></button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
</div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/output/action.php ─────────────────────────────────────────────
$files['modules/output/action.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

if ($action === 'create') {
    requirePermission('output', 'create');
    $stmt = $db->prepare("INSERT INTO output_records (po_id, output_date, completed_qty, packed_qty, notes, created_by) VALUES (:po, :od, :cq, :pq, :nt, :cb)");
    $stmt->execute(['po'=>(int)$_POST['po_id'],'od'=>$_POST['output_date'],'cq'=>(int)($_POST['completed_qty']??0),'pq'=>(int)($_POST['packed_qty']??0),'nt'=>trim($_POST['notes']??'')?:null,'cb'=>$_SESSION['user_id']??null]);
    logActivity('CREATE', 'output', (int)$db->lastInsertId(), 'PO '.(int)$_POST['po_id'].' — '.$_POST['output_date']);
    setFlash('success', 'Output entry saved.');
} elseif ($action === 'update') {
    requirePermission('output', 'edit');
    $stmt = $db->prepare("UPDATE output_records SET po_id=:po, output_date=:od, completed_qty=:cq, packed_qty=:pq, notes=:nt WHERE output_id=:id");
    $stmt->execute(['po'=>(int)$_POST['po_id'],'od'=>$_POST['output_date'],'cq'=>(int)($_POST['completed_qty']??0),'pq'=>(int)($_POST['packed_qty']??0),'nt'=>trim($_POST['notes']??'')?:null,'id'=>$id]);
    logActivity('UPDATE', 'output', $id, 'PO '.(int)$_POST['po_id'].' — '.$_POST['output_date']);
    setFlash('success', 'Output entry updated.');
} elseif ($action === 'delete') {
    requirePermission('output', 'delete');
    $db->prepare("DELETE FROM output_records WHERE output_id = ?")->execute([$id]);
    logActivity('DELETE', 'output', $id, null);
    setFlash('success', 'Output entry deleted.');
}
redirect('/modules/output/list.php');
END_OF_FILE;

// ── modules/fob/list.php ──────────────────────────────────────────────────
$files['modules/fob/list.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
requirePermission('fob_shipment', 'view');

$db = getDB();
$status = $_GET['status'] ?? '';
$where  = $status ? "WHERE fs.status = " . $db->quote($status) : "";
$rows = $db->query(
    "SELECT fs.*, d.destination_name, COUNT(fi.fob_item_id) AS po_count, SUM(fi.qty_shipped) AS total_qty, SUM(fi.cartons) AS total_cartons
     FROM fob_shipments fs LEFT JOIN destinations d ON d.destination_id = fs.destination_id
     LEFT JOIN fob_shipment_items fi ON fi.fob_id = fs.fob_id $where GROUP BY fs.fob_id ORDER BY fs.fob_id DESC LIMIT 200"
)->fetchAll();

$pageTitle = 'FOB Shipments';
include __DIR__ . '/../../includes/header.php';
$flash = getFlash();
if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
    <?= h($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2"><h5 class="mb-0">FOB Shipments</h5><span class="badge bg-secondary"><?= count($rows) ?></span></div>
    <div class="d-flex gap-2">
        <select onchange="location.href='list.php?status='+this.value" class="form-select form-select-sm" style="width:auto">
            <option value="">All Statuses</option>
            <?php foreach (['BOOKING','LOADED','IN_TRANSIT','ARRIVED','CANCELLED'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
        <?php if (can('fob_shipment', 'create')): ?><a href="form.php" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> New Shipment</a><?php endif; ?>
    </div>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0 align-middle">
<thead class="table-light"><tr><th>Booking Ref</th><th>Vessel / Flight</th><th>ETD</th><th>ETA</th><th>Destination</th><th>Buyer</th><th class="text-end">POs</th><th class="text-end">Qty</th><th class="text-end">Cartons</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r):
    $badge = match($r['status']) {'BOOKING'=>'secondary','LOADED'=>'info','IN_TRANSIT'=>'primary','ARRIVED'=>'success','CANCELLED'=>'danger',default=>'secondary'};
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
        <?php if (can('fob_shipment', 'edit')): ?><a href="form.php?id=<?= (int)$r['fob_id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a><?php endif; ?>
        <?php if (can('fob_shipment', 'delete')): ?>
        <form method="post" action="action.php" class="d-inline" onsubmit="return confirm('Delete shipment <?= h($r['booking_ref']) ?>?')">
            <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['fob_id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="11" class="text-center text-muted py-4">No FOB shipments yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/fob/form.php ──────────────────────────────────────────────────
$files['modules/fob/form.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db     = getDB();
$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

if ($isEdit) {
    requirePermission('fob_shipment', 'edit');
    $stmt = $db->prepare("SELECT * FROM fob_shipments WHERE fob_id = ?");
    $stmt->execute([$id]);
    $fob = $stmt->fetch();
    if (!$fob) { redirect('/modules/fob/list.php'); }
    $istmt = $db->prepare("SELECT fi.*, po.po_number, po.style_no FROM fob_shipment_items fi LEFT JOIN purchase_orders po ON po.po_id = fi.po_id WHERE fi.fob_id = ? ORDER BY fi.fob_item_id");
    $istmt->execute([$id]);
    $existingItems = $istmt->fetchAll();
} else {
    requirePermission('fob_shipment', 'create');
    $fob = ['booking_ref'=>'','vessel_flight'=>'','etd'=>'','eta'=>'','destination_id'=>'','buyer'=>'','status'=>'BOOKING','remarks'=>''];
    $existingItems = [];
}

$destinations = $db->query("SELECT destination_id, destination_name FROM destinations ORDER BY destination_name")->fetchAll();
$pos          = $db->query("SELECT po_id, po_number, style_no FROM purchase_orders ORDER BY po_number")->fetchAll();
$pageTitle = $isEdit ? 'Edit FOB Shipment' : 'New FOB Shipment';
include __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex align-items-center mb-3 gap-2">
    <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $isEdit ? 'Edit FOB Shipment' : 'New FOB Shipment' ?></h5>
</div>
<form method="post" action="action.php">
<?= csrfField() ?>
<input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
<input type="hidden" name="id" value="<?= (int)($fob['fob_id'] ?? 0) ?>">
<div class="card border-0 shadow-sm mb-3">
<div class="card-header fw-semibold" style="background:#f8fafc">Shipment Details</div>
<div class="card-body"><div class="row g-3">
    <div class="col-md-4"><label class="form-label fw-semibold">Booking Ref<span class="text-danger">*</span></label><input type="text" name="booking_ref" class="form-control" value="<?= h($fob['booking_ref']) ?>" required maxlength="100" placeholder="e.g. BKG-2026-001"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Vessel / Flight</label><input type="text" name="vessel_flight" class="form-control" value="<?= h($fob['vessel_flight']) ?>" maxlength="100"></div>
    <div class="col-md-4"><label class="form-label fw-semibold">Buyer / Consignee</label><input type="text" name="buyer" class="form-control" value="<?= h($fob['buyer']) ?>" maxlength="150"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">ETD</label><input type="date" name="etd" class="form-control" value="<?= h($fob['etd']) ?>"></div>
    <div class="col-md-3"><label class="form-label fw-semibold">ETA</label><input type="date" name="eta" class="form-control" value="<?= h($fob['eta']) ?>"></div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Destination</label>
        <select name="destination_id" class="form-select">
            <option value="">— select —</option>
            <?php foreach ($destinations as $d): ?><option value="<?= (int)$d['destination_id'] ?>" <?= (string)$fob['destination_id']===(string)$d['destination_id']?'selected':'' ?>><?= h($d['destination_name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold">Status</label>
        <select name="status" class="form-select">
            <?php foreach (['BOOKING','LOADED','IN_TRANSIT','ARRIVED','CANCELLED'] as $s): ?><option value="<?= $s ?>" <?= $fob['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-12"><label class="form-label fw-semibold">Remarks</label><input type="text" name="remarks" class="form-control" value="<?= h($fob['remarks']) ?>" maxlength="255"></div>
</div></div></div>
<div class="card border-0 shadow-sm mb-3">
<div class="card-header d-flex justify-content-between align-items-center" style="background:#f8fafc">
    <span class="fw-semibold">Purchase Orders in This Shipment</span>
    <button type="button" class="btn btn-outline-dark btn-sm" onclick="addItemRow()"><i class="bi bi-plus-lg"></i> Add PO</button>
</div>
<div class="card-body p-0"><div class="table-responsive">
<table class="table table-sm mb-0 align-middle" id="itemsTable">
<thead class="table-light"><tr><th style="width:35%">Purchase Order</th><th style="width:20%">Qty Shipped</th><th style="width:15%">Cartons</th><th>Notes</th><th style="width:40px"></th></tr></thead>
<tbody id="itemsBody">
<?php
$renderItems = $existingItems ?: [['po_id'=>null,'qty_shipped'=>0,'cartons'=>0,'notes'=>'']];
foreach ($renderItems as $item): ?>
<tr class="item-row">
    <td>
        <select name="item_po_id[]" class="form-select form-select-sm">
            <option value="">— PO —</option>
            <?php foreach ($pos as $po): ?><option value="<?= (int)$po['po_id'] ?>" <?= (string)($item['po_id']??'')===(string)$po['po_id']?'selected':'' ?>><?= h($po['po_number']) ?><?= $po['style_no']?' / '.h($po['style_no']):'' ?></option><?php endforeach; ?>
        </select>
    </td>
    <td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="<?= (int)($item['qty_shipped']??0) ?>" min="0"></td>
    <td><input type="number" name="item_cartons[]" class="form-control form-control-sm" value="<?= (int)($item['cartons']??0) ?>" min="0"></td>
    <td><input type="text" name="item_notes[]" class="form-control form-control-sm" value="<?= h($item['notes']??'') ?>" maxlength="255"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div></div></div>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-dark"><?= $isEdit ? 'Save Changes' : 'Create Shipment' ?></button>
    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
<script>
const poOptions = <?= json_encode('<option value="">— PO —</option>'.implode('',array_map(fn($po)=>'<option value="'.(int)$po['po_id'].'">'.htmlspecialchars($po['po_number'],ENT_QUOTES).($po['style_no']?' / '.htmlspecialchars($po['style_no'],ENT_QUOTES):'').'</option>',$pos))) ?>;
function addItemRow(){const tbody=document.getElementById('itemsBody');const tr=document.createElement('tr');tr.className='item-row';tr.innerHTML=`<td><select name="item_po_id[]" class="form-select form-select-sm">${poOptions}</select></td><td><input type="number" name="item_qty[]" class="form-control form-control-sm" value="0" min="0"></td><td><input type="number" name="item_cartons[]" class="form-control form-control-sm" value="0" min="0"></td><td><input type="text" name="item_notes[]" class="form-control form-control-sm" maxlength="255"></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>`;tbody.appendChild(tr);}
function removeRow(btn){const rows=document.querySelectorAll('#itemsBody .item-row');if(rows.length>1){btn.closest('tr').remove();}}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
END_OF_FILE;

// ── modules/fob/action.php ────────────────────────────────────────────────
$files['modules/fob/action.php'] = <<<'END_OF_FILE'
<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();
verifyCsrf();

$db     = getDB();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

function saveFobItems(PDO $db, int $fobId): void {
    $poIds   = $_POST['item_po_id']   ?? [];
    $qtys    = $_POST['item_qty']     ?? [];
    $cartons = $_POST['item_cartons'] ?? [];
    $notes   = $_POST['item_notes']   ?? [];
    $db->prepare("DELETE FROM fob_shipment_items WHERE fob_id = ?")->execute([$fobId]);
    $stmt = $db->prepare("INSERT INTO fob_shipment_items (fob_id, po_id, qty_shipped, cartons, notes) VALUES (:fob, :po, :qty, :cart, :nt)");
    foreach ($poIds as $i => $poId) {
        if (!$poId && !($qtys[$i] ?? 0)) { continue; }
        $stmt->execute(['fob'=>$fobId,'po'=>$poId?:null,'qty'=>(int)($qtys[$i]??0),'cart'=>(int)($cartons[$i]??0),'nt'=>trim($notes[$i]??'')?:null]);
    }
}

if ($action === 'create') {
    requirePermission('fob_shipment', 'create');
    $stmt = $db->prepare("INSERT INTO fob_shipments (booking_ref, vessel_flight, etd, eta, destination_id, buyer, status, remarks, created_by) VALUES (:br, :vf, :etd, :eta, :dest, :buyer, :st, :rem, :cb)");
    $stmt->execute(['br'=>trim($_POST['booking_ref']??''),'vf'=>trim($_POST['vessel_flight']??'')?:null,'etd'=>$_POST['etd']?:null,'eta'=>$_POST['eta']?:null,'dest'=>$_POST['destination_id']?:null,'buyer'=>trim($_POST['buyer']??'')?:null,'st'=>$_POST['status']??'BOOKING','rem'=>trim($_POST['remarks']??'')?:null,'cb'=>$_SESSION['user_id']??null]);
    $newId = (int)$db->lastInsertId();
    saveFobItems($db, $newId);
    logActivity('CREATE', 'fob_shipment', $newId, trim($_POST['booking_ref']??''));
    setFlash('success', 'FOB shipment created.');
} elseif ($action === 'update') {
    requirePermission('fob_shipment', 'edit');
    $stmt = $db->prepare("UPDATE fob_shipments SET booking_ref=:br, vessel_flight=:vf, etd=:etd, eta=:eta, destination_id=:dest, buyer=:buyer, status=:st, remarks=:rem WHERE fob_id=:id");
    $stmt->execute(['br'=>trim($_POST['booking_ref']??''),'vf'=>trim($_POST['vessel_flight']??'')?:null,'etd'=>$_POST['etd']?:null,'eta'=>$_POST['eta']?:null,'dest'=>$_POST['destination_id']?:null,'buyer'=>trim($_POST['buyer']??'')?:null,'st'=>$_POST['status']??'BOOKING','rem'=>trim($_POST['remarks']??'')?:null,'id'=>$id]);
    saveFobItems($db, $id);
    logActivity('UPDATE', 'fob_shipment', $id, trim($_POST['booking_ref']??''));
    setFlash('success', 'FOB shipment updated.');
} elseif ($action === 'delete') {
    requirePermission('fob_shipment', 'delete');
    $db->prepare("DELETE FROM fob_shipments WHERE fob_id = ?")->execute([$id]);
    logActivity('DELETE', 'fob_shipment', $id, null);
    setFlash('success', 'FOB shipment deleted.');
}
redirect('/modules/fob/list.php');
END_OF_FILE;

// ── includes/header.php (updated) ─────────────────────────────────────────
$files['includes/header.php'] = <<<'END_OF_FILE'
<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$companyName = setting('company_name', 'Your Company');
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));

$canOperations = can('priorities','view') || can('purchase_orders','view') || can('receiving','view') || can('qc','view') || can('putaway','view') || can('gatepass','view') || can('rss','view') || can('subcons','view') || can('tickets','view') || can('output','view') || can('fob_shipment','view');
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
<a href="#" class="nav-icon <?= $currentDir==='priorities'||$currentDir==='po'||$currentDir==='receiving'||$currentDir==='qc'||$currentDir==='putaway'||$currentDir==='gatepass'||$currentDir==='rss'||$currentDir==='subcon'||$currentDir==='tickets'||$currentDir==='output'||$currentDir==='fob'?'active':'' ?>" title="Operations"><i class="bi bi-gear-wide-connected"></i></a>
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
$pageTitle = 'Module Installer';
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center mb-3 gap-2">
    <h5 class="mb-0"><i class="bi bi-download me-2"></i>Missing Modules Installer</h5>
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
<div class="card border-0 shadow-sm mb-3" style="max-width:640px">
    <div class="card-body">
        <p class="mb-2">This will create the following files on the server:</p>
        <ul class="small mb-3">
            <?php foreach (array_keys($files) as $f): ?><li><code><?= h($f) ?></code></li><?php endforeach; ?>
        </ul>
        <div class="alert alert-warning mb-3">
            <strong>Run the SQL migration first</strong> in phpMyAdmin before clicking Install.
            <br>File: <code>warehouse_backups/migration_missing_modules.sql</code> (run from your local copy).
        </div>
        <form method="post">
            <button type="submit" name="go" value="1" class="btn btn-dark"
                    onclick="return confirm('Write all module files to the server?')">
                <i class="bi bi-play-fill me-1"></i> Install Now
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($done && !$errors): ?>
<div class="alert alert-info">
    <strong>Next steps:</strong>
    <ol class="mb-0 mt-2">
        <li>Run <code>migration_missing_modules.sql</code> in phpMyAdmin.</li>
        <li>Delete this file: <strong>delete <code>install.php</code></strong> from the server root after install.</li>
        <li>Reload the warehouse — the Operations flyout will show Tickets, Output Records &amp; FOB Shipment.</li>
    </ol>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
