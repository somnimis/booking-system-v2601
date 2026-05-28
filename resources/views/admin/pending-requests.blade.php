@extends('layouts.admin')
@section('title', 'Pending Requests')
@section('content')
<style>
    /* Modern Banner Styles - Navy Blue Theme */
    .create-reservation-banner {
        background: linear-gradient(135deg, #0e388c 0%, #1a4a9e 100%);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(11, 45, 114, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
    }
    
    .create-reservation-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='rgba(255,255,255,0.05)' fill-opacity='1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: bottom;
        background-size: cover;
        opacity: 0.3;
        pointer-events: none;
    }
    
    .banner-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .banner-text h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: white;
    }
    
    .banner-text p {
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 0;
        font-size: 0.9rem;
        max-width: 550px;
        line-height: 1.5;
    }
    
    .btn-create-reservation {
        background: white;
        color: #0b2d72;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .btn-create-reservation:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(11, 45, 114, 0.3);
        color: #0b2d72;
        background: #f8f9fa;
    }
    
    .btn-create-reservation i {
        font-size: 1.1rem;
    }
    
    @media (max-width: 768px) {
        .banner-content {
            flex-direction: column;
            text-align: center;
        }
        
        .banner-text p {
            max-width: 100%;
        }
        
        .create-reservation-banner {
            padding: 1.25rem;
        }
        
        .banner-text h3 {
            font-size: 1.25rem;
        }
    }
    
    /* Tab badge styling - Navy Blue */
    .nav-tabs .badge {
        background-color: #0b2d72 !important;
        color: white;
    }

    /* Tab link text styling */
    .nav-tabs .nav-link {
        color: #000000;
        font-weight: 500;
        border: none;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }

    .nav-tabs .nav-link:hover {
        color: #0b2d72;
        border: none;
        background-color: transparent;
    }

    .nav-tabs .nav-link.active {
        color: #0b2d72;
        background-color: transparent;
        border-bottom: 2px solid #0b2d72;
        font-weight: 600;
    }

    /* Remove default Bootstrap tab border */
    .nav-tabs {
        border-bottom: none;
        gap: 0.5rem;
    }

    /* Status badge styles */
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }

    /* Mobile-friendly requisition card styles */
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
    }

    .requester-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.25rem;
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

    /* Pagination styles */
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
        background: #0b2d72;
        border-color: #0b2d72;
        color: white;
    }
</style>

<main id="main">
    <div class="container-fluid px-4">
        <div class="row g-0">
            <!-- Modern Create Reservation Banner -->
            <div class="col-12">
                <div class="create-reservation-banner">
                    <div class="banner-content">
                        <div class="banner-text">
                            <h3>
                                <i class="bi bi-plus-circle me-2"></i>
                                Create Reservation
                            </h3>
                            <p>
                                Create reservations on behalf of users.
                            </p>
                        </div>
                        <a href="{{ url('/admin/reservations/create') }}" class="btn-create-reservation">
                            <i class="bi bi-calendar-plus"></i>
                            Create New Reservation
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Requisitions List with Tabs -->
            <div class="col-12">
                <div class="card p-3">
                    <!-- Tabs with Sort and Show controls in the same row -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
<!-- Bootstrap Tabs -->
<ul class="nav nav-tabs mb-0" id="requisitionTabs" role="tablist" style="border-bottom: none;">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending"
            type="button" role="tab" aria-controls="pending" aria-selected="true">
            Pending Approval <span id="pendingCount" class="badge bg-secondary ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="awaiting-tab" data-bs-toggle="tab" data-bs-target="#awaiting" type="button"
            role="tab" aria-controls="awaiting" aria-selected="false">
            Awaiting Payment <span id="awaitingCount" class="badge bg-secondary ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="payment-submitted-tab" data-bs-toggle="tab" data-bs-target="#payment-submitted" type="button"
            role="tab" aria-controls="payment-submitted" aria-selected="false">
            Payment Submitted <span id="paymentSubmittedCount" class="badge bg-secondary ms-1">0</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="reserved-tab" data-bs-toggle="tab" data-bs-target="#reserved" type="button"
            role="tab" aria-controls="reserved" aria-selected="false">
            Reserved <span id="reservedCount" class="badge bg-secondary ms-1">0</span>
        </button>
    </li>
