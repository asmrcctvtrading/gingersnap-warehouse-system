# WPMS Phase 1 QA and Security Testing Plan

**Agent:** Agent 5 (Quality Assurance and Security Testing)  
**Date:** October 24, 2026  
**Project:** Warehouse Productivity Monitoring System (WPMS)  
**Phase:** Phase 1 - Authentication, Employee Management, Task Workflows  
**Status:** In Progress

---

## Executive Summary

This document outlines the comprehensive QA and security testing strategy for WPMS Phase 1. Testing covers:
- **Functional Testing:** Authentication, Employee Management, Picking/Packing Tasks, Import, Dashboard
- **Security Testing:** SQL Injection, XSS, CSRF, Session Security, Authentication, Authorization
- **Performance Testing:** Load tests for import, dashboard, and reporting
- **Regression Testing:** End-to-end workflows and integration scenarios
- **Acceptance Criteria Verification:** All 13 criteria from requirements

### Test Scope
- **In Scope:** All Phase 1 deliverables from Agents 1-4
- **Out of Scope:** Phase 2 features (2FA, API tokens, advanced reporting)
- **Environment:** Development/Testing (Z.com cPanel after deployment)
- **Database:** Test database with seed data only

---

## Test Environment Setup

### Prerequisites
- PHP 7.4+ with PDO MySQL extension
- MySQL/MariaDB 5.7+
- Database: `yitrmcft_wpms` with seed data loaded
- Test framework: PHPUnit or custom test runner
- Security testing tools: Manual testing + code review

### Test Database Configuration
```
Host: localhost (development) or Z.com (production)
Database: yitrmcft_wpms_test
User: yitrmcft_wpms_user
Character Set: utf8mb4
Tables: 13 (from migrations 01-13)
Seed Data: 14_seed_data.sql
```

### Test Credentials (Seed Data)
```
Admin User:
  Email: admin@warehouse.local
  Password: AdminPass123!
  Role: admin
  ID: 1

Supervisor User:
  Email: supervisor@warehouse.local
  Password: SupervisorPass123!
  Role: supervisor
  ID: 2

Employee User:
  Email: employee@warehouse.local
  Password: EmployeePass123!
  Role: employee
  ID: 3
```

---

## 1. FUNCTIONAL TESTING

### 1.1 Authentication & Authorization Tests (15 tests)

#### Test 1.1.1: Login with Valid Credentials
- **Objective:** Verify successful login flow
- **Input:** Valid email + correct password
- **Expected Result:** Session created, redirect to dashboard, no errors
- **Status:** [ ] PENDING

#### Test 1.1.2: Login with Invalid Password
- **Objective:** Verify password validation
- **Input:** Valid email + incorrect password
- **Expected Result:** Error message (generic), no session created
- **Verification:** Error doesn't reveal if user exists
- **Status:** [ ] PENDING

#### Test 1.1.3: Login with Non-Existent User
- **Objective:** Verify non-existent user handling
- **Input:** Non-existent email + any password
- **Expected Result:** Same error as invalid password (generic)
- **Verification:** Cannot distinguish between user not found vs wrong password
- **Status:** [ ] PENDING

#### Test 1.1.4: Session Creation and Management
- **Objective:** Verify session is created on login
- **Expected Result:** Session contains user_id, role, email
- **Duration:** Should timeout after 30 minutes inactivity
- **Status:** [ ] PENDING

#### Test 1.1.5: Logout Clears Session
- **Objective:** Verify logout destroys session
- **Expected Result:** Session data removed, redirect to login
- **Verification:** Cannot access protected pages after logout
- **Status:** [ ] PENDING

#### Test 1.1.6: Remember Me Functionality (if implemented)
- **Objective:** Verify persistent login
- **Input:** Check "Remember me" checkbox on login
- **Expected Result:** Login persists across browser close (check cookie)
- **Status:** [ ] PENDING

#### Test 1.1.7: Password Reset Workflow
- **Objective:** Verify complete password reset flow
- **Steps:**
  1. Request password reset
  2. Receive email with reset link
  3. Click link (verify token valid and single-use)
  4. Enter new password
  5. Login with new password succeeds
- **Status:** [ ] PENDING

#### Test 1.1.8: Role-Based Access - Admin
- **Objective:** Verify Admin can access all modules
- **Login As:** Admin user
- **Expected Result:** Can access Employee, Picking, Packing, Dashboard, Settings
- **Status:** [ ] PENDING

#### Test 1.1.9: Role-Based Access - Supervisor
- **Objective:** Verify Supervisor sees only warehouse modules
- **Login As:** Supervisor user
- **Expected Result:** Can access Picking, Packing, Dashboard; Cannot access Admin Settings
- **Status:** [ ] PENDING

