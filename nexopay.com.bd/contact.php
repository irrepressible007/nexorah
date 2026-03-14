<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch site settings
$settings_query = "SELECT * FROM site_settings";
$settings_stmt = $db->prepare($settings_query);
$settings_stmt->execute();
$settings_data = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Fetch upcoming events for sidebar
$upcoming_query = "SELECT * FROM events WHERE status = 'upcoming' ORDER BY event_date ASC LIMIT 3";
$upcoming_stmt = $db->prepare($upcoming_query);
$upcoming_stmt->execute();
$upcoming_events = $upcoming_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for success parameter
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Thank you for your message! We will get back to you soon.";
}

// Handle contact form submission
if ($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $phone = $_POST['phone'];
    $message = $_POST['message'];

    $insert_query = "INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(':name', $name);
    $insert_stmt->bindParam(':email', $email);
    $insert_stmt->bindParam(':subject', $subject);
    $insert_stmt->bindParam(':message', $message);
    
    if ($insert_stmt->execute()) {
        $success_message = "Thank you for your message! We will get back to you soon.";
        // Redirect to prevent form resubmission
        header("Location: contact.php?success=1");
        exit();
    } else {
        $error_message = "Failed to send message. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Nexopay</title>
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/images/public/Nexopay logo.jpeg">
    <link rel="shortcut icon" href="assets/images/public/Nexopay logo.jpeg">
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
                    <img src="assets/images/public/Nexopay logo.jpeg" alt="Nexopay Logo" style="height: 45px; width: auto;">
                </a>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="contact-wrapper">
            <div class="contact-form-section">
                <h1>Contact Us</h1>
                <p>Get in touch with us for any inquiries or support.</p>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" class="contact-form">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </div>

            <div class="contact-info-section">
                <div class="contact-info">
                    <h3>Contact Information</h3>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p><?php echo isset($settings['contact_email']) ? htmlspecialchars($settings['contact_email']) : 'contact@nexopay.com'; ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p><?php echo isset($settings['contact_phone']) ? htmlspecialchars($settings['contact_phone']) : '+880123456789'; ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p><?php echo isset($settings['address']) ? htmlspecialchars($settings['address']) : 'Dhaka, Bangladesh'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="social-links">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <?php if (isset($settings['facebook_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>" target="_blank">
                                <i class="fab fa-facebook"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (isset($settings['twitter_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['twitter_url']); ?>" target="_blank">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (isset($settings['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($settings['linkedin_url']); ?>" target="_blank">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="upcoming-events-sidebar">
                    <h3>Upcoming Events</h3>
                    <?php if (!empty($upcoming_events)): ?>
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="sidebar-event">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p class="event-date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></p>
                                <p class="event-fee">৳<?php echo number_format($event['fee'], 2); ?></p>
                                <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn-small">Register</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No upcoming events.</p>
                    <?php endif; ?>
                </div>
            </div>
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
</body>
</html>