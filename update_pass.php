<?php
require_once 'nexopay.com.bd/config/database.php';

$db = new Database();
$pdo = $db->getConnection();

try {
    // New password
    $new_password = 'superadmin123';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update or insert the admin user
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashed_password]);

    if ($stmt->rowCount() > 0) {
        echo "<h2 style='color:green'>Nexopay Admin password successfully updated to: superadmin123</h2>";
    } else {
        // Just in case it doesn't exist yet, insert it
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email) VALUES ('admin', ?, 'admin@nexopay.com')");
        $stmt->execute([$hashed_password]);
        echo "<h2 style='color:green'>Nexopay Admin created with password: superadmin123</h2>";
    }
} catch(PDOException $e) {
    echo "<h2 style='color:red'>DB Error: " . $e->getMessage() . "</h2>";
}
?>
