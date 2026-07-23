<?php
/* ==========================================================
   POST /api/admin_login.php
   Body (JSON): { email, password }

   Checks the submitted email/password against the "admins"
   table (completely separate from the employee "accounts"
   table). On success, stores admin_id in the PHP session so
   admin.html can confirm the visitor is really an admin.
========================================================== */

require "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$email    = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Please enter your email and password."]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// NOTE: direct string comparison since passwords are stored in
// plain text (no password_verify() / hashing).
if (!$admin || $password !== $admin["password"]) {
    echo json_encode(["success" => false, "message" => "Invalid admin email or password."]);
    exit;
}

// Admins get their own session key so an admin login never
// gets mixed up with a regular employee login.
$_SESSION["admin_id"] = $admin["id"];

echo json_encode(["success" => true]);
