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
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$user_id = (int)$_SESSION["user_id"];
$userQuery = $conn->query("SELECT * FROM users WHERE user_id=$user_id");
$user = $userQuery->fetch_assoc();

// Default pond mapping for simulation
$pondMapping = [
    "SAMPLE-101"=>"Pond A",
    "SAMPLE-102"=>"Pond B",
    "SAMPLE-103"=>"Pond C",
    "SAMPLE-104"=>"Pond D",
    "SAMPLE-105"=>"Pond E",
    "SAMPLE-106"=>"Pond F"
];

// Default staff assignment
$defaultStaff = [
    "SAMPLE-103"=>"Juan Dela Cruz",
    "SAMPLE-105"=>"Pedro Reyes"
];

// Simulated latest detections
$latestDetections = [];
foreach($pondMapping as $sample=>$pond){
    $latestDetections[$sample] = [
        'sample_code'=>$sample,
        'pond_name'=>$pond,
        'organic_level'=>rand(0,15),
        'water_temperature'=>rand(24,32),
        'ph_level'=>round(6 + rand(0,20)/10,1),
        'status'=>'Safe',
        'full_name'=>$defaultStaff[$sample] ?? "Staff ".substr($sample,6),
        'detected_at'=>date('Y-m-d H:i:s')
    ];
}

// Add detection
if(isset($_POST['add_detection'])){
    $organic = floatval($_POST['organic_level']);
    $temp = floatval($_POST['temperature']);
    $ph = floatval($_POST['ph']);

    $status = ($organic <= 30) ? "Normal Organic Matter" : (($organic <=70) ? "Moderate Organic Matter" : "High Organic Matter");

    $stmt = $conn->prepare("INSERT INTO detections (sample_code, organic_level, water_temperature, ph_level, status, created_by, detected_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sddssi", $sample = "TEMP", $organic, $temp, $ph, $status, $user_id);
    if($stmt->execute()){
        $detection_id = $conn->insert_id;
        $sample_code = "SAMPLE-".str_pad($detection_id,3,"0",STR_PAD_LEFT);
        $update = $conn->prepare("UPDATE detections SET sample_code=? WHERE detection_id=?");
        $update->bind_param("si",$sample_code,$detection_id);
        $update->execute();
        $success = "Detection record added. Sample Code: $sample_code";
    }
}

// Delete detection
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

// Dashboard stats
$total = $conn->query("SELECT COUNT(*) AS total FROM detections")->fetch_assoc()['total'];
$normal = $conn->query("SELECT COUNT(*) AS total FROM detections WHERE status LIKE '%Normal%'")->fetch_assoc()['total'];
$moderate = $conn->query("SELECT COUNT(*) AS total FROM detections WHERE status LIKE '%Moderate%'")->fetch_assoc()['total'];
$high = $conn->query("SELECT COUNT(*) AS total FROM detections WHERE status LIKE '%High%'")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];

// High alerts
$alerts = $conn->query("SELECT sample_code, organic_level, detected_at FROM detections WHERE status LIKE '%High%' ORDER BY detected_at DESC");