#### Test 1.1.10: Role-Based Access - Employee
- **Objective:** Verify Employee sees only own tasks
- **Login As:** Employee user
- **Expected Result:** Can see own Picking/Packing tasks; Cannot access other employees' tasks
- **Status:** [ ] PENDING

#### Test 1.1.11: Employee Cannot Access Admin Settings
- **Objective:** Verify authorization is enforced
- **Login As:** Employee user
- **Action:** Try to navigate to /admin or /settings
- **Expected Result:** 403 Forbidden response
- **Status:** [ ] PENDING

#### Test 1.1.12: Supervisor Cannot Access Other Employees' Records
- **Objective:** Verify record-level access control
- **Login As:** Supervisor for Department A
- **Action:** Try to view tasks for Department B employee
- **Expected Result:** 403 Forbidden or empty results
- **Status:** [ ] PENDING

#### Test 1.1.13: Rate Limiting - 5 Failed Logins
- **Objective:** Verify brute-force protection
- **Steps:**
  1. Attempt login 5 times with wrong password
  2. Verify account is locked
  3. Attempt 6th login
  4. Expected: "Too many attempts. Try again in 15 minutes"
  5. Wait 15 minutes (or verify in code)
  6. 7th attempt succeeds
- **Status:** [ ] PENDING

#### Test 1.1.14: Deactivated User Cannot Login
- **Objective:** Verify deactivated accounts are blocked
- **Precondition:** Create a user, then deactivate
- **Action:** Try to login as deactivated user
- **Expected Result:** Login fails with generic error
- **Status:** [ ] PENDING

#### Test 1.1.15: Session Regeneration on Login
- **Objective:** Verify session fixation prevention
- **Steps:**
  1. Get initial session ID before login
  2. Login as user
  3. Get new session ID after login
  4. Verify they are different
- **Status:** [ ] PENDING

---

### 1.2 Employee Management Tests (10 tests)

#### Test 1.2.1: Create Employee with Valid Data
- **Objective:** Verify employee creation succeeds
- **Input:**
  ```
  emp_id: EMP-001
  full_name: John Smith
  email: john@warehouse.local
  warehouse_id: 1
  department_id: 1
  employment_status: active
  ```
- **Expected Result:** Employee created, assigned unique ID
- **Status:** [ ] PENDING

#### Test 1.2.2: Create Employee with Duplicate emp_id
- **Objective:** Verify unique constraint
- **Precondition:** Employee EMP-001 already exists
- **Action:** Try to create another with emp_id = EMP-001
- **Expected Result:** Duplicate key error
- **Status:** [ ] PENDING

#### Test 1.2.3: Edit Employee
- **Objective:** Verify employee update
- **Change:** Name and email
- **Expected Result:** Changes saved, audit log recorded
- **Status:** [ ] PENDING

#### Test 1.2.4: Deactivate Employee
- **Objective:** Verify soft delete (is_active = 0)
- **Expected Result:** Employee still in DB, but marked inactive
- **Status:** [ ] PENDING

#### Test 1.2.5: Deactivated Employee Cannot Login
- **Objective:** Verify login rejects inactive employees
- **Precondition:** Employee has user account and is deactivated
- **Action:** Try to login
- **Expected Result:** Login fails
- **Status:** [ ] PENDING

#### Test 1.2.6: Search Employees by ID
- **Objective:** Verify search functionality
- **Input:** emp_id = "EMP-001"
- **Expected Result:** Returns matching employee
- **Status:** [ ] PENDING

#### Test 1.2.7: Search Employees by Name
- **Objective:** Verify name search
- **Input:** "John"
- **Expected Result:** Returns employees with "John" in full_name
- **Status:** [ ] PENDING

#### Test 1.2.8: Filter by Warehouse and Status
- **Objective:** Verify combined filters
- **Input:** warehouse_id=1, employment_status=active
- **Expected Result:** Returns only active employees in warehouse 1
- **Status:** [ ] PENDING

#### Test 1.2.9: Pagination (50 per page default)
- **Objective:** Verify pagination works
- **Create:** 150 test employees
- **Expected Result:** Page 1 has 50, Page 2 has 50, Page 3 has 50
- **Status:** [ ] PENDING

#### Test 1.2.10: Employee Profile Shows Task Statistics
- **Objective:** Verify statistics accuracy
- **Precondition:** Employee has 5 picking tasks, 3 completed
- **Expected Result:** Profile shows: picking_assigned=5, picking_completed=3, etc.
- **Status:** [ ] PENDING

---

### 1.3 Picking Task Tests (14 tests)

#### Test 1.3.1: Create Picking Task
- **Objective:** Verify task creation with valid data
- **Input:**
  ```
  picklist_code: PT-001
  employee_id: 1
  order_number: ORDER-123
  sku_code: SKU-001
  product_description: Test Product
  quantity_ordered: 5
  shelf_code: A1
  batch_code: BATCH-001
  ```
