# Z.com cPanel Verification Checklist
**Project:** Warehouse Productivity Monitoring System (WPMS)  
**Date:** October 24, 2026  
**Status:** PENDING USER VERIFICATION  
**Owner:** Agent 6 (Deployment Lead)

---

## Overview

This checklist documents all information needed from the Z.com cPanel hosting environment to finalize WPMS deployment planning. **This information is required from the user before Week 1 is complete.**

The existing Warehouse Inventory System is a protected production application. No changes will be made to it. WPMS will be deployed in a separate directory structure with its own database.

---

## Section 1: Hosting Account & Access

### Basic Information
- [ ] **Z.com Account Email:** (confirmation)
- [ ] **Z.com Account Domain:** asmrcctvtechnologies.com (confirmation)
- [ ] **cPanel URL:** (usually cpanel.z.com or hosting provider portal)
- [ ] **cPanel Username:** (if different from account email)
- [ ] **Database Host:** (usually localhost or specific IP for remote access)

**Why Needed:** To access cPanel and verify services during deployment setup.

---

## Section 2: Document Root & File Structure

### File System Layout
- [ ] **Primary Public HTML Path:** Usually `/home/asmrcctvtechnologies/public_html/` 
  - **Actual Path:** ________________
  - **Current Contents:** ________________

- [ ] **Proposed WPMS Path:** `/home/asmrcctvtechnologies/public_html/warehouse-productivity/`
  - **Path Available?** ☐ YES ☐ NO ☐ EXISTS (please describe)
  - **If exists, current contents:** ________________

- [ ] **Home Directory:** `/home/asmrcctvtechnologies/`
  - **Accessible for config files?** ☐ YES ☐ NO
  - **Note:** Non-public config, backups, and logs should be stored here, outside web root

- [ ] **Current Directory Structure:**
  ```
  /home/asmrcctvtechnologies/
  ├── public_html/
  │   ├── [existing files/domains]
  │   └── warehouse-productivity/ ← WPMS will go here
  ├── backups/ ← For WPMS backups
  ├── config/ ← For WPMS config files (non-public)
  └── logs/ ← For WPMS logs (non-public)
  ```
  - **Can we create directories outside public_html?** ☐ YES ☐ NO

**Why Needed:** To confirm we can deploy WPMS without conflicting with existing applications and to ensure backup/config directories exist.

---

## Section 3: PHP Configuration

### PHP Version
- [ ] **Current PHP Version:** ________________
  - **Minimum Required:** PHP 7.4+
  - **Recommended:** PHP 8.0+
  - **Status:** ☐ COMPATIBLE ☐ NEEDS UPGRADE ☐ MULTIPLE VERSIONS AVAILABLE

- [ ] **PHP SAPI:** (FastCGI, FPM, mod_php, etc.)
  - **Type:** ________________
  - **Impact:** ________________

### PHP Extensions
Please confirm availability of these required extensions:

| Extension | Required? | Available? | Version/Notes |
|-----------|-----------|-----------|---|
| **PDO** | YES | ☐ | `php -m \| grep pdo` |
| **PDO_MySQL** | YES | ☐ | For database access |
| **OpenSSL** | YES | ☐ | For secure connections |
| **Curl** | NO (optional) | ☐ | For future API integrations |
| **JSON** | YES | ☐ | For data processing |
| **Filter** | YES | ☐ | For input validation |
| **Session** | YES | ☐ | For user sessions |

- [ ] **Other extensions installed?** ________________
- [ ] **Any disabled extensions that might conflict?** ________________

### PHP Settings (Check via phpinfo() or cPanel)
- [ ] **Upload Max Filesize:** ________________
  - **Minimum Required:** 10 MB
  - **Status:** ☐ ADEQUATE ☐ TOO LOW ☐ ADJUSTABLE

- [ ] **Post Max Size:** ________________
  - **Minimum Required:** 10 MB
  - **Status:** ☐ ADEQUATE ☐ TOO LOW ☐ ADJUSTABLE

- [ ] **Memory Limit:** ________________
  - **Minimum Required:** 128 MB
  - **Recommended:** 256 MB
  - **Status:** ☐ ADEQUATE ☐ TOO LOW ☐ ADJUSTABLE

