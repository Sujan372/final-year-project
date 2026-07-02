<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ========== DATABASE CONFIGURATION ==========
$host = "localhost";
$username = "root";
$password = "";
$database = "fuel_estimator";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

// Handle delete single entry
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM fuel_history WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $deleteId, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();
    header("Location: history.php?msg=deleted");
    exit();
}

// Handle clear all
if (isset($_GET['clear_all'])) {
    $clearStmt = $conn->prepare("DELETE FROM fuel_history WHERE user_id = ?");
    $clearStmt->bind_param("i", $userId);
    $clearStmt->execute();
    $clearStmt->close();
    header("Location: history.php?msg=cleared");
    exit();
}

// Success/delete messages
$message = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $message = "Entry deleted successfully.";
    if ($_GET['msg'] == 'cleared') $message = "All history cleared.";
    if ($_GET['msg'] == 'saved') $message = "Fuel estimation saved to history.";
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM fuel_history WHERE user_id = $userId");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch records
$historyStmt = $conn->prepare("SELECT * FROM fuel_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$historyStmt->bind_param("iii", $userId, $limit, $offset);
$historyStmt->execute();
$history = $historyStmt->get_result();
$historyStmt->close();

// Calculate stats
$statsResult = $conn->query("SELECT 
    COUNT(*) as total_entries,
    COALESCE(SUM(total_cost), 0) as total_spent,
    COALESCE(SUM(litres), 0) as total_litres,
    COALESCE(AVG(price_per_unit), 0) as avg_price,
    COALESCE(SUM(time_estimated), 0) as total_time
    FROM fuel_history WHERE user_id = $userId");
$stats = $statsResult->fetch_assoc();

// Get most used fuel type
$fuelStatsResult = $conn->query("SELECT 
    fuel_type, 
    COUNT(*) as count,
    SUM(total_cost) as spent
    FROM fuel_history 
    WHERE user_id = $userId 
    GROUP BY fuel_type 
    ORDER BY count DESC");
$fuelStats = [];
while ($row = $fuelStatsResult->fetch_assoc()) {
    $fuelStats[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel History - TurboFuel</title>
    
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
            padding: 30px 20px;
            color: #e2e8f0;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ========== HEADER ========== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title h1 {
            font-size: 26px;
            font-weight: 700;
            color: #f8fafc;
        }

        .page-title h1 span {
            color: #f97316;
        }

        .page-title .subtitle {
            font-size: 14px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .header-btns {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-outline {
            background: #1e293b;
            color: #cbd5e1;
            border-color: #334155;
        }

        .btn-outline:hover {
            border-color: #f97316;
            color: #ffffff;
            background: #1e293b;
        }

        .btn-primary {
            background: #f97316;
            color: #ffffff;
            border-color: #f97316;
        }

        .btn-primary:hover {
            background: #ea580c;
        }

        /* ========== MESSAGE ========== */
        .message {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        /* ========== STATS CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #f97316;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #f8fafc;
        }

        .stat-value.accent {
            color: #f97316;
        }

        /* ========== FUEL BREAKDOWN ========== */
        .fuel-breakdown {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .fuel-chip {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .fuel-chip.petrol {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .fuel-chip.diesel {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .fuel-chip.cng {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        /* ========== HISTORY TABLE ========== */
        .history-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 25px;
            overflow: hidden;
        }

        .history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .history-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #f8fafc;
        }

        .clear-btn {
            padding: 8px 16px;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 0 -25px;
            padding: 0 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        table th {
            background: #0f172a;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            text-align: left;
            white-space: nowrap;
        }

        table td {
            padding: 14px 16px;
            color: #cbd5e1;
            font-size: 14px;
            border-bottom: 1px solid #334155;
            white-space: nowrap;
        }

        table tbody tr {
            transition: all 0.2s ease;
        }

        table tbody tr:hover {
            background: #263348;
        }

        .fuel-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .fuel-petrol {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .fuel-diesel {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .fuel-cng {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .cost-highlight {
            font-weight: 700;
            color: #f97316;
        }

        .delete-btn {
            color: #f87171;
            text-decoration: none;
            font-size: 16px;
            transition: all 0.2s;
            padding: 4px 8px;
        }

        .delete-btn:hover {
            color: #ef4444;
            transform: scale(1.2);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state h3 {
            color: #f8fafc;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 15px;
            margin-bottom: 25px;
        }

        .empty-state .start-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #f97316;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .empty-state .start-btn:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        /* ========== PAGINATION ========== */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .page-link {
            min-width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1e293b;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #334155;
            transition: all 0.3s ease;
            padding: 0 12px;
        }

        .page-link:hover {
            border-color: #f97316;
            color: #ffffff;
        }

        .page-link.active {
            background: #f97316;
            color: #ffffff;
            border-color: #f97316;
        }

        .page-link.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        .page-info {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 12px;
            text-align: center;
        }

        @media (max-width: 600px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-btns {
                width: 100%;
            }

            .history-card {
                padding: 15px;
            }

            table th, table td {
                padding: 10px 8px;
                font-size: 13px;
            }

            .stat-value {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>Turbo<span>Fuel</span> History</h1>
                <p class="subtitle">Your complete fueling timeline and statistics</p>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">Profile</a>
                <a href="index.php" class="btn btn-primary">Dashboard</a>
            </div>
        </div>
        
        <!-- Success Message -->
        <?php if (!empty($message)): ?>
            <div class="message message-success">
                <span>&check;</span> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Fuelings</div>
                <div class="stat-value accent"><?php echo number_format($stats['total_entries']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Spent</div>
                <div class="stat-value">Rs. <?php echo number_format($stats['total_spent'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Volume</div>
                <div class="stat-value"><?php echo number_format($stats['total_litres'], 2); ?> L</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Time</div>
                <div class="stat-value"><?php echo number_format($stats['total_time'], 1); ?> min</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg Price/Unit</div>
                <div class="stat-value">Rs. <?php echo number_format($stats['avg_price'], 2); ?></div>
            </div>
        </div>
        
        <!-- Fuel Breakdown -->
        <?php if (!empty($fuelStats)): ?>
            <div class="fuel-breakdown">
                <?php foreach ($fuelStats as $fs): ?>
                    <div class="fuel-chip <?php echo $fs['fuel_type']; ?>">
                        <?php echo ucfirst($fs['fuel_type']); ?>: <?php echo $fs['count']; ?> times | Rs. <?php echo number_format($fs['spent'], 2); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- History Table -->
        <div class="history-card">
            <div class="history-header">
                <h2>Fueling Records</h2>
                <?php if ($totalRows > 0): ?>
                    <a href="history.php?clear_all=1" class="clear-btn" onclick="return confirm('Delete ALL history? This cannot be undone!')">Clear All History</a>
                <?php endif; ?>
            </div>
            
            <?php if ($history->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Fuel Type</th>
                                <th>Quantity</th>
                                <th>Price/Unit</th>
                                <th>Total Cost</th>
                                <th>Time</th>
                                <th>Station</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = $offset + 1;
                            while ($row = $history->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?><br><small style="color:#64748b;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small></td>
                                    <td>
                                        <span class="fuel-badge fuel-<?php echo $row['fuel_type']; ?>">
                                            <?php echo ucfirst($row['fuel_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($row['litres'], 2); ?> <?php echo ($row['fuel_type'] == 'cng') ? 'kg' : 'L'; ?></td>
                                    <td>Rs. <?php echo number_format($row['price_per_unit'], 2); ?></td>
                                    <td class="cost-highlight">Rs. <?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td><?php echo number_format($row['time_estimated'], 2); ?> min</td>
                                    <td><?php echo htmlspecialchars($row['station_name'] ?? '--'); ?></td>
                                    <td>
                                        <a href="history.php?delete=<?php echo $row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this entry?')" title="Delete">&#10005;</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="history.php?page=<?php echo $page-1; ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">Prev</a>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="history.php?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="history.php?page=<?php echo $page+1; ?>" class="page-link <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">Next</a>
                    </div>
                    <p class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo $totalRows; ?> total entries)</p>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Fueling History Yet</h3>
                    <p>Start using the fuel estimator to track your fuel expenses and time.</p>
                    <a href="index.php" class="start-btn">Start Estimating</a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>