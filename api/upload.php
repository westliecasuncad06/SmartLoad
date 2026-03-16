<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

/**
 * Parse a teacher availability string.
 *
 * Format example: "Mon 08:00-17:00;Tue 09:00-15:00"
 *
 * @return array<int, array{day_of_week:string,start_time:string,end_time:string}>
 */
function parse_teacher_availability(?string $availability): array {
    $availability = trim((string)$availability);
    if ($availability === '') {
        return [];
    }

    // Match DB enum values (Monday..Sunday) but accept Mon/Tue/etc in CSV.
    $dayMap = [
        'mon' => 'Monday',
        'tue' => 'Tuesday',
        'wed' => 'Wednesday',
        'thu' => 'Thursday',
        'fri' => 'Friday',
        'sat' => 'Saturday',
        'sun' => 'Sunday',
    ];

    $entries = [];
    $parts = preg_split('/\s*;\s*/', $availability) ?: [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part === '') {
            continue;
        }

        if (!preg_match('/^([A-Za-z]{3,9})\s+(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $part, $m)) {
            continue;
        }

        $dayRaw = strtolower($m[1]);
        $dayKey = substr($dayRaw, 0, 3);
        if (!isset($dayMap[$dayKey])) {
            continue;
        }
        $day = $dayMap[$dayKey];

        $start = $m[2];
        $end   = $m[3];

        // Normalize to HH:MM
        if (preg_match('/^(\d):(\d{2})$/', $start, $hm)) {
            $start = '0' . $hm[1] . ':' . $hm[2];
        }
        if (preg_match('/^(\d):(\d{2})$/', $end, $hm2)) {
            $end = '0' . $hm2[1] . ':' . $hm2[2];
        }

        $startDt = DateTime::createFromFormat('H:i', $start);
        $endDt   = DateTime::createFromFormat('H:i', $end);
        if (!$startDt || !$endDt) {
            continue;
        }
        if ($startDt->format('H:i') !== $start || $endDt->format('H:i') !== $end) {
            continue;
        }

        $startMinutes = ((int)$startDt->format('H')) * 60 + (int)$startDt->format('i');
        $endMinutes   = ((int)$endDt->format('H')) * 60 + (int)$endDt->format('i');
        if ($endMinutes <= $startMinutes) {
            continue;
        }

        $entries[] = [
            'day_of_week' => $day,
            'start_time'  => $start,
            'end_time'    => $end,
        ];
    }

    return $entries;
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

    // dataset_scope:
    // - current (default): import into live scheduling tables
    // - previous: store raw file for forecasting/historical analysis (do not modify live tables)
    $datasetScope = isset($_POST['dataset_scope']) ? strtolower(trim((string)$_POST['dataset_scope'])) : 'current';
    if (!in_array($datasetScope, ['current', 'previous'], true)) {
        $datasetScope = 'current';
    }
    // conflict_action:
    // - detect (default): insert non-duplicates and return conflicts (existing vs incoming)
    // - update: upsert (insert new + update duplicates)
    $conflictAction = isset($_POST['conflict_action']) ? strtolower(trim((string)$_POST['conflict_action'])) : 'detect';
    if (!in_array($conflictAction, ['detect', 'update'], true)) {
        $conflictAction = 'detect';
    }
    $tmpPath = $_FILES['file']['tmp_name'];

    if ($datasetScope === 'previous') {
        $academicYearRaw = isset($_POST['academic_year']) ? trim((string)$_POST['academic_year']) : '';
        $semesterRaw     = isset($_POST['semester']) ? trim((string)$_POST['semester']) : '';

        if ($academicYearRaw === '' || $semesterRaw === '') {
            json_response(400, ['status' => 'error', 'message' => 'Missing academic_year or semester for historical upload.']);
        }

        // Sanitize for filename and storage.
        $academicYear = preg_replace('/[^0-9\-]/', '', $academicYearRaw);
        // Normalize semester to a known token.
        $semesterNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', $semesterRaw));
        $semesterToken = match ($semesterNorm) {
            '1st', 'first', '1stsemester', 'firstsemester', 'sem1', 'semester1' => '1st',
            '2nd', 'second', '2ndsemester', 'secondsemester', 'sem2', 'semester2' => '2nd',
            'summer' => 'summer',
            default => $semesterNorm,
        };

        if ($academicYear === '') {
            json_response(400, ['status' => 'error', 'message' => 'Invalid academic_year format.']);
        }
        if ($semesterToken === '') {
            json_response(400, ['status' => 'error', 'message' => 'Invalid semester value.']);
        }

        $historicalDir = __DIR__ . '/../files/historical';
        if (!is_dir($historicalDir)) {
            if (!mkdir($historicalDir, 0775, true) && !is_dir($historicalDir)) {
                json_response(500, ['status' => 'error', 'message' => 'Failed to create historical upload directory.']);
            }
        }

        $originalName = isset($_FILES['file']['name']) ? (string)$_FILES['file']['name'] : '';
        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'csv';
        }

        $stamp = date('Ymd_His');
        try {
            $rand = bin2hex(random_bytes(4));
        } catch (Exception $ignore) {
            $rand = (string)mt_rand(10000000, 99999999);
        }

        $ayToken = preg_replace('/\-+/', '-', $academicYear);
        $semToken = preg_replace('/[^a-z0-9]+/i', '', $semesterToken);

        $fileName = 'previous_' . $type . '_AY' . $ayToken . '_' . $semToken . '_' . $stamp . '_' . $rand . '.' . preg_replace('/[^a-z0-9]+/i', '', $ext);
        if (substr($fileName, -1) === '.') {
            $fileName .= 'csv';
        }
        $targetPath = $historicalDir . '/' . $fileName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            json_response(500, ['status' => 'error', 'message' => 'Failed to store uploaded file as historical dataset.']);
        }

        // Sidecar metadata (best-effort)
        try {
            $meta = [
                'dataset_scope' => 'previous',
                'type' => $type,
                'academic_year' => $academicYearRaw,
                'semester' => $semesterRaw,
                'stored_as' => 'files/historical/' . $fileName,
                'original_name' => $originalName,
                'uploaded_at' => date('c'),
            ];
            @file_put_contents($targetPath . '.meta.json', json_encode($meta, JSON_PRETTY_PRINT));
        } catch (Exception $ignore) {
        }

        // Best-effort audit log (do not fail the upload if auditing fails)
        try {
            $label = ucfirst($type);
            $auditDesc = $label . ' CSV uploaded (historical AY ' . $academicYearRaw . ', ' . $semesterRaw . ')';
            $insertAudit = $pdo->prepare('INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)');
            $insertAudit->execute([
                'File Upload',
                $auditDesc,
                'Program Chair',
            ]);
        } catch (Exception $ignore) {
        }

        json_response(200, [
            'status' => 'success',
            'type' => $type,
            'dataset_scope' => 'previous',
            'academic_year' => $academicYearRaw,
            'semester' => $semesterRaw,
            'saved_only' => true,
            'stored_as' => 'files/historical/' . $fileName,
            'message' => 'Saved as historical dataset for forecasting (not imported into current scheduling data).',
        ]);
    }

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
            // Expected CSV columns: name, email, type, max_units, expertise_tags, availability (optional)
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
                    // Only treat availability as present if the column exists in the CSV row.
                    'availability_present' => array_key_exists(5, $row),
                    'availability'   => isset($row[5]) ? trim((string)$row[5]) : null,
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
                $stmtDeleteAvailability = $pdo->prepare('DELETE FROM teacher_availability WHERE teacher_id = ?');
                $stmtInsertAvailability = $pdo->prepare('INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
                $stmtUpsert = $pdo->prepare(
                    'INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE name=VALUES(name), type=VALUES(type), max_units=VALUES(max_units), expertise_tags=VALUES(expertise_tags), id=LAST_INSERT_ID(id)'
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

                    $teacherId = (int)$pdo->lastInsertId();

                    // If availability column is present in the CSV, replace availability rows.
                    if (!empty($teacherId) && !empty($rec['availability_present'])) {
                        $stmtDeleteAvailability->execute([$teacherId]);
                        $entries = parse_teacher_availability($rec['availability']);
                        foreach ($entries as $entry) {
                            $stmtInsertAvailability->execute([
                                $teacherId,
                                $entry['day_of_week'],
                                $entry['start_time'],
                                $entry['end_time'],
                            ]);
                        }
                    }

                    if ($isDuplicate) {
                        $rowsUpdated++;
                    } else {
                        $insertedCount++;
                    }
                }
                break;
            }

            $stmtInsertAvailability = $pdo->prepare('INSERT INTO teacher_availability (teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)');
            $stmtInsert = $pdo->prepare('INSERT INTO teachers (name, email, type, max_units, expertise_tags) VALUES (?, ?, ?, ?, ?)');
            foreach ($incoming as $rec) {
                if (isset($existingByEmail[$rec['email']])) {
                    $conflicts[] = [
                        'key'      => 'email',
                        'value'    => $rec['email'],
                        'existing' => $existingByEmail[$rec['email']],
                        // Keep conflict payload backward-compatible (exclude availability fields).
                        'incoming' => [
                            'name'           => $rec['name'],
                            'email'          => $rec['email'],
                            'type'           => $rec['type'],
                            'max_units'      => (int)$rec['max_units'],
                            'expertise_tags' => $rec['expertise_tags'],
                        ],
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

                    $teacherId = (int)$pdo->lastInsertId();
                    if (!empty($teacherId) && !empty($rec['availability_present'])) {
                        $entries = parse_teacher_availability($rec['availability']);
                        foreach ($entries as $entry) {
                            $stmtInsertAvailability->execute([
                                $teacherId,
                                $entry['day_of_week'],
                                $entry['start_time'],
                                $entry['end_time'],
                            ]);
                        }
                    }

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
            // Optional (if DB column exists): section

            $scheduleHasSection = false;
            try {
                $col = $pdo->query("SHOW COLUMNS FROM schedules LIKE 'section'")->fetch(PDO::FETCH_ASSOC);
                $scheduleHasSection = is_array($col) && !empty($col);
            } catch (Exception $ignore) {
                $scheduleHasSection = false;
            }

            $stmt = $scheduleHasSection
                ? $pdo->prepare('INSERT INTO schedules (subject_id, day_of_week, start_time, end_time, room, section) VALUES (?, ?, ?, ?, ?, ?)')
                : $pdo->prepare('INSERT INTO schedules (subject_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)');

            foreach ($csvRows as $row) {
                if (count($row) < 5) {
                    continue;
                }
                try {
                    $params = [
                        (int)$row[0],
                        trim((string)$row[1]),
                        trim((string)$row[2]),
                        trim((string)$row[3]),
                        trim((string)$row[4]),
                    ];
                    if ($scheduleHasSection) {
                        $params[] = isset($row[5]) ? trim((string)$row[5]) : null;
                    }
                    $stmt->execute($params);
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

    // Audit log (include both successful uploads and uploads with detected conflicts)
    try {
        $label = ucfirst($type);
        $descParts = [];
        $descParts[] = $label . ' CSV uploaded';
        $descParts[] = $insertedCount . ' inserted';
        if ($rowsUpdated > 0) {
            $descParts[] = $rowsUpdated . ' updated';
        }
        if (!empty($conflicts)) {
            $descParts[] = count($conflicts) . ' duplicates detected';
        }
        $auditDesc = implode(', ', $descParts);

        $insertAudit = $pdo->prepare('INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)');
        $insertAudit->execute([
            'File Upload',
            $auditDesc,
            'Program Chair',
        ]);
    } catch (Exception $ignore) {
        // Do not block upload on audit logging issues
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
