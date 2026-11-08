<?php
/**
 * POST /api/widgets/create.php
 *
 * Create new widget for current user
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

    // Get request body (form or JSON)
    $input = $_POST;
    if (empty($input) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // Validate required fields
    $errors = [];
    if (empty($input['widget_name'])) {
        $errors[] = 'widget_name is required';
    }
    if (empty($input['chart_type'])) {
        $errors[] = 'chart_type is required';
    }
    if (empty($input['data_source'])) {
        $errors[] = 'data_source is required';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Validate chart type
    $validChartTypes = ['donut', 'pie', 'bar', 'polar', 'line', 'doughnut'];
    if (!in_array($input['chart_type'], $validChartTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid chart type']);
        exit;
    }

    // Validate layout size
    $layoutSize = $input['layout_size'] ?? 'medium';
    $validSizes = ['small', 'medium', 'large'];
    if (!in_array($layoutSize, $validSizes)) {
        $layoutSize = 'medium';
    }

    // Insert widget
    $stmt = $db->prepare("
        INSERT INTO dashboard_widgets
        (user_id, widget_name, data_source, chart_type, layout_size, config, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $config = json_encode($input['config'] ?? []);
    $stmt->execute([
        $userId,
        htmlspecialchars($input['widget_name']),
        $input['data_source'],
        $input['chart_type'],
        $layoutSize,
        $config
    ]);

    $widgetId = (int)$db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Widget created successfully',
        'data' => [
            'id' => $widgetId,
            'user_id' => $userId,
            'widget_name' => $input['widget_name'],
            'data_source' => $input['data_source'],
            'chart_type' => $input['chart_type'],
            'layout_size' => $layoutSize,
            'config' => $input['config'] ?? [],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create widget',
        'message' => $e->getMessage()
    ]);
}
