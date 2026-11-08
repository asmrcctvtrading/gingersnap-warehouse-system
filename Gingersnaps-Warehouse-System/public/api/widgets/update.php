<?php
/**
 * POST /api/widgets/update.php
 *
 * Update widget configuration
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

    // Build update query
    $updates = [];
    $params = [];

    if (isset($input['widget_name'])) {
        $updates[] = 'widget_name = ?';
        $params[] = htmlspecialchars($input['widget_name']);
    }
    if (isset($input['chart_type'])) {
        $updates[] = 'chart_type = ?';
        $params[] = $input['chart_type'];
    }
    if (isset($input['layout_size'])) {
        $updates[] = 'layout_size = ?';
        $params[] = $input['layout_size'];
    }
    if (isset($input['config'])) {
        $updates[] = 'config = ?';
        $params[] = json_encode($input['config']);
    }

    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'No changes']);
        exit;
    }

    $updates[] = 'updated_at = NOW()';
    $params[] = $widgetId;

    $sql = "UPDATE dashboard_widgets SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Widget updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update widget',
        'message' => $e->getMessage()
    ]);
}
