@extends('layouts.app')

@section('title', 'Booking Catalog')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/public/catalog.css') }}" />
    {{-- All design overrides are handled purely in catalog.css --}}

    {{-- ── Hero ── --}}
    <section class="catalog-hero-section"
        style="background-image: url('{{ asset('assets/homepage.jpg') }}');">
        <div class="catalog-hero-content">
            <h2 id="catalogHeroTitle">Booking Catalog</h2>
        </div>
    </section>

    <main class="main-catalog-section">
        <div class="container">
            <div class="row g-4">

                {{-- ── Sidebar ── --}}
                <div class="col-lg-3 col-md-4">

                    {{-- Quick Links --}}
                    <div class="quick-links-card">
                        <p class="mb-0">
                            Not sure when to book?<br>View available timeslots here.
                        </p>
                        <div class="d-grid gap-2 mt-3">
                            <a href="/events-calendar" target="_blank" rel="noopener noreferrer"
                                class="btn btn-custom d-flex justify-content-center align-items-center"
                                id="eventsCalendarBtn">
                                <i class="fa-solid fa-calendar me-2"></i> Events Calendar
                            </a>
                            <div style="position:relative;">
                                <span id="requisitionBadge"
                                    class="badge bg-danger rounded-pill position-absolute"
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

                    {{-- Browse by Category --}}
                    <div class="sidebar-card">
                        <h5>Browse by Category</h5>
                        <div class="filter-list" id="categoryFilterList">
                            <x-skeleton
                                :lines="6"
                                rounded="lg"
                                intensity="medium"
                                :lineHeights="['24px','20px','20px','20px','20px','16px']"
                                :colWidths="[8,12,10,9,7,5]"
                                :margins="['15px','10px','10px','10px','10px','0']" />
                        </div>
                    </div>

                    {{-- Browse by Building (Rooms only) --}}
                    <div class="sidebar-card mt-3" id="buildingFilterCard" style="display:none;">
                        <h5>Browse by Building</h5>
                        <div id="buildingFilterList">
                            <x-skeleton
                                :lines="4"
                                rounded="lg"
                                intensity="medium"
                                :lineHeights="['24px','20px','20px','20px']"
                                :colWidths="[8,10,9,7]"
                                :margins="['15px','10px','10px','0']" />
                        </div>
                    </div>
                </div>

                {{-- ── Main Content ── --}}
                <div class="col-lg-9 col-md-8">

                    {{-- Toolbar: Tabs + Filters + Search --}}
                    <div class="right-content-header w-100">
                        <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center gap-3 mb-3 w-100">

                            {{-- Type Tabs --}}
                            <div class="catalog-type-tabs flex-wrap flex-sm-nowrap">
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="venues">
                                    <i class="fa-solid fa-building me-1"></i> Venues
                                </button>
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="rooms">
                                    <i class="fa-solid fa-door-open me-1"></i> Rooms
                                </button>
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="equipment">
                                    <i class="fa-solid fa-toolbox me-1"></i> Equipment
                                </button>
                            </div>

                            {{-- Filter + Search (right side) --}}
                            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 ms-auto">

                                {{-- Filter Dropdown --}}
                                <div class="dropdown filter-sort-dropdowns flex-shrink-0">
                                    <button class="btn btn-sm dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        id="filterDropdown" data-bs-auto-close="outside">
                                        <i class="bi bi-sliders2 me-1"></i>
                                        <span class="d-none d-sm-inline">Filters</span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end p-2"
                                        style="min-width:220px;" id="filterDropdownMenu">

                                        {{-- Status --}}
                                        <li>
                                            <div class="dropdown-header fw-semibold text-dark px-2 py-1">Status</div>
                                        </li>
                                        <li>
                                            <div class="px-2 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusAll" value="All"
                                                        data-status="All" checked>
                                                    <label class="form-check-label w-100" for="statusAll">All</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusAvailable" value="Available"
                                                        data-status="Available">
                                                    <label class="form-check-label w-100" for="statusAvailable">
                                                        <span class="badge"
                                                            style="background-color:#22c55e;width:10px;height:10px;display:inline-block;border-radius:50%;padding:0;"></span>
                                                        Available
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusUnavailable" value="Unavailable"
                                                        data-status="Unavailable">
                                                    <label class="form-check-label w-100" for="statusUnavailable">
                                                        <span class="badge"
                                                            style="background-color:#ef4444;width:10px;height:10px;display:inline-block;border-radius:50%;padding:0;"></span>
                                                        Unavailable
                                                    </label>
                                                </div>
                                            </div>
                                        </li>

                                        <li><hr class="dropdown-divider my-2"></li>

                                        {{-- Layout --}}
                                        <li>
                                            <div class="dropdown-header fw-semibold text-dark px-2 py-1">Layout</div>
                                        </li>
                                        <li>
                                            <div class="px-2 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input layout-option" type="radio"
                                                        name="layoutFilter" id="layoutGrid" value="grid"
                                                        data-layout="grid" checked>
                                                    <label class="form-check-label w-100" for="layoutGrid">
                                                        <i class="bi bi-grid-3x3-gap-fill me-2"></i> Grid
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input layout-option" type="radio"
                                                        name="layoutFilter" id="layoutList" value="list"
                                                        data-layout="list">
                                                    <label class="form-check-label w-100" for="layoutList">
                                                        <i class="bi bi-list-ul me-2"></i> List
                                                    </label>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                {{-- Search --}}
                                <div class="flex-grow-1" style="min-width:310px;">
                                    <form id="catalogSearchForm">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="catalogSearchInput"
                                                placeholder="Search venues, rooms, or equipment…"
                                                aria-label="Search">
                                            <button class="btn btn-primary" type="submit" id="catalogSearchBtn">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button"
                                                id="clearSearchBtn" style="display:none;">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Single Facility Availability Modal ── --}}
                    <div class="modal fade" id="singleFacilityAvailabilityModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:95%;">
                            <div class="modal-content" style="min-height:85vh;">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="singleFacilityAvailabilityModalLabel">
                                        <i class="bi bi-calendar-check me-2"></i>
                                        <span id="facilityAvailabilityName">Facility Availability</span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-3">
                                    <div class="row g-3">

                                        {{-- Left: Info + Mini Calendar --}}
                                        <div class="col-lg-3 col-md-12">
                                            <div class="scrollable-left-column">
                                                {{-- Facility Info --}}
                                                <div class="card mb-3" style="border:1px solid var(--border);border-radius:var(--radius-md);">
                                                    <div class="card-body">
                                                        <div class="facility-availability-info text-center">
                                                            <div id="facilityAvailabilityImage" class="mb-3"></div>
                                                            <h6 id="facilityAvailabilityTitle" class="mb-2">
                                                                <span id="facilityTitleText"></span>
                                                                <span class="text-muted" id="facilityCapacityInfo"></span>
                                                            </h6>
                                                            <div class="facility-meta small text-muted mb-2">
                                                                <div>
                                                                    <i class="bi bi-tags-fill me-1"></i>
                                                                    <span id="facilityCategory"></span>
                                                                </div>
                                                            </div>
                                                            <div class="availability-status mt-3">
                                                                <span class="badge" id="facilityStatusBadge"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Mini Calendar --}}
                                                <div class="card mb-3" style="border:1px solid var(--border);border-radius:var(--radius-md);">
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
                                                                    @foreach (['S','M','T','W','T','F','S'] as $d)
                                                                        <div class="day-header text-center flex-fill small text-muted">{{ $d }}</div>
                                                                    @endforeach
                                                                </div>
                                                                <div class="calendar-days" id="availabilityMiniCalendarDays">
                                                                    {{-- Populated by JavaScript --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Right: Full Calendar --}}
                                        <div class="col-lg-9 col-md-12 d-flex flex-column">
                                            <div class="position-relative flex-grow-1">
                                                {{-- Loading overlay --}}
                                                <div class="calendar-loading-overlay" id="availabilityLoadingOverlay">
                                                    <div class="calendar-loading-spinner"></div>
                                                    <div class="loading-text">Loading calendar…</div>
                                                </div>

                                                {{-- Legend --}}
                                                <div class="card" style="border:1px solid var(--border);border-radius:var(--radius-md);">
                                                    <div class="card-body py-2">
                                                        <div id="dynamicLegend" class="d-flex flex-wrap gap-3">
                                                            <div class="text-muted small">Loading status colors…</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Calendar --}}
                                                <div class="card flex-grow-1 mt-3" style="border:1px solid var(--border);border-radius:var(--radius-md);">
                                                    <div class="card-body p-3 d-flex flex-column">
                                                        <div class="calendar-content flex-grow-1 d-flex flex-column position-relative">
                                                            <div id="facilityAvailabilityCalendar" class="flex-grow-1"></div>
                                                        </div>
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

                    {{-- ── Loading Indicator ── --}}
                    <div id="loadingIndicator" class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                        <p class="mt-2 text-muted" style="font-size:0.9rem;">Loading catalog items…</p>
                    </div>

                    {{-- ── Catalog Items ── --}}
                    <div id="catalogItemsContainer" class="grid-layout d-none"></div>

                    {{-- ── Pagination ── --}}
                    <div class="text-center mt-4">
                        <nav>
                            <ul id="pagination" class="pagination justify-content-center gap-1"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- ── Event Details Modal ── --}}
    <div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventDetailModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="border-0 p-1"><strong>Event Title:</strong></td>
                                    <td class="border-0 p-1" id="eventTitle"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Requester:</strong></td>
                                    <td class="border-0 p-1" id="eventRequester"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Purpose:</strong></td>
                                    <td class="border-0 p-1" id="eventPurpose"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Participants:</strong></td>
                                    <td class="border-0 p-1" id="eventParticipants"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Status:</strong></td>
                                    <td class="border-0 p-1">
                                        <span id="eventStatus" class="badge"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Start:</strong></td>
                                    <td class="border-0 p-1" id="eventStart"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>End:</strong></td>
                                    <td class="border-0 p-1" id="eventEnd"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Facilities:</strong></td>
                                    <td class="border-0 p-1" id="eventFacilities"></td>
                                </tr>
                                <tr>
                                    <td class="border-0 p-1"><strong>Equipment:</strong></td>
                                    <td class="border-0 p-1" id="eventEquipment"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Availability Modal ── --}}
    <div class="modal fade" id="availabilityModal" tabindex="-1"
        aria-labelledby="availabilityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="availabilityModalLabel">Availability Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" style="min-height:70vh;">
                    <div id="availabilityCalendar" style="height:65vh;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/public/calendar.js') }}"></script>
    <script src="{{ asset('js/public/catalog.js') }}"></script>
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const bookingCatalog = new BookingCatalog({
                containerId: 'catalogItemsContainer',
                loadingIndicatorId: 'loadingIndicator',
                categoryFilterId: 'categoryFilterList',
                paginationId: 'pagination',
                requisitionBadgeId: 'requisitionBadge',
                heroTitleId: 'catalogHeroTitle',
                searchInputId: 'catalogSearchInput',
                searchFormId: 'catalogSearchForm',
                clearSearchBtnId: 'clearSearchBtn',
                filterDropdownId: 'filterDropdown',
                filterDropdownMenuId: 'filterDropdownMenu',
                itemsPerPage: 6,
                defaultCatalogType: 'venues',
                defaultLayout: 'grid',
                onItemAdded: (id, type, quantity) => {
                    console.log(`Item added: ${type} ${id} (x${quantity})`);
                },
                onItemRemoved: (id, type) => {
                    console.log(`Item removed: ${type} ${id}`);
                },
                onError: (error) => {
                    console.error('Catalog error:', error);
                }
            });

            // No need to pass CalendarModule anymore since it's global
            await bookingCatalog.init();
            console.log('BookingCatalog initialized successfully');

            window.bookingCatalog = bookingCatalog;
        });
    </script>
@endsection