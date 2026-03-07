// calendar-modal.js - All modal-related functions
let adminToken = localStorage.getItem('adminToken');

// Time functions
function generateTimeOptions() {
    const times = [];
    for (let hour = 0; hour < 24; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const period = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
            const timeString = `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
            const timeValue = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            times.push({ display: timeString, value: timeValue });
        }
    }
    return times;
}

function populateTimeDropdowns() {
    const times = generateTimeOptions();
    const startTimeSelect = document.getElementById('startTime');
    const endTimeSelect = document.getElementById('endTime');
    
    if (!startTimeSelect || !endTimeSelect) return;
    
    while (startTimeSelect.options.length > 1) startTimeSelect.remove(1);
    while (endTimeSelect.options.length > 1) endTimeSelect.remove(1);
    
    times.forEach(time => {
        const startOption = new Option(time.display, time.value);
        const endOption = new Option(time.display, time.value);
        startTimeSelect.appendChild(startOption);
        endTimeSelect.appendChild(endOption);
    });
    
    startTimeSelect.value = '09:00';
    endTimeSelect.value = '17:00';
}

function calculateDuration() {
    const startDate = document.getElementById('startDate')?.value;
    const startTime = document.getElementById('startTime')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const endTime = document.getElementById('endTime')?.value;
    const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;
    const durationDisplay = document.getElementById('durationDisplay');
    
    if (!durationDisplay) return;
    
    if (!startDate || !endDate) {
        durationDisplay.textContent = '0 hours';
        return;
    }
    
    if (isAllDay) {
        const start = new Date(startDate + 'T12:00:00');
        const end = new Date(endDate + 'T12:00:00');
        const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
        durationDisplay.textContent = diffDays === 1 ? '1 day' : `${diffDays} days`;
    } else if (startTime && endTime) {
        const start = new Date(`${startDate}T${startTime}`);
        const end = new Date(`${endDate}T${endTime}`);
        
        if (end > start) {
            const diffMs = end - start;
            const hours = Math.floor(diffMs / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (hours > 0 && minutes > 0) durationDisplay.textContent = `${hours}h ${minutes}m`;
            else if (hours > 0) durationDisplay.textContent = `${hours}hrs`;
            else durationDisplay.textContent = `${minutes}mins`;
        } else {
            durationDisplay.textContent = 'Invalid time range';
        }
    }
}

// Character counter
function updateCharacterCounter(input, maxLength, counterId) {
    if (!input) return;
    let counter = document.getElementById(counterId);
    if (!counter) {
        counter = document.createElement('small');
        counter.id = counterId;
        counter.className = 'text-muted d-block mt-1';
        input.parentNode.appendChild(counter);
    }
    
    const update = () => {
        const length = input.value.length;
        counter.textContent = `${length}/${maxLength} characters`;
        const percentage = (length / maxLength) * 100;
        counter.className = 'text-muted d-block mt-1';
        if (percentage >= 90) counter.classList.add('text-danger', 'fw-bold');
        else if (percentage >= 80) counter.classList.add('text-warning', 'fw-medium');
    };
    
    input.addEventListener('input', update);
    input.addEventListener('change', update);
    update();
}

function setupCharacterCounters() {
    const fields = [
        { selector: 'input[name="first_name"]', max: 50, id: 'firstNameCounter' },
        { selector: 'input[name="last_name"]', max: 50, id: 'lastNameCounter' },
        { selector: 'input[name="email"]', max: 100, id: 'emailCounter' },
        { selector: 'input[name="contact_number"]', max: 15, id: 'contactCounter' },
        { selector: '#organizationInput', max: 100, id: 'organizationCounter' },
        { selector: '#schoolIdInput', max: 20, id: 'schoolIdCounter' },
        { selector: 'input[name="endorser"]', max: 50, id: 'endorserCounter' },
        { selector: 'textarea[name="additional_requests"]', max: 250, id: 'additionalRequestsCounter' },
        { selector: 'input[name="calendar_title"]', max: 50, id: 'calendarTitleCounter' },
        { selector: 'textarea[name="calendar_description"]', max: 100, id: 'calendarDescriptionCounter' }
    ];
    
    fields.forEach(field => {
        const element = document.querySelector(field.selector);
        if (element) updateCharacterCounter(element, field.max, field.id);
    });
}

// Access code
function generateAccessCode() {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 10; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    const accessCodeInput = document.getElementById('accessCodeInput');
    if (accessCodeInput) {
        accessCodeInput.value = result;
        if (typeof validateCurrentStep === 'function') validateCurrentStep();
        const reviewAccessCode = document.getElementById('reviewAccessCode');
        if (reviewAccessCode) reviewAccessCode.textContent = result;
        showToast('Access code generated!', 'success');
    }
    return result;
}

// User type toggle
function initializeUserTypeToggle() {
    const userTypeSelect = document.querySelector('select[name="user_type"]');
    const schoolIdInput = document.getElementById('schoolIdInput');
    const organizationInput = document.getElementById('organizationInput');
    
    if (!userTypeSelect || !schoolIdInput || !organizationInput) return;
    
    const handleUserTypeChange = function() {
        if (this.value === 'Internal') {
            schoolIdInput.disabled = false;
            schoolIdInput.setAttribute('required', 'required');
            schoolIdInput.placeholder = "e.g., 2015-12345";
            schoolIdInput.classList.remove('bg-light');
        } else if (this.value === 'External') {
            schoolIdInput.disabled = true;
            schoolIdInput.removeAttribute('required');
            schoolIdInput.value = '';
            schoolIdInput.placeholder = "For internal users only";
            schoolIdInput.classList.add('bg-light');
        }
        organizationInput.disabled = false;
        organizationInput.removeAttribute('required');
    };
    
    userTypeSelect.addEventListener('change', handleUserTypeChange);
    if (!userTypeSelect.value) userTypeSelect.value = 'External';
    setTimeout(() => userTypeSelect.dispatchEvent(new Event('change')), 100);
}

// Load data functions
async function loadPurposes() {
    try {
        const response = await fetch('/api/requisition-purposes', {
            headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('Failed to load purposes');
        const purposes = await response.json();
        const select = document.getElementById('purposeSelect');
        if (!select) return;
        
        select.innerHTML = '<option value="" disabled selected>Select purpose of reservation</option>';
        purposes.forEach(purpose => {
            if (purpose.purpose_id) {
                const option = new Option(purpose.purpose_name, purpose.purpose_id.toString());
                select.appendChild(option);
            }
        });
    } catch (error) {
        console.error('Error loading purposes:', error);
        createFallbackPurposes();
    }
}

function createFallbackPurposes() {
    const select = document.getElementById('purposeSelect');
    if (!select) return;
    const fallbackPurposes = [
        'Facility Rental', 'Equipment Rental', 'Class/Seminar/Conference',
        'University Program/Activity', 'CPU Organization Led Activity',
        'Student-Organized Activity', 'Alumni-Organized Activity',
        'Alumni - Class Reunion', 'Alumni - Personal Events', 'External Event'
    ];
    select.innerHTML = '<option value="" disabled selected>Select purpose of reservation</option>';
    fallbackPurposes.forEach((purpose, index) => {
        const option = new Option(purpose, (index + 1).toString());
        select.appendChild(option);
    });
}

async function loadFacilitiesForReservation() {
    try {
        const response = await fetch('/api/facilities', {
            headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const facilitiesData = await response.json();
        const facilitiesArray = facilitiesData.data || facilitiesData;
        const facilitiesList = document.getElementById('facilitiesList');
        if (!facilitiesList) return;
        
        facilitiesList.innerHTML = '';
        facilitiesArray.forEach(facility => {
            const isAvailable = facility.status_id === 1 || facility.status?.status_id === 1;
            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            div.innerHTML = `
                <input class="form-check-input facility-checkbox" type="checkbox" 
                       id="facility_${facility.facility_id}" value="${facility.facility_id}"
                       data-name="${facility.facility_name}" data-fee="${facility.external_fee}"
                       data-rate-type="${facility.rate_type}" data-capacity="${facility.capacity}"
                       ${!isAvailable ? 'disabled' : ''}>
                <label class="form-check-label ${!isAvailable ? 'text-muted' : ''}" 
                       for="facility_${facility.facility_id}">
                    ${facility.facility_name} (₱${facility.external_fee}${facility.rate_type === 'Per Hour' ? '/hour' : '/event'})
                    <br><small class="text-muted">Capacity: ${facility.capacity} people</small>
                    ${!isAvailable ? '<span class="badge bg-warning ms-2">Unavailable</span>' : ''}
                </label>`;
            facilitiesList.appendChild(div);
        });
    } catch (error) {
        console.error('Error loading facilities:', error);
    }
}

async function loadEquipmentForReservation() {
    try {
        const response = await fetch('/api/equipment', {
            headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const equipmentData = await response.json();
        const equipmentArray = equipmentData.data || equipmentData;
        const equipmentList = document.getElementById('equipmentList');
        if (!equipmentList) return;
        
        equipmentList.innerHTML = '';
        equipmentArray.forEach(equipment => {
            const isAvailable = equipment.status_id === 1 || equipment.status?.status_id === 1;
            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            div.innerHTML = `
                <input class="form-check-input equipment-checkbox" type="checkbox" 
                       id="equipment_${equipment.equipment_id}" value="${equipment.equipment_id}"
                       data-name="${equipment.equipment_name}" data-fee="${equipment.external_fee}"
                       data-rate-type="${equipment.rate_type}" ${!isAvailable ? 'disabled' : ''}>
                <label class="form-check-label ${!isAvailable ? 'text-muted' : ''}" 
                       for="equipment_${equipment.equipment_id}">
                    ${equipment.equipment_name} (₱${equipment.external_fee}${equipment.rate_type === 'Per Hour' ? '/hour' : '/event'})
                    ${!isAvailable ? '<span class="badge bg-warning ms-2">Unavailable</span>' : ''}
                </label>`;
            equipmentList.appendChild(div);
        });
    } catch (error) {
        console.error('Error loading equipment:', error);
    }
}

async function loadExtraServices() {
    try {
        const response = await fetch('/api/extra-services', {
            headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const servicesData = await response.json();
        const servicesArray = servicesData.data || servicesData;
        const container = document.getElementById('extraServicesContainer');
        if (!container) return;
        
        container.innerHTML = '';
        servicesArray.forEach(service => {
            const colDiv = document.createElement('div');
            colDiv.className = 'col-lg-4 col-md-6 col-12';
            colDiv.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input service-checkbox" type="checkbox" 
                           id="service_${service.service_id}" value="${service.service_id}"
                           data-name="${service.service_name}">
                    <label class="form-check-label" for="service_${service.service_id}">${service.service_name}</label>
                </div>`;
            container.appendChild(colDiv);
        });
    } catch (error) {
        console.error('Error loading extra services:', error);
    }
}

