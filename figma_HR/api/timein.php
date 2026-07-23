<?php
// ==========================================================
//  api/timein.php
//  Called ONLY by the "Time In" button on profile.html. Starts
//  a new attendance shift (inserts a row with login_time = NOW()).
//  Logging into the account no longer does this automatically -
//  the employee must click Time In explicitly each time.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in employee ----
if (empty($_SESSION["account_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit;
}

// ---- Database connection ----
$host   = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "peoplehub";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit;
}

$accountId = $_SESSION["account_id"];

// ---- Make sure there isn't already an open shift ----
$stmt = $conn->prepare(
    "SELECT id FROM attendance WHERE account_id = ? AND logout_time IS NULL LIMIT 1"
);
$stmt->bind_param("i", $accountId);
$stmt->execute();
$openShift = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($openShift) {
    echo json_encode(["success" => false, "message" => "You are already timed in."]);
    exit;
}

// ---- Start a new shift ----
$insert = $conn->prepare("INSERT INTO attendance (account_id, login_time) VALUES (?, NOW())");
$insert->bind_param("i", $accountId);

if ($insert->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not record your time in."]);
}

$insert->close();
$conn->close();
