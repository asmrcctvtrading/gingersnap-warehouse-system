# WPMS Quick Start Guide
**Version:** 1.0  
**Date:** October 24, 2026  
**Time to Read:** 5 minutes  

---

## What is WPMS?

**Warehouse Productivity Monitoring System** - a web app for tracking picking/packing tasks in your warehouse.

**URL:** `https://asmrcctvtechnologies.com/warehouse-productivity/`

---

## In 30 Seconds

1. **Login** with email and password
2. **View Tasks** assigned to you
3. **Complete Tasks** by picking items and marking them done
4. **Report Issues** if something's wrong
5. **View Reports** showing your productivity

---

## Critical Setup Steps (For Administrators)

### 1. Database Setup (10 minutes)

```
1. Log into Z.com cPanel
2. Create database: yitrmcft_wpms
3. Create user: yitrmcft_wpms_user
4. Add user to database with all privileges
5. Run SQL migrations (01-14) in order
```

**Status:** ☐ COMPLETE

### 2. Environment Configuration (5 minutes)

```
1. Copy .env.sample to .env
2. Edit .env with database credentials
3. Set permissions: chmod 640 .env
4. Set owner: chown www-data .env (or Apache user)
```

**Status:** ☐ COMPLETE

### 3. Directory Structure (5 minutes)

```
1. Upload application to public_html/warehouse-productivity/
2. Set permissions:
   - Directories: 755
   - Files: 644
   - storage/: 770
3. Verify .htaccess is in place
4. Test HTTPS access
```

**Status:** ☐ COMPLETE

### 4. Create Admin Account (2 minutes)

```
1. Log into application (no account yet - use default)
2. Create first admin user:
   - Email: admin@asmrcctvtechnologies.com
   - Password: [Strong password, 20+ chars]
   - Role: Admin
3. Log out, log in with admin account
4. Change default password immediately
```

**Status:** ☐ COMPLETE

### 5. Create Departments & Employees (10 minutes)

```
1. Go to: Settings → Departments
2. Create warehouse locations (Main, Receiving, etc.)
3. Create departments (Picking, Packing, QA)
4. Go to: Employees
5. Add employee records for your staff
6. Optionally create user accounts for each
```

**Status:** ☐ COMPLETE

### 6. Assign Initial Tasks (5 minutes)

```
1. Go to: Tasks → Picking/Packing
2. Assign sample tasks to test employees
3. Verify employees can see tasks
4. Test completing a task
```

**Status:** ☐ COMPLETE

---

## Critical Features (First Week)

### User Roles

| Role | Can Do |
|------|--------|
| **Admin** | Manage everything, create users, view all data |
| **Manager** | Assign tasks, view team performance, limited admin |
| **Supervisor** | Track team, complete tasks, view reports |
| **Employee** | Complete assigned tasks, view own reports |
| **Viewer** | Read-only access to dashboards |

### Key Pages

| Page | URL Path | Purpose |
|------|----------|---------|
| Dashboard | `/` | Overview of tasks and performance |
| My Tasks | `/tasks/assigned` | Your assigned tasks |
| Employees | `/employees` | Add/manage staff |
| Tasks | `/tasks` | Assign and track tasks |
| Reports | `/reports` | View productivity reports |
| Settings | `/admin/settings` | Configure system |

### Minimum Configuration

To get started, you must:

1. ☐ Create at least 1 warehouse
2. ☐ Create at least 1 department
3. ☐ Create at least 1 employee
4. ☐ Create at least 1 user account
5. ☐ Assign at least 1 task

---

## First Day Checklist

**Morning:**
- [ ] Verify system is accessible: https://asmrcctvtechnologies.com/warehouse-productivity/
- [ ] Log in with admin account
- [ ] Verify database has all 13 tables
- [ ] Check that all seed data loaded correctly

**Mid-Day:**
- [ ] Create test user accounts for each role
- [ ] Create sample departments
- [ ] Assign sample tasks to test employees
- [ ] Walk through picking task workflow
- [ ] Walk through packing task workflow

**End of Day:**
- [ ] Verify all test tasks can be completed
- [ ] Check that reports are generating
- [ ] Verify backups are running
- [ ] Document any issues found

