# WPMS Phase 1: Import & Reporting Module Integration Guide

**Status:** In Development  
**Last Updated:** October 24, 2026  
**Agent:** Agent 4 (Importing and Reporting)

---

## Overview

Phase 1 delivers complete import and reporting capabilities for the WPMS. This guide integrates all components and explains how they work together.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND (UI)                            │
├─────────────────────────────────────────────────────────────┤
│  public/pages/import.php          (File upload & preview)   │
│  public/pages/dashboard.php       (Real-time metrics)       │
│  public/pages/reports.php         (Report generation)       │
└──────────────────┬──────────────────────────────────────────┘
                   │ HTTP Requests
                   ↓
┌─────────────────────────────────────────────────────────────┐
│                  API ENDPOINTS                              │
├─────────────────────────────────────────────────────────────┤
│  public/api/import/                                         │
│  ├─ preview.php          (Preview file upload)              │
│  ├─ process.php          (Process import)                   │
│  ├─ template.php         (Download template)                │
│  └─ history.php          (View import history)              │
│                                                              │
│  public/api/dashboard/                                      │
│  ├─ metrics.php          (Get dashboard metrics)            │
│  └─ leaderboard.php      (Get employee rankings)            │
│                                                              │
│  public/api/report/                                         │
│  ├─ picking.php          (Picking performance report)       │
│  ├─ packing.php          (Packing performance report)       │
│  ├─ warehouse.php        (Warehouse productivity report)    │
│  └─ executive.php        (Executive summary)                │
│                                                              │
│  public/api/export/                                         │
│  └─ report.php           (Export to CSV)                    │
└──────────────────┬──────────────────────────────────────────┘
                   │ Calls Controllers
                   ↓
┌─────────────────────────────────────────────────────────────┐
│                  CONTROLLERS                                │
├─────────────────────────────────────────────────────────────┤
│  ImportController      (Handles file upload & import)       │
│  ReportController      (Handles report generation)          │
└──────────────────┬──────────────────────────────────────────┘
                   │ Uses Services & Generators
                   ↓
┌─────────────────────────────────────────────────────────────┐
│                   BUSINESS LOGIC                            │
├─────────────────────────────────────────────────────────────┤
│  src/modules/import/                                        │
│  ├─ ImportService         (CSV parsing, validation, import) │
│  └─ ImportTemplateGenerator (Template generation)           │
│                                                              │
│  src/modules/reporting/                                     │
│  ├─ MetricsCalculator     (All metric calculations)         │
│  ├─ ReportGenerator       (All 4 report types)              │
│  └─ CSVExporter          (Export functionality)             │
└──────────────────┬──────────────────────────────────────────┘
                   │ Access Database
                   ↓
┌─────────────────────────────────────────────────────────────┐
│                     DATABASE                                │
├─────────────────────────────────────────────────────────────┤
│  Tables: picking_tasks, packing_tasks, imports,             │
│  import_details, audit_logs, productivity_summaries         │
└─────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. Import Module

**File Path:** `src/modules/import/`

#### ImportService.php
- **Purpose:** Core import logic
- **Key Methods:**
  - `getPreview($filePath)` - Preview file without saving
  - `importRecords($filePath, $userId, $warehouseId)` - Process full import
  - `checkDuplicate($record)` - Detect duplicate records
  - `validateRecord($record, $rowNumber)` - Validate single record
  - `getImportHistory($limit)` - Retrieve import history

- **Features:**
  - CSV parsing with header validation
  - Field-level validation (format, length, type)
  - Duplicate detection (by picklist_code and order_number+date)
  - Transaction-based batch processing
  - Detailed error reporting with row numbers
  - Import history tracking

- **Validation Rules:**
  - Picklist Code: alphanumeric + hyphens (3-50 chars)
  - Order Number: numeric (5-20 digits)
  - SKU Code: alphanumeric (max 100)
  - Quantity: positive integer (1-10000)
  - Status: IN_PROGRESS, PENDING, COMPLETED, CANCELLED

