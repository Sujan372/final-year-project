<?php
session_start();




// Store in variables with fallback
$fullname = $_SESSION['fullname'] ?? 'Unknown User';
$email = $_SESSION['email'] ?? 'No Email';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Turbo Line</title>
    
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .profile-container {
            width: 100%;
            max-width: 550px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .profile-container::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 10px 50px rgba(249, 115, 22, 0.5); }
        }

        .profile-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .profile-header .badge {
            display: inline-block;
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            margin-top: 8px;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .profile-info {
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .info-card {
            background: #222240;
            border: 1px solid #2a2a4a;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            border-color: #f97316;
            background: #252548;
            transform: translateX(5px);
        }

        .info-icon {
            width: 44px;
            height: 44px;
            background: rgba(249, 115, 22, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            color: #8888a0;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
            word-break: break-all;
        }

        .profile-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .action-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            background: #222240;
            border: 1px solid #2a2a4a;
            border-radius: 12px;
            color: #c8c8d8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-link:hover {
            background: #252548;
            border-color: #f97316;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .action-link .link-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .action-link.logout {
            grid-column: 1 / -1;
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
            justify-content: center;
            font-weight: 600;
        }

        .action-link.logout:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: #fca5a5;
        }

        .back-link {
            display: block;
            text-align: center;
            color: #8888a0;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
            z-index: 1;
        }

        .back-link:hover {
            color: #f97316;
        }

        @media (max-width: 500px) {
            .profile-container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .profile-avatar {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .profile-header h1 {
                font-size: 22px;
            }

            .profile-actions {
                grid-template-columns: 1fr;
            }

            .info-card {
                padding: 12px 14px;
            }

            .info-value {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    
    <div class="profile-container">
        
        <!-- Header -->
        <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <h1>My Profile</h1>
            <span class="badge">🟢 Active</span>
        </div>
        
        <!-- User Info -->
        <div class="profile-info">
            
            <!-- Name -->
            <div class="info-card">
                <div class="info-icon">👤</div>
                <div class="info-content">
                    <p class="info-label">Full Name</p>
                    <p class="info-value"><?php echo htmlspecialchars($fullname); ?></p>
                </div>
            </div>
            
            <!-- Email -->
            <div class="info-card">
                <div class="info-icon">📧</div>
                <div class="info-content">
                    <p class="info-label">Email Address</p>
                    <p class="info-value"><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>
            
        </div>
        
        <!-- Action Links -->
        <div class="profile-actions">
            <a href="edit_profile.php" class="action-link">
                <span class="link-icon">✏️</span> Edit Profile
            </a>
            <a href="change_password.php" class="action-link">
                <span class="link-icon">🔒</span> Change Password
            </a>
            <a href="history.php" class="action-link">
                <span class="link-icon">⛽</span> History
            </a>
            <a href="saved_stations.php" class="action-link">
                <span class="link-icon">⭐</span> Stations
            </a>
            <a href="settings.php" class="action-link">
                <span class="link-icon">⚙️</span> Settings
            </a>
            <a href="support.php" class="action-link">
                <span class="link-icon">❓</span> Support
            </a>
            <a href="logout.php" class="action-link logout">
                <span class="link-icon">🚪</span> Logout
            </a>
        </div>
        
        <!-- Back Link -->
        <a href="index.php" class="back-link">⬅ Back to Home</a>
        
    </div>

</body>
</html>