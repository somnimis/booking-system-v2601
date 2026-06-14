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
                        <select id="timelineFilterTab" class="form-select form-select-sm" style="width: auto;">
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
                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                            <p class="small mb-0">Loading activity...</p>
                        </div>
                    </div>
                    <div class="comment-input-area mt-3 flex-shrink-0">
                        <div class="d-flex gap-2">
                            <input type="text" class="form-control" placeholder="Press Enter to send..."
                                id="timelineCommentTab" style="flex: 1;">
                            <button class="btn btn-primary" id="timelineSendBtnTab" style="white-space: nowrap;">
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
                <div class="card-body" id="approvalsListContainer" style="max-height: 500px; overflow-y: auto;">
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
    <script>
        // ============================================================================
        // =========================== UTILITY FUNCTIONS ==============================
        // ============================================================================

        function formatMoney(amount) {
            let num = parseFloat(amount);
            if (isNaN(num)) return '₱0.00';
            return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

        function updateFooterTotalFee(amount) {
            const footerTotal = document.getElementById('footerTotalFee');
            if (footerTotal) footerTotal.textContent = formatMoney(amount);
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
            toast.innerHTML = `<div class="d-flex align-items-center px-3 py-1"><i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i><div class="toast-body flex-grow-1" style="padding:0.25rem 0;">${message}</div><button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button></div><div class="loading-bar" style="height:3px;background:rgba(255,255,255,0.7);width:100%;transition:width ${duration}ms linear;"></div>`;
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
            if (!history || history.length === 0) return '<div class="text-center text-muted py-4">No records found</div>';
            return history.map(item => `<div class="d-flex align-items-center mb-3 p-2 border rounded-3"><div class="me-3 flex-shrink-0">${item.admin_photo ? `<img src="${item.admin_photo}" class="rounded-circle" width="45" height="45" style="object-fit: cover;">` : `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 45px; height: 45px;">${item.admin_name.split(' ').map(n => n.charAt(0)).join('')}</div>`}</div><div class="flex-grow-1"><div class="d-flex justify-content-between align-items-start"><div><strong class="d-block">${escapeHtml(item.admin_name)}</strong><small class="text-muted"><i class="fa ${item.action_icon} ${item.action_class} me-1"></i>${item.action} this request</small>${item.remarks ? `<div class="mt-1 small text-muted">"${escapeHtml(item.remarks)}"</div>` : ''}</div><small class="text-muted text-end">${item.formatted_date}</small></div></div></div>`).join('');
        }

        // ============================================================================
        // ======================== GLOBAL VARIABLES ==================================
        // ============================================================================

        let cachedAdminRole = null;
        let currentComments = [];
        let currentFees = [];
        let currentRequestData = null;
        let selectedStatus = '';
        let renderedTabs = { details: false, financials: false, timeline: false, approvals: false };

        // ============================================================================
        // ======================== SINGLE API CALL ===================================
        // ============================================================================

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
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Failed to load request data');

                currentRequestData = result.data;
                currentComments = result.data.comments || [];
                currentFees = result.data.requisition_fees || [];

                console.log('✅ Request view data loaded in ' + Math.round(performance.now() - startTime) + 'ms');

                // Update request ID display (only element that exists)
                const requestIdDisplay = document.getElementById('requestIdDisplay');
                if (requestIdDisplay) {
                    requestIdDisplay.textContent = String(currentRequestData.request_id).padStart(4, '0');
                }

                // Render the active tab (Details by default)
                await renderActiveTab();

                document.getElementById('loadingState').style.display = 'none';
                document.getElementById('contentState').style.display = 'block';

                markNotificationAsRead(requestId);
                await checkAdminRoleAndUpdateUI(currentRequestData);
                console.log('Current renderedTabs state:', renderedTabs);

            } catch (error) {
                console.error('Error loading request data:', error);
                showToast('Failed to load request details: ' + error.message, 'error');
                document.getElementById('loadingState').style.display = 'none';
                const contentState = document.getElementById('contentState');
                if (contentState) {
                    contentState.innerHTML = '<div class="alert alert-danger">Failed to load request details. Please try refreshing the page.<br><small>Error: ' + escapeHtml(error.message) + '</small></div>';
                    contentState.style.display = 'block';
                }
            }
        }

        async function renderActiveTab() {
            const activeTab = document.querySelector('.nav-link.active')?.getAttribute('data-tab') || 'details';

            switch (activeTab) {
                case 'details':
                    await renderDetailsTab();
                    break;
                case 'financials':
                    break;
                case 'timeline':
                    await renderTimelineTab();
                    break;
            }
        }
        // ============================================================================
        // ======================== TAB RENDER FUNCTIONS ==============================
        // ============================================================================

        async function renderDetailsTab() {
            if (renderedTabs.details) return;

            const data = currentRequestData;
            if (!data) return;

            // Render Request Information
            const detailsContainer = document.getElementById('detailsContainer');
            if (detailsContainer) {
                detailsContainer.innerHTML = `
                                                                                                            <div class="col-md-6">
                                                                                                                <div class="mb-3"><label class="text-muted small">Requester</label><div class="fw-medium">${escapeHtml(data.user_details.first_name)} ${escapeHtml(data.user_details.last_name)}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">Organization</label><div class="fw-medium">${escapeHtml(data.user_details.organization_name || 'N/A')}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">Email</label><div class="fw-medium">${escapeHtml(data.user_details.email)}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">Contact No.</label><div class="fw-medium">${escapeHtml(data.user_details.contact_number || 'N/A')}</div></div>
                                                                                                            </div>
                                                                                                            <div class="col-md-6">
                                                                                                                <div class="mb-3"><label class="text-muted small">User Type</label><div class="fw-medium">${escapeHtml(data.user_details.user_type)}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">School ID</label><div class="fw-medium">${escapeHtml(data.user_details.school_id || 'N/A')}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">Purpose</label><div class="fw-medium">${escapeHtml(data.form_details.purpose || 'N/A')}</div></div>
                                                                                                                <div class="mb-3"><label class="text-muted small">Participants</label><div class="fw-medium">${data.form_details.num_participants || 0}</div></div>
                                                                                                            </div>
                                                                                                        `;
            }

            // Render Attachments
            const attachmentsContainer = document.getElementById('attachmentsStatusCard');
            if (attachmentsContainer) {
                attachmentsContainer.innerHTML = `
                                                                                                            <div class="documents-vertical">
                                                                                                                <div class="document-mini-item d-flex align-items-center p-2 border rounded-3 mb-2">
                                                                                                                    <i id="formalLetterIcon" class="fas fa-file-alt fa-lg text-muted me-3"></i>
                                                                                                                    <div class="flex-grow-1"><div class="small fw-bold">Event Documents</div></div>
                                                                                                                    <button type="button" id="formalLetterBtn" class="btn btn-sm btn-document-null ms-2">View</button>
                                                                                                                </div>
                                                                                                                <div class="document-mini-item d-flex align-items-center p-2 border rounded-3 mb-2">
                                                                                                                    <i id="facilityLayoutIcon" class="fas fa-map-marked-alt fa-lg text-muted me-3"></i>
                                                                                                                    <div class="flex-grow-1"><div class="small fw-bold">Venue Layout</div></div>
                                                                                                                    <button type="button" id="facilityLayoutBtn" class="btn btn-sm btn-document-null ms-2">View</button>
                                                                                                                </div>
                                                                                                                <div class="document-mini-item d-flex align-items-center p-2 border rounded-3 mb-2">
                                                                                                                    <i id="proofOfPaymentIcon" class="fas fa-receipt fa-lg text-muted me-3"></i>
                                                                                                                    <div class="flex-grow-1"><div class="small fw-bold">Official Receipt</div></div>
                                                                                                                    <button type="button" id="proofOfPaymentBtn" class="btn btn-sm btn-document-null ms-2">View</button>
                                                                                                                </div>
                                                                                                                <div class="document-mini-item d-flex align-items-center p-2 border rounded-3 mb-2">
                                                                                                                    <i id="officialReceiptIcon" class="fas fa-file-invoice-dollar fa-lg text-muted me-3"></i>
                                                                                                                    <div class="flex-grow-1"><div class="small fw-bold">Use of Hall Permit</div></div>
                                                                                                                    <button type="button" id="officialReceiptBtn" class="btn btn-sm btn-document-null ms-2">View</button>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        `;
            }

            // Render Status Badge
            const statusBadgeContainer = document.getElementById('statusBadgeContainer');
            if (statusBadgeContainer && data.form_details.status) {
                statusBadgeContainer.innerHTML = `<span class="badge" style="background-color: ${data.form_details.status.color}; color: white; padding: 8px 16px; font-size: 1rem;">${data.form_details.status.name}</span>`;
            }

            // Render Event Details
            const eventDetails = document.getElementById('eventDetails');
            if (eventDetails && data.schedule) {
                const startParts = (data.schedule.formatted?.start || 'N/A').split(' ');
                const startDate = startParts.slice(0, 3).join(' ');
                const startTime = startParts.slice(3).join(' ');

                const endParts = (data.schedule.formatted?.end || 'N/A').split(' ');
                const endDate = endParts.slice(0, 3).join(' ');
                const endTime = endParts.slice(3).join(' ');

                const submittedDate = data.status_tracking?.created_at
                    ? new Date(data.status_tracking.created_at).toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    }).replace(' at ', '<br>')
                    : '--';

                eventDetails.innerHTML = `
                                        <div class="row g-2 mb-2">
                                            <div class="col-6"><label class="text-muted small">Start</label><div class="fw-medium">${startDate}<br>${startTime}</div></div>
                                            <div class="col-6"><label class="text-muted small">End</label><div class="fw-medium">${endDate}<br>${endTime}</div></div>
                                            <div class="col-6"><label class="text-muted small">Duration</label><div class="fw-medium">${data.duration_hours} hours</div></div>
                                            <div class="col-6"><label class="text-muted small">Date Submitted</label><div class="fw-medium">${submittedDate}</div></div>
                                        </div>
                                        <div class="border-top pt-2 mt-1">
                                            <label class="text-muted small">Additional Requests</label>
                                            <div class="fw-medium">${escapeHtml(data.form_details.additional_requests || 'None')}</div>
                                        </div>
                                    `;
            }


            // Populate hourly total in details tab footer
            const detailsHourlyTotal = document.getElementById('detailsHourlyTotal');
            if (detailsHourlyTotal && data.fees) {
                detailsHourlyTotal.textContent = formatMoney(data.fees.hourly_total ?? data.fees.base_fee ?? 0);
            }

            // Update document icons
            updateDocumentIcons(data.documents);

            renderedTabs.details = true;
        }

