{{-- Add Reservation Modal --}}
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
                                            This will be displayed as an <strong>all-day event</strong> on the calendar with times set to 12:00 AM.
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
                                        <small class="text-muted mt-1 d-block">Unique 10-character code for event access. Click generate to create.</small>
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