<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load the Nexorah database connection
require_once 'includes/db.php';

echo "<h2>Patching Main Nexorah Database Tables...</h2><ul>";
try {
    // Read the full SQL schema file
    $sql_file = __DIR__ . '/database/nexorah.sql';
    if (!file_exists($sql_file)) {
        die("Could not find nexorah.sql file!");
    }

    $sql = file_get_contents($sql_file);
    
    // Extract ONLY the CREATE TABLE statements to prevent dummy data crashes
    // and collision with previously renamed columns in ALTER TABLE
    preg_match_all('/CREATE TABLE IF NOT EXISTS.*?\;/is', $sql, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $createStatement) {
            $pdo->exec($createStatement);
        }
    }
    
    // Now safely try applying the alters individually just in case (suppressing errors if already applied)
    try { $pdo->exec("ALTER TABLE blog_posts ADD COLUMN media_type ENUM('image', 'video', 'text') DEFAULT 'text' AFTER content"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE blog_posts CHANGE image_path media_path VARCHAR(255)"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE highlights ADD COLUMN is_hero BOOLEAN DEFAULT 0"); } catch(Exception $e) {}

    echo "<li>All Nexorah missing tables have been verified and built successfully!</li>";
    echo "</ul><h2 style='color:green'>Done! Go refresh nexorah.com.bd homepage now!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>" . $e->getMessage() . "</h2>";
}
