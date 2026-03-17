<?php
// ===================================================
// SmartLoad - Database Connection
// ===================================================

$host = 'localhost';
$dbname = 'smartload';
$username = 'root';
$password = '';

// Gemini AI API Key
// Priority: database api_settings table → environment variable → hardcoded fallback.
$__geminiKey = '';
try {
    $__tmpPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $__tmpPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $__keyRow = $__tmpPdo->query("SELECT api_key FROM api_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($__keyRow && $__keyRow['api_key'] !== '') {
        $__geminiKey = $__keyRow['api_key'];
    }
    $__tmpPdo = null;
} catch (Exception $__e) {
    // Table may not exist yet; fall through.
}
if ($__geminiKey === '') {
    $__geminiKey = getenv('GEMINI_API_KEY') ?: 'AIzaSyAc2guweyZowjbn289MNKWc3VP8VbVvp2A';
}
define('GEMINI_API_KEY', $__geminiKey);

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // ---------------------------------------------------
    // Lightweight schema safety: ensure archive support
    // ---------------------------------------------------
    // This prevents runtime failures if the DB wasn't migrated yet.
    try {
        $colStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS\n"
            . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teachers' AND COLUMN_NAME = 'is_archived'"
        );
        $colStmt->execute();
        $hasColumn = (int)$colStmt->fetchColumn() > 0;
        if (!$hasColumn) {
            $pdo->exec("ALTER TABLE teachers ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (Exception $ignore) {
        // If permissions are limited, we fail open and let queries raise clearer errors.
    }
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check your configuration.');
}
