<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once 'fuel_price.php';
$host = "localhost"; $user = "root"; $pass = ""; $db = "fuel_estimator";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed");

// FIX: cast to int before using in a query string, and use a prepared
// statement so this value can never be used to inject SQL.
$userId = (int) $_SESSION['user_id'];
$message = $error = "";
$showForm = true;

// Fetch saved stations
$stmt = $conn->prepare("SELECT id, station_name, city FROM saved_stations WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stations = $stmt->get_result();

// FIX: query/prepare can fail and $stations can come back falsy (e.g. a
// transient DB error). ->num_rows on a falsy result is a fatal error, so
// this is guarded before it's touched.
if (!$stations || $stations->num_rows == 0) {
    // Fallback demo stations
    $demo = [
        ['id' => 0, 'station_name' => 'HP Fuel Station', 'city' => 'Bangalore'],
        ['id' => 1, 'station_name' => 'Indian Oil - City Center', 'city' => 'Bangalore']
    ];
} else {
    $demo = [];
    while ($r = $stations->fetch_assoc()) $demo[] = $r;
}
$stmt->close();

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $station = trim($_POST['station_name']);
    $city = trim($_POST['city']);
    $fuelType = $_POST['fuel_type'];
    $vehicleType = $_POST['vehicle_type'] ?? 'car';
    $litres = floatval($_POST['litres']);
    $price = floatval($_POST['price_per_unit']);
    $total = $litres * $price;
    $bookingTime = date('Y-m-d H:i:s');

    if (empty($station) || $litres <= 0) {
        $error = "Please fill all fields correctly.";
    } else {
        $stmt2 = $conn->prepare("INSERT INTO fuel_bookings (user_id, station_name, city, fuel_type, litres, price_per_unit, total_cost, booking_time) VALUES (?,?,?,?,?,?,?,?)");
        $stmt2->bind_param("isssddds", $userId, $station, $city, $fuelType, $litres, $price, $total, $bookingTime);
        if ($stmt2->execute()) {
            $message = "Booking confirmed. Your prepaid fueling slot is reserved.";
            $showForm = false;
        } else {
            $error = "Booking failed. Please try again.";
        }
        $stmt2->close();
    }
}

