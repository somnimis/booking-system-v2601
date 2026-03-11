/**
 * CALENDAR MODULE - SEARCH FEATURE DOCUMENTATION
 * ==============================================
 * 
 * To add search functionality to any view:
 * 
 * 1. Add the required HTML elements:
 * 
 *    <!-- Search Input -->
 *    <div class="input-group">
 *        <input type="text" id="eventSearchInput" placeholder="Search events...">
 *        <button id="clearSearchBtn"><i class="bi bi-x-lg"></i></button>
 *    </div>
 * 
 *    <!-- Results Container (place inside your calendar card) -->
 *    <div id="searchResultsContainer" class="d-none">
 *        <button id="backToCalendarBtn">Back to Calendar</button>
 *        <div id="searchResultsList"></div>
 *    </div>
 * 
 * 2. Initialize CalendarModule with search config:
 * 
 *    const calendar = new CalendarModule({
 *        containerId: 'yourCalendarId',
 *        miniCalendarContainerId: 'yourMiniCalendarId',
 *        monthYearId: 'yourMonthYearId',
 *        eventModalId: 'yourModalId',
 *        // Optional: customize search element IDs (defaults shown)
 *        searchInputId: 'eventSearchInput',
 *        searchResultsContainerId: 'searchResultsContainer',
 *        searchResultsListId: 'searchResultsList'
 *    });
 * 
 * 3. Initialize the calendar:
 * 
 *    calendar.initialize();
 * 
 * The search feature will automatically:
 * - Search across titles, requesters, facilities, dates (Feb, March), and times (1:30pm)
 * - Show results in an overlay with "Back to Calendar" button
 * - Highlight matching text in results
 * - Allow clicking results to open event modal
 * - Clear search when "Clear" button is clicked
 */

// calendar.js - START
console.log("=== CALENDAR.JS LOADING ===");

// Clear any existing global variables that might conflict
if (window.calendarModuleInstance) {
    console.warn("Existing calendar instance found, cleaning up...");
    delete window.calendarModuleInstance;
}

// calendar-module.js
class CalendarModule {
    constructor(config = {}) {
        this.statuses = {};
        this.statusColors = {};
        this.selectedFacilityIds = [];
        this.parentModal = null;
    this.searchQuery = '';
    this.searchDebounceTimer = null;
    this.searchResultsVisible = false;
        this.searchResultsContainerId =
            config.searchResultsContainerId || "searchResultsContainer";
        this.searchResultsListId =
            config.searchResultsListId || "searchResultsList";
        this.searchInputId = config.searchInputId || "eventSearchInput";

        console.log(
            "🎯 CalendarModule CONSTRUCTOR called with config:",
            config,
        );

        // Store instance globally for debugging
        window.calendarModuleInstance = this;
        // Default configuration
        this.config = {
            isAdmin: false,
            apiEndpoint: "/api/requisition-forms/calendar-events",
            calendarEventsEndpoint: "/api/calendar-events/all", // Separate endpoint for calendar events
            adminToken: null,
            containerId: "calendar",
            miniCalendarContainerId: "miniCalendarDays",
            monthYearId: "currentMonthYear",
            eventModalId: "calendarEventModal",
            ...config,
        };

        console.log("📋 Final config:", this.config);
        console.log(
            "🔍 Calendar container exists?",
            !!document.getElementById(this.config.containerId),
        );
        console.log(
            "🔍 Mini calendar container exists?",
            !!document.getElementById(this.config.miniCalendarContainerId),
        );

        this.calendar = null;
        this.currentDate = new Date();
        this.allEvents = [];
        this.filteredEvents = [];
        this.selectedFacilityIds = [];
        this.allFacilities = [];
        this.selectedCategoryIds = []; // For category filtering
        this.allFacilityCategories = []; // Store facility categories
        this.filterMode = "facilities"; // 'facilities' or 'categories' - determines which filter to use

        // For edit functionality
        this.currentRequestId = null;
        this.originalCalendarTitle = "";
        this.originalCalendarDescription = "";

        // Month names
        this.monthNames = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December",
        ];

        // Mini calendar state
        this.miniCalendarInitialized = false;
    }

    handleSearch(query) {
        // Debounce to avoid too many filters while typing
        clearTimeout(this.searchDebounceTimer);

        this.searchDebounceTimer = setTimeout(() => {
            this.searchQuery = query.trim().toLowerCase();
            console.log(`Searching for: "${this.searchQuery}"`);
            this.applyFilters(); // Reuse existing filter method
        }, 300); // 300ms debounce
    }

    /**
     * Setup search functionality and event listeners
     */
    setupSearch() {
        const searchInput = document.getElementById(this.searchInputId);
        const clearButton = document.getElementById("clearSearchBtn");
        const backToCalendarBtn = document.getElementById("backToCalendarBtn");

        if (!searchInput) return;

        // Clear button click
        if (clearButton) {
            clearButton.addEventListener("click", () => {
                searchInput.value = "";
                this.clearSearch();
                this.hideSearchResults();
            });
        }

        // Search input handler with debounce
        searchInput.addEventListener("input", (e) => {
            clearTimeout(this.searchDebounceTimer);
            const query = e.target.value.trim();

            if (query === "") {
                this.clearSearch();
                this.hideSearchResults();
                return;
            }

            this.searchDebounceTimer = setTimeout(() => {
                this.performSearch(query);
            }, 500);
        });

        // Enter key handler
        searchInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                clearTimeout(this.searchDebounceTimer);
                const query = e.target.value.trim();
                if (query) {
                    this.performSearch(query);
                }
            }
        });

        // Back to calendar button
        if (backToCalendarBtn) {
            backToCalendarBtn.addEventListener("click", () => {
                this.hideSearchResults();
                if (searchInput) searchInput.value = "";
                this.clearSearch();
            });
        }
    }

    /**
     * Perform search with the given query
     */
    performSearch(query) {
        if (!this.allEvents) {
            console.warn("Calendar not ready");
            return;
        }

        this.showSearchLoading(true);

        // Small delay for visual feedback
        setTimeout(() => {
            const results = this.searchEvents(query);
            this.displaySearchResults(results, query);
            this.showSearchLoading(false);
            this.showSearchResults();
        }, 300);
    }

    /**
     * Search through events for matching query
     */
    searchEvents(query) {
        const searchTerm = query.toLowerCase().trim();
        const events = this.allEvents.filter((event) => event != null);

        return events.filter((event) => {
            const eventType =
                event.extendedProps?.eventType ||
                event.eventType ||
                "requisition";

            // Parse search term for month names and time patterns
            const monthNames = [
                "january",
                "february",
                "march",
                "april",
                "may",
                "june",
                "july",
                "august",
                "september",
                "october",
                "november",
                "december",
            ];
            const monthAbbr = [
                "jan",
                "feb",
                "mar",
                "apr",
                "may",
                "jun",
                "jul",
                "aug",
                "sep",
                "oct",
                "nov",
                "dec",
            ];

            const hasMonthName =
                monthNames.some((month) => searchTerm.includes(month)) ||
                monthAbbr.some((month) => searchTerm.includes(month));

            const timePattern =
                /\b([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]\b|\b([0-9]|0[0-9]|1[0-2]):[0-5][0-9]\s*(am|pm)\b/i;
            const hasTimePattern = timePattern.test(searchTerm);

            let searchHour = null,
                searchMinute = null,
                searchIsPM = false;

            if (hasTimePattern) {
                const timeMatch = searchTerm.match(timePattern);
                if (timeMatch) {
                    const timeParts = timeMatch[0].match(
                        /(\d{1,2}):(\d{2})\s*(am|pm)?/i,
                    );
                    if (timeParts) {
                        searchHour = parseInt(timeParts[1]);
                        searchMinute = parseInt(timeParts[2]);
                        searchIsPM =
                            timeParts[3] && timeParts[3].toLowerCase() === "pm";

                        if (searchIsPM && searchHour < 12) searchHour += 12;
                        if (!searchIsPM && searchHour === 12) searchHour = 0;
                    }
                }
            }

            if (eventType === "calendar_event") {
                const eventName =
                    event.extendedProps?.event_name || event.title || "";
                const description = event.extendedProps?.description || "";
                const eventTypeName = event.extendedProps?.event_type || "";

                const startDate =
                    event.extendedProps?.start_date || event.start;
                const startTime = event.extendedProps?.start_time || "";

                const dateStr = this.formatDateForSearch(startDate);
                const monthYearStr = this.formatMonthYearForSearch(startDate);

                const matchesText =
                    eventName.toLowerCase().includes(searchTerm) ||
                    description.toLowerCase().includes(searchTerm) ||
                    eventTypeName.toLowerCase().includes(searchTerm);

                const matchesDate = !hasMonthName
                    ? false
                    : dateStr.includes(searchTerm) ||
                      monthYearStr.includes(searchTerm);

                let matchesTime = false;
                if (hasTimePattern && startTime) {
                    matchesTime = this.compareTimeWithSearch(
                        startTime,
                        searchHour,
                        searchMinute,
                        searchTerm,
                    );
                }

                return matchesText || matchesDate || matchesTime;
            } else {
                const title = event.title || "";
                const calendarTitle = event.extendedProps?.calendar_title || "";
                const requester = event.extendedProps?.requester || "";
                const purpose = event.extendedProps?.purpose || "";
                const status = event.extendedProps?.status || "";
                const requestId = String(
                    event.extendedProps?.request_id || event.request_id || "",
                );

                const schedule = event.extendedProps?.schedule || {};
                const startDate = schedule.start_date || event.start;
                const startTime =
                    schedule.start_time || this.extractTimeFromISO(event.start);

                const dateStr = this.formatDateForSearch(startDate);
                const monthYearStr = this.formatMonthYearForSearch(startDate);
                const fullDuration = schedule.full_duration || "";

                const facilities = event.extendedProps?.facilities || [];
                const facilityNames = facilities
                    .map((f) => f.name || "")
                    .join(" ");

                const matchesText =
                    title.toLowerCase().includes(searchTerm) ||
                    calendarTitle.toLowerCase().includes(searchTerm) ||
                    requester.toLowerCase().includes(searchTerm) ||
                    purpose.toLowerCase().includes(searchTerm) ||
                    status.toLowerCase().includes(searchTerm) ||
                    requestId.includes(searchTerm) ||
                    facilityNames.toLowerCase().includes(searchTerm) ||
                    fullDuration.toLowerCase().includes(searchTerm);

                const matchesDate = !hasMonthName
                    ? false
                    : dateStr.includes(searchTerm) ||
                      monthYearStr.includes(searchTerm);

                let matchesTime = false;
                if (hasTimePattern && startTime) {
                    matchesTime = this.compareTimeWithSearch(
                        startTime,
                        searchHour,
                        searchMinute,
                        searchTerm,
                    );
                }

                return matchesText || matchesDate || matchesTime;
            }
        });
    }

    /**
     * Format date for search comparison
     */
    formatDateForSearch(dateStr) {
        if (!dateStr) return "";
        try {
            const date = new Date(dateStr);
            return date
                .toLocaleDateString("en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric",
                })
                .toLowerCase();
        } catch {
            return "";
        }
    }

    /**
     * Format month/year for search comparison
     */
    formatMonthYearForSearch(dateStr) {
        if (!dateStr) return "";
        try {
            const date = new Date(dateStr);
            return date
                .toLocaleDateString("en-US", {
                    month: "long",
                    year: "numeric",
                })
                .toLowerCase();
        } catch {
            return "";
        }
    }

    /**
     * Extract time from ISO string
     */
    extractTimeFromISO(isoString) {
        if (!isoString) return "";
        try {
            const date = new Date(isoString);
            const hours = date.getHours().toString().padStart(2, "0");
            const minutes = date.getMinutes().toString().padStart(2, "0");
            return `${hours}:${minutes}`;
        } catch {
            return "";
        }
    }

    /**
     * Compare event time with search time
     */
    compareTimeWithSearch(eventTimeStr, searchHour, searchMinute, searchTerm) {
        if (!eventTimeStr) return false;

        try {
            let eventHour, eventMinute;

            if (eventTimeStr.includes(":")) {
                const timeParts = eventTimeStr.split(":");
                eventHour = parseInt(timeParts[0]);
                eventMinute = parseInt(timeParts[1]);
            }

            if (eventHour === undefined) return false;

            if (searchHour !== null && searchMinute !== null) {
                if (eventHour === searchHour && eventMinute === searchMinute) {
                    return true;
                }
            }

            const eventTimeStrFormatted = `${eventHour.toString().padStart(2, "0")}:${eventMinute.toString().padStart(2, "0")}`;
            const eventTime12Hour = this.formatTimeTo12Hour(
                eventHour,
                eventMinute,
            ).toLowerCase();

            const searchTermClean = searchTerm
                .replace(/\s*(am|pm)\b/i, "")
                .trim();

            return (
                eventTimeStrFormatted.includes(searchTermClean) ||
                eventTime12Hour.includes(searchTermClean) ||
                eventTime12Hour.includes(searchTerm.toLowerCase())
            );
        } catch (error) {
            console.warn("Error comparing time:", error);
            return false;
        }
    }

    /**
     * Format time to 12-hour format
     */
    formatTimeTo12Hour(hour, minute) {
        const period = hour >= 12 ? "pm" : "am";
        let hour12 = hour % 12;
        hour12 = hour12 === 0 ? 12 : hour12;
        return `${hour12}:${minute.toString().padStart(2, "0")}${period}`;
    }

/**
 * Display search results in the results container
 */
