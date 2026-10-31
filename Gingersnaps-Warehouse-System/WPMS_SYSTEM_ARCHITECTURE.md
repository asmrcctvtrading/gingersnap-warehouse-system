# WPMS — System Architecture Document
**Version:** 1.0  
**Date:** October 24, 2026  
**Project:** Warehouse Productivity Monitoring System (WPMS)  
**Deployment:** Z.com cPanel → `https://asmrcctvtechnologies.com/warehouse-productivity/`

---

## 1. Architecture Overview

The WPMS is a modular PHP/MySQL application designed to monitor warehouse employee productivity across picking and packing tasks. The system is built with a **layered architecture** separating configuration, authentication, business logic, data access, and presentation.

### Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│  Presentation Layer (UI / Web Frontend)                 │
│  - HTML templates, Bootstrap CSS, JavaScript            │
│  - Dashboard, reports, forms, real-time status views    │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  Application/Business Logic Layer                       │
│  - Controllers, workflow orchestration, calculations    │
│  - Authentication, authorization, session management    │
│  - Productivity calculations, validation rules          │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  Data Access Layer (Models / Repositories)              │
│  - PDO database access with prepared statements         │
│  - Data persistence, transaction handling               │
│  - Query builders, result mapping                       │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│  Data Storage Layer (MySQL/MariaDB)                    │
│  - Relational database, constraints, indexes            │
│  - Transaction support, referential integrity           │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Module Structure

### 2.1 Core Modules

#### **Module 1: Authentication & Authorization**
- **Purpose:** Secure login, session management, role-based access control
- **Components:**
  - Login/logout screens
  - Password hashing and verification
  - Session-based authentication (PHP sessions)
  - Role-based access middleware
  - Activity logging
- **Responsibilities:**
  - User credential validation
  - Session token generation and validation
  - Permission checks for restricted actions
  - Account activation/deactivation
- **Owner:** Agent 2 (Authentication and Security)

#### **Module 2: Employee Management**
- **Purpose:** Manage warehouse employee profiles, assignments, and status
- **Components:**
  - Employee CRUD operations
  - Department and warehouse assignments
  - Employment status tracking
  - Search and filtering
  - Bulk employee import
- **Responsibilities:**
  - Employee record maintenance
  - Unique ID validation
  - Active/inactive status management
  - Department and location assignments
  - Performance baseline data
- **Owner:** Agent 3 (Picking and Packing Workflows)

#### **Module 3: Picking Task Management**
- **Purpose:** Create, assign, track, and complete picking tasks
- **Components:**
  - Picklist creation and management
  - Task assignment to pickers
  - Barcode scanning support (keyboard input)
  - Manual entry workflows
  - Status workflow enforcement (CREATED → PENDING → IN_PROGRESS → COMPLETED)
  - Exception and error tracking
  - Task detail views
- **Responsibilities:**
  - Task creation and validation
  - Timestamp recording (start, completion)
  - Status transition enforcement
  - Barcode/SKU scanning
  - Error and exception logging
  - Task history maintenance
- **Owner:** Agent 3 (Picking and Packing Workflows)

#### **Module 4: Packing Task Management**
- **Purpose:** Track packing activities, quality, and rework
- **Components:**
  - Packing task creation and assignment
  - Package tracking
  - Quality and error recording
  - Damage and discrepancy tracking
  - Rework workflow
  - Task history
- **Responsibilities:**
  - Package processing tracking
  - Quality defect recording
  - Rework task creation
  - Time tracking (start to completion)
  - Package status management
- **Owner:** Agent 3 (Picking and Packing Workflows)

#### **Module 5: Import & Data Integration**
- **Purpose:** Import Unicommerce picklist data and employee records
- **Components:**
  - CSV/Excel file upload and parsing
  - Import template generation
  - Field validation and duplicate detection
  - Import preview and confirmation
  - Error reporting and logging
  - Bulk import operations
- **Responsibilities:**
  - File upload handling
  - Data validation and transformation
  - Duplicate detection
  - Transaction-based batch import
  - Import audit trail
  - Error recovery
- **Owner:** Agent 4 (Importing and Reporting)

#### **Module 6: Productivity Dashboard & Reporting**
- **Purpose:** Generate dashboards and comprehensive productivity reports
- **Components:**
  - Real-time productivity dashboard
  - Metrics calculation engine
  - Report generation (daily, weekly, monthly)
  - Filtering and date range selection
  - CSV/Excel export functionality
  - Comparative analytics
