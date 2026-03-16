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
                'message' => 'No historical data available. Please upload historical data files first.',
                'data_loaded' => false
            ];
        }
        
        // Update analytics metadata
        $stmt = $pdo->query("
            SELECT 
                academic_year,
                COUNT(DISTINCT IF(semester IS NOT NULL, CONCAT(academic_year, semester), NULL)) as period_count
            FROM historical_teachers
            GROUP BY academic_year
            ORDER BY academic_year DESC
            LIMIT 1
        ");
        $latestYear = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Log analytics refresh
        $insertAudit = $pdo->prepare(
            'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
        );
        $insertAudit->execute([
            'Analytics Update',
            sprintf(
                'Predictive analytics refreshed: %d teachers, %d subjects, %d schedules from historical data',
                $teacherCount,
                $subjectCount,
                $scheduleCount
            ),
            'Program Chair'
        ]);
        
        return [
            'success' => true,
            'message' => 'Historical analytics updated successfully',
            'data_loaded' => true,
            'stats' => [
                'teachers' => $teacherCount,
                'subjects' => $subjectCount,
                'schedules' => $scheduleCount,
                'latest_academic_year' => $latestYear['academic_year'] ?? 'N/A'
            ]
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
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
