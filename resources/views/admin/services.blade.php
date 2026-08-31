@extends('layouts.admin')

@section('title', 'Manage Extra Services')

@section('content')
    <style>
        .service-card {
            transition: var(--transition);
        }

        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .badge-manager {
            background-color: #f5f6fa;
            color: #4a5568;
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
    </style>

    <main id="main">
        <div class="container-fluid px-4">
            <div class="section-card">
                <div class="section-body">
                    <div class="page-header">
                        <div>
                            <h5 class="mb-1"><i class="bi bi-grid me-2"></i>Extra Services</h5>
                            <p class="text-muted small mb-0">Manage additional services offered, including department
                                ownership, account numbers, and service fees.</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Service
                        </button>
                    </div>
                    <div id="servicesLoading" class="loading-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="text-muted">Loading services...</p>
                        </div>
                    </div>
                    <div id="servicesContent" style="display: none;">
                        <div class="p-3">
                            <div class="row" id="servicesGrid"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Add New Service</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addServiceForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" class="form-control" name="service_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department Ownership</label>
                            <select class="form-select" name="managed_by">
                                <option value="">Select department</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="number" class="form-control" name="account_number"
                                placeholder="Enter account number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Fee</label>
                            <input type="number" class="form-control" name="service_fee" step="0.01" placeholder="0.00">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addServiceForm" class="btn btn-primary" id="addServiceSubmitBtn">Add
                        Service</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Edit Service</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editServiceLoading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Loading service data...</p>
                    </div>
                    <div id="editServiceContent" style="display: none;">
                        <form id="editServiceForm">
                            @csrf
                            <input type="hidden" id="edit_service_id" name="service_id">
                            <div class="mb-3">
                                <label class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="edit_service_name" name="service_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department Ownership</label>
                                <select class="form-select" id="edit_managed_by" name="managed_by">
                                    <option value="">Select department</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="number" class="form-control" id="edit_account_number" name="account_number"
                                    placeholder="Enter account number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Service Fee</label>
                                <input type="number" class="form-control" id="edit_service_fee" name="service_fee"
                                    step="0.01" min="0" placeholder="0.00">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveServiceChanges">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Confirm Deletion</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger mb-3" style="font-size: 2rem;"></i>
                    <p class="mb-1 fw-bold">Are you sure you want to delete this service?</p>
                    <p class="mb-3 text-muted">This action cannot be undone.</p>
                    <div id="deleteServiceDetails" class="bg-light p-3 rounded"></div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteServiceBtn">Delete Service</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Services Modal -->
    <div class="modal fade" id="assignServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Assign Services to Admin</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assignServiceForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Select Admin</label>
                            <select class="form-select" id="assign_admin_id" name="admin_id" required>
                                <option value="">Select administrator</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Services</label>
                            <div id="assignServiceChecklist" class="border rounded p-3"
                                style="max-height: 200px; overflow-y: auto;"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="assignServicesBtn">Assign Services</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script>
        let servicesData = [];
        let adminsList = [];

        document.addEventListener('DOMContentLoaded', function () {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');

            if (!token) {
                console.error('No authentication token found');
                if (typeof showToast === 'function') {
                    showToast('Authentication error. Please login again.', 'error');
                }
                return;
            }

            loadServices();
        });

        let departmentsData = [];

        async function loadDepartments() {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
            try {
                const response = await fetch('/api/departments', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const result = await response.json();
                departmentsData = Array.isArray(result) ? result : (result.data || []);
                populateDepartmentDropdowns();
            } catch (error) {
                console.error('Error loading departments:', error);
            }
        }

        function populateDepartmentDropdowns() {
            const addSelect = document.querySelector('#addServiceForm select[name="managed_by"]');
            const editSelect = document.getElementById('edit_managed_by');

            const options = departmentsData.map(dept =>
                `<option value="${dept.department_id}">${dept.department_name} (${dept.department_code || ''})</option>`
            ).join('');

            if (addSelect) {
                addSelect.innerHTML = '<option value="">Select department</option>' + options;
            }

            if (editSelect) {
                editSelect.innerHTML = '<option value="">Select department</option>' + options;
            }
        }

        async function loadServices() {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');

            try {
                const [servicesRes, adminsRes, deptsRes] = await Promise.all([
                    fetch('/api/extra-services', { headers: { 'Authorization': `Bearer ${token}` } }),
                    fetch('/api/admins', { headers: { 'Authorization': `Bearer ${token}` } }),
                    fetch('/api/departments', { headers: { 'Authorization': `Bearer ${token}` } })
                ]);

                const servicesResult = await servicesRes.json();
                const adminsResult = await adminsRes.json();
                const deptsResult = await deptsRes.json();

                servicesData = Array.isArray(servicesResult) ? servicesResult : (servicesResult.data || []);
                adminsList = adminsResult.success ? (adminsResult.data || []) : [];
                departmentsData = Array.isArray(deptsResult) ? deptsResult : (deptsResult.data || []);

                renderServices();
                populateAdminDropdowns();
                populateDepartmentDropdowns();
                populateAssignChecklist();

                // Remove loading overlay entirely
                document.getElementById('servicesLoading').style.display = 'none';
                document.getElementById('servicesContent').style.display = 'block';

            } catch (error) {
                console.error('Error loading services:', error);
                document.getElementById('servicesLoading').innerHTML = '<div class="alert alert-danger">Failed to load services</div>';
            }
        }

        function populateAdminDropdowns() {
            const editSelect = document.getElementById('edit_admin_id');
            const assignSelect = document.getElementById('assign_admin_id');

            const options = adminsList.map(admin =>
                `<option value="${admin.admin_id}">${admin.full_name} ${admin.title ? '(' + admin.title + ')' : ''}</option>`
            ).join('');

            if (editSelect) {
                editSelect.innerHTML = '<option value="">Select administrator</option>' + options;
            }

            if (assignSelect) {
                assignSelect.innerHTML = '<option value="">Select administrator</option>' + options;
            }
        }

        function populateAssignChecklist() {
            const container = document.getElementById('assignServiceChecklist');
            if (!container) return;

            container.innerHTML = '';
            servicesData.forEach(service => {
                container.innerHTML += `
                                                                                                        <div class="form-check">
                                                                                                            <input class="form-check-input assign-service-cb" type="checkbox" value="${service.service_id}" id="assign_service_${service.service_id}">
                                                                                                            <label class="form-check-label" for="assign_service_${service.service_id}">${service.service_name}</label>
                                                                                                        </div>
                                                                                                    `;
            });
        }

        function renderServices() {
            const grid = document.getElementById('servicesGrid');
            if (!grid) return;

            grid.innerHTML = '';

            if (!servicesData || servicesData.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No services found</p></div>';
                return;
            }

            servicesData.forEach(service => {
                // Find department name from departmentsData
                const department = departmentsData.find(d => d.department_id === service.managed_by);
                const managedByName = department ? department.department_code : '--';

                const card = `
                                                                            <div class="col-md-6 col-lg-4 mb-3">
                                                                                <div class="card service-card h-100 border shadow-sm">
                                                                                    <div class="card-body">
                                                                                        <h6 class="card-title mb-2 fw-semibold" style="font-family: 'Fraunces', Georgia, serif;">
                                                                                            <i class="bi bi-grid me-2" style="color: var(--navy);"></i>
                                                                                            ${service.service_name || '--'}
                                                                                        </h6>

                                                                                        <div class="mb-1">
                                                                                            <small class="text-muted">Managed By:</small>
                                                                                            <span class="ms-1">${managedByName}</span>
                                                                                        </div>

                                                                                        <div class="mb-1">
                                                                                            <small class="text-muted">Account Number:</small>
                                                                                            <span class="ms-1">${service.account_number !== null && service.account_number !== undefined ? service.account_number : '--'}</span>
                                                                                        </div>

                                                                                        <div>
                                                                                            <small class="text-muted">Fee:</small>
                                                                                            <span class="ms-1">
                                                                                                ${service.service_fee !== null && service.service_fee !== undefined
                        ? `₱${parseFloat(service.service_fee).toLocaleString()}`
                        : '--'}
                                                                                            </span>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2">
                                                                                        <button class="btn btn-sm btn-primary" onclick="editService(${service.service_id})">
                                                                                            <i class="bi bi-pencil"></i> Edit
                                                                                        </button>
                                                                                        <button class="btn btn-sm btn-danger" onclick="deleteService(${service.service_id})">
                                                                                            <i class="bi bi-trash"></i> Delete
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        `;

                grid.insertAdjacentHTML('beforeend', card);
            });
        }


        // Add service form submission
        const addServiceForm = document.getElementById('addServiceForm');
        if (addServiceForm) {
            addServiceForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const formData = new FormData(this);

                // Get values from number inputs
                const accountNumberInput = this.querySelector('input[name="account_number"]');
                const serviceFeeInput = this.querySelector('input[name="service_fee"]');

                let accountNumber = accountNumberInput ? parseFloat(accountNumberInput.value) : null;
                let serviceFee = serviceFeeInput ? parseFloat(serviceFeeInput.value) : null;

                // Check if values are valid numbers
                if (isNaN(accountNumber) || accountNumberInput?.value === '') accountNumber = null;
                if (isNaN(serviceFee) || serviceFeeInput?.value === '') serviceFee = null;

                const data = {
                    service_name: formData.get('service_name'),
                    managed_by: formData.get('managed_by') || null,
                    account_number: accountNumber,
                    service_fee: serviceFee
                };

                console.log('Sending data:', data);

                const submitBtn = document.getElementById('addServiceSubmitBtn');
                const originalText = submitBtn ? submitBtn.innerHTML : 'Add Service';
                if (submitBtn) {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
                    submitBtn.disabled = true;
                }

                try {
                    const response = await fetch('/api/extra-services', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    console.log('Response:', result);

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Service added successfully', 'success');
                        }
                        // Add delay before closing
                        await new Promise(resolve => setTimeout(resolve, 500));
                        bootstrap.Modal.getInstance(document.getElementById('addServiceModal')).hide();
                        this.reset();
                        await loadServices();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Failed to add service', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error adding service', 'error');
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }
            });
        }


        // Edit service
        window.editService = async function (serviceId) {
            const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
            const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
            const loadingDiv = document.getElementById('editServiceLoading');
            const contentDiv = document.getElementById('editServiceContent');

            if (loadingDiv) loadingDiv.style.display = 'block';
            if (contentDiv) contentDiv.style.display = 'none';
            modal.show();

            try {
                const service = servicesData.find(s => s.service_id === serviceId);
                if (service) {
                    document.getElementById('edit_service_id').value = service.service_id;
                    document.getElementById('edit_service_name').value = service.service_name || '';
                    document.getElementById('edit_managed_by').value = service.managed_by || '';
                    // Set empty string for null/undefined values so the input shows blank
                    document.getElementById('edit_account_number').value = service.account_number !== null && service.account_number !== undefined ? service.account_number : '';
                    document.getElementById('edit_service_fee').value = service.service_fee !== null && service.service_fee !== undefined ? service.service_fee : '';
                }

                if (loadingDiv) loadingDiv.style.display = 'none';
                if (contentDiv) contentDiv.style.display = 'block';

            } catch (error) {
                console.error('Error loading service:', error);
                if (loadingDiv) loadingDiv.innerHTML = '<div class="alert alert-danger">Failed to load service details</div>';
            }
        };

        // Save edited service
        const saveServiceBtn = document.getElementById('saveServiceChanges');
        if (saveServiceBtn) {
            saveServiceBtn.addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const serviceId = document.getElementById('edit_service_id')?.value;

                // Get values - for number inputs, use .valueAsNumber or parse the value
                const accountNumberEl = document.getElementById('edit_account_number');
                const serviceFeeEl = document.getElementById('edit_service_fee');

                // Use valueAsNumber for number inputs, which returns NaN for empty fields
                let accountNumber = accountNumberEl?.valueAsNumber;
                let serviceFee = serviceFeeEl?.valueAsNumber;

                // Convert NaN to null
                if (isNaN(accountNumber)) accountNumber = null;
                if (isNaN(serviceFee)) serviceFee = null;

                const formData = {
                    service_name: document.getElementById('edit_service_name')?.value,
                    managed_by: document.getElementById('edit_managed_by')?.value || null,
                    account_number: accountNumber,
                    service_fee: serviceFee
                };

                console.log('Updating with data:', formData);

                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/api/extra-services/${serviceId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();
                    console.log('Update response:', result);

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Service updated successfully', 'success');
                        }
                        // Add delay before closing
                        await new Promise(resolve => setTimeout(resolve, 500));
                        bootstrap.Modal.getInstance(document.getElementById('editServiceModal')).hide();
                        await loadServices();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Update failed', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error updating service', 'error');
                    }
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        // Delete service
        window.deleteService = function (serviceId) {
            const service = servicesData.find(s => s.service_id === serviceId);
            const detailsEl = document.getElementById('deleteServiceDetails');
            if (detailsEl) {
                detailsEl.innerHTML = `
                                                                                                        <div class="row">
                                                                                                            <div class="col-4 fw-bold">Service:</div>
                                                                                                            <div class="col-8">${service ? service.service_name : 'Service ID: ' + serviceId}</div>
                                                                                                        </div>
                                                                                                    `;
            }

            window.serviceToDelete = serviceId;
            new bootstrap.Modal(document.getElementById('deleteServiceModal')).show();
        };

        // Confirm delete
        const confirmDeleteServiceBtn = document.getElementById('confirmDeleteServiceBtn');
        if (confirmDeleteServiceBtn) {
            confirmDeleteServiceBtn.addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const serviceId = window.serviceToDelete;
                if (!serviceId) return;

                const btn = this;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                btn.disabled = true;

                try {
                    const response = await fetch(`/api/extra-services/${serviceId}`, {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
                    });

                    const result = await response.json();

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('Service deleted successfully', 'success');
                        }
                        // Add delay before closing
                        await new Promise(resolve => setTimeout(resolve, 500));
                        bootstrap.Modal.getInstance(document.getElementById('deleteServiceModal')).hide();
                        await loadServices();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Delete failed', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error deleting service', 'error');
                    }
                } finally {
                    btn.innerHTML = 'Delete Service';
                    btn.disabled = false;
                }
            });
        }

        // Assign services to admin
        const assignServicesBtn = document.getElementById('assignServicesBtn');
        if (assignServicesBtn) {
            assignServicesBtn.addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
                const adminId = document.getElementById('assign_admin_id')?.value;
                const selectedServices = Array.from(document.querySelectorAll('.assign-service-cb:checked')).map(cb => parseInt(cb.value));

                if (!adminId) {
                    if (typeof showToast === 'function') {
                        showToast('Please select an administrator', 'error');
                    }
                    return;
                }

                if (selectedServices.length === 0) {
                    if (typeof showToast === 'function') {
                        showToast('Please select at least one service', 'error');
                    }
                    return;
                }

                const btn = this;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assigning...';
                btn.disabled = true;

                try {
                    const response = await fetch('api/extra-services/assign', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value
                        },
                        body: JSON.stringify({
                            admin_id: parseInt(adminId),
                            service_ids: selectedServices
                        })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Services assigned successfully', 'success');
                        }
                        bootstrap.Modal.getInstance(document.getElementById('assignServiceModal')).hide();
                        document.querySelectorAll('.assign-service-cb').forEach(cb => cb.checked = false);
                        document.getElementById('assign_admin_id').value = '';
                        await loadServices();
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(result.message || 'Assignment failed', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('Error assigning services', 'error');
                    }
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }
    </script>
@endsection