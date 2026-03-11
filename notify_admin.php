<?php
session_start();
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$conn = new mysqli("localhost","root","","organic_tilapia");
if($conn->connect_error) die(json_encode(['success'=>false,'message'=>$conn->connect_error]));

$data = json_decode(file_get_contents('php://input'), true);

if(!$data || !is_array($data)){
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

$now = date('Y-m-d H:i:s');

foreach($data as $p){
    $full_name = $conn->real_escape_string($p['full_name']);
    $pond_name = $conn->real_escape_string($p['pond_name']);
    $sample_code = $conn->real_escape_string($p['sample_code']);
    $organic_level = (int)$p['organic_level'];
    $status = $conn->real_escape_string($p['status']);

    $conn->query("INSERT INTO admin_notifications 
        (full_name, pond_name, sample_code, organic_level, status, detected_at) 
        VALUES ('$full_name','$pond_name','$sample_code','$organic_level','$status','$now')");
}

echo json_encode(['success'=>true]);
?>