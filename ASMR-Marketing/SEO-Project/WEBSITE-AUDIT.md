# ASMR CCTV Technologies — Website SEO & Content Audit
**Audit Date:** 2026-10-11
**Auditor:** Website Developer Agent (AGT-02)
**Site Audited:** http://asmrcctvtechnologies.com/
**Agent:** Phase 1 Technical SEO & Content Audit

---

## Executive Summary

The ASMR CCTV Trading website (asmrcctvtechnologies.com) is a professionally designed PHP-based site with a modern NVR-style dark theme. However, it has **critical and widespread SEO deficiencies** that are preventing it from ranking in local search results. The most serious problems are: zero meta descriptions across all pages, no structured data (JSON-LD) schema, no XML sitemap, no robots.txt, no canonical tags, and no Open Graph tags. The site does support HTTPS but does not enforce a redirect from HTTP to HTTPS. No blog content is optimized for search. The site contains strong local signals in its visible content (San Mateo, Rizal address, local client names) but these are invisible to search engines because the metadata layer is completely absent.

**Overall SEO Grade: F (Pre-optimization baseline)**
The site requires foundational technical SEO work before any content or link-building effort will be effective.

---

## 1. Page Inventory

| URL | Page Title (from browser/source) | H1 (first/primary) | Meta Description Present? | Schema Present? |
|-----|------|------|------|------|
| / (index.php) | ASMR CCTV Trading — Your Trusted Partner for Complete IT Solutions — ASMR CCTV Trading | Your trusted partner for complete IT solutions. | NO | NO |
| /about.php | NOT CONFIRMED — WebFetch returned title as NOT FOUND (possible missing title tag) | Who we are. | NO | NO |
| /services.php | Services — ASMR CCTV Trading — ASMR CCTV Trading | Hardware, networks, security, and software — end to end. | NO | NO |
| /products.php | Products — ASMR CCTV Trading — ASMR CCTV Trading | A comprehensive line of IT products. | NO | NO |
| /partners.php | Partners & Clients — ASMR CCTV Trading — ASMR CCTV Trading | Backed by the brands you already trust. | NO | NO |
| /contact.php | Contact Us — ASMR CCTV Trading — ASMR CCTV Trading | Let's talk about what your business needs. | NO | NO |
| /news.php | NOT CONFIRMED — WebFetch returned title as NOT FOUND | Latest from ASMR CCTV Trading | NO | NO |
| /news-post.php?slug=poe-camera-maintenance-olopsc-marikina | Preventive Maintenance and Replacement of Defective POE Camera at OLOPSC Marikina City — ASMR CCTV Trading — ASMR CCTV Trading | Preventive Maintenance and Replacement of Defective POE Camera at OLOPSC Marikina City | NO | NO |
| /news-post.php?slug=website-launch | NOT CONFIRMED — WebFetch returned NOT FOUND | ASMR CCTV Trading Website Is Now Live | NO | NO |
| /news-post.php?slug=dti-registered | NOT CONFIRMED — WebFetch returned NOT FOUND | ASMR CCTV Trading Now DTI-Registered | NO | NO |

**Notes on title tags:** Several pages returned NOT FOUND for title tags via HTML extraction (about.php, news.php, website-launch post, dti-registered post). Either these pages are missing `<title>` tags or the extraction tool failed to parse them. This requires verification by viewing page source directly. Pages where the browser tab confirmed titles are marked with full titles above.

---

## 2. Meta Tags Findings

### Homepage (/)
- **Title:** ASMR CCTV Trading — Your Trusted Partner for Complete IT Solutions — ASMR CCTV Trading
  - Issue: Brand name appears twice ("— ASMR CCTV Trading" repeated at end). Title is 83 characters — over the recommended 55–60 character limit.
- **Meta Description:** NOT PRESENT
- **Meta Keywords:** NOT PRESENT
- **Meta Robots:** NOT PRESENT (defaults to index, follow — acceptable but not explicit)
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE (og:title, og:description, og:image, og:url, og:type all missing)
- **Viewport:** `width=device-width, initial-scale=1.0, viewport-fit=cover` — PRESENT and correct
- **H1 Tags (multiple found):**
  - "Your trusted partner for complete IT solutions." (primary hero)
  - "Decades of combined IT experience, built into one end-to-end team."
  - "One team for hardware, networks, security, and software."
  - "Personalized service, without the markup of a middleman."
  - "Trusted across food & beverage, education, logistics, and retail."
  - Issue: Multiple H1 tags on one page is an SEO anti-pattern. Only one H1 should exist per page.

### /about.php
- **Title:** NOT CONFIRMED (may be missing)
- **Meta Description:** NOT PRESENT
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE
- **H1 Tags (multiple):**
  - "Who we are."
  - "Reliable hardware, software, networking, and security — from one team."
  - Issue: Multiple H1 tags present

