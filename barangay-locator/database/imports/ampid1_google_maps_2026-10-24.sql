-- Ampid 1, San Mateo, Rizal: 5 places hand-collected by staff from Google Maps on 2026-10-24 (source: Map.csv).
-- Run in phpMyAdmin -> SQL, on the barangay-locator database. Safe to re-run: a place already present
-- (same name in the same barangay) is skipped.
-- All rows are 'pending_verification' with coords_verified = 0 until staff confirm them on site.
-- Phone numbers are stored exactly as they appear in Map.csv (leading zeros / area codes were not in the file).

SET NAMES utf8mb4;

SET @b := (SELECT barangay_id FROM barangays WHERE name LIKE '%Ampid%' ORDER BY barangay_id LIMIT 1);
SET @src := 'Collected by staff from Google Maps (manual), 2026-10-24';
SET @csrc := 'Google Maps pin, copied by staff';

-- 1. Barangay Hall
INSERT INTO establishments (barangay_id, name, primary_category_id, street_address, latitude, longitude,
                            coords_verified, coords_source, classification, status, data_source)
SELECT @b, 'Brgy Ampid-1, Barangay Hall', (SELECT category_id FROM categories WHERE slug = 'barangay-office'),
       'J.F Diaz Ave, Brgy. Ampid 1, San Mateo, Rizal', 14.6813169, 121.1193773,
       0, @csrc, 'public', 'pending_verification', @src
WHERE NOT EXISTS (SELECT 1 FROM establishments WHERE barangay_id = @b AND name = 'Brgy Ampid-1, Barangay Hall');

-- 2. Mercury Drug Store
INSERT INTO establishments (barangay_id, name, primary_category_id, street_address, latitude, longitude,
                            coords_verified, coords_source, classification, status, data_source)
SELECT @b, 'Mercury Drug Store', (SELECT category_id FROM categories WHERE slug = 'hospital-pharmacy'),
       '0196 Gen. Luna Ave, Brgy. Ampid 1, San Mateo, Rizal', 14.6821169, 121.1157434,
       0, @csrc, 'private', 'pending_verification', @src
WHERE NOT EXISTS (SELECT 1 FROM establishments WHERE barangay_id = @b AND name = 'Mercury Drug Store');

-- 3. Padre Pio Maternity and Medical Hospital Inc
INSERT INTO establishments (barangay_id, name, primary_category_id, street_address, latitude, longitude,
                            coords_verified, coords_source, classification, status, data_source)
SELECT @b, 'Padre Pio Maternity and Medical Hospital Inc', (SELECT category_id FROM categories WHERE slug = 'hospital-pharmacy'),
       'Gen. Luna Ave, Brgy. Ampid 1, San Mateo, Rizal', 14.6811408, 121.1141730,
       0, @csrc, 'private', 'pending_verification', @src
WHERE NOT EXISTS (SELECT 1 FROM establishments WHERE barangay_id = @b AND name = 'Padre Pio Maternity and Medical Hospital Inc');

-- 4. SM City San Mateo (Map.csv category "Shopping Mall" has no exact match; filed under Retail shops)
INSERT INTO establishments (barangay_id, name, primary_category_id, street_address, latitude, longitude,
                            coords_verified, coords_source, classification, status, data_source)
SELECT @b, 'SM City San Mateo', (SELECT category_id FROM categories WHERE slug = 'retail'),
       'Gen. Luna Ave, Brgy. Ampid 1, San Mateo, Rizal', 14.6802675, 121.1140668,
       0, @csrc, 'private', 'pending_verification', @src
WHERE NOT EXISTS (SELECT 1 FROM establishments WHERE barangay_id = @b AND name = 'SM City San Mateo');

-- 5. Ciudad Christhia Resort (Map.csv notes also say "9 Weaves Resort"; kept in the description)
INSERT INTO establishments (barangay_id, name, description, primary_category_id, street_address, latitude, longitude,
                            coords_verified, coords_source, classification, status, data_source)
SELECT @b, 'Ciudad Christhia Resort', 'Also listed as: 9 Weaves Resort',
       (SELECT category_id FROM categories WHERE slug = 'other'),
       'Ampid 1, Carrieland Country homes, II Earth, San Mateo, Rizal', 14.6832103, 121.1129136,
       0, @csrc, 'private', 'pending_verification', @src
WHERE NOT EXISTS (SELECT 1 FROM establishments WHERE barangay_id = @b AND name = 'Ciudad Christhia Resort');

-- Phone contacts (public). Skipped if the establishment already has that number.
INSERT INTO establishment_contacts (establishment_id, contact_type, contact_value, is_public)
SELECT e.establishment_id, 'phone', v.phone, 1
FROM (
  SELECT 'Brgy Ampid-1, Barangay Hall' AS n, '282791068' AS phone
  UNION ALL SELECT 'Mercury Drug Store', '289971572'
  UNION ALL SELECT 'Padre Pio Maternity and Medical Hospital Inc', '286563475'
  UNION ALL SELECT 'SM City San Mateo', '6492464'
  UNION ALL SELECT 'Ciudad Christhia Resort', '9975945'
) v
JOIN establishments e ON e.barangay_id = @b AND e.name = v.n
WHERE NOT EXISTS (SELECT 1 FROM establishment_contacts c
                  WHERE c.establishment_id = e.establishment_id AND c.contact_value = v.phone);

-- Check: should list 5 rows.
SELECT establishment_id, name, status, coords_verified, latitude, longitude
FROM establishments WHERE barangay_id = @b AND data_source = @src ORDER BY establishment_id;
