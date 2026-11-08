# Day 7 Team Guide: SMS, CRM, Zapier
**ASMR CCTV Trading — Quick Reference**  
**Created:** 2026-11-06 | **For:** Team Members  

---

This guide shows how to use the 3 new systems for Day 7:
1. **Semaphore** — Send text messages to customers
2. **HubSpot** — Track leads and manage customer pipeline
3. **Zapier** — Automatic actions (when quote sent → send reminder SMS)

No coding needed. All passwords in your **password manager** (ask owner).

---

# Section A: SMS via Semaphore

## What is Semaphore?

Semaphore is a **Philippine SMS service**. We use it to send text messages to customers automatically or manually. Cost: **₱0.56 per SMS**.

### How to Log In

1. Open browser, go to `https://www.semaphore.co/` (or check bookmark)
2. Click **Sign In**
3. Enter email: `asmrcctvtechnologies@gmail.com` (or ask owner for account email)
4. Enter password (stored in password manager — ask owner)
5. Click **Log In**

[SCREENSHOT: Semaphore login page]

You're now in the **Dashboard**. You'll see:
- Account balance (₱XX left for SMS credits)
- Recent messages sent
- Menu on left: Messages, Contacts, Templates, API Keys

[SCREENSHOT: Semaphore dashboard overview]

---

### Send a Single SMS (Manual Follow-Up)

Use this when **one customer** needs a message right now (follow-up, reminder, etc.).

1. From dashboard, click **Messages** (left menu)
2. Click **Send Message** or **New Message**
3. Fill in:
   - **Recipient:** Enter customer phone number (start with `+63` for Philippines, or just `09XX...`)
     - Example: `+639123456789` or `09123456789`
   - **Message:** Type or paste from template (see list below)
   - **Sender ID:** Leave as default (or use "ASMR" if available)
4. Click **Preview** to check the message
5. Click **Send**

[SCREENSHOT: Semaphore "Send Message" form filled in]

**Wait:** Semaphore will show "Sent ✓" in 2–3 seconds. Check your account balance — it should decrease by ₱0.56.

---

### Send Batch SMS (Promo, Re-engagement)

Use this to send the **same message to many customers** at once (promo, monthly reminder, etc.).

1. Click **Contacts** (left menu)
2. Click **Import Contacts** (or **Upload CSV**)
3. Download the CSV template and fill it with:
   - Column A: Phone numbers (start with `+63` or `09XX...`)
   - Column B: Name (optional)
   - Save as `.csv` file
4. Upload the file
5. Once imported, click **Messages**
6. Click **Create Campaign** or **Batch Send**
7. Select your contact list
8. Paste your SMS message
9. Click **Schedule** or **Send Now**

[SCREENSHOT: Semaphore Contacts upload screen]

**Cost:** ₱0.56 × number of customers = total

**Example:** Sending to 50 customers = ₱28

---

### The 5 SMS Templates (When to Use Each)

**Store these in a note file or in Semaphore's Template section:**

#### Template 1: Appointment Reminder (2 days before visit)
```
Hi [NAME], your ASMR CCTV installation is scheduled for [DATE] at [TIME]. 
Please make sure someone's home. Questions? Call [PHONE]. See you soon!
```

#### Template 2: Installation Complete (Day 7 review request)
```
Hi [NAME], your ASMR CCTV system is installed and working great! 
Help us grow — leave a 5-star review here: [GOOGLE REVIEW LINK]
Thanks for choosing ASMR!
```

#### Template 3: Special Offer (Promotional)
```
🎉 SPECIAL OFFER: 20% OFF multi-location CCTV installs + free setup!
Valid until [DATE]. Call now: [PHONE] or inquiries@asmrcctvtechnologies.com
Limited slots available. — ASMR CCTV Trading
```

#### Template 4: Re-engagement (Dormant leads, 30+ days no contact)
```
Hi [NAME], we haven't heard from you! Your ASMR CCTV quote is still available. 
Questions? We're here to help. Call [PHONE] today. No obligation!
```

