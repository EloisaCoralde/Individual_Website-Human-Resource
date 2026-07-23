<?php
// ==========================================================
//  api/update_profile.php
//  Called from the "Edit Profile" modal on profile.html.
//  Lets the logged-in employee change their email and/or
//  position/department. If the email changes, it becomes the
//  email they must use to log in from then on.
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

// ---- Read the posted JSON body ----
$input    = json_decode(file_get_contents("php://input"), true);
$email    = trim($input["email"] ?? "");
$position = trim($input["position"] ?? "");

if ($email === "") {
    echo json_encode(["success" => false, "message" => "Email address is required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit;
}

// ---- Make sure no OTHER account is already using this email ----
$check = $conn->prepare("SELECT id FROM accounts WHERE LOWER(email) = LOWER(?) AND id != ?");
$check->bind_param("si", $email, $accountId);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    echo json_encode(["success" => false, "message" => "That email is already in use by another account."]);
    exit;
}
$check->close();

// ---- Update the account ----
$update = $conn->prepare("UPDATE accounts SET email = ?, position = ? WHERE id = ?");
$update->bind_param("ssi", $email, $position, $accountId);

if ($update->execute()) {
    // Keep the session's view of things in sync (in case it's used
    // elsewhere), though the DB row is now the source of truth.
    $_SESSION["account_email"] = $email;
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Could not update profile."]);
}

$update->close();
$conn->close();
