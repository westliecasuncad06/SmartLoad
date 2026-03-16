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

function normalize_status_filter(string $value): string
{
    $v = strtolower(trim($value));
    if ($v === '' || $v === 'all' || $v === 'all status') return 'all';
    if ($v === 'assigned') return 'assigned';
    if ($v === 'unassigned') return 'unassigned';
    return 'all';
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $program = isset($_GET['program']) ? trim((string)$_GET['program']) : 'All';
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : 'all';
    $status = normalize_status_filter($status);

    // Be defensive: some installations may not have the is_archived column yet.
    $hasArchive = false;
    try {
        $colStmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'is_archived'");
        $hasArchive = (bool)$colStmt->fetch();
    } catch (Exception $ignore) {
        $hasArchive = false;
    }

    $sql = 'SELECT sub.id, sub.course_code, sub.name, sub.program, sub.units, sub.prerequisites,
                   a.id AS assignment_id, t.name AS assigned_teacher_name
            FROM subjects sub
            LEFT JOIN (
                SELECT subject_id, MAX(id) AS latest_assignment_id
                FROM assignments
                GROUP BY subject_id
            ) la ON la.subject_id = sub.id
            LEFT JOIN assignments a ON a.id = la.latest_assignment_id
            LEFT JOIN teachers t ON t.id = a.teacher_id';
    $params = [];
    $where = [];

    if ($hasArchive) {
        $where[] = 'sub.is_archived = 0';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(sub.course_code LIKE ? OR sub.name LIKE ? OR sub.prerequisites LIKE ? OR sub.program LIKE ? OR t.name LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (!is_all_filter($program)) {
        $where[] = 'sub.program = ?';
        $params[] = $program;
    }

    if ($status === 'assigned') {
        $where[] = 'a.id IS NOT NULL';
    } elseif ($status === 'unassigned') {
        $where[] = 'a.id IS NULL';
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY sub.course_code ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize fields for JS rendering.
    foreach ($subjects as &$s) {
        $s['is_assigned'] = !empty($s['assignment_id']) ? 1 : 0;
        $s['assigned_teacher_name'] = $s['assigned_teacher_name'] ?? null;
        unset($s['assignment_id']);
    }
    unset($s);

    json_response(200, ['status' => 'success', 'subjects' => $subjects]);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to filter subjects: ' . $e->getMessage()]);
}
