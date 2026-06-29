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
$message = "";
$error = "";

// Handle new ticket submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ticket'])) {
    
    $subject = trim($_POST['subject']);
    $ticketMessage = trim($_POST['message']);
    $category = $_POST['category'] ?? 'general';
    
    if (empty($subject) || empty($ticketMessage)) {
        $error = "Subject and message are required!";
    } elseif (strlen($subject) < 5) {
        $error = "Subject must be at least 5 characters!";
    } elseif (strlen($ticketMessage) < 10) {
        $error = "Message must be at least 10 characters!";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, message, category) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("isss", $userId, $subject, $ticketMessage, $category);
        
        if ($insertStmt->execute()) {
            $message = "Ticket submitted successfully! We'll get back to you soon.";
        } else {
            $error = "Something went wrong! Please try again.";
        }
        $insertStmt->close();
    }
}

// Handle ticket closure
if (isset($_GET['close'])) {
    $ticketId = intval($_GET['close']);
    $closeStmt = $conn->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ? AND user_id = ?");
    $closeStmt->bind_param("ii", $ticketId, $userId);
    $closeStmt->execute();
    $closeStmt->close();
    header("Location: support.php?msg=closed");
    exit();
}

// Success/error from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'closed') $message = "Ticket closed successfully!";
}

// Fetch user's tickets
$ticketsStmt = $conn->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$ticketsStmt->bind_param("i", $userId);
$ticketsStmt->execute();
$tickets = $ticketsStmt->get_result();
$ticketsStmt->close();

// Count tickets by status
$statusCount = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM support_tickets WHERE user_id = $userId");
$counts = $statusCount->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - Turbo Line</title>
    
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
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* ========== HEADER ========== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title .icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .page-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
        }

        .page-title h1 span {
            color: #f97316;
        }

        .header-btns {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #2a2a4a;
            font-family: 'Inter', sans-serif;
        }

        .btn-outline {
            background: #222240;
            color: #a0a0b8;
        }

        .btn-outline:hover {
            border-color: #f97316;
            color: #ffffff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #ffffff;
            border-color: #f97316;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        /* ========== MESSAGE ========== */
        .message {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
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

        .message-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* ========== FAQ SECTION ========== */
        .faq-card {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-item {
            background: #222240;
            border: 1px solid #2a2a4a;
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            border-color: #3a3a5a;
        }

        .faq-question {
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .faq-question:hover {
            color: #f97316;
        }

        .faq-arrow {
            font-size: 12px;
            transition: transform 0.3s ease;
            color: #6a6a8a;
        }

        .faq-item.active .faq-arrow {
            transform: rotate(180deg);
            color: #f97316;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            color: #a0a0b8;
            font-size: 13px;
            line-height: 1.7;
            padding: 0 18px;
        }

        .faq-item.active .faq-answer {
            max-height: 200px;
            padding: 0 18px 16px;
        }

        /* ========== STATS ROW ========== */
        .stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-mini {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 140px;
        }

        .stat-mini .stat-num {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
        }

        .stat-mini .stat-text {
            font-size: 12px;
            color: #8888a0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========== TICKET FORM ========== */
        .form-card {
            background: #1a1a2e;
            border: 2px dashed #2a2a4a;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .form-card:hover {
            border-color: #f97316;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            color: #c0c0d0;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            background: #222240;
            border: 2px solid #2a2a4a;
            border-radius: 10px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: #5a5a7a;
        }

        select option {
            background: #1a1a2e;
            color: #ffffff;
        }

        /* ========== TICKETS LIST ========== */
        .tickets-card {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 20px;
        }

        .ticket-item {
            background: #222240;
            border: 1px solid #2a2a4a;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .ticket-item:hover {
            border-color: #3a3a5a;
        }

        .ticket-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ticket-subject {
            font-size: 15px;
            font-weight: 600;
            color: #ffffff;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .status-in_progress {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .status-resolved {
            background: rgba(168, 85, 247, 0.15);
            color: #a78bfa;
        }

        .status-closed {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .ticket-meta {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #6a6a8a;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .ticket-message {
            color: #a0a0b8;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .ticket-reply {
            background: #1a1a2e;
            border-left: 3px solid #f97316;
            padding: 12px 16px;
            border-radius: 0 10px 10px 0;
            margin-top: 10px;
        }

        .ticket-reply .reply-label {
            color: #f97316;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .ticket-reply .reply-text {
            color: #c8c8d8;
            font-size: 13px;
        }

        .ticket-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-sm {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid #2a2a4a;
            background: #1a1a2e;
            color: #a0a0b8;
        }

        .btn-sm:hover {
            border-color: #f97316;
            color: #ffffff;
        }

        .btn-sm.close-btn {
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .btn-sm.close-btn:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }

        /* ========== CONTACT INFO ========== */
        .contact-card {
            background: #1a1a2e;
            border: 1px solid #2a2a4a;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 20px;
            text-align: center;
        }

        .contact-methods {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .contact-method {
            text-align: center;
        }

        .contact-method .contact-icon {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .contact-method h4 {
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .contact-method p {
            color: #8888a0;
            font-size: 12px;
        }

        .contact-method a {
            color: #f97316;
            text-decoration: none;
            font-weight: 500;
        }

        .contact-method a:hover {
            text-decoration: underline;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 30px;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .empty-state h4 {
            color: #ffffff;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .empty-state p {
            color: #8888a0;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-card, .faq-card, .tickets-card, .contact-card {
                padding: 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .contact-methods {
                gap: 20px;
            }

            .ticket-meta {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <div class="icon">❓</div>
                <h1>Turbo<span>Line</span> Support</h1>
            </div>
            <div class="header-btns">
                <a href="profile.php" class="btn btn-outline">👤 Profile</a>
                <a href="index.php" class="btn btn-outline">🏠 Home</a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message message-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message message-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- FAQ Section -->
        <div class="faq-card">
            <div class="section-title">📖 Frequently Asked Questions</div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I estimate fuel cost?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Simply select your fuel type (Petrol/Diesel/CNG), enter the amount in litres, and our system calculates the cost using real-time prices. You can also find the nearest fuel station on the map.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    Are fuel prices updated in real-time?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    We fetch fuel prices from official government APIs and update them daily. Prices may vary slightly based on your city and specific station location.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I save a favorite fuel station?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Go to the "Saved Stations" page from your profile, click "Add Station", fill in the details, and save. You can also mark stations as favorites for quick access.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    Can I export my fuel history?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Yes! Go to Settings → Data & Privacy → click "Export My Data (CSV)". This downloads all your fuel estimation history as a spreadsheet file.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    How do I reset my password?
                    <span class="faq-arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Click "Forgot Password?" on the login page, enter your registered email, and we'll send you a reset link. You can also change your password from Settings → Account.
                </div>
            </div>
        </div>
        
        <!-- Ticket Stats -->
        <?php if ($counts['total'] > 0): ?>
            <div class="stats-row">
                <div class="stat-mini">
                    <span>🎫</span>
                    <div>
                        <div class="stat-num"><?php echo $counts['total']; ?></div>
                        <div class="stat-text">Total Tickets</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <span>🟢</span>
                    <div>
                        <div class="stat-num"><?php echo $counts['open_count']; ?></div>
                        <div class="stat-text">Open</div>
                    </div>
                </div>
                <div class="stat-mini">
                    <span>✅</span>
                    <div>
                        <div class="stat-num"><?php echo $counts['resolved_count']; ?></div>
                        <div class="stat-text">Resolved</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Submit Ticket Form -->
        <div class="form-card" id="ticket-form">
            <div class="section-title">🎫 Submit a Support Ticket</div>
            
            <form method="POST" action="support.php#ticket-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="general">General Inquiry</option>
                            <option value="bug">Bug Report</option>
                            <option value="feature">Feature Request</option>
                            <option value="account">Account Issue</option>
                            <option value="pricing">Fuel Price Issue</option>
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
                
                <button type="submit" name="submit_ticket" class="btn btn-primary" style="width:100%; padding:14px;">
                    📩 Submit Ticket
                </button>
            </form>
        </div>
        
        <!-- Previous Tickets -->
        <div class="tickets-card">
            <div class="section-title">📋 Your Tickets</div>
            
            <?php if ($tickets->num_rows > 0): ?>
                <?php while ($ticket = $tickets->fetch_assoc()): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <span class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></span>
                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                <?php echo str_replace('_', ' ', $ticket['status']); ?>
                            </span>
                        </div>
                        
                        <div class="ticket-meta">
                            <span>📅 <?php echo date('d M Y, h:i A', strtotime($ticket['created_at'])); ?></span>
                            <span>🏷️ <?php echo ucfirst($ticket['category']); ?></span>
                        </div>
                        
                        <div class="ticket-message">
                            <?php echo nl2br(htmlspecialchars(substr($ticket['message'], 0, 200))); ?>
                            <?php if (strlen($ticket['message']) > 200): ?>...<?php endif; ?>
                        </div>
                        
                        <?php if (!empty($ticket['admin_reply'])): ?>
                            <div class="ticket-reply">
                                <div class="reply-label">👨‍💻 Admin Reply</div>
                                <div class="reply-text"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($ticket['status'] != 'closed'): ?>
                            <div class="ticket-actions">
                                <a href="support.php?close=<?php echo $ticket['id']; ?>" class="btn-sm close-btn" onclick="return confirm('Close this ticket?')">✕ Close Ticket</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🎫</div>
                    <h4>No Tickets Yet</h4>
                    <p>Submit a ticket above if you need help.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Contact Information -->
        <div class="contact-card">
            <div class="section-title" style="justify-content:center;">📞 Contact Us</div>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="contact-icon">📧</div>
                    <h4>Email</h4>
                    <p><a href="mailto:support@turboline.com">support@turboline.com</a></p>
                </div>
                <div class="contact-method">
                    <div class="contact-icon">📱</div>
                    <h4>Phone</h4>
                    <p><a href="tel:+919876543210">+91 98765 43210</a></p>
                </div>
                <div class="contact-method">
                    <div class="contact-icon">💬</div>
                    <h4>Live Chat</h4>
                    <p>Available 9 AM - 6 PM</p>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        // ========== FAQ TOGGLE ==========
        function toggleFaq(element) {
            const faqItem = element.parentElement;
            faqItem.classList.toggle('active');
        }
    </script>

</body>
</html>