@extends('layouts.admin')
@section('title', 'Review Request')
@section('content')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/request-view.css') }}">

    <style>
        /* Sticky Activity Timeline Button - Always visible */
        .sticky-timeline-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #004080;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 999;
            transition: all 0.3s ease;
            opacity: 1;
            visibility: visible;
        }

        .sticky-timeline-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            background-color: #355a8f;
        }

        .sticky-timeline-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            background-color: #355a8f;
        }

        /* Activity Timeline Modal */
        .timeline-modal {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            display: none;
            /* Hide by default */
            border: 1px solid #e0e0e0;
            flex-direction: column;
        }

        .timeline-modal.show {
            display: flex;
            /* Show when .show class is added */
        }

        .timeline-modal .modal-header {
            padding: 12px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }

        .timeline-modal .modal-header h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .timeline-modal .modal-header .close-timeline {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #666;
            padding: 0 4px;
        }

        .timeline-modal .modal-header .close-timeline:hover {
            color: #dc3545;
        }

        .timeline-modal .modal-body {
            padding: 16px;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            /* Important for flex child scrolling */
        }

        .timeline-modal .modal-footer {
            padding: 8px 12px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .timeline-modal .input-group {
            display: flex;
            gap: 8px;
        }

        .timeline-modal textarea {
            flex: 1;
            border-radius: 20px !important;
            resize: none;
            font-size: 0.85rem;
        }

        /* Back to Top Button - Center when appearing */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #004080;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 998;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: #5a6268;
            transform: translateX(-50%) translateY(-3px);
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .timeline-modal {
                width: 300px;
                right: 10px;
                bottom: 130px;
            }

            .sticky-timeline-btn {
                bottom: 70px;
                right: 10px;
            }
        }

        /* Vertical documents stack */
        .documents-vertical {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .documents-vertical .document-mini-item {
            margin-bottom: 0 !important;
        }

        /* Custom button for documents with no data */
        .btn-document-null {
            background-color: #f0f0f0 !important;
            color: #999999 !important;
            border: 1px solid #e0e0e0 !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }

        .btn-document-null:hover {
            background-color: #f0f0f0 !important;
            color: #999999 !important;
        }

        /* Equal height cards with scrolling */
        .status-summary-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .status-summary-card .card-body {
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
            /* Adjust based on your needs */
        }

        .action-panel-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .action-panel-card .card-body {
            flex: 1;
            overflow-y: auto;
            max-height: 300px;
            /* Same as status summary card */
        }

        /* Ensure the row children have equal height */
        .row.g-2.mb-2>[class*="col-"] {
            display: flex;
        }

        .row.g-2.mb-2>[class*="col-"]>.card {
            width: 100%;
        }


        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        /* Action buttons spacing */
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .btn-success,
        .btn-danger,
        .btn-primary,
        .btn-secondary {
            transition: all 0.2s ease;
        }

        .btn-success:hover {
            background-color: #218838 !important;
            transform: translateY(-1px);
        }

        .btn-danger:hover {
            background-color: #c82333 !important;
            transform: translateY(-1px);
        }

        /* Approval pills */
        .status-pill {
            transition: all 0.2s ease;
        }

        .status-pill.border-success:hover {
            background-color: #d4edda !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .status-pill.border-danger:hover {
            background-color: #f8d7da !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }

        /* Dropdown menu styling */
        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.5rem 0;
        }

        .dropdown-item {
            transition: background-color 0.2s ease;
            font-size: 0.9rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item i {
            width: 1.2rem;
            color: #6c757d;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .status-pill {
                height: 70px !important;
            }

            .status-pill i {
                font-size: 1rem !important;
            }

            .status-pill span:last-child {
                font-size: 1rem !important;
            }

            .btn {
                padding: 0.5rem 0.75rem !important;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .col-6 {
                padding: 0 0.25rem;
            }

            .status-pill {
                height: 65px !important;
                padding: 0.5rem !important;
            }

            .status-pill span.fw-bold.small {
                font-size: 0.7rem !important;
            }
        }

        /* Document mini items styling */
        .document-mini-item {
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }

        .document-mini-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }

        .document-mini-item i {
            transition: color 0.2s ease;
        }

        .document-mini-item:hover i {
            color: #4272b1ff !important;
        }

        /* Updated status pills styling to match document items */
        .status-pill {
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .status-pill.border-success:hover {
            background-color: #d4edda !important;
            transform: translateY(-2px);
        }

        .status-pill.border-danger:hover {
            background-color: #f8d7da !important;
            transform: translateY(-2px);
        }

        /* Documents mini grid for mobile */
        .documents-mini-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }

        @media (max-width: 576px) {
            .documents-mini-grid {
                grid-template-columns: 1fr;
            }

            .document-mini-item {
                margin-bottom: 0 !important;
            }
        }

        /* Status pills container */
        .status-pills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        /* Ensure pills don't get too small */
        @media (max-width: 768px) {
            .status-pill {
                flex: 1 1 auto;
                justify-content: center;
                min-width: 140px;
            }
        }

        /* Icon colors when document is uploaded */
        .document-mini-item .text-primary {
            color: #4272b1ff !important;
        }

        .document-mini-item i.text-primary {
            filter: drop-shadow(0 2px 2px rgba(66, 114, 177, 0.2));
        }

        /* Button styling inside document items */
        .document-mini-item .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .document-mini-item .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .btn-outline-secondary {
            background: none !important;
            color: #003366 !important;
            border: none !important;
        }

        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus {
            background: #e6e6e6 !important;
            /* light gray */
            color: #003366 !important;
        }

        .hover-pointer:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Set fixed height for both cards */
        .status-action-row .card {
            height: 140px;
            /* Adjust this value as needed */
        }

        /* Status summary card specific styling */
        .status-summary-card .card-body {
            height: calc(100% - 60px);
            /* Subtract card header height */
            display: flex;
            align-items: center;
            /* Vertically center the pills */
            padding: 10px 16px;
        }

        /* Action panel specific styling */
        .action-panel-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Status pills container */
        .status-pills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            width: 100%;
        }

        /* Individual pills - fixed height, no stretching */
        .status-pill {
            height: 32px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            flex-shrink: 0;
            margin: 0;
        }

        .request-code {
            background-color: #f4f4f4;
            /* subtle background */
            color: #292929ff;
            /* Bootstrap primary color */
            font-family: "Courier New", monospace;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            /* vertically center with text */
            line-height: 1.2;
        }

        /* Mobile-first responsive styles */
        @media (max-width: 768px) {
            .mobile-collapsible-section {
                margin-bottom: 1rem;
            }

            .mobile-collapsible-header {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 1rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .mobile-collapsible-header:hover {
                background: #f8f9fa;
            }

            .mobile-collapsible-header.active {
                background: #f0f7ff;
                border-color: #4272b1ff;
            }

            .mobile-collapsible-content {
                display: none;
                padding: 1rem;
                background: white;
                border: 1px solid #e0e0e0;
                border-top: none;
                border-radius: 0 0 8px 8px;
            }

            .mobile-collapsible-content.show {
                display: block;
            }

            .mobile-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .mobile-info-card {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 0.5rem;
            }

            .mobile-info-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 0.5rem 0;
                border-bottom: 1px solid #f0f0f0;
            }

            .mobile-info-row:last-child {
                border-bottom: none;
            }

            .mobile-label {
                font-weight: 600;
                color: #555;
                font-size: 0.9rem;
                flex: 0 0 45%;
            }

            .mobile-value {
                color: #333;
                font-size: 0.9rem;
                flex: 0 0 55%;
                text-align: right;
            }

            .mobile-documents-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .mobile-document-card {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 1rem;
                text-align: center;
            }

            .mobile-floating-actions {
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                z-index: 1000;
            }

            .mobile-action-fab {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: #4272b1ff;
                color: white;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                cursor: pointer;
            }

            .mobile-action-menu {
                position: absolute;
                bottom: 70px;
                right: 0;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: none;
                min-width: 200px;
            }

            .mobile-action-menu.show {
                display: block;
            }

            .mobile-action-item {
                padding: 0.75rem 1rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                transition: background 0.2s;
            }

            .mobile-action-item:last-child {
                border-bottom: none;
            }

            .mobile-action-item:hover {
                background: #f8f9fa;
            }

            .mobile-status-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .mobile-status-card {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 0.75rem;
                text-align: center;
            }

            .mobile-timeline-item {
                display: flex;
                gap: 0.75rem;
                padding: 0.75rem;
                border-left: 3px solid #4272b1ff;
                background: white;
                margin-bottom: 0.75rem;
                border-radius: 0 8px 8px 0;
            }

            .mobile-fee-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem;
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin-bottom: 0.5rem;
            }
        }

        /* Responsive utilities */
        .mobile-only {
            display: none;
        }

        .desktop-only {
            display: block;
        }

        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none;
            }

            /* Hide desktop table views */
            .table {
                display: none;
            }
        }
    </style>

    <!-- Main Content -->
    <main id="main">

        <div class="card bg-transparent shadow-none pt-0"
            style="border: none !important; background-color: transparent !important">
            <div class="card-body">
                <!-- Skeleton Loading -->
                <div id="loadingState">
                    <div class="row g-3">
                        <!-- User Information Skeleton -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                    <hr>
                                    <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 90%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 95%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Request Details Skeleton -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                    <hr>
                                    <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 90%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 95%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Calendar Skeleton -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                    <div class="skeleton" style="height: 450px;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Items Skeleton -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 90%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Fees Skeleton -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 90%;"></div>
                                    <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actual Content (Initially Hidden) -->
                <div id="contentState" style="display: none;">

                    <!-- Request Status + Action Panel -->
                    <div class="row g-2 mb-2">
                        <!-- Status Summary -->
                        <div class="col-md-9">
                            <div class="card">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center text-primary fw-bold">
                                        Currently Reviewing:
                                        <code id="requestIdTitle" class="request-code ms-1"></code>
                                    </div>
                                    <span id="statusBadge" class="badge ms-1"></span>
                                </div>

                                <!-- Two-column layout: Contact Info (left) and Documents (right) -->
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Contact Information Column (left side) -->
                                        <div class="col-md-6">
                                            <div class="text-muted small fw-semibold mb-2"
                                                style="letter-spacing: 0.3px; font-size: 0.75rem;">CONTACT INFORMATION</div>
                                            <div id="formDetails" class="bg-light p-2 rounded" style="font-size: 0.85rem;">
                                                <!-- Content will be populated by JS -->
                                            </div>
                                        </div>

                                        <!-- Documents Column (right side) - Vertical stack -->
                                        <div class="col-md-6">
                                            <div class="text-muted small fw-semibold mb-2"
                                                style="letter-spacing: 0.3px; font-size: 0.75rem;">DOCUMENTS</div>
                                            <div class="documents-vertical">
                                                <!-- Formal Letter -->
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="formalLetterIcon" class="fas fa-file-alt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Formal Letter</div>
                                                    </div>
                                                    <button type="button" id="formalLetterBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>

                                                <!-- Facility Layout -->
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="facilityLayoutIcon"
                                                        class="fas fa-map-marked-alt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Facility Layout</div>
                                                    </div>
                                                    <button type="button" id="facilityLayoutBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>

                                                <!-- Proof of Payment -->
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="proofOfPaymentIcon" class="fas fa-receipt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Payment Confirmation</div>
                                                    </div>
                                                    <button type="button" id="proofOfPaymentBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>

                                                <!-- Official Receipt -->
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="officialReceiptIcon"
                                                        class="fas fa-file-invoice-dollar fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Transaction Record</div>
                                                    </div>
                                                    <button type="button" id="officialReceiptBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Panel with Approvals (unchanged) -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Actions & Approvals</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Actions Section -->
                                    <div class="mb-4">
                                        <h6 class="text-muted mb-2"
                                            style="font-size: 0.8rem; letter-spacing: 0.5px; font-weight: 600;">ACTIONS</h6>
                                        <button class="btn btn-success w-100 mb-2" id="approveBtn"
                                            style="padding: 0.5rem 1rem;">
                                            <i class="bi bi-hand-thumbs-up me-2"></i> Approve
                                        </button>
                                        <button class="btn btn-danger w-100 mb-2" id="rejectBtn"
                                            style="padding: 0.5rem 1rem;">
                                            <i class="bi bi-hand-thumbs-down me-2"></i> Reject
                                        </button>
                                        <!-- Finalize Button (Hidden by default, shown for Head Admin) -->
                                        <button class="btn btn-primary w-100 mb-2" id="finalizeBtn"
                                            style="display: none; padding: 0.5rem 1rem;">
                                            <i class="bi bi-check-circle me-2"></i> Finalize
                                        </button>
                                        <!-- More Actions Dropdown -->
                                        <div class="dropdown w-100" id="moreActionsDropdown">
                                            <button
                                                class="btn btn-secondary bg-secondary-subtle text-dark w-100 dropdown-toggle"
                                                type="button" id="moreActionsBtn" data-bs-toggle="dropdown"
                                                aria-expanded="false" style="padding: 0.5rem 1rem;">
                                                <i class="bi bi-three-dots me-2"></i> More
                                            </button>
                                            <ul class="dropdown-menu w-100" style="min-width: 100%;"
                                                aria-labelledby="moreActionsBtn">
                                                <li>
                                                    <a class="dropdown-item text-start w-100 status-option"
                                                        id="statusScheduled" data-value="Scheduled" href="#"
                                                        style="padding: 0.5rem 1rem;">
                                                        <i class="bi bi-calendar-event me-2"></i> Mark Scheduled
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-start w-100 status-option"
                                                        id="statusOngoing" data-value="Ongoing" href="#"
                                                        style="padding: 0.5rem 1rem;">
                                                        <i class="bi bi-play-circle me-2"></i> Mark Ongoing
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-start w-100 status-option" id="statusLate"
                                                        data-value="Late" href="#" style="padding: 0.5rem 1rem;">
                                                        <i class="bi bi-exclamation-circle me-2"></i> Mark Late
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-start w-100" id="closeForm" href="#"
                                                        style="padding: 0.5rem 1rem;">
                                                        <i class="bi bi-x-circle me-2"></i> Close Form
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Approvals Section -->
                                    <div>
                                        <h6 class="text-muted mb-2"
                                            style="font-size: 0.8rem; letter-spacing: 0.5px; font-weight: 600;">APPROVALS
                                        </h6>
                                        <div class="row g-2">
                                            <!-- Approvals Pill -->
                                            <div class="col-6">
                                                <div class="status-pill p-2 bg-white border border-success text-success rounded d-flex flex-column align-items-center justify-content-center hover-pointer shadow-sm text-center"
                                                    role="button" data-bs-toggle="modal" data-bs-target="#approvalsModal"
                                                    style="height: 80px; width: 100%;">
                                                    <i class="fa fa-thumbs-up mb-1" style="font-size: 1.2rem;"></i>
                                                    <span class="fw-bold small" style="font-size: 0.75rem;">Approvals</span>
                                                    <span class="fw-bold" id="approvalsCount"
                                                        style="font-size: 1.1rem;">0</span>
                                                </div>
                                            </div>

                                            <!-- Rejections Pill -->
                                            <div class="col-6">
                                                <div class="status-pill p-2 bg-white border border-danger text-danger rounded d-flex flex-column align-items-center justify-content-center hover-pointer shadow-sm text-center"
                                                    role="button" data-bs-toggle="modal" data-bs-target="#rejectionsModal"
                                                    style="height: 80px; width: 100%;">
                                                    <i class="fa fa-thumbs-down mb-1" style="font-size: 1.2rem;"></i>
                                                    <span class="fw-bold small"
                                                        style="font-size: 0.75rem;">Rejections</span>
                                                    <span class="fw-bold" id="rejectionsCount"
                                                        style="font-size: 1.1rem;">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information and Booking Details Side by Side -->
                    <div class="row g-2">

                        <!-- Booking Details -->
                        <div class="col-12">
                            <div class="card h-100">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Booking Details</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Event Details Section -->
                                    <div id="eventDetails" class="mb-4"></div>

                                    <!-- Requested Items Section -->
                                    <h6 class="fw-bold text-center mb-3"
                                        style="font-size:0.9rem; display:flex; align-items:center;">
                                        <span style="flex:1; height:1px; background:#ccc; margin-right:0.5rem;"></span>
                                        Requested Items
                                        <span style="flex:1; height:1px; background:#ccc; margin-left:0.5rem;"></span>
                                    </h6>

                                    <div id="requestedItems" style="font-size:0.9rem;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rest of the content remains the same from here... -->
                    <!-- Status Update Confirmation Modal -->
                    <div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Status Change</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="statusModalContent">
                                        <!-- Content will be dynamically populated -->
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmStatusUpdate">
                                        <span class="spinner-border spinner-border-sm d-none" role="status"
                                            aria-hidden="true"></span>
                                        Confirm Change
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Fee Modal -->
                    <div class="modal fade" id="feeModal" tabindex="-1" aria-labelledby="feeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="feeModalLabel">Add Fee or Discount</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="feeForm">
                                        <input type="hidden" id="feeRequestId" value="{{ $requestId }}">
                                        <div class="mb-2">
                                            <label for="feeType" class="form-label">Fee Type</label>
                                            <select id="feeType" class="form-select" required>
                                                <option value="">Select type...</option>
                                                <option value="additional">Additional Fee</option>
                                                <option value="discount">Discount</option>
                                                <option value="vat">Less VAT (12%)</option>
                                            </select>
                                        </div>
                                        <div class="mb-2" id="discountTypeSection" style="display: none;">
                                            <label for="discountType" class="form-label">Discount Type</label>
                                            <select id="discountType" class="form-select">
                                                <option value="Fixed">Fixed Amount</option>
                                                <option value="Percentage">Percentage</option>
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <!-- Updated: Added Account Number field -->
                                            <div class="col-md-6">
                                                <label for="feeLabel" class="form-label">Fee Label</label>
                                                <input type="text" id="feeLabel" class="form-control"
                                                    placeholder="Fee Label" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="accountNum" class="form-label">Account Number
                                                    (Optional)</label>
                                                <input type="text" id="accountNum" class="form-control"
                                                    placeholder="Enter account number">
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="feeValue" class="form-label">Amount</label>
                                                <input type="number" id="feeValue" class="form-control" step="0.01"
                                                    min="0.01" placeholder="Enter amount" required>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" id="saveFeeBtn" class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mt-1 align-items-stretch">
                        <!-- Right Column: Fee Breakdown -->
                        <div class="col-lg-12 d-flex">
                            <div class="card flex-fill d-flex flex-column">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Fee Breakdown</h5>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="waiveAllSwitch">
                                        <label class="form-check-label" for="waiveAllSwitch">Waive All Fees</label>
                                    </div>
                                </div>

                                <div class="card-body flex-fill overflow-auto">
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-2">Base Fees</h6>
                                        <div id="baseFeesContainer">
                                            <div id="facilitiesFees"></div>
                                            <div id="equipmentFees"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold mb-0">Miscellaneous Fees</h6>
                                            <button id="addFeeBtn" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-plus"></i> Add Fee/Discount
                                            </button>
                                        </div>
                                        <div id="additionalFeesContainer"></div>
                                    </div>
                                </div>

                                <div
                                    class="card-footer bg-white d-flex justify-content-between align-items-center fw-bold p-3 border-top">
                                    <span>Total Approved Fee:</span>
                                    <span id="feeBreakdownTotal">0.00</span> <!-- This is the correct ID -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOR APPROVE/REJECT BUTTONS -->

                    <style>
                        .expand-btn {
                            width: 50px;
                            height: 50px;
                            border-radius: 25px;
                            display: flex;
                            align-items: center;
                            justify-content: flex-start;
                            padding: 2px 13px;
                            font-size: 1.5rem;
                            border: none;
                            cursor: pointer;
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            position: relative;
                            overflow: hidden;
                        }

                        .expand-btn i {
                            flex-shrink: 0;
                            z-index: 2;
                            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        }

                        .expand-btn .btn-text {
                            font-size: 0.9rem;
                            font-weight: 500;
                            margin-left: 12px;
                            opacity: 0;
                            transform: translateX(-10px);
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            white-space: nowrap;
                        }

                        .expand-btn:hover {
                            width: 130px;
                            justify-content: flex-start;
                            padding-left: 15px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        }

                        .expand-btn:hover .btn-text {
                            opacity: 1;
                            transform: translateX(0);
                        }

                        .expand-btn:hover i {
                            transform: scale(1.1);
                        }

                        /* Button specific styles */
                        .btn-danger.expand-btn {
                            background-color: #dc3545;
                            color: white;
                        }

                        .btn-danger.expand-btn:hover {
                            background-color: #c82333;
                        }

                        .btn-success.expand-btn {
                            background-color: #28a745;
                            color: white;
                        }

                        .btn-success.expand-btn:hover {
                            background-color: #218838;
                        }

                        .expand-btn:active {
                            transform: scale(0.98);
                        }

                        /* Mobile responsive for approval pills */
                        @media (max-width: 768px) {
                            .status-pill {
                                padding: 0.5rem !important;
                            }

                            .status-pill i {
                                font-size: 1rem;
                            }

                            .status-pill span:last-child {
                                font-size: 1rem;
                            }
                        }
                    </style>
                    <div>
                        <br><br><br><br><br>
                    </div>

                </div>

                <!-- Confirmation Modal for Approve Action -->
                <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Approval</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to approve this request? This action cannot be undone.</p>
                                <p class="text-muted small">You will not be able to take further actions on this form
                                    after
                                    approval.</p>
                                <div class="mb-3">
                                    <label for="approveRemarks" class="form-label">Remarks (Optional)</label>
                                    <textarea class="form-control" id="approveRemarks" rows="3"
                                        placeholder="Add any remarks here..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" id="confirmApprove">Confirm
                                    Approval</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal for Reject Action -->
                <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Rejection</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to reject this request? This action cannot be undone.</p>
                                <p class="text-muted small">You will not be able to take further actions on this form
                                    after
                                    rejection.</p>
                                <div class="mb-3">
                                    <label for="rejectRemarks" class="form-label">Remarks (Optional)</label>
                                    <textarea class="form-control" id="rejectRemarks" rows="3"
                                        placeholder="Add any remarks here..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmReject">Confirm
                                    Rejection</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal for Finalize Action -->
                <div class="modal fade" id="finalizeModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Finalize Request</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2">
                                    <div class="col">
                                        <div class="alert alert-success mb-0">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <strong>Approvals:</strong>
                                            <span id="currentApprovalCount" class="fw-bold"></span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="alert alert-danger mb-0">
                                            <i class="bi bi-x-circle me-2"></i>
                                            <strong>Rejections:</strong>
                                            <span id="currentRejectionCount" class="fw-bold"></span>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="text-center">
                                    <h6 class="fw-bold mb-3">Are you sure? This action cannot be undone.</h6>
                                    <p class="text-muted small">Finalizing this request will change its status from "Pending
                                        Approval" to "Awaiting Payment". This action cannot be undone and will prevent
                                        further approvals/rejections for signatories.</p>
                                </div>


                                <div class="mb-3">
                                    <label for="calendarTitle" class="form-label">Event Title</label>
                                    <input type="text" class="form-control" id="calendarTitle"
                                        placeholder="Enter calendar event title" required maxlength="50"
                                        value="{{ $request->form_details->purpose ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label for="calendarDescription" class="form-label">Event Description</label>
                                    <textarea class="form-control" id="calendarDescription" rows="3"
                                        placeholder="Enter calendar event description" required maxlength="100"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmFinalize">
                                    <span class="spinner-border spinner-border-sm me-1 align-middle d-none" role="status"
                                        aria-hidden="true"></span>
                                    Finalize Request
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Event Details Modal -->
                <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
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
                                                    <i class="bi bi-pencil text-secondary ms-2" id="editCalendarTitleBtn"
                                                        style="cursor: pointer;"></i>
                                                    <div class="edit-actions ms-2 d-none" id="calendarTitleActions">
                                                        <button type="button" class="btn btn-sm btn-success me-1"
                                                            id="saveCalendarTitleBtn">
                                                            <i class="bi bi-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            id="cancelCalendarTitleBtn">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </label>
                                                <input type="text" class="form-control text-secondary"
                                                    id="modalCalendarTitle" readonly>
                                            </div>

                                            <!-- Calendar Description -->
                                            <div class="mb-0">
                                                <label class="form-label fw-bold d-flex align-items-center mb-2">
                                                    Calendar Description
                                                    <i class="bi bi-pencil text-secondary ms-2"
                                                        id="editCalendarDescriptionBtn" style="cursor: pointer;"></i>
                                                    <div class="edit-actions ms-2 d-none" id="calendarDescriptionActions">
                                                        <button type="button" class="btn btn-sm btn-success me-1"
                                                            id="saveCalendarDescriptionBtn">
                                                            <i class="bi bi-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            id="cancelCalendarDescriptionBtn">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
                                                </label>
                                                <textarea class="form-control text-secondary" id="modalCalendarDescription"
                                                    rows="2" readonly></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-none mb-3 p-3">
                                    <table class="table table-bordered mb-0 w-100"
                                        style="table-layout: fixed; border: 1px solid #dee2e6;">
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
                                                                <td id="modalRequester" class="p-2"
                                                                    style="border-bottom: 1px solid #dee2e6;"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                                    Purpose
                                                                </th>
                                                                <td id="modalPurpose" class="p-2"
                                                                    style="border-bottom: 1px solid #dee2e6;"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                                    Participants
                                                                </th>
                                                                <td id="modalParticipants" class="p-2"
                                                                    style="border-bottom: 1px solid #dee2e6;"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                                    Status
                                                                </th>
                                                                <td id="modalStatus" class="p-2"
                                                                    style="border-bottom: 1px solid #dee2e6;"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                                                                    Approved Fee
                                                                </th>
                                                                <td id="modalFee" class="p-2"
                                                                    style="border-bottom: 1px solid #dee2e6;"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6;">Approvals</th>
                                                                <td id="modalApprovals" class="p-2"></td>
                                                            </tr>
                                                            <tr>
                                                                <th class="bg-light text-nowrap p-2"
                                                                    style="border-right: 1px solid #dee2e6;">Rejections</th>
                                                                <td id="modalRejections" class="p-2"></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                                <td style="border: 1px solid #dee2e6; vertical-align: top; padding: 0;">
                                                    <div id="modalItems" class="p-3" style="min-height: 100%;">
                                                        <!-- JS will insert items here -->
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="modalViewDetails">View Full
                                    Details</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Close Form Confirmation Modal -->
                <div class="modal fade" id="closeFormModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Close Form</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="bi bi-exclamation-triangle fa-3x text-danger mb-3"></i>
                                    <p>Are you sure you want to close this form?</p>
                                    <p class="text-muted small">
                                        This will mark the form as completed. This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmCloseForm">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                        aria-hidden="true"></span>
                                    Confirm Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="markScheduledModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Mark as Scheduled</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to mark this request as scheduled? This will generate an official
                                    receipt.</p>

                                <div class="mb-3">
                                    <label for="officialReceiptNum" class="form-label">Official Receipt Number *</label>
                                    <input type="text" class="form-control" id="officialReceiptNum"
                                        placeholder="Enter official receipt number" required>
                                    <div class="form-text">This will be the unique identifier for the official receipt.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="scheduledCalendarTitle" class="form-label">Calendar Event Title</label>
                                    <input type="text" class="form-control" id="scheduledCalendarTitle"
                                        placeholder="Enter calendar event title" maxlength="50">
                                </div>

                                <div class="mb-3">
                                    <label for="scheduledCalendarDescription" class="form-label">Calendar Event
                                        Description</label>
                                    <textarea class="form-control" id="scheduledCalendarDescription" rows="3"
                                        placeholder="Enter calendar event description" maxlength="100"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmMarkScheduled">
                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                        aria-hidden="true"></span>
                                    Confirm & Generate Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Activity Timeline Button -->
                <button class="sticky-timeline-btn" id="stickyTimelineBtn" title="Activity Timeline">
                    <i class="fas fa-comment"></i>
                </button>

                <!-- Activity Timeline Modal -->
                <div class="timeline-modal" id="timelineModal">
                    <div class="modal-header" id="timelineModalHeader">
                        <h6><i class="fas fa-history me-2"></i>Activity Timeline</h6>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary p-1" id="refreshTimelineBtn" title="Refresh">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <select id="timelineFilter" class="form-select form-select-sm"
                                style="width: auto; font-size: 0.8rem;">
                                <option value="all">All</option>
                                <option value="comment">Comments</option>
                                <option value="fee">Fees</option>
                            </select>
                            <button class="close-timeline" id="closeTimelineBtn">&times;</button>
                        </div>
                    </div>
                    <div class="modal-body" id="timelineModalBody">
                        <div id="timelineContent" class="activity-timeline-content">
                            <!-- Content will be loaded here -->
                            <div class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="small mb-0">Loading activity...</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex flex-column w-100 gap-2">
                            <div class="input-group">
                                <textarea class="form-control" rows="1" placeholder="Add a comment..." id="timelineComment"
                                    style="height: 36px;"></textarea>
                                <button class="btn btn-primary rounded-circle" id="timelineSendBtn"
                                    style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back to Top Button -->
                <button class="back-to-top" id="backToTop" title="Back to Top">
                    <i class="bi bi-arrow-up"></i>
                </button>


                <div class="modal fade" id="approvalsModal" tabindex="-1" aria-labelledby="approvalsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="approvalsModalLabel">Approvals</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Content will be dynamically populated -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="rejectionsModal" tabindex="-1" aria-labelledby="rejectionsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rejectionsModalLabel">Rejections</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Content will be dynamically populated -->
                            </div>
                        </div>
                    </div>
                </div>

    </main>

