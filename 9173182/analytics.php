<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Include database connection
require_once '../includes/db.php';

// Handle reset analytics request
if (isset($_POST['reset_analytics'])) {
    try {
        // Start transaction for data integrity
        $pdo->beginTransaction();
        
        // Clear all analytics data in correct order
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE user_interactions");
        $pdo->exec("TRUNCATE TABLE event_views");
        $pdo->exec("TRUNCATE TABLE website_analytics");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Commit the transaction
        $pdo->commit();
        
        // Redirect to prevent form resubmission
        header("Location: analytics.php?reset=1&t=" . time());
        exit;
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollback();
        $error_message = "Error resetting analytics: " . $e->getMessage();
    }
}

// Handle clear activities request
if (isset($_POST['clear_activities'])) {
    // Simply redirect with cleared flag - don't delete any data
    header("Location: analytics.php?cleared=1");
    exit();
}

// Show success message if redirected after reset
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $success_message = "All analytics data has been reset successfully!";
}

// Show success message if redirected after clearing
if (isset($_GET['cleared']) && $_GET['cleared'] == '1') {
    $success_message = "Recent activity view has been cleared!";
}

// Get analytics data
// Website Overview
$totalPageViews = $pdo->query("SELECT COUNT(*) FROM website_analytics")->fetchColumn();
$uniqueVisitors = $pdo->query("SELECT COUNT(DISTINCT visitor_ip) FROM website_analytics")->fetchColumn();
$totalEventViews = $pdo->query("SELECT COUNT(*) FROM event_views")->fetchColumn();
$totalInteractions = $pdo->query("SELECT COUNT(*) FROM user_interactions")->fetchColumn();

