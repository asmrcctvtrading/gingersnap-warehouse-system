# WPMS Database Schema Documentation
**Version:** 1.0  
**Date:** October 24, 2026  
**Status:** Phase 1 Implementation Complete

---

## Table of Contents
1. [Entity-Relationship Diagram (ERD)](#entity-relationship-diagram)
2. [Table Reference Guide](#table-reference-guide)
3. [Field Descriptions & Data Types](#field-descriptions--data-types)
4. [Foreign Key Relationships](#foreign-key-relationships)
5. [Indexing Strategy](#indexing-strategy)
6. [Business Rules & Constraints](#business-rules--constraints)
7. [Common Queries & Use Cases](#common-queries--use-cases)

---

## Entity-Relationship Diagram

### Visual ERD (ASCII)

```
┌──────────────────────┐
│      USERS           │
│  (Authentication)    │
├──────────────────────┤
│ id (PK)              │
│ email UNIQUE         │
│ password_hash        │
│ full_name            │
│ role ENUM            │
│ is_active            │
│ last_login_at        │
│ created_by (FK→id)   │ ◄─────┐
│ updated_by (FK→id)   │ ◄─────┤
│ timestamps           │       │
└──────┬───────────────┘       │
       │                       │
       │ created_by/updated_by │
       └───────────────────────┘

       │
       │ (1 to many)
       ▼
┌──────────────────────────┐         ┌─────────────────────┐
│    WAREHOUSES            │ ◄─────► │   DEPARTMENTS       │
│  (Facility Locations)    │  (1:N)  │  (Org Structure)    │
├──────────────────────────┤         ├─────────────────────┤
│ id (PK)                  │         │ id (PK)             │
│ name UNIQUE              │         │ warehouse_id (FK)   │
│ location                 │         │ name                │
│ is_active                │         │ is_active           │
│ timestamps               │         │ created_by          │
│ created_by (FK→users)    │         │ timestamps          │
└──────┬───────────────────┘         └────────┬────────────┘
       │                                      │
       │ (1:N)                          (1:N) │
       ▼                                      ▼
┌────────────────────────────────────────────────────────┐
│            EMPLOYEES                                   │
│  (Warehouse Staff & Assignment)                        │
├────────────────────────────────────────────────────────┤
│ id (PK)                                                │
│ emp_id UNIQUE (External ID)                            │
│ user_id (FK→users, optional)                           │
│ full_name                                              │
│ email, phone                                           │
│ warehouse_id (FK→warehouses)                           │
│ department_id (FK→departments)                         │
│ employment_status ENUM                                 │
│ hire_date                                              │
│ is_active, notes                                       │
│ timestamps                                             │
└─────┬──────────────────────────────────────────────────┘
      │
      │ (1:N)
      ├─────────────────┬──────────────────────┐
      │                 │                      │
      ▼                 ▼                      ▼
┌───────────────┐  ┌──────────────┐  ┌────────────────────┐
│PICKING_TASKS  │  │PACKING_TASKS │  │PRODUCTIVITY_       │
│               │  │              │  │SUMMARIES           │
├───────────────┤  ├──────────────┤  ├────────────────────┤
│id (PK)        │  │id (PK)       │  │id (PK)             │
│picklist_code  │  │package_code  │  │employee_id (FK)    │
│employee_id(FK)│  │employee_id(FK)  │period_type ENUM    │
│order_number   │  │shipment_id   │  │period_date         │
│sku_code       │  │order_number  │  │tasks_picked        │
│quantity_*     │  │quantity_items│  │tasks_packed        │
│shelf_code     │  │package_status│  │error_count         │
│batch_code     │  │timestamps    │  │error_rate_percent  │
│status ENUM    │  │task_duration │  │rework_count        │
│task_*_time    │  │has_error     │  │on_time_percent     │
│has_error      │  │has_quality_* │  │updated_at          │
│task_duration  │  │notes         │  │UNIQUE:employee_id+ │
│exception_notes│  │created_by    │  │period_type+date    │
│timestamps     │  │timestamps    │  └────────────────────┘
│created_by     │  └──┬───────────┘
└────┬──────────┘     │
     │                │
     │ (1:N)     (1:N) │
     ▼                 ▼
┌──────────────────┐  ┌────────────────────┐
│PICKING_ERRORS    │  │PACKING_ERRORS      │
│(Pick Exceptions) │  │(Pack Quality)      │
├──────────────────┤  ├────────────────────┤
│id (PK)           │  │id (PK)             │
│picking_task_id(FK)  │packing_task_id(FK) │
│error_type        │  │error_type          │
│error_severity    │  │severity            │
│description       │  │quantity_affected   │
│resolution        │  │description         │
│requires_rework   │  │action_taken        │
│timestamps        │  │rework_required     │
│created_by        │  │timestamps          │
└──────────────────┘  │created_by          │
                      └────────────────────┘

┌──────────────────────────┐
│      IMPORTS             │
│  (Bulk Data Upload)      │
├──────────────────────────┤
│ id (PK)                  │
│ import_type              │
│ file_name, file_size     │
│ total_records            │
│ successful_records       │
│ failed_records           │
│ status ENUM              │
│ error_summary            │
│ timestamps               │
│ created_by (FK)          │
└─────┬────────────────────┘
      │
      │ (1:N)
      ▼
┌──────────────────────────┐
│  IMPORT_DETAILS          │
│  (Individual Records)    │
├──────────────────────────┤
│ id (PK)                  │
│ import_id (FK)           │
│ source_row_number        │
│ source_data JSON         │
│ target_table             │
│ target_record_id         │
│ status ENUM              │
│ error_message            │
│ created_at               │
└──────────────────────────┘

┌──────────────────────────┐
│   AUDIT_LOGS             │
│  (Activity Trail)        │
├──────────────────────────┤
│ id (PK) BIGINT           │
│ user_id (FK→users)       │
│ action VARCHAR           │
│ entity_type VARCHAR      │
│ entity_id INT            │
│ old_values JSON          │
│ new_values JSON          │
│ change_summary TEXT      │
│ ip_address, user_agent   │
│ created_at DATETIME      │
└──────────────────────────┘

┌──────────────────────────┐
│  SYSTEM_SETTINGS         │
│  (Configuration)         │
├──────────────────────────┤
│ id (PK)                  │
│ setting_key UNIQUE       │
│ setting_value            │
│ setting_type ENUM        │
│ description              │
│ is_editable              │
│ updated_at, updated_by   │
└──────────────────────────┘
```

### Relationship Legend
- **PK** = Primary Key (unique identifier)
- **FK** = Foreign Key (reference to another table)
- **1:N** = One-to-Many relationship
- **UNIQUE** = Values must be unique
- **ENUM** = Enumerated list of allowed values
- **JSON** = JSON data type for flexible storage

---

## Table Reference Guide

### 1. USERS Table
**Purpose:** Store application user accounts with role-based access control

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Unique user identifier |
| `email` | VARCHAR(255) | U | NOT NULL | Login identifier, must be unique |
| `password_hash` | VARCHAR(255) | | NOT NULL | bcrypt hash (minimum 60 chars) |
| `full_name` | VARCHAR(255) | | NOT NULL | User's display name |
| `role` | ENUM | | NOT NULL | admin, supervisor, or employee |
| `is_active` | TINYINT(1) | | DEFAULT 1 | 0=inactive, 1=active (soft delete) |
| `last_login_at` | DATETIME | | NULL | Timestamp of last login |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | Admin who created user |
| `updated_by` | INT UNSIGNED | FK | REFERENCES users(id) | Admin who last updated |

**Indexes:**
- `idx_email` - For login lookups
- `idx_role` - For permission checks
- `idx_is_active` - For filtering active users

---

### 2. WAREHOUSES Table
**Purpose:** Define warehouse locations and facilities

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Warehouse identifier |
| `name` | VARCHAR(255) | U | NOT NULL | Facility name (must be unique) |
| `location` | VARCHAR(500) | | | Geographic location/address |
| `is_active` | TINYINT(1) | | DEFAULT 1 | 0=inactive, 1=active |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update timestamp |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who created record |

**Indexes:**
- `idx_is_active` - For filtering active warehouses
- `uk_name` - Unique constraint on name

---

### 3. DEPARTMENTS Table
**Purpose:** Define departments within warehouses (Picking, Packing, QA, Receiving)

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Department identifier |
| `warehouse_id` | INT UNSIGNED | FK | REFERENCES warehouses(id) | Parent warehouse |
| `name` | VARCHAR(255) | | NOT NULL | Department name (e.g., "Picking", "Packing") |
| `is_active` | TINYINT(1) | | DEFAULT 1 | 0=inactive, 1=active |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update timestamp |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who created record |

**Indexes:**
- `idx_warehouse_id` - For finding departments by warehouse
- `idx_is_active` - For filtering active departments
- `uk_warehouse_name` - Unique pair (warehouse_id, name)

**Note:** Each warehouse can have multiple departments with unique names within that warehouse.

---

### 4. EMPLOYEES Table
**Purpose:** Store employee information and assignment details

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Employee record identifier |
| `emp_id` | VARCHAR(50) | U | NOT NULL | External employee ID (Unicommerce ID) |
| `user_id` | INT UNSIGNED | FK | REFERENCES users(id) | Link to app user account (optional) |
| `full_name` | VARCHAR(255) | | NOT NULL | Employee's name |
| `email` | VARCHAR(255) | | | Contact email |
| `phone` | VARCHAR(20) | | | Contact phone number |
| `warehouse_id` | INT UNSIGNED | FK | REFERENCES warehouses(id) | Assigned warehouse |
| `department_id` | INT UNSIGNED | FK | REFERENCES departments(id) | Assigned department |
| `employment_status` | ENUM | | NOT NULL | active, inactive, or on_leave |
| `hire_date` | DATE | | | Employment start date |
| `is_active` | TINYINT(1) | | DEFAULT 1 | 0=inactive (soft delete), 1=active |
| `notes` | TEXT | | | Additional notes about employee |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who created record |

**Indexes:**
- `idx_emp_id` - For finding by external ID
- `idx_warehouse_id` - For employees by warehouse
- `idx_department_id` - For employees by department
- `idx_is_active` - For filtering active employees
- `idx_employment_status` - For filtering by employment status

---

### 5. PICKING_TASKS Table
**Purpose:** Track individual picking operations and completion status

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Task identifier |
| `picklist_code` | VARCHAR(100) | U | NOT NULL | Unique code from Unicommerce |
| `employee_id` | INT UNSIGNED | FK | REFERENCES employees(id) | Assigned picker |
| `order_number` | VARCHAR(100) | | NOT NULL | Sales order ID |
| `sku_code` | VARCHAR(100) | | NOT NULL | Product SKU |
| `product_description` | VARCHAR(500) | | | Item description |
| `quantity_ordered` | INT UNSIGNED | | DEFAULT 1 | Quantity to pick |
| `quantity_picked` | INT UNSIGNED | | DEFAULT 0 | Actual quantity picked |
| `shelf_code` | VARCHAR(100) | | | Warehouse location/shelf |
| `batch_code` | VARCHAR(100) | | | Inventory batch identifier |
| `status` | ENUM | | NOT NULL | created, pending, in_progress, completed, cancelled |
| `task_start_time` | DATETIME | | NULL | When picker started task |
| `task_completion_time` | DATETIME | | NULL | When task was completed |
| `task_duration_seconds` | INT UNSIGNED | | NULL | Duration (completion_time - start_time) |
| `has_error` | TINYINT(1) | | DEFAULT 0 | Flag: 1 if error exists |
| `exception_notes` | TEXT | | | Notes on errors or issues |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who created task |
| `updated_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who last updated |

**Indexes:**
- `idx_employee_id` - For tasks by employee
- `idx_picklist_code` - For looking up specific task
- `idx_status` - For filtering by status
- `idx_created_at` - For time-range queries
- `idx_employee_status` - Composite: tasks by employee and status
- `idx_employee_date` - Composite: tasks by employee and date (for reporting)

---

### 6. PICKING_ERRORS Table
**Purpose:** Record errors and exceptions in picking operations

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Error record identifier |
| `picking_task_id` | INT UNSIGNED | FK | REFERENCES picking_tasks(id) | Related picking task |
| `error_type` | VARCHAR(100) | | NOT NULL | WRONG_QUANTITY, WRONG_PRODUCT, WRONG_LOCATION, etc. |
| `error_severity` | ENUM | | NOT NULL | low, medium, high, or critical |
| `description` | TEXT | | | Detailed error description |
| `resolution` | TEXT | | | How the error was resolved |
| `requires_rework` | TINYINT(1) | | DEFAULT 0 | 1 if task needs rework |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who logged error |

**Indexes:**
- `idx_picking_task_id` - For errors on specific task
- `idx_error_type` - For filtering by error type
- `idx_error_severity` - For filtering by severity
- `idx_requires_rework` - For rework queue

---

### 7. PACKING_TASKS Table
**Purpose:** Track packing operations and package completion

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Task identifier |
| `package_code` | VARCHAR(100) | U | NOT NULL | Unique package identifier |
| `employee_id` | INT UNSIGNED | FK | REFERENCES employees(id) | Assigned packer |
| `shipment_id` | VARCHAR(100) | | NOT NULL | Shipment reference |
| `order_number` | VARCHAR(100) | | NOT NULL | Sales order ID |
| `quantity_items` | INT UNSIGNED | | DEFAULT 1 | Number of items in package |
| `package_status` | ENUM | | NOT NULL | created, pending, in_progress, completed, cancelled |
| `task_start_time` | DATETIME | | NULL | When packer started task |
| `task_completion_time` | DATETIME | | NULL | When task was completed |
| `task_duration_seconds` | INT UNSIGNED | | NULL | Duration in seconds |
| `has_error` | TINYINT(1) | | DEFAULT 0 | Flag: 1 if error exists |
| `has_quality_issue` | TINYINT(1) | | DEFAULT 0 | Flag: 1 if quality issue exists |
| `notes` | TEXT | | | Notes on packaging |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who created task |
| `updated_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who last updated |

**Indexes:**
- `idx_employee_id` - For tasks by employee
- `idx_package_code` - For looking up specific package
- `idx_package_status` - For filtering by status
- `idx_created_at` - For time-range queries
- `idx_employee_status` - Composite: tasks by employee and status
- `idx_employee_date` - Composite: for employee reporting

---

### 8. PACKING_ERRORS Table
**Purpose:** Record quality defects and discrepancies in packing

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Error record identifier |
| `packing_task_id` | INT UNSIGNED | FK | REFERENCES packing_tasks(id) | Related packing task |
| `error_type` | VARCHAR(100) | | NOT NULL | DAMAGED_PACKAGE, INCORRECT_ITEM, MISSING_ITEM, etc. |
| `severity` | ENUM | | NOT NULL | low, medium, high, or critical |
| `quantity_affected` | INT UNSIGNED | | | Number of items affected |
| `description` | TEXT | | | Detailed error description |
| `action_taken` | TEXT | | | Corrective action performed |
| `rework_required` | TINYINT(1) | | DEFAULT 0 | 1 if rework needed |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who logged error |

**Indexes:**
- `idx_packing_task_id` - For errors on specific task
- `idx_error_type` - For filtering by error type
- `idx_rework_required` - For rework queue

---

### 9. IMPORTS Table
**Purpose:** Track all bulk data imports from external sources

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Import record identifier |
| `import_type` | VARCHAR(50) | | NOT NULL | PICKLIST_IMPORT, EMPLOYEE_IMPORT, INVENTORY_IMPORT |
| `file_name` | VARCHAR(255) | | NOT NULL | Uploaded file name |
| `file_size` | INT UNSIGNED | | | File size in bytes |
| `total_records` | INT UNSIGNED | | | Total rows in file |
| `successful_records` | INT UNSIGNED | | | Records successfully imported |
| `failed_records` | INT UNSIGNED | | | Records that failed validation |
| `status` | ENUM | | NOT NULL | pending, processing, completed, failed, cancelled |
| `error_summary` | TEXT | | | Summary of errors encountered |
| `import_started_at` | DATETIME | | | When import began |
| `import_completed_at` | DATETIME | | | When import finished |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last update time |
| `created_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who initiated import |

**Indexes:**
- `idx_import_type` - For filtering by type
- `idx_status` - For filtering by status
- `idx_created_at` - For time-range queries

---

### 10. IMPORT_DETAILS Table
**Purpose:** Map and track individual records within an import

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Detail record identifier |
| `import_id` | INT UNSIGNED | FK | REFERENCES imports(id) | Parent import record |
| `source_row_number` | INT UNSIGNED | | NOT NULL | Row number in source file |
| `source_data` | JSON | | NOT NULL | Original data from file (flexible structure) |
| `target_table` | VARCHAR(100) | | | Table where data was imported |
| `target_record_id` | INT UNSIGNED | | | ID of created/updated record |
| `status` | ENUM | | NOT NULL | success, duplicate, validation_error, skipped |
| `error_message` | TEXT | | | Validation error message if failed |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Indexes:**
- `idx_import_id` - For details by import
- `idx_status` - For filtering by status

**Note:** JSON field allows flexible storage of different import formats.

---

### 11. AUDIT_LOGS Table
**Purpose:** Immutable audit trail for compliance and activity tracking

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | BIGINT UNSIGNED | PK | AUTO_INCREMENT | Log entry identifier |
| `user_id` | INT UNSIGNED | FK | REFERENCES users(id) | User who performed action |
| `action` | VARCHAR(100) | | NOT NULL | CREATE, UPDATE, DELETE, LOGIN, IMPORT, etc. |
| `entity_type` | VARCHAR(100) | | NOT NULL | picking_task, employee, import, etc. |
| `entity_id` | INT UNSIGNED | | | ID of affected record |
| `old_values` | JSON | | NULL | Values before update |
| `new_values` | JSON | | NULL | Values after update/create |
| `change_summary` | TEXT | | | Human-readable summary |
| `ip_address` | VARCHAR(45) | | | Client IP address |
| `user_agent` | VARCHAR(500) | | | Browser/client information |
| `created_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | When action occurred |

**Indexes:**
- `idx_user_id` - For actions by user
- `idx_action` - For filtering by action type
- `idx_entity_type` - For filtering by entity
- `idx_created_at` - For time-range queries
- `idx_user_action` - Composite: actions by user and type
- `idx_entity` - Composite: changes to specific record

**Note:** BIGINT used for future growth (millions of log entries). Append-only table (no updates/deletes).

---

### 12. SYSTEM_SETTINGS Table
**Purpose:** Store application configuration without code changes

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Setting identifier |
| `setting_key` | VARCHAR(100) | U | NOT NULL | Configuration key (e.g., "session_timeout") |
| `setting_value` | TEXT | | | Configuration value |
| `setting_type` | ENUM | | NOT NULL | string, integer, boolean, json |
| `description` | TEXT | | | Meaning and usage notes |
| `is_editable` | TINYINT(1) | | DEFAULT 0 | 1 if admin can change |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last change time |
| `updated_by` | INT UNSIGNED | FK | REFERENCES users(id) | User who made change |

**Indexes:**
- `idx_setting_key` - For looking up specific setting

**Example Settings:**
- `session_timeout_minutes`: 120
- `items_per_page`: 50
- `enable_barcode_scanning`: true
- `task_status_workflow`: ["created","pending","in_progress","completed"]

---

### 13. PRODUCTIVITY_SUMMARIES Table
**Purpose:** Pre-calculated metrics for reporting and dashboards

| Column | Type | Key | Constraints | Description |
|--------|------|-----|-------------|-------------|
| `id` | INT UNSIGNED | PK | AUTO_INCREMENT | Summary record identifier |
| `employee_id` | INT UNSIGNED | FK | REFERENCES employees(id) | Employee being summarized |
| `period_type` | ENUM | | NOT NULL | daily, weekly, or monthly |
| `period_date` | DATE | | NOT NULL | Date of period |
| `tasks_picked` | INT UNSIGNED | | DEFAULT 0 | Number of picking tasks completed |
| `tasks_packed` | INT UNSIGNED | | DEFAULT 0 | Number of packing tasks completed |
| `total_tasks` | INT UNSIGNED | | DEFAULT 0 | Total tasks (picked + packed) |
| `total_duration_seconds` | BIGINT UNSIGNED | | DEFAULT 0 | Total time spent (all tasks) |
| `avg_task_duration_seconds` | INT UNSIGNED | | | Average time per task |
| `error_count` | INT UNSIGNED | | DEFAULT 0 | Number of errors logged |
| `error_rate_percent` | DECIMAL(5,2) | | DEFAULT 0 | Error count / total tasks * 100 |
| `rework_count` | INT UNSIGNED | | DEFAULT 0 | Tasks requiring rework |
| `quality_issue_count` | INT UNSIGNED | | DEFAULT 0 | Quality issues found |
| `tasks_completed_on_time` | INT UNSIGNED | | DEFAULT 0 | Tasks meeting SLA |
| `on_time_percent` | DECIMAL(5,2) | | DEFAULT 0 | On-time tasks / total tasks * 100 |
| `updated_at` | DATETIME | | DEFAULT CURRENT_TIMESTAMP | Last calculation time |

**Indexes:**
- `idx_employee_id` - For employee summaries
- `idx_period_type_date` - For finding by period
- `idx_employee_period` - Composite: employee's period summaries
- `uk_employee_period` - Unique: one summary per employee/period/date

**Note:** Pre-calculated nightly by cron job. Allows fast dashboard rendering without live aggregation.

---

## Foreign Key Relationships

### Cascade Rules

| FK Relationship | ON DELETE | ON UPDATE | Rationale |
|-----------------|-----------|-----------|-----------|
| users → created_by/updated_by | SET NULL | RESTRICT | Keep audit history; nullify user ref |
| warehouses ← created_by | RESTRICT | RESTRICT | Prevent warehouse deletion if used |
| departments ← warehouse_id | CASCADE | RESTRICT | Delete depts if warehouse deleted |
| departments ← created_by | RESTRICT | RESTRICT | Prevent user deletion if dept creator |
| employees ← warehouse_id | RESTRICT | RESTRICT | Prevent warehouse deletion if has employees |
| employees ← department_id | RESTRICT | RESTRICT | Prevent dept deletion if has employees |
| employees ← user_id | SET NULL | RESTRICT | Deactivate user but keep employee record |
| picking_tasks ← employee_id | RESTRICT | RESTRICT | Prevent employee deletion if has tasks |
| picking_tasks ← created_by | RESTRICT | RESTRICT | Prevent user deletion if created tasks |
| picking_errors ← picking_task_id | CASCADE | RESTRICT | Delete errors when task deleted |
| packing_tasks ← employee_id | RESTRICT | RESTRICT | Prevent employee deletion if has tasks |
| packing_errors ← packing_task_id | CASCADE | RESTRICT | Delete errors when task deleted |
| imports ← created_by | RESTRICT | RESTRICT | Prevent user deletion if initiated import |
| import_details ← import_id | CASCADE | RESTRICT | Delete details when import deleted |
| audit_logs ← user_id | SET NULL | RESTRICT | Keep logs; nullify user ref |
| productivity_summaries ← employee_id | CASCADE | RESTRICT | Delete summaries if employee deleted |
| system_settings ← updated_by | SET NULL | RESTRICT | Nullify user ref but keep setting |

---

## Indexing Strategy

### Index Analysis

**Primary Key Indexes (Automatic)**
All tables have automatic index on `id` (primary key).

**Foreign Key Indexes**
- Automatically created in InnoDB
- Improve JOIN performance
- Help maintain referential integrity

**Performance Indexes**

#### Most Critical (High Query Volume)
```sql
-- Employee filtering by warehouse and department
CREATE INDEX idx_warehouse_id ON employees(warehouse_id);
CREATE INDEX idx_department_id ON employees(department_id);

-- Task lookup by employee (common dashboard query)
CREATE INDEX idx_employee_id ON picking_tasks(employee_id);
CREATE INDEX idx_employee_id ON packing_tasks(employee_id);

-- Task status filtering
CREATE INDEX idx_status ON picking_tasks(status);
CREATE INDEX idx_package_status ON packing_tasks(package_status);

-- Date range queries for reports
CREATE INDEX idx_employee_date ON picking_tasks(employee_id, created_at);
CREATE INDEX idx_employee_date ON packing_tasks(employee_id, created_at);

-- Composite index for employee + status
CREATE INDEX idx_employee_status ON picking_tasks(employee_id, status);
CREATE INDEX idx_employee_status ON packing_tasks(employee_id, package_status);
```

#### Secondary (Medium Query Volume)
```sql
-- Login and user lookups
CREATE INDEX idx_email ON users(email);

-- Filter by active status
CREATE INDEX idx_is_active ON users(is_active);
CREATE INDEX idx_is_active ON employees(is_active);

-- Employee status queries
CREATE INDEX idx_employment_status ON employees(employment_status);

-- Error tracking and reporting
CREATE INDEX idx_error_type ON picking_errors(error_type);
CREATE INDEX idx_error_severity ON picking_errors(error_severity);

-- Rework queues
CREATE INDEX idx_requires_rework ON picking_errors(requires_rework);
CREATE INDEX idx_rework_required ON packing_errors(rework_required);

-- Import status tracking
CREATE INDEX idx_import_type ON imports(import_type);
CREATE INDEX idx_status ON imports(status);
```

#### Reporting & Analytics
```sql
-- Productivity metrics by period
CREATE INDEX idx_period_type_date ON productivity_summaries(period_type, period_date);

-- Audit log queries
CREATE INDEX idx_action ON audit_logs(action);
CREATE INDEX idx_entity_type ON audit_logs(entity_type);
CREATE INDEX idx_created_at ON audit_logs(created_at);
```

### Index Maintenance

**Check Index Usage (Monthly)**
```sql
-- Find unused indexes
SELECT OBJECT_SCHEMA, OBJECT_NAME, COUNT_READ, COUNT_WRITE, COUNT_DELETE, COUNT_UPDATE
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE OBJECT_SCHEMA = 'yitrmcft_wpms'
ORDER BY COUNT_READ DESC;

-- Drop unused indexes
DROP INDEX idx_unused ON table_name;
```

**Rebuild Fragmented Indexes**
```sql
-- Analyze and optimize
ANALYZE TABLE picking_tasks;
OPTIMIZE TABLE picking_tasks;

-- Or rebuild specific index
ALTER TABLE picking_tasks ENGINE=InnoDB;
```

---

## Business Rules & Constraints

### Data Validation Rules (Database Level)

| Rule | Enforcement | Impact |
|------|-------------|--------|
| Email must be unique | UNIQUE constraint on users.email | Prevent duplicate accounts |
| Employee ID must be unique | UNIQUE constraint on employees.emp_id | Prevent duplicate employee records |
| Picklist code must be unique | UNIQUE constraint on picking_tasks.picklist_code | One task per picklist |
| Package code must be unique | UNIQUE constraint on packing_tasks.package_code | One task per package |
| Setting keys must be unique | UNIQUE constraint on system_settings.setting_key | One value per config key |
| One summary per period | UNIQUE (employee_id, period_type, period_date) on productivity_summaries | No duplicate summaries |
| Warehouse name must be unique | UNIQUE constraint on warehouses.name | No duplicate warehouse names |
| Department name unique per warehouse | UNIQUE (warehouse_id, name) on departments | No duplicate dept names in same warehouse |
| Role must be from enum | ENUM ('admin', 'supervisor', 'employee') | Restrict to 3 roles |
| Task status is constrained | ENUM for status columns | Only valid statuses allowed |
| Employment status constrained | ENUM on employees.employment_status | Only valid statuses allowed |
| NOT NULL constraints enforce required fields | NOT NULL on critical columns | Prevent incomplete records |

### Status Workflow Rules

**Task Status Transitions**
```
CREATED → PENDING → IN_PROGRESS → COMPLETED
  ↓
CANCELLED (from any state)
```

Enforced via application logic (not database trigger in Phase 1).

**Import Status Flow**
```
PENDING → PROCESSING → COMPLETED
  ↓               ↓
  FAILED    or CANCELLED
```

### Data Relationship Rules

1. **Employees must belong to valid warehouse and department**
   - Cannot create employee without warehouse_id
   - Cannot assign to non-existent department
   - Department must belong to employee's warehouse

2. **Tasks must have assigned employee**
   - picking_tasks.employee_id must reference valid employee
   - packing_tasks.employee_id must reference valid employee
   - Employee must be in active status

3. **Errors belong to tasks**
   - picking_errors.picking_task_id must reference valid task
   - packing_errors.packing_task_id must reference valid task
   - Cascading delete removes errors with task

4. **Import details reference import**
   - import_details.import_id must reference valid import
   - Cascading delete removes details with import

5. **Audit logs reference users (optional)**
   - audit_logs.user_id may be NULL (for system actions)
   - SET NULL maintains audit history even if user deleted

6. **Productivity summaries link to employees**
   - Cascading delete removes summaries if employee deleted
   - Cannot create summary without valid employee_id

### Date & Time Rules

1. **Timestamps always in UTC**
   - created_at, updated_at in DATETIME
   - NO timezone storage (convert in application)

2. **Task timing constraints**
   - task_start_time must be ≤ task_completion_time
   - task_duration_seconds = DATEDIFF(SECOND, start, completion)
   - NULL until task started/completed

3. **Hire date must be in past**
   - employees.hire_date should be ≤ TODAY()
   - Validated at application level

4. **Login tracking**
   - users.last_login_at updated on successful login
   - NULL for new accounts that haven't logged in

---

## Common Queries & Use Cases

### Use Case 1: Find All Tasks for an Employee Today

```sql
SELECT 
    pt.id,
    pt.picklist_code,
    pt.order_number,
    pt.sku_code,
    pt.status,
    pt.task_start_time,
    pt.task_completion_time,
    pt.task_duration_seconds
FROM picking_tasks pt
WHERE pt.employee_id = ? 
  AND DATE(pt.created_at) = CURDATE()
ORDER BY pt.task_start_time;

-- Uses: idx_employee_date
```

### Use Case 2: Calculate Employee Productivity Metrics (Daily)

```sql
SELECT 
    e.id,
    e.emp_id,
    e.full_name,
    COUNT(DISTINCT pt.id) as tasks_picked,
    COUNT(DISTINCT pack.id) as tasks_packed,
    COUNT(DISTINCT pt.id) + COUNT(DISTINCT pack.id) as total_tasks,
    SUM(pt.task_duration_seconds) + SUM(COALESCE(pack.task_duration_seconds, 0)) as total_seconds,
    ROUND(AVG(COALESCE(pt.task_duration_seconds, pack.task_duration_seconds)), 0) as avg_duration,
    COUNT(DISTINCT CASE WHEN pt.has_error = 1 THEN pt.id END) as error_count,
    ROUND(
        COUNT(DISTINCT CASE WHEN pt.has_error = 1 THEN pt.id END) * 100.0 /
        NULLIF(COUNT(DISTINCT pt.id) + COUNT(DISTINCT pack.id), 0),
        2
    ) as error_rate_percent
FROM employees e
LEFT JOIN picking_tasks pt ON e.id = pt.employee_id 
    AND DATE(pt.created_at) = CURDATE()
    AND pt.status = 'completed'
LEFT JOIN packing_tasks pack ON e.id = pack.employee_id 
    AND DATE(pack.created_at) = CURDATE()
    AND pack.package_status = 'completed'
WHERE e.is_active = 1
GROUP BY e.id;
```

### Use Case 3: Find All Active Errors in Picking Queue

```sql
SELECT 
    pt.id,
    pt.picklist_code,
    e.emp_id,
    e.full_name,
    pe.error_type,
    pe.error_severity,
    pe.description,
    pe.requires_rework,
    pt.created_at
FROM picking_errors pe
JOIN picking_tasks pt ON pe.picking_task_id = pt.id
JOIN employees e ON pt.employee_id = e.id
WHERE pe.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND pe.requires_rework = 1
ORDER BY pe.error_severity DESC, pt.created_at ASC;

-- Uses: idx_picking_task_id, idx_requires_rework
```

### Use Case 4: Generate Employee Monthly Report

```sql
SELECT 
    ps.period_date,
    ps.period_type,
    e.emp_id,
    e.full_name,
    d.name as department,
    ps.tasks_picked,
    ps.tasks_packed,
    ps.total_tasks,
    ROUND(ps.total_duration_seconds / 3600, 2) as total_hours,
    ps.avg_task_duration_seconds,
    ps.error_count,
    ps.error_rate_percent,
    ps.rework_count,
    ps.on_time_percent
FROM productivity_summaries ps
JOIN employees e ON ps.employee_id = e.id
JOIN departments d ON e.department_id = d.id
WHERE ps.period_type = 'monthly'
  AND ps.period_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
ORDER BY ps.period_date DESC, e.full_name;

-- Uses: idx_employee_period, idx_period_type_date
```

### Use Case 5: Audit Activity for Compliance

```sql
SELECT 
    al.created_at,
    u.full_name as user_name,
    al.action,
    al.entity_type,
    al.entity_id,
    al.change_summary,
    al.ip_address
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.id
WHERE al.action IN ('CREATE_TASK', 'UPDATE_TASK', 'COMPLETE_TASK')
  AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY al.created_at DESC;

-- Uses: idx_action, idx_created_at
```

### Use Case 6: Import Progress Tracking

```sql
SELECT 
    i.id,
    i.import_type,
    i.file_name,
    i.status,
    i.total_records,
    i.successful_records,
    i.failed_records,
    ROUND(i.successful_records * 100.0 / NULLIF(i.total_records, 0), 2) as success_rate_percent,
    TIMESTAMPDIFF(MINUTE, i.import_started_at, i.import_completed_at) as duration_minutes,
    u.full_name as created_by
FROM imports i
LEFT JOIN users u ON i.created_by = u.id
WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY i.created_at DESC;

-- Uses: idx_status, idx_created_at
```

---

**Schema Version:** 1.0  
**Last Updated:** October 24, 2026  
**Status:** Ready for Production  
**Next Review:** After Phase 2 (4 weeks)
