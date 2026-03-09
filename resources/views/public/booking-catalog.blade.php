@extends('layouts.app')

@section('title', 'Booking Catalog')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/public/catalog.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}" />
    <style>
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
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="itemDetailModalLabel">Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="itemDetailContent">
                                    <!-- Content loaded dynamically -->
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
    <script>
        // ============================================
        // UNIFIED BOOKING CATALOG - COMBINED FUNCTIONALITY
        // ============================================

        // Global configuration
        let CATALOG_TYPE = 'venues'; // 'facilities' or 'equipment'

        // Global variables
        let currentPage = 1;
        const itemsPerPage = 6;
        let allItems = [];
        let itemCategories = [];
        let filteredItems = [];
        let currentLayout = "list";
        let selectedItems = [];
        let allowedStatusIds = [1, 2];
        let statusFilter = "All";
        let formStatuses = {};
        let availabilityCalendarInstance = null;
        let currentFacilityId = null;
        let searchQuery = '';
        let searchTimeout = null;


        // DOM elements
        const loadingIndicator = document.getElementById("loadingIndicator");
        const catalogItemsContainer = document.getElementById("catalogItemsContainer");
        const categoryFilterList = document.getElementById("categoryFilterList");
        const pagination = document.getElementById("pagination");
        const requisitionBadge = document.getElementById("requisitionBadge");
        const catalogHeroTitle = document.getElementById("catalogHeroTitle");

        // API endpoints configuration
        const API_ENDPOINTS = {
            venues: {
                items: '/api/facilities/venues',     // Buildings, auditoriums, outdoor spaces
                categories: '/api/facility-categories/venues'
            },
            rooms: {
                items: '/api/facilities/rooms',       // Classrooms, dorms, labs
                categories: '/api/facility-categories/rooms'
            },
            equipment: {
                items: '/api/equipment',
                categories: '/api/equipment-categories'
            }
        };

        // ============================================
        // UTILITY FUNCTIONS
        // ============================================

        // ============================================
        // CACHE MANAGEMENT WITH LOCALSTORAGE
        // ============================================

        function saveToCache(type, data) {
            try {
                const cacheKey = `catalog_cache_${type}`;
                const cacheData = {
                    items: data.items,
                    categories: data.categories,
                    timestamp: Date.now()
                };
                localStorage.setItem(cacheKey, JSON.stringify(cacheData));
                console.log(`Cached ${type} data`);
            } catch (e) {
                console.warn('Failed to save to cache:', e);
            }
        }

        function getFromCache(type) {
            try {
                const cacheKey = `catalog_cache_${type}`;
                const cached = localStorage.getItem(cacheKey);
                if (!cached) return null;

                const data = JSON.parse(cached);
                return data;
            } catch (e) {
                console.warn('Failed to read from cache:', e);
                return null;
            }
        }

        function clearCache(type = null) {
            if (type) {
                localStorage.removeItem(`catalog_cache_${type}`);
            } else {
                // Clear all catalog caches
                ['venues', 'rooms', 'equipment'].forEach(t => {
                    localStorage.removeItem(`catalog_cache_${t}`);
                });
            }
        }

async function fetchData(url, options = {}) {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    if (!csrfToken) {
        console.error('CSRF token not found in meta tag');
        // Fallback: try to get from cookie
        const cookieToken = getCookie('XSRF-TOKEN');
        if (!cookieToken) {
            throw new Error('CSRF token not available');
        }
    }

    try {
        // Ensure headers are properly set
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {})
        };

        // For Laravel Sanctum, include credentials
        const response = await fetch(url, {
            ...options,
            headers: headers,
            credentials: 'same-origin', // Important for cookies
            mode: 'same-origin'
        });

        if (!response.ok) {
            // Handle 419 CSRF mismatch specifically
            if (response.status === 419) {
                console.error('CSRF token mismatch - refreshing page');
                // Refresh the page to get a new CSRF token
                window.location.reload();
                throw new Error('Session expired. Please try again.');
            }

            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', {
            url: url,
            error: error.message,
            stack: error.stack
        });
        throw error;
    }
}

