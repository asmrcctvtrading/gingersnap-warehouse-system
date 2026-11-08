# Day 7: Team Operations Guide
**ASMR CCTV Trading Automation Systems**  
**For:** Odyssey + Sales Team  
**Created:** 2026-11-06 | **Version:** 1.0

---

## Quick Start (5 Minutes)

**Three systems you'll use every day:**

1. **HubSpot CRM** — Tracks all leads + pipeline (dashboard)
2. **Semaphore SMS** — Sends automated text messages
3. **Zapier** — Connects HubSpot → SMS (handles auto-sends)

**Daily workflow (20 minutes):**
- 9:00 AM: Check HubSpot for new leads
- 9:15 AM: Update pipeline stages (who's ready for demo? who needs follow-up?)
- 9:30 AM: Semaphore sends any scheduled SMS automatically
- 3:00 PM: Check for responses, log in HubSpot, reply to messages

**Cost:** ₱280–560/month (SMS only; CRM is free)

---

## System 1: HubSpot CRM Dashboard

### What Is It?
Central hub for ALL leads. One place to see:
- New inquiries (from Facebook, website, walk-ins)
- Which stage each lead is in (Awareness → Demo → Quote → Won)
- Who needs follow-up today
- Email/call history with each lead

### Login
```
URL: hubspot.com
Email: inquiries@asmrcctvtechnologies.com
Password: [stored in password manager, not here]
```

### Daily Tasks

#### Task 1: Check New Leads (9:00 AM)
1. Log into HubSpot dashboard
2. Click **"Contacts"** (left menu)
3. Look for contacts with **stage = "Awareness"** or **"Recently Added"**
4. Review:
   - Name + phone number
   - Service interest (e.g., "4-camera home system")
   - How they found you (source tag)

**Action:** If no phone/email, send message requesting contact info. Add tag: "Incomplete Contact Info"

#### Task 2: Update Pipeline (9:15 AM)
1. Click **"Deals"** (left menu)
2. View the **"Sales Pipeline"** board (cards move left-to-right)
3. Move deals based on what happened yesterday:
   - **Demo scheduled?** → Move to "Demo Requested"
   - **Quote sent?** → Move to "Quote Sent"
   - **Customer agreed?** → Move to "Won"
   - **Not interested?** → Move to "Lost"

**Example:**
```
BEFORE (yesterday):
• Juan Dela Cruz | Stage: "Awareness" | Note: "Called about 4-camera system"

TODAY (after follow-up call):
• Juan Dela Cruz | Stage: "Demo Requested" | Note: "Demo scheduled for Saturday 2 PM"
```

#### Task 3: Log Follow-ups
1. Click on a deal card
2. Scroll to **"Activities"** section
3. Click **"Log activity"**
4. Add:
   - **Type:** Phone call / Email / SMS / Message
   - **Notes:** What happened ("Sent quote P32k system", "Customer has questions about warranty")
   - **Date/Time:** When it happened
5. Click **"Save"**

**Why:** Helps team see history. When you're off, someone else can continue the conversation.

#### Task 4: Use Filters to Find Urgent Leads
1. Click **"Deals"** 
2. Click **"Filter"** button (top)
3. Add filter: **"Last activity > 3 days ago"** 
   - Shows leads you haven't touched in 3+ days (need follow-up)
4. Or filter: **"Stage = Quote Sent AND Last activity > 7 days ago"**
   - Shows quotes that are getting stale (send reminder)

**Action:** Send SMS or call these leads today.

---

### Email Tracking (Bonus Feature)
When you send quote via email in HubSpot:
- System tracks if customer **opened** the email
- You see exactly **when** they read it
- You see **which links** they clicked

**How:** Click "Send email" in HubSpot → system adds tracking. You see a check mark (✓ opened) in deal activity.

**Use case:** If quote sits unopened for 7 days, send SMS: "Haven't seen the quote yet? Questions?"

---

## System 2: Semaphore SMS Platform

### What Is It?
Simple SMS dashboard. Send text messages to customers (for follow-ups, reminders, promotions).

### Login
```
URL: semaphore.co
Email: inquiries@asmrcctvtechnologies.com
Password: [stored in password manager]
```

### Cost Per SMS
```
₱0.56 per SMS (VAT not included)
Example: 
• 500 SMS = ₱280/month
• 1000 SMS = ₱560/month
```

### Daily Tasks

#### Task 1: View Remaining SMS Credits
1. Log in to Semaphore
2. Top right corner shows **"Credits: ₱450"** (example)
3. If below ₱100 → Add more credit (buy ₱500 package)

#### Task 2: Send Single SMS (Manual Follow-up)
1. Dashboard → **"Send SMS"** button
2. Enter:
   - **Phone number:** +63-XXXXXXXXX (include +63 for PH)
   - **Template:** Choose from dropdown OR type custom message
   - **Preview:** Shows how message looks
3. Click **"Send Now"** or **"Schedule for later"** (e.g., 9 AM tomorrow)
4. Done! SMS shows in **"Outbox"** tab

**Example:**
```
Phone: +63-9171234567
Message: "Hi Juan! Following up on your quote for 4-camera system. Any questions? Call us: 0987-654-3210. -ASMR CCTV"
```

#### Task 3: Send Batch SMS (Promotion to Many People)
1. Dashboard → **"Broadcast"** or **"Batch Send"**
2. Upload CSV file OR paste phone numbers (one per line)
3. Choose **template** (see list below)
4. Preview message
5. **IMPORTANT:** Get approval from Odyssey before hitting "Send" for bulk SMS
6. Click **"Schedule"** (usually Tuesday or Thursday, 9–10 AM for best response)
7. Watch **"Reports"** tab to see delivery status

**Common broadcasts:**
- "October special: Free 3-month cloud storage! Book now: [link]"
- "Still interested in CCTV? We lowered prices. Call for quote: [phone]"

#### Task 4: Check Delivery Status
1. Go to **"Reports"** tab
2. See stats:
   - **Sent:** How many SMS went out
   - **Delivered:** How many reached phone (shows delivery rate %)
   - **Failed:** Bad phone numbers or network issues
   - **Response:** How many replies came back

**Target:** >95% delivery rate is normal. <80% means check phone numbers.

#### Task 5: View SMS Templates
Your 5 pre-made templates (from MARKETING_STRATEGY):

1. **Template 1: Appointment Reminder**
   - Use: Day before installation
   - Message: "Hi [Name]! Reminder: Installing your CCTV tomorrow (Monday) at 9 AM. We'll call when 10 min away. Questions? Call [PHONE]. -ASMR"

2. **Template 2: Installation Complete**
   - Use: Immediately after installation
   - Message: "[Name], your CCTV system is live! You should get motion alerts on your phone. Reply TEST to verify. Thanks for trusting ASMR! -Team ASMR"

3. **Template 3: Special Offer**
   - Use: When running promotion
   - Message: "October Special: New customers get 3 months FREE cloud storage! Valid until Oct 31. Book today: [SHORT LINK] or reply INSTALL. -ASMR CCTV"

4. **Template 4: Re-engagement**
   - Use: Lead went silent for 2+ weeks
   - Message: "Hi [Name]! Haven't heard from you in a while. We lowered prices on 4-camera systems to P32k. Still interested? Reply YES or call [PHONE]. -ASMR"

5. **Template 5: 30-day Follow-up**
   - Use: Customer installed 30 days ago
   - Message: "[Name], how's the CCTV system working? Any concerns? Our 30-day satisfaction check is here to help. Reply or call [PHONE]. -ASMR CCTV"

---

## System 3: Zapier Integration

### What Is It?
**Zapier = bridge between HubSpot + Semaphore**

When you move a deal to "Quote Sent" in HubSpot, Zapier automatically sends SMS via Semaphore. No manual work.

### How It Works (Example Flow)

```
YOU: Move deal "Juan Dela Cruz" to "Quote Sent" in HubSpot
       ↓
ZAPIER (automatic): "Oh! Deal moved to 'Quote Sent'. Time to send SMS."
       ↓
SEMAPHORE (automatic): Sends SMS: "Hi Juan! Your quote is ready. Questions? Call [PHONE]"
       ↓
HubSpot: Logs SMS in Juan's deal activity ("SMS sent: 2:15 PM")
```

### What You Need to Know

**You don't need to do anything in Zapier.** It runs automatically in the background.

**To verify it's working:**
1. HubSpot dashboard → Select a deal
2. Scroll to **"Activities"** section
3. Look for entry: **"SMS sent via Zapier at 2:15 PM"**
4. If it's there → ✓ Zapier is working

**If SMS doesn't send:**
1. Check: Is HubSpot deal in correct stage? ("Quote Sent"?)
2. Check: Does customer phone number exist in deal?
3. Check: Semaphore credits available (not ₱0)?
4. If still stuck → Contact Odyssey; may need to check Zapier dashboard

---

## Your 5 SMS Templates (Use Daily)

**Template 1 — Appointment Reminder** (send day before)
```
Hi [Name]! Just a reminder: We're installing your CCTV 
system tomorrow (Monday) at 9 AM. We'll call when 10 min 
away. Any questions? Reply here or call [PHONE]. -ASMR
```

**Template 2 — Installation Complete** (send day of installation)
```
[Name], your CCTV system is live! You should get motion 
alerts on your phone. Reply TEST to verify, or call us if 
questions. Thanks for trusting ASMR! -Team ASMR
```

**Template 3 — Special Offer** (send when promoting)
```
October Special: New customers get 3 months FREE cloud 
storage! Valid until Oct 31. Book today: [SHORT LINK] 
or reply INSTALL. -ASMR CCTV
```

**Template 4 — Re-engagement** (send to silent leads)
```
Hi [Name]! Haven't heard from you in a while. We lowered 
prices on 4-camera systems to P32k. Still interested? 
Reply YES or call [PHONE]. -ASMR
```

**Template 5 — 30-day Follow-up** (send 30 days post-install)
```
[Name], how's the CCTV system working? Any concerns? 
Our 30-day satisfaction check is here to help. Reply 
or call [PHONE]. -ASMR CCTV
```

---

## Daily Workflow (20 Minutes)

### 9:00 AM — Check Leads
- [ ] Log into HubSpot
- [ ] View "Awareness" stage → new leads
- [ ] Review: Name, phone, service interest
- [ ] Add missing contact info (call/text if needed)

### 9:15 AM — Update Pipeline
- [ ] Move deals based on yesterday's actions
- [ ] Filter: "Last activity > 3 days" → these need follow-up TODAY
- [ ] Log all activities (calls, emails, SMS)

### 9:30 AM — Check Semaphore
- [ ] View credits (add more if below ₱100)
- [ ] Check "Reports" tab → delivery stats from yesterday's batch SMS
- [ ] If any failed → note bad phone numbers, flag for manual correction

### 10:00 AM — Manual Follow-ups
- [ ] For high-priority leads: Send manual SMS using Template 1, 2, 4, or 5
- [ ] Or send email with quote (HubSpot will track if opened)
- [ ] Log activity after each contact

### 3:00 PM — Check Responses
- [ ] Review SMS replies in Semaphore ("Inbox" tab)
- [ ] Log responses in HubSpot deal activity
- [ ] Move deal stage if new info received (e.g., "Demo scheduled" → move to "Demo Requested")

---

## Common Questions

**Q: How do I know if SMS was delivered?**
A: Semaphore "Reports" tab shows ✓ Delivered vs ✗ Failed per SMS. Target: >95% delivery.

**Q: How long should I wait before sending follow-up SMS?**
A: After quote sent: 3 days → SMS reminder. After no response: 7 days → SMS follow-up. After installation: 30 days → satisfaction check.

**Q: Can I use BOTH HubSpot email + SMS on same lead?**
A: YES! Send email with quote (HubSpot tracks opens), then SMS 3 days later if no response. Two-channel approach = higher response rate.

**Q: What if customer replies "Stop" or "Remove"?**
A: Honor it. Add tag "Opted Out" in HubSpot. Never send SMS to that number again. (Semaphore may auto-flag this.)

**Q: Can I send SMS at night (say, 8 PM)?**
A: Yes, but customer may think it's urgent. Best practice: Send 7–10 AM or 4–6 PM (when people check phones).

**Q: How much does this cost monthly?**
A: Only Semaphore costs: ₱0.56/SMS. If you send 500 SMS = ₱280. HubSpot = ₱0 (free). Zapier = ₱0 (free tier). Total: ₱280–560/month.

---

## Credentials (DO NOT SHARE)

**Store in password manager ONLY. Never commit to git or write in plaintext.**

| System | Email | Password | URL |
|--------|-------|----------|-----|
| HubSpot | inquiries@asmrcctvtechnologies.com | [password manager] | hubspot.com |
| Semaphore | inquiries@asmrcctvtechnologies.com | [password manager] | semaphore.co |
| Zapier | inquiries@asmrcctvtechnologies.com | [password manager] | zapier.com |

**Password manager recommendation:** 1Password, LastPass, or Bitwarden (all free/cheap and team-shareable).

---

## Training Checklist

Before going solo, complete these tests:

- [ ] Create 1 new contact in HubSpot (your phone number)
- [ ] Move a deal through 2 pipeline stages
- [ ] Send 1 manual SMS via Semaphore to your phone
- [ ] Check Semaphore "Reports" to verify SMS delivered
- [ ] Verify Zapier sent SMS (check HubSpot activity log)
- [ ] Ask Odyssey any questions before handling real leads

---

## Support

**Questions?** 
- Odyssey is the main user; escalate any issues to them
- Semaphore support: support@semaphore.co (live chat on dashboard)
- HubSpot help: hubspot.com/help (searchable knowledge base)
- Zapier help: zapier.com/help (searchable knowledge base)

**Emergency (system down)?**
- Fall back to manual SMS via Semaphore dashboard
- Log notes in HubSpot to track what happened
- Contact Odyssey for next steps

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-11-06 | Initial guide created; all 3 systems integrated |

---

**Status:** Ready for team use  
**Last updated:** 2026-11-06  
**Next review:** 2026-11-13 (one week, check for issues)
