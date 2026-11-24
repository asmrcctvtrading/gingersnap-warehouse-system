<?php
defined('APP') || exit;

const ICONS = [
    'dashboard' => 'M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z',
    'products' => 'M21 8l-9-5-9 5v8l9 5 9-5zM3 8l9 5 9-5M12 13v8',
    'stock' => 'M3 7h18M3 12h18M3 17h18',
    'batches' => 'M9 3h6M10 3v6l-5 9a2 2 0 002 3h10a2 2 0 002-3l-5-9V3',
    'orders' => 'M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0',
    'purchasing' => 'M1 3h4l3 12h11l2-8H6M9 20a1 1 0 100 .01M18 20a1 1 0 100 .01',
    'transfers' => 'M17 3l4 4-4 4M21 7H7M7 21l-4-4 4-4M3 17h14',
    'counts' => 'M9 11l3 3 8-8M20 12v7a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2h9',
    'returns' => 'M3 12a9 9 0 109-9 9 9 0 00-6.7 3M3 4v5h5',
    'customers' => 'M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 11a4 4 0 100-8 4 4 0 000 8M21 21v-2a4 4 0 00-3-3.9',
    'settings' => 'M12 15a3 3 0 100-6 3 3 0 000 6M19 12a7 7 0 00-.1-1.2l2-1.5-2-3.4-2.3 1a7 7 0 00-2-1.2L14.2 3h-4.4l-.4 2.7a7 7 0 00-2 1.2l-2.3-1-2 3.4 2 1.5A7 7 0 005 12c0 .4 0 .8.1 1.2l-2 1.5 2 3.4 2.3-1a7 7 0 002 1.2l.4 2.7h4.4l.4-2.7a7 7 0 002-1.2l2.3 1 2-3.4-2-1.5c.1-.4.1-.8.1-1.2z',
];
const NAV = [
    'Overview' => ['dashboard' => 'Dashboard'],
    'Inventory' => ['products' => 'Products', 'stock' => 'Stock', 'batches' => 'Batches', 'transfers' => 'Transfers', 'counts' => 'Cycle counts'],
    'Sales & supply' => ['orders' => 'Orders', 'returns' => 'Returns', 'purchasing' => 'Purchasing', 'customers' => 'Customers'],
    'System' => ['settings' => 'Settings'],
];

function icon(string $n): string
{
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . ICONS[$n] . '"/></svg>';
}

function layout_start(string $title, string $page): void
{
    global $CFG;
    $user = current_user();
    ?><!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · <?= e($CFG['app_name'] ?? 'Gingersnap') ?></title>
<script>try{var t=localStorage.getItem('gsw:theme');if(t)document.documentElement.dataset.theme=t;if(localStorage.getItem('gsw:side')==='0')document.documentElement.classList.add('side-closed')}catch(e){}</script>
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<aside class="side">
  <div class="brand"><span class="logo">G</span><b><?= e(setting('company', $CFG['app_name'] ?? 'Gingersnap')) ?></b></div>
  <nav>
  <?php foreach (NAV as $group => $items): ?>
    <div class="grp"><?= e($group) ?></div>
    <?php foreach ($items as $key => $label): ?>
      <a href="index.php?p=<?= $key ?>" class="<?= $key === $page ? 'on' : '' ?>"><?= icon($key) ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </nav>
</aside>
<div class="main">
  <header class="top">
    <button class="btn ghost" id="sideBtn" aria-label="Toggle menu">☰</button>
    <h1><?= e($title) ?></h1>
    <div class="sp"></div>
    <button class="btn ghost" id="themeBtn" aria-label="Toggle theme">◐</button>
    <span class="who"><?= e($user['name'] ?? '') ?> <small>(<?= e($user['role'] ?? '') ?>)</small></span>
    <a class="btn ghost" href="logout.php">Sign out</a>
  </header>
  <main class="content">
  <?php foreach (flash() ?? [] as [$type, $msg]): ?>
    <div class="alert a-<?= e($type) ?>"><?= e($msg) ?></div>
  <?php endforeach; ?>
<?php
}

function layout_end(): void
{
    ?>
  </main>
</div>
<script src="assets/app.js"></script>
</body></html>
<?php
}