// Helper function to get cookie value
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}


        function showToast(message, type = "success", duration = 3000) {
            const toast = document.createElement("div");
            toast.className = `toast align-items-center border-0 position-fixed end-0 mb-2`;
            toast.style.zIndex = '1100';
            toast.style.bottom = '0';
            toast.style.right = '0';
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
        }

        function updateCartBadge() {
            if (!requisitionBadge) return;
            if (selectedItems.length > 0) {
                requisitionBadge.textContent = selectedItems.length;
                requisitionBadge.style.display = "";
                requisitionBadge.classList.remove("d-none");
            } else {
                requisitionBadge.style.display = "none";
                requisitionBadge.classList.add("d-none");
            }
        }

        function getPrimaryImage(item) {
            return item.images?.find(img => img.image_type === 'Primary')?.image_url ||
                'https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png';
        }

        function truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
        }

        // ============================================
        // SCHEDULE & ALL-DAY UTILITIES
        // ============================================

        function getRequestInfo() {
            return JSON.parse(localStorage.getItem('request_info') || '{}');
        }

        function validateScheduleDates(startDate, endDate, startTime, endTime, allDay) {
            if (!startDate || !endDate) {
                return { valid: false, message: "Start date and end date are required" };
            }

            if (allDay) {
                // All-day events: only validate dates
                if (new Date(startDate) > new Date(endDate)) {
                    return { valid: false, message: "End date must be after or equal to start date" };
                }
                return { valid: true };
            } else {
                // Regular events: validate dates AND times
                if (!startTime || !endTime) {
                    return { valid: false, message: "Start time and end time are required" };
                }

                const startDateTime = new Date(`${startDate}T${startTime}`);
                const endDateTime = new Date(`${endDate}T${endTime}`);

                if (startDateTime >= endDateTime) {
                    return { valid: false, message: "End time must be after start time" };
                }

                return { valid: true };
            }
        }

        function formatScheduleDisplay(requestInfo) {
            if (!requestInfo.start_date) return 'No schedule set';

            const startDate = new Date(requestInfo.start_date).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            });

            if (requestInfo.all_day) {
                if (requestInfo.start_date === requestInfo.end_date) {
                    return `${startDate} (All Day)`;
                } else {
                    const endDate = new Date(requestInfo.end_date).toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    });
                    return `${startDate} - ${endDate} (All Day)`;
                }
            } else {
                const endDate = new Date(requestInfo.end_date).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric', year: 'numeric'
                });
                return `${startDate} ${requestInfo.start_time} - ${endDate} ${requestInfo.end_time}`;
            }
        }

        function saveRequestInfo(formData) {
            const requestInfo = {
                user_type: formData.user_type,
                first_name: formData.first_name,
                last_name: formData.last_name,
                email: formData.email,
                contact_number: formData.contact_number,
                organization_name: formData.organization_name,
                school_id: formData.school_id,
                num_participants: formData.num_participants,
                purpose_id: formData.purpose_id,
                additional_requests: formData.additional_requests,
                endorser: formData.endorser,
                date_endorsed: formData.date_endorsed,
                start_date: formData.start_date,
                end_date: formData.end_date,
                start_time: formData.all_day ? null : formData.start_time,
                end_time: formData.all_day ? null : formData.end_time,
                all_day: formData.all_day || false
            };

            localStorage.setItem('request_info', JSON.stringify(requestInfo));

            // Update any schedule summary elements
            updateScheduleSummary();

            return requestInfo;
        }

        function updateScheduleSummary() {
            const requestInfo = getRequestInfo();
            const scheduleEl = document.getElementById('schedule-summary');
            if (scheduleEl) {
                scheduleEl.textContent = formatScheduleDisplay(requestInfo);
            }
        }

        // ============================================
        // CATALOG TYPE MANAGEMENT
        // ============================================

        function switchCatalogType(type) {
            // Clear search when switching tabs
            clearSearch();

            CATALOG_TYPE = type;
            currentPage = 1;

            const titles = {
                venues: 'Venues & Event Spaces',
                rooms: 'Rooms & Dormitories',
                equipment: 'Equipment Catalog'
            };

            // Update UI
            catalogHeroTitle.textContent = titles[type];

            // Update active state on tab buttons
            document.querySelectorAll('.catalog-type-tab').forEach(tab => {
                if (tab.dataset.type === type) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });

            // Load data (will use cache if available)
            loadCatalogData();
        }
        // ============================================
        // DATA LOADING
        // ============================================

        async function loadCatalogData() {
            try {
                const api = API_ENDPOINTS[CATALOG_TYPE];

                // Build URL with search parameter if exists
                let itemsUrl = api.items;
                if (searchQuery && searchQuery.trim() !== '') {
                    itemsUrl += `?search=${encodeURIComponent(searchQuery)}`;
                }

                // Check localStorage first - BUT ONLY IF NO SEARCH QUERY
                const cached = !searchQuery ? getFromCache(CATALOG_TYPE) : null;

                // Only show spinner if we don't have cached data
                if (!cached) {
                    loadingIndicator.style.display = "block";
                    catalogItemsContainer.classList.add("d-none");
                } else {
                    loadingIndicator.style.display = "none";
                }

                let itemsData, categoriesData;

                if (cached) {
                    console.log(`Loading ${CATALOG_TYPE} from localStorage cache`);
                    allItems = cached.items || [];
                    itemCategories = cached.categories || [];

                    allItems = allItems.filter(item => allowedStatusIds.includes(item.status?.status_id));

                    renderCategoryFilters();
                    filterAndRenderItems();
                    updateCartBadge();

                    catalogItemsContainer.classList.remove("d-none");
                } else {
                    console.log(`Fetching ${CATALOG_TYPE} from API with search: ${searchQuery}`);

                    // Fetch from API with search parameter
                    [itemsData, categoriesData] = await Promise.all([
                        fetchData(itemsUrl),
                        fetchData(api.categories)
                    ]);

                    // Extract items (same as before)
                    if (itemsData && itemsData.success && itemsData.data && itemsData.data.data) {
                        allItems = itemsData.data.data;
                    } else if (itemsData && Array.isArray(itemsData)) {
                        allItems = itemsData;
                    } else if (itemsData && itemsData.data && Array.isArray(itemsData.data)) {
                        allItems = itemsData.data;
                    } else {
                        allItems = [];
                    }

                    // Extract categories (same as before)
                    if (Array.isArray(categoriesData)) {
                        itemCategories = categoriesData;
                    } else if (categoriesData && categoriesData.data && Array.isArray(categoriesData.data)) {
                        itemCategories = categoriesData.data;
                    } else if (categoriesData && categoriesData.success && categoriesData.data) {
                        itemCategories = Array.isArray(categoriesData.data) ? categoriesData.data : [];
                    } else {
                        itemCategories = [];
                    }

                    allItems = allItems.filter(item => allowedStatusIds.includes(item.status?.status_id));

                    // Only cache if no search query
                    if (!searchQuery) {
                        saveToCache(CATALOG_TYPE, {
                            items: allItems,
                            categories: itemCategories
                        });
                    }

                    renderCategoryFilters();
                    filterAndRenderItems();
                    updateCartBadge();

                    catalogItemsContainer.classList.remove("d-none");
                    loadingIndicator.style.display = "none";
                }

                // Fetch selected items (always fresh)
                try {
                    const selectedItemsResponse = await fetchData('/api/requisition/get-items');
                    selectedItems = selectedItemsResponse.data?.selected_items || [];
                    updateCartBadge();
                } catch (e) {
                    console.warn('Failed to fetch selected items:', e);
                    selectedItems = [];
                }

            } catch (error) {
                console.error(`Error loading ${CATALOG_TYPE} data:`, error);
                showToast(`Failed to load ${CATALOG_TYPE}. Please try again.`, "error");
                loadingIndicator.style.display = "none";
            }
        }

        // ============================================
        // CATEGORY FILTERS
        // ============================================

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

            if (CATALOG_TYPE === 'equipment') {
                // Equipment categories (simple list)
                renderEquipmentCategories();
            } else {
                // For both venues and rooms, use facility categories
                renderFacilityCategories();
            }

            setupCategoryFilterEvents();
        }

        function renderFacilityCategories() {
            itemCategories.forEach((category) => {
                const categoryItem = document.createElement("div");
                categoryItem.className = "category-item";
                categoryItem.innerHTML = `
                                                                                                                                <div class="form-check d-flex justify-content-between align-items-center">
                                                                                                                                    <div>
                                                                                                                                        <input class="form-check-input category-filter" type="checkbox" id="category${category.category_id}" value="${category.category_id}">
                                                                                                                                        <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
                                                                                                                                    </div>
                                                                                                                                    ${category.subcategories && category.subcategories.length > 0 ?
                        '<i class="bi bi-chevron-up toggle-arrow" style="cursor:pointer"></i>' : ''}
                                                                                                                                </div>
                                                                                                                                ${category.subcategories && category.subcategories.length > 0 ? `
                                                                                                                                    <div class="subcategory-list ms-3" style="overflow: hidden; max-height: ${category.subcategories.length * 35}px;">
                                                                                                                                        ${category.subcategories.map(sub => `
                                                                                                                                            <div class="form-check">
                                                                                                                                                <input class="form-check-input subcategory-filter" type="checkbox" id="subcategory${sub.subcategory_id}" value="${sub.subcategory_id}" data-category="${category.category_id}">
                                                                                                                                                <label class="form-check-label" for="subcategory${sub.subcategory_id}">${sub.subcategory_name}</label>
                                                                                                                                            </div>
                                                                                                                                        `).join("")}
                                                                                                                                    </div>
                                                                                                                                ` : ''}
                                                                                                                            `;
                categoryFilterList.appendChild(categoryItem);

                // Add toggle functionality for subcategories
                const toggleArrow = categoryItem.querySelector(".toggle-arrow");
                if (toggleArrow) {
                    const subcategoryList = categoryItem.querySelector(".subcategory-list");

                    toggleArrow.addEventListener("click", function () {
                        const isExpanded = subcategoryList.style.maxHeight !== "0px";
                        if (isExpanded) {
                            subcategoryList.style.maxHeight = "0";
                            toggleArrow.classList.replace("bi-chevron-up", "bi-chevron-down");
                        } else {
                            subcategoryList.style.maxHeight = `${subcategoryList.scrollHeight}px`;
                            toggleArrow.classList.replace("bi-chevron-down", "bi-chevron-up");
                        }
                    });
                }
            });
        }

        function renderEquipmentCategories() {
            itemCategories.forEach((category) => {
                const categoryItem = document.createElement("div");
                categoryItem.className = "category-item";
                categoryItem.innerHTML = `
                                                                                                                                                            <div class="form-check">
                                                                                                                                                                <input class="form-check-input category-filter" type="checkbox" id="category${category.category_id}" value="${category.category_id}">
                                                                                                                                                                <label class="form-check-label" for="category${category.category_id}">${category.category_name}</label>
                                                                                                                                                            </div>
                                                                                                                                                        `;
                categoryFilterList.appendChild(categoryItem);
            });
        }

        function setupCategoryFilterEvents() {
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

            categoryCheckboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    // Only handle subcategories for venues/rooms (not equipment)
                    if (CATALOG_TYPE !== 'equipment') {
                        const catId = cb.value;
                        const relatedSubs = subcategoryCheckboxes.filter(sub => sub.dataset.category === catId);

                        if (!cb.checked) {
                            // When category is unchecked, uncheck all its subcategories
                            relatedSubs.forEach(sub => {
                                sub.checked = false;
                            });
                        } else {
                            // When category is checked, check all its subcategories
                            relatedSubs.forEach(sub => {
                                sub.checked = true;
                            });
                        }
                    }
                    updateAllCategoriesCheckbox();
                    filterAndRenderItems();
                });
            });

            subcategoryCheckboxes.forEach(sub => {
                sub.addEventListener('change', function () {
                    updateAllCategoriesCheckbox();
                    filterAndRenderItems();
                });
            });

            allCategoriesCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    categoryCheckboxes.forEach(cb => {
                        cb.checked = false;
                    });
                    subcategoryCheckboxes.forEach(sub => {
                        sub.checked = false;
                    });
                    allCategoriesCheckbox.disabled = true;
                    filterAndRenderItems();
                }
            });

            // Initialize: Make sure all subcategories start unchecked and enabled
            if (CATALOG_TYPE !== 'equipment') {
                subcategoryCheckboxes.forEach(sub => {
                    sub.checked = false;
                    sub.disabled = false;
                });
            }
        }
        // ============================================
        // FILTERING
        // ============================================
        function filterItems() {
            const allCategoriesCheckbox = document.getElementById('allCategories');
            const categoryCheckboxes = Array.from(document.querySelectorAll('.category-filter')).filter(cb => cb.id !== "allCategories");
            const subcategoryCheckboxes = Array.from(document.querySelectorAll('.subcategory-filter'));

            // Start with all items
            filteredItems = [...allItems];

            // Apply search filter if there's a query
            if (searchQuery && searchQuery.trim() !== '') {
                const query = searchQuery.toLowerCase().trim();
                filteredItems = filteredItems.filter(item => {
                    if (CATALOG_TYPE === 'equipment') {
                        // Search in equipment items (item_name through the items relationship)
                        if (item.items && Array.isArray(item.items)) {
                            return item.items.some(equipmentItem =>
                                equipmentItem.item_name &&
                                equipmentItem.item_name.toLowerCase().includes(query)
                            );
                        }
                        return false;
                    } else {
                        // Search in facilities (facility_name and description)
                        const nameMatch = item.facility_name &&
                            item.facility_name.toLowerCase().includes(query);
                        const descMatch = item.description &&
                            item.description.toLowerCase().includes(query);
                        return nameMatch || descMatch;
                    }
                });
            }

            // Filter by status
            if (statusFilter === "Available") {
                filteredItems = filteredItems.filter(item => item.status.status_id === 1);
            } else if (statusFilter === "Unavailable") {
                filteredItems = filteredItems.filter(item => item.status.status_id === 2);
            }

            // Filter by category if not "All Categories"
            if (!allCategoriesCheckbox.checked) {
                if (CATALOG_TYPE === 'equipment') {
                    filterEquipmentByCategory(categoryCheckboxes);
                } else {
                    // Both venues and rooms use the same facility filtering logic
                    filterFacilitiesByCategory(categoryCheckboxes, subcategoryCheckboxes);
                }
            }
        }

        function filterFacilitiesByCategory(categoryCheckboxes, subcategoryCheckboxes) {
            const selectedCategories = categoryCheckboxes.filter(cb => cb.checked).map(cb => cb.value);
            const selectedSubcategories = subcategoryCheckboxes.filter(cb => cb.checked).map(cb => cb.value);

            if (selectedCategories.length === 0 && selectedSubcategories.length === 0) {
                filteredItems = [];
                return;
            }

            filteredItems = filteredItems.filter(facility => {
                // Check if the facility matches subcategory filter
                const matchesSubcategory = selectedSubcategories.length > 0 &&
                    facility.subcategory &&
                    selectedSubcategories.includes(facility.subcategory.subcategory_id.toString());

                // Check if the facility matches category filter
                const matchesCategory = selectedCategories.length > 0 &&
                    selectedCategories.includes(facility.category.category_id.toString());

                if (selectedSubcategories.length > 0 && selectedCategories.length > 0) {
                    // If both filters are active, facility must match at least one
                    return matchesSubcategory || matchesCategory;
                } else if (selectedSubcategories.length > 0) {
                    // Only subcategory filter is active
                    return matchesSubcategory;
                } else {
                    // Only category filter is active
                    return matchesCategory;
                }
            });
        }

        function filterEquipmentByCategory(categoryCheckboxes) {
            const selectedCategories = categoryCheckboxes.filter(cb => cb.checked).map(cb => cb.value);

            if (selectedCategories.length === 0) {
                filteredItems = [];
                return;
            }

            filteredItems = filteredItems.filter(equipment =>
                selectedCategories.includes(equipment.category.category_id.toString())
            );
        }

        // ============================================
        // RENDERING
        // ============================================

        function filterAndRenderItems() {
            filterItems();
            renderItems(filteredItems);
            renderPagination(filteredItems.length);
        }

        function renderItems(items) {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedItems = items.slice(startIndex, startIndex + itemsPerPage);

            catalogItemsContainer.innerHTML = "";

            if (paginatedItems.length === 0) {
                catalogItemsContainer.classList.remove("grid-layout", "list-layout");

                // Choose icon based on catalog type
                let icon = 'bi-building';
                if (CATALOG_TYPE === 'rooms') icon = 'bi-door-open';
                else if (CATALOG_TYPE === 'equipment') icon = 'bi-box-seam';

                catalogItemsContainer.innerHTML = `
                                                                                                                                    <div style="grid-column: 1 / -1; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 220px; width: 100%;">
                                                                                                                                        <i class="bi ${icon} fs-1 text-muted"></i>
                                                                                                                                        <h4 class="mt-2">No ${CATALOG_TYPE} found</h4>
                                                                                                                                    </div>
                                                                                                                                `;
                return;
            }

            catalogItemsContainer.classList.remove("grid-layout", "list-layout");
            catalogItemsContainer.classList.add(`${currentLayout}-layout`);

            if (currentLayout === "grid") {
                renderGridLayout(paginatedItems);
            } else {
                renderListLayout(paginatedItems);
            }

            // Add click handlers for item titles
            document.querySelectorAll(".catalog-card-details h5").forEach((title) => {
                title.addEventListener("click", function () {
                    const id = this.getAttribute("data-id");
                    showItemDetails(id);
                });
            });
        }

        function renderGridLayout(items) {
            catalogItemsContainer.innerHTML = items.map(item => {
                const isEquipment = CATALOG_TYPE === 'equipment';
                const itemName = isEquipment ? item.equipment_name : item.facility_name;
                const itemId = isEquipment ? item.equipment_id : item.facility_id;
                const primaryImage = getPrimaryImage(item);
                const truncatedName = truncateText(itemName, 18);
                const description = truncateText(item.description || 'No description available', 100);

                return `
                                                                                                                                                            <div class="catalog-card">
                                                                                                                                                                <!-- Item type badge -->
                                                                                                                                                                <span class="item-type-badge badge ${isEquipment ? 'bg-info' : 'bg-warning'}">
                                                                                                                                                                    ${isEquipment ? 'Equipment' : 'Facility'}
                                                                                                                                                                </span>

                                                                                                                                                                <img src="${primaryImage}" 
                                                                                                                                                                     alt="${itemName}" 
                                                                                                                                                                     class="catalog-card-img"
                                                                                                                                                                     onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">

                                                                                                                                                                <div class="catalog-card-details">
                                                                                                                                                                    <h5 data-id="${itemId}" title="${itemName}">${truncatedName}</h5>
                                                                                                                                                                    <span class="status-banner" style="background-color: ${item.status.color_code}">
                                                                                                                                                                        ${item.status.status_name}
                                                                                                                                                                    </span>

                                                                                                                                                                    <div class="catalog-card-meta">
                                                                                                                                                                        ${isEquipment
                        ? `<span><i class="bi bi-tags-fill"></i> ${item.category.category_name}</span>
                                                                                                                                                                               <span><i class="bi bi-box-seam"></i> ${item.available_quantity}/${item.total_quantity} available</span>`
                        : `<span><i class="bi bi-people-fill"></i> ${item.capacity || "N/A"}</span>
                                                                                                                                                                               <span><i class="bi bi-tags-fill"></i> ${item.subcategory?.subcategory_name || item.category.category_name}</span>`
                    }
                                                                                                                                                                    </div>

                                                                                                                                                                    <p class="facility-description" title="${item.description || ''}">${description}</p>

                                                                                                                                                                    <div class="catalog-card-fee">
                                                                                                                                                                        <i class="bi bi-cash-stack"></i> ₱${parseFloat(item.external_fee).toLocaleString()} (${item.rate_type})
                                                                                                                                                                    </div>
                                                                                                                                                                </div>

                                                                                                                                                                <div class="catalog-card-actions">
                                                                                                                                                                    ${isEquipment ? getEquipmentActionsHtml(item) : getFacilityActionsHtml(item)}
                                                                                                                                                                    ${getCheckAvailabilityButtonHtml(item)}
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        `;
            }).join('');
        }

        function renderListLayout(items) {
            catalogItemsContainer.innerHTML = items.map(item => {
                const isEquipment = CATALOG_TYPE === 'equipment';
                const itemName = isEquipment ? item.equipment_name : item.facility_name;
                const itemId = isEquipment ? item.equipment_id : item.facility_id;
                const primaryImage = getPrimaryImage(item);
                const truncatedName = truncateText(itemName, 30);
                const description = truncateText(item.description || 'No description available', 150);

                return `
                                                                                                                                                            <div class="catalog-card">
                                                                                                                                                                <img src="${primaryImage}" 
                                                                                                                                                                     alt="${itemName}" 
                                                                                                                                                                     class="catalog-card-img"
                                                                                                                                                                     onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">

                                                                                                                                                                <div class="catalog-card-details">
                                                                                                                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                                                                                                        <h5 data-id="${itemId}" title="${itemName}">${truncatedName}</h5>
                                                                                                                                                                        <span class="status-banner" style="background-color: ${item.status.color_code}">
                                                                                                                                                                            ${item.status.status_name}
                                                                                                                                                                        </span>
                                                                                                                                                                    </div>

                                                                                                                                                                    <div class="catalog-card-meta">
                                                                                                                                                                        ${isEquipment
                        ? `<span><i class="bi bi-tags-fill"></i> ${item.category.category_name}</span>
                                                                                                                                                                               <span><i class="bi bi-box-seam"></i> ${item.available_quantity}/${item.total_quantity} available</span>`
                        : `<span><i class="bi bi-people-fill"></i> ${item.capacity || "N/A"}</span>
                                                                                                                                                                               <span><i class="bi bi-tags-fill"></i> ${item.subcategory?.subcategory_name || item.category.category_name}</span>`
                    }
                                                                                                                                                                    </div>

                                                                                                                                                                    <p class="facility-description" title="${item.description || ''}">${description}</p>
                                                                                                                                                                </div>

                                                                                                                                                                <div class="catalog-card-actions">
                                                                                                                                                                    <div class="catalog-card-fee mb-2 text-center">
                                                                                                                                                                        <i class="bi bi-cash-stack"></i> ₱${parseFloat(item.external_fee).toLocaleString()} (${item.rate_type})
                                                                                                                                                                    </div>

                                                                                                                                                                    ${isEquipment ? getEquipmentActionsHtml(item) : getFacilityActionsHtml(item)}

                                                                                                                                                                    ${getCheckAvailabilityButtonHtml(item)}
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        `;
            }).join('');
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

        // ============================================
        // ACTION BUTTONS
        // ============================================

        function getEquipmentActionsHtml(item) {
            const isSelected = selectedItems.some(
                selectedItem => selectedItem.type === 'equipment' &&
                    parseInt(selectedItem.equipment_id) === item.equipment_id
            );

            const selectedItem = isSelected ? selectedItems.find(
                selectedItem => selectedItem.type === 'equipment' &&
                    parseInt(selectedItem.equipment_id) === item.equipment_id
            ) : null;

            const currentQty = selectedItem ? selectedItem.quantity : 1;
            const maxQty = item.available_quantity || 0;
            const isUnavailable = item.status.status_id !== 1 || maxQty === 0;

            if (isUnavailable) {
                return `
                                                                                                                                                            <div class="equipment-actions">
                                                                                                                                                                <div class="equipment-quantity-selector">
                                                                                                                                                                    <input type="number" 
                                                                                                                                                                           class="form-control quantity-input" 
                                                                                                                                                                           value="${currentQty}" 
                                                                                                                                                                           min="1" 
                                                                                                                                                                           max="${maxQty}"
                                                                                                                                                                           disabled>
                                                                                                                                                                    <button class="btn btn-secondary add-remove-btn form-action-btn" disabled>
                                                                                                                                                                        Unavailable
                                                                                                                                                                    </button>
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        `;
            }

            if (isSelected) {
                return `
                                                                                                                                                            <div class="equipment-actions">
                                                                                                                                                                <div class="equipment-quantity-selector">
                                                                                                                                                                    <input type="number" 
                                                                                                                                                                           class="form-control quantity-input" 
                                                                                                                                                                           value="${currentQty}" 
                                                                                                                                                                           min="1" 
                                                                                                                                                                           max="${maxQty}">
                                                                                                                                                                    <button class="btn btn-danger add-remove-btn form-action-btn" 
                                                                                                                                                                            data-id="${item.equipment_id}" 
                                                                                                                                                                            data-type="equipment" 
                                                                                                                                                                            data-action="remove">
                                                                                                                                                                        Remove
                                                                                                                                                                    </button>
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        `;
            } else {
                return `
                                                                                                                                                            <div class="equipment-actions">
                                                                                                                                                                <div class="equipment-quantity-selector">
                                                                                                                                                                    <input type="number" 
                                                                                                                                                                           class="form-control quantity-input" 
                                                                                                                                                                           value="1" 
                                                                                                                                                                           min="1" 
                                                                                                                                                                           max="${maxQty}">
                                                                                                                                                                    <button class="btn btn-primary add-remove-btn form-action-btn" 
                                                                                                                                                                            data-id="${item.equipment_id}" 
                                                                                                                                                                            data-type="equipment" 
                                                                                                                                                                            data-action="add">
                                                                                                                                                                        Add
                                                                                                                                                                    </button>
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        `;
            }
        }

        function getFacilityActionsHtml(item) {
            const isUnavailable = item.status.status_id === 2;
            const isSelected = selectedItems.some(
                selectedItem => selectedItem.type === 'facility' &&
                    parseInt(selectedItem.facility_id) === item.facility_id
            );

            if (isUnavailable) {
                return `
                                                                                                                                                            <button class="btn btn-secondary add-remove-btn form-action-btn" disabled>
                                                                                                                                                                Unavailable
                                                                                                                                                            </button>
                                                                                                                                                        `;
            }

            if (isSelected) {
                return `
                                                                                                                                                            <button class="btn btn-danger add-remove-btn form-action-btn" 
                                                                                                                                                                    data-id="${item.facility_id}" 
                                                                                                                                                                    data-type="facility" 
                                                                                                                                                                    data-action="remove">
                                                                                                                                                                Remove from form
                                                                                                                                                            </button>
                                                                                                                                                        `;
            } else {
                return `
                                                                                                                                                            <button class="btn btn-primary add-remove-btn form-action-btn" 
                                                                                                                                                                    data-id="${item.facility_id}" 
                                                                                                                                                                    data-type="facility" 
                                                                                                                                                                    data-action="add">
                                                                                                                                                                Add to form
                                                                                                                                                            </button>
                                                                                                                                                        `;
            }
        }

        function getCheckAvailabilityButtonHtml(item) {
            const isEquipment = CATALOG_TYPE === 'equipment';
            const itemId = isEquipment ? item.equipment_id : item.facility_id;
            const itemName = isEquipment ? item.equipment_name : item.facility_name;
            const primaryImage = getPrimaryImage(item);
            const availableQty = isEquipment ? item.available_quantity : null;

            // For equipment, we need to pass the available quantity
            const additionalData = isEquipment ?
                `data-item-available-qty="${availableQty}" data-item-total-qty="${item.total_quantity}"` :
                `data-item-capacity="${item.capacity || 'N/A'}" data-item-fee="${parseFloat(item.external_fee).toLocaleString()}"`;

            return `
                                                                                                                                                        <button class="btn btn-light btn-custom check-availability-btn form-action-btn" 
                                                                                                                                                                data-item-id="${itemId}"
                                                                                                                                                                data-item-name="${itemName}"
                                                                                                                                                                data-item-type="${isEquipment ? 'equipment' : 'facility'}"
                                                                                                                                                                data-item-image="${primaryImage}"
                                                                                                                                                                data-item-category="${item.category.category_name}"
                                                                                                                                                                data-item-status="${item.status.status_name}"
                                                                                                                                                                data-item-status-color="${item.status.color_code}"
                                                                                                                                                                ${additionalData}>
                                                                                                                                                            Check Availability
                                                                                                                                                        </button>
                                                                                                                                                    `;
        }

        // ============================================
        // FORM MANAGEMENT
        // ============================================

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

        if (!response || !response.success) {
            throw new Error(response?.message || "Failed to add item");
        }

        selectedItems = response.data?.selected_items || [];
        showToast(`${type} added to form`, 'success');
        await updateAllUI();
        localStorage.setItem('formUpdated', Date.now().toString());
        
    } catch (error) {
        console.error("Add to form error:", error);
        
        // Handle specific error types
        if (error.message.includes('419')) {
            showToast('Session expired. Please refresh the page.', 'error');
            // Optionally refresh CSRF token
            await refreshCsrfToken();
        } else {
            showToast(error.message || "Error adding item to form", "error");
        }
    }
}

