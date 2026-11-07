@extends('layouts.admin')
@section('title', 'Edit Financials - Request #' . str_pad($requestId, 4, '0', STR_PAD_LEFT))

@section('content')
    <style>
        
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

        .tracking-wider {
            letter-spacing: 0.05em;
        }

        .sticky-actions {
            position: sticky;
            bottom: 20px;
            z-index: 1000;
        }

        .fee-item.waived {
            opacity: 0.7;
            background-color: #e9ecef !important;
        }

        /* Custom card styling matching request-view */
        .custom-card {
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .custom-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .border-top-accent {
            border-top: 3px solid #004080;
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
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

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: #0f4c8a !important;
            transform: translateY(-3px);
        }

/* Fee table styling */
.fee-table th {
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

/* Center checkboxes vertically in first column */
.fee-table td:first-child {
    vertical-align: middle !important;
    text-align: center !important;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

/* Add right padding to subtotal column (last column) */
.fee-table td:last-child {
    padding-right: 0.75rem !important;
}

/* Center the Waive header */
.fee-table th:first-child {
    text-align: center !important;
}
/* Force center the checkbox inside the form-check div */
.fee-table td:first-child .form-check {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: auto !important;
}

/* Remove any extra padding/margin from the checkbox input */
.fee-table td:first-child .form-check-input {
    margin: 0 !important;
    float: none !important;
    display: block !important;
}
    </style>

    <main id="main">
        <div class="view-container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/manage-requests">Requests</a></li>
                    <li class="breadcrumb-item">
                        <a href="/admin/requisition/{{ $requestId }}">Request
                            #{{ str_pad($requestId, 4, '0', STR_PAD_LEFT) }}</a>
                    </li>
                    <li class="breadcrumb-item active">Edit Financials</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Edit Financials</h2>
                    <p class="text-muted mb-0">Request #{{ str_pad($requestId, 4, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div id="statusBadge"></div>
            </div>

            <!-- Fee Management Section -->
            <div class="row g-3 mb-4">
                <!-- Base Fees Card (Facilities & Equipment combined) -->
                <div class="col-lg-7">
                    <div class="card custom-card border-top-accent">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Base Fees</h5>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="waiveAllSwitch">
                                <label class="form-check-label small" for="waiveAllSwitch">Waive All Fees</label>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle custom-table-modern mb-0 fee-table"
                                    style="font-size: 0.9rem;">
                                    <thead>
                                        <tr class="text-uppercase text-muted fw-bold align-middle"
                                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                            <th scope="col" class="border-0 text-start px-3" style="width: 5%;">
                                                <span>Waive</span>
                                            </th>
                                            <th scope="col" class="border-0 text-start px-3" style="width: 40%;">Item Name
                                            </th>
                                            <th scope="col" class="border-0 text-end px-3" style="width: 20%;">Unit Rate
                                            </th>
                                            <th scope="col" class="border-0 text-center px-3" style="width: 15%;">Quantity
                                            </th>
                                            <th scope="col" class="border-0 text-center px-3" style="width: 20%;">Duration
                                            </th>
                                            <th scope="col" class="border-0 text-end px-3" style="width: 20%;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="baseFeesTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <span class="text-uppercase fw-bold text-secondary tracking-wider"
                                style="font-size: 0.8rem;">Total Base Fee</span>
                            <span class="fw-bold fs-4 text-dark" id="totalBaseFeeDisplay">₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Miscellaneous Fees Card -->
                <div class="col-lg-5">
                    <div class="card custom-card border-top-accent">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">Miscellaneous Fees</h5>
                            <button id="addFeeBtn" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus me-1"></i> Add Fee/Discount
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="additionalFeesContainer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Total Fee -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card" style="background-color: #004080; border: none; border-radius: 12px;">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <div class="small text-white-50 mb-1">TOTAL APPROVED FEE</div>
                                    <div id="footerTotalFee" style="font-size: 2rem; font-weight: bold; color: white;">₱0.00
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Action Buttons -->
            <div class="sticky-actions mt-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/requisition/{{ $requestId }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Discard Changes
                    </a>
                    <button type="button" id="saveChangesBtn" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
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
                                    <input type="text" id="feeLabel" class="form-control" placeholder="Fee Label" required>
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

        <!-- Back to Top Button -->
        <button class="back-to-top" id="backToTop" title="Back to Top"><i class="bi bi-arrow-up"></i></button>
    </main>
@endsection

@section('scripts')
    <script>
        let currentFees = [];
        let currentRequestData = null;
        let hasChanges = false;
        let currentWaiverStates = {};
        let pendingFees = [];
        let deletedFeeIds = [];

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

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center border-0 position-fixed start-0 mb-2`;
            toast.style.cssText = 'z-index:1100;bottom:0;left:0;margin:1rem;opacity:0;transform:translateY(20px);transition:transform 0.4s ease, opacity 0.4s ease';
            toast.setAttribute('role', 'alert');
            const bgColor = type === 'success' ? '#004183ff' : '#dc3545';
            toast.style.backgroundColor = bgColor;
            toast.style.color = '#fff';
            toast.style.minWidth = '250px';
            toast.style.borderRadius = '0.3rem';
            toast.innerHTML = `<div class="d-flex align-items-center px-3 py-1"><i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i><div class="toast-body flex-grow-1" style="padding:0.25rem 0;">${message}</div><button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button></div>`;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
            bsToast.show();
            requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                setTimeout(() => { bsToast.hide(); toast.remove(); }, 400);
            }, 3000);
        }

        // Update total approved fee
        function updateTotalApprovedFee() {
            let totalBaseFee = 0;
            let totalMiscFees = 0;

            // Calculate base fees from table rows (only non-waived items)
            document.querySelectorAll('#baseFeesTableBody tr').forEach(row => {
                const isWaived = row.classList.contains('waived');
                if (!isWaived) {
                    const subtotalCell = row.querySelector('.subtotal-amount');
                    if (subtotalCell && subtotalCell.dataset.amount) {
                        const amount = parseFloat(subtotalCell.dataset.amount);
                        if (!isNaN(amount)) totalBaseFee += amount;
                    }
                }
            });

            // Calculate miscellaneous fees
            document.querySelectorAll('#additionalFeesContainer .fee-item').forEach(item => {
                const amountSpan = item.querySelector('.fw-bold');
                if (amountSpan && amountSpan.dataset.amount) {
                    const amount = parseFloat(amountSpan.dataset.amount);
                    if (!isNaN(amount)) totalMiscFees += amount;
                }
            });

            const totalApproved = totalBaseFee + totalMiscFees;
            const footerTotal = document.getElementById('footerTotalFee');
            const totalBaseDisplay = document.getElementById('totalBaseFeeDisplay');

            if (footerTotal) footerTotal.textContent = formatMoney(totalApproved);
            if (totalBaseDisplay) totalBaseDisplay.textContent = formatMoney(totalBaseFee);
        }

        // Add pending fee preview
        function addPendingFeePreview(type, label, amount, discountType = null, accountNum = null) {
            const container = document.getElementById('additionalFeesContainer');
            const tempId = `temp_${Date.now()}_${Math.random()}`;

            let amountText = '';
            let numericAmount = 0;
            let isDiscount = false;

            if (type === 'additional') {
                amountText = formatMoney(amount);
                numericAmount = amount;
            } else {
                isDiscount = true;
                if (discountType === 'Percentage') {
                    amountText = `-${amount}%`;
                    numericAmount = -amount;
                } else {
                    amountText = `-${formatMoney(amount)}`;
                    numericAmount = -amount;
                }
            }

            let labelHtml = escapeHtml(label);
            if (accountNum) labelHtml = `${escapeHtml(label)} <span class="text-muted small">(${escapeHtml(accountNum)})</span>`;

            pendingFees.push({
                tempId: tempId,
                type: type,
                label: label,
                amount: amount,
                discountType: discountType,
                accountNum: accountNum,
                numericAmount: numericAmount
            });

            const feeHtml = `<div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded-3" data-temp-id="${tempId}">
                <div><span class="${isDiscount ? 'text-danger' : ''}">${labelHtml}</span></div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold ${isDiscount ? 'text-danger' : ''}" data-amount="${numericAmount}">${amountText}</span>
                    <button class="btn btn-sm btn-outline-secondary remove-pending-fee-btn" data-temp-id="${tempId}"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>`;

            if (container.innerHTML.includes('No additional fees')) {
                container.innerHTML = feeHtml;
            } else {
                container.insertAdjacentHTML('beforeend', feeHtml);
            }

            container.querySelector(`.remove-pending-fee-btn[data-temp-id="${tempId}"]`).addEventListener('click', () => {
                removePendingFee(tempId);
            });

            hasChanges = true;
            updateTotalApprovedFee();
        }

        function removePendingFee(tempId) {
            pendingFees = pendingFees.filter(f => f.tempId !== tempId);
            const feeElement = document.querySelector(`.fee-item[data-temp-id="${tempId}"]`);
            if (feeElement) feeElement.remove();

            const container = document.getElementById('additionalFeesContainer');
            if (container.children.length === 0) {
                container.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center text-center text-muted" style="min-height: 300px;">
                    <i class="bi bi-coin fs-1 mb-3 opacity-50"></i>
                    <p class="mb-0">No additional fees or discounts</p>
                </div>`;
            }

            hasChanges = true;
            updateTotalApprovedFee();
        }

        function markFeeForDeletion(feeId, buttonElement) {
            const feeItem = buttonElement.closest('.fee-item');
            if (feeItem) {
                feeItem.style.opacity = '0.5';
                feeItem.style.backgroundColor = '#f8d7da';
                buttonElement.disabled = true;
                buttonElement.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                deletedFeeIds.push(feeId);
                hasChanges = true;
                updateTotalApprovedFee();
            }
        }

        // Show unsaved changes warning
        function showUnsavedChangesWarning(callback) {
            if (!hasChanges) {
                if (callback) callback();
                return;
            }

            const result = confirm('You have unsaved changes. Are you sure you want to leave? All unsaved changes will be lost.');
            if (result && callback) callback();
        }

        // Intercept navigation
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (link && hasChanges) {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    e.preventDefault();
                    showUnsavedChangesWarning(() => {
                        window.location.href = href;
                    });
                }
            }
        });

        window.addEventListener('beforeunload', (e) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        async function loadFinancialsData() {
            const requestId = {{ $requestId }};
            const adminToken = localStorage.getItem('adminToken');

            try {
                const response = await fetch(`/api/admin/requisition/${requestId}/view-data`, {
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
                const result = await response.json();
                if (result.success && result.data) {
                    currentRequestData = result.data;
                    currentFees = result.data.requisition_fees || [];
                    renderBaseFeesTable(result.data.requested_items, result.data.duration_hours);
                    renderAdditionalFees(result.data.requisition_fees || []);
                    hasChanges = false;
                    pendingFees = [];
                    deletedFeeIds = [];
                }
            } catch (error) {
                console.error('Error loading financials:', error);
                showToast('Failed to load financial data', 'error');
            }
        }

        function renderBaseFeesTable(requestedItems, durationHours) {
            const tbody = document.getElementById('baseFeesTableBody');
            if (!tbody) return;

            let html = '';

            // Render Facilities
            if (requestedItems.facilities && requestedItems.facilities.length > 0) {
                requestedItems.facilities.forEach(facility => {
                    const isPerHour = facility.rate_type === 'Per Hour';
                    const subtotal = isPerHour ? facility.fee * durationHours : facility.fee;
                    const rateLabel = isPerHour ? '/hr' : '/event';
                    const durationText = isPerHour ? `${durationHours.toFixed(1)} hrs` : '—';
                    const isWaived = facility.is_waived || false;

                    html += `<tr class="fee-item ${isWaived ? 'waived' : ''}" data-type="facility" data-id="${facility.requested_facility_id}">
                        <td class="text-center">
                            <div class="form-check">
                                <input class="form-check-input waiver-checkbox" type="checkbox" data-type="facility" data-id="${facility.requested_facility_id}" ${isWaived ? 'checked' : ''}>
                            </div>
                        </td>
                        <td class="py-3">
                            <div class="fw-semibold text-dark">${escapeHtml(facility.name)}</div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.3px;">Facility</small>
                        </td>
                        <td class="py-3 text-end fw-medium text-secondary">${formatMoney(facility.fee)}${rateLabel}</td>
                        <td class="py-3 text-center text-secondary">1</td>
                        <td class="py-3 text-center text-secondary">${durationText}</td>
                        <td class="py-3 text-end fw-bold text-dark subtotal-amount" data-amount="${subtotal}">${formatMoney(subtotal)}</td>
                     </tr>`;
                });
            }

            // Render Equipment
            if (requestedItems.equipment && requestedItems.equipment.length > 0) {
                requestedItems.equipment.forEach(equipment => {
                    const qty = equipment.quantity || 1;
                    const isPerHour = equipment.rate_type === 'Per Hour';
                    const subtotal = isPerHour ? (equipment.fee * durationHours) * qty : equipment.fee * qty;
                    const rateLabel = isPerHour ? '/hr' : '/event';
                    const durationText = isPerHour ? `${durationHours.toFixed(1)} hrs` : '—';
                    const isWaived = equipment.is_waived || false;

                    html += `<tr class="fee-item ${isWaived ? 'waived' : ''}" data-type="equipment" data-id="${equipment.requested_equipment_id}">
                        <td class="text-center">
                            <div class="form-check">
                                <input class="form-check-input waiver-checkbox" type="checkbox" data-type="equipment" data-id="${equipment.requested_equipment_id}" ${isWaived ? 'checked' : ''}>
                            </div>
                         </td>
                        <td class="py-3">
                            <div class="fw-semibold text-dark">${escapeHtml(equipment.name)}${equipment.quantity > 1 ? ` <span class="text-muted">(×${equipment.quantity})</span>` : ''}</div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.3px;">Equipment</small>
                         </td>
                        <td class="py-3 text-end fw-medium text-secondary">${formatMoney(equipment.fee)}${rateLabel}</td>
                        <td class="py-3 text-center text-secondary">${qty}</td>
                        <td class="py-3 text-center text-secondary">${durationText}</td>
                        <td class="py-3 text-end fw-bold text-dark subtotal-amount" data-amount="${subtotal}">${formatMoney(subtotal)}</td>
                     </tr>`;
                });
            }

            if (!html) {
                html = `<tr><td colspan="6" class="text-center text-muted py-4">No facilities or equipment requested</td></tr>`;
            }

            tbody.innerHTML = html;
            attachWaiverHandlers();
            updateTotalApprovedFee();
        }

        function renderAdditionalFees(requisitionFees) {
            const container = document.getElementById('additionalFeesContainer');
            if (!container) return;

            if (requisitionFees && requisitionFees.length > 0) {
                container.innerHTML = requisitionFees.map(fee => {
                    let amountText = '';
                    let isDiscount = false;
                    let numericAmount = 0;
                    if (fee.type === 'fee') {
                        amountText = formatMoney(fee.fee_amount);
                        numericAmount = fee.fee_amount;
                    } else if (fee.type === 'discount') {
                        isDiscount = true;
                        if (fee.discount_type === 'Percentage') {
                            amountText = `-${fee.discount_amount}%`;
                            numericAmount = -fee.discount_amount;
                        } else {
                            amountText = `-${formatMoney(fee.discount_amount)}`;
                            numericAmount = -fee.discount_amount;
                        }
                    }
                    let labelHtml = escapeHtml(fee.label);
                    if (fee.account_num) labelHtml = `${escapeHtml(fee.label)} <span class="text-muted small">(${escapeHtml(fee.account_num)})</span>`;
                    return `<div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded-3" data-fee-id="${fee.fee_id}">
                        <div><span class="${isDiscount ? 'text-danger' : ''}">${labelHtml}</span></div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold ${isDiscount ? 'text-danger' : ''}" data-amount="${numericAmount}">${amountText}</span>
                            <button class="btn btn-sm btn-outline-danger delete-fee-btn" data-fee-id="${fee.fee_id}"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>`;
                }).join('');

                container.querySelectorAll('.delete-fee-btn').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (confirm('Mark this fee/discount for deletion? You can save to confirm.')) {
                            markFeeForDeletion(btn.dataset.feeId, btn);
                        }
                    });
                });
            } else {
                container.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center text-center text-muted" style="min-height: 300px;">
                    <i class="bi bi-coin fs-1 mb-3 opacity-50"></i>
                    <p class="mb-0">No additional fees or discounts</p>
                </div>`;
            }
            updateTotalApprovedFee();
        }

        function attachWaiverHandlers() {
            document.querySelectorAll('#baseFeesTableBody .waiver-checkbox').forEach(checkbox => {
                const newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);
                newCheckbox.addEventListener('change', function () {
                    handleWaiverChange(this);
                    updateWaiveAllToggle();
                });
            });
        }

        function handleWaiverChange(checkbox) {
            const isWaived = checkbox.checked;
            const row = checkbox.closest('tr');
            if (row) {
                if (isWaived) {
                    row.classList.add('waived');
                } else {
                    row.classList.remove('waived');
                }
            }
            hasChanges = true;
            updateTotalApprovedFee();
        }

        function updateWaiveAllToggle() {
            const waiveAllSwitch = document.getElementById('waiveAllSwitch');
            if (!waiveAllSwitch) return;

            const checkboxes = document.querySelectorAll('#baseFeesTableBody .waiver-checkbox');
            if (checkboxes.length === 0) return;

            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            waiveAllSwitch.checked = allChecked;
        }

        function handleWaiveAll(switchElement) {
            const waiveAll = switchElement.checked;
            document.querySelectorAll('#baseFeesTableBody .waiver-checkbox').forEach(checkbox => {
                checkbox.checked = waiveAll;
                const row = checkbox.closest('tr');
                if (row) {
                    if (waiveAll) {
                        row.classList.add('waived');
                    } else {
                        row.classList.remove('waived');
                    }
                }
            });
            hasChanges = true;
            updateTotalApprovedFee();
        }

        async function saveChanges() {
            const requestId = {{ $requestId }};
            const adminToken = localStorage.getItem('adminToken');
            const saveBtn = document.getElementById('saveChangesBtn');

            const waivedFacilities = [], waivedEquipment = [];
            document.querySelectorAll('#baseFeesTableBody .waiver-checkbox').forEach(cb => {
                const itemId = parseInt(cb.dataset.id);
                const itemType = cb.dataset.type;
                if (cb.checked) {
                    if (itemType === 'facility') waivedFacilities.push(itemId);
                    else if (itemType === 'equipment') waivedEquipment.push(itemId);
                }
            });

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            try {
                const adminId = localStorage.getItem('adminId');
                const waiverResponse = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ waived_facilities: waivedFacilities, waived_equipment: waivedEquipment, admin_id: adminId })
                });

                if (!waiverResponse.ok) throw new Error('Failed to save waiver changes');

                for (const feeId of deletedFeeIds) {
                    await fetch(`/api/admin/requisition/${requestId}/fee/${feeId}`, {
                        method: 'DELETE',
                        headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                    });
                }

                for (const fee of pendingFees) {
                    let endpoint, body;
                    if (fee.type === 'additional') {
                        endpoint = `/api/admin/requisition/${requestId}/fee`;
                        body = { label: fee.label, fee_amount: fee.amount, account_num: fee.accountNum || null };
                    } else {
                        endpoint = `/api/admin/requisition/${requestId}/discount`;
                        body = { label: fee.label, discount_amount: fee.amount, discount_type: fee.discountType, account_num: fee.accountNum || null };
                    }

                    await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(body)
                    });
                }

                hasChanges = false;
                showToast('All changes saved successfully!', 'success');
                setTimeout(() => {
                    window.location.href = `/admin/requisition/${requestId}`;
                }, 1500);

            } catch (error) {
                showToast('Failed to save changes: ' + error.message, 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Changes';
            }
        }

        // Back to Top functionality
        const backToTopButton = document.getElementById('backToTop');
        if (backToTopButton) {
            window.addEventListener('scroll', () => backToTopButton.classList.toggle('show', window.pageYOffset > 300));
            backToTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
        }

        // Event Listeners
        document.getElementById('saveChangesBtn')?.addEventListener('click', saveChanges);
        document.getElementById('addFeeBtn')?.addEventListener('click', () => {
            const feeModal = new bootstrap.Modal(document.getElementById('feeModal'));
            feeModal.show();
        });
        document.getElementById('waiveAllSwitch')?.addEventListener('change', function () { handleWaiveAll(this); });

        document.getElementById('saveFeeBtn')?.addEventListener('click', async function () {
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
                addPendingFeePreview(type, label, value, discountType, accountNum);

                document.getElementById('feeValue').value = '';
                document.getElementById('feeType').value = '';
                document.getElementById('feeLabel').value = '';
                document.getElementById('accountNum').value = '';
                document.getElementById('discountTypeSection').style.display = 'none';

                const feeModal = bootstrap.Modal.getInstance(document.getElementById('feeModal'));
                feeModal.hide();

                showToast('Fee/discount added to pending changes', 'success');
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

        // Initialize
        loadFinancialsData();
    </script>
@endsection