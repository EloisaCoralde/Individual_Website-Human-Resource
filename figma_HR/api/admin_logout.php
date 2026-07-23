<?php
/* ==========================================================
   POST /api/admin_logout.php
   Clears the admin's session. The frontend (admin.html) then
   redirects the browser back to the public homepage.
========================================================== */

require "config.php";

unset($_SESSION["admin_id"]);
session_destroy();

echo json_encode(["success" => true]);
