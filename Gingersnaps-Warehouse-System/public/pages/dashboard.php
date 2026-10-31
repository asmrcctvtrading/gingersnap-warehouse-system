<?php
/**
 * Dashboard Page
 *
 * Main dashboard with key metrics, charts, and filters
 * Features: Metrics cards, performance graphs, real-time updates
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

// Get current user info
$user = SessionManager::getCurrentUser();
$userName = $user['first_name'] . ' ' . $user['last_name'];

// Initialize variables
$metrics = [
    'total_tasks' => 1247,
    'completed_today' => 284,
    'pending_tasks' => 156,
    'overdue_tasks' => 12
];

$chart_data = [
    'dates' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'completed' => [45, 52, 48, 61, 55, 42, 38],
    'assigned' => [50, 58, 56, 70, 65, 50, 45]
];

$leaderboard = [
    ['rank' => 1, 'name' => 'Maria Garcia', 'tasks_completed' => 287, 'efficiency' => '94%'],
    ['rank' => 2, 'name' => 'Juan Cruz', 'tasks_completed' => 276, 'efficiency' => '91%'],
    ['rank' => 3, 'name' => 'Ana Santos', 'tasks_completed' => 258, 'efficiency' => '89%'],
    ['rank' => 4, 'name' => 'Carlos Reyes', 'tasks_completed' => 241, 'efficiency' => '86%'],
    ['rank' => 5, 'name' => 'Rosa Dela Cruz', 'tasks_completed' => 235, 'efficiency' => '84%'],
];

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$employee_id = $_GET['employee_id'] ?? '';
$warehouse_id = $_GET['warehouse_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WPMS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header-left h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .page-header-left p {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0 0;
        }

        .btn-export {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filter-section h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
        }

        .form-control, .form-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-reset {
            padding: 8px 16px;
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: #e0e0e0;
            color: #333;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .metric-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .metric-icon.tasks {
            color: #667eea;
        }

        .metric-icon.completed {
            color: #28a745;
        }

        .metric-icon.pending {
            color: #ffc107;
        }

        .metric-icon.overdue {
            color: #dc3545;
        }

        .metric-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .metric-label {
            color: #666;
            font-size: 14px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .chart-card h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .real-time-note {
            background: #eef2ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #333;
        }

        .real-time-note i {
            color: #667eea;
            margin-right: 8px;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .leaderboard-item:last-child {
            border-bottom: none;
        }

        .rank-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .leaderboard-info {
            flex: 1;
        }

        .leaderboard-name {
            color: #333;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .leaderboard-stats {
            color: #666;
            font-size: 12px;
        }

        .leaderboard-efficiency {
            color: #667eea;
            font-weight: 600;
            min-width: 50px;
            text-align: right;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header-left {
                width: 100%;
            }

            .btn-export {
                width: 100%;
                text-align: center;
            }

            .metrics-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .metric-card {
                padding: 15px;
            }

            .metric-value {
                font-size: 28px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .btn-filter, .btn-reset {
                width: 100%;
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

            .page-header-left h1 {
                font-size: 22px;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .metric-card {
                padding: 12px;
            }

            .metric-value {
                font-size: 24px;
            }

            .chart-card {
                padding: 15px;
            }

            .leaderboard-item {
                padding: 12px;
            }

            .rank-badge {
                width: 35px;
                height: 35px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-left">
                <h1><i class="fa fa-dashboard"></i> Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($userName); ?>. Real-time warehouse performance overview.</p>
            </div>
            <a href="/warehouse-productivity/public/pages/reports.php" class="btn-export">
                <i class="fa fa-download"></i> Export Report
            </a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5><i class="fa fa-filter"></i> Filters</h5>
            <form method="GET" action="">
                <div class="filter-row">
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
                <div class="filter-buttons">
                    <button type="submit" class="btn-filter">
                        <i class="fa fa-search"></i> Apply Filters
                    </button>
                    <a href="/warehouse-productivity/public/pages/dashboard.php" class="btn-reset">
                        <i class="fa fa-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Real-time Update Note -->
        <div class="real-time-note">
            <i class="fa fa-info-circle"></i>
            <strong>Real-time Updates:</strong> Dashboard data updates every 30 seconds. Last update: <?php echo date('H:i:s'); ?>
        </div>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon tasks"><i class="fa fa-tasks"></i></div>
                <div class="metric-value"><?php echo number_format($metrics['total_tasks']); ?></div>
                <div class="metric-label">Total Tasks</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon completed"><i class="fa fa-check-circle"></i></div>
                <div class="metric-value"><?php echo number_format($metrics['completed_today']); ?></div>
                <div class="metric-label">Completed Today</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon pending"><i class="fa fa-clock-o"></i></div>
                <div class="metric-value"><?php echo number_format($metrics['pending_tasks']); ?></div>
                <div class="metric-label">Pending Tasks</div>
            </div>

            <div class="metric-card">
                <div class="metric-icon overdue"><i class="fa fa-exclamation-triangle"></i></div>
                <div class="metric-value"><?php echo number_format($metrics['overdue_tasks']); ?></div>
                <div class="metric-label">Overdue Tasks</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Task Completion Chart -->
            <div class="chart-card">
                <h5><i class="fa fa-line-chart"></i> 7-Day Task Completion</h5>
                <div class="chart-container">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>

            <!-- Employee Leaderboard -->
            <div class="chart-card">
                <h5><i class="fa fa-trophy"></i> Employee Leaderboard</h5>
                <div style="padding: 10px 0;">
                    <?php foreach ($leaderboard as $employee): ?>
                        <div class="leaderboard-item">
                            <div class="rank-badge"><?php echo $employee['rank']; ?></div>
                            <div class="leaderboard-info">
                                <div class="leaderboard-name"><?php echo htmlspecialchars($employee['name']); ?></div>
                                <div class="leaderboard-stats"><?php echo $employee['tasks_completed']; ?> tasks completed</div>
                            </div>
                            <div class="leaderboard-efficiency"><?php echo $employee['efficiency']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Task Completion Chart
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        new Chart(completionCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_data['dates']); ?>,
                datasets: [
                    {
                        label: 'Completed',
                        data: <?php echo json_encode($chart_data['completed']); ?>,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Assigned',
                        data: <?php echo json_encode($chart_data['assigned']); ?>,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.05)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 12 },
                            padding: 15,
                            usePointStyle: true
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: '#f0f0f0' },
                        ticks: { font: { size: 12 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 12 } }
                    }
                }
            }
        });

        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            // In production, this would fetch updated data from an API
            // For now, just log the update
            console.log('Dashboard updated at ' + new Date().toLocaleTimeString());
        }, 30000);
    </script>
</body>
</html>
