-- Local SEO Review Tracking Database Schema
-- ASMR CCTV Trading
-- Last Updated: 2026-10-28

-- Table for tracking review requests sent to customers
CREATE TABLE IF NOT EXISTS `local_seo_review_requests` (
	`id` INT PRIMARY KEY AUTO_INCREMENT,
	`customer_email` VARCHAR(255) NOT NULL,
	`customer_name` VARCHAR(255) NOT NULL,
	`service_type` VARCHAR(100),
	`phone` VARCHAR(20),
	`request_date` DATETIME NOT NULL,
	`scheduled_date` DATETIME,
	`sent_date` DATETIME,
	`status` ENUM('pending', 'sent', 'completed', 'failed') DEFAULT 'pending',
	`attempts` INT DEFAULT 0,
	`max_attempts` INT DEFAULT 3,
	`notes` TEXT,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `idx_email` (`customer_email`),
	INDEX `idx_status` (`status`),
	INDEX `idx_scheduled` (`scheduled_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking received reviews
CREATE TABLE IF NOT EXISTS `local_seo_reviews` (
	`id` INT PRIMARY KEY AUTO_INCREMENT,
	`review_id` VARCHAR(100),
	`reviewer_name` VARCHAR(255),
	`reviewer_email` VARCHAR(255),
	`service_type` VARCHAR(100),
	`rating` INT CHECK (rating >= 1 AND rating <= 5),
	`review_text` TEXT,
	`source` ENUM('google', 'facebook', 'justdial', 'other') DEFAULT 'google',
	`review_date` DATETIME,
	`received_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`status` ENUM('pending_approval', 'approved', 'flagged') DEFAULT 'pending_approval',
	`helpful_count` INT DEFAULT 0,
	`response` TEXT,
	`response_date` DATETIME,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `idx_rating` (`rating`),
	INDEX `idx_source` (`source`),
	INDEX `idx_review_date` (`review_date`),
	UNIQUE KEY `unique_review` (`review_id`, `source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking local citations
CREATE TABLE IF NOT EXISTS `local_seo_citations` (
	`id` INT PRIMARY KEY AUTO_INCREMENT,
	`directory_name` VARCHAR(255) NOT NULL,
	`directory_url` VARCHAR(500),
	`category` VARCHAR(100),
	`listing_url` VARCHAR(500),
	`nap_status` ENUM('consistent', 'inconsistent', 'missing') DEFAULT 'missing',
	`status` ENUM('listed', 'pending', 'not_found', 'error') DEFAULT 'pending',
	`last_verified` DATETIME,
	`submission_date` DATETIME,
	`priority` ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
	`notes` TEXT,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `idx_status` (`status`),
	INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for tracking SEO tasks and checkpoints
CREATE TABLE IF NOT EXISTS `local_seo_tasks` (
	`id` INT PRIMARY KEY AUTO_INCREMENT,
	`task_name` VARCHAR(255) NOT NULL,
	`description` TEXT,
	`task_type` ENUM('schema', 'citation', 'review', 'content', 'technical', 'other') DEFAULT 'other',
	`status` ENUM('pending', 'in_progress', 'completed', 'blocked') DEFAULT 'pending',
	`priority` ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
	`assigned_to` VARCHAR(100),
	`due_date` DATETIME,
	`completed_date` DATETIME,
	`notes` TEXT,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `idx_status` (`status`),
	INDEX `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for keyword rankings tracking (optional)
CREATE TABLE IF NOT EXISTS `local_seo_keywords` (
	`id` INT PRIMARY KEY AUTO_INCREMENT,
	`keyword` VARCHAR(255) NOT NULL,
	`service_type` VARCHAR(100),
	`search_engine` ENUM('google', 'bing', 'yahoo') DEFAULT 'google',
	`location` VARCHAR(100) DEFAULT 'San Mateo',
	`current_rank` INT,
	`previous_rank` INT,
	`search_volume` INT,
	`difficulty` VARCHAR(20),
	`url` VARCHAR(500),
	`tracked_from` DATE,
	`last_checked` DATETIME,
	`notes` TEXT,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX `idx_keyword` (`keyword`),
	INDEX `idx_rank` (`current_rank`),
	INDEX `idx_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for citations
INSERT INTO `local_seo_citations` (directory_name, directory_url, category, priority) VALUES
('Google Business Profile', 'https://business.google.com/', 'Search Engine', 'critical'),
('Yellow Pages Philippines', 'https://www.yellowpages.ph/', 'Business Directory', 'high'),
('Business.com Philippines', 'https://www.business.com/', 'Business Directory', 'high'),
('Chamber of Commerce Philippines', 'https://www.philcham.org/', 'Chamber', 'medium'),
('Rizal Provincial Directory', 'https://rizal.gov.ph/', 'Government', 'medium'),
('JustDial Philippines', 'https://www.justdial.com/', 'Business Directory', 'medium')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Sample data for initial tasks
INSERT INTO `local_seo_tasks` (task_name, description, task_type, priority, status) VALUES
('Validate all schema markup', 'Test all JSON-LD schemas with Google Rich Results tool', 'schema', 'critical', 'completed'),
('Submit to Google Business Profile', 'Ensure listing is complete and verified', 'citation', 'critical', 'completed'),
('Deploy service pages', 'Deploy 4 local service pages to production', 'content', 'high', 'completed'),
('Set up review request system', 'Integrate review request emails with contact form', 'review', 'high', 'pending'),
('Build local citation list', 'Compile and submit to major directories', 'citation', 'high', 'in_progress'),
('Create local keyword strategy', 'Research and document 20+ local keywords', 'content', 'medium', 'pending'),
('Set up SEO dashboard', 'Deploy admin dashboard for monitoring', 'technical', 'medium', 'pending'),
('Request initial reviews', 'Send review requests to existing customers', 'review', 'medium', 'pending')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Queries for common tasks:

-- Get review statistics
-- SELECT rating, COUNT(*) as count FROM local_seo_reviews WHERE status = 'approved' GROUP BY rating ORDER BY rating DESC;

-- Get pending review requests
-- SELECT customer_name, customer_email, service_type, scheduled_date FROM local_seo_review_requests WHERE status = 'pending' AND scheduled_date <= NOW() ORDER BY scheduled_date ASC LIMIT 10;

-- Check citation consistency
-- SELECT directory_name, nap_status, status FROM local_seo_citations ORDER BY priority DESC;

-- Get pending tasks
-- SELECT task_name, priority, due_date FROM local_seo_tasks WHERE status IN ('pending', 'in_progress') ORDER BY priority DESC, due_date ASC;
