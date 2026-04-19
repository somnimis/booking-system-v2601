// calendar.js - SIMPLE AVAILABILITY MATRIX MODAL
// Replaces FullCalendar with lightweight 30-minute slot matrix

class SimpleAvailabilityCalendar {
    constructor() {
        this.currentDate = new Date();
        this.timeSlots = this.generateTimeSlots();
        this.eventsCache = new Map();
        this.currentFacility = null;
        this.isLoading = false;
    }

    getEquipmentStatusForTimeSlot(
        facilityId,
        timeSlot,
        requisitions,
        calendarEvents,
    ) {
        const statusData = this.getStatusForTimeSlot(
            facilityId,
            timeSlot,
            requisitions,
            calendarEvents,
        );

        if (statusData.status === "booked") {
            const bookedCount = requisitions.filter((req) => {
                const matchesFacility = req.facilities?.some(
                    (f) => String(f.facility_id) === String(facilityId),
                );
                if (!matchesFacility) return false;
                if (req.all_day) return true;
                const reqStart = req.start_time?.substring(0, 5);
                const reqEnd = req.end_time?.substring(0, 5);
                return (
                    reqStart <= timeSlot &&
                    reqEnd > timeSlot &&
                    ["Scheduled", "Ongoing"].includes(req.status)
                );
            }).length;

            const available =
                (this.currentFacility.totalQuantity || 0) - bookedCount;
            statusData.text = `📦 ${available}/${this.currentFacility.totalQuantity || 0}`;
            statusData.tooltip = `${available} of ${this.currentFacility.totalQuantity} available`;
        } else if (
            statusData.status === "available" &&
            this.currentFacility.totalQuantity
        ) {
            statusData.text = `📦 ${this.currentFacility.totalQuantity}/${this.currentFacility.totalQuantity}`;
            statusData.tooltip = `${this.currentFacility.totalQuantity} units available`;
        }

        return statusData;
    }

    generateTimeSlots() {
        const slots = [];
        for (let hour = 0; hour < 24; hour++) {
            slots.push(`${hour.toString().padStart(2, "0")}:00`);
            slots.push(`${hour.toString().padStart(2, "0")}:30`);
        }
        return slots;
    }

    formatDate(date) {
        return date.toISOString().split("T")[0];
    }

    formatTime(timeStr) {
        const [hour, minute] = timeStr.split(":");
        const hour12 = hour % 12 || 12;
        const ampm = hour >= 12 ? "AM" : "PM";
        return minute === "00" ? `${hour12}:00${ampm}` : `${hour12}:30${ampm}`;
    }

    formatDisplayDate(date) {
        return date.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    }

    escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    async loadEventsForDate(date, facilityId) {
        const dateKey = `${this.formatDate(date)}_${facilityId}`;

        if (this.eventsCache.has(dateKey)) {
            return this.eventsCache.get(dateKey);
        }

        try {
            const params = new URLSearchParams({
                start_date: this.formatDate(date),
                end_date: this.formatDate(date),
                facility_id: facilityId,
            });

            const response = await fetch(`/api/availability/events?${params}`);
            const result = await response.json();

            if (result.success) {
                const data = {
                    requisitions: result.data.requisitions || [],
                    calendarEvents: result.data.calendar_events || [],
                };
                this.eventsCache.set(dateKey, data);
                this.cleanCache();
                return data;
            }
        } catch (error) {
            console.error("Error loading events:", error);
        }
        return { requisitions: [], calendarEvents: [] };
    }

    cleanCache() {
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        for (const [key] of this.eventsCache) {
            const datePart = key.split("_")[0];
            if (new Date(datePart) < sevenDaysAgo) {
                this.eventsCache.delete(key);
            }
        }
    }

