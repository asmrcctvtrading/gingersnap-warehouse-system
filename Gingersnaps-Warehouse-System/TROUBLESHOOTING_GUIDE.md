# WPMS Troubleshooting Guide
**Version:** 1.0  
**Date:** October 24, 2026  
**Audience:** Administrators, IT Staff, Support Team  

---

## Common Issues & Solutions

### Category 1: Login & Authentication Issues

#### Issue: "Invalid Email or Password" Error

**Symptoms:**
- Cannot log in with correct credentials
- Same error every attempt
- Other users can log in fine

**Possible Causes:**
1. Caps lock is ON
2. Typo in email or password
3. Password reset needed
4. Account disabled or deleted

**Solutions (Try in Order):**

1. **Check Caps Lock**
   - Verify caps lock is OFF
   - Try typing password again

2. **Verify Email**
   - Confirm you're using correct email
   - Check for extra spaces before/after

3. **Reset Password**
   - Click "Forgot Password" on login page
   - Enter email address
   - Check email for reset link (may take 5 minutes)
   - Click link in email
   - Create new password
   - Try logging in with new password

4. **Check Account Status**
   - As administrator, go to Settings → Users
   - Search for user email
   - Verify account is not "Inactive" or "Disabled"
   - If disabled, ask admin to reactivate
   - If deleted, create new account

5. **Check Browser Cache**
   - Clear browser cookies
   - Clear cached data
   - Try different browser
   - Try private/incognito window

---

#### Issue: "Account Locked" Error

**Symptoms:**
- See message: "Account locked due to failed login attempts"
- Cannot log in even with correct password
- Message says: "Please try again in 15 minutes"

**Cause:** Account automatically locked after 5 failed login attempts (security measure)

**Solutions:**

1. **Wait 15 Minutes**
   - Account automatically unlocks
   - Try logging in again
   - Use correct password this time

