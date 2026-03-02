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

    // ✅ AUTO GENERATE SAMPLE CODE (SAMPLE-001, SAMPLE-002...)
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

    $stmt->bind_param("sdddsi", 
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
<title>Add Detection</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
<div class="card shadow">
<div class="card-header bg-success text-white">
Add Detection Record
</div>

<div class="card-body">

<?php if($success != "") { ?>
<div class="alert alert-success">
<?php echo $success; ?>
</div>
<?php } ?>

<form method="POST">

<!-- ❌ TINANGGAL NA SAMPLE CODE INPUT (AUTO NA) -->

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