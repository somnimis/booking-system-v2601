{{-- resources/views/public/events-calendar.blade.php --}}
@extends('layouts.app')

@section('title', 'Schedule Availability')

@section('body_class', 'availability-page')

@section('content')
    <style>
        /* ============================================
           AVAILABILITY MATRIX VIEW - MAIN STYLES
           ============================================ */

        :root {
            --status-available: #28a745;
            --status-available-bg: #d4edda;
            --status-pending: #ffc107;
            --status-pending-bg: #fff3cd;
            --status-booked: #dc3545;
            --status-booked-bg: #f8d7da;
            --status-event: #6f42c1;
            --status-event-bg: #e2d9f3;
            --primary-color: #0a336c;
            --border-color: #dee2e6;
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
            padding: 0;
        }

        .availability-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            margin: 20px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Navigation Bar */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .date-nav {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: 50px;
        }

        .date-nav-btn {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
        }

        .date-nav-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .current-date {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            min-width: 200px;
            text-align: center;
        }

        .today-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .today-btn:hover {
            opacity: 0.9;
        }

        /* Time Range Selector */
        .time-range-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .time-range-btn {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .time-range-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Legend */
        .legend-bar {
            display: flex;
            gap: 24px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }

        .legend-color {
            width: 20px;
            height: 20px;
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

        /* Facility Filters - Enhanced with Dropdowns */
        .filters-bar {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .facility-filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            flex: 1;
        }

        /* Parent Facility Button Styles */
        .facility-filter-parent {
            position: relative;
            display: inline-block;
        }

        .facility-filter-badge {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .facility-filter-badge.has-children {
            padding-right: 8px;
        }

        .facility-filter-badge.has-children::after {
            content: '▼';
            font-size: 0.7rem;
            margin-left: 4px;
        }

        .facility-filter-badge:hover {
            background: #e9ecef;
        }

        .facility-filter-badge.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Dropdown Menu for Child Facilities */
        .facility-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .facility-dropdown.show {
            display: block;
        }

        .facility-dropdown-header {
            padding: 8px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 0.85rem;
            color: #666;
        }

        .facility-dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .facility-dropdown-item:hover {
            background: #f0f0f0;
        }

        .facility-dropdown-item.selected {
            background: var(--primary-color);
            color: white;
        }

        .facility-dropdown-item .facility-capacity {
            font-size: 0.7rem;
            color: #999;
            margin-left: auto;
        }

        .facility-dropdown-item.selected .facility-capacity {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Child facility indicator in matrix */
        .facility-cell.child-facility {
            padding-left: 20px;
            position: relative;
        }

        .facility-cell.child-facility::before {
            content: '↳';
            position: absolute;
            left: 8px;
            color: #999;
        }

        .search-box {
            position: relative;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 16px;
            padding-right: 35px;
            border: 1px solid var(--border-color);
            border-radius: 50px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        /* Clear Filters Button */
        .clear-filters-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .clear-filters-btn:hover {
            background: #5a6268;
        }

        /* Main Matrix Table */
        .matrix-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: white;
            position: relative;
            min-height: 400px;
        }

        .availability-matrix {
            min-width: 800px;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .matrix-table th {
            background: #f8f9fa;
            padding: 14px 8px;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            background: #f8f9fa;
        }

        .matrix-table th:last-child {
            border-right: none;
        }

        .matrix-table td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .matrix-table td:last-child {
            border-right: none;
        }

        .matrix-table .facility-cell {
            background: #f8f9fa;
            font-weight: 600;
            text-align: left;
            position: sticky;
            left: 0;
            background: #f8f9fa;
            min-width: 180px;
        }

        /* Status Cards */
        .status-card {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
            width: 100%;
            max-width: 100px;
        }

        .status-card.available {
            background: var(--status-available-bg);
            color: #155724;
            border: 1px solid var(--status-available);
        }

        .status-card.available:hover {
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .status-card.pending {
            background: var(--status-pending-bg);
            color: #856404;
            border: 1px solid var(--status-pending);
            cursor: default;
        }

        .status-card.booked {
            background: var(--status-booked-bg);
            color: #721c24;
            border: 1px solid var(--status-booked);
            cursor: default;
        }

        .status-card.event {
            background: var(--status-event-bg);
            color: #4a148c;
            border: 1px solid var(--status-event);
            cursor: default;
        }

        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            z-index: 10;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
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

        /* Modal Styles */
        .event-modal .modal-dialog {
            max-width: 600px;
        }

        .event-modal .modal-header {
            background: var(--primary-color);
            color: white;
        }

        .event-modal .btn-close-white {
            filter: brightness(0) invert(1);
        }

        .event-detail-row {
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .event-detail-label {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 4px;
        }

        .event-detail-value {
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .availability-container {
                margin: 10px;
                padding: 15px;
            }

            .facility-cell {
                min-width: 140px !important;
            }

            .status-card {
                padding: 6px 8px;
                font-size: 0.7rem;
            }

            .time-range-btn {
                padding: 6px 14px;
                font-size: 0.8rem;
            }

            .current-date {
                font-size: 0.9rem;
                min-width: 150px;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
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
            padding: 5px 10px;
            background: #333;
            color: white;
            font-size: 0.7rem;
            border-radius: 4px;
            white-space: nowrap;
            display: none;
            z-index: 100;
        }

        [data-tooltip]:hover:before {
            display: block;
        }

        /* Date Picker Styles */
        .date-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-picker-input {
            padding: 8px 12px;
            border: 1px solid var(--border-color, #dee2e6);
            border-radius: 50px;
            font-size: 0.9rem;
            cursor: pointer;
            background: white;
        }

        .date-picker-input:focus {
            outline: none;
            border-color: var(--primary-color, #0a336c);
        }

        /* Facility Group Toggle */
        .facility-group-toggle {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 50px;
            padding: 6px 12px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .facility-group-toggle:hover {
            background: #f8f9fa;
        }

        .facility-group-toggle.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>

    <main>
        <div class="availability-container">
            <!-- Navigation Header -->
            <div class="nav-header">
                <div class="date-nav">
                    <button class="date-nav-btn" id="prevDayBtn">←</button>
                    <span class="current-date" id="currentDateDisplay"></span>
                    <button class="date-nav-btn" id="nextDayBtn">→</button>
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

            <!-- Enhanced Filters Bar -->
            <div class="filters-bar">
                <div class="facility-filter-group" id="facilityFilters">
                    <!-- Will be populated dynamically with hierarchical structure -->
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search facility or event...">
                    <i class="bi bi-search"></i>
                </div>
                <button class="clear-filters-btn" id="clearFiltersBtn">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </button>
            </div>

            <!-- View Toggle for Hierarchical View -->
            <div class="time-range-bar" style="margin-top: -12px;">
                <button class="facility-group-toggle active" id="flatViewBtn">
                    <i class="bi bi-list-ul"></i> Flat View
                </button>
                <button class="facility-group-toggle" id="hierarchicalViewBtn">
                    <i class="bi bi-diagram-3"></i> Group by Building
                </button>
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
    <div class="modal fade event-modal" id="eventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Dynamic content -->
                </div>
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