- **Responsibilities:**
  - Metric calculation (tasks/hour, TAT, error rates)
  - Dashboard data aggregation
  - Report generation
  - Filter application
  - Export formatting
  - Metric documentation
- **Owner:** Agent 4 (Importing and Reporting)

#### **Module 7: Audit & System Administration**
- **Purpose:** Track all significant activities and provide administrative controls
- **Components:**
  - Login activity logging
  - Data change tracking
  - Backup and recovery procedures
  - System configuration management
  - Audit log viewer and exporter
  - Database maintenance tools
- **Responsibilities:**
  - Activity logging
  - Change tracking (who, what, when)
  - Configuration management
  - Backup scheduling
  - Audit log queries and reporting
- **Owner:** Lead Architect (integration), Agent 6 (Deployment)

---

## 3. Role-Based Access Control (RBAC) Model

### Roles and Permissions

#### **Admin Role**
- **Permissions:**
  - Manage all users and employee records
  - Assign tasks
  - View all data across warehouses
  - Generate reports
  - Configure system settings
  - Access audit logs
  - Manage backups
  - Import/export operations
  - Deactivate users
- **UI Access:** All modules

#### **Supervisor Role**
- **Permissions:**
  - Assign tasks to their warehouse/department employees
  - Monitor employee progress in real-time
  - View productivity reports for assigned employees
  - Manage employee status (active/inactive within their scope)
  - Export reports for their department
  - View audit logs (limited to their department)
- **UI Access:** Task assignment, monitoring, reporting (filtered)

#### **Employee Role**
- **Permissions:**
  - View assigned tasks
  - Update task status (start, complete)
  - View their own performance metrics
  - View personal productivity summary
  - Submit exceptions/errors
- **UI Access:** My tasks, my performance

---

## 4. Database Schema Overview

### 4.1 Entity Relationship Diagram

```
┌──────────────┐
│    users     │
│ (auth, roles)│
└──────┬───────┘
       │
       │ 1..* user_id (created_by)
       │
       ├────────────────────┬────────────────────┐
       │                    │                    │
       │                    ↓                    ↓
       │          ┌──────────────────┐  ┌──────────────────┐
       │          │    employees     │  │ audit_logs       │
       │          │ (profile, dept)  │  │ (activity trail) │
       │          └──────┬───────────┘  └──────────────────┘
       │                 │
       │          1...* employee_id
       │                 │
       │    ┌────────────┼────────────┐
       │    │            │            │
       │    ↓            ↓            ↓
       │  ┌──────────────┐  ┌─────────────────┐
       │  │ picking_tasks│  │ packing_tasks   │
       │  │(picked_items)│  │ (package items) │
       │  └──────┬───────┘  └────────┬────────┘
       │         │                   │
       └─────────┼───────────────────┘
               │
               ↓
    ┌──────────────────────┐
    │ imports              │
    │ (picklist,employee   │
    │  import logs)        │
    └──────────────────────┘

Shared Fields:
- warehouse_id: links to warehouse configuration
- created_at: timestamp
- updated_at: timestamp
- created_by: user_id who performed action
```

### 4.2 Table Definitions (Summary)

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| **users** | Authentication & roles | id, email, password_hash, role, active |
| **employees** | Warehouse staff | id, emp_id, name, dept_id, warehouse_id, active |
| **picking_tasks** | Picking operations | id, picklist_code, emp_id, sku, qty, status, start_time, complete_time |
| **picking_errors** | Pick exceptions | id, picking_task_id, error_type, notes |
| **packing_tasks** | Packing operations | id, package_code, emp_id, status, start_time, complete_time |
| **packing_errors** | Pack quality issues | id, packing_task_id, error_type, qty_affected |
| **imports** | Data import records | id, import_type, file_name, record_count, error_count, status |
| **audit_logs** | Activity tracking | id, user_id, action, table_name, record_id, change_details |
| **departments** | Org structure | id, name, warehouse_id |
| **warehouses** | Location data | id, name, location |
| **productivity_summaries** | Cached metrics | id, emp_id, period, tasks_completed, avg_time, error_rate |

---

## 5. Naming Conventions