#### Template 5: 30-Day Follow-Up (Satisfaction check)
```
Hi [NAME], 30 days since your install! How's your CCTV system working? 
Any questions or support needed? Reply or call [PHONE]. We're here for you!
```

---

### Track SMS Costs Monthly

Keep a simple spreadsheet:

| Date | Campaign | Recipients | Cost (₱) | Notes |
|------|----------|------------|---------|-------|
| Nov 6 | Review request | 5 customers | 2.80 | Day 7 follow-up |
| Nov 15 | Reengagement | 12 customers | 6.72 | Dormant leads |
| Nov 20 | Promo | 25 customers | 14.00 | Special offer |
| **Nov Total** | — | — | **23.52** | — |

**Monthly budget estimate:** ₱100–150 (depends on volume)

---

# Section B: HubSpot CRM Basics

## What is HubSpot?

HubSpot is a **Customer Relationship Manager (CRM)**. It's like a digital filing cabinet that stores:
- Customer names, phone numbers, emails
- What stage they're in (prospect, demo scheduled, quote sent, won, lost)
- When you last contacted them
- Notes about their needs
- Automatic reminders and emails

**Cost:** Free for 1 user and unlimited contacts.

### How to Log In

1. Open browser, go to `https://app.hubspot.com/`
2. Click **Sign In**
3. Enter email: (ask owner for HubSpot email)
4. Enter password (stored in password manager)
5. Click **Log In**

[SCREENSHOT: HubSpot login page]

You're now in the **HubSpot Dashboard**. You'll see:
- A welcome message
- Sales pipeline (visual stages)
- Recent activity
- Menu on left: **Contacts, Deals, Tasks, Reports**

[SCREENSHOT: HubSpot dashboard with pipeline visible]

---

### Create a New Contact

When a new customer calls, emails, or messages, add them to HubSpot right away.

1. Click **Contacts** (left menu)
2. Click **Create Contact** (top right, usually a blue button)

[SCREENSHOT: Contacts page with "Create Contact" button]

