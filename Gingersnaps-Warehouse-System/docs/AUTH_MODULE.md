# Authentication & Authorization Module Documentation

**Module Owner:** Agent 2 (Authentication and Security)  
**Version:** 1.0.0  
**Date:** October 24, 2026  

---

## Overview

The Authentication & Authorization module provides secure user login, session management, role-based access control (RBAC), and comprehensive audit logging for the WPMS application.

### Key Features

- Secure password-based authentication using bcrypt
- Session-based authentication with configurable timeouts
- Role-based access control (Admin, Supervisor, Employee)
- Permission checking middleware
- CSRF protection on all forms
- Rate limiting for login attempts
- Comprehensive audit logging of all authentication events
- Password reset workflow with email verification
- Account activation/deactivation controls

---

## Architecture

### Components

```
SessionManager.php      → Session initialization, validation, renewal
Security.php           → CSRF tokens, input sanitization, output escaping, rate limiting
AuthService.php        → User login, password management, authentication
Authorization.php      → RBAC implementation, permission checking
AuthMiddleware.php     → Middleware for protecting routes
AuditLogModel.php      → Logging all authentication and data changes
```

### Flow Diagram

```
1. User visits /login.php
   ↓
2. Login form with CSRF token
   ↓
3. User submits email + password
   ↓
4. POST /api/auth/login.php
   ├─ Verify CSRF token
   ├─ Check rate limiting
   ├─ Find user by email/username
   ├─ Verify password with bcrypt
   ├─ Check user is active
   └─ Create session on success
   ↓
5. Redirect to dashboard
   ↓
6. Each request validates session
   ├─ Check session exists
   ├─ Check session not expired
   ├─ Check required permissions
   └─ Process request or return 401/403
```

---

## Configuration

### Environment Variables (config/config.php)

```php
// Session timeout (30 minutes of inactivity)
define('SESSION_TIMEOUT', 1800);

// Cookie security flags
define('SESSION_COOKIE_SECURE', true);      // HTTPS only
define('SESSION_COOKIE_HTTPONLY', true);    // No JavaScript access
define('SESSION_COOKIE_SAMESITE', 'Strict'); // CSRF protection

// Password hashing
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// CSRF tokens
define('CSRF_TOKEN_LENGTH', 32);

// Login rate limiting
define('LOGIN_ATTEMPT_LIMIT', 5);           // Max 5 failed attempts
define('LOGIN_ATTEMPT_WINDOW', 900);        // In 15 minutes
```

---

## Usage Examples

### 1. Basic Login (from login.php form)

```php
// Form with CSRF token
<form method="POST" action="/api/auth/login.php">
    <input type="hidden" name="_csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

### 2. Protect a Route

```php
<?php
require_once 'bootstrap.php';

// Require user to be logged in
AuthMiddleware::requireLogin();

// Now user is authenticated, proceed with dashboard
echo "Welcome, " . SessionManager::getUserEmail();
?>
```

### 3. Check User Permissions

```php
<?php
// Require specific role
AuthMiddleware::requireRole('admin');  // Or ['admin', 'supervisor']

// Require specific permission
AuthMiddleware::requirePermission('manage_users');

// Check in code
if (Authorization::hasPermission('assign_tasks')) {
    // Show task assignment interface
}

// Check multiple
if (Authorization::hasAllPermissions(['manage_users', 'import_data'])) {
    // User is admin
}
?>
```

### 4. Module Access Control

```php
<?php
// Require access to a module
AuthMiddleware::requireModule('employees');

// Check in code
if (Authorization::canAccessModule('reports')) {
    // Show reports interface
}
?>
```

### 5. Logging In a User

```php
<?php
$authService = new AuthService($pdo);

$result = $authService->login('user@example.com', 'password123');

if ($result['success']) {
    header('Location: /dashboard.php');
} else {
    echo "Login failed: " . $result['message'];
    // Show login form with error
}
?>
```

### 6. Manual Session Creation

```php
<?php
// Initialize session (only needed once per request)
SessionManager::initializeSession();

// Create session for user
$sessionToken = SessionManager::createUserSession(
    $userId,        // int
    'supervisor',   // string role
    'user@example.com' // string email
);

// Get user info from session
$userId = SessionManager::getUserId();
$role = SessionManager::getUserRole();
$email = SessionManager::getUserEmail();

