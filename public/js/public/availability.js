// ============================================
// OPTIMIZED AVAILABILITY MATRIX CONTROLLER
// With virtual rendering, memoization, and reduced DOM operations
// ============================================

class AvailabilityMatrix {
    constructor() {
        this.currentDate = new Date();
        this.timeRange = "8-12";
        this.facilityHierarchy = [];
        this.eventsCache = new Map();
        this.selectedFacility = "all";
        this.searchQuery = "";
        this.currentView = "venues";
        
        // Performance optimizations
        this.renderedFacilities = [];
        this.statusCache = new Map(); // Cache status results
        this.debounceTimer = null;
        this.abortController = null;
        
        // DOM elements cache
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
            currentDateDisplay: document.getElementById("currentDateDisplay")
        };
    }

    async loadFacilityHierarchy() {
        try {
            const response = await fetch("/api/availability/facilities/hierarchy");
            const result = await response.json();
            if (result.success) {
                this.facilityHierarchy = result.data.hierarchy;
                // Pre-process facilities for faster access
                this.preProcessFacilities();
            }
        } catch (error) {
            console.error("Error loading facility hierarchy:", error);
        }
    }
    
    preProcessFacilities() {
        // Pre-split facilities by type for faster access
        this.venuesList = this.facilityHierarchy.filter(
            p => !p.children || p.children.length === 0
        );
        
        this.parentsWithChildren = this.facilityHierarchy.filter(
            p => p.children && p.children.length > 0
        );
        
        // Create a map for quick facility lookup
        this.facilityMap = new Map();
        for (const venue of this.venuesList) {
            this.facilityMap.set(venue.facility_id, venue);
        }
        for (const parent of this.parentsWithChildren) {
            this.facilityMap.set(parent.facility_id, parent);
            for (const child of parent.children) {
                this.facilityMap.set(child.facility_id, child);
                child.parentId = parent.facility_id;
            }
        }
    }

    async loadEventsForCurrentDate() {
        const dateKey = this.formatDate(this.currentDate);

        if (this.eventsCache.has(dateKey)) {
            const cached = this.eventsCache.get(dateKey);
            this.requisitions = cached.requisitions;
            this.calendarEvents = cached.calendarEvents;
            this.clearStatusCache();
            return;
        }

        this.showLoading(true);

        // Cancel previous request if exists
        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        try {
            const params = new URLSearchParams({
                start_date: dateKey,
                end_date: dateKey,
                facility_id: this.selectedFacility !== "all" ? this.selectedFacility : "",
            });

            const response = await fetch(`/api/availability/events?${params}`, {
                signal: this.abortController.signal
            });
            const result = await response.json();

            if (result.success) {
                this.requisitions = result.data.requisitions;
                this.calendarEvents = result.data.calendar_events;
                
                // Index requisitions by facility for faster lookup
                this.indexRequisitionsByFacility();
                
                this.eventsCache.set(dateKey, {
                    requisitions: this.requisitions,
                    calendarEvents: this.calendarEvents
                });
                
                this.clearStatusCache();
                this.cleanCache();
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error("Error loading events:", error);
                this.showError("Failed to load availability data");
            }
        } finally {
            this.showLoading(false);
        }
    }
    
    indexRequisitionsByFacility() {
        this.requisitionsByFacility = new Map();
        if (!this.requisitions) return;
        
        for (const req of this.requisitions) {
            if (req.facilities) {
                for (const f of req.facilities) {
                    const fid = String(f.facility_id);
                    if (!this.requisitionsByFacility.has(fid)) {
                        this.requisitionsByFacility.set(fid, []);
                    }
                    this.requisitionsByFacility.get(fid).push(req);
                }
            }
        }
    }
    
    clearStatusCache() {
        this.statusCache.clear();
    }

    cleanCache() {
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        for (const [dateKey] of this.eventsCache) {
            if (new Date(dateKey) < sevenDaysAgo) {
                this.eventsCache.delete(dateKey);
            }
        }
    }

    getTimeSlots() {
        const cacheKey = this.timeRange;
        if (this.cachedTimeSlots && this.cachedTimeSlots.key === cacheKey) {
            return this.cachedTimeSlots.slots;
        }
        
        const slots = [];
        const [startHour, endHour] = this.timeRange.split("-").map(Number);
        for (let hour = startHour; hour <= endHour; hour++) {
            slots.push(`${hour.toString().padStart(2, "0")}:00`);
            if (hour !== endHour) {
                slots.push(`${hour.toString().padStart(2, "0")}:30`);
            }
        }
        
        this.cachedTimeSlots = { key: cacheKey, slots };
        return slots;
    }

    getStatusForTimeSlot(facilityId, timeSlot) {
        const cacheKey = `${facilityId}|${timeSlot}`;
        if (this.statusCache.has(cacheKey)) {
            return this.statusCache.get(cacheKey);
        }
        
        let result;
        
        // Check calendar events
        const eventAtSlot = this.calendarEvents?.find(event => {
            if (event.all_day) return true;
            const eventStart = event.start_time?.substring(0, 5);
            const eventEnd = event.end_time?.substring(0, 5);
            return eventStart <= timeSlot && eventEnd > timeSlot;
        });

        if (eventAtSlot) {
            result = {
                status: "event",
                text: '<i class="bi bi-calendar-event-fill"></i> Event',
                color: "#6f42c1",
                event: eventAtSlot,
                tooltip: eventAtSlot.event_name,
                bookable: false,
            };
            this.statusCache.set(cacheKey, result);
            return result;
        }

        // Check requisitions using indexed map
        const requisitionsForFacility = this.requisitionsByFacility?.get(String(facilityId)) || [];
        const requisitionAtSlot = requisitionsForFacility.find(req => {
            if (req.all_day) return true;
            const reqStart = req.start_time?.substring(0, 5);
            const reqEnd = req.end_time?.substring(0, 5);
            return reqStart <= timeSlot && reqEnd > timeSlot;
        });

        if (requisitionAtSlot) {
            const isApproved = requisitionAtSlot.status === "Scheduled" || requisitionAtSlot.status === "Ongoing";
            const isPending = requisitionAtSlot.status === "Pending Approval";
            
            if (isApproved) {
                result = {
                    status: "booked",
                    text: '<i class="bi bi-x-circle-fill"></i> Booked',
                    color: "#ef4444",
                    event: requisitionAtSlot,
                    tooltip: requisitionAtSlot.title,
                    bookable: false,
                };
            } else if (isPending) {
                result = {
                    status: "pending",
                    text: '<i class="bi bi-clock-fill"></i> Pending',
                    color: "#f5bc40",
                    event: requisitionAtSlot,
                    tooltip: `${requisitionAtSlot.title} (Pending)`,
                    bookable: false,
                };
            } else {
                result = this.getDefaultAvailableResult();
            }
        } else {
            result = this.getDefaultAvailableResult();
        }
        
        this.statusCache.set(cacheKey, result);
        return result;
    }
    
    getDefaultAvailableResult() {
        return {
            status: "available",
            text: '<i class="bi bi-check-circle-fill"></i> Available',
            color: "#22c55e",
            event: null,
            bookable: true,
        };
    }
    getFacilitiesToShow() {
        if (this.currentView === "venues") {
            let venues = [...this.venuesList];
            if (this.selectedFacility !== "all") {
                venues = venues.filter(f => f.facility_id == this.selectedFacility);
            }
            return venues;
        } else {
            if (this.selectedFacility !== "all") {
                const parent = this.facilityMap.get(parseInt(this.selectedFacility));
                if (parent && parent.children) {
                    return [...parent.children];
                }
                return [];
            } else {
                const allChildren = [];
                for (const parent of this.parentsWithChildren) {
                    allChildren.push(...parent.children);
                }
                return allChildren;
            }
        }
    }

    renderMatrix() {
        this.removeHoverCard();
        if (!this.elements.container) return;
        
        const timeSlots = this.getTimeSlots();
        let facilitiesToShow = this.getFacilitiesToShow();

        // Apply search filter
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            facilitiesToShow = facilitiesToShow.filter(f =>
                f.facility_name.toLowerCase().includes(query)
            );
        }

        if (facilitiesToShow.length === 0) {
            this.elements.container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-building"></i>
                    <p>No ${this.currentView} available</p>
                </div>
            `;
            return;
        }

        // Build HTML efficiently using array join
        const headerCells = timeSlots.map(slot => `<th>${this.formatTime(slot)}</th>`).join("");
        
        const rows = [];
        for (const facility of facilitiesToShow) {
            rows.push(this.renderFacilityRowFast(facility, timeSlots));
        }
        
        const html = `
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>${this.currentView === "venues" ? "Venue" : "Room"} / Time</th>
                        ${headerCells}
                    </tr>
                </thead>
                <tbody>
                    ${rows.join("")}
                </tbody>
            </table>
        `;
        
        // Use requestAnimationFrame for smoother rendering
        requestAnimationFrame(() => {
            if (this.elements.container) {
                this.elements.container.innerHTML = html;
                this.updateDateDisplay();
                this.attachRowClickHandlers();
                this.attachHoverEvents();
            }
        });
    }

    renderFacilityRowFast(facility, timeSlots) {
        const cells = [];
        for (const slot of timeSlots) {
            const statusData = this.getStatusForTimeSlot(facility.facility_id, slot);
            const eventId = statusData.event?.request_id || statusData.event?.event_id || "";
            const eventType = statusData.event?.request_id ? "requisition" : "calendar_event";
            const tooltipAttr = statusData.status !== "available" ? `data-tooltip="${statusData.tooltip}"` : "";
            
            cells.push(`
                <td>
                    <div class="status-card ${statusData.status}" 
                         data-status="${statusData.status}"
                         data-facility="${facility.facility_id}"
                         data-time="${slot}"
                         data-event-id="${eventId}"
                         data-event-type="${eventType}"
                         data-bookable="${statusData.bookable}"
                         ${tooltipAttr}>
                        ${statusData.text}
                    </div>
                </td>
            `);
        }
        
        return `
            <tr data-facility-id="${facility.facility_id}">
                <td class="facility-cell" data-tooltip="${facility.facility_name}">
                    <strong>${this.truncate(facility.facility_name, 30)}</strong>
                    ${facility.capacity ? `<small>(${facility.capacity} pax)</small>` : ""}
                </td>
                ${cells.join("")}
            </tr>
        `;
    }

    renderFacilityFilter() {
        if (!this.elements.facilitySelect || !this.elements.filterLabel) return;

        if (this.currentView === "venues") {
            this.elements.filterLabel.textContent = "Venues";
            let options = '<option value="all">All Venues</option>';
            for (const facility of this.venuesList) {
                options += `<option value="${facility.facility_id}">${this.truncate(facility.facility_name, 45)}${facility.capacity ? ` (${facility.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = options;
        } else {
            this.elements.filterLabel.textContent = "Campus Rooms";
            let options = '<option value="all">All Rooms</option>';
            for (const parent of this.parentsWithChildren) {
                options += `<option value="parent_${parent.facility_id}">📁 ${this.truncate(parent.facility_name, 45)}${parent.capacity ? ` (${parent.capacity} pax)` : ""}</option>`;
            }
            this.elements.facilitySelect.innerHTML = options;
        }

        // Set selected value
        if (this.selectedFacility !== "all") {
            if (this.currentView === "rooms") {
                const parentForChild = this.parentsWithChildren.find(p =>
                    p.children.some(c => c.facility_id == this.selectedFacility)
                );
                if (parentForChild) {
                    this.elements.facilitySelect.value = `parent_${parentForChild.facility_id}`;
                } else {
                    this.elements.facilitySelect.value = this.selectedFacility;
                }
            } else {
                this.elements.facilitySelect.value = this.selectedFacility;
            }
        } else {
            this.elements.facilitySelect.value = "all";
        }
    }

    attachViewTypeEvents() {
        const viewBtns = document.querySelectorAll(".view-type-btn");
        viewBtns.forEach(btn => {
            btn.removeEventListener("click", this.handleViewChange);
            btn.addEventListener("click", this.handleViewChange.bind(this));
        });
    }

    handleViewChange = (e) => {
        const newView = e.currentTarget.dataset.view;
        if (newView === this.currentView) return;

        document.querySelectorAll(".view-type-btn").forEach(btn => btn.classList.remove("active"));
        e.currentTarget.classList.add("active");

        this.currentView = newView;
        this.selectedFacility = "all";
        this.searchQuery = "";
        
        if (this.elements.searchInput) this.elements.searchInput.value = "";
        this.clearStatusCache();
        
        this.renderFacilityFilter();
        this.refresh();
    }

    handleFacilityChange = (e) => {
        let selectedValue = e.currentTarget.value;
        
        if (this.currentView === "rooms" && selectedValue !== "all") {
            const match = selectedValue.match(/parent_(\d+)/);
            this.selectedFacility = match ? match[1] : selectedValue;
        } else {
            this.selectedFacility = selectedValue;
        }
        
        this.refresh();
    }

    attachRowClickHandlers() {
        if (!this.elements.container) return;
        
        const cards = this.elements.container.querySelectorAll(
            ".status-card.booked, .status-card.pending, .status-card.event"
        );
        
        cards.forEach(card => {
            card.removeEventListener("click", this.handleNonBookableClick);
            card.addEventListener("click", this.handleNonBookableClick.bind(this));
        });
    }

        /**
     * Wire hover events on non-available status cards. Uses a small delay
     * before showing the popover so fast scanning of a dense matrix doesn't
     * flash cards constantly. Popover anchors to the cell (doesn't follow
     * the mouse) since matrix cells are small and tightly packed.
     */
    attachHoverEvents() {
        if (!this.elements.container) return;

        const cards = this.elements.container.querySelectorAll(
            ".status-card.booked, .status-card.pending, .status-card.event"
        );

        cards.forEach((card) => {
            card.removeEventListener("mouseenter", this.handleCardMouseEnter);
            card.removeEventListener("mouseleave", this.handleCardMouseLeave);

            card.addEventListener("mouseenter", this.handleCardMouseEnter);
            card.addEventListener("mouseleave", this.handleCardMouseLeave);
        });
    }

    handleCardMouseEnter = (e) => {
        const card = e.currentTarget;
        // Small delay so accidental passes don't flash the popover.
        this.hoverTimer = setTimeout(() => {
            this.showHoverCard(card);
        }, 150);
    }

    handleCardMouseLeave = () => {
        if (this.hoverTimer) {
            clearTimeout(this.hoverTimer);
            this.hoverTimer = null;
        }
        this.removeHoverCard();
    }

    /**
     * Render a floating info card anchored above the hovered cell.
     * Content varies by status type (booked / pending / event).
     * Uses inline styles for portability — no dependency on availability-matrix.css.
     */
    showHoverCard(card) {
        this.removeHoverCard();

        const status = card.dataset.status;
        const eventId = card.dataset.eventId;
        const eventType = card.dataset.eventType;
        if (!eventId) return;

        // Resolve the underlying event/requisition object.
        let eventData = null;
        if (eventType === "calendar_event") {
            eventData = this.calendarEvents?.find((e) => e.event_id == eventId);
        } else {
            eventData = this.requisitions?.find((r) => r.request_id == eventId);
        }
        if (!eventData) return;

        // Match the accent color used by the cell.
        const colorByStatus = {
            booked: "#ef4444",
            pending: "#f5bc40",
            event: "#6f42c1",
        };
        const accent = colorByStatus[status] || "#6c757d";

        const hoverCard = document.createElement("div");
        hoverCard.className = "custom-hover-card";
        hoverCard.style.cssText = `
            position: fixed;
            z-index: 9999999;
            background: white;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(4, 26, 75, 0.16);
            padding: 0.85rem 1rem;
            min-width: 240px;
            max-width: 320px;
            pointer-events: none;
            border-left: 3px solid ${accent};
            font-family: 'DM Sans', sans-serif;
            transition: opacity 0.2s ease;
            opacity: 0;
        `;

        // Build card content by status type (mirrors modal-version popover).
        let cardHtml = "";
        if (status === "booked" && eventData) {
            cardHtml = `
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.6rem;">
                    <i class="bi bi-calendar-check-fill" style="color:${accent};font-size:1rem;"></i>
                    <span style="font-weight:700;color:#041a4b;font-size:0.85rem;">Booked Request</span>
                    <span style="background:${accent}15;color:${accent};padding:0.15rem 0.5rem;border-radius:20px;font-size:0.65rem;font-weight:600;">${eventData.status || "Booked"}</span>
                </div>
                <div style="font-weight:600;color:#1e2d4a;font-size:0.9rem;margin-bottom:0.5rem;border-bottom:1px solid #e2e6f0;padding-bottom:0.4rem;">
                    ${this.escapeHtml(eventData.title || "Untitled Booking")}
                </div>
                ${eventData.user ? `<div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;"><i class="bi bi-person" style="width:16px;"></i><span>Requester: ${this.escapeHtml(eventData.user.full_name || eventData.user.name || "N/A")}</span></div>` : ""}
                <div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-calendar3" style="width:16px;"></i>
                    <span>${eventData.all_day ? "All Day" : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
                </div>
                ${eventData.facilities && eventData.facilities.length > 0 ? `<div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;"><i class="bi bi-building" style="width:16px;"></i><span>${eventData.facilities.map((f) => this.escapeHtml(f.facility_name || f.name)).join(", ")}</span></div>` : ""}
                ${eventData.request_id ? `<div style="font-size:0.7rem;color:#9aaac5;margin-top:0.5rem;padding-top:0.4rem;border-top:1px solid #e2e6f0;">Request #${eventData.request_id}</div>` : ""}
            `;
        } else if (status === "pending" && eventData) {
            cardHtml = `
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.6rem;">
                    <i class="bi bi-clock-history" style="color:${accent};font-size:1rem;"></i>
                    <span style="font-weight:700;color:#041a4b;font-size:0.85rem;">Pending Approval</span>
                    <span style="background:${accent}15;color:#b45309;padding:0.15rem 0.5rem;border-radius:20px;font-size:0.65rem;font-weight:600;">Awaiting Review</span>
                </div>
                <div style="font-weight:600;color:#1e2d4a;font-size:0.9rem;margin-bottom:0.5rem;border-bottom:1px solid #e2e6f0;padding-bottom:0.4rem;">
                    ${this.escapeHtml(eventData.title || "Untitled Request")}
                </div>
                ${eventData.user ? `<div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;"><i class="bi bi-person" style="width:16px;"></i><span>Requester: ${this.escapeHtml(eventData.user.full_name || eventData.user.name || "N/A")}</span></div>` : ""}
                <div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-calendar3" style="width:16px;"></i>
                    <span>${eventData.all_day ? "All Day" : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
                </div>
                <div style="font-size:0.7rem;color:#9aaac5;margin-top:0.5rem;padding-top:0.4rem;border-top:1px solid #e2e6f0;">
                    <i class="bi bi-info-circle"></i> Subject to approval. Reservation is not guaranteed until approved.
                </div>
            `;
        } else if (status === "event" && eventData) {
            cardHtml = `
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.6rem;">
                    <i class="bi bi-calendar-event-fill" style="color:${accent};font-size:1rem;"></i>
                    <span style="font-weight:700;color:#041a4b;font-size:0.85rem;">System Event</span>
                    <span style="background:${accent}15;color:${accent};padding:0.15rem 0.5rem;border-radius:20px;font-size:0.65rem;font-weight:600;">${this.escapeHtml(eventData.event_type || "Event")}</span>
                </div>
                <div style="font-weight:600;color:#1e2d4a;font-size:0.9rem;margin-bottom:0.5rem;border-bottom:1px solid #e2e6f0;padding-bottom:0.4rem;">
                    ${this.escapeHtml(eventData.title || eventData.event_name || "Untitled Event")}
                </div>
                ${eventData.description ? `<div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:flex-start;gap:0.5rem;"><i class="bi bi-file-text" style="width:16px;margin-top:2px;"></i><span>${this.escapeHtml(eventData.description.substring(0, 80))}${eventData.description.length > 80 ? "..." : ""}</span></div>` : ""}
                <div style="font-size:0.75rem;color:#6b7a99;margin-bottom:0.4rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="bi bi-calendar3" style="width:16px;"></i>
                    <span>${eventData.all_day ? "All Day" : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
                </div>
            `;
        } else {
            return;
        }

        hoverCard.innerHTML = cardHtml;
        document.body.appendChild(hoverCard);

        // Anchor to the cell: centered above, clamped to viewport.
        const rect = card.getBoundingClientRect();
        const cardRect = hoverCard.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let left = rect.left + rect.width / 2 - cardRect.width / 2;
        let top = rect.top - cardRect.height - 10;

        if (left < 10) left = 10;
        if (left + cardRect.width > vw - 10) left = vw - cardRect.width - 10;
        // If no room above, place below the cell.
        if (top < 10) top = rect.bottom + 10;
        if (top + cardRect.height > vh - 10) top = vh - cardRect.height - 10;

        hoverCard.style.left = left + "px";
        hoverCard.style.top = top + "px";

        requestAnimationFrame(() => {
            hoverCard.style.opacity = "1";
        });

        this.hoverCardEl = hoverCard;
    }

    removeHoverCard() {
        if (this.hoverCardEl) {
            this.hoverCardEl.remove();
            this.hoverCardEl = null;
        }
    }

    /**
     * Minimal HTML escaper for interpolating user-supplied text into
     * the popover markup.
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return "";
        const div = document.createElement("div");
        div.textContent = String(text);
        return div.innerHTML;
    }

    handleNonBookableClick = (e) => {
        e.stopPropagation();
        const card = e.currentTarget;
        const eventId = card.dataset.eventId;
        const eventType = card.dataset.eventType;
        
        if (eventId) {
            this.showEventDetails(eventId, eventType);
        }
    }

    async showEventDetails(eventId, eventType) {
        let eventData = null;
        
        if (eventType === "calendar_event") {
            eventData = this.calendarEvents?.find(e => e.event_id == eventId);
        } else {
            eventData = this.requisitions?.find(r => r.request_id == eventId);
        }
        
        if (!eventData) return;

        const modalHtml = `
            <div class="modal fade event-modal" id="eventModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${eventData.title || eventData.event_name}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${this.renderEventDetails(eventData, eventType)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existingModal = document.getElementById("eventModal");
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML("beforeend", modalHtml);
        const modal = new bootstrap.Modal(document.getElementById("eventModal"));
        modal.show();

        document.getElementById("eventModal")?.addEventListener("hidden.bs.modal", () => {
            document.getElementById("eventModal")?.remove();
        });
    }

    renderEventDetails(eventData, eventType) {
        if (eventType === "calendar_event") {
            return `
                <div class="event-detail-row">
                    <div class="event-detail-label">Description</div>
                    <div class="event-detail-value">${eventData.description || "No description"}</div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Schedule</div>
                    <div class="event-detail-value">${eventData.all_day ? "All Day" : `${eventData.start_time} - ${eventData.end_time}`}</div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Type</div>
                    <div class="event-detail-value">${eventData.event_type}</div>
                </div>
            `;
        } else {
            return `
                <div class="event-detail-row">
                    <div class="event-detail-label">Status</div>
                    <div class="event-detail-value"><span class="badge" style="background-color: ${eventData.status_color || "#6c757d"}">${eventData.status}</span></div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Schedule</div>
                    <div class="event-detail-value">${eventData.schedule_display || eventData.start_time + " - " + eventData.end_time}</div>
                </div>
            `;
        }
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
        this.attachViewTypeEvents();

        document.querySelectorAll(".time-range-btn").forEach(btn => {
            btn.addEventListener("click", async () => {
                document.querySelectorAll(".time-range-btn").forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                this.timeRange = btn.dataset.range;
                this.clearStatusCache();
                this.renderMatrix();
            });
        });

        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener("input", (e) => {
                if (this.debounceTimer) clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.searchQuery = e.target.value;
                    this.renderMatrix();
                }, 300);
            });
        }

        if (this.elements.clearBtn) {
            this.elements.clearBtn.addEventListener("click", async () => {
                this.searchQuery = "";
                this.selectedFacility = "all";
                if (this.elements.searchInput) this.elements.searchInput.value = "";
                if (this.elements.facilitySelect) this.elements.facilitySelect.value = "all";
                await this.loadEventsForCurrentDate();
                this.renderMatrix();
            });
        }
        
        if (this.elements.facilitySelect) {
            this.elements.facilitySelect.removeEventListener("change", this.handleFacilityChange);
            this.elements.facilitySelect.addEventListener("change", this.handleFacilityChange);
        }
    }

    setupDatePicker() {
        if (!this.elements.datePicker) return;
        this.elements.datePicker.value = this.formatDate(this.currentDate);
        this.elements.datePicker.addEventListener("change", async (e) => {
            // Parse picker's YYYY-MM-DD as local, not UTC.
            const [y, m, d] = e.target.value.split("-").map(Number);
            const selectedDate = new Date(y, m - 1, d);
            if (!isNaN(selectedDate.getTime())) {
                this.currentDate = selectedDate;
                await this.refresh();
            }
        });
    }

    async refresh() {
        this.showLoading(true);
        await this.loadEventsForCurrentDate();
        this.renderMatrix();
        this.showLoading(false);
        if (this.elements.datePicker) {
            this.elements.datePicker.value = this.formatDate(this.currentDate);
        }
    }

    showLoading(show) {
        if (!this.elements.container) return;
        if (show && (!this.elements.container.innerHTML || this.elements.container.innerHTML.includes("empty-state"))) {
            this.elements.container.innerHTML = `
                <div class="loading-overlay">
                    <div class="loading-spinner"></div>
                    <p style="margin-top: 10px;">Loading availability data...</p>
                </div>
            `;
        } else if (!show) {
            const overlay = this.elements.container.querySelector(".loading-overlay");
            if (overlay) overlay.remove();
        }
    }

    showError(message) {
        if (!this.elements.container) return;
        this.elements.container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <p>${message}</p>
                <button class="btn btn-primary btn-sm mt-3" onclick="location.reload()">Retry</button>
            </div>
        `;
    }

    updateDateDisplay() {
        if (this.elements.currentDateDisplay) {
            this.elements.currentDateDisplay.textContent = this.currentDate.toLocaleDateString("en-US", {
                weekday: "long", year: "numeric", month: "long", day: "numeric"
            });
        }
    }

    formatDate(date) {
        // Local-timezone-safe YYYY-MM-DD. toISOString() would shift to UTC
        // and cause a one-day offset for admins in UTC+8, desyncing the
        // date picker from the displayed date.
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    formatTime(timeStr) {
        const [hour, minute] = timeStr.split(":");
        const hourNum = parseInt(hour);
        const hour12 = hourNum % 12 || 12;
        const ampm = hourNum >= 12 ? "pm" : "am";
        return `${hour12}:${minute}${ampm}`;
    }

        /**
     * Format "HH:MM" (24h) → "H:MM AM/PM".
     * Kept for parity with the popover code ported from the modal version,
     * which calls formatTimeDisplay() by name.
     */
    formatTimeDisplay(timeStr) {
        if (!timeStr) return "";
        const [hour, minute] = timeStr.split(":");
        const hour12 = parseInt(hour, 10) % 12 || 12;
        const ampm = parseInt(hour, 10) >= 12 ? "PM" : "AM";
        return minute === "00"
            ? `${hour12}:00 ${ampm}`
            : `${hour12}:${minute} ${ampm}`;
    }

    truncate(str, maxLen) {
        if (!str) return "";
        return str.length > maxLen ? str.substring(0, maxLen - 3) + "..." : str;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    window.availabilityMatrix = new AvailabilityMatrix();
});