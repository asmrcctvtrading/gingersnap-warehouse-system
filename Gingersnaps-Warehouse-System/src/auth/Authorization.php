<?php
/**
 * Authorization Class
 *
 * Role-Based Access Control (RBAC) implementation
 *
 * Roles:
 * - admin: Full system access
 * - supervisor: Warehouse management for assigned department
 * - employee: Task completion and personal performance view
 */

class Authorization {

    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_EMPLOYEE = 'employee';

    // Permission constants
    const PERM_MANAGE_USERS = 'manage_users';
    const PERM_MANAGE_EMPLOYEES = 'manage_employees';
    const PERM_ASSIGN_TASKS = 'assign_tasks';
    const PERM_VIEW_REPORTS = 'view_reports';
    const PERM_IMPORT_DATA = 'import_data';
    const PERM_VIEW_AUDIT = 'view_audit';
    const PERM_SYSTEM_CONFIG = 'system_config';
    const PERM_COMPLETE_TASKS = 'complete_tasks';
    const PERM_VIEW_OWN_PERFORMANCE = 'view_own_performance';

    /**
     * Role permissions mapping
     *
     * @return array
     */
    public static function getRolePermissions() {
        return [
            self::ROLE_ADMIN => [
                self::PERM_MANAGE_USERS,
                self::PERM_MANAGE_EMPLOYEES,
                self::PERM_ASSIGN_TASKS,
                self::PERM_VIEW_REPORTS,
                self::PERM_IMPORT_DATA,
                self::PERM_VIEW_AUDIT,
                self::PERM_SYSTEM_CONFIG,
                self::PERM_COMPLETE_TASKS,
                self::PERM_VIEW_OWN_PERFORMANCE,
            ],
            self::ROLE_SUPERVISOR => [
                self::PERM_ASSIGN_TASKS,
                self::PERM_VIEW_REPORTS,
                self::PERM_MANAGE_EMPLOYEES,
                self::PERM_VIEW_OWN_PERFORMANCE,
            ],
            self::ROLE_EMPLOYEE => [
                self::PERM_COMPLETE_TASKS,
                self::PERM_VIEW_OWN_PERFORMANCE,
            ],
        ];
    }

    /**
     * Check if user has a specific permission
     *
     * @param string $permission
     * @param string $userRole (optional, defaults to current session user role)
     * @return bool
     */
    public static function hasPermission($permission, $userRole = null) {
        if ($userRole === null) {
            $userRole = SessionManager::getUserRole();
        }

        if (!$userRole) {
            return false;
        }

        $rolePermissions = self::getRolePermissions();

        if (!isset($rolePermissions[$userRole])) {
            return false;
        }

        return in_array($permission, $rolePermissions[$userRole]);
    }

    /**
     * Check if user has any of the given permissions
     *
     * @param array $permissions
     * @param string $userRole (optional)
     * @return bool
     */
    public static function hasAnyPermission(array $permissions, $userRole = null) {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission, $userRole)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     *
     * @param array $permissions
     * @param string $userRole (optional)
     * @return bool
     */
    public static function hasAllPermissions(array $permissions, $userRole = null) {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission, $userRole)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific role
     *
     * @param string|array $role Role or array of roles
     * @return bool
     */
    public static function hasRole($role) {
        $userRole = SessionManager::getUserRole();

        if (is_array($role)) {
            return in_array($userRole, $role);
        }

        return $userRole === $role;
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public static function isAdmin() {
        return self::hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is supervisor
     *
     * @return bool
     */
    public static function isSupervisor() {
        return self::hasRole(self::ROLE_SUPERVISOR);
    }

    /**
     * Check if user is employee
     *
     * @return bool
     */
    public static function isEmployee() {
        return self::hasRole(self::ROLE_EMPLOYEE);
    }

    /**
     * Module-level access control
     *
     * @param string $module Module name: 'employees', 'picking', 'packing', 'import', 'reports', 'audit', 'settings'
     * @return bool
     */
    public static function canAccessModule($module) {
        $userRole = SessionManager::getUserRole();

        $moduleAccess = [
            'employees' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
            'picking' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_EMPLOYEE],
            'packing' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR, self::ROLE_EMPLOYEE],
            'import' => [self::ROLE_ADMIN],
            'reports' => [self::ROLE_ADMIN, self::ROLE_SUPERVISOR],
            'audit' => [self::ROLE_ADMIN],
            'settings' => [self::ROLE_ADMIN],
        ];

        if (!isset($moduleAccess[$module])) {
            return false;
        }

        return in_array($userRole, $moduleAccess[$module]);
    }

