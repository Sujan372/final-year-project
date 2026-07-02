<?php
session_start();
include("db.php");

$error = "";
$success = "";
$validToken = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $validToken = true;
        $resetData = $result->fetch_assoc();
        $userId = $resetData['user_id'];
    } else {
        $error = "This reset link is invalid or has expired.";
    }
    $stmt->close();
} else {
    $error = "No reset token was provided.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);

        if ($updateStmt->execute()) {
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            $deleteStmt->close();

            $success = "Your password has been reset. You can now log in.";
            $validToken = false;
        } else {
            $error = "Something went wrong. Please try again.";
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
    <title>Reset Password - TurboFuel</title>
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

        .reset-container {
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .icon-badge.neutral { background: #eff6ff; color: #2563eb; }
        .icon-badge.success { background: #f0fdf4; color: #16a34a; }
        .icon-badge.error { background: #fef2f2; color: #dc2626; }

        .reset-header { margin-bottom: 22px; }
        .reset-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .reset-header h1 span { color: #2563eb; }

        .alert {
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 500;
            text-align: left;
        }

        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .input-group { margin-bottom: 16px; text-align: left; }
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
            padding: 12px 40px 12px 40px;
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

        .toggle-password {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            display: flex;
            padding: 4px;
        }

        .toggle-password:hover { color: #475569; }

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
            margin-top: 4px;
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
            .reset-container { padding: 30px 24px; border-radius: 14px; }
        }
    </style>
</head>
<body>
    <div class="reset-container">

        <?php if (!empty($error)): ?>
            <div class="icon-badge error">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="icon-badge success">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <div class="icon-badge neutral">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <div class="reset-header">
                <h1>Turbo<span>Fuel</span></h1>
            </div>

            <form method="POST" novalidate>
                <div class="input-group">
                    <label for="password">New password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)" aria-label="Show password">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirm password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)" aria-label="Show password">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Reset password</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to login
        </a>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        }
    </script>
</body>
</html>