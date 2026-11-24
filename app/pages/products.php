<?php
defined('APP') || exit;
$id = (int) ($_GET['id'] ?? 0);

if (is_post()) {
    $act = $_POST['action'] ?? '';
    if ($act === 'save') {
        $f = [trim($_POST['sku'] ?? ''), trim($_POST['name'] ?? ''), trim($_POST['category'] ?? ''), trim($_POST['unit'] ?? '') ?: 'pcs',
              (float) ($_POST['cost'] ?? 0), (float) ($_POST['price'] ?? 0), max(0, (int) ($_POST['reorder_level'] ?? 0))];
        if ($f[0] === '' || $f[1] === '') { flash('SKU and name are required.', 'err'); redirect('index.php?p=products' . ($id ? "&id=$id" : '')); }
        try {
            if ($id) { run('UPDATE products SET sku=?, name=?, category=?, unit=?, cost=?, price=?, reorder_level=? WHERE id=?', [...$f, $id]); }
            else { insert('INSERT INTO products (sku,name,category,unit,cost,price,reorder_level) VALUES (?,?,?,?,?,?,?)', $f); }
            flash('Product saved.');
        } catch (PDOException $ex) { flash('That SKU already exists.', 'err'); redirect('index.php?p=products' . ($id ? "&id=$id" : '')); }
    } elseif ($act === 'toggle') {
        run('UPDATE products SET active = 1 - active WHERE id = ?', [(int) $_POST['id']]);
        flash('Product updated.');
    }
    redirect('index.php?p=products');
}

$edit = $id ? one('SELECT * FROM products WHERE id = ?', [$id]) : null;
$search = trim($_GET['s'] ?? '');
$rows = q('SELECT p.*, COALESCE((SELECT SUM(qty) FROM stock WHERE product_id = p.id),0) AS total FROM products p
           WHERE (? = \'\' OR p.name LIKE ? OR p.sku LIKE ?) ORDER BY p.active DESC, p.name LIMIT 300', [$search, "%$search%", "%$search%"]);

layout_start('Products', 'products');
?>
<div class="grid2 wide-left">
  <div class="card">
    <form class="inline" method="get"><input type="hidden" name="p" value="products">
      <input name="s" placeholder="Search name or SKU" value="<?= e($search) ?>"><button class="btn">Search</button></form>
    <table><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="r">Cost</th><th class="r">Price</th><th class="r">Stock</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= $r['active'] ? '' : 'dim' ?>">
        <td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['category']) ?></td>
        <td class="r"><?= money($r['cost']) ?></td><td class="r"><?= money($r['price']) ?></td>
        <td class="r <?= $r['total'] <= $r['reorder_level'] ? 'bad' : '' ?>"><?= (int) $r['total'] ?> <?= e($r['unit']) ?></td>
        <td class="r"><a class="btn sm" href="index.php?p=products&id=<?= (int) $r['id'] ?>">Edit</a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn sm ghost"><?= $r['active'] ? 'Archive' : 'Restore' ?></button></form></td>
      </tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">No products yet. Add your first one on the right.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
  <form method="post" class="card form">
    <h2><?= $edit ? 'Edit product' : 'Add product' ?></h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <label>SKU<input name="sku" required value="<?= e($edit['sku'] ?? '') ?>"></label>
    <label>Name<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
    <label>Category<input name="category" value="<?= e($edit['category'] ?? '') ?>"></label>
    <label>Unit<input name="unit" value="<?= e($edit['unit'] ?? 'pcs') ?>"></label>
    <div class="two"><label>Cost<input type="number" step="0.01" min="0" name="cost" value="<?= e($edit['cost'] ?? '0') ?>"></label>
    <label>Price<input type="number" step="0.01" min="0" name="price" value="<?= e($edit['price'] ?? '0') ?>"></label></div>
    <label>Reorder level<input type="number" min="0" name="reorder_level" value="<?= e($edit['reorder_level'] ?? '0') ?>"></label>
    <button class="btn primary">Save</button> <?php if ($edit): ?><a class="btn ghost" href="index.php?p=products">Cancel</a><?php endif; ?>
  </form>
</div>
<?php layout_end();