displaySearchResults(results, query) {
    const searchResultsList = document.getElementById(this.searchResultsListId);
    if (!searchResultsList) return;
    
    if (results.length === 0) {
        searchResultsList.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted opacity-25"></i>
                <h5 class="mt-3 text-muted">No events found</h5>
                <p class="text-muted small">No results match "${query}"</p>
                <p class="text-muted small mt-2">
                    <i class="bi bi-info-circle"></i> 
                    Try searching by: date (February, March), time (1:30, 2:30pm), title, requester, or facility
                </p>
            </div>
        `;
        return;
    }
    
    const highlightTerm = query.toLowerCase();
    
    let html = `<p class="text-muted mb-3">Found ${results.length} event${results.length > 1 ? 's' : ''}</p>`;
    
    results.forEach(event => {
        const eventType = event.extendedProps?.eventType || event.eventType || "requisition";
        const isCalendarEvent = eventType === "calendar_event";
        
        let dateInfo = '', timeInfo = '';
        
        if (isCalendarEvent) {
            const startDate = event.extendedProps?.start_date || event.start;
            const startTime = event.extendedProps?.start_time;
            const endTime = event.extendedProps?.end_time;
            
            dateInfo = this.formatDateForDisplay(startDate);
            if (startTime && endTime && startTime !== '00:00:00') {
                timeInfo = `${this.formatTimeForDisplay(startTime)} - ${this.formatTimeForDisplay(endTime)}`;
            }
        } else {
            const schedule = event.extendedProps?.schedule || {};
            dateInfo = schedule.start_date ? this.formatDateForDisplay(schedule.start_date) : '';
            if (schedule.start_time && schedule.end_time) {
                timeInfo = `${this.formatTimeForDisplay(schedule.start_time)} - ${this.formatTimeForDisplay(schedule.end_time)}`;
            } else if (event.start) {
                const startTime = this.extractTimeFromISO(event.start);
                const endTime = event.end ? this.extractTimeFromISO(event.end) : '';
                if (startTime && endTime) {
                    timeInfo = `${this.formatTimeForDisplay(startTime)} - ${this.formatTimeForDisplay(endTime)}`;
                }
            }
        }
        
        const facilities = event.extendedProps?.facilities || [];
        const facilityNames = facilities.map(f => f.name).join(', ');
        
        const title = this.highlightText(event.title || 'Untitled Event', highlightTerm);
        const requester = !isCalendarEvent && event.extendedProps?.requester ? 
                         `<span class="text-muted"><i class="bi bi-person me-1"></i> ${this.highlightText(event.extendedProps.requester, highlightTerm)}</span>` : '';
        
        // Store event data as data attributes for retrieval when clicked
        const eventData = JSON.stringify({
            id: event.id,
            title: event.title,
            start: event.start,
            end: event.end,
            allDay: event.allDay,
            backgroundColor: event.backgroundColor || event.color,
            borderColor: event.borderColor || event.color,
            extendedProps: event.extendedProps || event
        }).replace(/"/g, '&quot;'); // Escape quotes for HTML attribute
        
        html += `
            <div class="card mb-2 search-result-item" 
                 data-event-id="${event.id}"
                 data-event='${eventData}'
                 style="cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">${title}</h6>
                            <div class="small d-flex flex-wrap gap-2 align-items-center mb-1">
                                ${isCalendarEvent ? 
                                    '<span class="badge bg-success">Calendar Event</span>' : 
                                    `<span class="badge" style="background-color: ${event.color || '#007bff'}">${event.extendedProps?.status || 'Event'}</span>`
                                }
                                ${requester}
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i> ${this.highlightText(dateInfo, highlightTerm)}
                                ${timeInfo ? `<span class="ms-2"><i class="bi bi-clock me-1"></i> ${this.highlightText(timeInfo, highlightTerm)}</span>` : ''}
                            </div>
                            ${facilityNames ? `<div class="small text-muted mt-1"><i class="bi bi-building me-1"></i> ${this.highlightText(facilityNames, highlightTerm)}</div>` : ''}
                        </div>
                        <small class="text-primary">Click to view</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    searchResultsList.innerHTML = html;
    
    // Add click handlers using event delegation to ensure they always work
    searchResultsList.addEventListener('click', (e) => {
        // Find the closest search-result-item ancestor
        const resultItem = e.target.closest('.search-result-item');
        if (!resultItem) return;
        
        const eventDataAttr = resultItem.getAttribute('data-event');
        if (!eventDataAttr) return;
        
        try {
            const eventData = JSON.parse(eventDataAttr);
            
            this.hideSearchResults();
            const searchInput = document.getElementById(this.searchInputId);
            if (searchInput) searchInput.value = '';
            this.clearSearch();
            
            // Create a proper event object for the modal
            const calendarEvent = {
                extendedProps: eventData.extendedProps || eventData,
                title: eventData.title,
                start: eventData.start,
                end: eventData.end,
                allDay: eventData.allDay,
                backgroundColor: eventData.backgroundColor,
                borderColor: eventData.borderColor
            };
            
            // Call the showEventModal method
            this.showEventModal(calendarEvent);
            
        } catch (error) {
            console.error('Error parsing event data:', error);
        }
    });
}

    /**
     * Highlight matching text in results
     */
    highlightText(text, searchTerm) {
        if (!text || !searchTerm || searchTerm.length < 2) return text;

        try {
            const regex = new RegExp(
                `(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`,
                "gi",
            );
            return text.replace(
                regex,
                '<span class="bg-warning bg-opacity-25">$1</span>',
            );
        } catch {
            return text;
        }
    }

    /**
     * Format date for display
     */
    formatDateForDisplay(dateStr) {
        if (!dateStr) return "";
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString("en-US", {
                month: "long",
                day: "numeric",
                year: "numeric",
            });
        } catch {
            return dateStr;
        }
    }

    /**
     * Format time for display
     */
    formatTimeForDisplay(timeStr) {
        if (!timeStr) return "";
        try {
            const time = timeStr.split(":").slice(0, 2).join(":");
            const date = new Date(`2000-01-01T${time}`);
            return date.toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            });
        } catch {
            return timeStr;
        }
    }

    /**
     * Show/hide search loading state
     */
    showSearchLoading(show) {
        const searchResultsList = document.getElementById(
            this.searchResultsListId,
        );
        if (!searchResultsList) return;

        if (show) {
            searchResultsList.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Searching...</span>
                </div>
                <p class="mt-2 text-muted">Searching events...</p>
            </div>
        `;
        }
    }

    /**
     * Show search results container
     */
    showSearchResults() {
        const searchResultsContainer = document.getElementById(
            this.searchResultsContainerId,
        );
        const calendarEl = document.getElementById(this.config.containerId);

        if (searchResultsContainer && calendarEl) {
            searchResultsContainer.classList.remove("d-none");
            calendarEl.style.opacity = "0.3";
            this.searchResultsVisible = true;
        }
    }

    /**
     * Hide search results container
     */
    hideSearchResults() {
        const searchResultsContainer = document.getElementById(
            this.searchResultsContainerId,
        );
        const calendarEl = document.getElementById(this.config.containerId);

        if (searchResultsContainer && calendarEl) {
            searchResultsContainer.classList.add("d-none");
            calendarEl.style.opacity = "1";
            this.searchResultsVisible = false;
        }
    }

    /**
     * Clear search and reset filters
     */
    clearSearch() {
        this.searchQuery = "";

        // Clear the input field if it exists
        const searchInput = document.getElementById(this.searchInputId);
        if (searchInput) {
            searchInput.value = "";
        }

        this.applyFilters();
    }

    // Update setupGlobalEventListeners method to include search
    setupGlobalEventListeners() {
        // Add event listeners for status filter checkboxes
        document
            .querySelectorAll(".event-filter-checkbox")
            .forEach((checkbox) => {
                checkbox.addEventListener("change", () => {
                    this.applyFilters();
                });
            });

        // Setup search functionality
        this.setupSearch();
    }

    // Update initialize method to ensure search is set up after calendar loads
async initialize() {
    try {
        this.updateLoadingState(true);

        // Track multiple loading states
        this.loadingStates = {
            statusesLoaded: false,
            allEventsLoaded: false,
            facilitiesLoaded: false,
            miniCalendarRendered: false,
            fullCalendarRendered: false,
        };

        // Load statuses FIRST
        await this.loadStatuses();
        this.loadingStates.statusesLoaded = true;

        // Load facilities for filter
        if (this.filterMode === 'categories') {
            await this.loadFacilityCategories();
        } else {
            await this.loadFacilitiesForCalendar();
        }
        this.loadingStates.facilitiesLoaded = true;

        // Load ALL events (both requisition and calendar)
        await this.loadAllEvents();
        this.loadingStates.allEventsLoaded = true;

        // IMPORTANT: Apply initial filters
        this.applyFilters();

        // Initialize mini calendar AFTER events are loaded
        this.initializeMiniCalendar();
        this.initializeFullCalendar();

        // Setup listeners (including search)
        this.setupGlobalEventListeners();
        this.setupSearch();
        this.setupFacilityFilterSync();

        // Check mini calendar after a delay
        setTimeout(() => {
            this.checkMiniCalendarReady();
        }, 300);

        // Force resize
        setTimeout(() => {
            this.forceCalendarProperRender();
        }, 500);

        // Remove the 5-second fallback timeout - this was hiding too early
        // setTimeout(() => {
        //     this.hideLoadingOverlay();
        // }, 5000);
        
        console.log('Calendar initialized with search:', {
            searchInputId: this.searchInputId,
            searchResultsContainerId: this.searchResultsContainerId,
            searchResultsListId: this.searchResultsListId
        });
    } catch (error) {
        console.error("Error initializing calendar:", error);
        this.hideLoadingOverlay();
    }
}

    forceCalendarProperRender() {
        if (!this.calendar) return;

        // Force multiple resize updates
        const forceResize = () => {
            this.calendar.updateSize();
            console.log("Calendar resize forced");
        };

        // Execute immediately
        forceResize();

        // Execute after a series of delays
        [10, 50, 100, 200, 500, 1000].forEach((delay) => {
            setTimeout(forceResize, delay);
        });

        // Also trigger when window resizes
        window.addEventListener("resize", () => {
            setTimeout(forceResize, 100);
        });
    }

    updateStatusFilterColors() {
        // Get all status filter checkboxes
        const checkboxes = document.querySelectorAll(".event-filter-checkbox");

        checkboxes.forEach((checkbox) => {
            const statusName = checkbox.value;
            const color = this.statusColors[statusName];

            if (color) {
                // Find the label
                const label = checkbox
                    .closest(".form-check")
                    ?.querySelector(".form-check-label");
                if (label) {
                    // Also style the checkbox border
                    checkbox.style.borderColor = color;

                    // Style checked state
                    checkbox.addEventListener("change", () => {
                        if (checkbox.checked) {
                            checkbox.style.backgroundColor = color;
                            checkbox.style.borderColor = color;
                        } else {
                            checkbox.style.backgroundColor = "";
                            checkbox.style.borderColor = "";
                        }
                    });

                    // Set initial state
                    if (checkbox.checked) {
                        checkbox.style.backgroundColor = color;
                        checkbox.style.borderColor = color;
                    }
                }
            }
        });
    }

    async loadStatuses() {
        try {
            console.log("Loading statuses from API...");

            const headers = {};
            if (this.config.isAdmin && this.config.adminToken) {
                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
            }

            // Use your API endpoint
            const response = await fetch("/api/form-statuses", { headers });

            if (!response.ok) {
                throw new Error(`Failed to fetch statuses: ${response.status}`);
            }

            const result = await response.json();

            // Process statuses - assuming result is an array of status objects
            if (Array.isArray(result)) {
                result.forEach((status) => {
                    const statusName = status.status_name;
                    const colorCode = status.color_code || "#007bff"; // Default blue if no color

                    // Store by status name (and optionally by ID)
                    this.statuses[statusName] = {
                        id: status.status_id,
                        name: statusName,
                        color: colorCode,
                    };

                    this.statusColors[statusName] = colorCode;
                });

                console.log("Loaded statuses:", Object.keys(this.statuses));
                console.log("Status colors:", this.statusColors);

                // Apply colors to status filter checkboxes
                this.updateStatusFilterColors();
            } else {
                console.warn("Status API did not return array:", result);
            }
        } catch (error) {
            console.error("Error loading statuses:", error);
        }
    }
    async initialize() {
        try {
            this.updateLoadingState(true);

            // Track multiple loading states
            this.loadingStates = {
                statusesLoaded: false,
                allEventsLoaded: false,
                facilitiesLoaded: false,
                miniCalendarRendered: false,
                fullCalendarRendered: false,
            };

            // Load statuses FIRST
            await this.loadStatuses();
            this.loadingStates.statusesLoaded = true;

            // Load facilities for filter
            if (this.filterMode === "categories") {
                await this.loadFacilityCategories();
            } else {
                await this.loadFacilitiesForCalendar();
            }
            this.loadingStates.facilitiesLoaded = true;

            // Load ALL events (both requisition and calendar)
            await this.loadAllEvents();
            this.loadingStates.allEventsLoaded = true;

            // IMPORTANT: Apply initial filters
            this.applyFilters();

            // Initialize mini calendar AFTER events are loaded
            this.initializeMiniCalendar();
            this.initializeFullCalendar();

            // Setup listeners
            this.setupGlobalEventListeners();
            this.setupFacilityFilterSync();

            // Check mini calendar after a delay
            setTimeout(() => {
                this.checkMiniCalendarReady();
            }, 300);

            // Force resize
            setTimeout(() => {
                this.forceCalendarProperRender();
            }, 500);

            // Fallback timeout
            setTimeout(() => {
                this.hideLoadingOverlay();
            }, 5000);
        } catch (error) {
            console.error("Error initializing calendar:", error);
            this.hideLoadingOverlay();
        }
    }
    async loadAllEvents() {
        try {
            // Clear all events first
            this.allEvents = [];
            this.filteredEvents = [];

            // Load requisition events
            await this.loadRequisitionEvents();

            // Load calendar events
            await this.loadCalendarEvents();

            console.log(`Total events loaded: ${this.allEvents.length}`);
            console.log(
                `- Requisition events: ${this.requisitionEvents?.length || 0}`,
            );
            console.log(
                `- Calendar events: ${this.calendarEvents?.length || 0}`,
            );

            // Force update of calendar display
            this.applyFilters();
        } catch (error) {
            console.error("Error loading all events:", error);
        }
    }

    // === UPDATED: Renamed from loadCalendarEvents to loadRequisitionEvents ===
    async loadRequisitionEvents() {
        console.log("Loading requisition events...");

        try {
            const calendarContainer = document.getElementById(
                this.config.containerId,
            );
            if (calendarContainer) {
                calendarContainer.classList.add("loading");
            }

            const headers = {};
            if (this.config.isAdmin && this.config.adminToken) {
                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
            }

            // Add admin flag to request
            const params = new URLSearchParams();
            if (this.config.isAdmin) {
                params.append("admin_view", "true");
            }

            // Make API call for requisition events
            const response = await fetch(
                `${this.config.apiEndpoint}?${params}`,
                { headers },
            );

            // Parse the response
            const result = await response.json();

            // Process the result
            if (result.success && result.data) {
                // Filter out any null/undefined events from API response
                const validData = result.data.filter((event) => event != null);

                // Map requisition events and apply status colors
                const requisitionEvents = validData
                    .map((event) => {
                        if (!event) return null;

                        // Get status color for this event
                        const statusName = event.extendedProps?.status;
                        const statusColor =
                            this.statusColors[statusName] ||
                            event.extendedProps?.color ||
                            "#007bff";

                        // Update event with status color
                        return {
                            ...event,
                            eventType: "requisition", // Add event type identifier
                            color: statusColor,
                            extendedProps: {
                                ...event.extendedProps,
                                eventType: "requisition",
                                color: statusColor,
                            },
                        };
                    })
                    .filter((event) => event != null);

                console.log(
                    `Loaded ${requisitionEvents.length} requisition events`,
                );

                // Store separately for reference
                this.requisitionEvents = requisitionEvents;

                // Initialize allEvents with requisition events
                this.allEvents = requisitionEvents;

                // DEBUG: Check event structure
                this.debugEventStructure();
            } else {
                console.error("API did not return success or data:", result);
                this.allEvents = [];
                this.requisitionEvents = [];
            }

            // Remove loading class
            if (calendarContainer) {
                calendarContainer.classList.remove("loading");
            }
        } catch (error) {
            console.error("Error loading requisition events:", error);
            this.allEvents = [];
            this.requisitionEvents = [];

            // Ensure loading state is removed even on error
            const calendarContainer = document.getElementById(
                this.config.containerId,
            );
            if (calendarContainer) {
                calendarContainer.classList.remove("loading");
            }
        }
    }

    //  checks mini calendar if its ready

    checkMiniCalendarReady() {
        const daysContainer = document.getElementById(
            this.config.miniCalendarContainerId,
        );
        if (daysContainer && daysContainer.children.length > 0) {
            this.loadingStates.miniCalendarRendered = true;
            this.checkAllLoaded();
        } else {
            // Check again
            setTimeout(() => this.checkMiniCalendarReady(), 100);
        }
    }

checkAllLoaded() {
    console.log("Loading states:", this.loadingStates);

    const allLoaded = Object.values(this.loadingStates).every(
        (state) => state === true
    );

    if (allLoaded) {
        console.log("All calendar components loaded");
        // Add a small delay to ensure UI is completely settled
        setTimeout(() => {
            this.hideLoadingOverlay();
        }, 300);
    }
}

    async loadFacilitiesForCalendar() {
        try {
            const response = await fetch("/api/facilities");
            const result = await response.json();
            this.allFacilities = result.data || result;
            this.loadingStates.facilitiesLoaded = true;
        } catch (error) {
            console.error("Error loading facilities:", error);
            this.loadingStates.facilitiesLoaded = true; // Mark as loaded even on error
        }
    }

    async loadFacilityCategories() {
        try {
            console.log("Loading facility categories...");
            const response = await fetch("/api/facility-categories/index");

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            // Handle both array response or {data: [...]} structure
            const categories = Array.isArray(result)
                ? result
                : result.data || [];

            if (categories.length > 0) {
                // Define pastel colors for categories (cycling through colors)
                const categoryColors = [
                    { bg: "#e3f2fd", text: "#0d47a1" }, // pastel blue
                    { bg: "#e8f5e8", text: "#1b5e20" }, // pastel green
                    { bg: "#fff3e0", text: "#e65100" }, // pastel orange
                    { bg: "#f3e5f5", text: "#4a148c" }, // pastel purple
                    { bg: "#ffebee", text: "#b71c1c" }, // pastel red
                    { bg: "#e0f2f1", text: "#004d40" }, // pastel teal
                    { bg: "#fff8e1", text: "#ff6f00" }, // pastel amber
                    { bg: "#e1f5fe", text: "#01579b" }, // pastel light blue
                ];

                // Create a mapping of subcategory_id to facility_id
                // Since subcategories represent facilities, we'll store both
                this.allFacilityCategories = [];
                this.categoryToFacilityMap = {}; // Maps category_id to array of facility_ids
                this.subcategoryToFacilityMap = {}; // Maps subcategory_id to facility_id

                categories.forEach((category, index) => {
                    const categoryId = category.category_id;
                    const color = categoryColors[index % categoryColors.length];

                    // Store category
                    this.allFacilityCategories.push({
                        id: categoryId,
                        name: category.category_name,
                        type: "category",
                        color_code: color.bg,
                        text_color: color.text,
                        description: category.description || "",
                    });

                    // Initialize category mapping
                    this.categoryToFacilityMap[categoryId] = [];

                    // Process subcategories (these are the actual facilities)
                    if (
                        category.subcategories &&
                        category.subcategories.length > 0
                    ) {
                        category.subcategories.forEach((sub) => {
                            const subId = sub.subcategory_id;

                            // Store subcategory as a facility option
                            this.allFacilityCategories.push({
                                id: subId,
                                parentId: categoryId,
                                name: sub.subcategory_name,
                                type: "subcategory",
                                color_code: color.bg,
                                text_color: color.text,
                                parentName: category.category_name,
                            });

                            // Map subcategory to itself (since subcategory_id IS the facility_id)
                            this.subcategoryToFacilityMap[subId] = subId;

                            // Add to category mapping
                            this.categoryToFacilityMap[categoryId].push(subId);
                        });
                    }
                });

                console.log(
                    "Facility categories loaded:",
                    this.allFacilityCategories,
                );
                console.log(
                    "Category to facility mapping:",
                    this.categoryToFacilityMap,
                );
                console.log(
                    "Subcategory to facility mapping:",
                    this.subcategoryToFacilityMap,
                );
            } else {
                console.warn("No facility categories found");
                this.allFacilityCategories = [];
                this.categoryToFacilityMap = {};
                this.subcategoryToFacilityMap = {};
            }

            this.loadingStates.facilitiesLoaded = true;
        } catch (error) {
            console.error("Error loading facility categories:", error);
            this.allFacilityCategories = [];
            this.categoryToFacilityMap = {};
            this.subcategoryToFacilityMap = {};
            this.loadingStates.facilitiesLoaded = true;
        }
    }
hideLoadingOverlay() {
    if (typeof window.hideCalendarLoadingOverlay === 'function') {
        window.hideCalendarLoadingOverlay();
    } else {
        console.log("Manually hiding overlay");
        const miniOverlay = document.getElementById("miniCalendarLoadingOverlay");
        const fullOverlay = document.getElementById("fullCalendarLoadingOverlay");
        
        if (miniOverlay) {
            miniOverlay.style.opacity = "0";
            setTimeout(() => {
                miniOverlay.classList.add("d-none");
                miniOverlay.style.display = "none";
            }, 300);
        }
        
        if (fullOverlay) {
            fullOverlay.style.opacity = "0";
            setTimeout(() => {
                fullOverlay.classList.add("d-none");
                fullOverlay.style.display = "none";
            }, 300);
        }
    }
}
    async loadCalendarEvents() {
        try {
            console.log("Loading calendar events...");

            const headers = {};
            if (this.config.isAdmin && this.config.adminToken) {
                headers["Authorization"] = `Bearer ${this.config.adminToken}`;
            }

            const response = await fetch(
                `${this.config.calendarEventsEndpoint}`,
                { headers },
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to fetch calendar events: ${response.status}`,
                );
            }

            const result = await response.json();

            console.log("Calendar events API response:", result);

            let calendarEvents = [];

            // Get events array
            let events = [];
            if (result.success && Array.isArray(result.data)) {
                events = result.data;
            } else if (Array.isArray(result)) {
                events = result;
            }

            // FIX: Use the transformCalendarEvents method instead of inline transformation
            calendarEvents = this.transformCalendarEvents(events);

            console.log(`Transformed ${calendarEvents.length} calendar events`);

            // Store separately for reference
            this.calendarEvents = calendarEvents;

            // Add calendar events to allEvents
            this.allEvents = [...this.allEvents, ...calendarEvents];
        } catch (error) {
            console.warn("Error loading calendar events:", error);
            this.calendarEvents = [];
        }
    }

