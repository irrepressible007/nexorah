<?php include 'includes/header.php'; ?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-banner-content">
                    <h2>Events</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Events</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Check if specific event ID is provided
$singleEvent = false;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $event = getEventById($pdo, $eventId);
    if ($event) {
        if ($event['id'] == 6) {
            $old_loc = "House-17, Road-03, Block-B, Shenpara Parbata, Mirpur-10, Dhaka-1216";
            $new_loc = "SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh";
            $event['description'] = str_replace($old_loc, $new_loc, $event['description']);
            
            $old_contact = "+880 1857-108193";
            $new_contact = "+880 1886-365082";
            $event['description'] = str_replace($old_contact, $new_contact, $event['description']);

            // Rebuild the CSS rule for the bullets to use font-awesome instead of unicode characters
            $new_css_rule = ".nso-detail .rules-list li::before { content: '\\\\f0da'; font-family: 'Font Awesome 5 Free', 'Font Awesome 6 Free'; font-weight: 900; color: var(--primary-color, #3498db); flex-shrink: 0; margin-top: 3px; margin-right: 10px; font-size: 1.1rem; }";
            $event['description'] = preg_replace('/\.nso-detail \.rules-list li::before\s*\{[^}]+\}/', $new_css_rule, $event['description']);
            
            // Fix encoding errors on the live DB (where dashes became ?)
            $event['description'] = str_replace(' ? a', ' &mdash; a', $event['description']);
            $event['description'] = str_replace(' ? ', ' &mdash; ', $event['description']);
            $event['description'] = preg_replace('/Class (\d+)\?(\d+)/', 'Class $1&ndash;$2', $event['description']);
            $event['description'] = str_replace('8?8"', '8&times;8"', $event['description']);
            $event['description'] = str_replace('10?8"', '10&times;8"', $event['description']);
            $event['description'] = str_replace('12?8"', '12&times;8"', $event['description']);
            $event['description'] = str_replace('12?12"', '12&times;12"', $event['description']);
            $event['description'] = str_replace('20?12"', '20&times;12"', $event['description']);
            $event['description'] = str_replace('20?20"', '20&times;20"', $event['description']);
            $event['description'] = str_replace('›', '<i class="fas fa-caret-right"></i>', $event['description']);
        }
        $singleEvent = true;
        
        // Track event view when viewing single event
        require_once 'includes/analytics.php';
        trackEventView($pdo, $eventId);
    }
}

// Display single event
if ($singleEvent) :
    $eventDate = new DateTime($event['event_date']);
    // Fix image path natively vs admin uploads
    $imagePath = 'assets/images/default-event.jpg';
    if (!empty($event['image_path'])) {
        $imagePath = (strpos($event['image_path'], 'assets/') === 0) ? $event['image_path'] : '9173182/' . $event['image_path'];
    }

    $dispDateShort = $eventDate->format('M d, Y');
    $dispDateLong  = $eventDate->format('F d, Y');
    $dispTime      = date('h:i A', strtotime($event['event_time']));
    $dispLoc       = htmlspecialchars($event['location']);
    
    if ($event['id'] == 6) {
        $dispDateShort = 'May 02, 2026';
        $dispDateLong  = 'May 02, 2026';
        $dispTime      = '9AM to 6PM';
        $dispLoc       = 'SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh';
    }
    if ($event['id'] == 4) {
        $dispDateShort = 'May 07–09, 2026';
        $dispDateLong  = '7th May to 9th May, 2026';
        $dispTime      = '8AM to 6PM';
        $dispLoc       = 'Bangladesh University of Engineering and Technology (BUET)';
    }
?>

<!-- Single Event Section -->
<section class="single-event-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="single-event-content" data-aos="fade-up">
                    <div class="event-img">
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" class="img-fluid rounded">
                    </div>
                    <div class="event-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo $dispDateShort; ?></span>
                        <span><i class="far fa-clock"></i> <?php echo $dispTime; ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $dispLoc; ?></span>
                    </div>
                    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                    <div class="event-description">
                        <?php echo $event['description']; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="event-sidebar" data-aos="fade-up">
                    <div class="widget mb-4">
                        <h3>Event Details</h3>
                        <ul class="event-info">
                            <li><i class="far fa-calendar-alt"></i> <strong>Date:</strong> <?php echo $dispDateLong; ?></li>
                            <li><i class="far fa-clock"></i> <strong>Time:</strong> <?php echo $dispTime; ?></li>
                            <li><i class="fas fa-map-marker-alt"></i> <strong>Venue:</strong> <?php echo $dispLoc; ?></li>
                        </ul>
                    </div>
                    <?php if (isset($event['is_registration_open']) && $event['is_registration_open']) : ?>
                    <div class="widget p-0 bg-transparent border-0 text-center shadow-none">
                        <a href="<?php echo getNexopayUrl(); ?>/register.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary btn-lg w-100 register-btn" data-event-id="<?php echo $event['id']; ?>">Register Now</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php else : ?>