- **Expected Result:** Task created, status = 'created'
- **Status:** [ ] PENDING

#### Test 1.3.2: Assign Picking Task to Employee
- **Objective:** Verify task assignment
- **Precondition:** Task status = 'created'
- **Action:** Assign to employee
- **Expected Result:** Status changes to 'pending'
- **Status:** [ ] PENDING

#### Test 1.3.3: Employee Sees Only Assigned Tasks
- **Objective:** Verify task visibility
- **Precondition:** Employee 1 has task A, Employee 2 has task B
- **Login As:** Employee 1
- **Expected Result:** Employee 1 sees only task A
- **Status:** [ ] PENDING

#### Test 1.3.4: Status Workflow - PENDING → IN_PROGRESS
- **Objective:** Verify status transitions
- **Precondition:** Task status = 'pending'
- **Action:** Mark as started
- **Expected Result:** Status = 'in_progress'
- **Status:** [ ] PENDING

#### Test 1.3.5: Status Workflow - IN_PROGRESS → COMPLETED
- **Objective:** Verify completion
- **Precondition:** Task status = 'in_progress'
- **Action:** Mark as completed
- **Expected Result:** Status = 'completed'
- **Status:** [ ] PENDING

#### Test 1.3.6: Cannot Go Backwards in Status (COMPLETED → IN_PROGRESS)
- **Objective:** Verify status transition enforcement
- **Precondition:** Task status = 'completed'
- **Action:** Try to mark as started
- **Expected Result:** Error thrown
- **Status:** [ ] PENDING

#### Test 1.3.7: Start Task Records Start Time
- **Objective:** Verify timestamp recording
- **Precondition:** Task status = 'pending'
- **Before:** Record current time
- **Action:** Mark as started
- **After:** Record current time
- **Expected Result:** task_start_time is between before and after time
- **Status:** [ ] PENDING

#### Test 1.3.8: Complete Task Records Completion Time
- **Objective:** Verify completion timestamp
- **Similar to Test 1.3.7 but for completion**
- **Expected Result:** task_completion_time recorded correctly
- **Status:** [ ] PENDING

#### Test 1.3.9: Duration Calculated Correctly
- **Objective:** Verify duration = completion - start
- **Precondition:** Task completed with known start and end times
- **Expected Result:** task_duration_seconds = (end_time - start_time) in seconds
- **Status:** [ ] PENDING

#### Test 1.3.10: Completed Task Cannot Be Edited
- **Objective:** Verify read-only enforcement
- **Precondition:** Task status = 'completed'
- **Action:** Try to update any field
- **Expected Result:** Update rejected or exception thrown
- **Status:** [ ] PENDING

#### Test 1.3.11: Barcode Scanning - Picklist Code Lookup
- **Objective:** Verify barcode input (simulate keyboard input)
- **Precondition:** Task with picklist_code = "PT-ABC-123"
- **Action:** Search/lookup by picklist_code
- **Expected Result:** Task found and displayed
- **Status:** [ ] PENDING

#### Test 1.3.12: Manual Order Entry Lookup
- **Objective:** Verify manual order number entry
- **Precondition:** Task with order_number = "ORDER-XYZ-456"
- **Action:** Search/lookup by order_number
- **Expected Result:** Task found and displayed
- **Status:** [ ] PENDING

#### Test 1.3.13: Exception Notes Are Saved
- **Objective:** Verify exception tracking
- **Precondition:** Task in progress
- **Action:** Add note: "Item out of stock"
- **Expected Result:** Note saved in exception_notes field
- **Status:** [ ] PENDING

#### Test 1.3.14: Pending and Overdue Task Identification
- **Objective:** Verify task status filtering
- **Precondition:** Mix of pending, in_progress, completed tasks
- **Query:** Get all pending tasks
- **Expected Result:** Returns only pending status tasks
- **Status:** [ ] PENDING

---

### 1.4 Packing Task Tests (8 tests)

#### Test 1.4.1: Create Packing Task
- **Objective:** Verify task creation
- **Input:**
  ```
  package_code: PKG-001
  employee_id: 2
  shipment_id: SHIP-001
  order_number: ORDER-789
  quantity_items: 3
  ```
- **Expected Result:** Task created, status = 'pending'
- **Status:** [ ] PENDING

#### Test 1.4.2: Assign Packing Task to Employee
- **Objective:** Verify assignment
- **Expected Result:** Task assigned, employee can see it
- **Status:** [ ] PENDING

#### Test 1.4.3: Complete Packing Task Workflow
- **Objective:** Verify full workflow (pending → in_progress → completed)
- **Expected Result:** All transitions succeed, timestamps recorded
- **Status:** [ ] PENDING

