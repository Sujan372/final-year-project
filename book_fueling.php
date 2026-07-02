<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

require_once 'fuel_price.php';
$host = "localhost"; $user = "root"; $pass = ""; $db = "fuel_estimator";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed");

$userId = $_SESSION['user_id'];
$message = $error = "";
$showForm = true;

// Fetch saved stations
$stations = $conn->query("SELECT id, station_name, city FROM saved_stations WHERE user_id = $userId");
if ($stations->num_rows == 0) {
    // Fallback demo stations
    $demo = [
        ['id'=>0, 'station_name'=>'HP Fuel Station', 'city'=>'Bangalore'],
        ['id'=>1, 'station_name'=>'Indian Oil - City Center', 'city'=>'Bangalore']
    ];
} else {
    $demo = [];
    while($r = $stations->fetch_assoc()) $demo[] = $r;
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    $station = trim($_POST['station_name']);
    $city = trim($_POST['city']);
    $fuelType = $_POST['fuel_type'];
    $litres = floatval($_POST['litres']);
    $price = floatval($_POST['price_per_unit']);
    $total = $litres * $price;
    $bookingTime = date('Y-m-d H:i:s');

    if (empty($station) || $litres <= 0) {
        $error = "Please fill all fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO fuel_bookings (user_id, station_name, city, fuel_type, litres, price_per_unit, total_cost, booking_time) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssddds", $userId, $station, $city, $fuelType, $litres, $price, $total, $bookingTime);
        if ($stmt->execute()) {
            $message = "Booking confirmed! Your prepaid fueling slot is reserved.";
            $showForm = false;
        } else {
            $error = "Booking failed. Try again.";
        }
        $stmt->close();
    }
}

// Current prices and wait time (reuse wait time logic)
$livePrices = getFuelPrices('Bangalore'); // default city
$waitMultiplier = (date('H')>=8 && date('H')<=10 || date('H')>=17 && date('H')<=20) ? 1.8 : 1.0;
$baseWait = ['petrol'=>[3,8], 'diesel'=>[5,12], 'cng'=>[10,25]];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Fueling - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#0f172a; color:white; padding:30px 20px; }
        .container { max-width:700px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
        .header h1 { color:#f97316; font-size:24px; }
        .btn { background:#1e293b; color:white; padding:8px 18px; border-radius:8px; text-decoration:none; font-size:14px; border:1px solid #334155; transition:0.3s; }
        .btn:hover { border-color:#f97316; }
        .card { background:#1e293b; border:1px solid #334155; border-radius:16px; padding:25px; margin-bottom:20px; }
        label { display:block; margin-bottom:6px; color:#94a3b8; font-size:14px; }
        input, select { width:100%; padding:10px; margin-bottom:15px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:white; font-size:14px; }
        .price-preview { background:#263348; padding:12px; border-radius:8px; margin-top:15px; text-align:center; }
        .alert { padding:14px; border-radius:12px; margin-bottom:20px; }
        .alert-success { background:rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.3); color:#4ade80; }
        .alert-error { background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#f87171; }
        @media (max-width:500px) { .header { flex-direction:column; align-items:flex-start; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Book Fueling (Prepaid)</h1>
        <div>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="my_bookings.php" class="btn">My Bookings</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <a href="index.php" class="btn" style="width:100%; text-align:center;">Back to Dashboard</a>
    <?php elseif ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <div class="card">
        <form method="post" id="bookingForm">
            <label>Station</label>
            <select name="station_name" id="stationSelect" onchange="updateStation()">
                <?php foreach ($demo as $s): ?>
                    <option value="<?php echo htmlspecialchars($s['station_name']); ?>" data-city="<?php echo htmlspecialchars($s['city']); ?>">
                        <?php echo htmlspecialchars($s['station_name']); ?> (<?php echo htmlspecialchars($s['city']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="city" id="cityInput" value="<?php echo htmlspecialchars($demo[0]['city']); ?>">

            <label>Fuel Type</label>
            <select name="fuel_type" id="fuelType" onchange="updatePrice()">
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="cng">CNG</option>
            </select>

            <label>Litres</label>
            <input type="number" name="litres" id="litres" placeholder="e.g., 20" step="0.1" min="1" required onkeyup="updatePrice()">

            <input type="hidden" name="price_per_unit" id="pricePerUnit" value="<?php echo $livePrices['petrol']; ?>">

            <div class="price-preview" id="pricePreview">
                Total cost: Rs. 0.00 | Estimated wait: 3-8 min
            </div>

            <button type="submit" name="book" class="btn" style="width:100%; background:#f97316; border-color:#f97316; padding:12px; margin-top:10px;">
                Confirm Prepaid Booking
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
const prices = <?php echo json_encode($livePrices); ?>;
const baseWait = <?php echo json_encode($baseWait); ?>;
const multiplier = <?php echo $waitMultiplier; ?>;

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
    const minWait = Math.round(wait[0] * multiplier);
    const maxWait = Math.round(wait[1] * multiplier);
    document.getElementById('pricePreview').innerHTML = 
        `Total cost: Rs. ${total.toFixed(2)} | Estimated wait: ${minWait}-${maxWait} min`;
}

// init
updatePrice();
</script>
</body>
</html>