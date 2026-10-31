<?php
/**
 * ReportController.php
 *
 * Handles reporting and dashboard requests:
 * - Dashboard metrics
 * - Report generation (picking, packing, warehouse, executive)
 * - Report export (CSV)
 * - Filtering and date range selection
 *
 * Endpoints:
 * - GET /api/dashboard/metrics - Get dashboard metrics
 * - GET /api/dashboard/daily - Get 7-day metrics
 * - GET /api/dashboard/leaderboard - Get employee leaderboard
 * - GET /api/report/picking - Generate picking report
 * - GET /api/report/packing - Generate packing report
 * - GET /api/report/warehouse - Generate warehouse report
 * - GET /api/report/executive - Generate executive summary
 * - GET /api/export/report - Export report to CSV
 */

class ReportController {
    private $pdo;
    private $userId;
    private $userRole;
    private $calculator;
    private $generator;

    public function __construct($pdo, $userId, $userRole = 'employee') {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userRole = $userRole;
        $this->calculator = new MetricsCalculator($pdo);
        $this->generator = new ReportGenerator($pdo);
    }

    /**
     * Get dashboard metrics
     * GET /api/dashboard/metrics
     */
    public function getDashboardMetrics() {
        try {
            // Default date range: today
            $endDate = date('Y-m-d');
            $startDate = $endDate;

            if (isset($_GET['start_date'])) {
                $startDate = $this->validateDate($_GET['start_date']);
            }
            if (isset($_GET['end_date'])) {
                $endDate = $this->validateDate($_GET['end_date']);
            }

            // Get metrics based on role
            $metrics = [];

            if ($this->userRole === 'admin') {
                $metrics = $this->getAdminMetrics($startDate, $endDate);
            } elseif ($this->userRole === 'supervisor') {
                $metrics = $this->getSupervisorMetrics($startDate, $endDate);
            } else {
                $metrics = $this->getEmployeeMetrics($startDate, $endDate);
            }

            return $this->success($metrics);

        } catch (\Exception $e) {
            error_log('Dashboard metrics error: ' . $e->getMessage());
            return $this->error('Failed to retrieve metrics', 500);
        }
    }

    /**
     * Get 7-day daily metrics
     * GET /api/dashboard/daily
     */
    public function getDailyMetrics() {
        try {
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-7 days'));

            $metrics = $this->calculator->getDailyMetrics($startDate, $endDate);

            return $this->success([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'daily_metrics' => $metrics
            ]);

        } catch (\Exception $e) {
            error_log('Daily metrics error: ' . $e->getMessage());
            return $this->error('Failed to retrieve daily metrics', 500);
        }
    }

