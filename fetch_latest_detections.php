<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    exit("Unauthorized");
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pondMapping = [
    "SAMPLE-101"=>"Pond A","SAMPLE-102"=>"Pond B","SAMPLE-103"=>"Pond C",
    "SAMPLE-104"=>"Pond D","SAMPLE-105"=>"Pond E","SAMPLE-106"=>"Pond F",
    "SAMPLE-107"=>"Pond G","SAMPLE-108"=>"Pond H","SAMPLE-109"=>"Pond I"
];

$latest = $conn->query("
    SELECT detections.*, users.full_name, users.role 
    FROM detections 
    INNER JOIN users ON detections.created_by = users.user_id 
    WHERE users.role='staff'
    ORDER BY detections.detected_at DESC
");

$rows = [];
while($row = $latest->fetch_assoc()) {
    $row['pond_name'] = $pondMapping[$row['sample_code']] ?? "Unknown";
    $rows[] = $row;
}

echo json_encode($rows);
?>