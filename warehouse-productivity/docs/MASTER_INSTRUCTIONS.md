# Warehouse Productivity Monitoring System (WPMS) — Master Instructions

Source: pasted by the user on 2026-10-24. This is the approved spec; do not weaken it.

## Overview
PHP + MySQL system to monitor individual warehouse employees' picking and packing, measure task completion times, and generate productivity reports. Complements the existing Unicommerce Uniware platform but runs independently; do NOT assume Uniware API access unless verified and explicitly authorized.

- Hosting: existing Z.com cPanel account. Proposed dir `public_html/warehouse-productivity/`, proposed URL `https://asmrcctvtechnologies.com/warehouse-productivity/` (both must be verified against the real hosting config before deployment).
- Stack: PHP, MySQL/MariaDB, HTML, CSS, JavaScript, Bootstrap.
- Approach: multiple specialized agents coordinated by a lead architect (or clearly separated stages if agents are unavailable).

## 1. Core requirements
**A. Auth & access control** — secure login/logout; roles: administrator, warehouse supervisor, warehouse employee; employee account management + activate/deactivate; secure password hashing and session management; restrict modules/records by role; user activity and audit logs.

**B. Employee management** — employee ID, full name, department, assigned warehouse, employment status; per-employee productivity tracking; search/filter/profile management; assign tasks to specific employees; prevent duplicate employee IDs.

**C. Picking monitoring** — tasks assigned per employee; order number, SKU, product description, quantity, task reference; start time, completion time, duration; barcode scanning (keyboard-wedge) and manual entry; statuses pending / in-progress / completed / cancelled; record exceptions, errors, incomplete picks; prevent unauthorized modification of completed records.

**D. Packing monitoring** — packing by employee; order number, package reference, quantity, packing status; start/completion time and duration; packing errors, damaged items, discrepancies, rework; history of task updates and corrections.

**E. Unicommerce import** — CSV and Excel (if compatible with available PHP libs/hosting); downloadable template; validate required fields, employee IDs, order numbers, SKUs, dates, quantities; detect duplicates and report errors; PREVIEW before saving; import summary (successful / rejected / reasons); preserve original files and import history where storage policy permits. Initial integration = manually exported Uniware reports. No API or direct DB access assumed.

**F. Dashboard & reports** — tasks completed per employee; picking and packing productivity; average completion time; total processing time; daily/weekly/monthly; pending and overdue; picking and packing error rates; rework and exception counts; employee and warehouse comparisons; filters (date range, employee, warehouse, activity); export CSV and, where supported, Excel. Clearly distinguish measured data from calculated metrics. Document the formulas. Avoid misleading comparisons between employees with different workloads or task types.

**G. Audit & administration** — log logins and significant data changes; who created/updated/imported/cancelled a record, with timestamps; admin config for warehouse locations, task types, system settings; database backup and recovery procedures suited to the hosting.

**H. CCTV integration — Phase 2 (NOT in initial release unless separately approved)** — design so a future phase can associate task timestamps with authorized CCTV footage/incident references. No covert surveillance, no unauthorized camera access, no continuous video collection. Must follow privacy requirements and organizational policy.

## 2. Agents
Lead architect owns design, shared interfaces, integration, review, final acceptance. Agents must not redefine the schema or shared interfaces without approval.
1. **Database & data architecture** — schema, migrations, seed data, indexes/constraints, transactions; tables for employees, users, tasks, imports, productivity summaries, audit logs; dedicated WPMS database. Deliver: schema + migrations, ERD docs, test data, setup instructions.
2. **Authentication & security** — auth, RBAC, password hashing, sessions; prevent SQLi, XSS, CSRF; server-side validation; protect admin functions and sensitive records; review uploads and file permissions. Deliver: auth module, authorization checks, security checklist + results.
3. **Picking & packing workflows** — employee/picking/packing modules; assignment, status transitions, timestamps; barcode (keyboard input), manual entry, error handling; records traceable and never silently altered. Deliver: modules, screens, workflow docs, test cases.
4. **Importing & reporting** — CSV/Excel import, validation, preview, duplicates, error reports; dashboards, productivity calcs, filters, exports; documented metric definitions. Deliver: import module + templates, dashboards/reports, sample reports, validation tests.
5. **QA & security testing** — test all modules, role permissions, data integrity, invalid input, duplicate imports, missing fields, large files within hosting limits, timing/status/productivity calcs; verify existing production apps/DBs unaffected. Deliver: test plan + results, defect log, acceptance checklist.
6. **Deployment & documentation** — inspect Z.com cPanel via authorized access; verify document root, PHP version, extensions, MySQL/MariaDB, upload limits; deployment instructions for the real config; env-specific settings; keep credentials, logs, temp files, backups, private imports outside web root where possible; HTTPS; docs for install/backup/recovery/troubleshooting/maintenance. Deliver: deployment package, cPanel guide, DB install instructions, admin + user manuals, post-deployment verification report.

