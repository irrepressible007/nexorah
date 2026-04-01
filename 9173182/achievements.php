<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once '../includes/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Process each achievement update
        foreach ($_POST['achievements'] as $id => $achievement) {
            $stmt = $pdo->prepare("UPDATE achievements SET title = ?, value = ? WHERE id = ?");
            $stmt->execute([$achievement['title'], $achievement['value'], $id]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $successMessage = "Achievements updated successfully!";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $errorMessage = "Error updating achievements: " . $e->getMessage();
    }
}

// Get all achievements
$stmt = $pdo->query("SELECT * FROM achievements ORDER BY display_order ASC");
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Manage Achievements</h1>
            </div>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Achievement Numbers</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Value</th>
                                        <th>Icon</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($achievements as $achievement): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control" name="achievements[<?php echo $achievement['id']; ?>][title]" value="<?php echo htmlspecialchars($achievement['title']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="achievements[<?php echo $achievement['id']; ?>][value]" value="<?php echo htmlspecialchars($achievement['value']); ?>" required>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="<?php echo $achievement['icon']; ?> fa-2x me-2"></i>
                                                <span><?php echo $achievement['icon']; ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Achievements</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
