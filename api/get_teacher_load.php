<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function formatSubjectSchedules(array $schedules): array
{
    $byTime = [];
    foreach ($schedules as $s) {
        $time = (string)($s['start_time'] ?? '');
        $day = (string)($s['day_of_week'] ?? '');
        if ($time === '' || $day === '') continue;
        $byTime[$time][] = substr($day, 0, 3);
    }

    $result = [];
    foreach ($byTime as $time => $days) {
        $result[] = implode('/', array_unique($days)) . ' ' . date('g:i A', strtotime($time));
    }
    return $result;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
    if ($teacherId <= 0) {
        json_response(400, ['status' => 'error', 'message' => 'teacher_id is required.']);
    }

    $tStmt = $pdo->prepare('SELECT id, name, email, type, current_units, max_units, expertise_tags FROM teachers WHERE id = ?');
    $tStmt->execute([$teacherId]);
    $teacher = $tStmt->fetch(PDO::FETCH_ASSOC);
    if (!$teacher) {
        json_response(404, ['status' => 'error', 'message' => 'Teacher not found.']);
    }

    $aStmt = $pdo->prepare(
        'SELECT a.id AS assignment_id, a.status AS assignment_status, a.rationale, a.created_at AS assigned_at,
                sub.id AS subject_id, sub.course_code, sub.name AS subject_name, sub.units AS subject_units, sub.prerequisites
         FROM assignments a
         JOIN subjects sub ON a.subject_id = sub.id
         WHERE a.teacher_id = ?
         ORDER BY sub.course_code ASC'
    );
    $aStmt->execute([$teacherId]);
    $rows = $aStmt->fetchAll(PDO::FETCH_ASSOC);

    $subjects = [];
    $subjectIds = [];
    foreach ($rows as $r) {
        $sid = (int)$r['subject_id'];
        $subjectIds[] = $sid;
        $subjects[] = [
            'assignment_id' => (int)$r['assignment_id'],
            'assignment_status' => $r['assignment_status'],
            'rationale' => $r['rationale'],
            'assigned_at' => $r['assigned_at'],
            'subject_id' => $sid,
            'course_code' => $r['course_code'],
            'subject_name' => $r['subject_name'],
            'subject_units' => (int)$r['subject_units'],
            'prerequisites' => $r['prerequisites'],
            'schedule_lines' => [],
        ];
    }

    $schedBySubject = [];
    $subjectIds = array_values(array_unique(array_filter($subjectIds)));
    if (!empty($subjectIds)) {
        $ph = implode(',', array_fill(0, count($subjectIds), '?'));
        $sStmt = $pdo->prepare("SELECT subject_id, day_of_week, start_time FROM schedules WHERE subject_id IN ($ph) ORDER BY start_time, day_of_week");
        $sStmt->execute($subjectIds);
        foreach ($sStmt->fetchAll(PDO::FETCH_ASSOC) as $sr) {
            $schedBySubject[(int)$sr['subject_id']][] = $sr;
        }
    }

    foreach ($subjects as &$s) {
        $sid = (int)$s['subject_id'];
        $s['schedule_lines'] = !empty($schedBySubject[$sid]) ? formatSubjectSchedules($schedBySubject[$sid]) : [];
    }
    unset($s);

    json_response(200, [
        'status' => 'success',
        'teacher' => [
            'id' => (int)$teacher['id'],
            'name' => $teacher['name'],
            'email' => $teacher['email'],
            'type' => $teacher['type'],
            'current_units' => (int)$teacher['current_units'],
            'max_units' => (int)$teacher['max_units'],
            'expertise_tags' => $teacher['expertise_tags'],
        ],
        'subjects' => $subjects,
    ]);

} catch (Exception $e) {
    json_response(500, ['status' => 'error', 'message' => 'Failed to load teacher details: ' . $e->getMessage()]);
}