transformCalendarEvents(eventsData) {
    console.log('Transforming calendar events. Input data:', eventsData);
    console.log('Number of events to transform:', eventsData.length);
    
    const transformed = eventsData
        .filter((event) => {
            if (event == null || !event.event_id) {
                console.warn('Filtering out invalid event:', event);
                return false;
            }
            return true;
        })
        .map((event) => {
            try {
                console.log('Processing event ID:', event.event_id, 'Name:', event.event_name);
                console.log('Full event object:', JSON.stringify(event, null, 2));
                
                // Check if the event has a schedule object (new format from your API)
                if (event.schedule) {
                    console.log('Event has schedule object:', event.schedule);
                    
                    const schedule = event.schedule;
                    const startDate = schedule.start_date;
                    const endDate = schedule.end_date;
                    const isAllDay = schedule.all_day || false;
                    
                    console.log('Schedule details:', {
                        startDate,
                        endDate,
                        isAllDay,
                        startTime: schedule.start_time,
                        endTime: schedule.end_time
                    });
                    
                    if (isAllDay) {
                        // For all-day events - FullCalendar expects end date to be exclusive
                        const endDateObj = new Date(endDate);
                        endDateObj.setDate(endDateObj.getDate() + 1);
                        const nextDay = endDateObj.toISOString().split('T')[0];
                        
                        const transformedEvent = {
                            id: `calendar_event_${event.event_id}`,
                            title: event.event_name || "Unnamed Event",
                            start: startDate,
                            end: nextDay,
                            allDay: true,
                            color: event.color || "#28a745",
                            backgroundColor: event.color || "#28a745",
                            borderColor: event.color || "#28a745",
                            extendedProps: {
                                eventType: "calendar_event",
                                description: event.description || "",
                                event_id: event.event_id,
                                event_name: event.event_name || "Unnamed Event",
                                start_date: startDate,
                                end_date: endDate,
                                start_time: schedule.start_time || "00:00:00",
                                end_time: schedule.end_time || "23:59:59",
                                all_day: true,
                                display_name: event.display_name || "Calendar Event"
                            }
                        };
                        console.log('Transformed all-day event:', transformedEvent);
                        return transformedEvent;
                    } else {
                        // For timed events
                        let startTime = schedule.start_time || "09:00:00";
                        let endTime = schedule.end_time || "17:00:00";
                        
                        // Ensure times are in HH:MM:SS format
                        if (startTime && startTime.length === 5) startTime = `${startTime}:00`;
                        if (endTime && endTime.length === 5) endTime = `${endTime}:00`;
                        
                        const startDateTime = `${startDate}T${startTime}`;
                        const endDateTime = `${endDate}T${endTime}`;
                        
                        console.log('Timed event datetime:', { startDateTime, endDateTime });
                        
                        // Parse the dates to verify they're valid
                        const startParsed = new Date(startDateTime);
                        const endParsed = new Date(endDateTime);
                        console.log('Parsed dates:', {
                            start: startParsed.toString(),
                            end: endParsed.toString(),
                            startValid: !isNaN(startParsed.getTime()),
                            endValid: !isNaN(endParsed.getTime())
                        });
                        
                        const transformedEvent = {
                            id: `calendar_event_${event.event_id}`,
                            title: event.event_name || "Unnamed Event",
                            start: startDateTime,
                            end: endDateTime,
                            allDay: false,
                            color: event.color || "#28a745",
                            backgroundColor: event.color || "#28a745",
                            borderColor: event.color || "#28a745",
                            extendedProps: {
                                eventType: "calendar_event",
                                description: event.description || "",
                                event_id: event.event_id,
                                event_name: event.event_name || "Unnamed Event",
                                start_date: startDate,
                                start_time: startTime,
                                end_date: endDate,
                                end_time: endTime,
                                all_day: false,
                                display_name: event.display_name || "Calendar Event"
                            }
                        };
                        console.log('Transformed timed event:', transformedEvent);
                        return transformedEvent;
                    }
                } else {
                    // Old format (fallback)
                    console.log('Processing event in old format:', event);
                    
                    const startDate = event.start_date?.split("T")[0] || event.start_date;
                    const endDate = event.end_date?.split("T")[0] || event.end_date;
                    
                    if (!startDate || !endDate) {
                        console.warn("Event missing dates:", event);
                        return null;
                    }
                    
                    const isAllDay = event.all_day === true || event.all_day === 1 || event.all_day === "1";
                    
                    if (isAllDay) {
                        const endDateObj = new Date(endDate + "T12:00:00");
                        endDateObj.setDate(endDateObj.getDate() + 1);
                        
                        return {
                            id: `calendar_event_${event.event_id}`,
                            title: event.event_name || "Unnamed Event",
                            start: startDate,
                            end: endDateObj.toISOString().split("T")[0],
                            allDay: true,
                            color: event.color || "#28a745",
                            backgroundColor: event.color || "#28a745",
                            borderColor: event.color || "#28a745",
                            extendedProps: {
                                eventType: "calendar_event",
                                description: event.description || "",
                                event_id: event.event_id,
                                event_name: event.event_name || "Unnamed Event",
                                start_date: startDate,
                                end_date: endDate,
                                all_day: true,
                            },
                        };
                    } else {
                        let startTime = event.start_time || "09:00:00";
                        let endTime = event.end_time || "17:00:00";
                        
                        if (startTime.length === 5) startTime = `${startTime}:00`;
                        if (endTime.length === 5) endTime = `${endTime}:00`;
                        
                        const startDateTime = `${startDate}T${startTime}`;
                        const endDateTime = `${endDate}T${endTime}`;
                        
                        return {
                            id: `calendar_event_${event.event_id}`,
                            title: event.event_name || "Unnamed Event",
                            start: startDateTime,
                            end: endDateTime,
                            allDay: false,
                            color: event.color || "#28a745",
                            backgroundColor: event.color || "#28a745",
                            borderColor: event.color || "#28a745",
                            extendedProps: {
                                eventType: "calendar_event",
                                description: event.description || "",
                                event_id: event.event_id,
                                event_name: event.event_name || "Unnamed Event",
                                start_date: startDate,
                                start_time: startTime,
                                end_date: endDate,
                                end_time: endTime,
                                all_day: false,
                            },
                        };
                    }
                }
            } catch (error) {
                console.error("Error transforming calendar event:", error, event);
                return null;
            }
        })
        .filter((event) => event != null);
    
    console.log('Transformation complete. Total transformed events:', transformed.length);
    console.log('First few transformed events:', transformed.slice(0, 3));
    
    return transformed;
}
    updateLoadingState(isLoading) {
        if (isLoading) {
            document.body.classList.add("loading");
        } else {
            document.body.classList.remove("loading");
            // Also ensure all skeleton containers are hidden
            document
                .querySelectorAll(".skeleton-container")
                .forEach((container) => {
                    container.style.display = "none";
                });
            // Ensure all calendar content is visible
            document
                .querySelectorAll(".calendar-content")
                .forEach((content) => {
                    content.style.display = "";
                    content.style.opacity = "1";
                    content.style.visibility = "visible";
                });
        }
    }

    async loadFacilities() {
        try {
            const endpoint = this.config.isAdmin
                ? "/api/facilities"
                : "/api/public/facilities";
            const response = await fetch(endpoint);
            const result = await response.json();
            this.allFacilities = result.data || result;
            this.renderFacilityFilters();
        } catch (error) {
            console.error("Error loading facilities:", error);
        }
    }

    // Initialize main calendar
    initializeFullCalendar() {
        const calendarEl = document.getElementById(this.config.containerId);
        if (!calendarEl) return;

        // Make sure container has proper height
        calendarEl.style.height = "450px";
        calendarEl.style.minHeight = "450px";
        calendarEl.style.width = "100%";

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "timeGridWeek",
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            buttonText: {
                today: "Today",
                month: "Month",
                week: "Week",
                day: "Day",
            },
            titleFormat: {
                year: "numeric",
                month: "short",
            },
            height: 450,
            contentHeight: 450,
            aspectRatio: 1.5,
            handleWindowResize: true,
            windowResizeDelay: 200,
            expandRows: true,
            events: this.filteredEvents,

            // Important: Enable all-day slot in week/day views
            allDaySlot: true,
            allDayText: "All Day",

            // Time format settings
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
                meridiem: true,
            },
            slotLabelFormat: {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            },
            dayHeaderFormat: {
                weekday: "long",
                month: "short",
                day: "numeric",
                omitCommas: false,
            },
            displayEventEnd: true,

            // Add loading callback
