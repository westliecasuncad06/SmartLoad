<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function defaultPolicySettings(): array {
    return [
        'max_teaching_load' => 18,
        'expertise_weight' => 70,
        'availability_weight' => 30,
        'detect_schedule_overlaps' => 1,
        'flag_overload_teachers' => 1,
        'check_prerequisites' => 1,
    ];
}

function normalizePolicySettings(array $input): array {
    $settings = defaultPolicySettings();

    $maxLoad = isset($input['max_teaching_load']) ? (int) $input['max_teaching_load'] : $settings['max_teaching_load'];
    $expertise = isset($input['expertise_weight']) ? (int) $input['expertise_weight'] : $settings['expertise_weight'];
    $availability = isset($input['availability_weight']) ? (int) $input['availability_weight'] : $settings['availability_weight'];

    $maxLoad = max(1, min(40, $maxLoad));
    $expertise = max(0, min(100, $expertise));
    $availability = 100 - $expertise;

    $settings['max_teaching_load'] = $maxLoad;
    $settings['expertise_weight'] = $expertise;
    $settings['availability_weight'] = $availability;
    $settings['detect_schedule_overlaps'] = !empty($input['detect_schedule_overlaps']) ? 1 : 0;
    $settings['flag_overload_teachers'] = !empty($input['flag_overload_teachers']) ? 1 : 0;
    $settings['check_prerequisites'] = !empty($input['check_prerequisites']) ? 1 : 0;

    return $settings;
}

try {
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

    $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM policy_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $defaults = defaultPolicySettings();
            $insert = $pdo->prepare(
                'INSERT INTO policy_settings
                    (id, max_teaching_load, expertise_weight, availability_weight, detect_schedule_overlaps, flag_overload_teachers, check_prerequisites)
                 VALUES (1, :max_teaching_load, :expertise_weight, :availability_weight, :detect_schedule_overlaps, :flag_overload_teachers, :check_prerequisites)'
            );
            $insert->execute($defaults);
            $row = array_merge(['id' => 1], $defaults);
        }

        echo json_encode([
            'status' => 'success',
            'settings' => [
                'max_teaching_load' => (int) $row['max_teaching_load'],
                'expertise_weight' => (int) $row['expertise_weight'],
                'availability_weight' => (int) $row['availability_weight'],
                'detect_schedule_overlaps' => (int) $row['detect_schedule_overlaps'],
                'flag_overload_teachers' => (int) $row['flag_overload_teachers'],
                'check_prerequisites' => (int) $row['check_prerequisites'],
            ],
        ]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid request payload.');
        }

        $settings = normalizePolicySettings($payload);

        $upsert = $pdo->prepare(
            'INSERT INTO policy_settings
                (id, max_teaching_load, expertise_weight, availability_weight, detect_schedule_overlaps, flag_overload_teachers, check_prerequisites)
             VALUES (1, :max_teaching_load, :expertise_weight, :availability_weight, :detect_schedule_overlaps, :flag_overload_teachers, :check_prerequisites)
             ON DUPLICATE KEY UPDATE
                max_teaching_load = VALUES(max_teaching_load),
                expertise_weight = VALUES(expertise_weight),
                availability_weight = VALUES(availability_weight),
                detect_schedule_overlaps = VALUES(detect_schedule_overlaps),
                flag_overload_teachers = VALUES(flag_overload_teachers),
                check_prerequisites = VALUES(check_prerequisites)'
        );
        $upsert->execute($settings);

        echo json_encode([
            'status' => 'success',
            'message' => 'Policy settings saved successfully.',
            'settings' => $settings,
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
