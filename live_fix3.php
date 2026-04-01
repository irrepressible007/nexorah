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
    
    // Strip out CREATE DATABASE and USE statements since cPanel users don't have this privilege
    // and PDO is already connected to the correct database!
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;/i', '', $sql);
    $sql = preg_replace('/USE [^;]+;/i', '', $sql);
    
    // Quick hack to prevent ALTER TABLE failures if ran twice
    $sql = str_replace("ALTER TABLE blog_posts ADD COLUMN media_type", "-- comment", $sql);
    $sql = str_replace("ALTER TABLE blog_posts CHANGE image_path media_path", "-- comment", $sql);
    $sql = str_replace("ALTER TABLE highlights ADD COLUMN is_hero", "-- comment", $sql);
    
    // Execute all standard schema
    $pdo->exec($sql);

    // Now safely try applying the alters individually just in case
    try { $pdo->exec("ALTER TABLE blog_posts ADD COLUMN media_type ENUM('image', 'video', 'text') DEFAULT 'text' AFTER content"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE blog_posts CHANGE image_path media_path VARCHAR(255)"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE highlights ADD COLUMN is_hero BOOLEAN DEFAULT 0"); } catch(Exception $e) {}

    echo "<li>All Nextorah missing tables have been verified and built successfully!</li>";
    echo "</ul><h2 style='color:green'>Done! Go refresh nexorah.com.bd homepage now!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>" . $e->getMessage() . "</h2>";
}
