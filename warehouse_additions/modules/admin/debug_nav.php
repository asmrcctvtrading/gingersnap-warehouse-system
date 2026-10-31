<?php
require_once __DIR__ . '/../../config/config.php';

echo "<h2>Current Page Detection</h2>";
echo "<p><strong>SCRIPT_NAME:</strong> " . h($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p><strong>currentScript (basename):</strong> " . h(basename($_SERVER['SCRIPT_NAME']) ?? 'N/A') . "</p>";
echo "<p><strong>currentDir (dirname basename):</strong> " . h(basename(dirname($_SERVER['SCRIPT_NAME'])) ?? 'N/A') . "</p>";

echo "<h3>Menu link check:</h3>";
$checkDir = 'grn';
$checkScript = 'list.php';
$isActive = ($checkDir === basename(dirname($_SERVER['SCRIPT_NAME'])) && $checkScript === basename($_SERVER['SCRIPT_NAME']));
echo "<p>Checking: dir='$checkDir', script='$checkScript'</p>";
echo "<p>isActive: " . ($isActive ? "YES (will be highlighted)" : "NO (not highlighted)") . "</p>";

echo "<br><a href='index.php'>Back to Dashboard</a>";
