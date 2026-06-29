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

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM saved_stations WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $deleteId, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: saved_stations.php?msg=deleted");
    exit();
}

// Handle toggle favorite
if (isset($_GET['favorite'])) {
    $favId = intval($_GET['favorite']);
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
        $error = "Station name and city are required!";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO saved_stations (user_id, station_name, address, city, fuel_types, rating, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("issssis", $userId, $stationName, $address, $city, $fuelTypes, $rating, $notes);
        
        if ($insertStmt->execute()) {
            header("Location: saved_stations.php?msg=saved");
            exit();
        } else {
            $error = "Something went wrong! Please try again.";
        }
        $insertStmt->close();
    }
}

// Success messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $message = "Station removed!";
    if ($_GET['msg'] == 'saved') $message = "Station saved successfully!";
}

// Fetch all saved stations
$stationsResult = $conn->query("SELECT * FROM saved_stations WHERE user_id = $userId ORDER BY is_favorite DESC, created_at DESC");
$stations = [];
while ($row = $stationsResult->fetch_assoc()) {
    $stations[] = $row;
}

// Count stats
$totalStations = count($stations);
$favoriteCount = 0;
foreach ($stations as $s) {
    if ($s['is_favorite']) $favoriteCount++;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Stations - Turbo Line</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* ========== HEADER ========== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title .icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        .page-title h1 span {
            color: #f97316;
        }

        .header-btns {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #2a2a4a;
            font-family: 'Inter', sans-serif;
        }

        .btn-outline {
            background: #222240;
            color: #a0a0b8;
        }

        .btn-outline:hover {
            border-color: #f97316;
            color: #ffffff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #ffffff;
            border-color: #f97316;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        /* ========== MESSAGE ========== */
        .message {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .message-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* ========== STATS ROW ========== */
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-mini {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-mini .stat-num {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
        }

        .stat-mini .stat-text {
            font-size: 12px;
            color: #8888a0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========== STATIONS GRID ========== */
        .stations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .station-card {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 18px;
            padding: 22px;
            transition: all 0.3s ease;
            position: relative;
        }

        .station-card:hover {
            border-color: #f97316;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
        }

        .station-card.favorite {
            border-color: rgba(250, 204, 21, 0.4);
            box-shadow: 0 0 20px rgba(250, 204, 21, 0.05);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .station-name {
            font-size: 17px;
            font-weight: 600;
            color: #ffffff;
            margin-right: 10px;
        }

        .card-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #222240;
            border: 1px solid #2a2a4a;
        }

        .action-icon:hover {
            transform: scale(1.1);
        }

        .action-icon.fav {
            color: #facc15;
        }

        .action-icon.fav:hover {
            background: rgba(250, 204, 21, 0.15);
            border-color: #facc15;
        }

        .action-icon.fav.active {
            background: rgba(250, 204, 21, 0.2);
            border-color: #facc15;
        }

        .action-icon.delete {
            color: #f87171;
        }

        .action-icon.delete:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }

        .station-address {
            color: #8888a0;
            font-size: 13px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .station-city {
            color: #6a6a8a;
            font-size: 12px;
            margin-bottom: 12px;
        }

        .fuel-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .fuel-tag {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .tag-petrol { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .tag-diesel { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .tag-cng { background: rgba(34, 197, 94, 0.15); color: #4ade80; }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .star {
            font-size: 16px;
        }

        .star.filled { color: #facc15; }
        .star.empty { color: #3a3a5a; }

        .station-notes {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #222240;
            font-size: 12px;
            color: #6a6a8a;
            font-style: italic;
        }

        /* ========== ADD FORM ========== */
        .form-card {
            background: #1a1a2e;
            border: 2px dashed #2a2a4a;
            border-radius: 18px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .form-card:hover {
            border-color: #f97316;
        }

        .form-card h3 {
            color: #ffffff;
            font-size: 18px;
            margin-bottom: 8px;
            text-align: center;
        }

        .form-card .form-subtitle {
            color: #8888a0;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #c0c0d0;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: #222240;
            border: 2px solid #2a2a4a;
            border-radius: 10px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #5a5a7a;
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #c8c8d8;
            font-size: 13px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            accent-color: #f97316;
            width: 16px;
            height: 16px;
        }

        .star-rating {
            display: flex;
            gap: 4px;
            font-size: 24px;
            cursor: pointer;
        }

        .star-rating .star-input {
            color: #3a3a5a;
            transition: color 0.2s;
        }

        .star-rating .star-input:hover,
        .star-rating .star-input.active {
            color: #facc15;
        }

        .submit-row {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 10px;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #1a1a2e;
            border-radius: 20px;
            border: 1px solid #2a2a4a;
        }

        .empty-icon {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #ffffff;
            font-size: 18px;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #8888a0;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stations-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .header-btns {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="icon">⭐</div>
                <h1>Turbo<span>Line</span> Stations</h1>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">👤 Profile</a>
                <a href="index.php" class="btn btn-outline">🏠 Home</a>
                <a href="#add-form" class="btn btn-primary">➕ Add Station</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message message-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message message-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <?php if ($totalStations > 0): ?>
            <div class="stats-row">
                <div class="stat-mini">
                    <span>📍</span>
                    <div>
                        <div class="stat-num"><?php echo $totalStations; ?></div>
                        <div class="stat-text">Saved Stations</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <span>⭐</span>
                    <div>
                        <div class="stat-num"><?php echo $favoriteCount; ?></div>
                        <div class="stat-text">Favorites</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Stations Grid -->
        <?php if (!empty($stations)): ?>
            <div class="stations-grid">
                <?php foreach ($stations as $station): ?>
                    <div class="station-card <?php echo $station['is_favorite'] ? 'favorite' : ''; ?>">
                        <div class="card-header">
                            <h3 class="station-name">
                                <?php if ($station['is_favorite']): ?>⭐ <?php endif; ?>
                                <?php echo htmlspecialchars($station['station_name']); ?>
                            </h3>
                            <div class="card-actions">
                                <a href="saved_stations.php?favorite=<?php echo $station['id']; ?>" class="action-icon fav <?php echo $station['is_favorite'] ? 'active' : ''; ?>" title="Toggle Favorite">⭐</a>
                                <a href="saved_stations.php?delete=<?php echo $station['id']; ?>" class="action-icon delete" onclick="return confirm('Remove this station?')" title="Delete">🗑️</a>
                            </div>
                        </div>
                        
                        <?php if (!empty($station['address'])): ?>
                            <div class="station-address">📍 <?php echo htmlspecialchars($station['address']); ?></div>
                        <?php endif; ?>
                        
                        <div class="station-city">🏙️ <?php echo htmlspecialchars($station['city']); ?></div>
                        
                        <?php if (!empty($station['fuel_types'])): ?>
                            <div class="fuel-tags">
                                <?php 
                                $types = explode(', ', $station['fuel_types']);
                                foreach ($types as $type): 
                                    $tagClass = 'tag-' . strtolower(trim($type));
                                ?>
                                    <span class="fuel-tag <?php echo $tagClass; ?>"><?php echo trim($type); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($station['rating'] > 0): ?>
                            <div class="rating-row">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo ($i <= $station['rating']) ? 'filled' : 'empty'; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($station['notes'])): ?>
                            <div class="station-notes">💬 <?php echo htmlspecialchars($station['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📍</div>
                <h3>No Saved Stations Yet</h3>
                <p>Save your favorite fuel stations for quick access.</p>
            </div>
        <?php endif; ?>
        
        <!-- Add Station Form -->
        <div class="form-card" id="add-form">
            <h3>➕ Add New Station</h3>
            <p class="form-subtitle">Save a fuel station for future reference</p>
            
            <form method="POST" action="saved_stations.php#add-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Station Name *</label>
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
                    <label>Fuel Types Available</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="fuel_types[]" value="Petrol"> Petrol
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="fuel_types[]" value="Diesel"> Diesel
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="fuel_types[]" value="CNG"> CNG
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rating</label>
                        <select name="rating">
                            <option value="0">No Rating</option>
                            <option value="1">⭐ 1 Star</option>
                            <option value="2">⭐⭐ 2 Stars</option>
                            <option value="3">⭐⭐⭐ 3 Stars</option>
                            <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                            <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any notes about this station..."></textarea>
                </div>
                
                <div class="submit-row">
                    <button type="submit" name="add_station" class="btn btn-primary">💾 Save Station</button>
                </div>
            </form>
        </div>
        
    </div>

</body>
</html>