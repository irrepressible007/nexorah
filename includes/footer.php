<?php
// Get active social media links
$socialMediaQuery = "SELECT * FROM social_media WHERE is_active = 1 ORDER BY platform ASC";
$socialMediaStmt = $pdo->query($socialMediaQuery);
$socialMediaLinks = $socialMediaStmt->fetchAll();
?>
</main>
    <!-- Footer Section -->
    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-about">
                        <h3>About Nexorah</h3>
                        <p>Nexorah is about creating event experiences that inspire, engage and empower communities. We are committed to transform ideas into impactful realities.</p>
                        <div class="social-links">
                            <?php if (count($socialMediaLinks) > 0): ?>
                                <?php foreach ($socialMediaLinks as $link): ?>
                                    <a href="<?php echo $link['url']; ?>" target="_blank"><i class="<?php echo $link['icon']; ?>"></i></a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <a href="#"><i class="fab fa-facebook-f"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="footer-links">
                        <h3>Quick Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="services.php">What We Do</a></li>
                            <li><a href="events.php">Events</a></li>
                            <li><a href="blog.php">Blog</a></li>
                            <li><a href="contact.php">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="footer-contact">
                        <h3>Contact Us</h3>
                        <p><i class="fas fa-map-marker-alt"></i>  House- 17, Road-03, Block-B, Shenpara Parbata, Mirpur-10</p>
                        <p><i class="fas fa-phone"></i>+8801857108193</p>
                        <p><i class="fas fa-envelope"></i>info@nexorah.com.bd</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <p>&copy; <?php echo date('Y'); ?> Nexorah. All Rights Reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Analytics Tracking -->
    <script src="assets/js/analytics.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });
        
        // Change navbar background on scroll
        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $('.navbar').addClass('navbar-scrolled');
            } else {
                $('.navbar').removeClass('navbar-scrolled');
            }
        });
    </script>
</body>
</html>
