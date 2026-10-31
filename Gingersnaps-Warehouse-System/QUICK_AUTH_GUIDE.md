# Quick Authentication Implementation Guide

**For:** Agent 3 (Picking & Packing Workflows) and other agents  
**Purpose:** How to use the authentication & authorization in your modules  
**Date:** October 24, 2026

---

## 5-Minute Quick Start

### 1. Protect Your Page

Every page that needs authentication should start with:

```php
<?php
// At the very top of your file
require_once dirname(__FILE__) . '/../bootstrap.php';

// Require user to be logged in
AuthMiddleware::requireLogin();
?>
<!-- Your HTML here -->
```

### 2. Check User Permissions

```php
<?php
// Check if user is admin
if (Authorization::isAdmin()) {
    echo "Welcome, Admin!";
}

// Check specific permission
if (Authorization::hasPermission('assign_tasks')) {
    // Show task assignment form
}

// Check multiple permissions
if (Authorization::hasAllPermissions(['manage_users', 'import_data'])) {
    // User is admin
}
?>
```

### 3. Get Current User Info

```php
<?php
$userId = SessionManager::getUserId();
$role = SessionManager::getUserRole();
$email = SessionManager::getUserEmail();

echo "Logged in as: $email ($role)";
?>
```

### 4. Protect AJAX Endpoints

```php
<?php
// In /api/some-endpoint.php
require_once dirname(__FILE__) . '/../../bootstrap.php';

// Check authentication
AuthMiddleware::requireLogin();

// Check permission
AuthMiddleware::requirePermission('complete_tasks');

// Verify CSRF token
if (!Security::verifyCSRFToken()) {
    ResponseHelper::error('CSRF validation failed', 403);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Sanitize inputs
$taskId = filter_var($input['task_id'] ?? 0, FILTER_VALIDATE_INT);
$status = Security::sanitizeInput($input['status'] ?? '', 'text');

// Process request
// ...

// Return response
ResponseHelper::success(['task_id' => $taskId], 'Task updated');
?>
```

### 5. Add CSRF Token to Forms

```html
<form method="POST" action="/api/update-task.php">
    <!-- CSRF token (required) -->
    <input type="hidden" name="_csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
    
    <!-- Form fields -->
    <input type="text" name="task_name" required>
    <button type="submit">Save</button>
</form>
```

---

## Common Scenarios

### Scenario 1: Employee Can Only See Own Tasks

```php
<?php
$userId = SessionManager::getUserId();
$role = SessionManager::getUserRole();
$taskOwnerId = $_GET['task_owner_id'];

// Check if user can access this task
if (!Authorization::canAccessRecord($userId, $role, $taskOwnerId)) {
    ResponseHelper::forbidden('You cannot access this task');
}

// Proceed to show task
?>
```

### Scenario 2: Supervisor Can Only See Department Tasks

```php
<?php
$userId = SessionManager::getUserId();
$role = SessionManager::getUserRole();
$taskDepartmentId = 5;

// Get supervisor's department
$accessibleDepts = Authorization::getAccessibleDepartments($userId, $pdo);

if (!in_array($taskDepartmentId, $accessibleDepts) && $role !== 'admin') {
    ResponseHelper::forbidden('You cannot access this department');
}

// Proceed to show tasks
?>
```

### Scenario 3: Admin-Only Page

```php
<?php
// This will die with 403 if user is not admin
AuthMiddleware::requireRole('admin');

// Code here only runs if user is admin
echo "Admin dashboard";
?>
```

### Scenario 4: Require Multiple Permissions

```php
<?php
// Require both permissions
AuthMiddleware::requirePermission([
    'manage_users',
    'view_audit'
]);

// Code here only runs if user has BOTH permissions (only admin has these)
?>
```

### Scenario 5: Sanitize and Validate User Input

