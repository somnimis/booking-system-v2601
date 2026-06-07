{{-- create-reservation.blade.php --}}
@extends('layouts.admin')

@section('title', 'New Requisition Form')

@section('content')
    <style>
        /* Simple Loading Spinner */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e0e0e0;
            border-top-color: #224d9c;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        /* Disabled step navigation during loading */
        .step-nav-disabled {
            pointer-events: none;
            opacity: 0.5;
        }

        /* Create Reservation Card specific styles - ONLY apply to direct children */
        .create-reservation-card>.card-body {
            position: relative;
            min-height: 500px;
        }

        /* Loading container - absolute overlay inside this specific card */
        .create-reservation-card>.card-body .loading-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: white;
            z-index: 10;
            text-align: center;
            border-radius: 8px;
        }

        .create-reservation-card>.card-body .loading-container p {
            margin-top: 1rem;
            color: #666;
            font-size: 0.875rem;
        }

        /* Form content */
        .create-reservation-card>.card-body #formContent {
            display: block;
        }

        /* Form content starts visible, gets hidden when loading */
        #formContent {
            display: block;
        }

        body.loading #formContent {
            opacity: 0.3;
            pointer-events: none;
        }

        /* Custom scrollbar for better UX */
        .facilities-grid::-webkit-scrollbar,
        .equipment-grid::-webkit-scrollbar,
        #extraServicesContainer::-webkit-scrollbar {
            width: 6px;
        }

        .facilities-grid::-webkit-scrollbar-track,
        .equipment-grid::-webkit-scrollbar-track,
        #extraServicesContainer::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .facilities-grid::-webkit-scrollbar-thumb,
        .equipment-grid::-webkit-scrollbar-thumb,
        #extraServicesContainer::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .facilities-grid::-webkit-scrollbar-thumb:hover,
        .equipment-grid::-webkit-scrollbar-thumb:hover,
        #extraServicesContainer::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Card-style selectable items for Facilities and Equipment */
        .facilities-grid,
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            max-height: 450px;
            overflow-y: auto;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin: 0;
        }

        .facility-card,
        .equipment-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .facility-card:hover,
        .equipment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #c0c0c0;
        }

        .facility-card.selected,
        .equipment-card.selected {
            background: linear-gradient(135deg, #e8f0fe 0%, #d4e4fc 100%);
            border-color: #224d9c;
            box-shadow: 0 2px 8px rgba(34, 77, 156, 0.2);
        }

        .facility-card.selected::after,
        .equipment-card.selected::after,
        .service-card.selected::after {
            content: '✓';
            font-size: 14px;
            background: #224d9c;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 8px;
            right: 8px;
        }

        .facility-card.disabled,
        .equipment-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .facility-card.disabled:hover,
        .equipment-card.disabled:hover {
            transform: none;
            box-shadow: none;
            border-color: #e9ecef;
        }

        .facility-card strong,
        .equipment-card strong {
            font-size: 1rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            padding-right: 20px;
        }

        /* Card-style selectable items for Services */
        #extraServicesContainer {
            padding: 0.5rem;
            margin: 0;
        }

        .service-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #c0c0c0;
        }

        .service-card.selected {
            background: linear-gradient(135deg, #e8f0fe 0%, #d4e4fc 100%);
            border-color: #224d9c;
            box-shadow: 0 2px 8px rgba(34, 77, 156, 0.2);
        }

        .service-card strong {
            font-size: 1rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            padding-right: 20px;
        }

        /* Hide original checkboxes */
        .facility-checkbox,
        .equipment-checkbox,
        .service-checkbox {
            display: none;
        }

        .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 0.5rem;
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

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        .btn .spinner-border.spinner-border-sm {
            vertical-align: middle;
            margin-right: 0.25rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            body {
                font-size: 0.9rem;
            }

            .facilities-grid,
            .equipment-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 12px;
            }

            .facility-card,
            .equipment-card,
            .service-card {
                padding: 0.75rem;
            }
        }

        @media (max-width: 576px) {

            .facilities-grid,
            .equipment-grid {
                grid-template-columns: 1fr;
            }
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
    </style>

    <main id="main">
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card shadow-sm create-reservation-card">
                        <div class="card-header bg-gradient-primary text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Create New Reservation
                                    </h4>
                                    <p class="mb-0 mt-1 small opacity-75">Fill in the details to create a new reservation
                                        request</p>
                                </div>
                                <a href="{{ url()->previous() }}" class="btn btn-light btn-sm">
                                    <i class="bi bi-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <!-- Loading Spinner inside card -->
                            <div id="loadingContainer" class="loading-container" style="display: none;">
                                <div class="loading-spinner"></div>
                                <p>Loading form data...</p>
                            </div>
                            <div id="formContent">
                                <!-- Step Navigation -->
                                <div class="steps">
                                    <div class="step" data-step="1">
                                        <div class="step-circle">1</div>
                                        <div class="step-label">User Details</div>
                                    </div>
                                    <div class="step" data-step="2">
                                        <div class="step-circle">2</div>
                                        <div class="step-label">Event Details</div>
                                    </div>
                                    <div class="step" data-step="3">
                                        <div class="step-circle">3</div>
                                        <div class="step-label">Facilities</div>
                                    </div>
                                    <div class="step" data-step="4">
                                        <div class="step-circle">4</div>
                                        <div class="step-label">Equipment</div>
                                    </div>
                                    <div class="step" data-step="5">
                                        <div class="step-circle">5</div>
                                        <div class="step-label">Services</div>
                                    </div>
                                    <div class="step" data-step="6">
                                        <div class="step-circle">6</div>
                                        <div class="step-label">Schedule</div>
                                    </div>
                                    <div class="step" data-step="7">
                                        <div class="step-circle">7</div>
                                        <div class="step-label">Review & Submit</div>
                                    </div>
                                </div>

                                <form id="addReservationForm">
                                    @csrf
                                    <!-- Step 1: User Details -->
                                    <div class="step-content" data-step="1">
                                        <h5 class="mb-3">User Information</h5>
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <label class="form-label fw-medium">User Type <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" name="user_type" required>
                                                    <option value="">Select user type</option>
                                                    <option value="Internal">Internal (CPU Student/Faculty/Staff)</option>
                                                    <option value="External">External</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">First Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" maxlength="50"
                                                    required>
                                                <small class="text-muted" id="firstNameCounter"></small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Last Name <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" maxlength="50"
                                                    required>
                                                <small class="text-muted" id="lastNameCounter"></small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Email <span
                                                        class="text-danger">*</span></label>
                                                <input type="email" class="form-control" name="email" maxlength="100"
                                                    required>
                                                <small class="text-muted" id="emailCounter"></small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Contact Number</label>
                                                <input type="text" class="form-control" name="contact_number"
                                                    maxlength="15">
                                                <small class="text-muted" id="contactCounter"></small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">School ID / Employee ID</label>
                                                <input type="text" class="form-control" name="school_id" id="schoolIdInput"
                                                    maxlength="20" disabled>
                                                <small class="text-muted" id="schoolIdCounter">For internal users
                                                    only</small>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Organization</label>
                                                <input type="text" class="form-control" name="organization_name"
                                                    id="organizationInput" maxlength="100">
                                                <small class="text-muted" id="organizationCounter"></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Event Details -->
                                    <div class="step-content d-none" data-step="2">
                                        <h5 class="mb-3">Event Information</h5>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Purpose <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="purposeSelect" name="purpose_id" required>
                                                    <option value="">Loading purposes...</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Expected Participants <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="num_participants" min="1"
                                                    max="500" value="1" required>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-medium">Number of Tables</label>
                                                <input type="number" class="form-control" name="num_tables" min="0"
                                                    max="100" value="0">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-medium">Number of Chairs</label>
                                                <input type="number" class="form-control" name="num_chairs" min="0"
                                                    max="500" value="0">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label fw-medium">Number of Microphones</label>
                                                <input type="number" class="form-control" name="num_microphones" min="0"
                                                    value="0">
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-medium">Event Title</label>
                                                <input type="text" class="form-control" name="event_title" maxlength="50">
                                                <small class="text-muted" id="calendarTitleCounter"></small>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-medium">Event Description</label>
                                                <textarea class="form-control" name="event_details" rows="2"
                                                    maxlength="100"></textarea>
                                                <small class="text-muted" id="calendarDescriptionCounter"></small>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label fw-medium">Additional Requests</label>
                                                <textarea class="form-control" name="additional_requests" rows="3"
                                                    maxlength="250"></textarea>
                                                <small class="text-muted" id="additionalRequestsCounter"></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 3: Facilities -->
                                    <div class="step-content d-none" data-step="3">
                                        <h5 class="mb-3">Select Facilities</h5>

                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <div
                                                    class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <strong><i class="bi bi-building me-2"></i> Available
                                                        Facilities</strong>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <!-- Facilities Filters -->
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <input type="text" id="facilitySearch"
                                                            class="form-control form-control-sm"
                                                            placeholder="🔍 Search facilities...">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select id="facilityRateFilter" class="form-select form-select-sm">
                                                            <option value="">All Rate Types</option>
                                                            <option value="Per Hour">Per Hour</option>
                                                            <option value="Per Event">Per Event</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select id="facilityStatusFilter"
                                                            class="form-select form-select-sm">
                                                            <option value="">All Status</option>
                                                            <option value="available">Available Only</option>
                                                            <option value="unavailable">Unavailable Only</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="facilitiesList" class="facilities-grid"
                                                    style="max-height: 400px; overflow-y: auto;">
                                                    <div class="text-center text-muted py-3">Loading facilities...</div>
                                                </div>
                                                <div class="mt-2 text-muted small">
                                                    <span id="selectedFacilitiesCount">0</span> facilities selected
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4: Equipment -->
                                    <div class="step-content d-none" data-step="4">
                                        <h5 class="mb-3">Select Equipment</h5>

                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <div
                                                    class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <strong><i class="bi bi-tools me-2"></i> Available Equipment</strong>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <!-- Equipment Filters -->
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <input type="text" id="equipmentSearch"
                                                            class="form-control form-control-sm"
                                                            placeholder="🔍 Search equipment...">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select id="equipmentRateFilter" class="form-select form-select-sm">
                                                            <option value="">All Rate Types</option>
                                                            <option value="Per Hour">Per Hour</option>
                                                            <option value="Per Event">Per Event</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select id="equipmentStatusFilter"
                                                            class="form-select form-select-sm">
                                                            <option value="">All Status</option>
                                                            <option value="available">Available Only</option>
                                                            <option value="unavailable">Unavailable Only</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="equipmentList" class="equipment-grid"
                                                    style="max-height: 400px; overflow-y: auto;">
                                                    <div class="text-center text-muted py-3">Loading equipment...</div>
                                                </div>
                                                <div class="mt-2 text-muted small">
                                                    <span id="selectedEquipmentCount">0</span> equipment items selected
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 5: Extra Services -->
                                    <div class="step-content d-none" data-step="5">
                                        <h5 class="mb-3">Select Extra Services</h5>

                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <div
                                                    class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <strong><i class="bi bi-star me-2"></i> Available Services</strong>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="selectAllServices" autocomplete="off">
                                                        <label class="form-check-label" for="selectAllServices">
                                                            Select All Services
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-1">
                                                    <input type="text" id="serviceSearch"
                                                        class="form-control form-control-sm"
                                                        placeholder="🔍 Search services...">
                                                </div>
                                                <div id="extraServicesContainer" class="row g-1"
                                                    style="max-height: 400px; overflow-y: auto; padding: 0.5rem;">
                                                    <div class="col-12 text-center text-muted py-3">Loading extra
                                                        services...</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 4: Schedule -->
                                    <div class="step-content d-none" data-step="6">
                                        <h5 class="mb-3">Schedule Details</h5>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="allDayCheckbox">
                                                    <label class="form-check-label" for="allDayCheckbox">
                                                        <i class="bi bi-sun me-1"></i> All Day Event
                                                    </label>
                                                    <div id="allDayScheduleIndicator"
                                                        class="alert alert-info mt-2 small d-none">
                                                        <i class="bi bi-info-circle me-1"></i> This is an all-day event.
                                                        Time
                                                        selections are disabled.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Start Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="startDate" name="start_date"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">End Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="endDate" name="end_date"
                                                    required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Start Time <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="startTime" name="start_time" required>
                                                    <option value="09:00">9:00 AM</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">End Time <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="endTime" name="end_time" required>
                                                    <option value="17:00">5:00 PM</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <div class="alert alert-secondary">
                                                    <i class="bi bi-clock-history me-2"></i>
                                                    <strong>Duration:</strong> <span id="durationDisplay">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 5: Review & Submit -->
                                    <div class="step-content d-none" data-step="7">
                                        <h5 class="mb-3">Review Reservation Details</h5>

                                        <!-- Row 1: User Information + Event Information -->
                                        <div class="row mb-4">
                                            <!-- Column 1: User Information -->
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light">
                                                        <strong><i class="bi bi-person me-2"></i> User Information</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Name:</strong> <span id="reviewUserName">-</span></p>
                                                        <p><strong>User Type:</strong> <span id="reviewUserType">-</span>
                                                        </p>
                                                        <p><strong>Email:</strong> <span id="reviewEmail">-</span></p>
                                                        <p><strong>Contact Number:</strong> <span
                                                                id="reviewContactNumber">-</span></p>
                                                        <p><strong>School ID:</strong> <span id="reviewSchoolId">-</span>
                                                        </p>
                                                        <p><strong>Organization:</strong> <span
                                                                id="reviewOrganization">-</span></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Column 2: Event Information -->
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light">
                                                        <strong><i class="bi bi-calendar-event me-2"></i> Event
                                                            Information</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Event Title:</strong> <span
                                                                id="reviewEventTitle">-</span></p>
                                                        <p><strong>Event Description:</strong> <span
                                                                id="reviewEventDetails">-</span></p>
                                                        <p><strong>Purpose:</strong> <span id="reviewPurpose">-</span></p>
                                                        <p><strong>Participants:</strong> <span
                                                                id="reviewParticipants">-</span></p>
                                                        <p><strong>Tables/Chairs:</strong> <span
                                                                id="reviewFurniture">-</span></p>
                                                        <p><strong>Microphones:</strong> <span
                                                                id="reviewMicrophones">-</span></p>
                                                        <p><strong>Additional Requests:</strong> <span
                                                                id="reviewAdditionalRequests">-</span></p>
                                                        <p><strong>Status:</strong> <span id="reviewStatus">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row 2: Resources + Schedule -->
                                        <div class="row mb-4">
                                            <!-- Column 3: Resources -->
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light">
                                                        <strong><i class="bi bi-building me-2"></i> Resources</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Facilities:</strong> <span id="reviewFacilities">-</span>
                                                        </p>
                                                        <p><strong>Equipment:</strong> <span id="reviewEquipment">-</span>
                                                        </p>
                                                        <p><strong>Extra Services:</strong> <span
                                                                id="reviewServices">-</span></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Column 4: Schedule -->
                                            <div class="col-md-6">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light">
                                                        <strong><i class="bi bi-clock me-2"></i> Schedule</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Schedule:</strong> <span id="reviewSchedule">-</span></p>
                                                        <p><strong>Duration:</strong> <span id="reviewDuration">-</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row 3: Initial Status (full width) -->
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="card">
                                                    <div class="card-header bg-light">
                                                        <strong><i class="bi bi-flag me-2"></i> Initial Status</strong>
                                                    </div>
                                                    <div class="card-body">
                                                        <select class="form-select" id="initialStatusSelect"
                                                            name="status_id" required>
                                                            <option value="" disabled selected>Loading status options...
                                                            </option>
                                                        </select>
                                                        <small class="text-muted">Choose the initial status for this
                                                            reservation</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Navigation Buttons -->
                                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                        <button type="button" class="btn btn-outline-secondary" id="prevStepBtn"
                                            style="display: none;">
                                            <i class="bi bi-chevron-left me-1"></i> Previous
                                        </button>
                                        <div class="ms-auto">
                                            <button type="button" class="btn btn-primary" id="nextStepBtn">
                                                Next <i class="bi bi-chevron-right ms-1"></i>
                                            </button>
                                            <button type="button" class="btn btn-success d-none" id="submitReservationBtn">
                                                <i class="bi bi-check-circle me-1"></i> Create Reservation
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
<script>
    let adminToken = localStorage.getItem('adminToken');
    let currentStep = 1;
    const totalSteps = 7;

    // Lazy loading state
    let facilitiesLoaded = false;
    let equipmentLoaded = false;
    let servicesLoaded = false;
    
    // Pagination state
    let facilitiesPage = 1;
    let equipmentPage = 1;
    let facilitiesTotalPages = 1;
    let equipmentTotalPages = 1;
    let facilitiesFilters = { search: '', rate_type: '', status: '' };
    let equipmentFilters = { search: '', rate_type: '', status: '' };
    
    // Store data
    let allServices = [];
    let allPurposes = [];
    let allStatuses = [];

    // Toast notification function
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

    // Populate time dropdowns
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

    // Calculate duration
    function calculateDuration() {
        const startDate = document.getElementById('startDate')?.value;
        const endDate = document.getElementById('endDate')?.value;
        let startTime = document.getElementById('startTime')?.value;
        let endTime = document.getElementById('endTime')?.value;

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
            } else {
                const durationElement = document.getElementById('durationDisplay');
                if (durationElement) {
                    durationElement.textContent = 'End time must be after start time';
                }
            }
        }
    }

    // Update character counter
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

    // Setup character counters
    function setupCharacterCounters() {
        updateCharacterCounter(document.querySelector('input[name="first_name"]'), 50, 'firstNameCounter');
        updateCharacterCounter(document.querySelector('input[name="last_name"]'), 50, 'lastNameCounter');
        updateCharacterCounter(document.querySelector('input[name="email"]'), 100, 'emailCounter');
        updateCharacterCounter(document.querySelector('input[name="contact_number"]'), 15, 'contactCounter');
        updateCharacterCounter(document.getElementById('organizationInput'), 100, 'organizationCounter');
        updateCharacterCounter(document.getElementById('schoolIdInput'), 20, 'schoolIdCounter');
        updateCharacterCounter(document.querySelector('input[name="event_title"]'), 50, 'calendarTitleCounter');
        updateCharacterCounter(document.querySelector('textarea[name="event_details"]'), 100, 'calendarDescriptionCounter');

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
    }

    // Load lightweight init data (no facilities/equipment)
    async function loadFormInitData() {
        try {
            const response = await fetch('/api/admin/requisition/form-init-data', {
                headers: {
                    'Authorization': `Bearer ${adminToken}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error(`API returned ${response.status}`);

            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            const data = result.data;
            
            // Store lightweight data
            allPurposes = data.purposes;
            allServices = data.services;
            allStatuses = data.statuses;

            // Populate purposes
            populatePurposes(allPurposes);
            
            // Render services (services are lightweight, load immediately)
            renderServicesList();
            setupServiceFilters();
            
            // Populate status options
            populateStatusOptions(allStatuses);

            console.log('Form init data loaded:', {
                purposes: allPurposes.length,
                services: allServices.length,
                statuses: allStatuses.length
            });

        } catch (error) {
            console.error('Error loading form init data:', error);
            throw error;
        }
    }

    // LAZY LOAD: Load facilities when step 3 is first accessed
    async function loadFacilities(page = 1) {
        if (facilitiesLoaded && page === 1 && !facilitiesFilters.search && !facilitiesFilters.rate_type && !facilitiesFilters.status) {
            return; // Already loaded and no filters
        }
        
        const container = document.getElementById('facilitiesList');
        if (!container) return;
        
// Show loading state - centered
container.innerHTML = `
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; width: 100%; grid-column: 1 / -1;">
        <div class="spinner-border spinner-border-sm text-primary" role="status" style="width: 2rem; height: 2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 text-muted">Loading facilities...</div>
    </div>
`;

        try {
            const params = new URLSearchParams({
                page: page,
                per_page: 20,
                search: facilitiesFilters.search,
                rate_type: facilitiesFilters.rate_type,
                status: facilitiesFilters.status
            });
            
            const response = await fetch(`/api/admin/requisition/facilities?${params}`, {
                headers: {
                    'Authorization': `Bearer ${adminToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error(`API returned ${response.status}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            
            facilitiesPage = result.pagination.current_page;
            facilitiesTotalPages = result.pagination.last_page;
            facilitiesLoaded = true;
            
            renderFacilitiesList(result.data, result.pagination);
            
        } catch (error) {
            console.error('Error loading facilities:', error);
            container.innerHTML = '<div class="text-center text-danger py-3">Failed to load facilities. Please try again.</div>';
        }
    }
    
    // LAZY LOAD: Load equipment when step 4 is first accessed
    async function loadEquipment(page = 1) {
        if (equipmentLoaded && page === 1 && !equipmentFilters.search && !equipmentFilters.rate_type && !equipmentFilters.status) {
            return; // Already loaded and no filters
        }
        
        const container = document.getElementById('equipmentList');
        if (!container) return;
        
// Show loading state - centered
container.innerHTML = `
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; width: 100%; grid-column: 1 / -1;">
        <div class="spinner-border spinner-border-sm text-primary" role="status" style="width: 2rem; height: 2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 text-muted">Loading equipment...</div>
    </div>
`;
        try {
            const params = new URLSearchParams({
                page: page,
                per_page: 20,
                search: equipmentFilters.search,
                rate_type: equipmentFilters.rate_type,
                status: equipmentFilters.status
            });
            
            const response = await fetch(`/api/admin/requisition/equipment?${params}`, {
                headers: {
                    'Authorization': `Bearer ${adminToken}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error(`API returned ${response.status}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            
            equipmentPage = result.pagination.current_page;
            equipmentTotalPages = result.pagination.last_page;
            equipmentLoaded = true;
            
            renderEquipmentList(result.data, result.pagination);
            
        } catch (error) {
            console.error('Error loading equipment:', error);
            container.innerHTML = '<div class="text-center text-danger py-3">Failed to load equipment. Please try again.</div>';
        }
    }

    function populatePurposes(purposes) {
        const select = document.getElementById('purposeSelect');
        if (!select) return;

        select.innerHTML = '<option value="" disabled selected>Select purpose of reservation</option>';
        purposes.forEach(purpose => {
            const option = document.createElement('option');
            option.value = purpose.purpose_id.toString();
            option.textContent = purpose.purpose_name;
            select.appendChild(option);
        });
    }

    function populateStatusOptions(statuses) {
        const statusSelect = document.getElementById('initialStatusSelect');
        if (!statusSelect) return;

        statusSelect.innerHTML = '<option value="" disabled selected>Select initial status</option>';
        statuses.forEach(status => {
            const option = document.createElement('option');
            option.value = status.status_id;
            option.textContent = status.status_name;
            option.style.color = status.color_code;
            statusSelect.appendChild(option);
        });

        // Set default to Scheduled if available
        const scheduledOption = Array.from(statusSelect.options).find(opt => opt.textContent === 'Scheduled');
        if (scheduledOption) scheduledOption.selected = true;
    }
    
    function renderFacilitiesList(facilities, pagination) {
        const facilitiesList = document.getElementById('facilitiesList');
        if (!facilitiesList) return;

        if (facilities.length === 0) {
            facilitiesList.innerHTML = '<div class="text-center text-muted py-3">No facilities match your filters</div>';
            return;
        }

        facilitiesList.innerHTML = '';
        facilities.forEach(facility => {
            let isAvailable = false;
            let statusName = 'Unknown';
            let statusColor = '#6c757d';

            if (facility.status_name) {
                statusName = facility.status_name;
                isAvailable = facility.status_name === 'Available';
                statusColor = isAvailable ? '#28a745' :
                    (facility.status_name === 'Unavailable' ? '#dc3545' :
                        (facility.status_name === 'Under Maintenance' ? '#ffc107' :
                            (facility.status_name === 'Reserved' ? '#007bff' : '#6c757d')));
            } else if (facility.status && facility.status.status_name) {
                statusName = facility.status.status_name;
                isAvailable = facility.status.status_name === 'Available';
                statusColor = facility.status.color_code || '#6c757d';
            }

            const div = document.createElement('div');
            div.className = `facility-card ${!isAvailable ? 'disabled' : ''}`;
            div.innerHTML = `
                <input type="checkbox" class="facility-checkbox" value="${facility.facility_id}" 
                       data-name="${facility.facility_name.replace(/'/g, "\\'")}" data-fee="${facility.base_fee}"
                       data-rate-type="${facility.rate_type}" data-capacity="${facility.capacity}"
                       ${!isAvailable ? 'disabled' : ''}>
                <div class="fw-medium">${escapeHtml(facility.facility_name)}</div>
                <div><small>₱${parseFloat(facility.base_fee).toLocaleString()} ${facility.rate_type === 'Per Hour' ? '/hour' : '/event'}</small></div>
                <div><small class="text-muted">Capacity: ${facility.capacity} people</small></div>
                ${facility.location_note && facility.location_note !== 'No location note provided.' ?
                    `<div><small class="text-muted"><i class="bi bi-geo-alt"></i> ${escapeHtml(facility.location_note)}</small></div>` : ''}
                <div><span class="badge" style="background-color: ${statusColor}; color: white;">${statusName}</span></div>
            `;

            if (isAvailable) {
                div.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const checkbox = div.querySelector('.facility-checkbox');
                    checkbox.checked = !checkbox.checked;
                    div.classList.toggle('selected', checkbox.checked);
                    updateSelectedCounts();
                });
            }

            facilitiesList.appendChild(div);
        });
        
        // Add pagination controls if needed
        if (pagination && pagination.last_page > 1) {
            addFacilityPagination(pagination);
        }
        
        updateSelectedCounts();
    }
    
    function addFacilityPagination(pagination) {
        const facilitiesList = document.getElementById('facilitiesList');
        if (!facilitiesList) return;
        
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'd-flex justify-content-center align-items-center gap-2 mt-3 pt-2 border-top';
        paginationDiv.style.gridColumn = '1 / -1';
        
        let buttonsHTML = '';
        
        // Previous button
        buttonsHTML += `<button class="btn btn-sm btn-outline-secondary" onclick="changeFacilitiesPage(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>Previous</button>`;
        
        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                buttonsHTML += `<button class="btn btn-sm ${i === pagination.current_page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="changeFacilitiesPage(${i})">${i}</button>`;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                buttonsHTML += `<span class="px-1">...</span>`;
            }
        }
        
        // Next button
        buttonsHTML += `<button class="btn btn-sm btn-outline-secondary" onclick="changeFacilitiesPage(${pagination.current_page + 1})" ${pagination.current_page === pagination.last_page ? 'disabled' : ''}>Next</button>`;
        
        paginationDiv.innerHTML = buttonsHTML;
        facilitiesList.appendChild(paginationDiv);
    }
    
    function addEquipmentPagination(pagination) {
        const equipmentList = document.getElementById('equipmentList');
        if (!equipmentList) return;
        
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'd-flex justify-content-center align-items-center gap-2 mt-3 pt-2 border-top';
        paginationDiv.style.gridColumn = '1 / -1';
        
        let buttonsHTML = '';
        
        buttonsHTML += `<button class="btn btn-sm btn-outline-secondary" onclick="changeEquipmentPage(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>Previous</button>`;
        
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                buttonsHTML += `<button class="btn btn-sm ${i === pagination.current_page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="changeEquipmentPage(${i})">${i}</button>`;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                buttonsHTML += `<span class="px-1">...</span>`;
            }
        }
        
        buttonsHTML += `<button class="btn btn-sm btn-outline-secondary" onclick="changeEquipmentPage(${pagination.current_page + 1})" ${pagination.current_page === pagination.last_page ? 'disabled' : ''}>Next</button>`;
        
        paginationDiv.innerHTML = buttonsHTML;
        equipmentList.appendChild(paginationDiv);
    }
    
    window.changeFacilitiesPage = function(page) {
        if (page < 1 || page > facilitiesTotalPages) return;
        loadFacilities(page);
    };
    
    window.changeEquipmentPage = function(page) {
        if (page < 1 || page > equipmentTotalPages) return;
        loadEquipment(page);
    };
    
    function renderEquipmentList(equipment, pagination) {
        const equipmentList = document.getElementById('equipmentList');
        if (!equipmentList) return;

        if (equipment.length === 0) {
            equipmentList.innerHTML = '<div class="text-center text-muted py-3">No equipment match your filters</div>';
            return;
        }

        equipmentList.innerHTML = '';
        equipment.forEach(equip => {
            let isAvailable = false;
            let statusName = 'Unknown';
            let statusColor = '#6c757d';

            if (equip.status_name) {
                statusName = equip.status_name;
                isAvailable = equip.status_name === 'Available';
                statusColor = isAvailable ? '#28a745' :
                    (equip.status_name === 'Unavailable' ? '#dc3545' :
                        (equip.status_name === 'Under Maintenance' ? '#ffc107' :
                            (equip.status_name === 'Reserved' ? '#007bff' : '#6c757d')));
            } else if (equip.status && equip.status.status_name) {
                statusName = equip.status.status_name;
                isAvailable = equip.status.status_name === 'Available';
                statusColor = equip.status.color_code || '#6c757d';
            }

            const div = document.createElement('div');
            div.className = `equipment-card ${!isAvailable ? 'disabled' : ''}`;
            div.innerHTML = `
                <input type="checkbox" class="equipment-checkbox" value="${equip.equipment_id}"
                       data-name="${equip.equipment_name.replace(/'/g, "\\'")}" data-fee="${equip.base_fee}"
                       data-rate-type="${equip.rate_type}" ${!isAvailable ? 'disabled' : ''}>
                <strong>${escapeHtml(equip.equipment_name)}</strong>
                <div><small>₱${parseFloat(equip.base_fee).toLocaleString()} ${equip.rate_type === 'Per Hour' ? '/hour' : '/event'}</small></div>
                ${equip.description && equip.description !== 'No description provided for this equipment.' ?
                    `<div><small class="text-muted">${escapeHtml(equip.description.substring(0, 100))}</small></div>` : ''}
                <div><span class="badge" style="background-color: ${statusColor}; color: white;">${statusName}</span></div>
            `;

            if (isAvailable) {
                div.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const checkbox = div.querySelector('.equipment-checkbox');
                    checkbox.checked = !checkbox.checked;
                    div.classList.toggle('selected', checkbox.checked);
                    updateSelectedCounts();
                });
            }

            equipmentList.appendChild(div);
        });
        
        if (pagination && pagination.last_page > 1) {
            addEquipmentPagination(pagination);
        }
        
        updateSelectedCounts();
    }

    function setupFacilityFilters() {
        const facilitySearch = document.getElementById('facilitySearch');
        const facilityRateFilter = document.getElementById('facilityRateFilter');
        const facilityStatusFilter = document.getElementById('facilityStatusFilter');

        const applyFilters = () => {
            facilitiesFilters = {
                search: facilitySearch?.value.toLowerCase() || '',
                rate_type: facilityRateFilter?.value || '',
                status: facilityStatusFilter?.value || ''
            };
            facilitiesPage = 1;
            loadFacilities(1);
        };

        if (facilitySearch) facilitySearch.addEventListener('input', applyFilters);
        if (facilityRateFilter) facilityRateFilter.addEventListener('change', applyFilters);
        if (facilityStatusFilter) facilityStatusFilter.addEventListener('change', applyFilters);
    }

    function setupEquipmentFilters() {
        const equipmentSearch = document.getElementById('equipmentSearch');
        const equipmentRateFilter = document.getElementById('equipmentRateFilter');
        const equipmentStatusFilter = document.getElementById('equipmentStatusFilter');

        const applyFilters = () => {
            equipmentFilters = {
                search: equipmentSearch?.value.toLowerCase() || '',
                rate_type: equipmentRateFilter?.value || '',
                status: equipmentStatusFilter?.value || ''
            };
            equipmentPage = 1;
            loadEquipment(1);
        };

        if (equipmentSearch) equipmentSearch.addEventListener('input', applyFilters);
        if (equipmentRateFilter) equipmentRateFilter.addEventListener('change', applyFilters);
        if (equipmentStatusFilter) equipmentStatusFilter.addEventListener('change', applyFilters);
    }
    
    function renderServicesList() {
        const searchTerm = document.getElementById('serviceSearch')?.value.toLowerCase() || '';

        const filteredServices = allServices.filter(service => {
            return !searchTerm || service.service_name.toLowerCase().includes(searchTerm);
        });

        const container = document.getElementById('extraServicesContainer');
        if (!container) return;

        if (filteredServices.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-muted py-3">No services match your search</div>';
            return;
        }

        container.innerHTML = '';
        filteredServices.forEach(service => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-lg-4 col-md-6 col-12';

            const serviceCard = document.createElement('div');
            serviceCard.className = 'service-card';
            serviceCard.innerHTML = `
                <input type="checkbox" class="service-checkbox" value="${service.service_id}"
                       data-name="${service.service_name.replace(/'/g, "\\'")}">
                <div class="fw-medium">${escapeHtml(service.service_name)}</div>
                ${service.service_fee ? `<div><small class="text-muted">₱${parseFloat(service.service_fee).toLocaleString()}</small></div>` : ''}
            `;

            serviceCard.addEventListener('click', (e) => {
                e.stopPropagation();
                const checkbox = serviceCard.querySelector('.service-checkbox');
                checkbox.checked = !checkbox.checked;
                serviceCard.classList.toggle('selected', checkbox.checked);
            });

            colDiv.appendChild(serviceCard);
            container.appendChild(colDiv);
        });
        
        // Re-attach select all functionality
        const selectAllCheckbox = document.getElementById('selectAllServices');
        if (selectAllCheckbox) {
            const newSelectAll = selectAllCheckbox.cloneNode(true);
            selectAllCheckbox.parentNode.replaceChild(newSelectAll, selectAllCheckbox);

            newSelectAll.addEventListener('change', function () {
                const isChecked = this.checked;
                const allServiceCards = document.querySelectorAll('.service-card');
                allServiceCards.forEach(card => {
                    const checkbox = card.querySelector('.service-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = isChecked;
                        card.classList.toggle('selected', isChecked);
                    }
                });
            });
        }
    }

    function setupServiceFilters() {
        const serviceSearch = document.getElementById('serviceSearch');
        if (serviceSearch) serviceSearch.addEventListener('input', () => renderServicesList());
    }

    function updateSelectedCounts() {
        const facilitiesCount = document.querySelectorAll('.facility-checkbox:checked').length;
        const equipmentCount = document.querySelectorAll('.equipment-checkbox:checked').length;

        const facilitiesCountSpan = document.getElementById('selectedFacilitiesCount');
        const equipmentCountSpan = document.getElementById('selectedEquipmentCount');

        if (facilitiesCountSpan) facilitiesCountSpan.textContent = facilitiesCount;
        if (equipmentCountSpan) equipmentCountSpan.textContent = equipmentCount;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize user type toggle
    function initializeUserTypeToggle() {
        const userTypeSelect = document.querySelector('select[name="user_type"]');
        const schoolIdInput = document.getElementById('schoolIdInput');
        const organizationInput = document.getElementById('organizationInput');
        if (!userTypeSelect) return;

        const handleUserTypeChange = function () {
            if (this.value === 'Internal') {
                schoolIdInput.disabled = false;
                schoolIdInput.setAttribute('required', 'required');
                schoolIdInput.placeholder = "e.g., 2015-12345";
                schoolIdInput.classList.remove('bg-light');
                organizationInput.disabled = false;
                organizationInput.removeAttribute('required');
            } else if (this.value === 'External') {
                schoolIdInput.disabled = true;
                schoolIdInput.removeAttribute('required');
                schoolIdInput.value = '';
                schoolIdInput.placeholder = "For internal users only";
                schoolIdInput.classList.add('bg-light');
                organizationInput.disabled = false;
                organizationInput.removeAttribute('required');
            } else {
                schoolIdInput.disabled = true;
                schoolIdInput.removeAttribute('required');
                organizationInput.disabled = false;
                organizationInput.removeAttribute('required');
            }
            if (typeof validateCurrentStep === 'function') validateCurrentStep();
        };
        userTypeSelect.addEventListener('change', handleUserTypeChange);
        if (!userTypeSelect.value || userTypeSelect.value === '') userTypeSelect.value = 'External';
        setTimeout(() => userTypeSelect.dispatchEvent(new Event('change')), 100);
    }
    
    // Save reservation
    async function saveReservation() {
        const form = document.getElementById('addReservationForm');
        const confirmBtn = document.getElementById('submitReservationBtn');

        if (confirmBtn.disabled) return;

        const originalText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width: 1rem; height: 1rem;"></span> Creating...';
        confirmBtn.classList.remove('btn-loading');

        const statusSelect = document.getElementById('initialStatusSelect');
        const statusId = statusSelect ? parseInt(statusSelect.value) : 1;
        const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

        const resetButtonState = () => {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        };

        try {
            const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
            const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
            const email = document.querySelector('input[name="email"]')?.value.trim() || '';
            const contactNumber = document.querySelector('input[name="contact_number"]')?.value || '';
            const organizationName = document.querySelector('input[name="organization_name"]')?.value || '';
            const schoolId = document.querySelector('input[name="school_id"]')?.value || '';
            const userType = document.querySelector('select[name="user_type"]')?.value;
            const additionalRequests = document.querySelector('textarea[name="additional_requests"]')?.value || '';
            const calendarTitle = document.querySelector('input[name="event_title"]')?.value || '';
            const calendarDescription = document.querySelector('textarea[name="event_details"]')?.value || '';
            const purposeSelect = document.getElementById('purposeSelect');
            const purposeId = purposeSelect?.value ? parseInt(purposeSelect.value) : null;
            const numParticipants = parseInt(document.querySelector('input[name="num_participants"]')?.value || 1);
            const numTables = parseInt(document.querySelector('input[name="num_tables"]')?.value || 0);
            const numChairs = parseInt(document.querySelector('input[name="num_chairs"]')?.value || 0);
            const startDate = document.getElementById('startDate')?.value;
            const endDate = document.getElementById('endDate')?.value;
            let startTime = document.getElementById('startTime')?.value;
            let endTime = document.getElementById('endTime')?.value;

            const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => ({
                facility_id: parseInt(cb.value)
            }));

            const selectedEquipment = Array.from(document.querySelectorAll('.equipment-checkbox:checked')).map(cb => ({
                equipment_id: parseInt(cb.value),
                quantity: 1
            }));

            const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => ({
                service_id: parseInt(cb.value)
            }));

            const numMicrophones = parseInt(document.querySelector('input[name="num_microphones"]')?.value || 0);

            const validationErrors = [];
            if (!firstName) validationErrors.push('First name is required');
            if (!lastName) validationErrors.push('Last name is required');
            if (!email) validationErrors.push('Email is required');
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) validationErrors.push('Email format is invalid');
            if (!purposeId || isNaN(purposeId) || purposeId <= 0) validationErrors.push('Please select a valid purpose');
            if (!startDate || !endDate) validationErrors.push('Start and end dates are required');
            if (!userType) validationErrors.push('User type is required');
            if (userType === 'Internal' && !schoolId) validationErrors.push('School ID is required for internal users');
            if (selectedFacilities.length === 0 && selectedEquipment.length === 0) {
                validationErrors.push('Please select at least one facility or equipment item');
            }

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
                first_name: firstName,
                last_name: lastName,
                email: email,
                contact_number: contactNumber || null,
                organization_name: organizationName || null,
                school_id: schoolId || null,
                user_type: userType,
                additional_requests: additionalRequests || null,
                event_title: calendarTitle || null,
                event_details: calendarDescription || null,
                facilities: selectedFacilities,
                equipment: selectedEquipment,
                services: selectedServices
            };

            const response = await fetch('/api/admin/requisition/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${adminToken}`,
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(reservationData)
            });

            const result = await response.json();
            if (!response.ok) {
                if (result.details) {
                    const apiErrors = Object.values(result.details).flat().join('\n• ');
                    throw new Error(`Validation failed:\n• ${apiErrors}`);
                }
                throw new Error(result.message || result.error || `HTTP Error ${response.status}: Failed to create reservation`);
            }

            if (result && (result.request_id || result.message)) {
                showToast('Reservation created successfully!', 'success');
                resetButtonState();
                setTimeout(() => { window.location.href = '/admin/pending-requests'; }, 1500);
            } else {
                throw new Error(result.message || 'Reservation created but received unexpected response format');
            }
        } catch (error) {
            console.error('Error saving reservation:', error);
            let errorMessage = error.message;
            if (errorMessage.includes('Validation failed:')) {
                errorMessage = errorMessage.replace('Validation failed:\n', 'Please fix the following:\n• ');
            }
            showToast(errorMessage || 'Failed to create reservation. Please try again.', 'error');
            resetButtonState();
        }
    }

    // Setup step navigation
    function setupReservationStepNavigation() {
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
                    if (userType === 'Internal') schoolIdValid = schoolId && schoolId.length > 0;
                    isValid = userType && firstName && lastName && email && schoolIdValid;
                    break;
                case 2:
                    const purposeSelect = document.getElementById('purposeSelect');
                    const purposeValue = purposeSelect?.value;
                    const isPlaceholderSelected = purposeSelect?.selectedIndex === 0;
                    const purposeValid = !isPlaceholderSelected && purposeValue && parseInt(purposeValue) > 0;
                    const participantsValid = parseInt(document.querySelector('input[name="num_participants"]')?.value || 0) > 0;
                    isValid = purposeValid && participantsValid;
                    break;
                case 3:
                    isValid = true;
                    // LAZY LOAD: Load facilities when reaching this step
                    if (!facilitiesLoaded) {
                        loadFacilities(1);
                        setupFacilityFilters();
                    }
                    break;
                case 4:
                    isValid = true;
                    // LAZY LOAD: Load equipment when reaching this step
                    if (!equipmentLoaded) {
                        loadEquipment(1);
                        setupEquipmentFilters();
                    }
                    break;
                case 5:
                    isValid = true;
                    break;
                case 6:
                    const startDate = document.getElementById('startDate')?.value;
                    const endDate = document.getElementById('endDate')?.value;
                    const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;
                    if (isAllDay) {
                        isValid = startDate && endDate && new Date(endDate) >= new Date(startDate);
                    } else {
                        const startTime = document.getElementById('startTime')?.value;
                        const endTime = document.getElementById('endTime')?.value;
                        if (!startDate || !startTime || !endDate || !endTime) {
                            isValid = false;
                        } else {
                            const start = new Date(`${startDate}T${startTime}`);
                            const end = new Date(`${endDate}T${endTime}`);
                            isValid = end > start;
                        }
                    }
                    break;
                case 7:
                    const statusValid = statusSelect && statusSelect.value && statusSelect.value !== '';
                    isValid = statusValid;
                    break;
                default:
                    isValid = false;
            }

            if (nextBtn) nextBtn.disabled = !isValid;
            if (currentStep === totalSteps && submitBtn) submitBtn.disabled = !isValid;
            return isValid;
        }

        function updateStepDisplay() {
            document.querySelectorAll('.step').forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                if (stepNum === currentStep) step.classList.add('active');
                else step.classList.remove('active');
            });
            document.querySelectorAll('.step-content').forEach(content => {
                const stepNum = parseInt(content.dataset.step);
                if (stepNum === currentStep) content.classList.remove('d-none');
                else content.classList.add('d-none');
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
            document.getElementById('reviewUserType').textContent = document.querySelector('select[name="user_type"]')?.value || '-';
            document.getElementById('reviewEmail').textContent = document.querySelector('input[name="email"]')?.value || '-';
            document.getElementById('reviewContactNumber').textContent = document.querySelector('input[name="contact_number"]')?.value || 'Not provided';
            document.getElementById('reviewSchoolId').textContent = document.querySelector('input[name="school_id"]')?.value || 'Not provided';
            document.getElementById('reviewOrganization').textContent = document.querySelector('input[name="organization_name"]')?.value || 'Not provided';

            const purposeSelect = document.getElementById('purposeSelect');
            document.getElementById('reviewPurpose').textContent = purposeSelect?.options[purposeSelect.selectedIndex]?.text || '-';
            document.getElementById('reviewParticipants').textContent = document.querySelector('input[name="num_participants"]')?.value || '0';
            document.getElementById('reviewFurniture').textContent = `${document.querySelector('input[name="num_tables"]')?.value || 0} tables, ${document.querySelector('input[name="num_chairs"]')?.value || 0} chairs`;
            document.getElementById('reviewMicrophones').textContent = document.querySelector('input[name="num_microphones"]')?.value || '0';
            document.getElementById('reviewEventTitle').textContent = document.querySelector('input[name="event_title"]')?.value || 'Not provided';
            document.getElementById('reviewEventDetails').textContent = document.querySelector('textarea[name="event_details"]')?.value || 'Not provided';
            document.getElementById('reviewAdditionalRequests').textContent = document.querySelector('textarea[name="additional_requests"]')?.value || 'None';

            const statusSelect = document.getElementById('initialStatusSelect');
            document.getElementById('reviewStatus').textContent = statusSelect?.options[statusSelect.selectedIndex]?.text || 'Scheduled';
            
            const startDate = document.getElementById('startDate')?.value;
            const endDate = document.getElementById('endDate')?.value;
            let startTime = document.getElementById('startTime')?.value;
            let endTime = document.getElementById('endTime')?.value;
            const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

            if (startTime) startTime = startTime.replace(/^0/, '');
            if (endTime) endTime = endTime.replace(/^0/, '');

            if (startDate && endDate) {
                const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
                const startDateObj = new Date(startDate + 'T12:00:00');
                const endDateObj = new Date(endDate + 'T12:00:00');

                if (isAllDay) {
                    if (startDate === endDate) {
                        document.getElementById('reviewSchedule').textContent = `${startDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
                    } else {
                        document.getElementById('reviewSchedule').textContent = `${startDateObj.toLocaleDateString('en-US', dateOptions)} - ${endDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
                    }
                } else if (startTime && endTime) {
                    const formatTime = (timeStr) => {
                        const [hour, minute] = timeStr.split(':');
                        let hourNum = parseInt(hour, 10);
                        const ampm = hourNum >= 12 ? 'PM' : 'AM';
                        hourNum = hourNum % 12 || 12;
                        return `${hourNum}:${minute} ${ampm}`;
                    };
                    const startFormatted = formatTime(startTime);
                    const endFormatted = formatTime(endTime);
                    document.getElementById('reviewSchedule').textContent = `${startDateObj.toLocaleDateString('en-US', dateOptions)} ${startFormatted} to ${endDateObj.toLocaleDateString('en-US', dateOptions)} ${endFormatted}`;
                }
            }

            if (startDate && endDate && startTime && endTime && !isAllDay) {
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
                    document.getElementById('reviewDuration').textContent = durationText || '0 minutes';
                } else {
                    document.getElementById('reviewDuration').textContent = '-';
                }
            } else if (isAllDay && startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                document.getElementById('reviewDuration').textContent = `${days} day${days > 1 ? 's' : ''}`;
            } else {
                document.getElementById('reviewDuration').textContent = '-';
            }
            
            const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked')).map(cb => cb.dataset.name || cb.value);
            document.getElementById('reviewFacilities').textContent = selectedFacilities.length > 0 ? selectedFacilities.join(', ') : 'None selected';
            const selectedEquipment = Array.from(document.querySelectorAll('.equipment-checkbox:checked')).map(cb => cb.dataset.name || cb.value);
            document.getElementById('reviewEquipment').textContent = selectedEquipment.length > 0 ? selectedEquipment.join(', ') : 'None selected';
            const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked')).map(cb => cb.dataset.name || cb.value);
            const reviewServices = document.getElementById('reviewServices');
            if (reviewServices) reviewServices.textContent = selectedServices.length > 0 ? selectedServices.join(', ') : 'None selected';
        }

        prevBtn.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateStepDisplay(); } });
        nextBtn.addEventListener('click', () => { if (validateCurrentStep() && currentStep < totalSteps) { currentStep++; updateStepDisplay(); } });
        document.querySelectorAll('#addReservationForm input, #addReservationForm select, #addReservationForm textarea').forEach(element => {
            element.addEventListener('change', () => { validateCurrentStep(); if (currentStep === totalSteps) updateReviewSummary(); });
            if (element.type === 'number' || element.tagName === 'SELECT') {
                element.addEventListener('input', () => { validateCurrentStep(); if (currentStep === totalSteps) updateReviewSummary(); });
            }
        });
        updateStepDisplay();
        return { resetSteps: () => { currentStep = 1; updateStepDisplay(); }, validateCurrentStep };
    }

    // DOM Content Loaded
    document.addEventListener('DOMContentLoaded', async function () {
        const token = localStorage.getItem('adminToken');
        if (!token) {
            window.location.href = "/admin/login";
            return;
        }
        adminToken = token;

        const loadingContainer = document.getElementById('loadingContainer');
        const formContent = document.getElementById('formContent');

        if (loadingContainer) loadingContainer.style.display = 'flex';
        if (formContent) formContent.style.opacity = '0.3';
        if (formContent) formContent.style.pointerEvents = 'none';

        const nextBtn = document.getElementById('nextStepBtn');
        const prevBtn = document.getElementById('prevStepBtn');
        const submitBtn = document.getElementById('submitReservationBtn');

        if (nextBtn) nextBtn.disabled = true;
        if (prevBtn) prevBtn.disabled = true;
        if (submitBtn) submitBtn.disabled = true;

        document.querySelector('.steps')?.classList.add('step-nav-disabled');

        try {
            await loadFormInitData();

            if (loadingContainer) loadingContainer.style.display = 'none';
            if (formContent) {
                formContent.style.opacity = '1';
                formContent.style.pointerEvents = 'auto';
            }

            if (nextBtn) nextBtn.disabled = false;
            if (prevBtn) prevBtn.disabled = false;
            if (submitBtn) submitBtn.disabled = false;

            document.querySelector('.steps')?.classList.remove('step-nav-disabled');

            if (window.stepManager && window.stepManager.validateCurrentStep) {
                window.stepManager.validateCurrentStep();
            }

        } catch (error) {
            console.error('Failed to load form data:', error);
            if (loadingContainer) loadingContainer.style.display = 'none';
            if (formContent) {
                formContent.style.opacity = '1';
                formContent.style.pointerEvents = 'auto';
            }
            showToast('Failed to load form data. Please refresh the page.', 'error', 5000);
        }

        populateTimeDropdowns();
        setupCharacterCounters();
        initializeUserTypeToggle();

        document.getElementById('allDayCheckbox')?.addEventListener('change', function () {
            const startTimeSelect = document.getElementById('startTime');
            const endTimeSelect = document.getElementById('endTime');
            const allDayIndicator = document.getElementById('allDayScheduleIndicator');
            if (this.checked) {
                startTimeSelect.disabled = true;
                endTimeSelect.disabled = true;
                startTimeSelect.value = '00:00';
                endTimeSelect.value = '00:00';
                if (allDayIndicator) allDayIndicator.classList.remove('d-none');
                startTimeSelect.classList.add('bg-light');
                endTimeSelect.classList.add('bg-light');
            } else {
                startTimeSelect.disabled = false;
                endTimeSelect.disabled = false;
                startTimeSelect.value = '09:00';
                endTimeSelect.value = '17:00';
                if (allDayIndicator) allDayIndicator.classList.add('d-none');
                startTimeSelect.classList.remove('bg-light');
                endTimeSelect.classList.remove('bg-light');
            }
            calculateDuration();
            if (typeof validateCurrentStep === 'function') validateCurrentStep();
        });

        document.getElementById('startDate')?.addEventListener('change', calculateDuration);
        document.getElementById('startTime')?.addEventListener('change', calculateDuration);
        document.getElementById('endDate')?.addEventListener('change', calculateDuration);
        document.getElementById('endTime')?.addEventListener('change', calculateDuration);
        document.getElementById('submitReservationBtn')?.addEventListener('click', saveReservation);

        window.stepManager = setupReservationStepNavigation();
    });
</script>
@endsection