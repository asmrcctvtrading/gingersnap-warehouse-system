<?php
defined('APP') || exit;
if (is_post()) {
    if (trim($_POST['name'] ?? '') === '') flash('Customer name is required.', 'err');
    else { insert('INSERT INTO customers (name,email,phone) VALUES (?,?,?)', [trim($_POST['name']), trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? '')]); flash('Customer added.'); }
    redirect('index.php?p=customers');
}
$rows = q("SELECT c.*, (SELECT COUNT(*) FROM sales_orders WHERE customer_id = c.id AND status <> 'cancelled') AS orders,
           (SELECT COALESCE(SUM(i.qty*i.price),0) FROM sales_orders o JOIN so_items i ON i.so_id = o.id WHERE o.customer_id = c.id AND o.status = 'shipped') AS spent
           FROM customers c ORDER BY c.name LIMIT 300");
layout_start('Customers', 'customers');
?>
<div class="grid2 wide-left">
  <div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th class="r">Orders</th><th class="r">Shipped value</th></tr></thead><tbody>
  <?php foreach ($rows as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= e($c['email']) ?></td><td><?= e($c['phone']) ?></td><td class="r"><?= (int) $c['orders'] ?></td><td class="r"><?= money($c['spent']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No customers yet.</td></tr><?php endif; ?></tbody></table></div>
  <form method="post" class="card form"><h2>Add customer</h2><?= csrf_field() ?>
    <label>Name<input name="name" required></label><label>Email<input type="email" name="email"></label><label>Phone<input name="phone"></label><button class="btn primary">Add</button></form>
</div>
<?php layout_end();
