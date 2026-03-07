{{-- resources/views/admin/form-review.blade.php --}}
@extends('layouts.admin')

@section('title', 'Form Review - REQ-' . str_pad($requestId, 5, '0', STR_PAD_LEFT))

@section('content')
    <style>
        /* Global performance optimizations */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* Mobile-first responsive typography */
        body {
            font-size: 16px;
            /* Base size for mobile */
        }

        /* Responsive font scaling with minimum sizes */
        @media (min-width: 576px) {
            body {
                font-size: 16.5px;
            }
        }

        @media (min-width: 768px) {
            body {
                font-size: 17px;
            }
        }

        @media (min-width: 992px) {
            body {
                font-size: 17.5px;
            }
        }

        @media (min-width: 1200px) {
            body {
                font-size: 18px;
            }
        }

        /* Overview Card Mobile Optimizations */
        @media (max-width: 767.98px) {

            /* Stack columns vertically on mobile */
            .row.g-3>[class*="col-"] {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }

            /* Reduce padding in cards */
            .card-body.p-3 {
                padding: 1rem !important;
            }

            /* Smaller font sizes for mobile */
            .card-header h6 {
                font-size: 1rem !important;
            }

            .small,
            small {
                font-size: 0.75rem !important;
                /* 12px */
            }

            .fs-6 {
                font-size: 0.875rem !important;
                /* 14px */
            }

            .fs-5 {
                font-size: 1rem !important;
                /* 16px */
            }

            /* Badge sizing */
            .badge.fs-6 {
                font-size: 0.875rem !important;
                padding: 0.5rem !important;
            }

            /* Document items */
            .document-item {
                padding: 0.5rem !important;
            }

            .document-item .fw-medium {
                font-size: 0.8125rem !important;
                /* 13px */
            }

            .document-item small {
                font-size: 0.6875rem !important;
                /* 11px */
                max-width: 120px !important;
            }

            .document-item i {
                font-size: 1.25rem !important;
                /* 20px */
            }

            /* Status section */
            .bg-light.p-3.rounded {
                padding: 0.75rem !important;
            }

            .bg-light.p-3.rounded .badge {
                font-size: 0.875rem !important;
                padding: 0.5rem 0.75rem !important;
            }

            /* Action buttons */
            .btn-sm {
                font-size: 0.75rem !important;
                /* 12px */
                padding: 0.25rem 0.5rem !important;
            }

            .btn-sm i {
                font-size: 0.875rem !important;
                /* 14px */
            }

            /* Column headers */
            .text-uppercase.text-muted {
                font-size: 0.6875rem !important;
                /* 11px */
                margin-bottom: 0.5rem !important;
            }

            /* Approval/Rejection counts */
            .row.g-2 .col-6 small {
                font-size: 0.6875rem !important;
                /* 11px */
            }

            .row.g-2 .col-6 .fw-medium {
                font-size: 0.8125rem !important;
                /* 13px */
            }

            /* Border spacing */
            .border-top {
                margin-top: 0.5rem !important;
                padding-top: 0.5rem !important;
            }

            /* Gap reductions */
            .gap-2 {
                gap: 0.375rem !important;
            }

            /* Modal optimizations for mobile */
            .modal-dialog.modal-xl {
                margin: 0.5rem !important;
                max-width: calc(100% - 1rem) !important;
            }

            .modal-body {
                height: 60vh !important;
            }

            .modal-footer .btn-sm {
                font-size: 0.6875rem !important;
                padding: 0.2rem 0.4rem !important;
            }

            /* Timeline button adjustment */
            .timeline-toggle {
                width: 50px !important;
                height: 50px !important;
            }

            .timeline-toggle i {
                font-size: 1.25rem !important;
            }
        }

        /* Small phones (below 400px) */
        @media (max-width: 399.98px) {
            .document-item .text-truncate {
                max-width: 100px !important;
            }

            .badge.fs-6 {
                font-size: 0.75rem !important;
                padding: 0.375rem !important;
            }

            .btn-sm {
                font-size: 0.6875rem !important;
                padding: 0.2rem 0.4rem !important;
            }

            .btn-sm i {
                font-size: 0.75rem !important;
            }
        }

        /* Tablet optimizations */
        @media (min-width: 768px) and (max-width: 991.98px) {

            /* Slight adjustments for tablets */
            .document-item .text-truncate {
                max-width: 120px !important;
            }

            .card-body.p-3 {
                padding: 1.25rem !important;
            }

            .badge.fs-6 {
                font-size: 0.875rem !important;
            }
        }

        /* Performance optimizations */
        .document-item {
            transition: background-color 0.15s ease, border-color 0.15s ease;
            will-change: background-color, border-color;
        }

        .hover-shadow {
            transition: box-shadow 0.15s ease;
            will-change: box-shadow;
        }

        /* Optimize animations */
        .btn,
        .badge,
        .card {
            transition: all 0.15s ease;
        }

        /* Reduce layout shifts */
        .min-w-0 {
            min-width: 0;
        }

        /* Optimize font rendering for better performance */
        .fw-medium,
        .fw-semibold,
        .fw-bold {
            font-weight: 500;
            -webkit-font-smoothing: antialiased;
        }

        /* Hardware acceleration for smooth scrolling */
        .modal-body,
        .card-body,
        .overflow-auto {
            -webkit-overflow-scrolling: touch;
        }

        /* Prevent text resize issues on mobile */
        html {
            -webkit-text-size-adjust: 100%;
            -moz-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        /* Optimize images for performance */
        img {
            content-visibility: auto;
        }

        /* Lazy loading optimization */
        [loading="lazy"] {
            background-color: #f8f9fa;
        }

        /* Reduce repaints on hover effects */
        @media (hover: hover) {
            .document-item:hover {
                background-color: #f0f7ff;
                border-color: #0d6efd !important;
            }

            .hover-shadow:hover {
                background-color: #f8f9fa;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        }

        /* Ensure columns stack on mobile - add this to your existing CSS */
        @media (max-width: 767.98px) {
            .mobile-stack>[class*="col-"] {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }
        }

        [v-cloak] {
            display: none !important;
        }

        .hover-shadow {
            transition: all 0.2s ease;
        }

        .hover-shadow:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .document-item {
            transition: all 0.2s ease;
        }

        .document-item:hover {
            background-color: #f0f7ff;
            border-color: #0d6efd !important;
        }
    </Style>
    <main id="main">
        <div class="container-fluid px-0" id="request-view-app">
            {{-- Loading State --}}
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading request details...</p>
            </div>

            {{-- Error State --}}
            <div v-else-if="error" class="alert alert-danger">
                @{{ error }}
            </div>
            {{-- Request Details --}}
            <div v-else class="row g-3 mobile-stack">
                {{-- REQ-{request_id} Overview Card --}}
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-2">
                            <h6 class="mb-0 fw-bold">REQ-@{{ formatRequestId(data.request_id) }} Overview</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                {{-- Documents Section with Previewer --}}
                                <div class="col-12 col-md-5">
                                    <h6 class="fw-semibold mb-2 small text-uppercase text-muted">Documents</h6>

                                    {{-- Document Preview Modal --}}
                                    <div class="modal fade" id="documentPreviewModal" tabindex="-1"
                                        aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-xl modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="documentPreviewModalLabel">
                                                        <i :class="selectedDocumentIcon" class="me-2"></i>
                                                        @{{ selectedDocumentTitle }}
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close" @click="clearModalContent"></button>
                                                </div>
                                                <div class="modal-body p-0 bg-light" style="height: 80vh;">
                                                    {{-- Loading State - shown while content loads --}}
                                                    <div v-if="modalLoading"
                                                        class="d-flex flex-column align-items-center justify-content-center h-100">
                                                        <div class="spinner-border text-primary mb-3" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="text-muted">Loading document...</p>
                                                    </div>

                                                    {{-- PDF Viewer - only rendered when needed --}}
                                                    <iframe v-else-if="selectedDocumentType === 'pdf'"
                                                        :src="selectedDocumentUrl + '#toolbar=0&navpanes=0'"
                                                        class="w-100 h-100 border-0" loading="lazy">
                                                    </iframe>

                                                    {{-- Image Viewer - only rendered when needed --}}
                                                    <div v-else-if="selectedDocumentType === 'image'"
                                                        class="d-flex align-items-center justify-content-center h-100">
                                                        <img :src="selectedDocumentUrl" :alt="selectedDocumentTitle"
                                                            class="img-fluid"
                                                            style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                                            loading="lazy">
                                                    </div>

                                                    {{-- Unsupported File Type - only rendered when needed --}}
                                                    <div v-else-if="!modalLoading"
                                                        class="d-flex flex-column align-items-center justify-content-center h-100">
                                                        <i class="bi bi-file-earmark-x display-1 text-muted mb-3"></i>
                                                        <h5 class="text-muted">Preview not available for this file type</h5>
                                                        <a :href="selectedDocumentUrl" target="_blank"
                                                            class="btn btn-primary mt-3">
                                                            <i class="bi bi-download me-2"></i>Download to View
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <small class="text-muted me-auto">
                                                        <i class="bi bi-file-earmark me-1"></i>
                                                        @{{ selectedDocumentFileName }}
                                                    </small>
                                                    <a :href="selectedDocumentUrl" target="_blank"
                                                        class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i>Open in New Tab
                                                    </a>
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                        data-bs-dismiss="modal" @click="clearModalContent">
                                                        Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Document List with Preview Triggers --}}
                                    <div class="d-flex flex-column gap-2">
                                        {{-- Formal Letter --}}
                                        <div v-if="data.documents?.formal_letter?.url"
                                            class="document-item d-flex align-items-center justify-content-between p-2 border rounded hover-shadow"
                                            @click="previewDocument('formal_letter', data.documents.formal_letter)"
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-text text-primary me-2 fs-5"></i>
                                                <div class="min-w-0">
                                                    <span class="fw-medium d-block small">Formal Letter</span>
                                                    <small class="text-muted text-truncate" style="max-width: 150px;">@{{
                                                        getFileName(data.documents.formal_letter.url) }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark ms-2 flex-shrink-0">@{{
                                                getFileType(data.documents.formal_letter.url) }}</span>
                                        </div>

                                        {{-- Facility Layout --}}
                                        <div v-if="data.documents?.facility_layout?.url"
                                            class="document-item d-flex align-items-center justify-content-between p-2 border rounded hover-shadow"
                                            @click="previewDocument('facility_layout', data.documents.facility_layout)"
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-diagram-3 text-success me-2 fs-5"></i>
                                                <div class="min-w-0">
                                                    <span class="fw-medium d-block small">Facility Layout</span>
                                                    <small class="text-muted text-truncate" style="max-width: 150px;">@{{
                                                        getFileName(data.documents.facility_layout.url) }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark ms-2 flex-shrink-0">@{{
                                                getFileType(data.documents.facility_layout.url) }}</span>
                                        </div>

                                        {{-- Proof of Payment --}}
                                        <div v-if="data.documents?.proof_of_payment?.url"
                                            class="document-item d-flex align-items-center justify-content-between p-2 border rounded hover-shadow"
                                            @click="previewDocument('proof_of_payment', data.documents.proof_of_payment)"
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-receipt text-warning me-2 fs-5"></i>
                                                <div class="min-w-0">
                                                    <span class="fw-medium d-block small">Proof of Payment</span>
                                                    <small class="text-muted text-truncate" style="max-width: 150px;">@{{
                                                        getFileName(data.documents.proof_of_payment.url) }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark ms-2 flex-shrink-0">@{{
                                                getFileType(data.documents.proof_of_payment.url) }}</span>
                                        </div>

                                        {{-- Official Receipt --}}
                                        <div v-if="data.documents?.official_receipt?.url"
                                            class="document-item d-flex align-items-center justify-content-between p-2 border rounded hover-shadow"
                                            @click="previewDocument('official_receipt', data.documents.official_receipt)"
                                            style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-receipt-cutoff text-info me-2 fs-5"></i>
                                                <div class="min-w-0">
                                                    <span class="fw-medium d-block small">Official Receipt</span>
                                                    <small class="text-muted text-truncate" style="max-width: 150px;">@{{
                                                        getFileName(data.documents.official_receipt.url) }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-dark ms-2 flex-shrink-0">@{{
                                                getFileType(data.documents.official_receipt.url) }}</span>
                                        </div>

                                        {{-- No Documents Message --}}
                                        <div v-if="!hasDocuments" class="text-muted small p-2">
                                            <i class="bi bi-receipt me-2"></i>No documents available
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Section --}}
                                <div class="col-6 col-md-3">
                                    <h6 class="fw-semibold mb-2 small text-uppercase text-muted">Status</h6>
                                    <div class="bg-light p-3 rounded">
                                        {{-- Status Badge with proper colors from database --}}
                                        <div class="d-flex align-items-center justify-content-center">
                                            <span class="badge fs-6 p-3 w-100 text-center fw-semibold"
                                                :style="getStatusBadgeStyles(data.form_details?.status?.color)">
                                                <i class="bi bi-circle-fill me-2"
                                                    :style="{ color: data.form_details?.status?.color }"
                                                    style="font-size: 0.6rem;"></i>
                                                @{{ data.form_details?.status?.name || 'Unknown Status' }}
                                            </span>
                                        </div>

                                        {{-- Additional Status Info - Removed request date and last updated --}}
                                        <div class="mt-3 pt-2 border-top">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Approvals</small>
                                                    <span class="fw-medium small">
                                                        <i class="bi bi-check-circle-fill text-success me-1"
                                                            style="font-size: 0.8rem;"></i>
                                                        @{{ data.approval_info?.approval_count || 0 }}
                                                    </span>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Rejections</small>
                                                    <span class="fw-medium small">
                                                        <i class="bi bi-x-circle-fill text-danger me-1"
                                                            style="font-size: 0.8rem;"></i>
                                                        @{{ data.approval_info?.rejection_count || 0 }}
                                                    </span>
                                                </div>
                                                <div v-if="data.status_tracking?.is_finalized" class="col-12 mt-2">
                                                    <small class="text-muted d-block">Finalized</small>
                                                    <span
                                                        class="badge bg-success bg-opacity-10 text-success border border-success mt-1">
                                                        <i class="bi bi-check-lg me-1"></i>Yes
                                                    </span>
                                                </div>
                                                <div v-if="data.status_tracking?.is_closed" class="col-12 mt-2">
                                                    <small class="text-muted d-block">Closed</small>
                                                    <span
                                                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary mt-1">
                                                        <i class="bi bi-lock me-1"></i>Yes
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Actions Column --}}
                                <div class="col-6 col-md-4">
                                    <h6 class="fw-semibold mb-2 small text-uppercase text-muted">Actions</h6>
                                    <div class="bg-light p-3 rounded">
                                        {{-- Loading State --}}
                                        <div v-if="loading" class="text-center py-3">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>

                                        {{-- Actions for Head Admin (All Actions) --}}
                                        <div v-else-if="userRole === 'Head Admin'" class="d-flex flex-column gap-2">
                                            <button class="btn btn-success btn-sm w-100" @click="handleApprove">
                                                <i class="bi bi-check-circle me-1"></i>Approve Request
                                            </button>
                                            <button class="btn btn-danger btn-sm w-100" @click="handleReject">
                                                <i class="bi bi-x-circle me-1"></i>Reject Request
                                            </button>
                                            <button class="btn btn-warning btn-sm w-100" @click="openFeeDiscountModal">
                                                <i class="bi bi-plus-circle me-1"></i>Add Fee/Discount
                                            </button>
                                            <button class="btn btn-info btn-sm w-100" @click="handleWaiveAllFees"
                                                :class="{ 'active': waiveAllFees }">
                                                <i class="bi bi-tags me-1"></i>
                                                <span v-if="waiveAllFees">Unwaive All Fees</span>
                                                <span v-else>Waive All Fees</span>
                                            </button>
                                        </div>

                                        {{-- Actions for VP Administration & Approving Officer (Approve/Reject only) --}}
                                        <div v-else-if="isVicePresidentOrApprovingOfficer" class="d-flex flex-column gap-2">
                                            <button class="btn btn-success btn-sm w-100" @click="handleApprove">
                                                <i class="bi bi-check-circle me-1"></i>Approve Request
                                            </button>
                                            <button class="btn btn-danger btn-sm w-100" @click="handleReject">
                                                <i class="bi bi-x-circle me-1"></i>Reject Request
                                            </button>
                                        </div>

                                        {{-- Actions for other roles (Minimal or Read-only) --}}
                                        <div v-else class="d-flex flex-column gap-2">
                                            <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-eye me-1"></i>View Only
                                            </button>
                                            <small class="text-muted text-center d-block mt-1">No actions available</small>
                                        </div>

                                        {{-- Additional Action Info --}}
                                        <div class="mt-2 pt-2 border-top small">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Your Role:</span>
                                                <span class="fw-medium">@{{ userRole || 'Loading...' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                {{-- User Details Card --}}
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-bold">User Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Name</small>
                                    <span>@{{ data.user_details?.first_name }} @{{ data.user_details?.last_name }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">User Type</small>
                                    <span>@{{ data.user_details?.user_type }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Email</small>
                                    <span>@{{ data.user_details?.email }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Contact</small>
                                    <span>@{{ data.user_details?.contact_number }}</span>
                                </div>
                                <div v-if="data.user_details?.school_id" class="col-6">
                                    <small class="text-muted d-block">School ID</small>
                                    <span>@{{ data.user_details?.school_id }}</span>
                                </div>
                                <div v-if="data.user_details?.organization_name" class="col-6">
                                    <small class="text-muted d-block">Organization</small>
                                    <span>@{{ data.user_details?.organization_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Event & Form Details Card --}}
                <div class="col-12 mobile-stack">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Event & Form Details</h6>
                            <a href="{{ url('/admin/calendar') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-calendar-event me-1"></i>Open Calendar
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Date & Time</small>
                                            <span>@{{ data.schedule?.display }}</span>
                                        </div>
                                        <div v-if="data.schedule?.is_multi_day" class="col-12">
                                            <small class="text-muted d-block">Multi-day Event</small>
                                            <span>@{{ data.schedule?.formatted?.start }} to @{{
                                                data.schedule?.formatted?.end }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Calendar Title</small>
                                            <span>@{{ data.form_details?.calendar_info?.title || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Participants</small>
                                            <span>@{{ data.form_details?.num_participants }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tables/Chairs</small>
                                            <span>@{{ data.form_details?.num_tables }} tables, @{{
                                                data.form_details?.num_chairs }} chairs</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Endorser</small>
                                            <span>@{{ data.documents?.endorser || 'N/A' }}</span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Date Endorsed</small>
                                            <span>@{{ formatDate(data.documents?.date_endorsed) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Purpose</small>
                                            <span>@{{ data.form_details?.purpose }}</span>
                                        </div>
                                        <div v-if="data.form_details?.additional_requests" class="col-12">
                                            <small class="text-muted d-block">Additional Requests</small>
                                            <span>@{{ data.form_details?.additional_requests }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Requested Items Card --}}
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Requested Resource Breakdown</h6>
                            <div class="d-flex align-items-center gap-2">
                                {{-- Waive All Fees Toggle --}}
                                <div class="d-flex align-items-center me-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="waiveAllFees"
                                            v-model="waiveAllFees" @change="handleWaiveAllFees">
                                        <label class="form-check-label small" for="waiveAllFees">Waive all fees</label>
                                    </div>
                                </div>

                                {{-- Add Fee/Discount Button --}}
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="openFeeDiscountModal">
                                    <i class="bi bi-plus-circle me-1"></i>Add Fee/Discount
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            {{-- Breakdown --}}
                            <div v-if="data.fees?.breakdown?.facilities?.length" class="mb-3">
                                <h6 class="fw-semibold mb-2">Facilities</h6>
                                <div v-for="item in data.fees.breakdown.facilities" :key="item.name"
                                    class="d-flex justify-content-between small mb-1 align-items-center">
                                    <div class="d-flex align-items-center">
                                        {{-- Facility Checkbox --}}
                                        <div class="form-check me-2">
                                            <input class="form-check-input" type="checkbox"
                                                :id="'facility_' + item.name.replace(/\s+/g, '_')" :value="item"
                                                v-model="selectedFacilities"
                                                @change="handleItemSelection('facility', item)">
                                        </div>
                                        <label :for="'facility_' + item.name.replace(/\s+/g, '_')" class="form-check-label">
                                            @{{ item.name }} (@{{ item.duration_text }})
                                        </label>
                                    </div>
                                    <span>@{{ formatMoney(item.fee) }}</span>
                                </div>
                            </div>

                            <div v-if="data.fees?.breakdown?.equipment?.length" class="mb-3">
                                <h6 class="fw-semibold mb-2">Equipment</h6>
                                <div v-for="item in data.fees.breakdown.equipment" :key="item.name"
                                    class="d-flex justify-content-between small mb-1 align-items-center">
                                    <div class="d-flex align-items-center">
                                        {{-- Equipment Checkbox --}}
                                        <div class="form-check me-2">
                                            <input class="form-check-input" type="checkbox"
                                                :id="'equipment_' + item.name.replace(/\s+/g, '_')" :value="item"
                                                v-model="selectedEquipment"
                                                @change="handleItemSelection('equipment', item)">
                                        </div>
                                        <label :for="'equipment_' + item.name.replace(/\s+/g, '_')"
                                            class="form-check-label">
                                            @{{ item.name }} (@{{ item.duration_text }})
                                        </label>
                                    </div>
                                    <span>@{{ formatMoney(item.fee) }}</span>
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="border-top pt-2 mt-2">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Approved Fee</span>
                                    <span>@{{ formatMoney(data.fees?.approved_fee) }}</span>
                                </div>
                                <div v-if="data.fees?.is_late" class="text-danger small mt-1">
                                    Late Penalty: @{{ formatMoney(data.fees?.late_penalty_fee) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br><br>
    </main>
@endsection
{{-- Activity Timeline Floating Button --}}
<div class="position-fixed bottom-0 end-0 m-4" style="z-index: 1050;" id="timeline-app" v-cloak v-if="!loading">
    {{-- Minimized State --}}
    <div v-if="!isExpanded" @click="isExpanded = true"
        class="timeline-toggle btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center"
        style="width: 60px; height: 60px; cursor: pointer;">
        <i class="fa-regular fa-comment-dots fs-3"></i>
        <span v-if="hasActivity" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
            style="font-size: 0.6rem;">
            @{{ activityCount }}
        </span>
    </div>

    {{-- Expanded State --}}
    <div v-else class="card shadow-lg" style="width: 350px; max-width: calc(100vw - 2rem);">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold">Activity Timeline</h6>
            <button @click="isExpanded = false" class="btn btn-sm btn-link text-muted p-0 border-0">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>
        </div>

        <div class="card-body" style="height: 400px; overflow-y: auto;">
            {{-- DUMMY EXAMPLES - Remove when actual data is available --}}
            <div class="d-flex flex-column gap-3 mb-4">
                {{-- Dummy Approval --}}
                <div class="d-flex gap-2">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-light"
                            style="width: 40px; height: 40px; border: 2px solid #28a745;">
                            <img src="https://ui-avatars.com/api/?name=John+Smith&background=0D6EFD&color=fff&size=40"
                                alt="John Smith" class="w-100 h-100 object-fit-cover">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3 position-relative"
                            style="border-radius: 18px 18px 18px 4px !important;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold small">John Smith</span>
                                <small class="text-muted" style="font-size: 0.7rem;">2m ago</small>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <span class="text-success small fw-semibold">Approved</span>
                            </div>
                            <div class="text-dark small">
                                "Looks good to me. All requirements are complete."
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dummy Rejection --}}
                <div class="d-flex gap-2">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-light"
                            style="width: 40px; height: 40px; border: 2px solid #dc3545;">
                            <img src="https://ui-avatars.com/api/?name=Jane+Doe&background=DC3545&color=fff&size=40"
                                alt="Jane Doe" class="w-100 h-100 object-fit-cover">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3 position-relative"
                            style="border-radius: 18px 18px 18px 4px !important;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold small">Jane Doe</span>
                                <small class="text-muted" style="font-size: 0.7rem;">1h ago</small>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="bi bi-x-circle-fill text-danger" style="font-size: 0.8rem;"></i>
                                <span class="text-danger small fw-semibold">Rejected</span>
                            </div>
                            <div class="text-dark small">
                                "Missing required documents. Please resubmit with complete requirements."
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dummy Approval with no remarks --}}
                <div class="d-flex gap-2">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-light"
                            style="width: 40px; height: 40px; border: 2px solid #28a745;">
                            <img src="https://ui-avatars.com/api/?name=Mike+Wilson&background=198754&color=fff&size=40"
                                alt="Mike Wilson" class="w-100 h-100 object-fit-cover">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3 position-relative"
                            style="border-radius: 18px 18px 18px 4px !important;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold small">Mike Wilson</span>
                                <small class="text-muted" style="font-size: 0.7rem;">3h ago</small>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <span class="text-success small fw-semibold">Approved</span>
                            </div>
                            <div class="text-muted small fst-italic">
                                No remarks provided
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Divider between dummy and real data --}}
            <hr class="my-3">

            {{-- Empty State --}}
            <div v-if="!data?.approval_info?.approvals?.length && !data?.approval_info?.rejections?.length"
                class="text-center py-5">
                <i class="bi bi-chat-dots fs-1 text-muted mb-3"></i>
                <p class="text-muted mb-0">No action has been taken yet.</p>
            </div>

            {{-- Real Timeline Items --}}
            <div v-else class="d-flex flex-column gap-3">
                {{-- Approvals --}}
                <div v-for="approval in data?.approval_info?.approvals" :key="approval.id" class="d-flex gap-2">
                    {{-- Profile Circle with Image --}}
                    <div class="flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-light"
                            style="width: 40px; height: 40px; border: 2px solid #28a745;">
                            <img :src="approval.admin_photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(approval.admin_name) + '&background=0D6EFD&color=fff&size=40'"
                                :alt="approval.admin_name" class="w-100 h-100 object-fit-cover">
                        </div>
                    </div>

                    {{-- Chat Bubble --}}
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3 position-relative"
                            style="border-radius: 18px 18px 18px 4px !important;">
                            {{-- Admin Name and Time --}}
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold small">@{{ approval.admin_name }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">@{{
                                    formatTimeAgo(approval.created_at) }}</small>
                            </div>

                            {{-- Approval Indicator --}}
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                                <span class="text-success small fw-semibold">Approved</span>
                            </div>

                            {{-- Remarks --}}
                            <div v-if="approval.remarks" class="text-dark small">
                                "@{{ approval.remarks }}"
                            </div>
                            <div v-else class="text-muted small fst-italic">
                                No remarks provided
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rejections --}}
                <div v-for="rejection in data?.approval_info?.rejections" :key="rejection.id" class="d-flex gap-2">
                    {{-- Profile Circle with Image --}}
                    <div class="flex-shrink-0">
                        <div class="rounded-circle overflow-hidden bg-light"
                            style="width: 40px; height: 40px; border: 2px solid #dc3545;">
                            <img :src="rejection.admin_photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(rejection.admin_name) + '&background=DC3545&color=fff&size=40'"
                                :alt="rejection.admin_name" class="w-100 h-100 object-fit-cover">
                        </div>
                    </div>

                    {{-- Chat Bubble --}}
                    <div class="flex-grow-1">
                        <div class="bg-light rounded-3 p-3 position-relative"
                            style="border-radius: 18px 18px 18px 4px !important;">
                            {{-- Admin Name and Time --}}
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-semibold small">@{{ rejection.admin_name }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">@{{
                                    formatTimeAgo(rejection.created_at) }}</small>
                            </div>

                            {{-- Rejection Indicator --}}
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <i class="bi bi-x-circle-fill text-danger" style="font-size: 0.8rem;"></i>
                                <span class="text-danger small fw-semibold">Rejected</span>
                            </div>

                            {{-- Remarks --}}
                            <div v-if="rejection.remarks" class="text-dark small">
                                "@{{ rejection.remarks }}"
                            </div>
                            <div v-else class="text-muted small fst-italic">
                                No remarks provided
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Messenger-like Input --}}
        <div class="card-footer bg-white border-top p-3">
            <div class="input-group">
                <input type="text" class="form-control border-end-0" placeholder="Type your message..."
                    v-model="commentInput" @keyup.enter="sendComment">
                <button class="btn btn-primary" type="button" @click="sendComment">
                    <i class="fa-regular fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        // Define global helper functions at the top of your scripts section
        window.helpers = {
            formatTimeAgo(dateString) {
                if (!dateString) return '';

                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMins / 60);
                const diffDays = Math.floor(diffHours / 24);

                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;

                return date.toLocaleDateString();
            },

            formatMoney(amount) {
                if (amount === null || amount === undefined) return '₱0.00';
                const num = parseFloat(amount);
                if (isNaN(num)) return '₱0.00';
                return '₱' + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            },

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                return new Date(dateString).toLocaleDateString();
            },

            formatRequestId(id) {
                if (!id) return '00000';
                return id.toString().padStart(5, '0');
            }
        };

        // Create a shared event bus for communication if needed
        window.eventBus = new Vue();

        // Main app Vue instance
        new Vue({
            el: '#request-view-app',
            data: {
                requestId: parseInt('{{ $requestId }}'),
                data: null,
                loading: true,
                error: null,
                // New data properties for the requested features
                waiveAllFees: false,
                selectedFacilities: [],
                selectedEquipment: [],

                // Check what the admin's role is . remove in production, only for testing.
                userRole: null,
                userRoles: [],

                // Document preview properties - optimized
                selectedDocumentUrl: '',
                selectedDocumentTitle: '',
                selectedDocumentFileName: '',
                selectedDocumentType: '',
                selectedDocumentIcon: '',
                modalLoading: false, // New loading state for modal
                modalInstance: null, // Cache modal instance
            },
            mounted() {
                this.fetchRequestDetails();
                this.getUserRole();

                // Pre-initialize modal on mount
                this.$nextTick(() => {
                    const modalElement = document.getElementById('documentPreviewModal');
                    if (modalElement && typeof bootstrap !== 'undefined') {
                        this.modalInstance = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                        });
                    }
                });
            },
            computed: {
                hasDocuments() {
                    return this.data?.documents && (
                        this.data.documents.formal_letter?.url ||
                        this.data.documents.facility_layout?.url ||
                        this.data.documents.proof_of_payment?.url ||
                        this.data.documents.official_receipt?.url
                    );
                },

                // Check if user is Vice President of Administration or Approving Officer. TESTING ONLY. REMOVE IN PRODUCTION.
                isVicePresidentOrApprovingOfficer() {
                    return this.userRole === 'Vice President of Administration' ||
                        this.userRole === 'Approving Officer';
                },
            },
            methods: {
                fetchRequestDetails() {
                    const token = localStorage.getItem('adminToken');

                    if (!token) {
                        this.error = 'No authentication token found';
                        this.loading = false;
                        return;
                    }

                    fetch(`/api/admin/requisition-forms/${this.requestId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch request details');
                            }
                            return response.json();
                        })
                        .then(data => {
                            this.data = data;
                            this.loading = false;
                            // Emit event with data for timeline
                            window.eventBus.$emit('data-loaded', data);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.error = error.message;
                            this.loading = false;
                        });
                },

                // New method for status badge styles
                getStatusBadgeStyles(color) {
                    if (!color) {
                        return {
                            backgroundColor: '#e9ecef',
                            color: '#495057',
                            border: '1px solid #ced4da'
                        };
                    }

                    // Parse RGB color to create lighter background
                    let rgbValues;
                    if (color.startsWith('rgb')) {
                        // Extract RGB values from rgb(r, g, b) format
                        rgbValues = color.match(/\d+/g);
                    } else if (color.startsWith('#')) {
                        // Convert hex to RGB
                        const hex = color.replace('#', '');
                        if (hex.length === 3) {
                            rgbValues = [
                                parseInt(hex[0] + hex[0], 16),
                                parseInt(hex[1] + hex[1], 16),
                                parseInt(hex[2] + hex[2], 16)
                            ];
                        } else {
                            rgbValues = [
                                parseInt(hex.substring(0, 2), 16),
                                parseInt(hex.substring(2, 4), 16),
                                parseInt(hex.substring(4, 6), 16)
                            ];
                        }
                    }

                    if (rgbValues && rgbValues.length >= 3) {
                        // Create lighter background (85% opacity version)
                        return {
                            backgroundColor: `rgba(${rgbValues[0]}, ${rgbValues[1]}, ${rgbValues[2]}, 0.15)`,
                            color: color,
                            border: `1px solid ${color}`
                        };
                    }

                    // Fallback
                    return {
                        backgroundColor: '#e9ecef',
                        color: '#495057',
                        border: '1px solid #ced4da'
                    };
                },

                // New method to get user role from authentication
                getUserRole() {
                    try {
                        // Try to get from localStorage first (if stored during login)
                        const userData = localStorage.getItem('adminUser');
                        if (userData) {
                            const user = JSON.parse(userData);
                            this.userRole = user.role_title;
                            this.userRoles = user.roles || [];
                        } else {
                            // Fallback to a default or fetch from API
                            this.userRole = 'Head Admin'; // For testing - remove in production
                            this.userRoles = [{ role_title: 'Head Admin' }];
                        }
                    } catch (e) {
                        console.error('Error getting user role:', e);
                        this.userRole = 'Unknown';
                    }
                },

                // Action handlers
                handleApprove() {
                    // TODO: Implement approve logic
                    console.log('Approve request:', this.requestId);
                    // You can use SweetAlert or Bootstrap modal for confirmation
                    if (confirm('Are you sure you want to approve this request?')) {
                        // API call here
                        alert('Request approved successfully!');
                    }
                },

                handleReject() {
                    // TODO: Implement reject logic
                    console.log('Reject request:', this.requestId);
                    // You can use SweetAlert or Bootstrap modal for confirmation with reason
                    const reason = prompt('Please provide a reason for rejection:');
                    if (reason) {
                        // API call here
                        alert('Request rejected successfully!');
                    }
                },

                // Optimized document preview method
                previewDocument(docType, doc) {
                    // Clear previous content first
                    this.clearModalContent();

                    // Set basic info immediately (lightweight)
                    this.selectedDocumentTitle = this.getDocumentTitle(docType);
                    this.selectedDocumentIcon = this.getDocumentIcon(docType);
                    this.selectedDocumentFileName = this.getFileName(doc.url);
                    this.selectedDocumentType = this.getFileType(doc.url).toLowerCase();

                    // Show loading state
                    this.modalLoading = true;

                    // Show modal immediately with loading state
                    if (this.modalInstance) {
                        this.modalInstance.show();
                    } else {
                        // Fallback if modal not initialized
                        const modalElement = document.getElementById('documentPreviewModal');
                        if (modalElement && typeof bootstrap !== 'undefined') {
                            this.modalInstance = new bootstrap.Modal(modalElement);
                            this.modalInstance.show();
                        }
                    }

                    // Load the actual content in the background
                    setTimeout(() => {
                        this.selectedDocumentUrl = doc.url;
                        this.modalLoading = false;
                    }, 50); // Small delay to ensure modal is shown first
                },

                // Helper methods for document metadata (lightweight, no DOM manipulation)
                getDocumentTitle(docType) {
                    const titles = {
                        'formal_letter': 'Formal Letter',
                        'facility_layout': 'Facility Layout',
                        'proof_of_payment': 'Proof of Payment',
                        'official_receipt': 'Official Receipt'
                    };
                    return titles[docType] || 'Document Preview';
                },

                getDocumentIcon(docType) {
                    const icons = {
                        'formal_letter': 'bi-file-text text-primary',
                        'facility_layout': 'bi-diagram-3 text-success',
                        'proof_of_payment': 'bi-receipt text-warning',
                        'official_receipt': 'bi-receipt-cutoff text-info'
                    };
                    return icons[docType] || 'bi-file-earmark';
                },

                // Clear modal content when closed (frees memory)
                clearModalContent() {
                    this.selectedDocumentUrl = '';
                    this.modalLoading = false;
                },

                getFileName(url) {
                    if (!url) return 'No file';
                    try {
                        const parts = url.split('/');
                        let filename = parts[parts.length - 1].split('?')[0]; // Remove query params
                        filename = decodeURIComponent(filename);

                        // Truncate if too long
                        if (filename.length > 30) {
                            const ext = filename.split('.').pop();
                            const name = filename.substring(0, 26 - ext.length);
                            return name + '...' + ext;
                        }
                        return filename;
                    } catch (e) {
                        return 'File';
                    }
                },

                getFileType(url) {
                    if (!url) return 'Unknown';
                    try {
                        const extension = url.split('.').pop().split('?')[0].toLowerCase();
                        const imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
                        const pdfTypes = ['pdf'];

                        if (imageTypes.includes(extension)) {
                            return 'IMAGE';
                        } else if (pdfTypes.includes(extension)) {
                            return 'PDF';
                        } else {
                            return extension.toUpperCase();
                        }
                    } catch (e) {
                        return 'Unknown';
                    }
                },

                openFeeDiscountModal() {
                    console.log('Open add fee/discount modal');
                },

                handleWaiveAllFees() {
                    console.log('Waive all fees:', this.waiveAllFees);
                    if (this.waiveAllFees) {
                        this.selectedFacilities = this.data?.fees?.breakdown?.facilities ? [...this.data.fees.breakdown.facilities] : [];
                        this.selectedEquipment = this.data?.fees?.breakdown?.equipment ? [...this.data.fees.breakdown.equipment] : [];
                    } else {
                        this.selectedFacilities = [];
                        this.selectedEquipment = [];
                    }
                },

                handleItemSelection(type, item) {
                    console.log(`Selected ${type}:`, item,
                        type === 'facility' ? this.selectedFacilities : this.selectedEquipment);

                    const totalFacilities = this.data?.fees?.breakdown?.facilities?.length || 0;
                    const totalEquipment = this.data?.fees?.breakdown?.equipment?.length || 0;
                    const totalItems = totalFacilities + totalEquipment;
                    const selectedTotal = this.selectedFacilities.length + this.selectedEquipment.length;

                    if (selectedTotal === totalItems && totalItems > 0) {
                        this.waiveAllFees = true;
                    } else if (this.waiveAllFees && selectedTotal < totalItems) {
                        this.waiveAllFees = false;
                    }
                },

                // Use global helpers
                formatTimeAgo: (date) => window.helpers.formatTimeAgo(date),
                formatMoney: (amount) => window.helpers.formatMoney(amount),
                formatRequestId: (id) => window.helpers.formatRequestId(id),
                formatDate: (date) => window.helpers.formatDate(date),
            }
        });
        // Timeline Vue instance
        new Vue({
            el: '#timeline-app',
            data: {
                requestId: parseInt('{{ $requestId }}'),
                data: null,
                loading: true,
                isExpanded: false,
                commentInput: '',
                activeTab: 'all',
                tabLoading: false,
                approvalsLoaded: false,
                feesLoaded: false,
                commentsLoaded: false,
                feeActivities: [],
                comments: [],
            },
            mounted() {
                // Listen for data from main app
                window.eventBus.$on('data-loaded', (data) => {
                    this.data = data;
                    this.loading = false;
                });

                // If data hasn't been loaded yet, fetch it directly
                if (!this.data) {
                    this.fetchRequestDetails();
                }
            },
            computed: {
                hasActivity() {
                    return (this.data?.approval_info?.approvals?.length > 0 ||
                        this.data?.approval_info?.rejections?.length > 0);
                },
                activityCount() {
                    return (this.data?.approval_info?.approvals?.length || 0) +
                        (this.data?.approval_info?.rejections?.length || 0);
                },
                commentCount() {
                    return this.comments.length;
                },
            },
            methods: {
                fetchRequestDetails() {
                    const token = localStorage.getItem('adminToken');

                    if (!token) {
                        this.loading = false;
                        return;
                    }

                    fetch(`/api/admin/requisition-forms/${this.requestId}`, {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to fetch request details');
                            }
                            return response.json();
                        })
                        .then(data => {
                            this.data = data;
                            this.loading = false;
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.loading = false;
                        });
                },

                sendComment() {
                    if (!this.commentInput.trim()) return;
                    // TODO: Implement your API call to send comment
                    console.log('Sending comment:', this.commentInput);
                    this.commentInput = '';
                },

                loadTab(tab) {
                    if (this[`${tab}Loaded`]) return;

                    this.tabLoading = true;

                    // Simulate lazy loading (replace with actual API calls)
                    setTimeout(() => {
                        switch (tab) {
                            case 'approvals':
                                // Load approvals data
                                this.approvalsLoaded = true;
                                break;
                            case 'fees':
                                // Load fees data
                                this.feeActivities = [
                                    {
                                        id: 1,
                                        description: 'Tentative fee calculated',
                                        amount: 7600,
                                        created_at: new Date().toISOString()
                                    },
                                    {
                                        id: 2,
                                        description: 'Late penalty applied',
                                        amount: 500,
                                        created_at: new Date().toISOString()
                                    }
                                ];
                                this.feesLoaded = true;
                                break;
                            case 'comments':
                                // Load comments data
                                this.comments = [
                                    {
                                        id: 1,
                                        admin_name: 'John Smith',
                                        admin_photo: null,
                                        message: 'Please review the attached documents.',
                                        created_at: new Date().toISOString()
                                    }
                                ];
                                this.commentsLoaded = true;
                                break;
                        }
                        this.tabLoading = false;
                    }, 500);
                },

                // Use global helpers
                formatTimeAgo: (date) => window.helpers.formatTimeAgo(date),
                formatMoney: (amount) => window.helpers.formatMoney(amount),
                formatDate: (date) => window.helpers.formatDate(date),
            }
        });
    </script>
@endsection