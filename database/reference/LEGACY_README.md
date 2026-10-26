# SUNN Clinic System — Multicampus Edition

## Overview
This is a complete clinic management system for SUNN (State University of Northern Negros) with a MySQL backend, Python/FastAPI API, and vanilla HTML/JS frontends. Version 4.0 introduces full multicampus support across 4 campus clinics.

## Campuses
| ID | Name | Code |
|----|------|------|
| 1 | Old Sagay Campus | OLD-SAGAY |
| 2 | Sagay Main Campus | SAGAY-MAIN |
| 3 | Laga-an Campus | LAGA-AN |
| 4 | Escalante Campus | ESCALANTE |

## System Architecture

### Database (MySQL)
**File:** `db/setup.sql` | **Migration:** `db/migrate_multicampus.sql`

Key tables:
- `campuses` - Campus registry (4 SUNN campuses)
- `students` - Student accounts with home campus assignment
- `staff` - Clinic personnel with optional campus assignment (NULL = all campuses)
- `health_records` - Comprehensive Personal Health Records (PHR)
- `clinic_visits` - Daily consultation records (campus-tagged)
- `appointments` - Appointment scheduling (campus-tagged)
- `medications` - Medication tracking (campus-tagged)
- `forms_certificates` - Generated forms/certificates tracking (campus-tagged)
- `audit_log` - System audit trail with campus context
- `system_settings` - Configurable settings

### Backend API (Python/FastAPI)
```
api/
├── auth_routes.py     # Authentication + public get_campuses
├── admin_routes.py    # Admin dashboard + campus management (no inventory)
├── nurse_routes.py    # Nurse/staff API (campus-scoped)
├── student_routes.py  # Student-facing API (profile includes campus_name)
└── common.py          # Shared helpers + campus_filter_clause()
```

### Frontend JavaScript
```
js/
├── auth.js                 # Login/register (campus dropdown in registration)
├── student-dashboard.js    # Student dashboard (shows Home Campus in profile)
├── admin.js                # Admin dashboard (campus filter + staff assignment)
├── admin-extra.js          # Extended admin features (no inventory)
├── nurse.js                # Nurse dashboard (campus badge + scoped data)
├── nurse-forms.js          # Form/certificate rendering (20+ forms)
├── dashboard.js            # Shared utilities + renderCampusBadge()
└── sunn-logo-b64.js        # Base64 logo for offline printing
```

## Multicampus Access Model

### Students
- Must select a home campus at registration
- Campus assignment is **permanent** — cannot be changed by the student
- All visits, appointments, and forms are associated with their home campus

### Staff (Nurse / Physician / Dentist)
- Assigned to **one campus** or **all campuses** (campus_id = NULL) by admin
- Dashboard shows only data from their assigned campus
- A **campus badge** in the header shows which campus they are operating in
- All records created by staff are tagged with their campus_id

### Admin
- **Full cross-campus visibility** — sees all campuses by default
- Can **filter** all panels (stats, students, appointments, records, reports, audit log) by campus
- Can **assign staff** to a campus or grant all-campus access
- Campus filter dropdown is injected into the admin dashboard toolbar

## API Endpoints

### Authentication (`/api/auth.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `login` | POST | Returns session with `campusId` and `campusName` |
| `register` | POST | Requires `campus_id` field |
| `logout` | POST | Audit logging |
| `me` | GET | Session validation |
| `get_campuses` | GET | Public — returns active campuses (no auth required) |

### Admin API (`/api/admin.php`)
All GET endpoints accept optional `?campus_id=X` filter.

| Action | Method | Description |
|--------|--------|-------------|
| `get_stats` | GET | Dashboard statistics (campus-filterable, no inventory) |
| `get_students` | GET | Paginated student list |
| `get_staff` | GET | Staff list with campus_name |
| `add_staff` | POST | Add staff (accepts optional campus_id) |
| `get_campuses` | GET | All campuses including inactive (admin only) |
| `assign_staff_campus` | POST | `{employee_id, campus_id}` — assign or clear campus |
| `get_appointments` | GET | Appointments (campus-filterable) |
| `get_records` | GET | Medical/dental/prescription/lab records |
| `get_reports` | GET | Analytics (campus-filterable, no inventory report) |
| `get_audit_log` | GET | Paginated audit trail |
| `get_settings` | GET | System settings |
| `update_setting` | POST | Update setting |
| `export_data` | GET | Full database JSON export |

### Nurse/Staff API (`/api/nurse.php`)
All endpoints accept `?staff_campus_id=X` for campus scoping. Pass the staff's `campusId` from session.

| Action | Method | Description |
|--------|--------|-------------|
| `search_student` | GET | Student search — scoped to staff campus |
| `get_student_for_form` | GET | Complete student data for form auto-fill |
| `save_visit` | POST | Create visit (tagged with campus_id) |
| `get_visits` | GET | Today's visits — scoped to campus |
| `get_queue` | GET | Today's queue — scoped to campus |
| `get_nurse_stats` | GET | Dashboard stats — scoped to campus |
| `save_form` | POST | Save form/certificate (tagged with campus_id) |
| `get_forms` | GET | List forms — scoped to campus |
| `get_patients` | GET | Patient list — scoped to campus |

### Student API (`/api/student.php`)
| Action | Method | Description |
|--------|--------|-------------|
| `get_profile` | GET | Returns `campus_name` field |
| `update_profile` | POST | Update profile (campus_id NOT updatable) |
| `get_health_record` | GET | Latest health record |
| `save_health_record` | POST | Create/update PHR |
| `get_visits` | GET | Visit history |
| `get_appointments` | GET | Appointment list |
| `save_appointment` | POST | Book appointment |
| `cancel_appointment` | POST | Cancel appointment |
| `get_medications` | GET | Medication list |
| `save_medication` | POST | Add medication |
| `delete_medication` | POST | Remove medication |

## Setup Instructions

### Fresh Install
```bash
# 1. Run the full schema (includes campuses and campus_id columns)
mysql -u root sunn_uni_clinic < db/setup.sql
```

### Upgrade Existing Database
```bash
# Run the idempotent migration (safe to run multiple times)
mysql -u root sunn_uni_clinic < db/migrate_multicampus.sql
```

### Start the Server
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

## Default Credentials (after setup.sql)
| Role | Email | Password | Campus |
|------|-------|----------|--------|
| Student | student@uni.edu | password123 | (none — select at registration) |
| Admin | admin@uni.edu | admin123& | All Campuses |
| Nurse | nurse@uni.edu | nurse123& | All Campuses (reassign in admin panel) |
| Physician | doctor@uni.edu | physician123& | All Campuses |
| Dentist | dentist@uni.edu | dentist123& | All Campuses |

## Security Features
- Password hashing with bcrypt (cost 12)
- SQL injection prevention via parameterized queries
- Campus-based data isolation for all staff roles
- Role-based access control
- Audit logging with campus context
- Input validation on both client and server

## Notes
- The `inventory` table is retained in the database schema for data safety, but all API endpoints and UI for inventory management have been removed in v4.0
- The campus badge in staff dashboards is rendered by `renderCampusBadge()` in `js/dashboard.js`
- Staff with `campus_id = NULL` have unrestricted access to all campuses
- Students cannot change their campus after registration

---
**Version:** 4.0 (Multicampus Edition)
**Last Updated:** 2026
**Database:** MySQL 8.0+ (XAMPP compatible)
**Backend:** Python 3.10+ / FastAPI / PyMySQL
