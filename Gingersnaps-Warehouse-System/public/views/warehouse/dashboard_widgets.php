<?php
/**
 * Warehouse Widget Dashboard
 *
 * Customizable dashboard for warehouse productivity metrics.
 * Allows users to create, configure, and manage data visualization widgets.
 *
 * Auth: Required (admin/supervisor/manager can CRUD)
 */

// Load warehouse config and auth functions
require_once __DIR__ . '/../../config/config.php';

// Check authentication (requireLogin is defined in config.php)
requireLogin();

// Check permission to view dashboard
requirePermission('dashboard', 'view');

// Get current user from session
$user = [
    'id'        => $_SESSION['user_id'] ?? 0,
    'username'  => $_SESSION['username'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? 'User',
];

// Determine if user can manage widgets (check role or specific permission)
$userRole = $_SESSION['role_name'] ?? 'employee';
$isEditor = in_array($userRole, ['admin', 'supervisor', 'manager']) ||
            can('dashboard', 'manage');

// Get PHPSESSID for AJAX requests
$sessionId = session_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Warehouse Productivity Monitoring System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <!-- Chart.js 4.x -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>

    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --dark-bg: #1a1a2e;
            --sidebar-bg: #16213e;
            --card-bg: #f8f9fa;
            --card-dark-bg: #2a2a3e;
            --text-light: #e0e0e0;
            --border-color: #ddd;
            --border-dark: #444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        body.dark-theme {
            background-color: var(--dark-bg);
            color: var(--text-light);
        }

        /* Layout */
        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        body.dark-theme .header {
            background-color: var(--sidebar-bg);
            border-bottom-color: var(--border-dark);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 14px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        body.dark-theme .theme-toggle {
            color: var(--text-light);
        }

        .theme-toggle:hover {
            color: var(--primary-color);
        }

        /* Main content area */
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Toolbar */
        .widget-toolbar {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        body.dark-theme .widget-toolbar {
            background-color: var(--sidebar-bg);
            border-bottom-color: var(--border-dark);
        }

        .toolbar-left,
        .toolbar-right {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-toolbar {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-toolbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .btn-primary-toolbar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary-toolbar {
            background: #f0f0f0;
            color: #333;
            border: 1px solid var(--border-color);
        }

        body.dark-theme .btn-secondary-toolbar {
            background-color: #333;
            color: var(--text-light);
            border-color: var(--border-dark);
        }

        .btn-secondary-toolbar:hover {
            background: #e0e0e0;
        }

        body.dark-theme .btn-secondary-toolbar:hover {
            background-color: #444;
        }

        .last-updated {
            font-size: 12px;
            color: #999;
        }

        body.dark-theme .last-updated {
            color: #aaa;
        }

        /* Dashboard grid */
        .dashboard-container {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        .widget-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            grid-auto-rows: auto;
        }

        /* Widget card */
        .widget-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            min-height: 350px;
        }

        body.dark-theme .widget-card {
            background-color: var(--card-dark-bg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .widget-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        body.dark-theme .widget-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
        }

        .widget-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-title {
            font-size: 16px;
            font-weight: 600;
        }

        .widget-actions {
            display: flex;
            gap: 0.5rem;
        }

        .widget-action-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .widget-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .widget-body {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .widget-chart-container {
            flex: 1;
            position: relative;
            min-height: 250px;
        }

        .widget-meta {
            font-size: 12px;
            color: #999;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        body.dark-theme .widget-meta {
            color: #aaa;
            border-top-color: var(--border-dark);
        }

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            text-align: center;
            color: #999;
        }

        body.dark-theme .empty-state {
            color: #aaa;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Modals */
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 14px;
            color: #333;
        }

        body.dark-theme .form-label {
            color: var(--text-light);
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        body.dark-theme .form-control,
        body.dark-theme .form-select {
            background-color: #2a2a3e;
            color: var(--text-light);
            border-color: var(--border-dark);
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        body.dark-theme .form-control:focus,
        body.dark-theme .form-select:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-text {
            font-size: 12px;
            color: #999;
            margin-top: 0.25rem;
        }

        body.dark-theme .form-text {
            color: #aaa;
        }

        /* Modal footer buttons */
        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        body.dark-theme .modal-footer {
            border-top-color: var(--border-dark);
        }

        .btn-modal {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .btn-modal-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-modal-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1px solid var(--border-color);
        }

        body.dark-theme .btn-modal-secondary {
            background-color: #333;
            color: var(--text-light);
            border-color: var(--border-dark);
        }

        .btn-modal-secondary:hover {
            background: #e0e0e0;
        }

        body.dark-theme .btn-modal-secondary:hover {
            background-color: #444;
        }

        .btn-modal-danger {
            background: #dc3545;
            color: white;
        }

        .btn-modal-danger:hover {
            background: #c82333;
        }

        /* Alert messages */
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 14px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        body.dark-theme .alert-success {
            background-color: rgba(25, 135, 84, 0.2);
            color: #86efac;
            border-color: #4b9d71;
        }

        body.dark-theme .alert-error {
            background-color: rgba(220, 53, 69, 0.2);
            color: #fca5a5;
            border-color: #a04d52;
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(102, 126, 234, 0.1);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 1200px) {
            .widget-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }

            .widget-toolbar {
                padding: 1rem;
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-left,
            .toolbar-right {
                width: 100%;
                justify-content: space-between;
            }

            .dashboard-container {
                padding: 1rem;
            }

            .widget-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .widget-card {
                min-height: 300px;
            }
        }

        @media (max-width: 375px) {
            .header-title {
                font-size: 18px;
            }

            .header-right {
                gap: 0.75rem;
            }

            .user-info {
                font-size: 12px;
            }

            .btn-toolbar {
                padding: 0.5rem 1rem;
                font-size: 12px;
            }

            .widget-body {
                padding: 1rem;
            }

            .modal-body {
                padding: 1.5rem 1rem;
            }
        }

        /* Data source options styling */
        .data-source-group {
            margin-bottom: 1.5rem;
        }

        .data-source-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-theme .data-source-label {
            color: #aaa;
        }

        .data-source-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }

        .data-source-option {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
        }

        body.dark-theme .data-source-option {
            border-color: var(--border-dark);
            color: var(--text-light);
        }

        .data-source-option:hover {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .data-source-option.selected {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--primary-color);
        }

        /* Chart type options */
        .chart-type-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.75rem;
        }

        .chart-type-option {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 12px;
        }

        body.dark-theme .chart-type-option {
            border-color: var(--border-dark);
            color: var(--text-light);
        }

        .chart-type-option:hover {
            border-color: var(--primary-color);
        }

        .chart-type-option.selected {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="header-title">
                    <i class="fa fa-bar-chart"></i> Dashboard
                </div>
                <div class="header-right">
                    <span class="last-updated" id="lastUpdated">Last updated: just now</span>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo htmlspecialchars(substr($user['full_name'] ?? 'U', 0, 1)); ?></div>
                        <div>
                            <div><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></div>
                            <small style="opacity: 0.7;">
                                <?php
                                $roleLabels = ['admin' => 'Admin', 'supervisor' => 'Supervisor', 'manager' => 'Manager', 'employee' => 'Employee'];
                                echo $roleLabels[$userRole] ?? ucfirst($userRole);
                                ?>
                            </small>
                        </div>
                    </div>
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark theme">
                        <i class="fa fa-moon-o"></i>
                    </button>
                </div>
            </header>

            <!-- Toolbar -->
            <div class="widget-toolbar">
                <div class="toolbar-left">
                    <?php if ($isEditor): ?>
                    <button class="btn-toolbar btn-primary-toolbar" id="addWidgetBtn" title="Create new widget">
                        <i class="fa fa-plus"></i> Add Widget
                    </button>
                    <button class="btn-toolbar btn-secondary-toolbar" id="saveLayoutBtn" title="Save current layout">
                        <i class="fa fa-save"></i> Save Layout
                    </button>
                    <button class="btn-toolbar btn-secondary-toolbar" id="resetLayoutBtn" title="Reset to default layout">
                        <i class="fa fa-refresh"></i> Reset
                    </button>
                    <?php endif; ?>
                </div>
                <div class="toolbar-right">
                    <span class="last-updated" id="autoRefreshStatus">Auto-refresh: ON (5 min)</span>
                </div>
            </div>

            <!-- Dashboard Container -->
            <div class="dashboard-container">
                <div class="widget-grid" id="widgetGrid">
                    <!-- Widgets will be inserted here via JavaScript -->
                    <div class="empty-state" id="emptyState">
                        <div class="empty-state-icon">
                            <i class="fa fa-inbox"></i>
                        </div>
                        <p>No widgets configured yet</p>
                        <?php if ($isEditor): ?>
                        <p style="font-size: 12px; opacity: 0.7; margin-top: 0.5rem;">
                            Click "Add Widget" to create your first dashboard widget
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============= MODALS ============= -->

    <!-- Add Widget Modal -->
    <div class="modal fade" id="addWidgetModal" tabindex="-1" role="dialog" aria-labelledby="addWidgetModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addWidgetModalLabel">Create New Widget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addWidgetForm">
                    <div class="modal-body">
                        <!-- Widget Name -->
                        <div class="form-group">
                            <label class="form-label" for="widgetName">Widget Name</label>
                            <input type="text" class="form-control" id="widgetName" name="widget_name" placeholder="e.g., Zone Picking Performance" required>
                            <small class="form-text">Give your widget a descriptive name</small>
                        </div>

                        <!-- Data Source Selection -->
                        <div class="form-group">
                            <label class="form-label">Data Source</label>
                            <div class="data-source-options" id="dataSourceOptions">
                                <div class="data-source-option" data-source="picking_by_zone">
                                    <i class="fa fa-map-marker"></i><br>
                                    Picking by Zone
                                </div>
                                <div class="data-source-option" data-source="hourly_throughput">
                                    <i class="fa fa-line-chart"></i><br>
                                    Hourly Throughput
                                </div>
                                <div class="data-source-option" data-source="task_distribution">
                                    <i class="fa fa-tasks"></i><br>
                                    Task Distribution
                                </div>
                                <div class="data-source-option" data-source="top_performers">
                                    <i class="fa fa-star"></i><br>
                                    Top Performers
                                </div>
                                <div class="data-source-option" data-source="error_trends">
                                    <i class="fa fa-warning"></i><br>
                                    Error Trends
                                </div>
                                <div class="data-source-option" data-source="completion_rate">
                                    <i class="fa fa-check-circle"></i><br>
                                    Completion Rate
                                </div>
                            </div>
                            <input type="hidden" id="selectedDataSource" name="data_source" required>
                            <small class="form-text">Select the data source for this widget</small>
                        </div>

                        <!-- Chart Type Selection -->
                        <div class="form-group">
                            <label class="form-label">Chart Type</label>
                            <div class="chart-type-options" id="chartTypeOptions">
                                <div class="chart-type-option" data-chart="bar">
                                    <i class="fa fa-bar-chart"></i><br>
                                    Bar
                                </div>
                                <div class="chart-type-option" data-chart="line">
                                    <i class="fa fa-line-chart"></i><br>
                                    Line
                                </div>
                                <div class="chart-type-option" data-chart="pie">
                                    <i class="fa fa-pie-chart"></i><br>
                                    Pie
                                </div>
                                <div class="chart-type-option" data-chart="donut">
                                    <i class="fa fa-circle-o-notch"></i><br>
                                    Donut
                                </div>
                                <div class="chart-type-option" data-chart="polar">
                                    <i class="fa fa-compass"></i><br>
                                    Polar
                                </div>
                                <div class="chart-type-option" data-chart="doughnut">
                                    <i class="fa fa-circle-o"></i><br>
                                    Doughnut
                                </div>
                            </div>
                            <input type="hidden" id="selectedChartType" name="chart_type" value="bar" required>
                            <small class="form-text">Select how to display the data</small>
                        </div>

                        <!-- Layout Size -->
                        <div class="form-group">
                            <label class="form-label" for="layoutSize">Widget Size</label>
                            <select class="form-select" id="layoutSize" name="layout_size">
                                <option value="small">Small (1x1)</option>
                                <option value="medium" selected>Medium (1x2)</option>
                                <option value="large">Large (2x2)</option>
                            </select>
                            <small class="form-text">How much space should this widget take</small>
                        </div>

                        <!-- Refresh Interval -->
                        <div class="form-group">
                            <label class="form-label" for="refreshInterval">Auto-Refresh Interval</label>
                            <select class="form-select" id="refreshInterval" name="refresh_interval">
                                <option value="300">5 minutes</option>
                                <option value="600">10 minutes</option>
                                <option value="900">15 minutes</option>
                                <option value="1800">30 minutes</option>
                                <option value="0">Disabled</option>
                            </select>
                            <small class="form-text">How often to update this widget's data</small>
                        </div>

                        <!-- Alert message -->
                        <div id="addWidgetAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-primary">
                            <i class="fa fa-plus"></i> Create Widget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Widget Modal -->
    <div class="modal fade" id="editWidgetModal" tabindex="-1" role="dialog" aria-labelledby="editWidgetModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editWidgetModalLabel">Edit Widget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editWidgetForm">
                    <div class="modal-body">
                        <input type="hidden" id="editWidgetId" name="widget_id">

                        <!-- Widget Name -->
                        <div class="form-group">
                            <label class="form-label" for="editWidgetName">Widget Name</label>
                            <input type="text" class="form-control" id="editWidgetName" name="widget_name" required>
                        </div>

                        <!-- Chart Type -->
                        <div class="form-group">
                            <label class="form-label">Chart Type</label>
                            <div class="chart-type-options" id="editChartTypeOptions">
                                <div class="chart-type-option" data-chart="bar">
                                    <i class="fa fa-bar-chart"></i><br>
                                    Bar
                                </div>
                                <div class="chart-type-option" data-chart="line">
                                    <i class="fa fa-line-chart"></i><br>
                                    Line
                                </div>
                                <div class="chart-type-option" data-chart="pie">
                                    <i class="fa fa-pie-chart"></i><br>
                                    Pie
                                </div>
                                <div class="chart-type-option" data-chart="donut">
                                    <i class="fa fa-circle-o-notch"></i><br>
                                    Donut
                                </div>
                                <div class="chart-type-option" data-chart="polar">
                                    <i class="fa fa-compass"></i><br>
                                    Polar
                                </div>
                                <div class="chart-type-option" data-chart="doughnut">
                                    <i class="fa fa-circle-o"></i><br>
                                    Doughnut
                                </div>
                            </div>
                            <input type="hidden" id="editSelectedChartType" name="chart_type" required>
                        </div>

                        <!-- Layout Size -->
                        <div class="form-group">
                            <label class="form-label" for="editLayoutSize">Widget Size</label>
                            <select class="form-select" id="editLayoutSize" name="layout_size">
                                <option value="small">Small (1x1)</option>
                                <option value="medium">Medium (1x2)</option>
                                <option value="large">Large (2x2)</option>
                            </select>
                        </div>

                        <!-- Refresh Interval -->
                        <div class="form-group">
                            <label class="form-label" for="editRefreshInterval">Auto-Refresh Interval</label>
                            <select class="form-select" id="editRefreshInterval" name="refresh_interval">
                                <option value="300">5 minutes</option>
                                <option value="600">10 minutes</option>
                                <option value="900">15 minutes</option>
                                <option value="1800">30 minutes</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>

                        <!-- Alert message -->
                        <div id="editWidgetAlert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-modal btn-modal-primary">
                            <i class="fa fa-save"></i> Update Widget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmLabel">Delete Widget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the widget <strong id="deleteWidgetName"></strong>?</p>
                    <p style="font-size: 12px; color: #999;">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-modal btn-modal-danger" id="confirmDeleteBtn">
                        <i class="fa fa-trash"></i> Delete Widget
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============= SCRIPTS ============= -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Configuration
        const CONFIG = {
            sessionId: '<?php echo htmlspecialchars($sessionId); ?>',
            apiBase: '/warehouse/modules/dashboard/api',
            autoRefreshInterval: 5 * 60 * 1000, // 5 minutes
            isEditor: <?php echo $isEditor ? 'true' : 'false'; ?>,
        };

        let widgets = [];
        let charts = {};
        let autoRefreshTimer = null;
        let deleteWidgetId = null;

        // ============= INITIALIZATION =============

        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
            setupEventListeners();
            loadWidgets();
            startAutoRefresh();
        });

        // ============= THEME MANAGEMENT =============

        function loadTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
                updateThemeToggle();
            }
        }

        function updateThemeToggle() {
            const toggle = document.getElementById('themeToggle');
            const isDark = document.body.classList.contains('dark-theme');
            toggle.innerHTML = isDark ? '<i class="fa fa-sun-o"></i>' : '<i class="fa fa-moon-o"></i>';
        }

        // ============= EVENT LISTENERS =============

        function setupEventListeners() {
            // Theme toggle
            document.getElementById('themeToggle').addEventListener('click', function() {
                document.body.classList.toggle('dark-theme');
                const isDark = document.body.classList.contains('dark-theme');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                updateThemeToggle();
            });

            // Data source selection in add widget form
            document.querySelectorAll('#dataSourceOptions .data-source-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('#dataSourceOptions .data-source-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selectedDataSource').value = this.getAttribute('data-source');
                });
            });

            // Chart type selection in add widget form
            document.querySelectorAll('#chartTypeOptions .chart-type-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('#chartTypeOptions .chart-type-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selectedChartType').value = this.getAttribute('data-chart');
                });
            });

            // Chart type selection in edit widget form
            document.querySelectorAll('#editChartTypeOptions .chart-type-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('#editChartTypeOptions .chart-type-option').forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('editSelectedChartType').value = this.getAttribute('data-chart');
                });
            });

            // Add widget button
            document.getElementById('addWidgetBtn')?.addEventListener('click', function() {
                resetAddWidgetForm();
                new bootstrap.Modal(document.getElementById('addWidgetModal')).show();
            });

            // Add widget form submission
            document.getElementById('addWidgetForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitAddWidget();
            });

            // Edit widget form submission
            document.getElementById('editWidgetForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitEditWidget();
            });

            // Delete confirmation
            document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
                submitDeleteWidget();
            });

            // Save layout button
            document.getElementById('saveLayoutBtn')?.addEventListener('click', function() {
                saveLayout();
            });

            // Reset layout button
            document.getElementById('resetLayoutBtn')?.addEventListener('click', function() {
                if (confirm('Reset dashboard to default layout? This cannot be undone.')) {
                    resetLayout();
                }
            });
        }

        // ============= API CALLS =============

        async function loadWidgets() {
            try {
                const response = await fetch(`${CONFIG.apiBase}/list.php?PHPSESSID=${CONFIG.sessionId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    widgets = data.data;
                    renderWidgets();
                    updateLastUpdated();
                } else {
                    showEmptyState();
                }
            } catch (error) {
                console.error('Error loading widgets:', error);
                showEmptyState();
            }
        }

        async function submitAddWidget() {
            const form = document.getElementById('addWidgetForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(`${CONFIG.apiBase}/create.php?PHPSESSID=${CONFIG.sessionId}`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addWidgetModal')).hide();
                    showAlert('addWidgetAlert', 'Widget created successfully!', 'success');
                    loadWidgets();
                } else {
                    showAlert('addWidgetAlert', data.message || 'Failed to create widget', 'error');
                }
            } catch (error) {
                console.error('Error creating widget:', error);
                showAlert('addWidgetAlert', 'Error: ' + error.message, 'error');
            }
        }

        async function submitEditWidget() {
            const form = document.getElementById('editWidgetForm');
            const formData = new FormData(form);

            try {
                const response = await fetch(`${CONFIG.apiBase}/update.php?PHPSESSID=${CONFIG.sessionId}`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editWidgetModal')).hide();
                    showAlert('editWidgetAlert', 'Widget updated successfully!', 'success');
                    loadWidgets();
                } else {
                    showAlert('editWidgetAlert', data.message || 'Failed to update widget', 'error');
                }
            } catch (error) {
                console.error('Error updating widget:', error);
                showAlert('editWidgetAlert', 'Error: ' + error.message, 'error');
            }
        }

        async function submitDeleteWidget() {
            if (!deleteWidgetId) return;

            try {
                const response = await fetch(`${CONFIG.apiBase}/delete.php?PHPSESSID=${CONFIG.sessionId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'widget_id=' + deleteWidgetId
                });
                const data = await response.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                    loadWidgets();
                } else {
                    alert(data.message || 'Failed to delete widget');
                }
            } catch (error) {
                console.error('Error deleting widget:', error);
                alert('Error: ' + error.message);
            }
        }

        async function loadWidgetData(widgetId) {
            try {
                const response = await fetch(`${CONFIG.apiBase}/data.php?widget_id=${widgetId}&PHPSESSID=${CONFIG.sessionId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    renderChart(widgetId, data.data);
                }
            } catch (error) {
                console.error('Error loading widget data:', error);
            }
        }

        // ============= RENDERING =============

        function renderWidgets() {
            const grid = document.getElementById('widgetGrid');
            const emptyState = document.getElementById('emptyState');

            if (!widgets || widgets.length === 0) {
                grid.innerHTML = emptyState.outerHTML;
                return;
            }

            grid.innerHTML = widgets.map(widget => `
                <div class="widget-card" data-widget-id="${widget.id}">
                    <div class="widget-header">
                        <div class="widget-title">${htmlspecialchars(widget.widget_name)}</div>
                        <div class="widget-actions">
                            <button class="widget-action-btn refresh-btn" title="Refresh data" data-widget-id="${widget.id}">
                                <i class="fa fa-refresh"></i>
                            </button>
                            ${CONFIG.isEditor ? `
                                <button class="widget-action-btn edit-btn" title="Edit widget" data-widget-id="${widget.id}">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="widget-action-btn delete-btn" title="Delete widget" data-widget-id="${widget.id}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="widget-body">
                        <div class="widget-chart-container">
                            <canvas id="chart-${widget.id}"></canvas>
                        </div>
                        <div class="widget-meta">
                            <small>
                                Type: ${htmlspecialchars(widget.chart_type)} |
                                Source: ${htmlspecialchars(widget.data_source)}
                            </small>
                        </div>
                    </div>
                </div>
            `).join('');

            // Attach event listeners to widget action buttons
            document.querySelectorAll('.refresh-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const widgetId = this.getAttribute('data-widget-id');
                    loadWidgetData(widgetId);
                });
            });

            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const widgetId = this.getAttribute('data-widget-id');
                    openEditWidget(widgetId);
                });
            });

            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const widgetId = this.getAttribute('data-widget-id');
                    openDeleteConfirm(widgetId);
                });
            });

            // Load data for all widgets
            widgets.forEach(widget => {
                loadWidgetData(widget.id);
            });
        }

        function renderChart(widgetId, chartData) {
            const widget = widgets.find(w => w.id === widgetId);
            if (!widget) return;

            const canvasId = `chart-${widgetId}`;
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            // Destroy existing chart if it exists
            if (charts[widgetId]) {
                charts[widgetId].destroy();
            }

            const ctx = canvas.getContext('2d');
            const config = {
                type: widget.chart_type,
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: widget.widget_name,
                        data: chartData.data || [],
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    },
                    scales: widget.chart_type !== 'pie' && widget.chart_type !== 'donut' && widget.chart_type !== 'polar' ? {
                        y: { beginAtZero: true }
                    } : {}
                }
            };

            charts[widgetId] = new Chart(ctx, config);
        }

        function showEmptyState() {
            const grid = document.getElementById('widgetGrid');
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa fa-inbox"></i>
                    </div>
                    <p>No widgets configured yet</p>
                    ${CONFIG.isEditor ? `
                        <p style="font-size: 12px; opacity: 0.7; margin-top: 0.5rem;">
                            Click "Add Widget" to create your first dashboard widget
                        </p>
                    ` : ''}
                </div>
            `;
        }

        // ============= MODAL OPERATIONS =============

        function resetAddWidgetForm() {
            document.getElementById('addWidgetForm').reset();
            document.getElementById('selectedDataSource').value = '';
            document.getElementById('selectedChartType').value = 'bar';
            document.querySelectorAll('#dataSourceOptions .data-source-option').forEach(o => o.classList.remove('selected'));
            document.querySelectorAll('#chartTypeOptions .chart-type-option').forEach(o => o.classList.remove('selected'));
            document.querySelector('[data-chart="bar"]').classList.add('selected');
            document.getElementById('addWidgetAlert').innerHTML = '';
        }

        function openEditWidget(widgetId) {
            const widget = widgets.find(w => w.id === widgetId);
            if (!widget) return;

            document.getElementById('editWidgetId').value = widget.id;
            document.getElementById('editWidgetName').value = widget.widget_name;
            document.getElementById('editLayoutSize').value = widget.layout_size;
            document.getElementById('editRefreshInterval').value = widget.config?.refresh_interval || 300;
            document.getElementById('editSelectedChartType').value = widget.chart_type;

            document.querySelectorAll('#editChartTypeOptions .chart-type-option').forEach(o => o.classList.remove('selected'));
            document.querySelector(`[data-chart="${widget.chart_type}"]`).classList.add('selected');

            document.getElementById('editWidgetAlert').innerHTML = '';
            new bootstrap.Modal(document.getElementById('editWidgetModal')).show();
        }

        function openDeleteConfirm(widgetId) {
            const widget = widgets.find(w => w.id === widgetId);
            if (!widget) return;

            deleteWidgetId = widgetId;
            document.getElementById('deleteWidgetName').textContent = widget.widget_name;
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }

        // ============= LAYOUT MANAGEMENT =============

        function saveLayout() {
            // Save widget order and configuration to localStorage
            const layout = widgets.map((w, idx) => ({
                id: w.id,
                position: idx
            }));
            localStorage.setItem('dashboardLayout', JSON.stringify(layout));
            alert('Layout saved successfully!');
        }

        function resetLayout() {
            // Clear saved layout
            localStorage.removeItem('dashboardLayout');
            loadWidgets();
        }

        // ============= AUTO-REFRESH =============

        function startAutoRefresh() {
            autoRefreshTimer = setInterval(() => {
                loadWidgets();
            }, CONFIG.autoRefreshInterval);
        }

        function stopAutoRefresh() {
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
            }
        }

        // ============= UTILITY FUNCTIONS =============

        function updateLastUpdated() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            document.querySelectorAll('#lastUpdated').forEach(el => {
                el.textContent = `Last updated: ${timeStr}`;
            });
        }

        function showAlert(elementId, message, type) {
            const alertElement = document.getElementById(elementId);
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            alertElement.innerHTML = `<div class="alert ${alertClass}">${htmlspecialchars(message)}</div>`;
        }

        function htmlspecialchars(str) {
            if (!str) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(str).replace(/[&<>"']/g, m => map[m]);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopAutoRefresh();
        });
    </script>
</body>
</html>
