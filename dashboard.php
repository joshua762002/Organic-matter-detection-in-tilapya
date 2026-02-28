<?php
session_start();


if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}


$conn = new mysqli("localhost", "root", "", "organic_tilapia");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];
$user = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();


$total = $conn->query("SELECT COUNT(*) as total FROM detections")->fetch_assoc()['total'];

$high = $conn->query("SELECT COUNT(*) as high FROM detections 
                      WHERE status='High Organic Matter'")
              ->fetch_assoc()['high'];

$latest = $conn->query("SELECT * FROM detections ORDER BY detected_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard - Organic Matter Detection</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background:#f4f9f9;">


<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">
<span class="navbar-brand">
Tilapia Organic Matter Detection
</span>

<div>
<span class="text-white me-3">
Welcome, <?php echo $user['full_name']; ?> 
(<?php echo strtoupper($user['role']); ?>)
</span>
<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">


<?php if($user['role'] == 'admin') { ?>

<h2 class="mb-4 text-danger">ADMIN DASHBOARD</h2>

<div class="row mb-4">

<div class="col-md-4">
<div class="card text-white bg-primary shadow">
<div class="card-body text-center">
<h6>Total Samples</h6>
<h2><?php echo $total; ?></h2>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card text-white bg-danger shadow">
<div class="card-body text-center">
<h6>High Organic Matter</h6>
<h2><?php echo $high; ?></h2>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card text-white bg-dark shadow">
<div class="card-body text-center">
<h6>User Management</h6>
<a href="manage_users.php" class="btn btn-light btn-sm mt-2">
Manage Users
</a>
</div>
</div>
</div>

</div>

<?php } else { ?>



<h2 class="mb-4 text-primary">STAFF DASHBOARD</h2>

<div class="row mb-4">

<div class="col-md-6">
<div class="card text-white bg-primary shadow">
<div class="card-body text-center">
<h6>Total Samples</h6>
<h2><?php echo $total; ?></h2>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card text-white bg-danger shadow">
<div class="card-body text-center">
<h6>High Organic Matter</h6>
<h2><?php echo $high; ?></h2>
</div>
</div>
</div>

</div>

<?php } ?>



<div class="card shadow">
<div class="card-header bg-success text-white">
Latest Detection Records
</div>

<div class="card-body">
<table class="table table-hover table-bordered">
<thead>
<tr>
<th>Sample Code</th>
<th>Organic Level</th>
<th>Temp</th>
<th>pH</th>
<th>Status</th>
<th>Date</th>

<?php if ($user['role'] == 'admin') { ?>
<th>Action</th>
<?php } ?>

</tr>
</thead>

<tbody>
<?php while($row = $latest->fetch_assoc()) { ?>
<tr>
<td><?php echo $row['sample_code']; ?></td>
<td><?php echo $row['organic_level']; ?></td>
<td><?php echo $row['water_temperature']; ?></td>
<td><?php echo $row['ph_level']; ?></td>

<td>
<?php if($row['status'] == "High Organic Matter") { ?>
<span class="badge bg-danger"><?php echo $row['status']; ?></span>
<?php } else { ?>
<span class="badge bg-success"><?php echo $row['status']; ?></span>
<?php } ?>
</td>

<td><?php echo $row['detected_at']; ?></td>

<?php if ($user['role'] == 'admin') { ?>
<td>
<a href="delete_detection.php?id=<?php echo $row['detection_id']; ?>" 
   class="btn btn-sm btn-danger">
Delete
</a>
</td>
<?php } ?>

</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

</div>
</body>
</html>