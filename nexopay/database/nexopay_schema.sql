-- Nexopay Database Schema
-- Complete SQL file ready for execution

-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS highlights;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS site_settings;

-- Admin users table (UPDATED VERSION)
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0.00,
    max_participants INT DEFAULT 100,
    image VARCHAR(255),
    status ENUM('upcoming', 'ongoing', 'past') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registrations table (with all required fields for the registration form)
CREATE TABLE registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    guardian_number VARCHAR(20) NOT NULL,
    ticket_quantity INT DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Highlights table
CREATE TABLE highlights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    type ENUM('photo', 'video') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Partners table
CREATE TABLE partners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    website VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Site settings table
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL
);

-- Insert admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@nexopay.com');

-- Insert site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES 
('contact_email', 'info@nexopay.com'),
('contact_phone', '+880 1234 567890'),
('facebook_url', 'https://facebook.com/nexopay'),
('twitter_url', 'https://twitter.com/nexopay'),
('linkedin_url', 'https://linkedin.com/company/nexopay'),
('address', 'Dhaka, Bangladesh');
-- Nexopay Database Schema - Updated with Payment System
-- Complete SQL file ready for execution

-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS highlights;
DROP TABLE IF EXISTS partners;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS site_settings;

-- Admin users table (UPDATED VERSION)
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Events table
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0.00,
    max_participants INT DEFAULT 100,
    image VARCHAR(255),
    status ENUM('upcoming', 'ongoing', 'past') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Updated Registrations table with payment and approval system
CREATE TABLE registrations (
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
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Highlights table
CREATE TABLE highlights (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    type ENUM('photo', 'video') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Partners table
CREATE TABLE partners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    website VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Site settings table
CREATE TABLE site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL
);

-- Insert admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@nexopay.com');

-- Insert site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES 
('contact_email', 'info@nexopay.com'),
('contact_phone', '+880 1234 567890'),
('facebook_url', 'https://facebook.com/nexopay'),
('twitter_url', 'https://twitter.com/nexopay'),
('linkedin_url', 'https://linkedin.com/company/nexopay'),
('address', 'Dhaka, Bangladesh'),
('qr_payment_info', 'Scan the QR code to complete your payment'),
('payment_instructions', 'After scanning the QR code and making payment, click Register to submit your registration.');