<?php
defined('APP') || exit;
$skus = (int) val('SELECT COUNT(*) FROM products WHERE active = 1');
$value = (float) val('SELECT COALESCE(SUM(s.qty * p.cost),0) FROM stock s JOIN products p ON p.id = s.product_id');
$low = q('SELECT p.id, p.sku, p.name, p.reorder_level, COALESCE(SUM(s.qty),0) AS total
          FROM products p LEFT JOIN stock s ON s.product_id = p.id
          WHERE p.active = 1 GROUP BY p.id, p.sku, p.name, p.reorder_level
          HAVING COALESCE(SUM(s.qty),0) <= p.reorder_level ORDER BY total LIMIT 8');
$soon = date('Y-m-d', strtotime('+30 days'));
$exp = q("SELECT b.*, p.name AS pname FROM batches b JOIN products p ON p.id = b.product_id
          WHERE b.status = 'released' AND b.expiry_date IS NOT NULL AND b.expiry_date <= ? ORDER BY b.expiry_date LIMIT 8", [$soon]);
$open = (int) val("SELECT COUNT(*) FROM sales_orders WHERE status IN ('new','picking','packed')");

// Shipped revenue for the last 7 days
$days = [];
for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i days"))] = 0.0;
foreach (q("SELECT o.shipped_at, i.qty * i.price AS amt FROM sales_orders o JOIN so_items i ON i.so_id = o.id
            WHERE o.status = 'shipped' AND o.shipped_at >= ?", [array_key_first($days) . ' 00:00:00']) as $r) {
    $d = substr($r['shipped_at'], 0, 10);
    if (isset($days[$d])) $days[$d] += (float) $r['amt'];
}
$max = max(1, max($days));
$moves = q('SELECT m.*, p.name AS pname, l.name AS lname FROM stock_moves m JOIN products p ON p.id = m.product_id
            JOIN locations l ON l.id = m.location_id ORDER BY m.id DESC LIMIT 8');

layout_start('Dashboard', 'dashboard');
?>
<div class="kpis">
  <div class="card kpi"><span>Active products</span><b><?= $skus ?></b></div>
  <div class="card kpi"><span>Stock value (at cost)</span><b><?= money($value) ?></b></div>
  <div class="card kpi <?= $low ? 'warn' : '' ?>"><span>Low stock items</span><b><?= count($low) ?></b></div>
  <div class="card kpi"><span>Open orders</span><b><?= $open ?></b></div>
</div>

<div class="grid2">
  <div class="card">
    <h2>Shipped sales, last 7 days</h2>
    <svg class="chart" viewBox="0 0 350 150" role="img" aria-label="Shipped sales per day">
    <?php $i = 0; foreach ($days as $d => $v): $h = $v / $max * 100; $x = 10 + $i * 48; ?>
      <rect class="bar" x="<?= $x ?>" y="<?= 120 - $h ?>" width="34" height="<?= $h ?>" rx="4" style="animation-delay:<?= $i * 60 ?>ms"><title><?= e($d . ': ' . money($v)) ?></title></rect>
      <text x="<?= $x + 17 ?>" y="140" text-anchor="middle"><?= e(date('D', strtotime($d))) ?></text>
    <?php $i++; endforeach; ?>
    </svg>
  </div>
  <div class="card">
    <h2>Needs reordering</h2>
    <?php if (!$low): ?><p class="muted">Everything is above its reorder level.</p><?php else: ?>
    <table><thead><tr><th>Product</th><th class="r">In stock</th><th class="r">Reorder at</th></tr></thead><tbody>
    <?php foreach ($low as $r): ?>
      <tr><td><?= e($r['name']) ?> <small class="muted"><?= e($r['sku']) ?></small></td><td class="r"><?= (int) $r['total'] ?></td><td class="r"><?= (int) $r['reorder_level'] ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
</div>

<div class="grid2">
  <div class="card">
    <h2>Expiring within 30 days</h2>
    <?php if (!$exp): ?><p class="muted">No released batches are close to expiry.</p><?php else: ?>
    <table><thead><tr><th>Product</th><th>Lot</th><th>Expires</th></tr></thead><tbody>
    <?php foreach ($exp as $b): ?>
      <tr><td><?= e($b['pname']) ?></td><td><?= e($b['lot_no']) ?></td><td class="<?= $b['expiry_date'] < today() ? 'bad' : 'warnt' ?>"><?= e($b['expiry_date']) ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
  <div class="card">
    <h2>Recent stock movements</h2>
    <?php if (!$moves): ?><p class="muted">No movements yet.</p><?php else: ?>
    <table><thead><tr><th>Product</th><th>Where</th><th class="r">Change</th><th>Why</th></tr></thead><tbody>
    <?php foreach ($moves as $m): ?>
      <tr><td><?= e($m['pname']) ?></td><td><?= e($m['lname']) ?></td>
      <td class="r <?= $m['delta'] < 0 ? 'bad' : 'good' ?>"><?= $m['delta'] > 0 ? '+' : '' ?><?= (int) $m['delta'] ?></td><td><?= e($m['reason']) ?></td></tr>
    <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
</div>
<?php layout_end();
