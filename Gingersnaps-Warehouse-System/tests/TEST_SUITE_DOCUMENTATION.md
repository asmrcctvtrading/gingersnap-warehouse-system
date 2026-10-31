# WPMS Test Suite Documentation

## Overview

Comprehensive test suites for the Gingersnaps Warehouse Production Management System (WPMS) focusing on import and reporting modules.

**Files Created:**
- `tests/ImportTests.php` - 25+ test cases for import functionality
- `tests/ReportTests.php` - 20+ test cases for metrics and reporting
- `tests/TEST_SUITE_DOCUMENTATION.md` - This file

---

## ImportTests.php

### Test Coverage: 25 Test Cases

#### CSV Parsing Tests
1. **testValidCsvParsing** - Parses standard CSV with multiple rows
   - Validates headers are recognized
   - Verifies row count matches file content
   - Checks mapped fields are correct

2. **testRequiredHeaderValidation** - Validates required headers present
   - Fails when Picklist_Code missing
   - Fails when Sale_Order_Code missing
   - Fails when Item_SkuCode missing

3. **testOptionalHeaderHandling** - Handles optional fields gracefully
   - Recognizes optional headers (Item_Type_Name, EAN, etc.)
   - Processes data without optional fields
   - Maps optional headers when present

#### Duplicate Detection Tests
4. **testDuplicateDetectionByPicklistCode** - Prevents duplicate picklist codes
   - Detects by exact picklist_code match
   - Returns appropriate error message
   - Prevents database constraint violations

#### Field Validation Tests
5. **testInvalidPicklistCodeFormat** - Validates picklist code format
   - Pattern: `^[A-Z0-9\-]{3,50}$`
   - Rejects codes < 3 chars
   - Rejects codes with invalid characters

6. **testInvalidOrderNumberFormat** - Validates order number format
   - Pattern: `^[0-9]{5,20}$`
   - Must be numeric
   - Must be 5-20 digits

7. **testInvalidQuantity** - Validates quantity field
   - Rejects quantities < 1
   - Rejects quantities > 10000
   - Rejects non-numeric values

8. **testSkuCodeMaxLength** - Validates SKU code length
   - Maximum 100 characters
   - Alphanumeric and special chars allowed
   - Rejects oversized codes

#### Data Handling Tests
9. **testEmptyCSVHandling** - Handles CSV with no data rows
   - Returns success with empty data array
   - Doesn't crash on headers-only file

10. **testInvalidCSVFormat** - Rejects malformed CSV
    - Returns error for non-CSV content
    - Validates structure before processing

11. **testFileNotFoundHandling** - Handles missing files gracefully
    - Returns error message
    - Doesn't crash application

12. **testSpecialCharactersHandling** - Processes special characters
    - Handles quoted fields with commas
    - Processes multi-byte characters
    - Preserves data integrity

13. **testWhitespaceHandling** - Trims whitespace from fields
    - Leading/trailing spaces removed
    - Data validated after trimming

14. **testUnicodeHandling** - Supports UTF-8 characters
    - Non-Latin scripts (Spanish, Japanese, etc.)
    - BOM detection and removal
    - Proper encoding handling

15. **testNullFieldHandling** - Manages empty and null fields
    - Optional fields can be empty
    - Required fields enforced
    - Null handling consistent

#### Security Tests
16. **testSqlInjectionPrevention** - Prevents SQL injection
    - CSV data with SQL syntax treated as literal strings
    - Uses parameterized queries (prepared statements)
    - No code execution from CSV input
    - **Example blocked:** `PT-001'; DROP TABLE picking_tasks; --`

17. **testCsvInjectionPrevention** - Prevents CSV formula injection
    - Excel/Calc formulas treated as data
    - No formula execution
    - Prevents code execution via spreadsheet
    - **Example blocked:** `=cmd|' /C calc'!A0`

