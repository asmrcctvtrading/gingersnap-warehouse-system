<?php
/**
 * Local SEO Configuration - ASMR CCTV Trading
 * Central configuration for all local SEO components
 * Last Updated: 2026-10-28
 */

// Business Information (NAP - Name, Address, Phone)
define('BUSINESS_NAME', 'ASMR CCTV Trading');
define('BUSINESS_NAME_SHORT', 'ASMR CCTV');
define('BUSINESS_PHONE', '+63 976 382 3899');
define('BUSINESS_EMAIL', 'inquiries@asmrcctvtechnologies.com');
define('BUSINESS_WEBSITE', 'https://asmrcctvtechnologies.com');

// Address Information
define('BUSINESS_STREET', '110 Gen. Luna Ave');
define('BUSINESS_BARANGAY', 'Ampid-1');
define('BUSINESS_MUNICIPALITY', 'San Mateo');
define('BUSINESS_PROVINCE', 'Rizal');
define('BUSINESS_POSTAL_CODE', '1850');
define('BUSINESS_COUNTRY', 'PH');

// Complete Address Format
define('BUSINESS_ADDRESS_FULL', BUSINESS_STREET . ', ' . BUSINESS_BARANGAY . ', ' . BUSINESS_MUNICIPALITY . ', ' . BUSINESS_PROVINCE . ' ' . BUSINESS_POSTAL_CODE . ', ' . BUSINESS_COUNTRY);

// Coordinates for maps/schema
define('BUSINESS_LATITUDE', '14.6091');
define('BUSINESS_LONGITUDE', '121.1818');

// Business Hours (ISO 8601 format)
define('BUSINESS_HOURS', [
	'monday' => ['09:00', '18:00'],
	'tuesday' => ['09:00', '18:00'],
	'wednesday' => ['09:00', '18:00'],
	'thursday' => ['09:00', '18:00'],
	'friday' => ['09:00', '18:00'],
	'saturday' => ['09:00', '17:00'],
	'sunday' => ['closed', 'closed'],
]);

// Service Areas (primary and secondary)
define('PRIMARY_SERVICE_AREAS', [
	'San Mateo',
	'Ampid-1',
	'Rizal',
]);

define('SECONDARY_SERVICE_AREAS', [
	'Cainta',
	'Pasig',
	'Marikina',
	'Quezon City',
	'Manila',
	'Makati',
	'Metro Manila',
]);

// Services Offered (with local focus keywords)
define('SERVICES', [
	[
		'id' => 'cctv',
		'name' => 'CCTV Systems & Surveillance',
		'slug' => 'cctv-san-mateo',
		'description' => 'Professional CCTV installation, maintenance, and monitoring for San Mateo, Rizal businesses and homes.',
		'keywords' => [
			'CCTV Systems San Mateo',
			'Surveillance Rizal',
			'Security Cameras Ampid-1',
			'CCTV Installation Metro Manila',
			'24/7 CCTV Monitoring',
		],
	],
	[
		'id' => 'network',
		'name' => 'Network Solutions',
		'slug' => 'network-solutions',
		'description' => 'Enterprise and SME network design, implementation, and support in San Mateo, Rizal area.',
		'keywords' => [
			'Network Solutions San Mateo',
			'Business Internet Rizal',
			'Network Setup Ampid-1',
			'IT Infrastructure Manila',
			'LAN/WAN Solutions',
		],
	],
	[
		'id' => 'computers',
		'name' => 'Computer Sales & Support',
		'slug' => 'computer-sales-support',
		'description' => 'Computer sales, repairs, and technical support in San Mateo, Rizal. Same-day service available.',
		'keywords' => [
			'Computer Sales San Mateo',
			'PC Repair Rizal',
			'Laptop Support Ampid-1',
			'Computer Stores Metro Manila',
			'Technical Support',
		],
	],
	[
		'id' => 'maintenance',
		'name' => 'IT Maintenance & Support',
		'slug' => 'it-maintenance',
		'description' => 'Preventive and corrective IT maintenance, on-site support, and managed services for San Mateo area.',
		'keywords' => [
			'IT Maintenance San Mateo',
			'Computer Support Rizal',
			'Managed Services Ampid-1',
			'IT Help Desk Manila',
			'System Administration',
		],
	],
]);

// Social Profiles
define('SOCIAL_PROFILES', [
	'facebook' => 'https://facebook.com/asmrcctvtrading',
	'google' => 'https://business.google.com/asmrcctvtrading',
	'youtube' => 'https://youtube.com/@asmrcctvtrading',
]);

// Logo and Images
define('BUSINESS_LOGO', BUSINESS_WEBSITE . '/images/logo.png');
define('BUSINESS_LOGO_WIDTH', 250);
define('BUSINESS_LOGO_HEIGHT', 100);

// Schema Configuration
define('ORGANIZATION_SCHEMA_ID', '#organization');
define('LOCALBUSINESS_SCHEMA_ID', '#localbusiness');

// Review Configuration
define('REVIEW_REQUEST_DELAY_DAYS', 30); // Request reviews 30 days after service
define('REVIEW_REQUEST_MAX_ATTEMPTS', 3);
define('REVIEW_EXPIRY_DAYS', 90);

// SEO Configuration
define('META_AUTHOR', 'ASMR CCTV Trading');
define('META_PUBLISHER', 'ASMR CCTV Trading');
define('CANONICAL_BASE', BUSINESS_WEBSITE);

// Database Configuration (for review tracking)
define('REVIEW_DB_TABLE', 'local_seo_reviews');
define('REVIEW_REQUESTS_TABLE', 'local_seo_review_requests');
define('CITATION_TRACKING_TABLE', 'local_seo_citations');

// Cache Configuration
define('SCHEMA_CACHE_TTL', 86400); // 24 hours
define('CITATION_CACHE_TTL', 604800); // 7 days

// Local Keywords (used in SEO dashboard and content)
define('LOCAL_KEYWORDS', [
	// San Mateo focus
	'CCTV San Mateo',
	'CCTV Rizal',
	'Surveillance San Mateo',
	'Security Cameras Rizal',
	'Network Solutions San Mateo',
	'Computer Repair San Mateo',
	'IT Support Rizal',
	'Computer Sales Ampid-1',

	// Service area variations
	'CCTV Installation Cainta',
	'Network Setup Pasig',
	'Computer Support Marikina',
	'IT Maintenance Metro Manila',

	// Long-tail keywords
	'Best CCTV Systems San Mateo',
	'Affordable Network Solutions Rizal',
	'24/7 Computer Support Ampid-1',
	'Professional IT Services San Mateo',
	'Trusted CCTV Installer Rizal',
	'Computer Store Near Me San Mateo',

	// Service keywords
	'Business CCTV Systems',
	'Residential Surveillance',
	'Enterprise Network Solutions',
	'Managed IT Services',
]);

return true;
