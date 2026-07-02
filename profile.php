<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'] ?? 'Unknown User';
$email = $_SESSION['email'] ?? 'No Email';

$icon = [
  'user' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'mail' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>',
  'pencil' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>',
  'lock' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
  'fuel' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h12"/><path d="M4 9h9"/><path d="M4 4h8a1 1 0 0 1 1 1v17H5V5a1 1 0 0 1 1-1Z"/><path d="M14 8h1.5a2 2 0 0 1 2 2v6.5a1.5 1.5 0 0 0 3 0V9.83a2 2 0 0 0-.59-1.42L18 6.5"/></svg>',
  'star' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
  'settings' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
  'help' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  'logout' => '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
  'arrow-left' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head_common.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }

        .profile-container {
            width: 100%;
            max-width: 520px;
            background: #ffffff;
            border-radius: 18px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .profile-header { text-align: center; margin-bottom: 30px; }

        .profile-avatar {
            width: 76px;
            height: 76px;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            color: #ffffff;
        }

        .profile-header h1 { font-size: 22px; font-weight: 700; color: #0f172a; }

        .profile-header .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            color: #16a34a;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            margin-top: 8px;
            border: 1px solid #bbf7d0;
        }

        .status-dot { width: 7px; height: 7px; border-radius: 50%; background: #16a34a; }

        .profile-info { margin-bottom: 26px; }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .info-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-content { flex: 1; min-width: 0; }

        .info-label {
            color: #64748b;
            font-size: 11.5px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .info-value {
            color: #0f172a;
            font-size: 14.5px;
            font-weight: 600;
            word-break: break-word;
        }

        .profile-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 22px;
        }

        .action-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 15px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .action-link:hover { border-color: #94a3b8; background: #f8fafc; }
        .action-link svg { color: #2563eb; flex-shrink: 0; }

        .action-link.logout {
            grid-column: 1 / -1;
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
            justify-content: center;
            font-weight: 600;
        }

        .action-link.logout svg { color: #dc2626; }
        .action-link.logout:hover { background: #fee2e2; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
        }

        .back-link:hover { color: #2563eb; }

        @media (max-width: 480px) {
            .profile-container { padding: 30px 24px; border-radius: 14px; }
            .profile-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar"><?php echo $icon['user']; ?></div>
            <h1>My Profile</h1>
            <span class="badge"><span class="status-dot"></span> Active</span>
        </div>

        <div class="profile-info">
            <div class="info-card">
                <div class="info-icon"><?php echo $icon['user']; ?></div>
                <div class="info-content">
                    <p class="info-label">Full Name</p>
                    <p class="info-value"><?php echo htmlspecialchars($fullname); ?></p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon"><?php echo $icon['mail']; ?></div>
                <div class="info-content">
                    <p class="info-label">Email Address</p>
                    <p class="info-value"><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <a href="edit_profile.php" class="action-link"><?php echo $icon['pencil']; ?> Edit Profile</a>
            <a href="change_password.php" class="action-link"><?php echo $icon['lock']; ?> Change Password</a>
            <a href="history.php" class="action-link"><?php echo $icon['fuel']; ?> History</a>
            <a href="saved_stations.php" class="action-link"><?php echo $icon['star']; ?> Stations</a>
            <a href="settings.php" class="action-link"><?php echo $icon['settings']; ?> Settings</a>
            <a href="support.php" class="action-link"><?php echo $icon['help']; ?> Support</a>
            <a href="logout.php" class="action-link logout"><?php echo $icon['logout']; ?> Logout</a>
        </div>

        <a href="index.php" class="back-link"><?php echo $icon['arrow-left']; ?> Back to Home</a>
    </div>
</body>
</html>