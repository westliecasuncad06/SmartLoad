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

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        json_response(400, ['status' => 'error', 'message' => 'id is required.']);
    }

    // Ensure column exists (better error message if migration not applied)
    $hasArchive = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'is_archived'");
        $hasArchive = (bool)$colStmt->fetch();
    } catch (Exception $ignore) {
        $hasArchive = false;
    }

    if (!$hasArchive) {
        json_response(500, ['status' => 'error', 'message' => 'Archiving is not enabled. Please run: ALTER TABLE subjects ADD COLUMN is_archived TINYINT DEFAULT 0;']);
    }

    $stmtCode = $pdo->prepare('SELECT course_code FROM subjects WHERE id = ?');
    $stmtCode->execute([$id]);
    $row = $stmtCode->fetch();
    if (!$row) {
        json_response(404, ['status' => 'error', 'message' => 'Subject not found.']);
    }
    $courseCode = (string)$row['course_code'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE subjects SET is_archived = 1 WHERE id = ? AND is_archived = 0');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        json_response(404, ['status' => 'error', 'message' => 'Subject already archived or not found.']);
    }

    $audit = $pdo->prepare('INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)');
    $audit->execute([
        'Subject Archived',
        'Subject archived',
        'Program Chair',
    ]);

    $pdo->commit();

    json_response(200, ['status' => 'success']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(500, ['status' => 'error', 'message' => 'Failed to archive subject: ' . $e->getMessage()]);
}