- [ ] **Max Execution Time:** ________________
  - **Minimum Required:** 30 seconds
  - **For imports:** 60-300 seconds recommended
  - **Status:** ☐ ADEQUATE ☐ TOO LOW ☐ ADJUSTABLE

- [ ] **Display Errors:** ☐ ON (production risk) ☐ OFF (preferred)
- [ ] **Error Reporting:** Currently set to: ________________

### PHP Configuration Method
- [ ] **php.ini accessible:** ☐ YES ☐ NO (via cPanel)
- [ ] **User can modify php.ini:** ☐ YES ☐ NO
- [ ] **Alternative (MultiPHP or .htaccess):** ________________

**Why Needed:** To ensure environment supports application features and to identify any configuration limits for file uploads or long-running processes (imports).

---

## Section 4: MySQL/MariaDB Database

### Database Service
- [ ] **Database Service Installed:** ☐ MySQL ☐ MariaDB ☐ UNKNOWN
  - **Version:** ________________
  - **Minimum Required:** MySQL 5.7+ or MariaDB 10.3+
  - **Status:** ☐ COMPATIBLE ☐ NEEDS UPGRADE

- [ ] **Database Host:** 
  - **Local (localhost):** ☐ YES ☐ NO
  - **Remote Host:** ________________
  - **Port (if non-standard):** ________________

### Database Privileges
- [ ] **Can create new databases:** ☐ YES ☐ NO
- [ ] **Database limit per account:** ________________
  - **Current databases in use:** ________________

- [ ] **Can create database users:** ☐ YES ☐ NO
- [ ] **Database user limit:** ________________
  - **Current users in use:** ________________

### Current Databases (IMPORTANT - Do NOT modify!)
**Existing Production Databases (Protected):**
- [ ] **Warehouse Inventory System Database:** 
  - **Name:** ________________
  - **Size:** ________________
  - **Tables:** ________________
  - **Status:** ☐ PRODUCTION ☐ DO NOT TOUCH

- [ ] **Other databases in use:**
  - **Name/Purpose:** ________________
  - **Name/Purpose:** ________________

### WPMS New Database Details
- [ ] **Proposed WPMS Database Name:** `wpms_prod`
  - **Alternative Name (if needed):** ________________
  - **Name Available?** ☐ YES ☐ NO

- [ ] **Proposed WPMS DB User:** `wpms_user`
  - **User Available?** ☐ YES ☐ NO
  - **Alternative User (if needed):** ________________

- [ ] **Proposed Database Size Estimate:** 
  - **10,000 employee records:** ~20 MB
  - **1 year of task history:** ~100-200 MB
  - **With backups:** ~500 MB recommended
  - **Available Space:** ________________

### Database Character Set
- [ ] **Default Character Set:** ________________
  - **WPMS Requirement:** UTF-8 (utf8mb4)
  - **Status:** ☐ COMPATIBLE ☐ DIFFERENT ☐ ADJUSTABLE

**Why Needed:** To create a separate WPMS database with appropriate privileges, avoiding conflicts with existing production database.

---

## Section 5: HTTPS & Security

