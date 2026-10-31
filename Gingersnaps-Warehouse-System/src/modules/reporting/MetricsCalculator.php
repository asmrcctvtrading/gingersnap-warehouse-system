<?php
/**
 * MetricsCalculator.php
 *
 * Calculates productivity metrics for dashboard and reporting.
 * All formulas are documented with examples.
 *
 * Metrics Provided:
 * - Average task duration (seconds)
 * - Tasks completed per hour
 * - Error rates (%)
 * - Completion rates (%)
 * - Rework percentages
 * - Employee productivity ranking
 * - Team aggregate metrics
 *
 * Usage:
 *  $calc = new MetricsCalculator($pdo);
 *  $metrics = $calc->getEmployeeMetrics($employeeId, '2026-10-01', '2026-10-31');
 */

class MetricsCalculator {
    private $pdo;

    /**
     * METRIC FORMULAS:
     *
     * 1. Average Task Duration (seconds)
     *    Formula: SUM(task_duration_seconds) / COUNT(completed_tasks)
     *    Example: 50,000 seconds / 10 tasks = 5,000 seconds/task (83 minutes)
     *    Why: Measures efficiency - lower is better
     *
     * 2. Tasks Per Hour
     *    Formula: COUNT(completed_tasks) / (total_minutes_worked / 60)
     *    Example: 10 completed tasks / (480 minutes / 60 hours) = 1.25 tasks/hour
     *    Why: Overall throughput metric
     *
     * 3. Error Rate (%)
     *    Formula: COUNT(tasks_with_errors) / COUNT(total_tasks) * 100
     *    Example: 2 tasks with errors / 10 total = 20% error rate
     *    Why: Quality metric - lower is better
     *
     * 4. Completion Rate (%)
     *    Formula: COUNT(completed_tasks) / COUNT(assigned_tasks) * 100
     *    Example: 8 completed / 10 assigned = 80% completion rate
     *    Why: Productivity metric - higher is better
     *
     * 5. Rework %
     *    Formula: COUNT(rework_required) / COUNT(completed_tasks) * 100
     *    Example: 1 rework / 10 completed = 10% rework rate
     *    Why: Quality indicator - lower is better
     *
     * 6. On-Time Completion (%)
     *    Formula: COUNT(completed_on_time) / COUNT(completed_tasks) * 100
     *    Example: 9 on-time / 10 completed = 90% on-time
     *    Why: Timeliness and reliability
     */

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get comprehensive metrics for single employee
     *
     * @param int $employeeId Employee ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @param string $taskType 'picking', 'packing', or 'all'
     * @return array Detailed metrics
     */
    public function getEmployeeMetrics($employeeId, $startDate, $endDate, $taskType = 'all') {
        try {
            $metrics = [
                'employee_id' => $employeeId,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $this->calculateBusinessDays($startDate, $endDate)
                ],
                'picking' => $this->getPickingMetrics($employeeId, $startDate, $endDate),
                'packing' => $this->getPackingMetrics($employeeId, $startDate, $endDate),
                'overall' => $this->getOverallMetrics($employeeId, $startDate, $endDate)
            ];

            if ($taskType !== 'all') {
                unset($metrics['overall']);
                if ($taskType === 'picking') {
                    unset($metrics['packing']);
                } elseif ($taskType === 'packing') {
                    unset($metrics['picking']);
                }
            }

            return $metrics;

        } catch (\Exception $e) {
            error_log('Metrics calculation error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get picking-specific metrics
     *
     * @param int $employeeId Employee ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Picking metrics
     */
    private function getPickingMetrics($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(has_error) as error_count,
                    SUM(task_duration_seconds) as total_duration_seconds,
                    AVG(CASE WHEN status = 'completed' THEN task_duration_seconds END) as avg_task_duration,
                    SUM(quantity_ordered) as total_quantity_ordered,
                    SUM(quantity_picked) as total_quantity_picked
                FROM picking_tasks
                WHERE employee_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
                    AND is_active = 1
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'task_type' => 'picking',
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_task_duration_seconds' => 0,
                    'error_rate_percent' => 0,
                    'completion_rate_percent' => 0,
                    'tasks_per_hour' => 0
                ];
            }

            // Calculate metrics
            $totalTasks = (int)$row['total_tasks'];
            $completedTasks = (int)$row['completed_tasks'];
            $errorCount = (int)$row['error_count'];
            $totalDuration = (int)$row['total_duration_seconds'];
            $totalQuantity = (int)$row['total_quantity_ordered'];

            $avgDuration = $completedTasks > 0 ? (int)$row['avg_task_duration'] : 0;
            $errorRate = $totalTasks > 0 ? round(($errorCount / $totalTasks) * 100, 2) : 0;
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
            $tasksPerHour = $totalDuration > 0 ? round($completedTasks / ($totalDuration / 3600), 2) : 0;
            $picksPerTask = $completedTasks > 0 ? round($totalQuantity / $completedTasks, 2) : 0;

            return [
                'task_type' => 'picking',
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => (int)$row['pending_tasks'],
                'cancelled_tasks' => (int)$row['cancelled_tasks'],
                'avg_task_duration_seconds' => $avgDuration,
                'avg_task_duration_minutes' => round($avgDuration / 60, 1),
                'total_picks' => $totalQuantity,
                'error_count' => $errorCount,
                'error_rate_percent' => $errorRate,
                'completion_rate_percent' => $completionRate,
                'tasks_per_hour' => $tasksPerHour,
                'picks_per_task' => $picksPerTask
            ];

        } catch (\Exception $e) {
            error_log('Picking metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get packing-specific metrics
     *
     * @param int $employeeId Employee ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Packing metrics
     */
    private function getPackingMetrics($employeeId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN package_status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN package_status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN package_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
                    SUM(has_quality_issue) as quality_issue_count,
                    SUM(has_error) as error_count,
                    SUM(task_duration_seconds) as total_duration_seconds,
                    AVG(CASE WHEN package_status = 'completed' THEN task_duration_seconds END) as avg_task_duration,
                    SUM(quantity_items) as total_items
                FROM packing_tasks
                WHERE employee_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
                    AND is_active = 1
            ");

            $stmt->execute([$employeeId, $startDate, $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'task_type' => 'packing',
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_task_duration_seconds' => 0,
                    'quality_error_rate_percent' => 0,
                    'completion_rate_percent' => 0,
                    'tasks_per_hour' => 0
                ];
            }

            // Calculate metrics
            $totalTasks = (int)$row['total_tasks'];
            $completedTasks = (int)$row['completed_tasks'];
            $qualityIssues = (int)$row['quality_issue_count'];
            $totalDuration = (int)$row['total_duration_seconds'];
            $totalItems = (int)$row['total_items'];

            $avgDuration = $completedTasks > 0 ? (int)$row['avg_task_duration'] : 0;
            $qualityErrorRate = $totalTasks > 0 ? round(($qualityIssues / $totalTasks) * 100, 2) : 0;
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
            $tasksPerHour = $totalDuration > 0 ? round($completedTasks / ($totalDuration / 3600), 2) : 0;
            $itemsPerTask = $completedTasks > 0 ? round($totalItems / $completedTasks, 2) : 0;

            return [
                'task_type' => 'packing',
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => (int)$row['pending_tasks'],
                'cancelled_tasks' => (int)$row['cancelled_tasks'],
                'avg_task_duration_seconds' => $avgDuration,
                'avg_task_duration_minutes' => round($avgDuration / 60, 1),
                'total_items_packed' => $totalItems,
                'quality_issue_count' => $qualityIssues,
                'quality_error_rate_percent' => $qualityErrorRate,
                'completion_rate_percent' => $completionRate,
                'tasks_per_hour' => $tasksPerHour,
                'items_per_task' => $itemsPerTask
            ];

        } catch (\Exception $e) {
            error_log('Packing metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overall metrics (combined picking + packing)
     *
     * @param int $employeeId Employee ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Overall metrics
     */
    private function getOverallMetrics($employeeId, $startDate, $endDate) {
        try {
            // Get picking metrics
            $picking = $this->getPickingMetrics($employeeId, $startDate, $endDate);

            // Get packing metrics
            $packing = $this->getPackingMetrics($employeeId, $startDate, $endDate);

            $totalTasks = ($picking['total_tasks'] ?? 0) + ($packing['total_tasks'] ?? 0);
            $completedTasks = ($picking['completed_tasks'] ?? 0) + ($packing['completed_tasks'] ?? 0);
            $totalDuration = (($picking['avg_task_duration_seconds'] ?? 0) * ($picking['completed_tasks'] ?? 1)) +
                             (($packing['avg_task_duration_seconds'] ?? 0) * ($packing['completed_tasks'] ?? 1));

            $errorCount = ($picking['error_count'] ?? 0) + ($packing['quality_issue_count'] ?? 0);
            $errorRate = $totalTasks > 0 ? round(($errorCount / $totalTasks) * 100, 2) : 0;
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
            $tasksPerHour = $totalDuration > 0 ? round($completedTasks / ($totalDuration / 3600), 2) : 0;

            return [
                'task_type' => 'overall',
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'picking_tasks' => $picking['total_tasks'] ?? 0,
                'packing_tasks' => $packing['total_tasks'] ?? 0,
                'error_count' => $errorCount,
                'error_rate_percent' => $errorRate,
                'completion_rate_percent' => $completionRate,
                'tasks_per_hour' => $tasksPerHour,
                'productivity_score' => $this->calculateProductivityScore($completionRate, $errorRate)
            ];

        } catch (\Exception $e) {
            error_log('Overall metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get daily metrics for dashboard
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Daily metrics by date
     */
    public function getDailyMetrics($startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    DATE(created_at) as date,
                    COUNT(DISTINCT employee_id) as total_employees,
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN has_error = 1 THEN 1 ELSE 0 END) as error_count,
                    AVG(CASE WHEN status = 'completed' THEN task_duration_seconds END) as avg_duration
                FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) ASC
            ");

            $stmt->execute([$startDate, $endDate]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(function($row) {
                $total = (int)$row['total_tasks'];
                $completed = (int)$row['completed_tasks'];

                return [
                    'date' => $row['date'],
                    'total_tasks' => $total,
                    'completed_tasks' => $completed,
                    'error_count' => (int)$row['error_count'],
                    'error_rate_percent' => $total > 0 ? round(((int)$row['error_count'] / $total) * 100, 2) : 0,
                    'completion_rate_percent' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                    'avg_duration_seconds' => (int)$row['avg_duration'] ?? 0,
                    'total_employees' => (int)$row['total_employees']
                ];
            }, $rows);

        } catch (\Exception $e) {
            error_log('Daily metrics error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employee leaderboard
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param string $sortBy Metric to sort by ('completion_rate', 'tasks_completed', 'error_rate')
     * @param int $limit Number of employees
     * @return array Employee rankings
     */
    public function getEmployeeLeaderboard($startDate, $endDate, $sortBy = 'completion_rate', $limit = 10) {
        try {
            $employees = [];

            // Get all employees with tasks in period
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT employee_id FROM picking_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
                UNION
                SELECT DISTINCT employee_id FROM packing_tasks
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");

            $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
            $employeeIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Get metrics for each employee
            foreach ($employeeIds as $empId) {
                $metrics = $this->getEmployeeMetrics($empId, $startDate, $endDate);

                if (!isset($metrics['error'])) {
                    $overall = $metrics['overall'] ?? [];

                    $employees[] = [
                        'employee_id' => $empId,
                        'employee_name' => $this->getEmployeeName($empId),
                        'total_tasks' => $overall['total_tasks'] ?? 0,
                        'completed_tasks' => $overall['completed_tasks'] ?? 0,
                        'completion_rate_percent' => $overall['completion_rate_percent'] ?? 0,
                        'error_rate_percent' => $overall['error_rate_percent'] ?? 0,
                        'tasks_per_hour' => $overall['tasks_per_hour'] ?? 0,
                        'productivity_score' => $overall['productivity_score'] ?? 0
                    ];
                }
            }

            // Sort by metric
            usort($employees, function($a, $b) use ($sortBy) {
                $aVal = $a[$sortBy] ?? 0;
                $bVal = $b[$sortBy] ?? 0;

                // Higher is better for completion_rate and tasks_per_hour
                // Lower is better for error_rate
                if ($sortBy === 'error_rate_percent') {
                    return $aVal <=> $bVal;
                } else {
                    return $bVal <=> $aVal;
                }
            });

            // Return top N
            return array_slice($employees, 0, $limit);

        } catch (\Exception $e) {
            error_log('Leaderboard error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate productivity score (0-100)
     * Formula: (completion_rate * 0.5) + ((100 - error_rate) * 0.5)
     *
     * Example: 90% completion + 10% error rate
     * Score = (90 * 0.5) + ((100 - 10) * 0.5) = 45 + 45 = 90
     *
     * @param float $completionRate Completion rate (0-100)
     * @param float $errorRate Error rate (0-100)
     * @return float Productivity score (0-100)
     */
    private function calculateProductivityScore($completionRate, $errorRate) {
        $score = ($completionRate * 0.5) + ((100 - $errorRate) * 0.5);
        return min(100, max(0, round($score, 1)));
    }

    /**
     * Calculate business days between dates
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return int Number of business days (Mon-Fri)
     */
    private function calculateBusinessDays($startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $days = 0;

        while ($start <= $end) {
            $dayOfWeek = $start->format('N');
            if ($dayOfWeek < 6) { // Mon-Fri = 1-5
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Get employee name
     *
     * @param int $employeeId Employee ID
     * @return string Employee name
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
