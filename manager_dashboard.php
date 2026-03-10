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

$user_id = (int)$_SESSION["user_id"];
$userQuery = $conn->query("SELECT * FROM users WHERE user_id = $user_id");
$user = $userQuery->fetch_assoc();

// Pond mapping
$pondMapping = [
    "SAMPLE-101" => "Pond A",
    "SAMPLE-102" => "Pond B",
    "SAMPLE-103" => "Pond C",
    "SAMPLE-104" => "Pond D",
    "SAMPLE-105" => "Pond E",
    "SAMPLE-106" => "Pond F"
];

// Default staff
$defaultStaff = [
    "SAMPLE-103" => "Juan Dela Cruz",
    "SAMPLE-105" => "Pedro Reyes"
];

// Initial simulation data
$latestDetections = [];
foreach ($pondMapping as $sample => $pond) {
    $latestDetections[$sample] = [
        'sample_code' => $sample,
        'pond_name' => $pond,
        'organic_level' => rand(0, 15),
        'water_temperature' => rand(24, 32),
        'ph_level' => round(6 + rand(0, 20)/10, 1),
        'status' => 'Safe',
        'full_name' => $defaultStaff[$sample] ?? "Staff " . substr($sample, 6),
        'detected_at' => date('Y-m-d H:i:s')
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>IoT Simulator - Tilapia Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body { background:#f4f9f9; }
        #map { height:350px; border-radius:6px; }
        .chart-container { width:260px; margin:auto; }
        .alert-container { overflow:hidden; background:#fff5f5; border-radius:6px; padding:10px; }
        .alert-slider { display:flex; gap:80px; animation:slideAlerts 30s linear infinite; }
        .alert-item { white-space:nowrap; color:#c0392b; font-weight:600; font-size:15px; }
        @keyframes slideAlerts { 0%{transform:translateX(100%);} 100%{transform:translateX(-100%);} }

        /* Notify Admin button style */
        #notify-admin { margin-bottom: 15px; }

        /* Toast styles */
        #toast-container { position:fixed; top:20px; right:20px; z-index:9999; }
        .toast-professional { background:#fff; border-left:5px solid #e74c3c; padding:15px 20px; margin-bottom:10px; box-shadow:0 4px 8px rgba(0,0,0,0.2); border-radius:6px; font-family:monospace; opacity:0; transform:translateY(-20px); transition:0.5s; }
        .toast-professional.show { opacity:1; transform:translateY(0); }
        .toast-professional.high { border-left-color:#e74c3c; }
        .toast-professional.safe { border-left-color:#2ecc71; }
        .toast-professional .header { font-weight:bold; margin-bottom:8px; }
        .toast-professional .line { margin:2px 0; }
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

    <!-- Alerts -->
    <div class="card border-danger mb-4 shadow">
        <div class="card-header bg-danger text-white">⚠ High Organic Matter Alerts</div>
        <div class="card-body">
            <div class="alert-container">
                <div class="alert-slider" id="alert-slider"></div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-3"><div class="card text-white bg-primary shadow"><div class="card-body text-center"><h6>Total Samples</h6><h2 id="total-samples">6</h2></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-success shadow"><div class="card-body text-center"><h6>Safe</h6><h2 id="safe-count">0</h2></div></div></div>
        <div class="col-md-3"><div class="card text-white" style="background:#f39c12;"><div class="card-body text-center"><h6>Moderate</h6><h2 id="moderate-count">0</h2></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-danger shadow"><div class="card-body text-center"><h6>High</h6><h2 id="high-count">0</h2></div></div></div>
    </div>

    <!-- Map + Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">Pond Status Map</div>
                <div class="card-body"><div id="map"></div></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">Organic Matter Distribution</div>
                <div class="card-body text-center">
                    <div class="chart-container"><canvas id="organicChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table + Notify Button -->
    <div class="card shadow mb-5">
        <div class="card-header bg-success text-white">Recent Detections (Simulation)</div>
        <div class="card-body" style="max-height:450px; overflow-y:auto;">
            <button id="notify-admin" class="btn btn-warning">Notify Admin of High Levels</button>
            <table class="table table-hover table-bordered mt-2" id="detections-table">
                <thead>
                    <tr>
                        <th>Staff Name</th>
                        <th>Pond</th>
                        <th>Sample Code</th>
                        <th>Organic Level</th>
                        <th>Temp</th>
                        <th>pH</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container"></div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let ponds = <?php echo json_encode(array_values($latestDetections)); ?>;

// Map
const map = L.map('map').setView([8.4828,124.8254],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{ attribution:'OpenStreetMap' }).addTo(map);
let markers = {};

// Chart
const ctx = document.getElementById('organicChart');
let organicChart = new Chart(ctx, {
    type:'doughnut',
    data:{ labels:['Safe','Moderate','High'], datasets:[{data:[0,0,0], backgroundColor:['#2ecc71','#f39c12','#e74c3c']}] },
    options:{ plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
});

// Status color
function statusColor(status){
    if(status=="Safe") return '#2ecc71';
    if(status=="Moderate") return '#f39c12';
    if(status=="High") return '#e74c3c';
    return '#95a5a6';
}

// Simulate detection
function simulateDetection(p){
    p.organic_level = Math.floor(Math.random()*16);
    p.water_temperature = 24 + Math.floor(Math.random()*9);
    p.ph_level = (6 + Math.random()*2).toFixed(1);
    if(p.organic_level >= 10) p.status="High";
    else if(p.organic_level >=5) p.status="Moderate";
    else p.status="Safe";
    p.detected_at = new Date().toLocaleString();
}

// Update dashboard
function updateDashboard(){
    let safe=0, moderate=0, high=0;
    let alertHTML="", tableHTML="";
    ponds.forEach(p=>{
        simulateDetection(p);
        if(p.status=="High") high++;
        else if(p.status=="Moderate") moderate++;
        else safe++;

        if(p.status=="High") alertHTML += `<div class="alert-item">⚠ <b>${p.sample_code}</b> from <b>${p.pond_name}</b> detected HIGH Organic Matter (Level: ${p.organic_level})</div>`;

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
        else markers[p.sample_code] = L.circleMarker([8.4825 + Math.random()*0.001,124.8252 + Math.random()*0.001], { radius:10, color:statusColor(p.status), fillColor:statusColor(p.status), fillOpacity:0.8 }).addTo(map);

        markers[p.sample_code].bindPopup(`<b>${p.pond_name}</b><br>Sample: ${p.sample_code}<br>Status: ${p.status}`);
    });
    document.getElementById('alert-slider').innerHTML = alertHTML || '<div class="alert-item">No alerts</div>';
    document.getElementById('detections-table').querySelector('tbody').innerHTML = tableHTML;
    document.getElementById('safe-count').innerText = safe;
    document.getElementById('moderate-count').innerText = moderate;
    document.getElementById('high-count').innerText = high;
    organicChart.data.datasets[0].data = [safe, moderate, high];
    organicChart.update();
}

// Toast notification
function showToast(lines, type="high"){
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast-professional', type, 'show');

    const header = document.createElement('div');
    header.classList.add('header');
    header.innerText = "High Organic Level Alert";
    toast.appendChild(header);

    lines.forEach(line=>{
        const div = document.createElement('div');
        div.classList.add('line');
        div.innerText = line;
        toast.appendChild(div);
    });

    const footer = document.createElement('div');
    footer.classList.add('line');
    footer.style.fontStyle = "italic";
    footer.style.color = "#555";
    footer.innerText = `Notification sent to Admin | ${new Date().toLocaleTimeString()}`;
    toast.appendChild(footer);

    container.appendChild(toast);

    setTimeout(()=>{
        toast.classList.remove('show');
        setTimeout(()=>container.removeChild(toast), 500);
    }, 6000);
}

// Notify Admin button
document.getElementById('notify-admin').addEventListener('click', ()=>{
    let highPonds = ponds.filter(p=>p.status=="High");
    if(highPonds.length===0){
        showToast(["No high organic levels to notify."], "safe");
        return;
    }
    let messageLines = highPonds.map(p=>`${p.full_name} | ${p.pond_name} | ${p.sample_code} | Level: ${p.organic_level}`);
    showToast(messageLines, "high");
});

// Initial render
updateDashboard();
setInterval(updateDashboard, 300000); // 5 minutes
</script>

</body>
</html>