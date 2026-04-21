@extends('layouts.admin')
@section('title', 'Review Request')
@section('content')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/request-view.css') }}">

    <style>
        /* Ensure all cards inside main content inherit admin.blade.php card styles */
        #contentState .card,
        .card {
            border-radius: 0 !important;
            border: 1px solid #dee2e6 !important;
            background-color: #ffffff !important;
            box-shadow: none !important;
        }

        /* Keep card-header styling consistent */
        #contentState .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        /* Make status summary card match height of action panel card */
        .col-md-9 .card,
        .col-md-3 .card {
            height: 100%;
        }

        /* On mobile, stack Action Panel first, then Status Summary */
        @media (max-width: 767.98px) {
            .row.g-2.mb-2 {
                display: flex;
                flex-direction: column-reverse;
            }

            .row.g-2.mb-2 .col-md-9,
            .row.g-2.mb-2 .col-md-3 {
                width: 100%;
            }
        }

        /* Normalize all card headers to match Currently Reviewing style */
        .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #dee2e6 !important;
            padding: 0.75rem 1rem;
        }

        .card-header h5,
        .card-header .card-title,
        .card-header h6 {
            font-weight: 700 !important;
            color: #135ba3 !important;
            font-size: 1rem !important;
            margin-bottom: 0;
        }

        /* If some headers use text-primary class instead of h5 */
        .card-header .text-primary {
            font-weight: 700 !important;
            color: #135ba3 !important;
        }

        /* Sticky Activity Timeline Button - Always visible */
        .sticky-timeline-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: auto !important;
            height: auto !important;
            border-radius: 30px !important;
            background-color: #004080;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 999;
            transition: all 0.3s ease;
            opacity: 1;
            visibility: visible;
            padding: 10px 20px !important;
            gap: 8px;
        }

        .sticky-timeline-btn i {
            font-size: 1.1rem;
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
            border: 1px solid #e0e0e0;
            flex-direction: column;
        }

        .timeline-modal.show {
            display: flex;
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



        /* Pastel theme for all action buttons */
        #approveBtn,
        #confirmApprove,
        .btn-success:not(.btn-outline):not(.badge) {
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
        }

        #rejectBtn,
        #confirmReject,
        .btn-danger:not(.badge) {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
        }

        #finalizeBtn,
        #confirmFinalize,
        #confirmMarkScheduled,
        .btn-primary:not(.btn-outline):not(.badge) {
            background-color: #d1ecf1 !important;
            color: #0c5460 !important;
            border: 1px solid #bee5eb !important;
        }

        #markScheduledBtn,
        #markOngoingBtn {
            background-color: #d1ecf1 !important;
            color: #0c5460 !important;
            border: 1px solid #bee5eb !important;
        }

        #closeFormBtn,
        #closeForm {
            background-color: #e2e3e5 !important;
            color: #383d41 !important;
            border: 1px solid #d6d8db !important;
        }

        /* Keep sharp edges */
        #approveBtn,
        #rejectBtn,
        #finalizeBtn,
        #confirmApprove,
        #confirmReject,
        #confirmFinalize,
        #confirmMarkScheduled,
        #markScheduledBtn,
        #markOngoingBtn,
        #closeFormBtn,
        .btn-primary,
        .btn-success,
        .btn-danger,
        .btn-warning {
            border-radius: 0 !important;
        }

        /* Hover effects for pastel buttons */
        #approveBtn:hover,
        .btn-success:hover {
            background-color: #c3e6cb !important;
            color: #155724 !important;
        }

        #rejectBtn:hover,
        .btn-danger:hover {
            background-color: #f5c6cb !important;
            color: #721c24 !important;
        }

        #finalizeBtn:hover,
        .btn-primary:hover {
            background-color: #bee5eb !important;
            color: #0c5460 !important;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 45px !important;
            height: 45px !important;
            border-radius: 50% !important;
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

        .back-to-top i {
            font-size: 1.2rem;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: #0f4c8a !important;
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

        /* Updated status pills styling */
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
            color: #003366 !important;
        }

        .hover-pointer:hover {
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.05);
        }

        /* Status pills container */
        .status-pills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            width: 100%;
        }

        /* Individual pills */
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
            color: #292929ff;
            font-family: "Courier New", monospace;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            line-height: 1.2;
        }

        /* Mobile-first responsive styles */
        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }

            .desktop-only {
                display: none;
            }

            .table {
                display: none;
            }
        }

        .mobile-only {
            display: none;
        }

        .desktop-only {
            display: block;
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
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="text-muted small fw-semibold mb-2"
                                                style="letter-spacing: 0.3px; font-size: 0.75rem;">CONTACT INFORMATION</div>
                                            <div id="formDetails" class="bg-light p-2 rounded" style="font-size: 0.85rem;">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small fw-semibold mb-2"
                                                style="letter-spacing: 0.3px; font-size: 0.75rem;">DOCUMENTS</div>
                                            <div class="documents-vertical">
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="formalLetterIcon" class="fas fa-file-alt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Requisition Letter</div>
                                                    </div>
                                                    <button type="button" id="formalLetterBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="facilityLayoutIcon"
                                                        class="fas fa-map-marked-alt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Venue Layout</div>
                                                    </div>
                                                    <button type="button" id="facilityLayoutBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="proofOfPaymentIcon" class="fas fa-receipt fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Official Receipt</div>
                                                    </div>
                                                    <button type="button" id="proofOfPaymentBtn"
                                                        class="btn btn-sm btn-document-null ms-2 document-view-btn"
                                                        style="min-width: 60px;">View</button>
                                                </div>
                                                <div
                                                    class="document-mini-item d-flex align-items-center p-2 border rounded mb-2">
                                                    <i id="officialReceiptIcon"
                                                        class="fas fa-file-invoice-dollar fa-lg text-muted me-3"
                                                        style="width: 24px;"></i>
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-bold">Use of Hall Permit</div>
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

                        <!-- Action Panel -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Actions & Approvals</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <h6 class="text-muted small  fw-semibold mb-2"
                                            style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">ACTIONS
                                        </h6>
                                        <button class="btn btn-success w-100 mb-2" id="approveBtn"
                                            style="padding: 0.5rem 1rem;">
                                            <i class="bi bi-hand-thumbs-up me-2"></i> Approve
                                        </button>
                                        <button class="btn btn-danger w-100 mb-2" id="rejectBtn"
                                            style="padding: 0.5rem 1rem;">
                                            <i class="bi bi-hand-thumbs-down me-2"></i> Reject
                                        </button>
                                        <button class="btn btn-primary w-100 mb-2" id="finalizeBtn"
                                            style="display: none; padding: 0.5rem 1rem;">
                                            <i class="bi bi-check-circle me-2"></i> Finalize
                                        </button>
                                    </div>
                                    <div>
                                        <h6 class="text-muted small  fw-semibold mb-2"
                                            style="font-size: 0.75rem; letter-spacing: 0.5px; font-weight: 600;">APPROVALS
                                        </h6>
                                        <div class="row g-1">
                                            <div class="col-12">
                                                <div class="status-pill p-2 bg-white border border-success d-flex align-items-center justify-content-between hover-pointer"
                                                    role="button" data-bs-toggle="modal" data-bs-target="#approvalsModal"
                                                    style="height: 50px; width: 100%; cursor: pointer; background-color: #d4edda !important; border-color: #c3e6cb !important;">
                                                    <div class="d-flex align-items-center" style="padding-left: 12px;">
                                                        <i class="fa fa-thumbs-up me-2"
                                                            style="font-size: 1.1rem; color: #155724;"></i>
                                                        <span style="color: #155724;">View approvals</span>
                                                    </div>
                                                    <span class="fw-bold badge" id="approvalsCount"
                                                        style="font-size: 0.9rem; background-color: #155724 !important; border-radius: 0 !important; padding: 4px 10px;">0</span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="status-pill p-2 bg-white border border-danger d-flex align-items-center justify-content-between hover-pointer"
                                                    role="button" data-bs-toggle="modal" data-bs-target="#rejectionsModal"
                                                    style="height: 50px; width: 100%; cursor: pointer; background-color: #f8d7da !important; border-color: #f5c6cb !important;">
                                                    <div class="d-flex align-items-center" style="padding-left: 12px;">
                                                        <i class="fa fa-thumbs-down me-2"
                                                            style="font-size: 1.1rem; color: #721c24;"></i>
                                                        <span style="color: #721c24;">View rejections</span>
                                                    </div>
                                                    <span class="fw-bold badge" id="rejectionsCount"
                                                        style="font-size: 0.9rem; background-color: #721c24 !important; border-radius: 0 !important; padding: 4px 10px;">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="card h-100">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Booking Details</h5>
                                </div>
                                <div class="card-body">
                                    <div id="eventDetails" class="mb-4"></div>
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
                                    <div id="statusModalContent"></div>
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
                                            <div class="col-md-6">
                                                <label for="feeLabel" class="form-label">Fee Label</label>
                                                <input type="text" id="feeLabel" class="form-control"
                                                    placeholder="Fee Label" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="accountNum" class="form-label">Account Number (Optional)</label>
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

                    <!-- Fee Breakdown - Split into two cards -->
                    <div class="row g-2 mt-1 align-items-stretch">
                        <!-- Base Fees Card -->
                        <div class="col-md-6 d-flex">
                            <div class="card flex-fill d-flex flex-column">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Base Fees</h5>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="waiveAllSwitch">
                                        <label class="form-check-label" for="waiveAllSwitch">Waive All Fees</label>
                                    </div>
                                </div>
                                <div class="card-body flex-fill">
                                    <div id="baseFeesContainer">
                                        <div id="facilitiesFees"></div>
                                        <div id="equipmentFees"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Miscellaneous Fees Card -->
                        <div class="col-md-6 d-flex">
                            <div class="card flex-fill d-flex flex-column">
                                <div
                                    class="card-header bg-white text-dark d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Miscellaneous Fees</h5>
                                    <button id="addFeeBtn" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-plus"></i> Add Fee/Discount
                                    </button>
                                </div>
                                <div class="card-body flex-fill">
                                    <div id="additionalFeesContainer"></div>
                                </div>
                                <div
                                    class="card-footer bg-white d-flex justify-content-between align-items-center fw-bold p-3 border-top">
                                    <span>Total Approved Fee:</span>
                                    <span id="feeBreakdownTotal">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modals -->
                <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Approval</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to approve this request? This action cannot be undone.</p>
                                <p class="text-muted small">You will not be able to take further actions on this form after
                                    approval.</p>
                                <div class="mb-3">
                                    <label for="approveRemarks" class="form-label">Remarks (Optional)</label>
                                    <textarea class="form-control" id="approveRemarks" rows="3"
                                        placeholder="Add any remarks here..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" id="confirmApprove">Confirm Approval</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Rejection</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to reject this request? This action cannot be undone.</p>
                                <p class="text-muted small">You will not be able to take further actions on this form after
                                    rejection.</p>
                                <div class="mb-3">
                                    <label for="rejectRemarks" class="form-label">Remarks (Optional)</label>
                                    <textarea class="form-control" id="rejectRemarks" rows="3"
                                        placeholder="Add any remarks here..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmReject">Confirm Rejection</button>
                            </div>
                        </div>
                    </div>
                </div>

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
                                    <p class="text-muted small">This will mark the form as completed. This action cannot be
                                        undone.</p>
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

                <!-- Mark Scheduled Modal -->
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
                    <i class="fas fa-comment me-2"></i> Activity Timeline
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

                <!-- Approvals/Rejections Modals -->
                <div class="modal fade" id="approvalsModal" tabindex="-1" aria-labelledby="approvalsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="approvalsModalLabel">Approvals</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body"></div>
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
                            <div class="modal-body"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

@endsection

@section('scripts')
    <script>
        // ============================================================================
        // =========================== UTILITY FUNCTIONS ==============================
        // ============================================================================

        function formatMoney(amount) {
            let num = parseFloat(amount);
            if (isNaN(num)) return '₱0.00';
            return '₱' + num.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTimeAgo(timestamp) {
            const now = new Date();
            const commentTime = new Date(timestamp);
            const diffInSeconds = Math.floor((now - commentTime) / 1000);

            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minute(s) ago`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hour(s) ago`;
            if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} day(s) ago`;
            return commentTime.toLocaleDateString();
        }

        function showToast(message, type = 'success', duration = 3000) {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
            toast.style.cssText = 'z-index:1100;bottom:0;left:0;margin:1rem;opacity:0;transform:translateY(20px);transition:transform 0.4s ease, opacity 0.4s ease';
            toast.setAttribute('role', 'alert');

            const bgColor = type === 'success' ? '#004183ff' : '#dc3545';
            toast.style.backgroundColor = bgColor;
            toast.style.color = '#fff';
            toast.style.minWidth = '250px';
            toast.style.borderRadius = '0.3rem';

            toast.innerHTML = `
                                                                <div class="d-flex align-items-center px-3 py-1">
                                                                    <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
                                                                    <div class="toast-body flex-grow-1" style="padding:0.25rem 0;">${message}</div>
                                                                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button>
                                                                </div>
                                                                <div class="loading-bar" style="height:3px;background:rgba(255,255,255,0.7);width:100%;transition:width ${duration}ms linear;"></div>
                                                            `;

            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { autohide: false });
            bsToast.show();

            requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
            const loadingBar = toast.querySelector('.loading-bar');
            requestAnimationFrame(() => { loadingBar.style.width = '0%'; });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => { bsToast.hide(); toast.remove(); }, 400);
            }, duration);
        }

        function generateApprovalHistoryHTML(history) {
            if (!history || history.length === 0) {
                return '<div class="text-center text-muted py-4">No records found</div>';
            }
            return history.map(item => `
                                                                <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                                                    <div class="me-3 flex-shrink-0">
                                                                        ${item.admin_photo ?
                    `<img src="${item.admin_photo}" class="rounded-circle" width="45" height="45" style="object-fit: cover;">` :
                    `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 45px; height: 45px;">
                                                                                ${item.admin_name.split(' ').map(n => n.charAt(0)).join('')}
                                                                            </div>`
                }
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <strong class="d-block">${escapeHtml(item.admin_name)}</strong>
                                                                                <small class="text-muted">
                                                                                    <i class="fa ${item.action_icon} ${item.action_class} me-1"></i>
                                                                                    ${item.action} this request
                                                                                </small>
                                                                                ${item.remarks ? `<div class="mt-1 small text-muted">"${escapeHtml(item.remarks)}"</div>` : ''}
                                                                            </div>
                                                                            <small class="text-muted text-end">${item.formatted_date}</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            `).join('');
        }

        async function refreshStatusAndApprovals() {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        const data = result.data;

                        // Update status badge
                        if (data.form_details.status) {
                            const statusBadge = document.getElementById('statusBadge');
                            statusBadge.textContent = data.form_details.status.name;
                            statusBadge.style.backgroundColor = data.form_details.status.color;

                            // Update button visibility based on new status
                            updateAddFeeButtonVisibility(data.form_details.status.id);
                            updateWaiverCheckboxesStatus(data.form_details.status.id);
                        }

                        // Update approval counts
                        renderApprovalCounts(data.approval_info);

                        // Update action buttons based on new status
                        await checkAdminRoleAndUpdateUI(data);

                        // Update current data
                        currentRequestData = data;
                        currentFees = data.requisition_fees || [];
                        currentComments = data.comments || [];

                        return data;
                    }
                }
            } catch (error) {
                console.error('Error refreshing status:', error);
            }
            return null;
        }

        function updateAddFeeButtonVisibility(statusId) {
            const addFeeBtn = document.getElementById('addFeeBtn');
            if (!addFeeBtn) return;

            // Status IDs that should disable the Add Fee button
            // 3 = Awaiting Payment, 4 = Scheduled, 5 = Ongoing, 6 = Late, 7 = Completed, 8 = Cancelled, 9 = Closed
            const disabledStatuses = [3, 4, 5, 6, 7, 8, 9];

            if (disabledStatuses.includes(statusId)) {
                addFeeBtn.disabled = true;
                addFeeBtn.title = 'Fees cannot be modified after finalization';
                addFeeBtn.style.opacity = '0.6';
                addFeeBtn.style.cursor = 'not-allowed';
            } else {
                addFeeBtn.disabled = false;
                addFeeBtn.title = 'Add fee or discount';
                addFeeBtn.style.opacity = '1';
                addFeeBtn.style.cursor = 'pointer';
            }
        }

        function updateWaiverCheckboxesStatus(statusId) {
            const disabledStatuses = [3, 4, 5, 6, 7, 8, 9];
            const isDisabled = disabledStatuses.includes(statusId);

            document.querySelectorAll('.waiver-checkbox').forEach(checkbox => {
                if (isDisabled) {
                    checkbox.disabled = true;
                } else {
                    checkbox.disabled = false;
                }
            });
        }


        // ============================================================================
        // ======================== SINGLE OPTIMIZED API CALL =========================
        // ============================================================================

        let cachedAdminRole = null;
        let currentComments = [];
        let currentFees = [];
        let currentRequestData = null;
        let selectedStatus = '';

        async function loadRequestViewData() {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');

            if (!adminToken) {
                showToast('Authentication error. Please login again.', 'error');
                return;
            }

            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('contentState').style.display = 'none';

            try {
                const startTime = performance.now();

                const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Failed to load request data');
                }

                const data = result.data;
                currentRequestData = data;
                const loadTime = Math.round(performance.now() - startTime);
                console.log(`✅ Request view data loaded in ${loadTime}ms`);

                currentComments = data.comments || [];
                currentFees = data.requisition_fees || [];

                renderAllSections(data);

                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('contentState').style.display = 'block';

                markNotificationAsRead(requestId);
                await checkAdminRoleAndUpdateUI(data);

            } catch (error) {
                console.error('Error loading request data:', error);
                showToast('Failed to load request details: ' + error.message, 'error');
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('contentState').innerHTML = `
                                                                    <div class="alert alert-danger">
                                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                        Failed to load request details. Please try refreshing the page.
                                                                        <br><small>Error: ${escapeHtml(error.message)}</small>
                                                                    </div>
                                                                `;
                document.getElementById('contentState').style.display = 'block';
            }
        }

        // ============================================================================
        // ========================= RENDER FUNCTIONS =================================
        // ============================================================================

        function renderAllSections(data) {
            document.getElementById('requestIdTitle').textContent = 'RID #' + String(data.request_id).padStart(4, '0');

            if (data.form_details.status) {
                const statusBadge = document.getElementById('statusBadge');
                statusBadge.textContent = data.form_details.status.name;
                statusBadge.style.backgroundColor = data.form_details.status.color;

                // Update button visibility based on status
                updateAddFeeButtonVisibility(data.form_details.status.id);
                updateWaiverCheckboxesStatus(data.form_details.status.id);
            }

            renderContactInfo(data.user_details);
            renderEventDetails(data);
            renderRequestedItems(data.requested_items);
            renderApprovalCounts(data.approval_info);
            updateDocumentIcons(data.documents);
            renderFeeBreakdown(data);
            renderApprovalModals(data.approval_history || []);

            if (document.getElementById('timelineModal')?.classList.contains('show')) {
                updateTimelineContent(currentComments, currentFees);
            }
        }

        function renderContactInfo(userDetails) {
            const container = document.getElementById('formDetails');
            if (!container) return;

            container.innerHTML = `
                                                                <div class="d-flex justify-content-between py-1 border-bottom">
                                                                    <span class="text-muted">Requester:</span>
                                                                    <span class="fw-medium">${escapeHtml(userDetails.first_name)} ${escapeHtml(userDetails.last_name)}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between py-1 border-bottom">
                                                                    <span class="text-muted">Organization/Department:</span>
                                                                    <span class="fw-medium">${escapeHtml(userDetails.organization_name || 'N/A')}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between py-1 border-bottom">
                                                                    <span class="text-muted">User Type:</span>
                                                                    <span class="fw-medium">${escapeHtml(userDetails.user_type)}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between py-1 border-bottom">
                                                                    <span class="text-muted">Email:</span>
                                                                    <span class="fw-medium" style="word-break: break-word;">${escapeHtml(userDetails.email)}</span>
                                                                </div>
                                                                 <div class="d-flex justify-content-between py-1 border-bottom">
                                                                    <span class="text-muted">School ID:</span>
                                                                    <span class="fw-medium">${escapeHtml(userDetails.school_id || 'N/A')}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between py-1">
                                                                    <span class="text-muted">Contact no.:</span>
                                                                    <span class="fw-medium">${escapeHtml(userDetails.contact_number || 'N/A')}</span>
                                                                </div>
                                                            `;
        }

        function renderEventDetails(data) {
            const container = document.getElementById('eventDetails');
            if (!container) return;

            const schedule = data.schedule;
            const formDetails = data.form_details;
            const documents = data.documents;

            container.innerHTML = `
                                                                <div class="row g-3">
                                                                    <div class="col-sm-6">
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Endorser</div>
                                                                            <div>${escapeHtml(documents.endorser || 'N/A')}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Rental Purpose</div>
                                                                            <div>${escapeHtml(formDetails.purpose || 'N/A')}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Number of Tables</div>
                                                                            <div>${formDetails.num_tables || 0}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Start Schedule</div>
                                                                            <div>${schedule.formatted_start || 'N/A'}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Date Endorsed</div>
                                                                            <div>${formatDateEndorsed(documents.date_endorsed) || 'N/A'}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Participants</div>
                                                                            <div>${formDetails.num_participants || 0}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">Number of Chairs</div>
                                                                            <div>${formDetails.num_chairs || 0}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="fw-bold text-primary">End Schedule</div>
                                                                            <div>${schedule.formatted_end || 'N/A'}</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <div class="fw-bold text-primary">Additional Requests</div>
                                                                        <div>${escapeHtml(formDetails.additional_requests || 'No additional requests.')}</div>
                                                                    </div>
                                                                </div>
                                                            `;
        }

        function renderRequestedItems(requestedItems) {
            const container = document.getElementById('requestedItems');
            if (!container) return;

            let html = '<div class="requested-items-list">';

            if (requestedItems.facilities && requestedItems.facilities.length > 0) {
                html += `
                                                                    <div class="mb-3">
                                                                        <h6 class="fw-bold mb-2" style="font-size:0.9rem;">Facilities:</h6>
                                                                        ${requestedItems.facilities.map(f => `
                                                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                                                <span class="item-name">${escapeHtml(f.name)}</span>
                                                                                <span class="item-price fw-bold">${formatMoney(f.fee)}${f.rate_type === 'Per Hour' ? '/hour' : '/event'}</span>
                                                                            </div>
                                                                        `).join('')}
                                                                    </div>
                                                                `;
            }

            if (requestedItems.equipment && requestedItems.equipment.length > 0) {
                html += `
                                                                    <div>
                                                                        <h6 class="fw-bold mb-2" style="font-size:0.9rem;">Equipment:</h6>
                                                                        ${requestedItems.equipment.map(e => `
                                                                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                                                                <span class="item-name">${escapeHtml(e.name)} × ${e.quantity || 1}</span>
                                                                                <span class="item-price fw-bold">${formatMoney(e.fee)}${e.rate_type === 'Per Hour' ? '/hour' : '/event'}</span>
                                                                            </div>
                                                                        `).join('')}
                                                                    </div>
                                                                `;
            }

            html += '</div>';
            container.innerHTML = html || '<p class="text-muted small">No items requested</p>';
        }

        function renderApprovalCounts(approvalInfo) {
            const approvalsElem = document.getElementById('approvalsCount');
            const rejectionsElem = document.getElementById('rejectionsCount');
            const currentApprovalSpan = document.getElementById('currentApprovalCount');
            const currentRejectionSpan = document.getElementById('currentRejectionCount');

            if (approvalsElem) approvalsElem.textContent = approvalInfo.approval_count || 0;
            if (rejectionsElem) rejectionsElem.textContent = approvalInfo.rejection_count || 0;
            if (currentApprovalSpan) currentApprovalSpan.textContent = approvalInfo.approval_count || 0;
            if (currentRejectionSpan) currentRejectionSpan.textContent = approvalInfo.rejection_count || 0;
        }

        function renderFeeBreakdown(data) {
            const requestedItems = data.requested_items;
            const durationHours = data.duration_hours;

            renderBaseFees(requestedItems, durationHours);
            renderAdditionalFees(data.requisition_fees || []);

            // Use the API's calculated approved_fee
            const feeTotal = document.getElementById('feeBreakdownTotal');
            if (feeTotal && data.fees && data.fees.approved_fee !== undefined) {
                feeTotal.textContent = formatMoney(data.fees.approved_fee);
            }
        }
        function renderBaseFees(requestedItems, durationHours) {
            const facilitiesContainer = document.getElementById('facilitiesFees');
            const equipmentContainer = document.getElementById('equipmentFees');

            if (!facilitiesContainer || !equipmentContainer) return;

            if (requestedItems.facilities && requestedItems.facilities.length > 0) {
                facilitiesContainer.innerHTML = requestedItems.facilities.map(facility => {
                    let itemTotal = facility.rate_type === 'Per Hour' ? facility.fee * durationHours : facility.fee;
                    let rateDesc = facility.rate_type === 'Per Hour'
                        ? `${formatMoney(facility.fee)}/hr × ${durationHours.toFixed(1)} hrs`
                        : `${formatMoney(facility.fee)}/event`;

                    return `
                                                                        <div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${facility.is_waived ? 'waived' : ''}">
                                                                            <div class="d-flex align-items-center">
                                                                                <div class="form-check me-2">
                                                                                    <input class="form-check-input waiver-checkbox" type="checkbox" 
                                                                                        data-type="facility" 
                                                                                        data-id="${facility.requested_facility_id}"
                                                                                        ${facility.is_waived ? 'checked' : ''}>
                                                                                </div>
                                                                                <span class="item-name">${escapeHtml(facility.name)}</span>
                                                                            </div>
                                                                            <div class="text-end">
                                                                                <small>${rateDesc}</small>
                                                                                <div><strong>${formatMoney(itemTotal)}</strong></div>
                                                                            </div>
                                                                        </div>
                                                                    `;
                }).join('');
            } else {
                facilitiesContainer.innerHTML = '<div class="text-muted small">No facilities requested</div>';
            }

            if (requestedItems.equipment && requestedItems.equipment.length > 0) {
                equipmentContainer.innerHTML = requestedItems.equipment.map(equipment => {
                    let itemTotal = equipment.rate_type === 'Per Hour'
                        ? (equipment.fee * durationHours) * (equipment.quantity || 1)
                        : equipment.fee * (equipment.quantity || 1);
                    let rateDesc = equipment.rate_type === 'Per Hour'
                        ? `${formatMoney(equipment.fee)}/hr × ${durationHours.toFixed(1)} hrs × ${equipment.quantity || 1}`
                        : `${formatMoney(equipment.fee)}/event × ${equipment.quantity || 1}`;

                    return `
                                                                        <div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded ${equipment.is_waived ? 'waived' : ''}">
                                                                            <div class="d-flex align-items-center">
                                                                                <div class="form-check me-2">
                                                                                    <input class="form-check-input waiver-checkbox" type="checkbox" 
                                                                                        data-type="equipment" 
                                                                                        data-id="${equipment.requested_equipment_id}"
                                                                                        ${equipment.is_waived ? 'checked' : ''}>
                                                                                </div>
                                                                                <span class="item-name">
                                                                                    ${escapeHtml(equipment.name)} ${equipment.quantity > 1 ? `(×${equipment.quantity})` : ''}
                                                                                </span>
                                                                            </div>
                                                                            <div class="text-end">
                                                                                <small>${rateDesc}</small>
                                                                                <div><strong>${formatMoney(itemTotal)}</strong></div>
                                                                            </div>
                                                                        </div>
                                                                    `;
                }).join('');
            } else {
                equipmentContainer.innerHTML = '<div class="text-muted small">No equipment requested</div>';
            }

            attachWaiverHandlers();
        }

        function renderAdditionalFees(requisitionFees) {
            const container = document.getElementById('additionalFeesContainer');
            if (!container) return;

            if (requisitionFees && requisitionFees.length > 0) {
                container.innerHTML = requisitionFees.map(fee => {
                    let amountText = '';
                    let isDiscount = false;

                    if (fee.type === 'fee') {
                        amountText = formatMoney(fee.fee_amount);
                    } else if (fee.type === 'discount') {
                        isDiscount = true;
                        if (fee.discount_type === 'Percentage') {
                            amountText = `-${fee.discount_amount}%`;
                        } else {
                            amountText = `-${formatMoney(fee.discount_amount)}`;
                        }
                    }

                    let labelHtml = escapeHtml(fee.label);
                    if (fee.account_num) {
                        labelHtml = `${escapeHtml(fee.label)} <span class="text-muted small">(${escapeHtml(fee.account_num)})</span>`;
                    }

                    return `
                                                                        <div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded">
                                                                            <div>
                                                                                <span class="item-name ${isDiscount ? 'text-danger' : ''}">${labelHtml}</span>
                                                                            </div>
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <span class="item-price fw-bold ${isDiscount ? 'text-danger' : ''}">${amountText}</span>
                                                                                <button class="btn btn-sm btn-danger delete-fee-btn" data-fee-id="${fee.fee_id}" data-fee-type="${fee.type}">
                                                                                    <i class="fa fa-times"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    `;
                }).join('');

                container.querySelectorAll('.delete-fee-btn').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const feeId = e.currentTarget.dataset.feeId;
                        if (feeId) await deleteFee(feeId);
                    });
                });
            } else {
                container.innerHTML = `
                                            <div class="text-center text-muted" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px;">
                                                <i class="fa fa-coins fa-3x d-block mb-3 opacity-50"></i>
                                                <p class="mb-0">No additional fees or discounts</p>
                                            </div>
                                        `;
            }
        }

        function renderApprovalModals(history) {
            const approvals = history.filter(item => item.action === 'approved');
            const rejections = history.filter(item => item.action === 'rejected');

            const approvalsModalBody = document.querySelector('#approvalsModal .modal-body');
            const rejectionsModalBody = document.querySelector('#rejectionsModal .modal-body');

            if (approvalsModalBody) {
                approvalsModalBody.innerHTML = generateApprovalHistoryHTML(approvals);
            }
            if (rejectionsModalBody) {
                rejectionsModalBody.innerHTML = generateApprovalHistoryHTML(rejections);
            }
        }

        function updateTimelineContent(comments, fees) {
            const container = document.getElementById('timelineContent');
            if (!container) return;

            const allActivities = [];

            comments.forEach(comment => {
                allActivities.push({
                    type: 'comment',
                    data: comment,
                    timestamp: new Date(comment.created_at)
                });
            });

            fees.forEach(fee => {
                allActivities.push({
                    type: 'fee',
                    data: fee,
                    timestamp: new Date(fee.created_at)
                });
            });

            allActivities.sort((a, b) => b.timestamp - a.timestamp);

            if (allActivities.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fa-2x mb-2"></i><p class="small mb-0">No activity yet</p></div>';
                return;
            }

            container.innerHTML = allActivities.map(activity => {
                if (activity.type === 'comment') {
                    const comment = activity.data;
                    const admin = comment.admin || {};
                    return `
                                                                        <div class="timeline-item mb-3">
                                                                            <div class="d-flex align-items-start">
                                                                                <div class="me-2 flex-shrink-0">
                                                                                    ${admin.photo_url ?
                            `<img src="${admin.photo_url}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">` :
                            `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                                                            ${(admin.first_name?.charAt(0) || 'A')}${(admin.last_name?.charAt(0) || 'D')}
                                                                                        </div>`
                        }
                                                                                </div>
                                                                                <div class="flex-grow-1">
                                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                        <strong class="small">${escapeHtml(admin.first_name || 'Admin')} ${escapeHtml(admin.last_name || '')}</strong>
                                                                                        <small class="text-muted">${comment.formatted_date || formatTimeAgo(comment.created_at)}</small>
                                                                                    </div>
                                                                                    <div class="bg-light p-2 rounded small">${escapeHtml(comment.comment)}</div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    `;
                } else {
                    const fee = activity.data;
                    const amountDisplay = fee.type === 'discount'
                        ? (fee.discount_type === 'Percentage' ? `-${fee.discount_amount}%` : `-${formatMoney(fee.discount_amount)}`)
                        : formatMoney(fee.fee_amount);
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
                                                                                        <strong class="small">${escapeHtml(fee.added_by?.name || 'Admin')}</strong>
                                                                                        <small class="text-muted">${fee.formatted_date || formatTimeAgo(fee.created_at)}</small>
                                                                                    </div>
                                                                                    <div class="bg-light p-2 rounded small">
                                                                                        added ${fee.type === 'discount' ? 'discount' : 'fee'} - ${escapeHtml(fee.label)}: ${amountDisplay}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    `;
                }
            }).join('');
        }

        function updateDocumentIcons(documents) {
            const updateButton = (buttonId, iconId, hasDocument, documentUrl, title) => {
                const button = document.getElementById(buttonId);
                const icon = document.getElementById(iconId);

                if (button && icon) {
                    if (hasDocument && documentUrl) {
                        button.classList.remove('btn-document-null');
                        button.classList.add('btn-primary');
                        button.disabled = false;
                        button.setAttribute('data-document-url', documentUrl);
                        button.setAttribute('data-document-title', title);
                        button.setAttribute('data-bs-toggle', 'modal');
                        button.setAttribute('data-bs-target', '#documentModal');
                        icon.classList.remove('text-muted');
                        icon.classList.add('text-primary');
                    } else {
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-document-null');
                        button.disabled = true;
                        button.removeAttribute('data-document-url');
                        button.removeAttribute('data-bs-toggle');
                        button.removeAttribute('data-bs-target');
                        icon.classList.add('text-muted');
                        icon.classList.remove('text-primary');
                    }
                }
            };

            updateButton('formalLetterBtn', 'formalLetterIcon', documents.formal_letter?.url, documents.formal_letter?.url, 'Formal Letter');
            updateButton('facilityLayoutBtn', 'facilityLayoutIcon', documents.facility_layout?.url, documents.facility_layout?.url, 'Facility Layout');
            updateButton('proofOfPaymentBtn', 'proofOfPaymentIcon', documents.proof_of_payment?.url, documents.proof_of_payment?.url, 'Proof of Payment');

            const requestId = window.location.pathname.split('/').pop();
            const hasOfficialReceipt = documents.official_receipt?.url || documents.official_receipt?.number;
            const receiptUrl = documents.official_receipt?.url || (documents.official_receipt?.number ? `/official-receipt/${requestId}` : null);
            updateButton('officialReceiptBtn', 'officialReceiptIcon', hasOfficialReceipt, receiptUrl, 'Official Receipt');

            if (documents.official_receipt?.number && !documents.official_receipt?.url) {
                const button = document.getElementById('officialReceiptBtn');
                if (button) {
                    button.classList.remove('btn-document-null');
                    button.classList.add('btn-primary');
                    button.disabled = false;
                    button.removeAttribute('data-bs-toggle');
                    button.removeAttribute('data-bs-target');
                    button.onclick = () => window.open(`/official-receipt/${requestId}`, '_blank');
                }
            }
        }

        function formatDateEndorsed(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
            } catch {
                return dateString;
            }
        }

        // ============================================================================
        // ======================== WAIVER HANDLERS ===================================
        // ============================================================================

        function attachWaiverHandlers() {
            document.querySelectorAll('#baseFeesContainer .waiver-checkbox').forEach(checkbox => {
                const newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);
                newCheckbox.addEventListener('change', function () {
                    handleWaiverChange(this);
                });
            });
        }

        async function handleWaiverChange(checkbox) {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const type = checkbox.dataset.type;
            const id = parseInt(checkbox.dataset.id);
            const isWaived = checkbox.checked;

            const itemRow = checkbox.closest('.item-row');
            if (itemRow) {
                itemRow.classList.toggle('waived', isWaived);
            }

            const waivedFacilities = [];
            const waivedEquipment = [];

            document.querySelectorAll('.waiver-checkbox').forEach(cb => {
                const itemId = parseInt(cb.dataset.id);
                const itemType = cb.dataset.type;
                if (cb.checked) {
                    if (itemType === 'facility') waivedFacilities.push(itemId);
                    else if (itemType === 'equipment') waivedEquipment.push(itemId);
                }
            });

            try {
                const adminId = localStorage.getItem('adminId');
                const response = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ waived_facilities: waivedFacilities, waived_equipment: waivedEquipment, admin_id: adminId })
                });

                if (!response.ok) throw new Error('Failed to update waiver status');

                await refreshAllFeeDisplays();
                const itemName = itemRow ? itemRow.querySelector('.item-name')?.textContent : (type === 'facility' ? 'Facility' : 'Equipment');
                showToast(`${itemName} ${isWaived ? 'waived' : 'unwaived'} successfully.`, 'success');

            } catch (error) {
                checkbox.checked = !isWaived;
                if (itemRow) itemRow.classList.toggle('waived');
                showToast('Failed to update waiver: ' + error.message, 'error');
            }
        }

        async function handleWaiveAll(switchElement) {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const waiveAll = switchElement.checked;

            try {
                const adminId = localStorage.getItem('adminId');
                const response = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ waive_all: waiveAll, admin_id: adminId })
                });

                if (!response.ok) throw new Error('Failed to update waiver status');

                document.querySelectorAll('.waiver-checkbox').forEach(checkbox => {
                    checkbox.checked = waiveAll;
                    const itemRow = checkbox.closest('.item-row');
                    if (itemRow) itemRow.classList.toggle('waived', waiveAll);
                });

                await refreshAllFeeDisplays();
                showToast(waiveAll ? 'All items waived successfully.' : 'All waivers removed.', 'success');

            } catch (error) {
                switchElement.checked = !waiveAll;
                showToast('Failed to update waive all: ' + error.message, 'error');
            }
        }

        // ============================================================================
        // ======================== FEE MANAGEMENT ====================================
        // ============================================================================

        async function refreshAllFeeDisplays() {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        // Update current data
                        currentFees = result.data.requisition_fees || [];
                        currentRequestData = result.data;

                        // Re-render fee breakdown with fresh API data
                        renderBaseFees(result.data.requested_items, result.data.duration_hours);
                        renderAdditionalFees(result.data.requisition_fees || []);

                        // Update total using API's calculated value
                        const feeTotal = document.getElementById('feeBreakdownTotal');
                        if (feeTotal && result.data.fees && result.data.fees.approved_fee !== undefined) {
                            feeTotal.textContent = formatMoney(result.data.fees.approved_fee);
                        }

                        // Update timeline if it's open
                        if (document.getElementById('timelineModal')?.classList.contains('show')) {
                            updateTimelineContent(currentComments, currentFees);
                        }
                    }
                }
            } catch (error) {
                console.error('Error refreshing fees:', error);
                showToast('Failed to refresh fee data', 'error');
            }
        }

        async function deleteFee(feeId) {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/fee/${feeId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    showToast('Fee removed successfully', 'success');
                    await refreshAllFeeDisplays();
                }
            } catch (error) {
                console.error('Error removing fee:', error);
                showToast('Failed to remove fee', 'error');
            }
        }

        // ============================================================================
        // ======================== ADMIN ROLE & ACTIONS ==============================
        // ============================================================================

        async function checkAdminRoleAndUpdateUI(data) {
            if (cachedAdminRole === null) {
                try {
                    const response = await fetch('/api/admin/profile', {
                        headers: { 'Authorization': `Bearer ${localStorage.getItem('adminToken')}`, 'Accept': 'application/json' }
                    });
                    if (response.ok) {
                        const adminData = await response.json();
                        cachedAdminRole = adminData.role?.role_title;
                    }
                } catch (error) {
                    console.error('Error fetching admin role:', error);
                    return;
                }
            }

            const isHeadAdmin = cachedAdminRole === 'Head Admin';
            const isApprovingOfficer = cachedAdminRole === 'Approving Officer' || cachedAdminRole === 'Chief Approving Officer';
            const currentStatusId = data.form_details.status?.id;
            const terminalStatuses = [7, 8, 9];

            const actionsSection = document.querySelector('.col-md-3 .card-body .mb-2');
            if (!actionsSection) return;

            // Remove dynamically added buttons but keep original ones
            document.querySelectorAll('.dynamic-action-btn, .action-taken-message').forEach(btn => btn.remove());

            // Reset button visibility
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            const finalizeBtn = document.getElementById('finalizeBtn');

            if (approveBtn) approveBtn.style.display = 'none';
            if (rejectBtn) rejectBtn.style.display = 'none';
            if (finalizeBtn) finalizeBtn.style.display = 'none';

            if (isHeadAdmin && !terminalStatuses.includes(currentStatusId)) {
                if ([1, 2].includes(currentStatusId)) {
                    if (finalizeBtn) finalizeBtn.style.display = 'block';
                } else if (currentStatusId === 3) {
                    actionsSection.appendChild(createActionButton('markScheduledBtn', 'btn-primary', '<i class="bi bi-calendar-event me-1"></i> Mark Scheduled', () => markScheduledModal.show()));
                } else if (currentStatusId === 4) {
                    actionsSection.appendChild(createActionButton('markOngoingBtn', 'btn-primary', '<i class="bi bi-play-circle me-1"></i> Mark Ongoing', () => handleStatusAction('Ongoing')));
                }
                actionsSection.appendChild(createActionButton('closeFormBtn', 'btn-light-danger', '<i class="bi bi-x-circle me-1"></i> Close Form', () => closeFormModal.show()));
            } else if (isApprovingOfficer && [1, 2].includes(currentStatusId)) {
                if (approveBtn) approveBtn.style.display = 'block';
                if (rejectBtn) rejectBtn.style.display = 'block';
            } else if (!terminalStatuses.includes(currentStatusId)) {
                actionsSection.appendChild(createMessageDiv('No actions available for this request status.', 'info-circle-fill', 'text-secondary'));
            }
        }
        function createActionButton(id, className, html, onClick) {
            const btn = document.createElement('button');
            btn.id = id;
            btn.className = `btn ${className} w-100 mb-2 dynamic-action-btn`;
            btn.innerHTML = html;
            btn.addEventListener('click', onClick);
            return btn;
        }

        function createMessageDiv(message, icon, iconColor) {
            const div = document.createElement('div');
            div.className = 'action-taken-message text-center p-3 dynamic-action-btn';
            div.innerHTML = `<i class="bi bi-${icon} ${iconColor} fs-4 d-block mb-2"></i><p class="mb-0 small text-muted">${message}</p>`;
            return div;
        }

        function handleStatusAction(action) {
            const modalContent = document.getElementById('statusModalContent');
            const statusBadge = document.getElementById('statusBadge');
            const currentStatusName = statusBadge ? statusBadge.textContent.trim() : '';

            if (currentStatusName === 'Pending Approval' && action !== 'Cancel Form') {
                showToast('Finalize the form first', 'error');
                return;
            }

            let modalHtml = '';
            switch (action) {
                case 'Ongoing':
                    modalHtml = `<div class="text-center"><i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i><p>Are you sure? This action cannot be undone.</p><p class="text-muted small">Sets the form status to <strong>Ongoing</strong>.</p></div>`;
                    break;
                default: return;
            }

            modalContent.innerHTML = modalHtml;
            selectedStatus = action;
            statusUpdateModal.show();
        }

        async function markNotificationAsRead(requestId) {
            try {
                const adminToken = localStorage.getItem('adminToken');
                if (!adminToken) return;

                await fetch(`/api/admin/notifications/requisition/${requestId}/mark-as-read`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json', 'Content-Type': 'application/json' }
                });
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        // ============================================================================
        // ======================== TIMELINE FUNCTIONS ================================
        // ============================================================================

        async function loadTimelineContent() {
            const timelineContent = document.getElementById('timelineContent');
            const timelineFilter = document.getElementById('timelineFilter');
            if (!timelineContent) return;

            const filter = timelineFilter?.value || 'all';

            // Show loading spinner
            timelineContent.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary mb-2" role="status"><span class="visually-hidden">Loading...</span></div><p class="small mb-0">Loading activity...</p></div>';

            // Small delay to ensure spinner is visible
            await new Promise(resolve => setTimeout(resolve, 100));

            try {
                let activities = [];

                if (filter === 'all' || filter === 'comment') {
                    currentComments.forEach(comment => {
                        activities.push({ type: 'comment', data: comment, timestamp: new Date(comment.created_at) });
                    });
                }

                if (filter === 'all' || filter === 'fee') {
                    currentFees.forEach(fee => {
                        activities.push({ type: 'fee', data: fee, timestamp: new Date(fee.created_at) });
                    });
                }

                activities.sort((a, b) => b.timestamp - a.timestamp);

                if (activities.length === 0) {
                    timelineContent.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comment-slash fa-2x mb-2"></i><p class="small mb-0">No activity yet</p></div>';
                } else {
                    timelineContent.innerHTML = activities.map(activity => {
                        if (activity.type === 'comment') {
                            const c = activity.data;
                            const admin = c.admin || {};
                            return `
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="me-2 flex-shrink-0">
                                                    ${admin.photo_url ? `<img src="${admin.photo_url}" class="rounded-circle" width="32" height="32">` :
                                    `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width:32px;height:32px;font-size:0.8rem;">${(admin.first_name?.charAt(0) || 'A')}${(admin.last_name?.charAt(0) || 'D')}</div>`}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="small">${escapeHtml(admin.first_name || 'Admin')} ${escapeHtml(admin.last_name || '')}</strong>
                                                        <small class="text-muted">${formatTimeAgo(c.created_at)}</small>
                                                    </div>
                                                    <div class="bg-light p-2 rounded small">${escapeHtml(c.comment)}</div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                        } else {
                            const f = activity.data;
                            const amountDisplay = f.type === 'discount'
                                ? (f.discount_type === 'Percentage' ? `-${f.discount_amount}%` : `-${formatMoney(f.discount_amount)}`)
                                : formatMoney(f.fee_amount);
                            return `
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="me-2 flex-shrink-0">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background-color:#d4edda;color:#28a745;">
                                                        <i class="fas fa-money-bill" style="font-size:0.9rem;"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="small">${escapeHtml(f.added_by?.name || 'Admin')}</strong>
                                                        <small class="text-muted">${formatTimeAgo(f.created_at)}</small>
                                                    </div>
                                                    <div class="bg-light p-2 rounded small">added ${f.type === 'discount' ? 'discount' : 'fee'} - ${escapeHtml(f.label)}: ${amountDisplay}</div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                        }
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading timeline:', error);
                timelineContent.innerHTML = '<div class="text-center text-danger py-4">Failed to load activity</div>';
            }
        }

        async function addTimelineComment() {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const commentText = document.getElementById('timelineComment')?.value.trim();

            if (!commentText) {
                showToast('Please enter a comment', 'error');
                return;
            }

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/comment`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ comment: commentText })
                });

                if (response.ok) {
                    document.getElementById('timelineComment').value = '';

                    // Refresh comments only
                    const dataResponse = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                        headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                    });

                    if (dataResponse.ok) {
                        const result = await dataResponse.json();
                        if (result.success && result.data) {
                            currentComments = result.data.comments || [];
                            currentFees = result.data.requisition_fees || [];
                            await loadTimelineContent();
                        }
                    }
                    showToast('Comment added successfully', 'success');
                }
            } catch (error) {
                showToast('Failed to add comment', 'error');
            }
        }
        // ============================================================================
        // ======================== MODAL HANDLERS ====================================
        // ============================================================================

        const markScheduledModal = new bootstrap.Modal(document.getElementById('markScheduledModal'));
        const closeFormModal = new bootstrap.Modal(document.getElementById('closeFormModal'));
        const feeModal = new bootstrap.Modal(document.getElementById('feeModal'));
        const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const finalizeModal = new bootstrap.Modal(document.getElementById('finalizeModal'));
        const statusUpdateModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));

        document.getElementById('confirmMarkScheduled')?.addEventListener('click', async function () {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const btn = this;
            const officialReceiptNum = document.getElementById('officialReceiptNum')?.value.trim();
            const calendarTitle = document.getElementById('scheduledCalendarTitle')?.value.trim();
            const calendarDescription = document.getElementById('scheduledCalendarDescription')?.value.trim();

            if (!officialReceiptNum) {
                showToast('Please enter an official receipt number', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/mark-scheduled`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ official_receipt_num: officialReceiptNum, calendar_title: calendarTitle || null, calendar_description: calendarDescription || null })
                });

                if (response.ok) {
                    showToast('Request marked as scheduled successfully!', 'success');
                    markScheduledModal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Confirm & Generate Receipt';
            }
        });

        document.getElementById('confirmStatusUpdate')?.addEventListener('click', async function () {
            if (!selectedStatus) return;

            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const btn = this;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';

            try {
                const requestData = { status_name: selectedStatus };
                if (selectedStatus === 'Late') {
                    const penalty = document.getElementById('latePenaltyAmount')?.value;
                    if (penalty && parseFloat(penalty) > 0) requestData.late_penalty_fee = parseFloat(penalty);
                }

                const response = await fetch(`/api/admin/requisition/${requestId}/update-status`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(requestData)
                });

                if (response.ok) {
                    showToast('Status updated successfully!', 'success');
                    statusUpdateModal.hide();
                    await refreshStatusAndApprovals();
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Confirm Change';
            }
        });

        document.getElementById('confirmCloseForm')?.addEventListener('click', async function () {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const btn = this;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Closing...';

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/close`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });

                if (response.ok) {
                    closeFormModal.hide();
                    showToast('Form closed successfully!', 'success');
                    setTimeout(() => window.location.href = '/admin/calendar', 1500);
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Confirm Close';
            }
        });

        document.getElementById('confirmFinalize')?.addEventListener('click', async function (e) {
            e.preventDefault();
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const btn = this;
            let calendarTitle = document.getElementById('calendarTitle')?.value.trim() || null;
            let calendarDescription = document.getElementById('calendarDescription')?.value.trim() || null;

            if (!adminToken) {
                showToast('Authentication error. Please login again.', 'error');
                return;
            }

            if (calendarTitle && calendarTitle.length > 50) {
                showToast('Calendar Title must not exceed 50 characters.', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Finalizing...';

            try {
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

                const result = await response.json();

                if (response.ok) {
                    finalizeModal.hide();
                    showToast(result.message || 'Form finalized successfully!', 'success');
                    await refreshStatusAndApprovals();
                    // Also refresh fee displays since total might change
                    await refreshAllFeeDisplays();
                } else {
                    showToast(result.message || 'Failed to finalize request', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Finalize Request';
                }
            } catch (error) {
                console.error('Finalize error:', error);
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Finalize Request';
            }
        });
        document.getElementById('approveBtn')?.addEventListener('click', () => approveModal.show());
        document.getElementById('rejectBtn')?.addEventListener('click', () => rejectModal.show());
        document.getElementById('finalizeBtn')?.addEventListener('click', () => finalizeModal.show());
        document.getElementById('addFeeBtn')?.addEventListener('click', () => feeModal.show());
        document.getElementById('waiveAllSwitch')?.addEventListener('change', function () { handleWaiveAll(this); });
        document.getElementById('closeForm')?.addEventListener('click', () => closeFormModal.show());

        document.getElementById('confirmApprove')?.addEventListener('click', async function () {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const remarks = document.getElementById('approveRemarks')?.value;
            const btn = this;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

            const response = await fetch(`/api/admin/requisition/${requestId}/approve`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ remarks })
            });

            if (response.ok) {
                showToast('Request approved successfully!', 'success');
                approveModal.hide();
                document.getElementById('approveRemarks').value = '';
                await refreshStatusAndApprovals();
            } else {
                showToast('Failed to approve request', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = 'Confirm Approval';
        });

        document.getElementById('confirmReject')?.addEventListener('click', async function () {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const remarks = document.getElementById('rejectRemarks')?.value;
            const btn = this;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

            const response = await fetch(`/api/admin/requisition/${requestId}/reject`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ remarks })
            });

            if (response.ok) {
                showToast('Request rejected successfully!', 'success');
                rejectModal.hide();
                document.getElementById('rejectRemarks').value = '';
                await refreshStatusAndApprovals();
            } else {
                showToast('Failed to reject request', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = 'Confirm Rejection';
        });

        document.getElementById('addFeeBtn')?.addEventListener('click', () => feeModal.show());
        document.getElementById('waiveAllSwitch')?.addEventListener('change', function () { handleWaiveAll(this); });
        document.getElementById('closeForm')?.addEventListener('click', () => closeFormModal.show());

        document.getElementById('saveFeeBtn')?.addEventListener('click', async function () {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const type = document.getElementById('feeType')?.value;
            const value = parseFloat(document.getElementById('feeValue')?.value);
            const label = document.getElementById('feeLabel')?.value;
            const discountType = document.getElementById('discountType')?.value;
            const accountNum = document.getElementById('accountNum')?.value.trim();

            if (!type || !value || !label) {
                showToast("Please fill all required fields.", "error");
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

            try {
                let endpoint = '', body = {};
                if (type === 'additional') {
                    endpoint = `/api/admin/requisition/${requestId}/fee`;
                    body = { label, fee_amount: value, account_num: accountNum || null };
                } else {
                    endpoint = `/api/admin/requisition/${requestId}/discount`;
                    body = { label, discount_amount: value, discount_type: discountType, account_num: accountNum || null };
                }

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                });

                if (response.ok) {
                    document.getElementById('feeValue').value = '';
                    document.getElementById('feeType').value = '';
                    document.getElementById('feeLabel').value = '';
                    document.getElementById('accountNum').value = '';
                    document.getElementById('discountTypeSection').style.display = 'none';
                    feeModal.hide();
                    showToast('Fee/discount added successfully', 'success');

                    // Refresh only fee data without reloading entire page
                    await refreshAllFeeDisplays();
                }
            } catch (error) {
                showToast('Failed to add fee/discount', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = 'Add';
            }
        });

        document.getElementById('feeType')?.addEventListener('change', function () {
            const discountSection = document.getElementById('discountTypeSection');
            if (discountSection) discountSection.style.display = this.value === 'discount' ? 'block' : 'none';

            if (this.value === 'vat') {
                document.getElementById('feeLabel').value = 'Less VAT';
                document.getElementById('feeValue').value = '12';
                document.getElementById('discountType').value = 'Percentage';
            }
        });

        // ============================================================================
        // ======================== TIMELINE UI SETUP =================================
        // ============================================================================

        const stickyTimelineBtn = document.getElementById('stickyTimelineBtn');
        const timelineModal = document.getElementById('timelineModal');
        const closeTimelineBtn = document.getElementById('closeTimelineBtn');
        const timelineSendBtn = document.getElementById('timelineSendBtn');
        const timelineFilter = document.getElementById('timelineFilter');
        const refreshTimelineBtn = document.getElementById('refreshTimelineBtn');

        if (stickyTimelineBtn && timelineModal) {
            stickyTimelineBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                timelineModal.classList.toggle('show');
                if (timelineModal.classList.contains('show')) loadTimelineContent();
            });
            closeTimelineBtn?.addEventListener('click', () => timelineModal.classList.remove('show'));
            timelineSendBtn?.addEventListener('click', addTimelineComment);
            timelineFilter?.addEventListener('change', loadTimelineContent);
            refreshTimelineBtn?.addEventListener('click', async () => {
                const requestId = window.location.pathname.split('/').pop();
                const adminToken = localStorage.getItem('adminToken');

                try {
                    const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                        headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                    });

                    if (response.ok) {
                        const result = await response.json();
                        if (result.success && result.data) {
                            currentComments = result.data.comments || [];
                            currentFees = result.data.requisition_fees || [];
                            await loadTimelineContent();
                            showToast('Timeline refreshed', 'success');
                        }
                    }
                } catch (error) {
                    showToast('Failed to refresh timeline', 'error');
                }
            });

            const modalHeader = document.getElementById('timelineModalHeader');
            let isDragging = false, offsetX, offsetY;
            modalHeader?.addEventListener('mousedown', (e) => {
                if (e.target.closest('select') || e.target.closest('.close-timeline') || e.target.closest('#refreshTimelineBtn') || e.target.closest('#timelineFilter')) return;
                isDragging = true;
                offsetX = e.clientX - timelineModal.offsetLeft;
                offsetY = e.clientY - timelineModal.offsetTop;
                modalHeader.style.cursor = 'grabbing';
            });
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault();
                timelineModal.style.left = (e.clientX - offsetX) + 'px';
                timelineModal.style.top = (e.clientY - offsetY) + 'px';
                timelineModal.style.bottom = 'auto';
                timelineModal.style.right = 'auto';
            });
            document.addEventListener('mouseup', () => { isDragging = false; if (modalHeader) modalHeader.style.cursor = 'move'; });
        }

        const timelineComment = document.getElementById('timelineComment');
        timelineComment?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                timelineSendBtn?.click();
            }
        });
        timelineComment?.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // ============================================================================
        // ======================== BACK TO TOP BUTTON ================================
        // ============================================================================

        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            window.addEventListener('scroll', () => backToTopButton.classList.toggle('show', window.pageYOffset > 300));
            backToTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }

        // ============================================================================
        // ======================== DOCUMENT PREVIEW ==================================
        // ============================================================================

        document.addEventListener('click', function (event) {
            let button = event.target.closest('[data-bs-target="#documentModal"][data-document-url]');
            if (button && button.hasAttribute('data-document-url')) {
                event.preventDefault();
                event.stopPropagation();

                const documentUrl = button.getAttribute('data-document-url');
                const documentTitle = button.getAttribute('data-document-title');
                const fileExtension = documentUrl.split('.').pop().toLowerCase();
                const isPDF = fileExtension === 'pdf';
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExtension);

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

                const overlay = document.createElement('div');
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.9);z-index:9999;display:flex;justify-content:center;align-items:center;cursor:pointer';

                const closeOverlay = () => {
                    document.body.removeChild(overlay);
                    document.body.style.overflow = originalBodyStyles.overflow || '';
                    document.body.style.position = originalBodyStyles.position || '';
                    document.body.style.width = originalBodyStyles.width || '';
                    document.body.style.height = originalBodyStyles.height || '';
                };

                overlay.onclick = (e) => { if (e.target === overlay) closeOverlay(); };

                const closeButton = document.createElement('button');
                closeButton.innerHTML = '&times;';
                closeButton.style.cssText = 'position:absolute;top:20px;right:20px;background:rgba(255,255,255,0.2);color:white;border:none;border-radius:50%;width:40px;height:40px;font-size:24px;cursor:pointer;z-index:10000';
                closeButton.onclick = closeOverlay;
                overlay.appendChild(closeButton);

                const loadingContainer = document.createElement('div');
                loadingContainer.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;position:absolute;z-index:1000';
                loadingContainer.innerHTML = '<div class="spinner-border text-light" style="width:3rem;height:3rem;"></div><div style="color:white;margin-top:1.5rem;">Loading document...</div>';
                overlay.appendChild(loadingContainer);

                const removeLoading = () => loadingContainer.remove();

                if (isPDF) {
                    const iframe = document.createElement('iframe');
                    iframe.src = `https://docs.google.com/gview?url=${encodeURIComponent(documentUrl)}&embedded=true`;
                    iframe.style.cssText = 'width:90%;height:90%;border:none;border-radius:8px;opacity:0;transition:opacity 0.3s';
                    iframe.onload = () => { removeLoading(); iframe.style.opacity = '1'; };
                    overlay.appendChild(iframe);
                } else if (isImage) {
                    const img = document.createElement('img');
                    img.src = documentUrl;
                    img.style.cssText = 'max-width:90%;max-height:90vh;border-radius:8px;object-fit:contain;opacity:0;transition:opacity 0.3s';
                    img.onload = () => { removeLoading(); img.style.opacity = '1'; };
                    img.onerror = () => { loadingContainer.innerHTML = '<div class="text-danger">Failed to load image</div>'; };
                    overlay.appendChild(img);
                } else {
                    removeLoading();
                    const downloadContainer = document.createElement('div');
                    downloadContainer.style.cssText = 'background:white;padding:2rem;border-radius:8px;text-align:center';
                    downloadContainer.innerHTML = `<p>This file type cannot be previewed.</p><a href="${documentUrl}" class="btn btn-primary" download>Download File</a>`;
                    downloadContainer.querySelector('a').onclick = closeOverlay;
                    overlay.appendChild(downloadContainer);
                }

                document.body.appendChild(overlay);
            }
        });

        // ============================================================================
        // ======================== INITIALIZE PAGE ===================================
        // ============================================================================

        loadRequestViewData();
    </script>
@endsection