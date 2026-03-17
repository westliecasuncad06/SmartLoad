<?php

class GeminiEvaluator
{
    private string $apiKey;
    private string $endpoint;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    }

    public function isEnabled(): bool
    {
        $key = trim($this->apiKey);
        // Disabled when missing or left as placeholder.
        if ($key === '' || $key === 'AIzaSyAc2guweyZowjbn289MNKWc3VP8VbVvp2A') {
            return false;
        }

        return function_exists('curl_init');
    }

    private function incrementUsage(): void
    {
        try {
            $host = 'localhost'; $db = 'smartload'; $u = 'root'; $p = '';
            $conn = new \PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $u, $p);
            $conn->exec("UPDATE api_settings SET total_requests = total_requests + 1, last_used_at = NOW() WHERE id = 1");
        } catch (\Exception $e) {
            // Silently fail — tracking is non-critical.
        }
    }

    /**
     * Compare teacher expertise tags against subject prerequisites via the Gemini API.
     *
     * @param string $teacherTags   Comma-separated expertise tags.
     * @param string $subjectPrerequisites  Comma-separated prerequisite descriptions.
     * @return array{score: int, rationale: string}
     */
    public function scoreExpertise(string $teacherTags, string $subjectPrerequisites): array
    {
        if (!$this->isEnabled()) {
            return ['score' => 0, 'rationale' => 'AI scoring disabled or unavailable.'];
        }

        $prompt = <<<PROMPT
You are an academic scheduling assistant. Compare the following teacher expertise tags against the subject prerequisites and return a match score from 0 to 100, where 0 means no relevance and 100 means a perfect match.

Teacher expertise tags: {$teacherTags}
Subject prerequisites: {$subjectPrerequisites}

Return ONLY a pure raw JSON object with exactly two keys:
- "score": an integer from 0 to 100
- "rationale": a single sentence explaining the score

Do not wrap the JSON in markdown code fences or any other formatting. Do not include any text outside the JSON object.
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
                'maxOutputTokens' => 256,
            ],
        ];

        $url = $this->endpoint . '?key=' . urlencode($this->apiKey);

        $ch = curl_init($url);
        if ($ch === false) {
            return ['score' => 0, 'rationale' => 'cURL initialization failed.'];
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['score' => 0, 'rationale' => 'API request failed: ' . $curlError];
        }

        if ($httpCode !== 200) {
            return ['score' => 0, 'rationale' => 'API returned HTTP ' . $httpCode];
        }

        $this->incrementUsage();

        $body = json_decode($response, true);
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return ['score' => 0, 'rationale' => 'Unexpected API response structure.'];
        }

        $text = trim($body['candidates'][0]['content']['parts'][0]['text']);

        // Strip markdown code fences if the model included them despite instructions
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        $result = json_decode($text, true);
        if (!is_array($result) || !isset($result['score'], $result['rationale'])) {
            return ['score' => 0, 'rationale' => 'Failed to parse AI response.'];
        }

        return [
            'score'     => (int) $result['score'],
            'rationale' => (string) $result['rationale'],
        ];
    }

    /**
     * Pick the best teacher from a shortlist for a single subject.
     *
     * @param array<int, array{id: int|string, name: string, expertise_tags: string}> $candidates
     * @return array{teacher_id: int, score: int, rationale: string}
     */
    public function pickBestTeacher(array $candidates, string $subjectPrerequisites): array
    {
        if (!$this->isEnabled() || empty($candidates)) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'AI scoring disabled or no candidates.'];
        }

        $candidateLines = [];
        foreach ($candidates as $c) {
            $id = (int) ($c['id'] ?? 0);
            $name = (string) ($c['name'] ?? '');
            $tags = (string) ($c['expertise_tags'] ?? '');
            $candidateLines[] = "- id: {$id}, name: {$name}, tags: {$tags}";
        }
        $candidateList = implode("\n", $candidateLines);

        $prompt = <<<PROMPT
You are an academic scheduling assistant. Choose the single best teacher for this subject based on relevance.

Subject prerequisites: {$subjectPrerequisites}

