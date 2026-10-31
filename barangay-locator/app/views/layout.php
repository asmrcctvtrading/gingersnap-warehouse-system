<?php
declare(strict_types=1);

/** Redirect to the installer if the app is not installed yet. Call first on every public page. */
function public_boot(): void
{
    if (!app_installed()) {
        header('Location: ' . url('install/install.php'));
        exit;
    }
}

function icon_glyph(?string $key): string
{
    static $map = [
        'landmark' => '🏛️', 'building' => '🏢', 'cross' => '🏥', 'pill' => '💊', 'school' => '🏫', 'shield' => '🛡️',
        'flame' => '🚒', 'alert' => '🚨', 'church' => '⛪', 'cart' => '🛒', 'utensils' => '🍽️', 'bag' => '🛍️',
        'bank' => '🏦', 'cash' => '💵', 'bus' => '🚌', 'wrench' => '🔧', 'wifi' => '📶', 'droplet' => '💧',
        'flower' => '🌼', 'pin' => '📍',
    ];
    return $map[$key ?? 'pin'] ?? '📍';
}

function has_sample_data(): bool
{
    static $r = null;
    if ($r === null) {
        try {
            $r = setting('show_sample_banner', '1') === '1'
                && (bool)db()->query('SELECT 1 FROM barangays WHERE is_sample = 1 LIMIT 1')->fetchColumn();
        } catch (Throwable $e) {
            $r = false;
        }
    }
    return $r;
}

function page_header(string $title, string $active = '', array $opts = []): void
{
    $lang = current_lang();
    $site = setting('site_title', 'Barangay Establishment Locator');
    $other = $lang === 'en' ? 'tl' : 'en';
    $qs = $_GET;
    $qs['lang'] = $other;
    $switchUrl = '?' . http_build_query($qs);
    $nav = [
        'home' => ['index.php', t('nav.home')], 'search' => ['search.php', t('nav.search')],
        'map' => ['map.php', t('nav.map')], 'categories' => ['categories.php', t('nav.categories')],
        'services' => ['services.php', t('nav.services')], 'about' => ['about.php', t('nav.about')],
    ];
    ?><!doctype html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> · <?= h($site) ?></title>
<link rel="stylesheet" href="<?= h(url('assets/css/app.css')) ?>">
<?= $opts['head'] ?? '' ?>
</head>
<body data-base="<?= h(base_url()) ?>" data-csrf="<?= h(csrf_token()) ?>" data-lang="<?= h($lang) ?>" data-idle="<?= (int)setting('kiosk_idle_seconds', '90') ?>">
<a class="skip" href="#main"><?= h(t('nav.skip')) ?></a>
<?php if (has_sample_data()): ?><div class="sample-banner" role="note"><?= h(t('sample.banner')) ?></div><?php endif; ?>
<header class="site-header">
  <a class="brand" href="<?= h(url('index.php')) ?>"><?= h($site) ?></a>
  <nav aria-label="Main">
    <?php foreach ($nav as $k => [$href, $label]): ?>
      <a href="<?= h(url($href)) ?>"<?= $k === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
    <?php endforeach; ?>
    <a href="<?= h(url('report.php')) ?>"><?= h(t('nav.report')) ?></a>
    <a class="lang" href="<?= h($switchUrl) ?>"><?= h(t('lang.switch')) ?></a>
  </nav>
</header>
<main id="main">
<?php
}

function page_footer(array $opts = []): void
{
    ?>
</main>
<footer class="site-footer">
  <span><?= h(t('footer.line')) ?></span>
  <a class="staff" href="<?= h(url('admin/login.php')) ?>"><?= h(t('nav.admin')) ?></a>
</footer>
<div id="kiosk-overlay" class="kiosk-overlay" hidden><button type="button" id="kiosk-start"><?= h(t('kiosk.touch')) ?></button></div>
<script src="<?= h(url('assets/js/app.js')) ?>"></script>
<?= $opts['scripts'] ?? '' ?>
</body>
</html>
<?php
}

/** Render one establishment as a result card. */
function render_card(array $e): void
{
    $href = url('establishment.php?id=' . $e['id']);
    ?>
<article class="card">
  <span class="chip" style="--c:<?= h($e['category']['color']) ?>"><span aria-hidden="true"><?= icon_glyph($e['category']['icon']) ?></span> <?= h($e['category']['name']) ?></span>
  <h3><a href="<?= h($href) ?>"><?= h($e['name']) ?></a></h3>
  <?php if ($e['address']): ?><p class="muted"><?= h($e['address']) ?><?= $e['zone'] ? ' · ' . h($e['zone']) : '' ?></p><?php endif; ?>
  <p class="badges">
    <?php if ($e['verified']): ?><span class="badge ok"><?= h(t('status.verified')) ?></span>
    <?php else: ?><span class="badge warn"><?= h(t('status.pending_verification')) ?></span><?php endif; ?>
    <?php if ($e['emergency']): ?><span class="badge danger"><?= h(t('est.emergency_badge')) ?></span><?php endif; ?>
    <?php if ($e['is_sample']): ?><span class="badge sample">SAMPLE</span><?php endif; ?>
  </p>
</article>
<?php
}