2. **Have Admin Unlock (If Can't Wait)**
   - Administrator can unlock immediately
   - Go to Settings → Users
   - Search for user
   - Click "More Options" → "Unlock Account"
   - User can try logging in again

---

#### Issue: "Session Expired" Message

**Symptoms:**
- Logged in, working normally
- After 30 minutes of inactivity, get logout message
- Have to log in again
- Work may be lost if not saved

**Cause:** Session timeout for security (default 30 minutes)

**Solutions:**

1. **Adjust Timeout (Administrator)**
   - Edit .env file
   - Change: `SESSION_TIMEOUT=1800` (30 min)
   - To: `SESSION_TIMEOUT=3600` (60 min)
   - Save and reload application
   - Or adjust to your needs (in seconds)

2. **Stay Active**
   - Move around application every 20-25 minutes
   - Each page load resets timeout
   - Don't leave computer idle for 30+ minutes

3. **Save Work Frequently**
   - Click "Save" before taking breaks
   - Don't rely on auto-save

---

### Category 2: Application Not Loading

#### Issue: Blank Page or "Connection Refused"

**Symptoms:**
- Browser shows blank page
- Or shows: "Connection Refused" error
- Or shows: "Unable to connect to server"
- URL in browser: https://asmrcctvtechnologies.com/warehouse-productivity/

**Possible Causes:**
1. Application files not uploaded
2. Web server not running
3. PHP not configured properly
4. .htaccess blocking access
5. Wrong file permissions
6. Database unreachable

**Solutions (Try in Order):**

1. **Check Website is Up**
   ```bash
   # From command line on server
   curl -I https://asmrcctvtechnologies.com/warehouse-productivity/
   # Should return: HTTP/1.1 200 OK
   # If not, server may be down
   ```

2. **Verify Files Uploaded**
   ```bash
   # Via SFTP/SSH
   ls -la /public_html/warehouse-productivity/
   # Should show: public/ src/ config/ .htaccess
   # If missing, upload files again
   ```

3. **Check PHP is Working**
   ```bash
   # Create test.php in public_html/warehouse-productivity/
   echo '<?php phpinfo(); ?>' > test.php
   # Visit in browser: https://asmrcctvtechnologies.com/warehouse-productivity/test.php
   # Should show PHP configuration
   # Delete test.php when done
   ```

4. **Verify File Permissions**
   ```bash
   # Check permissions
   ls -la /public_html/warehouse-productivity/
   # public/ should show: drwxr-xr-x (755)
   # public/*.php should show: -rw-r--r-- (644)
   # If wrong, fix: chmod 755 directory, chmod 644 files
   ```

5. **Check Error Logs**
   ```bash
   tail -50 /home/username/storage/logs/application.log
   # Look for PHP errors, database errors
   # Document errors for database admin
   ```

6. **Verify Database**
   ```bash
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms
   # If can't connect, database is down
   # Contact hosting provider
   ```

7. **Restart Services**
   - Via cPanel: Restart Apache
   - Or contact hosting provider
   - May take 2-5 minutes

---

#### Issue: "500 Internal Server Error"

**Symptoms:**
- Browser shows: "500 Internal Server Error"
- Or shows: "Internal Server Error"
- Application was working, now broken
- Only this application affected

**Possible Causes:**
1. PHP syntax error in code
2. .htaccess syntax error
3. Missing required PHP extension
4. Database connection failed
5. File permissions wrong
6. Memory limit exceeded

**Solutions (Try in Order):**

1. **Check Error Logs**
   ```bash
   tail -100 /home/username/storage/logs/application.log
   tail -50 /var/log/apache2/error.log (if accessible)
   # Look for: PHP Parse error, Fatal error, PDO error
   # Note exact line numbers
   ```

2. **Check .htaccess Syntax**
   ```bash
   # Disable .htaccess temporarily
   mv .htaccess .htaccess.bak
   # Try accessing application
   # If works, .htaccess is broken
   # Fix syntax and restore
   mv .htaccess.bak .htaccess
   ```

3. **Verify PHP Extensions**
   ```bash
   # Check if PDO extension loaded
   php -m | grep -i pdo
   # Should show: PDO, pdo_mysql
   # If missing, request hosting provider enable
   ```

4. **Test Database Connection**
   ```bash
   # From command line
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms
   SHOW TABLES;
   # Should show 13 tables
   # If error, database issue
   ```

5. **Check File Permissions**
   ```bash
   ls -la /public_html/warehouse-productivity/
   # .env should be: -rw-r----- (640)
   # directories should be: drwxr-xr-x (755)
   # If wrong: fix with chmod
   ```

6. **Restore from Backup**
   - If just deployed and getting error
   - Restore application files from backup
   - Redeploy with fixes

---

### Category 3: Database Issues

#### Issue: "Database Connection Failed"

**Symptoms:**
- Error message mentions database
- Cannot view any pages
- Error like: "SQLSTATE[HY000]"
- Application won't load

**Possible Causes:**
1. Database server not running
2. Wrong database credentials in .env
3. User not added to database
4. Database doesn't exist
5. Wrong database host (localhost vs. IP)

**Solutions (Try in Order):**

1. **Verify Database Running**
   ```bash
   # Via cPanel
   # - Go to MySQL Databases
   # - Look for "Database Server Status"
   # - Should show: "Running"
   
   # Via command line
   mysqladmin ping
   # Should show: "mysqld is alive"
   ```

2. **Check Database Exists**
   ```bash
   mysql -u root -p
   SHOW DATABASES;
   # Look for: yitrmcft_wpms
   # If missing, create with migration scripts
   ```

3. **Verify User Can Connect**
   ```bash
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms
   # Enter password when prompted
   # If cannot connect:
   #   1. Check password is correct (copy from cPanel)
   #   2. Check for trailing spaces in password
   #   3. Verify user is added to database in cPanel
   ```

4. **Check Credentials in .env**
   ```
   DB_HOST=localhost
   DB_USER=yitrmcft_wpms_user
   DB_PASSWORD=YourPassword
   DB_NAME=yitrmcft_wpms
   ```
   - Verify each value matches cPanel
   - No extra spaces or typos
   - Password doesn't contain special chars that need escaping (%, &, ", ')

5. **Check User Privileges**
   ```bash
   # Login as admin
   mysql -u root -p
   # Check privileges
   SHOW GRANTS FOR 'yitrmcft_wpms_user'@'localhost';
   # Should show: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX
   # If missing, grant:
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
   ON yitrmcft_wpms.* TO 'yitrmcft_wpms_user'@'localhost';
   ```

6. **Contact Hosting Provider**
   - If database service won't start
   - Or if credentials don't work
   - Provide: database name, username, error message

