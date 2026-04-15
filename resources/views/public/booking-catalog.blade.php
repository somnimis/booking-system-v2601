@extends('layouts.app')

@section('title', 'Booking Catalog')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/public/catalog.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}" />
    <style>
        #itemDetailModal {
            z-index: 9999 !important;
        }

        /* Filter dropdown radio button styles */
        #filterDropdownMenu .form-check {
            padding-left: 1.8rem;
            margin-bottom: 0.25rem;
        }

        #filterDropdownMenu .form-check-input {
            margin-left: -1.5rem;
            cursor: pointer;
        }

        #filterDropdownMenu .form-check-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #filterDropdownMenu .dropdown-header {
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }

        #filterDropdownMenu .badge {
            width: 12px;
            height: 12px;
            padding: 0;
            border-radius: 50%;
        }

        /* Active state for radio labels */
        #filterDropdownMenu .form-check-input:checked+.form-check-label {
            font-weight: 500;
            color: #004183ff;
        }

        /* Responsive adjustments for catalog header */
        @media (max-width: 1199.98px) {
            .catalog-type-tabs {
                width: 100%;
            }

            .catalog-type-tab {
                flex: 1 1 auto;
                text-align: center;
                white-space: nowrap;
                padding: 0.5rem 0.25rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 575.98px) {
            .catalog-type-tab {
                padding: 0.5rem 0.5rem;
                font-size: 0.85rem;
            }

            .catalog-type-tab i {
                margin-right: 0.25rem;
            }

            .filter-sort-dropdowns .btn {
                padding: 0.5rem 0.75rem;
            }

            /* Adjust search input for mobile */
            #catalogSearchInput {
                font-size: 14px;
                height: 38px;
            }

            #catalogSearchBtn,
            #clearSearchBtn {
                width: 38px;
                padding: 0.5rem;
            }
        }

        /* Target by ID */
        #singleFacilityAvailabilityModal {
            z-index: 999999999 !important;
        }

        /* If the modal-dialog is a child, also target it */
        #singleFacilityAvailabilityModal .modal-dialog {
            z-index: 999999999 !important;
        }


        /* For very small devices */
        @media (max-width: 360px) {
            .catalog-type-tab {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }

            .catalog-type-tab i {
                margin-right: 0;
                font-size: 1rem;
            }
        }

        /* Catalog Type Tabs */
        .catalog-type-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .catalog-type-tab {
            background-color: #fff;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .catalog-type-tab:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .catalog-type-tab.active {
            background-color: #004183ff;
            border-color: #004183ff;
            color: white;
        }

        .catalog-type-tab.active i {
            color: white;
        }

        .catalog-type-tab i {
            font-size: 1rem;
        }

        .loading-text {
            margin-top: 10px;
            color: #555;
        }

        /* Make sure the calendar container is positioned relatively */
        #facilityAvailabilityCalendar {
            position: relative;
            min-height: 500px;
        }

        /* Base Catalog Styles */
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

        .catalog-hero-content h2 {
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        /* Item type indicator */
        .item-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        /* Quantity input styles */
        .quantity-input {
            width: 70px !important;
            min-width: 70px;
        }

        .quantity-input.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .quantity-error {
            font-size: 0.75rem;
        }

        /* Action button containers */
        .equipment-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .equipment-quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-action-btn {
            min-height: 38px;
            white-space: nowrap;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        /* Layout-specific styles */
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
            object-fit: cover;
            flex-shrink: 0;
            box-sizing: border-box;
        }

        .list-layout .catalog-card-details {
            flex: 1;
            min-width: 0;
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
            justify-content: flex-start;
            height: 100%;
            box-sizing: border-box;
            align-items: stretch;
            overflow: hidden;
        }

        .grid-layout .catalog-card {
            padding: 0.25rem !important;
            position: relative;
        }

        .catalog-card-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-top: 0.75rem;
        }

        .status-banner {
            align-self: flex-start;
            width: auto !important;
            white-space: nowrap;
            padding: 0.25rem 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            color: white;
            font-size: 0.85rem;
        }

        .facility-description {
            flex-grow: 1;
        }

        .catalog-card-fee,
        .catalog-card-actions {
            margin-top: auto;
        }

        .catalog-card-actions {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.75rem;
        }

        /* Custom button styles */
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

        /* Modal responsive fixes */
        @media (max-width: 991.98px) {
            .modal-dialog.modal-xl {
                max-width: 98% !important;
                margin: 0.5rem;
            }

            .scrollable-left-column {
                max-height: 40vh;
                overflow-y: auto;
                margin-bottom: 1rem;
            }

            #facilityAvailabilityCalendar,
            #userFullCalendar {
                min-height: 50vh !important;
            }
        }

        @media (min-width: 992px) {
            .scrollable-left-column {
                max-height: 65vh;
                overflow-y: auto;
                padding-right: 5px;
            }
        }

        /* Calendar loading overlay */
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

        /* Mini calendar navigation */
        .mini-calendar .prev-month,
        .mini-calendar .next-month {
            background-color: white !important;
            border: 1px solid #dee2e6 !important;
            color: #495057 !important;
            border-radius: 4px !important;
            padding: 0.25rem 0.5rem !important;
            transition: all 0.2s ease !important;
            width: 32px !important;
            height: 32px !important;
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

        /* ============================================
                           MOBILE RESPONSIVE STYLES - Place this at the END of your style section
                           to ensure it overrides previous styles
                           ============================================ */

        /* Tablet and below (up to 991px) */
        @media (max-width: 991px) {

            /* Catalog Cards - Grid Layout */
            .grid-layout {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }

            .grid-layout .catalog-card {
                height: auto;
                min-height: 280px;
                display: flex;
                flex-direction: column;
            }

            .grid-layout .catalog-card-img {
                width: 100%;
                height: 140px !important;
                object-fit: cover;
            }

            .grid-layout .catalog-card-details {
                padding: 0.5rem;
            }

            .grid-layout .catalog-card-details h5 {
                font-size: 1rem;
                margin-bottom: 0.25rem;
                white-space: normal;
                word-wrap: break-word;
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .grid-layout .facility-description {
                display: none;
            }

            .grid-layout .catalog-card-meta {
                font-size: 0.8rem;
                margin: 0.25rem 0;
            }

            .grid-layout .catalog-card-meta span {
                display: block;
                margin-bottom: 0.25rem;
            }

            .grid-layout .catalog-card-fee {
                font-size: 0.85rem;
                margin-top: 0.25rem;
            }

            /* OVERRIDE: Force actions to stack vertically in grid layout */
            .grid-layout .catalog-card-actions {
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                gap: 0.5rem !important;
                padding: 0.5rem !important;
                width: 100% !important;
                border-left: none !important;
                border-top: 1px solid #eee !important;
                margin-top: 0.5rem !important;
            }

            .grid-layout .check-availability-btn {
                width: 100% !important;
                margin-top: 0.25rem !important;
            }

            .grid-layout .add-remove-btn {
                width: 100% !important;
            }

            /* Equipment actions in grid layout */
            .grid-layout .equipment-actions {
                width: 100% !important;
            }

            .grid-layout .equipment-quantity-selector {
                display: flex !important;
                width: 100% !important;
                gap: 0.5rem !important;
            }

            .grid-layout .equipment-quantity-selector .quantity-input {
                flex: 0 0 70px !important;
            }

            .grid-layout .equipment-quantity-selector .add-remove-btn {
                flex: 1 !important;
            }

            /* Catalog Cards - List Layout */
            .list-layout .catalog-card {
                height: auto;
                min-height: 140px;
                flex-direction: row;
                padding: 0.5rem !important;
            }

            .list-layout .catalog-card-img {
                width: 100px;
                height: 100px !important;
                object-fit: cover;
            }

            .list-layout .catalog-card-details {
                padding-left: 0.5rem;
            }

            .list-layout .catalog-card-details h5 {
                font-size: 0.95rem;
                margin-bottom: 0.1rem;
                white-space: normal;
                word-wrap: break-word;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                max-height: 2.4em;
            }

            .list-layout .catalog-card-details .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 0.25rem;
            }

            .list-layout .status-banner {
                font-size: 0.7rem;
                padding: 0.15rem 0.5rem;
                margin-bottom: 0.1rem;
            }

            .list-layout .catalog-card-meta {
                font-size: 0.75rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .list-layout .catalog-card-meta span {
                display: inline-flex;
                align-items: center;
                white-space: nowrap;
            }

            .list-layout .facility-description {
                display: none;
            }

            /* OVERRIDE: Force actions to stack vertically in list layout */
            .list-layout .catalog-card-actions {
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                gap: 0.5rem !important;
                padding: 0.5rem !important;
                width: 140px !important;
                border-left: 1px solid #eee !important;
                border-top: none !important;
                align-items: stretch !important;
            }

            .list-layout .catalog-card-fee {
                font-size: 0.75rem;
                margin-bottom: 0.15rem !important;
                white-space: nowrap;
                text-align: left !important;
                width: 100% !important;
            }

            .list-layout .check-availability-btn {
                width: 100% !important;
                font-size: 0.7rem !important;
            }

            .list-layout .add-remove-btn {
                width: 100% !important;
                font-size: 0.7rem !important;
            }

            /* Equipment actions in list layout */
            .list-layout .equipment-actions {
                width: 100% !important;
            }

            .list-layout .equipment-quantity-selector {
                display: flex !important;
                width: 100% !important;
                gap: 0.25rem !important;
                flex-wrap: wrap !important;
            }

            .list-layout .equipment-quantity-selector .quantity-input {
                flex: 1 1 50px !important;
                min-width: 50px !important;
            }

            .list-layout .equipment-quantity-selector .add-remove-btn {
                flex: 2 1 auto !important;
            }

            /* Action buttons on mobile */
            .form-action-btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
                min-height: 32px;
                white-space: nowrap;
            }

            .quantity-input {
                width: 50px !important;
                min-width: 50px;
                font-size: 0.75rem;
                height: 32px;
            }

            /* Item type badge */
            .item-type-badge {
                font-size: 0.6rem;
                padding: 0.15rem 0.4rem;
                top: 5px;
                right: 5px;
            }
        }

        /* Mobile (up to 575px) */
        @media (max-width: 575px) {

            /* Grid layout for very small screens */
            .grid-layout {
                grid-template-columns: 1fr;
            }

            .grid-layout .catalog-card {
                min-height: 260px;
            }

            .grid-layout .catalog-card-img {
                height: 130px !important;
            }

            /* List layout adjustments - becomes stacked */
            .list-layout .catalog-card {
                flex-wrap: wrap;
                height: auto;
            }

            .list-layout .catalog-card-img {
                width: 100%;
                height: 130px !important;
            }

            .list-layout .catalog-card-details {
                width: 100%;
                padding-left: 0;
                padding-top: 0.5rem;
            }

            /* OVERRIDE: For very small screens, make list layout actions full width */
            .list-layout .catalog-card-actions {
                width: 100% !important;
                flex-direction: column !important;
                border-top: 1px solid #eee !important;
                border-left: none !important;
                margin-top: 0.5rem !important;
                padding: 0.5rem 0 0 0 !important;
                gap: 0.5rem !important;
            }

            .list-layout .catalog-card-fee {
                width: 100%;
                text-align: left !important;
                margin-bottom: 0.25rem !important;
            }

            .list-layout .check-availability-btn {
                width: 100%;
            }

            /* Equipment actions on very small screens */
            .equipment-actions {
                width: 100%;
            }

            .equipment-quantity-selector {
                width: 100%;
                display: flex;
                gap: 0.5rem;
            }

            .equipment-quantity-selector .quantity-input {
                flex: 0 0 60px;
            }

            .equipment-quantity-selector .add-remove-btn {
                flex: 1;
            }

            /* Check availability button */
            .check-availability-btn {
                width: 100%;
                margin-top: 0.25rem;
            }

            /* Meta information */
            .catalog-card-meta {
                flex-wrap: wrap;
            }

            .catalog-card-meta span {
                font-size: 0.7rem;
                white-space: nowrap;
            }
        }

        /* Very small devices (up to 360px) */
        @media (max-width: 360px) {
            .list-layout .catalog-card-meta span {
                white-space: normal;
                font-size: 0.65rem;
            }

            .list-layout .catalog-card-details h5 {
                font-size: 0.9rem;
            }

            .form-action-btn {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
                min-height: 28px;
            }

            .quantity-input {
                width: 45px !important;
                font-size: 0.7rem;
                height: 28px;
            }

            .equipment-quantity-selector {
                gap: 0.25rem;
            }
        }
    </style>

    <section class="catalog-hero-section">
        <div class="catalog-hero-content">
            <h2 id="catalogHeroTitle">Booking Catalog</h2>
        </div>
    </section>

    <main class="main-catalog-section">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-lg-3 col-md-4">
                    <!-- Quick Links Card -->
                    <div class="quick-links-card mb-4">
                        <p class="mb-2">
                            Not sure when to book?<br />View available timeslots here.
                        </p>
                        <div class="d-grid gap-2">
                            <a href="/events-calendar" target="_blank" rel="noopener noreferrer" role="button"
                                class="btn btn-light btn-custom d-flex justify-content-center align-items-center"
                                id="eventsCalendarBtn">
                                <i class="fa-solid fa-calendar me-2"></i> Events Calendar
                            </a>


                            <!-- Requisition Form Button -->
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

                    <!-- Category Filter -->
                    <div class="sidebar-card">
                        <h5>Browse by Category</h5>
                        <div class="filter-list" id="categoryFilterList">
                            <x-skeleton :lines="6" rounded="lg" intensity="medium" :lineHeights="['24px', '20px', '20px', '20px', '20px', '16px']" :colWidths="[8, 12, 10, 9, 7, 5]" :margins="['15px', '10px', '10px', '10px', '10px', '0']" />
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <!-- Main Content -->
                <div class="col-lg-9 col-md-8">
                    <!-- Catalog Type Selector - Tabs, Search, and Filters -->
                    <div class="right-content-header w-100">
                        <!-- Mobile: Stack vertically, Desktop: Row with wrapping -->
                        <div
                            class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center gap-3 mb-3 w-100">
                            <!-- Left side: Catalog Tabs - Keep them on the left -->
                            <div class="catalog-type-tabs flex-wrap flex-sm-nowrap">
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="venues">
                                    Venues
                                </button>
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="rooms">
                                    Rooms
                                </button>
                                <button class="btn catalog-type-tab px-2 px-sm-3" data-type="equipment">
                                    Equipment
                                </button>
                            </div>

                            <!-- Right side container: Pushes filter and search to the right -->
                            <div
                                class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 ms-auto">
                                <!-- Consolidated Filter Dropdown -->
                                <div class="dropdown filter-sort-dropdowns flex-shrink-0">
                                    <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false" id="filterDropdown" data-bs-auto-close="outside">
                                        <i class="bi bi-sliders2 me-1"></i>
                                        <span class="d-none d-sm-inline">Filters</span>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;"
                                        id="filterDropdownMenu">
                                        <!-- Status Filter Section -->
                                        <li>
                                            <div class="dropdown-header fw-semibold text-dark px-2 py-1">
                                                Status
                                            </div>
                                        </li>
                                        <li>
                                            <div class="px-2 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusAll" value="All" data-status="All"
                                                        checked>
                                                    <label class="form-check-label w-100" for="statusAll">All</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusAvailable" value="Available"
                                                        data-status="Available">
                                                    <label class="form-check-label w-100" for="statusAvailable">
                                                        <span class="badge"
                                                            style="background-color: #28a745; width: 12px; height: 12px; display: inline-block; border-radius: 50%;"></span>
                                                        Available
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input status-option" type="radio"
                                                        name="statusFilter" id="statusUnavailable" value="Unavailable"
                                                        data-status="Unavailable">
                                                    <label class="form-check-label w-100" for="statusUnavailable">
                                                        <span class="badge"
                                                            style="background-color: #dc3545; width: 12px; height: 12px; display: inline-block; border-radius: 50%;"></span>
                                                        Unavailable
                                                    </label>
                                                </div>
                                            </div>
                                        </li>

                                        <!-- Divider -->
                                        <li>
                                            <hr class="dropdown-divider my-2">
                                        </li>

                                        <!-- Layout Toggle Section -->
                                        <li>
                                            <div class="dropdown-header fw-semibold text-dark px-2 py-1">
                                                Layout
                                            </div>
                                        </li>
                                        <li>
                                            <div class="px-2 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input layout-option" type="radio"
                                                        name="layoutFilter" id="layoutGrid" value="grid" data-layout="grid"
                                                        checked>
                                                    <label class="form-check-label w-100" for="layoutGrid">
                                                        <i class="bi bi-grid-3x3-gap-fill me-2"></i> Grid
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input layout-option" type="radio"
                                                        name="layoutFilter" id="layoutList" value="list" data-layout="list">
                                                    <label class="form-check-label w-100" for="layoutList">
                                                        <i class="bi bi-list-ul me-2"></i> List
                                                    </label>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Search bar -->
                                <div class="flex-grow-1" style="min-width: 350px;">
                                    <form id="catalogSearchForm">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="catalogSearchInput"
                                                placeholder="Search venues, rooms, or equipment..." aria-label="Search">
                                            <button class="btn btn-primary" type="submit" id="catalogSearchBtn">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn"
                                                style="display: none;">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Facility Availability Modal -->
                    <div class="modal fade" id="singleFacilityAvailabilityModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%;">
                            <div class="modal-content" style="min-height: 85vh;">
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
                                        <!-- Left Column: Mini Calendar & Info -->
                                        <div class="col-lg-3 col-md-12">
                                            <div class="scrollable-left-column">
                                                <!-- Facility Info Card -->
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <div class="facility-availability-info text-center">
                                                            <div id="facilityAvailabilityImage" class="mb-3"></div>
                                                            <h6 id="facilityAvailabilityTitle" class="mb-2">
                                                                <span id="facilityTitleText"></span>
                                                                <span class="text-muted" id="facilityCapacityInfo">
                                                                    <!-- Will be populated dynamically -->
                                                                </span>
                                                            </h6>
                                                            <div class="facility-meta small text-muted mb-2">
                                                                <div><i class="bi bi-tags-fill me-1"></i> <span
                                                                        id="facilityCategory"></span></div>
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
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center mb-3">
                                                                    <button class="btn btn-sm btn-secondary prev-month"
                                                                        type="button">
                                                                        <i class="bi bi-chevron-left"></i>
                                                                    </button>
                                                                    <h6 class="mb-0 month-year"
                                                                        id="availabilityCurrentMonthYear"></h6>
                                                                    <button class="btn btn-sm btn-secondary next-month"
                                                                        type="button">
                                                                        <i class="bi bi-chevron-right"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="calendar-header d-flex mb-2">
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        S</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        M</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        T</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        W</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        T</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        F</div>
                                                                    <div
                                                                        class="day-header text-center flex-fill small text-muted">
                                                                        S</div>
                                                                </div>
                                                                <div class="calendar-days"
                                                                    id="availabilityMiniCalendarDays">
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
                                            <!-- Loading Overlay - FIXED: Position relative wrapper -->
                                            <div class="position-relative flex-grow-1">
                                                <div class="calendar-loading-overlay" id="availabilityLoadingOverlay">
                                                    <div class="calendar-loading-spinner"></div>
                                                    <div class="loading-text">Loading calendar...</div>
                                                </div>

                                                <!-- Legend -->
                                                <div class="card">
                                                    <div class="card-body py-2">
                                                        <div id="dynamicLegend" class="d-flex flex-wrap gap-3">
                                                            <!-- Will be populated by JavaScript -->
                                                            <div class="text-muted small">Loading status colors...</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Calendar -->
                                                <div class="card flex-grow-1 mt-3">
                                                    <div class="card-body p-3 d-flex flex-column">
                                                        <div
                                                            class="calendar-content flex-grow-1 d-flex flex-column position-relative">
                                                            <div id="facilityAvailabilityCalendar" class="flex-grow-1">
                                                            </div>
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

                    <!-- Item Detail Modal (Shared) -->
                    <div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title" id="itemDetailModalLabel">Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body" id="itemDetailContent">
                                    <!-- Content loaded dynamically -->
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
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

    <!-- Event Details Modal (Shared) -->
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

    <!-- Availability Modal (Shared) -->
    <div class="modal fade" id="availabilityModal" tabindex="-1" aria-labelledby="availabilityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="availabilityModalLabel">Availability Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" style="min-height: 70vh;">
                    <div id="availabilityCalendar" style="height: 65vh;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="{{ asset('js/public/calendar.js') }}"></script>
    <script src="{{ asset('js/public/catalog.js') }}"></script>
    <script>
        /**
         * Booking Catalog Page Initialization
         */

        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', async function() {
            // Initialize Booking Catalog
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
                defaultLayout: 'list',
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

            // Initialize with CalendarModule (make sure CalendarModule is available globally)
            if (typeof CalendarModule !== 'undefined') {
                await bookingCatalog.init(CalendarModule);
                console.log('BookingCatalog initialized successfully');
            } else {
                console.error('CalendarModule not found. Make sure calendar.js is loaded first.');
            }

            // Make available globally if needed
            window.bookingCatalog = bookingCatalog;
        });
    </script>
@endsection