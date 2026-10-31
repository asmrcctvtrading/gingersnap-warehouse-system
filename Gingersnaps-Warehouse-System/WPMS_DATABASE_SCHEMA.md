# WPMS Database Schema Design
**Version:** 1.0  
**Date:** October 24, 2026  
**Status:** Ready for Agent 1 Implementation

---

## Overview

This document defines the complete relational database schema for the Warehouse Productivity Monitoring System. The schema supports:
- User authentication and role-based access control
- Employee profile and assignment tracking
- Picking and packing task workflows
- Task error and quality tracking
- Data imports from Unicommerce
- Audit logging for compliance
- Productivity metrics and reporting

**Database Name:** `wpms_prod` (production) or `wpms_dev` (development)  
**Database Type:** MySQL 5.7+ or MariaDB 10.3+

---

## 1. Core Tables

### 1.1 `users` — Authentication & Authorization

**Purpose:** Store application user accounts with role-based access control.

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'supervisor', 'employee') NOT NULL DEFAULT 'employee',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
| Field | Type | Notes |
|-------|------|-------|
| `id` | INT UNSIGNED | Primary key, auto-increment |
| `email` | VARCHAR(255) | Unique login identifier |
| `password_hash` | VARCHAR(255) | bcrypt hash (60 chars minimum) |
| `full_name` | VARCHAR(255) | User's full name |
| `role` | ENUM | admin, supervisor, employee |
| `is_active` | TINYINT(1) | 0=inactive, 1=active |
| `last_login_at` | DATETIME | NULL until first login |
| `created_at` | DATETIME | System timestamp |
| `updated_at` | DATETIME | System timestamp |
| `created_by` | INT UNSIGNED | FK to users(id) |
| `updated_by` | INT UNSIGNED | FK to users(id) |

**Key Constraints:**
- `email`: UNIQUE (no duplicate user accounts)
- `created_by`, `updated_by`: Self-referential FK to track admin actions

---

### 1.2 `warehouses` — Location & Facility Management

**Purpose:** Define warehouse locations and facilities.

