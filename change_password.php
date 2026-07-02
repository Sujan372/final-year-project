<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$error = "";
$success = "";
$userId = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = "User not found. Please log in again.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = "Current password is incorrect.";
        } elseif (password_verify($newPassword, $user['password'])) {
            $error = "New password cannot be the same as your current password.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);

            if ($updateStmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Something went wrong. Please try again.";
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
    <?php include 'head_common.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - TurboFuel</title>
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

        .password-container {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 18px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .password-header { text-align: center; margin-bottom: 26px; }

        .password-icon {
            width: 60px;
            height: 60px;
            background: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            color: #ffffff;
        }

        .password-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .password-header h1 span { color: #2563eb; }
        .password-header p { color: #64748b; font-size: 14px; margin-top: 4px; }

        .alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 500;
        }

        .alert svg { flex-shrink: 0; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .input-group { margin-bottom: 16px; }
        .input-group label { display: block; color: #334155; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .input-group label .required { color: #2563eb; }

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

        .password-hint { font-size: 12px; color: #64748b; margin-top: 6px; }

        .btn-row { display: flex; gap: 10px; margin-top: 22px; }

        .save-btn {
            flex: 1;
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

        .save-btn:hover { background: #1d4ed8; }

        .cancel-btn {
            padding: 12px 22px;
            background: #ffffff;
            color: #334155;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .cancel-btn:hover { border-color: #94a3b8; background: #f8fafc; }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .strength-fill { height: 100%; border-radius: 2px; width: 0; transition: width 0.3s ease, background 0.3s ease; }
        .strength-weak { width: 25%; background: #dc2626; }
        .strength-fair { width: 50%; background: #d97706; }
        .strength-good { width: 75%; background: #2563eb; }
        .strength-strong { width: 100%; background: #16a34a; }

        @media (max-width: 480px) {
            .password-container { padding: 30px 24px; border-radius: 14px; }
            .btn-row { flex-direction: column; }
            .cancel-btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <div class="password-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h1>Turbo<span>Fuel</span></h1>
            <p>Change your password</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="passwordForm" novalidate>
            <div class="input-group">
                <label for="current_password">Current password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)" aria-label="Show password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="input-group">
                <label for="new_password">New password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="6" onkeyup="checkStrength()">
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)" aria-label="Show password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <p class="password-hint" id="strengthText">Password strength: --</p>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm new password <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)" aria-label="Show password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="save-btn">Change password</button>
                <a href="profile.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        }

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
                text.style.color = '#64748b';
            } else if (strength <= 1) {
                fill.classList.add('strength-weak');
                text.textContent = 'Password strength: Weak';
                text.style.color = '#dc2626';
            } else if (strength === 2) {
                fill.classList.add('strength-fair');
                text.textContent = 'Password strength: Fair';
                text.style.color = '#d97706';
            } else if (strength === 3) {
                fill.classList.add('strength-good');
                text.textContent = 'Password strength: Good';
                text.style.color = '#2563eb';
            } else {
                fill.classList.add('strength-strong');
                text.textContent = 'Password strength: Strong';
                text.style.color = '#16a34a';
            }
        }

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match.');
            }
        });
    </script>
</body>
</html>