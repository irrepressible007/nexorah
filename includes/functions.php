<?php
// Function to sanitize user input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to upload file
function uploadFile($file, $targetDir) {
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Generate a unique file name to avoid overwriting
    $uniqueName = uniqid() . '_' . $fileName;
    $targetFilePath = $targetDir . $uniqueName;
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'mp4', 'avi', 'mov');
    if (in_array(strtolower($fileType), $allowTypes)) {
        // Upload file to server
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            return $targetFilePath;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// Function to get all highlights
function getHighlights($pdo) {
    $stmt = $pdo->query("SELECT * FROM highlights ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Function to get all partners
function getPartners($pdo) {
    $stmt = $pdo->query("SELECT * FROM partners ORDER BY name ASC");
    return $stmt->fetchAll();
}

// Function to get upcoming events
function getUpcomingEvents($pdo, $limit = 3) {
    // Modified to include events happening today or in the future
    $stmt = $pdo->prepare("SELECT * FROM events WHERE is_upcoming = TRUE ORDER BY event_date ASC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Function to get all events
function getAllEvents($pdo) {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
    return $stmt->fetchAll();
}

// Function to get event by ID
function getEventById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to get recent blog posts
function getRecentBlogPosts($pdo, $limit = 3) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Function to get blog post by ID
function getBlogPostById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to send email
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: Nexorah <noreply@nexorah.com>' . "\r\n";
    
    // Check if running in local environment
    if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
        // For local development, just log the email instead of sending
        error_log("Email would be sent to: $to, Subject: $subject");
        error_log("Message: $message");
        return true; // Return success for local environment
    } else {
        // In production, actually send the email
        return mail($to, $subject, $message, $headers);
    }
}

// Function to get the correct NexoPay URL based on environment
function getNexopayUrl() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return 'http://localhost/nexorah/nexopay';
    } else {
        return 'https://nexopay.com.bd';
    }
}
?>
