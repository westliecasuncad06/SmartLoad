<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/GeminiAPI.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    $subjectName = trim((string) ($_GET['subject_name'] ?? ''));
    $historyString = trim((string) ($_GET['history_string'] ?? ''));

    $projectedUnits = (int) ($_GET['projected_units'] ?? 0);
    $totalCapacity = (int) ($_GET['total_capacity'] ?? 0);

    if ($subjectName === '') {
        json_response(400, ['status' => 'error', 'message' => 'Missing required parameter: subject_name']);
    }

    // If history isn't provided, accept a comma-separated list under `history` as a convenience.
    if ($historyString === '' && isset($_GET['history'])) {
        $historyString = trim((string) $_GET['history']);
    }

    // Provide a readable default history string if omitted.
    if ($historyString === '') {
        $historyString = 'N/A';
    }

    $ai = new GeminiEvaluator(GEMINI_API_KEY);

    $result = $ai->hiringRecommendation(
        $subjectName,
        $historyString,
        $projectedUnits,
        $totalCapacity
    );

    // IMPORTANT: return exactly the strict JSON structure requested by the prompt.
    http_response_code(200);
    echo json_encode([
        'shortfall_units' => (int) ($result['shortfall_units'] ?? 0),
        'risk_level' => (string) ($result['risk_level'] ?? 'Low'),
        'hr_recommendation' => (string) ($result['hr_recommendation'] ?? ''),
        'impact_warning' => (string) ($result['impact_warning'] ?? ''),
    ]);
    exit;

} catch (Throwable $e) {
    json_response(500, [
        'status' => 'error',
        'message' => 'Failed to generate hiring recommendation: ' . $e->getMessage(),
    ]);
}
