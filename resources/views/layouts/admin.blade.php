<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CPU Booking')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style>
        /* ============================================
           REFINED INSTITUTIONAL THEME - ADMIN LAYOUT
           Matching catalog.css design system
           ============================================ */

        :root {
            --navy: #0b2d72;
            --navy-mid: #0d368a;
            --navy-light: #e8edf8;
            --amber: #f5bc40;
            --amber-dark: #d9a12a;
            --white: #ffffff;
            --surface: #f5f6fa;
            --border: #e2e6f0;
            --text-base: #1e2d4a;
            --text-muted: #6b7a99;
            --text-light: #9aaac5;
            --success: #22c55e;
            --danger: #ef4444;
            --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
            --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
            --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);

            /* Legacy support */
            --cpu-primary: #041a4b;
            --btn-primary: #041a4b;
            --btn-primary-hover: #0b2d72;
        }

        /* Add to your existing style section, after the .sidebar-nav .nav-link:hover rules */

        .sidebar-nav .nav-link {
            transform: translateX(0);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-nav .nav-link:hover {
            transform: translateX(6px);
            background: var(--navy-light);
            color: var(--navy);
        }

        .sidebar-nav .nav-link:hover i {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        .sidebar-nav .nav-link.active {
            transform: translateX(0);
            background: var(--navy);
            color: var(--white);
        }

        .sidebar-nav .nav-link.active i {
            transform: scale(1);
        }

        /* Override Bootstrap primary color */
        .btn-primary {
            --bs-btn-bg: var(--navy) !important;
            --bs-btn-border-color: var(--navy) !important;
            --bs-btn-hover-bg: var(--navy-mid) !important;
            --bs-btn-hover-border-color: var(--navy-mid) !important;
            --bs-btn-active-bg: var(--navy-mid) !important;
            --bs-btn-active-border-color: var(--navy-mid) !important;
            --bs-btn-disabled-bg: var(--navy) !important;
            --bs-btn-disabled-border-color: var(--navy) !important;
        }

        .btn-outline-primary {
            --bs-btn-color: var(--navy) !important;
            --bs-btn-border-color: var(--navy) !important;
            --bs-btn-hover-bg: var(--navy) !important;
            --bs-btn-hover-border-color: var(--navy) !important;
            --bs-btn-active-bg: var(--navy) !important;
            --bs-btn-active-border-color: var(--navy) !important;
            --bs-btn-disabled-color: var(--navy) !important;
            --bs-btn-disabled-border-color: var(--navy) !important;
        }

        /* Primary text color */
        .text-primary {
            color: var(--navy) !important;
        }

        /* Primary background */
        .bg-primary {
            background-color: var(--navy) !important;
            color: var(--white) !important;
        }

        /* Border primary */
        .border-primary {
            border-color: var(--navy) !important;
        }

        /* Dropdown header primary color */
        .dropdown-header {
            color: var(--navy);
        }

        /* Link primary color */
        a:not(.btn):not(.nav-link):not(.dropdown-item) {
            color: var(--navy);
        }

        a:not(.btn):not(.nav-link):not(.dropdown-item):hover {
            color: var(--navy-mid);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--surface) 0%, #f0f2f8 100%);
            min-height: 100vh;
            margin: 0;
            color: var(--text-base);
            overflow-x: hidden;
        }

        /* Smooth transitions */
        .transition-all {
            transition: var(--transition);
        }

        /* Sidebar Styles */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background: var(--white);
            border-right: 1px solid var(--border);
            padding: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1030;
            box-shadow: var(--shadow-md);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: var(--text-light);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--text-muted);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: var(--text-light) transparent;
        }

        /* Sidebar Profile Section */
        .sidebar-profile {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--navy-light) 0%, var(--white) 100%);
        }

        .profile-img-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .profile-img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50% !important;
            border: 3px solid var(--white);
            box-shadow: var(--shadow-sm);
        }

        .status-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 14px;
            height: 14px;
            background: var(--success);
            border: 2px solid var(--white);
            border-radius: 50%;
        }

        .admin-name {
            font-family: 'Fraunces', serif;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .admin-name a {
            color: var(--navy);
            text-decoration: none;
            transition: var(--transition);
        }

        .admin-name a:hover {
            color: var(--amber);
        }

        .admin-role {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 1rem 0.75rem;
        }

        .nav-section-title {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            padding: 0.75rem 0.75rem 0.5rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 0.875rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: var(--radius-md);
            transition: var(--transition);
            margin-bottom: 2px;
        }

        .sidebar-nav .nav-link i {
            width: 20px;
            font-size: 1rem;
            color: var(--text-light);
            transition: var(--transition);
        }

        .sidebar-nav .nav-link:hover {
            background: var(--navy-light);
            color: var(--navy);
        }

        .sidebar-nav .nav-link:hover i {
            color: var(--navy);
        }

        .sidebar-nav .nav-link.active {
            background: var(--navy);
            color: var(--white);
        }

        .sidebar-nav .nav-link.active i {
            color: var(--white);
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem;
            text-align: center;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .sidebar-footer small {
            font-size: 0.7rem;
            color: var(--text-light);
        }

        /* Topbar Styles */
        #topbar {
            background: var(--navy-mid) !important;
            border-bottom: none;
            padding: 0.75rem 1.5rem;
            height: 64px;
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            width: calc(100% - 280px);
            box-shadow: var(--shadow-md);
            z-index: 1020;
            transition: var(--transition);
        }

        #topbar.topbar-hidden {
            transform: translateY(-100%);
        }

        .brand-text {
            font-family: 'Fraunces', serif;
            font-size: 1rem;
            font-weight: 600;
            color: var(--white);
        }

        /* Topbar Buttons */
        .topbar-icon-btn {
            background: transparent;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--white);
            font-size: 1.2rem;
        }

        .topbar-icon-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50% !important;
            transition: var(--transition);
            color: var(--white) !important;
            text-decoration: none;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white) !important;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--amber);
            color: var(--navy);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dropdown Menus */
        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 0.5rem;
            margin-top: 0.5rem;
        }

        .dropdown-item {
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--navy-light);
            color: var(--navy);
        }

        .dropdown-item.text-danger:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .dropdown-header {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 0.5rem 1rem;
        }

        /* Notification List */
        #notificationList {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 0.25rem;
        }

        .notification-item:hover {
            background: var(--navy-light);
        }

        .notification-item.unread {
            background: var(--navy-light);
            border-left: 3px solid var(--navy);
        }

        /* Main content area */
        main {
            margin-left: 95px;
            margin-top: 10px;
            padding: 2rem;
            width: calc(100% - 52px);
            transition: all 0.3s ease;
            min-height: calc(100vh - 70px);
        }

        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            background-size: 200% 100%;
            animation: shine 1.5s linear infinite;
        }

        @keyframes shine {
            to {
                background-position-x: -200%;
            }
        }

        .skeleton-circle {
            border-radius: 50%;
        }

        .skeleton-text {
            height: 1em;
            border-radius: var(--radius-sm);
        }

        /* Mobile responsive */
        @media (max-width: 991.98px) {
            main {
                margin-left: 0 !important;
                margin-top: 64px !important;
                width: 100% !important;
                padding: 1rem;
            }

            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.active {
                transform: translateX(0);
            }

            #topbar {
                margin-left: 0;
                width: 100%;
                padding-left: 20px !important;
            }

            /* If you want more specific control, target just the left side */
            #topbar .d-flex.align-items-center.gap-2 {
                margin-left: 40px;
                /* Adjust this value as needed */
            }

            .sidebar-toggle {
                display: block;
                position: fixed;
                left: 1rem;
                top: 0.75rem;
                z-index: 1031;
                padding: 0.25rem 0.75rem;
                font-size: 1.25rem;
                background: transparent;
                border: none;
                color: white !important;
                /* Make it white for visibility */
            }

            .sidebar-toggle:hover {
                background: rgba(255, 255, 255, 0.1);
            }

            .profile-img {
                width: 80px !important;
                height: 80px !important;
            }
        }

        @media (min-width: 992px) {
            .sidebar-toggle {
                display: none;
            }
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        /* Button Styles */
        .btn {
            border-radius: var(--radius-sm) !important;
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-primary:hover {
            background: var(--navy-mid);
            border-color: var(--navy-mid);
        }

        .btn-outline-primary {
            border-color: var(--navy);
            color: var(--navy);
        }

        .btn-outline-primary:hover {
            background: var(--navy);
            border-color: var(--navy);
        }

        /* Table Styles */
        .table {
            color: var(--text-base);
        }

        .table th {
            background: var(--surface);
            font-weight: 600;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border);
        }

        /* Badge Styles */
        .badge {
            border-radius: 60px;
            padding: 0.25rem 0.6rem;
            font-weight: 500;
        }

        /* Form Controls */
        .form-control,
        .form-select {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }
    </style>
</head>

<body>
    <button class="sidebar-toggle" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    {{-- Topbar --}}
    <header id="topbar" class="d-flex justify-content-between align-items-center transition-all">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ url()->previous() }}" class="back-button">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="brand-text">
                @yield('title', 'CPU Facilities and Equipment Management')
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Notification Bell -->
            <div class="dropdown">
                <button class="topbar-icon-btn" id="notificationDropdownButton" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="fa-regular fa-bell"></i>
                    <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="notificationDropdown" style="width: 350px;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>Notifications</span>
                        <button class="btn btn-sm btn-link p-0 text-primary" id="markAllAsRead">Mark all as
                            read</button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <div id="notificationList">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2 text-muted">Loading notifications...</span>
                        </div>
                    </div>
                </ul>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="topbar-icon-btn" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="{{ url('/admin/login') }}" id="logoutLink">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                </ul>
            </div>
        </div>
    </header>

    {{-- Sidebar --}}
    <nav id="sidebar" class="d-flex flex-column">
        <div class="sidebar-profile">
            <div class="profile-img-wrapper">
                <div id="profile-skeleton" class="skeleton skeleton-circle"
                    style="width: 90px; height: 90px; border-radius: 50% !important;"></div>
                <img id="admin-profile-img" class="profile-img" style="display: none;">
                <div class="status-indicator"></div>
            </div>
            <div id="name-skeleton" class="skeleton skeleton-text mx-auto mb-1" style="width: 130px; height: 16px;">
            </div>
            <h5 class="admin-name" id="admin-name" style="display: none">
                <a href="#">Loading...</a>
            </h5>
            <div id="role-skeleton" class="skeleton skeleton-text mx-auto" style="width: 90px; height: 12px;"></div>
            <p class="admin-role" id="admin-role" style="display: none">Loading...</p>
        </div>

        <div class="sidebar-nav flex-grow-1">
            <!-- Dashboard -->
            <div id="dashboard-nav-skeleton" class="mb-2">
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 80px; height: 16px;"></div>
                </div>
            </div>
            <div id="dashboard-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/dashboard') || Request::is('admin/signatory/dashboard') ? 'active' : '' }}"
                    id="dashboard-nav-link" href="#">
                    <i class="fa-solid fa-house" id="dashboard-nav-icon"></i>
                    <span id="dashboard-nav-text">Dashboard</span>
                </a>
            </div>

            <!-- Management Section -->
            <div id="management-section-skeleton">
                <div class="px-3 py-2 mt-2">
                    <div class="skeleton skeleton-text" style="width: 100px; height: 12px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 110px; height: 16px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 100px; height: 16px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 115px; height: 16px;"></div>
                </div>
            </div>

            <div id="management-section" style="display: none;">
                <div class="nav-section-title">Management</div>
            </div>

            <div id="pending-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/pending-requests') ? 'active' : '' }}"
                    href="{{ url('/admin/pending-requests') }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>Request Forms</span>
                </a>
            </div>

            <div id="administrators-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/admin-roles') ? 'active' : '' }}"
                    href="{{ url('/admin/admin-roles') }}">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Administrators</span>
                </a>
            </div>

            <!-- Inventories Section -->
            <div id="inventories-section-skeleton">
                <div class="px-3 py-2 mt-2">
                    <div class="skeleton skeleton-text" style="width: 90px; height: 12px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 80px; height: 16px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 85px; height: 16px;"></div>
                </div>
            </div>

            <div id="inventories-section" style="display: none;">
                <div class="nav-section-title">Inventories</div>
            </div>

            <div id="facilities-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/manage-facilities') ? 'active' : '' }}"
                    href="{{ url('/admin/manage-facilities') }}">
                    <i class="fa-solid fa-landmark"></i>
                    <span>Facilities</span>
                </a>
            </div>

            <div id="equipment-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/manage-equipment') ? 'active' : '' }}"
                    href="{{ url('/admin/manage-equipment') }}">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Equipment</span>
                </a>
            </div>

            <!-- Transactions Section -->
            <div id="transactions-section-skeleton">
                <div class="px-3 py-2 mt-2">
                    <div class="skeleton skeleton-text" style="width: 105px; height: 12px;"></div>
                </div>
                <div class="d-flex align-items-center gap-2 px-3 py-2">
                    <div class="skeleton skeleton-circle" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 90px; height: 16px;"></div>
                </div>
            </div>

            <div id="transactions-section" style="display: none;">
                <div class="nav-section-title">Transactions</div>
            </div>

            <div id="archive-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/archives') ? 'active' : '' }}"
                    href="{{ url('/admin/archives') }}">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Requisitions</span>
                </a>
            </div>

            <div id="feedback-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/user-feedback') ? 'active' : '' }}"
                    href="{{ url('/admin/user-feedback') }}">
                    <i class="fa-solid fa-star"></i>
                    <span>User Feedback</span>
                </a>
            </div>
        </div>
        <div class="sidebar-footer">
            <small>&copy; {{ date('Y') }} Central Philippine University</small>
        </div>
    </nav>

    <main id="main">
        <div class="container-fluid px-4">
            @yield('content')
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin/authentication.js') }}"></script>
    @yield('scripts')

    <script>
        class NotificationManager {
            constructor() {
                this.pollingInterval = null;
                this.isInitialized = false;
                this.init();
            }

            init() {
                if (this.isInitialized) return;
                this.loadNotifications();
                this.setupEventListeners();
                this.startPolling();
                this.isInitialized = true;
            }

            setupEventListeners() {
                document.getElementById('markAllAsRead')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
                document.getElementById('notificationDropdownButton')?.addEventListener('click', () => {
                    this.loadNotifications();
                });
            }

            async loadNotifications() {
                try {
                    const token = localStorage.getItem('adminToken');
                    if (!token) return;
                    const response = await fetch('/api/admin/notifications', {
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error('Failed to fetch notifications');
                    const data = await response.json();
                    this.updateNotificationUI(data);
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            }

            updateNotificationUI(data) {
                const { notifications, unread_count } = data;
                this.updateBadge('notificationBadge', unread_count);
                this.renderNotificationList(notifications);
            }

            updateBadge(elementId, count) {
                const badge = document.getElementById(elementId);
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }

            renderNotificationList(notifications) {
                const container = document.getElementById('notificationList');
                if (!container) return;
                if (notifications.length === 0) {
                    container.innerHTML = '<div class="text-center py-3 text-muted">No notifications</div>';
                    return;
                }
                container.innerHTML = notifications.map(notification => `
                    <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                         onclick="notificationManager.handleNotificationClick(${notification.notification_id}, ${notification.request_id || 'null'})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="small text-muted">${this.formatTime(notification.created_at)}</div>
                                <div class="fw-medium">${notification.message}</div>
                                ${notification.request_id ? `<small class="text-primary">Request #${notification.request_id}</small>` : ''}
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            handleNotificationClick(notificationId, requestId) {
                this.markAsRead(notificationId);
                if (requestId) window.location.href = `/admin/requisition/${requestId}`;
            }

            async markAsRead(notificationId = null) {
                try {
                    const token = localStorage.getItem('adminToken');
                    if (!token) return;
                    const url = notificationId ? `/api/admin/notifications/mark-read/${notificationId}` : '/api/admin/notifications/mark-all-read';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.updateBadge('notificationBadge', data.unread_count);
                        if (document.getElementById('notificationDropdown')?.classList.contains('show')) this.loadNotifications();
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            async markAllAsRead() { await this.markAsRead(); }

            formatTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMins = Math.floor((now - date) / 60000);
                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
                return `${Math.floor(diffMins / 1440)}d ago`;
            }

            startPolling() { this.pollingInterval = setInterval(() => this.loadNotifications(), 30000); }
            stopPolling() { if (this.pollingInterval) clearInterval(this.pollingInterval); }
        }

        const notificationManager = new NotificationManager();

        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('adminToken');
            if (!token) return;

            fetch('/api/admin/profile', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.photo_url) {
                        const img = new Image();
                        img.onload = () => {
                            document.getElementById('profile-skeleton').style.display = 'none';
                            document.getElementById('admin-profile-img').src = data.photo_url;
                            document.getElementById('admin-profile-img').style.display = 'block';
                        };
                        img.src = data.photo_url;
                    } else {
                        document.getElementById('profile-skeleton').style.display = 'none';
                        document.getElementById('admin-profile-img').style.display = 'block';
                    }

                    document.getElementById('name-skeleton').style.display = 'none';
                    const nameElement = document.querySelector('#admin-name a');
                    nameElement.textContent = `${data.first_name} ${data.last_name}`;
                    nameElement.href = `/admin/profile/${data.admin_id}`;
                    document.getElementById('admin-name').style.display = 'block';

                    document.getElementById('role-skeleton').style.display = 'none';
                    document.getElementById('admin-role').textContent = data.role ? data.role.role_title : 'Admin';
                    document.getElementById('admin-role').style.display = 'block';

                    const roleTitle = data.role ? data.role.role_title : '';
                    localStorage.setItem('adminRoleTitle', roleTitle);
                    hideSidebarItemsBasedOnRole(data.role ? data.role.role_id : null);
                    updateDashboardNavLink();
                    notificationManager.init();
                })
                .catch(error => {
                    console.error('Error fetching profile:', error);
                    document.getElementById('profile-skeleton').style.display = 'none';
                    document.getElementById('name-skeleton').style.display = 'none';
                    document.getElementById('role-skeleton').style.display = 'none';
                    showAllNavItems();
                });
        });

        function updateDashboardNavLink() {
            const roleTitle = localStorage.getItem('adminRoleTitle');
            const dashboardLink = document.getElementById('dashboard-nav-link');
            const isApprovalRole = roleTitle === 'Vice President of Administration' || roleTitle === 'Approving Officer';
            if (dashboardLink) dashboardLink.href = isApprovalRole ? '/admin/signatory/dashboard' : '/admin/dashboard';
        }

        function hideSidebarItemsBasedOnRole(roleId) {
            const skeletons = ['dashboard-nav-skeleton', 'management-section-skeleton', 'inventories-section-skeleton', 'transactions-section-skeleton'];
            skeletons.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });

            const sections = ['management-section', 'inventories-section', 'transactions-section'];
            sections.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; });

            const navItems = {
                'dashboard-nav-item': true,
                'pending-nav-item': true,
                'administrators-nav-item': true,
                'facilities-nav-item': true,
                'equipment-nav-item': true,
                'archive-nav-item': true,
                'feedback-nav-item': true
            };

            Object.keys(navItems).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'block';
            });

            if (roleId === 2 || roleId === 3) {
                const adminItem = document.getElementById('administrators-nav-item');
                if (adminItem) adminItem.style.display = 'none';
            } else if (roleId === 4) {
                const adminItem = document.getElementById('administrators-nav-item');
                const pendingItem = document.getElementById('pending-nav-item');
                if (adminItem) adminItem.style.display = 'none';
                if (pendingItem) pendingItem.style.display = 'none';
            }
        }

        function showAllNavItems() {
            const skeletons = ['dashboard-nav-skeleton', 'management-section-skeleton', 'inventories-section-skeleton', 'transactions-section-skeleton'];
            skeletons.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; });
            const sections = ['management-section', 'inventories-section', 'transactions-section'];
            sections.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; });
            const navItems = ['dashboard-nav-item', 'pending-nav-item', 'administrators-nav-item', 'facilities-nav-item', 'equipment-nav-item', 'archive-nav-item', 'feedback-nav-item'];
            navItems.forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'block'; });
        }

        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar')?.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            if (window.innerWidth <= 991.98 && sidebar?.classList.contains('active')) {
                if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                document.getElementById('sidebar')?.classList.remove('active');
            }
        });

        // Topbar Scroll Hide/Show
        const topbar = document.getElementById('topbar');
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            if (currentScroll > 100 && currentScroll > lastScroll) topbar?.classList.add('topbar-hidden');
            else if (currentScroll < lastScroll) topbar?.classList.remove('topbar-hidden');
            lastScroll = currentScroll;
        });
        topbar?.addEventListener('mouseenter', () => topbar.classList.remove('topbar-hidden'));
    </script>
</body>

</html>