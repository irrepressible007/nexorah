<?php
require_once 'config/database.php';

// Strip <style>/<script> block content from DB-stored HTML descriptions
function clean_html_description(string $html): string {
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/i', '', $html);
    return $html;
}

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header('Location: events.php');
    exit();
}

$event_id = $_GET['id'];

// Fetch event details
$query = "SELECT * FROM events WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: events.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo htmlspecialchars($event['title']); ?> - Nexopay</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body>
    <!-- Navigation -->
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
                <li><a href="events.php" class="active">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="user-panel.php">My Registrations</a></li>
                <li><a href="register.php" class="nav-register-pill">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="event-details">
            <div class="event-header">
                <?php 
                $image_path = $event['image'];
                // Ensure the path starts from root
                if (!empty($image_path) && file_exists($image_path)) {
                    $image_src = htmlspecialchars($image_path);
                } else {
                    $image_src = 'assets/images/placeholder.jpg'; // fallback image
                }
                ?>
                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                <div class="event-info">
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    <p class="event-status status-<?php echo $event['status']; ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </p>
                    <p class="event-date">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                    </p>
                    <p class="event-fee">
                        <i class="fas fa-tag"></i> 
                        Registration Fee: &#2547;<?php echo number_format($event['fee'], 2); ?> BDT
                    </p>
                </div>
            </div>

            <div class="event-description">
                <h3>Event Description</h3>
                <div class="event-desc-content"><?php echo clean_html_description($event['description']); ?></div>
            </div>

            <?php if ($event['status'] == 'upcoming'): ?>
                    <div class="registration-form" style="text-align: center; padding: 4rem 2rem;">
                        <h3 style="margin-bottom: 1.5rem; font-size: 1.8rem; background: none; -webkit-text-fill-color: #1a1a2e; color: #1a1a2e;">Ready to Join?</h3>
                        <p style="margin-bottom: 2rem; color: #495057; font-size: 1.1rem;">Click below to proceed to the official registration hub for this event.</p>
                        <?php
                            // Match title to determine URL query param for register.php
                            $target_url = 'register.php';
                            if (stripos($event['title'], 'carnival') !== false) {
                                $target_url = 'register.php?event_id=4';
                            } elseif (stripos($event['title'], 'olympiad') !== false) {
                                $target_url = 'register.php?event_id=6';
                            }
                        ?>
                        <a href="<?php echo htmlspecialchars($target_url); ?>" class="btn-primary btn-large" style="display: inline-block; padding: 15px 40px; font-weight: 600;">
                            Proceed to Registration <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    </div>
            <?php elseif ($event['status'] == 'ongoing'): ?>
                <div class="event-status-message">
                    <h3>Event is Currently Ongoing</h3>
                    <p>This event is currently running. Registration is not available at this time.</p>
                </div>
            <?php else: ?>
                <div class="event-status-message">
                    <h3>Event Completed</h3>
                    <p>This event has already been completed. Registration is no longer available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
