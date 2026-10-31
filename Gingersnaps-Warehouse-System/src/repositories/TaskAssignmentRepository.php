<?php
/**
 * TaskAssignmentRepository
 * Data Access Layer for Task Assignment Management
 *
 * Handles task assignment operations:
 * - Assign picking tasks to employees
 * - Assign packing tasks to employees
 * - Bulk task assignment
 * - Reassign tasks
 * - View unassigned tasks
 * - View tasks by employee
 */

namespace WPMS\Repositories;

use PDO;

class TaskAssignmentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Assign picking task to employee
     */
    public function assignPickingTask(int $taskId, int $employeeId, int $assignedById): bool
    {
        // Verify task exists and is in valid state
        $stmt = $this->db->prepare("SELECT status FROM picking_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new \Exception("Picking task not found");
        }

        if (!in_array($task['status'], ['created', 'pending'])) {
            throw new \Exception("Cannot assign task in status: {$task['status']}");
        }

        // Verify employee exists and is active
        $stmt = $this->db->prepare("
            SELECT id FROM employees
            WHERE id = ? AND is_active = 1 AND employment_status = 'active'
        ");
        $stmt->execute([$employeeId]);
        if (!$stmt->fetch()) {
            throw new \Exception("Employee not found or is not active");
        }

        // Assign task
        $stmt = $this->db->prepare("
            UPDATE picking_tasks
            SET employee_id = ?, status = 'pending', updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$employeeId, $assignedById, $taskId]);
    }

    /**
     * Assign packing task to employee
     */
    public function assignPackingTask(int $taskId, int $employeeId, int $assignedById): bool
    {
        // Verify task exists and is in valid state
        $stmt = $this->db->prepare("SELECT package_status FROM packing_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new \Exception("Packing task not found");
        }

        if (!in_array($task['package_status'], ['created', 'pending'])) {
            throw new \Exception("Cannot assign task in status: {$task['package_status']}");
        }

        // Verify employee exists and is active
        $stmt = $this->db->prepare("
            SELECT id FROM employees
            WHERE id = ? AND is_active = 1 AND employment_status = 'active'
        ");
        $stmt->execute([$employeeId]);
        if (!$stmt->fetch()) {
            throw new \Exception("Employee not found or is not active");
        }

        // Assign task
        $stmt = $this->db->prepare("
            UPDATE packing_tasks
            SET employee_id = ?, package_status = 'pending', updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$employeeId, $assignedById, $taskId]);
    }

    /**
     * Reassign picking task to different employee
     */
    public function reassignPickingTask(int $taskId, int $newEmployeeId, int $reassignedById): bool
    {
        // Only allow reassignment if task not started
        $stmt = $this->db->prepare("SELECT status FROM picking_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new \Exception("Task not found");
        }

        if ($task['status'] !== 'pending' && $task['status'] !== 'created') {
            throw new \Exception("Cannot reassign task that is already in progress or completed");
        }

        return $this->assignPickingTask($taskId, $newEmployeeId, $reassignedById);
    }

    /**
     * Reassign packing task to different employee
     */
    public function reassignPackingTask(int $taskId, int $newEmployeeId, int $reassignedById): bool
    {
        // Only allow reassignment if task not started
        $stmt = $this->db->prepare("SELECT package_status FROM packing_tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new \Exception("Task not found");
        }

        if ($task['package_status'] !== 'pending' && $task['package_status'] !== 'created') {
            throw new \Exception("Cannot reassign task that is already in progress or completed");
        }

        return $this->assignPackingTask($taskId, $newEmployeeId, $reassignedById);
    }

    /**
     * Bulk assign multiple picking tasks to employee
     */
    public function bulkAssignPickingTasks(array $taskIds, int $employeeId, int $assignedById): array
    {
        $successful = [];
        $failed = [];

        foreach ($taskIds as $taskId) {
            try {
                $this->assignPickingTask($taskId, $employeeId, $assignedById);
                $successful[] = $taskId;
            } catch (\Exception $e) {
                $failed[$taskId] = $e->getMessage();
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'success_count' => count($successful),
            'fail_count' => count($failed)
        ];
    }

    /**
     * Bulk assign multiple packing tasks to employee
     */
    public function bulkAssignPackingTasks(array $taskIds, int $employeeId, int $assignedById): array
    {
        $successful = [];
        $failed = [];

        foreach ($taskIds as $taskId) {
            try {
                $this->assignPackingTask($taskId, $employeeId, $assignedById);
                $successful[] = $taskId;
            } catch (\Exception $e) {
                $failed[$taskId] = $e->getMessage();
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'success_count' => count($successful),
            'fail_count' => count($failed)
        ];
    }

    /**
     * Get unassigned picking tasks
     */
    public function getUnassignedPickingTasks(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.quantity_ordered, pt.shelf_code, pt.batch_code,
                pt.status, pt.created_at
            FROM picking_tasks pt
            WHERE pt.status IN ('created', 'pending')
            AND pt.employee_id IS NULL
            ORDER BY pt.created_at ASC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unassigned packing tasks
     */
    public function getUnassignedPackingTasks(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.package_code, pt.shipment_id, pt.order_number,
                pt.quantity_items, pt.package_status, pt.created_at
            FROM packing_tasks pt
            WHERE pt.package_status IN ('created', 'pending')
            AND pt.employee_id IS NULL
            ORDER BY pt.created_at ASC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee's workload (current task counts)
     */
    public function getEmployeeWorkload(int $employeeId): array
    {
        // Picking tasks
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count
            FROM picking_tasks
            WHERE employee_id = ? AND status IN ('pending', 'in_progress')
            GROUP BY status
        ");
        $stmt->execute([$employeeId]);
        $pickingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Packing tasks
        $stmt = $this->db->prepare("
            SELECT package_status as status, COUNT(*) as count
            FROM packing_tasks
            WHERE employee_id = ? AND package_status IN ('pending', 'in_progress')
            GROUP BY package_status
        ");
        $stmt->execute([$employeeId]);
        $packingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $picking = ['pending' => 0, 'in_progress' => 0];
        $packing = ['pending' => 0, 'in_progress' => 0];

        foreach ($pickingTasks as $row) {
            $picking[$row['status']] = $row['count'];
        }

        foreach ($packingTasks as $row) {
            $packing[$row['status']] = $row['count'];
        }

        return [
            'picking_tasks' => $picking,
            'packing_tasks' => $packing,
            'total_active_tasks' => array_sum($picking) + array_sum($packing)
        ];
    }

    /**
     * Get tasks assigned to employee
     */
    public function getAssignedTasks(int $employeeId, array $filters = []): array
    {
        // Picking tasks
        $pickingQuery = "
            SELECT
                'picking' as task_type,
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.quantity_ordered, pt.quantity_picked,
                pt.status, pt.task_start_time, pt.task_completion_time
            FROM picking_tasks pt
            WHERE pt.employee_id = ?
        ";

        $params = [$employeeId];

        if (!empty($filters['status'])) {
            $pickingQuery .= " AND pt.status = ?";
            $params[] = $filters['status'];
        }

        // Packing tasks
        $packingQuery = "
            SELECT
                'packing' as task_type,
                pt.id, pt.package_code, pt.order_number,
                pt.quantity_items,
                pt.package_status as status,
                pt.task_start_time, pt.task_completion_time
            FROM packing_tasks pt
            WHERE pt.employee_id = ?
        ";

        $params2 = [$employeeId];

        if (!empty($filters['status'])) {
            $packingQuery .= " AND pt.package_status = ?";
            $params2[] = $filters['status'];
        }

        // Combine results
        $allTasks = [];

        $stmt = $this->db->prepare($pickingQuery);
        $stmt->execute($params);
        $allTasks = array_merge($allTasks, $stmt->fetchAll(PDO::FETCH_ASSOC));

        $stmt = $this->db->prepare($packingQuery);
        $stmt->execute($params2);
        $allTasks = array_merge($allTasks, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Sort by creation time
        usort($allTasks, function ($a, $b) {
            return strcmp($b['task_start_time'] ?? $b['id'], $a['task_start_time'] ?? $a['id']);
        });

        return $allTasks;
    }

    /**
     * Get tasks by status across all employees
     */
    public function getTasksByStatus(string $taskType, string $status, int $limit = 100): array
    {
        if ($taskType === 'picking') {
            $stmt = $this->db->prepare("
                SELECT
                    pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                    pt.quantity_ordered, pt.shelf_code, pt.status,
                    e.full_name as employee_name, e.emp_id,
                    pt.created_at
                FROM picking_tasks pt
                LEFT JOIN employees e ON pt.employee_id = e.id
                WHERE pt.status = ?
                ORDER BY pt.created_at ASC
                LIMIT ?
            ");
        } else {
            $stmt = $this->db->prepare("
                SELECT
                    pt.id, pt.package_code, pt.order_number,
                    pt.quantity_items, pt.package_status as status,
                    e.full_name as employee_name, e.emp_id,
                    pt.created_at
                FROM packing_tasks pt
                LEFT JOIN employees e ON pt.employee_id = e.id
                WHERE pt.package_status = ?
                ORDER BY pt.created_at ASC
                LIMIT ?
            ");
        }

        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