### Database Objects
- **Tables:** `lowercase_with_underscores` (e.g., `picking_tasks`, `audit_logs`)
- **Columns:** `lowercase_with_underscores` (e.g., `emp_id`, `created_at`, `status`)
- **Primary Keys:** `id` (auto-increment integer)
- **Foreign Keys:** `{table_name_singular}_id` (e.g., `employee_id`, `user_id`)
- **Timestamps:** `created_at`, `updated_at` (datetime)
- **Status columns:** `status` enum (specific values per table)
- **Boolean flags:** `is_active`, `is_deleted`, `is_verified`

### PHP Code
- **Classes:** `PascalCase` (e.g., `PickingTaskController`, `EmployeeModel`)
- **Methods:** `camelCase` (e.g., `assignTask()`, `calculateProductivity()`)
- **Constants:** `UPPER_CASE` (e.g., `ROLE_ADMIN`, `STATUS_COMPLETED`)
- **File names:** Match class name (e.g., `PickingTaskController.php`)
- **Functions:** `lowercase_with_underscores` or `camelCase`

### File Structure
```
public_html/warehouse-productivity/
├── config/                  (configuration files)
├── src/
│   ├── models/             (data access classes)
│   ├── controllers/        (business logic)
│   ├── middleware/         (auth, validation)
│   ├── views/              (PHP templates)
│   └── helpers/            (utility functions)
├── public/                 (web root)
│   ├── index.php          (entry point)
│   ├── css/
│   ├── js/
│   └── images/
├── sql/                    (schema, migrations)
├── tests/                  (test files)
└── docs/                   (documentation)
```

---

## 6. Shared Interfaces & Integration Points

### 6.1 Authentication Interface
All protected controllers require authentication. The `AuthMiddleware` checks:
- Active session (user logged in)
- Valid role for action
- Returns 401/403 on failure

```php
// Usage in controller
class TaskController {
    public function assignTask() {
        AuthMiddleware::requireRole(['admin', 'supervisor']);
        // ... proceed with business logic
    }
}
```

### 6.2 Database Access Pattern
All database queries use PDO with prepared statements:
```php
// Pattern: All models use PDO
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

### 6.3 Response Format
All API/AJAX endpoints return consistent JSON:
```json
{
  "success": true,
  "code": 200,
  "message": "Task assigned successfully",
  "data": { /* entity data */ }
}
```

Error response:
```json
{
  "success": false,
  "code": 400,
  "message": "Validation failed",
  "errors": { "field": "error message" }
}
```

### 6.4 Task Status Workflow
Picking and packing tasks follow strict state transitions:
```
CREATED → PENDING → IN_PROGRESS → COMPLETED
           ↓                          ↓
        CANCELLED                 (no reverse)
```

Status transitions enforce:
- Only authorized users can change status
- Timestamps recorded at transitions
- Audit trail logs who made the change

---

## 7. Security Architecture

### 7.1 Authentication
- **Method:** Session-based (PHP `$_SESSION`)
- **Password Hashing:** `password_hash()` with `PASSWORD_BCRYPT`
- **Session Duration:** Configurable (default 2 hours inactive timeout)
- **Cookies:** `HttpOnly`, `Secure` flags set

### 7.2 Authorization
- **Method:** Role-Based Access Control (RBAC)
- **Enforcement:** Middleware applied to all sensitive endpoints
- **Levels:** Admin > Supervisor > Employee
- **Data Filtering:** Supervisors see only their department; employees see only their own records

### 7.3 Data Protection
- **SQL Injection:** PDO prepared statements with parameterized queries
- **XSS Prevention:** Output escaping on all user-facing data
- **CSRF Protection:** Token validation for state-changing requests
- **Input Validation:** Server-side validation on all inputs
- **File Uploads:** Whitelist file types, store outside web root

### 7.4 Audit Trail
- **Logging:** All login, data changes, imports logged
- **Retention:** Configurable (default 90 days)
- **Immutability:** Audit logs append-only, no deletion/modification
- **Compliance:** Tracks who, what, when, why for all significant actions

---

## 8. Performance Considerations

### 8.1 Indexing Strategy
- **Primary Keys:** All tables
- **Foreign Keys:** Indexed for join performance
- **Status Columns:** Indexed for filtering
- **Date Ranges:** Composite indexes on `(emp_id, created_at)` for time-series queries
- **Search Fields:** Indexes on `emp_id`, `email`, `picklist_code`

### 8.2 Pagination
- Default page size: 50 records
- Maximum page size: 500 records
- Query optimization: SELECT only required columns

### 8.3 Caching
- **Productivity Metrics:** Pre-calculated daily summaries
- **Employee List:** Cached (refreshed hourly or on-demand)
- **Configurations:** File-based caching

---

## 9. Configuration Management

### 9.1 Environment-Specific Settings
```php
// config/config.php
define('ENVIRONMENT', 'production'); // or 'staging', 'development'
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);
define('APP_URL', 'https://asmrcctvtechnologies.com/warehouse-productivity/');
```

### 9.2 Protected Locations
- `.env` file: Outside web root, not committed to source
- Database backups: Outside web root
- Uploaded files: Outside web root, served via download handler
- Application logs: Outside web root

---

## 10. Module Dependencies & Integration Order

```
Phase 1 (Weeks 1-2): Foundation
├─ Agent 1: Database schema (no dependencies)
├─ Agent 2: Auth module (depends on schema)
└─ Agent 3: Employee & basic task mgmt (depends on auth + schema)

