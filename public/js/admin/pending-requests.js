/**
 * Pending Requests Management Module
 * Handles loading, displaying, and refreshing pending reservation requests
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        api: {
            pendingRequests: '/api/admin/pending-requests',
            pendingCount: '/api/admin/pending-requests-count'
        },
        pagination: {
            defaultPerPage: 10,
            pageRange: 2 // Number of pages to show on each side of current
        },
        selectors: {
            app: '#pendingRequestsApp',
            countBadge: '#pendingRequestsCount'
        },
        messages: {
            loadError: 'Failed to load pending requests',
            refreshSuccess: 'Pending requests refreshed',
            noRequests: 'No pending requests found'
        }
    };

    // State management
    let adminToken = null;

    /**
     * Initialize the pending requests module
     */
    function init() {
        // Get admin token
        adminToken = localStorage.getItem('adminToken');
        if (!adminToken) {
            console.error('No admin token found');
            window.location.href = '/admin/login';
            return;
        }

        // Initialize Vue app
        initVueApp();
        
        // Load initial pending count
        fetchPendingCount();
    }

    /**
     * Initialize Vue application
     */
    function initVueApp() {
        // Check if Vue is available
        if (typeof Vue === 'undefined') {
            console.error('Vue is not loaded');
            return;
        }

        window.pendingRequestsApp = new Vue({
            el: CONFIG.selectors.app,
            data: {
                pendingRequests: [],
                loading: false,
                currentPage: 1,
                totalPages: 1,
                totalItems: 0,
                perPage: CONFIG.pagination.defaultPerPage
            },
            computed: {
                /**
                 * Generate pagination display with ellipsis
                 */
                displayedPages() {
                    const delta = CONFIG.pagination.pageRange;
                    const range = [];
                    const rangeWithDots = [];
                    let l;

                    for (let i = 1; i <= this.totalPages; i++) {
                        if (i === 1 || i === this.totalPages || 
                            (i >= this.currentPage - delta && i <= this.currentPage + delta)) {
                            range.push(i);
                        }
                    }

                    range.forEach((i) => {
                        if (l) {
                            if (i - l === 2) {
                                rangeWithDots.push(l + 1);
                            } else if (i - l !== 1) {
                                rangeWithDots.push('...');
                            }
                        }
                        rangeWithDots.push(i);
                        l = i;
                    });

                    return rangeWithDots;
                }
            },
            methods: {
                /**
                 * Load pending requests from API
                 */
                async loadPendingRequests(page = 1) {
                    this.loading = true;
                    this.currentPage = page;

                    try {
                        const params = new URLSearchParams({
                            page: page,
                            per_page: this.perPage
                        });

                        const response = await fetch(`${CONFIG.api.pendingRequests}?${params}`, {
                            headers: {
                                'Authorization': `Bearer ${adminToken}`,
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }

                        const result = await response.json();
                        
                        this.pendingRequests = result.data || [];
                        this.totalPages = result.meta?.last_page || 1;
                        this.totalItems = result.meta?.total || 0;

                        // Update pending count badge
                        updatePendingCountBadge(this.totalItems);

                    } catch (error) {
                        console.error('Error loading pending requests:', error);
                        this.pendingRequests = [];
                        showToast(CONFIG.messages.loadError, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * Refresh current page
                 */
                async refreshPendingRequests() {
                    await this.loadPendingRequests(this.currentPage);
                    showToast(CONFIG.messages.refreshSuccess, 'success');
                },

                /**
                 * Format duration from schedule data
                 */
                formatDuration(request) {
                    if (!request.schedule?.duration) return 'N/A';
                    return request.schedule.duration;
                },

                /**
                 * Display first few items (optimized for display)
                 */
                displayItems(items) {
                    if (!items || items.length === 0) return [];
                    return items.slice(0, 2);
                },

                /**
                 * Change page
                 */
                changePage(page) {
                    if (page >= 1 && page <= this.totalPages) {
                        this.loadPendingRequests(page);
                    }
                },

                /**
                 * Navigate to event details
                 */
                goToEvent(requestId) {
                    window.location.href = `/admin/requisition/${requestId}`;
                }
            },
            mounted() {
                // Load data when component is mounted
                this.loadPendingRequests(1);

                // Optional: Auto-refresh every 60 seconds
                // setInterval(() => {
                //     if (!this.loading) {
                //         this.refreshPendingRequests();
                //     }
                // }, 60000);
            }
        });
    }

    /**
     * Fetch only the pending count
     */
    async function fetchPendingCount() {
        try {
            const response = await fetch(CONFIG.api.pendingCount, {
                headers: {
                    'Authorization': `Bearer ${adminToken}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    updatePendingCountBadge(result.count);
                }
            }
        } catch (error) {
            console.error('Error fetching pending count:', error);
        }
    }

    /**
     * Update pending count badge
     */
    function updatePendingCountBadge(count) {
        const badge = document.querySelector(CONFIG.selectors.countBadge);
        if (badge) {
            badge.textContent = count || 0;
            
            // Optional: Add visual effect for new requests
            if (count > 0) {
                badge.classList.add('pulse-animation');
                setTimeout(() => badge.classList.remove('pulse-animation'), 1000);
            }
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success', duration = 3000) {
        // Use existing toast function or create simple one
        if (typeof window.showToast === 'function') {
            window.showToast(message, type, duration);
            return;
        }

        // Fallback toast implementation
        const toast = document.createElement('div');
        toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
        toast.style.zIndex = '1100';
        toast.style.bottom = '0';
        toast.style.left = '0';
        toast.style.margin = '1rem';
        toast.style.backgroundColor = type === 'success' ? '#004183ff' : '#dc3545';
        toast.style.color = '#fff';
        toast.style.minWidth = '250px';
        toast.style.borderRadius = '0.3rem';

        toast.innerHTML = `
            <div class="d-flex align-items-center px-3 py-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: duration });
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.pendingRequestsModule = {
        refresh: () => {
            if (window.pendingRequestsApp) {
                window.pendingRequestsApp.refreshPendingRequests();
            }
        },
        fetchCount: fetchPendingCount,
        reload: (page = 1) => {
            if (window.pendingRequestsApp) {
                window.pendingRequestsApp.loadPendingRequests(page);
            }
        }
    };

})();