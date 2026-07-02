<?php
session_start();
include("db.php");

$error = "";
$success = "";
$firstName = $lastName = $email = $phone = "";

if (isset($_POST['register'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $phone, $hashed);

            if ($stmt->execute()) {
                $success = "Account created successfully. You can now log in.";
                $firstName = $lastName = $email = $phone = "";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TurboFuel</title>
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

        .register-container {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 16px;
            padding: 40px 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .register-header { text-align: center; margin-bottom: 26px; }

        .register-header .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-bottom: 14px;
        }

        .logo-mark {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .register-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .register-header h1 span { color: #2563eb; }
        .register-header p { color: #64748b; font-size: 14px; margin-top: 2px; }

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

        .input-row { display: flex; gap: 12px; }
        .input-row .input-group { flex: 1; }

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

        .terms {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 22px;
            cursor: pointer;
            line-height: 1.5;
        }

        .terms input[type="checkbox"] { accent-color: #2563eb; width: 15px; height: 15px; margin-top: 2px; }
        .terms a { color: #2563eb; text-decoration: none; }
        .terms a:hover { text-decoration: underline; }

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

        .login-link { text-align: center; margin-top: 22px; color: #64748b; font-size: 13.5px; }
        .login-link a { color: #2563eb; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }

        @media (max-width: 500px) {
            .register-container { padding: 30px 24px; }
            .input-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <div class="logo-row">
            <span class="logo-mark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h12"/><path d="M4 9h9"/><path d="M4 4h8a1 1 0 0 1 1 1v17H5V5a1 1 0 0 1 1-1Z"/><path d="M14 8h1.5a2 2 0 0 1 2 2v6.5a1.5 1.5 0 0 0 3 0V9.83a2 2 0 0 0-.59-1.42L18 6.5"/></svg>
            </span>
            <h1>Turbo<span>Fuel</span></h1>
        </div>
        <p>Create your account</p>
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
    <form method="POST" action="" novalidate>
        <div class="input-row">
            <div class="input-group">
                <label for="first_name">First name</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" id="first_name" name="first_name" placeholder="John" value="<?php echo htmlspecialchars($firstName); ?>" required>
                </div>
            </div>
            <div class="input-group">
                <label for="last_name">Last name</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" id="last_name" name="last_name" placeholder="Doe" value="<?php echo htmlspecialchars($lastName); ?>" required>
                </div>
            </div>
        </div>
        <div class="input-group">
            <label for="email">Email address</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                </span>
                <input type="email" id="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
        </div>
        <div class="input-group">
            <label for="phone">Phone number</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                </span>
                <input type="tel" id="phone" name="phone" placeholder="9876543210" value="<?php echo htmlspecialchars($phone); ?>">
            </div>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required minlength="6">
            </div>
        </div>
        <div class="input-group">
            <label for="confirm_password">Confirm password</label>
            <div class="input-wrapper">
                <span class="input-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </span>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
            </div>
        </div>
        <label class="terms">
            <input type="checkbox" required> I agree to the <a href="#">Terms &amp; Conditions</a>
        </label>
        <button type="submit" name="register" class="submit-btn">Create account</button>
    </form>
    <p class="login-link">
        Already have an account? <a href="login.php">Log in</a>
    </p>
</div>
</body>
</html>