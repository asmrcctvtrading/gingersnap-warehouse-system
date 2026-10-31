<?php
/**
 * Network Solutions in San Mateo, Rizal - Service Page
 * Location: /services/network-solutions.php
 * Local SEO focused page targeting San Mateo network market
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

$page_title = 'Network Solutions & IT Infrastructure in San Mateo, Rizal | ASMR CCTV Trading';
$page_description = 'Enterprise and SME network design, setup, and support for San Mateo, Rizal. LAN/WAN solutions, network security, and maintenance.';
$page_keywords = [
	'Network Solutions San Mateo',
	'Business Internet Rizal',
	'LAN/WAN Setup Ampid-1',
	'Network Infrastructure',
	'IT Networking Services',
	'Enterprise Network',
];

$service = get_service_by_id('network');
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
	<meta property="og:url" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/network-solutions.php">
	<meta property="og:image" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/images/services/network-solutions.jpg">

	<!-- Canonical -->
	<link rel="canonical" href="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/network-solutions.php">

	<!-- Schema Markup -->
	<?php inject_all_schemas('network', [
		[
			'question' => 'What network services do you offer in San Mateo?',
			'answer' => 'We provide LAN/WAN setup, network security, WiFi solutions, and complete network infrastructure design.',
		],
		[
			'question' => 'How long does network installation take?',
			'answer' => 'Installation timeline depends on network scope. Small networks: 1-2 days. Enterprise networks: 1-2 weeks.',
		],
		[
			'question' => 'Do you provide network maintenance contracts?',
			'answer' => 'Yes, we offer monthly, quarterly, and annual maintenance contracts with priority support.',
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
			<span>Network Solutions</span>
		</div>

		<!-- Main Content -->
		<h2>Enterprise & SME Network Solutions for San Mateo</h2>
		<p>
			<?php echo escape_output(constant('BUSINESS_NAME')); ?> provides comprehensive network infrastructure solutions for businesses of all sizes in San Mateo, Rizal. From small office LAN setups to enterprise WAN implementations, our team designs and deploys reliable, secure networks tailored to your business needs.
		</p>

		<div class="local-info">
			<strong>Based in <?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?>, <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?></strong><br>
			<?php echo format_address(true); ?><br>
			<strong>Phone:</strong> <?php echo format_phone(); ?><br>
			<strong>Service Area:</strong> San Mateo, Rizal, and Metro Manila
		</div>

		<h2>Network Solutions We Offer</h2>
		<h3>Network Design & Planning</h3>
		<ul>
			<li>Comprehensive network assessment and audit</li>
			<li>Custom network design based on business requirements</li>
			<li>Scalability planning for future growth</li>
			<li>Network security assessment and recommendations</li>
			<li>Documentation and network diagram creation</li>
		</ul>

		<h3>Network Installation & Setup</h3>
		<ul>
			<li>LAN (Local Area Network) installation and configuration</li>
			<li>WAN (Wide Area Network) implementation</li>
			<li>WiFi network design and deployment</li>
			<li>Switch and router configuration</li>
			<li>Cabling infrastructure and termination</li>
			<li>Network testing and performance validation</li>
		</ul>

		<h3>Network Security</h3>
		<ul>
			<li>Firewall installation and configuration</li>
			<li>VPN (Virtual Private Network) setup</li>
			<li>Network access control (NAC) implementation</li>
			<li>DDoS protection and mitigation</li>
			<li>Security policy development</li>
		</ul>

		<h3>Network Maintenance & Support</h3>
		<ul>
			<li>24/7 network monitoring and alerts</li>
			<li>Preventive maintenance programs</li>
			<li>Performance optimization and tuning</li>
			<li>Regular security patches and updates</li>
			<li>Dedicated support team for priority issues</li>
		</ul>

		<h2>Who We Serve</h2>
		<div class="service-area">
			<strong>Small & Medium Businesses (SMEs):</strong><br>
			Complete network solutions from design to support. Perfect for growing businesses in San Mateo.
		</div>

		<div class="service-area">
			<strong>Enterprise Organizations:</strong><br>
			Complex multi-site networks, WAN implementations, and managed services for large organizations.
		</div>

		<h2>Technology Stack</h2>
		<ul>
			<li>Cisco, Juniper, and Mikrotik routing solutions</li>
			<li>Enterprise-grade switches and access points</li>
			<li>Fortinet and Palo Alto Networks firewalls</li>
			<li>Network monitoring tools: Nagios, Zabbix, PRTG</li>
			<li>Cloud and hybrid network integration</li>
		</ul>

		<h2>Client Testimonials</h2>
		<div class="testimonial">
			<div class="author">Robert Santos - San Mateo Manufacturing</div>
			<div class="rating">★★★★★</div>
			<p>"ASMR CCTV Trading redesigned our entire network. Everything is faster, more secure, and their support team is always responsive. Best decision we made this year."</p>
		</div>

		<div class="testimonial">
			<div class="author">Lisa Reyes - Rizal Business Center</div>
			<div class="rating">★★★★★</div>
			<p>"Professional network design and implementation. They understood our growth plans and built a network that scales. Excellent service!"</p>
		</div>

		<!-- Call to Action -->
		<h2>Ready to Upgrade Your Network?</h2>
		<p>Schedule a free network assessment for your San Mateo business today.</p>
		<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>/contact?service=network" class="cta-button">Get Free Assessment</a>
		<a href="tel:<?php echo constant('BUSINESS_PHONE'); ?>" class="cta-button">Call Now: <?php echo format_phone(); ?></a>

		<p style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
			<strong>Network Consultation Promo:</strong> Free network audit and optimization plan for new clients. Limited time offer!
		</p>
	</div>

	<footer>
		<p>&copy; 2026 <?php echo escape_output(constant('BUSINESS_NAME')); ?>. All rights reserved.</p>
		<p><?php echo escape_output(constant('BUSINESS_ADDRESS_FULL')); ?> | <?php echo format_phone(); ?></p>
	</footer>
</body>
</html>
