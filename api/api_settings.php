<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Ensure the api_settings table exists
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS api_settings (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
        api_key VARCHAR(255) NOT NULL DEFAULT \'\',
        total_requests INT UNSIGNED NOT NULL DEFAULT 0,
        last_used_at DATETIME DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

// Seed default row if missing
$row = $pdo->query('SELECT * FROM api_settings WHERE id = 1')->fetch();
if (!$row) {
    $pdo->exec("INSERT INTO api_settings (id, api_key) VALUES (1, '')");
    $row = $pdo->query('SELECT * FROM api_settings WHERE id = 1')->fetch();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $key = $row['api_key'];
    $masked = '';
    if (strlen($key) > 8) {
        $masked = substr($key, 0, 6) . str_repeat('*', strlen($key) - 10) . substr($key, -4);
    } elseif ($key !== '') {
        $masked = str_repeat('*', strlen($key));
    }

    // Determine status
    $status = 'not_configured';
    if ($key !== '') {
        $status = 'configured';
        // Quick validation: try a tiny Gemini call
        if (function_exists('curl_init')) {
            $testUrl = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($key);
            $ch = curl_init($testUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $status = 'active';
            } elseif ($httpCode === 400 || $httpCode === 403) {
                $status = 'invalid';
            } elseif ($httpCode === 429) {
                $status = 'quota_exceeded';
            } else {
                $status = 'error';
            }
        }
    }

    echo json_encode([
        'status'         => 'success',
        'api_key_masked'  => $masked,
        'api_status'      => $status,
        'total_requests'  => (int) $row['total_requests'],
        'last_used_at'    => $row['last_used_at'],
        'updated_at'      => $row['updated_at'],
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $newKey = isset($input['api_key']) ? trim($input['api_key']) : null;

    if ($newKey === null) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'api_key is required.']);
        exit;
    }

    // Basic format validation for Gemini keys (AIza...)
    if ($newKey !== '' && !preg_match('/^AIza[A-Za-z0-9_-]{30,}$/', $newKey)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid API key format. Gemini keys start with AIza...']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE api_settings SET api_key = :key WHERE id = 1');
    $stmt->execute(['key' => $newKey]);

    // Log the change
    try {
        $logStmt = $pdo->prepare(
            "INSERT INTO audit_logs (action_type, description, user) VALUES ('Settings Update', :desc, 'Program Chair')"
        );
        $action = $newKey === '' ? 'API key removed' : 'API key updated';
        $logStmt->execute(['desc' => $action]);
    } catch (Exception $ignore) {}

    echo json_encode(['status' => 'success', 'message' => $newKey === '' ? 'API key removed.' : 'API key updated successfully.']);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
