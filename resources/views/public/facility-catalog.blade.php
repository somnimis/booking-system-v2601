@extends('layouts.app')

@section('title', 'Booking Catalog - Facilities')

@section('content')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/public/catalog.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}" />
  <style>
    /* Calendar loading spinner styles */
    .calendar-loading-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      opacity: 1;
      transition: opacity 0.3s ease;
    }

    .calendar-loading-overlay.hidden {
      opacity: 0;
      pointer-events: none;
    }

    .calendar-loading-spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    .loading-text {
      margin-top: 15px;
      color: #555;
      font-weight: 500;
    }


    .check-availability-btn {
      transition: all 0.2s ease;
    }

    .check-availability-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .facility-availability-info img {
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .color-box {
      border-radius: 3px;
    }

    /* Ensure the availability calendar has proper height */
    #facilityAvailabilityCalendar {
      min-height: 500px;
    }


    /* Active button state */
    .btn-outline-primary.active {
      background-color: #0d6efd;
      color: white;
    }

    /* Target only mini calendar navigation buttons */
    .mini-calendar .prev-month,
    .mini-calendar .next-month {
      background-color: white !important;
      border: 1px solid #dee2e6 !important;
      color: #495057 !important;
      border-radius: 4px !important;
      padding: 0.25rem 0.5rem !important;
      transition: all 0.2s ease !important;
      width: 32px !important;
      /* Fixed width for consistency */
      height: 32px !important;
      /* Fixed height for consistency */
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    .mini-calendar .prev-month:hover,
    .mini-calendar .next-month:hover {
      background-color: #f8f9fa !important;
      border-color: #adb5bd !important;
      color: #212529 !important;
    }

    .mini-calendar .prev-month:active,
    .mini-calendar .next-month:active {
      background-color: #e9ecef !important;
      transform: translateY(1px);
    }

    /* Style the chevron icons inside */
    .mini-calendar .prev-month i,
    .mini-calendar .next-month i {
      font-size: 0.9rem !important;
      line-height: 1 !important;
    }


    /* Ensure the facility list doesn't get too tall on mobile */
    #facilityFilterList {
      max-height: 200px;
      overflow-y: auto;
      padding-right: 5px;
    }

    /* Custom scrollbar styling for better mobile experience */
    .scrollable-left-column::-webkit-scrollbar {
      width: 6px;
    }

    .scrollable-left-column::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    .scrollable-left-column::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 3px;
    }

    .scrollable-left-column::-webkit-scrollbar-thumb:hover {
      background: #555;
    }


    .btn-custom {
      background-color: #f5bc40ff;
      color: #1d1300ff;
      border-color: transparent !important;
    }

    .btn-custom:hover {
      background-color: #daa32cff;
      color: #1d1300ff;
      border-color: transparent !important;
    }

    .btn-custom:active {
      background-color: #c08e22ff !important;
      color: #1d1300ff !important;
      border-color: transparent !important;
      box-shadow: none !important;
    }


    .list-layout .catalog-card {
      flex-direction: row;
      align-items: stretch;
      gap: 1rem;
      height: 200px;
      overflow: hidden;
      padding: 0.25rem !important;
      box-sizing: border-box;
      position: relative;
    }

    .list-layout .catalog-card-img {
      width: 200px;
      height: 190px !important;
      /* Force exact height */
      object-fit: cover;
      flex-shrink: 0;
      box-sizing: border-box;
    }

    .list-layout .catalog-card-details {
      flex: 1;
      min-width: 0;
      /* Prevent flex item from overflowing */
      padding-left: 1rem;

    }

    .list-layout .catalog-card-actions {
      flex-direction: column;
      width: 200px;
      flex-shrink: 0;
      border-top: none;
      border-left: 1px solid #eee;
      padding: 0.75rem;
      margin-top: 0;
      gap: 0.5rem;
      justify-content: center;
      /* Center vertically */
      height: 100%;
      /* Ensure actions take full card height */
      box-sizing: border-box;
      /* Include padding in height calculation */
    }

    .list-layout .catalog-card-fee {
      margin-top: 0;
      padding: 0.5rem 0;
    }


    .catalog-card-details {
      flex: 1;
      display: flex;
      flex-direction: column;
      padding-top: 0.75rem;
    }

    .status-banner {
      align-self: flex-start;
      /* Prevents stretching */
      width: auto !important;
      /* Prevents flex stretching */
      white-space: nowrap;
      /* Prevents text wrapping */
      padding: 0.25rem 0.75rem;
      /* Consistent padding */
      margin-bottom: 0.5rem;
      /* Space below banner */
    }

    .facility-description {
      flex-grow: 1;
      /* takes remaining space so the fee + buttons stay at bottom */
    }

    /* Fee and actions section */
    .catalog-card-fee,
    .catalog-card-actions {
      margin-top: auto;
      /* push to bottom */
    }

    .catalog-card-actions {
      margin-top: auto;
      /* pushes the buttons to the bottom */
      display: flex;
      justify-content: space-between;
      gap: 0.5rem;
      padding: 0.75rem;
      border-top: 1px solid #eee;
    }

    .grid-layout .catalog-card {
      padding: 0.25rem !important;
    }


    #chooseCatalogDropdown {
      color: #0c3779ff;
      transition: color 0.2s ease;
    }

    #chooseCatalogDropdown:hover,
    #chooseCatalogDropdown:focus,
    #chooseCatalogDropdown.show {
      color: #0066ffff;
    }

    /* Catalog Hero Section */
    .catalog-hero-section {
      background-image: url("{{ asset('assets/homepage.jpg') }}");
      background-size: cover;
      background-position: center;
      min-height: 170px;
      display: flex;
      align-items: flex-end;
      padding-bottom: 20px;
      position: relative;
      z-index: 0;
    }
  </style>

  <section class="catalog-hero-section">
    <div class="catalog-hero-content">
      <h2 id="catalogHeroTitle">Facility Catalog</h2>
    </div>
  </section>

  <main class="main-catalog-section">
    <div class="container">
      <!-- Sidebar -->
      <div class="row">
        <div class="col-lg-3 col-md-4">
          <div class="quick-links-card mb-4">
            <p class="mb-2">
              Not sure when to book?<br />View available timeslots here.
            </p>

            <div class="d-grid gap-2"> <!-- ensures uniform full-width buttons -->
              <a href="/events-calendar" target="_blank" rel="noopener noreferrer" role="button"
                class="btn btn-light btn-custom d-flex justify-content-center align-items-center" id="eventsCalendarBtn">
                <i class="fa-solid fa-calendar me-2"></i> Events Calendar
              </a>


              <div style="position:relative;">
                <span id="requisitionBadge" class="badge bg-danger rounded-pill position-absolute"
                  style="top:-0.7rem; right:-0.7rem; font-size:0.8em; z-index:2; display:none;">
                  0
                </span>
                <a id="requisitionFormButton" href="reservation-form"
                  class="btn btn-primary d-flex justify-content-center align-items-center position-relative">
                  <i class="fa-solid fa-file-invoice me-2"></i> Your Requisition Form
                </a>
              </div>
            </div>
          </div>

          <div class="sidebar-card">
            <h5>Browse by Category</h5>
            <div class="filter-list" id="categoryFilterList"></div>
          </div>
        </div>


        <!-- Main Content Area (Top Bar) -->
        <div class="col-lg-9 col-md-8">
          <div class="right-content-header">
            <div class="dropdown">
              <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" id="chooseCatalogDropdown"
                data-bs-toggle="dropdown" aria-expanded="false">
                All Facilities
              </button>
              <ul class="dropdown-menu" aria-labelledby="chooseCatalogDropdown">
                <li>
                  <a class="dropdown-item" href="{{ asset('booking-catalog') }}" data-catalog-type="facilities">
                    Facilities
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="{{ asset('booking-catalog') }}" data-catalog-type="equipment">
                    Equipment
                  </a>
                </li>
              </ul>

            </div>
            <div class="d-flex gap-2 filter-sort-dropdowns">
              <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                  id="statusDropdown">
                  Status: Available
                </button>
                <ul class="dropdown-menu" id="statusFilterMenu">
                  <li>
                    <a class="dropdown-item status-option" href="#" data-status="All">All</a>
                  </li>
                  <li>
                    <a class="dropdown-item status-option" href="#" data-status="Available">Available</a>
                  </li>
                  <li>
                    <a class="dropdown-item status-option" href="#" data-status="Unavailable">Unavailable</a>
                  </li>
                </ul>
              </div>
              <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                  id="layoutDropdown">
                  Grid Layout
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <a class="dropdown-item layout-option active" href="#" data-layout="grid">Grid</a>
                  </li>
                  <li>
                    <a class="dropdown-item layout-option" href="#" data-layout="list">List</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Calendar Event Modal (ROOT LEVEL - not nested) -->
          <div class="modal fade" id="calendarEventModal" tabindex="-1" aria-hidden="true">
            <!-- CalendarModule will populate the modal content -->
          </div>

          <!-- Facility Detail Modal -->
          <div class="modal fade" id="facilityDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="facilityDetailModalLabel">Facility Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="facilityDetailContent">
                  <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading Indicator -->
          <div id="loadingIndicator" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading catalog items...</p>
          </div>

          <!-- Catalog Items Container -->
          <div id="catalogItemsContainer" class="grid-layout d-none"></div>

          <!-- Pagination -->
          <div class="text-center mt-4">
            <nav>
              <ul id="pagination" class="pagination justify-content-center"></ul>
            </nav>
          </div>
        </div>
      </div>
    </div>


  </main>


  <!-- Single Facility Availability Modal -->
  <div class="modal fade" id="singleFacilityAvailabilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%;">
      <div class="modal-content" style="min-height: 85vh;">
        <div class="modal-header">
          <h5 class="modal-title" id="singleFacilityAvailabilityModalLabel">
            <i class="bi bi-calendar-check me-2"></i>
            <span id="facilityAvailabilityName">Facility Availability</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3">
          <div class="row g-3">
            <!-- Left Column: Mini Calendar & Info -->
            <div class="col-lg-3 col-md-12">
              <div class="scrollable-left-column">

                <!-- Facility Info Card -->
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="facility-availability-info text-center">
                      <div id="facilityAvailabilityImage" class="mb-3">
                        <!-- Image will be loaded here -->
                      </div>
                      <h6 id="facilityAvailabilityTitle" class="mb-2">
                        <span id="facilityTitleText"></span>
                        <span class="text-muted">
                          (<i class="bi bi-people-fill"></i> <span id="facilityCapacity"></span>)
                        </span>
                      </h6>

                      <div class="facility-meta small text-muted mb-2">

                        <div><i class="bi bi-tags-fill me-1"></i> <span id="facilityCategory"></span></div>
                      </div>
                      <div class="availability-status mt-3">
                        <span class="badge" id="facilityStatusBadge"></span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Mini Calendar Card -->
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="calendar-content">
                      <div class="mini-calendar">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <button class="btn btn-sm btn-secondary prev-month" type="button">
                            <i class="bi bi-chevron-left"></i>
                          </button>
                          <h6 class="mb-0 month-year" id="availabilityCurrentMonthYear"></h6>
                          <button class="btn btn-sm btn-secondary next-month" type="button">
                            <i class="bi bi-chevron-right"></i>
                          </button>
                        </div>
                        <div class="calendar-header d-flex mb-2">
                          <div class="day-header text-center flex-fill small text-muted">S</div>
                          <div class="day-header text-center flex-fill small text-muted">M</div>
                          <div class="day-header text-center flex-fill small text-muted">T</div>
                          <div class="day-header text-center flex-fill small text-muted">W</div>
                          <div class="day-header text-center flex-fill small text-muted">T</div>
                          <div class="day-header text-center flex-fill small text-muted">F</div>
                          <div class="day-header text-center flex-fill small text-muted">S</div>
                        </div>
                        <div class="calendar-days" id="availabilityMiniCalendarDays">
                          <!-- Days populated by JavaScript -->
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Right Column: Calendar -->
            <div class="col-lg-9 col-md-12 d-flex flex-column">

              <!-- Legend -->
              <div class="card">
                <div class="card-body py-2">
                  <div id="dynamicLegend" class="d-flex flex-wrap gap-3">
                    <!-- Will be populated by JavaScript -->
                    <div class="text-muted small">Loading status colors...</div>
                  </div>
                </div>
              </div>

              <div class="card flex-grow-1 mt-3">
                <div class="card-body p-3 d-flex flex-column">
                  <div class="calendar-content flex-grow-1 d-flex flex-column">
                    <div id="facilityAvailabilityCalendar" class="flex-grow-1"></div>
                  </div>
                </div>
              </div>


            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="bookNowBtn">
            <i class="bi bi-calendar-plus me-1"></i> Book This Facility
          </button>
        </div>
      </div>
    </div>
  </div>


