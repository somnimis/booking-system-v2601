// ========== CACHE SYSTEM ==========
const cache = {
    facilities: {
        data: null,
        timestamp: null,
        expiry: 300000 // 5 minutes in milliseconds
    },
    equipment: {
        data: null,
        timestamp: null,
        expiry: 300000
    },
    facilityCategories: {
        data: null,
        timestamp: null,
        expiry: 300000
    },
    equipmentCategories: {
        data: null,
        timestamp: null,
        expiry: 300000
    },
    isExpired(key) {
        if (!this[key].timestamp) return true;
        return Date.now() - this[key].timestamp > this[key].expiry;
    },
    get(key) {
        if (this.isExpired(key)) return null;
        return this[key].data;
    },
    set(key, data) {
        this[key].data = data;
        this[key].timestamp = Date.now();
    },
    clear(key) {
        this[key].data = null;
        this[key].timestamp = null;
    }
};

// ========== GLOBAL VARIABLES ==========
let currentStep = 1;
const facilityList = document.getElementById("facilityList");
const equipmentList = document.getElementById("equipmentList");
const feeDisplay = document.getElementById("feeDisplay");
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Ensure the form always starts at Step 1 when page loads
window.addEventListener("load", function () {
    showStep(1);
});

// Helper function to format time for display
function formatTimeForDisplay(time24) {
    if (!time24) return "";

    const [hours, minutes] = time24.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "pm" : "am";
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes}${ampm}`;
}
// ========== LOCAL STORAGE AUTO-SAVE ==========
class LocalStorageAutoSave {
    constructor(options = {}) {
        // Configuration
        this.formSelector = options.formSelector || "#reservationForm";
        this.formId =
            options.formId ||
            "reservation_form_" + window.location.pathname.replace(/\//g, "_");
        this.saveInterval = options.saveInterval || 2000; // 2 seconds
        this.excludedFields = options.excludedFields || [
            "password",
            "confirm_password",
            "token",
            "_token",
            "csrf_token",
        ];

        // State
        this.debounceTimer = null;
        this.isSaving = false;
        this.storageKey = `reservation_form_data_${this.formId}`;

        this.init();
    }

    init() {
        // Get form element
        this.form = document.querySelector(this.formSelector);
        if (!this.form) {
            console.warn("Form not found with selector:", this.formSelector);
            return;
        }

        // Check if form was recently submitted (using sessionStorage)
        const recentlySubmitted = sessionStorage.getItem(
            "form_recently_submitted",
        );

        if (recentlySubmitted === "true") {
            // Form was recently submitted, clear everything and start fresh
            sessionStorage.removeItem("form_recently_submitted");
            localStorage.removeItem(this.storageKey);
            showStep(1);
            console.log("Starting fresh (recent submission detected)");
        } else {
            // Normal behavior: restore saved data
            setTimeout(() => this.restoreProgress(), 100);
        }

        // Set up event listeners
        this.setupEventListeners();

        // Auto-save on page unload
        window.addEventListener("beforeunload", () => {
            this.saveProgress(true);
        });

        // Periodically save (as backup)
        setInterval(() => {
            this.saveProgress();
        }, this.saveInterval * 2);
    }

    setupEventListeners() {
        // Listen to all form inputs
        const saveHandler = (e) => {
            if (this.shouldExcludeField(e.target)) return;
            this.debouncedSave();
        };

        this.form.addEventListener("input", saveHandler);
        this.form.addEventListener("change", saveHandler);

        // Handle special cases
        this.form.addEventListener("blur", saveHandler, true);
    }

    shouldExcludeField(field) {
        if (!field) return false;

        const fieldName = (field.name || "").toLowerCase();
        const fieldType = (field.type || "").toLowerCase();

        return this.excludedFields.some(
            (excluded) =>
                fieldName.includes(excluded) ||
                fieldType.includes("password") ||
                fieldType.includes("file"),
        );
    }

    debouncedSave() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            this.saveProgress();
        }, 500); // 500ms debounce
    }

    getFormData() {
        const data = {};

        // Get all form elements
        const elements = this.form.querySelectorAll(
            "input, select, textarea, .selected-items-container",
        );

        elements.forEach((element) => {
            if (this.shouldExcludeField(element)) return;

            const name = element.name;
            if (!name) return;

            const type = element.type || "text";
            const tagName = element.tagName.toLowerCase();

            if (tagName === "input") {
                if (type === "checkbox" || type === "radio") {
                    // Handle checkboxes and radios
                    const checkboxes = this.form.querySelectorAll(
                        `[name="${name}"]`,
                    );
                    if (checkboxes.length > 1) {
                        data[name] = [];
                        checkboxes.forEach((cb) => {
                            if (cb.checked) {
                                data[name].push(cb.value);
                            }
                        });
                    } else {
                        data[name] = element.checked ? element.value : "";
                    }
                } else {
                    data[name] = element.value;
                }
            } else if (tagName === "select") {
                if (element.multiple) {
                    data[name] = Array.from(element.selectedOptions).map(
                        (option) => option.value,
                    );
                } else {
                    data[name] = element.value;
                }
            } else if (tagName === "textarea") {
                data[name] = element.value;
            }
        });

        // Save current step
        data._currentStep = currentStep;
        data._timestamp = new Date().toISOString();

        return data;
    }

    saveProgress(immediate = false) {
        if (this.isSaving) return;

        this.isSaving = true;
        const formData = this.getFormData();

        try {
            // Save to localStorage
            localStorage.setItem(this.storageKey, JSON.stringify(formData));

            return true;
        } catch (error) {
            console.error("Error saving to localStorage:", error);
            return false;
        } finally {
            this.isSaving = false;
        }
    }

    restoreProgress() {
        try {
            const savedData = localStorage.getItem(this.storageKey);
            if (!savedData) return false;

            const data = JSON.parse(savedData);

            if (!data || Object.keys(data).length === 0) {
                return false;
            }

            // Restore form data (excluding metadata)
            Object.entries(data).forEach(([fieldName, value]) => {
                if (fieldName.startsWith("_")) return;

                const elements = this.form.querySelectorAll(
                    `[name="${fieldName}"]`,
                );

                if (elements.length === 0) return;

                elements.forEach((element) => {
                    const type = element.type || "text";
                    const tagName = element.tagName.toLowerCase();

                    if (tagName === "input") {
                        if (type === "checkbox" || type === "radio") {
                            if (Array.isArray(value)) {
                                element.checked = value.includes(element.value);
                            } else {
                                element.checked = element.value == value;
                            }
                        } else {
                            element.value = value || "";
                        }
                    } else if (tagName === "select") {
                        if (element.multiple && Array.isArray(value)) {
                            Array.from(element.options).forEach((option) => {
                                option.selected = value.includes(option.value);
                            });
                        } else {
                            element.value = value || "";
                        }
                    } else if (tagName === "textarea") {
                        element.value = value || "";
                    }
                });
            });

            // Show restore notification
            this.showRestoreNotification();

            return true;
        } catch (error) {
            console.error("Error restoring from localStorage:", error);
            return false;
        }
    }

    clearProgress() {
        try {
            localStorage.removeItem(this.storageKey);
            this.form.reset();

            // Reset to step 1
            currentStep = 1;
            showStep(1);

            return true;
        } catch (error) {
            console.error("Error clearing localStorage:", error);
            return false;
        }
    }

    showRestoreNotification() {
        // Instead of creating a custom notification, use your existing showToast function
        const savedData = localStorage.getItem(this.storageKey);
        const data = savedData ? JSON.parse(savedData) : null;
        const timeText =
            data && data._timestamp
                ? `Last saved: ${new Date(data._timestamp).toLocaleString()}`
                : "";

        showToast(
            `Draft restored<br><small>${timeText}</small>`,
            "success",
            5000,
        );
    }
}

// ========== TIME SELECTION GENERATOR ==========

function populateTimeSelects() {
    const times = [];
    for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 30) {
            const hour12 = h % 12 || 12;
            const ampm = h >= 12 ? "PM" : "AM";
            const display = `${String(hour12).padStart(2, "0")}:${String(m).padStart(2, "0")} ${ampm}`;
            times.push(display);
        }
    }

    const options = times
        .map((t) => `<option value="${t}">${t}</option>`)
        .join("");
    document.getElementById("startTimeField").innerHTML = options;
    document.getElementById("endTimeField").innerHTML = options;
}

// ========== STEP NAVIGATION FUNCTIONS ==========
function showStep(stepNumber) {
    document.querySelectorAll(".step-section").forEach((section) => {
        section.classList.remove("active");
    });

    const stepElement = document.getElementById("step" + stepNumber);
    if (stepElement) {
        stepElement.classList.add("active");
    }

    currentStep = stepNumber;
}

function nextStep(nextStepNumber) {
    if (nextStepNumber === 2 && !validateStep1()) {
        return;
    }

    if (nextStepNumber === 3 && !validateStep2()) {
        return;
    }

    if (nextStepNumber === 3) {
        populateFormSummary();
    }
    showStep(nextStepNumber);
}

function previousStep(prevStepNumber) {
    const reservationForm = document.getElementById("reservationForm");
    reservationForm
        .querySelectorAll(".is-invalid")
        .forEach((input) => clearFieldError(input));
    showStep(prevStepNumber);
}

// ========== VALIDATION FUNCTIONS ==========
function validateStep1() {
    const startDate = document.getElementById("startDateField").value;
    const endDate = document.getElementById("endDateField").value;
    const startTime = document.getElementById("startTimeField").value;
    const endTime = document.getElementById("endTimeField").value;

    const errors = [];

    if (!startDate) errors.push("Start Date is required");
    if (!endDate) errors.push("End Date is required");
    if (!startTime) errors.push("Start Time is required");
    if (!endTime) errors.push("End Time is required");

    if (errors.length > 0) {
        showToast(
            "Please complete the booking schedule:\n• " + errors.join("\n• "),
            "error",
        );
        return false;
    }

    if (endDate < startDate) {
        showToast("End date cannot be before start date.", "error");
        return false;
    }

    if (startDate === endDate) {
        const startDateTime = new Date(
            `${startDate}T${convertTo24Hour(startTime)}:00`,
        );
        const endDateTime = new Date(
            `${endDate}T${convertTo24Hour(endTime)}:00`,
        );

        if (endDateTime <= startDateTime) {
            showToast(
                "End time must be after start time when using the same date.",
                "error",
            );
            return false;
        }
    }

    return true;
}

function validateStep2() {
    const reservationForm = document.getElementById("reservationForm");
    let valid = true;
    let firstInvalid = null;

    // Add num_microphones to required fields
    const requiredFields = [
        "user_type",
        "first_name",
        "last_name",
        "email",
        "num_participants",
        "purpose_id",
        "num_chairs",
        "num_tables",
        "num_microphones",
        "event_title",
    ];

    // Clear existing errors
    reservationForm
        .querySelectorAll(".is-invalid")
        .forEach((input) => clearFieldError(input));

    // Validate required fields
    requiredFields.forEach((name) => {
        const input = reservationForm.querySelector(`[name="${name}"]`);
        if (input) {
            // Special handling for numeric fields that should be >= 0
            if (
                name === "num_chairs" ||
                name === "num_tables" ||
                name === "num_microphones"
            ) {
                if (
                    input.value === "" ||
                    input.value === null ||
                    parseInt(input.value) < 0
                ) {
                    showFieldError(
                        input,
                        "Please enter a valid number (0 or greater).",
                    );
                    valid = false;
                    if (!firstInvalid) firstInvalid = input;
                }
            } else if (
                !input.value ||
                (name === "user_type" && input.value === "") ||
                (name === "purpose_id" &&
                    (input.value === "" || input.value === null))
            ) {
                showFieldError(input, "Please fill in this field.");
                valid = false;
                if (!firstInvalid) firstInvalid = input;
            }
        }
    });

    // School ID validation for Internal applicants
    const applicantType = document.getElementById("applicantType");
    const schoolIdInput = document.getElementById("school_id");
    if (applicantType.value === "Internal") {
        clearFieldError(schoolIdInput);
        if (!schoolIdInput.value) {
            showFieldError(schoolIdInput, "Please fill in this field.");
            valid = false;
            if (!firstInvalid) firstInvalid = schoolIdInput;
        }
    } else {
        clearFieldError(schoolIdInput);
    }

    // Email format validation
    const emailInput = reservationForm.querySelector('[name="email"]');
    if (emailInput && emailInput.value) {
        clearFieldError(emailInput);
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailInput.value)) {
            showFieldError(emailInput, "Please enter a valid email address.");
            valid = false;
            if (!firstInvalid) firstInvalid = emailInput;
        }
    }

    // Contact number validation
    const contactNumberField = document.getElementById("contactNumberField");
    if (contactNumberField && contactNumberField.value) {
        clearFieldError(contactNumberField);
        if (!/^\d{1,15}$/.test(contactNumberField.value)) {
            showFieldError(
                contactNumberField,
                "Contact number must be numbers only (max 15 digits).",
            );
            valid = false;
            if (!firstInvalid) firstInvalid = contactNumberField;
        }
    } else {
        clearFieldError(contactNumberField);
    }

    // Additional validation for numeric fields to ensure they're not negative
    const numChairsInput = document.querySelector('input[name="num_chairs"]');
    const numTablesInput = document.querySelector('input[name="num_tables"]');
    const numMicrophonesInput = document.querySelector(
        'input[name="num_microphones"]',
    );

    if (
        numChairsInput &&
        numChairsInput.value !== "" &&
        parseInt(numChairsInput.value) < 0
    ) {
        showFieldError(numChairsInput, "Number of chairs cannot be negative.");
        valid = false;
        if (!firstInvalid) firstInvalid = numChairsInput;
    }

    if (
        numTablesInput &&
        numTablesInput.value !== "" &&
        parseInt(numTablesInput.value) < 0
    ) {
        showFieldError(numTablesInput, "Number of tables cannot be negative.");
        valid = false;
        if (!firstInvalid) firstInvalid = numTablesInput;
    }

    if (
        numMicrophonesInput &&
        numMicrophonesInput.value !== "" &&
        parseInt(numMicrophonesInput.value) < 0
    ) {
        showFieldError(
            numMicrophonesInput,
            "Number of microphones cannot be negative.",
        );
        valid = false;
        if (!firstInvalid) firstInvalid = numMicrophonesInput;
    }

    // Scroll to first invalid field
    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalid.focus();
    }

    return valid;
}

// ========== HELPER FUNCTIONS ==========
function showFieldError(input, message) {
    input.classList.add("is-invalid");
    input.setAttribute("title", message);
    input.setAttribute("data-bs-toggle", "tooltip");
    input.setAttribute("data-bs-placement", "top");

    if (window.bootstrap) {
        new bootstrap.Tooltip(input);
        input.addEventListener("focus", function () {
            bootstrap.Tooltip.getInstance(input)?.show();
        });
    }
}

function clearFieldError(input) {
    input.classList.remove("is-invalid");
    input.removeAttribute("title");
    input.removeAttribute("data-bs-toggle");
    input.removeAttribute("data-bs-placement");

    if (window.bootstrap) {
        const tooltip = bootstrap.Tooltip.getInstance(input);
        if (tooltip) tooltip.dispose();
    }
}

// Add this function to your JavaScript code, preferably near other helper functions
function adjustEndTime() {
    const startTimeSelect = document.getElementById("startTimeField");
    const endTimeSelect = document.getElementById("endTimeField");

    if (!startTimeSelect || !endTimeSelect) return;

    const startTime = startTimeSelect.value;
    const endTime = endTimeSelect.value;

    // If no start time selected, do nothing
    if (!startTime) return;

    // Get all options from end time select
    const options = Array.from(endTimeSelect.options);

    // Find index of current start time
    const startIndex = options.findIndex((opt) => opt.value === startTime);

    // If end time is before or equal to start time, update it
    const endIndex = options.findIndex((opt) => opt.value === endTime);

    if (endIndex <= startIndex) {
        // Set end time to next available option after start time
        if (startIndex + 1 < options.length) {
            endTimeSelect.value = options[startIndex + 1].value;
        } else {
            // If start time is the last option, set to the same (will be validated later)
            endTimeSelect.value = startTime;
        }
    }
}

function updateEndTimeOptions() {
    const startTimeSelect = document.getElementById("startTimeField");
    const endTimeSelect = document.getElementById("endTimeField");

    if (!startTimeSelect || !endTimeSelect) return;

    const startTime = startTimeSelect.value;
    if (!startTime) return;

    const options = Array.from(endTimeSelect.options);
    const startIndex = options.findIndex((opt) => opt.value === startTime);

    // Enable/disable options based on start time
    options.forEach((option, index) => {
        option.disabled = index <= startIndex;
    });

    // If current end time is now disabled, update it
    if (endTimeSelect.selectedOptions[0]?.disabled) {
        const firstValidOption = options.find((opt) => !opt.disabled);
        if (firstValidOption) {
            endTimeSelect.value = firstValidOption.value;
        }
    }
}

window.convertTo24Hour = function (time12h) {
    if (!time12h) return "";

    if (time12h.includes(":")) {
        const [timePart, modifier] = time12h.split(" ");
        if (!modifier) return timePart;

        let [hours, minutes] = timePart.split(":");
        hours = parseInt(hours, 10);

        if (modifier === "PM" && hours !== 12) {
            hours += 12;
        } else if (modifier === "AM" && hours === 12) {
            hours = 0;
        }

        return `${hours.toString().padStart(2, "0")}:${minutes}`;
    }

    return time12h;
};

// ========== FORM SUMMARY FUNCTIONS ==========
function populateFormSummary() {
    // Contact Information
    const applicantType = document.getElementById("applicantType");
    const firstName = document.querySelector('input[name="first_name"]');
    const lastName = document.querySelector('input[name="last_name"]');
    const email = document.querySelector('input[name="email"]');
    const contactNumber = document.querySelector(
        'input[name="contact_number"]',
    );
    const organizationName = document.querySelector(
        'input[name="organization_name"]',
    );
    const schoolId = document.getElementById("school_id");

    document.getElementById("summary-applicant-type").textContent =
        applicantType.value || "Not specified";
    document.getElementById("summary-name").textContent =
        `${firstName.value || ""} ${lastName.value || ""}`.trim() ||
        "Not specified";
    document.getElementById("summary-email").textContent =
        email.value || "Not specified";
    document.getElementById("summary-contact").textContent =
        contactNumber.value || "Not specified";
    document.getElementById("summary-organization").textContent =
        organizationName.value || "Not specified";

    const schoolIdContainer = document.getElementById(
        "summary-school-id-container",
    );
    if (applicantType.value === "Internal" && schoolId.value) {
        document.getElementById("summary-school-id").textContent =
            schoolId.value;
        schoolIdContainer.style.display = "flex";
    } else {
        schoolIdContainer.style.display = "none";
    }

    // Extra Services
    const extraServicesCheckboxes = document.querySelectorAll(
        'input[name="extra_services[]"]:checked',
    );
    let selectedServices = [];
    extraServicesCheckboxes.forEach((checkbox) => {
        const label = document.querySelector(
            `label[for="${checkbox.id}"]`,
        )?.textContent;
        if (label) {
            selectedServices.push(label.trim());
        }
    });

    if (selectedServices.length > 0) {
        document.getElementById("summary-services").textContent =
            selectedServices.join(", ");
    } else {
        document.getElementById("summary-services").textContent = "None";
    }

    // Reservation Details
    const eventTitle = document.querySelector('input[name="event_title"]');
    const purposeSelect = document.getElementById("activityPurposeField");
    const purposeText =
        purposeSelect.options[purposeSelect.selectedIndex]?.text ||
        "Not specified";
    const eventDetails = document.querySelector(
        'textarea[name="event_details"]',
    );
    const startDate = document.getElementById("startDateField").value;
    const endDate = document.getElementById("endDateField").value;
    const startTime = document.getElementById("startTimeField").value;
    const endTime = document.getElementById("endTimeField").value;
    const allDayCheckbox = document.getElementById("allDayField");
    const isAllDay = allDayCheckbox ? allDayCheckbox.checked : false;
    const numParticipants = document.querySelector(
        'input[name="num_participants"]',
    );
    const numChairs = document.querySelector('input[name="num_chairs"]');
    const numTables = document.querySelector('input[name="num_tables"]');
    const numMicrophones = document.querySelector(
        'input[name="num_microphones"]',
    );
    const additionalRequests = document.querySelector(
        'textarea[name="additional_requests"]',
    );

    document.getElementById("summary-event-title").textContent =
        eventTitle?.value || "Not specified";
    document.getElementById("summary-purpose").textContent = purposeText;
    document.getElementById("summary-event-details").textContent =
        eventDetails?.value || "None";

    // Helper function to format date
    const formatDate = (dateString) => {
        if (!dateString) return "Not specified";
        return new Date(dateString).toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    };

    // Helper function to format time from 24h to 12h format
    const formatTime = (time24) => {
        if (!time24) return "";
        const [hours, minutes] = time24.split(":");
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? "pm" : "am";
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes}${ampm}`;
    };

    // Format schedule based on all_day flag
    if (startDate) {
        const formattedStartDate = formatDate(startDate);
        const formattedEndDate = formatDate(endDate);

        if (isAllDay) {
            if (startDate === endDate) {
                document.getElementById("summary-start").textContent =
                    `${formattedStartDate} (All Day)`;
                document.getElementById("summary-end").textContent =
                    `${formattedEndDate} (All Day)`;
            } else {
                document.getElementById("summary-start").textContent =
                    `${formattedStartDate} (All Day)`;
                document.getElementById("summary-end").textContent =
                    `${formattedEndDate} (All Day)`;
            }
        } else {
            if (startDate === endDate) {
                const formattedStartTime = formatTime(
                    convertTo24Hour(startTime),
                );
                const formattedEndTime = formatTime(convertTo24Hour(endTime));
                document.getElementById("summary-start").textContent =
                    `${formattedStartDate}, ${formattedStartTime}`;
                document.getElementById("summary-end").textContent =
                    `${formattedEndDate}, ${formattedEndTime}`;
            } else {
                const formattedStartTime = formatTime(
                    convertTo24Hour(startTime),
                );
                const formattedEndTime = formatTime(convertTo24Hour(endTime));
                document.getElementById("summary-start").textContent =
                    `${formattedStartDate}, ${formattedStartTime}`;
                document.getElementById("summary-end").textContent =
                    `${formattedEndDate}, ${formattedEndTime}`;
            }
        }
    } else {
        document.getElementById("summary-start").textContent = "Not specified";
        document.getElementById("summary-end").textContent = "Not specified";
    }

    document.getElementById("summary-participants").textContent =
        numParticipants.value || "0";

    // Furniture summary including microphones
    const furnitureText = `${numChairs.value || "0"} chairs, ${numTables.value || "0"} tables, ${numMicrophones.value || "0"} microphones`;
    document.getElementById("summary-furniture").textContent = furnitureText;

    document.getElementById("summary-requests").textContent =
        additionalRequests.value || "None";

    // Generate fee breakdown
    generateFeeBreakdownForSummary();
}
// ========== TOAST FUNCTION ==========
window.showToast = function (message, type = "success", duration = 3000) {
    const toast = document.createElement("div");
    toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
    toast.style.zIndex = "1100";
    toast.style.bottom = "0";
    toast.style.left = "0";
    toast.style.margin = "1rem";
    toast.style.opacity = "0";
    toast.style.transform = "translateY(20px)";
    toast.style.transition = "transform 0.4s ease, opacity 0.4s ease";

    const bgColor = type === "success" ? "#004183ff" : "#dc3545";
    toast.style.backgroundColor = bgColor;
    toast.style.color = "#fff";
    toast.style.minWidth = "250px";
    toast.style.borderRadius = "0.3rem";

    toast.innerHTML = `
            <div class="d-flex align-items-center px-3 py-1"> 
                <i class="bi ${type === "success" ? "bi-check-circle-fill" : "bi-exclamation-circle-fill"} me-2"></i>
                <div class="toast-body flex-grow-1" style="padding: 0.25rem 0;">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="loading-bar" style="
                height: 3px;
                background: rgba(255,255,255,0.7);
                width: 100%;
                transition: width ${duration}ms linear;
            "></div>
        `;

    document.body.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast, { autohide: false });
    bsToast.show();

    requestAnimationFrame(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateY(0)";
    });

    const loadingBar = toast.querySelector(".loading-bar");
    requestAnimationFrame(() => {
        loadingBar.style.width = "0%";
    });

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateY(20px)";
        setTimeout(() => {
            bsToast.hide();
            toast.remove();
        }, 400);
    }, duration);
};

