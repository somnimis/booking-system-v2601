@php
    // Signal that profile is already cached/loaded
    $skipProfileFetch = true;
@endphp
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

    .stat-content {
      flex: 1;
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
      /* Reduced padding */
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

    /* Additional component-specific styles that complement global-styles.css */
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

    /* Department card styles - matching dashboard pattern */
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

    /* Service & Purpose card styles */
    .service-card,
    .purpose-card {
      transition: var(--transition);
    }

    .service-card:hover,
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

    .badge-manager {
      background-color: #f5f6fa;
      color: #4a5568;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
    }

    /* Loading spinner */
    .loading-container {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 200px;
    }

    /* Summary cards - matching dashboard stat-card style */
    .summary-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 1.25rem 1.4rem;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }

    .summary-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 3px;
      height: 100%;
      background: var(--navy);
      border-radius: var(--radius-sm) 0 0 var(--radius-sm);
      opacity: 0;
      transition: var(--transition);
    }

    .summary-card:hover {
      transform: translateY(-3px);
      border-color: rgba(11, 45, 114, 0.2);
    }

    .summary-card:hover::before {
      opacity: 1;
    }

    /* Tab styling - matching dashboard */
    .nav-tabs .nav-link {
      color: var(--text-muted);
      font-weight: 500;
      border: none;
      border-bottom: 3px solid transparent;
      padding: 0.75rem 1.25rem;
      font-size: 0.875rem;
    }

    .nav-tabs .nav-link:hover {
      border-bottom-color: var(--border);
      color: var(--navy);
    }

    .nav-tabs .nav-link.active {
      color: var(--navy);
      border-bottom-color: var(--navy);
      background: none;
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

    /* Icon colors */
    .section-title i,
    .stat-card i,
    .summary-card i {
      color: var(--navy);
    }
  </style>

  <main id="main">
    <div class="container-fluid px-4">

      <!-- Dashboard Cards Summary - Always visible with skeleton loading -->
      <div class="row g-3 mb-4" id="summaryCards">
        <div class="col-md-3 col-6">
          <div class="summary-card" onclick="document.querySelector('#admins-tab').click()">
            <div class="d-flex justify-content-between align-items-start">
              <div class="stat-content" id="statAdminsContent">
                <div class="skeleton-wrapper">
                  <div class="skeleton-line" style="width: 50px; height: 32px; margin-bottom: 8px;"></div>
                  <div class="skeleton-line" style="width: 80px; height: 14px;"></div>
                </div>
                <div class="stat-value" id="totalAdmins"
                  style="font-family: 'Fraunces', Georgia, serif; font-size: 2rem; font-weight: 700; color: var(--navy); line-height: 1; display: none;">
                </div>
                <div class="stat-label"
                  style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; display: none;" id="adminsLabel">
                  Total Admins</div>
              </div>
              <i class="bi bi-people" style="font-size: 1.5rem; color: var(--navy); opacity: 0.3;"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="summary-card" onclick="document.querySelector('#departments-tab').click()">
            <div class="d-flex justify-content-between align-items-start">
              <div class="stat-content" id="statDepartmentsContent">
                <div class="skeleton-wrapper">
                  <div class="skeleton-line" style="width: 50px; height: 32px; margin-bottom: 8px;"></div>
                  <div class="skeleton-line" style="width: 80px; height: 14px;"></div>
                </div>
                <div class="stat-value" id="totalDepartments"
                  style="font-family: 'Fraunces', Georgia, serif; font-size: 2rem; font-weight: 700; color: var(--navy); line-height: 1; display: none;">
                </div>
                <div class="stat-label"
                  style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; display: none;">Departments</div>
              </div>
              <i class="bi bi-building" style="font-size: 1.5rem; color: var(--navy); opacity: 0.3;"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="summary-card" onclick="document.querySelector('#services-tab').click()">
            <div class="d-flex justify-content-between align-items-start">
              <div class="stat-content" id="statServicesContent">
                <div class="skeleton-wrapper">
                  <div class="skeleton-line" style="width: 50px; height: 32px; margin-bottom: 8px;"></div>
                  <div class="skeleton-line" style="width: 80px; height: 14px;"></div>
                </div>
                <div class="stat-value" id="totalServices"
                  style="font-family: 'Fraunces', Georgia, serif; font-size: 2rem; font-weight: 700; color: var(--navy); line-height: 1; display: none;">
                </div>
                <div class="stat-label"
                  style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; display: none;">Extra Services
                </div>
              </div>
              <i class="bi bi-grid" style="font-size: 1.5rem; color: var(--navy); opacity: 0.3;"></i>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="summary-card" onclick="document.querySelector('#purposes-tab').click()">
            <div class="d-flex justify-content-between align-items-start">
              <div class="stat-content" id="statPurposesContent">
                <div class="skeleton-wrapper">
                  <div class="skeleton-line" style="width: 50px; height: 32px; margin-bottom: 8px;"></div>
                  <div class="skeleton-line" style="width: 80px; height: 14px;"></div>
                </div>
                <div class="stat-value" id="totalPurposes"
                  style="font-family: 'Fraunces', Georgia, serif; font-size: 2rem; font-weight: 700; color: var(--navy); line-height: 1; display: none;">
                </div>
                <div class="stat-label"
                  style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; display: none;">Purposes</div>
              </div>
              <i class="bi bi-tag" style="font-size: 1.5rem; color: var(--navy); opacity: 0.3;"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation with Add New Dropdown -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <ul class="nav nav-tabs mb-0" id="dashboardTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button"
              role="tab">
              <i class="bi bi-people me-2"></i>Administrators
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="departments-tab" data-bs-toggle="tab" data-bs-target="#departments" type="button"
              role="tab">
              <i class="bi bi-building me-2"></i>Departments
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button"
              role="tab">
              <i class="bi bi-grid me-2"></i>Services
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="purposes-tab" data-bs-toggle="tab" data-bs-target="#purposes" type="button"
              role="tab">
              <i class="bi bi-tag me-2"></i>Purposes
            </button>
          </li>
        </ul>

        <div class="dropdown">
          <button class="btn btn-primary dropdown-toggle" type="button" id="addNewDropdown" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class="bi bi-plus-circle me-2"></i>Add New
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="addNewDropdown">
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="bi bi-people me-2"></i>Add New Admin
              </a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="#" onclick="alert('Add Department functionality coming soon')">
                <i class="bi bi-building me-2"></i>Add Department
              </a></li>
            <li><a class="dropdown-item" href="#" onclick="alert('Add Service functionality coming soon')">
                <i class="bi bi-grid me-2"></i>Add Service
              </a></li>
            <li><a class="dropdown-item" href="#" onclick="alert('Add Purpose functionality coming soon')">
                <i class="bi bi-tag me-2"></i>Add Purpose
              </a></li>
          </ul>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content" id="dashboardTabContent">

        <!-- Admins Tab -->
        <div class="tab-pane fade show active" id="admins" role="tabpanel">
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

        <!-- Departments Tab -->
        <div class="tab-pane fade" id="departments" role="tabpanel">
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

        <!-- Services Tab -->
        <div class="tab-pane fade" id="services" role="tabpanel">
          <div class="section-card">
            <div class="section-body">
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

        <!-- Purposes Tab -->
        <div class="tab-pane fade" id="purposes" role="tabpanel">
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
// Track which tabs have been loaded
let loadedTabs = {
    admins: false,
    departments: false,
    services: false,
    purposes: false
};

let currentAdminPage = 1;
const itemsPerPage = 10;

// Cache data for each tab
let adminsData = [];
let departmentsData = [];
let servicesData = [];
let purposesData = [];
let departmentsList = []; // For checkboxes
let rolesList = []; // For dropdowns

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('adminToken') || localStorage.getItem('token');
    
    if (!token) {
        console.error('No authentication token found');
        if (typeof showToast === 'function') {
            showToast('Authentication error. Please login again.', 'error');
        }
        return;
    }
    
    // Load ALL static data in ONE API call
    loadStaticData();
    
    // Set up tab click handlers for lazy rendering (data already loaded)
    const deptTab = document.getElementById('departments-tab');
    const servicesTab = document.getElementById('services-tab');
    const purposesTab = document.getElementById('purposes-tab');
    
    if (deptTab) {
        deptTab.addEventListener('shown.bs.tab', function() {
            if (!loadedTabs.departments && departmentsData.length > 0) {
                loadedTabs.departments = true;
                renderDepartments();
            }
        });
    }
    
    if (servicesTab) {
        servicesTab.addEventListener('shown.bs.tab', function() {
            if (!loadedTabs.services && servicesData.length > 0) {
                loadedTabs.services = true;
                renderServices();
            }
        });
    }
    
    if (purposesTab) {
        purposesTab.addEventListener('shown.bs.tab', function() {
            if (!loadedTabs.purposes && purposesData.length > 0) {
                loadedTabs.purposes = true;
                renderPurposes();
            }
        });
    }
    
    // Load ALL static data in ONE API call
    async function loadStaticData() {
        try {
            // ONE API call for all static data
            const response = await fetch('/api/manage/static-data', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                
                // Store data
                departmentsData = data.departments || [];
                servicesData = data.services || [];
                purposesData = data.purposes || [];
                departmentsList = data.departments_list || [];
                rolesList = data.roles || [];
                
                // Update stats counts
                updateStatCounts();
                
                // Populate role dropdowns for modals
                populateRoleDropdowns();
                
                // Populate add modal form with cached data
                populateAddModalFormData();
                
                // Now load paginated admins (separate call)
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
    
    function updateStatCounts() {
        const totalAdminsEl = document.getElementById('totalAdmins');
        const totalDepartmentsEl = document.getElementById('totalDepartments');
        const totalServicesEl = document.getElementById('totalServices');
        const totalPurposesEl = document.getElementById('totalPurposes');
        
        // For departments count - use departments_list length
        if (totalDepartmentsEl) {
            totalDepartmentsEl.innerHTML = departmentsList.length;
            const deptContent = document.getElementById('statDepartmentsContent');
            if (deptContent) {
                deptContent.querySelector('.skeleton-wrapper').style.display = 'none';
                totalDepartmentsEl.style.display = 'block';
                const label = deptContent.querySelector('.stat-label');
                if (label) label.style.display = 'block';
            }
        }
        
        if (totalServicesEl) {
            totalServicesEl.innerHTML = servicesData.length;
            const serviceContent = document.getElementById('statServicesContent');
            if (serviceContent) {
                serviceContent.querySelector('.skeleton-wrapper').style.display = 'none';
                totalServicesEl.style.display = 'block';
                const label = serviceContent.querySelector('.stat-label');
                if (label) label.style.display = 'block';
            }
        }
        
        if (totalPurposesEl) {
            totalPurposesEl.innerHTML = purposesData.length;
            const purposeContent = document.getElementById('statPurposesContent');
            if (purposeContent) {
                purposeContent.querySelector('.skeleton-wrapper').style.display = 'none';
                totalPurposesEl.style.display = 'block';
                const label = purposeContent.querySelector('.stat-label');
                if (label) label.style.display = 'block';
            }
        }
        
        // Make icons fully visible
        const icons = document.querySelectorAll('.summary-card i');
        icons.forEach(icon => {
            icon.style.opacity = '1';
        });
    }
    
    function showErrorState() {
        const statContents = document.querySelectorAll('.stat-content');
        statContents.forEach(content => {
            const skeleton = content.querySelector('.skeleton-wrapper');
            if (skeleton) skeleton.style.display = 'none';
            const value = content.querySelector('.stat-value');
            if (value) {
                value.style.display = 'block';
                if (value.id === 'totalAdmins') value.innerHTML = '!';
                else value.innerHTML = '0';
            }
            const label = content.querySelector('.stat-label');
            if (label) label.style.display = 'block';
        });
        
        const icons = document.querySelectorAll('.summary-card i');
        icons.forEach(icon => {
            icon.style.opacity = '1';
        });
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
        
        const serviceContainer = document.getElementById('addServiceChecklist');
        if (serviceContainer && servicesData.length > 0) {
            serviceContainer.innerHTML = '';
            servicesData.forEach(service => {
                const serviceId = service.service_id;
                const serviceName = service.service_name;
                serviceContainer.innerHTML += `
                    <div class="form-check">
                        <input class="form-check-input add-service-cb" type="checkbox" value="${serviceId}" id="service_${serviceId}">
                        <label class="form-check-label" for="service_${serviceId}">${serviceName}</label>
                    </div>
                `;
            });
        }
        
        // Add event listeners
        document.querySelectorAll('.add-dept-cb').forEach(cb => {
            cb.addEventListener('change', updateAddDeptPreview);
        });
        document.querySelectorAll('.add-service-cb').forEach(cb => {
            cb.addEventListener('change', updateAddServicePreview);
        });
    }
    
    // Load Admins Tab with pagination
    async function loadAdminsTab(page = 1) {
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
            
            // Update admin count in stats
            const totalAdminsEl = document.getElementById('totalAdmins');
            if (totalAdminsEl) {
                totalAdminsEl.innerHTML = window.adminPagination.total;
                const adminsContent = document.getElementById('statAdminsContent');
                if (adminsContent) {
                    adminsContent.querySelector('.skeleton-wrapper').style.display = 'none';
                    totalAdminsEl.style.display = 'block';
                    const label = adminsContent.querySelector('.stat-label');
                    if (label) label.style.display = 'block';
                }
            }
            
            loadedTabs.admins = true;
            renderAdminList();
            if (loadingEl) loadingEl.style.display = 'none';
            if (tableWrapper) tableWrapper.style.display = 'block';
            
        } catch (error) {
            console.error('Error loading admins:', error);
            if (loadingEl) loadingEl.innerHTML = '<div class="alert alert-danger">Failed to load administrators</div>';
        }
    }
    
    // Format admin ID with leading zeros
    function formatAdminId(id) {
        return String(id).padStart(4, '0');
    }

    // Drag to scroll functionality for admin table
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
    
    // Render admin list with pagination
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
    
    // Render departments
    function renderDepartments() {
        const grid = document.getElementById('departmentsGrid');
        if (!grid) return;
        
        // Show loading, then content
        const loadingEl = document.getElementById('deptLoading');
        const contentEl = document.getElementById('departmentsContent');
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
        
        grid.innerHTML = '';
        
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
                        <div class="card-footer bg-white border-top">
                            <small class="text-muted">Total: ${adminCount} admin(s)</small>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', card);
        });
    }
    
    // Render services
    function renderServices() {
        const grid = document.getElementById('servicesGrid');
        if (!grid) return;
        
        const loadingEl = document.getElementById('servicesLoading');
        const contentEl = document.getElementById('servicesContent');
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
        
        grid.innerHTML = '';
        
        servicesData.forEach(service => {
            const managerName = service.manager ? (service.manager.full_name || service.manager) : 'Not assigned';
            const card = `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card service-card h-100 border shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="card-title mb-0 fw-semibold" style="font-family: 'Fraunces', Georgia, serif;">
                                    <i class="bi bi-grid me-2" style="color: var(--navy);"></i>
                                    ${service.service_name}
                                </h6>
                            </div>
                            ${service.service_fee ? `
                            <div class="mb-2">
                                <small class="text-muted">Fee:</small>
                                <span class="fw-bold">₱${parseFloat(service.service_fee).toLocaleString()}</span>
                            </div>
                            ` : ''}
                            <div>
                                <small class="text-muted">Managing Administrator:</small>
                                <div class="badge-manager mt-1 d-inline-block">
                                    <i class="bi bi-person-badge me-1"></i>
                                    ${managerName}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', card);
        });
    }
    
    // Render purposes
    function renderPurposes() {
        const grid = document.getElementById('purposesGrid');
        if (!grid) return;
        
        const loadingEl = document.getElementById('purposesLoading');
        const contentEl = document.getElementById('purposesContent');
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
        
        grid.innerHTML = '';
        
        purposesData.forEach(purpose => {
            const discountDisplay = purpose.discount_fee ? 
                `${purpose.discount_type === 'percentage' ? purpose.discount_fee + '%' : '₱' + parseFloat(purpose.discount_fee).toLocaleString()}` : 
                'No discount';
            const routedTo = purpose.routed_admin ? (purpose.routed_admin.full_name || purpose.routed_admin) : 'Not assigned';
            
            const card = `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card purpose-card h-100 border shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="card-title mb-0 fw-semibold" style="font-family: 'Fraunces', Georgia, serif;">
                                    ${purpose.purpose_name}
                                </h6>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Discount:</small>
                                <span class="badge bg-info ms-2" style="background-color: var(--navy-light) !important; color: var(--navy);">${discountDisplay}</span>
                            </div>
                            <div>
                                <small class="text-muted">Routes To:</small>
                                <div class="badge-routed mt-1 d-inline-block" style="background-color: var(--navy-light) !important; color: var(--navy);">
                                    <i class="bi bi-share me-1"></i>
                                    ${routedTo}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', card);
        });
    }
    
    // Pagination function
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
    
    // Edit admin function
    window.editAdmin = async function(adminId) {
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
    
    // Delete admin function
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
                    
                    // Refresh data - reload current page
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
                    
                    // Refresh data - reload current page
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
                    
                    // Reset selected departments/services preview
                    document.getElementById('addSelectedDeptIds').value = '[]';
                    document.getElementById('addSelectedServiceIds').value = '[]';
                    document.getElementById('addSelectedDeptPreview').innerHTML = '<div class="text-muted">No departments selected</div>';
                    document.getElementById('addSelectedServicePreview').innerHTML = '<div class="text-muted">No services selected</div>';
                    document.querySelectorAll('.add-dept-cb').forEach(cb => cb.checked = false);
                    document.querySelectorAll('.add-service-cb').forEach(cb => cb.checked = false);
                    
                    // Refresh data - go to first page
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
});
</script>
@endsection