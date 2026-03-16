<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * @return array<int, array<int, string|null>>
 */
function read_csv_rows($handle): array {
    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (!is_array($row)) {
            continue;
        }
        // Skip completely empty lines
        $nonEmpty = false;
        foreach ($row as $cell) {
            if (trim((string)$cell) !== '') {
                $nonEmpty = true;
                break;
            }
        }
        if (!$nonEmpty) {
            continue;
        }
        $rows[] = $row;
    }
    return $rows;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    if (empty($_POST['type']) || !in_array($_POST['type'], ['teacher', 'subject', 'schedule'], true)) {
        json_response(400, ['status' => 'error', 'message' => 'Invalid or missing type parameter. Expected teacher, subject, or schedule.']);
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        json_response(400, ['status' => 'error', 'message' => 'No file uploaded or upload error.']);
    }

    $type = $_POST['type'];
    // conflict_action:
    // - detect (default): insert non-duplicates and return conflicts (existing vs incoming)
    // - update: upsert (insert new + update duplicates)
    $conflictAction = isset($_POST['conflict_action']) ? strtolower(trim((string)$_POST['conflict_action'])) : 'detect';
    if (!in_array($conflictAction, ['detect', 'update'], true)) {
        $conflictAction = 'detect';
    }
    $tmpPath = $_FILES['file']['tmp_name'];

    $handle = fopen($tmpPath, 'r');
    if ($handle === false) {
        json_response(500, ['status' => 'error', 'message' => 'Failed to open uploaded file.']);
    }

    // Skip header row
    fgetcsv($handle);

    $csvRows = read_csv_rows($handle);
    fclose($handle);

    $duplicates = [];
    $insertedCount = 0;
    $rowsUpdated  = 0;
    $conflicts    = [];

    $pdo->beginTransaction();

    switch ($type) {
        case 'teacher': {
            // Expected CSV columns: name, email, type, max_units, expertise_tags
            $incoming = [];
            $emails   = [];
            foreach ($csvRows as $row) {
                if (count($row) < 4) {
                    continue;
                }
                $record = [
                    'name'           => trim((string)$row[0]),
                    'email'          => trim((string)$row[1]),
                    'type'           => trim((string)$row[2]),
                    'max_units'      => (int)$row[3],
                    'expertise_tags' => isset($row[4]) ? trim((string)$row[4]) : null,
                ];
                if ($record['email'] === '') {
                    continue;
                }
                $incoming[] = $record;
                $emails[]   = $record['email'];
            }

            $existingByEmail = [];
            if (!empty($emails)) {
                $emails = array_values(array_unique($emails));
                $ph = implode(',', array_fill(0, count($emails), '?'));
                $stmtExisting = $pdo->prepare("SELECT id, name, email, type, max_units, current_units, expertise_tags FROM teachers WHERE email IN ($ph)");
                $stmtExisting->execute($emails);
                foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $existingByEmail[$r['email']] = $r;
                }
            }

            if ($conflictAction === 'update') {
                $stmtUpsert = $pdo->prepare(
                    'INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE name=VALUES(name), type=VALUES(type), max_units=VALUES(max_units), expertise_tags=VALUES(expertise_tags)'
                );
                foreach ($incoming as $rec) {
                    $isDuplicate = isset($existingByEmail[$rec['email']]);
                    $stmtUpsert->execute([
                        $rec['name'],
                        $rec['email'],
                        $rec['type'],
                        (int)$rec['max_units'],
                        $rec['expertise_tags'],
                    ]);
                    if ($isDuplicate) {
                        $rowsUpdated++;
                    } else {
                        $insertedCount++;
                    }
                }
                break;
            }

            $stmtInsert = $pdo->prepare('INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?)');
            foreach ($incoming as $rec) {
                if (isset($existingByEmail[$rec['email']])) {
                    $conflicts[] = [
                        'key'      => 'email',
                        'value'    => $rec['email'],
                        'existing' => $existingByEmail[$rec['email']],
                        'incoming' => $rec,
                    ];
                    continue;
                }
                try {
                    $stmtInsert->execute([
                        $rec['name'],
                        $rec['email'],
                        $rec['type'],
                        (int)$rec['max_units'],
                        $rec['expertise_tags'],
                    ]);
                    $insertedCount++;
                } catch (PDOException $e) {
                    $sqlState = (string)$e->getCode();
                    $mysqlErr = (isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0);
                    if ($sqlState === '23000' || $sqlState === '1062' || $mysqlErr === 1062) {
                        $identifier = $rec['name'] !== '' ? $rec['name'] : $rec['email'];
                        $duplicates[] = $identifier;
                        continue;
                    }
                    throw $e;
                }
            }
            break;
        }

        case 'subject': {
            // Expected CSV columns: course_code, name, program, units, prerequisites
            $incoming = [];
            $codes    = [];
            foreach ($csvRows as $row) {
                if (count($row) < 4) {
                    continue;
                }
                $record = [
                    'course_code'    => trim((string)$row[0]),
                    'name'           => trim((string)$row[1]),
                    'program'        => trim((string)$row[2]),
                    'units'          => (int)$row[3],
                    'prerequisites'  => isset($row[4]) ? trim((string)$row[4]) : null,
                ];
                if ($record['course_code'] === '') {
                    continue;
                }
                $incoming[] = $record;
                $codes[]    = $record['course_code'];
            }

            $existingByCode = [];
            if (!empty($codes)) {
                $codes = array_values(array_unique($codes));
                $ph = implode(',', array_fill(0, count($codes), '?'));
                $stmtExisting = $pdo->prepare("SELECT id, course_code, name, program, units, prerequisites FROM subjects WHERE course_code IN ($ph)");
                $stmtExisting->execute($codes);
                foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $existingByCode[$r['course_code']] = $r;
                }
            }

            if ($conflictAction === 'update') {
                $stmtUpsert = $pdo->prepare(
                    'INSERT INTO subjects (course_code, name, program, units, prerequisites) VALUES (?, ?, ?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE name=VALUES(name), program=VALUES(program), units=VALUES(units), prerequisites=VALUES(prerequisites)'
                );
                foreach ($incoming as $rec) {
                    $isDuplicate = isset($existingByCode[$rec['course_code']]);
                    $stmtUpsert->execute([
                        $rec['course_code'],
                        $rec['name'],
                        $rec['program'],
                        (int)$rec['units'],
                        $rec['prerequisites'],
                    ]);
                    if ($isDuplicate) {
                        $rowsUpdated++;
                    } else {
                        $insertedCount++;
                    }
                }
                break;
            }

            $stmtInsert = $pdo->prepare('INSERT INTO subjects (course_code, name, program, units, prerequisites) VALUES (?, ?, ?, ?, ?)');
            foreach ($incoming as $rec) {
                if (isset($existingByCode[$rec['course_code']])) {
                    $conflicts[] = [
                        'key'      => 'course_code',
                        'value'    => $rec['course_code'],
                        'existing' => $existingByCode[$rec['course_code']],
                        'incoming' => $rec,
                    ];
                    continue;
                }
                try {
                    $stmtInsert->execute([
                        $rec['course_code'],
                        $rec['name'],
                        $rec['program'],
                        (int)$rec['units'],
                        $rec['prerequisites'],
                    ]);
                    $insertedCount++;
                } catch (PDOException $e) {
                    $sqlState = (string)$e->getCode();
                    $mysqlErr = (isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0);
                    if ($sqlState === '23000' || $sqlState === '1062' || $mysqlErr === 1062) {
                        $identifier = $rec['course_code'];
                        $duplicates[] = $identifier;
                        continue;
                    }
                    throw $e;
                }
            }
            break;
        }

        case 'schedule': {
            // Expected CSV columns: subject_id, day_of_week, start_time, end_time, room
            $stmt = $pdo->prepare(
                'INSERT INTO schedules (subject_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($csvRows as $row) {
                if (count($row) < 5) {
                    continue;
                }
                try {
                    $stmt->execute([
                        (int)$row[0],
                        trim((string)$row[1]),
                        trim((string)$row[2]),
                        trim((string)$row[3]),
                        trim((string)$row[4]),
                    ]);
                    $insertedCount++;
                } catch (PDOException $e) {
                    $sqlState = (string)$e->getCode();
                    $mysqlErr = (isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0);
                    if ($sqlState === '23000' || $sqlState === '1062' || $mysqlErr === 1062) {
                        $identifier = isset($row[0]) ? trim((string)$row[0]) : '';
                        $duplicates[] = $identifier !== '' ? $identifier : 'schedule_row';
                        continue;
                    }
                    throw $e;
                }
            }
            break;
        }
    }

    $pdo->commit();

    if ($conflictAction === 'detect' && !empty($conflicts)) {
        json_response(200, [
            'status'        => 'conflict',
            'type'          => $type,
            'rows_inserted' => $insertedCount,
            'conflict_count'=> count($conflicts),
            'conflicts'     => $conflicts,
            'duplicates'    => $duplicates,
            'message'       => 'Upload completed with duplicates found. Review conflicts to decide whether to update existing records.',
        ]);
    }

    json_response(200, [
        'status'        => 'success',
        'type'          => $type,
        'rows_inserted' => $insertedCount,
        'rows_updated'  => $rowsUpdated,
        'duplicates'    => $duplicates,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
    json_response(500, ['status' => 'error', 'message' => 'Upload failed: ' . $e->getMessage()]);
}
