# Schema Validation Guide - ASMR CCTV Trading

**Document Version:** 1.0  
**Last Updated:** 2026-10-28  
**Purpose:** Test and validate JSON-LD schema markup for Google compliance  

---

## What is Schema Markup?

Schema markup is structured data (JSON-LD format) that tells search engines what your content is about. It improves:
- **Rich Results Display** - Extra information in search results
- **Local SEO** - Business information in Google Maps
- **Click-Through Rate** - More attractive search results
- **Trust & Authority** - Shows professional structure

---

## Schema Types We're Using

| Schema Type | Purpose | Pages |
|-------------|---------|-------|
| Organization | Company info, logo, social profiles | All pages |
| LocalBusiness | Address, phone, hours, service area | All pages |
| Service | Specific services offered | Service pages |
| FAQ | Common questions and answers | Service pages |
| Breadcrumb | Navigation hierarchy | All pages |

---

## Step 1: Test Homepage Schema

### What to Check

**URL to Test:**
```
https://asmrcctvtechnologies.com/
```

### Using Google Rich Results Test

1. **Open:** [Google Rich Results Test](https://search.google.com/test/rich-results)
2. **Enter your homepage URL**
3. **Click "Test URL"**
4. **Review results:**
   - ✓ Green = Valid
   - ⚠️ Orange = Warning (usually minor)
   - ✗ Red = Error (must fix)

### Expected Results

**Valid Schemas Should Include:**
- Organization Schema
  - Business name: "ASMR CCTV Trading"
  - Logo: [image URL]
  - URL: https://asmrcctvtechnologies.com
  - Phone: +63 976 382 3899
  - Social profiles: Facebook, Google, etc.

- LocalBusiness Schema
  - Address: 110 Gen. Luna Ave, Ampid-1, San Mateo, Rizal 1850
  - Phone: +63 976 382 3899
  - Hours: Mon-Sat 9am-6pm, Sun Closed
  - Geo coordinates: 14.6091, 121.1818

- Breadcrumb Schema
  - Home link
  - Current page position

### Common Schema Errors to Fix

| Error | Solution |
|-------|----------|
| Missing required property | Add the property to config |
| Invalid URL format | Ensure full URL with https:// |
| Incorrect phone format | Use international: +63 976 382 3899 |
| Invalid date format | Use ISO 8601: YYYY-MM-DD |
| Duplicate schema | Check if included twice |

---

## Step 2: Test Service Pages

### CCTV Service Page

**URL:**
```
https://asmrcctvtechnologies.com/services/cctv-san-mateo.php
```

**Using Google Rich Results Test:**
1. Paste URL
2. Click Test
3. Verify results include:
   - Organization Schema ✓
   - LocalBusiness Schema ✓
   - Service Schema ✓
   - FAQ Schema ✓
   - Breadcrumb Schema ✓

### Expected Service Schema

```json
{
  "@type": "Service",
  "name": "CCTV Systems & Surveillance",
  "description": "Professional CCTV installation & 24/7 monitoring...",
  "provider": {
    "@type": "LocalBusiness",
    "name": "ASMR CCTV Trading"
  },
  "areaServed": {
    "@type": "City",
    "name": "San Mateo"
  }
}
```

### Test All 4 Service Pages

```
1. https://asmrcctvtechnologies.com/services/cctv-san-mateo.php
2. https://asmrcctvtechnologies.com/services/network-solutions.php
3. https://asmrcctvtechnologies.com/services/computer-sales-support.php
4. https://asmrcctvtechnologies.com/services/it-maintenance.php
```

Each should pass validation with all 5 schema types.

---

## Step 3: Test FAQ Schema

### How to Verify FAQ Schema

1. **Open Google Rich Results Test**
2. **Paste service page URL**
3. **Scroll to FAQ Schema section**
4. **Verify:**
   - Questions listed
   - Answers provided
   - No errors

### Expected FAQ Items

Each service page should have 3-5 FAQs:

```json
{
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Question text here?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Answer text here."
      }
    }
  ]
}
```

---

## Step 4: Test Local Search Visibility

### Using Google Maps

1. **Go to:** [Google Maps](https://maps.google.com/)
2. **Search:** "CCTV San Mateo" or "Computer Repair Rizal"
3. **Look for your business in results**
4. **Verify:**
   - Correct name, address, phone
   - Hours displayed correctly
   - Photos showing
   - Reviews visible

### Using Google Search

1. **Go to:** [Google Search](https://google.com/)
2. **Search:** "ASMR CCTV Trading San Mateo"
3. **Look for rich result card showing:**
   - Business name and logo
   - Address
   - Phone number
   - Hours
   - Star rating (if reviews exist)

### Expected Search Result

```
ASMR CCTV Trading
⭐⭐⭐⭐⭐ (15 reviews)
110 Gen. Luna Ave, Ampid-1, San Mateo, Rizal 1850
📞 +63 976 382 3899
🕐 Closes soon at 6 PM
ℹ️ Security and Surveillance
```

---

## Step 5: Validate with Structured Data Testing Tool

### Alternative Validation Methods

**1. JSON-LD Validator**
- URL: [JSON-LD Validator](https://jsonformatter.org/json-ld-validator)
- Paste: Raw JSON from your page source
- Validates: JSON structure and format

**2. Schema.org Validator**
- URL: [Schema.org Markup Validator](https://validator.schema.org/)
- Enter: Full page HTML or URL
- Reviews: All schema types on page

**3. Bing Webmaster Tools**
- Go to: [Bing Webmaster Tools](https://www.bing.com/webmasters/)
- Add property
- Check: Markup validation results
- Reports: Indexed pages with schema

---

## Step 6: Test on Mobile

### Mobile-Friendly Test

1. **Go to:** [Google Mobile-Friendly Test](https://search.google.com/test/mobile-friendly)
2. **Test each URL:**
   - Homepage
   - All 4 service pages
   - Dashboard (if public)

3. **Verify:**
   - ✓ Mobile friendly
   - ✓ Text readable without zoom
   - ✓ Clickable elements properly spaced
   - ✓ No mobile usability issues

### Mobile Schema Test

1. **Use Chrome DevTools**
2. Open each page on mobile
3. Check if schema still renders
4. Verify rich result display

---

## Step 7: Monitor Indexing

### In Google Search Console

1. **Go to:** [Google Search Console](https://search.google.com/search-console)
2. **Add property** (if not already added)
3. **Navigation:** Coverage report
4. **Check:**
   - All pages indexed: ✓ Valid
   - No 404 errors
   - No blocked resources
   - No robots.txt exclusions

### URL Inspection

1. **Go to:** Search Console
2. **Paste each URL:**
   - Homepage
   - Service pages
   - Any custom pages

3. **Click "Inspect"**
4. **Verify:** "URL is on Google" status

---

## Step 8: Test Breadcrumb Schema

### What Breadcrumbs Look Like

**On Homepage:**
```
ASMR CCTV Trading
```

**On Service Page:**
```
Home > Services > CCTV Systems San Mateo
```

**Using Google Rich Results Test:**
1. Paste service page URL
2. Scroll to "Breadcrumb" section
3. Verify breadcrumbs listed correctly

### Expected Breadcrumb JSON

```json
{
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://asmrcctvtechnologies.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Services",
      "item": "https://asmrcctvtechnologies.com/services"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "CCTV Systems San Mateo",
      "item": "https://asmrcctvtechnologies.com/services/cctv-san-mateo.php"
    }
  ]
}
```

---

## Step 9: Check Meta Tags

### HTML Head Section

**Check these are present:**

```html
<!-- Essential Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="...">
<meta name="keywords" content="...">

<!-- Geo Tags -->
<meta name="geo.placename" content="San Mateo, Rizal">
<meta name="ICBM" content="14.6091, 121.1818">

<!-- Open Graph -->
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="...">

<!-- Canonical -->
<link rel="canonical" href="...">

<!-- Schema -->
<script type="application/ld+json">
[JSON-LD schema here]
</script>
```

### How to Check

1. **Open page in browser**
2. **Right-click → View Page Source**
3. **Search (Ctrl+F):** `<meta`
4. **Verify all tags present**
5. **Check canonical URL matches actual URL**

---

## Step 10: Performance Validation

### Core Web Vitals Check

1. **Go to:** [PageSpeed Insights](https://pagespeed.web.dev/)
2. **Test each page**
3. **Verify:**
   - Green scores (90+)
   - Fast loading
   - Mobile friendly

### What to Optimize

If scores are low:
1. **Compress images** - Max 100KB per image
2. **Minify CSS/JS** - Remove unnecessary code
3. **Enable caching** - .htaccess rules included
4. **Use CDN** - Distribute static assets globally
5. **Reduce redirects** - Direct URLs only

---

## Validation Checklist

### Pre-Launch

- [ ] Homepage schema valid (no errors)
- [ ] All 4 service pages schema valid
- [ ] FAQ schema present on service pages
- [ ] Breadcrumb schema correct
- [ ] Organization schema matches business info
- [ ] LocalBusiness schema has correct address
- [ ] All pages mobile-friendly
- [ ] Canonical tags present
- [ ] No 404 errors in console
- [ ] Page loads in <3 seconds

### Post-Launch

- [ ] Google Search Console shows indexed pages
- [ ] Google Maps shows business correctly
- [ ] Rich results display in search
- [ ] Mobile-friendly test passes
- [ ] No crawl errors in Search Console
- [ ] Core Web Vitals score 90+
- [ ] All links working
- [ ] Forms submitting correctly
- [ ] Analytics tracking firing
- [ ] Sitemap submitted to Google

### Monthly Validation

- [ ] Revalidate top 3 pages monthly
- [ ] Check for schema errors in Search Console
- [ ] Verify new pages getting indexed
- [ ] Monitor Core Web Vitals
- [ ] Check for 404 errors
- [ ] Test review request emails
- [ ] Validate citation consistency
- [ ] Review search console errors

---

## Common Validation Issues & Fixes

### Issue: "Property 'name' recommended"

**Problem:** Schema has recommended property but it's missing

**Fix:** Add to config if not present
```php
// In local-seo-functions.php or schema
'name' => constant('BUSINESS_NAME'),
```

### Issue: "Invalid URL format"

**Problem:** URLs missing https:// or have typos

**Fix:** Verify in config
```php
define('BUSINESS_WEBSITE', 'https://asmrcctvtechnologies.com');
```

### Issue: "Duplicate schema on page"

**Problem:** Schema included twice (once in header, once in page)

**Fix:** Include schema.php only once in header:
```php
<?php include_once '/local-seo-system/schema.php'; ?>
```

### Issue: "Missing required property 'address'"

**Problem:** LocalBusiness schema missing address object

**Fix:** Ensure config has complete address:
```php
define('BUSINESS_STREET', '110 Gen. Luna Ave');
define('BUSINESS_MUNICIPALITY', 'San Mateo');
define('BUSINESS_PROVINCE', 'Rizal');
```

### Issue: "Phone number format invalid"

**Problem:** Phone not in international format

**Fix:** Use +63 format:
```php
define('BUSINESS_PHONE', '+63 976 382 3899'); // ✓ Correct
// NOT: 0976 382 3899 or (976) 382-3899
```

---

## Re-Validation Schedule

### Initial Setup
- **Day 1:** Test all pages
- **Week 1:** Test again for consistency
- **Week 2:** Monitor Search Console errors

### Ongoing Maintenance
- **Monthly:** Full schema re-validation
- **After updates:** Validate immediately
- **Quarterly:** Complete site audit
- **Annually:** Full accessibility check

---

## Validation Tools Bookmarks

Save these URLs for quick access:

1. [Google Rich Results Test](https://search.google.com/test/rich-results)
2. [Google Mobile-Friendly Test](https://search.google.com/test/mobile-friendly)
3. [Google Search Console](https://search.google.com/search-console)
4. [PageSpeed Insights](https://pagespeed.web.dev/)
5. [Schema.org Validator](https://validator.schema.org/)
6. [JSON-LD Validator](https://jsonformatter.org/json-ld-validator)
7. [Bing Webmaster Tools](https://www.bing.com/webmasters/)

---

## Expected Results

### Successful Schema Implementation

**In Google Search:**
- Business name appears with logo
- Address shown (street, city, province)
- Phone number clickable
- 1-5 star rating (if reviews exist)
- Hours displayed (if configured)

**In Google Maps:**
- Full business details displayed
- Photos showing
- Reviews visible
- Service area map showing

**In Search Console:**
- Pages indexed: ✓ All pages
- Mobile usability: ✓ All pass
- Core Web Vitals: ✓ Good
- Coverage: ✓ No errors
- Enhancements: ✓ Schema detected

---

## When to Revalidate

✓ **After:** Changes to business info, address, phone  
✓ **After:** Adding new service pages  
✓ **After:** Updating FAQ schema  
✓ **After:** Modifying Open Graph tags  
✓ **After:** Restructuring website hierarchy  

❌ **NOT needed:** Minor text edits, image changes, styling updates  
❌ **NOT needed:** Adding social posts or customer reviews  

---

## Support & Escalation

### For Schema Errors:
1. Check [Schema.org documentation](https://schema.org/)
2. Review local-seo-functions.php comments
3. Test with [JSON Formatter](https://jsonformatter.org/)
4. Validate HTML structure

### For Search Console Issues:
1. Review [Google Search Central](https://developers.google.com/search)
2. Check coverage report
3. Request indexation if stuck
4. Wait 2 weeks for recrawl

### For Performance Issues:
1. Use [PageSpeed Insights](https://pagespeed.web.dev/)
2. Compress images (max 100KB)
3. Enable browser caching
4. Remove unused CSS/JavaScript

---

**Validation Complete!** Your schema is now Google-compliant and visible in search results.

---

**Related Docs:**
- LOCAL_SEO_SETUP.md - Installation guide
- KEYWORD_STRATEGY.md - Keyword targeting
- REVIEW_WORKFLOW.md - Review management
- CITATION_SUBMISSION.csv - Directory submission
