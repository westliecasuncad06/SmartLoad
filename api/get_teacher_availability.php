<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function ensure_teacher_availability_table(PDO $pdo): void {
    $exists = false;
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_availability'"
        );
        $stmt->execute();
        $exists = (int) $stmt->fetchColumn() > 0;
    } catch (Exception $ignore) {
        $exists = false;
    }

    if ($exists) return;

    $pdo->exec(
        "CREATE TABLE teacher_availability (\n"
        . "    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n"
        . "    teacher_id INT UNSIGNED NOT NULL,\n"
        . "    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,\n"
        . "    start_time TIME NOT NULL,\n"
        . "    end_time TIME NOT NULL,\n"
        . "    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,\n"
        . "    INDEX idx_availability_teacher (teacher_id),\n"
        . "    INDEX idx_availability_day (day_of_week)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    if ($teacherId <= 0) {
        json_response(400, ['status' => 'error', 'message' => 'teacher_id is required.']);
    }

    ensure_teacher_availability_table($pdo);

    $stmt = $pdo->prepare(
        'SELECT day_of_week, start_time, end_time FROM teacher_availability WHERE teacher_id = ? ORDER BY FIELD(day_of_week, "Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"), start_time'
    );
    $stmt->execute([$teacherId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(200, ['status' => 'success', 'availability' => $rows]);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to fetch availability: ' . $e->getMessage()]);
}