---

#### Issue: "Database Connection Lost During Task"

**Symptoms:**
- Page loads fine
- Click a button, get error
- Then reconnects automatically
- Happens intermittently

**Cause:** Database connection dropped (timeout or server restart)

**Solutions:**

1. **Check for Database Restarts**
   - Contact hosting provider
   - Ask if database was restarted recently
   - Request notification before future restarts

2. **Increase Connection Timeout**
   - Edit: config/database.php
   - Increase timeout value
   - Or add connection retry logic

3. **Monitor Database Performance**
   - Check slow_query_log
   - Look for long-running queries
   - Optimize slow queries
   - Add indexes for common filters

4. **Check Database Size**
   - May be approaching quota
   - Ask hosting provider for available space
   - Archive old data if needed

---

#### Issue: "Foreign Key Constraint Failed"

**Symptoms:**
- Error like: "SQLSTATE[23000]"
- Message mentions "foreign key constraint"
- When trying to add/modify data
- Data operation rejected

**Cause:** Referenced record doesn't exist

**Example:** Trying to create picking task for non-existent employee

**Solutions:**

1. **Verify Referenced Record Exists**
   ```bash
   # Example: Check employee exists before assigning task
   SELECT * FROM employees WHERE employee_id = 123;
   # If no results, employee doesn't exist
   # Create employee first, then assign task
   ```

2. **Check Data Before Import**
   - If doing bulk import
   - Verify all referenced records exist
   - Employee must exist before task
   - Warehouse must exist before department
   - Department must exist before assigning employee

3. **Review Business Logic**
   - Understand what data depends on what
   - See WPMS_SCHEMA_DOCUMENTATION.md
   - Section: "Entity Relationships"

4. **Contact Database Admin**
   - Document exact error and steps to reproduce
   - May need schema adjustment or procedure change

---

### Category 4: Performance Issues

#### Issue: Dashboard Slow to Load

**Symptoms:**
- Dashboard page takes >5 seconds to load
- Or times out completely
- Other pages load fine
- Gets worse over time

**Possible Causes:**
1. Dashboard query is complex (joins many tables)
2. Audit logs too large
3. Missing indexes
4. Too many records to process
5. Database server under load

**Solutions (Try in Order):**

1. **Check Database Performance**
   ```bash
   mysql> EXPLAIN SELECT * FROM picking_tasks 
           WHERE employee_id = 1 ORDER BY created_at DESC LIMIT 100;
   # Look at: type (should be "ref", not "ALL")
   # Look at: key (should show index name)
   # If type="ALL", need to add index
   ```

2. **Archive Old Data**
   ```bash
   # Archive audit logs older than 90 days
   DELETE FROM audit_logs 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
   
   # Then optimize table
   OPTIMIZE TABLE audit_logs;
   ```

3. **Add Missing Indexes**
   ```bash
   # Check what indexes exist
   SHOW INDEXES FROM picking_tasks;
   
   # Add common query indexes
   ALTER TABLE picking_tasks ADD INDEX idx_employee_date (employee_id, created_at);
   ALTER TABLE audit_logs ADD INDEX idx_user_date (user_id, created_at);
   ```

4. **Monitor Server Load**
   ```bash
   # Check CPU usage
   top
   # Check disk I/O
   iostat -x 1
   # If high, server may be overloaded
   ```

5. **Upgrade Server Resources**
   - If server consistently overloaded
   - Contact hosting provider
   - Request upgrade: RAM, CPU, or SSD

---

#### Issue: Exports Take Too Long or Time Out

**Symptoms:**
- Click "Export" button
- Page takes >30 seconds to load
- Or shows: "Timeout" or "504 Gateway Timeout"
- Waiting for report generation

**Possible Causes:**
1. Date range too large (many records)
2. Complex report (multiple joins)
3. Server resources limited
4. Slow queries in export code

**Solutions:**

1. **Reduce Data Set**
   - Limit date range (days instead of months)
   - Use filters to reduce records
   - Export in smaller chunks

2. **Try Different Format**
   - CSV usually faster than PDF
   - PDF needs to render (slower)

3. **Increase PHP Timeout**
   - In .env: `PHP_EXECUTION_TIME=300` (5 minutes)
   - Or contact hosting provider
   - Request increase in php.ini: max_execution_time

