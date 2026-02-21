<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Database connection failed. Check config/database.php.');
}

// Fetch upcoming events
$upcoming_query = "SELECT * FROM events WHERE status = 'upcoming' ORDER BY event_date ASC LIMIT 3";
$upcoming_stmt = $db->prepare($upcoming_query);
$upcoming_stmt->execute();
$upcoming_events = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch past events
$past_query = "SELECT * FROM events WHERE status = 'past' ORDER BY event_date DESC LIMIT 3";
$past_stmt = $db->prepare($past_query);
$past_stmt->execute();
$past_events = $past_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexopay - Smart Secure Seamless</title>
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content reveal">
                <h3 class="subtitle">Smart. Secure. Seamless.</h3>
                <h1 class="main-heading">Make smarter, secure payments—without limits. Anytime. Anywhere.</h1>
                <a href="events.php" class="cta-button"><i class="fas fa-bolt"></i> Explore Signature Events</a>
            </div>
        </div>
    </section>

    <!-- Upcoming Events Section -->
    <section class="upcoming-events">
        <div class="container reveal">
            <h2>Upcoming Events</h2>
            <div class="events-grid">
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event): ?>
                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="event-card-link">
                            <div class="event-card">
                                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <div class="event-info">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                                    <div class="event-meta">
                                        <span class="fee">৳<?php echo number_format($event['fee'], 2); ?></span>
                                        <span class="date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    <span class="btn-primary">Register Now</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No upcoming events at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Past Events Section -->
    <section class="past-events">
        <div class="container reveal">
            <h2>Past Events</h2>
            <div class="events-grid">
                <?php if (!empty($past_events)): ?>
                    <?php foreach ($past_events as $event): ?>
                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="event-card-link">
                            <div class="event-card past">
                                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <div class="event-info">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                                    <div class="event-meta">
                                        <span class="status status-past">Past Event</span>
                                        <span class="date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    <span class="btn-secondary">View Details</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No past events to display.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

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

    <script src="assets/js/script.js"></script>
    <!-- Scroll to top -->
    <button id="scrollToTop" class="scroll-to-top"><i class="fas fa-arrow-up"></i></button>
</body>
</html>