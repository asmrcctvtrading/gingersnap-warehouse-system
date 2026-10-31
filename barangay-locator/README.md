# Barangay Establishment Locator

A lightweight web app that helps residents and visitors find establishments and services in a Philippine barangay, with search, category browsing, a Leaflet map, straight-line "nearby" results, external-app directions, Tagalog/English, and a kiosk mode. Staff verify records before they are shown as verified.

**Stack:** PHP 8+, MySQL 8 / MariaDB 10.4+, PDO, sessions, vanilla JS, Leaflet. No Composer, Node or build step is needed on the server.

> **Build status (read this first):** all code and documents were written without access to PHP or MySQL, so **nothing has been executed yet**. See [docs/TEST_RESULTS.md](docs/TEST_RESULTS.md) and [docs/KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md). Do the smoke test below before putting real data in.

## Deploy on cPanel (same approach as the warehouse system)

1. **Create the database.** cPanel → *MySQL® Databases*: create a database, a user, and add the user to the database with ALL PRIVILEGES. Note the full prefixed names (e.g. `cpuser_brgy`).
2. **Upload.** Zip the `barangay-locator` folder contents and upload to `public_html/barangay/` (File Manager → Upload → Extract). Hidden files (`.htaccess`) must be included.
3. **PHP version.** cPanel → *MultiPHP Manager*: choose PHP 8.0 or newer for the domain. Make sure `pdo_mysql`, `mbstring` and `fileinfo` are enabled (*Select PHP Version → Extensions*).
4. **Permissions.** `app/`, `storage/` and `uploads/` must be writable by PHP (usually 755 on cPanel; 775 if the installer complains).
5. **Run the installer immediately:** open `https://your-domain/barangay/install/install.php`. Enter the DB host (`localhost`), DB name/user/password, base URL path (`/barangay`), and the first admin's name and password. Tick *sample data* only to try the system.
6. **Delete the `install/` folder** when it says done (File Manager → delete). The installer locks itself with `storage/installed.lock`, but the folder should not stay.
7. **Log in** at `/barangay/admin/login.php`. Add your real barangay under *Barangays*, then remove sample data in *Settings* if you loaded it.
8. **Use HTTPS.** Enable AutoSSL in cPanel. Session cookies are marked Secure when the site is served over HTTPS. Geolocation in browsers also needs HTTPS.

If URL rewriting is unavailable, the API still works as `api/index.php?r=establishments`.

## Smoke test after deploying (please do this and record it)

- Home, Search, Categories, Services, Map, About load without errors.
- Search "sample" returns demonstration records, each labelled as sample.
- Open an establishment; a pending record shows "Awaiting verification".
- Map: only records with checked coordinates are plotted; the OSM tile warning is shown until you set a production tile source.
- Staff: log in, verify a record (evidence required), approve a public submission, export CSV, view audit log.
- Log out; confirm `/barangay/app/config.local.php`, `/barangay/database/schema.sql` and `/barangay/storage/` return 403/404.

## Running tests (needs PHP CLI)

```
php tests/run_tests.php
php tests/db_tests.php localhost cpuser_brgy_test dbuser dbpass   # throw-away DB, name must contain "test"
```

## Local use on Windows

See [docs/INSTALLATION_WINDOWS.md](docs/INSTALLATION_WINDOWS.md) (XAMPP).

## Documents

| Document | Contents |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Design decisions, data flow |
| [docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md) | Phases and status |
| [docs/API_SPECIFICATION.md](docs/API_SPECIFICATION.md) | JSON endpoints |
| [docs/DATABASE.md](docs/DATABASE.md) | Tables and rules |
| [docs/MAPPING_AND_GIS.md](docs/MAPPING_AND_GIS.md) | Tiles, coordinates, boundaries, directions |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Staff manual |
| [docs/DATA_VERIFICATION.md](docs/DATA_VERIFICATION.md) | What "verified" means here |
| [docs/SECURITY_REVIEW.md](docs/SECURITY_REVIEW.md) | Controls and open risks |
| [docs/TEST_PLAN.md](docs/TEST_PLAN.md), [docs/TEST_RESULTS.md](docs/TEST_RESULTS.md) | Tests and their real status |
| [docs/KIOSK_AND_OPERATIONS.md](docs/KIOSK_AND_OPERATIONS.md) | Kiosk startup, backups |
| [docs/KNOWN_ISSUES.md](docs/KNOWN_ISSUES.md) | Honest list of gaps |
| [docs/FINAL_REPORT.md](docs/FINAL_REPORT.md) | What was delivered vs. verified |

## Data you must supply

The app ships **no real establishment data, coordinates, phone numbers or barangay boundaries**. The sample data is fictional ("Barangay Sampaguita (SAMPLE)") and every row is flagged `is_sample`. Collect real records with the barangay, verify them (see DATA_VERIFICATION), and only then mark them verified.
