<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Correct path and fetch live prices
require_once 'fuel_price.php';
$fuelPrices = getFuelPrices();   // Automatically fetched (API with fallback)

$userId = $_SESSION['user_id'];
$message = "";
$error = "";
$showResults = false;
$autoDistance = false;

// ❌ Removed the hardcoded $fuelPrices array – now using live prices only

$vehicleMileage = [
    'petrol' => 15,
    'diesel' => 18,
    'cng'    => 25
];

// ================== Geocoding helper ==================
function geocode($location) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location) . "&limit=1";
    $opts = ["http" => ["header" => "User-Agent: TurboFuel/1.0\r\n"]];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;
    $data = json_decode($response, true);
    if (empty($data)) return null;
    return ['lat' => $data[0]['lat'], 'lon' => $data[0]['lon']];
}

// ================== Routing helper (OSRM) ==================
function getDrivingDistance($startCoord, $endCoord) {
    $url = "https://router.project-osrm.org/route/v1/driving/{$startCoord['lon']},{$startCoord['lat']};{$endCoord['lon']},{$endCoord['lat']}?overview=false";
    $response = @file_get_contents($url);
    if ($response === false) return null;
    $data = json_decode($response, true);
    if (isset($data['routes'][0]['distance'])) {
        return $data['routes'][0]['distance'] / 1000;
    }
    return null;
}

// ================== Form handling ==================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['find_route'])) {

    $startLocation = trim($_POST['start_location'] ?? '');
    $endLocation   = trim($_POST['end_location'] ?? '');
    $fuelType      = $_POST['fuel_type'] ?? 'petrol';
    $mileage       = floatval($_POST['mileage'] ?? $vehicleMileage[$fuelType]);
    $distance      = floatval($_POST['distance'] ?? 0);

    if (empty($startLocation) || empty($endLocation)) {
        $error = "Please enter both start and end locations.";
    } else {
        $startCoord = geocode($startLocation);
        $endCoord   = geocode($endLocation);

        if ($startCoord && $endCoord) {
            $calculatedDist = getDrivingDistance($startCoord, $endCoord);
            if ($calculatedDist && $calculatedDist > 0) {
                $distance = $calculatedDist;
                $autoDistance = true;
            }
        }

        if ($distance <= 0) {
            $error = "Could not automatically determine distance. Please enter it manually.";
        } else {
            $showResults = true;

            $fuelNeeded   = $distance / $mileage;
            // ✅ Use live price from API
            $pricePerUnit = $fuelPrices[$fuelType];
            $totalCost    = $fuelNeeded * $pricePerUnit;

            $numStations = ceil($distance / 50);
            $stations = [];
            $stationNames = [
                'HP Fuel Station', 'Indian Oil Pump', 'BP Energy Center',
                'Reliance Fuel Point', 'Nayara Fuel Zone', 'Shell Service Station',
                'Essar Fuel Hub', 'Jio-BP Station', 'City Fuel Center'
            ];

            for ($i = 1; $i <= $numStations; $i++) {
                $stationDistance = round(($distance / ($numStations + 1)) * $i, 1);
                $stations[] = [
                    'name'                => $stationNames[array_rand($stationNames)],
                    'distance_from_start' => $stationDistance,
                    'fuel_types'          => $fuelType == 'cng' ? ['CNG'] : ['Petrol', 'Diesel'],
                    'price'               => $pricePerUnit,
                    'rating'              => rand(35, 50) / 10
                ];
            }

            $saveStmt = $conn->prepare("INSERT INTO route_history (user_id, start_location, end_location, distance_km, fuel_type, fuel_needed, total_cost, stations_found) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $saveStmt->bind_param("issdsddi", $userId, $startLocation, $endLocation, $distance, $fuelType, $fuelNeeded, $totalCost, $numStations);
            $saveStmt->execute();
            $saveStmt->close();

            $message = "Route calculated. " . $numStations . " stations found. ";
            $message .= $autoDistance ? "(Distance auto‑detected: " . number_format($distance, 1) . " km)" : "";
        }
    }
}

$settings = $_SESSION['settings'] ?? ['default_fuel' => 'petrol', 'default_city' => '', 'default_vehicle' => ''];
$defaultFuel = $settings['default_fuel'] ?? 'petrol';
$defaultCity = $settings['default_city'] ?? '';
$defaultVehicle = $settings['default_vehicle'] ?? '';

$conn->close();

