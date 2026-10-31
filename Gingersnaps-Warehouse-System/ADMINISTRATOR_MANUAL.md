# WPMS Administrator Manual
**Version:** 1.0  
**Project:** Warehouse Productivity Monitoring System  
**Date:** October 24, 2026  
**Audience:** System Administrators, IT Staff, Managers  

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Administrator Responsibilities](#administrator-responsibilities)
3. [User & Role Management](#user--role-management)
4. [Employee Management](#employee-management)
5. [Task Assignment Workflows](#task-assignment-workflows)
6. [Import Procedures](#import-procedures)
7. [Dashboard Interpretation](#dashboard-interpretation)
8. [Report Generation](#report-generation)
9. [Backup & Recovery](#backup--recovery)
10. [Performance Management](#performance-management)
11. [Troubleshooting](#troubleshooting)
12. [Emergency Procedures](#emergency-procedures)

---

## System Overview

### What is WPMS?

The Warehouse Productivity Monitoring System (WPMS) is a PHP-based web application designed to:

- **Track warehouse operations:** Monitor picking, packing, and quality assurance tasks
- **Measure productivity:** Calculate efficiency metrics and performance benchmarks
- **Manage staffing:** Assign employees to tasks, departments, and facilities
- **Import data:** Bulk import picklists and task assignments from external systems
- **Audit activities:** Maintain immutable logs of all user actions
- **Report performance:** Generate productivity reports and insights

### System Architecture

```
┌─────────────────────────────────────────┐
│     Web Browser (HTTPS)                 │
└────────────────┬────────────────────────┘
                 │
┌─────────────────────────────────────────┐
│   Apache Web Server (mod_rewrite)       │
│   • Redirects clean URLs                │
│   • Enforces HTTPS                      │
│   • Blocks sensitive directories        │
└────────────────┬────────────────────────┘
                 │
┌─────────────────────────────────────────┐
│   PHP Application Layer                 │
│   • index.php (router)                  │
│   • Controllers (business logic)        │
│   • Repositories (database access)      │
└────────────────┬────────────────────────┘
                 │
┌─────────────────────────────────────────┐
│   MariaDB Database                      │
│   • yitrmcft_wpms database              │
│   • 13 tables (users, tasks, etc.)      │
│   • Foreign key constraints             │
│   • Automated audit logging             │
└─────────────────────────────────────────┘
```

### Key Features

- **Role-Based Access Control:** 5 roles with different permission levels
- **Real-time Tracking:** Monitor task completion and productivity in real time
- **Data Validation:** Enforce business rules at the database level
- **Audit Trail:** Every action logged with user, timestamp, and IP address
- **Backup Automation:** Daily automated backups with 30-day retention
- **Security Hardening:** HTTPS, secure sessions, input validation, SQL injection prevention

---

## Administrator Responsibilities

### Daily Tasks

1. **Monitor System Health**
   - Check error logs for issues
   - Monitor database performance
   - Verify backups completed successfully
   - Monitor disk space usage

2. **Review Audit Logs**
   - Check for suspicious activities
   - Verify all deletions/modifications
   - Investigate failed login attempts
   - Document security incidents

3. **Support Users**
   - Reset passwords for locked accounts
   - Troubleshoot access issues
   - Answer configuration questions
   - Document problems for future reference

### Weekly Tasks

1. **Performance Review**
   - Check disk space and database size
   - Review slow query logs
   - Analyze user login patterns
   - Monitor API response times

2. **Backup Verification**
   - Verify automated backups completed
   - Test backup restoration (monthly)
   - Confirm backup retention policy compliance
   - Document backup status

3. **Security Review**
   - Check for unauthorized access attempts
   - Review user privilege escalations
   - Verify SSL certificate expiration (>30 days)
   - Update security documentation

### Monthly Tasks

1. **Full System Audit**
   - Review all user accounts and roles
   - Check for inactive accounts
   - Verify compliance with security policies
   - Archive old audit logs

2. **Performance Analysis**
   - Calculate database growth rate
   - Project when archiving needed
   - Review most-used features
   - Identify optimization opportunities

3. **Capacity Planning**
   - Check available disk space
   - Estimate growth requirements
   - Plan for upgrades if needed
   - Document capacity metrics

4. **User & Password Audit**
   - Verify employee changes (new/departures)
   - Review user permissions
   - Ensure password policy compliance
   - Document access changes

---

## User & Role Management

### User Roles Overview

WPMS has 5 predefined roles, each with specific permissions:

| Role | Employees | Picking Tasks | Packing Tasks | Reports | Admin |
|------|-----------|---------------|---------------|---------|-------|
| **Admin** | Manage | Full Access | Full Access | All | Full |
| **Manager** | View Dept | View/Assign | View/Assign | Dept | Limited |
| **Supervisor** | View Dept | View/Complete | View/Complete | Dept | None |
| **Employee** | View Own | Complete Own | Complete Own | Own | None |
| **Warehouse** | View All | View Only | View Only | Summary | None |

### Creating New Users

**Via Web Interface (Requires Admin Access):**

1. Log in as Administrator
2. Navigate to: Settings → Users → Add New User
3. Enter user information:
   - **Email:** Must be unique (used for login)
   - **Full Name:** Real name for audit trails
   - **Department:** Select warehouse and department
   - **Role:** Choose from 5 predefined roles
   - **Initial Password:** System generates or you provide
4. Click "Create User"
5. User receives welcome email with temporary password
6. User must change password on first login

**Via Command Line (SSH/Direct Database):**

```bash
# Not recommended - use web interface for consistency
# If needed, contact database administrator
```

### Assigning & Changing Roles

**To Promote/Demote User:**

1. Navigate to: Settings → Users → Search for user
2. Click "Edit" on user record
3. Select new role from dropdown
4. Add reason in "Change Note" field
5. Click "Save Changes"
6. Action logged in audit trail with admin username

**Important Rules:**
- Only admins can create/edit other admins
- Role changes are immutable (logged, cannot be undone)
- User receives notification of role change
- All previous permissions revoked immediately

### Disabling User Accounts

**When Employee Departs:**

1. Navigate to: Settings → Users → Search for user
2. Click "More Options" → "Disable Account"
3. Enter termination date and reason
4. System will:
   - Log final audit entry
   - Revoke all access
   - Archive user data
   - Clear session tokens
5. Account marked as "Inactive" (not deleted for compliance)

**To Reactivate (If Rehired):**

1. Search for inactive user
2. Click "Reactivate Account"
3. Confirm department and role
4. System generates new temporary password
5. User notified and can log in

### Password Management

**Resetting User Password:**

1. Navigate to: Settings → Users → Search for user
2. Click "More Options" → "Reset Password"
3. System generates temporary password
4. User receives email with temporary password
5. User must change password on next login
6. Action logged with your username

**Password Security Policy:**
- Minimum 10 characters
- Must contain uppercase, lowercase, number, and symbol
- Cannot reuse last 5 passwords
- Must change every 90 days (configurable)
- Failed login attempts locked for 15 minutes (after 5 attempts)

---

## Employee Management

### Employee Records

Employee records contain warehouse staff information and link to user accounts.

**Key Fields:**
- **Employee ID:** Unique identifier (immutable)
- **Full Name:** Real name for identification
- **Warehouse:** Assigned facility
- **Department:** Assigned department
- **Position:** Job title
- **User Account:** Links to login credentials (optional)
- **Status:** Active/Inactive/On Leave
- **Hire Date:** For record keeping
- **Manager:** Supervisor name
- **Contact:** Phone number
- **Photo:** Optional profile picture

### Adding New Employees

**Via Web Interface:**

1. Navigate to: Employees → Add New Employee
2. Fill in required fields (all marked with *)
3. Select warehouse and department
4. Enter contact information
5. Optionally create user account (see User Management section)
6. Click "Save Employee"
7. Receive confirmation with employee ID

**Bulk Import:**

See "Import Procedures" section for adding multiple employees via CSV.

### Updating Employee Information

**Changing Department/Warehouse:**

1. Search for employee
2. Click "Edit"
3. Change department or warehouse
4. Click "Update"
5. System will:
   - Archive old assignments
   - Create new task assignments
   - Log change in audit trail
   - Notify affected supervisors

**Marking Employee On Leave:**

1. Search for employee
2. Click "Edit"
3. Set Status: "On Leave"
4. Enter date range
5. System will:
   - Prevent task assignment
   - Remove from active roster
   - Preserve all data

**Terminating Employee:**

1. Disable user account (see User Management)
2. Mark employee status as "Inactive"
3. System will:
   - Archive all records
   - Preserve for compliance (not deleted)
   - Log termination date
   - Generate termination report

---

## Task Assignment Workflows

### Understanding Task Types

**Picking Tasks:**
- Retrieve items from warehouse shelves
- Match to picklist items
- Scan barcodes (if available)
- Complete by moving items to staging area

**Packing Tasks:**
- Pack picked items into boxes/containers
- Generate packing slips
- Apply labels
- Complete by staging packed items

### Assigning Picking Tasks

**Manual Assignment:**

1. Navigate to: Tasks → Picking → Assign New
2. Select employee from list
3. Enter task details:
   - **Picklist Number:** (from system/ERP)
   - **Items:** Quantity and SKU
   - **Priority:** Normal/High/Urgent
   - **Due Date:** When items needed
   - **Notes:** Special instructions
4. Click "Assign"
5. Employee receives notification

**Bulk Assignment:**

1. Navigate to: Tasks → Picking → Bulk Assign
2. Select employees from list
3. Upload picklist CSV (see format below)
4. System validates data
5. Click "Assign All"
6. All employees notified simultaneously

**Picklist CSV Format:**
```
employee_id,picklist_code,item_sku,quantity,priority,due_date,notes
1,PL001,SKU123,50,normal,2026-10-31,Check for damage
2,PL002,SKU456,30,high,2026-10-30,RUSH ORDER
```

### Task Status Workflow

**Picking Task States:**
```
Assigned → In Progress → Completed → Verified → Archived
           │
           └─→ On Hold (if issues) → Resume
```

**Packing Task States:**
```
Assigned → In Progress → Completed → QA Check → Shipped
           │
           └─→ On Hold (quality issue) → Fix → Resume
```

### Monitoring Task Progress

**Dashboard View:**

1. Navigate to: Dashboard
2. See real-time status:
   - Total tasks assigned
   - In-progress tasks
   - Completed today
   - Overdue tasks
   - Errors logged

**Filtering & Searching:**

1. Use filter panel to show:
   - By employee
   - By department
   - By status
   - By date range
2. Export results to CSV for analysis

### Handling Task Issues

**If Employee Reports Issue:**

1. Employee marks task as "On Hold"
2. Logs error description
3. You receive notification

**To Resolve:**

1. Navigate to: Tasks → Issues
2. Review error description
3. Options:
   - **Reassign:** Move to different employee
   - **Split:** Reduce task quantity
   - **Cancel:** If no longer needed
4. Update task and notify employee

---

## Import Procedures

### Import Types

WPMS supports importing:
- **Picking Tasks:** From order systems or manual entry
- **Packing Tasks:** From warehouse management systems
- **Employees:** Bulk employee roster updates
- **System Settings:** Configuration changes

### File Format Requirements

**CSV Format (Recommended):**
- UTF-8 encoding
- Headers in first row
- One record per row
- Fields in correct order
- No extra blank rows

**Excel Format:**
- .xlsx files (Excel 2007+)
- First sheet used only
- Headers in first row
- One record per row

### Step-by-Step Import Process

**1. Prepare File:**
- Ensure data is clean and validated
- Remove duplicates
- Check date formats match system format (YYYY-MM-DD)
- Verify all required fields present
- File size limit: 50 MB

**2. Access Import:**
1. Navigate to: Tools → Import Data
2. Select import type: "Picking Tasks"
3. Click "Choose File"
4. Select your file
5. Click "Upload"

**3. Validate Data:**
- System checks for:
  - Required fields present
  - Data types correct
  - Foreign key references valid
  - No duplicate entries
  - Date formats valid
- Review errors/warnings
- Download error report if issues

**4. Confirm Import:**
- Review summary
  - Number of records
  - Expected impact
  - Conflicts detected
- Click "Proceed" to import
- Click "Cancel" to fix file and retry

**5. Post-Import Verification:**
- System generates import log
- Verify record counts match expectations
- Spot-check random records
- Notify affected employees
- Document import details

### Common Import Issues

**Issue: "Duplicate Entry" Error**

**Cause:** Record already exists (by unique key)  
**Solution:**
- Update existing record instead of importing
- Or change key field (e.g., different picklist code)
- Remove duplicate from import file

**Issue: "Foreign Key Constraint" Error**

**Cause:** Referenced record doesn't exist  
**Solution:**
- Employee must exist before assigning task
- Warehouse must exist before assigning department
- Verify IDs are correct in your file

**Issue: "Invalid Date Format" Error**

**Cause:** Date not in YYYY-MM-DD format  
**Solution:**
- Reformat all dates to YYYY-MM-DD
- Example: 10/31/26 → 2026-10-31
- Check Excel didn't auto-convert format

**Issue: "File Too Large" Error**

**Cause:** File exceeds 50 MB limit  
**Solution:**
- Split file into smaller chunks
- Remove unnecessary columns
- Archive/delete old records first

---

## Dashboard Interpretation

### Key Metrics

**Daily Metrics:**
- **Total Tasks:** Assigned + Completed + In Progress
- **Completion Rate:** (Completed / Assigned) × 100%
- **Average Task Time:** Total hours / Number of tasks
- **Error Rate:** (Errors / Completed tasks) × 100%

**Employee Metrics:**
- **Productivity Score:** Completion rate vs. target
- **Error Rate:** Errors per task
- **Average Shift:** Hours worked per day
- **Equipment Usage:** Time with scanner, packaging tools

**Department Metrics:**
- **Output:** Total items processed
- **Efficiency:** Items per hour
- **Quality:** Error-free completion rate
- **Trend:** Comparing to previous period

### Dashboard Widgets

**Real-Time Status:**
- Green: On target
- Yellow: Slightly behind
- Red: Significantly behind target

**Trend Charts:**
- Showing 30-day trends
- Compare to previous month
- Seasonal adjustments (if configured)

### Using Filters

1. Click "Filter" button (top right)
2. Select criteria:
   - Date range
   - Employee/Department
   - Warehouse
   - Task type
3. Click "Apply"
4. Dashboard updates with filtered data

### Exporting Data

1. Click "Export" button
2. Select format:
   - CSV (Excel-compatible)
   - PDF (formatted report)
   - JSON (for integrations)
3. System generates file
4. File downloads automatically

---

## Report Generation

### Built-in Reports

**1. Productivity Report**
- Shows: Tasks completed, errors, time spent
- Filter by: Employee, department, date range
- Output: Summary and detail views

**2. Error Analysis Report**
- Shows: Error types, frequency, severity
- Filter by: Employee, department, error type
- Output: Trends and patterns

**3. Employee Performance Report**
- Shows: Individual metrics and rankings
- Filter by: Department, date range
- Output: Leaderboards and comparisons

**4. Department Summary Report**
- Shows: Department output and efficiency
- Filter by: Warehouse, date range
- Output: Department comparisons

**5. Audit Trail Report**
- Shows: All user actions and changes
- Filter by: User, action type, date range
- Output: Compliance documentation

### Custom Report Builder (Phase 2)

Not available in Phase 1. Use standard reports or export data to Excel.

---

## Backup & Recovery

### Automated Backup Schedule

**Default Configuration:**
- **Frequency:** Daily at 2:00 AM (configurable)
- **Retention:** 7 daily backups + 4 weekly backups
- **Location:** /home/username/storage/backups/
- **Size:** ~10 MB per day (grows with data)

### Backup Contents

Each daily backup includes:
- Complete database dump (all 13 tables)
- Audit logs
- System settings
- Backup metadata (timestamp, size, hash)

### Manual Backup

**Via cPanel:**

1. Log in to Z.com cPanel
2. Navigate to: Backup Wizard
3. Select: Full Backup
4. Choose destination (home directory recommended)
5. Click "Backup"
6. System generates backup file

**Via Command Line:**

```bash
# SSH into server
ssh user@asmrcctvtechnologies.com

# Create manual backup
mysqldump -u yitrmcft_wpms_user -p yitrmcft_wpms > ~/backups/manual_$(date +%Y%m%d_%H%M%S).sql

# Compress to save space
gzip ~/backups/manual_*.sql
```

### Testing Backups (Monthly)

**To Verify Backup Integrity:**

1. Create test database: `yitrmcft_wpms_test`
2. Create test user: `test_user`
3. Restore backup to test database:
   ```bash
   mysql -u test_user -p yitrmcft_wpms_test < backup_file.sql
   ```
4. Run health check queries
5. Verify all tables exist
6. Check row counts match production
7. Drop test database

**Document Results:**
- Backup file size
- Restore time
- Verification status
- Any issues found

### Recovery Procedures

**In Case of Data Loss:**

1. **Identify Problem**
   - Determine when data was lost
   - Identify affected tables
   - Check audit logs for what changed

2. **Select Restore Point**
   - Choose backup file (daily or weekly)
   - Preferably right before data loss
   - Verify backup integrity first

3. **Perform Restore**
   ```bash
   # WARNING: This overwrites current database
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < backup_file.sql
   ```

4. **Verify Restoration**
   - Check data is correct
   - Verify all tables present
   - Run health check queries
   - Check row counts match backup

5. **Notify Users**
   - Inform of data loss and recovery
   - Confirm restored state
   - Request verification of critical records
   - Document incident

**Recovery Time Objectives (RTO):**
- **Detection:** <1 hour
- **Recovery:** <2 hours
- **Verification:** <1 hour
- **Total:** <4 hours

---

## Performance Management

### Monitoring Database Health

**Weekly Health Check:**

```sql
-- Check table sizes
SELECT 
    TABLE_NAME, 
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'yitrmcft_wpms'
ORDER BY (data_length + index_length) DESC;

-- Check for missing indexes
SELECT * FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'yitrmcft_wpms'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Check for full table scans
EXPLAIN SELECT * FROM picking_tasks WHERE employee_id = 1;
-- Should use idx_employee_id index
```

### Slow Query Logging

**Enable Slow Query Log (1 second threshold):**

1. Contact hosting provider
2. Request: Enable slow_query_log
3. Set: long_query_time = 1
4. They will enable logging

**Review Slow Queries:**
```sql
-- After logging is enabled
SHOW VARIABLES LIKE 'slow_query_log_file';
-- Then review log file
```

**Common Slow Queries:**
- Productivity summary calculations (complex joins)
- Audit log searches with date ranges
- Reports with large data sets
- Employee task history queries

**Optimization:**
- Archive old audit logs (>90 days)
- Add indexes for common filters
- Use date ranges instead of full table scans

### Disk Space Management

**Monthly Disk Check:**

```bash
# Check available space
df -h /home/

# Check WPMS directories
du -sh /home/*/public_html/warehouse-productivity/*
du -sh /home/*/storage/*
du -sh /home/*/backups/*
```

**If Space Low (<20% available):**

1. **Archive Old Audit Logs**
   ```sql
   -- Delete audit logs older than 90 days
   DELETE FROM audit_logs 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
   ```

2. **Archive Old Backups**
   - Delete backups older than 30 days
   - Keep monthly backups for compliance

3. **Request Storage Upgrade**
   - Contact Z.com support
   - Request additional storage
   - Estimate growth rate for sizing

---

## Troubleshooting

### Login Issues

**Problem: "Invalid Credentials" Even with Correct Password**

1. Check password caps lock
2. Verify account is not disabled
3. Check failed login attempts (may be locked)
4. Reset password (see User Management)

**Problem: "Account Locked" Message**

1. After 5 failed login attempts, account locks for 15 minutes
2. User must wait or admin can unlock
3. To unlock manually:
   - Navigate to Settings → Users
   - Search for user
   - Click "Unlock Account"

**Problem: Session Expires Too Quickly**

1. Default timeout: 30 minutes
2. Can be adjusted in .env file
3. Contact admin to increase if needed
4. Longer sessions = slightly less secure

### Permission Issues

**Problem: User Cannot Access Feature**

1. Verify user's role (Settings → Users)
2. Check role has permission for feature
3. Verify no permission restrictions in department
4. Confirm user not disabled

**Problem: "Access Denied" on Specific Page**

1. User may not have role for that page
2. Or user may not have permission for that data
3. Contact admin with:
   - Page name
   - Username
   - What they were trying to do
4. Admin will investigate and adjust permissions

### Data Issues

**Problem: Task Not Appearing in Dashboard**

1. Verify task is assigned (check Assignments page)
2. Check task assignment status
3. Try refreshing browser (F5)
4. Clear browser cache
5. Try different browser

**Problem: Import Failed with No Error Message**

1. Check file format is CSV or Excel
2. Verify file is not corrupted
3. Try uploading smaller portion of file
4. Contact admin with file details

**Problem: Incorrect Calculation in Report**

1. Verify source data is correct
2. Check date filters applied
3. Run same query on database directly
4. Document exact discrepancy
5. Report to admin with details

### Performance Issues

**Problem: Dashboard Slow to Load**

1. Check your internet connection
2. Try accessing at different time (if server load high)
3. Try different browser
4. Clear browser cache
5. Close other tabs/applications

**Problem: Export Times Out**

1. Reduce date range of export
2. Reduce number of filters
3. Export to CSV instead of PDF
4. Split into multiple smaller exports
5. Contact admin if persistent

---

## Emergency Procedures

### Critical System Failure

**If Application Completely Down:**

1. **Verify Issue**
   - Try different browser
   - Try different computer
   - Check internet connection
   - Try accessing at: https://asmrcctvtechnologies.com/warehouse-productivity/

2. **Gather Information**
   - Note exact time issue started
   - Screenshot error message (if any)
   - Document what you were doing
   - Check Z.com status page

3. **Contact Support**
   - Call Z.com support: [Support Number]
   - Email: [Support Email]
   - Provide: exact time, error message, steps to reproduce
   - Reference: WPMS deployment on asmrcctvtechnologies.com

4. **Emergency Workaround**
   - Use backup system if available
   - Paper forms for task assignments
   - Manual data entry after system recovery

### Database Corruption

**If Database Will Not Connect:**

1. **Do Not Attempt Repairs**
2. **Restore from Backup**
   ```bash
   # As database admin
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < backup_file.sql
   ```
3. **Verify Tables**
   ```sql
   SHOW TABLES;
   SELECT COUNT(*) FROM users;
   ```
4. **Check Integrity**
   ```sql
   CHECK TABLE users;
   CHECK TABLE picking_tasks;
   ```

### Security Incident

**If Suspicious Activity Detected:**

1. **Isolate System**
   - Block user account if compromised
   - Change admin passwords
   - Review access logs

2. **Investigate**
   - Pull audit logs for user
   - Check what data was accessed
   - Determine scope of breach
   - Document timeline

3. **Notify Management**
   - Report findings
   - Recommend corrective actions
   - Update security procedures
   - Schedule training if needed

4. **Recover**
   - Restore from backup if needed
   - Re-secure system
   - Implement additional controls
   - Monitor closely for 2 weeks

---

## Key Contact Information

**System Administrator:** ___________________  
**Email:** ___________________  
**Phone:** ___________________  
**Available:** 8 AM - 5 PM, Mon-Fri

**IT Manager:** ___________________  
**Email:** ___________________  
**Phone:** ___________________

**Z.com Support:**  
**Phone:** [To be provided]  
**Email:** [To be provided]  
**Portal:** https://z.com/support  
**Available:** 24/7 for emergencies

**Database Administrator (if different):**  
**Name:** ___________________  
**Email:** ___________________  
**Phone:** ___________________

---

## Document Version Control

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-10-24 | Initial release |

**Last Updated:** October 24, 2026  
**Next Review:** January 24, 2027  
**Maintained By:** Agent 6 (Deployment & Documentation)