@endsection

@section('scripts')
    <script>
        function formatMoney(amount) {
            // Handle different input types
            let num = parseFloat(amount);
            if (isNaN(num)) return '₱0.00';

            // Format with commas and 2 decimal places
            return '₱' + num.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }



        document.addEventListener('DOMContentLoaded', function () {

            // Back to Top Button
            const backToTopButton = document.getElementById('backToTop');

            window.addEventListener('scroll', function () {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            });

            backToTopButton.addEventListener('click', function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            const markScheduledModal = new bootstrap.Modal(document.getElementById('markScheduledModal'));
            const closeFormModal = new bootstrap.Modal(document.getElementById('closeFormModal'));
            const commentsContainer = document.getElementById('formRemarks');
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            let allRequests = [];
            let currentComments = [];
            let currentFees = [];
            let currentRequestId = null;
            let originalCalendarTitle = '';
            let originalCalendarDescription = '';
            let eventListenersSetup = false;

            // Mark notification as read when viewing the request
            markNotificationAsRead(requestId);


            async function markNotificationAsRead(requestId) {
                try {
                    const adminToken = localStorage.getItem('adminToken');
                    if (!adminToken) {
                        console.error('No authentication token found');
                        return;
                    }

                    const response = await fetch(`/api/admin/notifications/requisition/${requestId}/mark-as-read`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.ok) {
                        console.log(`Notification for request ${requestId} marked as read`);
                        // Update the global unread count
                        updateGlobalUnreadCount();
                    } else {
                        console.error('Failed to mark notification as read');
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            function updateGlobalUnreadCount() {
                // This would update any global notification badge in the layout
                const navBadge = document.getElementById('requisitionNotificationBadge');
                if (navBadge) {
                    // You might want to fetch the updated count from the server
                    // or decrement the current count
                    const currentCount = parseInt(navBadge.textContent) || 0;
                    if (currentCount > 0) {
                        navBadge.textContent = currentCount - 1;
                        if (currentCount - 1 === 0) {
                            navBadge.style.display = 'none';
                        }
                    }
                }
            }


            // Initialize Bootstrap modal
            const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            const feeModal = new bootstrap.Modal(document.getElementById('feeModal'));

            // Form Action Modals
            const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
            const finalizeModal = new bootstrap.Modal(document.getElementById('finalizeModal'));

            // Function to handle mark scheduled action
            function handleMarkScheduled() {
                markScheduledModal.show();
            }


            // Fee management elements
            const feesContainer = document.getElementById("additionalFees");
            const placeholder = document.getElementById("feesPlaceholder");
            const addFeeBtn = document.getElementById("addFeeBtn");
            const saveFeeBtn = document.getElementById("saveFeeBtn");
            const feeTypeSelect = document.getElementById("feeType");
            const feeValueInput = document.getElementById("feeValue");
            const discountTypeSection = document.getElementById("discountTypeSection");

            // Status update functionality
            const statusDropdown = document.getElementById('statusDropdown');
            const moreDropdown = document.getElementById('moreDropdown');
            const statusOptions = document.querySelectorAll('.status-option');
            const updateStatusBtn = document.getElementById('updateStatusBtn');
            const statusUpdateModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
            let selectedStatus = '';

            // Function to check admin role and update UI accordingly
            async function checkAdminRoleAndUpdateUI() {
                try {
                    const adminToken = localStorage.getItem('adminToken');
                    const response = await fetch('/api/admin/profile', {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        console.error('Failed to fetch admin profile. Status:', response.status);
                        return;
                    }

                    const adminData = await response.json();
                    console.log('Admin role data:', {
                        adminId: adminData.admin_id,
                        roleTitle: adminData.role?.role_title,
                        departments: adminData.departments?.map(d => d.department_name)
                    });

                    // Get role title
                    const roleTitle = adminData.role?.role_title;
                    const isHeadAdmin = roleTitle === 'Head Admin';
                    const isApprovingOfficer = roleTitle === 'Approving Officer' || roleTitle === 'Chief Approving Officer';

                    // Get current request status
                    const currentRequest = allRequests.find(req => req.request_id == requestId);
                    if (!currentRequest) return;

                    const currentStatusId = currentRequest.form_details.status.id;

                    // Get the ACTIONS section container (the div with class "mb-4" that contains the ACTIONS label)
                    const actionsSection = document.querySelector('.col-md-3 .card-body .mb-4');
                    if (!actionsSection) return;

                    // Clear any existing dynamic content in the actions section (keep the ACTIONS label)
                    const existingButtons = actionsSection.querySelectorAll('.dynamic-action-btn, .action-taken-message');
                    existingButtons.forEach(btn => btn.remove());

                    // Hide default buttons first
                    document.getElementById('approveBtn').style.display = 'none';
                    document.getElementById('rejectBtn').style.display = 'none';
                    document.getElementById('finalizeBtn').style.display = 'none';
                    document.getElementById('moreActionsDropdown').style.display = 'none';

                    // HEAD ADMIN - Show all actions based on status rules
                    if (isHeadAdmin) {
                        // Check if this is a terminal status (no actions)
                        const terminalStatuses = [7, 8, 9]; // Completed, Rejected, Cancelled
                        if (terminalStatuses.includes(currentStatusId)) {
                            const message = document.createElement('div');
                            message.className = 'action-taken-message text-center p-3 dynamic-action-btn';
                            message.innerHTML = `
                                                                                                <i class="bi bi-info-circle-fill text-secondary fs-4 d-block mb-2"></i>
                                                                                                <p class="mb-0 small text-muted">No actions available for this status.</p>
                                                                                            `;
                            actionsSection.appendChild(message);
                            return;
                        }

                        // Status 1 or 2 (Pencil Booked, Pending Approval) - Show Finalize button
                        if ([1, 2].includes(currentStatusId)) {
                            document.getElementById('finalizeBtn').style.display = 'block';
                        }

                        // Status 3 (Awaiting Payment) - Show Mark Scheduled button
                        else if (currentStatusId === 3) {
                            const markScheduledBtn = document.createElement('button');
                            markScheduledBtn.id = 'markScheduledBtn';
                            markScheduledBtn.className = 'btn btn-primary w-100 mb-2 dynamic-action-btn';
                            markScheduledBtn.innerHTML = '<i class="bi bi-calendar-event me-1"></i> Mark Scheduled';
                            markScheduledBtn.addEventListener('click', function () {
                                handleMarkScheduled();
                            });
                            actionsSection.appendChild(markScheduledBtn);
                        }

                        // Status 4 (Scheduled) - Show Mark Ongoing button
                        else if (currentStatusId === 4) {
                            const markOngoingBtn = document.createElement('button');
                            markOngoingBtn.id = 'markOngoingBtn';
                            markOngoingBtn.className = 'btn btn-primary w-100 mb-2 dynamic-action-btn';
                            markOngoingBtn.innerHTML = '<i class="bi bi-play-circle me-1"></i> Mark Ongoing';
                            markOngoingBtn.addEventListener('click', function () {
                                handleStatusAction('Ongoing');
                            });
                            actionsSection.appendChild(markOngoingBtn);
                        }

                        // Status 5 (Ongoing) - Show Mark Overdue button
                        else if (currentStatusId === 5) {
                            const markOverdueBtn = document.createElement('button');
                            markOverdueBtn.id = 'markOverdueBtn';
                            markOverdueBtn.className = 'btn btn-warning w-100 mb-2 dynamic-action-btn';
                            markOverdueBtn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Mark Overdue';
                            markOverdueBtn.addEventListener('click', function () {
                                handleStatusAction('Late');
                            });
                            actionsSection.appendChild(markOverdueBtn);
                        }

                        // Status 6 (Overdue) - Show Add Penalty button
                        else if (currentStatusId === 6) {
                            const penaltyBtn = document.createElement('button');
                            penaltyBtn.id = 'penaltyBtn';
                            penaltyBtn.className = 'btn btn-danger w-100 mb-2 dynamic-action-btn';
                            penaltyBtn.innerHTML = '<i class="bi bi-cash-stack me-1"></i> Add Penalty Fee';
                            penaltyBtn.addEventListener('click', function () {
                                const amount = prompt('Enter late penalty amount:');
                                if (amount !== null && amount !== '') {
                                    const num = parseFloat(amount);
                                    if (!isNaN(num) && num >= 0) {
                                        addLatePenalty(num);
                                    } else {
                                        showToast('Please enter a valid penalty amount.', 'error');
                                    }
                                }
                            });
                            actionsSection.appendChild(penaltyBtn);
                        }

                        // Always show Close Form button (except terminal statuses)
                        if (!terminalStatuses.includes(currentStatusId)) {
                            const closeFormBtn = document.createElement('button');
                            closeFormBtn.id = 'closeFormBtn';
                            closeFormBtn.className = 'btn btn-light-danger w-100 mb-2 dynamic-action-btn';
                            closeFormBtn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Close Form';
                            closeFormBtn.addEventListener('click', function () {
                                closeForm();
                            });
                            actionsSection.appendChild(closeFormBtn);
                        }
                    }

                    // APPROVING OFFICER or CHIEF APPROVING OFFICER
                    else if (isApprovingOfficer) {
                        // Check if admin has already taken action
                        const hasTakenAction = await checkIfAdminHasTakenAction(adminData.admin_id);

                        if (hasTakenAction) {
                            // Show "already taken action" message in the actions section
                            const message = document.createElement('div');
                            message.className = 'action-taken-message text-center p-3 dynamic-action-btn';
                            message.innerHTML = `
                                                                                                <i class="bi bi-check-circle-fill text-success fs-4 d-block mb-2"></i>
                                                                                                <p class="mb-0 small text-muted">You have already taken action for this request.</p>
                                                                                            `;
                            actionsSection.appendChild(message);
                        } else {
                            // Show Approve/Reject buttons only for pending statuses (1 or 2)
                            if ([1, 2].includes(currentStatusId)) {
                                document.getElementById('approveBtn').style.display = 'block';
                                document.getElementById('rejectBtn').style.display = 'block';
                            } else {
                                // Show message that no actions are available
                                const message = document.createElement('div');
                                message.className = 'action-taken-message text-center p-3 dynamic-action-btn';
                                message.innerHTML = `
                                                                                                    <i class="bi bi-info-circle-fill text-secondary fs-4 d-block mb-2"></i>
                                                                                                    <p class="mb-0 small text-muted">No actions available for this request status.</p>
                                                                                                `;
                                actionsSection.appendChild(message);
                            }
                        }
                    }

                    // OTHER ROLES - No access (shouldn't reach here but just in case)
                    else {
                        const message = document.createElement('div');
                        message.className = 'action-taken-message text-center p-3 dynamic-action-btn';
                        message.innerHTML = `
                                                                                            <i class="bi bi-shield-exclamation fs-4 d-block mb-2 text-muted"></i>
                                                                                            <p class="mb-0 small text-muted">You don't have permission to take actions.</p>
                                                                                        `;
                        actionsSection.appendChild(message);
                    }

                } catch (error) {
                    console.error('Error checking admin role:', error);
                }
            }
            async function loadApprovalHistory() {
                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/approval-history`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch approval history');

                    const approvalHistory = await response.json();

                    // Update approvals modal - check if elements exist
                    const approvalsModalBody = document.querySelector('#approvalsModal .modal-body');
                    if (approvalsModalBody) {
                        approvalsModalBody.innerHTML = generateApprovalHistoryHTML(approvalHistory.filter(item => item.action === 'approved'));
                    }

                    // Update rejections modal - check if elements exist
                    const rejectionsModalBody = document.querySelector('#rejectionsModal .modal-body');
                    if (rejectionsModalBody) {
                        rejectionsModalBody.innerHTML = generateApprovalHistoryHTML(approvalHistory.filter(item => item.action === 'rejected'));
                    }

                } catch (error) {
                    console.error('Error loading approval history:', error);

                    // Only try to update modal bodies if they exist
                    const approvalsModalBody = document.querySelector('#approvalsModal .modal-body');
                    const rejectionsModalBody = document.querySelector('#rejectionsModal .modal-body');

                    if (approvalsModalBody) {
                        approvalsModalBody.innerHTML = '<div class="text-center text-muted py-4">Failed to load history</div>';
                    }

                    if (rejectionsModalBody) {
                        rejectionsModalBody.innerHTML = '<div class="text-center text-muted py-4">Failed to load history</div>';
                    }
                }
            }


            function generateApprovalHistoryHTML(history) {
                if (!history || history.length === 0) {
                    return '<div class="text-center text-muted py-4">No records found</div>';
                }

                return history.map(item => `
                                                                                                                                                                                <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                                                                                                                                                                    <div class="me-3 flex-shrink-0">
                                                                                                                                                                                        ${item.admin_photo ?
                        `<img src="${item.admin_photo}" class="rounded-circle" width="45" height="45" alt="${item.admin_name}" style="object-fit: cover;">` :
                        `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 45px; height: 45px;">
                                                                                                                                                                                                ${item.admin_name.split(' ').map(n => n.charAt(0)).join('')}
                                                                                                                                                                                            </div>`
                    }
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div class="flex-grow-1">
                                                                                                                                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                                                                                                                                            <div>
                                                                                                                                                                                                <strong class="d-block">${item.admin_name}</strong>
                                                                                                                                                                                                <small class="text-muted">
                                                                                                                                                                                                    <i class="fa ${item.action_icon} ${item.action_class} me-1"></i>
                                                                                                                                                                                                    ${item.action} this request
                                                                                                                                                                                                </small>
                                                                                                                                                                                                ${item.remarks ? `<div class="mt-1 small text-muted">"${item.remarks}"</div>` : ''}
                                                                                                                                                                                            </div>
                                                                                                                                                                                            <small class="text-muted text-end">${item.formatted_date}</small>
                                                                                                                                                                                        </div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                </div>
                                                                                                                                                                            `).join('');
            }



            // Function to check if the current admin has already taken action on this request
            async function checkIfAdminHasTakenAction(adminId) {
                try {
                    const adminToken = localStorage.getItem('adminToken');
                    const requestId = window.location.pathname.split('/').pop();

                    // Use the approval history endpoint we created
                    const response = await fetch(`/api/admin/requisition/${requestId}/approval-history`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        console.error('Failed to fetch approval history');
                        return false;
                    }

                    const approvalHistory = await response.json();

                    console.log('Approval history for request:', {
                        requestId: requestId,
                        adminId: adminId,
                        approvalHistory: approvalHistory
                    });

                    // Check if current admin appears in the approval history by admin_id
                    const hasTakenAction = approvalHistory.some(record => {
                        return record.admin_id === adminId;
                    });

                    console.log('Admin action check result:', {
                        adminId: adminId,
                        hasTakenAction: hasTakenAction,
                        approvalHistoryLength: approvalHistory.length,
                        matchingRecords: approvalHistory.filter(record => record.admin_id === adminId)
                    });

                    return hasTakenAction;

                } catch (error) {
                    console.error('Error checking admin action:', error);
                    return false;
                }
            }

            // Add event listener for Finalize button (AFTER the function definition)
            const finalizeBtn = document.getElementById('finalizeBtn');
            if (finalizeBtn) {
                finalizeBtn.addEventListener('click', function () {
                    finalizeModal.show();
                });
            }


            // Activity filter functionality
            const activityFilter = document.getElementById('activityFilter');
            if (activityFilter) {
                activityFilter.addEventListener('change', function () {
                    const filterValue = this.value;
                    displayMixedActivity(currentComments, currentFees, filterValue);
                });
            }



            // Check admin role and update UI (AFTER event listeners are set up)
            checkAdminRoleAndUpdateUI();


            // Handle status option clicks
            statusOptions.forEach(option => {
                option.addEventListener('click', function () {
                    const action = this.dataset.value;
                    handleStatusAction(action);
                });
            });

            document.getElementById('closeForm').addEventListener('click', function () {
                closeForm();
            });

            // Add event listeners for status options in the More Actions dropdown

            document.querySelectorAll('#moreActionsBtn + .dropdown-menu .status-option').forEach(option => {
                option.addEventListener('click', function (e) {
                    e.preventDefault();
                    const action = this.dataset.value;

                    // SPECIAL CASE: Handle Scheduled with custom modal
                    if (action === 'Scheduled') {
                        handleMarkScheduled();
                        return;
                    }

                    // Check if we're dealing with Late status and current status is already Late
                    const statusBadge = document.getElementById('statusBadge');
                    const currentStatusName = statusBadge ? statusBadge.textContent.trim() : '';

                    if (action === 'Late' && currentStatusName === 'Late') {
                        handleUnmarkLate();
                    } else {
                        handleStatusAction(action);
                    }
                });
            });


            // Function to handle different status actions
            function handleStatusAction(action) {
                const statusBadge = document.getElementById('statusBadge');
                const currentStatusName = statusBadge ? statusBadge.textContent.trim() : '';

                // Get current status ID from the request data
                const currentRequest = allRequests.find(req => req.request_id == requestId);
                const currentStatusId = currentRequest ? currentRequest.form_details.status.id : null;

                // Check if user is Head Admin (you'll need to implement this check)
                const isHeadAdmin = checkIfHeadAdmin();

                function checkIfHeadAdmin() {
                    try {
                        // Get admin data from localStorage or make an API call
                        const adminData = JSON.parse(localStorage.getItem('adminProfile') || '{}');
                        return adminData.role?.role_title === 'Head Admin';
                    } catch (error) {
                        console.error('Error checking Head Admin status:', error);
                        return false;
                    }
                }

                if (isHeadAdmin) {
                    // Head Admin specific restrictions based on status_id
                    switch (currentStatusId) {
                        case 'Awaiting Payment': // Assuming status_id for Awaiting Payment
                            if (action === 'Ongoing' || action === 'Late') {
                                showToast('This action is not available for forms with Awaiting Payment status.', 'error');
                                return;
                            }
                            break;

                        case 'Scheduled': // Assuming status_id for Scheduled
                            if (action === 'Scheduled' || action === 'Late') {
                                showToast('This action is not available for forms with Scheduled status.', 'error');
                                return;
                            }
                            // Disable finalize button for Scheduled status
                            const finalizeBtn = document.getElementById('finalizeBtn');
                            if (finalizeBtn && action === 'Finalize') {
                                showToast('Cannot finalize a form that is already Scheduled.', 'error');
                                return;
                            }
                            break;

                        case 'Ongoing': // Assuming status_id for Ongoing
                            // For Ongoing status, replace Finalize with Mark Late
                            if (action === 'Finalize') {
                                // Instead of finalizing, mark as late
                                handleStatusAction('Late');
                                return;
                            }
                            break;
                    }
                }

                // SPECIAL CASE: Handle Scheduled status with custom modal
                if (action === 'Scheduled') {
                    handleMarkScheduled();
                    return; // Exit early to prevent the generic status modal from showing
                }

                // For all other statuses, use the generic status update modal
                switch (action) {
                    case 'Ongoing':
                    case 'Late':
                        showStatusUpdateModal(action);
                        break;

                    case 'Finalize Form':
                        if (currentStatusName === 'Pending Approval') {
                            finalizeModal.show();
                        } else {
                            showToast('This form is already finalized.', 'error');
                        }
                        break;

                    case 'Set Penalty Fee':
                        // Prompt user for penalty amount
                        const penaltyAmount = prompt('Enter late penalty amount:');
                        if (penaltyAmount !== null && penaltyAmount !== '') {
                            const amount = parseFloat(penaltyAmount);
                            if (!isNaN(amount) && amount >= 0) { // allow 0
                                addLatePenalty(amount);
                            } else {
                                showToast('Please enter a valid penalty amount (0 or greater).', 'error');
                            }
                        }
                        break;

                    case 'Close':
                        closeForm();
                        break;

                    default:
                        console.log('Unknown action:', action);
                }
            }

            // Confirm mark scheduled
            document.getElementById('confirmMarkScheduled').addEventListener('click', async function () {
                const btn = this;
                const spinner = btn.querySelector('.spinner-border');
                const originalText = btn.innerHTML;

                const officialReceiptNum = document.getElementById('officialReceiptNum').value.trim();
                const calendarTitle = document.getElementById('scheduledCalendarTitle').value.trim();
                const calendarDescription = document.getElementById('scheduledCalendarDescription').value.trim();

                if (!officialReceiptNum) {
                    showToast('Please enter an official receipt number', 'error');
                    return;
                }

                // Show loading state
                btn.disabled = true;
                spinner.classList.remove('d-none');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/mark-scheduled`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            official_receipt_num: officialReceiptNum,
                            calendar_title: calendarTitle || null,
                            calendar_description: calendarDescription || null
                        })
                    });

                    const contentType = response.headers.get('content-type');
                    let responseData;

                    if (contentType && contentType.includes('application/json')) {
                        responseData = await response.json();
                    } else {
                        const textResponse = await response.text();
                        throw new Error(textResponse || 'Non-JSON response from server');
                    }

                    if (!response.ok) {
                        const errorMessage = responseData.error ||
                            responseData.message ||
                            JSON.stringify(responseData) ||
                            'Failed to mark as scheduled';
                        throw new Error(errorMessage);
                    }

                    showToast('Request marked as scheduled successfully! Official receipt generated.', 'success');
                    markScheduledModal.hide();

                    // Clear form
                    document.getElementById('officialReceiptNum').value = '';
                    document.getElementById('scheduledCalendarTitle').value = '';
                    document.getElementById('scheduledCalendarDescription').value = '';

                    // Refresh the page to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);

                } catch (error) {
                    console.error('Error marking as scheduled:', error);
                    showToast('Error: ' + error.message, 'error');

                    // Reset button state on error
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    spinner.classList.add('d-none');
                }
            });

            async function loadMixedActivity() {
                try {
                    const container = document.getElementById('formRemarks');
                    if (!container) {
                        console.warn('Comments container not found');
                        return;
                    }

                    container.innerHTML = `
                                                                                                        <div class="comment-loading">
                                                                                                            <div class="spinner-border text-primary" role="status">
                                                                                                                <span class="visually-hidden">Loading activity...</span>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    `;

                    const commentsResponse = await fetch(`/api/admin/requisition/${requestId}/comments`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    const feesResponse = await fetch(`/api/admin/requisition/${requestId}/fees`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    const commentsResult = await commentsResponse.json();
                    const feesResult = await feesResponse.json();

                    currentComments = commentsResult.comments || [];
                    currentFees = feesResult || [];

                    // Initially show all
                    displayMixedActivity(currentComments, currentFees, 'all');
                    scrollToBottom();

                } catch (error) {
                    console.error('Error loading activity:', error);
                    const container = document.getElementById('formRemarks');
                    if (container) {
                        container.innerHTML = `
                                                                                                            <div class="empty-comments text-danger">
                                                                                                                <i class="bi bi-exclamation-triangle"></i>
                                                                                                                <p>Failed to load activity.</p>
                                                                                                            </div>
                                                                                                        `;
                    }
                }
            }

            function scrollToBottom() {
                const container = document.querySelector('.comments-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }

            // Function to display mixed comments and actions
            function displayMixedActivity(comments, fees, filter = 'all') {
                const container = document.getElementById('formRemarks');

                if (!container) {
                    console.warn('Comments container not found');
                    return;
                }

                if (comments.length === 0 && fees.length === 0) {
                    container.innerHTML = `
                                                                                                        <div class="empty-comments">
                                                                                                            <i class="bi bi-chat"></i>
                                                                                                            <p>No action has been taken in this form yet.</p>
                                                                                                        </div>
                                                                                                    `;
                    return;
                }

                // Combine comments and fees
                let allActivities = [];

                if (filter === 'all' || filter === 'comment') {
                    comments.forEach(comment => {
                        allActivities.push({
                            type: 'comment',
                            data: comment,
                            timestamp: new Date(comment.created_at)
                        });
                    });
                }

                if (filter === 'all' || filter === 'fee') {
                    fees.forEach(fee => {
                        allActivities.push({
                            type: 'fee',
                            data: fee,
                            timestamp: new Date(fee.created_at)
                        });
                    });
                }

                // Sort by timestamp
                allActivities.sort((a, b) => a.timestamp - b.timestamp);

                // Render HTML
                container.innerHTML = allActivities.map(activity => {
                    return activity.type === 'comment' ?
                        generateCommentHTML(activity.data) :
                        generateFeeHTML(activity.data);
                }).join('');
            }

            // Function to generate comment HTML
            function generateCommentHTML(comment) {
                return `
                                                                                                                                                                                                <div class="comment mb-3">
                                                                                                                                                                                                    <div class="d-flex align-items-start">
                                                                                                                                                                                                        <!-- Admin Profile Picture -->
                                                                                                                                                                                                        <div class="me-2 flex-shrink-0">
                                                                                                                                                                                                            ${comment.admin.photo_url ?
                        `<img src="${comment.admin.photo_url}" class="rounded-circle" width="40" height="40" alt="${comment.admin.first_name}'s profile picture" style="object-fit: cover;">` :
                        `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 40px; height: 40px; font-size: 1rem;">
                                                                                                                                                                                                                    ${comment.admin.first_name.charAt(0)}${comment.admin.last_name.charAt(0)}
                                                                                                                                                                                                                </div>`
                    }
                                                                                                                                                                                                        </div>

                                                                                                                                                                                                        <!-- Message Bubble -->
                                                                                                                                                                                                        <div class="flex-grow-1">
                                                                                                                                                                                                            <div class="d-flex align-items-center mb-1">
                                                                                                                                                                                                                <strong class="me-2" style="font-size: 0.85rem;">${comment.admin.first_name} ${comment.admin.last_name}</strong>
                                                                                                                                                                                                                <small class="text-muted">${formatTimeAgo(comment.created_at)}</small>
                                                                                                                                                                                                            </div>
                                                                                                                                                                                                            <div class="message-bubble bg-primary text-white p-3 rounded-3" style="max-width: 80%; border-bottom-left-radius: 4px !important;">
                                                                                                                                                                                                                <p class="mb-0" style="white-space: pre-wrap; line-height: 1.4;">${escapeHtml(comment.comment)}</p>
                                                                                                                                                                                                            </div>
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                    </div>
                                                                                                                                                                                                </div>
                                                                                                                                                                                                `;
            }


            // Function to generate fee/action HTML
            function generateFeeHTML(fee) {
                const amount = parseFloat(fee.type === 'discount' ? fee.discount_amount : fee.fee_amount);
                const typeName = fee.type === 'discount' ? 'Discount' : 'Additional fee';
                const adminName = fee.added_by?.name || 'Admin';

                let message = '';
                let amountDisplay = '';

                if (fee.type === 'discount') {
                    if (fee.discount_type === 'Percentage') {
                        // Remove .00 for percentage discounts
                        amountDisplay = `${parseFloat(amount)}%`;
                        message = `${adminName} added a discount - ${fee.label}: ${amountDisplay}`;
                    } else {
                        // Fixed discount: use formatMoney
                        amountDisplay = `-${formatMoney(amount)}`;
                        message = `${adminName} added a discount - ${fee.label}: ${amountDisplay}`;
                    }
                } else if (fee.type === 'fee') {
                    // Regular fee: use formatMoney
                    amountDisplay = `${formatMoney(amount)}`;
                    message = `${adminName} added a fee - ${fee.label}: ${amountDisplay}`;
                } else if (fee.type === 'mixed') {
                    // Mixed fee and discount
                    const feePart = fee.fee_amount > 0 ? `${formatMoney(fee.fee_amount)}` : '';
                    const discountPart = fee.discount_amount > 0 ?
                        (fee.discount_type === 'Percentage' ?
                            `-${parseFloat(fee.discount_amount)}%` :
                            `-${formatMoney(fee.discount_amount)}`) : '';
                    amountDisplay = `${feePart} ${discountPart}`.trim();
                    message = `${adminName} added a mixed fee - ${fee.label}: ${amountDisplay}`;
                }

                return `
                                                                                                                                        <div class="comment mb-3">
                                                                                                                                            <div class="d-flex align-items-start">
                                                                                                                                                <!-- Icon Circle -->
                                                                                                                                                <div class="me-2 flex-shrink-0">
                                                                                                                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                                                                                                                         style="width: 40px; height: 40px; font-size: 1rem; background-color: #d4edda; color: #28a745;">
                                                                                                                                                        <i class="fa fa-money-bill"></i>
                                                                                                                                                    </div>
                                                                                                                                                </div>

                                                                                                                                                <!-- Action Bubble -->
                                                                                                                                                <div class="flex-grow-1">
                                                                                                                                                    <div class="message-bubble bg-info text-white p-3 rounded-3" 
                                                                                                                                                         style="max-width: 80%; border-bottom-left-radius: 4px !important;">
                                                                                                                                                        <p class="mb-1" style="white-space: normal; line-height: 1.4;">
                                                                                                                                                            ${message}
                                                                                                                                                        </p>
                                                                                                                                                        <small class="text-dark">
                                                                                                                                                            ${formatTimeAgo(fee.created_at)}
                                                                                                                                                        </small>
                                                                                                                                                    </div>
                                                                                                                                                </div>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    `;
            }

            // Helper function to format time ago (e.g., "2 minutes ago")
            function formatTimeAgo(timestamp) {
                const now = new Date();
                const commentTime = new Date(timestamp);
                const diffInSeconds = Math.floor((now - commentTime) / 1000);

                if (diffInSeconds < 60) {
                    return 'just now';
                } else if (diffInSeconds < 3600) {
                    const minutes = Math.floor(diffInSeconds / 60);
                    return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
                } else if (diffInSeconds < 86400) {
                    const hours = Math.floor(diffInSeconds / 3600);
                    return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
                } else if (diffInSeconds < 604800) {
                    const days = Math.floor(diffInSeconds / 86400);
                    return `${days} day${days !== 1 ? 's' : ''} ago`;
                } else {
                    return commentTime.toLocaleDateString();
                }
            }

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Simple toast notification function
            window.showToast = function (message, type = 'success', duration = 3000) {
                const toast = document.createElement('div');

                // Toast base styles
                toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
                toast.style.zIndex = '1100';
                toast.style.bottom = '0';
                toast.style.left = '0';
                toast.style.margin = '1rem';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                toast.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');

                // Colors
                const bgColor = type === 'success' ? '#004183ff' : '#dc3545';
                toast.style.backgroundColor = bgColor;
                toast.style.color = '#fff';
                toast.style.minWidth = '250px';
                toast.style.borderRadius = '0.3rem';

                toast.innerHTML = `
                                                                                                                                                                                                                                                                                                                <div class="d-flex align-items-center px-3 py-1"> 
                                                                                                                                                                                                                                                                                                                    <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
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

                // Bootstrap toast instance
                const bsToast = new bootstrap.Toast(toast, {
                    autohide: false
                });
                bsToast.show();

                // Float up appear animation
                requestAnimationFrame(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                // Start loading bar animation
                const loadingBar = toast.querySelector('.loading-bar');
                requestAnimationFrame(() => {
                    loadingBar.style.width = '0%';
                });

                // Remove after duration
                setTimeout(() => {
                    // Float down disappear animation
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(20px)';

                    setTimeout(() => {
                        bsToast.hide();
                        toast.remove();
                    }, 400);
                }, duration);
            };


            // Load comments when page loads
            loadMixedActivity();

            // Function to refresh all fee displays including the total
            async function refreshAllFeeDisplays() {
                try {
                    // Fetch updated request data
                    const response = await fetch(`/api/admin/requisition-forms/${requestId}`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const requestData = await response.json();

                        // FIX THIS LINE - use the correct element ID
                        const totalFee = formatMoney(requestData.fees.approved_fee);
                        document.getElementById('feeBreakdownTotal').textContent = totalFee; // Changed from 'totalApprovedFee'

                        // Update additional fees display
                        if (requestData.fees.requisition_fees) {
                            updateAdditionalFees(requestData.fees.requisition_fees);
                        }

                        // Update base fees display
                        if (requestData.requested_items) {
                            updateBaseFees(requestData.requested_items, requestData.schedule);
                        }

                        // Refresh activity timeline
                        await loadMixedActivity();
                    }
                } catch (error) {
                    console.error('Error refreshing fee displays:', error);
                }
            }


            // Function to show status update modal
            function showStatusUpdateModal(status) {
                const modalContent = document.getElementById('statusModalContent');
                const statusBadge = document.getElementById('statusBadge');
                const currentStatusName = statusBadge ? statusBadge.textContent.trim() : '';

                if (currentStatusName === 'Pending Approval' && status !== 'Cancel Form') {
                    showToast('Finalize the form first', 'error');
                    return;
                }

                // Set modal content based on selected status
                switch (status) {
                    case 'Scheduled':
                        modalContent.innerHTML = `
                                                                                                                                                                                                <div class="text-center">
                                                                                                                                                                                                    <i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i>
                                                                                                                                                                                                    <p>Are you sure? This action cannot be undone.</p>
                                                                                                                                                                                                    <p class="text-muted small">
                                                                                                                                                                                                        This will set the form's status to <strong>Scheduled</strong>.
                                                                                                                                                                                                        The request can still be cancelled if an emergency happens. 
                                                                                                                                                                                                        If such a situation occurs, make sure to contact the requester about refund details and settle it in the business office on campus before closing the form.
                                                                                                                                                                                                    </p>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            `;
                        break;
                    case 'Ongoing':
                        modalContent.innerHTML = `
                                                                                                                                                                                                <div class="text-center">
                                                                                                                                                                                                    <i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i>
                                                                                                                                                                                                    <p>Are you sure? This action cannot be undone.</p>
                                                                                                                                                                                                    <p class="text-muted small">
                                                                                                                                                                                                        Sets the form status to <strong>Ongoing</strong>. Use this to manually set it if not already done. 
                                                                                                                                                                                                    </p>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            `;
                        break;
                    case 'Late':
                        modalContent.innerHTML = `
                                                                                                                                                                                                <div class="text-center">
                                                                                                                                                                                                    <i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i>
                                                                                                                                                                                                    <p>Are you sure? This action cannot be undone.</p>
                                                                                                                                                                                                    <p class="text-muted small">
                                                                                                                                                                                                        This will set the form's status to <strong>Late</strong> and mark it as overdue.
                                                                                                                                                                                                    </p>
                                                                                                                                                                                                </div>
                                                                                                                                                                                                <div class="mt-4">
                                                                                                                                                                                                    <div class="mb-3">
                                                                                                                                                                                                        <label for="latePenaltyAmount" class="form-label">Late Penalty Amount (Optional)</label>
                                                                                                                                                                                                        <div class="input-group">
                                                                                                                                                                                                            <span class="input-group-text">₱</span>
                                                                                                                                                                                                            <input type="number" class="form-control" id="latePenaltyAmount" 
                                                                                                                                                                                                                   placeholder="Enter penalty amount" step="0.01" min="0" value="0">
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                        <small class="text-muted">Enter the late penalty amount to be added to the fees (leave as 0 for no penalty).</small>
                                                                                                                                                                                                    </div>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            `;
                        break;
                    case 'Cancel Form':
                        modalContent.innerHTML = `
                                                                                                                                                                                                <div class="text-center">
                                                                                                                                                                                                    <i class="fa fa-exclamation-circle fa-3x text-danger mb-3"></i>
                                                                                                                                                                                                    <p>Are you sure? This action cannot be undone.</p>
                                                                                                                                                                                                    <p class="text-muted small">
                                                                                                                                                                                                        This will <strong class="text-danger">cancel</strong> the form and set its status to <strong>Cancelled</strong>.
                                                                                                                                                                                                        Note: This action cannot be undone. The requester will be notified about the cancellation.
                                                                                                                                                                                                    </p>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            `;
                        break;
                }

                selectedStatus = status;
                statusUpdateModal.show();
            }

            // Confirm status update
            document.getElementById('confirmStatusUpdate').addEventListener('click', async function () {
                if (!selectedStatus) return;

                const btn = this;
                const originalText = btn.innerHTML;
                const adminToken = localStorage.getItem('adminToken');

                try {
                    // Show loading state
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

                    // Prepare request data
                    const requestData = {
                        status_name: selectedStatus
                    };

                    // Add late penalty fee for Late status
                    if (selectedStatus === 'Late') {
                        const penaltyAmount = document.getElementById('latePenaltyAmount').value;
                        if (penaltyAmount && parseFloat(penaltyAmount) > 0) {
                            requestData.late_penalty_fee = parseFloat(penaltyAmount);
                        }
                    }

                    const statusResponse = await fetch(`/api/admin/requisition/${requestId}/update-status`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    });

                    const contentType = statusResponse.headers.get('content-type');
                    let statusResponseData;

                    if (contentType && contentType.includes('application/json')) {
                        statusResponseData = await statusResponse.json();
                    } else {
                        const textResponse = await statusResponse.text();
                        throw new Error(textResponse || 'Non-JSON response from server');
                    }

                    if (!statusResponse.ok) {
                        const errorMessage = statusResponseData.error ||
                            statusResponseData.message ||
                            JSON.stringify(statusResponseData) ||
                            'Failed to update status';
                        throw new Error(errorMessage);
                    }

                    showToast('Status updated successfully!', 'success');
                    statusUpdateModal.hide();
                    selectedStatus = '';

                    // Refresh the entire page to show updated status and UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);

                    // Refresh the page to show updated status and fees
                    fetchRequestDetails();

                    // Update the dropdown text after status change
                    updateStatusDropdownText();

                } catch (error) {
                    console.error('Error updating status:', error);
                    console.error('Status update details:', {
                        requestId: requestId,
                        status: selectedStatus,
                        error: error.message
                    });
                    showToast('Error: ' + error.message, 'error');
                } finally {
                    // Reset button state
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });


            // Update the closeForm function to use the modal
            async function closeForm() {
                closeFormModal.show();
            }

            function handleUnmarkLate() {
                const modalContent = document.getElementById('statusModalContent');

                modalContent.innerHTML = `
                                                                                                                                                                                        <div class="text-center">
                                                                                                                                                                                            <i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i>
                                                                                                                                                                                            <p>Are you sure you want to unmark this form as late/damaged?</p>
                                                                                                                                                                                            <p class="text-muted small">
                                                                                                                                                                                                This will set the form's status back to <strong>Ongoing</strong>, remove the late flag, 
                                                                                                                                                                                                and reset any penalty fees to zero.
                                                                                                                                                                                            </p>
                                                                                                                                                                                        </div>
                                                                                                                                                                                    `;

                selectedStatus = 'Ongoing'; // Set to Ongoing to unmark late
                statusUpdateModal.show();
            }

            // Add event listener for the confirm button
            document.getElementById('confirmCloseForm').addEventListener('click', async function () {
                const btn = this;
                const spinner = btn.querySelector('.spinner-border');
                const originalText = btn.innerHTML;

                // Show loading state
                btn.disabled = true;
                spinner.classList.remove('d-none');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Closing...';

                const adminToken = localStorage.getItem('adminToken');

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/close`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.details || 'Failed to close form');
                    }

                    closeFormModal.hide();
                    showToast('Form closed successfully!', 'success');

                    setTimeout(() => {
                        window.location.href = '/admin/calendar';
                    }, 1500);

                } catch (error) {
                    console.error('Error closing form:', error);
                    showToast('Error: ' + error.message, 'error');

                    // Reset button state on error
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    spinner.classList.add('d-none');
                }
            });

            // Also update the event listener for the close form option in the dropdown
            document.getElementById('closeForm').addEventListener('click', function (e) {
                e.preventDefault();
                closeForm();
            });

            // Function to add late penalty
            async function addLatePenalty(penaltyAmount) {
                const adminToken = localStorage.getItem('adminToken');

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/late-penalty`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            penalty_amount: penaltyAmount
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.details || 'Failed to add late penalty');
                    }

                    showToast('Late penalty added successfully!', 'success');
                    // Refresh the page to show updated fees
                    fetchRequestDetails();

                } catch (error) {
                    console.error('Error adding late penalty:', error);
                    showToast('Error: ' + error.message, 'error');
                }
            }

            document.getElementById('confirmFinalize').addEventListener('click', async function (e) {
                e.preventDefault();

                const btn = document.getElementById('confirmFinalize');
                let calendarTitle = document.getElementById('calendarTitle').value.trim();
                let calendarDescription = document.getElementById('calendarDescription').value.trim();
                const adminToken = localStorage.getItem('adminToken');

                // Convert empty strings to null for the API
                calendarTitle = calendarTitle || null;
                calendarDescription = calendarDescription || null;

                // Title validation (only if it's provided)
                if (calendarTitle && calendarTitle.length > 50) {
                    showToast('Calendar Title must not exceed 50 characters.', 'error');
                    document.getElementById('calendarTitle').focus();
                    return;
                }

                // Description validation (only if it's provided)
                if (calendarDescription && calendarDescription.length > 100) {
                    showToast('Calendar Description must not exceed 100 characters.', 'error');
                    document.getElementById('calendarDescription').focus();
                    return;
                }

                // Disable button and show spinner
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Finalizing...';
                btn.disabled = true;

                try {
                    console.log('Sending finalize request with data:', {
                        requestId: requestId,
                        calendar_title: calendarTitle,
                        calendar_description: calendarDescription
                    });

                    const response = await fetch(`/api/admin/requisition/${requestId}/finalize`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            calendar_title: calendarTitle,
                            calendar_description: calendarDescription
                        })
                    });

                    console.log('Response status:', response.status);

                    // Try to get the response text first
                    const responseText = await response.text();
                    console.log('Raw response:', responseText);

                    let responseData;
                    try {
                        // Try to parse as JSON
                        responseData = JSON.parse(responseText);
                    } catch (parseError) {
                        // If it's not JSON, show the raw response
                        console.error('Response is not JSON:', responseText);
                        throw new Error(`Server returned non-JSON response: ${responseText.substring(0, 200)}`);
                    }

                    if (!response.ok) {
                        // Extract error message from various possible formats
                        const errorMessage = responseData.error ||
                            responseData.message ||
                            (responseData.details ? JSON.stringify(responseData.details) :
                                'Failed to finalize request');
                        throw new Error(errorMessage);
                    }

                    console.log('Finalize response:', responseData);

                    // Successful finalization
                    finalizeModal.hide();
                    document.getElementById('calendarTitle').value = '';
                    document.getElementById('calendarDescription').value = '';

                    // Refresh the page to show updated status
                    showToast('Form finalized successfully!', 'success');

                    // Wait a moment then reload to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);

                } catch (error) {
                    console.error('Error finalizing request:', error);
                    console.error('Finalize request details:', {
                        requestId: requestId,
                        calendarTitle: calendarTitle,
                        calendarDescription: calendarDescription,
                        error: error.message
                    });
                    showToast('Error: ' + error.message, 'error');
                } finally {
                    // Re-enable button and restore original text
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });



            // Handle individual waiver checkbox changes
            async function handleWaiverChange(checkbox) {
                const type = checkbox.dataset.type;
                const id = parseInt(checkbox.dataset.id);
                const isWaived = checkbox.checked;

                // Update UI immediately for better UX
                const itemRow = checkbox.closest('.item-row');
                if (itemRow) {
                    if (isWaived) {
                        itemRow.classList.add('waived');
                    } else {
                        itemRow.classList.remove('waived');
                    }
                }

                // Collect ALL waived items (both checked ones)
                const waivedFacilities = [];
                const waivedEquipment = [];

                document.querySelectorAll('.waiver-checkbox').forEach(cb => {
                    const itemId = parseInt(cb.dataset.id);
                    const itemType = cb.dataset.type;

                    if (cb.checked) {
                        if (itemType === 'facility') {
                            waivedFacilities.push(itemId);
                        } else if (itemType === 'equipment') {
                            waivedEquipment.push(itemId);
                        }
                    }
                });

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            waived_facilities: waivedFacilities,
                            waived_equipment: waivedEquipment
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        let errorMessage = 'Failed to update waiver status';
                        if (errorData.details) {
                            if (typeof errorData.details === 'object') {
                                errorMessage = Object.values(errorData.details).flat().join(', ');
                            } else {
                                errorMessage = errorData.details;
                            }
                        } else if (errorData.error) {
                            errorMessage = errorData.error;
                        }
                        throw new Error(errorMessage);
                    }

                    const result = await response.json();

                    // Refresh ALL fee displays including the total
                    await refreshAllFeeDisplays();

                    // Show appropriate success message based on action
                    if (type === 'facility') {
                        const facilityName = itemRow ? itemRow.querySelector('.item-name').textContent : 'Facility';
                        if (isWaived) {
                            showToast(`${facilityName} waived successfully.`, 'success');
                        } else {
                            showToast(`${facilityName} waiver removed.`, 'success');
                        }
                    } else if (type === 'equipment') {
                        const equipmentName = itemRow ? itemRow.querySelector('.item-name').textContent : 'Equipment';
                        if (isWaived) {
                            showToast(`${equipmentName} waived successfully.`, 'success');
                        } else {
                            showToast(`${equipmentName} waiver removed.`, 'success');
                        }
                    }

                    console.log('Sending waiver request:', {
                        waived_facilities: waivedFacilities,
                        waived_equipment: waivedEquipment
                    });

                } catch (error) {
                    console.error('Error updating waiver:', error);
                    // Revert checkbox state on error
                    checkbox.checked = !isWaived;
                    if (itemRow) {
                        itemRow.classList.toggle('waived');
                    }
                    showToast('Failed to update waiver: ' + error.message, 'error');
                }
            }
            document.getElementById('waiveAllSwitch').addEventListener('change', function () {
                handleWaiveAll(this);
            });

            // Handle "Waive All" toggle switch
            async function handleWaiveAll(switchElement) {
                const waiveAll = switchElement.checked;

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            waive_all: waiveAll
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.details || 'Failed to update waiver status');
                    }

                    const result = await response.json();

                    // Update all checkboxes to match the "waive all" state
                    document.querySelectorAll('.waiver-checkbox').forEach(checkbox => {
                        checkbox.checked = waiveAll;
                        const itemRow = checkbox.closest('.item-row');
                        if (itemRow) {
                            if (waiveAll) {
                                itemRow.classList.add('waived');
                            } else {
                                itemRow.classList.remove('waived');
                            }
                        }
                    });

                    // Refresh ALL fee displays including the total
                    await refreshAllFeeDisplays();

                    // Show success message
                    if (waiveAll) {
                        showToast('All items waived successfully.', 'success');
                    } else {
                        showToast('All waivers removed.', 'success');
                    }

                } catch (error) {
                    console.error('Error updating waive all:', error);
                    // Revert switch state on error
                    switchElement.checked = !waiveAll;
                    showToast('Failed to update waive all: ' + error.message, 'error');
                }
            }

            // A map to get user-friendly names for fee types
            const feeTypeNames = {
                additional: 'Additional fee',
                discount: 'Discount',
                vat: 'Less VAT (12%)'
            };



            // Remove the togglePlaceholder function entirely and replace it with:
            function updateFeesDisplay() {
                const feesContainer = document.getElementById('additionalFeesContainer');
                // This function can be used to update the fees display if needed
                // But we don't need the placeholder logic anymore
            }

            // Show the modal when "Add Fee" is clicked
            addFeeBtn.addEventListener("click", function () {
                feeModal.show();
            });


            // Handle fee type change to show/hide discount type
            feeTypeSelect.addEventListener('change', function () {
                discountTypeSection.style.display = this.value === 'discount' ? 'block' : 'none';

                // Auto-fill for VAT type
                if (this.value === 'vat') {
                    document.getElementById('feeLabel').value = 'Less VAT';
                    document.getElementById('feeValue').value = '12';
                    document.getElementById('discountType').value = 'Percentage';
                } else {
                    // Clear auto-filled values when switching away from VAT
                    if (document.getElementById('feeLabel').value === 'Less VAT') {
                        document.getElementById('feeLabel').value = '';
                    }
                    if (document.getElementById('feeValue').value === '12') {
                        document.getElementById('feeValue').value = '';
                    }
                }
            });


            // Save Fee button logic
            saveFeeBtn.addEventListener("click", async function () {
                const type = feeTypeSelect.value;
                const value = parseFloat(feeValueInput.value);
                const label = document.getElementById('feeLabel').value;
                const discountType = document.getElementById('discountType').value;
                const accountNum = document.getElementById('accountNum').value.trim(); // Get account number

                if (!type || !value || !label) {
                    showToast("Please fill all required fields.", "error");
                    return;
                }

                try {
                    let endpoint = '';
                    let requestData = {};

                    // Determine which API endpoint to call based on fee type
                    switch (type) {
                        case 'additional':
                            endpoint = `/api/admin/requisition/${requestId}/fee`;
                            requestData = {
                                label: label,
                                fee_amount: value,
                                account_num: accountNum || null // Include account number
                            };
                            break;
                        case 'discount':
                        case 'vat': // Both discount and vat use the discount API
                            endpoint = `/api/admin/requisition/${requestId}/discount`;
                            requestData = {
                                label: label,
                                discount_amount: value,
                                discount_type: discountType,
                                account_num: accountNum || null // Include account number
                            };
                            break;
                    }

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.details || 'Failed to add fee/discount');
                    }

                    const result = await response.json();

                    // Reset and close modal
                    feeValueInput.value = "";
                    feeTypeSelect.value = "";
                    document.getElementById('feeLabel').value = "";
                    document.getElementById('accountNum').value = ""; // Clear account number
                    discountTypeSection.style.display = 'none';
                    feeModal.hide();

                    // Show success message
                    showToast('Fee/discount added successfully', 'success');

                    // Refresh ALL fee displays including the total
                    await refreshAllFeeDisplays();

                } catch (error) {
                    console.error('Error adding fee/discount:', error);
                    console.error('Fee addition details:', {
                        type: type,
                        value: value,
                        label: label,
                        accountNum: accountNum,
                        discountType: discountType,
                        error: error.message
                    });
                    showToast('Failed to add fee/discount: ' + error.message, 'error');
                }
            });

            // Document preview functionality - clean overlay for PDFs and images
            document.addEventListener('click', function (event) {
                // Check if the click is on a button that has data-bs-target="#documentModal" 
                // and data-document-url attributes
                let button = null;

                if (event.target.matches('[data-bs-target="#documentModal"]')) {
                    button = event.target;
                } else if (event.target.closest('[data-bs-target="#documentModal"]')) {
                    button = event.target.closest('[data-bs-target="#documentModal"]');
                }

                if (button && button.hasAttribute('data-document-url')) {
                    event.preventDefault();
                    event.stopPropagation();

                    const documentUrl = button.getAttribute('data-document-url');
                    const documentTitle = button.getAttribute('data-document-title');
                    const fileExtension = documentUrl.split('.').pop().toLowerCase();
                    const isPDF = fileExtension === 'pdf';
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExtension);

                    // Disable main page scrolling
                    const originalBodyStyles = {
                        overflow: document.body.style.overflow,
                        position: document.body.style.position,
                        width: document.body.style.width,
                        height: document.body.style.height
                    };

                    document.body.style.overflow = 'hidden';
                    document.body.style.position = 'fixed';
                    document.body.style.width = '100%';
                    document.body.style.height = '100%';

                    // Create overlay container
                    const overlay = document.createElement('div');
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100%';
                    overlay.style.height = '100%';
                    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
                    overlay.style.zIndex = '9999';
                    overlay.style.display = 'flex';
                    overlay.style.justifyContent = 'center';
                    overlay.style.alignItems = 'center';
                    overlay.style.cursor = 'pointer';

                    // Close overlay when clicking on the background
                    overlay.onclick = function (event) {
                        if (event.target === overlay) {
                            closeOverlay();
                        }
                    };

                    // Close on ESC key
                    const handleEscape = function (event) {
                        if (event.key === 'Escape') {
                            closeOverlay();
                        }
                    };
                    document.addEventListener('keydown', handleEscape);

                    // Function to close overlay and restore scrolling
                    function closeOverlay() {
                        document.body.removeChild(overlay);
                        document.removeEventListener('keydown', handleEscape);

                        // Restore original body styles
                        document.body.style.overflow = originalBodyStyles.overflow || '';
                        document.body.style.position = originalBodyStyles.position || '';
                        document.body.style.width = originalBodyStyles.width || '';
                        document.body.style.height = originalBodyStyles.height || '';
                    }

                    // Create close button (positioned absolutely in the top right corner)
                    const closeButton = document.createElement('button');
                    closeButton.innerHTML = '&times;';
                    closeButton.style.position = 'absolute';
                    closeButton.style.top = '20px';
                    closeButton.style.right = '20px';
                    closeButton.style.background = 'rgba(255, 255, 255, 0.2)';
                    closeButton.style.color = 'white';
                    closeButton.style.border = 'none';
                    closeButton.style.borderRadius = '50%';
                    closeButton.style.width = '40px';
                    closeButton.style.height = '40px';
                    closeButton.style.fontSize = '24px';
                    closeButton.style.cursor = 'pointer';
                    closeButton.style.transition = 'background 0.2s';
                    closeButton.style.zIndex = '10000';
                    closeButton.onmouseover = function () {
                        this.style.background = 'rgba(255, 255, 255, 0.3)';
                    };
                    closeButton.onmouseout = function () {
                        this.style.background = 'rgba(255, 255, 255, 0.2)';
                    };
                    closeButton.onclick = function (e) {
                        e.stopPropagation();
                        closeOverlay();
                    };
                    overlay.appendChild(closeButton);

                    // Create loading indicator container
                    const loadingContainer = document.createElement('div');
                    loadingContainer.style.display = 'flex';
                    loadingContainer.style.flexDirection = 'column';
                    loadingContainer.style.alignItems = 'center';
                    loadingContainer.style.justifyContent = 'center';
                    loadingContainer.style.position = 'absolute';
                    loadingContainer.style.zIndex = '1000';

                    // Create loading indicator
                    const loadingIndicator = document.createElement('div');
                    loadingIndicator.className = 'spinner-border text-light';
                    loadingIndicator.style.width = '3rem';
                    loadingIndicator.style.height = '3rem';
                    loadingContainer.appendChild(loadingIndicator);

                    // Create loading text
                    const loadingText = document.createElement('div');
                    loadingText.textContent = 'Loading document...';
                    loadingText.style.color = 'white';
                    loadingText.style.marginTop = '1.5rem';
                    loadingText.style.fontSize = '1.1rem';
                    loadingContainer.appendChild(loadingText);

                    // Create troubleshooting text (only shown during loading)
                    const troubleText = document.createElement('div');
                    troubleText.innerHTML = '<p class="text-light text-center mt-2" style="font-size: 0.9rem;">If the document doesn\'t load, try <a href="' + documentUrl + '" target="_blank" style="color: #328bffff; text-decoration: underline;">opening in a new tab</a> or refreshing the page.</p>';
                    troubleText.style.color = 'white';
                    troubleText.style.marginTop = '0.5rem';
                    troubleText.style.textAlign = 'center';
                    loadingContainer.appendChild(troubleText);

                    overlay.appendChild(loadingContainer);

                    // Function to remove loading elements
                    function removeLoadingElements() {
                        loadingContainer.remove();
                    }

                    if (isPDF) {
                        // For PDF files - Google Docs viewer in full overlay
                        const viewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(documentUrl)}&embedded=true`;

                        const iframe = document.createElement('iframe');
                        iframe.src = viewerUrl;
                        iframe.style.width = '90%';
                        iframe.style.height = '90%';
                        iframe.style.border = 'none';
                        iframe.style.borderRadius = '8px';
                        iframe.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.5)';
                        iframe.style.opacity = '0';
                        iframe.style.transition = 'opacity 0.3s ease-in-out';

                        iframe.onload = function () {
                            // Hide loading elements when iframe loads
                            removeLoadingElements();
                            iframe.style.opacity = '1';
                        };

                        overlay.appendChild(iframe);

                    } else if (isImage) {
                        // For image files - centered image with close button
                        const imageContainer = document.createElement('div');
                        imageContainer.style.position = 'relative';
                        imageContainer.style.maxWidth = '90%';
                        imageContainer.style.maxHeight = '90%';

                        const img = document.createElement('img');
                        img.src = documentUrl;
                        img.alt = documentTitle;
                        img.style.maxWidth = '100%';
                        img.style.maxHeight = '90vh';
                        img.style.borderRadius = '8px';
                        img.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.5)';
                        img.style.objectFit = 'contain';
                        img.style.opacity = '0';
                        img.style.transition = 'opacity 0.3s ease-in-out';

                        img.onload = function () {
                            // Hide loading elements when image loads
                            removeLoadingElements();
                            img.style.opacity = '1';
                        };

                        img.onerror = function () {
                            // If image fails to load, show error message
                            loadingIndicator.style.display = 'none';
                            loadingText.textContent = 'Failed to load image. Please try opening in a new tab.';
                            loadingText.style.color = '#ff6b6b';
                            // Keep the troubleshooting text visible for errors
                        };

                        imageContainer.appendChild(img);
                        overlay.appendChild(imageContainer);

                    } else {
                        // For other file types - download link
                        removeLoadingElements();

                        const downloadContainer = document.createElement('div');
                        downloadContainer.style.background = 'white';
                        downloadContainer.style.padding = '2rem';
                        downloadContainer.style.borderRadius = '8px';
                        downloadContainer.style.textAlign = 'center';

                        const message = document.createElement('p');
                        message.textContent = 'This file type cannot be previewed.';
                        message.style.marginBottom = '1rem';

                        const downloadLink = document.createElement('a');
                        downloadLink.href = documentUrl;
                        downloadLink.className = 'btn btn-primary';
                        downloadLink.innerHTML = '<i class="bi bi-download"></i> Download File';
                        downloadLink.download = documentTitle;
                        downloadLink.onclick = function () {
                            closeOverlay();
                        };

                        downloadContainer.appendChild(message);
                        downloadContainer.appendChild(downloadLink);
                        overlay.appendChild(downloadContainer);
                    }

                    // Add to document body
                    document.body.appendChild(overlay);
                }
            });

            function hasSharedItems(request1, request2) {
                // Extract item names for comparison
                const getItemNames = (request) => {
                    const names = [];

                    // Add facility names
                    if (request.requested_items.facilities) {
                        names.push(...request.requested_items.facilities.map(f => f.name.toLowerCase()));
                    }

                    // Add equipment names
                    if (request.requested_items.equipment) {
                        names.push(...request.requested_items.equipment.map(e => e.name.toLowerCase()));
                    }

                    return names;
                };

                const items1 = getItemNames(request1);
                const items2 = getItemNames(request2);

                // Check for any shared items
                return items1.some(item => items2.includes(item));
            }
            // Initialize compact calendar
            let calendar;

            // Function to initialize compact calendar
            function initializeCalendar() {
                const calendarEl = document.getElementById('calendar');
                const currentRequest = allRequests.find(req => req.request_id == requestId);
                if (!calendarEl) return;

                // Get status names to exclude
                const excludedStatuses = ['Late', 'Returned', 'Late Return', 'Completed'];

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    initialDate: currentRequest.schedule.start_date,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week',
                        day: 'Day'
                    },
                    titleFormat: {
                        year: 'numeric',
                        month: 'short'
                    },
                    height: '100%',
                    handleWindowResize: true,
                    windowResizeDelay: 200,
                    aspectRatio: null,
                    expandRows: true,
                    events: [],

                    // NEW: Time formatting settings from calendar.js
                    eventTimeFormat: {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true,
                        meridiem: true
                    },
                    slotLabelFormat: {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    },
                    dayHeaderFormat: {
                        weekday: "long",
                        month: "short",
                        day: "numeric",
                        omitCommas: false
                    },

                    // Force display of end time in all views
                    displayEventEnd: true,

                    // Custom event rendering to show duration
                    eventContent: (arg) => {
                        const arrayOfDomNodes = [];
                        const event = arg.event;

                        // Create time container
                        const timeContainer = document.createElement('div');
                        timeContainer.style.display = 'flex';
                        timeContainer.style.flexDirection = 'column';
                        timeContainer.style.gap = '1px';
                        timeContainer.style.fontSize = '0.75em';
                        timeContainer.style.opacity = '0.9';

                        // Show start and end times
                        if (event.start && event.end) {
                            const startTime = event.start.toLocaleTimeString([], {
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });

                            const endTime = event.end.toLocaleTimeString([], {
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });

                            const timeText = document.createElement('div');
                            timeText.innerText = `${startTime} - ${endTime}`;
                            timeContainer.appendChild(timeText);
                        } else if (arg.timeText) {
                            const timeText = document.createElement('div');
                            timeText.innerText = arg.timeText;
                            timeContainer.appendChild(timeText);
                        }

                        // Create title container
                        const titleContainer = document.createElement('div');
                        titleContainer.style.fontSize = '0.85em';
                        titleContainer.style.fontWeight = 'bold';
                        titleContainer.style.whiteSpace = 'normal';
                        titleContainer.style.wordWrap = 'break-word';
                        titleContainer.style.lineHeight = '1.2';

                        if (event.title) {
                            titleContainer.innerText = event.title;
                            timeContainer.appendChild(titleContainer);
                        }

                        arrayOfDomNodes.push(timeContainer);
                        return { domNodes: arrayOfDomNodes };
                    },

                    // Views configuration from calendar.js
                    views: {
                        dayGridMonth: {
                            dayHeaderFormat: { weekday: "short" },
                            eventContent: (arg) => {
                                const arrayOfDomNodes = [];
                                const event = arg.event;

                                // Create container div
                                const container = document.createElement('div');
                                container.style.display = 'flex';
                                container.style.flexDirection = 'column';
                                container.style.gap = '2px';
                                container.style.width = '100%';

                                // Show start and end times in month view (if not all-day)
                                if (event.start && event.end && !event.allDay) {
                                    const timeContainer = document.createElement('div');
                                    timeContainer.style.display = 'flex';
                                    timeContainer.style.alignItems = 'center';
                                    timeContainer.style.fontSize = '0.7em';
                                    timeContainer.style.opacity = '0.9';
                                    timeContainer.style.marginBottom = '1px';
                                    timeContainer.style.fontWeight = '500';
                                    timeContainer.style.whiteSpace = 'nowrap';

                                    const startTime = event.start.toLocaleTimeString([], {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    });

                                    const endTime = event.end.toLocaleTimeString([], {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    });

                                    // Create SINGLE div with both times
                                    const timeText = document.createElement('div');
                                    timeText.innerText = `${startTime} - ${endTime}`;
                                    timeContainer.appendChild(timeText);
                                    container.appendChild(timeContainer);
                                }

                                // Title container
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

                                arrayOfDomNodes.push(container);

                                return { domNodes: arrayOfDomNodes };
                            }
                        },

                        // For week view
                        timeGridWeek: {
                            eventContent: (arg) => {
                                const arrayOfDomNodes = [];
                                const event = arg.event;

                                // Create container div
                                const container = document.createElement('div');
                                container.style.display = 'flex';
                                container.style.flexDirection = 'column';
                                container.style.gap = '3px';
                                container.style.width = '100%';
                                container.style.height = '100%';
                                container.style.padding = '2px 4px';

                                // Time display (top) - SINGLE LINE
                                if (!event.allDay) {
                                    const timeEl = document.createElement('div');
                                    timeEl.style.fontSize = '0.75em';
                                    timeEl.style.opacity = '0.9';
                                    timeEl.style.marginBottom = '1px';
                                    timeEl.style.fontWeight = '500';
                                    timeEl.style.whiteSpace = 'nowrap';

                                    if (event.start && event.end) {
                                        const startTime = event.start.toLocaleTimeString([], {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        });

                                        const endTime = event.end.toLocaleTimeString([], {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        });

                                        // SINGLE LINE: "5:00 PM - 6:00 PM"
                                        timeEl.innerText = `${startTime} - ${endTime}`;
                                    }
                                    container.appendChild(timeEl);
                                }

                                // Title (bottom)
                                const titleEl = document.createElement('div');
                                titleEl.style.fontSize = '0.85em';
                                titleEl.style.fontWeight = 'bold';
                                titleEl.style.whiteSpace = 'normal';
                                titleEl.style.wordWrap = 'break-word';
                                titleEl.style.lineHeight = '1.2';
                                titleEl.style.flexGrow = '1';

                                if (event.title) {
                                    titleEl.innerText = event.title;
                                }
                                container.appendChild(titleEl);

                                arrayOfDomNodes.push(container);

                                return { domNodes: arrayOfDomNodes };
                            }
                        },

                        // For day view
                        timeGridDay: {
                            eventContent: (arg) => {
                                const arrayOfDomNodes = [];
                                const event = arg.event;

                                // Create container div
                                const container = document.createElement('div');
                                container.style.display = 'flex';
                                container.style.flexDirection = 'column';
                                container.style.gap = '4px';
                                container.style.width = '100%';
                                container.style.height = '100%';
                                container.style.padding = '3px 6px';

                                // Time display (top) - SINGLE LINE
                                if (!event.allDay) {
                                    const timeEl = document.createElement('div');
                                    timeEl.style.fontSize = '0.75em';
                                    timeEl.style.opacity = '0.9';
                                    timeEl.style.marginBottom = '2px';
                                    timeEl.style.fontWeight = '500';
                                    timeEl.style.whiteSpace = 'nowrap';

                                    if (event.start && event.end) {
                                        const startTime = event.start.toLocaleTimeString([], {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        });

                                        const endTime = event.end.toLocaleTimeString([], {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        });

                                        // SINGLE LINE: "5:00 PM - 6:00 PM"
                                        timeEl.innerText = `${startTime} - ${endTime}`;
                                    }
                                    container.appendChild(timeEl);
                                }

                                // Title (bottom)
                                const titleEl = document.createElement('div');
                                titleEl.style.fontSize = '0.9em';
                                titleEl.style.fontWeight = 'bold';
                                titleEl.style.whiteSpace = 'normal';
                                titleEl.style.wordWrap = 'break-word';
                                titleEl.style.lineHeight = '1.2';
                                titleEl.style.flexGrow = '1';

                                if (event.title) {
                                    titleEl.innerText = event.title;
                                }
                                container.appendChild(titleEl);

                                arrayOfDomNodes.push(container);

                                return { domNodes: arrayOfDomNodes };
                            }
                        }
                    },

                    eventClick: function (info) {
                        const request = allRequests.find(req => req.request_id == info.event.extendedProps.requestId);
                        if (request) {
                            showEventModal(request);
                        }
                    },
                    eventDidMount: function (info) {
                        // Add custom styling based on status
                        const request = allRequests.find(req => req.request_id == info.event.extendedProps.requestId);

                        if (request) {
                            // Special styling for current request
                            if (info.event.extendedProps.isCurrent) {
                                info.el.style.backgroundColor = '#8164c4ff';
                                info.el.style.borderColor = '#8164c4ff';
                                info.el.style.color = '#FFFFFF';
                                info.el.style.fontWeight = 'bold';
                            } else {
                                info.el.style.backgroundColor = request.form_details.status.color;
                                info.el.style.borderColor = request.form_details.status.color;
                            }
                        }
                    },
                    datesSet: function (info) {
                        // Force refresh of calendar rendering
                        calendar.updateSize();
                    },
                    viewDidMount: function (info) {
                        // Ensure proper initial rendering
                        setTimeout(() => calendar.updateSize(), 0);
                    },
                    slotMinTime: '00:00:00',
                    slotMaxTime: '24:00:00',
                    allDaySlot: false,
                    nowIndicator: true,
                    navLinks: true,
                    eventDisplay: 'block'
                });

                calendar.render();

                // Update calendar with filtered events
                if (calendar) {
                    calendar.removeAllEvents();

                    // Filter requests to exclude the specified statuses AND exclude current request from regular events
                    const filteredRequests = allRequests.filter(req => {
                        const statusName = req.form_details.status.name;

                        // Skip if status is in excluded list
                        if (excludedStatuses.includes(statusName)) {
                            return false;
                        }

                        // Skip the current request (we'll add it separately with special styling)
                        if (req.request_id == requestId) {
                            return false;
                        }

                        // Check if this request shares any items with the current request
                        const currentRequest = allRequests.find(r => r.request_id == requestId);
                        if (currentRequest && !hasSharedItems(currentRequest, req)) {
                            return false;
                        }

                        // Include all other requests
                        return true;
                    });

                    console.log('Filtered calendar events with shared items:', {
                        totalRequests: allRequests.length,
                        filteredRequests: filteredRequests.length,
                        excludedStatuses: excludedStatuses,
                        filteredRequestIds: filteredRequests.map(r => r.request_id),
                        currentRequestId: requestId
                    });

                    // Add filtered events to calendar (excluding current request)
                    filteredRequests.forEach(req => {
                        const calendarTitle = req.form_details.calendar_info.title || 'No Calendar Title';

                        calendar.addEvent({
                            title: calendarTitle,
                            start: `${req.schedule.start_date}T${req.schedule.start_time}`,
                            end: `${req.schedule.end_date}T${req.schedule.end_time}`,
                            extendedProps: {
                                status: req.form_details.status.name,
                                requestId: req.request_id,
                                isCurrent: false
                            },
                            description: req.form_details.calendar_info.description
                        });
                    });

                    // Add current request with special styling (only once)
                    const currentCalendarTitle = currentRequest.form_details.calendar_info.title || 'No Calendar Title';

                    calendar.addEvent({
                        title: 'Current Request',
                        start: `${currentRequest.schedule.start_date}T${currentRequest.schedule.start_time}`,
                        end: `${currentRequest.schedule.end_date}T${currentRequest.schedule.end_time}`,
                        extendedProps: {
                            status: currentRequest.form_details.status.name,
                            requestId: currentRequest.request_id,
                            isCurrent: true
                        },
                        color: '#FF6B35',
                        textColor: '#FFFFFF',
                        description: currentRequest.form_details.calendar_info.description
                    });
                }
            }


            // Function to show event details in modal
            function showEventModal(request) {
                const formattedRequestId = String(request.request_id).padStart(4, '0');
                const calendarTitle = request.form_details.calendar_info?.title || 'No Calendar Title';
                const calendarDescription = request.form_details.calendar_info?.description || 'No description';

                document.getElementById('eventModalTitle').textContent =
                    `Request ID #${formattedRequestId} (${calendarTitle})`;

                // Set calendar title and description
                document.getElementById('modalCalendarTitle').value = calendarTitle;
                document.getElementById('modalCalendarDescription').value = calendarDescription;

                // Store current request ID and original values
                currentRequestId = request.request_id;
                originalCalendarTitle = calendarTitle;
                originalCalendarDescription = calendarDescription;

                // Reset edit states to ensure fields are read-only initially
                resetEditStates();

                // Set other modal content
                document.getElementById('modalRequester').textContent =
                    `${request.user_details.first_name} ${request.user_details.last_name}`;
                document.getElementById('modalPurpose').textContent = request.form_details.purpose;
                document.getElementById('modalParticipants').textContent = request.form_details.num_participants;
                document.getElementById('modalStatus').innerHTML = `
                                                                                                                                <span class="badge" style="background-color: ${request.form_details.status.color}">
                                                                                                                                    ${request.form_details.status.name}
                                                                                                                                </span>
                                                                                                                                `;
                document.getElementById('modalFee').textContent = `${formatMoney(request.fees.approved_fee)}`;
                document.getElementById('modalApprovals').textContent = `${request.approval_info.approval_count}`;
                document.getElementById('modalRejections').textContent = `${request.approval_info.rejection_count}`;

                // Format requested items
                let itemsHtml = '';

                if (request.requested_items.facilities.length > 0) {
                    itemsHtml += '<div class="fw-bold small mb-1">Facilities:</div>';
                    itemsHtml += request.requested_items.facilities.map(f =>
                        `<div class="mb-1 small">• ${f.name} | ${formatMoney(f.fee)}${f.rate_type === 'Per Hour' ? '/hour' : '/event'}${f.is_waived ? ' <span class="text-muted">(Waived)</span>' : ''}</div>`
                    ).join('');
                }

                if (request.requested_items.equipment.length > 0) {
                    itemsHtml += '<div class="fw-bold small mt-2 mb-1">Equipment:</div>';
                    itemsHtml += request.requested_items.equipment.map(e =>
                        `<div class="mb-1 small">• ${e.name} × ${e.quantity || 1} | ${formatMoney(e.fee)}${e.rate_type === 'Per Hour' ? '/hour' : '/event'}${e.is_waived ? ' <span class="text-muted">(Waived)</span>' : ''}</div>`
                    ).join('');
                }

                document.getElementById('modalItems').innerHTML = itemsHtml || '<p class="text-muted small">No items requested</p>';

                // Set up view details button
                document.getElementById('modalViewDetails').onclick = function () {
                    window.location.href = `/admin/requisition/${request.request_id}`;
                };

                // Setup event listeners for edit functionality
                setTimeout(() => {
                    setupEventListeners();
                }, 100);

                eventModal.show();
            }
            // One-time event listener setup with protection
            function setupEventListeners() {
                if (eventListenersSetup) return;

                // Remove any existing event listeners by cloning elements
                const elements = [
                    'editCalendarTitleBtn', 'editCalendarDescriptionBtn',
                    'saveCalendarTitleBtn', 'saveCalendarDescriptionBtn',
                    'cancelCalendarTitleBtn', 'cancelCalendarDescriptionBtn'
                ];

                elements.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        const newElement = element.cloneNode(true);
                        element.parentNode.replaceChild(newElement, element);
                    }
                });

                // Now attach fresh event listeners
                document.getElementById('editCalendarTitleBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    enableEdit('title');
                });

                document.getElementById('editCalendarDescriptionBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    enableEdit('description');
                });

                document.getElementById('saveCalendarTitleBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    saveEdit('title');
                });

                document.getElementById('saveCalendarDescriptionBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    saveEdit('description');
                });

                document.getElementById('cancelCalendarTitleBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    cancelEdit('title');
                });

                document.getElementById('cancelCalendarDescriptionBtn').addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    cancelEdit('description');
                });

                eventListenersSetup = true;
            }

            // Reset all edit states
            function resetEditStates() {
                // Title field
                const titleField = document.getElementById('modalCalendarTitle');
                if (titleField) {
                    titleField.readOnly = true;
                }
                const editTitleBtn = document.getElementById('editCalendarTitleBtn');
                if (editTitleBtn) {
                    editTitleBtn.classList.remove('d-none');
                }
                const titleActions = document.getElementById('calendarTitleActions');
                if (titleActions) {
                    titleActions.classList.add('d-none');
                }

                // Description field
                const descField = document.getElementById('modalCalendarDescription');
                if (descField) {
                    descField.readOnly = true;
                }
                const editDescBtn = document.getElementById('editCalendarDescriptionBtn');
                if (editDescBtn) {
                    editDescBtn.classList.remove('d-none');
                }
                const descActions = document.getElementById('calendarDescriptionActions');
                if (descActions) {
                    descActions.classList.add('d-none');
                }
            }

            // Enable editing for a field
            function enableEdit(fieldType) {
                if (fieldType === 'title') {
                    document.getElementById('modalCalendarTitle').readOnly = false;
                    document.getElementById('modalCalendarTitle').focus();
                    document.getElementById('editCalendarTitleBtn').classList.add('d-none');
                    document.getElementById('calendarTitleActions').classList.remove('d-none');
                } else if (fieldType === 'description') {
                    document.getElementById('modalCalendarDescription').readOnly = false;
                    document.getElementById('modalCalendarDescription').focus();
                    document.getElementById('editCalendarDescriptionBtn').classList.add('d-none');
                    document.getElementById('calendarDescriptionActions').classList.remove('d-none');
                }
            }

            // Cancel editing and revert changes
            function cancelEdit(fieldType) {
                if (fieldType === 'title') {
                    document.getElementById('modalCalendarTitle').value = originalCalendarTitle;
                } else if (fieldType === 'description') {
                    document.getElementById('modalCalendarDescription').value = originalCalendarDescription;
                }
                resetEditStates();
            }

            // Save changes to database
            async function saveEdit(fieldType) {
                const newTitle = document.getElementById('modalCalendarTitle').value.trim();
                const newDescription = document.getElementById('modalCalendarDescription').value.trim();

                // Validate title if we're saving title
                if (fieldType === 'title' && !newTitle) {
                    showToast('Calendar title cannot be empty', 'error');
                    return;
                }

                try {
                    const response = await fetch(`/api/admin/requisition-forms/${currentRequestId}/calendar-info`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            calendar_title: newTitle,
                            calendar_description: newDescription
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`Failed to update calendar info: ${response.status}`);
                    }

                    const result = await response.json();

                    // Update original values
                    originalCalendarTitle = newTitle;
                    originalCalendarDescription = newDescription;

                    // UPDATE THE LOCAL allRequests ARRAY
                    const requestIndex = allRequests.findIndex(req => req.request_id === currentRequestId);
                    if (requestIndex !== -1) {
                        // Update the calendar info in the local array
                        if (!allRequests[requestIndex].form_details.calendar_info) {
                            allRequests[requestIndex].form_details.calendar_info = {};
                        }
                        allRequests[requestIndex].form_details.calendar_info.title = newTitle;
                        allRequests[requestIndex].form_details.calendar_info.description = newDescription;
                    }

                    // Update modal title if calendar title was changed
                    if (fieldType === 'title') {
                        document.getElementById('eventModalTitle').textContent =
                            `Request ID #${String(currentRequestId).padStart(4, '0')} (${newTitle})`;
                    }

                    resetEditStates();
                    showToast(`Calendar ${fieldType} updated successfully`, 'success');

                    // Refresh calendar events to reflect changes
                    if (calendar) {
                        // Remove all events and re-add them
                        calendar.removeAllEvents();

                        // Filter requests to exclude the specified statuses
                        const filteredRequests = allRequests.filter(req => {
                            const statusName = req.form_details.status.name;
                            const excludedStatuses = ['Late', 'Returned', 'Late Return', 'Completed'];

                            if (excludedStatuses.includes(statusName)) {
                                return false;
                            }

                            // Skip the current request (we'll add it separately with special styling)
                            if (req.request_id == requestId) {
                                return false;
                            }

                            return true;
                        });

                        // Add filtered events to calendar (excluding current request)
                        filteredRequests.forEach(req => {
                            const calendarTitle = req.form_details.calendar_info?.title || 'No Calendar Title';

                            calendar.addEvent({
                                title: calendarTitle,
                                start: `${req.schedule.start_date}T${req.schedule.start_time}`,
                                end: `${req.schedule.end_date}T${req.schedule.end_time}`,
                                extendedProps: {
                                    status: req.form_details.status.name,
                                    requestId: req.request_id,
                                    isCurrent: false
                                },
                                description: req.form_details.calendar_info?.description
                            });
                        });

                        // Re-add current request with special styling
                        const currentRequest = allRequests.find(req => req.request_id == requestId);
                        if (currentRequest) {
                            const currentCalendarTitle = currentRequest.form_details.calendar_info?.title || 'No Calendar Title';

                            calendar.addEvent({
                                title: 'Current Request',
                                start: `${currentRequest.schedule.start_date}T${currentRequest.schedule.start_time}`,
                                end: `${currentRequest.schedule.end_date}T${currentRequest.schedule.end_time}`,
                                extendedProps: {
                                    status: currentRequest.form_details.status.name,
                                    requestId: currentRequest.request_id,
                                    isCurrent: true
                                },
                                color: '#FF6B35',
                                textColor: '#FFFFFF',
                                description: currentRequest.form_details.calendar_info?.description
                            });
                        }
                    }

                } catch (error) {
                    console.error(`Error updating calendar ${fieldType}:`, error);
                    showToast(`Failed to update calendar ${fieldType}`, 'error');

                    // Revert to original values on error
                    if (fieldType === 'title') {
                        document.getElementById('modalCalendarTitle').value = originalCalendarTitle;
                    } else {
                        document.getElementById('modalCalendarDescription').value = originalCalendarDescription;
                    }
                    resetEditStates();
                }
            }

            // Function to update additional fees display
            // Function to update additional fees display
            function updateAdditionalFees(requisitionFees) {
                const additionalFeesContainer = document.getElementById('additionalFeesContainer');

                // Clear existing content
                additionalFeesContainer.innerHTML = '';

                if (requisitionFees && requisitionFees.length > 0) {
                    requisitionFees.forEach((fee, index) => {
                        const feeElement = document.createElement('div');
                        feeElement.className = 'fee-item d-flex justify-content-between align-items-center mb-1';

                        let amountText = '';
                        if (fee.type === 'fee') {
                            amountText = `${formatMoney(fee.fee_amount)}`;
                        } else if (fee.type === 'discount') {
                            if (fee.discount_type === 'Percentage') {
                                // Remove .00 for percentage discounts
                                amountText = `${parseFloat(fee.discount_amount)}%`;
                            } else {
                                amountText = `-${formatMoney(fee.discount_amount)}`;
                            }
                        } else if (fee.type === 'mixed') {
                            const feePart = fee.fee_amount > 0 ? `${formatMoney(fee.fee_amount)}` : '';
                            const discountPart = fee.discount_amount > 0 ?
                                (fee.discount_type === 'Percentage' ?
                                    `-${parseFloat(fee.discount_amount)}%` :
                                    `-₱${parseFloat(fee.discount_amount).toFixed(2)}`) : '';
                            amountText = `${feePart} ${discountPart}`.trim();
                        }

                        // Create label with account number if it exists
                        let labelHtml = fee.label;
                        if (fee.account_num) {
                            labelHtml = `${fee.label} <span class="text-muted">(${fee.account_num})</span>`;
                        }

                        feeElement.innerHTML = `
                                                                                                                                        <div>
                                                                                                                                            <span class="item-name">${labelHtml}</span>
                                                                                                                                        </div>
                                                                                                                                        <span class="d-flex align-items-center">
                                                                                                                                            <span class="item-price me-2">${amountText}</span>
                                                                                                                                            <button class="btn btn-sm btn-danger delete-fee-btn" data-fee-id="${fee.fee_id}" data-fee-type="${fee.type}">
                                                                                                                                                <i class="fa fa-times"></i>
                                                                                                                                            </button>
                                                                                                                                        </span>
                                                                                                                                    `;

                        additionalFeesContainer.appendChild(feeElement);
                    });

                    // Handle delete click
                    additionalFeesContainer.querySelectorAll('.delete-fee-btn').forEach(btn => {
                        btn.addEventListener('click', async (e) => {
                            const feeId = e.currentTarget.dataset.feeId;
                            const feeType = e.currentTarget.dataset.feeType;

                            if (!feeId) {
                                console.error('No fee ID found for deletion');
                                showToast('Cannot delete fee that does not exist.', 'error');
                                return;
                            }

                            try {
                                const response = await fetch(`/api/admin/requisition/${requestId}/fee/${feeId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Authorization': `Bearer ${adminToken}`,
                                        'Accept': 'application/json'
                                    }
                                });

                                if (!response.ok) {
                                    const errorData = await response.json();
                                    throw new Error(errorData.details || 'Failed to delete fee');
                                }

                                const result = await response.json();

                                // Show success message
                                showToast('Fee removed successfully', 'success');

                                // Refresh ALL fee displays including the total
                                await refreshAllFeeDisplays();

                            } catch (error) {
                                console.error('Error removing fee:', error);
                                console.error('Fee deletion details:', {
                                    feeId: feeId,
                                    feeType: feeType,
                                    requestId: requestId,
                                    error: error.message
                                });
                                showToast('Failed to remove fee: ' + error.message, 'error');
                            }
                        });
                    });
                } else {
                    // Show empty message
                    additionalFeesContainer.innerHTML = `
                                                                                                                                    <div class="text-center text-muted py-4">
                                                                                                                                        <i class="fa fa-coins fa-2x d-block mb-2"></i>
                                                                                                                                        <p class="mb-0">No additional fees or discounts</p>
                                                                                                                                    </div>
                                                                                                                                `;
                }
            }

            // Update the dropdown menu text dynamically based on current status
            function updateStatusDropdownText() {
                const statusBadge = document.getElementById('statusBadge');
                const currentStatusName = statusBadge ? statusBadge.textContent.trim() : '';
                const lateOption = document.getElementById('statusLate');

                if (lateOption) {
                    if (currentStatusName === 'Late') {
                        lateOption.innerHTML = '<i class="bi bi-check-circle me-2"></i> Unmark Late';
                        lateOption.dataset.value = 'Late'; // Keep the same data value
                    } else {
                        lateOption.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i> Mark Late';
                        lateOption.dataset.value = 'Late';
                    }
                }
            }
            // Update document buttons and icons
            function updateDocumentIcons(documents) {
                // Helper function to update button state
                function updateButton(buttonId, iconId, hasDocument, documentUrl, title) {
                    const button = document.getElementById(buttonId);
                    const icon = document.getElementById(iconId);

                    if (button && icon) {
                        if (hasDocument) {
                            // Enable button
                            button.classList.remove('btn-document-null');
                            button.classList.add('btn-primary');
                            button.disabled = false;
                            button.setAttribute('data-document-url', documentUrl);
                            button.setAttribute('data-document-title', title);
                            button.setAttribute('data-bs-toggle', 'modal');
                            button.setAttribute('data-bs-target', '#documentModal');

                            // Update icon
                            icon.classList.remove('text-muted');
                            icon.classList.add('text-primary');
                        } else {
                            // Disable button
                            button.classList.remove('btn-primary');
                            button.classList.add('btn-document-null');
                            button.disabled = true;
                            button.removeAttribute('data-document-url');
                            button.removeAttribute('data-bs-toggle');
                            button.removeAttribute('data-bs-target');

                            // Update icon
                            icon.classList.add('text-muted');
                            icon.classList.remove('text-primary');
                        }
                    }
                }

                // Formal Letter
                updateButton(
                    'formalLetterBtn',
                    'formalLetterIcon',
                    documents.formal_letter.url,
                    documents.formal_letter.url,
                    'Formal Letter'
                );

                // Facility Layout
                updateButton(
                    'facilityLayoutBtn',
                    'facilityLayoutIcon',
                    documents.facility_layout.url,
                    documents.facility_layout.url,
                    'Facility Layout'
                );

                // Proof of Payment
                updateButton(
                    'proofOfPaymentBtn',
                    'proofOfPaymentIcon',
                    documents.proof_of_payment.url,
                    documents.proof_of_payment.url,
                    'Proof of Payment'
                );

                // Official Receipt - Check both uploaded document AND generated receipt number
                const hasOfficialReceipt = documents.official_receipt.url || documents.form_details?.official_receipt_num;
                const receiptUrl = documents.official_receipt.url || (documents.form_details?.official_receipt_num ? `/official-receipt/${requestId}` : null);

                updateButton(
                    'officialReceiptBtn',
                    'officialReceiptIcon',
                    hasOfficialReceipt,
                    receiptUrl,
                    'Official Receipt'
                );

                // Special handling for official receipt if it's just a receipt number
                if (documents.form_details?.official_receipt_num && !documents.official_receipt.url) {
                    const button = document.getElementById('officialReceiptBtn');
                    if (button) {
                        button.classList.remove('btn-document-null');
                        button.classList.add('btn-primary');
                        button.disabled = false;
                        button.removeAttribute('data-bs-toggle');
                        button.removeAttribute('data-bs-target');
                        button.onclick = function () {
                            window.open(`/official-receipt/${requestId}`, '_blank');
                        };
                    }
                }
            }

            async function fetchRequestDetails() {
                try {
                    // Ensure loading state is visible and content is hidden
                    document.getElementById('loadingState').style.display = 'block';
                    document.getElementById('contentState').style.display = 'none';

                    const response = await fetch(`/api/admin/requisition-forms`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch request details');

                    allRequests = await response.json();
                    const request = allRequests.find(req => req.request_id == requestId);
                    if (!request) throw new Error('Request not found');

                    // Update approval counts for the finalize modal
                    updateApprovalCounts(
                        request.approval_info.approval_count,
                        request.approval_info.rejection_count
                    );

                    // Update request ID in title
                    document.getElementById('requestIdTitle').textContent = 'RID #' + String(requestId).padStart(4, '0');


                    // Update status badge
                    const statusBadge = document.getElementById('statusBadge');
                    statusBadge.textContent = request.form_details.status.name;
                    statusBadge.style.backgroundColor = request.form_details.status.color;

                    // Contact information - simplified grid layout

                    document.getElementById('formDetails').innerHTML = `
                                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                                        <span class="text-muted">Requester:</span>
                                                        <span class="fw-medium">${request.user_details.first_name} ${request.user_details.last_name}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                                        <span class="text-muted">School ID:</span>
                                                        <span class="fw-medium">${request.user_details.school_id || 'N/A'}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                                        <span class="text-muted">Email:</span>
                                                       <span class="fw-medium" style="word-break: break-word;">${request.user_details.email}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                                        <span class="text-muted">Organization:</span>
                                                        <span class="fw-medium">${request.user_details.organization_name || 'N/A'}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                                        <span class="text-muted">User Type:</span>
                                                        <span class="fw-medium">${request.user_details.user_type}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between py-1">
                                                        <span class="text-muted">Contact #:</span>
                                                        <span class="fw-medium">${request.user_details.contact_number || 'N/A'}</span>
                                                    </div>
                                                `;
                    // Event details - simplified layout
                    document.getElementById('eventDetails').innerHTML = `
                                                                                                                <div class="row g-3">
                                                                                                                    <div class="col-sm-6">
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Endorser</div>
                                                                                                                            <div>${request.documents.endorser || 'N/A'}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Rental Purpose</div>
                                                                                                                            <div>${request.form_details.purpose}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Number of Tables</div>
                                                                                                                            <div>${request.form_details.num_tables || 0}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Start Schedule</div>
                                                                                                                            <div>${formatStartDateTime(request.schedule)}</div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="col-sm-6">
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Date Endorsed</div>
                                                                                                                            <div>${formatDateEndorsed(request.documents.date_endorsed) || 'N/A'}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Participants</div>
                                                                                                                            <div>${request.form_details.num_participants}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">Number of Chairs</div>
                                                                                                                            <div>${request.form_details.num_chairs || 0}</div>
                                                                                                                        </div>
                                                                                                                        <div class="mb-3">
                                                                                                                            <div class="fw-bold text-primary">End Schedule</div>
                                                                                                                            <div>${formatEndDateTime(request.schedule)}</div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="col-12">
                                                                                                                        <div class="fw-bold text-primary">Additional Requests</div>
                                                                                                                        <div>${request.form_details.additional_requests || 'No additional requests.'}</div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            `;
                    // Update requested items with fee breakdown
                    document.getElementById('requestedItems').innerHTML = `
                                                                                                                <div class="requested-items-list">
                                                                                                                    ${request.requested_items.facilities.length > 0 ? `
                                                                                                                        <div class="mb-3">
                                                                                                                            <h6 class="fw-bold mb-2" style="font-size:0.9rem;">Facilities:</h6>
                                                                                                                            ${request.requested_items.facilities.map(f => `
                                                                                                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                                                                                                    <span class="item-name">${f.name}</span>
                                                                                                                                    <span class="item-price fw-bold">${formatMoney(f.fee)}${f.rate_type === 'Per Hour' ? '/hour' : '/event'}</span>
                                                                                                                                </div>
                                                                                                                            `).join('')}
                                                                                                                        </div>
                                                                                                                    ` : ''}

                                                                                                                    ${request.requested_items.equipment.length > 0 ? `
                                                                                                                        <div>
                                                                                                                            <h6 class="fw-bold mb-2" style="font-size:0.9rem;">Equipment:</h6>
                                                                                                                            ${request.requested_items.equipment.map(e => `
                                                                                                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                                                                                                    <span class="item-name">${e.name} × ${e.quantity || 1}</span>
                                                                                                                                    <span class="item-price fw-bold">${formatMoney(e.fee)}${e.rate_type === 'Per Hour' ? '/hour' : '/event'}</span>
                                                                                                                                </div>
                                                                                                                            `).join('')}
                                                                                                                        </div>
                                                                                                                    ` : ''}
                                                                                                                </div>
                                                                                                            `;

                    // Update status cards
                    document.getElementById('approvalsCount').textContent = request.approval_info.approval_count;
                    document.getElementById('rejectionsCount').textContent = request.approval_info.rejection_count;
                    // Update document icons
                    updateDocumentIcons(request.documents);
                    // Load additional data and wait for completion
                    await Promise.all([
                        loadMixedActivity(),
                        loadApprovalHistory(),
                        updateBaseFees(request.requested_items, request.schedule)
                    ]);

                    // Ensure misc fees are updated
                    if (request.fees && request.fees.requisition_fees) {
                        updateAdditionalFees(request.fees.requisition_fees);
                    }

                    // Update dropdown text
                    updateStatusDropdownText();

                    // Check admin role and update UI
                    await checkAdminRoleAndUpdateUI();


                    // ONLY NOW show content after everything is properly loaded
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('contentState').style.display = 'block';

                } catch (error) {
                    console.error('Error:', error);
                    showToast('Failed to load request details', 'error');

                    // Show error state
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('contentState').innerHTML = `
                                                                                                                                                                                <div class="alert alert-danger">
                                                                                                                                                                                    Failed to load request details. Please try refreshing the page.
                                                                                                                                                                                </div>
                                                                                                                                                                            `;
                    document.getElementById('contentState').style.display = 'block';
                }
            }

            // Add this function to calculate rental duration
            function calculateRentalDuration(startDate, startTime, endDate, endTime) {
                const start = new Date(`${startDate}T${startTime}`);
                const end = new Date(`${endDate}T${endTime}`);

                const durationInHours = Math.round((end - start) / (1000 * 60 * 60) * 100) / 100;
                const formattedStart = start.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const formattedEnd = end.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                return {
                    hours: durationInHours,
                    formatted: `${durationInHours} hours (${formattedStart} – ${formattedEnd})`
                };
            }

            // Function to update base fees display
            function updateBaseFees(requestedItems, schedule) {
                const facilitiesContainer = document.getElementById('facilitiesFees');
                const equipmentContainer = document.getElementById('equipmentFees');

                // Clear existing content
                facilitiesContainer.innerHTML = '';
                equipmentContainer.innerHTML = '';

                // Calculate rental duration for hourly rate calculations
                const startDateTime = new Date(`${schedule.start_date}T${schedule.start_time}`);
                const endDateTime = new Date(`${schedule.end_date}T${schedule.end_time}`);
                const durationHours = Math.max(0, (endDateTime - startDateTime) / (1000 * 60 * 60));

                let totalBaseFees = 0; // Track total

                // Add facilities with proper rate type logic and waiver checkboxes
                if (requestedItems.facilities && requestedItems.facilities.length > 0) {
                    requestedItems.facilities.forEach(facility => {
                        const facilityElement = document.createElement('div');
                        facilityElement.className = `fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${facility.is_waived ? 'waived' : ''}`;

                        let feeAmount = parseFloat(facility.fee);
                        let itemTotal = 0;
                        let rateDescription = '';

                        if (facility.rate_type === 'Per Hour' && durationHours > 0) {
                            itemTotal = feeAmount * durationHours;
                            rateDescription = `${formatMoney(feeAmount)}/hr × ${durationHours.toFixed(1)} hrs`;
                            if (!facility.is_waived) totalBaseFees += itemTotal;
                        } else {
                            itemTotal = feeAmount;
                            rateDescription = `${formatMoney(feeAmount)}/event`;
                            if (!facility.is_waived) totalBaseFees += itemTotal;
                        }

                        facilityElement.innerHTML = `
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="form-check me-2">
                                                                                <input class="form-check-input waiver-checkbox" type="checkbox" 
                                                                                    data-type="facility" 
                                                                                    data-id="${facility.requested_facility_id}"
                                                                                    ${facility.is_waived ? 'checked' : ''}>
                                                                            </div>
                                                                            <span class="item-name">${facility.name}</span>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <small>${rateDescription}</small>
                                                                            <div><strong>${formatMoney(itemTotal)}</strong></div>
                                                                        </div>
                                                                    `;
                        facilitiesContainer.appendChild(facilityElement);
                    });
                }

                // Add equipment with proper rate type logic and waiver checkboxes
                if (requestedItems.equipment && requestedItems.equipment.length > 0) {
                    requestedItems.equipment.forEach(equipment => {
                        const equipmentElement = document.createElement('div');
                        equipmentElement.className = `fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${equipment.is_waived ? 'waived' : ''}`;

                        let unitFee = parseFloat(equipment.fee);
                        const quantity = equipment.quantity || 1;
                        let itemTotal = 0;
                        let rateDescription = '';

                        if (equipment.rate_type === 'Per Hour' && durationHours > 0) {
                            itemTotal = (unitFee * durationHours) * quantity;
                            rateDescription = `${formatMoney(unitFee)}/hr × ${durationHours.toFixed(1)} hrs × ${quantity}`;
                            if (!equipment.is_waived) totalBaseFees += itemTotal;
                        } else {
                            itemTotal = unitFee * quantity;
                            rateDescription = `${formatMoney(unitFee)}/event × ${quantity}`;
                            if (!equipment.is_waived) totalBaseFees += itemTotal;
                        }

                        equipmentElement.innerHTML = `
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="form-check me-2">
                                                                                <input class="form-check-input waiver-checkbox" type="checkbox" 
                                                                                    data-type="equipment" 
                                                                                    data-id="${equipment.requested_equipment_id}"
                                                                                    ${equipment.is_waived ? 'checked' : ''}>
                                                                            </div>
                                                                            <span class="item-name">
                                                                                ${equipment.name} ${quantity > 1 ? `(×${quantity})` : ''}
                                                                            </span>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <small>${rateDescription}</small>
                                                                            <div><strong>${formatMoney(itemTotal)}</strong></div>
                                                                        </div>
                                                                    `;
                        equipmentContainer.appendChild(equipmentElement);
                    });
                }

                // Add event listeners to waiver checkboxes
                document.querySelectorAll('#baseFeesContainer .waiver-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function () {
                        handleWaiverChange(this);
                    });
                });

                // IMPORTANT: Update the footer total with the calculated value
                document.getElementById('feeBreakdownTotal').textContent = formatMoney(totalBaseFees);

                // Debug logging
                console.log('Base Fees Total:', totalBaseFees);
            }
            function formatDateEndorsed(dateString) {
                if (!dateString || dateString === 'N/A') return 'N/A';

                try {
                    const date = new Date(dateString);
                    const options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    return date.toLocaleDateString('en-US', options);
                } catch (error) {
                    console.error('Error formatting date endorsed:', error);
                    return dateString; // Return original if formatting fails
                }
            }

            function formatStartDateTime(schedule) {
                const startDate = new Date(schedule.start_date + 'T' + schedule.start_time);
                const dateOptions = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                const timeOptions = {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                };

                return `${startDate.toLocaleDateString('en-US', dateOptions)} | ${startDate.toLocaleTimeString('en-US', timeOptions)}`;
            }

            function formatEndDateTime(schedule) {
                const endDate = new Date(schedule.end_date + 'T' + schedule.end_time);
                const dateOptions = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };
                const timeOptions = {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                };

                return `${endDate.toLocaleDateString('en-US', dateOptions)} | ${endDate.toLocaleTimeString('en-US', timeOptions)}`;
            }







            // Function to load and display fees

            async function loadFees() {
                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/fees`, {
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) throw new Error('Failed to fetch fees');

                    const fees = await response.json();

                    // Clear existing fees
                    feesContainer.innerHTML = '';

                    if (fees.length === 0) {
                        // No need for placeholder anymore since we're using mixed activity display
                        return;
                    }

                    // Add each fee to the container
                    for (const fee of fees) {
                        const feeItem = await createFeeItem(fee);
                        feesContainer.appendChild(feeItem);
                    }

                } catch (error) {
                    console.error('Error loading fees:', error);
                }
            }

            // Function to create a fee item element
            async function createFeeItem(fee) {
                const feeItem = document.createElement("div");
                feeItem.className = "fee-item d-flex align-items-start p-2 mb-2 rounded";
                feeItem.dataset.feeId = fee.fee_id;

                const timestamp = new Date(fee.created_at).toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Ensure amount is a number before using toFixed()
                const amount = parseFloat(fee.type === 'discount' ? fee.discount_amount : fee.fee_amount);
                const typeName = fee.type === 'discount' ? 'Discount' : 'Additional fee';

                // Use the admin info from the fee response if available
                let adminName = fee.added_by?.name || 'Admin';
                let adminPhoto = null;

                // If we don't have admin info from the fee response, try to get it
                if (!fee.added_by) {
                    try {
                        const adminResponse = await fetch('/api/admin/profile', {
                            headers: {
                                'Authorization': `Bearer ${adminToken}`,
                                'Accept': 'application/json'
                            }
                        });

                        if (adminResponse.ok) {
                            const adminData = await adminResponse.json();
                            adminName = `${adminData.first_name}${adminData.middle_name ? ' ' + adminData.middle_name : ''} ${adminData.last_name}`;
                            adminPhoto = adminData.photo_url;
                        }
                    } catch (error) {
                        console.error('Failed to fetch admin info:', error);
                    }
                } else {
                    // Use the admin info from the fee response
                    adminName = fee.added_by.name;
                }

                feeItem.innerHTML = `
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ${adminPhoto ?
                        `<img src="${adminPhoto}" class="rounded-circle me-3" width="32" height="32" alt="Admin Photo">` :
                        `<i class="bi bi-person-circle fs-5 me-3 text-secondary"></i>`
                    }
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    <div class="flex-grow-1">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <small class="text-muted fst-italic">
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    ${fee.label} (${typeName}) of ₱${amount.toFixed(2)} added by <strong>${adminName}</strong>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                </small>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <button class="btn btn-sm remove-btn text-secondary p-0 border-0">
                                                                                                                                                                                                                                                                                <i class="bi bi-x-lg"></i>
                                                                                                                                                                                                                                                                            </button>


                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            </button>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <small class="text-muted fst-italic">${timestamp}</small>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                `;

                // Add remove functionality for regular fees
                feeItem.querySelector(".remove-btn").addEventListener("click", async function () {
                    try {
                        const deleteResponse = await fetch(`/api/admin/requisition/${requestId}/fee/${fee.fee_id}`, {
                            method: 'DELETE',
                            headers: {
                                'Authorization': `Bearer ${adminToken}`,
                                'Accept': 'application/json'
                            }
                        });

                        if (!deleteResponse.ok) {
                            throw new Error('Failed to delete fee from database');
                        }

                        feeItem.remove();
                        // Remove this line: togglePlaceholder();
                        fetchRequestDetails(); // Refresh fee breakdown

                    } catch (error) {
                        console.error('Error removing fee:', error);
                        showToast('Failed to remove fee: ' + error.message, 'error');
                    }
                });

                return feeItem;
            }

            let currentApprovalCount = 0;
            let currentRejectionCount = 0;

            // Update approval counts when data is loaded
            function updateApprovalCounts(approvalCount, rejectionCount) {
                currentApprovalCount = approvalCount;
                currentRejectionCount = rejectionCount;
                document.getElementById('currentApprovalCount').textContent = approvalCount;
                document.getElementById('currentRejectionCount').textContent = rejectionCount;
            }

            // Approve button handler
            document.getElementById('approveBtn').addEventListener('click', function () {
                approveModal.show();
            });

            // Reject button handler (from dropdown)
            document.getElementById('rejectBtn').addEventListener('click', function () {
                rejectModal.show();
            });

            // Confirm approve action
            document.getElementById('confirmApprove').addEventListener('click', async function () {
                const remarks = document.getElementById('approveRemarks').value;
                const adminToken = localStorage.getItem('adminToken');

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            remarks: remarks
                        })
                    });

                    console.log('Response status:', response.status, response.statusText);

                    // First, check if response is JSON
                    const contentType = response.headers.get('content-type');
                    let responseData;

                    if (contentType && contentType.includes('application/json')) {
                        responseData = await response.json();
                        console.log('Response JSON:', responseData);
                    } else {
                        // If not JSON, get the text response
                        const textResponse = await response.text();
                        console.log('Response text:', textResponse);
                        throw new Error(textResponse || 'Non-JSON response from server');
                    }

                    if (!response.ok) {
                        // Check for different error response formats
                        const errorMessage = responseData.error ||
                            responseData.message ||
                            JSON.stringify(responseData) ||
                            'Failed to approve request';
                        throw new Error(errorMessage);
                    }

                    showToast('Request approved successfully!', 'success');
                    approveModal.hide();

                    // Refresh everything
                    fetchRequestDetails();
                    loadMixedActivity(); // Refresh activity timeline
                    loadApprovalHistory(); // Refresh approval history
                    checkAdminRoleAndUpdateUI(); // Update UI to hide buttons

                } catch (error) {
                    console.error('Error approving request:', error);
                    showToast('Error: ' + error.message, 'error');
                }
            });

            // Confirm reject action
            document.getElementById('confirmReject').addEventListener('click', async function () {
                const remarks = document.getElementById('rejectRemarks').value;
                const adminToken = localStorage.getItem('adminToken');

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/reject`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            remarks: remarks
                        })
                    });

                    // First, check if response is JSON
                    const contentType = response.headers.get('content-type');
                    let responseData;

                    if (contentType && contentType.includes('application/json')) {
                        responseData = await response.json();
                    } else {
                        // If not JSON, get the text response
                        const textResponse = await response.text();
                        throw new Error(textResponse || 'Non-JSON response from server');
                    }

                    if (!response.ok) {
                        const errorMessage = responseData.error ||
                            responseData.message ||
                            JSON.stringify(responseData) ||
                            'Failed to reject request';
                        throw new Error(errorMessage);
                    }

                    showToast('Request rejected successfully!', 'success');
                    rejectModal.hide();

                    // Refresh everything
                    fetchRequestDetails();
                    loadMixedActivity(); // Refresh activity timeline
                    loadApprovalHistory(); // Refresh approval history
                    checkAdminRoleAndUpdateUI(); // Update UI to hide buttons

                } catch (error) {
                    console.error('Error rejecting request:', error);
                    showToast('Error: ' + error.message, 'error');
                }
            });

            fetchRequestDetails();
            // Initialize event modal listeners
            setTimeout(() => {
                setupEventListeners();
            }, 100);

            // Sticky Activity Timeline functionality
            const stickyTimelineBtn = document.getElementById('stickyTimelineBtn');
            const timelineModal = document.getElementById('timelineModal');
            const closeTimelineBtn = document.getElementById('closeTimelineBtn');
            const timelineContent = document.getElementById('timelineContent');
            const timelineComment = document.getElementById('timelineComment');
            const timelineSendBtn = document.getElementById('timelineSendBtn');
            const timelineFilter = document.getElementById('timelineFilter');
            const refreshTimelineBtn = document.getElementById('refreshTimelineBtn');
            let isTimelineLoaded = false;
            let currentTimelineFilter = 'all';


// Toggle timeline modal - Update this section
stickyTimelineBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    
    if (timelineModal.classList.contains('show')) {
        timelineModal.classList.remove('show');
    } else {
        timelineModal.classList.add('show');
        
        // Lazy load content if not loaded yet
        if (!isTimelineLoaded) {
            loadTimelineContent();
        }
    }
});
// Close timeline button
if (closeTimelineBtn) {
    closeTimelineBtn.addEventListener('click', function() {
        console.log('Close button clicked'); // Debug log
        timelineModal.classList.remove('show');
    });
}

// Also add a backup click handler for any element with class 'close-timeline'
document.querySelectorAll('.close-timeline').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        timelineModal.classList.remove('show');
    });
});


            // Close when clicking outside
            document.addEventListener('click', function (e) {
                if (!timelineModal.contains(e.target) && !stickyTimelineBtn.contains(e.target)) {
                    timelineModal.classList.remove('show');
                }
            });

            // Filter change
            timelineFilter.addEventListener('change', function () {
                currentTimelineFilter = this.value;
                loadTimelineContent();
            });


            // Refresh button
            refreshTimelineBtn.addEventListener('click', function () {
                isTimelineLoaded = false;
                loadTimelineContent();
                showToast('Timeline refreshed', 'success');
            });

            // Make modal draggable
            const modalHeader = document.getElementById('timelineModalHeader');
            let isDragging = false;
            let offsetX, offsetY;


            modalHeader.addEventListener('mousedown', function (e) {
                // Don't start dragging if clicking on close button, select, or any interactive element
                if (e.target.tagName === 'SELECT' ||
                    e.target.tagName === 'OPTION' ||
                    e.target.closest('select') ||
                    e.target.closest('.close-timeline') ||  // Add this line
                    e.target.closest('#refreshTimelineBtn') || // Add this line
                    e.target.closest('#timelineFilter')) { // Add this line
                    return;
                }
                isDragging = true;
                offsetX = e.clientX - timelineModal.offsetLeft;
                offsetY = e.clientY - timelineModal.offsetTop;
                modalHeader.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', function (e) {
                if (isDragging) {
                    e.preventDefault();
                    timelineModal.style.left = (e.clientX - offsetX) + 'px';
                    timelineModal.style.top = (e.clientY - offsetY) + 'px';
                    timelineModal.style.bottom = 'auto';
                    timelineModal.style.right = 'auto';
                }
            });
            document.addEventListener('mouseup', function () {
                isDragging = false;
                modalHeader.style.cursor = 'move';
            });

            // Load timeline content with filter
            async function loadTimelineContent() {
                timelineContent.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary mb-2" role="status"><span class="visually-hidden">Loading...</span></div><p class="small mb-0">Loading activity...</p></div>';


                try {
                    let activities = [];

                    // Load comments if filter is 'all' or 'comment'
                    if (currentTimelineFilter === 'all' || currentTimelineFilter === 'comment') {
                        const commentsResponse = await fetch(`/api/admin/requisition/${requestId}/comments`, {
                            headers: {
                                'Authorization': `Bearer ${adminToken}`,
                                'Accept': 'application/json'
                            }
                        });
                        const commentsResult = await commentsResponse.json();
                        const comments = commentsResult.comments || [];

                        comments.forEach(comment => {
                            activities.push({
                                type: 'comment',
                                data: comment,
                                timestamp: new Date(comment.created_at)
                            });
                        });
                    }

                    // Load fees if filter is 'all' or 'fee'
                    if (currentTimelineFilter === 'all' || currentTimelineFilter === 'fee') {
                        const feesResponse = await fetch(`/api/admin/requisition/${requestId}/fees`, {
                            headers: {
                                'Authorization': `Bearer ${adminToken}`,
                                'Accept': 'application/json'
                            }
                        });
                        const feesResult = await feesResponse.json();
                        const fees = feesResult || [];

                        fees.forEach(fee => {
                            activities.push({
                                type: 'fee',
                                data: fee,
                                timestamp: new Date(fee.created_at)
                            });
                        });
                    }

                    // Sort by timestamp (newest first)
                    activities.sort((a, b) => b.timestamp - a.timestamp);

                    if (activities.length === 0) {
                        timelineContent.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fa-2x mb-2"></i><p class="small mb-0">No activity yet</p></div>';
                    } else {
                        timelineContent.innerHTML = activities.map(activity => {
                            if (activity.type === 'comment') {
                                return generateTimelineCommentHTML(activity.data);
                            } else {
                                return generateTimelineFeeHTML(activity.data);
                            }
                        }).join('');
                    }

                    isTimelineLoaded = true;

                } catch (error) {
                    console.error('Error loading timeline:', error);
                    timelineContent.innerHTML = '<div class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p class="small mb-0">Failed to load activity</p></div>';
                }
            }


            // Generate comment HTML for timeline
            function generateTimelineCommentHTML(comment) {
                const timeAgo = formatTimeAgo(comment.created_at);
                return `
                            <div class="timeline-item mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-2 flex-shrink-0">
                                        ${comment.admin.photo_url ?
                        `<img src="${comment.admin.photo_url}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">` :
                        `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                ${comment.admin.first_name.charAt(0)}${comment.admin.last_name.charAt(0)}
                                            </div>`
                    }
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="small">${comment.admin.first_name} ${comment.admin.last_name}</strong>
                                            <small class="text-muted">${timeAgo}</small>
                                        </div>
                                        <div class="bg-light p-2 rounded small">
                                            ${escapeHtml(comment.comment)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
            }


            // Generate fee HTML for timeline
            function generateTimelineFeeHTML(fee) {
                const amount = parseFloat(fee.type === 'discount' ? fee.discount_amount : fee.fee_amount);
                const typeName = fee.type === 'discount' ? 'Discount' : 'Additional fee';
                const adminName = fee.added_by?.name || 'Admin';
                const timeAgo = formatTimeAgo(fee.created_at);

                let amountDisplay = '';
                if (fee.type === 'discount') {
                    if (fee.discount_type === 'Percentage') {
                        amountDisplay = `${parseFloat(amount)}%`;
                    } else {
                        amountDisplay = `-${formatMoney(amount)}`;
                    }
                } else {
                    amountDisplay = `${formatMoney(amount)}`;
                }

                return `
                            <div class="timeline-item mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-2 flex-shrink-0">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px; background-color: #d4edda; color: #28a745;">
                                            <i class="fas fa-money-bill" style="font-size: 0.9rem;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="small">${adminName}</strong>
                                            <small class="text-muted">${timeAgo}</small>
                                        </div>
                                        <div class="bg-light p-2 rounded small">
                                            added ${typeName} - ${fee.label}: ${amountDisplay}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
            }



            // Send comment from timeline
            timelineSendBtn.addEventListener('click', async function () {
                const commentText = timelineComment.value.trim();

                if (!commentText) {
                    showToast('Please enter a comment', 'error');
                    return;
                }

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/comment`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${adminToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            comment: commentText
                        })
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Failed to add comment');
                    }

                    if (result.success) {
                        timelineComment.value = '';
                        timelineComment.style.height = 'auto';

                        // Reload timeline content
                        isTimelineLoaded = false;
                        loadTimelineContent();

                        showToast('Comment added successfully', 'success');
                    }

                } catch (error) {
                    console.error('Error adding comment:', error);
                    showToast('Failed to add comment: ' + error.message, 'error');
                }
            });

            // Allow Enter to send
            timelineComment.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    timelineSendBtn.click();
                }
            });

            // Auto-resize textarea
            timelineComment.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });


        });
    </script>
@endsection