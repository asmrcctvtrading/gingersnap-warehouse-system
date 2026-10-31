# ASMR CCTV Trading — Risks and Dependencies Register
**Document Type:** Governance — Risk and Dependency Tracking
**Date:** 2026-10-11
**Prepared by:** Execution Manager (AGT-EM)
**Status:** Phase 1 complete; Phase 2 pending approval

---

## Overview

This document catalogs all known risks, access dependencies, technical blockers, and owner-level decisions that must be resolved before or during Phase 2 implementation. It is a living register and should be updated as items are resolved or new risks are identified.

---

## Access Dependencies

The following access items must be resolved before the associated Phase 2 tasks can begin. None of these can be delegated to a developer or content agent — they require direct action by the business owner (Odyssey Jan Pascual).

| # | Access Required | Platform | How to Resolve | Blocks |
|---|---|---|---|---|
| A-001 | GBP ownership / verification | Google Business Profile (business.google.com) | Owner logs in to business.google.com and confirms or claims the listing; if unclaimed, verification may take 1–2 weeks via postcard or video call | T-001 through T-005; all GBP-related tasks |
| A-002 | Google Search Console — domain verification | Google Search Console | Owner or developer adds TXT record to domain DNS, or uploads HTML verification file to website; must be done before sitemap submission | T-013, T-017, T-035 |
| A-003 | Google Analytics 4 setup | GA4 / Website code | Developer adds GA4 measurement tag to all PHP pages (or via Google Tag Manager); owner verifies the property in GA4 | T-017, T-035 |
| A-004 | Facebook Page Admin access | Facebook | Owner already has admin access; must log in to make category, email, website, and messaging changes | T-006 through T-009 |
| A-005 | Website server / cPanel | Hosting provider | All file edits (robots.txt, sitemap, PHP code changes) require access to the hosting account's cPanel or FTP/SFTP | T-010 through T-019 |

---

## Technical Risks

### RISK-T01: Bot-Detection Script — SEVERITY: HIGH

**Description:** The website at asmrcctvtechnologies.com contains JavaScript that detects automated browser sessions and redirects them to external search engines (DuckDuckGo, Google, Google Maps, and Yelp were all observed as redirect destinations during the Phase 1 audit). This behavior disrupted audit tools and prevented mobile screenshot capture.

**SEO Impact:** If Googlebot or other legitimate crawlers trigger this redirect, the website will never be properly indexed. All subsequent SEO work — meta descriptions, schema, sitemaps, landing pages — may produce zero results if the crawler is being sent away before it can read the page content.

**Mitigation Required:** Developer must review the script's trigger conditions, confirm that known Googlebot user-agents and IP ranges are excluded, test with a crawler simulator, and document the outcome. This is the single highest-priority technical action before any on-site SEO changes are deployed.

**Status:** Unresolved as of 2026-10-11.

---

### RISK-T02: HTTP / HTTPS Dual Access — SEVERITY: MEDIUM

**Description:** The website loads on both http://asmrcctvtechnologies.com and https://asmrcctvtechnologies.com without a server-side redirect. Both protocols serve the same content.

**SEO Impact:** Google may index both versions as separate sites, splitting any link equity between them. Duplicate content signals reduce the authority of both versions. Any backlinks pointing to the HTTP version do not benefit the HTTPS canonical.

**Mitigation Required:** Implement a server-side 301 redirect from all HTTP URLs to their HTTPS equivalents. Add a canonical tag (`<link rel="canonical">`) to every page pointing to the HTTPS version.

**Status:** Unresolved as of 2026-10-11.

---

### RISK-T03: Unclaimed GBP Listing — SEVERITY: MEDIUM

**Description:** The GBP listing for ASMR CCTV TRADING exists and is publicly visible on Google Maps, but it is unknown whether the business owner has claimed and verified ownership. If unclaimed, no edits are possible by any party.

**SEO Impact:** Every GBP improvement — category change, website link, photo replacement, service listing, posts — requires verified owner access. Without claiming, all GBP quick wins are blocked.

**Mitigation Required:** Owner logs in to business.google.com and confirms ownership status. If verification is needed, the postcard method typically takes 5–14 business days. Begin this step immediately.

**Status:** Unknown as of 2026-10-11 — requires owner to verify.

---

### RISK-T04: PHP-Based Site Without CMS — SEVERITY: LOW

**Description:** The website is built in custom PHP with no content management system (CMS) such as WordPress. All content changes (meta tags, schema, page copy, new landing pages, robots.txt, sitemap) require direct file editing.

**SEO Impact:** Content updates are slower and more error-prone without a CMS. A non-technical team member or the business owner cannot make changes independently.

**Mitigation Required:** All on-site changes must be routed through the developer. Consider adding a lightweight admin panel or migrating to a CMS (e.g., WordPress) as a longer-term option, but do not block Phase 2 on this.

**Status:** Known constraint; low urgency.

---

### RISK-T05: NAP Address Discrepancy — SEVERITY: MEDIUM

