<?php
/**
 * PickingTaskController
 * Business logic for Picking Task operations
 */

namespace WPMS\Controllers;

use WPMS\Repositories\PickingTaskRepository;
use WPMS\Repositories\EmployeeRepository;

class PickingTaskController
{
    private PickingTaskRepository $pickingRepo;
    private EmployeeRepository $employeeRepo;

    public function __construct(PickingTaskRepository $pickingRepo, EmployeeRepository $employeeRepo)
    {
        $this->pickingRepo = $pickingRepo;
        $this->employeeRepo = $employeeRepo;
    }

    /**
     * Get picking tasks for logged-in employee
     */
    public function getMyTasks(int $employeeId, array $filters = [], int $page = 1): array
    {
        return $this->pickingRepo->getTasksForEmployee($employeeId, $filters, $page);
    }

    /**
     * Get task detail
     */
    public function getTaskDetail(int $taskId): ?array
    {
        $task = $this->pickingRepo->findById($taskId);
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
     * Get task by barcode/picklist code or order number
     */
    public function getTaskByBarcode(string $barcode): ?array
    {
        // Try picklist code first
        $task = $this->pickingRepo->findByPicklistCode($barcode);

        // Fall back to order number
        if (!$task) {
            $task = $this->pickingRepo->findByOrderNumber($barcode);
        }

        return $task;
    }

    /**
     * Mark task as started
     */
    public function startTask(int $taskId, int $employeeId): array
    {
        // Verify ownership
        $task = $this->pickingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        // Check if task is read-only
        if ($this->pickingRepo->isCompleted($taskId)) {
            return [
                'success' => false,
                'message' => 'Cannot modify completed task'
            ];
        }

        try {
            $this->pickingRepo->markStarted($taskId, $employeeId);
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
    public function completeTask(int $taskId, int $employeeId, ?int $quantityPicked = null): array
    {
        // Verify ownership
        $task = $this->pickingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        try {
            // Update quantity if provided
            if ($quantityPicked !== null) {
                $this->pickingRepo->updateQuantityPicked($taskId, $quantityPicked, $employeeId);
            }

            // Mark as completed
            $this->pickingRepo->markCompleted($taskId, $employeeId);

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
     * Add exception/error notes
     */
    public function addExceptionNotes(int $taskId, int $employeeId, string $notes): array
    {
        // Verify ownership
        $task = $this->pickingRepo->findById($taskId);
        if (!$task || $task['employee_id'] != $employeeId) {
            return [
                'success' => false,
                'message' => 'Task not found or you do not have permission'
            ];
        }

        // Check if task is already completed
        if ($this->pickingRepo->isCompleted($taskId)) {
            return [
                'success' => false,
                'message' => 'Cannot add notes to completed task'
            ];
        }

        try {
            $this->pickingRepo->addExceptionNotes($taskId, $notes, $employeeId);
            return [
                'success' => true,
                'message' => 'Notes added successfully'
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
        return $this->pickingRepo->getPendingTasks($limit);
    }

    /**
     * Get overdue tasks (for supervisors)
     */
    public function getOverdueTasks(): array
    {
        return $this->pickingRepo->getOverdueTasks();
    }

    /**
     * Get all tasks with filters (for supervisors/admin)
     */
    public function getAllTasks(array $filters = [], int $page = 1): array
    {
        return $this->pickingRepo->getAllTasks($filters, $page);
    }

    /**
     * Get status summary
     */
    public function getStatusSummary(array $filters = []): array
    {
        return $this->pickingRepo->getStatusSummary($filters);
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