Lead architect must: approve architecture and schema BEFORE parallel implementation; define interfaces, naming, coding standards, module boundaries; assign file ownership; sequence dependencies; integrate only after reviewing interfaces; keep a working app throughout; run end-to-end tests before release; confirm acceptance criteria; prevent unauthorized production changes.

## 3. Workflow
1. Inspect existing project files and hosting requirements without modifying production.
2. Document requirements and architecture.
3. Approve DB schema and shared interfaces.
4. Develop modules in parallel where possible.
5. Integrate in a controlled dev environment.
6. Functional, security, regression tests.
7. Fix critical/high defects.
8. Prepare the production package.
9. Back up relevant files and databases before deployment.
10. Deploy only after the plan and target directory are verified.
11. Post-deployment smoke tests, documented.
Missing dependency: document it and use a compatible alternative only after confirming it preserves functionality.

## 4. Mandatory Z.com cPanel safeguards
The existing **Warehouse Inventory System is a protected production application**: never overwrite, rename, delete or modify its files, config, database or records.
- Confirm real document root and directory structure; confirm `public_html/warehouse-productivity/` is available.
- Create a SEPARATE WPMS database and DB user with minimum privileges; never reuse the Inventory System DB.
- No changes to existing production tables; no destructive SQL against any existing DB. Never run DROP DATABASE, DROP TABLE, destructive migrations, or mass-deletion scripts against production.
- Back up the app and its DB before significant upgrades.
- Credentials in a protected config file / env outside public access where possible.
- Do not expose .env files, SQL dumps, logs, uploaded source files, or private reports via the web server. Disable directory listing; protect sensitive directories.
- Verify HTTPS and secure cookie/session settings; confirm permissions and PHP version compatibility.
- Test with sample data before importing real employee/warehouse records.
- No passwords, API keys, session cookies, cPanel session URLs or tokens in source, logs, prompts or reports.
- If hosting lacks a required feature, explain and recommend a compatible solution. Do not claim "deployed" or "working" until actually tested on the target host.

## 5. Architecture & coding standards
Modular PHP (separate config, business logic, DB access, presentation); PDO with parameterized queries; reusable components, consistent naming; server-side validation of all important input; transactions for multi-step changes; pagination; clear success/validation messages; responsive (desktop, tablet, warehouse workstation); config separate from code; sample config without real credentials; avoid dependencies that can't be installed on shared hosting; document third-party libraries and install needs.

## 6. Acceptance criteria
1. Authorized users log in and only reach permitted modules.
2. Admins manage employees and warehouse settings.
3. Picking and packing tasks can be assigned, tracked, completed.
4. Start/completion times and durations recorded accurately.
5. CSV and supported Excel files import with validation and error reporting.
6. Duplicate/invalid imports identified before finalizing.
7. Dashboards/reports match documented calculation rules.
8. Authorized users can export reports.
9. Audit logs capture important activities and record changes.
10. Security and functional tests pass with no unresolved critical defects.
11. Works in the actual Z.com cPanel environment.
12. Existing Warehouse Inventory System continues to work unchanged.
13. Deployment, backup, recovery and user documentation provided.

## 7. Final deliverables
Complete PHP source; schema/migrations/sample data; auth + role modules; employee, picking, packing modules; Uniware CSV/Excel import; dashboards and reports; audit logging; automated or documented tests; Z.com deployment instructions; DB setup and backup/recovery instructions; admin and user manuals; final report (completed features, limitations, known issues, deployment status).

## Final instruction
Start by inspecting available project files and preparing a development plan. Verify actual hosting requirements before choosing the deployment path. Do not assume access to cPanel, existing source, or production DBs unless authorized access genuinely exists. Never fabricate test results, hosting details, or deployment status. Prioritize data integrity, security, maintainability, and compatibility with the existing hosting; build WPMS as a separate application without disrupting the Warehouse Inventory System.
