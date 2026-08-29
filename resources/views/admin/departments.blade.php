@extends('layouts.admin')

@section('title', 'Manage Departments')

@section('content')
<style>
    /* Department card styles */
    .dept-card {
        transition: var(--transition);
        border-left: 4px solid transparent;
    }

    .dept-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .dept-card .card-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        color: var(--white);
    }

    .admin-badge {
        background-color: var(--surface);
        border-radius: 20px;
        padding: 4px 12px;
        margin: 4px;
        display: inline-block;
        font-size: 0.8rem;
        border: 1px solid var(--border);
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
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

<main id="main">
    <div class="container-fluid px-4">

        <!-- Page Header with Add Button -->
        <div class="page-header">
            <h5><i class="bi bi-building me-2"></i>Departments</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="bi bi-plus-circle me-2"></i>Add Department
            </button>
        </div>

        <!-- Departments Grid -->
        <div class="section-card">
            <div class="section-body">
                <div id="deptLoading" class="loading-container">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Loading departments...</p>
                    </div>
                </div>
                <div id="departmentsContent" style="display: none;">
                    <div class="p-3">
                        <div class="row" id="departmentsGrid"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Add New Department</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addDepartmentForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" class="form-control" name="department_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Code</label>
                        <input type="text" class="form-control" name="department_code" placeholder="e.g., CAS">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addDepartmentForm" class="btn btn-primary">Add Department</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Edit Department</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editDeptLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">Loading department data...</p>
                </div>
                <div id="editDeptContent" style="display: none;">
                    <form id="editDepartmentForm">
                        @csrf
                        <input type="hidden" id="edit_dept_id" name="department_id">
                        <div class="mb-3">
                            <label class="form-label">Department Name</label>
                            <input type="text" class="form-control" id="edit_dept_name" name="department_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department Code</label>
                            <input type="text" class="form-control" id="edit_dept_code" name="department_code">
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveDepartmentChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDeptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Confirm Deletion</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger mb-3" style="font-size: 2rem;"></i>
                <p class="mb-1 fw-bold">Are you sure you want to delete this department?</p>
                <p class="mb-3 text-muted">This action cannot be undone.</p>
                <div id="deleteDeptDetails" class="bg-light p-3 rounded"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteDeptBtn">Delete Department</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/admin/toast.js') }}"></script>
<script>
let departmentsData = [];

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    
    if (!token) {
        console.error('No authentication token found');
        if (typeof showToast === 'function') {
            showToast('Authentication error. Please login again.', 'error');
        }
        return;
    }
    
    loadDepartments();
});

