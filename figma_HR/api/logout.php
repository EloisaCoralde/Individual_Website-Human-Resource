<?php
// ==========================================================
//  api/logout.php
//  Ends the employee's ACCOUNT SESSION only. It intentionally
//  does NOT touch the attendance table anymore - shifts are
//  only closed out via the "Time Out" button (api/timeout.php).
//  This means an employee can log out and log back in later
//  while still "on the clock" for the same shift.
// ==========================================================

session_start();
header("Content-Type: application/json");

// Clear the session data
$_SESSION = [];

// Destroy the session cookie itself, if one is set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

echo json_encode(["success" => true]);