<?php
/**
 * AuditLogModel Class
 *
 * Handles audit logging for:
 * - Login attempts (success and failure)
 * - Logouts
 * - Data changes
 * - Role changes
 * - Password changes
 */

class AuditLogModel {

    private $pdo;

    // Action types
    const ACTION_LOGIN = 'login';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_LOGOUT = 'logout';
    const ACTION_PASSWORD_CHANGE = 'password_change';
    const ACTION_PASSWORD_RESET = 'password_reset';
    const ACTION_PASSWORD_RESET_REQUESTED = 'password_reset_requested';
    const ACTION_ROLE_CHANGE = 'role_change';
    const ACTION_USER_CREATED = 'user_created';
    const ACTION_USER_UPDATED = 'user_updated';
    const ACTION_USER_DELETED = 'user_deleted';
    const ACTION_DATA_IMPORT = 'data_import';
    const ACTION_REPORT_GENERATED = 'report_generated';

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log login attempt
     *
     * @param int $userId
     * @param bool $success
     * @param string $ipAddress
     * @param string $userAgent
     * @return bool
     */
    public function logLogin($userId, $success = true, $ipAddress = '', $userAgent = '') {
        $action = $success ? self::ACTION_LOGIN : self::ACTION_LOGIN_FAILED;

        $changeDetails = [
            'ip_address' => $ipAddress,
            'user_agent' => substr($userAgent, 0, 255),
        ];

        return $this->logAction($userId, $action, 'users', $userId, $changeDetails);
    }

    /**
     * Log logout
     *
     * @param int $userId
     * @return bool
     */
    public function logLogout($userId) {
        return $this->logAction($userId, self::ACTION_LOGOUT, 'users', $userId, []);
    }

    /**
     * Log password change
     *
     * @param int $userId
     * @param int $targetUserId
     * @return bool
     */
    public function logPasswordChange($userId, $targetUserId) {
        return $this->logAction($userId, self::ACTION_PASSWORD_CHANGE, 'users', $targetUserId, []);
    }

    /**
     * Log data change (generic)
     *
     * @param int $userId User ID performing the action
     * @param string $action Action type
     * @param string $tableName Table name affected
     * @param int $recordId Record ID affected
     * @param array $changeDetails Details of the change
     * @return bool
     */
    public function logAction($userId, $action, $tableName, $recordId, $changeDetails = []) {
        // Only log if user is logged in or for login actions
        if (!LOG_AUTHENTICATION && !in_array($action, [self::ACTION_LOGIN, self::ACTION_LOGIN_FAILED])) {
            return true;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_logs
                 (user_id, action, table_name, record_id, change_details, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $changeDetailsJson = json_encode($changeDetails);
            $ipAddress = $this->getClientIP();
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

            return $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $changeDetailsJson,
                $ipAddress,
                $userAgent,
            ]);
        } catch (PDOException $e) {
            error_log('Error logging audit action: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit logs for a user
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserLogs($userId, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM audit_logs
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching user logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a record
     *
     * @param string $tableName
     * @param int $recordId
     * @param int $limit
     * @return array
     */
    public function getRecordLogs($tableName, $recordId, $limit = 100) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM audit_logs
                 WHERE table_name = ? AND record_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?"
            );
            $stmt->execute([$tableName, $recordId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching record logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get login activity
     *
     * @param int|null $userId Filter by user (optional)
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getLoginActivity($userId = null, $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT * FROM audit_logs
                    WHERE action IN (?, ?) ";
            $params = [self::ACTION_LOGIN, self::ACTION_LOGIN_FAILED];

            if ($userId !== null) {
                $sql .= "AND user_id = ? ";
                $params[] = $userId;
            }

            $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching login activity: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get failed login attempts
     *
     * @param string $email
     * @param int $withinSeconds
     * @return int
     */
    public function getFailedLoginCount($email, $withinSeconds = 900) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM audit_logs
                 WHERE action = ?
                 AND change_details LIKE ?
                 AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([
                self::ACTION_LOGIN_FAILED,
                '%' . $email . '%',
                $withinSeconds,
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
        } catch (PDOException $e) {
            error_log('Error fetching failed login count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cleanup old audit logs
     *
     * @param int $olderThanSeconds Default: 90 days
     * @return int Number of rows deleted
     */
    public function cleanupOldLogs($olderThanSeconds = AUDIT_LOG_RETENTION) {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM audit_logs
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$olderThanSeconds]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Error cleaning up audit logs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all audit logs with filters
     *
     * @param array $filters ['action' => '', 'user_id' => '', 'date_from' => '', 'date_to' => '']
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllLogs($filters = [], $limit = 100, $offset = 0) {
        try {
            $sql = "SELECT * FROM audit_logs WHERE 1=1";
            $params = [];

            if (isset($filters['action']) && !empty($filters['action'])) {
                $sql .= " AND action = ?";
                $params[] = $filters['action'];
            }

            if (isset($filters['user_id']) && !empty($filters['user_id'])) {
                $sql .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $sql .= " AND created_at >= ?";
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $sql .= " AND created_at <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error fetching all logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }
}
