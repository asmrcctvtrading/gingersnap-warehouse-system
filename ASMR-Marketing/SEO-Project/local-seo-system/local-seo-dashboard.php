<?php
/**
 * Local SEO Dashboard - ASMR CCTV Trading
 * Admin dashboard to monitor local SEO metrics and tasks
 * URL: /admin/local-seo-dashboard.php
 * Last Updated: 2026-10-28
 */

require_once dirname(__FILE__) . '/local-seo-config.php';
require_once dirname(__FILE__) . '/local-seo-functions.php';

// Simple admin check (replace with real authentication in production)
$is_admin = isset($_GET['key']) && $_GET['key'] === 'ASMRCCTVTRADING2026';

if (!$is_admin) {
	http_response_code(403);
	die('Unauthorized access');
}

// Simulated data (in production, fetch from database)
$schema_status = [
	'organization' => 'Valid',
	'localbusiness' => 'Valid',
	'services' => 'Valid',
	'faq' => 'Valid',
	'breadcrumb' => 'Valid',
	'last_validated' => date('Y-m-d H:i:s', strtotime('-2 days')),
];

$citations = get_citation_list();

$reviews = [
	'total' => 15,
	'rating' => 4.8,
	'pending_requests' => 3,
	'monthly_avg' => 2.5,
];

