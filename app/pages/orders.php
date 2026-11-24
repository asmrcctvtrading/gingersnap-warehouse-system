<?php
defined('APP') || exit;
const FLOW = ['new' => 'picking', 'picking' => 'packed', 'packed' => 'shipped'];
const FLOW_LABEL = ['new' => 'Start picking', 'picking' => 'Mark packed', 'packed' => 'Ship'];

function read_lines(): array
{
    $lines = [];
    foreach ((array) ($_POST['pid'] ?? []) as $i => $pid) {
        $qty = (int) ($_POST['qty'][$i] ?? 0);
        if ((int) $pid && $qty > 0) $lines[(int) $pid] = ($lines[(int) $pid] ?? 0) + $qty;
    }
    return $lines;
}

if (is_post()) {
    $act = $_POST['action'] ?? '';
    if ($act === 'create') {
        $lines = read_lines();
        $cust = (int) $_POST['customer_id'];
        if (!$lines || !$cust) { flash('Choose a customer and at least one product.', 'err'); }
        elseif (atomic(function () use ($lines, $cust) {
            $id = insert("INSERT INTO sales_orders (so_no, customer_id, location_id, status, created_at) VALUES ('tmp',?,?,'new',?)", [$cust, (int) $_POST['location_id'], now()]);
            doc_no('sales_orders', 'so_no', 'SO-', $id);
            foreach ($lines as $pid => $qty) {
                run('INSERT INTO so_items (so_id, product_id, qty, price) VALUES (?,?,?,(SELECT price FROM products WHERE id = ?))', [$id, $pid, $qty, $pid]);
            }
        })) flash('Order created.');
    } else {
        $o = one('SELECT * FROM sales_orders WHERE id = ?', [(int) $_POST['id']]);
        if ($o && $act === 'advance' && isset(FLOW[$o['status']])) {
            $next = FLOW[$o['status']];
            if (atomic(function () use ($o, $next) {
                if ($next === 'shipped') {
                    foreach (q('SELECT product_id, qty FROM so_items WHERE so_id = ?', [$o['id']]) as $it)
                        move_stock((int) $it['product_id'], (int) $o['location_id'], -(int) $it['qty'], 'sale', $o['so_no']);
                    run("UPDATE sales_orders SET status='shipped', shipped_at=? WHERE id=?", [now(), $o['id']]);
                } else run('UPDATE sales_orders SET status=? WHERE id=?', [$next, $o['id']]);
            })) flash($o['so_no'] . ' is now ' . $next . '.');
        } elseif ($o && $act === 'cancel' && $o['status'] !== 'shipped') {
            run("UPDATE sales_orders SET status='cancelled' WHERE id=?", [$o['id']]); flash('Order cancelled.');
        }
    }
    redirect('index.php?p=orders');
}

$orders = q('SELECT o.*, c.name AS cname, (SELECT SUM(qty*price) FROM so_items WHERE so_id = o.id) AS total,
             (SELECT COUNT(*) FROM so_items WHERE so_id = o.id) AS n FROM sales_orders o JOIN customers c ON c.id = o.customer_id
             WHERE o.status <> \'cancelled\' ORDER BY o.id DESC LIMIT 200');
$cols = ['new' => 'New', 'picking' => 'Picking', 'packed' => 'Packed', 'shipped' => 'Shipped'];
$prods = q('SELECT id, name, sku FROM products WHERE active = 1 ORDER BY name');
layout_start('Orders', 'orders');
?>
<div class="board">
<?php foreach ($cols as $key => $label): $list = array_filter($orders, fn($o) => $o['status'] === $key); ?>
  <section class="col"><h3><?= e($label) ?> <span class="count"><?= count($list) ?></span></h3>
  <?php foreach ($list as $o): ?>
    <div class="card order">
      <div><b><?= e($o['so_no']) ?></b> <span class="muted"><?= (int) $o['n'] ?> line(s)</span></div>
      <div><?= e($o['cname']) ?></div>
      <div class="muted"><?= money($o['total']) ?></div>
      <?php if (isset(FLOW[$key])): ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
        <button class="btn sm" name="action" value="advance"><?= e(FLOW_LABEL[$key]) ?></button>
        <button class="btn sm ghost" name="action" value="cancel" onclick="return confirm('Cancel this order?')">Cancel</button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; if (!$list): ?><p class="muted empty">Nothing here.</p><?php endif; ?>
  </section>
<?php endforeach; ?>
</div>

<form method="post" class="card form neworder">
  <h2>New sales order</h2><?= csrf_field() ?><input type="hidden" name="action" value="create">
  <div class="two"><label>Customer<select name="customer_id" required><option value="">Choose…</option><?= options(q('SELECT id, name FROM customers ORDER BY name')) ?></select></label>
  <label>Ship from<select name="location_id"><?= options(locations()) ?></select></label></div>
  <div id="lines">
    <div class="line two"><select name="pid[]"><option value="">Product…</option><?php foreach ($prods as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['sku']) ?>)</option><?php endforeach; ?></select>
    <input type="number" min="1" name="qty[]" placeholder="Qty"></div>
  </div>
  <button type="button" class="btn ghost sm" id="addLine">+ Add line</button>
  <button class="btn primary">Create order</button>
  <p class="muted">Stock is deducted from the chosen location when the order is shipped.</p>
</form>
<?php layout_end();
