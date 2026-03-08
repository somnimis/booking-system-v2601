<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CPU Booking')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <style>
        :root {
            --bs-primary: #135ba3;
            --bs-primary-rgb: 19, 91, 163;
        }


        #markAllAsRead {
            color: #2664b6ff !important;
            text-decoration: none;
        }

        #markAllAsRead:hover {
            color: #14407aff !important;
        }


        .text-primary {
            color: var(--cpu-primary) !important;
        }

        .p {
            color: #333333;
        }

        .btn-primary {
            background-color: var(--btn-primary);
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            background-color: var(--btn-primary-hover);
            border: 1px solid transparent;
        }

        .btn-secondary {
            background-color: transparent;
            color: #666666ff;
            border: 1px transparent;
        }

        .btn-secondary:hover {
            background-color: lightgray;
            color: var(--cpu-text-dark);
            border: 1px transparent;
        }

        /* FullCalendar Styles */

        #calendar {
            background: #ffffff;
        }

        .fc-daygrid-body {
            background: #f8f9fa;
        }

        .fc-col-header-cell-cushion {
            text-decoration: none;
            color: var(--cpu-text-dark);
        }

        .fc-col-header-cell {
            background: #e6e8ebff;
        }

        .fc-daygrid-day-number {
            text-decoration: none;
            color: var(--cpu-text-dark);
        }

        .fc-day-today {
            background: #f3f4f7ff !important;
        }

        /* Essential Skeleton Loading */
        .skeleton {
            background: #eee;
            background: linear-gradient(110deg, #ececec 8%, #f5f5f5 18%, #ececec 33%);
            background-size: 200% 100%;
            animation: 1.5s shine linear infinite;
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
            border-radius: 4px;
        }

        /* Root Variables */
        :root {
            --cpu-primary: #003366;
            --cpu-primary-hover: #004a94;
            --btn-primary: #135ba3;
            --btn-primary-hover: #0f4c8aff;
            --cpu-secondary: #f2b123;
            --cpu-text-dark: #333333;
            --light-gray: #f8f9fa;
        }

        /* Base Layout */
        body {
            display: flex;
            background: linear-gradient(180deg, #eceef3ff 0%, #f8f8f8 50%, #f1f1f1 100%);
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Roboto, Arial, sans-serif;
            color: var(--cpu-text-dark);
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: #fafafaff;
            border-right: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1030;
            /* Box shadow for depth */
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
        }

        /* Global thin scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
            /* for horizontal scrollbars */
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.35);
        }

        /* Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }


        #sidebar .nav-link {
            color: #5b6a7aff;
            margin-bottom: -1px;
            transition: all 0.2s ease;
            flex: 1;
            text-align: left;
        }

        #sidebar .badge {
            margin-left: auto;
        }


        #sidebar .nav-link:hover {
            background-color: rgba(80, 128, 206, 0.1);
        }

        #sidebar .nav-link.active {
            background-color: #105ba7ff;
            font-weight: 500;
            color: white;
        }

        /* Hidden nav items */
        .nav-item.hidden {
            display: none !important;
            margin-bottom: 4px;
        }

        #sidebar .nav-link .nav-icon {
            width: 24px;
            text-align: center;
        }

        #sidebar .nav-link span {
            flex: 1;
            text-align: left;
        }



        /* Topbar Styles */
        #topbar {
            background-color: rgba(30, 83, 153, 0.94) !important;
            backdrop-filter: blur(8px);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            margin-left: 250px;
            width: calc(100% - 250px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1030;
        }

        /* Profile Image */
        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .status-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: green;
            border: 2px solid white;
            border-radius: 50%;
            width: 15px;
            height: 15px;
        }

        /* Notification Styles */
        #notificationDropdown {
            width: 300px !important;
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Add this new style for the sidebar toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            left: 1rem;
            top: 0.75rem;
            z-index: 1031;
            padding: 0.25rem 0.75rem;
            font-size: 1.25rem;
            background: transparent;
            border: none;
            color: var(--cpu-text-dark);
        }

        .sidebar-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        /* Main content area */
        main {
            margin-left: 100px;
            margin-top: 10px;
            padding: 2rem;
            width: calc(100% - 76px);
            transition: all 0.3s ease;
            min-height: calc(100vh - 70px);
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
                /* Add this line - pushes content right */
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


        /* Smooth transitions for topbar */
        .transition-all {
            transition: all 0.3s ease-in-out;
        }

        /* Hide the topbar when scrolled down */
        .topbar-hidden {
            transform: translateY(-100%);
        }

        /* Preserve your original three dots style */
        .three-dots-icon {
            background: rgba(255, 255, 255, 0);
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #ffffffff !important;
            font-size: 1.5rem;
            line-height: 1;
            display: inline-flex;
        }

        .three-dots-icon:hover {
            background: rgba(190, 201, 211, 0.3);
            transform: scale(1.05);
        }

        .three-dots-icon:active {
            transform: scale(0.95);
            background: rgba(190, 201, 211, 0.5);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
        }

        .back-button:hover {
            background: rgba(190, 201, 211, 0.3);
            transform: scale(1.05);
        }

        .back-button:active {
            transform: scale(0.95);
            background: rgba(190, 201, 211, 0.5);
        }
    </style>
</head>

<body>
    <button class="btn sidebar-toggle" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    {{-- Header --}}
    <header id="topbar" class="d-flex justify-content-between align-items-center fixed-top transition-all">

        <!-- Left side: back button + current page title -->
        <div class="d-flex align-items-center gap-2">

            {{-- Back button --}}
            <a href="{{ url()->previous() }}" class="back-button me-0">
                <i class="bi bi-caret-left-fill"></i>
            </a>


            {{-- Current page title --}}
            <span class="fw-bold brand-text">
                @yield('title', 'CPU Facilities and Equipment Management')
            </span>
        </div>

        <!-- Right side: notification bell + dropdown menu -->
        <div class="d-flex align-items-center gap-3">
            <!-- Notification Bell -->
            <div class="dropdown">
                <button class="btn btn-link p-0 text-white position-relative" id="notificationDropdownButton"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-inbox three-dots-icon"></i>
                    <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                </button>

                <ul class="dropdown-menu dropdown-menu-end" id="notificationDropdown" style="width: 350px;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>Notifications</span>
                        <button class="btn btn-sm btn-link p-0" id="markAllAsRead">Mark all as read</button>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <div id="notificationList" class="px-2" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span class="ms-2">Loading notifications...</span>
                        </div>
                    </div>
                </ul>
            </div>

            <!-- User Menu Dropdown -->
            <div class="dropdown">
                <button class="btn btn-link p-0 text-white" id="dropdownMenuButton" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bi bi-three-dots three-dots-icon"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">

                    <li><a class="dropdown-item text-danger" href="{{ url('/admin/login') }}" id="logoutLink">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                </ul>
            </div>
        </div>

    </header>


    {{-- Sidebar + Main Content --}}



    <nav id="sidebar" class="d-flex flex-column">
        <!-- Profile Section -->
        <div class="text-center mb-4 px-3 pt-4">
            <div class="position-relative d-inline-block mb-3">
                <div id="profile-img-container" class="position-relative">
                    <div id="profile-skeleton" class="skeleton skeleton-circle" style="width: 80px; height: 80px;">
                    </div>
                    <img id="admin-profile-img" class="rounded-circle border border-3 border-white shadow-sm"
                        style="width: 80px; height: 80px; object-fit: cover; display: none;">
                    <div class="status-indicator bg-success"></div>
                </div>
            </div>
            <div id="name-skeleton" class="skeleton skeleton-text mx-auto mb-2" style="width: 120px;"></div>
            <h5 class="mb-1 fw-semibold" id="admin-name" style="display: none">
                <a href="#" class="text-decoration-none text-dark admin-profile-link">Loading...</a>
            </h5>
            <div id="role-skeleton" class="skeleton skeleton-text mx-auto mb-3" style="width: 80px;"></div>
            <p class="text-muted small mb-0" id="admin-role" style="display: none">Loading...</p>
        </div>
        <!-- Navigation Items with Proper Organization -->
        <ul class="nav flex-column px-2 flex-grow-1">
            <!-- Dashboard (no section title needed) -->
            <li class="nav-item mb-1 nav-link-skeleton" id="dashboard-nav-skeleton">
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 85px; height: 16px;"></div>
                </div>
            </li>
            <li class="nav-item mb-1" id="dashboard-nav-item" style="display: none;">
                @php
                    $user = Auth::user();
                    $roleTitle = $user && $user->role ? $user->role->role_title : '';
                @endphp
                <a class="nav-link py-1 px-2 rounded-2" id="dashboard-nav-link" href="{{ url('/admin/dashboard') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-house" id="dashboard-nav-icon"></i>
                        </div>
                        <span id="dashboard-nav-text">Dashboard</span>
                    </div>
                </a>
            </li>

            <!-- Management Category Title + Items (grouped in skeleton) -->
            <li class="nav-item mb-1 nav-link-skeleton" id="management-section-skeleton" style="display: block;">
                <!-- Section Title Skeleton -->
                <div class="px-2 py-1">
                    <div class="skeleton skeleton-text" style="width: 90px; height: 14px;"></div>
                </div>
                <!-- Active Bookings Skeleton -->
                <div class="d-flex align-items-center py-1 px-2 mt-1">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 100px; height: 16px;"></div>
                </div>
                <!-- Pending Approval Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 115px; height: 16px;"></div>
                </div>
                <!-- Equipment Tracking Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 110px; height: 16px;"></div>
                </div>
                <!-- Administrators Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 105px; height: 16px;"></div>
                </div>
            </li>

            <!-- Actual Management Section (hidden initially) -->
            <li class="nav-item mt-1 mb-1" id="management-section" style="display: none;">
                <div class="px-2 py-1">
                    <h6 class="text-muted text-uppercase fw-bold small mb-0"
                        style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        Management
                    </h6>
                </div>
            </li>
            <!-- Active Bookings Nav Item -->
            <li class="nav-item mb-1" id="calendar-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/calendar*') ? 'active' : '' }}"
                    href="{{ url('/admin/calendar') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-calendar me-1"></i>
                        </div>
                        <span>Active Bookings</span>
                    </div>
                </a>
            </li>
            <!-- Pending Approval Nav Item -->
            <li class="nav-item mb-1" id="pending-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/pending-requests*') ? 'active' : '' }}"
                    href="{{ url('/admin/pending-requests') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-clock me-1"></i>
                        </div>
                        <span>Pending Approval</span>
                    </div>
                </a>
            </li>
            <!-- Equipment Tracking Nav Item -->
            <li class="nav-item mb-1" id="equipment-tracking-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/equipment-tracking*') ? 'active' : '' }}"
                    href="{{ url('/admin/equipment-tracking') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-chart-line me-1"></i>
                        </div>
                        <span>Asset Tracking</span>
                    </div>
                </a>
            </li>
            <!-- Administrators Nav Item -->
            <li class="nav-item mb-1" id="administrators-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/admin-roles*') ? 'active' : '' }}"
                    href="{{ url('/admin/admin-roles') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-user-gear me-2"></i>
                        </div>
                        <span>Administrators</span>
                    </div>
                </a>
            </li>

            <!-- Inventories Category Title + Items (grouped in skeleton) -->
            <li class="nav-item mb-1 nav-link-skeleton" id="inventories-section-skeleton" style="display: block;">
                <!-- Section Title Skeleton -->
                <div class="px-2 py-1 mt-2">
                    <div class="skeleton skeleton-text" style="width: 80px; height: 14px;"></div>
                </div>
                <!-- Facilities Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 70px; height: 16px;"></div>
                </div>
                <!-- Equipment Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 75px; height: 16px;"></div>
                </div>
            </li>

            <!-- Actual Inventories Section (hidden initially) -->
            <li class="nav-item mt-1 mb-1" id="inventories-section" style="display: none;">
                <div class="px-2 py-1">
                    <h6 class="text-muted text-uppercase fw-bold small mb-0"
                        style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        Inventories
                    </h6>
                </div>
            </li>
            <!-- Facilities Nav Item -->
            <li class="nav-item mb-1" id="facilities-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/manage-facilities*') ? 'active' : '' }}"
                    href="{{ url('/admin/manage-facilities') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-landmark me-2"></i>
                        </div>
                        <span>Facilities</span>
                    </div>
                </a>
            </li>
            <!-- Equipment Nav Item -->
            <li class="nav-item mb-1" id="equipment-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/manage-equipment*') ? 'active' : '' }}"
                    href="{{ url('/admin/manage-equipment') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-box-archive me-2"></i>
                        </div>
                        <span>Equipment</span>
                    </div>
                </a>
            </li>

            <!-- Transactions Category Title + Items (grouped in skeleton) -->
            <li class="nav-item mb-1 nav-link-skeleton" id="transactions-section-skeleton" style="display: block;">
                <!-- Section Title Skeleton -->
                <div class="px-2 py-1 mt-2">
                    <div class="skeleton skeleton-text" style="width: 95px; height: 14px;"></div>
                </div>
                <!-- Requisitions Skeleton -->
                <div class="d-flex align-items-center py-1 px-2">
                    <div class="skeleton skeleton-circle me-2" style="width: 20px; height: 20px;"></div>
                    <div class="skeleton skeleton-text" style="width: 80px; height: 16px;"></div>
                </div>
            </li>

            <!-- Actual Transactions Section (hidden initially) -->
            <li class="nav-item mt-2 mb-1" id="transactions-section" style="display: none;">
                <div class="px-2 py-1">
                    <h6 class="text-muted text-uppercase fw-bold small mb-0"
                        style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        Transactions
                    </h6>
                </div>
            </li>
            <!-- Requisitions Nav Item -->
            <li class="nav-item mb-1" id="archive-nav-item" style="display: none;">
                <a class="nav-link py-1 px-2 rounded-2 {{ Request::is('admin/archives*') ? 'active' : '' }}"
                    href="{{ url('/admin/archives') }}">
                    <div class="d-flex align-items-center">
                        <div class="nav-icon p-1 rounded me-2">
                            <i class="fa-solid fa-cart-shopping me-2"></i>
                        </div>
                        <span>Requisitions</span>
                    </div>
                </a>
            </li>
        </ul>
        <!-- Footer -->
        <div class="mt-auto p-3 text-center border-top">
            <small class="text-muted">&copy; {{ date('Y') }} Central Philippine University</small>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>

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
                // Mark all as read
                document.getElementById('markAllAsRead')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });

                // Mark as read when clicking requisitions nav link
                document.getElementById('requisitionsNavLink')?.addEventListener('click', () => {
                    this.markAllAsRead();
                });

                // Load notifications when clicking notification bell
                document.getElementById('notificationDropdownButton')?.addEventListener('click', () => {
                    this.loadNotifications();
                });
            }

            async loadNotifications() {
                try {
                    const token = localStorage.getItem('adminToken');
                    if (!token) return;

                    const response = await fetch('/api/admin/notifications', {
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json'
                        }
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

                // Update badge counts
                this.updateBadge('notificationBadge', unread_count);
                this.updateBadge('requisitionNotificationBadge', unread_count);

                // Update notification list
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
            <div class="notification-item mb-2 p-2 rounded ${notification.is_read ? '' : 'bg-light'}" 
                 style="cursor: pointer; border-left: 3px solid ${notification.is_read ? 'transparent' : '#007bff'};"
                 onclick="notificationManager.handleNotificationClick(${notification.notification_id}, ${notification.request_id || 'null'})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="small text-muted">${this.formatTime(notification.created_at)}</div>
                        <div class="fw-medium">${notification.message}</div>
                        ${notification.request_id ?
                        `<small class="text-primary">Request #${notification.request_id}</small>` : ''}
                    </div>
                    ${!notification.is_read ?
                        '<span class="badge bg-primary ms-2">New</span>' : ''}
                </div>
            </div>
        `).join('');
            }

            handleNotificationClick(notificationId, requestId) {
                // Mark as read first
                this.markAsRead(notificationId);

                // If it's a request notification, redirect to the request view
                if (requestId) {
                    window.location.href = `/admin/requisition/${requestId}`;
                }
            }

            async markAsRead(notificationId = null) {
                try {
                    const token = localStorage.getItem('adminToken');
                    if (!token) return;

                    const url = notificationId ?
                        `/api/admin/notifications/mark-read/${notificationId}` :
                        '/api/admin/notifications/mark-all-read';

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.updateBadge('notificationBadge', data.unread_count);
                        this.updateBadge('requisitionNotificationBadge', data.unread_count);

                        // Refresh list if dropdown is open
                        const dropdown = document.getElementById('notificationDropdown');
                        if (dropdown?.classList.contains('show')) {
                            this.loadNotifications();
                        }
                    }
                } catch (error) {
                    console.error('Error marking notification as read:', error);
                }
            }

            async markAllAsRead() {
                await this.markAsRead();
            }

            formatTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMins / 60);
                const diffDays = Math.floor(diffHours / 24);

                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;

                return date.toLocaleDateString();
            }

            startPolling() {
                // Poll every 30 seconds for new notifications
                this.pollingInterval = setInterval(() => {
                    this.loadNotifications();
                }, 30000);
            }

            stopPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                }
            }
        }

        // Initialize notification manager
        const notificationManager = new NotificationManager();

        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('adminToken');
            if (!token) return;

            fetch('/api/admin/profile', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    // Preload the image
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

                    // Update name
                    document.getElementById('name-skeleton').style.display = 'none';
                    const nameElement = document.querySelector('#admin-name a');
                    nameElement.textContent = `${data.first_name} ${data.last_name}`;
                    nameElement.href = `/admin/profile/${data.admin_id}`;
                    document.getElementById('admin-name').style.display = 'block';

                    // Update role
                    document.getElementById('role-skeleton').style.display = 'none';
                    const roleElement = document.getElementById('admin-role');
                    roleElement.textContent = data.role ? data.role.role_title : 'Admin';
                    roleElement.style.display = 'block';


                    const roleTitle = data.role ? data.role.role_title : '';
                    const userRoleId = data.role ? data.role.role_id : null;
                    const isApprovalRole = roleTitle === 'Vice President of Administration' || roleTitle === 'Approving Officer';

                    localStorage.setItem('adminRoleTitle', roleTitle);
                    localStorage.setItem('adminRoleId', userRoleId);
                    localStorage.setItem('isApprovalRole', isApprovalRole);



                    // Hide sidebar items based on role
                    hideSidebarItemsBasedOnRole(data.role ? data.role.role_id : null);
                    // Initialize notifications after profile is loaded
                    notificationManager.init();
                })
                .catch(error => {
                    console.error('Error fetching profile:', error);
                    // Show error state or fallback content
                    document.getElementById('profile-skeleton').style.display = 'none';
                    document.getElementById('name-skeleton').style.display = 'none';
                    document.getElementById('role-skeleton').style.display = 'none';
                    document.getElementById('admin-profile-img').style.display = 'block';
                    document.getElementById('admin-name').style.display = 'block';
                    document.getElementById('admin-role').style.display = 'block';

                    // If there's an error, show all nav items by default
                    showAllNavItems();
                });
        });

        // Update dashboard nav link based on actual role
        function updateDashboardNavLink() {
            const roleTitle = localStorage.getItem('adminRoleTitle');
            const dashboardLink = document.getElementById('dashboard-nav-link');
            const dashboardIcon = document.getElementById('dashboard-nav-icon');
            const dashboardText = document.getElementById('dashboard-nav-text');

            if (!dashboardLink || !roleTitle) return;

            const isApprovalRole = roleTitle === 'Vice President of Administration' ||
                roleTitle === 'Approving Officer';

            // Update link
            dashboardLink.href = isApprovalRole ? '/admin/signatory/dashboard' : '/admin/dashboard';

            // Icon STAYS as house for everyone! No checkmark
            dashboardIcon.className = 'fa-solid fa-house me-2';

            // Update text
            dashboardText.textContent = isApprovalRole ? 'Dashboard' : 'Dashboard';

            // Update active state
            const currentPath = window.location.pathname;
            const isOnDashboard = currentPath.includes('/admin/dashboard') ||
                currentPath.includes('/admin/signatory/dashboard');

            if (isOnDashboard) {
                dashboardLink.classList.add('active');
            } else {
                dashboardLink.classList.remove('active');
            }
        }

        // Call it after profile is loaded
        const originalFetchCallback = window.fetchProfileCallback;
        window.fetchProfileCallback = function (data) {
            if (originalFetchCallback) originalFetchCallback(data);
            updateDashboardNavLink();
        };

        // Also call on page load
        document.addEventListener('DOMContentLoaded', updateDashboardNavLink);

        // And call when navigation happens (for SPAs)
        window.addEventListener('popstate', updateDashboardNavLink);

        
function hideSidebarItemsBasedOnRole(roleId) {
    // Hide all skeletons first
    hideAllSkeletons();

    // Show all sections (Dashboard, Inventories, Transactions are always visible)
    const sections = [
        'management-section',
        'inventories-section',
        'transactions-section'
    ];
    
    sections.forEach(id => {
        const section = document.getElementById(id);
        if (section) section.style.display = 'block';
    });

    // Get all nav items
    const dashboardNavItem = document.getElementById('dashboard-nav-item');
    const calendarNavItem = document.getElementById('calendar-nav-item');           // Active Bookings
    const pendingNavItem = document.getElementById('pending-nav-item');             // Pending Approval
    const equipmentTrackingNavItem = document.getElementById('equipment-tracking-nav-item');
    const administratorsNavItem = document.getElementById('administrators-nav-item');
    const facilitiesNavItem = document.getElementById('facilities-nav-item');
    const equipmentNavItem = document.getElementById('equipment-nav-item');
    const archiveNavItem = document.getElementById('archive-nav-item');             // Requisitions

    // First, show all nav items (they'll be hidden based on role below)
    const allNavItems = [
        dashboardNavItem,
        calendarNavItem,
        pendingNavItem,
        equipmentTrackingNavItem,
        administratorsNavItem,
        facilitiesNavItem,
        equipmentNavItem,
        archiveNavItem
    ];

    allNavItems.forEach(item => {
        if (item) item.style.display = 'block';
    });

    // Apply role-based visibility rules
    switch (Number(roleId)) {
        case 1: // Head Admin
            // SHOW ALL - no items to hide
            break;

        case 2: // Vice President of Administration
            // SHOW ALL - can view and approve requisition forms only
            // (all items remain visible as per business rule)
            break;

        case 3: // Approving Officer
            // Hide only Administrators
            if (administratorsNavItem) administratorsNavItem.style.display = 'none';
            break;

        case 4: // Inventory Manager
            // Hide Administrators, Active Bookings, Pending Approval
            if (administratorsNavItem) administratorsNavItem.style.display = 'none';
            if (calendarNavItem) calendarNavItem.style.display = 'none';           // Active Bookings
            if (pendingNavItem) pendingNavItem.style.display = 'none';             // Pending Approval
            // Equipment Tracking visibility? Keeping visible since Inventory Manager manages equipment
            // If Equipment Tracking should also be hidden, uncomment the line below:
            // if (equipmentTrackingNavItem) equipmentTrackingNavItem.style.display = 'none';
            break;

        default:
            // For any undefined roles, show all items
            break;
    }

    // Optional: Hide entire Management section if all its items are hidden
    const managementItems = [calendarNavItem, pendingNavItem, equipmentTrackingNavItem, administratorsNavItem];
    const anyManagementVisible = managementItems.some(item => item && item.style.display !== 'none');
    const managementSection = document.getElementById('management-section');
    
    if (managementSection) {
        managementSection.style.display = anyManagementVisible ? 'block' : 'none';
    }
}

        function hideAllSkeletons() {
            const skeletons = [
                'dashboard-nav-skeleton',
                'management-section-skeleton',
                'inventories-section-skeleton',
                'transactions-section-skeleton'
            ];

            skeletons.forEach(id => {
                const skeleton = document.getElementById(id);
                if (skeleton) skeleton.style.display = 'none';
            });
        }

        function showAllNavItems() {
            // Hide skeletons
            hideAllSkeletons();

            // Show all sections
            const sections = [
                'management-section',
                'inventories-section',
                'transactions-section'
            ];

            sections.forEach(id => {
                const section = document.getElementById(id);
                if (section) section.style.display = 'block';
            });

            // Show all nav items
            const navItems = [
                'dashboard-nav-item',
                'calendar-nav-item',
                'pending-nav-item',
                'equipment-tracking-nav-item',
                'administrators-nav-item',
                'facilities-nav-item',
                'equipment-nav-item',
                'archive-nav-item'
            ];

            navItems.forEach(id => {
                const item = document.getElementById(id);
                if (item) item.style.display = 'block';
            });
        }
    </script>

    <main>
        @yield('content')
    </main>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin/authentication.js') }}"></script>

    @yield('scripts')

    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            sidebar?.classList.toggle('active');
        });



        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const main = document.querySelector('main');

            if (window.innerWidth <= 991.98 && sidebar?.classList.contains('active')) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                    // Ensure main is full width after closing sidebar
                    main.style.marginLeft = '0';
                    main.style.width = '100%';
                }
            }
        });


        // Handle window resize
        window.addEventListener('resize', function () {
            const sidebar = document.getElementById('sidebar');

            // On desktop, ensure sidebar is visible and not in "active" mobile state
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('active');
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const topbar = document.getElementById('topbar');
            let lastScroll = 0;

            window.addEventListener('scroll', () => {
                const currentScroll = window.scrollY;

                // Hide on scroll down, show on scroll up
                if (currentScroll > 100 && currentScroll > lastScroll) {
                    topbar.classList.add('topbar-hidden');
                } else if (currentScroll < lastScroll) {
                    topbar.classList.remove('topbar-hidden');
                }

                lastScroll = currentScroll;
            });

            // Show on hover
            topbar.addEventListener('mouseenter', () => {
                topbar.classList.remove('topbar-hidden');
            });
        });
    </script>
</body>

</html>