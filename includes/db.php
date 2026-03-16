<?php
// ===================================================
// SmartLoad - Database Connection
// ===================================================

$host = 'localhost';
$dbname = 'smartload';
$username = 'root';
$password = '';

// Gemini AI API Key (replace with your actual key)
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');

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
