<?php
session_start();
include("db.php");

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $stmt->close();
                $conn->close();
                header("Location: index.php");
                exit();
            } else {
                $error = "Incorrect email or password.";
            }
        } else {
            $error = "Incorrect email or password.";
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
    <title>Login - TurboFuel</title>
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

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 560px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .auth-banner {
            width: 42%;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .banner-content { max-width: 320px; }

        .banner-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }

        .banner-logo .logo-mark {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .banner-logo h1 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .banner-logo h1 span { color: #60a5fa; }

        .banner-text {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .banner-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.05);
            padding: 11px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .feature-item svg { flex-shrink: 0; color: #60a5fa; }

        .auth-form-section {
            width: 58%;
            padding: 48px 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-wrapper { width: 100%; max-width: 380px; }

        .form-header { margin-bottom: 28px; }

        .form-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-header p { color: #64748b; font-size: 14px; }

        .alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13.5px;
            font-weight: 500;
        }

        .alert svg { flex-shrink: 0; }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .input-group { margin-bottom: 16px; }

        .input-group label {
            display: block;
            color: #334155;
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
            left: 13px;
            color: #94a3b8;
            display: flex;
            pointer-events: none;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            color: #0f172a;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .input-wrapper input::placeholder { color: #94a3b8; }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #64748b;
            font-size: 13px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            accent-color: #2563eb;
            width: 15px;
            height: 15px;
        }

        .forgot-link {
            color: #2563eb;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover { text-decoration: underline; }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .submit-btn:hover { background: #1d4ed8; }

        .social-login { margin-top: 24px; }

        .social-divider {
            color: #94a3b8;
            font-size: 12.5px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .social-divider::before,
        .social-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .social-btns { display: flex; gap: 10px; }

        .social-btn {
            flex: 1;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .social-btn:hover { background: #f8fafc; border-color: #94a3b8; }

        .switch-form {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 13.5px;
        }

        .switch-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .switch-link:hover { text-decoration: underline; }

        @media (max-width: 760px) {
            .auth-container { flex-direction: column; max-width: 420px; min-height: 0; }
            .auth-banner { width: 100%; padding: 32px 28px; }
            .auth-form-section { width: 100%; padding: 32px 28px; }
            .banner-features { display: none; }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-banner">
        <div class="banner-content">
            <div class="banner-logo">
                <span class="logo-mark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h12"/><path d="M4 9h9"/><path d="M4 4h8a1 1 0 0 1 1 1v17H5V5a1 1 0 0 1 1-1Z"/><path d="M14 8h1.5a2 2 0 0 1 2 2v6.5a1.5 1.5 0 0 0 3 0V9.83a2 2 0 0 0-.59-1.42L18 6.5"/></svg>
                </span>
                <h1>Turbo<span>Fuel</span></h1>
            </div>
            <p class="banner-text">Calculate fueling time and cost with real-time prices. Find the nearest station instantly.</p>
            <div class="banner-features">
                <div class="feature-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Time Estimation
                </div>
                <div class="feature-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Cost Calculator
                </div>
                <div class="feature-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    Station Finder
                </div>
            </div>
        </div>
    </div>
    <div class="auth-form-section">
        <div class="form-wrapper">
            <div class="form-header">
                <h2>Welcome back</h2>
                <p>Log in to your account</p>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" novalidate>
                <div class="input-group">
                    <label for="email">Email address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z" opacity="0"/><path d="M22 6c0-1.1-.9-2-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6Z"/><path d="m22 6-10 7L2 6"/></svg>
                        </span>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="form-options">
                    <label class="remember-me"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>
                <button type="submit" name="login" class="submit-btn">Log in</button>
            </form>
            <div class="social-login">
                <div class="social-divider"><span>Or continue with</span></div>
                <div class="social-btns">
                    <a href="google_callback.php" class="social-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.85A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.85Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1a11 11 0 0 0-9.82 6.05l3.66 2.85c.87-2.6 3.3-4.52 6.16-4.52Z"/></svg>
                        Google
                    </a>
                    <a href="facebook_callback.php" class="social-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.78-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.9h-2.34V22c4.78-.8 8.44-4.95 8.44-9.94Z"/></svg>
                        Facebook
                    </a>
                </div>
            </div>
            <p class="switch-form">
                Don't have an account? <a href="register.php" class="switch-link">Sign up</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>