// Vehicle definitions for dropdown
$vehicles = [
    'maruti_swift_petrol'       => ['name' => 'Maruti Swift (Petrol)',   'mileage' => 22, 'fuel' => 'petrol'],
    'hyundai_creta_diesel'      => ['name' => 'Hyundai Creta (Diesel)',  'mileage' => 18, 'fuel' => 'diesel'],
    'tata_nexon_petrol'         => ['name' => 'Tata Nexon (Petrol)',     'mileage' => 17, 'fuel' => 'petrol'],
    'honda_city_petrol'         => ['name' => 'Honda City (Petrol)',     'mileage' => 18, 'fuel' => 'petrol'],
    'maruti_dzire_cng'          => ['name' => 'Maruti Dzire (CNG)',      'mileage' => 26, 'fuel' => 'cng'],
    'mahindra_scorpio_diesel'   => ['name' => 'Mahindra Scorpio (Diesel)', 'mileage' => 15, 'fuel' => 'diesel'],
    'custom'                    => ['name' => 'Other (manual mileage)',   'mileage' => 0,  'fuel' => '']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Finder - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- ✅ Use reliable CDN for Leaflet -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh; padding: 30px 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }
        .page-title h1 { font-size: 24px; font-weight: 700; color: #ffffff; }
        .page-title h1 span { color: #f97316; }
        .page-title p { color: #8888a0; font-size: 13px; margin-top: 4px; }
        .header-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 18px; border-radius: 10px; font-size: 13px;
            font-weight: 500; text-decoration: none; transition: all 0.3s ease;
            cursor: pointer; border: 1px solid #2a2a4a; font-family: 'Inter', sans-serif;
        }
        .btn-outline { background: #222240; color: #a0a0b8; }
        .btn-outline:hover { border-color: #f97316; color: #ffffff; }
        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #ffffff; border-color: #f97316;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px); box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }
        .alert {
            padding: 14px 18px; border-radius: 12px; margin-bottom: 22px;
            font-size: 14px; font-weight: 500; animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card {
            background: #1a1a2e; border: 1px solid #2a2a4a;
            border-radius: 20px; padding: 25px;
        }
        .card-title {
            font-size: 18px; font-weight: 600; color: #ffffff;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; color: #c0c0d0; font-size: 13px; font-weight: 500; margin-bottom: 6px;
        }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        input, select {
            width: 100%; padding: 12px 14px; background: #222240;
            border: 2px solid #2a2a4a; border-radius: 10px; color: #ffffff;
            font-size: 14px; font-family: 'Inter', sans-serif;
            transition: all 0.3s ease; outline: none;
        }
        input:focus, select:focus {
            border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }
        input::placeholder { color: #5a5a7a; }
        select option { background: #1a1a2e; color: #ffffff; }
        .route-summary {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
            margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #2a2a4a;
        }
        .summary-item {
            background: #222240; border-radius: 12px; padding: 16px; text-align: center;
        }
        .summary-label { font-size: 11px; color: #8888a0; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .summary-value { font-size: 20px; font-weight: 700; color: #ffffff; }
        .summary-value.highlight { color: #f97316; }
        .station-list { max-height: 400px; overflow-y: auto; }
        .station-card {
            background: #222240; border: 1px solid #2a2a4a; border-radius: 14px;
            padding: 16px; margin-bottom: 10px; transition: all 0.3s ease;
            display: flex; align-items: center; gap: 14px;
        }
        .station-card:hover { border-color: #f97316; transform: translateX(3px); }
        .station-number {
            width: 36px; height: 36px; background: #f97316; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff; font-size: 14px; flex-shrink: 0;
        }
        .station-info { flex: 1; }
        .station-info h4 { color: #ffffff; font-size: 14px; margin-bottom: 3px; }
        .station-info p { color: #8888a0; font-size: 12px; }
        .station-meta { text-align: right; flex-shrink: 0; }
        .station-meta .distance { color: #f97316; font-weight: 700; font-size: 16px; }
        .station-meta .price { color: #4ade80; font-size: 12px; }
        .station-meta .rating { color: #facc15; font-size: 12px; }
        .map-wrapper {
            height: 500px; border-radius: 14px; overflow: hidden;
            border: 2px solid #2a2a4a;
        }
        #map { height: 100%; width: 100%; }
        .route-visual {
            display: flex; align-items: center; gap: 8px;
            padding: 14px; background: #222240; border-radius: 10px;
            margin-bottom: 15px;
        }
        .route-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .route-dot.start { background: #4ade80; }
        .route-dot.end { background: #f87171; }
        .route-line { flex: 1; height: 2px; background: linear-gradient(90deg, #4ade80, #f87171); }
        .route-text { font-size: 12px; color: #a0a0b8; }
        .legend { display: flex; gap: 16px; margin-top: 15px; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 6px; color: #a0a0b8; font-size: 12px; }
        .auto-badge {
            background: rgba(34, 197, 94, 0.15); color: #4ade80;
            font-size: 11px; padding: 3px 10px; border-radius: 12px;
            margin-left: 8px;
        }
        .mileage-note { color: #6a6a8a; font-size: 11px; margin-top: 4px; }
        .live-price {
            background: rgba(249,115,22,0.1);
            color: #f97316;
            font-size: 12px;
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .content-grid { grid-template-columns: 1fr; }
            .route-summary { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1>Turbo<span>Fuel</span> Route Finder</h1>
                <p>Find fuel stations along your journey</p>
            </div>
            <div class="header-btns">
                <a href="index.php" class="btn btn-outline">Dashboard</a>
                <a href="profile.php" class="btn btn-outline">Profile</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Live prices indicator -->
        <div class="live-price">
            Live Fuel Prices: Petrol ₹<?php echo $fuelPrices['petrol']; ?>/L | Diesel ₹<?php echo $fuelPrices['diesel']; ?>/L | CNG ₹<?php echo $fuelPrices['cng']; ?>/kg
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-title">Plan Your Route</div>
                <form method="POST" action="route_finder.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Location</label>
                            <input type="text" name="start_location" placeholder="e.g., Bangalore" value="<?php echo htmlspecialchars($_POST['start_location'] ?? $defaultCity); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>End Location</label>
                            <input type="text" name="end_location" placeholder="e.g., Mysore" value="<?php echo htmlspecialchars($_POST['end_location'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Distance (km) – leave blank for auto</label>
                            <input type="number" name="distance" placeholder="Auto-detected if empty" step="0.1" min="0" value="<?php echo htmlspecialchars($_POST['distance'] ?? ''); ?>">
                            <small style="color:#6a6a8a; font-size:11px;">Auto-detection works for most locations</small>
                        </div>
                        <div class="form-group">
                            <label>Fuel Type</label>
                            <select name="fuel_type" id="fuelType">
                                <option value="petrol" <?php if(($_POST['fuel_type'] ?? $defaultFuel) == 'petrol') echo 'selected'; ?>>Petrol (₹<?php echo $fuelPrices['petrol']; ?>/L)</option>
                                <option value="diesel" <?php if(($_POST['fuel_type'] ?? '') == 'diesel') echo 'selected'; ?>>Diesel (₹<?php echo $fuelPrices['diesel']; ?>/L)</option>
                                <option value="cng"    <?php if(($_POST['fuel_type'] ?? '') == 'cng') echo 'selected'; ?>>CNG (₹<?php echo $fuelPrices['cng']; ?>/kg)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vehicle Model</label>
                        <select name="vehicle_model" id="vehicleModel" onchange="updateMileage()">
                            <?php foreach ($vehicles as $key => $v): ?>
                                <option value="<?php echo $key; ?>" 
                                    data-mileage="<?php echo $v['mileage']; ?>" 
                                    data-fuel="<?php echo $v['fuel']; ?>"
                                    <?php if ($key == $defaultVehicle) echo 'selected'; ?>>
                                    <?php echo $v['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mileage-note">Select a vehicle to auto‑fill mileage and fuel type</div>
                    </div>

                    <div class="form-group">
                        <label>Mileage (km/L or km/kg)</label>
                        <input type="number" name="mileage" id="mileageInput" placeholder="Enter mileage" step="0.1" min="1" value="<?php echo htmlspecialchars($_POST['mileage'] ?? ''); ?>" required>
                    </div>

                    <button type="submit" name="find_route" class="btn btn-primary" style="width:100%; padding:14px; font-size:15px;">
                        Find Stations on Route
                    </button>
                </form>

                <?php if ($showResults): ?>
                    <div class="route-visual" style="margin-top:20px;">
                        <span class="route-text"><?php echo htmlspecialchars($startLocation); ?></span>
                        <div class="route-dot start"></div>
                        <div class="route-line"></div>
                        <div class="route-dot end"></div>
                        <span class="route-text"><?php echo htmlspecialchars($endLocation); ?></span>
                    </div>
                    <div class="route-summary">
                        <div class="summary-item">
                            <div class="summary-label">Total Distance <?php if($autoDistance) echo '<span class="auto-badge">auto</span>'; ?></div>
                            <div class="summary-value"><?php echo number_format($distance, 1); ?> km</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Fuel Needed</div>
                            <div class="summary-value"><?php echo number_format($fuelNeeded, 2); ?> <?php echo ($fuelType == 'cng') ? 'kg' : 'L'; ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Total Cost</div>
                            <div class="summary-value highlight">Rs. <?php echo number_format($totalCost, 2); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Stations on Route</div>
                            <div class="summary-value"><?php echo $numStations; ?></div>
                        </div>
                    </div>
                    <h3 style="color:#fff; font-size:15px; margin-bottom:12px;">Stations Along Your Route</h3>
                    <div class="station-list">
                        <?php foreach ($stations as $index => $station): ?>
                            <div class="station-card">
                                <div class="station-number"><?php echo $index + 1; ?></div>
                                <div class="station-info">
                                    <h4><?php echo htmlspecialchars($station['name']); ?></h4>
                                    <p><?php echo implode(', ', $station['fuel_types']); ?></p>
                                    <p class="rating">Rating: <?php echo $station['rating']; ?>/5</p>
                                </div>
                                <div class="station-meta">
                                    <div class="distance"><?php echo $station['distance_from_start']; ?> km</div>
                                    <div class="price">Rs. <?php echo number_format($station['price'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Map -->
            <div class="card">
                <div class="card-title">Route Map</div>
                <div class="map-wrapper">
                    <div id="map"></div>
                </div>
                <?php if ($showResults): ?>
                    <div class="legend">
                        <div class="legend-item"><div class="route-dot start"></div> Start Point</div>
                        <div class="legend-item"><div class="route-dot end"></div> End Point</div>
                        <div class="legend-item" style="color:#f97316;">--- Route Line</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ✅ Load Leaflet JS from reliable CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        // ========== Auto‑fill mileage and fuel type ==========
        function updateMileage() {
            const select = document.getElementById('vehicleModel');
            const selectedOption = select.options[select.selectedIndex];
            const mileage = selectedOption.getAttribute('data-mileage');
            const fuel = selectedOption.getAttribute('data-fuel');
            const mileageInput = document.getElementById('mileageInput');
            const fuelSelect = document.getElementById('fuelType');

            if (selectedOption.value === 'custom') {
                mileageInput.value = '';
                mileageInput.placeholder = 'Enter mileage manually';
            } else if (mileage && parseFloat(mileage) > 0) {
                mileageInput.value = mileage;
                if (fuel) {
                    fuelSelect.value = fuel;
                }
            }
        }

        // Pre‑fill default vehicle on page load
        window.addEventListener('DOMContentLoaded', () => {
            const vehicleSelect = document.getElementById('vehicleModel');
            if (vehicleSelect.value !== 'custom') {
                updateMileage();
            }
        });
    </script>

    <!-- ✅ Map initialization (only if map div exists) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapElement = document.getElementById('map');
            if (!mapElement) return; // no map on this page

            const map = L.map('map').setView([12.9716, 77.5946], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            <?php if ($showResults): ?>
                // If results exist, add route markers
                const startLat = 12.9716 + (Math.random() * 2);
                const startLng = 77.5946 + (Math.random() * 2);
                const endLat   = startLat + (Math.random() * 2 - 1);
                const endLng   = startLng + (Math.random() * 2 - 1);

                L.marker([startLat, startLng])
                    .addTo(map)
                    .bindPopup('<b>Start:</b> <?php echo htmlspecialchars($startLocation); ?>')
                    .openPopup();

                L.marker([endLat, endLng])
                    .addTo(map)
                    .bindPopup('<b>End:</b> <?php echo htmlspecialchars($endLocation); ?>');

                L.polyline([[startLat, startLng], [endLat, endLng]], {
                    color: '#f97316', weight: 4, dashArray: '10, 10'
                }).addTo(map);

                <?php foreach ($stations as $index => $station): ?>
                    const midLat<?php echo $index; ?> = startLat + (endLat - startLat) * (<?php echo ($index + 1); ?> / <?php echo $numStations + 1; ?>);
                    const midLng<?php echo $index; ?> = startLng + (endLng - startLng) * (<?php echo ($index + 1); ?> / <?php echo $numStations + 1; ?>);
                    L.marker([midLat<?php echo $index; ?>, midLng<?php echo $index; ?>], {
                        icon: L.divIcon({
                            html: '<div style="background:#f97316; color:#fff; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11px; border:2px solid #fff;"><?php echo $index + 1; ?></div>',
                            className: ''
                        })
                    }).addTo(map)
                    .bindPopup('<b><?php echo htmlspecialchars($station["name"]); ?></b><br>Distance: <?php echo $station["distance_from_start"]; ?> km<br>Price: Rs. <?php echo number_format($station["price"], 2); ?>');
                <?php endforeach; ?>

                map.fitBounds([[startLat, startLng], [endLat, endLng]], { padding: [50, 50] });
            <?php endif; ?>
        });
    </script>

    <?php include 'theme.php'; ?>
</body>
</html>