### /services.php
- **Title:** Services — ASMR CCTV Trading — ASMR CCTV Trading (confirmed via browser DOM)
  - Issue: Brand name duplicated; title not keyword-optimized (no city, no service keywords)
- **Meta Description:** NOT PRESENT
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE
- **H1 Tags (multiple):**
  - "Hardware, networks, security, and software — end to end."
  - "Physical systems, installed and maintained on-site."
  - "Custom systems, built around how your team actually works."
  - Issue: Multiple H1 tags; no location signal

### /products.php
- **Title:** Products — ASMR CCTV Trading — ASMR CCTV Trading
- **Meta Description:** NOT PRESENT
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE
- **H1 Tags (multiple):**
  - "A comprehensive line of IT products."
  - "Everything from a single workstation to full infrastructure."
  - "The stack behind our custom software."
  - "Need a specific configuration?"

### /partners.php
- **Title:** Partners & Clients — ASMR CCTV Trading — ASMR CCTV Trading
- **Meta Description:** NOT PRESENT
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE
- **H1 Tag:** "Backed by the brands you already trust."

### /contact.php
- **Title:** Contact Us — ASMR CCTV Trading — ASMR CCTV Trading
- **Meta Description:** NOT PRESENT
- **Canonical:** NOT PRESENT
- **Open Graph:** NONE
- **H1 Tag:** "Let's talk about what your business needs."
- **H2 Tags:** "Request a free consultation.", "Visit us", "Call us", "Message us", "Frequently asked questions."

### /news.php
- **Title:** NOT CONFIRMED
- **Meta Description:** NOT PRESENT
- **H1 Tag:** "Latest from ASMR CCTV Trading"

### News Posts
- All three news posts are missing meta descriptions, canonical tags, and Open Graph tags.
- The POE Camera post has a full title tag confirmed.
- The other two posts had title tags NOT CONFIRMED.

---

## 3. Technical SEO Findings

### HTTPS Status
- **HTTPS Available:** YES — https://asmrcctvtechnologies.com/ loads correctly with valid SSL certificate.
- **HTTP-to-HTTPS Redirect:** NOT ENFORCED — http://asmrcctvtechnologies.com/ loads without redirecting to HTTPS. Users and search engines can access the site over insecure HTTP. Google prioritizes HTTPS sites; allowing HTTP access is a ranking disadvantage and a security risk.
- **Recommendation:** Implement a server-side 301 redirect from all HTTP URLs to HTTPS.

### robots.txt
- **Status:** HTTP 404 NOT FOUND at http://asmrcctvtechnologies.com/robots.txt
- **Impact:** Without robots.txt, search engines have no crawl directives. This is not immediately harmful (Google crawls without it) but it means no control over crawl budget and no ability to block unwanted sections.
- **Recommendation:** Create a robots.txt file at minimum pointing to the sitemap.

### XML Sitemap
- **Status:** HTTP 404 NOT FOUND at http://asmrcctvtechnologies.com/sitemap.xml
- **Impact:** Without a sitemap, search engines must discover pages by crawling links alone. Pages reachable only via JavaScript or deep in navigation may not be indexed. Given the site has news posts with URL query parameters (?slug=), a sitemap is especially important.
- **Recommendation:** Generate and submit an XML sitemap to Google Search Console.

### Canonical Tags
- **Status:** NONE present on any page audited.
- **Impact:** Without canonicals, if content appears at multiple URLs (e.g., http vs https, trailing slash vs no slash), search engines may index duplicate versions and split link equity.
- **Recommendation:** Add `<link rel="canonical">` to every page pointing to the preferred HTTPS URL.

### Page Speed / Load Feel
- **Observation:** At desktop width, the site loads with visible content quickly on first render (initial screenshot was complete). The design uses a dark theme with what appears to be minimal image assets (NVR-style mock camera frames). Load feel: MEDIUM-FAST. Formal Core Web Vitals measurement was not possible in this audit session.
- **Note:** JavaScript on the site appears to include bot-detection/redirect logic that sends automated browsers to search engines — this was observed repeatedly during auditing. This is an unusual implementation that could potentially interfere with Googlebot crawling if misconfigured.

### Heading Hierarchy
- Multiple H1 tags per page observed across all pages audited.
- H2 tags are used inconsistently — some pages use H3/H4 for sub-services and skip H2.
- Proper heading hierarchy (one H1, multiple H2, H3 as needed) should be implemented site-wide.

---

## 4. Structured Data Findings

