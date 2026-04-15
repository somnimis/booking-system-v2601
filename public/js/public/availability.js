// ============================================
// OPTIMIZED AVAILABILITY MATRIX CONTROLLER
// WITH HIERARCHICAL FACILITY DROPDOWNS
// ============================================

class AvailabilityMatrix {
    constructor() {
        this.currentDate = new Date();
        this.timeRange = '8-17';
        this.facilities = [];
        this.facilityHierarchy = [];
        this.eventsCache = new Map();
        this.selectedFacility = 'all';
        this.searchQuery = '';
        this.isLoading = false;
        this.viewMode = 'flat'; // 'flat' or 'hierarchical'
        
        this.init();
    }

    async init() {
        await this.loadFacilityHierarchy();
        this.renderFacilityFilters();
        await this.loadEventsForCurrentDate();
        this.renderMatrix();
        this.attachEvents();
    }

    async loadFacilityHierarchy() {
        try {
            const response = await fetch('/api/availability/facilities/hierarchy');
            const result = await response.json();
            
            if (result.success) {
                this.facilityHierarchy = result.data.hierarchy;
                // Flatten for flat view
                this.facilities = this.flattenFacilities(this.facilityHierarchy);
            }
        } catch (error) {
            console.error('Error loading facility hierarchy:', error);
            // Fallback to old method
            await this.loadInitialFacilities();
        }
    }

    flattenFacilities(hierarchy) {
        const flat = [];
        for (const parent of hierarchy) {
            flat.push({
                facility_id: parent.facility_id,
                facility_name: parent.facility_name,
                capacity: parent.capacity,
                is_parent: true,
                has_children: parent.children.length > 0
            });
            for (const child of parent.children) {
                flat.push({
                    facility_id: child.facility_id,
                    facility_name: child.facility_name,
                    capacity: child.capacity,
                    is_parent: false,
                    parent_facility_id: child.parent_facility_id,
                    parent_name: parent.facility_name
                });
            }
        }
        return flat;
    }

    async loadInitialFacilities() {
        try {
            const response = await fetch('/api/availability/facilities?per_page=50');
            const result = await response.json();
            if (result.success) {
                this.facilities = result.data;
            }
        } catch (error) {
            console.error('Error loading facilities:', error);
        }
    }

