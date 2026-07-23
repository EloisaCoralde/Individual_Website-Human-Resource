<?php
// ==========================================================
//  api/feedback_messages.php
//  Returns every row from `feedback` as JSON, for the "User
//  Feedback" table on admin.html.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin to view feedback ----
if (empty($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authorized."]);
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

$result = $conn->query(
    "SELECT id, email, message, date_sent, reply, replied_at
     FROM feedback
     ORDER BY date_sent DESC"
);

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(["success" => true, "messages" => $messages]);

$conn->close();
