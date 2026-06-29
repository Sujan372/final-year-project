<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ========== INITIALIZE SETTINGS ==========
if (!isset($_SESSION['settings'])) {
    $_SESSION['settings'] = [
        'currency' => 'INR',
        'unit' => 'litres',
        'default_fuel' => 'petrol',
        'theme' => 'dark',
        'notifications' => 1,
        'email_alerts' => 0,
        'price_alerts' => 1,
        'default_city' => ''
    ];
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

// Load settings
$settings = $_SESSION['settings'];

// Handle theme change
if (isset($_GET['change_theme'])) {
    $settings['theme'] = ($_GET['change_theme'] == 'light') ? 'light' : 'dark';
    $_SESSION['settings'] = $settings;
    header("Location: settings.php?saved=1");
    exit();
}

// Handle settings update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    
    $settings['currency'] = $_POST['currency'] ?? 'INR';
    $settings['unit'] = $_POST['unit'] ?? 'litres';
    $settings['default_fuel'] = $_POST['default_fuel'] ?? 'petrol';
    $settings['theme'] = $_POST['theme'] ?? 'dark';
    $settings['notifications'] = isset($_POST['notifications']) ? 1 : 0;
    $settings['email_alerts'] = isset($_POST['email_alerts']) ? 1 : 0;
    $settings['price_alerts'] = isset($_POST['price_alerts']) ? 1 : 0;
    $settings['default_city'] = trim($_POST['default_city'] ?? '');
    
    $_SESSION['settings'] = $settings;
    $message = "Settings saved successfully!";
}

// Success message from redirect
if (isset($_GET['saved'])) {
    $message = "Settings updated successfully!";
}

