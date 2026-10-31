# ASMR CCTV Trading — Team Lead Consolidated Report
**Report Date:** 2026-10-11
**Prepared by:** QA and Analytics Agent (AGT-05)
**Prepared for:** External Team Lead (ChatGPT) and Business Owner (Odyssey Jan Pascual)
**Phase:** End of Phase 1 Audit — Pre-Phase 2 Review

---

## 1. Executive Summary

### What Was Audited

Four parallel audits of ASMR CCTV Trading's online presence were completed on 2026-10-11 by the following agents:

| Audit | Agent | Scope |
|---|---|---|
| Website SEO & Content Audit | AGT-02 (Website Developer Agent) | Technical SEO, metadata, schema, content quality — asmrcctvtechnologies.com |
| Local SEO Research | AGT-01 (SEO Research Agent) | Keyword research, competitor analysis, search visibility gaps |
| Google Business Profile Audit | AGT-03 (GBP Agent) | GBP listing completeness, local Maps visibility, competitor benchmarks |
| Facebook Page Audit | AGT-04 (Content Marketing Agent) | Public Facebook page content, engagement, gaps — /ASMRCCTVTRADING |
| Verified Services Registry | AGT-02 + AGT-05 | Confirmed service catalog from website and Facebook |

All audits were conducted from publicly accessible data sources. No owner login credentials were available. The Google Search results page was blocked by bot-detection (CAPTCHA) during the GBP and Local SEO audits; Bing, DuckDuckGo, and Google Maps were used as proxies.

---

### Top 3 Highest-Priority Actions for Immediate Team Lead Decision

**These three actions have the highest combined impact and lowest cost. They can unblock all subsequent Phase 2 work.**

**Priority 1 — Fix the GBP Primary Category (Owner action required)**
The GBP is currently categorized as "Computer store." This is the direct cause of ASMR not appearing in any Google Maps result for CCTV installation, IT support, or networking searches in San Mateo or Rizal. No amount of website work will fix Maps visibility until the category is corrected. The owner must log into business.google.com to make this change.

**Priority 2 — Link the Website to the GBP and Add Website to Facebook About (Owner action required)**
Neither the GBP listing nor the Facebook About page links to asmrcctvtechnologies.com. These are zero-cost fixes that immediately improve credibility, cross-channel traffic, and local SEO signal consistency. Both require owner login.

**Priority 3 — Begin Google Review Generation Campaign (Owner action required)**
ASMR has zero public Google reviews. Competitors in the same municipality with fewer technical capabilities rank above ASMR in Maps because they have reviews. ASMR has named clients — Dunkin', Chef's Secret, OLOPSC, Honcho Logistics — who can be personally contacted. Even 5 reviews would move ASMR out of last place in local ranking. This requires no technical work.

---

### Overall Readiness Assessment for Phase 2

**Status: NOT READY — foundational blockers exist**

Phase 2 (active content production, link-building, and paid promotion) should not begin until the following Phase 1 blockers are resolved:

1. GBP category must be corrected — otherwise all Maps-related investment is wasted
2. Google Analytics 4 and Google Search Console must be installed — without these, Phase 2 cannot be measured
3. HTTP-to-HTTPS redirect must be enforced — currently the site runs on both protocols, creating duplicate content risk
4. The bot-detection JavaScript on the website must be reviewed — it may be interfering with Googlebot crawling

The website itself has a solid foundation (good design, real content, verified clients, clean structure) but has zero technical SEO infrastructure. It is currently invisible to search engines despite having content worth ranking.

---

## 2. Verified Company Services

**Source file:** VERIFIED-SERVICES.md (compiled by AGT-02, updated by AGT-05)

### Confirmed Services (from Website — High Confidence)

#### Hardware / Physical Systems (9 services)
- H01: Hardware Provisioning
- H02: IT Managed Services
- H03: Networking Solutions (structured cabling, secure network systems)
- H04: Biometrics & Door Access (timekeeping and access control)
- H05: Fire Detection & Alarm Systems
- H06: CCTV Installation & Centralization (including remote monitoring)
- H07: PABX / Intercom Systems
- H08: Projector & Video Wall
- H09: Solar Setup & Installation

#### Software / Custom Systems (11 services)
- S01: Custom Software Development
- S02: Mobile App Development (Android and cross-platform)
- S03: Web Application Development
- S04: Desktop Application Development (including POS)
- S05: Human Resource Information System (HRIS)
- S06: Point of Sale (POS) System
- S07: Accounting System
- S08: Warehouse Management System
- S09: Asset Inventory Management
- S10: GPS Tracking Solutions
- S11: Website Design & Development

#### IT Support / Consulting (3 services from About page)
- C01: IT Consulting
- C02: Technical Support & Troubleshooting
- C03: IT Maintenance

**Total confirmed from website: 23 services**

### Additional Services Observed on Facebook (from cover photo and post content)

The following services were observed on the Facebook page but do not all have exact matches in the website service list:

- IT Consultancy (matches C01)
- CCTV Setup and Installation (matches H06)
- Systems Development (matches S01/S03/S04)
- PC Assembly and Formatting — **not listed on website** (confirmed on Facebook cover photo)
- Structured Network Cabling (matches H03)
- [Se...istration] — partially obscured on cover photo; likely "Server Administration" — **requires confirmation**
- IT Disaster Recovery — **not listed on website** (confirmed on Facebook cover photo)
- Fiber Cabling and Splicing — **not explicitly listed on website** (listed separately from structured cabling)
- Printer Services — **not listed on website** (confirmed on Facebook cover photo)
- Public Address System (matches H07 partially)
- Solar Power System Setup (matches H09)
- IT Retail (matches products sold category)
- PTZ CCTV Camera Installation (confirmed via post content)
- SD-WAN / Ruijie Reyee Router networking (confirmed via post content)
- Warehouse Management and Production System / Sub-ERP (confirmed via post content)
- RFID Attendance Monitoring with SMS Alerts (confirmed in page comments — owner said "Yes po. We have po")

