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
                $title = $_POST['title'];
                $media_type = $_POST['media_type'];
                
                // Handle file upload
                $media_path = '';
                if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
                    $upload_dir = '../uploads/highlights/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $media_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['media_file']['tmp_name'], $media_path)) {
                        $media_path = 'uploads/highlights/' . $new_filename;
                    }
                }
                
                if ($media_path) {
                    $insert_query = "INSERT INTO highlights (title, type, file_path) VALUES (:title, :type, :file_path)";
                    $insert_stmt = $db->prepare($insert_query);
                    $insert_stmt->bindParam(':title', $title);
                    $insert_stmt->bindParam(':type', $media_type);
                    $insert_stmt->bindParam(':file_path', $media_path);
                    
                    if ($insert_stmt->execute()) {
                        $success_message = "Highlight added successfully!";
                    } else {
                        $error_message = "Error adding highlight.";
                    }
                } else {
                    $error_message = "Please upload a valid file.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get file path to delete from server
                $get_query = "SELECT file_path FROM highlights WHERE id = :id";
                $get_stmt = $db->prepare($get_query);
                $get_stmt->bindParam(':id', $id);
                $get_stmt->execute();
                $highlight = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($highlight) {
                    // Delete file from server
                    $file_path = '../' . $highlight['file_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    
                    // Delete from database
                    $query = "DELETE FROM highlights WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Highlight deleted successfully!";
                    } else {
                        $error_message = "Error deleting highlight.";
                    }
                }
                break;
        }
    }
}

// Fetch all highlights
$query = "SELECT * FROM highlights ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$highlights = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-star"></i> Highlights Management</h1>
        <p>Manage highlighted photos and videos for the About Us page</p>
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

    <!-- Add Highlight Form -->
    <div class="form-container">
        <div class="form-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Highlight</h3>
        </div>
        <div class="admin-form">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-heading"></i>
                        Title (Optional)
                    </label>
                    <input type="text" id="title" name="title" placeholder="Enter a title for this highlight">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="media_type">
                            <i class="fas fa-file-alt"></i>
                            Media Type *
                        </label>
                        <select id="media_type" name="media_type" required>
                            <option value="">Select media type</option>
                            <option value="photo">ðŸ“· Photo</option>
                            <option value="video">ðŸŽ¥ Video</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="media_file">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Upload File *
                        </label>
                        <div class="file-upload-wrapper">
                            <input type="file" id="media_file" name="media_file" accept="image/*,video/*" required>
                            <div class="file-upload-display">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload photo or video</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Highlight
                </button>
            </form>
        </div>
    </div>

    <!-- Highlights List -->
    <div class="table-container">
        <div class="section-header">
            <h3><i class="fas fa-images"></i> All Highlights</h3>
            <div class="stats-badge">
                <i class="fas fa-count"></i>
                <?php echo count($highlights); ?> Total
            </div>
        </div>
        
        <?php if (!empty($highlights)): ?>
            <div class="highlights-admin-grid">
                <?php foreach ($highlights as $highlight): ?>
                    <div class="highlight-admin-item">
                        <div class="highlight-preview">
                            <?php if ($highlight['type'] == 'photo'): ?>
                                <img src="../<?php echo htmlspecialchars($highlight['file_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($highlight['title']); ?>"
                                     onerror="this.src='../assets/images/no-image.png'; this.onerror=null;">
                                <div class="media-type-badge photo">
                                    <i class="fas fa-camera"></i>
                                </div>
                            <?php else: ?>
                                <video controls preload="metadata">
                                    <source src="../<?php echo htmlspecialchars($highlight['file_path']); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                <div class="media-type-badge video">
                                    <i class="fas fa-video"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="highlight-info">
                            <h4><?php echo $highlight['title'] ? htmlspecialchars($highlight['title']) : 'Untitled'; ?></h4>
                            <div class="highlight-meta">
                                <p>
                                    <i class="fas fa-tag"></i>
                                    <strong>Type:</strong> <?php echo ucfirst($highlight['type']); ?>
                                </p>
                                <p>
                                    <i class="fas fa-calendar"></i>
                                    <strong>Added:</strong> <?php echo date('M d, Y', strtotime($highlight['created_at'])); ?>
                                </p>
                                <p>
                                    <i class="fas fa-file"></i>
                                    <strong>File:</strong> <?php echo basename($highlight['file_path']); ?>
                                </p>
                            </div>
                            <div class="highlight-actions">
                                <form method="POST" class="delete-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $highlight['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small" 
                                            onclick="return confirm('Are you sure you want to delete this highlight?')">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h4>No highlights added yet</h4>
                <p>Start by adding your first highlight using the form above.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<script>
// Update file upload label based on media type
document.getElementById('media_type').addEventListener('change', function() {
    const fileInput = document.getElementById('media_file');
    const label = document.querySelector('.file-upload-display span');
    
    if (this.value === 'photo') {
        fileInput.accept = 'image/*';
        label.innerHTML = '<i class="fas fa-camera"></i> Click to upload photo';
    } else if (this.value === 'video') {
        fileInput.accept = 'video/*';
        label.innerHTML = '<i class="fas fa-video"></i> Click to upload video';
    } else {
        fileInput.accept = 'image/*,video/*';
        label.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Click to upload photo or video';
    }
});

// File upload preview
document.getElementById('media_file').addEventListener('change', function() {
    const file = this.files[0];
    const display = document.querySelector('.file-upload-display span');
    
    if (file) {
        display.innerHTML = `<i class="fas fa-check"></i> ${file.name}`;
        display.style.color = '#28a745';
    }
});
</script>
</body>
</html>
