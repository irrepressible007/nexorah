// Analytics tracking JavaScript

class AnalyticsTracker {
    constructor() {
        this.sessionId = this.getSessionId();
        this.startTime = Date.now();
        this.interactions = [];
        this.init();
    }
    
    init() {
        // Track page view
        this.trackPageView();
        
        // Track clicks
        document.addEventListener('click', (e) => {
            this.trackClick(e);
        });
        
        // Track form submissions
        document.addEventListener('submit', (e) => {
            this.trackFormSubmit(e);
        });
        
        // Track page unload (visit duration)
        window.addEventListener('beforeunload', () => {
            this.trackPageDuration();
        });
        
        // Track scroll depth
        this.trackScrollDepth();
    }
    
    getSessionId() {
        let sessionId = sessionStorage.getItem('analytics_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('analytics_session_id', sessionId);
        }
        return sessionId;
    }
    
    trackPageView() {
        const data = {
            action: 'page_view',
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer,
            timestamp: Date.now()
        };
        
        this.sendData(data);
    }
    
    trackClick(event) {
        const element = event.target;
        const data = {
            action: 'click',
            page_url: window.location.href,
            element_id: element.id || null,
            element_class: element.className || null,
            element_text: element.textContent?.substring(0, 100) || null,
            element_tag: element.tagName.toLowerCase(),
            timestamp: Date.now()
        };
        
        // Check if it's an event-related click
        const eventElement = element.closest('[data-event-id]');
        if (eventElement) {
            data.event_id = eventElement.getAttribute('data-event-id');
        }
        
        this.sendData(data);
    }
    
    trackFormSubmit(event) {
        const form = event.target;
        const data = {
            action: 'form_submit',
            page_url: window.location.href,
            form_id: form.id || null,
            form_class: form.className || null,
            timestamp: Date.now()
        };
        
        this.sendData(data);
    }
    
    trackPageDuration() {
        const duration = Math.round((Date.now() - this.startTime) / 1000);
        const data = {
            action: 'page_duration',
            page_url: window.location.href,
            duration: duration,
            timestamp: Date.now()
        };
        
        // Use sendBeacon for reliable sending on page unload
        if (navigator.sendBeacon) {
            navigator.sendBeacon('/site/includes/track.php', JSON.stringify(data));
        }
    }
    
    trackScrollDepth() {
        let maxScroll = 0;
        let scrollTimer;
        
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                const scrollPercent = Math.round(
                    (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
                );
                
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    
                    // Track milestone scrolls (25%, 50%, 75%, 100%)
                    if ([25, 50, 75, 100].includes(scrollPercent)) {
                        const data = {
                            action: 'scroll_depth',
                            page_url: window.location.href,
                            scroll_percent: scrollPercent,
                            timestamp: Date.now()
                        };
                        
                        this.sendData(data);
                    }
                }
            }, 100);
        });
    }
    
    trackEventView(eventId) {
        const data = {
            action: 'event_view',
            event_id: eventId,
            page_url: window.location.href,
            timestamp: Date.now()
        };
        
        this.sendData(data);
    }
    
    sendData(data) {
        fetch('/site/includes/track.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        }).catch(error => {
            console.error('Analytics tracking error:', error);
        });
    }
}

// Initialize analytics when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.analyticsTracker = new AnalyticsTracker();
});

// Helper function for manual event tracking
function trackEvent(eventType, eventData) {
    if (window.analyticsTracker) {
        const data = {
            action: eventType,
            ...eventData,
            page_url: window.location.href,
            timestamp: Date.now()
        };
        
        window.analyticsTracker.sendData(data);
    }
}