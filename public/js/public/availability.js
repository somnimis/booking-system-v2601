// ============================================
// HIERARCHICAL AVAILABILITY MATRIX CONTROLLER
// WITH COLLAPSIBLE PARENTS & LAZY LOADING
// ============================================

class AvailabilityMatrix {
    constructor() {
        this.currentDate = new Date();
        this.timeRange = '8-12';
        this.facilityHierarchy = [];
        this.expandedParents = new Set();
        this.eventsCache = new Map();
        this.selectedFacility = 'all';
        this.searchQuery = '';
        this.isLoading = false;
        
        this.init();
    }

    async init() {
        await this.loadFacilityHierarchy();
        this.renderFacilityFilter();
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
            }
        } catch (error) {
            console.error('Error loading facility hierarchy:', error);
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

    /**
     * FIXED: Get time slots that properly span the selected range
     * Now includes 8:00 AM to 11:30 AM for morning (8-12)
     * and 1:00 PM to 4:30 PM for afternoon (13-17)
     */
getTimeSlots() {
    const slots = [];
    const [startHour, endHour] = this.timeRange.split('-').map(Number);
    
    // Include slots up to and including the end hour
    for (let hour = startHour; hour <= endHour; hour++) {
        slots.push(`${hour.toString().padStart(2, '0')}:00`);
        if (hour !== endHour) {
            slots.push(`${hour.toString().padStart(2, '0')}:30`);
        }
    }
    
    return slots;
}

    /**
     * FIXED: Get status for a specific time slot
     */
    getStatusForTimeSlot(facilityId, timeSlot) {
        // Check calendar events first
        const eventAtSlot = this.calendarEvents?.find(event => {
            if (event.all_day) return true;
            const eventStart = event.start_time?.substring(0, 5);
            const eventEnd = event.end_time?.substring(0, 5);
            return eventStart <= timeSlot && eventEnd > timeSlot;
        });
        
if (eventAtSlot) {
    return {
        status: 'event',
        text: `📅 ${eventAtSlot.event_name || 'Event'}`,
        event: eventAtSlot,
        tooltip: eventAtSlot.event_name,
        bookable: false
    };
}
        
        // Check requisitions
        const requisitionAtSlot = this.requisitions?.find(req => {
            const matchesFacility = req.facilities?.some(f => 
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
                    tooltip: requisitionAtSlot.title,
                    bookable: false
                };
            } else if (isPending) {
                return {
                    status: 'pending',
                    text: '🟡 Pending',
                    event: requisitionAtSlot,
                    tooltip: `${requisitionAtSlot.title} (Pending Approval)`,
                    bookable: false
                };
            }
        }
        
        return {
            status: 'available',
            text: '✅ Available',
            event: null,
            tooltip: 'Click to book this slot',
            bookable: true
        };
    }

    async renderMatrix() {
        const container = document.getElementById('availabilityMatrix');
        const timeSlots = this.getTimeSlots();
        
        // Filter hierarchy based on selected facility
        let filteredHierarchy = [...this.facilityHierarchy];
        
        if (this.selectedFacility !== 'all') {
            const isParent = this.facilityHierarchy.some(p => p.facility_id == this.selectedFacility);
            
            if (isParent) {
                filteredHierarchy = this.facilityHierarchy.filter(p => p.facility_id == this.selectedFacility);
            } else {
                filteredHierarchy = this.facilityHierarchy.filter(parent => 
                    parent.children.some(child => child.facility_id == this.selectedFacility)
                ).map(parent => ({
                    ...parent,
                    children: parent.children.filter(child => child.facility_id == this.selectedFacility),
                    childrenLoaded: true
                }));
                if (filteredHierarchy.length > 0) {
                    this.expandedParents.add(filteredHierarchy[0].facility_id);
                }
            }
        }
        
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            filteredHierarchy = filteredHierarchy.filter(parent => {
                const parentMatch = parent.facility_name.toLowerCase().includes(query);
                const childrenMatch = parent.children.some(child => 
                    child.facility_name.toLowerCase().includes(query)
                );
                return parentMatch || childrenMatch;
            }).map(parent => ({
                ...parent,
                children: parent.children.filter(child => 
                    child.facility_name.toLowerCase().includes(query)
                ),
                childrenLoaded: true
            }));
            
            filteredHierarchy.forEach(parent => {
                if (parent.children.length > 0) {
                    this.expandedParents.add(parent.facility_id);
                }
            });
        }
        
        if (filteredHierarchy.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-building"></i>
                    <p>No facilities available</p>
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
        
        for (const parent of filteredHierarchy) {
            const hasChildren = parent.children && parent.children.length > 0;
            const isExpanded = this.expandedParents.has(parent.facility_id);
            const isBookable = !hasChildren;
            
            html += this.renderParentRow(parent, timeSlots, hasChildren, isExpanded, isBookable);
            
            if (hasChildren && isExpanded) {
                for (const child of parent.children) {
                    html += this.renderChildRow(child, timeSlots);
                }
            }
        }
        
        html += `</tbody></table>`;
        container.innerHTML = html;
        
        this.updateDateDisplay();
        this.attachRowClickHandlers();
        this.attachExpandCollapseHandlers();
    }
    
    renderParentRow(parent, timeSlots, hasChildren, isExpanded, isBookable) {
        const expandIcon = hasChildren ? (isExpanded ? '▼' : '▶') : '';
        const expandedAttr = isExpanded ? 'true' : 'false';
        
        let row = `
            <tr class="parent-row" data-parent-id="${parent.facility_id}" data-has-children="${hasChildren}" data-bookable="${isBookable}" data-expanded="${expandedAttr}">
                <td class="facility-cell parent-cell" data-tooltip="${parent.facility_name}">
                    <span class="expand-icon">${expandIcon}</span>
                    <strong>${this.truncate(parent.facility_name, 25)}</strong>
                    ${parent.capacity ? `<small>(${parent.capacity} pax)</small>` : ''}
                    ${hasChildren ? `<small class="child-count">(${parent.children.length} rooms)</small>` : ''}
                </td>
        `;
        
        for (const slot of timeSlots) {
            let statusData;
            
            if (isBookable) {
                statusData = this.getStatusForTimeSlot(parent.facility_id, slot);
            } else {
                statusData = {
                    status: 'parent-placeholder',
                    text: '📁',
                    event: null,
                    tooltip: `Click ▼ to view ${parent.children.length} rooms in ${parent.facility_name}`,
                    bookable: false
                };
            }
            
            const eventId = statusData.event?.request_id || statusData.event?.event_id || '';
            const eventType = statusData.event?.request_id ? 'requisition' : 'calendar_event';
            
            row += `
                <td>
                    <div class="status-card ${statusData.status}" 
                         data-status="${statusData.status}"
                         data-facility="${parent.facility_id}"
                         data-time="${slot}"
                         data-event-id="${eventId}"
                         data-event-type="${eventType}"
                         data-bookable="${statusData.bookable}"
                         data-tooltip="${statusData.tooltip}">
                        ${statusData.text}
                    </div>
                </td>
            `;
        }
        
        row += `</tr>`;
        return row;
    }
    
    renderChildRow(child, timeSlots) {
        let row = `
            <tr class="child-row" data-child-id="${child.facility_id}" data-parent-id="${child.parent_facility_id}">
                <td class="facility-cell child-cell" data-tooltip="${child.facility_name}">
                    <span class="child-indent">↳</span>
                    <strong>${this.truncate(child.facility_name, 25)}</strong>
                    ${child.capacity ? `<small>(${child.capacity} pax)</small>` : ''}
                </td>
        `;
        
        for (const slot of timeSlots) {
            const statusData = this.getStatusForTimeSlot(child.facility_id, slot);
            const eventId = statusData.event?.request_id || statusData.event?.event_id || '';
            const eventType = statusData.event?.request_id ? 'requisition' : 'calendar_event';
            
            row += `
                <td>
                    <div class="status-card ${statusData.status}" 
                         data-status="${statusData.status}"
                         data-facility="${child.facility_id}"
                         data-time="${slot}"
                         data-event-id="${eventId}"
                         data-event-type="${eventType}"
                         data-bookable="true"
                         data-tooltip="${statusData.tooltip}">
                        ${statusData.text}
                    </div>
                </td>
            `;
        }
        
        row += `</tr>`;
        return row;
    }
    
    attachExpandCollapseHandlers() {
        document.querySelectorAll('.parent-row[data-has-children="true"] .facility-cell').forEach(cell => {
            cell.removeEventListener('click', this.handleExpandCollapse);
            cell.addEventListener('click', this.handleExpandCollapse.bind(this));
        });
    }
    
    handleExpandCollapse(e) {
        e.stopPropagation();
        const row = e.currentTarget.closest('.parent-row');
        const parentId = parseInt(row.dataset.parentId);
        
        if (this.expandedParents.has(parentId)) {
            this.expandedParents.delete(parentId);
            row.dataset.expanded = 'false';
        } else {
            this.expandedParents.add(parentId);
            row.dataset.expanded = 'true';
        }
        
        this.renderMatrix();
    }
    
    attachRowClickHandlers() {
        const container = document.getElementById('availabilityMatrix');
        
        container.querySelectorAll('.status-card.available[data-bookable="true"]').forEach(card => {
            card.removeEventListener('click', this.handleSlotClick);
            card.addEventListener('click', this.handleSlotClick.bind(this));
        });
        
        container.querySelectorAll('.status-card.booked, .status-card.pending, .status-card.event, .status-card.parent-placeholder').forEach(card => {
            card.removeEventListener('click', this.handleNonBookableClick);
            card.addEventListener('click', this.handleNonBookableClick.bind(this));
        });
    }

    handleSlotClick(e) {
        e.stopPropagation();
        const card = e.currentTarget;
        const facilityId = card.dataset.facility;
        const timeSlot = card.dataset.time;
        
        let facilityName = '';
        for (const parent of this.facilityHierarchy) {
            if (parent.facility_id == facilityId) {
                facilityName = parent.facility_name;
                break;
            }
            const child = parent.children.find(c => c.facility_id == facilityId);
            if (child) {
                facilityName = child.facility_name;
                break;
            }
        }
        
        this.showBookingModal({
            facilityId: facilityId,
            facilityName: facilityName,
            date: this.currentDate,
            time: timeSlot
        });
    }

    handleNonBookableClick(e) {
        e.stopPropagation();
        const card = e.currentTarget;
        const eventId = card.dataset.eventId;
        const eventType = card.dataset.eventType;
        const status = card.dataset.status;
        
        if (status === 'parent-placeholder') {
            // Trigger expand/collapse for parent
            const row = card.closest('.parent-row');
            const parentId = parseInt(row.dataset.parentId);
            
            if (this.expandedParents.has(parentId)) {
                this.expandedParents.delete(parentId);
            } else {
                this.expandedParents.add(parentId);
            }
            this.renderMatrix();
        } else if (eventId) {
            this.showEventDetails(eventId, eventType);
        }
    }

    async showEventDetails(eventId, eventType) {
        let eventData = null;
        
        if (eventType === 'calendar_event') {
            eventData = this.calendarEvents?.find(e => e.event_id == eventId);
        } else {
            eventData = this.requisitions?.find(r => r.request_id == eventId);
        }
        
        if (!eventData) return;
        
        const modalHtml = `
            <div class="modal fade event-modal" id="eventModal" tabindex="-1">
                <div class="modal-dialog">
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
        
        const existingModal = document.getElementById('eventModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        modal.show();
        
        document.getElementById('eventModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('eventModal')?.remove();
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
                        <span class="badge" style="background-color: ${eventData.status_color || '#6c757d'}">${eventData.status}</span>
                    </div>
                </div>
                <div class="event-detail-row">
                    <div class="event-detail-label">Schedule</div>
                    <div class="event-detail-value">${eventData.schedule_display || eventData.start_time + ' - ' + eventData.end_time}</div>
                </div>
            `;
        }
    }

    showBookingModal(data) {
        const formattedDate = this.formatDisplayDate(data.date);
        const formattedTime = this.formatTime(data.time);
        
        // You can replace this with a proper modal or redirect to requisition form
        const confirmBooking = confirm(
            `Book ${data.facilityName}\n\n` +
            `Date: ${formattedDate}\n` +
            `Time: ${formattedTime}\n\n` +
            `Click OK to proceed with booking request.`
        );
        
        if (confirmBooking) {
            // Redirect to requisition form or open booking modal
            window.location.href = `/requisition/create?facility=${data.facilityId}&date=${this.formatDate(data.date)}&time=${data.time}`;
        }
    }

    renderFacilityFilter() {
        const container = document.getElementById('facilityFilters');
        
        let options = `<option value="all">🏢 All Facilities</option>`;
        
        for (const parent of this.facilityHierarchy) {
            options += `<option value="${parent.facility_id}">${parent.children.length > 0 ? '📁' : '📌'} ${this.truncate(parent.facility_name, 40)}</option>`;
        }
        
        container.innerHTML = `
            <select id="facilitySelect" class="facility-select">
                ${options}
            </select>
        `;
        
        const select = document.getElementById('facilitySelect');
        select.value = this.selectedFacility;
        
        select.addEventListener('change', async (e) => {
            this.selectedFacility = e.target.value;
            await this.loadEventsForCurrentDate();
            this.renderMatrix();
        });
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
        
        const clearBtn = document.getElementById('clearFiltersBtn');
        clearBtn?.addEventListener('click', async () => {
            this.searchQuery = '';
            this.selectedFacility = 'all';
            if (searchInput) searchInput.value = '';
            
            const select = document.getElementById('facilitySelect');
            if (select) select.value = 'all';
            
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
                    <p style="margin-top: 10px;">Loading availability data...</p>
                </div>
            `;
        } else if (!show) {
            const overlay = container.querySelector('.loading-overlay');
            if (overlay) overlay.remove();
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
    const hourNum = parseInt(hour);
    const hour12 = hourNum % 12 || 12;
    const ampm = hourNum >= 12 ? 'pm' : 'am';
    return `${hour12}:${minute}${ampm}`;
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