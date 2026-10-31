# ASMR CCTV Trading — Implementation Roadmap
**Document Type:** Phase 2 Action Plan
**Date:** 2026-10-11
**Prepared by:** Execution Manager (AGT-EM)
**Status:** PENDING APPROVAL — Do not execute until approved by Team Lead (ChatGPT) and Owner (Odyssey Jan Pascual)

---

## Overview

### Project Goal

Grow the online visibility of ASMR CCTV Trading (asmrcctvtechnologies.com) in San Mateo, Rizal and the surrounding Rizal province. The target outcome is for ASMR to appear in Google Maps and organic search results for CCTV installation, networking, biometrics, IT support, and related services — driving qualified local inquiries and converting them to paid engagements.

### Current Status

**Phase 1 is complete as of 2026-10-11.** Four parallel audits were conducted and compiled into a Team Lead Consolidated Report:

- Website SEO & Content Audit (AGT-02) — Grade: F (pre-optimization baseline)
- Local SEO Research (AGT-01) — 25+ target keywords identified across 5 clusters
- Google Business Profile Audit (AGT-03) — GBP Completeness Score: 30/100
- Facebook Page Audit (AGT-04) — 170 followers; zero Tagalog content; website not linked

**Phase 2 begins only after Team Lead and Owner approval of this roadmap.**

### Guiding Principles

1. **No live changes without approval.** This document is a plan, not authorization. Each task tier requires explicit go-ahead from the owner and/or team lead before execution begins.
2. **Verified information only.** No service, address, or claim may be published unless confirmed accurate. All NAP (Name, Address, Phone) data must be consistent across all platforms before any new content is created.
3. **Bot-detection investigation is a hard prerequisite.** The website JavaScript that redirects automated browsers must be reviewed and cleared by the developer before any on-site SEO changes are deployed.
4. **Owner actions first.** Several high-impact items require the owner's direct login to Google Business Profile and Facebook. These unblock all subsequent developer and content work.
5. **Measure everything.** Google Analytics 4 and Google Search Console must be installed in Month 1. No Phase 2 content work should be claimed as successful without data.

---

## Quick Wins (Week 1–2) — Owner-Executable, No Developer Needed

These are zero-cost, high-impact fixes the business owner (Odyssey) can complete without technical help. They should be done before any developer work begins.

| # | Task | Platform | Notes |
|---|---|---|---|
| T-001 | Confirm GBP listing is claimed/verified at business.google.com — if not claimed, initiate claim immediately | Google Business Profile | If unclaimed, verification via postcard may take 1–2 weeks; start this first |
| T-002 | Change GBP primary category to "Security system installer" | Google Business Profile | Add secondary categories: CCTV installer, IT management company, Computer repair service |
| T-003 | Add website URL to GBP listing: https://asmrcctvtechnologies.com | Google Business Profile | Currently shows "Add website" button — currently missing |
| T-004 | Replace GBP lead photo — remove the Dunkin' Donuts image; upload an actual business or office photo | Google Business Profile | Lead photo creates wrong first impression for every GBP visitor |
| T-005 | Add 5+ service photos to GBP: completed CCTV installations, networking jobs, biometric setups | Google Business Profile | Technoplete CCTV (a direct competitor) has 311 photos with no website; photos drive Maps visibility |
| T-006 | Add website URL to Facebook About section under Websites & Social Links | Facebook Page Admin | asmrcctvtechnologies.com is referenced in posts but not in About |
| T-007 | Update Facebook About email from asmrcctvandpc@gmail.com to inquiries@asmrcctvtechnologies.com | Facebook Page Admin | NAP inconsistency confirmed — email mismatch between website and Facebook hurts local SEO trust signals |
| T-008 | Check and enable Facebook Messenger if disabled | Facebook Page Admin | One customer commented "I can't PM your page" — messaging may be off in page settings |
| T-009 | Update Facebook page primary category from "Computer Repair Service" to a security/IT category | Facebook Page Admin | Recommended: Security System Installer or IT Company |

**Success criteria for Week 1–2:** ASMR CCTV Trading appears in at least one Google Maps search for a CCTV or IT service in San Mateo or Rizal.

---

## Month 1 — Technical SEO Foundations (Developer Required)

**IMPORTANT:** Task T-010 (bot-detection investigation) must be completed and cleared FIRST before any other on-site changes are deployed. If the bot-detection script affects Googlebot, all subsequent SEO work may fail to index.

