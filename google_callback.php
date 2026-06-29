<?php
session_start();

// ========== DEMO GOOGLE LOGIN (No API Keys Needed) ==========
// This simulates Google OAuth for demo purposes
// Replace with real API when you have credentials

if (isset($_GET['demo']) && $_GET['demo'] == 'google') {
    
    $conn = new mysqli("localhost", "root", "", "fuel_estimator");
    
    // Simulated Google user data
    $google_id = 'demo_google_' . rand(10000, 99999);
    $email = 'demo_google_user@gmail.com';
    $name = 'Demo Google User';
    $picture = 'https://ui-avatars.com/api/?name=Google+User&background=f97316&color=fff&size=200';
    
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Check if demo user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // Create demo user
        $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, google_id, profile_picture, auth_provider, password) VALUES (?, ?, ?, ?, ?, 'google', ?)");
        $dummyPassword = password_hash('demo123456', PASSWORD_DEFAULT);
        $insertStmt->bind_param("ssssss", $first_name, $last_name, $email, $google_id, $picture, $dummyPassword);
        $insertStmt->execute();
        $insertStmt->close();
        
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['fullname'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['profile_picture'] = $picture;
    $_SESSION['auth_provider'] = 'google';
    
    $stmt->close();
    $conn->close();
    
    header("Location: index.php?welcome=google");
    exit();
}

// If no demo parameter, redirect to login
header("Location: login.php");
exit();
?>