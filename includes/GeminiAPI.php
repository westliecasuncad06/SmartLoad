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
        if ($key === '' || $key === 'YOUR_GEMINI_API_KEY_HERE') {
            return false;
        }

        return function_exists('curl_init');
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
}
