@extends('layouts.app')

@section('title', 'Events Calendar')

@section('body_class', 'events-calendar-page')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}" />
    <style>
        main {
            background-image: url("{{ asset('assets/cpu-pic1.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 80vh;
            /* Full viewport height */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-header-bar {
            position: relative;
            z-index: 1030 !important;
            /* Higher than loading overlay */
        }

        .main-navbar {
            position: relative;
            z-index: 1025 !important;
            /* Between header and loading overlay */
        }

        /* Make the page take full available height */
        .events-calendar-page {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;

            /* Reduced padding */
            box-sizing: border-box;
            min-height: 0;
            /* Critical for flex children */
        }

        /* Main content wrapper fills remaining space */
        .calendar-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
            padding: 0 15px 15px 15px;
        }

        /* Container fills wrapper */
        .calendar-content-wrapper .container-fluid {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-height: 0;
            padding: 0;
            height: 100%;
            border-bottom-left-radius: 12px;
            /* Add bottom left radius */
            border-bottom-right-radius: 12px;
            /* Add bottom right radius */
        }

        /* Row fills container */
        .calendar-content-wrapper .row {
            flex: 1;
            display: flex;
            overflow: hidden;
            min-height: 0;
            margin: 0 -0.5rem;
            height: 100%;
        }

        /* Left column - fixed width, scrollable content */
        .col-lg-3.col-md-12 {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            width: auto;
            /* Change from 25% to auto */
            min-width: 280px;
            /* Minimum width to ensure readability */
            max-width: 300px;
            /* Maximum width to keep it compact */
            padding-right: 0.5rem;
            flex-shrink: 0;
            /* Prevent column from shrinking */
        }


        /* Right column - takes remaining width */
        .col-lg-9.col-md-12 {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            width: auto;
            /* Change from 75% to auto */
            flex: 1;
            /* Take remaining space */
            min-width: 0;
            /* Allow flex item to shrink below content size */
            padding-left: 0.5rem;
        }


        /* Hero section - compact */
        .events-calendar-header {
            flex-shrink: 0;
            padding: 0.75rem 1rem;
            /* Reduced padding */
            text-align: center;
            background: url('{{ asset("assets/cpu-pic1.jpg") }}') center center / cover no-repeat;
            position: relative;
            color: white;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            /* Reduced margin */
        }

        .events-calendar-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 8px;
        }

        .events-calendar-header * {
            position: relative;
            z-index: 1;
        }

        .events-calendar-header h1 {
            font-size: 1.5rem;
            /* Smaller font */
            margin-bottom: 0.1rem;
            line-height: 1.2;
        }

        .events-calendar-header p {
            font-size: 0.8rem;
            margin-bottom: 0;
            line-height: 1.2;
        }

        /* Left column scrollable area */
        .scrollable-left-column {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            padding-right: 4px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            /* Reduced gap */
        }

        /* Cards with minimal padding */
        .scrollable-left-column .card {
            margin-bottom: 0;
            flex-shrink: 0;
        }

        .scrollable-left-column .card-body {
            padding: 0.75rem;
            /* Reduced padding */
        }

        /* Mini calendar compact */
        .mini-calendar .d-flex.justify-content-between {
            margin-bottom: 0.5rem !important;
        }

        .mini-calendar .btn-sm {
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
        }

        .mini-calendar .month-year {
            font-size: 0.9rem;
        }

        .calendar-header .day-header {
            font-size: 0.7rem;
            padding: 0.2rem 0;
        }

        .calendar-day {
            font-size: 0.75rem;
            padding: 0.25rem !important;
        }

        /* Filter card */
        #facilityFilterList {
            max-height: 180px;
            /* Slightly reduced */
            overflow-y: auto;
        }

        /* Legend + Search Row - compact */
        .legend-search-row {
            margin-bottom: 0.5rem !important;
            flex-shrink: 0;
        }

        /* Legend card compact */
        .legend-search-row .card {
            margin-bottom: 0;
        }

        .legend-search-row .card-body {
            padding: 0.5rem 0.75rem;
        }

        /* Search bar compact */
        .legend-search-row .input-group-sm {
            transform: scale(0.95);
        }

        /* Main calendar card */
        .col-lg-9.col-md-12 .card.flex-grow-1 {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            margin-bottom: 0;
            /* Remove bottom margin */
        }

        .col-lg-9.col-md-12 .card.flex-grow-1 .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
            padding: 0.75rem;
            /* Reduced padding */
        }

        /* Calendar takes full space */
        #userFullCalendar {
            flex: 1;
            min-height: 0;
            height: 100% !important;
            width: 100% !important;
        }

        /* FullCalendar fixes - make it truly fill space */
        .fc {
            height: 100% !important;
            width: 100% !important;
        }

        .fc-view-harness {
            flex: 1 !important;
            min-height: 0 !important;
            height: 100% !important;
        }

        .fc-view {
            height: 100% !important;
        }

        /* Loading overlays */
        .calendar-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgb(255, 255, 255);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 30 !important;
            /* Lowered from 10 to ensure it's below header/navbar */
            border-radius: 8px;
            pointer-events: none;
            /* Allow clicks to pass through when hidden */
        }


        .calendar-loading-overlay:not(.d-none) {
            pointer-events: auto;
            /* Re-enable clicks when visible */
        }

        .calendar-loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0a336c;
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

        /* Mobile-specific styles */
        @media (max-width: 992px) {

            /* Hide the original right column on mobile */
            .col-lg-9.col-md-12 {
                display: none !important;
            }

            /* Style the mobile toggle */
            .mobile-calendar-toggle {
                display: block !important;
                margin-top: 10px;
            }

            .mobile-calendar-toggle .btn-group {
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .mobile-calendar-toggle .btn {
                padding: 12px 0;
                font-weight: 500;
            }

            /* Mobile events list */
            .mobile-events-list {
                margin-top: 10px;
            }

            .mobile-events-list .card {
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .mobile-events-list .card-header {
                background-color: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                padding: 12px 15px;
            }

            .mobile-events-list .card-header h6 {
                margin: 0;
                font-weight: 600;
            }

            .mobile-event-item {
                transition: transform 0.2s;
                border-left: 4px solid transparent;
            }

            .mobile-event-item:active {
                transform: scale(0.98);
                background-color: #f8f9fa;
            }

            /* Calendar event type indicator */
            .mobile-event-item[data-event-type="calendar_event"] {
                border-left-color: #28a745;
            }

            .mobile-event-item[data-event-type="requisition"] {
                border-left-color: var(--event-color, #007bff);
            }
        }

        /* Very small screens */
        @media (max-width: 480px) {
            .mobile-events-list .card-body {
                max-height: 350px !important;
            }

            .mobile-event-item .card-body {
                padding: 10px !important;
            }

            .mobile-event-item h6 {
                font-size: 0.9rem;
            }

            .mobile-event-item .small {
                font-size: 0.75rem;
            }
        }

        /* Mini calendar day styles */

        .calendar-day.today {
            background-color: #0a336c;
            color: white;
            border-radius: 50%;
        }

        /* SEARCHBAR STYLES */
        .search-result-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #searchResultsContainer {
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        #searchResultsContainer .border-bottom {
            border-color: #dee2e6 !important;
        }

        #backToCalendarBtn:hover {
            background-color: #f1f1f1 !important;
        }

        #dynamicLegend {
            scrollbar-width: none;
        }

        #dynamicLegend::-webkit-scrollbar {
            display: none;
        }

        #scrollLeftBtn:hover,
        #scrollRightBtn:hover {
            background-color: #f8f9fa !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }

        #eventSearchInput:focus,
        #eventSearchInput:focus-visible,
        .input-group:focus-within {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Simple padding adjustment - no negative margins needed */
        .col-lg-3.col-md-12 {
            padding-right: 0.25rem !important;
            /* Reduce from 0.5rem to 0.25rem */
        }

        .col-lg-9.col-md-12 {
            padding-left: 0.25rem !important;
            /* Reduce from 0.5rem to 0.25rem */
        }
    </style>
    <main>
        <div class="events-calendar-page">
            <div class="calendar-content-wrapper">
                <div class="container-fluid h-100">
                    <div class="row g-3 h-100 custom-gutter-row">
                        <!-- Left Column: Hero, Mini Calendar & Filters -->
                        <div class="col-lg-3 col-md-12 d-flex flex-column h-100">
                            <!-- HERO SECTION -->
                            <div class="events-calendar-header mb-2 rounded flex-shrink-0">
                                <h1>Events Calendar</h1>
                                <p>View all scheduled events and availability across facilities</p>
                            </div>

                            <!-- Scrollable content area -->
                            <div class="scrollable-left-column flex-grow-1 d-flex flex-column position-relative">
                                <!-- Mini Calendar Card with Loading Overlay -->
                                <div class="card mb-0 position-relative flex-shrink-0">
                                    <!-- Mini Calendar Loading Overlay - INSIDE the card -->
                                    <div id="miniCalendarLoadingOverlay" class="calendar-loading-overlay d-none">
                                        <div class="calendar-loading-spinner"></div>
                                        <div class="loading-text">Loading mini calendar...</div>
                                    </div>

                                    <div class="card-body">
                                        <div class="calendar-content">
                                            <div class="mini-calendar">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <button class="btn btn-light border-muted text-dark prev-month"
                                                        type="button">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </button>
                                                    <h6 class="mb-0 month-year" id="currentMonthYear"></h6>
                                                    <button class="btn btn-light border-muted text-dark next-month"
                                                        type="button">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </button>
                                                </div>
                                                <div class="calendar-header d-flex mb-2">
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        S
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        M
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        T
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        W
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        T
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        F
                                                    </div>
                                                    <div class="day-header text-center flex-fill small text-muted">
                                                        S
                                                    </div>
                                                </div>
                                                <div class="calendar-days" id="miniCalendarDays">
                                                    <!-- Days populated by CalendarModule -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Events Filter Card -->
                                <div class="card flex-grow-1">
                                    <div class="card-body d-flex flex-column h-100">
                                        <div class="calendar-content flex-grow-1 d-flex flex-column">
                                            <h6 class="fw-bold mb-3 flex-shrink-0">Event Filters</h6>
                                            <!-- Accordion Container -->
                                            <div class="accordion" id="eventFiltersAccordion">

                                                <!-- Filter by Facility Section -->
                                                <div class="accordion-item border-0">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button py-2 px-2" type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#filterFacilityCollapse" aria-expanded="true"
                                                            aria-controls="filterFacilityCollapse">
                                                            <span class="fw-semibold">Filter by Facility</span>
                                                        </button>
                                                    </h2>
                                                    <div id="filterFacilityCollapse"
                                                        class="accordion-collapse collapse show">
                                                        <div class="accordion-body p-2 pt-1 d-flex flex-column">
                                                            <div class="mb-2 small text-muted flex-shrink-0">
                                                                Select facilities to show events:
                                                            </div>

                                                            <!-- "All Facilities" checkbox -->
                                                            <div class="form-check mb-2 flex-shrink-0">
                                                                <input class="form-check-input facility-filter-checkbox"
                                                                    type="checkbox" value="all" id="filterAllFacilities"
                                                                    checked>
                                                                <label class="form-check-label fw-medium"
                                                                    for="filterAllFacilities">All
                                                                    Facilities</label>
                                                            </div>

                                                            <!-- Category/Subcategory Filter Container -->
                                                            <div id="facilityFilterList" class="overflow-auto"
                                                                style="max-height: 350px;">
                                                                <!-- Will be populated by JavaScript with nested structure -->
                                                                <div class="text-center py-3 text-muted">
                                                                    <div class="spinner-border spinner-border-sm me-2">
                                                                    </div>
                                                                    Loading facilities...
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Toggle View (visible only on mobile) -->
                        <div class="mobile-calendar-toggle d-none d-lg-none mb-3">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary active" id="showMiniCalendarBtn">
                                    <i class="bi bi-calendar3"></i> Calendar
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="showEventsListBtn">
                                    <i class="bi bi-list-check"></i> Events
                                </button>
                            </div>
                        </div>

                        <!-- Mobile Events List (visible only on mobile, hidden by default) -->
                        <div class="mobile-events-list d-none d-lg-none" id="mobileEventsList">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Events for <span id="mobileSelectedDate">today</span></h6>
                                </div>
                                <div class="card-body p-2" id="mobileEventsListContainer"
                                    style="max-height: 400px; overflow-y: auto;">
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-calendar-event display-4"></i>
                                        <p class="mt-2">Select a date to view events</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Legend and FullCalendar -->
                        <div class="col-lg-9 col-md-12 d-flex flex-column">
                            <!-- FullCalendar Loading Overlay -->
                            <div id="fullCalendarLoadingOverlay" class="calendar-loading-overlay d-none">
                                <div class="calendar-loading-spinner"></div>
                                <div class="loading-text">Loading calendar events...</div>
                            </div>

                            <!-- Legend + Search Row Wrapper -->
                            <div class="d-flex align-items-center gap-2 mb-1">

                                <!-- Legend Card -->
                                <div class="card flex-grow-1" style="margin-bottom: 0;">
                                    <!-- Remove mb-1 class, use inline style to ensure no margin -->
                                    <div class="card-body py-1">
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted small flex-shrink-0">
                                                <!-- Added me-3 back for spacing -->
                                                Filter by status:
                                            </span>

                                            <div class="position-relative flex-grow-1" style="min-width: 0;">
                                                <!-- Legend container - make it actually scrollable with hidden scrollbar -->
                                                <div class="d-flex gap-2 flex-nowrap px-2" id="dynamicLegend"
                                                    style="overflow-x: auto; overflow-y: hidden; white-space: nowrap; scrollbar-width: none; -ms-overflow-style: none; min-width: 0;">
                                                    <!-- Legend items will be rendered here -->
                                                </div>

                                                <!-- Left scroll button with Font Awesome icon -->
                                                <button id="scrollLeftBtn"
                                                    class="btn btn-sm btn-light border shadow-sm position-absolute top-50 start-0 translate-middle-y"
                                                    style="z-index: 10; display: none; margin-left: -12px; width: 24px; height: 24px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>

                                                <!-- Right scroll button with Font Awesome icon -->
                                                <button id="scrollRightBtn"
                                                    class="btn btn-sm btn-light border shadow-sm position-absolute top-50 end-0 translate-middle-y"
                                                    style="z-index: 10; display: none; margin-right: -12px; width: 24px; height: 24px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Search Bar -->
                                <div class="flex-shrink-0" style="width: 250px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" id="eventSearchInput" class="form-control border-start-0 ps-0"
                                            placeholder="Search by title or date..." autocomplete="off">
                                        <button class="btn btn-light border-0 text-secondary" type="button"
                                            id="clearSearchBtn" title="Clear search">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Main Calendar Card - SIMPLIFIED -->
                            <div class="card flex-grow-1 d-flex flex-column">
                                <div class="card-body p-3 d-flex flex-column position-relative">
                                    <!-- Search Results Container (initially hidden) -->
                                    <div id="searchResultsContainer"
                                        class="position-absolute top-0 start-0 w-100 h-100 bg-white p-4 d-none"
                                        style="z-index: 1000; overflow-y: auto;">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                            <button class="btn btn-sm border-0 bg-transparent" id="backToCalendarBtn">
                                                <i class="fa-solid fa-arrow-left me-1"></i>
                                                Back to Calendar
                                            </button>
                                            <p class="mb-0">
                                                <i class="bi bi-search"></i>
                                                Search Results
                                            </p>

                                        </div>
                                        <div id="searchResultsList" class="mt-3">
                                            <!-- Results will be populated here -->
                                        </div>
                                    </div>

                                    <!-- Calendar Container -->
                                    <div id="userFullCalendar" class="flex-grow-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Event Modal (will be dynamically injected by CalendarModule) -->
        <div id="calendarEventModalContainer"></div>
    </main>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="{{ asset('js/public/calendar.js') }}"></script>
    <script>
        // Global variables
        let eventCalendarInstance = null;
        let eventCalendarInitialized = false;
        let facilitiesLoaded = false;
        let miniCalendarLoaded = false;
        let fullCalendarLoaded = false;
        let activeStatusFilters = [];

        function updateScrollButtons() {
            const legend = document.getElementById('dynamicLegend');
            const scrollLeftBtn = document.getElementById('scrollLeftBtn');
            const scrollRightBtn = document.getElementById('scrollRightBtn');

            // Guard clause - if any element is missing, exit
            if (!legend || !scrollLeftBtn || !scrollRightBtn) return;

            scrollLeftBtn.style.display = legend.scrollLeft > 0 ? 'flex' : 'none';
            scrollRightBtn.style.display = legend.scrollLeft + legend.clientWidth < legend.scrollWidth ? 'flex' : 'none';
        }

        function setupScrollButtons() {
            const legend = document.getElementById('dynamicLegend');
            const scrollLeftBtn = document.getElementById('scrollLeftBtn');
            const scrollRightBtn = document.getElementById('scrollRightBtn');

            if (!legend || !scrollLeftBtn || !scrollRightBtn) return;

            // Remove any existing event listeners by cloning and replacing
            const newLeftBtn = scrollLeftBtn.cloneNode(true);
            const newRightBtn = scrollRightBtn.cloneNode(true);
            scrollLeftBtn.parentNode.replaceChild(newLeftBtn, scrollLeftBtn);
            scrollRightBtn.parentNode.replaceChild(newRightBtn, scrollRightBtn);

            // Add click handlers
            newLeftBtn.addEventListener('click', function () {
                // Scroll by 80% of the visible width
                const scrollAmount = legend.clientWidth * 0.8;
                legend.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });

                // Update button states after scrolling
                setTimeout(updateScrollButtons, 300);
            });

            newRightBtn.addEventListener('click', function () {
                // Scroll by 80% of the visible width
                const scrollAmount = legend.clientWidth * 0.8;
                legend.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });

                // Update button states after scrolling
                setTimeout(updateScrollButtons, 300);
            });

            // Mouse wheel scrolling WITHOUT shift key requirement
            legend.addEventListener('wheel', function (e) {
                // Check if there's horizontal scrollable content
                const canScrollLeft = this.scrollLeft > 0;
                const canScrollRight = this.scrollLeft + this.clientWidth < this.scrollWidth;

                // Only handle horizontal scrolling if content can scroll in that direction
                if ((e.deltaY > 0 && canScrollRight) || (e.deltaY < 0 && canScrollLeft)) {
                    e.preventDefault(); // Prevent page from scrolling vertically

                    // Scroll horizontally based on wheel movement
                    this.scrollBy({
                        left: e.deltaY > 0 ? 100 : -100,
                        behavior: 'auto'
                    });

                    // Update button states after wheel scroll
                    updateScrollButtons();
                }
            }, { passive: false }); // passive: false allows preventDefault()
        }


        document.addEventListener("DOMContentLoaded", function () {

            const legend = document.getElementById('dynamicLegend');

            // Only set up event listener if legend exists
            if (legend) {
                legend.addEventListener('scroll', updateScrollButtons);
                updateScrollButtons(); // Initial call to set correct button states

                // Setup the scroll buttons click handlers
                setupScrollButtons();

                // Add a small delay to ensure content is rendered
                setTimeout(updateScrollButtons, 500);
            }

            // Initialize the calendar
            initEventsCalendar();

            // Single resize handler with proper throttling
            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    if (eventCalendarInstance && eventCalendarInstance.calendar) {
                        eventCalendarInstance.calendar.updateSize();
                    }
                    // Also update scroll buttons on resize
                    updateScrollButtons();
                }, 100);
            });
        });

        function loadMobileEventsList() {
            const container = document.getElementById('mobileEventsListContainer');
            const selectedDateSpan = document.getElementById('mobileSelectedDate');

            if (!eventCalendarInstance || !eventCalendarInstance.filteredEvents) {
                container.innerHTML = '<div class="text-center py-4 text-muted">No events loaded</div>';
                return;
            }

            // Get today's date or currently selected date
            const today = new Date();
            const events = getEventsForDate(today);

            selectedDateSpan.textContent = today.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });

            displayMobileEvents(events);
        }

        function loadMobileEventsForDate(day, monthYear) {
            const container = document.getElementById('mobileEventsListContainer');
            const selectedDateSpan = document.getElementById('mobileSelectedDate');

            // Parse the selected date
            const [month, year] = monthYear.split(' ');
            const dateStr = `${month} ${day}, ${year}`;
            const selectedDate = new Date(dateStr);

            selectedDateSpan.textContent = dateStr;

            const events = getEventsForDate(selectedDate);
            displayMobileEvents(events);
        }

        function getEventsForDate(date) {
            if (!eventCalendarInstance || !eventCalendarInstance.filteredEvents) return [];

            const dateStr = date.toISOString().split('T')[0];

            return eventCalendarInstance.filteredEvents.filter(event => {
                if (!event || !event.start) return false;

                const eventStart = new Date(event.start);
                const eventStartStr = eventStart.toISOString().split('T')[0];
                const eventEndStr = event.end ? new Date(event.end).toISOString().split('T')[0] : eventStartStr;

                return dateStr >= eventStartStr && dateStr <= eventEndStr;
            });
        }

        function displayMobileEvents(events) {
            const container = document.getElementById('mobileEventsListContainer');

            if (events.length === 0) {
                container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <p class="mt-2 text-muted">No events scheduled</p>
                </div>
            `;
                return;
            }

            // Sort events by time
            events.sort((a, b) => new Date(a.start) - new Date(b.start));

            let html = '';
            events.forEach(event => {
                const eventType = event.extendedProps?.eventType || 'requisition';
                const startTime = event.start ? new Date(event.start).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : 'All day';

                const endTime = event.end ? new Date(event.end).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }) : '';

                const facilities = event.extendedProps?.facilities || [];
                const facilityNames = facilities.map(f => f.name).join(', ');

                html += `
                <div class="card mb-2 mobile-event-item" data-event-id="${event.id}" style="cursor: pointer;">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${event.title || 'Untitled'}</h6>
                                <div class="small">
                                    <span class="badge ${eventType === 'calendar_event' ? 'bg-success' : ''}" 
                                          style="${eventType !== 'calendar_event' ? 'background-color: ' + (event.color || '#007bff') : ''}">
                                        ${eventType === 'calendar_event' ? 'Calendar' : (event.extendedProps?.status || 'Event')}
                                    </span>
                                    <span class="ms-2">
                                        <i class="bi bi-clock"></i> ${startTime} ${endTime ? '- ' + endTime : ''}
                                    </span>
                                </div>
                                ${facilityNames ? `
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-building"></i> ${facilityNames}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;

            // Add click handlers
            document.querySelectorAll('.mobile-event-item').forEach(item => {
                item.addEventListener('click', function () {
                    const eventId = this.dataset.eventId;
                    const event = eventCalendarInstance.filteredEvents.find(e => e.id === eventId);
                    if (event && eventCalendarInstance) {
                        eventCalendarInstance.showEventModal({ extendedProps: event.extendedProps, ...event });
                    }
                });
            });
        }

        function initEventsCalendar() {
            // Show loading for both calendars
            showMiniCalendarLoading(true);
            showFullCalendarLoading(true);

            // Reset loaded flags
            miniCalendarLoaded = false;
            fullCalendarLoaded = false;

            // Initialize CalendarModule for public view
            eventCalendarInstance = new CalendarModule({
                isAdmin: false,
                apiEndpoint: '/api/requisition-forms/calendar-events',
                calendarEventsEndpoint: '/api/calendar-events',
                containerId: 'userFullCalendar',
                miniCalendarContainerId: 'miniCalendarDays',
                monthYearId: 'currentMonthYear',
                eventModalId: 'calendarEventModal',
                searchResultsContainerId: 'searchResultsContainer',
                searchResultsListId: 'searchResultsList',
                searchInputId: 'eventSearchInput',
                onMiniCalendarInitialized: function () {
                    miniCalendarLoaded = true;
                    showMiniCalendarLoading(false);
                },
                onFullCalendarInitialized: function () {
                    fullCalendarLoaded = true;
                    showFullCalendarLoading(false);
                }
            });

            // Load facilities for the filter
            loadFacilitiesForCalendar().then(() => {
                return eventCalendarInstance.initialize();
            }).then(() => {
                eventCalendarInitialized = true;

                if (window.innerWidth < 992 && eventCalendarInstance && eventCalendarInstance.calendar) {
                    setTimeout(() => {
                        eventCalendarInstance.calendar.updateSize();
                    }, 300);
                }
            }).catch(error => {
                console.error('Error:', error);
                eventCalendarInstance.initialize().catch(initError => {
                    console.error('Calendar initialization failed:', initError);
                });
            });

            // Load and display status legend
            loadFormStatuses();
        }

        function showMiniCalendarLoading(show) {
            const overlay = document.getElementById('miniCalendarLoadingOverlay');
            if (!overlay) return;

            if (show) {
                overlay.classList.remove('d-none');
                overlay.style.display = 'flex';
                overlay.style.opacity = '1';
            } else {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.classList.add('d-none');
                    overlay.style.display = 'none';
                }, 300);
            }
        }

        function showFullCalendarLoading(show) {
            const overlay = document.getElementById('fullCalendarLoadingOverlay');
            if (!overlay) return;

            if (show) {
                overlay.classList.remove('d-none');
                overlay.style.display = 'flex';
                overlay.style.opacity = '1';
            } else {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.classList.add('d-none');
                    overlay.style.display = 'none';
                }, 300);
            }
        }
        async function loadFormStatuses() {
            try {
                const response = await fetch('/api/form-statuses');
                if (response.ok) {
                    const statuses = await response.json();
                    if (Array.isArray(statuses)) {
                        const activeStatuses = statuses.filter(status => status.status_id <= 6);
                        renderDynamicLegend(activeStatuses);
                        updateScrollButtons();
                        activeStatusFilters = activeStatuses.map(s => s.status_name);
                        syncFilterCheckboxes();
                    }
                }
            } catch (error) {
                console.error('Failed to load form statuses:', error);
            }
        }

        function renderDynamicLegend(activeStatuses) {
            const legendContainer = document.getElementById('dynamicLegend');
            if (!legendContainer || !activeStatuses || activeStatuses.length === 0) return;

            legendContainer.innerHTML = '';

            activeStatuses.forEach(status => {
                const pill = document.createElement('span');
                pill.className = 'badge rounded-pill d-inline-flex align-items-center px-2 py-1 small';
                pill.style.cursor = 'pointer';
                pill.style.backgroundColor = status.color_code;
                pill.style.color = '#fff';
                pill.style.opacity = '1';
                pill.style.transition = 'opacity 0.2s ease';
                pill.setAttribute('data-status', status.status_name);
                pill.setAttribute('role', 'button');
                pill.setAttribute('aria-pressed', 'true');
                pill.innerHTML = `${status.status_name}<span class="ms-2 small opacity-75">✓</span>`;

                pill.addEventListener('click', function (e) {
                    e.preventDefault();
                    const statusName = this.getAttribute('data-status');
                    const isActive = this.getAttribute('aria-pressed') === 'true';

                    if (isActive) {
                        this.style.opacity = '0.4';
                        this.setAttribute('aria-pressed', 'false');
                        this.querySelector('span').innerHTML = '✕';
                        activeStatusFilters = activeStatusFilters.filter(s => s !== statusName);
                    } else {
                        this.style.opacity = '1';
                        this.setAttribute('aria-pressed', 'true');
                        this.querySelector('span').innerHTML = '✓';
                        if (!activeStatusFilters.includes(statusName)) {
                            activeStatusFilters.push(statusName);
                        }
                    }

                    updateFilterCheckboxes(statusName, !isActive);
                    if (eventCalendarInstance && eventCalendarInstance.calendar) {
                        applyStatusFilters();
                    }
                });

                legendContainer.appendChild(pill);
                updateScrollButtons();
            });
        }

        function updateFilterCheckboxes(statusName, checked) {
            const checkboxes = document.querySelectorAll('.event-filter-checkbox');
            checkboxes.forEach(cb => {
                if (cb.value === statusName) {
                    cb.checked = checked;
                    const event = new Event('change', { bubbles: true });
                    cb.dispatchEvent(event);
                }
            });
        }

        function syncFilterCheckboxes() {
            const checkboxes = document.querySelectorAll('.event-filter-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = true;
            });
        }

        /**
         * Update applyStatusFilters to work with the new structure
         * Now properly handles "no facilities selected" vs "all facilities selected"
         */
        function applyStatusFilters() {
            if (!eventCalendarInstance) return;

            const selectedStatuses = activeStatusFilters;
            const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');

            // Get selected facility IDs from checked facility checkboxes
            let selectedFacilityIds = [];
            let filterMode = 'all'; // 'all', 'none', or 'specific'

            if (allFacilitiesCheckbox?.checked) {
                // "All Facilities" is checked - show ALL events (no facility filtering)
                filterMode = 'all';
                selectedFacilityIds = [];
            } else {
                // Get all checked facility checkboxes
                selectedFacilityIds = Array.from(document.querySelectorAll('.facility-checkbox:checked'))
                    .map(cb => cb.value);

                // If no facilities are checked, show NO events
                if (selectedFacilityIds.length === 0) {
                    filterMode = 'none';
                } else {
                    // Some facilities are checked - show only those
                    filterMode = 'specific';
                }
            }

            if (eventCalendarInstance.allEvents) {
                const validEvents = eventCalendarInstance.allEvents.filter(e => e != null);

                eventCalendarInstance.filteredEvents = validEvents.filter(event => {
                    if (!event) return false;

                    const eventType = event.extendedProps?.eventType || event.eventType || "requisition";

                    // Calendar events are always shown regardless of facility filters
                    if (eventType === "calendar_event") return true;

                    // Apply status filter
                    const eventStatus = event.extendedProps?.status;
                    if (!selectedStatuses.includes(eventStatus)) return false;

                    // Apply facility filter based on mode
                    if (filterMode === 'none') {
                        // No facilities selected - hide all requisition events
                        return false;
                    }

                    if (filterMode === 'specific') {
                        // Specific facilities selected - only show events matching those facilities
                        const eventFacilities = event.extendedProps?.facilities || [];
                        const hasSelectedFacility = eventFacilities.some(f =>
                            selectedFacilityIds.includes(String(f.facility_id))
                        );
                        if (!hasSelectedFacility) return false;
                    }

                    // filterMode === 'all' - show all events (no facility filtering)
                    return true;
                });

                eventCalendarInstance.updateCalendarDisplay();

                // Optional: Show a message when no facilities are selected
                if (filterMode === 'none') {
                    console.log('No facilities selected - hiding all requisition events');
                    // You could also show a toast notification here
                }
            }
        }


        /**
         * Load and organize facilities by category and subcategory
         */
        async function loadFacilitiesForCalendar() {
            try {
                const response = await fetch('/api/facilities');
                const result = await response.json();

                // Get facilities array from response
                const facilities = result.data || result;

                const facilityFilterList = document.getElementById('facilityFilterList');
                if (!facilityFilterList) return;

                if (!Array.isArray(facilities) || facilities.length === 0) {
                    facilityFilterList.innerHTML = '<div class="text-muted small p-2">No facilities available</div>';
                    return;
                }

                // Organize facilities by category and subcategory
                const categorizedData = organizeFacilitiesByCategory(facilities);

                // Render the categorized filter list
                renderCategorizedFilters(categorizedData, facilityFilterList);

                // Setup event listeners for the new structure
                setupCategorizedFilterListeners();

                // Initialize all checkboxes to checked state
                initializeFilterCheckboxes();

                facilitiesLoaded = true;

            } catch (error) {
                console.error('Error loading facilities for calendar:', error);
                const facilityFilterList = document.getElementById('facilityFilterList');
                if (facilityFilterList) {
                    facilityFilterList.innerHTML = '<div class="text-danger small p-2">Failed to load facilities</div>';
                }
                throw error;
            }
        }

        /**
         * Organize facilities into category → subcategory → facilities structure
         */
        function organizeFacilitiesByCategory(facilities) {
            const categorized = {};

            facilities.forEach(facility => {
                const category = facility.category || {};
                const subcategory = facility.subcategory || {};

                const categoryId = category.category_id || 'uncategorized';
                const categoryName = category.category_name || 'Uncategorized';
                const subcategoryId = subcategory.subcategory_id || 'no-subcategory';
                const subcategoryName = subcategory.subcategory_name || 'General';

                // Initialize category if not exists
                if (!categorized[categoryId]) {
                    categorized[categoryId] = {
                        id: categoryId,
                        name: categoryName,
                        subcategories: {}
                    };
                }

                // Initialize subcategory if not exists
                if (!categorized[categoryId].subcategories[subcategoryId]) {
                    categorized[categoryId].subcategories[subcategoryId] = {
                        id: subcategoryId,
                        name: subcategoryName,
                        facilities: []
                    };
                }

                // Add facility to subcategory
                categorized[categoryId].subcategories[subcategoryId].facilities.push({
                    id: facility.facility_id || facility.id,
                    name: facility.facility_name || facility.name,
                    original: facility
                });
            });

            return categorized;
        }


        /**
         * Render the categorized filter list with Bootstrap accordion
         */
        function renderCategorizedFilters(categorizedData, container) {
            let html = '';
            let categoryIndex = 0;

            Object.values(categorizedData).forEach(category => {
                const categoryId = `cat-${category.id}-${categoryIndex}`;

                html += `
                                                                <div class="category-group mb-2">
                                                                    <div class="category-header">
                                                                        <div class="d-flex align-items-center">
                                                                            <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" 
                                                                                    type="button" 
                                                                                    data-bs-toggle="collapse" 
                                                                                    data-bs-target="#${categoryId}-subcats"
                                                                                    aria-expanded="true">
                                                                                <i class="bi bi-chevron-down"></i>
                                                                            </button>
                                                                            <div class="form-check flex-grow-1">
                                                                                <input class="form-check-input category-checkbox" 
                                                                                       type="checkbox" 
                                                                                       value="${category.id}"
                                                                                       id="cat-${category.id}"
                                                                                       data-category-id="${category.id}"
                                                                                       checked>
                                                                                <label class="form-check-label fw-semibold" for="cat-${category.id}">
                                                                                    ${category.name}
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="collapse show ps-4" id="${categoryId}-subcats">
                                                            `;

                // Add subcategories
                Object.values(category.subcategories).forEach(subcategory => {
                    const subcatId = `${categoryId}-sub-${subcategory.id}`;

                    html += `
                                                                    <div class="subcategory-group mb-2">
                                                                        <div class="d-flex align-items-center">
                                                                            <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" 
                                                                                    type="button" 
                                                                                    data-bs-toggle="collapse" 
                                                                                    data-bs-target="#${subcatId}-facilities"
                                                                                    aria-expanded="true">
                                                                                <i class="bi bi-chevron-down"></i>
                                                                            </button>
                                                                            <div class="form-check flex-grow-1">
                                                                                <input class="form-check-input subcategory-checkbox" 
                                                                                       type="checkbox" 
                                                                                       value="${subcategory.id}"
                                                                                       id="sub-${subcategory.id}"
                                                                                       data-category-id="${category.id}"
                                                                                       data-subcategory-id="${subcategory.id}"
                                                                                       checked>
                                                                                <label class="form-check-label fw-medium small" for="sub-${subcategory.id}">
                                                                                    ${subcategory.name}
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        <div class="collapse show ps-4" id="${subcatId}-facilities">
                                                                            <div class="facilities-list">
                                                                `;

                    // Add facilities
                    subcategory.facilities.forEach(facility => {
                        const facilityId = facility.id;
                        const facilityName = facility.name;
                        const displayName = facilityName.length > 30 ?
                            facilityName.substring(0, 30) + '...' : facilityName;

                        html += `
                                                                        <div class="form-check mb-1">
                                                                            <input class="form-check-input facility-checkbox individual-facility" 
                                                                                   type="checkbox" 
                                                                                   value="${facilityId}"
                                                                                   id="fac-${facilityId}"
                                                                                   data-category-id="${category.id}"
                                                                                   data-subcategory-id="${subcategory.id}"
                                                                                   data-facility-name="${facilityName}"
                                                                                   checked>
                                                                            <label class="form-check-label small" for="fac-${facilityId}" 
                                                                                   title="${facilityName}">
                                                                                ${displayName}
                                                                            </label>
                                                                        </div>
                                                                    `;
                    });

                    html += `
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                `;
                });

                html += `
                                                                    </div>
                                                                </div>
                                                            `;

                categoryIndex++;
            });

            container.innerHTML = html;
        }

        /**
         * Initialize all checkboxes to checked state
         */
        function initializeFilterCheckboxes() {
            // Check all facility checkboxes
            document.querySelectorAll('.facility-checkbox').forEach(cb => {
                cb.checked = true;
            });

            // Update subcategory states
            document.querySelectorAll('.subcategory-checkbox').forEach(cb => {
                const subcategoryId = cb.dataset.subcategoryId;
                updateSubcategoryCheckboxState(subcategoryId);
            });

            // Update category states
            document.querySelectorAll('.category-checkbox').forEach(cb => {
                const categoryId = cb.dataset.categoryId;
                updateCategoryCheckboxState(categoryId);
            });

            // Update All Facilities checkbox
            updateAllFacilitiesCheckbox();
        }

        /**
         * Setup event listeners for categorized filters
         */
        function setupCategorizedFilterListeners() {
            const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');

            // Category checkboxes
            document.querySelectorAll('.category-checkbox').forEach(cb => {
                // Remove existing listeners by cloning
                const newCb = cb.cloneNode(true);
                cb.parentNode.replaceChild(newCb, cb);

                newCb.addEventListener('change', function (e) {
                    e.stopPropagation(); // Prevent event bubbling
                    const categoryId = this.dataset.categoryId;
                    const isChecked = this.checked;

                    // Check/uncheck all subcategories in this category
                    document.querySelectorAll(`.subcategory-checkbox[data-category-id="${categoryId}"]`).forEach(subCb => {
                        subCb.checked = isChecked;
                        // Update facilities under this subcategory
                        const subcategoryId = subCb.dataset.subcategoryId;
                        document.querySelectorAll(`.facility-checkbox[data-subcategory-id="${subcategoryId}"]`).forEach(facCb => {
                            facCb.checked = isChecked;
                        });
                    });

                    // Reset indeterminate state
                    this.indeterminate = false;

                    updateAllFacilitiesCheckbox();
                    if (eventCalendarInstance) {
                        eventCalendarInstance.handleFacilityFilterChange();
                    }
                });
            });

            // Subcategory checkboxes
            document.querySelectorAll('.subcategory-checkbox').forEach(cb => {
                // Remove existing listeners by cloning
                const newCb = cb.cloneNode(true);
                cb.parentNode.replaceChild(newCb, cb);

                newCb.addEventListener('change', function (e) {
                    e.stopPropagation(); // Prevent event bubbling
                    const subcategoryId = this.dataset.subcategoryId;
                    const categoryId = this.dataset.categoryId;
                    const isChecked = this.checked;

                    // Check/uncheck all facilities in this subcategory
                    document.querySelectorAll(`.facility-checkbox[data-subcategory-id="${subcategoryId}"]`).forEach(facCb => {
                        facCb.checked = isChecked;
                    });

                    // Reset indeterminate state
                    this.indeterminate = false;

                    // Update parent category checkbox state
                    updateCategoryCheckboxState(categoryId);
                    updateAllFacilitiesCheckbox();

                    if (eventCalendarInstance) {
                        eventCalendarInstance.handleFacilityFilterChange();
                    }
                });
            });

            // Facility checkboxes
            document.querySelectorAll('.facility-checkbox').forEach(cb => {
                // Remove existing listeners by cloning
                const newCb = cb.cloneNode(true);
                cb.parentNode.replaceChild(newCb, cb);

                newCb.addEventListener('change', function (e) {
                    e.stopPropagation(); // Prevent event bubbling
                    const subcategoryId = this.dataset.subcategoryId;
                    const categoryId = this.dataset.categoryId;

                    // Update subcategory checkbox state
                    updateSubcategoryCheckboxState(subcategoryId);
                    // Update category checkbox state
                    updateCategoryCheckboxState(categoryId);
                    updateAllFacilitiesCheckbox();

                    if (eventCalendarInstance) {
                        eventCalendarInstance.handleFacilityFilterChange();
                    }
                });
            });

            // All Facilities checkbox
            if (allFacilitiesCheckbox) {
                // Remove existing listeners by cloning
                const newAllCb = allFacilitiesCheckbox.cloneNode(true);
                allFacilitiesCheckbox.parentNode.replaceChild(newAllCb, allFacilitiesCheckbox);

                newAllCb.addEventListener('change', function () {
                    const isChecked = this.checked;

                    if (isChecked) {
                        // Check ALL checkboxes when "All Facilities" is checked
                        document.querySelectorAll('.category-checkbox, .subcategory-checkbox, .facility-checkbox').forEach(cb => {
                            cb.checked = true;
                            cb.indeterminate = false;
                        });
                    } else {
                        // Uncheck ALL checkboxes when "All Facilities" is unchecked
                        document.querySelectorAll('.category-checkbox, .subcategory-checkbox, .facility-checkbox').forEach(cb => {
                            cb.checked = false;
                            cb.indeterminate = false;
                        });
                    }

                    if (eventCalendarInstance) {
                        eventCalendarInstance.handleFacilityFilterChange();
                    }
                });
            }
        }

        /**
         * Update category checkbox based on its subcategories state
         */
        function updateCategoryCheckboxState(categoryId) {
            const subcategoryCheckboxes = document.querySelectorAll(`.subcategory-checkbox[data-category-id="${categoryId}"]`);
            const categoryCheckbox = document.querySelector(`.category-checkbox[data-category-id="${categoryId}"]`);

            if (!categoryCheckbox || subcategoryCheckboxes.length === 0) return;

            const allChecked = Array.from(subcategoryCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(subcategoryCheckboxes).some(cb => cb.checked);

            categoryCheckbox.checked = allChecked;
            categoryCheckbox.indeterminate = !allChecked && someChecked;
        }

        /**
         * Update subcategory checkbox based on its facilities state
         */
        function updateSubcategoryCheckboxState(subcategoryId) {
            const facilityCheckboxes = document.querySelectorAll(`.facility-checkbox[data-subcategory-id="${subcategoryId}"]`);
            const subcategoryCheckbox = document.querySelector(`.subcategory-checkbox[data-subcategory-id="${subcategoryId}"]`);

            if (!subcategoryCheckbox || facilityCheckboxes.length === 0) return;

            const allChecked = Array.from(facilityCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(facilityCheckboxes).some(cb => cb.checked);

            subcategoryCheckbox.checked = allChecked;
            subcategoryCheckbox.indeterminate = !allChecked && someChecked;
        }

        /**
         * Update All Facilities checkbox based on individual selections
         */
        function updateAllFacilitiesCheckbox() {
            const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');
            const facilityCheckboxes = document.querySelectorAll('.facility-checkbox');

            if (!allFacilitiesCheckbox || facilityCheckboxes.length === 0) return;

            const allChecked = Array.from(facilityCheckboxes).every(cb => cb.checked);
            const anyChecked = Array.from(facilityCheckboxes).some(cb => cb.checked);

            if (allChecked) {
                // All facilities are checked -> check "All Facilities"
                allFacilitiesCheckbox.checked = true;
                allFacilitiesCheckbox.indeterminate = false;
            } else if (!anyChecked) {
                // No facilities are checked -> uncheck "All Facilities"
                allFacilitiesCheckbox.checked = false;
                allFacilitiesCheckbox.indeterminate = false;
            } else {
                // Some facilities are checked -> indeterminate state
                allFacilitiesCheckbox.checked = false;
                allFacilitiesCheckbox.indeterminate = true;
            }
        }
        // Fallback function for legacy compatibility
        window.hideCalendarLoadingOverlay = function () {
            showMiniCalendarLoading(false);
            showFullCalendarLoading(false);
        };

        // Add this after your existing JavaScript
        function adjustCalendarHeight() {
            const mainElement = document.querySelector('main');
            const header = document.querySelector('.top-header-bar');
            const navbar = document.querySelector('.main-navbar');
            const footer = document.querySelector('.footer-container');
            const eventsCalendarPage = document.querySelector('.events-calendar-page');

            if (!mainElement || !eventsCalendarPage) return;

            // Calculate total header height (top header + navbar)
            const headerHeight = (header?.offsetHeight || 0) + (navbar?.offsetHeight || 0);
            const footerHeight = footer?.offsetHeight || 0;

            // Set main to fill viewport minus header and footer
            const viewportHeight = window.innerHeight;
            const availableHeight = viewportHeight - headerHeight - footerHeight;

            mainElement.style.height = availableHeight + 'px';
            mainElement.style.minHeight = availableHeight + 'px';
            mainElement.style.maxHeight = availableHeight + 'px';

            // Force calendar to update its size
            if (eventCalendarInstance && eventCalendarInstance.calendar) {
                setTimeout(() => {
                    eventCalendarInstance.calendar.updateSize();
                }, 50);
            }
        }

        // Call on load and resize
        window.addEventListener('load', function () {
            adjustCalendarHeight();

            // Small delay for any dynamic content
            setTimeout(adjustCalendarHeight, 100);
            setTimeout(adjustCalendarHeight, 300);
        });

        window.addEventListener('resize', function () {
            // Debounce resize events
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(adjustCalendarHeight, 100);
        });

        // Also call when filters expand/collapse (they might change height)
        document.addEventListener('shown.bs.collapse', adjustCalendarHeight);
        document.addEventListener('hidden.bs.collapse', adjustCalendarHeight);
    </script>
@endsection