    getStatusForTimeSlot(facilityId, timeSlot, requisitions, calendarEvents) {
        // Check calendar events first
        const eventAtSlot = calendarEvents.find((event) => {
            if (event.all_day) return true;
            const eventStart = event.start_time?.substring(0, 5);
            const eventEnd = event.end_time?.substring(0, 5);
            return eventStart <= timeSlot && eventEnd > timeSlot;
        });

        if (eventAtSlot) {
            // Truncate event name if too long
            const eventName = eventAtSlot.event_name || "Event";
            const displayText =
                eventName.length > 12
                    ? eventName.substring(0, 10) + "..."
                    : eventName;

            return {
                status: "event",
                text: `📅 ${displayText}`,
                event: eventAtSlot,
                tooltip: eventAtSlot.event_name,
                bookable: false,
            };
        }

        // Check requisitions
        const reqAtSlot = requisitions.find((req) => {
            const matchesFacility = req.facilities?.some(
                (f) => String(f.facility_id) === String(facilityId),
            );
            if (!matchesFacility) return false;
            if (req.all_day) return true;

            const reqStart = req.start_time?.substring(0, 5);
            const reqEnd = req.end_time?.substring(0, 5);
            return reqStart <= timeSlot && reqEnd > timeSlot;
        });

        if (reqAtSlot) {
            const isApproved = ["Scheduled", "Ongoing"].includes(
                reqAtSlot.status,
            );
            const isPending = ["Pending Approval"].includes(reqAtSlot.status);

            if (isApproved) {
                return {
                    status: "booked",
                    text: "🔴",
                    tooltip: reqAtSlot.title,
                    bookable: false,
                    event: reqAtSlot,
                };
            }
            if (isPending) {
                return {
                    status: "pending",
                    text: "🟡",
                    tooltip: `${reqAtSlot.title} (Pending)`,
                    bookable: false,
                    event: reqAtSlot,
                };
            }
        }

        return {
            status: "available",
            text: "✅",
            tooltip: "Click to book",
            bookable: true,
        };
    }

