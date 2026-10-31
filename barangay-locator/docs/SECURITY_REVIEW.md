# Security Review

Method: author's read-through of the code against the checklist below. **No scanner, penetration test or runtime test has been run.** Treat this as a design review, not an audit.

## Controls implemented
| Area | Control |
|---|---|
| SQL injection | PDO with native prepared statements (`EMULATE_PREPARES=false`); limits/offsets are cast to int; LIKE input escaped |
| XSS | All output through `h()`; JS builds DOM with text nodes/escaped strings; CSP restricts scripts to self (+ unpkg fallback) |
| CSRF | Token per session on all POST forms and on `/api/reports` (header or body) |
| Authentication | `password_hash()`; 10+ char policy; login lockout (5 failures → 15 min); per-IP brake (20 failures / 10 min); session id regenerated on login; generic error message; comparable timing for unknown users |
| Sessions | HttpOnly, SameSite=Lax, Secure when HTTPS, dedicated cookie name |
| Authorisation | `requirePermission()` on every admin page; role map in `auth.php`; barangay scoping enforced in queries and edit pages |
| Uploads | allow-list jpg/png/webp, ≤ 2 MB, `finfo` MIME + `getimagesize`, random file names, no execution in `uploads/` |
| File exposure | `.htaccess` deny for `app/`, `storage/`, `database/`, `tests/`, `docs/` and `*.sql/.log/.lock/.md` |
| Secrets | DB credentials only in installer-generated `app/config.local.php` (not shipped, not committed) |
| Headers | CSP, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy (geolocation self) |
| Abuse | honeypot, per-visitor daily cap, payload cap on public reports; submissions never auto-publish |
| Privacy | submitter IP stored only as salted hash; contacts hidden unless marked public; no location data stored |
| Export | CSV formula injection neutralised; export is permissioned and audited |
| Integrity | every verification/state change writes `verification_records` and `audit_logs` |

## Open risks
1. Untested code: logic or syntax errors may exist (KNOWN_ISSUES #1).
2. Installer is open until completed; delete `install/` after use.
3. CSP still permits `'unsafe-inline'` styles and unpkg scripts until Leaflet is vendored.
4. Rate limits depend on `REMOTE_ADDR`; behind a shared proxy they may be coarse.
5. No 2FA for staff; use strong passwords and HTTPS.
6. Sessions stored in PHP default files on shared hosting.
7. No malware scan of uploaded images beyond type checks.

## Recommended before go-live
Run all S-tests in TEST_PLAN.md, force HTTPS (cPanel "Force HTTPS Redirect"), vendor Leaflet, remove sample data, create individual staff accounts, enable cPanel backups.
