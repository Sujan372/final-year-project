<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$userId = $_SESSION['user_id'];

// Fetch saved stations for dropdown (optional)
$stationsStmt = $conn->prepare("SELECT id, station_name, city FROM saved_stations WHERE user_id = ? ORDER BY is_favorite DESC");
$stationsStmt->bind_param("i", $userId);
$stationsStmt->execute();
$stationsResult = $stationsStmt->get_result();
$userStations = [];
while ($row = $stationsResult->fetch_assoc()) {
    $userStations[] = $row;
}
$stationsStmt->close();

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
$dayOfWeek = (int)date('N'); // 1=Mon, 7=Sun
$fuelType = in_array($_POST['fuel_type'] ?? '', ['petrol', 'diesel', 'cng']) ? $_POST['fuel_type'] : 'petrol';

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

$minWait = round($baseWait[$fuelType]['min'] * $rushMultiplier);
$maxWait = round($baseWait[$fuelType]['max'] * $rushMultiplier);
$avgWait = round(($minWait + $maxWait) / 2);

if ($avgWait <= 5) $crowdLevel = "Low traffic - quick refuel expected.";
elseif ($avgWait <= 12) $crowdLevel = "Moderate traffic - expect a short wait.";
else $crowdLevel = "High traffic - consider visiting at a different time.";

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wait Time Predictor - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 32px 20px; color: #0f172a; }
        .container { max-width: 640px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 14px; }
        .header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .header-btns { display: flex; gap: 8px; }
        .btn { background: #ffffff; color: #334155; padding: 9px 16px; border-radius: 9px; text-decoration: none; font-size: 13px; font-weight: 500; border: 1px solid #cbd5e1; transition: border-color 0.15s ease, background 0.15s ease; }
        .btn:hover { border-color: #94a3b8; background: #f8fafc; }

        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 18px; }

        label { display: block; margin-bottom: 6px; color: #334155; font-size: 13px; font-weight: 500; }
        select {
            width: 100%; padding: 11px 13px; margin-bottom: 15px;
            background: #ffffff; border: 1px solid #cbd5e1;
            border-radius: 9px; color: #0f172a; font-size: 13.5px;
            font-family: 'Inter', sans-serif; outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

        .submit-btn {
            width: 100%; padding: 12px; background: #2563eb; border: none;
            color: #ffffff; border-radius: 9px; font-size: 14px; font-weight: 600;
            font-family: 'Inter', sans-serif; cursor: pointer; transition: background 0.15s ease;
        }
        .submit-btn:hover { background: #1d4ed8; }

        .result { text-align: center; margin-top: 22px; padding-top: 22px; border-top: 1px solid #f1f5f9; }
        .wait-time { font-size: 34px; font-weight: 700; color: #2563eb; }
        .wait-range { font-size: 16px; color: #64748b; margin-top: 4px; }
        .message { margin-top: 12px; font-size: 15px; color: #16a34a; font-weight: 500; }
        .footnote { margin-top: 14px; font-size: 12.5px; color: #94a3b8; }

        .live-indicator { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #16a34a; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        .live-label { color: #64748b; font-size: 12.5px; }

        .info-card { font-size: 13.5px; color: #64748b; text-align: center; line-height: 1.6; }
        .info-card strong { color: #334155; }

        @media (max-width: 500px) { .header { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Wait Time Predictor</h1>
        <div class="header-btns">
            <a href="index.php" class="btn">Dashboard</a>
            <a href="profile.php" class="btn">Profile</a>
        </div>
    </div>

    <div class="card">
        <form method="post" action="">
            <label>Select Station</label>
            <select name="station_id">
                <?php foreach ($userStations as $station): ?>
                    <option value="<?php echo htmlspecialchars($station['id']); ?>"
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

            <button type="submit" class="submit-btn">Check Wait Time</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="result">
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span class="live-label">Live prediction for <?php echo date('l, H:i'); ?></span>
            </div>
            <div class="wait-time">~<?php echo $avgWait; ?> min</div>
            <div class="wait-range">(<?php echo $minWait; ?> &ndash; <?php echo $maxWait; ?> min)</div>
            <div class="message"><?php echo htmlspecialchars($crowdLevel); ?></div>
            <p class="footnote">Based on current time and day. Actual wait may vary.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="card info-card">
        <strong>How it works:</strong> The predictor uses your current time, day of the week, and fuel type to estimate queue length. CNG pumps naturally take longer, and rush hours (8-10 AM, 5-8 PM) increase waiting time.
    </div>
</div>
<?php include 'theme.php'; ?>
</body>
</html>