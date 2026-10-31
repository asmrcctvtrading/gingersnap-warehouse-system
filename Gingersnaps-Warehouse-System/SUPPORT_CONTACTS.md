# WPMS Support Contacts & Escalation
**Version:** 1.0  
**Date:** October 24, 2026  
**Purpose:** Emergency contacts and support procedures  

---

## Primary Support Contacts

### Level 1: System Administrator (First Point of Contact)

**Name:** ___________________________________  
**Title:** System Administrator  
**Email:** ___________________________________  
**Phone:** ___________________________________  
**Mobile:** ___________________________________  

**Availability:** 8:00 AM - 5:00 PM, Monday-Friday  
**Response Time:** 1-2 hours (business hours)  
**Backup During Vacation:** [Name, Phone, Email]  

**Handles:** User account issues, password resets, permissions, basic troubleshooting

---

### Level 2: IT Manager (Escalation)

**Name:** ___________________________________  
**Title:** IT Manager  
**Email:** ___________________________________  
**Phone:** ___________________________________  
**Mobile:** ___________________________________  

**Availability:** 8:00 AM - 5:00 PM, Monday-Friday  
**Response Time:** <1 hour (business hours)  
**For:** Critical system issues, escalations, emergency decisions

**Handles:** Application errors, database issues, security incidents, major outages

---

### Level 3: Project Owner (Authorization)

**Name:** ___________________________________  
**Title:** [Title]  
**Email:** ___________________________________  
**Phone:** ___________________________________  

**Availability:** [Specify]  
**Response Time:** [Specify]  

**Handles:** Decisions about rollbacks, major changes, budget for upgrades

---

## Hosting Provider Support

### Z.com Hosting Support

**Provider:** Z.com  
**Support Portal:** https://z.com/support  
**Email Support:** support@z.com  
**Phone Support:** [To be obtained from Z.com account]  
**Account Number:** [Your Z.com account number]  
**Domain:** asmrcctvtechnologies.com  

**Availability:** [Z.com support hours]  
**Response Time:** [Typical response time]  
**Emergency Support (24/7):** [Yes/No - check account level]

**For:** Server issues, database service problems, HTTPS certificate issues, file access problems, cPanel access

---

## Database Administrator (If Different from IT Manager)

**Name:** ___________________________________  
**Title:** Database Administrator  
**Email:** ___________________________________  
**Phone:** ___________________________________  
**Mobile:** ___________________________________  

**Availability:** [Specify]  
**Response Time:** [Specify]  

**Handles:** Database backups/recovery, performance tuning, schema modifications, complex SQL issues

---

## On-Call Rotation (For Emergencies)

**During Business Hours (8 AM - 5 PM Mon-Fri):**
- Primary: System Administrator (see above)
- Escalate to: IT Manager if no response after 30 minutes

**After Hours (5 PM - 8 AM & Weekends):**
- On-Call Schedule: [To be determined]
- Contact: [On-call engineer phone/email]
- Or: Call hosting provider emergency line

**On-Call Calendar:**
```
Week of [date]: [Name] on call
Week of [date]: [Name] on call
Week of [date]: [Name] on call
```

---

## Support Request Procedures

### For Non-Emergency Issues (During Business Hours)

1. **Email Support**
   - To: IT Administrator email (above)
   - Subject: "WPMS: [Issue Type] - [Brief Description]"
   - Include: Error message, steps to reproduce, who affected
   - Expected Response: 1-2 hours

2. **Example Email**
   ```
   Subject: WPMS: Login Issue - User Cannot Access System
   
   Body:
   User: John Smith (john.smith@company.com)
   Issue: Cannot log in, gets "Invalid credentials" error
   When: Started today at 9:30 AM
   Other users: Can log in fine
   Password reset: Tried, still getting error
   
   Can you please investigate and reset if needed?
   ```

### For Critical Issues (System Down)

1. **Call Immediately**
   - Phone: IT Manager (see above)
   - Or: System Administrator
   - Explain: What's down, who affected, business impact

2. **Provide Details**
   - What are users seeing? (error message, blank page, etc.)
   - What functions affected? (login, tasks, reports, etc.)
   - How many users affected? (1 person, department, whole company)
   - When did it start? (exact time)
   - What changed? (deployment, update, etc.)

