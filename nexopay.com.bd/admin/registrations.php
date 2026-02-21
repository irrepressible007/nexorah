<?php
// Remove this line: session_start();
require_once 'includes/auth_check.php';
require_once '../config/database.php';

// Quick fix: If admin_id is not set, get it from database
if (!isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT id FROM admin_users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $_SESSION['admin_username']);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
    }
}

$database = new Database();
$db = $database->getConnection();

// Handle approval/rejection actions
if (isset($_POST['action']) && isset($_POST['registration_id'])) {
    $registration_id = $_POST['registration_id'];
    $action = $_POST['action'];
    
    // Check if admin_id exists
    if (!isset($_SESSION['admin_id'])) {
        $error_message = "Admin ID not found in session. Please log out and log in again.";
    } else {
        $admin_id = $_SESSION['admin_id'];
        
        // Verify admin exists in database before proceeding
        $admin_check_query = "SELECT id FROM admin_users WHERE id = :admin_id";
        $admin_check_stmt = $db->prepare($admin_check_query);
        $admin_check_stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
        $admin_check_stmt->execute();
        
        if ($admin_check_stmt->rowCount() == 0) {
            $error_message = "Invalid admin user. Please log out and log in again.";
        } else {
            try {
                if ($action == 'approve') {
                    $query = "UPDATE registrations SET approval_status = 'approved', approved_by = :admin_id, approved_date = NOW() WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
                    if ($stmt->execute()) {
                        $success_message = "Registration approved successfully.";
                    } else {
                        $error_message = "Failed to approve registration.";
                    }
                } elseif ($action == 'reject') {
                    $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided';
                    $query = "UPDATE registrations SET approval_status = 'rejected', approved_by = :admin_id, approved_date = NOW(), rejection_reason = :reason WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
                    $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
                    $stmt->bindParam(':reason', $rejection_reason);
                    if ($stmt->execute()) {
                        $success_message = "Registration rejected successfully.";
                    } else {
                        $error_message = "Failed to reject registration.";
                    }
                }
            } catch (Exception $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle delete registration
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM registrations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        $success_message = "Registration deleted successfully.";
    }
}

// Get all registrations with event details and admin info
$query = "SELECT r.*, e.title as event_title, a.username as approved_by_name 
          FROM registrations r 
          LEFT JOIN events e ON r.event_id = e.id 
          LEFT JOIN admin_users a ON r.approved_by = a.id
          ORDER BY r.registration_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations - Nexopay Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Enhanced Modal Styles */
        .modal {
            position: fixed;
            z-index: 9999 !important;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex !important;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        .alert-success {
            background-color: #c6f6d5;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }
    </style>
    <script defer src="../assets/js/admin.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <div class="header-content">
                <div>
                    <h1>Event Registrations</h1>
                    <p>Manage all event registrations with approval system</p>
                </div>
                <div class="header-actions">
                    <a href="download_registrations_pdf.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Tickets</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><?php echo $registration['id']; ?></td>
                                <td><?php echo htmlspecialchars($registration['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['event_title'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                <td><?php echo htmlspecialchars($registration['phone']); ?></td>
                                <td><?php echo $registration['ticket_quantity'] ?? 'N/A'; ?></td>
                                <td>৳<?php echo number_format($registration['total_amount'] ?? 0, 2); ?></td>
                                <td>
                                    <span class="status-badge payment-<?php echo $registration['payment_status']; ?>">
                                        <?php echo ucfirst($registration['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge approval-<?php echo $registration['approval_status']; ?>">
                                        <?php echo ucfirst($registration['approval_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registration['transaction_id'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($registration['registration_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($registration['approval_status'] == 'pending'): ?>
                                            <button class="btn btn-success btn-small" onclick="approveRegistration(<?php echo $registration['id']; ?>)">Approve</button>
                                            <button class="btn btn-warning btn-small" onclick="rejectRegistration(<?php echo $registration['id']; ?>)">Reject</button>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $registration['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this registration?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <h3>Approve Registration</h3>
            <p>Are you sure you want to approve this registration?</p>
            <form method="POST" id="approvalForm">
                <input type="hidden" name="registration_id" id="approvalRegistrationId">
                <input type="hidden" name="action" value="approve">
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">Approve</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <h3>Reject Registration</h3>
            <form method="POST" id="rejectionForm">
                <input type="hidden" name="registration_id" id="rejectionRegistrationId">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="rejection_reason">Rejection Reason:</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-warning">Reject</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function approveRegistration(id) {
        console.log('Approving registration:', id);
        document.getElementById('approvalRegistrationId').value = id;
        const modal = document.getElementById('approvalModal');
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
    
    function rejectRegistration(id) {
        console.log('Rejecting registration:', id);
        document.getElementById('rejectionRegistrationId').value = id;
        document.getElementById('rejection_reason').value = ''; // Clear previous reason
        const modal = document.getElementById('rejectionModal');
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
    
    function closeModal() {
        const approvalModal = document.getElementById('approvalModal');
        const rejectionModal = document.getElementById('rejectionModal');
        
        approvalModal.classList.remove('show');
        rejectionModal.classList.remove('show');
        
        setTimeout(() => {
            approvalModal.style.display = 'none';
            rejectionModal.style.display = 'none';
        }, 300);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const approvalModal = document.getElementById('approvalModal');
        const rejectionModal = document.getElementById('rejectionModal');
        
        if (event.target === approvalModal) {
            closeModal();
        }
        if (event.target === rejectionModal) {
            closeModal();
        }
    }
    
    // Handle form submissions
    document.getElementById('approvalForm').addEventListener('submit', function(e) {
        console.log('Approval form submitted');
    });
    
    document.getElementById('rejectionForm').addEventListener('submit', function(e) {
        const reason = document.getElementById('rejection_reason').value.trim();
        if (!reason) {
            e.preventDefault();
            alert('Please provide a rejection reason.');
            return false;
        }
        console.log('Rejection form submitted with reason:', reason);
    });
    
    // Auto-hide alerts
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    });
    </script>
</body>
</html>

try {
    if ($action == 'approve') {
        // Update without approved_by field to avoid foreign key constraint
        $query = "UPDATE registrations SET approval_status = 'approved', approved_date = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            $success_message = "Registration approved successfully.";
        } else {
            $error_message = "Failed to approve registration.";
        }
    } elseif ($action == 'reject') {
        $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided';
        // Update without approved_by field to avoid foreign key constraint
        $query = "UPDATE registrations SET approval_status = 'rejected', approved_date = NOW(), rejection_reason = :reason WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $registration_id, PDO::PARAM_INT);
        $stmt->bindParam(':reason', $rejection_reason);
        if ($stmt->execute()) {
            $success_message = "Registration rejected successfully.";
        } else {
            $error_message = "Failed to reject registration.";
        }
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Quick Debug Steps:
// Add this after the session/auth check
echo "<div style='background: yellow; padding: 10px; margin: 10px;'>";
echo "Admin ID in session: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "<br>";
echo "Admin username: " . ($_SESSION['admin_username'] ?? 'NOT SET');
echo "</div>";