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

// FIX: cast to int so this value can never be used to inject SQL, since it
// is used inside a query string below.
$userId = (int) $_SESSION['user_id'];
$message = "";
$error = "";

// Handle new ticket submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {

    $subject = trim($_POST['subject']);
    $ticketMessage = trim($_POST['message']);
    $category = $_POST['category'] ?? 'general';

    if (empty($subject) || empty($ticketMessage)) {
        $error = "Subject and message are required.";
    } elseif (strlen($subject) < 5) {
        $error = "Subject must be at least 5 characters.";
    } elseif (strlen($ticketMessage) < 10) {
        $error = "Message must be at least 10 characters.";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, category) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("isss", $userId, $subject, $ticketMessage, $category);

        if ($insertStmt->execute()) {
            $message = "Ticket submitted. We'll get back to you soon.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $insertStmt->close();
    }
}

// FIX: closing a ticket used to run on a plain GET link
// (support.php?close=5), which can be triggered by a browser prefetching
// the link, a crawler following it, or a forged request from another page
// (CSRF) with no real interaction from the user. Now POST-only.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['close_id'])) {
    $ticketId = intval($_POST['close_id']);
    $closeStmt = $conn->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ? AND user_id = ?");
    $closeStmt->bind_param("ii", $ticketId, $userId);
    $closeStmt->execute();
    $closeStmt->close();
    header("Location: support.php?msg=closed");
    exit();
}

// Success/error from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'closed') $message = "Ticket closed successfully.";
}

// Fetch user's tickets
$ticketsStmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$ticketsStmt->bind_param("i", $userId);
$ticketsStmt->execute();
$tickets = $ticketsStmt->get_result();
$ticketsStmt->close();

// FIX: this used $conn->query() with $userId concatenated directly into
// the SQL string (SQL injection vector), and called ->fetch_assoc() with
// no check that the query even succeeded — a query failure would be a
// fatal error and blank the whole page. Now a prepared statement with a
// guarded, defaulted result.
$countsStmt = $conn->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM support_tickets WHERE user_id = ?");
$countsStmt->bind_param("i", $userId);
$countsStmt->execute();
$statusCount = $countsStmt->get_result();
$counts = ($statusCount && $row = $statusCount->fetch_assoc())
    ? $row
    : ['total' => 0, 'open_count' => 0, 'resolved_count' => 0];
$countsStmt->close();

$conn->close();

