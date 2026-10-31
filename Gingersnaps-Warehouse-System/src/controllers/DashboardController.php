<?php
/**
 * DashboardController
 * Business logic for Dashboard and reporting (Supervisor/Admin)
 */

namespace WPMS\Controllers;

use WPMS\Repositories\PickingTaskRepository;
use WPMS\Repositories\PackingTaskRepository;
use WPMS\Repositories\EmployeeRepository;

class DashboardController
{
    private PickingTaskRepository $pickingRepo;
    private PackingTaskRepository $packingRepo;
    private EmployeeRepository $employeeRepo;

    public function __construct(
        PickingTaskRepository $pickingRepo,
        PackingTaskRepository $packingRepo,
        EmployeeRepository $employeeRepo
    ) {
        $this->pickingRepo = $pickingRepo;
        $this->packingRepo = $packingRepo;
        $this->employeeRepo = $employeeRepo;
    }

    /**
     * Get dashboard overview
     */
    public function getOverview(array $filters = []): array
    {
        $pickingSummary = $this->pickingRepo->getStatusSummary($filters);
        $packingSummary = $this->packingRepo->getStatusSummary($filters);

        $totalPicking = array_sum($pickingSummary);
        $totalPacking = array_sum($packingSummary);

        return [
            'picking' => [
                'summary' => $pickingSummary,
                'total' => $totalPicking,
                'completed' => $pickingSummary['completed'],
                'completion_rate' => $totalPicking > 0 ? round(($pickingSummary['completed'] / $totalPicking) * 100, 2) : 0
            ],
            'packing' => [
                'summary' => $packingSummary,
                'total' => $totalPacking,
                'completed' => $packingSummary['completed'],
                'completion_rate' => $totalPacking > 0 ? round(($packingSummary['completed'] / $totalPacking) * 100, 2) : 0
            ],
            'pending_tasks' => [
                'picking' => $pickingSummary['pending'] + $pickingSummary['in_progress'],
                'packing' => $packingSummary['pending'] + $packingSummary['in_progress']
            ]
        ];
    }

    /**
     * Get pending tasks (unstarted)
     */
    public function getPendingTasks(array $filters = []): array
    {
        return [
            'picking' => $this->pickingRepo->getPendingTasks(50),
            'packing' => $this->packingRepo->getPendingTasks(50)
        ];
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks(): array
    {
        return [
            'picking' => $this->pickingRepo->getOverdueTasks(),
            'packing' => [] // Can add packing overdue logic if needed
        ];
    }

    /**
     * Get all picking tasks with pagination
     */
    public function getPickingTasks(array $filters = [], int $page = 1): array
    {
        return $this->pickingRepo->getAllTasks($filters, $page);
    }

    /**
     * Get all packing tasks with pagination
     */
    public function getPackingTasks(array $filters = [], int $page = 1): array
    {
        return $this->packingRepo->getAllTasks($filters, $page);
    }

    /**
     * Get employee performance summary
     */
    public function getEmployeePerformance(int $employeeId): array
    {
        $employee = $this->employeeRepo->getDetail($employeeId);
        if (!$employee) {
            return ['error' => 'Employee not found'];
        }

        $pickingTasks = $this->pickingRepo->getTasksForEmployee($employeeId, []);
        $packingTasks = $this->packingRepo->getTasksForEmployee($employeeId, []);

        // Calculate metrics
        $pickingMetrics = $this->calculateMetrics($pickingTasks['data']);
        $packingMetrics = $this->calculateMetrics($packingTasks['data']);

        return [
            'employee' => $employee,
            'picking' => [
                'total_tasks' => count($pickingTasks['data']),
                'completed' => count(array_filter($pickingTasks['data'], fn($t) => $t['status'] === 'completed')),
                'in_progress' => count(array_filter($pickingTasks['data'], fn($t) => $t['status'] === 'in_progress')),
                'pending' => count(array_filter($pickingTasks['data'], fn($t) => $t['status'] === 'pending')),
                'avg_duration' => $pickingMetrics['avg_duration'],
                'total_errors' => $pickingMetrics['total_errors']
            ],
            'packing' => [
                'total_tasks' => count($packingTasks['data']),
                'completed' => count(array_filter($packingTasks['data'], fn($t) => $t['package_status'] === 'completed')),
                'in_progress' => count(array_filter($packingTasks['data'], fn($t) => $t['package_status'] === 'in_progress')),
                'pending' => count(array_filter($packingTasks['data'], fn($t) => $t['package_status'] === 'pending')),
                'avg_duration' => $packingMetrics['avg_duration'],
                'quality_issues' => $packingMetrics['quality_issues']
            ]
        ];
    }

    /**
     * Get tasks with errors/quality issues
     */
    public function getProblematicTasks(): array
    {
        return [
            'packing_quality_issues' => $this->packingRepo->getTasksWithQualityIssues(50)
        ];
    }

    /**
     * Helper: Calculate metrics from task list
     */
    private function calculateMetrics(array $tasks): array
    {
        $completedTasks = array_filter($tasks, function ($task) {
            return isset($task['status']) ? $task['status'] === 'completed' : $task['package_status'] === 'completed';
        });

        $durations = array_filter(
            array_map(fn($t) => $t['task_duration_seconds'] ?? null, $completedTasks),
            fn($d) => $d !== null
        );

        $avgDuration = !empty($durations) ? round(array_sum($durations) / count($durations)) : 0;

        $errorCount = count(array_filter($tasks, fn($t) => $t['has_error'] ?? false));
        $qualityIssueCount = count(array_filter($tasks, fn($t) => $t['has_quality_issue'] ?? false));

        return [
            'avg_duration' => $avgDuration,
            'avg_duration_display' => $this->formatDuration($avgDuration),
            'total_errors' => $errorCount,
            'quality_issues' => $qualityIssueCount
        ];
    }

    /**
     * Format duration
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds === 0) return '0s';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($secs > 0) $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }
}
