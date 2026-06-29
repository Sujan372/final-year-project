<?php
session_start();

if (isset($_GET['demo']) && $_GET['demo'] == 'facebook') {
    
    $conn = new mysqli("localhost", "root", "", "fuel_estimator");
    
    $facebook_id = 'demo_fb_' . rand(10000, 99999);
    $email = 'demo_fb_user@facebook.com';
    $name = 'Demo FB User';
    $picture = 'https://ui-avatars.com/api/?name=FB+User&background=1877f2&color=fff&size=200';
    
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0];
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, facebook_id, profile_picture, auth_provider, password) VALUES (?, ?, ?, ?, ?, 'facebook', ?)");
        $dummyPassword = password_hash('demo123456', PASSWORD_DEFAULT);
        $insertStmt->bind_param("ssssss", $first_name, $last_name, $email, $facebook_id, $picture, $dummyPassword);
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
    $_SESSION['auth_provider'] = 'facebook';
    
    $stmt->close();
    $conn->close();
    
    header("Location: index.php?welcome=facebook");
    exit();
}

header("Location: login.php");
exit();
?>