// ========== INITIALIZATION ==========
document.addEventListener("DOMContentLoaded", function () {
    console.log("DOMContentLoaded fired - Initializing page");

    // ========== 1. INITIALIZE ALL BOOTSTRAP COMPONENTS ==========
    if (typeof bootstrap !== "undefined") {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]'),
        );
        tooltipTriggerList.map((el) => new bootstrap.Tooltip(el));

        const dropdownElements = document.querySelectorAll(".dropdown-toggle");
        dropdownElements.forEach((dropdown) => {
            new bootstrap.Dropdown(dropdown);
        });

        console.log("Bootstrap components initialized");
    } else {
        console.warn("Bootstrap not loaded yet");
    }

    populateTimeSelects();

    // ========== EQUIPMENT SEARCH ==========
    let equipmentSearchTimer;
    document.getElementById("equipmentSearch").addEventListener("input", function () {
        clearTimeout(equipmentSearchTimer);
        equipmentSearchTimer = setTimeout(() => {
            equipmentSearch = this.value;
            equipmentCurrentPage = 1;
            cache.clear('equipment');
            loadEquipment(true);
        }, 300);
    });

    // ========== FACILITY SEARCH ==========
    let facilitySearchTimer;
    document.getElementById("facilitySearch").addEventListener("input", function () {
        clearTimeout(facilitySearchTimer);
        facilitySearchTimer = setTimeout(() => {
            facilitySearch = this.value;
            facilityCurrentPage = 1;
            cache.clear('facilities');
            loadFacilities(true);
        }, 300);
    });

    // ========== FACILITY CATEGORY FILTER ==========
    document.getElementById("facilityCategoryFilter")?.addEventListener("change", function() {
        facilityCurrentPage = 1;
        cache.clear('facilities');
        loadFacilities(true);
    });

    // ========== EQUIPMENT CATEGORY FILTER ==========
    document.getElementById("equipmentCategoryFilter")?.addEventListener("change", function() {
        equipmentCurrentPage = 1;
        cache.clear('equipment');
        loadEquipment(true);
    });

    // ========== 2. INITIALIZE STEP SYSTEM ==========
    showStep(1);

    // ========== 3. INITIALIZE LOCAL STORAGE AUTO-SAVE ==========
    window.autoSave = new LocalStorageAutoSave({
        formSelector: "#reservationForm",
        formId: "reservation_form",
        saveInterval: 2000,
        excludedFields: ["password", "_token", "csrf_token"],
    });

    // ========== 4. SET UP EVENT LISTENERS ==========
    window.addEventListener("formSubmitted", () => {
        if (window.autoSave) {
            window.autoSave.clearProgress();
        }
    });

    // ========== 5. APPLICANT TYPE CHANGE HANDLER ==========
    const applicantType = document.getElementById("applicantType");
    const schoolIdInput = document.getElementById("school_id");
    const schoolIdRequired = document.getElementById("schoolIdRequired");

    if (applicantType) {
        applicantType.addEventListener("change", function () {
            if (this.value === "Internal") {
                schoolIdInput.required = true;
                schoolIdInput.disabled = false;
                if (schoolIdRequired) schoolIdRequired.style.display = "";
                schoolIdInput.placeholder = "School ID";
            } else {
                schoolIdInput.required = false;
                schoolIdInput.disabled = true;
                if (schoolIdRequired) schoolIdRequired.style.display = "none";
                schoolIdInput.value = "";
                schoolIdInput.placeholder = "School ID";
            }
        });

        if (applicantType.value === "Internal") {
            schoolIdInput.required = true;
            schoolIdInput.disabled = false;
            if (schoolIdRequired) schoolIdRequired.style.display = "";
        } else {
            schoolIdInput.required = false;
            schoolIdInput.disabled = true;
            if (schoolIdRequired) schoolIdRequired.style.display = "none";
            schoolIdInput.value = "";
        }
    }

    // ========== 6. CLEAR SELECTION BUTTON ==========
    const clearSelectionBtn = document.getElementById("clearSelectionBtn");
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener("click", function (e) {
            e.preventDefault();
            const startDateField = document.getElementById("startDateField");
            const endDateField = document.getElementById("endDateField");
            const startTimeField = document.getElementById("startTimeField");
            const endTimeField = document.getElementById("endTimeField");
            const availabilityResult = document.getElementById("availabilityResult");

            if (startDateField) startDateField.value = "";
            if (endDateField) endDateField.value = "";
            if (startTimeField) startTimeField.selectedIndex = 0;
            if (endTimeField) endTimeField.selectedIndex = 0;
            if (availabilityResult) {
                availabilityResult.textContent = "";
                availabilityResult.style.color = "";
            }

            if (typeof calculateAndDisplayFees === "function") {
                calculateAndDisplayFees();
            }

            if (typeof showToast === "function") {
                showToast("Booking schedule cleared successfully", "success");
            }
        });
    }

    // ========== 7. CONTACT NUMBER VALIDATION ==========
    const contactNumberField = document.getElementById("contactNumberField");
    if (contactNumberField) {
        contactNumberField.addEventListener("input", function (e) {
            this.value = this.value.replace(/\D/g, "");
        });
    }

    // ========== 8. TIME SELECTOR HANDLERS ==========
    const startTimeSelect = document.getElementById("startTimeField");
    if (startTimeSelect) {
        startTimeSelect.addEventListener("change", updateEndTimeOptions);
        setTimeout(updateEndTimeOptions, 100);
    }

    // ========== 9. SCHEDULE FIELD CHANGE LISTENERS ==========
    const scheduleFields = [
        "startDateField",
        "endDateField",
        "startTimeField",
        "endTimeField",
    ];
    scheduleFields.forEach((fieldId) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener("change", function () {
                if (typeof calculateAndDisplayFees === "function") {
                    calculateAndDisplayFees();
                }
            });
        }
    });

    // ========== 10. INITIALIZE FORM ITEMS ==========
    setTimeout(() => {
        console.log("Calling initForm...");
        if (typeof initForm === "function") {
            initForm();
        } else {
            console.error("initForm function not defined");
        }
    }, 100);

    console.log("DOMContentLoaded initialization complete");
});
// ========== AVAILABILITY CHECK ==========
window.checkAvailability = async function () {
    const checkBtn = document.getElementById("checkAvailabilityBtn");
    const originalText = checkBtn.innerHTML;

    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;
        const startDate = document.getElementById("startDateField").value;
        const endDate = document.getElementById("endDateField").value;
        const allDayCheckbox = document.getElementById("allDayField");
        const isAllDay = allDayCheckbox ? allDayCheckbox.checked : false;

        // For all-day events, times are optional
        let startTime, endTime;

        if (isAllDay) {
            startTime = null;
            endTime = null;
        } else {
            startTime = convertTo24Hour(
                document.getElementById("startTimeField").value,
            );
            endTime = convertTo24Hour(
                document.getElementById("endTimeField").value,
            );
        }

        // Validate dates
        if (!startDate || !endDate) {
            showToast("Please select start and end dates", "error");
            return;
        }

        // Validate times for non-all-day events
        if (!isAllDay && (!startTime || !endTime)) {
            showToast("Please select start and end times", "error");
            return;
        }

        if (endDate < startDate) {
            showToast("End date cannot be before start date.", "error");
            return;
        }

        // Show loading state
        checkBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...';
        checkBtn.disabled = true;

        // Get selected items from session
        const response = await fetch("/requisition/get-items", {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch items (HTTP ${response.status})`);
        }

        const data = await response.json();
        const items = data.data?.selected_items || [];

        if (items.length === 0) {
            showToast("Please add items to check availability", "error");
            checkBtn.innerHTML = originalText;
            checkBtn.disabled = false;
            return;
        }

        // Prepare request data with all_day flag
        const requestData = {
            start_date: startDate,
            end_date: endDate,
            start_time: startTime,
            end_time: endTime,
            all_day: isAllDay,
            items: items.map((item) => {
                const itemData = {
                    type: item.type,
                };
                if (item.type === "facility") {
                    itemData.facility_id = item.facility_id || item.id;
                } else {
                    itemData.equipment_id = item.equipment_id || item.id;
                }
                return itemData;
            }),
        };

        // Call API
        const checkResponse = await fetch("/requisition/check-availability", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
            },
            body: JSON.stringify(requestData),
        });

        if (!checkResponse.ok) {
            // Handle 500 errors gracefully
            if (checkResponse.status === 500) {
                showToast(
                    "Server error occurred. Please try again later.",
                    "error",
                );
                return;
            }

            let errorData;
            try {
                errorData = await checkResponse.json();
            } catch {
                errorData = {};
            }

            if (errorData.errors) {
                Object.values(errorData.errors)
                    .flat()
                    .forEach((msg) => showToast(msg, "error"));
            } else if (errorData.message) {
                showToast(errorData.message, "error");
            } else {
                showToast(
                    `Availability check failed (HTTP ${checkResponse.status})`,
                    "error",
                );
            }
            return;
        }

        const result = await checkResponse.json();

        if (!result.success) {
            showToast(result.message || "Availability check failed", "error");
            return;
        }

        const availabilityResult =
            document.getElementById("availabilityResult");
        if (result.data.available) {
            const availabilityMessage = isAllDay
                ? "All-day booking available!"
                : "Time slot is available!";
            availabilityResult.innerHTML = `
        <span class="text-success">
          <i class="bi bi-check-circle-fill" style="margin-right:5px;"></i>
          Available ${isAllDay ? "(All Day)" : ""}
        </span>
      `;
            showToast(availabilityMessage, "success");
        } else {
            availabilityResult.innerHTML = `
        <span class="text-danger">
          <i class="bi bi-x-circle-fill" style="margin-right:5px;"></i>
          ${isAllDay ? "All-day conflict" : "Conflict found"}!
        </span>
      `;

            showConflictModal(result.data.conflict_items, isAllDay);
        }
    } catch (error) {
        // Network error detection
        if (
            error.message.includes("Failed to fetch") ||
            error.message.includes("NetworkError")
        ) {
            showToast(
                "Cannot connect to server. Please check your internet connection.",
                "error",
            );
        } else if (error.message.includes("500")) {
            showToast(
                "Server error occurred. Our team has been notified.",
                "error",
            );
        } else if (error.message.includes("429")) {
            showToast("Too many requests. Please wait a moment.", "error");
        } else if (error.message.includes("403")) {
            showToast("Session expired. Please refresh the page.", "error");
        } else {
            showToast(error.message || "Failed to check availability", "error");
        }
    } finally {
        checkBtn.innerHTML = originalText;
        checkBtn.disabled = false;
    }
};
// Add performance tracking
window._availabilityCheckStart = null;

// Override the function start to track performance
const originalCheckAvailability = window.checkAvailability;
window.checkAvailability = async function () {
    window._availabilityCheckStart = performance.now();
    return originalCheckAvailability.apply(this, arguments);
};

// ========== CONFLICT MODAL FUNCTION ==========
function showConflictModal(conflictItems, isAllDay = false) {
    // Get modal elements
    const modalElement = document.getElementById("conflictModal");
    const conflictItemsList = document.getElementById("conflictItemsList");
    const modalTitle = modalElement?.querySelector(".modal-title");

    if (!conflictItemsList) {
        console.error("Conflict items list element not found");
        showToast(
            `Conflict with: ${conflictItems.map((item) => item.name).join(", ")}`,
            "error",
        );
        return;
    }

    // Update modal title for all-day conflicts
    if (modalTitle) {
        modalTitle.innerHTML = isAllDay
            ? '<i class="bi bi-calendar-day me-2"></i>All-Day Booking Conflicts'
            : '<i class="bi bi-clock me-2"></i>Scheduling Conflicts';
    }

    // Clear previous content
    conflictItemsList.innerHTML = "";

    // Group conflicts by type
    const facilities = conflictItems.filter((item) => item.type === "facility");
    const equipment = conflictItems.filter((item) => item.type === "equipment");

    // Create conflict list HTML
    let htmlContent = "";

    // All-day conflict notice
    if (isAllDay) {
        htmlContent += `
      <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>All-Day Booking:</strong> This will conflict with any existing bookings on the selected dates, regardless of time.
      </div>
    `;
    }

    if (facilities.length > 0) {
        htmlContent += `
      <div class="mb-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-building me-1"></i> Facilities with Conflicts</h6>
        <ul class="list-group">
    `;

        facilities.forEach((item) => {
            // Check if conflict has detailed conflict list
            const hasConflicts = item.conflicts && item.conflicts.length > 0;

            htmlContent += `
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <strong class="text-danger">${item.name}</strong>
              <span class="badge bg-danger-subtle text-danger ms-2">Facility</span>
              ${item.message ? `<div class="text-muted small mt-1">${item.message}</div>` : ""}
            </div>
          </div>
      `;

            // Show detailed conflicts if available
            if (hasConflicts) {
                htmlContent += `
          <div class="mt-2 ps-2 border-start border-3 border-danger">
            <small class="text-muted fw-bold">Existing bookings:</small>
            <ul class="mt-1 mb-0 small">
        `;

                item.conflicts.forEach((conflict) => {
                    // Format conflict date/time with all-day support
                    const isConflictAllDay = conflict.time === "All Day";
                    const conflictDateTime = isConflictAllDay
                        ? `${conflict.date} (All Day)`
                        : `${conflict.date} ${conflict.time}`;

                    htmlContent += `
            <li class="mb-1">
              <i class="bi bi-calendar-event me-1"></i>
              ${conflictDateTime}
              ${isConflictAllDay ? '<span class="badge bg-info-subtle text-info ms-1">All Day</span>' : ""}
            </li>
          `;
                });

                htmlContent += `</ul></div>`;
            } else {
                // Fallback for simple conflict display
                htmlContent += `
          <div class="mt-1 small text-muted">
            <i class="bi bi-calendar-event me-1"></i>
            ${item.conflicting_booking_date || "This facility is already booked"}
            ${item.time ? ` at ${item.time}` : ""}
            ${item.time === "All Day" ? '<span class="badge bg-info-subtle text-info ms-1">All Day</span>' : ""}
          </div>
        `;
            }

            htmlContent += `</li>`;
        });

        htmlContent += "</ul></div>";
    }

    if (equipment.length > 0) {
        htmlContent += `
      <div class="mb-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-tools me-1"></i> Equipment with Conflicts</h6>
        <ul class="list-group">
    `;

        equipment.forEach((item) => {
            htmlContent += `
        <li class="list-group-item d-flex justify-content-between align-items-start">
          <div>
            <strong class="text-danger">${item.name}</strong>
            <span class="badge bg-danger-subtle text-danger ms-2">Equipment</span>
            ${item.message ? `<div class="text-muted small mt-1">${item.message}</div>` : ""}
          </div>
        </li>
      `;
        });

        htmlContent += "</ul></div>";
    }

    // If no conflicts display but we're here, show generic message
    if (facilities.length === 0 && equipment.length === 0) {
        htmlContent += `
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Conflicts detected but no specific items found.
      </div>
    `;
    }

    // Add summary with all-day context
    htmlContent += `
    <div class="alert alert-danger mt-3">
      <div class="d-flex">
        <div class="flex-shrink-0">
          <i class="bi bi-exclamation-circle fs-4"></i>
        </div>
        <div class="flex-grow-1 ms-3">
          <h6 class="alert-heading fw-bold">Total Conflicts: ${conflictItems.length}</h6>
          <p class="mb-2 small fw-medium">What you can do:</p>
          <ul class="mb-0 small ps-3">
            ${
                isAllDay
                    ? `<li>Try a different date range for your all-day booking</li>
               <li>Switch to a regular (timed) booking instead</li>`
                    : `<li>Select a different date/time</li>
               <li>Adjust your schedule to avoid peak hours</li>`
            }
            <li>Remove conflicting items from your selection</li>
            <li>Contact administrators for assistance</li>
          </ul>
        </div>
      </div>
    </div>
  `;

    // Insert into modal
    conflictItemsList.innerHTML = htmlContent;

    // Show the modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Add event listener to close button
    const closeBtn = modalElement.querySelector(".btn-close");
    if (closeBtn) {
        // Remove previous listeners to prevent duplicates
        const newCloseBtn = closeBtn.cloneNode(true);
        closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
        newCloseBtn.addEventListener("click", () => {
            modal.hide();
        });
    }
}

// ========== FEE BREAKDOWN FOR SUMMARY ==========
async function generateFeeBreakdownForSummary() {
    const summaryFees = document.getElementById("summary-fees");

    if (!summaryFees) return;

    // Show loading state
    summaryFees.innerHTML = `
    <div class="fee-loading">
      <div class="loading-overlay">
        <div class="loading-spinner"></div>
        <small>Calculating fees...</small>
      </div>
    </div>
  `;

    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;
        const response = await fetch("/requisition/get-items", {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
        });

        if (!response.ok) throw new Error("Failed to fetch items");
        const data = await response.json();
        const items = data.data?.selected_items || [];

        if (items.length === 0) {
            summaryFees.innerHTML =
                '<div class="text-muted">No items added yet.</div>';
            return;
        }

        // Get schedule information
        const startDate = document.getElementById("startDateField").value;
        const endDate = document.getElementById("endDateField").value;
        const startTime = document.getElementById("startTimeField").value;
        const endTime = document.getElementById("endTimeField").value;

        // Format dates to "January 1, 2025" format
        let formattedStartDate = "";
        let formattedEndDate = "";

        if (startDate) {
            formattedStartDate = new Date(startDate).toLocaleDateString(
                "en-US",
                {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                },
            );
        }

        if (endDate) {
            formattedEndDate = new Date(endDate).toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        }

        // Calculate duration in hours
        let durationHours = 0;
        if (startDate && endDate && startTime && endTime) {
            const startDateTime = new Date(
                `${startDate}T${convertTo24Hour(startTime)}:00`,
            );
            const endDateTime = new Date(
                `${endDate}T${convertTo24Hour(endTime)}:00`,
            );
            durationHours = (endDateTime - startDateTime) / (1000 * 60 * 60);
            durationHours = Math.max(0, durationHours);
        }

        let facilityTotal = 0;
        let equipmentTotal = 0;
        let htmlContent = '<div class="fee-items">';

        // Facilities breakdown
        const facilityItems = items.filter((i) => i.type === "facility");
        if (facilityItems.length > 0) {
            htmlContent +=
                '<div class="fee-section"><p class="mb-3 text-primary">Facilities</p>';
            facilityItems.forEach((item) => {
                let fee = parseFloat(item.base_fee);
                if (item.rate_type === "Per Hour" && durationHours > 0) {
                    fee = fee * durationHours;
                    htmlContent += `
            <div class="fee-item d-flex justify-content-between mb-2">
                <span>${item.name} (${durationHours.toFixed(1)} hrs)</span>
                <div class="text-end">
                    <small>₱${parseFloat(item.base_fee).toLocaleString()}/hr</small>
                    <div><strong>₱${fee.toLocaleString()}</strong></div>
                </div>
            </div>
          `;
                } else {
                    htmlContent += `
            <div class="fee-item d-flex justify-content-between mb-2">
                <span>${item.name}</span>
                <span>₱${fee.toLocaleString()}</span>
            </div>
          `;
                }
                facilityTotal += fee;
            });
            htmlContent += `
        <div class="subtotal d-flex justify-content-between mt-2 pt-2 border-top">
            <strong>Subtotal</strong>
            <strong>₱${facilityTotal.toLocaleString()}</strong>
        </div>
      </div>`;
        }

        // Equipment breakdown
        const equipmentItems = items.filter((i) => i.type === "equipment");
        if (equipmentItems.length > 0) {
            htmlContent +=
                '<div class="fee-section mt-3"><p class="mb-3 text-primary">Equipment</p>';
            equipmentItems.forEach((item) => {
                let unitFee = parseFloat(item.base_fee);
                const quantity = item.quantity || 1;
                let itemTotal = unitFee * quantity;
                if (item.rate_type === "Per Hour" && durationHours > 0) {
                    itemTotal = itemTotal * durationHours;
                    htmlContent += `
            <div class="fee-item d-flex justify-content-between mb-2">
                <span>${item.name} ${quantity > 1 ? `(x${quantity})` : ""} (${durationHours.toFixed(1)} hrs)</span>
                <div class="text-end">
                    <small>₱${unitFee.toLocaleString()}/hr × ${quantity}</small>
                    <div><strong>₱${itemTotal.toLocaleString()}</strong></div>
                </div>
            </div>
          `;
                } else {
                    htmlContent += `
            <div class="fee-item d-flex justify-content-between mb-2">
                <span>${item.name} ${quantity > 1 ? `(x${quantity})` : ""}</span>
                <div class="text-end">
                    <div>₱${unitFee.toLocaleString()} × ${quantity}</div>
                    <strong>₱${itemTotal.toLocaleString()}</strong>
                </div>
            </div>
          `;
                }
                equipmentTotal += itemTotal;
            });
            htmlContent += `
        <div class="subtotal d-flex justify-content-between mt-2 pt-2 border-top">
            <strong>Subtotal</strong>
            <strong>₱${equipmentTotal.toLocaleString()}</strong>
        </div>
      </div>`;
        }

        // Total with prominent dark navy blue background
        const total = facilityTotal + equipmentTotal;
        if (total > 0) {
            htmlContent += `
        <div class="total-fee mt-4 pt-3 border-top">
          <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #003366; border-radius: 8px;">
            <h6 class="mb-0 text-white fw-bold">TOTAL AMOUNT</h6>
            <h5 class="mb-0 text-white fw-bold">₱${total.toLocaleString()}</h5>
          </div>
        </div>
      `;
        } else {
            htmlContent +=
                '<div class="text-muted text-center">No items added yet.</div>';
        }

        htmlContent += "</div>";
        summaryFees.innerHTML = htmlContent;
    } catch (error) {
        console.error("Error generating fee breakdown for summary:", error);
        summaryFees.innerHTML = `
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Error loading fee breakdown. Please try again.
      </div>
    `;
    }
}

// ========== ITEM MANAGEMENT FUNCTIONS ==========
function pluralizeType(type) {
    if (type.toLowerCase() === "facility") return "facilities";
    if (type.toLowerCase() === "equipment") return "equipment";
    return type + "s";
}

function renderItemsList(container, items, type) {
    if (!container) return;

    // If container is showing loading state, clear it first
    container.innerHTML = "";

    if (items.length === 0) {
        container.innerHTML = `
      <div class="empty-message text-center py-4">
        <p class="text-muted mb-0">No ${pluralizeType(type)} added yet.</p>
      </div>
    `;
        return;
    }

    const cardContainer = document.createElement("div");
    cardContainer.className = "row row-cols-1 g-3";

    items.forEach((item) => {
        const card = document.createElement("div");
        card.className = "col";
        const displayImage =
            item.images?.find((img) => img.image_type === "Primary") ||
            item.images?.[0];

        card.innerHTML = `
      <div class="card h-100 border-0 shadow-sm mb-2">
        <div class="card-body p-2 d-flex align-items-center">
          ${
              displayImage
                  ? `
            <img src="${displayImage.image_url}" 
              alt="${item.name}" 
              style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 12px;">
          `
                  : `
            <div style="width: 80px; height: 80px; background: #e9ecef; border-radius: 8px; margin-right: 12px;"></div>
          `
          }
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="mb-1 fw-semibold">${item.name}</h6>
              <button type="button" class="btn btn-sm btn-danger ms-2" 
                onclick="removeSelectedItem(${type === "facility" ? item.facility_id : item.equipment_id}, '${type}')">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <div>
                <span class="badge bg-primary me-2">${item.rate_type || "booking"}</span>
                ${type === "equipment" ? `<span class="badge bg-secondary">Qty: ${item.quantity || 1}</span>` : ""}
              </div>
              <div class="text-end">
                <div class="fw-bold text-success">
                  ₱${parseFloat(item.base_fee * (type === "equipment" ? item.quantity || 1 : 1)).toLocaleString()}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
        cardContainer.appendChild(card);
    });

    container.appendChild(cardContainer);
}

