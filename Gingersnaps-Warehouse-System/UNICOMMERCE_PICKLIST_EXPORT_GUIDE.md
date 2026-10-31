# Unicommerce Uniware Picklist Export Structure Guide
**Date:** October 24, 2026  
**Project:** Gingersnaps Warehouse System  
**Source:** Unicommerce Uniware - Tasks > Export Configurations > Picklist Export

---

## Executive Summary

The Unicommerce Picklist Export provides comprehensive picking task data including:
- Picklist identification and status tracking
- Item and shipment details
- Warehouse location information  
- Picker assignment and audit trail
- Order and SKU mappings

---

## 1. EXPORT CONFIGURATION DETAILS

### Export Type
**Name:** Picklist Export  
**Availability:** Tasks > Export Configurations > Picklist Export  
**Format:** CSV/Excel (configurable)  
**Column Selection:** All columns available for export

### Available Export Columns

| Column Name | Data Type | Description | Example |
|---|---|---|---|
| Picklist Code | String (PK) | Unique picklist identifier | PT-60185, PT-SAMPLE-001 |
| Sale Order Code | String (FK) | Associated sale order ID | SO-112191, SO-112192 |
| Sale Order Item Code | String | Specific item line in order | SO-112191-001, SO-112192-001 |
| Item SkuCode | String | Product SKU identifier | SKU-ASMR-001, SKU-ASMR-002 |
| Item Type Name | String | Product category/type | Surveillance Camera, NVR System |
| Shipping Package Code | String | Packaging unit reference | PKG-001, PKG-002 |
| Shelf Code | String | Warehouse shelf location | SHELF-A1, SHELF-B2, SHELF-C3 |
| Batch Code | String | Inventory batch identifier | BATCH-2026-001 |
| Picklist Status | Enum | Task state at picklist level | CREATED, PENDING, IN_PROGRESS, COMPLETED |
| Item Status | Enum | State of individual item | In Progress, Dispatched, Cancelled |
| Picklist Created By | String (FK: User) | User who generated task | allocator01@gingersnaps.com |
| Picked By | String (FK: User) | Assigned picker/operator | allocator02@gingersnaps.com |
| Created Date | DateTime | Task creation timestamp | 2026-09-01T08:30:00Z |
| Updated Date | DateTime | Last modification timestamp | 2026-09-01T10:15:00Z |
| EAN | String | Product barcode (European Article Number) | 8901234567890 |
| Operations | String | Available actions on record | Update/Complete, View/Assign |

---

## 2. PICKING TYPES / TASK TYPES

### Identified Picking Types

Based on Unicommerce data model analysis, the following picking task types exist:

| Type Code | Description | Use Case |
|---|---|---|
| PUTAWAY_GATEPASS_ITEM | Putaway task for gatepass items | Receiving/inbound gatepass entry |
| STANDARD_PICKING | Regular order picking | Outbound shipment picking |
| BATCH_PICKING | Multi-order batch picking | High-volume batch operations |
| CYCLE_COUNT | Inventory cycle count task | Stock verification |

**Field Reference:** The task type is typically stored in the `Picklist Status` or as metadata in the picklist record.

---

## 3. PACKING STRUCTURE & TYPES

### Packing Data Organization

Packing information in Unicommerce is organized hierarchically and relates to the shipment/order structure:

```
Order (Sale Order Code)
  ↓
  Item Line (Sale Order Item Code)
    ↓
    Shipping Package (Shipping Package Code)
      ↓
      Packing Details (Shelf Code, Batch Code, Physical Location)
```

### Packing-Related Fields

| Field | Type | Purpose | Example |
|---|---|---|---|
| **Shipping Package Code** | String | Groups items into shipping packages | PKG-001, PKG-002, PKG-003 |
| **Shelf Code** | String | Physical location of item in warehouse | SHELF-A1, SHELF-B2, SHELF-C3 |
| **Batch Code** | String | Inventory batch/lot identification | BATCH-2026-001 |
| **Item SkuCode** | String | Product variant identifier | SKU-ASMR-001 |
| **Shipping Package Code** | String | Package unit for consolidated picking | PKG-001 (may contain multiple SKUs) |

### Packing Types (Inferred from System)

While not explicitly exposed as separate "packing types," the system supports:

| Packing Method | Description |
|---|---|
| **Single Item Pack** | One product per package | 
| **Consolidated Pack** | Multiple items in one package |
| **Batch Pack** | Items from same batch grouped |
| **Shelf-Based Pack** | All items from one shelf location |

---

## 4. STATUS WORKFLOW

### Picklist Status Values
```
CREATED → PENDING → IN_PROGRESS → COMPLETED
```

- **CREATED** - Picklist generated, awaiting assignment
- **PENDING** - Assigned to picker, waiting to start
- **IN_PROGRESS** - Actively being picked
- **COMPLETED** - All items picked, task finished

### Item Status Values
```
In Progress → Dispatched → [Cancelled/Returned/Replaced/Reshipped]
```