```php
<?php
// From form or API request
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$name = $_POST['name'] ?? '';

// Sanitize
$email = Security::sanitizeInput($email, 'email');
$phone = Security::sanitizeInput($phone, 'phone');
$name = Security::sanitizeInput($name, 'text');

// Validate
if (!Security::validateInput($email, 'email')) {
    ResponseHelper::validationError(['email' => 'Invalid email format']);
}

if (!Security::validateInput($phone, 'phone')) {
    ResponseHelper::validationError(['phone' => 'Invalid phone number']);
}

// Proceed with valid data
?>
```

### Scenario 6: Escape Output for Security

```php
<?php
// When displaying user data in HTML
echo "Welcome, " . Security::escapeOutput($userInput, 'html');

// When putting data in JavaScript
?>
<script>
var userName = "<?php echo Security::escapeOutput($name, 'js'); ?>";
</script>

<!-- In HTML attribute -->
<div class="<?php echo Security::escapeOutput($className, 'attribute'); ?>">

<!-- In URL -->
<a href="/search.php?q=<?php echo Security::escapeOutput($query, 'url'); ?>">Search</a>
<?php
?>
```

### Scenario 7: Log User Actions

```php
<?php
$auditLog = new AuditLogModel($pdo);
$userId = SessionManager::getUserId();

// Log when user creates an employee
$auditLog->logAction(
    $userId,                    // Who did it
    'employee_created',         // What action
    'employees',                // Which table
    $newEmployeeId,             // Which record
    [                           // What changed
        'name' => $name,
        'email' => $email,
        'role' => 'employee',
    ]
);
?>
```

### Scenario 8: Redirect After Login

```php
<?php
// After successful login
if ($result['success']) {
    // Redirect based on role
    switch ($result['user']['role']) {
        case 'admin':
            header('Location: /dashboard.php');
            break;
        case 'supervisor':
            header('Location: /supervisor-dashboard.php');
            break;
        default:
            header('Location: /my-tasks.php');
    }
    exit;
}
?>
```

---

## Common Issues & Solutions

### Issue: "Headers already sent" error

**Cause:** Output before `require_once bootstrap.php`

**Solution:** Make sure bootstrap.php is at the very top:
```php
<?php
require_once dirname(__FILE__) . '/../bootstrap.php';  // ← First line!
// Everything else after this

```

### Issue: Session shows as not logged in

**Cause:** Session not initialized or expired

**Solution:** 
```php
<?php
// Always initialize session
SessionManager::initializeSession();

// Renew activity on each request
SessionManager::renewSessionActivity();

// Then check
if (!SessionManager::isLoggedIn()) {
    // Not logged in
}
?>
```

### Issue: CSRF token validation fails

**Cause:** Token missing or incorrect

**Solution:**
```php
<?php
// Generate token for form
$token = Security::generateCSRFToken();

// In form (MUST be present)
<input type="hidden" name="_csrf_token" value="<?php echo $token; ?>">

// On submission (MUST verify)
if (!Security::verifyCSRFToken($_POST['_csrf_token'])) {
    die('CSRF validation failed');
}
?>
```

### Issue: User says "Too many failed logins"

**Cause:** Rate limiting after 5 failed attempts in 15 minutes

**Solution:**
- Wait 15 minutes and try again
- Or admin can check audit logs and extend timeout if needed

### Issue: "Invalid email or password" but credentials look right

**Cause:** Credentials are case-sensitive, or user account is disabled

**Solution:**
1. Check email is exact match (case matters)
2. Check user `is_active = 1` in database
3. Check password was hashed correctly when user created

---

## Response Format

All API endpoints return consistent JSON:

### Success Response (200 OK)
```json
{
    "success": true,
    "code": 200,
    "message": "Operation successful",
    "data": {
        "id": 123,
        "name": "John Doe"
    }
}
```

### Error Response (400+ status)
```json
{
    "success": false,
    "code": 403,
    "message": "You do not have permission",
    "errors": {
        "field_name": "Error message"
    }
}
```

---

## Module Dependencies

### What You MUST Have

1. **Database tables** from Agent 1:
   - `users` table with authentication fields
   - `audit_logs` table for logging

2. **Configuration** from `config/config.php`:
   - Database credentials
   - Session settings
   - Security settings

