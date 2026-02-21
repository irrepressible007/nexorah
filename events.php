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
        $singleEvent = true;
        
        // Track event view when viewing single event
        require_once 'includes/analytics.php';
        trackEventView($pdo, $eventId);
    }
}

// Display single event
if ($singleEvent) :
    $eventDate = new DateTime($event['event_date']);
    // Fix image path
    $imagePath = !empty($event['image_path']) ? '9173182/' . $event['image_path'] : 'assets/images/default-event.jpg';
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
                        <span><i class="far fa-calendar-alt"></i> <?php echo $eventDate->format('M d, Y'); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                    <div class="event-description">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="event-sidebar" data-aos="fade-up">
                    <div class="widget">
                        <h3>Event Details</h3>
                        <ul class="event-info">
                            <li><i class="far fa-calendar-alt"></i> <strong>Date:</strong> <?php echo $eventDate->format('F d, Y'); ?></li>
                            <li><i class="far fa-clock"></i> <strong>Time:</strong> <?php echo date('h:i A', strtotime($event['event_time'])); ?></li>
                        </ul>
                    </div>
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
                        // Fix image path
                        $imagePath = !empty($event['image_path']) ? '9173182/' . $event['image_path'] : 'assets/images/default-event.jpg';
                ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                    <div class="event-card" data-event-id="<?php echo $event['id']; ?>">
                        <div class="event-img">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo $eventDate->format('M d, Y'); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                <a href="events.php?id=<?php echo $event['id']; ?>" class="btn btn-primary learn-more-btn" data-event-id="<?php echo $event['id']; ?>">Learn More</a>
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
                        // Fix image path
                        $imagePath = !empty($event['image_path']) ? '9173182/' . $event['image_path'] : 'assets/images/default-event.jpg';
                ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                    <div class="event-card past-event" data-event-id="<?php echo $event['id']; ?>">
                        <div class="event-img">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <div class="past-event-overlay">
                                <span>Past Event</span>
                            </div>
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span><i class="far fa-calendar-alt"></i> <?php echo $eventDate->format('M d, Y'); ?></span>
                                <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-end align-items-center">
                                <a href="events.php?id=<?php echo $event['id']; ?>" class="btn btn-primary learn-more-btn" data-event-id="<?php echo $event['id']; ?>">Learn More</a>
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
// Track Learn More button clicks
document.addEventListener('DOMContentLoaded', function() {
    const learnMoreButtons = document.querySelectorAll('.learn-more-btn');
    
    learnMoreButtons.forEach(button => {
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