<?php include 'includes/header.php'; ?>

<?php
$success = false;
$error = false;
$errorMsg = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        $error = true;
        $errorMsg = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMsg = "Please enter a valid email address.";
    } else {
        // Save message to database
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            
            // Send email notification
            $to = "info@nexorah.com"; // Change this to your email
            $emailSubject = "New Contact Form Submission: " . $subject;
            $emailMessage = "<h3>New Contact Message</h3>"
                . "<p><strong>Name:</strong> {$name}</p>"
                . "<p><strong>Email:</strong> {$email}</p>"
                . "<p><strong>Subject:</strong> {$subject}</p>"
                . "<p><strong>Message:</strong></p>"
                . "<p>{$message}</p>";
            
            if (sendEmail($to, $emailSubject, $emailMessage)) {
                $success = true;
            } else {
                // Even if email fails, the message is saved in the database
                $success = true;
            }
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="page-banner-content">
                    <h2>Contact Us</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="contact-info">
                    <div class="section-title text-left">
                        <h2>Get In Touch</h2>
                        <p>We'd love to hear from you</p>
                    </div>
                    <p>Have questions about our services or want to collaborate? Reach out to us using the contact information below or fill out the form.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <div class="icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="text">
                                <h4>Address</h4>
                                <p>House- 17, Road-03, Block-B, Shenpara Parbata, Mirpur-10</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="text">
                                <h4>Phone</h4>
                                <p>+8801857108193

</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="text">
                                <h4>Email</h4>
                                <p>info@nexorah.com</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h4>Follow Us</h4>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="contact-form">
                    <h3>Send Us a Message</h3>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        Your message has been sent successfully. We'll get back to you soon!
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $errorMsg; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="contactForm" method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="name">Your Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback" id="nameError">Please enter your name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="email">Your Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback" id="emailError">Please enter a valid email</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject">
                        </div>
                        <div class="form-group mb-3">
                            <label for="message">Your Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback" id="messageError">Please enter your message</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section Removed as Requested -->

<?php include 'includes/footer.php'; ?>
