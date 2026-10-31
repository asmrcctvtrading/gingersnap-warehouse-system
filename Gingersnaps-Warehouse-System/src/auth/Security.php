<?php
/**
 * Security Class
 *
 * Provides security utilities:
 * - CSRF token generation and validation
 * - Input sanitization and validation
 * - Output escaping (XSS prevention)
 * - Secure password reset tokens
 * - Rate limiting
 */

class Security {

    const CSRF_TOKEN_NAME = '_csrf_token';
    const CSRF_TOKEN_KEY = 'csrf_tokens';
    const PASSWORD_RESET_TOKEN_LENGTH = 32;

    /**
     * Generate CSRF token
     *
     * @return string Token
     */
    public static function generateCSRFToken() {
        // Initialize session if needed
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Generate token
        $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));

        // Store in session
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = [];
        }

        $_SESSION[self::CSRF_TOKEN_KEY][$token] = time();

        // Cleanup old tokens (older than 1 hour)
        self::cleanupCSRFTokens();

        return $token;
    }

    /**
     * Verify CSRF token
     *
     * @param string $token Token to verify
     * @param string $fieldName Form field name (optional)
     * @return bool
     */
    public static function verifyCSRFToken($token = null, $fieldName = self::CSRF_TOKEN_NAME) {
        // Get token from parameter or POST data
        if ($token === null) {
            $token = $_POST[$fieldName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }

        // Token must exist and be non-empty
        if (empty($token)) {
            return false;
        }

        // Token must be in session
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY][$token])) {
            return false;
        }

        // Token is valid - remove it (one-time use)
        unset($_SESSION[self::CSRF_TOKEN_KEY][$token]);

        return true;
    }

    /**
     * Clean up expired CSRF tokens
     *
     * @return void
     */
    private static function cleanupCSRFTokens() {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return;
        }

        $currentTime = time();
        $maxAge = 3600; // 1 hour

        foreach ($_SESSION[self::CSRF_TOKEN_KEY] as $token => $timestamp) {
            if (($currentTime - $timestamp) > $maxAge) {
                unset($_SESSION[self::CSRF_TOKEN_KEY][$token]);
            }
        }
    }

    /**
     * Sanitize input string (remove dangerous characters)
     *
     * @param string $input
     * @param string $type Type of sanitization: 'text', 'email', 'url', 'integer'
     * @return string
     */
    public static function sanitizeInput($input, $type = 'text') {
        // Ensure input is string
        if (!is_string($input)) {
            $input = (string) $input;
        }

        // Trim whitespace
        $input = trim($input);

        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);

            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);

            case 'integer':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

            case 'text':
            default:
                // Remove any HTML tags and encode special characters
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Validate input
     *
     * @param string $input
     * @param string $type Type of validation: 'email', 'url', 'integer', 'phone', 'alphanumeric'
     * @return bool
     */
    public static function validateInput($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;

            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) !== false;

            case 'integer':
                return filter_var($input, FILTER_VALIDATE_INT) !== false;

            case 'phone':
                // Philippine phone number format
                return preg_match('/^(\+63|0)?9\d{9}$/', preg_replace('/\D/', '', $input));

            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9_-]+$/', $input);

            default:
                return true;
        }
    }

    /**
     * Escape output for safe HTML display (XSS prevention)
     *
     * @param string $output
     * @param string $context Context: 'html', 'js', 'css', 'url', 'attribute'
     * @return string
     */
    public static function escapeOutput($output, $context = 'html') {
        switch ($context) {
            case 'html':
                return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

            case 'js':
                // Escape for JavaScript strings
                return addslashes(str_replace(['\\', '"', "'"], ['\\\\', '\\"', "\\'"], $output));

            case 'css':
                // Escape for CSS strings
                return addcslashes($output, '\\"\'');

            case 'url':
                // Escape for URLs
                return urlencode($output);

            case 'attribute':
                // Escape for HTML attributes
                return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

            default:
                return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Generate secure password reset token
     *
     * @return string
     */
    public static function generatePasswordResetToken() {
        return bin2hex(random_bytes(self::PASSWORD_RESET_TOKEN_LENGTH));
    }

    /**
     * Hash password using bcrypt
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
    }

    /**
     * Verify password against hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing
     *
     * @param string $hash
     * @return bool
     */
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
    }

    /**
     * Rate limiting for login attempts
     *
     * @param string $identifier Email or IP address
     * @param int $maxAttempts Maximum failed attempts allowed
     * @param int $window Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
     */
    public static function checkRateLimit($identifier, $maxAttempts = LOGIN_ATTEMPT_LIMIT, $window = LOGIN_ATTEMPT_WINDOW) {
        // Use session or cache to track attempts
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }

        $key = 'login_' . md5($identifier);
        $now = time();

        // Initialize or get attempt data
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 0,
                'first_attempt' => $now,
            ];
        }

        $data = $_SESSION['rate_limits'][$key];
        $elapsed = $now - $data['first_attempt'];

        // Reset if window has passed
        if ($elapsed > $window) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 0,
                'first_attempt' => $now,
            ];
            return [
                'allowed' => true,
                'remaining' => $maxAttempts,
                'reset_in' => 0,
            ];
        }

        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_in' => $window - $elapsed,
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxAttempts - $data['count'],
            'reset_in' => $window - $elapsed,
        ];
    }

    /**
     * Record failed login attempt
     *
     * @param string $identifier Email or IP address
     * @return void
     */
    public static function recordFailedLoginAttempt($identifier) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }

        $key = 'login_' . md5($identifier);

        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 0,
                'first_attempt' => time(),
            ];
        }

        $_SESSION['rate_limits'][$key]['count']++;
    }

    /**
     * Reset rate limit for identifier
     *
     * @param string $identifier
     * @return void
     */
    public static function resetRateLimit($identifier) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['rate_limits'])) {
            $key = 'login_' . md5($identifier);
            unset($_SESSION['rate_limits'][$key]);
        }
    }

    /**
     * Get security headers to prevent common attacks
     *
     * @return array Headers to send
     */
    public static function getSecurityHeaders() {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data:; font-src 'self' fonts.gstatic.com;",
        ];
    }

    /**
     * Send security headers
     *
     * @return void
     */
    public static function sendSecurityHeaders() {
        $headers = self::getSecurityHeaders();
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }
}