// Logout
SessionManager::logout();
?>
```

### 7. CSRF Protection

```php
<?php
// Generate token
$token = Security::generateCSRFToken();

// Include in form
<input type="hidden" name="_csrf_token" value="<?php echo $token; ?>">

// Verify token on form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['_csrf_token'])) {
        die('CSRF token invalid');
    }
    // Process form
}
?>
```

### 8. Input Sanitization

```php
<?php
// Sanitize different input types
$email = Security::sanitizeInput($input, 'email');
$url = Security::sanitizeInput($input, 'url');
$text = Security::sanitizeInput($input, 'text');
$number = Security::sanitizeInput($input, 'integer');

// Validate inputs
if (!Security::validateInput($email, 'email')) {
    echo 'Invalid email format';
}

if (!Security::validateInput($phone, 'phone')) {
    echo 'Invalid phone number';
}
?>
```

### 9. Output Escaping (XSS Prevention)

```php
<?php
// Escape for HTML
echo Security::escapeOutput($userInput, 'html');

// Escape for JavaScript string
var user = "<?php echo Security::escapeOutput($username, 'js'); ?>";

// Escape for URL
$url = "search.php?q=" . Security::escapeOutput($query, 'url');

// Escape for HTML attribute
<div class="<?php echo Security::escapeOutput($className, 'attribute'); ?>">
?>
```

### 10. Audit Logging

```php
<?php
$auditLog = new AuditLogModel($pdo);

// Log login
$auditLog->logLogin($userId, true, $_SERVER['REMOTE_ADDR']);

// Log data change
$auditLog->logAction(
    $userId,        // Who did it
    'user_created', // What action
    'users',        // Which table
    $newUserId,     // Which record
    [               // What changed
        'email' => $email,
        'role' => $role,
    ]
);

// Query logs
$logs = $auditLog->getLoginActivity($userId, 50);
$logs = $auditLog->getRecordLogs('users', $userId);
?>
```

---

## Database Schema Requirements

The module requires these tables:

### users table

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'employee') DEFAULT 'employee',
    is_active TINYINT DEFAULT 1,
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);
```

### audit_logs table

```sql
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    change_details JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## Role-Based Access Control

### Roles

| Role | Description | Access Level |
|------|-------------|--------------|
| Admin | Full system access | All modules |
| Supervisor | Warehouse management | Tasks, Reporting, Employees |
| Employee | Task completion | Tasks, Own Performance |

### Permissions by Role

```
ADMIN:
├─ manage_users
├─ manage_employees
├─ assign_tasks
├─ view_reports
├─ import_data
├─ view_audit
├─ system_config
├─ complete_tasks
└─ view_own_performance

SUPERVISOR:
├─ assign_tasks
├─ manage_employees
├─ view_reports
└─ view_own_performance

