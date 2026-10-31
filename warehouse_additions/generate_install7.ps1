# generate_install7.ps1
# Reads the PO module PHP files and the updated header.php,
# base64-encodes each one, and assembles install7.php.
#
# Prerequisites: Run this AFTER all PHP files have been written.
# Output: warehouse_additions\install7.php
#
# Usage:
#   cd "C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions"
#   .\generate_install7.ps1

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$base = "C:\Users\Odyssey Jan Pascual\.omniroute\warehouse_additions"

# Files to embed: relative path (deploy target) => source path on disk
$filePaths = [ordered]@{
    'modules/purchase_orders/list.php'    = "$base\modules\purchase_orders\list.php"
    'modules/purchase_orders/form.php'    = "$base\modules\purchase_orders\form.php"
    'modules/purchase_orders/action.php'  = "$base\modules\purchase_orders\action.php"
    'modules/purchase_orders/view.php'    = "$base\modules\purchase_orders\view.php"
    'modules/purchase_orders/receive.php' = "$base\modules\purchase_orders\receive.php"
    'includes/header.php'                 = "$base\includes\header.php"
}

# Build the PHP array entries
$arrayEntries = @()
foreach ($deployPath in $filePaths.Keys) {
    $srcPath = $filePaths[$deployPath]
    if (-not (Test-Path $srcPath)) {
        Write-Error "Source file not found: $srcPath"
        exit 1
    }
    $bytes  = [System.IO.File]::ReadAllBytes($srcPath)
    $b64    = [Convert]::ToBase64String($bytes)
    $arrayEntries += "    '$deployPath' => base64_decode('$b64'),"
    Write-Host "Encoded: $deployPath ($($bytes.Length) bytes)"
}

$arrayBody = $arrayEntries -join "`n"

$phpContent = @"
<?php
/**
 * Gingersnaps Warehouse — Module 7 Installer
 * Installs: Purchase Orders module (list, form, action, view, receive)
 *           Updated includes/header.php with PO nav link
 *
 * Deploy steps:
 *   1. Run migration7.sql in phpMyAdmin first.
 *   2. Upload this file to /public_html/warehouse/install7.php
 *   3. Visit the URL while logged in as Admin.
 *   4. Delete this file from the server after a successful install.
 */
session_start();
if (!isset(`$_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

`$files = [
$arrayBody
];

`$written = 0;
`$failed  = 0;

foreach (`$files as `$path => `$content) {
    `$full = __DIR__ . '/' . `$path;
    `$dir  = dirname(`$full);
    if (!is_dir(`$dir)) {
        mkdir(`$dir, 0755, true);
    }
    if (file_put_contents(`$full, `$content) !== false) {
        echo "OK: " . htmlspecialchars(`$path) . "<br>\n";
        `$written++;
    } else {
        echo "FAIL: " . htmlspecialchars(`$path) . "<br>\n";
        `$failed++;
    }
}

echo "<br>Done: `$written written, `$failed failed\n";
"@

$outPath = "$base\install7.php"
[System.IO.File]::WriteAllText($outPath, $phpContent, [System.Text.Encoding]::UTF8)

Write-Host ""
Write-Host "install7.php written to: $outPath" -ForegroundColor Green
Write-Host "Total files embedded: $($filePaths.Count)" -ForegroundColor Green
