<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "organic_tilapia");
if ($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$user_id = (int)$_SESSION["user_id"];
$userQuery = $conn->query("SELECT * FROM users WHERE user_id=$user_id");
$user = $userQuery->fetch_assoc();

/* ================================
   MANAGER NOTIFICATION RECEIVER
================================ */
$notifications = $conn->query("
SELECT sample_code, organic_level, water_temperature, ph_level, status, detected_at
FROM admin_notifications
ORDER BY detected_at DESC
LIMIT 10
");

/* ================================
   ORIGINAL SIMULATION CODE
================================ */
$pondMapping = [
    "SAMPLE-101"=>"Pond A",
    "SAMPLE-102"=>"Pond B",
    "SAMPLE-103"=>"Pond C",
    "SAMPLE-104"=>"Pond D",
    "SAMPLE-105"=>"Pond E",
    "SAMPLE-106"=>"Pond F"
];

$defaultStaff = [
    "SAMPLE-103"=>"Juan Dela Cruz",
    "SAMPLE-105"=>"Pedro Reyes"
];

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

$safe=0; $moderate=0; $high=0;
foreach($latestDetections as $p){
    if($p['organic_level']>=10) $high++;
    elseif($p['organic_level']>=5) $moderate++;
    else $safe++;
}

$total=count($latestDetections);
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
<title>IoT Simulation Dashboard - Tilapia</title>
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
<span class="navbar-brand">IoT Simulation Dashboard</span>
<div>
<span class="text-white me-3">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
<a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">
<h2 class="mb-4 text-primary">Tilapia Organic Matter Simulation</h2>

<!-- MANAGER NOTIFICATIONS -->
<div class="card border-warning mb-4 shadow">
<div class="card-header bg-warning text-dark">Manager Notifications</div>
<div class="card-body" style="max-height:250px; overflow-y:auto;">
<table class="table table-bordered table-sm">
<thead>
<tr>
<th>Sample</th>
<th>Organic</th>
<th>Temp</th>
<th>pH</th>
<th>Status</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php
if($notifications && $notifications->num_rows>0){
while($n=$notifications->fetch_assoc()){
?>
<tr>
<td><?php echo $n['sample_code']; ?></td>
<td><?php echo $n['organic_level']; ?></td>
<td><?php echo $n['water_temperature']; ?></td>
<td><?php echo $n['ph_level']; ?></td>
<td><span class="badge bg-danger"><?php echo $n['status']; ?></span></td>
<td><?php echo $n['detected_at']; ?></td>
</tr>
<?php
}
}else{
echo "<tr><td colspan='6'>No notifications yet</td></tr>";
}
?>
</tbody>
</table>
</div>
</div>

<!-- HIGH ALERTS -->
<div class="card border-danger mb-4 shadow">
<div class="card-header bg-danger text-white">⚠ High Organic Matter Alerts</div>
<div class="card-body">
<div class="alert-container">
<div class="alert-slider" id="alert-slider">
<?php
$hasAlert=false;
foreach($latestDetections as $p){
if($p['organic_level']>=10){
echo '<div class="alert-item">⚠ Sample <b>'.$p['sample_code'].'</b> from <b>'.$p['pond_name'].'</b> detected HIGH Organic Matter (Level: '.$p['organic_level'].')</div>';
$hasAlert=true;
}
}
if(!$hasAlert) echo '<div class="alert-item">No alerts found</div>';
?>
</div>
</div>
</div>
</div>

<!-- DASHBOARD CARDS -->
<div class="row mb-4">
<div class="col-md-3"><div class="card text-white bg-primary shadow"><div class="card-body text-center"><h6>Total Samples</h6><h2><?php echo $total; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white bg-success shadow"><div class="card-body text-center"><h6>Safe</h6><h2><?php echo $safe; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white" style="background:#f39c12;"><div class="card-body text-center"><h6>Moderate</h6><h2><?php echo $moderate; ?></h2></div></div></div>
<div class="col-md-3"><div class="card text-white bg-danger shadow"><div class="card-body text-center"><h6>High</h6><h2><?php echo $high; ?></h2></div></div></div>
</div>

<!-- MAP / CHART / SYSTEM -->
<div class="row mb-4">
<div class="col-md-6"><div class="card shadow"><div class="card-header bg-dark text-white">Pond Status Map</div><div class="card-body"><div id="map"></div></div></div></div>
<div class="col-md-3"><div class="card shadow"><div class="card-header bg-primary text-white">Organic Matter Distribution</div><div class="card-body text-center"><div class="chart-container"><canvas id="organicChart"></canvas></div></div></div></div>
<div class="col-md-3"><div class="card shadow"><div class="card-header bg-success text-white">System Information</div><div class="card-body text-center"><h5>Total Users</h5><h2><?php echo $totalUsers; ?></h2><a href="manage_users.php" class="btn btn-dark mt-2">Manage Users</a></div></div></div>
</div>

<!-- DETECTIONS TABLE -->
<div class="card shadow mb-5">
<div class="card-header bg-success text-white">Recent Detections (Simulation)</div>
<div class="card-body" style="max-height:450px;overflow-y:auto;">
<table class="table table-hover table-bordered">
<thead>
<tr>
<th>Staff Name</th><th>Pond</th><th>Sample Code</th><th>Organic Level</th><th>Temp</th><th>pH</th><th>Status</th><th>Date</th>
</tr>
</thead>
<tbody id="detections-tbody"></tbody>
</table>
</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let ponds = <?php echo json_encode(array_values($latestDetections)); ?>;
const map = L.map('map').setView([8.4828,124.8254],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'OpenStreetMap'}).addTo(map);
let markers={};

function statusColor(status){
    if(status=="Safe") return '#2ecc71';
    if(status=="Moderate") return '#f39c12';
    if(status=="High") return '#e74c3c';
    return '#95a5a6';
}

function simulateDetection(p){
    p.organic_level=Math.floor(Math.random()*16);
    p.water_temperature=24+Math.floor(Math.random()*9);
    p.ph_level=(6+Math.random()*2).toFixed(1);
    if(p.organic_level>=10) p.status="High";
    else if(p.organic_level>=5) p.status="Moderate";
    else p.status="Safe";
    p.detected_at=new Date().toLocaleString();
}

const ctx = document.getElementById('organicChart');
let organicChart = new Chart(ctx,{
    type:'doughnut',
    data:{labels:['Safe','Moderate','High'],datasets:[{data:[0,0,0],backgroundColor:['#2ecc71','#f39c12','#e74c3c']}]},
    options:{plugins:{legend:{position:'bottom'}},cutout:'65%'}
});

function updateDashboard(){
    let safe=0, moderate=0, high=0;
    let alertHTML="", tableHTML="";
    ponds.forEach(p=>{
        simulateDetection(p);
        if(p.status=="High") high++;
        else if(p.status=="Moderate") moderate++;
        else safe++;
        if(p.status=="High") alertHTML += `<div class="alert-item">⚠ Sample <b>${p.sample_code}</b> from <b>${p.pond_name}</b> detected HIGH Organic Matter (Level: ${p.organic_level})</div>`;
        let badge = p.status=="High"?'<span class="badge bg-danger">High</span>':
                    p.status=="Moderate"?'<span class="badge text-dark" style="background:#f39c12;">Moderate</span>':
                    '<span class="badge bg-success">Safe</span>';
        tableHTML += `<tr>
            <td>${p.full_name}</td>
            <td>${p.pond_name}</td>
            <td>${p.sample_code}</td>
            <td>${p.organic_level}</td>
            <td>${p.water_temperature}</td>
            <td>${p.ph_level}</td>
            <td>${badge}</td>
            <td>${p.detected_at}</td>
        </tr>`;
        if(markers[p.sample_code]) markers[p.sample_code].setStyle({color:statusColor(p.status), fillColor:statusColor(p.status)});
        else markers[p.sample_code]=L.circleMarker([8.4825+Math.random()*0.001,124.8252+Math.random()*0.001],{radius:10,color:statusColor(p.status),fillColor:statusColor(p.status),fillOpacity:0.8}).addTo(map);
        markers[p.sample_code].bindPopup(`<b>${p.pond_name}</b><br>Sample: ${p.sample_code}<br>Status: ${p.status}`);
    });
    document.getElementById('alert-slider').innerHTML = alertHTML || '<div class="alert-item">No alerts</div>';
    document.getElementById('detections-tbody').innerHTML = tableHTML;
    organicChart.data.datasets[0].data=[safe,moderate,high];
    organicChart.update();
}

updateDashboard();
setInterval(updateDashboard,300000);
</script>

<div style="height:40px;"></div>
</body>
</html>