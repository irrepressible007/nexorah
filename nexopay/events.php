<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all events
$query = "SELECT * FROM events ORDER BY 
          CASE 
              WHEN status = 'upcoming' THEN 1
              WHEN status = 'ongoing' THEN 2
              WHEN status = 'past' THEN 3
          END, event_date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Nexopay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Premium font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2>Nexopay</h2>
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
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header reveal">
            <h1>All Events</h1>
            <p>Discover our upcoming, ongoing, and past events</p>
        </div>

        <div class="events-grid reveal">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <a href="event-details.php?id=<?php echo $event['id']; ?>" class="event-card-link">
                        <div class="event-card <?php echo $event['status']; ?>">
                            <?php 
                            $image_path = $event['image'];
                            if (!empty($image_path) && file_exists($image_path)) {
                                $image_src = htmlspecialchars($image_path);
                            } else {
                                $image_src = 'assets/images/placeholder.jpg';
                            }
                            ?>
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <div class="event-info">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($event['description'], 0, 150)) . '...'; ?></p>
                                <div class="event-meta">
                                    <span class="status status-<?php echo $event['status']; ?>">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                    <span class="fee">৳<?php echo number_format($event['fee'], 2); ?></span>
                                    <span class="date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                </div>
                                <?php if ($event['status'] == 'upcoming'): ?>
                                    <span class="btn-primary">Register Now</span>
                                <?php else: ?>
                                    <span class="btn-secondary">View Details</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-events">
                    <p>No events available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Nexopay</h3>
                    <p>Smart. Secure. Seamless. Premium payments, perfected.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-envelope"></i> contact@nexopay.com</p>
                    <p><i class="fas fa-phone"></i> +880123456789</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Nexopay. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- Scroll to top -->
    <button id="scrollToTop" class="scroll-to-top"><i class="fas fa-arrow-up"></i></button>
</body>
</html>