**Description:** The GBP listing shows the address as "110 Gen. Luna Ave, Ampid-1, San Mateo, 1850 Rizal." The website shows "#51 General Luna St., Ampid-1, San Mateo, Rizal." These are different street numbers and different abbreviations (Ave vs. St.).

**SEO Impact:** NAP (Name, Address, Phone) consistency across all platforms is a primary local SEO ranking factor. Publishing LocalBusiness schema with either version before the correct address is confirmed will either (a) contradict the GBP or (b) contradict the website — both outcomes actively harm local SEO.

**Mitigation Required:** Owner confirms the single correct address. The confirmed address must then be applied to: GBP, website (all pages), Facebook About, JSON-LD schema, and any directory listings.

**Status:** Unresolved as of 2026-10-11 — requires owner input.

---

## Content Risks

### RISK-C01: Unverified Services — SEVERITY: MEDIUM

**Description:** Several services listed on the website or observed on Facebook have not been confirmed as currently available. Creating landing pages, GBP service listings, or advertising for services that cannot be delivered creates a conversion problem and a reputational risk.

**Services requiring owner confirmation before promoting:**

| Service | Risk Reason |
|---|---|
| Solar Setup & Installation | High-cost specialized trade — may require permits; confirm active offering |
| Fire Detection & Alarm Systems | FSIC (Fire Safety Inspection Certificate) compliance required in PH; confirm licensing |
| GPS Tracking Solutions | Complex hardware + software integration; confirm active client deployments |
| PABX / Intercom Systems | Confirm if actively marketed or a legacy listing |
| Projector & Video Wall | Confirm if offered standalone or bundled only |
| IT Managed Services | Confirm pricing model and current client capacity |
| Apple products — authorized dealer claim | Apple authorized reseller status requires official Apple authorization; listed as partner but not confirmed |

**Services on Facebook NOT listed on website (confirm before creating landing pages):**

| Service | Observed Where |
|---|---|
| PC Assembly and Formatting | Facebook cover photo |
| IT Disaster Recovery | Facebook cover photo |
| Printer Services | Facebook cover photo |
| Fiber Cabling and Splicing (as distinct from structured cabling) | Facebook cover photo |
| [Se...istration] — partially obscured | Facebook cover photo (likely Server Administration) |

**Mitigation:** Owner reviews this list and confirms status of each service before any landing page, GBP service entry, or ad campaign is created for them.

---

### RISK-C02: Client Permissions for Testimonials — SEVERITY: MEDIUM

**Description:** The website and Facebook page both reference named client engagements. Using client names, logos, or project details in testimonials, case studies, or GBP responses requires explicit client permission.

**Named clients identified:** Dunkin', Chef's Secret, OLOPSC (Our Lady of Perpetual Succor College), Honcho Logistics Corporation, Magiclean, O'Kafe, Tri Vision Ventures, Yrezabal & Co.

**Mitigation:** Owner must personally confirm permission with each client before any testimonial, case study, or brand logo is published on the website, GBP, or Facebook. Do not publish named client content without confirmed permission.

---

### RISK-C03: Pricing Absence — SEVERITY: LOW

**Description:** No pricing information was visible on the website, GBP, or Facebook page for any service. The one visible pricing inquiry from a potential customer was handled via Viber redirect.

**Mitigation:** No pricing should be published online without explicit owner confirmation of current pricing or range. A "Request a Free Quote" CTA is safer than published prices until the owner decides on a pricing transparency policy.

---

## SEO Risks

### RISK-S01: GBP Category Change — SEVERITY: LOW

**Description:** Changing the GBP primary category from "Computer store" to "Security system installer" may cause a temporary disruption in any existing Maps ranking (even if ASMR is currently not visible for CCTV searches, it may appear in "Computer store" queries).

**Mitigation:** This change is strongly recommended and the benefit (appearing in CCTV/security searches) vastly outweighs the risk (losing visibility in Computer store queries). Proceed as planned. Monitor GBP Insights for 2–4 weeks after the change.

---

### RISK-S02: Thin Content on New Landing Pages — SEVERITY: MEDIUM

**Description:** Creating location-based service landing pages with minimal text (fewer than 400 words, no structured content, no FAQs) risks a Google "thin content" assessment that can suppress ranking or trigger a manual review.

**Mitigation:** Each landing page must contain a minimum of 400–600 words of genuinely useful content: what the service is, how it is delivered, what technology is used, why it matters for local businesses, and a clear call to action. Do not create placeholder pages.

---

### RISK-S03: Review Velocity — SEVERITY: LOW

**Description:** Getting the first 10 Google reviews from a standing start of zero may take 60–90 days. Owner should not expect overnight results from review outreach.

**Mitigation:** Start outreach in Week 2 (after GBP is confirmed claimed). Personally contact each named client. A direct Google review link (from business.google.com) removes friction. Set a realistic expectation: 10 reviews by end of Month 3.

---

### RISK-S04: URL Structure Change for News Posts — SEVERITY: LOW

