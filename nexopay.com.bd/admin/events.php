<?php
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $fee = $_POST['fee'];
                $event_date = $_POST['event_date'];
                $event_time = $_POST['event_time'];
                $location = $_POST['location'];
                $status = $_POST['status'];
                
                // Handle image upload
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/events/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $image_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                        $image_path = 'uploads/events/' . $new_filename;
                    }
                }
                
                $insert_query = "INSERT INTO events (title, description, event_date, event_time, location, image, fee, status) VALUES (:title, :description, :event_date, :event_time, :location, :image, :fee, :status)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(':title', $name);
                $insert_stmt->bindParam(':description', $description);
                $insert_stmt->bindParam(':event_date', $event_date);
                $insert_stmt->bindParam(':event_time', $event_time);
                $insert_stmt->bindParam(':location', $location);
                $insert_stmt->bindParam(':image', $image_path);
                $insert_stmt->bindParam(':fee', $fee);
                $insert_stmt->bindParam(':status', $status);
                
                if ($insert_stmt->execute()) {
                    $success_message = "Event added successfully!";
                } else {
                    $error_message = "Error adding event.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get image path to delete from server
                $get_query = "SELECT image FROM events WHERE id = :id";
                $get_stmt = $db->prepare($get_query);
                $get_stmt->bindParam(':id', $id);
                $get_stmt->execute();
                $event = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($event && $event['image']) {
                    $file_path = '../' . $event['image'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                $query = "DELETE FROM events WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $success_message = "Event deleted successfully!";
                } else {
                    $error_message = "Error deleting event.";
                }
                break;
        }
    }
}

// Fetch all events
$query = "SELECT * FROM events ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Management - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Events Management</h1>
            <p>Manage your events, registrations, and event status</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Event Form -->
        <div class="form-container">
            <div class="form-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Event</h3>
            </div>
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> Event Name *</label>
                        <input type="text" id="name" name="name" required placeholder="Enter event name">
                    </div>
                    
                    <div class="form-group">
                        <label for="fee"><i class="fas fa-money-bill"></i> Registration Fee (BDT) *</label>
                        <input type="number" id="fee" name="fee" step="0.01" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description *</label>
                    <textarea id="description" name="description" required placeholder="Enter event description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image"><i class="fas fa-image"></i> Event Image</label>
                    <div class="file-upload">
                        <input type="file" id="image" name="image" accept="image/*">
                        <div class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload event image</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date"><i class="fas fa-calendar"></i> Event Date *</label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time"><i class="fas fa-clock"></i> Event Time *</label>
                        <input type="time" id="event_time" name="event_time" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location *</label>
                        <input type="text" id="location" name="location" required placeholder="Enter event location">
                    </div>
                    
                    <div class="form-group">
                        <label for="status"><i class="fas fa-flag"></i> Status *</label>
                        <select id="status" name="status" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="past">Past</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Event
                </button>
            </form>
        </div>

        <!-- Events List -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> All Events</h3>
                <div class="table-stats">
                    <span class="stat-badge">
                        <i class="fas fa-calendar-check"></i>
                        <?php echo count($events); ?> Total Events
                    </span>
                </div>
            </div>
            
            <?php if (!empty($events)): ?>
                <div class="table-responsive">
                    <table class="events-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-tag"></i> Name</th>
                                <th><i class="fas fa-image"></i> Image</th>
                                <th><i class="fas fa-money-bill"></i> Fee</th>
                                <th><i class="fas fa-calendar"></i> Date & Time</th>
                                <th><i class="fas fa-map-marker-alt"></i> Location</th>
                                <th><i class="fas fa-flag"></i> Status</th>
                                <th><i class="fas fa-users"></i> Registrations</th>
                                <th><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <?php
                                // Count registrations for this event
                                $reg_query = "SELECT COUNT(*) as count FROM registrations WHERE event_id = :event_id";
                                $reg_stmt = $db->prepare($reg_query);
                                $reg_stmt->bindParam(':event_id', $event['id']);
                                $reg_stmt->execute();
                                $reg_count = $reg_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?>
                                <tr>
                                    <td><?php echo $event['id']; ?></td>
                                    <td>
                                        <div class="event-name">
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                            <small><?php echo htmlspecialchars(substr($event['description'], 0, 50)) . '...'; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($event['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($event['image']); ?>" alt="Event Image" class="event-thumbnail">
                                        <?php else: ?>
                                            <div class="no-image"><i class="fas fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fee-badge">&#2547;<?php echo number_format($event['fee'], 2); ?></span>
                                    </td>
                                    <td>
                                        <div class="datetime-info">
                                            <strong><?php echo date('M d, Y', strtotime($event['event_date'])); ?></strong>
                                            <small><?php echo date('h:i A', strtotime($event['event_time'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="location-text">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $event['status']; ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="registration-count">
                                            <i class="fas fa-users"></i>
                                            <?php echo $reg_count; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="event_edit.php?id=<?php echo $event['id']; ?>" class="btn btn-warning btn-small" title="Edit Event">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="registrations.php?event_id=<?php echo $event['id']; ?>" class="btn btn-success btn-small" title="View Registrations">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this event?')" title="Delete Event">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Events Found</h3>
                    <p>Start by adding your first event using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
