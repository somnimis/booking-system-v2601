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

        this.venuesPage = 1;
        this.roomsPage = 1;
        this.facilityMeta = {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 10,
        };
        this.allFacilities = [];

        // Derived data — rebuilt on every loadEventsForCurrentDate()
        this.requisitions = [];
        this.calendarEvents = [];
        this.requisitionsByFacility = new Map();

        // Request coordination
        this.loading = false;
        this.searchModeActive = false;
        this.abortController = null;
        this.searchDebounce = null;

        this.elements = {};
        this.init();
    }

    async init() {
        this.cacheElements();
        await Promise.all([
            this.loadFacilityHierarchy(),
            this.loadAllFacilitiesForDropdown(),
        ]);
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
        const page =
            this.currentView === "rooms" ? this.roomsPage : this.venuesPage;
        try {
            const params = new URLSearchParams({
                view: this.currentView,
                page,
                per_page: 10,
            });

            if (this.currentView === "rooms") {
                // If a specific building is selected (dropdown value = "parent_X")
                if (this.selectedFacility.startsWith("parent_")) {
                    params.set(
                        "parent_id",
                        this.selectedFacility.replace("parent_", ""),
                    );
                }
                // If a specific room is selected (dropdown value = "123")
                else if (this.selectedFacility !== "all") {
                    params.set("facility_id", this.selectedFacility);
                }
            } else {
                if (this.selectedFacility !== "all") {
                    params.set("facility_id", this.selectedFacility);
                }
            }

            const res = await fetch(
                `/api/availability/facilities/hierarchy?${params}`,
            );
            const json = await res.json();
            if (json.success) {
                this.facilityHierarchy = json.data.hierarchy;
                this.facilityMeta = json.data.meta;
                this.preProcessFacilities();
            }
        } catch (err) {
            console.error("Error loading facility hierarchy:", err);
        }
    }

    async loadAllFacilitiesForDropdown() {
        // Full list — used only to populate the filter <select>.
        try {
            const res = await fetch(`/api/availability/facilities/list`);
            const json = await res.json();
            if (json.success) {
                this.allFacilities = json.data;
            }
        } catch (err) {
            console.error("Error loading facility list:", err);
        }
    }

    preProcessFacilities() {
        // Flatten: each room becomes its own row in rooms view.
        // For venues, each item is already a row.
        this.venuesList = this.facilityHierarchy.filter(
            (p) => !p.children || p.children.length === 0,
        );

        // For rooms view: flatten buildings + children into a single row list.
        this.roomsList = [];
        for (const parent of this.facilityHierarchy) {
            if (parent.children && parent.children.length > 0) {
                for (const child of parent.children) {
                    child.parentName = parent.facility_name;
                    child.parentId = parent.facility_id;
                    this.roomsList.push(child);
                }
            }
        }

        // Keep a lookup map (used only for the facility filter display if needed).
        this.facilityMap = new Map();
        for (const v of this.venuesList) this.facilityMap.set(v.facility_id, v);
        for (const r of this.roomsList) this.facilityMap.set(r.facility_id, r);
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
            const params = new URLSearchParams({
                start_date: dateKey,
                end_date: dateKey,
            });
            if (
                this.selectedFacility !== "all" &&
                this.currentView === "venues"
            ) {
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
                    (v) => String(v.facility_id) === String(facilityId),
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
            return this.venuesList;
        } else {
            return this.roomsList;
        }
    }

    async runSearch(q) {
        if (!this.elements.container) return;
        this.elements.container.innerHTML = `
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <p style="margin-top:10px;">Searching…</p>
        </div>`;
        try {
            const res = await fetch(
                `/api/availability/search?q=${encodeURIComponent(q)}`,
            );
            const json = await res.json();
            if (json.success) {
                this.enterSearchMode(json.data || [], q);
            } else {
                this.showError("Search failed");
            }
        } catch (err) {
            console.error("Search error:", err);
            this.showError("Search failed");
        }
    }

    enterSearchMode(results, q) {
        if (!this.elements.container) return;
        this.searchModeActive = true;

        if (results.length === 0) {
            this.elements.container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <p>No results for "${this.escapeHtml(q)}"</p>
            </div>`;
            return;
        }

        const items = results
            .map((r) => {
                const schedule =
                    this.formatDateShort(r.start_date) +
                    (r.start_date === r.end_date
                        ? r.all_day
                            ? " · All Day"
                            : ` · ${this.formatTime(this.normalizeTime(r.start_time))} – ${this.formatTime(this.normalizeTime(r.end_time))}`
                        : r.all_day
                          ? ` — ${this.formatDateShort(r.end_date)} (All Day)`
                          : ` ${this.formatTime(this.normalizeTime(r.start_time))} – ${this.formatDateShort(r.end_date)} ${this.formatTime(this.normalizeTime(r.end_time))}`);
                const detail = r.event_details
                    ? `<div class="search-result-snippet">${this.escapeHtml(r.event_details.substring(0, 140))}${r.event_details.length > 140 ? "…" : ""}</div>`
                    : "";
                const facilities =
                    r.facilities && r.facilities.length
                        ? `<div class="search-result-meta"><i class="bi bi-building"></i> ${this.escapeHtml(r.facilities.join(", "))}</div>`
                        : "";
                return `
            <div class="search-result-item" data-type="${r.type}" data-id="${r.id}" data-date="${r.start_date}">
                <div class="search-result-header">
                    <span class="search-result-title">${this.escapeHtml(r.title)}</span>
                <span class="search-result-chip" style="background:${r.status_color || "#6c757d"}22;color:${r.status_color || "#6c757d"};">
                    ${this.escapeHtml(
                        r.type === "calendar_event"
                            ? this.eventTypeLabel(r.status)
                            : r.status || ""
                    )}
                </span>
                </div>
                ${detail}
                ${facilities}
                <div class="search-result-meta"><i class="bi bi-calendar3"></i> ${this.escapeHtml(schedule)}</div>
            </div>`;
            })
            .join("");

        this.elements.container.innerHTML = `<div class="search-results">${items}</div>`;

        this.elements.container
            .querySelectorAll(".search-result-item")
            .forEach((el) => {
                el.addEventListener("click", async () => {
                    const [y, m, d] = el.dataset.date.split("-").map(Number);
                    this.currentDate = new Date(y, m - 1, d);
                    this.elements.searchInput.value = "";
                    this.exitSearchMode();
                    await this.refresh();
                });
            });
    }

    async exitSearchMode() {
        if (!this.searchModeActive) return;
        this.searchModeActive = false;
        // Restore matrix — refresh current date.
        await this.refresh();
    }

    renderMatrix() {
        this.removeHoverCard();
        if (!this.elements.container) return;

        const slots = this.getTimeSlots();
        let facilities = this.getFacilitiesToShow();

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
        this.renderPagination();
    }

    renderPagination() {
        const wrapper = document.getElementById("matrixWrapper");
        if (!wrapper) return;
        wrapper.querySelector(".pagination-bar")?.remove();

        if (this.facilityMeta.last_page <= 1) return;

        const label =
            this.currentView === "rooms"
                ? `Building Group ${this.facilityMeta.current_page} of ${this.facilityMeta.last_page}`
                : `Page ${this.facilityMeta.current_page} of ${this.facilityMeta.last_page}`;

        const bar = document.createElement("div");
        bar.className = "pagination-bar";
        bar.innerHTML = `
            <button class="page-btn" id="prevPageBtn" ${this.facilityMeta.current_page <= 1 ? "disabled" : ""}>‹ Prev</button>
            <span class="page-indicator">${label}</span>
            <button class="page-btn" id="nextPageBtn" ${this.facilityMeta.current_page >= this.facilityMeta.last_page ? "disabled" : ""}>Next ›</button>
        `;
        wrapper.appendChild(bar);

        document
            .getElementById("prevPageBtn")
            ?.addEventListener("click", () => this.changePage(-1));
        document
            .getElementById("nextPageBtn")
            ?.addEventListener("click", () => this.changePage(1));
    }

    async changePage(delta) {
        if (this.currentView === "rooms") {
            this.roomsPage = Math.max(1, this.roomsPage + delta);
        } else {
            this.venuesPage = Math.max(1, this.venuesPage + delta);
        }
        await this.loadFacilityHierarchy();
        this.renderMatrix();
    }

    renderFacilityRow(facility, slots) {
        const cells = slots
            .map((slot) => {
                const s = this.getStatusForTimeSlot(facility.facility_id, slot);
                const eventId = s.event?.request_id || s.event?.event_id || "";
                const eventType = s.eventType || "";

                return `
                <td>
                    <div class="status-card ${s.status}"
                        data-status="${s.status}"
                        data-facility="${facility.facility_id}"
                        data-time="${slot}"
                        data-event-id="${eventId}"
                        data-event-type="${eventType}"
                        data-bookable="${s.bookable}">
                        ${s.text}
                    </div>
                </td>`;
            })
            .join("");

        // Label: for rooms, prepend parent building name.
        const label =
            this.currentView === "rooms" && facility.parentName
                ? `<small style="color:#6b7a99;">${this.escapeHtml(facility.parentName)}</small><br><strong>${this.truncate(facility.facility_name, 30)}</strong>`
                : `<strong>${this.truncate(facility.facility_name, 30)}</strong>`;

        return `
            <tr data-facility-id="${facility.facility_id}">
                <td class="facility-cell" data-tooltip="${this.escapeHtmlAttr(facility.facility_name)}">
                    ${label}
                    ${facility.capacity ? `<small>(${facility.capacity} pax)</small>` : ""}
                </td>
                ${cells}
            </tr>`;
    }

    renderFacilityFilter() {
        if (!this.elements.facilitySelect || !this.elements.filterLabel) return;

        // Find all IDs that act as parents (containers).
        const parentIds = new Set(
            this.allFacilities
                .filter((f) => f.parent_facility_id !== null)
                .map((f) => f.parent_facility_id),
        );

        if (this.currentView === "venues") {
            this.elements.filterLabel.textContent = "Venues";
            let html = '<option value="all">All Venues</option>';

            // Only list top-level facilities that are NOT containers.
            const bookableVenues = this.allFacilities.filter(
                (f) =>
                    f.parent_facility_id === null &&
                    !parentIds.has(f.facility_id),
            );

            for (const f of bookableVenues) {
                html += `<option value="${f.facility_id}">${this.truncate(f.facility_name, 45)}${f.capacity ? ` (${f.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = html;
        } else {
            this.elements.filterLabel.textContent = "Campus Rooms";
            let html = '<option value="all">All Rooms</option>';

            // Only list top-level facilities that ARE containers (buildings).
            const buildings = this.allFacilities.filter(
                (f) =>
                    f.parent_facility_id === null &&
                    parentIds.has(f.facility_id),
            );

            for (const p of buildings) {
                html += `<option value="parent_${p.facility_id}">📁 ${this.truncate(p.facility_name, 45)}${p.capacity ? ` (${p.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = html;
        }
        this.elements.facilitySelect.value = "all";
    }

    attachRowClickHandlers() {
        if (!this.elements.container) return;
        this.elements.container
            .querySelectorAll(
                ".status-card.booked, .status-card.pending, .status-card.event",
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
            // Admin: jump to requisition view (singular "requisition" route).
            window.location.href = `/admin/requisition/${id}`;
            return;
        }
        this.showEventDetails(id, type);
    };

    attachHoverEvents() {
        if (!this.elements.container) return;
        this.elements.container
            .querySelectorAll(
                ".status-card.booked, .status-card.pending, .status-card.event",
            )
            .forEach((card) => {
                card.removeEventListener(
                    "mouseenter",
                    this.handleCardMouseEnter,
                );
                card.removeEventListener(
                    "mouseleave",
                    this.handleCardMouseLeave,
                );
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

        const colors = {
            booked: "#ef4444",
            pending: "#f5bc40",
            event: "#6f42c1",
        };
        const accent = colors[status] || "#6c757d";

        // Title varies by record type.
        const title =
            type === "requisition"
                ? data.title || "Untitled"
                : data.event_name || "Untitled";

        // Status / type label
const statusLabel =
    type === "requisition"
        ? data.status || ""
        : this.eventTypeLabel(data.event_type);

        // Optional subtext: event_details for requisitions, description for calendar events.
        const subtext =
            type === "calendar_event"
                ? data.description || ""
                : data.event_details || "";

        // Schedule: collapse to single date when start_date == end_date
        const startDate = this.formatDateShort(data.start_date);
        const endDate = this.formatDateShort(data.end_date);
        const sameDay = data.start_date === data.end_date;
        let scheduleLine;
        if (data.all_day) {
            scheduleLine = sameDay
                ? `${startDate} — All Day`
                : `${startDate} — ${endDate} (All Day)`;
        } else {
            const sTime = this.formatTime(this.normalizeTime(data.start_time));
            const eTime = this.formatTime(this.normalizeTime(data.end_time));
            scheduleLine = sameDay
                ? `${startDate} · ${sTime} – ${eTime}`
                : `${startDate} ${sTime} – ${endDate} ${eTime}`;
        }

        const el = document.createElement("div");
        el.className = "custom-hover-card";
        el.style.cssText = `
        position:fixed;z-index:9999999;background:#fff;border-radius:12px;
        box-shadow:0 12px 40px rgba(4,26,75,.16);padding:.85rem 1rem;
        min-width:240px;max-width:320px;pointer-events:none;
        border-left:3px solid ${accent};font-family:'DM Sans',sans-serif;
        transition:opacity .15s ease;opacity:0;
    `;

        el.innerHTML = `
        <div style="font-weight:700;color:#041a4b;font-size:.9rem;margin-bottom:.5rem;">
            ${this.escapeHtml(title)}
        </div>
        <div style="margin-bottom:.55rem;">
            <span style="background:${accent}22;color:${accent};padding:.15rem .5rem;border-radius:20px;font-size:.65rem;font-weight:600;display:inline-block;">
                ${this.escapeHtml(statusLabel)}
            </span>
        </div>
        ${subtext ? `<div style="font-size:.75rem;color:#6b7a99;line-height:1.35;margin-bottom:.55rem;">${this.escapeHtml(subtext.substring(0, 120))}${subtext.length > 120 ? "…" : ""}</div>` : ""}
        <div style="border-top:1px solid #e2e6f0;padding-top:.5rem;font-size:.75rem;color:#6b7a99;display:flex;align-items:center;gap:.5rem;">
            <i class="bi bi-calendar3" style="width:16px;"></i>
            <span>${this.escapeHtml(scheduleLine)}</span>
        </div>
    `;

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
        // Build a human-readable schedule that includes dates, mirroring the
        // hover card's single-day / multi-day / all-day handling.
        const startDateShort = this.formatDateShort(data.start_date);
        const endDateShort = this.formatDateShort(data.end_date);
        const sameDay = data.start_date === data.end_date;
        let scheduleText;
        if (data.all_day) {
            scheduleText = sameDay
                ? `${startDateShort} — All Day`
                : `${startDateShort} — ${endDateShort} (All Day)`;
        } else {
            const sTime = this.formatTime(this.normalizeTime(data.start_time));
            const eTime = this.formatTime(this.normalizeTime(data.end_time));
            scheduleText = sameDay
                ? `${startDateShort} · ${sTime} – ${eTime}`
                : `${startDateShort} ${sTime} – ${endDateShort} ${eTime}`;
        }

        const body =
            type === "calendar_event"
                ? `
                    <div class="event-detail-row"><div class="event-detail-label">Description</div>
                        <div class="event-detail-value">${this.escapeHtml(data.description || "—")}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Schedule</div>
                        <div class="event-detail-value">${this.escapeHtml(scheduleText)}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Type</div>
                        <div class="event-detail-value">${this.escapeHtml(this.eventTypeLabel(data.event_type))}</div></div>`
                : `
                    ${
                        data.event_details
                            ? `<div class="event-detail-row"><div class="event-detail-label">Event Details</div>
                        <div class="event-detail-value">${this.escapeHtml(data.event_details)}</div></div>`
                            : ""
                    }
                    <div class="event-detail-row"><div class="event-detail-label">Status</div>
                        <div class="event-detail-value">${this.escapeHtml(data.status || "")}</div></div>
                    <div class="event-detail-row"><div class="event-detail-label">Schedule</div>
                        <div class="event-detail-value">${this.escapeHtml(scheduleText)}</div></div>`;

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
            </div>`,
        );

        const modalEl = document.getElementById("eventModal");
        new bootstrap.Modal(modalEl).show();
        modalEl.addEventListener("hidden.bs.modal", () => modalEl.remove());
    }

    attachEvents() {
        document
            .getElementById("prevDayBtn")
            ?.addEventListener("click", async () => {
                this.currentDate.setDate(this.currentDate.getDate() - 1);
                await this.refresh();
            });
        document
            .getElementById("nextDayBtn")
            ?.addEventListener("click", async () => {
                this.currentDate.setDate(this.currentDate.getDate() + 1);
                await this.refresh();
            });
        document
            .getElementById("todayBtn")
            ?.addEventListener("click", async () => {
                this.currentDate = new Date();
                await this.refresh();
            });

        this.setupDatePicker();

        document.querySelectorAll(".view-type-btn").forEach((btn) => {
            btn.addEventListener("click", async (e) => {
                const view = e.currentTarget.dataset.view;
                if (view === this.currentView) return;
                document
                    .querySelectorAll(".view-type-btn")
                    .forEach((b) => b.classList.remove("active"));
                e.currentTarget.classList.add("active");
                this.currentView = view;

                // Reset the filter so we don't fetch a room ID as a venue, or vice-versa.
                this.selectedFacility = "all";
                if (this.elements.searchInput)
                    this.elements.searchInput.value = "";

                this.renderFacilityFilter();
                await this.loadFacilityHierarchy();
                await this.loadEventsForCurrentDate();
                this.renderMatrix();
            });
        });

        document.querySelectorAll(".time-range-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                document
                    .querySelectorAll(".time-range-btn")
                    .forEach((b) => b.classList.remove("active"));
                btn.classList.add("active");
                this.timeRange = btn.dataset.range;
                this.renderMatrix();
            });
        });

        this.elements.searchInput?.addEventListener("input", (e) => {
            if (this.searchDebounce) clearTimeout(this.searchDebounce);
            const q = e.target.value.trim();
            this.searchDebounce = setTimeout(async () => {
                if (q === "") {
                    this.exitSearchMode();
                } else {
                    await this.runSearch(q);
                }
            }, 300);
        });

        this.elements.searchInput?.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                this.elements.searchInput.value = "";
                this.exitSearchMode();
            }
        });

        this.elements.clearBtn?.addEventListener("click", async () => {
            this.exitSearchMode();
            this.searchQuery = "";
            this.selectedFacility = "all";
            if (this.elements.searchInput) this.elements.searchInput.value = "";
            if (this.elements.facilitySelect)
                this.elements.facilitySelect.value = "all";
            await this.refresh();
        });

        this.elements.facilitySelect?.addEventListener("change", async (e) => {
            // Keep the raw value. loadFacilityHierarchy will parse it.
            this.selectedFacility = e.target.value;

            // Reset page to 1 when changing facility filter
            if (this.currentView === "rooms") {
                this.roomsPage = 1;
            } else {
                this.venuesPage = 1;
            }

            // Fetch new paginated data before refreshing the matrix
            await this.loadFacilityHierarchy();
            await this.refresh();
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
            this.elements.container.querySelector(".loading-overlay")?.remove();
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

/**
 * Format any date string (YYYY-MM-DD, ISO datetime, etc.) → "Oct 4, 2026".
 * Robust to Carbon's date:Y-m-d cast occasionally serializing with a
 * time suffix (e.g. "2026-02-02T00:00:00.000000Z"), which would otherwise
 * parse to NaN and render "Invalid Date".
 */
formatDateShort(dateStr) {
    if (!dateStr) return "";
    // Grab only the YYYY-MM-DD prefix — safe for both "2026-02-02" and ISO.
    const match = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return "";
    const [, y, m, d] = match.map((v, i) => (i === 0 ? v : Number(v)));
    const dt = new Date(y, m - 1, d);
    if (isNaN(dt.getTime())) return "";
    return dt.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
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
    
    eventTypeLabel(type) {
        if (!type) return "Event";
        const map = {
            school_event: "Event",
            holiday: "Holiday",
            hall_booking: "Booking",
        };
        return map[type] || "Event";
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
