<?php
/**
 * Computer Sales & Support in San Mateo, Rizal - Service Page
 * Location: /services/computer-sales-support.php
 * Local SEO focused page targeting San Mateo computer market
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

$page_title = 'Computer Sales & Technical Support in San Mateo, Rizal | ASMR CCTV Trading';
$page_description = 'Buy computers in San Mateo with expert technical support. PC repair, laptop service, same-day support available. Local computer store in Ampid-1.';
$page_keywords = [
	'Computer Sales San Mateo',
	'PC Repair Rizal',
	'Laptop Support Ampid-1',
	'Computer Store',
	'Technical Support',
	'Computer Services',
];

$service = get_service_by_id('computers');
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
	<meta property="og:url" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/computer-sales-support.php">
	<meta property="og:image" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/images/services/computer-sales-support.jpg">

	<!-- Canonical -->
	<link rel="canonical" href="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/computer-sales-support.php">

	<!-- Schema Markup -->
	<?php inject_all_schemas('computers', [
		[
			'question' => 'Do you have computers in stock in San Mateo?',
			'answer' => 'Yes, we maintain inventory of desktops, laptops, and components. Same-day pickup available for in-stock items.',
		],
		[
			'question' => 'How quickly can you repair a computer in San Mateo?',
			'answer' => 'Most repairs are completed within 24-48 hours. Emergency repairs available for critical business systems.',
		],
		[
			'question' => 'Do you provide warranty on computers sold?',
			'answer' => 'All computers sold include manufacturer warranty plus 30-day satisfaction guarantee and free technical support.',
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
		.product-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
		.product-card { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
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
			<span>Computer Sales & Support</span>
		</div>

		<!-- Main Content -->
		<h2>Computer Sales & Technical Support for San Mateo</h2>
		<p>
			Welcome to <?php echo escape_output(constant('BUSINESS_NAME')); ?>'s computer store in San Mateo, Rizal. We offer quality computers, laptops, and computer components with professional technical support and repair services. Whether you're looking to buy a new system or need repairs for your existing one, our expert team is here to help.
		</p>

		<div class="local-info">
			<strong>Visit Our Store in <?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?></strong><br>
			<?php echo format_address(true); ?><br>
			<strong>Phone:</strong> <?php echo format_phone(); ?><br>
			<strong>Hours:</strong> Mon-Sat 9 AM - 6 PM | Sun 10 AM - 4 PM
		</div>

		<h2>What We Sell</h2>
		<div class="product-list">
			<div class="product-card">
				<h4>Desktop Computers</h4>
				<p>High-performance desktops for home, office, and gaming. Pre-configured systems or custom builds available.</p>
			</div>
			<div class="product-card">
				<h4>Laptops</h4>
				<p>Latest models from major brands: Dell, HP, ASUS, Lenovo. Business and consumer options in stock.</p>
			</div>
			<div class="product-card">
				<h4>Computer Components</h4>
				<p>CPUs, RAM, SSD, HDD, GPUs, PSUs, motherboards, and cooling solutions.</p>
			</div>
			<div class="product-card">
				<h4>Peripherals & Accessories</h4>
				<p>Monitors, keyboards, mice, headsets, cables, and networking equipment.</p>
			</div>
			<div class="product-card">
				<h4>Refurbished Systems</h4>
				<p>Quality refurbished computers at affordable prices. Fully tested and guaranteed.</p>
			</div>
			<div class="product-card">
				<h4>Business Bundles</h4>
				<p>Complete office setups with desktops, monitors, and peripherals. Special pricing for bulk orders.</p>
			</div>
		</div>

		<h2>Computer Repair & Support Services</h2>
		<h3>Hardware Repairs</h3>
		<ul>
			<li>Laptop and desktop repair</li>
			<li>Hard drive recovery and data backup</li>
			<li>Component replacement and upgrade</li>
			<li>Power supply and motherboard repairs</li>
			<li>Display and keyboard replacement</li>
		</ul>

		<h3>Software Services</h3>
		<ul>
			<li>Operating system installation (Windows, Linux)</li>
			<li>Software installation and configuration</li>
			<li>Virus removal and malware cleaning</li>
			<li>System optimization and performance tuning</li>
			<li>Data backup and recovery</li>
		</ul>

		<h3>Remote Support</h3>
		<ul>
			<li>Remote troubleshooting via phone or video call</li>
			<li>Software support and assistance</li>
			<li>Account setup and configuration</li>
			<li>Available Monday-Saturday 9 AM - 6 PM</li>
		</ul>

		<h2>Why Shop With Us?</h2>
		<ul>
			<li><strong>Local Inventory:</strong> Quality computers in stock, available for same-day pickup</li>
			<li><strong>Expert Team:</strong> Knowledgeable staff to help you choose the right system</li>
			<li><strong>Warranty:</strong> All systems include manufacturer warranty plus 30-day satisfaction guarantee</li>
			<li><strong>Support:</strong> Free technical support for systems purchased from us</li>
			<li><strong>Trade-in:</strong> Competitive prices for your old computer trade-in</li>
			<li><strong>Fast Service:</strong> Quick turnaround on repairs (24-48 hours average)</li>
			<li><strong>Local:</strong> Based in San Mateo, serving Rizal and Metro Manila for 10+ years</li>
		</ul>

		<h2>Customer Reviews</h2>
		<div class="testimonial">
			<div class="author">Ang Company Inc - San Mateo Office Manager</div>
			<div class="rating">★★★★★</div>
			<p>"Bought 5 computers for our office. Great quality, fair pricing, and they provided excellent setup support. Highly recommended for San Mateo businesses!"</p>
		</div>

		<div class="testimonial">
			<div class="author">Maria Gonzales - Ampid-1 Student</div>
			<div class="rating">★★★★★</div>
			<p>"My laptop had a serious problem. They fixed it quickly and at a reasonable price. The technicians were very professional. Thanks ASMR CCTV!"</p>
		</div>

		<!-- Call to Action -->
		<h2>Need a Computer or Repair?</h2>
		<p>Visit our store in San Mateo or contact us for advice on computer purchases and repairs.</p>
		<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>/contact?service=computers" class="cta-button">Send Inquiry</a>
		<a href="tel:<?php echo constant('BUSINESS_PHONE'); ?>" class="cta-button">Call: <?php echo format_phone(); ?></a>

		<p style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
			<strong>Grand Opening Special:</strong> Get 10% off computer purchases in San Mateo. Bring this coupon or mention online.
		</p>
	</div>

	<footer>
		<p>&copy; 2026 <?php echo escape_output(constant('BUSINESS_NAME')); ?>. All rights reserved.</p>
		<p><?php echo escape_output(constant('BUSINESS_ADDRESS_FULL')); ?> | <?php echo format_phone(); ?></p>
	</footer>
</body>
</html>
