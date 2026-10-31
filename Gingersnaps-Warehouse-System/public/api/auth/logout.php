<?php
/**
 * Logout API Endpoint
 *
 * POST /api/auth/logout.php
 * Handles user logout
 */

require_once dirname(dirname(dirname(__FILE__))) . '/bootstrap.php';

// Initialize session
SessionManager::initializeSession();

// Require login
AuthMiddleware::requireLogin();

// Send security headers
Security::sendSecurityHeaders();

// Logout user
$authService = new AuthService($pdo);
$authService->logout();

// Redirect to login
header('Location: /warehouse-productivity/public/views/login.php');
exit;
