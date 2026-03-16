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
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Please check your configuration.');
}
