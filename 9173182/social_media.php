<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Initialize variables
$message = '';
$messageType = '';
$editId = null;
$platform = '';
$url = '';
$icon = '';
$isActive = 1;

// Handle form submission for adding/updating social media
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add or update social media
        if ($_POST['action'] === 'save') {
            $platform = sanitize($_POST['platform']);
            $url = sanitize($_POST['url']);
            $icon = sanitize($_POST['icon']);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($platform) || empty($url) || empty($icon)) {
                $message = 'All fields are required';
                $messageType = 'danger';
            } else {
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // Update existing record
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("UPDATE social_media SET platform = ?, url = ?, icon = ?, is_active = ? WHERE id = ?");
                    $result = $stmt->execute([$platform, $url, $icon, $isActive, $id]);
                    
                    if ($result) {
                        $message = 'Social media updated successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Error updating social media';
                        $messageType = 'danger';
                    }
                } else {
                    // Add new record
                    $stmt = $pdo->prepare("INSERT INTO social_media (platform, url, icon, is_active) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$platform, $url, $icon, $isActive]);
                    
                    if ($result) {
                        $message = 'Social media added successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Error adding social media';
                        $messageType = 'danger';
                    }
                }
                
                // Reset form fields
                $editId = null;
                $platform = '';
                $url = '';
                $icon = '';
                $isActive = 1;
            }
        }
        
        // Delete social media
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM social_media WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $message = 'Social media deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Error deleting social media';
                $messageType = 'danger';
            }
        }
        
        // Edit social media (load data into form)
        if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT * FROM social_media WHERE id = ?");
            $stmt->execute([$id]);
            $socialMedia = $stmt->fetch();
            
            if ($socialMedia) {
                $editId = $socialMedia['id'];
                $platform = $socialMedia['platform'];
                $url = $socialMedia['url'];
                $icon = $socialMedia['icon'];
                $isActive = $socialMedia['is_active'];
            }
        }
    }
}

// Get all social media links
$stmt = $pdo->query("SELECT * FROM social_media ORDER BY platform ASC");
$socialMediaLinks = $stmt->fetchAll();

// Include header
include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Social Media Management</h1>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <?php echo $editId ? 'Edit Social Media' : 'Add New Social Media'; ?>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="save">
                                <?php if ($editId): ?>
                                <input type="hidden" name="id" value="<?php echo $editId; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="platform" class="form-label">Platform Name</label>
                                    <input type="text" class="form-control" id="platform" name="platform" value="<?php echo $platform; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="url" class="form-label">URL</label>
                                    <input type="url" class="form-control" id="url" name="url" value="<?php echo $url; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon Class</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="<?php echo !empty($icon) ? $icon : 'fab fa-facebook-f'; ?>"></i></span>
                                        <input type="text" class="form-control" id="icon" name="icon" value="<?php echo $icon; ?>" required>
                                    </div>
                                    <div class="form-text">Use Font Awesome classes (e.g., fab fa-facebook-f, fab fa-twitter)</div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $isActive ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary"><?php echo $editId ? 'Update' : 'Add'; ?> Social Media</button>
                                <?php if ($editId): ?>
                                <a href="social_media.php" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Social Media Links
                        </div>
                        <div class="card-body">
                            <?php if (count($socialMediaLinks) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Platform</th>
                                            <th>Icon</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($socialMediaLinks as $link): ?>
                                        <tr>
                                            <td><?php echo $link['platform']; ?></td>
                                            <td><i class="<?php echo $link['icon']; ?>"></i> <?php echo $link['icon']; ?></td>
                                            <td>
                                                <?php if ($link['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                                </form>
                                                
                                                <form method="POST" action="" class="d-inline delete-form">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-center">No social media links found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Confirm delete
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete this social media link?')) {
            e.preventDefault();
        }
    });
});

// Preview icon
document.getElementById('icon').addEventListener('input', function() {
    const iconPreview = this.previousElementSibling.querySelector('i');
    iconPreview.className = this.value || 'fab fa-facebook-f';
});
</script>

<?php include 'includes/admin_footer.php'; ?>