Phase 2 (Weeks 3-4): Data & Workflows  
├─ Agent 3: Picking/packing workflows (depends on Phase 1)
├─ Agent 4: Import module (depends on schema + auth)
└─ Agent 4: Dashboard/reporting (depends on tasks + auth)

Phase 3 (Weeks 5): Testing & Deployment
├─ Agent 5: QA & security testing (depends on all modules)
└─ Agent 6: Deployment (depends on QA sign-off)
```

---

## 11. Error Handling Strategy

### 11.1 User-Facing Errors
- **Validation Errors:** Clear field-level messages, suggest corrections
- **Business Logic Errors:** Helpful messages explaining why action failed
- **Recovery Options:** Provide next steps or alternative actions

### 11.2 System Errors
- **Logging:** All errors logged to file with context
- **Monitoring:** Admin dashboard shows critical errors
- **Recovery:** Automatic retries for transient failures, manual intervention for critical

### 11.3 Exception Handling
```php
try {
    $task = new PickingTaskModel();
    $task->assignTo($emp_id);
} catch (ValidationException $e) {
    $response->error($e->getMessage(), 400);
} catch (DatabaseException $e) {
    error_log($e);
    $response->error('Database error. Please try again.', 500);
}
```

---

## 12. Documentation Requirements

### For Each Module:
1. **Interface Documentation** - Explain how other modules interact with it
2. **API Documentation** - If exposing endpoints
3. **Database Schema** - Tables, columns, relationships
4. **Configuration** - Required settings and defaults
5. **Error Codes** - What can go wrong and what to do
6. **Usage Examples** - Code snippets showing common operations
7. **Metric Definitions** - For productivity calculations, explain the formula

---

## 13. Testing Strategy

### Unit Tests
- Test models in isolation
- Mock database calls
- Validate business logic

### Integration Tests
- Test module interactions
- Use test database
- Validate end-to-end workflows

### Security Tests
- SQL injection attempts
- XSS payload validation
- CSRF token validation
- Authorization bypass attempts

### Load Tests
- Simulate concurrent task updates
- Verify pagination performance
- Test large imports (10,000+ records)

---

## 14. Deployment Architecture

### Production Environment (Z.com cPanel)
```
https://asmrcctvtechnologies.com/warehouse-productivity/
  ↓
public_html/warehouse-productivity/
  ├── public/ ← Web root (accessible via HTTP)
  ├── config/ ← DB config (protected, outside web root via symlink or parent)
  ├── src/ ← Application code
  ├── sql/ ← Schema and migrations
  └── .htaccess ← Rewrite rules, security headers
```

### Database
- **Host:** Z.com MariaDB server
- **Database:** `wpms_prod` (separate from existing Warehouse Inventory System)
- **User:** `wpms_user` (least-privilege account)
- **Backups:** Daily, retained 30 days

---

## 15. Version Control & Releases

- **Repository:** Git (in project directory)
- **Branches:** main (production), develop (integration), feature/* (individual work)
- **Tagging:** Semantic versioning (v1.0.0, v1.0.1, v1.1.0)
- **Changelog:** Document breaking changes, new features, bug fixes

---

**Document Status:** Architecture Approved for Phase 1 Implementation  
**Next Step:** Database Schema Finalization by Agent 1
