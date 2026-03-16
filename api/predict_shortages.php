<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function table_exists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$tableName]);
    return (bool) $stmt->fetchColumn();
}

/**
 * @param array<int, array{academic_year:string, sections:int}> $series
 */
function average_growth_rate(array $series): float
{
    if (count($series) < 2) {
        return 0.0;
    }

    $rates = [];
    for ($i = 1; $i < count($series); $i++) {
        $prev = (int) $series[$i - 1]['sections'];
        $curr = (int) $series[$i]['sections'];
        if ($prev <= 0) {
            continue;
        }
        $rates[] = ($curr - $prev) / $prev;
    }

    if (empty($rates)) {
        return 0.0;
    }

    return array_sum($rates) / count($rates);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        json_response(405, ['status' => 'error', 'message' => 'Method not allowed.']);
    }

    if (!table_exists($pdo, 'historical_demand')) {
        json_response(500, [
            'status' => 'error',
            'message' => 'Missing required table: historical_demand.',
        ]);
    }

    // Expected historical_demand columns (assumed): subject_id, academic_year, sections_offered
    // Join to subjects to read subject_name, program, and units.
    $demandRows = $pdo->query(
        'SELECT
            s.id AS subject_id,
            s.name AS subject_name,
            s.program,
            s.units,
            hd.academic_year,
            hd.sections_offered
         FROM historical_demand hd
         JOIN subjects s ON s.id = hd.subject_id
         WHERE s.is_archived = 0
         ORDER BY s.id ASC, hd.academic_year ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    // If there are no historical rows, return an empty array (not an error).
    if (empty($demandRows)) {
        json_response(200, []);
    }

    // Group time series per subject.
    $seriesBySubject = [];
    $subjectMeta = [];

    foreach ($demandRows as $row) {
        $subjectId = (int) ($row['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            continue;
        }

        $subjectName = (string) ($row['subject_name'] ?? '');
        $program = (string) ($row['program'] ?? '');
        $units = (int) ($row['units'] ?? 0);
        $academicYear = (string) ($row['academic_year'] ?? '');
        $sections = (int) ($row['sections_offered'] ?? 0);

        $subjectMeta[$subjectId] = [
            'subject_name' => $subjectName,
            'program' => $program,
            'units' => $units,
        ];

        $seriesBySubject[$subjectId][] = [
            'academic_year' => $academicYear,
            'sections' => $sections,
        ];
    }

    // Compute capacity per subject using LIKE matches.
    // Requirement: match expertise_tags against subject name OR program.
    // Active teachers only: is_archived = 0.
    $capacityRows = $pdo->query(
        'SELECT
            s.id AS subject_id,
            COALESCE(SUM(t.max_units), 0) AS total_faculty_capacity
         FROM subjects s
         LEFT JOIN teachers t
            ON t.is_archived = 0
           AND t.expertise_tags IS NOT NULL
           AND (
               t.expertise_tags LIKE CONCAT("%", s.name, "%")
               OR t.expertise_tags LIKE CONCAT("%", s.program, "%")
           )
         WHERE s.is_archived = 0
         GROUP BY s.id'
    )->fetchAll(PDO::FETCH_ASSOC);

    $capacityBySubject = [];
    foreach ($capacityRows as $row) {
        $subjectId = (int) ($row['subject_id'] ?? 0);
        $capacityBySubject[$subjectId] = (int) ($row['total_faculty_capacity'] ?? 0);
    }

    $results = [];

    foreach ($seriesBySubject as $subjectId => $series) {
        if (!isset($subjectMeta[$subjectId])) {
            continue;
        }

        // Sort deterministically even if academic_year is a string.
        usort($series, static function (array $a, array $b): int {
            $ayA = (string) ($a['academic_year'] ?? '');
            $ayB = (string) ($b['academic_year'] ?? '');

            // Prefer comparing by the first 4-digit year when possible (e.g., "AY2024-2025").
            $numA = 0;
            $numB = 0;
            if (preg_match('/(\d{4})/', $ayA, $mA)) {
                $numA = (int) $mA[1];
            }
            if (preg_match('/(\d{4})/', $ayB, $mB)) {
                $numB = (int) $mB[1];
            }
            if ($numA !== 0 && $numB !== 0 && $numA !== $numB) {
                return $numA <=> $numB;
            }

            return strcmp($ayA, $ayB);
        });

        $avgGrowth = average_growth_rate($series);

        $lastSections = (int) ($series[count($series) - 1]['sections'] ?? 0);
        $predictedSections = (int) max(0, (int) ceil($lastSections * (1.0 + $avgGrowth)));

        $units = (int) ($subjectMeta[$subjectId]['units'] ?? 0);
        $projectedUnitsNeeded = $predictedSections * max(0, $units);

        $totalFacultyCapacity = (int) ($capacityBySubject[$subjectId] ?? 0);
        $unitShortage = max(0, $projectedUnitsNeeded - $totalFacultyCapacity);

        $historicalTrend = array_map(static function (array $p): int {
            return (int) ($p['sections'] ?? 0);
        }, $series);

        $results[] = [
            'subject_name' => (string) ($subjectMeta[$subjectId]['subject_name'] ?? ''),
            'historical_trend' => $historicalTrend,
            'projected_units_needed' => $projectedUnitsNeeded,
            'total_faculty_capacity' => $totalFacultyCapacity,
            'unit_shortage' => $unitShortage,
            'hiring_required' => ($unitShortage > 0),
        ];
    }

    // Sort: biggest shortage first.
    usort($results, static function (array $a, array $b): int {
        return ((int) ($b['unit_shortage'] ?? 0)) <=> ((int) ($a['unit_shortage'] ?? 0));
    });

    json_response(200, $results);

} catch (Throwable $e) {
    json_response(500, [
        'status' => 'error',
        'message' => 'Failed to predict shortages: ' . $e->getMessage(),
    ]);
}
