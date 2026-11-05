<div class="container main-content mb-5" x-data="{
    openModal: null,

    // Entangle the Livewire properties so Alpine can read/write them
    userType: @entangle('userType'),
    firstName: @entangle('firstName'),
    lastName: @entangle('lastName'),
    email: @entangle('email'),
    contactNumber: @entangle('contactNumber'),
    organizationName: @entangle('organizationName'),
    schoolId: @entangle('schoolId'),
    eventTitle: @entangle('eventTitle'),
    eventDetails: @entangle('eventDetails'),
    purposeId: @entangle('purposeId'),
    numParticipants: @entangle('numParticipants'),
    numChairs: @entangle('numChairs'),
    numTables: @entangle('numTables'),
    numMicrophones: @entangle('numMicrophones'),
    additionalRequests: @entangle('additionalRequests'),
    cleanupHours: @entangle('cleanupHours'),
    startDate: @entangle('startDate'),
    endDate: @entangle('endDate'),
    startTime: @entangle('startTime'),
    endTime: @entangle('endTime'),
    allDay: @entangle('allDay'),

    // On page load, restore from localStorage
    init() {
        const saved = JSON.parse(localStorage.getItem('reservationDraft'));
        if (saved) {
            Object.keys(saved).forEach(key => {
                if (this[key] !== undefined && saved[key] !== null) {
                    this[key] = saved[key];
                }
            });
        }

        // Watch all properties and save to localStorage automatically
        this.$watch('userType', val => this.saveDraft());
        this.$watch('firstName', val => this.saveDraft());
        this.$watch('lastName', val => this.saveDraft());
        this.$watch('email', val => this.saveDraft());
        this.$watch('contactNumber', val => this.saveDraft());
        this.$watch('organizationName', val => this.saveDraft());
        this.$watch('schoolId', val => this.saveDraft());
        this.$watch('eventTitle', val => this.saveDraft());
        this.$watch('eventDetails', val => this.saveDraft());
        this.$watch('purposeId', val => this.saveDraft());
        this.$watch('numParticipants', val => this.saveDraft());
        this.$watch('numChairs', val => this.saveDraft());
        this.$watch('numTables', val => this.saveDraft());
        this.$watch('numMicrophones', val => this.saveDraft());
        this.$watch('additionalRequests', val => this.saveDraft());
        this.$watch('cleanupHours', val => this.saveDraft());
        this.$watch('startDate', val => this.saveDraft());
        this.$watch('endDate', val => this.saveDraft());
        this.$watch('startTime', val => this.saveDraft());
        this.$watch('endTime', val => this.saveDraft());
        this.$watch('allDay', val => this.saveDraft());
    },

    saveDraft() {
        const draft = {
            userType: this.userType,
            firstName: this.firstName,
            lastName: this.lastName,
            email: this.email,
            contactNumber: this.contactNumber,
            organizationName: this.organizationName,
            schoolId: this.schoolId,
            eventTitle: this.eventTitle,
            eventDetails: this.eventDetails,
            purposeId: this.purposeId,
            numParticipants: this.numParticipants,
            numChairs: this.numChairs,
            numTables: this.numTables,
            numMicrophones: this.numMicrophones,
            additionalRequests: this.additionalRequests,
            cleanupHours: this.cleanupHours,
            startDate: this.startDate,
            endDate: this.endDate,
            startTime: this.startTime,
            endTime: this.endTime,
            allDay: this.allDay
        };
        localStorage.setItem('reservationDraft', JSON.stringify(draft));
    }
}" x-init="$watch('$wire.currentStep', value => { window.scrollTo({ top: 0, behavior: 'smooth' }); })">

    <div x-on:open-terms-modal.window="openModal = 'termsModal'" x-show="false"></div>

    <form wire:submit.prevent="submit">

        <!-- STEP 1: RESOURCES & SCHEDULE -->
        <div class="step-section" id="step1" style="display: {{ $currentStep === 1 ? 'block' : 'none' }};">

            <!-- COMPLETE YOUR RESERVATION CARD -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="form-section-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Complete Your Reservation</h5>
                            <button id="toggleReservationBtn" type="button"
                                class="btn btn-sm btn-outline-secondary btn-transparent"
                                style="height: 100%; align-self: center" data-bs-toggle="collapse"
                                data-bs-target="#reservationContent" aria-expanded="true"
                                aria-controls="reservationContent">
                                <i class="bi bi-chevron-down" style="transition: transform 0.3s;"></i>
                            </button>
                        </div>

                        <div id="reservationContent" class="collapse show" style="padding-top: 10px">
                            <p class="text-muted mb-2">
                                To confirm your request, please fill out the necessary details below. We need this
                                information to
                                process
                                your booking efficiently and provide complete details on how to proceed. A confirmation
                                email will be
                                sent
                                to your registered email address once your submission is reviewed and approved.
                            </p>
                            <div class="d-flex justify-content-start gap-2">
                                <a href="policies" class="btn btn-primary">Reservation Policies</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 1: RESOURCES & SCHEDULE -->
            <div class="row">
                <!-- LEFT COLUMN: RESOURCES -->
                <div class="col-md-6 mb-3">
                    <div class="form-section-card h-100 d-flex flex-column" style="min-height: 200px;">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Step 1: Requested Resources</h5>

                            <!-- Dropdown for Add Item -->
                            <div class="dropdown" x-data="{ openMenu: false }" x-on:click.away="openMenu = false">
                                <button
                                    class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-1"
                                    type="button" x-on:click="openMenu = !openMenu">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': openMenu }"
                                    style="position: absolute;">
                                    <li><a class="dropdown-item" href="#"
                                            x-on:click.prevent="openModal = 'facilityModal'; openMenu = false">Facility</a>
                                    </li>
                                    <li><a class="dropdown-item" href="#"
                                            x-on:click.prevent="openModal = 'equipmentModal'; openMenu = false">Equipment</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Scrollable List -->
                        <div class="selected-items-container flex-grow-1" style="overflow-y: auto;">
                            @forelse($selectedItems as $item)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div class="d-flex align-items-center gap-3">
                                        @if (!empty($item['image']))
                                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                        @else
                                            <div
                                                style="width: 50px; height: 50px; background: #e9ecef; border-radius: 6px;">
                                            </div>
                                        @endif

                                        <div>
                                            <h6 class="mb-1 fw-semibold">{{ $item['name'] }}</h6>
                                            <span
                                                class="badge me-1 {{ $item['type'] === 'equipment' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }}">
                                                {{ ucfirst($item['type']) }}
                                            </span>
                                            <span class="text-secondary small">
                                                ₱{{ number_format($item['base_fee'], 2) }}{{ $item['rate_type'] === 'Per Hour' ? '/hr' : '/event' }}
                                            </span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light text-secondary rounded-circle"
                                        wire:click="removeItem('{{ $item['type'] }}', {{ $item[$item['type'] . '_id'] }})"
                                        title="Remove">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            @empty
                                <div class="text-muted empty-message text-center py-4">No resources added yet.</div>
                            @endforelse
                        </div>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-end">
                            <a href="{{ url('/booking-catalog') }}" class="btn btn-primary">
                                <i class="bi bi-grid me-1"></i> Open Catalog
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: SCHEDULE -->
                <div class="col-md-6 mb-3">
                    <div class="form-section-card h-100 d-flex flex-column" style="min-height: 200px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Step 2: Booking Schedule</h5>

                            <a href="{{ url('/events-calendar') }}"
                                class="calendar-link text-decoration-none text-muted" title="View Events Calendar">
                                <i class="fa-regular fa-calendar fa-lg"></i>
                                <span>View Events</span>
                            </a>
                        </div>

                        <div class="flex-grow-1">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input wire:model.live="startDate" type="date"
                                        class="form-control mb-1 @error('startDate') is-invalid @enderror" />
                                    @error('startDate')
                                        <span class="text-danger small d-block mb-2">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Time</label>
                                    <select wire:model.live="startTime"
                                        class="form-select mb-1 @error('startTime') is-invalid @enderror"
                                        {{ $allDay ? 'disabled' : '' }}>
                                        <option value="">Select Time</option>
                                        @foreach ($this->timeSlots as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('startTime')
                                        <span class="text-danger small d-block mb-2">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input wire:model.live="endDate" type="date"
                                        class="form-control mb-1 @error('endDate') is-invalid @enderror" />
                                    @error('endDate')
                                        <span class="text-danger small d-block mb-2">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Time</label>
                                    <select wire:model.live="endTime"
                                        class="form-select mb-1 @error('endTime') is-invalid @enderror"
                                        {{ $allDay ? 'disabled' : '' }}>
                                        <option value="">Select Time</option>
                                        @foreach ($this->timeSlots as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('endTime')
                                        <span class="text-danger small d-block mb-2">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- All Day Toggle Pill -->
                            <div class="form-check form-switch mt-3 mb-3 ms-3">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    wire:model.live="allDay" id="allDayCheck">
                                <label class="form-check-label small" for="allDayCheck">All Day Event</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center gap-2 mt-auto pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary"
                                wire:click="$set('startDate', null); $set('endDate', null); $set('startTime', null); $set('endTime', null); $set('allDay', false); $set('availabilityResult', null); $resetErrorBag()">Clear</button>

                            <button type="button" class="btn btn-primary" wire:click="checkAvailability"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="checkAvailability">Check Availability</span>
                                <span wire:loading wire:target="checkAvailability"><span
                                        class="spinner-border spinner-border-sm"></span> Checking...</span>
                            </button>

                            @if ($availabilityResult === 'available')
                                <span class="text-success fw-bold small ms-2"><i
                                        class="bi bi-check-circle-fill me-1"></i>
                                    Available</span>
                            @elseif($availabilityResult === 'conflict')
                                <span class="text-danger fw-bold small ms-2"><i class="bi bi-x-circle-fill me-1"></i>
                                    Conflict!</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: USER INFORMATION -->
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-section-card">
                        <h5 class="mb-3">Step 3: User Information</h5>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Applicant Type <span class="text-danger">*</span></label>
                                <select wire:model.live="userType"
                                    class="form-select @error('userType') is-invalid @enderror">
                                    <option value="" disabled>Type of Applicant</option>
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                </select>
                                @error('userType')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input wire:model="firstName" type="text"
                                    class="form-control @error('firstName') is-invalid @enderror"
                                    placeholder="First Name" />
                                @error('firstName')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input wire:model="lastName" type="text"
                                    class="form-control @error('lastName') is-invalid @enderror"
                                    placeholder="Last Name" />
                                @error('lastName')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-2" x-data x-show="$wire.userType === 'Internal'" x-transition>
                                <label class="form-label">School ID <span class="text-danger">*</span></label>
                                <input wire:model="schoolId" type="text"
                                    class="form-control @error('schoolId') is-invalid @enderror"
                                    placeholder="School ID" />
                                @error('schoolId')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-2">
                                <label class="form-label">Contact Number</label>
                                <input wire:model="contactNumber" type="text"
                                    class="form-control @error('contactNumber') is-invalid @enderror"
                                    placeholder="Contact Number" />
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input wire:model="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="Email Address" />
                                @error('email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Department/Organization</label>
                                <input wire:model="organizationName" type="text" class="form-control"
                                    placeholder="Organization Name" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: EVENT DETAILS -->
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-section-card">
                        <h5 class="mb-3">Step 4: Event Details</h5>

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-4">
                                <div class="mb-2">
                                    <label class="form-label">Event Title <span class="text-danger">*</span></label>
                                    <input wire:model="eventTitle" type="text"
                                        class="form-control @error('eventTitle') is-invalid @enderror"
                                        placeholder="e.g., University Day" />
                                    @error('eventTitle')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Activity/Purpose <span
                                            class="text-danger">*</span></label>
                                    <select wire:model="purposeId"
                                        class="form-select @error('purposeId') is-invalid @enderror">
                                        <option value="" selected disabled>Select Purpose</option>
                                        @foreach ($purposes as $purpose)
                                            <option value="{{ $purpose->purpose_id }}">{{ $purpose->purpose_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('purposeId')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">
                                        Cleanup Hours
                                        <i class="bi bi-question-circle text-muted ms-1" style="cursor: help;"
                                            data-bs-toggle="tooltip"
                                            title="We block requests for a few hours after an event to allow time for cleaning and resetting the venue."></i>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input wire:model="cleanupHours" type="number"
                                        class="form-control @error('cleanupHours') is-invalid @enderror"
                                        min="0" max="12" />
                                    @error('cleanupHours')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-8">
                                <div class="mb-2 h-100 d-flex flex-column">
                                    <label class="form-label">Event Details</label>
                                    <textarea wire:model="eventDetails" class="form-control flex-grow-1" style="resize: none; min-height: 100px;"
                                        placeholder="Provide more details about your event..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2 border-top pt-3">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Participants <span class="text-danger">*</span></label>
                                <input wire:model="numParticipants" type="number"
                                    class="form-control @error('numParticipants') is-invalid @enderror"
                                    min="1" />
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Chairs</label>
                                <input wire:model="numChairs" type="number" class="form-control" min="0" />
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Tables</label>
                                <input wire:model="numTables" type="number" class="form-control" min="0" />
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Microphones</label>
                                <input wire:model="numMicrophones" type="number" class="form-control"
                                    min="0" />
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-12 col-md-6 mb-2">
                                <label class="form-label">Additional Requests</label>
                                <textarea wire:model="additionalRequests" class="form-control" rows="1" placeholder="Any special requests..."></textarea>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <label class="form-label">Attach Documents</label>
                                <input type="file" class="form-control @error('document') is-invalid @enderror"
                                    wire:model="document" />
                                <div wire:loading wire:target="document" class="text-primary small mt-1"><span
                                        class="spinner-border spinner-border-sm"></span> Uploading...</div>
                                @error('document')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: EXTRA SERVICES -->
            <div class="row">
                <div class="col-12 mb-3">
                    <div class="form-section-card">
                        <h5 class="mb-3">Step 5: Extra Resources or Services</h5>
                        <div class="border rounded p-3">
                            <div class="row">
                                @foreach ($services as $service)
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                value="{{ $service->service_id }}"
                                                id="service_{{ $service->service_id }}"
                                                wire:model.live="selectedServices">
                                            <label class="form-check-label small"
                                                for="service_{{ $service->service_id }}">
                                                {{ $service->service_name }}
                                                {{ $service->service_fee > 0 ? '(₱' . number_format($service->service_fee, 2) . ')' : '' }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REVIEW & SUBMIT BUTTON CONTAINER -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="form-section-card d-flex justify-content-end py-3">
                        <button type="button" class="btn btn-primary px-5 py-2" wire:click="nextStep">Review &
                            Submit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: SUMMARY -->
        <div class="step-section" id="step2" style="display: {{ $currentStep === 2 ? 'block' : 'none' }};">

            <!-- LIMIT OVERALL WIDTH -->
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">

                    <!-- Requisition Summary Card -->
                    <div class="form-section-card mb-4" style="box-shadow: none; border: 1px solid #dee2e6;">
                        <h5 class="fw-bold text-center mb-2">Requisition Summary</h5>
                        <small class="d-block text-center text-muted mb-4">Please review carefully. Submitted requests
                            cannot be edited.</small>

                        <!-- Contact Information -->
                        <div class="mb-4">
                            <h6 class="pb-2 fw-bold">Contact Information</h6>

                            <!-- Using flex-row to force side-by-side on mobile -->
                            <div class="d-flex flex-row flex-wrap gap-3 mb-3">
                                <!-- Left Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Applicant:</strong> <span class="text-end">{{ $userType }}</span>
                                    </div>
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Name:</strong> <span class="text-end">{{ $firstName }}
                                            {{ $lastName }}</span>
                                    </div>
                                    @if ($schoolId)
                                        <div class="summary-item"
                                            style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                            <strong>School ID:</strong> <span
                                                class="text-end">{{ $schoolId }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Right Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Contact No:</strong> <span
                                            class="text-end">{{ $contactNumber ?: 'None' }}</span>
                                    </div>
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Email:</strong> <span class="text-end"
                                            style="word-break: break-all;">{{ $email }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Organization - Full Width, Stacked -->
                            <div class="summary-item summary-stack pb-2" style="border-bottom: 1px solid #e9ecef;">
                                <strong class="d-block mb-1">Organization:</strong>
                                <span>{{ $organizationName ?: 'None' }}</span>
                            </div>
                        </div>

                        <!-- Event & Schedule Details -->
                        <div class="mb-4">
                            <h6 class="pb-2 fw-bold">Event Details</h6>

                            <div class="summary-item summary-stack mb-2 pb-2"
                                style="border-bottom: 1px solid #e9ecef;">
                                <strong class="d-block mb-1">Event Title:</strong>
                                <span>{{ $eventTitle }}</span>
                            </div>
                            <div class="summary-item summary-stack mb-3 pb-2"
                                style="border-bottom: 1px solid #e9ecef;">
                                <strong class="d-block mb-1">Event Details:</strong>
                                <span>{{ $eventDetails ?: 'None' }}</span>
                            </div>

                            <div class="d-flex flex-row flex-wrap gap-3 mt-3">
                                <!-- Left Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Purpose:</strong> <span
                                            class="text-end">{{ \App\Models\RequisitionPurpose::find($purposeId)?->purpose_name }}</span>
                                    </div>
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>All Day:</strong> <span
                                            class="text-end">{{ $allDay ? 'Yes' : 'No' }}</span>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Cleanup:</strong> <span class="text-end">{{ $cleanupHours }}
                                            hrs</span>
                                    </div>
                                    <!-- Merged Schedule Row -->
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Schedule:</strong>
                                        <span class="text-end" style="font-size: 0.85rem;">
                                            {{ \Carbon\Carbon::parse($startDate)->format('M j, Y') }}
                                            {{ $allDay ? '' : \Carbon\Carbon::parse($startTime)->format('g:i a') }}
                                            -
                                            {{ \Carbon\Carbon::parse($endDate)->format('M j, Y') }}
                                            {{ $allDay ? '' : \Carbon\Carbon::parse($endTime)->format('g:i a') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logistics & Uploads -->
                        <div class="mb-2">
                            <h6 class="pb-2 fw-bold">Logistics & Documents</h6>

                            <div class="d-flex flex-row flex-wrap gap-3 mb-3">
                                <!-- Left Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Participants:</strong> <span
                                            class="text-end">{{ $numParticipants }}</span>
                                    </div>
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Chairs:</strong> <span class="text-end">{{ $numChairs }}</span>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div style="flex: 1; min-width: 140px;">
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Tables:</strong> <span class="text-end">{{ $numTables }}</span>
                                    </div>
                                    <div class="summary-item"
                                        style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                        <strong>Microphones:</strong> <span
                                            class="text-end">{{ $numMicrophones }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="summary-item"
                                style="border-bottom: 1px solid #e9ecef; padding: 8px 0; display: flex; justify-content: space-between;">
                                <strong>Document:</strong>
                                <span class="text-end">
                                    @if ($document)
                                        <i class="bi bi-file-earmark-text text-primary me-1"></i>
                                        {{ $document->getClientOriginalName() }}
                                    @else
                                        None
                                    @endif
                                </span>
                            </div>

                            <div class="summary-item summary-stack mt-2 pb-2"
                                style="border-bottom: 1px solid #e9ecef;">
                                <strong class="d-block mb-1">Additional Requests:</strong>
                                <span>{{ $additionalRequests ?: 'None' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Breakdown Card -->
                    <div class="form-section-card mb-4" style="box-shadow: none; border: 1px solid #dee2e6;">
                        <h5 class="fw-bold pb-2 mb-3">Fee Breakdown</h5>
                        <div id="summary-fees">
                            @include('livewire.partials.fee-breakdown', ['feeBreakdown' => $feeBreakdown])
                        </div>
                    </div>

                </div>
            </div>

            <div class="row justify-content-center mb-5">
                <div class="form-section-card col-12 col-lg-10 d-flex justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-secondary"
                        wire:click="previousStep">Previous</button>
                    <button type="button" class="btn btn-primary px-5 py-2"
                        x-on:click="openModal = 'termsModal'">Submit Form</button>
                </div>
            </div>
        </div>
    </form>

    <!-- MODALS -->
    <!-- Facility Modal -->
    <div class="modal fade" :class="{ 'show d-block': openModal === 'facilityModal' }" tabindex="-1"
        style="background: rgba(0,0,0,0.5);" x-show="openModal === 'facilityModal'" x-cloak>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Facilities</h5>
                    <button type="button" class="btn-close" x-on:click="openModal = null"></button>
                </div>
                <div class="modal-body">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" placeholder="Search facilities..."
                                wire:model.live.debounce.300ms="facilitySearch">
                            <div wire:loading wire:target="facilitySearch" class="text-primary small mt-1"><span
                                    class="spinner-border spinner-border-sm"></span> Searching...</div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" wire:model.live="facilityCategoryFilter">
                                <option value="">All Categories</option>
                                @foreach ($facilityCategories as $cat)
                                    <option value="category:{{ $cat->category_id }}">{{ $cat->category_name }}
                                    </option>
                                    @if (isset($cat->subcategories))
                                        @foreach ($cat->subcategories as $sub)
                                            <option value="subcategory:{{ $sub->subcategory_id }}">--
                                                {{ $sub->subcategory_name }}</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="height: 300px; overflow-y: auto;">
                        @forelse($facilities as $parent)
                            <div>
                                @if ($parent->children->isNotEmpty())
                                    <div class="fw-bold mt-2 text-primary">{{ $parent->facility_name }}</div>
                                    @foreach ($parent->children as $child)
                                        @php
                                            $img =
                                                $child->images->firstWhere('image_type', 'Primary') ??
                                                $child->images->first();
                                            $imgUrl = $img ? $img->image_url : null;
                                        @endphp
                                        <div
                                            class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                            <div class="d-flex align-items-center gap-2">
                                                @if ($imgUrl)
                                                    <img src="{{ $imgUrl }}"
                                                        style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                                @endif
                                                <div>
                                                    <strong>{{ $child->facility_name }}</strong>
                                                    <small
                                                        class="text-muted d-block">{{ $child->facility_code }}</small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary"
                                                x-on:click="$wire.addItem('facility', {{ $child->facility_id }}, '{{ $child->facility_name }}', {{ $child->base_fee }}, '{{ $child->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                                        </div>
                                    @endforeach
                                @else
                                    @php
                                        $img =
                                            $parent->images->firstWhere('image_type', 'Primary') ??
                                            $parent->images->first();
                                        $imgUrl = $img ? $img->image_url : null;
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            @if ($imgUrl)
                                                <img src="{{ $imgUrl }}"
                                                    style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                            @endif
                                            <div>
                                                <strong>{{ $parent->facility_name }}</strong>
                                                <small class="text-muted d-block">{{ $parent->facility_code }}</small>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            x-on:click="$wire.addItem('facility', {{ $parent->facility_id }}, '{{ $parent->facility_name }}', {{ $parent->base_fee }}, '{{ $parent->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No facilities found matching
                                "{{ $facilitySearch }}".</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Modal -->
    <div class="modal fade" :class="{ 'show d-block': openModal === 'equipmentModal' }" tabindex="-1"
        style="background: rgba(0,0,0,0.5);" x-show="openModal === 'equipmentModal'" x-cloak>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Equipment</h5>
                    <button type="button" class="btn-close" x-on:click="openModal = null"></button>
                </div>
                <div class="modal-body">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <input type="text" class="form-control" placeholder="Search equipment..."
                                wire:model.live.debounce.300ms="equipmentSearch">
                            <div wire:loading wire:target="equipmentSearch" class="text-primary small mt-1"><span
                                    class="spinner-border spinner-border-sm"></span> Searching...</div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" wire:model.live="equipmentCategoryFilter">
                                <option value="">All Categories</option>
                                @foreach ($equipmentCategories as $cat)
                                    <option value="category:{{ $cat->category_id }}">{{ $cat->category_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="height: 300px; overflow-y: auto;">
                        @forelse($equipment as $item)
                            @php
                                $img = $item->images->firstWhere('image_type', 'Primary') ?? $item->images->first();
                                $imgUrl = $img ? $img->image_url : null;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    @if ($imgUrl)
                                        <img src="{{ $imgUrl }}"
                                            style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">
                                    @endif
                                    <div>
                                        <strong>{{ $item->equipment_name }}</strong>
                                        <small
                                            class="text-muted d-block">₱{{ number_format($item->base_fee, 2) }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary"
                                    x-on:click="$wire.addItem('equipment', {{ $item->equipment_id }}, '{{ $item->equipment_name }}', {{ $item->base_fee }}, '{{ $item->rate_type }}', '{{ $imgUrl }}'); openModal = null;">Add</button>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">No equipment found matching
                                "{{ $equipmentSearch }}".</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conflict Modal -->
    <div class="modal fade" id="conflictModal" tabindex="-1" x-data
        x-on:open-conflict-modal.window="new bootstrap.Modal($el).show()">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-dark">
                    <h5 class="modal-title"><i class="fas fa-triangle-exclamation text-danger me-2"></i>Scheduling
                        Conflict Found</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- Added text-start here -->
                <div class="modal-body text-start">
                    <div>{!! $conflictHtml !!}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" :class="{ 'show d-block': openModal === 'termsModal' }" tabindex="-1"
        style="background: rgba(0,0,0,0.5);" x-show="openModal === 'termsModal'" x-cloak>
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title text-primary mb-0">Terms and Conditions</h5>
                    <button type="button" class="btn-close" x-on:click="openModal = null"></button>
                </div>

                <div class="modal-body">
                    <div class="terms-content mb-0 mx-auto"
                        style="max-height: 50vh; overflow-y: auto; text-align: center;">
                        <small class="d-block text-start mb-3" style="padding-left: 17px;">
                            By using our booking service, you agree to comply with the following terms and conditions,
                            as well
                            as all campus policies set by Central Philippine University (CPU):
                        </small>

                        <ol class="text-start">
                            <li>
                                <strong class="text-primary">Approval Process</strong>
                                <small>All booking requests are subject to review and approval by the CPU
                                    Administration.
                                    Submission of a requisition form does not guarantee approval.</small>
                            </li>

                            <li>
                                <strong class="text-primary">Confirmation and Payment</strong>
                                <small>
                                    Requesters will receive a confirmation email after submitting their form. Once the
                                    booking has
                                    been reviewed and approved, a follow-up notification will be sent containing
                                    finalized booking
                                    details and payment instructions. All payments must be settled
                                    <span class="fw-bold">in person</span> at the CPU Business Office within
                                    <span class="fw-bold">three (3) business days</span> after approval.
                                </small>
                            </li>

                            <li>
                                <strong class="text-primary">Cancellations</strong>
                                <small>
                                    Requesters may cancel their booking <span class="fw-bold">up to five (5) days
                                        before the
                                        scheduled event</span> through the system using the access code provided via
                                    email after submission.
                                    Cancellations made beyond this period may not be honored.
                                </small>
                            </li>

                            <li>
                                <strong class="text-primary">Facility and Equipment Responsibility</strong>
                                <small>Requesters are responsible for the proper use and care of all facilities and
                                    equipment. Any
                                    damage, loss, or misuse may incur corresponding repair or replacement fees.</small>
                            </li>

                            <li>
                                <strong class="text-primary">Return Policy and Penalties</strong>
                                <small>
                                    All borrowed equipment must be returned within the specified booking period. A
                                    <span class="fw-bold">grace period of up to 4 hours</span> after the event may be
                                    allowed for
                                    clean-up or coordination.
                                    Failure to return items within this timeframe may result in <span
                                        class="fw-bold">late penalty
                                        fees</span> or temporary suspension of booking privileges.
                                </small>
                            </li>

                            <li>
                                <strong class="text-primary">Prohibited Acts</strong>
                                <small> Alcohol consumption and smoking are strictly prohibited within the campus
                                    premises.
                                    External users must present valid identification when required. </small>
                            </li>

                            <li>
                                <strong class="text-primary">Administrative Rights</strong>
                                <small> CPU reserves the right to cancel or revoke bookings for policy violations or
                                    non-compliance with these terms and conditions. </small>
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
                    <button type="button" class="btn btn-outline-secondary" x-on:click="openModal = null"
                        x-bind:disabled="isSubmitting">Cancel</button>

                    <!-- X-DATA WRAPPER FOR AJAX SUBMISSION -->
                    <div x-data="{ isSubmitting: false }">
                        <form action="{{ route('requisition.submit') }}" method="POST" id="finalSubmitForm"
                            x-on:submit.prevent="
                      if(!document.getElementById('agreeTerms').checked) { alert('Please agree to terms'); return; }
                      isSubmitting = true;
                      fetch($el.action, {
                          method: 'POST',
                          body: new FormData($el),
                          headers: { 'X-Requested-With': 'XMLHttpRequest' }
                      })
                      .then(res => res.json())
                      .then(data => {
                          if(data.success) {
                              // Hide terms modal and show success modal
                              openModal = 'successModal';
                          } else {
                              alert(data.message || 'Submission failed.');
                              isSubmitting = false;
                          }
                      })
                      .catch(err => {
                          alert('An error occurred. Please try again.');
                          isSubmitting = false;
                      });
                  ">
                            @csrf
                            <input type="hidden" name="user_type" value="{{ $userType }}">
                            <input type="hidden" name="school_id" value="{{ $schoolId }}">
                            <input type="hidden" name="first_name" value="{{ $firstName }}">
                            <input type="hidden" name="last_name" value="{{ $lastName }}">
                            <input type="hidden" name="email" value="{{ $email }}">
                            <input type="hidden" name="contact_number" value="{{ $contactNumber }}">
                            <input type="hidden" name="organization_name" value="{{ $organizationName }}">
                            <input type="hidden" name="event_title" value="{{ $eventTitle }}">
                            <input type="hidden" name="event_details" value="{{ $eventDetails }}">
                            <input type="hidden" name="num_participants" value="{{ $numParticipants }}">
                            <input type="hidden" name="num_chairs" value="{{ $numChairs }}">
                            <input type="hidden" name="num_tables" value="{{ $numTables }}">
                            <input type="hidden" name="num_microphones" value="{{ $numMicrophones }}">
                            <input type="hidden" name="all_day" value="{{ $allDay ? 1 : 0 }}">
                            <input type="hidden" name="purpose_id" value="{{ $purposeId }}">
                            <input type="hidden" name="additional_requests" value="{{ $additionalRequests }}">
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            <input type="hidden" name="start_time"
                                value="{{ $allDay ? '00:00:00' : $startTime }}">
                            <input type="hidden" name="end_time" value="{{ $allDay ? '23:59:59' : $endTime }}">
                            <input type="hidden" name="cleanup_hours" value="{{ $cleanupHours }}">
                            <input type="hidden" name="event_documents_url"
                                value="{{ session('request_info.event_documents_url') }}">
                            <input type="hidden" name="event_documents_public_id"
                                value="{{ session('request_info.event_documents_public_id') }}">

                            <button type="submit" class="btn btn-primary" x-bind:disabled="isSubmitting">
                                <span x-show="!isSubmitting">Accept & Submit</span>
                                <span x-show="isSubmitting" x-cloak>
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Processing...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" :class="{ 'show d-block': openModal === 'successModal' }" tabindex="-1"
        style="background: rgba(0,0,0,0.5);" x-show="openModal === 'successModal'" x-cloak>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-body py-5">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 fw-bold">Request Submitted Successfully!</h4>
                    <p class="text-muted mt-2 mb-4">
                        Your requisition has been sent for approval. A confirmation email will be sent shortly.
                    </p>
                    <button type="button" class="btn btn-primary px-5 py-2"
                        onclick="localStorage.removeItem('reservationDraft'); window.location.href='{{ route('home') }}';">
                        Back to Home
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@assets
    <style>
        [x-cloak] {
            display: none !important;
        }

        .btn-transparent[aria-expanded="true"] i.bi-chevron-down {
            transform: rotate(180deg);
        }

        .btn-transparent[aria-expanded="false"] i.bi-chevron-down {
            transform: rotate(0deg);
        }
    </style>
    <script>
        document.addEventListener('livewire:init', () => {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        })
    </script>
    <link rel="stylesheet" href="{{ asset('css/public/reservation-form.css') }}" />
@endassets
