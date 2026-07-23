<?php
// ==========================================================
//  api/admin_create.php
//  Called from admin.html's "Add New Admin" form. Instead of
//  typing a brand-new name/email/password, the admin picks an
//  EXISTING employee account, and this script copies that
//  account's name/email/password over into the `admins` table.
//  The original employee account in `accounts` is left as-is,
//  so the person keeps working as an employee AND gains admin
//  access using the same email/password.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin to do this ----
if (empty($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not authorized."]);
    exit;
}

// ---- Database connection ----
// Update these to match your MySQL setup (same DB used by the
// rest of /api, e.g. create_admin.php / admin_data.php).
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
$input     = json_decode(file_get_contents("php://input"), true);
$accountId = isset($input["account_id"]) ? (int) $input["account_id"] : 0;

if ($accountId <= 0) {
    echo json_encode(["success" => false, "message" => "Please select an employee account."]);
    exit;
}

// ---- Look up the employee account being promoted ----
$stmt = $conn->prepare("SELECT name, email, password FROM accounts WHERE id = ?");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$result  = $stmt->get_result();
$account = $result->fetch_assoc();
$stmt->close();

if (!$account) {
    echo json_encode(["success" => false, "message" => "That employee account no longer exists."]);
    exit;
}

// ---- Make sure they aren't already an admin ----
$check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
$check->bind_param("s", $account["email"]);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    echo json_encode(["success" => false, "message" => "This employee is already an admin."]);
    exit;
}
$check->close();

// ---- Insert into admins, reusing the employee's existing credentials ----
$insert = $conn->prepare(
    "INSERT INTO admins (name, email, password, date_joined) VALUES (?, ?, ?, NOW())"
);
$insert->bind_param("sss", $account["name"], $account["email"], $account["password"]);

if ($insert->execute()) {
    echo json_encode(["success" => true, "name" => $account["name"]]);
} else {
    echo json_encode(["success" => false, "message" => "Could not create admin account."]);
}

$insert->close();
$conn->close();