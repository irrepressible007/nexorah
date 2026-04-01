<?php
$log_file = 'error_log';

if (file_exists($log_file)) {
    echo "<h1>Recent Error Logs</h1><pre>";
    $lines = file($log_file);
    // Show last 50 lines
    $last_lines = array_slice($lines, -50);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<h2>No error_log file found in the root directory.</h2>";
}

// Ensure errors are displayed on the screen for this script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test database connection just in case
require_once 'includes/db.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<h2 style='color:green'>Database connection successful.</h2>";
} catch(Exception $e) {
    echo "<h2 style='color:red'>Database Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
