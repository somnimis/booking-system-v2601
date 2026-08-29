@extends('layouts.admin')

@section('title', 'Manage Administrators')

@section('content')
<style>
    /* Skeleton loading styles for stats */
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

    /* Table header background white */
    #adminListContainer th,
    .table thead th {
        background-color: var(--white) !important;
    }

    /* Dropdown menu styling */
    .dropdown-menu {
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
        padding: 0.5rem;
        min-width: 200px;
    }

    .dropdown-item {
        border-radius: var(--radius-sm);
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        transition: var(--transition);
    }

    .dropdown-item i {
        width: 20px;
        color: var(--navy);
    }

    .dropdown-item:hover {
        background: var(--navy-light);
        color: var(--navy);
    }

    .dropdown-item:hover i {
        color: var(--navy);
    }

    .dropdown-divider {
        margin: 0.25rem 0;
        border-color: var(--border);
    }

    /* Drag to scroll styling */
    .drag-scroll {
        cursor: grab;
        user-select: none;
        overflow-x: auto;
        scroll-behavior: smooth;
    }

    .drag-scroll:active {
        cursor: grabbing;
    }

    .drag-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .drag-scroll::-webkit-scrollbar-track {
        background: var(--surface);
        border-radius: 3px;
    }

    .drag-scroll::-webkit-scrollbar-thumb {
        background: var(--navy);
        border-radius: 3px;
    }

    .drag-scroll::-webkit-scrollbar-thumb:hover {
        background: var(--navy-mid);
    }

    /* Action buttons - fixed width */
    .action-btn {
        width: 100px;
        text-align: center;
        padding: 0.25rem 0.5rem !important;
    }

    /* Add vertical gap on mobile/small screens */
    @media (max-width: 768px) {
        .action-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .action-btn {
            width: 100%;
            margin-right: 0 !important;
        }
    }

    .title-col,
    td.title-col {
        white-space: normal !important;
    }

    #confirmDeleteBtn {
        min-width: 120px;
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    /* Admin table column widths */
    .table-section table th:nth-child(1) {
        width: 80px;
    }
    .table-section table td:nth-child(1) {
        width: 80px;
    }
    .table-section table th:nth-child(2) {
        width: 110px;
    }
    .table-section table td:nth-child(2) {
        width: 110px;
    }
    .table-section table th:nth-child(3) {
        width: 150px;
        white-space: normal;
    }
    .table-section table td:nth-child(3) {
        width: 150px;
        white-space: normal;
        word-wrap: break-word;
    }
    .table-section table th:nth-child(4) {
        width: 100px;
    }
    .table-section table td:nth-child(4) {
        width: 100px;
    }
    .table-section table th:nth-child(5) {
        width: 200px;
    }
    .table-section table td:nth-child(5) {
        width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table-section table th:nth-child(6) {
        width: 120px;
    }
    .table-section table td:nth-child(6) {
        width: 120px;
    }
    .table-section table th:nth-child(7) {
        width: 120px;
    }
    .table-section table td:nth-child(7) {
        width: 120px;
    }
    .table-section table th:nth-child(8) {
        width: 120px;
    }
    .table-section table td:nth-child(8) {
        width: 120px;
    }

    /* Loading spinner */
    .loading-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 200px;
    }

    /* Pagination - Navy theme */
    .pagination-container {
        margin-top: 1rem;
        padding: 0.75rem 1.25rem;
        border-top: 1px solid var(--border);
        background: var(--surface);
    }

    .page-link {
        cursor: pointer;
        color: var(--navy);
        background-color: var(--white);
        border: 1px solid var(--border);
    }

    .page-link:hover {
        background-color: var(--navy-light);
        color: var(--navy);
        border-color: var(--navy);
    }

    .page-item.active .page-link {
        background-color: var(--navy);
        border-color: var(--navy);
        color: var(--white);
    }

    .page-item.disabled .page-link {
        color: var(--text-muted);
        background-color: var(--surface);
        border-color: var(--border);
    }

    /* Action buttons */
    .btn-outline-primary {
        color: var(--navy);
        border-color: var(--navy);
    }

    .btn-outline-primary:hover {
        background-color: var(--navy);
        border-color: var(--navy);
        color: var(--white);
    }

    .btn-outline-danger {
        color: var(--danger);
        border-color: var(--danger);
    }

    .btn-outline-danger:hover {
        background-color: var(--danger);
        border-color: var(--danger);
        color: var(--white);
    }

    /* Header with Add button */
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

        <!-- Page Header with Add Button -->
        <div class="page-header">
            <h5><i class="bi bi-people me-2"></i>Administrators</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Admin
            </button>
        </div>

        <!-- Admin Table -->
        <div class="section-card">
            <div class="section-body">
                <div id="adminLoading" class="loading-container">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Loading administrators...</p>
                    </div>
                </div>
                <div id="adminTableWrapper" style="display: none;">
                    <div class="table-responsive drag-scroll" id="adminTableScroll">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">ID</th>
                                    <th class="small">School ID</th>
                                    <th class="small">Full Name</th>
                                    <th class="small">Title</th>
                                    <th class="small">Email</th>
                                    <th class="small">Phone</th>
                                    <th class="small">Role</th>
                                    <th class="small">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminListBody"></tbody>
                        </table>
                    </div>
                    <div class="pagination-container d-flex justify-content-between align-items-center">
                        <div id="adminPaginationInfo" class="text-muted small"></div>
                        <nav>
                            <ul class="pagination mb-0" id="adminPagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Add New Admin</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">School ID <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" name="school_id" placeholder="00-0000-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="contact_number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role_id" id="roleSelectAdd" required>
                                <option value="">Select role</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Temporary Password</label>
                            <input type="password" class="form-control" name="password" required minlength="8">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Departments</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body" style="max-height: 200px; overflow-y: auto;" id="addDeptChecklist"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body" style="max-height: 200px; overflow-y: auto;" id="addSelectedDeptPreview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="department_ids" id="addSelectedDeptIds">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Services</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body" style="max-height: 200px; overflow-y: auto;" id="addServiceChecklist"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body" style="max-height: 200px; overflow-y: auto;" id="addSelectedServicePreview">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="service_ids" id="addSelectedServiceIds">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addAdminForm" class="btn btn-primary">Add Admin</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Edit Admin</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="editModalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">Loading admin data...</p>
                </div>
                <div id="editModalContent" style="display: none;">
                    <form id="editAdminForm">
                        @csrf
                        <input type="hidden" id="edit_admin_id" name="admin_id">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">School ID</label>
                                <input type="text" class="form-control" id="edit_school_id" name="school_id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="edit_contact_number" name="contact_number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select class="form-select" id="edit_role_id" name="role_id" required>
                                    <option value="">Select role</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password"
                                    placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAdminChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold">Confirm Deletion</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger mb-3" style="font-size: 2rem;"></i>
                <p class="mb-1 fw-bold">Are you sure you want to delete this admin?</p>
                <p class="mb-3 text-muted">This action cannot be undone.</p>
                <div id="deleteAdminDetails" class="bg-light p-3 rounded"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Admin</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('js/admin/toast.js') }}"></script>
