<?php
/* ==========================================================
   ONE-TIME SETUP HELPER - NOT linked from any page.

   Open this file once in your browser, e.g.:
     http://localhost/figma_HR/api/create_admin.php
   to create your very first admin account. After that, use
   the "Manage Admins" tab in admin.html (logged in as an
   admin) to add any further admins.

   NOTE: passwords are stored in plain text in this project
   (no hashing) - see api/register.php / api/login.php for
   the same setup on the employee side.

   DELETE THIS FILE after creating your first admin - leaving
   it online lets anyone create an admin login.
========================================================== */

require "config.php";

// ---- EDIT THESE THREE VALUES, THEN LOAD THIS PAGE ----
$name     = "System Admin";
$email    = "admin@peoplehub.com";
$password = "admin123";
// --------------------------------------------------------

$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo "An admin with that email already exists.";
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO admins (name, email, password, date_joined) VALUES (?, ?, ?, NOW())"
);
$stmt->execute([$name, $email, $password]);

echo "Admin account created for $email. Remember to delete create_admin.php now.";
