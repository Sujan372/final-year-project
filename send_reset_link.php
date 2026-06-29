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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address!";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } 
    else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_ass();
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            // Store token in database
            $insertToken = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insertToken->bind_param("iss", $user['id'], $token, $expiry);
            
            if ($insertToken->execute()) {
                // Send email
                $resetLink = "http://localhost/TurboFuel/reset_password.php?token=" . $token;
                $to = $email;
                $subject = "Reset Your Password - Turbo Line";
                $message = "
                <html>
                <head><title>Reset Your Password</title></head>
                <body style='font-family: Arial, sans-serif; background: #0f0f1a; padding: 20px;'>
                    <div style='max-width: 500px; margin: 0 auto; background: #1a1a2e; padding: 30px; border-radius: 16px; color: #fff;'>
                        <h2 style='color: #f97316;'>⛽ Turbo Line</h2>
                        <p>Hello <b>{$user['first_name']}</b>,</p>
                        <p>Click the button below to reset your password. This link expires in 1 hour.</p>
                        <a href='{$resetLink}' style='display: inline-block; padding: 14px 30px; background: #f97316; color: #fff; text-decoration: none; border-radius: 10px; font-weight: bold; margin: 20px 0;'>Reset Password</a>
                        <p style='font-size: 12px; color: #888;'>If you didn't request this, please ignore this email.</p>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: Turbo Line <noreply@turboline.com>" . "\r\n";
                
                if (mail($to, $subject, $message, $headers)) {
                    $success = "Reset link sent to your email! Check your inbox.";
                } else {
                    $success = "Reset link sent! (Email simulation mode)";
                }
            } else {
                $error = "Something went wrong! Please try again.";
            }
            
            $insertToken->close();
        } else {
            // Don't reveal if email exists or not (security)
            $success = "If this email is registered, you will receive a reset link.";
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Link Sent - Turbo Line</title>
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

        .status-container {
            width: 100%;
            max-width: 450px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            text-align: center;
        }

        .status-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
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

        .back-link {
            display: inline-block;
            color: #f97316;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #fb923c;
        }
    </style>
</head>
<body>
    
    <div class="status-container">
        
        <?php if (!empty($error)): ?>
            <div class="status-icon">❌</div>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="status-icon">✅</div>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <a href="login.php" class="back-link">⬅ Back to Login</a>
        
    </div>

</body>
</html>