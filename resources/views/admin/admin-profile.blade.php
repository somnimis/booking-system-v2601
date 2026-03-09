@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
    <style>
        /* Department list styles */
        #department-list .list-group-item {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        #department-list .list-group-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }

        #department-list .list-group-item.active {
            background-color: #e7f1ff;
            color: #0d6efd;
            border-left-color: #0d6efd;
            border-top-color: #dee2e6;
            border-bottom-color: #dee2e6;
        }

        #department-list .list-group-item .form-check-input {
            cursor: pointer;
            margin-top: 0;
        }

        #department-list .list-group-item .department-code {
            font-size: 0.75rem;
            color: #6c757d;
        }

        #department-list .list-group-item.active .department-code {
            color: #0d6efd;
        }

        /* Selected department badges */
        .department-badge {
            display: inline-flex;
            align-items: center;
            background-color: #e7f1ff;
            color: #0d6efd;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
        }

        .department-badge.primary {
            background-color: #0d6efd;
            color: white;
        }

        .department-badge .remove-dept {
            cursor: pointer;
            margin-left: 0.25rem;
            font-size: 0.7rem;
        }

        .department-badge .remove-dept:hover {
            color: #dc3545;
        }

        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;
            overflow: hidden;
        }


        .card-img-container {
            position: relative;
            height: 160px;
            overflow: hidden;
        }

        .card-img-container img {
            transition: transform 0.4s ease;
        }

        /* Status badge styling */
        .status-badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: 20px;
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Truncate text with ellipsis */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Department text truncation */
        .department-text {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Facility selection styles */
        #facility-list .list-group-item {
            cursor: pointer;
            transition: all 0.2s;
        }

        #facility-list .list-group-item:hover {
            background-color: #f8f9fa;
        }

        #facility-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        #facility-list .list-group-item .form-check-input {
            cursor: pointer;
        }

        .selected-facility-item {
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 4px solid #0d6efd;
        }

        .selected-facility-item:last-child {
            margin-bottom: 0;
        }

        .selected-facility-item .remove-facility {
            cursor: pointer;
            color: #dc3545;
        }

        .selected-facility-item .remove-facility:hover {
            color: #b02a37;
        }

        .card {
            border: 0 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border-radius: 0.75rem;
            /* optional, for smoother corners */
        }

        #department-buttons-container button {
            display: inline-flex !important;
            width: auto !important;
        }

        /* Ensure proper column sizing */
        #password-full-width-container .form-control,
        #password-half-width-container .form-control {
            width: 100%;
        }

        main#main .profile-wrapper .profile-hero {
            /* Fluid side gap: min 20px, preferred 5vw, max 120px */
            --side-gap: clamp(20px, 9vw, 150px);

            /* Fluid height: min 180px, preferred 20vh, max 300px */
            height: clamp(180px, 20vh, 300px) !important;

            width: calc(100vw - (2 * var(--side-gap))) !important;
            position: relative !important;
            left: 50% !important;
            right: 50% !important;
            margin-left: calc(-50vw + var(--side-gap)) !important;
            margin-right: calc(-50vw + var(--side-gap)) !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 0 !important;
            max-width: calc(100vw - (2 * var(--side-gap))) !important;
        }

        /* Remove any top spacing from parent elements */
        main#main .profile-wrapper {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* If there's a header/navbar, reduce the body padding-top */
        body {
            padding-top: 0 !important;
        }

        /* Remove any spacing from the main element itself */
        main#main {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* If using Bootstrap, remove any container spacing */
        .container.position-relative {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* Ensure wallpaper starts at the very top */
        #wallpaper-container {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .one-line-truncate {
            white-space: nowrap;
            /* keeps it on one line */
            overflow: hidden;
            /* hides overflow */
            text-overflow: ellipsis;
            /* shows "..." */
        }

        /* Service modal styles for multiple selection */
        #service-list .list-group-item {
            cursor: pointer;
            transition: all 0.2s;
        }

        #service-list .list-group-item:hover {
            background-color: #f8f9fa;
        }

        #service-list .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        #service-list .list-group-item.active .form-check-input {
            background-color: white;
            border-color: white;
        }

        #service-list .list-group-item .form-check-input {
            cursor: pointer;
        }

        .selected-service-item {
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 4px solid #0d6efd;
        }

        .selected-service-item:last-child {
            margin-bottom: 0;
        }

        .selected-service-item .remove-service {
            cursor: pointer;
            color: #dc3545;
            padding: 0;
            background: none;
            border: none;
        }

        .selected-service-item .remove-service:hover {
            color: #b02a37;
        }
    </style>

    <main id="main">
        <div class="profile-wrapper position-relative">
            <!-- Hero/Wallpaper Section -->
            <div class="profile-hero position-relative mb-5" style="height: 200px; background-color: #f8f9fa;">
                <div id="wallpaper-container" class="w-100 h-100"
                    style="background: url('https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033948/verzp7lqedwsfn3hz8xf.jpg')
                                                                                                                        center center / cover no-repeat;">
                    <!-- Wallpaper will be loaded here -->
                </div>
                <button class="btn btn-light position-absolute bottom-0 end-0 m-3">
                    <i class="bi bi-image me-2"></i>Change Cover
                </button>
            </div>
            <input type="file" id="wallpaper-upload" class="d-none" accept="image/*">

            <div class="container position-relative">
                <!-- Profile Avatar -->
                <div class="position-absolute" style="top: -70px; left: 50px; z-index: 10;">
                    <div class="position-relative">
                        <div class="avatar-container rounded-circle border border-4 border-white"
                            style="width: 150px; height: 150px; overflow: hidden;">
                            <img id="profile-photo"
                                src="{{ url('https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033911/ksdmh4mmpxdtjogdgjmm.png') }}"
                                class="w-100 h-100 object-fit-cover">
                        </div>
                        <button class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0 shadow-sm"
                            style="width: 32px; height: 32px;" onclick="document.getElementById('photo-upload').click()">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <input type="file" id="photo-upload" class="d-none" accept="image/*">
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="row mt-5 pt-5 g-3">
                    <!-- Main Info Card -->
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body p-3">
                                <div id="main-info-loading" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div id="main-info-content" style="display: none; margin-top: 15px; margin-left: 10px;">
                                    <h2 class="card-title mb-4" id="admin-full-name"></h2>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <p><strong>School ID:</strong> <span id="admin-school-id"></span></p>
                                            <p><strong>Email:</strong> <span id="admin-email"></span></p>
                                            <p><strong>Contact:</strong> <span id="admin-contact"></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Member Since:</strong> <span id="admin-created"></span></p>
                                            <p><strong>Last Updated:</strong> <span id="admin-updated"></span></p>
                                        </div>
                                    </div>

                                    <!-- ✨ Edit Profile button -->
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-primary" id="editProfileBtn">
                                            <i class="bi bi-pencil me-1"></i> Edit Profile
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role & Departments Card -->
                    <div class="col-md-4 d-flex flex-column">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Role Details</h5>
                                <div id="role-content"></div>
                            </div>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Managing Departments</h5>
                                <div id="departments-content">
                                    <div class="text-muted">No departments assigned</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProfileForm">
                            <div class="row g-3">
                                <!-- Names on one row -->
                                <div class="col-md-4">
                                    <label for="edit-first-name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="edit-first-name" name="first_name"
                                        placeholder="First Name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit-middle-name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="edit-middle-name" name="middle_name"
                                        placeholder="Middle Name">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit-last-name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="edit-last-name" name="last_name"
                                        placeholder="Last Name" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit-school-id" class="form-label d-flex align-items-center">
                                        School ID
                                        <small class="text-muted ms-2">(Optional - Format: 00-0000-00)</small>
                                    </label>
                                    <input type="text" class="form-control" id="edit-school-id" name="school_id"
                                        placeholder="00-0000-00" pattern="\d{2}-\d{4}-\d{2}" maxlength="10" minlength="10">
                                </div>

                                <div class="col-md-6">
                                    <label for="edit-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit-email" name="email"
                                        placeholder="samplemail@gmail.com" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit-contact" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="edit-contact" name="contact_number"
                                        placeholder="e.g. 09123456789" pattern="\d{11,}" minlength="11" required>
                                </div>

                                <!-- Password Field - Now positioned next to Contact Number -->
                                <div class="col-md-6">
                                    <label for="edit-password" class="form-label d-flex align-items-center">
                                        New Password
                                        <small class="text-muted ms-2">(Leave blank to keep current)</small>
                                    </label>
                                    <input type="password" class="form-control" id="edit-password" name="password"
                                        placeholder="New Password">
                                </div>

                                <!-- Departments Section - For roles 1, 2, and 3 -->
                                <div class="col-12" id="departments-section-container" style="display: none;">
                                    <label class="form-label fw-bold mb-2">Departments</label>
                                    <div class="mb-2">
                                        <small class="text-muted">Select departments (first selected becomes
                                            primary)</small>
                                    </div>

                                    <!-- Search/filter for departments -->
                                    <div class="mb-2">
                                        <input type="text" id="department-search" class="form-control form-control-sm"
                                            placeholder="Search departments...">
                                    </div>

                                    <!-- Compact vertical department list -->
                                    <div id="department-list-container"
                                        style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px;">
                                        <div id="department-list" class="list-group list-group-flush">
                                            <!-- Departments will be loaded here dynamically -->
                                            <div class="text-muted text-center py-3">Loading departments...</div>
                                        </div>
                                    </div>

                                    <!-- Selected departments summary -->
                                    <div class="mt-3">
                                        <label class="form-label small text-muted">Selected Departments:</label>
                                        <div id="selected-departments-summary" class="d-flex flex-wrap gap-1">
                                            <span class="text-muted small">None selected</span>
                                        </div>
                                    </div>

                                    <input type="hidden" id="selected-departments" name="department_ids">
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveProfileChanges">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

