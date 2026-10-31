<?php
/**
 * SessionManager Class
 *
 * Handles secure session initialization, management, and cleanup.
 * Features:
 * - Secure session configuration (HttpOnly, Secure, SameSite)
 * - Session timeout handling
 * - Session renewal on activity
 * - Session fixation prevention
 * - Activity tracking
 */

class SessionManager {

    // Session keys
    const USER_KEY = 'user_id';
    const ROLE_KEY = 'user_role';
    const EMAIL_KEY = 'user_email';
    const LAST_ACTIVITY_KEY = 'last_activity';
    const IP_KEY = 'ip_address';
    const USER_AGENT_KEY = 'user_agent';
    const SESSION_TOKEN_KEY = 'session_token';

    /**
     * Initialize secure session
     *
     * @return void
     */
    public static function initializeSession() {
        // Session already started?
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configure session parameters before starting
        ini_set('session.name', 'WPMS_SESSION');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);

        // Set secure cookie parameters
        $cookieOptions = [
            'lifetime' => SESSION_COOKIE_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_COOKIE_SECURE,
            'httponly' => SESSION_COOKIE_HTTPONLY,
            'samesite' => SESSION_COOKIE_SAMESITE,
        ];

        session_set_cookie_params($cookieOptions);

        // Start session
        session_start();

        // Validate session integrity
        self::validateSessionIntegrity();
    }

    /**
     * Create a new session for authenticated user
     *
     * @param int $userId User ID
     * @param string $userRole User role
     * @param string $userEmail User email
     * @return string Session token
     */
    public static function createUserSession($userId, $userRole, $userEmail) {
        // Regenerate session ID for security (session fixation prevention)
        session_regenerate_id(true);

        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));

        // Store user data in session
        $_SESSION[self::USER_KEY] = (int) $userId;
        $_SESSION[self::ROLE_KEY] = $userRole;
        $_SESSION[self::EMAIL_KEY] = $userEmail;
        $_SESSION[self::SESSION_TOKEN_KEY] = $sessionToken;
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        $_SESSION[self::IP_KEY] = self::getClientIP();
        $_SESSION[self::USER_AGENT_KEY] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return $sessionToken;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION[self::USER_KEY]) && !self::isSessionExpired();
    }

    /**
     * Get current user ID
     *
     * @return int|null
     */
    public static function getUserId() {
        return self::isLoggedIn() ? (int) $_SESSION[self::USER_KEY] : null;
    }

    /**
     * Get current user role
     *
     * @return string|null
     */
    public static function getUserRole() {
        return self::isLoggedIn() ? $_SESSION[self::ROLE_KEY] : null;
    }

    /**
     * Get current user email
     *
     * @return string|null
     */
    public static function getUserEmail() {
        return self::isLoggedIn() ? $_SESSION[self::EMAIL_KEY] : null;
    }

    /**
     * Check if session has expired
     *
     * @return bool
     */
    public static function isSessionExpired() {
        if (!isset($_SESSION[self::LAST_ACTIVITY_KEY])) {
            return true;
        }

        $lastActivity = $_SESSION[self::LAST_ACTIVITY_KEY];
        $currentTime = time();

        if (($currentTime - $lastActivity) > SESSION_TIMEOUT) {
            return true;
        }

        return false;
    }

    /**
     * Renew session on activity
     *
     * @return void
     */
    public static function renewSessionActivity() {
        if (self::isLoggedIn()) {
            $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        }
    }

    /**
     * Validate session integrity (IP, User Agent)
     *
     * @return bool
     */
    private static function validateSessionIntegrity() {
        // Skip validation if session is new (no user logged in)
        if (!isset($_SESSION[self::USER_KEY])) {
            return true;
        }

        // Check IP address (warn if changed, but don't invalidate)
        // IP can change with mobile devices, so we'll log but not block
        $currentIP = self::getClientIP();
        if (isset($_SESSION[self::IP_KEY]) && $_SESSION[self::IP_KEY] !== $currentIP) {
            error_log("Session IP changed for user {$_SESSION[self::USER_KEY]}: {$_SESSION[self::IP_KEY]} -> $currentIP");
        }

        // Check User Agent
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (isset($_SESSION[self::USER_AGENT_KEY]) && $_SESSION[self::USER_AGENT_KEY] !== $currentUserAgent) {
            error_log("Session User Agent changed for user {$_SESSION[self::USER_KEY]}");
        }

        return true;
    }

    /**
     * Logout user and destroy session
     *
     * @return void
     */
    public static function logout() {
        // Store user ID for audit logging before destroying session
        $userId = self::getUserId();

        // Clear session array
        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session
        session_destroy();

        return $userId;
    }

    /**
     * Get client IP address (with proxy handling)
     *
     * @return string
     */
    private static function getClientIP() {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle multiple IPs (take first one)
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        // Check for remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    /**
     * Check if this is a safe AJAX request
     *
     * @return bool
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get session lifetime remaining (in seconds)
     *
     * @return int
     */
    public static function getSessionTimeRemaining() {
        if (!self::isLoggedIn()) {
            return 0;
        }

        $lastActivity = $_SESSION[self::LAST_ACTIVITY_KEY] ?? time();
        $elapsed = time() - $lastActivity;
        $remaining = SESSION_TIMEOUT - $elapsed;

        return max(0, $remaining);
    }
}
