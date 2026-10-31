<?php
/**
 * PackingTaskController
 * Business logic for Packing Task operations
 */

namespace WPMS\Controllers;

use WPMS\Repositories\PackingTaskRepository;
use WPMS\Repositories\EmployeeRepository;

class PackingTaskController
{
    private PackingTaskRepository $packingRepo;
    private EmployeeRepository $employeeRepo;

    public function __construct(PackingTaskRepository $packingRepo, EmployeeRepository $employeeRepo)
    {
        $this->packingRepo = $packingRepo;
        $this->employeeRepo = $employeeRepo;
    }

    /**
     * Get packing tasks for logged-in employee
     */
    public function getMyTasks(int $employeeId, array $filters = [], int $page = 1): array
    {
        return $this->packingRepo->getTasksForEmployee($employeeId, $filters, $page);
    }

    /**
     * Get task detail
     */
    public function getTaskDetail(int $taskId): ?array
    {
        $task = $this->packingRepo->findById($taskId);
        if (!$task) {
            return null;
        }

        // Add duration info
        if ($task['task_duration_seconds']) {
            $task['duration_display'] = $this->formatDuration($task['task_duration_seconds']);
        }

        return $task;
    }

    /**
     * Get task by barcode/package code or order number
     */
    public function getTaskByBarcode(string $barcode): ?array
    {
        // Try package code first
        $task = $this->packingRepo->findByPackageCode($barcode);

        // Fall back to order number
        if (!$task) {
            $task = $this->packingRepo->findByOrderNumber($barcode);
        }

        return $task;
    }

    /**
     * Mark task as started
     */
    public function startTask(int $taskId, int $employeeId): array
    {
        // Verify ownership
        $task = $this->packingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        // Check if task is read-only
        if ($this->packingRepo->isCompleted($taskId)) {
            return [
                'success' => false,
                'message' => 'Cannot modify completed task'
            ];
        }

        try {
            $this->packingRepo->markStarted($taskId, $employeeId);
            return [
                'success' => true,
                'message' => 'Task started successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark task as completed
     */
    public function completeTask(int $taskId, int $employeeId): array
    {
        // Verify ownership
        $task = $this->packingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        try {
            $this->packingRepo->markCompleted($taskId, $employeeId);

            return [
                'success' => true,
                'message' => 'Task completed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Record quality issue
     */
    public function recordQualityIssue(int $taskId, int $employeeId, string $notes = ''): array
    {
        // Verify ownership
        $task = $this->packingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        try {
            $this->packingRepo->recordQualityIssue($taskId, true, $employeeId);

            if ($notes) {
                $this->packingRepo->updateNotes($taskId, $notes, $employeeId);
            }

            return [
                'success' => true,
                'message' => 'Quality issue recorded successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Add or update notes
     */
    public function updateNotes(int $taskId, int $employeeId, string $notes): array
    {
        // Verify ownership
        $task = $this->packingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        // Check if task is completed
        if ($this->packingRepo->isCompleted($taskId)) {
            return [
                'success' => false,
                'message' => 'Cannot modify completed task'
            ];
        }

        try {
            $this->packingRepo->updateNotes($taskId, $notes, $employeeId);
            return [
                'success' => true,
                'message' => 'Notes updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get pending tasks (for supervisors)
     */
    public function getPendingTasks(int $limit = 50): array
    {
        return $this->packingRepo->getPendingTasks($limit);
    }

    /**
     * Get tasks with quality issues (for supervisors)
     */
    public function getTasksWithQualityIssues(int $limit = 50): array
    {
        return $this->packingRepo->getTasksWithQualityIssues($limit);
    }

    /**
     * Get all tasks with filters (for supervisors/admin)
     */
    public function getAllTasks(array $filters = [], int $page = 1): array
    {
        return $this->packingRepo->getAllTasks($filters, $page);
    }

    /**
     * Get status summary
     */
    public function getStatusSummary(array $filters = []): array
    {
        return $this->packingRepo->getStatusSummary($filters);
    }

    /**
     * Format duration for display
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($secs > 0) $parts[] = "{$secs}s";

        return implode(' ', $parts) ?: '0s';
    }
}
