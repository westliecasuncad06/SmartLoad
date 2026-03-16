# SmartLoad Documentation

Last updated: March 16, 2026

## Purpose

SmartLoad is a PHP and MySQL faculty scheduling system for managing teachers, subjects, schedule slots, generated teaching assignments, manual overrides, and audit history.

The current codebase is no longer only a UI prototype. It now includes working backend endpoints for CSV import, AI-assisted schedule generation, and manual reassignment.

## Workspace Structure

```text
SmartLoad/
|-- DOCUMENTATION.md
|-- database.sql
|-- index.html
|-- index.php
|-- api/
|   |-- generate_schedule.php
|   |-- override.php
|   `-- upload.php
|-- css/
|   `-- style.css
|-- database/
|   `-- database.sql
|-- includes/
|   |-- db.php
|   |-- GeminiAPI.php
|   `-- pages/
|       |-- audittrail.php
|       |-- loadreports.php
|       |-- schedules.php
|       |-- subjects.php
|       `-- teachers.php
|-- js/
|   `-- app.js
`-- uploads/
```

## Stack

- PHP with PDO
- MySQL / MariaDB
- Tailwind CSS via CDN
- Font Awesome via CDN
- Plain JavaScript with `fetch`
- Google Gemini API through PHP cURL

## Current Architecture

### 1. Entry point

`index.php` is the main application entry point. It:
- loads the shared PDO connection from `includes/db.php`
- queries dashboard summary metrics from the database
- fetches the 10 most recent audit log entries for dashboard display
- renders the full admin shell and includes the page partials under `includes/pages/`

If database queries fail during page bootstrap, the file falls back to zeroed counters and an empty audit list so the interface can still render.

### 2. Shared database bootstrap

`includes/db.php` defines:
- MySQL connection settings for a local XAMPP environment
- a global `$pdo` connection
- `GEMINI_API_KEY`

The file enables exception-based PDO error handling and disables emulated prepared statements.

### 3. Frontend behavior

`js/app.js` provides the browser-side behavior for:
- switching sections inside the single-page dashboard shell
- updating sidebar active state and breadcrumb text
- opening and closing modals
- drag and drop upload handling
- CSV upload requests to `api/upload.php`
- schedule generation requests to `api/generate_schedule.php`
- manual override requests to `api/override.php`
- `Ctrl+K` / `Cmd+K` search focus shortcut

### 4. Backend API layer

The `api/` directory exposes JSON endpoints used by the frontend:

- `api/upload.php`
- `api/generate_schedule.php`
- `api/override.php`

All endpoints require the shared PDO connection from `includes/db.php`.

### 5. AI scoring utility

`includes/GeminiAPI.php` contains a `GeminiEvaluator` class. It sends a teacher-versus-subject comparison prompt to Gemini and expects a strict JSON response with:
- `score`
- `rationale`

This utility is actively used by `api/generate_schedule.php` during assignment scoring.

## Main User Interface Modules

### Dashboard

The dashboard in `index.php` mixes live and static values.

Live values:

- total teachers
- total subjects
- total subject units
- assigned subject count
- overload count
- recent audit logs

Static or placeholder UI values still present:

- some growth badges such as `+3 new`
- `Last generated: Today, 2:45 PM`
- generation time card value `2.3s`
- notification count

### Teachers Page

`includes/pages/teachers.php` renders live teacher rows from the `teachers` table and displays:

- teacher name
- email
- expertise tags
- employment type
- current versus maximum load
- overload highlighting when `current_units > max_units`

Current limitation:

- the summary cards for full-time and part-time counts are still hard-coded as `38` and `4`
- action buttons are visual only

### Subjects Page

`includes/pages/subjects.php` renders live subject rows from the `subjects` table.

Visible fields:

- course code
- subject name
- program
- units
- prerequisites

Current limitation:

- the per-row `Assigned To` and `Status` values are still placeholder UI and are not joined against the `assignments` table
- action buttons are visual only

### Schedules Page

`includes/pages/schedules.php` renders a weekly schedule grid from the `schedules` table joined to `subjects` and optionally to `assignments` and `teachers`.

The page currently:

- groups classes by `day_of_week` and `start_time`
- shows subject code, subject name, assigned teacher if available, and room
- builds teacher and room filter dropdowns from live data

Current limitation:

- the teacher and room filter controls do not currently apply filtering logic
- the legend is static and not derived from database data

### Load Reports Page

`includes/pages/loadreports.php` is still mostly presentation-only.

It currently uses live totals for some counters such as:

- total teachers
- total subjects
- overload count

Current limitation:

- charts, utilization values, averages, and export buttons are placeholder UI

### Audit Trail Page

`includes/pages/audittrail.php` renders live audit records from the `audit_logs` table.

The page currently:

- fetches the 50 most recent entries
- maps action types to icon and badge styles
- displays user and timestamp information
- shows an empty state when no records exist

Current limitation:

- date range and dropdown filters are visual only
- export and details actions are not implemented

## API Documentation

### `POST /api/upload.php`

Imports CSV data into one of the main tables.

Request format:

- `multipart/form-data`
- fields:
	- `type`: `teacher`, `subject`, or `schedule`
	- `file`: uploaded CSV file

Accepted request methods:

- `POST` only

Response format:

```json
{
	"status": "success",
	"rows_inserted": 10
}
```

Supported CSV layouts:

`type=teacher`

- `name`
- `email`
- `type`
- `max_units`
- `expertise_tags` optional

`type=subject`

- `course_code`
- `name`
- `program`
- `units`
- `prerequisites` optional

`type=schedule`

- `subject_id`
- `day_of_week`
- `start_time`
- `end_time`
- `room`

Processing notes:

- the first CSV row is skipped as a header row
- inserts run inside a transaction
- rows with missing minimum columns are skipped

Current limitations:

- although the UI says CSV or Excel, the backend currently parses CSV only through `fgetcsv()`
- uploads are inserted directly without deduplication or upsert logic
- this endpoint does not currently create an audit log entry for uploads

### `POST /api/generate_schedule.php`

Generates pending assignments for subjects that the query currently treats as unassigned.

Processing flow:

1. Fetch subjects without an existing `Pending` or `Approved` assignment.
2. For each subject, find teachers whose `current_units + subject.units <= max_units`.
3. Score each eligible teacher using `GeminiEvaluator::scoreExpertise()`.
4. Insert a new `assignments` row with status `Pending`.
5. Increase the selected teacher's `current_units`.
6. Insert an audit log entry with action type `Schedule Generation`.

Success response:

```json
{
	"status": "success",
	"assigned_count": 12,
	"unassigned_count": 3
}
```

Important behavior notes:

- the endpoint uses Gemini scoring rather than simple keyword matching
- unit capacity is enforced before scoring
- no schedule conflict checks are performed
- `teacher_availability` is not consulted

Current limitation:

- the query only excludes `Pending` and `Approved` assignments, so rows marked `Manual` are not considered assigned by this endpoint

### `POST /api/override.php`

Reassigns an existing assignment to a different teacher.

Request body:

```json
{
	"assignment_id": 5,
	"new_teacher_id": 7,
	"reason": "Adjusted due to specialization and load balance"
}
```

Processing flow:

1. Read the current assignment and subject units.
2. Validate the new teacher exists.
3. Validate the new teacher has enough unit capacity.
4. Update the assignment row to the new teacher.
5. Set assignment status to `Manual`.
6. Subtract units from the old teacher.
7. Add units to the new teacher.
8. Insert an audit log entry with action type `Manual Override`.

Success response:

```json
{
	"status": "success",
	"message": "Assignment overridden successfully."
}
```

## Gemini Integration

`includes/GeminiAPI.php` uses the `gemini-1.5-flash:generateContent` endpoint.

Prompt contract:

- teacher expertise tags are compared against subject prerequisites
- Gemini must return raw JSON only
- the system expects exactly `score` and `rationale`

Fallback behavior:

- non-200 HTTP responses return a fallback score of `0`
- malformed responses return a fallback score of `0`
- markdown code fences are stripped if the model includes them anyway

Configuration note:

- `includes/db.php` still ships with `YOUR_GEMINI_API_KEY_HERE`, so a real key must be supplied before production use

## Database Schema

Both `database.sql` and `database/database.sql` currently define the same schema.

### `teachers`

Stores faculty records.

Columns:

- `id`
- `name`
- `email` unique
- `type` enum: `Full-time`, `Part-time`
- `max_units`
- `current_units`
- `expertise_tags`

### `subjects`

Stores the subject catalog.

Columns:

- `id`
- `course_code` unique
- `name`
- `program`
- `units`
- `prerequisites`

### `teacher_availability`

Stores teacher time availability.

Columns:

- `teacher_id`
- `day_of_week`
- `start_time`
- `end_time`

Current limitation:

- the table exists in the schema but is not currently used by the schedule generation logic or UI

### `schedules`

Stores class meeting slots.

Columns:

- `subject_id`
- `day_of_week`
- `start_time`
- `end_time`
- `room`

### `assignments`

Stores teacher-to-subject assignment decisions.

Columns:

- `subject_id`
- `teacher_id`
- `status`
- `rationale`
- `created_at`

Supported status values:

- `Pending`
- `Approved`
- `Rejected`
- `Manual`

### `audit_logs`

Stores recorded system activity.

Columns:

- `action_type`
- `description`
- `user`
- `created_at`

## Setup Notes

### Local environment

Expected local environment:

- Apache and MySQL through XAMPP or equivalent
- PHP with PDO MySQL enabled
- PHP cURL enabled for Gemini requests

### Initial setup

1. Create the `smartload` database by importing either `database.sql` or `database/database.sql`.
2. Ensure the MySQL credentials in `includes/db.php` match the local environment.
3. Replace `YOUR_GEMINI_API_KEY_HERE` with an actual Gemini API key if schedule generation should use AI scoring.
4. Serve the project from the web root and open `index.php`.

## Current Operational Flow

1. Upload teacher, subject, and schedule CSV files from the dashboard.
2. The frontend sends each file to `api/upload.php`.
3. Generate assignments from the dashboard.
4. `api/generate_schedule.php` scores eligible teachers with Gemini and inserts pending assignments.
5. Review schedules and audit history in the UI.
6. Reassign teachers manually through the override workflow when needed.

## Known Gaps And Risks

- Excel files are advertised in the UI but not parsed by the backend.
- `teacher_availability` is not yet used in assignment generation.
- no room conflict or teacher time conflict checks are performed.
- upload actions are not currently written to `audit_logs`.
- several page controls remain UI-only: filters, exports, some action buttons, and report widgets.
- the subjects table does not yet show live assignment status per row.
- schedule generation currently ignores assignment rows with status `Manual` when deciding whether a subject is already assigned.

## File Reference Summary

- `index.php`: main dashboard shell and top-level metric queries
- `js/app.js`: frontend navigation, uploads, generation, override calls
- `api/upload.php`: CSV import endpoint
- `api/generate_schedule.php`: AI-assisted assignment generation endpoint
- `api/override.php`: manual reassignment endpoint
- `includes/db.php`: database and Gemini key configuration
- `includes/GeminiAPI.php`: Gemini scoring helper
- `includes/pages/*.php`: UI page partials
- `database.sql`: primary schema definition
- `database/database.sql`: duplicate schema copy

## Summary

SmartLoad currently has a working data layer for importing records, generating assignments, tracking manual overrides, and displaying audit history. The main remaining gap is that several interface sections still present placeholder or partially wired behavior around filtering, exports, reporting, and some assignment views.

The system currently uses:

- PHP with PDO for server-side rendering and database access
- MySQL for persistent data storage
- Tailwind CSS via CDN for layout and styling
- Font Awesome via CDN for icons
- Plain JavaScript for page switching, modal behavior, upload UI, and simulated client interactions
- cURL-based Gemini API integration utilities for future AI-assisted matching

The application now includes two distinct layers:

1. A PHP-rendered admin interface in `index.php` with reusable partial views under `includes/pages/`
2. A backend API under `api/` for CSV imports, automated assignment generation, and manual override actions

## Runtime Architecture

### 1. Page bootstrap

When `index.php` loads, it requires `includes/db.php` to create the shared `$pdo` connection.

It then attempts to load summary data from the database, including:

- teacher count
- subject count
- total units
- assigned subject count
- overload count
- recent audit entries

If the database is unavailable, the page falls back to safe defaults so the interface can still render.

### 2. Layout composition

`index.php` defines the application shell:

- sidebar navigation
- top bar
- dashboard section
- modal dialogs

The page content itself is composed from PHP partials:

- `includes/pages/teachers.php`
- `includes/pages/subjects.php`
- `includes/pages/schedules.php`
- `includes/pages/loadreports.php`
- `includes/pages/audittrail.php`

### 3. Frontend behavior

`js/app.js` controls the browser-side experience by:

- switching between page sections using `.page-content`
- updating the active sidebar state
- updating breadcrumbs
- opening and closing modals
- styling drag-and-drop upload zones
- simulating file upload status in the UI
- simulating schedule generation from the frontend
- handling the search shortcut with `Ctrl+K` or `Cmd+K`

Important note: the frontend still simulates several workflows visually, but the backend endpoints now exist and can be wired into AJAX calls.

### 4. Backend API layer

The `api/` directory contains JSON endpoints used for data import and assignment workflows:

- `api/upload.php` imports CSV data into core tables
- `api/generate_schedule.php` assigns subjects to eligible teachers using a placeholder scoring algorithm
- `api/override.php` performs manual reassignment of an existing assignment and updates workload totals

These endpoints all use `includes/db.php` and return JSON responses intended for AJAX consumption.

### 5. AI integration utility

`includes/GeminiAPI.php` provides a `GeminiEvaluator` class that can send a teacher-subject comparison prompt to the Gemini API and parse a strict JSON response containing a score and rationale.

This class exists as an integration utility and is not yet called by the live scheduling endpoint, which still uses a placeholder string-matching approach.

## Current Functional Capabilities

The current system can now do the following on the backend:

- connect to the `smartload` MySQL database through PDO
- import teacher, subject, and schedule CSV files through `api/upload.php`
- render teacher and subject tables from live database records
- generate subject-to-teacher assignment records through `api/generate_schedule.php`
- update teacher workloads after automatic assignment
- manually override an assignment through `api/override.php`
- write audit trail entries for automated and manual assignment actions

The UI is still partly prototype-oriented, but the core data layer is no longer purely mock-driven.

## File-by-File Documentation

## `index.php`

Purpose:
Primary application entry point and main rendered UI.

Key responsibilities:

- requires `includes/db.php`
- queries dashboard metrics and recent logs
- provides fallback values when DB queries fail
- renders the overall dashboard shell
- includes the page partials from `includes/pages/`
- loads `css/style.css` and `js/app.js`

Important implementation details:

- uses Tailwind CSS, Google Fonts, and Font Awesome from CDNs
- mixes database-driven metrics with still-static interface elements
- includes `teachers.php` as a separate partial rather than embedding the table inline

Dependencies:

- `includes/db.php`
- `includes/pages/teachers.php`
- `includes/pages/subjects.php`
- `includes/pages/schedules.php`
- `includes/pages/loadreports.php`
- `includes/pages/audittrail.php`
- `css/style.css`
- `js/app.js`

## `index.html`

Purpose:
Static prototype version of the interface.

Key responsibilities:

- reproduces the dashboard layout without PHP
- uses hard-coded sample data
- serves as a visual mockup or fallback reference

Important implementation details:

- duplicates some structure and behavior that now lives in the PHP version
- does not use the backend API layer

## `css/style.css`

Purpose:
Custom CSS supplement for Tailwind utilities.

Key responsibilities:

- sets the default font family
- defines upload zone states
- defines the pulse animation used by generation UI states
- styles helper classes used by the interface

## `js/app.js`

Purpose:
Client-side interaction layer.

Key responsibilities:

- handles SPA-like section switching
- updates nav link state and breadcrumbs
- opens and closes modals
- manages upload zone state in the browser
- simulates schedule generation UX
- supports the search keyboard shortcut

Important implementation details:

- current upload behavior is still mostly UI-driven unless the frontend explicitly calls the backend endpoints
- `generateSchedule()` remains a frontend simulation and is separate from `api/generate_schedule.php`

## `includes/db.php`

Purpose:
Central PDO database connection bootstrap.

Key responsibilities:

- defines the local MySQL connection settings
- creates the `$pdo` connection
- enables exception-based PDO error handling
- sets associative fetch mode
- disables emulated prepared statements

Important implementation details:

- targets a local XAMPP-style environment
- assumes database name `smartload`
- exports `$pdo` into the including scope

## `includes/GeminiAPI.php`

Purpose:
Gemini API integration helper for teacher-subject expertise evaluation.

Key responsibilities:

- defines the `GeminiEvaluator` class
- exposes `scoreExpertise($teacherTags, $subjectPrerequisites)`
- builds a prompt asking Gemini to rate match quality from 0 to 100
- instructs Gemini to return raw JSON only
- sends the request with PHP cURL
- decodes the returned JSON into a PHP associative array

Important implementation details:

- uses the `gemini-1.5-flash:generateContent` endpoint
- strips code fences from the response as a fallback if the model ignores instructions
- returns a fallback `score` and `rationale` if the request fails or the response is malformed
- is currently available for future use but is not yet wired into the live schedule generation endpoint

## `includes/pages/teachers.php`

Purpose:
Partial view for teacher management.

Key responsibilities:

- queries `teachers` with `SELECT * FROM teachers ORDER BY name ASC`
- renders a live teacher table from database data
- displays teacher name, email, expertise tags, employment type, and current load
- visually flags overloaded teachers when `current_units > max_units`

Important implementation details:

- computes initials from teacher names for avatar badges
- splits comma-separated `expertise_tags` into individual UI chips
- reads shared summary variables from `index.php`

## `includes/pages/subjects.php`

Purpose:
Partial view for the subject catalog.

Key responsibilities:

- renders subject summary cards from shared PHP variables
- queries `subjects` and renders the table dynamically
- displays `course_code`, `name`, `program`, `units`, and `prerequisites`

Important implementation details:

- the table now uses live subject data rather than hardcoded rows
- assignment status and action controls are still largely presentational in this view

## `includes/pages/schedules.php`

Purpose:
Partial view for schedule management.

Key responsibilities:

- displays a timetable-style grid
- shows static example class blocks
- provides example filter controls

Important implementation details:

- still static markup
- not yet driven from the `schedules` table

## `includes/pages/loadreports.php`

Purpose:
Partial view for reporting and load summaries.

Key responsibilities:

- displays reporting cards and utilization summaries
- uses some database-derived totals from `index.php`
- presents placeholder actions for exporting or viewing reports

## `includes/pages/audittrail.php`

Purpose:
Partial view for activity history.

Key responsibilities:

- shows timeline-style audit activity UI
- provides filter controls and paginated sample layout

Important implementation details:

- the page itself is still static
- it does not yet render all live rows from `audit_logs`, even though logs are now written by backend endpoints

## `api/upload.php`

Purpose:
CSV import endpoint for teachers, subjects, and schedules.

Key responsibilities:

- accepts POST uploads using `FormData`
- validates `$_POST['type']` against `teacher`, `subject`, or `schedule`
- reads the uploaded CSV via `fgetcsv()`
- skips the header row
- inserts rows into the matching database table using prepared statements
- wraps the entire import in a database transaction
- returns JSON success or error responses

Expected CSV mappings:

- `teacher`: `name`, `email`, `type`, `max_units`, `expertise_tags`
- `subject`: `course_code`, `name`, `program`, `units`, `prerequisites`
- `schedule`: `subject_id`, `day_of_week`, `start_time`, `end_time`, `room`

Response format:

- success: `{"status":"success","rows_inserted":X}`
- error: `{"status":"error","message":"..."}`

## `api/generate_schedule.php`

Purpose:
Automated assignment generator for unassigned subjects.

Key responsibilities:

- fetches subjects that do not yet have a pending or approved assignment
- filters teachers by unit capacity using `current_units + subject_units <= max_units`
- calculates a placeholder match score using string matching between teacher expertise tags and subject program/prerequisites
- selects the highest-scoring eligible teacher
- inserts an assignment row with status `Pending`
- updates teacher `current_units`
- writes an audit log entry for each automated assignment
- returns JSON counts for assigned and still-unassigned subjects

Important implementation details:

- the scoring logic is a placeholder function, not yet Gemini-backed
- all writes are executed inside a transaction
- subjects with no eligible teachers are counted as unassigned

Response format:

- success: `{"status":"success","assigned_count":X,"unassigned_count":Y}`
- error: `{"status":"error","message":"..."}`

## `api/override.php`

Purpose:
Manual reassignment endpoint for Program Chair override actions.

Key responsibilities:

- accepts a POST request with `assignment_id`, `new_teacher_id`, and `reason`
- loads the current assignment and subject unit value
- validates that the new teacher exists and has enough capacity
- updates the assignment to the new teacher
- changes assignment status to `Manual`
- updates workload totals for both the old and new teacher
- inserts an audit log entry describing the reassignment
- commits all changes in one transaction

Important implementation details:

- reads JSON request data from `php://input`
- rejects overrides where the replacement teacher matches the current teacher
- uses `GREATEST(0, current_units - ?)` when subtracting from the old teacher

Response format:

- success: `{"status":"success","message":"Assignment overridden successfully."}`
- error: `{"status":"error","message":"..."}`

## `database.sql`

Purpose:
Primary SQL schema file.

Key responsibilities:

- creates the `smartload` database if needed
- defines all application tables
- applies indexes and foreign keys

Current tables:

- `teachers`
- `subjects`
- `teacher_availability`
- `schedules`
- `assignments`
- `audit_logs`

Important implementation details:

- `teachers.current_units` tracks active teaching load
- `assignments.status` now supports `Pending`, `Approved`, `Rejected`, and `Manual`
- the schema supports automated assignment, workload tracking, and audit logging

## `database/database.sql`

Purpose:
Secondary copy of the SQL schema.

Key responsibilities:

- mirrors the root `database.sql` file

Observation:

- both schema files should stay synchronized to avoid drift

## `uploads/`

Purpose:
Reserved directory for uploaded files.

Current state:

- currently empty
- the CSV import endpoint reads uploaded files from PHP temporary storage and does not persist them here yet

## Database Schema Details

### `teachers`

Stores faculty identity and load information.

- `id`: primary key
- `name`: full name
- `email`: unique email address
- `type`: `Full-time` or `Part-time`
- `max_units`: unit limit for the teacher
- `current_units`: currently assigned units
- `expertise_tags`: comma-separated expertise descriptors

### `subjects`

Stores the subject catalog.

- `id`: primary key
- `course_code`: unique course code
- `name`: subject title
- `program`: owning program
- `units`: unit count
- `prerequisites`: prerequisite description text

### `teacher_availability`

Stores teacher availability windows.

- `teacher_id`: owning teacher
- `day_of_week`: weekday enum
- `start_time`: availability start
- `end_time`: availability end

### `schedules`

Stores schedule slot records.

- `subject_id`: linked subject
- `day_of_week`: class day
- `start_time`: class start
- `end_time`: class end
- `room`: room assignment

### `assignments`

Stores subject-to-teacher assignment records.

- `subject_id`: linked subject
- `teacher_id`: assigned teacher
- `status`: `Pending`, `Approved`, `Rejected`, or `Manual`
- `rationale`: explanation for assignment or override
- `created_at`: creation timestamp

### `audit_logs`

Stores application and user activity records.

- `action_type`: event category
- `description`: event details
- `user`: acting user or system actor
- `created_at`: timestamp

## Dependency Map

- `index.php` depends on `includes/db.php`
- `index.php` includes all files in `includes/pages/`
- `includes/pages/teachers.php` and `includes/pages/subjects.php` query live database data through the inherited `$pdo`
- `api/upload.php`, `api/generate_schedule.php`, and `api/override.php` all depend on `includes/db.php`
- `api/generate_schedule.php` currently uses internal placeholder scoring rather than `includes/GeminiAPI.php`
- `includes/GeminiAPI.php` is available for future AI-assisted evaluation work

## Current Limitations

- the frontend still simulates some actions instead of calling the backend endpoints directly
- `js/app.js` schedule generation is separate from `api/generate_schedule.php`
- `includes/pages/schedules.php` is still static and not yet driven by stored schedule records
- `includes/pages/audittrail.php` does not yet fully render live `audit_logs` data
- `api/generate_schedule.php` uses placeholder string matching instead of the Gemini evaluator
- `api/upload.php` imports CSV content but does not yet validate headers or persist source files into `uploads/`
- `api/override.php` expects JSON request data, so the frontend must send the correct content type and body format
- the two SQL schema files can drift if they are not updated together

## Suggested Reading Order

If you want to understand the project quickly, read these files in order:

1. `index.php`
2. `includes/db.php`
3. `includes/pages/teachers.php`
4. `includes/pages/subjects.php`
5. `api/upload.php`
6. `api/generate_schedule.php`
7. `api/override.php`
8. `includes/GeminiAPI.php`
9. `js/app.js`
10. `database.sql`

## Summary

SmartLoad is now a hybrid UI-and-backend scheduling prototype. The interface is still partly mock-driven, but the project now includes real CSV import support, live teacher and subject table rendering, automated assignment generation with workload updates, manual override handling, audit logging, and a Gemini API utility prepared for future AI-assisted expertise scoring.