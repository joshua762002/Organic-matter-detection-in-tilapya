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

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $result = $conn->query("SELECT MAX(detection_id) AS last_id FROM detections");
    $row = $result->fetch_assoc();
    $next_id = ($row['last_id'] ?? 0) + 1;

    $sample_code = "SAMPLE-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

    $organic_level = $_POST["organic_level"];
    $temp = $_POST["temp"];
    $ph = $_POST["ph"];
    $status = $_POST["status"];
    $created_by = $_SESSION["user_id"];

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
        $success = "Detection record added successfully! Sample Code: " . $sample_code;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Detection - TilapiaDetect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #ffffff, #ffffff);
    min-height: 100vh;
}
.navbar-custom {
    background-color: #202020;
}
.navbar-custom .navbar-brand {
    color: white;
    font-weight: bold;
}
.card-custom {
    background-color: #fcffff;
    border-radius: 12px;
}
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom px-3">

    <a class="navbar-brand fw-normal ms-0" href="#">
        Tilapia Organic Matter Detection
    </a>

    <div class="ms-auto me-3">
        <a href="dashboard.php" class="btn btn-light btn-sm">
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

<?php if(!empty($success)) { ?>
<div class="alert alert-success">
 <?php echo $success; ?>
</div>
<?php } ?>

<form method="POST">

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

<div class="mb-3">
<label class="form-label">Status</label>
<select name="status" class="form-select" required>
<option value="Normal">Normal</option>
<option value="Moderate">Moderate</option>
<option value="High Organic Matter">High Organic Matter</option>
</select>
</div>

<button type="submit" class="btn btn-success">Save</button>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

</form>
</div>
</div>
</div>

</body>
</html>