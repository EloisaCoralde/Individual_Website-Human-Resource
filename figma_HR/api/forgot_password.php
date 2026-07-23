<?php
// ==========================================================
//  api/forgot_password.php
//  Called from the "Forgot Password" modal on login.html.
//  Looks up the account by email and overwrites its password
//  with the new one the user chose. No email/token step - if
//  the email matches an account, the password is updated
//  immediately.
// ==========================================================

header("Content-Type: application/json");

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
$input       = json_decode(file_get_contents("php://input"), true);
$email       = trim($input["email"] ?? "");
$newPassword = $input["newPassword"] ?? "";

if ($email === "" || $newPassword === "") {
    echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters."]);
    exit;
}

// ---- Make sure the account exists (case-insensitive email match) ----
$stmt = $conn->prepare("SELECT id, password FROM accounts WHERE LOWER(email) = LOWER(?)");
$stmt->bind_param("s", $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    // Same generic message either way, so this endpoint can't be used
    // to check which emails are registered.
    echo json_encode(["success" => false, "message" => "If that email is registered, please check it and try again."]);
    exit;
}

// ---- Update the password ----
// NOTE: passwords are stored in plain text in this schema (see
// database.sql). Ideally this should hash with password_hash()
// instead - flagging that here since it applies to every password
// write path, not just this one.
$update = $conn->prepare("UPDATE accounts SET password = ? WHERE id = ?");
$update->bind_param("si", $newPassword, $account["id"]);
$update->execute();

if ($update->error) {
    // Surface the real database error instead of a generic message,
    // so this is actually debuggable instead of failing silently.
    echo json_encode(["success" => false, "message" => "Database error: " . $update->error]);
    $update->close();
    $conn->close();
    exit;
}

if ($update->affected_rows === 0 && $account["password"] === $newPassword) {
    // Nothing changed because the "new" password is identical to the
    // old one - not a real failure, just a no-op.
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => true, "rows_updated" => $update->affected_rows]);
}

$update->close();
$conn->close();