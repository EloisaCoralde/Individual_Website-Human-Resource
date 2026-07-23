<?php
/* ==========================================================
   POST /api/apply.php
   Body: { position, name, email, phone }
========================================================== */

require "config.php";

$data = json_decode(file_get_contents("php://input"), true);

$position = trim($data["position"] ?? "");
$name     = trim($data["name"] ?? "");
$email    = trim($data["email"] ?? "");
$phone    = trim($data["phone"] ?? "");

if (!$position || !$name || !$email || !$phone) {
    echo json_encode(["success" => false, "message" => "Please fill out all fields."]);
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO applicants (position, name, email, phone, date_applied) VALUES (?, ?, ?, ?, NOW())"
);
$stmt->execute([$position, $name, $email, $phone]);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applicants WHERE position = ?");
$stmt->execute([$position]);
$count = (int)$stmt->fetchColumn();

echo json_encode(["success" => true, "count" => $count]);
