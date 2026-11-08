<?php
/**
 * POST /api/widgets/delete.php
 *
 * Delete widget
 * Auth: Required (dashboard.manage permission)
 */

header('Content-Type: application/json');

// Load warehouse config and auth functions
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php';

requireLogin();
requirePermission('dashboard', 'manage');

try {
    $db = getDB();
    $userId = (int)$_SESSION['user_id'];

    // Get request body
    $input = $_POST;
    if (empty($input) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $widgetId = (int)($input['widget_id'] ?? 0);
    if ($widgetId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'widget_id is required']);
        exit;
    }

    // Verify ownership
    $stmt = $db->prepare("SELECT user_id FROM dashboard_widgets WHERE id = ?");
    $stmt->execute([$widgetId]);
    $widget = $stmt->fetch();

    if (!$widget) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Widget not found']);
        exit;
    }

    if ($widget['user_id'] != $userId && !can('dashboard', 'admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Delete widget
    $stmt = $db->prepare("DELETE FROM dashboard_widgets WHERE id = ?");
    $stmt->execute([$widgetId]);

    echo json_encode([
        'success' => true,
        'message' => 'Widget deleted successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete widget',
        'message' => $e->getMessage()
    ]);
}
