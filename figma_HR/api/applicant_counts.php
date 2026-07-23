<?php
/* ==========================================================
   GET /api/applicant_counts.php
   Returns: { success, counts: { "Software Developer": 3, ... } }
========================================================== */

require "config.php";

$stmt = $pdo->query("SELECT position, COUNT(*) AS total FROM applicants GROUP BY position");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = [];
foreach ($rows as $row) {
    $counts[$row["position"]] = (int)$row["total"];
}

echo json_encode(["success" => true, "counts" => $counts]);
