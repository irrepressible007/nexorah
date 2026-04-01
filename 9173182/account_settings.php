<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once '../includes/db.php';

// Get current admin credentials
$current_username = "admin"; // Default value

// If username is stored in session, use that
if (isset($_SESSION['admin_username'])) {
    $current_username = $_SESSION['admin_username'];
}

$success = false;
$error = false;
$errorMsg = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $new_username = trim($_POST['new_username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $current_password = trim($_POST['current_password']);
    
    // Validate inputs
    if (empty($new_username) || empty($new_password) || empty($confirm_password) || empty($current_password)) {
        $error = true;
        $errorMsg = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = true;
        $errorMsg = "New password and confirm password do not match.";
    } elseif ($current_password !== "admin123") { // Check against hardcoded password
        $error = true;
        $errorMsg = "Current password is incorrect.";
    } else {
        // Update the admin credentials in index.php
        $index_file = file_get_contents('index.php');
        
        // Replace username and password
        $index_file = preg_replace('/\$admin_username = ".*?";/', '\$admin_username = "'.$new_username.'";', $index_file);
        $index_file = preg_replace('/\$admin_password = ".*?";/', '\$admin_password = "'.$new_password.'";', $index_file);
        
        // Write the file back
        if (file_put_contents('index.php', $index_file)) {
            // Update session
            $_SESSION['admin_username'] = $new_username;
            $success = true;
            $current_username = $new_username;
        } else {
            $error = true;
            $errorMsg = "Failed to update credentials. Please check file permissions.";
        }
    }
}

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
                <h1 class="h2">Account Settings</h1>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                Credentials updated successfully!
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $errorMsg; ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Change Username and Password</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="current_username" class="form-label">Current Username</label>
                                    <input type="text" class="form-control" id="current_username" value="<?php echo $current_username; ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="new_username" class="form-label">New Username</label>
                                    <input type="text" class="form-control" id="new_username" name="new_username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Credentials</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
