<?php
// ==========================================================
//  api/contact.php
//  Handles submissions from the "Send us a Message" form on
//  contact.html and stores each one in the `contact_messages`
//  table (see database.sql).
// ==========================================================

// ---- Database connection ----
// Update these to match your MySQL setup (same DB used by the
// rest of /api, e.g. create_admin.php).
$host   = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "peoplehub";

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
$firstName = trim($_POST["first_name"] ?? "");
$lastName  = trim($_POST["last_name"] ?? "");
$email     = trim($_POST["email"] ?? "");
$subject   = trim($_POST["subject"] ?? "");
$message   = trim($_POST["message"] ?? "");

if ($firstName === "" || $lastName === "" || $email === "" || $subject === "" || $message === "") {
    http_response_code(400);
    die("Please fill in all fields.");
}

// ---- Email must match the name@email.com format ----
// filter_var enforces a proper local-part@domain.tld shape;
// the extra regex double-checks the domain has a dot + letters
// after the @ (i.e. name@email.com, not just name@localhost).
$emailPattern = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($emailPattern, $email)) {
    http_response_code(400);
    die("Please enter a valid email address in the format name@email.com.");
}

// ---- Insert into the database ----
$stmt = $conn->prepare(
    "INSERT INTO contact_messages (first_name, last_name, email, subject, message, date_sent)
     VALUES (?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("sssss", $firstName, $lastName, $email, $subject, $message);

if ($stmt->execute()) {
    // Redirect back to the contact page with a success flag.
    // contact.html can check for ?contact=sent to show a thank-you message.
    header("Location: ../contact.html?contact=sent");
    exit;
} else {
    http_response_code(500);
    echo "Something went wrong. Please try again later.";
}

$stmt->close();
$conn->close();
