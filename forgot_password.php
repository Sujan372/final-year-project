<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TurboFuel</title>
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

        .forgot-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
            text-align: center;
        }

        .icon-badge {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .icon-badge svg { color: #2563eb; }

        .forgot-header { margin-bottom: 26px; }

        .forgot-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: -0.2px; }
        .forgot-header h1 span { color: #2563eb; }

        .forgot-header p {
            color: #64748b;
            font-size: 14px;
            margin-top: 8px;
            line-height: 1.6;
        }

        .alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 500;
            text-align: left;
        }

        .alert svg { flex-shrink: 0; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; color: #334155; font-size: 13px; font-weight: 500; margin-bottom: 6px; }

        .input-wrapper { position: relative; display: flex; align-items: center; }

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

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
        }

        .back-link:hover { color: #2563eb; }

        @media (max-width: 480px) {
            .forgot-container { padding: 30px 24px; border-radius: 14px; }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="icon-badge">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
        </div>

        <div class="forgot-header">
            <h1>Turbo<span>Fuel</span></h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>

        <form method="POST" action="send_reset_link.php" novalidate>
            <div class="input-group">
                <label for="email">Email address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
            </div>

            <button type="submit" class="submit-btn">Send reset link</button>
        </form>

        <a href="login.php" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to login
        </a>
    </div>
</body>
</html>