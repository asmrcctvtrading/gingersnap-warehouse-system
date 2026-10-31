<?php
/**
 * CCTV Systems in San Mateo, Rizal - Service Page
 * Location: /services/cctv-san-mateo.php
 * Local SEO focused page targeting San Mateo CCTV market
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

$page_title = 'CCTV Systems & Surveillance in San Mateo, Rizal | ASMR CCTV Trading';
$page_description = 'Professional CCTV installation & 24/7 monitoring for San Mateo, Rizal. Same-day service available. Local expertise since 2010.';
$page_keywords = [
	'CCTV San Mateo',
	'Surveillance Systems Rizal',
	'Security Cameras Ampid-1',
	'CCTV Installation Services',
	'24/7 CCTV Monitoring',
	'Business Security San Mateo',
];

$service = get_service_by_id('cctv');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo escape_output($page_title); ?></title>
	<meta name="description" content="<?php echo escape_output($page_description); ?>">
	<meta name="keywords" content="<?php echo escape_output(implode(', ', $page_keywords)); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- Open Graph -->
	<meta property="og:title" content="<?php echo escape_output($page_title); ?>">
	<meta property="og:description" content="<?php echo escape_output($page_description); ?>">
	<meta property="og:type" content="website">
	<meta property="og:url" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/cctv-san-mateo.php">
	<meta property="og:image" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/images/services/cctv-san-mateo.jpg">

	<!-- Canonical -->
	<link rel="canonical" href="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/cctv-san-mateo.php">

	<!-- Schema Markup -->
	<?php inject_all_schemas('cctv', [
		[
			'question' => 'How much does CCTV installation in San Mateo cost?',
			'answer' => 'Costs vary based on system complexity. Contact us for a free consultation.',
		],
		[
			'question' => 'Do you offer same-day CCTV installation in San Mateo?',
			'answer' => 'Yes, same-day installation available for orders placed before 12 PM.',
		],
		[
			'question' => 'Is monitoring available 24/7?',
			'answer' => 'Yes, we provide round-the-clock CCTV monitoring with instant alerts.',
		],
	]); ?>

	<style>
		body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
		header { background: #003366; color: white; padding: 20px 0; }
		header h1 { margin: 0; font-size: 28px; padding: 20px; max-width: 1200px; margin: 0 auto; }
		.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
		.breadcrumb { margin: 20px 0; font-size: 14px; }
		.breadcrumb a { color: #003366; text-decoration: none; }
		h2 { color: #003366; font-size: 24px; margin-top: 30px; margin-bottom: 15px; }
		h3 { color: #0066cc; font-size: 18px; margin-top: 20px; }
		.cta-button { background: #ff6600; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
		.cta-button:hover { background: #ff5500; }
		.service-area { background: #f5f5f5; padding: 15px; margin: 20px 0; border-left: 4px solid #ff6600; }
		.testimonial { background: white; border: 1px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 5px; }
		.testimonial .author { font-weight: bold; color: #003366; }
		.testimonial .rating { color: #ff6600; }
		.local-info { background: #e8f4f8; padding: 15px; margin: 15px 0; border-radius: 5px; }
		.map-container { margin: 20px 0; height: 400px; background: #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; }
		footer { background: #003366; color: white; padding: 20px; text-align: center; margin-top: 40px; }
	</style>
</head>
<body>
	<header>
		<h1><?php echo escape_output($service['name']); ?> in San Mateo, Rizal</h1>
	</header>

	<div class="container">
		<!-- Breadcrumb -->
		<div class="breadcrumb">
			<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>">Home</a> >
			<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>/services">Services</a> >
			<span>CCTV Systems San Mateo</span>
		</div>

		<!-- Main Content -->
		<h2>Professional CCTV Installation & Monitoring for San Mateo</h2>
		<p>
			At <?php echo escape_output(constant('BUSINESS_NAME')); ?>, we provide comprehensive CCTV and surveillance solutions for businesses and residences in San Mateo, Rizal. With over 10 years of experience serving the local community, we deliver professional installation, 24/7 monitoring, and responsive support.
		</p>

		<div class="local-info">
			<strong>Based in <?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?>, <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?></strong><br>
			<?php echo format_address(true); ?><br>
			<strong>Phone:</strong> <?php echo format_phone(); ?><br>
			<strong>Service Area:</strong> San Mateo, Ampid-1, Rizal, Cainta, Pasig, and Metro Manila
		</div>

		<h2>Our CCTV Services</h2>
		<h3>System Design & Installation</h3>
		<ul>
			<li>Custom CCTV system design based on your property layout</li>
			<li>Professional camera placement for optimal coverage</li>
			<li>Complete installation including wiring and configuration</li>
			<li>HD and 4K camera options available</li>
			<li>Analog, IP, and hybrid system support</li>
		</ul>

		<h3>24/7 Monitoring Services</h3>
		<ul>
			<li>Round-the-clock remote monitoring from our San Mateo office</li>
			<li>Instant alert notifications via SMS and email</li>
			<li>Mobile app access to live feed and recordings</li>
			<li>Professional response to suspicious activity</li>
			<li>Integration with local authorities as needed</li>
		</ul>

		<h3>Maintenance & Support</h3>
		<ul>
			<li>Regular system maintenance and updates</li>
			<li>Quick response for technical issues</li>
			<li>Same-day repair service for San Mateo area</li>
			<li>Extended warranty plans available</li>
			<li>Training for system operation</li>
		</ul>

		<h2>Service Areas in San Mateo & Rizal</h2>
		<div class="service-area">
			<strong>Primary Service Area (Same-Day Service):</strong><br>
			San Mateo, Ampid-1, Cainta, Pasig
		</div>

		<div class="service-area">
			<strong>Secondary Service Area:</strong><br>
			Marikina, Quezon City, Manila, Makati, and other Metro Manila areas
		</div>

		<h2>Why Choose ASMR CCTV Trading?</h2>
		<ul>
			<li><strong>Local Expertise:</strong> 10+ years serving San Mateo and Rizal businesses</li>
			<li><strong>Same-Day Service:</strong> Installation and support available same day for San Mateo</li>
			<li><strong>Professional Team:</strong> Certified technicians and installers</li>
			<li><strong>24/7 Monitoring:</strong> Continuous surveillance from our local office</li>
			<li><strong>Affordable Solutions:</strong> Competitive pricing with no hidden costs</li>
			<li><strong>Local Support:</strong> Call <?php echo format_phone(); ?> anytime for assistance</li>
		</ul>

		<h2>Local Testimonials</h2>
		<div class="testimonial">
			<div class="author">Maria Santos - San Mateo Business Owner</div>
			<div class="rating">★★★★★</div>
			<p>"ASMR CCTV Trading installed our office system in just one day. The quality is excellent and their support team is always responsive. Highly recommended for San Mateo businesses!"</p>
		</div>

		<div class="testimonial">
			<div class="author">Juan Dela Cruz - Ampid-1 Resident</div>
			<div class="rating">★★★★★</div>
			<p>"Had peace of mind with 24/7 monitoring. They detected an intrusion attempt and immediately notified me. Best investment for home security in San Mateo."</p>
		</div>

		<div class="testimonial">
			<div class="author">Rosa Garcia - Cainta Property Manager</div>
			<div class="rating">★★★★★</div>
			<p>"Professional service and affordable pricing. They covered multiple properties and everything works perfectly. Great team!"</p>
		</div>

		<!-- Map Placeholder -->
		<h2>Find Us in San Mateo</h2>
		<div class="map-container">
			[Google Maps Embed - Center: San Mateo, Rizal] <br>
			<em>Embedded map showing location and service area</em>
		</div>

		<!-- Call to Action -->
		<h2>Ready to Secure Your Property?</h2>
		<p>Contact <?php echo escape_output(constant('BUSINESS_NAME')); ?> today for a free CCTV consultation and quote.</p>
		<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>/contact?service=cctv" class="cta-button">Get Free Quote</a>
		<a href="tel:<?php echo constant('BUSINESS_PHONE'); ?>" class="cta-button">Call Now: <?php echo format_phone(); ?></a>

		<p style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
			<strong>Limited Time Offer:</strong> Get 15% off CCTV installation for San Mateo area customers. Call or contact us for details.
		</p>
	</div>

	<footer>
		<p>&copy; 2026 <?php echo escape_output(constant('BUSINESS_NAME')); ?>. All rights reserved.</p>
		<p><?php echo escape_output(constant('BUSINESS_ADDRESS_FULL')); ?> | <?php echo format_phone(); ?></p>
	</footer>
</body>
</html>
