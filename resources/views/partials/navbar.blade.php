<style>
    /* Your existing root variables are already in your main CSS file */

    /* Top Header Bar - Using CPU Blue */
    .top-header-bar {
        background-color: var(--cpu-primary);
        color: var(--cpu-white);
        padding: 10px 0;
    }

    .top-header-bar .cpu-brand {
        display: flex;
        align-items: center;
    }

    .top-header-bar .cpu-brand img {
        height: 60px;
        margin-right: 15px;
    }

    .top-header-bar .cpu-brand .title {
        font-size: 1.5rem;
        line-height: 1.2;
        color: var(--cpu-white);
    }

    .top-header-bar .cpu-brand .subtitle {
        font-size: 1rem;
        line-height: 1.2;
        color: var(--cpu-white);
    }

    .top-header-bar .admin-login {
        color: var(--cpu-white);
    }

    .top-header-bar .admin-login a {
        color: var(--cpu-white);
        text-decoration: underline;
    }

    .top-header-bar .admin-login a:hover {
        color: var(--cpu-secondary);
    }

    /* Main Navbar - Using Light BG with Gold Border */
    .main-navbar {
        background-color: var(--cpu-light-bg);
        border-bottom: 3px solid var(--cpu-border-accent);
        padding: 0.5rem 0;
        box-shadow: 0 2px 5px var(--cpu-shadow);
        position: sticky;
        top: 0;
        z-index: 2000;
    }

    .main-navbar .nav-link {
        color: var(--cpu-primary);
        padding: 0.5rem 1rem;
        font-size: 0.95rem;
        transition: color 0.2s ease-in-out;
    }

    .main-navbar .nav-link:hover,
    .main-navbar .nav-link.active {
        color: var(--cpu-secondary-hover);
        background-color: transparent;
    }

    /* Dropdown Menu Styling */
    .main-navbar .dropdown-menu {
        background-color: var(--cpu-white);
        border: 1px solid var(--cpu-border-accent);
        border-radius: 0;
        margin-top: 0;
        padding: 0.5rem 0;
        box-shadow: 0 4px 8px var(--cpu-shadow);
    }

    .main-navbar .dropdown-item {
        color: var(--cpu-primary);
        padding: 0.5rem 1.5rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .main-navbar .dropdown-item:hover,
    .main-navbar .dropdown-item:focus {
        background-color: var(--cpu-primary-light);
        color: var(--cpu-secondary-hover);
    }

    .main-navbar .dropdown-item.active {
        background-color: var(--cpu-primary);
        color: var(--cpu-white);
    }

    /* How to book trigger text */
    .navbar .how-to-book {
        font-size: 0.85rem;
        cursor: pointer;
        text-decoration: underline;
        color: var(--cpu-primary);
        white-space: nowrap;
    }

    .navbar .how-to-book i {
        color: var(--cpu-primary);
    }

    .navbar .how-to-book:hover {
        color: var(--cpu-secondary-hover);
    }

    .how-to-book:hover i {
        color: var(--cpu-secondary-hover);
        /* or whatever color you want */
    }

    /* If you want the icon to change color when hovering over just the icon as well */
    .how-to-book i:hover {
        color: var(--cpu-secondary-hover);
    }

    /* Book Now Button */
    .main-navbar .btn-book-now {
        background-color: var(--cpu-secondary);
        color: var(--cpu-primary);
        border: none;
        font-weight: bold;
        padding: 0.5rem 1.5rem;
        border-radius: 5px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px var(--cpu-shadow);
    }

    .main-navbar .btn-book-now:hover {
        background-color: var(--cpu-secondary-hover);
        color: var(--cpu-primary);
        box-shadow: 0 4px 8px var(--cpu-shadow);
    }

    /* Tooltip Styling */

    .tooltip {
        z-index: 2500 !important;
    }

    .tooltip-inner {
        background-color: #000000d2;
        color: var(--cpu-white);
        font-size: 0.85rem;
        padding: 0.75rem 0.75rem;
        line-height: 1.2;
        max-width: 300px;
        text-align: left;
        white-space: pre-line;
    }

    .tooltip-arrow::before {
        border-bottom-color: #000000d2;
    }


    /* Mobile Responsive Styles */
    @media (max-width: 768px) {

        /* Force header background */
        .top-header-bar {
            background-color: var(--cpu-primary) !important;
        }

        .top-header-bar .container {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px;
        }

        .top-header-bar .cpu-brand {
            width: 100%;
        }

        .top-header-bar .cpu-brand img {
            height: 50px;
            margin-right: 12px;
        }

        .top-header-bar .cpu-brand .title {
            font-size: 1.2rem;
            color: var(--cpu-white) !important;
        }

        .top-header-bar .cpu-brand .subtitle {
            font-size: 0.85rem;
            color: var(--cpu-white) !important;
        }

        .top-header-bar .admin-login {
            width: 100%;
            text-align: left;
            font-size: 0.85rem;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--cpu-white) !important;
        }

        /* Force navbar background */
        .main-navbar {
            background-color: var(--cpu-light-bg) !important;
            padding: 0.25rem 0;
        }

        .main-navbar .container {
            padding-left: 15px;
            padding-right: 15px;
        }

        .main-navbar .navbar-nav {
            padding: 15px 0;
            background-color: var(--cpu-light-bg) !important;
        }

        .main-navbar .nav-item {
            width: 100%;
            text-align: left;
        }

        .main-navbar .nav-link {
            padding: 12px 0 !important;
            border-bottom: 1px solid var(--cpu-border-accent);
            color: var(--cpu-primary) !important;
        }

        /* Dropdown mobile background */
        .main-navbar .dropdown-menu {
            width: 100%;
            border: none;
            background-color: var(--cpu-primary-light) !important;
            padding: 0 0 0 15px !important;
            margin: 0;
            box-shadow: none;
        }

        .main-navbar .dropdown-item {
            padding: 8px 15px;
            white-space: normal;
            font-size: 0.9rem;
            color: var(--cpu-primary) !important;
        }

        .main-navbar .dropdown-item:hover {
            background-color: transparent;
            color: var(--cpu-secondary-hover) !important;
        }

        /* Force Book Now section background */
        .d-flex.align-items-center.ms-lg-3 {
            flex-direction: row;
            justify-content: space-between;
            width: 100%;
            margin-left: 0 !important;
            padding: 15px 0 5px;
            gap: 10px;
            border-top: 1px solid var(--cpu-border-accent);
            background-color: var(--cpu-light-bg) !important;
        }

        .navbar .how-to-book {
            font-size: 0.8rem;
            white-space: normal;
            color: var(--cpu-primary) !important;
        }

        .navbar .how-to-book i {
            color: var(--cpu-primary) !important;
        }

        .main-navbar .btn-book-now {
            padding: 8px 20px;
            font-size: 0.9rem;
            white-space: nowrap;
            background-color: var(--cpu-secondary) !important;
            color: var(--cpu-primary) !important;
        }

        /* Tooltip mobile adjustments */
        .custom-tooltip .tooltip-inner {
            max-width: 250px;
            font-size: 0.75rem;
            padding: 0.6rem;
            background-color: #000000d2 !important;
        }

        /* Ensure collapsed menu has background */
        .navbar-collapse {
            background-color: var(--cpu-light-bg) !important;
        }
    }

    /* Extra small devices */
    @media (max-width: 480px) {
        .top-header-bar {
            background-color: var(--cpu-primary) !important;
        }

        .top-header-bar .cpu-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .top-header-bar .cpu-brand .title {
            font-size: 1rem;
            color: var(--cpu-white) !important;
        }

        .top-header-bar .cpu-brand .subtitle {
            font-size: 0.75rem;
            color: var(--cpu-white) !important;
        }

        .d-flex.align-items-center.ms-lg-3 {
            flex-wrap: wrap;
            background-color: var(--cpu-light-bg) !important;
        }

        .navbar .how-to-book {
            width: 100%;
            margin-bottom: 10px;
            justify-content: center;
            text-align: center;
            color: var(--cpu-primary) !important;
        }

        .main-navbar .btn-book-now {
            width: 100%;
            text-align: center;
        }

        /* Ensure collapsed menu background on small devices */
        .navbar-collapse {
            background-color: var(--cpu-light-bg) !important;
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
                        <li><a class="dropdown-item {{ Request::is('inquiries') ? 'active' : '' }}"
                                href="{{ url('inquiries') }}">Booking Guidelines</a></li>
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
                    <i class="bi bi-question-circle ms-1" style="font-size: 0.9rem;"></i>
                </span>



                <a href="{{ url('reservation-form') }}" class="btn btn-book-now">Book Now</a>
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