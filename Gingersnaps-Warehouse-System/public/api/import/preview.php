<?php
/**
 * POST /api/import/preview
 *
 * Upload file and get preview of data to be imported
 */

require_once '../../../public/bootstrap.php';
require_once APP_ROOT . '/src/modules/import/ImportService.php';
require_once APP_ROOT . '/src/modules/import/ImportTemplateGenerator.php';
require_once APP_ROOT . '/src/controllers/ImportController.php';
require_once APP_ROOT . '/src/helpers/ResponseFormatter.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Get user role
    $userRole = $_SESSION['user_role'] ?? 'employee';

    // Initialize controller
    $controller = new ImportController($pdo, $_SESSION['user_id'], $userRole);

    // Handle request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $controller->previewImport();
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (\Exception $e) {
    error_log('Import preview error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