### Services Requiring Owner Confirmation Before Promoting

The following 7 services are on the website but carry unverified assumptions about current delivery capacity or legal compliance:

| # | Service | Reason |
|---|---|---|
| 1 | Solar Setup & Installation | High-cost specialized trade — confirm if actively offered |
| 2 | Fire Detection & Alarm Systems | FSIC compliance required in PH — confirm licensing status |
| 3 | GPS Tracking Solutions | Confirm deployed with real clients |
| 4 | PABX / Intercom Systems | Confirm if actively marketed or legacy listing |
| 5 | Projector & Video Wall | Confirm if offered standalone or only bundled |
| 6 | IT Managed Services | Confirm pricing model and current capacity |
| 7 | Apple dealer claim | Apple authorized reseller status requires official verification |

---

## 3. Website Audit Findings

**Source file:** WEBSITE-AUDIT.md (AGT-02)
**Site audited:** http://asmrcctvtechnologies.com/
**Overall SEO Grade assigned by AGT-02: F (Pre-optimization baseline)**

### Page Inventory (10 pages confirmed or partially confirmed)

| Page | Title Tag | Meta Description | Schema |
|---|---|---|---|
| / (homepage) | Present (over-length, brand duplicated) | MISSING | MISSING |
| /about.php | NOT CONFIRMED | MISSING | MISSING |
| /services.php | Present (brand duplicated, no keywords) | MISSING | MISSING |
| /products.php | Present (brand duplicated) | MISSING | MISSING |
| /partners.php | Present (brand duplicated) | MISSING | MISSING |
| /contact.php | Present (brand duplicated) | MISSING | MISSING |
| /news.php | NOT CONFIRMED | MISSING | MISSING |
| News post: OLOPSC (POE camera) | Present | MISSING | MISSING |
| News post: website-launch | NOT CONFIRMED | MISSING | MISSING |
| News post: dti-registered | NOT CONFIRMED | MISSING | MISSING |

### Critical Issues (Immediate Action Required)

| # | Issue | Impact | Scope |
|---|---|---|---|
| C1 | No meta descriptions on any page | Google writes non-ideal snippets; severely hurts CTR | All 10+ pages |
| C2 | No XML sitemap (404) | Search engines may miss pages, especially news posts with query string URLs | Site-wide |
| C3 | No JSON-LD structured data — especially LocalBusiness schema | No local rich results; invisible to Local Pack ranking signals | All pages |
| C4 | HTTP not redirecting to HTTPS | Duplicate site on two protocols; split link equity; security risk | Site-wide |
| C5 | No canonical tags | Duplicate content risk across HTTP/HTTPS and URL variations | All pages |

### High Issues (Address Within 30 Days)

| # | Issue | Impact |
|---|---|---|
| H1 | Multiple H1 tags per page (all pages) | Confuses Google about primary topic of each page |
| H2 | No Open Graph tags on any page | No social media preview cards when pages are shared |
| H3 | Title tags not keyword-optimized (no city/province/service keywords) | Titles don't include San Mateo, Rizal, or primary service terms |
| H4 | 4 pages with unconfirmed title tags (about.php, news.php, 2 posts) | May appear with no title in SERPs |
| H5 | No Google Maps embed on contact page | Local SEO trust signal missing |
| H6 | No robots.txt (404) | No crawl control; no sitemap pointer for search engines |

### Medium Issues (Address Within 60 Days)

- News posts missing Article schema
- FAQ section on contact.php lacks FAQPage schema
- All services on one /services.php page — no individual landing pages
- No location keywords in service descriptions
- Only 3 blog articles (2-year gap between first and second article)
- No business hours displayed on contact page
- News post URLs use query strings (?slug=) rather than clean paths

### Low Issues

- Brand name duplicated at end of title tags
- No BreadcrumbList schema
- Partners page doesn't link to partner websites
- Bot-detection JavaScript may interfere with Googlebot (see flag below)

### Bot-Detection Script — FLAGGED FOR TEAM LEAD REVIEW

AGT-02 observed that the website's JavaScript detects automated browser sessions and redirects them to external search engines (DuckDuckGo, Google, Google Maps, Yelp were all observed as redirect targets). This behavior:

1. Prevented mobile viewport screenshot capture during the audit
2. Disrupted JavaScript-based DOM inspection
3. Potentially interferes with Googlebot if misconfigured — Googlebot crawls JavaScript-heavy pages selectively

**This is a risk that requires immediate Team Lead and owner attention.** If Googlebot triggers this redirect logic, pages may not be indexed correctly. The purpose of this script and its exact trigger conditions must be confirmed with the developer before any further SEO work.

### Local Signals Present in Content (Positive Baseline)

- Full address visible on homepage and contact page: #51 General Luna St., Ampid-1, San Mateo, Rizal
- Phone numbers: 0929-510-6881 (Smart), 0976-382-3899 (Globe)
- Email: inquiries@asmrcctvtechnologies.com
- Client references to local and Metro Manila businesses
- News post about work at OLOPSC Marikina City (verified project)

Note: GBP-AUDIT.md lists the address as "110 Gen. Luna Ave, Ampid-1, San Mateo, 1850 Rizal." The website lists "#51 General Luna St." The discrepancy in street number must be resolved. NAP consistency is a critical local SEO factor.

