<?php
/**
 * Manage Facilities View
 *
 * Uses a single merged API endpoint: GET /api/admin/manage-facilities
 * That one call returns paginated facilities AND all filter-dropdown metadata
 * (statuses, categories, parent buildings) — replacing the previous 3 separate
 * bootstrap calls.
 *
 * Server-side pagination: 12 items per page (handled by the API).
 * Filtering (status, category, building) and search are sent as query
 * parameters so the database does the work instead of the browser.
 *
 * The mass-assign-departments API call is intentionally kept separate and
 * is NOT merged into the above endpoint.
 */
?>
@extends('layouts.admin')

@section('title', 'Manage Facilities')

@section('content')
  <style>
    .layout-select {
      min-width: 180px;
    }

    /* Loading overlay styles */
    .facilities-container-loading {
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

    .d-flex.justify-content-center.mt-auto.pt-3 {
      flex-shrink: 0;
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

    #facilitiesContainer {
      flex: 1;
      overflow-y: auto;
      min-height: 500px;
      padding-right: 8px;
    }

    #facilitiesContainer::-webkit-scrollbar {
      width: 6px;
    }

    #facilitiesContainer::-webkit-scrollbar-track {
      background: #f1f1f1;
    }

    #facilitiesContainer::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 3px;
    }

    #facilitiesContainer::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    #facilitiesContainer {
      scrollbar-width: thin;
      scrollbar-color: #888 #f1f1f1;
    }

    .btn-group>.btn:first-child {
      border-top-right-radius: 0 !important;
      border-bottom-right-radius: 0 !important;
    }

    .btn-group>.dropdown-toggle-split {
      border-top-left-radius: 0 !important;
      border-bottom-left-radius: 0 !important;
    }
  </style>

  <main id="main">
    <div class="container-fluid px-4">
      <!-- Header & Controls -->
      <div>
        <div class="row mb-3 g-2 align-items-center filters-row">
          <div class="col-auto flex-shrink-0">
            <select id="layoutSelect" class="form-select layout-select">
              <option value="grid">Grid Layout</option>
              <option value="list">List Layout</option>
            </select>
          </div>

          <div class="col-auto flex-shrink-0">
            <select id="statusFilter" class="form-select">
              <option value="">All Statuses</option>
            </select>
          </div>

          <div class="col-auto flex-shrink-0">
            <select id="categoryFilter" class="form-select">
              <option value="">All Categories</option>
            </select>
          </div>

          <!-- Filter by Building (parent_facility_id = null → parent facility) -->
          <div class="col-auto flex-shrink-0">
            <select id="buildingFilter" class="form-select">
              <option value="">All Rooms</option>
            </select>
          </div>

          <div class="col flex-grow-1">
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="searchInput" class="form-control" placeholder="Search Facilities...">
            </div>
          </div>

          <div class="col-auto text-nowrap">
            <div class="btn-group" role="group">
              <a href="{{ url('/admin/add-facility') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle-fill me-2"></i>Add New
              </a>
              <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
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

        <!-- Facilities Grid/List (scrollable) -->
        <div id="facilitiesContainer">
          <div class="row g-2" id="facilitiesCardsContainer">
            <!-- Loading skeletons will be shown here -->
          </div>
        </div>

        <!-- Pagination Controls -->
        <div class="d-flex justify-content-center mt-auto pt-3">
          <nav aria-label="Facilities pagination">
            <ul class="pagination" id="paginationContainer"></ul>
          </nav>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              Are you sure you want to delete this facility? This action cannot be undone.
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Facility</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mass Assign Departments Modal -->
      <div class="modal fade" id="massAssignDepartmentsModal" tabindex="-1"
        aria-labelledby="massAssignDepartmentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="massAssignDepartmentsModalLabel">Mass Assign Department to Facilities</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="massAssignForm">
                <div class="mb-4">
                  <div class="mb-2 d-flex justify-content-between align-items-center">
                    <label class="form-label fw-bold mb-0" style="color: #003366;">Select Facilities</label>
                    <div>
                      <button type="button" id="selectAllFacilities" class="btn btn-sm btn-primary me-2"
                        style="background-color: #003366; color: white;">
                        <i class="bi bi-check-all"></i> Select All
                      </button>
                      <button type="button" id="clearAllFacilities" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear All
                      </button>
                    </div>
                  </div>
                  <select id="facilityMultiSelect" class="form-select" multiple size="8"
                    style="border-color: #003366;"></select>
                  <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple facilities</div>
                </div>
                <div class="mb-4">
                  <label class="form-label fw-bold" style="color: #003366;">Select Department to Assign</label>
                  <select id="departmentSingleSelect" class="form-select" style="border-color: #003366;">
                    <option value="">Select a department...</option>
                  </select>
                  <div class="form-text text-muted">This will update the managed_by field for all selected facilities
                  </div>
                </div>
                <div class="alert" style="background-color: #f8f9fa; border-left: 4px solid #003366;"
                  id="selectionSummary">
                  <i class="bi bi-info-circle me-2" style="color: #003366;"></i>
                  <span id="summaryText">No facilities selected</span>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn" id="executeMassAssignBtn"
                style="background-color: #003366; color: white;">Assign Department</button>
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
              <p class="text-center mb-0">This action will <strong>replace the managed_by field</strong> for all selected
                facilities with the selected department.</p>
              <p class="text-center text-muted mt-2 mb-0">Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn" id="confirmAssignBtn"
                style="background-color: #003366; color: white;">Yes, Assign Department</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