loading: (isLoading) => {
    console.log("FullCalendar loading:", isLoading);
    if (!isLoading) {
        // Wait a bit for the calendar to fully render
        setTimeout(() => {
            const hasEvents = calendarEl.querySelector(".fc-event") !== null;
            const hasDays = calendarEl.querySelector(".fc-daygrid-day") !== null;

            if (hasDays) {
                this.loadingStates.fullCalendarRendered = true;
                this.checkAllLoaded();
            } else {
                // If no days yet, check again in a moment
                setTimeout(() => {
                    if (calendarEl.querySelector(".fc-daygrid-day")) {
                        this.loadingStates.fullCalendarRendered = true;
                        this.checkAllLoaded();
                    }
                }, 500);
            }
        }, 300);
    }
},

            // === UPDATED: Custom event rendering ===
            eventContent: (arg) => {
                const arrayOfDomNodes = [];
                const event = arg.event;
                const eventType =
                    event.extendedProps?.eventType || "requisition";
                const isAllDay = event.allDay || false;

                // Create container div
                const container = document.createElement("div");
                container.style.display = "flex";
                container.style.flexDirection = "column";
                container.style.gap = "2px";
                container.style.width = "100%";
                container.style.padding = "2px";

                // Format time display based on view and event type
                if (!isAllDay) {
                    // For timed events - show start and end time
                    const timeContainer = document.createElement("div");
                    timeContainer.style.display = "flex";
                    timeContainer.style.flexDirection = "column";
                    timeContainer.style.gap = "1px";
                    timeContainer.style.fontSize = "0.75em";
                    timeContainer.style.opacity = "0.9";
                    timeContainer.style.fontWeight = "500";

                    if (event.start && event.end) {
                        const startTime = event.start.toLocaleTimeString([], {
                            hour: "numeric",
                            minute: "2-digit",
                            hour12: true,
                        });

                        const endTime = event.end.toLocaleTimeString([], {
                            hour: "numeric",
                            minute: "2-digit",
                            hour12: true,
                        });

                        const timeText = document.createElement("div");
                        timeText.innerText = `${startTime} - ${endTime}`;
                        timeContainer.appendChild(timeText);
                    }
                    container.appendChild(timeContainer);
                } else if (arg.view.type !== "dayGridMonth" && isAllDay) {
                    // For all-day events in week/day views - show just the time range (7am - 8am format)
                    const timeContainer = document.createElement("div");
                    timeContainer.style.display = "flex";
                    timeContainer.style.flexDirection = "column";
                    timeContainer.style.gap = "1px";
                    timeContainer.style.fontSize = "0.75em";
                    timeContainer.style.opacity = "0.9";
                    timeContainer.style.fontWeight = "500";

                    // Get times from extendedProps
                    const startTime =
                        event.extendedProps?.start_time || "00:00:00";
                    const endTime = event.extendedProps?.end_time || "00:00:00";

                    // Format times (remove seconds)
                    const formattedStartTime = startTime
                        .split(":")
                        .slice(0, 2)
                        .join(":");
                    const formattedEndTime = endTime
                        .split(":")
                        .slice(0, 2)
                        .join(":");

                    // Convert to 12-hour format
                    const startTimeObj = new Date(
                        `2000-01-01T${formattedStartTime}`,
                    );
                    const endTimeObj = new Date(
                        `2000-01-01T${formattedEndTime}`,
                    );

                    const startTimeStr = startTimeObj.toLocaleTimeString([], {
                        hour: "numeric",
                        minute: "2-digit",
                        hour12: true,
                    });

                    const endTimeStr = endTimeObj.toLocaleTimeString([], {
                        hour: "numeric",
                        minute: "2-digit",
                        hour12: true,
                    });

                    const timeText = document.createElement("div");
                    timeText.innerText = `${startTimeStr} - ${endTimeStr}`;
                    timeContainer.appendChild(timeText);
                    container.appendChild(timeContainer);
                }

                // Title container
                const titleContainer = document.createElement("div");
                titleContainer.style.fontSize = "0.85em";
                titleContainer.style.fontWeight = "bold";
                titleContainer.style.whiteSpace = "normal";
                titleContainer.style.wordWrap = "break-word";
                titleContainer.style.lineHeight = "1.2";
                titleContainer.style.marginBottom = "2px";

                if (event.title) {
                    titleContainer.innerText = event.title;
                    container.appendChild(titleContainer);
                }

                // Add requested facilities (for requisition events only)
                if (
                    eventType === "requisition" &&
                    event.extendedProps?.facilities &&
                    event.extendedProps.facilities.length > 0
                ) {
                    const facilities = event.extendedProps.facilities;
                    const facilityNames = facilities.map((f) => f.name);

                    const facilityContainer = document.createElement("div");
                    facilityContainer.style.fontSize = "0.7em";
                    facilityContainer.style.opacity = "0.8";
                    facilityContainer.style.whiteSpace = "normal";
                    facilityContainer.style.wordWrap = "break-word";
                    facilityContainer.style.lineHeight = "1.2";

                    // Show first facility, then add ', ...' if there are more
                    if (facilityNames.length === 1) {
                        facilityContainer.innerText = facilityNames[0];
                    } else {
                        facilityContainer.innerText = `${facilityNames[0]}, ...`;
                    }

                    container.appendChild(facilityContainer);
                }

                arrayOfDomNodes.push(container);
                return { domNodes: arrayOfDomNodes };
            },

            // View configurations
            views: {
                dayGridMonth: {
                    dayHeaderFormat: { weekday: "short" },
                    moreLinkClick: (arg) => {
                        const date = arg.date;
                        this.calendar.changeView("timeGridDay", date);
                        return false;
                    },
                    dayMaxEvents: 2,
                    moreLinkText: (num) => `+${num} more`,
                    moreLinkClassNames: ["fc-more-link-custom"],
                    eventContent: (arg) => {
                        const arrayOfDomNodes = [];
                        const event = arg.event;
                        const eventType =
                            event.extendedProps?.eventType || "requisition";
                        const isAllDay = event.allDay || false;

                        const container = document.createElement("div");
                        container.style.display = "flex";
                        container.style.flexDirection = "column";
                        container.style.gap = "1px";
                        container.style.width = "100%";
                        container.style.padding = "2px";

                        // Show time in month view
                        if (!isAllDay && event.start && event.end) {
                            const timeContainer = document.createElement("div");
                            timeContainer.style.fontSize = "0.7em";
                            timeContainer.style.opacity = "0.8";
                            timeContainer.style.fontWeight = "500";

                            const startTime = event.start.toLocaleTimeString(
                                [],
                                {
                                    hour: "numeric",
                                    minute: "2-digit",
                                    hour12: true,
                                },
                            );

                            const endTime = event.end.toLocaleTimeString([], {
                                hour: "numeric",
                                minute: "2-digit",
                                hour12: true,
                            });

                            timeContainer.innerText = `${startTime} - ${endTime}`;
                            container.appendChild(timeContainer);
                        } else if (isAllDay) {
                            // For all-day events in month view, show times from extendedProps
                            const startTime =
                                event.extendedProps?.start_time || "00:00:00";
                            const endTime =
                                event.extendedProps?.end_time || "00:00:00";

                            if (
                                startTime !== "00:00:00" ||
                                endTime !== "00:00:00"
                            ) {
                                const timeContainer =
                                    document.createElement("div");
                                timeContainer.style.fontSize = "0.7em";
                                timeContainer.style.opacity = "0.8";
                                timeContainer.style.fontWeight = "500";

                                // Format times
                                const formattedStartTime = startTime
                                    .split(":")
                                    .slice(0, 2)
                                    .join(":");
                                const formattedEndTime = endTime
                                    .split(":")
                                    .slice(0, 2)
                                    .join(":");

                                const startTimeObj = new Date(
                                    `2000-01-01T${formattedStartTime}`,
                                );
                                const endTimeObj = new Date(
                                    `2000-01-01T${formattedEndTime}`,
                                );

                                const startTimeStr =
                                    startTimeObj.toLocaleTimeString([], {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    });

                                const endTimeStr =
                                    endTimeObj.toLocaleTimeString([], {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    });

                                timeContainer.innerText = `${startTimeStr} - ${endTimeStr}`;
                                container.appendChild(timeContainer);
                            }
                        }

                        // Title
                        const titleEl = document.createElement("div");
                        titleEl.classList.add("fc-event-title");
                        titleEl.style.whiteSpace = "normal";
                        titleEl.style.wordWrap = "break-word";
                        titleEl.style.fontSize = "0.85em";
                        titleEl.style.lineHeight = "1.2";
                        titleEl.style.fontWeight = "600";

                        if (event.title) {
                            titleEl.innerText = event.title;
                        }
                        container.appendChild(titleEl);

                        // Add requested facilities (for requisition events only)
                        if (
                            eventType === "requisition" &&
                            event.extendedProps?.facilities &&
                            event.extendedProps.facilities.length > 0
                        ) {
                            const facilities = event.extendedProps.facilities;
                            const facilityNames = facilities.map((f) => f.name);

                            const facilityContainer =
                                document.createElement("div");
                            facilityContainer.style.fontSize = "0.65em";
                            facilityContainer.style.opacity = "0.7";
                            facilityContainer.style.whiteSpace = "normal";
                            facilityContainer.style.wordWrap = "break-word";
                            facilityContainer.style.lineHeight = "1.2";
                            facilityContainer.style.marginTop = "1px";

                            // Show first facility, then add ', ...' if there are more
                            if (facilityNames.length === 1) {
                                facilityContainer.innerText = facilityNames[0];
                            } else {
                                facilityContainer.innerText = `${facilityNames[0]}, ...`;
                            }

                            container.appendChild(facilityContainer);
                        }

                        arrayOfDomNodes.push(container);
                        return { domNodes: arrayOfDomNodes };
                    },
                },

                timeGridWeek: {
                    titleFormat: { year: "numeric", month: "short" },
                    dayHeaderFormat: {
                        weekday: "short",
                        month: "short",
                        day: "numeric",
                        omitCommas: false,
                    },
                    dayMaxEvents: 3,
                    moreLinkText: (num) => `+${num}`,
                    allDaySlot: true,

                    eventContent: (arg) => {
                        const arrayOfDomNodes = [];
                        const event = arg.event;
                        const eventType =
                            event.extendedProps?.eventType || "requisition";
                        const isAllDay = event.allDay || false;

                        const container = document.createElement("div");
                        container.style.display = "flex";
                        container.style.flexDirection = "column";
                        container.style.gap = "2px";
                        container.style.width = "100%";
                        container.style.height = "100%";
                        container.style.padding = "2px 4px";
                        container.style.overflow = "hidden"; // Prevent overflow
                        container.style.textOverflow = "ellipsis";
                        container.style.boxSizing = "border-box";

                        // Show time
                        if (!isAllDay && event.start && event.end) {
                            const timeEl = document.createElement("div");
                            timeEl.style.fontSize = "0.7em";
                            timeEl.style.opacity = "0.9";
                            timeEl.style.marginBottom = "1px";
                            timeEl.style.fontWeight = "500";
                            timeEl.style.whiteSpace = "nowrap";
                            timeEl.style.overflow = "hidden";
                            timeEl.style.textOverflow = "ellipsis";

                            const startTime = event.start.toLocaleTimeString(
                                [],
                                {
                                    hour: "numeric",
                                    minute: "2-digit",
                                    hour12: true,
                                },
                            );

                            const endTime = event.end.toLocaleTimeString([], {
                                hour: "numeric",
                                minute: "2-digit",
                                hour12: true,
                            });

                            timeEl.innerText = `${startTime} - ${endTime}`;
                            container.appendChild(timeEl);
                        } else if (isAllDay) {
                            // Show times for all-day events
                            const startTime =
                                event.extendedProps?.start_time || "00:00:00";
                            const endTime =
                                event.extendedProps?.end_time || "00:00:00";

                            if (
                                startTime !== "00:00:00" ||
                                endTime !== "00:00:00"
                            ) {
                                const timeEl = document.createElement("div");
                                timeEl.style.fontSize = "0.7em";
                                timeEl.style.opacity = "0.9";
                                timeEl.style.marginBottom = "1px";
                                timeEl.style.fontWeight = "500";
                                timeEl.style.whiteSpace = "nowrap";
                                timeEl.style.overflow = "hidden";
                                timeEl.style.textOverflow = "ellipsis";

                                // Format times
                                const formattedStartTime = startTime
                                    .split(":")
                                    .slice(0, 2)
                                    .join(":");
                                const formattedEndTime = endTime
                                    .split(":")
                                    .slice(0, 2)
                                    .join(":");

                                const startTimeObj = new Date(
                                    `2000-01-01T${formattedStartTime}`,
                                );
                                const endTimeObj = new Date(
                                    `2000-01-01T${formattedEndTime}`,
                                );

                                const startTimeStr =
                                    startTimeObj.toLocaleTimeString([], {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    });

                                const endTimeStr =
                                    endTimeObj.toLocaleTimeString([], {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    });

                                timeEl.innerText = `${startTimeStr} - ${endTimeStr}`;
                                container.appendChild(timeEl);
                            }
                        }

                        // Title
                        const titleEl = document.createElement("div");
                        titleEl.style.fontSize = "0.85em";
                        titleEl.style.fontWeight = "bold";
                        titleEl.style.whiteSpace = "nowrap"; // Prevent wrapping
                        titleEl.style.overflow = "hidden";
                        titleEl.style.textOverflow = "ellipsis";
                        titleEl.style.lineHeight = "1.2";
                        titleEl.style.flexGrow = "1";
                        titleEl.style.width = "100%";

                        if (event.title) {
                            titleEl.innerText = event.title;
                        }
                        container.appendChild(titleEl);

                        // Add requested facilities (for requisition events only)
                        if (
                            eventType === "requisition" &&
                            event.extendedProps?.facilities &&
                            event.extendedProps.facilities.length > 0
                        ) {
                            const facilities = event.extendedProps.facilities;
                            const facilityNames = facilities.map((f) => f.name);

                            const facilityContainer =
                                document.createElement("div");
                            facilityContainer.style.fontSize = "0.65em";
                            facilityContainer.style.opacity = "0.7";
                            facilityContainer.style.whiteSpace = "nowrap";
                            facilityContainer.style.overflow = "hidden";
                            facilityContainer.style.textOverflow = "ellipsis";
                            facilityContainer.style.lineHeight = "1.2";
                            facilityContainer.style.marginTop = "1px";
                            facilityContainer.style.width = "100%";

                            // Show first facility, then add ', ...' if there are more
                            if (facilityNames.length === 1) {
                                facilityContainer.innerText = facilityNames[0];
                            } else {
                                facilityContainer.innerText = `${facilityNames[0]}, ...`;
                            }

                            container.appendChild(facilityContainer);
                        }

                        arrayOfDomNodes.push(container);
                        return { domNodes: arrayOfDomNodes };
                    },
                },

                timeGridDay: {
                    allDaySlot: true,
                    eventContent: (arg) => {
                        const arrayOfDomNodes = [];
                        const event = arg.event;
                        const eventType =
                            event.extendedProps?.eventType || "requisition";
                        const isAllDay = event.allDay || false;

                        // Create container div
                        const container = document.createElement("div");
                        container.style.display = "flex";
                        container.style.flexDirection = "column";
                        container.style.gap = "2px";
                        container.style.width = "100%";
                        container.style.padding = "2px";
                        container.style.overflow = "hidden"; // Prevent overflow
                        container.style.textOverflow = "ellipsis";
                        container.style.boxSizing = "border-box";

                        // Format time display based on view and event type
                        if (!isAllDay) {
                            // For timed events - show start and end time
                            const timeContainer = document.createElement("div");
                            timeContainer.style.display = "flex";
                            timeContainer.style.flexDirection = "column";
                            timeContainer.style.gap = "1px";
                            timeContainer.style.fontSize = "0.75em";
                            timeContainer.style.opacity = "0.9";
                            timeContainer.style.fontWeight = "500";
                            timeContainer.style.whiteSpace = "nowrap";
                            timeContainer.style.overflow = "hidden";
                            timeContainer.style.textOverflow = "ellipsis";

                            if (event.start && event.end) {
                                const startTime =
                                    event.start.toLocaleTimeString([], {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    });

                                const endTime = event.end.toLocaleTimeString(
                                    [],
                                    {
                                        hour: "numeric",
                                        minute: "2-digit",
                                        hour12: true,
                                    },
                                );

                                const timeText = document.createElement("div");
                                timeText.innerText = `${startTime} - ${endTime}`;
                                timeContainer.appendChild(timeText);
                            }
                            container.appendChild(timeContainer);
                        } else if (
                            arg.view.type !== "dayGridMonth" &&
                            isAllDay
                        ) {
                            // For all-day events in week/day views - show just the time range
                            const timeContainer = document.createElement("div");
                            timeContainer.style.display = "flex";
                            timeContainer.style.flexDirection = "column";
                            timeContainer.style.gap = "1px";
                            timeContainer.style.fontSize = "0.75em";
                            timeContainer.style.opacity = "0.9";
                            timeContainer.style.fontWeight = "500";
                            timeContainer.style.whiteSpace = "nowrap";
                            timeContainer.style.overflow = "hidden";
                            timeContainer.style.textOverflow = "ellipsis";

                            // Get times from extendedProps
                            const startTime =
                                event.extendedProps?.start_time || "00:00:00";
                            const endTime =
                                event.extendedProps?.end_time || "00:00:00";

                            // Format times (remove seconds)
                            const formattedStartTime = startTime
                                .split(":")
                                .slice(0, 2)
                                .join(":");
                            const formattedEndTime = endTime
                                .split(":")
                                .slice(0, 2)
                                .join(":");

                            // Convert to 12-hour format
                            const startTimeObj = new Date(
                                `2000-01-01T${formattedStartTime}`,
                            );
                            const endTimeObj = new Date(
                                `2000-01-01T${formattedEndTime}`,
                            );

                            const startTimeStr =
                                startTimeObj.toLocaleTimeString([], {
                                    hour: "numeric",
                                    minute: "2-digit",
                                    hour12: true,
                                });

                            const endTimeStr = endTimeObj.toLocaleTimeString(
                                [],
                                {
                                    hour: "numeric",
                                    minute: "2-digit",
                                    hour12: true,
                                },
                            );

                            const timeText = document.createElement("div");
                            timeText.innerText = `${startTimeStr} - ${endTimeStr}`;
                            timeContainer.appendChild(timeText);
                            container.appendChild(timeContainer);
                        }

                        // Title container
                        const titleContainer = document.createElement("div");
                        titleContainer.style.fontSize = "0.85em";
                        titleContainer.style.fontWeight = "bold";
                        titleContainer.style.whiteSpace = "nowrap";
                        titleContainer.style.overflow = "hidden";
                        titleContainer.style.textOverflow = "ellipsis";
                        titleContainer.style.lineHeight = "1.2";
                        titleContainer.style.marginBottom = "2px";
                        titleContainer.style.width = "100%";

                        if (event.title) {
                            titleContainer.innerText = event.title;
                            container.appendChild(titleContainer);
                        }

                        // Add requested facilities (for requisition events only)
                        if (
                            eventType === "requisition" &&
                            event.extendedProps?.facilities &&
                            event.extendedProps.facilities.length > 0
                        ) {
                            const facilities = event.extendedProps.facilities;
                            const facilityNames = facilities.map((f) => f.name);

                            const facilityContainer =
                                document.createElement("div");
                            facilityContainer.style.fontSize = "0.7em";
                            facilityContainer.style.opacity = "0.8";
                            facilityContainer.style.whiteSpace = "nowrap";
                            facilityContainer.style.overflow = "hidden";
                            facilityContainer.style.textOverflow = "ellipsis";
                            facilityContainer.style.lineHeight = "1.2";
                            facilityContainer.style.width = "100%";

                            // Show first facility, then add ', ...' if there are more
                            if (facilityNames.length === 1) {
                                facilityContainer.innerText = facilityNames[0];
                            } else {
                                facilityContainer.innerText = `${facilityNames[0]}, ...`;
                            }

                            container.appendChild(facilityContainer);
                        }

                        arrayOfDomNodes.push(container);
                        return { domNodes: arrayOfDomNodes };
                    },
                },
            },

            eventClick: (info) => {
                this.showEventModal(info.event);
            },

            eventDidMount: (info) => {
                const event = info.event;
                const eventType =
                    event.extendedProps?.eventType || "requisition";

                if (eventType === "requisition" && event.extendedProps.color) {
                    info.el.style.backgroundColor = event.extendedProps.color;
                    info.el.style.borderColor = event.extendedProps.color;
                    info.el.style.color = "#fff";
                    info.el.style.fontWeight = "bold";
                } else if (eventType === "calendar_event") {
                    info.el.style.backgroundColor = "#28a745";
                    info.el.style.borderColor = "#218838";
                    info.el.style.color = "#fff";
                    info.el.style.fontWeight = "bold";

                    // Add back the subtle stripe pattern for calendar events
                    info.el.style.backgroundImage =
                        "linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent)";
                    info.el.style.backgroundSize = "10px 10px";
                }
            },

            datesSet: (info) => {
                if (this.calendar) {
                    this.calendar.updateSize();
                }
                setTimeout(() => {
                    if (this.calendar) {
                        this.calendar.updateSize();
                    }
                }, 50);

                const currentDate = this.calendar.getDate();
                const calendarMonth = currentDate.getMonth();
                const calendarYear = currentDate.getFullYear();

                const currentMiniMonth = this.currentDate.getMonth();
                const currentMiniYear = this.currentDate.getFullYear();

                if (
                    calendarMonth !== currentMiniMonth ||
                    calendarYear !== currentMiniYear
                ) {
                    this.currentDate = new Date(calendarYear, calendarMonth, 1);
                    this.updateMiniCalendar();
                }
            },

