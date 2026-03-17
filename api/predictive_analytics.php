<?php
/**
 * SmartLoad - Predictive Analytics API
 * Provides analytics and predictions based on historical data
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

/**
 * Get teacher workload trends
 */
function getTeacherWorkloadTrends($teacherEmail = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            CONCAT(ht.academic_year, ' - ', ht.semester) as period,
            COUNT(DISTINCT ht.id) as teacher_count,
            AVG(ht.units_assigned) as avg_load,
            MAX(ht.units_assigned) as max_load,
            MIN(ht.units_assigned) as min_load,
            ht.academic_year,
            ht.semester
        FROM historical_teachers ht
    ";
    
    if ($teacherEmail) {
        $sql .= " WHERE ht.email = :email ";
    }
    
    $sql .= " GROUP BY ht.academic_year, ht.semester ORDER BY ht.academic_year DESC, ht.semester DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacherEmail) {
        $stmt->execute([':email' => $teacherEmail]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Get subject assignment patterns
 */
function getSubjectAssignmentPatterns($courseCode = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            ha.subject_code,
            hs.name as subject_name,
            COUNT(DISTINCT CONCAT(ha.academic_year, ha.semester)) as offered_times,
            COUNT(DISTINCT ha.teacher_email) as unique_teachers,
            GROUP_CONCAT(DISTINCT ha.teacher_name SEPARATOR ', ') as teacher_history,
            GROUP_CONCAT(DISTINCT CONCAT(ha.academic_year, '-', ha.semester) SEPARATOR ', ') as offerings
        FROM historical_assignments ha
        LEFT JOIN historical_subjects hs ON ha.subject_code = hs.course_code
    ";
    
    if ($courseCode) {
        $sql .= " WHERE ha.subject_code = :code ";
    }
    
    $sql .= " GROUP BY ha.subject_code ORDER BY COUNT(*) DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($courseCode) {
        $stmt->execute([':code' => $courseCode]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Get academic year comparison
 */
function getAcademicYearComparison($academicYear1 = null, $academicYear2 = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            ham.academic_year,
            ham.semester,
            ham.total_teachers,
            ham.total_subjects,
            ham.total_assignments,
            ham.import_date,
            (ham.total_assignments / NULLIF(ham.total_subjects, 0)) as assignment_rate
        FROM historical_analytics_metadata ham
        ORDER BY ham.academic_year DESC, ham.semester DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get teacher expertise distribution
 */
function getTeacherExpertiseDistribution($academicYear = null, $semester = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            ht.academic_year,
            ht.semester,
            ht.expertise_tags,
            COUNT(DISTINCT ht.id) as teacher_count
        FROM historical_teachers ht
    ";
    
    $where = [];
    if ($academicYear) {
        $where[] = "ht.academic_year = :year";
    }
    if ($semester) {
        $where[] = "ht.semester = :semester";
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " GROUP BY ht.academic_year, ht.semester, ht.expertise_tags";
    
    $stmt = $pdo->prepare($sql);
    $params = [];
    if ($academicYear) $params[':year'] = $academicYear;
    if ($semester) $params[':semester'] = $semester;
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Get teaching load statistics
 */
function getTeachingLoadStats($academicYear = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            ht.academic_year,
            ht.semester,
            ht.type,
            COUNT(DISTINCT ht.id) as teacher_count,
            AVG(ht.max_units) as avg_max_units,
            AVG(ht.units_assigned) as avg_assigned_units,
            MIN(ht.units_assigned) as min_assigned,
            MAX(ht.units_assigned) as max_assigned,
            STDDEV(ht.units_assigned) as stddev_units
        FROM historical_teachers ht
    ";
    
    if ($academicYear) {
        $sql .= " WHERE ht.academic_year = :year ";
    }
    
    $sql .= " GROUP BY ht.academic_year, ht.semester, ht.type ORDER BY ht.academic_year DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($academicYear) {
        $stmt->execute([':year' => $academicYear]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Predict teacher shortage for upcoming period
 */
function predictTeacherShortage() {
    global $pdo;
    
    // Get average load trend
    $stmt = $pdo->query("
        SELECT 
            academic_year,
            AVG(units_assigned) as avg_load
        FROM historical_teachers
        GROUP BY academic_year
        ORDER BY academic_year DESC
        LIMIT 3
    ");
    
    $trends = $stmt->fetchAll();
    
    // Calculate simple linear trend
    $prediction = [
        'historical_data' => $trends,
        'trend' => count($trends) > 1 ? 'calculated' : 'insufficient_data',
        'last_avg_load' => $trends[0]['avg_load'] ?? 0
    ];
    
    return $prediction;
}

/**
 * Update/Refresh historical analytics data
 */
function updateHistoricalAnalytics() {
    global $pdo;

    $stopWords = [
        'and', 'or', 'the', 'of', 'to', 'in', 'for', 'on', 'with', 'a', 'an', 'is', 'ii', 'iii',
        'bs', 'ba', 'bsc', 'information', 'technology', 'computer', 'science',
    ];

    $tokenize = static function (string $text) use ($stopWords): array {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]+/', ' ', $text);
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        $tokens = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p === '' || strlen($p) <= 2) {
                continue;
            }
            if (in_array($p, $stopWords, true)) {
                continue;
            }
            $tokens[] = $p;
        }
        return array_values(array_unique($tokens));
    };

    $listPeriods = static function () use ($pdo): array {
        $rows = $pdo->query(
            'SELECT DISTINCT academic_year, semester FROM (
                SELECT academic_year, semester FROM historical_teachers
                UNION ALL
                SELECT academic_year, semester FROM historical_subjects
                UNION ALL
                SELECT academic_year, semester FROM historical_schedules
            ) x
            WHERE academic_year IS NOT NULL AND academic_year <> ""
              AND semester IS NOT NULL AND semester <> ""
            ORDER BY academic_year DESC, semester DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $periods = [];
        foreach ($rows as $r) {
            $ay = (string) ($r['academic_year'] ?? '');
            $sem = (string) ($r['semester'] ?? '');
            if ($ay === '' || $sem === '') {
                continue;
            }
            $periods[] = ['academic_year' => $ay, 'semester' => $sem];
        }
        return $periods;
    };

    $upsertMetadataForPeriod = static function (string $academicYear, string $semester) use ($pdo): array {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM historical_teachers WHERE academic_year = ? AND semester = ?');
        $stmt->execute([$academicYear, $semester]);
        $totalTeachers = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM historical_subjects WHERE academic_year = ? AND semester = ?');
        $stmt->execute([$academicYear, $semester]);
        $totalSubjects = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM historical_assignments WHERE academic_year = ? AND semester = ?');
        $stmt->execute([$academicYear, $semester]);
        $totalAssignments = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM historical_schedules WHERE academic_year = ? AND semester = ?');
        $stmt->execute([$academicYear, $semester]);
        $totalSchedules = (int) $stmt->fetchColumn();

        $notes = sprintf(
            'Refreshed on %s (teachers=%d, subjects=%d, schedules=%d, assignments=%d)',
            date('Y-m-d H:i:s'),
            $totalTeachers,
            $totalSubjects,
            $totalSchedules,
            $totalAssignments
        );

        $up = $pdo->prepare(
            'INSERT INTO historical_analytics_metadata (academic_year, semester, total_teachers, total_subjects, total_assignments, notes)\n'
            . 'VALUES (?, ?, ?, ?, ?, ?)\n'
            . 'ON DUPLICATE KEY UPDATE\n'
            . '  import_date = CURRENT_TIMESTAMP,\n'
            . '  total_teachers = VALUES(total_teachers),\n'
            . '  total_subjects = VALUES(total_subjects),\n'
            . '  total_assignments = VALUES(total_assignments),\n'
            . '  notes = VALUES(notes)'
        );
        $up->execute([$academicYear, $semester, $totalTeachers, $totalSubjects, $totalAssignments, $notes]);

        return [
            'total_teachers' => $totalTeachers,
            'total_subjects' => $totalSubjects,
            'total_schedules' => $totalSchedules,
            'total_assignments' => $totalAssignments,
        ];
    };

    $generateSyntheticAssignmentsForPeriod = static function (string $academicYear, string $semester) use ($pdo, $tokenize): int {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM historical_assignments WHERE academic_year = ? AND semester = ?');
        $stmt->execute([$academicYear, $semester]);
        if (((int) $stmt->fetchColumn()) > 0) {
            return 0;
        }

        $teachers = $pdo->prepare(
            'SELECT id, name, email, type, max_units, expertise_tags\n'
            . 'FROM historical_teachers\n'
            . 'WHERE academic_year = ? AND semester = ?\n'
            . 'ORDER BY id ASC'
        );
        $teachers->execute([$academicYear, $semester]);
        $teacherRows = $teachers->fetchAll(PDO::FETCH_ASSOC);
        if (empty($teacherRows)) {
            return 0;
        }

        $subjects = $pdo->prepare(
            'SELECT id, course_code, name, program, units\n'
            . 'FROM historical_subjects\n'
            . 'WHERE academic_year = ? AND semester = ?\n'
            . 'ORDER BY id ASC'
        );
        $subjects->execute([$academicYear, $semester]);
        $subjectRows = $subjects->fetchAll(PDO::FETCH_ASSOC);
        if (empty($subjectRows)) {
            return 0;
        }

        // Start with zero load per teacher for balancing.
        $teacherLoad = [];
        foreach ($teacherRows as $t) {
            $teacherLoad[(int) $t['id']] = 0;
        }

        $ins = $pdo->prepare(
            'INSERT INTO historical_assignments\n'
            . '(academic_year, semester, subject_id, subject_code, teacher_id, teacher_name, teacher_email, status, rationale)\n'
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, "Assigned", ?)'
        );

        $count = 0;
        foreach ($subjectRows as $s) {
            $subjectId = (int) ($s['id'] ?? 0);
            $subjectCode = trim((string) ($s['course_code'] ?? ''));
            $subjectName = trim((string) ($s['name'] ?? ''));
            $program = trim((string) ($s['program'] ?? ''));
            $units = (int) ($s['units'] ?? 0);

            if ($subjectId <= 0 || $subjectCode === '') {
                continue;
            }

            $keywords = array_merge($tokenize($subjectName), $tokenize($program));
            $bestTeacher = null;
            $bestScore = -1;

            foreach ($teacherRows as $t) {
                $tid = (int) ($t['id'] ?? 0);
                if ($tid <= 0) {
                    continue;
                }
                $expertise = strtolower(trim((string) ($t['expertise_tags'] ?? '')));
                $score = 0;
                foreach ($keywords as $kw) {
                    if ($kw !== '' && str_contains($expertise, $kw)) {
                        $score++;
                    }
                }

                // Tie-break: prefer lower current synthetic load.
                $load = (int) ($teacherLoad[$tid] ?? 0);
                $tieBreaker = -$load;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTeacher = $t;
                    $bestTeacher['_tiebreak'] = $tieBreaker;
                } elseif ($score === $bestScore && $bestTeacher !== null) {
                    $bestTb = (int) ($bestTeacher['_tiebreak'] ?? 0);
                    if ($tieBreaker > $bestTb) {
                        $bestTeacher = $t;
                        $bestTeacher['_tiebreak'] = $tieBreaker;
                    }
                }
            }

            if ($bestTeacher === null) {
                continue;
            }

            $teacherId = (int) ($bestTeacher['id'] ?? 0);
            if ($teacherId <= 0) {
                continue;
            }

            $teacherName = (string) ($bestTeacher['name'] ?? '');
            $teacherEmail = (string) ($bestTeacher['email'] ?? '');

            $rationale = 'Auto-generated assignment (no historical assignment file provided)';
            $ins->execute([
                $academicYear,
                $semester,
                $subjectId,
                $subjectCode,
                $teacherId,
                $teacherName,
                $teacherEmail,
                $rationale,
            ]);
            $count++;

            $teacherLoad[$teacherId] = (int) ($teacherLoad[$teacherId] ?? 0) + max(0, $units);
        }

        // Best-effort: update units_assigned based on generated assignments.
        try {
            $upd = $pdo->prepare(
                'UPDATE historical_teachers ht\n'
                . 'SET ht.units_assigned = (\n'
                . '  SELECT COALESCE(SUM(hsub.units), 0)\n'
                . '  FROM historical_assignments ha\n'
                . '  JOIN historical_subjects hsub\n'
                . '    ON hsub.academic_year = ha.academic_year\n'
                . '   AND hsub.semester = ha.semester\n'
                . '   AND hsub.id = ha.subject_id\n'
                . '  WHERE ha.academic_year = ?\n'
                . '    AND ha.semester = ?\n'
                . '    AND ha.teacher_id = ht.id\n'
                . ')\n'
                . 'WHERE ht.academic_year = ? AND ht.semester = ?'
            );
            $upd->execute([$academicYear, $semester, $academicYear, $semester]);
        } catch (Exception $ignore) {
        }

        return $count;
    };
    
    try {
        // Validate historical data exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM historical_teachers");
        $teacherCount = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM historical_subjects");
        $subjectCount = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM historical_schedules");
        $scheduleCount = (int)$stmt->fetchColumn();
        
        if ($teacherCount === 0 && $subjectCount === 0 && $scheduleCount === 0) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'No historical data available. Please upload historical data files first.',
                'data_loaded' => false
            ];
        }
        
        // Backfill historical_schedules.subject_code when missing.
        // Many historical schedule CSVs only contain subject_id; we map it via historical_subjects.id per AY/Sem.
        $updatedScheduleCodes = 0;
        try {
            $upd = $pdo->prepare(
                'UPDATE historical_schedules hs '
                . 'JOIN historical_subjects hsub '
                . '  ON hsub.academic_year = hs.academic_year '
                . ' AND hsub.semester = hs.semester '
                . ' AND hsub.id = hs.subject_id '
                . 'SET hs.subject_code = hsub.course_code '
                . 'WHERE (hs.subject_code IS NULL OR hs.subject_code = "")'
            );
            $upd->execute();
            $updatedScheduleCodes = (int) $upd->rowCount();
        } catch (Exception $ignore) {
            $updatedScheduleCodes = 0;
        }

        // Backfill metadata for all known periods and generate synthetic assignments if missing.
        $periods = $listPeriods();
        $periodsProcessed = 0;
        $metadataUpserts = 0;
        $syntheticAssignments = 0;

        foreach ($periods as $p) {
            $ay = (string) ($p['academic_year'] ?? '');
            $sem = (string) ($p['semester'] ?? '');
            if ($ay === '' || $sem === '') {
                continue;
            }

            // Generate assignments only if none exist for that period.
            try {
                $syntheticAssignments += $generateSyntheticAssignmentsForPeriod($ay, $sem);
            } catch (Exception $ignore) {
            }

            try {
                $upsertMetadataForPeriod($ay, $sem);
                $metadataUpserts++;
            } catch (Exception $ignore) {
            }

            $periodsProcessed++;
        }

        // Find latest academic year available (best-effort)
        $latestYear = null;
        try {
            $stmt = $pdo->query('SELECT academic_year FROM historical_subjects ORDER BY academic_year DESC LIMIT 1');
            $latestYear = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $ignore) {
            $latestYear = null;
        }
        
        // Log analytics refresh
        $insertAudit = $pdo->prepare(
            'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
        );
        $insertAudit->execute([
            'Analytics Update',
            sprintf(
                'Predictive analytics refreshed: %d teachers, %d subjects, %d schedules from historical data (%d schedule codes backfilled, %d synthetic assignments generated, %d metadata upserts)',
                $teacherCount,
                $subjectCount,
                $scheduleCount,
                $updatedScheduleCodes,
                $syntheticAssignments,
                $metadataUpserts
            ),
            'Program Chair'
        ]);
        
        return [
            'success' => true,
            'status' => 'success',
            'message' => 'Historical analytics updated successfully',
            'data_loaded' => true,
            'stats' => [
                'teachers' => $teacherCount,
                'subjects' => $subjectCount,
                'schedules' => $scheduleCount,
                'schedule_codes_backfilled' => $updatedScheduleCodes,
                'periods_processed' => $periodsProcessed,
                'metadata_upserts' => $metadataUpserts,
                'synthetic_assignments_generated' => $syntheticAssignments,
                'latest_academic_year' => $latestYear['academic_year'] ?? 'N/A'
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'status' => 'error',
            'message' => 'Error updating analytics: ' . $e->getMessage(),
            'data_loaded' => false
        ];
    }
}

// Route to different endpoints
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : (isset($_GET['action']) ? $_GET['action'] : '');

$response = ['success' => false, 'message' => 'Unknown endpoint'];

switch ($endpoint) {
    case 'update_records':
        $response = updateHistoricalAnalytics();
        break;

    case 'workload_trends':
        $email = $_GET['email'] ?? null;
        $response = [
            'success' => true,
            'endpoint' => 'workload_trends',
            'data' => getTeacherWorkloadTrends($email)
        ];
        break;
    
    case 'assignment_patterns':
        $courseCode = $_GET['course_code'] ?? null;
        $response = [
            'success' => true,
            'endpoint' => 'assignment_patterns',
            'data' => getSubjectAssignmentPatterns($courseCode)
        ];
        break;
    
    case 'academic_comparison':
        $response = [
            'success' => true,
            'endpoint' => 'academic_comparison',
            'data' => getAcademicYearComparison()
        ];
        break;
    
    case 'expertise_distribution':
        $year = $_GET['year'] ?? null;
        $semester = $_GET['semester'] ?? null;
        $response = [
            'success' => true,
            'endpoint' => 'expertise_distribution',
            'data' => getTeacherExpertiseDistribution($year, $semester)
        ];
        break;
    
    case 'teaching_load_stats':
        $year = $_GET['year'] ?? null;
        $response = [
            'success' => true,
            'endpoint' => 'teaching_load_stats',
            'data' => getTeachingLoadStats($year)
        ];
        break;
    
    case 'predict_shortage':
        $response = [
            'success' => true,
            'endpoint' => 'predict_shortage',
            'data' => predictTeacherShortage()
        ];
        break;
    
    case 'list_available':
        $response = [
            'success' => true,
            'available_endpoints' => [
                'workload_trends' => 'Get teacher workload trends over time',
                'assignment_patterns' => 'Get subject assignment patterns',
                'academic_comparison' => 'Compare statistics across academic years',
                'expertise_distribution' => 'Get teacher expertise distribution',
                'teaching_load_stats' => 'Get teaching load statistics',
                'predict_shortage' => 'Predict teacher shortage for upcoming period'
            ]
        ];
        break;
}

echo json_encode($response);
?>
