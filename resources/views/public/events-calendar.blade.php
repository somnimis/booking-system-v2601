{{-- resources/views/public/events-calendar.blade.php --}}
@extends('layouts.app')

@section('title', 'Schedule Availability')

@section('body_class', 'availability-page')

@section('content')
    <style>
        /* ============================================
                       REFINED INSTITUTIONAL THEME - AVAILABILITY MATRIX
                       Matching catalog.css design system
                       ============================================ */

        /* Import Fonts */
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap');

        /* Design Tokens */
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

        /* Filter Row - All in one horizontal row */
        .filter-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        /* View Type Tabs */
        .view-type-bar {
            display: flex;
            gap: 4px;
            background: var(--white);
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .view-type-btn {
            background: transparent;
            border: none;
            padding: 8px 20px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            font-family: 'DM Sans', sans-serif;
            white-space: nowrap;
        }

        .view-type-btn:hover {
            background: var(--navy-light);
            color: var(--navy);
        }

        .view-type-btn.active {
            background: var(--navy);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        /* Filter Group */
        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 4px 12px 4px 16px;
            border-radius: 60px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        .facility-select {
            padding: 6px 12px;
            border: none;
            background: transparent;
            font-size: 0.85rem;
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            color: var(--text-base);
            cursor: pointer;
            min-width: 180px;
            outline: none;
        }

        .facility-select:focus {
            outline: none;
        }

        /* Right Actions */
        .right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }

        .search-box {
            position: relative;
            min-width: 220px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 16px;
            padding-right: 36px;
            border: 1px solid var(--border);
            border-radius: 60px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            transition: var(--transition);
            background: var(--white);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .clear-filters-btn {
            background: var(--white);
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 60px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .clear-filters-btn:hover {
            background: #fee8e8;
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .right-actions {
                margin-left: 0;
                justify-content: stretch;
            }

            .search-box {
                flex: 1;
            }

            .view-type-bar {
                justify-content: center;
            }

            .filter-group {
                justify-content: space-between;
            }

            .facility-select {
                min-width: auto;
                flex: 1;
            }
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

        .availability-page {
            flex: 1;
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

        /* Navigation Header */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .date-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 6px 12px;
            border-radius: 60px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .date-nav-btn {
            background: transparent;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-base);
            font-size: 18px;
        }

        .date-nav-btn:hover {
            background: var(--navy-light);
            color: var(--navy);
        }

        .current-date {
            font-size: 1rem;
            font-weight: 600;
            color: var(--navy);
            min-width: 220px;
            text-align: center;
            font-family: 'Fraunces', serif;
        }

        .date-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-picker-input {
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 60px;
            font-size: 0.85rem;
            font-family: 'DM Sans', sans-serif;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
        }

        .date-picker-input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }

        .today-btn {
            background: var(--navy);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 60px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .today-btn:hover {
            background: var(--navy-mid);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Time Range Bar */
        .time-range-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .time-range-btn {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 8px 20px;
            border-radius: 60px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-base);
        }

        .time-range-btn.active {
            background: var(--navy);
            color: white;
            border-color: var(--navy);
            box-shadow: var(--shadow-sm);
        }

        .time-range-btn:hover:not(.active) {
            background: var(--navy-light);
            border-color: var(--navy);
        }

        /* Legend */
        .legend-bar {
            display: flex;
            gap: 28px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }

        .legend-color.available {
            background: var(--status-available-bg);
            border: 1px solid var(--status-available);
        }

        .legend-color.pending {
            background: var(--status-pending-bg);
            border: 1px solid var(--status-pending);
        }

        .legend-color.booked {
            background: var(--status-booked-bg);
            border: 1px solid var(--status-booked);
        }

        .legend-color.event {
            background: var(--status-event-bg);
            border: 1px solid var(--status-event);
        }

        /* Filters Bar - Split Venues & Campus Rooms */
        .filters-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 4px 12px 4px 16px;
            border-radius: 60px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        /* Clear filters button */
        .clear-filters-btn {
            background: var(--white);
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 60px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .clear-filters-btn:hover {
            background: #fee8e8;
            border-color: var(--danger);
            color: var(--danger);
        }

        .search-box {
            position: relative;
            min-width: 220px;
            flex: 1;
            max-width: 280px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 16px;
            padding-right: 36px;
            border: 1px solid var(--border);
            border-radius: 60px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Matrix Wrapper - Modern Table */
        .matrix-wrapper {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--white);
            position: relative;
            min-height: 400px;
            box-shadow: var(--shadow-sm);
        }

        .availability-matrix {
            min-width: 700px;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .matrix-table th {
            background: var(--surface);
            padding: 14px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--navy);
            border-bottom: 1px solid var(--border);
            border-right: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .matrix-table th:last-child {
            border-right: none;
        }

        .matrix-table td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid var(--border);
            border-right: 1px solid var(--border);
            vertical-align: middle;
        }

        .matrix-table td:last-child {
            border-right: none;
        }

        .matrix-table .facility-cell {
            background: var(--white);
            font-weight: 600;
            text-align: left;
            position: sticky;
            left: 0;
            background: var(--white);
            min-width: 180px;
            font-size: 0.85rem;
            color: var(--navy);
            border-right: 1px solid var(--border);
        }

        /* Parent Row Styles */
        .parent-row .facility-cell {
            cursor: pointer;
            background: var(--surface);
            font-weight: 600;
        }

        .parent-row .facility-cell:hover {
            background: var(--navy-light);
        }

        .expand-icon {
            display: inline-block;
            width: 20px;
            font-size: 11px;
            margin-right: 6px;
            color: var(--text-muted);
            transition: transform 0.2s ease;
        }

        .parent-row[data-expanded="true"] .expand-icon {
            transform: rotate(90deg);
        }

        .child-count {
            margin-left: 6px;
            color: var(--text-muted);
            font-weight: normal;
            font-size: 0.7rem;
        }

        .child-row .facility-cell {
            padding-left: 28px;
            background: var(--white);
        }

        .child-indent {
            display: inline-block;
            width: 20px;
            color: var(--text-light);
            margin-right: 4px;
        }

        /* Status Cards - Modern Redesign */
        .status-card {
            display: inline-block;
            padding: 8px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            max-width: 90px;
            text-align: center;
            letter-spacing: 0.01em;
        }

        .status-card.available {
            background: var(--status-available-bg);
            color: #166534;
            border: 1px solid var(--status-available);
        }

        .status-card.available:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
        }

        .status-card.pending {
            background: var(--status-pending-bg);
            color: #854d0e;
            border: 1px solid var(--status-pending);
        }

        .status-card.booked {
            background: var(--status-booked-bg);
            color: #991b1b;
            border: 1px solid var(--status-booked);
        }

        .status-card.event {
            background: var(--status-event-bg);
            color: #4a148c;
            border: 1px solid var(--status-event);
        }

        .status-card.parent-placeholder {
            background: var(--surface);
            color: var(--text-muted);
            border: 1px dashed var(--border);
            cursor: pointer;
            font-size: 1rem;
            padding: 6px 0;
        }

        .status-card.parent-placeholder:hover {
            background: var(--navy-light);
            border-color: var(--navy);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            z-index: 20;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--navy-light);
            border-top: 3px solid var(--navy);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--text-light);
        }

        /* Modal Styles - Matching Theme */
        .event-modal .modal-dialog {
            max-width: 520px;
        }

        .event-modal .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .event-modal .modal-header {
            background: var(--navy);
            color: white;
            border-bottom: none;
            padding: 1.2rem 1.5rem;
        }

        .event-modal .modal-title {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .event-modal .btn-close-white {
            filter: brightness(0) invert(1);
        }

        .event-detail-row {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .event-detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .event-detail-label {
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .event-detail-value {
            font-size: 0.95rem;
            color: var(--text-base);
        }

        .event-modal .modal-footer {
            border-top: 1px solid var(--border);
            background: var(--surface);
            padding: 1rem 1.5rem;
        }

        /* Tooltip */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 12px;
            background: var(--navy);
            color: white;
            font-size: 0.7rem;
            border-radius: var(--radius-sm);
            white-space: nowrap;
            display: none;
            z-index: 100;
            font-weight: 400;
            box-shadow: var(--shadow-sm);
        }

        [data-tooltip]:hover:before {
            display: block;
        }

        /* Child row animation */
        .child-row {
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .availability-container {
                margin: 16px;
                padding: 18px;
            }

            .facility-cell {
                min-width: 140px !important;
            }

            .status-card {
                padding: 6px 6px;
                font-size: 0.7rem;
                max-width: 75px;
            }

            .time-range-btn {
                padding: 6px 14px;
                font-size: 0.75rem;
            }

            .current-date {
                font-size: 0.85rem;
                min-width: 160px;
            }

            .legend-bar {
                gap: 16px;
            }

            .filter-group {
                width: 100%;
                flex-wrap: wrap;
            }

            .venues-filter {
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .search-box {
                max-width: 100%;
                flex: auto;
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