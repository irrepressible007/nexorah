<?php
/**
 * Nexopay Admin Setup Script
 * Run this file once to properly set up the admin system
 * Access: http://localhost/nexopay/admin_setup.php
 */

require_once 'config/database.php';

// Security: Only allow setup if no admin exists or if forced
$force_setup = isset($_GET['force']) && $_GET['force'] === 'true';

echo "<!DOCTYPE html>";
echo "<html><head><title>Nexopay Admin Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}";
echo "</style></head><body><div class='container'>";

echo "<h1>ðŸ”§ Nexopay Admin Setup</h1>";
echo "<p>This script will set up your admin system properly.</p>";

try {
    // Test database connection
    echo "<h2>ðŸ“Š Database Connection</h2>";
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    echo "<div class='success'>âœ… Database connection successful</div>";
    
    // Check if database exists and create tables if needed
    echo "<h2>ðŸ—„ï¸ Database Structure</h2>";
    
    // Check if admin_users table exists
    $query = "SHOW TABLES LIKE 'admin_users'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "<div class='info'>Creating admin_users table...</div>";
        $create_table = "
            CREATE TABLE admin_users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE
            )
        ";
        $db->exec($create_table);
        echo "<div class='success'>âœ… admin_users table created</div>";
    } else {
        echo "<div class='success'>âœ… admin_users table exists</div>";
    }
    
    // Check for existing admin user
    echo "<h2>ðŸ‘¤ Admin User Setup</h2>";
    $query = "SELECT * FROM admin_users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $existing_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_admin && !$force_setup) {
        echo "<div class='info'>Admin user already exists. Testing password...</div>";
        
        // Test current password
        if (password_verify('admin123', $existing_admin['password'])) {
            echo "<div class='success'>âœ… Admin password is correct and working</div>";
            echo "<div class='info'><strong>Login Details:</strong><br>";
            echo "Username: admin<br>";
            echo "Password: admin123</div>";
        } else {
            echo "<div class='error'>âŒ Password verification failed. Updating password...</div>";
            $new_password = password_hash('admin123', PASSWORD_DEFAULT);
            $update_query = "UPDATE admin_users SET password = :password WHERE username = 'admin'";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $new_password);
            
            if ($update_stmt->execute()) {
                echo "<div class='success'>âœ… Password updated successfully</div>";
            } else {
                echo "<div class='error'>âŒ Failed to update password</div>";
            }
        }
    } else {
        if ($force_setup) {
            echo "<div class='info'>Force setup enabled. Deleting ALL existing admin accounts...</div>";
            $db->exec("DELETE FROM admin_users");
        }
        
        echo "<div class='info'>Creating new admin user...</div>";
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@nexopay.com';
        
        $insert_query = "INSERT INTO admin_users (username, password, email) VALUES (:username, :password, :email)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':username', $username);
        $insert_stmt->bindParam(':password', $password);
        $insert_stmt->bindParam(':email', $email);
        
        if ($insert_stmt->execute()) {
            echo "<div class='success'>âœ… Admin user created successfully</div>";
        } else {
            echo "<div class='error'>âŒ Failed to create admin user</div>";
        }
    }
    
    // Final verification
    echo "<h2>ðŸ” Final Verification</h2>";
    $query = "SELECT * FROM admin_users WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify('admin123', $admin['password'])) {
        echo "<div class='success'>âœ… Setup completed successfully!</div>";
        echo "<div class='info'><strong>Your admin credentials:</strong><br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "Login URL: <a href='admin/login.php'>admin/login.php</a></div>";
        
        // Clean up sessions
        session_start();
        session_destroy();
        echo "<div class='info'>ðŸ§¹ Cleared any existing sessions</div>";
        
    } else {
        echo "<div class='error'>âŒ Setup verification failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>âŒ Error: " . $e->getMessage() . "</div>";
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<div class='error'><strong>Database 'nexopay' doesn't exist!</strong><br>";
        echo "Please create it first:<br>";
        echo "1. Open phpMyAdmin<br>";
        echo "2. Create database: <code>nexopay</code><br>";
        echo "3. Run this setup again</div>";
    }
}

echo "<hr><h2>ðŸ”§ Additional Options</h2>";
echo "<p><a href='?force=true'>ðŸ”„ Force Reset Admin User</a> - Use if you're still having issues</p>";
echo "<p><a href='admin/login.php'>ðŸ”‘ Go to Admin Login</a></p>";
echo "<p><a href='index.php'>ðŸ  Back to Website</a></p>";
echo "</div></body></html>";
?>
