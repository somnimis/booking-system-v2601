{{-- calendar.blade.php --}}
@extends('layouts.admin')
@section('title', 'Ongoing Events')
@section('content')
  <link rel="stylesheet" href="{{ asset('css/public/public-calendar.css') }}">
  <style>
    .reservation-card:hover,
    .calendar-event-card:hover {
      background-color: #f0f0f0 !important;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .refresh-btn {
      padding: 0.25rem 0.5rem;
      /* matches btn-sm */
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

    .fc {
      height: 100% !important;
    }

    .fc-view-harness {
      height: 100% !important;
    }

    .fc .fc-toolbar.fc-header-toolbar {
      margin-bottom: 1em;
    }

    /* Ensure calendar container has proper sizing */
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

    #deleteCalendarEventModal .btn-outline-secondary:hover {
      background-color: #f8f9fa;
    }

    /* Mobile optimization without scaling */
    @media (max-width: 768px) {
      body {
        font-size: 0.9rem;
      }

      h1,
      h2,
      h3,
      h4,
      h5,
      h6 {
        font-size: 0.9em;
      }
      .btn,
      .form-control,
      .form-select {
        padding: 0.35rem 0.65rem;
        font-size: 0.85rem;
      }

      .gap-2 {
        gap: 0.5rem !important;
      }

      .mb-3 {
        margin-bottom: 0.75rem !important;
      }

      .p-3 {
        padding: 1rem !important;
      }

      /* Reduce filter widths */
      .filter-select {
        min-width: 120px;
      }

      .input-group.input-group-sm {
        width: 150px;
      }
    }

    @media (max-width: 576px) {
      body {
        font-size: 0.85rem;
      }

      .btn,
      .form-control,
      .form-select {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
      }

      .filter-select {
        min-width: 100px;
      }

      .input-group.input-group-sm {
        width: 130px;
      }
    }

    /* Better wrapping for filters on smaller screens */
    @media (max-width: 992px) {
      .filter-select {
        min-width: 140px;
      }

      .input-group.input-group-sm {
        width: 180px;
      }
    }

    /* Ensure dropdown menu has proper width on mobile */
    .dropdown-menu {
      min-width: 250px;
    }

    /* Change tab text colors from neon blue to black */
    #adminDashboardTabs .nav-link {
      color: #000 !important;
    }

    #adminDashboardTabs .nav-link.active {
      color: #fff !important;
      background-color: #4272b1ff;
      border-color: #4272b1ff;
    }

    #adminDashboardTabs .nav-link:hover:not(.active) {
      color: #333 !important;
    }

    /* Change icons to black when not active, white when active */
    #adminDashboardTabs .nav-link:not(.active) i {
      color: #000 !important;
    }

    #adminDashboardTabs .nav-link.active i {
      color: #fff !important;
    }

    /* Style counters with #135ba3 color */
    #adminDashboardTabs .badge {
      background-color: #135ba3 !important;
      color: white !important;
    }

    #adminDashboardTabs #pendingRequestsCount {
      background-color: var(--bs-danger) !important;
      color: #ffffff !important;
    }


    /* Hide counter for "All Reservations" */
    #allReservationsCount {
      display: none !important;
    }

    /* Change refresh button icon color */
    #refreshAllReservationsBtn i,
    #refreshPendingRequestsBtn i {
      color: #6c757d !important;
    }

    #refreshAllReservationsBtn:hover i,
    #refreshPendingRequestsBtn:hover i {
      color: #135ba3 !important;
    }

    /* Spinner animation for refresh buttons */
    .animate-spin {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from {
        transform: rotate(0deg);
      }

      to {
        transform: rotate(360deg);
      }
    }

    /* Ensure loading spinner is properly positioned */
    #allReservationsLoadingSpinner,
    #pendingRequestsLoadingSpinner {
      position: relative;
      min-height: 200px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: opacity 0.3s ease;
    }

    /* Make sure spinner is visible when active */
    #allReservationsLoadingSpinner.active,
    #pendingRequestsLoadingSpinner.active {
      display: flex !important;
    }

    .filter-select {
      min-width: 150px;
      width: auto;
    }

    .input-group.input-group-sm {
      width: 200px;
    }

    /* Ensure the filter bar aligns properly */
    .d-flex.align-items-center.gap-2 {
      flex-wrap: wrap;
    }

    #initialStatusSelect option {
      padding: 8px;
      margin: 2px 0;
      border-radius: 4px;
    }

    /* Optional: Add a color indicator before each option */
    #initialStatusSelect option::before {
      content: "■";
      margin-right: 8px;
      font-size: 12px;
    }


    .fc .fc-toolbar-chunk .fc-button:focus,
    .fc .fc-toolbar-chunk .fc-button:active {
      outline: none !important;
      box-shadow: none !important;
    }

    /* FullCalendar Toolbar Buttons */
    .fc .fc-toolbar-chunk .fc-button {
      background-color: #ffffff !important;
      /* White background */
      color: #6c757d !important;
      /* Gray text */
      border: none !important;
      /* No border */
      font-weight: 500;
      border-radius: 6px !important;
    }

    /* Hover state */
    .fc .fc-toolbar-chunk .fc-button:hover {
      background-color: #f8f9fa !important;
      /* Slightly off-white hover */
      color: #495057 !important;
      /* Darker gray text on hover */
      border: none !important;
    }

    /* Active/Pressed state */
    .fc .fc-toolbar-chunk .fc-button.fc-button-active {
      background-color: #4272b1ff !important;
      color: #ffffffff !important;
      border: none !important;
    }

    .fc .fc-today-button {
      text-transform: capitalize !important;
    }

    /* Base checkbox style */
    .form-check-input {
      width: 1.1em;
      height: 1.1em;
      cursor: pointer;
    }


    .scheduled-checkbox:checked {
      background-color: #1e7941ff;
      border-color: #1e7941ff;
    }

    .ongoing-checkbox:focus {
      box-shadow: 0 0 0 0.2rem #1461314d
    }

    .ongoing-checkbox:checked {
      background-color: #ac7a0fff;
      border-color: #ac7a0fff;
    }

    .ongoing-checkbox:focus {
      box-shadow: 0 0 0 0.2rem #75530941;
    }

    /* Late = red */
    .late-checkbox:checked {
      background-color: #8f2a2aff;
      border-color: #8f2a2aff;
    }

    .late-checkbox:focus {
      box-shadow: 0 0 0 0.2rem #701a1a59;
    }

    /* Remove or update the skeleton filter height limit */
    .col-lg-3 .card:last-child .skeleton-container {
      max-height: none !important;
      /* Remove the height restriction */
      min-height: 200px;
      /* Ensure minimum height */
    }

    /* Make sure skeleton container is visible when loading */
    .loading .skeleton-container {
      display: block !important;
      /* Force display */
      visibility: visible !important;
      opacity: 1 !important;
    }

    /* Make sure calendar content is hidden when loading */
    .loading .calendar-content {
      display: none !important;
      /* Force hide */
      visibility: hidden !important;
      opacity: 0 !important;
    }

    /* Ensure skeleton containers fill available space */
    .col-lg-3 .card:last-child .skeleton-container,
    .col-lg-3 .card:last-child .calendar-content {
      width: 100%;
      height: 100%;
    }


    /* Ensure skeleton days don't take too much space */
    #miniCalendarDaysSkeleton {
      max-height: 120px;
      overflow: hidden;
    }

    /* Make skeleton days grid more compact */
    #miniCalendarDaysSkeleton .skeleton-day {
      height: 20px !important;
      /* Reduced from 32px */
      margin: 1px;
    }

    /* Make the row stretch full height */
    .row.g-3 {
      align-items: stretch;
    }

    /* Make both cards in left column fill height */
    .col-lg-3 {
      display: flex;
      flex-direction: column;
    }

    .col-lg-3 .card {
      flex-grow: 1;
    }

    /* Mini calendar takes minimal height, event filter fills remaining */
    .col-lg-3 .card:first-child {
      flex: 0 0 auto;
    }

    .col-lg-3 .card:last-child {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
    }

    /* Ensure events filter card body stretches */
    .col-lg-3 .card:last-child .card-body {
      flex-grow: 1;
    }

    /* Ensure calendar matches height */
    #calendar {
      height: 450px !important;
    }

    .mini-calendar .calendar-days {
      display: flex;
      flex-wrap: wrap;
    }

    .mini-calendar .calendar-day {
      min-height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      border-radius: 4px;
    }

    .mini-calendar .calendar-day:hover {
      background-color: #d3dbe4ff;
      cursor: pointer;
    }

    .mini-calendar .day-header {
      min-height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .mini-calendar .calendar-day.has-events {
      font-weight: bold;
      color: #004183;
      position: relative;
    }

    .mini-calendar .calendar-day.has-events::after {
      content: '';
      position: absolute;
      bottom: 2px;
      left: 50%;
      transform: translateX(-50%);
      width: 4px;
      height: 4px;
      background-color: #004183;
      border-radius: 50%;
    }

    .mini-calendar .calendar-day.today {
      background-color: #366eaaff;
      color: white;
    }

    .mini-calendar .calendar-day.today.has-events::after {
      background-color: white;
    }

    /* Mini Calendar Grid */
    #miniCalendarDays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      /* 7 days per week */
      gap: 4px;
    }

    /* Each day cell */
    #miniCalendarDays .day {
      aspect-ratio: 1 / 1;
      /* Make them perfect squares */
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: monospace;
      /* Equal number width */
      font-size: 0.9rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    /* Optional hover/active styling */
    #miniCalendarDays .day:hover {
      background-color: #f0f0f0;
    }

    /* Example for active day */
    #miniCalendarDays .day.active {
      background-color: #007bff;
      color: white;
    }

    /* Loading Skeleton Styles */
    .skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
      border-radius: 4px;
    }

    .skeleton-text {
      height: 12px;
      margin-bottom: 8px;
    }

    .skeleton-title {
      height: 20px;
      margin-bottom: 16px;
    }

    .skeleton-button {
      height: 32px;
      width: 32px;
      border-radius: 4px;
    }

    .skeleton-day {
      height: 32px;
      border-radius: 4px;
    }

    .skeleton-checkbox {
      height: 16px;
      width: 16px;
      border-radius: 3px;
      margin-right: 8px;
    }

    .skeleton-badge {
      height: 12px;
      width: 12px;
      border-radius: 2px;
      margin-right: 8px;
    }

    @keyframes loading {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    .skeleton-container {
      display: none;
    }

    .loading .skeleton-container {
      display: block;
    }

    .loading .calendar-content {
      display: none;
    }

    /* Event Modal Edit Mode Styles */
    #modalCalendarTitle:not([readonly]),
    #modalCalendarDescription:not([readonly]) {
      color: #000 !important;
      background-color: #fff !important;
      border-color: #4272b1ff !important;
      box-shadow: 0 0 0 0.2rem rgba(66, 114, 177, 0.25) !important;
    }

    /* Make sure the readonly state is properly styled */
    #modalCalendarTitle[readonly],
    #modalCalendarDescription[readonly] {
      color: #6c757d !important;
      background-color: #f8f9fa !important;
      cursor: default;
    }

    /* Focus state for better UX */
    #modalCalendarTitle:focus,
    #modalCalendarDescription:focus {
      color: #000 !important;
      border-color: #4272b1ff !important;
      box-shadow: 0 0 0 0.2rem rgba(66, 114, 177, 0.25) !important;
      outline: 0;
    }


    .facility-item .form-check-label {
      font-size: 0.85rem;
      cursor: pointer;
    }

    .facility-item .form-check-input:checked+.form-check-label {
      font-weight: bold;
      color: #004183;
    }

    #facilityFilterList::-webkit-scrollbar {
      width: 6px;
    }

    #facilityFilterList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    #facilityFilterList::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }

    #facilityFilterList::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Custom Styles for Modern Look */
    .modal-xl {
      max-width: 1000px;
    }

    .modal-content {
      border-radius: 12px;
      overflow: hidden;
    }

    .bg-gradient-primary {
      background: linear-gradient(135deg, #224d9c 0%, #3e6fca 100%);
    }

    .card-border {
      border: 1px solid #e0e0e0;
      border-radius: 10px;
      overflow: hidden;
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

    .form-check-input:checked {
      background-color: #224d9c;
      border-color: #224d9c;
    }

    .input-group-text {
      background-color: #f8f9fa;
    }

    .alert-info {
      background-color: #e7f1ff;
      border-color: #d0e2ff;
      color: #084298;
    }

    .font-monospace {
      font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
      font-size: 0.9rem;
      letter-spacing: 1px;
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

    /* Dynamic status checkbox colors */
    .event-filter-checkbox:checked {
      border-color: transparent !important;
    }

    /* Color dots for status labels */
    .status-color-dot {
      transition: transform 0.2s ease;
    }

    .status-color-dot:hover {
      transform: scale(1.2);
    }

    /* Loading spinner for admin reservations */
    .loading-spinner {
      display: none;
      text-align: center;
      padding: 2rem 0;
    }

    .loading-spinner.active {
      display: block !important;
    }

    .loading-spinner.d-flex {
      display: flex !important;
    }

    [v-cloak] {
      display: none;
    }

    /* Bootstrap's default spinner styles - exactly as Bootstrap defines them */
    .spinner-border {
      display: inline-block;
      width: 2rem;
      height: 2rem;
      vertical-align: -0.125em;
      border: 0.25em solid currentColor;
      border-right-color: transparent;
      border-radius: 50%;
      animation: spinner-border .75s linear infinite;
    }

    /* Bootstrap's small spinner variant */
    .spinner-border-sm {
      width: 1rem;
      height: 1rem;
      border-width: 0.2em;
    }

    /* Bootstrap's spinner animation */
    @keyframes spinner-border {
      to {
        transform: rotate(360deg);
      }
    }

    /* Ensure button spinners are properly aligned */
    .btn .spinner-border.spinner-border-sm {
      vertical-align: middle;
      margin-right: 0.25rem;
    }

    /* Make sure skeleton covers during initial load */
    #adminReservationsList .loading-skeleton {
      display: block;
    }

    #adminReservationsList.loading .loading-skeleton {
      display: block;
    }

    #adminReservationsList.loading .calendar-content {
      display: none !important;
    }

    #adminReservationsList.loading .loading-spinner {
      display: none !important;
    }

    /* Character counter styling */
    .text-muted .text-danger {
      color: #dc3545 !important;
    }

    .text-muted .text-warning {
      color: #ffc107 !important;
    }

    .fw-medium {
      font-weight: 500;
    }

    /* Real-time counter animation */
    @keyframes pulse {
      0% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.05);
      }

      100% {
        transform: scale(1);
      }
    }

    .text-danger.fw-bold {
      animation: pulse 0.5s ease-in-out;
    }

    /* Pagination Styles */
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

    .page-link:hover {
      background-color: #f8f9fa;
      color: #004183;
    }

    .page-link:focus {
      box-shadow: 0 0 0 0.2rem rgba(66, 114, 177, 0.25);
    }

    .page-item.disabled .page-link {
      color: #6c757d;
      background-color: #fff;
      border-color: #dee2e6;
    }

    /* Admin reservations card hover effect - Enhanced */
    #adminReservationsList .card {
      cursor: pointer;
      border: 1px solid transparent;
      border-radius: 0.75rem;
      /* Add this to preserve rounded corners */
    }

    #adminReservationsList .card:hover {
      background-color: #f8f9fa;
      border-color: #dee2e6;
      border-radius: 0.75rem;
      /* Also add here to ensure it stays rounded on hover */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
    }

    #adminReservationsList .card:hover .card-body {
      background-color: #f8f9fa;
      border-radius: 0.75rem;
      /* Add this if card-body also needs rounded corners */
    }

    /* Optional: Add a subtle pointer cursor to the entire card */
    #adminReservationsList .card-body {
      cursor: pointer;
      border-radius: 0.75rem;
      /* Preserve card-body's rounded corners */
    }

    /* Facility Filter List */
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

    /* Ensure the accordion takes full available height */
    .accordion.flex-grow-1 {
      flex: 1;
      min-height: 0;
    }

    /* Remove blue background from accordion buttons */
    #eventFiltersAccordion .accordion-button:not(.collapsed) {
      background-color: #00428318;
      ;
      color: inherit;
      box-shadow: none;
    }

    #eventFiltersAccordion .accordion-button:focus {
      box-shadow: none;
      border-color: rgba(0, 0, 0, .125);
    }

    /* Optional: Add a subtle hover effect instead */
    #eventFiltersAccordion .accordion-button:hover {
      background-color: #f8f9fa;
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
              <!-- Skeleton for Mini Calendar -->
              <div class="skeleton-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="skeleton skeleton-title flex-grow-1 mx-3" style="height: 24px;"></div>
                </div>
                <div class="calendar-header d-flex mb-2">
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                  <div class="skeleton skeleton-text flex-fill mx-1" style="height: 16px;"></div>
                </div>
                <div class="calendar-days" id="miniCalendarDaysSkeleton">
                  <!-- Skeleton days will be populated by JavaScript -->
                </div>
              </div>

              <!-- Actual Mini Calendar Content -->
              <div class="calendar-content">
                <div class="mini-calendar">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn btn-sm btn-secondary prev-month" type="button">
                      <i class="bi bi-chevron-left"></i>
                    </button>
                    <h6 class="mb-0 month-year" id="currentMonthYear">October 2024</h6>
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
                  <div class="calendar-days" id="miniCalendarDays">
                    <!-- Days populated by JavaScript -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Events Filter Card -->
          <div class="card h-200">
            <div class="card-body d-flex flex-column h-100">
              <!-- Skeleton for Events Filter -->
              <div class="skeleton-container flex-grow-1">
                <div class="skeleton skeleton-title mb-3" style="height: 20px; width: 60%;"></div>
                <div class="d-flex align-items-center mb-2">
                  <div class="skeleton skeleton-checkbox"></div>
                  <div class="skeleton skeleton-text flex-grow-1" style="height: 14px;"></div>
                </div>
                <div class="d-flex align-items-center mb-2">
                  <div class="skeleton skeleton-checkbox"></div>
                  <div class="skeleton skeleton-text flex-grow-1" style="height: 14px;"></div>
                </div>
                <div class="skeleton skeleton-title mt-4" style="height: 20px; width: 60%;"></div>
                <div class="filter-list mt-2">
                  <div class="d-flex align-items-center mb-2">
                    <div class="skeleton skeleton-checkbox"></div>
                    <div class="skeleton skeleton-text flex-grow-1" style="height: 14px;"></div>
                  </div>
                  <div class="d-flex align-items-center mb-2">
                    <div class="skeleton skeleton-checkbox"></div>
                    <div class="skeleton skeleton-text flex-grow-1" style="height: 14px;"></div>
                  </div>
                </div>
              </div>

              <!-- Actual Events Filter Content -->
              <div class="calendar-content d-flex flex-column h-100">
                <h6 class="fw-bold mb-3">Event Filters</h6>

                <!-- Accordion Container - Bootstrap handles everything -->
                <div class="accordion flex-grow-1 d-flex flex-column" id="eventFiltersAccordion">
                  <!-- Filter by Status Section -->
                  <div class="accordion-item border-0 border-bottom">
                    <h2 class="accordion-header">
                      <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse"
                        data-bs-target="#filterStatusCollapse" aria-expanded="true" aria-controls="filterStatusCollapse">
                        <span class="fw-semibold">Filter by Status</span>
                      </button>
                    </h2>
                    <div id="filterStatusCollapse" class="accordion-collapse collapse show"
                      data-bs-parent="#eventFiltersAccordion">
                      <div class="accordion-body p-3 pt-2">
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Pencil Booked"
                            id="filterPencilBooked" checked>
                          <label class="form-check-label" for="filterPencilBooked">Pencil Booked</label>
                          <!-- Fix this! -->
                        </div>
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Pending Approval"
                            id="filterPending" checked>
                          <label class="form-check-label" for="filterPending">Pending Approval</label>
                        </div>
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Awaiting Payment"
                            id="filterAwaitingPayment" checked>
                          <label class="form-check-label" for="filterAwaitingPayment">Awaiting Payment</label>
                        </div>
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Scheduled"
                            id="filterScheduled" checked>
                          <label class="form-check-label" for="filterScheduled">Scheduled</label>
                        </div>
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Ongoing"
                            id="filterOngoing" checked>
                          <label class="form-check-label" for="filterOngoing">Ongoing</label>
                        </div>
                        <div class="form-check mb-2">
                          <input class="form-check-input event-filter-checkbox" type="checkbox" value="Overdue"
                            id="filterOverdue" checked>
                          <label class="form-check-label" for="filterOverdue">Overdue</label>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Filter by Facility Section -->
                  <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                      <button class="accordion-button py-2 px-3 collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#filterFacilityCollapse" aria-expanded="false"
                        aria-controls="filterFacilityCollapse">
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
              <!-- Calendar Skeleton -->
              <div class="skeleton-container flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="d-flex gap-2">
                    <div class="skeleton skeleton-button" style="width: 200px;"></div>
                    <div class="skeleton skeleton-button" style="width: 80px;"></div>
                    <div class="skeleton skeleton-button" style="width: 80px;"></div>
                    <div class="skeleton skeleton-button" style="width: 100px;"></div>
                    <div class="skeleton skeleton-button" style="width: 100px;"></div>
                    <div class="skeleton skeleton-button" style="width: 100px;"></div>
                  </div>
                </div>
                <div class="skeleton flex-grow-1" style="border-radius: 8px;"></div>
              </div>
              <!-- Actual Calendar Content -->
              <div class="calendar-content flex-grow-1 d-flex flex-column">
                <div id="calendar" class="flex-grow-1"></div>
              </div>
            </div>
          </div>
        </div>
        <!-- Admin Reservations Card -->
        <div class="col-12 mt-3">
          <div class="card">
            <div class="card-body">
              <!-- Skeleton Container -->
              <div class="skeleton-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="skeleton skeleton-title" style="height: 20px; width: 40%;"></div>
                  <div class="skeleton skeleton-button" style="height: 28px; width: 100px;"></div>
                </div>

                <!-- Random length skeleton lines -->
                <div class="mb-3">
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 90%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 70%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 85%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 60%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 75%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 80%;"></div>
                  <div class="skeleton skeleton-text mb-2" style="height: 16px; width: 65%;"></div>
                </div>
              </div>

              <!-- Actual Content -->
              <div class="calendar-content">
                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs mb-4" id="adminDashboardTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-reservations-tab" data-bs-toggle="tab"
                      data-bs-target="#all-reservations" type="button" role="tab" aria-controls="all-reservations"
                      aria-selected="true">
                      <i class="bi bi-calendar-week me-1"></i>
                      Scheduled
                      <span class="badge bg-secondary ms-1" id="allReservationsCount">0</span>
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pending-requests-tab" data-bs-toggle="tab"
                      data-bs-target="#pending-requests" type="button" role="tab" aria-controls="pending-requests"
                      aria-selected="false">
                      <i class="bi bi-clock-history me-1"></i>
                      Pending
                      <span class="badge bg-danger ms-1" id="pendingRequestsCount">0</span>
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendar-events-tab" data-bs-toggle="tab"
                      data-bs-target="#calendar-events" type="button" role="tab" aria-controls="calendar-events"
                      aria-selected="false">
                      <i class="bi bi-calendar-event me-1"></i>
                      Events
                    </button>
                  </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="adminDashboardContent">
                  <!-- Tab 1: Ongoing Events -->
                  <div class="tab-pane fade show active" id="all-reservations" role="tabpanel"
                    aria-labelledby="all-reservations-tab">
                    <div id="ongoingEventsApp">
                      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2 me-3 mb-2">
                          <select class="form-select form-select-sm filter-select" v-model="filters.status">
                            <option value="">All Statuses</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="scheduled">Scheduled</option>
                          </select>
                          <select class="form-select form-select-sm filter-select" v-model="filters.sort">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                          </select>
                          <div class="input-group input-group-sm" style="width: 200px;">
                            <input type="search" class="form-control" v-model="filters.search"
                              placeholder="Find an ongoing event..." @keyup.enter="loadOngoingEvents(1)">
                            <button class="btn btn-outline-secondary" type="button" @click="loadOngoingEvents(1)">
                              <i class="bi bi-search"></i>
                            </button>
                          </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-2">
                          <div class="d-none d-lg-flex align-items-center gap-2">
                            <select class="form-select form-select-sm w-auto" v-model="perPage"
                              @change="loadOngoingEvents(1)">
                              <option value="5">5 per page</option>
                              <option value="10">10 per page</option>
                              <option value="20">20 per page</option>
                              <option value="50">50 per page</option>
                            </select>
                            <button type="button"
                              class="btn btn-link btn-sm text-secondary text-decoration-none me-2 refresh-btn"
                              @click="refreshOngoingEvents" :disabled="loading">
                              <i class="bi"
                                :class="loading ? 'bi-arrow-clockwise animate-spin' : 'bi-arrow-clockwise'"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                              data-bs-target="#addReservationModal">
                              <i class="bi bi-plus-circle me-1"></i> Add new
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
                                    @change="loadOngoingEvents(1)">
                                    <option value="5">5 per page</option>
                                    <option value="10">10 per page</option>
                                    <option value="20">20 per page</option>
                                    <option value="50">50 per page</option>
                                  </select>
                                </div>
                              </li>
                              <li>
                                <hr class="dropdown-divider">
                              </li>
                              <li>
                                <button type="button" class="dropdown-item" @click="refreshOngoingEvents">
                                  <i class="bi bi-arrow-clockwise me-2"></i> Refresh list
                                </button>
                              </li>
                              <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                  data-bs-target="#addReservationModal">
                                  <i class="bi bi-plus-circle me-2"></i> Add new reservation
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
                        <p class="text-muted mt-2 small">Loading ongoing events...</p>
                      </div>

                      <!-- Events List -->
                      <div id="ongoingEventsList" v-else>
                        <div v-if="ongoingEvents.length === 0" class="text-center py-5">
                          <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                          <p class="text-muted mb-0">No ongoing events found.</p>
                        </div>

                        <div v-for="event in ongoingEvents" :key="event.request_id"
                          class="card border mb-2 reservation-card" @click="goToEvent(event.request_id)">
                          <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                              <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                  <h6 class="fw-bold mb-0">
                                    @{{ event . requester . name }}
                                    <span class="text-muted"> • @{{ event . purpose || 'No purpose' }}</span>
                                    <span class="text-muted" v-if="event.requester.organization"> •
                                      @{{ event . requester . organization }}</span>
                                  </h6>
                                  <span class="badge" :style="{ backgroundColor: event.status.color }">
                                    @{{ event . status . name }}
                                  </span>
                                </div>

                                <p class="text-muted small mb-2">
                                  @{{ event . schedule . display }} • Duration: @{{ formatDuration(event) }}
                                </p>

                                <p class="mb-0 small" v-if="event.requested_items && event.requested_items.length > 0">
                                  <span v-for="(item, index) in displayItems(event.requested_items)" :key="index">
                                    @{{ item . name }}<span v-if="item.quantity > 1"> (×@{{ item . quantity }})</span>
                                  </span>
                                  <span class="text-muted" v-if="event.requested_items.length > 2"> •
                                    @{{ event . requested_items . length - 2 }} more...</span>
                                </p>
                                <p class="mb-0 small" v-else-if="event.participants">
                                  Participants: @{{ event . participants }}
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

                  <!-- Tab 2: Pending Requests -->
                  <div class="tab-pane fade" id="pending-requests" role="tabpanel" aria-labelledby="pending-requests-tab">
                    <div id="pendingRequestsApp">
                      <div class="d-flex justify-content-end align-items-center mb-3">
                        <div>
                          <button type="button"
                            class="btn btn-link btn-sm text-secondary text-decoration-none me-2 refresh-btn"
                            @click="refreshPendingRequests" :disabled="loading">
                            <i class="bi" :class="loading ? 'bi-arrow-clockwise animate-spin' : 'bi-arrow-clockwise'"></i>
                            Refresh
                          </button>
                        </div>
                      </div>

                      <!-- Loading Spinner -->
                      <div class="loading-spinner d-flex flex-column justify-content-center align-items-center py-5"
                        v-if="loading" style="min-height: 200px;">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 small">Loading pending requests...</p>
                      </div>

                      <!-- Pending Requests List -->
                      <div id="pendingRequestsList" v-else>
                        <div v-if="pendingRequests.length === 0" class="text-center py-5">
                          <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                          <p class="text-muted mb-0">No pending requests found.</p>
                          <p class="text-muted small">Statuses: Pending Approval or Awaiting Payment</p>
                        </div>

                        <div v-for="request in pendingRequests" :key="request.request_id"
                          class="card border mb-2 reservation-card" @click="goToEvent(request.request_id)">
                          <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                              <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                  <h6 class="fw-bold mb-0">
                                    @{{ request . requester . name }}
                                    <span class="text-muted" v-if="request.requester.organization"> •
                                      @{{ request . requester . organization }}</span>
                                  </h6>
                                  <span class="badge" :style="{ backgroundColor: request.status.color }">
                                    @{{ request . status . name }}
                                  </span>
                                </div>

                                <p class="text-muted small mb-2">
                                  @{{ request.schedule.display }} • Duration: @{{ formatDuration(request) }}
                                </p>

                                <p class="mb-0 small">
                                  <span v-for="(item, index) in displayItems(request.requested_items)" :key="index">
                                    <span v-if="index > 0" class="text-muted"> • </span>
                                    @{{ item . name }}<span v-if="item.quantity > 1"> (×@{{ item . quantity }})</span>
                                  </span>
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
                            Total: @{{ totalItems }} requests
                          </div>
                          <nav aria-label="Requests pagination">
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

                  <!-- Tab 3: Calendar Events -->
                  <div class="tab-pane fade" id="calendar-events" role="tabpanel" aria-labelledby="calendar-events-tab">
                    <div id="calendarEventsApp">
                      <!-- Events Header with Controls -->
                      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
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
                            <button class="btn btn-outline-secondary" type="button" @click="loadCalendarEvents(1)">
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
                                :class="loading ? 'bi-arrow-clockwise animate-spin' : 'bi-arrow-clockwise'"></i> Refresh
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
                                <button type="button" class="dropdown-item" @click="refreshCalendarEvents">
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
                          <p class="text-muted small">Click "Add Event" to create your first calendar event.</p>
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
                                      <span class="badge ms-2" :style="{ backgroundColor: event.color }">
                                        @{{ event.display_name }}
                                      </span>
                                      <span v-if="event.schedule.all_day" class="badge bg-info ms-2">All Day</span>
                                    </h6>
                                  </div>
                                  <button class="btn btn-sm btn-outline-danger delete-calendar-event-btn"
                                    @click.stop="confirmDeleteEvent(event)" :data-id="event.event_id"
                                    :data-name="event.event_name" title="Delete Event">
                                    <i class="bi bi-trash"></i>
                                  </button>
                                </div>

                                <p v-if="event.description" class="text-muted small mb-2">
                                  @{{ event.description }}
                                </p>

                                <p class="text-muted small mb-0">
                                  <i class="bi bi-clock me-1"></i>
                                  @{{ event.schedule.display }} • Duration: @{{ formatDuration(event) }}
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
        </div>

      </div> <!-- Close row.g-3 -->
    </div> <!-- Close container-fluid -->
  </main>

  <!-- Add Reservation Modal -->
  <div class="modal fade" id="addReservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg">
        <!-- Modal Header -->
        <div class="modal-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center">
            <i class="bi bi-calendar-plus me-3 fs-4"></i>
            <div>
              <h5 class="modal-title mb-0 fw-semibold">Create New Reservation</h5>
              <small class="opacity-75">Add a reservation on behalf of a user</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <!-- Replace the entire modal body section (from line starting with <div class="modal-body p-0"> to before the modal footer) -->
        <div class="modal-body p-0">
          <!-- Progress Steps -->
          <div class="px-4 pt-4">
            <div class="steps">
              <div class="step active" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">User Info</div>
              </div>
              <div class="step" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">Details</div>
              </div>
              <div class="step" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">Resources</div>
              </div>
              <div class="step" data-step="4">
                <div class="step-circle">4</div>
                <div class="step-label">Schedule</div>
              </div>
              <div class="step" data-step="5">
                <div class="step-circle">5</div>
                <div class="step-label">Review</div>
              </div>
            </div>
          </div>

          <form id="addReservationForm" class="p-4">
            <!-- Step 1: User Information -->
            <div class="step-content" id="step1" data-step="1">
              <div class="card card-border mb-4">
                <div class="card-header bg-light-subtle">
                  <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-person-circle me-2"></i>User Information
                  </h6>
                </div>

                <div class="card-body">
                  <div class="row g-3">

                    <!-- User Type -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">User Type <span class="text-danger">*</span></label>
                      <select class="form-select form-select-md" name="user_type" id="userTypeSelect" required>
                        <option value="" disabled selected>Select Type</option>
                        <option value="Internal">Internal User</option>
                        <option value="External">External User</option>
                      </select>
                    </div>

                    <!-- School ID (Always visible but disabled for external) -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">School ID</label>
                      <input type="text" class="form-control" name="school_id" id="schoolIdInput"
                        placeholder="For internal users only" maxlength="20" disabled>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">First Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="first_name" placeholder="Enter first name"
                        maxlength="50" required>
                      <small class="text-muted d-block mt-1" id="firstNameCounter">0/50 characters</small>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Last Name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="last_name" placeholder="Enter last name"
                        maxlength="50" required>
                      <small class="text-muted d-block mt-1" id="lastNameCounter">0/50 characters</small>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Email Address</label>
                      <input type="email" class="form-control" name="email" placeholder="user@example.com"
                        maxlength="100">
                      <small class="text-muted d-block mt-1" id="emailCounter">0/100 characters</small>
                    </div>

                    <!-- Contact Number -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Contact Number</label>
                      <input type="tel" class="form-control" name="contact_number" placeholder="+1 (123) 456-7890"
                        maxlength="15">
                      <small class="text-muted d-block mt-1" id="contactCounter">0/15 characters</small>
                    </div>

                    <!-- Organization (Optional for all users) -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Organization
                        <span class="text-muted small">(optional)</span>
                      </label>
                      <input type="text" class="form-control" name="organization_name" id="organizationInput"
                        placeholder="Organization name" maxlength="100">
                      <small class="text-muted d-block mt-1" id="organizationCounter">0/100 characters</small>
                    </div>

                  </div>
                </div>
              </div>
            </div>


            <!-- Step 2: Request Details -->
            <div class="step-content d-none" id="step2" data-step="2">
              <div class="card card-border mb-4">
                <div class="card-header bg-light-subtle">
                  <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-clipboard-data me-2"></i>Request Details
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <!-- Number of Participants -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Participants <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                        <input type="number" class="form-control" name="num_participants" min="1" max="500"
                          placeholder="Number of attendees" required>
                      </div>
                    </div>

                    <!-- Purpose -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Purpose <span class="text-danger">*</span></label>
                      <select class="form-select" name="purpose_id" id="purposeSelect" required>
                        <option value="" disabled selected>Select purpose of reservation</option>
                        <!-- Will be populated by JavaScript -->
                      </select>
                    </div>

                    <!-- Number of Tables -->
                    <div class="col-md-4">
                      <label class="form-label fw-medium">Number of Tables <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-table"></i></span>
                        <input type="number" class="form-control" name="num_tables" min="0" max="100" value="0" required>
                      </div>
                      <small class="text-muted">Required field, enter 0 if none</small>
                    </div>

                    <!-- Number of Chairs -->
                    <div class="col-md-4">
                      <label class="form-label fw-medium">Number of Chairs <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="number" class="form-control" name="num_chairs" min="0" max="500" value="0" required>
                      </div>
                      <small class="text-muted">Required field, enter 0 if none</small>
                    </div>

                    <!-- Number of Microphones (NEW) -->
                    <div class="col-md-4">
                      <label class="form-label fw-medium">Number of Microphones</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-mic"></i></span>
                        <input type="number" class="form-control" name="num_microphones" min="0" max="100" value="0">
                      </div>
                      <small class="text-muted">Enter 0 if none</small>
                    </div>

                    <!-- Extra Services Section (NEW) -->
                    <div class="col-12 mt-3">
                      <div class="card border bg-light">
                        <div class="card-body">
                          <h6 class="fw-semibold mb-3">
                            <i class="bi bi-grid-3x3-gap-fill me-2"></i>Extra Services
                          </h6>
                          <div class="row g-3" id="extraServicesContainer">
                            <!-- Extra services will be populated by JavaScript -->
                            <div class="col-12 text-center text-muted py-3">
                              <div class="spinner-border spinner-border-sm me-2"></div>
                              Loading extra services...
                            </div>
                          </div>
                          <small class="text-muted d-block mt-2">Select any additional services you may need</small>
                        </div>
                      </div>
                    </div>

                    <!-- Endorser and Date Endorsed side by side -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Endorser Name (Optional)</label>
                      <input type="text" class="form-control" name="endorser" placeholder="Name of endorser"
                        maxlength="50">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fw-medium">Date Endorsed (Optional)</label>
                      <input type="date" class="form-control" name="date_endorsed">
                    </div>

                    <!-- Additional Requests -->
                    <div class="col-12">
                      <label class="form-label fw-medium">Additional Requests & Notes (Optional)</label>
                      <textarea class="form-control" name="additional_requests" rows="3" maxlength="250"
                        placeholder="Any special requirements, setup needs, or additional information..."></textarea>
                      <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted"><span id="additionalRequestsCounter">0/250 characters</span></small>
                        <small class="text-muted text-danger">Required for external users</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 3: Resources -->
            <div class="step-content d-none" id="step3" data-step="3">
              <div class="card card-border mb-4">
                <div class="card-header bg-light-subtle">
                  <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-building me-2"></i>Resources
                  </h6>
                </div>
                <div class="card-body">
                  <!-- Facilities -->
                  <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="form-label fw-medium mb-0">Select Facilities</label>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFacilities">
                        Clear All
                      </button>
                    </div>
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" id="selectAllFacilities">
                      <label class="form-check-label fw-medium" for="selectAllFacilities">
                        Select All Available Facilities
                      </label>
                    </div>
                    <div class="facilities-grid" id="facilitiesList">
                      <!-- Facilities will be populated by JavaScript -->
                    </div>
                  </div>

                  <!-- Equipment -->
                  <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <label class="form-label fw-medium mb-0">Select Equipment</label>
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="clearEquipment">
                        Clear All
                      </button>
                    </div>
                    <div class="equipment-grid" id="equipmentList">
                      <!-- Equipment will be populated by JavaScript -->
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 4: Schedule -->
            <div class="step-content d-none" id="step4" data-step="4">
              <div class="card card-border mb-4">
                <div class="card-header bg-light-subtle">
                  <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-calendar-event me-2"></i>Schedule
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <!-- All Day Checkbox -->
                    <div class="col-12">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="allDayCheckbox" name="all_day">
                        <label class="form-check-label fw-medium" for="allDayCheckbox">
                          <i class="bi bi-calendar-day me-1"></i>All Day Event
                        </label>
                        <div class="form-text text-muted small ms-4 ps-3">
                          Check this if the event lasts the entire day (times will be set to 12:00 AM)
                        </div>
                      </div>
                    </div>

                    <!-- Visual indicator for all-day events -->
                    <div class="col-12" id="allDayScheduleIndicator" style="display: none;">
                      <div class="alert alert-info mb-0 py-2">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        This will be displayed as an <strong>all-day event</strong> on the calendar with times set to
                        12:00 AM.
                      </div>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                          <input type="date" class="form-control" name="start_date" id="startDate" required>
                        </div>
                      </div>
                    </div>

                    <!-- Start Time -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label fw-medium">Start Time <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-text"><i class="bi bi-clock"></i></span>
                          <select class="form-select" name="start_time" id="startTime" required>
                            <option value="" disabled selected>Select start time</option>
                            <!-- Options will be populated by JavaScript -->
                          </select>
                        </div>
                      </div>
                    </div>

                    <!-- End Date -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label fw-medium">End Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                          <input type="date" class="form-control" name="end_date" id="endDate" required>
                        </div>
                      </div>
                    </div>

                    <!-- End Time -->
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label class="form-label fw-medium">End Time <span class="text-danger">*</span></label>
                        <div class="input-group">
                          <span class="input-group-text"><i class="bi bi-clock"></i></span>
                          <select class="form-select" name="end_time" id="endTime" required>
                            <option value="" disabled selected>Select end time</option>
                            <!-- Options will be populated by JavaScript -->
                          </select>
                        </div>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Duration: <span id="durationDisplay">0 hours</span></small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 5: Review & Additional Info -->
            <div class="step-content d-none" id="step5" data-step="5">
              <div class="card card-border mb-4">
                <div class="card-header bg-light-subtle">
                  <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-sticky me-2"></i>Review & Additional Information
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3 mb-4">
                    <!-- Status Selection -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Initial Status <span class="text-danger">*</span></label>
                      <select class="form-select" name="initial_status" id="initialStatusSelect" required>
                        <option value="" disabled selected>Select initial status</option>
                        <!-- Will be populated by JavaScript -->
                      </select>
                      <small class="text-muted">Set the initial status for this reservation</small>
                    </div>

                    <!-- Calendar Title -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Calendar Title</label>
                      <input type="text" class="form-control" name="calendar_title"
                        placeholder="e.g., Quarterly Meeting - Sales Team" maxlength="50">
                      <small class="text-muted"><span id="calendarTitleCounter">0/50 characters</span></small>
                    </div>

                    <!-- Calendar Description -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Calendar Description</label>
                      <textarea class="form-control" name="calendar_description" rows="2" maxlength="100"
                        placeholder="Brief description visible on calendar..."></textarea>
                      <small class="text-muted"><span id="calendarDescriptionCounter">0/100 characters</span></small>
                    </div>

                    <!-- Access Code -->
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Access Code <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input type="text" class="form-control font-monospace" name="access_code" id="accessCodeInput"
                          placeholder="Click generate" readonly maxlength="10">
                        <button type="button" class="btn btn-outline-primary" id="generateAccessCode">
                          <i class="bi bi-key me-1"></i> Generate
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="copyAccessCode"
                          title="Copy to clipboard">
                          <i class="bi bi-clipboard"></i>
                        </button>
                      </div>
                      <small class="text-muted mt-1 d-block">Unique 10-character code for event access. Click generate to
                        create.</small>
                    </div>
                  </div>

                  <!-- Review Summary -->
                  <div class="border-top pt-4 mt-4">
                    <h6 class="fw-semibold mb-3">Reservation Summary</h6>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-2">
                          <small class="text-muted">User:</small>
                          <div id="reviewUserName" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Purpose:</small>
                          <div id="reviewPurpose" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Participants:</small>
                          <div id="reviewParticipants" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Tables/Chairs:</small>
                          <div id="reviewFurniture" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Microphones:</small>
                          <div id="reviewMicrophones" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Extra Services:</small>
                          <div id="reviewServices" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Endorser:</small>
                          <div id="reviewEndorser" class="fw-medium">-</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-2">
                          <small class="text-muted">Initial Status:</small>
                          <div id="reviewStatus" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Schedule:</small>
                          <div id="reviewSchedule" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Duration:</small>
                          <div id="reviewDuration" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Selected Facilities:</small>
                          <div id="reviewFacilities" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Selected Equipment:</small>
                          <div id="reviewEquipment" class="fw-medium">-</div>
                        </div>
                        <div class="mb-2">
                          <small class="text-muted">Access Code:</small>
                          <div id="reviewAccessCode" class="fw-medium font-monospace">-</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <!-- Update the modal footer to include navigation buttons -->
        <div class="modal-footer bg-light-subtle d-flex justify-content-between">
          <div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
          </div>
          <div>
            <button type="button" class="btn btn-outline-secondary me-2" id="prevStepBtn" style="display: none;">
              <i class="bi bi-chevron-left me-1"></i> Previous
            </button>
            <button type="button" class="btn btn-primary" id="nextStepBtn">
              Next <i class="bi bi-chevron-right ms-1"></i>
            </button>
            <button type="button" class="btn btn-success d-none" id="submitReservationBtn">
              <span class="spinner-border spinner-border-sm me-1 d-none"></span>
              <i class="bi bi-check-circle me-1"></i> Create Reservation
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Approval History Modal -->
  <div class="modal fade" id="approvalHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Approval History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs" id="approvalHistoryTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals"
                type="button" role="tab" aria-controls="approvals" aria-selected="true">
                <i class="bi bi-hand-thumbs-up text-success me-1"></i>
                Approvals <span class="badge bg-success ms-1" id="approvalsTabCount">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="rejections-tab" data-bs-toggle="tab" data-bs-target="#rejections" type="button"
                role="tab" aria-controls="rejections" aria-selected="false">
                <i class="bi bi-hand-thumbs-down text-danger me-1"></i>
                Rejections <span class="badge bg-danger ms-1" id="rejectionsTabCount">0</span>
              </button>
            </li>
          </ul>
          <div class="tab-content mt-3" id="approvalHistoryContent">
            <div class="tab-pane fade show active" id="approvals" role="tabpanel" aria-labelledby="approvals-tab">
              <div id="approvalsHistoryContent">
                <div class="text-center text-muted py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2">Loading approvals...</p>
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="rejections" role="tabpanel" aria-labelledby="rejections-tab">
              <div id="rejectionsHistoryContent">
                <div class="text-center text-muted py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2">Loading rejections...</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add new calendar event modal -->
  <div class="modal fade" id="addCalendarEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header bg-gradient-primary text-white">
          <h5 class="modal-title mb-0 fw-semibold">
            <i class="bi bi-calendar-plus me-2"></i>Add Calendar Event
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="addCalendarEventForm">
          <div class="modal-body">
            <div class="row g-3">
              <!-- Event Name and Event Type in same row -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Event Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="event_name" required placeholder="Enter event name"
                  maxlength="255">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Event Type <span class="text-danger">*</span></label>
                <select class="form-select" name="event_type" required>
                  <option value="" disabled selected>Select event type</option>
                  <option value="hall_booking">Hall Booking</option>
                  <option value="school_event">School Event</option>
                  <option value="holiday">Holiday</option>
                </select>
              </div>

              <!-- Description -->
              <div class="col-12">
                <label class="form-label fw-medium">Description</label>
                <textarea class="form-control" name="description" rows="3"
                  placeholder="Enter event description"></textarea>
              </div>

              <!-- All Day Checkbox -->
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="calendarAllDayField" name="all_day" value="1">
                  <label class="form-check-label fw-medium" for="calendarAllDayField">
                    <i class="bi bi-calendar-day me-1"></i>All Day Event
                  </label>
                  <div class="form-text text-muted small ms-4 ps-3">
                    Check this if the event lasts the entire day (times will be handled by the server)
                  </div>
                </div>
              </div>

              <!-- Start Date & Time -->
              <div class="col-md-6">
                <label class="form-label fw-medium">Start Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="start_date" id="calendarStartDate" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">Start Time</label>
                <input type="time" class="form-control" name="start_time" id="calendarStartTime">
                <div class="form-text text-muted small time-helper">Optional for all-day events</div>
              </div>

              <!-- End Date & Time -->
              <div class="col-md-6">
                <label class="form-label fw-medium">End Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="end_date" id="calendarEndDate" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-medium">End Time</label>
                <input type="time" class="form-control" name="end_time" id="calendarEndTime">
                <div class="form-text text-muted small time-helper">Optional for all-day events</div>
              </div>

              <!-- Visual indicator for all-day events -->
              <div class="col-12" id="allDayIndicator" style="display: none;">
                <div class="alert alert-info mb-0 py-2">
                  <i class="bi bi-info-circle-fill me-2"></i>
                  This will be displayed as an <strong>all-day event</strong> on the calendar.
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <span class="spinner-border spinner-border-sm d-none me-1" id="eventSubmitSpinner"></span>
              <i class="bi bi-calendar-plus me-1"></i>Add Event
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

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