### SSL/TLS Certificate
- [ ] **HTTPS Enabled for asmrcctvtechnologies.com:** ☐ YES ☐ NO
  - **Certificate Type:** ☐ Free (Let's Encrypt) ☐ Paid ☐ UNKNOWN
  - **Auto-renewal:** ☐ YES ☐ NO
  - **Expiration Date:** ________________

- [ ] **Subdomain Support:** WPMS will use same domain with path:
  - **URL:** `https://asmrcctvtechnologies.com/warehouse-productivity/`
  - **Certificate covers this path?** ☐ YES ☐ N/A (wildcard)

- [ ] **SSL Redirect (HTTP → HTTPS):** ☐ ENABLED ☐ NOT ENABLED ☐ UNKNOWN

### Security Features
- [ ] **ModSecurity/WAF available:** ☐ YES ☐ NO
- [ ] **.htaccess support (for security rules):** ☐ YES ☐ NO
- [ ] **Directory protection available:** ☐ YES ☐ NO
- [ ] **IP whitelisting available:** ☐ YES ☐ NO

**Why Needed:** To ensure HTTPS works correctly and to configure security headers and access restrictions.

---

## Section 6: Server Resources & Performance

### Server Resources
- [ ] **Server Type:** ☐ Shared ☐ VPS ☐ Dedicated ☐ UNKNOWN
- [ ] **Concurrent Connections Limit:** ________________
- [ ] **CPU Core Count:** ________________
- [ ] **RAM Available:** ________________
- [ ] **Disk Space Available:** ________________
  - **After WPMS deployment:** ________________ (estimate)

### Performance
- [ ] **Average Response Time (existing apps):** ________________
- [ ] **Peak Traffic Hours:** ________________
- [ ] **Expected WPMS Users Concurrent:** ________________
  - **Estimate:** 5-20 (warehouse staff using simultaneously)

### Backups
- [ ] **Automated Backups Available:** ☐ YES ☐ NO
  - **Frequency:** ☐ Daily ☐ Weekly ☐ UNKNOWN
  - **Retention:** ________________ days
  - **Cost:** ☐ Included ☐ Extra charge

- [ ] **Backup Location:** ☐ Same server ☐ Remote ☐ UNKNOWN
- [ ] **User can request manual backup:** ☐ YES ☐ NO
- [ ] **User can restore from backup:** ☐ YES ☐ NO

**Why Needed:** To understand resource constraints and confirm backup strategy is in place.

---

## Section 7: Email & Communications (Optional, Phase 2)

### Email Service
- [ ] **SMTP Available:** ☐ YES ☐ NO
- [ ] **SMTP Host:** ________________
- [ ] **SMTP Port:** ________________ (typically 587 or 465)
- [ ] **Authentication Required:** ☐ YES ☐ NO
- [ ] **TLS/SSL Support:** ☐ YES ☐ NO

### Outgoing Email
- [ ] **Sending Email as:** ________________ (PHP mail function)
- [ ] **Email Relay Available:** ☐ YES ☐ NO
- [ ] **SPF/DKIM Configured:** ☐ YES ☐ NO ☐ UNKNOWN

**Note:** Email notifications deferred to Phase 2. This section is for future reference.

---

## Section 8: Existing Applications & Conflicts

### Existing Warehouse Inventory System
- [ ] **Current Location:** ________________
  - **URL:** ________________
  - **Database:** ________________ (confirmed in Section 4)
  - **Technology:** ☐ PHP ☐ Other: ________________
  - **Status:** ☐ PRODUCTION ☐ STABLE ☐ REQUIRES SUPPORT

- [ ] **Admin Contact:** ________________
  - **Email:** ________________
  - **Availability for backup/coordination:** ________________

### File/Directory Conflicts
- [ ] **Directory `/warehouse-productivity/` exists:** ☐ YES ☐ NO
  - **Current contents (if exists):** ________________
  - **Can be moved/renamed?** ☐ YES ☐ NO

- [ ] **Any other relevant directories in public_html:** ________________

### Potential Conflicts
- [ ] **Rewrite rules (.htaccess):** Current rules in public_html: ________________
  - **Conflict with `/warehouse-productivity/`?** ☐ NO ☐ MAYBE ☐ YES

- [ ] **cron jobs currently running:** ________________
  - **Relevant to WPMS?** ☐ NO ☐ POSSIBLE ☐ YES

**Why Needed:** To ensure WPMS deployment doesn't disrupt existing production systems.

---

## Section 9: Administrative Access & Maintenance

### cPanel Access
- [ ] **User has full cPanel access:** ☐ YES ☐ NO
- [ ] **Subaccount restrictions:** ________________

- [ ] **File Manager Access:** ☐ YES ☐ NO
- [ ] **Database Management (phpMyAdmin):** ☐ YES ☐ NO
- [ ] **Email Account Creation:** ☐ YES ☐ NO

### Maintenance Windows
- [ ] **Scheduled Maintenance Windows:** ________________
  - **Day/Time:** ________________
  - **Duration:** ________________
  - **Notification in advance:** ☐ YES ☐ NO

- [ ] **Can perform backups during business hours:** ☐ YES ☐ NO
- [ ] **Can restart services if needed:** ☐ YES ☐ NO

**Why Needed:** To schedule deployment and backups appropriately and understand maintenance constraints.

---

## Section 10: Support & Service Level

### Support Options
- [ ] **Email Support:** ☐ YES - Response time: ________________
- [ ] **Phone Support:** ☐ YES - Hours: ________________
- [ ] **Ticket System:** ☐ YES - URL: ________________
- [ ] **Emergency Support (24/7):** ☐ YES ☐ NO

### Support Contact Information
- [ ] **Primary Contact:** ________________
- [ ] **Email:** ________________
- [ ] **Phone:** ________________

**Why Needed:** To have escalation path if deployment issues arise.

---

## Action Items for User

### IMMEDIATE (Before Week 1 starts - Oct 24)

1. **Gather Z.com Account Information**
   - Collect cPanel access details
   - Document current file/directory structure
   - List all databases and users in use

2. **Verify PHP Configuration**
   - Check PHP version (access phpinfo or cPanel)
   - Confirm PDO extension availability
   - Note upload/execution limits

3. **Verify Database Service**
   - Confirm MySQL/MariaDB version
   - Check available space and user limits
   - Identify existing Warehouse Inventory System database (DO NOT MODIFY)

4. **Notify Hosting Support** (Optional but helpful)
   - Let them know WPMS deployment is planned for late November
   - Ask them to ensure backups are active
   - Confirm they can assist with any issues

### BY END OF WEEK 1 (Oct 28)

5. **Complete This Checklist**
   - Fill in all sections above
   - Note any limitations or constraints
   - Share with Agent 6 for verification

6. **Provide Access to Agent 6**
   - Share cPanel credentials (securely) or
   - Share read-only account access or
   - Document steps for Agent 6 to verify independently

---

## Verification by Agent 6

### Week 1 Verification Tasks (Oct 24-28)

Agent 6 will:
- [ ] Access Z.com cPanel or review documentation provided
- [ ] Verify all information in this checklist
- [ ] Identify any limitations or compatibility issues
- [ ] Document findings in: `docs/ZCOM_ENVIRONMENT_REPORT.md`
- [ ] Prepare mitigation strategies for any constraints
- [ ] Confirm WPMS deployment is feasible
- [ ] Report to Lead Architect for Week 1 checkpoint approval

### Expected Output: ZCOM_ENVIRONMENT_REPORT.md

Document will include:
- ✅ Confirmed environment specifications
- ✅ Any limitations and workarounds
- ✅ Deployment readiness assessment
- ✅ Potential risks and mitigations
- ✅ Deployment procedure (customized for Z.com environment)

---

## Risk Assessment Matrix

| Item | Impact | Mitigation |
|------|--------|-----------|
| **PHP version < 7.4** | HIGH | Request upgrade or deploy to alternative host |
| **PDO extension unavailable** | CRITICAL | Request cPanel to enable or deploy elsewhere |
| **Upload limit < 5MB** | MEDIUM | Request increase or split large imports |
| **No HTTPS** | CRITICAL | Not acceptable for production; requires SSL |
| **Database space < 500MB** | MEDIUM | Monitor usage, implement cleanup policies |
| **Warehouse Inventory System conflicts** | HIGH | Careful deployment, separate database required |
| **No backups configured** | HIGH | Demand backup setup before deployment |

---

## Approval Checklist (For Lead Architect)

After Agent 6 completes verification:

- [ ] Environment specifications confirmed
- [ ] No critical compatibility issues
- [ ] Deployment path available and clear
- [ ] Database can be created separately
- [ ] Existing Warehouse Inventory System protected
- [ ] Backup strategy in place
- [ ] HTTPS configured
- [ ] Support available for any issues
- **Decision:** ☐ APPROVED FOR DEPLOYMENT ☐ REQUIRES RESOLUTION ☐ DEPLOY TO ALTERNATIVE HOST

---

**Document Status:** Awaiting User Input  
**Next Step:** Complete this checklist and provide to Agent 6  
**Timeline:** Must be completed by Oct 28 for Week 1 checkpoint approval

---

## Quick Reference: What We Need

**TL;DR — Just tell us:**

1. Z.com cPanel login/access method
2. Current public_html structure
3. PHP version installed
4. MySQL/MariaDB version
5. Existing databases (especially Warehouse Inventory System name)
6. Available disk space
7. HTTPS status for asmrcctvtechnologies.com
8. Where `/warehouse-productivity/` directory should go

**Once we have this, we can confirm deployment is possible and prepare the deployment package.**
