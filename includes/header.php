<?php
// Start session
session_start();

// Include database connection
require_once __DIR__ . '/db.php';

// Include helper functions
require_once __DIR__ . '/functions.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Nexorah - <?php echo ucfirst(str_replace(".php", "", $current_page)); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/public/Nexorah New logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nexorah New logo.png">
    <meta name="msapplication-TileColor" content="#002147">
    <meta name="theme-color" content="#002147">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Dark Mode + Mobile Optimisation -->
    ">
</head>
<body>
    <!-- Header Section -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="index.php">
                    <img src="assets/images/public/Nexorah New logo.png" alt="Nexorah Logo" style="height: 65px; width: auto; object-fit: contain;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="about.php">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>" href="services.php">What We Do</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'events.php') ? 'active' : ''; ?>" href="events.php">Events</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'blog.php') ? 'active' : ''; ?>" href="blog.php">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact Us</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center ms-lg-3 mt-3 mt-lg-0">
                        <a href="https://nexopay.com.bd/register.php" target="_blank" class="btn btn-light text-primary fw-bold text-uppercase px-4 py-2 rounded-pill shadow-sm" style="background-color: #fff;">
                            Register
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Main Content -->
    <main>
