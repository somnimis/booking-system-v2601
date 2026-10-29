// ============================================
// Availability Matrix — lean implementation.
// Shared by admin + public calendars.
// Bug class previously: partial cache invalidation caused
// events to disappear on revisit. Simplified: no
// eventsCache/statusCache/rAF layers. Every load rebuilds
// derived data from a single fresh API response.
// ============================================

class AvailabilityMatrix {
    constructor() {
        this.currentDate = new Date();
        this.timeRange = "8-12";
        this.facilityHierarchy = [];
        this.selectedFacility = "all";
        this.searchQuery = "";
        this.currentView = "venues";

        // Derived data — rebuilt on every loadEventsForCurrentDate()
        this.requisitions = [];
        this.calendarEvents = [];
        this.requisitionsByFacility = new Map();

        // Request coordination
        this.loading = false;
        this.abortController = null;
        this.searchDebounce = null;

        this.elements = {};
        this.init();
    }

    async init() {
        this.cacheElements();
        await this.loadFacilityHierarchy();
        this.renderFacilityFilter();
        await this.loadEventsForCurrentDate();
        this.renderMatrix();
        this.attachEvents();
    }

    cacheElements() {
        this.elements = {
            container: document.getElementById("availabilityMatrix"),
            facilitySelect: document.getElementById("facilitySelect"),
            filterLabel: document.getElementById("filterLabel"),
            searchInput: document.getElementById("searchInput"),
            clearBtn: document.getElementById("clearFiltersBtn"),
            datePicker: document.getElementById("datePicker"),
            currentDateDisplay: document.getElementById("currentDateDisplay"),
        };
    }

    async loadFacilityHierarchy() {
        try {
            const res = await fetch("/api/availability/facilities/hierarchy");
            const json = await res.json();
            if (json.success) {
                this.facilityHierarchy = json.data.hierarchy;
                this.preProcessFacilities();
            }
        } catch (err) {
            console.error("Error loading facility hierarchy:", err);
        }
    }

    preProcessFacilities() {
        this.venuesList = this.facilityHierarchy.filter(
            (p) => !p.children || p.children.length === 0
        );
        this.parentsWithChildren = this.facilityHierarchy.filter(
            (p) => p.children && p.children.length > 0
        );

        this.facilityMap = new Map();
        for (const v of this.venuesList) this.facilityMap.set(v.facility_id, v);
        for (const p of this.parentsWithChildren) {
            this.facilityMap.set(p.facility_id, p);
            for (const c of p.children) {
                this.facilityMap.set(c.facility_id, c);
                c.parentId = p.facility_id;
            }
        }
    }

    /**
     * Load events for the current date.
     * Guarded so overlapping refreshes don't render stale data.
     */
    async loadEventsForCurrentDate() {
        if (this.loading) return;
        this.loading = true;
        this.showLoading(true);

        if (this.abortController) this.abortController.abort();
        this.abortController = new AbortController();

        const dateKey = this.formatDate(this.currentDate);

        try {
            const params = new URLSearchParams({ start_date: dateKey, end_date: dateKey });
            if (this.selectedFacility !== "all" && this.currentView === "venues") {
                params.set("facility_id", this.selectedFacility);
            }

            const res = await fetch(`/api/availability/events?${params}`, {
                signal: this.abortController.signal,
            });
            const json = await res.json();

            if (json.success) {
                this.requisitions = json.data.requisitions || [];
                this.calendarEvents = json.data.calendar_events || [];
                this.indexRequisitionsByFacility();
            } else {
                this.showError("Failed to load availability data");
            }
        } catch (err) {
            if (err.name !== "AbortError") {
                console.error("Error loading events:", err);
                this.showError("Failed to load availability data");
            }
        } finally {
            this.loading = false;
            this.showLoading(false);
        }
    }

    /** Rebuild facility -> requisitions index from scratch. */
    indexRequisitionsByFacility() {
        this.requisitionsByFacility = new Map();
        for (const req of this.requisitions) {
            for (const f of req.facilities || []) {
                const fid = String(f.facility_id);
                if (!this.requisitionsByFacility.has(fid)) {
                    this.requisitionsByFacility.set(fid, []);
                }
                this.requisitionsByFacility.get(fid).push(req);
            }
        }
    }

    getTimeSlots() {
        const [startHour, endHour] = this.timeRange.split("-").map(Number);
        const slots = [];
        for (let h = startHour; h < endHour; h++) {
            slots.push(`${String(h).padStart(2, "0")}:00`);
            slots.push(`${String(h).padStart(2, "0")}:30`);
        }
        return slots;
    }