viewDidMount: (info) => {
    setTimeout(() => {
        if (this.calendar) {
            this.calendar.updateSize();
            console.log("Calendar size updated after view mount");
            
            // Mark as rendered if not already
            if (!this.loadingStates.fullCalendarRendered) {
                this.loadingStates.fullCalendarRendered = true;
                this.checkAllLoaded();
            }
        }
    }, 100);
},

            slotMinTime: "00:00:00",
            slotMaxTime: "24:00:00",
            nowIndicator: true,
            navLinks: true,
        });

        this.calendar.render();
        console.log("Calendar rendered");
    }

    initializeMiniCalendar() {
        // Prevent multiple initializations
        if (this.miniCalendarInitialized) {
            console.log("Mini calendar already initialized, skipping...");
            this.loadingStates.miniCalendarRendered = true;
            this.checkAllLoaded();
            return;
        }

        // Set to 1st of current month
        const today = new Date();
        this.currentDate = new Date(today.getFullYear(), today.getMonth(), 1);

        this.updateMiniCalendar();

        // Navigation - remove existing listeners first
        const prevBtn = document.querySelector(".prev-month");
        const nextBtn = document.querySelector(".next-month");

        // Clone buttons to remove existing event listeners
        const newPrevBtn = prevBtn.cloneNode(true);
        const newNextBtn = nextBtn.cloneNode(true);

        prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
        nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);

        // Add fresh event listeners
        newPrevBtn.addEventListener("click", () => {
            this.navigateMonth(-1);
        });

        newNextBtn.addEventListener("click", () => {
            this.navigateMonth(1);
        });

        // Mark as initialized
        this.miniCalendarInitialized = true;
        console.log("Mini calendar initialized");

        // Mark as rendered after a short delay to ensure DOM updates
        setTimeout(() => {
            const daysContainer = document.getElementById(
                this.config.miniCalendarContainerId,
            );
            if (daysContainer && daysContainer.children.length > 0) {
                this.loadingStates.miniCalendarRendered = true;
                this.checkAllLoaded();
            }
        }, 100);
    }

    navigateMonth(delta) {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        // Calculate new month/year
        let newMonth = month + delta;
        let newYear = year;

        // Handle month overflow/underflow
        if (newMonth < 0) {
            newMonth = 11;
            newYear--;
        } else if (newMonth > 11) {
            newMonth = 0;
            newYear++;
        }

        this.currentDate = new Date(newYear, newMonth, 1);
        console.log(`Navigated to: ${this.monthNames[newMonth]} ${newYear}`);
        this.updateMiniCalendar();
    }

