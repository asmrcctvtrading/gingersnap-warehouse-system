<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/views/admin_layout.php';
admin_boot();
requirePermission('categories.manage');   // admin only (wildcard)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $cid = isset($_POST['category_id']) && ctype_digit((string)$_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $slug = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', (string)($_POST['slug'] ?? '')) ?? '');
    $slug = trim($slug, '-');
    $en = clean_str($_POST['name_en'] ?? null, 120);
    $tl = clean_str($_POST['name_tl'] ?? null, 120);
    $icon = preg_match('/^[a-z]{2,20}$/', (string)($_POST['icon'] ?? '')) ? $_POST['icon'] : 'pin';
    $color = valid_hex_color($_POST['color'] ?? null) ? $_POST['color'] : '#2563eb';
    $sort = (int)($_POST['sort_order'] ?? 0);
    $ess = !empty($_POST['is_essential']) ? 1 : 0;
    $act = !empty($_POST['is_active']) ? 1 : 0;
    if ($en === null || ($cid === 0 && $slug === '')) {
        flash('error', 'English name' . ($cid === 0 ? ' and slug are' : ' is') . ' required.');
    } else {
        try {
            if ($cid) {
                db()->prepare('UPDATE categories SET name_en=?, name_tl=?, icon=?, color=?, sort_order=?, is_essential=?, is_active=? WHERE category_id=?')
                    ->execute([$en, $tl, $icon, $color, $sort, $ess, $act, $cid]);
                logAudit('update', 'category', $cid, $en);
            } else {
                db()->prepare('INSERT INTO categories (slug, name_en, name_tl, icon, color, sort_order, is_essential, is_active) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$slug, $en, $tl, $icon, $color, $sort, $ess, $act]);
                logAudit('create', 'category', (int)db()->lastInsertId(), $en);
            }
            flash('ok', 'Saved.');
        } catch (PDOException $e) {
            flash('error', $e->getCode() === '23000' ? 'That slug already exists.' : 'Could not save.');
        }
    }
    redirect('admin/categories.php');
}

$rows = db()->query('SELECT c.*, (SELECT COUNT(*) FROM establishments e WHERE e.primary_category_id = c.category_id) AS used
                     FROM categories c ORDER BY sort_order, name_en')->fetchAll();
admin_header('Categories', 'cats');
?>
<h1>Categories</h1>
<p class="muted">Categories in use cannot be deleted; deactivate them instead.</p>
<div class="tablewrap"><table class="data"><thead><tr><th>Slug</th><th>English</th><th>Tagalog</th><th>Icon</th><th>Colour</th><th>Order</th><th>Essential</th><th>Active</th><th>Used</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<?php $fid = 'cf' . (int)$r['category_id']; ?>
<tr>
  <td><form method="post" id="<?= $fid ?>"><?= csrfField() ?><input type="hidden" name="category_id" value="<?= (int)$r['category_id'] ?>"></form><?= h($r['slug']) ?></td>
  <td><input form="<?= $fid ?>" type="text" name="name_en" value="<?= h($r['name_en']) ?>" maxlength="120" aria-label="English name"></td>
  <td><input form="<?= $fid ?>" type="text" name="name_tl" value="<?= h($r['name_tl']) ?>" maxlength="120" aria-label="Tagalog name"></td>
  <td><input form="<?= $fid ?>" type="text" name="icon" value="<?= h($r['icon']) ?>" maxlength="20" style="width:6rem" aria-label="Icon"></td>
  <td><input form="<?= $fid ?>" type="text" name="color" value="<?= h($r['color']) ?>" maxlength="7" style="width:6rem" aria-label="Colour"></td>
  <td><input form="<?= $fid ?>" type="number" name="sort_order" value="<?= (int)$r['sort_order'] ?>" style="width:5rem" aria-label="Order"></td>
  <td><input form="<?= $fid ?>" type="checkbox" name="is_essential" value="1" <?= $r['is_essential'] ? 'checked' : '' ?>></td>
  <td><input form="<?= $fid ?>" type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>></td>
  <td><?= (int)$r['used'] ?></td>
  <td><button form="<?= $fid ?>" type="submit">Save</button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<h2>Add category</h2>
<form method="post" class="formgrid">
  <?= csrfField() ?>
  <div><label for="n_slug">Slug (letters, numbers, dashes)</label><input type="text" id="n_slug" name="slug" maxlength="60" required></div>
  <div><label for="n_en">English name</label><input type="text" id="n_en" name="name_en" maxlength="120" required></div>
  <div><label for="n_tl">Tagalog name</label><input type="text" id="n_tl" name="name_tl" maxlength="120"></div>
  <div><label for="n_icon">Icon key</label><input type="text" id="n_icon" name="icon" value="pin" maxlength="20"></div>
  <div><label for="n_color">Colour (#RRGGBB)</label><input type="text" id="n_color" name="color" value="#2563eb" maxlength="7"></div>
  <div><label for="n_sort">Order</label><input type="number" id="n_sort" name="sort_order" value="100"></div>
  <div><label class="check"><input type="checkbox" name="is_essential" value="1"> Essential service</label>
       <label class="check"><input type="checkbox" name="is_active" value="1" checked> Active</label></div>
  <div><button type="submit">Add</button></div>
</form>
<?php admin_footer(); ?>
