@extends('layouts.admin')

@section('title', 'Manage Equipment')

@section('content')
  <style>
    .layout-select {
      min-width: 180px;
    }

    /* Loading overlay styles */
    .equipment-container-loading {
      position: relative;
      min-height: 400px;
    }

    .loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.95);
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
    }

    .loading-spinner {
      text-align: center;
    }

    .loading-spinner .spinner-border {
      width: 3rem;
      height: 3rem;
    }

    .pagination.disabled-pagination {
      opacity: 0.5;
      pointer-events: none;
    }

    .pagination.disabled-pagination .page-link {
      cursor: not-allowed;
      background-color: #e9ecef;
    }

    /* Pagination stays at bottom */
    .d-flex.justify-content-center.mt-auto.pt-3 {
      flex-shrink: 0;
    }

    /* Target the equipment dropdown menu in both states (open and closed) */
    #equipmentDropdownToggle+.dropdown-menu,
    #equipmentDropdownToggle+.dropdown-menu.show {
      z-index: 9999 !important;
      position: absolute !important;
    }

    .btn-outline-danger {
      background-color: #ffe5e5;
      border-color: #dc3545;
      color: #dc3545;
    }

    .btn-outline-danger:hover {
      background-color: #dc3545;
      color: #fff;
    }

    .filters-row {
      flex-wrap: nowrap !important;
      overflow-x: auto;
      overflow-y: hidden;
      white-space: nowrap;
    }

    /* Toast notification styles */
    .toast {
      z-index: 1100;
      bottom: 0;
      left: 0;
      margin: 1rem;
      opacity: 0;
      transform: translateY(20px);
      transition: transform 0.4s ease, opacity 0.4s ease;
      min-width: 250px;
      border-radius: 0.3rem;
    }

    .toast .loading-bar {
      height: 3px;
      background: rgba(255, 255, 255, 0.7);
      width: 100%;
      transition: width 3000ms linear;
    }

    /* Custom pagination colors using CPU theme */
    .pagination .page-link {
      color: var(--cpu-primary);
    }

    .pagination .page-link:hover {
      color: var(--cpu-primary-hover);
    }

    .pagination .page-item.active .page-link {
      background-color: var(--cpu-primary);
      border-color: var(--cpu-primary);
      color: #fff;
    }

    .pagination .page-item.disabled .page-link {
      color: #6c757d;
      pointer-events: none;
      background-color: var(--light-gray);
      border-color: #dee2e6;
    }

    /* Fix button group corner rounding */
    .btn-group>.btn:first-child {
      border-top-right-radius: 0 !important;
      border-bottom-right-radius: 0 !important;
    }

    .btn-group>.dropdown-toggle-split {
      border-top-left-radius: 0 !important;
      border-bottom-left-radius: 0 !important;
    }

    /* Loading skeleton styles */
    .skeleton-card {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
      border-radius: 8px;
    }

    @keyframes loading {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }
  </style>

  <!-- Main Content -->
  <main id="main">
    <div class="container-fluid px-4">
      <!-- Header & Controls -->
      <div>
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="card-title m-0 fw-bold">Manage Equipment</h2>
        </div>

        <!-- Filters, Search Bar & Buttons (single scrollable row) -->
        <div class="row mb-3 g-2 align-items-center filters-row">
          <div class="col-auto flex-shrink-0">
            <select id="layoutSelect" class="form-select layout-select">
              <option value="grid">Grid Layout</option>
              <option value="list">List Layout</option>
            </select>
          </div>

          <div class="col-auto flex-shrink-0">
            <select id="statusFilter" class="form-select">
              <option value="all">All Statuses</option>
            </select>
          </div>

          <div class="col-auto flex-shrink-0">
            <select id="categoryFilter" class="form-select">
              <option value="all">All Categories</option>
            </select>
          </div>

          <div class="col flex-grow-1">
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="searchInput" class="form-control" placeholder="Search Equipment...">
            </div>
          </div>

          <div class="col-auto text-nowrap">
            <div class="btn-group" role="group">
              <a href="{{ url('/admin/add-equipment') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle-fill me-2"></i>Add New
              </a>
              <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                id="equipmentDropdownToggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="equipmentDropdownToggle">
                <li>
                  <button class="dropdown-item" type="button" data-bs-toggle="modal"
                    data-bs-target="#massAssignDepartmentsModal">
                    <i class="bi bi-diagram-3-fill me-2"></i>Mass Assign Departments
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Equipment List (scrollable) -->
        <div id="equipmentContainer">
          <div class="row g-2" id="equipmentCardsContainer">
            <!-- Loading skeletons will be shown here -->
          </div>
        </div>
      </div>

      <!-- Pagination Controls (fixed at bottom) -->
      <div class="d-flex justify-content-center mt-auto pt-3">
        <nav aria-label="Equipment pagination">
          <ul class="pagination" id="paginationContainer"></ul>
        </nav>
      </div>

      <!-- Mass Assign Departments Modal -->
      <div class="modal fade" id="massAssignDepartmentsModal" tabindex="-1"
        aria-labelledby="massAssignDepartmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="massAssignDepartmentsModalLabel" style="color: #003366;">Mass Assign Department
                to Equipment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="massAssignForm">
                <!-- Equipment Selection -->
                <div class="mb-4">
                  <div class="mb-2 d-flex justify-content-between align-items-center">
                    <label class="form-label fw-bold mb-0" style="color: #003366;">Select Equipment</label>
                    <div>
                      <button type="button" id="selectAllEquipment" class="btn btn-sm btn-primary me-2"
                        style="background-color: #003366; color: white;">
                        <i class="bi bi-check-all"></i> Select All
                      </button>
                      <button type="button" id="clearAllEquipment" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear All
                      </button>
                    </div>
                  </div>
                  <select id="equipmentMultiSelect" class="form-select" multiple size="8" style="border-color: #003366;">
                  </select>
                  <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple equipment</div>
                </div>

                <!-- Department Selection - Changed to single select -->
                <div class="mb-4">
                  <label class="form-label fw-bold" style="color: #003366;">Select Department to Assign</label>
                  <select id="departmentSingleSelect" class="form-select" style="border-color: #003366;">
                    <option value="">Select Department</option>
                  </select>
                  <div class="form-text text-muted">Select the department that will manage the selected equipment</div>
                </div>

                <!-- Summary -->
                <div class="alert" style="background-color: #f8f9fa; border-left: 4px solid #003366;"
                  id="selectionSummary">
                  <i class="bi bi-info-circle me-2" style="color: #003366;"></i>
                  <span id="summaryText">No equipment selected</span>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn" id="executeMassAssignBtn" style="background-color: #003366; color: white;"
                onmouseover="this.style.backgroundColor='#004080'" onmouseout="this.style.backgroundColor='#003366'">
                <i class="bi bi-check-circle me-2"></i>Assign Department
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Warning Modal -->
      <div class="modal fade" id="assignmentWarningModal" tabindex="-1" aria-labelledby="warningModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="warningModalLabel" style="color: #003366;">Confirm Department Assignment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="text-center mb-3">
                <i class="bi bi-exclamation-triangle-fill" style="color: #003366; font-size: 2.5rem;"></i>
              </div>
              <p class="text-center mb-0">
                This action will <strong>replace all existing department assignments</strong> for the selected equipment
                with the new departments.
              </p>
              <p class="text-center text-muted mt-2 mb-0">
                Are you sure you want to continue?
              </p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn" id="confirmAssignBtn" style="background-color: #003366; color: white;"
                onmouseover="this.style.backgroundColor='#004080'" onmouseout="this.style.backgroundColor='#003366'">
                <i class="bi bi-check-circle me-2"></i>Yes, Assign Departments
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

