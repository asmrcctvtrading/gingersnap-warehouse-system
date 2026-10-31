# WPMS Production Deployment Checklist
**Version:** 1.0  
**Date:** October 24, 2026  
**Target Environment:** Z.com cPanel (asmrcctvtechnologies.com)  
**Estimated Duration:** 2-3 hours  

---

## Pre-Deployment Phase (1-2 weeks before)

### Environment Verification
- [ ] Z.com environment verified (see ZCOM_DEPLOYMENT_VERIFICATION.md)
- [ ] All 11/11 deployment readiness checks passed
- [ ] PHP version compatible (7.4+)
- [ ] MariaDB running and accessible
- [ ] HTTPS certificate valid and auto-renewing
- [ ] .htaccess support enabled
- [ ] Sufficient disk space available (>100 MB free)

### Backup Planning
- [ ] Existing Warehouse Inventory System backed up
- [ ] Database backup tested and verified
- [ ] File system backup completed
- [ ] Backup location documented
- [ ] Restore procedure tested
- [ ] Z.com automated backups verified
- [ ] Manual backup procedure documented

### Code & Configuration Review
- [ ] All SQL migration files reviewed (01-13)
- [ ] Seed data verified for test environment
- [ ] .env.sample reviewed and updated
- [ ] .htaccess template reviewed and ready
- [ ] Application file structure verified
- [ ] Directory permissions documented
- [ ] Security configuration reviewed

### Documentation Complete
- [ ] ADMINISTRATOR_MANUAL.md finalized
- [ ] USER_MANUAL.md finalized
- [ ] DATABASE_SETUP_INSTRUCTIONS.md reviewed
- [ ] TROUBLESHOOTING_GUIDE.md prepared
- [ ] SUPPORT_CONTACTS.md populated
- [ ] This checklist reviewed and ready

### Testing Verification
- [ ] Unit tests pass (all critical paths)
- [ ] Integration tests pass (database + application)
- [ ] Workflow tests pass (picking + packing)
- [ ] Security tests pass (HTTPS, sessions, auth)
- [ ] Performance tests pass (database queries <100ms)
- [ ] Backup/restore tests pass

### Sign-Off for Pre-Deployment
```
Date: ____________
Checked by: ____________
Status: ☐ APPROVED TO PROCEED  ☐ ISSUES FOUND
If issues: ___________________________________
```

---

## Deployment Day Preparation (2 hours before)

### Pre-Deployment Communication
- [ ] Scheduled deployment window communicated to stakeholders
- [ ] Maintenance message prepared
- [ ] Z.com support notified of deployment plans
- [ ] Backup contact person on standby
- [ ] Emergency escalation path established
- [ ] Start time: _____________ (UTC/Local)
- [ ] Estimated duration: 2-3 hours

### Final Pre-Deployment Checks
- [ ] Backup of production database completed RIGHT NOW
- [ ] Backup of production files completed RIGHT NOW
- [ ] Backup verified (attempted restore test on copy)
- [ ] .env file prepared with real credentials
- [ ] All .env values verified (no typos in passwords)
- [ ] File upload limits verified in .env
- [ ] Backup directory path verified in .env
- [ ] HTTPS URL in .env verified
- [ ] Log directory exists and is writable
- [ ] Storage directory exists and is writable

### System Status Verification
- [ ] Z.com hosting is responsive
- [ ] cPanel accessible and working
- [ ] MySQL database service running
- [ ] Enough database space for schema + data
- [ ] Enough disk space for application + backups
- [ ] No other deployments in progress

### Deployment Team Ready
- [ ] Primary deployer (lead tech) ready
- [ ] Secondary (backup) deployer available
- [ ] Database administrator on call
- [ ] Project manager ready to update stakeholders
- [ ] All team members aware of deployment window

---

## Deployment Phase (During Deployment)

**IMPORTANT: Follow these steps EXACTLY in this order. Do not skip steps.**

### Phase 1: Pre-Flight Checks (15 minutes)