async function loadStatusOptions() {
    try {
        const response = await fetch('/api/form-statuses', {
            headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const statuses = await response.json();
        const statusSelect = document.getElementById('initialStatusSelect');
        if (!statusSelect) return;
        
        statusSelect.innerHTML = '<option value="" disabled selected>Select initial status</option>';
        const excludedStatuses = ['Returned', 'Late Return', 'Completed', 'Rejected', 'Cancelled'];
        const availableStatuses = statuses.filter(s => !excludedStatuses.includes(s.status_name));
        
        availableStatuses.forEach(status => {
            const option = new Option(status.status_name, status.status_id);
            option.style.color = status.color_code;
            statusSelect.appendChild(option);
        });
        
        const scheduledOption = Array.from(statusSelect.options).find(opt => opt.text === 'Scheduled');
        if (scheduledOption) scheduledOption.selected = true;
    } catch (error) {
        console.error('Error loading status options:', error);
    }
}

// Make functions globally available
window.generateAccessCode = generateAccessCode;
window.showToast = showToast;
window.calendarModule = null;

// ===== STEP NAVIGATION FUNCTIONS =====
let currentStep = 1;
const totalSteps = 5;

// Validate the current step
function validateCurrentStep() {
    let isValid = false;

    switch (currentStep) {
        case 1:
            const userType = document.querySelector('select[name="user_type"]')?.value;
            const firstName = document.querySelector('input[name="first_name"]')?.value.trim();
            const lastName = document.querySelector('input[name="last_name"]')?.value.trim();
            const email = document.querySelector('input[name="email"]')?.value.trim();
            const schoolId = document.querySelector('input[name="school_id"]')?.value.trim();

            let schoolIdValid = true;
            if (userType === 'Internal') {
                schoolIdValid = schoolId && schoolId.length > 0;
            }

            isValid = userType && firstName && lastName && email && schoolIdValid;
            break;
            
        case 2:
            const purposeSelect = document.getElementById('purposeSelect');
            if (!purposeSelect) {
                isValid = false;
                break;
            }

            const purposeValue = purposeSelect.value;
            const purposeId = purposeValue ? parseInt(purposeValue) : null;
            const selectedIndex = purposeSelect.selectedIndex;
            const isPlaceholderSelected = selectedIndex === 0;
            const purposeValid = !isPlaceholderSelected && purposeId && !isNaN(purposeId) && purposeId > 0;

            const participantsElem = document.querySelector('input[name="num_participants"]');
            const participantsValue = participantsElem?.value;
            const participantsValid = participantsValue && parseInt(participantsValue) > 0;

            const tablesElem = document.querySelector('input[name="num_tables"]');
            const tablesValue = tablesElem?.value;
            const tablesValid = tablesValue !== undefined && tablesValue !== '' && !isNaN(parseInt(tablesValue)) && parseInt(tablesValue) >= 0;

            const chairsElem = document.querySelector('input[name="num_chairs"]');
            const chairsValue = chairsElem?.value;
            const chairsValid = chairsValue !== undefined && chairsValue !== '' && !isNaN(parseInt(chairsValue)) && parseInt(chairsValue) >= 0;

            const microphonesElem = document.querySelector('input[name="num_microphones"]');
            const microphonesValue = microphonesElem?.value;
            const microphonesValid = !microphonesValue || (microphonesValue !== '' && !isNaN(parseInt(microphonesValue)) && parseInt(microphonesValue) >= 0);

            isValid = purposeValid && participantsValid && tablesValid && chairsValid && microphonesValid;

            if (isValid) {
                const accessCodeInput = document.getElementById('accessCodeInput');
                if (!accessCodeInput.value) {
                    generateAccessCode();
                }
            }
            break;

        case 3:
            const facilities = document.querySelectorAll('.facility-checkbox:checked').length;
            const equipment = document.querySelectorAll('.equipment-checkbox:checked').length;
            isValid = facilities > 0 || equipment > 0;
            break;

        case 4:
            const startDate = document.getElementById('startDate')?.value;
            const endDate = document.getElementById('endDate')?.value;
            const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

            if (isAllDay) {
                isValid = startDate && endDate && new Date(endDate) >= new Date(startDate);
            } else {
                const startTime = document.getElementById('startTime')?.value;
                const endTime = document.getElementById('endTime')?.value;

                if (!startDate || !startTime || !endDate || !endTime) {
                    isValid = false;
                } else {
                    const start = new Date(`${startDate}T${startTime}`);
                    const end = new Date(`${endDate}T${endTime}`);
                    isValid = end > start;
                }
            }
            break;

        case 5:
            const statusSelect = document.getElementById('initialStatusSelect');
            const statusValid = statusSelect && statusSelect.value && statusSelect.value !== '';
            const accessCodeInput = document.getElementById('accessCodeInput');
            const accessCodeValid = accessCodeInput && accessCodeInput.value.length === 10;
            isValid = statusValid && accessCodeValid;
            break;

        default:
            isValid = false;
    }

    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitReservationBtn');

    if (nextBtn) nextBtn.disabled = !isValid;
    if (currentStep === totalSteps && submitBtn) submitBtn.disabled = !isValid;

    return isValid;
}

// Update review summary
function updateReviewSummary() {
    // User info
    const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
    const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
    document.getElementById('reviewUserName').textContent = `${firstName} ${lastName}`;

    // Purpose
    const purposeSelect = document.querySelector('select[name="purpose_id"]');
    const purposeText = purposeSelect?.options[purposeSelect.selectedIndex]?.text || '-';
    document.getElementById('reviewPurpose').textContent = purposeText;

    // Participants
    const participants = document.querySelector('input[name="num_participants"]')?.value || '0';
    document.getElementById('reviewParticipants').textContent = participants;

    // Tables/Chairs
    const tables = document.querySelector('input[name="num_tables"]')?.value || '0';
    const chairs = document.querySelector('input[name="num_chairs"]')?.value || '0';
    document.getElementById('reviewFurniture').textContent = `${tables} tables, ${chairs} chairs`;

    // Microphones
    const numMicrophones = document.querySelector('input[name="num_microphones"]')?.value || '0';
    const reviewMicrophones = document.getElementById('reviewMicrophones');
    if (reviewMicrophones) reviewMicrophones.textContent = numMicrophones;

    // Endorser
    const endorser = document.querySelector('input[name="endorser"]')?.value || 'None';
    document.getElementById('reviewEndorser').textContent = endorser;

    // Status
    const statusSelect = document.getElementById('initialStatusSelect');
    const selectedStatus = statusSelect ? statusSelect.options[statusSelect.selectedIndex]?.text : 'Scheduled';
    document.getElementById('reviewStatus').textContent = selectedStatus;

    // Schedule
    const startDate = document.getElementById('startDate')?.value;
    const startTime = document.getElementById('startTime')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const endTime = document.getElementById('endTime')?.value;
    const isAllDay = document.getElementById('allDayCheckbox')?.checked || false;

    if (startDate && endDate) {
        const dateOptions = { month: 'short', day: 'numeric', year: 'numeric' };
        const startDateObj = new Date(startDate + 'T12:00:00');
        const endDateObj = new Date(endDate + 'T12:00:00');

        if (isAllDay) {
            if (startDate === endDate) {
                document.getElementById('reviewSchedule').textContent = `${startDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
            } else {
                document.getElementById('reviewSchedule').textContent = `${startDateObj.toLocaleDateString('en-US', dateOptions)} - ${endDateObj.toLocaleDateString('en-US', dateOptions)} (All Day)`;
            }

            const diffDays = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('reviewDuration').textContent = diffDays === 1 ? '1 day' : `${diffDays} days`;
        } else if (startTime && endTime) {
            const start = new Date(`${startDate}T${startTime}`);
            const end = new Date(`${endDate}T${endTime}`);
            const timeOptions = { hour: '2-digit', minute: '2-digit' };

            const startStr = `${start.toLocaleDateString('en-US', dateOptions)} ${start.toLocaleTimeString('en-US', timeOptions)}`;
            const endStr = `${end.toLocaleDateString('en-US', dateOptions)} ${end.toLocaleTimeString('en-US', timeOptions)}`;

            document.getElementById('reviewSchedule').textContent = `${startStr} to ${endStr}`;

            const durationMs = end - start;
            const hours = Math.floor(durationMs / (1000 * 60 * 60));
            const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));

            let durationStr = '';
            if (hours > 0) durationStr += `${hours} hour${hours > 1 ? 's' : ''}`;
            if (minutes > 0) {
                if (hours > 0) durationStr += ' ';
                durationStr += `${minutes} minute${minutes > 1 ? 's' : ''}`;
            }
            document.getElementById('reviewDuration').textContent = durationStr || '0 hours';
        } else {
            document.getElementById('reviewSchedule').textContent = '-';
            document.getElementById('reviewDuration').textContent = '-';
        }
    } else {
        document.getElementById('reviewSchedule').textContent = '-';
        document.getElementById('reviewDuration').textContent = '-';
    }

    // Facilities
    const selectedFacilities = Array.from(document.querySelectorAll('.facility-checkbox:checked'))
        .map(cb => cb.dataset.name || cb.value);
    document.getElementById('reviewFacilities').textContent = selectedFacilities.length > 0 ? selectedFacilities.join(', ') : 'None selected';

    // Equipment
    const selectedEquipment = Array.from(document.querySelectorAll('.equipment-checkbox:checked'))
        .map(cb => cb.dataset.name || cb.value);
    document.getElementById('reviewEquipment').textContent = selectedEquipment.length > 0 ? selectedEquipment.join(', ') : 'None selected';

    // Extra Services
    const selectedServices = Array.from(document.querySelectorAll('.service-checkbox:checked'))
        .map(cb => cb.dataset.name || cb.value);
    const reviewServices = document.getElementById('reviewServices');
    if (reviewServices) {
        reviewServices.textContent = selectedServices.length > 0 ? selectedServices.join(', ') : 'None selected';
    }

    // Access Code
    const accessCode = document.getElementById('accessCodeInput')?.value || '-';
    document.getElementById('reviewAccessCode').textContent = accessCode;
}

// Update step display
function updateStepDisplay() {
    // Update step indicators
    document.querySelectorAll('.step').forEach(step => {
        const stepNum = parseInt(step.dataset.step);
        if (stepNum === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    // Show/hide step content
    document.querySelectorAll('.step-content').forEach(content => {
        const stepNum = parseInt(content.dataset.step);
        if (stepNum === currentStep) {
            content.classList.remove('d-none');
        } else {
            content.classList.add('d-none');
        }
    });

    // Update navigation buttons
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const submitBtn = document.getElementById('submitReservationBtn');

    prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-block';

    if (currentStep === totalSteps) {
        nextBtn.classList.add('d-none');
        submitBtn.classList.remove('d-none');
        updateReviewSummary();
    } else {
        nextBtn.classList.remove('d-none');
        submitBtn.classList.add('d-none');
    }

    validateCurrentStep();
}

// Initialize step navigation
function setupReservationStepNavigation() {
    currentStep = 1;
    
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');

    // Add auto-validation when form fields change
    document.querySelectorAll('#addReservationForm input, #addReservationForm select, #addReservationForm textarea').forEach(element => {
        element.addEventListener('change', () => {
            validateCurrentStep();
            if (currentStep === 5) updateReviewSummary();
        });
        
        if (element.type === 'number' || element.tagName === 'SELECT') {
            element.addEventListener('input', () => {
                validateCurrentStep();
                if (currentStep === 5) updateReviewSummary();
            });
        }
    });

    // Navigation event listeners
    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStepDisplay();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (validateCurrentStep() && currentStep < totalSteps) {
            currentStep++;
            updateStepDisplay();
        }
    });

    // Setup checkbox listeners
    document.addEventListener('change', function (e) {
        if (e.target.matches('.facility-checkbox, .equipment-checkbox')) {
            if (currentStep === 3) validateCurrentStep();
            if (currentStep === 5) updateReviewSummary();
        }
    });

    // Clear Facilities button
    const clearFacilitiesBtn = document.getElementById('clearFacilities');
    if (clearFacilitiesBtn) {
        clearFacilitiesBtn.addEventListener('click', function () {
            document.querySelectorAll('.facility-checkbox:checked').forEach(cb => cb.checked = false);
            const selectAllCheckbox = document.getElementById('selectAllFacilities');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            if (currentStep === 3 || currentStep === 5) {
                validateCurrentStep();
                if (currentStep === 5) updateReviewSummary();
            }
        });
    }

    // Clear Equipment button
    const clearEquipmentBtn = document.getElementById('clearEquipment');
    if (clearEquipmentBtn) {
        clearEquipmentBtn.addEventListener('click', function () {
            document.querySelectorAll('.equipment-checkbox:checked').forEach(cb => cb.checked = false);
            if (currentStep === 3 || currentStep === 5) {
                validateCurrentStep();
                if (currentStep === 5) updateReviewSummary();
            }
        });
    }

    // Select All Facilities
    const selectAllFacilities = document.getElementById('selectAllFacilities');
    if (selectAllFacilities) {
        selectAllFacilities.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.facility-checkbox:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = this.checked);
            if (currentStep === 3 || currentStep === 5) {
                validateCurrentStep();
                if (currentStep === 5) updateReviewSummary();
            }
        });
    }

    // Initial display
    updateStepDisplay();
    
    return {
        resetSteps: () => {
            currentStep = 1;
            updateStepDisplay();
        },
        validateCurrentStep: validateCurrentStep
    };
}

// Reset steps function
function resetSteps() {
    currentStep = 1;
    updateStepDisplay();
}

// Make validateCurrentStep available globally for other functions
window.validateCurrentStep = validateCurrentStep;

// EXPORT the functions for dynamic import
export {
    generateTimeOptions,
    populateTimeDropdowns,
    calculateDuration,
    updateCharacterCounter,
    setupCharacterCounters,
    generateAccessCode,
    initializeUserTypeToggle,
    loadPurposes,
    createFallbackPurposes,
    loadFacilitiesForReservation,
    loadEquipmentForReservation,
    loadExtraServices,
    loadStatusOptions,
    // New exports
    setupReservationStepNavigation,
    resetSteps,
    validateCurrentStep,
    updateReviewSummary,
    currentStep,
    totalSteps
};