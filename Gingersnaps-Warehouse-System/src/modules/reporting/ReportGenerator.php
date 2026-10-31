<?php
/**
 * ReportGenerator.php
 *
 * Generates comprehensive productivity reports for WPMS warehouse system.
 * Supports 4 report types with flexible filtering and date range options.
 *
 * Reports Generated:
 * 1. Picking Performance Report - Employee-level picking metrics
 * 2. Packing Performance Report - Employee-level packing metrics
 * 3. Warehouse Productivity Report - Warehouse/team level aggregates
 * 4. Executive Summary - High-level KPIs and key metrics
 *
 * All reports return data in consistent JSON structure ready for CSV export.
 *
 * Usage:
 *  $generator = new ReportGenerator($pdo);
 *  $report = $generator->generatePickingPerformanceReport(
 *      '2026-10-01',
 *      '2026-10-31',
 *      ['warehouse_id' => 1, 'department_id' => 2]
 *  );
 *
 * @author WPMS Development Team
 * @version 1.0.0
 * @date October 24, 2026
 */

class ReportGenerator {
    private $pdo;
    private $metricsCalculator;

    /**
     * Constructor - Initialize report generator with database connection
     *
     * @param PDO $pdo Database connection object
     * @throws Exception If PDO connection is invalid
     */
    public function __construct($pdo) {
        if (!($pdo instanceof \PDO)) {
            throw new \Exception('Invalid PDO connection provided');
        }

        $this->pdo = $pdo;
        // Auto-load MetricsCalculator if available
        if (class_exists('MetricsCalculator')) {
            $this->metricsCalculator = new MetricsCalculator($pdo);
        }
    }

