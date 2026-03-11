<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost","root","","organic_tilapia");
if($conn->connect_error) die(json_encode([]));

$result = $conn->query("
    SELECT an.*, p.pond_name 
    FROM admin_notifications an 
    LEFT JOIN ponds p ON an.pond_id = p.pond_id 
    ORDER BY an.detected_at DESC 
    LIMIT 10
");

$notifications = [];
while($row=$result->fetch_assoc()){
    $notifications[] = [
        'sample_code'=>$row['sample_code'],
        'pond_name'=>$row['pond_name'],
        'status'=>$row['status'],
        'detected_at'=>$row['detected_at']
    ];
}

echo json_encode($notifications);
