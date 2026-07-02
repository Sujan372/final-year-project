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

// Fetch current user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If the account no longer exists, force back to login
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$firstName = $user['first_name'] ?? '';
$lastName = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($phone) && !preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Please enter a valid 10-digit phone number.";
    } else {
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmail->bind_param("si", $email, $userId);
        $checkEmail->execute();
        $checkEmail->store_result();

        if ($checkEmail->num_rows > 0) {
            $error = "Email already in use by another account.";
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $updateStmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);

            if ($updateStmt->execute()) {
                $_SESSION['fullname'] = $firstName . ' ' . $lastName;
                $_SESSION['email'] = $email;
                $success = "Profile updated successfully.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $updateStmt->close();
        }
        $checkEmail->close();
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
    <title>Edit Profile - TurboFuel</title>
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

        .edit-container {
            width: 100%;
            max-width: 500px;
            background: #ffffff;
            border-radius: 18px;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .edit-header { text-align: center; margin-bottom: 26px; }

        .edit-avatar {
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

        .edit-header h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .edit-header h1 span { color: #2563eb; }
        .edit-header p { color: #64748b; font-size: 14px; margin-top: 4px; }

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

        .btn-row { display: flex; gap: 10px; margin-top: 6px; }

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

        @media (max-width: 480px) {
            .edit-container { padding: 30px 24px; border-radius: 14px; }
            .input-row { flex-direction: column; gap: 0; }
            .btn-row { flex-direction: column; }
            .cancel-btn { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <div class="edit-avatar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
            </div>
            <h1>Turbo<span>Fuel</span></h1>
            <p>Edit your profile</p>
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
                    <label for="first_name">First name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="first_name" name="first_name" placeholder="First name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                    </div>
                </div>
                <div class="input-group">
                    <label for="last_name">Last name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="last_name" name="last_name" placeholder="Last name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <label for="email">Email address <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>

            <div class="input-group">
                <label for="phone">Phone number</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                    </span>
                    <input type="tel" id="phone" name="phone" placeholder="10-digit phone number" value="<?php echo htmlspecialchars($phone); ?>" pattern="[0-9]{10}">
                </div>
            </div>

            <div class="btn-row">
                <button type="submit" class="save-btn">Save changes</button>
                <a href="profile.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>