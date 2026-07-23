<?php
/* ==========================================================
   GET /api/admin_profile.php
   Requires an active admin session (set by admin_login.php).
   Used by admin.html on page load to make sure a random
   visitor can't open the dashboard without logging in.
========================================================== */

require "config.php";

if (!isset($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in as admin."]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email, date_joined FROM admins WHERE id = ?");
$stmt->execute([$_SESSION["admin_id"]]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Admin account not found."]);
    exit;
}

echo json_encode(["success" => true, "admin" => $admin]);
