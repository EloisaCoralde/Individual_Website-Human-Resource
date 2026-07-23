<?php
// ==========================================================
//  api/reply_feedback.php
//  Saves an admin's reply to a feedback row and emails it to
//  the person who submitted it.
// ==========================================================

session_start();
header("Content-Type: application/json");

// ---- Must be a logged-in admin to reply ----
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
$input = json_decode(file_get_contents("php://input"), true);
$id    = isset($input["id"]) ? (int) $input["id"] : 0;
$reply = trim($input["reply"] ?? "");

if ($id <= 0 || $reply === "") {
    echo json_encode(["success" => false, "message" => "A reply message is required."]);
    exit;
}

// ---- Look up the original feedback ----
$stmt = $conn->prepare("SELECT email FROM feedback WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$feedback = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$feedback) {
    echo json_encode(["success" => false, "message" => "That feedback no longer exists."]);
    exit;
}

// ---- Save the reply ----
$adminId = $_SESSION["admin_id"];
$update  = $conn->prepare(
    "UPDATE feedback SET reply = ?, replied_at = NOW(), replied_by = ? WHERE id = ?"
);
$update->bind_param("sii", $reply, $adminId, $id);

if (!$update->execute()) {
    echo json_encode(["success" => false, "message" => "Could not save the reply."]);
    exit;
}
$update->close();

// ---- Email the reply to the person who sent the feedback ----
// NOTE: PHP's mail() depends on a mail server/SMTP being configured
// on this machine. On localhost/XAMPP this usually won't actually
// deliver unless you've set that up - the reply is still saved either way.
$to      = $feedback["email"];
$subject = "Re: Your PeopleHub Feedback";
$body    = "Hi,\n\n" . $reply . "\n\n-- \nPeopleHub Support";
$headers = "From: peoplehub@gmail.com\r\nContent-Type: text/plain; charset=UTF-8";

$mailSent = @mail($to, $subject, $body, $headers);

echo json_encode([
    "success"   => true,
    "mail_sent" => $mailSent
]);

$conn->close();
