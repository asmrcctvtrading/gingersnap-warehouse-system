# Import Module Guide
## Warehouse Personnel Management System (WPMS)

---

## Table of Contents
1. [Overview](#overview)
2. [Import Workflow](#import-workflow)
3. [CSV Format Specifications](#csv-format-specifications)
4. [Field Mappings](#field-mappings)
5. [Data Validation](#data-validation)
6. [Duplicate Detection](#duplicate-detection)
7. [Import Preview](#import-preview)
8. [Error Handling](#error-handling)
9. [Best Practices](#best-practices)
10. [Examples](#examples)
11. [Troubleshooting](#troubleshooting)

---

## Overview

The Import Module allows warehouse managers to bulk upload employee, task, and warehouse data into the WPMS system using CSV files. This feature streamlines data entry, reduces manual work, and ensures consistent data formatting across the organization.

### Key Features
- **Batch Import**: Upload multiple records at once
- **Data Validation**: Automatic checking for data quality before import
- **Duplicate Detection**: Prevents duplicate entries in the system
- **Preview Mode**: Review data before committing to database
- **Error Reporting**: Detailed feedback on validation failures
- **Rollback Support**: Failed imports do not affect existing data

### Supported Import Types
- Employee records
- Task assignments
- Warehouse locations
- Department information
- Shift schedules

---

## Import Workflow

### Step-by-Step Process

#### Step 1: Prepare Your CSV File
1. Organize your data in a spreadsheet application (Excel, Google Sheets, LibreOffice Calc)
2. Ensure all required fields are present
3. Validate data format according to specifications (see [Data Validation](#data-validation))
4. Export the file as CSV format (.csv)

#### Step 2: Access the Import Module
1. Log in to WPMS with manager/admin credentials
2. Navigate to: **Settings** → **Data Management** → **Import Data**
3. Click the **"Select Import Type"** dropdown
4. Choose the data type you want to import

#### Step 3: Select and Upload File
1. Click **"Choose File"** button
2. Navigate to your prepared CSV file
3. Click **"Upload"**
4. Wait for file to be processed (progress indicator will show)

#### Step 4: Review Import Preview
1. System displays a preview of detected records
2. Review sample data from the first 5 rows
3. Check column mapping accuracy
4. Note any warnings or validation issues
5. Proceed to next step if all data appears correct

#### Step 5: Handle Duplicates (if any)
1. System identifies potential duplicates
2. Review duplicates list with matching field information
3. Choose action for each duplicate:
   - **Skip**: Don't import this record
   - **Update**: Overwrite existing record with new data
   - **Keep Both**: Import as new record (not recommended)

#### Step 6: Review Validation Results
1. System performs comprehensive validation checks
2. Review error summary report
3. Fix identified errors in your source file
4. Upload corrected file or proceed with import

#### Step 7: Confirm and Import
1. Click **"Confirm Import"** to proceed
2. Review final confirmation dialog with record count
3. Click **"Execute Import"** to commit to database
4. Wait for completion message

#### Step 8: Review Import Report
1. System generates detailed import report
2. Report shows:
   - Total records processed
   - Successfully imported records
   - Failed records with reasons
   - Duplicate records handled
   - Processing time
3. Download report as PDF or CSV for record-keeping

---

## CSV Format Specifications

### General Requirements

| Requirement | Specification |
|------------|---------------|
| File Format | Comma-separated values (.csv) |
| Encoding | UTF-8 (required for special characters) |
| Row Limit | Maximum 10,000 records per file |
| Column Order | Must match field order in template |
| Header Row | Required (first row must contain field names) |
| Empty Rows | Not allowed (will cause validation error) |
| File Size | Maximum 5 MB |

### Encoding Note
- **Always save as UTF-8** to support special characters (ñ, é, ç, etc.)
- In Excel: File → Save As → Format: CSV UTF-8 (Comma delimited)
- In Google Sheets: File → Download → Comma-separated values (.csv)

### Line Endings
- Use **LF (Unix)** line endings if available
- Windows CRLF is acceptable but not preferred
- Mac CR may cause parsing issues

---

## Field Mappings

### Employee Import

```
CSV Column | Database Field | Data Type | Required | Max Length
-----------|---------------|-----------|----------|------------
emp_id    | employee_id   | String    | YES      | 20
emp_name  | full_name     | String    | YES      | 100
email     | email_address | String    | NO       | 100
phone     | phone_number  | String    | NO       | 15
dept_id   | department_id | String    | YES      | 20
warehouse | warehouse_id  | String    | YES      | 20
role      | job_title     | String    | YES      | 50
status    | employment_status | String | YES     | 20
hire_date | hire_date     | Date      | NO       | -
manager   | manager_id    | String    | NO       | 20
```

### Task Import

```
CSV Column | Database Field | Data Type | Required | Max Length
-----------|---------------|-----------|----------|------------
task_id   | task_id       | String    | YES      | 30
emp_id    | assigned_to   | String    | YES      | 20
task_type | task_type     | String    | YES      | 50
location  | location      | String    | YES      | 100
priority  | priority_level| String    | YES      | 10
due_date  | due_date      | Date      | YES      | -
description | description | Text      | NO       | 500
quantity  | quantity      | Integer   | NO       | -
```

### Warehouse Location Import

```
CSV Column | Database Field | Data Type | Required | Max Length
-----------|---------------|-----------|----------|------------
location_id | location_id  | String    | YES      | 20
section   | section_code  | String    | YES      | 10
aisle     | aisle_number  | Integer   | YES      | -
shelf     | shelf_number  | Integer   | YES      | -
bin       | bin_letter    | String    | YES      | 5
capacity  | capacity_units| Integer   | YES      | -
category  | category      | String    | NO       | 50
active    | is_active     | Boolean   | YES      | -
```

---

## Data Validation

### Validation Rules by Data Type

#### String Fields
- **Trim whitespace**: Leading/trailing spaces are automatically removed
- **Maximum length**: Enforced per field specifications
- **Allowed characters**: Alphanumeric, hyphens, underscores, spaces
- **Special characters**: Allowed in name fields (ñ, é, etc.) if UTF-8 encoded
- **Case sensitivity**: Field names are case-insensitive; values are case-sensitive

#### Numeric Fields
- **Integer**: Must be whole numbers (no decimals)
- **Decimal**: Must use period (.) as decimal separator
- **Range**: Values must be within valid business ranges
- **Negative values**: Not allowed unless specified

#### Date Fields
- **Format**: YYYY-MM-DD (ISO 8601)
- **Range**: Dates must be realistic (within 50 years of today)
- **Required dates**: Cannot be empty or 0000-00-00

#### Email Fields
- **Format**: user@domain.com
- **Validation**: Must contain @ and valid domain
- **Case**: Converted to lowercase automatically
- **Uniqueness**: Checked against existing records

#### Phone Fields
- **Format**: Digits only (leading +, dashes, spaces removed)
- **Length**: 7-15 digits after cleaning
- **Examples**: +63-9xx-xxx-xxxx, (02) xxxx-xxxx

#### Boolean Fields
- **Accepted values**: YES/NO, TRUE/FALSE, 1/0, Y/N
- **Case insensitive**: YES = yes = Y = true = 1
- **Default**: FALSE if empty

#### Choice Fields
- **Allowed values**: Only predefined options accepted
- **Case sensitive**: Must match exactly (e.g., "Active" not "active")
- **Common fields**: Status, Priority, Role, Department

### Validation Error Messages

| Error Code | Message | Cause | Solution |
|-----------|---------|-------|----------|
| REQUIRED_FIELD_MISSING | Required field {field} is empty | Field marked required is blank | Provide value in CSV |
| INVALID_DATA_TYPE | Field {field} contains invalid {type} | Wrong data type provided | Check format specification |
| VALUE_TOO_LONG | Field {field} exceeds max length {max} | Value longer than allowed | Truncate to {max} characters |
| INVALID_EMAIL | Field email contains invalid email address | Email format incorrect | Use format: user@domain.com |
| INVALID_PHONE | Field phone contains invalid phone number | Phone format incorrect | Use format: +country-number |
| INVALID_DATE | Field {field} contains invalid date | Date not in YYYY-MM-DD format | Use format: YYYY-MM-DD |
| INVALID_CHOICE | Field {field} value not in allowed choices | Value not in predefined list | Choose from: {options} |
| INVALID_CHARACTER | Field {field} contains illegal character | Non-allowed character present | Remove special characters |
| DUPLICATE_REFERENCE | Field {field} references non-existent record | Foreign key reference invalid | Verify referenced record exists |
| RANGE_ERROR | Field {field} value outside acceptable range | Numeric value out of bounds | Use value between {min}-{max} |

---

## Duplicate Detection

### Detection Strategy

The system uses multi-field matching to identify potential duplicates:

#### Employee Duplicates
```
Primary Match: employee_id
Secondary Match: email + phone
Tertiary Match: full_name + hire_date
```

**Match Logic**: If primary matches, record is DUPLICATE. If secondary matches (both fields), record is DUPLICATE. If tertiary matches (both fields), record is WARNING.

#### Task Duplicates
```
Primary Match: task_id
Secondary Match: emp_id + due_date + task_type
```

**Match Logic**: Primary = DUPLICATE. Secondary match with 90% field similarity = WARNING.

#### Location Duplicates
```
Primary Match: location_id
Secondary Match: section + aisle + shelf + bin
```

**Match Logic**: Exact match on all secondary fields = DUPLICATE.

### Duplicate Actions

| Action | Behavior | When to Use |
|--------|----------|------------|
| **Skip** | Do not import this record; existing data unchanged | When import data is outdated or incorrect |
| **Update** | Overwrite all fields with new values from import | When import contains latest information |
| **Keep Both** | Import as new record with new primary key | When records are actually different (not recommended) |
| **Merge** | Keep existing record; merge new non-empty fields | When import contains partial updates |

### Example: Duplicate Detection

**Existing Record:**
```
emp_id: E001
emp_name: John Smith
email: john.smith@company.com
```

**Import Record:**
```
emp_id: E001
emp_name: John David Smith
email: john.smith@company.com
```

**Detection Result**: DUPLICATE (primary match on emp_id)

**Recommended Action**: UPDATE (to capture name change)

---

## Import Preview

### Preview Display

The preview shows:
- **First 5 rows** of detected data
- **Column headers** with field names
- **Data type** for each column
- **Sample values** from your file
- **Validation status** (pass/warning/error)
- **Column mapping** (CSV column → Database field)

### What to Check in Preview

1. **Correct headers**: Verify field names match your data
2. **Column mapping**: Ensure columns aligned correctly
3. **Data types**: Check if numeric fields show numbers, dates show dates
4. **Encoding issues**: Look for garbled characters (indicates encoding problem)
5. **Row count**: Confirm total records to be imported

### Preview Example

```
Import Preview: Employee Records
=================================

Total Records Detected: 247
Columns Detected: 9

Preview Data (First 5 rows):
┌──────┬──────────────┬────────────────────┬──────────────────┐
│ emp_id│ emp_name    │ email              │ dept_id          │
├──────┼──────────────┼────────────────────┼──────────────────┤
│ E001 │ John Smith   │ john.smith@...     │ DEPT-SALES       │
│ E002 │ Jane Doe     │ jane.doe@...       │ DEPT-OPS         │
│ E003 │ Bob Johnson  │ bob.johnson@...    │ DEPT-WAREHOUSE   │
│ E004 │ Alice Brown  │ alice.brown@...    │ DEPT-FINANCE     │
│ E005 │ Charlie Davis│ charlie.davis@...  │ DEPT-IT          │
└──────┴──────────────┴────────────────────┴──────────────────┘

Warnings: 2 records have missing optional fields
Errors: 0 records have validation errors
```

---

## Error Handling

### Error Categories

#### Critical Errors (Import Blocked)
- **Invalid file format**: File is not valid CSV
- **Missing required fields**: Column headers not found
- **Data corruption**: File cannot be read
- **Encoding issues**: File not UTF-8 encoded

**Recovery**: Fix file and re-upload

#### Record-Level Errors (Partial Import)
- **Validation failures**: Individual records have invalid data
- **Duplicate conflicts**: Unresolved duplicates prevent import
- **Reference errors**: Missing foreign key references

**Recovery**: Review error report, fix records, re-upload or skip problematic records

#### System Errors (Rare)
- **Database connection failed**: System cannot connect to database
- **Disk space exceeded**: System out of storage
- **Transaction timeout**: Import took too long

**Recovery**: Contact system administrator

### Error Report

After import attempt, system generates error report showing:

```
Import Error Report
===================
File: employees_2026-10.csv
Import Type: Employees
Timestamp: 2026-10-24 14:32:15

SUMMARY
-------
Total Records: 247
Successfully Imported: 242
Failed Records: 5
Warnings: 8

FAILED RECORDS DETAIL
--------------------

Row 15 - emp_id: E015
  Error: INVALID_EMAIL
  Value: "john.smith@.com"
  Message: Email is missing domain name
  Action: Fix email and re-import

Row 42 - emp_id: E042
  Error: REQUIRED_FIELD_MISSING
  Field: dept_id
  Message: Department ID is required
  Action: Provide department ID and re-import

Row 58 - emp_id: E058
  Error: DUPLICATE_REFERENCE
  Value: "MGR001"
  Message: Referenced manager (MGR001) does not exist
  Action: Create manager record first, or update manager_id

Row 103 - emp_id: E103
  Error: VALUE_TOO_LONG
  Field: email (max: 100)
  Value: "very.long.email.address.with.many.characters@subdomainname.example.com"
  Action: Use shorter email address

Row 234 - emp_id: E234
  Error: INVALID_DATE
  Field: hire_date
  Value: "10/24/2026"
  Message: Date must be in format YYYY-MM-DD
  Action: Use format: 2026-10-24

WARNINGS (Non-blocking)
----------------------

Row 7 - emp_id: E007
  Warning: OPTIONAL_FIELD_MISSING
  Field: phone
  Message: Phone number not provided
  Note: Employee can be imported but will have no phone on file

Row 22 - emp_id: E022
  Warning: DUPLICATE_DETECTED
  Match Type: Email
  Existing: E021 (John Smith)
  Message: Similar employee found - verify not duplicate
  Suggestion: Review and choose action (skip/update/merge)

NEXT STEPS
----------
1. Review failed records above
2. Update source CSV file with corrections
3. Re-upload corrected file
4. System will import previously failed records only (if re-uploading subset)
```

### Error Recovery Workflow

```
Import Attempt
      ↓
Validation Check
      ↓
  Errors Found? ──→ YES ──→ Generate Error Report
      ↓                          ↓
      NO                    Review Errors
      ↓                          ↓
  Continue                  Fix Source File
      ↓                          ↓
Database Import                Re-upload
      ↓                          ↓
Success! ←─────────────────────┘
```

---

## Best Practices

### Before You Import

1. **Backup existing data**
   - Always backup database before large imports
   - Use: Settings → Maintenance → Backup Database

2. **Validate your CSV file**
   - Use built-in CSV validator tool
   - Check file encoding (UTF-8)
   - Verify row counts in spreadsheet vs. file

3. **Test with small file first**
   - Create test import with 10-20 records
   - Review results before importing full dataset
   - Fix any issues found in test

4. **Prepare clean data**
   - Remove extra spaces/tabs
   - Standardize date formats (YYYY-MM-DD only)
   - Use consistent capitalization for choice fields
   - Verify email addresses and phone numbers

5. **Check for duplicates**
   - Use spreadsheet features to find duplicates
   - Decide how to handle (skip, update, or merge)
   - Document decisions for consistency

### During Import

1. **Review preview carefully**
   - Check first 5 rows for correct column mapping
   - Look for encoding issues (garbled text)
   - Verify data type recognition

2. **Address all errors**
   - Fix validation errors in source file
   - Resolve duplicate conflicts before proceeding
   - Don't ignore warnings

3. **Import during off-hours**
   - Schedule large imports outside business hours
   - Avoid importing while staff use system
   - Plan for 1-2 hours of system load

4. **Keep import file**
   - Don't delete uploaded CSV file
   - Use for audit trail and troubleshooting
   - Archive with import report

### After Import

1. **Review import report**
   - Download and save PDF report
   - Document number of records imported
   - Note any errors or warnings

2. **Spot-check data**
   - Verify sample records in database
   - Check calculations and dependencies
   - Test reports with new data

3. **Communicate changes**
   - Notify affected departments of updates
   - Provide list of new employee IDs
   - Explain any data corrections made

4. **Monitor for issues**
   - Watch for unusual reports or data
   - Address employee complaints about data
   - Fix errors quickly if found

---

## Examples

### Example 1: Valid Employee Import CSV

```csv
emp_id,emp_name,email,phone,dept_id,warehouse,role,status,hire_date,manager
E001,Juan dela Cruz,juan.delacruz@warehouse.com,+63-9171234567,DEPT-SALES,WH-MANILA,Picking Lead,Active,2024-01-15,MGR001
E002,Maria Santos,maria.santos@warehouse.com,+63-9189876543,DEPT-SALES,WH-MANILA,Picker,Active,2024-03-20,E001
E003,Pedro Garcia,pedro.garcia@warehouse.com,+63-9175551234,DEPT-OPS,WH-MANILA,Packer,Active,2024-02-10,MGR002
E004,Rosa Mendoza,rosa.mendoza@warehouse.com,+63-9179998888,DEPT-WAREHOUSE,WH-CEBU,Inventory Clerk,Active,2023-06-01,MGR003
E005,Carlos Reyes,carlos.reyes@warehouse.com,,DEPT-IT,WH-MANILA,Systems Admin,Active,2024-01-01,MGR004
```

**Notes:**
- All required fields present
- Dates in YYYY-MM-DD format
- Phone in +63-91xxxxxxxx format
- E005 has empty phone (optional field - OK)
- Status values are standardized

### Example 2: Invalid Employee Import CSV (with errors)

```csv
emp_id,emp_name,email,phone,dept_id,warehouse,role,status,hire_date,manager
E101,Antonio Silva,antonio.silva@.com,09171234567,DEPT-SALES,WH-MANILA,Picking Lead,ACTIVE,01/15/2024,
E102,Lucia Fernandez,,+63-9189876543,DEPT-OPS,,Packer,Active,2024-03-20,E101
E103,Diego Moreno,diego.moreno@warehouse.com with very long text here making it exceed maximum allowed length,+63-9175551234,INVALID_DEPT,WH-MANILA,Picker,Active,2024-02-10,NONEXISTENT
E104,Isabella Rossi,isabella.rossi@warehouse.com,INVALID_PHONE,DEPT-WAREHOUSE,WH-CEBU,Packer,Unknown Status,2024-13-45,MGR003
```

**Errors Identified:**

| Row | Field | Error | Fix |
|-----|-------|-------|-----|
| E101 | email | Missing domain | Use: antonio.silva@warehouse.com |
| E101 | hire_date | Wrong format | Use: 2024-01-15 (YYYY-MM-DD) |
| E101 | manager | Empty required | Provide manager ID |
| E102 | email | Empty required | Provide email address |
| E102 | warehouse | Empty required | Provide warehouse |
| E103 | email | Too long (>100 chars) | Shorten to <100 characters |
| E103 | dept_id | Invalid choice | Use valid department ID |
| E103 | manager | Non-existent reference | Create MGR003 first or remove |
| E104 | phone | Invalid format | Use: +63-9179998888 |
| E104 | status | Invalid choice | Use: Active, Inactive, or On Leave |
| E104 | hire_date | Invalid date | Use: 2024-02-10 (month/day valid) |

### Example 3: Task Import CSV

```csv
task_id,emp_id,task_type,location,priority,due_date,description,quantity
TSK-2026-0001,E001,Picking,Section-A1-Shelf-3,HIGH,2026-10-25,Pick 50 units of SKU-12345,50
TSK-2026-0002,E002,Packing,Section-B2-Shelf-1,MEDIUM,2026-10-25,Pack boxes for order #ORD-5678,30
TSK-2026-0003,E003,Inventory,Section-C3-Shelf-4,LOW,2026-10-26,Count stock and verify quantities,200
TSK-2026-0004,E001,Picking,Section-A1-Shelf-1,HIGH,2026-10-24,Urgent: Pick special order items,25
TSK-2026-0005,E004,Packing,Section-D1-Shelf-2,MEDIUM,2026-10-25,Pack return shipment,15
```

### Example 4: Warehouse Location Import CSV

```csv
location_id,section,aisle,shelf,bin,capacity,category,active
LOC-A1-001,A,1,1,A,500,Electronics,YES
LOC-A1-002,A,1,1,B,500,Electronics,YES
LOC-A1-003,A,1,2,A,500,Electronics,YES
LOC-B2-001,B,2,1,A,750,Apparel,YES
LOC-B2-002,B,2,1,B,750,Apparel,YES
LOC-C3-001,C,3,3,A,1000,Furniture,NO
LOC-C3-002,C,3,3,B,1000,Furniture,YES
```

---

## Troubleshooting

### Common Issues and Solutions

#### "File Upload Failed"

**Symptom**: Upload button shows error, file doesn't process

**Causes & Solutions**:
1. **File too large** (>5 MB)
   - Solution: Split CSV into multiple smaller files
   
2. **File format incorrect** (not .csv)
   - Solution: Save as CSV (comma-delimited) format
   
3. **Browser issue**
   - Solution: Try different browser, clear cache, refresh page
   
4. **Network timeout**
   - Solution: Check internet connection, try again

---

#### "Encoding Error / Garbled Characters"

**Symptom**: Special characters (ñ, é, ç) appear as ????? or Â

**Causes & Solutions**:
1. **File not saved as UTF-8**
   - Solution (Excel): File → Save As → CSV UTF-8 (Comma delimited)
   - Solution (Google Sheets): File → Download → CSV (current sheet)
   - Solution (LibreOffice): Save As → Format: CSV UTF-8
   
2. **Accidental character replacement**
   - Solution: Retype special characters in spreadsheet
   
3. **Copy-paste from formatted document**
   - Solution: Type data directly or paste as text only

---

#### "Validation Errors on Import"

**Symptom**: Import blocked with "X validation errors found"

**Solutions**:
1. **Download error report** and review each error
2. **Fix identified issues** in source CSV file
3. **Re-upload corrected file**
4. **Use CSV validator tool** before re-uploading

---

#### "Duplicate Records Not Detected"

**Symptom**: System imports records you expected to be flagged as duplicates

**Possible Reasons**:
1. **Fields don't match exactly**
   - Check for extra spaces or capitalization differences
   - Solution: Ensure exact field matching before import

2. **Primary key differs**
   - Example: emp_id E001 vs E1 (different values)
   - Solution: Use consistent primary key format

3. **Duplicate detection only for certain fields**
   - Solution: Check duplicate detection rules for your import type

---

#### "Duplicate Warning But Record Actually Different"

**Symptom**: System flags record as duplicate but it's a different person/task

**Solution**: Choose **"Keep Both"** action to import as new record

**Note**: Review duplicate detection logic to ensure correct matching

---

#### "Import Hangs / Takes Too Long"

**Symptom**: Import progress stuck, system appears frozen

**Causes & Solutions**:
1. **Very large file (>5000 records)**
   - Solution: Split into multiple smaller imports
   
2. **System busy**
   - Solution: Wait or schedule import during off-hours
   
3. **Browser timeout**
   - Solution: Import file may still process in background; check import history
   
4. **System resources low**
   - Solution: Contact administrator to check system resources

---

#### "Foreign Key Reference Error"

**Symptom**: Error message: "Referenced manager does not exist" or similar

**Cause**: Import references record that doesn't exist in system

**Solution**:
1. **Create referenced record first**
   - Example: If importing employees with manager_id MGR001, create MGR001 first
   
2. **Or remove reference field**
   - Leave manager_id empty if manager doesn't exist yet
   
3. **Import in correct order**
   - Import parents (managers) before children (employees)

---

#### "Import Succeeded But Data Not Visible"

**Symptom**: Import report shows success but new records don't appear in system

**Solutions**:
1. **Refresh page**: Browser may be showing cached data
2. **Apply filters**: Filters may be hiding new records
3. **Wait for sync**: System may still syncing data (wait 30 seconds)
4. **Check import report**: Verify records actually imported (not skipped as duplicates)
5. **Check data**: Search for specific record using search function

---

#### "Can't Create Import Type I Need"

**Symptom**: Desired import type not available in dropdown menu

**Supported Import Types**:
- Employee records
- Task assignments
- Warehouse locations
- Department information
- Shift schedules

**If missing**: Contact system administrator to request new import type

---

### Frequently Asked Questions

**Q: Can I import partial data (only some fields)?**
A: Yes. Optional fields can be left empty. Required fields must be provided.

**Q: What happens if import fails halfway through?**
A: No records are committed if validation fails. You can fix issues and re-import without duplication concerns.

**Q: Can I import 50,000 employee records?**
A: Maximum 10,000 per file. Split into multiple files: 10,000 + 10,000 + 10,000 + 10,000 + 10,000 = 50,000.

**Q: How often can I import?**
A: No limit. You can import as frequently as needed.

**Q: Do imports replace or add data?**
A: By default, imports ADD records. Duplicates can UPDATE if you choose that action.

**Q: Can I schedule automatic imports?**
A: Not currently. Imports are manual. Contact administrator for integration options.

**Q: How long is import history kept?**
A: 90 days. Download important reports immediately.

---

## Related Documentation

- [METRICS_DEFINITIONS.md](METRICS_DEFINITIONS.md) - Metric calculations
- [REPORTING_MODULE.md](REPORTING_MODULE.md) - Dashboard and reports
- [User Guide](USER_GUIDE.md) - General system usage
- [Admin Guide](ADMIN_GUIDE.md) - System administration

---

**Last Updated**: October 24, 2026
**Version**: 1.0
**Document ID**: IMPORT-GUIDE-001
