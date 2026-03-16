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
    if (stripos($v, 'all ') === 0) return true; // e.g. "All Departments"
    return false;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $type = isset($_GET['type']) ? trim((string)$_GET['type']) : 'All';
    $department = isset($_GET['department']) ? trim((string)$_GET['department']) : 'All';

    $sql = 'SELECT id, name, email, type, current_units, max_units, expertise_tags FROM teachers WHERE is_archived = 0';
    $params = [];

    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= ' AND (name LIKE ? OR email LIKE ? OR expertise_tags LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (!is_all_filter($type)) {
        // Be tolerant to stored variants like "Full Time", "Full-time", "FULLTIME", etc.
        $norm = strtolower(preg_replace('/[^a-z]/i', '', $type)); // keep letters only
        if ($norm === 'fulltime' || $norm === 'parttime') {
            $sql .= " AND LOWER(REPLACE(REPLACE(type, '-', ''), ' ', '')) = ?";
            $params[] = $norm;
        } else {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }
    }

    // SmartLoad currently has no explicit department column on teachers.
    // Use an expertise_tags LIKE filter to make the UI's "Department" dropdown functional.
    if (!is_all_filter($department)) {
        $sql .= ' AND expertise_tags LIKE ?';
        $params[] = '%' . $department . '%';
    }

    $sql .= ' ORDER BY name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(200, ['status' => 'success', 'teachers' => $teachers]);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to filter teachers: ' . $e->getMessage()]);
}
