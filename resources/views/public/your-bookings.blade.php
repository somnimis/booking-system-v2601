@extends('layouts.app')

@section('title', 'My Bookings - Requisition Status')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <style>
        /* ============================================
           REFINED INSTITUTIONAL THEME - YOUR BOOKINGS
           Matching catalog.css design system
           ============================================ */

        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap');

        :root {
            --navy: #041a4b;
            --navy-mid: #0b2d72;
            --navy-light: #e8edf8;
            --amber: #f5bc40;
            --amber-dark: #d9a12a;
            --white: #ffffff;
            --surface: #f5f6fa;
            --border: #e2e6f0;
            --text-base: #1e2d4a;
            --text-muted: #6b7a99;
            --text-light: #9aaac5;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f5bc40;
            --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
            --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
            --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--text-base);
        }

        .main-content {
            min-height: 100vh;
            background-image: url('{{ asset('assets/homepage.jpg') }}');
            background-size: cover;
            background-position: center bottom;
            background-repeat: no-repeat;
            padding: 3rem 1rem;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(4, 26, 75, 0.54) 0%, rgba(4, 26, 75, 0.75) 100%);
            z-index: 1;
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            backdrop-filter: blur(2px);
        }

        /* Header Styles */
        .lookup-header {
            font-family: 'Fraunces', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
            text-align: center;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .header-subtext {
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            line-height: 1.5;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Search Form */
        .lookup-form {
            margin-bottom: 1.5rem;
        }

        .input-group {
            display: flex;
            gap: 0.75rem;
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .form-control {
            flex: 1;
            padding: 0.875rem 1.25rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-base);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }

        .form-control::placeholder {
            color: var(--text-light);
        }

        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.875rem 1.75rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-primary:hover {
            background: var(--navy-mid);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary.btn-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .btn-secondary {
            background: var(--white);
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: var(--surface);
            border-color: var(--navy);
            color: var(--navy);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-success {
            background: var(--success);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-success:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }

        /* Loading Spinner */
        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            border: 3px solid var(--navy-light);
            border-top: 3px solid var(--navy);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: var(--navy);
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* No Results Message */
        .no-requisition-message {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-top: 1.5rem;
        }

        .no-requisition-message i {
            font-size: 3.5rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .no-requisition-message p {
            color: var(--text-base);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .no-requisition-message .subtext {
            color: var(--text-muted);
            font-size: 0.875rem;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Requisition Cards */
        .requisition-list {
            margin-top: 1.5rem;
        }

        .card-responsive {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 1.25rem;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .card-responsive:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .card-responsive-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .request-id {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--navy);
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 0.3px;
        }

        .status-badge-responsive {
            padding: 0.25rem 0.75rem;
            border-radius: 60px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: var(--surface);
            color: var(--text-base);
        }

        /* Dynamic status colors will be injected via JS */
        .card-responsive-body {
            padding: 1.25rem;
        }

        .card-responsive-footer {
            padding: 1rem 1.25rem;
            background: var(--surface);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* Fee Breakdown */
        .fee-section {
            background: var(--surface);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .fee-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }

        .fee-item:last-child {
            border-bottom: none;
        }

        .fee-item.subtotal {
            font-weight: 600;
            color: var(--navy);
            padding-top: 0.75rem;
            margin-top: 0.25rem;
            border-top: 1px solid var(--border);
        }

        .fee-item.total-fee {
            background: var(--navy);
            color: white;
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            margin-top: 0.75rem;
            font-weight: 700;
        }

        /* Lists */
        ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        li {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .no-items {
            color: var(--text-light);
            font-style: italic;
            font-size: 0.8rem;
        }

        /* Filter Dropdown */
        .filter-dropdown {
            position: relative;
            display: inline-block;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--text-base);
            text-decoration: none;
            font-size: 0.85rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .dropdown-item:hover {
            background: var(--navy-light);
            color: var(--navy);
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: var(--navy);
            color: white;
            border-bottom: none;
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border);
            background: var(--surface);
            padding: 1rem 1.5rem;
            gap: 0.75rem;
        }

        /* Upload Area */
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--surface);
        }

        .upload-area:hover {
            border-color: var(--navy);
            background: var(--navy-light);
        }

        .upload-area i {
            font-size: 2.5rem;
            color: var(--text-light);
            margin-bottom: 0.75rem;
        }

        .upload-area p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .upload-area .small {
            font-size: 0.7rem;
        }

        /* Progress Bar */
        .progress {
            background: var(--surface);
            border-radius: 60px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            background: var(--navy);
            border-radius: 60px;
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1.25rem;
            }

            .lookup-header {
                font-size: 1.4rem;
            }

            .header-subtext {
                font-size: 0.75rem;
                margin-bottom: 1.5rem;
            }

            .input-group {
                flex-direction: column;
            }

            .btn-primary {
                justify-content: center;
            }

            .card-responsive-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-responsive-footer {
                flex-direction: column;
            }

            .card-responsive-footer .btn {
                width: 100%;
            }

            .filter-bar {
                justify-content: stretch;
            }

            .filter-bar .dropdown-toggle {
                width: 100%;
                justify-content: center;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .content-wrapper {
                max-width: 95%;
            }
        }

        /* Text utilities */
        .text-primary { color: var(--navy) !important; }
        .text-muted { color: var(--text-muted) !important; }
        .fw-bold { font-weight: 700 !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 0.25rem !important; }
        .mb-2 { margin-bottom: 0.5rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mt-3 { margin-top: 1rem !important; }
        .ps-3 { padding-left: 1rem !important; }
    </style>

    <main class="main-content">
        <div class="content-wrapper">
            <h2 class="lookup-header">Your Bookings</h2>
            <p class="header-subtext">Track your requests, upload payment receipts, and manage your reservations.</p>

            <!-- Initial search section -->
            <div id="lookupSection" class="lookup-form">
                <div class="input-group">
                    <input type="text" class="form-control" id="referenceInput" 
                           placeholder="Enter your reference code..." aria-label="Reference code">
                    <button class="btn-primary" type="button" id="searchButton" onclick="showResults()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p class="loading-text">Searching for your requisition...</p>
                </div>
            </div>

            <!-- Results section (hidden by default) -->
            <div id="resultsSection" style="display: none;">
                <div class="lookup-form">
                    <div class="input-group">
                        <input type="text" class="form-control" id="resultsReferenceInput" 
                               placeholder="Enter reference code..." aria-label="Reference code">
                        <button class="btn-primary" type="button" id="resultsSearchButton" onclick="showResults()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div id="resultsLoadingSpinner" class="loading-spinner" style="display: none;">
                        <div class="spinner"></div>
                        <p class="loading-text">Searching for your requisition...</p>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-dropdown">
                        <button class="dropdown-toggle" type="button" id="filterDropdownBtn">
                            <i class="fas fa-filter"></i> Filter by Status
                        </button>
                        <div class="dropdown-menu" id="filterDropdownMenu">
                            <a class="dropdown-item" href="#" data-status="all">All</a>
                        </div>
                    </div>
                </div>

                <!-- Results container -->
                <div class="requisition-list mt-3"></div>
            </div>

            <!-- No results message -->
            <div id="noResultsMessage" class="no-requisition-message" style="display: none;">
                <i class="fas fa-search"></i>
                <p>No requisition forms found</p>
                <p class="subtext">Please check your reference code and try again.</p>
            </div>
        </div>
    </main>

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
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">No, Keep Request</button>
                    <button type="button" class="btn-danger" id="confirmCancelBtn">Yes, Cancel Request</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadReceiptModal" tabindex="-1" aria-labelledby="uploadReceiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadReceiptModalLabel">Upload Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="uploadArea" class="upload-area">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your receipt here or click to browse</p>
                        <p class="small text-muted">Supported formats: JPG, PNG, PDF (Max: 5MB)</p>
                    </div>
                    <input type="file" id="receiptFile" accept=".jpg,.jpeg,.png,.pdf" style="display: none;">

                    <div id="uploadPreview" class="mt-3" style="display: none;">
                        <div class="alert alert-info" style="background: var(--navy-light); border: none; border-radius: var(--radius-md); padding: 0.75rem;">
                            <i class="fas fa-file"></i>
                            <span id="fileName"></span>
                            <button type="button" class="btn-close float-end" onclick="clearFileSelection()" style="font-size: 0.7rem;"></button>
                        </div>
                    </div>

                    <div id="uploadProgress" class="progress mt-3" style="display: none; height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: 0%;"></div>
                    </div>

                    <div id="uploadError" class="alert alert-danger mt-3" style="display: none; border-radius: var(--radius-sm);"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary" id="confirmUploadBtn" disabled onclick="uploadReceipt()">Upload Receipt</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        // Cloudinary configuration
        const cloudName = 'dn98ntlkd';
        const uploadPreset = 'payment-receipts';
        let selectedFile = null;
        let currentRequestId = null;

        // Helper functions
        function getContrastingTextColor(bgColor) {
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
                return '#ffffff';
            }
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.5 ? '#000000' : '#ffffff';
        }

        function darkenColor(color, percent) {
            if (color.startsWith('rgb')) {
                const matches = color.match(/\d+/g);
                let r = Math.max(0, parseInt(matches[0]) * (100 - percent) / 100);
                let g = Math.max(0, parseInt(matches[1]) * (100 - percent) / 100);
                let b = Math.max(0, parseInt(matches[2]) * (100 - percent) / 100);
                return `rgb(${Math.round(r)}, ${Math.round(g)}, ${Math.round(b)})`;
            } else if (color.startsWith('#')) {
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

        function generateStatusStyles(statuses) {
            const styleId = 'dynamic-status-styles';
            let existingStyle = document.getElementById(styleId);
            if (existingStyle) existingStyle.remove();

            const style = document.createElement('style');
            style.id = styleId;
            let css = '';

            statuses.forEach(status => {
                const className = status.status_name.toLowerCase().replace(/\s+/g, '-');
                const bgColor = status.color_code;
                const textColor = getContrastingTextColor(bgColor);
                css += `
                    .status-badge-responsive.${className} {
                        background-color: ${bgColor} !important;
                        color: ${textColor} !important;
                    }
                `;
            });

            style.textContent = css;
            document.head.appendChild(style);
        }

        function generateFallbackStyles() {
            const fallbackStatuses = [
                { status_name: 'pending-approval', color_code: '#707485' },
                { status_name: 'awaiting-payment', color_code: '#1c5b8f' },
                { status_name: 'scheduled', color_code: '#1e7941' },
                { status_name: 'ongoing', color_code: '#ac7a0f' },
                { status_name: 'completed', color_code: '#3e5568' },
                { status_name: 'rejected', color_code: '#3e5568' },
                { status_name: 'cancelled', color_code: '#3e5568' }
            ];
            generateStatusStyles(fallbackStatuses);
        }

        function handleFileSelection(file) {
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!validTypes.includes(file.type)) {
                showUploadError('Invalid file type. Please upload JPG, PNG, or PDF files only.');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showUploadError('File too large. Maximum size is 5MB.');
                return;
            }
            selectedFile = file;
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
            document.getElementById('uploadProgress').style.display = 'block';
            document.getElementById('confirmUploadBtn').disabled = true;

            const formData = new FormData();
            formData.append('file', selectedFile);
            formData.append('upload_preset', uploadPreset);
            formData.append('cloud_name', cloudName);

            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                }
            });

            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
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

            xhr.addEventListener('error', function() {
                showUploadError('Upload failed. Please check your connection and try again.');
                document.getElementById('uploadProgress').style.display = 'none';
            });

            xhr.open('POST', `https://api.cloudinary.com/v1_1/${cloudName}/auto/upload`);
            xhr.send(formData);
        }

        let allForms = [];
        let statuses = [];

        async function fetchStatuses() {
            try {
                const response = await fetch('/api/form-statuses');
                const data = await response.json();
                statuses = data.filter(status =>
                    status.status_name !== 'Returned' &&
                    status.status_name !== 'Late Return'
                );
                generateStatusStyles(statuses);
                
                const dropdownMenu = document.querySelector('#filterDropdownMenu');
                if (dropdownMenu) {
                    dropdownMenu.innerHTML = `
                        <a class="dropdown-item" href="#" data-status="all">All</a>
                        ${statuses.map(status => `
                            <a class="dropdown-item" href="#" data-status="${status.status_name.toLowerCase().replace(/\s+/g, '-')}">${status.status_name}</a>
                        `).join('')}
                    `;
                }
            } catch (error) {
                console.error('Failed to fetch statuses:', error);
                generateFallbackStyles();
            }
        }

        function showResults() {
            let searchInput;
            const resultsSection = document.getElementById('resultsSection');
            const lookupSection = document.getElementById('lookupSection');

            document.getElementById('noResultsMessage').style.display = 'none';

            if (resultsSection && resultsSection.style.display !== 'none') {
                searchInput = document.getElementById('resultsReferenceInput');
            } else {
                searchInput = document.getElementById('referenceInput');
            }

            if (!searchInput || !searchInput.value.trim()) {
                alert('Please enter a reference code');
                return;
            }

            const referenceInput = searchInput.value.trim();
            showLoading();
            fetchFormByAccessCode(referenceInput);
        }

        function showLoading() {
            const resultsSection = document.getElementById('resultsSection');
            const isResultsVisible = resultsSection && resultsSection.style.display !== 'none';

            const requisitionList = document.querySelector('.requisition-list');
            if (requisitionList) requisitionList.innerHTML = '';

            if (isResultsVisible) {
                const loadingSpinner = document.getElementById('resultsLoadingSpinner');
                const searchButton = document.getElementById('resultsSearchButton');
                if (loadingSpinner) loadingSpinner.style.display = 'block';
                if (searchButton) {
                    searchButton.classList.add('btn-loading');
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                }
            } else {
                const loadingSpinner = document.getElementById('loadingSpinner');
                const searchButton = document.getElementById('searchButton');
                if (loadingSpinner) loadingSpinner.style.display = 'block';
                if (searchButton) {
                    searchButton.classList.add('btn-loading');
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                }
            }
        }

        function hideLoading() {
            const loadingSpinner1 = document.getElementById('loadingSpinner');
            const loadingSpinner2 = document.getElementById('resultsLoadingSpinner');
            const searchButton1 = document.getElementById('searchButton');
            const searchButton2 = document.getElementById('resultsSearchButton');

            if (loadingSpinner1) loadingSpinner1.style.display = 'none';
            if (loadingSpinner2) loadingSpinner2.style.display = 'none';
            if (searchButton1) {
                searchButton1.classList.remove('btn-loading');
                searchButton1.innerHTML = '<i class="fas fa-search"></i> Search';
            }
            if (searchButton2) {
                searchButton2.classList.remove('btn-loading');
                searchButton2.innerHTML = '<i class="fas fa-search"></i> Search';
            }
        }

        async function fetchFormByAccessCode(accessCode) {
            try {
                const response = await fetch(`/api/requester/form/${accessCode}`);
                if (!response.ok) throw new Error(`Form not found (${response.status})`);
                const form = await response.json();
                if (!form.form_status || !form.purpose) throw new Error('Invalid form data structure');
                allForms = [form];
                document.getElementById('noResultsMessage').style.display = 'none';
                displayForms();
                document.getElementById('resultsReferenceInput').value = '';
                document.getElementById('referenceInput').value = '';
                document.getElementById('lookupSection').style.display = 'none';
                document.getElementById('resultsSection').style.display = 'block';
            } catch (error) {
                console.error('Error fetching form:', error);
                const noResultsMessage = document.getElementById('noResultsMessage');
                const lookupSection = document.getElementById('lookupSection');
                const resultsSection = document.getElementById('resultsSection');
                const requisitionList = document.querySelector('.requisition-list');
                if (requisitionList) requisitionList.innerHTML = '';
                lookupSection.style.display = 'none';
                resultsSection.style.display = 'none';
                noResultsMessage.style.display = 'none';
                const resultsInput = document.getElementById('resultsReferenceInput');
                const lookupInput = document.getElementById('referenceInput');
                if (resultsInput && resultsInput.value.trim() === accessCode) {
                    resultsSection.style.display = 'block';
                    if (requisitionList) requisitionList.style.display = 'none';
                    resultsInput.value = '';
                } else {
                    lookupSection.style.display = 'block';
                    if (lookupInput) lookupInput.value = '';
                }
                noResultsMessage.style.display = 'block';
            } finally {
                hideLoading();
            }
        }

        function calculateTotalFee(form) {
            if (form.total_fee && parseFloat(form.total_fee) > 0) return parseFloat(form.total_fee);
            if (form.fees && form.fees.approved_fee) return parseFloat(form.fees.approved_fee);
            return 0;
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric', month: 'long', day: 'numeric'
            });
        }

        function formatTime(timeString) {
            if (!timeString) return '';
            if (timeString.includes('AM') || timeString.includes('PM')) return timeString;
            let time = timeString.length > 5 ? timeString.substring(0, 5) : timeString;
            const [hours, minutes] = time.split(':');
            let hour = parseInt(hours, 10);
            const suffix = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            return `${hour}:${minutes} ${suffix}`;
        }

        function displayForms() {
            const requisitionList = document.querySelector('.requisition-list');
            if (!requisitionList) return;
            requisitionList.innerHTML = '';
            requisitionList.style.display = 'block';

            allForms.forEach(form => {
                if (!form.form_status || !form.purpose) return;
                const statusClass = form.form_status.status_name.toLowerCase().replace(/\s+/g, '-');
                const statusName = form.form_status.status_name;
                const totalFee = calculateTotalFee(form);

                let footerButtons = '';
                if (['Pending Approval', 'Scheduled'].includes(statusName)) {
                    footerButtons = `<button class="btn-danger" onclick="showCancelModal(${form.request_id})">Cancel Request</button>`;
                } else if (statusName === 'Awaiting Payment') {
                    footerButtons = `
                        <button class="btn-success" onclick="showUploadModal(${form.request_id})">Upload Receipt</button>
                        <button class="btn-danger" onclick="showCancelModal(${form.request_id})">Cancel Request</button>
                    `;
                }

                const facilitiesList = form.requested_facilities && form.requested_facilities.length > 0
                    ? form.requested_facilities.map(f => `<li>${f.facility_name}</li>`).join('')
                    : '<p class="no-items">No facilities requested</p>';

                const equipmentList = form.requested_equipment && form.requested_equipment.length > 0
                    ? form.requested_equipment.map(e => `<li>${e.equipment_name}</li>`).join('')
                    : '<p class="no-items">No equipment requested</p>';

                let organizationName = '';
                let requesterName = '';
                if (form.user_details) {
                    organizationName = form.user_details.organization_name || '';
                    requesterName = `${form.user_details.first_name || ''} ${form.user_details.last_name || ''}`.trim();
                } else if (form.organization_name || form.first_name || form.last_name) {
                    organizationName = form.organization_name || '';
                    requesterName = `${form.first_name || ''} ${form.last_name || ''}`.trim();
                }

                const card = `
                    <div class="card-responsive">
                        <div class="card-responsive-header">
                            <span class="request-id">Request #${form.request_id.toString().padStart(4, '0')}</span>
                            <span class="status-badge-responsive ${statusClass}">${statusName}</span>
                        </div>
                        <div class="card-responsive-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    ${organizationName ? `<p class="mb-2"><strong>Organization:</strong> ${organizationName}</p>` : ''}
                                    ${requesterName ? `<p class="mb-2"><strong>Requester:</strong> ${requesterName}</p>` : ''}
                                    <p class="mb-2"><strong>Purpose:</strong> ${form.purpose.purpose_name}</p>
                                    <p class="mb-2"><strong>Start:</strong> ${formatDate(form.start_date)}, ${formatTime(form.start_time)}</p>
                                    <p class="mb-2"><strong>End:</strong> ${formatDate(form.end_date)}, ${formatTime(form.end_time)}</p>
                                    <p class="mb-0"><strong>Total Fee:</strong> ₱${totalFee.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                </div>
                                <div class="col-12 col-md-6">
                                    <p class="mb-1"><strong>Facilities:</strong></p>
                                    <ul class="mb-3">${facilitiesList}</ul>
                                    <p class="mb-1"><strong>Equipment:</strong></p>
                                    <ul class="mb-0">${equipmentList}</ul>
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
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
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
            const cards = document.querySelectorAll('.requisition-list .card-responsive');
            cards.forEach(card => {
                if (status === 'all') {
                    card.style.display = 'block';
                } else {
                    const statusBadge = card.querySelector('.status-badge-responsive');
                    if (statusBadge) {
                        const badgeStatus = statusBadge.textContent.toLowerCase().replace(/\s+/g, '-');
                        card.style.display = badgeStatus === status ? 'block' : 'none';
                    }
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('noResultsMessage').style.display = 'none';

            // Upload area handlers
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('receiptFile');
            if (uploadArea) {
                uploadArea.addEventListener('click', () => fileInput.click());
                uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.style.borderColor = 'var(--navy)'; });
                uploadArea.addEventListener('dragleave', () => uploadArea.style.borderColor = 'var(--border)');
                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.style.borderColor = 'var(--border)';
                    if (e.dataTransfer.files.length) handleFileSelection(e.dataTransfer.files[0]);
                });
            }
            if (fileInput) {
                fileInput.addEventListener('change', function() { if (this.files.length) handleFileSelection(this.files[0]); });
            }

            // Filter dropdown
            const filterBtn = document.getElementById('filterDropdownBtn');
            const filterMenu = document.getElementById('filterDropdownMenu');
            if (filterBtn && filterMenu) {
                filterBtn.addEventListener('click', () => filterMenu.classList.toggle('show'));
                document.addEventListener('click', (e) => {
                    if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
                        filterMenu.classList.remove('show');
                    }
                });
                filterMenu.addEventListener('click', (e) => {
                    if (e.target.classList.contains('dropdown-item')) {
                        e.preventDefault();
                        const status = e.target.getAttribute('data-status');
                        filterRequisitions(status);
                        filterBtn.innerHTML = `<i class="fas fa-filter"></i> Filter: ${status === 'all' ? 'All' : e.target.textContent}`;
                        filterMenu.classList.remove('show');
                    }
                });
            }

            // Cancel confirmation
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');
            if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', cancelRequest);

            fetchStatuses();
        });
    </script>
@endsection