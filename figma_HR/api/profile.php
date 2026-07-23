<?php
/* ==========================================================
   GET /api/profile.php
   Requires an active employee session (set by login.php).
   Returns the employee's account info, their full attendance
   history (including break start/end), and the login_time of
   the CURRENT session (used for the live "hours worked" timer).
========================================================== */

require "config.php";

if (!isset($_SESSION["account_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit;
}

$accountId = $_SESSION["account_id"];

// account details
$stmt = $pdo->prepare("SELECT id, name, email, position, date_joined FROM accounts WHERE id = ?");
$stmt->execute([$accountId]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Account not found."]);
    exit;
}

// full attendance history, most recent first (now includes break_start/break_end)
$stmt = $pdo->prepare(
    "SELECT id, login_time, logout_time, break_start, break_end
     FROM attendance WHERE account_id = ? ORDER BY login_time DESC"
);
$stmt->execute([$accountId]);
$attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// find the CURRENTLY OPEN shift, if any (logout_time IS NULL) - this is
// what drives the live timer and shows whether a break is in progress.
// An employee only ever has at most one open shift at a time (enforced
// by api/timein.php), so the first match in this DESC-ordered list is it.
$currentLoginTime = null;
$currentBreakStart = null;
$currentBreakEnd = null;

foreach ($attendance as $row) {
    if ($row["logout_time"] === null) {
        $currentLoginTime  = $row["login_time"];
        $currentBreakStart = $row["break_start"];
        $currentBreakEnd   = $row["break_end"];
        break;
    }
}

echo json_encode([
    "success"           => true,
    "account"           => $account,
    "attendance"        => $attendance,
    "currentLoginTime"  => $currentLoginTime,
    "currentBreakStart" => $currentBreakStart,
    "currentBreakEnd"   => $currentBreakEnd
]);