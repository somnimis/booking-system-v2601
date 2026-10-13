{{-- resources/views/public/events-calendar.blade.php --}}
@extends('layouts.app')

@section('title', 'Schedule Availability')

@section('body_class', 'availability-page')

@section('content')
    {{-- Shared matrix styles (filter-row, view tabs, matrix table, status cards, modal, tooltips, etc.) --}}
    <link rel="stylesheet" href="{{ asset('css/shared/availability-matrix.css') }}">

    <style>
        /* ============================================
           PUBLIC-ONLY STYLES
           Matrix-specific styles now live in
           css/shared/availability-matrix.css so admin
           and public pages share a single source.
           ============================================ */

        /* Import Fonts */
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap');

        /* Design Tokens (public page only — admin inherits from global-styles.css) */
        :root {
            --navy: #041a4b;
            --navy-mid: #0b2d72;
            --navy-light: #e8edf8;
            --amber: #f5bc40;
            --amber-dark: #d9a12a;
            --white: #ffffff;
            --surface: #f5f6fa;
            --border: #e2e6f0;
            --text-base: #1e2d4a;
            --text-muted: #6b7a99;
            --text-light: #9aaac5;
            --success: #22c55e;
            --danger: #ef4444;
            --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
            --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
            --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            --status-available: #22c55e;
            --status-available-bg: #e8f7ef;
            --status-pending: #f5bc40;
            --status-pending-bg: #fef8e8;
            --status-booked: #ef4444;
            --status-booked-bg: #fee8e8;
            --status-event: #6f42c1;
            --status-event-bg: #ede7f6;
        }

        /* Base */
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            margin: 0;
            padding: 0;
        }

        main {
            background-image: url("{{ asset('assets/cpu-pic1.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Container - Glassmorphic */
        .availability-container {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(2px);
            border-radius: var(--radius-xl);
            margin: 24px;
            padding: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Mobile override for the public-only container */
        @media (max-width: 768px) {
            .availability-container {
                margin: 16px;
                padding: 18px;
            }
        }
    </style>

    <main>
        <div class="availability-container">
            <!-- Navigation Header -->
            <div class="nav-header">
                <div class="date-nav">
                    <button class="date-nav-btn" id="prevDayBtn" aria-label="Previous day">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <span class="current-date" id="currentDateDisplay"></span>
                    <button class="date-nav-btn" id="nextDayBtn" aria-label="Next day">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
                <div class="date-actions">
                    <input type="date" id="datePicker" class="date-picker-input">
                    <button class="today-btn" id="todayBtn">Today</button>
                </div>
            </div>

            <!-- Time Range Selector -->
            <div class="time-range-bar">
                <button class="time-range-btn active" data-range="8-12">Morning (8 AM - 12 PM)</button>
                <button class="time-range-btn" data-range="13-17">Afternoon (1 PM - 5 PM)</button>
                <button class="time-range-btn" data-range="8-17">Full Day (8 AM - 5 PM)</button>
                <button class="time-range-btn" data-range="0-24">All Day (24h)</button>
            </div>

            <!-- Legend -->
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

            <!-- View Type Tabs & Filters Bar - All in one row -->
            <div class="filter-row">
                <!-- View Type Tabs -->
                <div class="view-type-bar">
                    <button class="view-type-btn active" data-view="venues">Venues</button>
                    <button class="view-type-btn" data-view="rooms">Campus Rooms</button>
                </div>

                <!-- Facility Filter Dropdown -->
                <div class="filter-group">
                    <span class="filter-label" id="filterLabel">Venues</span>
                    <select class="facility-select" id="facilitySelect">
                        <option value="all">All Venues</option>
                    </select>
                </div>

                <!-- Right-aligned group -->
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

            <!-- Main Matrix View -->
            <div class="matrix-wrapper" id="matrixWrapper">
                <div class="availability-matrix" id="availabilityMatrix">
                    <div class="loading-overlay">
                        <div class="loading-spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Event Modal -->
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
    <script src="{{ asset('js/public/availability.js') }}"></script>
@endsection