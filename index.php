<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-gradient-bg"></div>
    
    <div class="container">
        <div class="hero-content" data-aos="fade-up">
            <p class="subtitle">Empowering a Brighter Tomorrow</p>
            <h1>We Create Your Youth Memorable</h1>
            <a href="contact.php" class="btn btn-primary btn-lg">Get In Touch</a>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="mission-vision-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                <div class="mission-box">
                    <h3>Our Mission</h3>
                    <p>Nexorah is about creating event experiences that inspire, engage and empower communities. We are committed to transform ideas into impactful realities. Whether it's a sports tournament, tech or science fest or any kinds of educational activities. We believe every event is an opportunity for innovation, celebration and bringing people together, with passion, creativity and purpose. We aim for meaningful collaboration and bold commitment to excellence.</p>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="vision-box">
                    <h3>Our Vision</h3>
                    <p>Our vision is to become a leading force in the event industry. Where creativity can gain purpose, a gathering becomes a story of success and stays remembered. Through bold ideas and strategic planning, we envision building a legacy and igniting positive changes across the generations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Highlights Section -->
<section class="highlights-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Highlights</h2>
            <p>Take a look at our recent successful events</p>
        </div>
        
        <div class="row">
            <?php
            $highlights = getHighlights($pdo);
            foreach ($highlights as $highlight) :
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="highlight-card">
                    <div class="highlight-img">
                        <?php if ($highlight['media_type'] == 'image') : ?>
                            <img src="<?php echo $highlight['media_path']; ?>" alt="<?php echo $highlight['title']; ?>">
                        <?php else : ?>
                            <video width="100%" controls>
                                <source src="<?php echo $highlight['media_path']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>
                    <div class="highlight-content">
                        <h3><?php echo $highlight['title']; ?></h3>
                        <p><?php echo htmlspecialchars(strip_tags($highlight['description'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="partners-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Partners</h2>
            <p>We collaborate with the best in the industry</p>
        </div>
        
        <div class="row">
            <?php
            $partners = getPartners($pdo);
            foreach ($partners as $partner) :
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="partner-card">
                    <div class="partner-img">
                        <img src="<?php echo $partner['image_path']; ?>" alt="<?php echo $partner['name']; ?>">
                    </div>
                    <h3><?php echo $partner['name']; ?></h3>
                    <p><?php echo htmlspecialchars(strip_tags($partner['description'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Upcoming Events Section -->
<section class="upcoming-events-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Upcoming Events</h2>
            <p>Join us for these exciting upcoming events</p>
        </div>
        
        <div class="row">
            <?php
            $upcomingEvents = getUpcomingEvents($pdo);
            
            foreach ($upcomingEvents as $event) :
                $eventDate = new DateTime($event['event_date']);
                $dispDateShort = $eventDate->format('M d, Y');
                $dispTime      = date('h:i A', strtotime($event['event_time']));
                $dispLoc       = htmlspecialchars($event['location']);
                
                if ($event['id'] == 6) {
                    $dispDateShort = 'May 02, 2026';
                    $dispTime      = '9AM to 6PM';
                    $dispLoc       = 'SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh';
                }
                if ($event['id'] == 4) {
                    $dispDateShort = '7th May to 9th May, 2026';
                    $dispTime      = '8AM to 6PM';
                    $dispLoc       = 'Bangladesh University of Engineering and Technology (BUET)';
                }
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up">
                <div class="event-card" onclick="window.location.href='events.php?id=<?php echo $event['id']; ?>'" style="cursor: pointer;">
                    <div class="event-img">
                        <img src="<?php echo (!empty($event['image_path']) ? (strpos($event['image_path'], 'assets/') === 0 ? $event['image_path'] : '9173182/' . $event['image_path']) : 'assets/images/default-event.jpg'); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
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
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $dispLoc; ?></span>
                            <a href="events.php?id=<?php echo $event['id']; ?>" class="btn btn-primary" onclick="event.stopPropagation();">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4" data-aos="fade-up">
            <a href="events.php" class="btn btn-primary">View All Events</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
