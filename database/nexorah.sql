-- Create database
CREATE DATABASE IF NOT EXISTS nexorah;
USE nexorah;

-- Highlights table
CREATE TABLE IF NOT EXISTS highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    media_type ENUM('image', 'video') NOT NULL,
    media_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Partners table
CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    event_date DATE,
    event_time TIME,
    location VARCHAR(255),
    is_upcoming BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blog posts table
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample data
INSERT INTO highlights (title, description, media_type, media_path) VALUES
('Youth Conference 2023', 'Bringing together young minds for innovation', 'image', 'assets/images/highlights/highlight1.jpg'),
('Tech Summit', 'Exploring the future of technology', 'image', 'assets/images/highlights/highlight2.jpg'),
('Sports Tournament', 'Annual inter-college sports competition', 'video', 'assets/videos/highlights/highlight3.mp4');

INSERT INTO partners (name, image_path, description) VALUES
('TechCorp', 'assets/images/partners/partner1.jpg', 'Leading technology partner'),
('EduAlliance', 'assets/images/partners/partner2.jpg', 'Educational institution alliance'),
('SportX', 'assets/images/partners/partner3.jpg', 'Sports equipment and sponsorship');

INSERT INTO events (title, description, image_path, event_date, event_time, location, is_upcoming) VALUES
('Annual Tech Conference', 'Join us for the biggest tech event of the year', 'assets/images/events/event1.jpg', '2023-12-15', '09:00:00', 'Convention Center, Downtown', TRUE),
('Science Fair 2023', 'Showcasing innovations from young scientists', 'assets/images/events/event2.jpg', '2023-11-20', '10:00:00', 'University Campus', TRUE),
('Sports Tournament Finals', 'The culmination of months of competition', 'assets/images/events/event3.jpg', '2023-10-05', '14:00:00', 'City Stadium', FALSE);

INSERT INTO blog_posts (title, content, image_path) VALUES
('The Impact of Technology on Modern Education', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'assets/images/blog/blog1.jpg'),
('How Sports Shapes Youth Development', 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.', 'assets/images/blog/blog2.jpg');


-- Modify blog_posts table to support multiple media types
ALTER TABLE blog_posts ADD COLUMN media_type ENUM('image', 'video', 'text') DEFAULT 'text' AFTER content;
ALTER TABLE blog_posts CHANGE image_path media_path VARCHAR(255);
ALTER TABLE highlights ADD COLUMN is_hero BOOLEAN DEFAULT 0;
-- Create social media links table
CREATE TABLE IF NOT EXISTS social_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default social media platforms
INSERT INTO social_media (platform, url, icon) VALUES
('Facebook', '#', 'fab fa-facebook-f'),
('Twitter', '#', 'fab fa-twitter'),
('Instagram', '#', 'fab fa-instagram'),
('LinkedIn', '#', 'fab fa-linkedin-in');

-- Create achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    value VARCHAR(20) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default achievement values
INSERT INTO achievements (title, value, icon, display_order) VALUES
('Events Organized', '50+', 'fas fa-trophy', 1),
('Happy Participants', '10,000+', 'fas fa-users', 2),
('Partner Organizations', '25+', 'fas fa-handshake', 3),
('Countries Reached', '5+', 'fas fa-globe', 4);

-- Analytics Tables
-- Website analytics table for tracking page views and general site metrics
CREATE TABLE IF NOT EXISTS website_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255) NOT NULL,
    page_title VARCHAR(255),
    visitor_ip VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(255),
    session_id VARCHAR(100),
    country VARCHAR(100),
    city VARCHAR(100),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(100),
    visit_duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event views table for tracking specific event page visits
CREATE TABLE IF NOT EXISTS event_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    visitor_ip VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(255),
    session_id VARCHAR(100),
    country VARCHAR(100),
    city VARCHAR(100),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    view_duration INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- User interactions table for tracking clicks, form submissions, etc.
CREATE TABLE IF NOT EXISTS user_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    interaction_type ENUM('click', 'form_submit', 'download', 'share', 'like', 'comment') NOT NULL,
    page_url VARCHAR(255),
    element_id VARCHAR(100),
    element_class VARCHAR(100),
    element_text VARCHAR(255),
    event_id INT NULL,
    visitor_ip VARCHAR(45),
    session_id VARCHAR(100),
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
);

-- Audience data table for demographic and behavior analysis
CREATE TABLE IF NOT EXISTS audience_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE,
    visitor_ip VARCHAR(45),
    country VARCHAR(100),
    city VARCHAR(100),
    region VARCHAR(100),
    timezone VARCHAR(50),
    language VARCHAR(10),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(100),
    operating_system VARCHAR(100),
    screen_resolution VARCHAR(20),
    first_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_visit TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    total_visits INT DEFAULT 1,
    total_page_views INT DEFAULT 1,
    total_time_spent INT DEFAULT 0
);

-- Analytics summary table for quick dashboard metrics
CREATE TABLE IF NOT EXISTS analytics_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value INT NOT NULL,
    metric_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric_date (metric_name, metric_date)
);

-- Insert initial analytics summary data
INSERT IGNORE INTO analytics_summary (metric_name, metric_value, metric_date) VALUES
('total_page_views', 0, CURDATE()),
('unique_visitors', 0, CURDATE()),
('total_events_viewed', 0, CURDATE()),
('total_interactions', 0, CURDATE());