#### ImportTemplateGenerator.php
- **Purpose:** Generate downloadable templates
- **Key Methods:**
  - `generateAndDownload($templateType)` - Send CSV template as download
  - `generateAsString($templateType)` - Return CSV as string
  - `getTemplateInfo($templateType)` - Get template metadata
  - `getInstructions($templateType)` - Get usage instructions

- **Template Types:**
  - `picklist` - Unicommerce picklist import
  - `employee` - Employee records import

### 2. Reporting Module

**File Path:** `src/modules/reporting/`

#### MetricsCalculator.php
- **Purpose:** Calculate all productivity metrics
- **Key Methods:**
  - `getEmployeeMetrics($empId, $start, $end)` - Get employee metrics
  - `getDailyMetrics($start, $end)` - Get 7-day metrics
  - `getEmployeeLeaderboard($start, $end, $sortBy, $limit)` - Get rankings
  - `getPickingMetrics()` - Calculate picking-specific metrics
  - `getPackingMetrics()` - Calculate packing-specific metrics

- **Metrics Calculated:**
  - Average task duration (seconds/minutes)
  - Tasks per hour (throughput)
  - Error rate (%)
  - Completion rate (%)
  - Rework percentage (%)
  - Productivity score (0-100)

- **Formulas:**
  ```
  Average Duration = SUM(task_duration_seconds) / COUNT(completed)
  Tasks/Hour = COUNT(completed) / (total_seconds / 3600)
  Error Rate% = COUNT(errors) / COUNT(total) * 100
  Completion% = COUNT(completed) / COUNT(assigned) * 100
  Rework% = COUNT(rework) / COUNT(completed) * 100
  Productivity Score = (completion% * 0.5) + ((100-error%) * 0.5)
  ```

#### ReportGenerator.php
- **Purpose:** Generate 4 types of reports
- **Key Methods:**
  - `generatePickingReport()` - Picking performance by employee
  - `generatePackingReport()` - Packing performance by employee
  - `generateWarehouseReport()` - Warehouse-level aggregate metrics
  - `generateExecutiveSummary()` - High-level summary for management

- **Report Features:**
  - Date range filtering
  - Employee/warehouse/department filtering
  - Calculation of metrics per report type
  - Data ready for CSV export

#### CSVExporter.php
- **Purpose:** Export report data to CSV
- **Key Methods:**
  - `exportReport($data, $reportType, $params)` - Generate and stream CSV
  - `getCSVContent()` - Return CSV as string
  - `escapeField($value)` - Properly escape CSV fields

- **Features:**
  - Proper CSV formatting (quote escaping, commas, line breaks)
  - Report metadata in CSV header
  - Handle large datasets (streaming)
  - Browser download with proper headers

### 3. Controllers

**File Path:** `src/controllers/`

#### ImportController.php
- **Endpoints:**
  - `previewImport()` - Handle file upload preview
  - `processImport()` - Process confirmed import
  - `downloadTemplate()` - Stream template download
  - `getImportHistory()` - Retrieve import history
  - `getImportDetails($id)` - Get specific import details

- **Authorization:**
  - Requires admin or supervisor role
  - Validates file size (max 5MB)
  - Validates file type (CSV, Excel)
  - Manages temporary file handling

#### ReportController.php
- **Endpoints:**
  - `getDashboardMetrics()` - Real-time dashboard data
  - `getDailyMetrics()` - 7-day historical metrics
  - `getLeaderboard()` - Employee rankings
  - `getPickingReport()` - Picking report generator
  - `getPackingReport()` - Packing report generator
  - `getWarehouseReport()` - Warehouse report generator
  - `getExecutiveSummary()` - Executive summary generator
  - `exportReport()` - CSV export handler

- **Authorization:**
  - Employee: View self data only
  - Supervisor: View team/warehouse data
  - Admin: View all data + executive summary

---

## API Endpoints

