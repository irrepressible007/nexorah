<?php
session_start();
require_once 'includes/auth_check.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle delete message
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM contact_messages WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        $success_message = "Message deleted successfully.";
    }
}

// Get all messages
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Contact Messages</h1>
            <p>Manage contact form submissions</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?php echo $message['id']; ?></td>
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td><?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="mailto:<?php echo $message['email']; ?>" class="btn btn-primary btn-small">Reply</a>
                                    <a href="?delete=<?php echo $message['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Are you sure?')">Delete</a>
                                </div>
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