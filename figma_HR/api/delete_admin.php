<?php
// ==========================================================
//  api/delete_admin.php
//  Called from the "Existing Admin" table's Remove Admin
//  button on admin.html. Deletes ONLY the row in `admins` -
//  it does NOT touch the matching employee account in
//  `accounts`, so the person keeps working/logging in as a
//  regular employee, they just lose admin dashboard access.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin to remove admins ----
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
$input   = json_decode(file_get_contents("php://input"), true);
$adminId = isset($input["admin_id"]) ? (int) $input["admin_id"] : 0;

if ($adminId <= 0) {
    echo json_encode(["success" => false, "message" => "Please select an admin to remove."]);
    exit;
}

// ---- Don't let an admin remove their own access (avoids locking everyone out) ----
if ($adminId === (int) $_SESSION["admin_id"]) {
    echo json_encode(["success" => false, "message" => "You can't remove your own admin access. Ask another admin to do this instead."]);
    exit;
}

// ---- Make sure that admin exists, and check if they're the system admin ----
$stmt = $conn->prepare("SELECT id, is_system_admin FROM admins WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    echo json_encode(["success" => false, "message" => "That admin no longer exists."]);
    exit;
}

if ((int) $admin["is_system_admin"] === 1) {
    echo json_encode(["success" => false, "message" => "This is the main system admin and cannot be removed."]);
    exit;
}

// ---- Delete just the admins row ----
$delete = $conn->prepare("DELETE FROM admins WHERE id = ?");
$delete->bind_param("i", $adminId);

if ($delete->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not remove admin access."]);
}

$delete->close();
$conn->close();
