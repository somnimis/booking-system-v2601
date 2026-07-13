@extends('layouts.app')

@section('title', 'About Services - Facilities, Equipment & More')

@section('content')
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}">

    <style>
        /* Base Styles */
        body {
            background: #f5f5f7;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 2rem;
            background: url('{{ asset('assets/homepage.jpg') }}') center center / cover no-repeat;
            overflow: hidden;
            color: #fff;
            text-align: center;
        }

        .hero-section::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }

        .hero-section>* {
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            font-size: clamp(2.8rem, 6vw, 4.5rem);
            font-weight: 700;
            letter-spacing: -.03em;
            margin-bottom: .75rem;
        }

        .hero-section .subtitle {
            max-width: 700px;
            color: rgba(255, 255, 255, .85);
            margin-bottom: 0;
            font-size: 1.15rem;
        }


        @keyframes bounce {
            0%,
            100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(10px);
            }
        }

        /* Section Content - Main Container */
        .section-content {
            position: relative;
            margin-top: 40px;
            padding-bottom: 5rem;
            z-index: 2;
        }

        /* Category Headers */
        .category-header {
            text-align: center;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .category-header.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .category-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: .5rem;
            letter-spacing: -.02em;
        }

        .category-header .category-line {
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #4a6cf7, #6a3de8);
            border-radius: 2px;
            margin: 0.75rem auto;
        }

        .category-header p {
            color: #6c757d;
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Section Divider */
        .section-divider {
            margin: 4rem 0 3rem;
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.2s;
        }

        .section-divider.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .section-divider .divider-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4a6cf7, #6a3de8);
            border-radius: 50%;
            color: #fff;
            font-size: 1.3rem;
            box-shadow: 0 8px 25px rgba(74, 108, 247, .3);
        }

        .section-divider .divider-line {
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent, #dee2e6, transparent);
            margin: 0 1rem;
        }

        .section-divider .divider-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Cards - Shared Styles */
        .service-card,
        .facility-card,
        .equipment-card {
            background: rgba(255, 255, 255, .75);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, .6);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .08);
            transition: transform .35s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                box-shadow .35s ease,
                border-color .35s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            opacity: 0;
            transform: translateY(40px) scale(0.98);
            transition: opacity 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                box-shadow .35s ease,
                border-color .35s ease;
        }

        .service-card.visible,
        .facility-card.visible,
        .equipment-card.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .service-card:hover,
        .facility-card:hover,
        .equipment-card:hover {
            transform: translateY(-3px);
            border-color: #f5f5f7;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .12);
        }

        .service-card img,
        .facility-card img,
        .equipment-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .service-card .card-body,
        .facility-card .card-body,
        .equipment-card .card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .service-card h5,
        .facility-card h5,
        .equipment-card h5 {
            font-weight: 700;
            margin-bottom: .75rem;
            color: #1a1a2e;
        }

        .service-card h5 a,
        .facility-card h5 a,
        .equipment-card h5 a {
            color: #1a1a2e;
            text-decoration: none;
            transition: color .3s ease;
        }

        .service-card h5 a:hover,
        .facility-card h5 a:hover,
        .equipment-card h5 a:hover {
            color: #222222;
        }

        .service-card p,
        .facility-card p,
        .equipment-card p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 0;
            flex-grow: 1;
        }

        .card-link-arrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            font-weight: 600;
            color: #4a6cf7;
            text-decoration: none;
            font-size: 0.9rem;
            transition: gap .3s ease;
        }

        .card-link-arrow:hover {
            gap: 0.8rem;
            color: #3a56d4;
        }

        .card-link-arrow i {
            font-size: 0.8rem;
        }

        /* Stagger delays for cards */
        .stagger-1 {
            transition-delay: 0.05s;
        }
        .stagger-2 {
            transition-delay: 0.1s;
        }
        .stagger-3 {
            transition-delay: 0.15s;
        }
        .stagger-4 {
            transition-delay: 0.2s;
        }
        .stagger-5 {
            transition-delay: 0.25s;
        }
        .stagger-6 {
            transition-delay: 0.3s;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 240px;
                padding: 2rem 1.5rem;
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .section-content {
                margin-top: -30px;
            }

            .category-header h2 {
                font-size: 1.8rem;
            }

            .service-card img,
            .facility-card img,
            .equipment-card img {
                height: 170px;
            }

            .section-divider {
                margin: 2.5rem 0;
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                min-height: 200px;
                padding: 1.5rem 1rem;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section .subtitle {
                font-size: 1rem;
            }

            .section-content {
                margin-top: -20px;
                padding-bottom: 3rem;
            }

            .service-card img,
            .facility-card img,
            .equipment-card img {
                height: 140px;
            }

            .service-card .card-body,
            .facility-card .card-body,
            .equipment-card .card-body {
                padding: 1.25rem;
            }
        }
    </style>

    <!-- Hero Section -->
    <section class="hero-section">
        <h1>Our Services</h1>
        <p class="subtitle">
            Explore our comprehensive range of facilities, equipment, and support services
            designed to make your events, meetings, and activities seamless and memorable.
        </p>
    </section>

    <!-- Main Content -->
    <section class="section-content">
        <div class="container">

            <!-- ============ FACILITIES SECTION ============ -->
            <div class="category-header" id="facilities">
                <h2>Facilities</h2>
                <div class="category-line"></div>
                <p>Explore a range of facilities designed to host events, meetings, trainings, and recreational activities with ease.</p>
            </div>

            <div class="row g-4">
                <!-- Facility 1: Conference Rooms -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-1">
                        <img src="{{ asset('assets/frontend-pics/facilities/conference-room.jpeg') }}" class="card-img-top" alt="Conference Room">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Conference &amp; Meeting Rooms</a></h5>
                            <p>Well-equipped spaces for group discussions, seminars, and formal meetings.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Facility 2: Lecture Halls -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-2">
                        <img src="{{ asset('assets/frontend-pics/facilities/lecture-hall.jpg') }}" class="card-img-top" alt="Lecture Hall">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Lecture &amp; Training Halls</a></h5>
                            <p>Spacious venues ideal for lectures, presentations, and workshops.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Facility 3: Auditoriums -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-3">
                        <img src="{{ asset('assets/frontend-pics/facilities/auditorium.jpg') }}" class="card-img-top" alt="Auditorium">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Auditoriums</a></h5>
                            <p>Large venues designed for conferences, ceremonies, and cultural events.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Facility 4: Sports Facilities -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-4">
                        <img src="{{ asset('assets/frontend-pics/facilities/court.jpg') }}" class="card-img-top" alt="Gym Court">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Sports &amp; Gym Facilities</a></h5>
                            <p>Multipurpose gyms and courts for athletic events, exhibitions, and student activities.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Facility 5: Libraries -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-5">
                        <img src="{{ asset('assets/frontend-pics/facilities/study-area.jpg') }}" class="card-img-top" alt="Libraries">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Libraries &amp; Study Areas</a></h5>
                            <p>Quiet spaces designed for research, study sessions, and academic gatherings.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Facility 6: Computer Labs -->
                <div class="col-lg-4 col-md-6">
                    <div class="facility-card stagger-6">
                        <img src="{{ asset('assets/frontend-pics/facilities/comp-lab.jpg') }}" class="card-img-top" alt="Computer Laboratories">
                        <div class="card-body">
                            <h5><a href="{{ url('/booking-catalog') }}">Computer Laboratories</a></h5>
                            <p>Fully equipped labs for IT classes, training sessions, and technical workshops.</p>
                            <a href="{{ url('/booking-catalog') }}" class="card-link-arrow">
                                Book Now <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ DIVIDER ============ -->
            <div class="section-divider">
                <div class="divider-wrapper">
                    <span class="divider-line"></span>
                    <span class="divider-icon">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <span class="divider-line"></span>
                </div>
            </div>

            <!-- ============ EQUIPMENT SECTION ============ -->
            <div class="category-header" id="equipment">
                <h2>Equipment</h2>
                <div class="category-line"></div>
                <p>Choose from a range of equipment categories designed to support academic, technical, and event-related needs.</p>
            </div>

            <div class="row g-4">
                <!-- Equipment 1: Audio -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-1">
                        <img src="{{ asset('assets/frontend-pics/equipment/audio.jpg') }}" class="card-img-top" alt="Audio Equipment">
                        <div class="card-body">
                            <h5>Audio Equipment</h5>
                            <p>Sound systems, microphones, speakers, and other audio support devices for events and presentations.</p>
                        </div>
                    </div>
                </div>

                <!-- Equipment 2: Visual -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-2">
                        <img src="{{ asset('assets/frontend-pics/equipment/visual.webp') }}" class="card-img-top" alt="Visual Equipment">
                        <div class="card-body">
                            <h5>Visual Equipment</h5>
                            <p>Projectors, screens, and display systems for lectures, meetings, and visual presentations.</p>
                        </div>
                    </div>
                </div>

                <!-- Equipment 3: Lighting -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-3">
                        <img src="{{ asset('assets/frontend-pics/equipment/lighting.jpg') }}" class="card-img-top" alt="Lighting Equipment">
                        <div class="card-body">
                            <h5>Lighting Equipment</h5>
                            <p>Stage lights, spotlights, and adjustable lighting systems for indoor or outdoor events.</p>
                        </div>
                    </div>
                </div>

                <!-- Equipment 4: Conference -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-4">
                        <img src="{{ asset('assets/frontend-pics/equipment/conference.jpg') }}" class="card-img-top" alt="Conference Equipment">
                        <div class="card-body">
                            <h5>Conference Equipment</h5>
                            <p>Conference tools including microphones, display panels, and accessories for meetings.</p>
                        </div>
                    </div>
                </div>

                <!-- Equipment 5: Event -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-5">
                        <img src="{{ asset('assets/frontend-pics/equipment/event.png') }}" class="card-img-top" alt="Event Equipment">
                        <div class="card-body">
                            <h5>Event Equipment</h5>
                            <p>Essential event tools such as staging materials, podiums, and other event support gear.</p>
                        </div>
                    </div>
                </div>

                <!-- Equipment 6: IT -->
                <div class="col-lg-4 col-md-6">
                    <div class="equipment-card stagger-6">
                        <img src="{{ asset('assets/frontend-pics/equipment/it.jpg') }}" class="card-img-top" alt="IT Equipment">
                        <div class="card-body">
                            <h5>IT Equipment</h5>
                            <p>Laptops, computers, and communication devices for academic, research, and technical use.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ DIVIDER ============ -->
            <div class="section-divider">
                <div class="divider-wrapper">
                    <span class="divider-line"></span>
                    <span class="divider-icon">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <span class="divider-line"></span>
                </div>
            </div>

            <!-- ============ SERVICES (EXTRA) SECTION ============ -->
            <div class="category-header" id="services">
                <h2>Extra Services</h2>
                <div class="category-line"></div>
                <p>Access a range of support services designed to enhance event coordination, technical setup, and on-site management.</p>
            </div>

            <div class="row g-4">
                <!-- Service 1: Security -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card stagger-1">
                        <img src="{{ asset('assets/frontend-pics/services/security.jpg') }}" class="card-img-top" alt="Security Personnel">
                        <div class="card-body">
                            <h5>Security Personnel</h5>
                            <p>Professional staff to maintain safety, monitor access, and assist with crowd management throughout your event.</p>
                        </div>
                    </div>
                </div>

                <!-- Service 2: Technical Support -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card stagger-2">
                        <img src="{{ asset('assets/frontend-pics/services/tech-support.jpg') }}" class="card-img-top" alt="Technical Support">
                        <div class="card-body">
                            <h5>Technical Support</h5>
                            <p>Assistance with projectors, microphones, sound systems, and other technical equipment before and during your event.</p>
                        </div>
                    </div>
                </div>

                <!-- Service 3: Logistics -->
                <div class="col-lg-4 col-md-6">
                    <div class="service-card stagger-3">
                        <img src="{{ asset('assets/frontend-pics/services/logistics.jpg') }}" class="card-img-top" alt="Logistics Assistance">
                        <div class="card-body">
                            <h5>Logistics Assistance</h5>
                            <p>Support with venue preparation, equipment arrangement, and overall event coordination for a smooth event experience.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- JavaScript for Scroll Animations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer for fade-in animations
            const observerOptions = {
                threshold: 0.15,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        // Optionally unobserve after animation to improve performance
                        // observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observe all category headers
            document.querySelectorAll('.category-header').forEach(function(el) {
                observer.observe(el);
            });

            // Observe all cards
            document.querySelectorAll('.service-card, .facility-card, .equipment-card').forEach(function(el) {
                observer.observe(el);
            });

            // Observe section dividers
            document.querySelectorAll('.section-divider').forEach(function(el) {
                observer.observe(el);
            });
        });
    </script>

    <!-- Bootstrap Icons (if not already included) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection