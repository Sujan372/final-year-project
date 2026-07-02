<?php
session_start();

include("db.php");

$dates = [];
$petrol = [];
$diesel = [];

// Use the existing daily_prices table (city = 'Bangalore' - you can change this)
$query = "SELECT recorded_date AS price_date,
                 MAX(CASE WHEN fuel_type = 'petrol' THEN price END) AS petrol,
                 MAX(CASE WHEN fuel_type = 'diesel' THEN price END) AS diesel
          FROM daily_prices
          WHERE city = 'Bangalore'
          GROUP BY recorded_date
          ORDER BY recorded_date ASC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dates[]  = $row['price_date'];
        $petrol[] = $row['petrol'] !== null ? (float)$row['petrol'] : null;
        $diesel[] = $row['diesel'] !== null ? (float)$row['diesel'] : null;
    }
} else {
    // Fallback dummy data so the chart still displays
    $dates  = ['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04'];
    $petrol = [104.2, 104.5, 104.0, 103.8];
    $diesel = [92.1, 92.3, 92.0, 91.9];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head_common.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Price Trends - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; color: #0f172a; padding: 32px 20px; }
        .container { max-width: 1080px; margin: 0 auto; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; }
        h1 { margin-bottom: 6px; font-size: 22px; font-weight: 700; }
        .subtitle { color: #64748b; margin-bottom: 26px; font-size: 13.5px; }
        canvas { height: 420px !important; }
        .back {
            display: inline-flex; align-items: center; gap: 6px; margin-top: 22px; padding: 11px 20px;
            background: #2563eb; text-decoration: none; color: #ffffff;
            border-radius: 9px; font-weight: 600; font-size: 13.5px; transition: background 0.15s ease;
        }
        .back:hover { background: #1d4ed8; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Fuel Price Trends</h1>
        <p class="subtitle">Daily petrol and diesel price analysis</p>
        <canvas id="priceChart"></canvas>
        <a href="index.php" class="back">Back to Dashboard</a>
    </div>
</div>
 <?php include 'theme.php'; ?>
<script>
    const ctx = document.getElementById('priceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [
                {
                    label: 'Petrol',
                    data: <?php echo json_encode($petrol); ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.12)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Diesel',
                    data: <?php echo json_encode($diesel); ?>,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.12)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#334155' } }
            },
            scales: {
                x: {
                    ticks: { color: '#64748b' },
                    grid: { color: 'rgba(15,23,42,0.06)' }
                },
                y: {
                    ticks: { color: '#64748b' },
                    grid: { color: 'rgba(15,23,42,0.06)' }
                }
            }
        }
    });
</script>
</body>
</html>