// Latest detections
$latest = $conn->query("SELECT detections.*, users.full_name, users.role FROM detections INNER JOIN users ON detections.created_by = users.user_id ORDER BY detections.detected_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - Organic Matter Detection</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
body{background:#f4f9f9;}
.chart-container{width:260px;margin:auto;}
.alert-container{overflow:hidden;background:#fff5f5;border-radius:6px;padding:10px;}
.alert-slider{display:flex;gap:80px;animation:slideAlerts 45s linear infinite;}
.alert-item{white-space:nowrap;color:#c0392b;font-weight:600;font-size:15px;}
@keyframes slideAlerts{0%{transform:translateX(100%);}100%{transform:translateX(-100%);}}
#map{height:350px;border-radius:6px;}
</style>
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
<div class="container-fluid">
<span class="navbar-brand">ADMIN PANEL - Tilapia Organic Matter Detection</span>
<div>
<span class="text-white me-3">Welcome, <?php echo htmlspecialchars($user['full_name']); ?> (ADMIN)</span>
<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">

<h2 class="mb-4 text-danger">ADMIN DASHBOARD</h2>
<?php if(isset($success)){ ?><div class="alert alert-success"><?php echo $success; ?></div><?php } ?>

<!-- High alerts -->
<div class="card border-danger mb-4 shadow">
<div class="card-header bg-danger text-white">⚠ High Organic Matter Alerts</div>
<div class="card-body"><div class="alert-container"><div class="alert-slider" id="alert-slider">
<?php if($alerts->num_rows>0){ while($alert=$alerts->fetch_assoc()){ ?>
<div class="alert-item">⚠ Sample <b><?php echo $alert['sample_code']; ?></b> High Organic Matter detected (Level: <?php echo $alert['organic_level']; ?>)</div>
<?php }}else{ ?><div class="alert-item">No alerts found</div><?php } ?>
</div></div></div>
</div>

<!-- Dashboard cards -->
<div class="row mb-4">
<div class="col-md-3"><div class="card text-white bg-primary shadow"><div class="card-body text-center"><h6>Total Samples</h6><h2><?php echo $total; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white bg-success shadow"><div class="card-body text-center"><h6>Normal</h6><h2><?php echo $normal; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white" style="background:#f39c12;"><div class="card-body text-center"><h6>Moderate</h6><h2><?php echo $moderate; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white bg-danger shadow"><div class="card-body text-center"><h6>High Organic Matter</h6><h2><?php echo $high; ?></h2></div></div></div>
</div>

<!-- Map + Chart + System Info -->
<div class="row mb-4">
<div class="col-md-6"><div class="card shadow"><div class="card-header bg-dark text-white">Pond Status Map</div><div class="card-body"><div id="map"></div></div></div></div>
<div class="col-md-3"><div class="card shadow"><div class="card-header bg-primary text-white">Organic Matter Distribution</div><div class="card-body text-center"><div class="chart-container"><canvas id="organicChart"></canvas></div></div></div></div>
<div class="col-md-3"><div class="card shadow"><div class="card-header bg-success text-white">System Information</div><div class="card-body text-center"><h5>Total Users</h5><h2><?php echo $totalUsers; ?></h2><a href="manage_users.php" class="btn btn-dark mt-2">Manage Users</a></div></div></div>
</div>

<!-- Detections Table -->
<div class="card shadow mb-5">
<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
<span>All Detection Records</span>
<button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addDetection">Add Detection</button>
</div>
<div class="card-body" style="max-height:450px;overflow-y:auto;">
<table class="table table-hover table-bordered">
<thead>
<tr>
<th>Sample Code</th><th>Organic Level</th><th>Temp</th><th>pH</th><th>Status</th><th>Date</th><th>Created By</th><th>Role</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php if($latest->num_rows>0){ while($row=$latest->fetch_assoc()){ ?>
<tr>
<td><?php echo htmlspecialchars($row['sample_code']); ?></td>
<td><?php echo $row['organic_level']; ?></td>
<td><?php echo $row['water_temperature']; ?></td>
<td><?php echo $row['ph_level']; ?></td>
<td>
<?php if(strpos($row['status'],'High')!==false){ ?><span class="badge bg-danger">High</span>
<?php }elseif(strpos($row['status'],'Moderate')!==false){ ?><span class="badge text-dark" style="background:#f39c12;">Moderate</span>
<?php }else{ ?><span class="badge bg-success">Normal</span><?php } ?>
</td>
<td><?php echo $row['detected_at']; ?></td>
<td><?php echo htmlspecialchars($row['full_name']); ?></td>
<td><?php echo ($row['role']=="admin")?'<span class="badge bg-dark">ADMIN</span>':'<span class="badge bg-primary">STAFF</span>'; ?></td>
<td>
<form method="POST" onsubmit="return confirm('Delete this record?');">
<input type="hidden" name="delete_id" value="<?php echo $row['detection_id']; ?>">
<button class="btn btn-sm btn-danger">Delete</button>
</form>
</td>
</tr>
<?php }}else{ ?>
<tr><td colspan="9" class="text-center text-muted">No detection records found</td></tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

<!-- Add Detection Modal -->
<div class="modal fade" id="addDetection"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header bg-dark text-white"><h5 class="modal-title">Add Detection Record</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<form method="POST">
<div class="modal-body">
<div class="mb-3"><label>Organic Level</label><input type="number" step="0.01" name="organic_level" class="form-control" required></div>
<div class="mb-3"><label>Water Temperature</label><input type="number" step="0.01" name="temperature" class="form-control" required></div>
<div class="mb-3"><label>pH Level</label><input type="number" step="0.01" name="ph" class="form-control" required></div>
</div>
<div class="modal-footer"><button type="submit" name="add_detection" class="btn btn-success">Save Detection</button></div>
</form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart
const ctx = document.getElementById('organicChart');
new Chart(ctx,{type:'doughnut',data:{labels:['Normal','Moderate','High Organic Matter'],datasets:[{data:[<?php echo $normal ?>,<?php echo $moderate ?>,<?php echo $high ?>],backgroundColor:['#2ecc71','#f39c12','#e74c3c'],borderWidth:2}]},options:{responsive:true,plugins:{legend:{position:'bottom'}},cutout:'65%'}});

// Map setup
let ponds = <?php echo json_encode(array_values($latestDetections)); ?>;
const map = L.map('map').setView([8.4828,124.8254],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'OpenStreetMap'}).addTo(map);
let markers={};
function statusColor(status){return status=='High'?'#e74c3c':status=='Moderate'?'#f39c12':'#2ecc71';}
function simulateDetection(p){
    p.organic_level=Math.floor(Math.random()*16);
    p.water_temperature=24+Math.floor(Math.random()*9);
    p.ph_level=(6+Math.random()*2).toFixed(1);
    p.status=p.organic_level>=10?'High':p.organic_level>=5?'Moderate':'Safe';
    p.detected_at=new Date().toLocaleString();
}
function updateMap(){
    ponds.forEach(p=>{
        simulateDetection(p);
        if(markers[p.sample_code]) markers[p.sample_code].setStyle({color:statusColor(p.status),fillColor:statusColor(p.status)});
        else markers[p.sample_code]=L.circleMarker([8.4825+Math.random()*0.001,124.8252+Math.random()*0.001],{radius:10,color:statusColor(p.status),fillColor:statusColor(p.status),fillOpacity:0.8}).addTo(map);
        markers[p.sample_code].bindPopup(`<b>${p.pond_name}</b><br>Sample: ${p.sample_code}<br>Status: ${p.status}`);
    });
    let alertHTML='';
    ponds.forEach(p=>{if(p.status=='High') alertHTML+=`<div class="alert-item">⚠ Sample <b>${p.sample_code}</b> from <b>${p.pond_name}</b> detected HIGH Organic Matter (Level: ${p.organic_level})</div>`;});
    document.getElementById('alert-slider').innerHTML=alertHTML||'<div class="alert-item">No alerts</div>';
}
updateMap();
setInterval(updateMap,300000);
</script>

<div style="height:40px;"></div>
</body>
</html>