### Import Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| POST | `/api/import/preview` | Preview file | Admin/Supervisor |
| POST | `/api/import/process` | Process import | Admin/Supervisor |
| GET | `/api/import/template?type=picklist` | Download template | Admin/Supervisor |
| GET | `/api/import/history` | Get history | Admin/Supervisor |

### Dashboard Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/dashboard/metrics` | Dashboard metrics | All (role-based) |
| GET | `/api/dashboard/leaderboard` | Employee rankings | All (role-based) |

### Report Endpoints

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/report/picking` | Picking report | Admin/Supervisor |
| GET | `/api/report/packing` | Packing report | Admin/Supervisor |
| GET | `/api/report/warehouse` | Warehouse report | Admin/Supervisor |
| GET | `/api/report/executive` | Executive summary | Admin only |
| GET | `/api/export/report?type=picking` | Export to CSV | Admin/Supervisor |

---

## Data Flow Example: Importing Picklists

```
1. User uploads CSV file via public/pages/import.php
   ↓
2. JavaScript sends POST to /api/import/preview
   ↓
3. ImportController::previewImport()
   - Validates file (size, type)
   - Moves to temp location
   - Creates ImportService
   - Calls getPreview()
   ↓
4. ImportService::getPreview()
   - Opens file, reads headers
   - Validates headers (required fields)
   - Maps CSV columns to database fields
   - Reads first 10 rows
   - Returns preview data
   ↓
5. Response sent to frontend
   - Shows preview table
   - User reviews data
   - Clicks "Import" button
   ↓
6. JavaScript sends POST to /api/import/process
   ↓
7. ImportController::processImport()
   - Checks temp file exists
   - Validates warehouse access
   - Calls importRecords()
   ↓
8. ImportService::importRecords()
   - Starts database transaction
   - Creates import record
   - Parses and validates all records
   - Checks for duplicates
   - Inserts into picking_tasks table
   - Updates import record with counts
   - Commits transaction
   ↓
9. Response sent to frontend
   - Shows success message
   - Displays summary (200 imported, 5 failed)
   ↓
10. Audit trail recorded automatically
```

---

## Data Flow Example: Generating Report

```
1. User clicks "Generate Report" on public/pages/reports.php
   ↓
2. Selects: Picking Report, Date Range: Oct 1-31, Employee: Juan
   ↓
3. JavaScript sends GET to /api/report/picking?start_date=2026-10-01&end_date=2026-10-31&employee_id=1
   ↓
4. ReportController::getPickingReport()
   - Validates authorization
   - Extracts filter parameters
   - Calls ReportGenerator::generatePickingReport()
   ↓
5. ReportGenerator::generatePickingReport()
   - Queries picking_tasks for employee in date range
   - For each record, calculates metrics using MetricsCalculator
   - Formats data as report rows
   - Returns array of employee performance data
   ↓
6. Response sent to frontend
   - Displays report in table format
   - Shows employee name, total tasks, completion%, error rate, etc.
   ↓
7. User clicks "Export to CSV"
   ↓
8. JavaScript sends GET to /api/export/report?type=picking&...filters...
   ↓
9. ReportController::exportReport()
   - Regenerates report data
   - Creates CSVExporter instance
   - Calls exportReport()
   ↓
10. CSVExporter::exportReport()
    - Sets HTTP headers for CSV download
    - Writes BOM (UTF-8 compatibility)
    - Writes headers row
    - Writes metadata (date range, filters)
    - Writes data rows with proper CSV escaping
    - Streams to browser
    ↓
