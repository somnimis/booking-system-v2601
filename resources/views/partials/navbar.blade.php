<div id="cpu-navbar-root">
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
                        <a class="nav-link {{ Request::is('home') ? 'active' : '' }}"
                            href="{{ url('home') }}">Home</a>
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
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('policies') ? 'active' : '' }}"
                            href="{{ url('policies') }}">Our
                            Policies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('user-feedback') ? 'active' : '' }}"
                            href="{{ url('user-feedback') }}">Rate Services</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center ms-lg-3">
                    <a href="{{ route('user-guide') }}"
                        class="me-2 how-to-book d-flex align-items-center text-decoration-none">
                        How to book?
                        <i class="bi bi-question-circle ms-1" style="font-size: 0.85rem;"></i>
                    </a>
                </div>

                @if (Route::currentRouteName() === 'reservation.form' || Request::is('reservation-form'))
                    <a href="{{ url('booking-catalog') }}" class="btn btn-book-now">Back to Catalog</a>
                @else
                    <a href="{{ url('reservation-form') }}" class="btn btn-book-now">Book Now</a>
                @endif
            </div>
        </div>
</div>
</div>
</nav>
