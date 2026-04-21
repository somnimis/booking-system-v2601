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
      border-radius: 0.25rem;
    }

    .refresh-btn:hover {
      color: var(--bs-dark);
      background-color: rgba(0, 0, 0, 0.05);
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

    @media (max-width: 768px) {
      body {
        font-size: 0.9rem;
      }

      h1, h2, h3, h4, h5, h6 {
        font-size: 0.9em;
      }

      .btn, .form-control, .form-select {
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

      .btn, .form-control, .form-select {
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

    @media (max-width: 992px) {
      .filter-select {
        min-width: 140px;
      }

      .input-group.input-group-sm {
        width: 180px;
      }
    }

    .dropdown-menu {
      min-width: 250px;
    }

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

    #adminDashboardTabs .nav-link:not(.active) i {
      color: #000 !important;
    }

    #adminDashboardTabs .nav-link.active i {
      color: #fff !important;
    }

    #adminDashboardTabs .badge {
      background-color: #135ba3 !important;
      color: white !important;
    }

    #adminDashboardTabs {
      color: #ffffff !important;
    }

    #allReservationsCount {
      display: none !important;
    }

    #refreshAllReservationsBtn i {
      color: #6c757d !important;
    }

    #refreshAllReservationsBtn:hover i {
      color: #135ba3 !important;
    }

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

    .filter-select {
      min-width: 150px;
      width: auto;
    }

    .input-group.input-group-sm {
      width: 200px;
    }

    .d-flex.align-items-center.gap-2 {
      flex-wrap: wrap;
    }

    #initialStatusSelect option {
      padding: 8px;
      margin: 2px 0;
      border-radius: 4px;
    }

    #initialStatusSelect option::before {
      content: "■";
      margin-right: 8px;
      font-size: 12px;
    }

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

    .late-checkbox:checked {
      background-color: #8f2a2aff;
      border-color: #8f2a2aff;
    }

    .late-checkbox:focus {
      box-shadow: 0 0 0 0.2rem #701a1a59;
    }

    .col-lg-3 .card:last-child .skeleton-container {
      max-height: none !important;
      min-height: 200px;
    }

    .loading .skeleton-container {
      display: block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

    .loading .calendar-content {
      display: none !important;
      visibility: hidden !important;
      opacity: 0 !important;
    }

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

    .spinner-border-sm {
      width: 1rem;
      height: 1rem;
      border-width: 0.2em;
    }

    @keyframes spinner-border {
      to {
        transform: rotate(360deg);
      }
    }

    .btn .spinner-border.spinner-border-sm {
      vertical-align: middle;
      margin-right: 0.25rem;
    }

    .text-muted .text-danger {
      color: #dc3545 !important;
    }

    .text-muted .text-warning {
      color: #ffc107 !important;
    }

    .fw-medium {
      font-weight: 500;
    }

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

    #adminReservationsList .card {
      cursor: pointer;
      border: 1px solid transparent;
      border-radius: 0.75rem;
    }

    #adminReservationsList .card:hover {
      background-color: #f8f9fa;
      border-color: #dee2e6;
      border-radius: 0.75rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
    }

    #adminReservationsList .card:hover .card-body {
      background-color: #f8f9fa;
      border-radius: 0.75rem;
    }

    #adminReservationsList .card-body {
      cursor: pointer;
      border-radius: 0.75rem;
    }

    .fc .fc-event {
      border: none;
      border-radius: 4px;
      padding: 2px 4px !important;
      font-size: 0.85rem !important;
      cursor: pointer;
    }

    .fc .fc-event:hover {
      filter: brightness(95%);
    }

    .fc-event.requisition-event {
      background-color: #4272b1ff;
      border-color: #4272b1ff;
      color: white;
      font-weight: 500;
    }

    .fc-event.calendar-event {
      background-color: #28a745;
      border-color: #28a745;
      color: white;
      font-weight: 500;
      background-image: linear-gradient(45deg, rgba(255, 255, 255, 0.15) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.15) 75%, transparent 75%, transparent);
      background-size: 10px 10px;
    }

    .fc-daygrid-event {
      border: none !important;
      border-radius: 4px !important;
      margin: 1px 2px !important;
      padding: 2px 4px !important;
      font-size: 0.8rem !important;
    }

    .fc-daygrid-event .fc-event-title {
      font-weight: 500;
      color: white;
      padding: 0 2px;
    }

    .fc-timegrid-event {
      border: none !important;
      border-radius: 4px !important;
      padding: 2px 4px !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .fc-timegrid-event .fc-event-title {
      font-weight: 500;
      font-size: 0.8rem;
      white-space: normal;
      line-height: 1.2;
    }

    .fc-timegrid-event .fc-event-time {
      font-weight: 400;
      font-size: 0.7rem;
      opacity: 0.9;
    }

    .fc-event[style*="background-color"] {
      color: white !important;
    }

    .fc-event:hover {
      filter: brightness(90%);
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
      transition: all 0.2s ease;
    }

    .fc-daygrid-day-events {
      min-height: 20px !important;
    }

    .fc-daygrid-event-harness {
      margin-bottom: 1px !important;
    }

    .fc-event-title {
      color: white !important;
      font-weight: 500;
    }

    .fc-daygrid-more-link {
      border-radius: 12px;
      background-color: #f0f0f0;
      color: #4a4a4a !important;
      padding: 2px 8px;
      font-size: 0.8rem;
      font-weight: 500;
      text-decoration: none !important;
      transition: all 0.2s ease;
    }

    .fc-daygrid-more-link:hover {
      background-color: #e0e0e0;
      color: #2d2d2d !important;
      text-decoration: none !important;
    }

    .calendar-day.has-events {
      font-weight: bold;
      color: #004183;
      position: relative;
    }

    .calendar-day.has-events::after {
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

    .calendar-day.today.has-events::after {
      background-color: white;
    }

    #miniCalendarDays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
    }

    #miniCalendarDays .calendar-day {
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: monospace;
      font-size: 0.75rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      min-height: 24px;
      max-height: 24px;
      height: 24px;
      width: 100%;
      box-sizing: border-box;
    }

    #miniCalendarDays .calendar-day:hover {
      background-color: #f0f0f0;
    }

    #miniCalendarDays .calendar-day.today {
      background-color: #366eaaff;
      color: white;
      font-weight: bold;
    }

    #facilityFilterList {
      max-height: 350px;
      overflow-y: auto;
      padding-right: 4px;
    }

    #facilityFilterList .category-group {
      margin-bottom: 0.75rem;
    }

    #facilityFilterList .category-header {
      margin-bottom: 0.25rem;
    }

    #facilityFilterList .subcategory-group {
      margin-left: 0.5rem;
      margin-bottom: 0.5rem;
    }

    #facilityFilterList .facility-checkbox {
      margin-left: 1rem;
    }

    #facilityFilterList .form-check-label {
      font-size: 0.85rem;
      cursor: pointer;
    }

    #facilityFilterList .facility-checkbox:checked+.form-check-label {
      font-weight: 500;
      color: #004183;
    }

    #facilityFilterList::-webkit-scrollbar {
      width: 4px;
    }

    #facilityFilterList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 2px;
    }

    #facilityFilterList::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 2px;
    }

    #facilityFilterList::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }
  </style>

  <main id="main">
    <div class="container-fluid">
      <div class="row g-3">

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
                        <div class="d-flex align-items-center mb-3 flex-wrap">

                          <div class="d-flex align-items-center gap-2 mb-2 ms-auto">
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
                                      @{{ event.requester.name }}
                                      <span class="text-muted"> • @{{ event.purpose || 'No purpose' }}</span>
                                      <span class="text-muted" v-if="event.requester.organization"> •
                                        @{{ event.requester.organization }}</span>
                                    </h6>
                                    <span class="badge" :style="{ backgroundColor: event.status.color }">
                                      @{{ event.status.name }}
                                    </span>
                                  </div>

                                  <p class="text-muted small mb-2">
                                    @{{ event.schedule.display }} • Duration: @{{ formatDuration(event) }}
                                  </p>

                                  <p class="mb-0 small" v-if="event.requested_items && event.requested_items.length > 0">
                                    <span v-for="(item, index) in displayItems(event.requested_items)" :key="index">
                                      @{{ item.name }}<span v-if="item.quantity > 1"> (×@{{ item.quantity }})</span>
                                    </span>
                                    <span class="text-muted" v-if="event.requested_items.length > 2"> •
                                      @{{ event.requested_items.length - 2 }} more...</span>
                                  </p>
                                  <p class="mb-0 small" v-else-if="event.participants">
                                    Participants: @{{ event.participants }}
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

                    <!-- Tab 2: Calendar Events -->
                    <div class="tab-pane fade" id="calendar-events" role="tabpanel" aria-labelledby="calendar-events-tab">
                      <div id="calendarEventsApp">
                        <!-- Events Header with Controls -->
                        <div class="d-flex align-items-center mb-3 flex-wrap">
                          <div class="d-flex align-items-center gap-2 mb-2 ms-auto">
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

        </div>
      </div>
    </div>
  </main>

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
  @include('admin.modals.add-reservation-modal')

@endsection

