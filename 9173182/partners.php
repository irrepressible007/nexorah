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

// Process form submission for adding/editing partner
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Add new partner
    if ($action == 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        
        // Check if file is uploaded
        if (isset($_FILES['partner_image']) && $_FILES['partner_image']['error'] == 0) {
            // Set target directory
            $targetDir = '../assets/images/partners/';
            $imagePath = uploadFile($_FILES['partner_image'], $targetDir);
            
            if ($imagePath) {
                // Convert absolute path to relative path for database storage
                $imagePath = str_replace('../', '', $imagePath);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO partners (name, description, image_path) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description, $imagePath]);
                    $success = true;
                } catch (PDOException $e) {
                    $error = true;
                    $errorMsg = "Database error: " . $e->getMessage();
                }
            } else {
                $error = true;
                $errorMsg = "Failed to upload image. Please check file type and size.";
            }
        } else {
            $error = true;
            $errorMsg = "Please select an image to upload.";
        }
    }
    
    // Delete partner
    elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            // Get the image path before deleting
            $stmt = $pdo->prepare("SELECT image_path FROM partners WHERE id = ?");
            $stmt->execute([$id]);
            $partner = $stmt->fetch();
            
            if ($partner) {
                // Delete the file from server
                $filePath = '../' . $partner['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
                $stmt->execute([$id]);
                $success = true;
            }
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Get all partners
$stmt = $pdo->query("SELECT * FROM partners ORDER BY name ASC");
$partners = $stmt->fetchAll();

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
                <h1 class="h2">Manage Partners</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                    <i class="fas fa-plus"></i> Add New Partner
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
            
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><?php echo $partner['id']; ?></td>
                            <td>
                                <img src="../<?php echo $partner['image_path']; ?>" alt="<?php echo $partner['name']; ?>" width="50" height="50" class="rounded">
                            </td>
                            <td><?php echo $partner['name']; ?></td>
                            <td><?php echo substr($partner['description'], 0, 50) . (strlen($partner['description']) > 50 ? '...' : ''); ?></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this partner?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $partner['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($partners) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">No partners found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Partner Modal -->
<div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPartnerModalLabel">Add New Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Partner Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="partner_image" class="form-label">Partner Image</label>
                        <input class="form-control" type="file" id="partner_image" name="partner_image" accept="image/*" required>
                        <div class="form-text">Recommended size: 300x300 pixels</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
