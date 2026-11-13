<main id="main">
    <div class="container main-content mb-5" @include('livewire.partials.reservation-wrapper')
        style="max-width: 960px; margin-left: auto; margin-right: auto;">

        <div class="bg-white border rounded-3 admin-livewire-form" style="border-color: #dee2e6;">

            <style>
                .admin-livewire-form .card-header {
                    background-color: #f8f9fa !important;
                    border-bottom: 1px solid #e9ecef;
                    padding: 0.75rem 1rem;
                }

                .admin-livewire-form .card-header strong {
                    font-family: "Fraunces", Georgia, serif;
                    font-weight: 600;
                    font-size: 1rem;
                    color: var(--navy);
                }

                .admin-livewire-form .form-page-title {
                    font-family: "Fraunces", Georgia, serif;
                    font-weight: 900;
                    font-size: 1.25rem;
                    margin: 0;
                    color: #fff;
                }
            </style>

            <div class="d-flex justify-content-between align-items-center p-3"
                style="background: linear-gradient(135deg, #224d9c 0%, #3e6fca 100%); border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem;">
                <h4 class="form-page-title">Create New Reservation</h4>
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-light">Back</a>
            </div>

            <div class="p-4">
                <form wire:submit.prevent="submitAdmin">

                    {{-- ============ STEP 1 ============ --}}
                    <div style="display: {{ $currentStep === 1 ? 'block' : 'none' }};">

                        {{-- User Information --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header"><strong>User Information</strong></div>
                                    <div class="card-body row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">User Type <span
                                                    class="text-danger">*</span></label>
                                            <select wire:model.live="userType" class="form-select">
                                                <option value="Internal">Internal</option>
                                                <option value="External">External</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">First Name <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="firstName" type="text" class="form-control">
                                            @error('firstName')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Last Name <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="lastName" type="text" class="form-control">
                                            @error('lastName')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input wire:model="email" type="email" class="form-control">
                                            @error('email')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Contact Number</label>
                                            <input wire:model="contactNumber" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">School ID</label>
                                            <input wire:model="schoolId" type="text" class="form-control"
                                                {{ $userType === 'External' ? 'disabled' : '' }}>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label">Organization</label>
                                            <input wire:model="organizationName" type="text" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Event Details --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header"><strong>Event Details</strong></div>
                                    <div class="card-body row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Event Title <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="eventTitle" type="text" class="form-control">
                                            @error('eventTitle')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Purpose <span class="text-danger">*</span></label>
                                            <select wire:model="purposeId" class="form-select">
                                                <option value="">Select purpose</option>
                                                @foreach ($purposes as $p)
                                                    <option value="{{ $p->purpose_id }}">{{ $p->purpose_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('purposeId')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Event Details</label>
                                            <textarea wire:model="eventDetails" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Participants <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="numParticipants" type="number" min="1"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Chairs</label>
                                            <input wire:model="numChairs" type="number" min="0"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tables</label>
                                            <input wire:model="numTables" type="number" min="0"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Microphones</label>
                                            <input wire:model="numMicrophones" type="number" min="0"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cleanup Hours</label>
                                            <input wire:model="cleanupHours" type="number" min="0"
                                                max="12" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Additional Requests</label>
                                            <input wire:model="additionalRequests" type="text"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Resources + Services (Equal Height) --}}
                        <div class="row mb-3">
                            {{-- Resources --}}
                            <div class="col-md-6 mb-3 mb-md-0 d-flex">
                                <div class="card w-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong>Resources</strong>
                                        <div class="dropdown" x-data="{ openMenu: false }"
                                            x-on:click.away="openMenu = false">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                type="button" x-on:click="openMenu = !openMenu">
                                                <i class="bi bi-plus"></i> Add Resource
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
                                    {{-- The absolute positioning trick: fills the remaining height of the card --}}
                                    <div class="card-body position-relative p-0">
                                        <div class="position-absolute w-100 h-100 overflow-auto p-3">
                                            @forelse ($selectedItems as $i)
                                                <div
                                                    class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                    <div>
                                                        <strong>{{ $i['name'] }}</strong>
                                                        <span
                                                            class="badge bg-primary ms-2">{{ ucfirst($i['type']) }}</span>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        wire:click="removeItem('{{ $i['type'] }}', {{ $i[$i['type'] . '_id'] }})">
                                                        Remove
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="text-muted">No resources added yet.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Extra Services --}}
                            <div class="col-md-6 d-flex">
                                <div class="card w-100" style="min-height: 300px;">
                                    <div class="card-header"><strong>Extra Services</strong></div>
                                    <div class="card-body">
                                        <div class="row align-content-start">
                                            @foreach ($services as $s)
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            value="{{ $s->service_id }}"
                                                            id="svc_{{ $s->service_id }}"
                                                            wire:model.live="selectedServices">
                                                        <label class="form-check-label"
                                                            for="svc_{{ $s->service_id }}">
                                                            {{ $s->service_name }}
                                                            {{ $s->service_fee > 0 ? '(₱' . number_format($s->service_fee, 2) . ')' : '' }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Schedule + Initial Status (2-col) --}}
                        <div class="row mb-3">
                            {{-- Schedule --}}
                            <div class="col-md-8 d-flex">
                                <div class="card w-100 d-flex flex-column">
                                    <div class="card-header"><strong>Schedule</strong></div>
                                    <div class="card-body row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Start Date <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model.live="startDate" type="date" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Start Time</label>
                                            <input wire:model.live="startTime" type="time" class="form-control"
                                                {{ $allDay ? 'disabled' : '' }}>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Date <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model.live="endDate" type="date" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Time</label>
                                            <input wire:model.live="endTime" type="time" class="form-control"
                                                {{ $allDay ? 'disabled' : '' }}>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                    wire:model.live="allDay" id="allDaySwitch">
                                                <label class="form-check-label" for="allDaySwitch">All Day</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="button" class="btn btn-outline-secondary"
                                                wire:click="checkAvailability" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="checkAvailability">Check
                                                    Availability</span>
                                                <span wire:loading wire:target="checkAvailability">
                                                    <span class="spinner-border spinner-border-sm"></span> Checking...
                                                </span>
                                            </button>
                                            @if ($availabilityResult === 'available')
                                                <span class="text-success ms-2"><i
                                                        class="bi bi-check-circle-fill"></i> Available</span>
                                            @elseif ($availabilityResult === 'conflict')
                                                <span class="text-danger ms-2"><i class="bi bi-x-circle-fill"></i>
                                                    Conflict</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Submission Workflow (Initial Status) --}}
                            <div class="col-md-4 d-flex">
                                <div class="card w-100 d-flex flex-column">
                                    <div class="card-header"><strong>Submission Workflow</strong></div>
                                    <div class="card-body">
                                        <select wire:model.live="statusId" class="form-select mb-2">
                                            <option value="">Select workflow...</option>
                                            @foreach ($statuses as $st)
                                                <option value="{{ $st['status_id'] }}">{{ $st['status_name'] }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('statusId')
                                            <small class="text-danger d-block mb-2">{{ $message }}</small>
                                        @enderror

                                        {{-- Dynamic Helper Text --}}
                                        @php
                                            $selectedStatus = collect($statuses)->firstWhere('status_id', $statusId);
                                            $statusName = $selectedStatus['status_name'] ?? null;
                                        @endphp

                                        @if ($statusName === \App\Models\FormStatus::PENDING_APPROVAL)
                                            <div class="alert alert-info py-2 mb-0 small">
                                                <i class="bi bi-info-circle-fill me-1"></i>
                                                Acts like a normal user request. Requires standard admin approvals
                                                before scheduling.
                                            </div>
                                        @elseif($statusName === \App\Models\FormStatus::RESERVED)
                                            <div class="alert alert-warning py-2 mb-0 small text-dark">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                <strong>Admin Override:</strong> Immediately finalizes and schedules
                                                this event, completely bypassing the approval chain.
                                            </div>
                                        @else
                                            <small class="text-muted d-block mt-1">Select how this reservation should
                                                be processed.</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div class="d-flex justify-content-end mb-5">
                            @if ($currentStep > 1)
                                <button type="button" class="btn btn-outline-secondary me-2"
                                    wire:click="previousStep">
                                    Previous
                                </button>
                            @endif

                            @if ($currentStep < 2)
                                <button type="button" class="btn btn-primary" wire:click="nextStep">
                                    Next
                                </button>
                            @else
                                <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submitAdmin">Create Reservation</span>
                                    <span wire:loading wire:target="submitAdmin">
                                        <span class="spinner-border spinner-border-sm"></span> Creating...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- ============ STEP 2 (REVIEW) ============ --}}
                    <div style="display: {{ $currentStep === 2 ? 'block' : 'none' }};">
                        <div class="row justify-content-center">
                            <div class="col-12">

                                <!-- Requisition Summary Card -->
                                <div class="card mb-4" style="box-shadow: none; border: 1px solid #dee2e6;">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold text-center mb-2">Requisition Summary</h5>
                                        <small class="d-block text-center text-muted mb-4">Please review carefully.
                                            Submitted requests cannot be edited.</small>

                                        <!-- Contact Information -->
                                        <div class="mb-4">
                                            <h6 class="pb-2 fw-bold border-bottom">Contact Information</h6>
                                            <div class="d-flex flex-row flex-wrap gap-3 mb-3">
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Applicant:</strong> <span
                                                            class="text-end">{{ $userType }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Name:</strong> <span
                                                            class="text-end">{{ $firstName }}
                                                            {{ $lastName }}</span>
                                                    </div>
                                                    @if ($schoolId)
                                                        <div class="d-flex justify-content-between py-2 border-bottom">
                                                            <strong>School ID:</strong> <span
                                                                class="text-end">{{ $schoolId }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Contact No:</strong> <span
                                                            class="text-end">{{ $contactNumber ?: 'None' }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Email:</strong> <span class="text-end"
                                                            style="word-break: break-all;">{{ $email }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="py-2 border-bottom">
                                                <strong class="d-block mb-1">Organization:</strong>
                                                <span>{{ $organizationName ?: 'None' }}</span>
                                            </div>
                                        </div>

                                        <!-- Event & Schedule Details -->
                                        <div class="mb-4">
                                            <h6 class="pb-2 fw-bold border-bottom">Event Details</h6>
                                            <div class="py-2 border-bottom mb-2">
                                                <strong class="d-block mb-1">Event Title:</strong>
                                                <span>{{ $eventTitle }}</span>
                                            </div>
                                            <div class="py-2 border-bottom mb-3">
                                                <strong class="d-block mb-1">Event Details:</strong>
                                                <span>{{ $eventDetails ?: 'None' }}</span>
                                            </div>

                                            <div class="d-flex flex-row flex-wrap gap-3 mt-3">
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Purpose:</strong> <span
                                                            class="text-end">{{ \App\Models\RequisitionPurpose::find($purposeId)?->purpose_name }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>All Day:</strong> <span
                                                            class="text-end">{{ $allDay ? 'Yes' : 'No' }}</span>
                                                    </div>
                                                </div>
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Cleanup:</strong> <span
                                                            class="text-end">{{ $cleanupHours }} hrs</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
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

                                        <!-- Logistics -->
                                        <div class="mb-2">
                                            <h6 class="pb-2 fw-bold border-bottom">Logistics & Requests</h6>
                                            <div class="d-flex flex-row flex-wrap gap-3 mb-3">
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Participants:</strong> <span
                                                            class="text-end">{{ $numParticipants }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Chairs:</strong> <span
                                                            class="text-end">{{ $numChairs }}</span>
                                                    </div>
                                                </div>
                                                <div style="flex: 1; min-width: 140px;">
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Tables:</strong> <span
                                                            class="text-end">{{ $numTables }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-2 border-bottom">
                                                        <strong>Microphones:</strong> <span
                                                            class="text-end">{{ $numMicrophones }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="py-2 border-bottom mt-2">
                                                <strong class="d-block mb-1">Additional Requests:</strong>
                                                <span>{{ $additionalRequests ?: 'None' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fee Breakdown Card -->
                                <div class="card mb-4" style="box-shadow: none; border: 1px solid #dee2e6;">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold pb-2 mb-3">Fee Breakdown</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle mb-0"
                                                style="border-color: #e9ecef;">
                                                <thead class="bg-light">
                                                    <tr class="text-muted small"
                                                        style="border-bottom: 1px solid #dee2e6;">
                                                        <th style="width: 30%;">Resource</th>
                                                        <th class="text-center" style="width: 15%;">Type</th>
                                                        <th class="text-center" style="width: 15%;">Rate</th>
                                                        <th class="text-center" style="width: 15%;">Duration</th>
                                                        <th class="text-end" style="width: 12%;">Base Fee</th>
                                                        <th class="text-end" style="width: 13%;">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {{-- Facilities --}}
                                                    @foreach ($feeBreakdown['breakdown']['facilities'] as $fee)
                                                        <tr class="small" style="border-color: #e9ecef;">
                                                            <td>{{ $fee['name'] }}</td>
                                                            <td class="text-center text-muted">Facility</td>
                                                            <td class="text-center text-muted">{{ $fee['rate_type'] }}
                                                            </td>
                                                            <td class="text-center text-muted">
                                                                {{ $fee['duration_text'] ? str_replace(' hours', 'h', $fee['duration_text']) : '-' }}
                                                            </td>
                                                            <td class="text-end text-muted">
                                                                ₱{{ number_format($fee['unit_price'], 2) }}</td>
                                                            <td class="text-end fw-semibold">
                                                                ₱{{ number_format($fee['fee'], 2) }}</td>
                                                        </tr>
                                                    @endforeach

                                                    {{-- Equipment --}}
                                                    @foreach ($feeBreakdown['breakdown']['equipment'] as $fee)
                                                        <tr class="small" style="border-color: #e9ecef;">
                                                            <td>{{ $fee['name'] }} <span
                                                                    class="text-muted">x{{ $fee['quantity'] }}</span>
                                                            </td>
                                                            <td class="text-center text-muted">Equipment</td>
                                                            <td class="text-center text-muted">{{ $fee['rate_type'] }}
                                                            </td>
                                                            <td class="text-center text-muted">
                                                                {{ $fee['duration_text'] ? str_replace(' hours', 'h', $fee['duration_text']) : '-' }}
                                                            </td>
                                                            <td class="text-end text-muted">
                                                                ₱{{ number_format($fee['unit_price'], 2) }}</td>
                                                            <td class="text-end fw-semibold">
                                                                ₱{{ number_format($fee['fee'], 2) }}</td>
                                                        </tr>
                                                    @endforeach

                                                    {{-- Services --}}
                                                    @foreach ($feeBreakdown['breakdown']['services'] as $fee)
                                                        <tr class="small" style="border-color: #e9ecef;">
                                                            <td>{{ $fee['name'] }}</td>
                                                            <td class="text-center text-muted">Service</td>
                                                            <td class="text-center text-muted">Per Event</td>
                                                            <td class="text-center text-muted">-</td>
                                                            <td class="text-end text-muted">
                                                                ₱{{ number_format($fee['unit_price'], 2) }}</td>
                                                            <td class="text-end fw-semibold">
                                                                ₱{{ number_format($fee['fee'], 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light">
                                                    <tr style="border-color: #dee2e6;">
                                                        <td colspan="5" class="text-first fw-bold pt-3 pb-3 fs-6">
                                                            Total Amount</td>
                                                        <td class="text-end fw-bold text-primary pt-3 pb-3 fs-5">
                                                            ₱{{ number_format($feeBreakdown['approved_fee'], 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Navigation for Step 2 --}}
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-secondary" wire:click="previousStep">
                                Previous
                            </button>
                            <button type="submit" class="btn btn-success px-5" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitAdmin">Create Reservation</span>
                                <span wire:loading wire:target="submitAdmin">
                                    <span class="spinner-border spinner-border-sm"></span> Creating...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>

                @include('livewire.partials.resource-modals')
                @include('livewire.partials.conflict-modal')
            </div>
        </div>
    </div>
</main>
<script>
    document.addEventListener('livewire:init', () => {
        // This hook runs exactly when the component is ready
        Livewire.hook('component.init', ({ component }) => {
            // Bridge admin ID
            if (window.Admin?.admin_id) {
                component.$wire.set('adminId', window.Admin.admin_id);
            }

            // Bridge token (if still needed for other features)
            const t = localStorage.getItem('adminToken');
            if (t) {
                component.$wire.set('adminToken', t);
            }
        });

        Livewire.on('admin-submit-success', (data) => {
            const id = Array.isArray(data) ? data[0]?.request_id : data?.request_id;
            window.location.href = '/admin/requisition/' + id;
        });

        Livewire.on('toast', (data) => {
            const d = Array.isArray(data) ? data[0] : data;
            if (window.showToast) window.showToast(d.message, d.type || 'success');
            else alert(d.message);
        });
    });
</script>
