<?php 
// Include database connection at the top
require_once 'includes/db.php';

// Get achievements from database
$stmt = $pdo->query("SELECT * FROM achievements ORDER BY display_order ASC");
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php'; 
?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-banner-content">
                    <h2>About Us</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">About Us</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section section-padding">
    <div class="container">
        <div class="row align-items-center">
            <!-- Remove the image column entirely -->
            <div class="col-lg-12" data-aos="fade-left">
                <div class="about-content">
                    <div class="section-title text-left">
                        <h2>Who We Are</h2>
                    </div>
                    <p>Nexorah stands as a distinguished leader in event management, dedicated to crafting extraordinary experiences that transcend expectations. Established with an unwavering commitment to excellence, we have evolved into a trusted authority in the event industry landscape.</p>
                    <p>Our collective of visionary professionals works with precision and passion to transform concepts into captivating realities, ensuring each event we orchestrate becomes an unparalleled success. We firmly believe in the transformative power of events to inspire connections and create indelible impressions.</p>
                    <p>With a rich heritage of expertise and an unyielding dedication to innovation, we continuously redefine the boundaries of possibility in event management, delivering experiences that resonate long after the final moment.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Unique Achievements Section -->
<section class="achievements-section section-padding">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Our Achievements</h2>
            <p>Milestones that define our journey</p>
        </div>
        
        <div class="row">
            <?php foreach ($achievements as $index => $achievement): ?>
            <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="<?php echo $achievement['icon']; ?>"></i>
                    </div>
                    <h3><?php echo $achievement['value']; ?></h3>
                    <p><?php echo $achievement['title']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
