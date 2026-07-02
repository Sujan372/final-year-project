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

include("db.php");

$userId = $_SESSION['user_id'];
$message = "";
$error = "";

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
    $message = "Settings saved successfully.";
}

// Success message from redirect
if (isset($_GET['saved'])) {
    $message = "Settings updated successfully.";
}

// Handle data export
if (isset($_GET['export'])) {
    $exportStmt = $conn->prepare("SELECT * FROM fuel_history WHERE user_id = ? ORDER BY created_at DESC");
    $exportStmt->bind_param("i", $userId);
    $exportStmt->execute();
    $exportData = $exportStmt->get_result();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="turbofuel_history.csv"');

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

$icon = [
  'settings' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
  'user' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'home' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  'globe' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>',
  'bell' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>',
  'moon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>',
  'sun' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
  'folder' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2Z"/></svg>',
  'download' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
  'trash' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
  'alert' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  'lock' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  'logout' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
  'save' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>',
  'fuel' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h12"/><path d="M4 9h9"/><path d="M4 4h8a1 1 0 0 1 1 1v17H5V5a1 1 0 0 1 1-1Z"/><path d="M14 8h1.5a2 2 0 0 1 2 2v6.5a1.5 1.5 0 0 0 3 0V9.83a2 2 0 0 0-.59-1.42L18 6.5"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: 'Inter', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 32px 20px; color: #0f172a; }
        .container { max-width: 760px; margin: 0 auto; }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 14px; }
        .page-title { display: flex; align-items: center; gap: 12px; }
        .page-title .icon { width: 46px; height: 46px; background: #2563eb; color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .page-title h1 { font-size: 21px; font-weight: 700; color: #0f172a; }
        .page-title h1 span { color: #2563eb; }

        .header-btns { display: flex; gap: 8px; }
        .btn { padding: 9px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; transition: background 0.15s ease, border-color 0.15s ease; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; }
        .btn-outline { background: #ffffff; color: #334155; }
        .btn-outline:hover { border-color: #94a3b8; background: #f8fafc; }
        .btn-primary { background: #2563eb; color: #ffffff; border-color: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }

        .alert { display: flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .settings-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 26px; margin-bottom: 18px; }
        .section-title { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 9px; }
        .section-title svg { color: #2563eb; }
        .section-desc { font-size: 12.5px; color: #64748b; margin-bottom: 20px; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #334155; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .form-row { display: flex; gap: 14px; }
        .form-row .form-group { flex: 1; }

        select, input[type="text"] {
            width: 100%; padding: 11px 13px; background: #ffffff;
            border: 1px solid #cbd5e1; border-radius: 9px; color: #0f172a;
            font-size: 13.5px; font-family: 'Inter', sans-serif;
            transition: border-color 0.15s ease, box-shadow 0.15s ease; outline: none;
        }
        select:focus, input[type="text"]:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }

        .theme-cards { display: flex; gap: 10px; }
        .theme-card {
            flex: 1; padding: 16px 12px; border-radius: 12px;
            text-align: center; text-decoration: none; transition: border-color 0.15s ease, background 0.15s ease;
            border: 1px solid #cbd5e1; background: #f8fafc; cursor: pointer; color: #0f172a;
        }
        .theme-card:hover { border-color: #94a3b8; }
        .theme-card.active { border-color: #2563eb; background: #eff6ff; }
        .theme-card .theme-icon { display: flex; justify-content: center; margin-bottom: 6px; color: #2563eb; }
        .theme-card .theme-label { font-weight: 600; font-size: 13px; }
        .theme-card .theme-badge { display: flex; align-items: center; justify-content: center; gap: 4px; color: #16a34a; font-size: 11px; margin-top: 6px; font-weight: 500; }

        .toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-info h4 { font-size: 13.5px; font-weight: 500; color: #0f172a; margin-bottom: 2px; }
        .toggle-info p { font-size: 12px; color: #64748b; }

        .toggle-switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #cbd5e1; border-radius: 26px; transition: background 0.15s ease; }
        .toggle-slider::before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: #ffffff; border-radius: 50%; transition: transform 0.15s ease; }
        .toggle-switch input:checked + .toggle-slider { background: #2563eb; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

        .danger-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 26px; margin-bottom: 18px; }
        .danger-title { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .danger-title.warn svg { color: #dc2626; }
        .danger-desc { font-size: 12.5px; color: #64748b; margin-bottom: 18px; }
        .danger-btns { display: flex; gap: 10px; flex-wrap: wrap; }

        .btn-danger { padding: 9px 16px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; }
        .btn-danger:hover { background: #fee2e2; }
        .btn-success { padding: 9px 16px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; }
        .btn-success:hover { background: #dcfce7; }

        .save-btn-full { width: 100%; padding: 13px; font-size: 14.5px; margin-bottom: 18px; justify-content: center; }

        .app-info { text-align: center; padding: 6px; }
        .app-info .app-name { color: #0f172a; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .app-info .app-name svg { color: #2563eb; }
        .app-info .app-tagline { color: #64748b; font-size: 12px; margin-top: 4px; }
        .app-info .app-copy { color: #94a3b8; font-size: 11px; margin-top: 8px; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .settings-card, .danger-card { padding: 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .theme-cards { flex-direction: column; }
            .danger-btns { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <div class="icon"><?php echo $icon['settings']; ?></div>
                <h1>Turbo<span>Fuel</span> Settings</h1>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline"><?php echo $icon['user']; ?> Profile</a>
                <a href="index.php" class="btn btn-outline"><?php echo $icon['home']; ?> Home</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $icon['check']; ?> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="settings.php">
            <div class="settings-card">
                <div class="section-title"><?php echo $icon['globe']; ?> General Settings</div>
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
                            <option value="petrol" <?php if($defaultFuel == 'petrol') echo 'selected'; ?>>Petrol</option>
                            <option value="diesel" <?php if($defaultFuel == 'diesel') echo 'selected'; ?>>Diesel</option>
                            <option value="cng" <?php if($defaultFuel == 'cng') echo 'selected'; ?>>CNG</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Theme Preference</label>
                        <div class="theme-cards">
                            <a href="settings.php?change_theme=dark" class="theme-card <?php if($theme == 'dark') echo 'active'; ?>">
                                <span class="theme-icon"><?php echo $icon['moon']; ?></span>
                                <span class="theme-label">Dark Mode</span>
                                <?php if($theme == 'dark'): ?>
                                    <div class="theme-badge"><?php echo $icon['check']; ?> Active</div>
                                <?php endif; ?>
                            </a>
                            <a href="settings.php?change_theme=light" class="theme-card <?php if($theme == 'light') echo 'active'; ?>">
                                <span class="theme-icon"><?php echo $icon['sun']; ?></span>
                                <span class="theme-label">Light Mode</span>
                                <?php if($theme == 'light'): ?>
                                    <div class="theme-badge"><?php echo $icon['check']; ?> Active</div>
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

            <div class="settings-card">
                <div class="section-title"><?php echo $icon['bell']; ?> Notification Preferences</div>
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
                <?php echo $icon['save']; ?> Save All Settings
            </button>
        </form>

        <div class="danger-card">
            <div class="danger-title"><?php echo $icon['folder']; ?> Data &amp; Privacy</div>
            <div class="danger-desc">Manage your data. Some actions cannot be undone.</div>
            <div class="danger-btns">
                <a href="settings.php?export=1" class="btn-success"><?php echo $icon['download']; ?> Export My Data (CSV)</a>
                <a href="history.php?clear_all=1" class="btn-danger" onclick="return confirm('Delete ALL fuel history?')"><?php echo $icon['trash']; ?> Clear All History</a>
            </div>
        </div>

        <div class="danger-card">
            <div class="danger-title warn"><?php echo $icon['alert']; ?> Account</div>
            <div class="danger-desc">Manage your account settings</div>
            <div class="danger-btns">
                <a href="change_password.php" class="btn btn-outline"><?php echo $icon['lock']; ?> Change Password</a>
                <a href="logout.php" class="btn-danger"><?php echo $icon['logout']; ?> Logout</a>
            </div>
        </div>

        <div class="settings-card">
            <div class="app-info">
                <p class="app-name"><?php echo $icon['fuel']; ?> TurboFuel v1.0</p>
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