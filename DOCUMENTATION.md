# SmartLoad Documentation

Last updated: March 16, 2026

## Scope

This document describes the current SmartLoad workspace, including the PHP UI layer, the backend API endpoints, the Gemini integration utility, and the MySQL schema used by the application.

## Current File Inventory

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

## System Overview

SmartLoad is a PHP-based faculty scheduling application prototype with a dashboard-style UI and a small backend API layer.

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