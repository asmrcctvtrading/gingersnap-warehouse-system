<?php
defined('APP') || exit;
if (is_post()) {
    $pid = (int) $_POST['product_id']; $loc = (int) $_POST['location_id']; $qty = (int) $_POST['qty']; $restock = !empty($_POST['restock']) ? 1 : 0;
    if (!$pid || $qty < 1) flash('Pick a product and a quantity above zero.', 'err');
    elseif (atomic(function () use ($pid, $loc, $qty, $restock) {
        if ($restock) move_stock($pid, $loc, $qty, 'return');
        run('INSERT INTO returns_log (product_id, location_id, qty, reason, restocked, created_at) VALUES (?,?,?,?,?,?)', [$pid, $loc, $qty, trim($_POST['reason'] ?? ''), $restock, now()]);
    })) flash($restock ? 'Return logged and put back in stock.' : 'Return logged (not restocked).');
    redirect('index.php?p=returns');
}
$rows = q('SELECT r.*, p.name AS pname, l.name AS lname FROM returns_log r JOIN products p ON p.id = r.product_id JOIN locations l ON l.id = r.location_id ORDER BY r.id DESC LIMIT 100');
layout_start('Returns', 'returns');
?>
<div class="grid2 wide-left">
  <div class="card"><table><thead><tr><th>When</th><th>Product</th><th>Where</th><th class="r">Qty</th><th>Reason</th><th>Restocked</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['pname']) ?></td><td><?= e($r['lname']) ?></td><td class="r"><?= (int) $r['qty'] ?></td><td><?= e($r['reason']) ?></td><td><?= $r['restocked'] ? 'Yes' : 'No' ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No returns yet.</td></tr><?php endif; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Log a return</h2><?= csrf_field() ?>
    <label>Product<select name="product_id" required><option value="">Choose…</option><?php foreach (active_products() as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <label>Return to<select name="location_id"><?= options(locations()) ?></select></label>
    <label>Quantity<input type="number" min="1" name="qty" required></label><label>Reason<input name="reason"></label>
    <label class="chk"><input type="checkbox" name="restock" value="1" checked> Put back into sellable stock</label><button class="btn primary">Log return</button></form>
</div>
<?php layout_end();
