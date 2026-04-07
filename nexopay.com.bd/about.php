<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch highlights
$highlights_query = "SELECT * FROM highlights ORDER BY created_at DESC";
$highlights_stmt = $db->prepare($highlights_query);
$highlights_stmt->execute();
$highlights = $highlights_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch partners
$partners_query = "SELECT * FROM partners ORDER BY created_at DESC";
$partners_stmt = $db->prepare($partners_query);
$partners_stmt->execute();
$partners = $partners_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch ongoing events
$ongoing_query = "SELECT * FROM events WHERE status = 'ongoing' ORDER BY event_date ASC";
$ongoing_stmt = $db->prepare($ongoing_query);
$ongoing_stmt->execute();
$ongoing_events = $ongoing_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>About Us - Nexopay</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
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
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php" class="active">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="user-panel.php">My Registrations</a></li>
                <li><a href="register.php" class="nav-register-pill">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Mission Section -->
        <section class="mission-section">
            <div class="content-wrapper">
                <h1>Our Mission</h1>
                <p>Our mission is to redefine how the world paysâ€”delivering smart, secure, and truly seamless experiences. We empower individuals and businesses to move money without limits, anytime and anywhere, with absolute confidence and ease. Powered by cutting-edge technology and uncompromising security, NexoPay makes payments effortless while safeguarding your data and funds. We build trust through transparency, innovation, and exceptional service.</p>
            </div>
        </section>

        <!-- Vision Section -->
        <section class="vision-section">
            <div class="content-wrapper">
                <h2>Our Vision</h2>
                <p>Our vision is to be the most trusted digital payment platform, bridging people with their financial needs worldwide. We imagine a future where NexoPay removes complexity and borders, offering a single, accessible destination for every payment. By continually innovating and adapting to the evolving fintech landscape, we expand access to financial services, accelerate economic progress, and enable communities to pay easily, safely, and without limits.</p>
            </div>
        </section>

        <!-- Highlights Section -->
        <section class="highlights-section">
            <h2>Highlights</h2>
            <div class="highlights-grid">
                <?php if (!empty($highlights)): ?>
                    <?php foreach ($highlights as $highlight): ?>
                        <div class="highlight-item">
                            <?php if ($highlight['type'] == 'photo'): ?>
                                <img src="<?php echo htmlspecialchars($highlight['file_path']); ?>" alt="<?php echo htmlspecialchars($highlight['title']); ?>">
                            <?php else: ?>
                                <video controls>
                                    <source src="<?php echo htmlspecialchars($highlight['file_path']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php endif; ?>
                            <?php if ($highlight['title']): ?>
                                <h4><?php echo htmlspecialchars($highlight['title']); ?></h4>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No highlights available at the moment.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Partners Section -->
        <section class="partners-section">
            <h2>Our Partners</h2>
            <div class="partners-grid">
                <?php if (!empty($partners)): ?>
                    <?php foreach ($partners as $partner): ?>
                        <div class="partner-item">
                            <img src="<?php echo htmlspecialchars($partner['logo']); ?>" alt="<?php echo htmlspecialchars($partner['name']); ?>">
                            <h4><?php echo htmlspecialchars($partner['name']); ?></h4>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No partners to display at the moment.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Ongoing Events Section -->
        <section class="ongoing-events-section">
            <h2>Ongoing Events</h2>
            <div class="events-grid">
                <?php if (!empty($ongoing_events)): ?>
                    <?php foreach ($ongoing_events as $event): ?>
                        <a href="event-details.php?id=<?php echo $event['id']; ?>" class="event-card-link">
                            <div class="event-card ongoing">
                                <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <div class="event-info">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                                    <div class="event-meta">
                                        <span class="status status-ongoing">Ongoing</span>
                                        <span class="date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                                    </div>
                                    <span class="btn-secondary">View Details</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No ongoing events at the moment.</p>
                <?php endif; ?>
            </div>
        </section>
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
</body>
</html>
