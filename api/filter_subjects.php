<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function is_all_filter(string $value): bool
{
    $v = trim($value);
    if ($v === '') return true;
    if (strcasecmp($v, 'all') === 0) return true;
    if (stripos($v, 'all ') === 0) return true; // e.g. "All Programs"
    return false;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $program = isset($_GET['program']) ? trim((string)$_GET['program']) : 'All';

    // Be defensive: some installations may not have the is_archived column yet.
    $hasArchive = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'is_archived'");
        $hasArchive = (bool)$colStmt->fetch();
    } catch (Exception $ignore) {
        $hasArchive = false;
    }

    $sql = 'SELECT id, course_code, name, program, units, prerequisites FROM subjects';
    $params = [];
    $where = [];

    if ($hasArchive) {
        $where[] = 'is_archived = 0';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(course_code LIKE ? OR name LIKE ? OR prerequisites LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (!is_all_filter($program)) {
        $where[] = 'program = ?';
        $params[] = $program;
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY course_code ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(200, ['status' => 'success', 'subjects' => $subjects]);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to filter subjects: ' . $e->getMessage()]);
}