    async loadEventsForCurrentDate() {
        const dateKey = this.formatDate(this.currentDate);
        
        if (this.eventsCache.has(dateKey)) {
            const cached = this.eventsCache.get(dateKey);
            this.requisitions = cached.requisitions;
            this.calendarEvents = cached.calendarEvents;
            return;
        }
        
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams({
                start_date: dateKey,
                end_date: dateKey,
                facility_id: this.selectedFacility !== 'all' ? this.selectedFacility : ''
            });
            
            const response = await fetch(`/api/availability/events?${params}`);
            const result = await response.json();
            
            if (result.success) {
                this.requisitions = result.data.requisitions;
                this.calendarEvents = result.data.calendar_events;
                
                this.eventsCache.set(dateKey, {
                    requisitions: this.requisitions,
                    calendarEvents: this.calendarEvents
                });
                
                this.cleanCache();
            }
        } catch (error) {
            console.error('Error loading events:', error);
            this.showError('Failed to load availability data');
        } finally {
            this.showLoading(false);
        }
    }
    
    cleanCache() {
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        
        for (const [dateKey, _] of this.eventsCache) {
            const cacheDate = new Date(dateKey);
            if (cacheDate < sevenDaysAgo) {
                this.eventsCache.delete(dateKey);
            }
        }
    }

    getTimeSlots() {
        const slots = [];
        const [startHour, endHour] = this.timeRange.split('-').map(Number);
        
        for (let hour = startHour; hour < endHour; hour++) {
            slots.push(`${hour.toString().padStart(2, '0')}:00`);
            slots.push(`${hour.toString().padStart(2, '0')}:30`);
        }
        
        return slots;
    }

    getStatusForTimeSlot(facilityId, timeSlot) {
        const eventAtSlot = this.calendarEvents.find(event => {
            if (event.all_day) return true;
            const eventStart = event.start_time?.substring(0, 5);
            const eventEnd = event.end_time?.substring(0, 5);
            return eventStart <= timeSlot && eventEnd > timeSlot;
        });
        
        if (eventAtSlot) {
            return {
                status: 'event',
                text: '📅 Event',
                event: eventAtSlot,
                tooltip: eventAtSlot.event_name
            };
        }
        
        const requisitionAtSlot = this.requisitions.find(req => {
            const matchesFacility = req.facilities.some(f => 
                String(f.facility_id) === String(facilityId)
            );
            
            if (!matchesFacility) return false;
            if (req.all_day) return true;
            
            const reqStart = req.start_time?.substring(0, 5);
            const reqEnd = req.end_time?.substring(0, 5);
            
            return reqStart <= timeSlot && reqEnd > timeSlot;
        });
        
        if (requisitionAtSlot) {
            const isApproved = requisitionAtSlot.status === 'Scheduled' || requisitionAtSlot.status === 'Ongoing';
            const isPending = ['Pending Approval'].includes(requisitionAtSlot.status);
            
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

    async renderMatrix() {
        const container = document.getElementById('availabilityMatrix');
        const timeSlots = this.getTimeSlots();
        
        let filteredFacilities = [...this.facilities];
        
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            filteredFacilities = filteredFacilities.filter(f =>
                f.facility_name.toLowerCase().includes(query)
            );
        }
        
        if (this.selectedFacility !== 'all') {
            filteredFacilities = filteredFacilities.filter(f =>
                String(f.facility_id) === this.selectedFacility
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
        
        for (const facility of filteredFacilities) {
            const isChild = !facility.is_parent && facility.parent_facility_id;
            const childClass = isChild ? 'child-facility' : '';
            let displayName = facility.facility_name;
            
            if (this.viewMode === 'hierarchical' && isChild) {
                displayName = `↳ ${facility.facility_name}`;
            }
            
            html += `
                <tr>
                    <td class="facility-cell ${childClass}" data-tooltip="${facility.facility_name}">
                        <strong>${this.truncate(displayName, 25)}</strong>
                        ${facility.capacity ? `<small>(${facility.capacity} pax)</small>` : ''}
                    </td>
            `;
            
            for (const slot of timeSlots) {
                const { status, text, event, tooltip } = this.getStatusForTimeSlot(facility.facility_id, slot);
                const eventId = event?.request_id || event?.event_id || '';
                const eventType = event?.request_id ? 'requisition' : 'calendar_event';
                
                html += `
                    <td>
                        <div class="status-card ${status}" 
                             data-status="${status}"
                             data-facility="${facility.facility_id}"
                             data-time="${slot}"
                             data-event-id="${eventId}"
                             data-event-type="${eventType}"
                             data-tooltip="${tooltip}">
                            ${text}
                        </div>
                    </td>
                `;
            }
            
            html += `</tr>`;
        }
        
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
        const facility = this.facilities.find(f => f.facility_id == facilityId);
        
        this.showBookingModal({
            facilityId: facilityId,
            facilityName: facility?.facility_name,
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
            eventData = this.requisitions.find(r => r.request_id == eventId);
        }
        
        if (!eventData) return;
        
        const modalHtml = `
            <div class="modal fade" id="eventModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${eventData.title || eventData.event_name}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${this.renderEventDetails(eventData, eventType)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const existingModal = document.getElementById('eventModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        modal.show();
        
        document.getElementById('eventModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('eventModal').remove();
        });
    }
    
    renderEventDetails(eventData, eventType) {
        if (eventType === 'calendar_event') {
            return `
                <div class="event-detail-row">
                    <div class="event-detail-label">Description</div>
                    <div class="event-detail-value">${eventData.description || 'No description'}</div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Schedule</div>
                    <div class="event-detail-value">${eventData.all_day ? 'All Day' : `${eventData.start_time} - ${eventData.end_time}`}</div>
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
                    <div class="event-detail-value">
                        <span class="badge" style="background-color: ${eventData.status_color}">${eventData.status}</span>
                    </div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Schedule</div>
                    <div class="event-detail-value">${eventData.schedule_display}</div>
                </div>
            `;
        }
    }

    showBookingModal(data) {
        alert(`Booking request for ${data.facilityName}\nDate: ${this.formatDisplayDate(data.date)}\nTime: ${this.formatTime(data.time)}\n\nThis feature will open the requisition form.`);
    }

    renderFacilityFilters() {
        const container = document.getElementById('facilityFilters');
        
        let html = `
            <span class="facility-filter-badge active" data-facility="all" data-is-parent="false">
                All Facilities
            </span>
        `;
        
        // Render parent facilities with dropdowns
        for (const parent of this.facilityHierarchy) {
            const hasChildren = parent.children.length > 0;
            
            if (hasChildren) {
                // Parent with dropdown
                html += `
                    <div class="facility-filter-parent" data-parent-id="${parent.facility_id}">
                        <span class="facility-filter-badge has-children" data-facility="${parent.facility_id}" data-is-parent="true">
                            ${this.truncate(parent.facility_name, 20)} ▼
                        </span>
                        <div class="facility-dropdown" data-parent="${parent.facility_id}">
                            <div class="facility-dropdown-header">${parent.facility_name}</div>
                            <div class="facility-dropdown-item" data-facility="${parent.facility_id}" data-is-child="false">
                                📍 ${this.truncate(parent.facility_name, 30)} <span class="facility-capacity">(${parent.capacity || '?'} pax)</span>
                            </div>
                `;
                
                for (const child of parent.children) {
                    html += `
                        <div class="facility-dropdown-item" data-facility="${child.facility_id}" data-parent-id="${parent.facility_id}" data-is-child="true">
                            ↳ ${this.truncate(child.facility_name, 30)} <span class="facility-capacity">(${child.capacity || '?'} pax)</span>
                        </div>
                    `;
                }
                
                html += `</div></div>`;
            } else {
                // Parent without children (standalone)
                html += `
                    <span class="facility-filter-badge" data-facility="${parent.facility_id}" data-is-parent="true">
                        ${this.truncate(parent.facility_name, 20)}
                    </span>
                `;
            }
        }
        
        container.innerHTML = html;
        this.attachFilterEvents();
    }

    attachFilterEvents() {
        const container = document.getElementById('facilityFilters');
        
        // Handle dropdown toggles
        container.querySelectorAll('.facility-filter-parent').forEach(parentDiv => {
            const badge = parentDiv.querySelector('.facility-filter-badge');
            const dropdown = parentDiv.querySelector('.facility-dropdown');
            let closeTimeout;
            
            // Toggle dropdown on click
            badge.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close all other dropdowns
                document.querySelectorAll('.facility-dropdown.show').forEach(d => {
                    if (d !== dropdown) d.classList.remove('show');
                });
                dropdown.classList.toggle('show');
            });
            
            // Handle dropdown items
            dropdown.querySelectorAll('.facility-dropdown-item').forEach(item => {
                item.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const facilityId = item.dataset.facility;
                    const isChild = item.dataset.isChild === 'true';
                    
                    // Update active state
                    container.querySelectorAll('.facility-filter-badge').forEach(b => {
                        b.classList.remove('active');
                    });
                    
                    // Add active class to parent badge
                    badge.classList.add('active');
                    
                    this.selectedFacility = facilityId;
                    dropdown.classList.remove('show');
                    await this.loadEventsForCurrentDate();
                    this.renderMatrix();
                });
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!parentDiv.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
            
            // Hover delay for better UX
            badge.addEventListener('mouseenter', () => {
                clearTimeout(closeTimeout);
            });
            
            dropdown.addEventListener('mouseenter', () => {
                clearTimeout(closeTimeout);
            });
            
            dropdown.addEventListener('mouseleave', () => {
                closeTimeout = setTimeout(() => {
                    dropdown.classList.remove('show');
                }, 300);
            });
        });
        
        // Handle standalone facility badges (no dropdown)
        container.querySelectorAll('.facility-filter-badge:not(.has-children)').forEach(badge => {
            if (!badge.closest('.facility-filter-parent')) {
                badge.addEventListener('click', async () => {
                    container.querySelectorAll('.facility-filter-badge').forEach(b => {
                        b.classList.remove('active');
                    });
                    badge.classList.add('active');
                    this.selectedFacility = badge.dataset.facility;
                    await this.loadEventsForCurrentDate();
                    this.renderMatrix();
                });
            }
        });
        
        // Handle "All Facilities" separately
        const allFacilitiesBtn = container.querySelector('[data-facility="all"]');
        if (allFacilitiesBtn) {
            allFacilitiesBtn.addEventListener('click', async () => {
                container.querySelectorAll('.facility-filter-badge').forEach(b => {
                    b.classList.remove('active');
                });
                allFacilitiesBtn.classList.add('active');
                this.selectedFacility = 'all';
                await this.loadEventsForCurrentDate();
                this.renderMatrix();
            });
        }
    }

    attachEvents() {
        document.getElementById('prevDayBtn')?.addEventListener('click', async () => {
            this.currentDate.setDate(this.currentDate.getDate() - 1);
            await this.refresh();
        });
        
        document.getElementById('nextDayBtn')?.addEventListener('click', async () => {
            this.currentDate.setDate(this.currentDate.getDate() + 1);
            await this.refresh();
        });
        
        document.getElementById('todayBtn')?.addEventListener('click', async () => {
            this.currentDate = new Date();
            await this.refresh();
        });
        
        this.setupDatePicker();
        
        document.querySelectorAll('.time-range-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                document.querySelectorAll('.time-range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.timeRange = btn.dataset.range;
                this.renderMatrix();
            });
        });
        
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchQuery = e.target.value;
                this.renderMatrix();
            }, 300);
        });
        
        // Clear filters button
        const clearBtn = document.getElementById('clearFiltersBtn');
        clearBtn?.addEventListener('click', async () => {
            this.searchQuery = '';
            this.selectedFacility = 'all';
            if (searchInput) searchInput.value = '';
            
            // Reset active state on filters
            document.querySelectorAll('.facility-filter-badge').forEach(b => {
                b.classList.remove('active');
            });
            const allBtn = document.querySelector('[data-facility="all"]');
            if (allBtn) allBtn.classList.add('active');
            
            // Close all dropdowns
            document.querySelectorAll('.facility-dropdown.show').forEach(d => {
                d.classList.remove('show');
            });
            
            await this.loadEventsForCurrentDate();
            this.renderMatrix();
        });
        
        // View mode toggles
        const flatViewBtn = document.getElementById('flatViewBtn');
        const hierarchicalViewBtn = document.getElementById('hierarchicalViewBtn');
        
        flatViewBtn?.addEventListener('click', async () => {
            this.viewMode = 'flat';
            flatViewBtn.classList.add('active');
            hierarchicalViewBtn?.classList.remove('active');
            this.facilities = this.flattenFacilities(this.facilityHierarchy);
            await this.loadEventsForCurrentDate();
            this.renderMatrix();
        });
        
        hierarchicalViewBtn?.addEventListener('click', async () => {
            this.viewMode = 'hierarchical';
            hierarchicalViewBtn.classList.add('active');
            flatViewBtn?.classList.remove('active');
            this.facilities = this.flattenFacilities(this.facilityHierarchy);
            await this.loadEventsForCurrentDate();
            this.renderMatrix();
        });
    }
    
    setupDatePicker() {
        const datePicker = document.getElementById('datePicker');
        if (!datePicker) return;
        
        datePicker.value = this.formatDate(this.currentDate);
        
        datePicker.addEventListener('change', async (e) => {
            const selectedDate = new Date(e.target.value);
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
        
        const datePicker = document.getElementById('datePicker');
        if (datePicker) {
            datePicker.value = this.formatDate(this.currentDate);
        }
    }

    showLoading(show) {
        const container = document.getElementById('availabilityMatrix');
        if (show && (!container.innerHTML || container.innerHTML.includes('empty-state'))) {
            container.innerHTML = `
                <div class="loading-overlay">
                    <div class="loading-spinner"></div>
                    <p>Loading availability data...</p>
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
        const display = document.getElementById('currentDateDisplay');
        if (display) {
            display.textContent = this.currentDate.toLocaleDateString('en-US', options);
        }
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

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.availabilityMatrix = new AvailabilityMatrix();
});