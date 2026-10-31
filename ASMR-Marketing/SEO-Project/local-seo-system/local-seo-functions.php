<?php
/**
 * Local SEO Helper Functions - ASMR CCTV Trading
 * Functions for schema generation, citations, and SEO utilities
 * Last Updated: 2026-10-28
 */

if (!function_exists('get_service_by_id')) {
	function get_service_by_id($service_id) {
		$services = constant('SERVICES');
		foreach ($services as $service) {
			if ($service['id'] === $service_id) {
				return $service;
			}
		}
		return null;
	}
}

if (!function_exists('get_service_by_slug')) {
	function get_service_by_slug($slug) {
		$services = constant('SERVICES');
		foreach ($services as $service) {
			if ($service['slug'] === $slug) {
				return $service;
			}
		}
		return null;
	}
}

if (!function_exists('format_business_hours')) {
	function format_business_hours($day = null) {
		$hours = constant('BUSINESS_HOURS');
		if ($day) {
			$day = strtolower($day);
			return $hours[$day] ?? null;
		}
		return $hours;
	}
}

if (!function_exists('get_business_hours_json')) {
	function get_business_hours_json() {
		$hours = constant('BUSINESS_HOURS');
		$json_hours = [];
		$days_map = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];

		foreach ($days_map as $day_name => $day_num) {
			if ($hours[$day_name][0] !== 'closed') {
				$json_hours[] = [
					'@type' => 'OpeningHoursSpecification',
					'dayOfWeek' => ucfirst($day_name),
					'opens' => $hours[$day_name][0],
					'closes' => $hours[$day_name][1],
				];
			}
		}
		return $json_hours;
	}
}

if (!function_exists('generate_organization_schema')) {
	function generate_organization_schema() {
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'@id' => constant('BUSINESS_WEBSITE') . '/#organization',
			'name' => constant('BUSINESS_NAME'),
			'alternateName' => constant('BUSINESS_NAME_SHORT'),
			'url' => constant('BUSINESS_WEBSITE'),
			'logo' => [
				'@type' => 'ImageObject',
				'url' => constant('BUSINESS_LOGO'),
				'width' => constant('BUSINESS_LOGO_WIDTH'),
				'height' => constant('BUSINESS_LOGO_HEIGHT'),
			],
			'sameAs' => array_values(constant('SOCIAL_PROFILES')),
			'contactPoint' => [
				'@type' => 'ContactPoint',
				'telephone' => constant('BUSINESS_PHONE'),
				'contactType' => 'Customer Service',
				'areaServed' => 'PH',
				'availableLanguage' => ['en', 'fil'],
			],
		];
		return $schema;
	}
}

if (!function_exists('generate_localbusiness_schema')) {
	function generate_localbusiness_schema() {
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'LocalBusiness',
			'@id' => constant('BUSINESS_WEBSITE') . '/#localbusiness',
			'name' => constant('BUSINESS_NAME'),
			'image' => constant('BUSINESS_LOGO'),
			'url' => constant('BUSINESS_WEBSITE'),
			'telephone' => constant('BUSINESS_PHONE'),
			'email' => constant('BUSINESS_EMAIL'),
			'address' => [
				'@type' => 'PostalAddress',
				'streetAddress' => constant('BUSINESS_STREET'),
				'addressLocality' => constant('BUSINESS_MUNICIPALITY'),
				'addressRegion' => constant('BUSINESS_PROVINCE'),
				'postalCode' => constant('BUSINESS_POSTAL_CODE'),
				'addressCountry' => constant('BUSINESS_COUNTRY'),
			],
			'geo' => [
				'@type' => 'GeoCoordinates',
				'latitude' => constant('BUSINESS_LATITUDE'),
				'longitude' => constant('BUSINESS_LONGITUDE'),
			],
			'openingHoursSpecification' => get_business_hours_json(),
			'areaServed' => array_merge(constant('PRIMARY_SERVICE_AREAS'), constant('SECONDARY_SERVICE_AREAS')),
			'priceRange' => '$$',
		];
		return $schema;
	}
}

if (!function_exists('generate_service_schema')) {
	function generate_service_schema($service_id = null) {
		if (!$service_id) {
			return null;
		}

		$service = get_service_by_id($service_id);
		if (!$service) {
			return null;
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Service',
			'@id' => constant('BUSINESS_WEBSITE') . '/services/' . $service['slug'] . '/#service',
			'name' => $service['name'],
			'description' => $service['description'],
			'provider' => [
				'@type' => 'LocalBusiness',
				'name' => constant('BUSINESS_NAME'),
				'url' => constant('BUSINESS_WEBSITE'),
			],
			'areaServed' => [
				'@type' => 'City',
				'name' => constant('BUSINESS_MUNICIPALITY'),
			],
			'serviceType' => $service['name'],
			'image' => constant('BUSINESS_WEBSITE') . '/images/services/' . $service['slug'] . '.jpg',
		];

		return $schema;
	}
}

if (!function_exists('generate_breadcrumb_schema')) {
	function generate_breadcrumb_schema($breadcrumbs = []) {
		$default_breadcrumbs = [
			['name' => constant('BUSINESS_NAME'), 'url' => constant('BUSINESS_WEBSITE')],
		];

		$breadcrumbs = !empty($breadcrumbs) ? $breadcrumbs : $default_breadcrumbs;

		$items = [];
		foreach ($breadcrumbs as $index => $crumb) {
			$items[] = [
				'@type' => 'ListItem',
				'position' => $index + 1,
				'name' => $crumb['name'],
				'item' => $crumb['url'],
			];
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $items,
		];

		return $schema;
	}
}

