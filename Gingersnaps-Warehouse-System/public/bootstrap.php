<?php
/**
 * Bootstrap File
 *
 * Initializes the application:
 * - Loads configuration
 * - Loads core classes
 * - Sets up error handling
 */

// Define application root
define('APP_ROOT', dirname(dirname(__FILE__)));

// Load configuration
require_once APP_ROOT . '/config/config.php';

// Autoload classes
require_once APP_ROOT . '/src/auth/SessionManager.php';
require_once APP_ROOT . '/src/auth/Security.php';
require_once APP_ROOT . '/src/auth/AuthService.php';
require_once APP_ROOT . '/src/auth/Authorization.php';
require_once APP_ROOT . '/src/middleware/AuthMiddleware.php';
require_once APP_ROOT . '/src/models/AuditLogModel.php';

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (ENVIRONMENT === 'production') {
        error_log("[$errno] $errstr in $errfile:$errline");
        if (headers_sent() === false) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'An error occurred. Please contact support.';
        }
    } else {
        echo "<strong>Error [$errno]:</strong> $errstr in <strong>$errfile</strong>:$errline";
    }
});

// Set exception handler
set_exception_handler(function($e) {
    error_log('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (ENVIRONMENT === 'production') {
        http_response_code(500);
        echo 'An error occurred. Please contact support.';
    } else {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars($e->__toString()) . '</pre>';
    }
});

// Set JSON error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (headers_sent() === false && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'code' => 500,
            'message' => ENVIRONMENT === 'production' ? 'An error occurred' : $errstr,
        ]);
        exit;
    }
});
