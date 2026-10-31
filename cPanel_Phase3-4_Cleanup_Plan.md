# cPanel File Audit — Phase 3-4: CLEANUP CLASSIFICATION & PLAN
**Date:** 2026-10-24  
**Status:** AWAITING USER APPROVAL TO PROCEED TO PHASE 5 (CLEANUP EXECUTION)

---

## PHASE 2 BACKUP STATUS: ✅ INITIATED

- **Backup Type:** Full Account Backup
- **Destination:** Home Directory (/home/ytrmcft/)
- **Contents:** All files, databases, email configs, forwarders, filters
- **Notification:** Email to asmrcctvandpc@gmail.com upon completion
- **Recovery:** Backup can be restored if any issues occur during cleanup
- **Timeline:** You will receive email when backup is ready
- **Next Step:** Wait for email confirmation, then approve Phase 5 cleanup

---

## PHASE 3: CLEANUP CANDIDATE CLASSIFICATION

### CATEGORY A — SAFE TO DELETE (Low Risk)
**These files can be safely deleted with minimal risk:**

| File/Path | Size | Type | Last Modified | Reason for Cleanup | Risk |
|---|---:|---|---|---|---|
| /logs/asmrcctvtechnologies.com-Oct-2026.gz | 61.15 KB | Log Archive | Yesterday | Old monthly log backup; new logs are being generated | **LOW** |
| /logs/asmrcctvtechnologies.com-ssl_log-Oct-2026.gz | 118.28 KB | SSL Log Archive | Yesterday | Old SSL log archive; new logs are being generated | **LOW** |

**Subtotal Category A:** ~179 KB recoverable storage

---

### CATEGORY B — REVIEW BEFORE DELETION (Medium Risk)
**These files should be reviewed for specific content before deletion:**

| File/Path | Size | Type | Last Modified | Reason for Caution | Recommendation |
|---|---:|---|---|---|---|
| /logs/php.error.log | 71.74 KB | PHP Error Log | Yesterday | Active error log; review for critical errors before deletion | Review errors, then delete or archive |
| /logs/roundcube/carddav.log | 591 bytes | Roundcube Log | Oct 1 | Email system log; safe to rotate/delete after review | Review for errors, then rotate |
| /logs/roundcube/errors.log | 163 bytes | Roundcube Log | Sep 9 | Email system log; archive before deletion | Review, then delete |
| /logs/.php.error.log | 46.07 KB | Old PHP Error Archive | Oct 1 | Historical error log; review for patterns | Review, then delete if no issues found |

**Subtotal Category B:** ~118 KB (requires human review)

---

### CATEGORY C — NEVER DELETE (Protected)
**Critical files that must be preserved:**

#### Protected Applications & Code:
- ✅ AIMS (folder) — business application
- ✅ barangay-locator (folder) — application
- ✅ helpdesk (folder) — support system
- ✅ picklecam (folder) — PickleCam app (active project)
- ✅ warehouse (folder) — warehouse management (active project)
- ✅ warehouse-productivity (folder) — productivity tool
- ✅ assets (folder) — application assets
- ✅ ASMRCCTVTECHNOLOGIES.com website files (all PHP files, HTML, CSS, JS)

#### Critical Configuration Files:
- ✅ .htaccess — web server configuration
- ✅ .user.ini — PHP configuration
- ✅ schema.sql — database schema
- ✅ All database files and exports
- ✅ All email system files (mail/ directory)

#### System & Infrastructure:
- ✅ .cagefs, .caldav, .cpanel, .htpasswds, .koality, .razor, .spamassassin, .subaccounts — System files
- ✅ .well-known — Let's Encrypt / domain verification
- ✅ etc, logs, lscache, public_ftp/incoming — System directories
- ✅ news, modules, includes, data — Support directories

---

## PHASE 4: DETAILED CLEANUP PLAN

### Cleanup Strategy:
1. **Safe Removal (Category A):** Delete old log archives immediately (very low risk)
2. **Conditional Review (Category B):** Review error logs for critical issues, then delete
3. **Never Touch (Category C):** Leave all protected files completely untouched

### Implementation Timeline:

#### Step 1: Delete Category A Files (Very Safe)
**Action:** Delete old log archives (safe cleanup)