<script>
let currentAdminPage = 1;
const itemsPerPage = 10;
let adminsData = [];
let departmentsList = [];
let rolesList = [];

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    
    if (!token) {
        console.error('No authentication token found');
        if (typeof showToast === 'function') {
            showToast('Authentication error. Please login again.', 'error');
        }
        return;
    }
    
    loadStaticData();
});

async function loadStaticData() {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    
    try {
        const response = await fetch('/api/manage/static-data', {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            departmentsList = data.departments_list || [];
            rolesList = data.roles || [];
            
            populateRoleDropdowns();
            populateAddModalFormData();
            await loadAdminsTab(1);
        } else {
            console.error('Failed to load static data');
            showErrorState();
        }
    } catch (error) {
        console.error('Error loading static data:', error);
        showErrorState();
    }
}

function showErrorState() {
    const loadingEl = document.getElementById('adminLoading');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load administrators</div>';
    }
}

function populateRoleDropdowns() {
    const addRoleSelect = document.getElementById('roleSelectAdd');
    const editRoleSelect = document.getElementById('edit_role_id');
    
    if (addRoleSelect) {
        addRoleSelect.innerHTML = '<option value="">Select role</option>';
        rolesList.forEach(role => {
            addRoleSelect.innerHTML += `<option value="${role.role_id}">${role.role_title}</option>`;
        });
    }
    
    if (editRoleSelect) {
        editRoleSelect.innerHTML = '<option value="">Select role</option>';
        rolesList.forEach(role => {
            editRoleSelect.innerHTML += `<option value="${role.role_id}">${role.role_title}</option>`;
        });
    }
}