@endsection

@section('scripts')
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <script src="{{ asset('js/public/calendar.js') }}"></script>

  <!-- Load modal functions lazily -->
  <script>
    // Load modal functions only when needed
    window.loadModalFunctions = async function () {
      if (!window.modalFunctionsLoaded) {
        await import('{{ asset('js/admin/calendar-modal.js') }}');
        window.modalFunctionsLoaded = true;
      }
    };
  </script>


  <script>
    // ===== GLOBAL VARIABLES =====
    let adminToken = localStorage.getItem('adminToken');
    let deleteEventModal = null;
    let eventToDeleteId = null;
    let eventToDeleteName = null;

    // ===== TOAST FUNCTION (Keep this) =====
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
                          <div class="loading-bar" style="height: 3px; background: rgba(255,255,255,0.7); width: 100%; transition: width ${duration}ms linear;"></div>
                      `;

      document.body.appendChild(toast);
      const bsToast = new bootstrap.Toast(toast, { autohide: false });
      bsToast.show();

      requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
      });

      const loadingBar = toast.querySelector('.loading-bar');
      requestAnimationFrame(() => loadingBar.style.width = '0%');

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        setTimeout(() => {
          bsToast.hide();
          toast.remove();
        }, 400);
      }, duration);
    };

    // ===== DEBOUNCE FUNCTION =====
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // ===== DELETE CALENDAR EVENT FUNCTIONS =====
    window.confirmDeleteCalendarEvent = function (eventId, eventName) {
      eventToDeleteId = eventId;
      eventToDeleteName = eventName;

      document.getElementById('deleteEventModalTitle').textContent = `Delete "${eventName}"`;
      document.getElementById('deleteEventModalText').textContent =
        `Are you sure you want to delete the event "${eventName}"? This action cannot be undone.`;

      if (!deleteEventModal) {
        deleteEventModal = new bootstrap.Modal(document.getElementById('deleteCalendarEventModal'));
      }
      deleteEventModal.show();
    };

    async function handleDeleteCalendarEvent() {
      if (!eventToDeleteId) return;

      const confirmBtn = document.getElementById('confirmDeleteEventBtn');
      const spinner = document.getElementById('deleteEventSpinner');
      const originalText = confirmBtn.innerHTML;

      try {
        confirmBtn.disabled = true;
        spinner.classList.remove('d-none');

        const response = await fetch(`/api/calendar-events/${eventToDeleteId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });

        if (!response.ok) throw new Error('Failed to delete event');

        showToast('Calendar event deleted successfully!', 'success');

        // Refresh Vue app
        if (window.calendarEventsApp) {
          await window.calendarEventsApp.loadCalendarEvents(window.calendarEventsApp.currentPage);
        }

        // Refresh main calendar
        if (window.calendarModule) {
          await window.calendarModule.loadCalendarEvents();
        }

        if (deleteEventModal) deleteEventModal.hide();

      } catch (error) {
        console.error('Error deleting event:', error);
        showToast('Failed to delete event', 'error');
      } finally {
        confirmBtn.disabled = false;
        spinner.classList.add('d-none');
        confirmBtn.innerHTML = originalText;
        eventToDeleteId = null;
        eventToDeleteName = null;
      }
    }

    document.getElementById('confirmDeleteEventBtn')?.addEventListener('click', handleDeleteCalendarEvent);

    // ===== MAIN INITIALIZATION =====
    document.addEventListener('DOMContentLoaded', async function () {
      // Check authentication
      if (!adminToken) {
        window.location.href = "/admin/login";
        return;
      }

      // Show loading skeletons
      document.body.classList.add('loading');

      try {
        // ===== INITIALIZE CALENDAR MODULE =====
        const calendarModule = new CalendarModule({
          isAdmin: true,
          adminToken: adminToken,
          apiEndpoint: '/api/requisition-forms/calendar-events',
          calendarEventsEndpoint: '/api/calendar-events',
          containerId: 'calendar',
          miniCalendarContainerId: 'miniCalendarDays',
          monthYearId: 'currentMonthYear',
          eventModalId: 'calendarEventModal'
        });

        window.calendarModule = calendarModule;
        await calendarModule.initialize();

        // Force calendar render
        setTimeout(() => {
          if (calendarModule?.calendar) {
            calendarModule.calendar.updateSize();
            calendarModule.calendar.render();
          }
        }, 100);

        // ===== FETCH PENDING COUNT =====
        setTimeout(async () => {
          try {
            const response = await fetch('/api/admin/pending-requests-count', {
              headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
            });
            if (response.ok) {
              const result = await response.json();
              document.getElementById('pendingRequestsCount').textContent = result.count || 0;
            }
          } catch (error) {
            console.error('Error fetching pending count:', error);
          }
        }, 50);

        // ===== VUE INSTANCES =====

        // Ongoing Events App
        const ongoingEventsApp = new Vue({
          el: '#ongoingEventsApp',
          data: {
            ongoingEvents: [],
            loading: false,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 10,
            filters: { status: '', sort: 'newest', search: '' }
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
                  if (i - l === 2) rangeWithDots.push(l + 1);
                  else if (i - l !== 1) rangeWithDots.push('...');
                }
                rangeWithDots.push(i);
                l = i;
              });
              return rangeWithDots;
            }
          },
          methods: {
            async loadOngoingEvents(page = 1) {
              this.loading = true;
              this.currentPage = page;
              try {
                const params = new URLSearchParams({
                  page: page,
                  per_page: this.perPage,
                  status: this.filters.status,
                  sort: this.filters.sort,
                  search: this.filters.search
                });
                const response = await fetch(`/api/admin/ongoing-requests?${params}`, {
                  headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                if (response.ok) {
                  const result = await response.json();
                  this.ongoingEvents = result.data || [];
                  this.totalPages = result.meta?.last_page || 1;
                  this.totalItems = result.meta?.total || 0;
                }
              } catch (error) {
                console.error('Error loading ongoing events:', error);
                this.ongoingEvents = [];
                showToast('Failed to load ongoing events', 'error');
              } finally {
                this.loading = false;
              }
            },
            refreshOngoingEvents() { this.loadOngoingEvents(this.currentPage); },
            formatDuration(event) { return event.schedule?.duration || 'N/A'; },
            displayItems(items) { return items?.slice(0, 2) || []; },
            changePage(page) { if (page >= 1 && page <= this.totalPages) this.loadOngoingEvents(page); },
            goToEvent(requestId) { window.location.href = `/admin/requisition/${requestId}`; }
          },
          mounted() { this.loadOngoingEvents(1); }
        });

        // Pending Requests App
        const pendingRequestsApp = new Vue({
          el: '#pendingRequestsApp',
          data: {
            pendingRequests: [],
            loading: false,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 10
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
                  if (i - l === 2) rangeWithDots.push(l + 1);
                  else if (i - l !== 1) rangeWithDots.push('...');
                }
                rangeWithDots.push(i);
                l = i;
              });
              return rangeWithDots;
            }
          },
          methods: {
            async loadPendingRequests(page = 1) {
              this.loading = true;
              this.currentPage = page;
              try {
                const params = new URLSearchParams({ page: page, per_page: this.perPage });
                const response = await fetch(`/api/admin/pending-requests?${params}`, {
                  headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                if (response.ok) {
                  const result = await response.json();
                  this.pendingRequests = result.data || [];
                  this.totalPages = result.meta?.last_page || 1;
                  this.totalItems = result.meta?.total || 0;
                  document.getElementById('pendingRequestsCount').textContent = this.totalItems;
                }
              } catch (error) {
                console.error('Error loading pending requests:', error);
                this.pendingRequests = [];
                showToast('Failed to load pending requests', 'error');
              } finally {
                this.loading = false;
              }
            },
            refreshPendingRequests() { this.loadPendingRequests(this.currentPage); },
            formatDuration(request) { return request.schedule?.duration || 'N/A'; },
            displayItems(items) { return items || []; },
            changePage(page) { if (page >= 1 && page <= this.totalPages) this.loadPendingRequests(page); },
            goToEvent(requestId) { window.location.href = `/admin/requisition/${requestId}`; }
          },
          mounted() {
            const pendingTab = document.getElementById('pending-requests-tab');
            if (pendingTab) {
              pendingTab.addEventListener('shown.bs.tab', () => {
                if (this.pendingRequests.length === 0 && !this.loading) {
                  this.loadPendingRequests(1);
                }
              });
            }
          }
        });

        // Calendar Events App
        const calendarEventsApp = new Vue({
          el: '#calendarEventsApp',
          data: {
            calendarEvents: [],
            loading: false,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 50,
            filters: { eventType: '', sort: 'newest', search: '' }
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
                  if (i - l === 2) rangeWithDots.push(l + 1);
                  else if (i - l !== 1) rangeWithDots.push('...');
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
                const params = new URLSearchParams({ page: page, per_page: this.perPage });
                if (this.filters.eventType) params.append('event_type', this.filters.eventType);
                if (this.filters.search) params.append('search', this.filters.search);

                const response = await fetch(`/api/calendar-events?${params}`, {
                  headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
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
                }
              } catch (error) {
                console.error('Error loading calendar events:', error);
                this.calendarEvents = [];
                showToast('Error loading calendar events', 'error');
              } finally {
                this.loading = false;
              }
            },
            refreshCalendarEvents() { this.loadCalendarEvents(this.currentPage); },
            formatDuration(event) {
              if (!event?.schedule) return 'N/A';
              const s = event.schedule;
              if (s.all_day) {
                const start = new Date(s.start_date + 'T00:00:00');
                const end = new Date(s.end_date + 'T00:00:00');
                const days = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                return days === 1 ? '1 day' : `${days} days`;
              }
              if (s.start_time && s.end_time) {
                try {
                  const start = new Date(`${s.start_date}T${s.start_time}`);
                  const end = new Date(`${s.end_date}T${s.end_time}`);
                  let durationMs = end - start;
                  if (durationMs < 0) {
                    const adjustedEnd = new Date(end);
                    adjustedEnd.setDate(adjustedEnd.getDate() + 1);
                    durationMs = adjustedEnd - start;
                  }
                  const minutes = Math.floor(durationMs / (1000 * 60));
                  const hours = Math.floor(minutes / 60);
                  const mins = minutes % 60;
                  if (hours > 0 && mins > 0) return `${hours}h ${mins}m`;
                  if (hours > 0) return `${hours}hrs`;
                  return `${mins}mins`;
                } catch (error) {
                  return 'N/A';
                }
              }
              return 'N/A';
            },
            confirmDeleteEvent(event) {
              if (typeof window.confirmDeleteCalendarEvent === 'function') {
                window.confirmDeleteCalendarEvent(event.event_id, event.event_name);
              }
            },
            changePage(page) { if (page >= 1 && page <= this.totalPages) this.loadCalendarEvents(page); }
          },
          mounted() {
            const calendarTab = document.getElementById('calendar-events-tab');
            if (calendarTab) {
              calendarTab.addEventListener('shown.bs.tab', () => {
                if (this.calendarEvents.length === 0 && !this.loading) {
                  this.loadCalendarEvents(1);
                }
              });
            }
            const refreshBtn = document.getElementById('refreshCalendarEventsBtn');
            if (refreshBtn) {
              refreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.refreshCalendarEvents();
              });
            }
          }
        });

        window.calendarEventsApp = calendarEventsApp;

        // ===== RESERVATION MODAL INITIALIZATION =====
        const modalElement = document.getElementById('addReservationModal');
        if (modalElement) {
          modalElement.addEventListener('show.bs.modal', async function () {
            // Lazy load modal scripts
            const modalScripts = await import('{{ asset('js/calendar-modal.js') }}');

            // Initialize modal components
            const allDayCheckbox = document.getElementById('allDayCheckbox');
            if (allDayCheckbox) {
              const newCheckbox = allDayCheckbox.cloneNode(true);
              allDayCheckbox.parentNode.replaceChild(newCheckbox, allDayCheckbox);
              newCheckbox.addEventListener('change', function () {
                const startTimeSelect = document.getElementById('startTime');
                const endTimeSelect = document.getElementById('endTime');
                const allDayIndicator = document.getElementById('allDayScheduleIndicator');

                if (this.checked) {
                  startTimeSelect.disabled = true;
                  endTimeSelect.disabled = true;
                  startTimeSelect.dataset.originalValue = startTimeSelect.value;
                  endTimeSelect.dataset.originalValue = endTimeSelect.value;
                  startTimeSelect.value = '00:00';
                  endTimeSelect.value = '00:00';
                  if (allDayIndicator) allDayIndicator.style.display = 'block';
                  startTimeSelect.classList.add('bg-light');
                  endTimeSelect.classList.add('bg-light');
                } else {
                  startTimeSelect.disabled = false;
                  endTimeSelect.disabled = false;
                  startTimeSelect.value = startTimeSelect.dataset.originalValue || '09:00';
                  endTimeSelect.value = endTimeSelect.dataset.originalValue || '17:00';
                  if (allDayIndicator) allDayIndicator.style.display = 'none';
                  startTimeSelect.classList.remove('bg-light');
                  endTimeSelect.classList.remove('bg-light');
                }
                // Use the imported calculateDuration function
                if (typeof modalScripts.calculateDuration === 'function') {
                  modalScripts.calculateDuration();
                }
              });
            }

            // Use imported functions with modalScripts prefix
            modalScripts.initializeUserTypeToggle();

            await Promise.all([
              modalScripts.loadPurposes(),
              modalScripts.loadFacilitiesForReservation(),
              modalScripts.loadEquipmentForReservation(),
              modalScripts.loadExtraServices(),
              modalScripts.loadStatusOptions()
            ]);

            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);

            document.getElementById('startDate').value = now.toISOString().split('T')[0];
            document.getElementById('endDate').value = tomorrow.toISOString().split('T')[0];

            modalScripts.populateTimeDropdowns();
            modalScripts.calculateDuration();

            document.getElementById('startDate').addEventListener('change', modalScripts.calculateDuration);
            document.getElementById('startTime').addEventListener('change', modalScripts.calculateDuration);
            document.getElementById('endDate').addEventListener('change', modalScripts.calculateDuration);
            document.getElementById('endTime').addEventListener('change', modalScripts.calculateDuration);

            setTimeout(() => modalScripts.setupCharacterCounters(), 200);

            // Initialize step navigation
            modalScripts.setupReservationStepNavigation();
          });
        }

        // ===== SAVE RESERVATION BUTTON =====
        document.getElementById('submitReservationBtn')?.addEventListener('click', saveReservation);

        // ===== COPY ACCESS CODE =====
        document.getElementById('copyAccessCode')?.addEventListener('click', function () {
          const codeInput = document.getElementById('accessCodeInput');
          codeInput.select();
          document.execCommand('copy');
          const originalText = this.innerHTML;
          this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
          setTimeout(() => this.innerHTML = originalText, 2000);
        });

        // ===== HIDE LOADING SKELETONS =====
        document.body.classList.remove('loading');

      } catch (error) {
        console.error('Initialization error:', error);
        showToast('Failed to initialize page. Please refresh.', 'error');
        document.body.classList.remove('loading');
      }
    });

    // ===== SAVE RESERVATION FUNCTION (Keep this) =====
    async function saveReservation() {
      const form = document.getElementById('addReservationForm');
      const confirmBtn = document.getElementById('submitReservationBtn');
      const statusSelect = document.getElementById('initialStatusSelect');
      const statusId = statusSelect ? parseInt(statusSelect.value) : 1;

      // Get the selected status text to check if it's pending
      const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
      const selectedStatusText = selectedStatusOption ? selectedStatusOption.text : '';
      const isPendingStatus = selectedStatusText === 'Pending Approval' || selectedStatusText === 'Awaiting Payment';

      const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

      // Save original button state
      const originalText = confirmBtn.innerHTML;
      confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
      confirmBtn.disabled = true;

      try {
        // Get form values for validation
        const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
        const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
        const email = document.querySelector('input[name="email"]')?.value.trim() || '';
        const contactNumber = document.querySelector('input[name="contact_number"]')?.value || '';
        const organizationName = document.querySelector('input[name="organization_name"]')?.value || '';
        const schoolId = document.querySelector('input[name="school_id"]')?.value || '';
        const endorser = document.querySelector('input[name="endorser"]')?.value || '';
        const additionalRequests = document.querySelector('textarea[name="additional_requests"]')?.value || '';
        const calendarTitle = document.querySelector('input[name="calendar_title"]')?.value || '';
        const calendarDescription = document.querySelector('textarea[name="calendar_description"]')?.value || '';
        const accessCode = document.querySelector('input[name="access_code"]')?.value || '';

        // Get purpose value
        const purposeSelect = document.getElementById('purposeSelect');
        const purposeValue = purposeSelect?.value;
        const purposeId = purposeValue ? parseInt(purposeValue) : null;

        // Get numeric values
        const numParticipants = parseInt(document.querySelector('input[name="num_participants"]')?.value || 1);
        const numTables = parseInt(document.querySelector('input[name="num_tables"]')?.value || 0);
        const numChairs = parseInt(document.querySelector('input[name="num_chairs"]')?.value || 0);

        // Get date/time values
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        let startTime = document.getElementById('startTime')?.value;
        let endTime = document.getElementById('endTime')?.value;

        // Get selected facilities and equipment
        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked'))
          .map(cb => ({
            facility_id: parseInt(cb.value),
            name: cb.dataset.name,
            fee: parseFloat(cb.dataset.fee) || 0,
            rate_type: cb.dataset.rateType || 'Per Event'
          }));

        const selectedEquipment = Array.from(document.querySelectorAll('.equipment-checkbox:checked'))
          .map(cb => ({
            equipment_id: parseInt(cb.value),
            name: cb.dataset.name,
            fee: parseFloat(cb.dataset.fee) || 0,
            rate_type: cb.dataset.rateType || 'Per Event',
            quantity: 1
          }));

        // Get selected extra services
        const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked'))
          .map(cb => ({
            service_id: parseInt(cb.value),
            name: cb.dataset.name
          }));

        // Get number of microphones
        const numMicrophones = parseInt(document.querySelector('input[name="num_microphones"]')?.value || 0);

        // CLIENT-SIDE VALIDATION based on database schema
        const validationErrors = [];

        // First Name validation (max 50 chars)
        if (firstName.length > 50) {
          validationErrors.push('First name must be 50 characters or less');
        }
        if (!firstName) {
          validationErrors.push('First name is required');
        }

        // Last Name validation (max 50 chars)
        if (lastName.length > 50) {
          validationErrors.push('Last name must be 50 characters or less');
        }
        if (!lastName) {
          validationErrors.push('Last name is required');
        }

        // Email validation (max 100 chars)
        if (email.length > 100) {
          validationErrors.push('Email must be 100 characters or less');
        }
        if (!email) {
          validationErrors.push('Email is required');
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          validationErrors.push('Email format is invalid');
        }

        // Contact Number validation (max 15 chars)
        if (contactNumber && contactNumber.length > 15) {
          validationErrors.push('Contact number must be 15 characters or less');
        }

        // Organization Name validation (max 100 chars)
        if (organizationName && organizationName.length > 100) {
          validationErrors.push('Organization name must be 100 characters or less');
        }

        // School ID validation (max 20 chars)
        if (schoolId && schoolId.length > 20) {
          validationErrors.push('School ID must be 20 characters or less');
        }

        // Endorser validation (max 50 chars)
        if (endorser && endorser.length > 50) {
          validationErrors.push('Endorser name must be 50 characters or less');
        }

        // Additional Requests validation (max 250 chars)
        if (additionalRequests && additionalRequests.length > 250) {
          validationErrors.push('Additional requests must be 250 characters or less');
        }

        // Calendar Title validation (max 50 chars)
        if (calendarTitle && calendarTitle.length > 50) {
          validationErrors.push('Calendar title must be 50 characters or less');
        }

        // Calendar Description validation (max 100 chars)
        if (calendarDescription && calendarDescription.length > 100) {
          validationErrors.push('Calendar description must be 100 characters or less');
        }

        // Access Code validation (exactly 10 chars)
        if (accessCode.length !== 10) {
          validationErrors.push('Access code must be exactly 10 characters');
        }
        if (!accessCode) {
          validationErrors.push('Access code is required');
        }

        // Numeric validations
        if (numParticipants < 1 || numParticipants > 500) {
          validationErrors.push('Number of participants must be between 1 and 500');
        }
        if (numTables < 0 || numTables > 100) {
          validationErrors.push('Number of tables must be between 0 and 100');
        }
        if (numChairs < 0 || numChairs > 500) {
          validationErrors.push('Number of chairs must be between 0 and 500');
        }

        // Purpose validation
        if (!purposeId || isNaN(purposeId) || purposeId <= 0) {
          validationErrors.push('Please select a valid purpose');
        }

        // Date/Time validation
        if (!startDate || !endDate) {
          validationErrors.push('Start and end dates are required');
        } else {
          // Validate dates are not in the past
          const startDateOnly = new Date(startDate);
          const today = new Date();
          today.setHours(0, 0, 0, 0);

          if (startDateOnly < today) {
            validationErrors.push('Start date cannot be in the past');
          }

          if (isAllDay) {
            // For all-day events: end date must be on or after start date
            const startDateTime = new Date(`${startDate}T00:00:00`);
            const endDateTime = new Date(`${endDate}T00:00:00`);

            if (endDateTime < startDateTime) {
              validationErrors.push('End date must be on or after start date for all-day events');
            }
          } else {
            // For timed events: end must be after start
            if (!startTime || !endTime) {
              validationErrors.push('Start and end times are required for timed events');
            } else {
              const startDateTime = new Date(`${startDate}T${startTime}`);
              const endDateTime = new Date(`${endDate}T${endTime}`);

              if (endDateTime <= startDateTime) {
                validationErrors.push('End date/time must be after start date/time for timed events');
              }
            }
          }
        }

        // Validate at least one facility or equipment is selected
        if (selectedFacilities.length === 0 && selectedEquipment.length === 0) {
          validationErrors.push('Please select at least one facility or equipment item');
        }

        // If there are validation errors, show them and stop
        if (validationErrors.length > 0) {
          throw new Error(`Validation failed:\n${validationErrors.join('\n• ')}`);
        }

        // For all-day events, set times to 00:00 (12:00 AM)
        if (isAllDay) {
          startTime = '00:00';
          endTime = '00:00';
        }

        // Prepare data for API
        const reservationData = {
          status_id: statusId,
          start_date: startDate,
          end_date: endDate,
          start_time: startTime,
          end_time: endTime,
          all_day: isAllDay,
          purpose_id: purposeId,
          num_participants: numParticipants,
          num_tables: numTables,
          num_chairs: numChairs,
          num_microphones: numMicrophones,
          access_code: accessCode,
          first_name: firstName,
          last_name: lastName,
          email: email,
          contact_number: contactNumber || null,
          organization_name: organizationName || null,
          user_type: document.querySelector('select[name="user_type"]')?.value,
          additional_requests: additionalRequests || null,
          endorser: endorser || null,
          date_endorsed: document.querySelector('input[name="date_endorsed"]')?.value || null,
          calendar_title: calendarTitle || null,
          calendar_description: calendarDescription || null,
          facilities: selectedFacilities,
          equipment: selectedEquipment,
          services: selectedServices,
          is_admin_created: true
        };

        console.log('Submitting reservation with all_day:', isAllDay);
        console.log('Complete payload:', JSON.stringify(reservationData, null, 2));

        // Call the admin-only endpoint
        const response = await fetch('/api/admin/requisition/create', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(reservationData)
        });

        const result = await response.json();

        // Log the response for debugging
        console.log('API Response:', result);

        // Check if response is OK (status 200-299)
        if (!response.ok) {
          console.error('API Error Response:', result);

          if (response.status === 422) {
            // Validation errors from server
            const errorMessages = [];
            if (result.errors) {
              Object.entries(result.errors).forEach(([field, messages]) => {
                errorMessages.push(`${field}: ${messages.join(', ')}`);
              });
            }
            throw new Error(`Server validation failed: ${errorMessages.join('; ')}`);
          } else if (response.status === 409) {
            // Conflict errors - IMPROVED MESSAGE HANDLING
            const conflictMessages = result.conflict_items?.map(item => {
              // Try to get the item name from various possible locations
              let itemName = 'Unknown';

              if (item.name) {
                itemName = item.name;
              } else if (item.facility_name) {
                itemName = item.facility_name;
              } else if (item.equipment_name) {
                itemName = item.equipment_name;
              } else if (item.item_name) {
                itemName = item.item_name;
              } else if (item.resource_name) {
                itemName = item.resource_name;
              }

              // Get the conflict message
              const message = item.message || `Only ${item.available || 0} available, requested ${item.requested || 1}`;

              return `${itemName} - ${message}`;
            }) || [];

            throw new Error(`Scheduling conflicts: ${conflictMessages.join(', ')}`);
          } else {
            throw new Error(result.message || `HTTP Error ${response.status}: Failed to create reservation`);
          }
        }

        // Check if the response indicates success
        if (result && (result.success === true || result.data || result.request_id)) {
          showToast('Reservation created successfully!', 'success');

          // Get modal instance and hide it
          const modalElement = document.getElementById('addReservationModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) {
            modal.hide();
          }

          // Clear form
          form.reset();

          // Reset all-day checkbox
          const allDayCheckbox = document.getElementById('allDayCheckbox');
          if (allDayCheckbox) {
            allDayCheckbox.checked = false;
            // Trigger change event to update UI
            const changeEvent = new Event('change', { bubbles: true });
            allDayCheckbox.dispatchEvent(changeEvent);
          }

          // Reset step navigation if it exists
          if (window.stepManager && typeof window.stepManager.resetSteps === 'function') {
            window.stepManager.resetSteps();
          } else {
            // Fallback: manually reset to step 1
            const stepContents = document.querySelectorAll('.step-content');
            const steps = document.querySelectorAll('.step');

            // Hide all step contents and show step 1
            stepContents.forEach(content => {
              content.classList.add('d-none');
            });
            const step1 = document.querySelector('.step-content[data-step="1"]');
            if (step1) step1.classList.remove('d-none');

            // Update step indicators
            steps.forEach(step => {
              step.classList.remove('active');
            });
            const step1Indicator = document.querySelector('.step[data-step="1"]');
            if (step1Indicator) step1Indicator.classList.add('active');

            // Update navigation buttons
            const prevBtn = document.getElementById('prevStepBtn');
            const nextBtn = document.getElementById('nextStepBtn');
            const submitBtn = document.getElementById('submitReservationBtn');

            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) {
              nextBtn.classList.remove('d-none');
              nextBtn.disabled = true;
            }
            if (submitBtn) submitBtn.classList.add('d-none');
          }

          // Refresh calendar using CalendarModule
          if (window.calendarModule) {
            console.log('Refreshing calendar after reservation creation...');
            await window.calendarModule.loadCalendarEvents();
            if (typeof window.calendarModule.applyFilters === 'function') {
              window.calendarModule.applyFilters();
            }
            if (window.calendarModule.calendar) {
              try {
                window.calendarModule.calendar.refetchEvents();
                window.calendarModule.calendar.render();
                window.calendarModule.calendar.updateSize();
                console.log('Calendar refreshed successfully');
              } catch (error) {
                console.warn('Error refreshing calendar layout:', error);
              }
            }
          }

          // Refresh ongoing events app (Tab 1)
          if (window.ongoingEventsApp && typeof window.ongoingEventsApp.loadOngoingEvents === 'function') {
            console.log('Refreshing ongoing events...');
            const page = window.ongoingEventsApp.currentPage || 1;
            await window.ongoingEventsApp.loadOngoingEvents(page);
          }

          // Refresh calendar events app (Tab 3)
          if (window.calendarEventsApp && typeof window.calendarEventsApp.loadCalendarEvents === 'function') {
            console.log('Refreshing calendar events...');
            const page = window.calendarEventsApp.currentPage || 1;
            await window.calendarEventsApp.loadCalendarEvents(page);
          }

          // If the status is pending, refresh the pending tab (Tab 2)
          if (isPendingStatus) {
            console.log('Pending status detected, refreshing pending tab...');

            if (window.pendingRequestsApp && typeof window.pendingRequestsApp.loadPendingRequests === 'function') {
              const page = window.pendingRequestsApp.currentPage || 1;
              await window.pendingRequestsApp.loadPendingRequests(page);
            }

            // Update the pending count badge
            try {
              const response = await fetch('/api/admin/pending-requests-count', {
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
              });
              if (response.ok) {
                const result = await response.json();
                document.getElementById('pendingRequestsCount').textContent = result.count || 0;
              }
            } catch (error) {
              console.error('Error fetching pending count:', error);
            }
          }

          // Refresh mini calendar
          if (window.calendarModule && typeof window.calendarModule.loadMiniCalendar === 'function') {
            await window.calendarModule.loadMiniCalendar();
          }

          // Force a second calendar refresh
          setTimeout(() => {
            if (window.calendarModule && window.calendarModule.calendar) {
              try {
                window.calendarModule.calendar.refetchEvents();
                window.calendarModule.calendar.render();
                window.calendarModule.calendar.updateSize();
              } catch (error) {
                console.warn('Error in delayed calendar refresh:', error);
              }
            }
          }, 500);

        } else {
          // If we got a 200 OK but the response doesn't indicate success
          console.error('Unexpected success response format:', result);
          throw new Error(result.message || 'Reservation created but received unexpected response format');
        }

      } catch (error) {
        console.error('Error saving reservation:', error);

        // Format error message for display
        let errorMessage = error.message;
        if (errorMessage.includes('Validation failed:')) {
          errorMessage = errorMessage.replace('Validation failed:\n', 'Please fix the following:\n• ');
        }

        showToast(errorMessage || 'Failed to create reservation. Please try again.', 'error');
      } finally {
        // Restore button state
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
      }
    }

  </script>
@endsection