    /**
     * Determine status for a facility row at a time slot.
     * Overlap: interval [slotStart, slotEnd) intersects [eventStart, eventEnd).
     */
    getStatusForTimeSlot(facilityId, timeSlot) {
        const slotStart = this.timeSlotToMinutes(timeSlot);
        const slotEnd = slotStart + 30;

        // Calendar events (checked first — system-wide overrides)
        const calEvent = this.calendarEvents.find((ev) => {
            if (!this.timeOverlaps(slotStart, slotEnd, ev)) return false;
            // hall_booking → only on its venue rows
            if (ev.event_type === "hall_booking") {
                return (ev.venues || []).some(
                    (v) => String(v.facility_id) === String(facilityId)
                );
            }
            // school_event / holiday → global
            return true;
        });

        if (calEvent) {
            return {
                status: "event",
                text: '<i class="bi bi-calendar-event-fill"></i> Event',
                event: calEvent,
                eventType: "calendar_event",
                bookable: false,
            };
        }

        // Requisitions for this facility
        const reqs = this.requisitionsByFacility.get(String(facilityId)) || [];
        const req = reqs.find((r) => this.timeOverlaps(slotStart, slotEnd, r));

        if (req) {
            // Per FormStatusSeeder: Reserved = booked; everything else = pending.
            if (req.status === "Reserved") {
                return {
                    status: "booked",
                    text: '<i class="bi bi-x-circle-fill"></i> Booked',
                    event: req,
                    eventType: "requisition",
                    bookable: false,
                };
            }
            return {
                status: "pending",
                text: '<i class="bi bi-clock-fill"></i> Pending',
                event: req,
                eventType: "requisition",
                bookable: false,
            };
        }

        return {
            status: "available",
            text: '<i class="bi bi-check-circle-fill"></i> Available',
            event: null,
            bookable: true,
        };
    }

    /** Check if [slotStart, slotEnd) overlaps an event/requisition. */
    timeOverlaps(slotStart, slotEnd, item) {
        const d = this.formatDate(this.currentDate);
        const startsToday = item.start_date === d;
        const endsToday = item.end_date === d;

        if (item.all_day && startsToday && endsToday) return true;

        const evStart = startsToday
            ? this.timeSlotToMinutes(this.normalizeTime(item.start_time))
            : 0;
        const evEnd = endsToday
            ? this.timeSlotToMinutes(this.normalizeTime(item.end_time))
            : 24 * 60;

        // Treat end time of "00:00" as end-of-day when event spans > 1 day
        const endMinutes = evEnd === 0 && !endsToday ? 24 * 60 : evEnd;

        return slotStart < endMinutes && slotEnd > evStart;
    }

    timeSlotToMinutes(t) {
        if (!t) return 0;
        const [h, m] = t.split(":").map(Number);
        return h * 60 + (m || 0);
    }

    normalizeTime(t) {
        if (!t) return "00:00";
        return t.substring(0, 5);
    }

    getFacilitiesToShow() {
        if (this.currentView === "venues") {
            let venues = [...this.venuesList];
            if (this.selectedFacility !== "all") {
                venues = venues.filter((f) => f.facility_id == this.selectedFacility);
            }
            return venues;
        }
        if (this.selectedFacility !== "all") {
            const parent = this.facilityMap.get(parseInt(this.selectedFacility));
            return parent && parent.children ? [...parent.children] : [];
        }
        const all = [];
        for (const p of this.parentsWithChildren) all.push(...p.children);
        return all;
    }

    renderMatrix() {
        this.removeHoverCard();
        if (!this.elements.container) return;

        const slots = this.getTimeSlots();
        let facilities = this.getFacilitiesToShow();

        if (this.searchQuery) {
            const q = this.searchQuery.toLowerCase();
            facilities = facilities.filter((f) =>
                f.facility_name.toLowerCase().includes(q)
            );
        }

        if (facilities.length === 0) {
            this.elements.container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-building"></i>
                    <p>No ${this.currentView} available</p>
                </div>`;
            return;
        }

        const headerCells = slots
            .map((s) => `<th>${this.formatTime(s)}</th>`)
            .join("");

        const rows = facilities
            .map((f) => this.renderFacilityRow(f, slots))
            .join("");

        this.elements.container.innerHTML = `
            <table class="matrix-table">
                <thead><tr>
                    <th>${this.currentView === "venues" ? "Venue" : "Room"} / Time</th>
                    ${headerCells}
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        `;

        this.updateDateDisplay();
        this.attachRowClickHandlers();
        this.attachHoverEvents();
    }

    renderFacilityRow(facility, slots) {
        const cells = slots.map((slot) => {
            const s = this.getStatusForTimeSlot(facility.facility_id, slot);
            const eventId = s.event?.request_id || s.event?.event_id || "";
            const eventType = s.eventType || "";
            const tooltip =
                s.status !== "available"
                    ? `data-tooltip="${this.escapeHtmlAttr(
                          s.event?.title || s.event?.event_name || ""
                      )}"`
                    : "";

