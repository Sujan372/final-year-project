<?php
session_start();
include("db.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (first_name, last_name, email, phone, password, created_at) VALUES ('$firstName', '$lastName', '$email', '$phone', '$hashed', NOW())";
            if (mysqli_query($conn, $sql)) {
                $success = "Account created! You can now login.";
                // Clear form
                $firstName = $lastName = $email = $phone = "";
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TurboFuel</title>
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
        .register-container {
            width: 100%;
            max-width: 500px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }
        .register-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        .register-header { text-align: center; margin-bottom: 30px; position: relative; z-index: 1; }
        .register-header h1 { font-size: 28px; font-weight: 700; color: #ffffff; }
        .register-header h1 span { color: #f97316; }
        .register-header p { color: #8888a0; font-size: 14px; margin-top: 6px; }
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
        .input-group { margin-bottom: 18px; position: relative; z-index: 1; }
        .input-group label { display: block; color: #c0c0d0; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        .input-row { display: flex; gap: 12px; }
        .input-row .input-group { flex: 1; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-icon { position: absolute; left: 14px; font-size: 16px; z-index: 1; color: #6a6a8a; }
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
        .terms {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a0a0b8;
            font-size: 13px;
            margin-bottom: 22px;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }
        .terms input[type="checkbox"] { accent-color: #f97316; width: 16px; height: 16px; }
        .terms a { color: #f97316; text-decoration: none; }
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
        .login-link { text-align: center; margin-top: 22px; color: #8888a0; font-size: 14px; position: relative; z-index: 1; }
        .login-link a { color: #f97316; text-decoration: none; font-weight: 600; }
        @media (max-width: 500px) {
            .register-container { padding: 30px 20px; }
            .input-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-header">
        <h1>Turbo<span>Fuel</span></h1>
        <p>Create your account</p>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="input-row">
            <div class="input-group">
                <label>First Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">A</span>
                    <input type="text" name="first_name" placeholder="John" value="<?php echo htmlspecialchars($firstName ?? ''); ?>" required>
                </div>
            </div>
            <div class="input-group">
                <label>Last Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">B</span>
                    <input type="text" name="last_name" placeholder="Doe" value="<?php echo htmlspecialchars($lastName ?? ''); ?>" required>
                </div>
            </div>
        </div>
        <div class="input-group">
            <label>Email Address</label>
            <div class="input-wrapper">
                <span class="input-icon">@</span>
                <input type="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
        </div>
        <div class="input-group">
            <label>Phone Number</label>
            <div class="input-wrapper">
                <span class="input-icon">#</span>
                <input type="tel" name="phone" placeholder="9876543210" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
            </div>
        </div>
        <div class="input-group">
            <label>Password</label>
            <div class="input-wrapper">
                <span class="input-icon">*</span>
                <input type="password" name="password" placeholder="Min 6 characters" required minlength="6">
            </div>
        </div>
        <div class="input-group">
            <label>Confirm Password</label>
            <div class="input-wrapper">
                <span class="input-icon">*</span>
                <input type="password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
            </div>
        </div>
        <label class="terms">
            <input type="checkbox" required> I agree to the <a href="#">Terms & Conditions</a>
        </label>
        <button type="submit" name="register" class="submit-btn">Create Account</button>
    </form>
    <p class="login-link">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>
</body>
</html>