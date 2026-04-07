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

if ($event && $event['id'] == 3) {
    $event['event_date'] = '2026-05-02';
    $event['location'] = 'SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh';
}

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
                        <p style="margin-bottom: 25px; font-size: 1.05rem;">Join us for the <strong>National STEAM Olympiad</strong> &mdash; a premier multi-discipline competition designed to inspire young minds across Bangladesh. Compete in STEAM Olympiad, Art, and Photography segments. All registrations are individual (solo).</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border-left: 4px solid #3498db;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 1.15rem; color: #fff;"><i class="fas fa-layer-group"></i> Categories</h4>
                                <ul style="list-style-type: none; margin: 0; padding: 0; line-height: 1.6; font-size: 0.95rem;">
                                    <li><strong>Elementary</strong> &mdash; Class 1&ndash;3</li>
                                    <li><strong>Primary</strong> &mdash; Class 4&ndash;5</li>
                                    <li><strong>Junior</strong> &mdash; Class 6&ndash;8</li>
                                    <li><strong>Secondary</strong> &mdash; Class 9&ndash;10</li>
                                    <li><strong>Senior</strong> &mdash; Class 11&ndash;12+</li>
                                </ul>
                            </div>
                            
                            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border-left: 4px solid #2ecc71;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 1.15rem; color: #fff;"><i class="fas fa-ticket-alt"></i> Registration Fees</h4>
                                <ul style="list-style-type: none; margin: 0; padding: 0; line-height: 1.6; font-size: 0.95rem;">
                                    <li><strong>200 BDT</strong> &mdash; STEAM Olympiad</li>
                                    <li><strong>200 BDT</strong> &mdash; Art Exhibition / Competition</li>
                                    <li><strong>300 BDT</strong> &mdash; Photography</li>
                                </ul>
                            </div>
                            
                            <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px; border-left: 4px solid #f1c40f;">
                                <h4 style="margin-top: 0; margin-bottom: 10px; font-size: 1.15rem; color: #fff;"><i class="fas fa-trophy"></i> Prizes &amp; Awards</h4>
                                <ul style="list-style-type: disc; margin-left: 20px; padding: 0; line-height: 1.6; font-size: 0.95rem;">
                                    <li>Medals for selected &amp; winners</li>
                                    <li>Participant Certificates for all</li>
                                    <li>Appreciation Certificates (Olympiad winners)</li>
                                    <li>Crests for top performers</li>
                                </ul>
                            </div>
                        </div>

                        <h3 style="margin-top: 35px; margin-bottom: 20px; font-size: 1.4rem; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Segment Details</h3>
                        
                        <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 1.2rem; color: #3498db;">STEAM Olympiad &mdash; 200 BDT</h4>
                        <p style="margin-bottom: 10px; font-weight: 600; font-size: 0.95rem;">All 5 categories eligible.</p>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8; margin-bottom: 25px;">
                            <li>Organized in two rounds: <strong>Selection Round</strong> and <strong>Final Round</strong>.</li>
                            <li>Final Round will be held alongside the <strong>National STEAM Carnival</strong>.</li>
                            <li>Selection Round is <strong>institution-based</strong>.</li>
                            <li>Using a calculator is <strong>prohibited</strong>.</li>
                            <li>Format: Short questions / MCQ.</li>
                            <li>Participants must bring their own stationery (pen, pencil, board, etc.).</li>
                            <li>Top <strong>100</strong> of Selection Round receive medals and achievement certificates.</li>
                            <li>Top <strong>300</strong> of Selection Round advance to the Final Round.</li>
                        </ul>
                        
                        <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 1.2rem; color: #e74c3c;">Art Exhibition &amp; Competition &mdash; 200 BDT</h4>
                        <p style="margin-bottom: 5px; font-weight: bold; font-size: 1.05rem;">Art Exhibition &mdash; Junior, Secondary &amp; Senior</p>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8; margin-bottom: 15px;">
                            <li>Theme: <strong>Astronomy, Space, Technology &amp; Science.</strong></li>
                            <li>Digital artworks are <strong>not permitted</strong>.</li>
                            <li>Participants may submit sketch and/or colored art.</li>
                            <li>Canvas/paper sizes: 8&times;8", 10&times;8", 12&times;8", 12&times;12", 20&times;12", 20&times;20" (inches).</li>
                            <li>Any art materials are permitted.</li>
                            <li>Participants <strong>must sign</strong> their artwork.</li>
                            <li>Stolen or copied artwork will be <strong>disqualified</strong>.</li>
                            <li>Captions are mandatory (English or Bangla) &mdash; include Caption, Name, and Institution on the back of the artwork.</li>
                            <li>Authorities may decline artworks containing nudity, violence, or political content.</li>
                            <li>No changes allowed after submission.</li>
                            <li>Artwork <strong>must be submitted by the first day</strong> of the event. All submitted artworks will be displayed.</li>
                        </ul>
                        
                        <p style="margin-bottom: 5px; font-weight: bold; font-size: 1.05rem;">Art Competition Primary &amp; Junior Only</p>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8; margin-bottom: 25px;">
                            <li>Only for <strong>Primary (Class 1&ndash;5)</strong> and <strong>Junior (Class 6&ndash;8)</strong> participants.</li>
                            <li>Participants have <strong>1 hour</strong> to complete their artwork.</li>
                            <li>Bring your own materials: color pencils, board, pencil, eraser, sharpener, etc.</li>
                            <li>Both sketch and colored art are accepted.</li>
                            <li>Theme: <strong>Open</strong>.</li>
                        </ul>

                        <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 1.2rem; color: #f39c12;">Photography Exhibition &mdash; 300 BDT</h4>
                        <p style="margin-bottom: 5px; font-weight: bold; font-size: 1.05rem;">Mobile Photography &amp; DSLR Photography</p>
                        <ul style="list-style-type: disc; margin-left: 20px; line-height: 1.8; margin-bottom: 20px;">
                            <li>Photos must be submitted <strong>before the event</strong> &mdash; this is a pre-registration exhibition.</li>
                            <li>All photos will be framed and displayed at the event.</li>
                            <li>Submit photos in <strong>high-resolution</strong> via a downloadable drive link.</li>
                            <li>Each participant may submit <strong>one photo</strong>.</li>
                            <li>Stolen or copied photos will be <strong>disqualified</strong>.</li>
                            <li>Captions are mandatory (English or Bangla) &mdash; include Caption, Name, and Institution with the photo / in the registration form.</li>
                            <li>Authorities may decline photos containing nudity, violence, or political content.</li>
                            <li>No changes allowed after submission.</li>
                            <li>Participants may <strong>take back their framed photos</strong> after the event.</li>
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
