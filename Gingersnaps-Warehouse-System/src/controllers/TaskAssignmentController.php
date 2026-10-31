<?php
/**
 * TaskAssignmentController
 * Business logic for Task Assignment (Supervisor/Admin only)
 */

namespace WPMS\Controllers;

use WPMS\Repositories\TaskAssignmentRepository;
use WPMS\Repositories\EmployeeRepository;

class TaskAssignmentController
{
    private TaskAssignmentRepository $assignmentRepo;
    private EmployeeRepository $employeeRepo;

    public function __construct(TaskAssignmentRepository $assignmentRepo, EmployeeRepository $employeeRepo)
    {
        $this->assignmentRepo = $assignmentRepo;
        $this->employeeRepo = $employeeRepo;
    }

    /**
     * Assign picking task to employee
     */
    public function assignPickingTask(int $taskId, int $employeeId, int $assignedById): array
    {
        // Verify employee
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        try {
            $this->assignmentRepo->assignPickingTask($taskId, $employeeId, $assignedById);
            return [
                'success' => true,
                'message' => 'Task assigned successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Assign packing task to employee
     */
    public function assignPackingTask(int $taskId, int $employeeId, int $assignedById): array
    {
        // Verify employee
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        try {
            $this->assignmentRepo->assignPackingTask($taskId, $employeeId, $assignedById);
            return [
                'success' => true,
                'message' => 'Task assigned successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reassign picking task
     */
    public function reassignPickingTask(int $taskId, int $newEmployeeId, int $reassignedById): array
    {
        if (!$this->employeeRepo->exists($newEmployeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        try {
            $this->assignmentRepo->reassignPickingTask($taskId, $newEmployeeId, $reassignedById);
            return [
                'success' => true,
                'message' => 'Task reassigned successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reassign packing task
     */
    public function reassignPackingTask(int $taskId, int $newEmployeeId, int $reassignedById): array
    {
        if (!$this->employeeRepo->exists($newEmployeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        try {
            $this->assignmentRepo->reassignPackingTask($taskId, $newEmployeeId, $reassignedById);
            return [
                'success' => true,
                'message' => 'Task reassigned successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk assign picking tasks
     */
    public function bulkAssignPickingTasks(array $taskIds, int $employeeId, int $assignedById): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $result = $this->assignmentRepo->bulkAssignPickingTasks($taskIds, $employeeId, $assignedById);
        return [
            'success' => true,
            'message' => "Assigned {$result['success_count']} tasks, {$result['fail_count']} failed",
            'details' => $result
        ];
    }

    /**
     * Bulk assign packing tasks
     */
    public function bulkAssignPackingTasks(array $taskIds, int $employeeId, int $assignedById): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $result = $this->assignmentRepo->bulkAssignPackingTasks($taskIds, $employeeId, $assignedById);
        return [
            'success' => true,
            'message' => "Assigned {$result['success_count']} tasks, {$result['fail_count']} failed",
            'details' => $result
        ];
    }

    /**
     * Get unassigned picking tasks
     */
    public function getUnassignedPickingTasks(int $limit = 100): array
    {
        return $this->assignmentRepo->getUnassignedPickingTasks($limit);
    }

    /**
     * Get unassigned packing tasks
     */
    public function getUnassignedPackingTasks(int $limit = 100): array
    {
        return $this->assignmentRepo->getUnassignedPackingTasks($limit);
    }

    /**
     * Get employee workload
     */
    public function getEmployeeWorkload(int $employeeId): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $workload = $this->assignmentRepo->getEmployeeWorkload($employeeId);
        return [
            'success' => true,
            'data' => $workload
        ];
    }

    /**
     * Get all tasks assigned to employee
     */
    public function getAssignedTasks(int $employeeId, array $filters = []): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $tasks = $this->assignmentRepo->getAssignedTasks($employeeId, $filters);
        return [
            'success' => true,
            'data' => $tasks
        ];
    }

    /**
     * Get tasks by status
     */
    public function getTasksByStatus(string $taskType, string $status, int $limit = 100): array
    {
        if (!in_array($taskType, ['picking', 'packing'])) {
            return [
                'success' => false,
                'message' => 'Invalid task type'
            ];
        }

        $tasks = $this->assignmentRepo->getTasksByStatus($taskType, $status, $limit);
        return [
            'success' => true,
            'data' => $tasks
        ];
    }
}
