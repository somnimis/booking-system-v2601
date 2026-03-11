@extends('layouts.admin')

@section('title', 'Signatory Dashboard')

@section('content')
  <style>
    /* Feedback item styles */
.feedback-item {
    border-left: 3px solid #007bff;
    padding-left: 1rem;
}

.feedback-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.ratings-section .badge {
    font-size: 0.7em;
    padding: 0.25rem 0.5rem;
}

.log-container {
    max-height: 400px;
    overflow-y: auto;
}

    /* New styles for the dashboard header */
    .dashboard-header {
      position: relative;
      padding: 2rem;
      margin-bottom: 2rem;
      border-radius: 0.5rem;
      overflow: hidden;
      color: white;
    }

    .dashboard-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 100%;
      background-image: url("{{ asset('assets/cpu-pic1.jpg') }}");
      background-size: cover;
      background-position: center;
      z-index: -1;
    }

    .dashboard-header::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 100%;
      background: linear-gradient(to right, rgba(0, 51, 102, 0.8), rgba(0, 51, 102, 0.5));
      z-index: -1;
    }

    /* Status badge styles */
    .status-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
      font-weight: 500;
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
    
    .organization-badge {
      background-color: #e9ecef;
      color: #495057;
      font-size: 0.7rem;
      padding: 0.2rem 0.5rem;
      border-radius: 0.25rem;
      display: inline-block;
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
    
    .status-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 0.5rem;
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
      background: #0d6efd;
      border-color: #0d6efd;
      color: white;
    }
  </style>

  <div>
    <!-- Main Content -->
    <main id="main">
      <!-- Dashboard Header with Wallpaper -->
      <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="fw-bold mb-0">Your Dashboard</h2>
          <a href="#" id="manageProfileBtn" class="btn btn-light">
            <i class="bi bi-gear me-1"></i> Edit Profile
          </a>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 hover-effect">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="text-muted small">Ongoing Events</span>
                  <h2 class="mt-2 mb-0 fw-bold" id="ongoingEvents">0</h2>
                </div>
                <a href="{{ asset('admin/calendar') }}" class="text-primary text-decoration-none">
                  <div
                    class="bg-primary bg-opacity-10 p-3 rounded-circle d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px; border-color: #5d759917 !important;">
                    <i class="fa-solid fa-angle-right fs-5"></i>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 hover-effect">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="text-muted small">Pending Requests</span>
                  <h2 class="mt-2 mb-0 fw-bold" id="pendingRequests">0</h2>
                </div>
                <a href="{{ asset('admin/manage-requests') }}" class="text-primary text-decoration-none">
                  <div
                    class="bg-primary bg-opacity-10 p-3 rounded-circle d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px; border-color: #5d759917 !important;">
                    <i class="fa-solid fa-angle-right fs-5"></i>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 hover-effect">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <span class="text-muted small">Completed Transactions</span>
                  <h2 class="mt-2 mb-0 fw-bold" id="totalRequisitions">0</h2>
                </div>
                <a href="{{ asset('admin/archives') }}" class="text-primary text-decoration-none">
                  <div
                    class="bg-primary bg-opacity-10 p-3 rounded-circle d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px; border-color: #5d759917 !important;">
                    <i class="fa-solid fa-angle-right fs-5"></i>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

<div class="row g-3">
<!-- Pending Requisitions List -->
<div class="col-12 mt-3">
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/admin/manage-requests') }}" class="text-decoration-none">
          <h5 class="fw-bold mb-0 text-primary">Pending Requisitions</h5>
        </a>
        <span class="badge bg-primary" id="requisitionCount">0</span>
      </div>
      
      <!-- Per Page Selector - Alone on the right -->
      <div class="per-page-selector d-flex align-items-center gap-1 ms-auto">
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
    
    <!-- Requisition List Container -->
    <div id="requisitionListContainer">
      <!-- Content will be loaded here -->
    </div>
    
    <!-- Pagination Container -->
    <div id="paginationContainer" class="pagination-container" style="display: none;">
      <div class="pagination-info" id="paginationInfo"></div>
      <div class="pagination-controls" id="paginationControls"></div>
    </div>
  </div>
