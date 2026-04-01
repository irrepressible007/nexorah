<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'nexopay.com.bd/config/database.php';

echo "<h2>Patching Missing Tables...</h2><ul>";
try {
    $db = (new Database())->getConnection();

    $tables = [
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS highlights (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            type ENUM('photo', 'video') NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS partners (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            logo VARCHAR(255),
            website VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS site_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS registrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_id INT,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            guardian_number VARCHAR(20) NOT NULL,
            ticket_quantity INT DEFAULT 1,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            payment_method VARCHAR(50) DEFAULT 'qr_code',
            transaction_id VARCHAR(100) NULL,
            payment_date TIMESTAMP NULL,
            approved_by INT NULL,
            approved_date TIMESTAMP NULL,
            rejection_reason TEXT NULL,
            registration_type ENUM('general', 'campus_ambassador', 'volunteer') DEFAULT 'general',
            extra_data JSON NULL,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $sql) {
        $db->exec($sql);
        echo "<li>Table verified/created successfully.</li>";
    }

    echo "</ul><h2 style='color:green'>Done! Check admin/dashboard.php now.</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>" . $e->getMessage() . "</h2>";
}