    async showModal(facilityData) {
        this.currentFacility = {
            ...facilityData,
            type: facilityData.type || "facility",
            totalQuantity:
                facilityData.totalQuantity ||
                facilityData.availableQuantity ||
                0,
        };
        this.currentDate = new Date();

        const modalHtml = `
            <div class="modal fade" id="availabilityCalendarModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%;">
                    <div class="modal-content" style="min-height: 75vh;">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-calendar-check me-2"></i>
                                ${this.escapeHtml(facilityData.name)} - Availability Schedule
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-3">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-body text-center">
                                            <img src="${this.escapeHtml(facilityData.image)}" class="img-fluid rounded mb-2" style="max-height: 100px;" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                                            <div class="small text-muted">${this.escapeHtml(facilityData.category)}</div>
                                            <div class="mt-2">
                                                <span class="badge" style="background-color: ${facilityData.statusColor}">${facilityData.status}</span>
                                            </div>
                                            ${facilityData.capacity ? `<div class="mt-2 small"><i class="bi bi-people"></i> Capacity: ${facilityData.capacity}</div>` : ""}
                                            ${facilityData.fee ? `<div class="small"><i class="bi bi-cash"></i> ₱${facilityData.fee}</div>` : ""}
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <button class="btn btn-sm btn-outline-secondary" id="calPrevDay"><i class="bi bi-chevron-left"></i></button>
                                                <h6 class="mb-0" id="calCurrentDate">${this.formatDisplayDate(this.currentDate)}</h6>
                                                <button class="btn btn-sm btn-outline-secondary" id="calNextDay"><i class="bi bi-chevron-right"></i></button>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary w-100" id="calTodayBtn">Today</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-10 col-md-12">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="d-flex flex-wrap gap-3 mb-3 pb-2 border-bottom">
                                                <span><span style="background:#d4edda; display:inline-block; width:16px; height:16px; border-radius:3px;"></span> Available</span>
                                                <span><span style="background:#f8d7da; display:inline-block; width:16px; height:16px; border-radius:3px;"></span> Booked</span>
                                                <span><span style="background:#fff3cd; display:inline-block; width:16px; height:16px; border-radius:3px;"></span> Pending</span>
                                                <span><span style="background:#d1ecf1; display:inline-block; width:16px; height:16px; border-radius:3px;"></span> Event</span>
                                            </div>
                                            <div id="availabilityMatrixContainer" style="overflow-x: auto; max-height: 55vh; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="modalBookNowBtn">
                                <i class="bi bi-calendar-plus"></i> Book This Facility
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if present
        const existingModal = document.getElementById(
            "availabilityCalendarModal",
        );
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML("beforeend", modalHtml);

        const modal = new bootstrap.Modal(
            document.getElementById("availabilityCalendarModal"),
        );
        await this.renderMatrix();
        this.attachModalEvents(modal);
        modal.show();
    }

    async renderMatrix() {
        const container = document.getElementById(
            "availabilityMatrixContainer",
        );
        if (!container) return;

        const { requisitions, calendarEvents } = await this.loadEventsForDate(
            this.currentDate,
            this.currentFacility.id,
        );
        const isEquipment = this.currentFacility.type === "equipment";

        let html = `
        <table class="table table-bordered table-sm" style="min-width: 800px;">
            <thead>
                <tr>
                    <th style="position: sticky; left: 0; background: white; width: 120px;">Time</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
    `;

        for (const slot of this.timeSlots) {
            let statusData;
            if (isEquipment) {
                statusData = this.getEquipmentStatusForTimeSlot(
                    this.currentFacility.id,
                    slot,
                    requisitions,
                    calendarEvents,
                );
            } else {
                statusData = this.getStatusForTimeSlot(
                    this.currentFacility.id,
                    slot,
                    requisitions,
                    calendarEvents,
                );
            }

            let bgColor = "";
            if (statusData.status === "available") bgColor = "#d4edda";
            else if (statusData.status === "booked") bgColor = "#f8d7da";
            else if (statusData.status === "pending") bgColor = "#fff3cd";
            else if (statusData.status === "event") bgColor = "#d1ecf1";

            html += `
            <tr>
                <td style="background: #f8f9fa; font-weight: 500;">${this.formatTime(slot)}</td>
                <td style="background: ${bgColor}; cursor: ${statusData.bookable ? "pointer" : "default"};"
                    class="availability-slot"
                    data-time="${slot}"
                    data-bookable="${statusData.bookable}"
                    data-status="${statusData.status}"
                    data-tooltip="${this.escapeHtml(statusData.tooltip)}"
                    data-event-id="${statusData.event?.request_id || statusData.event?.event_id || ""}"
                    data-event-type="${statusData.event?.request_id ? "requisition" : "calendar_event"}">
                    <div class="text-center py-1">
                        ${statusData.text} ${statusData.status !== "available" && statusData.status !== "booked" ? `<span class="small">${this.escapeHtml(statusData.tooltip?.substring(0, 50) || "")}</span>` : ""}
                        ${statusData.status === "available" && !isEquipment ? '<span class="small">Click to book</span>' : ""}
                    </div>
                 </td>
             </tr>
        `;
        }

        html += `</tbody></table>`;
        container.innerHTML = html;

        document.getElementById("calCurrentDate").textContent =
            this.formatDisplayDate(this.currentDate);

        container
            .querySelectorAll('.availability-slot[data-bookable="true"]')
            .forEach((slot) => {
                slot.removeEventListener("click", this.handleSlotClickBound);
                this.handleSlotClickBound = this.handleSlotClick.bind(this);
                slot.addEventListener("click", this.handleSlotClickBound);
            });

        container
            .querySelectorAll('.availability-slot[data-bookable="false"]')
            .forEach((slot) => {
                slot.removeEventListener(
                    "click",
                    this.handleNonBookableClickBound,
                );
                this.handleNonBookableClickBound =
                    this.handleNonBookableClick.bind(this);
                slot.addEventListener(
                    "click",
                    this.handleNonBookableClickBound,
                );
            });
    }

    handleSlotClick(event) {
        const slot = event.currentTarget;
        const timeSlot = slot.dataset.time;

        const modal = document.getElementById("availabilityCalendarModal");
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }

        // Redirect to requisition form with pre-filled data
        const params = new URLSearchParams({
            facility_id: this.currentFacility.id,
            facility_name: this.currentFacility.name,
            date: this.formatDate(this.currentDate),
            time: timeSlot,
        });
        window.location.href = `/reservation-form?${params.toString()}`;
    }

    async handleNonBookableClick(event) {
        const slot = event.currentTarget;
        const eventId = slot.dataset.eventId;
        const eventType = slot.dataset.eventType;

        if (eventId) {
            await this.showEventDetails(eventId, eventType);
        }
    }

    async showEventDetails(eventId, eventType) {
        try {
            const response = await fetch(
                `/api/availability/event/${eventId}?type=${eventType}`,
            );
            const result = await response.json();

            if (result.success) {
                const event = result.data;
                const modalHtml = `
                    <div class="modal fade" id="eventDetailModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">${this.escapeHtml(event.title || event.event_name || "Event Details")}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Status:</strong> <span class="badge" style="background:${event.color || "#6c757d"}">${event.status || "Event"}</span></p>
                                    <p><strong>Schedule:</strong> ${event.start_date || event.date} ${event.start_time ? this.formatTime(event.start_time) : ""}</p>
                                    <p><strong>Description:</strong> ${this.escapeHtml(event.description || event.purpose || "No description")}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                let modal = document.getElementById("eventDetailModal");
                if (modal) modal.remove();
                document.body.insertAdjacentHTML("beforeend", modalHtml);
                const bsModal = new bootstrap.Modal(
                    document.getElementById("eventDetailModal"),
                );
                bsModal.show();
                document
                    .getElementById("eventDetailModal")
                    .addEventListener("hidden.bs.modal", () => {
                        document.getElementById("eventDetailModal")?.remove();
                    });
            }
        } catch (error) {
            console.error("Error loading event details:", error);
        }
    }

    attachModalEvents(modal) {
        const prevBtn = document.getElementById("calPrevDay");
        const nextBtn = document.getElementById("calNextDay");
        const todayBtn = document.getElementById("calTodayBtn");
        const bookBtn = document.getElementById("modalBookNowBtn");

        if (prevBtn) {
            prevBtn.removeEventListener("click", this.prevDayBound);
            this.prevDayBound = async () => {
                this.currentDate.setDate(this.currentDate.getDate() - 1);
                await this.renderMatrix();
            };
            prevBtn.addEventListener("click", this.prevDayBound);
        }

        if (nextBtn) {
            nextBtn.removeEventListener("click", this.nextDayBound);
            this.nextDayBound = async () => {
                this.currentDate.setDate(this.currentDate.getDate() + 1);
                await this.renderMatrix();
            };
            nextBtn.addEventListener("click", this.nextDayBound);
        }

        if (todayBtn) {
            todayBtn.removeEventListener("click", this.todayBound);
            this.todayBound = async () => {
                this.currentDate = new Date();
                await this.renderMatrix();
            };
            todayBtn.addEventListener("click", this.todayBound);
        }

        if (bookBtn) {
            bookBtn.removeEventListener("click", this.bookNowBound);
            this.bookNowBound = () => {
                modal.hide();
                const params = new URLSearchParams({
                    facility_id: this.currentFacility.id,
                    facility_name: this.currentFacility.name,
                });
                window.location.href = `/reservation-form?${params.toString()}`;
            };
            bookBtn.addEventListener("click", this.bookNowBound);
        }

        // Clean up on modal close
        const modalElement = document.getElementById(
            "availabilityCalendarModal",
        );
        modalElement.addEventListener("hidden.bs.modal", () => {
            modalElement.remove();
        });
    }
}

// === INITIALIZATION === //
// Create global instance and expose function after DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    window.simpleCalendarInstance = new SimpleAvailabilityCalendar();

    // Expose the function globally
    window.showAvailabilityCalendar = function (facilityData) {
        if (window.simpleCalendarInstance) {
            window.simpleCalendarInstance.showModal(facilityData);
        } else {
            console.error("Calendar instance not initialized");
            if (typeof window.showToast === "function") {
                window.showToast(
                    "Calendar module is loading. Please try again.",
                    "warning",
                );
            } else {
                alert("Calendar module is loading. Please try again.");
            }
        }
    };

    console.log("SimpleAvailabilityCalendar initialized and ready");
});
