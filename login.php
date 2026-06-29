<?php
session_start();
include("db.php");

$error = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['email'] = $user['email'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid Password";
        }
    } else {
        $error = "Email Not Found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            background: #1a1a2e;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
        }
        .auth-banner {
            width: 45%;
            background: linear-gradient(135deg, #1e1e3a 0%, #16213e 50%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
            position: relative;
            overflow: hidden;
        }
        .auth-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .banner-content { position: relative; z-index: 1; text-align: center; }
        .banner-logo { margin-bottom: 30px; }
        .banner-logo .logo-icon {
            font-size: 60px;
            display: block;
            margin-bottom: 15px;
            animation: bounce 2s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .banner-logo h1 {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .banner-logo h1 span { color: #f97316; }
        .banner-text {
            color: #a0a0b8;
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 35px;
        }
        .banner-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #c8c8d8;
            font-size: 14px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 18px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            background: rgba(249, 115, 22, 0.15);
            color: #ffffff;
            transform: translateX(5px);
        }
        .feature-item span { font-size: 20px; }
        .auth-form-section {
            width: 55%;
            padding: 50px 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a2e;
        }
        .form-wrapper { width: 100%; max-width: 400px; }
        .form-header { margin-bottom: 30px; }
        .form-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .form-header p { color: #8888a0; font-size: 14px; }
        .error-message {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .input-group { margin-bottom: 18px; }
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
        .input-wrapper input::placeholder { color: #5a5a7a; }
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a0a0b8;
            font-size: 13px;
            cursor: pointer;
        }
        .remember-me input[type="checkbox"] {
            accent-color: #f97316;
            width: 16px;
            height: 16px;
        }
        .forgot-link {
            color: #f97316;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .forgot-link:hover { color: #fb923c; }
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
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }
        .social-login { margin-top: 25px; text-align: center; }
        .social-divider {
            color: #6a6a8a;
            font-size: 13px;
            margin-bottom: 16px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .social-divider::before,
        .social-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #2a2a4a;
        }
        .social-btns { display: flex; gap: 12px; justify-content: center; }
        .social-btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 10px;
            border: 2px solid #2a2a4a;
            background: #222240;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .social-btn.google {
            border-color: rgba(234, 67, 53, 0.3);
            color: #ea4335;
        }
        .social-btn.google:hover {
            background: rgba(234, 67, 53, 0.1);
            border-color: #ea4335;
        }
        .social-btn.facebook {
            border-color: rgba(24, 119, 242, 0.3);
            color: #1877f2;
        }
        .social-btn.facebook:hover {
            background: rgba(24, 119, 242, 0.1);
            border-color: #1877f2;
        }
        .switch-form {
            text-align: center;
            margin-top: 25px;
            color: #8888a0;
            font-size: 14px;
        }
        .switch-link {
            color: #f97316;
            text-decoration: none;
            font-weight: 600;
        }
        .switch-link:hover { color: #fb923c; text-decoration: underline; }
        @media (max-width: 768px) {
            .auth-container { flex-direction: column; max-width: 450px; }
            .auth-banner { width: 100%; padding: 35px 30px; }
            .auth-form-section { width: 100%; padding: 35px 30px; }
            .banner-logo .logo-icon { font-size: 45px; }
            .banner-logo h1 { font-size: 24px; }
            .banner-features { flex-direction: row; flex-wrap: wrap; gap: 10px; }
            .feature-item { font-size: 12px; padding: 8px 12px; }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-banner">
        <div class="banner-content">
            <div class="banner-logo">
                <span class="logo-icon">TF</span>
                <h1>Turbo<span>Fuel</span></h1>
            </div>
            <p class="banner-text">Calculate fueling time and cost with real-time prices. Find the nearest station instantly.</p>
            <div class="banner-features">
                <div class="feature-item">Time Estimation</div>
                <div class="feature-item">Cost Calculator</div>
                <div class="feature-item">Station Finder</div>
            </div>
        </div>
    </div>
    <div class="auth-form-section">
        <div class="form-wrapper">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Login to your account</p>
            </div>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">@</span>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">*</span>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="form-options">
                    <label class="remember-me"><input type="checkbox"> Remember me</label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
                <button type="submit" name="login" class="submit-btn">Login</button>
            </form>
            <div class="social-login">
                <div class="social-divider"><span>Or continue with</span></div>
                <div class="social-btns">
                    <a href="google_callback.php?demo=google" class="social-btn google">Google</a>
                    <a href="facebook_callback.php?demo=facebook" class="social-btn facebook">Facebook</a>
                </div>
            </div>
            <p class="switch-form">
                Don't have an account? <a href="register.php" class="switch-link">Register</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>