window.renderSelectedItems = async function () {
    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;

        // Show loading state for facilities
        if (facilityList) {
            facilityList.innerHTML = `
        <div class="selected-items-loading">
          <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <small>Loading facilities...</small>
          </div>
        </div>
      `;
        }

        // Show loading state for equipment
        if (equipmentList) {
            equipmentList.innerHTML = `
        <div class="selected-items-loading">
          <div class="loading-overlay">
            <div class="loading-spinner"></div>
            <small>Loading equipment...</small>
          </div>
        </div>
      `;
        }

        const response = await fetch("/requisition/get-items", {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
        });

        if (!response.ok) throw new Error("Failed to fetch items");
        const data = await response.json();
        const items = data.data?.selected_items || [];

        // Render facilities and equipment with actual data
        renderItemsList(
            facilityList,
            items.filter((i) => i.type === "facility"),
            "facility",
        );
        renderItemsList(
            equipmentList,
            items.filter((i) => i.type === "equipment"),
            "equipment",
        );
    } catch (error) {
        console.error("Error rendering selected items:", error);
        showToast("Failed to load selected items", "error");

        // Show error state in both containers
        if (facilityList) {
            facilityList.innerHTML = `
        <div class="alert alert-danger mt-3">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load facilities
        </div>
      `;
        }

        if (equipmentList) {
            equipmentList.innerHTML = `
        <div class="alert alert-danger mt-3">
          <i class="bi bi-exclamation-triangle me-2"></i>
          Failed to load equipment
        </div>
      `;
        }
    }
};