- **JSON-LD Schema Present:** NO — zero structured data found on any page.
- **Schema Types Expected/Recommended but Missing:**
  - `LocalBusiness` or `ProfessionalService` on homepage and contact page — would enable rich results showing address, phone, hours in Google search.
  - `Service` schema on services.php — would help Google understand each service category.
  - `BreadcrumbList` on inner pages — improves site navigation display in SERPs.
  - `Article` or `NewsArticle` on news post pages — enables Google News eligibility and article-style rich results.
  - `FAQPage` on contact.php (FAQ section exists) — enables FAQ rich results in SERPs.
  - `Organization` on about.php — establishes entity identity for Google's Knowledge Graph.
- **Recommendation:** Implement JSON-LD schema starting with LocalBusiness on homepage as highest priority.

---

## 5. Mobile Usability

- **Viewport Meta Tag:** PRESENT — `width=device-width, initial-scale=1.0, viewport-fit=cover`
- **Visual Responsiveness:** Based on desktop screenshots, the site uses a modern layout with flex/grid-style design that appears designed to reflow at mobile widths. Navigation shows a full horizontal menu at desktop width, consistent with a hamburger menu implementation for mobile.
- **Direct Mobile Screenshot:** INCONCLUSIVE — mobile viewport testing was disrupted by the site's bot-detection JavaScript, which redirected the browser session before a mobile screenshot could be captured.
- **Tap Target Assessment:** Not directly verifiable in this session.
- **Assessment:** Site appears designed with mobile in mind based on viewport tag and visual layout, but direct mobile confirmation is recommended via a real mobile device or Google Search Console's Mobile Usability report.

---

## 6. Content and Local SEO Signals

### Local Signals Present in Page Content
- Full address visible on homepage and contact page: **#51 General Luna St., Ampid-1, San Mateo, Rizal**
- Phone numbers on contact page: **0929-510-6881 (Smart)**, **0976-382-3899 (Globe)**
- Email: **inquiries@asmrcctvtechnologies.com**
- "Founded 2022, San Mateo, Rizal" mentioned on homepage
- Client logos/names include local businesses (Dunkin' PH franchise, Chef's Secret, OLOPSC Marikina)
- Services page references installations in Marikina City (news post about OLOPSC)
- Website-launch news post mentions "San Mateo, Rizal, and the Metro Manila area"

### Local Signals MISSING from Metadata
- No city/location keywords in any page title tag
- No meta descriptions that would show San Mateo/Rizal/Philippines in search snippets
- No LocalBusiness schema with NAP (Name, Address, Phone)
- No Google Business Profile link or embed observed
- No Google Maps embed on contact page
- No business hours displayed

### Services Content Quality
- Services page is comprehensive with 20 distinct services across hardware and software categories.
- Each service has a short description visible in the page content.
- Services page lacks location context (no "in San Mateo, Rizal" or "serving Metro Manila" text visible in service listings).
- No individual landing pages per service — all services are on one /services.php page.

### Blog / News Content
- 3 news articles exist:
  1. "Preventive Maintenance and Replacement of Defective POE Camera at OLOPSC Marikina City" (Sept 17, 2026)
  2. "ASMR CCTV Trading Website Is Now Live" (Sept 17, 2026)
  3. "ASMR CCTV Trading Now DTI-Registered" (Aug 11, 2024)
- Articles lack meta descriptions and Open Graph tags — they will not generate preview cards when shared on social media.
- Article 3 (DTI registration) is dated August 2024 — gap of over 2 years before the next article.
- Articles are positive signals (proof of active business) but are not yet SEO-optimized.

---

## 7. Internal Linking Map

```
Homepage (/)
├── /about.php
├── /services.php
│   ├── /services.php#cctv
│   ├── /services.php#networking
│   ├── /services.php#software
│   └── /services.php#hardware
├── /products.php
├── /partners.php
├── /contact.php
│   └── /contact.php#consult
├── /news.php
│   ├── /news-post.php?slug=poe-camera-maintenance-olopsc-marikina
│   ├── /news-post.php?slug=website-launch
│   └── /news-post.php?slug=dti-registered
└── /contact.php#consult (Free Consultation CTA — repeated in header)
```

