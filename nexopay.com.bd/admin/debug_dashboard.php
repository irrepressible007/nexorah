<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Mode Active</h2><hr>";

try {
    require_once 'dashboard.php';
} catch (Throwable $e) {
    echo "<h3>Fatal Error Caught:</h3>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