window.calculateAndDisplayFees = async function () {
    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;
        const response = await fetch("/requisition/get-items", {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
        });

        if (!response.ok) throw new Error("Failed to fetch items");
        const data = await response.json();
        const items = data.data?.selected_items || [];
        const feeDisplay = document.getElementById("feeDisplay");

        if (!feeDisplay) return;

        if (items.length === 0) {
            feeDisplay.innerHTML =
                '<div class="text-muted">No items added yet.</div>';
            return;
        }

        // Get schedule information
        const startDate = document.getElementById("startDateField").value;
        const endDate = document.getElementById("endDateField").value;
        const startTime = document.getElementById("startTimeField").value;
        const endTime = document.getElementById("endTimeField").value;

        let durationHours = 0;
        if (startDate && endDate && startTime && endTime) {
            const startDateTime = new Date(
                `${startDate}T${convertTo24Hour(startTime)}:00`,
            );
            const endDateTime = new Date(
                `${endDate}T${convertTo24Hour(endTime)}:00`,
            );
            durationHours = (endDateTime - startDateTime) / (1000 * 60 * 60);
            durationHours = Math.max(0, durationHours);
        }

        let facilityTotal = 0;
        let equipmentTotal = 0;
        let htmlContent = '<div class="fee-items">';

        // Facilities breakdown
        const facilityItems = items.filter((i) => i.type === "facility");
        if (facilityItems.length > 0) {
            htmlContent +=
                '<div class="fee-section"><h6 class="mb-3">Facilities</h6>';
            facilityItems.forEach((item) => {
                let fee = parseFloat(item.base_fee);
                if (item.rate_type === "Per Hour" && durationHours > 0) {
                    fee = fee * durationHours;
                    htmlContent += `
                            <div class="fee-item d-flex justify-content-between mb-2">
                                <span>${item.name} (${durationHours.toFixed(1)} hrs)</span>
                                <div class="text-end">
                                    <small>₱${parseFloat(item.base_fee).toLocaleString()}/hr</small>
                                    <div><strong>₱${fee.toLocaleString()}</strong></div>
                                </div>
                            </div>
                        `;
                } else {
                    htmlContent += `
                            <div class="fee-item d-flex justify-content-between mb-2">
                                <span>${item.name}</span>
                                <span>₱${fee.toLocaleString()}</span>
                            </div>
                        `;
                }
                facilityTotal += fee;
            });
            htmlContent += `
                    <div class="subtotal d-flex justify-content-between mt-2 pt-2 border-top">
                        <strong>Subtotal</strong>
                        <strong>₱${facilityTotal.toLocaleString()}</strong>
                    </div>
                </div>`;
        }

        // Equipment breakdown
        const equipmentItems = items.filter((i) => i.type === "equipment");
        if (equipmentItems.length > 0) {
            htmlContent +=
                '<div class="fee-section mt-3"><h6 class="mb-3">Equipment</h6>';
            equipmentItems.forEach((item) => {
                let unitFee = parseFloat(item.base_fee);
                const quantity = item.quantity || 1;
                let itemTotal = unitFee * quantity;
                if (item.rate_type === "Per Hour" && durationHours > 0) {
                    itemTotal = itemTotal * durationHours;
                    htmlContent += `
                            <div class="fee-item d-flex justify-content-between mb-2">
                                <span>${item.name} ${quantity > 1 ? `(x${quantity})` : ""} (${durationHours.toFixed(1)} hrs)</span>
                                <div class="text-end">
                                    <small>₱${unitFee.toLocaleString()}/hr × ${quantity}</small>
                                    <div><strong>₱${itemTotal.toLocaleString()}</strong></div>
                                </div>
                            </div>
                        `;
                } else {
                    htmlContent += `
                            <div class="fee-item d-flex justify-content-between mb-2">
                                <span>${item.name} ${quantity > 1 ? `(x${quantity})` : ""}</span>
                                <div class="text-end">
                                    <div>₱${unitFee.toLocaleString()} × ${quantity}</div>
                                    <strong>₱${itemTotal.toLocaleString()}</strong>
                                </div>
                            </div>
                        `;
                }
                equipmentTotal += itemTotal;
            });
            htmlContent += `
                    <div class="subtotal d-flex justify-content-between mt-2 pt-2 border-top">
                        <strong>Subtotal</strong>
                        <strong>₱${equipmentTotal.toLocaleString()}</strong>
                    </div>
                </div>`;
        }

        // Total
        const total = facilityTotal + equipmentTotal;
        if (total > 0) {
            htmlContent += `
                    <div class="total-fee d-flex justify-content-between mt-4 pt-3 border-top">
                        <h6 class="mb-0">Total Amount</h6>
                        <h6 class="mb-0">₱${total.toLocaleString()}</h6>
                    </div>
                `;
        } else {
            htmlContent +=
                '<div class="text-muted text-center">No items added yet.</div>';
        }

        htmlContent += "</div>";
        feeDisplay.innerHTML = htmlContent;
    } catch (error) {
        console.error("Error calculating fees:", error);
        const feeDisplay = document.getElementById("feeDisplay");
        if (feeDisplay) {
            feeDisplay.innerHTML =
                '<div class="alert alert-danger">Error loading fee breakdown</div>';
        }
    }
};

