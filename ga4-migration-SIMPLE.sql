-- ============================================================================
-- GA4 MIGRATION - SIMPLE VERSION
-- ============================================================================
-- Minimal, idempotent migration for GA4 tracking and lead management
-- Safe to execute multiple times - uses IF NOT EXISTS on all tables
-- No complex indexes or foreign keys - simple data types only
-- ============================================================================

-- TABLE 1: LEADS
-- Stores lead information for GA4 conversion tracking
-- ============================================================================
CREATE TABLE IF NOT EXISTS leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    service_type VARCHAR(100),
    lead_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 2: GA4_EVENTS_LOG
-- Stores raw GA4 event data for tracking and analysis
-- ============================================================================
CREATE TABLE IF NOT EXISTS ga4_events_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_name VARCHAR(100) NOT NULL,
    event_data VARCHAR(500),
    user_id INT,
    session_id VARCHAR(100),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 3: CONTACT_LEADS
-- Links existing contacts to leads (bridge table)
-- ============================================================================
CREATE TABLE IF NOT EXISTS contact_leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contact_id INT,
    lead_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- VERIFICATION QUERY
-- ============================================================================
-- Run this to confirm all 3 tables were created successfully
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME IN ('leads', 'ga4_events_log', 'contact_leads');