3. Fill in the form:
   - **First Name:** Customer's first name
   - **Last Name:** Customer's last name
   - **Email:** Their email (if they have one)
   - **Phone Number:** Their phone (e.g., `09123456789`)
   - **Company:** `ASMR customer` (or the name of their business if they're commercial)
   - **Notes:** How you got them (Facebook message? Walk-in? Referral?)
   - **Lead Source:** Select from dropdown (Website, Social Media, Phone Call, etc.)

4. Click **Create**

[SCREENSHOT: New Contact form with fields filled in]

---

### Move a Deal Through the Pipeline

**Pipeline stages** (in order):

1. **Prospect** — New lead, not yet contacted
2. **Demo Scheduled** — Customer agreed to a demo/visit
3. **Quote Sent** — You've sent them a price quote
4. **Negotiating** — Back and forth on price/terms
5. **Won** — They said yes, installation scheduled
6. **Lost** — They said no or went silent

### To Move a Deal:

1. Click **Deals** (left menu)
2. You'll see columns (Prospect | Demo Scheduled | Quote Sent | Negotiating | Won | Lost)
3. Click a contact's name or create a new deal
4. In the deal detail, click **Stage** dropdown
5. Select the next stage (e.g., from Prospect → Demo Scheduled)
6. Add notes about why they moved (e.g., "Customer called, wants demo on Nov 10")
7. Click **Save**

[SCREENSHOT: HubSpot pipeline with deals in columns]

**Example:**
- Customer calls → Create contact → Create deal → Stage: "Prospect"
- You send quote → Move to "Quote Sent"
- Customer says yes → Move to "Won"

---

### View Automation Rules (What They Do)

Automations are **rules that HubSpot follows automatically**. For example: "When a new contact is created, send them a welcome email."

To see what's running:

1. Click **Settings** (bottom left, gear icon)
2. Click **Workflows** or **Automation**
3. You'll see a list of workflows (e.g., "Welcome Email," "7-Day Review Request," etc.)

[SCREENSHOT: HubSpot Workflows page]

**What these automations do:**

| Automation | Trigger | Action |
|-----------|---------|--------|
| Welcome Email | New contact created | Sends automated email saying "Thanks for reaching out!" |
| 7-Day Review Request | Deal moved to "Won" | Sends SMS review request 7 days after install |
| Re-engagement Reminder | No contact for 30 days | Sends email offering help |
| Quote Reminder | Deal in "Quote Sent" for 3 days | Sends follow-up SMS: "Checking in on your quote..." |

**You don't edit these** — the owner sets them up. But you should know they exist so you're not confused when customers get automatic emails.

---

### Export Contact List for Bulk SMS

When you want to send a batch SMS to all customers (using Semaphore), first export the list from HubSpot.

1. Click **Contacts** (left menu)
2. Click the **Export** button (usually top right)
3. Choose **Export All Contacts** or filter first (e.g., "Last contacted in past 30 days")
4. Choose format: **CSV**
5. Click **Export**

[SCREENSHOT: Contacts export dialog]

6. A `.csv` file downloads to your computer
7. Open it in Excel or Google Sheets
8. Keep only 2 columns: Name | Phone Number
9. Delete all other columns
10. Save as `.csv`
11. Upload to Semaphore (see Section A)

---

### Daily Workflow: 20 Minutes/Day

**Time: 9:00 AM**

1. **Check for new contacts** (1 minute)
   - Click Contacts → Sort by "Date Created"
   - See if any new leads came in overnight
   - Add notes if you know more info

2. **Update pipeline** (5 minutes)
   - Click Deals
   - Move any deals that changed status
   - Example: Customer texted yes → move to "Won"
   - Add notes: "Customer confirmed for Nov 10 install"

3. **Send follow-up SMS if needed** (5 minutes)
   - Look at deals in "Quote Sent" stage
   - If no response in 2 days, send SMS: "Hi [NAME], checking in on your quote..."
   - Log in to Semaphore and send manually

4. **Respond to review requests** (5 minutes)
   - Check if any customers left reviews on Google/Facebook
   - Reply with "Thanks!" or address any concerns
   - (See GOOGLE-FACEBOOK-REVIEWS.md)

5. **Check if automations ran** (check email / logs)
   - Look for emails that came from HubSpot (e.g., review requests)
   - If something seems wrong, ask owner

---

# Section C: Zapier Integration

## What is Zapier?

Zapier is an **automation connector**. It links HubSpot and Semaphore together so that:
- When you send a quote in HubSpot, Zapier automatically sends an SMS reminder to the customer

**You don't need to log in to Zapier** — it works in the background.

### What the Main Zap Does

**Trigger:** When a deal is moved to "Quote Sent" in HubSpot  
**Action:** Zapier sends an SMS reminder to the customer

**SMS Template:**
```
Hi [CUSTOMER NAME], just checking in on your ASMR CCTV quote! 
Questions? Reply or call [PHONE]. We're here to help!
```

### How to Check If Zapier Ran

1. Log in to Zapier at `https://zapier.com`
2. Enter email and password (ask owner)
3. Click on the **zap** named "HubSpot Quote → SMS Reminder"
4. Click **View Activity** or **Run History**
5. Look for your recent quote — you should see:
   - ✅ "Sent to Semaphore"
   - ✅ "SMS delivered"

[SCREENSHOT: Zapier run history showing successful zap execution]

**What to look for:**
- ✅ = Success (SMS sent)
- ❌ = Failed (something went wrong)

If you see a ❌, contact the owner — they'll check what went wrong.

---

### When/How to Adjust the Zap

**Most of the time, you don't touch Zapier.** But if something changes:

**Example scenarios:**

1. **"We want to send SMS 2 days after quote, not immediately"**
   - Owner adjusts Zapier settings
   - You don't need to do anything

2. **"The SMS template is wrong"**
   - Owner edits the template in Zapier
   - Check Zapier settings to confirm the new message

3. **"Zapier stopped working"**
   - Owner will investigate and fix
   - Report if you notice automations aren't running

**For any changes, ask the owner — don't edit Zapier yourself.**

---

# Section D: Daily Workflow (20 Minutes/Day)

**Your job each day: keep leads moving through the pipeline.**

### Time: 9:00 AM

**Step 1: Check HubSpot Dashboard (2 minutes)**
- Go to `https://app.hubspot.com/`
- Log in (email + password)
- Look at **Deals** board
- Count how many in each stage:
  - Prospects?
  - Demo Scheduled?
  - Quote Sent?
  - Won?

[SCREENSHOT: HubSpot pipeline dashboard]

**Step 2: Handle New Contacts (3 minutes)**
- Click **Contacts**
- Sort by **Date Created** (newest first)
- Any new leads? Check their notes and contact method
- If you know more info, add it (e.g., "Wants demo for office, interested in remote monitoring")

**Step 3: Move Deals Forward (5 minutes)**
- Go to **Deals**
- Look at each column:
  - **Prospect** — Did they respond? If yes, move to "Demo Scheduled"
  - **Demo Scheduled** — Was demo done? Move to "Quote Sent"
  - **Quote Sent** — Did they say yes? Move to "Won" (and schedule install)
  - **Negotiating** — Still talking? Leave it, but add update notes

**Step 4: Send Any Follow-Ups (7 minutes)**
- Go to Semaphore (Section A)
- Look for deals stuck in "Quote Sent" for 2+ days
- Send a manual SMS: "Hi [NAME], checking in on your ASMR quote. Ready to move forward? Call [PHONE]."
- Or use Template #4 (Re-engagement)

**Step 5: Respond to Reviews (3 minutes)**
- Check Google Business Profile (GOOGLE-FACEBOOK-REVIEWS.md)
- Check Facebook page Reviews
- Respond to any new reviews with "Thank you!" or address concerns

---

## Example: One Customer's Journey Through HubSpot

**Monday 9 AM:** 
- Customer calls asking about CCTV
- Create contact: "Juan Dela Cruz, 09123456789, interested in small office"
- Create deal, Stage: **Prospect**

**Wednesday 5 PM:**
- Juan agrees to demo on Friday
- Move deal to **Demo Scheduled**
- Add note: "Demo scheduled Friday 2 PM at office in Cainta"

**Friday 3 PM (after demo):**
- You sent him a quote
- Move deal to **Quote Sent**
- Zapier automatically sends SMS reminder
- Add note: "Quote sent ₱25,000 for 4-camera package"

**Monday 9 AM (no response yet):**
- You manually send SMS: "Hi Juan, checking in..."
- Deal still in **Quote Sent**

**Tuesday 10 AM:**
- Juan says yes!
- Move deal to **Won**
- Add note: "Install scheduled Thursday Nov 13. Customer happy with price."
- Schedule installation task for Thursday

**Friday (7 days after install):**
- Zapier automatically sends review request SMS
- Monitor if Juan leaves a review

---

## Troubleshooting

**Q: "I can't find a customer in HubSpot."**  
A: Click Contacts → Search by name or phone number in top right.

**Q: "An SMS didn't send."**  
A: Go to Semaphore dashboard. Check your account balance (may be out of credits). If balance OK, see if Zapier activity shows an error.

**Q: "I moved a deal to the wrong stage."**  
A: Click the deal, click the Stage, select the correct one. It's reversible.

**Q: "My HubSpot password doesn't work."**  
A: Click "Forgot Password" on login page. Reset via email.

---

## Quick Links (Save These)

| System | URL | Login With |
|--------|-----|-----------|
| Semaphore | `https://www.semaphore.co/` | Email in password manager |
| HubSpot | `https://app.hubspot.com/` | Email in password manager |
| Zapier | `https://zapier.com/` | Email in password manager |

---

## Important

- **All passwords:** Store in your password manager (1Password, LastPass, etc.)
- **Never share passwords** in chat, email, or sticky notes
- **SMS costs money:** Track usage on the spreadsheet (Section A)
- **Ask owner for any changes** to automations or templates

---

**Questions?** Ask the owner or check GOOGLE-FACEBOOK-REVIEWS.md for review workflows.

**Last updated:** 2026-11-06