window.removeSelectedItem = async function (id, type) {
    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;
        const requestBody = {
            type: type,
            equipment_id: type === "equipment" ? id : undefined,
            facility_id: type === "facility" ? id : undefined,
        };

        const cleanedRequestBody = Object.fromEntries(
            Object.entries(requestBody).filter(([_, v]) => v !== undefined),
        );

        const response = await fetch("/api/requisition/remove-item", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify(cleanedRequestBody),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || "Failed to remove item");
        }

        const result = await response.json();

        if (result.success) {
            await Promise.all([
                window.renderSelectedItems(),
                window.calculateAndDisplayFees(),
            ]);
            showToast(
                `${type.charAt(0).toUpperCase() + type.slice(1)} removed successfully`,
                "success",
            );

            if (typeof updateCartBadge === "function") {
                updateCartBadge();
            }
        } else {
            throw new Error(result.message || "Failed to remove item");
        }
    } catch (error) {
        console.error("Error removing item:", error);
        showToast(error.message || "Failed to remove item", "error");
    }
};

// ========== FORM SUBMISSION FUNCTIONS ==========
window.openTermsModal = function (event) {
    if (event) event.preventDefault();

    // Check if modal already exists
    let modalEl = document.getElementById("termsModal");
    let modalInstance = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;

    // Function to attach event listeners
    const attachModalEventListeners = () => {
        const agreeTerms = document.getElementById("agreeTerms");
        const confirmSubmitBtn = document.getElementById("confirmSubmitBtn");

        if (agreeTerms && confirmSubmitBtn) {
            // Remove any existing listeners first to avoid duplicates
            agreeTerms.replaceWith(agreeTerms.cloneNode(true));
            confirmSubmitBtn.replaceWith(confirmSubmitBtn.cloneNode(true));

            // Re-get references after cloning
            const newAgreeTerms = document.getElementById("agreeTerms");
            const newConfirmSubmitBtn =
                document.getElementById("confirmSubmitBtn");

            // Attach new listeners
            newAgreeTerms.addEventListener("change", function () {
                newConfirmSubmitBtn.disabled = !this.checked;
            });

            newConfirmSubmitBtn.addEventListener("click", async function () {
                await submitForm();
            });

            // Ensure button starts disabled
            newConfirmSubmitBtn.disabled = true;
        }
    };

    if (!modalEl) {
        // Create modal HTML
        const modalHTML = `
          <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="terms-content mb-4" style="max-height: 50vh; overflow-y: auto;">
                    <h6>Booking Terms and Conditions</h6>
                    <ol>
                      <li>All bookings are subject to approval by CPU Administration.</li>
                      <li>Payment must be made within 3 business days after approval.</li>
                      <li>Cancellations must be made at least 5 days before the event.</li>
                      <li>Damage to facilities/equipment will incur additional charges.</li>
                      <li>Alcohol and smoking are strictly prohibited on campus.</li>
                      <li>External users must provide valid identification.</li>
                      <li>CPU reserves the right to cancel bookings for violations.</li>
                    </ol>
                    <div class="form-check mt-3 text-center">
                      <input class="form-check-input me-2" type="checkbox" id="agreeTerms">
                      <label class="form-check-label" for="agreeTerms">
                        I agree to the terms and conditions
                      </label>
                    </div>
                  </div>
                </div>
                <div class="modal-footer justify-content-end">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmSubmitBtn" class="btn btn-primary" disabled>
            <span class="btn-text">Submit Request</span>
            <span class="btn-loading">
              <span class="spinner-border spinner-border-sm me-2" role="status"></span>
              Submitting Request...
            </span>
          </button>
                </div>
              </div>
            </div>
          </div>

              `;

        document.body.insertAdjacentHTML("beforeend", modalHTML);
        modalEl = document.getElementById("termsModal");

        // Initialize new modal instance
        modalInstance = new bootstrap.Modal(modalEl, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });

        // Add event listeners for modal lifecycle
        modalEl.addEventListener("hidden.bs.modal", function () {
            modalInstance.dispose();
            modalEl.remove();
        });

        // Attach event listeners for checkbox and button
        attachModalEventListeners();
    } else if (!modalInstance) {
        // If modal element exists but no instance, create one
        modalInstance = new bootstrap.Modal(modalEl, {
            backdrop: true,
            keyboard: true,
            focus: true,
        });

        // Attach event listeners since they might be missing
        attachModalEventListeners();
    } else {
        // Modal exists and has instance, but ensure listeners are attached
        attachModalEventListeners();
    }

    // Reset checkbox state each time modal opens
    const agreeTerms = document.getElementById("agreeTerms");
    const confirmSubmitBtn = document.getElementById("confirmSubmitBtn");
    if (agreeTerms && confirmSubmitBtn) {
        agreeTerms.checked = false;
        confirmSubmitBtn.disabled = true;
    }

    // Show the modal
    if (modalInstance) {
        modalInstance.show();
    } else {
        console.error("Modal instance could not be created");
        showToast("Failed to open terms modal. Please try again.", "error");
    }
};

