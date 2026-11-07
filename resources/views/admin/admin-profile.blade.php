@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/admin/global-styles.css') }}">

    <style>
        /* ============================================================
                                ADMIN PROFILE — Institutional Theme
                            ============================================================ */

        /* ── Page wrapper ── */
        .profile-page {
            padding: 0;
            min-height: 100vh;
            margin-top: -20px;
        }

/* ── Hero / Cover ── */
.profile-cover {
    position: relative;
    height: clamp(220px, 30vh, 350px);  /* Changed from 180px, 22vh, 280px */
    width: 100%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, #1a4fa8 100%);
}

        .profile-cover::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(4, 26, 75, 0.45) 100%);
            pointer-events: none;
        }

        #wallpaper-container {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .cover-change-btn {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            z-index: 5;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.45rem 0.9rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cover-change-btn:hover {
            background: rgba(255, 255, 255, 0.30);
        }

        /* ── Identity strip ── */
        .profile-identity {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0 2rem;
        }

        .identity-inner {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding-bottom: 0.5rem;
            position: relative;
        }

/* Avatar */
.avatar-wrap {
    position: relative;
    flex-shrink: 0;
    margin-top: -52px; 
    z-index: 10;
}
        .avatar-ring {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            background: var(--surface);
            margin-bottom: 0.5rem;
        }

        .avatar-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--navy);
            border: 2px solid var(--white);
            color: #fff;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .avatar-edit-btn:hover {
            background: var(--navy-mid);
            transform: scale(1.1);
        }

        /* Name / role block */
        .identity-meta {
            flex: 1;
            padding-top: 0.5rem;
            padding-bottom: 0.25rem;
        }

        .identity-name {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.15;
            margin-bottom: 0.25rem;
        }

        .identity-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--navy-light);
            color: var(--navy);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            padding: 0.25rem 0.65rem;
            border-radius: 40px;
            border: 1px solid rgba(11, 45, 114, 0.15);
        }

        .identity-role-badge i {
            font-size: 0.65rem;
        }

        /* Edit button in identity strip */
        .identity-actions {
            margin-left: auto;
            padding-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }

        .btn-edit-profile {
            background: var(--navy);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.5rem 1.25rem;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .btn-edit-profile:hover {
            background: var(--navy-mid);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Personal Details Card */
        .personal-details-card {
            margin-bottom: 1.25rem;
        }

        .btn-edit-profile-sm {
            background: var(--navy);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            padding: 0.35rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .btn-edit-profile-sm:hover {
            background: var(--navy-mid);
            transform: translateY(-1px);
        }

        .personal-details-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .personal-detail-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .personal-detail-item:nth-last-child(-n+2) {
            border-bottom: none;
        }

        .personal-detail-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }

        .personal-detail-label i {
            font-size: 0.7rem;
            color: var(--amber);
        }

        .personal-detail-value {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-base);
            word-break: break-word;
        }

        .personal-detail-value .not-set-mini {
            color: var(--text-light);
            font-style: italic;
            font-size: 0.85rem;
        }

        /* ── Main content grid ── */
        .profile-body {
            padding: 0.5rem 2rem 2.5rem;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 1.5rem;
            align-items: start;
        }

        /* ── Section cards ── */
        .profile-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .profile-card:hover {
            box-shadow: var(--shadow-md);
        }

        .profile-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .profile-card-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--navy);
            letter-spacing: -0.1px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-card-title i {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .profile-card-body {
            padding: 1.25rem;
        }

        /* ── Role card ── */
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--navy);
            color: var(--white);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.35rem 0.85rem;
            border-radius: 40px;
            margin-bottom: 0.85rem;
        }

        .role-description {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.55;
        }

        /* ── Departments card ── */
        .dept-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--navy-light);
            color: var(--navy);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.7rem;
            border-radius: 40px;
            border: 1px solid rgba(11, 45, 114, 0.15);
            margin: 0.2rem;
        }

        .dept-chip.dept-primary {
            background: var(--navy);
            color: var(--white);
            border-color: var(--navy);
        }

        .dept-chip i {
            font-size: 0.6rem;
        }

        .no-data-state {
            text-align: center;
            padding: 1.5rem 1rem;
            color: var(--text-muted);
            font-size: 0.82rem;
        }

        .no-data-state i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-light);
        }

        .no-data-state h4 {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }

        .no-data-state p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            max-width: 200px;
            margin-left: auto;
            margin-right: auto;
        }

