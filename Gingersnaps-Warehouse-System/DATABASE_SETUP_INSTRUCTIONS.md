# WPMS Database Setup Instructions
**Version:** 1.0  
**Date:** October 24, 2026  
**Status:** Ready for Production Deployment  
**Target Environment:** Z.com cPanel with MariaDB

---

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Database Creation](#database-creation)
3. [User & Permissions](#user--permissions)
4. [Migration Execution](#migration-execution)
5. [Seed Data Loading](#seed-data-loading)
6. [Verification & Testing](#verification--testing)
7. [Backup Procedures](#backup-procedures)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Required Software
- MariaDB 10.3+ or MySQL 5.7+
- Z.com cPanel access (or equivalent control panel)
- SSH client or Terminal access (optional but recommended)
- MySQL command-line client or GUI tool (e.g., Workbench, phpMyAdmin)

### Database Specifications
- **Database Name:** `yitrmcft_wpms` (or `wpms_prod` for standalone)
- **Character Set:** UTF-8 (utf8mb4)
- **Storage Engine:** InnoDB (for transactions and foreign keys)
- **Collation:** utf8mb4_unicode_ci

### Credentials to Have Ready
- cPanel username and password
- Database root password (if direct SQL access needed)
- Intended database username and password (we'll create a non-root user)

---

## Database Creation

### Step 1: Access Database Management

**Via cPanel (Recommended):**
1. Log in to Z.com cPanel at `https://asmrcctvtechnologies.com/cPanel`
2. Navigate to **Databases** → **MySQL Databases**
3. Look for the section to create a new database

**Via Command Line (Advanced):**
```bash
ssh user@asmrcctvtechnologies.com
# Enter password when prompted

# Log into MySQL
mysql -u root -p
# Enter root password when prompted
```

### Step 2: Create Database via cPanel

1. In the "Create New Database" section, enter:
   - **Database Name:** `yitrmcft_wpms`
   - Leave other fields at defaults
2. Click **Create Database**
3. You should see confirmation: "Database 'yitrmcft_wpms' has been created"

### Step 3: Create Database via Command Line

If using SSH/MySQL command line:

```sql
CREATE DATABASE yitrmcft_wpms 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Verify creation
SHOW DATABASES LIKE 'yitrmcft_wpms';
```

---

## User & Permissions

### Step 4: Create Limited Database User (Recommended)

**Via cPanel:**
1. In the same Database section, find "MySQL Users"
2. Create a new user:
   - **Username:** `yitrmcft_wpms_user` (or similar)
   - **Password:** Generate a strong password (20+ chars, mixed case, numbers, symbols)
   - Click **Create User**

3. Next, "Add User to Database":
   - Select the user just created
   - Select database `yitrmcft_wpms`
   - Check **ALL PRIVILEGES** (or select specific: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX)
   - Click **Add User to Database**

**Via Command Line:**

```sql
-- Create database user
CREATE USER 'yitrmcft_wpms_user'@'localhost' 
IDENTIFIED BY 'YourStrongPassword123!@#';

-- Grant necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES 
ON yitrmcft_wpms.* 
TO 'yitrmcft_wpms_user'@'localhost';

-- Flush privileges to take effect
FLUSH PRIVILEGES;

-- Verify privileges
SHOW GRANTS FOR 'yitrmcft_wpms_user'@'localhost';
```

### Privilege Explanation
- **SELECT:** Read data from tables
- **INSERT:** Add new records
- **UPDATE:** Modify existing records
- **DELETE:** Remove records
- **CREATE:** Create new tables (for migrations)
- **ALTER:** Modify table structure
- **INDEX:** Create indexes for performance
- **REFERENCES:** Create foreign keys

---

## Migration Execution

### Step 5: Execute Migration Files in Order

**Important:** Execute migration files in the exact order listed below. They depend on foreign key relationships.

#### Using phpMyAdmin (via cPanel)
1. Log into phpMyAdmin through cPanel
2. Select database `yitrmcft_wpms`
3. Click **Import** tab
4. Upload migration file (or copy-paste SQL)
5. Click **Go** to execute

#### Using Command Line
```bash
# Method 1: Direct execution with user credentials
mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < 01_create_users_table.sql
# Repeat for each file in order

# Method 2: Using root (if needed)
mysql -u root -p yitrmcft_wpms < 01_create_users_table.sql
```

#### Migration Execution Order
Execute these files in this exact sequence:

```sql
-- Phase 1: Core Tables (No Dependencies)
01_create_users_table.sql
02_create_warehouses_table.sql
03_create_departments_table.sql

-- Phase 2: Employee & Task Tables (Depend on core tables)
04_create_employees_table.sql
05_create_picking_tasks_table.sql
06_create_picking_errors_table.sql
07_create_packing_tasks_table.sql
08_create_packing_errors_table.sql

-- Phase 3: Import & Audit (Depend on core + tasks)
09_create_imports_table.sql
10_create_import_details_table.sql
11_create_audit_logs_table.sql

-- Phase 4: Configuration & Reporting
12_create_system_settings_table.sql
13_create_productivity_summaries_table.sql
```

### Step 6: Verify Migration Execution

After executing all migrations, verify tables were created:

```sql
-- List all tables
SHOW TABLES;

-- Expected output (13 tables):
-- audit_logs
-- departments
-- employees
-- import_details
-- imports
-- packing_errors
-- packing_tasks
-- picking_errors
-- picking_tasks
-- productivity_summaries
-- system_settings
-- users
-- warehouses
```

Or check table count:
```sql
SELECT COUNT(*) as table_count 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'yitrmcft_wpms';
-- Should return: 13
```

---

## Seed Data Loading

### Step 7: Load Sample Data (Testing/Development Only)

**IMPORTANT:** Only load seed data on development or testing environments. Do NOT load on production if you have real data.

**Via phpMyAdmin:**
1. Select database `yitrmcft_wpms`
2. Click **Import** tab
3. Upload file: `14_seed_data.sql`
4. Click **Go**

**Via Command Line:**
```bash
mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < 14_seed_data.sql
```

### Step 8: Verify Seed Data

After loading, check sample data was inserted:

```sql
-- Check users
SELECT COUNT(*) as user_count FROM users;
-- Expected: 5 users (1 admin, 2 supervisors, 2 employees)

-- Check employees
SELECT COUNT(*) as emp_count FROM employees;
-- Expected: 5 employees

-- Check tasks
SELECT COUNT(*) as pick_count FROM picking_tasks;
-- Expected: 5 picking tasks

SELECT COUNT(*) as pack_count FROM packing_tasks;
-- Expected: 5 packing tasks

-- Check imports
SELECT COUNT(*) as import_count FROM imports;
-- Expected: 1 import record
```

### Sample User Credentials

After seeding, test login with:

**Admin Account:**
- Email: `admin@asmrcctvtechnologies.com`
- Password: `admin123` (bcrypt hashed in database)
- Role: admin

**Supervisor Account:**
- Email: `supervisor1@asmrcctvtechnologies.com`
- Password: `admin123` (bcrypt hashed in database)
- Role: supervisor

**Employee Account:**
- Email: `employee1@asmrcctvtechnologies.com`
- Password: `admin123` (bcrypt hashed in database)
- Role: employee

**IMPORTANT:** Change all default passwords immediately in production.

---

## Verification & Testing

### Step 9: Run Health Checks

```sql
-- Check foreign key constraints are enabled
SHOW VARIABLES LIKE 'foreign_key_checks';
-- Should return: ON

-- Verify all tables exist
SELECT TABLE_NAME FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'yitrmcft_wpms'
ORDER BY TABLE_NAME;

-- Check primary keys
SELECT TABLE_NAME, COLUMN_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'yitrmcft_wpms' 
AND CONSTRAINT_NAME = 'PRIMARY'
ORDER BY TABLE_NAME;

-- Check indexes (should have composite indexes for performance)
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'yitrmcft_wpms'
AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- Test referential integrity (should return 0)
SELECT COUNT(*) FROM employees WHERE warehouse_id NOT IN (SELECT id FROM warehouses);
-- Should return: 0
```

### Step 10: Verify Data Integrity

```sql
-- Check for orphaned employee records
SELECT e.id, e.emp_id, e.warehouse_id
FROM employees e
LEFT JOIN warehouses w ON e.warehouse_id = w.id
WHERE w.id IS NULL;
-- Should return: empty result set

-- Check tasks have valid employee assignments
SELECT pt.id, pt.picklist_code, pt.employee_id
FROM picking_tasks pt
LEFT JOIN employees e ON pt.employee_id = e.id
WHERE e.id IS NULL;
-- Should return: empty result set

-- Verify all created_by references are valid
SELECT u.id, u.full_name, COUNT(e.id) as records_created
FROM users u
LEFT JOIN employees e ON u.id = e.created_by
WHERE u.is_active = 1
GROUP BY u.id
ORDER BY u.id;
```

---

## Backup Procedures

### Step 11: Set Up Automated Backups (cPanel)

1. Log into cPanel
2. Navigate to **Backup Wizard** or **Backups**
3. Configure:
   - **Backup Frequency:** Daily
   - **Backup Time:** 2:00 AM (off-peak hours)
   - **Retention:** 30 days
   - **Location:** Remote backup server (Z.com provides)

### Step 12: Manual Backup (Command Line)

Create a backup on demand:

```bash
# Full database backup with timestamp
mysqldump -u yitrmcft_wpms_user -p yitrmcft_wpms \
  > wpms_backup_$(date +%Y%m%d_%H%M%S).sql

# Backup with compression (recommended)
mysqldump -u yitrmcft_wpms_user -p yitrmcft_wpms | gzip \
  > wpms_backup_$(date +%Y%m%d).sql.gz

# Backup from root user
mysqldump -u root -p yitrmcft_wpms | gzip \
  > wpms_backup_full_$(date +%Y%m%d).sql.gz
```

### Backup Storage Location

Store backups outside the web root:
```
/home/asmrcctvtechnologies/backups/wpms/
```

### Restore from Backup

```bash
# Restore from uncompressed backup
mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < wpms_backup_20261024.sql

# Restore from compressed backup
gunzip < wpms_backup_20261024.sql.gz | mysql -u yitrmcft_wpms_user -p yitrmcft_wpms
```

---

## Troubleshooting

### Issue: "Access denied for user"

**Cause:** Wrong username, password, or host

**Solution:**
```bash
# Verify user exists in cPanel MySQL Users section
# Verify password is correct
# Ensure user is assigned to database (cPanel: Add User to Database)

# Test connection
mysql -u yitrmcft_wpms_user -h localhost -p
# Or for remote host
mysql -u yitrmcft_wpms_user -h z.com -p
```

### Issue: "1064: Syntax error near CREATE TABLE"

**Cause:** MySQL version too old or unsupported syntax

**Solution:**
```sql
-- Check MySQL version
SELECT VERSION();

-- If version < 5.7, remove JSON columns and ON UPDATE CURRENT_TIMESTAMP
-- Schema requires MySQL 5.7.8+ or MariaDB 10.3+
```

### Issue: "Foreign key constraint fails"

**Cause:** Tables created in wrong order or data integrity issue

**Solution:**
```sql
-- Check foreign key constraint status
SHOW CREATE TABLE picking_tasks\G

-- Verify referenced tables and IDs exist
SELECT * FROM employees WHERE id NOT IN (SELECT id FROM employees);

-- Disable FK checks temporarily (for repair)
SET FOREIGN_KEY_CHECKS=0;
-- [Fix data]
SET FOREIGN_KEY_CHECKS=1;
```

### Issue: "Disk space error"

**Cause:** Limited disk space on cPanel account

**Solution:**
- Contact Z.com support to increase disk space
- Or delete old backups not needed
- Monitor usage via cPanel File Manager

### Issue: "Character encoding mismatch"

**Cause:** Database not created with utf8mb4

**Solution:**
```sql
-- Convert existing database
ALTER DATABASE yitrmcft_wpms 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Convert all tables
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Repeat for all tables
```

### Issue: "Table lock timeout"

**Cause:** Long-running transaction blocking other operations

**Solution:**
```sql
-- List running processes
SHOW PROCESSLIST;

-- Kill long-running process (if needed)
KILL QUERY process_id;
KILL process_id;  -- Force kill

-- Check for locked tables
SHOW OPEN TABLES WHERE In_use > 0;
```

---

## Post-Setup Checklist

- [ ] Database `yitrmcft_wpms` created
- [ ] Database user `yitrmcft_wpms_user` created with limited privileges
- [ ] All 13 migration files executed in correct order
- [ ] All 13 tables created (verified via SHOW TABLES)
- [ ] Foreign key constraints verified
- [ ] Seed data loaded (if development environment)
- [ ] Sample user accounts tested and working
- [ ] Automated backups configured in cPanel
- [ ] Manual backup created and stored securely
- [ ] Data integrity health checks passed
- [ ] Application connected to database successfully
- [ ] First login audit log entry recorded

---

## Next Steps

1. **Application Configuration**
   - Update application `config.php` with database connection details:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'yitrmcft_wpms_user');
     define('DB_PASS', 'YourPassword');
     define('DB_NAME', 'yitrmcft_wpms');
     ```

2. **Application Deployment**
   - Follow deployment instructions in `WPMS_SYSTEM_ARCHITECTURE.md`
   - Deploy PHP application code to `/public_html/warehouse-productivity/`

3. **Change Default Passwords**
   - Update all sample user passwords in production
   - Use strong, unique passwords for each user

4. **Enable SSL/TLS**
   - Install SSL certificate on Z.com cPanel
   - Update `APP_URL` to use `https://`

5. **Configure Cron Jobs**
   - Set up automated productivity summary calculation
   - Schedule nightly audit log cleanup
   - Configure backup scripts

---

## Support & Maintenance

**For Issues:**
1. Check troubleshooting section above
2. Review application logs in `/var/log/`
3. Contact Z.com support with error details

**For Optimization:**
1. Monitor slow query log: `SET SESSION slow_query_log = 'ON'`
2. Use `EXPLAIN` to analyze query performance
3. Consider adding additional indexes based on usage patterns

**Database Maintenance (Monthly):**
```sql
-- Optimize all tables
OPTIMIZE TABLE users, employees, picking_tasks, packing_tasks, 
  picking_errors, packing_errors, imports, import_details, 
  audit_logs, productivity_summaries, departments, warehouses, 
  system_settings;

-- Check table integrity
CHECK TABLE users, employees, picking_tasks, packing_tasks;
REPAIR TABLE users;  -- If needed
```

---

**Document Version:** 1.0  
**Last Updated:** October 24, 2026  
**Created by:** Agent 1 (Database and Data Architecture)  
**Status:** Ready for Production
