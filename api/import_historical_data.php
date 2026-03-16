<?php
/**
 * SmartLoad - Historical Data Import Script
 * Imports historical data from CSV files into the historical tables
 * Usage: Call via POST to /api/import_historical_data.php with academic_year and semester parameters
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Function to clean and normalize academic year format
function normalizeAcademicYear($filename) {
    // Extract from filename like "teacher_AY2024-2025_1stSem.csv"
    if (preg_match('/AY(\d{4}-\d{4})/', $filename, $matches)) {
        return $matches[1];
    }
    return null;
}

// Function to extract semester from filename
function extractSemester($filename) {
    if (preg_match('/(1st|2nd)Sem/', $filename, $matches)) {
        return $matches[1] . ' Semester';
    }
    return null;
}

/**
 * Import teachers from CSV to historical_teachers table
 */
function importHistoricalTeachers($filepath, $academicYear, $semester) {
    global $pdo;
    
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => "File not found: $filepath"];
    }
    
    $count = 0;
    try {
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue; // Skip empty rows
                
                $data = array_combine($headers, $row);
                
                $stmt = $pdo->prepare("
                    INSERT INTO historical_teachers 
                    (academic_year, semester, name, email, type, max_units, expertise_tags)
                    VALUES (:year, :semester, :name, :email, :type, :max_units, :expertise)
                ");
                
                $stmt->execute([
                    ':year' => $academicYear,
                    ':semester' => $semester,
                    ':name' => trim($data['name'] ?? ''),
                    ':email' => trim($data['email'] ?? ''),
                    ':type' => trim($data['type'] ?? 'Part-time'),
                    ':max_units' => (int)($data['max_units'] ?? 12),
                    ':expertise' => trim($data['expertise_tags'] ?? '')
                ]);
                
                $count++;
            }
            fclose($handle);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error importing teachers: " . $e->getMessage()];
    }
    
    return ['success' => true, 'count' => $count, 'message' => "Imported $count teachers"];
}

/**
 * Import subjects from CSV to historical_subjects table
 */
function importHistoricalSubjects($filepath, $academicYear, $semester) {
    global $pdo;
    
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => "File not found: $filepath"];
    }
    
    $count = 0;
    try {
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue;
                
                $data = array_combine($headers, $row);
                
                $stmt = $pdo->prepare("
                    INSERT INTO historical_subjects 
                    (academic_year, semester, course_code, name, program, units, prerequisites)
                    VALUES (:year, :semester, :code, :name, :program, :units, :prereq)
                ");
                
                $stmt->execute([
                    ':year' => $academicYear,
                    ':semester' => $semester,
                    ':code' => trim($data['course_code'] ?? ''),
                    ':name' => trim($data['name'] ?? ''),
                    ':program' => trim($data['program'] ?? ''),
                    ':units' => (int)($data['units'] ?? 3),
                    ':prereq' => trim($data['prerequisites'] ?? '')
                ]);
                
                $count++;
            }
            fclose($handle);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error importing subjects: " . $e->getMessage()];
    }
    
    return ['success' => true, 'count' => $count, 'message' => "Imported $count subjects"];
}

/**
 * Import schedules from CSV to historical_schedules table
 */
function importHistoricalSchedules($filepath, $academicYear, $semester) {
    global $pdo;
    
    if (!file_exists($filepath)) {
        return ['success' => false, 'message' => "File not found: $filepath"];
    }
    
    $count = 0;
    try {
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            
            // First, get the course codes to subject mapping
            $subjectStmt = $pdo->prepare("
                SELECT id, course_code FROM historical_subjects 
                WHERE academic_year = :year AND semester = :semester
            ");
            $subjectStmt->execute([':year' => $academicYear, ':semester' => $semester]);
            $subjectMap = [];
            foreach ($subjectStmt->fetchAll() as $row) {
                $subjectMap[$row['course_code']] = $row['id'];
            }
            
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row[0])) continue;
                
                $data = array_combine($headers, $row);
                
                // Try to find subject code from schedule, or use subject_id directly
                $subjectId = isset($data['subject_id']) ? (int)$data['subject_id'] : null;
                $subjectCode = $data['subject_code'] ?? '';
                
                $stmt = $pdo->prepare("
                    INSERT INTO historical_schedules 
                    (academic_year, semester, subject_id, subject_code, day_of_week, start_time, end_time, room, section)
                    VALUES (:year, :semester, :subject_id, :code, :day, :start, :end, :room, :section)
                ");
                
                $stmt->execute([
                    ':year' => $academicYear,
                    ':semester' => $semester,
                    ':subject_id' => $subjectId ?? 0,
                    ':code' => $subjectCode,
                    ':day' => trim($data['day_of_week'] ?? ''),
                    ':start' => trim($data['start_time'] ?? ''),
                    ':end' => trim($data['end_time'] ?? ''),
                    ':room' => trim($data['room'] ?? ''),
                    ':section' => trim($data['section'] ?? '')
                ]);
                
                $count++;
            }
            fclose($handle);
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error importing schedules: " . $e->getMessage()];
    }
    
    return ['success' => true, 'count' => $count, 'message' => "Imported $count schedules"];
}

/**
 * Main import function
 */
function importHistoricalData($academicYear, $semester) {
    $historicalDir = __DIR__ . '/../files/historical/';
    
    $results = [];
    
    // Import teachers
    $teacherFile = $historicalDir . "teacher_AY{$academicYear}_{$semester}.csv";
    $results['teachers'] = importHistoricalTeachers($teacherFile, $academicYear, $semester);
    
    // Import subjects
    $subjectFile = $historicalDir . "subject_AY{$academicYear}_{$semester}.csv";
    $results['subjects'] = importHistoricalSubjects($subjectFile, $academicYear, $semester);
    
    // Import schedules
    $scheduleFile = $historicalDir . "schedule_AY{$academicYear}_{$semester}.csv";
    $results['schedules'] = importHistoricalSchedules($scheduleFile, $academicYear, $semester);
    
    // Record metadata
    try {
        $totalTeachers = $results['teachers']['count'] ?? 0;
        $totalSubjects = $results['subjects']['count'] ?? 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO historical_analytics_metadata 
            (academic_year, semester, total_teachers, total_subjects, total_assignments, notes)
            VALUES (:year, :semester, :teachers, :subjects, :assignments, :notes)
            ON DUPLICATE KEY UPDATE 
                total_teachers = :teachers, 
                total_subjects = :subjects,
                total_assignments = :assignments
        ");
        
        $stmt->execute([
            ':year' => $academicYear,
            ':semester' => $semester,
            ':teachers' => $totalTeachers,
            ':subjects' => $totalSubjects,
            ':assignments' => 0,
            ':notes' => "Imported on " . date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        $results['metadata'] = ['success' => false, 'message' => $e->getMessage()];
    }
    
    return $results;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $academicYear = $data['academic_year'] ?? '2024-2025';
    $semester = $data['semester'] ?? '1stSem';
    
    // Import the data
    $results = importHistoricalData($academicYear, $semester);
    
    // Check if all imports were successful
    $allSuccessful = true;
    foreach ($results as $key => $result) {
        if (isset($result['success']) && !$result['success']) {
            $allSuccessful = false;
            break;
        }
    }
    
    echo json_encode([
        'success' => $allSuccessful,
        'academic_year' => $academicYear,
        'semester' => $semester,
        'results' => $results,
        'message' => $allSuccessful ? 'Historical data imported successfully' : 'Some imports failed'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are accepted']);
}
?>
