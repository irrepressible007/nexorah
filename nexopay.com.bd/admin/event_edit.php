<?php
session_start();
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    header('Location: events.php');
    exit();
}

// Handle form submission
if ($_POST) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = $_POST['location'];
    $fee = $_POST['fee'];
    $max_participants = $_POST['max_participants'];
    $status = $_POST['status'];
    
    $query = "UPDATE events SET title = :title, description = :description, event_date = :event_date, 
              event_time = :event_time, location = :location, fee = :fee, max_participants = :max_participants, 
              status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':event_date', $event_date);
    $stmt->bindParam(':event_time', $event_time);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':fee', $fee);
    $stmt->bindParam(':max_participants', $max_participants);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $event_id);
    
    if ($stmt->execute()) {
        $success_message = "Event updated successfully.";
    } else {
        $error_message = "Failed to update event.";
    }
}

// Get event details
$query = "SELECT * FROM events WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: events.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="../assets/js/admin.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <h1>Edit Event</h1>
            <a href="events.php" class="btn btn-secondary">â† Back to Events</a>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" id="event_date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time">Event Time</label>
                        <input type="time" id="event_time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fee">Fee (à§³)</label>
                        <input type="number" id="fee" name="fee" step="0.01" value="<?php echo $event['fee']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" value="<?php echo $event['max_participants']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="upcoming" <?php echo $event['status'] == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="ongoing" <?php echo $event['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="past" <?php echo $event['status'] == 'past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
