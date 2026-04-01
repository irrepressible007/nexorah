<?php
// Analytics tracking functions

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function getDeviceType($userAgent) {
    if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
        if (preg_match('/ipad|tablet/i', $userAgent)) {
            return 'tablet';
        }
        return 'mobile';
    }
    return 'desktop';
}

function getBrowser($userAgent) {
    $browsers = [
        'Chrome' => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari' => '/Safari/i',
        'Edge' => '/Edge/i',
        'Opera' => '/Opera/i',
        'Internet Explorer' => '/MSIE/i'
    ];
    
    foreach ($browsers as $browser => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $browser;
        }
    }
    return 'Unknown';
}

function getSessionId() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return session_id();
}

function trackPageView($pdo, $pageUrl, $pageTitle = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO website_analytics 
            (page_url, page_title, visitor_ip, user_agent, referrer, session_id, device_type, browser) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        $stmt->execute([
            $pageUrl,
            $pageTitle,
            getUserIP(),
            $userAgent,
            $referrer,
            getSessionId(),
            getDeviceType($userAgent),
            getBrowser($userAgent)
        ]);
        
        // Update audience data
        updateAudienceData($pdo);
        
    } catch (PDOException $e) {
        error_log("Analytics tracking error: " . $e->getMessage());
    }
}

function trackEventView($pdo, $eventId) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO event_views 
            (event_id, visitor_ip, user_agent, referrer, session_id, device_type) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        $stmt->execute([
            $eventId,
            getUserIP(),
            $userAgent,
            $referrer,
            getSessionId(),
            getDeviceType($userAgent)
        ]);
        
    } catch (PDOException $e) {
        error_log("Event tracking error: " . $e->getMessage());
    }
}

function trackInteraction($pdo, $interactionType, $pageUrl, $elementId = null, $elementClass = null, $elementText = null, $eventId = null, $additionalData = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_interactions 
            (interaction_type, page_url, element_id, element_class, element_text, event_id, visitor_ip, session_id, additional_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $interactionType,
            $pageUrl,
            $elementId,
            $elementClass,
            $elementText,
            $eventId,
            getUserIP(),
            getSessionId(),
            $additionalData ? json_encode($additionalData) : null
        ]);
        
    } catch (PDOException $e) {
        error_log("Interaction tracking error: " . $e->getMessage());
    }
}

function updateAudienceData($pdo) {
    try {
        $sessionId = getSessionId();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check if session exists
        $stmt = $pdo->prepare("SELECT id FROM audience_data WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing session
            $updateStmt = $pdo->prepare("
                UPDATE audience_data 
                SET last_visit = CURRENT_TIMESTAMP, 
                    total_visits = total_visits + 1,
                    total_page_views = total_page_views + 1
                WHERE session_id = ?
            ");
            $updateStmt->execute([$sessionId]);
        } else {
            // Create new session
            $insertStmt = $pdo->prepare("
                INSERT INTO audience_data 
                (session_id, visitor_ip, device_type, browser, operating_system) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                $sessionId,
                getUserIP(),
                getDeviceType($userAgent),
                getBrowser($userAgent),
                getOperatingSystem($userAgent)
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Audience data error: " . $e->getMessage());
    }
}

function getOperatingSystem($userAgent) {
    $os = [
        'Windows' => '/Windows/i',
        'Mac' => '/Mac/i',
        'Linux' => '/Linux/i',
        'Android' => '/Android/i',
        'iOS' => '/iPhone|iPad/i'
    ];
    
    foreach ($os as $system => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return $system;
        }
    }
    return 'Unknown';
}

// Function to get event analytics
function getEventAnalytics($pdo, $eventId) {
    $analytics = [];
    
    // Total views
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_views WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $analytics['total_views'] = $stmt->fetchColumn();
    
    // Unique viewers
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT visitor_ip) FROM event_views WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $analytics['unique_viewers'] = $stmt->fetchColumn();
    
    // Interactions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_interactions WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $analytics['interactions'] = $stmt->fetchColumn();
    
    // Views by device
    $stmt = $pdo->prepare("
        SELECT device_type, COUNT(*) as count 
        FROM event_views 
        WHERE event_id = ? 
        GROUP BY device_type
    ");
    $stmt->execute([$eventId]);
    $analytics['device_breakdown'] = $stmt->fetchAll();
    
    // Daily views for last 7 days
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as views 
        FROM event_views 
        WHERE event_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$eventId]);
    $analytics['daily_views'] = $stmt->fetchAll();
    
    return $analytics;
}
?>
