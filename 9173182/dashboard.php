<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once '../includes/db.php';

// Get counts for dashboard
$highlightsCount = $pdo->query("SELECT COUNT(*) FROM highlights")->fetchColumn();
$partnersCount = $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();
$eventsCount = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$blogPostsCount = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$messagesCount = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
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
                <h1 class="h2">Dashboard</h1>
            </div>
            
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Highlights</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $highlightsCount; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-image fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Partners</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $partnersCount; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-handshake fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Events</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $eventsCount; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Blog Posts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $blogPostsCount; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-blog fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Messages</h6>
                            <a href="messages.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
                            $recentMessages = $stmt->fetchAll();
                            
                            if (count($recentMessages) > 0) :
                            ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentMessages as $message) : 
                                            $messageDate = new DateTime($message['created_at']);
                                        ?>
                                        <tr>
                                            <td><?php echo $message['name']; ?></td>
                                            <td><?php echo $message['email']; ?></td>
                                            <td><?php echo $message['subject'] ? $message['subject'] : 'N/A'; ?></td>
                                            <td><?php echo $messageDate->format('M d, Y'); ?></td>
                                            <td>
                                                <?php if ($message['is_read']) : ?>
                                                <span class="badge bg-success">Read</span>
                                                <?php else : ?>
                                                <span class="badge bg-warning">Unread</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else : ?>
                            <p>No messages yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Upcoming Events</h6>
                            <a href="events.php" class="btn btn-sm btn-primary">Manage Events</a>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM events WHERE is_upcoming = TRUE AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
                            $stmt->execute();
                            $upcomingEvents = $stmt->fetchAll();
                            
                            if (count($upcomingEvents) > 0) :
                            ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingEvents as $event) : 
                                            $eventDate = new DateTime($event['event_date']);
                                        ?>
                                        <tr>
                                            <td><?php echo $event['title']; ?></td>
                                            <td><?php echo $eventDate->format('M d, Y'); ?></td>
                                            <td><?php echo $event['location']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else : ?>
                            <p>No upcoming events.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
