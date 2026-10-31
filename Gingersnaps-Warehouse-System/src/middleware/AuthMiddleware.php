<?php
/**
 * AuthMiddleware Class
 *
 * Middleware for protecting routes and checking permissions
 */

class AuthMiddleware {

    /**
     * Require user to be logged in
     *
     * @return void (Dies if not authenticated)
     */
    public static function requireLogin() {
        SessionManager::initializeSession();
        SessionManager::renewSessionActivity();

        if (!SessionManager::isLoggedIn()) {
            self::handleUnauthorized('You must be logged in');
        }
    }

    /**
     * Require specific role
     *
     * @param string|array $roles
     * @return void
     */
    public static function requireRole($roles) {
        self::requireLogin();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!Authorization::hasRole($roles)) {
            self::handleForbidden('You do not have permission to access this resource');
        }
    }

    /**
     * Require specific permission
     *
     * @param string|array $permissions
     * @return void
     */
    public static function requirePermission($permissions) {
        self::requireLogin();

        if (is_array($permissions)) {
            if (!Authorization::hasAllPermissions($permissions)) {
                self::handleForbidden('You do not have the required permissions');
            }
        } else {
            if (!Authorization::hasPermission($permissions)) {
                self::handleForbidden('You do not have permission to perform this action');
            }
        }
    }

    /**
     * Require access to module
     *
     * @param string $module
     * @return void
     */
    public static function requireModule($module) {
        self::requireLogin();

        if (!Authorization::canAccessModule($module)) {
            self::handleForbidden("You do not have access to the $module module");
        }
    }

    /**
     * Handle unauthorized access (not logged in)
     *
     * @param string $message
     * @return void
     */
    private static function handleUnauthorized($message) {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'code' => 401,
            'message' => $message,
        ]);

        exit;
    }

    /**
     * Handle forbidden access (logged in but no permission)
     *
     * @param string $message
     * @return void
     */
    private static function handleForbidden($message) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'code' => 403,
            'message' => $message,
        ]);

        exit;
    }
}
