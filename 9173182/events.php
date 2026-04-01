<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

require_once '../includes/db.php';
require_once '../includes/functions.php';

$success_message = '';
$error_message = '';

// Handle success messages from redirects FIRST
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $success_message = 'Event added successfully!';
            break;
        case 'updated':
            $success_message = 'Event updated successfully!';
            break;
        case 'deleted':
            $success_message = 'Event deleted successfully!';
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_event':
                    // Validate required fields
                    if (empty($_POST['title']) || empty($_POST['description'])) {
                        throw new Exception('Title and description are required.');
                    }
                    
                    $title = sanitize($_POST['title']);
                    $description = sanitize($_POST['description']);
                    $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
                    $event_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
                    $location = sanitize($_POST['location']);
                    $is_upcoming = isset($_POST['is_upcoming']) ? 1 : 0;
                    $is_registration_open = isset($_POST['is_registration_open']) ? 1 : 0;
                    
                    // Handle image upload
                    $image_path = '';
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = uploadFile($_FILES['image'], 'events');
                        if ($upload_result !== false) {
                            $image_path = str_replace('../uploads/', '', $upload_result);
                        } else {
                            throw new Exception('Image upload failed');
                        }
                    }
                    
                    // Insert into database
                    $stmt = $pdo->prepare("INSERT INTO events (title, description, image_path, event_date, event_time, location, is_upcoming, is_registration_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $image_path, $event_date, $event_time, $location, $is_upcoming, $is_registration_open]);
                    
                    // Redirect to prevent form resubmission
                    header('Location: events.php?success=added');
                    exit();
                    break;
                    
                case 'edit_event':
                    if (empty($_POST['event_id']) || empty($_POST['title']) || empty($_POST['description'])) {
                        throw new Exception('Event ID, title and description are required.');
                    }
                    
                    $event_id = (int)$_POST['event_id'];
                    $title = sanitize($_POST['title']);
                    $description = sanitize($_POST['description']);
                    $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
                    $event_time = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
                    $location = sanitize($_POST['location']);
                    $is_upcoming = isset($_POST['is_upcoming']) ? 1 : 0;
                    $is_registration_open = isset($_POST['is_registration_open']) ? 1 : 0;
                    
                    // Handle image upload
                    $image_update = '';
                    $image_params = [];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = uploadFile($_FILES['image'], 'events');
                        if ($upload_result !== false) {
                            $image_update = ', image_path = ?';
                            $image_params[] = str_replace('../uploads/', '', $upload_result);
                        } else {
                            throw new Exception('Image upload failed');
                        }
                    }
                    
                    // Update database
                    $sql = "UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, is_upcoming = ?, is_registration_open = ?" . $image_update . " WHERE id = ?";
                    $params = array_merge([$title, $description, $event_date, $event_time, $location, $is_upcoming, $is_registration_open], $image_params, [$event_id]);
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    // Redirect to prevent form resubmission
                    header('Location: events.php?success=updated');
                    exit();
                    break;
                    
                case 'delete_event':
                    if (empty($_POST['event_id'])) {
                        throw new Exception('Event ID is required.');
                    }
                    
                    $event_id = (int)$_POST['event_id'];
                    
                    // Get image path and analytics data before deletion
                    $stmt = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
                    $stmt->execute([$event_id]);
                    $event = $stmt->fetch();
                    
                    // Get analytics counts for this event before deletion
                    $eventViewsStmt = $pdo->prepare("SELECT COUNT(*) FROM event_views WHERE event_id = ?");
                    $eventViewsStmt->execute([$event_id]);
                    $eventViewsCount = $eventViewsStmt->fetchColumn();
                    
                    // Delete related analytics data first
                    $pdo->prepare("DELETE FROM event_views WHERE event_id = ?")->execute([$event_id]);
                    
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$event_id]);
                    
                    // Delete image file if exists
                    if ($event && !empty($event['image_path']) && file_exists('../uploads/' . $event['image_path'])) {
                        unlink('../uploads/' . $event['image_path']);
                    }
                    
                    // Redirect to prevent form resubmission
                    header('Location: events.php?success=deleted&views_removed=' . $eventViewsCount);
                    exit();
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// NOW include analytics.php after all potential redirects
require_once 'analytics.php';

