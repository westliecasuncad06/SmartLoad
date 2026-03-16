<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

ob_start();
$__jsonSent = false;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e) use (&$__jsonSent): void {
    if ($__jsonSent) {
        return;
    }
    $__jsonSent = true;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'line'    => $e->getLine(),
    ]);
});

register_shutdown_function(static function () use (&$__jsonSent): void {
    if ($__jsonSent) {
        return;
    }

    $lastError = error_get_last();
    $bufferedOutput = '';
    if (ob_get_level() > 0) {
        $bufferedOutput = (string) ob_get_contents();
    }

    $isFatal = false;
    if ($lastError !== null) {
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        $isFatal = in_array($lastError['type'], $fatalTypes, true);
    }

    // If we reach shutdown without having sent JSON, force a JSON error.
    // This catches fatals and also premature termination via die()/exit() in included files.
    $__jsonSent = true;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $message = $isFatal && $lastError !== null
        ? (string) $lastError['message']
        : trim($bufferedOutput);
    if ($message === '') {
        $message = 'Unknown server error.';
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => $message,
        'line'    => ($isFatal && $lastError !== null) ? (int) $lastError['line'] : 0,
    ]);
});

try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/GeminiAPI.php';

    $ai = new GeminiEvaluator(GEMINI_API_KEY);

    $aiEnabled = $ai->isEnabled();
    $aiCalls = 0;
    $aiMaxCalls = 20; // hard cap to keep the request responsive

    $requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? '');
    if ($requestMethod !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        $__jsonSent = true;
        ob_end_flush();
        exit;
    }

    $tokenize = static function (string $value): array {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $parts = preg_split('/\s+/', trim((string) $value));
        $tokens = [];
        foreach ($parts as $p) {
            if ($p === '' || strlen($p) < 3) {
                continue;
            }
            $tokens[$p] = true;
        }
        return array_keys($tokens);
    };

    $heuristicExpertiseScore = static function (string $teacherTags, string $subjectPrereq) use ($tokenize): array {
        $tags = $tokenize($teacherTags);
        $reqs = $tokenize($subjectPrereq);

        if (empty($reqs)) {
            return ['score' => 50, 'rationale' => 'No prerequisites listed; expertise assumed neutral.'];
        }
        if (empty($tags)) {
            return ['score' => 0, 'rationale' => 'No expertise tags provided for teacher.'];
        }

        $tagSet = array_fill_keys($tags, true);
        $intersection = 0;
        foreach ($reqs as $r) {
            if (isset($tagSet[$r])) {
                $intersection++;
            }
        }
        $union = count(array_unique(array_merge($tags, $reqs)));
        $jaccard = $union > 0 ? ($intersection / $union) : 0.0;
        $score = (int) round(min(1.0, $jaccard) * 100);

        return [
            'score' => $score,
            'rationale' => $intersection > 0
                ? 'Matched ' . $intersection . ' prerequisite keyword(s) to expertise tags.'
                : 'No prerequisite keywords matched expertise tags.',
        ];
    };

    // 1) Fetch all unassigned subjects (no approved/pending assignment yet)
    $unassignedStmt = $pdo->query(
        'SELECT s.*
         FROM subjects s
         LEFT JOIN assignments a ON a.subject_id = s.id AND a.status IN ("Pending", "Approved")
         WHERE a.id IS NULL
         ORDER BY s.id ASC'
    );
    $unassignedSubjects = $unassignedStmt->fetchAll();

    // 2) Fetch all teachers once and simulate load in-memory while selecting assignments
    $teacherStmt = $pdo->query('SELECT * FROM teachers ORDER BY name ASC');
    $teachers = $teacherStmt->fetchAll();

    $teacherById = [];
    foreach ($teachers as $t) {
        $id = (int) ($t['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $t['current_units'] = (int) ($t['current_units'] ?? 0);
        $t['max_units'] = (int) ($t['max_units'] ?? 0);
        $teacherById[$id] = $t;
    }

    $plannedAssignments = [];
    $assignedCount = 0;
    $unassignedCount = 0;

    foreach ($unassignedSubjects as $subject) {
        $subjectUnits = (int) ($subject['units'] ?? 0);
        $subjectPrereq = (string) ($subject['prerequisites'] ?? '');

        // Eligible based on simulated load
        $eligible = [];
        foreach ($teacherById as $teacher) {
            $maxUnits = (int) ($teacher['max_units'] ?? 0);
            if ($maxUnits <= 0) {
                continue;
            }

            $currentUnits = (int) ($teacher['current_units'] ?? 0);
            if ($currentUnits + $subjectUnits <= $maxUnits) {
                $eligible[] = $teacher;
            }
        }

        if (empty($eligible)) {
            $unassignedCount++;
            continue;
        }

        // Score eligible teachers quickly (Expertise 70% + Availability 30%)
        $scored = [];
        foreach ($eligible as $teacher) {
            $expertise = $heuristicExpertiseScore(
                (string) ($teacher['expertise_tags'] ?? ''),
                $subjectPrereq
            );

            $maxUnits = max(1, (int) ($teacher['max_units'] ?? 1));
            $currentUnits = (int) ($teacher['current_units'] ?? 0);
            $availabilityScore = (int) round(max(0.0, 1.0 - ($currentUnits / $maxUnits)) * 100);
            $combined = (int) round(($expertise['score'] * 0.7) + ($availabilityScore * 0.3));

            $scored[] = [
                'teacher' => $teacher,
                'expertise_score' => (int) $expertise['score'],
                'availability_score' => $availabilityScore,
                'combined_score' => $combined,
                'rationale' => (string) $expertise['rationale'],
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            // Higher combined score first; tie-breaker: lower current_units
            if ($a['combined_score'] !== $b['combined_score']) {
                return $b['combined_score'] <=> $a['combined_score'];
            }
            $aUnits = (int) ($a['teacher']['current_units'] ?? 0);
            $bUnits = (int) ($b['teacher']['current_units'] ?? 0);
            return $aUnits <=> $bUnits;
        });

        $best = $scored[0];
        $bestTeacher = $best['teacher'];
        $bestScore = (int) $best['combined_score'];
        $bestRationale = (string) $best['rationale'];

        // Optional AI refinement: ask Gemini to pick from the top shortlist
        if ($aiEnabled && $aiCalls < $aiMaxCalls) {
            $shortlist = array_slice(array_map(static function (array $row): array {
                return [
                    'id' => (int) ($row['teacher']['id'] ?? 0),
                    'name' => (string) ($row['teacher']['name'] ?? ''),
                    'expertise_tags' => (string) ($row['teacher']['expertise_tags'] ?? ''),
                ];
            }, $scored), 0, 5);

            $aiPick = $ai->pickBestTeacher($shortlist, $subjectPrereq);
            $aiCalls++;

            $pickedId = (int) ($aiPick['teacher_id'] ?? 0);
            if ($pickedId > 0 && isset($teacherById[$pickedId])) {
                $pickedTeacher = $teacherById[$pickedId];
                // Ensure still eligible under simulated load
                if (((int) $pickedTeacher['current_units'] + $subjectUnits) <= (int) $pickedTeacher['max_units']) {
                    $bestTeacher = $pickedTeacher;

                    $aiExpertiseScore = (int) ($aiPick['score'] ?? 0);
                    $maxUnits = max(1, (int) ($pickedTeacher['max_units'] ?? 1));
                    $currentUnits = (int) ($pickedTeacher['current_units'] ?? 0);
                    $availabilityScore = (int) round(max(0.0, 1.0 - ($currentUnits / $maxUnits)) * 100);
                    $bestScore = (int) round(($aiExpertiseScore * 0.7) + ($availabilityScore * 0.3));
                    $bestRationale = (string) ($aiPick['rationale'] ?? $bestRationale);
                }
            }
        }

        $bestTeacherId = (int) ($bestTeacher['id'] ?? 0);
        if ($bestTeacherId <= 0) {
            $unassignedCount++;
            continue;
        }

        $plannedAssignments[] = [
            'subject_id' => (int) ($subject['id'] ?? 0),
            'teacher_id' => $bestTeacherId,
            'subject_units' => $subjectUnits,
            'rationale' => $bestRationale,
            'course_code' => (string) ($subject['course_code'] ?? ''),
            'subject_name' => (string) ($subject['name'] ?? ''),
            'teacher_name' => (string) ($bestTeacher['name'] ?? ''),
            'score' => $bestScore,
        ];

        // Update simulated load
        $teacherById[$bestTeacherId]['current_units'] = (int) $teacherById[$bestTeacherId]['current_units'] + $subjectUnits;
        $assignedCount++;
    }

    // 3) Apply DB writes in a short transaction (no network calls inside)
    $insertAssignment = $pdo->prepare(
        'INSERT INTO assignments (subject_id, teacher_id, status, rationale) VALUES (?, ?, "Pending", ?)'
    );
    $updateUnits = $pdo->prepare(
        'UPDATE teachers SET current_units = current_units + ? WHERE id = ?'
    );
    $insertAudit = $pdo->prepare(
        'INSERT INTO audit_logs (action_type, description, user) VALUES (?, ?, ?)'
    );

    $pdo->beginTransaction();
    foreach ($plannedAssignments as $plan) {
        $insertAssignment->execute([
            $plan['subject_id'],
            $plan['teacher_id'],
            $plan['rationale'],
        ]);

        $updateUnits->execute([
            (int) $plan['subject_units'],
            (int) $plan['teacher_id'],
        ]);

        $insertAudit->execute([
            'Schedule Generation',
            "Auto-assigned \"{$plan['course_code']} - {$plan['subject_name']}\" to \"{$plan['teacher_name']}\" (score: {$plan['score']}/100).",
            'System',
        ]);
    }
    $pdo->commit();

    echo json_encode([
        'status'           => 'success',
        'assigned_count'   => $assignedCount,
        'unassigned_count' => $unassignedCount,
    ]);

    $__jsonSent = true;
    ob_end_flush();

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    if (ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'line'    => $e->getLine(),
    ]);

    $__jsonSent = true;
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