updateMiniCalendar() {
    const monthYearElement = document.getElementById(
        this.config.monthYearId,
    );
    const daysContainer = document.getElementById(
        this.config.miniCalendarContainerId,
    );

    if (!monthYearElement || !daysContainer) return;

    // Get current month/year
    const currentMonth = this.currentDate.getMonth();
    const currentYear = this.currentDate.getFullYear();

    monthYearElement.textContent = `${this.monthNames[currentMonth]} ${currentYear}`;
    console.log(
        `Displaying: ${this.monthNames[currentMonth]} ${currentYear}`,
    );

    // Clear container
    daysContainer.innerHTML = "";

    // Add CSS to make the container more compact
    daysContainer.style.display = "grid";
    daysContainer.style.gridTemplateColumns = "repeat(7, 1fr)";
    daysContainer.style.gap = "2px"; // Reduce gap between cells
    daysContainer.style.padding = "2px"; // Reduce padding

    // Today for comparison
    const today = new Date();
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth();
    const todayDay = today.getDate();

    // Get the first day of the month and its day of week (0=Sunday)
    const firstDay = new Date(currentYear, currentMonth, 1);
    const firstDayOfWeek = firstDay.getDay();

    // Calculate starting date (Sunday before the 1st)
    const startDate = new Date(
        currentYear,
        currentMonth,
        1 - firstDayOfWeek,
    );

    // Generate 42 days (6 weeks)
    for (let i = 0; i < 42; i++) {
        // Calculate date for this cell
        const cellDate = new Date(startDate);
        cellDate.setDate(startDate.getDate() + i);

        const day = cellDate.getDate();
        const month = cellDate.getMonth();
        const year = cellDate.getFullYear();

        const dayElement = document.createElement("div");
        dayElement.className = "calendar-day text-center";
        
        // Apply compact styling directly
        dayElement.style.padding = "2px 0"; // Minimal vertical padding
        dayElement.style.fontSize = "0.75rem"; // Smaller font (was 'small' which is ~0.875rem)
        dayElement.style.lineHeight = "1.2"; // Tighter line height
        dayElement.style.cursor = "pointer";
        dayElement.style.width = "100%";
        dayElement.style.boxSizing = "border-box";
        
        // Make the day number container square and compact
        dayElement.style.display = "flex";
        dayElement.style.alignItems = "center";
        dayElement.style.justifyContent = "center";
        dayElement.style.minHeight = "24px"; // Fixed smaller height
        dayElement.style.maxHeight = "24px";
        dayElement.style.height = "24px";
        
        dayElement.textContent = day;

        // Check if this day is in the current month
        const isCurrentMonthDay =
            month === currentMonth && year === currentYear;

        if (!isCurrentMonthDay) {
            // Previous or next month
            dayElement.classList.add("text-muted");
            dayElement.style.opacity = "0.4";
        } else {
            // Current month - check for events
            const hasEvents = this.checkDayHasEvents(cellDate);
            if (hasEvents) {
                dayElement.classList.add("has-events");
                dayElement.title = "Click to view events on this day";
            }

            // Highlight today
            if (
                year === todayYear &&
                month === todayMonth &&
                day === todayDay
            ) {
                dayElement.classList.add("today");
            }
        }

        // Click handler
        dayElement.addEventListener("click", () => {
            this.navigateToDate(new Date(year, month, day));
        });

        daysContainer.appendChild(dayElement);

        
    }

        // Force the entire mini calendar section to be narrower
    const miniCalendarSection = daysContainer.closest('.col-md-3, .col-3, .col-auto, [class*="col-"]');
    if (miniCalendarSection) {
        miniCalendarSection.style.maxWidth = "250px"; // Force narrow width
    }
}

    // === UPDATED: Check day has events for both types ===
    checkDayHasEvents(date) {
        // Format date as YYYY-MM-DD for comparison
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const dateString = `${year}-${month}-${day}`;

        // If no filtered events yet, return false
        if (!this.filteredEvents || !Array.isArray(this.filteredEvents)) {
            return false;
        }

        return this.filteredEvents.some((event) => {
            if (!event || !event.start || !event.end) {
                return false;
            }

            try {
                const eventStart = new Date(event.start);
                const eventEnd = new Date(event.end);

                // Check if dates are valid
                if (isNaN(eventStart.getTime()) || isNaN(eventEnd.getTime())) {
                    console.warn("Invalid date in event:", event);
                    return false;
                }

                const eventStartDate = eventStart.toISOString().split("T")[0];
                const eventEndDate = eventEnd.toISOString().split("T")[0];

                return (
                    dateString >= eventStartDate && dateString <= eventEndDate
                );
            } catch (error) {
                console.warn("Error checking event date:", error, event);
                return false;
            }
        });
    }

    navigateToDate(date) {
        if (this.calendar) {
            this.calendar.gotoDate(date);
            this.calendar.changeView("timeGridDay");
        }
    }

    syncMiniCalendarToToday() {
        const today = new Date();
        this.currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
        this.updateMiniCalendar();
        console.log(
            "Mini calendar synced to today:",
            this.monthNames[this.currentDate.getMonth()],
            this.currentDate.getFullYear(),
        );
    }

    showDayEventsSummary(date, events) {
        const formattedDate = date.toLocaleDateString("en-US", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        });

        const eventsHtml = events
            .map((event) => {
                const eventType =
                    event.extendedProps?.eventType || "requisition";
                const timeStr = event.start
                    ? event.start.toLocaleTimeString([], {
                          hour: "numeric",
                          minute: "2-digit",
                          hour12: true,
                      })
                    : "";

                return `
            <div class="mb-2 p-2 border rounded" style="background-color: ${event.backgroundColor || "#007bff"}20; border-left: 4px solid ${event.backgroundColor || "#007bff"}">
                <div class="d-flex justify-content-between">
                    <strong>${event.title}</strong>
                    ${timeStr ? `<span class="text-muted small">${timeStr}</span>` : ""}
                </div>
                ${
                    event.extendedProps?.eventType === "calendar_event"
                        ? '<span class="badge bg-success mt-1">Calendar Event</span>'
                        : `<span class="badge mt-1" style="background-color: ${event.backgroundColor || "#007bff"}">${event.extendedProps?.status || "Event"}</span>`
                }
            </div>
        `;
            })
            .join("");

        const modalHtml = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Events for ${formattedDate}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                    ${eventsHtml || '<p class="text-muted">No events found</p>'}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;

        this.openGenericModal(modalHtml, "dayEventsModal");
    }

    applyFilters() {
        console.log("=== APPLYING FILTERS ===");
        console.log("Filter mode:", this.filterMode);
        console.log("Selected facility IDs:", this.selectedFacilityIds);
        console.log("Selected category IDs:", this.selectedCategoryIds);
        console.log("Search query:", this.searchQuery);

        if (!this.allEvents || !Array.isArray(this.allEvents)) {
            console.error("allEvents is not an array!");
            return;
        }

        // Filter out undefined/null events first
        const validEvents = this.allEvents.filter((event) => event != null);
        console.log(
            `Valid events to filter: ${validEvents.length}/${this.allEvents.length}`,
        );

        // Get selected statuses from checkboxes
        const selectedStatuses = this.getSelectedStatuses();
        console.log("Selected statuses:", selectedStatuses);

        // Apply status, facility, and search filters
        this.filteredEvents = validEvents.filter((event) => {
            if (!event) return false;

            const eventType =
                event.extendedProps?.eventType ||
                event.eventType ||
                "requisition";

            // Calendar events are always shown (but still searchable)
            if (eventType === "calendar_event") {
                // Apply search filter to calendar events
                if (this.searchQuery) {
                    return this.eventMatchesSearch(event);
                }
                return true;
            }

            // For requisition events, apply all filters
            const eventStatus = event.extendedProps?.status;

            // Check status filter
            if (
                selectedStatuses.length > 0 &&
                !selectedStatuses.includes(eventStatus)
            ) {
                return false;
            }

            // Check search filter
            if (this.searchQuery && !this.eventMatchesSearch(event)) {
                return false;
            }

            // Check facility/category filter based on mode
            if (this.filterMode === "categories") {
                // Category filtering mode
                if (
                    this.selectedCategoryIds &&
                    this.selectedCategoryIds.length > 0
                ) {
                    // Get category and subcategory IDs from the event
                    const eventCategoryIds = (
                        event.extendedProps?.category_ids || []
                    ).map((id) => String(id));
                    const eventSubcategoryIds = (
                        event.extendedProps?.subcategory_ids || []
                    ).map((id) => String(id));

                    // Combine both category and subcategory IDs
                    const allEventCategoryIds = [
                        ...eventCategoryIds,
                        ...eventSubcategoryIds,
                    ];

                    // Also get facility IDs as fallback
                    const eventFacilities =
                        event.extendedProps?.facilities || [];
                    const eventFacilityIds = eventFacilities.map((f) =>
                        String(f.facility_id || f.id),
                    );

                    // Check if any of the selected category/subcategory IDs match
                    const hasMatchingCategory =
                        allEventCategoryIds.some((id) =>
                            this.selectedCategoryIds.includes(id),
                        ) ||
                        eventFacilityIds.some((id) =>
                            this.selectedCategoryIds.includes(id),
                        );

                    if (!hasMatchingCategory) {
                        return false;
                    }
                }
            } else {
                // Individual facility filtering mode
                if (
                    this.selectedFacilityIds &&
                    this.selectedFacilityIds.length > 0
                ) {
                    const eventFacilities =
                        event.extendedProps?.facilities || [];

                    const eventFacilityIds = eventFacilities.map((f) =>
                        String(f.facility_id || f.id),
                    );

                    const hasSelectedFacility = eventFacilityIds.some(
                        (facilityId) =>
                            this.selectedFacilityIds.includes(facilityId),
                    );

                    if (!hasSelectedFacility) {
                        return false;
                    }
                }
            }

            return true;
        });

        console.log(
            `Filtered events: ${this.filteredEvents.length}/${validEvents.length}`,
        );

        // Update calendar display
        this.updateCalendarDisplay();
    }

    // Helper method to check if an event matches search query
    eventMatchesSearch(event) {
        if (!this.searchQuery) return true;

        const query = this.searchQuery.toLowerCase();

        if (event.eventType === "calendar_event") {
            // Search calendar events
            const eventName =
                event.extendedProps?.event_name || event.title || "";
            const description = event.extendedProps?.description || "";
            const eventType = event.extendedProps?.event_type || "";

            return (
                eventName.toLowerCase().includes(query) ||
                description.toLowerCase().includes(query) ||
                eventType.toLowerCase().includes(query)
            );
        } else {
            // Search requisition events
            const title = event.title || "";
            const calendarTitle = event.extendedProps?.calendar_title || "";
            const requester = event.extendedProps?.requester || "";
            const purpose = event.extendedProps?.purpose || "";
            const status = event.extendedProps?.status || "";
            const requestId = String(
                event.extendedProps?.request_id || event.request_id || "",
            );

            // Search in facility names
            const facilities = event.extendedProps?.facilities || [];
            const facilityNames = facilities.map((f) => f.name || "").join(" ");

            return (
                title.toLowerCase().includes(query) ||
                calendarTitle.toLowerCase().includes(query) ||
                requester.toLowerCase().includes(query) ||
                purpose.toLowerCase().includes(query) ||
                status.toLowerCase().includes(query) ||
                requestId.includes(query) ||
                facilityNames.toLowerCase().includes(query)
            );
        }
    }

    clearSearch() {
        this.searchQuery = "";

        // Clear the input field if it exists
        const searchInput = document.getElementById("eventSearchInput");
        if (searchInput) {
            searchInput.value = "";
        }

        this.applyFilters();
    }

    debugFiltering() {
        console.log("=== FILTERING DEBUG ===");
        console.log("Filter mode:", this.filterMode);
        console.log(
            "Selected category IDs (facility IDs):",
            this.selectedCategoryIds,
        );
        console.log("Category to facility map:", this.categoryToFacilityMap);

        if (!this.allEvents) return;

        this.allEvents.forEach((event, index) => {
            if (!event) return;

            const eventFacilities = event.extendedProps?.facilities || [];
            const eventFacilityIds = eventFacilities.map((f) =>
                String(f.facility_id || f.id),
            );

            console.log(`Event ${index}:`, {
                title: event.title,
                facilityIds: eventFacilityIds,
                matches:
                    this.selectedCategoryIds.length > 0
                        ? eventFacilityIds.some((id) =>
                              this.selectedCategoryIds.includes(id),
                          )
                        : true,
            });
        });
    }

    updateCalendarDisplay() {
        // Update calendar with filtered events
        if (this.calendar) {
            this.calendar.removeAllEvents();

            if (this.filteredEvents.length > 0) {
                this.calendar.addEventSource(this.filteredEvents);
            }

            this.calendar.render();
            setTimeout(() => {
                this.calendar.updateSize();
            }, 50);
        }

        // Update mini calendar to reflect event dots
        this.updateMiniCalendar();
    }

    setupFacilityFilterSync() {
        // Listen for changes on facility filter checkboxes
        document.addEventListener("change", (e) => {
            if (e.target.matches(".facility-filter-checkbox")) {
                if (
                    this.filterMode === "categories" &&
                    (e.target.matches(".category-checkbox") ||
                        e.target.matches(".subcategory-checkbox"))
                ) {
                    this.handleCategoryFilterChange();
                } else if (
                    this.filterMode === "facilities" &&
                    e.target.matches(".individual-facility")
                ) {
                    this.handleFacilityFilterChange();
                }
            }
        });

        // Also check initial state
        setTimeout(() => {
            if (this.filterMode === "categories") {
                this.handleCategoryFilterChange();
            } else {
                this.handleFacilityFilterChange();
            }
        }, 500);
    }

    handleFacilityFilterChange() {
        const allFacilitiesCheckbox = document.getElementById(
            "filterAllFacilities",
        );
        const individualCheckboxes = document.querySelectorAll(
            ".individual-facility:not(:disabled)",
        );

        if (allFacilitiesCheckbox?.checked) {
            // "All facilities" is checked - show all events
            this.selectedFacilityIds = [];
            console.log("All facilities checked - showing all events");
        } else {
            // Get selected individual facilities
            this.selectedFacilityIds = Array.from(individualCheckboxes)
                .filter((cb) => cb.checked)
                .map((cb) => cb.value);

            console.log("Selected facility IDs:", this.selectedFacilityIds);
        }

        console.log(
            "Facility filter changed. Selected IDs:",
            this.selectedFacilityIds,
        );
        this.applyFilters();
    }
    handleCategoryFilterChange() {
        const allFacilitiesCheckbox = document.getElementById(
            "filterAllFacilities",
        );
        const categoryCheckboxes = document.querySelectorAll(
            ".category-checkbox:checked",
        );
        const subcategoryCheckboxes = document.querySelectorAll(
            ".subcategory-checkbox:checked",
        );

        if (allFacilitiesCheckbox?.checked) {
            // "All facilities" is checked - show all events
            this.selectedCategoryIds = [];
            console.log("All facilities selected - showing all events");
        } else {
            // Get selected categories and expand them to include all their subcategories
            const selectedCategories = Array.from(categoryCheckboxes).map(
                (cb) => cb.value,
            );
            const selectedSubcategories = Array.from(subcategoryCheckboxes).map(
                (cb) => cb.value,
            );

            // Expand categories to include all their subcategory IDs
            let expandedCategoryIds = [];
            selectedCategories.forEach((catId) => {
                const subIds = this.categoryToFacilityMap[catId] || [];
                expandedCategoryIds = [...expandedCategoryIds, ...subIds];
            });

            // Combine expanded categories with directly selected subcategories
            this.selectedCategoryIds = [
                ...expandedCategoryIds,
                ...selectedSubcategories,
            ];

            console.log(
                "Category filter changed. Selected category IDs (original):",
                selectedCategories,
            );
            console.log("Expanded to facility IDs:", this.selectedCategoryIds);
        }

        this.applyFilters();
    }
    // Helper method to determine if an event matches selected categories
    eventMatchesCategories(event) {
        if (
            !this.selectedCategoryIds ||
            this.selectedCategoryIds.length === 0
        ) {
            return true; // No category filters active
        }

        // Get event's facility categories (you'll need to have this data in your events)
        const eventCategories = event.extendedProps?.facility_categories || [];
        const eventFacilities = event.extendedProps?.facilities || [];

        // Check if any of the event's categories/subcategories match selected ones
        return (
            eventCategories.some((cat) =>
                this.selectedCategoryIds.includes(cat.toString()),
            ) ||
            eventFacilities.some((fac) =>
                this.selectedCategoryIds.includes(fac.facility_id?.toString()),
            )
        );
    }

    debugFacilityFiltering() {
        console.log("=== FACILITY FILTER DEBUG ===");

        if (!this.allEvents || !Array.isArray(this.allEvents)) {
            console.error("allEvents is not an array!");
            return;
        }

        // Filter out undefined events
        const validEvents = this.allEvents.filter((event) => event != null);
        console.log(
            `Valid events: ${validEvents.length}/${this.allEvents.length}`,
        );

        // Check all events for their facility IDs
        validEvents.forEach((event, index) => {
            if (!event) return;

            const facilities = event.extendedProps?.facilities || [];
            const facilityIds = facilities.map((f) =>
                f.facility_id?.toString(),
            );

            console.log(`Event ${index + 1}:`);
            console.log(`  ID: ${event.id || "No ID"}`);
            console.log(`  Title: ${event.title || "No Title"}`);
            console.log(
                `  Status: ${event.extendedProps?.status || "Unknown"}`,
            );
            console.log(`  Facility IDs: ${facilityIds.join(", ") || "None"}`);
            console.log(
                `  Facility Names: ${facilities.map((f) => f.name).join(", ") || "None"}`,
            );
        });

        // Check current filter state
        console.log("Current filter state:");
        console.log("Selected facility IDs:", this.selectedFacilityIds);

        // Check checkbox states
        const allCheckbox = document.getElementById("filterAllFacilities");
        const individualCheckboxes = document.querySelectorAll(
            ".individual-facility:not(:disabled)",
        );
        const checkedIds = Array.from(individualCheckboxes)
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);

        console.log("All facilities checkbox checked:", allCheckbox?.checked);
        console.log("Individual checkboxes checked:", checkedIds.join(", "));
    }
    debugEventStructure() {
        console.log("=== DEBUG: Event Structure Analysis ===");

        if (!this.allEvents || !Array.isArray(this.allEvents)) {
            console.error("allEvents is not an array:", this.allEvents);
            return;
        }

        console.log(`Total events: ${this.allEvents.length}`);

        // Filter out undefined/null events first
        const validEvents = this.allEvents.filter((event) => event != null);
        const invalidEvents = this.allEvents.filter((event) => event == null);

        if (invalidEvents.length > 0) {
            console.error(
                `Found ${invalidEvents.length} invalid/undefined events!`,
            );
            console.error(
                "Invalid events at indices:",
                this.allEvents
                    .map((event, index) => (event == null ? index : -1))
                    .filter((index) => index !== -1),
            );
        }

        if (validEvents.length === 0) {
            console.log("No valid events to analyze");
            return;
        }

        // Analyze first few valid events
        const sampleEvents = validEvents.slice(0, 3);
        sampleEvents.forEach((event, index) => {
            if (!event) return;

            console.log(`Valid Event ${index + 1}:`, {
                id: event.id || "No ID",
                title: event.title || "No Title",
                status: event.extendedProps?.status || "Unknown",
                facilities: event.extendedProps?.facilities || [],
                hasFacilities: event.extendedProps?.facilities?.length > 0,
                facilityIds: event.extendedProps?.facilities?.map(
                    (f) => f.facility_id,
                ),
            });
        });

        // Count events by status
        const statusCount = {};
        validEvents.forEach((event) => {
            if (!event) return;
            const status = event.extendedProps?.status || "Unknown";
            statusCount[status] = (statusCount[status] || 0) + 1;
        });
        console.log("Events by status:", statusCount);

        // Count events with facilities
        const withFacilities = validEvents.filter(
            (e) => e && e.extendedProps?.facilities?.length > 0,
        ).length;
        console.log(
            `Events with facilities: ${withFacilities}/${validEvents.length}`,
        );
    }

    getSelectedStatuses() {
        // Get selected status checkboxes
        const checkboxes = document.querySelectorAll(
            ".event-filter-checkbox:checked",
        );
        return Array.from(checkboxes).map((cb) => cb.value);
    }
    renderFacilityFilters() {
        const facilityFilterList =
            document.getElementById("facilityFilterList");
        if (!facilityFilterList) return;

        facilityFilterList.innerHTML = "";

        // "All Facilities" option
        const allFacilitiesItem = document.createElement("div");
        allFacilitiesItem.className = "facility-item";
        allFacilitiesItem.innerHTML = `
            <div class="form-check">
                <input class="form-check-input facility-filter" type="checkbox" id="allFacilities" value="All" checked>
                <label class="form-check-label" for="allFacilities">All Facilities</label>
            </div>
        `;
        facilityFilterList.appendChild(allFacilitiesItem);

        // Render facility checkboxes
        this.allFacilities.forEach((facility) => {
            const facilityId = facility.facility_id || facility.id;
            const facilityName = facility.facility_name || facility.name;

            if (!facilityId) {
                console.warn("Facility missing ID:", facility);
                return;
            }

            const facilityItem = document.createElement("div");
            facilityItem.className = "facility-item mb-1";
            facilityItem.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input facility-filter" type="checkbox" 
                           id="facility${facilityId}" 
                           value="${facilityId}"
                           data-name="${facilityName}">
                    <label class="form-check-label small" for="facility${facilityId}" 
                           title="${facilityName}">
                        ${
                            facilityName.length > 25
                                ? facilityName.substring(0, 25) + "..."
                                : facilityName
                        }
                    </label>
                </div>
            `;
            facilityFilterList.appendChild(facilityItem);
        });

        this.setupFacilityFilterListeners();
    }

    setupFacilityFilterListeners() {
        const allFacilitiesCheckbox = document.getElementById("allFacilities");
        const facilityCheckboxes = Array.from(
            document.querySelectorAll(".facility-filter"),
        ).filter((cb) => cb.id !== "allFacilities");

        // Initialize selectedFacilityIds
        this.selectedFacilityIds = [];

        // When "All Facilities" is checked/unchecked
        if (allFacilitiesCheckbox) {
            allFacilitiesCheckbox.addEventListener("change", () => {
                if (allFacilitiesCheckbox.checked) {
                    // Uncheck all individual facility checkboxes
                    facilityCheckboxes.forEach((cb) => {
                        cb.checked = false;
                    });
                    this.selectedFacilityIds = [];
                }
                this.applyFilters();
            });
        }

        // When individual facility checkboxes change
        facilityCheckboxes.forEach((cb) => {
            cb.addEventListener("change", () => {
                const facilityId = cb.value;
                const facilityName = cb.dataset.name;

                if (cb.checked) {
                    // Uncheck "All Facilities"
                    if (allFacilitiesCheckbox) {
                        allFacilitiesCheckbox.checked = false;
                    }
                    // Add to selected facilities if not already there
                    if (!this.selectedFacilityIds.includes(facilityId)) {
                        this.selectedFacilityIds.push(facilityId);
                        console.log(
                            `Added facility: ${facilityId} (${facilityName})`,
                        );
                    }
                } else {
                    // Remove from selected facilities
                    this.selectedFacilityIds = this.selectedFacilityIds.filter(
                        (id) => id !== facilityId,
                    );
                    console.log(
                        `Removed facility: ${facilityId} (${facilityName})`,
                    );

                    // If no facilities selected, check "All Facilities"
                    if (
                        this.selectedFacilityIds.length === 0 &&
                        allFacilitiesCheckbox
                    ) {
                        allFacilitiesCheckbox.checked = true;
                    }
                }

                console.log(
                    "Currently selected facility IDs:",
                    this.selectedFacilityIds,
                );
                this.applyFilters();
            });
        });
    }

    setupGlobalEventListeners() {
        // Add event listeners for status filter checkboxes
        document
            .querySelectorAll(".event-filter-checkbox")
            .forEach((checkbox) => {
                checkbox.addEventListener("change", () => {
                    this.applyFilters();
                });
            });

        // Setup search input listener
        const searchInput = document.getElementById("eventSearchInput");
        if (searchInput) {
            // Listen for input events
            searchInput.addEventListener("input", (e) => {
                this.handleSearch(e.target.value);
            });

            // Listen for Enter key (optional - submits immediately)
            searchInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    clearTimeout(this.searchDebounceTimer);
                    this.searchQuery = e.target.value.trim().toLowerCase();
                    this.applyFilters();
                }
            });

            // Add clear button functionality
            const clearButton = document.getElementById("clearSearchBtn");
            if (clearButton) {
                clearButton.addEventListener("click", () => {
                    this.clearSearch();
                });
            }
        }
            this.setupSearch();
    }

    // === UPDATED: Event click handler to show appropriate modal ===
    showEventModal(event) {
        const eventData = event.extendedProps;
        const eventType = eventData.eventType || "requisition";

        console.log("Event clicked:", eventType, eventData);

        if (eventType === "calendar_event") {
            this.showCalendarEventModal(eventData);
        } else {
            this.showRequisitionEventModal(eventData); // This method needs to be defined
        }
    }

    showRequisitionEventModal(eventData) {
        console.log("Showing requisition event modal:", eventData);
        this.openModal(eventData); // Use existing openModal method for requisitions
    }

    // === NEW: Modal for calendar events ===
    showCalendarEventModal(eventData) {
        // Create modal HTML for calendar events
        const modalHtml = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${this.escapeHtml(eventData.event_name || "Calendar Event")}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <p>${this.escapeHtml(eventData.description || "No description provided")}</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Start</label>
                            <p>${this.formatCalendarEventDateTime(eventData.start_date, eventData.start_time)}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">End</label>
                            <p>${this.formatCalendarEventDateTime(eventData.end_date, eventData.end_time)}</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>`;

        this.openGenericModal(modalHtml, "calendarEventModal");
    }

    formatCalendarEventDateTime(dateStr, timeStr) {
        try {
            const date = new Date(dateStr);
            const time = timeStr || "00:00:00";

            // Format date
            const formattedDate = date.toLocaleDateString("en-US", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric",
            });

            // Format time if available and not midnight
            if (time !== "00:00:00" && time !== "00:00") {
                const formattedTime = new Date(
                    `2000-01-01T${time}`,
                ).toLocaleTimeString("en-US", {
                    hour: "numeric",
                    minute: "2-digit",
                    hour12: true,
                });
                return `${formattedDate} at ${formattedTime}`;
            }

            return formattedDate;
        } catch (error) {
            console.error("Error formatting date:", error);
            return `${dateStr} ${timeStr}`;
        }
    }

    openGenericModal(modalHtml, modalId = "genericEventModal") {
        let modalContainer = document.getElementById(modalId);

        if (!modalContainer) {
            modalContainer = document.createElement("div");
            modalContainer.id = modalId;
            modalContainer.className = "modal fade";
            modalContainer.tabIndex = "-1";
            document.body.appendChild(modalContainer);
        }

        modalContainer.innerHTML = modalHtml;
        modalContainer.style.zIndex = "1060";

        const bsModal = new bootstrap.Modal(modalContainer, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });

        bsModal.show();

        // Clean up modal after it's hidden
        modalContainer.addEventListener("hidden.bs.modal", () => {
            setTimeout(() => {
                if (modalContainer && document.body.contains(modalContainer)) {
                    document.body.removeChild(modalContainer);
                }
            }, 300);
        });
    }

    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    openModal(eventData) {
        const isAdmin = this.config.isAdmin;

        // IMPORTANT: Check if we're inside availability modal
        const availabilityModal = document.getElementById(
            "singleFacilityAvailabilityModal",
        );
        if (availabilityModal && availabilityModal.classList.contains("show")) {
            console.log("Skipping event modal - inside availability modal");
            return; // Don't open event modal when inside availability modal
        }

        // Get the modal HTML structure
        const modalHtml = this.getModalHtml(eventData, isAdmin);

        // Get or create modal container (ROOT LEVEL)
        const modalId = this.config.eventModalId;
        let modalContainer = document.getElementById(modalId);

        if (!modalContainer) {
            modalContainer = document.createElement("div");
            modalContainer.id = modalId;
            modalContainer.className = "modal fade";
            modalContainer.tabIndex = "-1";
            document.body.appendChild(modalContainer);
        }

        // Set modal content
        modalContainer.innerHTML = modalHtml;

        // IMPORTANT: Set z-index higher than parent modal
        modalContainer.style.zIndex = "1060"; // Bootstrap's default modal z-index is 1055

        // Store current request ID and original values (for admin only)
        if (isAdmin) {
            this.currentRequestId = eventData.request_id;
            this.originalCalendarTitle = eventData.calendar_title;
            this.originalCalendarDescription = eventData.calendar_description;
        }

        // Populate modal data
        this.populateModalData(eventData, isAdmin, modalContainer);

        // Set up view details button (admin only)
        if (isAdmin) {
            const viewDetailsBtn =
                modalContainer.querySelector("#modalViewDetails");
            if (viewDetailsBtn) {
                viewDetailsBtn.onclick = () => {
                    window.location.href = `/admin/requisition/${eventData.request_id}`;
                };
            }
        }

        // Initialize Bootstrap modal
        const bsModal = new bootstrap.Modal(modalContainer, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });

        // Show the modal
        bsModal.show();
    }

    getModalHtml(eventData, isAdmin) {
        return `
        <div class="modal-dialog" style="max-width: 800px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Calendar Title & Description Section -->
                    <div class="card border-0 shadow-none mb-0 py-1 px-3">
                        <div class="row">
                            <div class="col-12">
                                <!-- Calendar Title -->
                                <div class="mb-2">
                                    <label class="form-label fw-bold d-flex align-items-center mb-2">
                                        Calendar Title
                                        ${
                                            isAdmin
                                                ? `
                                        <i class="bi bi-pencil text-secondary ms-2" id="editCalendarTitleBtn" style="cursor: pointer;"></i>
                                        <div class="edit-actions ms-2 d-none" id="calendarTitleActions">
                                            <button type="button" class="btn btn-sm btn-success me-1" id="saveCalendarTitleBtn">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" id="cancelCalendarTitleBtn">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        `
                                                : ""
                                        }
                                    </label>
                                    <input type="text" class="form-control text-secondary" id="modalCalendarTitle" ${isAdmin ? "" : "readonly"}>
                                </div>

                                <!-- Calendar Description -->
                                <div class="mb-0">
                                    <label class="form-label fw-bold d-flex align-items-center mb-2">
                                        Calendar Description
                                        ${
                                            isAdmin
                                                ? `
                                        <i class="bi bi-pencil text-secondary ms-2" id="editCalendarDescriptionBtn"
                                            style="cursor: pointer;"></i>
                                        <div class="edit-actions ms-2 d-none" id="calendarDescriptionActions">
                                            <button type="button" class="btn btn-sm btn-success me-1" id="saveCalendarDescriptionBtn">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" id="cancelCalendarDescriptionBtn">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        `
                                                : ""
                                        }
                                    </label>
                                    <textarea class="form-control text-secondary" id="modalCalendarDescription" rows="2"
                                        ${isAdmin ? "" : "readonly"}></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-none mb-3 p-3">
                        <table class="table table-bordered mb-0 w-100" style="table-layout: fixed; border: 1px solid #dee2e6;">
                            <thead>
                                <tr>
                                    <th class="bg-light p-2" style="width: 50%; border: 1px solid #dee2e6;">
                                        Event Information
                                    </th>
                                    <th class="bg-light p-2" style="width: 50%; border: 1px solid #dee2e6;">
                                        Requested Items
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid #dee2e6; padding: 0;">
                                        <table class="table mb-0 w-100" style="border-collapse: collapse;">
                                            <tbody>
                                                <tr>
                                                    <th class="bg-light text-nowrap p-2"
                                                        style="width: 40%; border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                        Requester
                                                    </th>
                                                    <td id="modalRequester" class="p-2" style="border-bottom: 1px solid #dee2e6;"></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light text-nowrap p-2"
                                                        style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                        Purpose
                                                    </th>
                                                    <td id="modalPurpose" class="p-2" style="border-bottom: 1px solid #dee2e6;"></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light text-nowrap p-2"
                                                        style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                        Participants
                                                    </th>
                                                    <td id="modalParticipants" class="p-2" style="border-bottom: 1px solid #dee2e6;"></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light text-nowrap p-2"
                                                        style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                        Status
                                                    </th>
                                                    <td id="modalStatus" class="p-2" style="border-bottom: 1px solid #dee2e6;"></td>
                                                </tr>
                                                ${
                                                    isAdmin
                                                        ? `
                                                <tr>
                                                    <th class="bg-light text-nowrap p-2"
                                                        style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                        Approved Fee
                                                    </th>
                                                    <td id="modalFee" class="p-2" style="border-bottom: 1px solid #dee2e6;"></td>
                                                </tr>
                                                `
                                                        : ""
                                                }
                                            </tbody>
                                        </table>
                                    </td>
                                    <td style="border: 1px solid #dee2e6; vertical-align: top; padding: 0;">
                                        <div id="modalItems" class="p-3" style="min-height: 100%;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    ${isAdmin ? '<button type="button" class="btn btn-primary" id="modalViewDetails">View Full Details</button>' : ""}
                </div>
            </div>
        </div>
        `;
    }

    populateModalData(eventData, isAdmin, modalContainer) {
        const formattedRequestId = String(eventData.request_id).padStart(
            4,
            "0",
        );

        // Set modal title
        const modalTitle = modalContainer.querySelector("#eventModalTitle");
        if (modalTitle) {
            modalTitle.textContent = `Request ID #${formattedRequestId} (${eventData.calendar_title || "No Title"})`;
        }

        // Set calendar title and description
        modalContainer.querySelector("#modalCalendarTitle").value =
            eventData.calendar_title || "No Calendar Title";
        modalContainer.querySelector("#modalCalendarDescription").value =
            eventData.calendar_description || "No description";

        // Set other modal content
        modalContainer.querySelector("#modalRequester").textContent =
            eventData.requester || "Unknown";
        modalContainer.querySelector("#modalPurpose").textContent =
            eventData.purpose || "N/A";
        modalContainer.querySelector("#modalParticipants").textContent =
            eventData.num_participants || "0";
        modalContainer.querySelector("#modalStatus").innerHTML = `
            <span class="badge" style="background-color: ${eventData.color || "#007bff"}">
                ${eventData.status || "Unknown"}
            </span>
        `;

        // GET STATUS COLOR FROM STATUSES STORE
        const statusName = eventData.status || "Unknown";
        const statusColor =
            this.statusColors[statusName] || eventData.color || "#007bff";

        modalContainer.querySelector("#modalStatus").innerHTML = `
        <span class="badge" style="background-color: ${statusColor}; color: white;">
            ${statusName}
        </span>
    `;

        // Admin-only fields
        if (isAdmin) {
            modalContainer.querySelector("#modalFee").textContent =
                `₱${eventData.fees?.approved_fee || 0}`;
        }

        // Format requested items
        let itemsHtml = "";

        if (eventData.facilities && eventData.facilities.length > 0) {
            itemsHtml += '<div class="fw-bold small mb-1">Facilities:</div>';
            itemsHtml += eventData.facilities
                .map(
                    (f) =>
                        `<div class="mb-1 small">• ${f.name} | ₱${f.fee}${f.rate_type === "Per Hour" ? "/hour" : "/event"}${f.is_waived ? ' <span class="text-muted">(Waived)</span>' : ""}</div>`,
                )
                .join("");
        }

        if (eventData.equipment && eventData.equipment.length > 0) {
            itemsHtml +=
                '<div class="fw-bold small mt-2 mb-1">Equipment:</div>';
            itemsHtml += eventData.equipment
                .map(
                    (e) =>
                        `<div class="mb-1 small">• ${e.name} × ${e.quantity || 1} | ₱${e.fee}${e.rate_type === "Per Hour" ? "/hour" : "/event"}${e.is_waived ? ' <span class="text-muted">(Waived)</span>' : ""}</div>`,
                )
                .join("");
        }

        modalContainer.querySelector("#modalItems").innerHTML =
            itemsHtml || '<p class="text-muted small">No items requested</p>';
    }

    setupModalEventListeners(modalContainer) {
        const elements = [
            "editCalendarTitleBtn",
            "editCalendarDescriptionBtn",
            "saveCalendarTitleBtn",
            "saveCalendarDescriptionBtn",
            "cancelCalendarTitleBtn",
            "cancelCalendarDescriptionBtn",
        ];

        elements.forEach((id) => {
            const element = modalContainer.querySelector(`#${id}`);
            if (element) {
                const newElement = element.cloneNode(true);
                element.parentNode.replaceChild(newElement, element);
            }
        });

        // Attach fresh event listeners
        modalContainer
            .querySelector("#editCalendarTitleBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.enableEdit("title", modalContainer);
            });

        modalContainer
            .querySelector("#editCalendarDescriptionBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.enableEdit("description", modalContainer);
            });

        modalContainer
            .querySelector("#saveCalendarTitleBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.saveEdit("title", modalContainer);
            });

        modalContainer
            .querySelector("#saveCalendarDescriptionBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.saveEdit("description", modalContainer);
            });

        modalContainer
            .querySelector("#cancelCalendarTitleBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.cancelEdit("title", modalContainer);
            });

        modalContainer
            .querySelector("#cancelCalendarDescriptionBtn")
            ?.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.cancelEdit("description", modalContainer);
            });

        // Reset edit states
        this.resetEditStates(modalContainer);
    }

    resetEditStates(modalContainer) {
        modalContainer.querySelector("#modalCalendarTitle").readOnly = true;
        modalContainer
            .querySelector("#editCalendarTitleBtn")
            ?.classList.remove("d-none");
        modalContainer
            .querySelector("#calendarTitleActions")
            ?.classList.add("d-none");

        modalContainer.querySelector("#modalCalendarDescription").readOnly =
            true;
        modalContainer
            .querySelector("#editCalendarDescriptionBtn")
            ?.classList.remove("d-none");
        modalContainer
            .querySelector("#calendarDescriptionActions")
            ?.classList.add("d-none");
    }

    enableEdit(fieldType, modalContainer) {
        if (fieldType === "title") {
            modalContainer.querySelector("#modalCalendarTitle").readOnly =
                false;
            modalContainer.querySelector("#modalCalendarTitle").focus();
            modalContainer
                .querySelector("#editCalendarTitleBtn")
                .classList.add("d-none");
            modalContainer
                .querySelector("#calendarTitleActions")
                .classList.remove("d-none");
        } else if (fieldType === "description") {
            modalContainer.querySelector("#modalCalendarDescription").readOnly =
                false;
            modalContainer.querySelector("#modalCalendarDescription").focus();
            modalContainer
                .querySelector("#editCalendarDescriptionBtn")
                .classList.add("d-none");
            modalContainer
                .querySelector("#calendarDescriptionActions")
                .classList.remove("d-none");
        }
    }

    cancelEdit(fieldType, modalContainer) {
        if (fieldType === "title") {
            modalContainer.querySelector("#modalCalendarTitle").value =
                this.originalCalendarTitle;
        } else if (fieldType === "description") {
            modalContainer.querySelector("#modalCalendarDescription").value =
                this.originalCalendarDescription;
        }
        this.resetEditStates(modalContainer);
    }

    async saveEdit(fieldType, modalContainer) {
        const newTitle = modalContainer
            .querySelector("#modalCalendarTitle")
            .value.trim();
        const newDescription = modalContainer
            .querySelector("#modalCalendarDescription")
            .value.trim();

        // Validate title if we're saving title
        if (fieldType === "title" && !newTitle) {
            this.showToast("Calendar title cannot be empty", "error");
            return;
        }

        try {
            const response = await fetch(
                `/api/admin/requisition-forms/${this.currentRequestId}/calendar-info`,
                {
                    method: "PUT",
                    headers: {
                        Authorization: `Bearer ${this.config.adminToken}`,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        calendar_title: newTitle,
                        calendar_description: newDescription,
                    }),
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to update calendar info: ${response.status}`,
                );
            }

            const result = await response.json();

            // Update original values
            this.originalCalendarTitle = newTitle;
            this.originalCalendarDescription = newDescription;

            // --- CRITICAL FIX: Update ALL data sources ---

            // 1. Update the local allEvents array (source of truth)
            const eventIndex = this.allEvents.findIndex(
                (e) => e.extendedProps?.request_id === this.currentRequestId,
            );

            if (eventIndex !== -1) {
                // Update the event in the allEvents array
                this.allEvents[eventIndex].title = newTitle;
                this.allEvents[eventIndex].extendedProps.calendar_title =
                    newTitle;
                this.allEvents[eventIndex].extendedProps.calendar_description =
                    newDescription;

                console.log(
                    `Updated event ${this.currentRequestId} in allEvents array`,
                );
            }

            // 2. Update the filteredEvents array
            const filteredIndex = this.filteredEvents.findIndex(
                (e) => e.extendedProps?.request_id === this.currentRequestId,
            );

            if (filteredIndex !== -1) {
                this.filteredEvents[filteredIndex].title = newTitle;
                this.filteredEvents[
                    filteredIndex
                ].extendedProps.calendar_title = newTitle;
                this.filteredEvents[
                    filteredIndex
                ].extendedProps.calendar_description = newDescription;

                console.log(
                    `Updated event ${this.currentRequestId} in filteredEvents array`,
                );
            }

            // 3. Update the FullCalendar instance directly
            if (this.calendar) {
                // Get all events from the calendar
                const calendarEvents = this.calendar.getEvents();

                // Find the specific event by request_id
                const targetEvent = calendarEvents.find(
                    (calEvent) =>
                        calEvent.extendedProps?.request_id ===
                        this.currentRequestId,
                );

                if (targetEvent) {
                    // Update the event title (visible in calendar)
                    targetEvent.setProp("title", newTitle);

                    // Update extended props
                    targetEvent.setExtendedProp("calendar_title", newTitle);
                    targetEvent.setExtendedProp(
                        "calendar_description",
                        newDescription,
                    );

                    console.log(
                        `Updated event ${this.currentRequestId} directly in calendar`,
                    );
                } else {
                    console.warn(
                        `Event ${this.currentRequestId} not found in calendar, will refresh`,
                    );
                    // If we can't find it, force a refresh of filtered events
                    this.updateCalendarDisplay();
                }
            }

            // Update modal title if calendar title was changed
            if (fieldType === "title") {
                modalContainer.querySelector("#eventModalTitle").textContent =
                    `Request ID #${String(this.currentRequestId).padStart(4, "0")} (${newTitle})`;
            }

            this.resetEditStates(modalContainer);
            this.showToast(
                `Calendar ${fieldType} updated successfully`,
                "success",
            );

            // Force calendar to repaint
            setTimeout(() => {
                if (this.calendar) {
                    this.calendar.updateSize();
                    // Force a rerender of events
                    this.calendar.render();
                    console.log("Calendar repainted after update");
                }
            }, 50);
        } catch (error) {
            console.error(`Error updating calendar ${fieldType}:`, error);
            this.showToast(`Failed to update calendar ${fieldType}`, "error");

            // Revert to original values on error
            if (fieldType === "title") {
                modalContainer.querySelector("#modalCalendarTitle").value =
                    this.originalCalendarTitle;
            } else {
                modalContainer.querySelector(
                    "#modalCalendarDescription",
                ).value = this.originalCalendarDescription;
            }
            this.resetEditStates(modalContainer);
        }
    }

    showToast(message, type = "success", duration = 3000) {
        // Implement your toast notification logic here
        console.log(`${type.toUpperCase()}: ${message}`);
    }
}

