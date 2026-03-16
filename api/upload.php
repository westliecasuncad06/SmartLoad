<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        exit;
    }

    if (empty($_POST['type']) || !in_array($_POST['type'], ['teacher', 'subject', 'schedule'], true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing type parameter. Expected teacher, subject, or schedule.']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error.']);
        exit;
    }

    $type = $_POST['type'];
    $tmpPath = $_FILES['file']['tmp_name'];

    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to open uploaded file.']);
        exit;
    }

    // Skip header row
    fgetcsv($handle);

    $rowsInserted = 0;

    $pdo->beginTransaction();

    switch ($type) {
        case 'teacher':
            // Expected CSV columns: name, email, type, max_units, expertise_tags
            $stmt = $pdo->prepare(
                'INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?)'
            );
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    continue;
                }
                $stmt->execute([
                    trim($row[0]),
                    trim($row[1]),
                    trim($row[2]),
                    (int) $row[3],
                    isset($row[4]) ? trim($row[4]) : null,
                ]);
                $rowsInserted++;
            }
            break;

        case 'subject':
            // Expected CSV columns: course_code, name, program, units, prerequisites
            $stmt = $pdo->prepare(
                'INSERT INTO subjects (course_code, name, program, units, prerequisites) VALUES (?, ?, ?, ?, ?)'
            );
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    continue;
                }
                $stmt->execute([
                    trim($row[0]),
                    trim($row[1]),
                    trim($row[2]),
                    (int) $row[3],
                    isset($row[4]) ? trim($row[4]) : null,
                ]);
                $rowsInserted++;
            }
            break;

        case 'schedule':
            // Expected CSV columns: subject_id, day_of_week, start_time, end_time, room
            $stmt = $pdo->prepare(
                'INSERT INTO schedules (subject_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)'
            );
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 5) {
                    continue;
                }
                $stmt->execute([
                    (int) $row[0],
                    trim($row[1]),
                    trim($row[2]),
                    trim($row[3]),
                    trim($row[4]),
                ]);
                $rowsInserted++;
            }
            break;
    }

    $pdo->commit();
    fclose($handle);

    echo json_encode(['status' => 'success', 'rows_inserted' => $rowsInserted]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Upload failed: ' . $e->getMessage()]);
}
