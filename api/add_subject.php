<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid JSON payload.']);
    }

    $courseCode = isset($input['course_code']) ? trim((string)$input['course_code']) : '';
    $name = isset($input['name']) ? trim((string)$input['name']) : '';
    $program = isset($input['program']) ? trim((string)$input['program']) : '';
    $units = isset($input['units']) ? (int)$input['units'] : 0;
    $prereq = isset($input['prerequisites']) ? trim((string)$input['prerequisites']) : '';

    if ($courseCode === '' || $name === '' || $program === '') {
        json_response(400, ['status' => 'error', 'message' => 'course_code, name, and program are required.']);
    }

    if ($units < 0 || $units > 255) {
        json_response(400, ['status' => 'error', 'message' => 'units must be between 0 and 255.']);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO subjects (course_code, name, program, units, prerequisites) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $courseCode,
        $name,
        $program,
        $units,
        ($prereq !== '' ? $prereq : null),
    ]);

    $audit = $pdo->prepare('INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)');
    $audit->execute([
        'Subject Added',
        $courseCode . ' added to subject catalog',
        'Program Chair',
    ]);

    $pdo->commit();

    json_response(200, ['status' => 'success']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $sqlState = (string)$e->getCode();
    $mysqlErr = (isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0);
    if ($sqlState === '23000' || $mysqlErr === 1062) {
        json_response(409, ['status' => 'error', 'message' => 'A subject with this course code already exists.']);
    }

    json_response(500, ['status' => 'error', 'message' => 'Failed to add subject: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(500, ['status' => 'error', 'message' => 'Failed to add subject: ' . $e->getMessage()]);
}
