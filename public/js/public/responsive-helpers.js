// public/js/public/responsive-helpers.js


/**
 * Responsive design helpers for all user-facing pages
 */

class ResponsiveHelpers {
    constructor() {
        this.isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
        this.currentBreakpoint = this.getCurrentBreakpoint();
        
        this.init();
    }
    
    init() {
        // Add touch device class to body
        if (this.isTouchDevice) {
            document.body.classList.add('touch-device');
        }
        
        // Watch for breakpoint changes
        window.addEventListener('resize', this.debounce(() => {
            this.onResize();
        }, 150));
        
        // Initialize touch optimizations
        if (this.isTouchDevice) {
            this.optimizeForTouch();
        }
        
        // Prevent double-tap zoom on iOS
        this.preventDoubleTapZoom();
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width < 576) return 'xs';
        if (width < 768) return 'sm';
        if (width < 992) return 'md';
        if (width < 1200) return 'lg';
        if (width < 1400) return 'xl';
        return '2xl';
    }
    
    onResize() {
        const newBreakpoint = this.getCurrentBreakpoint();
        if (newBreakpoint !== this.currentBreakpoint) {
            const oldBreakpoint = this.currentBreakpoint;
            this.currentBreakpoint = newBreakpoint;
            
            // Dispatch custom event for breakpoint change
            const event = new CustomEvent('breakpointChange', {
                detail: {
                    old: oldBreakpoint,
                    new: newBreakpoint,
                    width: window.innerWidth
                }
            });
            window.dispatchEvent(event);
        }
    }
    
    optimizeForTouch() {
        // Enhance all buttons for touch
        const touchElements = document.querySelectorAll('.btn-responsive, .dropdown-item, .status-badge-responsive');
        touchElements.forEach(el => {
            el.classList.add('touch-target');
        });
        
        // Add active state feedback
        document.addEventListener('touchstart', (e) => {
            if (e.target.classList.contains('btn-responsive')) {
                e.target.classList.add('active');
            }
        }, { passive: true });
        
        document.addEventListener('touchend', (e) => {
            if (e.target.classList.contains('btn-responsive')) {
                setTimeout(() => {
                    e.target.classList.remove('active');
                }, 150);
            }
        }, { passive: true });
    }
    
    preventDoubleTapZoom() {
        let lastTouchEnd = 0;
        
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Utility methods
    isMobile() {
        return this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
    }
    
    isTablet() {
        return this.currentBreakpoint === 'md';
    }
    
    isDesktop() {
        return this.currentBreakpoint === 'lg' || this.currentBreakpoint === 'xl' || this.currentBreakpoint === '2xl';
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.responsiveHelpers = new ResponsiveHelpers();
});