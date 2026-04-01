<?php
/**
 * Database Schema Fixer for Live Server
 * Upload/Push to live server and run from browser via yoursite.com/live_fix.php
 */
session_start();
require_once 'nexopay.com.bd/config/database.php';

echo "<!DOCTYPE html><html><head><title>DB Fixer</title><style>body{font-family:sans-serif;padding:30px;line-height:1.6;} .ok {color:green;font-weight:bold;} .err {color:red;}</style></head><body><h1>Running Database fixes...</h1><ul>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check and add columns to `registrations` table
    $columns = [
        'registration_type' => "ENUM('general', 'campus_ambassador', 'volunteer') DEFAULT 'general'",
        'payment_status' => "ENUM('pending', 'paid', 'failed') DEFAULT 'pending'",
        'approval_status' => "ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
        'payment_method' => "VARCHAR(50) DEFAULT 'qr_code'",
        'transaction_id' => "VARCHAR(100) NULL",
        'payment_date' => "TIMESTAMP NULL",
        'approved_by' => "INT NULL",
        'approved_date' => "TIMESTAMP NULL",
        'rejection_reason' => "TEXT NULL",
        'extra_data' => "JSON NULL"
    ];
    
    foreach ($columns as $col => $def) {
        try {
            // Check if column exists by attempting to select it
            $db->query("SELECT `$col` FROM registrations LIMIT 1");
            echo "<li>Column <b>$col</b> already exists. <span class='ok'>OK</span></li>";
        } catch(PDOException $e) {
            // If the query fails, the column likely does not exist, so add it
            try {
                $db->exec("ALTER TABLE registrations ADD COLUMN `$col` $def");
                echo "<li>Column <b>$col</b> added successfully! <span class='ok'>OK</span></li>";
            } catch(PDOException $e2) {
                echo "<li><span class='err'>Error adding $col: " . $e2->getMessage() . "</span></li>";
            }
        }
    }
    
    echo "</ul><h2>All database structures verified and updated! You can check your admin dashboard now. Please delete this file afterwards.</h2></body></html>";

} catch (Exception $e) {
    echo "</ul><h2><span class='err'>Critical Error: " . $e->getMessage() . "</span></h2></body></html>";
}