| # | Task | Owner | Dependency |
|---|---|---|---|
| T-010 | Investigate and resolve the automated-browser redirect script — confirm Googlebot is not being redirected; document trigger conditions; disable or whitelist crawlers as needed | Developer | None — MUST complete before T-011 through T-019 |
| T-011 | Force HTTPS redirect: implement HTTP → HTTPS 301 redirect site-wide; add canonical tags to all pages | Developer | T-010 cleared |
| T-012 | Create robots.txt (allow all for now; add pointer to sitemap.xml) | Developer | T-010 cleared |
| T-013 | Create sitemap.xml covering all pages and news posts; submit to Google Search Console | Developer | T-012; GSC access (T-017) |
| T-014 | Add meta title (keyword-optimized, location-included) and meta description (150–160 chars) to every page — priority order: homepage, /services.php, /contact.php, /products.php, /about.php, news posts | Developer | T-010 cleared |
| T-015 | Fix multiple H1 issues — reduce to exactly one H1 per page; restructure heading hierarchy to H1/H2/H3 | Developer | T-010 cleared |
| T-016 | Add Open Graph tags (og:title, og:description, og:image) to all pages — enables social media preview cards when links are shared | Developer | T-010 cleared |
| T-017 | Install Google Analytics (GA4) and verify domain in Google Search Console | Developer + Owner | Owner must verify domain in GSC; prerequisite for all traffic measurement |
| T-018 | Add LocalBusiness JSON-LD schema to homepage and contact page — NAP must exactly match confirmed GBP address | Developer | Correct address confirmed by owner (see RISKS-AND-DEPENDENCIES.md — address discrepancy) |
| T-019 | Add Service schema to services.php | Developer | T-018 complete |

**Success criteria for Month 1:** All pages indexed in Google Search Console with no crawl errors; LocalBusiness schema validates in Google Rich Results Test; GA4 tracking live on all pages; HTTP → HTTPS redirect active.

---

## Month 2 — Content and Landing Pages

| # | Task | Owner | Dependency |
|---|---|---|---|
| T-020 | Create dedicated landing page: CCTV Installation San Mateo Rizal | Developer + Content | T-010, T-018 |
| T-021 | Create dedicated landing page: Network & Wi-Fi Setup Rizal | Developer + Content | T-010, T-018 |
| T-022 | Create dedicated landing page: Biometric Attendance Systems Rizal | Developer + Content | T-010, T-018 |
| T-023 | Create dedicated landing page: Fire Alarm Systems Rizal (low competition niche — verify FSIC compliance first) | Developer + Content | T-010, T-018; Owner confirms Fire Alarm service is actively offered |
| T-024 | Create dedicated landing page: Access Control Rizal | Developer + Content | T-010, T-018 |
| T-025 | Add business hours to website — visible on contact page and homepage | Developer | Owner confirms hours |
| T-026 | Add Google Maps embed to contact page (linked to GBP listing) | Developer | T-010 cleared |
| T-027 | Add customer testimonials section to homepage or a dedicated page — use named clients (Dunkin', OLOPSC, Chef's Secret) only with explicit written permission | Developer + Owner | Owner confirms client permission before publishing |
| T-028 | Start Facebook Tagalog/Taglish content publishing — minimum 2 posts per week | Content/Owner | None — can start immediately once approved |
| T-029 | Begin requesting Google reviews from existing clients (Dunkin', OLOPSC, Chef's Secret, Honcho Logistics, Magiclean) | Owner | GBP must be claimed (T-001); GBP website link live (T-003) |

**Success criteria for Month 2:** 5 new service landing pages indexed; Google Maps embed on contact page; Facebook posting at 2x/week cadence; at least 3 Google reviews received; testimonials section live (with permission).

---

## Month 3 — Reviews, Authority, and Analytics

| # | Task | Owner | Dependency |
|---|---|---|---|
| T-030 | Implement structured review request process — systematic follow-up with all past clients for Google reviews | Owner | GBP claimed; review link ready |
| T-031 | Target: 10 Google reviews (minimum) by end of Month 3 | Owner | T-029, T-030 |
| T-032 | Add GBP Posts — publish weekly, using completed project photos and service highlights | Owner/Content | GBP claimed (T-001) |
| T-033 | Seed GBP Q&A section with common customer questions and answers (e.g., "Do you serve areas outside San Mateo?" / "How much does CCTV installation cost?") | Owner/Content | GBP claimed (T-001) |
| T-034 | Publish first 4 blog/news articles on website — mix of Tagalog/English; cover service topics (CCTV buying guide, network setup FAQ, biometrics explainer, fire alarm primer) | Content/Developer | Clean blog URL structure (T-013 area) |
| T-035 | Review GA4 and Search Console baseline — analyze first 60 days of data; adjust keyword targeting and content strategy based on actual impressions and clicks | Owner + Content | T-017; minimum 60 days of data |

**Success criteria for Month 3:** 10+ Google reviews visible; ASMR appearing in Google Maps for at least 3 distinct service query types; 5 new landing pages indexed and receiving impressions in Search Console; Facebook follower count above 300; GA4 and Search Console data available for strategy review.