function populateAddModalFormData() {
    const deptContainer = document.getElementById('addDeptChecklist');
    if (deptContainer && departmentsList.length > 0) {
        deptContainer.innerHTML = '';
        departmentsList.forEach(dept => {
            deptContainer.innerHTML += `
                <div class="form-check">
                    <input class="form-check-input add-dept-cb" type="checkbox" value="${dept.department_id}" id="dept_${dept.department_id}">
                    <label class="form-check-label" for="dept_${dept.department_id}">${dept.department_name} (${dept.department_code})</label>
                </div>
            `;
        });
    }
    
    // Services are loaded from the static data but we don't need to populate services here
    // since they'll be loaded separately in the add modal
    
    document.querySelectorAll('.add-dept-cb').forEach(cb => {
        cb.addEventListener('change', updateAddDeptPreview);
    });
}

async function loadAdminsTab(page = 1) {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    const loadingEl = document.getElementById('adminLoading');
    const tableWrapper = document.getElementById('adminTableWrapper');
    
    if (loadingEl) loadingEl.style.display = 'flex';
    if (tableWrapper) tableWrapper.style.display = 'none';
    
    try {
        const response = await fetch(`/api/manage/admins?page=${page}&per_page=${itemsPerPage}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        
        if (result.data && result.data.data) {
            adminsData = result.data.data;
            window.adminPagination = {
                current_page: result.data.current_page,
                last_page: result.data.last_page,
                total: result.data.total,
                per_page: result.data.per_page
            };
        } else {
            adminsData = result.data || [];
            window.adminPagination = {
                current_page: page,
                last_page: Math.ceil(adminsData.length / itemsPerPage),
                total: adminsData.length,
                per_page: itemsPerPage
            };
        }
        
        renderAdminList();
        if (loadingEl) loadingEl.style.display = 'none';
        if (tableWrapper) tableWrapper.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading admins:', error);
        if (loadingEl) loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load administrators</div>';
    }
}

function formatAdminId(id) {
    return String(id).padStart(4, '0');
}

function initDragToScroll() {
    const scrollContainer = document.getElementById('adminTableScroll');
    if (!scrollContainer) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    
    scrollContainer.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        isDown = true;
        scrollContainer.style.cursor = 'grabbing';
        startX = e.pageX - scrollContainer.offsetLeft;
        scrollLeft = scrollContainer.scrollLeft;
    });
    
    scrollContainer.addEventListener('mouseleave', () => {
        isDown = false;
        scrollContainer.style.cursor = 'grab';
    });
    
    scrollContainer.addEventListener('mouseup', () => {
        isDown = false;
        scrollContainer.style.cursor = 'grab';
    });
    
    scrollContainer.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - scrollContainer.offsetLeft;
        const walk = (x - startX) * 1.5;
        scrollContainer.scrollLeft = scrollLeft - walk;
    });
    
    scrollContainer.addEventListener('touchstart', (e) => {
        isDown = true;
        startX = e.touches[0].pageX - scrollContainer.offsetLeft;
        scrollLeft = scrollContainer.scrollLeft;
    });
    
    scrollContainer.addEventListener('touchend', () => {
        isDown = false;
    });
    
    scrollContainer.addEventListener('touchmove', (e) => {
        if (!isDown) return;
        const x = e.touches[0].pageX - scrollContainer.offsetLeft;
        const walk = (x - startX) * 1.5;
        scrollContainer.scrollLeft = scrollLeft - walk;
    });
    
    scrollContainer.style.cursor = 'grab';
}

function renderAdminList() {
    if (!adminsData.length && (!window.adminPagination || window.adminPagination.total === 0)) {
        const tbody = document.getElementById('adminListBody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center">No administrators found</td></tr>';
        return;
    }
    
    const tbody = document.getElementById('adminListBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    adminsData.forEach(admin => {
        const row = `
            <tr>
                <td>${formatAdminId(admin.admin_id)}</td>
                <td>${admin.school_id || 'N/A'}</td>
                <td>${admin.full_name}</td>
                <td>${admin.title || 'N/A'}</td>
                <td title="${admin.email}">${admin.email}</td>
                <td>${admin.contact_number || 'N/A'}</td>
                <td>${admin.role_title || 'N/A'}</td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <button class="btn btn-sm btn-primary action-btn" onclick="editAdmin(${admin.admin_id})" title="Edit" style="background-color: var(--navy); border-color: var(--navy);">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger action-btn" onclick="deleteAdmin(${admin.admin_id})" title="Delete" style="background-color: var(--danger); border-color: var(--danger);">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
    });
    
    // Render pagination
    const currentPage = window.adminPagination?.current_page || currentAdminPage;
    const totalPages = window.adminPagination?.last_page || 1;
    const totalItems = window.adminPagination?.total || adminsData.length;
    const start = ((currentPage - 1) * itemsPerPage) + 1;
    const end = Math.min(currentPage * itemsPerPage, totalItems);
    
    const paginationEl = document.getElementById('adminPagination');
    if (paginationEl) {
        paginationEl.innerHTML = '';
        
        paginationEl.innerHTML += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'tabindex="-1"' : ''}>&laquo; Prev</a>
            </li>
        `;
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4 && startPage > 1) {
            startPage = Math.max(1, endPage - 4);
        }
        
        if (startPage > 1) {
            paginationEl.innerHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationEl.innerHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" onclick="goToPage(${i})">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            paginationEl.innerHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        
        paginationEl.innerHTML += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'tabindex="-1"' : ''}>Next &raquo;</a>
            </li>
        `;
    }
    
    const paginationInfo = document.getElementById('adminPaginationInfo');
    if (paginationInfo && totalItems > 0) {
        paginationInfo.textContent = `Showing ${start} to ${end} of ${totalItems} admins`;
    }
    
    setTimeout(() => {
        initDragToScroll();
    }, 100);
}

