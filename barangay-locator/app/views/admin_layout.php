<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/repo/admin_est.php';

function admin_boot(): void
{
    if (!app_installed()) {
        header('Location: ' . url('install/install.php'));
        exit;
    }
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function admin_header(string $title, string $active = ''): void
{
    $u = currentUser();
    $items = [
        'dash'     => ['admin/index.php', 'Dashboard', null],
        'est'      => ['admin/establishments.php', 'Establishments', 'establishments.view'],
        'requests' => ['admin/requests.php', 'Requests', 'requests.review'],
        'cats'     => ['admin/categories.php', 'Categories', '*'],
        'brgy'     => ['admin/barangays.php', 'Barangays', '*'],
        'users'    => ['admin/users.php', 'Users', '*'],
        'audit'    => ['admin/audit.php', 'Audit log', 'audit.view'],
        'export'   => ['admin/export.php', 'Export', 'export.run'],
        'osm'      => ['admin/osm_import.php', 'OSM import', '*'],
        'settings' => ['admin/settings.php', 'Settings', '*'],
    ];
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($title) ?> · Staff</title>
<link rel="stylesheet" href="<?= h(url('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= h(url('assets/css/admin.css')) ?>">
</head><body>
<header class="site-header">
  <a class="brand" href="<?= h(url('admin/index.php')) ?>">Staff area</a>
  <nav aria-label="Admin">
  <?php foreach ($items as $k => [$href, $label, $perm]):
      if ($perm !== null && !can($perm === '*' ? '__admin_only__' : $perm)) { continue; } ?>
    <a href="<?= h(url($href)) ?>"<?= $k === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
  <?php endforeach; ?>
    <a href="<?= h(url('index.php')) ?>">Public site</a>
  </nav>
  <?php if ($u): ?>
  <form method="post" action="<?= h(url('admin/logout.php')) ?>" class="inline">
    <?= csrfField() ?><span class="muted"><?= h($u['full_name']) ?> (<?= h($u['role_key']) ?>)</span>
    <button type="submit" class="secondary">Log out</button>
  </form>
  <?php endif; ?>
</header>
<main>
<?php foreach (($_SESSION['flash'] ?? []) as [$type, $msg]): ?>
  <div class="notice <?= h($type) ?>" role="status"><?= h($msg) ?></div>
<?php endforeach; $_SESSION['flash'] = []; ?>
<?php
}

function admin_footer(): void
{
    echo "</main></body></html>";
}

/** Fetch all barangays for dropdowns (respecting the user's scope). */
function admin_barangays(): array
{
    $scope = userBarangayScope();
    if ($scope !== null) {
        $st = db()->prepare('SELECT barangay_id, name FROM barangays WHERE barangay_id = ?');
        $st->execute([$scope]);
        return $st->fetchAll();
    }
    return db()->query('SELECT barangay_id, name FROM barangays ORDER BY name')->fetchAll();
}

function admin_categories(): array
{
    return db()->query('SELECT category_id, name_en, is_active FROM categories ORDER BY sort_order, name_en')->fetchAll();
}