#### Test 1.4.4: Quality Issue Flag Is Stored
- **Objective:** Verify quality flag
- **Action:** Mark has_quality_issue = true
- **Expected Result:** Flag stored in database
- **Status:** [ ] PENDING

#### Test 1.4.5: Damage/Discrepancy Notes Saved
- **Objective:** Verify notes field
- **Action:** Add note: "Packaging damage detected"
- **Expected Result:** Notes saved with timestamp and user_id
- **Status:** [ ] PENDING

#### Test 1.4.6: Error Flagging for Quick Filtering
- **Objective:** Verify error tasks are findable
- **Precondition:** Task with quality issue
- **Query:** Get all tasks with quality_issue = true
- **Expected Result:** Task appears in results
- **Status:** [ ] PENDING

#### Test 1.4.7: Quality Issue Tasks Visible to Supervisor
- **Objective:** Verify supervisor can see flagged tasks
- **Login As:** Supervisor
- **Expected Result:** Can filter and view all quality-issue tasks
- **Status:** [ ] PENDING

#### Test 1.4.8: Packing Task Duration Calculated
- **Objective:** Verify duration calculation
- **Expected Result:** task_duration_seconds calculated correctly
- **Status:** [ ] PENDING

---

### 1.5 Import Module Tests (11 tests)

#### Test 1.5.1: CSV File Upload (Valid Format)
- **Objective:** Verify file upload succeeds
- **Input:** Valid CSV with columns: emp_id, order_number, sku_code, quantity, status
- **Expected Result:** File accepted, preview shows data
- **Status:** [ ] PENDING

#### Test 1.5.2: CSV Validation - Missing Required Fields
- **Objective:** Verify validation catches errors
- **Input:** CSV missing 'quantity' column
- **Expected Result:** Validation error before import
- **Status:** [ ] PENDING

#### Test 1.5.3: Duplicate Detection - Same Order + Employee + Date
- **Objective:** Verify duplicate prevention
- **Precondition:** Import record exists: emp_id=1, order=ORDER-001, date=2026-10-24
- **Action:** Try to import same data again
- **Expected Result:** Duplicate detected, import rejected or flagged
- **Status:** [ ] PENDING

#### Test 1.5.4: Date Format Validation
- **Objective:** Verify date validation
- **Input:** Invalid date: "2026-13-50"
- **Expected Result:** Error shown, row rejected
- **Status:** [ ] PENDING

#### Test 1.5.5: Employee ID Validation
- **Objective:** Verify FK constraint
- **Input:** emp_id = 99999 (non-existent)
- **Expected Result:** Validation error, row rejected
- **Status:** [ ] PENDING

#### Test 1.5.6: Preview Shows Correct Data
- **Objective:** Verify preview accuracy
- **Input:** CSV with 10 rows
- **Expected Result:** Preview displays all 10 rows with correct values
- **Status:** [ ] PENDING

#### Test 1.5.7: Successful Import Stores Records
- **Objective:** Verify data insertion
- **Precondition:** Valid CSV ready
- **Action:** Confirm import
- **Expected Result:** All records inserted into database
- **Status:** [ ] PENDING

#### Test 1.5.8: Import History Tracking
- **Objective:** Verify audit trail
- **Expected Result:** imports table contains: file_name, imported_by, imported_at, row_count
- **Status:** [ ] PENDING

#### Test 1.5.9: Error Report Details
- **Objective:** Verify error reporting
- **Input:** CSV with 5 valid, 3 invalid rows
- **Expected Result:** Error report shows: row numbers, error descriptions, counts
- **Status:** [ ] PENDING

#### Test 1.5.10: Template Download
- **Objective:** Verify template provides correct format
- **Action:** Download CSV template
- **Expected Result:** File contains correct column headers and sample data
- **Status:** [ ] PENDING

#### Test 1.5.11: Large File Handling (100+ Records)
- **Objective:** Verify performance and correctness
- **Input:** CSV with 500 records
- **Expected Result:** Import completes in <30 seconds, all records stored
- **Status:** [ ] PENDING

---

### 1.6 Dashboard & Reports Tests (12 tests)

#### Test 1.6.1: Dashboard Displays Task Counts
- **Objective:** Verify metrics accuracy
- **Precondition:** Database has: assigned=10, completed=7, pending=3
- **Expected Result:** Dashboard shows: Assigned: 10, Completed: 7, Pending: 3
- **Status:** [ ] PENDING

#### Test 1.6.2: 7-Day Completion Chart
- **Objective:** Verify chart data
- **Precondition:** Daily completion data for past 7 days
- **Expected Result:** Chart displays correct values for each day
- **Status:** [ ] PENDING

