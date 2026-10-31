<?php
/**
 * PackingTaskRepository
 * Data Access Layer for Packing Task Management
 *
 * Handles all CRUD operations for packing tasks with:
 * - Strict status workflow: CREATED -> PENDING -> IN_PROGRESS -> COMPLETED
 * - Timestamp recording and duration calculation
 * - Quality issue and damage tracking
 * - Rework flag management
 */

namespace WPMS\Repositories;

use PDO;
use DateTime;

class PackingTaskRepository
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
     * Find packing task by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id,
                u.full_name as created_by_name
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            LEFT JOIN users u ON pt.created_by = u.id
            WHERE pt.id = ?
        ");

        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find packing task by package code
     */
    public function findByPackageCode(string $packageCode): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.package_code = ?
        ");

        $stmt->execute([$packageCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find packing task by order number
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.*,
                e.full_name as employee_name,
                e.emp_id
            FROM packing_tasks pt
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
     * Get all packing tasks for an employee
     */
    public function getTasksForEmployee(int $employeeId, array $filters = [], int $page = 1, int $limit = self::ITEMS_PER_PAGE): array
    {
        // Validate pagination
        $limit = min($limit, self::MAX_ITEMS_PER_PAGE);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT
                pt.id, pt.package_code, pt.shipment_id, pt.order_number,
                pt.quantity_items, pt.package_status,
                pt.task_start_time, pt.task_completion_time, pt.task_duration_seconds,
                pt.has_error, pt.has_quality_issue, pt.notes, pt.created_at,
                e.full_name as employee_name
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.employee_id = ?
        ";

        $params = [$employeeId];

        // Apply status filter
        if (!empty($filters['status'])) {
            $query .= " AND pt.package_status = ?";
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
     * Get all packing tasks with filtering (for supervisor/admin)
     */
    public function getAllTasks(array $filters = [], int $page = 1, int $limit = self::ITEMS_PER_PAGE): array
    {
        $limit = min($limit, self::MAX_ITEMS_PER_PAGE);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT
                pt.id, pt.package_code, pt.shipment_id, pt.order_number,
                pt.quantity_items, pt.package_status,
                pt.task_start_time, pt.task_completion_time, pt.task_duration_seconds,
                pt.has_error, pt.has_quality_issue, pt.created_at,
                e.full_name as employee_name, e.emp_id,
                d.name as department_name,
                w.name as warehouse_name
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN warehouses w ON e.warehouse_id = w.id
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $query .= " AND pt.package_status = ?";
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
     * Create new packing task
     */
    public function create(array $data, int $createdById): int
    {
        // Validate package code is unique
        if ($this->findByPackageCode($data['package_code'])) {
            throw new \Exception("Package code '{$data['package_code']}' already exists");
        }

        $stmt = $this->db->prepare("
            INSERT INTO packing_tasks (
                package_code, employee_id, shipment_id, order_number,
                quantity_items, package_status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $data['package_code'],
            $data['employee_id'],
            $data['shipment_id'],
            $data['order_number'],
            $data['quantity_items'] ?? 1,
            self::STATUS_CREATED,
            $createdById
        ]);

        if (!$success) {
            throw new \Exception("Failed to create packing task: " . implode(", ", $stmt->errorInfo()));
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

        $currentStatus = $task['package_status'];

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
        $updateData = ['package_status' => $newStatus, 'updated_by' => $updatedById];

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

        $query = "UPDATE packing_tasks SET " . implode(", ", $updates) . " WHERE id = ?";
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
     * Record a quality issue
     */
    public function recordQualityIssue(int $id, bool $hasIssue, int $updatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE packing_tasks
            SET has_quality_issue = ?, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$hasIssue ? 1 : 0, $updatedById, $id]);
    }

    /**
     * Add or update notes
     */
    public function updateNotes(int $id, string $notes, int $updatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE packing_tasks
            SET notes = ?, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$notes, $updatedById, $id]);
    }

    /**
     * Record error occurrence
     */
    public function recordError(int $id, int $updatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE packing_tasks
            SET has_error = 1, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$updatedById, $id]);
    }

    /**
     * Get pending tasks
     */
    public function getPendingTasks(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.package_code, pt.shipment_id, pt.order_number,
                pt.quantity_items, pt.package_status,
                e.full_name as employee_name, e.emp_id
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            WHERE pt.package_status IN ('pending', 'created')
            ORDER BY pt.created_at ASC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get tasks with quality issues
     */
    public function getTasksWithQualityIssues(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT
                pt.id, pt.package_code, pt.order_number,
                pt.package_status, pt.has_quality_issue, pt.notes,
                e.full_name as employee_name, e.emp_id,
                COUNT(pe.id) as error_count
            FROM packing_tasks pt
            LEFT JOIN employees e ON pt.employee_id = e.id
            LEFT JOIN packing_errors pe ON pt.id = pe.packing_task_id
            WHERE pt.has_quality_issue = 1 OR pt.has_error = 1
            GROUP BY pt.id
            ORDER BY pt.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get task duration
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
        $stmt = $this->db->prepare("SELECT package_status FROM packing_tasks WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['package_status'] === self::STATUS_COMPLETED;
    }

    /**
     * Get status summary
     */
    public function getStatusSummary(array $filters = []): array
    {
        $query = "SELECT package_status as status, COUNT(*) as count FROM packing_tasks WHERE 1=1";
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

        $query .= " GROUP BY package_status";

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
