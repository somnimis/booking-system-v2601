<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>
    Reservation Form Submission
  </title>
  <link rel="stylesheet" href="{{ asset('css/public/reservation-form.css') }}" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css"
    rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css"
    rel="stylesheet" />

</head>

<body>

  @include('partials.navbar')
  <main class="flex-grow-1">
    <div class="container main-content">
      <form id="reservationForm" method="POST">
        @csrf

        <!-- Complete Your Reservation Section -->
        <div class="row">
          <div class="col-12">
            <style>
              .btn-transparent {
                background-color: transparent !important;
                border: none !important;
                box-shadow: none !important;
              }

              .btn-transparent i {
                display: inline-block;
                color: #6c757d;
                transition: transform 0.25s ease-in-out;
              }

              button.btn-transparent[aria-expanded="true"] i.bi-chevron-down {
                transform: rotate(0deg);
              }

              button.btn-transparent[aria-expanded="false"] i.bi-chevron-down {
                transform: rotate(180deg);
              }

              .step-section {
                display: none;
              }

              .step-section.active {
                display: block;
              }

              .navigation-buttons {
                display: flex;
                justify-content: space-between;
                padding: 15px;
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                margin-bottom: 1rem;
              }
            </style>

            <div class="form-section-card">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Complete Your Reservation</h5>
                <button id="toggleReservationBtn" type="button" class="btn btn-sm btn-secondary btn-transparent"
                  style="height: 100%; align-self: center" data-bs-toggle="collapse"
                  data-bs-target="#reservationContent" aria-expanded="true" aria-controls="reservationContent">
                  <i class="bi bi-chevron-down"></i>
                </button>
              </div>

              <div id="reservationContent" class="collapse show" style="padding-top: 10px">
                <p class="text-muted">
                  To confirm your request, please fill out the necessary details below.
                  We need this information to process your booking efficiently and provide
                  complete details on how to proceed. A confirmation email will be sent
                  to your registered email address once your submission is reviewed and approved.
                </p>
                <div class="d-flex justify-content-start gap-2">
                  <a href="policies" class="btn btn-primary">Reservation Policies</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 1: Requested Items & Booking Schedule -->
        <div class="step-section active" id="step1">
          <div class="row">
            <div class="col-md-6">
              <div class="form-section-card" style="height: 400px; overflow-y: auto;">
                <!-- Requested Facilities -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="mb-0">Requested Facilities</h5>
                  <a id="addFacilityBtn" href="#" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                    onclick="event.preventDefault(); openFacilityModal();">
                    <i class="bi bi-plus"></i>
                    <span>Add item</span>
                  </a>

                </div>
                <div id="facilityList" class="selected-items-container mb-3">
                  <div class="text-muted empty-message">No facilities added yet.</div>
                </div>

                <!-- Requested Equipment -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="mb-0">Requested Equipment</h5>
                  <a id="addEquipmentBtn" href="#"
                    class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                    onclick="event.preventDefault(); openEquipmentModal();">
                    <i class="bi bi-plus"></i>
                    <span>Add item</span>
                  </a>
                </div>
                <div id="equipmentList" class="selected-items-container">
                  <!-- Equipment items will be dynamically added here -->
                  <div class="text-muted empty-message">No equipment added yet.</div>
                </div>
              </div>
            </div>


            <div class="col-md-6">
              <div class="form-section-card flex-grow-1" style="height: 400px; overflow-y: auto; padding-bottom: 15px;">
                <div class="d-flex justify-content-between align-items-center">
                  <h5>Step 1: Booking Schedule</h5>
                </div>
                <p id="selectedDateTime" class="text-muted">
                  Add items to form first in order to check schedule availability.
                </p>
                <div class="row">
                  <div class="col-md-6">
                    <label for="startDateField" class="form-label">Start Date</label>
                    <input name="start_date" type="date" id="startDateField" class="form-control mb-2" />
                  </div>
                  <div class="col-md-6">
                    <label for="startTimeField" class="form-label">Start Time</label>
                    <select id="startTimeField" name="start_time" class="form-select mb-2" onchange="adjustEndTime()">
                      <!-- Options generated by JavaScript -->
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <label for="endDateField" class="form-label">End Date</label>
                    <input name="end_date" type="date" id="endDateField" class="form-control mb-2" />
                  </div>
                  <div class="col-md-6">
                    <label for="endTimeField" class="form-label">End Time</label>
                    <select id="endTimeField" name="end_time" class="form-select mb-3">
                      <!-- Options generated by JavaScript -->
                    </select>
                  </div>
                </div>
                <div class="d-flex justify-content-start gap-2">
                  <button id="clearSelectionBtn" class="btn btn-outline-secondary">
                    Clear Selection
                  </button>
                  <button id="checkAvailabilityBtn" type="button" class="btn btn-primary" onclick="checkAvailability()">
                    Check Availability
                  </button>
                  <span id="availabilityResult" style="margin-left: 1px; font-weight: bold;"></span>
                </div>
                <p class="text-muted mt-4" style="font-size: 0.875rem;">
                  In case of emergency, please ensure to cancel reservations at least 5 days before the scheduled date
                  to
                  avoid complications.
                </p>

              </div>
            </div>
          </div>

          <!-- Navigation Buttons for Step 1 -->
          <div class="navigation-buttons">
            <button type="button" class="btn btn-secondary" disabled>Previous</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(2)">Next</button>
          </div>
        </div>

        <!-- Step 2: Contact Information & Reservation Details -->
        <div class="step-section" id="step2">
          <!-- Contact Information Card -->
          <div class="row mb-2">
            <div class="col-12">
              <div class="form-section-card">
                <h5>Step 2: Your Contact Information</h5>
                <div class="row">
                  <div class="col-md-4">
                    <label class="form-label">Applicant Type <span style="color: red;">*</span></label>
                    <select id="applicantType" name="user_type" class="form-select mb-2" aria-label="Type of Applicant"
                      required>
                      <option value="" selected disabled>Type of Applicant</option>
                      <option value="Internal">Internal</option>
                      <option value="External">External</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">First Name <span style="color: red;">*</span></label>
                    <input name="first_name" type="text" class="form-control" placeholder="First Name" required
                      maxlength="50" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Last Name <span style="color: red;">*</span></label>
                    <input name="last_name" type="text" class="form-control" placeholder="Last Name" required
                      maxlength="50" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">CPU School ID <span id="schoolIdRequired"
                        style="color:red;display:none">*</span></label>
                    <input name="school_id" id="school_id" type="text" class="form-control" placeholder="School ID"
                      maxlength="20" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input name="contact_number" type="text" class="form-control" placeholder="Contact Number"
                      maxlength="15" pattern="\d{1,15}" inputmode="numeric" id="contactNumberField"
                      autocomplete="off" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Email Address <span style="color: red;">*</span></label>
                    <input name="email" type="email" class="form-control mb-2" placeholder="Email Address" required
                      maxlength="100" />
                  </div>
                  <div class="col-md-12">
                    <label class="form-label">Department/Organization Name</label>
                    <input name="organization_name" type="text" class="form-control mb-2"
                      placeholder="Organization Name" maxlength="100" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reservation Details Card -->
          <div class="row">
            <div class="col-12">
              <div class="form-section-card">
                <h5>Step 3: Reservation Details</h5>
                <div class="row g-3">
                  <!-- Event Title -->
                  <div class="col-md-6">
                    <label class="form-label required">Event Title</label>
                    <input name="event_title" type="text" class="form-control"
                      placeholder="e.g., University Day Celebration" required maxlength="100" />
                  </div>

                  <!-- Activity/Purpose -->
                  <div class="col-md-6">
                    <label class="form-label required">Activity/Purpose</label>
                    <select id="activityPurposeField" name="purpose_id" class="form-select"
                      aria-label="Activity/Purpose" required>
                      <option value="" selected disabled>Select Activity/Purpose</option>
                      <option value="8">Alumni - Class Reunion</option>
                      <option value="9">Alumni - Personal Events</option>
                      <option value="7">Alumni-Organized Events</option>
                      <option value="5">CPU Organization Led Activity</option>
                      <option value="2">Equipment Rental</option>
                      <option value="10">External Event</option>
                      <option value="1">Facility Rental</option>
                      <option value="6">Student-Organized Activity</option>
                      <option value="3">Subject Requirement - Class, Seminar, Conference</option>
                      <option value="4">University Program/Activity</option>
                    </select>
                  </div>

                  <!-- Event Details -->
                  <div class="col-md-12">
                    <label class="form-label">Event Details</label>
                    <textarea name="event_details" class="form-control" rows="3" maxlength="500"
                      placeholder="Provide more details about your event (optional)"></textarea>
                  </div>

                  <!-- Attach Event Documents -->
                  <div class="col-md-12">
                    <label class="form-label">Attach Event Documents</label>
                    <div class="position-relative">
                      <input type="file" class="form-control" id="eventDocuments" onchange="uploadToCloudinary(this)" />
                      <input type="hidden" name="event_documents_url" id="event_documents_url">
                      <input type="hidden" name="event_documents_public_id" id="event_documents_public_id">
                      <button type="button" id="removeEventDocumentsBtn"
                        class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 d-none"
                        style="color: black; background: none; border: none"
                        onclick="removeFile('eventDocuments', 'removeEventDocumentsBtn')">
                        x
                      </button>
                    </div>
                    <div id="uploadProgress" class="progress mt-2 d-none">
                      <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Upload supporting documents (PDF, DOC, or image files)</small>
                  </div>

                  <!-- Number of Participants, Chairs, Tables, and Microphones -->
                  <div class="col-md-3">
                    <label class="form-label required">Participants</label>
                    <input name="num_participants" type="number" class="form-control" value="1" min="1" required />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label required">Chairs</label>
                    <input name="num_chairs" type="number" class="form-control" value="0" min="0" required />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label required">Tables</label>
                    <input name="num_tables" type="number" class="form-control" value="0" min="0" required />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label required">Microphones</label>
                    <input name="num_microphones" type="number" class="form-control" value="0" min="0" required />
                  </div>

                  <!-- Additional Requests -->
                  <div class="col-12">
                    <label class="form-label">Additional Requests</label>
                    <textarea name="additional_requests" class="form-control" rows="3" maxlength="250"
                      placeholder="Write a brief description of any additional requests you may have (e.g., WiFi, special seating arrangement, security personnel, technical support, logistics, etc.)."></textarea>
                  </div>

                  <!-- Extra Services Needed -->
                  <div class="col-12">
                    <label class="form-label mb-2">Extra Resources or Services Needed</label>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="1"
                              id="service_projector">
                            <label class="form-check-label" for="service_projector">Projector</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="2"
                              id="service_screen">
                            <label class="form-check-label" for="service_screen">Projection Screen</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="3"
                              id="service_sound">
                            <label class="form-check-label" for="service_sound">Sound Reinforcement System</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="4"
                              id="service_led">
                            <label class="form-check-label" for="service_led">LED Wall</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="5"
                              id="service_electrical">
                            <label class="form-check-label" for="service_electrical">Electrical</label>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="6"
                              id="service_internet">
                            <label class="form-check-label" for="service_internet">Internet Connection</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="7"
                              id="service_plants">
                            <label class="form-check-label" for="service_plants">Plants for Decoration</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="8"
                              id="service_platform">
                            <label class="form-check-label" for="service_platform">Platform</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="9"
                              id="service_security">
                            <label class="form-check-label" for="service_security">Security Guard</label>
                          </div>
                          <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="extra_services[]" value="10"
                              id="service_emergency">
                            <label class="form-check-label" for="service_emergency">Emergency Response Team</label>
                          </div>
                        </div>
                      </div>
                    </div>
                    <small class="text-muted mt-2 d-block">Select any additional resource/services you need for your
                      event.</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons for Step 2 -->
          <div class="navigation-buttons">
            <button type="button" class="btn btn-secondary" onclick="previousStep(1)">Previous</button>
            <button type="button" class="btn btn-primary" onclick="nextStep(3)">Next</button>
          </div>
        </div>

        <!-- Step 3: Form Summary -->
        <div class="step-section" id="step3">
          <!-- Requisition Summary Card with two-column layout -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="form-section-card">
                <!-- Centered Title -->
                <h5 class="fw-bold text-center mb-2">Requisition Summary</h5>

                <!-- Warning/Info message below title -->
                <small class="d-block text-center text-muted mb-4">
                  Please review all information carefully. Submitted requests cannot be edited.
                </small>

                <!-- Two column layout: Left side for Contact Info & Reservation Details, Right side for Fee Breakdown -->
                <div class="row">
                  <!-- LEFT COLUMN - Contact Information and Reservation Details -->
                  <div class="col-md-7">
                    <!-- Contact Information Row -->
                    <div class="row mb-4">
                      <div class="col-12">
                        <h6 class="border-bottom pb-2">Contact Information</h6>
                        <div class="summary-item">
                          <strong>Applicant Type:</strong>
                          <span id="summary-applicant-type"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Name:</strong>
                          <span id="summary-name"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Email:</strong>
                          <span id="summary-email"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Contact Number:</strong>
                          <span id="summary-contact"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Organization:</strong>
                          <span id="summary-organization"></span>
                        </div>
                        <div class="summary-item" id="summary-school-id-container">
                          <strong>School ID:</strong>
                          <span id="summary-school-id"></span>
                        </div>
                      </div>
                    </div>

                    <!-- Reservation Details Row -->
                    <div class="row">
                      <div class="col-12">
                        <h6 class="border-bottom pb-2">Reservation Details</h6>
                        <div class="summary-item">
                          <strong>Event Title:</strong>
                          <span id="summary-event-title"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Activity/Purpose:</strong>
                          <span id="summary-purpose"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Event Details:</strong>
                          <span id="summary-event-details"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Start Date & Time:</strong>
                          <span id="summary-start"></span>
                        </div>
                        <div class="summary-item">
                          <strong>End Date & Time:</strong>
                          <span id="summary-end"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Participants:</strong>
                          <span id="summary-participants"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Furniture & Equipment:</strong>
                          <span id="summary-furniture"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Additional Requests:</strong>
                          <span id="summary-requests"></span>
                        </div>
                        <div class="summary-item">
                          <strong>Extra Services:</strong>
                          <span id="summary-services"></span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- RIGHT COLUMN - Fee Breakdown (No scroll, expands naturally) -->
                  <div class="col-md-5">
                    <div class="border rounded p-3 bg-light" style="height: 100%;">
                      <h6 class="border-bottom pb-2 mb-3">Fee Breakdown</h6>
                      <div id="summary-fees" style="min-height: 200px;">
                        <!-- Fees will be dynamically populated -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons for Step 3 -->
          <div class="navigation-buttons">
            <button type="button" class="btn btn-secondary" onclick="previousStep(2)">Previous</button>
            <button type="button" class="btn btn-primary" onclick="openTermsModal(event)">Submit Form</button>
          </div>
        </div>

        <!-- Success Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header" style="padding: 0.25rem 1rem;">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                  style="width: 1rem; height: 1rem; margin-top: 0.2rem;"></button>
              </div>
              <div class="modal-body text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Request Submitted Successfully!</h5>
                <small class="text-muted d-block mt-2">
                  A confirmation email has been sent to <span id="userEmail" class="fw-bold"></span>.
                </small>
                <small class="text-muted d-block">
                  Please monitor your email for updates on your request status.
                </small>
                <div id="successDetails" class="mt-3 p-3 bg-light rounded text-start"></div>
              </div>
              <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" onclick="window.location.href='{{ asset('home') }}'">
                  Back to Home
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Conflict Items Modal -->
        <div class="modal fade" id="conflictModal" tabindex="-1" aria-labelledby="conflictModalLabel"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="conflictModalLabel">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  Scheduling Conflict Detected
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p class="text-muted mb-3">
                  The selected time slot conflicts with the following items that are already booked:
                </p>

                <div id="conflictItemsList" class="mb-3">
                  <!-- Conflict items will be dynamically inserted here -->
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  Close
                </button>
                <button type="button" class="btn btn-primary"
                  onclick="window.location.href='{{ asset("booking-catalog") }}'">
                  View Catalog
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content text-center">
              <div class="modal-header">
                <h5 class="modal-title text-primary mb-0" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                  style="width: 1rem; height: 1rem; margin-top: 0.2rem;"></button>
              </div>



              <div class="modal-body">
                <div class="terms-content mb-0 mx-auto" style="max-height: 50vh; overflow-y: auto; text-align: center;">
                  <small class="d-block text-start mb-3" style="padding-left: 17px;">
                    By using our booking service, you agree to comply with the following terms and conditions, as well
                    as all
                    campus policies set by Central Philippine University (CPU):
                  </small>


                  <ol class="text-start">
                    <li>
                      <strong class="text-primary">Approval Process</strong>
                      <small>All booking requests are subject to review and approval by the CPU Administration.
                        Submission of
                        a
                        requisition form does not guarantee approval.</small>
                    </li>

                    <li>
                      <strong class="text-primary">Confirmation and Payment</strong>
                      <small>
                        Requesters will receive a confirmation email after submitting their form. Once the booking has
                        been
                        reviewed and approved, a follow-up notification will be sent containing finalized booking
                        details and
                        payment instructions. All payments must be settled
                        <span class="fw-bold">in person</span> at the CPU Business Office within
                        <span class="fw-bold">three (3) business days</span> after approval.
                      </small>
                    </li>

                    <li>
                      <strong class="text-primary">Cancellations</strong>
                      <small>
                        Requesters may cancel their booking <span class="fw-bold">up to five (5) days before the
                          scheduled
                          event</span>
                        through the system using the access code provided via email after submission. Cancellations made
                        beyond
                        this period may not be honored.
                      </small>
                    </li>


                    <li>
                      <strong class="text-primary">Facility and Equipment Responsibility</strong>
                      <small>Requesters are responsible for the proper use and care of all facilities and equipment. Any
                        damage,
                        loss, or misuse may incur corresponding repair or replacement fees.</small>
                    </li>

                    <li>
                      <strong class="text-primary">Return Policy and Penalties</strong>
                      <small>
                        All borrowed equipment must be returned within the specified booking period. A
                        <span class="fw-bold">grace period of up to 4 hours</span> after the event may be allowed for
                        clean-up
                        or coordination.
                        Failure to return items within this timeframe may result in <span class="fw-bold">late penalty
                          fees</span> or temporary suspension of booking privileges.
                      </small>
                    </li>



                    <li>
                      <strong class="text-primary">Prohibited Acts</strong>
                      <small> Alcohol consumption and smoking are strictly prohibited within the campus premises.
                        External
                        users must
                        present valid identification when required. </small>
                    </li>

                    <li>
                      <strong class="text-primary">Administrative Rights</strong>
                      <small> CPU reserves the right to cancel or revoke bookings for policy violations or
                        non-compliance with
                        these
                        terms and conditions. </small>
                    </li>
                  </ol>
                  <div class="form-check mt-4 d-flex justify-content-center">
                    <input class="form-check-input me-2" type="checkbox" id="agreeTerms">
                    <label class="form-check-label" for="agreeTerms">
                      I have read and agree to the terms and conditions.
                    </label>
                  </div>
                </div>
              </div>

              <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmSubmitBtn" class="btn btn-primary" disabled>
                  <span class="btn-text">Accept & Submit</span>
                  <span class="btn-loading">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Submitting Request...
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- Select Facilities Modal -->
        <div class="modal fade" id="facilityModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top: 45px;">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Select Facilities</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-3">
                  <div class="col-md-8">
                    <input type="text" id="facilitySearch" class="form-control" placeholder="Search facilities...">
                  </div>
                  <div class="col-md-4">
                    <select id="facilityCategoryFilter" class="form-select">
                      <option value="">All Categories</option>
                      <!-- Categories loaded dynamically -->
                    </select>
                  </div>
                </div>
                <div id="facilityListContainer" style="height: 300px; overflow-y: auto;">
                  <!-- Results loaded here -->
                </div>
                <div id="facilityPagination" class="mt-3"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="batchAddFacilitiesBtn" onclick="batchAddFacilities()">
                  <i class="bi bi-plus"></i> Add Selected (<span id="facilityCount">0</span>)
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- Select Equipment Modal -->
        <div class="modal fade" id="equipmentModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered" style="margin-top: 45px;">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Select Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-3">
                  <div class="col-md-8">
                    <input type="text" id="equipmentSearch" class="form-control" placeholder="Search equipment...">
                  </div>
                  <div class="col-md-4">
                    <select id="equipmentCategoryFilter" class="form-select">
                      <option value="">All Categories</option>
                      <!-- Categories loaded dynamically -->
                    </select>
                  </div>
                </div>
                <div id="equipmentListContainer" style="height: 250px; overflow-y: auto;">
                  <!-- Results loaded here -->
                </div>
                <div id="equipmentPagination" class="mt-3"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="batchAddEquipmentBtn" onclick="batchAddEquipment()">
                  <i class="bi bi-plus"></i> Add Selected (<span id="equipmentCount">0</span>)
                </button>
              </div>
            </div>
          </div>
        </div>
  </main>


  @include('partials.footer')
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('js/admin/toast.js') }}"></script>
  <script src="{{ asset('js/public/reservation-form.js') }}"></script>
</body>

</html>