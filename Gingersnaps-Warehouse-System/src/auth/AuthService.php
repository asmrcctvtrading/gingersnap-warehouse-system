<?php
/**
 * AuthService Class
 *
 * Handles user authentication:
 * - User login validation
 * - Password verification
 * - Session creation
 * - User account validation
 * - Password change
 */

class AuthService {

    private $pdo;
    private $auditLog;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/AuditLogModel.php';
        $this->auditLog = new AuditLogModel($pdo);
    }

    /**
     * Authenticate user by email/username and password
     *
     * @param string $emailOrUsername
     * @param string $password
     * @param string $rememberMe Optional remember me token
     * @return array ['success' => bool, 'user' => array, 'message' => string]
     */
    public function login($emailOrUsername, $password, $rememberMe = null) {
        // Check rate limiting
        $rateLimit = Security::checkRateLimit($emailOrUsername);
        if (!$rateLimit['allowed']) {
            return [
                'success' => false,
                'user' => null,
                'message' => "Too many failed login attempts. Please try again in {$rateLimit['reset_in']} seconds.",
                'code' => 429,
            ];
        }

        // Validate input
        if (empty($emailOrUsername) || empty($password)) {
            Security::recordFailedLoginAttempt($emailOrUsername);
            return [
                'success' => false,
                'user' => null,
                'message' => 'Email and password are required',
                'code' => 400,
            ];
        }

        // Find user by email or username
        $user = $this->findUser($emailOrUsername);

        if (!$user) {
            Security::recordFailedLoginAttempt($emailOrUsername);
            // Generic message to prevent email enumeration
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid email or password',
                'code' => 401,
            ];
        }

        // Check if user account is active
        if (!$user['is_active']) {
            Security::recordFailedLoginAttempt($emailOrUsername);
            return [
                'success' => false,
                'user' => null,
                'message' => 'Account is disabled. Please contact administrator.',
                'code' => 403,
            ];
        }

        // Verify password
        if (!Security::verifyPassword($password, $user['password_hash'])) {
            Security::recordFailedLoginAttempt($emailOrUsername);
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid email or password',
                'code' => 401,
            ];
        }

        // Check if password needs rehashing
        if (Security::passwordNeedsRehash($user['password_hash'])) {
            $this->updatePasswordHash($user['id'], $password);
        }

        // Clear rate limit on successful login
        Security::resetRateLimit($emailOrUsername);

        // Create session
        SessionManager::initializeSession();
        $sessionToken = SessionManager::createUserSession($user['id'], $user['role'], $user['email']);

        // Log successful login
        $this->auditLog->logLogin($user['id'], true, Security::getClientIP());

        // Clean user data (remove sensitive fields)
        unset($user['password_hash']);

        return [
            'success' => true,
            'user' => $user,
            'message' => 'Login successful',
            'code' => 200,
            'session_token' => $sessionToken,
        ];
    }

    /**
     * Find user by email or username
     *
     * @param string $emailOrUsername
     * @return array|null
     */
    private function findUser($emailOrUsername) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, email, password_hash, role, is_active, created_at, updated_at
                 FROM users
                 WHERE email = ? OR username = ?
                 LIMIT 1"
            );
            $stmt->execute([$emailOrUsername, $emailOrUsername]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in findUser: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Logout user
     *
     * @return bool
     */
    public function logout() {
        try {
            $userId = SessionManager::logout();

            if ($userId) {
                $this->auditLog->logLogout($userId);
            }

            return true;
        } catch (Exception $e) {
            error_log('Error during logout: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Change user password
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Validate passwords
        $validation = $this->validatePasswordChange($currentPassword, $newPassword);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'code' => 400,
            ];
        }

        // Get user
        $user = $this->getUserById($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'code' => 404,
            ];
        }

        // Verify current password
        if (!Security::verifyPassword($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect',
                'code' => 401,
            ];
        }

        // Update password
        $result = $this->updatePasswordHash($userId, $newPassword);

        if ($result) {
            // Log password change
            $this->auditLog->logAction(
                $userId,
                'password_change',
                'users',
                $userId,
                ['old_hash' => 'hidden', 'new_hash' => 'hidden']
            );

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'code' => 200,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update password',
                'code' => 500,
            ];
        }
    }

    /**
     * Request password reset
     *
     * @param string $email
     * @return array ['success' => bool, 'message' => string, 'token' => string]
     */
    public function requestPasswordReset($email) {
        // Find user
        $user = $this->findUserByEmail($email);
        if (!$user) {
            // Don't reveal if email exists (security)
            return [
                'success' => true,
                'message' => 'If this email exists, password reset link will be sent',
                'code' => 200,
            ];
        }

        // Generate reset token
        $resetToken = Security::generatePasswordResetToken();
        $tokenHash = password_hash($resetToken, PASSWORD_BCRYPT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        try {
            // Store token in database
            $stmt = $this->pdo->prepare(
                "UPDATE users
                 SET password_reset_token = ?, password_reset_expires = ?
                 WHERE id = ?"
            );
            $stmt->execute([$tokenHash, $expiresAt, $user['id']]);

            // Log password reset request
            $this->auditLog->logAction(
                $user['id'],
                'password_reset_requested',
                'users',
                $user['id'],
                []
            );

            return [
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'code' => 200,
                'token' => $resetToken, // In real implementation, include in email
            ];
        } catch (PDOException $e) {
            error_log('Error in requestPasswordReset: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process password reset',
                'code' => 500,
            ];
        }
    }

    /**
     * Reset password with token
     *
     * @param int $userId
     * @param string $token
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword($userId, $token, $newPassword) {
        // Validate password
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters',
                'code' => 400,
            ];
        }

        // Get user
        $user = $this->getUserById($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'code' => 404,
            ];
        }

        // Check if reset token exists and is not expired
        if (!$user['password_reset_token'] || !$user['password_reset_expires']) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token',
                'code' => 401,
            ];
        }

        // Verify token
        if (!password_verify($token, $user['password_reset_token'])) {
            return [
                'success' => false,
                'message' => 'Invalid reset token',
                'code' => 401,
            ];
        }

        // Check expiration
        if (strtotime($user['password_reset_expires']) < time()) {
            return [
                'success' => false,
                'message' => 'Reset token has expired',
                'code' => 401,
            ];
        }

        try {
            // Update password and clear reset token
            $passwordHash = Security::hashPassword($newPassword);
            $stmt = $this->pdo->prepare(
                "UPDATE users
                 SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL
                 WHERE id = ?"
            );
            $stmt->execute([$passwordHash, $userId]);

            // Log password reset
            $this->auditLog->logAction(
                $userId,
                'password_reset',
                'users',
                $userId,
                []
            );

            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'code' => 200,
            ];
        } catch (PDOException $e) {
            error_log('Error in resetPassword: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reset password',
                'code' => 500,
            ];
        }
    }

    /**
     * Get user by ID
     *
     * @param int $userId
     * @return array|null
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, email, username, role, is_active, password_hash,
                        password_reset_token, password_reset_expires, created_at, updated_at
                 FROM users
                 WHERE id = ?
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in getUserById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return array|null
     */
    private function findUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, email, password_hash, role, is_active,
                        password_reset_token, password_reset_expires
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Database error in findUserByEmail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update password hash
     *
     * @param int $userId
     * @param string $password
     * @return bool
     */
    private function updatePasswordHash($userId, $password) {
        try {
            $passwordHash = Security::hashPassword($password);
            $stmt = $this->pdo->prepare(
                "UPDATE users
                 SET password_hash = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            return $stmt->execute([$passwordHash, $userId]);
        } catch (PDOException $e) {
            error_log('Error in updatePasswordHash: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password change inputs
     *
     * @param string $currentPassword
     * @param string $newPassword
     * @return array ['valid' => bool, 'message' => string]
     */
    private function validatePasswordChange($currentPassword, $newPassword) {
        if (empty($currentPassword) || empty($newPassword)) {
            return [
                'valid' => false,
                'message' => 'Current password and new password are required',
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'valid' => false,
                'message' => 'New password must be at least 8 characters',
            ];
        }

        if ($currentPassword === $newPassword) {
            return [
                'valid' => false,
                'message' => 'New password must be different from current password',
            ];
        }

        return ['valid' => true];
    }
}
