# SmartLoad Documentation

Last updated: March 17, 2026

## Overview

SmartLoad is a PHP + MySQL (PDO) faculty scheduling system with integrated predictive analytics for:

- importing teachers, subjects, and schedule slots from CSV
- generating subject → teacher assignments (rule-based with optional Gemini AI refinement)
- tracking loads (current units vs max units)
- manual reassignment ("override") with audit logging
- exporting reports (CSV + PDF from the browser) and exporting audit logs
- viewing teacher load details and (attempting to) email a teacher load PDF
- **[NEW v2.0]** separating current operational data from historical data for predictive analytics

The UI is rendered by PHP (`index.php`) and behaves like a single-page dashboard using JavaScript (`js/app.js`) to switch sections and call the backend APIs under `api/`.

### Version 2.0 Features (Predictive Analytics)

- ✅ Separate databases for current operations and historical records
- ✅ Historical data import from CSV by academic year and semester
- ✅ Predictive analytics engine with multiple endpoints
- ✅ Teacher workload trend analysis
- ✅ Subject assignment pattern recognition
- ✅ Academic year comparison analytics
- ✅ Teaching load statistics and forecasting

## Tech Stack

- PHP (PDO + cURL)
- MySQL / MariaDB
- Tailwind CSS via CDN
- Font Awesome via CDN
- Plain JavaScript (`fetch`)
- Google Gemini API (optional) via `includes/GeminiAPI.php`

## Project Structure

```text
SmartLoad/
|-- index.php
|-- DOCUMENTATION.md
|-- DATABASE_RECONSTRUCTION.md (NEW: Architecture overview)
|-- ADMIN_GUIDE.md (NEW: Administration procedures)
|-- database.sql
|-- api/
|   |-- upload.php
|   |-- generate_schedule.php
|   |-- override.php
|   |-- policy_settings.php
|   |-- get_teacher_load.php
|   |-- send_teacher_load_pdf.php
|   |-- add_teacher.php
|   |-- update_teacher.php
|   |-- archive_teacher.php
|   |-- filter_teachers.php
|   |-- add_subject.php
|   |-- update_subject.php
|   |-- archive_subject.php
|   |-- filter_subjects.php
|   |-- import_historical_data.php (NEW: Import historical data)
|   `-- predictive_analytics.php (NEW: Analytics endpoints)
|-- includes/
|   |-- db.php
|   |-- GeminiAPI.php
|   `-- pages/
|       |-- teachers.php
|       |-- subjects.php
|       |-- schedules.php
|       |-- loadreports.php
|       `-- audittrail.php
|-- js/
|   `-- app.js
|-- css/
|   `-- style.css
|-- database/
|   |-- smartload.sql (updated schema)
|   |-- smartload_new.sql (NEW: Fresh install)
|   `-- add_historical_tables.sql (NEW: Migration)
`-- files/ (Sample CSVs)
    `-- historical/ (Historical data by year/semester)
```

Notes:

- There is no `index.html` in the current workspace.
- The UI advertises "CSV/Excel", but the backend importer parses CSV only.
- **NEW v2.0:** Historical data is completely separated from current operations.
- The UI advertises “CSV/Excel”, but the backend importer parses CSV only.

## Setup (Local / XAMPP)

### For Fresh Installation (v2.0)
1. Import the schema into MySQL:
	- Use `database/smartload_new.sql` for complete v2.0 setup with historical tables
	- Command: `mysql smartload < database/smartload_new.sql`
2. Configure DB access + Gemini key in `includes/db.php`.
3. Ensure PHP has:
	- PDO MySQL enabled
	- cURL enabled (required for Gemini calls)
4. Serve via Apache (XAMPP) and open `index.php`.

### For Existing Installations (Upgrade to v2.0)
1. **Backup your current database:**
   ```bash
   mysqldump smartload > backup_$(date +%Y%m%d).sql
   ```
