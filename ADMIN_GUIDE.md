# SmartLoad Administration Guide - Historical Data Management

## Quick Start

### Step 1: Set Up New Database Schema
```bash
# Option A: Drop and recreate (production: backup first!)
mysql smartload < database/smartload_new.sql

# Option B: Add historical tables to existing database
mysql smartload < database/add_historical_tables.sql
```

### Step 2: Import Historical Data
Use the import endpoint for each historical dataset:

```bash
# Import AY 2024-2025 1st Semester
curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
  -H "Content-Type: application/json" \
  -d '{"academic_year":"2024-2025","semester":"1stSem"}'

# Import AY 2024-2025 2nd Semester
curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
  -H "Content-Type: application/json" \
  -d '{"academic_year":"2024-2025","semester":"2ndSem"}'

# Import AY 2025-2026 1st Semester
curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
  -H "Content-Type: application/json" \
  -d '{"academic_year":"2025-2026","semester":"1stSem"}'
```

### Step 3: Verify Homepage Shows Only Current Data
- Teachers page: Shows `is_archived = 0` only
- Subjects page: Shows `is_archived = 0` only
- Schedules page: Only for non-archived subjects
- Load reports: Only current assignments

### Step 4: Access Predictive Analytics
Navigate to `/api/predictive_analytics.php?endpoint=list_available` to see:

**Available Analytics Endpoints:**
```
- workload_trends: Teacher load over time
- assignment_patterns: Subject assignments history
- academic_comparison: Compare across years
- expertise_distribution: Skills by period
- teaching_load_stats: Load statistics by type
- predict_shortage: Shortage forecast
```

## Archiving Workflow

### Archive a Teacher (End of Semester)
```sql
UPDATE teachers 
SET is_archived = 1 
WHERE id = ? 
  AND is_archived = 0;
```

### Archive a Subject (End of Semester)
```sql
UPDATE subjects 
SET is_archived = 1 
WHERE id = ? 
  AND is_archived = 0;
```

### Via API
```bash
curl -X POST http://localhost/SmartLoad/api/archive_teacher.php \
  -H "Content-Type: application/json" \
  -d '{"id":5,"reason":"End of AY 2025-2026"}'
```

## Data Migration Guidelines

### Scenario: New Academic Year

1. **Export previous semester** (optional backup)
   ```sql
   SELECT * FROM assignments WHERE created_at >= '2025-12-01';
   ```

2. **Archive old data**
   ```sql
   UPDATE teachers SET is_archived = 1 WHERE hire_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   UPDATE subjects SET is_archived = 1 WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

3. **Import historical records** (via import_historical_data.php)
   ```json
   {"academic_year":"2025-2026","semester":"1stSem"}
   ```

4. **Add new teachers** to `teachers` table (not historical)

5. **Add new subjects** to `subjects` table (not historical)

### Scenario: Bulk Historical Upload

```php
<?php
// Upload multiple historical datasets
$datasets = [
    ['year' => '2024-2025', 'sem' => '1stSem'],
    ['year' => '2024-2025', 'sem' => '2ndSem'],
    ['year' => '2025-2026', 'sem' => '1stSem'],
];

