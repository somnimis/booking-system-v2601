@extends('layouts.admin')
@section('title', 'Review Request')
@section('content')

    <style>
        /* Approval Stages Styling */
        .approval-stages-container {
            padding: 0.5rem;
        }

        .stage-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .stage-header {
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
        }

        .approval-card {
            transition: all 0.2s ease;
            background: white;
        }

        .approval-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .approval-progress {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
            border-radius: 12px;
        }

        .badge {
            padding: 0.35rem 0.75rem;
            font-weight: 500;
        }

        /* Timeline animation for pending items */
        @keyframes pulse {
            0% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.6;
            }
        }

        .approval-card .fa-clock {
            animation: pulse 1.5s ease-in-out infinite;
        }

        .tracking-wider {
            letter-spacing: 0.05em;
        }

        #actionButtonsTop .btn {
            min-width: 200px !important;
        }

        @media (max-width: 576px) {
            #actionButtonsTop .btn {
                width: 100%;
            }
        }

        /* ============================================================
                                                                                                                                       TAB STYLES
                                                                                                                                    ============================================================ */
        .request-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
        }

        .request-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border: none;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .request-tabs .nav-link:hover {
            color: #004080;
            border-bottom-color: #00408080;
        }

        .request-tabs .nav-link.active {
            color: #004080;
            background: transparent;
            border-bottom-color: #004080;
        }

        .request-tabs .nav-link i {
            margin-right: 0.5rem;
        }

        /* Tab content containers */
        .tab-pane {
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================================
                                                                                                                                       ORIGINAL STYLES (Preserved)
                                                                                                                                    ============================================================ */
        /* Footer card responsive styles */
        @media (max-width: 768px) {
            #footerTotalFee {
                font-size: 1.5rem !important;
            }

            .card .small {
                font-size: 0.7rem;
            }

            .d-flex.gap-2.flex-wrap {
                gap: 0.5rem !important;
            }
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            /* Changed from left: 50% to right: 20px */
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
            transform: translateY(-3px);
            /* Removed the translateX since we're using right positioning */
        }

        /* Pastel theme for action buttons */
        #approveBtn,
        #confirmApprove {
            background-color: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
        }

        #rejectBtn,
        #confirmReject {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
        }

        #finalizeBtn,
        #confirmFinalize,
        #confirmMarkScheduled {
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

        /* Approval pills styling */
        .status-pill {
            transition: all 0.2s ease;
            border-radius: 8px !important;
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

        /* Document items styling */
        .documents-vertical {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .document-mini-item {
            background-color: #f8f9fa;
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .document-mini-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }

        .btn-document-null {
            background-color: #f0f0f0 !important;
            color: #999999 !important;
            border: 1px solid #e0e0e0 !important;
            cursor: not-allowed !important;
            opacity: 0.7;
        }

        /* Request code styling */
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

        /* Timeline container and empty state vertical centering */
        .timeline-tab-container {
            display: flex;
            flex-direction: column;
        }

        .timeline-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }

        .timeline-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .comment-input-area {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        /* Financials tab styles */
        .fee-item.waived {
            opacity: 0.7;
            background-color: #e9ecef !important;
        }

        /* Tab content visibility */
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }
    </style>

    <!-- Main Content -->
    <main id="main">
        <div class="view-container">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end mb-3 gap-3 gap-sm-0">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="/admin/manage-requests">Requests</a></li>
                            <li class="breadcrumb-item active">View Details</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="fw-bold mb-0">Request #<span id="requestIdDisplay">--</span></h2>
                        <div id="statusBadgeContainer" class="d-inline-flex align-items-center"></div>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2" id="actionButtonsTop"></div>
            </div>

            <div class="request-tabs">
                <ul class="nav nav-tabs" id="requestTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-tab="details" type="button" role="tab">
                            <i class="bi bi-info-circle"></i> Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="financials-tab" data-tab="financials" type="button" role="tab">
                            <i class="bi bi-currency-dollar"></i> Financials
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="timeline-tab" data-tab="timeline" type="button" role="tab">
                            <i class="bi bi-clock-history"></i> Timeline
                        </button>
                    </li>
                </ul>
            </div>

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
                </div>
            </div>

            <div id="contentState" style="display: none;">
                <!-- ==================== TAB: DETAILS ==================== -->
                <div id="detailsPane" class="tab-pane active">
                    <div class="row g-3">
                        <div class="col-lg-8 d-flex flex-column gap-3">
                            <div class="card custom-card border-top-accent m-1">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Request Information</h5>
                                    <i class="bi bi-info-circle text-muted"></i>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3" id="detailsContainer"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 d-flex flex-column gap-3">
                            <div class="card custom-card border-top-accent m-1">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Booking Details</h5>
                                    <i class="bi bi-calendar text-muted"></i>
                                </div>
                                <div class="card-body">
                                    <div id="eventDetails"></div>
                                </div>
                            </div>

                            <div class="card custom-card border-top-accent m-1">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Attachments</h5>
                                    <i class="bi bi-paperclip text-muted"></i>
                                </div>
                                <div class="card-body">
                                    <div class="documents-vertical" id="attachmentsStatusCard"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: FINANCIALS ==================== -->
                <div id="financialsPane" class="tab-pane">
                    <div class="text-center py-5">
                        <i class="bi bi-currency-dollar fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="mb-2">Financial Management</h5>
                        <p class="text-muted mb-4">Manage fees, waivers, and discounts for this request</p>
                        <a href="/admin/requisition/{{ $requestId }}/financials" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-2"></i>Edit Financials
                        </a>
                    </div>
                </div>

                <!-- ==================== TAB: TIMELINE ==================== -->
                <div id="timelinePane" class="tab-pane">
                    <div class="row g-3">
                        <!-- Left Column: Activity Timeline -->
                        <div class="col-lg-7">
                            <div class="card custom-card border-top-accent h-100 d-flex flex-column">
                                <div class="card-header d-flex align-items-center justify-content-between flex-shrink-0">
                                    <h5 class="mb-0">Activity Timeline</h5>
                                    <div class="d-flex gap-2">
                                        <select id="timelineFilterTab" class="form-select form-select-sm"
                                            style="width: auto;">
                                            <option value="all">All</option>
                                            <option value="comment">Comments only</option>
                                            <option value="fee">Fee changes only</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary" id="refreshTimelineBtnTab">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column" style="flex: 1; overflow: hidden;">
                                    <div id="timelineContentTab" class="timeline-tab-container"
                                        style="flex: 1; overflow-y: auto; min-height: 300px;">
                                        <div class="text-center text-muted py-4">
                                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                            </div>
                                            <p class="small mb-0">Loading activity...</p>
                                        </div>
                                    </div>
                                    <div class="comment-input-area mt-3 flex-shrink-0">
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control" placeholder="Press Enter to send..."
                                                id="timelineCommentTab" style="flex: 1;">
                                            <button class="btn btn-primary" id="timelineSendBtnTab"
                                                style="white-space: nowrap;">
                                                <i class="fas fa-paper-plane"></i> Send
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Approvals Card -->
                        <div class="col-lg-5">
                            <div class="card custom-card border-top-accent h-100">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0">Approval Status</h5>
                                    <i class="bi bi-check2-circle text-muted"></i>
                                </div>
                                <div class="card-body" id="approvalsListContainer"
                                    style="max-height: 500px; overflow-y: auto;">
                                    <div class="text-center text-muted py-4">Loading approvals...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Modals -->
            <!-- Status Update Modal -->
            <div class="modal fade" id="statusUpdateModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Status Change</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="statusModalContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmStatusUpdate">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                Confirm Change
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Fee Modal -->
            <div class="modal fade" id="feeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Fee or Discount</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="feeForm">
                                <input type="hidden" id="feeRequestId" value="{{ $requestId }}">
                                <div class="mb-3">
                                    <label for="feeType" class="form-label">Fee Type</label>
                                    <select id="feeType" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="additional">Additional Fee</option>
                                        <option value="discount">Discount</option>
                                        <option value="vat">Less VAT (12%)</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="discountTypeSection" style="display: none;">
                                    <label for="discountType" class="form-label">Discount Type</label>
                                    <select id="discountType" class="form-select">
                                        <option value="Fixed">Fixed Amount</option>
                                        <option value="Percentage">Percentage</option>
                                    </select>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="feeLabel" class="form-label">Fee Label</label>
                                        <input type="text" id="feeLabel" class="form-control" placeholder="Fee Label"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="accountNum" class="form-label">Account Number (Optional)</label>
                                        <input type="text" id="accountNum" class="form-control"
                                            placeholder="Enter account number">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="feeValue" class="form-label">Amount</label>
                                    <input type="number" id="feeValue" class="form-control" step="0.01" min="0.01"
                                        placeholder="Enter amount" required>
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

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Approval</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to approve this request?</p>
                            <div class="mb-3">
                                <label for="approveRemarks" class="form-label">Remarks (Optional)</label>
                                <textarea class="form-control" id="approveRemarks" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" id="confirmApprove">Confirm Approval</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Rejection</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to reject this request?</p>
                            <div class="mb-3">
                                <label for="rejectRemarks" class="form-label">Remarks (Optional)</label>
                                <textarea class="form-control" id="rejectRemarks" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmReject">Confirm Rejection</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finalize Modal -->
            <div class="modal fade" id="finalizeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Finalize Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-4">
                                <div class="col">
                                    <div class="alert alert-success mb-0"><strong>Approvals:</strong> <span
                                            id="currentApprovalCount" class="fw-bold"></span></div>
                                </div>
                                <div class="col">
                                    <div class="alert alert-danger mb-0"><strong>Rejections:</strong> <span
                                            id="currentRejectionCount" class="fw-bold"></span></div>
                                </div>
                            </div>
                            <div class="text-center mb-4">
                                <h6 class="fw-bold mb-3">Are you sure? This action cannot be undone.</h6>
                            </div>
                            <div class="mb-3">
                                <label for="calendarTitle" class="form-label">Event Title</label>
                                <input type="text" class="form-control" id="calendarTitle" maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label for="calendarDescription" class="form-label">Event Description</label>
                                <textarea class="form-control" id="calendarDescription" rows="3" maxlength="100"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmFinalize">
                                <span class="spinner-border spinner-border-sm me-1 d-none"></span> Finalize Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Close Form Modal -->
            <div class="modal fade" id="closeFormModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Close Form</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="bi bi-exclamation-triangle fa-3x text-danger mb-3"></i>
                                <p>Are you sure you want to close this form?</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmCloseForm">
                                <span class="spinner-border spinner-border-sm d-none"></span> Confirm Close
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
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to mark this request as scheduled?</p>
                            <div class="mb-3">
                                <label for="officialReceiptNum" class="form-label">Official Receipt Number *</label>
                                <input type="text" class="form-control" id="officialReceiptNum" required>
                            </div>
                            <div class="mb-3">
                                <label for="scheduledCalendarTitle" class="form-label">Calendar Event Title</label>
                                <input type="text" class="form-control" id="scheduledCalendarTitle" maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label for="scheduledCalendarDescription" class="form-label">Event Description</label>
                                <textarea class="form-control" id="scheduledCalendarDescription" rows="3"
                                    maxlength="100"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmMarkScheduled">
                                <span class="spinner-border spinner-border-sm d-none"></span> Confirm & Generate Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Approvals/Rejections Modals -->
            <div class="modal fade" id="approvalsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Approvals</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="rejectionsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Rejections</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                    </div>
                </div>
            </div>

            <!-- Back to Top Button -->
            <button class="back-to-top" id="backToTop" title="Back to Top"><i class="bi bi-arrow-up"></i></button>
        </div>
    </main>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/request-view.js') }}" defer></script>
    <script>
        // =============================================
        // PAGE INITIALIZATION ONLY
        // All logic is in request-view.js
        // =============================================

        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function () {
            // Get request ID from URL
            const pathParts = window.location.pathname.split('/');
            const requestId = pathParts[pathParts.length - 1];

            // Log initialization
            console.log('🚀 Initializing RequestView for ID:', requestId);

            // Check if RequestView module is loaded
            if (typeof RequestView !== 'undefined' && RequestView.init) {
                try {
                    RequestView.init(requestId);
                } catch (error) {
                    console.error('❌ RequestView initialization failed:', error);
                    // Fallback to basic loading
                    loadBasicRequestData(requestId);
                }
            } else {
                console.warn('⚠️ RequestView module not loaded, using fallback');
                loadBasicRequestData(requestId);
            }
        });

        // =============================================
        // FALLBACK: Basic data loading
        // (Only used if RequestView module fails)
        // =============================================
        async function loadBasicRequestData(requestId) {
            const adminToken = localStorage.getItem('adminToken');

            if (!adminToken) {
                showFallbackError('Authentication error. Please login again.');
                return;
            }

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to load request');
                const result = await response.json();

                if (!result.success) throw new Error(result.error || 'Failed to load data');

                // Show content
                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('contentState').style.display = 'block';

                // Update basic info
                const idDisplay = document.getElementById('requestIdDisplay');
                if (idDisplay) {
                    idDisplay.textContent = String(result.data.request_id).padStart(4, '0');
                }

                // Update status badge
                const badgeContainer = document.getElementById('statusBadgeContainer');
                if (badgeContainer && result.data.form_details?.status) {
                    badgeContainer.innerHTML = `<span class="badge" style="background-color: ${result.data.form_details.status.color}; color: white; padding: 8px 16px; font-size: 1rem;">${result.data.form_details.status.name}</span>`;
                }

                console.log('✅ Fallback data loaded successfully');

            } catch (error) {
                console.error('❌ Fallback load failed:', error);
                showFallbackError(error.message);
            }
        }

        function showFallbackError(message) {
            document.getElementById('loadingState').style.display = 'none';
            const contentState = document.getElementById('contentState');
            if (contentState) {
                contentState.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Error loading request:</strong> ${message}
                            <br><br>
                            <button class="btn btn-outline-danger btn-sm" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Retry
                            </button>
                        </div>
                    `;
                contentState.style.display = 'block';
            }
        }
    </script>
@endsection