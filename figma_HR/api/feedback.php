<?php
// ==========================================================
//  api/feedback.php
//  Handles submissions from the Feedback form in the footer
//  of Coralde.html and stores each one in the `feedback`
//  table (see database.sql).
// ==========================================================

// ---- Database connection ----
// Update these to match your MySQL setup (same DB used by the
// rest of /api, e.g. create_admin.php).
$host     = "localhost";
$dbUser   = "root";
$dbPass   = "";
$dbName   = "peoplehub";

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// ---- Only accept POST requests ----
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Invalid request method.");
}

// ---- Collect + validate form fields ----
$email   = trim($_POST["email"] ?? "");
$message = trim($_POST["message"] ?? "");

if ($email === "" || $message === "") {
    http_response_code(400);
    die("Please fill in both the email and message fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die("Please enter a valid email address.");
}

// ---- Insert into the database ----
$stmt = $conn->prepare(
    "INSERT INTO feedback (email, message, date_sent) VALUES (?, ?, NOW())"
);
$stmt->bind_param("ss", $email, $message);

if ($stmt->execute()) {
    // Redirect back to the homepage with a success flag.
    // Coralde.html can check for ?feedback=sent to show a thank-you message.
    header("Location: ../Coralde.html?feedback=sent");
    exit;
} else {
    http_response_code(500);
    echo "Something went wrong. Please try again later.";
}

$stmt->close();
$conn->close();
