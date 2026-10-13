{{-- resources/views/admin/admin-calendar.blade.php
Admin calendar — facility × time availability matrix.
Shares CSS with public/events-calendar.blade.php via availability-matrix.css.
Shares JS logic with the public page via availability.js; the admin-specific
behavior (navigate to requisition on click instead of modal) is toggled by
window.AvailabilityMode = 'admin'.
--}}
@extends('layouts.admin')

@section('title', 'Calendar')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/shared/availability-matrix.css') }}">
    
    <main id=main>

        <div class="container-fluid px-4">
            <div class="card p-3">

                {{-- Navigation Header --}}
                <div class="nav-header">
                    <div class="date-nav">
                        <button class="date-nav-btn" id="prevDayBtn" aria-label="Previous day">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="current-date" id="currentDateDisplay"></span>
                        <button class="date-nav-btn" id="nextDayBtn" aria-label="Next day">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    <div class="date-actions">
                        <input type="date" id="datePicker" class="date-picker-input">
                        <button class="today-btn" id="todayBtn">Today</button>
                    </div>
                </div>

                {{-- Time Range Selector --}}
                <div class="time-range-bar">
                    <button class="time-range-btn active" data-range="8-12">Morning (8 AM - 12 PM)</button>
                    <button class="time-range-btn" data-range="13-17">Afternoon (1 PM - 5 PM)</button>
                    <button class="time-range-btn" data-range="8-17">Full Day (8 AM - 5 PM)</button>
                    <button class="time-range-btn" data-range="0-24">All Day (24h)</button>
                </div>

                {{-- Legend --}}
                <div class="legend-bar">
                    <div class="legend-item">
                        <div class="legend-color available"></div><span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color pending"></div><span>Pending Approval</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color booked"></div><span>Booked/Approved</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color event"></div><span>System Event</span>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="filter-row">
                    <div class="view-type-bar">
                        <button class="view-type-btn active" data-view="venues">Venues</button>
                        <button class="view-type-btn" data-view="rooms">Campus Rooms</button>
                    </div>

                    <div class="filter-group">
                        <span class="filter-label" id="filterLabel">Venues</span>
                        <select class="facility-select" id="facilitySelect">
                            <option value="all">All Venues</option>
                        </select>
                    </div>

                    <div class="right-actions">
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search facility or event...">
                            <i class="bi bi-search"></i>
                        </div>
                        <button class="clear-filters-btn" id="clearFiltersBtn">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>

                {{-- Matrix --}}
                <div class="matrix-wrapper" id="matrixWrapper">
                    <div class="availability-matrix" id="availabilityMatrix">
                        <div class="loading-overlay">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>

    {{-- Event Modal (shared markup; also re-created dynamically by JS) --}}
    <div class="modal fade event-modal" id="eventModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Mode flag MUST be set before availability.js loads so the constructor picks it up --}}
    <script>window.AvailabilityMode = 'admin';</script>
    <script src="{{ asset('js/public/availability.js') }}"></script>
@endsection