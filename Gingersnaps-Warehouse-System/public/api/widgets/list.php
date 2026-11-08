<?php
/**
 * GET /api/widgets/list.php
 *
 * Return all widgets for current user
 * Response: Array of widget configurations
 * Auth: Required (any role)
 */

header('Content-Type: application/json');

// Load warehouse config and auth functions
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.php';

requireLogin();
requirePermission('dashboard', 'view');

try {
    $db = getDB();
    $userId = (int)$_SESSION['user_id'];

    // Get user's widgets
    $stmt = $db->prepare("
        SELECT
            id,
            user_id,
            widget_name,
            data_source,
            chart_type,
            layout_size,
            config,
            created_at,
            updated_at
        FROM dashboard_widgets
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $widgets = $stmt->fetchAll();

    // Parse JSON configs
    foreach ($widgets as &$widget) {
        if (is_string($widget['config'])) {
            $widget['config'] = json_decode($widget['config'], true) ?? [];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $widgets,
        'count' => count($widgets)
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch widgets',
        'message' => $e->getMessage()
    ]);
}