4. **Export During Off-Peak**
   - Early morning (2-4 AM) usually quiet
   - Avoid lunch/shift-change times
   - Better server performance = faster export

5. **Pre-Generate Reports**
   - Generate reports ahead of time (Phase 2 feature)
   - Keep in database for quick access

---

### Category 5: File Upload Issues

#### Issue: File Upload Fails with No Error

**Symptoms:**
- Click "Import" button
- Select file
- Click "Upload"
- Page refreshes
- File not uploaded, no error shown
- File still missing

**Possible Causes:**
1. File size exceeds limit
2. File format not supported
3. upload/ directory not writable
4. PHP upload_max_filesize limit
5. Browser cache issue

**Solutions (Try in Order):**

1. **Check File Size**
   - File must be <50 MB
   - Check file properties
   - If larger: split file or ask admin to increase limit

2. **Verify File Format**
   - Must be: CSV or XLSX (Excel)
   - Not: PDF, TXT, ODS, etc.
   - Rename file if extension wrong
   - Or export as CSV from Excel

3. **Check Directory Permissions**
   ```bash
   ls -la /home/username/storage/uploads/
   # Should show: drwxrwx--- or drwxrwxr-x
   # Owner should be: apache/www-data
   # If wrong: chmod 770 and chown www-data
   ```

4. **Check PHP Upload Settings**
   ```bash
   php -i | grep -A 5 upload_max_filesize
   # Should show: upload_max_filesize: 50M or higher
   # If lower, contact hosting provider
   ```

5. **Try Different Browser**
   - Clear cache: Ctrl+Shift+Delete
   - Close and reopen browser
   - Try different browser (Chrome, Firefox, Safari)

6. **Check Application Logs**
   ```bash
   tail -50 /home/username/storage/logs/application.log
   # Look for upload errors
   # Document error for admin
   ```

---

#### Issue: "File Type Not Allowed" Error

**Symptoms:**
- File uploaded but rejected
- Error: "File type not allowed"
- Or "Invalid file extension"

**Cause:** File format not supported (need CSV or XLSX)

**Solutions:**

1. **Convert File Format**
   - If file is PDF: extract to Excel/CSV
   - If file is ODS: open in Excel, save as XLSX
   - If file is TXT: open in Excel, save as CSV

2. **Verify File Extension**
   - Must end in: .csv or .xlsx or .xls
   - If named wrong: rename file
   - Windows may hide extension - check carefully

3. **Check File Content**
   - Excel file may need to be saved as .xlsx
   - CSV should be plain text (no formatting)
   - Copy data to new file if corrupted

---

### Category 6: Email/Notification Issues

#### Issue: Users Not Receiving Emails

**Symptoms:**
- User created but no welcome email
- Task assigned but no notification
- Password reset requested but no reset email
- Email is Phase 2 feature

**Status:** Email notifications not available in Phase 1

**Current Workarounds:**
1. Manually notify employees of tasks
2. Use in-app messaging
3. Post announcements on system

**Phase 2:** Email notifications will be added with SMTP configuration

---

### Category 7: Security Issues

#### Issue: ".env File is Accessible via Browser"

**Symptoms:**
- Try to access: https://asmrcctvtechnologies.com/warehouse-productivity/.env
- Browser shows file contents (database password visible!)
- CRITICAL SECURITY ISSUE

**Cause:** .htaccess not blocking access to .env

**Solutions (Immediate):**