```
Files to delete:
- /logs/asmrcctvtechnologies.com-Oct-2026.gz (61.15 KB)
- /logs/asmrcctvtechnologies.com-ssl_log-Oct-2026.gz (118.28 KB)

Expected outcome: Free ~179 KB of space
Risk level: LOW (old archives, new ones exist)
```

#### Step 2: Review & Clean Category B Files (Requires Review)
**Action:** Examine error logs, then clean up

```
Files to review:
- /logs/php.error.log (71.74 KB) - Check for critical errors
- /logs/.php.error.log (46.07 KB) - Old archive
- /logs/roundcube/carddav.log (591 bytes) - Roundcube system log
- /logs/roundcube/errors.log (163 bytes) - Roundcube system log

Recommended action:
1. Download /logs/php.error.log and review for critical errors
2. If no critical errors found, delete all error logs
3. Expect to free ~118 KB of space
Risk level: MEDIUM (requires human verification)
```

#### Step 3: Verification
**Action:** Verify all applications still work after cleanup

```
Test after cleanup:
1. ASMRCCTVTECHNOLOGIES.com website — verify homepage loads
2. AIMS application — verify login and basic functions
3. PickleCam — verify access and functionality
4. Warehouse — verify access and operations
5. Email — verify webmail (Roundcube) still works
6. Check disk usage — verify space recovered

Expected: All should work normally, ~297 KB space recovered
```

---

## STORAGE RECOVERY SUMMARY

| Category | Safeness | Size | Action |
|---|---|---:|---|
| A (Very Safe) | ✅ DELETE | 179 KB | Proceed immediately |
| B (Needs Review) | ⚠️ REVIEW THEN DELETE | 118 KB | Review + delete after approval |
| **TOTAL RECOVERABLE** | | **~297 KB** | |
| **NOT DELETED (Protected)** | ✅ PRESERVED | ~4 MB+ | Applications, configs, data |

---

## ROLLBACK PROCEDURES

If any problem occurs after cleanup:

1. **Restore from backup** — Use cPanel's Restore function
2. **Restore specific files** — Copy back from backup directory
3. **Rollback steps:**
   - Stop all application traffic if needed
   - Restore affected files from backup
   - Verify application functionality
   - Check error logs for issues

---

## CRITICAL SAFETY CHECKPOINTS

✅ **Before cleanup:**
- [ ] Backup is complete (email received from cPanel)
- [ ] Backup is verified and accessible
- [ ] All protected apps listed in Category C
- [ ] Error logs reviewed for critical errors

✅ **During cleanup:**
- [ ] Delete only approved files (Category A + reviewed Category B)
- [ ] Never delete anything in Category C
- [ ] Keep manifest of all deleted files
- [ ] Stop immediately if any error occurs

✅ **After cleanup:**
- [ ] Test all applications work correctly
- [ ] Verify no 404 or database errors
- [ ] Confirm disk space recovered
- [ ] Document final status

---

## YOUR APPROVAL NEEDED

**Before proceeding to Phase 5 (Cleanup Execution), please confirm:**

1. ✅ **Do you want to delete Category A files?**
   - YES (delete old log archives)
   - NO (keep all logs)

2. ✅ **Do you want to review and delete Category B error logs?**
   - YES (I will review errors first)
   - NO (keep all error logs)
   - MAYBE (depends on error content)

3. ✅ **Backup status:**
   - Have you received the backup email from cPanel?
   - Can you confirm the backup file is accessible?

4. ✅ **Are you ready for Phase 5 cleanup?**
   - YES (proceed with cleanup)
   - NO (wait/stop)
   - READY (after backup is confirmed)

---

## NEXT STEPS

1. **Your action:** Receive backup email from cPanel, verify backup is ready
2. **Your decision:** Approve which files to delete (Category A, B, or both)
3. **My action (Phase 5):** Execute cleanup in controlled batches
4. **My action (Phase 6):** Verify all apps work after cleanup
5. **My action (Phase 7):** Report final status and storage recovered

---

**Status:** AWAITING USER APPROVAL  
**Backup:** IN PROGRESS (Phase 2)  
**Ready for Phase 5:** YES (after user approval)

Contact: asmrcctvandpc@gmail.com — Backup notification email will arrive here
