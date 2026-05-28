@extends('layouts.admin')
@section('title', 'Edit Financials - Request #' . str_pad($requestId, 4, '0', STR_PAD_LEFT))

@section('content')
<style>
    .fee-item.waived {
        opacity: 0.7;
        background-color: #e9ecef !important;
    }
    
    .sticky-actions {
        position: sticky;
        bottom: 20px;
        z-index: 1000;
    }
</style>

<main id="main">
<div class="view-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin/manage-requests">Requests</a></li>
            <li class="breadcrumb-item">
                <a href="/admin/requisition/{{ $requestId }}">Request #{{ str_pad($requestId, 4, '0', STR_PAD_LEFT) }}</a>
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

    <!-- Fee Breakdown Section -->
    <div class="row g-3 align-items-stretch mb-4">
        <div class="col-md-6 d-flex">
            <div class="card custom-card border-top-accent w-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Base Fees</h5>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="waiveAllSwitch">
                        <label class="form-check-label small" for="waiveAllSwitch">Waive All Fees</label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="baseFeesContainer">
                        <div id="facilitiesFees"></div>
                        <div id="equipmentFees"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 d-flex">
            <div class="card custom-card border-top-accent w-100">
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
            <div class="card" style="background-color: #004080; border: none; border-radius: 8px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div class="small text-white-50 mb-1">TOTAL APPROVED FEE</div>
                            <div id="footerTotalFee" style="font-size: 2rem; font-weight: bold; color: white;">₱0.00</div>
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
                            <input type="text" id="accountNum" class="form-control" placeholder="Enter account number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="feeValue" class="form-label">Amount</label>
                        <input type="number" id="feeValue" class="form-control" step="0.01" min="0.01" placeholder="Enter amount" required>
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
</main>
@endsection