window.submitForm = async function () {
    try {
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]',
        ).content;

        const modal = document.getElementById("termsModal");
        const confirmBtn = document.getElementById("confirmSubmitBtn");

        // Show loading state
        confirmBtn.classList.add("loading");
        confirmBtn.disabled = true;

        // Get all_day checkbox value
        const allDayCheckbox = document.getElementById("allDayField");
        const isAllDay = allDayCheckbox ? allDayCheckbox.checked : false;

        // Check required fields (conditional for times based on all_day)
        const requiredFields = [
            {
                name: "first_name",
                element: document.querySelector('input[name="first_name"]'),
            },
            {
                name: "last_name",
                element: document.querySelector('input[name="last_name"]'),
            },
            {
                name: "email",
                element: document.querySelector('input[name="email"]'),
            },
            {
                name: "purpose_id",
                element: document.getElementById("activityPurposeField"),
            },
            {
                name: "start_date",
                element: document.getElementById("startDateField"),
            },
            {
                name: "end_date",
                element: document.getElementById("endDateField"),
            },
        ];

        // Only require time fields if NOT all-day
        if (!isAllDay) {
            requiredFields.push(
                {
                    name: "start_time",
                    element: document.getElementById("startTimeField"),
                },
                {
                    name: "end_time",
                    element: document.getElementById("endTimeField"),
                },
            );
        }

        const missingFields = [];
        requiredFields.forEach((field) => {
            if (
                !field.element ||
                !field.element.value ||
                field.element.value.trim() === ""
            ) {
                missingFields.push(field.name);
            }
        });

        if (missingFields.length > 0) {
            throw new Error(
                `Required fields missing: ${missingFields.join(", ")}`,
            );
        }

        // Get selected extra services
        const extraServices = [];
        const extraServiceCheckboxes = document.querySelectorAll(
            'input[name="extra_services[]"]:checked',
        );
        extraServiceCheckboxes.forEach((checkbox) => {
            extraServices.push(parseInt(checkbox.value));
        });

        // Prepare form data with all_day support
        const formData = {
            event_title:
                document.querySelector('input[name="event_title"]')?.value ||
                "",
            event_details:
                document.querySelector('textarea[name="event_details"]')
                    ?.value || null,
            start_date: document.getElementById("startDateField").value,
            end_date: document.getElementById("endDateField").value,
            start_time: isAllDay
                ? null
                : convertTo24Hour(
                      document.getElementById("startTimeField").value,
                  ),
            end_time: isAllDay
                ? null
                : convertTo24Hour(
                      document.getElementById("endTimeField").value,
                  ),
            all_day: isAllDay,
            purpose_id: document.getElementById("activityPurposeField").value,
            num_participants:
                document.querySelector('input[name="num_participants"]')
                    ?.value || 1,
            num_chairs:
                document.querySelector('input[name="num_chairs"]')?.value || 0,
            num_tables:
                document.querySelector('input[name="num_tables"]')?.value || 0,
            num_microphones:
                document.querySelector('input[name="num_microphones"]')
                    ?.value || 0,
            additional_requests:
                document.querySelector('textarea[name="additional_requests"]')
                    ?.value || "",
            event_documents_url:
                document.getElementById("event_documents_url")?.value || null,
            event_documents_public_id:
                document.getElementById("event_documents_public_id")?.value ||
                null,
            first_name: document.querySelector('input[name="first_name"]')
                .value,
            last_name: document.querySelector('input[name="last_name"]').value,
            email: document.querySelector('input[name="email"]').value,
            contact_number:
                document.querySelector('input[name="contact_number"]')?.value ||
                null,
            organization_name:
                document.querySelector('input[name="organization_name"]')
                    ?.value || null,
            user_type: document.getElementById("applicantType").value,
            school_id:
                document.getElementById("applicantType").value === "Internal"
                    ? document.querySelector('input[name="school_id"]')
                          ?.value || null
                    : null,
            extra_services: extraServices.length > 0 ? extraServices : null,
        };

        const submitResponse = await fetch("/requisition/submit", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
            },
            body: JSON.stringify(formData),
            credentials: "include",
        });

        // Check if it's a 500 error and log everything
        if (submitResponse.status === 500) {
            console.error("=== SERVER 500 ERROR DETECTED ===");
            console.error("Request that caused 500:", {
                url: "/requisition/submit",
                method: "POST",
                formData: formData,
                timestamp: new Date().toISOString(),
            });
        }

        // FIRST, check if the response is JSON by looking at Content-Type header
        const contentType = submitResponse.headers.get("content-type");

        let result;
        let responseText;

        if (contentType && contentType.includes("application/json")) {
            result = await submitResponse.json();
        } else {
            responseText = await submitResponse.text();

            // For 500 errors, log the full HTML response
            if (submitResponse.status === 500) {
                // Check for specific Laravel error patterns
                if (
                    responseText.includes(
                        "Symfony\\Component\\HttpKernel\\Exception\\HttpException",
                    )
                ) {
                    console.error("Laravel HTTP Exception detected");
                }
                if (
                    responseText.includes(
                        "Illuminate\\Database\\Eloquent\\ModelNotFoundException",
                    )
                ) {
                    console.error("Model not found exception detected");
                }
                if (responseText.includes("SQLSTATE")) {
                    console.error("SQL Database error detected");
                    const sqlMatch = responseText.match(
                        /SQLSTATE\[[^\]]+\]: [^<]+/,
                    );
                    if (sqlMatch) console.error("SQL Error:", sqlMatch[0]);
                }
                if (
                    responseText.includes("Method [") &&
                    responseText.includes("] does not exist")
                ) {
                    console.error("Method not found exception detected");
                }
                if (responseText.includes("Trying to get property")) {
                    console.error("Null property access detected");
                }
                if (responseText.includes("Undefined variable")) {
                    console.error("Undefined variable detected");
                }
                if (responseText.includes("Class '")) {
                    console.error("Class not found detected");
                }
            }

            // Try to parse as JSON anyway (in case Content-Type is wrong)
            try {
                result = JSON.parse(responseText);
                console.log("Successfully parsed as JSON:", result);
            } catch (parseError) {
                console.log(
                    "Could not parse as JSON, treating as text response",
                );
                result = {
                    success: false,
                    message: `Server returned non-JSON response (${submitResponse.status}): ${responseText.substring(0, 100)}...`,
                };
            }
        }

        if (!submitResponse.ok) {
            console.log("=== RESPONSE NOT OK ===");
            console.log("Response status:", submitResponse.status);
            console.log("Response status text:", submitResponse.statusText);

            // Build error message
            let errorMessage = `Submission failed with status: ${submitResponse.status}`;

            if (result && result.message) {
                errorMessage = result.message;
                console.log("Error message from response:", result.message);
            } else if (responseText) {
                // Try to extract error from HTML
                const errorMatch =
                    responseText.match(/<title[^>]*>([^<]+)<\/title>/i) ||
                    responseText.match(/<h1[^>]*>([^<]+)<\/h1>/i) ||
                    responseText.match(/<p[^>]*>([^<]+)<\/p>/i);

                if (errorMatch && errorMatch[1]) {
                    errorMessage = `Server Error: ${errorMatch[1]}`;
                    console.log("Extracted error from HTML:", errorMatch[1]);
                }
            }

            // Check for validation errors
            if (result && result.errors) {
                console.log("Validation Errors:", result.errors);
                const errorMessages = Object.values(result.errors)
                    .flat()
                    .join("\n");
                errorMessage = `Validation failed:\n${errorMessages}`;
            }

            // Log the complete error context
            console.error("=== COMPLETE ERROR CONTEXT ===");
            console.error("Status:", submitResponse.status);
            console.error("Message:", errorMessage);
            console.error("Result:", result);
            console.error(
                "Response Text Preview:",
                responseText?.substring(0, 200),
            );

            throw new Error(errorMessage);
        }

        console.log("=== SUCCESS RESPONSE ===");
        console.log("Result:", result);

        if (!result.success) {
            console.error("Submission returned success=false:", result.message);
            throw new Error(result.message || "Submission failed");
        }

        console.log("=== FORM SUBMISSION SUCCESSFUL ===");
        console.log("Request ID:", result.data?.request_id);
        console.log("Access Code:", result.data?.access_code);
        console.log("All Day:", isAllDay);

        // Hide the terms modal
        const termsModalInstance = bootstrap.Modal.getInstance(modal);
        if (termsModalInstance) {
            termsModalInstance.hide();
        }

        // Format dates in a readable way
        const startDateObj = new Date(formData.start_date + "T12:00:00"); // Add T to avoid timezone issues
        const endDateObj = new Date(formData.end_date + "T12:00:00");

        const formattedStartDate = startDateObj.toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        });

        const formattedEndDate = endDateObj.toLocaleDateString("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        });

        let scheduleText = "";

        if (isAllDay) {
            if (formData.start_date === formData.end_date) {
                scheduleText = `${formattedStartDate} (All Day)`;
            } else {
                scheduleText = `${formattedStartDate} - ${formattedEndDate} (All Day)`;
            }
        } else {
            // Format times from 24h to 12h format
            const formatTimeForDisplay = (time24) => {
                if (!time24) return "";
                const [hours, minutes] = time24.split(":");
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? "pm" : "am";
                const hour12 = hour % 12 || 12;
                return `${hour12}:${minutes}${ampm}`;
            };

            const startTimeFormatted = formatTimeForDisplay(
                formData.start_time,
            );
            const endTimeFormatted = formatTimeForDisplay(formData.end_time);

            if (formData.start_date === formData.end_date) {
                scheduleText = `${formattedStartDate}, ${startTimeFormatted} - ${endTimeFormatted}`;
            } else {
                scheduleText = `${formattedStartDate}, ${startTimeFormatted} - ${formattedEndDate}, ${endTimeFormatted}`;
            }
        }

        // Show success details with formatted schedule
        document.getElementById("successDetails").innerHTML = `
    <div class="text-start mt-2">
        <p class="mb-1"><strong>Request ID:</strong> ${result.data.request_id}</p>
        <p class="mb-1"><strong>Access Code:</strong> <span class="badge bg-primary">${result.data.access_code}</span></p>
        <p class="mb-0"><strong>Schedule:</strong><br>${scheduleText}</p>
    </div>
`;

        document.getElementById("userEmail").textContent = formData.email;

        // Show the success modal
        const successModal = new bootstrap.Modal(
            document.getElementById("successModal"),
        );
        successModal.show();

        // ========== CLEAR ALL STORAGE DATA ==========
        console.log("=== CLEARING STORAGE ===");
        // Clear session storage
        try {
            await fetch("/requisition/clear-session", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
            });
            console.log("Server session cleared");
        } catch (clearError) {
            console.warn("Failed to clear server session:", clearError);
        }

        // Clear local storage
        if (typeof localStorage !== "undefined") {
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (
                    key &&
                    (key.includes("reservation_form") || key === "request_info")
                ) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach((key) => localStorage.removeItem(key));
            console.log("LocalStorage cleared:", keysToRemove);
        }

        // Clear session storage
        if (typeof sessionStorage !== "undefined") {
            const sessionKeys = [
                "selected_items",
                "reservationFormData",
                "request_info",
            ];
            sessionKeys.forEach((key) => sessionStorage.removeItem(key));
            console.log("SessionStorage cleared");
        }

        console.log("=== FORM RESET COMPLETE ===");
    } catch (error) {
        console.error("=== FORM SUBMISSION ERROR ===");
        console.error("Error Type:", error.constructor.name);
        console.error("Error Message:", error.message);
        console.error("Error Stack:", error.stack);

        // Additional error debugging
        console.error("Error details:", {
            name: error.name,
            fileName: error.fileName,
            lineNumber: error.lineNumber,
            columnNumber: error.columnNumber,
        });

        showToast(error.message || "Failed to submit form", "error");

        // Reset button state
        const confirmBtn = document.getElementById("confirmSubmitBtn");
        if (confirmBtn) {
            confirmBtn.classList.remove("loading");
            confirmBtn.disabled = false;
        }
    } finally {
        console.log("=== SUBMIT FORM FINISHED ===");
    }
};

// ========== FILE UPLOAD FUNCTIONS ==========

