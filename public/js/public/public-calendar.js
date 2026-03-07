// Consolidated Calendar Functions for Equipment and Facility Catalogs
document.addEventListener('DOMContentLoaded', function() {
// Show event details in modal (Common)
function showEventModal(event) {
  const modalElement = document.getElementById('eventDetailModal');
  if (!modalElement) {
    console.error('Event detail modal not found in DOM');
    return;
  }

  const extendedProps = event.extendedProps;
  const modal = new bootstrap.Modal(modalElement);

  // Format dates and times
  const startDate = event.start.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  const endDate = event.end.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
  const startTime = event.start.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
  const endTime = event.end.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });

  // Set modal content with safety checks
  const elements = {
    eventDetailModalLabel: document.getElementById('eventDetailModalLabel'),
    eventTitle: document.getElementById('eventTitle'),
    eventRequester: document.getElementById('eventRequester'),
    eventPurpose: document.getElementById('eventPurpose'),
    eventParticipants: document.getElementById('eventParticipants'),
    eventStatus: document.getElementById('eventStatus'),
    eventStart: document.getElementById('eventStart'),
    eventEnd: document.getElementById('eventEnd'),
    eventFacilities: document.getElementById('eventFacilities'),
    eventEquipment: document.getElementById('eventEquipment')
  };

  // Check if all elements exist before setting content
  Object.keys(elements).forEach(key => {
    if (!elements[key]) {
      console.error(`Element with id '${key}' not found`);
      return;
    }
  });

  // Now safely set the content
  elements.eventDetailModalLabel.textContent = 'Booking Details';
  elements.eventTitle.textContent = event.title || 'N/A';
  elements.eventRequester.textContent = extendedProps.requester || 'N/A';
  elements.eventPurpose.textContent = extendedProps.purpose || 'N/A';
  elements.eventParticipants.textContent = extendedProps.num_participants || 'N/A';

  // Status badge
  elements.eventStatus.textContent = extendedProps.status || 'N/A';
  elements.eventStatus.style.backgroundColor = event.backgroundColor;
  elements.eventStatus.style.color = '#fff';
  elements.eventStatus.style.padding = '0.25rem 0.5rem';
  elements.eventStatus.style.borderRadius = '0.25rem';

  // Start and end times
  elements.eventStart.textContent = `${startDate} at ${startTime}`;
  elements.eventEnd.textContent = `${endDate} at ${endTime}`;

  // Facilities
  const facilities = extendedProps.facilities || [];
  elements.eventFacilities.textContent = facilities.length > 0
    ? facilities.join(', ')
    : 'None';

  // Equipment
  const equipment = extendedProps.equipment || [];
  elements.eventEquipment.textContent = equipment.length > 0
    ? equipment.join(', ')
    : 'None';

  modal.show();
}

