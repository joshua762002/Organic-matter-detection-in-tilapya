<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit();
}

$conn = new mysqli("localhost","root","","organic_tilapia");
if($conn->connect_error) die(json_encode(['success'=>false,'message'=>$conn->connect_error]));

$data = json_decode(file_get_contents('php://input'), true);
if(!$data) die(json_encode(['success'=>false,'message'=>'No data received']));

// Filter only High
$highAlerts = array_filter($data, fn($p)=> $p['status']=="High");

if(empty($highAlerts)) die(json_encode(['success'=>true,'message'=>'No high alerts']));

$stmt = $conn->prepare("INSERT INTO admin_notifications (sample_code, organic_level, water_temperature, ph_level, status, created_by, detected_at) VALUES (?,?,?,?,?,?,?)");

foreach($highAlerts as $p){
    $stmt->bind_param(
        "sddssss",
        $p['sample_code'],
        $p['organic_level'],
        $p['water_temperature'],
        $p['ph_level'],
        $p['status'],
        $_SESSION['user_id'],
        $p['detected_at']
    );
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success'=>true,'message'=>'High alerts sent to admin']);
?>