async function renderTimelineTab() {
    if (renderedTabs.timeline) {
        await loadTimelineContentTab();
        await renderApprovalsTab(); // Also refresh approvals when tab is already rendered
        return;
    }

    await loadTimelineContentTab();
    await renderApprovalsTab(); // Load approvals when first rendering timeline tab
    renderedTabs.timeline = true;
}

        function generateApprovalCard(approval) {
            const isApproved = approval.status === 'Approved';
            const isRejected = approval.status === 'Rejected';
            const isPending = approval.status === 'Pending';

            const statusIcon = isApproved ? 'fa-check-circle' : (isRejected ? 'fa-times-circle' : 'fa-clock');
            const statusClass = isApproved ? 'success' : (isRejected ? 'danger' : 'warning');
            const statusText = approval.status;

            const requiredAdmin = approval.required_admin || {};
            const actedBy = approval.acted_by || {};

            return `
            <div class="approval-card border rounded-3 mb-3 p-3 ${isRejected ? 'bg-light' : ''}">
                <div class="d-flex align-items-start">
                    <!-- Admin Avatar -->
                    <div class="me-3 flex-shrink-0">
                        ${requiredAdmin.photo ?
                    `<img src="${requiredAdmin.photo}" class="rounded-circle" width="48" height="48" style="object-fit: cover;">` :
                    `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width: 48px; height: 48px;">
                                <span class="fw-bold fs-5">${(requiredAdmin.first_name?.charAt(0) || '')}${(requiredAdmin.last_name?.charAt(0) || '')}</span>
                             </div>`
                }
                    </div>

                    <!-- Admin Info and Action -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <h6 class="mb-1">${escapeHtml(requiredAdmin.name || 'Unknown Signatory')}</h6>
                                <small class="text-muted d-block">${escapeHtml(requiredAdmin.role || 'Signatory')}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-${statusClass} mb-1">
                                    <i class="fas ${statusIcon} me-1"></i>${statusText}
                                </span>
                                ${approval.formatted_date !== 'Pending' ?
                    `<div><small class="text-muted">${approval.formatted_date}</small></div>` : ''
                }
                            </div>
                        </div>

                        <!-- Action details if acted upon -->
                        ${!isPending ? `
                            <div class="mt-2 pt-2 border-top">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        ${actedBy.photo ?
                        `<img src="${actedBy.photo}" class="rounded-circle" width="24" height="24" style="object-fit: cover;">` :
                        `<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px;">
                                                ${(actedBy.first_name?.charAt(0) || '')}${(actedBy.last_name?.charAt(0) || '')}
                                             </div>`
                    }
                                    </div>
                                    <div>
                                        <small>Acted by: <strong>${escapeHtml(actedBy.name || 'System')}</strong></small>
                                    </div>
                                </div>
                                ${approval.remarks ? `
                                    <div class="mt-2">
                                        <small class="text-muted">Remarks:</small>
                                        <div class="small bg-light p-2 rounded mt-1">${escapeHtml(approval.remarks)}</div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : `
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted"><i class="fas fa-hourglass-half me-1"></i>Awaiting action from this signatory</small>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
        }

        function generateRejectionsList(rejections) {
            if (!rejections || rejections.length === 0) {
                return '<div class="text-center text-muted py-4">No rejections recorded</div>';
            }

            return rejections.map(rejection => {
                const requiredAdmin = rejection.required_admin || {};
                const actedBy = rejection.acted_by || {};

                return `
                <div class="rejection-item border rounded-3 mb-3 p-3 bg-light">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="fas fa-times-circle text-danger fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap">
                                <div>
                                    <h6 class="mb-1">Stage ${rejection.stage} - ${escapeHtml(requiredAdmin.name || 'Signatory')}</h6>
                                    <small class="text-muted">Rejected by: ${escapeHtml(actedBy.name || 'Unknown')}</small>
                                </div>
                                <small class="text-muted">${rejection.formatted_date}</small>
                            </div>
                            ${rejection.remarks ? `
                                <div class="mt-2">
                                    <small class="text-muted">Reason:</small>
                                    <div class="small bg-white p-2 rounded mt-1 border">${escapeHtml(rejection.remarks)}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            }).join('');
        }

        async function renderApprovalsTab() {
            if (renderedTabs.approvals) return;

            const data = currentRequestData;
            if (!data) return;

            const approvalsHistory = data.approval_history || [];

            // Group approvals by stage
            const stage1Approvals = approvalsHistory.filter(item => item.stage === 1);
            const stage2Approvals = approvalsHistory.filter(item => item.stage === 2);
            const stage3Approvals = approvalsHistory.filter(item => item.stage === 3);

            // Calculate counts for each stage
            const stage1Approved = stage1Approvals.filter(a => a.status === 'Approved').length;
            const stage1Rejected = stage1Approvals.filter(a => a.status === 'Rejected').length;
            const stage1Pending = stage1Approvals.filter(a => a.status === 'Pending').length;

            const stage2Approved = stage2Approvals.filter(a => a.status === 'Approved').length;
            const stage2Rejected = stage2Approvals.filter(a => a.status === 'Rejected').length;
            const stage2Pending = stage2Approvals.filter(a => a.status === 'Pending').length;

            const stage3Approved = stage3Approvals.filter(a => a.status === 'Approved').length;
            const stage3Rejected = stage3Approvals.filter(a => a.status === 'Rejected').length;
            const stage3Pending = stage3Approvals.filter(a => a.status === 'Pending').length;

            // Check if stage is fully approved
            const stage1Complete = stage1Pending === 0;
            const stage2Complete = stage2Pending === 0;
            const stage3Complete = stage3Pending === 0;

            const allApproved = stage1Approved === stage1Approvals.length &&
                stage2Approved === stage2Approvals.length &&
                stage3Approved === stage3Approvals.length;

            const hasRejection = stage1Rejected > 0 || stage2Rejected > 0 || stage3Rejected > 0;

            // Generate HTML for approvals
            let html = `
        <div class="approval-stages-container">
            <!-- Approval Progress Summary -->
            <div class="approval-progress mb-4 p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Approval Progress</h6>
                    ${allApproved ? '<span class="badge bg-success">Fully Approved ✓</span>' :
                    hasRejection ? '<span class="badge bg-danger">Rejected ✗</span>' :
                        '<span class="badge bg-warning">In Progress</span>'}
                </div>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: ${(stage1Approved + stage2Approved + stage3Approved) / (stage1Approvals.length + stage2Approvals.length + stage3Approvals.length) * 100}%"></div>
                </div>
                <div class="row text-center small">
                    <div class="col">
                        <span class="text-muted">Stage 1:</span>
                        <span class="fw-bold ${stage1Complete ? 'text-success' : 'text-warning'}">${stage1Approved}/${stage1Approvals.length}</span>
                    </div>
                    <div class="col">
                        <span class="text-muted">Stage 2:</span>
                        <span class="fw-bold ${stage2Complete ? 'text-success' : 'text-warning'}">${stage2Approved}/${stage2Approvals.length}</span>
                    </div>
                    <div class="col">
                        <span class="text-muted">Stage 3:</span>
                        <span class="fw-bold ${stage3Complete ? 'text-success' : 'text-warning'}">${stage3Approved}/${stage3Approvals.length}</span>
                    </div>
                </div>
            </div>
        `;

            // Stage 1 Section
            html += `
        <div class="stage-section mb-4">
            <div class="stage-header d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">Stage 1 Approval</h5>
                    <small class="text-muted">Initial Review and Endorsement</small>
                </div>
                <div class="stage-status">
                    ${stage1Rejected > 0 ?
                    '<span class="badge bg-danger">Rejected</span>' :
                    (stage1Complete ?
                        '<span class="badge bg-success">Completed</span>' :
                        '<span class="badge bg-warning">Pending (' + stage1Pending + ')</span>')}
                </div>
            </div>
            <div class="stage-content">
                ${stage1Approvals.length === 0 ?
                    '<div class="text-center text-muted py-3">No signatories assigned for Stage 1</div>' :
                    stage1Approvals.map(approval => generateApprovalCard(approval)).join('')}
            </div>
        </div>
    `;

            // Stage 2 Section
            if (stage2Approvals.length > 0) {
                html += `
            <div class="stage-section mb-4">
                <div class="stage-header d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Stage 2 Approval</h5>
                        <small class="text-muted">Department Head Review</small>
                    </div>
                    <div class="stage-status">
                        ${stage2Rejected > 0 ?
                        '<span class="badge bg-danger">Rejected</span>' :
                        (stage2Complete ?
                            '<span class="badge bg-success">Completed</span>' :
                            '<span class="badge bg-warning">Pending (' + stage2Pending + ')</span>')}
                    </div>
                </div>
                <div class="stage-content">
                    ${stage2Approvals.map(approval => generateApprovalCard(approval)).join('')}
                </div>
            </div>
        `;
            }

            // Stage 3 Section
            if (stage3Approvals.length > 0) {
                html += `
            <div class="stage-section mb-4">
                <div class="stage-header d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-0">Stage 3 Approval</h5>
                        <small class="text-muted">Final Review and Authorization</small>
                    </div>
                    <div class="stage-status">
                        ${stage3Rejected > 0 ?
                        '<span class="badge bg-danger">Rejected</span>' :
                        (stage3Complete ?
                            '<span class="badge bg-success">Completed</span>' :
                            '<span class="badge bg-warning">Pending (' + stage3Pending + ')</span>')}
                    </div>
                </div>
                <div class="stage-content">
                    ${stage3Approvals.map(approval => generateApprovalCard(approval)).join('')}
                </div>
            </div>
        `;
            }

            html += `</div>`;

            // Update the approvals container
            const approvalsContainer = document.getElementById('approvalsListContainer');
            if (approvalsContainer) {
                approvalsContainer.innerHTML = html;
            }

            renderedTabs.approvals = true;
        }

        // ============================================================================
        // ======================== EXISTING RENDER FUNCTIONS =========================
        // ============================================================================

        async function loadTimelineContentTab() {
            const container = document.getElementById('timelineContentTab');
            const filter = document.getElementById('timelineFilterTab')?.value || 'all';
            if (!container) return;

            container.innerHTML = '<div class="timeline-empty-state text-center text-muted"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><p class="small mb-0">Loading activity...</p></div>';

            try {
                let activities = [];
                if (filter === 'all' || filter === 'comment') {
                    currentComments.forEach(comment => activities.push({ type: 'comment', data: comment, timestamp: new Date(comment.created_at) }));
                }
                if (filter === 'all' || filter === 'fee') {
                    currentFees.forEach(fee => activities.push({ type: 'fee', data: fee, timestamp: new Date(fee.created_at) }));
                }
                activities.sort((a, b) => b.timestamp - a.timestamp);

                if (activities.length === 0) {
    container.innerHTML = '<div class="timeline-empty-state text-center text-muted"><i class="fas fa-comment-slash fa-2x mb-2"></i><p class="small mb-0">No activity yet</p></div>';
} else {
                    container.innerHTML = activities.map(activity => {
                        if (activity.type === 'comment') {
                            const c = activity.data;
                            const admin = c.admin || {};
                            return `<div class="timeline-item mb-3"><div class="d-flex align-items-start"><div class="me-2 flex-shrink-0">${admin.photo_url ? `<img src="${admin.photo_url}" class="rounded-circle" width="32" height="32">` : `<div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary text-white" style="width:32px;height:32px;font-size:0.8rem;">${(admin.first_name?.charAt(0) || 'A')}${(admin.last_name?.charAt(0) || 'D')}</div>`}</div><div class="flex-grow-1"><div class="d-flex justify-content-between align-items-center mb-1"><strong class="small">${escapeHtml(admin.first_name || 'Admin')} ${escapeHtml(admin.last_name || '')}</strong><small class="text-muted">${formatTimeAgo(c.created_at)}</small></div><div class="bg-light p-2 rounded-3 small">${escapeHtml(c.comment)}</div></div></div></div>`;
                        } else {
                            const f = activity.data;
                            const amountDisplay = f.type === 'discount' ? (f.discount_type === 'Percentage' ? `-${f.discount_amount}%` : `-${formatMoney(f.discount_amount)}`) : formatMoney(f.fee_amount);
                            return `<div class="timeline-item mb-3"><div class="d-flex align-items-start"><div class="me-2 flex-shrink-0"><div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background-color:#d4edda;color:#28a745;"><i class="fas fa-money-bill" style="font-size:0.9rem;"></i></div></div><div class="flex-grow-1"><div class="d-flex justify-content-between align-items-center mb-1"><strong class="small">${escapeHtml(f.added_by?.name || 'Admin')}</strong><small class="text-muted">${formatTimeAgo(f.created_at)}</small></div><div class="bg-light p-2 rounded-3 small">added ${f.type === 'discount' ? 'discount' : 'fee'} - ${escapeHtml(f.label)}: ${amountDisplay}</div></div></div></div>`;
                        }
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading timeline:', error);
                container.innerHTML = '<div class="timeline-empty-state text-center text-danger"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p class="small mb-0">Failed to load activity</p></div>';
            }
        }

        async function addTimelineCommentTab() {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const commentText = document.getElementById('timelineCommentTab')?.value.trim();

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
                    document.getElementById('timelineCommentTab').value = '';
                    const dataResponse = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                        headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                    });
                    if (dataResponse.ok) {
                        const result = await dataResponse.json();
                        if (result.success && result.data) {
                            currentComments = result.data.comments || [];
                            currentFees = result.data.requisition_fees || [];
                            await loadTimelineContentTab();
                        }
                    }
                    showToast('Comment added successfully', 'success');
                }
            } catch (error) {
                showToast('Failed to add comment', 'error');
            }
        }

        // ============================================================================
        // ======================== ADMIN ROLE & ACTIONS ==============================
        // ============================================================================

        async function checkAdminRoleAndUpdateUI(data) {
            const topActionsContainer = document.getElementById('actionButtonsTop');
            if (!topActionsContainer) return;

            topActionsContainer.innerHTML = '';

            const currentStatusId = data.form_details?.status?.id;
            const terminalStatuses = [7, 8, 9];

            // Only show Approve/Reject buttons for statuses 1 and 2 (pending/under review)
            // And only if not in terminal status
            if ([1, 2].includes(currentStatusId) && !terminalStatuses.includes(currentStatusId)) {
                // Create Approve button
                const approveBtn = document.createElement('button');
                approveBtn.className = 'btn btn-primary';
                approveBtn.innerHTML = 'Approve';
                approveBtn.style.minWidth = '100px';
                approveBtn.addEventListener('click', () => {
                    document.getElementById('approveRemarks').value = '';
                    approveModal.show();
                });

                // Create Reject button
                const rejectBtn = document.createElement('button');
                rejectBtn.className = 'btn btn-outline-secondary';
                rejectBtn.innerHTML = 'Reject';
                rejectBtn.style.minWidth = '100px';
                rejectBtn.addEventListener('click', () => {
                    document.getElementById('rejectRemarks').value = '';
                    rejectModal.show();
                });

                topActionsContainer.appendChild(approveBtn);
                topActionsContainer.appendChild(rejectBtn);
            }
        }

        function handleStatusAction(action) {
            const modalContent = document.getElementById('statusModalContent');
            if (action === 'Ongoing') {
                modalContent.innerHTML = `<div class="text-center"><i class="fa fa-exclamation-circle fa-3x text-warning mb-3"></i><p>Are you sure? This action cannot be undone.</p><p class="text-muted small">Sets the form status to <strong>Ongoing</strong>.</p></div>`;
                selectedStatus = action;
                statusUpdateModal.show();
            }
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
                        currentRequestData = result.data;
                        currentFees = result.data.requisition_fees || [];
                        currentComments = result.data.comments || [];
                        if (document.getElementById('financialsPane').classList.contains('active')) {
                            renderFeeBreakdown(result.data);
                        }
                        if (document.getElementById('timelinePane').classList.contains('active')) {
                            await loadTimelineContentTab();
                        }
                        if (document.getElementById('timelinePane').classList.contains('active')) {
                            await renderApprovalsTab();
                        }
                        return result.data;
                    }
                }
            } catch (error) {
                console.error('Error refreshing status:', error);
            }
            return null;
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
            if (!officialReceiptNum) { showToast('Please enter an official receipt number', 'error'); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/mark-scheduled`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ official_receipt_num: officialReceiptNum, event_title: calendarTitle || null, event_details: calendarDescription || null })
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
            if (!adminToken) { showToast('Authentication error. Please login again.', 'error'); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Finalizing...';
            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/finalize`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ event_title: calendarTitle, event_details: calendarDescription })
                });
                const result = await response.json();
                if (response.ok) {
                    finalizeModal.hide();
                    showToast(result.message || 'Form finalized successfully!', 'success');
                    await refreshStatusAndApprovals();
                    await refreshAllFeeDisplays();
                } else {
                    showToast(result.message || 'Failed to finalize request', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Finalize Request';
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Finalize Request';
            }
        });

        // Single function to handle both approve and reject actions
        async function processApprovalAction(action) {
            const requestId = window.location.pathname.split('/').pop();
            const adminToken = localStorage.getItem('adminToken');
            const remarks = action === 'approve'
                ? document.getElementById('approveRemarks')?.value
                : document.getElementById('rejectRemarks')?.value;

            const modal = action === 'approve' ? approveModal : rejectModal;
            const btn = action === 'approve'
                ? document.getElementById('confirmApprove')
                : document.getElementById('confirmReject');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${adminToken}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ remarks })
                });

                const result = await response.json();

                if (response.ok) {
                    showToast(`Request ${action}d successfully!`, 'success');
                    modal.hide();

                    // Clear the remarks field
                    if (action === 'approve') {
                        document.getElementById('approveRemarks').value = '';
                    } else {
                        document.getElementById('rejectRemarks').value = '';
                    }

                    // Refresh data and redirect for reject, just refresh for approve
                    if (action === 'reject') {
                        setTimeout(() => {
                            window.location.href = '/admin/manage-requests';
                        }, 1500);
                    } else {
                        await refreshStatusAndApprovals();
                        btn.disabled = false;
                        btn.innerHTML = action === 'approve' ? 'Confirm Approval' : 'Confirm Rejection';
                    }
                } else {
                    showToast(result.message || `Failed to ${action} request`, 'error');
                    btn.disabled = false;
                    btn.innerHTML = action === 'approve' ? 'Confirm Approval' : 'Confirm Rejection';
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = action === 'approve' ? 'Confirm Approval' : 'Confirm Rejection';
            }
        }

        // Approve button handler
        document.getElementById('confirmApprove')?.addEventListener('click', async function () {
            await processApprovalAction('approve');
        });

        // Reject button handler
        document.getElementById('confirmReject')?.addEventListener('click', async function () {
            await processApprovalAction('reject');
        });

        // ============================================================================
        // ======================== TAB SWITCHING =====================================
        // ============================================================================

        function setupTabSwitching() {
            const tabs = document.querySelectorAll('.nav-link[data-tab]');
            const panes = {
                details: document.getElementById('detailsPane'),
                financials: document.getElementById('financialsPane'),
                timeline: document.getElementById('timelinePane')
            };

            tabs.forEach(tab => {
                tab.addEventListener('click', async (e) => {
                    const targetTab = tab.getAttribute('data-tab');

                    // Update active states
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Hide all panes
                    Object.values(panes).forEach(pane => {
                        if (pane) pane.classList.remove('active');
                    });

                    // Show selected pane
                    if (panes[targetTab]) {
                        panes[targetTab].classList.add('active');

                        // Render if not yet rendered
                        switch (targetTab) {
                            case 'details':
                                await renderDetailsTab();
                                break;
                            case 'financials':
                                break;
                            case 'timeline':
                                await renderTimelineTab();
                                break;
                        }
                    }
                });
            });
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
                const originalBodyStyles = { overflow: document.body.style.overflow, position: document.body.style.position, width: document.body.style.width, height: document.body.style.height };
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
        // ======================== BACK TO TOP =======================================
        // ============================================================================

        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            window.addEventListener('scroll', () => backToTopButton.classList.toggle('show', window.pageYOffset > 300));
            backToTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }

        // ============================================================================
        // ======================== INITIALIZE PAGE ===================================
        // ============================================================================

        document.getElementById('timelineSendBtnTab')?.addEventListener('click', addTimelineCommentTab);

        document.getElementById('timelineCommentTab')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addTimelineCommentTab();
            }
        });

        document.getElementById('timelineFilterTab')?.addEventListener('change', loadTimelineContentTab);
        document.getElementById('refreshTimelineBtnTab')?.addEventListener('click', async () => {
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
                        await loadTimelineContentTab();
                        showToast('Timeline refreshed', 'success');
                    }
                }
            } catch (error) {
                showToast('Failed to refresh timeline', 'error');
            }
        });

        setupTabSwitching();
        loadRequestViewData();
    </script>
@endsection