#### Test 1.6.3: Employee Leaderboard Rankings
- **Objective:** Verify ranking accuracy
- **Precondition:** Employees with different completion counts
- **Expected Result:** Top performers listed in correct order
- **Status:** [ ] PENDING

#### Test 1.6.4: Average Task Time Calculation
- **Objective:** Verify average duration
- **Precondition:** 5 tasks with durations: 600s, 700s, 800s, 500s, 900s
- **Expected Result:** Average = 700s (sum / count)
- **Status:** [ ] PENDING

#### Test 1.6.5: Filters Work (Date Range)
- **Objective:** Verify date filtering
- **Action:** Filter by date range: 2026-10-01 to 2026-10-15
- **Expected Result:** Shows only tasks created in that range
- **Status:** [ ] PENDING

#### Test 1.6.6: Filters Work (Employee)
- **Objective:** Verify employee filter
- **Action:** Filter by employee_id = 5
- **Expected Result:** Shows only tasks for that employee
- **Status:** [ ] PENDING

#### Test 1.6.7: Filters Work (Warehouse)
- **Objective:** Verify warehouse filter
- **Action:** Filter by warehouse_id = 2
- **Expected Result:** Shows only tasks for that warehouse
- **Status:** [ ] PENDING

#### Test 1.6.8: Picking Performance Report Accuracy
- **Objective:** Verify picking metrics
- **Expected Result:** Report shows: total_tasks, completed, completion_rate, avg_time
- **Status:** [ ] PENDING

#### Test 1.6.9: Packing Performance Report Accuracy
- **Objective:** Verify packing metrics
- **Expected Result:** Report shows: total_tasks, quality_issues, quality_issue_rate, avg_time
- **Status:** [ ] PENDING

#### Test 1.6.10: Warehouse Productivity Report Aggregations
- **Objective:** Verify warehouse-level metrics
- **Expected Result:** Shows: tasks by department, completion rates, average times
- **Status:** [ ] PENDING

#### Test 1.6.11: CSV Export Format
- **Objective:** Verify export correctness
- **Action:** Export report to CSV
- **Expected Result:** CSV has correct columns, proper quoting, valid format
- **Status:** [ ] PENDING

#### Test 1.6.12: PDF Export Renders
- **Objective:** Verify PDF generation (if available)
- **Action:** Export to PDF
- **Expected Result:** PDF contains all report data, readable format
- **Status:** [ ] PENDING

---

## 2. SECURITY TESTING

### 2.1 SQL Injection Tests (5 tests)

#### Test 2.1.1: SQL Injection in Login
- **Payload:** `' OR '1'='1`
- **Expected:** Query escaped, login fails or succeeds only with valid credentials
- **Verification:** Check PDO prepared statements in code
- **Status:** [ ] PENDING

#### Test 2.1.2: SQL Injection in Employee Search
- **Payload:** `UNION SELECT * FROM users --`
- **Expected:** Treated as literal string, no injection
- **Status:** [ ] PENDING

#### Test 2.1.3: SQL Injection in Import Data
- **Payload:** CSV with: `emp_id: "1'; DROP TABLE employees; --"`
- **Expected:** Treated as string literal, table not dropped
- **Status:** [ ] PENDING

#### Test 2.1.4: Admin Comment (All Queries Use Prepared Statements)
- **Verification:** Code review of all database queries
- **Expected:** All queries use `?` placeholders and `execute([$param])`
- **No raw SQL concatenation found**
- **Status:** [ ] PENDING

#### Test 2.1.5: Admin Comment (PDO Prepared Statements Enforced)
- **Verification:** Check repositories use PDO prepare/execute
- **Expected:** No $pdo->query() calls with user input
- **Status:** [ ] PENDING

---

### 2.2 Cross-Site Scripting (XSS) Tests (5 tests)

#### Test 2.2.1: XSS in Employee Name Field
- **Payload:** `<script>alert('xss')</script>`
- **Storage:** Save as employee name
- **Retrieval:** Display employee list
- **Expected:** Script tags visible as `&lt;script&gt;`, no execution
- **Status:** [ ] PENDING

#### Test 2.2.2: XSS in Notes/Comments Field
- **Payload:** `"><script>alert('xss')</script>`
- **Storage:** Add as exception note
- **Expected:** HTML escaped, script doesn't execute
- **Status:** [ ] PENDING

#### Test 2.2.3: Output Escaping Verification
- **Verification:** Code review
- **Check:** All user input in views uses `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`
- **Expected:** Output properly escaped
- **Status:** [ ] PENDING

#### Test 2.2.4: No Eval or Dynamic Code Execution
- **Verification:** Grep for eval(), create_function(), $GLOBALS
- **Expected:** None found
- **Status:** [ ] PENDING

