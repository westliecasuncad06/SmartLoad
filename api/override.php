<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $assignmentId  = isset($input['assignment_id'])  ? (int) $input['assignment_id']  : 0;
    $newTeacherId  = isset($input['new_teacher_id']) ? (int) $input['new_teacher_id'] : 0;
    $reason        = isset($input['reason'])         ? trim($input['reason'])          : '';

    if ($assignmentId <= 0 || $newTeacherId <= 0 || $reason === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'assignment_id, new_teacher_id, and reason are required.']);
        exit;
    }

    // 1. Fetch the existing assignment with subject units
    $stmt = $pdo->prepare(
        'SELECT a.teacher_id AS old_teacher_id, a.subject_id, s.units, s.course_code, s.name AS subject_name
         FROM assignments a
         JOIN subjects s ON s.id = a.subject_id
         WHERE a.id = ?'
    );
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Assignment not found.']);
        exit;
    }

    $oldTeacherId = (int) $assignment['old_teacher_id'];
    $units        = (int) $assignment['units'];

    if ($newTeacherId === $oldTeacherId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'New teacher is the same as the current teacher.']);
        exit;
    }

    // Verify new teacher exists and has capacity
    $newTeacherStmt = $pdo->prepare('SELECT id, name, current_units, max_units FROM teachers WHERE id = ?');
    $newTeacherStmt->execute([$newTeacherId]);
    $newTeacher = $newTeacherStmt->fetch();

    if (!$newTeacher) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'New teacher not found.']);
        exit;
    }

    if (($newTeacher['current_units'] + $units) > $newTeacher['max_units']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'New teacher does not have enough unit capacity.']);
        exit;
    }

    // Fetch old teacher name for audit log
    $oldTeacherStmt = $pdo->prepare('SELECT name FROM teachers WHERE id = ?');
    $oldTeacherStmt->execute([$oldTeacherId]);
    $oldTeacher = $oldTeacherStmt->fetch();

    // 2. Begin transaction
    $pdo->beginTransaction();

    // 3. Update assignment
    $updateAssignment = $pdo->prepare(
        'UPDATE assignments SET teacher_id = ?, status = "Manual", rationale = ? WHERE id = ?'
    );
    $updateAssignment->execute([$newTeacherId, $reason, $assignmentId]);

    // 4. Subtract units from old teacher
    $subtractUnits = $pdo->prepare(
        'UPDATE teachers SET current_units = GREATEST(0, current_units - ?) WHERE id = ?'
    );
    $subtractUnits->execute([$units, $oldTeacherId]);

    // 5. Add units to new teacher
    $addUnits = $pdo->prepare(
        'UPDATE teachers SET current_units = current_units + ? WHERE id = ?'
    );
    $addUnits->execute([$units, $newTeacherId]);

    // 6. Audit log
    $insertAudit = $pdo->prepare(
        'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
    );
    $insertAudit->execute([
        'Manual Override',
        "Reassigned \"{$assignment['course_code']} - {$assignment['subject_name']}\" from \"{$oldTeacher['name']}\" to \"{$newTeacher['name']}\". Reason: {$reason}",
        'Program Chair',
    ]);

    // 7. Commit
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Assignment overridden successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Override failed: ' . $e->getMessage()]);
}
