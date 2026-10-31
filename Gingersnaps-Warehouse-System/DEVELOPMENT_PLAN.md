# Gingersnaps Warehouse System - Picker Task Tracking
## Development Plan & Implementation Schedule

**Project Start:** October 24, 2026  
**Status:** Phase 1 - In Development

---

## Executive Summary

The Gingersnaps Warehouse System picker identification and task tracking feature enables warehouse managers to assign picking tasks (picklists) to warehouse staff, track task progress in real-time, and monitor task completion. This replaces manual coordination and provides visibility into warehouse operations.

**Key Goals:**
- Real-time task assignment and tracking
- Status workflow management (CREATED → PENDING → IN_PROGRESS → COMPLETED)
- Picker performance visibility
- Simplified warehouse operations

---

## Phase Breakdown

### Phase 1: MVP - Core Database & API (Week 1-2)
**Status:** CURRENT - In Progress

**Deliverables:**
1. ✅ Project structure setup
2. ✅ Database schema (4 tables)
3. ✅ Core PHP API endpoints (create, list, assign, update)
4. ✅ Basic authentication/authorization
5. ✅ Task management UI (assignment, status updates)

**Database Tables:**
- `users` - Picker/warehouse staff profiles
- `picklists` - Picking tasks
- `shipments` - Associated shipments
- `picklist_items` - Line items in tasks

**API Endpoints (Phase 1):**
- POST `/api/picklists/create` - Create new picklist
- GET `/api/picklists/list` - List picklists (with filters)
- POST `/api/picklists/assign` - Assign picklist to picker
- POST `/api/picklists/update-status` - Update task status
- GET `/api/picklists/{id}` - Get picklist details
- GET `/api/pickers/list` - List active pickers
- GET `/api/tasks/assigned` - Get pickers' assigned tasks

**UI Components (Phase 1):**
- Task assignment dashboard (Manager view)
- Picker task list (Picker view)
- Status update form
- Real-time progress tracking

---

### Phase 2: Enhanced Features (Week 3-4)
**Status:** Planned

**Deliverables:**
1. Task analytics and reporting
2. Picker performance metrics
3. TAT (Turnaround Time) tracking
4. Batch task assignment
5. Mobile-responsive UI
6. Performance optimizations

---

### Phase 3: Integration & Optimization (Week 5+)
**Status:** Planned

**Deliverables:**
1. Unicommerce data sync/import
2. Barcode/QR scanning integration
3. Real-time notifications
4. Advanced reporting dashboard
5. System optimization & caching

---

## Technical Stack

| Component | Technology | Notes |
|-----------|-----------|-------|
| **Database** | MySQL 5.7+ | XAMPP compatible |
| **Backend** | PHP 7.4+ | Procedural or OOP (your choice) |
| **Frontend** | Bootstrap 5 + jQuery | XAMPP-compatible |
| **API Format** | REST (JSON) | Standard HTTP methods |
| **Authentication** | Session-based + Token optional | PHP sessions initially |
| **Deployment** | XAMPP (local development) | Ready for production LAMP stack |

---

## Project Structure

```
Gingersnaps-Warehouse-System/
├── DEVELOPMENT_PLAN.md              (this file)
├── unicommerce-data-extraction.md   (reference data)
├── README.md                        (setup instructions)
│
├── database/
│   ├── schema.sql                   (4 table definitions)
│   ├── seed-data.sql                (optional test data)
│   └── migrations/                  (future schema changes)
│
├── api/
│   ├── config/
│   │   ├── database.php             (DB connection config)
│   │   └── constants.php            (API constants)
│   ├── middleware/
│   │   ├── auth.php                 (session/auth check)
│   │   └── response.php             (JSON response helper)
│   ├── controllers/
│   │   ├── PicklistController.php
│   │   ├── PickerController.php
│   │   └── TaskController.php
│   ├── models/
│   │   ├── Picklist.php
│   │   ├── Picker.php
│   │   ├── Shipment.php
│   │   └── PicklistItem.php
│   └── index.php                    (router/entry point)
│
├── public/
│   ├── index.php                    (web UI entry point)
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── app.js                   (main app JS)
│   │   ├── api.js                   (API client)
│   │   └── ui.js                    (UI helpers)
│   └── views/
│       ├── layout.php               (master template)
│       ├── dashboard.php            (manager dashboard)
│       ├── assign-task.php          (task assignment form)
│       ├── picker-tasks.php         (picker task list)
│       └── task-details.php         (task detail view)
│
├── tests/
│   ├── api-test.php                 (API endpoint tests)
│   └── README.md                    (test guide)
│
├── docs/
│   ├── API_REFERENCE.md             (endpoint documentation)
│   ├── DATABASE_SCHEMA.md           (schema documentation)
│   └── USER_GUIDE.md                (end-user guide)
│
└── .gitignore

```