---

## 4. Local SEO Opportunities

**Source file:** LOCAL-SEO-RESEARCH.md (AGT-01)

### Top Keyword Clusters

#### Cluster 1: CCTV & Security Installation (Core business, medium competition)
- "CCTV installation San Mateo Rizal" — transactional, medium competition, core target
- "CCTV installer near me" — high-intent mobile query
- "CCTV maintenance San Mateo Rizal" — low competition, post-installation service
- "Hikvision CCTV installer Rizal" — low-medium competition, brand-specific
- Tagalog variants: "magpainstall ng CCTV Rizal," "CCTV para sa bahay San Mateo," "magkano ang CCTV installation Rizal"

#### Cluster 2: Networking & IT Support (Low competition, quick win potential)
- "Networking solutions San Mateo Rizal" — low competition (Network Fix IT Solutions is only visible competitor)
- "IT support San Mateo Rizal" — low-medium competition
- "IT managed services San Mateo Rizal" — low competition, strong ASMR differentiator
- "Structured cabling San Mateo Rizal" — low competition

#### Cluster 3: Biometrics & Access Control (Niche, very low competition)
- "Biometric time attendance San Mateo Rizal" — low competition (only Kiel Biometric visible)
- "Door access control installation Rizal" — low competition
- "ZKTeco installer Philippines" — low-medium competition

#### Cluster 4: Fire Alarm (Niche, near-zero competition)
- "Fire alarm installation San Mateo Rizal" — very thin local competition observed
- "Fire alarm system para sa opisina" — Tagalog, low competition

#### Cluster 5: Software Development (Low competition, high differentiation)
- "Custom software development San Mateo Rizal" — low competition
- "POS system developer Philippines" — medium national competition
- "Web development San Mateo Rizal" — low local competition

### Gap Analysis: Where ASMR is Invisible vs. Quick Wins

**Invisible (requires GBP fix as prerequisite):**
- ASMR did not appear in any Google Maps search tested: CCTV installation San Mateo Rizal, IT support San Mateo Rizal, networking solutions San Mateo Rizal, biometrics San Mateo Rizal
- Root cause: wrong GBP category + zero reviews + no website link

**Quick wins (achievable within 30 days):**
- Fix GBP category → immediately eligible to appear in CCTV/security Maps searches
- Add website to GBP → increases GBP trust score and website click-throughs
- First 5 Google reviews → moves ASMR from invisible to competitive in Maps
- Add LocalBusiness JSON-LD schema → Google can confirm NAP match between website and GBP

**Medium-term wins (30–90 days):**
- Individual service landing pages with location keywords (e.g., /cctv-installation-san-mateo-rizal/)
- Tagalog/Taglish FAQ content on website and Facebook
- Brand partnership content pages (Hikvision installer, Cisco networking)

### Niche Opportunities (Low Competition, High Potential)

**Biometrics:** Only Kiel Biometric Security Shop (no website) and Good Hand CCTV compete in San Mateo/Rizal biometrics searches. A dedicated biometrics landing page would likely rank quickly.

**Fire Alarm:** No visible local competitors actively target "fire alarm installation San Mateo Rizal." This is an uncontested niche if ASMR can confirm active service delivery.

**Software Development local angle:** DevStruct Company PH is the only visible local software company in San Mateo with a GBP listing. ASMR's full-stack capability (hardware + software) is a unique differentiator with near-zero competing content.

---

## 5. Competitor Research

**Source files:** LOCAL-SEO-RESEARCH.md (AGT-01), GBP-AUDIT.md (AGT-03)
**Data sources:** Google Maps (2026-10-11), DuckDuckGo, Bing, direct website visits

### Top Competitors

| Business | Primary Strength | Reviews | Website | Maps Visibility |
|---|---|---|---|---|
| Good Hand CCTV — Antipolo | National brand, 49 reviews, 40 photos, active posts, authorized for Hikvision/Dahua/EZVIZ/ZKTeco | 5.0 / 49 | goodhand88.com (linked to GBP) | Appears in all CCTV queries |
| Good Hand Safety Solutions (Batasan-San Mateo Rd) | National brand presence near San Mateo border | 5.0 / 8 | goodhand88.com | Appears in CCTV queries |
| Technoplete CCTV — San Mateo | 311 GBP photos, 5 reviews, same municipality | 5.0 / 5 | None | Appears in CCTV San Mateo queries |
| AICA-Tech IT Solutions | Direct IT competitor, San Mateo based, functional website | None visible | aicatech.ph | Appears in IT support queries |
| DevStruct Company PH | Software development, San Mateo | 4.6 / 5 | devstruct.ph (inaccessible) | Appears in software queries |
| Citycure System Solutions | Security systems, Antipolo | 4.8 / 4 | Directory listing only | Appears in Antipolo CCTV queries |
| neltech cctv services | Facebook-only presence, Rizal province | N/A | None | Social only |

### How ASMR Compares

**Disadvantages:**
- Zero Google reviews vs. competitors with 4–49 reviews
- Wrong GBP category (Computer store) vs. competitors categorized correctly as CCTV/security installer
- No website link on GBP vs. most competitors having websites linked
- No GBP posts vs. Good Hand posting regularly
- No individual service landing pages vs. Good Hand having an e-commerce shop

**Advantages:**
- Broader service range than any single competitor (CCTV + networking + software development + biometrics + fire alarm + solar)
- Verified client base in recognizable brands (Dunkin', OLOPSC, Chef's Secret)
- Authorized partner with more brands than any local competitor (26 brands including Hikvision, Cisco, Fortinet, Juniper)
- Active custom software capability (Sub-ERP, WMS) — no local CCTV competitor offers this
- Functioning professional website vs. Technoplete CCTV (311 photos, no website) and neltech (Facebook only)

