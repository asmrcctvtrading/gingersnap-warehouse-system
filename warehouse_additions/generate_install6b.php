<?php
/**
 * generate_install6b.php
 * -----------------------------------------------------------------------
 * Run this once from the warehouse_additions directory using PHP CLI:
 *
 *   php generate_install6b.php
 *
 * It reads the 5 module PHP files, base64-encodes each one, and writes
 * install6b.php ready to upload to the server.
 * -----------------------------------------------------------------------
 */

$base = __DIR__;

$sources = [
    'modules/locations/action.php',
    'modules/stock/list.php',
    'modules/stock/move.php',
    'modules/stock/action.php',
    'modules/stock/ledger.php',
];

// Verify all source files exist before we do anything
$missing = [];
foreach ($sources as $rel) {
    if (!file_exists($base . '/' . $rel)) {
        $missing[] = $rel;
    }
}
if ($missing) {
    fwrite(STDERR, "ERROR: Missing source files:\n");
    foreach ($missing as $m) { fwrite(STDERR, "  $m\n"); }
    exit(1);
}

// Build the $files array entries with base64-encoded content
$entries = [];
foreach ($sources as $rel) {
    $content   = file_get_contents($base . '/' . $rel);
    $b64       = base64_encode($content);
    $paddedRel = str_pad("'" . $rel . "'", 38);
    $entries[] = "    {$paddedRel} => base64_decode('{$b64}'),";
}

$filesBlock = implode("\n", $entries);

$out = <<<'PHPEOF'
<?php
/**
 * install6b.php — Deploy remaining Locations & Stock module files
 * (Fixes the 4 files that install6.php could not write)
 * Upload to /public_html/warehouse/install6b.php
 * Visit while logged in as admin, then DELETE the file.
 */
require_once __DIR__ . '/config/config.php';
requireLogin();
if (!can('users','view')) { die('Admin access required.'); }

$base  = __DIR__;
$files = [
PHPEOF;

$out .= "\n" . $filesBlock . "\n";

$out .= <<<'PHPEOF'
];

$ok  = 0; $err = 0; $log = [];
foreach ($files as $rel => $content) {
    $path = $base . '/' . $rel;
    $dir  = dirname($path);
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    if (file_put_contents($path, $content) !== false) {
        $ok++;
        $log[] = '<span class="text-success">&#10003; ' . htmlspecialchars($rel) . '</span>';
    } else {
        $err++;
        $log[] = '<span class="text-danger">&#10007; ' . htmlspecialchars($rel) . '</span>';
    }
}
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>install6b</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">
<h4>install6b.php &mdash; Deploy Results</h4>
<p><?= $ok ?> written, <?= $err ?> failed.</p>
<ul><?php foreach ($log as $l) echo '<li>' . $l . '</li>'; ?></ul>
<?php if ($err===0): ?>
<div class="alert alert-success mt-3">Done! <strong>Delete this file now.</strong></div>
<?php else: ?>
<div class="alert alert-danger mt-3">Some files failed. Check folder permissions on modules/stock/ and modules/locations/.</div>
<?php endif; ?>
</body></html>
PHPEOF;

$outPath = $base . '/install6b.php';
if (file_put_contents($outPath, $out) === false) {
    fwrite(STDERR, "ERROR: Could not write install6b.php to $outPath\n");
    exit(1);
}

$size = filesize($outPath);
echo "SUCCESS: install6b.php written.\n";
echo "Path : $outPath\n";
echo "Size : $size bytes\n";
echo "\nNext steps:\n";
echo "  1. Upload install6b.php to /public_html/warehouse/install6b.php\n";
echo "  2. Visit it in your browser while logged in as admin\n";
echo "  3. DELETE the file after it reports success\n";