    /**
     * Generate Picking Performance Report
     *
     * Returns detailed picking metrics per employee including:
     * - Task counts (total, completed, pending)
     * - Average pick time
     * - Error rates and rework percentages
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $filters Optional filters:
     *   - warehouse_id: int
     *   - department_id: int
     *   - employee_id: int
     * @return array Report data structure
     */
    public function generatePickingPerformanceReport($startDate, $endDate, $filters = []) {
        try {
            // Validate date range
            $this->validateDateRange($startDate, $endDate);

            // Build employee list with filters
            $employees = $this->getFilteredEmployees($filters);

            $reportData = [];

            foreach ($employees as $employee) {
                $metrics = $this->getPickingMetricsForEmployee(
                    $employee['id'],
                    $startDate,
                    $endDate
                );

                // Get error details for this employee
                $errorDetails = $this->getPickingErrorsForEmployee(
                    $employee['id'],
                    $startDate,
                    $endDate
                );

                // Get rework count
                $reworkCount = $this->getPickingReworkCount(
                    $employee['id'],
                    $startDate,
                    $endDate
                );

                $reportData[] = [
                    'employee_id' => $employee['id'],
                    'employee_name' => $employee['full_name'],
                    'employee_code' => $employee['emp_id'],
                    'department' => $employee['department_name'],
                    'warehouse' => $employee['warehouse_name'],
                    'total_picks' => (int)$metrics['total_tasks'],
                    'completed_picks' => (int)$metrics['completed_tasks'],
                    'pending_picks' => (int)$metrics['pending_tasks'],
                    'cancelled_picks' => (int)$metrics['cancelled_tasks'],
                    'avg_pick_time_seconds' => (int)$metrics['avg_task_duration'],
                    'avg_pick_time_minutes' => round($metrics['avg_task_duration'] / 60, 1),
                    'total_items_picked' => (int)$metrics['total_quantity'],
                    'error_count' => (int)$errorDetails['total_errors'],
                    'error_rate_percent' => $metrics['error_rate'],
                    'error_types' => $errorDetails['error_types'],
                    'rework_count' => (int)$reworkCount,
                    'rework_percent' => $metrics['completed_tasks'] > 0 ?
                        round(($reworkCount / $metrics['completed_tasks']) * 100, 2) : 0,
                    'completion_rate_percent' => $metrics['completion_rate'],
                    'tasks_per_hour' => $metrics['tasks_per_hour'],
                    'picks_per_task' => $metrics['picks_per_task']
                ];
            }

            return [
                'report_type' => 'picking_performance',
                'generated_at' => date('Y-m-d H:i:s'),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'filters_applied' => $filters,
                'record_count' => count($reportData),
                'data' => $reportData
            ];

        } catch (\Exception $e) {
            error_log('Picking Performance Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Packing Performance Report
     *
     * Returns detailed packing metrics per employee including:
     * - Task counts (total, completed, pending)
     * - Average pack time
     * - Quality error rates and damage counts
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $filters Optional filters
     * @return array Report data structure
     */
    public function generatePackingPerformanceReport($startDate, $endDate, $filters = []) {
        try {
            $this->validateDateRange($startDate, $endDate);
            $employees = $this->getFilteredEmployees($filters);

            $reportData = [];

            foreach ($employees as $employee) {
                $metrics = $this->getPackingMetricsForEmployee(
                    $employee['id'],
                    $startDate,
                    $endDate
                );

                // Get quality issue details
                $qualityIssues = $this->getPackingQualityIssues(
                    $employee['id'],
                    $startDate,
                    $endDate
                );

                // Get rework and damage counts
                $reworkCount = $qualityIssues['rework_count'];
                $damageCount = $qualityIssues['damage_count'];
                $discrepancyCount = $qualityIssues['discrepancy_count'];

                $reportData[] = [
                    'employee_id' => $employee['id'],
                    'employee_name' => $employee['full_name'],
                    'employee_code' => $employee['emp_id'],
                    'department' => $employee['department_name'],
                    'warehouse' => $employee['warehouse_name'],
                    'total_packs' => (int)$metrics['total_tasks'],
                    'completed_packs' => (int)$metrics['completed_tasks'],
                    'pending_packs' => (int)$metrics['pending_tasks'],
                    'cancelled_packs' => (int)$metrics['cancelled_tasks'],
                    'avg_pack_time_seconds' => (int)$metrics['avg_task_duration'],
                    'avg_pack_time_minutes' => round($metrics['avg_task_duration'] / 60, 1),
                    'total_items_packed' => (int)$metrics['total_items'],
                    'quality_error_count' => (int)$metrics['quality_issues'],
                    'quality_error_rate_percent' => $metrics['quality_error_rate'],
                    'damage_count' => (int)$damageCount,
                    'damage_rate_percent' => $metrics['total_tasks'] > 0 ?
                        round(($damageCount / $metrics['total_tasks']) * 100, 2) : 0,
                    'discrepancy_count' => (int)$discrepancyCount,
                    'rework_required_count' => (int)$reworkCount,
                    'completion_rate_percent' => $metrics['completion_rate'],
                    'tasks_per_hour' => $metrics['tasks_per_hour'],
                    'items_per_task' => $metrics['items_per_task']
                ];
            }

            return [
                'report_type' => 'packing_performance',
                'generated_at' => date('Y-m-d H:i:s'),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'filters_applied' => $filters,
                'record_count' => count($reportData),
                'data' => $reportData
            ];

        } catch (\Exception $e) {
            error_log('Packing Performance Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Warehouse Productivity Report
     *
     * Aggregates team-level metrics by warehouse showing:
     * - Total employees, tasks assigned/completed
     * - Picking vs packing task split
     * - Team average completion time
     * - Top performers by warehouse
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $filters Optional filters (warehouse_id, department_id)
     * @return array Report data structure
     */
    public function generateWarehouseProductivityReport($startDate, $endDate, $filters = []) {
        try {
            $this->validateDateRange($startDate, $endDate);

            // Get list of warehouses to report on
            $warehouses = $this->getFilteredWarehouses($filters);
            $reportData = [];

            foreach ($warehouses as $warehouse) {
                // Get all active employees in warehouse
                $employees = $this->getWarehouseEmployees($warehouse['id']);
                $employeeIds = array_column($employees, 'id');

                if (empty($employeeIds)) {
                    continue; // Skip warehouses with no employees
                }

                // Aggregate picking metrics
                $pickingStats = $this->getAggregatePickingStats(
                    $employeeIds,
                    $startDate,
                    $endDate
                );

                // Aggregate packing metrics
                $packingStats = $this->getAggregatePackingStats(
                    $employeeIds,
                    $startDate,
                    $endDate
                );

                // Get top performers
                $topPerformers = $this->getWarehouseTopPerformers(
                    $warehouse['id'],
                    $startDate,
                    $endDate,
                    5 // Top 5
                );

                $totalTasks = $pickingStats['total_tasks'] + $packingStats['total_tasks'];
                $completedTasks = $pickingStats['completed_tasks'] + $packingStats['completed_tasks'];
                $totalDuration = $pickingStats['total_duration'] + $packingStats['total_duration'];

                $reportData[] = [
                    'warehouse_id' => $warehouse['id'],
                    'warehouse_name' => $warehouse['name'],
                    'warehouse_location' => $warehouse['location'],
                    'active_employees' => count($employees),
                    'total_tasks_assigned' => (int)$totalTasks,
                    'total_tasks_completed' => (int)$completedTasks,
                    'total_tasks_pending' => (int)($totalTasks - $completedTasks),
                    'picking_tasks' => (int)$pickingStats['total_tasks'],
                    'picking_completed' => (int)$pickingStats['completed_tasks'],
                    'packing_tasks' => (int)$packingStats['total_tasks'],
                    'packing_completed' => (int)$packingStats['completed_tasks'],
                    'overall_completion_rate_percent' => $totalTasks > 0 ?
                        round(($completedTasks / $totalTasks) * 100, 2) : 0,
                    'team_avg_task_duration_seconds' => $completedTasks > 0 ?
                        round($totalDuration / $completedTasks, 0) : 0,
                    'team_avg_task_duration_minutes' => $completedTasks > 0 ?
                        round($totalDuration / $completedTasks / 60, 1) : 0,
                    'overall_error_rate_percent' => $totalTasks > 0 ?
                        round((($pickingStats['error_count'] + $packingStats['error_count']) / $totalTasks) * 100, 2) : 0,
                    'team_avg_tasks_per_hour' => $totalDuration > 0 ?
                        round($completedTasks / ($totalDuration / 3600), 2) : 0,
                    'top_performers' => $topPerformers,
                    'departments_count' => $this->getWarehouseDepartmentCount($warehouse['id'])
                ];
            }

            return [
                'report_type' => 'warehouse_productivity',
                'generated_at' => date('Y-m-d H:i:s'),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'filters_applied' => $filters,
                'warehouse_count' => count($reportData),
                'data' => $reportData
            ];

        } catch (\Exception $e) {
            error_log('Warehouse Productivity Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Executive Summary Report
     *
     * High-level KPI summary including:
     * - Total tasks processed
     * - Overall completion rates
     * - Total processing hours
     * - Average task duration
     * - Top 5 performing employees
     * - Outstanding tasks by priority
     * - Quality metrics
     *
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param array $filters Optional filters
     * @return array Report data structure
     */
    public function generateExecutiveSummaryReport($startDate, $endDate, $filters = []) {
        try {
            $this->validateDateRange($startDate, $endDate);

            // Get total tasks across all employees
            $totalStats = $this->getTotalSystemStats($startDate, $endDate, $filters);

            // Get top 5 performers overall
            $topPerformers = $this->getTopPerformersOverall($startDate, $endDate, 5);

            // Get outstanding (pending/in_progress) tasks
            $outstandingTasks = $this->getOutstandingTasks($filters);

            // Get quality metrics summary
            $qualityMetrics = $this->getQualitySummary($startDate, $endDate, $filters);

            // Get productivity trend (daily average)
            $productivityTrend = $this->getProductivityTrend($startDate, $endDate);

            // Calculate key ratios
            $completionRate = $totalStats['total_tasks'] > 0 ?
                round(($totalStats['completed_tasks'] / $totalStats['total_tasks']) * 100, 2) : 0;

            $totalHours = $totalStats['total_duration_seconds'] > 0 ?
                round($totalStats['total_duration_seconds'] / 3600, 2) : 0;

            $avgTaskDuration = $totalStats['completed_tasks'] > 0 ?
                round($totalStats['total_duration_seconds'] / $totalStats['completed_tasks'], 0) : 0;

            $tasksPerHour = $totalStats['total_duration_seconds'] > 0 ?
                round($totalStats['completed_tasks'] / ($totalStats['total_duration_seconds'] / 3600), 2) : 0;

            return [
                'report_type' => 'executive_summary',
                'generated_at' => date('Y-m-d H:i:s'),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'business_days' => $this->countBusinessDays($startDate, $endDate)
                ],
                'filters_applied' => $filters,
                'summary' => [
                    'total_tasks_processed' => (int)$totalStats['total_tasks'],
                    'completed_tasks' => (int)$totalStats['completed_tasks'],
                    'pending_tasks' => (int)$totalStats['pending_tasks'],
                    'cancelled_tasks' => (int)$totalStats['cancelled_tasks'],
                    'overall_completion_rate_percent' => $completionRate,
                    'total_processing_hours' => $totalHours,
                    'average_task_duration_seconds' => (int)$avgTaskDuration,
                    'average_task_duration_minutes' => round($avgTaskDuration / 60, 1),
                    'tasks_per_hour' => $tasksPerHour,
                    'total_employees_active' => (int)$totalStats['unique_employees'],
                    'avg_tasks_per_employee' => $totalStats['unique_employees'] > 0 ?
                        round($totalStats['completed_tasks'] / $totalStats['unique_employees'], 1) : 0
                ],
                'quality_metrics' => [
                    'total_errors' => (int)$qualityMetrics['total_errors'],
                    'error_rate_percent' => $qualityMetrics['error_rate'],
                    'total_rework_items' => (int)$qualityMetrics['total_rework'],
                    'quality_issue_count' => (int)$qualityMetrics['quality_issues'],
                    'quality_issue_rate_percent' => $qualityMetrics['quality_issue_rate']
                ],
                'top_performers' => $topPerformers,
                'outstanding_work' => [
                    'pending_count' => $outstandingTasks['pending_count'],
                    'in_progress_count' => $outstandingTasks['in_progress_count'],
                    'total_outstanding' => $outstandingTasks['total_outstanding']
                ],
                'productivity_trend' => $productivityTrend,
                'notes' => 'Executive summary covering period from ' . $startDate . ' to ' . $endDate
            ];

        } catch (\Exception $e) {
            error_log('Executive Summary Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * Validate date range
     */
    private function validateDateRange($startDate, $endDate) {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        if (!$start || !$end) {
            throw new \Exception('Invalid date format. Use YYYY-MM-DD');
        }

        if ($start > $end) {
            throw new \Exception('Start date must be before end date');
        }
    }

    /**
     * Get filtered list of employees based on filters
     */
    private function getFilteredEmployees($filters = []) {
        $sql = "SELECT e.id, e.emp_id, e.full_name, e.department_id,
                       d.name as department_name, w.name as warehouse_name
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                JOIN warehouses w ON e.warehouse_id = w.id
                WHERE e.is_active = 1 AND e.employment_status = 'active'";

        $params = [];

        if (isset($filters['warehouse_id'])) {
            $sql .= " AND e.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }

        if (isset($filters['department_id'])) {
            $sql .= " AND e.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (isset($filters['employee_id'])) {
            $sql .= " AND e.id = ?";
            $params[] = $filters['employee_id'];
        }

        $sql .= " ORDER BY e.full_name ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting filtered employees: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get picking metrics for single employee
     */
    private function getPickingMetricsForEmployee($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(has_error) as error_count,
                    SUM(task_duration_seconds) as total_duration,
                    AVG(CASE WHEN status = 'completed' THEN task_duration_seconds END) as avg_task_duration,
                    SUM(quantity_ordered) as total_quantity
                FROM picking_tasks
                WHERE employee_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || $row['total_tasks'] == 0) {
                return [
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'pending_tasks' => 0,
                    'cancelled_tasks' => 0,
                    'error_count' => 0,
                    'avg_task_duration' => 0,
                    'error_rate' => 0,
                    'completion_rate' => 0,
                    'tasks_per_hour' => 0,
                    'picks_per_task' => 0,
                    'total_quantity' => 0
                ];
            }

            $total = (int)$row['total_tasks'];
            $completed = (int)$row['completed_tasks'];
            $errors = (int)$row['error_count'];
            $duration = (int)$row['total_duration'];
            $quantity = (int)$row['total_quantity'];

            return [
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'pending_tasks' => (int)$row['pending_tasks'],
                'cancelled_tasks' => (int)$row['cancelled_tasks'],
                'error_count' => $errors,
                'avg_task_duration' => $completed > 0 ? (int)$row['avg_task_duration'] : 0,
                'error_rate' => $total > 0 ? round(($errors / $total) * 100, 2) : 0,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'tasks_per_hour' => $duration > 0 ? round($completed / ($duration / 3600), 2) : 0,
                'picks_per_task' => $completed > 0 ? round($quantity / $completed, 2) : 0,
                'total_quantity' => $quantity
            ];

        } catch (\Exception $e) {
            error_log('Error getting picking metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get picking errors for employee
     */
    private function getPickingErrorsForEmployee($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_errors,
                    error_type,
                    COUNT(*) as count
                FROM picking_errors pe
                JOIN picking_tasks pt ON pe.picking_task_id = pt.id
                WHERE pt.employee_id = ?
                    AND DATE(pt.created_at) BETWEEN ? AND ?
                GROUP BY error_type
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $totalErrors = 0;
            $errorTypes = [];

            foreach ($rows as $row) {
                $totalErrors += (int)$row['count'];
                $errorTypes[(string)$row['error_type']] = (int)$row['count'];
            }

            return [
                'total_errors' => $totalErrors,
                'error_types' => $errorTypes
            ];

        } catch (\Exception $e) {
            error_log('Error getting picking errors: ' . $e->getMessage());
            return ['total_errors' => 0, 'error_types' => []];
        }
    }

    /**
     * Get picking rework count
     */
    private function getPickingReworkCount($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as rework_count
                FROM picking_errors
                WHERE picking_task_id IN (
                    SELECT id FROM picking_tasks
                    WHERE employee_id = ?
                        AND DATE(created_at) BETWEEN ? AND ?
                )
                AND requires_rework = 1
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($row['rework_count'] ?? 0);

        } catch (\Exception $e) {
            error_log('Error getting rework count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get packing metrics for single employee
     */
    private function getPackingMetricsForEmployee($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN package_status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN package_status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN package_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(has_quality_issue) as quality_issues,
                    SUM(has_error) as error_count,
                    SUM(task_duration_seconds) as total_duration,
                    AVG(CASE WHEN package_status = 'completed' THEN task_duration_seconds END) as avg_task_duration,
                    SUM(quantity_items) as total_items
                FROM packing_tasks
                WHERE employee_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || $row['total_tasks'] == 0) {
                return [
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'pending_tasks' => 0,
                    'cancelled_tasks' => 0,
                    'quality_issues' => 0,
                    'avg_task_duration' => 0,
                    'quality_error_rate' => 0,
                    'completion_rate' => 0,
                    'tasks_per_hour' => 0,
                    'items_per_task' => 0,
                    'total_items' => 0
                ];
            }

            $total = (int)$row['total_tasks'];
            $completed = (int)$row['completed_tasks'];
            $issues = (int)$row['quality_issues'];
            $duration = (int)$row['total_duration'];
            $items = (int)$row['total_items'];

            return [
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'pending_tasks' => (int)$row['pending_tasks'],
                'cancelled_tasks' => (int)$row['cancelled_tasks'],
                'quality_issues' => $issues,
                'error_count' => (int)$row['error_count'],
                'avg_task_duration' => $completed > 0 ? (int)$row['avg_task_duration'] : 0,
                'quality_error_rate' => $total > 0 ? round(($issues / $total) * 100, 2) : 0,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'tasks_per_hour' => $duration > 0 ? round($completed / ($duration / 3600), 2) : 0,
                'items_per_task' => $completed > 0 ? round($items / $completed, 2) : 0,
                'total_items' => $items
            ];

        } catch (\Exception $e) {
            error_log('Error getting packing metrics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get packing quality issues
     */
    private function getPackingQualityIssues($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    SUM(CASE WHEN rework_required = 1 THEN 1 ELSE 0 END) as rework_count,
                    SUM(CASE WHEN error_type = 'DAMAGED_PACKAGE' THEN 1 ELSE 0 END) as damage_count,
                    SUM(CASE WHEN error_type LIKE '%DISCREPANCY%' THEN 1 ELSE 0 END) as discrepancy_count
                FROM packing_errors
                WHERE packing_task_id IN (
                    SELECT id FROM packing_tasks
                    WHERE employee_id = ?
                        AND DATE(created_at) BETWEEN ? AND ?
                )
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'rework_count' => (int)($row['rework_count'] ?? 0),
                'damage_count' => (int)($row['damage_count'] ?? 0),
                'discrepancy_count' => (int)($row['discrepancy_count'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Error getting quality issues: ' . $e->getMessage());
            return ['rework_count' => 0, 'damage_count' => 0, 'discrepancy_count' => 0];
        }
    }

    /**
     * Get filtered list of warehouses
     */
    private function getFilteredWarehouses($filters = []) {
        $sql = "SELECT id, name, location FROM warehouses WHERE is_active = 1";
        $params = [];

        if (isset($filters['warehouse_id'])) {
            $sql .= " AND id = ?";
            $params[] = $filters['warehouse_id'];
        }

        $sql .= " ORDER BY name ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Error getting filtered warehouses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all employees in warehouse
     */
    private function getWarehouseEmployees($warehouseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM employees
                WHERE warehouse_id = ? AND is_active = 1 AND employment_status = 'active'
            ");

            $stmt->execute([$warehouseId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            error_log('Error getting warehouse employees: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get aggregate picking stats for multiple employees
     */
    private function getAggregatePickingStats($employeeIds, $startDate, $endDate) {
        try {
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(task_duration_seconds) as total_duration,
                    SUM(has_error) as error_count
                FROM picking_tasks
                WHERE employee_id IN ($placeholders)
                    AND DATE(created_at) BETWEEN ? AND ?
            ");

            $params = array_merge($employeeIds, [$startDate, $endDate]);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_tasks' => (int)($row['total_tasks'] ?? 0),
                'completed_tasks' => (int)($row['completed_tasks'] ?? 0),
                'total_duration' => (int)($row['total_duration'] ?? 0),
                'error_count' => (int)($row['error_count'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Error getting aggregate picking stats: ' . $e->getMessage());
            return ['total_tasks' => 0, 'completed_tasks' => 0, 'total_duration' => 0, 'error_count' => 0];
        }
    }

    /**
     * Get aggregate packing stats for multiple employees
     */
    private function getAggregatePackingStats($employeeIds, $startDate, $endDate) {
        try {
            $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));

            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN package_status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(task_duration_seconds) as total_duration,
                    SUM(has_quality_issue) as error_count
                FROM packing_tasks
                WHERE employee_id IN ($placeholders)
                    AND DATE(created_at) BETWEEN ? AND ?
            ");

            $params = array_merge($employeeIds, [$startDate, $endDate]);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_tasks' => (int)($row['total_tasks'] ?? 0),
                'completed_tasks' => (int)($row['completed_tasks'] ?? 0),
                'total_duration' => (int)($row['total_duration'] ?? 0),
                'error_count' => (int)($row['error_count'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Error getting aggregate packing stats: ' . $e->getMessage());
            return ['total_tasks' => 0, 'completed_tasks' => 0, 'total_duration' => 0, 'error_count' => 0];
        }
    }

    /**
     * Get top performers for warehouse
     */
    private function getWarehouseTopPerformers($warehouseId, $startDate, $endDate, $limit = 5) {
        try {
            // Build employee list for warehouse
            $employees = $this->getWarehouseEmployees($warehouseId);
            $performerData = [];

            foreach ($employees as $emp) {
                $metrics = $this->getPickingMetricsForEmployee($emp['id'], $startDate, $endDate);
                $packingMetrics = $this->getPackingMetricsForEmployee($emp['id'], $startDate, $endDate);

                $totalTasks = $metrics['total_tasks'] + $packingMetrics['total_tasks'];

                if ($totalTasks > 0) {
                    $completionRate = ($metrics['completed_tasks'] + $packingMetrics['completed_tasks']) / $totalTasks * 100;
                    $errorRate = (($metrics['error_count'] + $packingMetrics['error_count']) / $totalTasks) * 100;
                    $productivityScore = ($completionRate * 0.5) + ((100 - $errorRate) * 0.5);

                    $employeeName = $this->getEmployeeName($emp['id']);

                    $performerData[] = [
                        'employee_id' => $emp['id'],
                        'employee_name' => $employeeName,
                        'total_tasks' => $totalTasks,
                        'completion_rate_percent' => round($completionRate, 2),
                        'productivity_score' => round($productivityScore, 1)
                    ];
                }
            }

            // Sort by productivity score
            usort($performerData, function($a, $b) {
                return $b['productivity_score'] <=> $a['productivity_score'];
            });

            return array_slice($performerData, 0, $limit);

        } catch (\Exception $e) {
            error_log('Error getting warehouse top performers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department count for warehouse
     */
    private function getWarehouseDepartmentCount($warehouseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as dept_count FROM departments
                WHERE warehouse_id = ? AND is_active = 1
            ");

            $stmt->execute([$warehouseId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($row['dept_count'] ?? 0);

        } catch (\Exception $e) {
            error_log('Error getting department count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total system stats
     */
    private function getTotalSystemStats($startDate, $endDate, $filters = []) {
        try {
            // Get picking stats
            $pickingSql = "
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(task_duration_seconds) as total_duration,
                    COUNT(DISTINCT employee_id) as unique_employees
                FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
            ";

            $params = [$startDate, $endDate];

            if (isset($filters['warehouse_id'])) {
                $pickingSql .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $params[] = $filters['warehouse_id'];
            }

            $stmt = $this->pdo->prepare($pickingSql);
            $stmt->execute($params);
            $picking = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Get packing stats
            $packingSql = "
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN package_status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN package_status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN package_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(task_duration_seconds) as total_duration
                FROM packing_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
            ";

            $packingParams = [$startDate, $endDate];

            if (isset($filters['warehouse_id'])) {
                $packingSql .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $packingParams[] = $filters['warehouse_id'];
            }

            $stmt = $this->pdo->prepare($packingSql);
            $stmt->execute($packingParams);
            $packing = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'total_tasks' => (int)($picking['total_tasks'] ?? 0) + (int)($packing['total_tasks'] ?? 0),
                'completed_tasks' => (int)($picking['completed_tasks'] ?? 0) + (int)($packing['completed_tasks'] ?? 0),
                'pending_tasks' => (int)($picking['pending_tasks'] ?? 0) + (int)($packing['pending_tasks'] ?? 0),
                'cancelled_tasks' => (int)($picking['cancelled_tasks'] ?? 0) + (int)($packing['cancelled_tasks'] ?? 0),
                'total_duration_seconds' => (int)($picking['total_duration'] ?? 0) + (int)($packing['total_duration'] ?? 0),
                'unique_employees' => (int)($picking['unique_employees'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Error getting total system stats: ' . $e->getMessage());
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
                'cancelled_tasks' => 0,
                'total_duration_seconds' => 0,
                'unique_employees' => 0
            ];
        }
    }

    /**
     * Get top performers overall
     */
    private function getTopPerformersOverall($startDate, $endDate, $limit = 5) {
        try {
            // Get all employees with tasks
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT employee_id FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
                UNION
                SELECT DISTINCT employee_id FROM packing_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
            $employees = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $performerData = [];

            foreach ($employees as $empId) {
                $metrics = $this->getPickingMetricsForEmployee($empId, $startDate, $endDate);
                $packingMetrics = $this->getPackingMetricsForEmployee($empId, $startDate, $endDate);

                $totalTasks = $metrics['total_tasks'] + $packingMetrics['total_tasks'];

                if ($totalTasks > 0) {
                    $completionRate = ($metrics['completed_tasks'] + $packingMetrics['completed_tasks']) / $totalTasks * 100;
                    $errorRate = (($metrics['error_count'] + $packingMetrics['error_count']) / $totalTasks) * 100;
                    $productivityScore = ($completionRate * 0.5) + ((100 - $errorRate) * 0.5);

                    $employeeName = $this->getEmployeeName($empId);

                    $performerData[] = [
                        'employee_id' => $empId,
                        'employee_name' => $employeeName,
                        'total_tasks_completed' => (int)($metrics['completed_tasks'] + $packingMetrics['completed_tasks']),
                        'completion_rate_percent' => round($completionRate, 2),
                        'error_rate_percent' => round($errorRate, 2),
                        'productivity_score' => round($productivityScore, 1)
                    ];
                }
            }

            // Sort by productivity score descending
            usort($performerData, function($a, $b) {
                return $b['productivity_score'] <=> $a['productivity_score'];
            });

            return array_slice($performerData, 0, $limit);

        } catch (\Exception $e) {
            error_log('Error getting top performers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get outstanding (pending/in_progress) tasks
     */
    private function getOutstandingTasks($filters = []) {
        try {
            $pickingSql = "
                SELECT
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count
                FROM picking_tasks
                WHERE status IN ('pending', 'in_progress')
            ";

            $packingSql = "
                SELECT
                    SUM(CASE WHEN package_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN package_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count
                FROM packing_tasks
                WHERE package_status IN ('pending', 'in_progress')
            ";

            $params = [];

            if (isset($filters['warehouse_id'])) {
                $pickingSql .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $packingSql .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $params[] = $filters['warehouse_id'];
                $params[] = $filters['warehouse_id'];
            }

            $stmt = $this->pdo->prepare($pickingSql);
            $stmt->execute(array_slice($params, 0, isset($params[0]) ? 1 : 0));
            $picking = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare($packingSql);
            $stmt->execute(array_slice($params, isset($params[0]) ? 1 : 0));
            $packing = $stmt->fetch(\PDO::FETCH_ASSOC);

            return [
                'pending_count' => (int)($picking['pending_count'] ?? 0) + (int)($packing['pending_count'] ?? 0),
                'in_progress_count' => (int)($picking['in_progress_count'] ?? 0) + (int)($packing['in_progress_count'] ?? 0),
                'total_outstanding' => (int)($picking['pending_count'] ?? 0) + (int)($picking['in_progress_count'] ?? 0) +
                                       (int)($packing['pending_count'] ?? 0) + (int)($packing['in_progress_count'] ?? 0)
            ];

        } catch (\Exception $e) {
            error_log('Error getting outstanding tasks: ' . $e->getMessage());
            return ['pending_count' => 0, 'in_progress_count' => 0, 'total_outstanding' => 0];
        }
    }

    /**
     * Get quality summary
     */
    private function getQualitySummary($startDate, $endDate, $filters = []) {
        try {
            $pickingErrorSql = "
                SELECT COUNT(*) as error_count FROM picking_errors pe
                JOIN picking_tasks pt ON pe.picking_task_id = pt.id
                WHERE DATE(pt.created_at) BETWEEN ? AND ?
            ";

            $params = [$startDate, $endDate];

            if (isset($filters['warehouse_id'])) {
                $pickingErrorSql .= " AND pt.employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $params[] = $filters['warehouse_id'];
            }

            $stmt = $this->pdo->prepare($pickingErrorSql);
            $stmt->execute($params);
            $pickingErrors = $stmt->fetch(\PDO::FETCH_ASSOC);

            $pickingReworkSql = "
                SELECT COUNT(*) as rework_count FROM picking_errors
                WHERE requires_rework = 1
                    AND picking_task_id IN (
                        SELECT id FROM picking_tasks
                        WHERE DATE(created_at) BETWEEN ? AND ?
            ";

            $reworkParams = [$startDate, $endDate];

            if (isset($filters['warehouse_id'])) {
                $pickingReworkSql .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $reworkParams[] = $filters['warehouse_id'];
            }

            $pickingReworkSql .= ")";

            $stmt = $this->pdo->prepare($pickingReworkSql);
            $stmt->execute($reworkParams);
            $pickingRework = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Packing quality issues
            $packingQualitySql = "
                SELECT COUNT(*) as quality_count FROM packing_errors pe
                JOIN packing_tasks pt ON pe.packing_task_id = pt.id
                WHERE DATE(pt.created_at) BETWEEN ? AND ?
            ";

            $qualityParams = [$startDate, $endDate];

            if (isset($filters['warehouse_id'])) {
                $packingQualitySql .= " AND pt.employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
                $qualityParams[] = $filters['warehouse_id'];
            }

            $stmt = $this->pdo->prepare($packingQualitySql);
            $stmt->execute($qualityParams);
            $packingQuality = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Get total tasks for rate calculation
            $totalStats = $this->getTotalSystemStats($startDate, $endDate, $filters);
            $totalTasks = $totalStats['total_tasks'];

            $totalErrors = (int)($pickingErrors['error_count'] ?? 0);
            $totalRework = (int)($pickingRework['rework_count'] ?? 0);
            $qualityIssues = (int)($packingQuality['quality_count'] ?? 0);

            return [
                'total_errors' => $totalErrors,
                'error_rate' => $totalTasks > 0 ? round(($totalErrors / $totalTasks) * 100, 2) : 0,
                'total_rework' => $totalRework,
                'quality_issues' => $qualityIssues,
                'quality_issue_rate' => $totalTasks > 0 ? round(($qualityIssues / $totalTasks) * 100, 2) : 0
            ];

        } catch (\Exception $e) {
            error_log('Error getting quality summary: ' . $e->getMessage());
            return [
                'total_errors' => 0,
                'error_rate' => 0,
                'total_rework' => 0,
                'quality_issues' => 0,
                'quality_issue_rate' => 0
            ];
        }
    }

    /**
     * Get productivity trend
     */
    private function getProductivityTrend($startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at)
            ");

            $stmt->execute([$startDate, $endDate]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($row) {
                $total = (int)$row['total_tasks'];
                $completed = (int)$row['completed_tasks'];

                return [
                    'date' => $row['date'],
                    'tasks_completed' => $completed,
                    'completion_rate_percent' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
                ];
            }, $rows);

        } catch (\Exception $e) {
            error_log('Error getting productivity trend: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count business days
     */
    private function countBusinessDays($startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $days = 0;

        while ($start <= $end) {
            $dayOfWeek = $start->format('N');
            if ($dayOfWeek < 6) {
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Get employee name
     */
    private function getEmployeeName($employeeId) {
        try {
            $stmt = $this->pdo->prepare("SELECT full_name FROM employees WHERE id = ? LIMIT 1");
            $stmt->execute([$employeeId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? $row['full_name'] : 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
}
?>