- [ ] **Start Deployment Timer** - Note current time: _______
- [ ] Access Z.com cPanel: https://cpanel.z.com (or provider URL)
- [ ] Verify login successful
- [ ] Navigate to phpMyAdmin or MySQL section
- [ ] Verify database service is running
- [ ] Run test query to confirm connectivity
- [ ] Check disk space (df -h or cPanel dashboard)
- [ ] Screenshot current state for records

### Phase 2: Database Preparation (20 minutes)

**These steps are in DATABASE_SETUP_INSTRUCTIONS.md - follow exactly**

- [ ] **Create new database:**
  - Name: `yitrmcft_wpms`
  - Character set: utf8mb4
  - Collation: utf8mb4_unicode_ci
  - Verify creation: `SHOW DATABASES LIKE 'yitrmcft_wpms';`

- [ ] **Create database user:**
  - Username: `yitrmcft_wpms_user`
  - Password: [Strong, 20+ characters]
  - Store password securely
  - Add to database with privileges: SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX

- [ ] **Verify user can connect:**
  ```bash
  mysql -u yitrmcft_wpms_user -p yitrmcft_wpms
  # Should connect successfully
  SHOW TABLES; # Should be empty initially
  EXIT;
  ```

- [ ] **Execute migration files (01-13) in order:**
  
  ```bash
  # Via phpMyAdmin: Import each file manually
  # Via Command Line:
  for file in 01_create_users_table \
              02_create_warehouses_table \
              03_create_departments_table \
              04_create_employees_table \
              05_create_picking_tasks_table \
              06_create_picking_errors_table \
              07_create_packing_tasks_table \
              08_create_packing_errors_table \
              09_create_imports_table \
              10_create_import_details_table \
              11_create_audit_logs_table \
              12_create_system_settings_table \
              13_create_productivity_summaries_table; do
    mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < sql/${file}.sql
    if [ $? -eq 0 ]; then
      echo "✓ $file loaded"
    else
      echo "✗ FAILED: $file - STOP DEPLOYMENT"
      exit 1
    fi
  done
  ```

- [ ] **Verify all tables created:**
  ```sql
  SELECT COUNT(*) as table_count FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = 'yitrmcft_wpms';
  -- Should return: 13
  ```

- [ ] **Verify foreign keys created:**
  ```sql
  SELECT COUNT(*) as fk_count FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = 'yitrmcft_wpms' AND REFERENCED_TABLE_NAME IS NOT NULL;
  -- Should return: 15+
  ```

- [ ] **Load seed data (for testing):**
  ```bash
  mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < sql/14_seed_data.sql
  # If this is production without seed data, SKIP this step
  ```

**Database Preparation Time:** _____ minutes

### Phase 3: Application File Upload (20 minutes)

- [ ] **Upload application files to cPanel:**
  - Via File Manager or SFTP client
  - Upload to: `/public_html/warehouse-productivity/`
  - Include all directories: public/, src/, config/, sql/
  - Do NOT include: .git, node_modules (if any), testing files
  - Verify all files uploaded successfully

- [ ] **Verify directory structure:**
  ```
  public_html/warehouse-productivity/
  ├── public/
  │   ├── index.php (router)
  │   ├── login.php
  │   ├── bootstrap.php
  │   ├── css/
  │   ├── js/
  │   └── views/
  ├── src/
  │   ├── auth/
  │   ├── controllers/
  │   ├── repositories/
  │   ├── models/
  │   ├── middleware/
  │   ├── helpers/
  │   └── services/
  ├── config/
  │   ├── database.php
  │   └── config.php
  ├── storage/ (create if doesn't exist)
  │   ├── uploads/
  │   ├── logs/
  │   ├── backups/
  │   └── cache/
  └── .htaccess (copy from htaccess.template)
  ```

