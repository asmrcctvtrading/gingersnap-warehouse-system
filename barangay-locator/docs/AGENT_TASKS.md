# Agent Tasks and Ownership

Sub-agent tooling was **not confirmed** to be available when Phase 0 ran (two attempts to launch a helper agent failed with a tooling error, and shell access was also unavailable). Roles below are therefore executed **sequentially by the main agent**. No parallel agents are claimed. If sub-agents become available, the "owns" column is the split to use.

| Role | Owns | Phase |
|---|---|---|
| Architect / orchestrator | `docs/ARCHITECTURE.md`, `IMPLEMENTATION_PLAN.md`, `API_SPECIFICATION.md`, `app/bootstrap.php` | 0–1 |
| Database engineer | `database/*`, `docs/DATABASE.md` | 1 |
| PHP backend | `app/lib/*`, `api/*`, `docs/BACKEND.md` | 2 |
| Frontend / UI-UX | public `*.php` pages, `assets/css`, `assets/js/ui`, `docs/FRONTEND.md` | 2 |
| GIS / mapping | `assets/js/map.js`, `map.php`, `directions.php`, `docs/MAPPING_AND_GIS.md` | 3 |
| Admin dashboard | `admin/*`, `docs/ADMIN_GUIDE.md` | 4 |
| QA / security | `tests/*`, `docs/TEST_PLAN.md`, `TEST_RESULTS.md`, `SECURITY_REVIEW.md` | 5–6 |
| Deployment | `install/*`, `docs/INSTALLATION_WINDOWS.md`, cPanel guide | 7 |

## Defect list
Maintained in `docs/KNOWN_ISSUES.md`.
