<?php
/**
 * Reports Page
 *
 * Generate and export various warehouse reports
 * Features: Report filtering, export to CSV, save favorites
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load bootstrap and dependencies
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

// Check authentication
$authMiddleware = new AuthMiddleware();
if (!$authMiddleware->isAuthenticated()) {
    header('Location: /warehouse-productivity/public/views/login.php?timeout=1');
    exit;
}

// Check authorization - only admin and supervisors can view reports
if (!Authorization::hasPermission(Authorization::PERM_VIEW_REPORTS)) {
    http_response_code(403);
    die('<h1>Access Denied</h1><p>You do not have permission to view reports.</p>');
}

// Get current user info
$user = SessionManager::getCurrentUser();
$userName = $user['first_name'] . ' ' . $user['last_name'];

// Initialize variables
$report_type = $_GET['report_type'] ?? 'picking';
$date_range = $_GET['date_range'] ?? 'this_month';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$employee_id = $_GET['employee_id'] ?? '';
$warehouse_id = $_GET['warehouse_id'] ?? '';
$report_generated = isset($_GET['generated']);

// Determine date range based on preset
$today = date('Y-m-d');
$date_ranges = [
    'today' => ['label' => 'Today', 'from' => $today, 'to' => $today],
    'this_week' => ['label' => 'This Week', 'from' => date('Y-m-d', strtotime('monday this week')), 'to' => $today],
    'this_month' => ['label' => 'This Month', 'from' => date('Y-m-01'), 'to' => $today],
    'last_month' => ['label' => 'Last Month', 'from' => date('Y-m-01', strtotime('first day of last month')), 'to' => date('Y-m-t', strtotime('last day of last month'))],
    'custom' => ['label' => 'Custom', 'from' => $date_from, 'to' => $date_to],
];

// Set default date range
if (isset($date_ranges[$date_range])) {
    $date_from = $date_ranges[$date_range]['from'];
    $date_to = $date_ranges[$date_range]['to'];
}

// Report type definitions
$report_types = [
    'picking' => [
        'name' => 'Picking Report',
        'icon' => 'fa-boxes',
        'description' => 'Picking tasks and productivity metrics'
    ],
    'packing' => [
        'name' => 'Packing Report',
        'icon' => 'fa-cube',
        'description' => 'Packing operations and efficiency data'
    ],
    'warehouse' => [
        'name' => 'Warehouse Report',
        'icon' => 'fa-warehouse',
        'description' => 'Warehouse inventory and operations'
    ],
    'executive' => [
        'name' => 'Executive Summary',
        'icon' => 'fa-bar-chart',
        'description' => 'High-level business metrics and KPIs'
    ]
];

// Sample report data
$report_data = [];
if ($report_generated) {
    for ($i = 0; $i < 15; $i++) {
        $report_data[] = [
            'date' => date('Y-m-d', strtotime("-$i days")),
            'employee' => ['Maria Garcia', 'Juan Cruz', 'Ana Santos', 'Carlos Reyes'][array_rand([0, 1, 2, 3])],
            'warehouse' => ['Gingersnaps Main', 'Just G Branch'][array_rand([0, 1])],
            'tasks' => rand(25, 60),
            'completed' => rand(20, 58),
            'efficiency' => rand(75, 99) . '%',
            'avg_time' => rand(5, 12) . 'min'
        ];
    }
}

// Saved reports (favorites)
$saved_reports = [
    ['id' => 1, 'name' => 'Weekly Performance Report', 'type' => 'picking', 'created' => '2024-10-20'],
    ['id' => 2, 'name' => 'Monthly Inventory Check', 'type' => 'warehouse', 'created' => '2024-10-15'],
    ['id' => 3, 'name' => 'Executive Dashboard', 'type' => 'executive', 'created' => '2024-10-10'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - WPMS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            padding: 20px;
        }

        .container-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card-header {
            background: #f8f9fa;
            border: none;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h5 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        /* Report Type Selector */
        .report-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .report-type-card {
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            background: white;
        }

        .report-type-card:hover {
            border-color: #667eea;
            background: #eef2ff;
        }

        .report-type-card.selected {
            border-color: #667eea;
            background: #eef2ff;
        }

        .report-type-card i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 12px;
        }

        .report-type-card h6 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .report-type-card p {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        /* Filters */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control, .form-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .date-preset-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .date-preset-btn {
            padding: 6px 12px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .date-preset-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .date-preset-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-generate {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-export {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-export:hover {
            background: #218838;
            color: white;
        }

        .btn-save {
            padding: 10px 20px;
            background: #17a2b8;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: #138496;
            color: white;
        }

        /* Report Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .report-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        .report-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .report-table tr:hover {
            background: #fafafa;
        }

        /* Saved Reports */
        .saved-reports-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .saved-report-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .saved-report-info {
            flex: 1;
        }

        .saved-report-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .saved-report-meta {
            font-size: 12px;
            color: #666;
        }

        .saved-report-actions {
            display: flex;
            gap: 8px;
        }

        .saved-report-actions a {
            padding: 6px 12px;
            background: white;
            color: #667eea;
            border: 1px solid #667eea;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .saved-report-actions a:hover {
            background: #667eea;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .card-body {
                padding: 20px;
            }

            .report-types-grid {
                grid-template-columns: 1fr 1fr;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-generate, .btn-export, .btn-save {
                width: 100%;
            }

            .saved-report-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .saved-report-actions {
                margin-top: 10px;
                width: 100%;
            }

            .saved-report-actions a {
                flex: 1;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 20px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .report-types-grid {
                grid-template-columns: 1fr;
            }

            .report-type-card {
                padding: 15px;
            }

            .report-type-card i {
                font-size: 24px;
            }

            .report-table {
                font-size: 12px;
            }

            .report-table th, .report-table td {
                padding: 8px;
            }

            .date-preset-buttons {
                gap: 5px;
            }

            .date-preset-btn {
                padding: 5px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa fa-bar-chart"></i> Reports</h1>
            <p>Generate and export warehouse reports</p>
        </div>

        <!-- Report Type Selection -->
        <div class="card">
            <div class="card-header">
                <h5>Select Report Type</h5>
            </div>
            <div class="card-body">
                <div class="report-types-grid">
                    <?php foreach ($report_types as $key => $type): ?>
                        <div class="report-type-card <?php echo $report_type === $key ? 'selected' : ''; ?>"
                             onclick="selectReportType('<?php echo $key; ?>')">
                            <i class="fa <?php echo $type['icon']; ?>"></i>
                            <h6><?php echo htmlspecialchars($type['name']); ?></h6>
                            <p><?php echo htmlspecialchars($type['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card">
            <div class="card-header">
                <h5>Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="reportForm">
                    <input type="hidden" name="report_type" id="reportTypeInput" value="<?php echo htmlspecialchars($report_type); ?>">
                    <input type="hidden" name="generated" value="1">

                    <!-- Date Range Presets -->
                    <div class="form-group">
                        <label class="form-label">Date Range</label>
                        <div class="date-preset-buttons">
                            <button type="button" class="date-preset-btn <?php echo $date_range === 'today' ? 'active' : ''; ?>"
                                    onclick="setDateRange('today')">Today</button>
                            <button type="button" class="date-preset-btn <?php echo $date_range === 'this_week' ? 'active' : ''; ?>"
                                    onclick="setDateRange('this_week')">This Week</button>
                            <button type="button" class="date-preset-btn <?php echo $date_range === 'this_month' ? 'active' : ''; ?>"
                                    onclick="setDateRange('this_month')">This Month</button>
                            <button type="button" class="date-preset-btn <?php echo $date_range === 'last_month' ? 'active' : ''; ?>"
                                    onclick="setDateRange('last_month')">Last Month</button>
                            <button type="button" class="date-preset-btn <?php echo $date_range === 'custom' ? 'active' : ''; ?>"
                                    onclick="setDateRange('custom')">Custom</button>
                        </div>
                    </div>

                    <!-- Custom Date Inputs -->
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" id="date_from" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" id="date_to" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="form-group">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select id="employee_id" name="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                <option value="1" <?php echo $employee_id === '1' ? 'selected' : ''; ?>>Maria Garcia</option>
                                <option value="2" <?php echo $employee_id === '2' ? 'selected' : ''; ?>>Juan Cruz</option>
                                <option value="3" <?php echo $employee_id === '3' ? 'selected' : ''; ?>>Ana Santos</option>
                                <option value="4" <?php echo $employee_id === '4' ? 'selected' : ''; ?>>Carlos Reyes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="warehouse_id" class="form-label">Warehouse</label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select">
                                <option value="">All Warehouses</option>
                                <option value="g" <?php echo $warehouse_id === 'g' ? 'selected' : ''; ?>>Gingersnaps Main</option>
                                <option value="jg" <?php echo $warehouse_id === 'jg' ? 'selected' : ''; ?>>Just G Branch</option>
                            </select>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn-generate">
                            <i class="fa fa-refresh"></i> Generate Report
                        </button>
                        <?php if ($report_generated): ?>
                            <a href="/warehouse-productivity/public/pages/reports.php?report_type=<?php echo urlencode($report_type); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&export=csv" class="btn-export">
                                <i class="fa fa-download"></i> Export to CSV
                            </a>
                            <button type="button" class="btn-save" onclick="saveReport()">
                                <i class="fa fa-heart"></i> Save as Favorite
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Results -->
        <?php if ($report_generated && !empty($report_data)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Report Results: <?php echo htmlspecialchars($report_types[$report_type]['name']); ?></h5>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Warehouse</th>
                                    <th>Tasks</th>
                                    <th>Completed</th>
                                    <th>Efficiency</th>
                                    <th>Avg Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['employee']); ?></td>
                                        <td><?php echo htmlspecialchars($row['warehouse']); ?></td>
                                        <td><?php echo $row['tasks']; ?></td>
                                        <td><?php echo $row['completed']; ?></td>
                                        <td><?php echo htmlspecialchars($row['efficiency']); ?></td>
                                        <td><?php echo htmlspecialchars($row['avg_time']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif ($report_generated): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fa fa-inbox"></i>
                        <h5>No Data Found</h5>
                        <p>No records found for the selected filters. Try adjusting your date range or filters.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Saved Reports -->
        <div class="card">
            <div class="card-header">
                <h5>Saved Reports</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($saved_reports)): ?>
                    <div class="saved-reports-list">
                        <?php foreach ($saved_reports as $report): ?>
                            <div class="saved-report-item">
                                <div class="saved-report-info">
                                    <div class="saved-report-name"><?php echo htmlspecialchars($report['name']); ?></div>
                                    <div class="saved-report-meta">
                                        <i class="fa <?php echo $report_types[$report['type']]['icon']; ?>"></i>
                                        <?php echo htmlspecialchars($report_types[$report['type']]['name']); ?> |
                                        Created: <?php echo htmlspecialchars($report['created']); ?>
                                    </div>
                                </div>
                                <div class="saved-report-actions">
                                    <a href="?report_type=<?php echo urlencode($report['type']); ?>&generated=1">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="?report_type=<?php echo urlencode($report['type']); ?>&export=csv">
                                        <i class="fa fa-download"></i> Export
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-bookmark-o"></i>
                        <h5>No Saved Reports</h5>
                        <p>Generate and save your favorite reports to access them quickly.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function selectReportType(type) {
            document.getElementById('reportTypeInput').value = type;
            document.querySelectorAll('.report-type-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.target.closest('.report-type-card').classList.add('selected');
        }

        function setDateRange(range) {
            const form = document.getElementById('reportForm');
            const today = new Date();

            switch(range) {
                case 'today':
                    document.getElementById('date_from').value = formatDate(today);
                    document.getElementById('date_to').value = formatDate(today);
                    break;
                case 'this_week':
                    const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                    document.getElementById('date_from').value = formatDate(weekStart);
                    document.getElementById('date_to').value = formatDate(new Date());
                    break;
                case 'this_month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    document.getElementById('date_from').value = formatDate(monthStart);
                    document.getElementById('date_to').value = formatDate(new Date());
                    break;
                case 'last_month':
                    const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                    document.getElementById('date_from').value = formatDate(lastMonthStart);
                    document.getElementById('date_to').value = formatDate(lastMonthEnd);
                    break;
            }

            // Update active button
            document.querySelectorAll('.date-preset-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function saveReport() {
            const reportName = prompt('Enter a name for this report:');
            if (reportName) {
                alert('Report "' + reportName + '" saved successfully!');
            }
        }
    </script>
</body>
</html>
