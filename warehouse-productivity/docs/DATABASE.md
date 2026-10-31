# Database design (DRAFT) — see `database/schema.sql`

Dedicated database (e.g. `cpanelprefix_wpms`), separate DB user with privileges limited to it. Never the Warehouse Inventory System database.

## ER overview
```
warehouses 1─* employees 1─0..1 users ─* user_warehouses *─1 warehouses
warehouses 1─* tasks *─1 employees (assignee, nullable)
task_types 1─* tasks            import_batches 1─* tasks (source=import)
tasks 1─* task_items            tasks 1─* task_exceptions
tasks 1─* task_events (append-only history)
tasks 1─* external_references   (Phase 2 CCTV/incident references, unused in v1)
import_batches 1─* import_rows
users 1─* audit_log             productivity_daily (rebuildable cache)
```

## Table purposes
| Table | Purpose |
|---|---|
| users / user_warehouses | Login accounts, roles, lockout state, supervisor warehouse scope |
| employees | Business identity (unique `employee_code`), department, warehouse, employment status |
| warehouses, task_types, app_settings | Admin-configurable reference data |
| tasks | One picking or packing task; status, measured times, stored duration, optimistic `row_version` |
| task_items | SKU lines with expected/done quantity and scan-vs-manual flag |
| task_exceptions | Errors, damage, discrepancy, rework (flags drive error-rate and rework metrics) |
| task_events | Append-only history of every transition and correction, with actor and reason |
| import_batches / import_rows | Preserved upload record, preview results, per-row rejection reasons |
| audit_log | Logins, config changes, imports, record changes |
| productivity_daily | Optional aggregate cache; never the source of truth |
| external_references | Phase 2 placeholder for authorised CCTV/incident references |

## Integrity rules
- Unique: `employees.employee_code`, `users.username`, `tasks.task_ref`, `tasks.external_key` (duplicate-import guard), `warehouses.code`, `task_types.code`.
- All relationships are foreign keys without cascading deletes; history rows are never deleted. "Delete" of employees/warehouses = set inactive.
- `completed_at >= started_at` (CHECK + app).
- Multi-row changes (complete task + event + audit; import commit) run in one PDO transaction.
- Indexes target the report filters: (employee, status, completed_at), (warehouse, activity, status, completed_at), (status, due_at), order number.

## Setup
1. cPanel → MySQL Databases: create DB + user, add user with required privileges only.
2. Run `install/install.php` (loads `schema.sql` only into an empty DB) or import `schema.sql` through phpMyAdmin.
3. Seed: roles are an ENUM (no table); installer creates the first admin and default task types. Sample data is optional and flagged.

## Backup / recovery (to be detailed in deployment guide)
cPanel *Backup* (partial MySQL backup download) or phpMyAdmin Export before every upgrade; weekly scheduled export kept outside `public_html`; restore by importing into a new empty database and switching `config.local.php`. Not yet tested on the host.
