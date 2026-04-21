<?php
/**
 * Manage Facilities View
 *
 * This view provides a comprehensive interface for administrators to manage facilities.
 * It includes:
 * - Grid/List layout toggle
 * - Search and filter functionality (by status, category)
 * - Pagination (client-side with 12 items per page)
 * - Facility deletion with confirmation modal
 * - Mass department assignment to multiple facilities
 *
 * The JavaScript uses modern async/await patterns and handles all API interactions
 * with proper error handling and user feedback.
 */
?>
@extends('layouts.admin')

@section('title', 'Manage Facilities')

@section('content')
  <style>
    /* Pagination stays at bottom */
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

    /* Fix button group corner rounding */
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
            <select id="layoutSelect" class="form-select">
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

        <!-- Facilities List (scrollable) -->
        <div id="facilitiesContainer">
          <div class="row g-2" id="facilitiesCardsContainer">
            <div class="col-12 text-center py-5" id="loadingIndicator">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="mt-2">Loading facilities...</p>
            </div>
            <div class="col-12 text-center py-5 d-none" id="noResultsMessage">
              <i class="bi bi-exclamation-circle fs-1 text-muted"></i>
              <p class="mt-2 text-muted">No facilities found matching your criteria</p>
            </div>
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
              <h5 class="modal-title" id="massAssignDepartmentsModalLabel">Mass Assign Departments to Facilities</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="massAssignForm">
                <div class="mb-4">
                  <label class="form-label fw-bold" style="color: #003366;">Select Facilities</label>
                  <select id="facilityMultiSelect" class="form-select" multiple size="6"
                    style="border-color: #003366;"></select>
                  <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple facilities</div>
                </div>

                <div class="mb-4">
                  <label class="form-label fw-bold" style="color: #003366;">Select Departments to Assign</label>
                  <select id="departmentMultiSelect" class="form-select" multiple size="6"
                    style="border-color: #003366;"></select>
                  <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple departments</div>
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
                style="background-color: #003366; color: white;">Assign Departments</button>
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
              <p class="text-center mb-0">This action will <strong>replace all existing department assignments</strong>
                for
                the selected facilities with the new departments.</p>
              <p class="text-center text-muted mt-2 mb-0">Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn" id="confirmAssignBtn"
                style="background-color: #003366; color: white;">Yes,
                Assign Departments</button>
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
      // ==================== AUTH & STATE ====================
      const token = localStorage.getItem("adminToken");
      if (!token) return window.location.href = "/admin/login";

      let allFacilities = [];
      let filteredFacilities = [];
      let categories = [];
      let currentPage = 1;
      const itemsPerPage = 12;
      let totalPages = 1;

      // DOM Elements
      const facilitiesContainer = document.getElementById("facilitiesCardsContainer");
      const searchInput = document.getElementById("searchInput");
      const layoutSelect = document.getElementById("layoutSelect");
      const statusFilter = document.getElementById("statusFilter");
      const categoryFilter = document.getElementById("categoryFilter");
      const loadingIndicator = document.getElementById("loadingIndicator");
      const noResultsMessage = document.getElementById("noResultsMessage");
      const paginationContainer = document.getElementById("paginationContainer");

      // ==================== API HELPERS ====================
      const apiFetch = async (url, options = {}) => {
        const response = await fetch(url, {
          ...options,
          headers: {
            'Accept': 'application/json',
            ...(options.headers || {}),
            ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {})
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

      // ==================== DATA FETCHING ====================
      const fetchFacilities = async () => {
        loadingIndicator.classList.remove("d-none");
        noResultsMessage.classList.add("d-none");

        try {
          const response = await apiFetch("/api/facilities");
          // The API returns { data: [...] }
          allFacilities = response.data || [];
          filteredFacilities = [...allFacilities];
          renderFacilities();
        } catch (error) {
          console.error("Error fetching facilities:", error);
          loadingIndicator.classList.add("d-none");
          noResultsMessage.classList.remove("d-none");
          noResultsMessage.innerHTML = `<p class="text-danger">Failed to load facilities. Please try again later.</p>`;
        }
      };

      let statusMap = new Map(); // id -> name
      let categoryMap = new Map(); // id -> name

      const fetchStatuses = async () => {
        try {
          const statuses = await apiFetch("/api/availability-statuses");
          statusFilter.innerHTML = '<option value="all">All Statuses</option>';
          statuses.forEach(status => {
            statusMap.set(status.status_id.toString(), status.status_name);
            statusFilter.innerHTML += `<option value="${status.status_id}">${status.status_name}</option>`;
          });
        } catch (error) {
          console.error("Error fetching statuses:", error);
        }
      };


      const fetchCategories = async () => {
        try {
          const data = await apiFetch("/api/facility-categories", {
            headers: { Authorization: `Bearer ${token}` }
          });
          categoryFilter.innerHTML = '<option value="all">All Categories</option>';
          data.forEach(category => {
            categoryMap.set(category.category_id.toString(), category.category_name);
            categoryFilter.innerHTML += `<option value="${category.category_id}">${category.category_name}</option>`;
          });
        } catch (error) {
          console.error("Error fetching categories:", error);
        }
      };


      // ==================== RENDERING ====================
      const getStatusClass = (status) => {
        const statusMap = {
          'available': 'bg-success',
          'reserved': 'bg-warning text-dark',
          'unavailable': 'bg-danger',
          'under maintenance': 'bg-info text-dark'
        };
        return statusMap[status?.toLowerCase()] || 'bg-secondary';
      };


      const getPrimaryImage = (facility) => {
        if (!facility.images?.length) return "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

        const validImages = facility.images.filter(img => img.image_url?.trim());
        if (!validImages.length) return "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

        // Check for primary image or first image
        const primaryImage = validImages.find(img => img.is_primary === true);
        return primaryImage?.image_url || validImages[0]?.image_url;
      };

      const renderFacilities = () => {
        loadingIndicator.classList.add("d-none");
        facilitiesContainer.innerHTML = "";

        if (!filteredFacilities.length) {
          facilitiesContainer.innerHTML = `<div class="col-12 text-center py-5"><i class="bi bi-tools fs-1 text-muted" style="font-size: 4rem !important;"></i><p class="mt-2 text-muted">No facilities found.</p></div>`;
          paginationContainer.innerHTML = '';
          return;
        }

        totalPages = Math.ceil(filteredFacilities.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredFacilities.length);
        const layout = layoutSelect.value;

        facilitiesContainer.className = layout === "list" ? "row g-3" : "row g-2";

        for (let i = startIndex; i < endIndex; i++) {
          const facility = filteredFacilities[i];
          // Safely access nested properties with optional chaining and fallbacks
          const statusName = facility.status_name || 'Unknown';
          const statusClass = getStatusClass(statusName);
          const primaryImage = getPrimaryImage(facility);
          const categoryName = facility.category_name || 'Uncategorized';
          const subcategoryName = facility.subcategory_name || 'Uncategorized';

          const card = document.createElement("div");

          if (layout === "list") {
            card.className = "col-12 facilities-card mb-0";
            card.innerHTML = `
                <div class="card h-100 shadow-sm rounded-3">
                  <div class="row g-0">
                    <div class="col-md-2" style="max-width: 120px; flex: 0 0 120px;">
                      <img src="${primaryImage}" class="img-fluid rounded-start" style="width: 120px; height: 120px; object-fit: cover;" alt="${facility.facility_name}">
                    </div>
                    <div class="col-md-8">
                      <div class="card-body py-3">
                        <h5 class="card-title fw-bold mb-2">${escapeHtml(facility.facility_name)}</h5>
                        <p class="card-text mb-2">
                          <span class="badge ${statusClass} me-2">${escapeHtml(statusName)}</span>
                          <small class="text-muted">
                            <i class="bi bi-tag-fill text-secondary me-1"></i>${escapeHtml(categoryName)}
                            <i class="bi bi-tag-fill text-secondary ms-2 me-1"></i>${escapeHtml(subcategoryName)}
                          </small>
                        </p>
                        <p class="card-text text-muted mb-0">${escapeHtml(facility.description || "No description available")}</p>
                      </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                      <div class="d-grid gap-2 w-100 px-2">
                        <a href="/admin/edit-facility?id=${facility.facility_id}" class="btn btn-sm btn-primary">Manage</a>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${facility.facility_id}">Delete</button>
                      </div>
                    </div>
                  </div>
                </div>
              `;
          } else {
            card.className = "col-md-4 col-lg-3 facilities-card mb-3";
            card.innerHTML = `
                <div class="card h-100">
                  <img src="${primaryImage}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${escapeHtml(facility.facility_name)}">
                  <div class="card-body d-flex flex-column p-2">
                    <div>
                      <h6 class="card-title mb-1 fw-bold text-truncate" style="max-width: 250px;" data-bs-toggle="tooltip" title="${escapeHtml(facility.facility_name)}">${escapeHtml(facility.facility_name)}</h6>
                      <p class="card-text text-muted mb-1 small text-truncate">
                        <i class="bi bi-tag-fill text-secondary me-1"></i>${escapeHtml(categoryName)} |
                        <i class="fa fa-layer-group text-secondary ms-1 me-1"></i>${escapeHtml(subcategoryName)}
                      </p>
                      <span class="badge ${statusClass} mb-2">${escapeHtml(statusName)}</span>
                      <p class="card-text mb-2 small text-truncate">${escapeHtml(facility.description || "No description available")}</p>
                    </div>
                    <div class="facilities-actions mt-auto d-grid gap-1">
                      <a href="/admin/edit-facility?id=${facility.facility_id}" class="btn btn-sm btn-primary">Manage</a>
                      <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${facility.facility_id}">Delete</button>
                    </div>
                  </div>
                </div>
              `;
          }

          facilitiesContainer.appendChild(card);
        }

        // Attach delete handlers
        document.querySelectorAll(".btn-delete").forEach(btn => {
          btn.addEventListener("click", () => showDeleteConfirmation(btn.dataset.id));
        });

        updatePagination();
      };

      const escapeHtml = (str) => {
        if (!str) return '';
        return str
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      };


      // ==================== FILTERING ====================
      const filterFacilities = () => {
        const searchTerm = searchInput.value.toLowerCase();
        const statusId = statusFilter.value;
        const categoryId = categoryFilter.value;

        // Get the status name from the map if not "all"
        const selectedStatusName = statusId !== "all" ? statusMap.get(statusId) : null;
        // Get the category name from the map if not "all"
        const selectedCategoryName = categoryId !== "all" ? categoryMap.get(categoryId) : null;

        filteredFacilities = allFacilities.filter(facility => {
          const matchesSearch = facility.facility_name?.toLowerCase().includes(searchTerm) || false;
          const matchesStatus = statusId === "all" || facility.status_name === selectedStatusName;
          const matchesCategory = categoryId === "all" || facility.category_name === selectedCategoryName;
          return matchesSearch && matchesStatus && matchesCategory;
        });

        currentPage = 1;
        renderFacilities();
      };
      // ==================== PAGINATION ====================
      const updatePagination = () => {
        const total = Math.ceil(filteredFacilities.length / itemsPerPage);
        if (total <= 1) {
          paginationContainer.innerHTML = '';
          return;
        }

        paginationContainer.innerHTML = `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}" id="prevPage">
                  <a class="page-link" href="#" aria-disabled="true"><span aria-hidden="true">&laquo;</span><span class="visually-hidden">Previous</span></a>
                </li>
                ${generatePageNumbers(total)}
                <li class="page-item ${currentPage === total ? 'disabled' : ''}" id="nextPage">
                  <a class="page-link" href="#" data-page="${currentPage + 1}"><span aria-hidden="true">&raquo;</span><span class="visually-hidden">Next</span></a>
                </li>
              `;

        // Attach pagination events
        document.querySelectorAll('.page-link[data-page]').forEach(link => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (page >= 1 && page <= total) {
              currentPage = page;
              renderFacilities();
            }
          });
        });

        document.getElementById('prevPage')?.addEventListener('click', (e) => {
          e.preventDefault();
          if (currentPage > 1) {
            currentPage--;
            renderFacilities();
          }
        });

        document.getElementById('nextPage')?.addEventListener('click', (e) => {
          e.preventDefault();
          if (currentPage < total) {
            currentPage++;
            renderFacilities();
          }
        });
      };

      const generatePageNumbers = (total) => {
        let pages = '';
        const maxVisible = 5;
        let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let end = Math.min(total, start + maxVisible - 1);
        if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);

        for (let i = start; i <= end; i++) {
          pages += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        return pages;
      };

      // ==================== DELETE FUNCTIONALITY ====================
      let currentDeleteId = null;

      const showDeleteConfirmation = (facilityId) => {
        currentDeleteId = facilityId;
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        modal.show();
      };

      const handleDeleteFacility = async () => {
        if (!currentDeleteId) return;

        try {
          await apiFetch(`/api/admin/facilities/${currentDeleteId}`, {
            method: "DELETE",
            headers: { Authorization: `Bearer ${token}` }
          });

          showToast("Facility deleted successfully!", 'success');
          await fetchFacilities();
        } catch (error) {
          console.error("Error deleting facility:", error);
          showToast("Failed to delete facility", 'error');
        } finally {
          currentDeleteId = null;
          // Close modal after deletion
          const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
          if (modal) modal.hide();
        }
      };

      document.getElementById("confirmDeleteBtn")?.addEventListener("click", async () => {
        await handleDeleteFacility();
        bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'))?.hide();
      });

      // ==================== MASS ASSIGNMENT ====================
      let facilitiesList = [];
      let departmentsList = [];

      const fetchFacilitiesForDropdown = async () => {
        try {
          const result = await apiFetch("/api/facilities/dropdown");
          facilitiesList = result.data || [];
          const select = document.getElementById('facilityMultiSelect');
          if (select) select.innerHTML = facilitiesList.map(f => `<option value="${f.facility_id}">${f.facility_name}</option>`).join('');
        } catch (error) {
          console.error("Error fetching facilities:", error);
          showToast('Failed to load facilities', 'error');
        }
      };

      const fetchDepartmentsForModal = async () => {
        try {
          const result = await apiFetch("/api/departments");
          departmentsList = Array.isArray(result) ? result : (result.data || []);
          const select = document.getElementById('departmentMultiSelect');
          if (select) select.innerHTML = departmentsList.map(d => `<option value="${d.department_id}">${d.department_name}</option>`).join('');
        } catch (error) {
          console.error("Error fetching departments:", error);
          showToast('Failed to load departments', 'error');
        }
      };

      const updateSelectionSummary = () => {
        const facilityCount = document.getElementById('facilityMultiSelect')?.selectedOptions.length || 0;
        const departmentCount = document.getElementById('departmentMultiSelect')?.selectedOptions.length || 0;
        const summarySpan = document.getElementById('summaryText');
        if (summarySpan) {
          if (facilityCount === 0 && departmentCount === 0) summarySpan.textContent = 'No facilities or departments selected';
          else if (facilityCount === 0) summarySpan.textContent = 'Please select at least one facility';
          else if (departmentCount === 0) summarySpan.textContent = 'Please select at least one department';
          else summarySpan.textContent = `${facilityCount} facility/facilities and ${departmentCount} department(s) selected. Current department assignments will be replaced.`;
        }
      };

      let pendingAssignment = null;

      document.getElementById('executeMassAssignBtn')?.addEventListener('click', () => {
        const facilityIds = Array.from(document.getElementById('facilityMultiSelect')?.selectedOptions || []).map(opt => parseInt(opt.value));
        const departmentIds = Array.from(document.getElementById('departmentMultiSelect')?.selectedOptions || []).map(opt => parseInt(opt.value));

        if (!facilityIds.length) return showToast('Please select at least one facility', 'error');
        if (!departmentIds.length) return showToast('Please select at least one department', 'error');

        pendingAssignment = { facilityIds, departmentIds };
        new bootstrap.Modal(document.getElementById('assignmentWarningModal')).show();
      });

      document.getElementById('confirmAssignBtn')?.addEventListener('click', async () => {
        if (!pendingAssignment) return;

        const btn = document.getElementById('confirmAssignBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
          await apiFetch('/api/admin/facilities/mass-assign-departments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              facility_ids: pendingAssignment.facilityIds,
              department_ids: pendingAssignment.departmentIds,
              action: 'replace'
            })
          });

          showToast('Departments assigned successfully!', 'success');
          bootstrap.Modal.getInstance(document.getElementById('assignmentWarningModal'))?.hide();
          bootstrap.Modal.getInstance(document.getElementById('massAssignDepartmentsModal'))?.hide();
          await fetchFacilities();
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
      document.getElementById('departmentMultiSelect')?.addEventListener('change', updateSelectionSummary);

      const massAssignModal = document.getElementById('massAssignDepartmentsModal');
      massAssignModal?.addEventListener('show.bs.modal', async () => {
        await Promise.all([fetchFacilitiesForDropdown(), fetchDepartmentsForModal()]);
        if (document.getElementById('facilityMultiSelect')) document.getElementById('facilityMultiSelect').selectedIndex = -1;
        if (document.getElementById('departmentMultiSelect')) document.getElementById('departmentMultiSelect').selectedIndex = -1;
        updateSelectionSummary();
      });

      // ==================== EVENT LISTENERS ====================
      const setupEventListeners = () => {
        searchInput.addEventListener('input', filterFacilities);
        layoutSelect.addEventListener('change', () => renderFacilities());
        statusFilter.addEventListener('change', filterFacilities);
        categoryFilter.addEventListener('change', filterFacilities);
      };

      // ==================== INITIALIZATION ====================
      const init = async () => {
        await Promise.all([fetchFacilities(), fetchStatuses(), fetchCategories()]);
        setupEventListeners();
      };

      init();
    });
  </script>
@endsection