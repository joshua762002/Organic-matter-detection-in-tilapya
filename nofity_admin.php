<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit();
}

$conn = new mysqli("localhost","root","","organic_tilapia");
if($conn->connect_error){
    echo json_encode(['status'=>'error','message'=>$conn->connect_error]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if(!$data){
    echo json_encode(['status'=>'error','message'=>'No data received']);
    exit();
}

// If using highPonds key from JS
$highPonds = $data['highPonds'] ?? $data;

// Map sample codes to pond IDs (adjust based on your table)
$pondMappingIDs = [
    "SAMPLE-101"=>1,
    "SAMPLE-102"=>2,
    "SAMPLE-103"=>3,
    "SAMPLE-104"=>4,
    "SAMPLE-105"=>5,
    "SAMPLE-106"=>6
];

// Prepare statement for admin_notifications
$stmt = $conn->prepare("INSERT INTO admin_notifications 
    (pond_id, sample_code, organic_level, water_temperature, ph_level, status, created_by, detected_at)
    VALUES (?,?,?,?,?,?,?,?)"
);

foreach($highPonds as $p){
    $pond_id = $pondMappingIDs[$p['sample_code']] ?? null;
    if(!$pond_id) continue;

    // Bind params: i=integer, s=string, d=double
    $stmt->bind_param(
        "isddsssi", 
        $pond_id,
        $p['sample_code'],
        $p['organic_level'],
        $p['water_temperature'],
        $p['ph_level'],
        $p['status'],
        $_SESSION['user_id'],
        $p['detected_at']  // should be string
    );

    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success'=>true]);
?>