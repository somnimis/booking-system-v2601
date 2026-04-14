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

        /* Facility Filters */
        .filters-bar {
            display: flex;
            align-items: center;
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

        .facility-filter-badge {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid var(--border-color);
        }

        .facility-filter-badge:hover {
            background: #e9ecef;
        }

        .facility-filter-badge.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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

        /* Main Matrix Table */
        .matrix-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: white;
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

        /* Date Picker Styles - Minimal, just for alignment */
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

            <!-- Filters Bar -->
            <div class="filters-bar">
                <div class="facility-filter-group" id="facilityFilters">
                    <span class="facility-filter-badge active" data-facility="all">All Facilities</span>
                </div>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search facility or event...">
                    <i class="bi bi-search"></i>
                </div>
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

    <script>
        // ============================================
        // AVAILABILITY MATRIX CONTROLLER
        // ============================================

        class AvailabilityMatrix {
            constructor() {
                this.currentDate = new Date();
                this.timeRange = '8-17'; // Default: 8 AM - 5 PM
                this.facilities = [];
                this.requisitions = [];
                this.calendarEvents = [];
                this.selectedFacility = 'all';
                this.searchQuery = '';
                this.isLoading = false;

                // Remove seconds from time strings for consistency
                this.timeFormat = 'H:i';

                this.init();
            }

            async init() {
                this.showLoading(true);
                await this.loadData();
                this.renderFacilityFilters();
                this.renderMatrix();
                this.attachEvents();
                this.showLoading(false);
            }



            async loadData() {
                try {
                    const dateStr = this.formatDate(this.currentDate);

                    // Fetch facilities, requisitions, and calendar events in parallel
                    const [facilitiesRes, requisitionsRes, calendarEventsRes] = await Promise.all([
                        fetch('/api/facilities'),
                        fetch(`/api/requisition-forms/calendar-events`),
                        fetch('/api/calendar-events/all')
                    ]);

                    const facilitiesData = await facilitiesRes.json();
                    const requisitionsData = await requisitionsRes.json();
                    const calendarEventsData = await calendarEventsRes.json();

                    // Process facilities
                    this.facilities = facilitiesData.data || facilitiesData || [];

                    // Process requisitions (filter for current date and active statuses)
                    const requisitions = requisitionsData.data || requisitionsData || [];
                    this.requisitions = requisitions.filter(req => {
                        const startDate = req.start?.split('T')[0] || req.extendedProps?.start_date;
                        const endDate = req.end?.split('T')[0] || req.extendedProps?.end_date;
                        const currentDateStr = this.formatDate(this.currentDate);
                        return currentDateStr >= startDate && currentDateStr <= endDate;
                    });

                    // Process calendar events
                    const calendarEvents = calendarEventsData.data || calendarEventsData || [];
                    this.calendarEvents = calendarEvents.filter(event => {
                        const startDate = event.schedule?.start_date;
                        const endDate = event.schedule?.end_date;
                        const currentDateStr = this.formatDate(this.currentDate);
                        return currentDateStr >= startDate && currentDateStr <= endDate;
                    });

                    console.log(`Loaded: ${this.facilities.length} facilities, ${this.requisitions.length} requisitions, ${this.calendarEvents.length} calendar events`);

                } catch (error) {
                    console.error('Error loading data:', error);
                    this.showError('Failed to load availability data');
                }
            }

            setupDatePicker() {
                const datePicker = document.getElementById('datePicker');
                if (!datePicker) return;

                // Set initial value to current date in YYYY-MM-DD format
                datePicker.value = this.formatDate(this.currentDate);

                // Add change event listener
                datePicker.addEventListener('change', (e) => {
                    const selectedDate = new Date(e.target.value);
                    if (!isNaN(selectedDate.getTime())) {
                        this.currentDate = selectedDate;
                        this.refresh();
                    }
                });
            }

            getTimeSlots() {
                const slots = [];
                const [startHour, endHour] = this.timeRange.split('-').map(Number);

                // Generate 30-minute intervals
                for (let hour = startHour; hour < endHour; hour++) {
                    slots.push(`${hour.toString().padStart(2, '0')}:00`);
                    slots.push(`${hour.toString().padStart(2, '0')}:30`);
                }

                return slots;
            }

            getStatusForTimeSlot(facilityId, timeSlot) {
                // Check system events first (these override everything)
                const eventAtSlot = this.calendarEvents.find(event => {
                    const eventStartTime = event.schedule?.start_time?.substring(0, 5);
                    const eventEndTime = event.schedule?.end_time?.substring(0, 5);
                    return eventStartTime <= timeSlot && eventEndTime > timeSlot;
                });

                if (eventAtSlot) {
                    return {
                        status: 'event',
                        text: '📅 Event',
                        event: eventAtSlot,
                        tooltip: eventAtSlot.event_name
                    };
                }

                // Check requisitions for this facility
                const requisitionAtSlot = this.requisitions.find(req => {
                    const reqFacilities = req.extendedProps?.facilities || [];
                    const matchesFacility = reqFacilities.some(f =>
                        String(f.facility_id) === String(facilityId)
                    );

                    if (!matchesFacility) return false;

                    const reqStartTime = req.start?.substring(11, 16) || req.extendedProps?.start_time?.substring(0, 5);
                    const reqEndTime = req.end?.substring(11, 16) || req.extendedProps?.end_time?.substring(0, 5);

                    return reqStartTime <= timeSlot && reqEndTime > timeSlot;
                });

                if (requisitionAtSlot) {
                    const statusName = requisitionAtSlot.extendedProps?.status || 'Pending';
                    const isApproved = statusName === 'Approved';
                    const isPending = ['Pending', 'For Approval', 'Under Review'].includes(statusName);

                    if (isApproved) {
                        return {
                            status: 'booked',
                            text: '🔴 Booked',
                            event: requisitionAtSlot,
                            tooltip: requisitionAtSlot.title
                        };
                    } else if (isPending) {
                        return {
                            status: 'pending',
                            text: '🟡 Pending',
                            event: requisitionAtSlot,
                            tooltip: `${requisitionAtSlot.title} (Pending Approval)`
                        };
                    }
                }

                return {
                    status: 'available',
                    text: '✅ Available',
                    event: null,
                    tooltip: 'Click to book this slot'
                };
            }

            renderMatrix() {
                const container = document.getElementById('availabilityMatrix');
                const timeSlots = this.getTimeSlots();

                if (this.facilities.length === 0) {
                    container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-building"></i>
                        <p>No facilities available</p>
                    </div>
                `;
                    return;
                }

                // Filter facilities by search
                let filteredFacilities = this.facilities;
                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase();
                    filteredFacilities = this.facilities.filter(f =>
                        (f.facility_name || f.name).toLowerCase().includes(query)
                    );
                }

                // Filter by selected facility
                if (this.selectedFacility !== 'all') {
                    filteredFacilities = filteredFacilities.filter(f =>
                        String(f.facility_id || f.id) === this.selectedFacility
                    );
                }

                if (filteredFacilities.length === 0) {
                    container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-search"></i>
                        <p>No facilities match your search</p>
                    </div>
                `;
                    return;
                }

                let html = `
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th>Facility / Time</th>
                            ${timeSlots.map(slot => `<th>${this.formatTime(slot)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
            `;

                filteredFacilities.forEach(facility => {
                    const facilityId = facility.facility_id || facility.id;
                    const facilityName = facility.facility_name || facility.name;

                    html += `<tr>
                            <td class="facility-cell" data-tooltip="${facilityName}">
                                <strong>${this.truncate(facilityName, 25)}</strong>
                            </td>`;

                    timeSlots.forEach(slot => {
                        const { status, text, event, tooltip } = this.getStatusForTimeSlot(facilityId, slot);
                        const eventId = event?.extendedProps?.request_id || event?.event_id || '';

                        html += `<td>
                                <div class="status-card ${status}" 
                                     data-status="${status}"
                                     data-facility="${facilityId}"
                                     data-time="${slot}"
                                     data-event-id="${eventId}"
                                     data-event-type="${event?.extendedProps?.eventType || 'requisition'}"
                                     data-tooltip="${tooltip}">
                                    ${text}
                                </div>
                             </td>`;
                    });

                    html += `</tr>`;
                });

                html += `</tbody></table>`;
                container.innerHTML = html;

                this.updateDateDisplay();
                this.attachSlotClickHandlers();
            }

            attachSlotClickHandlers() {
                const container = document.getElementById('availabilityMatrix');

                container.querySelectorAll('.status-card.available').forEach(card => {
                    card.removeEventListener('click', this.handleSlotClick);
                    card.addEventListener('click', this.handleSlotClick.bind(this));
                });

                container.querySelectorAll('.status-card.booked, .status-card.pending, .status-card.event').forEach(card => {
                    card.removeEventListener('click', this.handleEventClick);
                    card.addEventListener('click', this.handleEventClick.bind(this));
                });
            }

            handleSlotClick(e) {
                const card = e.currentTarget;
                const facilityId = card.dataset.facility;
                const timeSlot = card.dataset.time;
                const facility = this.facilities.find(f => (f.facility_id || f.id) == facilityId);

                this.showBookingModal({
                    facilityId: facilityId,
                    facilityName: facility?.facility_name || facility?.name,
                    date: this.currentDate,
                    time: timeSlot
                });
            }

            handleEventClick(e) {
                const card = e.currentTarget;
                const eventId = card.dataset.eventId;
                const eventType = card.dataset.eventType;

                if (eventId) {
                    this.showEventDetails(eventId, eventType);
                }
            }

            async showEventDetails(eventId, eventType) {
                let eventData = null;

                if (eventType === 'calendar_event') {
                    eventData = this.calendarEvents.find(e => e.event_id == eventId);
                } else {
                    eventData = this.requisitions.find(r =>
                        r.extendedProps?.request_id == eventId || r.request_id == eventId
                    );
                }

                if (!eventData) return;

                const modal = new bootstrap.Modal(document.getElementById('eventModal'));
                const modalBody = document.getElementById('eventModalBody');
                const modalTitle = document.getElementById('eventModalTitle');

                if (eventType === 'calendar_event') {
                    modalTitle.textContent = eventData.event_name || 'Calendar Event';
                    modalBody.innerHTML = `
                    <div class="event-detail-row">
                        <div class="event-detail-label">Description</div>
                        <div class="event-detail-value">${eventData.description || 'No description'}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Schedule</div>
                        <div class="event-detail-value">${eventData.schedule?.display || 'N/A'}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Type</div>
                        <div class="event-detail-value">${eventData.display_name || eventData.event_type}</div>
                    </div>
                `;
                } else {
                    modalTitle.textContent = eventData.title || 'Event Details';
                    const props = eventData.extendedProps || eventData;
                    modalBody.innerHTML = `
                    <div class="event-detail-row">
                        <div class="event-detail-label">Requester</div>
                        <div class="event-detail-value">${props.requester || 'N/A'}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Purpose</div>
                        <div class="event-detail-value">${props.purpose || 'N/A'}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Status</div>
                        <div class="event-detail-value">
                            <span class="badge" style="background-color: ${eventData.color || '#007bff'}">
                                ${props.status || 'Unknown'}
                            </span>
                        </div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Participants</div>
                        <div class="event-detail-value">${props.num_participants || 0}</div>
                    </div>
                    <div class="event-detail-row">
                        <div class="event-detail-label">Facilities</div>
                        <div class="event-detail-value">${(props.facilities || []).map(f => f.name).join(', ') || 'N/A'}</div>
                    </div>
                `;
                }

                modal.show();
            }

            showBookingModal(data) {
                // Implement booking modal logic here
                // This would open a form to submit a requisition
                console.log('Booking slot:', data);
                alert(`Booking request for ${data.facilityName}\nDate: ${this.formatDisplayDate(data.date)}\nTime: ${this.formatTime(data.time)}\n\nThis feature will open the requisition form.`);
            }

            renderFacilityFilters() {
                const container = document.getElementById('facilityFilters');

                // Keep the "All Facilities" option
                let html = `<span class="facility-filter-badge active" data-facility="all">All Facilities</span>`;

                // Add top 10 facilities (to avoid overwhelming the UI)
                const topFacilities = this.facilities.slice(0, 12);
                topFacilities.forEach(facility => {
                    const facilityId = facility.facility_id || facility.id;
                    const facilityName = facility.facility_name || facility.name;
                    html += `<span class="facility-filter-badge" data-facility="${facilityId}">${this.truncate(facilityName, 20)}</span>`;
                });

                container.innerHTML = html;

                // Attach click handlers
                container.querySelectorAll('.facility-filter-badge').forEach(badge => {
                    badge.addEventListener('click', () => {
                        container.querySelectorAll('.facility-filter-badge').forEach(b => b.classList.remove('active'));
                        badge.classList.add('active');
                        this.selectedFacility = badge.dataset.facility;
                        this.renderMatrix();
                    });
                });
            }

            attachEvents() {
                // Date navigation
                document.getElementById('prevDayBtn')?.addEventListener('click', () => {
                    this.currentDate.setDate(this.currentDate.getDate() - 1);
                    this.refresh();
                });

                document.getElementById('nextDayBtn')?.addEventListener('click', () => {
                    this.currentDate.setDate(this.currentDate.getDate() + 1);
                    this.refresh();
                });

                document.getElementById('todayBtn')?.addEventListener('click', () => {
                    this.currentDate = new Date();
                    this.refresh();
                });

                // Setup date picker
                this.setupDatePicker();

                // Time range selector
                document.querySelectorAll('.time-range-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        this.timeRange = btn.dataset.range;
                        this.refresh();
                    });
                });

                // Search input
                const searchInput = document.getElementById('searchInput');
                let searchTimeout;
                searchInput?.addEventListener('input', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.searchQuery = e.target.value;
                        this.renderMatrix();
                    }, 300);
                });
            }

async refresh() {
    this.showLoading(true);
    await this.loadData();
    this.renderMatrix();
    this.showLoading(false);
    
    // Update date picker value
    const datePicker = document.getElementById('datePicker');
    if (datePicker) {
        datePicker.value = this.formatDate(this.currentDate);
    }
}

            showLoading(show) {
                const container = document.getElementById('availabilityMatrix');
                if (show) {
                    container.innerHTML = `
                    <div class="loading-overlay">
                        <div class="loading-spinner"></div>
                    </div>
                `;
                }
            }

            showError(message) {
                const container = document.getElementById('availabilityMatrix');
                container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>${message}</p>
                    <button class="btn btn-primary btn-sm mt-3" onclick="location.reload()">Retry</button>
                </div>
            `;
            }

            updateDateDisplay() {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('currentDateDisplay').textContent =
                    this.currentDate.toLocaleDateString('en-US', options);
            }

            formatDate(date) {
                return date.toISOString().split('T')[0];
            }

            formatDisplayDate(date) {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }

            formatTime(timeStr) {
                const [hour, minute] = timeStr.split(':');
                const hour12 = hour % 12 || 12;
                const ampm = hour >= 12 ? 'PM' : 'AM';
                return minute === '00' ? `${hour12}${ampm}` : `${hour12}:${minute}${ampm}`;
            }

            truncate(str, maxLen) {
                if (!str) return '';
                return str.length > maxLen ? str.substring(0, maxLen - 3) + '...' : str;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            window.availabilityMatrix = new AvailabilityMatrix();
        });
    </script>
@endsection

@section('scripts')
    <!-- No external dependencies needed - pure vanilla JS -->
@endsection