// Handle data export
if (isset($_GET['export'])) {
    $exportStmt = $conn->prepare("SELECT * FROM fuel_history WHERE user_id = ? ORDER BY created_at DESC");
    $exportStmt->bind_param("i", $userId);
    $exportStmt->execute();
    $exportData = $exportStmt->get_result();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="turbo_line_history.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Fuel Type', 'Litres', 'Price/Unit', 'Total Cost', 'Time (min)', 'Station']);
    
    while ($row = $exportData->fetch_assoc()) {
        fputcsv($output, [
            $row['created_at'],
            $row['fuel_type'],
            $row['litres'],
            $row['price_per_unit'],
            $row['total_cost'],
            $row['time_estimated'],
            $row['station_name'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    $exportStmt->close();
    $conn->close();
    exit();
}

$conn->close();

// ========== SAFE VARIABLES FOR FORM ==========
$currency = $settings['currency'] ?? 'INR';
$unit = $settings['unit'] ?? 'litres';
$defaultFuel = $settings['default_fuel'] ?? 'petrol';
$theme = $settings['theme'] ?? 'dark';
$notifications = $settings['notifications'] ?? 1;
$emailAlerts = $settings['email_alerts'] ?? 0;
$priceAlerts = $settings['price_alerts'] ?? 1;
$defaultCity = $settings['default_city'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Turbo Line</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .container { max-width: 800px; margin: 0 auto; }
        
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 25px; flex-wrap: wrap; gap: 15px;
        }
        
        .page-title { display: flex; align-items: center; gap: 12px; }
        
        .page-title .icon {
            width: 50px; height: 50px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 14px; display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }
        
        .page-title h1 { font-size: 24px; font-weight: 700; color: #ffffff; }
        .page-title h1 span { color: #f97316; }
        
        .header-btns { display: flex; gap: 10px; }
        
        .btn {
            padding: 10px 20px; border-radius: 10px; font-size: 14px;
            font-weight: 500; text-decoration: none; transition: all 0.3s ease;
            cursor: pointer; border: 1px solid #2a2a4a; font-family: 'Inter', sans-serif;
            display: inline-flex; align-items: center; gap: 6px;
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
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80;
        }
        
        .settings-card {
            background: #1a1a2e; border: 1px solid #2a2a4a;
            border-radius: 20px; padding: 30px; margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 18px; font-weight: 600; color: #ffffff;
            margin-bottom: 6px; display: flex; align-items: center; gap: 10px;
        }
        
        .section-desc { font-size: 13px; color: #8888a0; margin-bottom: 22px; }
        
        .form-group { margin-bottom: 18px; }
        
        .form-group label {
            display: block; color: #c0c0d0; font-size: 13px; font-weight: 500; margin-bottom: 6px;
        }
        
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        
        select, input[type="text"] {
            width: 100%; padding: 12px 14px; background: #222240;
            border: 2px solid #2a2a4a; border-radius: 10px; color: #ffffff;
            font-size: 14px; font-family: 'Inter', sans-serif;
            transition: all 0.3s ease; outline: none;
        }
        
        select:focus, input[type="text"]:focus {
            border-color: #f97316; box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }
        
        select option { background: #1a1a2e; color: #ffffff; }
        
        .theme-cards { display: flex; gap: 12px; }
        
        .theme-card {
            flex: 1; padding: 20px 15px; border-radius: 14px;
            text-align: center; text-decoration: none; transition: all 0.3s ease;
            border: 2px solid #2a2a4a; background: #222240; cursor: pointer;
        }
        
        .theme-card:hover { transform: translateY(-3px); border-color: #f97316; }
        
        .theme-card.active {
            border-color: #f97316 !important;
            background: rgba(249, 115, 22, 0.1) !important;
            box-shadow: 0 0 25px rgba(249, 115, 22, 0.2);
        }
        
        .theme-card .theme-icon { font-size: 36px; display: block; margin-bottom: 8px; }
        .theme-card .theme-label { color: #fff; font-weight: 600; font-size: 14px; }
        .theme-card .theme-badge { color: #4ade80; font-size: 11px; margin-top: 6px; font-weight: 500; }
        
        .toggle-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0; border-bottom: 1px solid #222240;
        }
        .toggle-row:last-child { border-bottom: none; }
        
        .toggle-info h4 { font-size: 14px; font-weight: 500; color: #ffffff; margin-bottom: 3px; }
        .toggle-info p { font-size: 12px; color: #8888a0; }
        
        .toggle-switch { position: relative; width: 48px; height: 26px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #2a2a4a; border-radius: 26px; transition: all 0.3s ease;
        }
        
        .toggle-slider::before {
            content: ''; position: absolute; height: 20px; width: 20px;
            left: 3px; bottom: 3px; background: #ffffff; border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .toggle-switch input:checked + .toggle-slider { background: #f97316; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }
        
        .danger-card {
            background: #1a1a2e; border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 20px; padding: 30px; margin-bottom: 20px;
        }
        
        .danger-title {
            font-size: 16px; font-weight: 600; color: #f87171;
            margin-bottom: 6px; display: flex; align-items: center; gap: 8px;
        }
        
        .danger-desc { font-size: 13px; color: #8888a0; margin-bottom: 20px; }
        
        .danger-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .btn-danger {
            padding: 10px 20px; background: rgba(239, 68, 68, 0.1); color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px;
            font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none;
            transition: all 0.3s ease; font-family: 'Inter', sans-serif;
        }
        .btn-danger:hover { background: rgba(239, 68, 68, 0.2); border-color: #ef4444; }
        
        .btn-success {
            padding: 10px 20px; background: rgba(34, 197, 94, 0.1); color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px;
            font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none;
            transition: all 0.3s ease; font-family: 'Inter', sans-serif;
        }
        .btn-success:hover { background: rgba(34, 197, 94, 0.2); border-color: #22c55e; }
        
        .save-btn-full { width: 100%; padding: 15px; font-size: 16px; margin-bottom: 20px; }
        
        .app-info { text-align: center; padding: 10px; }
        .app-info .app-name { color: #ffffff; font-weight: 600; font-size: 15px; }
        .app-info .app-tagline { color: #8888a0; font-size: 12px; margin-top: 4px; }
        .app-info .app-copy { color: #6a6a8a; font-size: 11px; margin-top: 8px; }
        
        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .settings-card, .danger-card { padding: 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .theme-cards { flex-direction: column; }
            .danger-btns { flex-direction: column; }
            .danger-btns .btn-danger, .danger-btns .btn-success { text-align: center; }
        }
    </style>
</head>
<body>
    
    <div class="container">
        
        <div class="page-header">
            <div class="page-title">
                <div class="icon">⚙️</div>
                <h1>Turbo<span>Line</span> Settings</h1>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">👤 Profile</a>
                <a href="index.php" class="btn btn-outline">🏠 Home</a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="settings.php">
            
            <!-- General Settings -->
            <div class="settings-card">
                <div class="section-title">🌍 General Settings</div>
                <div class="section-desc">Customize your fuel estimation experience</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency">
                            <option value="INR" <?php if($currency == 'INR') echo 'selected'; ?>>₹ INR - Indian Rupee</option>
                            <option value="USD" <?php if($currency == 'USD') echo 'selected'; ?>>$ USD - US Dollar</option>
                            <option value="EUR" <?php if($currency == 'EUR') echo 'selected'; ?>>€ EUR - Euro</option>
                            <option value="GBP" <?php if($currency == 'GBP') echo 'selected'; ?>>£ GBP - British Pound</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Measurement Unit</label>
                        <select name="unit">
                            <option value="litres" <?php if($unit == 'litres') echo 'selected'; ?>>Litres (L)</option>
                            <option value="gallons" <?php if($unit == 'gallons') echo 'selected'; ?>>Gallons (gal)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Default Fuel Type</label>
                        <select name="default_fuel">
                            <option value="petrol" <?php if($defaultFuel == 'petrol') echo 'selected'; ?>>🛢️ Petrol</option>
                            <option value="diesel" <?php if($defaultFuel == 'diesel') echo 'selected'; ?>>🚛 Diesel</option>
                            <option value="cng" <?php if($defaultFuel == 'cng') echo 'selected'; ?>>🍃 CNG</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Theme Preference</label>
                        <div class="theme-cards">
                            <a href="settings.php?change_theme=dark" class="theme-card <?php if($theme == 'dark') echo 'active'; ?>">
                                <span class="theme-icon">🌙</span>
                                <span class="theme-label">Dark Mode</span>
                                <?php if($theme == 'dark'): ?>
                                    <div class="theme-badge">✅ Active</div>
                                <?php endif; ?>
                            </a>
                            <a href="settings.php?change_theme=light" class="theme-card <?php if($theme == 'light') echo 'active'; ?>">
                                <span class="theme-icon">☀️</span>
                                <span class="theme-label">Light Mode</span>
                                <?php if($theme == 'light'): ?>
                                    <div class="theme-badge">✅ Active</div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Default City for Fuel Prices</label>
                    <input type="text" name="default_city" placeholder="e.g., Bangalore" value="<?php echo htmlspecialchars($defaultCity); ?>">
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="settings-card">
                <div class="section-title">🔔 Notification Preferences</div>
                <div class="section-desc">Control what alerts you receive</div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>Push Notifications</h4>
                        <p>Get notified about price changes and updates</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notifications" <?php if($notifications == 1) echo 'checked'; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>Email Alerts</h4>
                        <p>Receive fuel price updates via email</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_alerts" <?php if($emailAlerts == 1) echo 'checked'; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="toggle-row">
                    <div class="toggle-info">
                        <h4>Price Drop Alerts</h4>
                        <p>Get notified when fuel prices drop in your city</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="price_alerts" <?php if($priceAlerts == 1) echo 'checked'; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            
            <button type="submit" name="save_settings" class="btn btn-primary save-btn-full">
                💾 Save All Settings
            </button>
            
        </form>
        
        <div class="danger-card">
            <div class="danger-title">📂 Data & Privacy</div>
            <div class="danger-desc">Manage your data. Some actions cannot be undone.</div>
            <div class="danger-btns">
                <a href="settings.php?export=1" class="btn-success">📥 Export My Data (CSV)</a>
                <a href="history.php?clear_all=1" class="btn-danger" onclick="return confirm('Delete ALL fuel history?')">🗑️ Clear All History</a>
            </div>
        </div>
        
        <div class="danger-card">
            <div class="danger-title">⚠️ Account</div>
            <div class="danger-desc">Manage your account settings</div>
            <div class="danger-btns">
                <a href="change_password.php" class="btn btn-outline">🔒 Change Password</a>
                <a href="logout.php" class="btn-danger">🚪 Logout</a>
            </div>
        </div>
        
        <div class="settings-card">
            <div class="app-info">
                <p class="app-name">⚡ Turbo Line v1.0</p>
                <p class="app-tagline">Fuel Smarter, Drive Further.</p>
                <p class="app-copy">Final Year Project &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
        
    </div>

    <script>
        const theme = '<?php echo $theme; ?>';
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('turbo_theme', theme);
    </script>
    
    <?php include 'theme.php'; ?>

</body>
</html>