<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";

$result = $conn->query("SELECT MAX(detection_id) AS last_id FROM detections");
$row = $result->fetch_assoc();
$next_id = ($row['last_id'] ?? 0) + 1;
$next_sample_code = "SAMPLE-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $organic_level = $_POST["organic_level"];
    $temp = $_POST["temp"];
    $ph = $_POST["ph"];
    $created_by = $_SESSION["user_id"];

    if ($organic_level < 3) {
        $status = "Normal";
    } elseif ($organic_level >= 3 && $organic_level < 6) {
        $status = "Moderate";
    } else {
        $status = "High Organic Matter";
    }

    if ($temp >= 24 && $temp <= 28) {
        $temp_indicator = "Optimal";
    } elseif ($temp < 24 && $temp >= 20) {
        $temp_indicator = "Slightly Low";
    } elseif ($temp > 28 && $temp <= 32) {
        $temp_indicator = "Slightly High";
    } else {
        $temp_indicator = "Critical";
    }

    if ($ph >= 6.5 && $ph <= 8.5) {
        $ph_indicator = "Normal";
    } elseif ($ph < 6.5 && $ph >= 5.5) {
        $ph_indicator = "Acidic";
    } elseif ($ph > 8.5 && $ph <= 9.5) {
        $ph_indicator = "Alkaline";
    } else {
        $ph_indicator = "Dangerous";
    }

    
    $sample_code = "TEMP";

    $stmt = $conn->prepare("INSERT INTO detections 
    (sample_code, organic_level, water_temperature, ph_level, status, detected_at, created_by)
    VALUES (?, ?, ?, ?, ?, NOW(), ?)");

    $stmt->bind_param(
        "sdddsi",
        $sample_code,
        $organic_level,
        $temp,
        $ph,
        $status,
        $created_by
    );

    if ($stmt->execute()) {

        
        $detection_id = $conn->insert_id;

        
        $sample_code = "SAMPLE-" . str_pad($detection_id, 3, "0", STR_PAD_LEFT);

        
        $conn->query("UPDATE detections 
                      SET sample_code='$sample_code' 
                      WHERE detection_id=$detection_id");

        if ($status == "High Organic Matter") {

            $alert_message = "High Organic Matter detected in $sample_code. Immediate water monitoring recommended.";

            $stmt2 = $conn->prepare("INSERT INTO alerts (detection_id, alert_message, alert_level)
            VALUES (?, ?, 'High')");

            $stmt2->bind_param("is", $detection_id, $alert_message);
            $stmt2->execute();
        }

        if ($status == "Moderate") {

            $alert_message = "Moderate Organic Matter detected in $sample_code. Monitoring recommended.";

            $stmt3 = $conn->prepare("INSERT INTO alerts (detection_id, alert_message, alert_level)
            VALUES (?, ?, 'Moderate')");

            $stmt3->bind_param("is", $detection_id, $alert_message);
            $stmt3->execute();
        }

        $success = "Detection saved! Sample Code: $sample_code 
        | Temp Status: $temp_indicator 
        | pH Status: $ph_indicator";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Add Detection - TilapiaDetect</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
background:linear-gradient(135deg,#eef7ff,#f7fbff);
min-height:100vh;
}

.navbar-custom{
background-color:#1f2937;
}

.navbar-custom .navbar-brand{
color:white;
font-weight:bold;
}

.card-custom{
background:white;
border-radius:12px;
}
</style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom px-3">

<a class="navbar-brand fw-normal ms-0" href="#">
Tilapia Organic Matter Detection
</a>

<div class="ms-auto me-3">
<a href="staff_dashboard.php" class="btn btn-light btn-sm">
⬅ Back to Dashboard
</a>
</div>

</nav>

<div class="container mt-5">

<div class="card shadow card-custom">

<div class="card-header bg-success text-white">
Add Detection Record
</div>

<div class="card-body">

<?php if(!empty($success)){ ?>

<div class="alert alert-success">
<?php echo $success; ?>
</div>

<?php } ?>

<form method="POST">

<div class="mb-3">
<label class="form-label">Sample Code</label>
<input type="text" class="form-control" value="<?php echo $next_sample_code; ?>" readonly>
</div>

<div class="mb-3">
<label class="form-label">Organic Level</label>
<input type="number" step="0.01" name="organic_level" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Water Temperature</label>
<input type="number" step="0.01" name="temp" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">pH Level</label>
<input type="number" step="0.01" name="ph" class="form-control" required>
</div>

<button type="submit" class="btn btn-success">Save Detection</button>
<a href="staff_dashboard.php" class="btn btn-secondary">Back</a>

</form>

</div>
</div>
</div>

</body>
</html>