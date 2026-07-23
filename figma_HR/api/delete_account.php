<?php
// ==========================================================
//  api/delete_account.php
//  Called from the Accounts & Attendance table's Delete button
//  on admin.html. Deletes the employee account AND, if that
//  employee had also been promoted to admin (see admin_create.php),
//  deletes their matching row in the `admins` table too - so
//  removing an account can't leave a stray admin login behind.
//  Attendance rows are removed automatically via the existing
//  ON DELETE CASCADE foreign key on attendance.account_id.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin to delete accounts ----
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
$input     = json_decode(file_get_contents("php://input"), true);
$accountId = isset($input["account_id"]) ? (int) $input["account_id"] : 0;

if ($accountId <= 0) {
    echo json_encode(["success" => false, "message" => "Please select an account to delete."]);
    exit;
}

// ---- Look up the account's email first, so we can also clean up admins ----
$stmt = $conn->prepare("SELECT email FROM accounts WHERE id = ?");
$stmt->bind_param("i", $accountId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    echo json_encode(["success" => false, "message" => "That account no longer exists."]);
    exit;
}

$email = $account["email"];

// ---- Delete the employee account (attendance rows cascade automatically) ----
$deleteAccount = $conn->prepare("DELETE FROM accounts WHERE id = ?");
$deleteAccount->bind_param("i", $accountId);

if (!$deleteAccount->execute()) {
    echo json_encode(["success" => false, "message" => "Could not delete the account."]);
    $deleteAccount->close();
    $conn->close();
    exit;
}
$deleteAccount->close();

// ---- Also delete any admin row that was promoted from this same email ----
$deleteAdmin = $conn->prepare("DELETE FROM admins WHERE email = ?");
$deleteAdmin->bind_param("s", $email);
$deleteAdmin->execute();
$adminRowsDeleted = $deleteAdmin->affected_rows;
$deleteAdmin->close();

echo json_encode([
    "success"          => true,
    "admin_also_removed" => $adminRowsDeleted > 0
]);

$conn->close();
