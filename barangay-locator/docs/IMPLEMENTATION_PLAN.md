# Implementation Plan and Status

"Written" means files exist. "Executed" means it was run and the result observed. Nothing is executed yet.

| Phase | Scope | Written | Executed |
|---|---|---|---|
| 0 Planning | ARCHITECTURE, AGENT_TASKS, plan, API spec, known issues | Yes | n/a |
| 1 Foundation | schema, seeds (20 categories, settings), config, libs, installer, `.htaccess` hardening | Yes | No |
| 2 Public experience | home, search, categories, services, establishment page, about, report form, i18n EN/TL, kiosk mode | Yes | No |
| 3 Mapping | map page (Leaflet), markers, nearby, directions hand-off, boundary support | Yes | No |
| 4 Admin | login, dashboard, establishments + verification workflow, requests, categories, barangays, users, audit, export, settings | Yes | No |
| 5 Security/QA | security review, test plan, unit and DB tests | Yes (tests unrun) | No |
| 6 Documentation | README, guides, reports | Yes | n/a |
| 7 Deployment | cPanel steps, Windows/XAMPP notes, backups | Steps written | Not deployed |

## Next steps for whoever has PHP/MySQL
1. `php -l` every file; fix typos.
2. Run `tests/run_tests.php` and `tests/db_tests.php`; record in TEST_RESULTS.md.
3. Deploy to a staging folder on cPanel, run the smoke test in README.
4. Vendor Leaflet locally; choose a production tile provider.
5. Collect real barangay data with the barangay's authority; verify per DATA_VERIFICATION.md.

## Backlog (not built)
OSRM/other routing, "open now" with holidays, public correction auto-apply (deliberately not built), e-mail password reset, image resizing, per-barangay public subdomains, offline PWA.
