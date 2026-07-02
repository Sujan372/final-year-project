<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

require_once 'fuel_price.php';
$fuelPrices = getFuelPrices();

$userId = $_SESSION['user_id'];
$message = "";
$error = "";
$showResults = false;
$autoDistance = false;
$startCoord = null;
$endCoord = null;

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
    return ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];
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
    $fuelType      = in_array($_POST['fuel_type'] ?? '', ['petrol', 'diesel', 'cng']) ? $_POST['fuel_type'] : 'petrol';
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
        } elseif ($mileage <= 0) {
            $error = "Please enter a valid mileage.";
        } else {
            $showResults = true;

            $fuelNeeded   = $distance / $mileage;
            $pricePerUnit = $fuelPrices[$fuelType];
            $totalCost    = $fuelNeeded * $pricePerUnit;

            $numStations = (int) ceil($distance / 50);
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

            $message = "Route calculated. " . $numStations . " station" . ($numStations == 1 ? '' : 's') . " found.";
            if ($autoDistance) $message .= " (Distance auto-detected: " . number_format($distance, 1) . " km)";
        }
    }
}

$settings = $_SESSION['settings'] ?? ['default_fuel' => 'petrol', 'default_city' => '', 'default_vehicle' => ''];
$defaultFuel = $settings['default_fuel'] ?? 'petrol';
$defaultCity = $settings['default_city'] ?? '';
$defaultVehicle = $settings['default_vehicle'] ?? '';

$conn->close();

