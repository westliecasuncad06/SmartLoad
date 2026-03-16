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

    $name         = trim($input['name'] ?? '');
    $email        = trim($input['email'] ?? '');
    $type         = trim($input['type'] ?? '');
    $maxUnits     = isset($input['max_units']) ? (int) $input['max_units'] : 0;
    $expertiseTags = trim($input['expertise_tags'] ?? '');

    if ($name === '' || $email === '' || !in_array($type, ['Full-time', 'Part-time'], true) || $maxUnits <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing or invalid required fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $name,
        $email,
        $type,
        $maxUnits,
        $expertiseTags !== '' ? $expertiseTags : null,
    ]);

    $auditStmt = $pdo->prepare(
        'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
    );
    $auditStmt->execute([
        'Teacher Created',
        'Teacher ' . $name . ' added',
        'Program Chair',
    ]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Teacher created successfully.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'A teacher with that email already exists.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unexpected error.']);
}
