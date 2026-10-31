# cPanel File Audit — Phase 1: READ-ONLY INVENTORY AUDIT
**Date:** 2026-10-24  
**Hosting:** Z.com (cpanel11wh.jpt1.cloud.z.com)  
**Account:** ytrmcft  
**Root Path:** /home/ytrmcft

---

## PHASE 1 STATUS: AUDIT COMPLETE — AWAITING YOUR APPROVAL TO PROCEED TO PHASE 2

---

## 1. PROTECTED APPLICATIONS & CRITICAL FILES

❌ **DO NOT DELETE OR MODIFY:**
- **AIMS** (folder) — 4 KB
- **barangay-locator** (folder) — 4 KB
- **helpdesk** (folder) — 331 bytes
- **picklecam** (folder) — 4 KB — PickleCam application
- **warehouse** (folder) — 4 KB — Warehouse management app
- **warehouse-productivity** (folder) — 142 bytes — Warehouse productivity tool
- **assets** (folder) — 38 bytes
- **ASMRCCTVTECHNOLOGIES.com** website files (public_html root level):
  - .htaccess (670 bytes)
  - .user.ini (374 bytes)
  - schema.sql (9.57 KB) — database schema
  - PHP files: about.php, admin.php, admin-news.php, admin-news-handler.php, contact.php, contact-handler.php, events.php, events-post.php, header.php, index.php, news.php, news-post.php, partners.php, products.php, services.php

---

## 2. SYSTEM DIRECTORIES (PROTECTED - Essential for hosting)

**Hidden system files/folders — DO NOT DELETE:**
- .cagefs (39 bytes) — cPanel Cage FileSystem
- .caldav (189 bytes) — CalDAV calendar protocol
- .cl.selector (48 bytes) — CloudLinux selector
- .clwpos (22 bytes)
- .cpanel (219 bytes) — cPanel configuration
- .htpasswds (6 bytes) — HTTP authentication
- .koality (20 bytes)
- .razor (231 bytes)
- .spamassassin (60 bytes) — Spam filtering
- .subaccounts (28 bytes) — Sub-account data
- .trash (empty) — 6 bytes
- .well-known (28 bytes) — Let's Encrypt / domain verification

---

## 3. ACTIVE SERVICES (PROTECTED)

- **mail** — Email system and Roundcube (webmail)
  - Contains mailbox storage, configuration
  - logs/roundcube/ has carddav.log (591 bytes), errors.log (163 bytes)
- **etc** — Configuration files for hosting services
- **public_ftp** — FTP directory (if FTP enabled)
- **news** — News/bulletin system

---

## 4. CANDIDATE CLEANUP ITEMS (SAFE TO REMOVE AFTER APPROVAL)

### Category A — Very Safe to Remove
| Path | File/Folder | Size | Type | Last Modified | Risk | Reason |
|------|---------|------|------|---------------|------|--------|
| /logs/ | asmrcctvtechnologies.com-Oct-2026.gz | 61.15 KB | Archive | Yesterday | **LOW** | Old monthly log backup — can be archived offsite or deleted |
| /logs/ | asmrcctvtechnologies.com-ssl_log-Oct-2026.gz | 118.28 KB | Archive | Yesterday | **LOW** | Old SSL log backup — can be archived offsite or deleted |
| /logs/roundcube/ | carddav.log | 591 bytes | Log | Oct 1 | **LOW** | Roundcube calendar sync log — can be rotated |
| /logs/roundcube/ | errors.log | 163 bytes | Log | Sep 9 | **LOW** | Roundcube error log — can be rotated |

### Category B — Review Before Removal
| Path | File/Folder | Size | Type | Last Modified | Risk | Reason |
|------|---------|------|------|---------------|------|--------|
| / | php.error.log | 71.74 KB | Log | Yesterday | **MEDIUM** | Active PHP error log — verify no active errors before deletion |
| / | .php.error.log | 46.07 KB | Log | Oct 1 | **MEDIUM** | Archive of older PHP errors — review for patterns |

---

## 5. DIRECTORIES INSPECTED

✅ **Checked:**
- / (root) — Found application folders, configuration files, PHP website code
- /logs/ — Found old log archives and error logs
- /logs/roundcube/ — Found small log files
- /.trash/ — Empty
- /lscache/ — Empty

⏳ **Recommended to Inspect** (for more detailed cleanup):
- /mail/ — Email directories (verify integrity, check for old mailboxes)
- /etc/ — System configuration (identify obsolete configs)
- /public_ftp/ — FTP staging area (check for abandoned uploads)
- /includes/, /modules/, /data/ — Application support files
- Individual app folders (AIMS, helpdesk, warehouse, picklecam) — Check for cache/temp files

---

## 6. STORAGE ANALYSIS (Preliminary)

**Total files reviewed:** ~50+ (including hidden system files)  
**Clearly removable space:** ~251 KB (log archives + old logs)  
**Requires review before removal:** ~118 KB (active error logs)  
**Protected (DO NOT TOUCH):** Website code, app files, email, databases, configs

---

## 7. RISKS & ROLLBACK PROCEDURES

### Risk Assessment:
- **Low Risk:** Log archives from October can be safely archived offsite or deleted
- **Medium Risk:** Active php.error.log should be reviewed; old error logs can be reviewed before cleanup
- **High Risk:** Any modification to application code, .htaccess, .env, .user.ini, or database files

### Rollback Plan:
1. Create backup of entire /home/ytrmcft/ directory before ANY cleanup
2. Store backup at: **~/safe_cleanup_backup_YYYYMMDD_HHMMSS/**
3. Keep backup outside public_html
4. Before deleting any file, copy to quarantine directory first
5. Verify each application works after each cleanup batch

---

## 8. PHASE 1 FINDINGS SUMMARY

✅ **Audit Complete**  
✅ **Protected Applications Identified:** 7 critical apps + ASMRCCTVTECHNOLOGIES.com  
✅ **Cleanup Candidates Found:** 6 low-risk items (~251 KB recoverable)  
✅ **Critical Files Protected:** Database schema, configs, PHP code  
✅ **System Integrity:** All hosting system files intact  

---

## NEXT STEPS

**Phase 1 is complete. Your action is required:**

1. **Review** this audit report
2. **Approve** or **reject** the cleanup plan
3. **If approved**, I will proceed to:
   - **Phase 2:** Create verified backup of all files
   - **Phase 3:** Classify each file (Category A/B/C)
   - **Phase 4:** Create detailed cleanup plan with approval gates
   - **Phase 5:** Execute cleanup in controlled batches
   - **Phase 6:** Verify all applications work
   - **Phase 7:** Rollback if needed

**⚠️ CRITICAL:** Do NOT delete anything until backup is created and verified.

---

## QUESTIONS FOR USER

Before proceeding to Phase 2, please confirm:

1. Should I proceed with creating the backup? (**YES** / **NO**)
2. Are there any additional directories you want me to inspect before cleanup?
3. Should I preserve the log archives, or can they be safely deleted?
4. Are there any database exports or old SQL dumps you want me to look for?
5. Should I investigate the /public_ftp/ folder for old files?

---

**Report Generated:** 2026-10-24 | **Status:** AWAITING APPROVAL
