<?php
/* ==========================================================
   DATABASE CONNECTION CONFIG
   ----------------------------------------------------------
   Edit these four values to match your MySQL setup
   (defaults below match a fresh XAMPP/WAMP install).
========================================================== */

$DB_HOST = "localhost";
$DB_NAME = "peoplehub";
$DB_USER = "root";
$DB_PASS = "";

header("Content-Type: application/json");

// Allow the frontend to send/receive the PHP session cookie
session_start();

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed. Check config.php credentials."
    ]);
    exit;
}
