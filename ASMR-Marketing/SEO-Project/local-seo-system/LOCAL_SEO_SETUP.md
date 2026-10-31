# Local SEO Setup Guide - ASMR CCTV Trading

**Document Version:** 1.0  
**Last Updated:** 2026-10-28  
**For:** San Mateo, Rizal, Philippines  

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [File Structure](#file-structure)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Deployment to cPanel](#deployment-to-cPanel)
6. [Testing & Validation](#testing--validation)
7. [Integration with Existing Website](#integration-with-existing-website)
8. [Maintenance Schedule](#maintenance-schedule)
9. [Troubleshooting](#troubleshooting)

---

## Quick Start

### What You're Getting
- **4 Service Pages** with local keywords targeting San Mateo, Rizal, Ampid-1
- **Schema Markup** (Organization, LocalBusiness, Service, FAQ, Breadcrumb)
- **Review Request System** to generate customer reviews on Google
- **SEO Dashboard** to monitor local SEO performance
- **Database Schema** for review tracking
- **Configuration Files** for cPanel deployment

### Setup Time
- **First-time setup:** 2-3 hours
- **Integration with existing site:** 1-2 hours
- **Testing & validation:** 30 minutes

---

## File Structure

```
local-seo-system/
├── local-seo-config.php          # NAP, services, business info
├── local-seo-functions.php       # Helper functions for schema
├── schema.php                     # Include in header for schema injection
├── review-request.php            # Review request email system
├── cctv-san-mateo.php           # Service page 1
├── network-solutions.php         # Service page 2
├── computer-sales-support.php    # Service page 3
├── it-maintenance.php            # Service page 4
├── local-seo-dashboard.php      # Admin dashboard
├── review-system-db.sql         # Database schema
├── .htaccess                     # Apache optimization
├── LOCAL_SEO_SETUP.md           # This file
├── KEYWORD_STRATEGY.md          # Keyword research doc
├── CITATION_SUBMISSION.csv      # Directory list
├── REVIEW_WORKFLOW.md           # Review process guide
└── SCHEMA_VALIDATION.md         # Schema testing guide
```

---

## Installation Steps

### Step 1: Prepare Your Server

**Requirements:**
- PHP 7.4+ or PHP 8.x
- MySQL 5.7+ or MySQL 8.x
- Apache with mod_rewrite enabled
- PDO extension for PHP

**Check PHP Version:**
```bash
php -v
```

**Enable mod_rewrite (if not enabled):**
```bash
a2enmod rewrite
systemctl restart apache2
```

### Step 2: Upload Files to cPanel

1. **Access cPanel → File Manager**
2. **Navigate to your website root** (usually `public_html/`)
3. **Create folder:** `local-seo-system/`
4. **Upload all files** from this package to that folder
5. **Upload .htaccess** to website root (if not already present)

**File Permissions:**
- PHP files: `644` or `644` (no execute needed for web access)
- Directories: `755`
- Config files: `600` (if sensitive)

### Step 3: Update Configuration

**Edit `local-seo-config.php`:**

```php
// Change these to match your business
define('BUSINESS_NAME', 'ASMR CCTV Trading'); // Your business name
define('BUSINESS_PHONE', '+63 976 382 3899'); // Your phone
define('BUSINESS_EMAIL', 'inquiries@asmrcctvtechnologies.com'); // Your email
define('BUSINESS_WEBSITE', 'https://asmrcctvtechnologies.com'); // Your domain

// Address (exactly as it appears in Google Business Profile)
define('BUSINESS_STREET', '110 Gen. Luna Ave');
define('BUSINESS_BARANGAY', 'Ampid-1');
define('BUSINESS_MUNICIPALITY', 'San Mateo');
define('BUSINESS_PROVINCE', 'Rizal');
define('BUSINESS_POSTAL_CODE', '1850');

// Map coordinates (get from Google Maps)
define('BUSINESS_LATITUDE', '14.6091');
define('BUSINESS_LONGITUDE', '121.1818');
```

**Critical:** All information must match your Google Business Profile exactly for NAP consistency.

### Step 4: Create Database Tables

1. **Access cPanel → phpMyAdmin**
2. **Select your website database**
3. **Click "SQL" tab**
4. **Copy contents of `review-system-db.sql`**
5. **Paste and execute**

**What gets created:**
- `local_seo_review_requests` - Track review requests sent
- `local_seo_reviews` - Store received reviews
- `local_seo_citations` - Track directory submissions
- `local_seo_tasks` - SEO task management
- `local_seo_keywords` - Keyword ranking tracking

### Step 5: Update Your Website Header

**In your website's `header.php` (or main template):**

Add this line before closing `</head>` tag:

```php
<?php include_once '/local-seo-system/schema.php'; ?>
```

This injects schema markup into all pages.

**Example:**
```html
<!DOCTYPE html>
<html>
<head>
	<title>Your Page Title</title>
	<!-- Other meta tags -->
	<?php include_once '/local-seo-system/schema.php'; ?>
</head>
<body>
	<!-- Page content -->
</body>
</html>
```

### Step 6: Integrate Review Request System

**In your contact form handler (`contact-handler.php`):**

After successful form submission, send review request:

```php
<?php
// After successful contact form submission
if ($form_submitted_successfully) {
	require_once '/local-seo-system/review-request.php';
	$review_system = new ReviewRequestSystem($pdo); // Pass your PDO connection
	
	$result = $review_system->send_review_request_email(
		$_POST['email'],
		$_POST['name'],
		$_POST['service_type'] ?? 'General Inquiry'
	);
	
	if ($result['success']) {
		// Optional: log success
		error_log('Review request sent to: ' . $_POST['email']);
	}
}
?>
```

### Step 7: Update Sitemap

**Add to your `sitemap.xml`:**

```xml
<!-- Local SEO Service Pages -->
<url>
	<loc>https://asmrcctvtechnologies.com/services/cctv-san-mateo.php</loc>
	<lastmod>2026-10-28</lastmod>
	<priority>0.8</priority>
</url>
<url>
	<loc>https://asmrcctvtechnologies.com/services/network-solutions.php</loc>
	<lastmod>2026-10-28</lastmod>
	<priority>0.8</priority>
</url>
<url>
	<loc>https://asmrcctvtechnologies.com/services/computer-sales-support.php</loc>
	<lastmod>2026-10-28</lastmod>
	<priority>0.8</priority>
</url>
<url>
	<loc>https://asmrcctvtechnologies.com/services/it-maintenance.php</loc>
	<lastmod>2026-10-28</lastmod>
	<priority>0.8</priority>
</url>
```

---

## Configuration

### Google Verification Codes

In `schema.php`, add your verification codes:

```php
<meta name="google-site-verification" content="YOUR_CODE_HERE">
```

**Get your code:**
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add your property
3. Choose verification method
4. Copy the meta tag content value

### Review Request Timing

In `local-seo-config.php`:

```php
define('REVIEW_REQUEST_DELAY_DAYS', 30); // When to send request (after 30 days)
define('REVIEW_REQUEST_MAX_ATTEMPTS', 3); // How many times to retry
```

### Service Configuration

Edit services in `local-seo-config.php`:

```php
define('SERVICES', [
	[
		'id' => 'cctv',
		'name' => 'CCTV Systems & Surveillance',
		'slug' => 'cctv-san-mateo',
		'description' => 'Professional CCTV...',
		'keywords' => ['CCTV San Mateo', '...'],
	],
	// Add more services as needed
]);
```

---

## Deployment to cPanel

### Pre-Deployment Checklist

- [ ] All files uploaded to `local-seo-system/` folder
- [ ] `local-seo-config.php` updated with your business info
- [ ] Database tables created via phpMyAdmin
- [ ] `schema.php` included in website header
- [ ] `.htaccess` uploaded to website root
- [ ] File permissions set correctly (644 for files, 755 for dirs)

### Production Checklist

- [ ] Test all service pages load without errors
- [ ] Validate schema markup with Google Rich Results Test
- [ ] Test form submission and review request email
- [ ] Check dashboard loads at `/local-seo-system/local-seo-dashboard.php?key=YOUR_KEY`
- [ ] Verify NAP consistency across all pages
- [ ] Test on mobile devices

### Dashboard Access

**URL:** `https://your-domain.com/local-seo-system/local-seo-dashboard.php?key=YOUR_KEY`

**Change the security key** in production:

1. Edit `local-seo-dashboard.php`
2. Find: `if ($_GET['key'] === 'YOUR_SECURE_KEY_HERE')`
3. Change to unique key: `if ($_GET['key'] === 'your-super-secret-key-12345')`

---

## Testing & Validation

### Schema Markup Validation

1. **Go to:** [Google Rich Results Test](https://search.google.com/test/rich-results)
2. **Test each page:**
   - Homepage (should show Organization + LocalBusiness)
   - Service pages (should show Service schema)
   - Check for any errors

### NAP Consistency Check

Verify Name, Address, Phone are identical across:
- [ ] Website header/footer
- [ ] All service pages
- [ ] Google Business Profile
- [ ] Google Maps
- [ ] Schema markup

### Mobile-Friendly Test

1. **Go to:** [Google Mobile-Friendly Test](https://search.google.com/test/mobile-friendly)
2. **Test each page**
3. **Fix any issues before going live**

### Local Keyword Check

Test that keywords appear in:
- [ ] Page title
- [ ] Meta description
- [ ] H1 and H2 headings
- [ ] First paragraph
- [ ] Service area mentions

---

## Integration with Existing Website

### If You Have a Multi-Page Site

**1. Link to service pages from homepage:**

```html
<a href="/services/cctv-san-mateo.php">CCTV Systems</a>
<a href="/services/network-solutions.php">Network Solutions</a>
<a href="/services/computer-sales-support.php">Computer Sales</a>
<a href="/services/it-maintenance.php">IT Maintenance</a>
```

**2. Add "Services" navigation menu item**

**3. Create `/services/index.php` with links to all 4 pages**

### If You Have a WordPress Site

**Option 1: Custom PHP Pages**
- Upload service pages to `/wp-content/local-seo-system/`
- Access via `/local-seo-system/cctv-san-mateo.php`

**Option 2: Convert to WordPress Pages**
- Create WordPress pages with same content
- Include `schema.php` in `functions.php`:
```php
require_once '/local-seo-system/schema.php';
```

### If Using Existing Database

Modify `review-request.php`:

```php
// Change to your actual database connection
$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');
$review_system = new ReviewRequestSystem($pdo);
```

---

## Maintenance Schedule

### Weekly
- [ ] Review new customer feedback
- [ ] Check Google Business Profile for questions
- [ ] Post update to GBP (photo, text post, or special offer)
- [ ] Reply to reviews (both positive and critical)

### Monthly
- [ ] Send review requests to recent customers
- [ ] Check dashboard for pending tasks
- [ ] Verify NAP consistency across directories
- [ ] Review local keyword rankings
- [ ] Update testimonials with recent reviews

### Quarterly
- [ ] Validate schema markup again
- [ ] Audit citations for accuracy
- [ ] Research new local keywords
- [ ] Plan content updates
- [ ] Generate SEO performance report

### Annually
- [ ] Comprehensive local SEO audit
- [ ] Resubmit to major directories
- [ ] Update business photos/videos
- [ ] Analyze competitor strategies
- [ ] Plan next year's local SEO goals

---

## Troubleshooting

### Schema Not Appearing

**Issue:** Schema markup doesn't show in Google Rich Results Test

**Solution:**
1. Check if `schema.php` is included in your header
2. Validate PHP syntax: `php -l local-seo-config.php`
3. Check browser console for JavaScript errors
4. Ensure JSON is properly formatted

### Review Emails Not Sending

**Issue:** Customers not receiving review request emails

**Solution:**
1. Check server logs: `tail -f /var/log/mail.log`
2. Verify PHP mail() is enabled: `php -i | grep mail`
3. Check SPF/DKIM records in your domain DNS
4. Test with different email provider (Gmail, Outlook)
5. Check spam folder

### Performance Issues

**Issue:** Service pages loading slowly

**Solution:**
1. Enable gzip compression (.htaccess installed)
2. Optimize images (max 100KB each)
3. Use CDN for static assets
4. Check database query performance
5. Enable caching headers

### Database Connection Error

**Issue:** "Error connecting to database"

**Solution:**
1. Verify PDO extension is installed: `php -m | grep pdo`
2. Check MySQL is running
3. Verify credentials in connection string
4. Ensure database user has proper permissions

### 404 Errors on Service Pages

**Issue:** Service pages return 404

**Solution:**
1. Verify files uploaded to correct directory
2. Check file permissions (644)
3. Ensure mod_rewrite is enabled
4. Check `.htaccess` is in website root
5. Clear browser cache

---

## Support & Next Steps

### What to Do Now

1. **Complete installation** following steps above
2. **Test everything** using validation guides
3. **Submit to directories** (see CITATION_SUBMISSION.csv)
4. **Start requesting reviews** (see REVIEW_WORKFLOW.md)
5. **Monitor dashboard** monthly

### Getting Help

- **PHP/MySQL Questions:** Check local-seo-functions.php comments
- **cPanel Help:** Contact your hosting provider
- **Google Tools:** Visit [Google Search Central](https://developers.google.com/search)
- **Local SEO Tips:** See KEYWORD_STRATEGY.md and SCHEMA_VALIDATION.md

---

**Setup Complete!** Your Local SEO system is now ready to boost your San Mateo, Rizal business online.

For detailed keyword strategy, see **KEYWORD_STRATEGY.md**  
For review management workflow, see **REVIEW_WORKFLOW.md**  
For citations to submit, see **CITATION_SUBMISSION.csv**
