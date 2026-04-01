<?php
require_once 'config/database.php';

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "âœ… Database connection successful<br>";
    
    // Check if admin_users table exists
    $query = "SHOW TABLES LIKE 'admin_users'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "âœ… admin_users table exists<br>";
        
        // Check admin user
        $query = "SELECT * FROM admin_users WHERE username = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "âœ… Admin user found<br>";
            echo "Username: " . $admin['username'] . "<br>";
            echo "Email: " . $admin['email'] . "<br>";
            
            // Test password verification
            $test_password = 'admin123';
            if (password_verify($test_password, $admin['password'])) {
                echo "âœ… Password verification successful for 'admin123'<br>";
            } else {
                echo "âŒ Password verification failed<br>";
                echo "Current hash: " . $admin['password'] . "<br>";
                
                // Generate new hash
                $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
                echo "New hash for 'admin123': " . $new_hash . "<br>";
                
                // Update password
                $update_query = "UPDATE admin_users SET password = :password WHERE username = 'admin'";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':password', $new_hash);
                if ($update_stmt->execute()) {
                    echo "âœ… Password updated successfully<br>";
                } else {
                    echo "âŒ Failed to update password<br>";
                }
            }
        } else {
            echo "âŒ Admin user not found<br>";
            
            // Create admin user
            $username = 'admin';
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $email = 'admin@nexopay.com';
            
            $insert_query = "INSERT INTO admin_users (username, password, email) VALUES (:username, :password, :email)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':username', $username);
            $insert_stmt->bindParam(':password', $password);
            $insert_stmt->bindParam(':email', $email);
            
            if ($insert_stmt->execute()) {
                echo "âœ… Admin user created successfully<br>";
            } else {
                echo "âŒ Failed to create admin user<br>";
            }
        }
    } else {
        echo "âŒ admin_users table does not exist<br>";
        echo "Please run the SQL schema file to create the database structure.<br>";
    }
    
} catch (Exception $e) {
    echo "âŒ Error: " . $e->getMessage() . "<br>";
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<br><strong>The 'nexopay' database doesn't exist. Please create it first:</strong><br>";
        echo "1. Open phpMyAdmin or MySQL command line<br>";
        echo "2. Run: CREATE DATABASE nexopay;<br>";
        echo "3. Then import the schema file: nexopay_schema.sql<br>";
    }
}
?>
