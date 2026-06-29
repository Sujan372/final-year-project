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

$error = "";
$success = "";
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required!";
    }
    elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters!";
    }
    elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match!";
    }
    else {
        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // ✅ Check if user exists
        if (!$user) {
            $error = "User not found! Please login again.";
        }
        // Verify current password
        elseif (!password_verify($currentPassword, $user['password'])) {
            $error = "Current password is incorrect!";
        }
        elseif (password_verify($newPassword, $user['password'])) {
            $error = "New password cannot be same as current password!";
        }
        else {
            // Hash new password and update
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            
            if ($updateStmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Something went wrong! Please try again.";
            }
            $updateStmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Turbo Line</title>
    
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

        .password-container {
            width: 100%;
            max-width: 480px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .password-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .password-container::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .password-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .password-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 38px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 10px 50px rgba(249, 115, 22, 0.5); }
        }

        .password-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .password-header h1 span {
            color: #f97316;
        }

        .password-header p {
            color: #8888a0;
            font-size: 14px;
            margin-top: 6px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .input-group {
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .input-group label {
            display: block;
            color: #c0c0d0;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .input-group label .required {
            color: #f97316;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            font-size: 16px;
            z-index: 1;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 45px 14px 42px;
            background: #222240;
            border: 2px solid #2a2a4a;
            border-radius: 12px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
            background: #252548;
        }

        .input-wrapper input::placeholder {
            color: #5a5a7a;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            z-index: 1;
            transition: transform 0.2s;
        }

        .toggle-password:hover {
            transform: scale(1.2);
        }

        .password-hint {
            font-size: 11px;
            color: #6a6a8a;
            margin-top: 5px;
            margin-left: 5px;
            position: relative;
            z-index: 1;
        }

        .btn-row {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            position: relative;
            z-index: 1;
        }

        .save-btn {
            flex: 1;
            padding: 15px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        .save-btn:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .save-btn:active {
            transform: translateY(0);
        }

        .cancel-btn {
            padding: 15px 25px;
            background: #222240;
            color: #a0a0b8;
            font-size: 15px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            border: 2px solid #2a2a4a;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .cancel-btn:hover {
            border-color: #f97316;
            color: #ffffff;
            background: #252548;
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #2a2a4a;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
        }

        .strength-weak { width: 25%; background: #ef4444; }
        .strength-fair { width: 50%; background: #f59e0b; }
        .strength-good { width: 75%; background: #3b82f6; }
        .strength-strong { width: 100%; background: #22c55e; }

        @media (max-width: 500px) {
            .password-container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .password-header h1 {
                font-size: 20px;
            }

            .password-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .btn-row {
                flex-direction: column;
            }

            .cancel-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    
    <div class="password-container">
        
        <!-- Header -->
        <div class="password-header">
            <div class="password-icon">🔒</div>
            <h1>Turbo<span>Line</span></h1>
            <p>Change Your Password</p>
        </div>
        
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Change Password Form -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="passwordForm">
            
            <!-- Current Password -->
            <div class="input-group">
                <label for="current_password">Current Password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">🔑</span>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">👁️</button>
                </div>
            </div>
            
            <!-- New Password -->
            <div class="input-group">
                <label for="new_password">New Password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="6" onkeyup="checkStrength()">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">👁️</button>
                </div>
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <p class="password-hint" id="strengthText">Password strength: --</p>
            </div>
            
            <!-- Confirm New Password -->
            <div class="input-group">
                <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">✅</span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</button>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="btn-row">
                <button type="submit" class="save-btn">🔒 Change Password</button>
                <a href="profile.php" class="cancel-btn">Cancel</a>
            </div>
            
        </form>
        
    </div>

    <script>
        // ========== TOGGLE PASSWORD VISIBILITY ==========
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }
        
        // ========== PASSWORD STRENGTH CHECKER ==========
        function checkStrength() {
            const password = document.getElementById('new_password').value;
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            fill.className = 'strength-fill';
            
            if (password.length === 0) {
                fill.style.width = '0';
                text.textContent = 'Password strength: --';
                text.style.color = '#6a6a8a';
            } else if (strength <= 1) {
                fill.classList.add('strength-weak');
                text.textContent = 'Password strength: Weak';
                text.style.color = '#ef4444';
            } else if (strength === 2) {
                fill.classList.add('strength-fair');
                text.textContent = 'Password strength: Fair';
                text.style.color = '#f59e0b';
            } else if (strength === 3) {
                fill.classList.add('strength-good');
                text.textContent = 'Password strength: Good';
                text.style.color = '#3b82f6';
            } else {
                fill.classList.add('strength-strong');
                text.textContent = 'Password strength: Strong';
                text.style.color = '#22c55e';
            }
        }
        
        // ========== CLIENT-SIDE PASSWORD MATCH CHECK ==========
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>

</body>
</html>