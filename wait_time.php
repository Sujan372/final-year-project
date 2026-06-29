<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ========== DATABASE CONNECTION ==========
$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$userId = $_SESSION['user_id'];

// Fetch saved stations for dropdown (optional)
$stationsQuery = $conn->query("SELECT id, station_name, city FROM saved_stations WHERE user_id = $userId ORDER BY is_favorite DESC");
$userStations = [];
while ($row = $stationsQuery->fetch_assoc()) {
    $userStations[] = $row;
}

// If no saved stations, provide a demo list
if (empty($userStations)) {
    $userStations = [
        ['id' => 0, 'station_name' => 'HP Fuel Station - MG Road', 'city' => 'Bangalore'],
        ['id' => 1, 'station_name' => 'Indian Oil - City Center', 'city' => 'Bangalore'],
        ['id' => 2, 'station_name' => 'CNG Highway Stop', 'city' => 'Mysore'],
    ];
}

// Get selected station (default first)
$selectedStationId = $_POST['station_id'] ?? $userStations[0]['id'];
$selectedStation = null;
foreach ($userStations as $s) {
    if ($s['id'] == $selectedStationId) {
        $selectedStation = $s;
        break;
    }
}
if (!$selectedStation) $selectedStation = $userStations[0];

// Simulate wait time
date_default_timezone_set('Asia/Kolkata');
$currentHour = (int)date('H');
$dayOfWeek = date('N'); // 1=Mon, 7=Sun
$fuelType = $_POST['fuel_type'] ?? 'petrol';

// Base wait times by fuel type (in minutes)
$baseWait = [
    'petrol' => ['min' => 3, 'max' => 8],
    'diesel' => ['min' => 5, 'max' => 12],
    'cng'    => ['min' => 10, 'max' => 25]
];

// Rush hour multiplier (8-10 AM and 5-8 PM on weekdays)
$rushMultiplier = 1.0;
if ($dayOfWeek <= 5) { // weekdays
    if (($currentHour >= 8 && $currentHour <= 10) || ($currentHour >= 17 && $currentHour <= 20)) {
        $rushMultiplier = 1.8;
    }
} else {
    // Weekends: slightly busier mid-day
    if ($currentHour >= 11 && $currentHour <= 14) $rushMultiplier = 1.3;
}

// Calculate estimated wait
$minWait = round($baseWait[$fuelType]['min'] * $rushMultiplier);
$maxWait = round($baseWait[$fuelType]['max'] * $rushMultiplier);
$avgWait = round(($minWait + $maxWait) / 2);

// Friendly message
if ($avgWait <= 5) $crowdLevel = "Low traffic – quick refuel expected!";
elseif ($avgWait <= 12) $crowdLevel = "Moderate traffic – expect a short wait.";
else $crowdLevel = "High traffic – consider visiting at a different time.";

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wait Time Predictor - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: white;
            padding: 30px 20px;
        }
        .container { max-width: 700px; margin: 0 auto; }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }
        .header h1 { font-size: 24px; color: #f97316; }
        .btn {
            background: #1e293b; color: white; padding: 8px 18px;
            border-radius: 8px; text-decoration: none; font-size: 14px;
            border: 1px solid #334155; transition: 0.3s;
        }
        .btn:hover { border-color: #f97316; }
        .card {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 16px; padding: 25px; margin-bottom: 20px;
        }
        label { display: block; margin-bottom: 6px; color: #94a3b8; font-size: 14px; }
        select, input {
            width: 100%; padding: 10px; margin-bottom: 15px;
            background: #0f172a; border: 1px solid #334155;
            border-radius: 8px; color: white; font-size: 14px;
        }
        .result { text-align: center; margin-top: 20px; }
        .wait-time { font-size: 36px; font-weight: 700; color: #f97316; }
        .wait-range { font-size: 18px; color: #94a3b8; margin-top: 5px; }
        .message { margin-top: 15px; font-size: 16px; color: #10b981; }
        .live-indicator {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; margin-bottom: 10px;
        }
        .live-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: #10b981; animation: pulse 1.5s infinite;
        }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        @media (max-width: 500px) { .header { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Wait Time Predictor</h1>
        <div>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="profile.php" class="btn">Profile</a>
        </div>
    </div>

    <div class="card">
        <form method="post">
            <label>Select Station</label>
            <select name="station_id">
                <?php foreach ($userStations as $station): ?>
                    <option value="<?php echo $station['id']; ?>" 
                        <?php if ($station['id'] == $selectedStationId) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($station['station_name']) . ' - ' . htmlspecialchars($station['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Fuel Type</label>
            <select name="fuel_type">
                <option value="petrol" <?php if ($fuelType == 'petrol') echo 'selected'; ?>>Petrol</option>
                <option value="diesel" <?php if ($fuelType == 'diesel') echo 'selected'; ?>>Diesel</option>
                <option value="cng"    <?php if ($fuelType == 'cng') echo 'selected'; ?>>CNG</option>
            </select>

            <button type="submit" class="btn" style="width:100%; background:#f97316; border-color:#f97316; padding:12px;">
                Check Wait Time
            </button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="result">
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span style="color:#94a3b8; font-size:13px;">Live prediction for <?php echo date('l, H:i'); ?></span>
            </div>
            <div class="wait-time">~<?php echo $avgWait; ?> min</div>
            <div class="wait-range">(<?php echo $minWait; ?> – <?php echo $maxWait; ?> min)</div>
            <div class="message"><?php echo $crowdLevel; ?></div>
            <p style="margin-top:15px; font-size:13px; color:#64748b;">
                Based on current time and day. Actual wait may vary.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="card" style="font-size:14px; color:#94a3b8; text-align:center;">
        <strong>How it works:</strong> The predictor uses your current time, day of the week, and fuel type to estimate queue length. CNG pumps naturally take longer, and rush hours (8-10 AM, 5-8 PM) increase waiting time.
    </div>
</div>
<?php include 'theme.php'; ?>
</body>
</html>