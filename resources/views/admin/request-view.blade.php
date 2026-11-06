@extends('layouts.admin')
@section('title', 'Review Request')
@section('content')
    <style>
        /* Prevent the "Waived by" text from being struck through */
        .waived-by-text {
            text-decoration: none !important;
        }

        .table-bordered> :not(caption)>*>*:first-child {
            border-left: 0;
        }

        .table-bordered> :not(caption)>*>*:last-child {
            border-right: 0;
        }

        /* Request Tabs */
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

        .bg-danger-subtle {
            background-color: #f8d7da !important;
        }

        .bg-success-subtle {
            background-color: #d1e7dd !important;
        }

        .min-w-0 {
            min-width: 0;
        }

        /* Custom Approval Button */
        #approveBtn,
        #confirmApprove {
            background-color: #0c923d !important;
            color: white !important;
            font-weight: 700 !important;
            border: 1px solid #08732f !important;
            transition: background-color 0.2s ease-in-out,
                border-color 0.2s ease-in-out !important;
        }

        /* Hover Effect */
        #approveBtn:hover,
        #confirmApprove:hover {
            background-color: #08732f !important;
            border-color: #065a25 !important;
            color: white !important;
            box-shadow: none !important;
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

        /* Letter Reader */

        h6 {
            font-family: "Fraunces", Georgia, serif;
            color: var(--navy);
        }

        .letter-title {
            font-size: 1.25rem;
            font-family: "Fraunces", Georgia, serif;
            font-weight: 700;
            color: var(--navy);
        }

        .letter-meta {
            color: #6c757d;
            font-size: .9rem;
        }

        .letter-section {
            margin-bottom: 1.25rem;
        }

        .letter-section:last-child {
            margin-bottom: 0;
        }

        .letter-section-label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #416c97;
            margin-bottom: .35rem;
        }

        .letter-item {
            display: flex;
            justify-content: space-between;
            padding: .25rem 0;
        }

        .breadcrumb-back {
            transition: color 0.15s ease-in-out;
        }

        .breadcrumb-back:hover {
            color: #343a40 !important;
        }
    </style>

    <main id="main">
        <div class="view-container">
            <!-- Header Card -->
            <div class="card mb-3" style="overflow: visible;">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <!-- Left: breadcrumb + title + subtitle -->
                        <div class="flex-grow-1">
                            <nav aria-label="breadcrumb" class="mb-3">
                                <ol class="breadcrumb small mb-0">
                                    <li class="breadcrumb-item">
                                        <a href="/admin/pending-requests" class="text-decoration-none text-secondary">
                                            <i class="bi bi-arrow-left me-1"></i>Requests
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                                        Request #<span id="requestIdDisplay">--</span>
                                    </li>
                                </ol>
                            </nav>

                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                <h1 class="h3 fw-bold mb-0 text-dark">Request #<span id="requestIdDisplayMirror">--</span>
                                </h1>
                                <div id="statusBadgeContainer"></div>
                            </div>
                            <div id="requestSubtitle" class="text-muted small d-flex align-items-center gap-2 flex-wrap">
                            </div>
                        </div>

                        <!-- Right: actions (flex-shrink-0 keeps buttons from being squeezed) -->
                        <div class="d-flex align-items-center gap-2 flex-wrap pe-3 flex-shrink-0" id="actionButtonsTop">
                        </div>
                    </div>
                </div>
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
            <div id="loadingState" class="row g-3">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="skeleton mb-3" style="height:1.5rem;width:150px"></div>
                            <div class="skeleton mb-2" style="height:1rem"></div>
                            <div class="skeleton mb-2" style="height:1rem;width:90%"></div>
                            <div class="skeleton mb-2" style="height:1rem;width:95%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="skeleton mb-3" style="height:1.5rem;width:120px"></div>
                            <div class="skeleton mb-2" style="height:1rem"></div>
                            <div class="skeleton mb-2" style="height:1rem;width:85%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content State -->
            <div id="contentState" style="display: none;">
                <div class="tab-content">
                    <!-- Details Tab -->
                    <div id="detailsPane" class="tab-pane active">
                        {{-- Row 1: Letter body (left) | Booking Details (right) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body border-bottom" id="letterHeader"></div>
                                    <div class="card-body" id="letterBody">
                                        <div id="detailsContainer" class="d-none"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 d-flex flex-column gap-3">
                                {{-- Added flex-grow-1 so this card fills the column height --}}
                                <div class="card h-100 d-flex flex-column flex-grow-1">
                                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar"></i> Booking Details</h6>
                                    </div>
                                    {{-- Added flex-grow-1 so the body fills the card --}}
                                    <div class="card-body d-flex flex-column flex-grow-1" id="bookingDetails"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Event Resources Breakdown (full width) --}}
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap"
                                style="border-bottom: none;">
                                <h6 class="mb-0 fw-semibold">Event Resources Breakdown & Management</h6>

                                <div class="d-flex align-items-center gap-3 ms-auto">
                                    {{-- Waive All Switch --}}
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="waiveAllSwitch">
                                        <label class="form-check-label small fw-semibold text-muted" for="waiveAllSwitch"
                                            style="cursor: pointer;">Waive All</label>
                                    </div>

                                    {{-- Add Fee Button --}}
                                    <button class="btn btn-sm btn-outline-secondary" id="addFeeBtn">
                                        <i class="bi bi-plus-lg"></i> Add Fee/Discount
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0" id="resourcesBreakdown"></div>
                        </div>
                    </div>

                    <!-- Timeline Tab -->
                    <div id="timelinePane" class="tab-pane">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                <div class="card h-100">
                                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                                        <h6 class="mb-0 fw-semibold"> Activity Timeline</h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <select id="timelineFilter" class="form-select form-select-sm"
                                                style="width: auto;">
                                                <option value="all">All</option>
                                                <option value="comment">Comments</option>
                                                <option value="fee">Fees</option>
                                                <option value="waiver">Waivers</option>
                                                <option value="approval">Approvals</option>
                                            </select>
                                            <button class="btn btn-sm btn-outline-secondary" id="refreshTimeline">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column" style="min-height: 400px;">
                                        <div id="timelineContent" class="flex-grow-1 overflow-auto"
                                            style="max-height: 350px;">
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
                                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                                        <h6 class="mb-0 fw-semibold"> Approval Status</h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <span id="approvalCountBadge"
                                                class="ms-auto text-muted small fw-normal"></span>
                                        </div>
                                    </div>

                                    <div class="card-body overflow-auto p-2" id="approvalsContainer"
                                        style="max-height: 550px;">
                                        <div class="text-center text-muted py-4">
                                            <div class="spinner-border spinner-border-sm"></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generic Action Modal -->
            <div class="modal fade" id="actionModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="actionModalTitle"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="actionModalBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn" id="actionModalConfirm">Confirm</button>
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
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="feeForm">
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
                                        <label for="feeLabel" class="form-label">Label</label>
                                        <input type="text" id="feeLabel" class="form-control"
                                            placeholder="e.g. Sound System" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="accountNum" class="form-label">Account Number (Opt)</label>
                                        <input type="text" id="accountNum" class="form-control"
                                            placeholder="Account #">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="feeValue" class="form-label">Amount</label>
                                    <input type="number" id="feeValue" class="form-control" step="0.01"
                                        min="0.01" placeholder="0.00" required>
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

            <!-- Back to Top -->
            <button class="back-to-top" id="backToTop" title="Back to Top">
                <i class="bi bi-arrow-up"></i>
            </button>
        </div>
    </main>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/request-view.js') }}" defer></script>
@endsection
