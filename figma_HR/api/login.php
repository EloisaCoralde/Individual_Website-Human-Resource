<?php
/* ==========================================================
   POST /api/login.php
   Body (JSON): { email, password }

   Checks the submitted email/password against the "accounts"
   table in MySQL. On success it ONLY starts a PHP session for
   the employee (account_id) - it does NOT touch attendance.
   Starting/ending a shift ("Time In" / "Time Out") is handled
   separately by api/timein.php and api/timeout.php, triggered
   only when the employee clicks those buttons on profile.html.
   Logging out (api/logout.php) also does NOT touch attendance -
   the employee can log back in later and click Time Out whenever
   they're ready to end their shift.
   The frontend (login.html) then redirects to profile.html.
========================================================== */

require "config.php"; // opens $pdo connection + starts session (do NOT include this twice)

$data = json_decode(file_get_contents("php://input"), true);

$email    = strtolower(trim($data["email"] ?? ""));
$password = $data["password"] ?? "";

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Please enter your email and password."]);
    exit;
}

// look up the account by email
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE email = ?");
$stmt->execute([$email]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// NOTE: direct string comparison since passwords are stored in
// plain text (no password_verify() / hashing).
if (!$account || $password !== $account["password"]) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    exit;
}

// keep the logged-in employee in the PHP session (server-side, cookie-based)
// - no attendance row is created here anymore; the employee must click
// "Time In" on profile.html to start their shift, and "Time Out" to end it.
$_SESSION["account_id"] = $account["id"];

echo json_encode(["success" => true]);