3. **Meanwhile**
   - Don't keep trying - may make worse
   - Have backup procedures ready (paper forms, etc.)
   - Communicate to affected users
   - Wait for support team direction

### For Security Issues (Potential Breach)

1. **Immediate Actions**
   - Call IT Manager RIGHT NOW
   - Do NOT investigate further (may destroy evidence)
   - Block public access if instructed
   - Keep system running (for logs)

2. **Information to Provide**
   - What suspicious activity occurred?
   - When did you notice it? (exact time)
   - What data was accessed? (best guess)
   - Any unusual activity in logs?
   - Screenshot of error/suspicious content

3. **Next Steps**
   - IT Manager will initiate incident response
   - May contact hosting provider
   - Will preserve logs for investigation
   - Will notify management
   - Will develop recovery plan

---

## Support Communication Methods

### Email Support
- **Best for:** Non-urgent issues, documentation requests
- **Response Time:** 1-2 hours (business hours)
- **Format:** Plain text or structured information
- **Include:** Error messages, screenshots, steps to reproduce

### Phone Support
- **Best for:** Critical issues, urgent problems, complex issues
- **Response Time:** 15-30 minutes (business hours)
- **When:** Business hours 8 AM - 5 PM weekdays
- **Emergency:** After hours call cell phone (listed in Level 2 contact)

### In-Person Support
- **Best for:** Major deployment issues, training, hands-on troubleshooting
- **Location:** [Your office location]
- **Scheduling:** Email or phone to request

### Remote Support
- **Best for:** Complex troubleshooting requiring screen share
- **Method:** TeamViewer, AnyDesk, or Zoom
- **Scheduling:** Via email, typically next day

---

## Issue Priority Levels

### Priority 1: CRITICAL (Response: 15 minutes)

**Definition:** System completely down, all users affected, business-critical functions unavailable

**Examples:**
- Application not accessible at all
- Database completely unreachable
- All users locked out
- Security breach in progress

**Action:** Immediately contact IT Manager by phone

---

### Priority 2: HIGH (Response: 1 hour)

**Definition:** Significant impact, multiple users affected, critical function degraded

**Examples:**
- Login broken for many users
- Reports not generating
- Dashboard unavailable
- Data integrity issues
- Performance severely degraded

**Action:** Call IT Manager or email with urgency flag

---

### Priority 3: MEDIUM (Response: 4 hours)

**Definition:** Limited impact, few users affected, non-critical functions affected

**Examples:**
- One user can't log in
- Export timing out
- Filter not working correctly
- One feature showing errors
- Slow performance

**Action:** Email support team with details

---

### Priority 4: LOW (Response: Next business day)

**Definition:** Minimal impact, workaround available, cosmetic issues

**Examples:**
- Help text unclear
- Formatting issue in report
- Suggestion for improvement
- Documentation question
- Password reset needed

**Action:** Email support team, can be handled at convenience

---

## Service Level Agreements (SLAs)

| Priority | Response Time | Resolution Target | Escalation |
|----------|---------------|-------------------|------------|
| Critical | 15 minutes | 2 hours | IT Manager → Project Owner |
| High | 1 hour | 4 hours | IT Manager → Project Owner |
| Medium | 4 hours | 24 hours | IT Manager → Level 2 |
| Low | 24 hours | 3 days | IT Manager |

---

## Escalation Procedures

### When to Escalate

Escalate if:
- Initial contact unresponsive after 30 min
- Priority 1 issue not resolved in 2 hours
- Priority 2 issue not resolved in 4 hours
- Issue requires budget decision
- Rollback needed
- Involves sensitive security matter

### Escalation Chain

1. **Initial Contact:** System Administrator
   - Wait: 30 minutes (business hours)
   - If no response → Escalate

2. **First Escalation:** IT Manager
   - Wait: 1 hour
   - If not resolved → Escalate

3. **Final Escalation:** Project Owner
   - Authority: Can make major decisions
   - Action: Rollback, external contractor, emergency upgrade, etc.

