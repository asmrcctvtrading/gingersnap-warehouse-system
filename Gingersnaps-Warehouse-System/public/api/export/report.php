<?php
/**
 * GET /api/export/report
 *
 * Export report to CSV
 */

require_once '../../../public/bootstrap.php';
require_once APP_ROOT . '/src/modules/reporting/MetricsCalculator.php';
require_once APP_ROOT . '/src/modules/reporting/ReportGenerator.php';
require_once APP_ROOT . '/src/modules/reporting/CSVExporter.php';
require_once APP_ROOT . '/src/controllers/ReportController.php';

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die('Unauthorized');
    }

    $userRole = $_SESSION['user_role'] ?? 'employee';

    // Initialize controller
    $controller = new ReportController($pdo, $_SESSION['user_id'], $userRole);

    // Handle request
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $controller->exportReport();
    } else {
        http_response_code(405);
        die('Method not allowed');
    }

} catch (\Exception $e) {
    error_log('Export error: ' . $e->getMessage());
    http_response_code(500);
    die('Export failed');
}
?>