// Get all events with analytics
try {
    $stmt = $pdo->query("
        SELECT e.*, 
               COALESCE(ev.total_views, 0) as total_views,
               COALESCE(ev.unique_viewers, 0) as unique_viewers,
               COALESCE(ui.interactions, 0) as interactions
        FROM events e
        LEFT JOIN (
            SELECT event_id, 
                   COUNT(*) as total_views, 
                   COUNT(DISTINCT visitor_ip) as unique_viewers
            FROM event_views 
            GROUP BY event_id
        ) ev ON e.id = ev.event_id
        LEFT JOIN (
            SELECT event_id, COUNT(*) as interactions
            FROM user_interactions 
            WHERE event_id IS NOT NULL
            GROUP BY event_id
        ) ui ON e.id = ui.event_id
        ORDER BY e.created_at DESC
    ");
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error fetching events: ' . $e->getMessage();
    $events = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Nexorah Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manage Events</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="fas fa-plus"></i> Add New Event
                    </button>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Unique Viewers</th>
                                <th>Interactions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($event['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event Image" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo $event['event_date'] ? date('M j, Y', strtotime($event['event_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge <?php echo $event['is_upcoming'] ? 'bg-success' : 'bg-secondary'; ?> mb-1">
                                        <?php echo $event['is_upcoming'] ? 'Upcoming' : 'Past'; ?>
                                    </span><br>
                                    <span class="badge <?php echo $event['is_registration_open'] ? 'bg-primary' : 'bg-dark'; ?>">
                                        <?php echo $event['is_registration_open'] ? 'Registration Open' : 'Registration Closed'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($event['total_views']); ?></td>
                                <td><?php echo number_format($event['unique_viewers']); ?></td>
                                <td><?php echo number_format($event['interactions']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewEventAnalytics(<?php echo $event['id']; ?>)">
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_date" class="form-label">Event Date</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_time" class="form-label">Event Time</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Event Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_upcoming" name="is_upcoming" checked>
                            <label class="form-check-label" for="is_upcoming">This is an upcoming event</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_registration_open" name="is_registration_open" checked>
                            <label class="form-check-label" for="is_registration_open">Registration is Open</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_event">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_event_date" class="form-label">Event Date</label>
                                    <input type="date" class="form-control" id="edit_event_date" name="event_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_event_time" class="form-label">Event Time</label>
                                    <input type="time" class="form-control" id="edit_event_time" name="event_time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location">
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Event Image (leave empty to keep current)</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_upcoming" name="is_upcoming">
                            <label class="form-check-label" for="edit_is_upcoming">This is an upcoming event</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_registration_open" name="is_registration_open">
                            <label class="form-check-label" for="edit_is_registration_open">Registration is Open</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Analytics Modal -->
    <div class="modal fade" id="eventAnalyticsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Event Analytics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="analyticsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="event_id" value="${eventId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editEvent(event) {
            document.getElementById('edit_event_id').value = event.id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_event_date').value = event.event_date;
            document.getElementById('edit_event_time').value = event.event_time;
            document.getElementById('edit_location').value = event.location || '';
            document.getElementById('edit_is_upcoming').checked = event.is_upcoming == 1;
            document.getElementById('edit_is_registration_open').checked = event.is_registration_open == 1;
            
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }

        function viewEventAnalytics(eventId) {
            const modal = new bootstrap.Modal(document.getElementById('eventAnalyticsModal'));
            modal.show();
            
            // Show loading spinner
            document.getElementById('analyticsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Fetch analytics data
            fetch(`get_event_analytics.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayEventAnalytics(data.analytics);
                    } else {
                        document.getElementById('analyticsContent').innerHTML = `
                            <div class="alert alert-danger">
                                Error loading analytics: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('analyticsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading analytics: ${error.message}
                        </div>
                    `;
                });
        }

        function displayEventAnalytics(analytics) {
            const content = `
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">${analytics.total_views}</h5>
                                <p class="card-text">Total Views</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">${analytics.unique_viewers}</h5>
                                <p class="card-text">Unique Viewers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">${analytics.interactions}</h5>
                                <p class="card-text">Interactions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">${analytics.avg_duration}s</h5>
                                <p class="card-text">Avg. Duration</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Views Over Time</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="viewsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Device Breakdown</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="deviceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('analyticsContent').innerHTML = content;
            
            // Create charts
            setTimeout(() => {
                // Views chart
                const viewsCtx = document.getElementById('viewsChart').getContext('2d');
                new Chart(viewsCtx, {
                    type: 'line',
                    data: {
                        labels: analytics.daily_views.map(item => item.date),
                        datasets: [{
                            label: 'Views',
                            data: analytics.daily_views.map(item => item.views),
                            borderColor: 'rgb(75, 192, 192)',
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
                
                // Device chart
                const deviceCtx = document.getElementById('deviceChart').getContext('2d');
                new Chart(deviceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: analytics.device_breakdown.map(item => item.device_type),
                        datasets: [{
                            data: analytics.device_breakdown.map(item => item.count),
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }, 100);
        }
    </script>
</body>
</html>

<?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Event deleted successfully!
    <?php if (isset($_GET['views_removed']) && $_GET['views_removed'] > 0): ?>
        <br><small>Also removed <?php echo $_GET['views_removed']; ?> related view(s) from analytics.</small>
    <?php endif; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>
