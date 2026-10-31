# Implementation plan and status

Status legend: DONE-WRITTEN = file exists, **not executed**. NOT STARTED.

| # | Milestone | Owner (agent role) | Files | Status |
|---|---|---|---|---|
| 0 | Architecture, schema draft, metrics, config/htaccess | Lead architect | docs/*, database/schema.sql, app/config.sample.php, .htaccess | DONE-WRITTEN (awaiting approval) |
| 1 | Schema approval + seeds (roles, task types, sample data flagged) | Database | database/seeds/*, migrations/ | NOT STARTED |
| 2 | Core libs: bootstrap, db, csrf, validate, audit, http, auth + login/logout/change password | Auth/Security | app/bootstrap.php, app/lib/{db,csrf,validate,audit,auth,http}.php, app/views/layout.php, login.php, logout.php, change_password.php, index.php, assets/css/app.css, tests/run_tests.php | DONE-WRITTEN (not executed; written inline, no independent review). User admin screen (admin/users.php) NOT STARTED |
| 3 | Installer | Deployment | install/install.php, database/seeds/base.sql | DONE-WRITTEN (not executed) |
| 4 | Employees, tasks, picking and packing screens, workflow service, exceptions | Picking/Packing | employees/*, tasks/*, picking/*, packing/*, app/lib/repo/{tasks,employees}.php, app/lib/service/task_workflow.php | NOT STARTED |
| 5 | CSV import: template, preview, commit, rejection report | Import/Reporting | imports/*, app/lib/csv.php, app/lib/service/import_service.php | NOT STARTED |
| 6 | Dashboard and reports, CSV export | Import/Reporting | reports/*, app/lib/service/metrics.php | NOT STARTED |
| 7 | Admin config: warehouses, task types, settings, audit viewer | Auth + Picking | admin/* | NOT STARTED |
| 8 | Tests (PHP CLI scripts), security review | QA | tests/*, docs/SECURITY_REVIEW.md | NOT STARTED |
| 9 | Deployment guide, backup/recovery, admin + user manuals | Deployment/Docs | docs/* | NOT STARTED |
| 10 | Deploy to Z.com subfolder and smoke test | User + Deployment | - | BLOCKED until hosting facts verified and user authorises |

Rules: file ownership as above; nobody changes `schema.sql` or `app/lib/*` interfaces without architect approval; no DROP in shipped SQL; no remote action without the user's go-ahead.