// Export for use in different contexts
window.CalendarModule = CalendarModule;


// * ======= MOBILE CALENDAR EXTENSION ======= * //


class MobileCalendar extends CalendarModule {
    constructor(config = {}) {
        super(config);
        this.selectedDate = new Date();
        this.mobileCurrentDate = new Date();
    }
    
    initializeMobile() {
        this.setupMobileToggle();
        this.renderMobileCalendar();
        this.setupMobileEventListeners();
        this.setupMobileFilterSync();
    }
    
    setupMobileToggle() {
        const showCalendarBtn = document.getElementById('showMobileCalendarBtn');
        const showEventsBtn = document.getElementById('showMobileEventsBtn');
        const calendarView = document.getElementById('mobileCalendarView');
        const eventsView = document.getElementById('mobileEventsView');
        
        if (showCalendarBtn && showEventsBtn) {
            showCalendarBtn.addEventListener('click', () => {
                showCalendarBtn.classList.add('active');
                showEventsBtn.classList.remove('active');
                calendarView.classList.remove('d-none');
                eventsView.classList.add('d-none');
                this.renderMobileCalendar();
            });
            
            showEventsBtn.addEventListener('click', () => {
                showEventsBtn.classList.add('active');
                showCalendarBtn.classList.remove('active');
                eventsView.classList.remove('d-none');
                calendarView.classList.add('d-none');
                this.loadMobileEventsForDate(this.selectedDate);
            });
        }
    }
    
