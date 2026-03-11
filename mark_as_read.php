<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if(!$data || !isset($data['id'])){
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit();
}

$conn = new mysqli("localhost","root","","organic_tilapia");
if($conn->connect_error){
    echo json_encode(['success'=>false,'message'=>$conn->connect_error]);
    exit();
}

$id = (int)$data['id'];
$stmt = $conn->prepare("UPDATE admin_notifications SET is_read=1 WHERE id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>'DB update failed']);
}

$stmt->close();
$conn->close();
?>