@endsection

@section('scripts')
  <script>
    document.addEventListener("DOMContentLoaded", function () {

      // Authentication check
      const token = localStorage.getItem("adminToken");
      if (!token) {
        window.location.href = "/admin/login";
        return;
      }

      // DOM elements
      const equipmentContainer = document.getElementById("equipmentContainer");
      const searchInput = document.getElementById("searchInput");
      const layoutSelect = document.getElementById("layoutSelect");
      const statusFilter = document.getElementById("statusFilter");
      const categoryFilter = document.getElementById("categoryFilter");
      const paginationContainer = document.getElementById("paginationContainer");

      // State variables
      let allEquipment = [];
      let filteredEquipment = [];
      let currentPage = 1;
      let totalPages = 1;
      let totalItems = 0;
      let itemsPerPage = 12;
      let isLoading = false;

      // Filter state
      let currentFilters = {
        status_id: 'all',
        category_id: 'all',
        search: ''
      };

      // Debounce timer for search
      let searchDebounceTimer;

      // Initialize the page
      async function init() {
        try {
          await fetchEquipmentData();
          setupEventListeners();
        } catch (error) {
          console.error("Initialization error:", error);
          showToast("Failed to initialize page. Please refresh and try again.", "error");
        }
      }

      // Fetch equipment data from merged API
      async function fetchEquipmentData(page = 1) {
        if (isLoading) return;

        isLoading = true;
        showLoadingOverlay(); // Changed from showLoadingSkeletons()

        try {
          // Build query parameters
          const params = new URLSearchParams();
          params.append('page', page);

          if (currentFilters.status_id && currentFilters.status_id !== 'all') {
            params.append('status_id', currentFilters.status_id);
          }

          if (currentFilters.category_id && currentFilters.category_id !== 'all') {
            params.append('category_id', currentFilters.category_id);
          }

          if (currentFilters.search && currentFilters.search.trim()) {
            params.append('search', currentFilters.search.trim());
          }

          const response = await fetch(`/api/admin/manage-equipment?${params.toString()}`, {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          });

          if (response.status === 401) {
            localStorage.removeItem("adminToken");
            showToast("Your session has expired. Please log in again.", "error");
            setTimeout(() => {
              window.location.href = "/admin/login";
            }, 2000);
            return;
          }

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }

          const result = await response.json();

          if (!result.success) {
            throw new Error(result.message || "Failed to fetch equipment");
          }

          // Store equipment data
          allEquipment = result.data || [];
          filteredEquipment = [...allEquipment];

          // Update pagination info
          totalPages = result.pagination.last_page;
          currentPage = result.pagination.current_page;
          totalItems = result.pagination.total;

          // Populate filter dropdowns on first load only
          if (page === 1 && result.filters) {
            populateStatusFilter(result.filters.statuses);
            populateCategoryFilter(result.filters.categories);
          }

          // Render equipment
          renderEquipment();

          // Update pagination controls
          updatePagination();

        } catch (error) {
          console.error("Error fetching equipment:", error);
          showToast(error.message || "Failed to load equipment", "error");
          showEmptyState("Failed to load equipment. Please try again.");
        } finally {
          isLoading = false;
          hideLoadingOverlay(); // Changed from setting isLoading false
        }
      }

      // Show loading overlay
      function showLoadingOverlay() {
        const container = document.getElementById('equipmentContainer');
        if (!container) return;

        // Add loading class to container
        container.classList.add('equipment-container-loading');

        // Check if overlay already exists
        let overlay = container.querySelector('.loading-overlay');
        if (!overlay) {
          overlay = document.createElement('div');
          overlay.className = 'loading-overlay';
          overlay.innerHTML = `
                      <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading equipment...</p>
                      </div>
                    `;
          container.appendChild(overlay);
        } else {
          overlay.style.display = 'flex';
        }

        // Disable pagination
        const pagination = document.getElementById('paginationContainer');
        if (pagination) {
          pagination.classList.add('disabled-pagination');
        }
      }

      // Hide loading overlay
      function hideLoadingOverlay() {
        const container = document.getElementById('equipmentContainer');
        if (!container) return;

        container.classList.remove('equipment-container-loading');

        const overlay = container.querySelector('.loading-overlay');
        if (overlay) {
          overlay.style.display = 'none';
        }

        // Re-enable pagination
        const pagination = document.getElementById('paginationContainer');
        if (pagination) {
          pagination.classList.remove('disabled-pagination');
        }
      }

      // Populate status filter dropdown
      function populateStatusFilter(statuses) {
        statusFilter.innerHTML = '<option value="all">All Statuses</option>';

        if (statuses && Array.isArray(statuses)) {
          statuses.forEach((status) => {
            const option = document.createElement("option");
            option.value = status.status_id;
            option.textContent = status.status_name;
            statusFilter.appendChild(option);
          });
        }
      }

      // Populate category filter dropdown
      function populateCategoryFilter(categories) {
        categoryFilter.innerHTML = '<option value="all">All Categories</option>';

        if (categories && Array.isArray(categories)) {
          categories.forEach((category) => {
            const option = document.createElement("option");
            option.value = category.category_id;
            option.textContent = category.category_name;
            categoryFilter.appendChild(option);
          });
        }
      }

      // Render equipment cards
      function renderEquipment() {
        const container = document.getElementById('equipmentCardsContainer');
        if (!container) return;

        container.innerHTML = "";

        if (!filteredEquipment || filteredEquipment.length === 0) {
          showEmptyState("No equipment found matching your criteria");
          return;
        }

        const layout = layoutSelect.value;
        container.className = layout === "list" ? "row g-3" : "row g-2";

        filteredEquipment.forEach((equipment) => {
          const statusClass = getStatusClass(equipment.status.status_name);

          // Find primary image
          let primaryImage = "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

          if (equipment.images && equipment.images.length > 0) {
            const validImages = equipment.images.filter(img => img.image_url && img.image_url.trim() !== '');
            if (validImages.length > 0) {
              const sortOrder1Image = validImages.find(img => img.sort_order === 1);
              const primaryTypeImage = validImages.find(img => img.image_type === "Primary");

              primaryImage = sortOrder1Image?.image_url ||
                primaryTypeImage?.image_url ||
                validImages[0]?.image_url ||
                primaryImage;
            }
          }

          const card = document.createElement("div");

          if (layout === "list") {
            card.className = "col-12 equipment-card mb-0";
            card.innerHTML = `
                          <div class="card h-100 shadow-sm rounded-3">
                            <div class="row g-0">
                              <div class="col-md-2" style="max-width: 120px; flex: 0 0 120px;">
                                <img src="${primaryImage}" 
                                     class="img-fluid rounded-start" 
                                     style="width: 120px; height: 120px; object-fit: cover;" 
                                     alt="${escapeHtml(equipment.equipment_name)}"
                                     onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                              </div>
                              <div class="col-md-8">
                                <div class="card-body py-3">
                                  <h5 class="card-title fw-bold mb-2">${escapeHtml(equipment.equipment_name)}</h5>
                                  <p class="card-text mb-2">
                                    <span class="badge ${statusClass} me-2">${escapeHtml(equipment.status.status_name)}</span>
                                    <small class="text-muted">
                                      <i class="bi bi-tag-fill text-primary me-1"></i>${escapeHtml(equipment.category.category_name)}
                                      <i class="bi bi-box-fill text-primary ms-2 me-1"></i>${equipment.available_quantity}/${equipment.total_quantity} available
                                    </small>
                                  </p>
                                  <p class="card-text text-muted mb-0">
                                    ${escapeHtml(equipment.description || "No description available")}
                                  </p>
                                </div>
                              </div>
                              <div class="col-md-2 d-flex align-items-center justify-content-center">
                                <div class="d-grid gap-2 w-100 px-2">
                                  <a href="/admin/edit-equipment?id=${equipment.equipment_id}" 
                                     class="btn btn-sm btn-primary">
                                     Manage
                                  </a>
                                  <button class="btn btn-sm btn-outline-danger btn-delete" 
                                          data-id="${equipment.equipment_id}"
                                          data-name="${escapeHtml(equipment.equipment_name)}">
                                    Delete
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                        `;
          } else {
            card.className = "col-md-4 col-lg-3 equipment-card mb-3";
            card.innerHTML = `
                          <div class="card h-100">
                            <img src="${primaryImage}" 
                                 class="card-img-top" 
                                 style="height: 150px; object-fit: cover;" 
                                 alt="${escapeHtml(equipment.equipment_name)}"
                                 onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                            <div class="card-body d-flex flex-column p-2">
                              <div>
                                <h6 class="card-title mb-1 fw-bold">${escapeHtml(equipment.equipment_name)}</h6>
                                <p class="card-text text-muted mb-1 small">
                                  <i class="bi bi-tag-fill text-primary me-1"></i>${escapeHtml(equipment.category.category_name)}
                                  <i class="bi bi-box-fill text-primary ms-2 me-1"></i>${equipment.available_quantity}/${equipment.total_quantity}
                                </p>
                                <span class="badge ${statusClass} mb-2">${escapeHtml(equipment.status.status_name)}</span>
                                <p class="card-text mb-2 small text-truncate">${escapeHtml(equipment.description || "No description available")}</p>
                              </div>
                              <div class="equipment-actions mt-auto d-grid gap-1">
                                <a href="/admin/edit-equipment?id=${equipment.equipment_id}" class="btn btn-sm btn-primary btn-manage">Manage</a>
                                <button class="btn btn-sm btn-outline-danger btn-delete" 
                                        data-id="${equipment.equipment_id}"
                                        data-name="${escapeHtml(equipment.equipment_name)}">Delete</button>
                              </div>
                            </div>
                          </div>
                        `;
          }

          container.appendChild(card);
        });

        // Add event listeners to delete buttons
        addDeleteButtonListeners();
      }

      // Helper function to escape HTML
      function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }

      // Show empty state
      function showEmptyState(message = "No equipment found.") {
        const container = document.getElementById('equipmentCardsContainer');
        if (container) {
          container.innerHTML = `
                        <div class="col-12 text-center py-5">
                          <i class="bi bi-tools fs-1 text-muted" style="font-size: 4rem !important;"></i>
                          <p class="mt-2 text-muted">${escapeHtml(message)}</p>
                        </div>
                      `;
        }
        paginationContainer.innerHTML = '';
      }

      // Get appropriate status class
      function getStatusClass(status) {
        switch (status?.toLowerCase()) {
          case "available":
            return "bg-success";
          case "reserved":
            return "bg-warning text-dark";
          case "unavailable":
            return "bg-danger";
          case "under maintenance":
            return "bg-info text-dark";
          default:
            return "bg-secondary";
        }
      }

      // Set up event listeners
      function setupEventListeners() {
        // Search with debounce
        searchInput.addEventListener("input", function () {
          clearTimeout(searchDebounceTimer);
          searchDebounceTimer = setTimeout(() => {
            currentFilters.search = searchInput.value;
            fetchEquipmentData(1);
          }, 500);
        });

        // Status filter
        statusFilter.addEventListener("change", function () {
          currentFilters.status_id = statusFilter.value;
          fetchEquipmentData(1);
        });

        // Category filter
        categoryFilter.addEventListener("change", function () {
          currentFilters.category_id = categoryFilter.value;
          fetchEquipmentData(1);
        });

        // Layout switch
        layoutSelect.addEventListener("change", function () {
          renderEquipment();
        });
      }

      // Add event listeners to delete buttons
      function addDeleteButtonListeners() {
        document.querySelectorAll(".btn-delete").forEach((button) => {
          button.removeEventListener('click', handleDeleteClick);
          button.addEventListener('click', handleDeleteClick);
        });
      }

      function handleDeleteClick(e) {
        const button = e.currentTarget;
        const equipmentId = button.dataset.id;
        const equipmentName = button.dataset.name;
        showDeleteConfirmationModal(equipmentId, equipmentName);
      }

      function showDeleteConfirmationModal(equipmentId, equipmentName) {
        const modalHtml = `
                      <div class="modal fade" id="deleteEquipmentModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">Confirm Deletion</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                              <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2.5rem;"></i>
                              <p class="mt-3 mb-1">Are you sure you want to delete <strong>"${escapeHtml(equipmentName)}"</strong>?</p>
                              <p class="text-danger mt-1">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="button" class="btn btn-danger" id="confirmDeleteEquipmentBtn">Delete Equipment</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('deleteEquipmentModal'));
        modal.show();

        document.getElementById('confirmDeleteEquipmentBtn').addEventListener('click', async function () {
          try {
            const success = await deleteEquipment(equipmentId);
            if (success) {
              showToast('Equipment deleted successfully!', 'success');
              await fetchEquipmentData(currentPage);
            }
          } catch (error) {
            console.error("Error deleting equipment:", error);
            showToast('Failed to delete equipment: ' + error.message, 'error');
          } finally {
            modal.hide();
            setTimeout(() => {
              document.getElementById('deleteEquipmentModal')?.remove();
            }, 300);
          }
        });

        document.getElementById('deleteEquipmentModal').addEventListener('hidden.bs.modal', function () {
          this.remove();
        });
      }

      // Toast notification function
      window.showToast = function (message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
        toast.style.zIndex = '1100';
        toast.style.bottom = '0';
        toast.style.left = '0';
        toast.style.margin = '1rem';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        toast.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const bgColor = type === 'success' ? '#004183ff' : '#dc3545';
        toast.style.backgroundColor = bgColor;
        toast.style.color = '#fff';
        toast.style.minWidth = '250px';
        toast.style.borderRadius = '0.3rem';

        toast.innerHTML = `
                      <div class="d-flex align-items-center px-3 py-1"> 
                        <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
                        <div class="toast-body flex-grow-1" style="padding: 0.25rem 0;">${escapeHtml(message)}</div>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
                      </div>
                      <div class="loading-bar" style="height: 3px; background: rgba(255,255,255,0.7); width: 100%; transition: width ${duration}ms linear;"></div>
                    `;

        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: false });
        bsToast.show();

        requestAnimationFrame(() => {
          toast.style.opacity = '1';
          toast.style.transform = 'translateY(0)';
        });

        const loadingBar = toast.querySelector('.loading-bar');
        requestAnimationFrame(() => {
          loadingBar.style.width = '0%';
        });

        setTimeout(() => {
          toast.style.opacity = '0';
          toast.style.transform = 'translateY(20px)';
          setTimeout(() => {
            bsToast.hide();
            toast.remove();
          }, 400);
        }, duration);
      };

      // Delete equipment
      async function deleteEquipment(id) {
        try {
          const response = await fetch(`/api/admin/equipment/${id}`, {
            method: "DELETE",
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
              'Content-Type': 'application/json'
            },
          });

          if (response.status === 401) {
            localStorage.removeItem("adminToken");
            showToast("Your session has expired. Please log in again.", "error");
            setTimeout(() => {
              window.location.href = "/admin/login";
            }, 2000);
            return false;
          }

          if (!response.ok) {
            let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
            try {
              const errorData = await response.json();
              errorMessage = errorData.message || errorMessage;
            } catch (parseError) { }
            throw new Error(errorMessage);
          }

          return true;
        } catch (error) {
          console.error('Equipment deletion failed:', error);
          throw error;
        }
      }

      // Update pagination controls
      function updatePagination() {
        paginationContainer.innerHTML = "";

        if (totalPages <= 1) {
          return;
        }

        // Previous button
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
        prevLi.innerHTML = `
                      <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                        <span aria-hidden="true">&laquo;</span>
                        <span class="visually-hidden">Previous</span>
                      </a>
                    `;

        prevLi.addEventListener("click", (e) => {
          e.preventDefault();
          if (currentPage > 1) {
            fetchEquipmentData(currentPage - 1);
          }
        });
        paginationContainer.appendChild(prevLi);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
          const pageLi = document.createElement("li");
          pageLi.className = `page-item ${i === currentPage ? "active" : ""}`;
          pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
          pageLi.addEventListener("click", (e) => {
            e.preventDefault();
            fetchEquipmentData(i);
          });
          paginationContainer.appendChild(pageLi);
        }

        // Next button
        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`;
        nextLi.innerHTML = `
                      <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <span aria-hidden="true">&raquo;</span>
                        <span class="visually-hidden">Next</span>
                      </a>
                    `;
        nextLi.addEventListener("click", (e) => {
          e.preventDefault();
          if (currentPage < totalPages) {
            fetchEquipmentData(currentPage + 1);
          }
        });
        paginationContainer.appendChild(nextLi);
      }

      document.getElementById('selectAllEquipment')?.addEventListener('click', function () {
        const select = document.getElementById('equipmentMultiSelect');
        if (select) {
          Array.from(select.options).forEach(option => {
            option.selected = true;
          });
          updateSelectionSummary();
        }
      });

      document.getElementById('clearAllEquipment')?.addEventListener('click', function () {
        const select = document.getElementById('equipmentMultiSelect');
        if (select) {
          Array.from(select.options).forEach(option => {
            option.selected = false;
          });
          updateSelectionSummary();
        }
      });

      // Mass Assignment Modal functionality
      let equipmentList = [];
      let departmentsList = [];

      async function fetchEquipmentForDropdown() {
        try {
          const select = document.getElementById('equipmentMultiSelect');
          if (!select) return;

          // Show loading state
          select.innerHTML = '<option disabled>Loading equipment...</option>';

          // Fetch ALL equipment only when modal opens
          const response = await fetch("/api/admin/equipment/all", {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          });

          if (!response.ok) {
            throw new Error("Failed to fetch equipment");
          }

          const result = await response.json();

          if (!result.success) {
            throw new Error(result.message || "Failed to fetch equipment");
          }

          equipmentList = result.data || [];
          populateEquipmentMultiSelect();

        } catch (error) {
          console.error("Error loading equipment:", error);
          showToast('Failed to load equipment: ' + error.message, 'error');

          const select = document.getElementById('equipmentMultiSelect');
          if (select) {
            select.innerHTML = '<option disabled>Failed to load equipment. Please try again.</option>';
          }
        }
      }

      async function fetchDepartmentsForModal() {
        try {
          const response = await fetch("/api/departments", {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
          });

          if (!response.ok) throw new Error("Failed to fetch departments");

          const result = await response.json();
          departmentsList = Array.isArray(result) ? result : (result.data || []);
          populateDepartmentSingleSelect();
        } catch (error) {
          console.error("Error fetching departments:", error);
          showToast('Failed to load departments', 'error');
        }
      }

      function populateEquipmentMultiSelect() {
        const select = document.getElementById('equipmentMultiSelect');
        if (!select) return;

        select.innerHTML = '';

        if (equipmentList.length === 0) {
          select.innerHTML = '<option disabled>No equipment found</option>';
          return;
        }

        equipmentList.forEach(equipment => {
          const option = document.createElement('option');
          option.value = equipment.equipment_id;
          option.textContent = equipment.equipment_name;
          select.appendChild(option);
        });
      }

      function populateDepartmentSingleSelect() {
        const select = document.getElementById('departmentSingleSelect');
        if (!select) return;
        select.innerHTML = '<option value="">Select Department</option>';

        departmentsList.forEach(dept => {
          const option = document.createElement('option');
          option.value = dept.department_id;
          option.textContent = dept.department_name;
          select.appendChild(option);
        });
      }

      function updateSelectionSummary() {
        const equipmentSelect = document.getElementById('equipmentMultiSelect');
        const departmentSelect = document.getElementById('departmentSingleSelect');

        const equipmentCount = equipmentSelect ? equipmentSelect.selectedOptions.length : 0;
        const departmentId = departmentSelect ? departmentSelect.value : '';

        const summarySpan = document.getElementById('summaryText');
        if (summarySpan) {
          if (equipmentCount === 0) {
            summarySpan.textContent = 'Please select at least one equipment item';
          } else if (!departmentId) {
            summarySpan.textContent = 'Please select a department to assign';
          } else {
            const departmentName = departmentSelect.options[departmentSelect.selectedIndex]?.text || '';
            summarySpan.textContent = `${equipmentCount} equipment item(s) will be assigned to ${departmentName}. Current department assignments will be replaced.`;
          }
        }
      }
      document.getElementById('executeMassAssignBtn')?.addEventListener('click', function () {
        const equipmentSelect = document.getElementById('equipmentMultiSelect');
        const departmentSelect = document.getElementById('departmentSingleSelect');

        const equipmentIds = equipmentSelect ? Array.from(equipmentSelect.selectedOptions).map(opt => parseInt(opt.value)) : [];
        const departmentId = departmentSelect ? departmentSelect.value : '';

        if (equipmentIds.length === 0) {
          showToast('Please select at least one equipment item', 'error');
          return;
        }

        if (!departmentId) {
          showToast('Please select a department', 'error');
          return;
        }

        window.pendingAssignment = {
          equipmentIds: equipmentIds,
          departmentId: departmentId
        };

        const warningModal = new bootstrap.Modal(document.getElementById('assignmentWarningModal'));
        warningModal.show();
      });

      document.getElementById('confirmAssignBtn')?.addEventListener('click', async function () {
        if (!window.pendingAssignment) return;

        const { equipmentIds, departmentId } = window.pendingAssignment;

        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
          const response = await fetch('/api/admin/equipment/mass-assign-departments', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              equipment_ids: equipmentIds,
              department_id: departmentId  // Changed from department_ids to department_id
            })
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to process mass assignment');
          }

          const result = await response.json();
          showToast(result.message, 'success');

          const warningModal = bootstrap.Modal.getInstance(document.getElementById('assignmentWarningModal'));
          const massModal = bootstrap.Modal.getInstance(document.getElementById('massAssignDepartmentsModal'));

          if (warningModal) warningModal.hide();
          if (massModal) massModal.hide();

          window.pendingAssignment = null;

          await fetchEquipmentData(currentPage);

        } catch (error) {
          console.error('Error in mass assignment:', error);
          showToast('Failed to process mass assignment: ' + error.message, 'error');
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      });

      document.getElementById('equipmentMultiSelect')?.addEventListener('change', updateSelectionSummary);
      document.getElementById('departmentSingleSelect')?.addEventListener('change', updateSelectionSummary);

      const massAssignModal = document.getElementById('massAssignDepartmentsModal');
      if (massAssignModal) {
        massAssignModal.addEventListener('show.bs.modal', function () {
          // This will fetch ALL equipment only when modal opens
          fetchEquipmentForDropdown();  // This now fetches all equipment
          fetchDepartmentsForModal();

          const equipmentSelect = document.getElementById('equipmentMultiSelect');
          const departmentSelect = document.getElementById('departmentSingleSelect');

          if (equipmentSelect) equipmentSelect.selectedIndex = -1;
          if (departmentSelect) departmentSelect.selectedIndex = 0;

          updateSelectionSummary();
        });
      }

      // Fix dropdown placement
      const equipmentDropdownToggle = document.getElementById('equipmentDropdownToggle');
      if (equipmentDropdownToggle) {
        new bootstrap.Dropdown(equipmentDropdownToggle, {
          popperConfig: {
            modifiers: [
              {
                name: 'preventOverflow',
                options: { boundary: 'viewport' }
              },
              {
                name: 'flip',
                options: { fallbackPlacements: ['bottom-start', 'bottom-end', 'top-start', 'top-end'] }
              }
            ]
          }
        });

        equipmentDropdownToggle.addEventListener('show.bs.dropdown', function () {
          const dropdownMenu = document.querySelector('#equipmentDropdownToggle + .dropdown-menu');
          if (dropdownMenu && dropdownMenu.parentElement !== document.body) {
            document.body.appendChild(dropdownMenu);
          }
        });
      }

      // Start the application
      init();
    });
  </script>
@endsection