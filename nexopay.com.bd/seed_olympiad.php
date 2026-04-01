<?php
// Script to inject National STEAM Olympiad into both Nexorah and Nexopay DBs

$title = "National STEAM Olympiad";
$desc = "Categories: Elementary, Primary, Junior, Secondary, Senior. Segments: Olympiad, Art Exhibition, Art Competition, Photography. Prizes included!";
$date = "2026-06-15"; // Placeholder date for event
$time = "09:00:00";
$location = "House-17, Road-03, Block-B, Shenpara Parbata, Mirpur-10, Dhaka-1216";
$image_nexopay = "../assets/images/public/nso logo.jpeg";
$image_nexorah = "assets/images/public/nso logo.jpeg";

// 1. NEXOPAY DATABASE
try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    
    // Check if exists
    $stmt = $db->prepare("SELECT id FROM events WHERE title = ?");
    $stmt->execute([$title]);
    $existing = $stmt->fetch();
    
    if($existing) {
        $nexopay_id = $existing['id'];
        echo "Event already exists in Nexopay with ID: $nexopay_id\n";
    } else {
        $stmt = $db->prepare("INSERT INTO events (title, description, event_date, event_time, location, image, fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $desc, $date, $time, $location, $image_nexopay, 0.00, 'upcoming']);
        $nexopay_id = $db->lastInsertId();
        echo "Inserted into Nexopay DB successfully. ID: $nexopay_id\n";
    }
} catch (Exception $e) {
    echo "Nexopay DB Error: " . $e->getMessage() . "\n";
}

// 2. NEXORAH MAIN DATABASE
try {
    require_once 'config/nexorah_db.php';
    // $pdo is defined in nexorah_db.php
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM events WHERE title = ?");
    $stmt->execute([$title]);
    $existing = $stmt->fetch();
    
    if($existing) {
        $nexorah_id = $existing['id'];
        echo "Event already exists in Nexorah with ID: $nexorah_id\n";
    } else {
        // Find columns
        $q = $pdo->query("DESCRIBE events");
        $cols = $q->fetchAll(PDO::FETCH_COLUMN);
        
        // Build generic insert ignoring complex foreign keys if they exist locally
        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, image_path, is_registration_open, is_upcoming) VALUES (?, ?, ?, ?, ?, ?, 1, 1)");
        $stmt->execute([$title, $desc, $date, $time, $location, $image_nexorah]);
        $nexorah_id = $pdo->lastInsertId();
        echo "Inserted into Nexorah DB successfully. ID: $nexorah_id\n";
    }
} catch (Exception $e) {
    echo "Nexorah DB Error: " . $e->getMessage() . "\n";
}
echo "Seeding completed!\n";
?>