// Today's stats
$todayPageViews = $pdo->query("SELECT COUNT(*) FROM website_analytics WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayVisitors = $pdo->query("SELECT COUNT(DISTINCT visitor_ip) FROM website_analytics WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayEventViews = $pdo->query("SELECT COUNT(*) FROM event_views WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayInteractions = $pdo->query("SELECT COUNT(*) FROM user_interactions WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Top events by views
$topEventsStmt = $pdo->query("
    SELECT e.id, e.title, e.image_path, COUNT(ev.id) as view_count,
           COUNT(DISTINCT ev.visitor_ip) as unique_viewers
    FROM events e 
    LEFT JOIN event_views ev ON e.id = ev.event_id 
    GROUP BY e.id, e.title, e.image_path
    ORDER BY view_count DESC 
    LIMIT 5
");
$topEvents = $topEventsStmt->fetchAll();

// Recent activity (only if not just reset or cleared)
if (!isset($_GET['reset']) && !isset($_GET['cleared'])) {
    $recentActivityStmt = $pdo->query("
        SELECT 'page_view' as type, page_url as details, created_at 
        FROM website_analytics 
        UNION ALL
        SELECT 'event_view' as type, CONCAT('Event ID: ', event_id) as details, created_at 
        FROM event_views
        UNION ALL
        SELECT interaction_type as type, page_url as details, created_at 
        FROM user_interactions
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recentActivity = $recentActivityStmt->fetchAll();
} else {
    // If just reset or cleared, show empty array
    $recentActivity = [];
}

// Device breakdown
$deviceStatsStmt = $pdo->query("
    SELECT device_type, COUNT(*) as count 
    FROM (
        SELECT device_type FROM website_analytics
        UNION ALL
        SELECT device_type FROM event_views
    ) as combined_devices
    GROUP BY device_type
");
$deviceStats = $deviceStatsStmt->fetchAll();

// Weekly views chart data
$weeklyStatsStmt = $pdo->query("
    SELECT DATE(created_at) as date, 
           COUNT(*) as page_views,
           COUNT(DISTINCT visitor_ip) as unique_visitors
    FROM website_analytics 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$weeklyStats = $weeklyStatsStmt->fetchAll();

// Include admin header
include 'includes/admin_header.php';
?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($success_message); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($error_message); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-chart-bar"></i> Analytics Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reset ALL analytics data? This will clear:\n\nâ€¢ Total Page Views: <?php echo number_format($totalPageViews); ?>\nâ€¢ Unique Visitors: <?php echo number_format($uniqueVisitors); ?>\nâ€¢ Event Views: <?php echo number_format($totalEventViews); ?>\nâ€¢ Interactions: <?php echo number_format($totalInteractions); ?>\nâ€¢ All Recent Activity\n\nThis action cannot be undone!');">
                            <button type="submit" name="reset_analytics" class="btn btn-sm btn-danger ms-2">
                                <i class="fas fa-redo-alt"></i> Reset Analytics
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Page Views</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalPageViews); ?></div>
                                    <div class="text-xs text-muted">Today: <?php echo number_format($todayPageViews); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-eye fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Unique Visitors</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($uniqueVisitors); ?></div>
                                    <div class="text-xs text-muted">Today: <?php echo number_format($todayVisitors); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Event Views</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalEventViews); ?></div>
                                    <div class="text-xs text-muted">Today: <?php echo number_format($todayEventViews); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Interactions</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalInteractions); ?></div>
                                    <div class="text-xs text-muted">Today: <?php echo number_format($todayInteractions); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-mouse-pointer fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Tables Row -->
            <div class="row">
                <!-- Weekly Views Chart -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Weekly Views Trend</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="weeklyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Device Breakdown -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Device Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="deviceChart" width="400" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Events and Recent Activity -->
            <div class="row">
                <!-- Top Events by Views -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Events by Views</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($topEvents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Total Views</th>
                                            <th>Unique Viewers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topEvents as $event): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($event['image_path']): ?>
                                                    <img src="../<?php echo $event['image_path']; ?>" alt="Event" class="rounded" width="40" height="40" style="object-fit: cover; margin-right: 10px;">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($event['title']); ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-primary"><?php echo number_format($event['view_count']); ?></span></td>
                                            <td><span class="badge bg-info"><?php echo number_format($event['unique_viewers']); ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No event views recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                            <?php if (count($recentActivity) > 0): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all recent activities? This action cannot be undone.');">
                                <button type="submit" name="clear_activities" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt"></i> Clear All
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentActivity) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentActivity as $activity): 
                                    $activityDate = new DateTime($activity['created_at']);
                                    $icon = '';
                                    $color = '';
                                    switch($activity['type']) {
                                        case 'page_view':
                                            $icon = 'fas fa-eye';
                                            $color = 'text-primary';
                                            break;
                                        case 'event_view':
                                            $icon = 'fas fa-calendar';
                                            $color = 'text-info';
                                            break;
                                        case 'click':
                                            $icon = 'fas fa-mouse-pointer';
                                            $color = 'text-success';
                                            break;
                                        default:
                                            $icon = 'fas fa-activity';
                                            $color = 'text-secondary';
                                    }
                                ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="<?php echo $icon; ?> <?php echo $color; ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $activity['type'])); ?>
                                        </h6>
                                        <small><?php echo $activityDate->format('M d, H:i'); ?></small>
                                    </div>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($activity['details']); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No recent activity recorded.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Weekly Views Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
const weeklyChart = new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: [<?php 
            foreach($weeklyStats as $stat) {
                echo "'" . date('M d', strtotime($stat['date'])) . "',";
            }
        ?>],
        datasets: [{
            label: 'Page Views',
            data: [<?php 
                foreach($weeklyStats as $stat) {
                    echo $stat['page_views'] . ",";
                }
            ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'Unique Visitors',
            data: [<?php 
                foreach($weeklyStats as $stat) {
                    echo $stat['unique_visitors'] . ",";
                }
            ?>],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Device Breakdown Chart
const deviceCtx = document.getElementById('deviceChart').getContext('2d');
const deviceChart = new Chart(deviceCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            foreach($deviceStats as $device) {
                echo "'" . ucfirst($device['device_type']) . "',";
            }
        ?>],
        datasets: [{
            data: [<?php 
                foreach($deviceStats as $device) {
                    echo $device['count'] . ",";
                }
            ?>],
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