#### Large File Tests
18. **testLargeFileImport** - Handles 100+ record files
    - Successfully parses 150 records
    - Memory efficient processing
    - All records processed correctly
    - No timeout on reasonable file sizes

#### Template Generation Tests
19. **testTemplateGenerationPicklist** - Generates picklist template
    - Contains all required headers
    - Includes optional headers
    - Properly formatted CSV

20. **testTemplateGenerationEmployee** - Generates employee template
    - Contains all required headers
    - Includes optional headers
    - Sample data included

21. **testTemplateAsString** - Returns template as CSV string
    - Valid CSV format
    - Headers and sample rows included
    - Can be saved or displayed

#### Advanced Tests
22. **testImportHistoryTracking** - Tracks import records
    - Maintains import history
    - Records file info, status, counts
    - Accessible for audit trail

23. **testRowCountLimit** - Enforces row limits
    - Validates row count (max 10,000)
    - Provides clear error message
    - Prevents system overload

24. **testImportWithEmployeeAssignment** - Assigns employees during import
    - Maps employee IDs from CSV
    - Links to existing employees
    - Handles missing employee errors

25. **testBomHandling** - Handles UTF-8 BOM
    - Removes BOM from Excel-exported CSV
    - Correctly parses BOM-encoded files
    - Handles with and without BOM

---

## ReportTests.php

### Test Coverage: 20 Test Cases

#### Metrics Calculation Tests
1. **testAverageDurationCalculation** - Verifies avg task duration formula
   - Formula: `SUM(task_duration_seconds) / COUNT(completed_tasks)`
   - Example: 10 tasks × 1 hour = 10 hours total ÷ 10 tasks = 1 hour/task
   - Tests with known values (3600s, 5400s, 4800s = 4600s avg)

2. **testErrorRateCalculation** - Verifies error rate formula
   - Formula: `COUNT(tasks_with_errors) / COUNT(total_tasks) * 100`
   - Example: 2 errors out of 10 tasks = 20% error rate
   - Tests with 10 tasks: 2 with errors, 8 without

3. **testCompletionRateCalculation** - Verifies completion formula
   - Formula: `COUNT(completed_tasks) / COUNT(total_tasks) * 100`
   - Example: 12 completed out of 15 total = 80% completion
   - Tests mixed task statuses

4. **testTasksPerHourCalculation** - Verifies throughput metric
   - Formula: `COUNT(completed_tasks) / (total_seconds / 3600)`
   - Example: 5 tasks completed in 5 hours = 1 task/hour
   - Tests realistic workflow scenarios

5. **testProductivityScoreCalculation** - Verifies composite score
   - Formula: `(completion_rate * 0.5) + ((100 - error_rate) * 0.5)`
   - Example: 90% completion + 10% error = (90×0.5) + (90×0.5) = 90/100
   - Range: 0-100, higher is better

#### Report Aggregation Tests
6. **testDailyMetricsAggregation** - Aggregates daily metrics
   - Summarizes all tasks for a day
   - Counts distinct employees
   - Calculates daily rates and totals
   - Supports multiple employees

7. **testEmployeeLeaderboard** - Ranks employees by performance
   - Sorts by completion rate, error rate, etc.
   - Includes productivity scores
   - Returns top N employees
   - Tests with 3 employees of varying performance

#### Filtering Tests
8. **testDateRangeFiltering** - Filters metrics by date
   - Single day filtering (today)
   - Multi-day range filtering (10 days)
   - Handles date boundaries correctly
   - Supports all date formats

9. **testPackingMetricsCalculation** - Calculates packing metrics
   - Similar formulas as picking
   - Includes quality issue rate
   - Tracks items per task
   - Handles packing-specific fields

10. **testOverallMetrics** - Combines picking + packing metrics
    - Aggregates across both task types
    - Total tasks = picking + packing
    - Error rates weighted appropriately
    - Productivity score includes both

#### Edge Case Tests
11. **testZeroAndNullValues** - Handles zero/null safely
    - Zero tasks returns 0% for all rates
    - No division by zero errors
    - NULL values handled gracefully
    - Returns valid data structure