</div>
</div>

    </main>
  </div>
@endsection

@section('scripts')
  <script>
    let currentPage = 1;
    let currentPerPage = 4;
    let totalPages = 1;
    let totalItems = 0;

    document.addEventListener('DOMContentLoaded', function () {
      // === Get admin authentication token === //
      const adminId = localStorage.getItem('adminId');
      const manageProfileBtn = document.getElementById('manageProfileBtn');
      if (adminId) {
        manageProfileBtn.href = `/admin/profile/${adminId}`;
      }

      // Get the authentication token
      const token = localStorage.getItem('adminToken');

      if (!token) {
        console.error('No authentication token found');
        return;
      }

      // Initial fetch of pending requests
      fetchPendingRequests(currentPage, currentPerPage);
      
      // Per page selector change handler
      document.getElementById('perPage').addEventListener('change', function() {
        currentPerPage = parseInt(this.value);
        currentPage = 1; // Reset to first page
        fetchPendingRequests(currentPage, currentPerPage);
      });
    });

/**
 * Fetch pending requests from the simplified endpoint
 */
function fetchPendingRequests(page = 1, perPage = 15) {
  const token = localStorage.getItem('adminToken');
  const requisitionContainer = document.getElementById('requisitionListContainer');
  
  // Show loading state
  requisitionContainer.innerHTML = `
    <div class="text-center text-muted py-4">
      <div class="spinner-border spinner-border-sm" role="status"></div>
      <div class="mt-2">Loading requisitions...</div>
    </div>
  `;
  
  // Hide pagination while loading
  document.getElementById('paginationContainer').style.display = 'none';

  // CHANGE THIS LINE - add /api/ prefix
  fetch(`/api/admin/pending-requests?page=${page}&per_page=${perPage}`, {
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
    // Update pagination info
    currentPage = data.meta.current_page;
    totalPages = data.meta.last_page;
    totalItems = data.meta.total;
    
    // Update stats cards
    updateStatsCards(data.data);
    
    // Display pending requisitions
    displayPendingRequisitions(data.data);
    
    // Update pagination UI
    updatePagination(data.meta, data.links);
  })
  .catch(error => {
    console.error('Error fetching requisition data:', error);
    
    // Show error in requisition list
    document.getElementById('requisitionListContainer').innerHTML = `
      <div class="text-center text-danger py-4">
        <i class="bi bi-exclamation-triangle fs-4"></i>
        <div class="mt-2">Failed to load requisitions</div>
        <small class="text-muted">${error.message}</small>
      </div>
    `;
  });
}

    /**
     * Update stats cards using the new data structure
     */
    function updateStatsCards(requisitions) {
      // Get status IDs to identify pending and ongoing
      const pendingRequests = requisitions.filter(req => 
        req.status.name === 'Pending Approval' || req.status.name === 'Awaiting Payment'
      ).length;
      
      // For ongoing events and total, we'd need additional data
      // Keeping the old values for now, but you might want to fetch these separately
      document.getElementById('pendingRequests').textContent = pendingRequests;
      
      // Note: totalRequisitions and ongoingEvents might need separate endpoints
      // For now, we're just updating pendingRequests from this data
    }

 /**
 * Display pending requisitions in mobile-friendly cards
 */
