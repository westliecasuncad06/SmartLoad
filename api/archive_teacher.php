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
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON body.']);
        exit;
    }

    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing or invalid teacher ID.']);
        exit;
    }

    $pdo->beginTransaction();

    $nameStmt = $pdo->prepare('SELECT name FROM teachers WHERE id = ? AND archived = 0');
    $nameStmt->execute([$id]);
    $teacher = $nameStmt->fetch();

    if (!$teacher) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Teacher not found or already archived.']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE teachers SET archived = 1 WHERE id = ?');
    $stmt->execute([$id]);

    $auditStmt = $pdo->prepare(
        'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
    );
    $auditStmt->execute([
        'Teacher Archived',
        'Teacher ' . $teacher['name'] . ' archived',
        'Program Chair',
    ]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Teacher archived successfully.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unexpected error.']);
}
