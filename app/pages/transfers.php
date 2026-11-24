<?php
defined('APP') || exit;
if (is_post()) {
    $pid = (int) $_POST['product_id']; $from = (int) $_POST['from_location']; $to = (int) $_POST['to_location']; $qty = (int) $_POST['qty'];
    if (!$pid || $qty < 1 || $from === $to) flash('Pick a product, a quantity above zero and two different locations.', 'err');
    elseif (atomic(function () use ($pid, $from, $to, $qty) {
        move_stock($pid, $from, -$qty, 'transfer out'); move_stock($pid, $to, $qty, 'transfer in');
        run('INSERT INTO transfers (product_id, from_location, to_location, qty, created_at) VALUES (?,?,?,?,?)', [$pid, $from, $to, $qty, now()]);
    })) flash('Transfer complete.');
    redirect('index.php?p=transfers');
}
$rows = q('SELECT t.*, p.name AS pname, a.name AS fname, b.name AS tname FROM transfers t JOIN products p ON p.id = t.product_id
           JOIN locations a ON a.id = t.from_location JOIN locations b ON b.id = t.to_location ORDER BY t.id DESC LIMIT 100');
$locs = locations();
layout_start('Transfers', 'transfers');
?>
<div class="grid2 wide-left">
  <div class="card"><table><thead><tr><th>When</th><th>Product</th><th>From</th><th>To</th><th class="r">Qty</th></tr></thead><tbody>
  <?php foreach ($rows as $t): ?><tr><td><?= e($t['created_at']) ?></td><td><?= e($t['pname']) ?></td><td><?= e($t['fname']) ?></td><td><?= e($t['tname']) ?></td><td class="r"><?= (int) $t['qty'] ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No transfers yet.</td></tr><?php endif; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Move stock</h2><?= csrf_field() ?>
    <label>Product<select name="product_id" required><option value="">Choose…</option><?php foreach (active_products() as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <label>From<select name="from_location"><?= options($locs) ?></select></label>
    <label>To<select name="to_location"><?= options($locs, $locs[1]['id'] ?? null) ?></select></label>
    <label>Quantity<input type="number" min="1" name="qty" required></label><button class="btn primary">Transfer</button></form>
</div>
<?php layout_end();
