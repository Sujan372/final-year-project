<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ========== DATABASE CONFIGURATION ==========
$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// FIX: cast to int so this value can never be used to inject SQL, since it
// is used inside a query string below.
$userId = (int) $_SESSION['user_id'];
$message = "";
$error = "";

// FIX: delete and favorite-toggle used to run on a plain GET request
// (saved_stations.php?delete=5), which meant either action could be
// triggered by a browser prefetching the link, a crawler following it, or
// a forged request from another page (CSRF) — no click required, no
// confirmation needed. Both are now POST-only actions.

// Handle delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $deleteId = intval($_POST['delete_id']);
    $deleteStmt = $conn->prepare("DELETE FROM saved_stations WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $deleteId, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: saved_stations.php?msg=deleted");
    exit();
}

// Handle toggle favorite
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['favorite_id'])) {
    $favId = intval($_POST['favorite_id']);
    $toggleStmt = $conn->prepare("UPDATE saved_stations SET is_favorite = NOT is_favorite WHERE id = ? AND user_id = ?");
    $toggleStmt->bind_param("ii", $favId, $userId);
    $toggleStmt->execute();
    $toggleStmt->close();
    header("Location: saved_stations.php");
    exit();
}

// Handle add new station
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_station'])) {

    $stationName = trim($_POST['station_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $fuelTypes = isset($_POST['fuel_types']) ? implode(', ', $_POST['fuel_types']) : '';
    $rating = intval($_POST['rating']);
    $notes = trim($_POST['notes']);

    if (empty($stationName) || empty($city)) {
        $error = "Station name and city are required.";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO saved_stations (user_id, station_name, address, city, fuel_types, rating, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("issssis", $userId, $stationName, $address, $city, $fuelTypes, $rating, $notes);

        if ($insertStmt->execute()) {
            header("Location: saved_stations.php?msg=saved");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $insertStmt->close();
    }
}

// Success messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $message = "Station removed.";
    if ($_GET['msg'] == 'saved') $message = "Station saved successfully.";
}

// Fetch all saved stations
$stmt = $conn->prepare("SELECT * FROM saved_stations WHERE user_id = ? ORDER BY is_favorite DESC, created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stationsResult = $stmt->get_result();

$stations = [];
// FIX: fetch_assoc() was called on $stationsResult without checking it was
// a valid result first — if the query ever failed, this would be a fatal
// error and the whole page would go blank.
if ($stationsResult) {
    while ($row = $stationsResult->fetch_assoc()) {
        $stations[] = $row;
    }
}
$stmt->close();

// Count stats
$totalStations = count($stations);
$favoriteCount = 0;
foreach ($stations as $s) {
    if ($s['is_favorite']) $favoriteCount++;
}

$conn->close();

$fuelColorClass = ['petrol' => 'tag-petrol', 'diesel' => 'tag-diesel', 'cng' => 'tag-cng'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Stations - TurboFuel</title>
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
            --azure-dim: rgba(91, 141, 239, 0.12);
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
        h1, h2, h3, .display { font-family: 'Rajdhani', 'Inter', sans-serif; letter-spacing: -0.01em; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .container { max-width: 1080px; margin: 0 auto; }
        .icon { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 1.7; flex-shrink: 0; }

        /* ---------- Header ---------- */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .page-title { display: flex; align-items: center; gap: 14px; }
        .page-title .badge {
            width: 46px; height: 46px; border-radius: 14px;
            background: var(--amber-dim); border: 1px solid var(--amber);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .page-title .badge svg { width: 22px; height: 22px; stroke: var(--amber); fill: none; stroke-width: 1.6; }
        .page-title h1 { font-size: 24px; font-weight: 700; margin: 0; }
        .page-title h1 span { color: var(--amber); }
        .page-title .eyebrow { display: block; font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--teal); margin-bottom: 3px; }

        .header-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 20px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
            text-decoration: none; transition: all 0.2s ease; cursor: pointer; border: 1px solid var(--line);
            font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 8px; background: var(--panel); color: var(--text-dim);
        }
        .btn-outline:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-dim); }
        .btn-primary { background: var(--amber); color: #1a1305; border-color: var(--amber); }
        .btn-primary:hover { filter: brightness(1.08); transform: translateY(-1px); }

        /* ---------- Messages ---------- */
        .message { padding: 12px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .message-success { background: var(--teal-dim); border: 1px solid rgba(43, 200, 168, 0.3); color: var(--teal); }
        .message-error { background: var(--red-dim); border: 1px solid rgba(226, 88, 79, 0.3); color: var(--red); }

        /* ---------- Stats ---------- */
        .stats-row { display: flex; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-mini { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-md); padding: 15px 20px; display: flex; align-items: center; gap: 12px; min-width: 150px; }
        .stat-mini svg { width: 20px; height: 20px; stroke: var(--amber); fill: none; stroke-width: 1.7; }
        .stat-mini:nth-child(2) svg { stroke: var(--teal); }
        .stat-mini .stat-num { font-family: 'JetBrains Mono', monospace; font-size: 24px; font-weight: 600; color: var(--text); }
        .stat-mini .stat-text { font-size: 11px; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }

        /* ---------- Stations grid ---------- */
        .stations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 30px; }
        .station-card {
            background: var(--panel); border: 1px solid var(--line-soft); border-left: 3px solid var(--line);
            border-radius: var(--radius-lg); padding: 22px; transition: all 0.2s ease; position: relative;
        }
        .station-card:hover { border-color: var(--line-soft); border-left-color: var(--teal); transform: translateY(-3px); box-shadow: 0 16px 34px rgba(0,0,0,0.35); }
        .station-card.favorite { border-left-color: var(--amber); }

        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .station-name { font-size: 16.5px; font-weight: 700; color: var(--text); margin: 0; display: flex; align-items: center; gap: 7px; }
        .station-name svg { width: 15px; height: 15px; stroke: var(--amber); fill: var(--amber); flex-shrink: 0; }

        .card-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .icon-form { margin: 0; }
        .action-icon {
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            background: var(--ink); border: 1px solid var(--line); cursor: pointer; transition: all 0.2s ease;
        }
        .action-icon svg { width: 15px; height: 15px; stroke: var(--text-dim); fill: none; stroke-width: 1.7; }
        .action-icon.fav:hover, .action-icon.fav.active { border-color: var(--amber); background: var(--amber-dim); }
        .action-icon.fav.active svg { stroke: var(--amber); fill: var(--amber); }
        .action-icon.fav:hover svg { stroke: var(--amber); }
        .action-icon.delete:hover { border-color: var(--red); background: var(--red-dim); }
        .action-icon.delete:hover svg { stroke: var(--red); }

        .station-address, .station-city { color: var(--text-dim); font-size: 13px; margin-bottom: 6px; display: flex; align-items: center; gap: 7px; }
        .station-address svg, .station-city svg { width: 13px; height: 13px; stroke: var(--text-faint); fill: none; stroke-width: 1.7; flex-shrink: 0; }
        .station-city { color: var(--text-faint); font-size: 12px; margin-bottom: 12px; }

        .fuel-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
        .fuel-tag { padding: 3px 10px; border-radius: 12px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .tag-petrol { background: var(--amber-dim); color: var(--amber); }
        .tag-diesel { background: var(--teal-dim); color: var(--teal); }
        .tag-cng { background: var(--azure-dim); color: var(--azure); }

        .rating-row { display: flex; align-items: center; gap: 3px; }
        .rating-row svg { width: 15px; height: 15px; }
        .star-filled { stroke: var(--amber); fill: var(--amber); }
        .star-empty { stroke: var(--line); fill: none; }

        .station-notes { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line-soft); font-size: 12.5px; color: var(--text-faint); font-style: italic; }

        /* ---------- Add form ---------- */
        .form-card { background: var(--panel); border: 1px dashed var(--line); border-radius: var(--radius-lg); padding: 28px; transition: border-color 0.2s ease; }
        .form-card:hover { border-color: var(--teal); }
        .form-card h3 { color: var(--text); font-size: 18px; margin: 0 0 6px; text-align: center; }
        .form-card .form-subtitle { color: var(--text-faint); font-size: 13px; text-align: center; margin: 0 0 22px; }

        .form-row { display: flex; gap: 14px; margin-bottom: 0; }
        .form-group { flex: 1; margin-bottom: 16px; }
        .form-group label {
            display: block; color: var(--text-faint); font-size: 11px; font-weight: 500; margin-bottom: 7px;
            text-transform: uppercase; letter-spacing: 0.08em; font-family: 'JetBrains Mono', monospace;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 11px 13px; background: var(--ink); border: 1px solid var(--line);
            border-radius: var(--radius-sm); color: var(--text); font-size: 14px; font-family: 'Inter', sans-serif;
            transition: all 0.2s ease; outline: none; resize: vertical;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-dim); }
        .form-group input::placeholder, .form-group textarea::placeholder { color: var(--text-faint); }

        .checkbox-group { display: flex; gap: 16px; flex-wrap: wrap; }
        .checkbox-label { display: flex; align-items: center; gap: 7px; color: var(--text-dim); font-size: 13px; cursor: pointer; }
        .checkbox-label input[type="checkbox"] { accent-color: var(--amber); width: 15px; height: 15px; }

        .submit-row { display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px; }

        /* ---------- Empty state ---------- */
        .empty-state { text-align: center; padding: 50px 20px; background: var(--panel); border-radius: var(--radius-lg); border: 1px solid var(--line-soft); margin-bottom: 30px; }
        .empty-state svg { width: 40px; height: 40px; stroke: var(--text-faint); fill: none; stroke-width: 1.4; margin-bottom: 14px; }
        .empty-state h3 { color: var(--text); font-size: 17px; margin: 0 0 8px; }
        .empty-state p { color: var(--text-faint); font-size: 13.5px; margin: 0; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .stations-grid { grid-template-columns: 1fr; }
            .form-row { flex-direction: column; gap: 0; }
            .header-btns { width: 100%; }
            .form-card { padding: 22px; }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="page-header">
            <div class="page-title">
                <div class="badge">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 14.5 9H9.5L12 2Z"></path><path d="m12 22 2.5-7h-5L12 22Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </div>
                <div>
                    <span class="eyebrow">Station roster</span>
                    <h1>Turbo<span>Fuel</span> Stations</h1>
                </div>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">
                    <svg class="icon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"></path></svg>
                    Profile
                </a>
                <a href="index.php" class="btn btn-outline">
                    <svg class="icon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"></path><path d="M5 10v10h14V10"></path></svg>
                    Home
                </a>
                <a href="#add-form" class="btn btn-primary">
                    <svg class="icon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"></path></svg>
                    Add station
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($totalStations > 0): ?>
            <div class="stats-row">
                <div class="stat-mini">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                    <div>
                        <div class="stat-num"><?php echo $totalStations; ?></div>
                        <div class="stat-text">Saved stations</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 14.5 9H9.5L12 2Z"></path><path d="m12 22 2.5-7h-5L12 22Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <div>
                        <div class="stat-num"><?php echo $favoriteCount; ?></div>
                        <div class="stat-text">Favorites</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($stations)): ?>
            <div class="stations-grid">
                <?php foreach ($stations as $station): ?>
                    <div class="station-card <?php echo $station['is_favorite'] ? 'favorite' : ''; ?>">
                        <div class="card-header">
                            <h3 class="station-name">
                                <?php if ($station['is_favorite']): ?>
                                <svg viewBox="0 0 24 24"><path d="M12 2 14.5 9H21l-5.2 4.2L17.8 20 12 15.9 6.2 20l2-6.8L3 9h6.5Z"></path></svg>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($station['station_name']); ?>
                            </h3>
                            <div class="card-actions">
                                <form class="icon-form" method="POST" action="saved_stations.php">
                                    <input type="hidden" name="favorite_id" value="<?php echo (int) $station['id']; ?>">
                                    <button type="submit" class="action-icon fav <?php echo $station['is_favorite'] ? 'active' : ''; ?>" title="Toggle favorite">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 14.5 9H21l-5.2 4.2L17.8 20 12 15.9 6.2 20l2-6.8L3 9h6.5Z"></path></svg>
                                    </button>
                                </form>
                                <form class="icon-form" method="POST" action="saved_stations.php" onsubmit="return confirm('Remove this station?')">
                                    <input type="hidden" name="delete_id" value="<?php echo (int) $station['id']; ?>">
                                    <button type="submit" class="action-icon delete" title="Delete">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"></path><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path><path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($station['address'])): ?>
                            <div class="station-address">
                                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                                <?php echo htmlspecialchars($station['address']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="station-city">
                            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V9l6-4 6 4v12"></path><path d="M14 21v-6h4v6"></path><path d="M8 12h.01M8 15h.01M8 18h.01"></path></svg>
                            <?php echo htmlspecialchars($station['city']); ?>
                        </div>

                        <?php if (!empty($station['fuel_types'])): ?>
                            <div class="fuel-tags">
                                <?php
                                $types = explode(', ', $station['fuel_types']);
                                foreach ($types as $type):
                                    $key = strtolower(trim($type));
                                    $tagClass = $fuelColorClass[$key] ?? 'tag-petrol';
                                ?>
                                    <span class="fuel-tag <?php echo $tagClass; ?>"><?php echo htmlspecialchars(trim($type)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($station['rating'] > 0): ?>
                            <div class="rating-row">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg viewBox="0 0 24 24" class="<?php echo ($i <= $station['rating']) ? 'star-filled' : 'star-empty'; ?>" stroke-width="1.4"><path d="M12 2 14.5 9H21l-5.2 4.2L17.8 20 12 15.9 6.2 20l2-6.8L3 9h6.5Z"></path></svg>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($station['notes'])): ?>
                            <div class="station-notes"><?php echo htmlspecialchars($station['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11Z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
                <h3>No saved stations yet</h3>
                <p>Save your favorite fuel stations for quick access.</p>
            </div>
        <?php endif; ?>

        <div class="form-card" id="add-form">
            <h3>Add new station</h3>
            <p class="form-subtitle">Save a fuel station for future reference</p>

            <form method="POST" action="saved_stations.php#add-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Station name *</label>
                        <input type="text" name="station_name" placeholder="e.g., HP Petrol Pump" required>
                    </div>
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" placeholder="e.g., Bangalore" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" placeholder="Full address of the station">
                </div>

                <div class="form-group">
                    <label>Fuel types available</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label"><input type="checkbox" name="fuel_types[]" value="Petrol"> Petrol</label>
                        <label class="checkbox-label"><input type="checkbox" name="fuel_types[]" value="Diesel"> Diesel</label>
                        <label class="checkbox-label"><input type="checkbox" name="fuel_types[]" value="CNG"> CNG</label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating">
                            <option value="0">No rating</option>
                            <option value="1">1 star</option>
                            <option value="2">2 stars</option>
                            <option value="3">3 stars</option>
                            <option value="4">4 stars</option>
                            <option value="5">5 stars</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any notes about this station..."></textarea>
                </div>

                <div class="submit-row">
                    <button type="submit" name="add_station" class="btn btn-primary">Save station</button>
                </div>
            </form>
        </div>

    </div>
</body>
</html>