@endsection

@section('scripts')
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <script src="{{ asset('js/public/calendar.js') }}"></script>
  <script>
    // Global variables
    let currentPage = 1;
    const itemsPerPage = 6;
    let allFacilities = [];
    let facilityCategories = [];
    let filteredItems = [];
    let currentLayout = "grid";
    let selectedItems = [];
    let allowedStatusIds = [1, 2];
    let statusFilter = "All";
    let formStatuses = {};
    let availabilityCalendarInstance = null;
    let currentFacilityId = null;
    let cachedFacilityEvents = {};

    // DOM elements
    const loadingIndicator = document.getElementById("loadingIndicator");
    const catalogItemsContainer = document.getElementById("catalogItemsContainer");
    const categoryFilterList = document.getElementById("categoryFilterList");
    const pagination = document.getElementById("pagination");
    const requisitionBadge = document.getElementById("requisitionBadge");

    // Initialize modals
    let facilityDetailModal = null;
    let singleFacilityAvailabilityModal = null;

    document.addEventListener("DOMContentLoaded", function () {
      // Initialize Bootstrap modals
      facilityDetailModal = new bootstrap.Modal(document.getElementById("facilityDetailModal"));
      singleFacilityAvailabilityModal = new bootstrap.Modal(document.getElementById("singleFacilityAvailabilityModal"));
      
      // Set initial layoutDropdown button text and active class
      const layoutDropdownBtn = document.getElementById("layoutDropdown");
      if (currentLayout === "grid") {
        layoutDropdownBtn.textContent = "Grid Layout";
        document.querySelectorAll(".layout-option").forEach(opt => {
          if (opt.dataset.layout === "grid") opt.classList.add("active");
          else opt.classList.remove("active");
        });
      } else {
        layoutDropdownBtn.textContent = "List Layout";
        document.querySelectorAll(".layout-option").forEach(opt => {
          if (opt.dataset.layout === "list") opt.classList.add("active");
          else opt.classList.remove("active");
        });
      }

      // Set initial active status option
      document.querySelectorAll("#statusFilterMenu .status-option").forEach(opt => {
        if (opt.dataset.status === statusFilter) {
          opt.classList.add("active");
          document.getElementById("statusDropdown").textContent =
            "Status: " + (statusFilter === "All" ? "All" : statusFilter);
        } else {
          opt.classList.remove("active");
        }
      });

      init();
    });

    // ----- Utility Functions ----- //
    async function fetchData(url, options = {}) {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
      const response = await fetch(url, {
        ...options,
        headers: {
          "X-CSRF-TOKEN": csrfToken,
          "Content-Type": "application/json",
          "Accept": "application/json",
          ...(options.headers || {}),
        },
        credentials: "same-origin"
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      return await response.json();
    }

    function showToast(message, type = "success", duration = 3000) {
      const toast = document.createElement("div");
      toast.className = `toast align-items-center border-0 position-fixed end-0 mb-2`;
      toast.style.zIndex = "1100";
      toast.style.bottom = "0";
      toast.style.right = "0";
      toast.style.margin = "1rem";
      toast.style.opacity = "0";
      toast.style.transform = "translateY(20px)";
      toast.style.transition = "transform 0.4s ease, opacity 0.4s ease";
      toast.setAttribute("role", "alert");
      toast.setAttribute("aria-live", "assertive");
      toast.setAttribute("aria-atomic", "true");

      const bgColor = type === "success" ? "#004183ff" : "#dc3545";
      toast.style.backgroundColor = bgColor;
      toast.style.color = "#fff";
      toast.style.minWidth = "250px";
      toast.style.borderRadius = "0.3rem";

      toast.innerHTML = `
        <div class="d-flex align-items-center px-3 py-1"> 
          <i class="bi ${type === "success" ? "bi-check-circle-fill" : "bi-exclamation-circle-fill"} me-2"></i>
          <div class="toast-body flex-grow-1" style="padding: 0.25rem 0;">${message}</div>
          <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="loading-bar" style="
          height: 3px;
          background: rgba(255,255,255,0.7);
          width: 100%;
          transition: width ${duration}ms linear;
        "></div>
      `;

      document.body.appendChild(toast);

      const bsToast = new bootstrap.Toast(toast, {
        autohide: false
      });

      bsToast.show();

      requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0)";
      });

      const loadingBar = toast.querySelector(".loading-bar");
      requestAnimationFrame(() => {
        loadingBar.style.width = "0%";
      });

      setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(20px)";

        setTimeout(() => {
          bsToast.hide();
          toast.remove();
        }, 400);
      }, duration);
    }

    function showError(message) {
      showToast(message, "error");
    }

    async function updateAllUI() {
      try {
        const response = await fetchData("/api/requisition/get-items");
        selectedItems = response.data?.selected_items || [];
        filterAndRenderItems();
        updateCartBadge();
      } catch (error) {
        console.error("Error updating UI:", error);
      }
    }

    async function addToForm(id, type, quantity = 1) {
      try {
        const requestBody = {
          type: type,
          equipment_id: type === 'equipment' ? parseInt(id) : undefined,
          facility_id: type === 'facility' ? parseInt(id) : undefined,
          quantity: parseInt(quantity)
        };

        const response = await fetchData("/api/requisition/add-item", {
          method: "POST",
          body: JSON.stringify(requestBody)
        });

        if (!response.success) {
          throw new Error(response.message || "Failed to add item");
        }

        selectedItems = response.data.selected_items || [];
        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} added to form`);
        await updateAllUI();
        localStorage.setItem('formUpdated', Date.now().toString());
      } catch (error) {
        console.error("Error adding item:", error);
        showToast(error.message || "Error adding item to form", "error");
      }
    }

    async function removeFromForm(id, type) {
      try {
        const requestBody = {
          type: type,
          equipment_id: type === 'equipment' ? parseInt(id) : undefined,
          facility_id: type === 'facility' ? parseInt(id) : undefined
        };

        const response = await fetchData("/api/requisition/remove-item", {
          method: "POST",
          body: JSON.stringify(requestBody)
        });

        if (!response.success) {
          throw new Error(response.message || "Failed to remove item");
        }

        selectedItems = response.data.selected_items || [];
        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} removed from form`);
        await updateAllUI();
        localStorage.setItem('formUpdated', Date.now().toString());
      } catch (error) {
        console.error("Error removing item:", error);
        showToast(error.message || "Error removing item from form", "error");
      }
    }

    function getFacilityButtonHtml(facility) {
      const isUnavailable = facility.status.status_id === 2;
      const isSelected = selectedItems.some(
        item => item.type === 'facility' && parseInt(item.facility_id) === facility.facility_id
      );

      if (isUnavailable) {
        return `
          <button class="btn btn-secondary add-remove-btn" 
            data-id="${facility.facility_id}" 
            data-type="facility" 
            disabled
            style="cursor: not-allowed; opacity: 0.65;">
            Unavailable
          </button>
        `;
      }

      if (isSelected) {
        return `
          <button class="btn btn-danger add-remove-btn" 
            data-id="${facility.facility_id}" 
            data-type="facility" 
            data-action="remove">
            Remove from form
          </button>
        `;
      } else {
        return `
          <button class="btn btn-primary add-remove-btn" 
            data-id="${facility.facility_id}" 
            data-type="facility" 
            data-action="add">
            Add to form
          </button>
        `;
      }
    }

    function renderCategoryFilters() {
      categoryFilterList.innerHTML = "";

      // "All Categories" option
      const allCategoriesItem = document.createElement("div");
      allCategoriesItem.className = "category-item";
      allCategoriesItem.innerHTML = `
        <div class="form-check">
          <input class="form-check-input category-filter" type="checkbox" id="allCategories" value="All" checked disabled>
          <label class="form-check-label" for="allCategories">All Categories</label>
        </div>
      `;
      categoryFilterList.appendChild(allCategoriesItem);

      // Render categories and subcategories
      facilityCategories.forEach((category) => {
        const categoryItem = document.createElement("div");
        categoryItem.className = "category-item";
        categoryItem.innerHTML = `
          <div class="form-check d-flex justify-content-between align-items-center">
            <div>
              <input class="form-check-input category-filter" type="checkbox" id="category${category.category_id}" value="${category.category_id}">
              <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
            </div>
            <i class="bi bi-chevron-up toggle-arrow" style="cursor:pointer"></i>
          </div>
          <div class="subcategory-list ms-3" style="overflow: hidden; max-height: 0;">
            ${category.subcategories.map(sub => `
              <div class="form-check">
                <input class="form-check-input subcategory-filter" type="checkbox" id="subcategory${sub.subcategory_id}" value="${sub.subcategory_id}" data-category="${category.category_id}">
                <label class="form-check-label" for="subcategory${sub.subcategory_id}">${sub.subcategory_name}</label>
              </div>
            `).join("")}
          </div>
        `;
        categoryFilterList.appendChild(categoryItem);

        const toggleArrow = categoryItem.querySelector(".toggle-arrow");
        const subcategoryList = categoryItem.querySelector(".subcategory-list");
        subcategoryList.style.maxHeight = `${subcategoryList.scrollHeight}px`;
        toggleArrow.classList.remove("bi-chevron-up");
        toggleArrow.classList.add("bi-chevron-down");

        toggleArrow.addEventListener("click", function () {
          const isExpanded = subcategoryList.style.maxHeight !== "0px";
          if (isExpanded) {
            subcategoryList.style.maxHeight = "0";
          } else {
            subcategoryList.style.maxHeight = `${subcategoryList.scrollHeight}px`;
          }
          toggleArrow.classList.toggle("bi-chevron-down");
          toggleArrow.classList.toggle("bi-chevron-up");
        });
      });

      // Filtering logic
      const allCategoriesCheckbox = document.getElementById("allCategories");
      const categoryCheckboxes = Array.from(document.querySelectorAll('.category-filter')).filter(cb => cb.id !== "allCategories");
      const subcategoryCheckboxes = Array.from(document.querySelectorAll('.subcategory-filter'));

      function updateAllCategoriesCheckbox() {
        const anyChecked = categoryCheckboxes.some(c => c.checked) || subcategoryCheckboxes.some(s => s.checked);
        if (anyChecked) {
          allCategoriesCheckbox.checked = false;
          allCategoriesCheckbox.disabled = false;
        } else {
          allCategoriesCheckbox.checked = true;
          allCategoriesCheckbox.disabled = true;
        }
      }

      function updateCategoryCheckboxState(catId) {
        const catCheckbox = document.getElementById("category" + catId);
        const relatedSubs = subcategoryCheckboxes.filter(sub => sub.dataset.category === catId);
        const anySubChecked = relatedSubs.some(sub => sub.checked);
        catCheckbox.checked = anySubChecked;
        catCheckbox.disabled = relatedSubs.every(sub => sub.disabled);
        const label = catCheckbox.nextElementSibling;
        if (catCheckbox.disabled) {
          label.style.fontWeight = "";
        }
      }

      categoryCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
          const catId = cb.value;
          const relatedSubs = subcategoryCheckboxes.filter(sub => sub.dataset.category === catId);
          if (!cb.checked) {
            relatedSubs.forEach(sub => {
              sub.checked = false;
              sub.disabled = true;
              sub.nextElementSibling.style.fontWeight = "";
            });
          } else {
            relatedSubs.forEach(sub => {
              sub.disabled = false;
            });
          }
          updateAllCategoriesCheckbox();
          filterAndRenderItems();
        });
      });

      subcategoryCheckboxes.forEach(sub => {
        sub.addEventListener('change', function () {
          const catId = sub.dataset.category;
          updateCategoryCheckboxState(catId);
          updateAllCategoriesCheckbox();
          filterAndRenderItems();
        });
      });

      allCategoriesCheckbox.addEventListener('change', function () {
        if (this.checked) {
          categoryCheckboxes.forEach(cb => {
            cb.checked = false;
            cb.disabled = false;
            cb.nextElementSibling.style.fontWeight = "";
          });
          subcategoryCheckboxes.forEach(sub => {
            sub.checked = false;
            sub.disabled = false;
            sub.nextElementSibling.style.fontWeight = "";
          });
          allCategoriesCheckbox.disabled = true;
          filterAndRenderItems();
        }
      });

      // Initial state
      allCategoriesCheckbox.checked = true;
      allCategoriesCheckbox.disabled = true;
      categoryCheckboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
        cb.nextElementSibling.style.fontWeight = "";
      });
      subcategoryCheckboxes.forEach(sub => {
        sub.checked = false;
        sub.disabled = false;
        sub.nextElementSibling.style.fontWeight = "";
      });
    }

    function renderItems(items) {
      const startIndex = (currentPage - 1) * itemsPerPage;
      const paginatedItems = items.slice(startIndex, startIndex + itemsPerPage);

      catalogItemsContainer.innerHTML = "";

      if (paginatedItems.length === 0) {
        catalogItemsContainer.classList.remove("grid-layout", "list-layout");
        catalogItemsContainer.innerHTML = `
          <div style="grid-column: 1 / -1; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 220px; width: 100%;">
            <i class="bi bi-building fs-1 text-muted"></i>
            <h4 class="mt-2">No facilities found</h4>
          </div>
        `;
        return;
      }

      catalogItemsContainer.classList.remove("grid-layout", "list-layout");
      catalogItemsContainer.classList.add(`${currentLayout}-layout`);

      if (currentLayout === "grid") {
        renderFacilitiesGrid(paginatedItems);
      } else {
        renderFacilitiesList(paginatedItems);
      }

      document.querySelectorAll(".catalog-card-details h5").forEach((title) => {
        title.addEventListener("click", function () {
          const id = this.getAttribute("data-id");
          showFacilityDetails(id);
        });
      });
    }

    function renderFacilitiesGrid(facilities) {
      catalogItemsContainer.innerHTML = facilities
        .map((facility) => {
          const primaryImage = facility.images?.find((img) => img.image_type === "Primary")?.image_url ||
            "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

          const facilityName = facility.facility_name.length > 18 ?
            facility.facility_name.substring(0, 18) + "..." : facility.facility_name;

          const description = facility.description ?
            (facility.description.length > 100 ?
              facility.description.substring(0, 100) + "..." : facility.description) :
            "No description available.";

          return `
            <div class="catalog-card">
              <img src="${primaryImage}" 
                   alt="${facility.facility_name}" 
                   class="catalog-card-img"
                   onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
              <div class="catalog-card-details">
                <h5 data-id="${facility.facility_id}" title="${facility.facility_name}">${facilityName}</h5>
                <span class="status-banner" style="background-color: ${facility.status.color_code}">
                  ${facility.status.status_name}
                </span>
                <div class="catalog-card-meta">
                  <span><i class="bi bi-people-fill"></i> ${facility.capacity || "N/A"}</span>
                  <span><i class="bi bi-tags-fill"></i> ${facility.subcategory?.subcategory_name || facility.category.category_name}</span>
                </div>
                <p class="facility-description" title="${facility.description || ''}">${description}</p>
                <div class="catalog-card-fee">
                  <i class="bi bi-cash-stack"></i> ₱${parseFloat(facility.external_fee).toLocaleString()} (${facility.rate_type})
                </div>
              </div>
              <div class="catalog-card-actions">
                ${getFacilityButtonHtml(facility)}
                ${getCheckAvailabilityButtonHtml(facility)}
              </div>
            </div>
          `;
        })
        .join("");
    }

    function renderFacilitiesList(facilities) {
      catalogItemsContainer.innerHTML = facilities
        .map((facility) => {
          const primaryImage = facility.images?.find((img) => img.image_type === "Primary")?.image_url ||
            "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";

          const facilityName = facility.facility_name.length > 30 ?
            facility.facility_name.substring(0, 30) + "..." : facility.facility_name;

          const description = facility.description ?
            (facility.description.length > 150 ?
              facility.description.substring(0, 150) + "..." : facility.description) :
            "No description available.";

          return `
            <div class="catalog-card">
              <img src="${primaryImage}" 
                   alt="${facility.facility_name}" 
                   class="catalog-card-img"
                   onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
              <div class="catalog-card-details">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 data-id="${facility.facility_id}" title="${facility.facility_name}">${facilityName}</h5>
                  <span class="status-banner" style="background-color: ${facility.status.color_code}">
                    ${facility.status.status_name}
                  </span>
                </div>
                <div class="catalog-card-meta">
                  <span><i class="bi bi-people-fill"></i> ${facility.capacity || "N/A"}</span>
                  <span><i class="bi bi-tags-fill"></i> ${facility.subcategory?.subcategory_name || facility.category.category_name}</span>
                </div>
                <p class="facility-description" title="${facility.description || ''}">${description}</p>
              </div>
              <div class="catalog-card-actions">
                <div class="catalog-card-fee mb-2 text-center">
                  <i class="bi bi-cash-stack"></i> ₱${parseFloat(facility.external_fee).toLocaleString()} (${facility.rate_type})
                </div>
                ${getFacilityButtonHtml(facility)}
                ${getCheckAvailabilityButtonHtml(facility)}
              </div>
            </div>
          `;
        })
        .join("");
    }

    function renderPagination(totalItems) {
      const totalPages = Math.ceil(totalItems / itemsPerPage);
      pagination.innerHTML = "";

      if (totalPages <= 1) return;

      for (let i = 1; i <= totalPages; i++) {
        const pageItem = document.createElement("li");
        pageItem.className = `page-item ${i === currentPage ? "active" : ""}`;
        pageItem.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        pageItem.addEventListener("click", (e) => {
          e.preventDefault();
          currentPage = i;
          filterAndRenderItems();
          window.scrollTo({
            top: catalogItemsContainer.offsetTop - 100,
            behavior: "smooth",
          });
        });
        pagination.appendChild(pageItem);
      }
    }

    function filterAndRenderItems() {
      filterItems();
      renderItems(filteredItems);
      renderPagination(filteredItems.length);
    }

    function getCheckAvailabilityButtonHtml(facility) {
      return `
        <button class="btn btn-light btn-custom check-availability-btn" 
                data-facility-id="${facility.facility_id}"
                data-facility-name="${facility.facility_name}"
                data-facility-image="${facility.images?.find(img => img.image_type === "Primary")?.image_url || 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'}"
                data-facility-capacity="${facility.capacity || 'N/A'}"
                data-facility-fee="${parseFloat(facility.external_fee).toLocaleString()}"
                data-facility-category="${facility.category.category_name}"
                data-facility-status="${facility.status.status_name}"
                data-facility-status-color="${facility.status.color_code}">
          Check Availability
        </button>
      `;
    }

    function updateCartBadge() {
      const badge = document.getElementById("requisitionBadge");
      if (!badge) return;
      if (selectedItems.length > 0) {
        badge.textContent = selectedItems.length;
        badge.style.display = "";
        badge.classList.remove("d-none");
      } else {
        badge.style.display = "none";
        badge.classList.add("d-none");
      }
    }

    // Event Handlers
    document.addEventListener("click", async (e) => {
      // Handle Add/Remove buttons
      const button = e.target.closest(".add-remove-btn");
      if (button) {
        e.preventDefault();
        const id = button.dataset.id;
        const type = button.dataset.type;
        const action = button.dataset.action;

        try {
          if (action === "add") {
            await addToForm(id, type);
          } else if (action === "remove") {
            await removeFromForm(id, type);
          }
          await updateAllUI();
        } catch (error) {
          console.error("Error handling form action:", error);
        }
        return;
      }

      // Handle check availability buttons
      const availabilityButton = e.target.closest('.check-availability-btn');
      if (availabilityButton) {
        e.preventDefault();
        const facilityData = {
          facility_id: availabilityButton.dataset.facilityId,
          facility_name: availabilityButton.dataset.facilityName,
          facility_image: availabilityButton.dataset.facilityImage,
          facility_capacity: availabilityButton.dataset.facilityCapacity,
          facility_fee: availabilityButton.dataset.facilityFee,
          facility_category: availabilityButton.dataset.facilityCategory,
          facility_status: availabilityButton.dataset.facilityStatus,
          facility_status_color: availabilityButton.dataset.facilityStatusColor
        };
        showFacilityAvailability(availabilityButton.dataset.facilityId, facilityData);
        return;
      }
    });

    // Category and subcategory filters
    document.addEventListener("change", function (e) {
      if (
        e.target.classList.contains("category-filter") ||
        e.target.classList.contains("subcategory-filter")
      ) {
        const label = e.target.nextElementSibling;
        if (e.target.checked) {
          label.style.fontWeight = "bold";
        } else {
          label.style.fontWeight = "";
        }
        currentPage = 1;
        filterAndRenderItems();
      }
    });

    // Layout toggle
    document.querySelectorAll(".layout-option").forEach((option) => {
      option.addEventListener("click", (e) => {
        e.preventDefault();
        currentLayout = option.dataset.layout;
        document.querySelectorAll(".layout-option").forEach(opt => opt.classList.remove("active"));
        option.classList.add("active");
        const layoutDropdownBtn = document.getElementById("layoutDropdown");
        layoutDropdownBtn.textContent = currentLayout === "grid" ? "Grid Layout" : "List Layout";
        filterAndRenderItems();
      });
    });

    // Status dropdown filter
    document.querySelectorAll("#statusFilterMenu .status-option").forEach((option) => {
      option.addEventListener("click", (e) => {
        e.preventDefault();
        statusFilter = option.dataset.status;
        document.querySelectorAll("#statusFilterMenu .status-option").forEach(opt => opt.classList.remove("active"));
        option.classList.add("active");
        document.getElementById("statusDropdown").textContent =
          "Status: " + (statusFilter === "All" ? "All" : statusFilter);
        filterAndRenderItems();
      });
    });

    async function showFacilityDetails(facilityId) {
      try {
        const facility = allFacilities.find((f) => f.facility_id == facilityId);
        if (!facility) return;

        const primaryImage = facility.images?.find((img) => img.image_type === "Primary")?.image_url || 
          "https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png";
        
        const isUnavailable = facility.status.status_id === 2;
        const isSelected = selectedItems.some(
          (selectedItem) =>
            parseInt(selectedItem.id) === facility.facility_id &&
            selectedItem.type === "facility"
        );

        document.getElementById("facilityDetailModalLabel").textContent = facility.facility_name;
        document.getElementById("facilityDetailContent").innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <img src="${primaryImage}" alt="${facility.facility_name}" class="facility-image img-fluid">
            </div>
            <div class="col-md-6">
              <div class="facility-details">
                <p><strong>Status:</strong> <span class="badge" style="background-color: ${facility.status.color_code}">${facility.status.status_name}</span></p>
                <p><strong>Category:</strong> ${facility.category.category_name}</p>
                <p><strong>Subcategory:</strong> ${facility.subcategory?.subcategory_name || "N/A"}</p>
                <p><strong>Capacity:</strong> ${facility.capacity}</p>
                <p><strong>Rate:</strong> ₱${parseFloat(facility.external_fee).toLocaleString()} (${facility.rate_type})</p>
                <p><strong>Description:</strong></p>
                <p>${facility.description || "No description available."}</p>
              </div>
              <div class="mt-3">
                ${isUnavailable
                  ? `<button class="btn btn-secondary" disabled style="cursor: not-allowed; opacity: 0.65;">Unavailable</button>`
                  : `<button class="btn ${isSelected ? "btn-danger" : "btn-primary"} add-remove-btn" 
                      data-id="${facility.facility_id}" 
                      data-type="facility" 
                      data-action="${isSelected ? "remove" : "add"}">
                      ${isSelected ? "Remove from Form" : "Add to Form"}
                    </button>`}
              </div>
            </div>
          </div>
        `;
        
        if (facilityDetailModal) {
          facilityDetailModal.show();
        }
      } catch (error) {
        console.error("Error showing facility details:", error);
        showError("Failed to load facility details.");
      }
    }

    // Main Initialization
    async function init() {
      try {
        // Fetch form statuses for calendar colors
        const statusesResponse = await fetchData('/api/form-statuses');
        if (statusesResponse && Array.isArray(statusesResponse)) {
          const activeStatuses = statusesResponse.filter(status => status.status_id <= 6);
          activeStatuses.forEach(status => {
            formStatuses[status.status_name] = status.color_code;
          });
          console.log('Form statuses loaded (filtered):', formStatuses);
          renderDynamicLegend();
        } else {
          console.error('Failed to load form statuses');
        }

        const [facilitiesData, facilityCategoriesData, selectedItemsResponse] = await Promise.all([
          fetchData('/api/facilities'),
          fetchData('/api/facility-categories/index'),
          fetchData('/api/requisition/get-items')
        ]);

        allFacilities = (facilitiesData.data || []).filter(f => allowedStatusIds.includes(f.status.status_id));
        facilityCategories = facilityCategoriesData || [];
        selectedItems = selectedItemsResponse.data?.selected_items || [];

        renderCategoryFilters();
        filterAndRenderItems();
        updateCartBadge();

        // Set up availability modal cleanup
        const availabilityModal = document.getElementById('singleFacilityAvailabilityModal');
        if (availabilityModal) {
          availabilityModal.addEventListener('hidden.bs.modal', function () {
            // Clean up calendar instance
            if (availabilityCalendarInstance && availabilityCalendarInstance.calendar) {
              try {
                availabilityCalendarInstance.calendar.destroy();
              } catch (e) {
                console.log('Calendar already destroyed');
              }
              availabilityCalendarInstance = null;
            }
          });
        }

        catalogItemsContainer.classList.remove("d-none");
      } catch (error) {
        console.error("Error initializing page:", error);
        showToast("Failed to initialize the page. Please try again.", "error");
      } finally {
        loadingIndicator.style.display = "none";
      }
    }

    function renderDynamicLegend() {
      const legendContainer = document.getElementById('dynamicLegend');
      if (!legendContainer || !formStatuses || Object.keys(formStatuses).length === 0) {
        if (legendContainer) {
          legendContainer.innerHTML = '<div class="text-muted small">Loading status colors...</div>';
        }
        return;
      }

      legendContainer.innerHTML = '';

      Object.entries(formStatuses).forEach(([statusName, colorCode]) => {
        const legendItem = document.createElement('div');
        legendItem.className = 'd-flex align-items-center';
        legendItem.innerHTML = `
          <div class="color-box me-2" style="background-color: ${colorCode}; width: 16px; height: 16px; border-radius: 3px;"></div>
          <small>${statusName}</small>
        `;
        legendContainer.appendChild(legendItem);
      });
    }

    function filterItems() {
      const allCategoriesCheckbox = document.getElementById("allCategories");
      const categoryCheckboxes = Array.from(document.querySelectorAll('.category-filter')).filter(cb => cb.id !== "allCategories");
      const subcategoryCheckboxes = Array.from(document.querySelectorAll('.subcategory-filter'));

      filteredItems = [...allFacilities].filter(f => allowedStatusIds.includes(f.status.status_id));

      if (statusFilter === "Available") {
        filteredItems = filteredItems.filter(f => f.status.status_id === 1);
      } else if (statusFilter === "Unavailable") {
        filteredItems = filteredItems.filter(f => f.status.status_id === 2);
      }

      if (allCategoriesCheckbox.checked) {
        return filteredItems;
      }

      const selectedCategories = categoryCheckboxes.filter(cb => cb.checked).map(cb => cb.value);
      const selectedSubcategories = subcategoryCheckboxes.filter(cb => cb.checked).map(cb => cb.value);

      if (selectedCategories.length === 0 && selectedSubcategories.length === 0) {
        filteredItems = [];
        return filteredItems;
      }

      filteredItems = filteredItems.filter(facility => {
        if (selectedSubcategories.length > 0 && facility.subcategory) {
          if (selectedSubcategories.includes(facility.subcategory.subcategory_id.toString())) {
            return true;
          }
        }
        if (selectedCategories.length > 0) {
          if (selectedCategories.includes(facility.category.category_id.toString())) {
            return true;
          }
        }
        return false;
      });

      return filteredItems;
    }

    function showFacilityAvailability(facilityId, facilityData) {
      currentFacilityId = facilityId;

      // Update modal info
      document.getElementById('facilityAvailabilityName').textContent = facilityData.facility_name;
      document.getElementById('facilityTitleText').textContent = facilityData.facility_name;
      document.getElementById('facilityCapacity').textContent = facilityData.facility_capacity;

      const imageContainer = document.getElementById('facilityAvailabilityImage');
      imageContainer.innerHTML = `
        <div class="facility-img-wrapper">
          <img src="${facilityData.facility_image}" 
               alt="${facilityData.facility_name}" 
               class="facility-img"
               onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
        </div>
      `;

      document.getElementById('facilityCategory').textContent = facilityData.facility_category;

      const statusBadge = document.getElementById('facilityStatusBadge');
      statusBadge.textContent = facilityData.facility_status;
      statusBadge.style.backgroundColor = facilityData.facility_status_color;
      statusBadge.style.color = '#fff';

      renderDynamicLegend();

      // Set up book now button
      const bookNowBtn = document.getElementById('bookNowBtn');
      if (bookNowBtn) {
        bookNowBtn.onclick = function() {
          if (currentFacilityId) {
            addToForm(currentFacilityId, 'facility');
            if (singleFacilityAvailabilityModal) {
              singleFacilityAvailabilityModal.hide();
            }
          }
        };
      }

      // Show modal
      if (singleFacilityAvailabilityModal) {
        singleFacilityAvailabilityModal.show();
        
        // Initialize calendar after modal is shown
        setTimeout(() => {
          initAvailabilityCalendar(facilityId);
        }, 100);
      }
    }

    function initAvailabilityCalendar(facilityId) {
      return new Promise(async (resolve, reject) => {
        try {
          // Destroy previous calendar if exists
          if (availabilityCalendarInstance && availabilityCalendarInstance.calendar) {
            availabilityCalendarInstance.calendar.destroy();
          }

          // Create new CalendarModule with facility filter
          availabilityCalendarInstance = new CalendarModule({
            isAdmin: false,
            apiEndpoint: `/api/requisition-forms/calendar-events?facility_id=${facilityId}`,
            containerId: 'facilityAvailabilityCalendar',
            miniCalendarContainerId: 'availabilityMiniCalendarDays',
            monthYearId: 'availabilityCurrentMonthYear',
            eventModalId: 'calendarEventModal'
          });

          // Override the loadCalendarEvents method
          const originalLoadEvents = availabilityCalendarInstance.loadCalendarEvents;
          availabilityCalendarInstance.loadCalendarEvents = async function () {
            try {
              const params = new URLSearchParams();
              params.append('facility_id', facilityId);

              const headers = {};
              if (this.config.isAdmin && this.config.adminToken) {
                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
              }

              const response = await fetch(
                `${this.config.apiEndpoint}?${params}`,
                { headers }
              );

              const result = await response.json();

              if (result.success && result.data) {
                this.allEvents = result.data
                  .filter(event => event != null)
                  .map(event => {
                    const statusName = event.extendedProps?.status;
                    const statusColor = this.statusColors[statusName] ||
                      event.extendedProps?.color ||
                      "#007bff";

                    return {
                      ...event,
                      color: statusColor,
                      extendedProps: {
                        ...event.extendedProps,
                        color: statusColor,
                      },
                    };
                  });

                this.applyFilters();
              } else {
                this.allEvents = [];
                this.applyFilters();
              }
            } catch (error) {
              console.error("Error loading calendar events:", error);
              this.allEvents = [];
            }
          };

          // Override applyFilters to only show events for this facility
          availabilityCalendarInstance.applyFilters = function () {
            console.log("=== FACILITY-SPECIFIC FILTER ===");

            if (!this.allEvents || !Array.isArray(this.allEvents)) {
              console.error("allEvents is not an array!");
              return;
            }

            const facilityEvents = this.allEvents.filter(event => {
              if (!event) return false;
              const eventFacilities = event.extendedProps?.facilities || [];
              return eventFacilities.some(f =>
                f.facility_id &&
                (f.facility_id.toString() === facilityId.toString())
              );
            });

            console.log(`Found ${facilityEvents.length} events for facility ${facilityId}`);

            const selectedStatuses = this.getSelectedStatuses();
            if (selectedStatuses.length > 0) {
              this.filteredEvents = facilityEvents.filter(event => {
                const eventStatus = event.extendedProps?.status;
                return selectedStatuses.includes(eventStatus);
              });
            } else {
              this.filteredEvents = facilityEvents;
            }

            console.log(`After status filter: ${this.filteredEvents.length} events`);
            this.updateCalendarDisplay();
          };

          await availabilityCalendarInstance.initialize();

          if (availabilityCalendarInstance.calendar) {
            availabilityCalendarInstance.calendar.changeView('timeGridWeek');
            availabilityCalendarInstance.forceCalendarProperRender();
          }

          resolve();
        } catch (error) {
          console.error('Failed to initialize availability calendar:', error);
          reject(error);
        }
      });
    }
  </script>
@endsection