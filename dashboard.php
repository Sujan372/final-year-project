<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location:index.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>TurboFuel Dashboard</title>
</head>
<body>

<h2>Welcome, <?php echo $_SESSION['fullname']; ?></h2>

<a href="logout.php">
    <button style="
        background:#ff6b35;
        color:red;
        border:none;
        padding:12px 25px;
        border-radius:8px;
        cursor:pointer;
        font-size:16px;
    ">
        Logout
    </button>
</a>

</body>
</html>