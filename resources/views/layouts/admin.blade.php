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
    @php
        $cssCacheVersion = filemtime(public_path('css/admin/global-styles.css'));
    @endphp
    <link rel="stylesheet" href="{{ asset('css/admin/global-styles.css') . '?v=' . $cssCacheVersion }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
</head>

<body>
    <button class="sidebar-toggle" type="button" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    {{-- Topbar --}}
    <header id="topbar" class="d-flex justify-content-between align-items-center transition-all">
        <div class="d-flex align-items-center gap-2">
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
                    <li><a class="dropdown-item" href="#" id="accountSettingsBtn">
                            <i class="bi bi-person-circle me-2"></i>Account Settings
                        </a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
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

            <div id="equipment-tracker-nav-item" style="display: none;">
                <a class="nav-link {{ Request::is('admin/scan-equipment') ? 'active' : '' }}"
                    href="{{ url('/admin/scan-equipment') }}">
                    <i class="fa-solid fa-camera"></i>
                    <span>Equipment Tracker</span>
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

            // Check cache first
            const cached = sessionStorage.getItem('admin_profile');
            if (cached) {
                try {
                    const data = JSON.parse(cached);
                    // Use cache if less than 30 minutes old
                    if (Date.now() - data.timestamp < 30 * 60 * 1000) {
                        renderProfile(data.profile);
                        return;
                    }
                } catch (e) { }
            }

            fetch('/api/admin/profile', {
                headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    // Cache the profile
                    sessionStorage.setItem('admin_profile', JSON.stringify({
                        profile: data,
                        timestamp: Date.now()
                    }));
                    renderProfile(data);
                })
                .catch(error => {
                    console.error('Error fetching profile:', error);
                    document.getElementById('profile-skeleton').style.display = 'none';
                    document.getElementById('name-skeleton').style.display = 'none';
                    document.getElementById('role-skeleton').style.display = 'none';
                    showAllNavItems();
                });

            function renderProfile(data) {
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

                const accountSettingsBtn = document.getElementById('accountSettingsBtn');
                if (accountSettingsBtn && nameElement) {
                    accountSettingsBtn.href = nameElement.href;
                }

                document.getElementById('role-skeleton').style.display = 'none';
                document.getElementById('admin-role').textContent = data.role ? data.role.role_title : 'Admin';
                document.getElementById('admin-role').style.display = 'block';

                const roleTitle = data.role ? data.role.role_title : '';
                localStorage.setItem('adminRoleTitle', roleTitle);
                hideSidebarItemsBasedOnRole(data.role ? data.role.role_id : null);
                updateDashboardNavLink();
                notificationManager.init();

                const sidebar = document.getElementById('sidebar');
                const toggleBtn = document.getElementById('sidebarToggle');
                if (sidebar && toggleBtn) {
                    toggleBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        sidebar.classList.toggle('active');
                    });
                    document.addEventListener('click', (e) => {
                        if (window.innerWidth <= 991.98 && sidebar.classList.contains('active') &&
                            !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                            sidebar.classList.remove('active');
                        }
                    });
                }
            }
        });

        function updateDashboardNavLink() {
            const dashboardLink = document.getElementById('dashboard-nav-link');
            if (dashboardLink) dashboardLink.href = '/admin/dashboard';
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
                'feedback-nav-item': true,
                'equipment-tracker-nav-item': true
            };

            Object.keys(navItems).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'block';
            });

            if (roleId === 4) {
                const managementSection = document.getElementById('management-section');
                if (managementSection) managementSection.style.display = 'none';

                const adminItem = document.getElementById('administrators-nav-item');
                const pendingItem = document.getElementById('pending-nav-item');
                if (adminItem) adminItem.style.display = 'none';
                if (pendingItem) pendingItem.style.display = 'none';
            } else if (roleId === 2 || roleId === 3) {
                const adminItem = document.getElementById('administrators-nav-item');
                if (adminItem) adminItem.style.display = 'none';
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

        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                document.getElementById('sidebar')?.classList.remove('active');
            }
        });

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