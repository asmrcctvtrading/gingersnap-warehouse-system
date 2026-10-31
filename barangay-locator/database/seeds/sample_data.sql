-- SAMPLE / DEMONSTRATION DATA — ALL FICTIONAL.
-- Every row has is_sample = 1. These are NOT real establishments, addresses, phone numbers or coordinates.
-- Remove with:  DELETE FROM establishments WHERE is_sample = 1;  DELETE FROM barangays WHERE is_sample = 1;
-- (admin dashboard: Settings → "Remove sample data")
-- Requires seeds/base.sql to have been loaded first.

SET NAMES utf8mb4;

INSERT INTO barangays (name, municipality, province, region, center_lat, center_lng, default_zoom,
                       hall_address, hall_phone, is_active, is_sample)
VALUES ('Barangay Sampaguita (SAMPLE)', 'Sample City', 'Sample Province', 'Sample Region',
        14.6000000, 121.0000000, 17, 'Sample Barangay Hall, Sample St.', '(000) 000-0000', 1, 1);

SET @b := (SELECT barangay_id FROM barangays WHERE name = 'Barangay Sampaguita (SAMPLE)' AND is_sample = 1 LIMIT 1);

-- name, category slug, address, lat, lng, coords_verified, classification, status, hours
-- Coordinates below are PLACEHOLDERS near the sample centre, only to exercise the map. They are not real places.
INSERT INTO establishments
  (barangay_id, name, description, primary_category_id, street_address, landmark, zone,
   latitude, longitude, coords_verified, coords_source, classification, status, data_source, last_verified_at, is_sample)
VALUES
 (@b, 'Sample Barangay Hall', 'Fictional barangay hall for demonstration.',
   (SELECT category_id FROM categories WHERE slug='barangay-office'), 'Sample St.', 'Beside the sample plaza', 'Zone 1',
   14.6000000, 121.0000000, 1, 'SAMPLE placeholder', 'public', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Health Center', 'Fictional health center for demonstration.',
   (SELECT category_id FROM categories WHERE slug='health-clinic'), '1 Sample Ave.', 'Near the sample barangay hall', 'Zone 1',
   14.6006000, 121.0008000, 1, 'SAMPLE placeholder', 'public', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Elementary School', 'Fictional school for demonstration.',
   (SELECT category_id FROM categories WHERE slug='school'), '2 Sample Ave.', 'Across the sample chapel', 'Zone 2',
   14.5992000, 120.9990000, 1, 'SAMPLE placeholder', 'public', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Police Assistance Desk', 'Fictional public-safety desk. Emergency flag intentionally NOT verified, to test the rule.',
   (SELECT category_id FROM categories WHERE slug='police'), '3 Sample St.', NULL, 'Zone 1',
   14.6003000, 121.0012000, 1, 'SAMPLE placeholder', 'public', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Chapel', 'Fictional place of worship.',
   (SELECT category_id FROM categories WHERE slug='worship'), '4 Sample St.', NULL, 'Zone 2',
   14.5990000, 120.9996000, 1, 'SAMPLE placeholder', 'private', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Mini Grocery', 'Fictional store.',
   (SELECT category_id FROM categories WHERE slug='grocery'), '5 Sample St.', 'Corner of Sample St. and Sample Ave.', 'Zone 3',
   14.6011000, 120.9985000, 1, 'SAMPLE placeholder', 'private', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Water Refilling', 'Fictional refilling station.',
   (SELECT category_id FROM categories WHERE slug='water-refill'), '6 Sample St.', NULL, 'Zone 3',
   14.6015000, 120.9992000, 1, 'SAMPLE placeholder', 'private', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Karinderya', 'Fictional eatery.',
   (SELECT category_id FROM categories WHERE slug='food'), '7 Sample St.', NULL, 'Zone 2',
   14.5997000, 121.0015000, 1, 'SAMPLE placeholder', 'private', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Repair Shop (new submission)', 'Pending verification, coordinates NOT verified: listed but not plotted.',
   (SELECT category_id FROM categories WHERE slug='repair'), '8 Sample St.', NULL, 'Zone 3',
   14.6020000, 121.0020000, 0, NULL, 'private', 'pending_verification', 'Resident submission (demo)', NULL, 1),
 (@b, 'Sample Pharmacy (no coordinates)', 'Verified record with no coordinates: listed, never plotted.',
   (SELECT category_id FROM categories WHERE slug='hospital-pharmacy'), '9 Sample St.', NULL, 'Zone 1',
   NULL, NULL, 0, NULL, 'private', 'verified', 'Demo data', NOW(), 1),
 (@b, 'Sample Bakery (needs review)', 'Flagged for review: hidden from the public.',
   (SELECT category_id FROM categories WHERE slug='retail'), '10 Sample St.', NULL, 'Zone 2',
   14.5985000, 121.0005000, 1, 'SAMPLE placeholder', 'private', 'needs_review', 'Demo data', NOW(), 1),
 (@b, 'Sample Closed Shop (inactive)', 'Deactivated record: hidden from the public.',
   (SELECT category_id FROM categories WHERE slug='retail'), '11 Sample St.', NULL, 'Zone 2',
   14.5988000, 121.0009000, 1, 'SAMPLE placeholder', 'private', 'inactive', 'Demo data', NOW(), 1);

-- Facility flags (note: police desk is_emergency = 1 but NOT verified, so the API must not expose it as emergency).
INSERT INTO facilities (establishment_id, facility_type, is_emergency, emergency_verified_at, services_offered)
SELECT establishment_id, 'government', 0, NULL, 'Certificates, community assistance (sample)'
  FROM establishments WHERE name = 'Sample Barangay Hall' AND is_sample = 1;
INSERT INTO facilities (establishment_id, facility_type, is_emergency, emergency_verified_at, services_offered)
SELECT establishment_id, 'health', 0, NULL, 'Consultations, immunization (sample)'
  FROM establishments WHERE name = 'Sample Health Center' AND is_sample = 1;
INSERT INTO facilities (establishment_id, facility_type, is_emergency, emergency_verified_at, services_offered)
SELECT establishment_id, 'safety', 1, NULL, 'Blotter, assistance (sample)'
  FROM establishments WHERE name = 'Sample Police Assistance Desk' AND is_sample = 1;

-- Contacts: obviously fake numbers.
INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value)
SELECT establishment_id, 'phone', '(000) 000-0001' FROM establishments WHERE name = 'Sample Barangay Hall' AND is_sample = 1;
INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value)
SELECT establishment_id, 'phone', '(000) 000-0002' FROM establishments WHERE name = 'Sample Health Center' AND is_sample = 1;

-- Hours for two records (Mon–Fri 08:00–17:00 for the hall; 24h flag for nothing).
INSERT INTO operating_hours (establishment_id, day_of_week, opens_at, closes_at, is_closed)
SELECT e.establishment_id, d.dow, '08:00:00', '17:00:00', 0
  FROM establishments e
  JOIN (SELECT 1 AS dow UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
 WHERE e.name = 'Sample Barangay Hall' AND e.is_sample = 1;
INSERT INTO operating_hours (establishment_id, day_of_week, opens_at, closes_at, is_closed)
SELECT e.establishment_id, d.dow, NULL, NULL, 1
  FROM establishments e
  JOIN (SELECT 0 AS dow UNION SELECT 6) d
 WHERE e.name = 'Sample Barangay Hall' AND e.is_sample = 1;
