<style>
    /* ============================================
       REFINED INSTITUTIONAL THEME - NAVBAR
       Matching catalog.css design system
       ============================================ */

    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap');

    /* Design Tokens - Matching catalog.css */
    :root {
        --navy: #041a4b;
        --navy-mid: #0b2d72;
        --navy-light: #e8edf8;
        --amber: #f5bc40;
        --amber-dark: #d9a12a;
        --white: #ffffff;
        --surface: #f5f6fa;
        --border: #e2e6f0;
        --text-base: #1e2d4a;
        --text-muted: #6b7a99;
        --text-light: #9aaac5;
        --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
        --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
        --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
        --radius-sm: 6px;
        --radius-md: 12px;
        --radius-lg: 18px;
        --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);

        /* Legacy support */
        --cpu-primary: #041a4b;
        --cpu-primary-light: #e8edf8;
        --cpu-secondary: #f5bc40;
        --cpu-secondary-hover: #d9a12a;
        --cpu-white: #ffffff;
        --cpu-light-bg: #f5f6fa;
        --cpu-border-accent: #e2e6f0;
        --cpu-shadow: rgba(4, 26, 75, .06);
    }

    /* Top Header Bar */
    .top-header-bar {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
        color: var(--white);
        padding: 12px 0;
        position: relative;
        z-index: 2001;
    }

    .top-header-bar .cpu-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .top-header-bar .cpu-brand img {
        height: 55px;
        width: auto;
        transition: var(--transition);
    }

    .top-header-bar .cpu-brand .title {
        font-family: 'Fraunces', serif;
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--white);
        letter-spacing: -0.3px;
    }

    .top-header-bar .cpu-brand .subtitle {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        font-weight: 400;
        line-height: 1.2;
        color: rgba(255, 255, 255, 0.85);
        letter-spacing: 0.2px;
    }

    .top-header-bar .admin-login {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .top-header-bar .admin-login a {
        color: var(--amber);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }

    .top-header-bar .admin-login a:hover {
        color: var(--amber-dark);
        text-decoration: underline;
    }

    /* Main Navbar */
    .main-navbar {
        background: var(--white);
        border-bottom: 1px solid var(--border);
        padding: 0;
        position: sticky;
        top: 0;
        z-index: 2000;
        box-shadow: var(--shadow-sm);
    }

    .navbar {
        padding: 0.75rem 0;
    }

    /* Navbar Toggler */
    .navbar-toggler {
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 0.5rem 0.75rem;
        transition: var(--transition);
    }

    .navbar-toggler:hover {
        background: var(--surface);
        border-color: var(--navy);
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3E%3Cpath stroke='%23041a4b' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    }

    /* Navbar Links */
    .main-navbar .nav-link {
        font-family: 'DM Sans', sans-serif;
        color: var(--text-base);
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
        transition: var(--transition);
        border-radius: var(--radius-sm);
        margin: 0 2px;
    }

    .main-navbar .nav-link:hover {
        color: var(--amber);
        background-color: transparent;
    }

    .main-navbar .nav-link.active {
        color: var(--amber);
        background-color: transparent;
        font-weight: 600;
        position: relative;
    }

    .main-navbar .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 1rem;
        right: 1rem;
        height: 2px;
        background: var(--amber);
        border-radius: 2px;
    }

    /* Dropdown */
    .main-navbar .dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .main-navbar .dropdown-toggle::after {
        margin-top: 2px;
    }

    .main-navbar .dropdown-menu {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-top: 0.5rem;
        padding: 0.5rem;
        box-shadow: var(--shadow-md);
        min-width: 200px;
        animation: dropdownFadeIn 0.2s ease-out;
    }

    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .main-navbar .dropdown-item {
        font-family: 'DM Sans', sans-serif;
        color: var(--text-base);
        font-size: 0.85rem;
        padding: 0.6rem 1rem;
        border-radius: var(--radius-sm);
        transition: var(--transition);
    }

    .main-navbar .dropdown-item:hover,
    .main-navbar .dropdown-item:focus {
        background-color: var(--navy-light);
        color: var(--navy);
    }

    .main-navbar .dropdown-item.active {
        background-color: var(--navy);
        color: var(--white);
    }

    /* How to Book */
    .navbar .how-to-book {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        cursor: pointer;
        color: var(--text-muted);
        transition: var(--transition);
        white-space: nowrap;
        padding: 0.5rem 0.75rem;
        border-radius: var(--radius-sm);
    }

    .navbar .how-to-book:hover {
        color: var(--navy);
        background-color: var(--navy-light);
    }

    .navbar .how-to-book i {
        color: var(--text-muted);
        transition: var(--transition);
    }

    .navbar .how-to-book:hover i {
        color: var(--navy);
    }

    /* Book Now Button */
    .main-navbar .btn-book-now {
        background: linear-gradient(135deg, var(--amber) 0%, var(--amber-dark) 100%);
        color: var(--navy);
        border: none;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 0.6rem 1.5rem;
        border-radius: 60px;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
        letter-spacing: 0.3px;
    }

    .main-navbar .btn-book-now:hover {
        background: linear-gradient(135deg, var(--amber-dark) 0%, #c08e22 100%);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .main-navbar .btn-book-now:active {
        transform: translateY(0);
    }

    /* Tooltip Styling */
    .tooltip {
        z-index: 2500 !important;
    }

    .tooltip-inner {
        background-color: var(--navy);
        color: var(--white);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        padding: 0.75rem 1rem;
        line-height: 1.4;
        max-width: 320px;
        text-align: left;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-md);
    }

    .tooltip-arrow::before {
        border-bottom-color: var(--navy);
    }

    .bs-tooltip-top .tooltip-arrow::before {
        border-top-color: var(--navy);
    }

    .bs-tooltip-end .tooltip-arrow::before {
        border-right-color: var(--navy);
    }

    .bs-tooltip-start .tooltip-arrow::before {
        border-left-color: var(--navy);
    }

    /* Mobile Responsive Styles */
    @media (max-width: 991px) {
        .navbar-collapse {
            background: var(--white);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-top: 0.75rem;
            box-shadow: var(--shadow-md);
        }
    }

    @media (max-width: 768px) {

        /* Top Header */
        .top-header-bar {
            padding: 10px 0;
        }

        .top-header-bar .container {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 12px;
        }

        .top-header-bar .cpu-brand {
            width: 100%;
        }

        .top-header-bar .cpu-brand img {
            height: 45px;
        }

        .top-header-bar .cpu-brand .title {
            font-size: 1.1rem;
        }

        .top-header-bar .cpu-brand .subtitle {
            font-size: 0.7rem;
        }

        .top-header-bar .admin-login {
            width: 100%;
            text-align: left;
            font-size: 0.8rem;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Navbar */
        .main-navbar {
            padding: 0;
        }

        .navbar {
            padding: 0.5rem 0;
        }

        .navbar-collapse {
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .main-navbar .nav-link {
            padding: 0.75rem 0;
            margin: 0;
            border-radius: 0;
        }

        .main-navbar .nav-link.active::after {
            display: none;
        }

        .main-navbar .dropdown-menu {
            background: var(--surface);
            border: none;
            padding: 0;
            margin: 0;
            box-shadow: none;
            animation: none;
        }

        .main-navbar .dropdown-item {
            padding: 0.6rem 0 0.6rem 1rem;
        }

        /* Right side actions */
        .d-flex.align-items-center.ms-lg-3 {
            flex-direction: column;
            width: 100%;
            margin-left: 0 !important;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            gap: 10px;
            border-top: 1px solid var(--border);
        }

        .navbar .how-to-book {
            width: 100%;
            text-align: center;
            white-space: normal;
        }

        .main-navbar .btn-book-now {
            width: 100%;
            text-align: center;
        }
    }

    /* Extra small devices */
    @media (max-width: 480px) {
        .top-header-bar .cpu-brand img {
            height: 38px;
        }

        .top-header-bar .cpu-brand .title {
            font-size: 0.95rem;
        }

        .top-header-bar .cpu-brand .subtitle {
            font-size: 0.65rem;
        }

        .tooltip-inner {
            max-width: 260px;
            font-size: 0.7rem;
            padding: 0.5rem 0.75rem;
        }
    }
</style>

<header class="top-header-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="cpu-brand">
            <img src="{{ asset('assets/cpu-logo.png') }}" alt="CPU Logo">
            <div>
                <div class="title">Central Philippine University</div>
                <div class="subtitle">Equipment and Facility Booking Services</div>
            </div>
        </div>
        <div class="admin-login">
            <span>Are you an Admin? <a href="{{ url('admin/login') }}">Login here.</a></span>
        </div>
    </div>
</header>

<nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('home') ? 'active' : '' }}" href="{{ url('home') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('booking-catalog') ? 'active' : '' }}"
                        href="{{ url('booking-catalog') }}">Booking Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('events-calendar') ? 'active' : '' }}"
                        href="{{ url('events-calendar') }}">Events Calendar</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('your-bookings') ? 'active' : '' }}"
                        href="{{ url('your-bookings') }}">Your Bookings</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ Request::is('about-facilities', 'about-equipment', 'about-services') ? 'active' : '' }}"
                        href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        About Services
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item {{ Request::is('about-facilities') ? 'active' : '' }}"
                                href="{{ url('about-facilities') }}">About Facilities</a></li>
                        <li><a class="dropdown-item {{ Request::is('about-equipment') ? 'active' : '' }}"
                                href="{{ url('about-equipment') }}">About Equipment</a></li>
                        <li><a class="dropdown-item {{ Request::is('about-services') ? 'active' : '' }}"
                                href="{{ url('about-services') }}">Extra Services</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('policies') ? 'active' : '' }}" href="{{ url('policies') }}">Our
                        Policies</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('user-feedback') ? 'active' : '' }}"
                        href="{{ url('user-feedback') }}">Rate Services</a>
                </li>
            </ul>

            <div class="d-flex align-items-center ms-lg-3">
                <span class="me-2 how-to-book d-flex align-items-center" data-bs-toggle="tooltip"
                    data-bs-placement="bottom" data-bs-custom-class="custom-tooltip" title="1. Browse the catalog and add venues or equipment to your booking cart.
2. Go to the reservation form via 'Book Now' or your cart.
3. Fill in required booking data and check item availability for your timeslot.
4. Read reservation policies before submitting.">
                    How to book?
                    <i class="bi bi-question-circle ms-1" style="font-size: 0.85rem;"></i>
                </span>

                @if(Route::currentRouteName() === 'reservation.form' || Request::is('reservation-form'))
                    <a href="{{ url('booking-catalog') }}" class="btn btn-book-now">Back to Catalog</a>
                @else
                    <a href="{{ url('reservation-form') }}" class="btn btn-book-now">Book Now</a>
                @endif
            </div>
        </div>
    </div>
</nav>

<script>
    // Initialize Bootstrap tooltips with body container
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el, { container: 'body' });
    });

    // Initialize Bootstrap dropdowns
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownElements = document.querySelectorAll('.dropdown-toggle');
        dropdownElements.forEach(dropdown => {
            new bootstrap.Dropdown(dropdown);
        });
    });
</script>