@endsection

@section('scripts')
    <!-- Include toast.js -->
    <script src="{{ asset('js/admin/toast.js') }}"></script>

    <script>
        // Global Variables
        let currentAdminData = null;
        let departmentsData = null;

        // Core Configuration and Helper Functions
        document.getElementById('edit-school-id').addEventListener('input', function (e) {
            // keep only digits
            let digits = e.target.value.replace(/\D/g, '');
            // add dashes after 2 and 6 digits
            if (digits.length > 2 && digits.length <= 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2);
            } else if (digits.length > 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6, 8);
            }
            e.target.value = digits;
        });

        // Cloudinary configuration
        const cloudinaryConfig = {
            cloudName: 'dn98ntlkd',
            apiKey: '545682193957699',
            uploadPresetPhoto: 'admin-photos',
            uploadPresetWallpaper: 'admin-wallpapers'
        };

        // Utility Functions
        function canManageDepartments(adminData) {
            // Roles that can manage departments: 1 = Head Admin, 2 = Vice President, 3 = Approving Officer
            return adminData && adminData.role && [1, 2, 3].includes(adminData.role.role_id);
        }

        function getCurrentAdminId() {
            return currentAdminData ? currentAdminData.admin_id : null;
        }

        function isCurrentAdminHeadAdmin() {
            return currentAdminData && currentAdminData.role && currentAdminData.role.role_id === 1;
        }

        // Function to delete old image from Cloudinary via backend
        async function deleteOldCloudinaryImage(publicId, type) {
            if (!publicId) return true;

            // Skip deletion for default images
            const defaultIds = ['ksdmh4mmpxdtjogdgjmm', 'verzp7lqedwsfn3hz8xf'];
            if (defaultIds.includes(publicId)) {
                console.log('Skipping deletion of default image:', publicId);
                return true;
            }

            try {
                const token = localStorage.getItem('adminToken');
                const response = await fetch('/api/admin/delete-cloudinary-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        public_id: publicId,
                        type: type
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.warn('Failed to delete old image:', errorData);
                    // Don't throw error - continue with upload even if deletion fails
                    return false;
                }

                const result = await response.json();
                console.log('Old image deleted successfully:', result);
                return result.deleted;
            } catch (error) {
                console.error('Error deleting old image from Cloudinary:', error);
                // Continue with upload even if deletion fails
                return false;
            }
        }

        async function loadRolesAndDepartments() {
            const token = localStorage.getItem('adminToken');

            try {
                // Load departments only (roles are removed)
                const deptResponse = await fetch('/api/departments', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!deptResponse.ok) throw new Error('Failed to fetch departments');
                const deptData = await deptResponse.json();

                // Check if departments data is an array
                if (Array.isArray(deptData)) {
                    departmentsData = deptData;
                } else if (deptData.data && Array.isArray(deptData.data)) {
                    departmentsData = deptData.data;
                } else if (deptData.departments && Array.isArray(deptData.departments)) {
                    departmentsData = deptData.departments;
                } else {
                    console.error('Unexpected departments response format:', deptData);
                    departmentsData = [];
                }

            } catch (error) {
                console.error('Error loading modal options:', error);
                throw error;
            }
        }
        // Function to update hidden input with selected departments
        function updateSelectedDepartments() {
            const selectedItems = document.querySelectorAll('#department-list .list-group-item.active');
            const selectedDeptIds = Array.from(selectedItems).map(item => item.dataset.deptId);
            document.getElementById('selected-departments').value = JSON.stringify(selectedDeptIds);

            // Update summary badges
            updateSelectedDepartmentsSummary();
        }

        // Function to update selected departments summary
        function updateSelectedDepartmentsSummary() {
            const summary = document.getElementById('selected-departments-summary');
            const selectedItems = document.querySelectorAll('#department-list .list-group-item.active');

            if (selectedItems.length === 0) {
                summary.innerHTML = '<span class="text-muted small">None selected</span>';
                return;
            }

            let html = '';
            selectedItems.forEach((item, index) => {
                const deptName = item.querySelector('.dept-name').textContent;
                const isPrimary = index === 0; // First selected is primary
                html += `<span class="department-badge ${isPrimary ? 'primary' : ''}" data-dept-id="${item.dataset.deptId}">
                        ${deptName} ${isPrimary ? '(Primary)' : ''}
                        <span class="remove-dept" onclick="removeDepartment('${item.dataset.deptId}')">×</span>
                    </span>`;
            });
            summary.innerHTML = html;
        }


        // Function to remove a department from selection
        window.removeDepartment = function (deptId) {
            const deptItem = document.querySelector(`#department-list .list-group-item[data-dept-id="${deptId}"]`);
            if (deptItem) {
                deptItem.classList.remove('active');
                deptItem.querySelector('.form-check-input').checked = false;
                updateSelectedDepartments();
            }
        };


        // Function to create department list
        function createDepartmentButtons() {
            const deptList = document.getElementById('department-list');
            if (!deptList) {
                console.error('Department list not found - retrying in 50ms');
                setTimeout(createDepartmentButtons, 50);
                return;
            }

            deptList.innerHTML = '';

            if (!departmentsData || departmentsData.length === 0) {
                deptList.innerHTML = '<div class="text-muted text-center py-3">No departments available</div>';
                return;
            }

            // Sort departments by name
            const sortedDepts = [...departmentsData].sort((a, b) =>
                a.department_name.localeCompare(b.department_name)
            );

            sortedDepts.forEach(dept => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action d-flex align-items-center';
                item.dataset.deptId = dept.department_id;

                item.innerHTML = `
                        <div class="form-check me-2">
                            <input class="form-check-input" type="checkbox" value="${dept.department_id}" id="dept-${dept.department_id}">
                        </div>
                        <div class="flex-grow-1">
                            <span class="dept-name">${dept.department_name}</span>
                            <small class="department-code ms-2">${dept.department_code}</small>
                        </div>
                    `;

                // Add click event to toggle selection
                item.addEventListener('click', function (e) {
                    // Prevent double toggling if clicking directly on checkbox
                    if (e.target.type !== 'checkbox') {
                        const checkbox = this.querySelector('.form-check-input');
                        checkbox.checked = !checkbox.checked;
                        this.classList.toggle('active', checkbox.checked);
                        updateSelectedDepartments();
                    }
                });

                // Add separate event for checkbox to handle its own changes
                const checkbox = item.querySelector('.form-check-input');
                checkbox.addEventListener('change', function (e) {
                    e.stopPropagation();
                    item.classList.toggle('active', this.checked);
                    updateSelectedDepartments();
                });

                deptList.appendChild(item);
            });

            // Pre-select current departments
            if (currentAdminData && currentAdminData.departments && currentAdminData.departments.length > 0) {
                currentAdminData.departments.forEach(dept => {
                    const item = deptList.querySelector(`[data-dept-id="${dept.department_id}"]`);
                    if (item) {
                        item.classList.add('active');
                        item.querySelector('.form-check-input').checked = true;
                    }
                });
                updateSelectedDepartments();
            }

            // Add search functionality
            const searchInput = document.getElementById('department-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.toLowerCase();
                    const items = deptList.querySelectorAll('.list-group-item');

                    items.forEach(item => {
                        const deptName = item.querySelector('.dept-name').textContent.toLowerCase();
                        const deptCode = item.querySelector('.department-code').textContent.toLowerCase();
                        const matches = deptName.includes(searchTerm) || deptCode.includes(searchTerm);
                        item.style.display = matches ? 'flex' : 'none';
                    });
                });
            }

            // Add role change listener for auto-select
            const roleSelect = document.getElementById('edit-role');
            if (roleSelect) {
                roleSelect.replaceWith(roleSelect.cloneNode(true));
                const newRoleSelect = document.getElementById('edit-role');

                newRoleSelect.addEventListener('change', function () {
                    const selectedRoleId = parseInt(this.value);
                    // Roles that should auto-select all departments (Head Admin and VP)
                    const autoSelectRoleIds = [1, 2];

                    if (autoSelectRoleIds.includes(selectedRoleId)) {
                        // Select all departments
                        const allItems = deptList.querySelectorAll('.list-group-item');
                        allItems.forEach(item => {
                            if (item.style.display !== 'none') {
                                item.classList.add('active');
                                item.querySelector('.form-check-input').checked = true;
                            }
                        });
                        updateSelectedDepartments();
                    }
                });
            }
        }


        // ==================== MAIN DOM CONTENT LOADED ====================

        document.addEventListener('DOMContentLoaded', function () {
            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = '/admin/admin-login';
                return;
            }

            // Load profile data
            fetch('/api/admin/profile', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                credentials: 'include'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch profile data');
                    }
                    return response.json();
                })
                .then(data => {
                    currentAdminData = data;
                    console.log('Profile data:', data);

                    // Load profile photo
                    if (data.photo_url) {
                        document.getElementById('profile-photo').src = data.photo_url;
                    }

                    // Load wallpaper if exists
                    if (data.wallpaper_url) {
                        document.getElementById('wallpaper-container').style.backgroundImage = `url(${data.wallpaper_url})`;
                        document.getElementById('wallpaper-container').style.backgroundSize = 'cover';
                        document.getElementById('wallpaper-container').style.backgroundPosition = 'center';
                    }

                    // Update main info
                    document.getElementById('admin-full-name').textContent = `${data.first_name} ${data.last_name}`;
                    document.getElementById('admin-school-id').textContent = data.school_id || 'Not set';
                    document.getElementById('admin-email').textContent = data.email;
                    document.getElementById('admin-contact').textContent = data.contact_number || 'Not set';
                    document.getElementById('admin-created').textContent = new Date(data.created_at).toLocaleDateString();
                    document.getElementById('admin-updated').textContent = new Date(data.updated_at).toLocaleDateString();

                    // Update role details
                    if (data.role) {
                        document.getElementById('role-content').innerHTML = `
                                                                    <div class="d-flex align-items-center mb-3">
                                                                        <span class="badge bg-primary me-2">${data.role.role_title}</span>
                                                                    </div>
                                                                    <p class="text-muted small">${data.role.description}</p>
                                                                `;
                    }

                    // Update departments
                    if (data.departments && data.departments.length > 0) {
                        const deptList = data.departments.map(dept => {
                            const isPrimary = dept.pivot.is_primary ? ' (Primary)' : '';
                            return `<div class="badge bg-light text-dark me-2 mb-2">${dept.department_name}${isPrimary}</div>`;
                        }).join('');
                        document.getElementById('departments-content').innerHTML = deptList;
                    } else {
                        document.getElementById('departments-content').innerHTML =
                            '<div class="text-muted">No departments assigned</div>';
                    }

                    // Show content
                    document.getElementById('main-info-loading').style.display = 'none';
                    document.getElementById('main-info-content').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching profile:', error);
                    document.getElementById('main-info-loading').innerHTML =
                        '<div class="text-danger">Error loading profile data</div>';
                });

            // Edit Profile Modal Functionality
            document.getElementById('editProfileBtn').addEventListener('click', function () {
                const modalElement = document.getElementById('editProfileModal');
                const modal = new bootstrap.Modal(modalElement);

                // Load roles and departments into the modal
                loadRolesAndDepartments().then(() => {
                    // Pre-fill form with current admin data
                    if (currentAdminData) {
                        document.getElementById('edit-first-name').value = currentAdminData.first_name || '';
                        document.getElementById('edit-last-name').value = currentAdminData.last_name || '';
                        document.getElementById('edit-middle-name').value = currentAdminData.middle_name || '';
                        document.getElementById('edit-school-id').value = currentAdminData.school_id || '';
                        document.getElementById('edit-email').value = currentAdminData.email || '';
                        document.getElementById('edit-contact').value = currentAdminData.contact_number || '';

                        // Set the current admin's role after a brief delay to ensure options are populated
                        setTimeout(() => {
                            const roleSelect = document.getElementById('edit-role');
                            if (roleSelect && currentAdminData.role_id) {
                                roleSelect.value = currentAdminData.role_id;
                                console.log('Set role to:', currentAdminData.role_id);
                            }
                        }, 100);
                    }
                    // Use Bootstrap modal event to create buttons when modal is shown
                    modalElement.addEventListener('shown.bs.modal', function onModalShow() {
                        // Check if user can manage departments (roles 1, 2, or 3)
                        const canManage = canManageDepartments(currentAdminData);
                        console.log('Can manage departments:', canManage, 'Current Admin Data:', currentAdminData);

                        // Departments section - for roles 1, 2, and 3
                        const deptSection = document.getElementById('departments-section-container');
                        if (deptSection) {
                            deptSection.style.display = canManage ? 'block' : 'none';
                        }

                        // Only create department buttons if canManage
                        if (canManage) {
                            createDepartmentButtons();
                        }

                        modalElement.removeEventListener('shown.bs.modal', onModalShow);
                    });

                    modal.show();
                }).catch(error => {
                    console.error('Error loading modal data:', error);
                    showToast('Failed to load edit form data', 'error', 3000);
                });
            });

            document.getElementById('saveProfileChanges').addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken');
                const canManage = canManageDepartments(currentAdminData);

                // Validate School ID format ONLY if provided
                const schoolId = document.getElementById('edit-school-id').value.trim();
                if (schoolId) {
                    const schoolIdPattern = /^\d{2}-\d{4}-\d{2}$/;
                    if (!schoolIdPattern.test(schoolId)) {
                        showToast('School ID must follow the format ##-####-##', 'error', 4000);
                        return;
                    }
                }

                // Get selected department values (for users who can manage departments)
                let selectedDepartments = [];
                if (canManage) {
                    selectedDepartments = JSON.parse(document.getElementById('selected-departments').value || '[]');
                }

                // Validate department selection (only for users who can manage departments)
                if (canManage) {
                    const noDeptRequiredRoleIds = [1, 2]; // Head Admin and VP don't need departments
                    // Use the role data from currentAdminData
                    if (selectedDepartments.length === 0 && !noDeptRequiredRoleIds.includes(currentAdminData.role.role_id)) {
                        showToast('Please select at least one department', 'error', 3000);
                        return;
                    }
                }

                // Prepare data for API - use role_id from currentAdminData
                const jsonData = {
                    first_name: document.getElementById('edit-first-name').value,
                    last_name: document.getElementById('edit-last-name').value,
                    middle_name: document.getElementById('edit-middle-name').value,
                    school_id: schoolId || null,
                    email: document.getElementById('edit-email').value,
                    contact_number: document.getElementById('edit-contact').value,
                    role_id: currentAdminData.role.role_id // Use role_id from the loaded profile data
                };

                // Only include departments if user can manage them
                if (canManage) {
                    jsonData.department_ids = selectedDepartments;
                }

                // Get password from the single password field
                const password = document.getElementById('edit-password').value;
                if (password) jsonData.password = password;

                try {
                    const response = await fetch(
                        `/api/admin/update/${currentAdminData.admin_id}`,
                        {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(jsonData)
                        }
                    );

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Failed to update profile');
                    }

                    showToast('Profile updated successfully!', 'success', 3000);
                    bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                    setTimeout(() => location.reload(), 1000);

                } catch (error) {
                    console.error('Error updating profile:', error);
                    showToast('Failed to update profile: ' + error.message, 'error', 4000);
                }
            });
            // Handle photo upload with Cloudinary
            document.getElementById('photo-upload').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                let oldPublicId = null;

                try {
                    // Show loading state
                    const originalSrc = document.getElementById('profile-photo').src;
                    document.getElementById('profile-photo').src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIwLjM1ZW0iPlVwbG9hZGluZy4uLjwvdGV4dD48L3N2Zz4=';

                    // Store old public_id for deletion after successful upload
                    if (currentAdminData && currentAdminData.photo_public_id) {
                        oldPublicId = currentAdminData.photo_public_id;
                    }

                    // Upload new photo to Cloudinary
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('upload_preset', cloudinaryConfig.uploadPresetPhoto);

                    console.log('Uploading photo to Cloudinary...');

                    const uploadResponse = await fetch(`https://api.cloudinary.com/v1_1/${cloudinaryConfig.cloudName}/upload`, {
                        method: 'POST',
                        body: formData
                    });

                    if (!uploadResponse.ok) {
                        const errorText = await uploadResponse.text();
                        console.error('Cloudinary upload failed:', errorText);
                        throw new Error('Failed to upload to Cloudinary');
                    }

                    const cloudinaryResult = await uploadResponse.json();
                    console.log('Cloudinary upload result:', cloudinaryResult);

                    if (!cloudinaryResult.secure_url || !cloudinaryResult.public_id) {
                        throw new Error('Invalid response from Cloudinary');
                    }

                    // Update database records
                    console.log('Updating database records...');
                    const updateResponse = await fetch('/api/admin/update-photo-records', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            photo_url: cloudinaryResult.secure_url,
                            photo_public_id: cloudinaryResult.public_id,
                            type: 'photo'
                        })
                    });

                    if (!updateResponse.ok) {
                        const errorData = await updateResponse.json();
                        console.error('Database update failed:', errorData);
                        throw new Error(errorData.message || 'Failed to update database');
                    }

                    const updateResult = await updateResponse.json();
                    console.log('Database update result:', updateResult);

                    // Update UI and current data
                    document.getElementById('profile-photo').src = cloudinaryResult.secure_url;
                    currentAdminData.photo_url = cloudinaryResult.secure_url;
                    currentAdminData.photo_public_id = cloudinaryResult.public_id;

                    // Delete old image from Cloudinary after successful update
                    if (oldPublicId) {
                        console.log('Deleting old photo from Cloudinary:', oldPublicId);
                        await deleteOldCloudinaryImage(oldPublicId, 'photo');
                    }

                    showToast('Profile photo updated successfully!', 'success', 2000);

                    // Refresh the entire page after 2 seconds to show the toast
                    setTimeout(() => {
                        location.reload();
                    }, 2000);

                } catch (error) {
                    console.error('Error uploading photo:', error);
                    document.getElementById('profile-photo').src = originalSrc;
                    showToast('Failed to upload photo: ' + error.message, 'error', 4000);
                } finally {
                    // Clear the file input
                    e.target.value = '';
                }
            });

            // Handle wallpaper upload with Cloudinary
            document.querySelector('.profile-hero button').addEventListener('click', () => {
                document.getElementById('wallpaper-upload').click();
            });

            document.getElementById('wallpaper-upload').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                let oldPublicId = null;

                try {
                    // Show loading state
                    const originalBackground = document.getElementById('wallpaper-container').style.backgroundImage;
                    document.getElementById('wallpaper-container').style.backgroundImage = 'linear-gradient(45deg, #f8f9fa 25%, #e9ecef 25%, #e9ecef 50%, #f8f9fa 50%, #f8f9fa 75%, #e9ecef 75%, #e9ecef 100%)';
                    document.getElementById('wallpaper-container').style.backgroundSize = '20px 20px';

                    // Store old public_id for deletion after successful upload
                    if (currentAdminData && currentAdminData.wallpaper_public_id) {
                        oldPublicId = currentAdminData.wallpaper_public_id;
                    }

                    // Upload new wallpaper to Cloudinary
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('upload_preset', cloudinaryConfig.uploadPresetWallpaper);

                    console.log('Uploading wallpaper to Cloudinary...');

                    const uploadResponse = await fetch(`https://api.cloudinary.com/v1_1/${cloudinaryConfig.cloudName}/upload`, {
                        method: 'POST',
                        body: formData
                    });

                    if (!uploadResponse.ok) {
                        const errorText = await uploadResponse.text();
                        console.error('Cloudinary wallpaper upload failed:', errorText);
                        throw new Error('Failed to upload to Cloudinary');
                    }

                    const cloudinaryResult = await uploadResponse.json();
                    console.log('Cloudinary wallpaper upload result:', cloudinaryResult);

                    if (!cloudinaryResult.secure_url || !cloudinaryResult.public_id) {
                        throw new Error('Invalid response from Cloudinary');
                    }

                    // Update database records
                    console.log('Updating database records for wallpaper...');
                    const updateResponse = await fetch('/api/admin/update-photo-records', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            wallpaper_url: cloudinaryResult.secure_url,
                            wallpaper_public_id: cloudinaryResult.public_id,
                            type: 'wallpaper'
                        })
                    });

                    if (!updateResponse.ok) {
                        const errorData = await updateResponse.json();
                        console.error('Database update failed:', errorData);
                        throw new Error(errorData.message || 'Failed to update database');
                    }

                    const updateResult = await updateResponse.json();
                    console.log('Database update result:', updateResult);

                    // Update UI and current data
                    document.getElementById('wallpaper-container').style.backgroundImage = `url(${cloudinaryResult.secure_url})`;
                    document.getElementById('wallpaper-container').style.backgroundSize = 'cover';
                    currentAdminData.wallpaper_url = cloudinaryResult.secure_url;
                    currentAdminData.wallpaper_public_id = cloudinaryResult.public_id;

                    // Delete old image from Cloudinary after successful update
                    if (oldPublicId) {
                        console.log('Deleting old wallpaper from Cloudinary:', oldPublicId);
                        await deleteOldCloudinaryImage(oldPublicId, 'wallpaper');
                    }

                    showToast('Wallpaper updated successfully!', 'success', 2000);

                    // Refresh the entire page after 2 seconds to show the toast
                    setTimeout(() => {
                        location.reload();
                    }, 2000);

                } catch (error) {
                    console.error('Error uploading wallpaper:', error);
                    document.getElementById('wallpaper-container').style.backgroundImage = originalBackground;
                    showToast('Failed to upload wallpaper: ' + error.message, 'error', 4000);
                } finally {
                    // Clear the file input
                    e.target.value = '';
                }
            });

        });
    </script>
@endsection