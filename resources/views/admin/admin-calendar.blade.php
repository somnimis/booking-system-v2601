{{-- admin-calendar.blade.php --}}
@extends('layouts.admin')
@section('title', 'Admin Calendar')
@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/public/responsive-styles.css') }}">

    <style>
        /* Minimal custom styles that aren't in external CSS */
        .reservation-card:hover,
        .calendar-event-card:hover {
            background-color: #f0f0f0 !important;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .refresh-btn {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }

        .refresh-btn:hover {
            color: var(--bs-dark);
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Fix FullCalendar initialization */
        #calendar {
            height: 550px !important;
            min-height: 550px !important;
            width: 100% !important;
        }

        .card.flex-grow-1 {
            min-height: 600px;
        }

        .card-body.p-3.d-flex.flex-column {
            min-height: 550px;
        }

        #deleteCalendarEventModal .modal-content {
            border-radius: 12px;
            overflow: hidden;
        }

        #deleteCalendarEventModal .modal-header {
            padding: 1.5rem 1.5rem 0;
        }

        #deleteCalendarEventModal .modal-body {
            padding: 0 1.5rem;
        }

        #deleteCalendarEventModal .modal-footer {
            padding: 0 1.5rem 1.5rem;
        }

        #deleteCalendarEventModal .btn-danger {
            min-width: 120px;
        }

        .fc .fc-toolbar-chunk .fc-button:focus,
        .fc .fc-toolbar-chunk .fc-button:active {
            outline: none !important;
            box-shadow: none !important;
        }

        .fc .fc-toolbar-chunk .fc-button {
            background-color: #ffffff !important;
            color: #6c757d !important;
            border: none !important;
            font-weight: 500;
            border-radius: 6px !important;
        }

        .fc .fc-toolbar-chunk .fc-button:hover {
            background-color: #f8f9fa !important;
            color: #495057 !important;
            border: none !important;
        }

        .fc .fc-toolbar-chunk .fc-button.fc-button-active {
            background-color: #4272b1ff !important;
            color: #ffffffff !important;
            border: none !important;
        }

        .fc .fc-today-button {
            text-transform: capitalize !important;
        }

        .modal-xl {
            max-width: 1000px;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #224d9c 0%, #3e6fca 100%);
        }

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 0.5rem;
        }

        .step.active .step-circle {
            background: #224d9c;
            border-color: #224d9c;
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        .step.active .step-label {
            color: #224d9c;
            font-weight: 600;
        }

        .facilities-grid,
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 250px;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .calendar-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .calendar-header-bar h4 {
            margin: 0;
        }

        .open-calendar-btn {
            padding: 0.375rem 1rem;
        }

        #facilityFilterList .facility-item {
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        #facilityFilterList .facility-item:last-child {
            border-bottom: none;
        }

        #facilityFilterList .facility-item .form-check-label {
            font-size: 0.85rem;
            cursor: pointer;
            width: 100%;
            padding: 2px 0;
        }

        #facilityFilterList .facility-item .form-check-input:checked+.form-check-label {
            font-weight: bold;
            color: #004183;
        }

        #facilityFilterList .facility-badge {
            font-size: 0.7rem;
            padding: 1px 4px;
            border-radius: 3px;
        }

        .pagination {
            margin-bottom: 0;
        }

        .page-item.active .page-link {
            background-color: #4272b1ff;
            border-color: #4272b1ff;
            color: white;
        }

        .page-link {
            color: #4272b1ff;
            border: 1px solid #dee2e6;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>

    <main id="main">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- Left Column: Mini Calendar & Filters -->
                <div class="col-lg-3">
                    <!-- Mini Calendar Card -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mini-calendar">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <button class="btn btn-sm btn-secondary prev-month" type="button">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <h6 class="mb-0 month-year" id="currentMonthYear"></h6>
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
                                <div class="calendar-days" id="miniCalendarDays"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Events Filter Card -->
                    <div class="card h-200">
                        <div class="card-body d-flex flex-column h-100">
                            <div class="calendar-content d-flex flex-column h-100">
                                <h6 class="fw-bold mb-3">Event Filters</h6>

                                <div class="accordion flex-grow-1 d-flex flex-column" id="eventFiltersAccordion">
                                    <!-- Filter by Status Section -->
                                    <div class="accordion-item border-0 border-bottom">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button py-2 px-3" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#filterStatusCollapse"
                                                aria-expanded="true" aria-controls="filterStatusCollapse">
                                                <span class="fw-semibold">Filter by Status</span>
                                            </button>
                                        </h2>
                                        <div id="filterStatusCollapse" class="accordion-collapse collapse show"
                                            data-bs-parent="#eventFiltersAccordion">
                                            <div class="accordion-body p-3 pt-2">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Pencil Booked" id="filterPencilBooked" checked>
                                                    <label class="form-check-label" for="filterPencilBooked">Pencil
                                                        Booked</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Pending Approval" id="filterPending" checked>
                                                    <label class="form-check-label" for="filterPending">Pending
                                                        Approval</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Awaiting Payment" id="filterAwaitingPayment" checked>
                                                    <label class="form-check-label" for="filterAwaitingPayment">Awaiting
                                                        Payment</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Scheduled" id="filterScheduled" checked>
                                                    <label class="form-check-label" for="filterScheduled">Scheduled</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Ongoing" id="filterOngoing" checked>
                                                    <label class="form-check-label" for="filterOngoing">Ongoing</label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input event-filter-checkbox" type="checkbox"
                                                        value="Overdue" id="filterOverdue" checked>
                                                    <label class="form-check-label" for="filterOverdue">Overdue</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Filter by Facility Section -->
                                    <div class="accordion-item border-0">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button py-2 px-3 collapsed" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#filterFacilityCollapse"
                                                aria-expanded="false" aria-controls="filterFacilityCollapse">
                                                <span class="fw-semibold">Filter by Facility</span>
                                            </button>
                                        </h2>
                                        <div id="filterFacilityCollapse" class="accordion-collapse collapse"
                                            data-bs-parent="#eventFiltersAccordion">
                                            <div class="accordion-body p-3 pt-2 d-flex flex-column" style="height: 300px;">
                                                <div class="mb-2 small text-muted">Select facilities to show events:</div>
                                                <div id="facilityFilterList" class="flex-grow-1 overflow-auto">
                                                    <!-- Facilities will be populated by JavaScript -->
                                                    <div class="text-center py-3 text-muted">
                                                        <div class="spinner-border spinner-border-sm me-2"></div>
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

                <!-- Right Column: FullCalendar -->
                <div class="col-lg-9 d-flex flex-column">
                    <div class="card flex-grow-1">
                        <div class="card-body p-3 d-flex flex-column">
                            <div id="calendar" class="flex-grow-1"></div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Events List Card -->
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="calendar-header-bar">
                                <h4 class="mb-0">Calendar Events</h4>
                                <a href="{{ route('admin.calendar') }}" class="btn btn-primary open-calendar-btn">
                                    <i class="bi bi-calendar-plus me-1"></i> Open Calendar
                                </a>
                            </div>

                            <div id="calendarEventsApp">
                                <!-- Events Header with Controls -->
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap mt-3">
                                    <div class="d-flex align-items-center gap-2 me-3 mb-2">
                                        <select class="form-select form-select-sm filter-select" v-model="filters.eventType"
                                            @change="loadCalendarEvents(1)">
                                            <option value="">All Event Types</option>
                                            <option value="hall_booking">Hall Booking</option>
                                            <option value="school_event">School Event</option>
                                            <option value="holiday">Holiday</option>
                                        </select>
                                        <select class="form-select form-select-sm filter-select" v-model="filters.sort"
                                            @change="loadCalendarEvents(1)">
                                            <option value="newest">Newest First</option>
                                            <option value="oldest">Oldest First</option>
                                        </select>
                                        <div class="input-group input-group-sm" style="width: 200px;">
                                            <input type="search" class="form-control" v-model="filters.search"
                                                placeholder="Find an event..." @keyup.enter="loadCalendarEvents(1)">
                                            <button class="btn btn-outline-secondary" type="button"
                                                @click="loadCalendarEvents(1)">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="d-none d-lg-flex align-items-center gap-2">
                                            <select class="form-select form-select-sm w-auto" v-model="perPage"
                                                @change="loadCalendarEvents(1)">
                                                <option value="10">10 per page</option>
                                                <option value="25">25 per page</option>
                                                <option value="50">50 per page</option>
                                                <option value="100">100 per page</option>
                                            </select>
                                            <button type="button"
                                                class="btn btn-link btn-sm text-secondary text-decoration-none me-2 refresh-btn"
                                                @click="refreshCalendarEvents" :disabled="loading">
                                                <i class="bi"
                                                    :class="loading ? 'bi-arrow-clockwise animate-spin' : 'bi-arrow-clockwise'"></i>
                                                Refresh
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#addCalendarEventModal">
                                                <i class="bi bi-plus-circle me-1"></i> Add Event
                                            </button>
                                        </div>

                                        <div class="dropdown d-lg-none">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-funnel"></i> Controls
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <div class="px-3 py-2">
                                                        <label class="form-label small mb-1">Items per page:</label>
                                                        <select class="form-select form-select-sm" v-model="perPage"
                                                            @change="loadCalendarEvents(1)">
                                                            <option value="10">10 per page</option>
                                                            <option value="25">25 per page</option>
                                                            <option value="50">50 per page</option>
                                                            <option value="100">100 per page</option>
                                                        </select>
                                                    </div>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item"
                                                        @click="refreshCalendarEvents">
                                                        <i class="bi bi-arrow-clockwise me-2"></i> Refresh list
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                        data-bs-target="#addCalendarEventModal">
                                                        <i class="bi bi-plus-circle me-2"></i> Add Event
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading Spinner -->
                                <div class="loading-spinner d-flex flex-column justify-content-center align-items-center py-5"
                                    v-if="loading" style="min-height: 200px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2 small">Loading calendar events...</p>
                                </div>

                                <!-- Calendar Events List -->
                                <div id="calendarEventsList" v-else>
                                    <div v-if="calendarEvents.length === 0" class="text-center py-5">
                                        <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                                        <p class="text-muted mb-2">No calendar events found.</p>
                                        <p class="text-muted small">Click "Add Event" to create your first calendar event.
                                        </p>
                                    </div>

                                    <div v-for="event in calendarEvents" :key="event.event_id"
                                        class="card border mb-2 calendar-event-card">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <h6 class="fw-bold mb-0">
                                                                @{{ event.event_name }}
                                                                <span class="badge ms-2"
                                                                    :style="{ backgroundColor: event.color }">
                                                                    @{{ event.display_name }}
                                                                </span>
                                                                <span v-if="event.schedule.all_day"
                                                                    class="badge bg-info ms-2">All
                                                                    Day</span>
                                                            </h6>
                                                        </div>
                                                        <button
                                                            class="btn btn-sm btn-outline-danger delete-calendar-event-btn"
                                                            @click.stop="confirmDeleteEvent(event)"
                                                            :data-id="event.event_id" :data-name="event.event_name"
                                                            title="Delete Event">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>

                                                    <p v-if="event.description" class="text-muted small mb-2">
                                                        @{{ event.description }}
                                                    </p>

                                                    <p class="text-muted small mb-0">
                                                        <i class="bi bi-clock me-1"></i>
                                                        @{{ event.schedule.display }} • Duration: @{{ formatDuration(event)
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top"
                                        v-if="totalPages > 1">
                                        <div class="text-muted small">
                                            Showing page @{{ currentPage }} of @{{ totalPages }}
                                            <span class="mx-2">•</span>
                                            Total: @{{ totalItems }} events
                                        </div>
                                        <nav aria-label="Events pagination">
                                            <ul class="pagination pagination-sm mb-0">
                                                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                                    <button class="page-link" @click="changePage(currentPage - 1)"
                                                        :disabled="currentPage === 1">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </button>
                                                </li>
                                                <li class="page-item" v-for="page in displayedPages" :key="page"
                                                    :class="{ active: page === currentPage }">
                                                    <button class="page-link" @click="changePage(page)">@{{ page }}</button>
                                                </li>
                                                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                                                    <button class="page-link" @click="changePage(currentPage + 1)"
                                                        :disabled="currentPage === totalPages">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </button>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Calendar Event Confirmation Modal -->
    <div class="modal fade" id="deleteCalendarEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0 pb-4">
                    <div class="mb-3">
                        <i class="bi bi-trash3 text-danger display-4"></i>
                    </div>
                    <h5 class="modal-title mb-3" id="deleteEventModalTitle">Delete Event</h5>
                    <p class="text-muted mb-0" id="deleteEventModalText">
                        Are you sure you want to delete this event? This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteEventBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="deleteEventSpinner"></span>
                        Delete Event
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.modals.add-calendar-event-modal')
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="{{ asset('js/public/calendar.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = "/admin/login";
                return;
            }

            // Initialize Calendar Module
            window.calendarModule = new CalendarModule({
                isAdmin: true,
                adminToken: token,
                apiEndpoint: '/api/requisition-forms/calendar-events',
                calendarEventsEndpoint: '/api/calendar-events',
                containerId: 'calendar',
                miniCalendarContainerId: 'miniCalendarDays',
                monthYearId: 'currentMonthYear',
                eventModalId: 'calendarEventModal',
                searchInputId: 'eventSearchInput',
                searchResultsContainerId: 'searchResultsContainer',
                searchResultsListId: 'searchResultsList'
            });

            await window.calendarModule.initialize();

            // Load facilities for filter
            loadFacilitiesForFilter();

            // Initialize Vue app for calendar events list
            initializeCalendarEventsApp();
        });

        // Load facilities for filter
        async function loadFacilitiesForFilter() {
            try {
                const facilityFilterList = document.getElementById('facilityFilterList');
                if (!facilityFilterList) return;

                const token = localStorage.getItem('adminToken');
                const response = await fetch('/api/facilities', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    const facilities = result.data || result;

                    if (facilities.length === 0) {
                        facilityFilterList.innerHTML = `
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-building-slash"></i>
                                    <div class="small mt-1">No facilities found</div>
                                </div>
                            `;
                        return;
                    }

                    let html = `
                            <div class="facility-item">
                                <div class="form-check">
                                    <input class="form-check-input facility-filter-checkbox select-all-facilities" 
                                           type="checkbox" 
                                           id="filterAllFacilities"
                                           checked>
                                    <label class="form-check-label fw-medium" for="filterAllFacilities">
                                        All facilities
                                    </label>
                                </div>
                            </div>
                            <hr class="my-2">
                        `;

                    facilities.forEach(facility => {
                        const isAvailable = facility.status_id === 1 || facility.status?.status_id === 1;
                        const badgeColor = isAvailable ? 'bg-success' : 'bg-warning';
                        const badgeText = isAvailable ? 'Available' : 'Unavailable';

                        html += `
                                <div class="facility-item">
                                    <div class="form-check">
                                        <input class="form-check-input facility-filter-checkbox individual-facility" 
                                               type="checkbox" 
                                               value="${facility.facility_id}" 
                                               id="filterFacility_${facility.facility_id}"
                                               data-name="${facility.facility_name}"
                                               ${isAvailable ? 'checked' : 'disabled'}>
                                        <label class="form-check-label d-flex justify-content-between align-items-center" 
                                               for="filterFacility_${facility.facility_id}">
                                            <span class="${!isAvailable ? 'text-muted' : ''}">
                                                ${facility.facility_name}
                                            </span>
                                            <span class="facility-badge ${badgeColor} text-white">${badgeText}</span>
                                        </label>
                                    </div>
                                </div>
                            `;
                    });

                    facilityFilterList.innerHTML = html;

                    // Setup event listeners
                    setupFacilityFilterListeners();

                } else {
                    throw new Error('Failed to fetch facilities');
                }
            } catch (error) {
                console.error('Error loading facilities for filter:', error);
                const facilityFilterList = document.getElementById('facilityFilterList');
                if (facilityFilterList) {
                    facilityFilterList.innerHTML = `
                            <div class="text-center py-3 text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                <div class="small mt-1">Failed to load facilities</div>
                            </div>
                        `;
                }
            }
        }

        // Setup facility filter listeners
        function setupFacilityFilterListeners() {
            const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');
            if (allFacilitiesCheckbox) {
                allFacilitiesCheckbox.addEventListener('change', function () {
                    const individualCheckboxes = document.querySelectorAll('.individual-facility:not(:disabled)');
                    individualCheckboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });

                    if (window.calendarModule) {
                        window.calendarModule.applyFilters();
                    }
                });
            }

            document.querySelectorAll('.individual-facility').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    updateAllFacilitiesCheckbox();
                    if (window.calendarModule) {
                        window.calendarModule.applyFilters();
                    }
                });
            });

            function updateAllFacilitiesCheckbox() {
                const allCheckbox = document.getElementById('filterAllFacilities');
                if (!allCheckbox) return;

                const individualCheckboxes = document.querySelectorAll('.individual-facility:not(:disabled)');
                const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
                const totalCount = individualCheckboxes.length;

                if (checkedCount === totalCount) {
                    allCheckbox.checked = true;
                    allCheckbox.indeterminate = false;
                } else if (checkedCount === 0) {
                    allCheckbox.checked = false;
                    allCheckbox.indeterminate = false;
                } else {
                    allCheckbox.checked = false;
                    allCheckbox.indeterminate = true;
                }
            }
        }

        // Initialize Vue app for calendar events list
        function initializeCalendarEventsApp() {
            if (typeof Vue !== 'undefined' && document.getElementById('calendarEventsApp')) {
                new Vue({
                    el: '#calendarEventsApp',
                    data: {
                        calendarEvents: [],
                        loading: false,
                        currentPage: 1,
                        totalPages: 1,
                        totalItems: 0,
                        perPage: 50,
                        filters: {
                            eventType: '',
                            sort: 'newest',
                            search: ''
                        }
                    },
                    computed: {
                        displayedPages() {
                            const delta = 2;
                            const range = [];
                            const rangeWithDots = [];
                            let l;

                            for (let i = 1; i <= this.totalPages; i++) {
                                if (i === 1 || i === this.totalPages || (i >= this.currentPage - delta && i <= this.currentPage + delta)) {
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
                        async loadCalendarEvents(page = 1) {
                            this.loading = true;
                            this.currentPage = page;

                            try {
                                const params = new URLSearchParams({
                                    page: page,
                                    per_page: this.perPage
                                });

                                if (this.filters.eventType) {
                                    params.append('event_type', this.filters.eventType);
                                }
                                if (this.filters.search) {
                                    params.append('search', this.filters.search);
                                }

                                const response = await fetch(`/api/calendar-events?${params}`, {
                                    headers: {
                                        'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                        'Accept': 'application/json'
                                    }
                                });

                                if (response.ok) {
                                    const result = await response.json();
                                    let events = result.data || [];

                                    if (this.filters.sort === 'newest') {
                                        events = events.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                                    } else if (this.filters.sort === 'oldest') {
                                        events = events.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                                    }

                                    this.calendarEvents = events;
                                    this.totalPages = result.meta?.last_page || 1;
                                    this.totalItems = result.meta?.total || 0;
                                } else {
                                    console.error('Failed to load calendar events');
                                    window.showToast?.('Failed to load calendar events', 'error');
                                }
                            } catch (error) {
                                console.error('Error loading calendar events:', error);
                                this.calendarEvents = [];
                                window.showToast?.('Error loading calendar events', 'error');
                            } finally {
                                this.loading = false;
                            }
                        },

                        async refreshCalendarEvents() {
                            await this.loadCalendarEvents(this.currentPage);
                        },

                        formatDuration(event) {
                            if (!event.schedule) return 'N/A';

                            const schedule = event.schedule;
                            const allDay = schedule.all_day || false;

                            if (allDay) {
                                const startDate = new Date(schedule.start_date + 'T00:00:00');
                                const endDate = new Date(schedule.end_date + 'T00:00:00');
                                const diffTime = Math.abs(endDate - startDate);
                                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                                return diffDays === 1 ? '1 day' : `${diffDays} days`;
                            }

                            if (schedule.start_time && schedule.end_time) {
                                try {
                                    const startDateTime = new Date(`${schedule.start_date}T${schedule.start_time}`);
                                    const endDateTime = new Date(`${schedule.end_date}T${schedule.end_time}`);

                                    let durationMs = endDateTime - startDateTime;
                                    if (durationMs < 0) {
                                        const adjustedEnd = new Date(endDateTime);
                                        adjustedEnd.setDate(adjustedEnd.getDate() + 1);
                                        durationMs = adjustedEnd - startDateTime;
                                    }

                                    const totalMinutes = Math.floor(durationMs / (1000 * 60));
                                    const hours = Math.floor(totalMinutes / 60);
                                    const minutes = totalMinutes % 60;

                                    if (hours > 0 && minutes > 0) {
                                        return `${hours}h ${minutes}m`;
                                    } else if (hours > 0) {
                                        return `${hours}hrs`;
                                    } else {
                                        return `${minutes}mins`;
                                    }
                                } catch (error) {
                                    console.error('Error calculating duration:', error);
                                    return 'N/A';
                                }
                            }
                            return 'N/A';
                        },

                        confirmDeleteEvent(event) {
                            if (typeof window.confirmDeleteCalendarEvent === 'function') {
                                window.confirmDeleteCalendarEvent(event.event_id, event.event_name);
                            } else {
                                if (confirm(`Are you sure you want to delete the event "${event.event_name}"?`)) {
                                    this.deleteEvent(event.event_id);
                                }
                            }
                        },

                        async deleteEvent(eventId) {
                            try {
                                const response = await fetch(`/api/calendar-events/${eventId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                    }
                                });

                                if (response.ok) {
                                    window.showToast?.('Calendar event deleted successfully!', 'success');
                                    await this.loadCalendarEvents(this.currentPage);
                                    if (window.calendarModule) {
                                        await window.calendarModule.loadAllEvents();
                                    }
                                } else {
                                    const result = await response.json();
                                    throw new Error(result.message || 'Failed to delete event');
                                }
                            } catch (error) {
                                console.error('Error deleting calendar event:', error);
                                window.showToast?.(error.message || 'Failed to delete event', 'error');
                            }
                        },

                        changePage(page) {
                            if (page >= 1 && page <= this.totalPages) {
                                this.loadCalendarEvents(page);
                            }
                        }
                    },
                    mounted() {
                        this.loadCalendarEvents(1);
                    }
                });
            }
        }

        // Simple toast notification function
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

        // Delete calendar event functions
        let eventToDeleteId = null;
        let eventToDeleteName = null;
        let deleteEventModal = null;

        window.confirmDeleteCalendarEvent = function (eventId, eventName) {
            eventToDeleteId = eventId;
            eventToDeleteName = eventName;

            document.getElementById('deleteEventModalTitle').textContent = 'Delete Event';
            document.getElementById('deleteEventModalText').innerHTML =
                `Are you sure you want to delete <strong>${eventName}</strong>? This action cannot be undone.`;

            const modalElement = document.getElementById('deleteCalendarEventModal');
            if (modalElement) {
                deleteEventModal = new bootstrap.Modal(modalElement);
                deleteEventModal.show();
            }
        };

        document.getElementById('confirmDeleteEventBtn')?.addEventListener('click', async function () {
            if (!eventToDeleteId) return;

            const confirmBtn = document.getElementById('confirmDeleteEventBtn');
            const spinner = document.getElementById('deleteEventSpinner');

            try {
                confirmBtn.disabled = true;
                spinner.classList.remove('d-none');

                const response = await fetch(`/api/calendar-events/${eventToDeleteId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    window.showToast('Calendar event deleted successfully!', 'success');

                    if (deleteEventModal) {
                        deleteEventModal.hide();
                    }

                    // Refresh both calendar views
                    if (window.calendarModule) {
                        await window.calendarModule.loadAllEvents();
                    }

                    // Refresh Vue list
                    const calendarEventsApp = document.getElementById('calendarEventsApp')?.__vue__;
                    if (calendarEventsApp) {
                        await calendarEventsApp.loadCalendarEvents(1);
                    }
                } else {
                    const result = await response.json();
                    throw new Error(result.message || 'Failed to delete event');
                }
            } catch (error) {
                console.error('Error deleting calendar event:', error);
                window.showToast(error.message || 'Failed to delete event', 'error');
            } finally {
                confirmBtn.disabled = false;
                spinner.classList.add('d-none');
                eventToDeleteId = null;
                eventToDeleteName = null;
            }
        });
    </script>
@endsection