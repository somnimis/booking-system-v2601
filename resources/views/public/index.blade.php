@extends('layouts.app')

@section('title', 'CPU Facility & Equipment Booking Services')

@section('content')
  <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
  <style>
    :root {
      --glass-bg: rgba(255, 255, 255, 0.05);
      --glass-border: rgba(255, 255, 255, 0.15);
      --cpu-gold: #e8b342;
      --cpu-gold-hover: #f7c55f;
      --cpu-blue: #003366;
      --cpu-blue-hover: #004a94;
    }

    body {
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
        url("{{ asset('assets/homepage.jpg') }}");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: #fff;
    }

    /* Modern Hero Section */
    .hero-section {
      min-height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      opacity: 0;
      transition: all 1s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    .hero-section.visible {
      opacity: 1;
    }

    .hero-section h1 {
      font-size: clamp(2.5rem, 5vw, 4rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      line-height: 1.1;
      margin-bottom: 1.5rem;
    }

    /* Glass Panels for Sections */
    .catalog-section-container {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 30px;
      padding: 3rem;
      margin-bottom: 4rem;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .section-title-area h2 {
      font-weight: 700;
      letter-spacing: -0.5px;
      position: relative;
      display: inline-block;
      color: white;
    }

    .section-title-area h2::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 50px;
      height: 4px;
      background: var(--cpu-gold);
      border-radius: 2px;
    }

    .section-title-area p {
      color: rgba(255, 255, 255, 0.7) !important;
    }

    .section-header-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }

    .section-title-area {
      margin-bottom: 0;
    }

    /* Buttons */
    .btn-gold {
      background: linear-gradient(135deg, var(--cpu-gold), #f3c969);
      color: #111;
      border-radius: 14px;
      border: 1px solid rgba(0, 0, 0, 0.08);
      font-weight: 600;
      letter-spacing: 0.3px;
      padding: 0.6rem 1.4rem;
      box-shadow: 0 6px 16px rgba(232, 179, 66, 0.25);
      transition: all 0.25s ease;
      position: relative;
      overflow: hidden;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-gold:hover {
      background: linear-gradient(135deg, var(--cpu-gold-hover), #f7d47a);
      box-shadow: 0 12px 28px rgba(232, 179, 66, 0.35);
      color: #111;
    }

    .btn-gold:active {
      box-shadow: 0 6px 14px rgba(232, 179, 66, 0.2);
    }

    .btn-gold:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(232, 179, 66, 0.25);
    }

    .btn-outline-light {
      border-radius: 14px;
      padding: 0.6rem 1.4rem;
      font-weight: 600;
      border: 2px solid rgba(255, 255, 255, 0.3);
      color: white;
      transition: all 0.25s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-outline-light:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.5);
      color: white;
    }

    /* Category Cards */
    .category-card {
      display: flex;
      flex-direction: column;
      height: 100%;

      background: rgba(255, 255, 255, 0.9);
      border: none;
      border-radius: 20px;
      overflow: hidden;
      color: #333;
      cursor: pointer;

      opacity: 0;
      transform: translateY(24px);

      transition:
        opacity .5s ease,
        transform .35s ease,
        box-shadow .35s ease;
    }

    .category-card.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 14px 30px rgba(0, 0, 0, .18);
    }

    .category-card .card-img-top {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .category-card .card-body {
      display: flex;
      flex-direction: column;
      flex: 1;
      padding: 1.5rem;
    }

    .category-card .card-title {
      margin-bottom: .75rem;
      font-weight: 700;
      color: #1a1a2e;
    }

    .category-card .card-text {
      color: #6c757d;
      line-height: 1.6;
    }

    .card-link-arrow {
      display: inline-flex;
      align-items: center;
      gap: .5rem;

      margin-top: auto;
      padding-top: 1rem;

      font-size: .9rem;
      font-weight: 600;
      color: var(--cpu-blue);
      text-decoration: none;

      transition:
        color .25s ease,
        transform .25s ease;
    }

    .card-link-arrow:hover {
      color: var(--cpu-blue-hover);
    }

    .card-link-arrow i {
      transition: transform .25s ease;
    }

    .category-card:hover .card-link-arrow i {
      transform: translateX(3px);
    }

    /* Stagger delays */
    .stagger-1 {
      transition-delay: .04s;
    }

    .stagger-2 {
      transition-delay: .08s;
    }

    .stagger-3 {
      transition-delay: .12s;
    }

    .stagger-4 {
      transition-delay: .16s;
    }

    .stagger-5 {
      transition-delay: .20s;
    }

    .stagger-6 {
      transition-delay: .24s;
    }

    /* Toast Styles */
    .toast-container {
      z-index: 3000;
    }

    #storageConsentToast {
      width: 380px;
      background: rgba(20, 20, 20, 0.75);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, .08);
      border-radius: 1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hero-section {
        min-height: 50vh;
      }

      .catalog-section-container {
        padding: 1.5rem;
        margin-bottom: 2rem;
      }

      .section-header-flex {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }

      .category-card .card-img-top {
        height: 160px;
      }
    }

    @media (max-width: 576px) {
      .hero-section h1 {
        font-size: 2rem;
      }

      .catalog-section-container {
        padding: 1rem;
        border-radius: 20px;
      }

      .category-card .card-img-top {
        height: 140px;
      }
    }

    /* Utility */
    .text-white-50 {
      color: rgba(255, 255, 255, 0.7) !important;
    }

    .category-card {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .category-card .card-body {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .card-link-arrow {
      margin-top: auto;
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
        <a href="/booking-catalog" class="btn btn-gold px-4 py-2">
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
                <a href="/booking-catalog?type=conference" class="card-link-arrow">
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
                <a href="/booking-catalog?type=lecture" class="card-link-arrow">
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
                <a href="/booking-catalog?type=auditorium" class="card-link-arrow">
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
                <a href="/booking-catalog?type=sports" class="card-link-arrow">
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
              <a href="/booking-catalog?type=library" class="card-link-arrow">
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
                <a href="/booking-catalog?type=computer-lab" class="card-link-arrow">
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
        <a href="/booking-catalog" class="btn btn-gold px-4 py-2">
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
                <a href="/booking-catalog?category=audio" class="card-link-arrow">
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
                <a href="/booking-catalog?category=visual" class="card-link-arrow">
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
                <a href="/booking-catalog?category=lighting" class="card-link-arrow">
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
                <a href="/booking-catalog?category=conference" class="card-link-arrow">
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
                <a href="/booking-catalog?category=event" class="card-link-arrow">
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
                <a href="/booking-catalog?category=it" class="card-link-arrow">
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