12. **testSingleTaskMetrics** - Calculates for single task
    - 1 completed task = 100% completion
    - Correct duration captured
    - Error rate calculated properly
    - Productivity score accurate

13. **testLargeDatasetMetrics** - Processes 100+ records
    - 150 tasks successfully processed
    - 10% error rate maintained
    - Calculations accurate at scale
    - No performance degradation

14. **testDivisionByZeroProtection** - Prevents math errors
    - Zero tasks doesn't crash
    - Returns safe default values
    - No exception thrown
    - All fields populated

15. **testBusinessDaysCalculation** - Calculates working days
    - Excludes weekends
    - Includes period information
    - Accurate day counting
    - Supports all date ranges

#### Authorization Tests
16. **testAdminCanAccessAllReports** - Admin full access
    - Admin sees all employee metrics
    - Can view any date range
    - No restrictions on data access
    - All reports accessible

17. **testSupervisorTeamReports** - Supervisor team access
    - Can see team leaderboard
    - Filtered to team scope
    - Can view multiple employees
    - Supports team analytics

18. **testEmployeeSelfMetricsOnly** - Employee self-access
    - Employees get their own metrics
    - Employee_id matches requestor
    - Separated from other employees
    - Permission enforced

#### Security Tests
19. **testSqlInjectionInDateFilter** - Prevents SQL injection in filters
    - Malicious date strings rejected
    - Uses parameterized queries
    - No code execution from input
    - Example blocked: `2026-10-01' OR '1'='1`

20. **testXssPrevention** - Prevents XSS in output
    - Returns safe array structures
    - No HTML/JavaScript in output
    - Proper data encoding
    - Safe for web display

---

## How to Run Tests

### Option 1: Using Existing Test Framework

If you have the PhaseOneWorkflowTests structure set up:

```php
<?php
// Add to your test runner
require_once 'tests/ImportTests.php';
require_once 'tests/ReportTests.php';

$importTests = new \WPMS\Tests\ImportTests($pdo);
$reportTests = new \WPMS\Tests\ReportTests($pdo);

$importResults = $importTests->runAllTests();
$reportResults = $reportTests->runAllTests();

$importTests->printSummary($importResults);
$reportTests->printSummary($reportResults);
?>
```

### Option 2: PHPUnit Integration

If using PHPUnit, create test wrapper:

```php
<?php
use PHPUnit\Framework\TestCase;
use WPMS\Tests\ImportTests;

class ImportTestsUnit extends TestCase
{
    private $importTests;

    protected function setUp(): void
    {
        global $pdo;
        $this->importTests = new ImportTests($pdo);
    }

    public function testValidCsvParsing()
    {
        $result = $this->importTests->testValidCsvParsing();
        $this->assertEquals('PASS', $result['status']);
    }

    // Add test methods for each test...
}
?>
```

### Option 3: CLI Test Runner

```php
<?php
require_once 'config/database.php';
require_once 'src/modules/import/ImportService.php';
require_once 'tests/ImportTests.php';

$importTests = new \WPMS\Tests\ImportTests($pdo);
$results = $importTests->runAllTests();

// Display results
foreach ($results as $name => $result) {
    $status = $result['status'];
    $message = $result['message'];
    echo "[{$status}] {$name}: {$message}\n";
}

$importTests->printSummary($results);
?>
```

---

## Test Data Requirements

### Database Setup

Tests require these tables to exist:
- `users` (with at least 1 admin user id=1)
- `warehouses` (with id=1)
- `departments` (with id=1, warehouse_id=1)
- `employees`
- `picking_tasks`
- `packing_tasks`
- `imports`
- `import_details`

### Initial Data