- [ ] **Set directory permissions:**
  ```bash
  # Public-accessible directories
  chmod 755 public_html/warehouse-productivity/public
  chmod 755 public_html/warehouse-productivity/public/css
  chmod 755 public_html/warehouse-productivity/public/js
  chmod 755 public_html/warehouse-productivity/public/views
  
  # Protected directories
  chmod 755 public_html/warehouse-productivity/src
  chmod 750 public_html/warehouse-productivity/config
  
  # Writable directories
  chmod 770 public_html/warehouse-productivity/storage
  chmod 770 public_html/warehouse-productivity/storage/uploads
  chmod 770 public_html/warehouse-productivity/storage/logs
  chmod 770 public_html/warehouse-productivity/storage/backups
  
  # Files
  find public_html/warehouse-productivity -type f -name "*.php" -exec chmod 644 {} \;
  find public_html/warehouse-productivity -type f -name ".htaccess" -exec chmod 644 {} \;
  ```

- [ ] **Verify file ownership (if accessible):**
  ```bash
  # Should be owned by Apache user (usually: nobody, www-data, or apache)
  chown -R www-data:www-data public_html/warehouse-productivity/storage
  ```

**File Upload Time:** _____ minutes

### Phase 4: Environment Configuration (10 minutes)

- [ ] **Create .env file:**
  - Copy .env.sample to .env (in application root)
  - Or create via SFTP/File Manager

- [ ] **Edit .env with production values:**
  ```
  DB_HOST=localhost
  DB_NAME=yitrmcft_wpms
  DB_USER=yitrmcft_wpms_user
  DB_PASSWORD=[password from Step 2]
  
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://asmrcctvtechnologies.com/warehouse-productivity/
  
  SESSION_SECURE=true
  SESSION_HTTPONLY=true
  SESSION_SAMESITE=Strict
  
  LOG_DIR=/home/[username]/storage/logs/
  UPLOAD_DIR=/home/[username]/storage/uploads/
  BACKUP_DIR=/home/[username]/storage/backups/
  ```

- [ ] **Verify .env file:**
  - [ ] All values filled in (no CHANGE_THIS placeholders)
  - [ ] Passwords correct (copy from cPanel if unsure)
  - [ ] Paths use absolute paths (start with /)
  - [ ] APP_URL uses https://

- [ ] **Set .env file permissions:**
  ```bash
  chmod 640 .env
  # Should NOT be world-readable or executable
  ```

- [ ] **Verify .env is NOT accessible via HTTP:**
  ```
  Try in browser: https://asmrcctvtechnologies.com/warehouse-productivity/.env
  Should get: 403 Forbidden (or not found)
  If shows content: SECURITY ISSUE - stop deployment, fix .htaccess
  ```

**Configuration Time:** _____ minutes

### Phase 5: Apache & Security Configuration (5 minutes)

- [ ] **Place .htaccess file:**
  - Copy htaccess.template to public_html/warehouse-productivity/.htaccess
  - OR create .htaccess with content from template
  - Verify it's in correct location

- [ ] **Test Apache rewrite rules:**
  ```
  Try in browser: https://asmrcctvtechnologies.com/warehouse-productivity/test
  Should show: index.php (not 404)
  If shows 404: Apache mod_rewrite not working, contact provider
  If shows 500: .htaccess syntax error, fix and reload
  ```

- [ ] **Verify HTTPS redirect:**
  ```
  Try: http://asmrcctvtechnologies.com/warehouse-productivity/
  Should redirect to: https://asmrcctvtechnologies.com/warehouse-productivity/
  If doesn't redirect: Check .htaccess redirect rule
  ```

- [ ] **Test security headers:**
  ```bash
  curl -I https://asmrcctvtechnologies.com/warehouse-productivity/
  Should show headers:
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: SAMEORIGIN
  - X-XSS-Protection: 1; mode=block
  - Referrer-Policy: strict-origin-when-cross-origin
  ```

**Security Configuration Time:** _____ minutes

### Phase 6: Application Test (15 minutes)

- [ ] **Test HTTP access:**
  ```
  Open: https://asmrcctvtechnologies.com/warehouse-productivity/
  Should see: Login page (no errors)
  Should see: WPMS logo/title
  Should see: Email and password fields
  ```