#### Test 2.2.5: Content Security Policy Header
- **Verification:** Check response headers
- **Expected:** CSP header configured to restrict script sources
- **Status:** [ ] PENDING

---

### 2.3 Cross-Site Request Forgery (CSRF) Tests (4 tests)

#### Test 2.3.1: CSRF Token Required on Forms
- **Verification:** Code review of all forms
- **Expected:** All POST/PUT/DELETE forms have CSRF token field
- **Status:** [ ] PENDING

#### Test 2.3.2: POST Without Token Rejected
- **Action:** Submit form with token removed
- **Expected:** Request rejected with 403 or error
- **Status:** [ ] PENDING

#### Test 2.3.3: Token Validation on State-Changing Operations
- **Verification:** Code check in auth middleware
- **Expected:** All POST/PUT/DELETE validated before execution
- **Status:** [ ] PENDING

#### Test 2.3.4: Token Expiration (1-hour)
- **Verification:** Check token generation and validation
- **Expected:** Tokens expire after 1 hour
- **Status:** [ ] PENDING

---

### 2.4 Session Security Tests (7 tests)

#### Test 2.4.1: Session Cookies Have HttpOnly Flag
- **Action:** Login, check cookie attributes
- **Expected:** HttpOnly flag set (prevents JS access)
- **Status:** [ ] PENDING

#### Test 2.4.2: Session Cookies Have Secure Flag
- **Verification:** Cookie configuration check
- **Expected:** Secure flag set (HTTPS only)
- **Status:** [ ] PENDING

#### Test 2.4.3: Session Cookies Have SameSite=Strict
- **Action:** Check cookie headers
- **Expected:** SameSite=Strict attribute present
- **Status:** [ ] PENDING

#### Test 2.4.4: Session Fixation Prevention
- **Objective:** Verify session regeneration on login
- **Steps:**
  1. Get initial session ID (before login)
  2. Login
  3. Get new session ID (after login)
- **Expected:** Session IDs different
- **Status:** [ ] PENDING

#### Test 2.4.5: Session Timeout (30 minutes inactivity)
- **Action:** Login, wait 31 minutes without action
- **Expected:** Session expires, redirect to login on next request
- **Status:** [ ] PENDING

#### Test 2.4.6: Session Logout Destroys Session
- **Action:** Login, logout, try to access protected page
- **Expected:** Redirected to login, session data gone
- **Status:** [ ] PENDING

#### Test 2.4.7: Session Invalidation on Suspicious Activity
- **Verification:** Code check for IP/User-Agent validation
- **Expected:** Session rejected if IP or User-Agent changes
- **Status:** [ ] PENDING

---

### 2.5 Authentication Security Tests (5 tests)

#### Test 2.5.1: Password Is Hashed with Bcrypt
- **Verification:** Check seed data and password_hash() code
- **Expected:** `PASSWORD_BCRYPT` used, cost >= 12
- **Database check:** No plaintext passwords in users table
- **Status:** [ ] PENDING

#### Test 2.5.2: Password Reset Token Is Secure
- **Verification:** Token generation check
- **Expected:** 32+ random bytes, cryptographically secure
- **Status:** [ ] PENDING

#### Test 2.5.3: Reset Token Single-Use and Expires
- **Action:** Request password reset twice
- **Expected:** First token works, second is invalid; Token expires after 1 hour
- **Status:** [ ] PENDING

#### Test 2.5.4: Brute-Force Protection (5 Failed Attempts)
- **Action:** 5 failed logins in 15 minutes
- **Expected:** Account locked, user blocked for 15 minutes
- **Status:** [ ] PENDING

#### Test 2.5.5: Lockout Duration Is 15 Minutes
- **Action:** Trigger lockout, wait 14:59, try login
- **Expected:** Still locked
- **Action:** Wait another 1 second (total 15:00), try login
- **Expected:** Login succeeds (or would succeed with correct password)
- **Status:** [ ] PENDING

---

### 2.6 Authorization Tests (5 tests)

#### Test 2.6.1: Employee Cannot Access Other Employee's Tasks
- **Login As:** Employee 1
- **Action:** Try to view Employee 2's tasks (via API or direct link)
- **Expected:** 403 Forbidden or empty results
- **Status:** [ ] PENDING

#### Test 2.6.2: Employee Cannot Edit/Delete Tasks
- **Login As:** Employee
- **Action:** Try to PATCH/DELETE a task
- **Expected:** 403 Forbidden
- **Status:** [ ] PENDING

#### Test 2.6.3: Supervisor Cannot Access Admin Settings
- **Login As:** Supervisor
- **Action:** Try to navigate to /admin or /settings
- **Expected:** 403 Forbidden
- **Status:** [ ] PENDING

