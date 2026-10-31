<?php
/**
 * IT Maintenance & Support in San Mateo, Rizal - Service Page
 * Location: /services/it-maintenance.php
 * Local SEO focused page targeting San Mateo IT services market
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

$page_title = 'IT Maintenance & Managed Services in San Mateo, Rizal | ASMR CCTV Trading';
$page_description = 'Professional IT maintenance and managed services for San Mateo businesses. Preventive maintenance, on-site support, system administration. Call for quote.';
$page_keywords = [
	'IT Maintenance San Mateo',
	'Managed IT Services Rizal',
	'On-site Support Ampid-1',
	'System Administration',
	'IT Support Services',
	'Preventive Maintenance',
];

$service = get_service_by_id('maintenance');
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
	<meta property="og:url" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/it-maintenance.php">
	<meta property="og:image" content="<?php echo constant('BUSINESS_WEBSITE'); ?>/images/services/it-maintenance.jpg">

	<!-- Canonical -->
	<link rel="canonical" href="<?php echo constant('BUSINESS_WEBSITE'); ?>/services/it-maintenance.php">

	<!-- Schema Markup -->
	<?php inject_all_schemas('maintenance', [
		[
			'question' => 'What does IT maintenance include?',
			'answer' => 'System updates, security patches, backups, performance monitoring, preventive hardware maintenance, and user support.',
		],
		[
			'question' => 'How much does IT maintenance cost in San Mateo?',
			'answer' => 'Pricing depends on business size and complexity. We offer flexible plans: hourly, monthly, or annual contracts.',
		],
		[
			'question' => 'Do you offer 24/7 IT support?',
			'answer' => 'Standard business hours support is included. 24/7 monitoring and support available as premium add-on.',
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
		.plan { background: #f9f9f9; border: 2px solid #ddd; padding: 20px; margin: 15px 0; border-radius: 5px; }
		.plan.recommended { border-color: #ff6600; background: #fff9f0; }
		.plan h3 { color: #003366; }
		.plan ul { list-style: none; padding: 0; }
		.plan li { padding: 8px 0; border-bottom: 1px solid #eee; }
		.plan li:before { content: "✓ "; color: #ff6600; font-weight: bold; }
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
			<span>IT Maintenance & Support</span>
		</div>

		<!-- Main Content -->
		<h2>Managed IT Maintenance & Support for San Mateo Businesses</h2>
		<p>
			<?php echo escape_output(constant('BUSINESS_NAME')); ?> provides comprehensive IT maintenance and managed services for businesses in San Mateo, Rizal. Our proactive approach prevents problems before they occur, keeping your systems running smoothly and your team productive. Focus on your business while we handle your IT infrastructure.
		</p>

		<div class="local-info">
			<strong>Serving <?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?> and <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?></strong><br>
			<?php echo format_address(true); ?><br>
			<strong>Phone:</strong> <?php echo format_phone(); ?><br>
			<strong>Response Time:</strong> Same-day for critical issues in San Mateo area
		</div>

		<h2>Preventive Maintenance Services</h2>
		<h3>System Monitoring & Management</h3>
		<ul>
			<li>24/7 system monitoring and automated alerts</li>
			<li>Performance optimization and tuning</li>
			<li>Server and workstation health checks</li>
			<li>Storage capacity management</li>
			<li>Application performance monitoring</li>
		</ul>

		<h3>Security & Updates</h3>
		<ul>
			<li>Operating system and application patches</li>
			<li>Security updates and vulnerability management</li>
			<li>Antivirus and malware protection updates</li>
			<li>Firewall and intrusion detection management</li>
			<li>Security audits and compliance reporting</li>
		</ul>

		<h3>Backup & Disaster Recovery</h3>
		<ul>
			<li>Automated daily backups</li>
			<li>Offsite backup storage</li>
			<li>Disaster recovery planning and testing</li>
			<li>Data recovery services</li>
			<li>Business continuity planning</li>
		</ul>

		<h3>User Support</h3>
		<ul>
			<li>Help desk support via phone, email, and ticket system</li>
			<li>On-site support for urgent issues</li>
			<li>Account and password management</li>
			<li>Software troubleshooting and training</li>
			<li>Priority response for critical business systems</li>
		</ul>

		<h2>Our Service Plans</h2>

		<div class="plan">
			<h3>Essential Plan</h3>
			<ul>
				<li>Monthly system maintenance visits</li>
				<li>Security updates and patches</li>
				<li>Basic help desk support (business hours)</li>
				<li>Monthly performance reports</li>
			</ul>
			<p><strong>Starting at: ₱15,000/month</strong></p>
		</div>

		<div class="plan recommended">
			<h3>Professional Plan (Most Popular)</h3>
			<ul>
				<li>Bi-weekly maintenance visits</li>
				<li>24/7 system monitoring</li>
				<li>Priority help desk support</li>
				<li>Automated daily backups</li>
				<li>Security audits quarterly</li>
				<li>Detailed monthly reports</li>
			</ul>
			<p><strong>Starting at: ₱30,000/month</strong></p>
		</div>

		<div class="plan">
			<h3>Enterprise Plan</h3>
			<ul>
				<li>Weekly on-site visits</li>
				<li>24/7 monitoring and support</li>
				<li>Dedicated IT support team</li>
				<li>Advanced backup and disaster recovery</li>
				<li>Strategic IT planning and consulting</li>
				<li>Custom compliance and security reviews</li>
			</ul>
			<p><strong>Custom Pricing - Request Quote</strong></p>
		</div>

		<h2>Why Choose Our IT Services?</h2>
		<ul>
			<li><strong>Local Presence:</strong> Based in San Mateo with quick response times</li>
			<li><strong>Experienced Team:</strong> Certified IT professionals with 10+ years experience</li>
			<li><strong>Proactive Approach:</strong> Prevention vs. reaction - save money and downtime</li>
			<li><strong>Flexible Plans:</strong> Customized to your business size and needs</li>
			<li><strong>24/7 Monitoring:</strong> Your systems monitored around the clock</li>
			<li><strong>Transparent Pricing:</strong> No hidden fees, clear contracts</li>
			<li><strong>Local Support:</strong> Local team means better understanding of your business</li>
		</ul>

		<h2>Client Testimonials</h2>
		<div class="testimonial">
			<div class="author">Benjamin Cruz - San Mateo Logistics Company</div>
			<div class="rating">★★★★★</div>
			<p>"We switched to ASMR CCTV's managed IT services and haven't had a major system failure since. Their proactive maintenance saves us time and money. Highly professional!"</p>
		</div>

		<div class="testimonial">
			<div class="author">Angela Santos - Rizal Real Estate Firm</div>
			<div class="rating">★★★★★</div>
			<p>"Their help desk support is excellent. They resolve most issues remotely without disrupting our office. Best IT service provider we've worked with!"</p>
		</div>

		<!-- Call to Action -->
		<h2>Get Professional IT Maintenance Today</h2>
		<p>Schedule a free consultation to discuss your business IT needs and get a custom maintenance plan.</p>
		<a href="<?php echo constant('BUSINESS_WEBSITE'); ?>/contact?service=maintenance" class="cta-button">Request Consultation</a>
		<a href="tel:<?php echo constant('BUSINESS_PHONE'); ?>" class="cta-button">Call: <?php echo format_phone(); ?></a>

		<p style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
			<strong>First Month 50% Off:</strong> Sign up for any maintenance plan in October and get 50% off your first month. Limited offer!
		</p>
	</div>

	<footer>
		<p>&copy; 2026 <?php echo escape_output(constant('BUSINESS_NAME')); ?>. All rights reserved.</p>
		<p><?php echo escape_output(constant('BUSINESS_ADDRESS_FULL')); ?> | <?php echo format_phone(); ?></p>
	</footer>
</body>
</html>
