<?php
defined('APP') || exit;
$tab = ($_GET['tab'] ?? 'po') === 'suppliers' ? 'suppliers' : 'po';

if (is_post()) {
    $act = $_POST['action'] ?? '';
    if ($act === 'supplier') {
        if (trim($_POST['name'] ?? '') === '') flash('Supplier name is required.', 'err');
        else { insert('INSERT INTO suppliers (name,email,phone) VALUES (?,?,?)', [trim($_POST['name']), trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? '')]); flash('Supplier added.'); }
        redirect('index.php?p=purchasing&tab=suppliers');
    }
    if ($act === 'create') {
        $lines = [];
        foreach ((array) ($_POST['pid'] ?? []) as $i => $pid) {
            $qty = (int) ($_POST['qty'][$i] ?? 0);
            if ((int) $pid && $qty > 0) $lines[] = [(int) $pid, $qty, (float) ($_POST['cost'][$i] ?? 0)];
        }
        $sup = (int) $_POST['supplier_id'];
        if (!$lines || !$sup) flash('Choose a supplier and at least one product.', 'err');
        elseif (atomic(function () use ($lines, $sup) {
            $exp = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expected_date'] ?? '') ? $_POST['expected_date'] : null;
            $id = insert("INSERT INTO purchase_orders (po_no, supplier_id, location_id, status, expected_date, created_at) VALUES ('tmp',?,?,'draft',?,?)", [$sup, (int) $_POST['location_id'], $exp, now()]);
            doc_no('purchase_orders', 'po_no', 'PO-', $id);
            foreach ($lines as [$pid, $qty, $cost]) run('INSERT INTO po_items (po_id, product_id, qty, unit_cost) VALUES (?,?,?,?)', [$id, $pid, $qty, $cost]);
        })) flash('Purchase order created as draft.');
    } else {
        $po = one('SELECT * FROM purchase_orders WHERE id = ?', [(int) $_POST['id']]);
        if ($po && $act === 'order' && $po['status'] === 'draft') { run("UPDATE purchase_orders SET status='ordered' WHERE id=?", [$po['id']]); flash('Marked as ordered.'); }
        elseif ($po && $act === 'receive' && $po['status'] === 'ordered') {
            if (atomic(function () use ($po) {
                foreach (q('SELECT * FROM po_items WHERE po_id = ?', [$po['id']]) as $it) {
                    move_stock((int) $it['product_id'], (int) $po['location_id'], (int) $it['qty'], 'purchase', $po['po_no']);
                    run('UPDATE products SET cost = ? WHERE id = ?', [$it['unit_cost'], $it['product_id']]);
                }
                run("UPDATE purchase_orders SET status='received' WHERE id=?", [$po['id']]);
            })) flash($po['po_no'] . ' received into stock.');
        } elseif ($po && $act === 'cancel' && in_array($po['status'], ['draft', 'ordered'], true)) { run("UPDATE purchase_orders SET status='cancelled' WHERE id=?", [$po['id']]); flash('Purchase order cancelled.'); }
    }
    redirect('index.php?p=purchasing');
}

layout_start('Purchasing', 'purchasing');
?>
<div class="tabs"><a class="<?= $tab === 'po' ? 'on' : '' ?>" href="index.php?p=purchasing">Purchase orders</a><a class="<?= $tab === 'suppliers' ? 'on' : '' ?>" href="index.php?p=purchasing&tab=suppliers">Suppliers</a></div>
<?php if ($tab === 'suppliers'): $sups = q('SELECT * FROM suppliers ORDER BY name'); ?>
<div class="grid2 wide-left">
  <div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>
  <?php foreach ($sups as $s): ?><tr><td><?= e($s['name']) ?></td><td><?= e($s['email']) ?></td><td><?= e($s['phone']) ?></td></tr>
  <?php endforeach; if (!$sups): ?><tr><td colspan="3" class="muted">No suppliers yet.</td></tr><?php endif; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Add supplier</h2><?= csrf_field() ?><input type="hidden" name="action" value="supplier">
    <label>Name<input name="name" required></label><label>Email<input type="email" name="email"></label><label>Phone<input name="phone"></label><button class="btn primary">Add</button></form>
</div>
<?php else:
  $pos = q('SELECT po.*, s.name AS sname, l.name AS lname, (SELECT SUM(qty*unit_cost) FROM po_items WHERE po_id = po.id) AS total
            FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id JOIN locations l ON l.id = po.location_id ORDER BY po.id DESC LIMIT 200');
  $prods = active_products(); ?>
<div class="card"><table><thead><tr><th>PO</th><th>Supplier</th><th>Deliver to</th><th>Expected</th><th class="r">Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($pos as $po): ?>
  <tr><td><?= e($po['po_no']) ?></td><td><?= e($po['sname']) ?></td><td><?= e($po['lname']) ?></td><td><?= e($po['expected_date'] ?? '—') ?></td><td class="r"><?= money($po['total']) ?></td><td><?= badge($po['status']) ?></td>
  <td class="r"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $po['id'] ?>">
    <?php if ($po['status'] === 'draft'): ?><button class="btn sm" name="action" value="order">Mark ordered</button><?php endif; ?>
    <?php if ($po['status'] === 'ordered'): ?><button class="btn sm" name="action" value="receive">Receive</button><?php endif; ?>
    <?php if (in_array($po['status'], ['draft', 'ordered'], true)): ?><button class="btn sm ghost" name="action" value="cancel" onclick="return confirm('Cancel this PO?')">Cancel</button><?php endif; ?></form></td></tr>
<?php endforeach; if (!$pos): ?><tr><td colspan="7" class="muted">No purchase orders yet.</td></tr><?php endif; ?></tbody></table></div>

<form method="post" class="card form neworder"><h2>New purchase order</h2><?= csrf_field() ?><input type="hidden" name="action" value="create">
  <div class="two"><label>Supplier<select name="supplier_id" required><option value="">Choose…</option><?= options(q('SELECT id, name FROM suppliers ORDER BY name')) ?></select></label>
  <label>Deliver to<select name="location_id"><?= options(locations()) ?></select></label></div>
  <label>Expected date<input type="date" name="expected_date"></label>
  <div id="lines"><div class="line three"><select name="pid[]"><option value="">Product…</option><?php foreach ($prods as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
    <input type="number" min="1" name="qty[]" placeholder="Qty"><input type="number" min="0" step="0.01" name="cost[]" placeholder="Unit cost"></div></div>
  <button type="button" class="btn ghost sm" id="addLine">+ Add line</button> <button class="btn primary">Create PO</button>
</form>
<?php endif; layout_end();