**Observations:**
- Navigation is consistent across all pages (full menu in header).
- No orphan pages detected — all pages are reachable from the homepage via navigation.
- Services.php uses anchor links (#cctv, #networking, etc.) for in-page navigation — these may not be crawled as separate pages.
- Footer likely contains additional links (not fully mapped in this session).
- News posts use query-string URLs (/news-post.php?slug=...) — this is a less ideal URL structure than clean paths (/news/title-slug/). Verify with PHP routing options.
- No breadcrumb navigation observed on inner pages.

---

## 8. Priority Issues

### CRITICAL (Immediate Action Required)

| # | Issue | Impact | Affected Pages |
|---|-------|--------|----------------|
| C1 | No meta descriptions on any page | Google writes its own snippets, often pulling non-ideal text; severely hurts CTR from search | All 10+ pages |
| C2 | No XML sitemap | Search engines may not discover or index all pages | Site-wide |
| C3 | No JSON-LD structured data (especially LocalBusiness) | Zero chance of local rich results; invisible to Local Pack ranking signals | All pages |
| C4 | HTTP not redirecting to HTTPS | Duplicate site over two protocols; split link equity; security risk | Site-wide |
| C5 | No canonical tags | Duplicate content risk between HTTP/HTTPS and URL variations | All pages |

### HIGH (Address Within 30 Days)

| # | Issue | Impact | Affected Pages |
|---|-------|--------|----------------|
| H1 | Multiple H1 tags per page | Confuses Google about primary topic of each page | All pages |
| H2 | No Open Graph tags | No social media preview cards when pages are shared (no image, title, or description shown) | All pages |
| H3 | Page title tags not keyword-optimized | Titles don't include city, province, or primary service keywords | Most pages |
| H4 | Possible missing title tags on about.php, news.php, and 2 news posts | Pages may not appear with proper titles in SERPs | 4 pages |
| H5 | No Google Maps embed on contact page | Local SEO trust signal missing; harder for users to verify physical location | /contact.php |
| H6 | No robots.txt | No crawl control; no sitemap pointer for search engines | Site-wide |

### MEDIUM (Address Within 60 Days)

| # | Issue | Impact | Affected Pages |
|---|-------|--------|----------------|
| M1 | News posts missing Article schema | Not eligible for Google News or article-style rich results | 3 news posts |
| M2 | FAQ section on contact.php lacks FAQPage schema | Missing FAQ rich result opportunity in SERPs | /contact.php |
| M3 | Services on one page vs. individual landing pages | Single /services.php reduces keyword targeting surface area | /services.php |
| M4 | No location keywords in service content | Services descriptions don't mention San Mateo, Rizal, or Metro Manila | /services.php |
| M5 | Blog content gap (2024–2026) | Only 3 articles; no consistent content strategy; thin topical authority | /news.php |
| M6 | No business hours displayed | Missing from contact page and schema; Local Pack requires hours | /contact.php |
| M7 | News post URLs use query strings (?slug=) | Less ideal than clean URL paths for SEO and sharing | News posts |

### LOW (Address When Possible)

| # | Issue | Impact | Affected Pages |
|---|-------|--------|----------------|
| L1 | Brand name duplicated in title tags | Minor; "— ASMR CCTV Trading — ASMR CCTV Trading" at end of title | All pages |
| L2 | No meta keywords tag | Minor — Google ignores meta keywords, but consistency is good | All pages |
| L3 | No BreadcrumbList schema | Missing breadcrumb display in SERPs | Inner pages |
| L4 | Bot-detection JavaScript may interfere with Googlebot | Googlebot primarily crawls without JavaScript; needs verification | Site-wide |
| L5 | Partners page doesn't link to partner websites | Missed opportunity for trust signals (reciprocal links) | /partners.php |

---

## 9. Data Limitations

1. **Bot-detection redirect:** The website's JavaScript appears to detect automated browser sessions and redirect to external search engines (DuckDuckGo, Google, Google Maps, Yelp were all observed as redirect targets). This prevented direct mobile viewport screenshot capture and some JavaScript-based DOM inspection. All critical data was obtained via static HTML fetch (WebFetch) and the first unauthenticated browser session before redirection.

2. **HTTP-to-HTTPS redirect not fully confirmed:** WebFetch automatically upgrades HTTP URLs to HTTPS before fetching, so direct comparison was not possible through that tool. The browser session confirmed HTTPS loads the site correctly, and HTTP also loaded the site in the initial session before being redirected. Recommend verifying HTTP redirect with curl or GTmetrix.

3. **Title tags on 4 pages not confirmed:** About.php, news.php, website-launch post, and dti-registered post had title tags returned as NOT FOUND by WebFetch. This may indicate missing `<title>` tags OR a WebFetch extraction limitation. Page source verification recommended.

4. **Core Web Vitals not measured:** LCP, FID/INP, and CLS could not be measured in this session. Use Google Search Console or PageSpeed Insights for formal CWV data.

5. **Google Business Profile not audited:** Whether the company has a Google Business Profile (Maps listing) is outside scope of website audit; this should be audited separately.

6. **Footer content not fully mapped:** Footer links and content were not extracted in full in this session.

7. **Full page source not reviewed:** All findings are based on processed/rendered HTML; raw PHP source code was not reviewed. There may be server-side SEO logic not visible in rendered output.

---

*End of Audit — ASMR CCTV Technologies — 2026-10-11*
*Agent: AGT-02 Website Developer Agent*