**Sign-Off:**
```
Date: ____________
Admin Name: ____________
Signature: ____________
Status: ☐ Ready for Testing  ☐ Issues Found - See Log
```

---

## Common Questions

### Q: How do I reset a user password?

Go to Settings → Users → Select User → Reset Password → User gets email

### Q: Can I bulk import employees?

Yes, via Tools → Import Data → Upload CSV

### Q: How do I see all employee tasks?

Go to Tasks → Filter by Employee → Select employee

### Q: Where are backups stored?

Daily at 2:00 AM in /home/username/storage/backups/

### Q: How do I export a report?

Click any report → Click "Export" → Choose CSV/PDF

### Q: What if HTTPS isn't working?

Contact Z.com support. Verify SSL certificate is installed.

### Q: Can I change task priorities after assigning?

Yes, click task → Edit → Change priority → Save

### Q: How long does a backup take?

Usually 5-10 minutes depending on database size

### Q: Can I undo a completed task?

Only admin can reopen completed tasks (see Troubleshooting)

---

## In Case of Emergency

### System Down

1. Check: https://asmrcctvtechnologies.com/warehouse-productivity/
2. If error: Screenshot and note time
3. Contact Z.com support: [Support Number]
4. Meanwhile: Use paper forms for task tracking

### Database Won't Connect

1. Verify database is running (cPanel)
2. Verify credentials in .env file
3. Check permissions on .env file
4. Restore from backup (see BACKUP section in Admin Manual)

### Can't Log In

1. Check email address is correct
2. Try password reset
3. Verify user account exists
4. Check user role (may be disabled)

### Forgot Admin Password

```
Contact database administrator to:
1. Connect directly to database
2. Run: UPDATE users SET password = '[hash]' WHERE email = 'admin@...'
3. Or restore from backup and redo setup
```

---

## Important Numbers to Know

### Database Connection
- **Host:** localhost
- **User:** yitrmcft_wpms_user
- **Database:** yitrmcft_wpms
- **Port:** 3306

### Default Admin User
- **Email:** [Set during setup]
- **Password:** [Set during setup]
- **Role:** Admin

### File Locations (Z.com)
- **Application:** `/public_html/warehouse-productivity/`
- **Backups:** `/home/username/storage/backups/`
- **Logs:** `/home/username/storage/logs/`
- **Uploads:** `/home/username/storage/uploads/`

---

## What's NOT in Phase 1

These features are coming in Phase 2+:

- Email notifications ❌
- Barcode integration (basic only) ⚠️
- API for external systems ❌
- Advanced reporting/dashboards ❌
- Multi-warehouse analytics ❌
- Mobile app ❌

---

## Next Steps

### Week 1: Setup
- Complete checklist above
- Create test accounts
- Test all workflows
- Document issues

### Week 2: Training
- Train supervisors on task assignment
- Train employees on completing tasks
- Practice bulk imports
- Run sample reports

### Week 3: Go-Live
- Import real employee data
- Assign real tasks
- Monitor system performance
- Adjust configuration as needed

### Week 4: Optimization
- Review performance metrics
- Identify slow areas
- Optimize database indexes
- Plan next phases (Phase 2+)

---

## Need Help?

**For Setup Issues:**
- Read: ADMINISTRATOR_MANUAL.md
- Read: DATABASE_SETUP_INSTRUCTIONS.md
- Contact: IT Admin at [email]

**For System Issues:**
- Read: TROUBLESHOOTING_GUIDE.md
- Check: SUPPORT_CONTACTS.md
- Contact: Z.com Support at [phone]

**For User Questions:**
- Read: USER_MANUAL.md
- Contact: Your Manager

---

## Success Metrics

After 1 week, you should have:

✅ System fully deployed and accessible  
✅ All 13 database tables created  
✅ At least 5 test users created  
✅ At least 10 test tasks assigned and completed  
✅ Daily backups running successfully  
✅ HTTPS working correctly  
✅ Basic understanding of all roles  
✅ All staff trained on their roles  

---

**Document Version:** 1.0  
**Last Updated:** October 24, 2026  
**Next Review:** November 7, 2026 (Phase 1 + 2 weeks)