</ul>

                        <!-- Sort and Per Page Controls -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Sort Dropdown -->
                            <div class="sort-selector d-flex align-items-center gap-1">
                                <label for="sortOrder" class="small text-muted mb-0">Sort:</label>
                                <select id="sortOrder" class="form-select form-select-sm" style="width: 140px;">
                                    <option value="desc">Newest First</option>
                                    <option value="asc" selected>Oldest First</option>
                                </select>
                            </div>

                            <!-- Per Page Selector -->
                            <div class="per-page-selector d-flex align-items-center gap-1">
                                <label for="perPage" class="small text-muted mb-0">Show:</label>
                                <select id="perPage" class="form-select form-select-sm" style="width: 90px;">
                                    <option value="4" selected>4</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                    </div>

<!-- Tab Content -->
<div class="tab-content" id="requisitionTabsContent">
    <!-- Pending Approval Tab -->
    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
        <div id="pendingRequisitionsContainer">
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="mt-2">Loading pending requisitions...</div>
            </div>
        </div>
        <div id="pendingPaginationContainer" class="pagination-container" style="display: none;"></div>
    </div>

    <!-- Awaiting Payment Tab -->
    <div class="tab-pane fade" id="awaiting" role="tabpanel" aria-labelledby="awaiting-tab">
        <div id="awaitingRequisitionsContainer">
            <div class="text-center text-muted py-4">
                Click the tab to load awaiting payment requisitions...
            </div>
        </div>
        <div id="awaitingPaginationContainer" class="pagination-container" style="display: none;"></div>
    </div>

    <!-- Payment Submitted Tab -->
    <div class="tab-pane fade" id="payment-submitted" role="tabpanel" aria-labelledby="payment-submitted-tab">
        <div id="paymentSubmittedRequisitionsContainer">
            <div class="text-center text-muted py-4">
                Click the tab to load payment submitted requisitions...
            </div>
        </div>
        <div id="paymentSubmittedPaginationContainer" class="pagination-container" style="display: none;"></div>
    </div>

    <!-- Reserved Tab -->
    <div class="tab-pane fade" id="reserved" role="tabpanel" aria-labelledby="reserved-tab">
        <div id="reservedRequisitionsContainer">
            <div class="text-center text-muted py-4">
                Click the tab to load reserved requisitions...
            </div>
        </div>
        <div id="reservedPaginationContainer" class="pagination-container" style="display: none;"></div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
// Update the tracking variables
let currentTab = 'pending';
let currentPage = 1;
let currentPerPage = 4;
let currentSortOrder = 'asc';
let totalPages = 1;
let totalItems = 0;

// Track which tabs have been loaded (updated)
let loadedTabs = {
    pending: false,
    awaiting: false,
    'payment-submitted': false,
    reserved: false
};

// Store data for each tab (updated)
let tabData = {
    pending: null,
    awaiting: null,
    'payment-submitted': null,
    reserved: null
};

// Store counts for each tab (updated)
let tabCounts = {
    pending: 0,
    awaiting: 0,
    'payment-submitted': 0,
    reserved: 0
};

// Function to get URL parameter
function getUrlParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

    // Helper function to escape HTML special characters
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const token = localStorage.getItem('adminToken');

        if (!token) {
            console.error('No authentication token found');
            return;
        }

         const tabParam = getUrlParameter('tab');
    
    if (tabParam) {
        // Small delay to ensure DOM is fully loaded
        setTimeout(() => {
            switch(tabParam) {
                case 'pending':
                    document.getElementById('pending-tab').click();
                    break;
                case 'awaiting':
                    document.getElementById('awaiting-tab').click();
                    break;
                case 'payment-submitted':
                    if (document.getElementById('payment-submitted-tab')) {
                        document.getElementById('payment-submitted-tab').click();
                    }
                    break;
                case 'reserved':
                    document.getElementById('reserved-tab').click();
                    break;
                default:
                    // Default to pending tab
                    document.getElementById('pending-tab').click();
                    break;
            }
        }, 100);
    }

        // Fetch all counters immediately (not lazy loaded)
        fetchAllCounters();