<!-- All Events Section -->
<section class="events-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Events</h2>
            <p>Discover our upcoming and past events</p>
        </div>
        
        <!-- Upcoming Events -->
        <div class="upcoming-events mb-5">
            <h3 class="mb-4" data-aos="fade-up">Upcoming Events</h3>
            <div class="row">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM events WHERE is_upcoming = TRUE ORDER BY event_date ASC");
                $stmt->execute();
                $upcomingEvents = $stmt->fetchAll();
                
                if (count($upcomingEvents) > 0) :
                    foreach ($upcomingEvents as $event) :
                        $eventDate = new DateTime($event['event_date']);
                        // Fix image path natively vs admin uploads
                        $imagePath = 'assets/images/default-event.jpg';
                        if (!empty($event['image_path'])) {
                            $imagePath = (strpos($event['image_path'], 'assets/') === 0) ? $event['image_path'] : '9173182/' . $event['image_path'];
                        }
                        
                        $dispDateShort = $eventDate->format('M d, Y');
                        $dispTime      = date('h:i A', strtotime($event['event_time']));
                        $dispLoc       = htmlspecialchars($event['location']);
                        if ($event['id'] == 6) {
                            $dispDateShort = 'May 02, 2026';
                            $dispTime      = '9AM to 6PM';
                            $dispLoc       = 'SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh';
                        }
                        if ($event['id'] == 4) {
                            $dispDateShort = 'May 07–09, 2026';
                            $dispTime      = '8AM to 6PM';
                            $dispLoc       = 'Bangladesh University of Engineering and Technology (BUET)';
                        }
                ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                    <div class="event-card" data-event-id="<?php echo $event['id']; ?>" onclick="window.location.href='events.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                        <div class="event-img" style="height: 220px; overflow: hidden;">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo $dispDateShort; ?></span>
                                <span><i class="far fa-clock"></i> <?php echo $dispTime; ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <?php 
                                $cleanDesc = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $event['description']);
                                $cleanDesc = strip_tags(str_replace('<', ' <', $cleanDesc));
                                $cleanDesc = trim(preg_replace('/\s+/', ' ', $cleanDesc));
                            ?>
                            <p><?php echo htmlspecialchars(mb_substr($cleanDesc, 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
                                <span class="text-muted" style="font-size: 0.95rem; line-height: 1.4;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $dispLoc; ?></span>
                                <?php if (isset($event['is_registration_open']) && $event['is_registration_open']) : ?>
                                    <a href="<?php echo getNexopayUrl(); ?>/register.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary register-btn flex-shrink-0" data-event-id="<?php echo $event['id']; ?>" onclick="event.stopPropagation();">Register</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                else :
                ?>
                <div class="col-12">
                    <div class="alert alert-info">No upcoming events at the moment. Please check back later.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Past Events -->
        <div class="past-events">
            <h3 class="mb-4" data-aos="fade-up">Past Events</h3>
            <div class="row">
                <?php
                $stmt = $pdo->prepare("SELECT * FROM events WHERE is_upcoming = FALSE ORDER BY event_date DESC");
                $stmt->execute();
                $pastEvents = $stmt->fetchAll();
                
                if (count($pastEvents) > 0) :
                    foreach ($pastEvents as $event) :
                        $eventDate = new DateTime($event['event_date']);
                        // Fix image path natively vs admin uploads
                        $imagePath = 'assets/images/default-event.jpg';
                        if (!empty($event['image_path'])) {
                            $imagePath = (strpos($event['image_path'], 'assets/') === 0) ? $event['image_path'] : '9173182/' . $event['image_path'];
                        }

                        $dispDateShort = $eventDate->format('M d, Y');
                        $dispTime      = date('h:i A', strtotime($event['event_time']));
                        $dispLoc       = htmlspecialchars($event['location']);
                        if ($event['id'] == 6) {
                            $dispDateShort = 'May 02, 2026';
                            $dispTime      = '9AM to 6PM';
                            $dispLoc       = 'SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh';
                        }
                        if ($event['id'] == 4) {
                            $dispDateShort = 'May 07–09, 2026';
                            $dispTime      = '8AM to 6PM';
                            $dispLoc       = 'Bangladesh University of Engineering and Technology (BUET)';
                        }
                ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                    <div class="event-card past-event" data-event-id="<?php echo $event['id']; ?>" onclick="window.location.href='events.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                        <div class="event-img" style="height: 220px; overflow: hidden;">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="past-event-overlay">
                                <span>Past Event</span>
                            </div>
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo $dispDateShort; ?></span>
                                <span><i class="far fa-clock"></i> <?php echo $dispTime; ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <?php 
                                $cleanDesc = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $event['description']);
                                $cleanDesc = strip_tags(str_replace('<', ' <', $cleanDesc));
                                $cleanDesc = trim(preg_replace('/\s+/', ' ', $cleanDesc));
                            ?>
                            <p><?php echo htmlspecialchars(mb_substr($cleanDesc, 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
                                <span class="text-muted" style="font-size: 0.95rem; line-height: 1.4;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $dispLoc; ?></span>
                                <?php if (isset($event['is_registration_open']) && $event['is_registration_open']) : ?>
                                    <a href="<?php echo getNexopayUrl(); ?>/register.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary register-btn flex-shrink-0" data-event-id="<?php echo $event['id']; ?>" onclick="event.stopPropagation();">Register</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach;
                else :
                ?>
                <div class="col-12">
                    <div class="alert alert-info">No past events to display.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php endif; ?>

<!-- Analytics Tracking Script -->
<script>
// Track Register button clicks
document.addEventListener('DOMContentLoaded', function() {
    const registerButtons = document.querySelectorAll('.register-btn');
    
    registerButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const eventId = this.getAttribute('data-event-id');
            
            // Track the interaction
            fetch('includes/track.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'click',
                    page_url: window.location.href,
                    element_id: this.id,
                    element_class: this.className,
                    element_text: this.textContent,
                    event_id: eventId,
                    element_tag: 'a'
                })
            }).catch(error => {
                console.error('Analytics tracking error:', error);
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
