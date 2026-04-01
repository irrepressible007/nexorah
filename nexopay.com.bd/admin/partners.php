<?php
session_start();
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
                $website = $_POST['website'];
                $description = $_POST['description'];
                
                // Handle logo upload
                $logo_path = '';
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $upload_dir = '../uploads/partners/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '.' . $file_extension;
                    $logo_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                        $logo_path = 'uploads/partners/' . $new_filename;
                    }
                }
                
                $query = "INSERT INTO partners (name, logo, website, description) VALUES (:name, :logo, :website, :description)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':logo', $logo_path);
                $stmt->bindParam(':website', $website);
                $stmt->bindParam(':description', $description);
                
                if ($stmt->execute()) {
                    $success_message = "Partner added successfully.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $query = "DELETE FROM partners WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                if ($stmt->execute()) {
                    $success_message = "Partner deleted successfully.";
                }
                break;
        }
    }
}

// Get all partners
$query = "SELECT * FROM partners ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partners - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Partners</h1>
            <p>Manage business partners</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <!-- Add Partner Form -->
        <div class="form-container">
            <h3>Add New Partner</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Partner Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="website">Website URL</label>
                    <input type="url" id="website" name="website">
                </div>
                
                <div class="form-group">
                    <label for="logo">Logo</label>
                    <input type="file" id="logo" name="logo" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Partner</button>
            </form>
        </div>
        
        <!-- Partners List -->
        <div class="table-container">
            <h3>Existing Partners</h3>
            <table>
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>Website</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td>
                                <?php if ($partner['logo']): ?>
                                    <img src="../<?php echo $partner['logo']; ?>" alt="Logo" style="max-width: 50px; max-height: 50px;">
                                <?php else: ?>
                                    No logo
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($partner['name']); ?></td>
                            <td>
                                <?php if ($partner['website']): ?>
                                    <a href="<?php echo htmlspecialchars($partner['website']); ?>" target="_blank">Visit</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($partner['description'], 0, 100)) . (strlen($partner['description']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y', strtotime($partner['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $partner['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
