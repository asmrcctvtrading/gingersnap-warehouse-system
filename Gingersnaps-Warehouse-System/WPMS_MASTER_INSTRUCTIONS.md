# Warehouse Productivity Monitoring System (WPMS)
## Master Instructions and Development Specification

**Project Overview**

Develop a Warehouse Productivity Monitoring System (WPMS) using PHP and MySQL to monitor individual warehouse employees' picking and packing activities, measure task completion times, and generate productivity reports.

The system will complement the existing Unicommerce Uniware platform. It must operate independently and must not assume API access to Uniware unless verified and explicitly authorized.

- **Deployment environment:** Existing Z.com cPanel hosting
- **Proposed application directory:** `public_html/warehouse-productivity/`
- **Proposed URL:** `https://asmrcctvtechnologies.com/warehouse-productivity/`
- **Technology stack:** PHP, MySQL/MariaDB, HTML, CSS, JavaScript, and Bootstrap

---

## 1. Core Requirements

### A. User Authentication and Access Control
* Secure login and logout.
* Role-based access control for administrators, warehouse supervisors, and warehouse employees.
* Employee account management and activation/deactivation.
* Secure password hashing and session management.
* Restrict access to authorized modules and records.
* Maintain user activity and audit logs.

### B. Employee Management
* Employee ID, full name, department, assigned warehouse, and employment status.
* Individual employee productivity tracking.
* Employee search, filtering, and profile management.
* Ability to assign tasks to specific employees.
* Prevent duplicate employee IDs.

### C. Picking Monitoring
* Record picking tasks assigned to each employee.
* Capture order number, SKU, product description, quantity, and task reference.
* Record task start time, completion time, and total duration.
* Support barcode scanning and manual entry.
* Track pending, in-progress, completed, and cancelled tasks.
* Record exceptions, errors, and incomplete picks.
* Prevent unauthorized modification of completed records.

### D. Packing Monitoring
* Track packing activities by employee.
* Record order number, package reference, quantity, and packing status.
* Capture start time, completion time, and processing duration.
* Record packing errors, damaged items, discrepancies, and rework.
* Maintain a history of task updates and corrections.

### E. Unicommerce Data Import
* Support CSV and Excel imports when compatible with available PHP libraries and hosting environment.
* Provide a downloadable import template.
* Validate required fields: employee IDs, order numbers, SKUs, dates, quantities.
* Detect duplicate records and report import errors.
* Provide an import preview before saving.
* Generate an import summary showing successful records, rejected records, and rejection reasons.
* Preserve original imported files and import history where storage policies permit.
* Do not assume API connectivity or direct database access to Uniware.
* Use manually exported Uniware reports as the initial integration method unless another authorized integration is verified.

### F. Productivity Dashboard and Reports
Generate dashboards and reports showing:
* Tasks completed per employee.
* Picking and packing productivity.
* Average task completion time.
* Total processing time.
* Daily, weekly, and monthly performance.
* Pending and overdue tasks.
* Picking and packing error rates.
* Rework and exception counts.
* Employee and warehouse comparisons.
* Date-range, employee, warehouse, and activity filters.
* Exportable reports in CSV and supported Excel format.

**Documentation requirement:** Clearly distinguish measured data from calculated metrics. Document the formulas used for productivity calculations and avoid misleading comparisons between employees with different workloads or task types.

### G. Audit Trail and System Administration
* Record login activity and significant data changes.
* Track who created, updated, imported, or cancelled a record.
* Record timestamps for changes.
* Provide administrative configuration for warehouse locations, task types, and system settings.
* Implement database backup and recovery procedures appropriate to the hosting environment.

### H. CCTV Integration — Phase 2
Do not include CCTV integration in the initial release unless separately approved. Design the system so a future phase can associate task timestamps with authorized CCTV footage or incident references. Do not implement covert employee surveillance, unauthorized camera access, or continuous video collection.

---

## 2. Multi-Agent Development Approach

### Lead Architect
Responsibilities:
* System design, shared interfaces, integration, code review, and final acceptance
* Approve architecture and database schema before parallel implementation
* Define shared interfaces, naming conventions, coding standards, and module boundaries
* Assign file ownership to avoid conflicting edits
* Establish dependencies between agents
* Integrate completed modules only after reviewing their interfaces
* Resolve conflicts and maintain a working application throughout development
* Run end-to-end tests before release
* Confirm that all acceptance criteria are satisfied
* Prevent agents from making unauthorized production changes

### Agent 1 — Database and Data Architecture
Responsibilities:
* Design the relational database schema
* Create SQL migrations and seed data
* Define tables, relationships, indexes, and constraints
* Design tables for employees, users, tasks, imports, productivity summaries, and audit logs
* Ensure appropriate data integrity and transaction handling
* Use a dedicated database for WPMS

Deliverables:
* Schema and migration files
* Entity relationship documentation
* Test data and database setup instructions

### Agent 2 — Authentication and Security
Responsibilities:
* Implement authentication and role-based access control
* Apply secure password hashing and session handling
* Prevent SQL injection, cross-site scripting, and cross-site request forgery
* Validate user input on the server
* Protect administrative functions and sensitive records
* Review upload handling and file access permissions

Deliverables:
* Authentication module
* Authorization middleware or equivalent access checks
* Security checklist and test results

### Agent 3 — Picking and Packing Workflows
Responsibilities:
* Develop employee, picking, and packing modules
* Implement task assignment, status transitions, and timestamp recording
* Add barcode scanning support where the hardware behaves as a standard keyboard input
* Add manual entry and error-handling workflows
* Ensure task records are traceable and cannot be silently altered

Deliverables:
* Employee and task management modules
* Picking and packing screens
* Workflow documentation and test cases

