<?php
defined('APP') || exit;
$locs = locations();

if (is_post()) {
    $pid = (int) $_POST['product_id']; $loc = (int) $_POST['location_id']; $delta = (int) $_POST['delta'];
    $why = trim($_POST['reason'] ?? '') ?: 'adjustment';
    if ($pid && $loc && $delta !== 0) {
        if (atomic(fn() => move_stock($pid, $loc, $delta, 'adjust', $why))) flash('Stock adjusted.');
    } else flash('Pick a product, a location and a non-zero change.', 'err');
    redirect('index.php?p=stock');
}

$prods = q('SELECT id, sku, name, unit, reorder_level FROM products WHERE active = 1 ORDER BY name');
$map = [];
foreach (q('SELECT product_id, location_id, qty FROM stock') as $s) $map[$s['product_id']][$s['location_id']] = (int) $s['qty'];

layout_start('Stock', 'stock');
?>
<div class="grid2 wide-left">
  <div class="card">
    <table><thead><tr><th>Product</th><?php foreach ($locs as $l): ?><th class="r"><?= e($l['name']) ?></th><?php endforeach; ?><th class="r">Total</th></tr></thead><tbody>
    <?php foreach ($prods as $p): $t = array_sum($map[$p['id']] ?? []); ?>
      <tr><td><?= e($p['name']) ?> <small class="muted"><?= e($p['sku']) ?></small></td>
      <?php foreach ($locs as $l): ?><td class="r"><?= (int) ($map[$p['id']][$l['id']] ?? 0) ?></td><?php endforeach; ?>
      <td class="r <?= $t <= $p['reorder_level'] ? 'bad' : '' ?>"><b><?= $t ?></b></td></tr>
    <?php endforeach; if (!$prods): ?><tr><td colspan="<?= count($locs) + 2 ?>" class="muted">Add products first.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
  <form method="post" class="card form">
    <h2>Manual adjustment</h2><?= csrf_field() ?>
    <label>Product<select name="product_id" required><option value="">Choose…</option><?php foreach ($prods as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <label>Location<select name="location_id" required><?= options($locs) ?></select></label>
    <label>Change (+ add, − remove)<input type="number" name="delta" required></label>
    <label>Reason<input name="reason" placeholder="e.g. damaged, opening stock"></label>
    <button class="btn primary">Apply</button>
  </form>
</div>
<?php layout_end();
