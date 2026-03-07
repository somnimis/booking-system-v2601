@extends('layouts.app')

@section('title', 'Booking Catalog - Facilities & Equipment')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <style>
        .main-content {
            min-height: 100vh;
            background-image: url('{{ asset('assets/homepage.jpg') }}');
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
            padding: 2rem 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content-wrapper {
            position: relative;
            background-color: #ffffff;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .1);
            max-width: 1000px;
            width: 90%;
            margin: 0 auto;
        }

        /* Override responsive container for this specific page if needed */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem 0;
                background-attachment: fixed;
            }

            .content-wrapper {
                padding: 1rem;
                width: 95%;
            }
        }

        /* Loading Spinner Styles (keep these specific to this page) */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #041A4B;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            color: #041A4B;
            font-weight: bold;
        }

        /* Disable button during loading */
        .btn-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        @media (min-width: 768px) {
            .requisition-list {
                padding-bottom: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .requisition-list {
                padding-bottom: 1.5rem;
            }
        }

        .lookup-header {
            font-weight: bolder;
            text-align: center;
        }

        .header-subtext {
            color: gray !important;
            /* Force color */
            text-align: center !important;
            /* Force center alignment */
            margin-bottom: 1.5rem !important;
            /* Force margin */
            display: block;
            /* Make it block-level */
            font-size: 0.875rem;
            /* Explicit font size */
            opacity: 0.8;
            /* Alternative to gray color */
            line-height: 1.4;
        }

        .no-requisition-message {
            text-align: center;
            margin-top: 1.5rem;
            padding: 2rem 1rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            border: 1px solid #e9ecef;
            display: none;
            /* Hidden by default */
            width: 100%;
            /* Full width */
        }

        .no-requisition-message i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
            display: block;
        }

        .no-requisition-message p {
            color: #495057;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .no-requisition-message .subtext {
            color: #6c757d;
            font-size: 0.9rem;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.5;
        }

        .no-requisition-message.show {
            display: block;
            /* Show when class is added */
        }

        .has-no-results .requisition-list {
            display: none;
        }
    </style>
    <main class="main-content">
        <div class="content-wrapper">
            <h2 class="lookup-header">Requisition Form Lookup</h2>
            <small class="header-subtext">To monitor your submitted requests, check your email address for its unique code.
                You will also be notified once your form has been approved.</small>

            <!-- Initial search section -->
            <div id="lookupSection" class="lookup-form">
                <div class="input-group">
                    <input type="text" class="form-control" id="referenceInput" placeholder="Enter reference code..."
                        aria-label="Reference code">
                    <button class="btn btn-primary" type="button" id="searchButton" onclick="showResults()">Search</button>
                </div>
                <div id="loadingSpinner" class="loading-spinner">
                    <div class="spinner"></div>
                    <p class="loading-text">Searching for your requisition...</p>
                </div>
            </div>

            <!-- Results section (hidden by default) -->
            <div id="resultsSection" style="display: none;">
                <div class="lookup-form">
                    <div class="input-group">
                        <input type="text" class="form-control" id="resultsReferenceInput" aria-label="Reference code"
                            placeholder="Enter reference code...">
                        <button class="btn btn-primary" type="button" id="resultsSearchButton"
                            onclick="showResults()">Search</button>
                    </div>
                    <div id="resultsLoadingSpinner" class="loading-spinner" style="display: none;">
                        <div class="spinner"></div>
                        <p class="loading-text">Searching for your requisition...</p>
                    </div>
                </div>
                <!-- Results container - cards will go here -->
                <div class="requisition-list mt-3">
                    <!-- Cards will be dynamically inserted here by JavaScript -->
                </div>
            </div>

            <!-- No results message -->
            <div id="noResultsMessage" class="no-requisition-message" style="display: none;">
                <i class="fas fa-search"></i>
                <p>No requisition forms found</p>
                <p class="subtext">Please check your reference code and try again.</p>
            </div>

            <!-- Modals -->
            <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelModalLabel">Confirm Cancellation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel this request? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep
                                Request</button>
                            <button type="button" class="btn btn-danger" id="confirmCancelBtn">Yes, Cancel Request</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="uploadReceiptModal" tabindex="-1" aria-labelledby="uploadReceiptModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadReceiptModalLabel">Upload Payment Receipt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="uploadArea" class="border-dashed p-4 text-center"
                                style="border: 2px dashed #ccc; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                <p>Drag & drop your receipt here or click to browse</p>
                                <p class="small text-muted">Supported formats: JPG, PNG, PDF (Max: 5MB)</p>
                            </div>
                            <input type="file" id="receiptFile" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">

                            <div id="uploadPreview" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-file"></i>
                                    <span id="fileName"></span>
                                    <button type="button" class="btn-close float-end"
                                        onclick="clearFileSelection()"></button>
                                </div>
                            </div>

                            <div id="uploadProgress" class="progress mt-3" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                    aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>

                            <div id="uploadError" class="alert alert-danger mt-3" style="display: none;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmUploadBtn" disabled
                                onclick="uploadReceipt()">Upload Receipt</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

