<?php
$status  = $_GET['status'] ?? 'success'; // 'success' or 'error'
$message = $_GET['message'] ?? '';
$txn     = $_GET['txn'] ?? '';
$type    = $_GET['type'] ?? 'general'; // 'general', 'ca', 'volunteer'

if (empty($message)) {
    if ($status === 'success') {
        if ($type === 'ca') {
            $message = "Your Campus Ambassador application has been submitted successfully! We will review it and get back to you soon.";
        } elseif ($type === 'volunteer') {
            $message = "Your Volunteer application has been submitted successfully! We will review it and contact you.";
        } else {
            $message = "Your event registration was successful! We have received your payment details and they are currently under review.";
        }
    } else {
        $message = "Something went wrong while processing your request. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $status === 'success' ? 'Success' : 'Error'; ?> | Nexopay</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
    <style>
        .result-section {
            padding: 100px 20px;
            min-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f7fb;
        }
        .result-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            padding: 50px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: slide-up 0.4s ease-out;
        }
        @keyframes slide-up {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .status-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        .icon-success { color: #2ecc71; }
        .icon-error { color: #e74c3c; }
        
        .result-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1a2a4a;
            margin-bottom: 15px;
        }
        .result-message {
            font-size: 1.1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .txn-box {
            background: #f8f9fa;
            border: 2px dashed #3498db;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            display: inline-block;
        }
        .txn-box span {
            display: block;
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .txn-box strong {
            font-size: 1.2rem;
            color: #2c3e50;
            font-family: monospace;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-home {
            padding: 12px 30px;
            background: #e9ecef;
            color: #495057;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-home:hover { background: #dee2e6; color: #212529; }
        
        .btn-panel {
            padding: 12px 30px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-panel:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(52,152,219,0.4); }
        
        @media(max-width: 480px) {
            .result-card { padding: 40px 20px; }
            .action-buttons { flex-direction: column; }
            .btn-home, .btn-panel { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="display: flex; align-items: center; text-decoration: none;">
                    <img src="assets/images/public/Nex Pay new logo.png" alt="Nexopay Logo" style="height: 45px; width: auto;">
                </a>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="user-panel.php">My Registrations</a></li>
                <li><a href="register.php" class="active nav-register-pill" style="background-color: #fff; color: #002147 !important; font-weight: bold; text-transform: uppercase; padding: 8px 24px !important; border-radius: 50rem; box-shadow: 0 2px 4px rgba(0,0,0,.075);">Register</a></li>
            </ul>
        </div>
    </nav>

    <section class="result-section">
        <div class="container" style="display: flex; justify-content: center;">
            <div class="result-card">
                <?php if ($status === 'success'): ?>
                    <div class="status-icon icon-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="result-title">Success!</h1>
                <?php else: ?>
                    <div class="status-icon icon-error">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h1 class="result-title">Oops!</h1>
                <?php endif; ?>
                
                <p class="result-message"><?php echo htmlspecialchars($message); ?></p>
                
                <?php if ($status === 'success' && !empty($txn)): ?>
                    <div class="txn-box">
                        <span>Transaction ID</span>
                        <strong><?php echo htmlspecialchars($txn); ?></strong>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="index.php" class="btn-home"><i class="fas fa-home"></i> Back to Homepage</a>
                    <?php if ($status === 'success'): ?>
                        <a href="user-panel.php" class="btn-panel"><i class="fas fa-list-alt"></i> View My Registrations</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom" style="text-align:center;padding-top:20px;border-top:1px solid #eaeaea;">
                <p>&copy; <?php echo date('Y'); ?> Nexopay. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
