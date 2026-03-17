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

    // Load policy settings (or fallback defaults)
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS policy_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            max_teaching_load TINYINT UNSIGNED NOT NULL DEFAULT 18,
            expertise_weight TINYINT UNSIGNED NOT NULL DEFAULT 70,
            availability_weight TINYINT UNSIGNED NOT NULL DEFAULT 30,
            detect_schedule_overlaps TINYINT(1) NOT NULL DEFAULT 1,
            flag_overload_teachers TINYINT(1) NOT NULL DEFAULT 1,
            check_prerequisites TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $policy = [
        'max_teaching_load' => 18,
        'expertise_weight' => 70,
        'availability_weight' => 30,
        'detect_schedule_overlaps' => 1,
        'flag_overload_teachers' => 1,
        'check_prerequisites' => 1,
    ];

    $policyRow = $pdo->query('SELECT * FROM policy_settings WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($policyRow) {
        $policy['max_teaching_load'] = (int) ($policyRow['max_teaching_load'] ?? 18);
        $policy['expertise_weight'] = (int) ($policyRow['expertise_weight'] ?? 70);
        $policy['availability_weight'] = (int) ($policyRow['availability_weight'] ?? 30);
        $policy['detect_schedule_overlaps'] = (int) ($policyRow['detect_schedule_overlaps'] ?? 1);
        $policy['flag_overload_teachers'] = (int) ($policyRow['flag_overload_teachers'] ?? 1);
        $policy['check_prerequisites'] = (int) ($policyRow['check_prerequisites'] ?? 1);
    }

    $maxTeachingLoad = max(1, (int) $policy['max_teaching_load']);
    $expertiseWeightPct = max(0, min(100, (int) $policy['expertise_weight']));
    $availabilityWeightPct = 100 - $expertiseWeightPct;
    $detectScheduleOverlaps = ((int) $policy['detect_schedule_overlaps'] === 1);
    $enforceOverloadCap = ((int) $policy['flag_overload_teachers'] === 1);
    $requirePrereqCheck = ((int) $policy['check_prerequisites'] === 1);

    $timeToSeconds = static function (string $time): int {
        $parts = explode(':', $time);
        $h = isset($parts[0]) ? (int) $parts[0] : 0;
        $m = isset($parts[1]) ? (int) $parts[1] : 0;
        $s = isset($parts[2]) ? (int) $parts[2] : 0;
        return ($h * 3600) + ($m * 60) + $s;
    };

    $hasScheduleConflict = static function (array $occupiedSlots, array $candidateSlots): bool {
        foreach ($candidateSlots as $candidate) {
            $day = (string) ($candidate['day_of_week'] ?? '');
            if ($day === '' || !isset($occupiedSlots[$day])) {
                continue;
            }
            $candidateStart = (int) ($candidate['start_sec'] ?? 0);
            $candidateEnd = (int) ($candidate['end_sec'] ?? 0);

            foreach ($occupiedSlots[$day] as $occupied) {
                $occupiedStart = (int) ($occupied['start_sec'] ?? 0);
                $occupiedEnd = (int) ($occupied['end_sec'] ?? 0);

                // overlap if start < other_end && other_start < end
                if ($candidateStart < $occupiedEnd && $occupiedStart < $candidateEnd) {
                    return true;
                }
            }
        }
        return false;
    };

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

    // Build subject schedule index for overlap checks.
    $subjectScheduleRows = $pdo->query('SELECT subject_id, day_of_week, start_time, end_time FROM schedules')->fetchAll();
    $subjectSchedulesById = [];
    foreach ($subjectScheduleRows as $ssr) {
        $sid = (int) ($ssr['subject_id'] ?? 0);
        if ($sid <= 0) {
            continue;
        }
        $subjectSchedulesById[$sid][] = [
            'day_of_week' => (string) ($ssr['day_of_week'] ?? ''),
            'start_sec' => $timeToSeconds((string) ($ssr['start_time'] ?? '00:00:00')),
            'end_sec' => $timeToSeconds((string) ($ssr['end_time'] ?? '00:00:00')),
        ];
    }

    // Build existing occupied slots per teacher from current assignments.
    $teacherOccupied = [];
    $occupiedRows = $pdo->query(
        'SELECT a.teacher_id, sch.day_of_week, sch.start_time, sch.end_time
         FROM assignments a
         JOIN schedules sch ON sch.subject_id = a.subject_id
         WHERE a.status IN ("Pending", "Approved", "Manual")'
    )->fetchAll();

    foreach ($occupiedRows as $orow) {
        $tid = (int) ($orow['teacher_id'] ?? 0);
        $day = (string) ($orow['day_of_week'] ?? '');
        if ($tid <= 0 || $day === '') {
            continue;
        }
        $teacherOccupied[$tid][$day][] = [
            'start_sec' => $timeToSeconds((string) ($orow['start_time'] ?? '00:00:00')),
            'end_sec' => $timeToSeconds((string) ($orow['end_time'] ?? '00:00:00')),
        ];
    }

    $plannedAssignments = [];
    $assignedCount = 0;
    $unassignedCount = 0;

    foreach ($unassignedSubjects as $subject) {
        $subjectId = (int) ($subject['id'] ?? 0);
        $subjectUnits = (int) ($subject['units'] ?? 0);
        $subjectPrereq = (string) ($subject['prerequisites'] ?? '');
        $subjectSlots = $subjectSchedulesById[$subjectId] ?? [];

        // Eligible based on simulated load
        $eligible = [];
        foreach ($teacherById as $teacher) {
            $teacherMaxUnits = (int) ($teacher['max_units'] ?? 0);
            if ($teacherMaxUnits <= 0) {
                continue;
            }

            $currentUnits = (int) ($teacher['current_units'] ?? 0);
            $effectiveMaxUnits = min($teacherMaxUnits, $maxTeachingLoad);
            if ($effectiveMaxUnits <= 0) {
                $effectiveMaxUnits = $teacherMaxUnits;
            }

            if ($detectScheduleOverlaps && !empty($subjectSlots)) {
                $tid = (int) ($teacher['id'] ?? 0);
                $occupied = $teacherOccupied[$tid] ?? [];
                if ($hasScheduleConflict($occupied, $subjectSlots)) {
                    continue;
                }
            }

            if (!$enforceOverloadCap || ($currentUnits + $subjectUnits <= $effectiveMaxUnits)) {
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
            $expertise = $requirePrereqCheck
                ? $heuristicExpertiseScore(
                    (string) ($teacher['expertise_tags'] ?? ''),
                    $subjectPrereq
                )
                : ['score' => 50, 'rationale' => 'Prerequisite checking disabled by policy setting.'];

            $teacherMaxUnits = (int) ($teacher['max_units'] ?? 1);
            $maxUnits = max(1, min($teacherMaxUnits, $maxTeachingLoad));
            $currentUnits = (int) ($teacher['current_units'] ?? 0);
            $availabilityScore = (int) round(max(0.0, 1.0 - ($currentUnits / $maxUnits)) * 100);
            $combined = (int) round(($expertise['score'] * ($expertiseWeightPct / 100)) + ($availabilityScore * ($availabilityWeightPct / 100)));

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
                    $teacherMaxUnits = (int) ($pickedTeacher['max_units'] ?? 1);
                    $maxUnits = max(1, min($teacherMaxUnits, $maxTeachingLoad));
                    $currentUnits = (int) ($pickedTeacher['current_units'] ?? 0);
                    $availabilityScore = (int) round(max(0.0, 1.0 - ($currentUnits / $maxUnits)) * 100);
                    $bestScore = (int) round(($aiExpertiseScore * ($expertiseWeightPct / 100)) + ($availabilityScore * ($availabilityWeightPct / 100)));
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
            'subject_id' => $subjectId,
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

        // Update occupied slots for subsequent overlap checks.
        if ($detectScheduleOverlaps && !empty($subjectSlots)) {
            foreach ($subjectSlots as $slot) {
                $day = (string) ($slot['day_of_week'] ?? '');
                if ($day === '') {
                    continue;
                }
                $teacherOccupied[$bestTeacherId][$day][] = [
                    'start_sec' => (int) ($slot['start_sec'] ?? 0),
                    'end_sec' => (int) ($slot['end_sec'] ?? 0),
                ];
            }
        }

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
        'ai_enabled'       => $aiEnabled,
        'ai_calls'         => $aiCalls,
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