// Current prices and wait time (reuse wait time logic)
$livePrices = getFuelPrices('Bangalore'); // default city
$waitMultiplier = (date('H') >= 8 && date('H') <= 10 || date('H') >= 17 && date('H') <= 20) ? 1.8 : 1.0;
$baseWait = ['petrol' => [3, 8], 'diesel' => [5, 12], 'cng' => [10, 25]];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head_common.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Fueling - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #090c14;
            --panel: #10151f;
            --panel-alt: #141b2a;
            --line: #212b3d;
            --line-soft: #181f30;
            --amber: #f2a63d;
            --amber-dim: rgba(242, 166, 61, 0.12);
            --teal: #2bc8a8;
            --teal-dim: rgba(43, 200, 168, 0.12);
            --azure: #5b8def;
            --red: #e2584f;
            --red-dim: rgba(226, 88, 79, 0.12);
            --text: #edeff5;
            --text-dim: #8b96ac;
            --text-faint: #4e5872;
            --radius-lg: 22px;
            --radius-md: 14px;
            --radius-sm: 9px;
        }
        * { box-sizing: border-box; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 900px 500px at 15% -10%, rgba(242, 166, 61, 0.07), transparent 60%),
                radial-gradient(ellipse 800px 500px at 100% 0%, rgba(43, 200, 168, 0.06), transparent 55%),
                var(--ink);
            color: var(--text);
            padding: 40px 20px 60px;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, .display { font-family: 'Rajdhani', 'Inter', sans-serif; letter-spacing: -0.01em; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .container { max-width: 640px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 26px; flex-wrap: wrap; gap: 14px; }
        .header-title .eyebrow {
            font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 500;
            letter-spacing: 0.14em; text-transform: uppercase; color: var(--teal); margin-bottom: 6px; display: block;
        }
        .header h1 { font-size: 26px; font-weight: 700; margin: 0; }
        .header-actions { display: flex; gap: 10px; }
        .btn {
            background: var(--panel); color: var(--text); padding: 9px 18px; border-radius: var(--radius-sm);
            text-decoration: none; font-size: 13.5px; font-weight: 600; border: 1px solid var(--line);
            transition: all 0.2s ease; display: inline-block;
        }
        .btn:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-dim); }

        .panel-card { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); padding: 28px; margin-bottom: 20px; }

        .alert { padding: 14px 16px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 14px; font-weight: 500; }
        .alert-success { background: var(--teal-dim); border: 1px solid rgba(43, 200, 168, 0.3); color: var(--teal); }
        .alert-error { background: var(--red-dim); border: 1px solid rgba(226, 88, 79, 0.3); color: var(--red); }

        .vehicle-select { display: flex; gap: 10px; margin-bottom: 20px; }
        .vehicle-option {
            flex: 1; display: flex; flex-direction: column; align-items: center; gap: 7px;
            padding: 14px 8px; background: var(--ink); border: 1px solid var(--line); border-radius: 12px;
            cursor: pointer; color: var(--text-dim); transition: all 0.2s ease;
        }
        .vehicle-option:hover { border-color: var(--line); background: #0d1220; }
        .vehicle-option svg { width: 24px; height: 24px; stroke: currentColor; fill: none; stroke-width: 1.6; }
        .vehicle-option .v-label { font-size: 12px; font-weight: 600; }
        .vehicle-option .v-tank { font-size: 10.5px; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; }
        .vehicle-option.active { border-color: var(--amber); background: var(--amber-dim); color: var(--amber); }
        .vehicle-option.active .v-tank { color: var(--amber); opacity: 0.75; }

        label { display: block; margin-bottom: 7px; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; }
        input, select {
            width: 100%; padding: 11px 12px; margin-bottom: 16px; background: var(--ink);
            border: 1px solid var(--line); border-radius: var(--radius-sm); color: var(--text);
            font-size: 14px; font-family: 'Inter', sans-serif; transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-dim); }

        .price-preview {
            background: var(--ink); border: 1px solid var(--line-soft); padding: 13px;
            border-radius: var(--radius-sm); margin-top: 4px; text-align: center;
            font-family: 'JetBrains Mono', monospace; font-size: 13.5px; color: var(--text-dim);
        }
        .confirm-btn {
            width: 100%; background: var(--amber); color: #1a1305; border: none;
            padding: 13px; margin-top: 16px; border-radius: var(--radius-sm);
            font-size: 14.5px; font-weight: 700; cursor: pointer; transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }
        .confirm-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

        .back-btn { width: 100%; text-align: center; padding: 12px; }

        @media (max-width: 500px) {
            .header { flex-direction: column; align-items: flex-start; }
            .panel-card { padding: 22px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="header-title">
            <span class="eyebrow">Prepaid slot</span>
            <h1>Book fueling</h1>
        </div>
        <div class="header-actions">
            <a href="index.php" class="btn">Dashboard</a>
            <a href="my_bookings.php" class="btn">My bookings</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <a href="index.php" class="btn back-btn">Back to dashboard</a>
    <?php elseif ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <div class="panel-card">
        <form method="post" id="bookingForm">
            <label>Vehicle</label>
            <div class="vehicle-select" id="vehicleSelect">
                <div class="vehicle-option active" data-vehicle="bike">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="5.5" cy="17.5" r="3.5"></circle><circle cx="18.5" cy="17.5" r="3.5"></circle><path d="M5.5 17.5 9 10h4l3 3.5h2M9 10 7.5 6.5h-2M12 10l1.8-3.5"></path></svg>
                    <span class="v-label">Bike</span>
                    <span class="v-tank">~5 L</span>
                </div>
                <div class="vehicle-option" data-vehicle="car">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16v-3l2-4.5A2 2 0 0 1 6.8 7h10.4a2 2 0 0 1 1.8 1.5L21 13v3"></path><path d="M3 16h18v2a1 1 0 0 1-1 1h-1.5a1 1 0 0 1-1-1v-1H6.5v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2Z"></path><circle cx="7.5" cy="16" r="1.6"></circle><circle cx="16.5" cy="16" r="1.6"></circle></svg>
                    <span class="v-label">Car</span>
                    <span class="v-tank">~20 L</span>
                </div>
                <div class="vehicle-option" data-vehicle="truck">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8h11v8H2z"></path><path d="M13 11h4l3 2.5V16h-7z"></path><circle cx="6" cy="18" r="1.8"></circle><circle cx="16.5" cy="18" r="1.8"></circle></svg>
                    <span class="v-label">Truck</span>
                    <span class="v-tank">~80 L</span>
                </div>
            </div>
            <input type="hidden" name="vehicle_type" id="vehicleTypeInput" value="bike">

            <label>Station</label>
            <select name="station_name" id="stationSelect" onchange="updateStation()">
                <?php foreach ($demo as $s): ?>
                    <option value="<?php echo htmlspecialchars($s['station_name']); ?>" data-city="<?php echo htmlspecialchars($s['city']); ?>">
                        <?php echo htmlspecialchars($s['station_name']); ?> (<?php echo htmlspecialchars($s['city']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="city" id="cityInput" value="<?php echo htmlspecialchars($demo[0]['city']); ?>">

            <label>Fuel type</label>
            <select name="fuel_type" id="fuelType" onchange="updatePrice()">
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="cng">CNG</option>
            </select>

            <label>Litres</label>
            <input type="number" name="litres" id="litres" placeholder="e.g., 20" step="0.1" min="1" required onkeyup="updatePrice()">

            <input type="hidden" name="price_per_unit" id="pricePerUnit" value="<?php echo $livePrices['petrol']; ?>">

            <div class="price-preview" id="pricePreview">Total cost: Rs. 0.00 | Estimated wait: 3-8 min</div>

            <button type="submit" name="book" class="confirm-btn">Confirm prepaid booking</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
const prices = <?php echo json_encode($livePrices); ?>;
const baseWait = <?php echo json_encode($baseWait); ?>;
const multiplier = <?php echo $waitMultiplier; ?>;
const vehicles = {
    bike:  { tank: 5,  fuel: 'petrol', waitFactor: 0.7 },
    car:   { tank: 20, fuel: 'petrol', waitFactor: 1.0 },
    truck: { tank: 80, fuel: 'diesel', waitFactor: 1.6 }
};
let currentVehicle = 'bike';

function selectVehicle(key) {
    currentVehicle = key;
    document.querySelectorAll('.vehicle-option').forEach(el => el.classList.toggle('active', el.dataset.vehicle === key));
    document.getElementById('vehicleTypeInput').value = key;
    const v = vehicles[key];
    document.getElementById('fuelType').value = v.fuel;
    document.getElementById('litres').value = v.tank;
    updatePrice();
}

function updateStation() {
    const select = document.getElementById('stationSelect');
    const city = select.options[select.selectedIndex].getAttribute('data-city');
    document.getElementById('cityInput').value = city;
}

function updatePrice() {
    const fuel = document.getElementById('fuelType').value;
    const litres = parseFloat(document.getElementById('litres').value) || 0;
    const price = prices[fuel];
    document.getElementById('pricePerUnit').value = price;
    const total = litres * price;
    const wait = baseWait[fuel];
    const factor = multiplier * vehicles[currentVehicle].waitFactor;
    const minWait = Math.round(wait[0] * factor);
    const maxWait = Math.round(wait[1] * factor);
    document.getElementById('pricePreview').innerHTML =
        `Total cost: Rs. ${total.toFixed(2)} | Estimated wait: ${minWait}-${maxWait} min`;
}

document.querySelectorAll('.vehicle-option').forEach(el => el.addEventListener('click', () => selectVehicle(el.dataset.vehicle)));

// init
updatePrice();
</script>
</body>
</html>