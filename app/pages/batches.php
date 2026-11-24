<?php
defined('APP') || exit;
$locs = locations();

if (is_post()) {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $pid = (int) $_POST['product_id']; $qty = (int) $_POST['qty']; $lot = trim($_POST['lot_no'] ?? '');
        $exp = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['expiry_date'] ?? '') ? $_POST['expiry_date'] : null;
        if ($pid && $qty > 0 && $lot !== '') {
            insert("INSERT INTO batches (product_id, lot_no, location_id, qty, expiry_date, status, created_at) VALUES (?,?,?,?,?,'quarantine',?)",
                [$pid, $lot, (int) $_POST['location_id'], $qty, $exp, now()]);
            flash('Batch logged in quarantine. Release it after QA to add it to stock.');
        } else flash('Product, lot number and a quantity above zero are required.', 'err');
    } else {
        $b = one('SELECT * FROM batches WHERE id = ?', [(int) $_POST['id']]);
        if ($b && $act === 'release' && $b['status'] === 'quarantine') {
            if (atomic(fn() => move_stock((int) $b['product_id'], (int) $b['location_id'], (int) $b['qty'], 'batch release', 'Lot ' . $b['lot_no']))) {
                run("UPDATE batches SET status='released' WHERE id=?", [$b['id']]); flash('Batch released into stock.');
            }
        } elseif ($b && $act === 'writeoff' && in_array($b['status'], ['released', 'quarantine'], true)) {
            $wasReleased = $b['status'] === 'released';
            if (atomic(function () use ($b, $wasReleased) {
                if ($wasReleased) move_stock((int) $b['product_id'], (int) $b['location_id'], -(int) $b['qty'], 'batch write-off', 'Lot ' . $b['lot_no']);
                run("UPDATE batches SET status='expired' WHERE id=?", [$b['id']]);
            })) flash('Batch written off.');
        }
    }
    redirect('index.php?p=batches');
}

$rows = q('SELECT b.*, p.name AS pname, l.name AS lname FROM batches b JOIN products p ON p.id = b.product_id JOIN locations l ON l.id = b.location_id
           ORDER BY b.status = \'expired\', b.expiry_date IS NULL, b.expiry_date LIMIT 300');
layout_start('Batches & expiry', 'batches');
?>
<div class="grid2 wide-left">
  <div class="card">
    <table><thead><tr><th>Product</th><th>Lot</th><th>Where</th><th class="r">Qty</th><th>Expires</th><th>QA</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $b): $late = $b['expiry_date'] && $b['expiry_date'] < today() && $b['status'] !== 'expired'; ?>
      <tr><td><?= e($b['pname']) ?></td><td><?= e($b['lot_no']) ?></td><td><?= e($b['lname']) ?></td><td class="r"><?= (int) $b['qty'] ?></td>
        <td class="<?= $late ? 'bad' : '' ?>"><?= e($b['expiry_date'] ?? '—') ?></td><td><?= badge($b['status']) ?></td>
        <td class="r">
        <?php if ($b['status'] === 'quarantine'): ?>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="release"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="btn sm">Release</button></form>
        <?php endif; if ($b['status'] !== 'expired'): ?>
          <form method="post" class="inline" data-confirm="Write off this batch?"><?= csrf_field() ?><input type="hidden" name="action" value="writeoff"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="btn sm ghost">Write off</button></form>
        <?php endif; ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">No batches yet.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
  <form method="post" class="card form">
    <h2>Log a production batch</h2><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label>Product<select name="product_id" required><option value="">Choose…</option><?php foreach (active_products() as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></label>
    <label>Lot number<input name="lot_no" required></label>
    <label>Location<select name="location_id"><?= options($locs) ?></select></label>
    <div class="two"><label>Quantity<input type="number" min="1" name="qty" required></label><label>Expiry date<input type="date" name="expiry_date"></label></div>
    <button class="btn primary">Add to quarantine</button>
  </form>
</div>
<?php layout_end();