2. **Add historical tables:**
   ```bash
   mysql smartload < database/add_historical_tables.sql
   ```
3. **Import historical data:**
   - See "Predictive Analytics APIs" section below
   - Use `/api/import_historical_data.php` to import historical CSV data

### Email Configuration Note

- `api/send_teacher_load_pdf.php` uses PHP `mail()`. On many XAMPP installs `mail()` is not configured, so sending may fail until SMTP is set up.

### Additional Documentation

- **DATABASE_RECONSTRUCTION.md** - Architecture and design decisions
- **ADMIN_GUIDE.md** - Operations, migration procedures, and troubleshooting

## Database Schema

The database now has two distinct sections:

### Current Operational Tables

These tables display on the homepage and are used for day-to-day operations:

- `teachers`: current faculty records + load tracking (with `is_archived` flag)
- `subjects`: current subject catalog (with `is_archived` flag)
- `teacher_availability`: availability windows for current teachers
- `schedules`: current meeting slots (day/time/room)
- `assignments`: current subject ↔ teacher assignments
- `audit_logs`: append-only activity log
- `policy_settings`: system configuration

### Historical Data Tables (NEW)

Dedicated tables for predictive analytics, never displayed on homepage:

- `historical_teachers`: teacher records by academic year/semester
- `historical_subjects`: subject records by academic year/semester
- `historical_schedules`: schedule records by academic year/semester
- `historical_assignments`: assignment records by academic year/semester
- `historical_analytics_metadata`: import metadata and statistics

**Key Design:** Current and historical tables are completely separated. All homepage queries filter to `is_archived = 0` only.

### Archive Support

- `teachers.is_archived` and `subjects.is_archived` control which records appear on homepage
- Archiving moves records out of operational view without deletion
- Historical data can be imported separately without affecting current operations

Runtime-created table:

- `policy_settings` is created automatically by `api/policy_settings.php` and `api/generate_schedule.php` if missing.
## Predictive Analytics APIs (NEW v2.0)

SmartLoad now includes predictive analytics engines powered by historical data.

### Import Historical Data

**Endpoint:** `POST /api/import_historical_data.php`

**Request Body:**
```json
{
  "academic_year": "2024-2025",
  "semester": "1stSem"
}
```

**Response:**
```json
{
  "success": true,
  "academic_year": "2024-2025",
  "semester": "1stSem",
  "results": {
    "teachers": {"success": true, "count": 12},
    "subjects": {"success": true, "count": 14},
    "schedules": {"success": true, "count": 15}
  }
}
```

### Analytics Endpoints

**Endpoint:** `GET /api/predictive_analytics.php?endpoint=<NAME>`

Available endpoints:

| Endpoint | Purpose | Parameters |
|----------|---------|-----------|
| `workload_trends` | Teacher loads over time | `email` (optional) |
| `assignment_patterns` | Subject assignment history | `course_code` (optional) |
| `academic_comparison` | Compare years/semesters | None |
| `teaching_load_stats` | Load distribution by type | `year` (optional) |
| `expertise_distribution` | Expertise by period | `year`, `semester` (optional) |
| `predict_shortage` | Forecast teacher shortage | None |
| `list_available` | Show available endpoints | None |

**Example:**
```bash
GET /api/predictive_analytics.php?endpoint=workload_trends
GET /api/predictive_analytics.php?endpoint=teaching_load_stats&year=2024-2025
```
## UI Modules (What’s Live)

### Dashboard (in `index.php`)

- Step 1 Upload (Teachers/Subjects/Schedules) with conflict handling UI
- Step 2 Generate Schedule (calls `api/generate_schedule.php`)
- Load Assignment Report table (teacher loads + assigned subjects + schedule lines)
- “Teacher Load Details” modal (calls `api/get_teacher_load.php`)
- Policy Settings modal (calls `api/policy_settings.php`)

### Teachers (in `includes/pages/teachers.php`)

