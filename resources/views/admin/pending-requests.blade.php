{{-- pending-requests-tab.blade.php --}}
@extends('layouts.admin')
@section('title', 'Pending Requests')
@section('content')
<style>
    /* Pending Requests specific styles - adapted from signatory dashboard */
    .requisition-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .requisition-card:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .requester-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }
    
    .organization-badge {
        background-color: #e9ecef;
        color: #495057;
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-block;
    }
    
    .status-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 500;
        white-space: nowrap;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .status-awaiting {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .item-chip {
        background-color: #f1f3f5;
        color: #495057;
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        display: inline-block;
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .item-chip i {
        margin-right: 0.2rem;
        font-size: 0.6rem;
    }
    
    .schedule-info {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    
    .schedule-info i {
        font-size: 0.6rem;
    }
    
    .request-id {
        font-size: 0.65rem;
        color: #adb5bd;
        font-weight: 500;
    }
    
    .refresh-btn {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        color: #6c757d;
        background: transparent;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    
    .refresh-btn:hover {
        color: #135ba3;
        background-color: rgba(0,0,0,0.02);
    }
    
    .refresh-btn:hover i {
        color: #135ba3 !important;
    }
    
    .refresh-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Pagination styles - from signatory dashboard */
    .pagination-container {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
    
    .pagination-info {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .pagination-controls {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .btn-pagination {
        background: #fff;
        border: 1px solid #dee2e6;
        color: #495057;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.85rem;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .btn-pagination:hover:not(:disabled) {
        background: #e9ecef;
        border-color: #ced4da;
    }
    
    .btn-pagination:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-pagination.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    
    /* Per page selector */
    .per-page-selector {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .per-page-selector select {
        width: auto;
        min-width: 70px;
        padding: 0.25rem 1.5rem 0.25rem 0.5rem;
        font-size: 0.85rem;
        background-position: right 0.25rem center;
    }
    
    /* Loading spinner */
    .loading-spinner {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .card {
        border: 0 !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .requester-name {
            font-size: 0.9rem;
        }
        .btn-pagination {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    }
</style>

<main id="main">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Pending Requests Card -->
                <div class="card p-3">
                    <div id="pendingRequestsApp">
                        <!-- Header with Title, Count, and Controls -->
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold mb-0 text-primary">
                                    <i class="bi bi-clock-history me-2"></i>
                                    Pending Requisitions
                                </h5>
                                <span class="badge bg-primary" id="pendingRequestsCount">0</span>
                            </div>
                            
                            <div class="d-flex align-items-center gap-2">
                                <!-- Per Page Selector -->
                                <div class="per-page-selector d-flex align-items-center gap-1">
                                    <label for="perPage" class="small text-muted mb-0">Show:</label>
                                    <select id="perPage" class="form-select form-select-sm" v-model="perPage" @change="changePerPage">
                                        <option value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                                
                                <!-- Refresh Button -->
                                <button type="button"
                                    class="btn btn-link text-secondary text-decoration-none refresh-btn p-1"
                                    @click="refreshPendingRequests" :disabled="loading">
                                    <i class="bi" :class="loading ? 'bi-arrow-clockwise animate-spin' : 'bi-arrow-clockwise'"></i>
                                    <span class="small">Refresh</span>
                                </button>
                            </div>
                        </div>

                        <!-- Loading Spinner -->
                        <div class="loading-spinner text-center py-5" v-if="loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2 small">Loading pending requests...</p>
                        </div>

                        <!-- Pending Requests List -->
                        <div v-else>
                            <!-- Empty State -->
                            <div v-if="pendingRequests.length === 0" class="text-center py-5">
                                <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                                <p class="text-muted mb-0">No pending requests found.</p>
                                <p class="text-muted small">Statuses: Pending Approval or Awaiting Payment</p>
                            </div>

                            <!-- Requests List -->
                            <div v-for="request in pendingRequests" :key="request.request_id"
                                class="requisition-card"
                                @click="goToEvent(request.request_id)">
                                
                                <!-- Header with Name and Organization -->
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                        <span class="requester-name">@{{ request.requester.name }}</span>
                                        <span class="text-muted small" v-if="request.requester.organization">
                                            - @{{ request.requester.organization }}
                                        </span>
                                    </div>
                                    <span class="status-badge" :class="getStatusClass(request.status.name)">
                                        @{{ request.status.name }}
                                    </span>
                                </div>
                                
                                <!-- Requested Items -->
                                <div class="mb-1">
                                    <span v-for="(item, index) in displayItems(request.requested_items)" :key="index" 
                                          class="item-chip" :title="item.name">
                                        <i class="bi" :class="item.type === 'facility' ? 'bi-building' : 'bi-tools'"></i>
                                        @{{ item.name }}<span v-if="item.quantity > 1"> (@{{ item.quantity }})</span>
                                    </span>
                                    <span class="item-chip text-muted" v-if="hasMoreItems(request.requested_items)">
                                        <i class="bi bi-three-dots"></i>
                                        @{{ getRemainingCount(request.requested_items) }} more...
                                    </span>
                                </div>
                                
                                <!-- Schedule Info -->
                                <div class="schedule-info">
                                    <i class="bi bi-calendar3 me-1"></i> 
                                    @{{ request.schedule.display }}
                                    <span class="mx-1">•</span>
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    @{{ formatDuration(request) }}
                                </div>
                                
                                <!-- Footer with Request ID -->
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="request-id">#@{{ padRequestId(request.request_id) }}</span>
                                    <i class="bi bi-chevron-right text-primary" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination-container" v-if="totalPages > 1">
                                <div class="pagination-info">
                                    Showing @{{ meta.from || 0 }} to @{{ meta.to || 0 }} of @{{ totalItems }} entries
                                </div>
                                <div class="pagination-controls">
                                    <!-- Previous Button -->
                                    <button class="btn-pagination" 
                                            @click="changePage(currentPage - 1)" 
                                            :disabled="currentPage === 1">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </button>
                                    
                                    <!-- Page Numbers -->
                                    <button v-for="page in displayedPages" 
                                            :key="page"
                                            class="btn-pagination" 
                                            :class="{ active: page === currentPage }"
                                            @click="changePage(page)"
                                            v-if="page !== '...'">
                                        @{{ page }}
                                    </button>
                                    <span v-else class="btn-pagination disabled">...</span>
                                    
                                    <!-- Next Button -->
                                    <button class="btn-pagination" 
                                            @click="changePage(currentPage + 1)" 
                                            :disabled="currentPage === totalPages">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
<script>
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
                pageRange: 2
            }
        };

        // Get admin token
        const adminToken = localStorage.getItem('adminToken');
        if (!adminToken) {
            console.error('No admin token found');
            window.location.href = '/admin/login';
            return;
        }

        // Initialize Vue app
        window.pendingRequestsApp = new Vue({
            el: '#pendingRequestsApp',
            data: {
                pendingRequests: [],
                loading: false,
                currentPage: 1,
                totalPages: 1,
                totalItems: 0,
                perPage: CONFIG.pagination.defaultPerPage,
                meta: {
                    from: 0,
                    to: 0
                }
            },
            computed: {
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
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const result = await response.json();
                        
                        this.pendingRequests = result.data || [];
                        this.totalPages = result.meta?.last_page || 1;
                        this.totalItems = result.meta?.total || 0;
                        this.meta = {
                            from: result.meta?.from || 0,
                            to: result.meta?.to || 0
                        };

                        // Update pending count badge
                        this.updatePendingCount();

                    } catch (error) {
                        console.error('Error loading pending requests:', error);
                        this.pendingRequests = [];
                        this.showToast('Failed to load pending requests', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                async refreshPendingRequests() {
                    await this.loadPendingRequests(this.currentPage);
                    this.showToast('Pending requests refreshed', 'success');
                },

                async updatePendingCount() {
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
                                document.getElementById('pendingRequestsCount').textContent = result.count;
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching pending count:', error);
                    }
                },

                formatDuration(request) {
                    return request.schedule?.duration || 'N/A';
                },

                displayItems(items) {
                    if (!items || items.length === 0) return [];
                    return items.slice(0, 2);
                },

                hasMoreItems(items) {
                    return items && items.length > 2;
                },

                getRemainingCount(items) {
                    return items.length - 2;
                },

                getStatusClass(statusName) {
                    return statusName === 'Pending Approval' ? 'status-pending' : 'status-awaiting';
                },

                padRequestId(id) {
                    return id.toString().padStart(4, '0');
                },

                changePage(page) {
                    if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
                        this.loadPendingRequests(page);
                    }
                },

                changePerPage() {
                    this.currentPage = 1;
                    this.loadPendingRequests(1);
                },

                goToEvent(requestId) {
                    window.location.href = `/admin/requisition/${requestId}`;
                },

                showToast(message, type = 'success') {
                    // Use existing toast function if available
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, type);
                        return;
                    }

                    // Simple fallback
                    console.log(`[${type}] ${message}`);
                }
            },
            mounted() {
                // Load initial data
                this.loadPendingRequests(1);
                
                // Set up per page selector sync
                const perPageSelect = document.getElementById('perPage');
                if (perPageSelect) {
                    perPageSelect.value = this.perPage;
                    perPageSelect.addEventListener('change', (e) => {
                        this.perPage = parseInt(e.target.value);
                        this.changePerPage();
                    });
                }
            }
        });
    })();
</script>
@endsection