async function uploadToCloudinary(input) {
    const file = input.files[0];
    if (!file) return;

    const progressBar = document.getElementById("progressBar");
    const uploadProgress = document.getElementById("uploadProgress");
    uploadProgress.classList.remove("d-none");

    const formData = new FormData();
    formData.append("file", file);
    formData.append("upload_preset", "event-documents"); // Changed from 'formal-letters'
    formData.append("folder", "user-uploads/event-documents"); // Changed from 'user-letters'

    if (file.type === "application/pdf") {
        formData.append("resource_type", "raw");
    } else {
        formData.append("resource_type", "auto");
    }

    try {
        const response = await fetch(
            `https://api.cloudinary.com/v1_1/dn98ntlkd/auto/upload`,
            {
                method: "POST",
                body: formData,
            },
        );

        if (!response.ok) {
            throw new Error("Upload failed with status: " + response.status);
        }

        const data = await response.json();
        console.log("Upload successful:", data);

        document.getElementById("event_documents_url").value = data.secure_url;
        document.getElementById("event_documents_public_id").value =
            data.public_id;

        showToast("File uploaded successfully!", "success");

        // Get the button ID dynamically from the input's associated button
        const buttonId =
            input.id === "eventDocuments"
                ? "removeEventDocumentsBtn"
                : "removeAttachLetterBtn";
        document.getElementById(buttonId)?.classList.remove("d-none");
    } catch (error) {
        console.error("Upload error:", error);
        showToast("File upload failed: " + error.message, "error");
        input.value = "";
    } finally {
        uploadProgress.classList.add("d-none");
        progressBar.style.width = "0%";
    }
}

function removeFile(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);

    input.value = "";
    document.getElementById("event_documents_url").value = "";
    document.getElementById("event_documents_public_id").value = "";
    button.classList.add("d-none");

    showToast("File removed", "info");
}

// ========== FACILITY MODAL ==========
let facilityCurrentPage = 1;
let facilitySearch = "";
let facilityCategoryFilter = "";
let selectedFacilities = [];

async function openFacilityModal() {
    const btn = document.getElementById("addFacilityBtn");
    btn.disabled = true;
    btn.classList.add("opacity-50");
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Add item';

    const modal = new bootstrap.Modal(document.getElementById("facilityModal"));
    facilityCurrentPage = 1;
    facilitySearch = "";
    facilityCategoryFilter = "";
    selectedFacilities = [];
    document.getElementById("facilitySearch").value = "";
    document.getElementById("facilityCategoryFilter").value = "";
    
    const countElement = document.getElementById("facilityCount");
    if (countElement) countElement.textContent = "0";
    
    await loadFacilityCategories();
    await loadFacilities();
    modal.show();

    btn.disabled = false;
    btn.classList.remove("opacity-50");
    btn.innerHTML = '<i class="bi bi-plus"></i> Add item';
}

async function loadFacilityCategories() {
    // Check cache first
    const cached = cache.get('facilityCategories');
    if (cached) {
        const select = document.getElementById("facilityCategoryFilter");
        select.innerHTML = '<option value="">All Categories</option>';
        cached.forEach(category => {
            const option = document.createElement('option');
            option.value = `category:${category.category_id}`;
            option.textContent = category.category_name;
            select.appendChild(option);
            if (category.subcategories && category.subcategories.length > 0) {
                category.subcategories.forEach(sub => {
                    const subOption = document.createElement('option');
                    subOption.value = `subcategory:${sub.subcategory_id}`;
                    subOption.textContent = `-- ${sub.subcategory_name}`;
                    select.appendChild(subOption);
                });
            }
        });
        return;
    }

    try {
        const response = await fetch(`/api/facility-categories/venues`);
        const data = await response.json();
        cache.set('facilityCategories', data);
        
        const select = document.getElementById("facilityCategoryFilter");
        select.innerHTML = '<option value="">All Categories</option>';
        data.forEach(category => {
            const option = document.createElement('option');
            option.value = `category:${category.category_id}`;
            option.textContent = category.category_name;
            select.appendChild(option);
            if (category.subcategories && category.subcategories.length > 0) {
                category.subcategories.forEach(sub => {
                    const subOption = document.createElement('option');
                    subOption.value = `subcategory:${sub.subcategory_id}`;
                    subOption.textContent = `-- ${sub.subcategory_name}`;
                    select.appendChild(subOption);
                });
            }
        });
    } catch (error) {
        console.error('Error loading facility categories:', error);
    }
}

async function loadFacilities(forceRefresh = false) {
    const container = document.getElementById("facilityListContainer");
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div></div>';

    try {
        const categoryFilter = document.getElementById("facilityCategoryFilter")?.value || "";
        const cacheKey = `facilities_${facilityCurrentPage}_${facilitySearch}_${categoryFilter}`;

        if (!forceRefresh) {
            const cached = cache.get('facilities');
            if (cached && cached[cacheKey]) {
                buildFacilityList(cached[cacheKey]);
                return;
            }
        }

        const params = new URLSearchParams({
            page: facilityCurrentPage,
            search: facilitySearch,
            filter: categoryFilter,
        });

        const response = await fetch(`/api/requisition/facilities/with-selected?${params}`);
        const data = await response.json();

        const facilitiesCache = cache.get('facilities') || {};
        facilitiesCache[cacheKey] = data;
        cache.set('facilities', facilitiesCache);

        buildFacilityList(data);
    } catch (error) {
        console.error('Error loading facilities:', error);
        container.innerHTML = '<div class="text-danger text-center py-3">Failed to load facilities</div>';
    }
}