@section('scripts')
  <script defer src="{{ asset('js/public/calendar.js') }}"></script>

  <script>
    let adminToken = localStorage.getItem('adminToken');
    let currentRequestId = null;
    let originalCalendarTitle = '';
    let originalCalendarDescription = '';
    let currentReservationPage = 1;
    let reservationsPerPage = 10;
    let totalReservationPages = 1;
    let totalReservationCount = 0;
    let eventToDeleteId = null;
    let eventToDeleteName = null;
    let deleteEventModal = null;

    let currentFilters = {
      status: 'all',
      sort: 'newest',
      search: ''
    };

    const statusCheckboxes = document.querySelectorAll('.event-filter-checkbox');
    console.log('First checkbox:', statusCheckboxes[0]);
    console.log('First checkbox id:', statusCheckboxes[0]?.id);
    console.log('All checkboxes:', Array.from(statusCheckboxes).map(cb => cb.id));

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

      const bsToast = new bootstrap.Toast(toast, {
        autohide: false
      });
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

    let statusOptions = [];

    function setupFacilityFilterChangeListeners() {
      function updateCalendarFacilityFilters() {
        if (!window.calendarModule) return;
        window.calendarModule.applyFilters();
      }

      document.addEventListener('change', function (e) {
        if (e.target.matches('.individual-facility')) {
          const allCheckbox = document.getElementById('filterAllFacilities');
          if (allCheckbox) {
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

          updateCalendarFacilityFilters();
        }
      });

      const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');
      if (allFacilitiesCheckbox) {
        allFacilitiesCheckbox.addEventListener('change', function () {
          if (this.checked) {
            const individualCheckboxes = document.querySelectorAll('.individual-facility:not(:disabled)');
            individualCheckboxes.forEach(cb => {
              cb.checked = true;
            });
          }
          updateCalendarFacilityFilters();
        });
      }

      setTimeout(updateCalendarFacilityFilters, 1000);
    }

    async function fetchStatusOptionsForFilter() {
      try {
        const response = await fetch('/api/form-statuses', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const statusData = await response.json();
          statusOptions = statusData;

          const statusFilter = document.getElementById('statusFilter');
          if (statusFilter) {
            while (statusFilter.options.length > 1) {
              statusFilter.remove(1);
            }

            const excludedStatuses = [
              'Pending Approval',
              'Awaiting Payment',
              'Late',
              'Returned',
              'Late Return',
              'Completed',
              'Rejected',
              'Cancelled'
            ];

            statusData.forEach(status => {
              if (excludedStatuses.includes(status.status_name)) {
                return;
              }

              const option = document.createElement('option');
              option.value = status.status_id.toString();
              option.textContent = status.status_name;
              statusFilter.appendChild(option);
            });

            console.log('Status filter dropdown populated with:',
              Array.from(statusFilter.options).map(opt => opt.textContent).filter(t => t !== 'All Statuses'));
          }
        }
      } catch (error) {
        console.error('Error fetching status options for filter:', error);
      }
    }

    function logReservationStructure(reservations) {
      if (reservations.length > 0) {
        console.log('Sample reservation structure:', reservations[0]);
        console.log('Status structure:', reservations[0].form_details?.status);
        console.log('Status options:', statusOptions);
      }
    }

    async function loadExtraServices() {
      try {
        const response = await fetch('/api/extra-services', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const servicesData = await response.json();
          const servicesArray = servicesData.data || servicesData;

          const container = document.getElementById('extraServicesContainer');
          if (!container) return;

          if (servicesArray.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-muted py-2">No extra services available</div>';
            return;
          }

          container.innerHTML = '';

          servicesArray.forEach(service => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-lg-4 col-md-6 col-12';

            const formCheckDiv = document.createElement('div');
            formCheckDiv.className = 'form-check';

            const checkbox = document.createElement('input');
            checkbox.className = 'form-check-input service-checkbox';
            checkbox.type = 'checkbox';
            checkbox.id = `service_${service.service_id}`;
            checkbox.value = service.service_id;
            checkbox.setAttribute('data-name', service.service_name);

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = `service_${service.service_id}`;
            label.textContent = service.service_name;

            formCheckDiv.appendChild(checkbox);
            formCheckDiv.appendChild(label);
            colDiv.appendChild(formCheckDiv);
            container.appendChild(colDiv);
          });

          const selectAllDiv = document.createElement('div');
          selectAllDiv.className = 'col-12 mt-2';
          selectAllDiv.innerHTML = `
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="selectAllServices">
              <label class="form-check-label fw-medium" for="selectAllServices">
                Select All Services
              </label>
            </div>
          `;
          container.appendChild(selectAllDiv);

          const selectAllCheckbox = document.getElementById('selectAllServices');
          if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
              const checkboxes = document.querySelectorAll('.service-checkbox');
              checkboxes.forEach(cb => {
                cb.checked = this.checked;
              });

              if (typeof validateCurrentStep === 'function') {
                validateCurrentStep();
              }
            });
          }

          document.querySelectorAll('.service-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
              const selectAll = document.getElementById('selectAllServices');
              if (selectAll) {
                const totalCheckboxes = document.querySelectorAll('.service-checkbox').length;
                const checkedCheckboxes = document.querySelectorAll('.service-checkbox:checked').length;

                if (checkedCheckboxes === totalCheckboxes) {
                  selectAll.checked = true;
                  selectAll.indeterminate = false;
                } else if (checkedCheckboxes === 0) {
                  selectAll.checked = false;
                  selectAll.indeterminate = false;
                } else {
                  selectAll.checked = false;
                  selectAll.indeterminate = true;
                }
              }

              if (typeof validateCurrentStep === 'function') {
                validateCurrentStep();
              }
            });
          });

        } else {
          console.error('Failed to load extra services:', response.status);
          const container = document.getElementById('extraServicesContainer');
          if (container) {
            container.innerHTML = '<div class="col-12 text-center text-danger py-2">Failed to load extra services</div>';
          }
        }
      } catch (error) {
        console.error('Error loading extra services:', error);
        const container = document.getElementById('extraServicesContainer');
        if (container) {
          container.innerHTML = '<div class="col-12 text-center text-danger py-2">Error loading extra services</div>';
        }
      }
    }

    async function loadFacilitiesForFilter() {
      try {
        const facilityFilterList = document.getElementById('facilityFilterList');
        if (!facilityFilterList) return;

        facilityFilterList.innerHTML = `
          <div class="text-center py-3 text-muted">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading facilities...
          </div>
        `;

        const response = await fetch('/api/facilities', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
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

          let html = '';

          html += `
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

          const allFacilitiesCheckbox = document.getElementById('filterAllFacilities');
          if (allFacilitiesCheckbox) {
            allFacilitiesCheckbox.addEventListener('change', function () {
              const individualCheckboxes = document.querySelectorAll('.individual-facility:not(:disabled)');
              individualCheckboxes.forEach(cb => {
                cb.checked = this.checked;
              });

              if (window.calendarModule) {
                window.calendarModule.loadCalendarEvents();
              }
            });
          }

          document.querySelectorAll('.individual-facility').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
              updateAllFacilitiesCheckbox();

              if (window.calendarModule) {
                window.calendarModule.loadCalendarEvents();
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

    async function loadStatusOptions() {
      try {
        const response = await fetch('/api/form-statuses', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const statuses = await response.json();
          const statusSelect = document.getElementById('initialStatusSelect');

          if (statusSelect) {
            statusSelect.innerHTML = '<option value="" disabled selected>Select initial status</option>';

            const excludedStatuses = ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'];
            const availableStatuses = statuses.filter(status =>
              !excludedStatuses.includes(status.status_name)
            );

            availableStatuses.forEach(status => {
              const option = document.createElement('option');
              option.value = status.status_id;
              option.textContent = status.status_name;
              option.style.color = status.color_code;
              statusSelect.appendChild(option);
            });

            const scheduledOption = Array.from(statusSelect.options).find(opt =>
              opt.textContent === 'Scheduled'
            );
            if (scheduledOption) {
              scheduledOption.selected = true;
            }

            console.log('Loaded status options:', availableStatuses.length);
          }
        } else {
          console.warn('Failed to load status options');
          createFallbackStatusOptions();
        }
      } catch (error) {
        console.error('Error loading status options:', error);
        createFallbackStatusOptions();
      }
    }

    function createFallbackStatusOptions() {
      const statusSelect = document.getElementById('initialStatusSelect');
      if (!statusSelect) return;

      statusSelect.innerHTML = '<option value="" disabled selected>Select initial status</option>';
      
      const fallbackStatuses = [
        { status_id: 1, status_name: 'Scheduled' },
        { status_id: 2, status_name: 'Pending Approval' },
        { status_id: 3, status_name: 'Awaiting Payment' },
        { status_id: 4, status_name: 'Ongoing' }
      ];

      fallbackStatuses.forEach(status => {
        const option = document.createElement('option');
        option.value = status.status_id;
        option.textContent = status.status_name;
        statusSelect.appendChild(option);
      });
    }

    document.addEventListener('DOMContentLoaded', async function () {
      const desktopSelect = document.getElementById('reservationsPerPageSelect');
      const mobileSelect = document.getElementById('reservationsPerPageSelectMobile');
      const mobileRefreshBtn = document.getElementById('mobileRefreshBtn');
      const desktopRefreshBtn = document.getElementById('refreshAllReservationsBtn');

      if (desktopSelect && mobileSelect) {
        mobileSelect.value = desktopSelect.value;

        desktopSelect.addEventListener('change', function () {
          mobileSelect.value = this.value;
          reservationsPerPage = parseInt(this.value);
          currentReservationPage = 1;
          loadAdminReservations(1);
        });

        mobileSelect.addEventListener('change', function () {
          desktopSelect.value = this.value;
          reservationsPerPage = parseInt(this.value);
          currentReservationPage = 1;
          loadAdminReservations(1);
        });
      }

      if (mobileRefreshBtn && desktopRefreshBtn) {
        mobileRefreshBtn.addEventListener('click', function () {
          desktopRefreshBtn.click();
        });
      }

      const token = localStorage.getItem('adminToken');
      if (!token) {
        window.location.href = "/admin/login";
        return;
      }

      document.body.classList.add('loading');

      try {
        setTimeout(() => {
          Promise.all([
            loadFacilitiesForFilter().catch(console.error)
          ]);
        }, 50);

        const modalElement = document.getElementById('addReservationModal');
        if (modalElement) {
          const addReservationModal = new bootstrap.Modal(modalElement);

          modalElement.addEventListener('show.bs.modal', async function () {
            console.log('Modal opened, initializing...');

            const allDayCheckbox = document.getElementById('allDayCheckbox');
            const startTimeSelect = document.getElementById('startTime');
            const endTimeSelect = document.getElementById('endTime');
            const allDayIndicator = document.getElementById('allDayScheduleIndicator');

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

                  if (typeof calculateDuration === 'function') {
                    calculateDuration();
                  }
                } else {
                  startTimeSelect.disabled = false;
                  endTimeSelect.disabled = false;

                  if (startTimeSelect.dataset.originalValue) {
                    startTimeSelect.value = startTimeSelect.dataset.originalValue;
                  } else {
                    startTimeSelect.value = '09:00';
                  }

                  if (endTimeSelect.dataset.originalValue) {
                    endTimeSelect.value = endTimeSelect.dataset.originalValue;
                  } else {
                    endTimeSelect.value = '17:00';
                  }

                  if (allDayIndicator) allDayIndicator.style.display = 'none';

                  startTimeSelect.classList.remove('bg-light');
                  endTimeSelect.classList.remove('bg-light');

                  if (typeof calculateDuration === 'function') {
                    calculateDuration();
                  }
                }

                if (typeof validateCurrentStep === 'function') {
                  validateCurrentStep();
                }
              });
            }

            initializeUserTypeToggle();

            await Promise.all([
              loadPurposes().catch(console.error),
              loadFacilitiesForReservation().catch(console.error),
              loadEquipmentForReservation().catch(console.error),
              loadExtraServices().catch(console.error),
              loadStatusOptions().catch(console.error)
            ]);

            const now = new Date();
            const tomorrow = new Date(now);

            populateTimeDropdowns();
            calculateDuration();

            tomorrow.setDate(tomorrow.getDate() + 1);

            document.getElementById('startDate').value = now.toISOString().split('T')[0];
            document.getElementById('endDate').value = tomorrow.toISOString().split('T')[0];

            populateTimeDropdowns();
            calculateDuration();

            document.getElementById('startDate').addEventListener('change', calculateDuration);
            document.getElementById('startTime').addEventListener('change', calculateDuration);
            document.getElementById('endDate').addEventListener('change', calculateDuration);
            document.getElementById('endTime').addEventListener('change', calculateDuration);

            setTimeout(() => {
              setupCharacterCounters();
            }, 200);

            if (typeof stepManager !== 'undefined' && stepManager.resetSteps) {
              stepManager.resetSteps();
            } else {
              window.stepManager = setupReservationStepNavigation();
            }

            console.log('Modal initialization complete');
          });
        }

        document.getElementById('submitReservationBtn')?.addEventListener('click', saveReservation);

        document.getElementById('generateAccessCode')?.addEventListener('click', generateAccessCode);

        document.getElementById('copyAccessCode')?.addEventListener('click', function () {
          const codeInput = document.getElementById('accessCodeInput');
          codeInput.select();
          document.execCommand('copy');
          const originalText = this.innerHTML;
          this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
          setTimeout(() => {
            this.innerHTML = originalText;
          }, 2000);
        });

        document.getElementById('refreshCalendarEventsBtn')?.addEventListener('click', refreshCalendarEvents);

        const addCalendarEventForm = document.getElementById('addCalendarEventForm');
        if (addCalendarEventForm) {
          const newForm = addCalendarEventForm.cloneNode(true);
          addCalendarEventForm.parentNode.replaceChild(newForm, addCalendarEventForm);

          newForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const allDayCheckbox = document.getElementById('calendarAllDayField');
            const isAllDay = allDayCheckbox ? allDayCheckbox.checked : false;

            const formData = new FormData(this);

            const eventName = formData.get('event_name');
            const eventType = formData.get('event_type');

            if (!eventName || eventName.trim() === '') {
              showToast('Event name is required', 'error');
              return false;
            }

            if (!eventType) {
              showToast('Event type is required', 'error');
              return false;
            }

            const startDate = formData.get('start_date');
            const endDate = formData.get('end_date');

            if (!startDate) {
              showToast('Start date is required', 'error');
              return false;
            }

            if (!endDate) {
              showToast('End date is required', 'error');
              return false;
            }

            if (new Date(endDate) < new Date(startDate)) {
              showToast('End date must be on or after start date', 'error');
              return false;
            }

            const eventData = {
              event_name: eventName.trim(),
              event_type: eventType,
              description: formData.get('description') || '',
              start_date: startDate,
              end_date: endDate,
              all_day: isAllDay ? true : false
            };

            let startTime = formData.get('start_time');
            let endTime = formData.get('end_time');

            if (isAllDay) {
              startTime = '00:00';
              endTime = '23:59';
            }

            if (!startTime) {
              showToast('Start time is required', 'error');
              return false;
            }

            if (!endTime) {
              showToast('End time is required', 'error');
              return false;
            }

            const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timeRegex.test(startTime)) {
              showToast('Start time must be in HH:MM format (24-hour)', 'error');
              return false;
            }
            if (!timeRegex.test(endTime)) {
              showToast('End time must be in HH:MM format (24-hour)', 'error');
              return false;
            }

            if (!isAllDay) {
              const startDateTime = new Date(`${startDate}T${startTime}`);
              const endDateTime = new Date(`${endDate}T${endTime}`);

              if (endDateTime <= startDateTime) {
                showToast('End date/time must be after start date/time', 'error');
                return false;
              }
            }

            eventData.start_time = startTime;
            eventData.end_time = endTime;

            try {
              await addCalendarEvent(eventData);
            } catch (error) {
              console.error('Error in form submission:', error);
            }

            return false;
          });
        }

        const addCalendarEventModal = document.getElementById('addCalendarEventModal');
        if (addCalendarEventModal) {
          addCalendarEventModal.addEventListener('show.bs.modal', function () {
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];

            const startDateInput = this.querySelector('input[name="start_date"]');
            const endDateInput = this.querySelector('input[name="end_date"]');
            const startTimeInput = this.querySelector('input[name="start_time"]');
            const endTimeInput = this.querySelector('input[name="end_time"]');
            const allDayCheckbox = this.querySelector('#calendarAllDayField');
            const allDayIndicator = this.querySelector('#allDayIndicator');
            const eventTypeSelect = this.querySelector('select[name="event_type"]');

            if (startDateInput) startDateInput.value = today;
            if (endDateInput) endDateInput.value = tomorrowStr;

            if (startTimeInput) {
              startTimeInput.value = '09:00';
              startTimeInput.disabled = false;
              startTimeInput.required = true;
            }
            if (endTimeInput) {
              endTimeInput.value = '17:00';
              endTimeInput.disabled = false;
              endTimeInput.required = true;
            }

            if (allDayCheckbox) {
              allDayCheckbox.checked = false;
              allDayCheckbox.disabled = false;
            }

            if (allDayIndicator) allDayIndicator.style.display = 'none';

            if (eventTypeSelect) eventTypeSelect.value = '';
          });
        }

        const allDayCheckbox = addCalendarEventModal.querySelector('#calendarAllDayField');
        if (allDayCheckbox) {
          const newCheckbox = allDayCheckbox.cloneNode(true);
          allDayCheckbox.parentNode.replaceChild(newCheckbox, allDayCheckbox);

          newCheckbox.addEventListener('change', function () {
            const modal = this.closest('.modal');
            const startTimeInput = modal.querySelector('input[name="start_time"]');
            const endTimeInput = modal.querySelector('input[name="end_time"]');
            const allDayIndicator = modal.querySelector('#allDayIndicator');
            const timeHelpers = modal.querySelectorAll('.time-helper');

            if (this.checked) {
              if (startTimeInput) {
                startTimeInput.value = '00:00';
                startTimeInput.disabled = true;
                startTimeInput.required = true;
              }
              if (endTimeInput) {
                endTimeInput.value = '23:59';
                endTimeInput.disabled = true;
                endTimeInput.required = true;
              }
              if (allDayIndicator) {
                allDayIndicator.style.display = 'block';
              }
              timeHelpers.forEach(helper => {
                helper.textContent = 'Automatically set to 00:00 - 23:59 for all-day events';
              });
            } else {
              if (startTimeInput) {
                startTimeInput.disabled = false;
                startTimeInput.value = '09:00';
                startTimeInput.required = true;
              }
              if (endTimeInput) {
                endTimeInput.disabled = false;
                endTimeInput.value = '17:00';
                endTimeInput.required = true;
              }
              if (allDayIndicator) {
                allDayIndicator.style.display = 'none';
              }
              timeHelpers.forEach(helper => {
                helper.textContent = 'Required for timed events';
              });
            }
          });
        }

        document.getElementById('confirmDeleteEventBtn')?.addEventListener('click', handleDeleteCalendarEvent);

        document.body.classList.remove('loading');

        const ongoingEventsApp = new Vue({
          el: '#ongoingEventsApp',
          data: {
            ongoingEvents: [],
            loading: false,
            currentPage: 1,
            totalPages: 1,
            totalItems: 0,
            perPage: 10,
            filters: {
              status: '',
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
                  headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                  }
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

            async refreshOngoingEvents() {
              await this.loadOngoingEvents(this.currentPage);
            },

            formatDuration(event) {
              return event.schedule?.duration || 'N/A';
            },

            displayItems(items) {
              if (!items || items.length === 0) return [];
              return items.slice(0, 2);
            },

            changePage(page) {
              if (page >= 1 && page <= this.totalPages) {
                this.loadOngoingEvents(page);
              }
            },

            goToEvent(requestId) {
              window.location.href = `/admin/requisition/${requestId}`;
            }
          },
          mounted() {
            this.loadOngoingEvents(1);
          }
        });

      } catch (error) {
        console.error('Initialization error:', error);
        showToast('Failed to initialize page. Please refresh.', 'error');
        document.body.classList.remove('loading');
      }

      const calendarEventsApp = new Vue({
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
                showToast('Failed to load calendar events', 'error');
              }
            } catch (error) {
              console.error('Error loading calendar events:', error);
              this.calendarEvents = [];
              showToast('Error loading calendar events', 'error');
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
                showToast('Calendar event deleted successfully!', 'success');
                await this.loadCalendarEvents(this.currentPage);
              } else {
                const result = await response.json();
                throw new Error(result.message || 'Failed to delete event');
              }
            } catch (error) {
              console.error('Error deleting calendar event:', error);
              showToast(error.message || 'Failed to delete event', 'error');
            }
          },

          changePage(page) {
            if (page >= 1 && page <= this.totalPages) {
              this.loadCalendarEvents(page);
            }
          }
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

      window.ongoingEventsApp = ongoingEventsApp;
      window.calendarEventsApp = calendarEventsApp;
    });

    let miniCalendarInitialized = false;

    function setupCharacterCounters() {
      const firstNameInput = document.querySelector('input[name="first_name"]');
      updateCharacterCounter(firstNameInput, 50, 'firstNameCounter');

      const lastNameInput = document.querySelector('input[name="last_name"]');
      updateCharacterCounter(lastNameInput, 50, 'lastNameCounter');

      const emailInput = document.querySelector('input[name="email"]');
      updateCharacterCounter(emailInput, 100, 'emailCounter');

      const contactNumberInput = document.querySelector('input[name="contact_number"]');
      updateCharacterCounter(contactNumberInput, 15, 'contactCounter');

      const organizationInput = document.getElementById('organizationInput');
      updateCharacterCounter(organizationInput, 100, 'organizationCounter');

      const schoolIdInput = document.getElementById('schoolIdInput');
      updateCharacterCounter(schoolIdInput, 20, 'schoolIdCounter');

      const endorserInput = document.querySelector('input[name="endorser"]');
      updateCharacterCounter(endorserInput, 50, 'endorserCounter');

      const additionalRequestsTextarea = document.querySelector('textarea[name="additional_requests"]');
      const additionalRequestsCounter = document.getElementById('additionalRequestsCounter');

      if (additionalRequestsTextarea && additionalRequestsCounter) {
        const updateAdditionalRequestsCounter = () => {
          const length = additionalRequestsTextarea.value.length;
          const maxLength = 250;
          additionalRequestsCounter.textContent = `${length}/${maxLength} characters`;

          const percentage = (length / maxLength) * 100;
          additionalRequestsCounter.className = '';

          if (percentage >= 90) {
            additionalRequestsCounter.classList.add('text-danger', 'fw-bold');
          } else if (percentage >= 80) {
            additionalRequestsCounter.classList.add('text-warning', 'fw-medium');
          } else {
            additionalRequestsCounter.classList.add('text-muted');
          }
        };

        additionalRequestsTextarea.addEventListener('input', updateAdditionalRequestsCounter);
        additionalRequestsTextarea.addEventListener('change', updateAdditionalRequestsCounter);
        updateAdditionalRequestsCounter();
      }

      const calendarTitleInput = document.querySelector('input[name="calendar_title"]');
      const calendarTitleCounter = document.getElementById('calendarTitleCounter');

      if (calendarTitleInput && calendarTitleCounter) {
        const updateCalendarTitleCounter = () => {
          const length = calendarTitleInput.value.length;
          const maxLength = 50;
          calendarTitleCounter.textContent = `${length}/${maxLength} characters`;

          const percentage = (length / maxLength) * 100;
          calendarTitleCounter.className = '';

          if (percentage >= 90) {
            calendarTitleCounter.classList.add('text-danger', 'fw-bold');
          } else if (percentage >= 80) {
            calendarTitleCounter.classList.add('text-warning', 'fw-medium');
          } else {
            calendarTitleCounter.classList.add('text-muted');
          }
        };

        calendarTitleInput.addEventListener('input', updateCalendarTitleCounter);
        calendarTitleInput.addEventListener('change', updateCalendarTitleCounter);
        updateCalendarTitleCounter();
      }

      const calendarDescriptionTextarea = document.querySelector('textarea[name="calendar_description"]');
      const calendarDescriptionCounter = document.getElementById('calendarDescriptionCounter');

      if (calendarDescriptionTextarea && calendarDescriptionCounter) {
        const updateCalendarDescriptionCounter = () => {
          const length = calendarDescriptionTextarea.value.length;
          const maxLength = 100;
          calendarDescriptionCounter.textContent = `${length}/${maxLength} characters`;

          const percentage = (length / maxLength) * 100;
          calendarDescriptionCounter.className = '';

          if (percentage >= 90) {
            calendarDescriptionCounter.classList.add('text-danger', 'fw-bold');
          } else if (percentage >= 80) {
            calendarDescriptionCounter.classList.add('text-warning', 'fw-medium');
          } else {
            calendarDescriptionCounter.classList.add('text-muted');
          }
        };

        calendarDescriptionTextarea.addEventListener('input', updateCalendarDescriptionCounter);
        calendarDescriptionTextarea.addEventListener('change', updateCalendarDescriptionCounter);
        updateCalendarDescriptionCounter();
      }
    }

    function initializeUserTypeToggle() {
      const userTypeSelect = document.querySelector('select[name="user_type"]');
      const schoolIdInput = document.getElementById('schoolIdInput');
      const organizationInput = document.getElementById('organizationInput');

      if (!userTypeSelect || !schoolIdInput || !organizationInput) {
        console.error('Could not find required elements for user type toggle');
        return;
      }

      console.log('Initializing user type toggle...');

      const handleUserTypeChange = function () {
        console.log('User type changed to:', this.value);

        if (this.value === 'Internal') {
          schoolIdInput.disabled = false;
          schoolIdInput.setAttribute('required', 'required');
          schoolIdInput.placeholder = "e.g., 2015-12345";
          schoolIdInput.classList.remove('bg-light');

          organizationInput.disabled = false;
          organizationInput.removeAttribute('required');
          organizationInput.placeholder = "Organization (optional)";
          organizationInput.classList.remove('bg-light');

          console.log('Internal user: School ID required, Organization optional');

        } else if (this.value === 'External') {
          schoolIdInput.disabled = true;
          schoolIdInput.removeAttribute('required');
          schoolIdInput.value = '';
          schoolIdInput.placeholder = "For internal users only";
          schoolIdInput.classList.add('bg-light');

          organizationInput.disabled = false;
          organizationInput.removeAttribute('required');
          organizationInput.placeholder = "Organization (optional)";
          organizationInput.classList.remove('bg-light');

          console.log('External user: School ID disabled, Organization optional');

        } else {
          schoolIdInput.disabled = true;
          schoolIdInput.removeAttribute('required');
          organizationInput.disabled = false;
          organizationInput.removeAttribute('required');
        }

        updateCharacterCounter(schoolIdInput, 20, 'schoolIdCounter');
        updateCharacterCounter(organizationInput, 100, 'organizationCounter');

        if (typeof validateCurrentStep === 'function') {
          console.log('Triggering validation for step 1');
          validateCurrentStep();
        }
      };

      userTypeSelect.addEventListener('change', handleUserTypeChange);

      console.log('Setting initial user type state, current value:', userTypeSelect.value);

      if (!userTypeSelect.value || userTypeSelect.value === '') {
        userTypeSelect.value = 'External';
      }

      setTimeout(() => {
        userTypeSelect.dispatchEvent(new Event('change'));
      }, 100);

      return userTypeSelect;
    }

    async function loadPurposes() {
      try {
        console.log('Fetching purposes from API...');

        const response = await fetch('/api/requisition-purposes', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        console.log('API Response status:', response.status);

        if (!response.ok) {
          throw new Error(`API returned ${response.status}: ${response.statusText}`);
        }

        const purposes = await response.json();
        console.log('Purposes API response:', purposes);

        const select = document.getElementById('purposeSelect');
        if (!select) {
          console.error('Could not find purposeSelect element!');
          throw new Error('Purpose select element not found');
        }

        select.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = 'Select purpose of reservation';
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        select.appendChild(placeholderOption);

        purposes.forEach(purpose => {
          const purposeId = purpose.purpose_id;
          const purposeName = purpose.purpose_name;

          if (!purposeId) {
            console.warn('Skipping purpose with no ID:', purpose);
            return;
          }

          const option = document.createElement('option');
          option.value = purposeId.toString();
          option.textContent = purposeName;
          option.setAttribute('data-id', purposeId);
          option.setAttribute('data-name', purposeName);
          select.appendChild(option);

          console.log('Added purpose option:', {
            id: purposeId,
            name: purposeName,
            value: option.value
          });
        });

        console.log(`Successfully loaded ${purposes.length} purposes`);

        if (purposes.length > 0) {
          setTimeout(() => {
            select.selectedIndex = 1;
            const selectedId = select.value;
            const selectedName = select.options[select.selectedIndex].text;
            console.log('Auto-selected first purpose:', {
              id: selectedId,
              name: selectedName
            });

            if (typeof validateCurrentStep === 'function') {
              validateCurrentStep();
            }
          }, 100);
        }

      } catch (error) {
        console.error('Error in loadPurposes:', error);
        createFallbackPurposes();
        showToast('Failed to load purpose options. Using fallback.', 'error');
      }
    }

    function createFallbackPurposes() {
      console.log('Creating fallback purposes...');

      const select = document.getElementById('purposeSelect');
      if (!select) return;

      select.innerHTML = '';

      const placeholderOption = document.createElement('option');
      placeholderOption.value = '';
      placeholderOption.textContent = 'Select purpose of reservation';
      placeholderOption.disabled = true;
      placeholderOption.selected = true;
      select.appendChild(placeholderOption);

      const fallbackPurposes = [
        { purpose_id: 1, purpose_name: 'Facility Rental' },
        { purpose_id: 2, purpose_name: 'Equipment Rental' },
        { purpose_id: 3, purpose_name: 'Class/Seminar/Conference' },
        { purpose_id: 4, purpose_name: 'University Program/Activity' },
        { purpose_id: 5, purpose_name: 'CPU Organization Led Activity' },
        { purpose_id: 6, purpose_name: 'Student-Organized Activity' },
        { purpose_id: 7, purpose_name: 'Alumni-Organized Activity' },
        { purpose_id: 8, purpose_name: 'Alumni - Class Reunion' },
        { purpose_id: 9, purpose_name: 'Alumni - Personal Events' },
        { purpose_id: 10, purpose_name: 'External Event' }
      ];

      fallbackPurposes.forEach(purpose => {
        const option = document.createElement('option');
        option.value = purpose.purpose_id.toString();
        option.textContent = purpose.purpose_name;
        select.appendChild(option);
      });

      console.log(`Created ${fallbackPurposes.length} fallback purposes`);
    }

    async function loadFacilitiesForReservation() {
      try {
        const response = await fetch('/api/facilities', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const facilitiesData = await response.json();
          const facilitiesList = document.getElementById('facilitiesList');
          facilitiesList.innerHTML = '';

          const facilitiesArray = facilitiesData.data || facilitiesData;

          facilitiesArray.forEach(facility => {
            const isAvailable = facility.status_id === 1 || facility.status?.status_id === 1;

            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            div.innerHTML = `
              <input class="form-check-input facility-checkbox" type="checkbox" 
                     id="facility_${facility.facility_id}" 
                     value="${facility.facility_id}"
                     data-name="${facility.facility_name}"
                     data-fee="${facility.base_fee}"
                     data-rate-type="${facility.rate_type}"
                     data-capacity="${facility.capacity}"
                     ${!isAvailable ? 'disabled' : ''}>
              <label class="form-check-label ${!isAvailable ? 'text-muted' : ''}" 
                     for="facility_${facility.facility_id}">
                ${facility.facility_name} 
                (₱${facility.base_fee}${facility.rate_type === 'Per Hour' ? '/hour' : '/event'})
                <br>
                <small class="text-muted">Capacity: ${facility.capacity} people</small>
                ${!isAvailable ? '<span class="badge bg-warning ms-2">Unavailable</span>' : ''}
              </label>
            `;
            facilitiesList.appendChild(div);
          });

          const selectAllCheckbox = document.getElementById('selectAllFacilities');
          if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
              const checkboxes = document.querySelectorAll('.facility-checkbox:not(:disabled)');
              checkboxes.forEach(cb => {
                cb.checked = this.checked;
              });
            });
          }
        } else {
          console.error('Failed to load facilities:', response.status);
        }
      } catch (error) {
        console.error('Error loading facilities:', error);
        showToast('Failed to load facility options', 'error');
      }
    }

    async function loadEquipmentForReservation() {
      try {
        const response = await fetch('/api/equipment', {
          headers: {
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json'
          }
        });

        if (response.ok) {
          const equipmentData = await response.json();
          const equipmentList = document.getElementById('equipmentList');
          equipmentList.innerHTML = '';

          const equipmentArray = equipmentData.data || equipmentData;

          equipmentArray.forEach(equipment => {
            const isAvailable = equipment.status_id === 1 || equipment.status?.status_id === 1;

            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            div.innerHTML = `
              <input class="form-check-input equipment-checkbox" type="checkbox" 
                     id="equipment_${equipment.equipment_id}" 
                     value="${equipment.equipment_id}"
                     data-name="${equipment.equipment_name}"
                     data-fee="${equipment.base_fee}"
                     data-rate-type="${equipment.rate_type}"
                     ${!isAvailable ? 'disabled' : ''}>
              <label class="form-check-label ${!isAvailable ? 'text-muted' : ''}" 
                     for="equipment_${equipment.equipment_id}">
                ${equipment.equipment_name} 
                (₱${equipment.base_fee}${equipment.rate_type === 'Per Hour' ? '/hour' : '/event'})
                ${!isAvailable ? '<span class="badge bg-warning ms-2">Unavailable</span>' : ''}
              </label>
            `;
            equipmentList.appendChild(div);
          });
        } else {
          console.error('Failed to load equipment:', response.status);
        }
      } catch (error) {
        console.error('Error loading equipment:', error);
        showToast('Failed to load equipment options', 'error');
      }
    }

    function setupReservationEventListeners() {
      const generateAccessCodeBtn = document.getElementById('generateAccessCode');
      if (generateAccessCodeBtn) {
        generateAccessCodeBtn.addEventListener('click', generateAccessCode);
      }

      const selectAllFacilitiesBtn = document.getElementById('selectAllFacilities');
      if (selectAllFacilitiesBtn) {
        selectAllFacilitiesBtn.addEventListener('change', function () {
          const checkboxes = document.querySelectorAll('.facility-checkbox');
          checkboxes.forEach(cb => cb.checked = this.checked);
        });
      }

      document.addEventListener('click', function (e) {
        if (e.target.closest('.increment')) {
          const button = e.target.closest('.increment');
          const input = button.parentElement.querySelector('.equipment-quantity');
          const max = parseInt(input.getAttribute('max'));
          if (parseInt(input.value) < max) {
            input.value = parseInt(input.value) + 1;
          }
        }

        if (e.target.closest('.decrement')) {
          const button = e.target.closest('.decrement');
          const input = button.parentElement.querySelector('.equipment-quantity');
          if (parseInt(input.value) > 0) {
            input.value = parseInt(input.value) - 1;
          }
        }
      });

      const saveReservationBtn = document.getElementById('saveReservation');
      if (saveReservationBtn) {
        saveReservationBtn.addEventListener('click', saveReservation);
      }
    }

    async function saveReservation() {
      const form = document.getElementById('addReservationForm');
      const confirmBtn = document.getElementById('submitReservationBtn');
      const statusSelect = document.getElementById('initialStatusSelect');
      const statusId = statusSelect ? parseInt(statusSelect.value) : 1;

      const selectedStatusOption = statusSelect.options[statusSelect.selectedIndex];
      const selectedStatusText = selectedStatusOption ? selectedStatusOption.text : '';
      const isPendingStatus = selectedStatusText === 'Pending Approval' || selectedStatusText === 'Awaiting Payment';

      const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

      const originalText = confirmBtn.innerHTML;
      confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
      confirmBtn.disabled = true;

      try {
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

        const purposeSelect = document.getElementById('purposeSelect');
        const purposeValue = purposeSelect?.value;
        const purposeId = purposeValue ? parseInt(purposeValue) : null;

        const numParticipants = parseInt(document.querySelector('input[name="num_participants"]')?.value || 1);
        const numTables = parseInt(document.querySelector('input[name="num_tables"]')?.value || 0);
        const numChairs = parseInt(document.querySelector('input[name="num_chairs"]')?.value || 0);

        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        let startTime = document.getElementById('startTime')?.value;
        let endTime = document.getElementById('endTime')?.value;

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

        const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked'))
          .map(cb => ({
            service_id: parseInt(cb.value),
            name: cb.dataset.name
          }));

        const numMicrophones = parseInt(document.querySelector('input[name="num_microphones"]')?.value || 0);

        const validationErrors = [];

        if (firstName.length > 50) validationErrors.push('First name must be 50 characters or less');
        if (!firstName) validationErrors.push('First name is required');
        if (lastName.length > 50) validationErrors.push('Last name must be 50 characters or less');
        if (!lastName) validationErrors.push('Last name is required');
        if (email.length > 100) validationErrors.push('Email must be 100 characters or less');
        if (!email) validationErrors.push('Email is required');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) validationErrors.push('Email format is invalid');
        if (contactNumber && contactNumber.length > 15) validationErrors.push('Contact number must be 15 characters or less');
        if (organizationName && organizationName.length > 100) validationErrors.push('Organization name must be 100 characters or less');
        if (schoolId && schoolId.length > 20) validationErrors.push('School ID must be 20 characters or less');
        if (endorser && endorser.length > 50) validationErrors.push('Endorser name must be 50 characters or less');
        if (additionalRequests && additionalRequests.length > 250) validationErrors.push('Additional requests must be 250 characters or less');
        if (calendarTitle && calendarTitle.length > 50) validationErrors.push('Calendar title must be 50 characters or less');
        if (calendarDescription && calendarDescription.length > 100) validationErrors.push('Calendar description must be 100 characters or less');
        if (accessCode.length !== 10) validationErrors.push('Access code must be exactly 10 characters');
        if (!accessCode) validationErrors.push('Access code is required');
        if (numParticipants < 1 || numParticipants > 500) validationErrors.push('Number of participants must be between 1 and 500');
        if (numTables < 0 || numTables > 100) validationErrors.push('Number of tables must be between 0 and 100');
        if (numChairs < 0 || numChairs > 500) validationErrors.push('Number of chairs must be between 0 and 500');
        if (!purposeId || isNaN(purposeId) || purposeId <= 0) validationErrors.push('Please select a valid purpose');
        if (!startDate || !endDate) validationErrors.push('Start and end dates are required');
        if (selectedFacilities.length === 0 && selectedEquipment.length === 0) validationErrors.push('Please select at least one facility or equipment item');

        if (validationErrors.length > 0) {
          throw new Error(`Validation failed:\n${validationErrors.join('\n• ')}`);
        }

        if (isAllDay) {
          startTime = '00:00';
          endTime = '00:00';
        }

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

        console.log('API Response:', result);

        if (!response.ok) {
          console.error('API Error Response:', result);

          if (response.status === 422) {
            const errorMessages = [];
            if (result.errors) {
              Object.entries(result.errors).forEach(([field, messages]) => {
                errorMessages.push(`${field}: ${messages.join(', ')}`);
              });
            }
            throw new Error(`Server validation failed: ${errorMessages.join('; ')}`);
          } else if (response.status === 409) {
            const conflictMessages = result.conflict_items?.map(item =>
              `${item.name} - ${item.message || 'Conflict detected'}`
            ) || [];
            throw new Error(`Scheduling conflicts: ${conflictMessages.join(', ')}`);
          } else {
            throw new Error(result.message || `HTTP Error ${response.status}: Failed to create reservation`);
          }
        }

        if (result && (result.success === true || result.data || result.request_id)) {
          showToast('Reservation created successfully!', 'success');

          const modalElement = document.getElementById('addReservationModal');
          const modal = bootstrap.Modal.getInstance(modalElement);
          if (modal) {
            modal.hide();
          }

          form.reset();

          const allDayCheckbox = document.getElementById('allDayCheckbox');
          if (allDayCheckbox) {
            allDayCheckbox.checked = false;
            const changeEvent = new Event('change', { bubbles: true });
            allDayCheckbox.dispatchEvent(changeEvent);
          }

          if (window.stepManager && typeof window.stepManager.resetSteps === 'function') {
            window.stepManager.resetSteps();
          } else {
            const stepContents = document.querySelectorAll('.step-content');
            const steps = document.querySelectorAll('.step');

            stepContents.forEach(content => {
              content.classList.add('d-none');
            });
            const step1 = document.querySelector('.step-content[data-step="1"]');
            if (step1) step1.classList.remove('d-none');

            steps.forEach(step => {
              step.classList.remove('active');
            });
            const step1Indicator = document.querySelector('.step[data-step="1"]');
            if (step1Indicator) step1Indicator.classList.add('active');

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

          await loadAdminReservations(currentReservationPage || 1);
        } else {
          console.error('Unexpected success response format:', result);
          throw new Error(result.message || 'Reservation created but received unexpected response format');
        }

      } catch (error) {
        console.error('Error saving reservation:', error);

        let errorMessage = error.message;
        if (errorMessage.includes('Validation failed:')) {
          errorMessage = errorMessage.replace('Validation failed:\n', 'Please fix the following:\n• ');
        }

        showToast(errorMessage || 'Failed to create reservation. Please try again.', 'error');
      } finally {
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
      }
    }

    function validateReservationForm(formData, facilities, equipment) {
      const requiredFields = ['user_type', 'first_name', 'last_name', 'email', 'purpose_id', 'num_participants', 'start_date', 'end_date', 'start_time', 'end_time'];

      for (const field of requiredFields) {
        if (!formData.get(field)) {
          showToast(`Please fill in the ${field.replace('_', ' ')} field`, 'error');
          return false;
        }
      }

      const startDate = new Date(formData.get('start_date') + ' ' + formData.get('start_time'));
      const endDate = new Date(formData.get('end_date') + ' ' + formData.get('end_time'));

      if (endDate <= startDate) {
        showToast('End date/time must be after start date/time', 'error');
        return false;
      }

      if (facilities.length === 0 && equipment.length === 0) {
        showToast('Please select at least one facility or equipment item', 'error');
        return false;
      }

      return true;
    }

    function setupReservationStepNavigation() {
      let currentStep = 1;
      const totalSteps = 5;

      const prevBtn = document.getElementById('prevStepBtn');
      const nextBtn = document.getElementById('nextStepBtn');
      const submitBtn = document.getElementById('submitReservationBtn');

      function validateCurrentStep() {
        let isValid = false;

        switch (currentStep) {
          case 1:
            const userType = document.querySelector('select[name="user_type"]')?.value;
            const firstName = document.querySelector('input[name="first_name"]')?.value.trim();
            const lastName = document.querySelector('input[name="last_name"]')?.value.trim();
            const email = document.querySelector('input[name="email"]')?.value.trim();
            const schoolId = document.querySelector('input[name="school_id"]')?.value.trim();

            let schoolIdValid = true;

            if (userType === 'Internal') {
              schoolIdValid = schoolId && schoolId.length > 0;
            } else {
              schoolIdValid = true;
            }

            console.log('Step 1 validation:', {
              userType,
              firstName,
              lastName,
              email,
              schoolId,
              schoolIdValid
            });

            isValid = userType && firstName && lastName && email && schoolIdValid;
            break;
          case 2:
            const purposeSelect = document.getElementById('purposeSelect');

            if (!purposeSelect) {
              console.error('Purpose select element not found!');
              isValid = false;
              break;
            }

            const purposeValue = purposeSelect.value;
            const purposeId = purposeValue ? parseInt(purposeValue) : null;
            const selectedIndex = purposeSelect.selectedIndex;
            const isPlaceholderSelected = selectedIndex === 0;

            console.log('Purpose validation check:', {
              rawValue: purposeValue,
              parsedId: purposeId,
              selectedIndex: selectedIndex,
              isPlaceholderSelected: isPlaceholderSelected,
              optionText: purposeSelect.options[selectedIndex]?.text
            });

            const purposeValid = !isPlaceholderSelected && purposeId && !isNaN(purposeId) && purposeId > 0;

            console.log('Purpose valid?', purposeValid);

            const participantsElem = document.querySelector('input[name="num_participants"]');
            const participantsValue = participantsElem?.value;
            const participantsValid = participantsValue && parseInt(participantsValue) > 0;

            const tablesElem = document.querySelector('input[name="num_tables"]');
            const tablesValue = tablesElem?.value;
            const tablesValid = tablesValue !== undefined && tablesValue !== '' && !isNaN(parseInt(tablesValue)) && parseInt(tablesValue) >= 0;

            const chairsElem = document.querySelector('input[name="num_chairs"]');
            const chairsValue = chairsElem?.value;
            const chairsValid = chairsValue !== undefined && chairsValue !== '' && !isNaN(parseInt(chairsValue)) && parseInt(chairsValue) >= 0;

            const microphonesElem = document.querySelector('input[name="num_microphones"]');
            const microphonesValue = microphonesElem?.value;
            const microphonesValid = !microphonesValue || (microphonesValue !== '' && !isNaN(parseInt(microphonesValue)) && parseInt(microphonesValue) >= 0);

            console.log('Step 2 validation results:', {
              purposeValid,
              participantsValid,
              tablesValid,
              chairsValid,
              microphonesValid,
              allValid: purposeValid && participantsValid && tablesValid && chairsValid && microphonesValid
            });

            isValid = purposeValid && participantsValid && tablesValid && chairsValid && microphonesValid;
            break;

          case 3:
            const facilities = document.querySelectorAll('.facility-checkbox:checked').length;
            const equipment = document.querySelectorAll('.equipment-checkbox:checked').length;

            console.log('Step 4 validation:', {
              facilities,
              equipment
            });
            isValid = facilities > 0 || equipment > 0;
            break;

          case 4:
            const startDate = document.getElementById('startDate')?.value;
            const endDate = document.getElementById('endDate')?.value;
            const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

            if (isAllDay) {
              isValid = startDate && endDate && new Date(endDate) >= new Date(startDate);
              console.log('Step 4 all-day validation:', {
                startDate,
                endDate,
                isValid,
                comparison: new Date(endDate) >= new Date(startDate)
              });
            } else {
              const startTime = document.getElementById('startTime')?.value;
              const endTime = document.getElementById('endTime')?.value;

              console.log('Step 4 timed validation:', {
                startDate,
                startTime,
                endDate,
                endTime
              });

              if (!startDate || !startTime || !endDate || !endTime) {
                isValid = false;
              } else {
                const start = new Date(`${startDate}T${startTime}`);
                const end = new Date(`${endDate}T${endTime}`);
                isValid = end > start;
              }
            }
            break;

          case 5:
            const statusSelect = document.getElementById('initialStatusSelect');
            const statusValid = statusSelect && statusSelect.value && statusSelect.value !== '';

            const accessCodeInput = document.getElementById('accessCodeInput');
            const accessCodeValid = accessCodeInput && accessCodeInput.value.length === 10;

            console.log('Step 5 validation:', {
              statusValid,
              accessCodeValid,
              allValid: statusValid && accessCodeValid
            });

            isValid = statusValid && accessCodeValid;
            break;

          default:
            isValid = false;
        }

        console.log(`Step ${currentStep} validation result:`, isValid);

        if (nextBtn) {
          nextBtn.disabled = !isValid;
          console.log(`Next button disabled: ${nextBtn.disabled}`);
        }

        if (currentStep === totalSteps && submitBtn) {
          submitBtn.disabled = !isValid;
        }

        return isValid;
      }

      document.querySelectorAll('#addReservationForm input, #addReservationForm select').forEach(element => {
        element.addEventListener('change', () => {
          validateCurrentStep();
          if (currentStep === 5) {
            updateReviewSummary();
          }
        });

        element.addEventListener('input', () => {
          validateCurrentStep();
          if (currentStep === 5) {
            updateReviewSummary();
          }
        });
      });

      function updateStepDisplay() {
        document.querySelectorAll('.step').forEach(step => {
          const stepNum = parseInt(step.dataset.step);
          if (stepNum === currentStep) {
            step.classList.add('active');
          } else {
            step.classList.remove('active');
          }
        });

        document.querySelectorAll('.step-content').forEach(content => {
          const stepNum = parseInt(content.dataset.step);
          if (stepNum === currentStep) {
            content.classList.remove('d-none');
          } else {
            content.classList.add('d-none');
          }
        });

        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-block';

        if (currentStep === totalSteps) {
          nextBtn.classList.add('d-none');
          submitBtn.classList.remove('d-none');
          updateReviewSummary();
        } else {
          nextBtn.classList.remove('d-none');
          submitBtn.classList.add('d-none');
        }

        validateCurrentStep();
      }

      function updateReviewSummary() {
        const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
        const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
        document.getElementById('reviewUserName').textContent = `${firstName} ${lastName}`;

        const purposeSelect = document.querySelector('select[name="purpose_id"]');
        const purposeText = purposeSelect?.options[purposeSelect.selectedIndex]?.text || '-';
        document.getElementById('reviewPurpose').textContent = purposeText;

        const participants = document.querySelector('input[name="num_participants"]')?.value || '0';
        document.getElementById('reviewParticipants').textContent = participants;

        const tables = document.querySelector('input[name="num_tables"]')?.value || '0';
        const chairs = document.querySelector('input[name="num_chairs"]')?.value || '0';
        document.getElementById('reviewFurniture').textContent = `${tables} tables, ${chairs} chairs`;

        const numMicrophones = document.querySelector('input[name="num_microphones"]')?.value || '0';
        const reviewMicrophones = document.getElementById('reviewMicrophones');
        if (reviewMicrophones) {
          reviewMicrophones.textContent = numMicrophones;
        }

        const endorser = document.querySelector('input[name="endorser"]')?.value || 'None';
        document.getElementById('reviewEndorser').textContent = endorser;

        const statusSelect = document.getElementById('initialStatusSelect');
        const selectedStatus = statusSelect ? statusSelect.options[statusSelect.selectedIndex]?.text : 'Scheduled';
        document.getElementById('reviewStatus').textContent = selectedStatus;

        const startDate = document.getElementById('startDate')?.value;
        const startTime = document.getElementById('startTime')?.value;
        const endDate = document.getElementById('endDate')?.value;
        const endTime = document.getElementById('endTime')?.value;
        const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

        if (startDate && endDate) {
          const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
          const startDateObj = new Date(startDate + 'T12:00:00');
          const endDateObj = new Date(endDate + 'T12:00:00');

          if (isAllDay) {
            if (startDate === endDate) {
              document.getElementById('reviewSchedule').textContent =
                `${startDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
            } else {
              document.getElementById('reviewSchedule').textContent =
                `${startDateObj.toLocaleDateString('en-US', dateOptions)} - ${endDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
            }

            const diffDays = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('reviewDuration').textContent =
              diffDays === 1 ? '1 day' : `${diffDays} days`;
          } else if (startTime && endTime) {
            const start = new Date(`${startDate}T${startTime}`);
            const end = new Date(`${endDate}T${endTime}`);
            const timeOptions = { hour: '2-digit', minute: '2-digit' };

            const startStr = `${start.toLocaleDateString('en-US', dateOptions)} ${start.toLocaleTimeString('en-US', timeOptions)}`;
            const endStr = `${end.toLocaleDateString('en-US', dateOptions)} ${end.toLocaleTimeString('en-US', timeOptions)}`;

            document.getElementById('reviewSchedule').textContent = `${startStr} to ${endStr}`;

            const durationMs = end - start;
            const hours = Math.floor(durationMs / (1000 * 60 * 60));
            const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));

            let durationStr = '';
            if (hours > 0) durationStr += `${hours} hour${hours > 1 ? 's' : ''}`;
            if (minutes > 0) {
              if (hours > 0) durationStr += ' ';
              durationStr += `${minutes} minute${minutes > 1 ? 's' : ''}`;
            }
            document.getElementById('reviewDuration').textContent = durationStr || '0 hours';
          } else {
            document.getElementById('reviewSchedule').textContent = '-';
            document.getElementById('reviewDuration').textContent = '-';
          }
        } else {
          document.getElementById('reviewSchedule').textContent = '-';
          document.getElementById('reviewDuration').textContent = '-';
        }

        const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked'))
          .map(cb => cb.dataset.name || cb.value);
        document.getElementById('reviewFacilities').textContent =
          selectedFacilities.length > 0 ? selectedFacilities.join(', ') : 'None selected';

        const selectedEquipment = Array.from(document.querySelectorAll('.equipment-checkbox:checked'))
          .map(cb => cb.dataset.name || cb.value);
        document.getElementById('reviewEquipment').textContent =
          selectedEquipment.length > 0 ? selectedEquipment.join(', ') : 'None selected';

        const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked'))
          .map(cb => cb.dataset.name || cb.value);
        const reviewServices = document.getElementById('reviewServices');
        if (reviewServices) {
          reviewServices.textContent = selectedServices.length > 0 ? selectedServices.join(', ') : 'None selected';
        }

        const accessCode = document.getElementById('accessCodeInput')?.value || '-';
        document.getElementById('reviewAccessCode').textContent = accessCode;
      }

      prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
          currentStep--;
          updateStepDisplay();
        }
      });

      nextBtn.addEventListener('click', () => {
        if (validateCurrentStep() && currentStep < totalSteps) {
          currentStep++;
          updateStepDisplay();
        }
      });

      function setupCheckboxListeners() {
        document.addEventListener('change', function (e) {
          if (e.target.matches('.facility-checkbox, .equipment-checkbox')) {
            if (currentStep === 4) {
              validateCurrentStep();
            }
            if (currentStep === 5) {
              updateReviewSummary();
            }
          }
        });

        const clearFacilitiesBtn = document.getElementById('clearFacilities');
        if (clearFacilitiesBtn) {
          clearFacilitiesBtn.addEventListener('click', function () {
            document.querySelectorAll('.facility-checkbox:checked').forEach(cb => {
              cb.checked = false;
            });
            const selectAllCheckbox = document.getElementById('selectAllFacilities');
            if (selectAllCheckbox) {
              selectAllCheckbox.checked = false;
            }
            if (currentStep === 4 || currentStep === 5) {
              validateCurrentStep();
              if (currentStep === 5) updateReviewSummary();
            }
          });
        }

        const clearEquipmentBtn = document.getElementById('clearEquipment');
        if (clearEquipmentBtn) {
          clearEquipmentBtn.addEventListener('click', function () {
            document.querySelectorAll('.equipment-checkbox:checked').forEach(cb => {
              cb.checked = false;
            });
            if (currentStep === 4 || currentStep === 5) {
              validateCurrentStep();
              if (currentStep === 5) updateReviewSummary();
            }
          });
        }

        const eventModalElement = document.getElementById('eventModal');
        if (eventModalElement) {
          eventModal = new bootstrap.Modal(eventModalElement);
        }

        const selectAllFacilities = document.getElementById('selectAllFacilities');
        if (selectAllFacilities) {
          selectAllFacilities.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.facility-checkbox:not(:disabled)');
            checkboxes.forEach(cb => {
              cb.checked = this.checked;
            });
            if (currentStep === 4 || currentStep === 5) {
              validateCurrentStep();
              if (currentStep === 5) updateReviewSummary();
            }
          });
        }
      }

      const formElements = document.querySelectorAll('#addReservationForm input, #addReservationForm select, #addReservationForm textarea');
      formElements.forEach(element => {
        element.addEventListener('change', () => {
          console.log(`${element.name} changed, validating step ${currentStep}`);
          validateCurrentStep();
          if (currentStep === 5) {
            updateReviewSummary();
          }
        });

        if (element.type === 'number' || element.tagName === 'SELECT') {
          element.addEventListener('input', () => {
            console.log(`${element.name} input, validating step ${currentStep}`);
            validateCurrentStep();
            if (currentStep === 5) {
              updateReviewSummary();
            }
          });
        }
      });

      document.getElementById('generateAccessCode')?.addEventListener('click', () => {
        console.log('Access code generated, validating step');
        validateCurrentStep();
      });

      updateStepDisplay();
      setupCheckboxListeners();

      return {
        resetSteps: () => {
          currentStep = 1;
          updateStepDisplay();
        },
        validateCurrentStep: validateCurrentStep
      };
    }

    document.getElementById('reservationsPerPageSelect')?.addEventListener('change', function () {
      reservationsPerPage = parseInt(this.value);
      currentReservationPage = 1;
      loadAdminReservations(1);
    });

    function showApprovalHistoryModal(requestId) {
      console.log('Showing approval history for request:', requestId);

      const modalElement = document.getElementById('approvalHistoryModal');
      if (!modalElement) {
        console.error('Modal element not found');
        return;
      }

      const modal = new bootstrap.Modal(modalElement);

      document.getElementById('approvalsHistoryContent').innerHTML = `
        <div class="text-center text-muted py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading approvals...</p>
        </div>
      `;

      document.getElementById('rejectionsHistoryContent').innerHTML = `
        <div class="text-center text-muted py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading rejections...</p>
        </div>
      `;

      document.getElementById('approvalsTabCount').textContent = '0';
      document.getElementById('rejectionsTabCount').textContent = '0';

      modal.show();

      loadApprovalHistory(requestId);
    }

    function populateTimeDropdowns() {
      const startTimeSelect = document.getElementById('startTime');
      const endTimeSelect = document.getElementById('endTime');

      if (!startTimeSelect || !endTimeSelect) return;

      startTimeSelect.innerHTML = '';
      endTimeSelect.innerHTML = '';

      for (let hour = 0; hour < 24; hour++) {
        for (let minute = 0; minute < 60; minute += 15) {
          const hourStr = hour.toString().padStart(2, '0');
          const minuteStr = minute.toString().padStart(2, '0');
          const timeValue = `${hourStr}:${minuteStr}`;

          const displayHour = hour % 12 || 12;
          const ampm = hour < 12 ? 'AM' : 'PM';
          const displayTime = `${displayHour}:${minuteStr} ${ampm}`;

          const option = new Option(displayTime, timeValue);
          startTimeSelect.appendChild(option.cloneNode(true));
          endTimeSelect.appendChild(option);
        }
      }

      startTimeSelect.value = '09:00';
      endTimeSelect.value = '17:00';
    }

    function calculateDuration() {
      const startDate = document.getElementById('startDate')?.value;
      const endDate = document.getElementById('endDate')?.value;
      const startTime = document.getElementById('startTime')?.value;
      const endTime = document.getElementById('endTime')?.value;

      if (startDate && endDate && startTime && endTime) {
        const start = new Date(`${startDate}T${startTime}`);
        const end = new Date(`${endDate}T${endTime}`);

        if (end > start) {
          const diffMs = end - start;
          const hours = Math.floor(diffMs / (1000 * 60 * 60));
          const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

          let durationText = '';
          if (hours > 0) durationText += `${hours} hour${hours > 1 ? 's' : ''}`;
          if (minutes > 0) {
            if (hours > 0) durationText += ' ';
            durationText += `${minutes} minute${minutes > 1 ? 's' : ''}`;
          }

          const durationElement = document.getElementById('durationDisplay');
          if (durationElement) {
            durationElement.textContent = durationText || '0 minutes';
          }
        }
      }
    }

    async function loadCalendarEvents() {
      try {
        if (window.calendarEventsApp && typeof window.calendarEventsApp.loadCalendarEvents === 'function') {
          await window.calendarEventsApp.loadCalendarEvents(1);
        }
      } catch (error) {
        console.error('Error loading calendar events:', error);
      }
    }

    async function loadAdminReservations(page = 1) {
      try {
        if (window.ongoingEventsApp && typeof window.ongoingEventsApp.loadOngoingEvents === 'function') {
          await window.ongoingEventsApp.loadOngoingEvents(page);
        }
      } catch (error) {
        console.error('Error loading admin reservations:', error);
      }
    }

    async function loadApprovalHistory(requestId) {
      try {
        console.log('Fetching approval history for request:', requestId);

        const [historyResponse, statusResponse] = await Promise.all([
          fetch(`/api/admin/requisition/${requestId}/approval-history`, {
            headers: {
              'Authorization': `Bearer ${adminToken}`,
              'Accept': 'application/json'
            }
          }),
          fetch(`/requisitions/${requestId}/approval-status`, {
            headers: {
              'Authorization': `Bearer ${adminToken}`,
              'Accept': 'application/json'
            }
          })
        ]);

        if (!historyResponse.ok) {
          throw new Error(`HTTP error! status: ${historyResponse.status}`);
        }

        const approvalHistory = await historyResponse.json();
        const approvalData = statusResponse.ok ? await statusResponse.json() : null;

        console.log('Approval history data:', approvalHistory);
        console.log('Approval status data:', approvalData);

        const approvals = approvalHistory.filter(item => item.action === 'approved');
        const rejections = approvalHistory.filter(item => item.action === 'rejected');

        document.getElementById('approvalsTabCount').textContent = approvals.length;
        document.getElementById('rejectionsTabCount').textContent = rejections.length;

        document.getElementById('approvalsHistoryContent').innerHTML = generateApprovalHistoryHTML(approvals, approvalData);
        document.getElementById('rejectionsHistoryContent').innerHTML = generateApprovalHistoryHTML(rejections, approvalData);

      } catch (error) {
        console.error('Error loading approval history:', error);
        const errorHtml = `<div class="text-center text-danger py-4">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load history: ${error.message}
        </div>`;
        document.getElementById('approvalsHistoryContent').innerHTML = errorHtml;
        document.getElementById('rejectionsHistoryContent').innerHTML = errorHtml;
      }
    }

    function generateApprovalHistoryHTML(history, approvalData) {
      if (!history || history.length === 0) {
        return '<div class="text-center text-muted py-4">No approval history found</div>';
      }

      const currentApprovals = approvalData?.current_approvals || 0;
      const requiredApprovals = approvalData?.max_approvals || 0;
      const requiredAdmins = approvalData?.required_admins || [];
      const isFullyApproved = approvalData?.is_fully_approved || false;
      const approvalPercentage = requiredApprovals > 0 ?
        Math.round((currentApprovals / requiredApprovals) * 100) : 0;

      return `
        ${requiredApprovals > 0 ? `
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-body">
            <h6 class="card-title mb-3">Approval Progress</h6>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-user-tie text-muted me-2"></i>
                <span class="fw-medium ${isFullyApproved ? 'text-success' : 'text-warning'}">
                  ${currentApprovals}/${requiredApprovals} heads approved
                </span>
                ${isFullyApproved ?
          '<span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Fully Approved</span>' :
          `<span class="badge bg-warning ms-2"><i class="bi bi-clock"></i> Pending</span>`}
              </div>
              <span class="text-muted">${approvalPercentage}%</span>
            </div>

            <div class="progress mb-3" style="height: 8px;">
              <div class="progress-bar ${isFullyApproved ? 'bg-success' : 'bg-primary'}" 
                   role="progressbar" 
                   style="width: ${approvalPercentage}%"
                   aria-valuenow="${approvalPercentage}" 
                   aria-valuemin="0" 
                   aria-valuemax="100">
              </div>
            </div>

            ${requiredAdmins.length > 0 ? `
            <div class="mt-3">
              <h6 class="card-subtitle mb-2 text-muted">Required Approvers:</h6>
              <div class="d-flex flex-wrap gap-2">
                ${requiredAdmins.map(admin => `
                  <div class="d-flex align-items-center border rounded p-2 ${admin.has_approved ? 'border-success bg-success-light' : 'border-secondary'}">
                    <div class="me-2">
                      ${admin.has_approved ?
          '<i class="bi bi-check-circle-fill text-success"></i>' :
          '<i class="bi bi-clock text-warning"></i>'}
                    </div>
                    <div>
                      <div class="fw-medium">${admin.name}</div>
                      ${admin.title ? `<small class="text-muted">${admin.title}</small>` : ''}
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
            ` : ''}
          </div>
        </div>
        ` : ''}

        <h6 class="mb-3">Approval History</h6>
        ${history.map(item => `
          <div class="d-flex align-items-center mb-3 p-3 border rounded">
            <div class="me-3 flex-shrink-0">
              ${item.admin_photo ?
          `<img src="${item.admin_photo}" class="rounded-circle" width="45" height="45" alt="${item.admin_name}" style="object-fit: cover;">` :
          `<div class="rounded-circle d-flex align-items-center justify-content-center ${item.action === 'approved' ? 'bg-success' : 'bg-danger'} text-white" style="width: 45px; height: 45px;">
                  ${item.admin_name.split(' ').map(n => n.charAt(0)).join('')}
                </div>`}
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong class="d-block">${item.admin_name}</strong>
                  <small class="text-muted">
                    <i class="bi ${item.action === 'approved' ? 'bi-hand-thumbs-up text-success' : 'bi-hand-thumbs-down text-danger'} me-1"></i>
                    ${item.action === 'approved' ? 'Approved' : 'Rejected'} this request
                  </small>
                  ${item.remarks ? `<div class="mt-2 small p-2 bg-light rounded">"${item.remarks}"</div>` : ''}
                </div>
                <small class="text-muted text-end">${item.formatted_date}</small>
              </div>
            </div>
          </div>
        `).join('')}
      `;
    }

    async function addCalendarEvent(eventData) {
      try {
        const submitBtn = document.querySelector('#addCalendarEventForm button[type="submit"]');
        const spinner = submitBtn.querySelector('#eventSubmitSpinner');

        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        const form = document.getElementById('addCalendarEventForm');
        const formData = new FormData(form);

        const isAllDay = document.getElementById('calendarAllDayField')?.checked || false;

        const payload = {
          event_name: formData.get('event_name'),
          event_type: formData.get('event_type'),
          description: formData.get('description') || '',
          start_date: formData.get('start_date'),
          end_date: formData.get('end_date'),
          all_day: isAllDay
        };

        if (isAllDay) {
          payload.start_time = '00:00';
          payload.end_time = '23:59';
        } else {
          payload.start_time = formData.get('start_time');
          payload.end_time = formData.get('end_time');
        }

        console.log('Submitting calendar event payload:', payload);

        const response = await fetch('/api/calendar-events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${adminToken}`,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(payload)
        });

        console.log('Response status:', response.status);

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let result;
        try {
          result = JSON.parse(responseText);
        } catch (e) {
          console.error('Failed to parse response as JSON:', responseText);
          throw new Error('Server returned invalid JSON response');
        }

        if (!response.ok) {
          if (response.status === 422 && result.errors) {
            const errorMessages = Object.values(result.errors).flat().join(', ');
            throw new Error(errorMessages || 'Validation failed');
          }
          throw new Error(result.message || `HTTP Error ${response.status}: Failed to add event`);
        }

        if (!result.success) {
          throw new Error(result.message || 'Server returned error response');
        }

        console.log('Event created successfully:', result.data);

        const successMessage = isAllDay
          ? 'All-day calendar event added successfully!'
          : 'Calendar event added successfully!';
        showToast(successMessage, 'success');

        const modalElement = document.getElementById('addCalendarEventModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();

        if (form) {
          form.reset();

          const allDayCheckbox = document.getElementById('calendarAllDayField');
          if (allDayCheckbox) {
            allDayCheckbox.checked = false;
            allDayCheckbox.disabled = false;
          }

          const startTimeInput = form.querySelector('input[name="start_time"]');
          const endTimeInput = form.querySelector('input[name="end_time"]');
          if (startTimeInput) {
            startTimeInput.disabled = false;
            startTimeInput.value = '09:00';
            startTimeInput.required = true;
          }
          if (endTimeInput) {
            endTimeInput.disabled = false;
            endTimeInput.value = '17:00';
            endTimeInput.required = true;
          }

          const allDayIndicator = document.getElementById('allDayIndicator');
          if (allDayIndicator) allDayIndicator.style.display = 'none';
        }

        await new Promise(resolve => setTimeout(resolve, 300));

        if (window.calendarEventsApp && typeof window.calendarEventsApp.loadCalendarEvents === 'function') {
          console.log('Refreshing calendar events Vue app...');
          await window.calendarEventsApp.loadCalendarEvents(1);
        }

        return result;

      } catch (error) {
        console.error('Error adding calendar event:', error);
        showToast(error.message || 'Failed to add calendar event. Please try again.', 'error');
        throw error;
      } finally {
        const submitBtn = document.querySelector('#addCalendarEventForm button[type="submit"]');
        if (submitBtn) {
          const spinner = submitBtn.querySelector('#eventSubmitSpinner');
          submitBtn.disabled = false;
          if (spinner) spinner.classList.add('d-none');
        }
      }
    }

    async function handleDeleteCalendarEvent() {
      if (!eventToDeleteId) return;

      const confirmBtn = document.getElementById('confirmDeleteEventBtn');
      const spinner = document.getElementById('deleteEventSpinner');
      const originalText = confirmBtn.innerHTML;

      try {
        confirmBtn.disabled = true;
        spinner.classList.remove('d-none');

        await deleteCalendarEvent(eventToDeleteId);

        if (deleteEventModal) {
          deleteEventModal.hide();
        }

      } finally {
        confirmBtn.disabled = false;
        spinner.classList.add('d-none');
        confirmBtn.innerHTML = originalText;

        eventToDeleteId = null;
        eventToDeleteName = null;
      }
    }

    function updateCharacterCounter(input, maxLength, counterId) {
      if (!input) return;

      let counter = document.getElementById(counterId);
      if (!counter) {
        counter = document.createElement('small');
        counter.id = counterId;
        counter.className = 'text-muted d-block mt-1';
        input.parentNode.appendChild(counter);
      }

      const update = () => {
        const length = input.value.length;
        counter.textContent = `${length}/${maxLength} characters`;

        const percentage = (length / maxLength) * 100;
        counter.className = 'text-muted d-block mt-1';

        if (percentage >= 90) {
          counter.classList.add('text-danger', 'fw-bold');
        } else if (percentage >= 80) {
          counter.classList.add('text-warning', 'fw-medium');
        }
      };

      input.addEventListener('input', update);
      input.addEventListener('change', update);
      update();
    }

    function generateAccessCode() {
      const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      let result = '';

      for (let i = 0; i < 10; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        result += characters.charAt(randomIndex);
      }

      const accessCodeInput = document.getElementById('accessCodeInput');
      if (accessCodeInput) {
        accessCodeInput.value = result;

        if (typeof validateCurrentStep === 'function') {
          validateCurrentStep();
        }

        const reviewAccessCode = document.getElementById('reviewAccessCode');
        if (reviewAccessCode) {
          reviewAccessCode.textContent = result;
        }

        showToast('Access code generated!', 'success');
      }

      return result;
    }

  </script>
@endsection