// Tab click handlers for lazy loading (content only)
document.getElementById('pending-tab').addEventListener('shown.bs.tab', function () {
    currentTab = 'pending';
    if (!loadedTabs.pending) {
        fetchRequisitionsByStatus(1, 'pending', 1); // Status ID 1 = Pending Approval
    } else if (tabData.pending) {
        displayRequisitions(tabData.pending.data, 'pendingRequisitionsContainer');
        updatePaginationForTab(tabData.pending.meta, 'pendingPaginationContainer', 'pending');
    }
});

document.getElementById('awaiting-tab').addEventListener('shown.bs.tab', function () {
    currentTab = 'awaiting';
    if (!loadedTabs.awaiting) {
        fetchRequisitionsByStatus(1, 'awaiting', 2); // Status ID 2 = Awaiting Payment
    } else if (tabData.awaiting) {
        displayRequisitions(tabData.awaiting.data, 'awaitingRequisitionsContainer');
        updatePaginationForTab(tabData.awaiting.meta, 'awaitingPaginationContainer', 'awaiting');
    }
});

document.getElementById('payment-submitted-tab').addEventListener('shown.bs.tab', function () {
    currentTab = 'payment-submitted';
    if (!loadedTabs['payment-submitted']) {
        fetchRequisitionsByStatus(1, 'payment-submitted', 7); // Status ID 7 = Payment Submitted
    } else if (tabData['payment-submitted']) {
        displayRequisitions(tabData['payment-submitted'].data, 'paymentSubmittedRequisitionsContainer');
        updatePaginationForTab(tabData['payment-submitted'].meta, 'paymentSubmittedPaginationContainer', 'payment-submitted');
    }
});

document.getElementById('reserved-tab').addEventListener('shown.bs.tab', function () {
    currentTab = 'reserved';
    if (!loadedTabs.reserved) {
        fetchRequisitionsByStatus(1, 'reserved', 3); // Status ID 3 = Reserved
    } else if (tabData.reserved) {
        displayRequisitions(tabData.reserved.data, 'reservedRequisitionsContainer');
        updatePaginationForTab(tabData.reserved.meta, 'reservedPaginationContainer', 'reserved');
    }
});

        // Per page selector change handler
        document.getElementById('perPage').addEventListener('change', function () {
            currentPerPage = parseInt(this.value);
            currentPage = 1;
            if (currentTab === 'pending') {
                fetchRequisitionsByStatus(currentPage, 'pending', 1);
            } else if (currentTab === 'awaiting') {
                fetchRequisitionsByStatus(currentPage, 'awaiting', 2);
            } else if (currentTab === 'reserved') {
                fetchRequisitionsByStatus(currentPage, 'reserved', 3);
            }
        });

        // Sort order change handler
        document.getElementById('sortOrder').addEventListener('change', function () {
            currentSortOrder = this.value;
            currentPage = 1;
            if (currentTab === 'pending') {
                fetchRequisitionsByStatus(currentPage, 'pending', 1);
            } else if (currentTab === 'awaiting') {
                fetchRequisitionsByStatus(currentPage, 'awaiting', 2);
            } else if (currentTab === 'reserved') {
                fetchRequisitionsByStatus(currentPage, 'reserved', 3);
            }
        });

        // Initial load for pending tab content
        fetchRequisitionsByStatus(1, 'pending', 1);
    });

    /**
     * Fetch counts for all statuses immediately (not lazy loaded)
     */
function fetchAllCounters() {
    const token = localStorage.getItem('adminToken');

    // Fetch count for Pending Approval (status_id=1)
    fetch(`/api/admin/pending-requests?page=1&per_page=1&status_id=1`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            tabCounts.pending = data.meta.total;
            updateTabBadges();
        })
        .catch(error => console.error('Error fetching pending count:', error));

    // Fetch count for Awaiting Payment (status_id=2)
    fetch(`/api/admin/pending-requests?page=1&per_page=1&status_id=2`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            tabCounts.awaiting = data.meta.total;
            updateTabBadges();
        })
        .catch(error => console.error('Error fetching awaiting count:', error));

    // Fetch count for Payment Submitted (status_id=7)
    fetch(`/api/admin/pending-requests?page=1&per_page=1&status_id=7`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            tabCounts['payment-submitted'] = data.meta.total;
            updateTabBadges();
        })
        .catch(error => console.error('Error fetching payment submitted count:', error));

    // Fetch count for Reserved (status_id=3)
    fetch(`/api/admin/pending-requests?page=1&per_page=1&status_id=3`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        },
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            tabCounts.reserved = data.meta.total;
            updateTabBadges();
        })
        .catch(error => console.error('Error fetching reserved count:', error));
}

    /**
     * Fetch requisitions by status using updated API
     */