- List teachers (non-archived)
- Add / Edit / Archive via modals (calls the corresponding `api/*teacher*.php` endpoints)
- Filter + search (calls `api/filter_teachers.php`)

Important note: the “Department” filter is simulated by searching `expertise_tags` because there is no `department` column in the DB.

### Subjects (in `includes/pages/subjects.php`)

- List subjects (non-archived when column exists)
- Shows “Assigned To” based on the latest assignment per subject
- The “Assigned/Unassigned” badge is based on whether any assignment row exists for the subject (it does not currently filter by assignment status)
- Add / Edit / Archive via modals (calls the corresponding `api/*subject*.php` endpoints)
- Filter + search (calls `api/filter_subjects.php`)

### Schedules (in `includes/pages/schedules.php`)

- Renders a weekly grid from the `schedules` table joined to `subjects` (and optionally assigned teacher)
- Teacher/Room dropdowns are populated from live data

Current limitation: teacher/room dropdowns are display-only (no filtering logic is applied).

### Load Reports (in `includes/pages/loadreports.php`)

- Data is pulled from the DB on page load (teachers, subjects, overload list, utilization)
- Export buttons generate CSV/PDF in the browser (loads `jspdf` and `jspdf-autotable` via CDN on demand)

### Audit Trail (in `includes/pages/audittrail.php`)

- Renders the 50 most recent `audit_logs`
- Export buttons generate CSV/PDF in the browser using the same PDF libraries

## Scheduling / Assignment Logic (Current)

`api/generate_schedule.php` assigns unassigned subjects using:

1. Policy settings:
	- `max_teaching_load` (caps effective max per teacher)
	- `expertise_weight` and `availability_weight` (must total 100)
	- `detect_schedule_overlaps` (uses `schedules` + existing assignments)
	- `flag_overload_teachers` (enforce load cap)
	- `check_prerequisites` (controls keyword matching)
2. Heuristic expertise scoring:
	- token-based overlap between `teachers.expertise_tags` and `subjects.prerequisites`
3. Availability score:
	- based on remaining unit capacity (not on `teacher_availability` time windows)
4. Optional Gemini refinement:
	- if a valid Gemini key is set and cURL is available, it asks Gemini to pick the best teacher from a shortlist

Important note about “unassigned”:

- Subjects are considered assigned only if they have an assignment with status `Pending` or `Approved`.
- A subject whose latest assignment is `Manual` is treated as unassigned by the generator and may receive an additional new `Pending` assignment row.

Schedule overlap detection:

- When enabled, it prevents selecting teachers whose existing assigned schedule times overlap the candidate subject’s schedule slots.
- Occupied slots include assignments with status `Pending`, `Approved`, and `Manual`.

Assignment record:

- New rows are inserted into `assignments` with status `Pending`.
- `teachers.current_units` is incremented.
- An `audit_logs` row is written per auto-assignment.

## API Reference

All endpoints return JSON and include `status` (`success` or `error`) unless noted.

### CSV Import

`POST /api/upload.php` (multipart/form-data)

Fields:

- `type`: `teacher` | `subject` | `schedule`
- `file`: CSV file
- `conflict_action` (optional):
  - `detect` (default): insert non-duplicates, return a `conflict` response for duplicates
  - `update`: upsert (insert new + update duplicates) for teachers/subjects

CSV columns:

- `type=teacher`: `name`, `email`, `type`, `max_units`, `expertise_tags` (optional)
- `type=subject`: `course_code`, `name`, `program`, `units`, `prerequisites` (optional)
- `type=schedule`: `subject_id`, `day_of_week`, `start_time`, `end_time`, `room`

Responses:

- Success:

```json
{ "status": "success", "type": "teacher", "rows_inserted": 10, "rows_updated": 0, "duplicates": [] }
```

- Conflict (still HTTP 200):

