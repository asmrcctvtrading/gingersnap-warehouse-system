<?php
/**
 * Local SEO Schema Injection - ASMR CCTV Trading
 * Include this file in your website header (<head> section)
 * Usage: <?php include 'schema.php'; ?>
 * Last Updated: 2026-10-28
 */

// Load configuration and functions
require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

// Determine current page context
$current_page = basename($_SERVER['PHP_SELF']);
$service_slug = isset($_GET['service']) ? sanitize_input($_GET['service']) : null;
$service_id = null;

// Map service slug to ID
if ($service_slug) {
	$service = get_service_by_slug($service_slug);
	if ($service) {
		$service_id = $service['id'];
	}
}

// Inject all relevant schemas
inject_all_schemas($service_id);

// Add Google verification and additional meta tags
?>
<!-- Local SEO Verification -->
<meta name="google-site-verification" content="YOUR_GOOGLE_VERIFICATION_CODE_HERE">
<meta name="facebook-domain-verification" content="YOUR_FACEBOOK_VERIFICATION_CODE_HERE">

<!-- Local SEO Meta Tags -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="UTF-8">
<meta name="revisit-after" content="7 days">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta http-equiv="x-ua-compatible" content="ie=edge">

<!-- Geo Tags -->
<meta name="geo.placename" content="<?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?>, <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?>">
<meta name="geo.region" content="PH-00">
<meta name="ICBM" content="<?php echo constant('BUSINESS_LATITUDE'); ?>, <?php echo constant('BUSINESS_LONGITUDE'); ?>">

<!-- Business Schema Verification -->
<!-- Organization: <?php echo escape_output(constant('BUSINESS_NAME')); ?> -->
<!-- Location: <?php echo escape_output(constant('BUSINESS_ADDRESS_FULL')); ?> -->
<!-- Phone: <?php echo format_phone(); ?> -->
<!-- Service Areas: <?php echo escape_output(implode(', ', constant('PRIMARY_SERVICE_AREAS'))); ?> -->

<?php
// Optional: Debug mode (comment out in production)
// error_log('Schema injection completed for page: ' . $current_page);
// if ($service_id) {
//     error_log('Service schema injected for: ' . $service_id);
// }
?>
