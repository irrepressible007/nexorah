<?php
require_once 'includes/auth_check.php';
require_once '../config/database.php';

echo "<h2>Debug Information</h2>";
echo "<p>Admin logged in: " . (isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No') . "</p>";
echo "<p>Admin ID: " . (isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Not set') . "</p>";
echo "<p>Admin Username: " . (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Not set') . "</p>";

$database = new Database();
$db = $database->getConnection();

// Test database connection
try {
    $query = "SELECT COUNT(*) as count FROM registrations WHERE approval_status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Pending registrations: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Test admin_users table
try {
    $query = "SELECT id, username FROM admin_users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Available Admins:</h3>";
    foreach ($admins as $admin) {
        echo "<p>ID: {$admin['id']}, Username: {$admin['username']}</p>";
    }
} catch (Exception $e) {
    echo "<p>Admin table error: " . $e->getMessage() . "</p>";
}
?>
