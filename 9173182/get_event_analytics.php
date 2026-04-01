<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
        throw new Exception('Invalid event ID');
    }
    
    $event_id = (int)$_GET['event_id'];
    
    // Get basic analytics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_views,
            COUNT(DISTINCT visitor_ip) as unique_viewers,
            COALESCE(AVG(view_duration), 0) as avg_duration
        FROM event_views 
        WHERE event_id = ?
    ");
    $stmt->execute([$event_id]);
    $basic_stats = $stmt->fetch();
    
    // Get interactions count
    $stmt = $pdo->prepare("SELECT COUNT(*) as interactions FROM user_interactions WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $interactions = $stmt->fetch()['interactions'];
    
    // Get daily views for the last 30 days
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as views
        FROM event_views 
        WHERE event_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$event_id]);
    $daily_views = $stmt->fetchAll();
    
    // Get device breakdown
    $stmt = $pdo->prepare("
        SELECT 
            device_type,
            COUNT(*) as count
        FROM event_views 
        WHERE event_id = ?
        GROUP BY device_type
    ");
    $stmt->execute([$event_id]);
    $device_breakdown = $stmt->fetchAll();
    
    $analytics = [
        'total_views' => (int)$basic_stats['total_views'],
        'unique_viewers' => (int)$basic_stats['unique_viewers'],
        'interactions' => (int)$interactions,
        'avg_duration' => round($basic_stats['avg_duration'], 1),
        'daily_views' => $daily_views,
        'device_breakdown' => $device_breakdown
    ];
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
