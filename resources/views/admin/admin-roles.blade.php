@extends('layouts.admin')

@section('title', 'Manage Administrators')

@section('content')
  <style>
    .title-col,
    td.title-col {
      white-space: normal !important;
    }

    #confirmDeleteBtn {
      min-width: 120px;
    }

    /* Spinner for delete button */
    .spinner-border-sm {
      width: 1rem;
      height: 1rem;
    }

    /* Shared card-like styling for both sections */
    .form-section,
    .table-section {
      background-color: #fff;
    }

    .table-section .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .table-section table {
      table-layout: auto;
      min-width: 100%;
      font-size: 0.875rem;
    }


    .table-section table th,
    .table-section table td {
      vertical-align: middle;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .table-section table th {
      background-color: #eff0f1ff;
    }

    /* Column width adjustments */
    .table-section table th:nth-child(1),
    /* Admin ID */
    .table-section table td:nth-child(1) {
      width: 80px;
      min-width: 80px;
      max-width: 80px;
    }

    .table-section table th:nth-child(2),
    /* School ID */
    .table-section table td:nth-child(2) {
      width: 110px;
      min-width: 110px;
      max-width: 110px;
    }

    .table-section table th:nth-child(3),
    /* Full Name */
    .table-section table td:nth-child(3) {
      width: 110px;
      min-width: 110px;
      white-space: normal;
      /* Allow name wrapping */
      word-wrap: break-word;
    }

    .table-section table th:nth-child(4),
    /* Title */
    .table-section table td:nth-child(4) {
      width: 110px;
      min-width: 110px;
      white-space: normal;
      word-wrap: break-word;
    }

    .table-section table th:nth-child(5),
    /* Email */
    .table-section table td:nth-child(5) {
      width: 200px;
      min-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .table-section table th:nth-child(6),
    /* Phone Number */
    .table-section table td:nth-child(6) {
      width: 120px;
      min-width: 120px;
      max-width: 120px;
    }

    .table-section table th:nth-child(7),
    /* Role */
    .table-section table td:nth-child(7) {
      width: 120px;
      min-width: 120px;
      white-space: normal;
      /* Allow role name wrapping */
    }

    /* Department column */
    .table-section table th:nth-child(8) {
      width: auto;
      min-width: 150px;
      white-space: nowrap;
      /* Prevent header from breaking */
      overflow: visible;
    }

    .table-section table td:nth-child(8) {
      width: auto;
      min-width: 150px;
      white-space: normal;
      /* Allow department badges to wrap */
      word-wrap: break-word;
    }

    /* Services column - NEW */
    .table-section table th:nth-child(9) {
      width: auto;
      min-width: 150px;
      white-space: nowrap;
      overflow: visible;
    }

    .table-section table td:nth-child(9) {
      width: auto;
      min-width: 150px;
      white-space: normal;
      word-wrap: break-word;
    }

    /* Actions column */
    .table-section table th:nth-child(10) {
      width: 120px;
      min-width: 120px;
      max-width: 120px;
      white-space: nowrap;
      overflow: visible;
    }

    .table-section table td:nth-child(10) {
      width: 120px;
      min-width: 120px;
      max-width: 120px;
      white-space: nowrap;
      overflow: visible;
    }

    /* Ensure action buttons container doesn't wrap */
    .table-section table td:nth-child(10) .btn {
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* Badge styles */
    .badge-department {
      background-color: #fef3c7 !important;
      /* pastel yellow */
      color: #92400e !important;
      /* darker yellow/brown for text */
      padding: 0.35rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 500;
      border-radius: 0.375rem;
      margin-bottom: 2px;
      display: inline-block;
      border: 1px solid #fde68a;
    }

    .badge-service {
      background-color: #d1fae5 !important;
      /* pastel teal */
      color: #065f46 !important;
      /* darker teal for text */
      padding: 0.35rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 500;
      border-radius: 0.375rem;
      margin-bottom: 2px;
      display: inline-block;
      border: 1px solid #a7f3d0;
    }

    /* Container for badges to stack vertically */
    .badge-container {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    /* Department button styles */
    .department-btn {
      background-color: #6c757d !important;
      /* solid gray */
      color: #fff !important;
      border-color: #6c757d !important;
      transition: background-color 0.2s ease;
    }

    /* Darker gray on hover if NOT selected */
    .department-btn:hover:not(.selected) {
      background-color: #5a6268 !important;
      /* darker gray */
      border-color: #5a6268 !important;
    }

    .department-btn.selected {
      background-color: var(--btn-primary) !important;
      /* custom blue */
      color: #fff !important;
      border-color: var(--btn-primary) !important;
    }

    /* Optional: keep same blue on hover when selected */
    .department-btn.selected:hover {
      background-color: var(--btn-primary-hover) !important;
      /* slightly darker blue */
      border-color: var(--btn-primary-hover) !important;
    }

    /* Department buttons styling */
    #department-buttons-container button,
    #add-department-buttons-container button {
      display: inline-flex !important;
      width: auto !important;
    }
  </style>

  <main id="main">
    <div class="container-fluid px-4">

      <!-- Existing Admins Card -->
      <section class="card border-0 shadow-sm mb-3">

        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
          <span class="mb-0 fw-bold">Existing Admins</span>

          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-plus-circle me-2"></i>Add New
          </button>
        </div>

        <div class="card-body p-4">

          <!-- Loading indicator -->
          <div id="adminLoading" class="text-center my-4">
            <p class="mb-2">Loading admins...</p>
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div class="table-responsive" id="adminTableWrapper" style="display: none;">
            <table class="table table-hover align-middle mb-0 text-nowrap">
              <thead>
                <tr>
                  <th>Admin ID</th>
                  <th>School ID</th>
                  <th>Full Name</th>
                  <th class="title-col">Title</th>
                  <th>Email</th>
                  <th>Phone Number</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody id="adminListBody">
                <!-- Dynamic content -->
              </tbody>
            </table>
          </div>

        </div>

      </section>

      <!-- Managing Resources Card -->
      <section class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white fw-bold py-3 d-flex align-items-center">
          Managing Departments & Services
        </div>

        <div class="card-body p-4">

          <!-- Loading indicator -->
          <div id="resourcesLoading" class="text-center my-4" style="display: none;">
            <p class="mb-2">Loading resources...</p>
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div id="resourcesGrid" class="row g-3">
            <!-- Dynamic content will be loaded here -->
          </div>

        </div>

      </section>

      <!-- Add Admin Modal -->
      <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add New Admin</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="addModalLoading" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary mb-3" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted">Loading form data...</p>
              </div>

              <div id="addModalContent">
                <form id="addAdminForm" novalidate>
                  @csrf
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label for="first_name" class="form-label">First Name</label>
                      <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name"
                        required>
                    </div>
                    <div class="col-md-4">
                      <label for="middle_name" class="form-label">Middle Name</label>
                      <input type="text" class="form-control" id="middle_name" name="middle_name"
                        placeholder="Middle Name">
                    </div>
                    <div class="col-md-4">
                      <label for="last_name" class="form-label">Last Name</label>
                      <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name"
                        required>
                    </div>

                    <!-- Title Field -->
                    <div class="col-md-6">
                      <label for="title" class="form-label">Title</label>
                      <input type="text" class="form-control" id="title" name="title"
                        placeholder="e.g., Dr., Prof., Mr., Ms." maxlength="100">
                    </div>

                    <div class="col-md-6">
                      <label for="school_id" class="form-label d-flex align-items-center">
                        School ID
                        <small class="text-muted ms-2">(Optional)</small>
                      </label>
                      <input type="text" class="form-control" id="school_id" name="school_id" placeholder="00-0000-00"
                        pattern="\d{2}-\d{4}-\d{2}" maxlength="10" minlength="10">
                    </div>
                    <div class="col-md-6">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control" id="email" name="email" placeholder="samplemail@gmail.com"
                        required minlength="6" maxlength="150" autocomplete="off">
                    </div>

                    <div class="col-md-6">
                      <label for="contact_number" class="form-label">Phone Number</label>
                      <input type="tel" class="form-control" id="contact_number" name="contact_number"
                        placeholder="e.g. 09123456789" pattern="\d{11,20}" minlength="11" maxlength="20">
                    </div>

                    <div class="col-md-6">
                      <label for="role_id" class="form-label">Role</label>
                      <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">Select a role</option>
                      </select>
                    </div>

                    <div class="col-12">
                      <label for="password" class="form-label d-flex align-items-center">
                        Temporary Password
                        <small class="text-muted ms-2">(Admin will be prompted to change this upon first login.)</small>
                      </label>
                      <input type="password" class="form-control" id="password" name="password"
                        placeholder="Temporary Password" required minlength="8" maxlength="12">
                    </div>

                    <!-- Departments Checklist Section -->
                    <div class="col-12">
                      <label class="form-label fw-bold">Departments</label>
                      <div class="row">
                        <div class="col-md-6">
                          <div class="card border">
                            <div class="card-header bg-light py-2">
                              <h6 class="mb-0">Select Departments</h6>
                            </div>
                            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                              <div id="add-departments-checklist" class="d-flex flex-column gap-2">
                                <div class="text-muted">Loading departments...</div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="card border">
                            <div class="card-header bg-light py-2">
                              <h6 class="mb-0">Selected Departments</h6>
                            </div>
                            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                              <div id="add-selected-departments-preview" class="d-flex flex-column gap-2">
                                <div class="text-muted">No departments selected</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <input type="hidden" id="add-selected-departments" name="department_ids">
                    </div>

                    <!-- Services Checklist Section -->
                    <div class="col-12">
                      <label class="form-label fw-bold">Services</label>
                      <div class="row">
                        <div class="col-md-6">
                          <div class="card border">
                            <div class="card-header bg-light py-2">
                              <h6 class="mb-0">Select Services</h6>
                            </div>
                            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                              <div id="add-services-checklist" class="d-flex flex-column gap-2">
                                <div class="text-muted">Loading services...</div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="card border">
                            <div class="card-header bg-light py-2">
                              <h6 class="mb-0">Selected Services</h6>
                            </div>
                            <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                              <div id="add-selected-services-preview" class="d-flex flex-column gap-2">
                                <div class="text-muted">No services selected</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <input type="hidden" id="add-selected-services" name="service_ids">
                    </div>
                  </div>
                </form>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" form="addAdminForm" class="btn btn-primary">Add Admin</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Admin Modal -->
  <div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Admin</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="editModalLoading" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Loading admin data...</p>
          </div>

          <div id="editModalContent" style="display: none;">
            <form id="editAdminForm">
              @csrf
              <input type="hidden" id="edit_admin_id" name="admin_id">
              <div class="row g-3">
                <div class="col-md-4">
                  <label for="edit_first_name" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                </div>
                <div class="col-md-4">
                  <label for="edit_middle_name" class="form-label">Middle Name</label>
                  <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
                </div>
                <div class="col-md-4">
                  <label for="edit_last_name" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                </div>

                <!-- Title Field in Edit Modal -->
                <div class="col-md-6">
                  <label for="edit_title" class="form-label">Title</label>
                  <input type="text" class="form-control" id="edit_title" name="title"
                    placeholder="e.g., Dr., Prof., Mr., Ms." maxlength="100">
                </div>

                <div class="col-md-6">
                  <label for="edit_school_id" class="form-label d-flex align-items-center">
                    School ID
                    <small class="text-muted ms-2">(Optional)</small>
                  </label>
                  <input type="text" class="form-control" id="edit_school_id" name="school_id" placeholder="00-0000-00"
                    pattern="\d{2}-\d{4}-\d{2}" maxlength="10" minlength="10">
                </div>
                <div class="col-md-6">
                  <label for="edit_email" class="form-label">Email</label>
                  <input type="email" class="form-control" id="edit_email" name="email" required>
                </div>
                <div class="col-md-6">
                  <label for="edit_contact_number" class="form-label">Phone Number</label>
                  <input type="tel" class="form-control" id="edit_contact_number" name="contact_number"
                    placeholder="e.g. 09123456789" pattern="\d{11,}" minlength="11">
                </div>
                <div class="col-md-6">
                  <label for="edit_role_id" class="form-label">Role</label>
                  <select class="form-select" id="edit_role_id" name="role_id" required>
                    <option value="">Loading roles...</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="edit_password" class="form-label">
                    New Password
                  </label>

                  <input type="password" class="form-control" id="edit_password" name="password"
                    placeholder="New Password">

                  <small class="form-text text-muted">
                    Leave blank to keep current password
                  </small>
                </div>

                <!-- Departments Checklist Section -->
                <div class="col-12">
                  <label class="form-label fw-bold">Departments</label>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card border">
                        <div class="card-header bg-light py-2">
                          <h6 class="mb-0">Select Departments</h6>
                        </div>
                        <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                          <div id="edit-departments-checklist" class="d-flex flex-column gap-2">
                            <div class="text-muted">Loading departments...</div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="card border">
                        <div class="card-header bg-light py-2">
                          <h6 class="mb-0">Selected Departments</h6>
                        </div>
                        <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                          <div id="edit-selected-departments-preview" class="d-flex flex-column gap-2">
                            <div class="text-muted">No departments selected</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <input type="hidden" id="edit-selected-departments" name="department_ids">
                </div>

                <!-- Services Checklist Section -->
                <div class="col-12">
                  <label class="form-label fw-bold">Services</label>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="card border">
                        <div class="card-header bg-light py-2">
                          <h6 class="mb-0">Select Services</h6>
                        </div>
                        <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                          <div id="edit-services-checklist" class="d-flex flex-column gap-2">
                            <div class="text-muted">Loading services...</div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="card border">
                        <div class="card-header bg-light py-2">
                          <h6 class="mb-0">Selected Services</h6>
                        </div>
                        <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                          <div id="edit-selected-services-preview" class="d-flex flex-column gap-2">
                            <div class="text-muted">No services selected</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <input type="hidden" id="edit-selected-services" name="service_ids">
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="modal-footer">
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
        <div class="modal-header">
          <h5 class="modal-title">Confirm Deletion</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <i class="bi bi-exclamation-triangle-fill text-danger mb-3" style="font-size: 2rem;"></i>
          <p class="mb-1 fw-bold">Are you sure you want to delete this admin?</p>
          <p class="mb-3 text-muted">This action cannot be undone. All associated data will be permanently removed.</p>

          <div id="deleteAdminDetails" class="bg-light p-3 rounded">
            <!-- Admin details will be populated here -->
          </div>
        </div>
        <div class="modal-footer">
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

    // School ID formatting function
    function formatSchoolId(input) {
      input.addEventListener('input', function (e) {
        let digits = e.target.value.replace(/\D/g, '');
        if (digits.length > 2 && digits.length <= 6) {
          digits = digits.slice(0, 2) + '-' + digits.slice(2);
        } else if (digits.length > 6) {
          digits = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6, 8);
        }
        e.target.value = digits;
      });
    }

    // Function to create add department checklist - NO pre-selection
    function createAddDepartmentChecklist() {
      const checklistContainer = document.getElementById('add-departments-checklist');
      if (!checklistContainer) return;

      if (!window.departmentsData || window.departmentsData.length === 0) {
        checklistContainer.innerHTML = '<div class="text-muted">No departments available</div>';
        return;
      }

      checklistContainer.innerHTML = '';

      window.departmentsData.forEach(dept => {
        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
                    <input class="form-check-input add-department-checkbox" type="checkbox" 
                           value="${dept.department_id}" id="add_dept_${dept.department_id}">
                    <label class="form-check-label" for="add_dept_${dept.department_id}">
                      ${dept.department_name} (${dept.department_code})
                    </label>
                  `;

        div.querySelector('.add-department-checkbox').addEventListener('change', updateAddDepartmentsPreview);
        checklistContainer.appendChild(div);
      });
    }

    // Function to create add service checklist - NO pre-selection
    function createAddServiceChecklist() {
      const checklistContainer = document.getElementById('add-services-checklist');
      if (!checklistContainer) return;

      if (!window.servicesData || window.servicesData.length === 0) {
        checklistContainer.innerHTML = '<div class="text-muted">No services available</div>';
        return;
      }

      checklistContainer.innerHTML = '';

      window.servicesData.forEach(service => {
        const serviceId = service.service_id || service.id;
        const serviceName = service.service_name || service.name || 'Unknown';

        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
                    <input class="form-check-input add-service-checkbox" type="checkbox" 
                           value="${serviceId}" id="add_service_${serviceId}">
                    <label class="form-check-label" for="add_service_${serviceId}">
                      ${serviceName}
                    </label>
                  `;

        div.querySelector('.add-service-checkbox').addEventListener('change', updateAddServicesPreview);
        checklistContainer.appendChild(div);
      });
    }

    // Function to update add departments preview
    function updateAddDepartmentsPreview() {
      const selectedCheckboxes = document.querySelectorAll('#add-departments-checklist .add-department-checkbox:checked');
      const selectedDeptIds = Array.from(selectedCheckboxes).map(cb => cb.value);
      document.getElementById('add-selected-departments').value = JSON.stringify(selectedDeptIds);

      // Update preview
      const previewContainer = document.getElementById('add-selected-departments-preview');
      if (selectedDeptIds.length === 0) {
        previewContainer.innerHTML = '<div class="text-muted">No departments selected</div>';
        return;
      }

      let previewHtml = '';
      selectedDeptIds.forEach(deptId => {
        const dept = window.departmentsData.find(d => d.department_id.toString() === deptId.toString());
        if (dept) {
          previewHtml += `
                      <div class="p-2 bg-light rounded mb-1">
                        ${dept.department_name} (${dept.department_code})
                      </div>
                    `;
        }
      });
      previewContainer.innerHTML = previewHtml;
    }

    // Function to update add services preview
    function updateAddServicesPreview() {
      const selectedCheckboxes = document.querySelectorAll('#add-services-checklist .add-service-checkbox:checked');
      const selectedServiceIds = Array.from(selectedCheckboxes).map(cb => cb.value);
      document.getElementById('add-selected-services').value = JSON.stringify(selectedServiceIds);

      // Update preview
      const previewContainer = document.getElementById('add-selected-services-preview');
      if (selectedServiceIds.length === 0) {
        previewContainer.innerHTML = '<div class="text-muted">No services selected</div>';
        return;
      }

      let previewHtml = '';
      selectedServiceIds.forEach(serviceId => {
        const service = window.servicesData.find(s => (s.service_id || s.id).toString() === serviceId.toString());
        if (service) {
          const serviceName = service.service_name || service.name || 'Unknown';
          previewHtml += `
                      <div class="p-2 bg-light rounded mb-1">
                        ${serviceName}
                      </div>
                    `;
        }
      });
      previewContainer.innerHTML = previewHtml;
    }

    // Function to create department checklist - ensure proper checkbox state
    function createDepartmentChecklist(selectedDeptIds = []) {
      const checklistContainer = document.getElementById('edit-departments-checklist');
      if (!checklistContainer) return;

      if (!window.departmentsData || window.departmentsData.length === 0) {
        checklistContainer.innerHTML = '<div class="text-muted">No departments available</div>';
        return;
      }

      // Clear container first
      checklistContainer.innerHTML = '';

      window.departmentsData.forEach(dept => {
        const deptIdStr = dept.department_id.toString();
        // Make sure we're comparing strings
        const isChecked = selectedDeptIds.some(id => id.toString() === deptIdStr);

        const div = document.createElement('div');
        div.className = 'form-check';
        div.innerHTML = `
                              <input class="form-check-input department-checkbox" type="checkbox" 
                                     value="${dept.department_id}" id="dept_${dept.department_id}"
                                     ${isChecked ? 'checked' : ''}>
                              <label class="form-check-label" for="dept_${dept.department_id}">
                                ${dept.department_name} (${dept.department_code})
                              </label>
                            `;

        div.querySelector('.department-checkbox').addEventListener('change', updateSelectedDepartmentsPreview);
        checklistContainer.appendChild(div);
      });

      console.log('Department checklist created with', selectedDeptIds.length, 'selected items');
    }

    // Function to update hidden input with selected departments - FIXED
    function updateSelectedDepartments(containerType) {
      let deptContainer, hiddenInput;

      if (containerType === 'add-department-buttons-container') {
        deptContainer = document.getElementById('add-department-buttons-container');
        hiddenInput = document.getElementById('add-selected-departments');
      } else {
        deptContainer = document.getElementById('edit-department-buttons-container');
        hiddenInput = document.getElementById('edit-selected-departments');
      }

      if (!deptContainer || !hiddenInput) return;

      // Look for buttons with EITHER selected OR active class
      const selectedButtons = deptContainer.querySelectorAll('.btn.selected, .btn.active');
      const selectedDeptIds = Array.from(selectedButtons).map(btn => btn.dataset.deptId);
      hiddenInput.value = JSON.stringify(selectedDeptIds);
    }

    // Function to auto-select all departments for specific roles - FIXED
    function setupRoleAutoSelect(roleSelectId, deptContainerId) {
      const roleSelect = document.getElementById(roleSelectId);
      if (!roleSelect) return;

      roleSelect.addEventListener('change', function () {
        const selectedRoleId = parseInt(this.value);

        // Role IDs that should auto-select all departments
        const autoSelectRoleIds = [1, 2]; // Head Admin and Vice President

        const deptContainer = document.getElementById(deptContainerId);
        if (!deptContainer) return;

        const allButtons = deptContainer.querySelectorAll('.department-btn');

        if (autoSelectRoleIds.includes(selectedRoleId)) {
          allButtons.forEach(button => {
            button.classList.add('selected');
            button.classList.add('active');
          });
        } else {
          allButtons.forEach(button => {
            button.classList.remove('selected');
            button.classList.remove('active');
          });
        }
        updateSelectedDepartments(deptContainerId);
      });
    }

    // Function to manually select all departments
    function selectAllDepartments(containerId) {
      const deptContainer = document.getElementById(containerId);
      if (!deptContainer) return;

      const allButtons = deptContainer.querySelectorAll('.department-btn');
      allButtons.forEach(button => {
        button.classList.add('selected');
        button.classList.add('active');
      });
      updateSelectedDepartments(containerId);
    }

    document.addEventListener('DOMContentLoaded', function () {
      const addAdminForm = document.getElementById('addAdminForm');
      const roleSelect = document.getElementById('role_id');
      const adminListBody = document.getElementById('adminListBody');
      const token = localStorage.getItem('adminToken') || localStorage.getItem('token');

      // Apply School ID formatting
      formatSchoolId(document.getElementById('school_id'));
      formatSchoolId(document.getElementById('edit_school_id'));

      // Function to load departments
      async function loadDepartments() {
        try {
          const response = await fetch('/api/departments', {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
            }
          });

          if (!response.ok) {
            throw new Error('Failed to fetch departments');
          }

          window.departmentsData = await response.json();
        } catch (error) {
          console.error('Error loading departments:', error);
          window.departmentsData = [];
        }
      }

      // Function to load managing resources grid
      async function loadManagingResources() {
        const resourcesLoading = document.getElementById('resourcesLoading');
        const resourcesGrid = document.getElementById('resourcesGrid');

        resourcesLoading.style.display = 'block';
        resourcesGrid.innerHTML = '';

        try {
          const response = await fetch('/api/admins', {
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          });

          if (!response.ok) throw new Error('Failed to fetch admin resources');

          const responseData = await response.json();
          const admins = responseData.data || responseData;

          resourcesLoading.style.display = 'none';

          if (!admins || admins.length === 0) {
            resourcesGrid.innerHTML = '<div class="col-12 text-center text-muted">No admin resources found</div>';
            return;
          }

          admins.forEach(admin => {
            const currentAdminId = localStorage.getItem('adminId');
            if (currentAdminId && admin.admin_id == currentAdminId) return;

            // Department badges
            let departmentsHtml = '';
            if (admin.departments?.length) {
              admin.departments.forEach(dept => {
                const deptName = dept.department_name || dept.name || 'Unknown';
                const isPrimary = dept.pivot?.is_primary ? '<span class="ms-1 text-warning">★</span>' : '';
                departmentsHtml += `<span class="badge-department d-inline-block me-1 mb-1">${deptName}${isPrimary}</span>`;
              });
            } else {
              departmentsHtml = '<span class="text-muted">None</span>';
            }

            // Service badges
            let servicesHtml = '';
            if (admin.services?.length) {
              admin.services.forEach(service => {
                const serviceName = service.service_name || service.name || 'Unknown';
                servicesHtml += `<span class="badge-service d-inline-block me-1 mb-1">${serviceName}</span>`;
              });
            } else {
              servicesHtml = '<span class="text-muted">None</span>';
            }

            const fullName = admin.full_name || `${admin.first_name} ${admin.middle_name ? admin.middle_name + ' ' : ''}${admin.last_name}`;

            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4';
            card.innerHTML = `
                              <div class="card h-100 border">
                                <div class="card-body">
                                  <h6 class="card-title fw-bold mb-3">${fullName}</h6>
                                  <div class="mb-2">
                                    <small class="text-muted d-block mb-1">Departments:</small>
                                    <div>${departmentsHtml}</div>
                                  </div>
                                  <div>
                                    <small class="text-muted d-block mb-1">Services:</small>
                                    <div>${servicesHtml}</div>
                                  </div>
                                </div>
                              </div>
                            `;
            resourcesGrid.appendChild(card);
          });
        } catch (error) {
          console.error('Error loading managing resources:', error);
          resourcesLoading.style.display = 'none';
          resourcesGrid.innerHTML = '<div class="col-12 text-center text-danger">Error loading resources</div>';
        }
      }

      // Function to render admin list - UPDATED to remove departments and services columns
      async function loadAdminList() {
        const loadingEl = document.getElementById('adminLoading');
        const tableWrapper = document.getElementById('adminTableWrapper');

        // Show spinner and hide table at start
        loadingEl.style.display = 'block';
        tableWrapper.style.display = 'none';

        try {
          const response = await fetch('/api/admins', {
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          });

          if (!response.ok) throw new Error('Failed to fetch admin list');

          const responseData = await response.json();

          // Handle the new response structure with success/data wrapper
          const admins = responseData.data || responseData;

          adminListBody.innerHTML = '';

          if (!admins || admins.length === 0) {
            adminListBody.innerHTML =
              '<tr><td colspan="8" class="text-center">No other admins found</td></tr>';
          } else {
            admins.forEach(admin => {
              const currentAdminId = localStorage.getItem('adminId');
              if (currentAdminId && admin.admin_id == currentAdminId) return;

              const row = document.createElement('tr');
              row.innerHTML = `
                                <td>${admin.admin_id}</td>
                                <td>${admin.school_id || 'N/A'}</td>
                                <td>${admin.full_name || `${admin.first_name} ${admin.middle_name ? admin.middle_name + ' ' : ''}${admin.last_name}`}</td>
                                <td class="title-col">${admin.title || 'N/A'}</td>
                                <td title="${admin.email}">${admin.email}</td>
                                <td>${admin.contact_number || 'N/A'}</td>
                                <td>${admin.role ? admin.role.role_title : 'N/A'}</td>
                                <td>
                                  <button class="btn btn-sm btn-info me-1" onclick="openEditModal(${admin.admin_id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                  </button>
                                  <button class="btn btn-sm btn-danger" onclick="deleteAdmin(${admin.admin_id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                  </button>
                                </td>
                              `;
              adminListBody.appendChild(row);
            });
          }

          // Show table & hide spinner when done
          loadingEl.style.display = 'none';
          tableWrapper.style.display = 'block';

        } catch (error) {
          console.error('Error loading admin list:', error);
          adminListBody.innerHTML =
            '<tr><td colspan="8" class="text-center">Error loading admin list</td></tr>';

          // Hide spinner even on error
          loadingEl.style.display = 'none';
          tableWrapper.style.display = 'block';
        }
      }

      // Delete admin function - UPDATED to match backend deleteAdmin method
      let adminToDelete = null;

      window.deleteAdmin = function (adminId) {
        adminToDelete = adminId;

        // Fetch admin details to show in confirmation modal
        fetch(`/api/admins/${adminId}`, {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
          }
        })
          .then(response => {
            if (!response.ok) throw new Error('Failed to fetch admin details');
            return response.json();
          })
          .then(admin => {
            // Populate admin details in modal
            document.getElementById('deleteAdminDetails').innerHTML = `
                        <div class="row">
                          <div class="col-4 fw-bold">School ID:</div>
                          <div class="col-8">${admin.school_id || 'N/A'}</div>
                          <div class="col-4 fw-bold">Name:</div>
                          <div class="col-8">${admin.first_name} ${admin.middle_name ? admin.middle_name + ' ' : ''}${admin.last_name}</div>
                          <div class="col-4 fw-bold">Title:</div>
                          <div class="col-8">${admin.title || 'N/A'}</div>
                          <div class="col-4 fw-bold">Email:</div>
                          <div class="col-8">${admin.email}</div>
                          <div class="col-4 fw-bold">Role:</div>
                          <div class="col-8">${admin.role ? admin.role.role_title : 'N/A'}</div>
                        </div>
                      `;

            // Show the modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
            deleteModal.show();
          })
          .catch(error => {
            console.error('Error fetching admin details:', error);
            // Fallback: show modal with basic info if details fetch fails
            document.getElementById('deleteAdminDetails').innerHTML = `
                        <div class="text-center">
                          <p class="mb-0">Admin ID: ${adminId}</p>
                          <p class="text-muted">Unable to load full details</p>
                        </div>
                      `;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
            deleteModal.show();
          });
      };

      // Confirm delete button handler - WITH BETTER DEBUGGING
      document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
        if (!adminToDelete) return;

        const deleteBtn = this;
        const originalText = deleteBtn.innerHTML;

        // Show loading state
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        deleteBtn.disabled = true;

        try {
          console.log('Sending DELETE request for admin ID:', adminToDelete);

          const response = await fetch(`/api/admins/${adminToDelete}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          });

          console.log('Response status:', response.status);
          console.log('Response headers:', response.headers);

          // Try to get the response text first
          const responseText = await response.text();
          console.log('Raw response text:', responseText);

          let responseData;
          try {
            // Try to parse as JSON
            responseData = JSON.parse(responseText);
            console.log('Parsed response data:', responseData);
          } catch (parseError) {
            console.error('Failed to parse response as JSON:', parseError);
            throw new Error(`Server returned non-JSON response: ${responseText.substring(0, 100)}`);
          }

          if (!response.ok) {
            throw new Error(responseData.message || responseData.error || `HTTP error ${response.status}`);
          }

          // Success - close modal and refresh list
          bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal')).hide();

          // Show success message using the message from backend
          showToast(responseData.message || 'Admin deleted successfully', 'success');

          await Promise.all([
            loadAdminList(),
            loadManagingResources()
          ]);

        } catch (error) {
          console.error('Detailed error:', error);

          // Show error message
          showToast(error.message, 'error');

          // Reset button state
          deleteBtn.innerHTML = originalText;
          deleteBtn.disabled = false;
        }
      });

      // Clear add modal data when hidden
      document.getElementById('addAdminModal').addEventListener('hidden.bs.modal', function () {
        // Clear form
        document.getElementById('addAdminForm').reset();

        // Clear checklists
        document.getElementById('add-departments-checklist').innerHTML = '<div class="text-muted">Loading departments...</div>';
        document.getElementById('add-services-checklist').innerHTML = '<div class="text-muted">Loading services...</div>';

        // Clear previews
        document.getElementById('add-selected-departments-preview').innerHTML = '<div class="text-muted">No departments selected</div>';
        document.getElementById('add-selected-services-preview').innerHTML = '<div class="text-muted">No services selected</div>';

        // Clear hidden inputs
        document.getElementById('add-selected-departments').value = '[]';
        document.getElementById('add-selected-services').value = '[]';
      });


      // Reset modal state when hidden
      document.getElementById('deleteConfirmationModal').addEventListener('hidden.bs.modal', function () {
        adminToDelete = null;
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        deleteBtn.innerHTML = 'Delete Admin';
        deleteBtn.disabled = false;
      });

      // Clear modal data when hidden
      document.getElementById('editAdminModal').addEventListener('hidden.bs.modal', function () {
        // Clear form fields
        document.getElementById('editAdminForm').reset();

        // Clear checklists
        document.getElementById('edit-departments-checklist').innerHTML = '<div class="text-muted">Loading departments...</div>';
        document.getElementById('edit-services-checklist').innerHTML = '<div class="text-muted">Loading services...</div>';

        // Clear previews
        document.getElementById('edit-selected-departments-preview').innerHTML = '<div class="text-muted">No departments selected</div>';
        document.getElementById('edit-selected-services-preview').innerHTML = '<div class="text-muted">No services selected</div>';

        // Clear hidden inputs
        document.getElementById('edit-selected-departments').value = '[]';
        document.getElementById('edit-selected-services').value = '[]';

      });

      // Fetch roles and populate dropdown - UPDATED for new API response structure
      async function loadRoles() {
        try {
          const response = await fetch('/api/admin-role', {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
            }
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const responseData = await response.json();

          // Handle the new response structure with success/data wrapper
          const roles = responseData.data || responseData;

          // Populate add form role select
          roleSelect.innerHTML = '<option value="">Select a role</option>';
          if (roles && roles.length > 0) {
            roles.forEach(role => {
              const option = new Option(role.role_title, role.role_id);
              roleSelect.add(option);
            });
          }

          // Populate edit form role select
          const editRoleSelect = document.getElementById('edit_role_id');
          if (editRoleSelect) {
            editRoleSelect.innerHTML = '<option value="">Select a role</option>';
            roles.forEach(role => {
              const option = new Option(role.role_title, role.role_id);
              editRoleSelect.add(option);
            });
          }
        } catch (error) {
          console.error('Error loading roles:', error);
          roleSelect.innerHTML = '<option value="">Error loading roles</option>';
        }
      }

      // Add admin form submission
      addAdminForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Validate School ID format (optional)
        const schoolId = document.getElementById('school_id').value.trim();
        if (schoolId) {
          const schoolIdPattern = /^\d{2}-\d{4}-\d{2}$/;
          if (!schoolIdPattern.test(schoolId)) {
            showToast('School ID must follow the format ##-####-##');
            return;
          }
        }

        // Validate email format
        const email = document.getElementById('email').value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
          showToast('Please enter a valid email address');
          return;
        }

        // Get selected departments and services
        const selectedDeptIds = JSON.parse(document.getElementById('add-selected-departments').value || '[]');
        const selectedServiceIds = JSON.parse(document.getElementById('add-selected-services').value || '[]');

        // Validate department selection for certain roles
        const roleId = parseInt(document.getElementById('role_id').value);
        const noDeptRequiredRoleIds = [1, 2]; // Head Admin and Vice President

        if (selectedDeptIds.length === 0 && !noDeptRequiredRoleIds.includes(roleId)) {
          showToast('Please select at least one department for this role');
          return;
        }

        const formData = {
          first_name: document.getElementById('first_name').value,
          middle_name: document.getElementById('middle_name').value,
          last_name: document.getElementById('last_name').value,
          title: document.getElementById('title').value,
          email: document.getElementById('email').value,
          contact_number: document.getElementById('contact_number').value || null,
          role_id: roleId,
          school_id: schoolId || null,
          password: document.getElementById('password').value,
          department_ids: selectedDeptIds,
          service_ids: selectedServiceIds,
          photo_url: 'https://res.cloudinary.com/dn98ntlkd/image/upload/v1751033911/ksdmh4mmpxdtjogdgjmm.png',
          photo_public_id: 'ksdmh4mmpxdtjogdgjmm',
          wallpaper_url: null,
          wallpaper_public_id: null,
          signature_url: null,
          signature_public_id: null
        };

        console.log('Sending form data:', formData);

        // FIXED: Get the submit button from the modal footer
        const submitBtn = document.querySelector('#addAdminModal .btn-primary[type="submit"]');

        if (!submitBtn) {
          console.error('Submit button not found');
          return;
        }

        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
        submitBtn.disabled = true;

        try {
          const response = await fetch('/api/admins', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
          });

          const responseData = await response.json();

          if (!response.ok) {
            console.error('Backend error response:', responseData);
            throw new Error(responseData.message || responseData.error || 'Failed to add admin');
          }

          showToast(responseData.message || 'Admin added successfully!', 'success');

          // Close the modal
          const addModal = bootstrap.Modal.getInstance(document.getElementById('addAdminModal'));
          if (addModal) {
            addModal.hide();
          }

          // Reset form
          addAdminForm.reset();
          document.getElementById('add-selected-departments').value = '[]';
          document.getElementById('add-selected-services').value = '[]';

          // Reset previews
          document.getElementById('add-selected-departments-preview').innerHTML = '<div class="text-muted">No departments selected</div>';
          document.getElementById('add-selected-services-preview').innerHTML = '<div class="text-muted">No services selected</div>';

          // Uncheck all checkboxes
          document.querySelectorAll('#add-departments-checklist .add-department-checkbox').forEach(cb => cb.checked = false);
          document.querySelectorAll('#add-services-checklist .add-service-checkbox').forEach(cb => cb.checked = false);

          // Refresh the admin list
          await loadAdminList();

        } catch (error) {
          console.error('Error adding admin:', error);
          showToast(error.message, 'error');
        } finally {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
      });
      // Function to load services for dropdowns
      async function loadServices() {
        try {
          const response = await fetch('/api/extra-services', {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
            }
          });

          if (!response.ok) {
            throw new Error('Failed to fetch services');
          }

          const responseData = await response.json();
          window.servicesData = responseData.data || responseData;
          return window.servicesData;
        } catch (error) {
          console.error('Error loading services:', error);
          window.servicesData = [];
          return [];
        }
      }

      // Function to create department checklist - NO BADGES
      function createDepartmentChecklist(selectedDeptIds = []) {
        const checklistContainer = document.getElementById('edit-departments-checklist');
        if (!checklistContainer) return;

        if (!window.departmentsData || window.departmentsData.length === 0) {
          checklistContainer.innerHTML = '<div class="text-muted">No departments available</div>';
          return;
        }

        checklistContainer.innerHTML = '';

        window.departmentsData.forEach(dept => {
          const isChecked = selectedDeptIds.includes(dept.department_id.toString());

          const div = document.createElement('div');
          div.className = 'form-check';
          div.innerHTML = `
                                <input class="form-check-input department-checkbox" type="checkbox" 
                                       value="${dept.department_id}" id="dept_${dept.department_id}"
                                       ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label" for="dept_${dept.department_id}">
                                  ${dept.department_name} (${dept.department_code})
                                </label>
                              `;

          div.querySelector('.department-checkbox').addEventListener('change', updateSelectedDepartmentsPreview);
          checklistContainer.appendChild(div);
        });
      }

      // Function to create service checklist - ensure proper checkbox state
      function createServiceChecklist(selectedServiceIds = []) {
        const checklistContainer = document.getElementById('edit-services-checklist');
        if (!checklistContainer) return;

        if (!window.servicesData || window.servicesData.length === 0) {
          checklistContainer.innerHTML = '<div class="text-muted">No services available</div>';
          return;
        }

        // Clear container first
        checklistContainer.innerHTML = '';

        window.servicesData.forEach(service => {
          const serviceId = service.service_id || service.id;
          const serviceIdStr = serviceId.toString();
          const serviceName = service.service_name || service.name || 'Unknown';
          // Make sure we're comparing strings
          const isChecked = selectedServiceIds.some(id => id.toString() === serviceIdStr);

          const div = document.createElement('div');
          div.className = 'form-check';
          div.innerHTML = `
                              <input class="form-check-input service-checkbox" type="checkbox" 
                                     value="${serviceId}" id="service_${serviceId}"
                                     ${isChecked ? 'checked' : ''}>
                              <label class="form-check-label" for="service_${serviceId}">
                                ${serviceName}
                              </label>
                            `;

          div.querySelector('.service-checkbox').addEventListener('change', updateSelectedServicesPreview);
          checklistContainer.appendChild(div);
        });

        console.log('Service checklist created with', selectedServiceIds.length, 'selected items');
      }

      // Function to update selected departments preview - NO BADGES
      function updateSelectedDepartmentsPreview() {
        const selectedCheckboxes = document.querySelectorAll('#edit-departments-checklist .department-checkbox:checked');
        const selectedDeptIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        document.getElementById('edit-selected-departments').value = JSON.stringify(selectedDeptIds);

        // Update preview
        const previewContainer = document.getElementById('edit-selected-departments-preview');
        if (selectedDeptIds.length === 0) {
          previewContainer.innerHTML = '<div class="text-muted">No departments selected</div>';
          return;
        }

        let previewHtml = '';
        selectedDeptIds.forEach(deptId => {
          const dept = window.departmentsData.find(d => d.department_id.toString() === deptId.toString());
          if (dept) {
            previewHtml += `
                                  <div class="p-2 bg-light rounded mb-1">
                                    ${dept.department_name} (${dept.department_code})
                                  </div>
                                `;
          }
        });
        previewContainer.innerHTML = previewHtml;

      }

      // Function to update selected services preview - FIXED to ensure hidden input is updated
      function updateSelectedServicesPreview() {
        const selectedCheckboxes = document.querySelectorAll('#edit-services-checklist .service-checkbox:checked');
        const selectedServiceIds = Array.from(selectedCheckboxes).map(cb => cb.value);

        // Make sure we're storing as an array of strings/numbers
        document.getElementById('edit-selected-services').value = JSON.stringify(selectedServiceIds);

        console.log('Updated selected services:', selectedServiceIds); // Debug log

        // Update preview
        const previewContainer = document.getElementById('edit-selected-services-preview');
        if (selectedServiceIds.length === 0) {
          previewContainer.innerHTML = '<div class="text-muted">No services selected</div>';
          return;
        }

        let previewHtml = '';
        selectedServiceIds.forEach(serviceId => {
          const service = window.servicesData.find(s => (s.service_id || s.id).toString() === serviceId.toString());
          if (service) {
            const serviceName = service.service_name || service.name || 'Unknown';
            previewHtml += `
                            <div class="p-2 bg-light rounded mb-1">
                              ${serviceName}
                            </div>
                          `;
          }
        });
        previewContainer.innerHTML = previewHtml;
      }

      // Updated Function to open edit modal with lazy loading - FIXED pre-selection
      window.openEditModal = async function (adminId) {
        const modalEl = document.getElementById('editAdminModal');
        const editButton = event?.target?.closest('button') || document.querySelector(`button[onclick="openEditModal(${adminId})"]`);
        const originalButtonHtml = editButton ? editButton.innerHTML : '';

        // Show loading spinner on button
        if (editButton) {
          editButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
          editButton.disabled = true;
        }

        // Show modal loading state
        document.getElementById('editModalLoading').style.display = 'block';
        document.getElementById('editModalContent').style.display = 'none';

        // Show modal immediately with loading state
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) {
          modal = new bootstrap.Modal(modalEl);
        }
        modal.show();

        try {
          // Clear any cached data to ensure fresh load
          window.departmentsData = null;
          window.servicesData = null;
          window.rolesData = null;

          // Load all required data in parallel
          const [adminResponse, rolesResponse, departmentsResponse, servicesResponse] = await Promise.all([
            fetch(`/api/admins/${adminId}/edit`, {
              headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
              }
            }),
            fetch('/api/admin-role', {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
              }
            }),
            fetch('/api/departments', {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
              }
            }),
            fetch('/api/extra-services', {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
              }
            })
          ]);

          if (!adminResponse.ok) throw new Error('Failed to fetch admin details');

          const admin = await adminResponse.json();

          // Handle roles response
          const rolesData = await rolesResponse.json();
          window.rolesData = rolesData.data || rolesData;

          // Handle departments response
          const deptsData = await departmentsResponse.json();
          window.departmentsData = deptsData.data || deptsData;

          // Handle services response
          const servicesData = await servicesResponse.json();
          window.servicesData = servicesData.data || servicesData;

          // Log for debugging
          console.log('Admin data:', admin);
          console.log('Departments data:', window.departmentsData);
          console.log('Services data:', window.servicesData);

          // Populate role dropdown FIRST
          const editRoleSelect = document.getElementById('edit_role_id');
          editRoleSelect.innerHTML = '<option value="">Select a role</option>';

          if (window.rolesData && window.rolesData.length > 0) {
            window.rolesData.forEach(role => {
              const option = new Option(role.role_title, role.role_id);
              editRoleSelect.add(option);
            });
          }

          // Populate form fields
          document.getElementById('edit_admin_id').value = admin.admin_id;
          document.getElementById('edit_first_name').value = admin.first_name || '';
          document.getElementById('edit_middle_name').value = admin.middle_name || '';
          document.getElementById('edit_last_name').value = admin.last_name || '';
          document.getElementById('edit_title').value = admin.title || '';
          document.getElementById('edit_email').value = admin.email || '';
          document.getElementById('edit_contact_number').value = admin.contact_number || '';
          document.getElementById('edit_school_id').value = admin.school_id || '';

          // Set role value AFTER options are populated
          let roleIdToSet = null;

          if (admin.role_id) {
            roleIdToSet = admin.role_id;
          } else if (admin.role && admin.role.role_id) {
            roleIdToSet = admin.role.role_id;
          }

          console.log('Setting role ID to:', roleIdToSet);

          if (roleIdToSet) {
            editRoleSelect.value = roleIdToSet;
          }

          // Get selected department IDs from the pivot data
          let selectedDeptIds = [];
          if (admin.departments && admin.departments.length > 0) {
            selectedDeptIds = admin.departments.map(dept => dept.department_id.toString());
          }

          // Get selected service IDs from the explicit array in response
          let selectedServiceIds = [];
          if (admin.service_ids && admin.service_ids.length > 0) {
            selectedServiceIds = admin.service_ids.map(id => id.toString());
          } else if (admin.services && admin.services.length > 0) {
            // Fallback to extracting from services if needed
            selectedServiceIds = admin.services.map(service => {
              const serviceId = service.service_id || service.id;
              return serviceId.toString();
            });
          }

          console.log('Selected departments to check:', selectedDeptIds);
          console.log('Selected services to check:', selectedServiceIds);

          // Clear and recreate checklists with pre-selected items
          const deptChecklistContainer = document.getElementById('edit-departments-checklist');
          deptChecklistContainer.innerHTML = ''; // Clear existing
          createDepartmentChecklist(selectedDeptIds);

          const serviceChecklistContainer = document.getElementById('edit-services-checklist');
          serviceChecklistContainer.innerHTML = ''; // Clear existing
          createServiceChecklist(selectedServiceIds);

          // Initialize hidden inputs
          document.getElementById('edit-selected-departments').value = JSON.stringify(selectedDeptIds);
          document.getElementById('edit-selected-services').value = JSON.stringify(selectedServiceIds);

          // Update previews
          updateSelectedDepartmentsPreview();
          updateSelectedServicesPreview();

          // Hide loading, show content
          document.getElementById('editModalLoading').style.display = 'none';
          document.getElementById('editModalContent').style.display = 'block';

        } catch (error) {
          console.error('Error opening edit modal:', error);
          showToast('Failed to load admin details: ' + error.message, 'error');

          // Hide modal on error
          modal.hide();
        } finally {
          // Restore button state
          if (editButton) {
            editButton.innerHTML = originalButtonHtml;
            editButton.disabled = false;
          }
        }
      };
      // Save edited admin - UPDATED with services and proper data refresh
      document.getElementById('saveAdminChanges').addEventListener('click', async function () {
        const adminId = document.getElementById('edit_admin_id').value;

        // Validate School ID format (optional)
        const schoolId = document.getElementById('edit_school_id').value.trim();
        if (schoolId) {
          const schoolIdPattern = /^\d{2}-\d{4}-\d{2}$/;
          if (!schoolIdPattern.test(schoolId)) {
            showToast('School ID must follow the format ##-####-##');
            return;
          }
        }

        // Get selected departments and services
        const selectedDeptIds = JSON.parse(document.getElementById('edit-selected-departments').value || '[]');

        // FIXED: Make sure we're getting the service IDs correctly
        const selectedServiceIds = JSON.parse(document.getElementById('edit-selected-services').value || '[]');

        console.log('Saving with service IDs:', selectedServiceIds); // Debug log

        // Validate department selection for certain roles
        const roleId = parseInt(document.getElementById('edit_role_id').value);
        const noDeptRequiredRoleIds = [1, 2]; // Head Admin and Vice President

        if (selectedDeptIds.length === 0 && !noDeptRequiredRoleIds.includes(roleId)) {
          showToast('Please select at least one department for this role');
          return;
        }

        const formData = {
          admin_id: adminId,
          first_name: document.getElementById('edit_first_name').value,
          middle_name: document.getElementById('edit_middle_name').value,
          last_name: document.getElementById('edit_last_name').value,
          title: document.getElementById('edit_title').value,
          email: document.getElementById('edit_email').value,
          contact_number: document.getElementById('edit_contact_number').value || null,
          role_id: roleId,
          school_id: schoolId || null,
          password: document.getElementById('edit_password').value || undefined,
          department_ids: selectedDeptIds,
          service_ids: selectedServiceIds, // Make sure this is included
          signature_url: null,
          signature_public_id: null
        };

        console.log('Sending form data:', formData); // Debug log

        const saveBtn = this;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        saveBtn.disabled = true;

        try {
          const response = await fetch(`/api/admins/${adminId}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formData)
          });

          if (!response.ok) {
            const errorData = await response.json();
            console.error('Backend error response:', errorData);
            throw new Error(errorData.message || 'Failed to update admin');
          }

          const responseData = await response.json();
          console.log('Update successful:', responseData); // Debug log

          showToast('Admin updated successfully!', 'success');

          // Refresh both the admin list and resources grid
          await Promise.all([
            loadAdminList(),
            loadManagingResources()
          ]);

          // Clear any cached data to force fresh load next time
          window.departmentsData = null;
          window.servicesData = null;
          window.rolesData = null;

          // Hide the modal
          bootstrap.Modal.getInstance(document.getElementById('editAdminModal')).hide();

        } catch (error) {
          console.error('Error updating admin:', error);
          showToast(error.message || 'Failed to update admin', 'error');
        } finally {
          saveBtn.innerHTML = originalText;
          saveBtn.disabled = false;
        }
      });

      // Initialize the page
      async function initializePage() {
        await Promise.all([
          loadDepartments(),
          loadServices(), // Make sure services are loaded
          loadRoles()
        ]);

        await Promise.all([
          loadAdminList(),
          loadManagingResources()
        ]);

        // Create checklists for add modal
        createAddDepartmentChecklist();
        createAddServiceChecklist();
      }

      initializePage();
    });
  </script>
@endsection