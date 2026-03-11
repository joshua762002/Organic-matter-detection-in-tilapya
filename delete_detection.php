```php
<?php
session_start();


if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}


if ($_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit();
}


$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id = intval($_GET['id']);


$stmt1 = $conn->prepare("DELETE FROM alerts WHERE detection_id = ?");
$stmt1->bind_param("i", $id);
$stmt1->execute();


$stmt2 = $conn->prepare("DELETE FROM detections WHERE detection_id = ?");
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {
    header("Location: admin_dashboard.php?deleted=1");
    exit();
} else {
    echo "Error deleting record.";
}

$conn->close();
?>