- [ ] **Test database connection:**
  - Don't log in yet - first verify connection
  - Check application error logs: storage/logs/
  - Look for any database connection errors
  - If errors: verify .env credentials are correct

- [ ] **Test login with seed data user:**
  ```
  Email: (from seed data - usually admin@example.com)
  Password: (from seed data - check 14_seed_data.sql)
  Should: Log in successfully
  Should see: Dashboard
  ```

- [ ] **Verify all pages load:**
  - [ ] Dashboard: https://asmrcctvtechnologies.com/warehouse-productivity/
  - [ ] Employees: Click "Employees" in menu
  - [ ] Tasks: Click "Tasks" in menu
  - [ ] Reports: Click "Reports" in menu
  - [ ] Settings: Click "Settings" in menu (admin only)
  - [ ] Each should load without errors

- [ ] **Test key functionality:**
  - [ ] Create a test task (if permissions allow)
  - [ ] View task in dashboard
  - [ ] Verify audit log was created
  - [ ] Export a report to CSV

- [ ] **Check error logs:**
  ```bash
  tail -50 storage/logs/application.log
  Should show: Normal operations
  Should NOT show: PHP errors, database connection errors
  If errors: Document and fix before going live
  ```

**Application Test Time:** _____ minutes

### Phase 7: Backup Verification (10 minutes)

- [ ] **Verify database backup works:**
  ```bash
  mysqldump -u yitrmcft_wpms_user -p yitrmcft_wpms > /tmp/test_backup.sql
  # Should complete without errors
  ls -lh /tmp/test_backup.sql
  # Should show file size ~10+ MB
  ```

- [ ] **Verify backup restoration:**
  ```bash
  # Create test database
  CREATE DATABASE yitrmcft_wpms_test;
  
  # Restore backup to test database
  mysql -u yitrmcft_wpms_user -p yitrmcft_wpms_test < /tmp/test_backup.sql
  
  # Verify restore
  SHOW TABLES;
  # Should show 13 tables
  SELECT COUNT(*) FROM users;
  # Should match production count
  
  # Clean up
  DROP DATABASE yitrmcft_wpms_test;
  ```

- [ ] **Verify cPanel automated backups are enabled:**
  - [ ] Log into cPanel
  - [ ] Navigate to: Backups (or Backup Wizard)
  - [ ] Verify daily backup is scheduled
  - [ ] Verify retention is set (30 days recommended)
  - [ ] Verify remote backup location (if available)

**Backup Verification Time:** _____ minutes

### Phase 8: Final Verification (10 minutes)

- [ ] **Performance check:**
  - [ ] Dashboard loads in <2 seconds
  - [ ] List pages load in <2 seconds
  - [ ] Export completes in <5 seconds
  - [ ] No error messages in logs

- [ ] **Security verification:**
  - [ ] HTTPS is enforced (no HTTP access)
  - [ ] Session cookie is secure
  - [ ] .env file is not accessible
  - [ ] config/ directory not browsable
  - [ ] src/ directory not browsable

- [ ] **Database verification:**
  - [ ] All 13 tables present
  - [ ] Can read data (SELECT works)
  - [ ] Can write data (INSERT works)
  - [ ] Foreign keys enforced
  - [ ] Indexes present and working

- [ ] **File system verification:**
  - [ ] Permissions correct (755 dirs, 644 files)
  - [ ] log/ directory writable
  - [ ] storage/ directory writable
  - [ ] Backup directory exists

- [ ] **Documentation verification:**
  - [ ] All documentation files uploaded (if needed)
  - [ ] README.md accessible and current
  - [ ] TROUBLESHOOTING_GUIDE.md available to admins

**Final Verification Time:** _____ minutes

---

## Post-Deployment Phase (Immediately After)

### Verification & Notification (30 minutes)

- [ ] **Verify system is stable:**
  - [ ] All tests from Phase 8 passed
  - [ ] No critical errors in logs
  - [ ] Database is responsive
  - [ ] HTTPS is working

