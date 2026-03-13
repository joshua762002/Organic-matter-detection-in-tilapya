<?php
session_start();
$conn = new mysqli("localhost","root","","organic_tilapia");
$user_id = $_SESSION['user_id'];

$sample_code = $_POST['sample_code'];
$organic = floatval($_POST['organic_level']);
$temp = floatval($_POST['water_temperature']);
$ph = floatval($_POST['ph_level']);


if($organic < 50) $status = 'Safe';
elseif($organic < 80) $status = 'Moderate';
else $status = 'High';

$stmtInsert = $conn->prepare("INSERT INTO detections (sample_code, organic_level, water_temperature, ph_level, status, created_by, detected_at) VALUES (?,?,?,?,?,?,NOW())");
$stmtInsert->bind_param("sddssi", $sample_code, $organic, $temp, $ph, $status, $user_id);
$stmtInsert->execute();


$total = $conn->query("SELECT COUNT(*) as total FROM detections WHERE created_by=$user_id")->fetch_assoc()['total'];
$safe = $conn->query("SELECT COUNT(*) as total FROM detections WHERE created_by=$user_id AND status='Safe'")->fetch_assoc()['total'];
$moderate = $conn->query("SELECT COUNT(*) as total FROM detections WHERE created_by=$user_id AND status='Moderate'")->fetch_assoc()['total'];
$high = $conn->query("SELECT COUNT(*) as total FROM detections WHERE created_by=$user_id AND status='High'")->fetch_assoc()['total'];

$statusClass = $status=="High" ? "bg-danger" : ($status=="Moderate" ? "bg-warning" : "bg-success");

echo json_encode([
    'success'=>true,
    'sample_code'=>$sample_code,
    'organic_level'=>$organic,
    'water_temperature'=>$temp,
    'ph_level'=>$ph,
    'status'=>$status,
    'statusClass'=>$statusClass,
    'detected_at'=>date('Y-m-d H:i:s'),
    'safe'=>$safe,
    'moderate'=>$moderate,
    'high'=>$high
]);