    /**
     * Record-level access control
     *
     * @param int $userId Current user ID
     * @param string $role Current user role
     * @param int $recordOwnerId Owner of the record
     * @param int|null $departmentId (for supervisor scope checking)
     * @param int|null $supervisorDeptId Supervisor's department ID
     * @return bool
     */
    public static function canAccessRecord($userId, $role, $recordOwnerId, $departmentId = null, $supervisorDeptId = null) {
        // Admin can access all records
        if ($role === self::ROLE_ADMIN) {
            return true;
        }

        // Employee can only access their own records
        if ($role === self::ROLE_EMPLOYEE) {
            return $userId === $recordOwnerId;
        }

        // Supervisor can access records from their department
        if ($role === self::ROLE_SUPERVISOR) {
            // If department ID is provided, check if it matches supervisor's department
            if ($departmentId !== null && $supervisorDeptId !== null) {
                return $departmentId === $supervisorDeptId;
            }
            // Otherwise allow (other checks handled by calling code)
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit another user's data
     *
     * @param int $currentUserId
     * @param string $currentUserRole
     * @param int $targetUserId
     * @param string $targetUserRole
     * @return bool
     */
    public static function canEditUser($currentUserId, $currentUserRole, $targetUserId, $targetUserRole) {
        // Can't edit yourself (handled by calling code)
        // Admin can edit anyone except other admins
        if ($currentUserRole === self::ROLE_ADMIN) {
            return $targetUserRole !== self::ROLE_ADMIN || $currentUserId === $targetUserId;
        }

        // Supervisors can't edit users
        if ($currentUserRole === self::ROLE_SUPERVISOR) {
            return false;
        }

        // Employees can't edit users
        return false;
    }

    /**
     * Check if user can delete another user
     *
     * @param string $currentUserRole
     * @param string $targetUserRole
     * @return bool
     */
    public static function canDeleteUser($currentUserRole, $targetUserRole) {
        // Only admins can delete users
        if ($currentUserRole !== self::ROLE_ADMIN) {
            return false;
        }

        // Admins cannot delete other admins
        if ($targetUserRole === self::ROLE_ADMIN) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can assign tasks
     *
     * @param string $userRole
     * @return bool
     */
    public static function canAssignTasks($userRole) {
        return in_array($userRole, [self::ROLE_ADMIN, self::ROLE_SUPERVISOR]);
    }

    /**
     * Check if user can view reports
     *
     * @param string $userRole
     * @return bool
     */
    public static function canViewReports($userRole) {
        return in_array($userRole, [self::ROLE_ADMIN, self::ROLE_SUPERVISOR]);
    }

    /**
     * Get accessible departments for supervisor
     *
     * @param int $userId
     * @param PDO $pdo
     * @return array Array of department IDs
     */
    public static function getAccessibleDepartments($userId, $pdo) {
        try {
            $stmt = $pdo->prepare(
                "SELECT DISTINCT d.id
                 FROM departments d
                 JOIN employees e ON d.id = e.dept_id
                 WHERE e.supervisor_id = ? OR ? IN (
                     SELECT id FROM users WHERE role = 'admin'
                 )"
            );
            $stmt->execute([$userId, $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'id');
        } catch (PDOException $e) {
            error_log('Error in getAccessibleDepartments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get accessible warehouses for user
     *
     * @param string $userRole
     * @param int|null $userId
     * @param PDO $pdo
     * @return array Array of warehouse IDs
     */
    public static function getAccessibleWarehouses($userRole, $userId = null, $pdo = null) {
        if ($userRole === self::ROLE_ADMIN) {
            // Admin can access all warehouses
            return [];  // Empty array means all
        }

        if ($userRole === self::ROLE_SUPERVISOR && $pdo && $userId) {
            // Supervisor can access warehouses with their departments
            try {
                $stmt = $pdo->prepare(
                    "SELECT DISTINCT d.warehouse_id
                     FROM departments d
                     WHERE d.supervisor_id = ?"
                );
                $stmt->execute([$userId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return array_column($results, 'warehouse_id');
            } catch (PDOException $e) {
                error_log('Error in getAccessibleWarehouses: ' . $e->getMessage());
                return [];
            }
        }

        return [];
    }

    /**
     * Get role display name
     *
     * @param string $role
     * @return string
     */
    public static function getRoleDisplayName($role) {
        $names = [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_EMPLOYEE => 'Employee',
        ];

        return $names[$role] ?? 'Unknown';
    }

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function getAllRoles() {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SUPERVISOR,
            self::ROLE_EMPLOYEE,
        ];
    }

    /**
     * Validate role
     *
     * @param string $role
     * @return bool
     */
    public static function isValidRole($role) {
        return in_array($role, self::getAllRoles());
    }
}
