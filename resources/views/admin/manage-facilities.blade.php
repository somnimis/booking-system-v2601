@extends('layouts.admin')

@section('title', 'Manage Facilities')

@section('content')

  <style>
    .btn-outline-danger {
      background-color: #ffe5e5;
      /* light red */
      border-color: #dc3545;
      /* default Bootstrap danger border */
      color: #dc3545;
    }

    .btn-outline-danger:hover {
      background-color: #dc3545;
      color: #fff;
    }

    /* Custom pagination colors using CPU theme */
    .pagination .page-link {
      color: var(--cpu-primary);
      /* dark blue text */
    }

    .pagination .page-link:hover {
      color: var(--cpu-primary-hover);
      /* hover text color */
    }

    /* Active page */
    .pagination .page-item.active .page-link {
      background-color: var(--cpu-primary);
      border-color: var(--cpu-primary);
      color: #fff;
      /* white text for contrast */
    }

    /* Disabled state */
    .pagination .page-item.disabled .page-link {
      color: #6c757d;
      /* gray */
      pointer-events: none;
      background-color: var(--light-gray);
      border-color: #dee2e6;
    }


    html,
    body {
      height: 100%;
      margin: 0;
      overflow: hidden;
      /* prevent the whole page from scrolling */
    }

    #facilitiesContainer {
      flex: 1;
      /* take up remaining space between header and pagination */
      overflow-y: auto;
      /* allow inner scrolling */
      min-height: 500px;
      /* IMPORTANT for flexbox scrolling */
      padding-right: 8px;
      /* Add right padding */
    }

    /* Custom thin scrollbar */
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

    /* Firefox */
    #facilitiesContainer {
      scrollbar-width: thin;
      scrollbar-color: #888 #f1f1f1;
    }
  </style>

  <!-- Main Content -->
  <main>

    <!-- Header & Controls -->
    <div>
      <!-- Filters, Search Bar & Buttons (single scrollable row) -->
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
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"
              aria-expanded="false">
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
      <div id="facilitiesContainer" class="flex-grow-1 overflow-auto" style="height: calc(100vh - 300px);">
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

      <!-- Pagination Controls (fixed at bottom) -->
      <div class="d-flex justify-content-center mt-auto pt-3">
        <nav aria-label="Facilities pagination">
          <ul class="pagination" id="paginationContainer">
            <li class="page-item disabled" id="prevPage">
              <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                <span aria-hidden="true">&laquo;</span>
                <span class="visually-hidden">Previous</span>
              </a>
            </li>
            <li class="page-item active">
              <a class="page-link" href="#" data-page="1">1</a>
            </li>
            <li class="page-item" id="nextPage">
              <a class="page-link" href="#" data-page="2">
                <span aria-hidden="true">&raquo;</span>
                <span class="visually-hidden">Next</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>
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

    <!-- Mass Assign Departments Modal for Facilities -->
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
              <!-- Facility Selection -->
              <div class="mb-4">
                <label class="form-label fw-bold" style="color: #003366;">Select Facilities</label>
                <select id="facilityMultiSelect" class="form-select" multiple size="6" style="border-color: #003366;">
                  <!-- Will be populated dynamically -->
                </select>
                <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple facilities</div>
              </div>

              <!-- Department Selection -->
              <div class="mb-4">
                <label class="form-label fw-bold" style="color: #003366;">Select Departments to Assign</label>
                <select id="departmentMultiSelect" class="form-select" multiple size="6" style="border-color: #003366;">
                  <!-- Will be populated dynamically -->
                </select>
                <div class="form-text text-muted">Hold Ctrl/Cmd to select multiple departments</div>
              </div>

              <!-- Summary -->
              <div class="alert" style="background-color: #f8f9fa; border-left: 4px solid #003366;" id="selectionSummary">
                <i class="bi bi-info-circle me-2" style="color: #003366;"></i>
                <span id="summaryText">No facilities selected</span>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn" id="executeMassAssignBtn" style="background-color: #003366; color: white;"
              onmouseover="this.style.backgroundColor='#004080'" onmouseout="this.style.backgroundColor='#003366'">
              <i class="bi bi-check-circle me-2"></i>Assign Departments
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
              This action will <strong>replace all existing department assignments</strong> for the selected facilities
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
  </main>
