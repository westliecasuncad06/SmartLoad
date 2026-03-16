<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/GeminiAPI.php';

$ai = new GeminiEvaluator(GEMINI_API_KEY);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        exit;
    }

    // 1. Fetch all unassigned subjects (no approved/pending assignment yet)
    $unassignedStmt = $pdo->query(
        'SELECT s.*
         FROM subjects s
         LEFT JOIN assignments a ON a.subject_id = s.id AND a.status IN ("Pending", "Approved")
         WHERE a.id IS NULL'
    );
    $unassignedSubjects = $unassignedStmt->fetchAll();

    $assignedCount   = 0;
    $unassignedCount = 0;

    $insertAssignment = $pdo->prepare(
        'INSERT INTO assignments (subject_id, teacher_id, status, rationale) VALUES (?, ?, "Pending", ?)'
    );
    $updateUnits = $pdo->prepare(
        'UPDATE teachers SET current_units = current_units + ? WHERE id = ?'
    );
    $insertAudit = $pdo->prepare(
        'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
    );

    $pdo->beginTransaction();

    foreach ($unassignedSubjects as $subject) {
        // 2. Hard filter: eligible teachers whose capacity can absorb the subject units
        $eligibleStmt = $pdo->prepare(
            'SELECT * FROM teachers WHERE (current_units + ?) <= max_units ORDER BY name ASC'
        );
        $eligibleStmt->execute([$subject['units']]);
        $eligibleTeachers = $eligibleStmt->fetchAll();

        if (empty($eligibleTeachers)) {
            $unassignedCount++;
            continue;
        }

        // 3. Score each eligible teacher
        $bestTeacher  = null;
        $bestScore    = -1;
        $bestRationale = '';

        foreach ($eligibleTeachers as $teacher) {
            $aiResult = $ai->scoreExpertise(
                $teacher['expertise_tags'] ?? '',
                $subject['prerequisites'] ?? ''
            );

            $score     = (int) $aiResult['score'];
            $rationale = (string) $aiResult['rationale'];

            if ($score > $bestScore) {
                $bestScore     = $score;
                $bestTeacher   = $teacher;
                $bestRationale = $rationale;
            }
        }

        // 4. Assign the best teacher
        $insertAssignment->execute([
            $subject['id'],
            $bestTeacher['id'],
            $bestRationale,
        ]);

        // 5. Update current_units
        $updateUnits->execute([
            $subject['units'],
            $bestTeacher['id'],
        ]);

        // 6. Audit log
        $insertAudit->execute([
            'Schedule Generation',
            "Auto-assigned \"{$subject['course_code']} - {$subject['name']}\" to \"{$bestTeacher['name']}\" (score: {$bestScore}/100).",
            'System',
        ]);

        $assignedCount++;
    }

    $pdo->commit();

    echo json_encode([
        'status'           => 'success',
        'assigned_count'   => $assignedCount,
        'unassigned_count' => $unassignedCount,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Schedule generation failed: ' . $e->getMessage()]);
}
