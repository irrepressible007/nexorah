<?php
// Database test script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    require_once '../includes/db.php';
    echo "✅ Database connection successful<br>";
    
    // Test if tables exist
    $tables = ['events', 'website_analytics', 'event_views', 'user_interactions', 'audience_data', 'analytics_summary'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Test events table structure
    echo "<h3>Events Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE events");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "<br>";
    }
    
    // Test if events directory exists
    $eventsDir = '../uploads/events';
    if (is_dir($eventsDir)) {
        echo "✅ Events directory exists<br>";
        if (is_writable($eventsDir)) {
            echo "✅ Events directory is writable<br>";
        } else {
            echo "❌ Events directory is not writable<br>";
        }
    } else {
        echo "❌ Events directory missing<br>";
        // Create the directory
        if (mkdir($eventsDir, 0755, true)) {
            echo "✅ Created events directory<br>";
        } else {
            echo "❌ Failed to create events directory<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>