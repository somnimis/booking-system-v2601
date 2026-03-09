{{-- resources/views/admin/asset-tracking.blade.php --}}
@extends('layouts.admin')

@section('title', 'Asset Tracking')

@section('content')
    <main id="main">
                {{-- UNDER CONSTRUCTION DISCLAIMER --}}
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" style="border-left: 4px solid #856404;">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fa-xl me-3"></i>
                <div>
                    <strong class="fw-semibold">🚧 Under Construction</strong>
                    <p class="mb-1">This page is still being developed and is for viewing purposes only. Some features may not work as expected.</p>
                    <p class="mb-0 small">
                        <i class="fa-regular fa-lightbulb me-1"></i>
                        For any suggestions or feedback,
                        <a href="https://booking-system-v2601.onrender.com/user-feedback" target="_blank" class="alert-link fw-semibold">
                            please submit your ideas here <i class="fa-solid fa-arrow-up-right-from-s ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="container-fluid mb-2" id="assetTrackingApp">
            {{-- Quick Stats Row --}}
            <div class="row g-4 mb-4" id="statsContainer">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                    <i class="fa-solid fa-rotate fa-xl text-primary"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Active Transactions</h6>
                                    <h4 class="mb-0 fw-bold text-center" id="activeTransactionsCount">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                    <i class="fa-solid fa-right-from-bracket fa-xl text-success"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Released Today</h6>
                                    <h4 class="mb-0 fw-bold text-center" id="releasedTodayCount">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                                    <i class="fa-solid fa-right-to-bracket fa-xl text-warning"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Returned Today</h6>
                                    <h4 class="mb-0 fw-bold text-center" id="returnedTodayCount">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                                    <i class="fa-solid fa-clock fa-xl text-info"></i>
                                </div>
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Pending Returns</h6>
                                    <h4 class="mb-0 fw-bold text-center" id="pendingReturnsCount">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- To Return Card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-semibold">
                            To Return
                            <span class="badge bg-warning ms-2" id="toReturnCount">0</span>
                        </h5>
                        <small class="text-muted">Items currently checked out</small>
                    </div>
                    <button class="btn btn-primary" id="beginTransactionBtn" data-bs-toggle="modal"
                        data-bs-target="#beginTransactionModal">
                        <i class="fa-solid fa-qrcode me-2"></i>
                        Begin Transaction
                    </button>
                </div>
                <div class="card-body p-0">
                    {{-- Loading State --}}
                    <div id="transactionsLoading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading transactions...</p>
                    </div>

                    {{-- Error State --}}
                    <div id="transactionsError" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-circle-exclamation fa-3x text-danger mb-3"></i>
                        <p class="text-muted">Failed to load transactions. Please try again.</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="loadOngoingTransactions()">
                            <i class="fa-solid fa-rotate me-2"></i>Retry
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div id="transactionsEmpty" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No ongoing transactions found.</p>
                    </div>

                    {{-- Transactions List --}}
                    <div class="list-group list-group-flush" id="ongoingTransactionsList" style="display: none;"></div>

                    {{-- Load More Button --}}
                    <div id="loadMoreContainer" class="p-3 text-center border-top" style="display: none;">
                        <button class="btn btn-light btn-sm px-4" id="loadMoreBtn" onclick="loadMoreTransactions()">
                            <i class="fa-regular fa-arrow-down me-2"></i>
                            Load More Transactions
                        </button>
                    </div>
                </div>
            </div>

            {{-- To Release Container --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div>
                        <h5 class="mb-0 fw-semibold">
                            To Release
                            <span class="badge bg-primary ms-2" id="toReleaseCount">0</span>
                        </h5>
                        <small class="text-muted">Requested equipment waiting for release</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    {{-- Loading State --}}
                    <div id="toReleaseLoading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading requested equipment...</p>
                    </div>

                    {{-- Error State --}}
                    <div id="toReleaseError" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-circle-exclamation fa-3x text-danger mb-3"></i>
                        <p class="text-muted">Failed to load requested equipment. Please try again.</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="loadToRelease()">
                            <i class="fa-solid fa-rotate me-2"></i>Retry
                        </button>
                    </div>

                    {{-- Empty State --}}
                    <div id="toReleaseEmpty" class="text-center py-5" style="display: none;">
                        <i class="fa-solid fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No pending equipment requests to release.</p>
                    </div>

                    {{-- To Release List --}}
                    <div class="table-responsive" id="toReleaseList" style="display: none;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Requester</th>
                                    <th>Equipment</th>
                                    <th class="text-center">Qty</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="toReleaseTableBody"></tbody>
                        </table>
                    </div>

                    {{-- Load More Button --}}
                    <div id="toReleaseLoadMoreContainer" class="p-3 text-center border-top" style="display: none;">
                        <button class="btn btn-light btn-sm px-4" id="toReleaseLoadMoreBtn" onclick="loadMoreToRelease()">
                            <i class="fa-regular fa-arrow-down me-2"></i>
                            Load More
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Release Item Modal --}}
        <div class="modal fade" id="releaseItemModal" tabindex="-1" aria-labelledby="releaseItemModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="releaseItemModalLabel">
                            <i class="fa-solid fa-hand-back-point-up me-2" style="color: var(--cpu-primary);"></i>
                            Release Equipment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Loading State --}}
                        <div id="releaseModalLoading" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading available items...</p>
                        </div>

                        {{-- Request Info Card --}}
                        <div id="releaseRequestInfo" class="card bg-light border-0 mb-4" style="display: none;">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Requester</small>
                                        <span id="releaseRequesterName" class="fw-semibold"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Organization</small>
                                        <span id="releaseOrganization"></span>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Equipment Requested</small>
                                        <span id="releaseEquipmentName" class="fw-semibold"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Quantity Requested</small>
                                        <span id="releaseQuantityRequested" class="badge bg-primary"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Available Items List --}}
                        <div id="releaseItemsContainer" style="display: none;">
                            <h6 class="fw-semibold mb-3">Select Item to Release</h6>
                            <div class="row g-3" id="availableItemsGrid"></div>
                        </div>

                        {{-- No Items Available --}}
                        <div id="noItemsAvailable" class="text-center py-5" style="display: none;">
                            <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No available items found for this equipment type.</p>
                            <p class="small text-muted">Please check equipment inventory or mark items as available.</p>
                        </div>

                        {{-- Release Form (shown after item selection) --}}
                        <div id="releaseFormContainer" style="display: none;">
                            <h6 class="fw-semibold mb-3">Release Details</h6>

                            {{-- Selected Item Summary --}}
                            <div class="alert alert-info d-flex align-items-center mb-3" id="selectedItemSummary">
                                <i class="fa-solid fa-circle-check me-2"></i>
                                <span id="selectedItemName"></span>
                                <small class="text-muted ms-2" id="selectedItemBarcode"></small>
                            </div>

                            {{-- Destination --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fa-regular fa-building me-1"></i>
                                    Destination
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <select class="form-select" id="releaseFacilitySelect">
                                            <option value="">Select a facility...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="releaseManualDestination"
                                            placeholder="Or enter manual location...">
                                    </div>
                                </div>
                            </div>

                            {{-- Condition --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fa-regular fa-clipboard me-1"></i>
                                    Equipment Condition
                                </label>
                                <div class="d-flex gap-2 flex-wrap" id="releaseConditionRadios">
                                    <!-- Will be populated dynamically -->
                                </div>
                            </div>

                            {{-- Release Notes --}}
                            <div class="mb-3">
                                <label class="form-label fw-medium">
                                    <i class="fa-regular fa-note-sticky me-1"></i>
                                    Release Notes
                                </label>
                                <textarea class="form-control" id="releaseModalNotes" rows="2"
                                    placeholder="Add any notes about the release..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmReleaseBtn" style="display: none;"
                            onclick="confirmRelease()">
                            <i class="fa-regular fa-circle-check me-2"></i>
                            Confirm Release
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Begin Transaction Modal --}}
        <div class="modal fade" id="beginTransactionModal" tabindex="-1" aria-labelledby="beginTransactionModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="beginTransactionModalLabel">
                            <i class="fa-solid fa-qrcode me-2" style="color: var(--cpu-primary);"></i>
                            Begin Equipment Transaction
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Loading State --}}
                        <div id="modalLoading" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Processing...</p>
                        </div>

                        {{-- Step 1: Barcode Scanner --}}
                        <div id="scanStep" class="transaction-step">
                            <div class="text-center mb-4">
                                <div class="display-1 text-muted mb-3">
                                    <i class="fa-solid fa-camera"></i>
                                </div>
                                <h6>Scan Equipment Barcode</h6>
                                <p class="text-muted small">Position the barcode in front of the camera or enter manually
                                </p>
                            </div>

                            {{-- Camera Scanner --}}
                            <div id="reader" class="mb-3"
                                style="width: 100%; height: 300px; border: 2px dashed #dee2e6; border-radius: 8px; overflow: hidden;">
                            </div>

                            {{-- Scanner Controls --}}
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <button id="stopScanBtn" class="btn btn-sm btn-danger" type="button">
                                    <i class="fa-solid fa-stop me-1"></i> Stop Scan
                                </button>
                                <button id="resumeScanBtn" class="btn btn-sm btn-warning" type="button"
                                    style="display:none;">
                                    <i class="fa-solid fa-play me-1"></i> Resume Scan
                                </button>
                            </div>

                            {{-- Manual Entry --}}
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa-regular fa-keyboard"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Or enter barcode manually..."
                                    id="manualBarcodeInput">
                                <button class="btn btn-primary" type="button" id="lookupBarcodeBtn"
                                    onclick="lookupBarcode()">
                                    <i class="fa-regular fa-magnifying-glass me-2"></i>
                                    Lookup
                                </button>
                            </div>

                            {{-- File Upload Option --}}
                            <div class="mt-3 text-center">
                                <label for="uploadInput" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa-solid fa-cloud-upload-alt me-1"></i> Upload Image/PDF
                                </label>
                                <input type="file" id="uploadInput" accept="image/*,application/pdf" style="display: none;">
                            </div>

                            <div id="barcodeError" class="text-danger small mt-2" style="display: none;"></div>
                        </div>

                        {{-- Step 2: Equipment Details (Hidden initially) --}}
                        <div id="equipmentDetailsStep" class="transaction-step" style="display: none;">
                            {{-- Equipment Info Card --}}
                            <div class="card bg-light border-0 mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-auto">
                                            <div class="rounded bg-white p-3">
                                                <img id="scannedEquipmentImage" src="" alt="Equipment"
                                                    style="width: 48px; height: 48px; object-fit: cover; display: none;">
                                                <i class="fa-solid fa-laptop fa-2x" id="equipmentIcon"
                                                    style="color: var(--cpu-primary);"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h5 class="mb-1" id="scannedEquipmentName">Loading...</h5>
                                            <p class="text-muted mb-2" id="scannedEquipmentBarcode"></p>
                                            <div class="d-flex gap-2" id="equipmentBadges">
                                                <span class="badge px-3 py-2" id="scannedEquipmentCondition"></span>
                                                <span class="badge px-3 py-2" id="scannedEquipmentStatus"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Selection --}}
                            <h6 class="fw-semibold mb-3">Select Transaction Action</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="transactionAction" id="actionRelease"
                                        value="release" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary w-100 py-3" for="actionRelease">
                                        <i class="fa-regular fa-hand-back-point-up fa-lg mb-2 d-block"></i>
                                        <span class="small fw-semibold">Release</span>
                                        <br>
                                        <small class="text-muted">Equipment goes out</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="transactionAction" id="actionReturn"
                                        value="return" autocomplete="off">
                                    <label class="btn btn-outline-success w-100 py-3" for="actionReturn">
                                        <i class="fa-regular fa-hand-back-point-down fa-lg mb-2 d-block"></i>
                                        <span class="small fw-semibold">Return</span>
                                        <br>
                                        <small class="text-muted">Equipment comes back</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="transactionAction" id="actionUpdate"
                                        value="update" autocomplete="off">
                                    <label class="btn btn-outline-warning w-100 py-3" for="actionUpdate">
                                        <i class="fa-regular fa-pen-to-square fa-lg mb-2 d-block"></i>
                                        <span class="small fw-semibold">Update</span>
                                        <br>
                                        <small class="text-muted">Update condition/status</small>
                                    </label>
                                </div>
                            </div>

                            {{-- Dynamic Form Based on Action --}}
                            <div id="releaseForm" class="action-form">
                                <h6 class="fw-semibold mb-3">Release Details</h6>

                                {{-- Requisition ID Selection --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-file-lines me-1"></i>
                                        Link to Requisition <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="requisitionSelect" required>
                                        <option value="">Loading requisitions...</option>
                                    </select>
                                </div>

                                {{-- Destination Selection --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-building me-1"></i>
                                        Destination
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <select class="form-select" id="facilitySelect">
                                                <option value="">Select a facility...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="manualDestination"
                                                placeholder="Or enter manual location...">
                                        </div>
                                    </div>
                                </div>

                                {{-- Condition Update --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-clipboard me-1"></i>
                                        Equipment Condition
                                    </label>
                                    <div class="d-flex gap-2 flex-wrap" id="releaseConditionRadios">
                                        <!-- Will be populated dynamically -->
                                    </div>
                                </div>

                                {{-- Release Notes --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-note-sticky me-1"></i>
                                        Release Notes
                                    </label>
                                    <textarea class="form-control" id="releaseNotes" rows="2"
                                        placeholder="Add any notes about the release..."></textarea>
                                </div>
                            </div>

                            <div id="returnForm" class="action-form" style="display: none;">
                                <h6 class="fw-semibold mb-3">Return Details</h6>

                                {{-- Return Condition --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-clipboard me-1"></i>
                                        Return Condition <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex gap-2 flex-wrap" id="returnConditionRadios">
                                        <!-- Will be populated dynamically -->
                                    </div>
                                </div>

                                {{-- Late Fee --}}
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="applyLateFee">
                                        <label class="form-check-label fw-medium" for="applyLateFee">
                                            Apply late penalty fee
                                        </label>
                                    </div>
                                    <div class="mt-2" id="lateFeeInput" style="display: none;">
                                        <small class="text-muted">Fee from requisition form will be applied if late</small>
                                    </div>
                                </div>

                                {{-- Return Notes --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-note-sticky me-1"></i>
                                        Return Notes
                                    </label>
                                    <textarea class="form-control" id="returnNotes" rows="2"
                                        placeholder="Add any notes about the return condition..."></textarea>
                                </div>
                            </div>

                            <div id="updateForm" class="action-form" style="display: none;">
                                <h6 class="fw-semibold mb-3">Update Details</h6>

                                {{-- Update Status --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-circle me-1"></i>
                                        Update Status
                                    </label>
                                    <select class="form-select" id="updateStatusSelect">
                                        <option value="">Select status...</option>
                                    </select>
                                </div>

                                {{-- Update Condition --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-clipboard me-1"></i>
                                        Update Condition
                                    </label>
                                    <div class="d-flex gap-2 flex-wrap" id="updateConditionRadios">
                                        <!-- Will be populated dynamically -->
                                    </div>
                                </div>

                                {{-- Update Notes --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">
                                        <i class="fa-regular fa-note-sticky me-1"></i>
                                        Update Notes
                                    </label>
                                    <textarea class="form-control" id="updateNotes" rows="2"
                                        placeholder="Reason for update..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="backToScanBtn" style="display: none;"
                            onclick="backToScan()">
                            <i class="fa-regular fa-arrow-left me-2"></i>
                            Back to Scan
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmTransactionBtn" style="display: none;"
                            onclick="confirmTransaction()">
                            <i class="fa-regular fa-circle-check me-2"></i>
                            Confirm Transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    <script>
        // State management
        let currentPage = 1;
        let hasMorePages = false;
        let currentEquipmentItem = null;
        let currentTransaction = null;
        let conditions = [];
        let facilities = [];
        let formStatuses = [];

        // Scanner variables
        const html5QrCode = new Html5Qrcode("reader");
        let scannerRunning = false;
        const SYSTEM_PREFIX = "EQ-";

        // Update DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function () {
            loadOngoingTransactions();
            loadLookupData(); // This now loads both conditions AND availability statuses
            loadFacilities();
            loadRequisitions();
            setupEventListeners();
            setupScannerControls();
        });
        // Load ongoing transactions
        function loadOngoingTransactions(page = 1) {
            const loadingEl = document.getElementById('transactionsLoading');
            const errorEl = document.getElementById('transactionsError');
            const emptyEl = document.getElementById('transactionsEmpty');
            const listEl = document.getElementById('ongoingTransactionsList');
            const loadMoreContainer = document.getElementById('loadMoreContainer');

            loadingEl.style.display = 'block';
            errorEl.style.display = 'none';
            emptyEl.style.display = 'none';
            listEl.style.display = 'none';
            loadMoreContainer.style.display = 'none';

            fetch(`/api/admin/equipment-transactions/ongoing?page=${page}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    loadingEl.style.display = 'none';

                    if (data.success && data.data && data.data.length > 0) {
                        if (page === 1) {
                            document.getElementById('ongoingTransactionsList').innerHTML = '';
                        }
                        renderTransactions(data.data);
                        updateStats(data.data);

                        // Handle pagination if your API returns pagination info
                        if (data.meta) {
                            hasMorePages = data.meta.current_page < data.meta.last_page;
                            currentPage = data.meta.current_page;
                        }

                        listEl.style.display = 'block';
                        if (hasMorePages) {
                            loadMoreContainer.style.display = 'block';
                        }
                    } else {
                        emptyEl.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading transactions:', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = 'block';
                });
        }

        // Load more transactions
        function loadMoreTransactions() {
            if (hasMorePages) {
                loadOngoingTransactions(currentPage + 1);
            }
        }

        // Render transactions
        function renderTransactions(transactions) {
            const listEl = document.getElementById('ongoingTransactionsList');

            transactions.forEach(transaction => {
                const itemHtml = `
                                                        <div class="list-group-item p-3">
                                                            <div class="row align-items-center">
                                                                <div class="col-lg-6">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="rounded bg-light d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; overflow: hidden;">
                                                                            ${transaction.item_image ?
                        `<img src="${transaction.item_image}" alt="${transaction.item_name}" style="width: 48px; height: 48px; object-fit: cover;">` :
                        `<i class="fa-solid fa-laptop fa-xl" style="color: var(--cpu-primary); opacity: 0.5;"></i>`
                    }
                                                                        </div>
                                                                        <div>
                                                                            <h6 class="mb-1 fw-semibold">${transaction.item_name}</h6>
                                                                            <div class="d-flex flex-wrap gap-2 small">
                                                                                <span class="text-muted">
                                                                                    <i class="fa-regular fa-file-lines me-1"></i>
                                                                                    Request #${transaction.request_id || 'N/A'}
                                                                                </span>
                                                                                <span class="text-muted">
                                                                                    <i class="fa-regular fa-building me-1"></i>
                                                                                    ${transaction.destination}
                                                                                </span>
                                                                                ${transaction.requester !== 'N/A' ?
                        `<span class="text-muted">
                                                                                        <i class="fa-regular fa-user me-1"></i>
                                                                                        ${transaction.requester}
                                                                                    </span>` : ''
                    }
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-3">
                                                                        <span class="badge px-3 py-2" style="background-color: ${transaction.condition.color_code}20; color: ${transaction.condition.color_code};">
                                                                            <i class="fa-regular fa-circle-check me-1"></i>
                                                                            ${transaction.condition.name}
                                                                        </span>
                                                                        <span class="badge px-3 py-2" style="background-color: ${getStatusColor(transaction.status.type)}20; color: ${getStatusColor(transaction.status.type)};">
                                                                            <i class="fa-regular ${getStatusIcon(transaction.status.type)} me-1"></i>
                                                                            ${transaction.status.name}
                                                                        </span>
                                                                        ${transaction.is_late ?
                        `<span class="badge bg-danger px-3 py-2">
                                                                                <i class="fa-regular fa-clock me-1"></i>
                                                                                Overdue
                                                                            </span>` : ''
                    }
                                                                        <small class="text-muted">
                                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                                            ${transaction.released_at || transaction.returned_at || 'Just now'}
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    `;
                listEl.innerHTML += itemHtml;
            });
        }

        // Update stats
        function updateStats(transactions) {
            const activeCount = transactions.filter(t => t.status.type === 'primary' || t.status.type === 'danger').length;
            const releasedToday = transactions.filter(t => t.status.type === 'primary' && !t.is_late).length;
            const returnedToday = transactions.filter(t => t.status.type === 'success').length;
            const pendingReturns = transactions.filter(t => t.status.type === 'warning' || t.is_late).length;

            document.getElementById('activeTransactionsCount').textContent = activeCount;
            document.getElementById('releasedTodayCount').textContent = releasedToday;
            document.getElementById('returnedTodayCount').textContent = returnedToday;
            document.getElementById('pendingReturnsCount').textContent = pendingReturns;
        }

        // Update your loadLookupData function
        function loadLookupData() {
            // Load conditions
            loadConditions();

            // Load availability statuses
            loadAvailabilityStatuses();
        }

        // Load facilities for dropdown
        function loadFacilities() {
            fetch('/api/facilities/dropdown', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    const facilitySelect = document.getElementById('facilitySelect');

                    if (data.success && data.data && data.data.length > 0) {
                        facilitySelect.innerHTML = '<option value="">Select a facility...</option>' +
                            data.data.map(f => `<option value="${f.facility_id}">${f.facility_name}</option>`).join('');
                    } else {
                        facilitySelect.innerHTML = '<option value="">No facilities available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading facilities:', error);
                    document.getElementById('facilitySelect').innerHTML = '<option value="">Failed to load facilities</option>';
                });
        }
        // Load requisitions
        function loadRequisitions() {
            fetch('/api/admin/active-requests', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    const requisitionSelect = document.getElementById('requisitionSelect');

                    if (data.success && data.data && data.data.length > 0) {
                        requisitionSelect.innerHTML = '<option value="">Select a requisition...</option>' +
                            data.data.map(r => `<option value="${r.request_id}">${r.label}</option>`).join('');
                    } else {
                        requisitionSelect.innerHTML = '<option value="">No active requisitions available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading requisitions:', error);
                    document.getElementById('requisitionSelect').innerHTML = '<option value="">Failed to load requisitions</option>';
                });
        }


        // Load conditions
        function loadConditions() {
            fetch('/api/conditions', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        conditions = data;
                    } else if (data.data && Array.isArray(data.data)) {
                        conditions = data.data;
                    } else {
                        conditions = [];
                    }
                    renderConditionRadios();
                })
                .catch(error => {
                    console.error('Error loading conditions:', error);
                    // Set default conditions as fallback
                    conditions = [
                        { condition_id: 1, condition_name: 'New' },
                        { condition_id: 2, condition_name: 'Good' },
                        { condition_id: 3, condition_name: 'Fair' },
                        { condition_id: 4, condition_name: 'Needs Maintenance' },
                        { condition_id: 5, condition_name: 'Damaged' }
                    ];
                    renderConditionRadios();
                });
        }

        function loadAvailabilityStatuses() {
            fetch('/api/availability-statuses', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    const statusSelect = document.getElementById('updateStatusSelect');

                    let statuses = [];
                    if (data.success && data.data) {
                        statuses = data.data;
                    } else if (Array.isArray(data)) {
                        statuses = data;
                    }

                    if (statuses.length > 0) {
                        statusSelect.innerHTML = '<option value="">Select status...</option>' +
                            statuses.map(s => `<option value="${s.status_id}">${s.status_name}</option>`).join('');
                    } else {
                        statusSelect.innerHTML = '<option value="">No statuses available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading availability statuses:', error);
                    // Fallback statuses
                    const fallbackStatuses = [
                        { status_id: 1, status_name: 'Available' },
                        { status_id: 2, status_name: 'In Use' },
                        { status_id: 3, status_name: 'Reserved' },
                        { status_id: 4, status_name: 'Under Maintenance' },
                        { status_id: 5, status_name: 'Unavailable' }
                    ];

                    const statusSelect = document.getElementById('updateStatusSelect');
                    statusSelect.innerHTML = '<option value="">Select status...</option>' +
                        fallbackStatuses.map(s => `<option value="${s.status_id}">${s.status_name}</option>`).join('');
                });
        }

        // Render condition radios
        function renderConditionRadios() {
            const releaseContainer = document.getElementById('releaseConditionRadios');
            const returnContainer = document.getElementById('returnConditionRadios');
            const updateContainer = document.getElementById('updateConditionRadios');

            if (!conditions || conditions.length === 0) {
                const emptyHtml = '<p class="text-muted small">No conditions available</p>';
                releaseContainer.innerHTML = emptyHtml;
                returnContainer.innerHTML = emptyHtml;
                updateContainer.innerHTML = emptyHtml;
                return;
            }

            // Define color mapping for conditions (since your API doesn't return colors)
            const conditionColors = {
                1: '#28a745', // New - Green
                2: '#17a2b8', // Good - Blue
                3: '#ffc107', // Fair - Yellow
                4: '#fd7e14', // Needs Maintenance - Orange
                5: '#dc3545', // Damaged - Red
                6: '#6c757d'  // In Use - Gray
            };

            const radiosHtml = conditions.map((condition, index) => {
                const color = condition.color_code || conditionColors[condition.condition_id] || '#6c757d';
                return `
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" 
                                    name="condition_${condition.condition_id}" 
                                    id="condition_${condition.condition_id}" 
                                    value="${condition.condition_id}" 
                                    ${index === 0 ? 'checked' : ''}>
                                <label class="form-check-label" for="condition_${condition.condition_id}">
                                    <span class="badge px-3 py-2" style="background-color: ${color}20; color: ${color};">
                                        <i class="fa-regular fa-circle-check me-1"></i>
                                        ${condition.condition_name}
                                    </span>
                                </label>
                            </div>
                        `;
            }).join('');

            releaseContainer.innerHTML = radiosHtml.replace(/name="[^"]*"/g, 'name="releaseCondition"');
            returnContainer.innerHTML = radiosHtml.replace(/name="[^"]*"/g, 'name="returnCondition"');
            updateContainer.innerHTML = radiosHtml.replace(/name="[^"]*"/g, 'name="updateCondition"');
        }

        // Render status select
        function renderStatusSelect() {
            const statusSelect = document.getElementById('updateStatusSelect');
            statusSelect.innerHTML = '<option value="">Select status...</option>' +
                formStatuses.map(s => `<option value="${s.status_id}">${s.status_name}</option>`).join('');
        }

        // ========== SCANNER FUNCTIONS ==========

        function setupScannerControls() {
            const stopBtn = document.getElementById('stopScanBtn');
            const resumeBtn = document.getElementById('resumeScanBtn');
            const uploadInput = document.getElementById('uploadInput');

            stopBtn.addEventListener('click', stopScanner);
            resumeBtn.addEventListener('click', resumeScanner);
            uploadInput.addEventListener('change', handleFileUpload);

            // Start scanner when modal is shown
            document.getElementById('beginTransactionModal').addEventListener('shown.bs.modal', function () {
                startScanner();
            });

            // Stop scanner when modal is hidden
            document.getElementById('beginTransactionModal').addEventListener('hidden.bs.modal', function () {
                stopScanner();
                resetModal();
            });
        }

        async function startScanner() {
            if (scannerRunning) return;

            try {
                await html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 150 } },
                    onScanSuccess,
                    (errorMessage) => {
                        // console.debug("QR error", errorMessage);
                    }
                );
                scannerRunning = true;
                document.getElementById('stopScanBtn').style.display = 'inline-block';
                document.getElementById('resumeScanBtn').style.display = 'none';
            } catch (err) {
                console.error("Scanner start error:", err);
                showBarcodeError("Unable to start camera. Please check permissions or use manual entry.");
            }
        }

        async function stopScanner() {
            if (!scannerRunning) return;

            try {
                await html5QrCode.stop();
            } catch (err) {
                console.warn("Stop scanner error:", err);
            } finally {
                scannerRunning = false;
                document.getElementById('stopScanBtn').style.display = 'none';
                document.getElementById('resumeScanBtn').style.display = 'inline-block';
            }
        }

        async function resumeScanner() {
            document.getElementById('manualBarcodeInput').value = '';
            document.getElementById('barcodeError').style.display = 'none';
            await startScanner();
        }

        async function onScanSuccess(decodedText) {
            console.log('Raw scanned text:', decodedText);

            if (!decodedText) {
                showBarcodeError("No barcode data detected");
                return;
            }

            // Clean and normalize the barcode
            let cleanBarcode = decodedText.toString().trim().replace(/\s/g, '');

            // Ensure it starts with EQ- (our system format)
            if (!cleanBarcode.startsWith('EQ-')) {
                const eqMatch = cleanBarcode.match(/(EQ[A-Z0-9\-]{5,})/i);
                if (eqMatch) {
                    let extractedCode = eqMatch[1];
                    cleanBarcode = extractedCode.startsWith('EQ-') ? extractedCode : 'EQ-' + extractedCode.substring(2);
                } else {
                    showBarcodeError(`Scanned: "${decodedText}"\nOur system uses EQ-XXXXXXX format`);
                    return;
                }
            }

            cleanBarcode = cleanBarcode.replace(/[^A-Z0-9\-]/gi, '');
            console.log('Cleaned barcode:', cleanBarcode);

            // Stop scanner
            await stopScanner();

            // Lookup the barcode
            document.getElementById('manualBarcodeInput').value = cleanBarcode;
            lookupBarcode(cleanBarcode);
        }

        async function handleFileUpload(e) {
            const file = e.target.files[0];
            if (!file) return;

            await stopScanner();
            showModalLoading();

            try {
                if (file.type === "application/pdf") {
                    await scanPDFBarcode(file);
                } else {
                    await scanImageBarcode(file);
                }
            } catch (error) {
                console.error('File scanning error:', error);
                showBarcodeError('Failed to process file: ' + error.message);
            } finally {
                hideModalLoading();
            }
        }

        async function scanPDFBarcode(file) {
            try {
                const pdfjsLib = window['pdfjsLib'];
                const pdf = await pdfjsLib.getDocument(URL.createObjectURL(file)).promise;
                const page = await pdf.getPage(1);
                const viewport = page.getViewport({ scale: 3.0 });
                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;

                const barcodeData = await scanWithQuagga(canvas.toDataURL());

                if (barcodeData) {
                    await onScanSuccess(barcodeData);
                } else {
                    showBarcodeError("No barcode found in PDF. Try a clearer image.");
                }
            } catch (err) {
                console.error("PDF decode error:", err);
                showBarcodeError("Failed to decode PDF file.");
            }
        }

        async function scanImageBarcode(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = async function () {
                    try {
                        const img = new Image();
                        img.onload = async function () {
                            let barcodeData = await scanWithQuagga(reader.result);

                            if (!barcodeData) {
                                barcodeData = await scanWithImagePreprocessing(img);
                            }

                            if (barcodeData) {
                                await onScanSuccess(barcodeData);
                                resolve(true);
                            } else {
                                showBarcodeError("No barcode detected. Try a clearer image.");
                                resolve(false);
                            }
                        };
                        img.src = reader.result;
                    } catch (error) {
                        console.error("Image processing error:", error);
                        showBarcodeError("Error processing image file.");
                        resolve(false);
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        async function scanWithQuagga(imageData) {
            return new Promise((resolve) => {
                const config = {
                    src: imageData,
                    numOfWorkers: 4,
                    inputStream: {
                        size: 800,
                        type: "ImageStream",
                        area: {
                            top: "0%",
                            right: "0%",
                            left: "0%",
                            bottom: "0%"
                        }
                    },
                    locator: {
                        patchSize: "x-large",
                        halfSample: true
                    },
                    decoder: {
                        readers: [
                            "code_128_reader",
                            "ean_reader",
                            "ean_8_reader",
                            "code_39_reader",
                            "code_39_vin_reader",
                            "codabar_reader",
                            "upc_reader",
                            "upc_e_reader"
                        ]
                    },
                    locate: true
                };

                Quagga.decodeSingle(config, function (result) {
                    if (result && result.codeResult && result.codeResult.code) {
                        resolve(result.codeResult.code);
                    } else {
                        resolve(null);
                    }
                });
            });
        }

        async function scanWithImagePreprocessing(img) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = img.width;
            canvas.height = img.height;

            const techniques = [
                { method: 'original', filter: 'none' },
                { method: 'enhanced_contrast', filter: 'contrast(1.5) brightness(1.1)' },
                { method: 'grayscale', filter: 'grayscale(1) contrast(1.2)' },
                { method: 'high_contrast', filter: 'contrast(2) brightness(0.9)' }
            ];

            for (let technique of techniques) {
                ctx.filter = technique.filter;
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                if (technique.method.includes('grayscale')) {
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    for (let i = 0; i < data.length; i += 4) {
                        const gray = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                        data[i] = data[i + 1] = data[i + 2] = gray;
                    }
                    ctx.putImageData(imageData, 0, 0);
                }

                const result = await scanWithQuagga(canvas.toDataURL());
                if (result) return result;

                ctx.filter = 'none';
            }

            return null;
        }

        // ========== TRANSACTION FUNCTIONS ==========
        function lookupBarcode(barcode = null) {
            const barcodeValue = barcode || document.getElementById('manualBarcodeInput').value.trim();

            if (!barcodeValue) {
                showError('Please enter a barcode');
                return;
            }

            showModalLoading();

            fetch('/api/scanner/scan', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ barcode: barcodeValue })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    hideModalLoading();

                    // Handle different response structures
                    if (data.status === 'success' && data.item) {
                        currentEquipmentItem = data.item;
                        showEquipmentDetails(data);
                    } else if (data.success && data.data) {
                        // Alternative response structure
                        currentEquipmentItem = data.data.item || data.data;
                        showEquipmentDetails({
                            item: currentEquipmentItem,
                            active_transaction: data.data.active_transaction
                        });
                    } else {
                        showBarcodeError(data.message || 'Item not found');
                    }
                })
                .catch(error => {
                    hideModalLoading();
                    showBarcodeError('Failed to lookup barcode. Please try again.');
                    console.error('Error:', error);
                });
        }

        function showEquipmentDetails(data) {
            const scanStep = document.getElementById('scanStep');
            const equipmentStep = document.getElementById('equipmentDetailsStep');
            const backBtn = document.getElementById('backToScanBtn');
            const confirmBtn = document.getElementById('confirmTransactionBtn');

            document.getElementById('scannedEquipmentName').textContent = data.item.item_name || 'Unknown Equipment';
            document.getElementById('scannedEquipmentBarcode').textContent = data.item.barcode_number || 'No Barcode';

            // Handle image with fallback
            const equipmentImage = document.getElementById('scannedEquipmentImage');
            const equipmentIcon = document.getElementById('equipmentIcon');

            if (data.item.image_url && data.item.image_url.trim() !== '') {
                // Try to load the image, if it fails show placeholder
                equipmentImage.onload = function () {
                    equipmentImage.style.display = 'block';
                    equipmentIcon.style.display = 'none';
                };
                equipmentImage.onerror = function () {
                    // Image failed to load, use placeholder
                    equipmentImage.style.display = 'none';
                    equipmentIcon.style.display = 'block';
                    // Optional: You could also set the icon based on equipment type
                    equipmentIcon.className = 'fa-solid fa-laptop fa-2x';
                };
                equipmentImage.src = data.item.image_url;
            } else {
                // No image URL provided, use placeholder
                equipmentImage.style.display = 'none';
                equipmentIcon.style.display = 'block';
                equipmentIcon.className = 'fa-solid fa-laptop fa-2x';
            }

            // Handle condition with fallback
            const conditionBadge = document.getElementById('scannedEquipmentCondition');
            if (data.item.condition && data.item.condition.condition_name) {
                conditionBadge.textContent = data.item.condition.condition_name;
                conditionBadge.style.backgroundColor = (data.item.condition.color_code || '#6c757d') + '20';
                conditionBadge.style.color = data.item.condition.color_code || '#6c757d';
            } else {
                conditionBadge.textContent = 'Unknown';
                conditionBadge.style.backgroundColor = '#6c757d20';
                conditionBadge.style.color = '#6c757d';
            }

            // Handle status with fallback
            const statusBadge = document.getElementById('scannedEquipmentStatus');
            if (data.item.status_name) {
                statusBadge.textContent = data.item.status_name;
                statusBadge.style.backgroundColor = getStatusColor(data.item.status_id || '1') + '20';
                statusBadge.style.color = getStatusColor(data.item.status_id || '1');
            } else {
                statusBadge.textContent = 'Unknown';
                statusBadge.style.backgroundColor = '#6c757d20';
                statusBadge.style.color = '#6c757d';
            }

            // Handle active transaction
            if (data.active_transaction) {
                currentTransaction = data.active_transaction;
                document.getElementById('actionReturn').disabled = false;
                document.getElementById('actionRelease').disabled = true;
                document.getElementById('actionUpdate').disabled = false;
                document.getElementById('actionReturn').checked = true;

                document.getElementById('releaseForm').style.display = 'none';
                document.getElementById('returnForm').style.display = 'block';
                document.getElementById('updateForm').style.display = 'none';
            } else {
                currentTransaction = null;
                document.getElementById('actionReturn').disabled = true;
                document.getElementById('actionRelease').disabled = false;
                document.getElementById('actionUpdate').disabled = false;
                document.getElementById('actionRelease').checked = true;

                // Show release form by default
                document.getElementById('releaseForm').style.display = 'block';
                document.getElementById('returnForm').style.display = 'none';
                document.getElementById('updateForm').style.display = 'none';
            }

            // Switch to details step
            scanStep.style.display = 'none';
            equipmentStep.style.display = 'block';
            backBtn.style.display = 'inline-block';
            confirmBtn.style.display = 'inline-block';
        }
        function confirmTransaction() {
            const action = document.querySelector('input[name="transactionAction"]:checked').value;

            showModalLoading();

            let endpoint = '';
            let payload = {
                barcode: currentEquipmentItem.barcode_number,
                transaction_type: action
            };

            switch (action) {
                case 'release':
                    endpoint = '/api/scanner/borrow';
                    payload.request_id = document.getElementById('requisitionSelect').value;
                    payload.facility_id = document.getElementById('facilitySelect').value || null;
                    payload.destination_name = document.getElementById('manualDestination').value || null;
                    payload.notes = document.getElementById('releaseNotes').value;
                    payload.condition_id = document.querySelector('input[name="releaseCondition"]:checked')?.value;
                    break;

                case 'return':
                    endpoint = '/api/scanner/return';
                    payload.condition_id = document.querySelector('input[name="returnCondition"]:checked')?.value;
                    payload.apply_late_fee = document.getElementById('applyLateFee').checked;
                    payload.notes = document.getElementById('returnNotes').value;
                    break;

                case 'update':
                    endpoint = '/api/admin/equipment-transactions';
                    payload.condition_id = document.querySelector('input[name="updateCondition"]:checked')?.value;
                    payload.status_id = document.getElementById('updateStatusSelect').value;
                    payload.notes = document.getElementById('updateNotes').value;
                    break;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    hideModalLoading();

                    if (data.success || data.status === 'success') {
                        showToast('success', data.message || 'Transaction completed successfully');
                        bootstrap.Modal.getInstance(document.getElementById('beginTransactionModal')).hide();
                        resetModal();
                        loadOngoingTransactions();
                    } else {
                        showBarcodeError(data.message || 'Transaction failed');
                    }
                })
                .catch(error => {
                    hideModalLoading();
                    showBarcodeError('Failed to process transaction');
                    console.error('Error:', error);
                });
        }

        // Helper functions
        function setupEventListeners() {
            document.querySelectorAll('input[name="transactionAction"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    document.getElementById('releaseForm').style.display = 'none';
                    document.getElementById('returnForm').style.display = 'none';
                    document.getElementById('updateForm').style.display = 'none';

                    if (this.value === 'release') {
                        document.getElementById('releaseForm').style.display = 'block';
                    } else if (this.value === 'return') {
                        document.getElementById('returnForm').style.display = 'block';
                    } else if (this.value === 'update') {
                        document.getElementById('updateForm').style.display = 'block';
                    }
                });
            });

            document.getElementById('applyLateFee')?.addEventListener('change', function () {
                document.getElementById('lateFeeInput').style.display = this.checked ? 'block' : 'none';
            });

            const facilitySelect = document.getElementById('facilitySelect');
            const manualDestination = document.getElementById('manualDestination');

            if (facilitySelect && manualDestination) {
                facilitySelect.addEventListener('change', function () {
                    if (this.value) {
                        manualDestination.value = '';
                        manualDestination.disabled = true;
                    } else {
                        manualDestination.disabled = false;
                    }
                });

                manualDestination.addEventListener('input', function () {
                    if (this.value) {
                        facilitySelect.value = '';
                    }
                });
            }
        }

        function backToScan() {
            document.getElementById('scanStep').style.display = 'block';
            document.getElementById('equipmentDetailsStep').style.display = 'none';
            document.getElementById('backToScanBtn').style.display = 'none';
            document.getElementById('confirmTransactionBtn').style.display = 'none';
            document.getElementById('barcodeError').style.display = 'none';
            document.getElementById('manualBarcodeInput').value = '';
            startScanner();
        }

        function resetModal() {
            currentEquipmentItem = null;
            currentTransaction = null;
            document.getElementById('scanStep').style.display = 'block';
            document.getElementById('equipmentDetailsStep').style.display = 'none';
            document.getElementById('backToScanBtn').style.display = 'none';
            document.getElementById('confirmTransactionBtn').style.display = 'none';
            document.getElementById('manualBarcodeInput').value = '';
        }

        function showModalLoading() {
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('scanStep').style.display = 'none';
            document.getElementById('equipmentDetailsStep').style.display = 'none';
        }

        function hideModalLoading() {
            document.getElementById('modalLoading').style.display = 'none';
        }

        function showBarcodeError(message) {
            const errorEl = document.getElementById('barcodeError');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            hideModalLoading();
            document.getElementById('scanStep').style.display = 'block';
        }

        function showError(message) {
            const errorEl = document.getElementById('barcodeError');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }

        function showToast(type, message) {
            alert(message);
        }

        function getStatusColor(type) {
            const colors = {
                'primary': '#135ba3',
                'success': '#28a745',
                'danger': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };
            return colors[type] || '#6c757d';
        }

        function getStatusIcon(type) {
            const icons = {
                'primary': 'fa-clock',
                'success': 'fa-circle-check',
                'danger': 'fa-circle-exclamation',
                'warning': 'fa-clock',
                'info': 'fa-circle-info'
            };
            return icons[type] || 'fa-circle';
        }

        // ========== TO RELEASE FUNCTIONS ==========
        let toReleasePage = 1;
        let toReleaseHasMore = false;
        let currentReleaseRequest = null;
        let selectedReleaseItem = null;

        function loadToRelease(page = 1) {
            const loadingEl = document.getElementById('toReleaseLoading');
            const errorEl = document.getElementById('toReleaseError');
            const emptyEl = document.getElementById('toReleaseEmpty');
            const listEl = document.getElementById('toReleaseList');
            const loadMoreContainer = document.getElementById('toReleaseLoadMoreContainer');

            loadingEl.style.display = 'block';
            errorEl.style.display = 'none';
            emptyEl.style.display = 'none';
            listEl.style.display = 'none';
            loadMoreContainer.style.display = 'none';

            fetch(`/api/admin/equipment/to-release?page=${page}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    loadingEl.style.display = 'none';

                    if (data.success && data.data && data.data.length > 0) {
                        if (page === 1) {
                            document.getElementById('toReleaseTableBody').innerHTML = '';
                        }
                        renderToReleaseRows(data.data);
                        document.getElementById('toReleaseCount').textContent = data.meta.total || data.data.length;

                        toReleaseHasMore = data.meta.current_page < data.meta.last_page;
                        toReleasePage = data.meta.current_page;

                        listEl.style.display = 'block';
                        if (toReleaseHasMore) {
                            loadMoreContainer.style.display = 'block';
                        }
                    } else {
                        emptyEl.style.display = 'block';
                        document.getElementById('toReleaseCount').textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error loading to release:', error);
                    loadingEl.style.display = 'none';
                    errorEl.style.display = 'block';
                });
        }

        function loadMoreToRelease() {
            if (toReleaseHasMore) {
                loadToRelease(toReleasePage + 1);
            }
        }

        function renderToReleaseRows(items) {
            const tbody = document.getElementById('toReleaseTableBody');

            items.forEach(item => {
                const row = document.createElement('tr');

                const canReleaseClass = item.can_release ? '' : 'text-muted';
                const releaseButton = item.can_release
                    ? `<button class="btn btn-sm btn-primary" onclick="openReleaseModal(${item.requested_equipment_id}, ${item.equipment_id}, '${item.equipment_name}', ${item.quantity_requested}, '${item.requester_name}', '${item.organization_name}')">
                    <i class="fa-solid fa-hand-back-point-up me-1"></i>Release
                   </button>`
                    : `<button class="btn btn-sm btn-secondary" disabled title="Not enough available items (${item.available_items}/${item.quantity_requested})">
                    <i class="fa-solid fa-ban me-1"></i>Insufficient
                   </button>`;

                row.innerHTML = `
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">${item.requester_name}</span>
                        <small class="text-muted">${item.organization_name}</small>
                    </div>
                </td>
                <td>
                    <span class="fw-semibold">${item.equipment_name}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-primary">${item.quantity_requested}</span>
                </td>
                <td class="${canReleaseClass}">
                    ${item.available_items} available
                    ${!item.can_release ? `<br><small class="text-danger">Need ${item.quantity_requested - item.available_items} more</small>` : ''}
                </td>
                <td>
                    <span class="badge px-3 py-2" style="background-color: #6c757d20; color: #6c757d;">
                        ${item.status}
                    </span>
                </td>
                <td class="text-end">
                    ${releaseButton}
                </td>
            `;

                tbody.appendChild(row);
            });
        }

        // ========== RELEASE MODAL FUNCTIONS ==========
        function openReleaseModal(requestedEquipmentId, equipmentId, equipmentName, quantityRequested, requesterName, organization) {
            currentReleaseRequest = {
                requested_equipment_id: requestedEquipmentId,
                equipment_id: equipmentId,
                equipment_name: equipmentName,
                quantity: quantityRequested,
                requester: requesterName,
                organization: organization
            };

            // Reset modal state
            document.getElementById('releaseRequestInfo').style.display = 'block';
            document.getElementById('releaseItemsContainer').style.display = 'none';
            document.getElementById('noItemsAvailable').style.display = 'none';
            document.getElementById('releaseFormContainer').style.display = 'none';
            document.getElementById('confirmReleaseBtn').style.display = 'none';

            // Set request info
            document.getElementById('releaseRequesterName').textContent = requesterName;
            document.getElementById('releaseOrganization').textContent = organization;
            document.getElementById('releaseEquipmentName').textContent = equipmentName;
            document.getElementById('releaseQuantityRequested').textContent = quantityRequested;

            // Load available items
            loadAvailableItems(equipmentId);

            // Show modal
            new bootstrap.Modal(document.getElementById('releaseItemModal')).show();
        }

        function loadAvailableItems(equipmentId) {
            const modalLoading = document.getElementById('releaseModalLoading');
            const itemsContainer = document.getElementById('releaseItemsContainer');
            const noItems = document.getElementById('noItemsAvailable');

            modalLoading.style.display = 'block';
            itemsContainer.style.display = 'none';
            noItems.style.display = 'none';

            fetch(`/api/admin/equipment/available-items/${equipmentId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    modalLoading.style.display = 'none';

                    if (data.success && data.data && data.data.length > 0) {
                        renderAvailableItems(data.data);
                        itemsContainer.style.display = 'block';
                    } else {
                        noItems.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading available items:', error);
                    modalLoading.style.display = 'none';
                    noItems.style.display = 'block';
                    noItems.innerHTML = '<i class="fa-solid fa-circle-exclamation fa-3x text-danger mb-3"></i><p>Failed to load available items</p>';
                });
        }

        function renderAvailableItems(items) {
            const grid = document.getElementById('availableItemsGrid');
            grid.innerHTML = '';

            items.forEach(item => {
                const col = document.createElement('div');
                col.className = 'col-md-6';

                col.innerHTML = `
                <div class="card item-select-card" onclick="selectItem(${item.item_id}, '${item.item_name}', '${item.barcode_number || 'No barcode'}')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded bg-light p-2 me-3" style="width: 48px; height: 48px; overflow: hidden;">
                                ${item.image_url && item.image_url !== 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1750895337/oxvsxogzu9koqhctnf7s.webp'
                        ? `<img src="${item.image_url}" alt="${item.item_name}" style="width: 48px; height: 48px; object-fit: cover;">`
                        : `<i class="fa-solid fa-laptop fa-2x text-muted" style="opacity: 0.5;"></i>`}
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${item.item_name}</h6>
                                <small class="text-muted d-block">${item.barcode_number || 'No barcode'}</small>
                                <div class="mt-1">
                                    <span class="badge px-2 py-1" style="background-color: ${item.condition_color}20; color: ${item.condition_color};">
                                        ${item.condition_name}
                                    </span>
                                    <span class="badge px-2 py-1" style="background-color: ${item.status_color}20; color: ${item.status_color};">
                                        ${item.status_name}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                grid.appendChild(col);
            });

            // Add hover effect CSS if not already present
            if (!document.getElementById('itemSelectCardStyle')) {
                const style = document.createElement('style');
                style.id = 'itemSelectCardStyle';
                style.textContent = `
                .item-select-card {
                    transition: all 0.2s ease;
                    border: 2px solid transparent;
                }
                .item-select-card:hover {
                    border-color: var(--cpu-primary);
                    background-color: rgba(19, 91, 163, 0.02);
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .item-select-card.selected {
                    border-color: var(--cpu-primary);
                    background-color: rgba(19, 91, 163, 0.05);
                }
            `;
                document.head.appendChild(style);
            }
        }

        function selectItem(itemId, itemName, barcode) {
            // Remove selected class from all cards
            document.querySelectorAll('.item-select-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');

            // Store selected item
            selectedReleaseItem = {
                item_id: itemId,
                item_name: itemName,
                barcode: barcode
            };

            // Show release form
            document.getElementById('releaseItemsContainer').style.display = 'none';
            document.getElementById('releaseFormContainer').style.display = 'block';
            document.getElementById('confirmReleaseBtn').style.display = 'inline-block';

            // Update selected item summary
            document.getElementById('selectedItemName').textContent = itemName;
            document.getElementById('selectedItemBarcode').textContent = barcode;

            // Load facilities if not already loaded
            if (document.getElementById('releaseFacilitySelect').options.length <= 1) {
                loadFacilitiesForRelease();
            }
        }

        function loadFacilitiesForRelease() {
            fetch('/api/facilities/dropdown', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    const facilitySelect = document.getElementById('releaseFacilitySelect');

                    if (data.success && data.data && data.data.length > 0) {
                        facilitySelect.innerHTML = '<option value="">Select a facility...</option>' +
                            data.data.map(f => `<option value="${f.facility_id}">${f.facility_name}</option>`).join('');
                    }
                })
                .catch(error => console.error('Error loading facilities:', error));
        }

        function confirmRelease() {
            if (!selectedReleaseItem) {
                alert('Please select an item to release');
                return;
            }

            const conditionRadio = document.querySelector('input[name="releaseModalCondition"]:checked');
            if (!conditionRadio) {
                alert('Please select equipment condition');
                return;
            }

            const facilityId = document.getElementById('releaseFacilitySelect').value;
            const manualDestination = document.getElementById('releaseManualDestination').value;
            const notes = document.getElementById('releaseModalNotes').value;

            // Validate destination
            if (!facilityId && !manualDestination) {
                alert('Please select a destination or enter a manual location');
                return;
            }

            const payload = {
                requested_equipment_id: currentReleaseRequest.requested_equipment_id,
                item_id: selectedReleaseItem.item_id,
                condition_id: conditionRadio.value,
                facility_id: facilityId || null,
                destination_name: manualDestination || null,
                release_notes: notes || null
            };

            // Show loading
            document.getElementById('releaseModalLoading').style.display = 'block';
            document.getElementById('releaseRequestInfo').style.display = 'none';
            document.getElementById('releaseFormContainer').style.display = 'none';
            document.getElementById('confirmReleaseBtn').style.display = 'none';

            fetch('/api/admin/equipment/release-item', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('releaseModalLoading').style.display = 'none';

                    if (data.success) {
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('releaseItemModal')).hide();

                        // Show success message
                        showToast('success', data.message || 'Item released successfully');

                        // Refresh both containers
                        loadToRelease();
                        loadOngoingTransactions(); // This will now load To Return

                        // Reset
                        selectedReleaseItem = null;
                        currentReleaseRequest = null;
                    } else {
                        alert(data.message || 'Failed to release item');
                        // Show the form again
                        document.getElementById('releaseRequestInfo').style.display = 'block';
                        document.getElementById('releaseFormContainer').style.display = 'block';
                        document.getElementById('confirmReleaseBtn').style.display = 'inline-block';
                    }
                })
                .catch(error => {
                    console.error('Error releasing item:', error);
                    document.getElementById('releaseModalLoading').style.display = 'none';
                    alert('Failed to release item. Please try again.');

                    // Show the form again
                    document.getElementById('releaseRequestInfo').style.display = 'block';
                    document.getElementById('releaseFormContainer').style.display = 'block';
                    document.getElementById('confirmReleaseBtn').style.display = 'inline-block';
                });
        }

        // Update the DOMContentLoaded event listener
        document.addEventListener('DOMContentLoaded', function () {
            loadOngoingTransactions(); // This will now load To Return
            loadToRelease(); // Load To Release
            loadLookupData();
            loadFacilities();
            loadRequisitions();
            setupEventListeners();
            setupScannerControls();

            // Add event listeners for release modal
            const facilitySelect = document.getElementById('releaseFacilitySelect');
            const manualDestination = document.getElementById('releaseManualDestination');

            if (facilitySelect && manualDestination) {
                facilitySelect.addEventListener('change', function () {
                    if (this.value) {
                        manualDestination.value = '';
                        manualDestination.disabled = true;
                    } else {
                        manualDestination.disabled = false;
                    }
                });

                manualDestination.addEventListener('input', function () {
                    if (this.value) {
                        facilitySelect.value = '';
                    }
                });
            }
        });
    </script>

    <style>
        #scannedEquipmentImage {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 4px;
        }

        #scannedEquipmentImage[src=""],
        #scannedEquipmentImage:not([src]) {
            display: none;
        }

        #equipmentIcon {
            font-size: 2rem;
            color: var(--cpu-primary);
            opacity: 0.7;
        }

        /* Additional styles specific to asset tracking */
        .list-group-item {
            transition: background-color 0.2s ease;
        }

        .list-group-item:hover {
            background-color: rgba(19, 91, 163, 0.02);
        }

        .badge {
            font-weight: 500;
            font-size: 0.75rem;
        }

        .btn-outline-primary:hover,
        .btn-outline-success:hover,
        .btn-outline-warning:hover {
            color: white;
        }

        .btn-check:checked+.btn-outline-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
        }

        .btn-check:checked+.btn-outline-success {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }

        .btn-check:checked+.btn-outline-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .transaction-step {
            min-height: 400px;
        }

        .action-form {
            transition: all 0.3s ease;
        }

        #reader {
            width: 100%;
            height: 300px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: #000;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }

            #reader {
                height: 240px;
            }
        }
    </style>
@endsection