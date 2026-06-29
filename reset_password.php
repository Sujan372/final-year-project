<?php
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
$validToken = false;
$token = $_GET['token'] ?? '';

// Validate token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
        $resetData = $result->fetch_ass();
        $userId = $resetData['user_id'];
    } else {
        $error = "Invalid or expired reset link!";
    }
    $stmt->close();
} else {
    $error = "No reset token provided!";
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match!";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        
        if ($updateStmt->execute()) {
            // Delete used token
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            $success = "Password reset successfully! You can now login.";
            $validToken = false;
        } else {
            $error = "Something went wrong! Please try again.";
        }
        $updateStmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Turbo Line</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
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

        .reset-container {
            width: 100%;
            max-width: 450px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .reset-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .reset-icon {
            font-size: 50px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .reset-header {
            position: relative;
            z-index: 1;
            margin-bottom: 25px;
        }

        .reset-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
        }

        .reset-header h1 span {
            color: #f97316;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
            text-align: left;
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
            text-align: left;
        }

        .input-group label {
            display: block;
            color: #c0c0d0;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
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
            padding: 14px 14px 14px 42px;
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
            font-size: 16px;
        }

        .submit-btn {
            width: 100%;
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
            position: relative;
            z-index: 1;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
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
            .reset-container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .reset-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    
    <div class="reset-container">
        
        <?php if (!empty($error)): ?>
            <div class="reset-icon">❌</div>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="reset-icon">✅</div>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
            <div class="reset-icon">🔒</div>
            <div class="reset-header">
                <h1>Turbo<span>Line</span></h1>
            </div>
            
            <form method="POST">
                <div class="input-group">
                    <label for="password">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" placeholder="Min 6 characters" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">👁️</button>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <a href="login.php" class="back-link">⬅ Back to Login</a>
        
    </div>

    <script>
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
    </script>

</body>
</html>