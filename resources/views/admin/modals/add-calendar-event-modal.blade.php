{{-- Add new calendar event modal --}}
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