**Strategic position:** ASMR is the most capable IT+security company in San Mateo/Rizal by service breadth, but has the worst digital presence of all major local competitors. The gap is not about capability — it is entirely about digital infrastructure.

---

## 6. Google Business Profile Findings

**Source file:** GBP-AUDIT.md (AGT-03)
**GBP URL:** https://www.google.com/maps/place/ASMR+CCTV+TRADING/@14.682155,121.1150069

### Current GBP Status

A verified GBP listing exists for ASMR CCTV TRADING. It was confirmed via direct Google Maps navigation on 2026-10-11.

**GBP Completeness Score: 30 / 100 (Critical)**

### Critical GBP Gaps

| Issue | Status | Impact |
|---|---|---|
| Primary Category | "Computer store" — WRONG | ASMR does not appear in any CCTV or security search on Maps |
| Website Link | MISSING ("Add website" button visible) | No click-through path from GBP to website |
| Customer Reviews | ZERO visible | Last-place ranking signal; every local competitor has reviews |
| Lead Photo | Appears to show a Dunkin' Donuts sign (auto-filled Street View or misattributed photo) | Poor first impression for any visitor who finds the listing |
| GBP Posts | NONE | Signals inactive business |
| Business Description | NOT CONFIRMED (likely absent) | Cannot confirm without owner login |
| Services List | NOT CONFIRMED (likely absent) | Cannot confirm without owner login |

### What GBP Does Have

- Business name (all caps: ASMR CCTV TRADING)
- Address: 110 Gen. Luna Ave, Ampid-1, San Mateo, 1850 Rizal
- Phone: 0976 382 3899
- Hours: Mon–Sun 9 AM–5 PM (7 days)
- 21 photos (but lead photo is incorrect)
- Maps pin appears correctly placed

### Address Discrepancy — FLAG

The GBP lists the address as "110 Gen. Luna Ave." The website lists "#51 General Luna St." This NAP inconsistency will hurt local SEO. The correct address must be confirmed with the owner and applied consistently across GBP, website, Facebook, and any directory listings.

### Competitor GBP Benchmarks

| Business | Reviews | Rating | Website | Photos | Posts | In CCTV Search? |
|---|---|---|---|---|---|---|
| Good Hand CCTV Antipolo | 49 | 5.0 | YES | 40 | YES (active) | YES |
| Good Hand Safety Solutions | 8 | 5.0 | YES | 10 | YES | YES |
| Technoplete CCTV (San Mateo) | 5 | 5.0 | NO | 311 | NO | YES |
| **ASMR CCTV TRADING** | **0** | **None** | **NO** | **21** | **NO** | **NO** |

The three highest-weighted GBP ranking factors — correct category (15 pts), reviews (15 pts), website link (10 pts) — are all absent or wrong for ASMR, accounting for 40 out of 70 lost points.

**Important note:** It is unknown whether the GBP ownership is claimed and verified. If not claimed, the owner must claim it at business.google.com before any edits can be made.

---

## 7. Facebook Page Findings

**Source file:** FACEBOOK-AUDIT.md (AGT-04)
**Page URL:** https://www.facebook.com/ASMRCCTVTRADING
**Audit method:** Public view, no login, login popup dismissed

### Page Summary

| Element | Status |
|---|---|
| Followers | 170 |
| Facebook category | Computer Repair Service (also: IT Company, Computer Store — subcategories observed) |
| Verified badge | Not present |
| Profile photo | ASMR CCTV Trading logo (appropriate) |
| Cover photo | Text list of 12 services (functional but not visually compelling) |
| Website link in About | NOT LISTED (asmrcctvtechnologies.com referenced in posts but not in About page) |
| Instagram | Linked (asmrcctvtrading) |
| Rating | Not yet rated (1 review, content not accessible publicly) |
| Private messaging | Possible issue — one customer commented "I can't PM your page" |

### Recent Posts

5 posts were visible on the public feed (August 30 – September 17, 2025). All written content is in English. Key observations:
- Post 1 (website launch): highest engagement (4 reactions, 7 comments, 8 shares)
- Post 4 (CCTV installation in Pililla, Rizal): detailed project post with real photos and equipment specs
- Post 3 (Warehouse Management System deployment, Valenzuela City): zero visible reactions, no hashtags — high-value content under-promoted
- Customer comments on Post 1 are in Taglish; one potential customer mentioned they could not send a private message

### Reels Activity

10 reels exist with average 407 views per reel (177–634 range). Reel content could not be verified without login. Total reel views: approximately 4,070.

### Critical Content Gaps

1. **Website not linked in About page** — must be added immediately
2. **Messaging may be disabled** — customer explicitly said "I can't PM your page"; requires owner to check page settings
3. **All posts in English** — San Mateo/Rizal audience communicates in Taglish; no local-language content
4. **Zero promotional content** — no pricing, no packages, no seasonal offers
5. **Zero customer testimonials** — projects completed in Pililla and Valenzuela but no formal testimonial posts
6. **6 services on cover photo with no post content:** Public Address System, Solar Power, Fiber Cabling, PC Assembly/Formatting, Printer Services, IT Retail
7. **Systems Development severely under-promoted** — Warehouse Management System post had zero reactions and no hashtags; this is a high-value differentiator

### Language Gap

All page posts are in English. The single customer service interaction visible in comments was in Taglish ("Yes po. We have po"). The target market in San Mateo, Rizal communicates predominantly in Filipino/Taglish. This is the single largest content-tone gap observed.