    /**
     * Get employee leaderboard
     * GET /api/dashboard/leaderboard
     */
    public function getLeaderboard() {
        try {
            $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'completion_rate';
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10;

            // Default: past 30 days
            $endDate = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime('-30 days'));

            if (isset($_GET['start_date'])) {
                $startDate = $this->validateDate($_GET['start_date']);
            }
            if (isset($_GET['end_date'])) {
                $endDate = $this->validateDate($_GET['end_date']);
            }

            // Validate sort_by
            $validSortFields = ['completion_rate_percent', 'tasks_per_hour', 'error_rate_percent', 'productivity_score'];
            if (!in_array($sortBy, $validSortFields)) {
                $sortBy = 'completion_rate_percent';
            }

            $leaderboard = $this->calculator->getEmployeeLeaderboard($startDate, $endDate, $sortBy, $limit);

            return $this->success([
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'sort_by' => $sortBy,
                'limit' => $limit,
                'employees' => $leaderboard
            ]);

        } catch (\Exception $e) {
            error_log('Leaderboard error: ' . $e->getMessage());
            return $this->error('Failed to retrieve leaderboard', 500);
        }
    }

    /**
     * Generate picking performance report
     * GET /api/report/picking
     */
    public function getPickingReport() {
        // Authorization
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        try {
            $params = $this->getFilterParams();

            $report = $this->generator->generatePickingReport(
                $params['start_date'],
                $params['end_date'],
                $params['employee_id'],
                $params['warehouse_id'],
                $params['department_id']
            );

            return $this->success([
                'report_type' => 'picking_performance',
                'generated_at' => date('Y-m-d H:i:s'),
                'filters' => $params,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            error_log('Picking report error: ' . $e->getMessage());
            return $this->error('Failed to generate picking report', 500);
        }
    }

    /**
     * Generate packing performance report
     * GET /api/report/packing
     */
    public function getPackingReport() {
        // Authorization
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        try {
            $params = $this->getFilterParams();

            $report = $this->generator->generatePackingReport(
                $params['start_date'],
                $params['end_date'],
                $params['employee_id'],
                $params['warehouse_id'],
                $params['department_id']
            );

            return $this->success([
                'report_type' => 'packing_performance',
                'generated_at' => date('Y-m-d H:i:s'),
                'filters' => $params,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            error_log('Packing report error: ' . $e->getMessage());
            return $this->error('Failed to generate packing report', 500);
        }
    }

    /**
     * Generate warehouse productivity report
     * GET /api/report/warehouse
     */
    public function getWarehouseReport() {
        // Authorization
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        try {
            $params = $this->getFilterParams();

            $report = $this->generator->generateWarehouseReport(
                $params['start_date'],
                $params['end_date'],
                $params['warehouse_id']
            );

            return $this->success([
                'report_type' => 'warehouse_productivity',
                'generated_at' => date('Y-m-d H:i:s'),
                'filters' => $params,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            error_log('Warehouse report error: ' . $e->getMessage());
            return $this->error('Failed to generate warehouse report', 500);
        }
    }

    /**
     * Generate executive summary
     * GET /api/report/executive
     */
    public function getExecutiveSummary() {
        // Authorization: admin only
        if ($this->userRole !== 'admin') {
            return $this->error('Permission denied', 403);
        }

        try {
            $params = $this->getFilterParams();

            $report = $this->generator->generateExecutiveSummary(
                $params['start_date'],
                $params['end_date']
            );

            return $this->success([
                'report_type' => 'executive_summary',
                'generated_at' => date('Y-m-d H:i:s'),
                'filters' => $params,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            error_log('Executive summary error: ' . $e->getMessage());
            return $this->error('Failed to generate executive summary', 500);
        }
    }

    /**
     * Export report to CSV
     * GET /api/export/report
     */
    public function exportReport() {
        // Authorization
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            http_response_code(403);
            die('Permission denied');
        }

        try {
            $reportType = isset($_GET['type']) ? $_GET['type'] : null;
            $params = $this->getFilterParams();

            if (!$reportType) {
                http_response_code(400);
                die('Report type required');
            }

            // Generate report data
            $data = [];
            switch ($reportType) {
                case 'picking':
                    $data = $this->generator->generatePickingReport(
                        $params['start_date'], $params['end_date'],
                        $params['employee_id'], $params['warehouse_id'], $params['department_id']
                    );
                    break;
                case 'packing':
                    $data = $this->generator->generatePackingReport(
                        $params['start_date'], $params['end_date'],
                        $params['employee_id'], $params['warehouse_id'], $params['department_id']
                    );
                    break;
                case 'warehouse':
                    $data = $this->generator->generateWarehouseReport(
                        $params['start_date'], $params['end_date'],
                        $params['warehouse_id']
                    );
                    break;
                case 'executive':
                    if ($this->userRole !== 'admin') {
                        http_response_code(403);
                        die('Permission denied');
                    }
                    $data = $this->generator->generateExecutiveSummary(
                        $params['start_date'], $params['end_date']
                    );
                    break;
                default:
                    http_response_code(400);
                    die('Invalid report type');
            }

            // Export to CSV
            $exporter = new CSVExporter();
            $exporter->exportReport($data, $reportType, $params);
            exit;

        } catch (\Exception $e) {
            error_log('Export error: ' . $e->getMessage());
            http_response_code(500);
            die('Export failed');
        }
    }

    /**
     * Get filter parameters from request
     *
     * @return array Filter parameters
     */
    private function getFilterParams() {
        // Date range (default: this month)
        $endDate = isset($_GET['end_date']) ? $this->validateDate($_GET['end_date']) : date('Y-m-d');
        $startDate = isset($_GET['start_date']) ? $this->validateDate($_GET['start_date']) : date('Y-m-01');

        // Employee filter
        $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

        // Warehouse filter
        $warehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : null;

        // Department filter
        $departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'employee_id' => $employeeId,
            'warehouse_id' => $warehouseId,
            'department_id' => $departmentId
        ];
    }

    /**
     * Get admin-level metrics
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Admin metrics
     */
    private function getAdminMetrics($startDate, $endDate) {
        try {
            // Total tasks
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending,
                    SUM(has_error) as errors
                FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$startDate, $endDate]);
            $metrics = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_tasks_assigned' => (int)($metrics['total'] ?? 0),
                'total_tasks_completed' => (int)($metrics['completed'] ?? 0),
                'total_tasks_pending' => (int)($metrics['pending'] ?? 0),
                'tasks_with_errors' => (int)($metrics['errors'] ?? 0),
                'error_rate_percent' => $metrics['total'] > 0 ? round((($metrics['errors'] ?? 0) / $metrics['total']) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            error_log('Admin metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get supervisor-level metrics
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Supervisor metrics
     */
    private function getSupervisorMetrics($startDate, $endDate) {
        return $this->getAdminMetrics($startDate, $endDate);
    }

    /**
     * Get employee-level metrics (self only)
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Employee metrics
     */
    private function getEmployeeMetrics($startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending
                FROM picking_tasks
                WHERE employee_id = (SELECT id FROM employees WHERE user_id = ?)
                    AND DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$this->userId, $startDate, $endDate]);
            $metrics = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'my_total_tasks' => (int)($metrics['total'] ?? 0),
                'my_tasks_completed' => (int)($metrics['completed'] ?? 0),
                'my_tasks_pending' => (int)($metrics['pending'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Employee metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     *
     * @param string $date Date string
     * @return string Validated date
     */
    private function validateDate($date) {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return date('Y-m-d');
        }
        return $date;
    }

    /**
     * Return success response
     *
     * @param array $data Response data
     * @return array JSON response
     */
    private function success($data) {
        return [
            'success' => true,
            'code' => 200,
            'data' => $data
        ];
    }

    /**
     * Return error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @return array JSON response
     */
    private function error($message, $code = 400) {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message
        ];
    }
}
?>
