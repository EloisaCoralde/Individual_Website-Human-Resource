<?php
/* ==========================================================
   POST /api/register.php
   Body (JSON): { name, email, password, position }

   Creates a new employee account in the MySQL "accounts" table.
   NOTE: the password is stored exactly as submitted, in plain
   text (no hashing) - anyone with database access can read it.
========================================================== */

require "config.php"; // opens $pdo connection + starts session

$data = json_decode(file_get_contents("php://input"), true);

$name     = trim($data["name"] ?? "");
$email    = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";
$position = trim($data["position"] ?? "") ?: "Employee";

if (!$name || !$email || !$password) {
    echo json_encode(["success" => false, "message" => "Please fill out all fields."]);
    exit;
}

// make sure this email isn't already registered
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo json_encode(["success" => false, "message" => "That email is already registered."]);
    exit;
}

// NOTE: password stored exactly as the user typed it (no hashing).
// This is easier to read directly in the database, but means
// anyone with database access can see every password in plain text.
$stmt = $pdo->prepare(
    "INSERT INTO accounts (name, email, password, position, date_joined) VALUES (?, ?, ?, ?, NOW())"
);
$stmt->execute([$name, $email, $password, $position]);

echo json_encode(["success" => true]);