---

## 8. Proposed Website Architecture

Based on the combined findings from WEBSITE-AUDIT.md and LOCAL-SEO-RESEARCH.md.

**Current structure (flat, single service page):**
```
/ (index.php)
/about.php
/services.php (all 23 services on one page, anchor-linked)
/products.php
/partners.php
/contact.php
/news.php
/news-post.php?slug=...
```

**Proposed improved structure:**

```
/ (homepage)
/about/
/services/ (hub page — links to all individual service pages)
  /services/cctv-installation-san-mateo-rizal/
  /services/networking-solutions-san-mateo-rizal/
  /services/it-managed-services-rizal/
  /services/biometrics-access-control-rizal/
  /services/fire-alarm-installation-rizal/
  /services/solar-setup-installation-rizal/
  /services/custom-software-development-rizal/
  /services/pos-system-philippines/
  /services/warehouse-management-system-philippines/
  /services/cctv-maintenance-repair/
/products/ (existing page, improved)
/partners/ (existing page, improved)
/contact/ (with Google Maps embed, Google review CTA, business hours)
/blog/ (replaces /news.php with clean URL structure)
  /blog/slug-here/ (clean paths, no query strings)
/faq/ (new — Tagalog/English bilingual FAQ targeting price and how-to queries)
```

**New priority landing pages to create:**

| Page | Target Keyword | Priority |
|---|---|---|
| /services/cctv-installation-san-mateo-rizal/ | "CCTV installation San Mateo Rizal" | Critical |
| /services/networking-solutions-san-mateo-rizal/ | "networking solutions San Mateo Rizal" | High |
| /services/biometrics-access-control-rizal/ | "biometric time attendance San Mateo Rizal" | High |
| /services/fire-alarm-installation-rizal/ | "fire alarm installation San Mateo Rizal" | High (low competition) |
| /services/it-managed-services-rizal/ | "IT managed services San Mateo Rizal" | High |
| /faq/ | Tagalog price/how-to queries | Medium |

**Internal linking improvements:**
- Each service landing page should link back to /contact/ with a prominent CTA
- Homepage should feature 3–5 top services with direct links to their landing pages
- /blog/ posts should link to the relevant service landing pages (e.g., OLOPSC POE camera post links to CCTV installation page)
- Add breadcrumb navigation to all inner pages (also enables BreadcrumbList schema)

---

## 9. Priority-Ranked Recommendations

Listed from highest to lowest priority. All recommendations are based directly on audit findings.

