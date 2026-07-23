<?php
/* ==========================================================
   GET /api/session_check.php
   Lightweight endpoint used by the public pages (Coralde.html,
   careers.html, etc.) to check - without requiring a full
   login - whether the visitor already has an active employee
   or admin session. This lets the navbar "Login" button turn
   into a "My Profile" / "Admin Dashboard" button automatically.
========================================================== */

require "config.php";

$loggedInAsAccount = isset($_SESSION["account_id"]);
$loggedInAsAdmin   = isset($_SESSION["admin_id"]);

$accountName = null;
if ($loggedInAsAccount) {
    $stmt = $pdo->prepare("SELECT name FROM accounts WHERE id = ?");
    $stmt->execute([$_SESSION["account_id"]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $accountName = $row ? $row["name"] : null;
}

$adminName = null;
if ($loggedInAsAdmin) {
    $stmt = $pdo->prepare("SELECT name FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION["admin_id"]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminName = $row ? $row["name"] : null;
}

echo json_encode([
    "success"            => true,
    "loggedInAsAccount"  => $loggedInAsAccount,
    "loggedInAsAdmin"    => $loggedInAsAdmin,
    "accountName"        => $accountName,
    "adminName"          => $adminName
]);
