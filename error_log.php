<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

echo "<h1>Error Logging Enabled</h1>";
echo "<p>Check the php_error.log file for details.</p>";

// Test database connection
require_once 'includes/db.php';
echo "<p>Database connection successful!</p>";

// Test blog functions
require_once 'includes/functions.php';
try {
    $posts = getRecentBlogPosts($pdo, 1);
    echo "<p>Blog function test successful!</p>";
    echo "<pre>";
    print_r($posts);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
