<?php
declare(strict_types=1);

/**
 * Shared page chrome. Bootstrap 5 is used when vendored at assets/vendor/bootstrap/bootstrap.min.css
 * (no CDN: warehouse PCs and the CSP are same-origin only); assets/css/app.css works on its own.
 */
function page_header(string $title, string $active = ''): void
{
    $u = currentUserCached();
    $bs = APP_ROOT . '/assets/vendor/bootstrap/bootstrap.min.css';
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> - WPMS</title>
<?php if (is_file($bs)): ?><link rel="stylesheet" href="<?= h(url('assets/vendor/bootstrap/bootstrap.min.css')) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= h(url('assets/css/app.css')) ?>">
</head><body>
<header class="topbar">
  <a class="brand" href="<?= h(url('index.php')) ?>">WPMS</a>
  <?php if ($u): ?>
  <nav class="nav">
    <a href="<?= h(url('index.php')) ?>"<?= $active === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
    <a href="<?= h(url('change_password.php')) ?>"<?= $active === 'password' ? ' class="active"' : '' ?>>Change password</a>
  </nav>
  <form class="userbox" method="post" action="<?= h(url('logout.php')) ?>">
    <?= csrfField() ?>
    <span><?= h($u['full_name']) ?> <small>(<?= h($u['role']) ?>)</small></span>
    <button type="submit" class="btn btn-link">Sign out</button>
  </form>
  <?php endif; ?>
</header>
<main class="container">
<?php foreach (flash_pull() as $f): ?>
  <div class="alert alert-<?= h($f['type']) ?>" role="alert"><?= h($f['msg']) ?></div>
<?php endforeach; ?>
<?php
}

function page_footer(): void
{
    ?></main>
<footer class="footer"><small>Warehouse Productivity Monitoring System</small></footer>
</body></html>
<?php
}