@endsection

@section('scripts')
  <script src="{{ asset('js/admin/toast.js') }}"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {

      // ==================== AUTH ====================
      const token = localStorage.getItem("adminToken");
      if (!token) return window.location.href = "/admin/login";

      // ==================== STATE ====================
      // currentPage is the server-side page — pagination is done by the API.
      let currentPage = 1;

      let totalPages = 1;
      let searchTimer = null; // debounce handle

      // DOM references
      const facilitiesContainer = document.getElementById("facilitiesCardsContainer");
      const searchInput = document.getElementById("searchInput");
      const layoutSelect = document.getElementById("layoutSelect");
      const statusFilter = document.getElementById("statusFilter");
      const categoryFilter = document.getElementById("categoryFilter");
      const buildingFilter = document.getElementById("buildingFilter");
      const paginationContainer = document.getElementById("paginationContainer");

      // ==================== API HELPERS ====================
      const apiFetch = async (url, options = {}) => {
        const response = await fetch(url, {
          ...options,
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...(options.headers || {}),
            ...(options.body && !(options.body instanceof FormData)
              ? { 'Content-Type': 'application/json' } : {})
          }
        });

        if (response.status === 401) {
          localStorage.removeItem("adminToken");
          alert("Your session has expired. Please log in again.");
          window.location.href = "/admin/login";
          throw new Error("Unauthorized");
        }

        if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        return response.json();
      };

      /**
       * Build the query string from current filter state.
       * Empty / default values are omitted so the API treats them as "no filter".
       */
      const buildQuery = (page = currentPage) => {
        const params = new URLSearchParams();
        params.set('page', page);

        const status = statusFilter.value;
        const category = categoryFilter.value;
        const building = buildingFilter.value;
        const search = searchInput.value.trim();

        if (status) params.set('status_id', status);
        if (category) params.set('category_id', category);
        if (building) params.set('parent_id', building);
        if (search) params.set('search', search);

        return params.toString();
      };

      // ==================== MERGED DATA FETCH ====================
      /**
       * Single API call that returns:
       *  - paginated facilities (data + pagination meta)
       *  - filter dropdown data (filters.statuses, filters.categories, filters.parent_buildings)
       *
       * populateFilters controls whether the dropdown <option> elements are
       * rebuilt from the response metadata. We only need to do this once on
       * the initial load — subsequent filter/page changes skip it to avoid
       * resetting the user's selections.
       */
      const fetchFacilities = async (populateFilters = false) => {
        const container = document.getElementById('facilitiesContainer');
        if (!container) return;

        // Show loading overlay
        container.classList.add('facilities-container-loading');

        // Add loading overlay if not exists
        let overlay = container.querySelector('.loading-overlay');
        if (!overlay) {
          overlay = document.createElement('div');
          overlay.className = 'loading-overlay';
          overlay.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading facilities...</p>
                </div>
            `;
          container.appendChild(overlay);
        } else {
          overlay.style.display = 'flex';
        }

        // Disable pagination while loading
        const pagination = document.getElementById('paginationContainer');
        if (pagination) {
          pagination.classList.add('disabled-pagination');
        }

        // Clear existing content
        const facilitiesContainer = document.getElementById("facilitiesCardsContainer");
        if (facilitiesContainer) {
          facilitiesContainer.innerHTML = '';
        }

        try {
          const query = buildQuery();
          const response = await apiFetch(`/api/admin/manage-facilities?${query}`);

          // Populate filter dropdowns (first load only)
          if (populateFilters && response.filters) {
            populateStatusFilter(response.filters.statuses || []);
            populateCategoryFilter(response.filters.categories || []);
            populateBuildingFilter(response.filters.parent_buildings || []);
          }

          // Render facilities
          const facilities = response.data || [];
          const pagination = response.pagination || {};

          totalPages = pagination.last_page || 1;

          if (!facilities.length) {
            if (facilitiesContainer) {
              facilitiesContainer.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-exclamation-circle fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">No facilities found matching your criteria</p>
                        </div>
                    `;
            }
            paginationContainer.innerHTML = '';
          } else {
            renderFacilities(facilities);
            updatePagination(pagination);
          }

        } catch (error) {
          console.error("Error fetching facilities:", error);
          if (facilitiesContainer) {
            facilitiesContainer.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-exclamation-triangle-fill fs-1 text-danger"></i>
                        <p class="mt-2 text-danger">Failed to load facilities. Please try again later.</p>
                    </div>
                `;
          }
        } finally {
          // Hide loading overlay
          if (container) {
            container.classList.remove('facilities-container-loading');
            if (overlay) {
              overlay.style.display = 'none';
            }
          }

          // Re-enable pagination
          if (pagination) {
            pagination.classList.remove('disabled-pagination');
          }
        }
      };

      // ==================== FILTER POPULATION ====================
      const populateStatusFilter = (statuses) => {
        statusFilter.innerHTML = '<option value="">All Statuses</option>';
        statuses.forEach(s => {
          statusFilter.innerHTML +=
            `<option value="${s.status_id}">${escapeHtml(s.status_name)}</option>`;
        });
      };

      const populateCategoryFilter = (categories) => {
        categoryFilter.innerHTML = '<option value="">All Categories</option>';
        categories.forEach(c => {
          categoryFilter.innerHTML +=
            `<option value="${c.category_id}">${escapeHtml(c.category_name)}</option>`;
        });
      };

      /**
       * Builds the "Filter by Building" dropdown from parent facilities
       * (facilities where parent_facility_id IS NULL, as identified by the API).
       * A special "Top-level only" option (value="0") is also injected so the
       * admin can view only buildings without their children.
       */
      const populateBuildingFilter = (buildings) => {
        buildingFilter.innerHTML = '<option value="">All Rooms</option>';
        if (buildings.length) {
          buildingFilter.innerHTML +=
            `<option value="0">— Top-level Facilities Only —</option>`;
          buildings.forEach(b => {
            buildingFilter.innerHTML +=
              `<option value="${b.facility_id}">${escapeHtml(b.facility_name)}</option>`;
          });
        }
      };

      // ==================== RENDERING ====================
      const getStatusClass = (status) => {
        const map = {
          'available': 'bg-success',
          'reserved': 'bg-warning text-dark',
          'unavailable': 'bg-danger',
          'under maintenance': 'bg-info text-dark',
        };
        return map[status?.toLowerCase()] || 'bg-secondary';
      };

      const getPrimaryImage = (facility) => {
        const fallback = "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";
        if (!facility.images?.length) return fallback;
        const valid = facility.images.filter(img => img.image_url?.trim());
        if (!valid.length) return fallback;
        return valid.find(img => img.is_primary)?.image_url || valid[0].image_url;
      };

      const renderFacilities = (facilities) => {
        facilitiesContainer.innerHTML = "";
        const layout = layoutSelect.value;
        facilitiesContainer.className = layout === "list" ? "row g-3" : "row g-2";

        facilities.forEach(facility => {
          const statusName = facility.status_name || 'Unknown';
          const statusClass = getStatusClass(statusName);
          const primaryImage = getPrimaryImage(facility);
          const categoryName = facility.category_name || 'Uncategorized';
          const subcategory = facility.subcategory_name || 'Uncategorized';

          const card = document.createElement("div");

          if (layout === "list") {
            card.className = "col-12 facilities-card mb-0";
            card.innerHTML = `
                      <div class="card h-100 shadow-sm rounded-3">
                        <div class="row g-0">
                          <div class="col-md-2" style="max-width:120px;flex:0 0 120px;">
                            <img src="${primaryImage}" class="img-fluid rounded-start"
                              style="width:120px;height:120px;object-fit:cover;"
                              alt="${escapeHtml(facility.facility_name)}">
                          </div>
                          <div class="col-md-8">
                            <div class="card-body py-3">
                              <h5 class="card-title fw-bold mb-2">${escapeHtml(facility.facility_name)}</h5>
                              <p class="card-text mb-2">
                                <span class="badge ${statusClass} me-2">${escapeHtml(statusName)}</span>
                                <small class="text-muted">
                                  <i class="bi bi-tag-fill text-secondary me-1"></i>${escapeHtml(categoryName)}
                                  <i class="bi bi-tag-fill text-secondary ms-2 me-1"></i>${escapeHtml(subcategory)}
                                </small>
                              </p>
                              <p class="card-text text-muted mb-0">
                                ${escapeHtml(facility.description || "No description available")}
                              </p>
                            </div>
                          </div>
                          <div class="col-md-2 d-flex align-items-center justify-content-center">
                            <div class="d-grid gap-2 w-100 px-2">
                              <a href="/admin/edit-facility?id=${facility.facility_id}"
                                class="btn btn-sm btn-primary">Manage</a>
                              <button class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="${facility.facility_id}">Delete</button>
                            </div>
                          </div>
                        </div>
                      </div>`;
          } else {
            card.className = "col-md-4 col-lg-3 facilities-card mb-3";
            card.innerHTML = `
                      <div class="card h-100">
                        <img src="${primaryImage}" class="card-img-top"
                          style="height:150px;object-fit:cover;"
                          alt="${escapeHtml(facility.facility_name)}">
                        <div class="card-body d-flex flex-column p-2">
                          <div>
                            <h6 class="card-title mb-1 fw-bold text-truncate" style="max-width:250px;"
                              data-bs-toggle="tooltip" title="${escapeHtml(facility.facility_name)}">
                              ${escapeHtml(facility.facility_name)}
                            </h6>
                            <p class="card-text text-muted mb-1 small text-truncate">
                              <i class="bi bi-tag-fill text-secondary me-1"></i>${escapeHtml(categoryName)} |
                              <i class="fa fa-layer-group text-secondary ms-1 me-1"></i>${escapeHtml(subcategory)}
                            </p>
                            <span class="badge ${statusClass} mb-2">${escapeHtml(statusName)}</span>
                            <p class="card-text mb-2 small text-truncate">
                              ${escapeHtml(facility.description || "No description available")}
                            </p>
                          </div>
                          <div class="facilities-actions mt-auto d-grid gap-1">
                            <a href="/admin/edit-facility?id=${facility.facility_id}"
                              class="btn btn-sm btn-primary">Manage</a>
                            <button class="btn btn-sm btn-outline-danger btn-delete"
                              data-id="${facility.facility_id}">Delete</button>
                          </div>
                        </div>
                      </div>`;
          }

          facilitiesContainer.appendChild(card);
        });

        // Attach delete handlers
        document.querySelectorAll(".btn-delete").forEach(btn => {
          btn.addEventListener("click", () => showDeleteConfirmation(btn.dataset.id));
        });
      };

      const escapeHtml = (str) => {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      };

      // ==================== PAGINATION ====================
      /**
       * Pagination is now server-side. The API returns pagination meta;
       * clicking a page link triggers a new API call with that page number.
       */
      const updatePagination = (pagination) => {
        const { current_page, last_page, total, from, to } = pagination;

        if (last_page <= 1) {
          paginationContainer.innerHTML = '';
          return;
        }

        const maxVisible = 5;
        let start = Math.max(1, current_page - Math.floor(maxVisible / 2));
        let end = Math.min(last_page, start + maxVisible - 1);
        if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);

        let pages = '';
        for (let i = start; i <= end; i++) {
          pages += `<li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }

        paginationContainer.innerHTML = `
                  <li class="page-item ${current_page === 1 ? 'disabled' : ''}" id="prevPage">
                    <a class="page-link" href="#" data-page="${current_page - 1}">
                      <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>
                  ${pages}
                  <li class="page-item ${current_page === last_page ? 'disabled' : ''}" id="nextPage">
                    <a class="page-link" href="#" data-page="${current_page + 1}">
                      <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>`;

        // Wire up page-link clicks
        paginationContainer.querySelectorAll('.page-link[data-page]').forEach(link => {
          link.addEventListener('click', async (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (page >= 1 && page <= last_page && page !== current_page) {
              currentPage = page;
              await fetchFacilities();
            }
          });
        });
      };

      // ==================== FILTERING ====================
      /**
       * Any filter change resets to page 1 and re-fetches from the server.
       * Search is debounced 350 ms to avoid hammering the API on every keystroke.
       */
      const onFilterChange = () => {
        currentPage = 1;
        fetchFacilities();
      };

      const onSearchInput = () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          currentPage = 1;
          fetchFacilities();
        }, 350);
      };

      // ==================== DELETE ====================
      let currentDeleteId = null;

      const showDeleteConfirmation = (facilityId) => {
        currentDeleteId = facilityId;
        new bootstrap.Modal(document.getElementById('deleteConfirmationModal')).show();
      };

      const handleDeleteFacility = async () => {
        if (!currentDeleteId) return;
        try {
          await apiFetch(`/api/admin/facilities/${currentDeleteId}`, { method: "DELETE" });
          showToast("Facility deleted successfully!", 'success');
          // Stay on current page; if the page becomes empty the API will
          // return 0 results and noResultsMessage will appear.
          await fetchFacilities();
        } catch (error) {
          console.error("Error deleting facility:", error);
          showToast("Failed to delete facility", 'error');
        } finally {
          currentDeleteId = null;
          bootstrap.Modal.getInstance(
            document.getElementById('deleteConfirmationModal')
          )?.hide();
        }
      };

      document.getElementById("confirmDeleteBtn")?.addEventListener("click", async () => {
        await handleDeleteFacility();
      });

      // ==================== MASS ASSIGNMENT (update managed_by field) ====================
      let facilitiesList = [];

      const fetchFacilitiesForDropdown = async () => {
        try {
          const result = await apiFetch("/api/facilities/dropdown");
          facilitiesList = result.data || [];
          const select = document.getElementById('facilityMultiSelect');
          if (select) {
            select.innerHTML = facilitiesList
              .map(f => `<option value="${f.facility_id}">${escapeHtml(f.facility_name)}</option>`)
              .join('');
          }
        } catch (error) {
          console.error("Error fetching facilities for dropdown:", error);
          showToast('Failed to load facilities', 'error');
        }
      };

      const fetchDepartmentsForModal = async () => {
        try {
          const result = await apiFetch("/api/departments");
          const departments = Array.isArray(result) ? result : (result.data || []);
          const select = document.getElementById('departmentSingleSelect');
          if (select) {
            select.innerHTML = '<option value="">Select a department...</option>' +
              departments.map(d => `<option value="${d.department_id}">${escapeHtml(d.department_name)}</option>`)
                .join('');
          }
        } catch (error) {
          console.error("Error fetching departments:", error);
          showToast('Failed to load departments', 'error');
        }
      };

      const updateSelectionSummary = () => {
        const facilityCount = document.getElementById('facilityMultiSelect')?.selectedOptions.length || 0;
        const departmentId = document.getElementById('departmentSingleSelect')?.value;
        const summarySpan = document.getElementById('summaryText');
        const departmentSelect = document.getElementById('departmentSingleSelect');
        const selectedDeptText = departmentSelect?.options[departmentSelect.selectedIndex]?.text || '';

        if (!summarySpan) return;

        if (facilityCount === 0) {
          summarySpan.textContent = 'Please select at least one facility';
        } else if (!departmentId) {
          summarySpan.textContent = `${facilityCount} facility/facilities selected. Please select a department.`;
        } else {
          summarySpan.textContent =
            `${facilityCount} facility/facilities will be assigned to department: "${selectedDeptText}". ` +
            `The managed_by field will be updated for all selected facilities.`;
        }
      };

      let pendingAssignment = null;

      document.getElementById('executeMassAssignBtn')?.addEventListener('click', () => {
        const facilityIds = Array.from(
          document.getElementById('facilityMultiSelect')?.selectedOptions || []
        ).map(opt => parseInt(opt.value));
        const departmentId = document.getElementById('departmentSingleSelect')?.value;

        if (!facilityIds.length) return showToast('Please select at least one facility', 'error');
        if (!departmentId) return showToast('Please select a department', 'error');

        const departmentSelect = document.getElementById('departmentSingleSelect');
        const departmentName = departmentSelect?.options[departmentSelect.selectedIndex]?.text || '';

        pendingAssignment = { facilityIds, departmentId: parseInt(departmentId), departmentName };
        new bootstrap.Modal(document.getElementById('assignmentWarningModal')).show();
      });

      document.getElementById('confirmAssignBtn')?.addEventListener('click', async () => {
        if (!pendingAssignment) return;
        const btn = document.getElementById('confirmAssignBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
          // API call to update managed_by field for selected facilities
          await apiFetch('/api/admin/facilities/mass-assign-department', {
            method: 'POST',
            body: JSON.stringify({
              facility_ids: pendingAssignment.facilityIds,
              department_id: pendingAssignment.departmentId,
            }),
          });

          showToast(`Successfully assigned ${pendingAssignment.facilityIds.length} facility/facilities to department: ${pendingAssignment.departmentName}`, 'success');
          bootstrap.Modal.getInstance(document.getElementById('assignmentWarningModal'))?.hide();
          bootstrap.Modal.getInstance(document.getElementById('massAssignDepartmentsModal'))?.hide();
          await fetchFacilities(); // Refresh the facilities list
        } catch (error) {
          console.error('Error in mass assignment:', error);
          showToast('Failed to process mass assignment: ' + error.message, 'error');
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalText;
          pendingAssignment = null;
        }
      });

      document.getElementById('facilityMultiSelect')?.addEventListener('change', updateSelectionSummary);
      document.getElementById('departmentSingleSelect')?.addEventListener('change', updateSelectionSummary);

      document.getElementById('massAssignDepartmentsModal')?.addEventListener('show.bs.modal', async () => {
        await Promise.all([fetchFacilitiesForDropdown(), fetchDepartmentsForModal()]);
        document.getElementById('facilityMultiSelect').selectedIndex = -1;
        document.getElementById('departmentSingleSelect').value = '';
        updateSelectionSummary();
      });

      // Select All Facilities button
      document.getElementById('selectAllFacilities')?.addEventListener('click', function () {
        const select = document.getElementById('facilityMultiSelect');
        if (select) {
          Array.from(select.options).forEach(option => {
            option.selected = true;
          });
          updateSelectionSummary();
          showToast(`${select.options.length} facilities selected`, 'success');
        }
      });

      // Clear All Facilities button
      document.getElementById('clearAllFacilities')?.addEventListener('click', function () {
        const select = document.getElementById('facilityMultiSelect');
        if (select) {
          Array.from(select.options).forEach(option => {
            option.selected = false;
          });
          updateSelectionSummary();
        }
      });

      // ==================== EVENT LISTENERS ====================
      searchInput.addEventListener('input', onSearchInput);
      statusFilter.addEventListener('change', onFilterChange);
      categoryFilter.addEventListener('change', onFilterChange);
      buildingFilter.addEventListener('change', onFilterChange);
      layoutSelect.addEventListener('change', () => fetchFacilities());

      // ==================== INIT ====================
      // Single API call: fetches page 1 facilities AND populates all filter dropdowns.
      fetchFacilities(/* populateFilters = */ true);
    });
  </script>
@endsection