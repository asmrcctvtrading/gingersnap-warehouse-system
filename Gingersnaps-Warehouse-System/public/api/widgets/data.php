<?php
/**
 * GET /api/widgets/data.php?widget_id=ID
 *
 * Return chart data JSON for a specific widget
 *
 * Query Parameters:
 *   widget_id: int (required)
 *
 * Response: Chart data with labels and datasets
 * Auth: Required (owner only)
 */

header('Content-Type: application/json');

// Load warehouse config and auth functions
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php';

requireLogin();

try {
    $db = getDB();
    $userId = (int)$_SESSION['user_id'];

    // Get widget_id from query string
    $widgetId = (int)($_GET['widget_id'] ?? 0);
    if ($widgetId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'widget_id is required']);
        exit;
    }

    // Get widget
    $stmt = $db->prepare("SELECT * FROM dashboard_widgets WHERE id = ?");
    $stmt->execute([$widgetId]);
    $widget = $stmt->fetch();

    if (!$widget) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Widget not found']);
        exit;
    }

    // Check if user is owner or has admin permission
    if ($widget['user_id'] != $userId && !can('dashboard', 'admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }

    // Return sample data for now
    // In production, this would query the actual data source
    $data = [
        'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        'data' => [12, 19, 3, 5, 2, 3, 7]
    ];

    echo json_encode([
        'success' => true,
        'data' => $data,
        'widget_info' => [
            'id' => $widget['id'],
            'name' => $widget['widget_name'],
            'type' => $widget['chart_type'],
            'source' => $widget['data_source']
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch widget data',
        'message' => $e->getMessage()
    ]);
}