async function refreshCsrfToken() {
    try {
        const response = await fetch('/csrf-token', {
            method: 'GET',
            credentials: 'same-origin'
        });
        const data = await response.json();
        document.querySelector('meta[name="csrf-token"]').content = data.csrf_token;
    } catch (error) {
        console.error('Failed to refresh CSRF token:', error);
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

        // ============================================
        // SEARCH FUNCTIONS - Place near top with other global variables
        // ============================================


        const searchForm = document.getElementById('catalogSearchForm');
        const searchInput = document.getElementById('catalogSearchInput');
        function performSearch() {
            const newQuery = searchInput.value.trim();
            if (newQuery !== searchQuery) {
                searchQuery = newQuery;
                currentPage = 1; // Reset to first page on new search

                // Show/hide clear button
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = searchQuery ? 'block' : 'none';
                }

                filterAndRenderItems();
            }
        }

        function clearSearch() {
            if (searchInput) {
                searchInput.value = '';
            }
            searchQuery = '';
            currentPage = 1;

            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }

            filterAndRenderItems();
        }

        // ============================================
        // EVENT HANDLERS
        // ============================================

        function setupEventListeners() {

            // Search form submission

            const clearSearchBtn = document.getElementById('clearSearchBtn');

            // Catalog type switcher - Tab buttons
            document.querySelectorAll('.catalog-type-tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    switchCatalogType(tab.dataset.type);
                });
            });

            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    performSearch();
                });
            }

            if (searchInput) {
                // Real-time search with debounce (optional)
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        performSearch();
                    }, 300); // Wait 300ms after user stops typing
                });

                // Clear search on Escape key
                searchInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        clearSearch();
                    }
                });
            }

            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', () => {
                    clearSearch();
                });
            }

            // Add/Remove buttons
            catalogItemsContainer.addEventListener("click", async (e) => {
                const button = e.target.closest(".add-remove-btn");
                if (!button || button.disabled) return;

                const id = button.dataset.id;
                const type = button.dataset.type;
                const action = button.dataset.action;

                let quantity = 1;

                if (type === "equipment") {
                    const quantityInput = button.closest('.equipment-quantity-selector').querySelector('.quantity-input');
                    quantity = parseInt(quantityInput.value) || 1;
                }

                try {
                    if (action === "add") {
                        await addToForm(id, type, quantity);
                    } else if (action === "remove") {
                        await removeFromForm(id, type);
                    }
                    await updateAllUI();
                } catch (error) {
                    console.error("Error handling form action:", error);
                }
            });

            // Quantity input validation for equipment
            catalogItemsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('quantity-input')) {
                    const button = e.target.closest('.equipment-quantity-selector').querySelector('.add-remove-btn');
                    const id = button.dataset.id;
                    const quantity = parseInt(e.target.value) || 0;

                    const equipmentItem = allItems.find(item =>
                        item.equipment_id === parseInt(id) && CATALOG_TYPE === 'equipment'
                    );

                    if (equipmentItem) {
                        const availableQty = equipmentItem.available_quantity || 0;

                        if (quantity > availableQty) {
                            e.target.classList.add('is-invalid');
                            let errorMsg = e.target.parentNode.querySelector('.quantity-error');
                            if (!errorMsg) {
                                errorMsg = document.createElement('div');
                                errorMsg.className = 'quantity-error text-danger small mt-1';
                                e.target.parentNode.appendChild(errorMsg);
                            }
                            errorMsg.textContent = `Max: ${availableQty}`;
                        } else {
                            e.target.classList.remove('is-invalid');
                            const errorMsg = e.target.parentNode.querySelector('.quantity-error');
                            if (errorMsg) errorMsg.remove();
                        }

                        if (quantity < 1) {
                            e.target.classList.add('is-invalid');
                        }
                    }
                }
            });

            // Check availability buttons
            catalogItemsContainer.addEventListener('click', async (e) => {
                const button = e.target.closest('.check-availability-btn');
                if (!button) return;

                e.preventDefault();

                // Collect all data attributes
                const itemData = {
                    id: button.dataset.itemId,
                    name: button.dataset.itemName,
                    type: button.dataset.itemType,
                    image: button.dataset.itemImage,
                    category: button.dataset.itemCategory,
                    status: button.dataset.itemStatus,
                    statusColor: button.dataset.itemStatusColor
                };

                // Add type-specific data
                if (button.dataset.itemType === 'equipment') {
                    itemData.availableQty = button.dataset.itemAvailableQty;
                    itemData.totalQty = button.dataset.itemTotalQty;
                    itemData.dataset = button.dataset;
                } else {
                    itemData.capacity = button.dataset.itemCapacity;
                    itemData.fee = button.dataset.itemFee;
                }

                showFacilityAvailability(itemData);
            });

            // Filter dropdown - Status radio buttons
            document.querySelectorAll('#filterDropdownMenu .status-option').forEach((radio) => {
                radio.addEventListener('change', function () {
                    if (this.checked) {
                        statusFilter = this.dataset.status;
                        // Update dropdown button text to show selected status (optional)
                        document.getElementById('filterDropdown').innerHTML =
                            `<i class="bi bi-sliders2 me-1"></i> <span class="d-none d-sm-inline">Filters</span>`;
                        filterAndRenderItems();
                    }
                });
            });

            // Filter dropdown - Layout radio buttons
            document.querySelectorAll('#filterDropdownMenu .layout-option').forEach((radio) => {
                radio.addEventListener('change', function () {
                    if (this.checked) {
                        currentLayout = this.dataset.layout;
                        filterAndRenderItems();
                    }
                });
            });

            // Schedule summary update listener
            window.addEventListener('storage', function (e) {
                if (e.key === 'request_info') {
                    updateScheduleSummary();
                }
                if (e.key === 'formUpdated') {
                    updateAllUI();
                }
            });
        }

        // ============================================
        // DETAILS & AVAILABILITY MODALS
        // ============================================

        function showItemDetails(itemId) {
            const item = allItems.find(item => {
                if (CATALOG_TYPE === 'equipment') {
                    return item.equipment_id == itemId;
                } else {
                    return item.facility_id == itemId;
                }
            });

            if (!item) return;

            const isEquipment = CATALOG_TYPE === 'equipment';
            const primaryImage = getPrimaryImage(item);
            const isUnavailable = item.status.status_id === 2;
            const itemType = CATALOG_TYPE === 'equipment' ? 'equipment' :
                CATALOG_TYPE === 'rooms' ? 'facility' : 'facility';

            const isSelected = selectedItems.some(
                selectedItem => selectedItem.type === itemType &&
                    parseInt(selectedItem[`${itemType}_id`]) == itemId
            );

            document.getElementById("itemDetailModalLabel").textContent =
                isEquipment ? item.equipment_name : item.facility_name;


            document.getElementById("itemDetailContent").innerHTML = `
                                                                                                                                                        <div class="row">
                                                                                                                                                            <div class="col-md-6">
                                                                                                                                                                <img src="${primaryImage}" alt="${isEquipment ? item.equipment_name : item.facility_name}" 
                                                                                                                                                                     class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                                                                                                                                            </div>
                                                                                                                                                            <div class="col-md-6">
                                                                                                                                                                <div class="item-details">
                                                                                                                                                                    <p><strong>Status:</strong> <span class="badge" style="background-color: ${item.status.color_code}">${item.status.status_name}</span></p>
                                                                                                                                                                    <p><strong>Category:</strong> ${item.category.category_name}</p>
                                                                                                                                                                    ${!isEquipment ? `<p><strong>Subcategory:</strong> ${item.subcategory?.subcategory_name || "N/A"}</p>` : ''}
                                                                                                                                                                    ${!isEquipment ? `<p><strong>Capacity:</strong> ${item.capacity}</p>` :
                    `<p><strong>Available Quantity:</strong> ${item.available_quantity}/${item.total_quantity}</p>`}
                                                                                                                                                                    <p><strong>Rate:</strong> ₱${parseFloat(item.external_fee).toLocaleString()} (${item.rate_type})</p>
                                                                                                                                                                    <p><strong>Description:</strong></p>
                                                                                                                                                                    <p>${item.description || "No description available."}</p>
                                                                                                                                                                </div>
                                                                                                                                                                <div class="mt-3">
                                                                                                                                                                    ${isUnavailable
                    ? `<button class="btn btn-secondary" disabled>Unavailable</button>`
                    : `<button class="btn ${isSelected ? "btn-danger" : "btn-primary"} add-remove-btn" 
                                                                                                                                                                                        data-id="${itemId}" 
                                                                                                                                                                                        data-type="${CATALOG_TYPE.slice(0, -1)}" 
                                                                                                                                                                                        data-action="${isSelected ? "remove" : "add"}">
                                                                                                                                                                                    ${isSelected ? "Remove from Form" : "Add to Form"}
                                                                                                                                                                                  </button>`}
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                    `;

            const modal = new bootstrap.Modal(document.getElementById('itemDetailModal'));
            modal.show();
        }

        function showFacilityAvailability(itemData) {
            try {
                currentFacilityId = itemData.id;
                const isEquipment = itemData.type === 'equipment';

                // Update modal title based on item type
                const modalTitleElement = document.getElementById('singleFacilityAvailabilityModalLabel');
                if (modalTitleElement) {
                    modalTitleElement.innerHTML = `
                                                                                                                                                                <i class="bi ${isEquipment ? 'bi-tools' : 'bi-calendar-check'} me-2"></i>
                                                                                                                                                                <span id="facilityAvailabilityName">${isEquipment ? 'Equipment Availability' : 'Facility Availability'}</span>
                                                                                                                                                            `;
                }

                // Update facility info
                const titleElement = document.getElementById('facilityTitleText');
                const capacityElement = document.getElementById('facilityCapacity');

                if (titleElement) {
                    titleElement.textContent = itemData.name || 'N/A';
                }

                if (capacityElement) {
                    if (isEquipment) {
                        const availableQty = itemData.availableQty || itemData.dataset?.itemAvailableQty || 'N/A';
                        const totalQty = itemData.totalQty || itemData.dataset?.itemTotalQty || 'N/A';
                        capacityElement.textContent = `${availableQty}/${totalQty} available`;
                    } else {
                        capacityElement.textContent = itemData.capacity || 'N/A';
                    }
                }

                // Update image
                const imageContainer = document.getElementById('facilityAvailabilityImage');
                if (imageContainer) {
                    if (itemData.image) {
                        imageContainer.innerHTML = `
                                                                                                                                                                    <div class="facility-img-wrapper text-center">
                                                                                                                                                                        <img src="${itemData.image}" 
                                                                                                                                                                             alt="${itemData.name}" 
                                                                                                                                                                             class="img-fluid rounded"
                                                                                                                                                                             style="max-height: 150px; object-fit: ${isEquipment ? 'contain' : 'cover'};"
                                                                                                                                                                             onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                                                                                                                                                                    </div>
                                                                                                                                                                `;
                    } else {
                        imageContainer.innerHTML = `<i class="bi ${isEquipment ? 'bi-tools' : 'bi-building'} fs-1 text-muted"></i>`;
                    }
                }

                // Update category
                const categoryElement = document.getElementById('facilityCategory');
                if (categoryElement) {
                    categoryElement.textContent = itemData.category || 'N/A';
                }

                // Update status badge
                const statusBadge = document.getElementById('facilityStatusBadge');
                if (statusBadge) {
                    statusBadge.textContent = itemData.status || 'N/A';
                    statusBadge.style.backgroundColor = itemData.statusColor || '#6c757d';
                    statusBadge.style.color = '#fff';
                }

                // Update book button
                const bookNowBtn = document.getElementById('bookNowBtn');
                if (bookNowBtn) {
                    bookNowBtn.innerHTML = `
                                                                                                                                                                <i class="bi ${isEquipment ? 'bi-cart-plus' : 'bi-calendar-plus'} me-1"></i> 
                                                                                                                                                                ${isEquipment ? 'Add to Form' : 'Book This Facility'}
                                                                                                                                                            `;

                    bookNowBtn.onclick = function () {
                        if (currentFacilityId) {
                            const type = isEquipment ? 'equipment' : 'facility';
                            addToForm(currentFacilityId, type);
                            const modal = bootstrap.Modal.getInstance(document.getElementById('singleFacilityAvailabilityModal'));
                            if (modal) modal.hide();
                        }
                    };
                }

                // Initialize the legend
                renderDynamicLegend();

                // Show the modal
                const modalElement = document.getElementById('singleFacilityAvailabilityModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();

                    // Set up cleanup when modal is hidden
                    modalElement.addEventListener('hidden.bs.modal', function () {
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

                // Show loading overlay
                const loadingOverlay = document.getElementById('availabilityLoadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }

                // Initialize calendar
                setTimeout(() => {
                    if (isEquipment) {
                        initEquipmentAvailabilityCalendar(itemData.id);
                    } else {
                        initAvailabilityCalendar(itemData.id);
                    }
                }, 300);

            } catch (error) {
                console.error('Error showing facility availability:', error);
                showToast('Failed to open availability modal', 'error');
            }
        }

        async function initEquipmentAvailabilityCalendar(equipmentId) {
            return new Promise(async (resolve, reject) => {
                try {
                    // Show loading overlay
                    const loadingOverlay = document.getElementById('availabilityLoadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('hidden');
                    }

                    // Destroy previous calendar if exists
                    if (availabilityCalendarInstance && availabilityCalendarInstance.calendar) {
                        availabilityCalendarInstance.calendar.destroy();
                        availabilityCalendarInstance = null;
                    }

                    // Create new CalendarModule with equipment filter
                    availabilityCalendarInstance = new CalendarModule({
                        isAdmin: false,
                        apiEndpoint: `/api/requisition-forms/calendar-events`,
                        containerId: 'facilityAvailabilityCalendar',
                        miniCalendarContainerId: 'availabilityMiniCalendarDays',
                        monthYearId: 'availabilityCurrentMonthYear',
                        eventModalId: 'calendarEventModal'
                    });

                    // Override the hideLoadingOverlay method
                    const originalHideLoadingOverlay = availabilityCalendarInstance.hideLoadingOverlay;
                    availabilityCalendarInstance.hideLoadingOverlay = function () {
                        const availabilityOverlay = document.getElementById('availabilityLoadingOverlay');
                        if (availabilityOverlay) {
                            availabilityOverlay.classList.add('hidden');
                        }
                        if (typeof originalHideLoadingOverlay === 'function') {
                            originalHideLoadingOverlay.call(this);
                        }
                    };

                    // Override the updateLoadingState method
                    availabilityCalendarInstance.updateLoadingState = function (isLoading) {
                        const availabilityOverlay = document.getElementById('availabilityLoadingOverlay');
                        if (availabilityOverlay) {
                            if (isLoading) {
                                availabilityOverlay.classList.remove('hidden');
                            } else {
                                availabilityOverlay.classList.add('hidden');
                            }
                        }
                    };

                    // Load statuses first
                    await availabilityCalendarInstance.loadStatuses();

                    // Override the loadCalendarEvents method to filter by equipment
                    const originalLoadEvents = availabilityCalendarInstance.loadCalendarEvents;
                    availabilityCalendarInstance.loadCalendarEvents = async function () {
                        try {
                            const headers = {};
                            if (this.config.isAdmin && this.config.adminToken) {
                                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
                            }

                            const response = await fetch(
                                `${this.config.apiEndpoint}`,
                                { headers }
                            );

                            const result = await response.json();

                            if (result.success && result.data) {
                                // Filter events to only show those for this specific equipment
                                this.allEvents = result.data
                                    .filter(event => event != null)
                                    .filter(event => {
                                        const eventEquipment = event.extendedProps?.equipment || [];
                                        return eventEquipment.some(eq =>
                                            eq.equipment_id &&
                                            eq.equipment_id.toString() === equipmentId.toString()
                                        );
                                    })
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

                                // If no events found, show a message
                                if (this.allEvents.length === 0) {
                                    const calendarEl = document.getElementById('facilityAvailabilityCalendar');
                                    if (calendarEl) {
                                        calendarEl.innerHTML = `
                                                                                                                                                                                    <div class="text-center py-5">
                                                                                                                                                                                        <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                                                                                                                                                                        <p class="mt-2">No bookings found for this equipment</p>
                                                                                                                                                                                        <p class="text-muted small">This equipment has not been booked yet</p>
                                                                                                                                                                                    </div>
                                                                                                                                                                                `;
                                    }
                                }

                                // Ensure overlay is hidden after events are loaded
                                this.updateLoadingState(false);
                            } else {
                                this.allEvents = [];
                                this.applyFilters();
                                this.updateLoadingState(false);
                            }
                        } catch (error) {
                            console.error("Error loading calendar events:", error);
                            this.allEvents = [];
                            this.updateLoadingState(false);
                        }
                    };

                    // Initialize the calendar
                    await availabilityCalendarInstance.initialize();

                    // Force proper calendar rendering
                    setTimeout(() => {
                        if (availabilityCalendarInstance.calendar) {
                            availabilityCalendarInstance.calendar.updateSize();
                        }
                    }, 300);

                    resolve();
                } catch (error) {
                    console.error('Failed to initialize equipment availability calendar:', error);
                    const loadingOverlay = document.getElementById('availabilityLoadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.add('hidden');
                    }

                    // Show error message in calendar container
                    const calendarEl = document.getElementById('facilityAvailabilityCalendar');
                    if (calendarEl) {
                        calendarEl.innerHTML = `
                                                                                                                                                                    <div class="text-center py-5">
                                                                                                                                                                        <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                                                                                                                                                                        <p class="mt-2">Failed to load availability</p>
                                                                                                                                                                        <p class="text-muted small">Please try again later</p>
                                                                                                                                                                    </div>
                                                                                                                                                                `;
                    }

                    reject(error);
                }
            });
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

        async function initAvailabilityCalendar(facilityId) {
            return new Promise(async (resolve, reject) => {
                try {
                    // Show loading overlay
                    const loadingOverlay = document.getElementById('availabilityLoadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('hidden');
                    }

                    // Destroy previous calendar if exists
                    if (availabilityCalendarInstance && availabilityCalendarInstance.calendar) {
                        availabilityCalendarInstance.calendar.destroy();
                        availabilityCalendarInstance = null;
                    }

                    // Create new CalendarModule with facility filter
                    availabilityCalendarInstance = new CalendarModule({
                        isAdmin: false,
                        apiEndpoint: `/api/requisition-forms/calendar-events`,
                        containerId: 'facilityAvailabilityCalendar',
                        miniCalendarContainerId: 'availabilityMiniCalendarDays',
                        monthYearId: 'availabilityCurrentMonthYear',
                        eventModalId: 'calendarEventModal'
                    });

                    // Override the hideLoadingOverlay method to target the correct overlay
                    const originalHideLoadingOverlay = availabilityCalendarInstance.hideLoadingOverlay;
                    availabilityCalendarInstance.hideLoadingOverlay = function () {
                        // Hide the availability modal's loading overlay
                        const availabilityOverlay = document.getElementById('availabilityLoadingOverlay');
                        if (availabilityOverlay) {
                            availabilityOverlay.classList.add('hidden');
                        }
                        // Also call the original method if needed
                        if (typeof originalHideLoadingOverlay === 'function') {
                            originalHideLoadingOverlay.call(this);
                        }
                    };

                    // Override the updateLoadingState method
                    availabilityCalendarInstance.updateLoadingState = function (isLoading) {
                        const availabilityOverlay = document.getElementById('availabilityLoadingOverlay');
                        if (availabilityOverlay) {
                            if (isLoading) {
                                availabilityOverlay.classList.remove('hidden');
                            } else {
                                availabilityOverlay.classList.add('hidden');
                            }
                        }
                    };

                    // Load statuses first
                    await availabilityCalendarInstance.loadStatuses();

                    // Override the loadCalendarEvents method to filter by facility
                    const originalLoadEvents = availabilityCalendarInstance.loadCalendarEvents;
                    availabilityCalendarInstance.loadCalendarEvents = async function () {
                        try {
                            const headers = {};
                            if (this.config.isAdmin && this.config.adminToken) {
                                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
                            }

                            const response = await fetch(
                                `${this.config.apiEndpoint}`,
                                { headers }
                            );

                            const result = await response.json();

                            if (result.success && result.data) {
                                // Filter events to only show those for this specific facility
                                this.allEvents = result.data
                                    .filter(event => event != null)
                                    .filter(event => {
                                        const eventFacilities = event.extendedProps?.facilities || [];
                                        return eventFacilities.some(f =>
                                            f.facility_id &&
                                            f.facility_id.toString() === facilityId.toString()
                                        );
                                    })
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

                                // Ensure overlay is hidden after events are loaded
                                this.updateLoadingState(false);
                            } else {
                                this.allEvents = [];
                                this.applyFilters();
                                this.updateLoadingState(false);
                            }
                        } catch (error) {
                            console.error("Error loading calendar events:", error);
                            this.allEvents = [];
                            this.updateLoadingState(false);
                        }
                    };

                    // Initialize the calendar
                    await availabilityCalendarInstance.initialize();

                    // Force proper calendar rendering
                    setTimeout(() => {
                        if (availabilityCalendarInstance.calendar) {
                            availabilityCalendarInstance.calendar.updateSize();
                        }
                    }, 300);

                    resolve();
                } catch (error) {
                    console.error('Failed to initialize availability calendar:', error);
                    const loadingOverlay = document.getElementById('availabilityLoadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.add('hidden');
                    }
                    reject(error);
                }
            });
        }

        async function renderDynamicLegend() {
            const legendContainer = document.getElementById('dynamicLegend');
            if (!legendContainer) return;

            try {
                // Load form statuses
                const response = await fetch('/api/form-statuses');
                if (response.ok) {
                    const statuses = await response.json();
                    if (Array.isArray(statuses)) {
                        const activeStatuses = statuses.filter(status => status.status_id <= 6);

                        legendContainer.innerHTML = '';
                        activeStatuses.forEach(status => {
                            const legendItem = document.createElement('div');
                            legendItem.className = 'd-flex align-items-center';
                            legendItem.innerHTML = `
                                                                                                                                                                        <div class="color-box me-2" style="background-color: ${status.color_code};"></div>
                                                                                                                                                                        <small>${status.status_name}</small>
                                                                                                                                                                    `;
                            legendContainer.appendChild(legendItem);
                        });
                    }
                }
            } catch (error) {
                console.error('Failed to load form statuses:', error);
                legendContainer.innerHTML = '<div class="text-muted small">Failed to load status colors</div>';
            }
        }

        // Setup mini calendar navigation for availability modal
        document.addEventListener('click', function (e) {
            if (e.target.closest('.mini-calendar .prev-month')) {
                e.preventDefault();
                if (availabilityCalendarInstance) {
                    availabilityCalendarInstance.navigateMonth(-1);
                }
            }

            if (e.target.closest('.mini-calendar .next-month')) {
                e.preventDefault();
                if (availabilityCalendarInstance) {
                    availabilityCalendarInstance.navigateMonth(1);
                }
            }
        });

        // ============================================
        // INITIALIZATION
        // ============================================

        // ============================================
        // INITIALIZATION
        // ============================================

        async function init() {
            // Set initial active catalog type
            document.querySelectorAll('.catalog-type-tab').forEach(tab => {
                if (tab.dataset.type === CATALOG_TYPE) {
                    tab.classList.add('active');
                }
            });

            // Set initial status filter radio
            const statusRadio = document.querySelector(`#filterDropdownMenu .status-option[data-status="${statusFilter}"]`);
            if (statusRadio) {
                statusRadio.checked = true;
            }

            // Set initial layout radio
            const layoutRadio = document.querySelector(`#filterDropdownMenu .layout-option[data-layout="${currentLayout}"]`);
            if (layoutRadio) {
                layoutRadio.checked = true;
            }

            setupEventListeners();
            await loadCatalogData();

            // Update schedule summary on load
            updateScheduleSummary();

            // Load form statuses for calendar
            try {
                const statusesResponse = await fetchData('/api/form-statuses');
                if (statusesResponse && Array.isArray(statusesResponse)) {
                    const activeStatuses = statusesResponse.filter(status => status.status_id <= 6);
                    activeStatuses.forEach(status => {
                        formStatuses[status.status_name] = status.color_code;
                    });
                }
            } catch (error) {
                console.error('Failed to load form statuses:', error);
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener("DOMContentLoaded", init);

        // Handle storage events for cross-page sync
        window.addEventListener('storage', function (e) {
            if (e.key === 'formUpdated') {
                updateAllUI();
            }
            if (e.key === 'request_info') {
                updateScheduleSummary();
            }
        });
    </script>
@endsection