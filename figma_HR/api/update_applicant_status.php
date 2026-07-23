<?php
// ==========================================================
//  api/update_applicant_status.php
//  Called from the Job Applicants status dropdown on
//  admin.html. Updates an applicant's progress status.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin ----
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

// ---- Read the posted JSON body ----
$input        = json_decode(file_get_contents("php://input"), true);
$applicantId  = isset($input["applicant_id"]) ? (int) $input["applicant_id"] : 0;
$status       = trim($input["status"] ?? "");

$allowedStatuses = ["Pending", "Under Review", "Interview Scheduled", "Hired", "Rejected"];

if ($applicantId <= 0 || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(["success" => false, "message" => "Invalid applicant or status."]);
    exit;
}

$update = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?");
$update->bind_param("si", $status, $applicantId);

if ($update->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update status."]);
}

$update->close();
$conn->close();