@section('scripts')
<script>
    let currentFees = [];
    let currentRequestData = null;
    let hasChanges = false;
    let isRefreshing = false;
    let currentWaiverStates = {};
    let pendingFees = []; // Store pending fees/discounts to be added
    let deletedFeeIds = []; // Store IDs of fees marked for deletion

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

    // Save current waiver states
    function saveCurrentWaiverStates() {
        document.querySelectorAll('#baseFeesContainer .waiver-checkbox').forEach(cb => {
            const key = `${cb.dataset.type}_${cb.dataset.id}`;
            currentWaiverStates[key] = cb.checked;
        });
    }

    // Restore waiver states
    function restoreWaiverStates() {
        document.querySelectorAll('#baseFeesContainer .waiver-checkbox').forEach(cb => {
            const key = `${cb.dataset.type}_${cb.dataset.id}`;
            if (currentWaiverStates.hasOwnProperty(key)) {
                const shouldBeChecked = currentWaiverStates[key];
                cb.checked = shouldBeChecked;
                const itemRow = cb.closest('.fee-item');
                if (itemRow) {
                    if (shouldBeChecked) {
                        itemRow.classList.add('waived');
                    } else {
                        itemRow.classList.remove('waived');
                    }
                }
            }
        });
        
        updateWaiveAllToggle();
        updateTotalApprovedFee();
    }

    // Update the "Waive All" toggle state
    function updateWaiveAllToggle() {
        const waiveAllSwitch = document.getElementById('waiveAllSwitch');
        if (!waiveAllSwitch) return;
        
        const checkboxes = document.querySelectorAll('#baseFeesContainer .waiver-checkbox');
        if (checkboxes.length === 0) return;
        
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        waiveAllSwitch.checked = allChecked;
    }

    // Calculate and update total approved fee based on current selections
    function updateTotalApprovedFee() {
        let totalBaseFee = 0;
        let totalMiscFees = 0;
        
        // Calculate base fees (only non-waived items)
        document.querySelectorAll('#baseFeesContainer .fee-item').forEach(item => {
            const isWaived = item.classList.contains('waived');
            if (!isWaived) {
                const amountElement = item.querySelector('strong');
                if (amountElement && amountElement.dataset.amount) {
                    const amount = parseFloat(amountElement.dataset.amount);
                    if (!isNaN(amount)) totalBaseFee += amount;
                }
            }
        });
        
        // Calculate miscellaneous fees and discounts (including pending ones)
        document.querySelectorAll('#additionalFeesContainer .fee-item').forEach(item => {
            const amountSpan = item.querySelector('.fw-bold');
            if (amountSpan && amountSpan.dataset.amount) {
                const amount = parseFloat(amountSpan.dataset.amount);
                if (!isNaN(amount)) {
                    totalMiscFees += amount;
                }
            }
        });
        
        const totalApproved = totalBaseFee + totalMiscFees;
        const footerTotal = document.getElementById('footerTotalFee');
        if (footerTotal) {
            footerTotal.textContent = formatMoney(totalApproved);
        }
    }

    // Add a pending fee preview (no API call)
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
        
        // Store pending fee
        pendingFees.push({
            tempId: tempId,
            type: type,
            label: label,
            amount: amount,
            discountType: discountType,
            accountNum: accountNum,
            numericAmount: numericAmount
        });
        
        // Add to UI with delete button that removes from pending
        const feeHtml = `<div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded-3" data-temp-id="${tempId}">
            <div><span class="${isDiscount ? 'text-danger' : ''}">${labelHtml}</span></div>
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold ${isDiscount ? 'text-danger' : ''}" data-amount="${numericAmount}">${amountText}</span>
                <button class="btn btn-sm btn-warning remove-pending-fee-btn" data-temp-id="${tempId}"><i class="fa fa-undo"></i></button>
            </div>
        </div>`;
        
        if (container.innerHTML.includes('No additional fees')) {
            container.innerHTML = feeHtml;
        } else {
            container.insertAdjacentHTML('beforeend', feeHtml);
        }
        
        // Add remove handler
        container.querySelector(`.remove-pending-fee-btn[data-temp-id="${tempId}"]`).addEventListener('click', () => {
            removePendingFee(tempId);
        });
        
        hasChanges = true;
        updateTotalApprovedFee();
    }

    function removePendingFee(tempId) {
        // Remove from pendingFees array
        pendingFees = pendingFees.filter(f => f.tempId !== tempId);
        
        // Remove from UI
        const feeElement = document.querySelector(`.fee-item[data-temp-id="${tempId}"]`);
        if (feeElement) feeElement.remove();
        
        // If no fees left, show empty message
        const container = document.getElementById('additionalFeesContainer');
        if (container.children.length === 0) {
            container.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center text-center text-muted" style="min-height: 400px;">
                <i class="fa fa-coins fa-3x mb-3 opacity-50"></i>
                <p class="mb-0">No additional fees or discounts</p>
            </div>`;
        }
        
        hasChanges = true;
        updateTotalApprovedFee();
    }

    // Mark existing fee for deletion
    function markFeeForDeletion(feeId, buttonElement) {
        const feeItem = buttonElement.closest('.fee-item');
        if (feeItem) {
            // Style it differently to show it will be deleted
            feeItem.style.opacity = '0.5';
            feeItem.style.backgroundColor = '#f8d7da';
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            deletedFeeIds.push(feeId);
            hasChanges = true;
            updateTotalApprovedFee();
        }
    }

    // Show unsaved changes warning modal
    function showUnsavedChangesWarning(callback) {
        if (!hasChanges) {
            if (callback) callback();
            return;
        }
        
        const modalHtml = `
            <div class="modal fade" id="unsavedChangesModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Unsaved Changes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3 d-block"></i>
                                <p class="mb-2">You have unsaved changes to the financials.</p>
                                <p class="text-muted small">Are you sure you want to leave? All unsaved changes will be lost.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Stay</button>
                            <button type="button" class="btn btn-danger" id="confirmLeaveBtn">Leave & Discard Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const existingModal = document.getElementById('unsavedChangesModal');
        if (existingModal) existingModal.remove();
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('unsavedChangesModal'));
        
        document.getElementById('confirmLeaveBtn').addEventListener('click', () => {
            modal.hide();
            if (callback) callback();
        });
        
        modal.show();
    }

    // Prevent page refresh/close with unsaved changes
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
                renderBaseFees(result.data.requested_items, result.data.duration_hours);
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

    function renderBaseFees(requestedItems, durationHours) {
        const facilitiesContainer = document.getElementById('facilitiesFees');
        const equipmentContainer = document.getElementById('equipmentFees');
        if (!facilitiesContainer || !equipmentContainer) return;

        if (requestedItems.facilities && requestedItems.facilities.length > 0) {
            facilitiesContainer.innerHTML = requestedItems.facilities.map(facility => {
                let itemTotal = facility.rate_type === 'Per Hour' ? facility.fee * durationHours : facility.fee;
                let rateDesc = facility.rate_type === 'Per Hour' ? `${formatMoney(facility.fee)}/hr × ${durationHours.toFixed(1)} hrs` : `${formatMoney(facility.fee)}/event`;
                return `<div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded-3 ${facility.is_waived ? 'waived' : ''}">
                    <div class="d-flex align-items-center">
                        <div class="form-check me-2">
                            <input class="form-check-input waiver-checkbox" type="checkbox" data-type="facility" data-id="${facility.requested_facility_id}" ${facility.is_waived ? 'checked' : ''}>
                        </div>
                        <span>${escapeHtml(facility.name)}</span>
                    </div>
                    <div class="text-end">
                        <small>${rateDesc}</small>
                        <div><strong data-amount="${itemTotal}">${formatMoney(itemTotal)}</strong></div>
                    </div>
                </div>`;
            }).join('');
        } else {
            facilitiesContainer.innerHTML = '<div class="text-muted small">No facilities requested</div>';
        }

        if (requestedItems.equipment && requestedItems.equipment.length > 0) {
            equipmentContainer.innerHTML = requestedItems.equipment.map(equipment => {
                let itemTotal = equipment.rate_type === 'Per Hour' ? (equipment.fee * durationHours) * (equipment.quantity || 1) : equipment.fee * (equipment.quantity || 1);
                let rateDesc = equipment.rate_type === 'Per Hour' ? `${formatMoney(equipment.fee)}/hr × ${durationHours.toFixed(1)} hrs × ${equipment.quantity || 1}` : `${formatMoney(equipment.fee)}/event × ${equipment.quantity || 1}`;
                return `<div class="fee-item d-flex justify-content-between align-items-center mb-2 p-2 rounded-3 ${equipment.is_waived ? 'waived' : ''}">
                    <div class="d-flex align-items-center">
                        <div class="form-check me-2">
                            <input class="form-check-input waiver-checkbox" type="checkbox" data-type="equipment" data-id="${equipment.requested_equipment_id}" ${equipment.is_waived ? 'checked' : ''}>
                        </div>
                        <span>${escapeHtml(equipment.name)} ${equipment.quantity > 1 ? `(×${equipment.quantity})` : ''}</span>
                    </div>
                    <div class="text-end">
                        <small>${rateDesc}</small>
                        <div><strong data-amount="${itemTotal}">${formatMoney(itemTotal)}</strong></div>
                    </div>
                </div>`;
            }).join('');
        } else {
            equipmentContainer.innerHTML = '<div class="text-muted small">No equipment requested</div>';
        }
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
                        <button class="btn btn-sm btn-danger delete-fee-btn" data-fee-id="${fee.fee_id}"><i class="fa fa-times"></i></button>
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
            container.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center text-center text-muted" style="min-height: 400px;">
                <i class="fa fa-coins fa-3x mb-3 opacity-50"></i>
                <p class="mb-0">No additional fees or discounts</p>
            </div>`;
        }
        updateTotalApprovedFee();
    }

    function attachWaiverHandlers() {
        document.querySelectorAll('#baseFeesContainer .waiver-checkbox').forEach(checkbox => {
            const newCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(newCheckbox, checkbox);
            newCheckbox.addEventListener('change', function () { 
                handleWaiverChange(this);
                updateWaiveAllToggle();
            });
        });
    }

    async function handleWaiverChange(checkbox) {
        const isWaived = checkbox.checked;
        const itemRow = checkbox.closest('.fee-item');
        if (itemRow) itemRow.classList.toggle('waived', isWaived);
        hasChanges = true;
        updateTotalApprovedFee();
    }

    async function handleWaiveAll(switchElement) {
        const waiveAll = switchElement.checked;
        document.querySelectorAll('.waiver-checkbox').forEach(checkbox => {
            checkbox.checked = waiveAll;
            const itemRow = checkbox.closest('.fee-item');
            if (itemRow) {
                if (waiveAll) {
                    itemRow.classList.add('waived');
                } else {
                    itemRow.classList.remove('waived');
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
        document.querySelectorAll('.waiver-checkbox').forEach(cb => {
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
            // 1. Save waiver changes
            const adminId = localStorage.getItem('adminId');
            const waiverResponse = await fetch(`/api/admin/requisition/${requestId}/waive`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${adminToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ waived_facilities: waivedFacilities, waived_equipment: waivedEquipment, admin_id: adminId })
            });
            
            if (!waiverResponse.ok) throw new Error('Failed to save waiver changes');
            
            // 2. Delete marked fees
            for (const feeId of deletedFeeIds) {
                await fetch(`/api/admin/requisition/${requestId}/fee/${feeId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${adminToken}`, 'Accept': 'application/json' }
                });
            }
            
            // 3. Add pending fees
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

    // Intercept navigation links and back button
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link && hasChanges && !isRefreshing) {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                e.preventDefault();
                showUnsavedChangesWarning(() => {
                    window.location.href = href;
                });
            }
        }
    });

    // Intercept back button
    window.addEventListener('popstate', function(e) {
        if (hasChanges) {
            e.preventDefault();
            showUnsavedChangesWarning(() => {
                window.history.back();
            });
        }
    });

    // Event Listeners
    document.getElementById('saveChangesBtn')?.addEventListener('click', saveChanges);
    document.getElementById('addFeeBtn')?.addEventListener('click', () => {
        const feeModal = new bootstrap.Modal(document.getElementById('feeModal'));
        feeModal.show();
    });
    document.getElementById('waiveAllSwitch')?.addEventListener('change', function() { handleWaiveAll(this); });
    
    document.getElementById('saveFeeBtn')?.addEventListener('click', async function() {
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
            // Just add preview, no API call
            addPendingFeePreview(type, label, value, discountType, accountNum);
            
            // Clear form
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
    
    document.getElementById('feeType')?.addEventListener('change', function() {
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