#### Test 2.6.4: Admin Panel Requires Admin Role
- **Login As:** Employee or Supervisor
- **Action:** Try to access admin dashboard
- **Expected:** 403 Forbidden
- **Status:** [ ] PENDING

#### Test 2.6.5: Module Access Is Role-Specific
- **Verification:** Check AuthMiddleware for permission checks
- **Expected:** Each module has requireRole() or requirePermission() call
- **Status:** [ ] PENDING

---

### 2.7 Data Integrity Tests (6 tests)

#### Test 2.7.1: Completed Tasks Cannot Be Modified
- **Precondition:** Task status = 'completed'
- **Action:** Try to update any field
- **Expected:** Update rejected, exception thrown
- **Status:** [ ] PENDING

#### Test 2.7.2: Timestamps Are in UTC
- **Verification:** Check database for datetime values
- **Expected:** All timestamps in UTC timezone
- **Status:** [ ] PENDING

#### Test 2.7.3: Duration Calculation Correct (No Negative Values)
- **Precondition:** Task with start_time = 10:00, completion_time = 10:05
- **Expected:** Duration = 300 seconds (positive)
- **Status:** [ ] PENDING

#### Test 2.7.4: Status Transitions Are Enforced
- **Verification:** Code check in repositories
- **Expected:** Invalid transitions rejected (e.g., completed → pending)
- **Status:** [ ] PENDING

#### Test 2.7.5: No Data Loss on Import Failures
- **Precondition:** Valid and invalid data in import file
- **Action:** Import fails on row 5
- **Expected:** Rows 1-4 inserted, row 5+ not inserted (transactional)
- **Status:** [ ] PENDING

#### Test 2.7.6: Original Imported File Is Preserved
- **Action:** Import CSV file
- **Expected:** Original file stored in imports directory, accessible for audit
- **Status:** [ ] PENDING

---

## 3. PERFORMANCE TESTING

### 3.1 Load Tests

#### Test 3.1.1: Import 100 Picking Tasks
- **Action:** Import CSV with 100 rows
- **Measurement:** Time to completion
- **Target:** < 10 seconds
- **Status:** [ ] PENDING

#### Test 3.1.2: Import 500 Packing Tasks
- **Action:** Import CSV with 500 rows
- **Measurement:** Time to completion
- **Target:** < 30 seconds
- **Status:** [ ] PENDING

#### Test 3.1.3: Load Dashboard with 1000+ Tasks
- **Precondition:** Database contains 1000+ tasks
- **Action:** Load dashboard page
- **Measurement:** Page load time
- **Target:** < 2 seconds
- **Status:** [ ] PENDING

#### Test 3.1.4: Generate Large Report (1000+ Records)
- **Precondition:** Database with 1000+ completed tasks
- **Action:** Export report to CSV or PDF
- **Measurement:** Generation time
- **Target:** < 5 seconds
- **Status:** [ ] PENDING

### 3.2 Database Performance Tests

#### Test 3.2.1: Indexes Are Used
- **Action:** EXPLAIN query on common operations
- **Expected:** Queries use indexes (type=ref or range, not ALL)
- **Status:** [ ] PENDING

#### Test 3.2.2: No N+1 Query Problems
- **Action:** Get 10 picking tasks and all associated data
- **Expected:** 1 query for tasks + 1 query for employees = 2 total (not 1 + 10)
- **Status:** [ ] PENDING

#### Test 3.2.3: Connection Pooling Works
- **Verification:** Check PDO connection reuse
- **Expected:** Multiple requests reuse same connection
- **Status:** [ ] PENDING

---

## 4. REGRESSION TESTING

### 4.1 Integration Tests (End-to-End Workflows)

#### Test 4.1.1: Complete Picking Workflow
- **Steps:**
  1. Create employee (EMP-XXX)
  2. Create picking task (PT-XXX)
  3. Assign to employee (status → pending)
  4. Employee starts task (status → in_progress, record start_time)
  5. Employee updates quantity_picked
  6. Employee completes task (status → completed, record completion_time)
  7. Verify duration calculated
  8. Verify task is read-only
- **Expected:** All steps succeed, data correct
- **Status:** [ ] PENDING

#### Test 4.1.2: Complete Packing Workflow
- **Steps:**
  1. Create employee (PACK-XXX)
  2. Create packing task (PKG-XXX)
  3. Assign to employee
  4. Employee starts task
  5. Employee records quality issue (if any)
  6. Employee adds notes
  7. Employee completes task
  8. Verify task in database
- **Expected:** All steps succeed
- **Status:** [ ] PENDING

#### Test 4.1.3: Import → Task Assignment → Completion
- **Steps:**
  1. Import CSV with 10 tasks
  2. Supervisor assigns tasks to employees
  3. Employees complete tasks
  4. Dashboard reflects completions
- **Expected:** Full workflow works
- **Status:** [ ] PENDING