Minimum required:
```sql
-- Admin user (if not exists)
INSERT INTO users (id, email, password_hash, full_name, role, created_at)
VALUES (1, 'admin@test.local', 'hash', 'Admin User', 'admin', NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Warehouse
INSERT INTO warehouses (id, name, created_by, created_at)
VALUES (1, 'Test Warehouse', 1, NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Department
INSERT INTO departments (id, warehouse_id, name, created_by, created_at)
VALUES (1, 1, 'Picking', 1, NOW())
ON DUPLICATE KEY UPDATE id=id;
```

---

## Expected Results

### Import Tests

**Pass Rate Target:** 100% (25/25)

- CSV parsing: Should pass all tests
- Validation: Should catch all invalid formats
- Security: Should prevent injection attacks
- Large files: Should handle 150+ records

**Common Issues:**
- Missing validation rules: Ensure all regex patterns are correct
- Duplicate detection timing: May need adjustment based on import workflow
- Template format: Verify headers match Unicommerce output

### Report Tests

**Pass Rate Target:** 100% (20/20)

- Metrics: All formulas must be accurate
- Calculations: Results must match expected values
- Filtering: Date ranges must work correctly
- Security: All SQL/XSS prevention must pass

**Common Issues:**
- Business days calculation: Verify timezone handling
- Aggregation: Ensure NULL handling in calculations
- Permission checks: Implement in controllers, not calculator

---

## Coverage Summary

| Component | Tests | Coverage |
|-----------|-------|----------|
| CSV Parsing | 5 | Headers, formats, structure |
| Validation | 8 | Field types, lengths, patterns |
| Security | 4 | SQL injection, CSV injection, XSS |
| Large Files | 1 | 150+ record performance |
| Templates | 3 | Generation, formats, content |
| Metrics | 5 | All formulas and calculations |
| Filtering | 4 | Date ranges, dimensions |
| Edge Cases | 5 | Zero/null, single item, scale |
| Authorization | 3 | Admin, supervisor, employee |
| **TOTAL** | **45** | **Core functionality** |

---

## Key Formula References

### Picking Metrics

1. **Average Duration** = SUM(completed_task_seconds) / COUNT(completed_tasks)
2. **Error Rate** = (tasks_with_errors / total_tasks) × 100
3. **Completion Rate** = (completed_tasks / total_tasks) × 100
4. **Tasks/Hour** = completed_tasks / (total_seconds / 3600)

### Packing Metrics

1. **Quality Error Rate** = (tasks_with_issues / total_tasks) × 100
2. **Items/Task** = total_items / completed_tasks

### Overall Metrics

1. **Productivity Score** = (completion_rate × 0.5) + ((100 - error_rate) × 0.5)
2. **Total Tasks** = picking_tasks + packing_tasks

---

## Maintenance Notes

### When to Update Tests

- **New validation rules**: Add test cases for new patterns
- **Formula changes**: Update expected values and examples
- **Schema changes**: Update test data creation queries
- **Performance optimizations**: Add benchmarking tests

### Test Data Cleanup

Tests create temporary data that should be cleaned:

```php
// Manual cleanup
DELETE FROM picking_tasks WHERE picklist_code LIKE 'PT-TEST-%';
DELETE FROM packing_tasks WHERE package_code LIKE 'PKG-TEST-%';
DELETE FROM employees WHERE emp_id LIKE 'TEST-%';

// Or use test isolation
$pdo->beginTransaction();
// Run tests
$pdo->rollBack();
```

### Debugging Failed Tests

1. Check test output for specific assertion
2. Verify test data was created correctly
3. Check database for orphaned records
4. Review error messages for SQL issues
5. Validate formula calculations manually

---

## Next Steps

1. **Run baseline tests** - All should pass initially
2. **Add to CI/CD** - Run tests on every commit
3. **Monitor coverage** - Aim for 100% pass rate
4. **Performance testing** - Add benchmarks for large files
5. **Load testing** - Test with real-world data volumes
6. **Integration testing** - Test with actual Unicommerce data

---

**Last Updated:** October 24, 2026  
**Test Suite Version:** 1.0  
**Status:** Ready for Integration