function fetchRequisitionsByStatus(page = 1, tab = 'pending', statusId = 1) {
    const token = localStorage.getItem('adminToken');
    let containerId;
    let paginationContainerId;

    // Map tab to container IDs
    switch (tab) {
        case 'pending':
            containerId = 'pendingRequisitionsContainer';
            paginationContainerId = 'pendingPaginationContainer';
            break;
        case 'awaiting':
            containerId = 'awaitingRequisitionsContainer';
            paginationContainerId = 'awaitingPaginationContainer';
            break;
        case 'payment-submitted':
            containerId = 'paymentSubmittedRequisitionsContainer';
            paginationContainerId = 'paymentSubmittedPaginationContainer';
            break;
        case 'reserved':
            containerId = 'reservedRequisitionsContainer';
            paginationContainerId = 'reservedPaginationContainer';
            break;
        default:
            containerId = 'pendingRequisitionsContainer';
            paginationContainerId = 'pendingPaginationContainer';
    }

        const container = document.getElementById(containerId);

        // Show loading state
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="mt-2">Loading requisitions...</div>
            </div>
        `;

        document.getElementById(paginationContainerId).style.display = 'none';

        // Build URL with status_id parameter
        const url = `/api/admin/pending-requests?page=${page}&per_page=${currentPerPage}&sort_order=${currentSortOrder}&status_id=${statusId}`;

        fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'include'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Store data for this tab
                tabData[tab] = data;

                // Update count for this specific tab from the meta.total
                if (tab === 'pending') {
                    tabCounts.pending = data.meta.total;
                } else if (tab === 'awaiting') {
                    tabCounts.awaiting = data.meta.total;
                } else if (tab === 'reserved') {
                    tabCounts.reserved = data.meta.total;
                }

                // Update all tab badges
                updateTabBadges();

                // Mark tab as loaded
                loadedTabs[tab] = true;

                // Display requisitions
                displayRequisitions(data.data, containerId);

                // IMPORTANT: Use the meta data from the API response
                updatePaginationForTab(data.meta, paginationContainerId, tab);
            })
            .catch(error => {
                console.error('Error fetching requisition data:', error);
                container.innerHTML = `
                    <div class="text-center text-danger py-4">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                        <div class="mt-2">Failed to load requisitions</div>
                        <small class="text-muted">${error.message}</small>
                    </div>
                `;
            });
    }

    /**
     * Update tab badge counts
     */
function updateTabBadges() {
    document.getElementById('pendingCount').textContent = tabCounts.pending;
    document.getElementById('awaitingCount').textContent = tabCounts.awaiting;
    document.getElementById('paymentSubmittedCount').textContent = tabCounts['payment-submitted'];
    document.getElementById('reservedCount').textContent = tabCounts.reserved;
}

    /**
     * Display requisitions in the specified container
     */
function displayRequisitions(requisitions, containerId) {
    const container = document.getElementById(containerId);

    if (!requisitions || requisitions.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4 small">
                <i class="bi bi-inbox fs-4"></i>
                <div class="mt-2">No requisitions found</div>
            </div>
        `;
        return;
    }

    const cardsHTML = requisitions.map(req => {
        const requestId = req.request_id;
        const requesterName = req.requester.name;
        const organization = req.requester.organization;
        const statusName = req.status.name;
        const statusColor = req.status.color; // Get color from API response
        const schedule = req.schedule.display;

        const dateSubmitted = req.created_at ? new Date(req.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) : 'Date unknown';

        const eventTitle = req.event_title && req.event_title.trim() !== '' ? req.event_title : 'No Event Title';
        const eventDetails = req.event_details && req.event_details.trim() !== '' ? req.event_details : 'No Description';

        return `
            <div class="requisition-card clickable-requisition-item py-2" data-request-id="${requestId}">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="d-flex align-items-center flex-wrap gap-1">
                        <span class="requester-name">${escapeHtml(requesterName)}</span>
                        <span class="text-muted small">- ${escapeHtml(organization)}</span>
                    </div>
                    <span class="status-badge" style="background-color: ${statusColor}; color: white; border: none;">
                        ${escapeHtml(statusName)}
                    </span>
                </div>

                <div class="mb-2 small">
                    <strong>${escapeHtml(eventTitle)}</strong> — ${escapeHtml(eventDetails)}
                </div>

                <div class="schedule-info">
                    <i class="bi bi-calendar3 me-1"></i> ${escapeHtml(schedule)}
                </div>

                <div class="schedule-info mt-1">
                    <i class="bi bi-clock-history me-1"></i> Submitted: ${dateSubmitted}
                </div>

                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="request-id">#${requestId.toString().padStart(4, '0')}</span>
                    <i class="bi bi-chevron-right text-primary" style="font-size: 0.8rem;"></i>
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = cardsHTML;
    addRequisitionItemClickListeners();
}

    /**
     * Update pagination for a specific tab
     */
    function updatePaginationForTab(meta, paginationContainerId, tab) {
        const paginationContainer = document.getElementById(paginationContainerId);

        if (!paginationContainer) return;

        // Always show pagination container, even with one page
        if (!meta.last_page || meta.total === 0) {
            paginationContainer.style.display = 'none';
            return;
        }

        paginationContainer.style.display = 'flex';
        paginationContainer.innerHTML = `
            <div class="pagination-info">Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total} entries</div>
            <div class="pagination-controls" id="${tab}PaginationControls"></div>
        `;

        const controlsContainer = document.getElementById(`${tab}PaginationControls`);
        if (!controlsContainer) return;

        let buttonsHTML = '';

        // Previous button
        buttonsHTML += `
            <button class="btn-pagination" onclick="changePageForTab(${meta.current_page - 1}, '${tab}')" ${meta.current_page === 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
        `;

        // Page numbers - show all pages if less than 5, otherwise show with ellipsis
        if (meta.last_page <= 5) {
            for (let i = 1; i <= meta.last_page; i++) {
                buttonsHTML += `
                    <button class="btn-pagination ${i === meta.current_page ? 'active' : ''}" onclick="changePageForTab(${i}, '${tab}')">
                        ${i}
                    </button>
                `;
            }
        } else {
            const startPage = Math.max(1, meta.current_page - 2);
            const endPage = Math.min(meta.last_page, startPage + 4);

            if (startPage > 1) {
                buttonsHTML += `
                    <button class="btn-pagination" onclick="changePageForTab(1, '${tab}')">1</button>
                    ${startPage > 2 ? '<span class="px-1">...</span>' : ''}
                `;
            }

            for (let i = startPage; i <= endPage; i++) {
                buttonsHTML += `
                    <button class="btn-pagination ${i === meta.current_page ? 'active' : ''}" onclick="changePageForTab(${i}, '${tab}')">
                        ${i}
                    </button>
                `;
            }

            if (endPage < meta.last_page) {
                buttonsHTML += `
                    ${endPage < meta.last_page - 1 ? '<span class="px-1">...</span>' : ''}
                    <button class="btn-pagination" onclick="changePageForTab(${meta.last_page}, '${tab}')">${meta.last_page}</button>
                `;
            }
        }

        // Next button
        buttonsHTML += `
            <button class="btn-pagination" onclick="changePageForTab(${meta.current_page + 1}, '${tab}')" ${meta.current_page === meta.last_page ? 'disabled' : ''}>
                Next <i class="bi bi-chevron-right"></i>
            </button>
        `;

        controlsContainer.innerHTML = buttonsHTML;
    }

    /**
     * Change page for a specific tab
     */
function changePageForTab(page, tab) {
    if (page < 1) return;

    let statusId;
    if (tab === 'pending') statusId = 1;
    else if (tab === 'awaiting') statusId = 2;
    else if (tab === 'payment-submitted') statusId = 7;
    else if (tab === 'reserved') statusId = 3;

    fetchRequisitionsByStatus(page, tab, statusId);
}

    /**
     * Add click listeners to requisition items
     */
    function addRequisitionItemClickListeners() {
        const requisitionItems = document.querySelectorAll('.clickable-requisition-item');

        requisitionItems.forEach(item => {
            item.removeEventListener('click', handleItemClick);
            item.addEventListener('click', handleItemClick);
        });
    }

    function handleItemClick() {
        const requestId = this.getAttribute('data-request-id');
        if (requestId) {
            window.location.href = `/admin/requisition/${requestId}`;
        }
    }
</script>
@endsection