// Initialize availability calendar for specific item (Common) - KEEP ORIGINAL
function initializeAvailabilityCalendar(itemId, itemType, itemName) {
  const calendarEl = document.getElementById('availabilityCalendar');
  if (!calendarEl) return;

  // Clear any existing content first and ensure proper structure
  calendarEl.innerHTML = '<div class="calendar-inner-container" style="height: 100%; position: relative;"></div>';
  const calendarInner = calendarEl.querySelector('.calendar-inner-container');

  // Create and show loading overlay - append to inner container
  const loadingOverlay = document.createElement('div');
  loadingOverlay.className = 'calendar-loading-overlay';
  loadingOverlay.innerHTML = `
    <div class="calendar-loading-content">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading availability data...</p>
    </div>
  `;

  // Add loading overlay to INNER container (not outer)
  calendarInner.appendChild(loadingOverlay);

  // Hide the calendar initially
  calendarInner.classList.add('calendar-hidden');

  const calendar = new FullCalendar.Calendar(calendarInner, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    titleFormat: {
      year: 'numeric',
      month: 'long'
    },
    buttonText: {
      today: 'Today',
      month: 'Month',
      week: 'Week',
      day: 'Day'
    },
    height: '100%',
    handleWindowResize: true,
    windowResizeDelay: 100,
    aspectRatio: 1.5,
    expandRows: true,
    events: function (fetchInfo, successCallback, failureCallback) {
      // Ensure loading overlay is visible and calendar is hidden
      loadingOverlay.style.display = 'flex';
      calendarInner.classList.add('calendar-hidden');
      
      // Fetch events filtered by specific item
      fetch(`/api/requisition-forms/calendar-events?${itemType}_id=${itemId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            successCallback(data.data);
          } else {
            failureCallback(data.message);
          }
        })
        .catch(error => {
          failureCallback('Failed to load availability data');
          console.error('Availability calendar error:', error);
        })
        .finally(() => {
          // Hide loading overlay and show calendar when everything is ready
          setTimeout(() => {
            loadingOverlay.style.display = 'none';
            calendarInner.classList.remove('calendar-hidden');
            calendarInner.classList.add('calendar-visible');
            calendar.updateSize();
          }, 500);
        });
    },
    loading: function(isLoading) {
      if (isLoading) {
        loadingOverlay.style.display = 'flex';
        calendarInner.classList.add('calendar-hidden');
      } else {
        setTimeout(() => {
          loadingOverlay.style.display = 'none';
          calendarInner.classList.remove('calendar-hidden');
          calendarInner.classList.add('calendar-visible');
        }, 500);
      }
    },
    eventClick: function (info) {
      showEventModal(info.event);
    },
    eventDidMount: function (info) {
      info.el.style.backgroundColor = info.event.backgroundColor;
      info.el.style.borderColor = info.event.borderColor;
      info.el.style.color = '#fff';
      info.el.style.fontWeight = 'bold';
      info.el.style.borderRadius = '4px';
      info.el.style.padding = '2px 4px';
      info.el.style.fontSize = '12px';
    },
    datesSet: function (info) {
      setTimeout(() => {
        calendar.updateSize();
      }, 50);
    },
    viewDidMount: function (info) {
      setTimeout(() => {
        calendar.updateSize();
      }, 100);
    },
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    },
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    allDaySlot: false,
    nowIndicator: true,
    navLinks: true,
    dayHeaderFormat: {
      weekday: 'long',
      month: 'short',
      day: 'numeric'
    },
    slotLabelFormat: {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    },
    views: {
      dayGridMonth: {
        dayHeaderFormat: { weekday: 'short' },
        fixedWeekCount: false
      },
      timeGridWeek: {
        dayHeaderFormat: {
          weekday: 'short',
          month: 'short',
          day: 'numeric'
        },
        slotMinTime: '00:00:00',
        slotMaxTime: '24:00:00'
      },
      timeGridDay: {
        dayHeaderFormat: {
          weekday: 'long',
          month: 'short',
          day: 'numeric'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00'
      }
    },
    eventDisplay: 'block',
    dayMaxEvents: 3,
    moreLinkClick: 'popover',
    slotDuration: '01:00:00',
    slotLabelInterval: '01:00:00'
  });

  calendar.render();
  
  // Update modal title with item name
  document.getElementById('availabilityModalLabel').textContent = `Availability - ${itemName}`;

  return calendar;
}

// FullCalendar initialization for user view (Common) - ENHANCED VERSION with proper initialization
function initializeUserCalendar() {
  const calendarEl = document.getElementById('userFullCalendar');
  if (!calendarEl) return;

  let calendarEvents = [];
  let currentDate = new Date();
  let selectedStatuses = ['Pending Approval', 'Awaiting Payment', 'Scheduled', 'Ongoing', 'Late'];
  let selectedFacilityIds = [];
  let calendar = null;
  let miniCalendarInitialized = false;
  let filtersInitialized = false;
  let initializationInProgress = false;
  let allFacilities = []; // Store all facilities for filtering

  // Initialize mini calendar - SAFE VERSION (only once)
  function initializeMiniCalendar() {
    if (miniCalendarInitialized) return;
    
    // Wait for DOM elements to be available
    const checkElements = () => {
      const prevMonthBtn = document.querySelector('.prev-month');
      const nextMonthBtn = document.querySelector('.next-month');
      const monthYearElement = document.getElementById('currentMonthYear');
      const daysContainer = document.getElementById('miniCalendarDays');
      
      if (!prevMonthBtn || !nextMonthBtn || !monthYearElement || !daysContainer) {
        console.log('Mini calendar elements not found yet, retrying...');
        setTimeout(checkElements, 100);
        return;
      }
      
      updateMiniCalendar();

      prevMonthBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateMiniCalendar();
      });

      nextMonthBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateMiniCalendar();
      });
      
      miniCalendarInitialized = true;
      console.log('Mini calendar initialized');
    };
    
    setTimeout(checkElements, 100);
  }

  function updateMiniCalendar() {
    const monthYearElement = document.getElementById('currentMonthYear');
    const daysContainer = document.getElementById('miniCalendarDays');

    if (!monthYearElement || !daysContainer) return;

    // Update month year display
    const monthNames = [
      "January", "February", "March", "April", "May", "June",
      "July", "August", "September", "October", "November", "December"
    ];
    monthYearElement.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;

    // Clear existing days
    daysContainer.innerHTML = '';

    // Get first/last day of current month
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay(); // 0 = Sunday

    // Get previous and next month details
    const prevMonthLastDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0);
    const daysInPrevMonth = prevMonthLastDay.getDate();

    const today = new Date();

    // Add previous month's days
    for (let i = startingDay - 1; i >= 0; i--) {
      const prevDay = daysInPrevMonth - i;
      const prevDayElement = document.createElement('div');
      prevDayElement.className = 'calendar-day text-center flex-fill small text-muted';
      prevDayElement.style.opacity = '0.4';
      prevDayElement.textContent = prevDay;

      // Clicking these jumps to the previous month
      prevDayElement.addEventListener('click', function() {
        const selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, prevDay);
        navigateToDate(selectedDate);
      });

      daysContainer.appendChild(prevDayElement);
    }

    // Add current month's days
    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement('div');
      dayElement.className = 'calendar-day text-center flex-fill small p-1';
      dayElement.style.cursor = 'pointer';

      const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
      const hasEvents = checkDayHasEvents(dayDate);

      if (hasEvents) {
        dayElement.classList.add('has-events');
        dayElement.title = 'Click to view events on this day';
      }

      // Highlight today
      if (
        day === today.getDate() &&
        currentDate.getMonth() === today.getMonth() &&
        currentDate.getFullYear() === today.getFullYear()
      ) {
        dayElement.classList.add('today');
      }

      dayElement.textContent = day;

      // Jump to date when clicked
      dayElement.addEventListener('click', function() {
        const selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
        navigateToDate(selectedDate);
      });

      daysContainer.appendChild(dayElement);
    }

    // Add next month's days to complete the grid (42 cells = 6 weeks)
    const totalCells = daysContainer.children.length;
    const remainingCells = 42 - totalCells;
    for (let i = 1; i <= remainingCells; i++) {
      const nextDayElement = document.createElement('div');
      nextDayElement.className = 'calendar-day text-center flex-fill small text-muted';
      nextDayElement.style.opacity = '0.4';
      nextDayElement.textContent = i;

      // Clicking these jumps to the next month
      nextDayElement.addEventListener('click', function() {
        const selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, i);
        navigateToDate(selectedDate);
      });

      daysContainer.appendChild(nextDayElement);
    }
  }

  // Navigate FullCalendar to specific date
  function navigateToDate(date) {
    if (calendar) {
      calendar.gotoDate(date);
      calendar.changeView('timeGridDay');
    }
  }

  function checkDayHasEvents(date) {
    const dateString = date.toISOString().split('T')[0];
    const filteredEvents = filterEvents();
    
    return filteredEvents.some(event => {
      const eventStart = new Date(event.start);
      const eventEnd = new Date(event.end);
      return date >= eventStart && date <= eventEnd;
    });
  }

  function filterEvents() {
    return calendarEvents.filter(event => {
      // Filter by status
      if (!selectedStatuses.includes(event.extendedProps.status)) {
        return false;
      }
      
      // Filter by facility
      const allFacilitiesCheckbox = document.getElementById('allFacilities');
      
      // Check if "All Facilities" is selected
      if (allFacilitiesCheckbox && allFacilitiesCheckbox.checked) {
        return true; // Show all events when "All Facilities" is checked
      }

      // If specific facilities are selected
      if (selectedFacilityIds.length === 0) {
        return true; // Show all events when no specific facilities selected
      }

      // Check if this event includes any of the selected facilities
      const eventFacilities = event.extendedProps.facilities || [];
      
      // event.extendedProps.facilities might be array of IDs or array of objects
      // Handle both cases
      const hasSelectedFacility = eventFacilities.some(facility => {
        // If facility is an object with facility_id
        if (facility && typeof facility === 'object' && facility.facility_id) {
          return selectedFacilityIds.includes(facility.facility_id.toString());
        }
        // If facility is just an ID
        else if (facility) {
          return selectedFacilityIds.includes(facility.toString());
        }
        return false;
      });

      return hasSelectedFacility;
    });
  }

  // Load facilities for filter - MATCHING ADMIN CALENDAR FORMAT
  async function loadFacilitiesForFilter() {
    if (filtersInitialized) return;
    
    try {
      const response = await fetch('/api/facilities');
      if (response.ok) {
        const data = await response.json();
        allFacilities = data.data || [];
        renderFacilityFilters(allFacilities);
      }
    } catch (error) {
      console.error('Error loading facilities:', error);
    }
  }

  function renderFacilityFilters(facilities) {
    const facilityFilterList = document.getElementById('facilityFilterList');
    if (!facilityFilterList) {
      console.log('facilityFilterList not found, retrying...');
      setTimeout(() => renderFacilityFilters(facilities), 100);
      return;
    }

    facilityFilterList.innerHTML = '';

    // "All Facilities" option - EXACTLY LIKE ADMIN CALENDAR
    const allFacilitiesItem = document.createElement('div');
    allFacilitiesItem.className = 'facility-item';
    allFacilitiesItem.innerHTML = `
      <div class="form-check">
        <input class="form-check-input facility-filter" type="checkbox" id="allFacilities" value="All" checked>
        <label class="form-check-label" for="allFacilities">All Facilities</label>
      </div>
    `;
    facilityFilterList.appendChild(allFacilitiesItem);

    // Render facility checkboxes - WITH PROPER ID FORMAT
    facilities.forEach(facility => {
      // Get the ID - use facility_id from the response
      const facilityId = facility.facility_id || facility.id;
      const facilityName = facility.facility_name || facility.name;

      if (!facilityId) {
        console.warn('Facility missing ID:', facility);
        return;
      }

      const facilityItem = document.createElement('div');
      facilityItem.className = 'facility-item mb-1';
      facilityItem.innerHTML = `
        <div class="form-check">
          <input class="form-check-input facility-filter" type="checkbox" 
                 id="facility${facilityId}" 
                 value="${facilityId}"
                 data-name="${facilityName}">
          <label class="form-check-label small" for="facility${facilityId}" 
                 title="${facilityName}">
            ${facilityName.length > 25 ? 
              facilityName.substring(0, 25) + '...' : 
              facilityName}
          </label>
        </div>
      `;
      facilityFilterList.appendChild(facilityItem);
    });

    setupFacilityFilterListeners();
  }

  function setupFacilityFilterListeners() {
    const allFacilitiesCheckbox = document.getElementById('allFacilities');
    const facilityCheckboxes = Array.from(document.querySelectorAll('.facility-filter')).filter(
      cb => cb.id !== 'allFacilities'
    );

    if (!allFacilitiesCheckbox || facilityCheckboxes.length === 0) {
      console.log('Facility filter elements not ready, retrying...');
      setTimeout(setupFacilityFilterListeners, 100);
      return;
    }

    // Initialize selectedFacilityIds
    selectedFacilityIds = [];

    // When "All Facilities" is checked/unchecked
    if (allFacilitiesCheckbox) {
      allFacilitiesCheckbox.addEventListener('change', function() {
        if (this.checked) {
          // Uncheck all individual facility checkboxes
          facilityCheckboxes.forEach(cb => {
            cb.checked = false;
          });
          selectedFacilityIds = [];
        }
        updateCalendar();
      });
    }

    // When individual facility checkboxes change
    facilityCheckboxes.forEach(cb => {
      cb.addEventListener('change', function() {
        const facilityId = this.value;
        const facilityName = this.dataset.name;

        if (this.checked) {
          // Uncheck "All Facilities"
          if (allFacilitiesCheckbox) {
            allFacilitiesCheckbox.checked = false;
          }
          // Add to selected facilities if not already there
          if (!selectedFacilityIds.includes(facilityId)) {
            selectedFacilityIds.push(facilityId);
            console.log(`Added facility: ${facilityId} (${facilityName})`);
          }
        } else {
          // Remove from selected facilities
          selectedFacilityIds = selectedFacilityIds.filter(id => id !== facilityId);
          console.log(`Removed facility: ${facilityId} (${facilityName})`);

          // If no facilities selected, check "All Facilities"
          if (selectedFacilityIds.length === 0 && allFacilitiesCheckbox) {
            allFacilitiesCheckbox.checked = true;
          }
        }

        console.log('Currently selected facility IDs:', selectedFacilityIds);
        updateCalendar();
      });
    });
    
    filtersInitialized = true;
    console.log('Facility filters initialized');
  }

  // Setup status filter event listeners - SAFE VERSION (only once)
  function setupStatusFilterListeners() {
    if (filtersInitialized) return;
    
    const statusCheckboxes = document.querySelectorAll('.event-filter-checkbox');
    if (statusCheckboxes.length === 0) {
      console.log('Status checkboxes not found, retrying...');
      setTimeout(setupStatusFilterListeners, 100);
      return;
    }
    
    statusCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        selectedStatuses = Array.from(document.querySelectorAll('.event-filter-checkbox:checked'))
          .map(cb => cb.value);
        updateCalendar();
      });
    });
    
    console.log('Status filters initialized');
  }

  // Update calendar with filtered events
  function updateCalendar() {
    if (calendar) {
      calendar.removeAllEvents();
      const filteredEvents = filterEvents();
      console.log(`Filtered events: ${filteredEvents.length} out of ${calendarEvents.length}`);
      console.log('Selected facility IDs:', selectedFacilityIds);
      calendar.addEventSource(filteredEvents);
      if (miniCalendarInitialized) {
        updateMiniCalendar();
      }
    }
  }

  // Initialize the main calendar
  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    titleFormat: {
      year: 'numeric',
      month: 'short'
    },
    height: '100%',
    handleWindowResize: true,
    windowResizeDelay: 100,
    aspectRatio: null,
    expandRows: true,
    
    // Properties for multi-day events
    allDaySlot: true,
    allDayText: 'All Day',
    slotEventOverlap: false,
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    eventDisplay: 'auto',
    
    // Views configuration
    views: {
      timeGridWeek: {
        dayHeaderFormat: {
          weekday: 'short',
          month: 'short',
          day: 'numeric'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        allDayText: 'All Day',
        slotEventOverlap: false
      },
      timeGridDay: {
        dayHeaderFormat: {
          weekday: 'long',
          month: 'short',
          day: 'numeric'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: true,
        allDayText: 'All Day'
      },
      dayGridMonth: {
        dayHeaderFormat: { weekday: 'short' },
        fixedWeekCount: false,
        eventDisplay: 'block'
      }
    },
    
    // Event rendering settings
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    },
    
    events: function (fetchInfo, successCallback, failureCallback) {
      // Prevent multiple initializations
      if (initializationInProgress) {
        // Just return the events without re-initializing filters
        successCallback(calendarEvents);
        return;
      }
      
      initializationInProgress = true;
      
      fetch('/api/requisition-forms/calendar-events')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            calendarEvents = data.data || [];
            
            // Debug: log event facilities structure
            console.log('Calendar events loaded:', calendarEvents.length);
            if (calendarEvents.length > 0) {
              console.log('First event facilities:', calendarEvents[0].extendedProps?.facilities);
              console.log('First event status:', calendarEvents[0].extendedProps?.status);
            }
            
            // Transform events for display
            const transformedEvents = calendarEvents.map(event => {
              const start = new Date(event.start);
              const end = new Date(event.end);
              const isMultiDay = end.getDate() !== start.getDate() || 
                               end.getMonth() !== start.getMonth() || 
                               end.getFullYear() !== start.getFullYear();
              
              return {
                ...event,
                allDay: isMultiDay
              };
            });
            
            successCallback(transformedEvents);
            
            // Initialize mini calendar and filters AFTER calendar is rendered
            // But only once
            setTimeout(() => {
              if (!miniCalendarInitialized) {
                initializeMiniCalendar();
              }
              if (!filtersInitialized) {
                loadFacilitiesForFilter();
                setupStatusFilterListeners();
              }
            }, 300);
          } else {
            failureCallback(data.message);
          }
        })
        .catch(error => {
          failureCallback('Failed to load events');
          console.error('Calendar events error:', error);
        })
        .finally(() => {
          // Reset after a delay to allow for user interactions
          setTimeout(() => {
            initializationInProgress = false;
          }, 1000);
        });
    },
    
    eventClick: function (info) {
      showEventModal(info.event);
    },
    
    eventDidMount: function (info) {
      info.el.style.backgroundColor = info.event.backgroundColor;
      info.el.style.borderColor = info.event.borderColor;
      info.el.style.color = '#fff';
      info.el.style.fontWeight = 'bold';
      info.el.style.borderRadius = '4px';
      info.el.style.padding = '2px 4px';
      info.el.style.fontSize = '12px';
      
      // For multi-day events in timeGrid views
      if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
        // Check if event spans multiple days or is all-day
        const start = info.event.start;
        const end = info.event.end;
        if (end && (end.getDate() !== start.getDate() || end.getMonth() !== start.getMonth())) {
          // Multi-day event - ensure it goes to all-day slot
          info.el.classList.add('fc-event-multiday');
        }
      }
    },
    
    datesSet: function (info) {
      setTimeout(() => {
        calendar.updateSize();
      }, 50);
    },
    
    viewDidMount: function (info) {
      setTimeout(() => {
        calendar.updateSize();
      }, 100);
    },
    
    slotMinTime: '06:00:00',
    slotMaxTime: '22:00:00',
    nowIndicator: true,
    navLinks: true,
    dayHeaderFormat: {
      weekday: 'long',
      month: 'short',
      day: 'numeric'
    },
    slotLabelFormat: {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    },
    eventDisplay: 'block',
    dayMaxEvents: 3,
    moreLinkClick: 'popover',
    slotDuration: '01:00:00',
    slotLabelInterval: '01:00:00'
  });

  calendar.render();

  setTimeout(() => {
    calendar.updateSize();
  }, 200);

  return calendar;
}

// Setup calendar modal event listeners (Common)
function setupCalendarModalListeners() {
  const calendarModal = document.getElementById('userCalendarModal');
  let userCalendar = null;

  if (calendarModal) {
    calendarModal.addEventListener('shown.bs.modal', function () {
      if (!userCalendar) {
        userCalendar = initializeUserCalendar();
      } else {
        userCalendar.updateSize();
      }
    });
  }
}

// Setup availability buttons (Common)
function setupAvailabilityButtons() {
  const catalogItemsContainer = document.getElementById('catalogItemsContainer');
  if (!catalogItemsContainer) return;

  catalogItemsContainer.addEventListener("click", (e) => {
    if ((e.target.classList.contains("btn-custom") || e.target.classList.contains("btn-outline-secondary")) && 
        e.target.textContent === "Check Availability") {
      const card = e.target.closest(".catalog-card");
      const itemId = card.querySelector(".add-remove-btn")?.dataset.id;
      const itemType = card.querySelector(".add-remove-btn")?.dataset.type;
      const itemName = card.querySelector("h5")?.textContent.trim();
      
      if (itemId && itemType) {
        showAvailabilityCalendar(itemId, itemType, itemName);
      }
    }
  });
}

// Function to show availability modal (Enhanced version from facility catalog)
function showAvailabilityCalendar(itemId, itemType, itemName) {
  const modalElement = document.getElementById('availabilityModal');
  const modal = new bootstrap.Modal(modalElement);
  let availabilityCalendar = null;

  // Clear previous calendar on hide
  modalElement.addEventListener('hidden.bs.modal', function () {
    if (availabilityCalendar) {
      availabilityCalendar.destroy();
      availabilityCalendar = null;
      document.getElementById('availabilityCalendar').innerHTML = '';
    }
  });

  // Initialize calendar when modal is shown
  modalElement.addEventListener('shown.bs.modal', function () {
    setTimeout(() => {
      availabilityCalendar = initializeAvailabilityCalendar(itemId, itemType, itemName);
    }, 50);
  });

  modal.show();
}

// Initialize all calendar functionality
function initCalendar() {
  setupCalendarModalListeners();
  setupAvailabilityButtons();
}

// Make functions available globally
  window.showEventModal = showEventModal;
  window.initializeAvailabilityCalendar = initializeAvailabilityCalendar;
  window.initializeUserCalendar = initializeUserCalendar;
  window.setupCalendarModalListeners = setupCalendarModalListeners;
  window.setupAvailabilityButtons = setupAvailabilityButtons;
  window.showAvailabilityCalendar = showAvailabilityCalendar;
  window.initCalendar = initCalendar;
});