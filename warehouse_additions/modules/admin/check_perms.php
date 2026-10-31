<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();

echo "<h2>Debug: dispatching_priority permission check</h2>";

// Direct check
echo "<h3>can('dispatching_priority', 'view'):</h3>";
echo "<pre>";
var_dump(can('dispatching_priority', 'view'));
echo "</pre>";

// Check session permissions
echo "<h3>Session permissions array:</h3>";
echo "<pre>";
print_r($_SESSION['permissions'] ?? []);
echo "</pre>";

// Check if dispatching_priority.view exists in array
echo "<h3>Checking 'dispatching_priority.view' in array:</h3>";
$hasPerm = in_array('dispatching_priority.view', $_SESSION['permissions'] ?? [], true);
echo "Result: " . ($hasPerm ? "TRUE - Found" : "FALSE - Not found") . "<br>";

// Check database directly
echo "<h3>Database check:</h3>";
$perm = $db->query("SELECT p.module_key, p.action_key FROM permissions p JOIN role_permissions rp ON rp.permission_id = p.permission_id WHERE p.module_key = 'dispatching_priority' AND rp.role_id = " . $_SESSION['role_id'])->fetchAll();
if ($perm) {
    echo "<pre>";
    print_r($perm);
    echo "</pre>";
} else {
    echo "No database records found for dispatching_priority with your role<br>";
}

echo "<br><a href='index.php'>Back to Admin</a>";