```json
{ "status": "conflict", "type": "teacher", "rows_inserted": 8, "conflict_count": 2, "conflicts": [ ... ] }
```

Audit:

- Writes an `audit_logs` row with action type `File Upload`.

Limitations:

- Excel files (`.xlsx`, `.xls`) are accepted by the UI input, but the server parses CSV only.
- Schedule upload does not currently support upsert/conflict resolution (it always inserts rows).

### Schedule Generation

`POST /api/generate_schedule.php`

Response:

```json
{ "status": "success", "assigned_count": 12, "unassigned_count": 3 }
```

Note:

- The frontend currently checks `ai_enabled` / `ai_calls`, but the endpoint does not include these fields in the response.
- “Unassigned” is determined by absence of `Pending`/`Approved` rows only (manual assignments do not exclude the subject).

### Policy Settings

`GET /api/policy_settings.php`

- Returns the current policy row; auto-inserts defaults if missing.

`POST /api/policy_settings.php` (JSON)

Payload:

```json
{
  "max_teaching_load": 18,
  "expertise_weight": 70,
  "availability_weight": 30,
  "detect_schedule_overlaps": 1,
  "flag_overload_teachers": 1,
  "check_prerequisites": 1
}
```

Server behavior:

- clamps `max_teaching_load` to 1..40
- clamps `expertise_weight` to 0..100 and forces `availability_weight = 100 - expertise_weight`

### Teachers

`POST /api/add_teacher.php` (JSON)

- Inserts a teacher and writes an `audit_logs` row (`Teacher Added`).

`POST /api/update_teacher.php` (JSON)

- Updates a non-archived teacher and writes `Teacher Updated`.

`POST /api/archive_teacher.php` (JSON)

- Sets `teachers.is_archived = 1` and writes `Teacher Archived`.

`GET /api/filter_teachers.php`

Query params:

- `search` (optional): matches name/email/expertise
- `type` (optional): `Full-time` | `Part-time` | `All`
- `department` (optional): filters by `expertise_tags LIKE %department%`

### Subjects

`POST /api/add_subject.php` (JSON)

- Inserts a subject and writes `Subject Added`.

`POST /api/update_subject.php` (JSON)

- Updates a subject (and avoids archived rows when `is_archived` exists) and writes `Subject Updated`.

`POST /api/archive_subject.php` (JSON)

- Sets `subjects.is_archived = 1` (requires the column) and writes `Subject Archived`.

`GET /api/filter_subjects.php`

Query params:

- `search` (optional): matches code/name/program/prereq/teacher
- `program` (optional): exact match, or `All`
- `status` (optional): `all` | `assigned` | `unassigned`

Note:

- “assigned/unassigned” is computed by whether an assignment row exists (latest assignment id is present), not by checking the assignment status.

### Teacher Load Details + Email PDF

`GET /api/get_teacher_load.php?teacher_id=123`

- Returns teacher info and assigned subjects with schedule lines (derived from `schedules`).

`POST /api/send_teacher_load_pdf.php` (JSON)

```json
{ "teacher_id": 123 }
```

- Builds a minimal PDF in PHP and sends it as an email attachment using `mail()`.
- Common failure mode: `mail()` not configured.

### Manual Override

`POST /api/override.php` (JSON)

```json
{ "assignment_id": 5, "new_teacher_id": 7, "reason": "..." }
```

- Moves an assignment to another teacher, sets status to `Manual`, adjusts both teachers’ loads, and writes `Manual Override` to `audit_logs`.

## Known Gaps / Behavior Notes

- Manual override modal markup in `index.php` is not fully wired to the JS function `submitOverride()` (missing element ids / handler).
- Schedules page filters are UI-only.
- `teacher_availability` exists in the DB but is not currently used in generation scoring.
- Schedule generation response does not currently include the AI telemetry fields that the UI checks.
- The dashboard “Recent Activity” cards include static example entries; the Audit Trail page is the source of truth.