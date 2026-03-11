<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success'=>false, 'message'=>'Not logged in']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");
if ($conn->connect_error) die(json_encode(['success'=>false,'message'=>$conn->connect_error]));

$input = json_decode(file_get_contents('php://input'), true);
if(!$input) { echo json_encode(['success'=>false,'message'=>'No data']); exit(); }

$stmt = $conn->prepare("INSERT INTO notifications (sample_code, pond_name, staff_name, organic_level, status, detected_at) VALUES (?, ?, ?, ?, ?, ?)");

foreach($input as $p){
    $stmt->bind_param("sssiss", $p['sample_code'], $p['pond_name'], $p['full_name'], $p['organic_level'], $p['status'], $p['detected_at']);
    $stmt->execute();
}

$stmt->close();
$conn->close();
echo json_encode(['success'=>true]);
?>