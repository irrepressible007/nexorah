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

// Process form submission for message actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Mark message as read
    if ($action == 'mark_read' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = TRUE WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
    
    // Delete message
    elseif ($action == 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $success = true;
        } catch (PDOException $e) {
            $error = true;
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Get all messages
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

// Count unread messages
$unreadMessagesCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = FALSE")->fetchColumn();

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
                <h1 class="h2">Messages</h1>
                <?php if ($unreadMessagesCount > 0): ?>
                <span class="badge bg-danger"><?php echo $unreadMessagesCount; ?> Unread</span>
                <?php endif; ?>
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
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): 
                            $messageDate = new DateTime($message['created_at']);
                        ?>
                        <tr class="<?php echo $message['is_read'] ? '' : 'table-primary'; ?>">
                            <td><?php echo $message['id']; ?></td>
                            <td><?php echo $message['name']; ?></td>
                            <td><a href="mailto:<?php echo $message['email']; ?>"><?php echo $message['email']; ?></a></td>
                            <td><?php echo $message['subject']; ?></td>
                            <td><?php echo $messageDate->format('M d, Y H:i'); ?></td>
                            <td>
                                <?php if ($message['is_read']): ?>
                                <span class="badge bg-secondary">Read</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewMessageModal<?php echo $message['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (!$message['is_read']): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($messages) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center">No messages found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Message View Modals -->
<?php foreach ($messages as $message): ?>
<div class="modal fade" id="viewMessageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="viewMessageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMessageModalLabel<?php echo $message['id']; ?>">
                    Message from <?php echo $message['name']; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>From:</strong> <?php echo $message['name']; ?> (<?php echo $message['email']; ?>)
                </div>
                <div class="mb-3">
                    <strong>Subject:</strong> <?php echo $message['subject']; ?>
                </div>
                <div class="mb-3">
                    <strong>Date:</strong> <?php echo (new DateTime($message['created_at']))->format('F d, Y H:i'); ?>
                </div>
                <div class="mb-3">
                    <strong>Message:</strong>
                    <p class="mt-2"><?php echo nl2br($message['message']); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="mailto:<?php echo $message['email']; ?>" class="btn btn-primary">Reply</a>
                <?php if (!$message['is_read']): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                    <button type="submit" class="btn btn-success">Mark as Read</button>
                </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/admin_footer.php'; ?>