window.goToPage = function(page) {
    if (page < 1) return;
    if (window.adminPagination && page > window.adminPagination.last_page) return;
    
    if (window.adminPagination && window.adminPagination.last_page) {
        currentAdminPage = page;
        loadAdminsTab(page);
    } else if (page >= 1 && page <= Math.ceil(adminsData.length / itemsPerPage)) {
        currentAdminPage = page;
        renderAdminList();
    }
};

window.editAdmin = async function(adminId) {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    const modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
    const loadingDiv = document.getElementById('editModalLoading');
    const contentDiv = document.getElementById('editModalContent');
    
    if (loadingDiv) loadingDiv.style.display = 'block';
    if (contentDiv) contentDiv.style.display = 'none';
    modal.show();
    
    try {
        const response = await fetch(`/api/manage/admins/${adminId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        const admin = result.data;
        
        document.getElementById('edit_admin_id').value = admin.admin_id;
        document.getElementById('edit_first_name').value = admin.first_name || '';
        document.getElementById('edit_middle_name').value = admin.middle_name || '';
        document.getElementById('edit_last_name').value = admin.last_name || '';
        document.getElementById('edit_title').value = admin.title || '';
        document.getElementById('edit_email').value = admin.email || '';
        document.getElementById('edit_contact_number').value = admin.contact_number || '';
        document.getElementById('edit_school_id').value = admin.school_id || '';
        
        const roleSelect = document.getElementById('edit_role_id');
        if (roleSelect && roleSelect.value !== admin.role_id) {
            roleSelect.value = admin.role_id;
        }
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        if (contentDiv) contentDiv.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading admin:', error);
        if (loadingDiv) loadingDiv.innerHTML = '<div class="alert alert-danger">Failed to load admin details</div>';
    }
};

window.deleteAdmin = function(adminId) {
    const admin = adminsData.find(a => a.admin_id === adminId);
    const detailsEl = document.getElementById('deleteAdminDetails');
    if (detailsEl) {
        detailsEl.innerHTML = `
            <div class="row">
                <div class="col-4 fw-bold">Name:</div>
                <div class="col-8">${admin ? admin.full_name : 'Admin ID: ' + adminId}</div>
                <div class="col-4 fw-bold">Email:</div>
                <div class="col-8">${admin ? admin.email : 'N/A'}</div>
            </div>
        `;
    }
    
    window.adminToDelete = adminId;
    new bootstrap.Modal(document.getElementById('deleteConfirmationModal')).show();
};

// Confirm delete
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', async function() {
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const adminId = window.adminToDelete;
        if (!adminId) return;
        
        const btn = this;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`/api/admins/${adminId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (response.ok) {
                if (typeof showToast === 'function') {
                    showToast('Admin deleted successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal')).hide();
                
                const currentPage = window.adminPagination?.current_page || 1;
                await loadAdminsTab(currentPage);
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Delete failed', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error deleting admin', 'error');
            }
        } finally {
            btn.innerHTML = 'Delete Admin';
            btn.disabled = false;
        }
    });
}