### Agent 4 — Importing and Reporting
Responsibilities:
* Implement CSV and Excel import workflows
* Validate and preview imported records
* Detect duplicates and provide detailed import error reports
* Build dashboards, productivity calculations, filters, and report exports
* Document metric definitions and calculation rules

Deliverables:
* Import module and templates
* Dashboard and reporting modules
* Sample reports and validation tests

### Agent 5 — Quality Assurance and Security Testing
Responsibilities:
* Test all modules against approved requirements
* Validate role permissions and data integrity
* Test invalid inputs, duplicate imports, missing fields, and large files within hosting limits
* Test task timing, status transitions, and productivity calculations
* Identify security vulnerabilities and integration issues
* Verify that existing production applications and databases remain unaffected

Deliverables:
* Test plan and test results
* Defect log
* Final acceptance checklist

### Agent 6 — Deployment and Documentation
Responsibilities:
* Inspect the Z.com cPanel hosting environment through authorized access
* Verify the actual domain document root, PHP version, required extensions, MySQL/MariaDB availability, and upload limits
* Prepare deployment instructions suitable for the actual hosting configuration
* Configure the application using environment-specific settings
* Keep credentials, logs, temporary files, backups, and private imports outside the publicly accessible directory
* Configure HTTPS and verify access restrictions
* Document installation, backup, recovery, troubleshooting, and maintenance procedures

Deliverables:
* Deployment-ready application package
* Z.com cPanel deployment guide
* Database installation instructions
* Administrator and user manuals
* Post-deployment verification report

---

## 3. Development Workflow

Follow this sequence:

1. Inspect existing project files and hosting requirements without modifying production
2. Document requirements and establish the system architecture
3. Approve the database schema and shared interfaces
4. Develop independent modules in parallel where possible
5. Integrate modules in a controlled development environment
6. Run functional, security, and regression tests
7. Fix critical and high-priority defects
8. Prepare the production deployment package
9. Back up relevant files and databases before deployment
10. Deploy only after the deployment plan and target directory are verified
11. Run post-deployment smoke tests and document the result

---

## 4. Mandatory Z.com cPanel Deployment Safeguards

**CRITICAL:** The WPMS will be hosted on an existing Z.com cPanel account that may already host production applications.

**Protected Production Application:** The existing Warehouse Inventory System is a protected production application. Do not overwrite, rename, delete, or modify its files, configuration, database, or records.

**Before Deployment:**
* Confirm the actual document root and existing directory structure
* Confirm whether the proposed `public_html/warehouse-productivity/` directory is available
* Create a separate WPMS database and database user with only the required privileges
* Do not reuse the existing Warehouse Inventory System database
* Do not modify existing production tables or execute destructive SQL against any existing database
* Never run `DROP DATABASE`, `DROP TABLE`, destructive migrations, or mass deletion scripts against production
* Back up the new application and its database before significant upgrades
* Keep database credentials in a protected configuration file or environment configuration outside public access
* Do not expose `.env` files, SQL dumps, application logs, uploaded source files, or private reports through the web server
* Disable directory listing and protect sensitive directories
* Verify HTTPS and secure cookie/session settings
* Confirm file permissions and compatibility with the actual cPanel PHP version
* Test with sample data before importing real employee or warehouse records
* Do not place passwords, API keys, session cookies, cPanel session URLs, or access tokens in source code, logs, prompts, or reports

**If the hosting environment does not support a required feature, explain the limitation and recommend a compatible solution.**

---

## 5. Architecture and Coding Standards

* Use a modular PHP architecture with clear separation of configuration, business logic, database access, and presentation
* Use PDO or another secure, supported database-access method with parameterized queries
* Use reusable components and consistent naming conventions
* Validate all important inputs on the server, even when client-side validation is present
* Use database transactions for operations that require multiple related changes
* Use pagination for large record sets
* Provide clear success messages, validation errors, and recovery instructions
* Make the interface responsive for desktop computers, tablets, and warehouse workstations
* Keep application configuration separate from source code
* Include setup instructions and a sample configuration file without real credentials
* Avoid unnecessary dependencies that may not be installable on shared hosting
* Document any third-party libraries and their installation requirements

---

## 6. Acceptance Criteria

The initial release is complete only when:

1. ✅ Authorized users can log in and access only permitted modules
2. ✅ Administrators can manage employees and warehouse settings
3. ✅ Picking and packing tasks can be assigned, tracked, and completed
4. ✅ Task start times, completion times, and durations are recorded accurately
5. ✅ CSV and supported Excel files can be imported with validation and error reporting
6. ✅ Duplicate or invalid imports are identified before finalizing the import
7. ✅ Productivity dashboards and reports produce results consistent with documented calculation rules
8. ✅ Authorized users can export reports
9. ✅ Audit logs capture important activities and record changes
10. ✅ Security and functional tests pass without unresolved critical defects
11. ✅ The application works within the actual Z.com cPanel environment
12. ✅ The existing Warehouse Inventory System continues to function without changes caused by WPMS
13. ✅ Deployment, backup, recovery, and user documentation are provided

---

## 7. Final Deliverables

Provide:
* Complete PHP source code
* Database schema, migrations, and sample data
* Authentication and role-management modules
* Employee, picking, and packing modules
* Uniware CSV/Excel import module
* Productivity dashboards and reports
* Audit logging
* Automated or documented test procedures
* Z.com cPanel deployment instructions
* Database setup and backup/recovery instructions
* Administrator and user manuals
* A final report listing completed features, limitations, known issues, and deployment status

---

**Document Version:** 1.0  
**Date:** October 24, 2026  
**Project:** Gingersnaps Warehouse System — WPMS Development
