<?php
/**
 * Review Request System - ASMR CCTV Trading
 * Send review requests to leads after service conversion
 * Include this in contact-handler.php or integrate with your lead management
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

class ReviewRequestSystem {

	private $db;
	private $review_table = REVIEW_REQUESTS_TABLE;
	private $delay_days = REVIEW_REQUEST_DELAY_DAYS;

	public function __construct($pdo = null) {
		$this->db = $pdo;
	}

	/**
	 * Send initial review request email
	 */
	public function send_review_request_email($customer_email, $customer_name, $service_type) {
		if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
			return ['success' => false, 'message' => 'Invalid email address'];
		}

		$customer_email = sanitize_input($customer_email);
		$customer_name = sanitize_input($customer_name);
		$service_type = sanitize_input($service_type);

		// Review request email template
		$review_link = $this->generate_review_link($customer_email);

		$subject = "Share Your Experience with " . constant('BUSINESS_NAME');

		$message = $this->build_review_email($customer_name, $service_type, $review_link);

		$headers = [
			'From' => constant('BUSINESS_EMAIL'),
			'Reply-To' => constant('BUSINESS_EMAIL'),
			'Content-Type' => 'text/html; charset=UTF-8',
		];

		// Send email
		$header_string = implode("\r\n", array_map(function($k, $v) {
			return "$k: $v";
		}, array_keys($headers), $headers));

		$sent = mail($customer_email, $subject, $message, $header_string);

		if ($sent) {
			// Log in database if available
			if ($this->db) {
				$this->log_review_request($customer_email, $customer_name, $service_type);
			}
			return ['success' => true, 'message' => 'Review request sent successfully'];
		} else {
			return ['success' => false, 'message' => 'Failed to send email'];
		}
	}

	/**
	 * Build HTML email template
	 */
	private function build_review_email($name, $service, $review_link) {
		$business_name = constant('BUSINESS_NAME');
		$phone = format_phone();

		$html = <<<EOT
<!DOCTYPE html>
<html>
<head>
	<style>
		body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
		.container { max-width: 600px; margin: 0 auto; background: #f5f5f5; }
		.header { background: linear-gradient(135deg, #003366 0%, #0066cc 100%); color: white; padding: 20px; text-align: center; }
		.content { background: white; padding: 30px; }
		.cta-button {
			background: #ff6600;
			color: white;
			padding: 12px 30px;
			text-decoration: none;
			border-radius: 5px;
			display: inline-block;
			margin: 20px 0;
		}
		.footer { background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; }
		.divider { border-top: 2px solid #ff6600; margin: 20px 0; }
		.stars { font-size: 24px; color: #ffc107; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h2>We'd Love Your Feedback!</h2>
		</div>

		<div class="content">
			<p>Hi {$name},</p>

			<p>Thank you for choosing <strong>{$business_name}</strong> for your {$service} needs! We truly appreciate your business.</p>

			<p>Your feedback is invaluable to us. If you're satisfied with our service, would you mind sharing your experience with others in the San Mateo community?</p>

			<p style="text-align: center; margin: 30px 0;">
				<a href="{$review_link}" class="cta-button">Leave a Review on Google</a>
			</p>

			<p>It only takes 2 minutes, and your review helps other customers make informed decisions while supporting our local business.</p>

			<div class="divider"></div>

			<h3>Why Google Reviews Matter</h3>
			<ul>
				<li>Help other San Mateo families and businesses find quality services</li>
				<li>Build trust in our local community</li>
				<li>Your genuine feedback helps us improve</li>
			</ul>

			<div class="divider"></div>

			<h3>Have Questions or Issues?</h3>
			<p>If you had any concerns with our service, we'd prefer to hear about them directly before you post a review. Please contact us:</p>
			<p>
				<strong>Phone:</strong> {$phone}<br>
				<strong>Email:</strong> inquiries@asmrcctvtechnologies.com<br>
				<strong>Location:</strong> 110 Gen. Luna Ave, Ampid-1, San Mateo, Rizal 1850
			</p>

			<div class="divider"></div>

			<p>Thank you again for choosing {$business_name}. We look forward to serving you again!</p>

			<p>Best regards,<br>
			<strong>The {$business_name} Team</strong>
			</p>
		</div>

		<div class="footer">
			<p>&copy; 2026 {$business_name}. All rights reserved.</p>
			<p>This email was sent because you recently used our services.
			<a href="mailto:inquiries@asmrcctvtechnologies.com">Unsubscribe from these requests</a></p>
		</div>
	</div>
</body>
</html>
EOT;

		return $html;
	}

	/**
	 * Generate review link with QR code
	 */
	public function generate_review_link($email) {
		// Standard Google Reviews link for ASMR CCTV Trading
		$google_review_url = 'https://search.google.com/local/writereview?placeid=YOUR_GOOGLE_PLACE_ID';

		// Alternative using Google Maps
		$google_maps_url = 'https://www.google.com/maps/search/' . urlencode(constant('BUSINESS_ADDRESS_FULL')) . '/reviews';

		return $google_review_url;
	}

	/**
	 * Generate QR code for review
	 */
	public function generate_qr_code($review_url) {
		// QR Code URL using Google Charts API
		$qr_url = 'https://chart.googleapis.com/chart?' . http_build_query([
			'chs' => '200x200',
			'choe' => 'UTF-8',
			'chld' => 'H|0',
			'cht' => 'qr',
			'chl' => $review_url,
		]);

		return $qr_url;
	}

	/**
	 * Log review request in database (PDO prepared statement)
	 */
	private function log_review_request($email, $name, $service) {
		if (!$this->db) {
			return false;
		}

		try {
			$sql = "INSERT INTO " . $this->review_table . "
					(customer_email, customer_name, service_type, request_date, scheduled_date, status, attempts)
					VALUES (:email, :name, :service, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), 'pending', 0)";

			$stmt = $this->db->prepare($sql);
			$stmt->bindParam(':email', $email);
			$stmt->bindParam(':name', $name);
			$stmt->bindParam(':service', $service);
			$stmt->bindValue(':delay', $this->delay_days, PDO::PARAM_INT);

			return $stmt->execute();
		} catch (PDOException $e) {
			error_log('Review request log error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get pending review requests (for scheduled sending)
	 */
	public function get_pending_requests($limit = 10) {
		if (!$this->db) {
			return [];
		}

		try {
			$sql = "SELECT * FROM " . $this->review_table . "
					WHERE status = 'pending'
					AND scheduled_date <= NOW()
					AND attempts < " . REVIEW_REQUEST_MAX_ATTEMPTS . "
					ORDER BY scheduled_date ASC
					LIMIT ?";

			$stmt = $this->db->prepare($sql);
			$stmt->bindValue(1, $limit, PDO::PARAM_INT);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log('Get pending requests error: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Mark review request as sent
	 */
	public function mark_as_sent($request_id) {
		if (!$this->db) {
			return false;
		}

		try {
			$sql = "UPDATE " . $this->review_table . "
					SET status = 'sent', attempts = attempts + 1, sent_date = NOW()
					WHERE id = ?";

			$stmt = $this->db->prepare($sql);
			$stmt->bindValue(1, $request_id, PDO::PARAM_INT);

			return $stmt->execute();
		} catch (PDOException $e) {
			error_log('Mark as sent error: ' . $e->getMessage());
			return false;
		}
	}
}

// Example usage (call this after a successful service completion/lead conversion)
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_review_request'])) {
	$review_system = new ReviewRequestSystem($pdo); // Pass your PDO connection

	$result = $review_system->send_review_request_email(
		$_POST['customer_email'],
		$_POST['customer_name'],
		$_POST['service_type']
	);

	if ($result['success']) {
		echo 'Review request sent successfully!';
	} else {
		echo 'Error: ' . $result['message'];
	}
}
*/

// Make class available for use
return new ReviewRequestSystem();
