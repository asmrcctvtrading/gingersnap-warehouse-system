<?php
/**
 * EmployeeRepository
 * Data Access Layer for Employee Management
 *
 * Handles all CRUD operations for employee records with:
 * - Unique emp_id validation (prevent duplicates)
 * - Active/inactive status management
 * - Department and warehouse filtering
 * - Search by ID, name, or department
 * - Pagination support
 */

namespace WPMS\Repositories;

use PDO;
use PDOStatement;

class EmployeeRepository
{
    private PDO $db;
    private const ITEMS_PER_PAGE = 50;
    private const MAX_ITEMS_PER_PAGE = 500;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find employee by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.*,
                w.name as warehouse_name,
                d.name as department_name,
                u.full_name as user_name
            FROM employees e
            LEFT JOIN warehouses w ON e.warehouse_id = w.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE e.id = ? AND e.is_active = 1
        ");

        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find employee by emp_id (external ID)
     */
    public function findByEmpId(string $empId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.*,
                w.name as warehouse_name,
                d.name as department_name,
                u.full_name as user_name
            FROM employees e
            LEFT JOIN warehouses w ON e.warehouse_id = w.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users u ON e.user_id = u.id
            WHERE e.emp_id = ? AND e.is_active = 1
        ");

        $stmt->execute([$empId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get employees with filtering, searching, and pagination
     */
    public function getList(array $filters = [], int $page = 1, int $limit = self::ITEMS_PER_PAGE): array
    {
        // Validate pagination
        $limit = min($limit, self::MAX_ITEMS_PER_PAGE);
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        // Build base query
        $query = "
            SELECT
                e.id, e.emp_id, e.full_name, e.email, e.phone,
                e.warehouse_id, e.department_id, e.employment_status,
                e.hire_date, e.created_at,
                w.name as warehouse_name,
                d.name as department_name
            FROM employees e
            LEFT JOIN warehouses w ON e.warehouse_id = w.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE e.is_active = 1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['warehouse_id'])) {
            $query .= " AND e.warehouse_id = ?";
            $params[] = $filters['warehouse_id'];
        }

        if (!empty($filters['department_id'])) {
            $query .= " AND e.department_id = ?";
            $params[] = $filters['department_id'];
        }

        if (!empty($filters['employment_status'])) {
            $query .= " AND e.employment_status = ?";
            $params[] = $filters['employment_status'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query .= " AND (e.emp_id LIKE ? OR e.full_name LIKE ? OR d.name LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Count total records
        $countQuery = preg_replace('/SELECT .*? FROM/', 'SELECT COUNT(*) as count FROM', $query);
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        $totalPages = ceil($totalCount / $limit);

        // Apply ordering and pagination
        $query .= " ORDER BY e.full_name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $employees,
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
     * Create new employee
     *
     * @throws \Exception if emp_id already exists
     */
    public function create(array $data, int $createdById): int
    {
        // Validate emp_id is unique
        if ($this->findByEmpId($data['emp_id'])) {
            throw new \Exception("Employee ID '{$data['emp_id']}' already exists");
        }

        $stmt = $this->db->prepare("
            INSERT INTO employees (
                emp_id, full_name, email, phone,
                warehouse_id, department_id, employment_status,
                hire_date, user_id, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $hireDate = !empty($data['hire_date']) ? $data['hire_date'] : null;
        $userId = !empty($data['user_id']) ? $data['user_id'] : null;

        $success = $stmt->execute([
            $data['emp_id'],
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['warehouse_id'],
            $data['department_id'],
            $data['employment_status'] ?? 'active',
            $hireDate,
            $userId,
            $createdById
        ]);

        if (!$success) {
            throw new \Exception("Failed to create employee: " . implode(", ", $stmt->errorInfo()));
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update employee details
     */
    public function update(int $id, array $data, int $updatedById): bool
    {
        // Check if trying to update emp_id to existing value
        if (!empty($data['emp_id'])) {
            $existing = $this->findByEmpId($data['emp_id']);
            if ($existing && $existing['id'] != $id) {
                throw new \Exception("Employee ID '{$data['emp_id']}' is already taken");
            }
        }

        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['emp_id', 'full_name', 'email', 'phone', 'warehouse_id', 'department_id', 'employment_status', 'hire_date', 'user_id'])) {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return true; // Nothing to update
        }

        $updates[] = "updated_by = ?";
        $params[] = $updatedById;
        $params[] = $id;

        $query = "UPDATE employees SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);

        return $stmt->execute($params);
    }

    /**
     * Deactivate (soft delete) employee
     */
    public function deactivate(int $id, int $deactivatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET is_active = 0, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$deactivatedById, $id]);
    }

    /**
     * Reactivate employee
     */
    public function activate(int $id, int $activatedById): bool
    {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET is_active = 1, updated_by = ?
            WHERE id = ?
        ");

        return $stmt->execute([$activatedById, $id]);
    }

    /**
     * Get all active employees for dropdown lists
     */
    public function getAllActive(?int $warehouseId = null, ?int $departmentId = null): array
    {
        $query = "
            SELECT id, emp_id, full_name, email, warehouse_id, department_id, employment_status
            FROM employees
            WHERE is_active = 1 AND employment_status = 'active'
        ";

        $params = [];

        if ($warehouseId !== null) {
            $query .= " AND warehouse_id = ?";
            $params[] = $warehouseId;
        }

        if ($departmentId !== null) {
            $query .= " AND department_id = ?";
            $params[] = $departmentId;
        }

        $query .= " ORDER BY full_name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if employee exists
     */
    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM employees WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get employee's task counts by status
     */
    public function getTaskStats(int $employeeId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                'picking' as task_type,
                status,
                COUNT(*) as count
            FROM picking_tasks
            WHERE employee_id = ? AND status != 'cancelled'
            GROUP BY status

            UNION ALL

            SELECT
                'packing' as task_type,
                package_status as status,
                COUNT(*) as count
            FROM packing_tasks
            WHERE employee_id = ? AND package_status != 'cancelled'
            GROUP BY package_status
        ");

        $stmt->execute([$employeeId, $employeeId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'picking' => ['pending' => 0, 'in_progress' => 0, 'completed' => 0],
            'packing' => ['pending' => 0, 'in_progress' => 0, 'completed' => 0]
        ];

        foreach ($results as $row) {
            $taskType = $row['task_type'];
            $status = $row['status'];
            if (isset($stats[$taskType][$status])) {
                $stats[$taskType][$status] = $row['count'];
            }
        }

        return $stats;
    }
}
