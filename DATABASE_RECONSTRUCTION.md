# SmartLoad Database Reconstruction - Predictive Analytics Architecture

## Overview

The SmartLoad database has been reconstructed to separate **current operational data** from **historical data for predictive analytics**. This ensures:

- ✅ Homepage displays only current teachers, subjects, and schedules
- ✅ Historical data is preserved separately for analytics
- ✅ No mixing of current and historical data in reports
- ✅ Scalable predictive analytics engine

## Database Structure

### Current Operational Tables

These tables are used for day-to-day operations and displayed on the homepage:

```
- teachers (current faculty)
- subjects (current course catalog)
- schedules (current meeting slots)
- assignments (current subject-teacher assignments)
- teacher_availability (current availability windows)
- audit_logs (system activity log)
- policy_settings (system configuration)
```

**Key Feature:** All current tables use `is_archived` flag (0 = active, 1 = archived) to manage record lifecycle.

### Historical Data Tables

New tables dedicated to historical records for analytics and predictions:

```
- historical_teachers (teacher records by academic year/semester)
- historical_subjects (subject records by academic year/semester)
- historical_schedules (schedule records by academic year/semester)
- historical_assignments (assignment records by academic year/semester)
- historical_analytics_metadata (import metadata and statistics)
```

**Key Features:**
- Each record includes `academic_year` (e.g., "2024-2025") and `semester` (e.g., "1st Semester")
- All historical tables use `recorded_at` timestamp for audit trail
- No foreign key constraints between current and historical tables (intentional separation)

## Data Flow

### Importing Historical Data

1. **CSV Files** in `/files/historical/` are structured by academic year and semester:
   ```
   teacher_AY2024-2025_1stSem.csv
   subject_AY2024-2025_1stSem.csv
   schedule_AY2024-2025_1stSem.csv
   ```

2. **Import via API** - POST to `/api/import_historical_data.php`:
   ```json
   {
     "academic_year": "2024-2025",
     "semester": "1stSem"
   }
   ```

3. **Data Destination** - Historical tables in database (NOT current tables)

### Homepage Query Pattern

All current data queries follow this pattern:

```sql
SELECT * FROM teachers WHERE is_archived = 0
SELECT * FROM subjects WHERE is_archived = 0
SELECT * FROM schedules WHERE subject_id IN (SELECT id FROM subjects WHERE is_archived = 0)
SELECT * FROM assignments WHERE 
    subject_id IN (SELECT id FROM subjects WHERE is_archived = 0)
    AND teacher_id IN (SELECT id FROM teachers WHERE is_archived = 0)
```

### Analytics Query Pattern

All predictive analytics use this pattern:

```sql
SELECT * FROM historical_teachers WHERE academic_year = ? AND semester = ?
SELECT * FROM historical_subjects WHERE academic_year = ? AND semester = ?
SELECT * FROM historical_assignments WHERE academic_year = ? AND semester = ?
```

## API Endpoints

### Current Operations (Updated Queries)

| Endpoint | Purpose | Data Source |
|----------|---------|-------------|
| `GET /api/filter_teachers.php` | Search/filter teachers | `teachers` (is_archived=0) |
| `GET /api/filter_subjects.php` | Search/filter subjects | `subjects` (is_archived=0) |
| `GET /api/get_teacher_load.php` | Get teacher load | `teachers`, `assignments` (current) |
| `POST /api/add_teacher.php` | Add new teacher | `teachers` |
| `POST /api/update_teacher.php` | Update teacher | `teachers` |
| `POST /api/archive_teacher.php` | Archive teacher | Sets `is_archived = 1` |

### Predictive Analytics (New Endpoints)

| Endpoint | Purpose | Parameters |
|----------|---------|------------|
| `GET /api/predictive_analytics.php?endpoint=workload_trends` | Teacher workload trends over time | `email` (optional) |
| `GET /api/predictive_analytics.php?endpoint=assignment_patterns` | Subject assignment history | `course_code` (optional) |
| `GET /api/predictive_analytics.php?endpoint=academic_comparison` | Compare stats across years | None |
| `GET /api/predictive_analytics.php?endpoint=teaching_load_stats` | Load statistics by type | `year` (optional) |
| `GET /api/predictive_analytics.php?endpoint=expertise_distribution` | Expertise by period | `year`, `semester` (optional) |
| `GET /api/predictive_analytics.php?endpoint=predict_shortage` | Predict teacher shortage | None |
| `POST /api/import_historical_data.php` | Import historical CSV data | `academic_year`, `semester` |

## Implementation Checklist

### ✅ Completed

- [x] New schema with separated current and historical tables
- [x] Created `import_historical_data.php` for CSV ingestion
- [x] Created `predictive_analytics.php` with multiple analytics endpoints
- [x] Database files updated: `database.sql` and `database/smartload_new.sql`

### 📋 Configuration Steps

1. **Backup Current Database**
   ```sql
   -- Optional: back up current data before migration
   CREATE TABLE teachers_backup AS SELECT * FROM teachers;
   ```

2. **Import New Schema**
   - Use `database/smartload_new.sql` to create new tables
   - Run: `mysql smartload < database/smartload_new.sql`

3. **Migrate Current Data (Optional)**
   - If you have current data, keep it in current tables
   - Historical tables remain empty until import

4. **Import Historical Data**
   - POST request to `/api/import_historical_data.php`
   - For each historical CSV set:
     ```bash
     curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
       -H "Content-Type: application/json" \
       -d '{"academic_year":"2024-2025","semester":"1stSem"}'
     ```

## Key Benefits

| Feature | Before | After |
|---------|--------|-------|
| Data Isolation | Mixed | Separated |
| Homepage Performance | May include archived | Only current data |
| Analytics Accuracy | Historical mixed with current | Pure historical records |
| Growth Scalability | Degraded with time | Optimized with archival |
| Audit Trail | No time series | Full academic timeline |

## Migration Path for Existing Systems

If upgrading from version 1.0:

1. Create new historical tables (from `smartload_new.sql`)
2. Existing `teachers`, `subjects`, `schedules`, `assignments` tables unchanged
3. Archive old academic cycles: `UPDATE teachers SET is_archived = 1 WHERE ...`
4. Import archived data to `historical_*` tables via `import_historical_data.php`
5. Current tables now only reflect active academic term

## Notes

- **Current Tables** should only contain active semester data
- **Archiving** is done via `is_archived` flag, not deletion
- **Historical Import** is idempotent (can be run multiple times)
- **Analytics** automatically filters to historical tables only
- **Backup** historical data periodically using exports

## Support

For issues with:
- Database migration → See `/database/smartload_new.sql`
- Historical imports → See `/api/import_historical_data.php`
- Analytics queries → See `/api/predictive_analytics.php`
