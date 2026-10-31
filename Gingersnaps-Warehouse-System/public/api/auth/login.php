<?php
/**
 * Login API Endpoint
 *
 * POST /api/auth/login.php
 * Handles user authentication
 */

// Initialize application
require_once dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Initialize session
SessionManager::initializeSession();

// Send security headers
Security::sendSecurityHeaders();

// Verify CSRF token
if (!Security::verifyCSRFToken()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'code' => 403, 'message' => 'Invalid security token']);
    exit;
}

// Get form data
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$rememberMe = $_POST['remember_me'] ?? false;

// Sanitize inputs
$email = Security::sanitizeInput($email, 'email');

// Authenticate user
$authService = new AuthService($pdo);
$result = $authService->login($email, $password, $rememberMe);

if ($result['success']) {
    // Set response header
    http_response_code(200);
    header('Content-Type: application/json');

    // Return success response
    echo json_encode([
        'success' => true,
        'code' => 200,
        'message' => 'Login successful',
        'redirect' => '/warehouse-productivity/public/dashboard.php',
    ]);

    // Log successful login to session for display
    $_SESSION['login_success'] = 'Login successful. Welcome back!';

} else {
    // Set response header based on error code
    http_response_code($result['code'] ?? 401);
    header('Content-Type: application/json');

    // Return error response
    echo json_encode([
        'success' => false,
        'code' => $result['code'] ?? 401,
        'message' => $result['message'] ?? 'Login failed',
    ]);

    // Store error in session
    $_SESSION['login_error'] = $result['message'] ?? 'Login failed';
}

exit;