$local_keywords = constant('LOCAL_KEYWORDS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Local SEO Dashboard - <?php echo escape_output(constant('BUSINESS_NAME')); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: #f5f5f5;
			color: #333;
		}
		header {
			background: linear-gradient(135deg, #003366 0%, #0066cc 100%);
			color: white;
			padding: 20px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		header h1 { margin: 0; font-size: 24px; }
		.subtitle { font-size: 14px; opacity: 0.9; margin-top: 5px; }

		.container { max-width: 1400px; margin: 0 auto; padding: 20px; }

		.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }

		.card {
			background: white;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			padding: 20px;
		}

		.card h2 { color: #003366; font-size: 18px; margin-bottom: 15px; border-bottom: 2px solid #ff6600; padding-bottom: 10px; }

		.metric { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
		.metric:last-child { border-bottom: none; }
		.metric-label { color: #666; }
		.metric-value {
			font-size: 18px;
			font-weight: bold;
			color: #003366;
		}

		.status-valid { color: #28a745; }
		.status-warning { color: #ffc107; }
		.status-error { color: #dc3545; }

		.progress { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
		.progress-bar { background: #ff6600; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold; }

		.table { width: 100%; border-collapse: collapse; margin-top: 15px; }
		.table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: bold; color: #003366; border-bottom: 2px solid #ddd; }
		.table td { padding: 12px; border-bottom: 1px solid #ddd; }
		.table tr:hover { background: #f9f9f9; }

		.status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
		.status-badge.completed { background: #d4edda; color: #155724; }
		.status-badge.pending { background: #fff3cd; color: #856404; }
		.status-badge.in-progress { background: #cce5ff; color: #004085; }

		.checklist { list-style: none; }
		.checklist li { padding: 8px 0; display: flex; align-items: center; }
		.checklist li:before { content: "✓"; color: #28a745; font-weight: bold; margin-right: 10px; }
		.checklist li.pending:before { content: "○"; color: #ffc107; }

		.alert { padding: 15px; border-radius: 4px; margin: 15px 0; }
		.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
		.alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
		.alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

		.action-buttons { display: flex; gap: 10px; margin-top: 15px; }
		.btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
		.btn-primary { background: #003366; color: white; }
		.btn-primary:hover { background: #001f40; }
		.btn-secondary { background: #ff6600; color: white; }
		.btn-secondary:hover { background: #ff5500; }
		.btn-small { padding: 4px 12px; font-size: 12px; }

		footer { background: #f5f5f5; padding: 20px; text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
	</style>
</head>
<body>
	<header>
		<h1>Local SEO Dashboard</h1>
		<div class="subtitle"><?php echo escape_output(constant('BUSINESS_NAME')); ?> - <?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?>, <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?></div>
	</header>

	<div class="container">
		<!-- Quick Stats -->
		<div class="grid">
			<div class="card">
				<h2>Schema Validation</h2>
				<div class="metric">
					<span class="metric-label">Overall Status</span>
					<span class="metric-value status-valid">✓ Valid</span>
				</div>
				<div class="metric">
					<span class="metric-label">Last Validated</span>
					<span><?php echo escape_output($schema_status['last_validated']); ?></span>
				</div>
				<div style="margin-top: 15px;">
					<button class="btn btn-primary btn-small">Validate Now</button>
					<a href="https://search.google.com/test/rich-results" class="btn btn-secondary btn-small" target="_blank">Test in Google</a>
				</div>
			</div>

			<div class="card">
				<h2>Google Reviews</h2>
				<div class="metric">
					<span class="metric-label">Total Reviews</span>
					<span class="metric-value"><?php echo $reviews['total']; ?></span>
				</div>
				<div class="metric">
					<span class="metric-label">Average Rating</span>
					<span class="metric-value" style="color: #ffc107;">★ <?php echo $reviews['rating']; ?>/5</span>
				</div>
				<div class="metric">
					<span class="metric-label">Pending Requests</span>
					<span class="metric-value"><?php echo $reviews['pending_requests']; ?></span>
				</div>
				<div style="margin-top: 15px;">
					<button class="btn btn-primary btn-small">Send Review Requests</button>
				</div>
			</div>

			<div class="card">
				<h2>Business Information</h2>
				<div class="metric">
					<span class="metric-label">Business Name</span>
					<span><?php echo escape_output(constant('BUSINESS_NAME')); ?></span>
				</div>
				<div class="metric">
					<span class="metric-label">Phone</span>
					<span><?php echo format_phone(); ?></span>
				</div>
				<div class="metric">
					<span class="metric-label">Address</span>
					<span><?php echo escape_output(constant('BUSINESS_MUNICIPALITY')); ?>, <?php echo escape_output(constant('BUSINESS_PROVINCE')); ?></span>
				</div>
			</div>
		</div>

		<!-- Schema Markup Details -->
		<div class="card">
			<h2>Schema Markup Status</h2>
			<table class="table">
				<thead>
					<tr>
						<th>Schema Type</th>
						<th>Status</th>
						<th>Last Checked</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Organization</td>
						<td><span class="status-badge completed"><?php echo escape_output($schema_status['organization']); ?></span></td>
						<td><?php echo escape_output($schema_status['last_validated']); ?></td>
						<td><a href="#" class="btn btn-small btn-secondary">Edit</a></td>
					</tr>
					<tr>
						<td>LocalBusiness</td>
						<td><span class="status-badge completed"><?php echo escape_output($schema_status['localbusiness']); ?></span></td>
						<td><?php echo escape_output($schema_status['last_validated']); ?></td>
						<td><a href="#" class="btn btn-small btn-secondary">Edit</a></td>
					</tr>
					<tr>
						<td>Service Schemas (4)</td>
						<td><span class="status-badge completed"><?php echo escape_output($schema_status['services']); ?></span></td>
						<td><?php echo escape_output($schema_status['last_validated']); ?></td>
						<td><a href="#" class="btn btn-small btn-secondary">Edit</a></td>
					</tr>
					<tr>
						<td>FAQ</td>
						<td><span class="status-badge completed"><?php echo escape_output($schema_status['faq']); ?></span></td>
						<td><?php echo escape_output($schema_status['last_validated']); ?></td>
						<td><a href="#" class="btn btn-small btn-secondary">Edit</a></td>
					</tr>
					<tr>
						<td>Breadcrumb</td>
						<td><span class="status-badge completed"><?php echo escape_output($schema_status['breadcrumb']); ?></span></td>
						<td><?php echo escape_output($schema_status['last_validated']); ?></td>
						<td><a href="#" class="btn btn-small btn-secondary">Edit</a></td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Citations Checklist -->
		<div class="card">
			<h2>Local Citations & Directories</h2>
			<p style="color: #666; margin: 10px 0; font-size: 14px;">Track where your business is listed. NAP consistency is critical for local SEO.</p>
			<table class="table">
				<thead>
					<tr>
						<th>Directory</th>
						<th>Priority</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($citations as $citation): ?>
					<tr>
						<td><strong><?php echo escape_output($citation['name']); ?></strong></td>
						<td><span class="status-badge <?php echo strtolower($citation['priority'] === 'Critical' ? 'in-progress' : 'pending'); ?>"><?php echo escape_output($citation['priority']); ?></span></td>
						<td><span class="status-badge <?php echo strtolower($citation['status']); ?>"><?php echo escape_output($citation['status']); ?></span></td>
						<td><a href="<?php echo escape_output($citation['url']); ?>" target="_blank" class="btn btn-small btn-secondary">Visit</a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<!-- Service Pages Status -->
		<div class="card">
			<h2>Service Pages Deployment</h2>
			<table class="table">
				<thead>
					<tr>
						<th>Service Page</th>
						<th>URL</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>CCTV Systems in San Mateo</td>
						<td>/services/cctv-san-mateo.php</td>
						<td><span class="status-badge completed">Live</span></td>
						<td><a href="/services/cctv-san-mateo.php" target="_blank" class="btn btn-small btn-secondary">View</a></td>
					</tr>
					<tr>
						<td>Network Solutions</td>
						<td>/services/network-solutions.php</td>
						<td><span class="status-badge completed">Live</span></td>
						<td><a href="/services/network-solutions.php" target="_blank" class="btn btn-small btn-secondary">View</a></td>
					</tr>
					<tr>
						<td>Computer Sales & Support</td>
						<td>/services/computer-sales-support.php</td>
						<td><span class="status-badge completed">Live</span></td>
						<td><a href="/services/computer-sales-support.php" target="_blank" class="btn btn-small btn-secondary">View</a></td>
					</tr>
					<tr>
						<td>IT Maintenance & Support</td>
						<td>/services/it-maintenance.php</td>
						<td><span class="status-badge completed">Live</span></td>
						<td><a href="/services/it-maintenance.php" target="_blank" class="btn btn-small btn-secondary">View</a></td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Local Keywords Coverage -->
		<div class="card">
			<h2>Local Keywords (<?php echo count($local_keywords); ?> Total)</h2>
			<p style="color: #666; margin: 10px 0; font-size: 14px;">Keywords being targeted across service pages and content.</p>
			<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
				<?php foreach (array_slice($local_keywords, 0, 15) as $keyword): ?>
				<div style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 13px; border-left: 3px solid #ff6600;">
					<?php echo escape_output($keyword); ?>
				</div>
				<?php endforeach; ?>
			</div>
			<p style="margin-top: 15px; color: #999; font-size: 12px;">Showing 15 of <?php echo count($local_keywords); ?> keywords. <a href="#" style="color: #ff6600;">View all</a></p>
		</div>

		<!-- Action Items -->
		<div class="grid">
			<div class="card">
				<h2>Quick Actions</h2>
				<div class="action-buttons" style="flex-direction: column; gap: 8px;">
					<button class="btn btn-primary" style="width: 100%; text-align: left;">+ Add Review Request</button>
					<button class="btn btn-primary" style="width: 100%; text-align: left;">+ Add Citation</button>
					<button class="btn btn-primary" style="width: 100%; text-align: left;">📊 Generate SEO Report</button>
					<button class="btn btn-primary" style="width: 100%; text-align: left;">✓ Validate All Schema</button>
				</div>
			</div>

			<div class="card">
				<h2>Maintenance Tasks</h2>
				<ul class="checklist">
					<li>Update Google Business Profile photos</li>
					<li>Post weekly Google Business updates</li>
					<li class="pending">Submit citations to Yellow Pages</li>
					<li class="pending">Request 5 new reviews</li>
					<li>Check local keyword rankings</li>
					<li>Review NAP consistency</li>
				</ul>
			</div>
		</div>

		<!-- Help & Documentation -->
		<div class="card">
			<h2>Documentation & Resources</h2>
			<p style="margin-bottom: 15px;">Learn more about local SEO and how to manage your online presence.</p>
			<ul style="list-style: none;">
				<li style="padding: 8px 0;"><a href="LOCAL_SEO_SETUP.md" style="color: #0066cc;">📖 Local SEO Setup Guide</a></li>
				<li style="padding: 8px 0;"><a href="KEYWORD_STRATEGY.md" style="color: #0066cc;">🔍 Keyword Strategy Document</a></li>
				<li style="padding: 8px 0;"><a href="CITATION_SUBMISSION.csv" style="color: #0066cc;">📋 Citation Submission List</a></li>
				<li style="padding: 8px 0;"><a href="REVIEW_WORKFLOW.md" style="color: #0066cc;">⭐ Review Request Workflow</a></li>
				<li style="padding: 8px 0;"><a href="https://search.google.com/test/rich-results" target="_blank" style="color: #0066cc;">🔗 Google Rich Results Test</a></li>
			</ul>
		</div>
	</div>

	<footer>
		<p>Local SEO Dashboard - Last Updated: <?php echo date('Y-m-d H:i:s'); ?></p>
		<p>For security, always access this page with your admin key: ?key=ASMRCCTVTRADING2026</p>
	</footer>
</body>
</html>
