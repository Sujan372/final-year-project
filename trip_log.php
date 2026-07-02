<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$userId = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM route_history WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $id, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: trip_log.php");
    exit();
}

// Fetch all trips for this user
$tripsStmt = $conn->prepare("SELECT * FROM route_history WHERE user_id = ? ORDER BY created_at DESC");
$tripsStmt->bind_param("i", $userId);
$tripsStmt->execute();
$trips = $tripsStmt->get_result();

// Calculate summary stats
$statsStmt = $conn->prepare("SELECT
    COUNT(*) as total_trips,
    COALESCE(SUM(distance_km),0) as total_km,
    COALESCE(SUM(total_cost),0) as total_spent,
    COALESCE(AVG(total_cost),0) as avg_cost
    FROM route_history WHERE user_id = ?");
$statsStmt->bind_param("i", $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

// Monthly breakdown for the current year
$year = date('Y');
$monthlyStmt = $conn->prepare("SELECT
    MONTH(created_at) as month,
    COUNT(*) as trips,
    SUM(total_cost) as cost
    FROM route_history
    WHERE user_id = ? AND YEAR(created_at) = ?
    GROUP BY MONTH(created_at)
    ORDER BY month");
$monthlyStmt->bind_param("ii", $userId, $year);
$monthlyStmt->execute();
$monthly = $monthlyStmt->get_result();

$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthlyData = [];
while ($row = $monthly->fetch_assoc()) {
    $monthlyData[$row['month']] = $row;
}
$monthlyStmt->close();
$tripsStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trip Log &amp; Expenses - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #0f172a; padding: 32px 20px; }
        .container { max-width: 1080px; margin: 0 auto; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px; }
        .header h1 { font-size: 21px; font-weight: 700; color: #0f172a; }
        .header-btns { display: flex; gap: 8px; }
        .btn { background: #ffffff; color: #334155; padding: 9px 16px; border-radius: 9px; text-decoration: none; font-size: 13px; font-weight: 500; border: 1px solid #cbd5e1; transition: border-color 0.15s ease, background 0.15s ease; }
        .btn:hover { border-color: #94a3b8; background: #f8fafc; }

        .stats { display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
        .stat-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; flex: 1; min-width: 150px; text-align: center; }
        .stat-box .value { font-size: 21px; font-weight: 700; color: #2563eb; }
        .stat-box .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

        h2.section-heading { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 14px; }

        table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; }
        th { background: #f8fafc; padding: 11px 14px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; color: #64748b; }
        td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        .badge { background: #eff6ff; color: #2563eb; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .delete-link { color: #dc2626; text-decoration: none; font-size: 12.5px; font-weight: 500; }
        .delete-link:hover { text-decoration: underline; }

        .chart-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 22px 34px; margin-bottom: 24px; }
        .month-chart { display: flex; align-items: flex-end; gap: 6px; height: 120px; }
        .bar { flex: 1; background: #2563eb; border-radius: 4px 4px 0 0; min-width: 18px; min-height: 2px; position: relative; }
        .bar span { position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #94a3b8; white-space: nowrap; }

        .empty { text-align: center; padding: 44px; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; font-size: 13.5px; }

        @media (max-width: 600px) { .stats { flex-direction: column; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Trip Log &amp; Expenses</h1>
        <div class="header-btns">
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

    <div class="chart-card">
        <h2 class="section-heading">Monthly Spending (<?php echo $year; ?>)</h2>
        <div class="month-chart">
            <?php
            $maxCost = 1;
            foreach ($monthlyData as $m) { if ($m['cost'] > $maxCost) $maxCost = $m['cost']; }
            for ($m=1; $m<=12; $m++):
                $height = isset($monthlyData[$m]) ? max(2, ($monthlyData[$m]['cost'] / $maxCost) * 100) : 2;
            ?>
            <div class="bar" style="height:<?php echo $height; ?>%;" title="<?php echo isset($monthlyData[$m]) ? '₹' . number_format($monthlyData[$m]['cost'], 2) : 'No trips'; ?>">
                <span><?php echo $monthNames[$m-1]; ?></span>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <h2 class="section-heading">Recent Trips</h2>
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
                <td><a href="trip_log.php?delete=<?php echo (int)$trip['id']; ?>" onclick="return confirm('Delete this trip?')" class="delete-link">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty">No trips logged yet. Use the Route Finder to plan your first trip.</div>
    <?php endif; ?>
</div>
<?php include 'theme.php'; ?>
</body>
</html>