<?php
/**
 * GET /api/import/template?type=picklist
 *
 * Download CSV import template
 */

require_once '../../../public/bootstrap.php';
require_once APP_ROOT . '/src/modules/import/ImportTemplateGenerator.php';
require_once APP_ROOT . '/src/controllers/ImportController.php';

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized');
    }

    $userRole = $_SESSION['user_role'] ?? 'employee';

    // Initialize controller
    $controller = new ImportController($pdo, $_SESSION['user_id'], $userRole);

    // Handle request
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller->downloadTemplate();
    } else {
        http_response_code(405);
        die('Method not allowed');
    }

} catch (\Exception $e) {
    error_log('Template download error: ' . $e->getMessage());
    http_response_code(500);
    die('Server error');
}
?>
