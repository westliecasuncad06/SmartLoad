# SmartLoad v2.0 Quick Reference Guide

## 🚀 Quick Start (5 Steps)

### Step 1: Import Database Schema
```bash
# Fresh install (recommended)
mysql -u root smartload < database/smartload_new.sql

# OR upgrade existing
mysqldump smartload > backup.sql
mysql -u root smartload < database/add_historical_tables.sql
```

### Step 2: Import Historical Data
```bash
curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
  -H "Content-Type: application/json" \
  -d '{"academic_year":"2024-2025","semester":"1stSem"}'
```

### Step 3: Verify Homepage
- Open http://localhost/SmartLoad/index.php
- Check Teachers tab - should show current data only
- No historical data should appear

### Step 4: Test Analytics
```bash
# Test analytics endpoint
curl http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=list_available

# Get academic comparison
curl http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=academic_comparison
```

### Step 5: Archive Old Data (Optional)
```sql
-- At semester end
UPDATE teachers SET is_archived = 1 WHERE previous_year = 1;
UPDATE subjects SET is_archived = 1 WHERE previous_year = 1;
```

---

## 📊 Database Structure at a Glance

### Current Data (Homepage)
```
teachers (is_archived=0 only)
├─ id, name, email, type, max_units, current_units
├─ expertise_tags, is_archived, created_at

subjects (is_archived=0 only)
├─ id, course_code, name, program, units
├─ prerequisites, is_archived, created_at

schedules
├─ subject_id, day_of_week, start_time, end_time, room

assignments
├─ subject_id, teacher_id, status, rationale, created_at
```

### Historical Data (Analytics)
```
historical_teachers
├─ academic_year, semester, name, email, type
├─ max_units, units_assigned, expertise_tags

historical_subjects
├─ academic_year, semester, course_code, name, program, units

historical_schedules
├─ academic_year, semester, subject_code, day_of_week, times

historical_assignments
├─ academic_year, semester, subject_code, teacher_name
├─ teacher_email, status

historical_analytics_metadata
├─ academic_year, semester, total_teachers, total_subjects
├─ import_date, notes
```

---

## 🔌 API Quick Reference

### Import Historical Data
```
POST /api/import_historical_data.php

Request:
{
  "academic_year": "2024-2025",
  "semester": "1stSem"
}

Response:
{
  "success": true,
  "results": {
    "teachers": {"count": 12},
    "subjects": {"count": 14},
    "schedules": {"count": 15}
  }
}
```

### Analytics Endpoints
```
GET /api/predictive_analytics.php?endpoint=workload_trends
GET /api/predictive_analytics.php?endpoint=workload_trends&email=john@uni.edu
GET /api/predictive_analytics.php?endpoint=assignment_patterns
GET /api/predictive_analytics.php?endpoint=academic_comparison
GET /api/predictive_analytics.php?endpoint=teaching_load_stats&year=2024-2025
GET /api/predictive_analytics.php?endpoint=expertise_distribution
GET /api/predictive_analytics.php?endpoint=predict_shortage
GET /api/predictive_analytics.php?endpoint=list_available
```

---

## 📝 SQL Queries Cheat Sheet

### Current Data Queries
```sql
-- Show all active teachers
SELECT * FROM teachers WHERE is_archived = 0;

-- Show all active subjects
SELECT * FROM subjects WHERE is_archived = 0;

-- Archive a teacher
UPDATE teachers SET is_archived = 1 WHERE id = 5;

-- Count active vs archived
SELECT 
  SUM(IF(is_archived=0, 1, 0)) as active,
  SUM(IF(is_archived=1, 1, 0)) as archived
FROM teachers;
```

### Historical Data Queries
```sql
-- Show all historical records by year
SELECT academic_year, semester, COUNT(*) as count
FROM historical_teachers
GROUP BY academic_year, semester;

-- Get specific academic year
SELECT * FROM historical_teachers
WHERE academic_year = '2024-2025' AND semester = '1stSem';

-- Compare semesters
SELECT t1.academic_year, COUNT(t1.id) as sem1, COUNT(t2.id) as sem2
FROM historical_teachers t1
LEFT JOIN historical_teachers t2 
  ON t1.academic_year = t2.academic_year 
  AND t2.semester = '2ndSem'
WHERE t1.semester = '1stSem'
GROUP BY t1.academic_year;

-- Get import history
SELECT * FROM historical_analytics_metadata
ORDER BY import_date DESC;
```

---

## 🎯 Common Tasks