#### Test 4.1.4: Existing Warehouse Inventory System Unaffected
- **Verification:** Check that old system still works
- **Action:** Perform old system operations
- **Expected:** No errors, data intact
- **Status:** [ ] PENDING

#### Test 4.1.5: Z.com cPanel Environment Compatibility
- **Verification:** Test on Z.com after deployment
- **Expected:** All functions work with Z.com MariaDB
- **Status:** [ ] PENDING

---

## 5. ACCEPTANCE CRITERIA VERIFICATION

All 13 acceptance criteria must be verified:

- [ ] 1. Authorized users can log in and access only permitted modules
- [ ] 2. Administrators can manage employees and warehouse settings
- [ ] 3. Picking and packing tasks can be assigned, tracked, and completed
- [ ] 4. Task start times, completion times, and durations are recorded accurately
- [ ] 5. CSV and supported Excel files can be imported with validation and error reporting
- [ ] 6. Duplicate or invalid imports are identified before finalizing
- [ ] 7. Productivity dashboards and reports produce results consistent with documented calculations
- [ ] 8. Authorized users can export reports
- [ ] 9. Audit logs capture important activities and record changes
- [ ] 10. Security and functional tests pass without unresolved critical defects
- [ ] 11. Application works within actual Z.com cPanel environment
- [ ] 12. Existing Warehouse Inventory System continues to function
- [ ] 13. Deployment, backup, and user documentation provided

---

## 6. TEST EXECUTION PLAN

### Phase 1: Functional Testing (Days 1-2)
- Execute all 15 authentication tests
- Execute all 10 employee management tests
- Execute all 14 picking task tests
- Document results in FUNCTIONAL_TEST_RESULTS.md

### Phase 2: Task & Import Testing (Day 3)
- Execute all 8 packing task tests
- Execute all 11 import tests
- Document results

### Phase 3: Dashboard & Security (Days 4-5)
- Execute all 12 dashboard/report tests
- Execute all security tests (31 tests)
- Document results in SECURITY_TEST_RESULTS.md

### Phase 4: Performance & Regression (Days 6-7)
- Execute all performance tests
- Execute all regression tests
- Document results in PERFORMANCE_TEST_RESULTS.md

### Phase 5: Final Review & Sign-Off (Day 8)
- Review all test results
- Create DEFECT_LOG.md for any failures
- Verify all 13 acceptance criteria
- Create go/no-go recommendation

---

## 7. REPORTING

### 7.1 Test Result Documents

**FUNCTIONAL_TEST_RESULTS.md**
```
Total Tests: 70
Passed: XX
Failed: XX
Pass Rate: XX%

Breakdown:
- Authentication: X/15 PASSED
- Employee Management: X/10 PASSED
- Picking Tasks: X/14 PASSED
- Packing Tasks: X/8 PASSED
- Import Module: X/11 PASSED
- Dashboard & Reports: X/12 PASSED
```

**SECURITY_TEST_RESULTS.md**
```
Total Security Tests: 31
Passed: XX
Failed: XX

Coverage:
- SQL Injection: X/5 PASSED
- XSS Prevention: X/5 PASSED
- CSRF Protection: X/4 PASSED
- Session Security: X/7 PASSED
- Authentication: X/5 PASSED
- Authorization: X/5 PASSED
```

**PERFORMANCE_TEST_RESULTS.md**
```
Import 100 records: X seconds (target < 10s)
Import 500 records: X seconds (target < 30s)
Dashboard load: X seconds (target < 2s)
Report export: X seconds (target < 5s)
```

**DEFECT_LOG.md**
```
ID | Severity | Description | Reproduction | Status
---|----------|-------------|--------------|-------
DFT-001 | Critical | Login fails with valid credentials | Steps | Open
...
```

---

## 8. SUCCESS CRITERIA

**Release can proceed when:**
- ✅ All functional tests pass (100% pass rate)
- ✅ All security tests pass (0 vulnerabilities)
- ✅ No critical or high-severity defects remaining
- ✅ Performance tests meet targets
- ✅ All 13 acceptance criteria verified
- ✅ Comprehensive test documentation complete

**Release must be blocked if:**
- ❌ Any critical-severity defect remains unresolved
- ❌ SQL injection vulnerability found
- ❌ XSS vulnerability found
- ❌ Any authentication bypass found
- ❌ Authorization bypass found
- ❌ More than 5% of functional tests fail

---

## Document Version

| Version | Date | Author | Status |
|---------|------|--------|--------|
| 1.0 | 2026-10-24 | Agent 5 | In Progress |

**Next Step:** Execute all tests and document results

---

**Prepared By:** Agent 5 (QA and Security Testing)  
**Date:** October 24, 2026  
**Project:** WPMS Phase 1
