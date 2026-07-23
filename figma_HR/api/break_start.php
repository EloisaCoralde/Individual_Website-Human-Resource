<?php
/* ==========================================================
   POST /api/break_start.php
   Requires an active employee session (login.php).
   Sets break_start = NOW() on the employee's CURRENTLY OPEN
   shift (found by account_id + logout_time IS NULL - the same
   approach api/timein.php and api/timeout.php use), rather than
   relying on a session-stored attendance_id.
========================================================== */

require "config.php";

if (!isset($_SESSION["account_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit;
}

$accountId = $_SESSION["account_id"];

// find the currently open shift (no logout_time yet)
$stmt = $pdo->prepare(
    "SELECT id, break_start FROM attendance
     WHERE account_id = ? AND logout_time IS NULL
     ORDER BY login_time DESC LIMIT 1"
);
$stmt->execute([$accountId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(["success" => false, "message" => "You need to Time In before starting a break."]);
    exit;
}

if ($row["break_start"] !== null) {
    echo json_encode(["success" => false, "message" => "Break already started."]);
    exit;
}

$stmt = $pdo->prepare("UPDATE attendance SET break_start = NOW() WHERE id = ?");
$stmt->execute([$row["id"]]);

echo json_encode(["success" => true]);