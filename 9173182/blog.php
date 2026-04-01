<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection and functions
require_once '../includes/db.php';
require_once '../includes/functions.php';

$success = false;
$error = false;
$errorMsg = '';

// Process form submission for adding/editing blog post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Add new blog post
    if ($action == 'add') {
        $title = sanitize($_POST['title']);
        $content = $_POST['content']; // Allow HTML content for rich text
        $mediaType = sanitize($_POST['media_type']);
        $mediaPath = null;
        
        // Handle file upload if media type is image or video
        if ($mediaType != 'text') {
            // Check if file is uploaded
            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
                // Set target directory based on media type
                $targetDir = $mediaType == 'image' ? '../assets/images/blog/' : '../assets/videos/blog/';
                $mediaPath = uploadFile($_FILES['media_file'], $targetDir);
                
                if ($mediaPath) {
                    // Convert absolute path to relative path for database storage
                    $mediaPath = str_replace('../', '', $mediaPath);
                } else {
                    $error = true;
                    $errorMsg = "Failed to upload file. Please check file type and size.";
                }
            } else {
                $error = true;
                $errorMsg = "Please select a file to upload.";
            }
        }
        
        if (!$error) {
            try {
                $stmt = $pdo->prepare("INSERT INTO blog_posts (title, content, media_type, media_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $mediaType, $mediaPath]);
                $success = true;
            } catch (PDOException $e) {
                $error = true;
                $errorMsg = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Delete blog post
    elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            // Get the media path before deleting
            $stmt = $pdo->prepare("SELECT media_path, media_type FROM blog_posts WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch();
            
            // Delete the file from server if it exists
            if ($post && $post['media_path'] && $post['media_type'] != 'text') {
                $filePath = '../' . $post['media_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Get all blog posts
$stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

// Include admin header
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Blog Posts</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPostModal">Add New Post</button>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                Operation completed successfully!
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $errorMsg; ?>
            </div>
            <?php endif; ?>
            
            <!-- Blog Posts Table -->
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Media Type</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?php echo $post['id']; ?></td>
                            <td><?php echo $post['title']; ?></td>
                            <td><?php echo isset($post['media_type']) ? ucfirst($post['media_type']) : 'Image'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Post Modal -->
<div class="modal fade" id="addPostModal" tabindex="-1" aria-labelledby="addPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPostModalLabel">Add New Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="6" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="media_type" class="form-label">Media Type</label>
                        <select class="form-select" id="media_type" name="media_type" onchange="toggleMediaUpload()">
                            <option value="text">Text Only</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="media_upload_div" style="display: none;">
                        <label for="media_file" class="form-label">Upload Media</label>
                        <input type="file" class="form-control" id="media_file" name="media_file">
                        <div class="form-text">Supported formats: JPG, PNG, GIF for images; MP4, AVI, MOV for videos.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleMediaUpload() {
        var mediaType = document.getElementById('media_type').value;
        var mediaUploadDiv = document.getElementById('media_upload_div');
        
        if (mediaType === 'text') {
            mediaUploadDiv.style.display = 'none';
        } else {
            mediaUploadDiv.style.display = 'block';
        }
    }
    
    // Initialize rich text editor for content
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor
                .create(document.querySelector('#content'))
                .catch(error => {
                    console.error(error);
                });
        }
    });
</script>

<?php include 'includes/admin_footer.php'; ?>
