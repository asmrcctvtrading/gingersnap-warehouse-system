<?php
/**
 * WPMS Dashboard
 */

require_once 'config.php';
requireLogin();

// Get dashboard statistics
$stats = [];

try {
    // Total employees
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM employees WHERE is_active = 1');
    $stats['total_employees'] = $stmt->fetch()['count'];

    // Active tasks today
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM picking_tasks WHERE DATE(task_start_time) = CURDATE() AND status IN ("in_progress", "completed")');
    $stats['active_picking_tasks'] = $stmt->fetch()['count'];

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM packing_tasks WHERE DATE(task_start_time) = CURDATE() AND package_status IN ("in_progress", "completed")');
    $stats['active_packing_tasks'] = $stmt->fetch()['count'];

    // Total warehouses
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM warehouses WHERE is_active = 1');
    $stats['total_warehouses'] = $stmt->fetch()['count'];

    // Error count today
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM picking_errors WHERE DATE(created_at) = CURDATE()');
    $stats['errors_today'] = $stmt->fetch()['count'];

    // Today's productivity
    $stmt = $pdo->query('SELECT SUM(total_tasks) as total, AVG(error_rate_percent) as error_rate FROM productivity_summaries WHERE period_date = CURDATE()');
    $productivity = $stmt->fetch();
    $stats['total_tasks_today'] = $productivity['total'] ?? 0;
    $stats['avg_error_rate'] = round($productivity['error_rate'] ?? 0, 2);

} catch (PDOException $e) {
    $error = 'Error loading statistics: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WPMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .navbar {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .navbar h1 {
            font-size: 24px;
            color: #667eea;
        }

        .navbar-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .navbar a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: #667eea;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #666;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-change {
            font-size: 12px;
            color: #28a745;
            margin-top: 10px;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        .content-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .content-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .quick-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: transform 0.3s;
        }

        .quick-link:hover {
            transform: translateY(-4px);
        }

        .quick-link strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .quick-link span {
            font-size: 12px;
            opacity: 0.9;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>WPMS</h1>
        <div class="navbar-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="tasks.php">Tasks</a>
            <a href="employees.php">Employees</a>
            <a href="reports.php">Reports</a>
            <span style="color: #999;">|</span>
            <span style="font-size: 12px; color: #666;">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="login.php?logout=1" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h2>Dashboard</h2>
            <p>Warehouse Productivity Monitoring Overview</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Employees</div>
                <div class="stat-value"><?= $stats['total_employees'] ?></div>
                <div class="stat-change">Active employees</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Active Picking Tasks</div>
                <div class="stat-value"><?= $stats['active_picking_tasks'] ?></div>
                <div class="stat-change">Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Active Packing Tasks</div>
                <div class="stat-value"><?= $stats['active_packing_tasks'] ?></div>
                <div class="stat-change">Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Warehouses</div>
                <div class="stat-value"><?= $stats['total_warehouses'] ?></div>
                <div class="stat-change">Active facilities</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Errors Reported</div>
                <div class="stat-value"><?= $stats['errors_today'] ?></div>
                <div class="stat-change negative">Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Error Rate</div>
                <div class="stat-value"><?= $stats['avg_error_rate'] ?>%</div>
                <div class="stat-change">Average</div>
            </div>
        </div>

        <div class="content-section">
            <h3>Quick Links</h3>
            <div class="quick-links">
                <a href="tasks.php?type=picking" class="quick-link">
                    <strong>Picking Tasks</strong>
                    <span>View & manage picking tasks</span>
                </a>
                <a href="tasks.php?type=packing" class="quick-link">
                    <strong>Packing Tasks</strong>
                    <span>View & manage packing tasks</span>
                </a>
                <a href="employees.php" class="quick-link">
                    <strong>Employees</strong>
                    <span>View employee profiles</span>
                </a>
                <a href="reports.php" class="quick-link">
                    <strong>Reports</strong>
                    <span>View productivity reports</span>
                </a>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>WPMS v1.0.0 | Gingersnaps Warehouse Management | © 2026</p>
    </div>
</body>
</html>
