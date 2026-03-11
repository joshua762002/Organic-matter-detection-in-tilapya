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

$user_id = $_SESSION["user_id"];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle simulation form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_detection'])) {
    $sample_code = $_POST['sample_code'];
    $organic = floatval($_POST['organic_level']);
    $temp = floatval($_POST['water_temperature']);
    $ph = floatval($_POST['ph_level']);

    // Determine status
    if($organic < 50) $status = 'Safe';
    elseif($organic < 80) $status = 'Moderate';
    else $status = 'High';

    $stmtInsert = $conn->prepare("INSERT INTO detections (sample_code, organic_level, water_temperature, ph_level, status, created_by, detected_at) VALUES (?,?,?,?,?,?,NOW())");
    $stmtInsert->bind_param("sddssi", $sample_code, $organic, $temp, $ph, $status, $user_id);
    $stmtInsert->execute();
}

// Refresh stats after insertion
$stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM detections WHERE created_by=?");
$stmtTotal->bind_param("i",$user_id);
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'];

$stmtSafe = $conn->prepare("SELECT COUNT(*) as total FROM detections WHERE status='Safe' AND created_by=?");
$stmtSafe->bind_param("i",$user_id);
$stmtSafe->execute();
$safe = $stmtSafe->get_result()->fetch_assoc()['total'];

$stmtModerate = $conn->prepare("SELECT COUNT(*) as total FROM detections WHERE status='Moderate' AND created_by=?");
$stmtModerate->bind_param("i",$user_id);
$stmtModerate->execute();
$moderate = $stmtModerate->get_result()->fetch_assoc()['total'];

$stmtHigh = $conn->prepare("SELECT COUNT(*) as total FROM detections WHERE status='High' AND created_by=?");
$stmtHigh->bind_param("i",$user_id);
$stmtHigh->execute();
$high = $stmtHigh->get_result()->fetch_assoc()['total'];

// Latest detections
$stmtLatest = $conn->prepare("SELECT * FROM detections WHERE created_by=? ORDER BY detected_at DESC");
$stmtLatest->bind_param("i",$user_id);
$stmtLatest->execute();
$latest = $stmtLatest->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Staff Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{ background:#f4f9f9; }
.chart-container{ width:260px; margin:auto; }
</style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">
<span class="navbar-brand">Tilapia Organic Matter Detection</span>
<div>
<span class="text-white me-3">
Welcome, <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo ucfirst($user['role']); ?>)
</span>
<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">
<h2 class="mb-4 text-primary">STAFF DASHBOARD (Simulation Mode)</h2>

<div class="row mb-4">
<div class="col-md-3">
<div class="card text-white bg-primary shadow">
<div class="card-body text-center">
<h6>Total Samples</h6>
<h2><?php echo $total ?></h2>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white bg-success shadow">
<div class="card-body text-center">
<h6>Safe</h6>
<h2><?php echo $safe ?></h2>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white shadow" style="background:#f39c12;">
<div class="card-body text-center">
<h6>Moderate</h6>
<h2><?php echo $moderate ?></h2>
</div>
</div>
</div>
<div class="col-md-3">
<div class="card text-white bg-danger shadow">
<div class="card-body text-center">
<h6>High</h6>
<h2><?php echo $high ?></h2>
</div>
</div>
</div>
</div>

<div class="row mb-4">
<div class="col-md-6">
<div class="card shadow">
<div class="card-header bg-dark text-white">Organic Matter Statistics</div>
<div class="card-body text-center">
<div class="chart-container">
<canvas id="organicChart"></canvas>
</div>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card shadow">
<div class="card-header bg-primary text-white">Simulate Detection</div>
<div class="card-body">
<form method="POST">
<div class="mb-2">
<input type="text" name="sample_code" class="form-control" placeholder="Sample Code" required>
</div>
<div class="mb-2">
<input type="number" name="organic_level" class="form-control" placeholder="Organic Level" required>
</div>
<div class="mb-2">
<input type="number" name="water_temperature" class="form-control" placeholder="Water Temp °C" required>
</div>
<div class="mb-2">
<input type="number" step="0.1" name="ph_level" class="form-control" placeholder="pH Level" required>
</div>
<button type="submit" name="simulate_detection" class="btn btn-success w-100">Add Simulated Detection</button>
</form>
</div>
</div>
</div>
</div>

<div class="card shadow">
<div class="card-header bg-success text-white">Your Detection Records</div>
<div class="card-body" style="max-height:450px; overflow-y:auto;">
<table class="table table-hover table-bordered">
<thead>
<tr>
<th>Sample Code</th>
<th>Organic Level</th>
<th>Temp</th>
<th>pH</th>
<th>Status</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php if($latest->num_rows > 0){ ?>
<?php while($row = $latest->fetch_assoc()){ ?>
<tr>
<td><?php echo htmlspecialchars($row['sample_code']); ?></td>
<td><?php echo $row['organic_level']; ?></td>
<td><?php echo $row['water_temperature']; ?></td>
<td><?php echo $row['ph_level']; ?></td>
<td>
<?php if($row['status']=="High"){ ?>
<span class="badge bg-danger">High</span>
<?php }elseif($row['status']=="Moderate"){ ?>
<span class="badge" style="background:#f39c12;">Moderate</span>
<?php }else{ ?>
<span class="badge bg-success">Safe</span>
<?php } ?>
</td>
<td><?php echo $row['detected_at']; ?></td>
</tr>
<?php } ?>
<?php }else{ ?>
<tr>
<td colspan="6" class="text-center text-muted">No records found</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

</div>

<script>
const ctx = document.getElementById('organicChart');
new Chart(ctx, {
type:'doughnut',
data:{
labels:['Safe','Moderate','High'],
datasets:[{
data:[<?php echo $safe ?>,<?php echo $moderate ?>,<?php echo $high ?>],
backgroundColor:['#2ecc71','#f39c12','#e74c3c'],
borderWidth:2
}]
},
options:{
responsive:true,
plugins:{legend:{position:'bottom'}},
cutout:'65%'
}
});
</script>

<div style="height:40px;"></div>
<footer></footer>
</body>
</html>