---

## Success Metrics (By End of Month 3)

| Metric | Target | Baseline (2026-10-11) |
|---|---|---|
| GBP appearing in Maps for "CCTV installation San Mateo Rizal" | YES | NOT appearing |
| Google reviews | 10 minimum | 0 |
| Pages with meta title + description | All pages | 0 pages |
| Sitemap submitted; no crawl errors in GSC | YES | No sitemap; no GSC |
| Facebook followers | 300+ | 170 |
| Facebook posting cadence | 2+ posts/week | ~1 post/6 weeks |
| Local service landing pages live | 5 new pages | 0 dedicated local pages |
| Google Analytics baseline established | YES | Not installed |

---

## Task Assignment Table

| Task ID | Task | Owner | Month | Effort | Dependency |
|---|---|---|---|---|---|
| T-001 | Confirm/claim GBP listing | Odyssey | Week 1 | Low | None |
| T-002 | Fix GBP primary category | Odyssey | Week 1 | Low | T-001 |
| T-003 | Add website URL to GBP | Odyssey | Week 1 | Low | T-001 |
| T-004 | Replace GBP lead photo | Odyssey | Week 1 | Low | T-001 |
| T-005 | Add 5+ service photos to GBP | Odyssey | Week 1–2 | Low | T-001 |
| T-006 | Add website to Facebook About | Odyssey | Week 1 | Low | None |
| T-007 | Update Facebook email to domain email | Odyssey | Week 1 | Low | None |
| T-008 | Enable Facebook Messenger | Odyssey | Week 1 | Low | None |
| T-009 | Update Facebook page category | Odyssey | Week 1 | Low | None |
| T-010 | Investigate bot-detection script | Developer | Month 1 | Medium | None |
| T-011 | Force HTTPS + canonical tags | Developer | Month 1 | Low | T-010 |
| T-012 | Create robots.txt | Developer | Month 1 | Low | T-010 |
| T-013 | Create and submit sitemap.xml | Developer | Month 1 | Low | T-012, T-017 |
| T-014 | Add meta titles + descriptions all pages | Developer | Month 1 | Medium | T-010 |
| T-015 | Fix multiple H1 tags site-wide | Developer | Month 1 | Medium | T-010 |
| T-016 | Add Open Graph tags all pages | Developer | Month 1 | Low | T-010 |
| T-017 | Install GA4 + verify GSC | Developer + Odyssey | Month 1 | Low | None |
| T-018 | Add LocalBusiness JSON-LD schema | Developer | Month 1 | Medium | Address confirmed |
| T-019 | Add Service schema to services.php | Developer | Month 1 | Medium | T-018 |
| T-020 | Landing page: CCTV Installation San Mateo Rizal | Developer + Content | Month 2 | Medium | T-010, T-018 |
| T-021 | Landing page: Network & Wi-Fi Setup Rizal | Developer + Content | Month 2 | Medium | T-010, T-018 |
| T-022 | Landing page: Biometric Attendance Rizal | Developer + Content | Month 2 | Medium | T-010, T-018 |
| T-023 | Landing page: Fire Alarm Rizal | Developer + Content | Month 2 | Medium | T-010, T-018; Owner confirms service |
| T-024 | Landing page: Access Control Rizal | Developer + Content | Month 2 | Medium | T-010, T-018 |
| T-025 | Add business hours to website | Developer | Month 2 | Low | Owner confirms hours |
| T-026 | Google Maps embed on contact page | Developer | Month 2 | Low | T-010 |
| T-027 | Testimonials section (with client permission) | Developer + Owner | Month 2 | Medium | Client permission confirmed |
| T-028 | Facebook Tagalog/Taglish posts (2x/week) | Content/Owner | Month 2 | Ongoing | None |
| T-029 | Request Google reviews from past clients | Odyssey | Month 2 | Low | T-001, T-003 |
| T-030 | Structured review request process | Odyssey | Month 3 | Low | T-029 |
| T-031 | Reach 10 Google reviews | Odyssey | Month 3 | Low | T-030 |
| T-032 | Weekly GBP Posts | Owner/Content | Month 3 | Ongoing | T-001 |
| T-033 | Seed GBP Q&A | Owner/Content | Month 3 | Low | T-001 |
| T-034 | Publish 4 blog/news articles | Content/Developer | Month 3 | Medium | T-013 |
| T-035 | GA4 + GSC baseline review | Owner + Content | Month 3 | Low | T-017 + 60 days data |

---

*End of Implementation Roadmap*
*Date: 2026-10-11*
*Prepared by: Execution Manager (AGT-EM)*
*Status: PENDING — awaiting Team Lead (ChatGPT) and Owner (Odyssey Jan Pascual) approval before any execution begins*
