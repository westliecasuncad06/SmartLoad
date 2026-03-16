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
    $name = isset($input['name']) ? trim((string)$input['name']) : '';
    $email = isset($input['email']) ? trim((string)$input['email']) : '';
    $type = isset($input['type']) ? trim((string)$input['type']) : '';
    $maxUnits = isset($input['max_units']) ? (int)$input['max_units'] : 0;
    $expertiseTags = isset($input['expertise_tags']) ? trim((string)$input['expertise_tags']) : '';

    if ($id <= 0) {
        json_response(400, ['status' => 'error', 'message' => 'Teacher id is required.']);
    }

    if ($name === '' || $email === '') {
        json_response(400, ['status' => 'error', 'message' => 'Name and email are required.']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid email address.']);
    }

    if (!in_array($type, ['Full-time', 'Part-time'], true)) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid employment type.']);
    }

    if ($maxUnits < 0 || $maxUnits > 255) {
        json_response(400, ['status' => 'error', 'message' => 'max_units must be between 0 and 255.']);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE teachers SET name = ?, email = ?, type = ?, max_units = ?, expertise_tags = ? WHERE id = ? AND is_archived = 0'
    );
    $stmt->execute([$name, $email, $type, $maxUnits, ($expertiseTags !== '' ? $expertiseTags : null), $id]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        json_response(404, ['status' => 'error', 'message' => 'Teacher not found (or already archived).']);
    }

    $audit = $pdo->prepare('INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)');
    $audit->execute([
        'Teacher Updated',
        $name . ' updated teacher details',
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
        json_response(409, ['status' => 'error', 'message' => 'A teacher with this email already exists.']);
    }

    json_response(500, ['status' => 'error', 'message' => 'Failed to update teacher: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(500, ['status' => 'error', 'message' => 'Failed to update teacher: ' . $e->getMessage()]);
}
