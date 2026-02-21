<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_email = $_GET['email'] ?? '';
$user_registrations = [];

if (!empty($user_email)) {
    // Get user registrations
    $query = "SELECT r.*, e.title as event_title, e.event_date, e.location, e.image_url 
              FROM registrations r 
              LEFT JOIN events e ON r.event_id = e.id 
              WHERE r.email = :email 
              ORDER BY r.registration_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $user_email);
    $stmt->execute();
    $user_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations - Nexopay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2><i class="fas fa-ticket-alt"></i> Nexopay</h2>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                <li><a href="user-panel.php" class="active"><i class="fas fa-user-circle"></i> My Registrations</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="user-panel-hero">
        <div class="container">
            <div class="hero-content">
                <h1><i class="fas fa-clipboard-list"></i> My Event Registrations</h1>
                <p>Track and manage all your event registrations in one place</p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="user-panel-modern">
            <?php if (empty($user_email)): ?>
                <div class="email-form-modern">
                    <div class="form-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <h3>Access Your Registrations</h3>
                    <p>Enter your email address to view all your event registrations</p>
                    <form method="GET" class="modern-form">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                            <button type="submit" class="btn-modern-primary">
                                <i class="fas fa-search"></i>
                                View Registrations
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="user-header">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h3>Welcome back!</h3>
                            <p><?php echo htmlspecialchars($user_email); ?></p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="user-panel.php" class="btn-outline">
                            <i class="fas fa-exchange-alt"></i> Change Email
                        </a>
                        <a href="events.php" class="btn-modern-secondary">
                            <i class="fas fa-plus"></i> Browse Events
                        </a>
                    </div>
                </div>
                
                <?php if (empty($user_registrations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>No Registrations Found</h3>
                        <p>You haven't registered for any events yet. Start exploring our amazing events!</p>
                        <a href="events.php" class="btn-modern-primary">
                            <i class="fas fa-calendar-plus"></i> Browse Events
                        </a>
                    </div>
                <?php else: ?>
                    <div class="registrations-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo count($user_registrations); ?></h4>
                                <p>Total Registrations</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo count(array_filter($user_registrations, function($r) { return $r['approval_status'] == 'approved'; })); ?></h4>
                                <p>Approved</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h4><?php echo count(array_filter($user_registrations, function($r) { return $r['approval_status'] == 'pending'; })); ?></h4>
                                <p>Pending</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="stat-info">
                                <h4>৳<?php echo number_format(array_sum(array_column($user_registrations, 'total_amount')), 2); ?></h4>
                                <p>Total Spent</p>
                            </div>
                        </div>
                    </div>

                    <div class="registrations-modern-grid">
                        <?php foreach ($user_registrations as $registration): ?>
                            <div class="registration-modern-card">
                                <div class="card-header">
                                    <div class="event-image">
                                        <?php if (!empty($registration['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($registration['image_url']); ?>" alt="Event Image">
                                        <?php else: ?>
                                            <div class="placeholder-image">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="status-badges">
                                        <span class="status-badge-modern status-<?php echo $registration['approval_status']; ?>">
                                            <i class="fas fa-<?php echo $registration['approval_status'] == 'approved' ? 'check' : ($registration['approval_status'] == 'rejected' ? 'times' : 'clock'); ?>"></i>
                                            <?php echo ucfirst($registration['approval_status']); ?>
                                        </span>
                                        <span class="payment-badge-modern payment-<?php echo $registration['payment_status']; ?>">
                                            <i class="fas fa-<?php echo $registration['payment_status'] == 'paid' ? 'credit-card' : ($registration['payment_status'] == 'failed' ? 'exclamation-triangle' : 'clock'); ?>"></i>
                                            <?php echo ucfirst($registration['payment_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="card-content">
                                    <h4 class="event-title"><?php echo htmlspecialchars($registration['event_title']); ?></h4>
                                    
                                    <div class="event-details">
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Event Date: <?php echo date('M d, Y', strtotime($registration['event_date'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($registration['location']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-ticket-alt"></i>
                                            <span><?php echo $registration['ticket_quantity']; ?> Ticket<?php echo $registration['ticket_quantity'] > 1 ? 's' : ''; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>৳<?php echo number_format($registration['total_amount'], 2); ?> BDT</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Registered: <?php echo date('M d, Y', strtotime($registration['registration_date'])); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($registration['transaction_id'])): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-receipt"></i>
                                                <span>Transaction: <?php echo htmlspecialchars($registration['transaction_id']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($registration['approval_status'] == 'rejected' && !empty($registration['rejection_reason'])): ?>
                                        <div class="rejection-notice">
                                            <div class="notice-header">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Rejection Reason</strong>
                                            </div>
                                            <p><?php echo htmlspecialchars($registration['rejection_reason']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="card-actions">
                                        <?php if ($registration['payment_status'] == 'paid' && $registration['approval_status'] == 'approved'): ?>
                                            <button class="btn-action success" onclick="downloadTicket(<?php echo $registration['id']; ?>)">
                                                <i class="fas fa-download"></i> Download Ticket
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn-action info" onclick="viewDetails(<?php echo $registration['id']; ?>)">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        
                                        <?php if ($registration['approval_status'] == 'pending'): ?>
                                            <button class="btn-action warning" onclick="cancelRegistration(<?php echo $registration['id']; ?>)">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function downloadTicket(registrationId) {
            // Implement ticket download functionality
            alert('Downloading ticket for registration ID: ' + registrationId);
        }
        
        function viewDetails(registrationId) {
            // Implement view details functionality
            alert('Viewing details for registration ID: ' + registrationId);
        }
        
        function cancelRegistration(registrationId) {
            if (confirm('Are you sure you want to cancel this registration?')) {
                // Implement cancellation functionality
                alert('Cancelling registration ID: ' + registrationId);
            }
        }
    </script>
</body>
</html>