function displayPendingRequisitions(requisitions) {
  const requisitionContainer = document.getElementById('requisitionListContainer');
  const requisitionCount = document.getElementById('requisitionCount');

  if (!requisitions || requisitions.length === 0) {
    requisitionContainer.innerHTML = `
      <div class="text-center text-muted py-4 small">
        <i class="bi bi-inbox fs-4"></i>
        <div class="mt-2">No pending requisitions</div>
      </div>
    `;
    requisitionCount.textContent = '0';
    document.getElementById('paginationContainer').style.display = 'none';
    return;
  }

  // Update count
  requisitionCount.textContent = totalItems;

  // Generate mobile-friendly card HTML with more compact spacing
  const cardsHTML = requisitions.map(req => {
    const requestId = req.request_id;
    const requesterName = req.requester.name;
    const organization = req.requester.organization;
    const status = req.status.name;
    const statusClass = status === 'Pending Approval' ? 'status-pending' : 'status-awaiting';
    const statusColor = req.status.color;
    const schedule = req.schedule.display;
    
    // Generate item chips
    const itemChips = req.requested_items.map(item => {
      const icon = item.type === 'facility' ? 'bi-building' : 'bi-tools';
      return `
        <span class="item-chip" title="${item.name}">
          <i class="bi ${icon}"></i> ${item.name}
          ${item.quantity > 1 ? ` (${item.quantity})` : ''}
        </span>
      `;
    }).join('');

    return `
      <div class="requisition-card clickable-requisition-item py-2" data-request-id="${requestId}">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div class="d-flex align-items-center flex-wrap gap-1">
            <span class="requester-name">${requesterName}</span>
            <span class="text-muted small">- ${organization}</span>
          </div>
          <span class="status-badge ${statusClass} ms-2">${status}</span>
        </div>
        
        <div class="mb-1">
          ${itemChips}
        </div>
        
        <div class="schedule-info">
          <i class="bi bi-calendar3 me-1"></i> ${schedule}
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-1">
          <span class="request-id">#${requestId.toString().padStart(4, '0')}</span>
          <i class="bi bi-chevron-right text-primary" style="font-size: 0.8rem;"></i>
        </div>
      </div>
    `;
  }).join('');

  requisitionContainer.innerHTML = cardsHTML;

  // Add click event listeners to all requisition cards
  addRequisitionItemClickListeners();
  
  // Show pagination
  document.getElementById('paginationContainer').style.display = 'flex';
}
    /**
     * Update pagination controls
     */
    function updatePagination(meta, links) {
      const paginationInfo = document.getElementById('paginationInfo');
      const paginationControls = document.getElementById('paginationControls');
      
      // Update info text
      paginationInfo.textContent = `Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total} entries`;
      
      // Generate pagination buttons
      let buttonsHTML = '';
      
      // Previous button
      buttonsHTML += `
        <button class="btn-pagination" 
                onclick="changePage(${meta.current_page - 1})" 
                ${meta.current_page === 1 ? 'disabled' : ''}>
          <i class="bi bi-chevron-left"></i> Previous
        </button>
      `;
      
      // Page numbers (show up to 5 pages)
      const startPage = Math.max(1, meta.current_page - 2);
      const endPage = Math.min(meta.last_page, startPage + 4);
      
      for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `
          <button class="btn-pagination ${i === meta.current_page ? 'active' : ''}" 
                  onclick="changePage(${i})">
            ${i}
          </button>
        `;
      }
      
      // Next button
      buttonsHTML += `
        <button class="btn-pagination" 
                onclick="changePage(${meta.current_page + 1})" 
                ${meta.current_page === meta.last_page ? 'disabled' : ''}>
          Next <i class="bi bi-chevron-right"></i>
        </button>
      `;
      
      paginationControls.innerHTML = buttonsHTML;
    }

    /**
     * Change page
     */
    function changePage(page) {
      if (page < 1 || page > totalPages) return;
      currentPage = page;
      fetchPendingRequests(currentPage, currentPerPage);
    }

    /**
     * Add click listeners to requisition items
     */
    function addRequisitionItemClickListeners() {
      const requisitionItems = document.querySelectorAll('.clickable-requisition-item');

      requisitionItems.forEach(item => {
        item.addEventListener('click', function () {
          const requestId = this.getAttribute('data-request-id');
          if (requestId) {
            window.location.href = `/admin/requisition/${requestId}`;
          }
        });
      });
    }
  </script>
@endsection