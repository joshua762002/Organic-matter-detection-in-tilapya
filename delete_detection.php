<?php
session_start();

// CHECK LOGIN
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// ADMIN ONLY
if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CHECK IF ID EXISTS
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = intval($_GET['id']);

// DELETE RECORD
$stmt = $conn->prepare("DELETE FROM detections WHERE detection_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "Error deleting record.";
}
?>