### What You CAN Use

1. **SessionManager** - Get logged-in user info
2. **Authorization** - Check permissions
3. **AuthMiddleware** - Protect routes
4. **Security** - Sanitize/validate/escape
5. **AuditLogModel** - Log user actions
6. **ResponseHelper** - Return consistent responses

### What You DON'T Need

- Don't create your own authentication (use AuthService)
- Don't bypass permission checks (use AuthMiddleware)
- Don't store passwords plaintext (use Security::hashPassword)
- Don't trust user input (always sanitize)

---

## Full Working Example

```php
<?php
/**
 * Example: Employee Task List Page
 */

// Initialize application (must be first)
require_once dirname(__FILE__) . '/../bootstrap.php';

// Protect route - must be logged in
AuthMiddleware::requireLogin();

// Get current user
$userId = SessionManager::getUserId();
$role = SessionManager::getUserRole();
$email = SessionManager::getUserEmail();

// For database queries
global $pdo;

// Get user's tasks
if ($role === 'employee') {
    // Employee sees only their own tasks
    $stmt = $pdo->prepare("SELECT * FROM picking_tasks WHERE employee_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
} else if ($role === 'supervisor') {
    // Supervisor sees tasks for employees in their department
    $stmt = $pdo->prepare("
        SELECT pt.* FROM picking_tasks pt
        JOIN employees e ON pt.employee_id = e.id
        WHERE e.supervisor_id = ? OR ? IN (SELECT id FROM users WHERE role = 'admin')
        ORDER BY pt.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
} else {
    // Admin sees all tasks
    $stmt = $pdo->prepare("SELECT * FROM picking_tasks ORDER BY created_at DESC");
    $stmt->execute([]);
}

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token for any forms
$csrfToken = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Tasks</title>
</head>
<body>
    <h1>Tasks for <?php echo Security::escapeOutput($email, 'html'); ?></h1>
    
    <table>
        <tr>
            <th>Task ID</th>
            <th>Status</th>
            <th>Created</th>
            <th>Action</th>
        </tr>
        <?php foreach ($tasks as $task): ?>
        <tr>
            <td><?php echo Security::escapeOutput($task['id'], 'html'); ?></td>
            <td><?php echo Security::escapeOutput($task['status'], 'html'); ?></td>
            <td><?php echo Security::escapeOutput($task['created_at'], 'html'); ?></td>
            <td>
                <form method="POST" action="/api/update-task.php">
                    <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <select name="status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <hr>
    <a href="/api/auth/logout.php">Logout</a>
</body>
</html>
```

---

## Testing Your Code

### Before Submitting

```php
<?php
// Test 1: Can you access authenticated page?
// Test 2: Session data correct?
// Test 3: Permissions working?
// Test 4: CSRF token validates?
// Test 5: SQL injection blocked?
// Test 6: XSS prevented?
// Test 7: Audit log records action?

// Quick test
require_once 'bootstrap.php';
AuthMiddleware::requireLogin();

echo "Current user: " . SessionManager::getUserEmail() . "\n";
echo "Role: " . SessionManager::getUserRole() . "\n";
echo "Can assign tasks: " . (Authorization::hasPermission('assign_tasks') ? 'Yes' : 'No') . "\n";

// Test input sanitization
$test = '<script>alert("xss")</script>';
echo "Escaped: " . Security::escapeOutput($test, 'html') . "\n";

// Test password hashing
$hash = Security::hashPassword('test123');
echo "Password verified: " . (Security::verifyPassword('test123', $hash) ? 'Yes' : 'No') . "\n";
?>
```

---

## Need More Help?

1. Read `docs/AUTH_MODULE.md` for complete documentation
2. Read `docs/SECURITY_CHECKLIST.md` for security details
3. Check the actual code in `src/auth/` for implementation details
4. Look at `public/views/login.php` for a complete example

---

**Version:** 1.0  
**Last Updated:** October 24, 2026  
**Questions?** Contact Agent 2 (Authentication and Security)