### Task: Add New Teacher for Current Semester
```sql
INSERT INTO teachers (name, email, type, max_units, current_units)
VALUES ('Jane Doe', 'jane.doe@uni.edu', 'Full-time', 18, 0);
```

### Task: Archive End-of-Semester Data
```sql
UPDATE teachers SET is_archived = 1 
WHERE current_units > 0 AND updated_at < '2025-08-31';

UPDATE subjects SET is_archived = 1 
WHERE id IN (SELECT subject_id FROM assignments WHERE created_at < '2025-08-31');
```

### Task: Import New Semester Historical Data
```bash
# November 2024 2nd semester data
curl -X POST http://localhost/SmartLoad/api/import_historical_data.php \
  -H "Content-Type: application/json" \
  -d '{"academic_year":"2024-2025","semester":"2ndSem"}'
```

### Task: Generate Workload Report
```bash
curl 'http://localhost/SmartLoad/api/predictive_analytics.php?endpoint=workload_trends' \
  -H "Content-Type: application/json" | python -m json.tool
```

### Task: Backup Database
```bash
mysqldump smartload > backup_$(date +%Y%m%d).sql
```

### Task: Verify Data Integrity
```sql
-- Check for archived data on homepage
SELECT * FROM teachers WHERE is_archived = 1;

-- Verify historical data exists
SELECT COUNT(*) FROM historical_teachers;

-- Check import metadata
SELECT academic_year, semester, import_date, total_teachers FROM historical_analytics_metadata;
```

---

## ⚙️ Configuration Files

### database/smartload_new.sql
- **Use for:** Fresh installations
- **Contains:** All current + historical tables
- **Command:** `mysql smartload < database/smartload_new.sql`

### database/add_historical_tables.sql
- **Use for:** Upgrading existing systems
- **Contains:** Only historical tables + migrations
- **Command:** `mysql smartload < database/add_historical_tables.sql`

### includes/db.php
- **No changes needed** - uses existing connection
- **Configuration:** Set $host, $dbname, $username, $password

---

## 🔍 Troubleshooting Quick Tips

| Problem | Solution |
|---------|----------|
| Homepage shows old data | Check `is_archived = 0` in queries |
| Import fails | Verify CSV headers match format |
| Analytics empty | Check `SELECT COUNT(*) FROM historical_teachers;` |
| Duplicate records | Clear table and reimport via API |
| Slow queries | Check indexes with `SHOW INDEX FROM historical_teachers;` |
| Permission error | Ensure MySQL user has all privileges on smartload database |

---

## 📂 File Locations

```
/database/
  ├─ smartload.sql (updated)
  ├─ smartload_new.sql (new - recommended)
  └─ add_historical_tables.sql (new - migration)

/api/
  ├─ import_historical_data.php (new)
  └─ predictive_analytics.php (new)

/files/historical/
  ├─ teacher_AY2024-2025_1stSem.csv
  ├─ subject_AY2024-2025_1stSem.csv
  ├─ schedule_AY2024-2025_1stSem.csv
  └─ ... (other years/semesters)

Documentation:
  ├─ DOCUMENTATION.md (updated)
  ├─ DATABASE_RECONSTRUCTION.md (new)
  ├─ ADMIN_GUIDE.md (new)
  ├─ IMPLEMENTATION_CHECKLIST.md (new)
  └─ RECONSTRUCTION_SUMMARY.md (new)
```

---

## 🚨 Important Reminders

✅ **DO:**
- Backup before major operations
- Use `is_archived` for archiving (not deletion)
- Import historical data to separate tables
- Verify data separation works

❌ **DON'T:**
- Mix current and historical in reports
- Delete records (use archiving instead)
- Modify `academic_year` in historical tables
- Update historical_* tables directly (import only)

---

## 📞 Getting Help

1. **Understanding Architecture?** → Read `DATABASE_RECONSTRUCTION.md`
2. **How to operate?** → Read `ADMIN_GUIDE.md`
3. **Step-by-step setup?** → Follow `IMPLEMENTATION_CHECKLIST.md`
4. **API details?** → Check `DOCUMENTATION.md`
5. **Specific issue?** → Search `ADMIN_GUIDE.md` troubleshooting

---

## ✅ Success Indicators

After setup, you should see:

- ✅ Homepage shows only current data (no archived/historical)
- ✅ Analytics endpoints return data from historical tables
- ✅ Import logs show successful imports
- ✅ Data counts match expected values
- ✅ No performance degradation
- ✅ Ability to generate trend reports

---

**Version:** 2.0 | **Updated:** March 17, 2026  
**Status:** ✅ Production Ready
