<?php
session_start();
include("db.php");

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $insertToken = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insertToken->bind_param("iss", $user['id'], $token, $expiry);

            if ($insertToken->execute()) {
                $resetLink = "http://localhost/TurboFuel/reset_password.php?token=" . $token;
                $to = $email;
                $subject = "Reset Your Password - TurboFuel";
                $message = "
                <html>
                <head><title>Reset Your Password</title></head>
                <body style='font-family: Arial, sans-serif; background: #f1f5f9; padding: 20px;'>
                    <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;'>
                        <h2 style='color: #2563eb; margin: 0 0 16px;'>TurboFuel</h2>
                        <p>Hello <b>" . htmlspecialchars($user['first_name']) . "</b>,</p>
                        <p>Click the button below to reset your password. This link expires in 1 hour.</p>
                        <a href='{$resetLink}' style='display: inline-block; padding: 12px 26px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0;'>Reset Password</a>
                        <p style='font-size: 12px; color: #64748b;'>If you didn't request this, you can safely ignore this email.</p>
                    </div>
                </body>
                </html>
                ";

                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: TurboFuel <noreply@turbofuel.com>" . "\r\n";

                @mail($to, $subject, $message, $headers);
                $success = "If this email is registered, a reset link has been sent. Check your inbox.";
            } else {
                $error = "Something went wrong. Please try again.";
            }

            $insertToken->close();
        } else {
            // Don't reveal whether the email exists (security best practice)
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
    <title>Reset Link Sent - TurboFuel</title>
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

        .status-container {
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
            margin: 0 auto 18px;
        }

        .icon-badge.success { background: #f0fdf4; color: #16a34a; }
        .icon-badge.error { background: #fef2f2; color: #dc2626; }

        .alert {
            padding: 11px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 500;
        }

        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2563eb;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
        }

        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="status-container">

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

        <a href="login.php" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to login
        </a>
    </div>
</body>
</html>