// Save edited admin
const saveAdminBtn = document.getElementById('saveAdminChanges');
if (saveAdminBtn) {
    saveAdminBtn.addEventListener('click', async function() {
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const adminId = document.getElementById('edit_admin_id')?.value;
        const formData = {
            admin_id: adminId,
            first_name: document.getElementById('edit_first_name')?.value,
            middle_name: document.getElementById('edit_middle_name')?.value,
            last_name: document.getElementById('edit_last_name')?.value,
            title: document.getElementById('edit_title')?.value,
            email: document.getElementById('edit_email')?.value,
            contact_number: document.getElementById('edit_contact_number')?.value || null,
            role_id: parseInt(document.getElementById('edit_role_id')?.value),
            school_id: document.getElementById('edit_school_id')?.value || null,
            password: document.getElementById('edit_password')?.value || undefined
        };
        
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        btn.disabled = true;
        
        try {
            const response = await fetch(`/api/admins/${adminId}`, {
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
                    showToast('Admin updated successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('editAdminModal')).hide();
                
                const currentPage = window.adminPagination?.current_page || 1;
                await loadAdminsTab(currentPage);
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Update failed', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error updating admin', 'error');
            }
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// Add admin form submission
const addAdminForm = document.getElementById('addAdminForm');
if (addAdminForm) {
    addAdminForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
        const formData = new FormData(this);
        const data = {
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            title: formData.get('title'),
            email: formData.get('email'),
            contact_number: formData.get('contact_number'),
            role_id: parseInt(formData.get('role_id')),
            school_id: formData.get('school_id') || null,
            password: formData.get('password'),
            department_ids: JSON.parse(document.getElementById('addSelectedDeptIds')?.value || '[]'),
            service_ids: JSON.parse(document.getElementById('addSelectedServiceIds')?.value || '[]'),
            photo_url: 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033911/ksdmh4mmpxdtjogdgjmm.png',
            photo_public_id: 'ksdmh4mmpxdtjogdgjmm'
        };
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : 'Add Admin';
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            submitBtn.disabled = true;
        }
        
        try {
            const response = await fetch('/api/admins', {
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
                    showToast('Admin added successfully', 'success');
                }
                bootstrap.Modal.getInstance(document.getElementById('addAdminModal')).hide();
                this.reset();
                
                document.getElementById('addSelectedDeptIds').value = '[]';
                document.getElementById('addSelectedServiceIds').value = '[]';
                document.getElementById('addSelectedDeptPreview').innerHTML = '<div class="text-muted">No departments selected</div>';
                document.getElementById('addSelectedServicePreview').innerHTML = '<div class="text-muted">No services selected</div>';
                document.querySelectorAll('.add-dept-cb').forEach(cb => cb.checked = false);
                document.querySelectorAll('.add-service-cb').forEach(cb => cb.checked = false);
                
                await loadAdminsTab(1);
            } else {
                if (typeof showToast === 'function') {
                    showToast(result.message || 'Failed to add admin', 'error');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('Error adding admin', 'error');
            }
        } finally {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    });
}

function updateAddDeptPreview() {
    const selected = Array.from(document.querySelectorAll('.add-dept-cb:checked')).map(cb => cb.value);
    const hiddenInput = document.getElementById('addSelectedDeptIds');
    const preview = document.getElementById('addSelectedDeptPreview');
    
    if (hiddenInput) hiddenInput.value = JSON.stringify(selected);
    if (preview) {
        if (selected.length === 0) {
            preview.innerHTML = '<div class="text-muted">No departments selected</div>';
        } else {
            preview.innerHTML = selected.map(id => `<div class="p-2 bg-light rounded mb-1">Department ID: ${id}</div>`).join('');
        }
    }
}

function updateAddServicePreview() {
    const selected = Array.from(document.querySelectorAll('.add-service-cb:checked')).map(cb => cb.value);
    const hiddenInput = document.getElementById('addSelectedServiceIds');
    const preview = document.getElementById('addSelectedServicePreview');
    
    if (hiddenInput) hiddenInput.value = JSON.stringify(selected);
    if (preview) {
        if (selected.length === 0) {
            preview.innerHTML = '<div class="text-muted">No services selected</div>';
        } else {
            preview.innerHTML = selected.map(id => `<div class="p-2 bg-light rounded mb-1">Service ID: ${id}</div>`).join('');
        }
    }
}
</script>
@endsection
