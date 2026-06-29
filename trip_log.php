<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

// Handle delete (optional)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM route_history WHERE id = $id AND user_id = $userId");
    header("Location: trip_log.php");
    exit();
}

// Fetch all trips for this user
$trips = $conn->query("SELECT * FROM route_history WHERE user_id = $userId ORDER BY created_at DESC");

// Calculate summary stats
$stats = $conn->query("SELECT 
    COUNT(*) as total_trips,
    COALESCE(SUM(distance_km),0) as total_km,
    COALESCE(SUM(total_cost),0) as total_spent,
    COALESCE(AVG(total_cost),0) as avg_cost
    FROM route_history WHERE user_id = $userId");
$stats = $stats->fetch_assoc();

// Monthly breakdown for the current year
$year = date('Y');
$monthly = $conn->query("SELECT 
    MONTH(created_at) as month, 
    COUNT(*) as trips, 
    SUM(total_cost) as cost 
    FROM route_history 
    WHERE user_id = $userId AND YEAR(created_at) = $year 
    GROUP BY MONTH(created_at) 
    ORDER BY month");

$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyData = [];
while ($row = $monthly->fetch_assoc()) {
    $monthlyData[$row['month']] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Log & Expenses - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: white; padding: 30px 20px; }
        .container { max-width: 1200px; margin:0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 24px; color: #f97316; }
        .btn { background: #1e293b; color: white; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 14px; border: 1px solid #334155; transition: 0.3s; }
        .btn:hover { border-color: #f97316; }
        .stats { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-box { background: #1e293b; border-radius: 12px; padding: 20px; flex: 1; min-width: 150px; text-align: center; }
        .stat-box .value { font-size: 24px; font-weight: 700; color: #f97316; }
        .stat-box .label { font-size: 12px; color: #94a3b8; text-transform: uppercase; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 12px; overflow: hidden; }
        th { background: #0f172a; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #94a3b8; }
        td { padding: 12px; border-bottom: 1px solid #334155; font-size: 14px; }
        tr:hover { background: #263348; }
        .badge { background: #f97316; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
        .month-chart { display: flex; align-items: flex-end; gap: 6px; height: 120px; margin: 20px 0; }
        .bar { flex: 1; background: #f97316; border-radius: 4px 4px 0 0; min-width: 20px; position: relative; transition: 0.3s; }
        .bar:hover { opacity: 0.8; }
        .bar span { position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #94a3b8; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        @media (max-width: 600px) { .stats { flex-direction: column; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Trip Log & Expenses</h1>
        <div>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="profile.php" class="btn">Profile</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value"><?php echo $stats['total_trips']; ?></div>
            <div class="label">Total Trips</div>
        </div>
        <div class="stat-box">
            <div class="value"><?php echo number_format($stats['total_km'],1); ?> km</div>
            <div class="label">Total Distance</div>
        </div>
        <div class="stat-box">
            <div class="value">₹<?php echo number_format($stats['total_spent'],2); ?></div>
            <div class="label">Total Spent</div>
        </div>
        <div class="stat-box">
            <div class="value">₹<?php echo number_format($stats['avg_cost'],2); ?></div>
            <div class="label">Avg per Trip</div>
        </div>
    </div>

    <h2 style="margin-bottom:15px; font-size:18px;">Monthly Spending (<?php echo $year; ?>)</h2>
    <div class="month-chart">
        <?php 
        $maxCost = 1;
        foreach ($monthlyData as $m) { if ($m['cost'] > $maxCost) $maxCost = $m['cost']; }
        for ($m=1; $m<=12; $m++): 
            $height = isset($monthlyData[$m]) ? ($monthlyData[$m]['cost'] / $maxCost) * 100 : 0;
        ?>
        <div class="bar" style="height:<?php echo $height; ?>%;">
            <span><?php echo $monthNames[$m-1]; ?></span>
        </div>
        <?php endfor; ?>
    </div>

    <h2 style="margin:25px 0 15px; font-size:18px;">Recent Trips</h2>
    <?php if ($trips->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Route</th>
                <th>Distance</th>
                <th>Fuel</th>
                <th>Cost</th>
                <th>Stations</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php while($trip = $trips->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('d M Y', strtotime($trip['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($trip['start_location'] . ' → ' . $trip['end_location']); ?></td>
                <td><?php echo number_format($trip['distance_km'],1); ?> km</td>
                <td><span class="badge"><?php echo ucfirst($trip['fuel_type']); ?></span></td>
                <td>₹<?php echo number_format($trip['total_cost'],2); ?></td>
                <td><?php echo $trip['stations_found']; ?></td>
                <td><a href="trip_log.php?delete=<?php echo $trip['id']; ?>" onclick="return confirm('Delete?')" style="color:#ef4444;">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty">No trips logged yet. Use the Route Finder to plan your first trip!</div>
    <?php endif; ?>
</div>
<?php include 'assets/theme.php'; ?>
</body>
</html>