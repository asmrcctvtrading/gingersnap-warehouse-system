<?php
/**
 * PickingTaskRepository
 * Data Access Layer for Picking Task Management
 *
 * Handles all CRUD operations for picking tasks with:
 * - Strict status workflow: CREATED -> PENDING -> IN_PROGRESS -> COMPLETED (no backwards)
 * - Timestamp recording (start_time, completed_time)
 * - Duration calculation
 * - Error and exception tracking
 * - Barcode/manual entry support
 */

namespace WPMS\Repositories;

use PDO;
use DateTime;

class PickingTaskRepository
{
    private PDO $db;
    private const ITEMS_PER_PAGE = 50;
    private const MAX_ITEMS_PER_PAGE = 500;

    // Status workflow constants
    private const STATUS_CREATED = 'created';
    private const STATUS_PENDING = 'pending';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find picking task by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id,
                u.full_name as created_by_name
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            LEFT JOIN users u ON pt.created_by = u.id
            WHERE pt.id = ?
        ");

        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find picking task by picklist code
     */
    public function findByPicklistCode(string $picklistCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.picklist_code = ?
        ");

        $stmt->execute([$picklistCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find picking task by order number (supports manual entry)
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.order_number = ?
            ORDER BY pt.created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$orderNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all picking tasks for an employee
     */
    public function getTasksForEmployee(int $employeeId, array $filters = [], int $page = 1, int $limit = self::ITEMS_PER_PAGE): array
    {
        // Validate pagination
        $limit = min($limit, self::MAX_ITEMS_PER_PAGE);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.quantity_ordered, pt.quantity_picked, pt.shelf_code, pt.batch_code,
                pt.status, pt.task_start_time, pt.task_completion_time, pt.task_duration_seconds,
                pt.has_error, pt.exception_notes, pt.created_at,
                e.full_name as employee_name
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.employee_id = ?
        ";

        $params = [$employeeId];

        // Apply status filter
        if (!empty($filters['status'])) {
            $query .= " AND pt.status = ?";
            $params[] = $filters['status'];
        }

        // Apply date range filter
        if (!empty($filters['from_date'])) {
            $query .= " AND DATE(pt.created_at) >= ?";
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $query .= " AND DATE(pt.created_at) <= ?";
            $params[] = $filters['to_date'];
        }

        // Count total records
        $countQuery = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as count FROM', $query);
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $totalPages = ceil($totalCount / $limit);

        // Apply ordering and pagination
        $query .= " ORDER BY pt.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $tasks,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ];
    }

    /**
     * Get all picking tasks with filtering (for supervisor/admin)
     */
    public function getAllTasks(array $filters = [], int $page = 1, int $limit = self::ITEMS_PER_PAGE): array
    {
        $limit = min($limit, self::MAX_ITEMS_PER_PAGE);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.quantity_ordered, pt.quantity_picked, pt.status,
                pt.task_start_time, pt.task_completion_time, pt.task_duration_seconds,
                pt.has_error, pt.created_at,
                e.full_name as employee_name, e.emp_id,
                d.name as department_name,
                w.name as warehouse_name
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN warehouses w ON e.warehouse_id = w.id
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND pt.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['employee_id'])) {
            $query .= " AND pt.employee_id = ?";
            $params[] = $filters['employee_id'];
        }

        if (!empty($filters['warehouse_id'])) {
            $query .= " AND e.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }

        if (!empty($filters['from_date'])) {
            $query .= " AND DATE(pt.created_at) >= ?";
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $query .= " AND DATE(pt.created_at) <= ?";
            $params[] = $filters['to_date'];
        }

        // Count total
        $countQuery = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as count FROM', $query);
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $totalPages = ceil($totalCount / $limit);

        // Apply pagination
        $query .= " ORDER BY pt.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $tasks,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_count' => $totalCount,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Create new picking task
     */
    public function create(array $data, int $createdById): int
    {
        // Validate picklist code is unique
        if ($this->findByPicklistCode($data['picklist_code'])) {
            throw new \Exception("Picklist code '{$data['picklist_code']}' already exists");
        }

        $stmt = $this->db->prepare("
            INSERT INTO picking_tasks (
                picklist_code, employee_id, order_number, sku_code,
                product_description, quantity_ordered, shelf_code, batch_code,
                status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $data['picklist_code'],
            $data['employee_id'],
            $data['order_number'],
            $data['sku_code'],
            $data['product_description'] ?? null,
            $data['quantity_ordered'] ?? 1,
            $data['shelf_code'] ?? null,
            $data['batch_code'] ?? null,
            self::STATUS_CREATED,
            $createdById
        ]);

        if (!$success) {
            throw new \Exception("Failed to create picking task: " . implode(", ", $stmt->errorInfo()));
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update task status with strict workflow enforcement
     * Only allows: CREATED->PENDING->IN_PROGRESS->COMPLETED
     */
    public function updateStatus(int $id, string $newStatus, int $updatedById): bool
    {
        $task = $this->findById($id);
        if (!$task) {
            throw new \Exception("Task not found");
        }

        $currentStatus = $task['status'];

        // Validate status transition
        $validTransitions = [
            self::STATUS_CREATED => [self::STATUS_PENDING, self::STATUS_CANCELLED],
            self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [], // No transitions allowed from completed
            self::STATUS_CANCELLED => [] // No transitions allowed from cancelled
        ];

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \Exception("Cannot transition from '$currentStatus' to '$newStatus'");
        }

        // Prepare update data
        $updateData = ['status' => $newStatus, 'updated_by' => $updatedById];

        // Record timestamps
        if ($newStatus === self::STATUS_IN_PROGRESS && !$task['task_start_time']) {
            $updateData['task_start_time'] = date('Y-m-d H:i:s');
        }

        if ($newStatus === self::STATUS_COMPLETED && !$task['task_completion_time']) {
            $updateData['task_completion_time'] = date('Y-m-d H:i:s');

            // Calculate duration if both timestamps exist
            if ($task['task_start_time']) {
                $startTime = new DateTime($task['task_start_time']);
                $endTime = new DateTime($updateData['task_completion_time']);
                $updateData['task_duration_seconds'] = $endTime->getTimestamp() - $startTime->getTimestamp();
            }
        }

        // Build update query
        $updates = [];
        $params = [];

        foreach ($updateData as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $query = "UPDATE picking_tasks SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);

        return $stmt->execute($params);
    }

    /**
     * Mark task as started (IN_PROGRESS)
     */
    public function markStarted(int $id, int $userId): bool
    {
        return $this->updateStatus($id, self::STATUS_IN_PROGRESS, $userId);
    }

    /**
     * Mark task as completed
     */
    public function markCompleted(int $id, int $userId): bool
    {
        return $this->updateStatus($id, self::STATUS_COMPLETED, $userId);
    }

    /**
     * Update picked quantity
     */
    public function updateQuantityPicked(int $id, int $quantityPicked, int $updatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE picking_tasks
            SET quantity_picked = ?, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$quantityPicked, $updatedById, $id]);
    }

    /**
     * Add or update exception notes
     */
    public function addExceptionNotes(int $id, string $notes, int $updatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE picking_tasks
            SET exception_notes = ?, has_error = 1, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$notes, $updatedById, $id]);
    }

    /**
     * Get pending tasks (unassigned or assigned but not started)
     */
    public function getPendingTasks(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.quantity_ordered, pt.shelf_code, pt.batch_code,
                e.full_name as employee_name, e.emp_id
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.status IN ('pending', 'created')
            ORDER BY pt.created_at ASC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overdue tasks (not completed beyond expected TAT)
     */
    public function getOverdueTasks(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.picklist_code, pt.order_number, pt.sku_code,
                pt.status, pt.created_at,
                e.full_name as employee_name, e.emp_id,
                TIMESTAMPDIFF(HOUR, pt.created_at, NOW()) as hours_elapsed
            FROM picking_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.status != 'completed' AND pt.status != 'cancelled'
            AND pt.created_at < DATE_SUB(NOW(), INTERVAL 4 HOUR)
            ORDER BY pt.created_at ASC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get task duration (if completed)
     */
    public function getTaskDuration(int $id): ?int
    {
        $task = $this->findById($id);
        if (!$task || !$task['task_duration_seconds']) {
            return null;
        }
        return (int) $task['task_duration_seconds'];
    }

    /**
     * Check if task is completed and read-only
     */
    public function isCompleted(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT status FROM picking_tasks WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['status'] === self::STATUS_COMPLETED;
    }

    /**
     * Get status summary (counts by status)
     */
    public function getStatusSummary(array $filters = []): array
    {
        $query = "SELECT status, COUNT(*) as count FROM picking_tasks WHERE 1=1";
        $params = [];

        if (!empty($filters['warehouse_id'])) {
            $query .= " AND employee_id IN (SELECT id FROM employees WHERE warehouse_id = ?)";
            $params[] = $filters['warehouse_id'];
        }

        if (!empty($filters['from_date'])) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $filters['to_date'];
        }

        $query .= " GROUP BY status";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'created' => 0,
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];

        foreach ($results as $row) {
            $summary[$row['status']] = $row['count'];
        }

        return $summary;
    }
}
