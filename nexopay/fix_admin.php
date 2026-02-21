<?php
/**
 * Direct Admin Fix Script
 * This will diagnose and fix the admin login issue immediately
 */

require_once 'config/database.php';

echo "<h1>Admin Login Fix</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Test database connection
    echo "<h2>Testing Database Connection...</h2>";
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "<div class='error'>❌ Database connection failed!</div>";
        echo "<p>Please check:</p>";
        echo "<ul><li>XAMPP MySQL is running</li><li>Database 'nexopay' exists</li></ul>";
        exit;
    }
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Check if admin_users table exists
    echo "<h2>Checking Database Structure...</h2>";
    $result = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($result->rowCount() == 0) {
        echo "<div class='info'>Creating admin_users table...</div>";
        $sql = "
            CREATE TABLE admin_users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $db->exec($sql);
        echo "<div class='success'>✅ Table created</div>";
    } else {
        echo "<div class='success'>✅ admin_users table exists</div>";
    }
    
    // Remove any existing admin user and create fresh one
    echo "<h2>Setting Up Admin User...</h2>";
    $db->exec("DELETE FROM admin_users WHERE username = 'admin'");
    
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@nexopay.com';
    
    $stmt = $db->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
    $result = $stmt->execute([$username, $password, $email]);
    
    if ($result) {
        echo "<div class='success'>✅ Admin user created successfully!</div>";
        
        // Test the password immediately
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify('admin123', $admin['password'])) {
            echo "<div class='success'>✅ Password verification test passed!</div>";
            echo "<h2>🎉 Setup Complete!</h2>";
            echo "<div class='info'>";
            echo "<strong>Your login credentials:</strong><br>";
            echo "Username: <strong>admin</strong><br>";
            echo "Password: <strong>admin123</strong><br>";
            echo "<br><a href='admin/login.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Go to Admin Login</a>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ Password verification failed!</div>";
        }
    } else {
        echo "<div class='error'>❌ Failed to create admin user</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<div class='error'><strong>The 'nexopay' database doesn't exist!</strong></div>";
        echo "<p>Please:</p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
        echo "<li>Click 'New' to create a database</li>";
        echo "<li>Name it 'nexopay'</li>";
        echo "<li>Click 'Create'</li>";
        echo "<li>Then run this script again</li>";
        echo "</ol>";
    }
}
?>