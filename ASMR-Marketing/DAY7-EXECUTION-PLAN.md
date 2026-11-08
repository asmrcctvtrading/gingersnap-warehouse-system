# Day 7 Execution Plan: Automation & CRM Setup
**ASMR CCTV Trading Growth Initiative**
**Date:** 2026-11-06 | **Status:** In Progress

---

## Overview
Day 7 shifts from content/sales scripts (Days 1-6) to **operational systems** that scale execution.
- **Goal:** Set up SMS automation, review request workflows, and basic CRM
- **Timeline:** Single execution cycle (implement by end of Day 7)
- **Success metric:** 3 systems live and tested, leads can flow through automation

---

## Three Core Deliverables

### 1. SMS Automation Setup
**Source:** Section E, MARKETING_STRATEGY_2026.md (5 templates defined)

**Templates to activate:**
- Template 1: Appointment Reminder (pre-install)
- Template 2: Installation Complete (post-install)
- Template 3: Special Offer (Oct special promo)
- Template 4: Re-engagement (dormant leads)
- Template 5: 30-day Follow-up (satisfaction check)

**Setup tasks:**
- Choose SMS platform (Twilio, Semaphore PH, or local alternative)
- Create trigger workflow (incoming lead → welcome SMS)
- Schedule batch sends (promo, re-engagement reminders)
- Test with sample phone number
- Document platform login + API key location

**Owner:** Odyssey (or delegate to Rex for setup)
**Deliverable:** SMS platform account live, 3 templates active, test SMS received

---

### 2. Google & Facebook Review Request Workflow
**Purpose:** Activate social proof (Audit Rec #5, per README.md)

**Setup tasks:**

#### Google Reviews
- Create Google Business Profile (if not exists) — use 110 Gen. Luna Ave, San Mateo, Rizal
- Generate unique review link: `https://g.page/[business-name]/review`
- Add review link to email signature + WhatsApp auto-reply
- SMS template for review request (post-installation)

#### Facebook Reviews
- Enable reviews on Facebook page (settings → reviews tab)
- Post request in Stories/Reels: "Drop a review → help fellow homeowners trust ASMR"
- Create Facebook review link for email/SMS follow-ups

**Triggers:**
- Auto-send 7 days post-installation
- Include in email signature for all outbound comms
- Post monthly reminder on Facebook

**Owner:** Odyssey (or delegate)
**Deliverable:** Review links live, 1 test review submitted, workflow automated

---

### 3. CRM Setup
**Purpose:** Centralize leads, track funnel, automate follow-ups

**Recommended options (by ease):**
1. **HubSpot Free** (best for SME) — 1 user, unlimited contacts, free CRM + email sequences
2. **Pipedrive** (sales-focused) — highly visual pipeline
3. **Google Sheets + Zapier** (if budget/complexity limited)

**Data to import (from Days 1-6):**
- All leads from Facebook messages, DMs, walk-ins
- Contact info: name, phone, email, service interest
- Stage: Awareness → Demo Request → Quote Sent → Negotiating → Won/Lost
- Created date, last contact date
- Notes: pain points, budget, timeline

**Setup scope:**
- Choose + register account
- Create pipeline stages (match sales script flow)
- Set up 2-3 automation rules:
  - New lead → auto-welcome SMS/email
  - 3 days no response → auto-reminder
  - Quote sent → auto-reminder Day 7
- Import test data (5-10 leads)
- Assign user access (Odyssey)
- Document login credentials (secure location)

**Stretch goal:** Connect HubSpot to Facebook Lead Ads (auto-import future leads)

**Owner:** Odyssey (or delegate to Rex)
**Deliverable:** CRM live, sample leads imported, 1 automation rule tested, login credentials secured

---

## Checklist (By Task)

### SMS Automation
- [ ] Platform chosen (Twilio / Semaphore / other)
- [ ] Account registered + verified
- [ ] 5 SMS templates entered into system
- [ ] Triggers configured (e.g., new lead auto-welcome)
- [ ] Test SMS sent and received
- [ ] Team member credentials stored securely

### Google & Facebook Reviews
- [ ] Google Business Profile created/verified
- [ ] Google review link generated + shared
- [ ] Facebook reviews enabled on page
- [ ] Facebook review link created
- [ ] Review request SMS template added
- [ ] Review request email template added
- [ ] 1 test review posted (ask team member)

### CRM Setup
- [ ] CRM tool chosen (HubSpot / Pipedrive / Sheets)
- [ ] Account created + initial setup complete
- [ ] Pipeline stages defined
- [ ] User access assigned (Odyssey)
- [ ] Sample leads imported (5-10)
- [ ] 1 automation rule configured + tested
- [ ] Login credentials secured (e.g., password manager)
- [ ] Handoff doc written (how to use, daily workflow)

---

## Success Criteria
- [ ] SMS automation: Can send manual test SMS from chosen platform
- [ ] Reviews: Both Google + Facebook review links visible and clickable
- [ ] CRM: Dashboard loads, 1 lead visible, can send auto-email
- [ ] Team understands how to use all 3 systems (brief training or doc)

---

## Timeline
**Estimated effort:** 4-6 hours total
- SMS setup: 1-2 hours
- Review workflow: 1 hour
- CRM setup: 2-3 hours

**Start:** Today (2026-11-06)
**Target completion:** Same day or Day 8 morning

---

## Notes
- SMS platform choice depends on budget + local availability (Semaphore strong in PH)
- HubSpot free tier best for small team; no credit card required
- All integrations should be reversible (no destructive actions)
- Keep credentials in secure location (e.g., password manager, not plaintext in docs)