---

## Current Deliverables - Phase 1

### 1. Database Schema (schema.sql)
- Users table (pickers/warehouse staff)
- Picklists table (picking tasks)
- Shipments table (associated shipments)
- Picklist Items table (line items)
- Indexes for performance

### 2. PHP API Layer
- RESTful endpoints for task management
- Standard CRUD operations
- Status workflow enforcement
- Error handling & validation

### 3. Web UI
- Bootstrap-based responsive design
- Manager dashboard (task assignment, monitoring)
- Picker interface (view assigned tasks, update status)
- Real-time status updates

### 4. Documentation
- API reference
- Database schema docs
- Setup/deployment guide

---

## Dependencies & Prerequisites

1. **Web Server:** Apache (XAMPP)
2. **PHP Version:** 7.4+ (XAMPP included)
3. **MySQL Version:** 5.7+ (XAMPP included)
4. **Browser:** Modern browser (Chrome, Firefox, Safari, Edge)
5. **Libraries:**
   - Bootstrap 5 (CDN)
   - jQuery 3.x (CDN)
   - Possibly: axios or fetch API for async requests

---

## Success Metrics (Phase 1)

- [ ] Database schema deployed and tested
- [ ] All Phase 1 API endpoints functional
- [ ] Manager can create and assign tasks
- [ ] Pickers can view assigned tasks
- [ ] Status workflow enforced (state transitions)
- [ ] UI responsive on desktop/tablet
- [ ] Error handling implemented
- [ ] Basic documentation complete
- [ ] Test coverage for critical paths

---

## Known Constraints & Decisions

1. **User Authentication:** Will use PHP sessions initially; no OAuth
2. **Real-time Updates:** Phase 1 uses polling; WebSockets reserved for Phase 2
3. **Data Sync:** Manual API calls; Unicommerce sync in Phase 3
4. **Mobile:** Bootstrap responsive design; native app deferred to future
5. **Notifications:** UI-based status; email/SMS in Phase 3

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Scope creep during Phase 1 | High | Strict MVP focus; defer Phase 2+ features |
| Database performance at scale | Medium | Indexed queries; pagination implemented early |
| UI/UX usability | Medium | User feedback; iterative refinement |
| Data validation gaps | High | Input validation on all endpoints |
| Session management complexity | Low | Use PHP native sessions initially |

---

## Next Steps (Immediate Actions)

1. **Create database schema** (schema.sql)
2. **Set up project structure** (directory tree)
3. **Build PHP API layer** (controllers, models)
4. **Develop manager UI** (dashboard, task assignment)
5. **Develop picker UI** (task list, status updates)
6. **Testing & QA** (endpoint testing, UI validation)

---

## Assumptions

1. XAMPP running on local machine with MySQL accessible
2. Database name: `gingersnaps_warehouse` (configurable)
3. Users have browser access to `http://localhost:8888/gingersnaps-warehouse`
4. PHP file uploads/configuration allowed
5. No complex authentication initially (single app, same network)

---

## Document Status

**Created:** October 24, 2026  
**Last Updated:** October 24, 2026  
**Owner:** Development Team  
**Phase Status:** Phase 1 - In Progress
