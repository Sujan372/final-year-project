<?php
session_start();
 
// Load theme from session or default to dark
$theme = $_SESSION['settings']['theme'] ?? 'dark';
 
// Include live prices to show on dashboard
require_once 'fuel_price.php';
$fuelPrices = getFuelPrices();
 
// ---------- Database connection ----------
$host = "localhost";
$user = "root";
$pass = "";
$db = "fuel_estimator";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed");
}
 
// FIX: cast to int so this value can never be used to inject SQL,
// since it is used inside query strings below.
$userId = (int) ($_SESSION['user_id'] ?? 0);
 
// --- Saved stations (for dropdown + sidebar list) ---
$stationsStmt = $conn->prepare("SELECT station_name, city FROM saved_stations WHERE user_id = ? LIMIT 5");
$stationsStmt->bind_param("i", $userId);
$stationsStmt->execute();
$stationsResult = $stationsStmt->get_result();
 
$stationOptions = '';
$stationRows = [];
$demoCity = 'Bangalore';
 
if ($stationsResult && $stationsResult->num_rows > 0) {
    while ($s = $stationsResult->fetch_assoc()) {
        $stationOptions .= '<option value="' . htmlspecialchars($s['station_name']) . '" data-city="' . htmlspecialchars($s['city']) . '">'
            . htmlspecialchars($s['station_name']) . ' (' . htmlspecialchars($s['city']) . ')</option>';
        $stationRows[] = $s;
        $demoCity = $s['city'];
    }
} else {
    $stationOptions = '
        <option value="HP Fuel Station" data-city="Bangalore">HP Fuel Station (Bangalore)</option>
        <option value="Indian Oil" data-city="Bangalore">Indian Oil (Bangalore)</option>';
    $stationRows = [
        ['station_name' => 'HP Fuel Station', 'city' => 'Bangalore'],
        ['station_name' => 'Indian Oil', 'city' => 'Bangalore'],
    ];
}
$stationsStmt->close();
 
// --- Dashboard stats (initialise with zeros) ---
$stats = [
    'total_trips' => 0,
    'total_km' => 0,
    'total_spent' => 0,
    'total_fuel' => 0,
    'saved_stations' => 0,
    'recent_bookings' => 0
];
 
// Total trips, km, spent from route_history
$stmt = $conn->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(distance_km),0) as km, COALESCE(SUM(total_cost),0) as cost FROM route_history WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $row = $res->fetch_assoc()) {
    $stats['total_trips'] = $row['cnt'];
    $stats['total_km'] = round($row['km'], 1);
    $stats['total_spent'] = $row['cost'];
}
$stmt->close();
 
// FIX: query() can return false (e.g. on a transient DB error), and calling
// ->num_rows on false is a fatal error. Guard every SHOW TABLES check.
$fuelHistoryCheck = $conn->query("SHOW TABLES LIKE 'fuel_history'");
if ($fuelHistoryCheck && $fuelHistoryCheck->num_rows > 0) {
    $stmt2 = $conn->prepare("SELECT COALESCE(SUM(litres),0) as fuel FROM fuel_history WHERE user_id = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $stats['total_fuel'] = round($row2['fuel'], 1);
    }
    $stmt2->close();
}
 
// Saved stations count
$stmt3 = $conn->prepare("SELECT COUNT(*) as cnt FROM saved_stations WHERE user_id = ?");
$stmt3->bind_param("i", $userId);
$stmt3->execute();
$res3 = $stmt3->get_result();
if ($res3 && $row3 = $res3->fetch_assoc()) {
    $stats['saved_stations'] = $row3['cnt'];
}
$stmt3->close();
 
