// availability-calendar-v1.js - REFACTORED VERSION WITH REFINED INSTITUTIONAL THEME
// Clean, compact calendar with static display

class FullCalendarStyle {
    constructor() {
        this.currentDate = new Date();
        this.currentView = "timeGridDay";
        this.eventsCache = new Map();
        this.currentFacility = null;
        this.isLoading = false;
        this.modalInstance = null;
        this.calendarEvents = [];
        this.requisitions = [];

        // Refined Institutional Theme color scheme
        this.theme = {
            navy: "#041a4b",
            navyMid: "#0b2d72",
            navyLight: "#e8edf8",
            amber: "#f5bc40",
            amberDark: "#d9a12a",
            white: "#ffffff",
            surface: "#f5f6fa",
            border: "#e2e6f0",
            textBase: "#1e2d4a",
            textMuted: "#6b7a99",
        };

        // View button colors - Refined Institutional
        this.viewButtonColors = {
            day: {
                bg: this.theme.navyLight,
                hover: this.theme.navy,
                active: this.theme.navy,
                text: this.theme.navy,
                textActive: this.theme.white,
            },
            week: {
                bg: this.theme.navyLight,
                hover: this.theme.navy,
                active: this.theme.navy,
                text: this.theme.navy,
                textActive: this.theme.white,
            },
            month: {
                bg: this.theme.navyLight,
                hover: this.theme.navy,
                active: this.theme.navy,
                text: this.theme.navy,
                textActive: this.theme.white,
            },
        };

        // Status colors - Refined Institutional
        this.statusColors = {
            available: "#22c55e",
            booked: "#ef4444",
            pending: "#f5bc40",
            event: "#6f42c1",
        };
    }

    formatDate(date) {
        return date.toISOString().split("T")[0];
    }

    formatDisplayDate(date, format = "full") {
        if (format === "month") {
            return date.toLocaleDateString("en-US", {
                month: "long",
                year: "numeric",
            });
        } else if (format === "week") {
            const start = this.getWeekStart(date);
            const end = this.getWeekEnd(date);
            return `${start.toLocaleDateString("en-US", { month: "short", day: "numeric" })} - ${end.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })}`;
        }
        return date.toLocaleDateString("en-US", {
            weekday: "long",
            month: "long",
            day: "numeric",
            year: "numeric",
        });
    }

    getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        const diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    getWeekEnd(date) {
        const start = this.getWeekStart(date);
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        return end;
    }

    generateTimeSlots() {
        const slots = [];
        for (let hour = 0; hour < 24; hour++) {
            slots.push(`${hour.toString().padStart(2, "0")}:00`);
            slots.push(`${hour.toString().padStart(2, "0")}:30`);
        }
        return slots;
    }

    escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    async loadEventsForDateRange(startDate, endDate, facilityId) {
        const cacheKey = `${this.formatDate(startDate)}_${this.formatDate(endDate)}_${facilityId}`;

        if (this.eventsCache.has(cacheKey)) {
            const cached = this.eventsCache.get(cacheKey);
            this.calendarEvents = cached.calendarEvents;
            this.requisitions = cached.requisitions;
            return cached;
        }

        try {
            const params = new URLSearchParams({
                start_date: this.formatDate(startDate),
                end_date: this.formatDate(endDate),
                facility_id: facilityId,
            });

            const response = await fetch(`/api/availability/events?${params}`);
            const result = await response.json();

            if (result.success) {
                const data = {
                    requisitions: result.data.requisitions || [],
                    calendarEvents: result.data.calendar_events || [],
                };
                this.calendarEvents = data.calendarEvents;
                this.requisitions = data.requisitions;
                this.eventsCache.set(cacheKey, data);
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

    getStatusForTimeSlot(date, timeSlot, facilityId) {
        const dateStr = this.formatDate(date);

        const eventAtSlot = this.calendarEvents.find((event) => {
            const eventDate = this.formatDate(
                new Date(event.start_date || event.date),
            );
            if (eventDate !== dateStr) return false;
            if (event.all_day) return true;
            const eventStart = event.start_time?.substring(0, 5);
            const eventEnd = event.end_time?.substring(0, 5);
            return eventStart <= timeSlot && eventEnd > timeSlot;
        });

        if (eventAtSlot) {
            return {
                status: "event",
                title: eventAtSlot.event_name,
                color: this.statusColors.event,
                bookable: false,
                event: eventAtSlot,
            };
        }

        const reqAtSlot = this.requisitions.find((req) => {
            const reqDate = this.formatDate(
                new Date(req.start_date || req.date),
            );
            if (reqDate !== dateStr) return false;
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
                    title: reqAtSlot.title,
                    color: this.statusColors.booked,
                    bookable: false,
                    event: reqAtSlot,
                };
            }
            if (isPending) {
                return {
                    status: "pending",
                    title: `${reqAtSlot.title} (Pending)`,
                    color: this.statusColors.pending,
                    bookable: false,
                    event: reqAtSlot,
                };
            }
        }

        return {
            status: "available",
            title: "Available",
            color: this.statusColors.available,
            bookable: false,
        };
    }

    async showModal(facilityData) {
        console.log("Showing modal for:", facilityData);

        if (this.modalInstance) {
            this.modalInstance.hide();
            this.modalInstance = null;
        }

        this.currentFacility = {
            ...facilityData,
            type: facilityData.type || "facility",
            totalQuantity:
                facilityData.totalQuantity ||
                facilityData.availableQuantity ||
                0,
        };
        this.currentDate = new Date();
        this.miniCalendarDate = new Date(this.currentDate);

        const existingModal = document.getElementById("fullCalendarModal");
        if (existingModal) existingModal.remove();

        const modalHtml = this.createModalHTML(facilityData);
        document.body.insertAdjacentHTML("beforeend", modalHtml);

        const modalElement = document.getElementById("fullCalendarModal");
        this.modalInstance = new bootstrap.Modal(modalElement);

        await this.renderCurrentView();
        await this.renderMiniCalendar();
        this.attachModalEvents();
        this.attachMiniCalendarEvents();
        this.modalInstance.show();
    }

    async renderMiniCalendar() {
        const container = document.getElementById("miniCalendarDays");
        const monthYearEl = document.getElementById("miniCalendarMonthYear");

        if (!container) return;

        const year = this.miniCalendarDate
            ? this.miniCalendarDate.getFullYear()
            : this.currentDate.getFullYear();
        const month = this.miniCalendarDate
            ? this.miniCalendarDate.getMonth()
            : this.currentDate.getMonth();

        if (!this.miniCalendarDate) {
            this.miniCalendarDate = new Date(this.currentDate);
        }

        monthYearEl.textContent = this.miniCalendarDate.toLocaleDateString(
            "en-US",
            { month: "long", year: "numeric" },
        );

        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const startDate = new Date(firstDayOfMonth);
        startDate.setDate(firstDayOfMonth.getDate() - firstDayOfMonth.getDay());
        const endDate = new Date(lastDayOfMonth);
        endDate.setDate(
            lastDayOfMonth.getDate() + (6 - lastDayOfMonth.getDay()),
        );

        await this.loadEventsForDateRange(
            startDate,
            endDate,
            this.currentFacility.id,
        );

        let html =
            '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;">';

        for (
            let d = new Date(startDate);
            d <= endDate;
            d.setDate(d.getDate() + 1)
        ) {
            const day = new Date(d);
            const isCurrentMonth = day.getMonth() === month;
            const isToday =
                this.formatDate(day) === this.formatDate(new Date());
            const isSelected =
                this.formatDate(day) === this.formatDate(this.currentDate);
            const dateStr = this.formatDate(day);

            let hasEvents = false;
            let dayStatus = "available";

            const hasRequisitions = this.requisitions.some((req) => {
                const reqDate = this.formatDate(
                    new Date(req.start_date || req.date),
                );
                if (reqDate !== dateStr) return false;
                return req.facilities?.some(
                    (f) =>
                        String(f.facility_id) ===
                        String(this.currentFacility.id),
                );
            });

            const hasCalendarEvents = this.calendarEvents.some((event) => {
                const eventDate = this.formatDate(
                    new Date(event.start_date || event.date),
                );
                return eventDate === dateStr;
            });

            if (hasRequisitions) {
                hasEvents = true;
                dayStatus = "booked";
            } else if (hasCalendarEvents) {
                hasEvents = true;
                dayStatus = "event";
            }

            let cellStyle = "text-center p-1 rounded ";
            let textStyle = "";

            if (!isCurrentMonth) {
                textStyle = "color: #9aaac5;";
            }

            if (isToday) {
                cellStyle += "bg-primary ";
                textStyle = "color: white; font-weight: bold;";
            } else if (isSelected && !isToday) {
                cellStyle += "bg-secondary ";
                textStyle = "color: white;";
            }

            if (hasEvents && !isToday && !isSelected) {
                cellStyle += "bg-light ";
            }

            const indicatorColor =
                dayStatus === "booked"
                    ? this.statusColors.booked
                    : this.statusColors.event;

            html += `
                <div class="mini-calendar-day" style="cursor: pointer;" data-date="${dateStr}">
                    <div class="${cellStyle}" style="padding: 4px 2px; ${textStyle}">
                        <div style="font-size: 0.7rem; font-weight: ${isToday ? "bold" : "normal"};">${day.getDate()}</div>
                        ${hasEvents && !isToday && !isSelected ? `<div style="width: 4px; height: 4px; background-color: ${indicatorColor}; border-radius: 50%; margin: 2px auto 0;"></div>` : '<div style="height: 6px;"></div>'}
                    </div>
                </div>
            `;
        }

        html += "</div>";
        container.innerHTML = html;

        container.querySelectorAll(".mini-calendar-day").forEach((dayEl) => {
            dayEl.removeEventListener("click", this.miniDayClickHandler);
            this.miniDayClickHandler = () => {
                const dateStr = dayEl.dataset.date;
                this.currentDate = new Date(dateStr);
                this.currentView = "timeGridDay";
                this.renderCurrentView();
                this.renderMiniCalendar();

                const dateRangeEl = document.getElementById("fcDateRange");
                if (dateRangeEl) {
                    dateRangeEl.textContent = this.formatDisplayDate(
                        this.currentDate,
                        "full",
                    );
                }
            };
            dayEl.addEventListener("click", this.miniDayClickHandler);
        });
    }

    attachMiniCalendarEvents() {
        const prevBtn = document.querySelector(".mini-prev-month");
        const nextBtn = document.querySelector(".mini-next-month");

        // Add hover effects for mini calendar buttons
        if (prevBtn) {
            prevBtn.addEventListener("mouseenter", () => {
                prevBtn.style.background = "#e8edf8";
                prevBtn.style.color = "#041a4b";
            });
            prevBtn.addEventListener("mouseleave", () => {
                prevBtn.style.background = "transparent";
                prevBtn.style.color = "#6b7a99";
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("mouseenter", () => {
                nextBtn.style.background = "#e8edf8";
                nextBtn.style.color = "#041a4b";
            });
            nextBtn.addEventListener("mouseleave", () => {
                nextBtn.style.background = "transparent";
                nextBtn.style.color = "#6b7a99";
            });
        }

        if (prevBtn) {
            prevBtn.removeEventListener("click", this.miniPrevHandler);
            this.miniPrevHandler = async () => {
                if (!this.miniCalendarDate) {
                    this.miniCalendarDate = new Date(this.currentDate);
                }
                this.miniCalendarDate.setMonth(
                    this.miniCalendarDate.getMonth() - 1,
                );
                await this.renderMiniCalendar();
            };
            prevBtn.addEventListener("click", this.miniPrevHandler);
        }

        if (nextBtn) {
            nextBtn.removeEventListener("click", this.miniNextHandler);
            this.miniNextHandler = async () => {
                if (!this.miniCalendarDate) {
                    this.miniCalendarDate = new Date(this.currentDate);
                }
                this.miniCalendarDate.setMonth(
                    this.miniCalendarDate.getMonth() + 1,
                );
                await this.renderMiniCalendar();
            };
            nextBtn.addEventListener("click", this.miniNextHandler);
        }
    }

showHoverCard(event, element, statusData) {
    // Remove any existing hover card
    this.removeHoverCard();
    
    // Create hover card element
    const hoverCard = document.createElement('div');
    hoverCard.className = 'custom-hover-card';
    hoverCard.style.cssText = `
        position: fixed;
        z-index: 9999999 !important;
        background: white;
        border-radius: 12px;
        box-shadow: 0 12px 40px rgba(4, 26, 75, 0.16);
        padding: 0.85rem 1rem;
        min-width: 240px;
        max-width: 320px;
        pointer-events: none;
        border-left: 3px solid ${statusData.color};
        font-family: 'DM Sans', sans-serif;
        transition: opacity 0.2s ease;
        opacity: 0;
    `;
    
    // Build content based on status type
    let cardHtml = '';
    const eventData = statusData.event;
    
    if (statusData.status === 'booked' && eventData) {
        cardHtml = `
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.6rem;">
                <i class="bi bi-calendar-check-fill" style="color: ${statusData.color}; font-size: 1rem;"></i>
                <span style="font-weight: 700; color: #041a4b; font-size: 0.85rem;">Booked Request</span>
                <span style="background: ${statusData.color}15; color: ${statusData.color}; padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; font-weight: 600;">${eventData.status || 'Booked'}</span>
            </div>
            <div style="font-weight: 600; color: #1e2d4a; font-size: 0.9rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e6f0; padding-bottom: 0.4rem;">
                ${this.escapeHtml(eventData.title || 'Untitled Booking')}
            </div>
            ${eventData.user ? `
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-person" style="width: 16px;"></i>
                <span>Requester: ${this.escapeHtml(eventData.user.full_name || eventData.user.name || 'N/A')}</span>
            </div>
            ` : ''}
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-calendar3" style="width: 16px;"></i>
                <span>${eventData.all_day ? 'All Day' : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
            </div>
            ${eventData.facilities && eventData.facilities.length > 0 ? `
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-building" style="width: 16px;"></i>
                <span>${eventData.facilities.map(f => this.escapeHtml(f.facility_name || f.name)).join(', ')}</span>
            </div>
            ` : ''}
            ${eventData.request_id ? `
            <div style="font-size: 0.7rem; color: #9aaac5; margin-top: 0.5rem; padding-top: 0.4rem; border-top: 1px solid #e2e6f0;">
                Request #${eventData.request_id}
            </div>
            ` : ''}
        `;
    } 
    else if (statusData.status === 'pending' && eventData) {
        cardHtml = `
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.6rem;">
                <i class="bi bi-clock-history" style="color: ${statusData.color}; font-size: 1rem;"></i>
                <span style="font-weight: 700; color: #041a4b; font-size: 0.85rem;">Pending Approval</span>
                <span style="background: ${statusData.color}15; color: #b45309; padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; font-weight: 600;">Awaiting Review</span>
            </div>
            <div style="font-weight: 600; color: #1e2d4a; font-size: 0.9rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e6f0; padding-bottom: 0.4rem;">
                ${this.escapeHtml(eventData.title || 'Untitled Request')}
            </div>
            ${eventData.user ? `
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-person" style="width: 16px;"></i>
                <span>Requester: ${this.escapeHtml(eventData.user.full_name || eventData.user.name || 'N/A')}</span>
            </div>
            ` : ''}
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-calendar3" style="width: 16px;"></i>
                <span>${eventData.all_day ? 'All Day' : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
            </div>
            <div style="font-size: 0.7rem; color: #9aaac5; margin-top: 0.5rem; padding-top: 0.4rem; border-top: 1px solid #e2e6f0;">
                <i class="bi bi-info-circle"></i> Subject to approval
            </div>
        `;
    }
    else if (statusData.status === 'event' && eventData) {
        cardHtml = `
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.6rem;">
                <i class="bi bi-calendar-event-fill" style="color: ${statusData.color}; font-size: 1rem;"></i>
                <span style="font-weight: 700; color: #041a4b; font-size: 0.85rem;">System Event</span>
                <span style="background: ${statusData.color}15; color: ${statusData.color}; padding: 0.15rem 0.5rem; border-radius: 20px; font-size: 0.65rem; font-weight: 600;">${this.escapeHtml(eventData.event_type || 'Event')}</span>
            </div>
            <div style="font-weight: 600; color: #1e2d4a; font-size: 0.9rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e6f0; padding-bottom: 0.4rem;">
                ${this.escapeHtml(eventData.title || eventData.event_name || 'Untitled Event')}
            </div>
            ${eventData.description ? `
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: flex-start; gap: 0.5rem;">
                <i class="bi bi-file-text" style="width: 16px; margin-top: 2px;"></i>
                <span>${this.escapeHtml(eventData.description.substring(0, 80))}${eventData.description.length > 80 ? '...' : ''}</span>
            </div>
            ` : ''}
            <div style="font-size: 0.75rem; color: #6b7a99; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-calendar3" style="width: 16px;"></i>
                <span>${eventData.all_day ? 'All Day' : `${this.formatTimeDisplay(eventData.start_time)} - ${this.formatTimeDisplay(eventData.end_time)}`}</span>
            </div>
        `;
    }
    
    hoverCard.innerHTML = cardHtml;
    document.body.appendChild(hoverCard);
    
    // Position the hover card
    const updatePosition = (clientX, clientY) => {
        const cardRect = hoverCard.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        let left = clientX + 15;
        let top = clientY + 15;
        
        if (left + cardRect.width > viewportWidth - 10) {
            left = clientX - cardRect.width - 15;
        }
        if (top + cardRect.height > viewportHeight - 10) {
            top = clientY - cardRect.height - 15;
        }
        if (left < 10) left = 10;
        if (top < 10) top = 10;
        
        hoverCard.style.left = left + 'px';
        hoverCard.style.top = top + 'px';
    };
    
    updatePosition(event.clientX, event.clientY);
    
    this.hoverCardPositionUpdater = (moveEvent) => {
        updatePosition(moveEvent.clientX, moveEvent.clientY);
    };
    
    requestAnimationFrame(() => {
        hoverCard.style.opacity = '1';
    });
    
    document.addEventListener('mousemove', this.hoverCardPositionUpdater);
    
    return hoverCard;
}

removeHoverCard() {
    const existingCard = document.querySelector('.custom-hover-card');
    if (existingCard) {
        existingCard.remove();
    }
    if (this.hoverCardPositionUpdater) {
        document.removeEventListener('mousemove', this.hoverCardPositionUpdater);
        this.hoverCardPositionUpdater = null;
    }
}


    createModalHTML(facilityData) {
        return `
        <div class="modal fade" id="fullCalendarModal" tabindex="-1" data-bs-backdrop="static" style="z-index: 999999;">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95%;">
                <div class="modal-content" style="border-radius: 12px; overflow: hidden; max-height: 85vh; border: none; outline: none; box-shadow: var(--shadow-lg, 0 12px 40px rgba(4, 26, 75, 0.16));">
                   <div class="modal-header" style="background: linear-gradient(135deg, #041a4b 0%, #0b2d72 100%); color: white; border: none; padding: 1rem 1.5rem; outline: none; box-shadow: none;">
                        <div class="d-flex align-items-center">
                            <div>
                               <h5 class="modal-title" style="font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.1rem; letter-spacing: -0.3px; color: white;">${this.escapeHtml(facilityData.name)}</h5>
                                <small class="opacity-75" style="font-size: 0.7rem; font-family: 'DM Sans', sans-serif;">Availability Schedule</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3" style="max-height: calc(85vh - 110px); overflow-y: auto; background: #f5f6fa;">
                        <div class="row g-3">
                            <!-- Left Column: Facility Info + Mini Calendar -->
                            <div class="col-md-3">
                                <!-- Facility Info Card -->
                                <div class="card mb-3" style="background: white; border: 1px solid #e2e6f0; border-radius: 12px;">
                                    <div class="card-body p-3 text-center">
                                        <img src="${this.escapeHtml(facilityData.image)}" class="img-fluid rounded mb-2" style="max-height: 100px; width: auto;" onerror="this.src='https://res.cloudinary.com/dn98ntlkd/image/upload/v1759850278/t4fyv56wog6pglhwvwtn.png'">
                                        <div class="mb-2">
                                            <span class="badge me-1" style="background-color: ${facilityData.statusColor};">${facilityData.status}</span>
                                            <span class="badge" style="background: #6b7a99; color: white;">${this.escapeHtml(facilityData.category)}</span>
                                        </div>
                                        ${facilityData.capacity ? `<div class="small mb-1" style="color: #6b7a99;"><i class="bi bi-people-fill me-1"></i> Capacity: ${facilityData.capacity}</div>` : ""}
                                        ${facilityData.fee ? `<div class="small mb-1" style="color: #6b7a99;"><i class="bi bi-cash-stack me-1"></i> ₱${facilityData.fee}</div>` : ""}
                                        ${facilityData.totalQuantity ? `<div class="small" style="color: #6b7a99;"><i class="bi bi-box-seam me-1"></i> Available: ${facilityData.totalQuantity} units</div>` : ""}
                                    </div>
                                </div>

                                <!-- Mini Calendar Card -->
                                <div class="card" style="border: 1px solid #e2e6f0; border-radius: 12px;">
                                    <div class="card-body p-2">
                                        <div class="mini-calendar">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <button class="btn btn-sm mini-prev-month" type="button" style="padding: 0.2rem 0.4rem; border-radius: 6px; border: none; background: transparent; color: #6b7a99; transition: all 0.2s ease;">
    <i class="bi bi-chevron-left"></i>
</button>
                                                <h6 class="mb-0" id="miniCalendarMonthYear" style="font-size: 0.85rem; font-family: 'DM Sans', sans-serif; font-weight: 600; color: #041a4b;"></h6>
                                                <button class="btn btn-sm mini-next-month" type="button" style="padding: 0.2rem 0.4rem; border-radius: 6px; border: none; background: transparent; color: #6b7a99; transition: all 0.2s ease;">
    <i class="bi bi-chevron-right"></i>
</button>
                                            </div>
                                            <div class="mini-calendar-header d-flex mb-1">
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">S</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">M</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">T</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">W</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">T</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">F</div>
                                                <div class="text-center flex-fill small text-muted" style="font-size: 0.7rem; color: #9aaac5;">S</div>
                                            </div>
                                            <div class="mini-calendar-days" id="miniCalendarDays"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Toolbar + Calendar -->
                            <div class="col-md-9">
                                <!-- Toolbar: Legend (left), View Tabs + Today (right) -->
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                    <!-- Legend - Left aligned -->
                                    <div class="d-flex flex-wrap gap-3">
                                        <span class="d-flex align-items-center"><span style="display: inline-block; width: 12px; height: 12px; background: ${this.statusColors.available}; border-radius: 3px; margin-right: 5px;"></span><span style="font-size: 0.7rem; color: #1e2d4a;">Available</span></span>
                                        <span class="d-flex align-items-center"><span style="display: inline-block; width: 12px; height: 12px; background: ${this.statusColors.booked}; border-radius: 3px; margin-right: 5px;"></span><span style="font-size: 0.7rem; color: #1e2d4a;">Booked</span></span>
                                        <span class="d-flex align-items-center"><span style="display: inline-block; width: 12px; height: 12px; background: ${this.statusColors.pending}; border-radius: 3px; margin-right: 5px;"></span><span style="font-size: 0.7rem; color: #1e2d4a;">Pending</span></span>
                                        <span class="d-flex align-items-center"><span style="display: inline-block; width: 12px; height: 12px; background: ${this.statusColors.event}; border-radius: 3px; margin-right: 5px;"></span><span style="font-size: 0.7rem; color: #1e2d4a;">Event</span></span>
                                    </div>

                                    <!-- View Controls - Right aligned -->
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- View Tabs -->
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-view-tab" data-view="timeGridDay" style="background: #e8edf8; color: #041a4b; border: none; padding: 0.35rem 0.9rem; border-radius: 6px 0 0 6px; font-family: 'DM Sans', sans-serif; font-weight: 500;">
                                                <i class="bi bi-calendar-day me-1"></i> Day
                                            </button>
                                            <button type="button" class="btn btn-view-tab" data-view="timeGridWeek" style="background: #e8edf8; color: #041a4b; border: none; padding: 0.35rem 0.9rem; border-radius: 0; font-family: 'DM Sans', sans-serif; font-weight: 500;">
                                                <i class="bi bi-calendar-week me-1"></i> Week
                                            </button>
                                            <button type="button" class="btn btn-view-tab" data-view="dayGridMonth" style="background: #e8edf8; color: #041a4b; border: none; padding: 0.35rem 0.9rem; border-radius: 0 6px 6px 0; font-family: 'DM Sans', sans-serif; font-weight: 500;">
                                                <i class="bi bi-calendar-month me-1"></i> Month
                                            </button>
                                        </div>
                                        
                                        <!-- Navigation -->
                                        <div class="d-flex align-items-center gap-1">
                                            <button class="btn btn-sm" id="fcPrevBtn" style="padding: 0.25rem 0.5rem; background: transparent; border: none; border-radius: 6px; color: #6b7a99; transition: all 0.2s ease;">
    <i class="bi bi-chevron-left"></i>
</button>
                                            <span class="fw-semibold mx-1" id="fcDateRange" style="font-size: 0.75rem; min-width: 160px; text-align: center; color: #1e2d4a; font-family: 'DM Sans', sans-serif;">${this.formatDisplayDate(this.currentDate, "full")}</span>
                                            <button class="btn btn-sm" id="fcNextBtn" style="padding: 0.25rem 0.5rem; background: transparent; border: none; border-radius: 6px; color: #6b7a99; transition: all 0.2s ease;">
    <i class="bi bi-chevron-right"></i>
</button>
                                        </div>
                                        
                                        <button class="btn btn-sm" id="fcTodayBtn" style="padding: 0.25rem 0.75rem; background: #041a4b; color: white; border: none; border-radius: 6px; font-size: 0.7rem; font-family: 'DM Sans', sans-serif; font-weight: 500;">
                                            <i class="bi bi-calendar3 me-1"></i> Today
                                        </button>
                                    </div>
                                </div>

                                <!-- Calendar Container -->
                                <div id="fcCalendarContainer" style="min-height: 400px; max-height: 55vh; overflow-y: auto; background: white; border-radius: 12px; border: 1px solid #e2e6f0;">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status" style="color: #041a4b;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2" style="color: #6b7a99;">Loading calendar...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #f5f6fa; border-top: 1px solid #e2e6f0; padding: 0.75rem 1rem;">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" style="background: white; color: #6b7a99; border: 1px solid #e2e6f0; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-weight: 500;">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    }

    async renderCurrentView() {
        const container = document.getElementById("fcCalendarContainer");
        if (!container) return;

        if (this.currentView === "timeGridDay") {
            await this.renderDayView();
        } else if (this.currentView === "timeGridWeek") {
            await this.renderWeekView();
        } else if (this.currentView === "dayGridMonth") {
            await this.renderMonthView();
        }

        const dateRangeEl = document.getElementById("fcDateRange");
        if (dateRangeEl) {
            if (this.currentView === "timeGridDay") {
                dateRangeEl.textContent = this.formatDisplayDate(
                    this.currentDate,
                    "full",
                );
            } else if (this.currentView === "timeGridWeek") {
                dateRangeEl.textContent = this.formatDisplayDate(
                    this.currentDate,
                    "week",
                );
            } else {
                dateRangeEl.textContent = this.formatDisplayDate(
                    this.currentDate,
                    "month",
                );
            }
        }

        // Update active view button styling
        document.querySelectorAll(".btn-view-tab").forEach((btn) => {
            const view = btn.dataset.view;
            if (view === this.currentView) {
                btn.style.background = this.theme.navy;
                btn.style.color = this.theme.white;
                btn.style.fontWeight = "600";
            } else {
                btn.style.background = this.theme.navyLight;
                btn.style.color = this.theme.navy;
                btn.style.fontWeight = "500";
            }
        });

        this.attachHoverEvents();
    }

async renderDayView() {
    const container = document.getElementById("fcCalendarContainer");
    const date = this.currentDate;

    await this.loadEventsForDateRange(date, date, this.currentFacility.id);

    const timeSlots = this.generateTimeSlots();

    let html = `
        <div class="day-view" style="padding: 1rem;">
            <div class="timeline-grid">
    `;

    for (const slot of timeSlots) {
        const statusData = this.getStatusForTimeSlot(
            date,
            slot,
            this.currentFacility.id,
        );
        const displayTime = this.formatTimeDisplay(slot);

        let statusText = "";
        let statusIcon = "";

        if (statusData.status === "available") {
            statusIcon = '<i class="bi bi-check-circle-fill" style="color: #22c55e;"></i>';
            statusText = "Available";
        } else if (statusData.status === "booked") {
            statusIcon = '<i class="bi bi-x-circle-fill" style="color: #ef4444;"></i>';
            statusText = statusData.title;
        } else if (statusData.status === "pending") {
            statusIcon = '<i class="bi bi-clock-fill" style="color: #f5bc40;"></i>';
            statusText = statusData.title;
        } else {
            statusIcon = '<i class="bi bi-calendar-event-fill" style="color: #6f42c1;"></i>';
            statusText = statusData.title;
        }

        // Store statusData as JSON string on the card
        const statusDataJson = this.escapeHtml(JSON.stringify({
            status: statusData.status,
            title: statusData.title,
            color: statusData.color,
            event: statusData.event ? {
                request_id: statusData.event.request_id,
                event_id: statusData.event.event_id,
                title: statusData.event.title || statusData.event.event_name,
                status: statusData.event.status,
                start_time: statusData.event.start_time,
                end_time: statusData.event.end_time,
                all_day: statusData.event.all_day,
                facilities: statusData.event.facilities,
                description: statusData.event.description,
                event_type: statusData.event.event_type,
                user: statusData.event.user
            } : null
        }));

        html += `
            <div class="timeline-row mb-2" style="border-bottom: 1px solid #e2e6f0; padding: 0.5rem 0;">
                <div class="row align-items-center">
                    <div class="col-md-2 col-3">
                        <span class="fw-semibold" style="font-size: 0.85rem; color: #1e2d4a;">${displayTime}</span>
                    </div>
                    <div class="col-md-10 col-9">
                        <div class="slot-card p-2 rounded" 
                             style="background-color: ${statusData.color}15; border-left: 3px solid ${statusData.color}; border-radius: 8px; cursor: ${statusData.status !== 'available' ? 'pointer' : 'default'}; transition: all 0.2s ease;"
                             data-status="${statusData.status}"
                             data-status-data='${statusDataJson}'
                             data-bookable="${statusData.bookable}">
                            <div class="d-flex align-items-center gap-2">
                                ${statusIcon}
                                <span class="small" style="color: #1e2d4a;">${this.escapeHtml(statusText)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    container.innerHTML = html;
    
    // Attach hover events after rendering
    this.attachHoverEvents();
}

    async renderWeekView() {
        const container = document.getElementById("fcCalendarContainer");
        const startDate = this.getWeekStart(this.currentDate);
        const endDate = this.getWeekEnd(this.currentDate);

        await this.loadEventsForDateRange(
            startDate,
            endDate,
            this.currentFacility.id,
        );

        const days = [];
        for (let i = 0; i < 7; i++) {
            const day = new Date(startDate);
            day.setDate(startDate.getDate() + i);
            days.push(day);
        }

        const timeSlots = this.generateTimeSlots();

        let html = `
            <div class="week-view" style="overflow-x: auto;">
                <table class="table table-bordered table-sm" style="min-width: 700px; font-size: 0.8rem; border-color: #e2e6f0;">
                    <thead>
                        <tr style="background: #f5f6fa;">
                            <th style="width: 80px; color: #1e2d4a;">Time</th>
                            ${days
                                .map(
                                    (day) => `
                                <th class="text-center" style="min-width: 90px; color: #1e2d4a;">
                                    <div class="fw-semibold" style="font-family: 'DM Sans', sans-serif;">${day.toLocaleDateString("en-US", { weekday: "short" })}</div>
                                    <div class="small" style="color: #6b7a99;">${day.getDate()}</div>
                                </th>
                            `,
                                )
                                .join("")}
                        </tr>
                    </thead>
                    <tbody>
        `;

        for (const slot of timeSlots) {
            const displayTime = this.formatTimeDisplay(slot);
            html += `
                <tr>
                    <td class="fw-semibold" style="vertical-align: middle; font-size: 0.75rem; background: #f5f6fa; color: #1e2d4a;">${displayTime}</td>
                    ${days
                        .map((day) => {
                            const status = this.getStatusForTimeSlot(
                                day,
                                slot,
                                this.currentFacility.id,
                            );
                            let statusIcon = "";
                            if (status.status === "available")
                                statusIcon =
                                    '<i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 0.9rem;"></i>';
                            else if (status.status === "booked")
                                statusIcon =
                                    '<i class="bi bi-x-circle-fill" style="color: #ef4444; font-size: 0.9rem;"></i>';
                            else if (status.status === "pending")
                                statusIcon =
                                    '<i class="bi bi-clock-fill" style="color: #f5bc40; font-size: 0.9rem;"></i>';
                            else
                                statusIcon =
                                    '<i class="bi bi-calendar-event-fill" style="color: #6f42c1; font-size: 0.9rem;"></i>';

                            return `
    <td class="text-center p-1" style="background-color: ${status.color}08; vertical-align: middle;">
        <div class="slot-cell p-1 rounded text-center" 
             style="cursor: ${status.status !== 'available' ? 'pointer' : 'default'}; transition: all 0.2s ease;"
             data-status="${status.status}"
             data-status-data='${this.escapeHtml(JSON.stringify({
                 status: status.status,
                 title: status.title,
                 color: status.color,
                 event: status.event ? {
                     request_id: status.event.request_id,
                     event_id: status.event.event_id,
                     title: status.event.title || status.event.event_name,
                     status: status.event.status,
                     start_time: status.event.start_time,
                     end_time: status.event.end_time,
                     all_day: status.event.all_day,
                     facilities: status.event.facilities,
                     description: status.event.description,
                     event_type: status.event.event_type,
                     user: status.event.user
                 } : null
             }))}">
            ${statusIcon}
            ${status.status !== "available" ? `<div class="small text-muted mt-1" style="font-size: 0.65rem; color: #6b7a99;">${this.escapeHtml(status.title.substring(0, 15))}${status.title.length > 15 ? "..." : ""}</div>` : ""}
        </div>
    </td>
`;
                        })
                        .join("")}
                </tr>
            `;
        }

        html += `
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

async renderMonthView() {
    const container = document.getElementById("fcCalendarContainer");
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(firstDay.getDate() - firstDay.getDay());
    const endDate = new Date(lastDay);
    endDate.setDate(lastDay.getDate() + (6 - lastDay.getDay()));

    await this.loadEventsForDateRange(
        startDate,
        endDate,
        this.currentFacility.id,
    );

    const days = [];
    for (
        let d = new Date(startDate);
        d <= endDate;
        d.setDate(d.getDate() + 1)
    ) {
        days.push(new Date(d));
    }

    const weeks = [];
    for (let i = 0; i < days.length; i += 7) {
        weeks.push(days.slice(i, i + 7));
    }

    let html = `
        <div class="month-view" style="overflow-x: auto;">
            <table class="table table-bordered table-sm" style="min-width: 600px; font-size: 0.8rem; border-color: #e2e6f0;">
                <thead>
                    <tr style="background: #f5f6fa;" class="text-center">
                        ${["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((day) => `<th style="padding: 0.5rem; color: #1e2d4a; font-weight: 600;">${day}</th>`).join("")}
                    </tr>
                </thead>
                <tbody>
    `;

    for (const week of weeks) {
        html += "<tr>";
        for (const day of week) {
            const isCurrentMonth = day.getMonth() === month;
            const dateStr = this.formatDate(day);

            const dayEvents = [];
            
            // Collect requisitions for this day
            for (const req of this.requisitions) {
                const reqDate = this.formatDate(
                    new Date(req.start_date || req.date),
                );
                if (
                    reqDate === dateStr &&
                    req.facilities?.some(
                        (f) =>
                            String(f.facility_id) ===
                            String(this.currentFacility.id),
                    )
                ) {
                    dayEvents.push({
                        type: "requisition",
                        status: req.status,
                        title: req.title,
                        request_id: req.request_id,
                        start_time: req.start_time,
                        end_time: req.end_time,
                        all_day: req.all_day,
                        facilities: req.facilities,
                        user: req.user,
                        color: req.status === "Scheduled" ? this.statusColors.booked : this.statusColors.pending
                    });
                }
            }
            
            // Collect calendar events for this day
            for (const event of this.calendarEvents) {
                const eventDate = this.formatDate(
                    new Date(event.start_date || event.date),
                );
                if (eventDate === dateStr) {
                    dayEvents.push({
                        type: "event",
                        title: event.event_name,
                        event_id: event.event_id,
                        event_type: event.event_type,
                        description: event.description,
                        start_time: event.start_time,
                        end_time: event.end_time,
                        all_day: event.all_day,
                        color: this.statusColors.event
                    });
                }
            }

            const hasEvents = dayEvents.length > 0;

            html += `
                <td class="p-1" style="height: 100px; vertical-align: top; background-color: ${isCurrentMonth ? "#fff" : "#f8f9fa"}; border-color: #e2e6f0;">
                    <div class="fw-semibold mb-1 ${!isCurrentMonth ? "text-muted" : ""}" style="font-size: 0.75rem; color: ${isCurrentMonth ? "#1e2d4a" : "#9aaac5"};">${day.getDate()}</div>
                    <div class="small" style="display: flex; flex-direction: column; gap: 2px;">
            `;
            
            if (hasEvents) {
                // Display events (up to 3, show +more for additional)
                const displayEvents = dayEvents.slice(0, 3);
                for (const event of displayEvents) {
                    // Determine status for this event
                    let eventStatus = 'event';
                    let eventStatusColor = event.color;
                    
                    if (event.type === 'requisition') {
                        eventStatus = event.status === 'Scheduled' ? 'booked' : 'pending';
                        eventStatusColor = event.status === 'Scheduled' ? this.statusColors.booked : this.statusColors.pending;
                    }
                    
                    // Create statusData object for hover
                    const statusDataForEvent = {
                        status: eventStatus,
                        title: event.title,
                        color: eventStatusColor,
                        event: event.type === 'requisition' ? {
                            request_id: event.request_id,
                            title: event.title,
                            status: event.status,
                            start_time: event.start_time,
                            end_time: event.end_time,
                            all_day: event.all_day,
                            facilities: event.facilities,
                            user: event.user
                        } : {
                            event_id: event.event_id,
                            title: event.title,
                            event_name: event.title,
                            event_type: event.event_type,
                            description: event.description,
                            start_time: event.start_time,
                            end_time: event.end_time,
                            all_day: event.all_day
                        }
                    };
                    
                    const statusDataJson = this.escapeHtml(JSON.stringify(statusDataForEvent));
                    
                    html += `
                        <div class="month-event p-1 rounded" 
                             style="background-color: ${eventStatusColor}15; font-size: 0.65rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-radius: 4px; cursor: pointer; transition: all 0.2s ease;"
                             data-status="${eventStatus}"
                             data-status-data='${statusDataJson}'>
                            <i class="bi ${event.type === "event" ? "bi-calendar-event" : "bi-calendar-check"}" style="font-size: 0.6rem; color: ${eventStatusColor};"></i>
                            <span class="ms-1" style="color: #1e2d4a;">${this.escapeHtml(event.title.substring(0, 15))}${event.title.length > 15 ? "..." : ""}</span>
                        </div>
                    `;
                }
                
                if (dayEvents.length > 3) {
                    html += `
                        <div class="text-muted mt-1" style="font-size: 0.6rem; color: #6b7a99;">
                            +${dayEvents.length - 3} more
                        </div>
                    `;
                }
            } else {
                // Available day
                html += `
                    <div class="text-center p-1 available-indicator">
                        <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 0.7rem;"></i>
                        <span class="text-muted" style="font-size: 0.65rem; color: #6b7a99;"> Available</span>
                    </div>
                `;
            }
            
            html += `
                    </div>
                 </td>
            `;
        }
        html += "</tr>";
    }

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
    
    // Attach hover events after rendering
    this.attachHoverEvents();
}

    attachHoverEvents() {
    // Select all slot cards and month events
    const hoverableElements = document.querySelectorAll('#fcCalendarContainer .slot-card, #fcCalendarContainer .slot-cell, #fcCalendarContainer .month-event');
    
    hoverableElements.forEach(element => {
        const status = element.dataset.status;
        
        // Only add hover for booked, pending, and event slots
        if (status && status !== 'available') {
            // Remove existing listeners to avoid duplicates
            element.removeEventListener('mouseenter', this.hoverEnterHandler);
            element.removeEventListener('mouseleave', this.hoverLeaveHandler);
            
            // Create bound handlers
            this.hoverEnterHandler = (e) => {
                e.stopPropagation();
                const statusDataJson = element.dataset.statusData;
                if (statusDataJson) {
                    try {
                        const statusData = JSON.parse(statusDataJson);
                        this.showHoverCard(e, element, statusData);
                    } catch (err) {
                        console.error('Error parsing status data:', err);
                    }
                }
            };
            
            this.hoverLeaveHandler = () => {
                this.removeHoverCard();
            };
            
            element.addEventListener('mouseenter', this.hoverEnterHandler);
            element.addEventListener('mouseleave', this.hoverLeaveHandler);
        }
    });
}



    formatTimeDisplay(timeStr) {
        const [hour, minute] = timeStr.split(":");
        const hour12 = hour % 12 || 12;
        const ampm = hour >= 12 ? "PM" : "AM";
        return minute === "00"
            ? `${hour12}:00 ${ampm}`
            : `${hour12}:30 ${ampm}`;
    }

    attachModalEvents() {
        // View buttons
        document.querySelectorAll(".btn-view-tab").forEach((btn) => {
            btn.removeEventListener("click", this.viewChangeHandler);
            this.viewChangeHandler = async (e) => {
                this.currentView = btn.dataset.view;
                await this.renderCurrentView();
            };
            btn.addEventListener("click", this.viewChangeHandler);
        });

        // Navigation
        const prevBtn = document.getElementById("fcPrevBtn");
        const nextBtn = document.getElementById("fcNextBtn");
        const todayBtn = document.getElementById("fcTodayBtn");

        if (prevBtn) {
            prevBtn.addEventListener("mouseenter", () => {
                prevBtn.style.background = "#e8edf8";
                prevBtn.style.color = "#041a4b";
            });
            prevBtn.addEventListener("mouseleave", () => {
                prevBtn.style.background = "transparent";
                prevBtn.style.color = "#6b7a99";
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("mouseenter", () => {
                nextBtn.style.background = "#e8edf8";
                nextBtn.style.color = "#041a4b";
            });
            nextBtn.addEventListener("mouseleave", () => {
                nextBtn.style.background = "transparent";
                nextBtn.style.color = "#6b7a99";
            });
        }

        if (prevBtn) {
            prevBtn.removeEventListener("click", this.prevHandler);
            this.prevHandler = async () => {
                if (this.currentView === "timeGridDay") {
                    this.currentDate.setDate(this.currentDate.getDate() - 1);
                } else if (this.currentView === "timeGridWeek") {
                    this.currentDate.setDate(this.currentDate.getDate() - 7);
                } else {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                }
                await this.renderCurrentView();
                await this.renderMiniCalendar();
            };
            prevBtn.addEventListener("click", this.prevHandler);
        }

        if (nextBtn) {
            nextBtn.removeEventListener("click", this.nextHandler);
            this.nextHandler = async () => {
                if (this.currentView === "timeGridDay") {
                    this.currentDate.setDate(this.currentDate.getDate() + 1);
                } else if (this.currentView === "timeGridWeek") {
                    this.currentDate.setDate(this.currentDate.getDate() + 7);
                } else {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                }
                await this.renderCurrentView();
                await this.renderMiniCalendar();
            };
            nextBtn.addEventListener("click", this.nextHandler);
        }

        if (todayBtn) {
            todayBtn.removeEventListener("click", this.todayHandler);
            this.todayHandler = async () => {
                this.currentDate = new Date();
                await this.renderCurrentView();
                await this.renderMiniCalendar();
            };
            todayBtn.addEventListener("click", this.todayHandler);
        }

        const modalElement = document.getElementById("fullCalendarModal");
        modalElement.addEventListener("hidden.bs.modal", () => {
            modalElement.remove();
            this.modalInstance = null;
        });
    }
}

// Initialize calendar
let calendarInstance = null;

function getCalendarInstance() {
    if (!calendarInstance) {
        calendarInstance = new FullCalendarStyle();
    }
    return calendarInstance;
}

window.showAvailabilityCalendar = function (facilityData) {
    console.log("Showing modal:", facilityData);
    const instance = getCalendarInstance();
    instance.showModal(facilityData);
};

// Fallback for custom events
document.addEventListener("facilityAvailabilityRequested", (event) => {
    if (event.detail && event.detail.facilityData) {
        window.showAvailabilityCalendar(event.detail.facilityData);
    }
});

console.log("FullCalendarStyle loaded and ready");
