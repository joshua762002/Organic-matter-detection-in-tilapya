<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'msg'=>'Invalid request']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'msg'=>'DB connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$manager_id = (int)$input['manager_id'];
$highPonds = $input['highPonds'] ?? [];

if(empty($highPonds)){
    echo json_encode(['success'=>false,'msg'=>'No high data']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO notifications (manager_id, sample_code, pond_name, organic_level) VALUES (?,?,?,?)");

foreach($highPonds as $p){
    $stmt->bind_param("issi", $manager_id, $p['sample_code'], $p['pond_name'], $p['organic_level']);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success'=>true]);