EMPLOYEE:
├─ complete_tasks
└─ view_own_performance
```

---

## Security Features

### Password Security

- Passwords hashed with bcrypt (cost=12)
- Password verification uses timing-safe comparison
- Password reset tokens single-use with expiration
- Password strength validated (min 8 characters)
- Failed login attempts logged

### Session Security

- HttpOnly cookies (prevents JavaScript access)
- Secure flag (HTTPS only)
- SameSite=Strict (CSRF prevention)
- Session timeout after 30 minutes of inactivity
- Session regeneration on login
- Session fixation prevention

### Input Protection

- All inputs sanitized and validated
- Prepared statements for all SQL queries
- CSRF tokens on all state-changing requests
- SQL injection prevention via PDO parameterization
- XSS prevention via output escaping

### Audit Trail

- All login attempts logged (success and failure)
- All data changes logged with user, timestamp, IP
- Password reset requests logged
- Role changes logged
- Import events logged

---

## Testing

### Unit Test Cases

1. **Valid Login**
   - User can log in with correct email and password
   - Session is created with correct user data
   - Password hash is verified correctly

2. **Invalid Credentials**
   - Login fails with wrong password
   - Login fails with non-existent user
   - Generic error message prevents email enumeration

3. **Rate Limiting**
   - After 5 failed attempts, login is blocked
   - Error message shows retry time
   - Successful login resets counter

4. **Session Management**
   - Session timeout works (30 min inactivity)
   - Session renewal extends timeout
   - Logout destroys session
   - Invalid session ID is rejected

5. **CSRF Protection**
   - Missing CSRF token is rejected
   - Invalid CSRF token is rejected
   - Expired CSRF token is rejected
   - Valid token works once, then cannot be reused

6. **RBAC**
   - Admin can access all modules
   - Supervisor can access only assigned modules
   - Employee can access only own tasks
   - Unauthorized access returns 403

### Integration Tests

1. Complete login workflow with all security checks
2. Password reset flow with token verification
3. Session timeout and renewal
4. Logout with audit logging
5. Cross-site request forgery prevention

### Security Tests

- SQL injection attempts in login fields
- XSS payloads in username/password
- CSRF token manipulation
- Session hijacking attempts
- Authorization bypass attempts
- Brute force attack prevention

---

## Error Handling

### Error Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Proceed |
| 400 | Bad Request | Check input |
| 401 | Unauthorized | Log in |
| 403 | Forbidden | Insufficient permissions |
| 429 | Too Many Requests | Rate limited, try later |
| 500 | Server Error | Contact support |

### Error Messages

**Production Mode:**
- Generic error messages (don't reveal user existence)
- Technical details logged to server logs
- User-friendly guidance provided

**Development Mode:**
- Full error details with stack traces
- Database errors with context
- Input validation failures explained

---

## Integration with Other Modules

### Employee Management Module
- AuthService validates user exists before creating session
- Authorization checks supervisor access to employees
- Audit logs record employee changes

### Task Management Module
- AuthMiddleware requires login before task access
- Authorization ensures user can only see own tasks (employee) or department tasks (supervisor)
- Audit logs record task assignments

### Import Module
- Requires admin role for imports
- Audit logs each import with filename, record count
- Failed imports logged with error details

### Reporting Module
- Requires supervisor+ role
- Supervisors see only their department data
- Admins see all data
- Audit logs report generation

---

## Troubleshooting

### User Cannot Log In

1. Check user exists in database
2. Check user is_active = 1
3. Check password is correct (case-sensitive)
4. Check for rate limiting (5 failed attempts = 15 min lockout)
5. Check session cookie settings match browser

### CSRF Token Errors

1. Ensure session is initialized before token generation
2. Ensure token is in form before submission
3. Check token hasn't expired (1 hour)
4. Check POST method is used (not GET)

### Session Expires Too Quickly

1. Check SESSION_TIMEOUT value (default 1800 seconds = 30 min)
2. Verify session is being renewed on each request
3. Check server time is correct
4. Check PHP max_session_lifetime setting

### Authorization Denied Errors

1. Check user has required role
2. Check role has required permission
3. Check module access is allowed for role
4. Verify user is logged in (not expired session)

---

## Security Checklist

- [x] Passwords hashed with bcrypt (PASSWORD_BCRYPT, cost=12)
- [x] Session cookies: HttpOnly=true, Secure=true, SameSite=Strict
- [x] Session timeout configured (30 minutes default)
- [x] Session regeneration on login (prevents fixation)
- [x] CSRF tokens on all state-changing requests
- [x] SQL injection prevention (PDO prepared statements)
- [x] XSS prevention (output escaping)
- [x] Input validation (email, phone, alphanumeric, etc.)
- [x] Rate limiting (5 failed logins in 15 min window)
- [x] Audit logging (all login and data changes)
- [x] Error handling (no sensitive data exposure)
- [x] Security headers (X-Frame-Options, X-Content-Type-Options, etc.)

---

## Future Enhancements

1. **Two-Factor Authentication (2FA)**
   - TOTP or SMS verification
   - Required for admin users
   - Optional for others

2. **API Tokens**
   - Long-lived tokens for API access
   - Separate from session tokens
   - Rate limited and logged

3. **Social Login**
   - Google OAuth integration
   - Microsoft/Azure AD integration
   - SAML support for enterprises

4. **Advanced Audit Logging**
   - Elasticsearch integration for log search
   - Real-time alerting for suspicious activity
   - Compliance reporting (SOC 2, ISO 27001)

5. **Session Management UI**
   - Active sessions dashboard
   - Ability to revoke sessions
   - Device and IP tracking

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [PHP password_hash()](https://www.php.net/manual/en/function.password-hash.php)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)
- [Session Security](https://www.php.net/manual/en/session.security.php)

---

**Document Status:** Complete  
**Last Updated:** October 24, 2026  
**Next Review:** December 1, 2026