// ================== VEHICLES (BIKES, CARS, TRUCKS) ==================
$vehicles = [
    // --- Bikes ---
    'hero_splendor_petrol'        => ['name' => 'Hero Splendor (Petrol)',         'mileage' => 70, 'fuel' => 'petrol'],
    'bajaj_pulsar_petrol'         => ['name' => 'Bajaj Pulsar (Petrol)',          'mileage' => 45, 'fuel' => 'petrol'],
    'tvs_apache_petrol'           => ['name' => 'TVS Apache (Petrol)',            'mileage' => 40, 'fuel' => 'petrol'],
    'royal_enfield_classic_petrol'=> ['name' => 'Royal Enfield Classic (Petrol)',  'mileage' => 35, 'fuel' => 'petrol'],

    // --- Trucks ---
    'tata_407_diesel'             => ['name' => 'Tata 407 (Diesel)',              'mileage' => 6,  'fuel' => 'diesel'],
    'ashok_leyland_dost_diesel'   => ['name' => 'Ashok Leyland Dost (Diesel)',    'mileage' => 8,  'fuel' => 'diesel'],
    'mahindra_bolero_pickup_diesel'=> ['name' => 'Mahindra Bolero Pickup (Diesel)', 'mileage' => 10, 'fuel' => 'diesel'],
    'eicher_pro_3015_diesel'      => ['name' => 'Eicher Pro 3015 (Diesel)',       'mileage' => 5,  'fuel' => 'diesel'],

    // --- Cars ---
    'maruti_swift_petrol'         => ['name' => 'Maruti Swift (Petrol)',          'mileage' => 22, 'fuel' => 'petrol'],
    'hyundai_creta_diesel'        => ['name' => 'Hyundai Creta (Diesel)',         'mileage' => 18, 'fuel' => 'diesel'],
    'tata_nexon_petrol'           => ['name' => 'Tata Nexon (Petrol)',            'mileage' => 17, 'fuel' => 'petrol'],
    'honda_city_petrol'           => ['name' => 'Honda City (Petrol)',            'mileage' => 18, 'fuel' => 'petrol'],
    'maruti_dzire_cng'            => ['name' => 'Maruti Dzire (CNG)',             'mileage' => 26, 'fuel' => 'cng'],
    'mahindra_scorpio_diesel'     => ['name' => 'Mahindra Scorpio (Diesel)',      'mileage' => 15, 'fuel' => 'diesel'],

    // --- Manual entry ---
    'custom'                      => ['name' => 'Other (manual mileage)',          'mileage' => 0,  'fuel' => '']
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        /* (your existing styles are unchanged) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 32px 20px; color: #0f172a; }
        .container { max-width: 1180px; margin: 0 auto; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 14px; }
        .page-title h1 { font-size: 21px; font-weight: 700; color: #0f172a; }
        .page-title h1 span { color: #2563eb; }
        .page-title p { color: #64748b; font-size: 13px; margin-top: 3px; }
        .header-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { padding: 9px 16px; border-radius: 9px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; transition: background 0.15s ease, border-color 0.15s ease; cursor: pointer; font-family: inherit; }
        .btn-outline { background: #ffffff; color: #334155; }
        .btn-outline:hover { border-color: #94a3b8; background: #f8fafc; }
        .btn-primary { background: #2563eb; color: #ffffff; border-color: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 13.5px; font-weight: 500; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; }
        .card-title { font-size: 15.5px; font-weight: 600; color: #0f172a; margin-bottom: 18px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #334155; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .form-row { display: flex; gap: 12px; }
        .form-row .form-group { flex: 1; }
        input, select { width: 100%; padding: 11px 13px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 9px; color: #0f172a; font-size: 13.5px; font-family: 'Inter', sans-serif; transition: border-color 0.15s ease, box-shadow 0.15s ease; outline: none; }
        input:focus, select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
        input::placeholder { color: #94a3b8; }
        .field-hint { color: #94a3b8; font-size: 11px; margin-top: 4px; display: block; }
        .mileage-note { color: #94a3b8; font-size: 11px; margin-top: 4px; }
        .route-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 18px 0; padding-bottom: 18px; border-bottom: 1px solid #f1f5f9; }
        .summary-item { background: #f8fafc; border-radius: 10px; padding: 14px; text-align: center; }
        .summary-label { font-size: 10.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
        .summary-value { font-size: 18px; font-weight: 700; color: #0f172a; }
        .summary-value.highlight { color: #2563eb; }
        .station-list { max-height: 380px; overflow-y: auto; }
        .station-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .station-number { width: 32px; height: 32px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; font-size: 13px; flex-shrink: 0; }
        .station-info { flex: 1; min-width: 0; }
        .station-info h4 { color: #0f172a; font-size: 13.5px; margin-bottom: 2px; }
        .station-info p { color: #64748b; font-size: 11.5px; }
        .station-meta { text-align: right; flex-shrink: 0; }
        .station-meta .distance { color: #2563eb; font-weight: 700; font-size: 14.5px; }
        .station-meta .price { color: #16a34a; font-size: 11.5px; }
        .station-meta .rating { color: #d97706; font-size: 11.5px; }
        .map-wrapper { height: 460px; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        #map { height: 100%; width: 100%; }
        .route-visual { display: flex; align-items: center; gap: 8px; padding: 12px; background: #f8fafc; border-radius: 10px; margin-top: 14px; }
        .route-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .route-dot.start { background: #16a34a; }
        .route-dot.end { background: #dc2626; }
        .route-line { flex: 1; height: 2px; background: linear-gradient(90deg, #16a34a, #dc2626); }
        .route-text { font-size: 11.5px; color: #64748b; }
        .legend { display: flex; gap: 16px; margin-top: 14px; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 11.5px; }
        .auto-badge { background: #f0fdf4; color: #16a34a; font-size: 10.5px; padding: 2px 9px; border-radius: 12px; margin-left: 8px; }
        .live-price { background: #eff6ff; color: #2563eb; font-size: 12px; padding: 9px 14px; border-radius: 9px; margin-bottom: 16px; display: inline-block; }
        h3.section-heading { color: #0f172a; font-size: 14px; margin-bottom: 10px; }
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

        <div class="live-price">
            Live fuel prices: Petrol ₹<?php echo $fuelPrices['petrol']; ?>/L &nbsp;|&nbsp; Diesel ₹<?php echo $fuelPrices['diesel']; ?>/L &nbsp;|&nbsp; CNG ₹<?php echo $fuelPrices['cng']; ?>/kg
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
                            <label>Distance (km) &ndash; leave blank for auto</label>
                            <input type="number" name="distance" placeholder="Auto-detected if empty" step="0.1" min="0" value="<?php echo htmlspecialchars($_POST['distance'] ?? ''); ?>">
                            <small class="field-hint">Auto-detection works for most locations</small>
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
                                <option value="<?php echo htmlspecialchars($key); ?>"
                                    data-mileage="<?php echo $v['mileage']; ?>"
                                    data-fuel="<?php echo htmlspecialchars($v['fuel']); ?>"
                                    <?php if ($key == $defaultVehicle) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($v['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mileage-note">Select a vehicle to auto-fill mileage and fuel type</div>
                    </div>

                    <div class="form-group">
                        <label>Mileage (km/L or km/kg)</label>
                        <input type="number" name="mileage" id="mileageInput" placeholder="Enter mileage" step="0.1" min="1" value="<?php echo htmlspecialchars($_POST['mileage'] ?? ''); ?>" required>
                    </div>

                    <button type="submit" name="find_route" class="btn btn-primary" style="width:100%; padding:12px; font-size:14px;">
                        Find Stations on Route
                    </button>
                </form>

                <?php if ($showResults): ?>
                    <div class="route-visual">
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
                            <div class="summary-value highlight">₹<?php echo number_format($totalCost, 2); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Stations on Route</div>
                            <div class="summary-value"><?php echo $numStations; ?></div>
                        </div>
                    </div>
                    <h3 class="section-heading">Stations Along Your Route</h3>
                    <div class="station-list">
                        <?php foreach ($stations as $index => $station): ?>
                            <div class="station-card">
                                <div class="station-number"><?php echo $index + 1; ?></div>
                                <div class="station-info">
                                    <h4><?php echo htmlspecialchars($station['name']); ?></h4>
                                    <p><?php echo htmlspecialchars(implode(', ', $station['fuel_types'])); ?></p>
                                    <p class="rating">Rating: <?php echo $station['rating']; ?>/5</p>
                                </div>
                                <div class="station-meta">
                                    <div class="distance"><?php echo $station['distance_from_start']; ?> km</div>
                                    <div class="price">₹<?php echo number_format($station['price'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Route Map</div>
                <div class="map-wrapper">
                    <div id="map"></div>
                </div>
                <?php if ($showResults): ?>
                    <div class="legend">
                        <div class="legend-item"><div class="route-dot start"></div> Start Point</div>
                        <div class="legend-item"><div class="route-dot end"></div> End Point</div>
                        <div class="legend-item" style="color:#2563eb;">--- Route Line</div>
                    </div>
                    <?php if (!$startCoord || !$endCoord): ?>
                        <p style="color:#94a3b8; font-size:11.5px; margin-top:10px;">Exact coordinates couldn't be found for one of the locations, so the map below uses an approximate position.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
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

        window.addEventListener('DOMContentLoaded', () => {
            const vehicleSelect = document.getElementById('vehicleModel');
            if (vehicleSelect.value !== 'custom') {
                updateMileage();
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapElement = document.getElementById('map');
            if (!mapElement) return;

            const map = L.map('map').setView([20.5937, 78.9629], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            <?php if ($showResults): ?>
                <?php
                    $sLat = $startCoord['lat'] ?? 20.5937;
                    $sLon = $startCoord['lon'] ?? 78.9629;
                    $eLat = $endCoord['lat'] ?? ($sLat + 1);
                    $eLon = $endCoord['lon'] ?? ($sLon + 1);
                ?>
                const startLat = <?php echo $sLat; ?>;
                const startLng = <?php echo $sLon; ?>;
                const endLat   = <?php echo $eLat; ?>;
                const endLng   = <?php echo $eLon; ?>;

                L.marker([startLat, startLng])
                    .addTo(map)
                    .bindPopup('<b>Start:</b> <?php echo htmlspecialchars($startLocation); ?>')
                    .openPopup();

                L.marker([endLat, endLng])
                    .addTo(map)
                    .bindPopup('<b>End:</b> <?php echo htmlspecialchars($endLocation); ?>');

                L.polyline([[startLat, startLng], [endLat, endLng]], {
                    color: '#2563eb', weight: 4, dashArray: '10, 10'
                }).addTo(map);

                <?php foreach ($stations as $index => $station): ?>
                    const midLat<?php echo $index; ?> = startLat + (endLat - startLat) * (<?php echo ($index + 1); ?> / <?php echo $numStations + 1; ?>);
                    const midLng<?php echo $index; ?> = startLng + (endLng - startLng) * (<?php echo ($index + 1); ?> / <?php echo $numStations + 1; ?>);
                    L.marker([midLat<?php echo $index; ?>, midLng<?php echo $index; ?>], {
                        icon: L.divIcon({
                            html: '<div style="background:#2563eb; color:#fff; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11px; border:2px solid #fff;"><?php echo $index + 1; ?></div>',
                            className: ''
                        })
                    }).addTo(map)
                    .bindPopup('<b><?php echo htmlspecialchars($station["name"]); ?></b><br>Distance: <?php echo $station["distance_from_start"]; ?> km<br>Price: &#8377;<?php echo number_format($station["price"], 2); ?>');
                <?php endforeach; ?>

                map.fitBounds([[startLat, startLng], [endLat, endLng]], { padding: [50, 50] });
            <?php endif; ?>
        });
    </script>

    <!-- Theme support -->
    <?php include 'theme.php'; ?>
</body>
</html>