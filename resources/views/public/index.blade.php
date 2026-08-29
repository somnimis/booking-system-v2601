@extends('layouts.app')

@section('title', 'CPU Facility & Equipment Booking Services')

@section('content')
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/public/index.css') }}" />
  <style>
    body {
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
        url("{{ asset('assets/homepage.jpg') }}");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
  </style>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="col-lg-8 mx-auto">
        <h2 class="fw-bold">
          Simplify the way you book campus facilities, equipment, and services — all in one platform, anytime, anywhere.
        </h2>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
          <a href="/booking-catalog" class="btn btn-gold btn-lg px-5 py-3 fw-bold">
            Start Booking <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <div class="container pb-5">
    <!-- ============ FACILITIES SECTION ============ -->
    <div id="facilities" class="catalog-section-container">
      <div class="section-header-flex">
        <div class="section-title-area">
          <h2>Facilities</h2>
          <p class="mt-2">Explore a range of facilities designed to host events, meetings, trainings, and recreational
            activities with ease.</p>
        </div>
        <a href="/booking-catalog?tab=venues" class="btn btn-gold px-4 py-2">
          View All Venues <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="row g-4">
        <!-- Facility 1: Conference Rooms -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=rooms" class="text-decoration-none">
            <div class="category-card stagger-1">
              <img src="{{ asset('assets/frontend-pics/facilities/conference-room.jpeg') }}" class="card-img-top"
                alt="Conference Room">
              <div class="card-body">
                <h5 class="card-title">Conference &amp; Meeting Rooms</h5>
                <p class="card-text">Well-equipped spaces for group discussions, seminars, and formal meetings.</p>
                <a href="/booking-catalog?tab=rooms" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Facility 2: Lecture Halls -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=rooms" class="text-decoration-none">
            <div class="category-card stagger-2">
              <img src="{{ asset('assets/frontend-pics/facilities/lecture-hall.jpg') }}" class="card-img-top"
                alt="Lecture Hall">
              <div class="card-body">
                <h5 class="card-title">Lecture &amp; Training Halls</h5>
                <p class="card-text">Spacious venues ideal for lectures, presentations, and workshops.</p>
                <a href="/booking-catalog?tab=rooms" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Facility 3: Auditoriums -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=venues" class="text-decoration-none">
            <div class="category-card stagger-3">
              <img src="{{ asset('assets/frontend-pics/facilities/auditorium.jpg') }}" class="card-img-top"
                alt="Auditorium">
              <div class="card-body">
                <h5 class="card-title">Auditoriums</h5>
                <p class="card-text">Large venues designed for conferences, ceremonies, and cultural events.</p>
                <a href="/booking-catalog?tab=venues" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Facility 4: Sports Facilities -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=venues" class="text-decoration-none">
            <div class="category-card stagger-4">
              <img src="{{ asset('assets/frontend-pics/facilities/court.jpg') }}" class="card-img-top" alt="Gym Court">
              <div class="card-body">
                <h5 class="card-title">Sports &amp; Gym Facilities</h5>
                <p class="card-text">Multipurpose gyms and courts for athletic events, exhibitions, and student
                  activities.
                </p>
                <a href="/booking-catalog?tab=venues" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Facility 5: Libraries -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=rooms" class="text-decoration-none"></a>
          <div class="category-card stagger-5">
            <img src="{{ asset('assets/frontend-pics/facilities/study-area.jpg') }}" class="card-img-top" alt="Libraries">
            <div class="card-body">
              <h5 class="card-title">Libraries &amp; Study Areas</h5>
              <p class="card-text">Quiet spaces designed for research, study sessions, and academic gatherings.</p>
              <a href="/booking-catalog?tab=rooms" class="card-link-arrow">
                Explore <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
          </a>
        </div>

        <!-- Facility 6: Computer Labs -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=rooms" class="text-decoration-none">
            <div class="category-card stagger-6">
              <img src="{{ asset('assets/frontend-pics/facilities/comp-lab.jpg') }}" class="card-img-top"
                alt="Computer Laboratories">
              <div class="card-body">
                <h5 class="card-title">Computer Laboratories</h5>
                <p class="card-text">Fully equipped labs for IT classes, training sessions, and technical workshops.</p>
                <a href="/booking-catalog?tab=rooms" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- ============ EQUIPMENT SECTION ============ -->
    <div id="equipment" class="catalog-section-container">
      <div class="section-header-flex">
        <div class="section-title-area">
          <h2>Equipment</h2>
          <p class="mt-2">Choose from a range of equipment categories designed to support academic, technical, and
            event-related needs.</p>
        </div>
        <a href="/booking-catalog?tab=equipment" class="btn btn-gold px-4 py-2">
          View All Equipment <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="row g-4">
        <!-- Equipment 1: Audio -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-1">
              <img src="{{ asset('assets/frontend-pics/equipment/audio.jpg') }}" class="card-img-top"
                alt="Audio Equipment">
              <div class="card-body">
                <h5 class="card-title">Audio Equipment</h5>
                <p class="card-text">Sound systems, microphones, speakers, and other audio support devices for events and
                  presentations.</p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Equipment 2: Visual -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-2">
              <img src="{{ asset('assets/frontend-pics/equipment/visual.webp') }}" class="card-img-top"
                alt="Visual Equipment">
              <div class="card-body">
                <h5 class="card-title">Visual Equipment</h5>
                <p class="card-text">Projectors, screens, and display systems for lectures, meetings, and visual
                  presentations.</p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Equipment 3: Lighting -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-3">
              <img src="{{ asset('assets/frontend-pics/equipment/lighting.jpg') }}" class="card-img-top"
                alt="Lighting Equipment">
              <div class="card-body">
                <h5 class="card-title">Lighting Equipment</h5>
                <p class="card-text">Stage lights, spotlights, and adjustable lighting systems for indoor or outdoor
                  events.
                </p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Equipment 4: Conference -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-4">
              <img src="{{ asset('assets/frontend-pics/equipment/conference.jpg') }}" class="card-img-top"
                alt="Conference Equipment">
              <div class="card-body">
                <h5 class="card-title">Conference Equipment</h5>
                <p class="card-text">Conference tools including microphones, display panels, and accessories for meetings.
                </p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Equipment 5: Event -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-5">
              <img src="{{ asset('assets/frontend-pics/equipment/event.png') }}" class="card-img-top"
                alt="Event Equipment">
              <div class="card-body">
                <h5 class="card-title">Event Equipment</h5>
                <p class="card-text">Essential event tools such as staging materials, podiums, and other event support
                  gear.
                </p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>

        <!-- Equipment 6: IT -->
        <div class="col-lg-4 col-md-6">
          <a href="/booking-catalog?tab=equipment" class="text-decoration-none">
            <div class="category-card stagger-6">
              <img src="{{ asset('assets/frontend-pics/equipment/it.jpg') }}" class="card-img-top" alt="IT Equipment">
              <div class="card-body">
                <h5 class="card-title">IT Equipment</h5>
                <p class="card-text">Laptops, computers, and communication devices for academic, research, and technical
                  use.</p>
                <a href="/booking-catalog?tab=equipment" class="card-link-arrow">
                  Explore <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Storage Consent Toast -->
  <div class="toast-container position-fixed bottom-0 start-0 p-4" style="z-index: 3000;">
    <div id="storageConsentToast" class="toast border-0 shadow-lg text-white" role="alert" aria-live="assertive"
      aria-atomic="true" data-bs-autohide="false" style="
                          width: 380px;
                          background: rgba(20, 20, 20, 0.75);
                          backdrop-filter: blur(16px);
                          -webkit-backdrop-filter: blur(16px);
                          border: 1px solid rgba(255,255,255,.08);
                          border-radius: 1rem;
                      ">

      <div class="toast-body p-4">
        <h6 class="fw-semibold mb-2">
          We value your privacy
        </h6>
        <p class="small text-white-50 mb-4">
          We use cookies and your browser's local storage to remember your activity,
          such as your cart and form progress, so you can continue where you left off.
        </p>
        <div class="d-flex gap-2 justify-content-end">
          <button type="button" class="btn btn-outline-light btn-sm px-3" data-bs-dismiss="toast" id="closeToastBtn">
            Later
          </button>
          <button type="button" class="btn btn-light btn-sm px-3 fw-semibold" id="acceptStorageBtn">
            I Understand
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap Icons (if not already included) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Hero section animation
      const heroSection = document.querySelector(".hero-section");
      if (heroSection) {
        new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add("visible");
              obs.unobserve(entry.target);
            }
          });
        }, { threshold: 0.2 }).observe(heroSection);
      }

      // Intersection Observer for category cards
      const observerOptions = {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
      };

      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
          }
        });
      }, observerOptions);

      // Observe all category cards
      document.querySelectorAll('.category-card').forEach(function (el) {
        observer.observe(el);
      });

      // Toast consent
      const hasSeenStorageToast = localStorage.getItem('storage_consent_seen');
      const toastElement = document.getElementById('storageConsentToast');

      if (!hasSeenStorageToast && toastElement) {
        setTimeout(() => new bootstrap.Toast(toastElement, { autohide: false }).show(), 1500);
      }

      document.getElementById('acceptStorageBtn')?.addEventListener('click', () => {
        localStorage.setItem('storage_consent_seen', 'true');
        bootstrap.Toast.getInstance(toastElement)?.hide();
      });

      document.getElementById('closeToastBtn')?.addEventListener('click', () => {
        localStorage.setItem('storage_consent_seen', 'true');
      });
    });
  </script>
@endsection