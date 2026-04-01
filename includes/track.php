<?php
// Server-side tracking endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/analytics.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        throw new Exception('Invalid data');
    }
    
    $action = $data['action'];
    $pageUrl = $data['page_url'] ?? '';
    
    switch ($action) {
        case 'page_view':
            trackPageView($pdo, $pageUrl, $data['page_title'] ?? '');
            break;
            
        case 'event_view':
            if (isset($data['event_id'])) {
                trackEventView($pdo, $data['event_id']);
            }
            break;
            
        case 'click':
            trackInteraction(
                $pdo, 
                'click', 
                $pageUrl, 
                $data['element_id'] ?? null,
                $data['element_class'] ?? null,
                $data['element_text'] ?? null,
                $data['event_id'] ?? null,
                [
                    'element_tag' => $data['element_tag'] ?? null
                ]
            );
            break;
            
        case 'form_submit':
            trackInteraction(
                $pdo, 
                'form_submit', 
                $pageUrl, 
                $data['form_id'] ?? null,
                $data['form_class'] ?? null
            );
            break;
            
        case 'scroll_depth':
            trackInteraction(
                $pdo, 
                'scroll', 
                $pageUrl, 
                null, 
                null, 
                null, 
                null,
                [
                    'scroll_percent' => $data['scroll_percent'] ?? 0
                ]
            );
            break;
            
        case 'page_duration':
            // Update visit duration in website_analytics
            $stmt = $pdo->prepare("
                UPDATE website_analytics 
                SET visit_duration = ? 
                WHERE page_url = ? AND session_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([
                $data['duration'] ?? 0,
                $pageUrl,
                getSessionId()
            ]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    error_log('Analytics tracking error: ' . $e->getMessage());
}
?>
