-- Base reference data (roles, categories, settings). NOT sample data: safe for production.
-- Uses INSERT IGNORE so re-running does not duplicate rows.

SET NAMES utf8mb4;

INSERT IGNORE INTO roles (role_key, label) VALUES
  ('admin',    'Administrator'),
  ('verifier', 'Verifier'),
  ('encoder',  'Encoder');

-- 20 default categories (spec section 4). is_essential = shown under "Essential services".
INSERT IGNORE INTO categories (slug, name_en, name_tl, icon, color, sort_order, is_essential) VALUES
  ('barangay-office',   'Barangay government offices',            'Tanggapan ng Barangay',          'landmark',  '#1d4ed8',  1, 1),
  ('city-gov-services', 'City or municipal government services',  'Serbisyo ng Lungsod/Munisipyo',  'building',  '#1e40af',  2, 1),
  ('health-clinic',     'Health centers and clinics',             'Health Center at Klinika',       'cross',     '#dc2626',  3, 1),
  ('hospital-pharmacy', 'Hospitals and pharmacies',               'Ospital at Botika',              'pill',      '#b91c1c',  4, 1),
  ('school',            'Schools and educational centers',        'Paaralan at Sentrong Pang-edukasyon', 'school', '#7c3aed',  5, 1),
  ('police',            'Police stations and public safety offices', 'Himpilan ng Pulisya',          'shield',    '#0f766e',  6, 1),
  ('fire-station',      'Fire stations',                          'Istasyon ng Bumbero',            'flame',     '#ea580c',  7, 1),
  ('emergency-facility','Emergency and disaster-response facilities', 'Pasilidad para sa Emerhensiya', 'alert', '#be123c',  8, 1),
  ('worship',           'Churches and places of worship',         'Simbahan at Lugar-sambahan',     'church',    '#6d28d9',  9, 0),
  ('grocery',           'Grocery stores and convenience stores',  'Grocery at Tindahan',            'cart',      '#16a34a', 10, 0),
  ('food',              'Restaurants and food establishments',    'Kainan',                         'utensils',  '#d97706', 11, 0),
  ('retail',            'Retail shops',                           'Mga Tindahan',                   'bag',       '#0891b2', 12, 0),
  ('bank-atm',          'Banks and ATMs',                         'Bangko at ATM',                  'bank',      '#047857', 13, 0),
  ('remittance',        'Remittance centers',                     'Remittance Center',              'cash',      '#15803d', 14, 0),
  ('transport',         'Transportation terminals and stops',     'Terminal at Sakayan',            'bus',       '#0369a1', 15, 0),
  ('repair',            'Repair shops and technical services',    'Talyer at Serbisyong Teknikal',  'wrench',    '#475569', 16, 0),
  ('telecom',           'Internet and telecommunications services','Internet at Telekomunikasyon',  'wifi',      '#4338ca', 17, 0),
  ('water-refill',      'Water-refilling stations',               'Water Refilling Station',        'droplet',   '#0284c7', 18, 0),
  ('funeral',           'Funeral services',                       'Punerarya',                      'flower',    '#57534e', 19, 0),
  ('other',             'Other local businesses and services',    'Iba pang Negosyo at Serbisyo',   'pin',       '#64748b', 20, 0);

-- Default settings. Tile and routing defaults are DEVELOPMENT values; see docs/MAPPING_AND_GIS.md.
INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
  ('site_title',            'Barangay Establishment Locator'),
  ('default_barangay_id',   ''),
  ('default_language',      'en'),
  ('public_show_unverified','1'),
  ('plot_unverified_coords','0'),
  ('kiosk_idle_seconds',    '90'),
  ('tile_url',              'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
  ('tile_attribution',      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
  ('tile_is_production_ok', '0'),
  ('routing_provider',      'external_link'),
  ('routing_url',           ''),
  ('external_nav_url',      'https://www.google.com/maps/dir/?api=1&origin={from_lat}%2C{from_lng}&destination={to_lat}%2C{to_lng}'),
  ('reports_per_ip_per_day','5'),
  ('show_sample_banner',    '1');
