<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$host="localhost"; $user="root"; $pass=""; $db="fuel_estimator";
$conn = new mysqli($host,$user,$pass,$db);
$userId = $_SESSION['user_id'];

// Cancel booking
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $conn->query("UPDATE fuel_bookings SET status='cancelled' WHERE id=$id AND user_id=$userId AND status='confirmed'");
    header("Location: my_bookings.php");
    exit();
}

$bookings = $conn->query("SELECT * FROM fuel_bookings WHERE user_id=$userId ORDER BY created_at DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#0f172a; color:white; padding:30px 20px; }
        .container { max-width:800px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
        .header h1 { color:#f97316; font-size:24px; }
        .btn { background:#1e293b; color:white; padding:8px 18px; border-radius:8px; text-decoration:none; font-size:14px; border:1px solid #334155; transition:0.3s; }
        .btn:hover { border-color:#f97316; }
        table { width:100%; border-collapse:collapse; background:#1e293b; border-radius:12px; overflow:hidden; }
        th { background:#0f172a; padding:12px; text-align:left; font-size:12px; text-transform:uppercase; color:#94a3b8; }
        td { padding:12px; border-bottom:1px solid #334155; font-size:14px; }
        .badge { padding:3px 10px; border-radius:12px; font-size:11px; }
        .confirmed { background:rgba(34,197,94,0.2); color:#4ade80; }
        .cancelled { background:rgba(239,68,68,0.2); color:#f87171; }
        .completed { background:rgba(59,130,246,0.2); color:#60a5fa; }
        @media (max-width:500px) { .header { flex-direction:column; align-items:flex-start; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>My Bookings</h1>
        <div>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="book_fueling.php" class="btn">New Booking</a>
        </div>
    </div>

    <?php if ($bookings->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Station</th>
                <th>Fuel</th>
                <th>Litres</th>
                <th>Cost</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($b = $bookings->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($b['station_name']); ?></td>
                <td><?php echo ucfirst($b['fuel_type']); ?></td>
                <td><?php echo $b['litres']; ?></td>
                <td>Rs. <?php echo number_format($b['total_cost'],2); ?></td>
                <td><?php echo date('d M H:i', strtotime($b['created_at'])); ?></td>
                <td><span class="badge <?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                <td>
                    <?php if ($b['status'] == 'confirmed'): ?>
                        <a href="my_bookings.php?cancel=<?php echo $b['id']; ?>" onclick="return confirm('Cancel this booking?')" style="color:#f87171;">Cancel</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#94a3b8; padding:40px;">No bookings yet.</p>
    <?php endif; ?>
</div>
</body>
</html>