11. Browser downloads "WPMS_Picking_Report_20261001-20261031.csv"
```

---

## Integration Points with Other Modules

### With Authentication Module (Agent 2)
- Controllers require `$_SESSION['user_id']` and `$_SESSION['user_role']`
- Role-based access control enforced before data operations
- Audit trail records `created_by` as current user

### With Employee/Task Module (Agent 3)
- ImportService references `employees` and `warehouses` tables
- ReportGenerator queries `picking_tasks` and `packing_tasks`
- MetricsCalculator joins with `employees` for names
- Foreign key constraints ensure data integrity

### With Audit Module
- All imports logged to `audit_logs` table
- All report accesses can be logged (future enhancement)
- User actions tracked in `created_by`, `updated_by` fields

---

## Configuration

### Environment Variables (in `.env`)
```
DB_HOST=your_host
DB_USER=wpms_user
DB_PASS=your_password
DB_NAME=wpms_prod
ENVIRONMENT=production
APP_URL=https://asmrcctvtechnologies.com/warehouse-productivity/
```

### File Upload Settings
```php
// In ImportController
$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedMimes = ['text/csv', 'application/vnd.ms-excel', ...];
$tempDir = sys_get_temp_dir();
```

### Session Configuration
```php
// In config/config.php
session_set_cookie_params([
    'secure' => true,      // HTTPS only
    'httponly' => true,    // No JavaScript access
    'samesite' => 'Lax'    // CSRF protection
]);
```

---

## Security Considerations

### Input Validation
- All file uploads validated (size, type, content)
- All CSV fields validated against regex patterns
- All date/numeric inputs validated before use
- All query parameters sanitized

### SQL Injection Prevention
- PDO prepared statements used exclusively
- No dynamic query building with user input
- Bind parameters used for all values

### Authorization
- Role-based access control enforced
- Supervisors can only see team data
- Employees can only see own data
- Admin access to all data

### File Security
- Uploaded files stored outside web root
- Temp files automatically deleted after processing
- No executable files allowed
- Proper MIME type validation

---

## Testing Checklist

- [ ] Import CSV with valid data (200 records)
- [ ] Import CSV with invalid data (shows errors)
- [ ] Duplicate detection prevents re-import
- [ ] Template download works for both types
- [ ] Dashboard metrics calculate correctly
- [ ] Leaderboard sorts by completion rate
- [ ] Picking report filters by employee
- [ ] Packing report filters by warehouse
- [ ] Warehouse report shows aggregate metrics
- [ ] Executive summary available to admin only
- [ ] CSV export creates downloadable file
- [ ] Large import (1000+ records) completes
- [ ] Supervisor can't access other warehouse data
- [ ] Employee can only view self metrics
- [ ] Audit trail records all imports

---

## Performance Optimization

### Database Indexes
- `picking_tasks(employee_id, created_at)` - For date range queries
- `packing_tasks(employee_id, created_at)` - Same
- `picking_tasks(status)` - For filtering by status
- `employees(id, warehouse_id)` - For joins

### Caching (Future Enhancement)
- Cache daily metrics (refresh nightly)
- Cache employee list (refresh hourly)
- Use Redis for session storage

### Query Optimization
- Use SELECT specific columns (not SELECT *)
- Limit result sets with LIMIT clause
- Use OFFSET for pagination (max 500 per page)
- Batch imports with transactions

---

## Troubleshooting

### Import Issues

**Problem:** "File upload failed"
- Check file size (max 5MB)
- Verify file type (CSV or Excel)
- Check server permissions on temp directory

**Problem:** "Duplicate records detected"
- Check if same file was uploaded twice
- Check picklist codes in file (must be unique)
- Review import history for previous imports

**Problem:** "Validation failed on row 25"
- Check format of: order number (numeric), quantity (integer)
- Verify dates are YYYY-MM-DD format
- Check SKU codes don't exceed 100 chars

### Report Issues

**Problem:** "No data to display"
- Check date range contains tasks
- Verify employee/warehouse filters are correct
- Confirm user has access to selected data

**Problem:** "Export file is empty"
- Verify report generated successfully first
- Check that report has data rows
- Try exporting smaller date range

---

## Future Enhancements (Phase 2+)

- PDF report generation (use mPDF)
- Email report delivery (scheduled)
- Report scheduling and automation
- Advanced filtering and saved views
- Excel import with multi-sheet support
- Real-time notifications for milestones
- Performance baselines and anomaly detection
- Predictive analytics

---

**Status:** Ready for Testing  
**Next Steps:** Run test suite, deploy to staging environment