.btn-manage-depts {
    border-radius: var(--radius-sm);
    padding: 0.35rem 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

        /* ── Amber accent rule ── */
        .accent-rule {
            display: block;
            width: 32px;
            height: 3px;
            background: var(--amber);
            border-radius: 2px;
            margin-bottom: 0.75rem;
        }

        /* ── Loading spinner ── */
        .profile-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
        }

        .spinner-ring {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--navy);
            border-radius: 50%;
            animation: spin 0.75s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Modal overrides ── */
        #editProfileModal .modal-header {
            background: var(--navy);
            border-bottom: none;
        }

        #editProfileModal .modal-title {
            color: var(--white);
            font-family: 'Fraunces', Georgia, serif;
        }

        #editProfileModal .btn-close {
            filter: invert(1) grayscale(1) brightness(2);
        }

        #editProfileModal .modal-footer {
            background: var(--surface);
        }

        .form-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid var(--border);
        }

        /* ── Department list in modal ── */
        #department-list .list-group-item {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-size: 0.85rem;
        }

        #department-list .list-group-item:hover {
            background-color: var(--navy-light);
            border-left-color: var(--navy);
        }

        #department-list .list-group-item.active {
            background-color: var(--navy-light);
            color: var(--navy);
            border-left-color: var(--navy);
        }

        #department-list .list-group-item .form-check-input {
            cursor: pointer;
            margin-top: 0;
        }

        #department-list .list-group-item .department-code {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        #department-list .list-group-item.active .department-code {
            color: var(--navy-mid);
        }

        /* ── Department badges (modal) ── */
        .department-badge {
            display: inline-flex;
            align-items: center;
            background-color: var(--navy-light);
            color: var(--navy);
            padding: 0.25rem 0.55rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            border: 1px solid rgba(11, 45, 114, 0.15);
        }

        .department-badge.primary {
            background-color: var(--navy);
            color: var(--white);
        }

        .department-badge .remove-dept {
            cursor: pointer;
            margin-left: 0.35rem;
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .department-badge .remove-dept:hover {
            opacity: 1;
            color: var(--danger);
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .profile-body {
                grid-template-columns: 1fr;
                padding: 1.25rem 1rem 2rem;
            }

            .profile-identity {
                padding: 1.5rem 2rem 1rem 2rem;
            }

            .identity-inner {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .identity-actions {
                width: 100%;
                margin-left: 0;
            }

            .identity-name {
                font-size: 1.25rem;
            }

            .personal-details-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .personal-detail-item:nth-last-child(-n+2) {
                border-bottom: 1px solid var(--border);
            }

            .personal-detail-item:last-child {
                border-bottom: none;
            }
        }
    </style>

    <main id="main">
        <div class="profile-page">

            <!-- ── Cover / Wallpaper ── -->
            <div class="profile-cover">
                <div id="wallpaper-container"
                    style="background-image: url('{{ asset('storage/defaults/wallpaper.png') }}');"></div>
                <button class="cover-change-btn" onclick="document.getElementById('wallpaper-upload').click()">
                    <i class="bi bi-image"></i> Change Cover
                </button>
            </div>
            <input type="file" id="wallpaper-upload" class="d-none" accept="image/*">

            <!-- ── Identity strip ── -->
            <div class="profile-identity">
                <div class="identity-inner">

                    <!-- Avatar -->
                    <div class="avatar-wrap">
                        <div class="avatar-ring">
                            <img id="profile-photo" src="{{ asset('storage/defaults/admin-photo.png') }}"
                                alt="Profile photo">
                        </div>
                        <button class="avatar-edit-btn" onclick="document.getElementById('photo-upload').click()"
                            title="Change photo">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <input type="file" id="photo-upload" class="d-none" accept="image/*">
                    </div>

                    <!-- Name / role -->
                    <div class="identity-meta">
                        <div id="identity-loading">
                            <div class="skeleton skeleton-name"></div>
                            <div class="skeleton skeleton-badge mt-2"></div>
                        </div>
                        <div id="identity-content" style="display:none;">
                            <div class="identity-name" id="admin-full-name"></div>
                            <span class="identity-role-badge">
                                <i class="bi bi-shield-check-fill"></i>
                                <span id="identity-role-title"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions (moved inside identity-inner) -->
                    <div class="identity-actions">
                        <button type="button" class="btn-edit-profile" id="editProfileBtn">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── Body grid ── -->
            <div class="profile-body">

                <!-- Left column -->
                <div>
                    <!-- Personal Details Card -->
                    <div class="profile-card personal-details-card">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">
                                <i class="bi bi-person-badge"></i>
                                Personal Details
                            </h5>
                            <!-- Edit button removed - only top button remains -->
                        </div>
                        <div class="profile-card-body">
                            <div id="mini-info-loading" class="profile-spinner" style="padding: 1rem;">
                                <div class="spinner-ring"></div>
                            </div>
                            <div id="mini-info-content" style="display:none;">
                                <div class="personal-details-grid">
                                    <div class="personal-detail-item">
                                        <div class="personal-detail-label">
                                            <i class="bi bi-card-list"></i>
                                            <span>School ID</span>
                                        </div>
                                        <div class="personal-detail-value" id="mini-school-id"></div>
                                    </div>
                                    <div class="personal-detail-item">
                                        <div class="personal-detail-label">
                                            <i class="bi bi-envelope"></i>
                                            <span>Email Address</span>
                                        </div>
                                        <div class="personal-detail-value" id="mini-email"></div>
                                    </div>
                                    <div class="personal-detail-item">
                                        <div class="personal-detail-label">
                                            <i class="bi bi-telephone"></i>
                                            <span>Contact Number</span>
                                        </div>
                                        <div class="personal-detail-value" id="mini-contact"></div>
                                    </div>
                                    <div class="personal-detail-item">
                                        <div class="personal-detail-label">
                                            <i class="bi bi-calendar"></i>
                                            <span>Member Since</span>
                                        </div>
                                        <div class="personal-detail-value" id="mini-member-since"></div>
                                    </div>
                                    <div class="personal-detail-item">
                                        <div class="personal-detail-label">
                                            <i class="bi bi-clock-history"></i>
                                            <span>Last Updated</span>
                                        </div>
                                        <div class="personal-detail-value" id="mini-last-updated"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Right column -->
                <div>
                    <!-- Role card -->
                    <div class="profile-card">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">
                                <i class="bi bi-shield-fill"></i>
                                Role & Access
                            </h5>
                        </div>
                        <div class="profile-card-body">
                            <div id="role-content">
                                <div class="skeleton"
                                    style="height:22px;width:90px;border-radius:40px;margin-bottom:.75rem;"></div>
                                <div class="skeleton skeleton-text" style="width:100%;margin-bottom:.4rem;"></div>
                                <div class="skeleton skeleton-text" style="width:80%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Departments card -->
                    <div class="profile-card" style="margin-top:1.25rem;">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">
                                <i class="bi bi-diagram-3-fill"></i>
                                Departments
                            </h5>
                        </div>
                        <div class="profile-card-body">
                            <div id="departments-content">
                                <div class="skeleton skeleton-text" style="width:60%;margin-bottom:.4rem;"></div>
                                <div class="skeleton skeleton-text" style="width:75%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.profile-body -->

        </div><!-- /.profile-page -->

        <!-- ══════════════════════════════════════════
                                                             Edit Profile Modal
                                                        ══════════════════════════════════════════ -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel">
                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <form id="editProfileForm">
                            <!-- Names -->
                            <div class="form-section-label">Full Name</div>
                            <div class="row g-3 mb-4">
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
                            </div>

                            <!-- Contact info -->
                            <div class="form-section-label">Contact & Credentials</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="edit-school-id" class="form-label">
                                        School ID
                                        <small class="text-muted ms-1 fw-normal">(Optional · 00-0000-00)</small>
                                    </label>
                                    <input type="text" class="form-control" id="edit-school-id" name="school_id"
                                        placeholder="00-0000-00" pattern="\d{2}-\d{4}-\d{2}" maxlength="10" minlength="10">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="edit-email" name="email"
                                        placeholder="you@example.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-contact" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="edit-contact" name="contact_number"
                                        placeholder="09XXXXXXXXX" pattern="\d{11,}" minlength="11" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-password" class="form-label">
                                        New Password
                                        <small class="text-muted ms-1 fw-normal">(Leave blank to keep current)</small>
                                    </label>
                                    <input type="password" class="form-control" id="edit-password" name="password"
                                        placeholder="New password">
                                </div>
                            </div>

                            <!-- Departments -->
                            <div class="col-12" id="departments-section-container" style="display: none;">
                                <div class="form-section-label">Department Assignment</div>
                                <small class="text-muted d-block mb-2">
                                    First selected department becomes primary.
                                </small>
                                <div class="mb-2">
                                    <input type="text" id="department-search" class="form-control form-control-sm"
                                        placeholder="Search departments…">
                                </div>
                                <div
                                    style="max-height:260px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:6px;">
                                    <div id="department-list" class="list-group list-group-flush">
                                        <div class="text-muted text-center py-3 small">Loading departments…</div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label small text-muted mb-1">Selected:</label>
                                    <div id="selected-departments-summary" class="d-flex flex-wrap gap-1">
                                        <span class="text-muted small">None selected</span>
                                    </div>
                                </div>
                                <input type="hidden" id="selected-departments" name="department_ids">
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveProfileChanges">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>

    <script>
        // ── Global state ──────────────────────────────────────────────
        let currentAdminData = null;
        let departmentsData = null;

        // ── School ID auto-formatter ──────────────────────────────────
        document.getElementById('edit-school-id').addEventListener('input', function (e) {
            let digits = e.target.value.replace(/\D/g, '');
            if (digits.length > 2 && digits.length <= 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2);
            } else if (digits.length > 6) {
                digits = digits.slice(0, 2) + '-' + digits.slice(2, 6) + '-' + digits.slice(6, 8);
            }
            e.target.value = digits;
        });

        // ── Helpers ───────────────────────────────────────────────────
        function canManageDepartments(adminData) {
            return adminData && adminData.role && [1, 2, 3].includes(adminData.role.role_id);
        }

        function getCurrentAdminId() {
            return currentAdminData ? currentAdminData.admin_id : null;
        }

        function isCurrentAdminHeadAdmin() {
            return currentAdminData && currentAdminData.role && currentAdminData.role.role_id === 1;
        }

        // ── Delete old local image ────────────────────────────────────
        async function deleteOldLocalImage(imagePath, type) {
            if (!imagePath) return true;
            const defaultPaths = ['defaults/admin-photo.png', 'defaults/wallpaper.png'];
            if (defaultPaths.some(p => imagePath.includes(p))) return true;
            try {
                const token = localStorage.getItem('adminToken');
                const response = await fetch('/api/admin/delete-local-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ image_path: imagePath, type })
                });
                if (!response.ok) return false;
                return (await response.json()).deleted;
            } catch { return false; }
        }

        // ── Load roles & departments ──────────────────────────────────
        async function loadRolesAndDepartments() {
            const token = localStorage.getItem('adminToken');
            const deptResponse = await fetch('/api/departments', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
            });
            if (!deptResponse.ok) throw new Error('Failed to fetch departments');
            const deptData = await deptResponse.json();

            if (Array.isArray(deptData)) {
                departmentsData = deptData;
            } else if (deptData.data && Array.isArray(deptData.data)) {
                departmentsData = deptData.data;
            } else if (deptData.departments && Array.isArray(deptData.departments)) {
                departmentsData = deptData.departments;
            } else {
                departmentsData = [];
            }
        }

        // ── Department selection helpers ──────────────────────────────
        function updateSelectedDepartments() {
            const selected = document.querySelectorAll('#department-list .list-group-item.active');
            document.getElementById('selected-departments').value =
                JSON.stringify(Array.from(selected).map(i => i.dataset.deptId));
            updateSelectedDepartmentsSummary();
        }

        function updateSelectedDepartmentsSummary() {
            const summary = document.getElementById('selected-departments-summary');
            const selected = document.querySelectorAll('#department-list .list-group-item.active');
            if (selected.length === 0) {
                summary.innerHTML = '<span class="text-muted small">None selected</span>';
                return;
            }
            let html = '';
            selected.forEach((item, i) => {
                const name = item.querySelector('.dept-name').textContent;
                const isPrimary = i === 0;
                html += `<span class="department-badge ${isPrimary ? 'primary' : ''}" data-dept-id="${item.dataset.deptId}">
                                                                    ${name}${isPrimary ? ' <em style="font-style:normal;opacity:.75;">(Primary)</em>' : ''}
                                                                    <span class="remove-dept" onclick="removeDepartment('${item.dataset.deptId}')">×</span>
                                                                </span>`;
            });
            summary.innerHTML = html;
        }

        window.removeDepartment = function (deptId) {
            const item = document.querySelector(`#department-list .list-group-item[data-dept-id="${deptId}"]`);
            if (item) {
                item.classList.remove('active');
                item.querySelector('.form-check-input').checked = false;
                updateSelectedDepartments();
            }
        };

        function createDepartmentButtons() {
            const deptList = document.getElementById('department-list');
            if (!deptList) { setTimeout(createDepartmentButtons, 50); return; }
            deptList.innerHTML = '';

            if (!departmentsData || departmentsData.length === 0) {
                deptList.innerHTML = '<div class="text-muted text-center py-3 small">No departments available</div>';
                return;
            }

            [...departmentsData].sort((a, b) => a.department_name.localeCompare(b.department_name))
                .forEach(dept => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action d-flex align-items-center';
                    item.dataset.deptId = dept.department_id;
                    item.innerHTML = `
                                                                        <div class="form-check me-2">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                   value="${dept.department_id}" id="dept-${dept.department_id}">
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <span class="dept-name">${dept.department_name}</span>
                                                                            <small class="department-code ms-2">${dept.department_code}</small>
                                                                        </div>`;

                    item.addEventListener('click', function (e) {
                        if (e.target.type !== 'checkbox') {
                            const cb = this.querySelector('.form-check-input');
                            cb.checked = !cb.checked;
                            this.classList.toggle('active', cb.checked);
                            updateSelectedDepartments();
                        }
                    });

                    item.querySelector('.form-check-input').addEventListener('change', function (e) {
                        e.stopPropagation();
                        item.classList.toggle('active', this.checked);
                        updateSelectedDepartments();
                    });

                    deptList.appendChild(item);
                });

            // Pre-select current departments
            if (currentAdminData?.departments?.length) {
                currentAdminData.departments.forEach(dept => {
                    const el = deptList.querySelector(`[data-dept-id="${dept.department_id}"]`);
                    if (el) {
                        el.classList.add('active');
                        el.querySelector('.form-check-input').checked = true;
                    }
                });
                updateSelectedDepartments();
            }

            // Search
            const searchInput = document.getElementById('department-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = this.value.toLowerCase();
                    deptList.querySelectorAll('.list-group-item').forEach(item => {
                        const name = item.querySelector('.dept-name').textContent.toLowerCase();
                        const code = item.querySelector('.department-code').textContent.toLowerCase();
                        item.style.display = (name.includes(q) || code.includes(q)) ? 'flex' : 'none';
                    });
                });
            }

            // Auto-select all for roles 1 & 2
            const roleSelect = document.getElementById('edit-role');
            if (roleSelect) {
                roleSelect.replaceWith(roleSelect.cloneNode(true));
                document.getElementById('edit-role')?.addEventListener('change', function () {
                    if ([1, 2].includes(parseInt(this.value))) {
                        deptList.querySelectorAll('.list-group-item').forEach(item => {
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

        // ── Upload helper ─────────────────────────────────────────────
        async function uploadLocalImage(file, type) {
            const token = localStorage.getItem('adminToken');
            const formData = new FormData();
            formData.append(type, file);
            formData.append('type', type);

            const response = await fetch('/api/admin/update-photo', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });
            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.message || `Failed to upload ${type}`);
            }
            return response.json();
        }

        // ══════════════════════════════════════════════════════════════
        //  DOM READY
        // ══════════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', function () {
            const token = localStorage.getItem('adminToken');
            if (!token) { window.location.href = '/admin/admin-login'; return; }

            // ── Load profile ──────────────────────────────────────────
            fetch('/api/admin/profile', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                credentials: 'include'
            })
                .then(r => { if (!r.ok) throw new Error('Failed to fetch profile data'); return r.json(); })
                .then(data => {
                    currentAdminData = data;

                    // Photo
                    if (data.photo_url) document.getElementById('profile-photo').src = data.photo_url;

                    // Wallpaper
                    if (data.wallpaper_url) {
                        const wc = document.getElementById('wallpaper-container');
                        wc.style.backgroundImage = `url(${data.wallpaper_url})`;
                        wc.style.backgroundSize = 'cover';
                        wc.style.backgroundPosition = 'center';
                    }

                    // Identity strip
                    const fullName = `${data.first_name} ${data.last_name}`;
                    document.getElementById('admin-full-name').textContent = fullName;
                    document.getElementById('identity-role-title').textContent =
                        data.role ? data.role.role_title : 'Administrator';
                    document.getElementById('identity-loading').style.display = 'none';
                    document.getElementById('identity-content').style.display = 'block';

                    // Populate mini detailed personal info card (replaces old personal info card)
                    document.getElementById('mini-school-id').innerHTML = data.school_id
                        ? data.school_id
                        : '<span class="not-set-mini">Not set</span>';
                    document.getElementById('mini-email').innerHTML = data.email || '<span class="not-set-mini">Not set</span>';
                    document.getElementById('mini-contact').innerHTML = data.contact_number
                        ? data.contact_number
                        : '<span class="not-set-mini">Not set</span>';
                    document.getElementById('mini-member-since').innerHTML = data.created_at
                        ? new Date(data.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
                        : '<span class="not-set-mini">Not set</span>';
                    document.getElementById('mini-last-updated').innerHTML = data.updated_at
                        ? new Date(data.updated_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
                        : '<span class="not-set-mini">Not set</span>';

                    // Show mini info content and hide skeleton
                    document.getElementById('mini-info-loading').style.display = 'none';
                    document.getElementById('mini-info-content').style.display = 'block';

                    // Role card
                    if (data.role) {
                        document.getElementById('role-content').innerHTML = `
                                        <span class="accent-rule"></span>
                                        <span class="role-pill">
                                            <i class="bi bi-shield-fill-check"></i>
                                            ${data.role.role_title}
                                        </span>
                                        <p class="role-description">${data.role.description}</p>`;
                    }

// Departments card
if (data.departments && data.departments.length > 0) {
    const chips = data.departments.map(dept => {
        const isPrimary = dept.pivot?.is_primary;
        return `<span class="dept-chip ${isPrimary ? 'dept-primary' : ''}">
                    ${isPrimary ? '<i class="bi bi-star-fill"></i>' : ''}
                    ${dept.department_name}
                </span>`;
    }).join('');
    document.getElementById('departments-content').innerHTML = chips;
} else {
    document.getElementById('departments-content').innerHTML = `
        <div class="no-data-state">
            <i class="bi bi-diagram-3"></i>
            <h4>No departments assigned</h4>
            <p>Assign this administrator to a specific operational unit to track activity.</p>
            <button class="btn-manage-depts btn btn-outline-secondary" id="manageDeptsBtn">
                Manage Departments
            </button>
        </div>`;
}
                })
                .catch(err => {
                    console.error('Error fetching profile:', err);
                    // Show error in the role card instead since main-info-loading is gone
                    const roleContent = document.getElementById('role-content');
                    if (roleContent) {
                        roleContent.innerHTML = '<div class="text-danger text-center py-3 small"><i class="bi bi-exclamation-triangle me-1"></i>Error loading profile data</div>';
                    }
                });

            // Handle Manage Departments button click (delegated event since button is dynamically created)
            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'manageDeptsBtn') {
                    e.preventDefault();
                    // Trigger the edit profile button click which opens the modal with departments
                    const editBtn = document.getElementById('editProfileBtn');
                    if (editBtn) {
                        editBtn.click();
                    }
                }
            });

            // ── Edit profile buttons (both the original and the new card button) ──
            function openEditProfileModal() {
                const modalEl = document.getElementById('editProfileModal');
                const modal = new bootstrap.Modal(modalEl);

                loadRolesAndDepartments().then(() => {
                    if (currentAdminData) {
                        document.getElementById('edit-first-name').value = currentAdminData.first_name || '';
                        document.getElementById('edit-last-name').value = currentAdminData.last_name || '';
                        document.getElementById('edit-middle-name').value = currentAdminData.middle_name || '';
                        document.getElementById('edit-school-id').value = currentAdminData.school_id || '';
                        document.getElementById('edit-email').value = currentAdminData.email || '';
                        document.getElementById('edit-contact').value = currentAdminData.contact_number || '';

                        setTimeout(() => {
                            const rs = document.getElementById('edit-role');
                            if (rs && currentAdminData.role_id) rs.value = currentAdminData.role_id;
                        }, 100);
                    }

                    modalEl.addEventListener('shown.bs.modal', function onShow() {
                        const canManage = canManageDepartments(currentAdminData);
                        const ds = document.getElementById('departments-section-container');
                        if (ds) ds.style.display = canManage ? 'block' : 'none';
                        if (canManage) createDepartmentButtons();
                        modalEl.removeEventListener('shown.bs.modal', onShow);
                    });

                    modal.show();
                }).catch(err => {
                    console.error('Error loading modal data:', err);
                    showToast('Failed to load edit form data', 'error', 3000);
                });
            }

            // Attach event listener only to the top edit button
            const topEditBtn = document.getElementById('editProfileBtn');
            if (topEditBtn) {
                topEditBtn.addEventListener('click', openEditProfileModal);
            }

            // ── Save profile ──────────────────────────────────────────
            document.getElementById('saveProfileChanges').addEventListener('click', async function () {
                const token = localStorage.getItem('adminToken');
                const canManage = canManageDepartments(currentAdminData);
                const schoolId = document.getElementById('edit-school-id').value.trim();

                if (schoolId && !/^\d{2}-\d{4}-\d{2}$/.test(schoolId)) {
                    showToast('School ID must follow the format ##-####-##', 'error', 4000);
                    return;
                }

                let selectedDepts = [];
                if (canManage) {
                    selectedDepts = JSON.parse(document.getElementById('selected-departments').value || '[]');
                }

                if (canManage) {
                    const noDeptsNeeded = [1, 2];
                    if (selectedDepts.length === 0 && !noDeptsNeeded.includes(currentAdminData.role.role_id)) {
                        showToast('Please select at least one department', 'error', 3000);
                        return;
                    }
                }

                const jsonData = {
                    first_name: document.getElementById('edit-first-name').value,
                    last_name: document.getElementById('edit-last-name').value,
                    middle_name: document.getElementById('edit-middle-name').value,
                    school_id: schoolId || null,
                    email: document.getElementById('edit-email').value,
                    contact_number: document.getElementById('edit-contact').value,
                    role_id: currentAdminData.role.role_id
                };
                if (canManage) jsonData.department_ids = selectedDepts;

                const pw = document.getElementById('edit-password').value;
                if (pw) jsonData.password = pw;

                try {
                    const response = await fetch(`/api/admin/update/${currentAdminData.admin_id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(jsonData)
                    });

                    if (!response.ok) {
                        const err = await response.json();
                        throw new Error(err.message || 'Failed to update profile');
                    }

                    showToast('Profile updated successfully!', 'success', 3000);
                    bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } catch (err) {
                    console.error('Error updating profile:', err);
                    showToast('Failed to update profile: ' + err.message, 'error', 4000);
                }
            });

            // ── Photo upload ──────────────────────────────────────────
            document.getElementById('photo-upload').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                try {
                    document.getElementById('profile-photo').style.opacity = '0.5';
                    const result = await uploadLocalImage(file, 'photo');
                    document.getElementById('profile-photo').src = result.photo_url;
                    document.getElementById('profile-photo').style.opacity = '1';
                    currentAdminData.photo_url = result.photo_url;
                    showToast('Profile photo updated!', 'success', 2000);
                    setTimeout(() => location.reload(), 2000);
                } catch (err) {
                    console.error('Photo upload error:', err);
                    showToast('Failed to upload photo: ' + err.message, 'error', 4000);
                } finally {
                    e.target.value = '';
                }
            });

            // ── Wallpaper upload ──────────────────────────────────────
            document.getElementById('wallpaper-upload').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const wc = document.getElementById('wallpaper-container');
                try {
                    wc.style.opacity = '0.5';
                    const result = await uploadLocalImage(file, 'wallpaper');
                    wc.style.backgroundImage = `url(${result.wallpaper_url})`;
                    wc.style.backgroundSize = 'cover';
                    wc.style.backgroundPosition = 'center';
                    wc.style.opacity = '1';
                    currentAdminData.wallpaper_url = result.wallpaper_url;
                    showToast('Cover photo updated!', 'success', 2000);
                    setTimeout(() => location.reload(), 2000);
                } catch (err) {
                    console.error('Wallpaper upload error:', err);
                    showToast('Failed to upload cover: ' + err.message, 'error', 4000);
                } finally {
                    e.target.value = '';
                }
            });
        });
    </script>
@endsection