function buildFacilityList(data) {
    const container = document.getElementById("facilityListContainer");
    const selectedItemIds = data.selected_ids || [];
    const parentNames = data.parent_names || {};

    if (data.data.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-3">No facilities found.</div>';
        return;
    }

    // Get all facility IDs that are parents (have children)
    const parentIds = new Set();
    data.data.forEach(f => {
        if (f.parent_facility_id) {
            parentIds.add(f.parent_facility_id);
        }
    });

    // Group facilities by parent_facility_id
    const grouped = {};
    const orphans = [];

    data.data.forEach(f => {
        if (f.parent_facility_id) {
            if (!grouped[f.parent_facility_id]) {
                grouped[f.parent_facility_id] = [];
            }
            grouped[f.parent_facility_id].push(f);
        } else {
            orphans.push(f);
        }
    });
let html = '';

// Render orphan facilities (no parent) - these are selectable
orphans.forEach(f => {
    // Check if this orphan is actually a parent (has children)
    const isParent = parentIds.has(f.facility_id);
    // If it has children, skip it (it will be rendered as a parent container below)
    if (isParent) {
        // Skip adding to orphans, it will be handled as a parent container
        return;
    }

    const isSelected = selectedFacilities.includes(f.facility_id);
    const isInForm = selectedItemIds.includes(f.facility_id);
    const isDisabled = isInForm || isSelected;

    html += `
        <div
            class="d-flex align-items-center p-3 mb-2 facility-item ${isDisabled ? 'opacity-50' : ''} ${isSelected ? 'selected' : ''}"
            data-id="${f.facility_id}"
            style="
                cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
                border-radius: 12px;
                border: 1px solid ${isSelected ? 'rgba(11, 45, 114, 0.5)' : 'rgba(0, 0, 0, 0.06)'};
                background: ${isSelected ? 'rgba(11, 45, 114, 0.15)' : '#ffffff'};
                backdrop-filter: ${isSelected ? 'blur(4px)' : 'none'};
                color: ${isSelected ? '#0b2d72' : '#212529'};
                transition: 0.2s ease;
                box-shadow: ${isSelected ? '0 2px 12px rgba(11, 45, 114, 0.15)' : 'none'};
            "
        >
            <div class="flex-grow-1">
                <strong>${f.facility_name}</strong>
                <small class="${isSelected ? 'text-secondary' : 'text-muted'} d-block">
                    ${f.facility_code}
                </small>
                ${isInForm ? '<span class="badge bg-success ms-2">Already Added</span>' : ''}
            </div>
            ${isSelected ? '<i class="bi bi-check-circle-fill text-primary ms-2" style="color: #0b2d72 !important;"></i>' : ''}
        </div>
    `;
});

// Render parent buildings with children (parent is NOT selectable, only children are)
Object.keys(grouped).forEach(parentId => {
    const children = grouped[parentId];
    const parentName = parentNames[parentId] || 'Unknown Building';

    html += `
        <div class="border-bottom">
            <div class="d-flex align-items-center p-2 parent-item" style="cursor: pointer; background-color: #f8f9fa; border-radius: 8px; margin-bottom: 4px;">
                <span class="toggle-icon me-2" data-parent="${parentId}" style="cursor: pointer; font-weight: bold;">
                    <i class="bi bi-chevron-down"></i>
                </span>
                <strong style="color: #0b2d72;">${parentName}</strong>
                <span class="badge bg-secondary ms-2">${children.length}</span>
            </div>
            <div class="child-container" data-parent="${parentId}" style="padding-left: 20px;">
    `;

    children.forEach(f => {
        const isSelected = selectedFacilities.includes(f.facility_id);
        const isInForm = selectedItemIds.includes(f.facility_id);
        const isDisabled = isInForm || isSelected;

        html += `
            <div
                class="d-flex align-items-center p-3 mb-2 facility-item ${isDisabled ? 'opacity-50' : ''} ${isSelected ? 'selected' : ''}"
                data-id="${f.facility_id}"
                style="
                    cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
                    border-radius: 12px;
                    border: 1px solid ${isSelected ? 'rgba(11, 45, 114, 0.5)' : 'rgba(0, 0, 0, 0.06)'};
                    background: ${isSelected ? 'rgba(1, 86, 255, 0.15)' : '#ffffff'};
                    backdrop-filter: ${isSelected ? 'blur(4px)' : 'none'};
                    color: ${isSelected ? '#0b2d72' : '#212529'};
                    transition: 0.2s ease;
                    box-shadow: ${isSelected ? '0 2px 12px rgba(11, 45, 114, 0.15)' : 'none'};
                "
            >
                <div class="flex-grow-1">
                    <span class="text-muted me-2">—</span>
                    <strong>${f.facility_name}</strong>
                    <small class="${isSelected ? 'text-secondary' : 'text-muted'} d-block" style="padding-left: 20px;">
                        ${f.facility_code}
                    </small>
                    ${isInForm ? '<span class="badge bg-success ms-2">Already Added</span>' : ''}
                </div>
                ${isSelected ? '<i class="bi bi-check-circle-fill text-primary ms-2" style="color: #0b2d72 !important;"></i>' : ''}
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;
});

container.innerHTML = html;

    // Toggle functionality for parent items
    document.querySelectorAll('.parent-item').forEach(parentItem => {
        parentItem.addEventListener('click', function(e) {
            const parentId = this.querySelector('.toggle-icon')?.dataset.parent;
            if (!parentId) return;

            const childContainer = document.querySelector(`.child-container[data-parent="${parentId}"]`);
            const icon = this.querySelector('.toggle-icon i');

            if (childContainer) {
                if (childContainer.style.display === 'none') {
                    childContainer.style.display = 'block';
                    icon.className = 'bi bi-chevron-down';
                } else {
                    childContainer.style.display = 'none';
                    icon.className = 'bi bi-chevron-right';
                }
            }
        });

        const toggleIcon = parentItem.querySelector('.toggle-icon');
        if (toggleIcon) {
            toggleIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                const parentId = this.dataset.parent;
                const childContainer = document.querySelector(`.child-container[data-parent="${parentId}"]`);
                const icon = this.querySelector('i');

                if (childContainer) {
                    if (childContainer.style.display === 'none') {
                        childContainer.style.display = 'block';
                        icon.className = 'bi bi-chevron-down';
                    } else {
                        childContainer.style.display = 'none';
                        icon.className = 'bi bi-chevron-right';
                    }
                }
            });
        }
    });

    // Click handlers for facility items (only children and orphans without children)
document.querySelectorAll('.facility-item').forEach(item => {
    item.addEventListener('click', function() {
        const id = parseInt(this.dataset.id);

        if (selectedItemIds.includes(id)) {
            showToast("This facility is already in your form", "info");
            return;
        }

        const index = selectedFacilities.indexOf(id);

        if (index > -1) {
            selectedFacilities.splice(index, 1);
            this.classList.remove('selected');
            // Reset to unselected styles
            this.style.backgroundColor = '#ffffff';
            this.style.color = '#212529';
            this.style.border = '1px solid rgba(0, 0, 0, 0.06)';
            this.style.boxShadow = 'none';
            this.style.backdropFilter = 'none';
            this.style.borderRadius = '12px';
            this.style.cursor = 'pointer';
            
            const checkIcon = this.querySelector('.bi-check-circle-fill');
            if (checkIcon) checkIcon.remove();
            
            const smallElements = this.querySelectorAll('small');
            smallElements.forEach(el => {
                el.classList.remove('text-secondary');
                el.classList.add('text-muted');
            });
        } else {
            selectedFacilities.push(id);
            this.classList.add('selected');
            // Set selected styles with glassy blue
            this.style.backgroundColor = 'rgba(11, 45, 114, 0.15)';
            this.style.color = '#0b2d72';
            this.style.border = '1px solid rgba(11, 45, 114, 0.5)';
            this.style.boxShadow = '0 2px 12px rgba(11, 45, 114, 0.15)';
            this.style.backdropFilter = 'blur(4px)';
            this.style.borderRadius = '12px';
            this.style.cursor = 'pointer';
            
            if (!this.querySelector('.bi-check-circle-fill')) {
                const icon = document.createElement('i');
                icon.className = 'bi bi-check-circle-fill text-primary ms-2';
                icon.style.color = '#0b2d72 !important';
                this.appendChild(icon);
            }
            
            const smallElements = this.querySelectorAll('small');
            smallElements.forEach(el => {
                el.classList.remove('text-muted');
                el.classList.add('text-secondary');
            });
        }
        const countElement = document.getElementById("facilityCount");
        if (countElement) countElement.textContent = selectedFacilities.length;
    });
});
    const pagination = document.getElementById("facilityPagination");
    pagination.innerHTML = `
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item ${data.current_page === 1 ? "disabled" : ""}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); facilityCurrentPage = ${data.current_page - 1}; loadFacilities();">Previous</a>
                </li>
                <li class="page-item active"><span class="page-link">${data.current_page} / ${data.last_page}</span></li>
                <li class="page-item ${data.current_page === data.last_page ? "disabled" : ""}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); facilityCurrentPage = ${data.current_page + 1}; loadFacilities();">Next</a>
                </li>
            </ul>
        </nav>
    `;
}

async function batchAddFacilities() {
    if (selectedFacilities.length === 0) {
        showToast("Please select at least one facility", "error");
        return;
    }

    const btn = document.getElementById("batchAddFacilitiesBtn");
    btn.disabled = true;
    btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

    try {
        const items = selectedFacilities.map((id) => ({
            type: "facility",
            facility_id: id,
        }));

        const response = await fetch("/api/requisition/batch-add-items", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ items: items }),
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, "success");
            await Promise.all([
                renderSelectedItems(),
                calculateAndDisplayFees(),
            ]);

            // Reset button
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';

            // Reset selections
            selectedFacilities = [];

            // Close modal FIRST
            const modal = bootstrap.Modal.getInstance(
                document.getElementById("facilityModal"),
            );
            if (modal) modal.hide();

            // Clear cache and reload AFTER modal is closed
            cache.clear('facilities');
            await loadFacilities(true);
        } else {
            showToast(result.message || "Failed to add facilities", "error");
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';
        }
    } catch (error) {
        console.error("Batch add facilities error:", error);
        showToast("Error adding facilities", "error");
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';
    }
}

// ========== EQUIPMENT MODAL ==========
let equipmentCurrentPage = 1;
let equipmentSearch = "";
let selectedEquipment = [];

async function openEquipmentModal() {
    const btn = document.getElementById("addEquipmentBtn");
    btn.disabled = true;
    btn.classList.add("opacity-50");
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Add item';

    const modal = new bootstrap.Modal(document.getElementById("equipmentModal"));
    equipmentCurrentPage = 1;
    equipmentSearch = "";
    equipmentCategoryFilter = "";
    selectedEquipment = [];
    document.getElementById("equipmentSearch").value = "";
    document.getElementById("equipmentCategoryFilter").value = "";
    
    const countElement = document.getElementById("equipmentCount");
    if (countElement) countElement.textContent = "0";
    
    await loadEquipmentCategories();
    await loadEquipment();
    modal.show();

    btn.disabled = false;
    btn.classList.remove("opacity-50");
    btn.innerHTML = '<i class="bi bi-plus"></i> Add item';
}

async function loadEquipmentCategories() {
    // Check cache first
    const cached = cache.get('equipmentCategories');
    if (cached) {
        const select = document.getElementById("equipmentCategoryFilter");
        select.innerHTML = '<option value="">All Categories</option>';
        cached.forEach(category => {
            const option = document.createElement('option');
            option.value = `category:${category.category_id}`;
            option.textContent = category.category_name;
            select.appendChild(option);
        });
        return;
    }

    try {
        const response = await fetch(`/api/equipment-categories`);
        const data = await response.json();
        cache.set('equipmentCategories', data);
        
        const select = document.getElementById("equipmentCategoryFilter");
        select.innerHTML = '<option value="">All Categories</option>';
        data.forEach(category => {
            const option = document.createElement('option');
            option.value = `category:${category.category_id}`;
            option.textContent = category.category_name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading equipment categories:', error);
    }
}

async function loadEquipment(forceRefresh = false) {
    const container = document.getElementById("equipmentListContainer");
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border"></div></div>';

    try {
        const filter = document.getElementById("equipmentCategoryFilter")?.value || "";
        const cacheKey = `equipment_${equipmentCurrentPage}_${equipmentSearch}_${filter}`;

        if (!forceRefresh) {
            const cached = cache.get('equipment');
            if (cached && cached[cacheKey]) {
                buildEquipmentList(cached[cacheKey]);
                return;
            }
        }

        const params = new URLSearchParams({
            page: equipmentCurrentPage,
            search: equipmentSearch,
            filter: filter,
        });

        const response = await fetch(`/api/requisition/equipment/with-selected?${params}`);
        const data = await response.json();

        const equipmentCache = cache.get('equipment') || {};
        equipmentCache[cacheKey] = data;
        cache.set('equipment', equipmentCache);

        buildEquipmentList(data);
    } catch (error) {
        console.error('Error loading equipment:', error);
        container.innerHTML = '<div class="text-danger text-center py-3">Failed to load equipment</div>';
    }
}

function buildEquipmentList(data) {
    const container = document.getElementById("equipmentListContainer");
    const selectedItemIds = data.selected_ids || [];
    const filtered = data.data || [];

    if (filtered.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-3">No equipment found.</div>';
        return;
    }

    let html = '';

    filtered.forEach(e => {
        const isUnavailable = e.status_name === "Unavailable" || e.status_name === "Under Maintenance";
        const isSelected = selectedEquipment.includes(e.equipment_id);
        const isInForm = selectedItemIds.includes(e.equipment_id);
        const isDisabled = isUnavailable || isInForm || isSelected;

        if (isUnavailable) {
            html += `
                <div class="d-flex align-items-center p-3 mb-2 opacity-50" style="border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); background: #f8f9fa;">
                    <div class="flex-grow-1">
                        <strong>${e.equipment_name}</strong>
                        <span class="badge bg-warning ms-2">${e.status_name}</span>
                    </div>
                    <span class="text-muted small">Unavailable</span>
                </div>
            `;
            return;
        }

        html += `
            <div
                class="d-flex align-items-center p-3 mb-2 equipment-item ${isDisabled ? 'opacity-50' : ''} ${isSelected ? 'selected' : ''}"
                data-id="${e.equipment_id}"
                style="
                    cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
                    border-radius: 12px;
                    border: 1px solid ${isSelected ? 'rgba(11, 45, 114, 0.5)' : 'rgba(0, 0, 0, 0.06)'};
                    background: ${isSelected ? 'rgba(11, 45, 114, 0.15)' : '#ffffff'};
                    backdrop-filter: ${isSelected ? 'blur(4px)' : 'none'};
                    color: ${isSelected ? '#0b2d72' : '#212529'};
                    transition: 0.2s ease;
                    box-shadow: ${isSelected ? '0 2px 12px rgba(11, 45, 114, 0.15)' : 'none'};
                "
            >
                <div class="flex-grow-1">
                    <strong>${e.equipment_name}</strong>
                    <span class="badge ${e.status_name === "Available" ? "bg-success" : "bg-warning"} ms-2">${e.status_name}</span>
                    ${isInForm ? '<span class="badge bg-success ms-2">Already Added</span>' : ''}
                </div>
                ${isSelected ? '<i class="bi bi-check-circle-fill text-primary ms-2" style="color: #0b2d72 !important;"></i>' : ''}
            </div>
        `;
    });

    container.innerHTML = html;

    document.querySelectorAll('.equipment-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);

            if (selectedItemIds.includes(id)) {
                showToast("This equipment is already in your form", "info");
                return;
            }

            const index = selectedEquipment.indexOf(id);

            if (index > -1) {
                selectedEquipment.splice(index, 1);
                this.classList.remove('selected');
                // Reset to unselected styles
                this.style.backgroundColor = '#ffffff';
                this.style.color = '#212529';
                this.style.border = '1px solid rgba(0, 0, 0, 0.06)';
                this.style.boxShadow = 'none';
                this.style.backdropFilter = 'none';
                this.style.borderRadius = '12px';
                this.style.cursor = 'pointer';
                
                const checkIcon = this.querySelector('.bi-check-circle-fill');
                if (checkIcon) checkIcon.remove();
            } else {
                selectedEquipment.push(id);
                this.classList.add('selected');
                // Set selected styles with glassy blue
                this.style.backgroundColor = 'rgba(11, 45, 114, 0.15)';
                this.style.color = '#0b2d72';
                this.style.border = '1px solid rgba(11, 45, 114, 0.5)';
                this.style.boxShadow = '0 2px 12px rgba(11, 45, 114, 0.15)';
                this.style.backdropFilter = 'blur(4px)';
                this.style.borderRadius = '12px';
                this.style.cursor = 'pointer';
                
                if (!this.querySelector('.bi-check-circle-fill')) {
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-check-circle-fill text-primary ms-2';
                    icon.style.color = '#0b2d72 !important';
                    this.appendChild(icon);
                }
            }
            const countElement = document.getElementById("equipmentCount");
            if (countElement) countElement.textContent = selectedEquipment.length;
        });
    });

    const pagination = document.getElementById("equipmentPagination");
    pagination.innerHTML = `
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item ${data.current_page === 1 ? "disabled" : ""}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); equipmentCurrentPage = ${data.current_page - 1}; loadEquipment();">Previous</a>
                </li>
                <li class="page-item active"><span class="page-link">${data.current_page} / ${data.last_page}</span></li>
                <li class="page-item ${data.current_page === data.last_page ? "disabled" : ""}">
                    <a class="page-link" href="#" onclick="event.preventDefault(); equipmentCurrentPage = ${data.current_page + 1}; loadEquipment();">Next</a>
                </li>
            </ul>
        </nav>
    `;
}

async function batchAddEquipment() {
    if (selectedEquipment.length === 0) {
        showToast("Please select at least one equipment item", "error");
        return;
    }

    const btn = document.getElementById("batchAddEquipmentBtn");
    btn.disabled = true;
    btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

    try {
        const items = selectedEquipment.map((id) => ({
            type: "equipment",
            equipment_id: id,
            quantity: 1,
        }));

        const response = await fetch("/api/requisition/batch-add-items", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ items: items }),
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, "success");
            await Promise.all([
                renderSelectedItems(),
                calculateAndDisplayFees(),
            ]);

            // Reset button
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';

            // Reset selections
            selectedEquipment = [];

            // Close modal FIRST
            const modal = bootstrap.Modal.getInstance(
                document.getElementById("equipmentModal"),
            );
            if (modal) modal.hide();

            // Clear cache and reload AFTER modal is closed
            cache.clear('equipment');
            await loadEquipment(true);
        } else {
            showToast(result.message || "Failed to add equipment", "error");
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';
        }
    } catch (error) {
        console.error("Batch add equipment error:", error);
        showToast("Error adding equipment", "error");
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plus"></i> Add Selected';
    }
}

// ========== INITIALIZE FORM ITEMS ==========
async function initForm() {
    try {
        await Promise.all([
            window.renderSelectedItems(),
            window.calculateAndDisplayFees(),
        ]);
    } catch (error) {
        console.error("Error initializing form:", error);
        showToast("Failed to initialize form", "error");
    }
}
