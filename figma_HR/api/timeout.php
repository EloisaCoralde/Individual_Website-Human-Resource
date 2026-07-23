<?php
// ==========================================================
//  api/timeout.php
//  Called ONLY by the "Time Out" button on profile.html. This
//  is now the single place that ends an attendance shift
//  (sets logout_time). Logging out of the account (api/logout.php)
//  no longer does this.
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

// ---- Find this employee's currently open shift (no logout_time yet) ----
$stmt = $conn->prepare(
    "SELECT id FROM attendance WHERE account_id = ? AND logout_time IS NULL
     ORDER BY login_time DESC LIMIT 1"
);
$stmt->bind_param("i", $accountId);
$stmt->execute();
$shift = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shift) {
    echo json_encode(["success" => false, "message" => "You don't have an active shift to time out from."]);
    exit;
}

// ---- Close it out ----
$update = $conn->prepare("UPDATE attendance SET logout_time = NOW() WHERE id = ?");
$update->bind_param("i", $shift["id"]);

if ($update->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not record your time out."]);
}

$update->close();
$conn->close();
