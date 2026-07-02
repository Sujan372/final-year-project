<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("db.php");

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
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM fuel_history WHERE user_id = ?");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch records
$historyStmt = $conn->prepare("SELECT * FROM fuel_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$historyStmt->bind_param("iii", $userId, $limit, $offset);
$historyStmt->execute();
$history = $historyStmt->get_result();
$historyStmt->close();

// Calculate stats
$statsStmt = $conn->prepare("SELECT
    COUNT(*) as total_entries,
    COALESCE(SUM(total_cost), 0) as total_spent,
    COALESCE(SUM(litres), 0) as total_litres,
    COALESCE(AVG(price_per_unit), 0) as avg_price,
    COALESCE(SUM(time_estimated), 0) as total_time
    FROM fuel_history WHERE user_id = ?");
$statsStmt->bind_param("i", $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();

// Get most used fuel type
$fuelStatsStmt = $conn->prepare("SELECT
    fuel_type,
    COUNT(*) as count,
    SUM(total_cost) as spent
    FROM fuel_history
    WHERE user_id = ?
    GROUP BY fuel_type
    ORDER BY count DESC");
$fuelStatsStmt->bind_param("i", $userId);
$fuelStatsStmt->execute();
$fuelStatsResult = $fuelStatsStmt->get_result();
$fuelStats = [];
while ($row = $fuelStatsResult->fetch_assoc()) {
    $fuelStats[] = $row;
}
$fuelStatsStmt->close();

$conn->close();

$icon = [
  'home' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
  'user' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  'trash' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
  'fuel' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h12"/><path d="M4 9h9"/><path d="M4 4h8a1 1 0 0 1 1 1v17H5V5a1 1 0 0 1 1-1Z"/><path d="M14 8h1.5a2 2 0 0 1 2 2v6.5a1.5 1.5 0 0 0 3 0V9.83a2 2 0 0 0-.59-1.42L18 6.5"/></svg>',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head_common.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel History - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 32px 20px; color: #0f172a; }
        .container { max-width: 1080px; margin: 0 auto; }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 14px; }
        .page-title { display: flex; align-items: center; gap: 12px; }
        .page-title .icon { width: 46px; height: 46px; background: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .page-title h1 { font-size: 21px; font-weight: 700; color: #0f172a; }
        .page-title h1 span { color: #2563eb; }

        .header-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { padding: 9px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; transition: background 0.15s ease, border-color 0.15s ease; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; }
        .btn-outline { background: #ffffff; color: #334155; }
        .btn-outline:hover { border-color: #94a3b8; background: #f8fafc; }
        .btn-primary { background: #2563eb; color: #ffffff; border-color: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; }

        .message { display: flex; align-items: center; gap: 8px; padding: 11px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; }
        .message-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .stat-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; text-align: center; }
        .stat-value { font-size: 21px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
        .stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }

        .fuel-breakdown { display: flex; gap: 8px; margin-bottom: 22px; flex-wrap: wrap; }
        .fuel-chip { padding: 7px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 600; }
        .fuel-chip.petrol { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .fuel-chip.diesel { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .fuel-chip.cng { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        .history-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; }
        .history-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
        .history-header h2 { font-size: 16px; font-weight: 600; color: #0f172a; }
        .clear-btn { padding: 7px 14px; background: #fef2f2; color: #dc2626; text-decoration: none; border-radius: 8px; font-size: 12.5px; font-weight: 500; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 6px; }
        .clear-btn:hover { background: #fee2e2; }

        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; padding: 12px 14px; text-align: left; white-space: nowrap; }
        table td { padding: 12px 14px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; white-space: nowrap; }
        table tbody tr:hover { background: #f8fafc; }

        .fuel-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; }
        .fuel-petrol { background: #fef2f2; color: #dc2626; }
        .fuel-diesel { background: #eff6ff; color: #2563eb; }
        .fuel-cng { background: #f0fdf4; color: #16a34a; }

        .cost-highlight { font-weight: 700; color: #2563eb; }
        .delete-btn { color: #dc2626; display: inline-flex; padding: 4px; }
        .delete-btn:hover { color: #b91c1c; }

        .empty-state { text-align: center; padding: 56px 20px; }
        .empty-state .icon-badge { width: 52px; height: 52px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
        .empty-state h3 { color: #0f172a; font-size: 16px; margin-bottom: 6px; }
        .empty-state p { color: #64748b; font-size: 13.5px; margin-bottom: 18px; }

        .pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 18px; flex-wrap: wrap; }
        .page-link { min-width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; background: #ffffff; color: #334155; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 500; border: 1px solid #cbd5e1; padding: 0 10px; }
        .page-link:hover { border-color: #94a3b8; }
        .page-link.active { background: #2563eb; color: #ffffff; border-color: #2563eb; }
        .page-link.disabled { opacity: 0.4; pointer-events: none; }
        .page-info { color: #64748b; font-size: 12.5px; margin-top: 10px; text-align: center; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .history-card { padding: 15px; }
            table th, table td { padding: 9px 8px; font-size: 11.5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <div class="icon"><?php echo $icon['fuel']; ?></div>
                <h1>Turbo<span>Fuel</span> History</h1>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline"><?php echo $icon['user']; ?> Profile</a>
                <a href="index.php" class="btn btn-outline"><?php echo $icon['home']; ?> Home</a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message message-success"><?php echo $icon['check']; ?> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_entries']); ?></div>
                <div class="stat-label">Total Fuelings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($stats['total_spent'], 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_litres'], 2); ?> L</div>
                <div class="stat-label">Total Volume</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_time'], 1); ?> min</div>
                <div class="stat-label">Total Time</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₹<?php echo number_format($stats['avg_price'], 2); ?></div>
                <div class="stat-label">Avg Price/Unit</div>
            </div>
        </div>

        <?php if (!empty($fuelStats)): ?>
            <div class="fuel-breakdown">
                <?php foreach ($fuelStats as $fs): ?>
                    <div class="fuel-chip <?php echo htmlspecialchars($fs['fuel_type']); ?>">
                        <?php echo ucfirst($fs['fuel_type']); ?>: <?php echo $fs['count']; ?> times &middot; ₹<?php echo number_format($fs['spent'], 2); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="history-card">
            <div class="history-header">
                <h2>Fueling History</h2>
                <?php if ($totalRows > 0): ?>
                    <a href="history.php?clear_all=1" class="clear-btn" onclick="return confirm('Delete ALL history? This cannot be undone.')"><?php echo $icon['trash']; ?> Clear All History</a>
                <?php endif; ?>
            </div>

            <?php if ($history->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date &amp; Time</th>
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
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?><br><small style="color:#94a3b8;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small></td>
                                    <td>
                                        <span class="fuel-badge fuel-<?php echo htmlspecialchars($row['fuel_type']); ?>">
                                            <?php echo ucfirst($row['fuel_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($row['litres'], 2); ?> <?php echo ($row['fuel_type'] == 'cng') ? 'kg' : 'L'; ?></td>
                                    <td>₹<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                    <td class="cost-highlight">₹<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td><?php echo number_format($row['time_estimated'], 2); ?> min</td>
                                    <td><?php echo htmlspecialchars($row['station_name'] ?? '--'); ?></td>
                                    <td>
                                        <a href="history.php?delete=<?php echo (int)$row['id']; ?>" class="delete-btn" onclick="return confirm('Delete this entry?')" title="Delete"><?php echo $icon['trash']; ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

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
                    <div class="icon-badge"><?php echo $icon['fuel']; ?></div>
                    <h3>No Fueling History Yet</h3>
                    <p>Start using the fuel estimator to track your fuel expenses and time.</p>
                    <a href="index.php" class="btn btn-primary">Start Estimating</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>