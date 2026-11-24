<?php
defined('APP') || exit;
if (is_post()) {
    $pid = (int) $_POST['product_id']; $loc = (int) $_POST['location_id'];
    if (!$pid || !isset($_POST['counted']) || $_POST['counted'] === '' || (int) $_POST['counted'] < 0) flash('Pick a product and enter the counted quantity.', 'err');
    else {
        $counted = (int) $_POST['counted'];
        $exp = (int) val('SELECT qty FROM stock WHERE product_id = ? AND location_id = ?', [$pid, $loc]);
        if (atomic(function () use ($pid, $loc, $counted, $exp) {
            if ($counted !== $exp) move_stock($pid, $loc, $counted - $exp, 'cycle count');
            run('INSERT INTO cycle_counts (product_id, location_id, expected, counted, created_at) VALUES (?,?,?,?,?)', [$pid, $loc, $exp, $counted, now()]);
        })) flash($counted === $exp ? 'Count matches the system. No change needed.' : 'Count saved and stock corrected by ' . ($counted - $exp) . '.');
    }
    redirect('index.php?p=counts');
}
$rows = q('SELECT c.*, p.name AS pname, l.name AS lname FROM cycle_counts c JOIN products p ON p.id = c.product_id JOIN locations l ON l.id = c.location_id ORDER BY c.id DESC LIMIT 100');
layout_start('Cycle counts', 'counts');
?>
<div class="grid2 wide-left">
  <div class="card"><table><thead><tr><th>When</th><th>Product</th><th>Where</th><th class="r">Expected</th><th class="r">Counted</th><th class="r">Variance</th></tr></thead><tbody>
  <?php foreach ($rows as $c): $v = $c['counted'] - $c['expected']; ?><tr><td><?= e($c['created_at']) ?></td><td><?= e($c['pname']) ?></td><td><?= e($c['lname']) ?></td>
    <td class="r"><?= (int) $c['expected'] ?></td><td class="r"><?= (int) $c['counted'] ?></td><td class="r <?= $v < 0 ? 'bad' : ($v > 0 ? 'good' : '') ?>"><?= $v > 0 ? '+' : '' ?><?= $v ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted">No counts yet.</td></tr><?php endif; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Record a count</h2><?= csrf_field() ?>
    <label>Product<select name="product_id" required><option value="">Choose…</option><?php foreach (active_products() as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <label>Location<select name="location_id"><?= options(locations()) ?></select></label>
    <label>Counted quantity<input type="number" min="0" name="counted" required></label>
    <button class="btn primary">Save count</button><p class="muted">Any difference is applied to stock straight away.</p></form>
</div>
<?php layout_end();