- **In Progress** - Item is being picked
- **Dispatched** - Item picked and dispatched
- **Cancelled** - Item cancelled
- **Returned** - Item returned
- **Replaced** - Item replaced
- **Reshipped** - Item reshipped

---

## 5. FILTER FIELDS IN EXPORT CONFIGURATION

The export configuration allows filtering by:

| Filter Name | Type | Purpose |
|---|---|---|
| **Status** | Enum Dropdown | Filter by picklist status (CREATED/PENDING/IN_PROGRESS/COMPLETED) |
| **Picklist No.** | Text Input | Filter by picklist code pattern |
| **Item Type SkuCode** | Text Input | Filter by product SKU |
| **Batch Code Contains** | Text Input | Filter by batch code |
| **User Name Contains** | Text Input | Filter by picker/user name |
| **In Date Range** | Date Range | Filter by creation date |
| **Updated In Date Range** | Date Range | Filter by modification date |

---

## 6. DATA MODEL RELATIONSHIPS

### Key Relationships
```
User (Picker)
  ├── has many Picklists (picked_by)
  └── has many Picklists (created_by/generated_by)

Picklist (Task)
  ├── belongs to User (picked_by)
  ├── belongs to User (created_by)
  ├── has one Sale Order (via Sale Order Code)
  └── has many Order Items

Sale Order
  ├── has many Order Items (Line Items)
  ├── has many Shipping Packages
  └── has many Picklists

Order Item
  ├── has SKU
  ├── has Shelf Location
  ├── has Batch Code
  └── has Status
```

---

## 7. SAMPLE EXPORT DATA

See accompanying file: `unicommerce_picklist_export_structure.csv`

Sample columns with 3 picklist records showing:
- Status progression (PENDING → IN_PROGRESS → COMPLETED)
- Picker assignment (allocator01, allocator02, allocator03)
- Item tracking (SKU, Batch, Location)
- Order references (SO-112191, SO-112192, SO-112193)

---

## 8. EXPORT SCHEDULING & DELIVERY

### Report Schedule Options
- **Manual Export** - Generate on-demand via export configuration
- **Scheduled Export** - Set up recurring exports (daily, weekly, monthly)
- **Email Delivery** - Receive export via email on completion (optional)

### File Formats Supported
- CSV (Comma-Separated Values)
- Excel (XLSX)
- JSON (structured data)

---

## 9. IMPLEMENTATION NOTES FOR GINGERSNAPS

### Field Mappings for Your Database

```
Unicommerce Field          →  Gingersnaps Database Field
─────────────────────────────────────────────────────────
Picklist Code              →  picklist_code (PK)
Sale Order Code            →  shipment_id (FK)
Item SkuCode               →  sku
Shelf Code                 →  warehouse_location
Batch Code                 →  batch_code
Picklist Status            →  status
Item Status                →  item_status
Picklist Created By        →  created_by_user_id (FK)
Picked By (Assigned To)    →  assigned_to_user_id (FK)
Created Date               →  created_date
Updated Date               →  updated_date
```

### Missing Fields (Not in Unicommerce Export)
- Actual start time (picked_on) - inferred from status change
- Actual completion time (completed_on) - inferred from status change
- Duration/TAT - calculated from timestamps
- Picker location/section - available via User record relationship

---

## 10. API INTEGRATION HINTS

### Potential Endpoints (Based on URL Structure)
- `/tasks/export` - Export configuration page
- `/tasks/exportSubscribe` - Subscription management
- `/picklists` - Picklist list/search
- `/dispatch/picklists` - Dispatch view (authorization required)

### Authentication
- Session-based authentication
- User must have "Allocator" or "Admin" role to access picklist data
- Warehouse context required (Local Warehouse 20 for Gingersnaps)

---

## 11. KNOWN LIMITATIONS & OBSERVATIONS

1. **Filter Persistence** - Export configuration filters auto-populate from previous selections
2. **Date Range Requirement** - Date filters require explicit date ranges or must be unchecked
3. **Picklist Type** - Currently shows only task status, not "picking type" as separate field
4. **Packing Types** - Not explicitly listed; inferred from package/batch structure
5. **Real-time Data** - Export may not show live current status; generated on-demand

---

## 12. NEXT STEPS FOR IMPLEMENTATION

1. **Export Template** - Use the provided CSV structure as database schema template
2. **Field Mapping** - Map Unicommerce fields to your Gingersnaps database
3. **Scheduled Sync** - Set up hourly/daily exports from Unicommerce for data sync
4. **Status Sync** - Implement status update push-back to Unicommerce after picking completion
5. **Audit Trail** - Log all imports/syncs with timestamps and change tracking

---

**Document Status:** Complete  
**Last Updated:** October 24, 2026  
**Extracted From:** Gingersnaps Unicommerce Tenant - Live Interface Exploration  
**Reference Files:**
- `unicommerce_picklist_export_structure.csv` - Sample export with column headers and data
- `unicommerce-data-extraction.md` - Previous data model documentation
