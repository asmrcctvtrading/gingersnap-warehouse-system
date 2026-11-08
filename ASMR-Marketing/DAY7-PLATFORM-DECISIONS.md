# Day 7: Platform Decisions & Rationale
**ASMR CCTV Trading Growth Initiative**
**Date:** 2026-11-06 | **Decided by:** Research + Team Lead Analysis

---

## Executive Summary
**Selected Stack:** Semaphore (SMS) + HubSpot (CRM) + Zapier (Integration)
**Total Cost:** ₱300–1,250/month
**Setup Time:** 3–4 hours
**Team Skill Required:** Non-technical (web dashboard, no coding)

---

## Platform Decisions (Why Each Choice)

### SMS: Semaphore ✓ (vs Twilio, PhilSMS, CloudSMS)

| Criterion | Semaphore | Twilio | PhilSMS | CloudSMS |
|-----------|-----------|--------|---------|----------|
| **Cost/SMS** | ₱0.56 | ₱2.40+ | ₱0.35 | ₱0.50 |
| **Setup** | Web UI only | Requires API | Web UI | Web UI |
| **Support** | 24/7 PH support | English only | Local | Local |
| **Batch send** | Yes | Yes | Yes | Yes |
| **Schedule SMS** | Yes | Yes | Limited | Limited |
| **Sender ID branding** | Yes ("ASMR-CCTV") | Yes | Limited | Limited |

**Winner: Semaphore** because:
- 20× cheaper than Twilio for PH-only business
- Web dashboard (no API code needed)
- Local 24/7 support for PH time zones
- Full feature set (batch, scheduling, branding)
- Can scale to 1000s SMS/month at same cost

**Cost example:** 500 SMS/month = ~₱280 (vs ₱1,200 with Twilio)

---

### CRM: HubSpot Free Tier ✓ (vs Pipedrive, Zoho, Google Sheets)

| Criterion | HubSpot Free | Pipedrive | Zoho CRM | Google Sheets |
|-----------|--------------|-----------|----------|--------------|
| **Cost** | ₱0 | ₱800/user/mo | ₱0 (free) | ₱0 |
| **Contacts** | Unlimited | Unlimited | Limited | Manual |
| **Automation** | Yes | Yes | Yes | No |
| **Email tracking** | Yes | Yes | Yes | No |
| **Mobile app** | Yes | Yes | Yes | No |
| **Learning curve** | Easy | Medium | Medium | Very easy |

**Winner: HubSpot Free** because:
- Zero cost, unlimited contacts
- Professional workflow (pipeline stages match sales script)
- Email tracking (know when lead opens quote)
- Automation (welcome email, follow-up reminders)
- Integrates with Zapier (to send SMS from CRM)
- Industry standard (team already familiar)

**Alternative considered:** Zoho CRM (also free, better SMS integration). **Decision:** Start with HubSpot, upgrade to Zoho in month 2 if needed.

---

### Integration: Zapier ✓ (vs Direct API, Manual Updates)

**Purpose:** Connect Semaphore SMS → HubSpot CRM so SMS sends automatically based on pipeline stage.

**Example workflow:**
1. Lead moves to "Quote Sent" in HubSpot
2. Zapier detects the change
3. Zapier automatically sends SMS via Semaphore: "Quote ready! Questions? Call [PHONE]"
4. SMS logged back into HubSpot contact record

**Cost:** Free tier = 100 tasks/month (enough for 50+ leads/month). Upgrade to ₱950/mo if scaling beyond that.

---

## What This Enables (Day 7 → Beyond)

### Immediate (Week 1–2)
- SMS follow-ups fully automated (no manual texting)
- CRM tracks every lead → reduces "lost" prospects
- Reviews solicitation automated (Google + Facebook links sent post-install)
- Team has single source of truth (HubSpot dashboard)

### Month 2+
- Batch re-engagement SMS to old leads ("Miss 50% off this month?")
- Automated reminders (3 days after quote: "Still interested?")
- Data analytics (which messages get highest response? which team member closes most?)

### Month 3+ (If scaling)
- Upgrade to Zoho CRM (native SMS module, local support)
- Add Facebook Lead Ads (auto-import leads directly to HubSpot)
- SMS A/B testing (Template 3 vs different promo wording)

---

## Cost Breakdown (Monthly)

| Component | Qty | Unit Cost | Total |
|-----------|-----|-----------|-------|
| Semaphore SMS | 500/mo | ₱0.56 | ₱280 |
| HubSpot CRM | 1 account | ₱0 | ₱0 |
| Zapier | 50 tasks/mo | ₱0 (free tier) | ₱0 |
| **Month 1 Total** | — | — | **₱280** |
| **Month 3 (if 1000 SMS)** | 1000/mo | ₱0.56 | **₱560** |
| **Month 6 (if upgrade to Zoho)** | + Zoho ₱0 | — | **₱560** |

**Max realistic cost (scaled):** ₱560/month (no additional fees)

---

## Next Steps (In Progress)

**Today (Nov 6):**
- [ ] Set up Semaphore account + buy ₱500 credit
- [ ] Set up HubSpot + create pipeline stages
- [ ] Connect via Zapier (1 test zap)
- [ ] Create review request templates (Google + Facebook)
- [ ] Test entire flow with 5 sample leads

**Tomorrow (Nov 7):**
- [ ] Import existing leads from Days 1–6 into HubSpot
- [ ] Train Odyssey + team on dashboards (30 min)
- [ ] Send first batch re-engagement SMS to old website inquiries
- [ ] Monitor first review requests going out

**Week 2:**
- [ ] Monitor SMS delivery rate + cost tracking
- [ ] Check CRM data quality (contacts with phone numbers, correct stage)
- [ ] Adjust SMS templates based on response rates (if needed)
- [ ] Plan Month 2 expansion (Zapier upgrade, Facebook Lead Ads)

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| **SMS bounces (bad phone #)** | Validate phone before importing; monitor Semaphore delivery reports |
| **Team forgets to update CRM** | Set daily 9 AM check-in habit; lead assignment in HubSpot |
| **Integration breaks (Zapier down)** | Keep manual SMS sending as fallback; test zap weekly |
| **Accidental bulk SMS** | HubSpot template requires approval before send; batch SMS requires 2-person check |
| **Credentials exposed** | Store logins in password manager, not in docs; never commit to git |

---

## Success Metrics (Track Weekly)

- **SMS delivery rate:** Target >95% (Semaphore reports in dashboard)
- **CRM fill rate:** 100% of new leads added within 24 hours
- **Review generation:** 1 review/week (target 3–5 in month 1)
- **Pipeline velocity:** Track "days to move from Awareness → Quote Sent" (target <7 days)
- **Cost tracking:** Actual SMS spend vs budget ₱500/mo

---

## Approved by
- **AI Team Lead:** Confirmed platform strategy
- **Pending owner review:** Odyssey to approve costs + team training plan
