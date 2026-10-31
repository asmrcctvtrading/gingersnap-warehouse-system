<?php
/**
 * GET /api/dashboard/metrics
 *
 * Get dashboard metrics
 */

require_once '../../../public/bootstrap.php';
require_once APP_ROOT . '/src/modules/reporting/MetricsCalculator.php';
require_once APP_ROOT . '/src/controllers/ReportController.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $userRole = $_SESSION['user_role'] ?? 'employee';

    // Initialize controller
    $controller = new ReportController($pdo, $_SESSION['user_id'], $userRole);

    // Handle request
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $controller->getDashboardMetrics();
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    error_log('Dashboard metrics error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
