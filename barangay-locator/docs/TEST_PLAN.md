# Test Plan

Environments: cPanel (PHP 8.x, MySQL/MariaDB) and local XAMPP. Browsers: current Chrome/Edge on desktop, Chrome on Android, and the kiosk browser. Use a **throw-away database** for destructive tests.

## Automated (needs PHP CLI)
- U-01 `php tests/run_tests.php` — validators, SQL splitter (quotes, comments), Tagalog keys ⊆ English keys.
- D-01 `php tests/db_tests.php host dbname user pass` — loads schema + sample data, asserts: needs_review/inactive never public; pending hidden when setting off; unverified coordinates not exposed unless admin opts in; unverified emergency flag not exposed; markers equal plottable records.
- L-01 Lint: `Get-ChildItem -Recurse -Filter *.php | % { php -l $_.FullName }`.

## Manual functional (F)
| ID | Steps | Expected |
|---|---|---|
| F-01 | Open home without allowing location | Page works; no permission prompt |
| F-02 | Search by name, category, zone | Matching public records only |
| F-03 | Search for a needs_review sample record | Not found |
| F-04 | Open pending record | "Awaiting verification" label |
| F-05 | Map | Only checked coordinates plotted; tile warning visible |
| F-06 | Click "Use my location" then deny | Friendly message; list still works |
| F-07 | Directions page | Opens external app link; no claim of driving distance |
| F-08 | Nearby | Distances labelled straight-line |
| F-09 | Submit report with honeypot filled | Silently dropped / rejected, nothing stored |
| F-10 | Submit 6 reports from one IP (limit 5) | 6th refused |
| F-11 | `?kiosk=1`, wait idle time | Resets to start screen |
| F-12 | Language switch | UI strings change; missing Tagalog falls back to English |

## Staff workflow (W)
- W-01 Encoder creates a record → pending_verification; cannot verify.
- W-02 Verifier verifies without evidence → refused; with evidence → verified + audit entry.
- W-03 Encoder edits a verified record → returns to pending_verification.
- W-04 Changing coordinates clears `coords_verified`.
- W-05 Emergency flag verification needs evidence; unverified flag not shown publicly.
- W-06 Duplicate name in same barangay → warning that must be acknowledged.
- W-07 Approve "new" request → pending record, unchecked coords. Approve "correction" → record flagged needs_review.
- W-08 Barangay-scoped user cannot see or edit other barangays (try URL id tampering).
- W-09 CSV export: a name beginning with `=` is prefixed with `'`.
- W-10 Settings: http tile URL rejected; "Remove sample data" requires typed confirmation.

## Security (S)
- S-01 POST without CSRF token → rejected (all forms and report API).
- S-02 SQL injection strings in search, id, filters → no error, no leakage.
- S-03 XSS strings in names/descriptions → escaped everywhere (list, map popup, admin).
- S-04 Upload `.php` renamed `.jpg`, oversize file, SVG → rejected.
- S-05 Direct GET of `app/config.local.php`, `database/schema.sql`, `storage/installed.lock`, `tests/`, `docs/` → 403/404.
- S-06 Script execution inside `uploads/` → blocked.
- S-07 6 wrong passwords → account locked 15 min; 21 failures from one IP → brake.
- S-08 Session fixation: session id changes on login; logout destroys it.
- S-09 Role checks: encoder GET `admin/users.php` → 403; verifier cannot open settings.
- S-10 Installer re-run after completion → refused.

## Accessibility / usability
Keyboard-only navigation, visible focus, labels on all inputs, text contrast, touch targets ≥ 44 px, 320 px width without horizontal scroll, kiosk at 1080p.

Record outcomes in TEST_RESULTS.md.
