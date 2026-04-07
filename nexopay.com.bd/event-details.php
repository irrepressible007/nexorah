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
    <style>
        .event-desc-content, .event-desc-content * {
            color: #e2e8f0 !important;
            background-color: transparent !important;
        }
    </style>
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
                <div class="event-desc-content">
                    <?php if ($event_id == 3): ?>
                        <p style="margin-bottom: 20px;">Join us for the <strong>National STEAM Olympiad</strong> &mdash; a premier multi-discipline competition designed to inspire young minds across Bangladesh. Compete in STEAM Olympiad, Art, and Photography segments. All registrations are individual (solo).</p>
                        
                        <h4 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.2rem; color: #fff;">Segment Details</h4>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8;">
                            <li>Organized in two rounds: Selection Round and Final Round.</li>
                            <li>Final Round will be held alongside the National STEAM Carnival.</li>
                            <li>Selection Round is institution-based.</li>
                            <li>Using a calculator is prohibited.</li>
                            <li>Format: Short questions / MCQ.</li>
                            <li>Participants must bring their own stationery (pen, pencil, board, etc.).</li>
                            <li>Top 100 of Selection Round receive medals and achievement certificates.</li>
                            <li>Top 300 of Selection Round advance to the Final Round.</li>
                            <li>Theme: Astronomy, Space, Technology & Science.</li>
                        </ul>
                        
                        <h4 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.2rem; color: #fff;">Art Exhibition &amp; Competition</h4>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8;">
                            <li>Digital artworks are not permitted.</li>
                            <li>Participants may submit sketch and/or colored art.</li>
                            <li>Canvas/paper sizes: 8&times;8", 10&times;8", 12&times;8", 12&times;12", 20&times;12", 20&times;20" (inches).</li>
                            <li>Any art materials are permitted.</li>
                            <li>Participants must sign their artwork.</li>
                            <li>Stolen or copied artwork will be disqualified.</li>
                            <li>Captions are mandatory (English or Bangla) &mdash; include Caption, Name, and Institution on the back of the artwork.</li>
                            <li>Authorities may decline artworks containing nudity, violence, or political content.</li>
                            <li>No changes allowed after submission.</li>
                            <li>Artwork must be submitted by the first day of the event. All submitted artworks will be displayed.</li>
                            <li>Only for Primary (Class 1&ndash;5) and Junior (Class 6&ndash;8) participants.</li>
                            <li>Participants have 1 hour to complete their artwork.</li>
                            <li>Bring your own materials: color pencils, board, pencil, eraser, sharpener, etc.</li>
                            <li>Theme: Open.</li>
                        </ul>

                        <h4 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.2rem; color: #fff;">Photography</h4>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8;">
                            <li>Photos must be submitted before the event &mdash; this is a pre-registration exhibition.</li>
                            <li>All photos will be framed and displayed at the event.</li>
                            <li>Submit photos in high-resolution via a downloadable drive link.</li>
                            <li>Each participant may submit one photo.</li>
                            <li>Stolen or copied photos will be disqualified.</li>
                            <li>Captions are mandatory (English or Bangla) &mdash; include Caption, Name, and Institution with the photo / in the registration form.</li>
                            <li>Authorities may decline photos containing nudity, violence, or political content.</li>
                            <li>No changes allowed after submission.</li>
                            <li>Participants may take back their framed photos after the event.</li>
                        </ul>
                    <?php else: ?>
                        <?php echo clean_html_description($event['description']); ?>
                    <?php endif; ?>
                </div>
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