foreach ($datasets as $data) {
    $response = file_get_contents(
        'http://localhost/SmartLoad/api/import_historical_data.php',
        false,
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'academic_year' => $data['year'],
                    'semester' => $data['sem']
                ])
            ]
        ])
    );
    echo "Imported {$data['year']} {$data['sem']}: $response\n";
}
?>
```

## Analytics Queries

### Example 1: Get Teacher Workload Trends
```bash
curl "http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=workload_trends"
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "period": "2025-2026 - 1st Semester",
      "teacher_count": 12,
      "avg_load": 15.5,
      "max_load": 18,
      "min_load": 9
    }
  ]
}
```

### Example 2: Specific Teacher Trends
```bash
curl "http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=workload_trends&email=john.doe@university.edu"
```

### Example 3: Subject Assignment Patterns
```bash
curl "http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=assignment_patterns"
```

### Example 4: Teaching Load Statistics
```bash
curl "http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=teaching_load_stats&year=2024-2025"
```

## Maintenance Tasks

### Weekly
- Monitor current table sizes (no growth = good)
- Check historical import logs for errors

### Monthly
- Verify `is_archived` counts: `SELECT COUNT(*) FROM teachers WHERE is_archived = 1;`
- Backup historical tables: `mysqldump smartload historical_* > backup.sql`

### Semester End
1. Archive completed teachers/subjects
2. Generate historical analytics
3. Export to CSV for external tools
4. Clean up temporary files in `/files/`

### Quarterly
- Review analytics patterns
- Identify trends in teacher loads
- Plan for upcoming semester

## Troubleshooting

### Issue: Historical Import Fails
**Solution:** Check CSV format matches headers:
```csv
name,email,type,max_units,expertise_tags
John Doe,john.doe@university.edu,Full-time,18,"PHP, MySQL"
```

### Issue: Homepage Shows Old Data
**Solution:** Verify queries use `is_archived = 0`:
```sql
SELECT * FROM teachers WHERE is_archived = 0;
```

### Issue: Analytics Returns Empty
**Solution:** 
1. Verify historical tables populated: `SELECT COUNT(*) FROM historical_teachers;`
2. Check academic_year format: `SELECT DISTINCT academic_year FROM historical_teachers;`
3. Ensure import ran successfully: `SELECT * FROM historical_analytics_metadata;`

### Issue: Duplicate Historical Records
**Solution:** Import creates duplicates if run twice
```sql
-- Clear and reimport
DELETE FROM historical_teachers WHERE academic_year = '2024-2025';
-- Then reimport via API
```

## Performance Optimization

### For Current Data Queries
```sql
-- Ensure indexes exist
CREATE INDEX idx_teachers_archived ON teachers(is_archived);
CREATE INDEX idx_subjects_archived ON subjects(is_archived);

-- Query optimization
SELECT * FROM teachers WHERE is_archived = 0 LIMIT 1000;
```

### For Historical Analytics
```sql
-- Query optimization for trend analysis
SELECT academic_year, semester, AVG(units_assigned)
FROM historical_teachers
GROUP BY academic_year, semester
ORDER BY academic_year DESC;

-- Use EXPLAIN to check index usage
EXPLAIN SELECT * FROM historical_teachers 
WHERE academic_year = '2024-2025' AND semester = '1stSem';
```

## Backup Strategy

### Daily
```bash
# Backup current operations
mysqldump smartload teachers subjects schedules assignments > daily_current.sql
```

### Weekly
```bash
# Backup everything
mysqldump smartload > weekly_full.sql
```

### Quarterly
```bash
# Archive entire historical tables
mysqldump smartload historical_* > quarterly_archive_$(date +%Y-%m-%d).sql
```

## Rollback Procedure

If data gets corrupted:

1. **Stop application**
2. **Restore from backup**
   ```bash
   mysql smartload < backup_date.sql
   ```
3. **Verify data integrity**
   ```sql
   SELECT COUNT(*) FROM teachers WHERE is_archived = 0;
   SELECT COUNT(*) FROM historical_teachers;
   ```
4. **Restart application**

## Key Reminders

✅ **Do:**
- Archive instead of delete
- Use separate queries for current vs. historical
- Backup before major operations
- Verify imports with LIMIT 10 first

❌ **Don't:**
- Delete records from current tables
- Mix current and historical in reports
- Update academic_year in historical records
- Run imports without backups

## Support Commands

```bash
# Check database size
mysql -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) 
          FROM information_schema.TABLES WHERE table_schema = 'smartload';"

# Count records by table
mysql smartload -e "SELECT 'teachers' as tbl, COUNT(*) FROM teachers UNION
                     SELECT 'subjects', COUNT(*) FROM subjects UNION
                     SELECT 'historical_teachers', COUNT(*) FROM historical_teachers;"

# View last imports
mysql smartload -e "SELECT * FROM historical_analytics_metadata ORDER BY import_date DESC LIMIT 5;"

# Monitor active connections
mysql -e "SHOW PROCESSLIST;" | grep smartload
```
