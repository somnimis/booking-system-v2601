@extends('layouts.admin')

@section('title', 'Manage Request Purposes')

@section('content')
    <style>
        @media (max-width: 768px) {
            .page-header {
                flex-wrap: wrap !important;
            }

            .page-header>div:first-child {
                flex: 0 0 100% !important;
                padding-right: 0 !important;
                margin-bottom: 0.75rem;
            }
        }

        /* Purpose card styles */
        .purpose-card {
            transition: var(--transition);
        }

        .purpose-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .badge-routed {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .loading-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-header h5 {
            font-family: 'Fraunces', Georgia, serif;
            color: var(--navy);
            margin: 0;
        }

        /* Skeleton styles */
        .skeleton-wrapper {
            display: block;
        }

        .skeleton-line {
            background: linear-gradient(90deg, var(--surface) 25%, var(--border) 50%, var(--surface) 75%);
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
    </style>

    <main id="main">
        <div class="container-fluid px-4">

            <!-- Page Header with Add Button -->
            <div class="page-header d-flex align-items-center justify-content-between"
                style="background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; flex-wrap: nowrap;">
                <div class="flex-grow-1" style="flex: 1; min-width: 0; padding-right: 1rem;">
                    <h5 class="mb-1 d-flex align-items-center gap-2"
                        style="font-family: 'Fraunces', Georgia, serif; color: var(--navy);">
                        <i class="fa-regular fa-calendar-check"></i>
                        <span>Event Purposes</span>
                    </h5>

                    <p class="text-muted small mb-0" style="max-width: 100%;">
                        Manage the event purposes available for users to select when submitting a reservation, including
                        their descriptions, discount options, and assigned administrators.
                    </p>
                </div>
                <div style="flex-shrink: 0; white-space: nowrap;">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPurposeModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Purpose
                    </button>
                </div>
            </div>

            <!-- Purposes Grid -->
            <div class="section-card">
                <div class="section-body">
                    <div id="purposesLoading" class="loading-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="text-muted">Loading purposes...</p>
                        </div>
                    </div>
                    <div id="purposesContent" style="display: none;">
                        <div class="p-3">
                            <div class="row" id="purposesGrid"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Add Purpose Modal -->
    <div class="modal fade" id="addPurposeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Add New Purpose</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPurposeForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Purpose Name</label>
                            <input type="text" class="form-control" name="purpose_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Type</label>
                            <select class="form-select" name="discount_type">
                                <option value="flat">Flat Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Discount Value</label>
                            <input type="number" class="form-control" name="discount_fee" step="0.01" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department Ownership</label>
                            <select class="form-select" name="routes_to">
                                <option value="">Select department</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addPurposeForm" class="btn btn-primary">Add Purpose</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Purpose Modal -->
    <div class="modal fade" id="editPurposeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Edit Purpose</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editPurposeLoading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Loading purpose data...</p>
                    </div>
                    <div id="editPurposeContent" style="display: none;">
                        <form id="editPurposeForm">
                            @csrf
                            <input type="hidden" id="edit_purpose_id" name="purpose_id">
                            <div class="mb-3">
                                <label class="form-label">Purpose Name</label>
                                <input type="text" class="form-control" id="edit_purpose_name" name="purpose_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount Type</label>
                                <select class="form-select" id="edit_discount_type" name="discount_type">
                                    <option value="flat">Flat Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount Value</label>
                                <input type="number" class="form-control" id="edit_discount_fee" name="discount_fee"
                                    step="0.01" min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department Ownership</label>
                                <select class="form-select" id="edit_routes_to" name="routes_to">
                                    <option value="">Select department</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePurposeChanges">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePurposeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Confirm Deletion</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger mb-3" style="font-size: 2rem;"></i>
                    <p class="mb-1 fw-bold">Are you sure you want to delete this purpose?</p>
                    <p class="mb-3 text-muted">This action cannot be undone.</p>
                    <div id="deletePurposeDetails" class="bg-light p-3 rounded"></div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePurposeBtn">Delete Purpose</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        let purposesData = [];
        let departmentsList = [];

        document.addEventListener('DOMContentLoaded', function () {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');

            if (!token) {
                console.error('No authentication token found');
                if (typeof showToast === 'function') {
                    showToast('Authentication error. Please login again.', 'error');
                }
                return;
            }

            loadPurposes();
        });

        async function loadPurposes() {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
            const loadingEl = document.getElementById('purposesLoading');
            const contentEl = document.getElementById('purposesContent');

            if (loadingEl) loadingEl.style.display = 'flex';
            if (contentEl) contentEl.style.display = 'none';

            try {
                console.log('Fetching purposes and departments...');

                const [purposesRes, deptsRes] = await Promise.all([
                    fetch('/api/purposes', { headers: { 'Authorization': `Bearer ${token}` } }),
                    fetch('/api/departments/dropdown', { headers: { 'Authorization': `Bearer ${token}` } })
                ]);

                console.log('Purposes response status:', purposesRes.status);
                console.log('Departments response status:', deptsRes.status);

                const purposesResult = await purposesRes.json();
                const deptsResult = await deptsRes.json();

                console.log('Purposes data:', purposesResult);
                console.log('Departments data:', deptsResult);

                if (!purposesRes.ok) {
                    throw new Error(`Purposes API error (${purposesRes.status}): ${JSON.stringify(purposesResult)}`);
                }

                if (!deptsRes.ok) {
                    throw new Error(`Departments API error (${deptsRes.status}): ${JSON.stringify(deptsResult)}`);
                }

                if (purposesResult.success) {
                    purposesData = purposesResult.data || [];
                } else {
                    throw new Error(purposesResult.message || 'Failed to load purposes');
                }

                departmentsList = Array.isArray(deptsResult) ? deptsResult : (deptsResult.data || []);

                renderPurposes();
                populateDepartmentDropdowns();
                if (loadingEl) loadingEl.style.display = 'none';
                if (contentEl) contentEl.style.display = 'block';

            } catch (error) {
                console.error('Error loading purposes:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    response: error.response || 'No response object'
                });

                if (loadingEl) {
                    loadingEl.innerHTML = `
                                                        <div class="alert alert-danger">
                                                            <strong>Failed to load purposes</strong>
                                                            <br>
                                                            <small class="text-muted">${error.message || 'Unknown error'}</small>
                                                            <br>
                                                            <small class="text-muted">Check console for details</small>
                                                        </div>
                                                    `;
                }
            }
        }

        function populateDepartmentDropdowns() {
            const addSelect = document.querySelector('#addPurposeForm select[name="routes_to"]');
            const editSelect = document.getElementById('edit_routes_to');

            const options = departmentsList.map(dept =>
                `<option value="${dept.department_id}">${dept.department_name} ${dept.department_code ? '(' + dept.department_code + ')' : ''}</option>`
            ).join('');

            if (addSelect) {
                addSelect.innerHTML = '<option value="">Select department</option>' + options;
            }

            if (editSelect) {
                editSelect.innerHTML = '<option value="">Select department</option>' + options;
            }
        }

        function renderPurposes() {
            const grid = document.getElementById('purposesGrid');
            if (!grid) return;

            grid.innerHTML = '';

            if (purposesData.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No purposes found</p></div>';
                return;
            }

            purposesData.forEach(purpose => {
                const discountDisplay = purpose.discount_fee ?
                    `${purpose.discount_type === 'percentage' ? purpose.discount_fee + '%' : '₱' + parseFloat(purpose.discount_fee).toLocaleString()}` :
                    'No discount';
                const routedTo = purpose.routed_department ? purpose.routed_department.department_code : 'Not assigned';

                const card = `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card purpose-card h-100 border shadow-sm">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="card-title mb-0 fw-semibold" style="font-family: 'Fraunces', Georgia, serif;">
                                            ${purpose.purpose_name}
                                        </h6>
                                    </div>
                                    <div class="mb-2 d-flex align-items-center">
                                        <small class="text-muted me-2">Discount:</small>
                                        <span class="badge" style="background-color: var(--navy-light) !important; color: var(--navy);">${discountDisplay}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2">Department:</small>
                                        <div class="badge-routed d-inline-block" style="background-color: var(--navy-light) !important; color: var(--navy); padding: 5px 10px; border-radius: 20px; font-size: 0.75rem;">
                                            <i class="bi bi-share me-1"></i>
                                            ${routedTo}
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="editPurpose(${purpose.purpose_id})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePurpose(${purpose.purpose_id})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                grid.insertAdjacentHTML('beforeend', card);
            });
        }

        // Add purpose form submission
        const addPurposeForm = document.getElementById('addPurposeForm');
        if (addPurposeForm) {
            addPurposeForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const formData = new FormData(this);
                const data = {
                    purpose_name: formData.get('purpose_name'),
                    discount_type: formData.get('discount_type'),
                    discount_fee: parseFloat(formData.get('discount_fee')) || null,
                    routes_to: parseInt(formData.get('routes_to')) || null
                };

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : 'Add Purpose';
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
                    submitBtn.disabled = true;
                }

                try {
                    const response = await fetch('/api/purposes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Purpose added successfully', 'success');
                        }
                        bootstrap.Modal.getInstance(document.getElementById('addPurposeModal')).hide();
                        this.reset();
                        await loadPurposes();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Failed to add purpose', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error adding purpose', 'error');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }
            });
        }

        // Edit purpose
        window.editPurpose = async function (purposeId) {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
            const modal = new bootstrap.Modal(document.getElementById('editPurposeModal'));
            const loadingDiv = document.getElementById('editPurposeLoading');
            const contentDiv = document.getElementById('editPurposeContent');

            if (loadingDiv) loadingDiv.style.display = 'block';
            if (contentDiv) contentDiv.style.display = 'none';
            modal.show();

            try {
                const response = await fetch(`/api/purposes/${purposeId}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const result = await response.json();
                const purpose = result.data;

                document.getElementById('edit_purpose_id').value = purpose.purpose_id;
                document.getElementById('edit_purpose_name').value = purpose.purpose_name || '';
                document.getElementById('edit_discount_type').value = purpose.discount_type || 'percentage';
                document.getElementById('edit_discount_fee').value = purpose.discount_fee || '';

                const deptSelect = document.getElementById('edit_routes_to');
                if (deptSelect && purpose.routes_to) {
                    deptSelect.value = purpose.routes_to;
                }

                if (loadingDiv) loadingDiv.style.display = 'none';
                if (contentDiv) contentDiv.style.display = 'block';

            } catch (error) {
                console.error('Error loading purpose:', error);
                if (loadingDiv) loadingDiv.innerHTML = '<div class="alert alert-danger">Failed to load purpose details</div>';
            }
        };

        // Save edited purpose
        const savePurposeBtn = document.getElementById('savePurposeChanges');
        if (savePurposeBtn) {
            savePurposeBtn.addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const purposeId = document.getElementById('edit_purpose_id')?.value;
                const formData = {
                    purpose_name: document.getElementById('edit_purpose_name')?.value,
                    discount_type: document.getElementById('edit_discount_type')?.value,
                    discount_fee: parseFloat(document.getElementById('edit_discount_fee')?.value) || null,
                    routes_to: parseInt(document.getElementById('edit_routes_to')?.value) || null
                };

                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/api/purposes/${purposeId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Purpose updated successfully', 'success');
                        }
                        bootstrap.Modal.getInstance(document.getElementById('editPurposeModal')).hide();
                        await loadPurposes();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Update failed', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error updating purpose', 'error');
                    }
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        // Delete purpose
        window.deletePurpose = function (purposeId) {
            const purpose = purposesData.find(p => p.purpose_id === purposeId);
            const detailsEl = document.getElementById('deletePurposeDetails');
            if (detailsEl) {
                detailsEl.innerHTML = `
                                                                        <div class="row">
                                                                            <div class="col-4 fw-bold">Purpose:</div>
                                                                            <div class="col-8">${purpose ? purpose.purpose_name : 'Purpose ID: ' + purposeId}</div>
                                                                            ${purpose?.discount_fee ? `<div class="col-4 fw-bold">Discount:</div><div class="col-8">${purpose.discount_type === 'percentage' ? purpose.discount_fee + '%' : '₱' + parseFloat(purpose.discount_fee).toLocaleString()}</div>` : ''}
                                                                        </div>
                                                                    `;
            }

            window.purposeToDelete = purposeId;
            new bootstrap.Modal(document.getElementById('deletePurposeModal')).show();
        };

        // Confirm delete
        const confirmDeletePurposeBtn = document.getElementById('confirmDeletePurposeBtn');
        if (confirmDeletePurposeBtn) {
            confirmDeletePurposeBtn.addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const purposeId = window.purposeToDelete;
                if (!purposeId) return;

                const btn = this;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/api/purposes/${purposeId}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Purpose deleted successfully', 'success');
                        }
                        bootstrap.Modal.getInstance(document.getElementById('deletePurposeModal')).hide();
                        await loadPurposes();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Delete failed', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error deleting purpose', 'error');
                    }
                } finally {
                    btn.innerHTML = 'Delete Purpose';
                    btn.disabled = false;
                }
            });
        }
    </script>
@endsection