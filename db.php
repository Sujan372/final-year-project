<?php
// ========== DATABASE CONFIGURATION ==========
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "fuel_estimator";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    // Avoid leaking connection details to the browser in production.
    die("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");