@endsection
@section('scripts')
  <script src="{{ asset('js/admin/toast.js') }}"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {

      // Authentication check
      const token = localStorage.getItem("adminToken");
      if (!token) {
        window.location.href = "/admin/login";
        return;
      }

      // DOM elements
      const facilitiesContainer = document.getElementById("facilitiesContainer");
      const searchInput = document.getElementById("searchInput");
      const layoutSelect = document.getElementById("layoutSelect");
      const statusFilter = document.getElementById("statusFilter");
      const categoryFilter = document.getElementById("categoryFilter");
      const loadingIndicator = document.getElementById("loadingIndicator");
      const noResultsMessage = document.getElementById("noResultsMessage");
      const paginationContainer = document.getElementById("paginationContainer");
      const addFacilitiesBtn = document.getElementById("addFacilitiesBtn");

      // State variables
      let allFacilities = [];
      let filteredFacilities = [];
      let categories = [];
      let itemsPerPage = 12;
      let currentPage = 1;
      let totalPages = 1;

      // Update the init function to fetch statuses
      async function init() {
        try {
          // Fetch facilities data
          await fetchFacilities();

          // Fetch and populate dropdowns
          await fetchStatuses();
          await fetchCategories();

          // Set up event listeners
          setupEventListeners();

          // Initialize pagination
          initializePagination();
        } catch (error) {
          console.error("Initialization error:", error);
          alert("Failed to initialize page. Please try again.");
        }
      }

      // Fetch and populate availability statuses
      async function fetchStatuses() {
        try {
          const response = await fetch("/api/availability-statuses", {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          });

          if (response.status === 401) {
            console.error("Unauthorized: Invalid or expired token.");
            localStorage.removeItem("adminToken");
            alert("Your session has expired. Please log in again.");
            setTimeout(() => {
              window.location.href = "/admin/login";
            }, 2000);
            return;
          }

          if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
          }

          const data = await response.json();

          // Populate dropdown with status data
          populateStatusFilter(data);

        } catch (error) {
          console.error("Error fetching statuses:", error);
        }
      }

      // Populate status filter dropdown
      function populateStatusFilter(statuses) {
        statusFilter.innerHTML = '<option value="all">All Statuses</option>';

        statuses.forEach((status) => {
          const option = document.createElement("option");
          option.value = status.status_id;
          option.textContent = status.status_name;
          statusFilter.appendChild(option);
        });
      }

      // Fetch facilities data from API
      async function fetchFacilities() {
        try {
          loadingIndicator.classList.remove("d-none");
          noResultsMessage.classList.add("d-none");

          const response = await fetch(
            "/api/facilities",
            {
              headers: {
                Accept: "application/json",
              },
            }
          );

          if (!response.ok) {
            throw new Error(
              `Error ${response.status}: ${response.statusText}`
            );
          }

          const data = await response.json();
          allFacilities = data.data || [];
          filteredFacilities = [...allFacilities];
          window.filteredFacilities = filteredFacilities; // Add this line

          // Render facilities dynamically
          renderFacilities(allFacilities);
        } catch (error) {
          console.error("Error fetching facilities:", error);
          loadingIndicator.classList.add("d-none");
          noResultsMessage.classList.remove("d-none");
          noResultsMessage.innerHTML = `<p class="text-danger">Failed to load facilities. Please try again later.</p>`;
        }
      }

      // Fetch and populate facilities categories
      async function fetchCategories() {
        try {
          const response = await fetch("/api/facility-categories", {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: "application/json",
            },
          });

          if (response.status === 401) {
            console.error("Unauthorized: Invalid or expired token.");
            localStorage.removeItem("adminToken");
            alert("Your session has expired. Please log in again.");
            setTimeout(() => {
              window.location.href = "/admin/login";
            }, 2000);
            return;
          }

          if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
          }

          const data = await response.json();

          // Log to inspect
          console.log("Facilities categories:", data);

          // Populate dropdown directly with API data
          populateCategoryFilter(data);

        } catch (error) {
          console.error("Error fetching categories:", error);
        }
      }

      // Populate category filter dropdown
      function populateCategoryFilter(categories) {
        categoryFilter.innerHTML = '<option value="all">All Categories</option>';

        categories.forEach((category) => {
          const option = document.createElement("option");
          option.value = category.category_id;      // Use category_id for value
          option.textContent = category.category_name;
          categoryFilter.appendChild(option);
        });
      }

      // Render facilities cards
      function renderFacilities(facilitiesList) {
        loadingIndicator.classList.add("d-none");

        // Clear existing content
        const container = document.getElementById('facilitiesCardsContainer');
        container.innerHTML = "";

        if (facilitiesList.length === 0) {
          // Show no facilities found message with icon
          container.innerHTML = `
                                                              <div class="col-12 text-center py-5">
                                                                <i class="bi bi-tools fs-1 text-muted" style="font-size: 4rem !important;"></i>
                                                                <p class="mt-2 text-muted">No facilities found.</p>
                                                              </div>
                                                            `;
          // Clear pagination when no results
          paginationContainer.innerHTML = '';
          return;
        }

        noResultsMessage.classList.add("d-none");

        // Calculate pagination
        totalPages = Math.ceil(facilitiesList.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, facilitiesList.length);

        // Get layout selection
        const layout = layoutSelect.value;

        // Set container class based on layout
        container.className = layout === "list" ? "row g-3" : "row g-2";

        // Render only the current page's items
        for (let i = startIndex; i < endIndex; i++) {
          const facilities = facilitiesList[i];
          const statusClass = getStatusClass(facilities.status.status_name);

          let primaryImage = "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

          if (facilities.images && facilities.images.length > 0) {
            const validImages = facilities.images.filter(img => img.image_url && img.image_url.trim() !== '');

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
          card.className = "col-md-4 col-lg-3 facilities-card mb-3";
          card.dataset.status = facilities.status.status_id.toString();
          card.dataset.category = facilities.category.category_id.toString();
          card.dataset.title = facilities.facility_name.toLowerCase();

          if (layout === "list") {
            // List layout
            card.className = "col-12 facilities-card mb-0";
            card.innerHTML = `
                        <div class="card h-100 shadow-sm rounded-3">
                          <div class="row g-0">
                            <div class="col-md-2" style="max-width: 120px; flex: 0 0 120px;">
                              <img src="${primaryImage}" 
                                   class="img-fluid rounded-start" 
                                   style="width: 120px; height: 120px; object-fit: cover;" 
                                   alt="${facilities.facility_name}">
                            </div>
                            <div class="col-md-8">
                              <div class="card-body py-3">
                                <h5 class="card-title fw-bold mb-2">${facilities.facility_name}</h5>
                                <p class="card-text mb-2">
                                  <span class="badge ${statusClass} me-2">${facilities.status.status_name}</span>
                    <small class="text-muted">
                      <i class="bi bi-tag-fill text-secondary me-1"></i>${facilities.category.category_name}
                      <i class="bi bi-tag-fill text-secondary me-1"></i>${facilities.subcategory.subcategory_name}
                    </small>
                                </p>
                                <p class="card-text text-muted mb-0">
                                  ${facilities.description || "No description available"}
                                </p>
                              </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center justify-content-center">
                              <div class="d-grid gap-2 w-100 px-2">
                                <a href="/admin/edit-facility?id=${facilities.facility_id}" 
                                   class="btn btn-sm btn-primary">
                                   Manage
                                </a>
                                <button class="btn btn-sm btn-outline-danger btn-delete" 
                                        data-id="${facilities.facility_id}">
                                  Delete
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      `;
          } else {
            // Grid layout
            card.className = "col-md-4 col-lg-3 facilities-card mb-3";
            card.innerHTML = `
                        <div class="card h-100">
                          <img src="${primaryImage}" class="card-img-top" style="height: 150px; object-fit: cover;" alt="${facilities.facility_name}">
                          <div class="card-body d-flex flex-column p-2">
                            <div>
                            <h6 class="card-title mb-1 fw-bold text-truncate" 
                                style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="${facilities.facility_name}">
                              ${facilities.facility_name}
                            </h6>
                            <p class="card-text text-muted mb-1 small text-truncate" 
                              style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                              <i class="bi bi-tag-fill text-secondary me-1"></i>
                              ${facilities.category.category_name} |
                              <i class="fa fa-layer-group text-secondary ms-1 me-1" 
                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                title="${facilities.subcategory.subcategory_name}"></i>
                              ${facilities.subcategory.subcategory_name}
                            </p>
                              <span class="badge ${statusClass} mb-2">${facilities.status.status_name}</span>
                              <p class="card-text mb-2 small text-truncate">${facilities.description || "No description available"}</p>
                            </div>
                            <div class="facilities-actions mt-auto d-grid gap-1">
                              <a href="/admin/edit-facility?id=${facilities.facility_id}" class="btn btn-sm btn-primary btn-manage">Manage</a>
                              <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${facilities.facility_id}">Delete</button>
                            </div>
                          </div>
                        </div>
                      `;
          }

          container.appendChild(card);
        }

        // Add event listeners to new buttons
        addButtonEventListeners();

        // Update pagination controls
        updatePagination();
      }

      // Get appropriate status class
      function getStatusClass(status) {
        switch (status.toLowerCase()) {
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
        // Filter controls
        [
          searchInput,
          layoutSelect,
          statusFilter,
          categoryFilter,
        ].forEach((control) => {
          control.addEventListener("change", filterFacilities);
        });
        searchInput.addEventListener("input", filterFacilities);
      }

      // Add event listeners to manage and delete buttons
      function addButtonEventListeners() {
        // Manage buttons
        document.querySelectorAll(".btn-manage").forEach((button) => {
          button.addEventListener("click", function () {
            const facilitiesId = this.dataset.id;
            window.location.href = `edit-facilities.html?id=${facilitiesId}`;
          });
        });

        // Delete buttons
        document.querySelectorAll(".btn-delete").forEach((button) => {
          button.addEventListener("click", function () {
            const facilityId = this.dataset.id;
            showDeleteConfirmation(facilityId);
          });
        });
      }

      // Show delete confirmation modal
      function showDeleteConfirmation(facilityId) {
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        // Set up the confirmation button
        confirmDeleteBtn.onclick = async function () {
          await handleDeleteFacility(facilityId);
          // Hide modal after action
          const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
          modal.hide();
        };

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        modal.show();
      }


      // Handle facility deletion
      async function handleDeleteFacility(id) {
        try {
          const response = await fetch(
            `/api/admin/facilities/${id}`,
            {
              method: "DELETE",
              headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/json",
              },
            }
          );

          if (!response.ok) {
            throw new Error("Failed to delete facility");
          }

          // Show success message
          showToast("Facility deleted successfully!", 'success');

          // Refresh the facilities list
          await fetchFacilities();

          return true;
        } catch (error) {
          console.error("Error deleting facility:", error);
          showToast("Failed to delete facility", 'error');
          throw error;
        }
      }

      // Filter Facilities based on all criteria
      function filterFacilities() {
        const searchTerm = searchInput.value.toLowerCase();
        const status = statusFilter.value;
        const category = categoryFilter.value;
        const layout = layoutSelect.value;
        // Apply layout view
        facilitiesContainer.className = `row g-2 ${layout === "list" ? "list-view" : ""}`;

        // Reset to first page when filters change
        currentPage = 1;

        // Filter the facilities array
        filteredFacilities = allFacilities.filter(facilities => {
          const facilitiesStatus = facilities.status.status_id.toString();
          const facilitiesCategory = facilities.category.category_id.toString();
          const facilitiesTitle = facilities.facility_name.toLowerCase();

          const matchesSearch = facilitiesTitle.includes(searchTerm);
          const matchesStatus = status === "all" || facilitiesStatus === status;
          const matchesCategory = category === "all" || facilitiesCategory === category; // Compare by ID

          return matchesSearch && matchesStatus && matchesCategory;
        });
        // Re-render with filtered results
        renderFacilities(filteredFacilities);
      }

      // Initialize pagination
      function initializePagination() {
        updatePagination();
        showPage(1);

        // Event delegation for pagination (handles dynamically created elements)
        paginationContainer.addEventListener("click", function (e) {
          if (e.target.classList.contains("page-link")) {
            e.preventDefault();

            const page = parseInt(e.target.getAttribute("data-page"));
            if (!isNaN(page)) {
              showPage(page);
            } else if (e.target.closest("#prevPage")) {
              // Previous page button
              if (currentPage > 1) {
                showPage(currentPage - 1);
              }
            } else if (e.target.closest("#nextPage")) {
              // Next page button
              if (currentPage < totalPages) {
                showPage(currentPage + 1);
              }
            }
          }
        });
      }

      // Show a specific page
      function showPage(page) {
        currentPage = page;

        // Re-render the facilities with the new page
        renderFacilities(filteredFacilities);
      }

      // Update pagination controls
      function updatePagination() {
        const totalPages = Math.ceil(filteredFacilities.length / itemsPerPage);

        // Clear existing pagination
        paginationContainer.innerHTML = "";


        // Don't show pagination if there's only 1 page or no items
        if (totalPages <= 1) {
          return;
        }

        // Previous button
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
        prevLi.id = "prevPage";
        prevLi.innerHTML = `
                                  <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                    <span aria-hidden="true">&laquo;</span>
                                    <span class="visually-hidden">Previous</span>
                                  </a>
                                `;
        paginationContainer.appendChild(prevLi);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        // Adjust start page if we're near the end
        if (endPage - startPage + 1 < maxVisiblePages) {
          startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
          const pageLi = document.createElement("li");
          pageLi.className = `page-item ${i === currentPage ? "active" : ""}`;
          pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
          paginationContainer.appendChild(pageLi);
        }
        // Next button
        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`;
        nextLi.id = "nextPage";
        nextLi.innerHTML = `
                                          <a class="page-link" href="#" data-page="${currentPage + 1}">
                                            <span aria-hidden="true">&raquo;</span>
                                            <span class="visually-hidden">Next</span>
                                          </a>
                                        `;
        paginationContainer.appendChild(nextLi);

        // Add event listeners
        paginationContainer.querySelectorAll(".page-link[data-page]").forEach((link) => {
          link.addEventListener("click", function (e) {
            e.preventDefault();
            const page = parseInt(this.getAttribute("data-page"));
            if (page >= 1 && page <= totalPages) {
              showPage(page);
            }
          });
        });

        // Previous page event
        prevLi.querySelector(".page-link").addEventListener("click", function (e) {
          e.preventDefault();
          if (currentPage > 1) {
            showPage(currentPage - 1);
          }
        });

        // Next page event
        nextLi.querySelector(".page-link").addEventListener("click", function (e) {
          e.preventDefault();
          if (currentPage < totalPages) {
            showPage(currentPage + 1);
          }
        });
      }

      // Mass Assignment Modal functionality for Facilities
      let facilitiesList = [];
      let departmentsList = [];

      // Fetch facilities for dropdown
      async function fetchFacilitiesForDropdown() {
        try {
          const response = await fetch("/api/facilities/dropdown", {
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
          });

          if (!response.ok) throw new Error("Failed to fetch facilities");

          const result = await response.json();
          facilitiesList = result.data || [];
          populateFacilityMultiSelect();
        } catch (error) {
          console.error("Error fetching facilities:", error);
          showToast('Failed to load facilities', 'error');
        }
      }

      // Fetch departments for dropdown
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
          populateDepartmentMultiSelect();
        } catch (error) {
          console.error("Error fetching departments:", error);
          showToast('Failed to load departments', 'error');
        }
      }

      // Populate facility multi-select
      function populateFacilityMultiSelect() {
        const select = document.getElementById('facilityMultiSelect');
        if (!select) return;
        select.innerHTML = '';

        facilitiesList.forEach(facility => {
          const option = document.createElement('option');
          option.value = facility.facility_id;
          option.textContent = facility.facility_name;
          select.appendChild(option);
        });
      }

      // Populate department multi-select
      function populateDepartmentMultiSelect() {
        const select = document.getElementById('departmentMultiSelect');
        if (!select) return;
        select.innerHTML = '';

        departmentsList.forEach(dept => {
          const option = document.createElement('option');
          option.value = dept.department_id;
          option.textContent = dept.department_name;
          select.appendChild(option);
        });
      }

      // Update selection summary
      function updateSelectionSummary() {
        const facilitySelect = document.getElementById('facilityMultiSelect');
        const departmentSelect = document.getElementById('departmentMultiSelect');

        const facilityCount = facilitySelect ? facilitySelect.selectedOptions.length : 0;
        const departmentCount = departmentSelect ? departmentSelect.selectedOptions.length : 0;

        const summarySpan = document.getElementById('summaryText');
        if (summarySpan) {
          if (facilityCount === 0 && departmentCount === 0) {
            summarySpan.textContent = 'No facilities or departments selected';
          } else if (facilityCount === 0) {
            summarySpan.textContent = 'Please select at least one facility';
          } else if (departmentCount === 0) {
            summarySpan.textContent = 'Please select at least one department';
          } else {
            summarySpan.textContent = `${facilityCount} facility/facilities and ${departmentCount} department(s) selected. Current department assignments will be replaced.`;
          }
        }
      }

      // Show warning modal before execution
      document.getElementById('executeMassAssignBtn')?.addEventListener('click', function () {
        const facilitySelect = document.getElementById('facilityMultiSelect');
        const departmentSelect = document.getElementById('departmentMultiSelect');

        const facilityIds = facilitySelect ? Array.from(facilitySelect.selectedOptions).map(opt => parseInt(opt.value)) : [];
        const departmentIds = departmentSelect ? Array.from(departmentSelect.selectedOptions).map(opt => parseInt(opt.value)) : [];

        if (facilityIds.length === 0) {
          showToast('Please select at least one facility', 'error');
          return;
        }

        if (departmentIds.length === 0) {
          showToast('Please select at least one department', 'error');
          return;
        }

        // Store data for confirmation
        window.pendingAssignment = {
          facilityIds: facilityIds,
          departmentIds: departmentIds
        };

        // Show warning modal
        const warningModal = new bootstrap.Modal(document.getElementById('assignmentWarningModal'));
        warningModal.show();
      });

      // Execute mass assignment after confirmation
      document.getElementById('confirmAssignBtn')?.addEventListener('click', async function () {
        if (!window.pendingAssignment) return;

        const { facilityIds, departmentIds } = window.pendingAssignment;

        // Disable button and show loading
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
          const response = await fetch('/api/admin/facilities/mass-assign-departments', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              facility_ids: facilityIds,
              department_ids: departmentIds,
              action: 'replace' // Always replace existing assignments
            })
          });

          if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to process mass assignment');
          }

          const result = await response.json();

          // Show success message
          showToast(result.message, 'success');

          // Close both modals
          const warningModal = bootstrap.Modal.getInstance(document.getElementById('assignmentWarningModal'));
          const massModal = bootstrap.Modal.getInstance(document.getElementById('massAssignDepartmentsModal'));

          if (warningModal) warningModal.hide();
          if (massModal) massModal.hide();

          // Clear pending data
          window.pendingAssignment = null;

          // Refresh facilities data
          await fetchFacilities();

        } catch (error) {
          console.error('Error in mass assignment:', error);
          showToast('Failed to process mass assignment: ' + error.message, 'error');
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      });

      // Add event listeners for changes that update summary
      document.getElementById('facilityMultiSelect')?.addEventListener('change', updateSelectionSummary);
      document.getElementById('departmentMultiSelect')?.addEventListener('change', updateSelectionSummary);

      // Fetch data when modal is opened
      const massAssignModal = document.getElementById('massAssignDepartmentsModal');
      if (massAssignModal) {
        massAssignModal.addEventListener('show.bs.modal', function () {
          fetchFacilitiesForDropdown();
          fetchDepartmentsForModal();

          // Reset selections
          const facilitySelect = document.getElementById('facilityMultiSelect');
          const departmentSelect = document.getElementById('departmentMultiSelect');

          if (facilitySelect) facilitySelect.selectedIndex = -1;
          if (departmentSelect) departmentSelect.selectedIndex = -1;

          updateSelectionSummary();
        });
      }

      // Start the application
      init();
    });

  </script>
@endsection