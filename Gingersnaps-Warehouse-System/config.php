<?php
/**
 * WPMS Configuration
 * Database and application settings
 */

// Load environment variables
$env_file = dirname(__FILE__) . '/.env';
if (!file_exists($env_file)) {
    die('ERROR: .env file not found. Please create it with database credentials.');
}

$env = parse_ini_file($env_file);

// Database Configuration
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? 'yitrmcft_wpms');
define('DB_USER', $env['DB_USER'] ?? 'yitrmcft_wpms_user');
define('DB_PASS', $env['DB_PASSWORD'] ?? '');

// Application Settings
define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_DEBUG', $env['APP_DEBUG'] ?? 'false');
define('APP_URL', $env['APP_URL'] ?? 'https://asmrcctvtechnologies.com/warehouse-productivity/');

// Session Configuration
define('SESSION_TIMEOUT', (int)($env['SESSION_TIMEOUT'] ?? '1800'));
define('SESSION_SECURE', ($env['SESSION_SECURE'] ?? 'true') === 'true');
define('SESSION_HTTPONLY', ($env['SESSION_HTTPONLY'] ?? 'true') === 'true');
define('SESSION_SAMESITE', $env['SESSION_SAMESITE'] ?? 'Strict');

// Security
define('BCRYPT_COST', 10);

// Database Connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection error: ' . htmlspecialchars($e->getMessage()));
}

// Session Configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
ini_set('session.cookie_samesite', SESSION_SAMESITE);

session_start();

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Helper function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . 'login.php');
        exit;
    }
}

// Helper function to check user role
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

// Helper function for CSRF tokens
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