    renderMobileCalendar() {
        const daysContainer = document.getElementById('mobileCalendarDays');
        const monthYearElement = document.getElementById('mobileMonthYear');
        
        if (!daysContainer || !monthYearElement) return;
        
        const month = this.mobileCurrentDate.getMonth();
        const year = this.mobileCurrentDate.getFullYear();
        
        monthYearElement.textContent = `${this.monthNames[month]} ${year}`;
        
        // Get first day of month (0 = Sunday)
        const firstDay = new Date(year, month, 1).getDay();
        
        // Get last date of month
        const lastDate = new Date(year, month + 1, 0).getDate();
        
        // Get last date of previous month
        const lastDatePrevMonth = new Date(year, month, 0).getDate();
        
        let html = '';
        
        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const date = lastDatePrevMonth - i;
            html += this.renderMobileDay(date, month - 1, year, true);
        }
        
        // Current month days
        for (let date = 1; date <= lastDate; date++) {
            html += this.renderMobileDay(date, month, year, false);
        }
        
        // Next month days
        const remainingCells = 42 - (firstDay + lastDate);
        for (let date = 1; date <= remainingCells; date++) {
            html += this.renderMobileDay(date, month + 1, year, true);
        }
        
        daysContainer.innerHTML = html;
        
        // Add click handlers
        document.querySelectorAll('.mobile-calendar-day').forEach(day => {
            day.addEventListener('click', (e) => {
                const date = new Date(e.currentTarget.dataset.date);
                this.selectMobileDate(date);
            });
        });
    }
    
    renderMobileDay(day, month, year, isOtherMonth) {
        const date = new Date(year, month, day);
        const dateStr = date.toISOString().split('T')[0];
        const today = new Date();
        
        const isToday = date.toDateString() === today.toDateString();
        const isSelected = this.selectedDate && 
                          date.toDateString() === this.selectedDate.toDateString();
        
        // Check if date has events
        const events = this.getEventsForDate(date);
        const calendarEvents = events.filter(e => e.extendedProps?.eventType === 'calendar_event');
        const requisitionEvents = events.filter(e => e.extendedProps?.eventType !== 'calendar_event');
        
        let dotHtml = '';
        if (events.length > 0) {
            dotHtml = '<div class="mobile-event-dot">';
            if (calendarEvents.length > 0) dotHtml += '<span style="background: #28a745;"></span>';
            if (requisitionEvents.length > 0) dotHtml += '<span style="background: #007bff;"></span>';
            dotHtml += '</div>';
        }
        
        const classes = [
            'mobile-calendar-day',
            isOtherMonth ? 'other-month' : '',
            isToday ? 'today' : '',
            isSelected ? 'selected' : ''
        ].filter(Boolean).join(' ');
        
        return `
            <div class="${classes}" data-date="${dateStr}" data-day="${day}" data-month="${month}" data-year="${year}">
                <span>${day}</span>
                ${dotHtml}
            </div>
        `;
    }
    
    selectMobileDate(date) {
        this.selectedDate = date;
        
        // Update selected class
        document.querySelectorAll('.mobile-calendar-day').forEach(day => {
            day.classList.remove('selected');
            if (day.dataset.date === date.toISOString().split('T')[0]) {
                day.classList.add('selected');
            }
        });
        
        // Load events for selected date
        this.loadMobileEventsForDate(date);
        
        // Switch to events view
        document.getElementById('showMobileEventsBtn')?.click();
    }
    
    getEventsForDate(date) {
        if (!this.filteredEvents) return [];
        
        const dateStr = date.toISOString().split('T')[0];
        
        return this.filteredEvents.filter(event => {
            if (!event || !event.start) return false;
            
            const eventStart = new Date(event.start);
            const eventStartStr = eventStart.toISOString().split('T')[0];
            const eventEndStr = event.end ? 
                new Date(event.end).toISOString().split('T')[0] : 
                eventStartStr;
            
            return dateStr >= eventStartStr && dateStr <= eventEndStr;
        });
    }
    
    loadMobileEventsForDate(date) {
        const container = document.getElementById('mobileEventsListContainer');
        const displayElement = document.getElementById('mobileSelectedDateDisplay');
        
        if (!container || !displayElement) return;
        
        displayElement.textContent = date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
        
        const events = this.getEventsForDate(date);
        
        if (events.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-4 text-muted"></i>
                    <p class="mt-2 text-muted">No events scheduled for this date</p>
                </div>
            `;
            return;
        }
        
        // Sort events by time
        events.sort((a, b) => new Date(a.start) - new Date(b.start));
        
        let html = '';
        events.forEach(event => {
            const eventType = event.extendedProps?.eventType || 'requisition';
            const isCalendarEvent = eventType === 'calendar_event';
            
            const startTime = event.start ? new Date(event.start).toLocaleTimeString([], { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            }) : 'All day';
            
            const endTime = event.end ? new Date(event.end).toLocaleTimeString([], { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            }) : '';
            
            const facilities = event.extendedProps?.facilities || [];
            const facilityNames = facilities.map(f => f.name).join(', ');
            
            const eventClass = isCalendarEvent ? 'calendar-event' : 'requisition-event';
            const badgeClass = isCalendarEvent ? 'bg-success' : '';
            const badgeStyle = !isCalendarEvent ? `background-color: ${event.color || '#007bff'};` : '';
            
            html += `
                <div class="card mb-2 mobile-event-item ${eventClass}" 
                     data-event-id="${event.id}"
                     data-event-type="${eventType}"
                     style="cursor: pointer; border-left-color: ${isCalendarEvent ? '#28a745' : (event.color || '#007bff')}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${this.escapeHtml(event.title || 'Untitled')}</h6>
                                <div class="small mb-1">
                                    <span class="badge ${badgeClass}" style="${badgeStyle}">
                                        ${isCalendarEvent ? 'Calendar' : (event.extendedProps?.status || 'Event')}
                                    </span>
                                    <span class="ms-2 text-muted">
                                        <i class="bi bi-clock"></i> ${startTime} ${endTime ? '- ' + endTime : ''}
                                    </span>
                                </div>
                                ${facilityNames ? `
                                    <div class="small text-muted">
                                        <i class="bi bi-building"></i> ${this.escapeHtml(facilityNames)}
                                    </div>
                                ` : ''}
                                ${event.extendedProps?.requester ? `
                                    <div class="small text-muted">
                                        <i class="bi bi-person"></i> ${this.escapeHtml(event.extendedProps.requester)}
                                    </div>
                                ` : ''}
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Add click handlers
        document.querySelectorAll('.mobile-event-item').forEach(item => {
            item.addEventListener('click', () => {
                const eventId = item.dataset.eventId;
                const event = this.filteredEvents.find(e => e.id === eventId);
                if (event) {
                    this.showEventModal({ extendedProps: event.extendedProps, ...event });
                }
            });
        });
    }
    
    setupMobileEventListeners() {
        // Mobile month navigation
        const prevBtn = document.querySelector('.mobile-prev-month');
        const nextBtn = document.querySelector('.mobile-next-month');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                this.mobileCurrentDate.setMonth(this.mobileCurrentDate.getMonth() - 1);
                this.renderMobileCalendar();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                this.mobileCurrentDate.setMonth(this.mobileCurrentDate.getMonth() + 1);
                this.renderMobileCalendar();
            });
        }
    }
    
    setupMobileFilterSync() {
        // Update mobile view when filters change
        const originalApplyFilters = this.applyFilters;
        this.applyFilters = function() {
            originalApplyFilters.call(this);
            if (!document.querySelector('.d-lg-none').classList.contains('d-none')) {
                // We're on mobile
                this.renderMobileCalendar();
                if (this.selectedDate) {
                    this.loadMobileEventsForDate(this.selectedDate);
                }
            }
        }.bind(this);
    }
}

// Override the initialization to include mobile
const originalInitialize = CalendarModule.prototype.initialize;
CalendarModule.prototype.initialize = async function() {
    await originalInitialize.call(this);
    
    // Initialize mobile features
    if (window.innerWidth < 992) {
        this.mobileCalendar = new MobileCalendar(this.config);
        Object.assign(this, this.mobileCalendar);
        this.initializeMobile();
    }
};
