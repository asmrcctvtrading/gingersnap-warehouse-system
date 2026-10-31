# Metric definitions (DRAFT)

**Measured** = recorded directly. **Calculated** = derived by the formulas below. Reports label each column accordingly.

## Measured
| Field | Source |
|---|---|
| `started_at`, `completed_at` | server clock (Asia/Manila) at the Start/Complete action, or supplied by CSV import (flagged `source=import`) |
| `qty_expected`, `qty_done` | entered/scanned by the worker, or imported |
| Exceptions (`is_error`, `is_rework`, `qty_affected`) | recorded by worker/supervisor |
| `due_at` | set by supervisor/import |

## Calculated (period = by `completed_at` date unless stated)
1. **Tasks completed** = COUNT(tasks WHERE status='completed' AND completed_at in range).
2. **Task duration** = `completed_at - started_at` in seconds (stored in `duration_seconds`). Wall-clock: includes interruptions; v1 has no pause.
3. **Average completion time** = SUM(duration_seconds) / COUNT(completed tasks having a duration). Tasks over the `max_task_seconds` setting are shown but excluded from averages when the "exclude outliers" toggle is on (default on, count of excluded shown).
4. **Total processing time** = SUM(duration_seconds).
5. **Units per active hour** = SUM(qty_done) / (SUM(duration_seconds) / 3600). Based on task time, **not** shift time; it ignores idle time between tasks.
6. **Tasks per active hour** = COUNT / (SUM(duration_seconds)/3600).
7. **Error rate (task basis)** = COUNT(completed tasks with >=1 exception where is_error=1) / COUNT(completed tasks).
8. **Error rate (unit basis)** = SUM(qty_affected of is_error exceptions) / SUM(qty_done). Shown only when qty_affected is recorded.
9. **Rework count** = COUNT(exceptions WHERE is_rework=1) in range (by exception `created_at`).
10. **Pending** = COUNT(status='pending'); **In progress** = COUNT(status='in_progress') (point in time, not period).
11. **Overdue** = COUNT(status IN ('pending','in_progress') AND due_at < NOW()).
12. **On-time rate** = COUNT(completed with completed_at <= due_at) / COUNT(completed with due_at).
13. **Daily / weekly / monthly** = the above grouped by DATE, ISO week (Monday start), calendar month of `completed_at`.
14. **Vs target** (optional) = average duration / `task_types.target_seconds`; only when a target is set.

## Fair-comparison rules (enforced in the UI)
- Employee and warehouse comparisons group by **activity and task type**; picking and packing are never merged into one rate.
- Comparisons show the sample size (n). Fewer than `min_sample` tasks (default 10) displays "insufficient data" instead of a ranking.
- Workload mix differs between people; the report shows task count and units next to every rate, and states that rates are not a performance score.
- Imported historical tasks with supplied timestamps can be filtered out via the `source` filter.

## Imported (Uniware) tasks and time metrics
Uniware exports give created and completed timestamps, not the picker's real start. For `source='import'` tasks `started_at` and `duration_seconds` are NULL, so they are **excluded from duration, average-time and per-active-hour metrics** and included in counts, units completed and daily/weekly/monthly throughput. Created→completed lead time may be shown only as a separately labelled "Uniware lead time (includes queue time)" column, never as picking time. Reports show a "timed tasks vs imported tasks" split so the basis of each figure is visible.

## Known limitations
No pause/resume, no shift or attendance data, so utilisation cannot be calculated. Duration depends on workers pressing Start/Complete honestly and promptly; data-quality flags (zero/negative/very long durations) are surfaced rather than hidden.