```sql
CREATE TABLE warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(500),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Data:**
```sql
INSERT INTO warehouses (name, location, created_by) VALUES
('Main Warehouse', 'Gingersnaps HQ, Manila', 1),
('Secondary Facility', 'Rizal Distribution Center', 1);
```

---

### 1.3 `departments` — Organizational Structure

**Purpose:** Define departments within warehouses.

```sql
CREATE TABLE departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    warehouse_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_is_active (is_active),
    UNIQUE KEY uk_warehouse_name (warehouse_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Data:**
```sql
INSERT INTO departments (warehouse_id, name, created_by) VALUES
(1, 'Picking', 1),
(1, 'Packing', 1),
(1, 'QA', 1);
```

---

### 1.4 `employees` — Warehouse Staff Profiles

**Purpose:** Store employee information and assignment details.

```sql
CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    warehouse_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    employment_status ENUM('active', 'inactive', 'on_leave') NOT NULL DEFAULT 'active',
    hire_date DATE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_emp_id (emp_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_department_id (department_id),
    INDEX idx_is_active (is_active),
    INDEX idx_employment_status (employment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
| Field | Type | Notes |
|-------|------|-------|
| `emp_id` | VARCHAR(50) | External employee ID (e.g., from Unicommerce) |
| `user_id` | INT UNSIGNED | Link to application user (optional) |
| `full_name` | VARCHAR(255) | Employee name |
| `email` | VARCHAR(255) | Contact email |
| `phone` | VARCHAR(20) | Contact number |
| `warehouse_id` | INT UNSIGNED | FK to warehouses |
| `department_id` | INT UNSIGNED | FK to departments |
| `employment_status` | ENUM | active, inactive, on_leave |
| `hire_date` | DATE | Employment start date |
| `is_active` | TINYINT(1) | Soft delete flag |

**Key Constraints:**
- `emp_id`: UNIQUE (no duplicate employee IDs)
- `user_id`: Optional (employee may not have system login)

---

## 2. Picking & Packing Task Tables

### 2.1 `picking_tasks` — Picking Task Records

**Purpose:** Track individual picking operations and their completion status.

```sql
CREATE TABLE picking_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picklist_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    sku_code VARCHAR(100) NOT NULL,
    product_description VARCHAR(500),
    quantity_ordered INT UNSIGNED NOT NULL DEFAULT 1,
    quantity_picked INT UNSIGNED DEFAULT 0,
    shelf_code VARCHAR(100),
    batch_code VARCHAR(100),
    status ENUM('created', 'pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME NULL,
    task_completion_time DATETIME NULL,
    task_duration_seconds INT UNSIGNED NULL,
    has_error TINYINT(1) NOT NULL DEFAULT 0,
    exception_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_picklist_code (picklist_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_employee_status (employee_id, status),
    INDEX idx_employee_date (employee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
| Field | Type | Notes |
|-------|------|-------|
| `picklist_code` | VARCHAR(100) | Unique task identifier |
| `employee_id` | INT UNSIGNED | FK to employees |
| `order_number` | VARCHAR(100) | Sales order ID |
| `sku_code` | VARCHAR(100) | Product SKU |
| `product_description` | VARCHAR(500) | Item description |
| `quantity_ordered` | INT UNSIGNED | Ordered amount |
| `quantity_picked` | INT UNSIGNED | Actually picked amount |
| `shelf_code` | VARCHAR(100) | Warehouse location |
| `batch_code` | VARCHAR(100) | Inventory batch |
| `status` | ENUM | created, pending, in_progress, completed, cancelled |
| `task_start_time` | DATETIME | When picking started |
| `task_completion_time` | DATETIME | When picking completed |
| `task_duration_seconds` | INT UNSIGNED | Calculated from timestamps |
| `has_error` | TINYINT(1) | Flag for quick filtering |
| `exception_notes` | TEXT | Notes on errors or issues |

**Triggers (To Be Implemented):**
- Auto-calculate `task_duration_seconds` when `task_completion_time` is set
- Set `has_error` flag when error record is created

---

### 2.2 `picking_errors` — Pick Exceptions & Defects

**Purpose:** Record errors, exceptions, and quality issues in picking operations.

```sql
CREATE TABLE picking_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    picking_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    error_severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    description TEXT,
    resolution TEXT,
    requires_rework TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (picking_task_id) REFERENCES picking_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_picking_task_id (picking_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_error_severity (error_severity),
    INDEX idx_requires_rework (requires_rework)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Error Types (Reference):**
- WRONG_QUANTITY
- WRONG_PRODUCT
- WRONG_LOCATION
- DAMAGED_ITEM
- MISSING_ITEM
- BARCODE_MISMATCH
- OTHER

---

### 2.3 `packing_tasks` — Packing Task Records

**Purpose:** Track packing operations and package completion.

```sql
CREATE TABLE packing_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(100) NOT NULL UNIQUE,
    employee_id INT UNSIGNED NOT NULL,
    shipment_id VARCHAR(100) NOT NULL,
    order_number VARCHAR(100) NOT NULL,
    quantity_items INT UNSIGNED NOT NULL DEFAULT 1,
    package_status ENUM('created', 'pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'created',
    task_start_time DATETIME NULL,
    task_completion_time DATETIME NULL,
    task_duration_seconds INT UNSIGNED NULL,
    has_error TINYINT(1) NOT NULL DEFAULT 0,
    has_quality_issue TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_package_code (package_code),
    INDEX idx_package_status (package_status),
    INDEX idx_created_at (created_at),
    INDEX idx_employee_status (employee_id, package_status),
    INDEX idx_employee_date (employee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 2.4 `packing_errors` — Packing Quality Issues

**Purpose:** Record quality defects, damages, and discrepancies in packing.

```sql
CREATE TABLE packing_errors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    packing_task_id INT UNSIGNED NOT NULL,
    error_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    quantity_affected INT UNSIGNED,
    description TEXT,
    action_taken TEXT,
    rework_required TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (packing_task_id) REFERENCES packing_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_packing_task_id (packing_task_id),
    INDEX idx_error_type (error_type),
    INDEX idx_rework_required (rework_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Error Types (Reference):**
- DAMAGED_PACKAGE
- INCORRECT_ITEM
- MISSING_ITEM
- WEIGHT_DISCREPANCY
- BROKEN_SEAL
- LABEL_ISSUE
- OTHER

---

## 3. Import & Integration Tables

### 3.1 `imports` — Import Records

**Purpose:** Track all bulk data imports (Unicommerce, employee data, etc.)

```sql
CREATE TABLE imports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED,
    total_records INT UNSIGNED,
    successful_records INT UNSIGNED,
    failed_records INT UNSIGNED,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    error_summary TEXT,
    import_started_at DATETIME,
    import_completed_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_import_type (import_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Import Types:**
- PICKLIST_IMPORT (Unicommerce picklists)
- EMPLOYEE_IMPORT (Employee records)
- INVENTORY_IMPORT (Stock data)

---

### 3.2 `import_details` — Import Record Mapping

**Purpose:** Map import records to database records and track errors.

```sql
CREATE TABLE import_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_id INT UNSIGNED NOT NULL,
    source_row_number INT UNSIGNED NOT NULL,
    source_data JSON NOT NULL,
    target_table VARCHAR(100),
    target_record_id INT UNSIGNED,
    status ENUM('success', 'duplicate', 'validation_error', 'skipped') NOT NULL DEFAULT 'success',
    error_message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    INDEX idx_import_id (import_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Audit & Compliance Tables

### 4.1 `audit_logs` — Activity & Change Tracking

**Purpose:** Immutable audit trail for all significant activities.

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    change_summary TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at),
    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields:**
| Field | Type | Notes |
|-------|------|-------|
| `user_id` | INT UNSIGNED | Who performed the action |
| `action` | VARCHAR(100) | CREATE, UPDATE, DELETE, LOGIN, IMPORT, etc. |
| `entity_type` | VARCHAR(100) | picking_task, employee, import, etc. |
| `entity_id` | INT UNSIGNED | Which record was affected |
| `old_values` | JSON | Previous values (for updates) |
| `new_values` | JSON | New values (for creates/updates) |
| `change_summary` | TEXT | Human-readable description |
| `ip_address` | VARCHAR(45) | For security monitoring |
| `user_agent` | VARCHAR(500) | Browser/client information |

**Actions (Reference):**
- LOGIN, LOGOUT
- CREATE_EMPLOYEE, UPDATE_EMPLOYEE, DEACTIVATE_EMPLOYEE
- CREATE_TASK, UPDATE_TASK, COMPLETE_TASK, CANCEL_TASK
- CREATE_ERROR, UPDATE_ERROR
- IMPORT_START, IMPORT_COMPLETE, IMPORT_ROLLBACK
- EXPORT_REPORT, DOWNLOAD_FILE

---

## 5. Reporting & Metrics Tables

### 5.1 `productivity_summaries` — Daily/Weekly/Monthly Metrics

**Purpose:** Pre-calculated productivity metrics for reporting performance.

```sql
CREATE TABLE productivity_summaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly') NOT NULL,
    period_date DATE NOT NULL,
    tasks_picked INT UNSIGNED DEFAULT 0,
    tasks_packed INT UNSIGNED DEFAULT 0,
    total_tasks INT UNSIGNED DEFAULT 0,
    total_duration_seconds BIGINT UNSIGNED DEFAULT 0,
    avg_task_duration_seconds INT UNSIGNED,
    error_count INT UNSIGNED DEFAULT 0,
    error_rate_percent DECIMAL(5, 2) DEFAULT 0,
    rework_count INT UNSIGNED DEFAULT 0,
    quality_issue_count INT UNSIGNED DEFAULT 0,
    tasks_completed_on_time INT UNSIGNED DEFAULT 0,
    on_time_percent DECIMAL(5, 2) DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_id (employee_id),
    INDEX idx_period_type_date (period_type, period_date),
    INDEX idx_employee_period (employee_id, period_type, period_date),
    UNIQUE KEY uk_employee_period (employee_id, period_type, period_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose:**
- Avoid recalculating metrics on every report request
- Support caching at the application level
- Enable fast dashboard rendering

**Refresh Strategy:**
- Triggered nightly by cron job or manually
- Recalculates all periods (daily, weekly, monthly) from raw task data
- Updates `updated_at` timestamp for change tracking

---

## 6. Configuration Tables (Optional)

### 6.1 `system_settings` — Application Configuration

**Purpose:** Store system-wide configuration without code changes.

```sql
CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
    description TEXT,
    is_editable TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Settings:**
```sql
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_editable) VALUES
('session_timeout_minutes', '120', 'integer', 'Session inactivity timeout', 1),
('items_per_page', '50', 'integer', 'Default pagination size', 1),
('retention_days_audit_logs', '90', 'integer', 'Days to keep audit logs', 1),
('error_notification_email', 'admin@example.com', 'string', 'Email for critical errors', 1);
```

---

## 7. Data Integrity & Relationships

### 7.1 Relationship Diagram

```
users (1) ──────────────────→ (many) employees
          │
          ├────→ (many) audit_logs (created_by, updated_by)
          ├────→ (many) picking_tasks (created_by, updated_by)
          ├────→ (many) packing_tasks (created_by, updated_by)
          └────→ (many) imports (created_by)

warehouses (1) ────────→ (many) departments
                              │
                              └────→ (many) employees

employees (1) ──────→ (many) picking_tasks
                            └────→ (many) picking_errors

employees (1) ──────→ (many) packing_tasks
                            └────→ (many) packing_errors
```

### 7.2 Cascading Rules

| FK Relationship | ON DELETE | Rationale |
|-----------------|-----------|-----------|
| employee → picking_task | RESTRICT | Prevent deletion of employees with tasks |
| user → audit_logs | SET NULL | Keep audit history; nullify user reference |
| import → import_details | CASCADE | Delete details when import is deleted |
| picking_task → picking_errors | CASCADE | Remove errors when task is deleted |

---

## 8. Indexes for Performance

### Critical Indexes
```sql
-- Finding tasks by employee and date range (very common)
CREATE INDEX idx_employee_date ON picking_tasks(employee_id, created_at);
CREATE INDEX idx_employee_date ON packing_tasks(employee_id, created_at);

-- Filtering by status (common in dashboards)
CREATE INDEX idx_employee_status ON picking_tasks(employee_id, status);
CREATE INDEX idx_employee_status ON packing_tasks(employee_id, package_status);

-- Time-range queries for reporting
CREATE INDEX idx_created_at ON picking_tasks(created_at);
CREATE INDEX idx_created_at ON packing_tasks(created_at);

-- Foreign key lookups
CREATE INDEX idx_warehouse_id ON departments(warehouse_id);
CREATE INDEX idx_department_id ON employees(department_id);
```

---

## 9. Constraints & Validation Rules

### 9.1 NOT NULL Constraints

| Table | Columns | Reason |
|-------|---------|--------|
| users | email, password_hash, role | Required for authentication |
| employees | emp_id, full_name, warehouse_id | Core identifying info |
| picking_tasks | picklist_code, employee_id, sku_code | Task tracking requirement |
| packing_tasks | package_code, employee_id | Task identification |

### 9.2 Uniqueness Constraints

| Table | Column(s) | Business Requirement |
|-------|-----------|---------------------|
| users | email | One account per email |
| employees | emp_id | External ID must be unique |
| picking_tasks | picklist_code | Task ID from Unicommerce |
| packing_tasks | package_code | Package ID must be unique |
| productivity_summaries | (employee_id, period_type, period_date) | One summary per period |

### 9.3 Enumeration Values

**users.role:**
```
'admin' - Full system access
'supervisor' - Department-level access
'employee' - Read-only self-access
```

**Task Status Values:**
```
'created' - Just created
'pending' - Assigned, awaiting start
'in_progress' - Currently being worked on
'completed' - Finished
'cancelled' - Voided/cancelled
```

**Employment Status:**
```
'active' - Currently working
'inactive' - Not working
'on_leave' - Temporarily away
```

---

## 10. Migration Strategy

### Phase 1: Initial Schema (Week 1)
1. Create core tables (users, employees, warehouses, departments)
2. Create task tables (picking_tasks, packing_tasks)
3. Create error tracking tables
4. Create audit_logs table

### Phase 2: Import & Reporting (Week 2)
1. Create import tables
2. Create productivity_summaries table
3. Create system_settings table
4. Add indexes and constraints

### Phase 3: Future Enhancements
1. Add CCTV integration tables (if approved)
2. Add notification/alert tables
3. Add external API sync tables

---

## 11. Sample Data for Testing

See file: `WPMS_SAMPLE_DATA.sql` (to be created by Agent 1)

Includes:
- 3-5 test users (admin, supervisor, employees)
- 2 test warehouses with departments
- 10-20 employee records
- 50-100 picking/packing tasks at various statuses
- 10-15 error records
- 1-2 import records with details

---

## 12. Backup & Recovery

### Backup Strategy
- **Frequency:** Daily at 2 AM server time
- **Retention:** 30 days of full backups + 7 days of incremental
- **Location:** `/home/asmrcctvtechnologies/backups/wpms/` (outside web root)
- **Automation:** cPanel Backup wizard or mysqldump cron

### Restore Procedure
1. Stop application (place maintenance page)
2. Restore from latest backup
3. Run migrations if newer than backup
4. Verify data integrity
5. Resume application

---

**Schema Status:** Ready for Implementation  
**Next Step:** Agent 1 creates SQL files and seed data  
**Review Date:** October 25, 2026