| # | Recommendation | Effort | Impact | Owner | Blocking Dependencies |
|---|---|---|---|---|---|
| 1 | Fix GBP primary category from "Computer store" to "Security system installer"; add secondary categories (security supplier, networking, IT support) | Low | Critical | Business owner (Odyssey) — requires GBP login | Must claim/verify GBP ownership first |
| 2 | Confirm GBP ownership is claimed at business.google.com; if not claimed, claim it immediately | Low | Critical | Business owner | None — prerequisite for all GBP actions |
| 3 | Add website URL (https://asmrcctvtechnologies.com) to GBP listing | Low | High | Business owner | GBP ownership confirmed (Rec 2) |
| 4 | Add website URL to Facebook About page under Websites & Social Links | Low | High | Business owner or page admin | None |
| 5 | Fix/enable Facebook page messaging (investigate "Can't PM" report from customer) | Low | High | Business owner or page admin | None |
| 6 | Replace GBP lead photo (apparent Dunkin' sign) with professional business photo | Low | High | Business owner | GBP ownership confirmed (Rec 2) |
| 7 | Begin Google review collection — contact Dunkin', OLOPSC, Chef's Secret, Honcho Logistics for reviews; target 10 reviews in 60 days | Low | High | Business owner | GBP ownership confirmed; working Maps listing |
| 8 | Implement HTTP-to-HTTPS 301 redirect site-wide | Low | High | Developer | Server/hosting access |
| 9 | Create robots.txt with sitemap pointer; generate and submit XML sitemap to Google Search Console | Low | High | Developer | Google Search Console access (requires domain verification) |
| 10 | Install Google Analytics 4 (GA4) and verify domain in Google Search Console | Low | High | Developer + Owner | Owner must verify domain; prerequisite for all Phase 2 measurement |
| 11 | Investigate and document the bot-detection JavaScript behavior — confirm whether Googlebot is excluded or affected | Medium | High | Developer | None — must be done before any other on-site SEO work |
| 12 | Add meta descriptions to all pages (start with homepage, /services.php, /contact.php) — 150–160 characters each, include city/service keywords | Medium | High | Developer | None |
| 13 | Implement LocalBusiness JSON-LD schema on homepage and contact page — must exactly match GBP NAP | Medium | High | Developer | Address discrepancy resolved (Rec 14) |
| 14 | Resolve NAP address discrepancy: GBP shows "110 Gen. Luna Ave"; website shows "#51 General Luna St." — confirm correct address with owner and apply consistently | Low | High | Owner confirms; Developer implements | Owner input required |
| 15 | Fix multiple H1 tags on all pages — reduce to one H1 per page, restructure to proper H1/H2/H3 hierarchy | Medium | Medium | Developer | None |
| 16 | Add Google Maps embed to /contact.php; display business hours | Low | Medium | Developer | None |
| 17 | Optimize title tags on all pages — include primary keyword + location (e.g., "CCTV Installation San Mateo Rizal — ASMR CCTV Trading") | Medium | Medium | Developer | None |
| 18 | Add Open Graph tags (og:title, og:description, og:image) to all pages — enables social media preview cards | Low | Medium | Developer | None |
| 19 | Create first priority service landing page: /services/cctv-installation-san-mateo-rizal/ | Medium | High | Developer + Content | Rec 11 (bot detection), Rec 13 (schema) |
| 20 | Create second priority service landing page: /services/biometrics-access-control-rizal/ | Medium | High | Developer + Content | None (biometrics niche is nearly uncontested) |
| 21 | Start GBP posts — minimum 1 post per week; use completed project photos | Low | Medium | Business owner or marketing | GBP ownership confirmed (Rec 2) |
| 22 | Add GBP services list — list all confirmed services in GBP Services section | Low | Medium | Business owner | GBP ownership confirmed (Rec 2) |
| 23 | Write and publish GBP business description (500–750 chars; include services, location, founding year, CTA) | Low | Medium | Business owner or marketing | GBP ownership confirmed (Rec 2) |
| 24 | Expand GBP photo library to 50+ photos (shopfront, team, before/after installation work, equipment) | Low-Medium | Medium | Business owner | GBP ownership confirmed (Rec 2) |
| 25 | Develop Tagalog/Taglish FAQ blog posts targeting price research queries ("magkano ang CCTV installation sa Pilipinas") | Medium | Medium | Content writer | Blog infrastructure (clean URL structure) |

---

## 10. Risks, Dependencies, and Access Needed

### Owner Login Required (Cannot Proceed Without)

| Action | Platform | Why It Matters |
|---|---|---|
| Confirm GBP is claimed | Google Business Profile (business.google.com) | If not claimed, no edits possible at all |
| Fix GBP primary category | Google Business Profile | Highest-impact single action — completely blocks Maps visibility |
| Add website link to GBP | Google Business Profile | Required to connect GBP to website for ranking signal |
| Add business description to GBP | Google Business Profile | Needed for completeness and customer trust |
| Add services to GBP | Google Business Profile | Needed for completeness |
| Upload replacement lead photo | Google Business Profile | Current Dunkin' photo creates wrong first impression |
| Verify domain in Google Search Console | Google Search Console | Required to submit sitemap and monitor indexing |
| Install Google Analytics 4 | Website (requires GA tag in PHP code or GTM) | Without this, all Phase 2 traffic measurement is impossible |
| Fix Facebook messaging settings | Facebook Page admin | Customer explicitly reported being unable to PM the page |
| Add website to Facebook About page | Facebook Page admin | Currently missing |

### Technical Risks

**Risk 1 — Bot-Detection JavaScript (HIGH)**
The website's JavaScript redirects automated browser sessions to external search engines. This was observed redirecting to DuckDuckGo, Google, Google Maps, and Yelp during the audit. If this script triggers on Googlebot (or other legitimate crawlers), the site may never be indexed correctly regardless of other SEO work. This must be reviewed before any on-site SEO implementation.

**Risk 2 — HTTP/HTTPS Dual Access (MEDIUM)**
The site loads over both HTTP and HTTPS without a redirect. This creates duplicate URLs that split link equity and may confuse search engine indexing. Until 301 redirects are implemented, any links pointing to http:// versions do not benefit the https:// canonical.

**Risk 3 — Query String URLs for News Posts (LOW-MEDIUM)**
News posts use the URL pattern /news-post.php?slug=... rather than clean paths. These URLs are less shareable and less SEO-friendly. However, changing URL structure after the site has been indexed requires proper 301 redirects to avoid losing any existing link equity.

### Content Risks

**Risk 1 — Unverified Services Being Promoted**
Seven services on the website have not been confirmed as actively delivered (see Section 2). If Phase 2 content or ads promote services that ASMR cannot currently fulfill (e.g., Fire Alarm without FSIC license, Solar without active projects), this creates a business risk of inquiries that cannot be converted.

**Risk 2 — NAP Address Inconsistency**
GBP shows "110 Gen. Luna Ave"; website shows "#51 General Luna St." Publishing content, schema, or directory listings with inconsistent NAP actively harms local SEO. The correct address must be established before any schema or directory work proceeds.

### Access Gaps Summary

| Access | Status | Who Needs to Act |
|---|---|---|
| GBP owner access | UNKNOWN — may not be claimed | Odyssey (owner) |
| Google Search Console | NOT INSTALLED | Developer + Odyssey |
| Google Analytics 4 | NOT INSTALLED | Developer |
| Facebook page admin access | Owner has this | No gap — owner action needed |
| Website PHP source access | Developer has this | No gap |
| GBP Insights / analytics | Requires GBP owner login | Odyssey |

---

## 11. Proposed Project Milestones

### Week 1–2: Quick Wins (Zero-Cost, High-Impact)

**Goal:** Get ASMR appearing in Google Maps for the first time.

| Action | Owner | Success Metric |
|---|---|---|
| Confirm GBP claimed/verified | Odyssey | GBP editable in business.google.com |
| Fix GBP primary category to "Security system installer" | Odyssey | ASMR appears in at least 1 CCTV Maps search |
| Add website link to GBP | Odyssey | Website traffic from GBP referral visible in GA4 |
| Add website to Facebook About page | Odyssey / page admin | Website listed in Facebook About |
| Fix Facebook messaging | Odyssey / page admin | Messenger button functional |
| Replace GBP lead photo | Odyssey | Professional ASMR photo is the lead photo |
| Send Google review requests to 3 clients | Odyssey | 3+ review requests sent |
| Resolve correct NAP address | Odyssey | Confirmed single address for all platforms |

**Success criteria for Week 1–2:** ASMR CCTV Trading appears in at least one Google Maps search for "CCTV installation San Mateo Rizal" or equivalent service + location query.

---

### Month 1: Technical SEO Foundations

**Goal:** Give Google the signals it needs to crawl, index, and understand the website.

| Action | Owner | Success Metric |
|---|---|---|
| Investigate and resolve bot-detection JavaScript | Developer | Googlebot can crawl all pages without redirect |
| Implement HTTP-to-HTTPS 301 redirect | Developer | http:// URLs redirect to https:// |
| Create robots.txt pointing to sitemap | Developer | robots.txt returns 200 with sitemap URL |
| Generate and submit XML sitemap | Developer | All 10 pages indexed in Search Console |
| Verify domain in Google Search Console | Odyssey + Developer | Domain verified, sitemap submitted |
| Install Google Analytics 4 | Developer | GA4 tracking fires on all pages |
| Add meta descriptions to all pages | Developer | 0 pages with missing meta descriptions |
| Add canonical tags to all pages | Developer | 0 pages with missing canonical |
| Implement LocalBusiness JSON-LD schema (homepage, contact) | Developer | Schema validates in Google Rich Results Test |
| Fix multiple H1 tags site-wide | Developer | 1 H1 per page |

**Success criteria for Month 1:** All pages indexed in Google Search Console; no crawl errors; LocalBusiness schema validates; GA4 tracking live.

---

### Month 2: Content and Landing Pages

**Goal:** Create content that can rank for target keywords.

| Action | Owner | Success Metric |
|---|---|---|
| Create /services/cctv-installation-san-mateo-rizal/ landing page | Developer + Content | Page indexed; targeting "CCTV installation San Mateo Rizal" |
| Create /services/biometrics-access-control-rizal/ landing page | Developer + Content | Page indexed; targeting biometrics queries |
| Create /services/fire-alarm-installation-rizal/ landing page | Developer + Content | Page indexed (verify FSIC compliance first) |
| Optimize title tags site-wide with keywords + location | Developer | All pages have keyword-optimized, properly formatted titles |
| Add Google Maps embed to contact page with business hours | Developer | Maps embed visible on /contact.php |
| Add Open Graph tags site-wide | Developer | Social media previews work on all pages |
| Publish first Tagalog/Taglish FAQ blog post | Content | Post published, indexed; targets at least one Tagalog query |
| Begin GBP weekly posts | Odyssey / marketing | 4 GBP posts published in Month 2 |
| Add services and description to GBP | Odyssey | GBP completeness score raised to 60+ |

**Success criteria for Month 2:** 3 new service landing pages indexed; at least 1 Tagalog FAQ post; GBP completeness score above 60/100; ASMR appearing in Maps for at least 2 service queries.

---

### Month 3: Review Generation, Content Calendar, Authority Building

**Goal:** Build review authority and establish consistent content publishing rhythm.

| Action | Owner | Success Metric |
|---|---|---|
| Reach 10+ Google reviews | Odyssey (outreach) | 10+ reviews visible on GBP |
| Publish 4 Facebook posts per month in Tagalog/Taglish | Content / marketing | Consistent posting schedule; engagement increases |
| Publish 2–4 blog posts per month (project showcases, FAQs) | Content | At least 8 new articles by end of Month 3 |
| Create brand partnership content (Hikvision installer page, Cisco networking page) | Developer + Content | 2 brand-specific landing pages indexed |
| Submit ASMR to local directories (servicepros.ph, cybo.com, ofphilippines.com) | Marketing | Consistent NAP across 5+ directories |
| Add FAQPage schema to /faq/ or /contact.php | Developer | FAQ rich results appearing in Google |

**Success criteria for Month 3:** 10+ Google reviews; ASMR appearing in Maps for 5+ query types; organic website impressions (visible in Search Console) showing growth; 20+ Facebook followers added from Month 1 baseline.

---

## 12. Evidence Summary

This section records the specific audit file and observation for each major finding. No assumptions are presented as facts.

| Finding | Source File | Specific Observation |
|---|---|---|
| GBP primary category is "Computer store" | GBP-AUDIT.md §2 | Observed in public GBP listing on Google Maps; confirmed as reason ASMR is absent from CCTV searches |
| ASMR absent from all Google Maps CCTV searches tested | GBP-AUDIT.md §1.3, LOCAL-SEO-RESEARCH.md §Gap 1 | Google Maps search "CCTV installation San Mateo Rizal Philippines" returned 8 businesses; ASMR not among them |
| GBP website link missing | GBP-AUDIT.md §2 | "Add website" button visible on public GBP listing |
| GBP completeness score 30/100 | GBP-AUDIT.md §3 | Scored across 13 GBP elements; missing category, reviews, website, description, services, posts |
| GBP lead photo appears to be Dunkin' Donuts sign | GBP-AUDIT.md §2 | Observed in public listing; described as "likely a Google Street View auto-fill or misattributed photo" |
| Zero Google reviews | GBP-AUDIT.md §2 | "No star rating displayed, no review count shown publicly" |
| No meta descriptions on any page | WEBSITE-AUDIT.md §2 | Confirmed across all 10 pages audited |
| No XML sitemap or robots.txt | WEBSITE-AUDIT.md §3 | Both return HTTP 404 |
| No LocalBusiness JSON-LD schema | WEBSITE-AUDIT.md §4 | "Zero structured data found on any page" |
| HTTP not redirecting to HTTPS | WEBSITE-AUDIT.md §3 | "http://asmrcctvtechnologies.com/ loads without redirecting to HTTPS" |
| Multiple H1 tags per page | WEBSITE-AUDIT.md §3 | Observed on all pages audited (homepage: 5 H1 tags) |
| Bot-detection JavaScript redirects automated browsers | WEBSITE-AUDIT.md §3, §9 | Observed during audit — redirected to DuckDuckGo, Google, Google Maps, Yelp; disrupted mobile screenshot capture |
| No Google Analytics or Search Console installed | GBP-AUDIT.md §4, WEBSITE-AUDIT.md (supporting context) | "No GA4 or UA tag detected; No GTM container detected" |
| Facebook website not listed in About page | FACEBOOK-AUDIT.md §Page Profile Summary | "Website: Not listed in Websites & Social Links section" |
| Facebook messaging possibly disabled | FACEBOOK-AUDIT.md §Recent Posts | Customer Venus Lorca Jimenez commented "I can't PM your page" on Post 1 |
| All Facebook posts in English; customer responds in Taglish | FACEBOOK-AUDIT.md §Content Quality | "Posts written in English; customer service replies in Taglish" |
| 170 Facebook followers | FACEBOOK-AUDIT.md §Page Profile Summary | Directly observed on 2026-10-11 |
| Good Hand CCTV Antipolo: 49 reviews, 5.0 rating | GBP-AUDIT.md §5, LOCAL-SEO-RESEARCH.md | Observed on Google Maps competitor listing |
| Technoplete CCTV (San Mateo) has 311 GBP photos, no website, appears in Maps | GBP-AUDIT.md §5 | Confirmed via public GBP listing |
| NAP address discrepancy: GBP "110 Gen. Luna Ave" vs. website "#51 General Luna St." | GBP-AUDIT.md §2, WEBSITE-AUDIT.md §6 | Cross-reference of GBP listing and website content |
| Fire Alarm and Biometrics niches have near-zero local competition | LOCAL-SEO-RESEARCH.md §Gap 9, §Keyword Tables | "Only Kiel Biometric visible" for biometrics; "Very thin local competition observed" for fire alarm |
| RFID attendance monitoring confirmed as offered service | FACEBOOK-AUDIT.md §Services | Owner replied "Yes po. We have po" to customer inquiry |
| PC Assembly, IT Disaster Recovery, Fiber Cabling, Printer Services on Facebook but not website | FACEBOOK-AUDIT.md §Services, VERIFIED-SERVICES.md | Cross-reference: cover photo items not found in website service listings |

---

## 13. Decisions Requiring Team Lead or Owner Approval

The following decisions cannot proceed without explicit authorization. Phase 2 work in the affected areas must pause until these are resolved.

| # | Decision | Who Must Decide | Why It Cannot Proceed Without This |
|---|---|---|---|
| 1 | **GBP category change** — Change primary category from "Computer store" to "Security system installer" (and confirm secondary categories) | Business owner (Odyssey) | Requires GBP owner login; changes how ASMR appears in ALL Maps searches; if wrong choice is made, Maps visibility continues to suffer |
| 2 | **GBP ownership claim** — Confirm whether GBP has been claimed; if not, owner must claim at business.google.com | Business owner (Odyssey) | If listing is unclaimed, no edits are possible by any agent or developer |
| 3 | **Bot-detection script review** — Determine the purpose of the JavaScript redirect behavior and confirm whether Googlebot is excluded | Developer + Owner | All on-site SEO work may be wasted if Googlebot is being redirected away from the site |
| 4 | **HTTP-to-HTTPS redirect implementation** — Approve server-side 301 redirect from all HTTP URLs to HTTPS | Developer + Owner (server/hosting access) | Requires hosting admin access; if done incorrectly can cause site downtime |
| 5 | **robots.txt content** — Confirm which sections of the site (if any) should be blocked from crawling | Developer + Owner | Incorrectly blocking pages in robots.txt can cause pages to be deindexed |
| 6 | **Schema implementation approach** — Confirm correct NAP (address) before any LocalBusiness schema is published | Owner (confirm address) | Publishing schema with wrong address worsens NAP inconsistency |
| 7 | **Fire alarm and solar services status** — Confirm these services are actively offered and whether FSIC/trade licensing requirements are met before promoting | Business owner (Odyssey) | Promoting regulated services that cannot be delivered creates business and legal risk |
| 8 | **Content language policy** — Decide whether Facebook and website content will be published in Tagalog, English, or Taglish, and set a standard | Business owner (Odyssey) + content team | Without a decision, content production for Phase 2 will be inconsistent |
| 9 | **Google review outreach strategy** — Authorize direct outreach to named clients (Dunkin', OLOPSC, Chef's Secret, Honcho Logistics) for Google reviews | Business owner (Odyssey) | Owner must personally reach out to clients or authorize a team member to do so; agent cannot contact clients |
| 10 | **Apple authorized dealer claim** — Confirm whether ASMR is an actual Apple authorized reseller before this is used in SEO content or GBP | Business owner (Odyssey) | Apple authorized reseller status requires official Apple authorization; ASSUMED — not confirmed |
| 11 | **Website URL structure change** — Decision on whether to change news post URLs from ?slug= query strings to clean /blog/slug/ paths | Developer + Owner | Changing URLs after indexing requires 301 redirects; if done without redirects, any indexed pages lose their ranking |
| 12 | **GBP address correction** — Choose the correct street address to standardize across all platforms (GBP, website, Facebook, schema) | Business owner (Odyssey) | Every platform currently has a different version; schema cannot be implemented until the correct address is confirmed |

---

*End of Team Lead Consolidated Report*
*Report Date: 2026-10-11*
*Prepared by: QA and Analytics Agent (AGT-05)*
*Reviewed by: [Team Lead — pending]*
*Approved by: [Odyssey Jan Pascual — pending]*
