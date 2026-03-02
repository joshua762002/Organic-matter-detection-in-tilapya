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

// Get logged in user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ADMIN sees all records
if ($user['role'] == 'admin') {

    $total = $conn->query("SELECT COUNT(*) as total FROM detections")
                  ->fetch_assoc()['total'];

    $high = $conn->query("SELECT COUNT(*) as high FROM detections 
                          WHERE status='High Organic Matter'")
                  ->fetch_assoc()['high'];

    // ✅ REMOVED LIMIT
    $latest = $conn->query("SELECT * 
                            FROM detections 
                            ORDER BY detected_at DESC");

} else {

    // USER sees only own records
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM detections WHERE created_by = ?");
    $stmtTotal->bind_param("i", $user_id);
    $stmtTotal->execute();
    $total = $stmtTotal->get_result()->fetch_assoc()['total'];

    $stmtHigh = $conn->prepare("SELECT COUNT(*) as high FROM detections 
                                WHERE status='High Organic Matter' 
                                AND created_by = ?");
    $stmtHigh->bind_param("i", $user_id);
    $stmtHigh->execute();
    $high = $stmtHigh->get_result()->fetch_assoc()['high'];

    // ✅ REMOVED LIMIT
    $stmtLatest = $conn->prepare("SELECT * 
                                  FROM detections 
                                  WHERE created_by = ? 
                                  ORDER BY detected_at DESC");
    $stmtLatest->bind_param("i", $user_id);
    $stmtLatest->execute();
    $latest = $stmtLatest->get_result();
}
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
Welcome, <?php echo htmlspecialchars($user['full_name']); ?> 
(<?php echo strtoupper($user['role']); ?>)
</span>
<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">

<?php if($user['role'] == 'admin') { ?>
<h2 class="mb-4 text-danger">ADMIN DASHBOARD</h2>
<?php } else { ?>
<h2 class="mb-4 text-primary">USER DASHBOARD</h2>
<?php } ?>

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

<div class="mb-3">
<a href="add_detection.php" class="btn btn-success">
+ Add New Detection
</a>
</div>

<div class="card shadow">
<div class="card-header bg-success text-white">
Detection Records
</div>

<!-- ✅ SCROLLABLE TABLE -->
<div class="card-body" style="max-height: 400px; overflow-y: auto;">

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

<?php if($latest->num_rows > 0) { ?>
<?php while($row = $latest->fetch_assoc()) { ?>
<tr>
<td><?php echo htmlspecialchars($row['sample_code']); ?></td>
<td><?php echo $row['organic_level']; ?></td>
<td><?php echo $row['water_temperature']; ?></td>
<td><?php echo $row['ph_level']; ?></td>

<td>
<?php if($row['status'] == "High Organic Matter") { ?>
<span class="badge bg-danger"><?php echo $row['status']; ?></span>
<?php } elseif($row['status'] == "Moderate") { ?>
<span class="badge bg-warning text-dark"><?php echo $row['status']; ?></span>
<?php } else { ?>
<span class="badge bg-success"><?php echo $row['status']; ?></span>
<?php } ?>
</td>

<td><?php echo $row['detected_at']; ?></td>

<?php if ($user['role'] == 'admin') { ?>
<td>
<a href="delete_detection.php?id=<?php echo $row['detection_id']; ?>" 
   class="btn btn-sm btn-danger"
   onclick="return confirm('Are you sure you want to delete this record?')">
Delete
</a>
</td>
<?php } ?>

</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="7" class="text-center text-muted">
No detection records found.
</td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>

</div>
</body>
</html>