# Database

MySQL 8 / MariaDB 10.4+, InnoDB, utf8mb4. Source of truth: [database/schema.sql](../database/schema.sql). Seeds: `database/seeds/base.sql` (roles, 20 categories, default settings; idempotent) and `database/seeds/sample_data.sql` (fictional, optional). Future changes go in `database/migrations/` as numbered SQL files.

| Table | Purpose |
|---|---|
| roles | admin / verifier / encoder |
| barangays | name, municipality, province, centre, zoom, optional boundary GeoJSON + source + verified flag, `is_sample` |
| users | staff accounts: `password_hash`, role, optional barangay scope, failed-login counter, lockout time |
| categories | 20 defaults: EN/TL names, icon, colour, essential flag, sort order, active |
| establishments | the records; status, classification, coordinates + `coords_verified`, verification timestamps, `is_sample`; CHECK constraints on status and coordinate ranges |
| establishment_categories | additional categories per establishment |
| establishment_contacts | phone/email/etc.; `is_public` controls exposure |
| operating_hours | per weekday; closed / 24h flags |
| facilities | service details, emergency flag, `emergency_verified_at/by` |
| verification_records | each verification action with evidence text and user |
| establishment_change_requests | public submissions (JSON payload, hashed IP) awaiting review |
| audit_logs | who/what/when/IP for staff actions and failed logins |
| app_settings | key/value configuration (tiles, visibility toggles, kiosk, limits) |

## Rules enforced in code (not only in SQL)
Status values, visibility, coordinate plotting, emergency exposure and contact exposure are decided in `app/lib/repo/establishments.php`. State changes go through `est_apply_action()` in `app/lib/repo/admin_est.php`.

## Backups
Export with cPanel → Backup (partial backup: MySQL database) or phpMyAdmin → Export (SQL). Keep copies off the server. `uploads/` holds photos and should be backed up too.

## Notes
Application uses prepared statements only. A few statements use double-quoted literals; avoid running MySQL with `ANSI_QUOTES` (see KNOWN_ISSUES #12).
