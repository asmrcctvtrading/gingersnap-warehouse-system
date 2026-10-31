<?php
/**
 * EmployeeController
 * Business logic for Employee Management operations
 */

namespace WPMS\Controllers;

use WPMS\Repositories\EmployeeRepository;
use WPMS\Repositories\PickingTaskRepository;
use WPMS\Repositories\PackingTaskRepository;

class EmployeeController
{
    private EmployeeRepository $employeeRepo;
    private PickingTaskRepository $pickingRepo;
    private PackingTaskRepository $packingRepo;

    public function __construct(EmployeeRepository $employeeRepo, PickingTaskRepository $pickingRepo, PackingTaskRepository $packingRepo)
    {
        $this->employeeRepo = $employeeRepo;
        $this->pickingRepo = $pickingRepo;
        $this->packingRepo = $packingRepo;
    }

    /**
     * Get paginated employee list with filtering
     */
    public function getList(array $filters = [], int $page = 1): array
    {
        return $this->employeeRepo->getList($filters, $page);
    }

    /**
     * Get single employee with stats
     */
    public function getDetail(int $employeeId): ?array
    {
        $employee = $this->employeeRepo->findById($employeeId);
        if (!$employee) {
            return null;
        }

        // Add task statistics
        $employee['task_stats'] = $this->employeeRepo->getTaskStats($employeeId);

        return $employee;
    }

    /**
     * Create new employee with validation
     */
    public function create(array $data, int $createdById): array
    {
        // Validate required fields
        $required = ['emp_id', 'full_name', 'warehouse_id', 'department_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '$field' is required");
            }
        }

        // Validate employment status
        if (!empty($data['employment_status']) && !in_array($data['employment_status'], ['active', 'inactive', 'on_leave'])) {
            throw new \Exception("Invalid employment status");
        }

        try {
            $id = $this->employeeRepo->create($data, $createdById);
            return [
                'success' => true,
                'id' => $id,
                'message' => 'Employee created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update employee details
     */
    public function update(int $employeeId, array $data, int $updatedById): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        try {
            $this->employeeRepo->update($employeeId, $data, $updatedById);
            return [
                'success' => true,
                'message' => 'Employee updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Deactivate employee
     */
    public function deactivate(int $employeeId, int $deactivatedById): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $this->employeeRepo->deactivate($employeeId, $deactivatedById);
        return [
            'success' => true,
            'message' => 'Employee deactivated successfully'
        ];
    }

    /**
     * Activate employee
     */
    public function activate(int $employeeId, int $activatedById): array
    {
        if (!$this->employeeRepo->exists($employeeId)) {
            return [
                'success' => false,
                'message' => 'Employee not found'
            ];
        }

        $this->employeeRepo->activate($employeeId, $activatedById);
        return [
            'success' => true,
            'message' => 'Employee activated successfully'
        ];
    }

    /**
     * Get active employees for assignment
     */
    public function getActiveEmployees(?int $warehouseId = null, ?int $departmentId = null): array
    {
        return $this->employeeRepo->getAllActive($warehouseId, $departmentId);
    }

    /**
     * Validate employee exists
     */
    public function exists(int $employeeId): bool
    {
        return $this->employeeRepo->exists($employeeId);
    }
}
