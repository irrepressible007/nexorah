<?php
session_start();
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_POST) {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $query = "INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) 
                     ON DUPLICATE KEY UPDATE setting_value = :value";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->execute();
        }
    }
    $success_message = "Settings updated successfully.";
}

// Get current settings
$query = "SELECT * FROM site_settings";
$stmt = $db->prepare($query);
$stmt->execute();
$settings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Site Settings</h1>
            <p>Manage website configuration</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST">
                <h3>Contact Information</h3>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Contact Phone</label>
                    <input type="text" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                </div>
                
                <h3>Social Media</h3>
                
                <div class="form-group">
                    <label for="facebook_url">Facebook URL</label>
                    <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="twitter_url">Twitter URL</label>
                    <input type="url" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="linkedin_url">LinkedIn URL</label>
                    <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>">
                </div>
                
                <button type="submit" name="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </main>
    
    
    <script defer src="../assets/js/admin.js"></script>
</body>
</html>