1. **Verify .htaccess Blocks .env**
   ```apache
   <FilesMatch "\.(env|config|sql)$">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

2. **Move .env Outside public_html**
   - .env should NOT be in public_html
   - Should be in /home/username/ (parent directory)
   - Application looks for: /home/username/.env
   - Update application config if needed

3. **Set File Permissions**
   ```bash
   chmod 640 .env
   # Only owner and group can read
   # Not readable by others
   ```

4. **Test Access Blocked**
   ```bash
   curl https://asmrcctvtechnologies.com/warehouse-productivity/.env
   # Should return: 403 Forbidden
   # Not file contents
   ```

---

#### Issue: Suspected SQL Injection or XSS Attack

**Symptoms:**
- Strange errors in logs
- Unauthorized data access
- Pages showing unexpected content
- Security breach suspected

**Actions (Immediate):**

1. **Isolate System**
   - Block public access to application
   - Maintain admin access only
   - Don't shut down (need logs for investigation)

2. **Investigate**
   - Review audit logs: `SELECT * FROM audit_logs WHERE created_at > NOW() - INTERVAL 1 HOUR;`
   - Check application error logs
   - Look for unusual SQL queries
   - Document suspicious activities

3. **Contact IT Security**
   - Notify IT Manager immediately
   - Provide: logs, timeline, evidence
   - Do NOT go public yet

4. **Assess Damage**
   - Determine what data was accessed
   - Determine if data was modified
   - Check for backdoors or malware

5. **Recover**
   - Restore from backup before attack
   - Verify application code for vulnerabilities
   - Patch code if needed
   - Change all database passwords

---

### Category 8: User Access Issues

#### Issue: User Can't See Assigned Tasks

**Symptoms:**
- User logs in
- Dashboard shows 0 tasks
- Manager says tasks were assigned
- Other users see their tasks fine

**Possible Causes:**
1. Task not assigned to this user
2. User filtered tasks accidentally
3. Task status is "Draft" (not yet assigned)
4. Permission issue for user's role

**Solutions:**

1. **Check Task Assignment**
   - As admin: go to Tasks → All Tasks
   - Search for user name
   - Verify task shows this employee
   - If not, must re-assign task

2. **Check Filters**
   - User may have filtered tasks
   - Click "Clear Filters" button
   - Refresh page
   - Tasks should reappear

3. **Verify User Permissions**
   - Go to Settings → Users → User
   - Check role: Employee, Supervisor, etc.
   - Verify role can view tasks
   - If wrong role, change it

4. **Check User Status**
   - User may be marked "On Leave"
   - Or marked "Inactive"
   - Change status if needed
   - Notify user of any changes

---

#### Issue: User Has Wrong Permissions

**Symptoms:**
- User can do something they shouldn't
- User can't do something they should
- "Access Denied" on legitimate page
- Or can access restricted data

**Solutions:**

1. **Verify User Role**
   - Go to Settings → Users → User
   - Check role column
   - Verify it matches job function
   - Change if needed

2. **Check Department Assignment**
   - Some permissions based on department
   - Verify department assignment
   - Verify department-level restrictions
   - Update if needed

3. **Review Audit Logs**
   - See what user actually accessed
   - Verify if access was authorized
   - Document any concerning access
   - Report to manager if issues

4. **Adjust Permissions**
   - Change user role if needed
   - Or change department
   - Changes take effect immediately
   - No need to log out/in

---

## Troubleshooting Checklist

When system has issues, check in this order:

1. **Is Internet/Network Working?**
   - Can ping google.com?
   - Can access other websites?
   - Is WiFi/connection strong?

2. **Is Application Up?**
   - Can access main URL?
   - Do you see login page?
   - Or blank page/error?

3. **Can You Log In?**
   - Try your account
   - Try different account
   - Try admin account
   - All failing, or just some?

4. **Check Logs**
   - Application logs: /home/*/storage/logs/
   - Database logs: /var/log/mysql/
   - Web server logs: /var/log/apache2/
   - What errors show up?

5. **Verify Database**
   - Is database running?
   - Can you connect with credentials?
   - Do tables exist?
   - Can you SELECT data?

6. **Check File System**
   - Are files in correct location?
   - Are permissions correct?
   - Is disk space available?
   - Are directories writable?

7. **Review Changes**
   - What changed recently?
   - Was there a deployment?
   - Was there an update?
   - Configuration change?

8. **Contact Support**
   - Provide: error messages, logs, steps to reproduce
   - Document: when issue started, who affected
   - Provide: screenshots of error pages

---

## Getting Help

**For Immediate Support:**
- Email: [Support Email]
- Phone: [Support Phone]
- Available: [Business Hours]

**For Escalation:**
- IT Manager: [Name, Email, Phone]
- Database Admin: [Name, Email, Phone]

**For Critical Issues:**
- Call hosting provider: [Z.com Support Number]
- Reference: WPMS on asmrcctvtechnologies.com
- Emergency: [24/7 number]

---

**Document Version:** 1.0  
**Last Updated:** October 24, 2026  
**Next Review:** January 24, 2027