$statusColorClass = [
    'open' => 'status-open',
    'in_progress' => 'status-in-progress',
    'resolved' => 'status-resolved',
    'closed' => 'status-closed',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - TurboFuel</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #090c14;
            --panel: #10151f;
            --panel-alt: #141b2a;
            --line: #212b3d;
            --line-soft: #181f30;
            --amber: #f2a63d;
            --amber-dim: rgba(242, 166, 61, 0.12);
            --teal: #2bc8a8;
            --teal-dim: rgba(43, 200, 168, 0.12);
            --azure: #5b8def;
            --azure-dim: rgba(91, 141, 239, 0.12);
            --violet: #a78bfa;
            --violet-dim: rgba(167, 139, 250, 0.12);
            --red: #e2584f;
            --red-dim: rgba(226, 88, 79, 0.12);
            --text: #edeff5;
            --text-dim: #8b96ac;
            --text-faint: #4e5872;
            --radius-lg: 22px;
            --radius-md: 14px;
            --radius-sm: 9px;
        }
        * { box-sizing: border-box; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(ellipse 900px 500px at 15% -10%, rgba(242, 166, 61, 0.07), transparent 60%),
                radial-gradient(ellipse 800px 500px at 100% 0%, rgba(43, 200, 168, 0.06), transparent 55%),
                var(--ink);
            color: var(--text);
            padding: 40px 20px 60px;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, .display { font-family: 'Rajdhani', 'Inter', sans-serif; letter-spacing: -0.01em; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .container { max-width: 900px; margin: 0 auto; }
        .icon { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 1.7; flex-shrink: 0; }

        /* ---------- Header ---------- */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .page-title { display: flex; align-items: center; gap: 14px; }
        .page-title .badge {
            width: 46px; height: 46px; border-radius: 14px;
            background: var(--teal-dim); border: 1px solid var(--teal);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .page-title .badge svg { width: 22px; height: 22px; stroke: var(--teal); fill: none; stroke-width: 1.6; }
        .page-title h1 { font-size: 24px; font-weight: 700; margin: 0; }
        .page-title h1 span { color: var(--amber); }
        .page-title .eyebrow { display: block; font-family: 'JetBrains Mono', monospace; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--teal); margin-bottom: 3px; }

        .header-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 20px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
            text-decoration: none; transition: all 0.2s ease; cursor: pointer; border: 1px solid var(--line);
            font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 8px; background: var(--panel); color: var(--text-dim);
        }
        .btn-outline:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-dim); }
        .btn-primary { background: var(--amber); color: #1a1305; border-color: var(--amber); }
        .btn-primary:hover { filter: brightness(1.08); transform: translateY(-1px); }

        /* ---------- Messages ---------- */
        .message { padding: 12px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 14px; font-weight: 500; }
        .message-success { background: var(--teal-dim); border: 1px solid rgba(43, 200, 168, 0.3); color: var(--teal); }
        .message-error { background: var(--red-dim); border: 1px solid rgba(226, 88, 79, 0.3); color: var(--red); }

        /* ---------- Section cards ---------- */
        .faq-card, .form-card, .tickets-card, .contact-card { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); padding: 26px 28px; margin-bottom: 20px; }
        .form-card { border-style: dashed; transition: border-color 0.2s ease; }
        .form-card:hover { border-color: var(--teal); }

        .section-title { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
        .section-title svg { width: 19px; height: 19px; stroke: var(--amber); fill: none; stroke-width: 1.7; }

        /* ---------- FAQ ---------- */
        .faq-item { background: var(--ink); border: 1px solid var(--line-soft); border-radius: 12px; margin-bottom: 9px; overflow: hidden; transition: border-color 0.2s ease; }
        .faq-item:hover { border-color: var(--line); }
        .faq-question { padding: 15px 17px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: var(--text); font-size: 13.5px; font-weight: 600; transition: color 0.2s ease; }
        .faq-question:hover { color: var(--amber); }
        .faq-arrow { width: 14px; height: 14px; stroke: var(--text-faint); fill: none; stroke-width: 2; transition: transform 0.3s ease; flex-shrink: 0; }
        .faq-item.active .faq-arrow { transform: rotate(180deg); stroke: var(--amber); }
        .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s ease; color: var(--text-dim); font-size: 13px; line-height: 1.7; padding: 0 17px; }
        .faq-item.active .faq-answer { max-height: 200px; padding: 0 17px 15px; }

        /* ---------- Stats ---------- */
        .stats-row { display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-mini { background: var(--panel); border: 1px solid var(--line-soft); border-radius: var(--radius-md); padding: 15px 20px; display: flex; align-items: center; gap: 12px; flex: 1; min-width: 140px; }
        .stat-mini svg { width: 20px; height: 20px; stroke: var(--amber); fill: none; stroke-width: 1.7; }
        .stat-mini:nth-child(2) svg { stroke: var(--teal); }
        .stat-mini:nth-child(3) svg { stroke: var(--violet); }
        .stat-mini .stat-num { font-family: 'JetBrains Mono', monospace; font-size: 24px; font-weight: 600; color: var(--text); }
        .stat-mini .stat-text { font-size: 11px; color: var(--text-faint); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }

        /* ---------- Form ---------- */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: var(--text-faint); font-size: 11px; font-weight: 500; margin-bottom: 7px; text-transform: uppercase; letter-spacing: 0.08em; font-family: 'JetBrains Mono', monospace; }
        .form-row { display: flex; gap: 14px; }
        .form-row .form-group { flex: 1; }
        input, select, textarea {
            width: 100%; padding: 11px 13px; background: var(--ink); border: 1px solid var(--line);
            border-radius: var(--radius-sm); color: var(--text); font-size: 14px; font-family: 'Inter', sans-serif;
            transition: all 0.2s ease; outline: none; resize: vertical;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--teal); box-shadow: 0 0 0 3px var(--teal-dim); }
        input::placeholder, textarea::placeholder { color: var(--text-faint); }
        select option { background: var(--panel); color: var(--text); }

        /* ---------- Tickets ---------- */
        .ticket-item { background: var(--ink); border: 1px solid var(--line-soft); border-radius: 12px; padding: 18px; margin-bottom: 12px; transition: border-color 0.2s ease; }
        .ticket-item:hover { border-color: var(--line); }
        .ticket-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 10px; }
        .ticket-subject { font-size: 15px; font-weight: 700; color: var(--text); }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .status-open { background: var(--teal-dim); color: var(--teal); }
        .status-in-progress { background: var(--azure-dim); color: var(--azure); }
        .status-resolved { background: var(--violet-dim); color: var(--violet); }
        .status-closed { background: var(--red-dim); color: var(--red); }

        .ticket-meta { display: flex; gap: 18px; font-size: 12px; color: var(--text-faint); margin-bottom: 10px; flex-wrap: wrap; }
        .ticket-meta span { display: flex; align-items: center; gap: 6px; }
        .ticket-meta svg { width: 13px; height: 13px; stroke: var(--text-faint); fill: none; stroke-width: 1.7; }
        .ticket-message { color: var(--text-dim); font-size: 13px; line-height: 1.6; margin-bottom: 10px; }

        .ticket-reply { background: var(--panel-alt); border-left: 3px solid var(--amber); padding: 12px 16px; border-radius: 0 10px 10px 0; margin-top: 10px; }
        .ticket-reply .reply-label { color: var(--amber); font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .ticket-reply .reply-text { color: var(--text-dim); font-size: 13px; }

        .ticket-actions { margin-top: 10px; }
        .close-btn {
            padding: 7px 15px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer;
            border: 1px solid rgba(226, 88, 79, 0.3); background: var(--ink); color: var(--red);
            font-family: 'Inter', sans-serif; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px;
        }
        .close-btn:hover { background: var(--red-dim); border-color: var(--red); }
        .close-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; }

        /* ---------- Contact ---------- */
        .contact-card { text-align: center; }
        .contact-methods { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; margin-top: 6px; }
        .contact-method { text-align: center; }
        .contact-method svg { width: 24px; height: 24px; stroke: var(--teal); fill: none; stroke-width: 1.6; margin-bottom: 8px; }
        .contact-method h4 { color: var(--text); font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .contact-method p { color: var(--text-faint); font-size: 12px; }
        .contact-method a { color: var(--amber); text-decoration: none; font-weight: 600; }
        .contact-method a:hover { text-decoration: underline; }

        /* ---------- Empty state ---------- */
        .empty-state { text-align: center; padding: 30px; }
        .empty-state svg { width: 34px; height: 34px; stroke: var(--text-faint); fill: none; stroke-width: 1.4; margin-bottom: 10px; }
        .empty-state h4 { color: var(--text); font-size: 15px; margin-bottom: 4px; }
        .empty-state p { color: var(--text-faint); font-size: 13px; }

        @media (max-width: 600px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .faq-card, .form-card, .tickets-card, .contact-card { padding: 20px; }
            .form-row { flex-direction: column; gap: 0; }
            .contact-methods { gap: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="page-header">
            <div class="page-title">
                <div class="badge">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M9.5 9a2.5 2.5 0 0 1 5 0c0 1.5-2.5 2-2.5 3.5"></path><circle cx="12" cy="16.5" r="0.5" fill="currentColor"></circle></svg>
                </div>
                <div>
                    <span class="eyebrow">Help center</span>
                    <h1>Turbo<span>Fuel</span> Support</h1>
                </div>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">
                    <svg class="icon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.5-7 8-7s8 3 8 7"></path></svg>
                    Profile
                </a>
                <a href="index.php" class="btn btn-outline">
                    <svg class="icon" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 4l9 7"></path><path d="M5 10v10h14V10"></path></svg>
                    Home
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="faq-card">
            <div class="section-title">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V6a2 2 0 0 1 2-2h13v14H6a2 2 0 0 0-2 2Z"></path><path d="M4 19.5A2 2 0 0 1 6 18h13"></path></svg>
                Frequently asked questions
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I estimate fuel cost?
                    <svg class="faq-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                </div>
                <div class="faq-answer">Simply select your fuel type (Petrol, Diesel, or CNG), enter the amount in litres, and the system calculates the cost using real-time prices. You can also find the nearest fuel station on the map.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    Are fuel prices updated in real-time?
                    <svg class="faq-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                </div>
                <div class="faq-answer">We fetch fuel prices from official government APIs and update them daily. Prices may vary slightly based on your city and specific station location.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I save a favorite fuel station?
                    <svg class="faq-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                </div>
                <div class="faq-answer">Go to the Saved Stations page from your profile, click Add Station, fill in the details, and save. You can also mark stations as favorites for quick access.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    Can I export my fuel history?
                    <svg class="faq-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                </div>
                <div class="faq-answer">Yes. Go to Settings, then Data & Privacy, and click Export My Data (CSV). This downloads all your fuel estimation history as a spreadsheet file.</div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I reset my password?
                    <svg class="faq-arrow" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>
                </div>
                <div class="faq-answer">Click Forgot Password on the login page, enter your registered email, and we'll send you a reset link. You can also change your password from Settings, then Account.</div>
            </div>
        </div>

        <?php if ($counts['total'] > 0): ?>
            <div class="stats-row">
                <div class="stat-mini">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z"></path></svg>
                    <div><div class="stat-num"><?php echo $counts['total']; ?></div><div class="stat-text">Total tickets</div></div>
                </div>
                <div class="stat-mini">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle></svg>
                    <div><div class="stat-num"><?php echo $counts['open_count']; ?></div><div class="stat-text">Open</div></div>
                </div>
                <div class="stat-mini">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                    <div><div class="stat-num"><?php echo $counts['resolved_count']; ?></div><div class="stat-text">Resolved</div></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-card" id="ticket-form">
            <div class="section-title">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z"></path></svg>
                Submit a support ticket
            </div>

            <form method="POST" action="support.php#ticket-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="general">General inquiry</option>
                            <option value="bug">Bug report</option>
                            <option value="feature">Feature request</option>
                            <option value="account">Account issue</option>
                            <option value="pricing">Fuel price issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" placeholder="Brief summary of your issue" required minlength="5">
                </div>

                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" rows="4" placeholder="Describe your issue in detail..." required minlength="10"></textarea>
                </div>

                <button type="submit" name="submit_ticket" class="btn btn-primary" style="width:100%; padding:13px; justify-content:center;">Submit ticket</button>
            </form>
        </div>

        <div class="tickets-card">
            <div class="section-title">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V6a2 2 0 0 1 2-2h13v14H6a2 2 0 0 0-2 2Z"></path><path d="M4 19.5A2 2 0 0 1 6 18h13"></path></svg>
                Your tickets
            </div>

            <?php if ($tickets && $tickets->num_rows > 0): ?>
                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                            <span class="status-badge <?php echo $statusColorClass[$ticket['status']] ?? 'status-open'; ?>">
                                <?php echo str_replace('_', ' ', $ticket['status']); ?>
                            </span>
                        </div>

                        <div class="ticket-meta">
                            <span>
                                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18M8 3v4M16 3v4"></path></svg>
                                <?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?>
                            </span>
                            <span>
                                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 12.5 12 21l-9-9V4h8Z"></path><circle cx="7.5" cy="7.5" r="1"></circle></svg>
                                <?php echo ucfirst($ticket['category']); ?>
                            </span>
                        </div>

                        <div class="ticket-message">
                            <?php echo nl2br(htmlspecialchars(substr($ticket['message'], 0, 200))); ?>
                            <?php if (strlen($ticket['message']) > 200): ?>...<?php endif; ?>
                        </div>

                        <?php if (!empty($ticket['admin_reply'])): ?>
                            <div class="ticket-reply">
                                <div class="reply-label">Admin reply</div>
                                <div class="reply-text"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($ticket['status'] != 'closed'): ?>
                            <div class="ticket-actions">
                                <form method="POST" action="support.php" onsubmit="return confirm('Close this ticket?')" style="margin:0;">
                                    <input type="hidden" name="close_id" value="<?php echo (int) $ticket['id']; ?>">
                                    <button type="submit" class="close-btn">
                                        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                                        Close ticket
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z"></path></svg>
                    <h4>No tickets yet</h4>
                    <p>Submit a ticket above if you need help.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="contact-card">
            <div class="section-title" style="justify-content:center;">
                <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"></path></svg>
                Contact us
            </div>

            <div class="contact-methods">
                <div class="contact-method">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path></svg>
                    <h4>Email</h4>
                    <p><a href="mailto:support@turbofuel.com">support@turbofuel.com</a></p>
                </div>
                <div class="contact-method">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2Z"></path></svg>
                    <h4>Phone</h4>
                    <p><a href="tel:+919876543210">+91 98765 43210</a></p>
                </div>
                <div class="contact-method">
                    <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V6a2 2 0 0 1 2-2h13v14H6a2 2 0 0 0-2 2Z"></path><path d="M4 19.5A2 2 0 0 1 6 18h13"></path></svg>
                    <h4>Live chat</h4>
                    <p>Available 9 AM - 6 PM</p>
                </div>
            </div>
        </div>

    </div>

    <script>
        function toggleFaq(element) {
            const faqItem = element.parentElement;
            faqItem.classList.toggle('active');
        }
    </script>
</body>
</html>