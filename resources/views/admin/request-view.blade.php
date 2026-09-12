@extends('layouts.admin')
@section('title', 'Review Request')
@section('content')

    <style>
        .bg-danger-subtle {
            background-color: #f8d7da !important;
        }

        .bg-success-subtle {
            background-color: #d1e7dd !important;
        }

        .min-w-0 {
            min-width: 0;
        }

        /* Tab Styles */
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

        /* Tab Panes */
        .tab-pane {
            display: none;
            animation: fadeIn 0.2s ease;
        }

        .tab-pane.active {
            display: block;
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

        /* Action Buttons */
        #actionButtonsTop .btn {
            min-width: 180px;
        }

        @media (max-width: 576px) {
            #actionButtonsTop .btn {
                width: 100%;
            }
        }

        /* Pastel Action Buttons */
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
        #confirmMarkScheduled,
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

        /* Back to Top */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
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
            background-color: #0f4c8a;
            transform: translateY(-3px);
        }

        /* Timeline */
        .timeline-item {
            padding: 0.75rem;
            border-left: 2px solid #e9ecef;
            margin-left: 1rem;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 1rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #004080;
        }

        .timeline-item.comment::before {
            background: #6c757d;
        }

        .timeline-item.fee::before {
            background: #28a745;
        }

        .timeline-item.approval::before {
            background: #004080;
        }

        .timeline-item.rejection::before {
            background: #dc3545;
        }

        /* Approval Cards */
        .approval-card {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .approval-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .approval-card.approved {
            border-left-color: #28a745;
        }

        .approval-card.rejected {
            border-left-color: #dc3545;
        }

        .approval-card.pending {
            border-left-color: #ffc107;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 0.6;
            }

            50% {
                opacity: 1;
            }
        }

        .pending-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }

        /* Request Code */
        .request-code {
            background-color: #f4f4f4;
            color: #292929;
            font-family: "Courier New", monospace;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        /* Fee Items */
        .fee-item.waived {
            opacity: 0.7;
            background-color: #e9ecef !important;
        }

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-text {
            height: 1rem;
        }
    </style>

    <main id="main">
        <div class="view-container">
            <!-- Header -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end mb-3 gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="/admin/pending-requests">Requests</a></li>
                            <li class="breadcrumb-item active">View Details</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="fw-bold mb-0">Request #<span id="requestIdDisplay">--</span></h2>
                        <div id="statusBadgeContainer"></div>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2" id="actionButtonsTop"></div>
            </div>

            <!-- Tabs -->
            <div class="request-tabs">
                <ul class="nav nav-tabs" id="requestTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-tab="details" type="button">
                            <i class="bi bi-info-circle"></i> Details
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-tab="timeline" type="button">
                            <i class="bi bi-clock-history"></i> Timeline
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Loading State -->
            <div id="loadingState">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="skeleton skeleton-text mb-3" style="width: 150px;"></div>
                                <hr>
                                <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                <div class="skeleton skeleton-text mb-2" style="width: 90%;"></div>
                                <div class="skeleton skeleton-text mb-2" style="width: 95%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="skeleton skeleton-text mb-3" style="width: 120px;"></div>
                                <hr>
                                <div class="skeleton skeleton-text mb-2" style="width: 100%;"></div>
                                <div class="skeleton skeleton-text mb-2" style="width: 85%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content State -->
            <div id="contentState" style="display: none;">
                <!-- Details Tab -->
                <div id="detailsPane" class="tab-pane active">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Request Information</h5>
                                    <i class="bi bi-info-circle text-muted"></i>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3" id="detailsContainer"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 d-flex flex-column gap-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Booking Details</h5>
                                    <i class="bi bi-calendar text-muted"></i>
                                </div>
                                <div class="card-body" id="eventDetails"></div>
                            </div>
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Attachments</h5>
                                    <i class="bi bi-paperclip text-muted"></i>
                                </div>
                                <div class="card-body" id="attachmentsContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Tab -->
                <div id="timelinePane" class="tab-pane">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Activity Timeline</h5>
                                    <div class="d-flex gap-2">
                                        <select id="timelineFilter" class="form-select form-select-sm" style="width: auto;">
                                            <option value="all">All</option>
                                            <option value="comment">Comments</option>
                                            <option value="fee">Fees</option>
                                            <option value="approval">Approvals</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary" id="refreshTimeline">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column" style="min-height: 400px;">
                                    <div id="timelineContent" class="flex-grow-1 overflow-auto" style="max-height: 350px;">
                                        <div class="text-center text-muted py-4">
                                            <div class="spinner-border spinner-border-sm"></div>
                                            <p class="small mb-0 mt-2">Loading...</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex gap-2">
                                            <input type="text" class="form-control" placeholder="Add a comment..."
                                                id="commentInput">
                                            <button class="btn btn-primary" id="sendCommentBtn">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Approval Status</h5>
                                    <span class="badge bg-primary" id="approvalCountBadge">0/0</span>
                                </div>
                                <div class="card-body overflow-auto p-2" id="approvalsContainer" style="max-height: 550px;">
                                    <div class="text-center text-muted py-4">
                                        <div class="spinner-border spinner-border-sm"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Approval</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to approve this request?</p>
                            <div class="mb-3">
                                <label class="form-label">Remarks (Optional)</label>
                                <textarea class="form-control" id="approveRemarks" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" id="confirmApprove">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Rejection</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to reject this request?</p>
                            <div class="mb-3">
                                <label class="form-label">Reason (Optional)</label>
                                <textarea class="form-control" id="rejectRemarks" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmReject">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finalize Modal -->
            <div class="modal fade" id="finalizeModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Finalize Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col">
                                    <div class="alert alert-success mb-0">
                                        <strong>Approvals:</strong> <span id="approvalCountFinalize">0</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="alert alert-danger mb-0">
                                        <strong>Rejections:</strong> <span id="rejectionCountFinalize">0</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-center fw-bold">Are you sure? This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmFinalize">Finalize</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mark Scheduled Modal -->
            <div class="modal fade" id="markScheduledModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Mark as Scheduled</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Official Receipt Number *</label>
                                <input type="text" class="form-control" id="officialReceiptNum" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmMarkScheduled">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Close Form Modal -->
            <div class="modal fade" id="closeFormModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Close Form</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <i class="bi bi-exclamation-triangle fs-1 text-danger mb-3 d-block"></i>
                            <p>Are you sure you want to close this form?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmCloseForm">Close Form</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Update Modal -->
            <div class="modal fade" id="statusUpdateModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Status Change</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="statusModalContent"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back to Top -->
            <button class="back-to-top" id="backToTop" title="Back to Top">
                <i class="bi bi-arrow-up"></i>
            </button>
        </div>
    </main>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/request-view.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pathParts = window.location.pathname.split('/');
            const requestId = pathParts[pathParts.length - 1];

            if (typeof RequestViewThin !== 'undefined') {
                RequestViewThin.init(requestId);
            } else {
                console.error('RequestViewThin module not loaded');
                document.getElementById('loadingState').innerHTML =
                    '<div class="alert alert-danger">Failed to load module. Please refresh.</div>';
            }
        });
    </script>
@endsection