if (!function_exists('generate_faq_schema')) {
	function generate_faq_schema($faqs = []) {
		if (empty($faqs)) {
			$faqs = [
				[
					'question' => 'Do you service the San Mateo, Rizal area?',
					'answer' => 'Yes, we are based in San Mateo and provide same-day service for the immediate area and surrounding municipalities.',
				],
				[
					'question' => 'What services does ASMR CCTV Trading provide?',
					'answer' => 'We offer CCTV systems, network solutions, computer sales & support, and IT maintenance services.',
				],
				[
					'question' => 'Are you available on weekends?',
					'answer' => 'We are open Saturday 9 AM - 5 PM. Sunday appointments available upon request. Call for emergencies.',
				],
				[
					'question' => 'What is your service warranty?',
					'answer' => 'All installations come with 1-year warranty. Extended maintenance plans available.',
				],
				[
					'question' => 'Do you offer 24/7 monitoring?',
					'answer' => 'Yes, we provide 24/7 CCTV monitoring services with instant alert notifications.',
				],
			];
		}

		$main_entity = [];
		foreach ($faqs as $faq) {
			$main_entity[] = [
				'@type' => 'Question',
				'name' => $faq['question'],
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text' => $faq['answer'],
				],
			];
		}

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'FAQPage',
			'mainEntity' => $main_entity,
		];

		return $schema;
	}
}

if (!function_exists('inject_schema')) {
	function inject_schema($schema_array) {
		if (is_array($schema_array) && !empty($schema_array)) {
			echo '<script type="application/ld+json">' . "\n";
			echo json_encode($schema_array, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
			echo '</script>' . "\n";
		}
	}
}

if (!function_exists('inject_all_schemas')) {
	function inject_all_schemas($service_id = null, $custom_faqs = null) {
		// Organization Schema
		inject_schema(generate_organization_schema());

		// LocalBusiness Schema
		inject_schema(generate_localbusiness_schema());

		// Service Schema (if specific service)
		if ($service_id) {
			inject_schema(generate_service_schema($service_id));
		}

		// Breadcrumb Schema
		inject_schema(generate_breadcrumb_schema());

		// FAQ Schema
		inject_schema(generate_faq_schema($custom_faqs));
	}
}

if (!function_exists('sanitize_input')) {
	function sanitize_input($input) {
		if (is_array($input)) {
			return array_map('sanitize_input', $input);
		}
		return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('escape_output')) {
	function escape_output($output) {
		return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('get_local_meta_tags')) {
	function get_local_meta_tags($title, $description, $keywords = []) {
		$meta = [
			'title' => escape_output($title),
			'description' => escape_output($description),
			'keywords' => escape_output(implode(', ', $keywords)),
			'og:title' => escape_output($title),
			'og:description' => escape_output($description),
			'og:type' => 'business.business',
			'og:url' => constant('BUSINESS_WEBSITE'),
			'og:image' => constant('BUSINESS_LOGO'),
			'twitter:card' => 'summary_large_image',
			'twitter:title' => escape_output($title),
			'twitter:description' => escape_output($description),
			'twitter:image' => constant('BUSINESS_LOGO'),
			'geo:position' => constant('BUSINESS_LATITUDE') . '; ' . constant('BUSINESS_LONGITUDE'),
			'ICBM' => constant('BUSINESS_LATITUDE') . ', ' . constant('BUSINESS_LONGITUDE'),
		];
		return $meta;
	}
}

if (!function_exists('format_phone')) {
	function format_phone($phone = null) {
		$phone = $phone ?: constant('BUSINESS_PHONE');
		return escape_output($phone);
	}
}

if (!function_exists('format_address')) {
	function format_address($multiline = false) {
		if ($multiline) {
			return escape_output(constant('BUSINESS_STREET')) . '<br>' .
				   escape_output(constant('BUSINESS_BARANGAY')) . '<br>' .
				   escape_output(constant('BUSINESS_MUNICIPALITY')) . ', ' .
				   escape_output(constant('BUSINESS_PROVINCE')) . ' ' .
				   escape_output(constant('BUSINESS_POSTAL_CODE')) . '<br>' .
				   escape_output(constant('BUSINESS_COUNTRY'));
		}
		return escape_output(constant('BUSINESS_ADDRESS_FULL'));
	}
}

if (!function_exists('get_citation_list')) {
	function get_citation_list() {
		return [
			[
				'name' => 'Google Business Profile',
				'url' => 'https://business.google.com/',
				'priority' => 'Critical',
				'status' => 'Completed',
			],
			[
				'name' => 'Yellow Pages Philippines',
				'url' => 'https://www.yellowpages.ph/',
				'priority' => 'High',
				'status' => 'Pending',
			],
			[
				'name' => 'Business.com Philippines',
				'url' => 'https://www.business.com/',
				'priority' => 'High',
				'status' => 'Pending',
			],
			[
				'name' => 'Chamber of Commerce Philippines',
				'url' => 'https://www.philcham.org/',
				'priority' => 'Medium',
				'status' => 'Pending',
			],
			[
				'name' => 'Rizal Provincial Directory',
				'url' => 'https://rizal.gov.ph/',
				'priority' => 'Medium',
				'status' => 'Pending',
			],
			[
				'name' => 'JustDial Philippines',
				'url' => 'https://www.justdial.com/',
				'priority' => 'Medium',
				'status' => 'Pending',
			],
		];
	}
}

return true;
