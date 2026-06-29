<?php
session_start();
// Include live prices to show on dashboard
require_once 'fuel_price.php';
$fuelPrices = getFuelPrices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TurboFuel Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background: #0f172a;
            color: white;
        }
        .navbar {
            background: #111827;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #f97316;
        }
        .logo p {
            font-size: 12px;
            font-style: italic;
            margin: 2px 0 0 0;
            color: #94a3b8;
        }
        .right-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .menu a {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }
        .menu a:hover {
            color: #f97316;
        }
        .nav-menu-container {
            position: relative;
        }
        .three-dot-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #1e293b;
            border: 1px solid #334155;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 8px;
            transition: all 0.3s ease;
        }
        .three-dot-btn:hover {
            border-color: #f97316;
            background: #1e293b;
        }
        .three-dot-btn span {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #94a3b8;
            display: block;
        }
        .dot-menu-dropdown {
            position: absolute;
            top: 45px;
            right: 0;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 8px 0;
            min-width: 180px;
            display: none;
            z-index: 100;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .dot-menu-dropdown.show {
            display: block;
        }
        .dot-menu-item {
            padding: 10px 18px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .dot-menu-item:hover {
            background: #0f172a;
            color: #ffffff;
        }
        .menu-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        .hero {
            padding: 60px 60px 40px;
            text-align: center;
        }
        .hero h1 {
            font-size: 55px;
            margin-bottom: 10px;
        }
        .hero span {
            color: #f97316;
        }
        .hero p {
            font-size: 18px;
            color: #cbd5e1;
        }
        .price-banner {
            text-align: center;
            margin: 0 30px 20px;
            background: #1e293b;
            padding: 12px;
            border-radius: 12px;
            color: #f97316;
            font-weight: 500;
            font-size: 15px;
            border: 1px solid #334155;
        }
        .cards {
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
            padding: 30px;
        }
        .card {
            background: #1e293b;
            width: 250px;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            transition: 0.3s;
            text-decoration: none;
            color: white;
            display: block;
        }
        .card:hover {
            transform: translateY(-8px);
            background: #263348;
        }
        .card h2 {
            color: #f97316;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .card p {
            color: #cbd5e1;
            font-size: 14px;
        }
        .profile-section {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            margin-top: 30px;
            background: #1e293b;
            padding: 25px;
            border-radius: 20px;
        }
        .profile-section h2 {
            color: #f97316;
        }
        .footer {
            margin-top: 50px;
            padding: 20px;
            text-align: center;
            background: #111827;
            color: #94a3b8;
        }
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .hero { padding: 40px 20px; }
            .hero h1 { font-size: 40px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            ⛽ TurboFuel
            <p>"The Intelligent Way to Refuel"</p>
        </div>
        <div class="right-section">
            <div class="menu">
                <a href="profile.php">Profile</a>
            </div>
            <div class="nav-menu-container">
                <button class="three-dot-btn" onclick="toggleDotMenu()" title="More options">
                    <span></span><span></span><span></span>
                </button>
                <div class="dot-menu-dropdown" id="dotMenu">
                    <a href="route_finder.php" class="dot-menu-item">
                        <span class="menu-icon">&#128506;</span> Route Finder
                    </a>
                    <a href="wait_time.php" class="dot-menu-item">
                        <span class="menu-icon">&#128196;</span> Wait Time
                    </a>
                    <a href="trip_log.php" class="dot-menu-item">
                        <span class="menu-icon">&#11088;</span> Trip Log
                    </a>
                    <a href="settings.php" class="dot-menu-item">
                        <span class="menu-icon">&#9881;</span> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="hero">
        <h1>Welcome <span><?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : "Guest"; ?></span></h1>
        <p>Smart Fuel Station Waiting Time Estimation System</p>
    </div>

    <!-- Live price banner -->
    <div class="price-banner">
        Today's Fuel Prices: Petrol ₹<?php echo $fuelPrices['petrol']; ?>/L | Diesel ₹<?php echo $fuelPrices['diesel']; ?>/L | CNG ₹<?php echo $fuelPrices['cng']; ?>/kg
    </div>

    <div class="cards">
        <a href="history.php" class="card">
            <h2>⛽ Fuel History</h2>
            <p>View all previous fuel estimations and activities.</p>
        </a>
        <a href="saved_stations.php" class="card">
            <h2>⭐ Saved Stations</h2>
            <p>Quick access to your favourite fuel stations.</p>
        </a>
        <a href="route_finder.php" class="card">
            <h2>🗺️ Route Finder</h2>
            <p>Find fuel stations along your journey.</p>
        </a>
        <a href="settings.php" class="card">
            <h2>⚙️ Settings</h2>
            <p>Manage your profile and account preferences.</p>
        </a>
    </div>

    <div class="profile-section">
        <h2>Dashboard Overview</h2>
        <p>TurboFuel helps users estimate fuel station waiting times, analyze station traffic, and improve fueling efficiency.</p>
        <br>
        <p><strong>Current User:</strong> <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : "Guest"; ?></p>
    </div>

    <div class="footer">
        TurboFuel &copy; 2026 | Fuel Station Waiting Time Estimation System
    </div>

    <?php include 'theme.php'; ?>
    <script>
        function toggleDotMenu() {
            document.getElementById('dotMenu').classList.toggle('show');
        }
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('dotMenu');
            const btn = e.target.closest('.three-dot-btn');
            if (!btn && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });
    </script>
</body>
</html>