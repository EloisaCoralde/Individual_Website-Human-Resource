<?php
/* ==========================================================
   GET /api/admin_data.php
   Returns every account (with attendance) and every applicant.

   PROTECTED: only usable when logged in as an admin
   (see admin_login.php / admin.html).
========================================================== */

require "config.php";

// ---- ADMIN GUARD ----
// Without this check, anyone who guesses this URL could see
// every employee's data. admin_login.php is the only place
// that sets $_SESSION["admin_id"].
if (!isset($_SESSION["admin_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Admin login required."]);
    exit;
}

$accounts = $pdo->query(
    "SELECT id, name, email, position, date_joined FROM accounts ORDER BY date_joined DESC"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($accounts as &$account) {
    $stmt = $pdo->prepare(
        "SELECT login_time, logout_time, break_start, break_end
         FROM attendance WHERE account_id = ? ORDER BY login_time DESC"
    );
    $stmt->execute([$account["id"]]);
    $account["attendance"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($account);

$applicants = $pdo->query(
    "SELECT id, position, name, email, phone, date_applied, status FROM applicants ORDER BY date_applied DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// list of existing admins, so the "Manage Admins" tab can show who already has access
$admins = $pdo->query(
    "SELECT id, name, email, date_joined FROM admins ORDER BY date_joined DESC"
)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success"    => true,
    "accounts"   => $accounts,
    "applicants" => $applicants,
    "admins"     => $admins
]);