// Recent bookings (last 7 days) if table exists
$fuelBookingsCheck = $conn->query("SHOW TABLES LIKE 'fuel_bookings'");
if ($fuelBookingsCheck && $fuelBookingsCheck->num_rows > 0) {
    $stmt4 = $conn->prepare("SELECT COUNT(*) as cnt FROM fuel_bookings WHERE user_id = ? AND created_at >= NOW() - INTERVAL 7 DAY");
    $stmt4->bind_param("i", $userId);
    $stmt4->execute();
    $res4 = $stmt4->get_result();
    if ($res4 && $row4 = $res4->fetch_assoc()) {
        $stats['recent_bookings'] = $row4['cnt'];
    }
    $stmt4->close();
}
 
$conn->close();
 
// Decorative gauge-ring sweep values for the sidebar rail (purely visual)
$ringSweeps = [72, 58, 84, 46];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TurboFuel Dashboard</title>
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
            font-family: 'Inter', Arial, sans-serif;
            background:
                radial-gradient(ellipse 900px 500px at 15% -10%, rgba(242, 166, 61, 0.07), transparent 60%),
                radial-gradient(ellipse 800px 500px at 100% 0%, rgba(43, 200, 168, 0.06), transparent 55%),
                var(--ink);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, .display { font-family: 'Rajdhani', 'Inter', sans-serif; letter-spacing: -0.01em; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px 60px; }
 
        .navbar {
            background: linear-gradient(180deg, rgba(16, 21, 31, 0.94), rgba(16, 21, 31, 0.84));
            backdrop-filter: blur(14px);
            padding: 14px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--line-soft);
            position: sticky; top: 0; z-index: 10; flex-wrap: wrap;
        }
        .logo-group { display: flex; align-items: center; gap: 12px; }
        .logo-badge {
            width: 40px; height: 40px; border-radius: 50%;
            border: 2px solid var(--amber);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 15px;
            color: var(--amber); background: var(--amber-dim); flex-shrink: 0;
        }
        .logo-text { font-size: 22px; font-weight: 700; line-height: 1.1; }
        .logo-text span { color: var(--amber); }
        .logo-text p { font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 400; font-style: italic; margin: 2px 0 0; color: var(--text-faint); }
        .right-section { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .menu a { text-decoration: none; }
        .profile-btn {
            background: var(--panel); color: var(--text); text-decoration: none;
            font-size: 13.5px; font-weight: 600; padding: 9px 22px; border-radius: var(--radius-sm);
            border: 1px solid var(--line); transition: all 0.25s ease;
        }
        .profile-btn:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-dim); }
 
        .theme-toggle {
            width: 46px; height: 26px; background: var(--line); border-radius: 13px;
            cursor: pointer; position: relative; transition: background 0.3s ease; border: none;
        }
        .theme-toggle.light { background: var(--amber); }
        .theme-toggle::after {
            content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px;
            background: var(--ink); border-radius: 50%; transition: transform 0.3s ease;
        }
        .theme-toggle.light::after { transform: translateX(20px); background: white; }
 
        .nav-menu-container { position: relative; }
        .three-dot-btn {
            width: 38px; height: 38px; border-radius: var(--radius-sm); background: var(--panel);
            border: 1px solid var(--line); cursor: pointer; display: flex; flex-direction: column;
            align-items: center; justify-content: center; gap: 3px; padding: 8px; transition: all 0.25s ease;
        }
        .three-dot-btn:hover { border-color: var(--amber); background: var(--amber-dim); }
        .three-dot-btn span { width: 4px; height: 4px; border-radius: 50%; background: var(--text-dim); display: block; }
        .dot-menu-dropdown {
            position: absolute; top: 48px; right: 0; background: var(--panel); border: 1px solid var(--line);
            border-radius: var(--radius-md); padding: 8px; min-width: 190px; display: none; z-index: 100;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        }
        .dot-menu-dropdown.show { display: block; animation: dropIn 0.18s ease; }
        @keyframes dropIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .dot-menu-item {
            padding: 10px 14px; color: var(--text-dim); text-decoration: none; font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 10px; border-radius: 8px; transition: all 0.2s ease;
        }
        .dot-menu-item:hover { background: var(--line-soft); color: var(--text); }
 
        /* ---------- Cockpit hero ---------- */
        .cockpit { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 30px; padding: 48px 0 10px; align-items: center; }
        .eyebrow {
            font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 500;
            letter-spacing: 0.18em; text-transform: uppercase; color: var(--teal);
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
        }
        .eyebrow::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--teal); box-shadow: 0 0 10px var(--teal); }
        .cockpit h1 { font-size: 50px; font-weight: 700; margin: 0 0 14px; line-height: 1.05; }
        .cockpit h1 span { color: var(--amber); }
        .cockpit p.lede { font-size: 16px; color: var(--text-dim); margin: 0 0 26px; max-width: 46ch; }
        .cockpit-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-primary {
            background: var(--amber); color: #1a1305; border: none; font-weight: 700;
            padding: 12px 26px; border-radius: var(--radius-sm); font-size: 14px; cursor: pointer;
            text-decoration: none; display: inline-block;
        }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-secondary {
            background: transparent; color: var(--text); border: 1px solid var(--line);
            padding: 12px 26px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600;
            cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-secondary:hover { border-color: var(--teal); color: var(--teal); }
 
        .dial-panel { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); padding: 26px 26px 22px; text-align: center; }
        .dial-title { font-size: 11.5px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; margin-bottom: 6px; }
        .dial-svg { width: 100%; max-width: 260px; margin: 6px auto -6px; display: block; }
        .dial-readout { font-size: 34px; font-weight: 700; margin-top: -6px; }
        .dial-readout span { font-size: 15px; color: var(--text-faint); font-weight: 500; margin-left: 6px; }
        .dial-sub { font-size: 12.5px; color: var(--text-dim); margin-top: 4px; }
        .needle { transition: transform 1.1s cubic-bezier(0.16, 1, 0.3, 1); }
 
        /* ---------- Price ticker ---------- */
        .ticker { display: flex; flex-wrap: wrap; margin: 34px 0; background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-md); overflow: hidden; }
        .ticker-item { flex: 1; min-width: 150px; padding: 16px 18px; text-align: center; border-right: 1px solid var(--line-soft); }
        .ticker-item:last-child { border-right: none; }
        .ticker-label { font-family: 'JetBrains Mono', monospace; font-size: 10.5px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--text-faint); display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 6px; }
        .ticker-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .ticker-dot.petrol { background: var(--amber); box-shadow: 0 0 8px var(--amber); }
        .ticker-dot.diesel { background: var(--teal); box-shadow: 0 0 8px var(--teal); }
        .ticker-dot.cng { background: var(--azure); box-shadow: 0 0 8px var(--azure); }
        .ticker-value { font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 600; }
 
        /* ---------- Two column body ---------- */
        .body-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; align-items: start; }
        .panel-card { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); padding: 28px; }
        .panel-card h2 { font-size: 20px; font-weight: 700; margin: 0 0 4px; }
        .panel-card .subtext { font-size: 13px; color: var(--text-faint); margin: 0 0 20px; }
 
        .vehicle-select { display: flex; gap: 10px; margin-bottom: 22px; }
        .vehicle-option {
            flex: 1; display: flex; flex-direction: column; align-items: center; gap: 7px;
            padding: 14px 8px; background: var(--ink); border: 1px solid var(--line); border-radius: 12px;
            cursor: pointer; color: var(--text-dim); transition: all 0.2s ease;
        }
        .vehicle-option:hover { border-color: var(--line); background: #0d1220; }
        .vehicle-option svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 1.6; }
        .vehicle-option .v-label { font-size: 12px; font-weight: 600; }
        .vehicle-option .v-tank { font-size: 10.5px; color: var(--text-faint); font-family: 'JetBrains Mono', monospace; }
        .vehicle-option.active { border-color: var(--amber); background: var(--amber-dim); color: var(--amber); }
        .vehicle-option.active .v-tank { color: var(--amber); opacity: 0.75; }
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { flex: 1; min-width: 140px; }
        .form-group label { color: var(--text-faint); font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 7px; }
        .form-group select, .form-group input {
            width: 100%; padding: 11px 12px; background: var(--ink); border: 1px solid var(--line);
            border-radius: var(--radius-sm); color: var(--text); font-size: 14px; font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-dim); }
        .book-btn { background: var(--amber); color: #1a1305; border: none; padding: 11px 24px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; flex: 0 0 auto; }
        .book-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .preview-box { margin-top: 16px; padding: 12px; background: var(--ink); border: 1px solid var(--line-soft); border-radius: var(--radius-sm); text-align: center; color: var(--text-dim); font-family: 'JetBrains Mono', monospace; font-size: 13px; }
 
        .action-cards { display: flex; gap: 16px; margin-top: 20px; flex-wrap: wrap; }
        .action-card {
            flex: 1; min-width: 180px; background: var(--panel-alt); border: 1px solid var(--line-soft);
            border-left: 3px solid var(--amber); border-radius: var(--radius-md); padding: 20px;
            text-decoration: none; color: var(--text); transition: all 0.2s ease;
        }
        .action-card:nth-child(2) { border-left-color: var(--teal); }
        .action-card:hover { transform: translateY(-4px); background: #182036; }
        .action-card h3 { font-size: 16px; font-weight: 700; margin: 0 0 6px; }
        .action-card p { font-size: 13px; color: var(--text-dim); margin: 0; line-height: 1.5; }
 
        /* ---------- Sidebar ---------- */
        .sidebar { display: flex; flex-direction: column; gap: 18px; }
        .stat-rail { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat-tile { background: var(--ink); border: 1px solid var(--line-soft); border-radius: 12px; padding: 16px 10px; text-align: center; transition: border-color 0.2s ease; }
        .stat-tile:hover { border-color: var(--amber); }
        .ring { width: 58px; height: 58px; margin: 0 auto 8px; position: relative; }
        .ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
        .ring circle { fill: none; stroke-width: 5; }
        .ring .track { stroke: var(--line); }
        .ring .sweep { stroke-linecap: round; stroke-dasharray: 151; transition: stroke-dashoffset 1s cubic-bezier(0.16,1,0.3,1); }
        .ring-label { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; }
        .stat-tile-label { font-size: 10.5px; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.04em; }
 
        .station-list { display: flex; flex-direction: column; gap: 8px; }
        .station-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: var(--ink); border: 1px solid var(--line-soft); border-radius: 10px; font-size: 13.5px; }
        .station-row .city { color: var(--text-faint); font-size: 12px; }
        .station-tag { font-family: 'JetBrains Mono', monospace; font-size: 11px; padding: 3px 8px; border-radius: 6px; background: var(--teal-dim); color: var(--teal); }
 
        .current-user { font-size: 13px; color: var(--text-dim); }
        .current-user strong { color: var(--text); font-weight: 600; }
 
        .footer { margin-top: 46px; padding: 24px 0; text-align: center; border-top: 1px solid var(--line-soft); color: var(--text-faint); font-size: 12.5px; font-family: 'JetBrains Mono', monospace; letter-spacing: 0.03em; }
 
        @media (max-width: 900px) {
            .cockpit { grid-template-columns: 1fr; }
            .body-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .navbar { padding: 14px 20px; }
            .right-section { width: 100%; justify-content: flex-end; margin-top: 10px; }
            .cockpit h1 { font-size: 34px; }
            .form-row { flex-direction: column; gap: 12px; }
            .form-group { min-width: 100%; }
            .book-btn { width: 100%; text-align: center; padding: 13px; }
            .panel-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo-group">
            <div class="logo-badge">TF</div>
            <div class="logo-text">Turbo<span>Fuel</span><p>"The Intelligent Way to Refuel"</p></div>
        </div>
        <div class="right-section">
            <div class="menu">
              <a href="profile.php" class="profile-btn">Profile</a>
            </div>
            <button class="theme-toggle <?php echo ($theme == 'light') ? 'light' : ''; ?>" id="themeToggle" onclick="toggleTheme()" title="Switch theme"></button>
            <div class="nav-menu-container">
                <button class="three-dot-btn" onclick="toggleDotMenu()" title="More options">
                    <span></span><span></span><span></span>
                </button>
                <div class="dot-menu-dropdown" id="dotMenu">
                    <a href="route_finder.php" class="dot-menu-item">Route Finder</a>
                    <a href="wait_time.php" class="dot-menu-item">Wait Time</a>
                    <a href="trip_log.php" class="dot-menu-item">Trip Log</a>
                    <a href="price_trends.php" class="dot-menu-item">Price Trends</a>
                    <a href="settings.php" class="dot-menu-item">Settings</a>
                </div>
            </div>
        </div>
    </div>
 
    <div class="wrap">
        <div class="cockpit">
            <div>
                <div class="eyebrow">Live system status: online</div>
                <h1>Welcome <span><?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : "Guest"; ?></span></h1>
                <p class="lede">Smart fuel station wait-time estimation, built for the daily commute. Track prices, book a slot, and skip the line.</p>
                <div class="cockpit-actions">
                    <a href="book_fueling.php" class="btn-primary">Book fueling now</a>
                    <a href="trip_log.php" class="btn-secondary">View trip log</a>
                </div>
            </div>
            <div class="dial-panel">
                <div class="dial-title">Avg wait, nearby stations</div>
                <svg class="dial-svg" viewBox="0 0 200 130">
                    <path d="M 20 115 A 80 80 0 0 1 60 45.72" fill="none" stroke="#2bc8a8" stroke-width="14" stroke-linecap="round"></path>
                    <path d="M 60 45.72 A 80 80 0 0 1 140 45.72" fill="none" stroke="#f2a63d" stroke-width="14" stroke-linecap="round"></path>
                    <path d="M 140 45.72 A 80 80 0 0 1 180 115" fill="none" stroke="#e2584f" stroke-width="14" stroke-linecap="round"></path>
                    <line id="needle" class="needle" x1="100" y1="115" x2="32" y2="115" stroke="#edeff5" stroke-width="3" stroke-linecap="round"></line>
                    <circle cx="100" cy="115" r="6" fill="#edeff5"></circle>
                    <text x="20" y="128" fill="#4e5872" font-size="9" font-family="JetBrains Mono, monospace">0</text>
                    <text x="96" y="24" fill="#4e5872" font-size="9" font-family="JetBrains Mono, monospace">15</text>
                    <text x="172" y="128" fill="#4e5872" font-size="9" font-family="JetBrains Mono, monospace">30</text>
                </svg>
                <div class="dial-readout" id="dialReadout">-<span>min</span></div>
                <div class="dial-sub">Rush-hour multiplier applied</div>
            </div>
        </div>
 
        <div class="ticker">
            <div class="ticker-item">
                <div class="ticker-label"><span class="ticker-dot petrol"></span>Petrol / L</div>
                <div class="ticker-value">Rs <?php echo $fuelPrices['petrol']; ?></div>
            </div>
            <div class="ticker-item">
                <div class="ticker-label"><span class="ticker-dot diesel"></span>Diesel / L</div>
                <div class="ticker-value">Rs <?php echo $fuelPrices['diesel']; ?></div>
            </div>
            <div class="ticker-item">
                <div class="ticker-label"><span class="ticker-dot cng"></span>CNG / Kg</div>
                <div class="ticker-value">Rs <?php echo $fuelPrices['cng']; ?></div>
            </div>
        </div>
 
        <div class="body-grid">
            <div>
                <div class="panel-card">
                    <h2>Quick booking</h2>
                    <p class="subtext">Prepaid slot &mdash; skip the line at the pump.</p>
                    <form id="quickBookingForm" method="POST" action="book_fueling.php">
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
                        <div class="form-row">
                            <div class="form-group">
                                <label>Station</label>
                                <select name="station_name" id="stationSelect" onchange="updateCity()">
                                    <?php echo $stationOptions; ?>
                                </select>
                                <input type="hidden" name="city" id="cityInput" value="<?php echo htmlspecialchars($demoCity); ?>">
                            </div>
                            <div class="form-group">
                                <label>Fuel type</label>
                                <select name="fuel_type" id="fuelType" onchange="updateQuickPreview()">
                                    <option value="petrol">Petrol</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="cng">CNG</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Litres</label>
                                <input type="number" name="litres" id="litresInput" placeholder="e.g., 20" step="0.1" min="1" required onkeyup="updateQuickPreview()">
                            </div>
                            <div class="form-group" style="flex:0 0 auto;">
                                <button type="submit" name="book" class="book-btn">Book now</button>
                            </div>
                        </div>
                        <input type="hidden" name="price_per_unit" id="pricePerUnit" value="104.23">
                        <div class="preview-box" id="quickPreview">Total cost: Rs. 0.00 | Estimated wait: 3-8 min</div>
                    </form>
                </div>
 
                <div class="action-cards">
                    <a href="book_fueling.php" class="action-card">
                        <h3>Book fueling</h3>
                        <p>Reserve a prepaid fueling slot now.</p>
                    </a>
                    <a href="history.php" class="action-card">
                        <h3>Fuel history</h3>
                        <p>View all previous fuel estimations and activities.</p>
                    </a>
                </div>
            </div>
 
            <div class="sidebar">
                <div class="panel-card" style="padding: 22px;">
                    <h2 style="font-size:16px;">Instrument cluster</h2>
                    <p class="subtext">Your activity at a glance.</p>
                    <div class="stat-rail">
                        <?php
                        $railDefs = [
                            ['value' => $stats['total_trips'], 'label' => 'Trips', 'color' => '#f2a63d'],
                            ['value' => $stats['total_km'] . ' km', 'label' => 'Distance', 'color' => '#2bc8a8'],
                            ['value' => 'Rs ' . number_format($stats['total_spent'], 0), 'label' => 'Spent', 'color' => '#f2a63d'],
                            ['value' => $stats['saved_stations'], 'label' => 'Stations', 'color' => '#2bc8a8'],
                        ];
                        foreach ($railDefs as $i => $rd):
                            $sweep = $ringSweeps[$i % count($ringSweeps)];
                            $offset = 151 - (151 * $sweep / 100);
                        ?>
                        <div class="stat-tile">
                            <div class="ring">
                                <svg viewBox="0 0 60 60">
                                    <circle class="track" cx="30" cy="30" r="24"></circle>
                                    <circle class="sweep" cx="30" cy="30" r="24" stroke="<?php echo $rd['color']; ?>" style="stroke-dashoffset: <?php echo $offset; ?>;"></circle>
                                </svg>
                                <div class="ring-label mono"><?php echo htmlspecialchars($rd['value']); ?></div>
                            </div>
                            <div class="stat-tile-label"><?php echo htmlspecialchars($rd['label']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="current-user" style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line-soft);">
                        <strong>Current user:</strong> <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : "Guest"; ?>
                        &middot; <?php echo $stats['recent_bookings']; ?> booking(s) this week
                    </div>
                </div>
 
                <div class="panel-card" style="padding: 22px;">
                    <h2 style="font-size:16px;">Saved stations</h2>
                    <p class="subtext">Quick access for booking.</p>
                    <div class="station-list">
                        <?php if (count($stationRows) > 0): foreach ($stationRows as $sr): ?>
                        <div class="station-row">
                            <div><?php echo htmlspecialchars($sr['station_name']); ?><div class="city"><?php echo htmlspecialchars($sr['city']); ?></div></div>
                            <span class="station-tag">nearby</span>
                        </div>
                        <?php endforeach; else: ?>
                        <div class="station-row"><div>No saved stations yet<div class="city">Add one from Route Finder</div></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
 
        <div class="footer">TURBOFUEL &copy; 2026 &nbsp;/&nbsp; FUEL STATION WAITING TIME ESTIMATION SYSTEM</div>
    </div>
 
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
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('turbo_theme', next);
            const toggleBtn = document.getElementById('themeToggle');
            if (next === 'light') {
                toggleBtn.classList.add('light');
            } else {
                toggleBtn.classList.remove('light');
            }
            fetch('update_theme.php?theme=' + next);
        }
        const quickPrices = { petrol: 104.23, diesel: 92.15, cng: 76.50 };
        const baseWait = { petrol: [3,8], diesel: [5,12], cng: [10,25] };
        const vehicles = {
            bike:  { tank: 5,  fuel: 'petrol', waitFactor: 0.7 },
            car:   { tank: 20, fuel: 'petrol', waitFactor: 1.0 },
            truck: { tank: 80, fuel: 'diesel', waitFactor: 1.6 }
        };
        let currentVehicle = 'bike';
        const multiplier = (() => {
            const h = new Date().getHours();
            return (h >= 8 && h <= 10 || h >= 17 && h <= 20) ? 1.8 : 1.0;
        })();
        function updateCity() {
            const sel = document.getElementById('stationSelect');
            const city = sel.options[sel.selectedIndex].getAttribute('data-city');
            document.getElementById('cityInput').value = city || 'Bangalore';
        }
        function selectVehicle(key) {
            currentVehicle = key;
            document.querySelectorAll('.vehicle-option').forEach(el => el.classList.toggle('active', el.dataset.vehicle === key));
            document.getElementById('vehicleTypeInput').value = key;
            const v = vehicles[key];
            document.getElementById('fuelType').value = v.fuel;
            document.getElementById('litresInput').value = v.tank;
            updateQuickPreview();
            updateDial();
        }
        function updateQuickPreview() {
            const fuel = document.getElementById('fuelType').value;
            const litres = parseFloat(document.getElementById('litresInput').value) || 0;
            const price = quickPrices[fuel];
            document.getElementById('pricePerUnit').value = price;
            const total = litres * price;
            const wait = baseWait[fuel];
            const factor = multiplier * vehicles[currentVehicle].waitFactor;
            const minWait = Math.round(wait[0] * factor);
            const maxWait = Math.round(wait[1] * factor);
            document.getElementById('quickPreview').innerHTML =
                `Total cost: Rs. ${total.toFixed(2)} | Estimated wait: ${minWait}-${maxWait} min`;
        }
        function updateDial() {
            const fuel = document.getElementById('fuelType').value;
            const wait = baseWait[fuel];
            const factor = multiplier * vehicles[currentVehicle].waitFactor;
            const avgMin = Math.round(((wait[0] + wait[1]) / 2) * factor);
            const t = Math.min(Math.max(avgMin / 30, 0), 1);
            const deg = t * 180;
            document.getElementById('needle').setAttribute('transform', `rotate(${deg} 100 115)`);
            document.getElementById('dialReadout').innerHTML = avgMin + '<span>min</span>';
        }
        document.querySelectorAll('.vehicle-option').forEach(el => el.addEventListener('click', () => selectVehicle(el.dataset.vehicle)));
        updateCity();
        updateQuickPreview();
        updateDial();
    </script>
    <script>
        const theme = '<?php echo $theme; ?>';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('turbo_theme', theme);
    </script>
    <?php include 'theme.php'; ?>
</body>
</html>