**Description:** News post URLs currently use query strings (/news-post.php?slug=...). If these are changed to clean paths (/blog/slug/), any existing indexed pages will return 404 unless proper 301 redirects are put in place.

**Mitigation:** Do not change URL structure without implementing matching 301 redirects. Defer this change until after the site is fully indexed in Google Search Console so redirect mapping can be confirmed.

---

## Dependencies Between Tasks

The following task dependency chain must be respected. Starting a dependent task before its prerequisite is resolved risks wasted effort or live errors.

| Dependent Task | Must Wait For | Reason |
|---|---|---|
| T-011 through T-019 (all on-site SEO) | T-010 (bot-detection investigation resolved) | On-site changes may not be crawled if bot-detection is redirecting Googlebot |
| T-002 through T-005, T-021–T-029 (GBP tasks) | T-001 (GBP claim confirmed) | Cannot edit any GBP element without verified owner access |
| T-013 (sitemap submission) | T-017 (GSC verified) | Cannot submit sitemap without Search Console domain verification |
| T-018 (LocalBusiness schema) | Address discrepancy resolved (owner confirms correct address) | Publishing schema with incorrect address worsens NAP inconsistency |
| T-020 through T-024 (landing pages) | T-010 + T-018 (bot-detection cleared + schema ready) | Landing pages need schema and should not go live before bot-detection is resolved |
| T-035 (analytics review) | T-017 + 60 days of live data | No meaningful data available before at least 60 days of GA4 tracking |

---

## Items Requiring Owner Decision Before Proceeding

The following seven decisions cannot be made by the developer, content agent, or team lead. They require direct owner input. Phase 2 work in the affected areas is paused until these are resolved.

| # | Decision | Why It Blocks Phase 2 | Urgency |
|---|---|---|---|
| D-001 | Confirm GBP listing is claimed, or initiate claim at business.google.com | Blocks all GBP improvements (T-001 through T-005) | IMMEDIATE |
| D-002 | Approve switch of GBP primary category to "Security system installer" | Highest-impact single SEO change available; cannot proceed without owner login and consent | IMMEDIATE |
| D-003 | Confirm correct street address: "110 Gen. Luna Ave" (GBP) vs. "#51 General Luna St." (website) | Blocks LocalBusiness schema (T-018) and all NAP-dependent content | WEEK 1 |
| D-004 | Confirm which unverified services are currently offered — specifically: Solar, Fire Alarm (FSIC?), GPS Tracking, PABX, Projector/Video Wall, IT Managed Services, Apple dealer status | Blocks landing page creation and GBP service listing for those services | MONTH 1 |
| D-005 | Confirm permission from Dunkin', Chef's Secret, OLOPSC, Honcho Logistics (and others) to name them as clients on website, GBP, and Facebook | Blocks testimonial section (T-027) and case study content | MONTH 1–2 |
| D-006 | Decide on content language policy: English only, Tagalog only, or bilingual (Tagalog/English/Taglish) | Blocks Facebook content plan (T-028) and blog article strategy (T-034) | WEEK 1–2 |
| D-007 | Confirm business hours for website and GBP (current GBP shows Mon–Sun 9 AM–5 PM; website does not display hours) | Blocks T-025 (business hours on website) | MONTH 1 |
| D-008 | Authorize developer to investigate, modify, or disable the bot-detection JavaScript | Blocks T-010 and all dependent on-site SEO work | MONTH 1 — CRITICAL |

---

## Risk Summary Matrix

| Risk ID | Risk | Severity | Status | Owner |
|---|---|---|---|---|
| RISK-T01 | Bot-detection script may block Googlebot | HIGH | Unresolved | Developer + Odyssey |
| RISK-T02 | HTTP/HTTPS dual access — duplicate content | MEDIUM | Unresolved | Developer |
| RISK-T03 | GBP listing may be unclaimed | MEDIUM | Unknown | Odyssey |
| RISK-T04 | PHP site without CMS — slow content updates | LOW | Known constraint | Developer |
| RISK-T05 | NAP address discrepancy (GBP vs. website) | MEDIUM | Unresolved | Odyssey |
| RISK-C01 | Unverified services being promoted | MEDIUM | Unresolved | Odyssey |
| RISK-C02 | Client permission for testimonials | MEDIUM | Unresolved | Odyssey |
| RISK-C03 | Pricing absent — no publishing without owner approval | LOW | Managed | Odyssey |
| RISK-S01 | GBP category change temporary ranking disruption | LOW | Acceptable | Monitor only |
| RISK-S02 | Thin content on new landing pages | MEDIUM | Preventable | Content/Developer |
| RISK-S03 | Review velocity — 10 reviews may take 60–90 days | LOW | Expectation set | Odyssey |
| RISK-S04 | URL structure change may lose indexed pages | LOW | Preventable | Developer |

---

*End of Risks and Dependencies Register*
*Date: 2026-10-11*
*Prepared by: Execution Manager (AGT-EM)*
*Next review: After owner resolves D-001 through D-008 and developer completes T-010 (bot-detection investigation)*
