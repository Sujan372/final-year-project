<?php
session_start();

// Redirect if not logged in


// ========== DATABASE CONFIGURATION ==========
$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// If user not found, redirect to login
// Set default values to prevent null errors
$firstName = $user['first_name'] ?? '';
$lastName = $user['last_name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = "Name and Email are required!";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    }
    elseif (!empty($phone) && !preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Please enter a valid 10-digit phone number!";
    }
    else {
        // Check if email is already taken by another user
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmail->bind_param("si", $email, $userId);
        $checkEmail->execute();
        $checkEmail->store_result();
        
        if ($checkEmail->num_rows > 0) {
            $error = "Email already in use by another account!";
        } else {
            // Update user
            $updateStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $updateStmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
            
            if ($updateStmt->execute()) {
                // Update session
                $_SESSION['fullname'] = $firstName . ' ' . $lastName;
                $_SESSION['email'] = $email;
                
                $success = "Profile updated successfully!";
            } else {
                $error = "Something went wrong! Please try again.";
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Turbo Line</title>
    
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

        .edit-container {
            width: 100%;
            max-width: 520px;
            background: #1a1a2e;
            border-radius: 24px;
            padding: 45px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .edit-container::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 70%);
            border-radius: 50%;
        }

        .edit-container::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .edit-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }

        .edit-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 35px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
        }

        .edit-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .edit-header h1 span {
            color: #f97316;
        }

        .edit-header p {
            color: #8888a0;
            font-size: 14px;
            margin-top: 6px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            z-index: 1;
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
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .input-group label {
            display: block;
            color: #c0c0d0;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .input-group label .required {
            color: #f97316;
        }

        .input-row {
            display: flex;
            gap: 12px;
        }

        .input-row .input-group {
            flex: 1;
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

        .btn-row {
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .save-btn {
            flex: 1;
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

        .save-btn:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
        }

        .save-btn:active {
            transform: translateY(0);
        }

        .cancel-btn {
            padding: 15px 25px;
            background: #222240;
            color: #a0a0b8;
            font-size: 15px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            border: 2px solid #2a2a4a;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .cancel-btn:hover {
            border-color: #f97316;
            color: #ffffff;
            background: #252548;
        }

        @media (max-width: 500px) {
            .edit-container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .edit-header h1 {
                font-size: 20px;
            }

            .edit-avatar {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .input-row {
                flex-direction: column;
                gap: 0;
            }

            .btn-row {
                flex-direction: column;
            }

            .cancel-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    
    <div class="edit-container">
        
        <!-- Header -->
        <div class="edit-header">
            <div class="edit-avatar">✏️</div>
            <h1>Turbo<span>Line</span></h1>
            <p>Edit Your Profile</p>
        </div>
        
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Edit Form -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            
            <!-- First & Last Name -->
            <div class="input-row">
                <div class="input-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="first_name" name="first_name" placeholder="First name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="last_name" name="last_name" placeholder="Last name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Email -->
            <div class="input-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>
            
            <!-- Phone -->
            <div class="input-group">
                <label for="phone">Phone Number</label>
                <div class="input-wrapper">
                    <span class="input-icon">📱</span>
                    <input type="tel" id="phone" name="phone" placeholder="10-digit phone number" value="<?php echo htmlspecialchars($phone); ?>" pattern="[0-9]{10}">
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="btn-row">
                <button type="submit" class="save-btn">💾 Save Changes</button>
                <a href="profile.php" class="cancel-btn">Cancel</a>
            </div>
            
        </form>
        
    </div>

</body>
</html>