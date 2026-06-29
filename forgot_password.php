<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Turbo Line</title>
    
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

        .forgot-container {
            width: 100%;
            max-width: 450px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .forgot-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .forgot-container::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .forgot-icon {
            font-size: 55px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        .forgot-header {
            position: relative;
            z-index: 1;
            margin-bottom: 10px;
        }

        .forgot-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .forgot-header h1 span {
            color: #f97316;
        }

        .forgot-header p {
            color: #8888a0;
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.6;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
            text-align: left;
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
            margin-bottom: 20px;
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
            letter-spacing: 0.3px;
            position: relative;
            z-index: 1;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
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
            .forgot-container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .forgot-icon {
                font-size: 40px;
            }

            .forgot-header h1 {
                font-size: 20px;
            }

            .input-wrapper input {
                padding: 12px 12px 12px 38px;
            }
        }
    </style>
</head>
<body>
    
    <div class="forgot-container">
        
        <!-- Icon -->
        <div class="forgot-icon">🔑</div>
        
        <!-- Header -->
        <div class="forgot-header">
            <h1>Turbo<span>Line</span></h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>
        
        <!-- Form -->
        <form method="POST" action="send_reset_link.php">
            <div class="input-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Send Reset Link</button>
        </form>
        
        <!-- Back to Login -->
        <a href="login.php" class="back-link">⬅ Back to Login</a>
        
    </div>

</body>
</html>