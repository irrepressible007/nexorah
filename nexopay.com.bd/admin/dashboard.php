<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total events
$query = "SELECT COUNT(*) as total FROM events";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total registrations
$query = "SELECT COUNT(*) as total FROM registrations";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total contact messages
$query = "SELECT COUNT(*) as total FROM contact_messages";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent registrations
$query = "SELECT r.*, e.title as event_name FROM registrations r 
          JOIN events e ON r.event_id = e.id 
          ORDER BY r.registration_date DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Nexopay</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="../assets/js/admin.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_events']; ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_registrations']; ?></h3>
                    <p>Total Registrations</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_messages']; ?></h3>
                    <p>Contact Messages</p>
                </div>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Registrations</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Event</th>
                            <th>Email</th>
                            <th>Tickets</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_registrations)): ?>
                            <?php foreach ($recent_registrations as $registration): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registration['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['event_name']); ?></td>
                                    <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                    <td><?php echo $registration['ticket_quantity']; ?></td>
                                    <td>৳<?php echo number_format($registration['total_amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($registration['registration_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No registrations yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>