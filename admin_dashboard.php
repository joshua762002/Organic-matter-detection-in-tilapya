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

$user_id = (int)$_SESSION["user_id"];

$userQuery = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();


if(isset($_POST['delete_id'])){

    $delete_id = intval($_POST['delete_id']);

    
    $stmt1 = $conn->prepare("DELETE FROM alerts WHERE detection_id=?");
    $stmt1->bind_param("i",$delete_id);
    $stmt1->execute();

    
    $stmt2 = $conn->prepare("DELETE FROM detections WHERE detection_id=?");
    $stmt2->bind_param("i",$delete_id);
    $stmt2->execute();

    $success = "Detection deleted successfully.";
}



$total = $conn->query("SELECT COUNT(*) as total FROM detections")->fetch_assoc()['total'];

$normal = $conn->query("SELECT COUNT(*) as total FROM detections WHERE status LIKE '%Normal%'")->fetch_assoc()['total'];

$moderate = $conn->query("SELECT COUNT(*) as total FROM detections WHERE status LIKE '%Moderate%'")->fetch_assoc()['total'];

$high = $conn->query("SELECT COUNT(*) as total FROM detections WHERE status LIKE '%High%'")->fetch_assoc()['total'];

$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];



$alerts = $conn->query("
SELECT sample_code, organic_level, detected_at
FROM detections
WHERE status LIKE '%High%'
ORDER BY detected_at DESC
");



$latest = $conn->query("
SELECT detections.*, users.full_name, users.role
FROM detections
INNER JOIN users ON detections.created_by = users.user_id
ORDER BY detections.detected_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard - Organic Matter Detection</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
background:#f4f9f9;
}

.chart-container{
width:260px;
margin:auto;
}



.alert-container{
overflow:hidden;
background:#fff5f5;
border-radius:6px;
padding:10px;
}

.alert-slider{
display:flex;
gap:80px;
animation:slideAlerts 45s linear infinite;
}

.alert-item{
white-space:nowrap;
color:#c0392b;
font-weight:600;
font-size:15px;
}

@keyframes slideAlerts{

0%{
transform:translateX(100%);
}

100%{
transform:translateX(-100%);
}

}

</style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">

<span class="navbar-brand">
ADMIN PANEL - Tilapia Organic Matter Detection
</span>

<div>

<span class="text-white me-3">
Welcome, <?php echo htmlspecialchars($user['full_name']); ?> (ADMIN)
</span>

<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>

</div>

</div>
</nav>


<div class="container mt-4">

<h2 class="mb-4 text-danger">ADMIN DASHBOARD</h2>

<?php if(isset($success)){ ?>

<div class="alert alert-success">
<?php echo $success; ?>
</div>

<?php } ?>




<div class="card border-danger mb-4 shadow">

<div class="card-header bg-danger text-white">
⚠ High Organic Matter Alerts
</div>

<div class="card-body">

<div class="alert-container">

<div class="alert-slider">

<?php if($alerts->num_rows > 0){ ?>

<?php while($alert = $alerts->fetch_assoc()){ ?>

<div class="alert-item">

⚠ Sample <b><?php echo $alert['sample_code']; ?></b> 
High Organic Matter detected (Level: <?php echo $alert['organic_level']; ?>)

</div>

<?php } ?>

<?php }else{ ?>

<div class="alert-item">
No alerts found
</div>

<?php } ?>

</div>

</div>

</div>

</div>


<div class="row mb-4">

<div class="col-md-3">
<div class="card text-white bg-primary shadow">
<div class="card-body text-center">
<h6>Total Samples</h6>
<h2><?php echo $total; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-white bg-success shadow">
<div class="card-body text-center">
<h6>Normal</h6>
<h2><?php echo $normal; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-white" style="background:#f39c12;">
<div class="card-body text-center">
<h6>Moderate</h6>
<h2><?php echo $moderate; ?></h2>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card text-white bg-danger shadow">
<div class="card-body text-center">
<h6>High Organic Matter</h6>
<h2><?php echo $high; ?></h2>
</div>
</div>
</div>

</div>



<div class="row mb-4">

<div class="col-md-6">

<div class="card shadow">
<div class="card-header bg-dark text-white">
Organic Matter Statistics
</div>

<div class="card-body text-center">

<div class="chart-container">
<canvas id="organicChart"></canvas>
</div>

</div>
</div>

</div>


<div class="col-md-6">

<div class="card shadow">

<div class="card-header bg-primary text-white">
System Information
</div>

<div class="card-body text-center">

<h5>Total Users</h5>
<h2><?php echo $totalUsers; ?></h2>

<a href="manage_users.php" class="btn btn-dark mt-2">
Manage Users
</a>

</div>

</div>

</div>

</div>



<div class="card shadow">

<div class="card-header bg-success text-white">
All Detection Records
</div>

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
<th>Created By</th>
<th>Role</th>
<th>Action</th>
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

<?php if(strpos($row['status'],'High') !== false){ ?>

<span class="badge bg-danger">High</span>

<?php }elseif(strpos($row['status'],'Moderate') !== false){ ?>

<span class="badge text-dark" style="background:#f39c12;">Moderate</span>

<?php }else{ ?>

<span class="badge bg-success">Normal</span>

<?php } ?>

</td>

<td><?php echo $row['detected_at']; ?></td>

<td><?php echo htmlspecialchars($row['full_name']); ?></td>

<td>

<?php if($row['role']=="admin"){ ?>

<span class="badge bg-dark">ADMIN</span>

<?php }else{ ?>

<span class="badge bg-primary">STAFF</span>

<?php } ?>

</td>

<td>

<form method="POST" onsubmit="return confirm('Delete this record?');">

<input type="hidden" name="delete_id" value="<?php echo $row['detection_id']; ?>">

<button class="btn btn-sm btn-danger">
Delete
</button>

</form>

</td>

</tr>

<?php } ?>

<?php }else{ ?>

<tr>
<td colspan="9" class="text-center text-muted">
No detection records found
</td>
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
labels:['Normal','Moderate','High Organic Matter'],

datasets:[{
data:[
<?php echo $normal ?>,
<?php echo $moderate ?>,
<?php echo $high ?>
],

backgroundColor:[
'#2ecc71',
'#f39c12',
'#e74c3c'
],

borderWidth:2
}]
},

options:{
responsive:true,
plugins:{
legend:{
position:'bottom'
}
},
cutout:'65%'
}

});

</script>

<div style="height:40px;"></div>

</body>
</html>