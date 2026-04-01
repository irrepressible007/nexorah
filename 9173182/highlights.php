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

// Show success alert after redirect (PRG)
if (isset($_GET['success'])) {
    $success = true;
}

// Process form submission for adding/editing highlight
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Add new highlight
    if ($action == 'add') {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $mediaType = sanitize($_POST['media_type']);
        $isHero = isset($_POST['is_hero']) ? 1 : 0;
        
        // Only allow videos to be hero backgrounds
        if ($mediaType != 'video') {
            $isHero = 0;
        }
        
        // Check if file is uploaded
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
            // Set target directory based on media type
            $targetDir = $mediaType == 'image' ? '../assets/images/highlights/' : '../assets/videos/highlights/';
            $mediaPath = uploadFile($_FILES['media_file'], $targetDir);
            
            if ($mediaPath) {
                // Convert absolute path to relative path for database storage
                $mediaPath = str_replace('../', '', $mediaPath);
                
                try {
                    // If this is set as hero, unset any other hero videos
                    if ($isHero) {
                        $stmt = $pdo->prepare("UPDATE highlights SET is_hero = 0 WHERE media_type = 'video'");
                        $stmt->execute();
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO highlights (title, description, media_type, media_path, is_hero) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $mediaType, $mediaPath, $isHero]);
                    // Redirect to avoid duplicate insert on refresh (PRG)
                    header("Location: highlights.php?success=1");
                    exit;
                } catch (PDOException $e) {
                    $error = true;
                    $errorMsg = "Database error: " . $e->getMessage();
                }
            } else {
                $error = true;
                $errorMsg = "Failed to upload file. Please check file type and size.";
            }
        } else {
            $error = true;
            $errorMsg = "Please select a file to upload.";
        }
    }
    
    // Delete highlight
    elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            // Get the media path before deleting
            $stmt = $pdo->prepare("SELECT media_path FROM highlights WHERE id = ?");
            $stmt->execute([$id]);
            $highlight = $stmt->fetch();
            
            if ($highlight) {
                // Delete the file from server
                $filePath = '../' . $highlight['media_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM highlights WHERE id = ?");
                $stmt->execute([$id]);
                // Redirect to avoid duplicate submit on refresh
                header("Location: highlights.php?success=1");
                exit;
            }
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Get all highlights
$stmt = $pdo->query("SELECT * FROM highlights ORDER BY created_at DESC");
$highlights = $stmt->fetchAll();

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
                <h1 class="h2">Manage Highlights</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHighlightModal">
                    <i class="fas fa-plus"></i> Add New Highlight
                </button>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Operation completed successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errorMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Highlights</h6>
                </div>
                <div class="card-body">
                    <?php if (count($highlights) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Media Type</th>
                                    <th>Preview</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($highlights as $highlight): 
                                    $createdAt = new DateTime($highlight['created_at']);
                                ?>
                                <tr>
                                    <td><?php echo $highlight['title']; ?></td>
                                    <td><?php echo substr($highlight['description'], 0, 100) . (strlen($highlight['description']) > 100 ? '...' : ''); ?></td>
                                    <td><?php echo ucfirst($highlight['media_type']); ?></td>
                                    <td>
                                        <?php if ($highlight['media_type'] == 'image'): ?>
                                        <img src="../<?php echo $highlight['media_path']; ?>" alt="<?php echo $highlight['title']; ?>" width="100">
                                        <?php else: ?>
                                        <video width="100" controls>
                                            <source src="../<?php echo $highlight['media_path']; ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $createdAt->format('M d, Y'); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteHighlightModal<?php echo $highlight['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteHighlightModal<?php echo $highlight['id']; ?>" tabindex="-1" aria-labelledby="deleteHighlightModalLabel<?php echo $highlight['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteHighlightModalLabel<?php echo $highlight['id']; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete this highlight: <strong><?php echo $highlight['title']; ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?php echo $highlight['id']; ?>">
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p>No highlights found. Add your first highlight using the button above.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Highlight Modal -->
<div class="modal fade" id="addHighlightModal" tabindex="-1" aria-labelledby="addHighlightModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addHighlightModalLabel">Add New Highlight</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="" enctype="multipart/form-data" id="addHighlightForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Media Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="media_type" id="mediaTypeImage" value="image" checked>
                            <label class="form-check-label" for="mediaTypeImage">
                                Image
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="media_type" id="mediaTypeVideo" value="video">
                            <label class="form-check-label" for="mediaTypeVideo">
                                Video
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="media_file" class="form-label">Upload File</label>
                        <input type="file" class="form-control" id="media_file" name="media_file" required>
                        <div class="form-text">For images: JPG, PNG, or GIF. For videos: MP4 format. No size limit.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_hero" id="isHero" value="1">
                            <label class="form-check-label" for="isHero">
                                Use as Hero Background (for videos only)
                            </label>
                            <div class="form-text">If checked, this video will be used as the background in the homepage hero section.</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Highlight</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