            return `
                <td>
                    <div class="status-card ${s.status}"
                         data-status="${s.status}"
                         data-facility="${facility.facility_id}"
                         data-time="${slot}"
                         data-event-id="${eventId}"
                         data-event-type="${eventType}"
                         data-bookable="${s.bookable}"
                         ${tooltip}>
                        ${s.text}
                    </div>
                </td>`;
        }).join("");

        return `
            <tr data-facility-id="${facility.facility_id}">
                <td class="facility-cell" data-tooltip="${this.escapeHtmlAttr(facility.facility_name)}">
                    <strong>${this.truncate(facility.facility_name, 30)}</strong>
                    ${facility.capacity ? `<small>(${facility.capacity} pax)</small>` : ""}
                </td>
                ${cells}
            </tr>`;
    }

    renderFacilityFilter() {
        if (!this.elements.facilitySelect || !this.elements.filterLabel) return;

        if (this.currentView === "venues") {
            this.elements.filterLabel.textContent = "Venues";
            let html = '<option value="all">All Venues</option>';
            for (const f of this.venuesList) {
                html += `<option value="${f.facility_id}">${this.truncate(
                    f.facility_name,
                    45
                )}${f.capacity ? ` (${f.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = html;
        } else {
            this.elements.filterLabel.textContent = "Campus Rooms";
            let html = '<option value="all">All Rooms</option>';
            for (const p of this.parentsWithChildren) {
                html += `<option value="parent_${p.facility_id}">📁 ${this.truncate(
                    p.facility_name,
                    45
                )}${p.capacity ? ` (${p.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = html;
        }
        this.elements.facilitySelect.value = "all";
    }

    attachRowClickHandlers() {
        if (!this.elements.container) return;
        this.elements.container
            .querySelectorAll(
                ".status-card.booked, .status-card.pending, .status-card.event"
            )
            .forEach((card) => {
                card.removeEventListener("click", this.handleCardClick);
                card.addEventListener("click", this.handleCardClick);
            });
    }

    handleCardClick = (e) => {
        e.stopPropagation();
        const card = e.currentTarget;
        const id = card.dataset.eventId;
        const type = card.dataset.eventType;
        if (!id) return;

        if (window.AvailabilityMode === "admin" && type === "requisition") {
            // Admin: jump to requisition view
            window.location.href = `/admin/requisitions/${id}`;
            return;
        }
        this.showEventDetails(id, type);
    };

    attachHoverEvents() {
        if (!this.elements.container) return;
        this.elements.container
            .querySelectorAll(
                ".status-card.booked, .status-card.pending, .status-card.event"
            )
            .forEach((card) => {
                card.removeEventListener("mouseenter", this.handleCardMouseEnter);
                card.removeEventListener("mouseleave", this.handleCardMouseLeave);
                card.addEventListener("mouseenter", this.handleCardMouseEnter);
                card.addEventListener("mouseleave", this.handleCardMouseLeave);
            });
    }

    handleCardMouseEnter = (e) => {
        const card = e.currentTarget;
        this.hoverTimer = setTimeout(() => this.showHoverCard(card), 150);
    };

    handleCardMouseLeave = () => {
        if (this.hoverTimer) {
            clearTimeout(this.hoverTimer);
            this.hoverTimer = null;
        }
        this.removeHoverCard();
    };

    showHoverCard(card) {
        this.removeHoverCard();
        const id = card.dataset.eventId;
        const type = card.dataset.eventType;
        const status = card.dataset.status;
        if (!id) return;

        const data =
            type === "calendar_event"
                ? this.calendarEvents.find((e) => e.event_id == id)
                : this.requisitions.find((r) => r.request_id == id);
        if (!data) return;

        const colors = { booked: "#ef4444", pending: "#f5bc40", event: "#6f42c1" };
        const accent = colors[status] || "#6c757d";

        const el = document.createElement("div");
        el.className = "custom-hover-card";
        el.style.cssText = `
            position:fixed;z-index:9999999;background:#fff;border-radius:12px;
            box-shadow:0 12px 40px rgba(4,26,75,.16);padding:.85rem 1rem;
            min-width:240px;max-width:320px;pointer-events:none;
            border-left:3px solid ${accent};font-family:'DM Sans',sans-serif;
            transition:opacity .15s ease;opacity:0;
        `;

        let html = "";
        if (type === "requisition") {
            const label = status === "booked" ? "Booked Request" : "Pending Approval";
            html = `
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                    <i class="bi bi-calendar-check-fill" style="color:${accent};"></i>
                    <span style="font-weight:700;color:#041a4b;font-size:.85rem;">${label}</span>
                    <span style="background:${accent}22;color:${accent};padding:.15rem .5rem;border-radius:20px;font-size:.65rem;font-weight:600;">
                        ${this.escapeHtml(data.status || "")}
                    </span>
                </div>
                <div style="font-weight:600;color:#1e2d4a;font-size:.9rem;margin-bottom:.5rem;border-bottom:1px solid #e2e6f0;padding-bottom:.4rem;">
                    ${this.escapeHtml(data.title || "Untitled")}
                </div>
                <div style="font-size:.75rem;color:#6b7a99;display:flex;align-items:center;gap:.5rem;">
                    <i class="bi bi-calendar3" style="width:16px;"></i>
                    <span>${this.formatRange(data)}</span>
                </div>`;
        } else {
            html = `
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                    <i class="bi bi-calendar-event-fill" style="color:${accent};"></i>
                    <span style="font-weight:700;color:#041a4b;font-size:.85rem;">System Event</span>
                    <span style="background:${accent}22;color:${accent};padding:.15rem .5rem;border-radius:20px;font-size:.65rem;font-weight:600;">
                        ${this.escapeHtml(data.event_type || "")}
                    </span>
                </div>
                <div style="font-weight:600;color:#1e2d4a;font-size:.9rem;margin-bottom:.5rem;border-bottom:1px solid #e2e6f0;padding-bottom:.4rem;">
                    ${this.escapeHtml(data.event_name || "Untitled")}
                </div>
                <div style="font-size:.75rem;color:#6b7a99;display:flex;align-items:center;gap:.5rem;">
                    <i class="bi bi-calendar3" style="width:16px;"></i>
                    <span>${this.formatRange(data)}</span>
                </div>`;
        }

        el.innerHTML = html;
        document.body.appendChild(el);

        const rect = card.getBoundingClientRect();
        const cr = el.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let left = rect.left + rect.width / 2 - cr.width / 2;
        let top = rect.top - cr.height - 10;
        if (left < 10) left = 10;
        if (left + cr.width > vw - 10) left = vw - cr.width - 10;
        if (top < 10) top = rect.bottom + 10;
        if (top + cr.height > vh - 10) top = vh - cr.height - 10;

        el.style.left = `${left}px`;
        el.style.top = `${top}px`;
        requestAnimationFrame(() => (el.style.opacity = "1"));
        this.hoverCardEl = el;
    }

    removeHoverCard() {
        if (this.hoverCardEl) {
            this.hoverCardEl.remove();
            this.hoverCardEl = null;
        }
    }

    async showEventDetails(id, type) {
        const data =
            type === "calendar_event"
                ? this.calendarEvents.find((e) => e.event_id == id)
                : this.requisitions.find((r) => r.request_id == id);
        if (!data) return;

        const existing = document.getElementById("eventModal");
        if (existing) existing.remove();

        const title = data.title || data.event_name || "Details";
        const body =
            type === "calendar_event"
                ? `
                    <div class="event-detail-row"><div class="event-detail-label">Description</div>
                        <div class="event-detail-value">${this.escapeHtml(data.description || "—")}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Schedule</div>
                        <div class="event-detail-value">${this.formatRange(data)}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Type</div>
                        <div class="event-detail-value">${this.escapeHtml(data.event_type || "")}</div></div>`
                : `
                    <div class="event-detail-row"><div class="event-detail-label">Status</div>
                        <div class="event-detail-value">${this.escapeHtml(data.status || "")}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Schedule</div>
                        <div class="event-detail-value">${this.formatRange(data)}</div></div>`;

        document.body.insertAdjacentHTML(
            "beforeend",
            `<div class="modal fade event-modal" id="eventModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${this.escapeHtml(title)}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${body}</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>`
        );

        const modalEl = document.getElementById("eventModal");
        new bootstrap.Modal(modalEl).show();
        modalEl.addEventListener("hidden.bs.modal", () => modalEl.remove());
    }

    attachEvents() {
        document.getElementById("prevDayBtn")?.addEventListener("click", async () => {
            this.currentDate.setDate(this.currentDate.getDate() - 1);
            await this.refresh();
        });
        document.getElementById("nextDayBtn")?.addEventListener("click", async () => {
            this.currentDate.setDate(this.currentDate.getDate() + 1);
            await this.refresh();
        });
        document.getElementById("todayBtn")?.addEventListener("click", async () => {
            this.currentDate = new Date();
            await this.refresh();
        });

        this.setupDatePicker();

        document.querySelectorAll(".view-type-btn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const view = e.currentTarget.dataset.view;
                if (view === this.currentView) return;
                document.querySelectorAll(".view-type-btn").forEach((b) =>
                    b.classList.remove("active")
                );
                e.currentTarget.classList.add("active");
                this.currentView = view;
                this.selectedFacility = "all";
                this.searchQuery = "";
                if (this.elements.searchInput) this.elements.searchInput.value = "";
                this.renderFacilityFilter();
                this.refresh();
            });
        });

        document.querySelectorAll(".time-range-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                document.querySelectorAll(".time-range-btn").forEach((b) =>
                    b.classList.remove("active")
                );
                btn.classList.add("active");
                this.timeRange = btn.dataset.range;
                this.renderMatrix();
            });
        });

        this.elements.searchInput?.addEventListener("input", (e) => {
            if (this.searchDebounce) clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => {
                this.searchQuery = e.target.value;
                this.renderMatrix();
            }, 300);
        });

        this.elements.clearBtn?.addEventListener("click", async () => {
            this.searchQuery = "";
            this.selectedFacility = "all";
            if (this.elements.searchInput) this.elements.searchInput.value = "";
            if (this.elements.facilitySelect) this.elements.facilitySelect.value = "all";
            await this.refresh();
        });

        this.elements.facilitySelect?.addEventListener("change", (e) => {
            let v = e.target.value;
            if (this.currentView === "rooms" && v !== "all") {
                const m = v.match(/parent_(\d+)/);
                this.selectedFacility = m ? m[1] : v;
            } else {
                this.selectedFacility = v;
            }
            this.refresh();
        });
    }

    setupDatePicker() {
        if (!this.elements.datePicker) return;
        this.elements.datePicker.value = this.formatDate(this.currentDate);
        this.elements.datePicker.addEventListener("change", async (e) => {
            const [y, m, d] = e.target.value.split("-").map(Number);
            const picked = new Date(y, m - 1, d);
            if (!isNaN(picked.getTime())) {
                this.currentDate = picked;
                await this.refresh();
            }
        });
    }

    async refresh() {
        await this.loadEventsForCurrentDate();
        this.renderMatrix();
        if (this.elements.datePicker) {
            this.elements.datePicker.value = this.formatDate(this.currentDate);
        }
    }

    showLoading(show) {
        if (!this.elements.container) return;
        if (show) {
            if (!this.elements.container.querySelector(".matrix-table")) {
                this.elements.container.innerHTML = `
                    <div class="loading-overlay">
                        <div class="loading-spinner"></div>
                        <p style="margin-top:10px;">Loading…</p>
                    </div>`;
            }
        } else {
            this.elements.container
                .querySelector(".loading-overlay")
                ?.remove();
        }
    }

    showError(msg) {
        if (!this.elements.container) return;
        this.elements.container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <p>${this.escapeHtml(msg)}</p>
                <button class="btn btn-primary btn-sm mt-3" onclick="location.reload()">Retry</button>
            </div>`;
    }

    updateDateDisplay() {
        if (this.elements.currentDateDisplay) {
            this.elements.currentDateDisplay.textContent =
                this.currentDate.toLocaleDateString("en-US", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
        }
    }

    formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    formatTime(t) {
        const [h, m] = t.split(":");
        const hn = parseInt(h, 10);
        const h12 = hn % 12 || 12;
        return `${h12}:${m}${hn >= 12 ? "pm" : "am"}`;
    }

    formatRange(item) {
        if (!item) return "";
        if (item.all_day) return "All Day";
        const s = this.normalizeTime(item.start_time);
        const e = this.normalizeTime(item.end_time);
        return `${this.formatTime(s)} – ${this.formatTime(e)}`;
    }

    truncate(str, len) {
        if (!str) return "";
        return str.length > len ? `${str.substring(0, len - 3)}...` : str;
    }

    escapeHtml(t) {
        if (t == null) return "";
        const d = document.createElement("div");
        d.textContent = String(t);
        return d.innerHTML;
    }

    escapeHtmlAttr(t) {
        return this.escapeHtml(t).replace(/"/g, "&quot;");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    window.availabilityMatrix = new AvailabilityMatrix();
});