4. **External:** Hosting Provider (Z.com)
   - If server-level issue
   - IT Manager contacts support
   - Reference: Account number, domain

---

## Backup Contact Protocol

**If Primary Contact Unavailable:**

1. Call next person on escalation chain
2. Leave voicemail with:
   - Issue priority level
   - Situation summary
   - Your contact number
   - Callback time needed
3. Send email with same information
4. Keep trying until someone responds

**Critical Issues (24/7):**
- Don't wait for business hours
- Call emergency on-call number
- Or call Z.com 24/7 support
- Document everything for morning briefing

---

## Contact Information Template

**Print and Post in Visible Location:**

```
═══════════════════════════════════════════
        WPMS SUPPORT QUICK REFERENCE
═══════════════════════════════════════════

SYSTEM DOWN? 
→ Call IT Manager: ____________ (urgent)

PASSWORD RESET?
→ Email Admin: ____________

QUESTION ABOUT USING WPMS?
→ See USER_MANUAL.md or ask your manager

DATABASE ISSUE?
→ Call IT Manager: ____________

SECURITY CONCERN?
→ Call IT Manager IMMEDIATELY: ____________

AFTER HOURS EMERGENCY?
→ Call Z.com Support: ____________
   Reference: asmrcctvtechnologies.com

═══════════════════════════════════════════
        CONTACT CARD (Wallet Size)
═══════════════════════════════════════════

WPMS SUPPORT CONTACTS:

System Admin: ____________
Phone: ____________

IT Manager: ____________  
Phone: ____________

Z.com Support: ____________

Print Date: ____________
```

---

## Knowledge Base & Documentation

### For Users
- **Where to Find:** https://[internal docs site]/WPMS/
- **What's Available:** USER_MANUAL.md, QUICK_START_GUIDE.md
- **Update Frequency:** As features change
- **Who Updates:** IT Administrator

### For Administrators
- **Where to Find:** https://[internal docs site]/WPMS/
- **What's Available:** ADMINISTRATOR_MANUAL.md, TROUBLESHOOTING_GUIDE.md, deployment docs
- **Update Frequency:** Monthly or as needed
- **Who Updates:** IT Administrator

### For Developers (Phase 2+)
- **Where to Find:** GitHub repo or internal wiki
- **What's Available:** Architecture docs, API docs, code comments
- **Update Frequency:** With each release
- **Who Updates:** Development team

---

## Feedback & Improvement

**Have a suggestion for improving WPMS?**
- Email: [IT Manager email]
- Subject: "WPMS Improvement Suggestion: [Topic]"
- Include: Description, impact, priority

**Report a bug?**
- Email: [IT Manager email]
- Subject: "WPMS Bug Report: [Brief Description]"
- Include: Steps to reproduce, error message, screenshot

**Need additional training?**
- Contact: [IT Manager email]
- Request: Individual training, group training, or documentation

---

## Emergency Contacts Quick Reference

```
╔═══════════════════════════════════════════════════════╗
║            WPMS EMERGENCY CONTACTS                   ║
╠═══════════════════════════════════════════════════════╣
║ CRITICAL ISSUE (System Down)                         ║
║ → CALL: [IT Manager Name]                            ║
║    Phone: [Phone Number] IMMEDIATELY                 ║
║                                                       ║
║ DATABASE ISSUE                                        ║
║ → CALL: [DB Admin Name]                              ║
║    Phone: [Phone Number]                             ║
║                                                       ║
║ SECURITY ISSUE                                        ║
║ → CALL: [IT Manager] - DO NOT EMAIL                  ║
║    Phone: [Phone Number] IMMEDIATELY                 ║
║                                                       ║
║ AFTER HOURS EMERGENCY                                ║
║ → CALL: [On-call engineer phone]                     ║
║    Or: Z.com Support [Phone]                         ║
║                                                       ║
╚═══════════════════════════════════════════════════════╝
```

---

## Last Updated

| Date | Changed By | Changes |
|------|-----------|---------|
| 2026-10-24 | Agent 6 | Initial creation |
| [Date] | [Name] | [Changes] |

**Next Review Date:** January 24, 2027

---

**Important:** Print this document and post in visible location.  
**Update:** Review quarterly and update any changed contacts.