Candidate teachers:
{$candidateList}

Return ONLY a pure raw JSON object with exactly three keys:
- "teacher_id": integer (must match one of the candidate ids)
- "score": integer 0..100 (how well the chosen teacher matches the prerequisites)
- "rationale": one sentence

Do not wrap the JSON in markdown code fences or any other formatting. Do not include any text outside the JSON object.
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
                'maxOutputTokens' => 256,
            ],
        ];

        $url = $this->endpoint . '?key=' . urlencode($this->apiKey);
        $ch = curl_init($url);
        if ($ch === false) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'cURL initialization failed.'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'API request failed: ' . $curlError];
        }
        if ($httpCode !== 200) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'API returned HTTP ' . $httpCode];
        }

        $this->incrementUsage();

        $body = json_decode($response, true);
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'Unexpected API response structure.'];
        }

        $text = trim((string) $body['candidates'][0]['content']['parts'][0]['text']);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        $result = json_decode($text, true);
        if (!is_array($result) || !isset($result['teacher_id'], $result['score'], $result['rationale'])) {
            return ['teacher_id' => 0, 'score' => 0, 'rationale' => 'Failed to parse AI response.'];
        }

        return [
            'teacher_id' => (int) $result['teacher_id'],
            'score' => (int) $result['score'],
            'rationale' => (string) $result['rationale'],
        ];
    }

    /**
     * Provide a strategic hiring recommendation for a subject based on projected units vs capacity.
     *
     * @return array{shortfall_units:int, risk_level:string, hr_recommendation:string, impact_warning:string}
     */
    public function hiringRecommendation(
        string $subjectName,
        string $historyString,
        int $projectedUnits,
        int $totalCapacity
    ): array {
        $shortfall = $projectedUnits - $totalCapacity;
        if ($shortfall <= 0) {
            return [
                'shortfall_units' => 0,
                'risk_level' => 'Low',
                'hr_recommendation' => 'No additional instructor hiring is required for this subject based on the current projection.',
                'impact_warning' => 'No projected capacity shortfall; continue monitoring demand to avoid unexpected enrollment constraints.',
            ];
        }

        if (!$this->isEnabled()) {
            $risk = $shortfall >= 12 ? 'Critical' : 'Medium';
            $hireType = $shortfall >= 12 ? '1 Full-Time instructor' : '1 Part-Time instructor';

            return [
                'shortfall_units' => (int) $shortfall,
                'risk_level' => $risk,
                'hr_recommendation' => 'Hire ' . $hireType . ' specialized for ' . trim($subjectName) . ' to cover the projected unit shortfall.',
                'impact_warning' => 'If unaddressed, faculty overload or blocked enrollments may delay student progression and reduce course completion rates.',
            ];
        }

        $subjectNameEscaped = trim($subjectName);
        $historyEscaped = trim($historyString);

        $prompt = <<<PROMPT
You are an Expert University HR Planner and Data Analyst.
Analyze the following faculty workload projection and provide a strategic hiring recommendation.

DATA FOR ANALYSIS:
- Subject: {$subjectNameEscaped}
- Historical Sections Offered (Past 3 Years): {$historyEscaped}
- Projected Units Needed Next Year: {$projectedUnits}
- Current Specialized Faculty Capacity: {$totalCapacity} units

YOUR TASK:
Calculate the shortfall (Projected - Capacity). If there is a shortfall, assess the business risk and provide a specific hiring recommendation.

CRITICAL IMPORTANCE FACTORS TO CONSIDER:
1. Distinguish between needing a Part-Time instructor (usually 3-9 units) vs a Full-Time instructor (12+ units).
2. Consider the impact of overloaded teachers or students being unable to enroll in required classes.

OUTPUT FORMAT:
You must return your analysis in STRICT, valid JSON format. Do not include markdown formatting or code blocks. Use this exact structure:
{
    "shortfall_units": <integer>,
    "risk_level": "<Low | Medium | Critical>",
    "hr_recommendation": "<1 clear, direct sentence on exactly who to hire>",
    "impact_warning": "<1 sentence explaining the negative impact to the university if this shortage is ignored>"
}
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
                'maxOutputTokens' => 256,
                // Best-effort hint: if supported by the API/model, this encourages JSON output.
                'responseMimeType' => 'application/json',
            ],
        ];

        $url = $this->endpoint . '?key=' . urlencode($this->apiKey);
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'shortfall_units' => (int) $shortfall,
                'risk_level' => 'Medium',
                'hr_recommendation' => 'Hire 1 Part-Time instructor specialized for ' . $subjectNameEscaped . ' to cover the projected shortfall.',
                'impact_warning' => 'If ignored, course offerings may be reduced and students may be blocked from required classes.',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode === 200) {
            $this->incrementUsage();
        }

        if ($response === false || $httpCode !== 200) {
            $risk = $shortfall >= 12 ? 'Critical' : 'Medium';
            $hireType = $shortfall >= 12 ? '1 Full-Time instructor' : '1 Part-Time instructor';
            $warning = $response === false
                ? ('AI request failed: ' . $curlError)
                : ('AI returned HTTP ' . $httpCode);

            return [
                'shortfall_units' => (int) $shortfall,
                'risk_level' => $risk,
                'hr_recommendation' => 'Hire ' . $hireType . ' specialized for ' . $subjectNameEscaped . ' to cover the projected unit shortfall.',
                'impact_warning' => 'AI unavailable (' . $warning . '); if ignored, overloads or blocked enrollments may occur due to insufficient capacity.',
            ];
        }

        $body = json_decode($response, true);
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'shortfall_units' => (int) $shortfall,
                'risk_level' => 'Medium',
                'hr_recommendation' => 'Hire 1 Part-Time instructor specialized for ' . $subjectNameEscaped . ' to cover the projected shortfall.',
                'impact_warning' => 'Unexpected AI response format; if ignored, insufficient staffing may restrict course offerings and student enrollment.',
            ];
        }

        $text = trim((string) $body['candidates'][0]['content']['parts'][0]['text']);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        // If extra text is present, attempt to extract the first JSON object.
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $text = substr($text, $start, $end - $start + 1);
        }

        $result = json_decode($text, true);
        if (!is_array($result)) {
            return [
                'shortfall_units' => (int) $shortfall,
                'risk_level' => $shortfall >= 12 ? 'Critical' : 'Medium',
                'hr_recommendation' => 'Hire ' . ($shortfall >= 12 ? '1 Full-Time instructor' : '1 Part-Time instructor') . ' specialized for ' . $subjectNameEscaped . ' to cover the projected unit shortfall.',
                'impact_warning' => 'Failed to parse AI JSON; if ignored, student enrollment may be constrained and faculty may be overloaded.',
            ];
        }

        $outShortfall = isset($result['shortfall_units']) ? (int) $result['shortfall_units'] : (int) $shortfall;
        if ($outShortfall < 0) {
            $outShortfall = 0;
        }

        $riskLevel = (string) ($result['risk_level'] ?? 'Medium');
        $riskLevelNorm = ucfirst(strtolower(trim($riskLevel)));
        if (!in_array($riskLevelNorm, ['Low', 'Medium', 'Critical'], true)) {
            $riskLevelNorm = $outShortfall >= 12 ? 'Critical' : 'Medium';
        }

        $hrRecommendation = trim((string) ($result['hr_recommendation'] ?? ''));
        if ($hrRecommendation === '') {
            $hrRecommendation = 'Hire ' . ($outShortfall >= 12 ? '1 Full-Time instructor' : '1 Part-Time instructor') . ' specialized for ' . $subjectNameEscaped . ' to cover the projected unit shortfall.';
        }

        $impactWarning = trim((string) ($result['impact_warning'] ?? ''));
        if ($impactWarning === '') {
            $impactWarning = 'If ignored, course sections may be reduced, increasing overload risk and preventing students from enrolling in required classes.';
        }

        return [
            'shortfall_units' => (int) $outShortfall,
            'risk_level' => $riskLevelNorm,
            'hr_recommendation' => $hrRecommendation,
            'impact_warning' => $impactWarning,
        ];
    }
}