- [ ] **Create initial admin account for live use:**
  - [ ] Log in with seed data admin account
  - [ ] Navigate to: Settings → Users → Add User
  - [ ] Create new admin account with real credentials
  - [ ] Log out and verify new admin can log in
  - [ ] Document new admin credentials securely
  - [ ] Delete/disable seed data admin account

- [ ] **Notify stakeholders:**
  - [ ] Deployment completed successfully
  - [ ] System is live and accessible
  - [ ] Admin account created and tested
  - [ ] Support contact information provided
  - [ ] Training scheduled for staff

- [ ] **Document deployment:**
  - [ ] Actual start time: _______
  - [ ] Actual end time: _______
  - [ ] Total duration: _______ minutes
  - [ ] Any issues encountered: ___________________
  - [ ] Any deviations from plan: ___________________

### Immediate Monitoring (First 24 hours)

- [ ] **Monitor system closely:**
  - [ ] Check error logs hourly (first 4 hours)
  - [ ] Check error logs every 4 hours (first 24 hours)
  - [ ] Monitor database performance
  - [ ] Monitor disk space

- [ ] **Address any issues immediately:**
  - [ ] If database errors: restart database or restore backup
  - [ ] If file permission errors: fix permissions
  - [ ] If performance issues: check database indexes
  - [ ] If security issues: lockdown immediately

- [ ] **Prepare for user access:**
  - [ ] Ensure all staff can access WPMS
  - [ ] Create user accounts for active staff
  - [ ] Assign roles appropriately
  - [ ] Provide login instructions to staff

**Deployment Sign-Off:**

```
Deployment Date: ____________
Deployment Time: __________ to __________
Duration: __________ minutes

Primary Deployer: ____________________
Secondary Deployer: ____________________

Pre-Deployment Checks: ☐ All Passed  ☐ Issues Found
Deployment Phase: ☐ All Passed  ☐ Issues Found
Post-Deployment Checks: ☐ All Passed  ☐ Issues Found

Final Status: ☐ SUCCESSFUL - LIVE ☐ ISSUES FOUND - INVESTIGATING

Issues/Comments:
___________________________________________
___________________________________________

Authorized by: ____________________
Date: ____________________
```

---

## Rollback Procedures (If Needed)

If critical issues are found that cannot be quickly fixed:

### Rollback Decision Criteria

Stop and rollback if:
- Database will not connect
- Application shows 500 errors
- Existing systems are affected
- Security vulnerability discovered
- Data integrity issues found

### Rollback Steps

1. **Stop Application**
   - Remove .env file (if not security issue)
   - Or replace index.php with maintenance page

2. **Restore Database**
   ```bash
   # Restore from backup taken before deployment
   mysql -u yitrmcft_wpms_user -p yitrmcft_wpms < pre_deployment_backup.sql
   ```

3. **Remove Application Files**
   - Delete /public_html/warehouse-productivity/
   - Or restore from file backup

4. **Verify Existing Systems**
   - Confirm Warehouse Inventory System still works
   - Check that no existing data was affected
   - Verify backups are intact

5. **Notify Stakeholders**
   - Document reason for rollback
   - Estimate timeline for resolution
   - Plan for retry deployment

6. **Post-Mortem**
   - Identify root cause
   - Plan remediation
   - Schedule retry

---

## Deployment Checklist Completion

**All sections completed?**

- [ ] Pre-Deployment Phase: _____ checks
- [ ] Deployment Phase: _____ checks  
- [ ] Post-Deployment Phase: _____ checks

**Total Time:**
- Planned: 2-3 hours
- Actual: _____ hours, _____ minutes
- Variance: _____ minutes (over/under)

**Final Status:** ☐ COMPLETE & SUCCESSFUL ☐ COMPLETE WITH ISSUES ☐ INCOMPLETE - ROLLBACK

---

**Document Version:** 1.0  
**Last Updated:** October 24, 2026  
**Maintained By:** Agent 6 (Deployment & Documentation)