async function loadDepartments() {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    const loadingEl = document.getElementById('deptLoading');
    const contentEl = document.getElementById('departmentsContent');
    
    if (loadingEl) loadingEl.style.display = 'flex';
    if (contentEl) contentEl.style.display = 'none';
    
    try {
        const response = await fetch('/api/departments', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        
        if (result.success) {
            departmentsData = result.data || [];
            renderDepartments();
            if (loadingEl) loadingEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'block';
        } else {
            throw new Error(result.message || 'Failed to load departments');
        }
    } catch (error) {
        console.error('Error loading departments:', error);
        if (loadingEl) loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load departments</div>';
    }
}

function renderDepartments() {
    const grid = document.getElementById('departmentsGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    if (departmentsData.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center py-5"><p class="text-muted">No departments found</p></div>';
        return;
    }
    
    departmentsData.forEach(dept => {
        const adminCount = dept.admins ? dept.admins.length : 0;
        const card = `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card dept-card h-100 border shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold" style="font-family: 'Fraunces', Georgia, serif;">
                            ${dept.department_name}
                            ${dept.department_code ? `<span class="badge bg-light text-dark ms-2">${dept.department_code}</span>` : ''}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Assigned Administrators (${adminCount})</small>
                        </div>
                        <div>
                            ${adminCount > 0 ? 
                                dept.admins.map(admin => `
                                    <div class="admin-badge">
                                        <i class="bi bi-person-circle me-1"></i>
                                        ${admin.full_name}
                                        ${admin.title ? `<small class="text-muted">(${admin.title})</small>` : ''}
                                    </div>
                                `).join('') : 
                                '<p class="text-muted mb-0">No administrators assigned</p>'
                            }
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
                        <small class="text-muted">Total: ${adminCount} admin(s)</small>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editDepartment(${dept.department_id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDepartment(${dept.department_id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        grid.insertAdjacentHTML('beforeend', card);
    });
}

// Add department form submission
const addDeptForm = document.getElementById('addDepartmentForm');
if (addDeptForm) {
    addDeptForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const formData = new FormData(this);
        const data = {
            department_name: formData.get('department_name'),
            department_code: formData.get('department_code') || null
        };
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : 'Add Department';
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            submitBtn.disabled = true;
        }
        
        try {
            const response = await fetch('/api/departments', {
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
                    showToast('Department added successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('addDepartmentModal')).hide();
                this.reset();
                await loadDepartments();
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Failed to add department', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error adding department', 'error');
            }
        } finally {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    });
}

// Edit department
window.editDepartment = async function(deptId) {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    const modal = new bootstrap.Modal(document.getElementById('editDepartmentModal'));
    const loadingDiv = document.getElementById('editDeptLoading');
    const contentDiv = document.getElementById('editDeptContent');
    
    if (loadingDiv) loadingDiv.style.display = 'block';
    if (contentDiv) contentDiv.style.display = 'none';
    modal.show();
    
    try {
        const response = await fetch(`/api/departments/${deptId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        const dept = result.data;
        
        document.getElementById('edit_dept_id').value = dept.department_id;
        document.getElementById('edit_dept_name').value = dept.department_name || '';
        document.getElementById('edit_dept_code').value = dept.department_code || '';
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        if (contentDiv) contentDiv.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading department:', error);
        if (loadingDiv) loadingDiv.innerHTML = '<div class="alert alert-danger">Failed to load department details</div>';
    }
};

// Save edited department
const saveDeptBtn = document.getElementById('saveDepartmentChanges');
if (saveDeptBtn) {
    saveDeptBtn.addEventListener('click', async function() {
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const deptId = document.getElementById('edit_dept_id')?.value;
        const formData = {
            department_name: document.getElementById('edit_dept_name')?.value,
            department_code: document.getElementById('edit_dept_code')?.value || null
        };
        
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`/api/departments/${deptId}`, {
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
                    showToast('Department updated successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('editDepartmentModal')).hide();
                await loadDepartments();
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Update failed', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error updating department', 'error');
            }
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// Delete department
window.deleteDepartment = function(deptId) {
    const dept = departmentsData.find(d => d.department_id === deptId);
    const detailsEl = document.getElementById('deleteDeptDetails');
    if (detailsEl) {
        detailsEl.innerHTML = `
            <div class="row">
                <div class="col-4 fw-bold">Department:</div>
                <div class="col-8">${dept ? dept.department_name : 'Department ID: ' + deptId}</div>
                ${dept?.department_code ? `<div class="col-4 fw-bold">Code:</div><div class="col-8">${dept.department_code}</div>` : ''}
            </div>
        `;
    }
    
    window.deptToDelete = deptId;
    new bootstrap.Modal(document.getElementById('deleteDeptModal')).show();
};

// Confirm delete
const confirmDeleteDeptBtn = document.getElementById('confirmDeleteDeptBtn');
if (confirmDeleteDeptBtn) {
    confirmDeleteDeptBtn.addEventListener('click', async function() {
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const deptId = window.deptToDelete;
        if (!deptId) return;
        
        const btn = this;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`/api/departments/${deptId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok) {
                if (typeof showToast === 'function') {
                    showToast('Department deleted successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('deleteDeptModal')).hide();
                await loadDepartments();
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Delete failed', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error deleting department', 'error');
            }
        } finally {
            btn.innerHTML = 'Delete Department';
            btn.disabled = false;
        }
    });
}
</script>
@endsection