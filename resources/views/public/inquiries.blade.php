@extends('layouts.app')

@section('title', 'CPU Booking Policies & Guidelines')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-image: url('{{ asset('assets/cpu-pic1.jpg') }}');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content-wrapper {
            background-color: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            margin: 2rem auto;
            max-width: 1300px;
        }

        @media (max-width: 768px) {
            .main-content-wrapper {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        /* Quick Stats / Summary Cards */
        .summary-badge {
            background: linear-gradient(135deg, #1f4988ff 0%, #2a5fb4 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(31, 73, 136, 0.3);
        }

        /* Policy Cards */
        .policy-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            border-bottom: 4px solid rgba(23, 79, 163, 0.3);
            overflow: hidden;
        }

        .policy-card:hover {
            box-shadow: 0 15px 30px rgba(31, 73, 136, 0.15);
        }

        .policy-header {
            background: linear-gradient(135deg, #f8faff 0%, #f0f4ff 100%);
            padding: 1.25rem 1.25rem 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(31, 73, 136, 0.1);
        }

        .policy-icon {
            width: 45px;
            height: 45px;
            background: #1f4988ff;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
        }

        .policy-category {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .policy-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f4988ff;
            margin: 0;
            line-height: 1.3;
        }

        .policy-body {
            padding: 1.25rem;
        }

        .discount-badge {
            background: #e8f5e9;
            color: #2e7d32;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 0.75rem;
        }

        .policy-details {
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .policy-details ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
        }

        .policy-details li {
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .policy-details li i {
            color: #1f4988ff;
            font-size: 0.7rem;
            margin-right: 0.5rem;
        }

        /* General Policies Section */
        .general-policies {
            background: #f8faff;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid rgba(31, 73, 136, 0.1);
        }

        .general-policy-item {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .general-policy-item:last-child {
            border-bottom: none;
        }

        .policy-number {
            width: 28px;
            height: 28px;
            background: #1f4988ff;
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .highlight-note {
            background: #fff4e3;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #ffcb78;
            margin: 2rem 0 1rem 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f4988ff;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: #1f4988ff;
            border-radius: 2px;
        }

        .sub-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2d3748;
            margin: 1.5rem 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>

    <main class="main-content-wrapper container">
        <!-- Header with Quick Summary -->
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h1 class="section-title">Booking Policies & Guidelines</h1>
            <div class="summary-badge">
                <i class="fas fa-calendar-check fa-2x"></i>
                <span>Bookings must be made at least <strong>1-2 weeks</strong> in advance</span>
            </div>
        </div>

        <!-- General Reservation Policies -->
        <div class="general-policies mt-0">
            <div class="row">
                <div class="col-md-6">
                    <div class="general-policy-item">
                        <div class="policy-number">1</div>
                        <div><strong>Eligibility:</strong> Available to students, alumni, faculty, staff, and external
                            users. Valid school ID required for Centralians.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">2</div>
                        <div><strong>Booking Period:</strong> Submit at least 1-2 weeks before intended use for your booking
                            to be approved on time by designated offices.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">3</div>
                        <div><strong>Approval Process:</strong> Bookings require approval from the responsible offices.
                            Track progress and upload payment receipts in the <a href="{{ url('your-bookings') }}"
                                class="text-primary text-decoration-underline">Your Bookings</a> page using your reference code.
                        </div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">4</div>
                        <div><strong>Cancellation:</strong> Must be made at least 24 hours before scheduled use.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="general-policy-item">
                        <div class="policy-number">5</div>
                        <div><strong>Equipment Returns:</strong> Return borrowed items on the scheduled date. Late returns
                            may incur penalties that must be paid at the Business office.
                        </div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">6</div>
                        <div><strong>User Accountability:</strong> Responsible for proper use and safekeeping. Any damage,
                            loss, or misuse may incure penalties.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">7</div>
                        <div><strong>Notifications:</strong> Booking updates are sent to your registered email. Please check
                            regularly to avoid delays or cancellation.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">8</div>
                        <div><strong>Feedback:</strong> To better serve you, a feedback form is available <a
                                href="{{ url('user-feedback') }}" class="text-primary text-decoration-underline">here</a>
                            for users to report or suggest improvements in our system.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Note -->
        <div class="highlight-note mb-5">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle text-warning me-3 mt-1" style="font-size: 1.5rem;"></i>
                <div>
                    <h5 class="mb-2" style="color: #f39c12;">Important Note</h5>
                    <p class="mb-2"><strong>All bookings are processed on a first-approved, first-served basis.</strong>
                        Early submission does not guarantee approval — requests are confirmed only upon official approval
                        from the designated offices. Please monitor your email for updates regarding your booking status.
                        For detailed procedures, eligibility requirements, and real-time availability, call VPA's local
                        landline 3266 or visit the office.</p>
                </div>
            </div>
        </div>


        <!-- Fee Guidelines Section -->
        <div class="sub-section-title">
            <h1 class="section-title">Booking Fee Discounts & Waiver Eligibility</h1>
        </div>
        <!-- Policy Cards Grid -->
        <!-- Category: University Students, Organizations & Staff -->
        <div class="category-section position-relative mb-5">
            <div class="position-relative d-flex justify-content-center mb-4">
                <hr class="w-100 position-absolute top-50 start-0 translate-middle-y"
                    style="border-top: 2px solid #1f4988ff; opacity: 0.3; margin: 0; z-index: 0;">
                <h5 class="category-title px-4 py-2 mb-0 position-relative"
                    style="z-index: 1; display: inline-block; border-radius: 30px; color: white; font-weight: 600; background: linear-gradient(135deg, #1f4988ff 0%, #2a5fb4 100%);">
                    University Students, Organizations & Staff
                </h5>
            </div>
            <div class="row g-4">
                <!-- Academic & University Events (Free) -->
                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <div class="policy-category">Academic Requirements</div>
                                <h6 class="policy-title">Classes, Seminars & Conferences</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-check-circle me-1"></i>100% Free</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For official academic activities by
                                faculty, departments, and recognized academic units
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues, PA equipment, staff overtime:
                                        <strong>Free</strong>
                                    </li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <div class="policy-category">University Events</div>
                                <h6 class="policy-title">Programs & Activities</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-check-circle me-1"></i>100% Free</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For official university-wide events
                                organized by the Administration
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> All university programs: <strong>Free</strong></li>
                                    <li><i class="fas fa-circle"></i> Includes board passers ceremonies</li>
                                    <li><i class="fas fa-circle"></i> No venue or equipment fees</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <div class="policy-category">Student Organizations</div>
                                <h6 class="policy-title">Student-Organized Activities</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-tag me-1"></i>75% Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For accredited student organizations
                                (excluding UDAY activities)
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>75% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Activities -->
                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div>
                                <div class="policy-category">Student Activities</div>
                                <h6 class="policy-title">Without Fee</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-tag me-1"></i>75% Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For student activities that don't charge
                                participation fees
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>75% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div>
                                <div class="policy-category">Student Activities</div>
                                <h6 class="policy-title">With Fee</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-tag me-1"></i>50% Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For student activities that charge
                                registration or entrance fees
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>50% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category: Alumni -->
        <div class="category-section position-relative mb-5">
            <div class="position-relative d-flex justify-content-center mb-4">
                <hr class="w-100 position-absolute top-50 start-0 translate-middle-y"
                    style="border-top: 2px solid #1f4988ff; opacity: 0.3; margin: 0; z-index: 0;">
                <h5 class="category-title px-4 py-2 mb-0 position-relative"
                    style="z-index: 1; display: inline-block; border-radius: 30px; color: white; font-weight: 600; background: linear-gradient(135deg, #1f4988ff 0%, #2a5fb4 100%);">
                    Alumni
                </h5>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="policy-category">Alumni Activities</div>
                                <h6 class="policy-title">Class Reunions</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-check-circle me-1"></i>100% Free</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For official class reunions organized by
                                alumni batches
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues, PA, staff: <strong>Free</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Special alumni rates apply</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <div class="policy-category">Alumni Activities</div>
                                <h6 class="policy-title">Alumni-Organized Events</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-tag me-1"></i>30% Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For events organized by alumni
                                associations/groups (non-reunion)
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>30% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div>
                                <div class="policy-category">Alumni Activities</div>
                                <h6 class="policy-title">Personal Events</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge"><i class="fas fa-tag me-1"></i>20% Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For alumni hosting personal celebrations
                                (weddings, birthdays, etc.)
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>20% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong>
                                    </li>
                                    <li><i class="fas fa-circle"></i> Other required fees apply</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category: External -->
        <div class="category-section position-relative mb-5">
            <div class="position-relative d-flex justify-content-center mb-4">
                <hr class="w-100 position-absolute top-50 start-0 translate-middle-y"
                    style="border-top: 2px solid #1f4988ff; opacity: 0.3; margin: 0; z-index: 0;">
                <h5 class="category-title px-4 py-2 mb-0 position-relative"
                    style="z-index: 1; display: inline-block; border-radius: 30px; color: white; font-weight: 600; background: linear-gradient(135deg, #1f4988ff 0%, #2a5fb4 100%);">
                    External Users
                </h5>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="policy-card">
                        <div class="policy-header d-flex align-items-center">
                            <div class="policy-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                            <div>
                                <div class="policy-category">External Users</div>
                                <h6 class="policy-title">Outsiders / External</h6>
                            </div>
                        </div>
                        <div class="policy-body">
                            <span class="discount-badge bg-danger bg-opacity-10 text-danger"><i
                                    class="fas fa-times-circle me-1"></i>No Discount</span>
                            <div class="small text-muted mb-2 border-bottom pb-2">
                                <i class="fas fa-user-check me-1 text-primary"></i> For non-CPU affiliates, outside
                                organizations, and commercial users
                            </div>
                            <div class="policy-details">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-circle"></i> <strong>All necessary charges apply</strong></li>
                                    <li><i class="fas fa-circle"></i> No university discounts</li>
                                    <li><i class="fas fa-circle"></i> Standard rates apply</li>
                                    <li><i class="fas fa-circle"></i> Employee overtime charged</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer>
        </footer>
    </main>
@endsection