@endsection

@section('scripts')
    <script>
        // Cloudinary configuration
        const cloudName = 'dn98ntlkd';
        const uploadPreset = 'payment-receipts';
        let selectedFile = null;
        let currentRequestId = null;

        // ------  Helper functions  ------ //

        // Function to generate dynamic CSS for status badges
        function generateStatusStyles(statuses) {
            console.log('Generating dynamic styles for statuses:', statuses);

            const styleId = 'dynamic-status-styles';
            let existingStyle = document.getElementById(styleId);

            if (existingStyle) {
                existingStyle.remove();
            }

            const style = document.createElement('style');
            style.id = styleId;

            let css = '';

            statuses.forEach(status => {
                const className = status.status_name.toLowerCase().replace(/\s+/g, '-');
                const bgColor = status.color_code;
                const textColor = getContrastingTextColor(bgColor);

                // ADD !important to override dark mode styles
                css += `
                .status-badge-responsive.${className} {
                    background-color: ${bgColor} !important;
                    color: ${textColor} !important;
                    border-color: ${darkenColor(bgColor, 20)} !important;
                }

                /* Dark mode override */
                @media (prefers-color-scheme: dark) {
                    .status-badge-responsive.${className} {
                        background-color: ${bgColor} !important;
                        color: ${textColor} !important;
                        border-color: ${darkenColor(bgColor, 20)} !important;
                    }
                }
            `;
            });

            style.textContent = css;
            document.head.appendChild(style);

            console.log('Dynamic styles injected with !important flags');
        }

        // Helper function to determine contrasting text color
        function getContrastingTextColor(bgColor) {
            // Convert rgb/rgba/hex to rgb values
            let r, g, b;

            if (bgColor.startsWith('rgb')) {
                const matches = bgColor.match(/\d+/g);
                r = parseInt(matches[0]);
                g = parseInt(matches[1]);
                b = parseInt(matches[2]);
            } else if (bgColor.startsWith('#')) {
                const hex = bgColor.replace('#', '');
                r = parseInt(hex.substring(0, 2), 16);
                g = parseInt(hex.substring(2, 4), 16);
                b = parseInt(hex.substring(4, 6), 16);
            } else {
                return '#ffffff'; // Default to white
            }

            // Calculate luminance
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

            // Return black for light backgrounds, white for dark backgrounds
            return luminance > 0.5 ? '#000000' : '#ffffff';
        }

        // Helper function to darken a color for borders
        function darkenColor(color, percent) {
            if (color.startsWith('rgb')) {
                const matches = color.match(/\d+/g);
                let r = Math.max(0, parseInt(matches[0]) * (100 - percent) / 100);
                let g = Math.max(0, parseInt(matches[1]) * (100 - percent) / 100);
                let b = Math.max(0, parseInt(matches[2]) * (100 - percent) / 100);
                return `rgb(${Math.round(r)}, ${Math.round(g)}, ${Math.round(b)})`;
            } else if (color.startsWith('#')) {
                // Convert hex to rgb, darken, then back to hex
                const hex = color.replace('#', '');
                let r = parseInt(hex.substring(0, 2), 16);
                let g = parseInt(hex.substring(2, 4), 16);
                let b = parseInt(hex.substring(4, 6), 16);

                r = Math.max(0, Math.round(r * (100 - percent) / 100));
                g = Math.max(0, Math.round(g * (100 - percent) / 100));
                b = Math.max(0, Math.round(b * (100 - percent) / 100));

                return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
            }
            return color;
        }

        function handleFileSelection(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!validTypes.includes(file.type)) {
                showUploadError('Invalid file type. Please upload JPG, PNG, or PDF files only.');
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showUploadError('File too large. Maximum size is 5MB.');
                return;
            }

            selectedFile = file;

            // Show file preview
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('uploadPreview').style.display = 'block';
            document.getElementById('confirmUploadBtn').disabled = false;
            document.getElementById('uploadError').style.display = 'none';
        }

        function clearFileSelection() {
            selectedFile = null;
            document.getElementById('receiptFile').value = '';
            document.getElementById('uploadPreview').style.display = 'none';
            document.getElementById('confirmUploadBtn').disabled = true;
        }

        function showUploadError(message) {
            const errorDiv = document.getElementById('uploadError');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }

        function showUploadModal(requestId) {
            currentRequestId = requestId;
            clearFileSelection();
            const modal = new bootstrap.Modal(document.getElementById('uploadReceiptModal'));
            modal.show();
        }

        function uploadReceipt() {
            if (!selectedFile || !currentRequestId) {
                showUploadError('Please select a file to upload.');
                return;
            }

            const progressBar = document.querySelector('#uploadProgress .progress-bar');
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
            document.getElementById('uploadProgress').style.display = 'block';
            document.getElementById('confirmUploadBtn').disabled = true;

            // Create form data for Cloudinary upload
            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('upload_preset', uploadPreset);
            formData.append('cloud_name', cloudName);

            // Upload to Cloudinary
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                }
            });

            xhr.addEventListener('load', function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);

                    // Send to our server to save to database
                    fetch(`/api/requester/requisition/${currentRequestId}/upload-receipt`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            receipt_url: response.secure_url,
                            public_id: response.public_id
                        })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Close modal and refresh page
                                bootstrap.Modal.getInstance(document.getElementById('uploadReceiptModal')).hide();
                                alert('Receipt uploaded successfully!');
                                location.reload();
                            } else {
                                showUploadError('Failed to save receipt details: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving receipt:', error);
                            showUploadError('Failed to save receipt details. Please try again.');
                        });
                } else {
                    showUploadError('Upload failed. Please try again.');
                }

                document.getElementById('uploadProgress').style.display = 'none';
            });

            xhr.addEventListener('error', function () {
                showUploadError('Upload failed. Please check your connection and try again.');
                document.getElementById('uploadProgress').style.display = 'none';
            });

            xhr.open('POST', `https://api.cloudinary.com/v1_1/${cloudName}/auto/upload`);
            xhr.send(formData);
        }

        let allForms = [];
        let statuses = [];

        // Fetch statuses from API
        async function fetchStatuses() {
            try {
                const response = await fetch('/api/form-statuses');
                const data = await response.json();

                // Filter out unwanted statuses if needed
                statuses = data.filter(status =>
                    status.status_name !== 'Returned' &&
                    status.status_name !== 'Late Return'
                );

                // Generate dynamic CSS styles
                generateStatusStyles(statuses);

                // Update dropdown menu
                const dropdownMenu = document.querySelector('#resultsSection .dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.innerHTML = `
                    <li><a class="dropdown-item" href="#" data-status="all">All</a></li>
                    ${statuses.map(status => `
                        <li><a class="dropdown-item" href="#" data-status="${status.status_name.toLowerCase().replace(/\s+/g, '-')}">${status.status_name}</a></li>
                    `).join('')}
                `;
                }

            } catch (error) {
                console.error('Failed to fetch statuses:', error);
                // Fallback to default colors if API fails
                generateFallbackStyles();
            }
        }

        // Fallback function in case API fails
        function generateFallbackStyles() {
            const fallbackStatuses = [
                { status_name: 'pending-approval', color_code: '#707485' },
                { status_name: 'awaiting-payment', color_code: '#1c5b8f' },
                { status_name: 'scheduled', color_code: '#1e7941' },
                { status_name: 'ongoing', color_code: '#ac7a0f' },
                { status_name: 'late', color_code: '#8f2a2a' },
                { status_name: 'returned', color_code: '#3e5568' },
                { status_name: 'late-return', color_code: '#3e5568' },
                { status_name: 'completed', color_code: '#3e5568' },
                { status_name: 'rejected', color_code: '#3e5568' },
                { status_name: 'cancelled', color_code: '#3e5568' }
            ];

            generateStatusStyles(fallbackStatuses);
        }
        function showResults() {
            // Get the active search input based on which section is visible
            let searchInput;

            const resultsSection = document.getElementById('resultsSection');
            const lookupSection = document.getElementById('lookupSection');

            // Hide no results message immediately when starting new search
            document.getElementById('noResultsMessage').style.display = 'none';

            // Check if results section is visible
            if (resultsSection && resultsSection.style.display !== 'none') {
                // Results are showing, use the results section input
                searchInput = document.getElementById('resultsReferenceInput');
            } else {
                // Initial lookup, use the original input
                searchInput = document.getElementById('referenceInput');
            }

            if (!searchInput || !searchInput.value.trim()) {
                alert('Please enter a reference code');
                return;
            }

            const referenceInput = searchInput.value.trim();

            // Show loading animation
            showLoading();

            fetchFormByAccessCode(referenceInput);
        }
        function showLoading() {
            const resultsSection = document.getElementById('resultsSection');
            const isResultsVisible = resultsSection && resultsSection.style.display !== 'none';

            // Clear previous results immediately when starting new search
            const requisitionList = document.querySelector('.requisition-list');
            if (requisitionList) {
                requisitionList.innerHTML = '';
            }

            if (isResultsVisible) {
                // Results section is visible
                const loadingSpinner = document.getElementById('resultsLoadingSpinner');
                const searchButton = document.getElementById('resultsSearchButton');

                if (loadingSpinner) loadingSpinner.style.display = 'block';
                if (searchButton) {
                    searchButton.classList.add('btn-loading');
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                }
            } else {
                // Initial lookup section
                const loadingSpinner = document.getElementById('loadingSpinner');
                const searchButton = document.getElementById('searchButton');
                const noResultsMessage = document.getElementById('noResultsMessage');

                if (loadingSpinner) loadingSpinner.style.display = 'block';
                if (searchButton) {
                    searchButton.classList.add('btn-loading');
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                }
                if (noResultsMessage) noResultsMessage.style.display = 'none';
            }
        }

        function hideLoading() {
            // Hide both loading spinners and reset both buttons
            const loadingSpinner1 = document.getElementById('loadingSpinner');
            const loadingSpinner2 = document.getElementById('resultsLoadingSpinner');
            const searchButton1 = document.getElementById('searchButton');
            const searchButton2 = document.getElementById('resultsSearchButton');

            if (loadingSpinner1) loadingSpinner1.style.display = 'none';
            if (loadingSpinner2) loadingSpinner2.style.display = 'none';

            if (searchButton1) {
                searchButton1.classList.remove('btn-loading');
                searchButton1.innerHTML = 'Search';
            }
            if (searchButton2) {
                searchButton2.classList.remove('btn-loading');
                searchButton2.innerHTML = 'Search';
            }
        }

        async function fetchFormByAccessCode(accessCode) {
            try {
                const response = await fetch(`/api/requester/form/${accessCode}`);

                if (!response.ok) {
                    throw new Error(`Form not found (${response.status})`);
                }

                const form = await response.json();

                // Check if the response structure is correct
                if (!form.form_status || !form.purpose) {
                    throw new Error('Invalid form data structure');
                }

                allForms = [form];

                // SUCCESS: Hide no results message, show requisition list
                document.getElementById('noResultsMessage').style.display = 'none';

                // Display the form - this will show the requisition list
                displayForms();

                // Clear inputs
                document.getElementById('resultsReferenceInput').value = '';
                document.getElementById('referenceInput').value = '';

                // Show results section, hide lookup
                document.getElementById('lookupSection').style.display = 'none';
                document.getElementById('resultsSection').style.display = 'block';

            } catch (error) {
                console.error('Error fetching form:', error);

                // Get all elements
                const noResultsMessage = document.getElementById('noResultsMessage');
                const lookupSection = document.getElementById('lookupSection');
                const resultsSection = document.getElementById('resultsSection');
                const requisitionList = document.querySelector('.requisition-list');

                // Clear the requisition list content
                if (requisitionList) {
                    requisitionList.innerHTML = '';
                }

                // Hide everything first
                lookupSection.style.display = 'none';
                resultsSection.style.display = 'none';
                noResultsMessage.style.display = 'none';

                // Determine which search interface to show
                const resultsInput = document.getElementById('resultsReferenceInput');
                const lookupInput = document.getElementById('referenceInput');

                if (resultsInput && resultsInput.value.trim() === accessCode) {
                    // Came from results section - IMPORTANT: Hide the results container area
                    resultsSection.style.display = 'block';
                    // Hide just the requisition-list container to eliminate the gap
                    if (requisitionList) {
                        requisitionList.style.display = 'none';
                    }
                    resultsInput.value = '';
                } else {
                    // Came from lookup section or fallback
                    lookupSection.style.display = 'block';
                    if (lookupInput) lookupInput.value = '';
                }

                // Show no results message
                noResultsMessage.style.display = 'block';

            } finally {
                hideLoading();
            }
        }

        async function fetchFormsByEmail(email) {
            try {
                const response = await fetch(`/api/requester/forms/${email}`);
                if (!response.ok) throw new Error('No forms found');

                const forms = await response.json();
                if (forms.length === 0) throw new Error('No forms found');

                // Fetch details for each form
                const formDetails = [];
                for (const form of forms) {
                    try {
                        const detailResponse = await fetch(`/api/requester/form/${form.access_code}`);
                        if (detailResponse.ok) {
                            const detail = await detailResponse.json();
                            // Validate the detail structure
                            if (detail.form_details && detail.form_details.status) {
                                formDetails.push(detail);
                            } else {
                                console.warn('Skipping invalid form detail:', detail);
                            }
                        }
                    } catch (detailError) {
                        console.warn('Failed to fetch form detail:', detailError);
                    }
                }

                if (formDetails.length === 0) throw new Error('No valid forms found');

                allForms = formDetails;
                displayForms();

                document.getElementById('lookupSection').style.display = 'none';
                document.getElementById('resultsSection').style.display = 'block';
                document.getElementById('noResultsMessage').style.display = 'none';

            } catch (error) {
                console.error('Error fetching forms:', error);
                document.getElementById('noResultsMessage').style.display = 'block';
            }
        }



        // Function to calculate total fee based on form data
        function calculateTotalFee(form) {
            console.log('Form data for fee calculation:', form); // Debug log

            // First priority: Use total_fee from requester API
            if (form.total_fee && parseFloat(form.total_fee) > 0) {
                return parseFloat(form.total_fee);
            }

            // Second priority: Use approved_fee from admin API structure
            if (form.fees && form.fees.approved_fee) {
                return parseFloat(form.fees.approved_fee);
            }

            // Fallback: Calculate manually (this won't work for requester API since fee data is missing)
            let totalFee = 0;

            // Calculate facility fees - this requires external_fee data which isn't in requester API
            if (form.requested_facilities && form.requested_facilities.length > 0) {
                console.warn('Cannot calculate facility fees - external_fee data missing');
            }

            // Calculate equipment fees - this requires external_fee data which isn't in requester API
            if (form.requested_equipment && form.requested_equipment.length > 0) {
                console.warn('Cannot calculate equipment fees - external_fee data missing');
            }

            // Add late penalty if applicable
            if (form.is_late && form.late_penalty_fee) {
                totalFee += parseFloat(form.late_penalty_fee);
            }

            return totalFee;
        }


        // Function to calculate duration in hours
        function calculateDurationHours(startDate, startTime, endDate, endTime) {
            const startDateTime = new Date(`${startDate}T${convertTo24Hour(startTime)}:00`);
            const endDateTime = new Date(`${endDate}T${convertTo24Hour(endTime)}:00`);
            const durationHours = (endDateTime - startDateTime) / (1000 * 60 * 60);
            return Math.max(0, durationHours);
        }

        // Function to convert 12-hour time to 24-hour time (same as in reservation form)
        function convertTo24Hour(time12h) {
            if (!time12h) return '';

            // If it's already in 24-hour format (contains : but no AM/PM)
            if (time12h.includes(':') && !time12h.includes('AM') && !time12h.includes('PM')) {
                return time12h; // Already in 24-hour format
            }

            // Convert from 12-hour to 24-hour
            if (time12h.includes(':')) {
                const [timePart, modifier] = time12h.split(' ');
                if (!modifier) return timePart;

                let [hours, minutes] = timePart.split(':');
                hours = parseInt(hours, 10);

                if (modifier === 'PM' && hours !== 12) {
                    hours += 12;
                } else if (modifier === 'AM' && hours === 12) {
                    hours = 0;
                }

                return `${hours.toString().padStart(2, '0')}:${minutes}`;
            }

            return time12h;
        }

        function convertTo12Hour(time24h) {
            if (!time24h) return '';

            // Remove seconds if present
            let time = time24h;
            if (time.length > 5) {
                time = time.substring(0, 5);
            }

            const [hours, minutes] = time.split(':');
            let hour = parseInt(hours, 10);
            const suffix = hour >= 12 ? 'PM' : 'AM';

            // Convert to 12-hour format
            hour = hour % 12 || 12;

            return `${hour}:${minutes} ${suffix}`;
        }

        // Function to generate fee breakdown HTML
        function generateFeeBreakdown(form) {
            const durationHours = calculateDurationHours(form.start_date, form.start_time, form.end_date, form.end_time);

            let facilityTotal = 0;
            let equipmentTotal = 0;
            let htmlContent = '';

            // Facilities breakdown
            const facilityItems = form.requested_facilities || [];
            if (facilityItems.length > 0) {
                htmlContent += '<div class="fee-section"><h6 class="text-primary">Facilities</h6>';
                facilityItems.forEach(item => {
                    let fee = parseFloat(item.external_fee || 0);
                    if (item.rate_type === 'Per Hour' && durationHours > 0) {
                        fee = fee * durationHours;
                        htmlContent += `
                                <div class="fee-item">
                                    <span>${item.facility_name} (${durationHours.toFixed(1)} hrs)</span>
                                    <div class="text-end">
                                        <small>₱${parseFloat(item.external_fee).toLocaleString()}/hr</small>
                                        <div><strong>₱${fee.toLocaleString()}</strong></div>
                                    </div>
                                </div>
                            `;
                    } else {
                        htmlContent += `
                                <div class="fee-item">
                                    <span>${item.facility_name}</span>
                                    <span>₱${fee.toLocaleString()}</span>
                                </div>
                            `;
                    }
                    facilityTotal += fee;
                });
                htmlContent += `
                        <div class="fee-item subtotal">
                            <strong>Subtotal</strong>
                            <strong>₱${facilityTotal.toLocaleString()}</strong>
                        </div>
                    </div>`;
            }

            // Equipment breakdown
            const equipmentItems = form.requested_equipment || [];
            if (equipmentItems.length > 0) {
                htmlContent += '<div class="fee-section mt-3"><h6 class="text-primary">Equipment</h6>';
                equipmentItems.forEach(item => {
                    let unitFee = parseFloat(item.external_fee || 0);
                    const quantity = item.quantity || 1;
                    let itemTotal = unitFee * quantity;
                    if (item.rate_type === 'Per Hour' && durationHours > 0) {
                        itemTotal = itemTotal * durationHours;
                        htmlContent += `
                                <div class="fee-item">
                                    <span>${item.equipment_name} ${quantity > 1 ? `(x${quantity})` : ''} (${durationHours.toFixed(1)} hrs)</span>
                                    <div class="text-end">
                                        <small>₱${unitFee.toLocaleString()}/hr × ${quantity}</small>
                                        <div><strong>₱${itemTotal.toLocaleString()}</strong></div>
                                    </div>
                                </div>
                            `;
                    } else {
                        htmlContent += `
                                <div class="fee-item">
                                    <span>${item.equipment_name} ${quantity > 1 ? `(x${quantity})` : ''}</span>
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
                        <div class="fee-item subtotal">
                            <strong>Subtotal</strong>
                            <strong>₱${equipmentTotal.toLocaleString()}</strong>
                        </div>
                    </div>`;
            }

            // Total
            const total = facilityTotal + equipmentTotal;
            if (total > 0) {
                htmlContent += `
                        <div class="fee-item total-fee">
                            <strong>Total Amount</strong>
                            <strong>₱${total.toLocaleString()}</strong>
                        </div>
                    `;
            }

            return {
                html: htmlContent,
                total: total
            };
        }

        function displayForms() {
            const requisitionList = document.querySelector('.requisition-list');
            if (!requisitionList) return;

            requisitionList.innerHTML = '';
            requisitionList.style.display = 'block'; // Ensure it's visible

            allForms.forEach(form => {
                // Update the safety check to match the new structure
                if (!form.form_status || !form.purpose) {
                    console.error('Invalid form structure:', form);
                    return;
                }

                const statusClass = form.form_status.status_name.toLowerCase().replace(/\s+/g, '-');
                const statusName = form.form_status.status_name;

                // Calculate total fee
                const totalFee = calculateTotalFee(form);

                let footerButtons = '';

                if (['Pending Approval', 'Scheduled'].includes(statusName)) {
                    footerButtons = `
                        <div class="card-responsive-footer">
                            <button class="btn btn-sm btn-danger" onclick="showCancelModal(${form.request_id})">Cancel Request</button>
                        </div>
                    `;
                } else if (statusName === 'Awaiting Payment') {
                    footerButtons = `
                        <div class="card-responsive-footer">
                            <button class="btn btn-sm btn-success" onclick="showUploadModal(${form.request_id})">Upload Receipt</button>
                            <button class="btn btn-sm btn-danger" onclick="showCancelModal(${form.request_id})">Cancel Request</button>
                        </div>
                    `;
                } else {
                    footerButtons = '<div class="card-responsive-footer"></div>';
                }

                const facilitiesList = form.requested_facilities && form.requested_facilities.length > 0
                    ? form.requested_facilities.map(f => `<li>${f.facility_name}</li>`).join('')
                    : '<p class="no-items mb-0">No facilities requested</p>';

                const equipmentList = form.requested_equipment && form.requested_equipment.length > 0
                    ? form.requested_equipment.map(e => `<li>${e.equipment_name}</li>`).join('')
                    : '<p class="no-items mb-0">No equipment requested</p>';

                const purpose = form.purpose.purpose_name || 'No purpose specified';

                // Format dates properly
                const formatDate = (dateString) => {
                    return new Date(dateString).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                };

                // Format time to standard 12-hour format with AM/PM
                const formatTime = (timeString) => {
                    // If timeString is already in 12-hour format (contains AM/PM), return as is
                    if (timeString.includes('AM') || timeString.includes('PM')) {
                        return timeString;
                    }

                    // Convert military time (HH:MM:SS or HH:MM) to 12-hour format
                    let time = timeString;
                    // Remove seconds if present
                    if (time.length > 5) {
                        time = time.substring(0, 5);
                    }

                    const [hours, minutes] = time.split(':');
                    let hour = parseInt(hours, 10);
                    const suffix = hour >= 12 ? 'PM' : 'AM';

                    // Convert to 12-hour format
                    hour = hour % 12 || 12;

                    return `${hour}:${minutes} ${suffix}`;
                };

                // Extract requester details - check for both old and new structure
                let organizationName = '';
                let requesterName = '';

                // Check for user_details structure (from controller)
                if (form.user_details) {
                    organizationName = form.user_details.organization_name || '';
                    requesterName = `${form.user_details.first_name || ''} ${form.user_details.last_name || ''}`.trim();
                }
                // Check for older structure
                else if (form.organization_name || form.first_name || form.last_name) {
                    organizationName = form.organization_name || '';
                    requesterName = `${form.first_name || ''} ${form.last_name || ''}`.trim();
                }

                const card = `
        <div class="card card-responsive mb-3">
            <div class="card-responsive-header">
                <span class="request-id">Request ID #${form.request_id.toString().padStart(4, '0')}</span>
                <span class="status-badge-responsive ${statusClass}">${statusName}</span>
            </div>
            <div class="card-responsive-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        ${organizationName ? `<p class="mb-2"><strong class="d-inline-block" style="min-width: 110px;">Organization:</strong> ${organizationName}</p>` : ''}
                        ${requesterName ? `<p class="mb-2"><strong class="d-inline-block" style="min-width: 110px;">Requester:</strong> ${requesterName}</p>` : ''}

                        <p class="mb-2"><strong class="d-inline-block" style="min-width: 110px;">Purpose:</strong> ${purpose}</p>
                        <p class="mb-2"><strong class="d-inline-block" style="min-width: 110px;">Start Schedule:</strong> ${formatDate(form.start_date)}, ${formatTime(form.start_time)}</p>
                        <p class="mb-2"><strong class="d-inline-block" style="min-width: 110px;">End Schedule:</strong> ${formatDate(form.end_date)}, ${formatTime(form.end_time)}</p>
                        <p class="mb-0"><strong class="d-inline-block" style="min-width: 110px;">Total Fee:</strong> ₱${totalFee.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}</p>
                    </div>
                    <div class="col-12 col-md-6 border-md-start ps-md-3">
                        <h6 class="fw-bold text-responsive-md mb-3">Request Details</h6>
                        <p class="mb-1"><strong>Facilities:</strong></p>
                        <ul class="mb-3 ps-3">${facilitiesList}</ul>
                        <p class="mb-1"><strong>Equipment:</strong></p>
                        <ul class="mb-0 ps-3">${equipmentList}</ul>
                    </div>
                </div>
            </div>
            <div class="card-responsive-footer">
                ${footerButtons}
            </div>
        </div>
    `;
                requisitionList.innerHTML += card;
            });
        }
        function showCancelModal(requestId) {
            currentRequestId = requestId;
            const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
            cancelModal.show();
        }

        async function cancelRequest() {
            if (!currentRequestId) return;

            try {
                const response = await fetch(`/api/requester/requisition/${currentRequestId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Request cancelled successfully');
                    location.reload();
                } else {
                    throw new Error(result.details || 'Failed to cancel request');
                }
            } catch (error) {
                console.error('Error cancelling request:', error);
                alert('Error: ' + error.message);
            } finally {
                currentRequestId = null;
            }
        }

        function filterRequisitions(status) {
            const cards = document.querySelectorAll('.requisition-list .card.mb-3');

            cards.forEach(card => {
                if (status === 'all') {
                    card.style.display = 'block';
                } else {
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        const badgeStatus = statusBadge.textContent.toLowerCase().replace(/\s+/g, '-');
                        if (badgeStatus === status.toLowerCase()) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                }
            });
        }


        // Initialize upload area
        document.addEventListener('DOMContentLoaded', function () {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('receiptFile');

            // Initially hide the no results message
            document.getElementById('noResultsMessage').style.display = 'none';

            uploadArea.addEventListener('click', function () {
                fileInput.click();
            });

            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#007bff';
            });

            uploadArea.addEventListener('dragleave', function () {
                uploadArea.style.borderColor = '#ccc';
            });

            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#ccc';

                if (e.dataTransfer.files.length) {
                    handleFileSelection(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function () {
                if (this.files.length) {
                    handleFileSelection(this.files[0]);
                }
            });
            // Set up filter dropdown - use more specific selector
            const resultsSection = document.getElementById('resultsSection');
            if (resultsSection) {
                resultsSection.addEventListener('click', function (e) {
                    if (e.target.classList.contains('dropdown-item')) {
                        e.preventDefault();
                        const status = e.target.getAttribute('data-status');
                        filterRequisitions(status);

                        const dropdownBtn = resultsSection.querySelector('.dropdown-toggle');
                        if (dropdownBtn && status === 'all') {
                            dropdownBtn.textContent = 'Filter by';
                        } else if (dropdownBtn) {
                            dropdownBtn.textContent = 'Filter: ' + e.target.textContent;
                        }
                    }
                });
            }

            // Set up cancel confirmation button
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', cancelRequest);
            }

            fetchStatuses();
        });
    </script>
@endsection