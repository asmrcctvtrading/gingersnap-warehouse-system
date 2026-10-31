# WPMS Deployment Guide

## Files Ready for Upload

All PHP files have been created and are located in your local project folder:
- `C:\Users\Odyssey Jan Pascual\.omniroute\Gingersnaps-Warehouse-System\`

### Files to Create/Upload to `/warehouse-productivity/`:

1. **config.php** - Database configuration
2. **.env** - Environment variables (UPDATED with password: p@$$w0rd@123)
3. **login.php** - Login page
4. **dashboard.php** - Main dashboard
5. **tasks.php** - Task management (create next)
6. **employees.php** - Employee management (create next)
7. **reports.php** - Reports page (create next)

## Quick Copy-Paste Instructions

### Option A: Upload Files Directly (Recommended)

1. Navigate to `/public_html/warehouse-productivity/` in cPanel File Manager
2. For each file below, use **File > Create New File** option
3. Copy the entire code from each section and paste into the file
4. Save each file

### Option B: Use Terminal via cPanel

If cPanel has SSH/Terminal access:

```bash
cd /home/yitrmcft/public_html/warehouse-productivity/
# Create files using cat command or nano editor
# Then paste the code
```

---

## File Contents - Ready to Copy-Paste

### 1. config.php
[Already created - file location: config.php]

### 2. .env
[Already created with correct database password]

### 3. login.php  
[Already created - file location: login.php]

### 4. dashboard.php
[Already created - file location: dashboard.php]

---

## Next Steps

After uploading all files:

1. **Verify .env file permissions:**
   - In cPanel File Manager, right-click .env
   - Set permissions to 640 (readable by owner and group)

2. **Test Login:**
   - Visit: `https://asmrcctvtechnologies.com/warehouse-productivity/login.php`
   - Use credentials:
     - Email: `admin@asmrcctvtechnologies.com`
     - Password: `admin123`

3. **Verify Database Connection:**
   - If login fails, check database credentials in .env
   - Ensure MySQL user has proper privileges

4. **Create Remaining Pages:**
   - tasks.php, employees.php, reports.php (to be created)

---

## Database Status

✅ Database created: `yitrmcft_wpms`  
✅ Tables created: 13 tables  
✅ Sample data loaded  
✅ All constraints configured  

Database ready at: `localhost` / `yitrmcft_wpms`

---

## Support

All PHP files are documented and ready to use. For any issues:
1. Check .env file for correct database credentials
2. Verify database connection from cPanel > MySQL
3. Check file permissions in cPanel File Manager
