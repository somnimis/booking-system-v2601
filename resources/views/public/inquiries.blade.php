@extends('layouts.app')

@section('title', 'CPU Booking Policies & Guidelines')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/public/global-styles.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           REFINED INSTITUTIONAL THEME - POLICIES & GUIDELINES
           Matching catalog.css design system
           ============================================ */

        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap');

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
            --success: #22c55e;
            --success-bg: #e8f7ef;
            --danger: #ef4444;
            --danger-bg: #fee8e8;
            --warning: #f5bc40;
            --warning-bg: #fef8e8;
            --info: #6f42c1;
            --info-bg: #ede7f6;
            --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
            --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
            --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-image: url('{{ asset('assets/cpu-pic1.jpg') }}');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(4, 26, 75, 0.85) 0%, rgba(4, 26, 75, 0.75) 100%);
    z-index: -1;
}

.main-content-wrapper {
    position: relative;
    z-index: 1;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    padding: 2.5rem;
    margin: 2rem auto;
    max-width: 1300px;
    width: calc(100% - 2rem);
}

        @media (max-width: 768px) {
            .main-content-wrapper {
                margin: 1rem;
                padding: 1.5rem;
                width: calc(100% - 2rem);
            }
        }

        /* Section Title */
        .section-title {
            font-family: 'Fraunces', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--navy);
            border-radius: 2px;
        }

        .sub-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-base);
            margin: 1.5rem 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Quick Stats / Summary Badge */
        .summary-badge {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 60px;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-md);
            font-size: 0.85rem;
        }

        .summary-badge i {
            font-size: 1.5rem;
        }

        /* Category Section */
        .category-section {
            margin-bottom: 2.5rem;
        }

        .category-title {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        hr {
            border-top-color: var(--border) !important;
        }

        /* Policy Cards */
        .policy-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .policy-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--navy-light);
        }

        .policy-header {
            background: var(--surface);
            padding: 1.25rem 1.25rem 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }

        .policy-icon {
            width: 48px;
            height: 48px;
            background: var(--navy);
            color: white;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .policy-category {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .policy-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--navy);
            margin: 0;
            line-height: 1.3;
            font-family: 'DM Sans', sans-serif;
        }

        .policy-body {
            padding: 1.25rem;
        }

        .discount-badge {
            background: var(--success-bg);
            color: #166534;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 60px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.75rem;
        }

        .discount-badge.danger {
            background: var(--danger-bg);
            color: #991b1b;
        }

        .discount-badge.warning {
            background: var(--warning-bg);
            color: #854d0e;
        }

        .policy-details {
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .policy-details ul {
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
        }

        .policy-details li {
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .policy-details li i {
            color: var(--navy);
            font-size: 0.6rem;
            margin-top: 0.35rem;
        }

        /* General Policies Section */
        .general-policies {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            margin-top: 2rem;
            border: 1px solid var(--border);
        }

        .general-policy-item {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border);
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
            background: var(--navy);
            color: white;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .general-policy-item strong {
            color: var(--navy);
            font-weight: 600;
        }

        .general-policy-item a {
            color: var(--navy);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .general-policy-item a:hover {
            color: var(--navy-mid);
            text-decoration: underline;
        }

        /* Highlight Note */
        .highlight-note {
            background: var(--warning-bg);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            border-left: 4px solid var(--warning);
            margin: 1.5rem 0;
        }

        .highlight-note h5 {
            color: #b45309;
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .highlight-note p {
            color: var(--text-base);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Category Section Header */
        .category-header {
            text-align: center;
            margin-bottom: 1.75rem;
            position: relative;
        }

        .category-header h5 {
            display: inline-block;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: white;
            padding: 0.5rem 1.75rem;
            border-radius: 60px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        /* Footer */
        footer {
            margin-top: 1rem;
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section-title {
                font-size: 1.4rem;
            }

            .summary-badge {
                margin-top: 1rem;
                font-size: 0.75rem;
                padding: 0.5rem 1rem;
            }

            .general-policy-item {
                flex-direction: column;
                gap: 0.5rem;
            }

            .policy-header {
                flex-direction: column;
                text-align: center;
            }

            .policy-icon {
                margin-right: 0;
                margin-bottom: 0.75rem;
            }

            .policy-category, .policy-title {
                text-align: center;
            }

            .highlight-note {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .main-content-wrapper {
                padding: 1rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .general-policies {
                padding: 1rem;
            }

            .policy-body {
                padding: 1rem;
            }
        }

        /* Utility Classes */
        .text-primary { color: var(--navy) !important; }
        .text-muted { color: var(--text-muted) !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 0.25rem !important; }
        .mb-2 { margin-bottom: 0.5rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .mb-4 { margin-bottom: 1.5rem !important; }
        .mb-5 { margin-bottom: 2rem !important; }
        .mt-0 { margin-top: 0 !important; }
        .mt-1 { margin-top: 0.25rem !important; }
        .mt-2 { margin-top: 0.5rem !important; }
        .mt-3 { margin-top: 1rem !important; }
        .mt-4 { margin-top: 1.5rem !important; }
        .mt-5 { margin-top: 2rem !important; }
        .me-1 { margin-right: 0.25rem !important; }
        .me-2 { margin-right: 0.5rem !important; }
        .me-3 { margin-right: 1rem !important; }
        .border-bottom { border-bottom: 1px solid var(--border) !important; }
        .pb-2 { padding-bottom: 0.5rem !important; }
    </style>

    <main class="main-content-wrapper">
        <!-- Header with Quick Summary -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h1 class="section-title">Booking Policies & Guidelines</h1>
            <div class="summary-badge">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings must be made at least <strong>1-2 weeks</strong> in advance</span>
            </div>
        </div>

        <!-- General Reservation Policies -->
        <div class="general-policies">
            <div class="row g-3">
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
                            Track progress and upload payment receipts in the <a href="{{ url('your-bookings') }}">Your Bookings</a> page using your reference code.
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
                            loss, or misuse may incur penalties.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">7</div>
                        <div><strong>Notifications:</strong> Booking updates are sent to your registered email. Please check
                            regularly to avoid delays or cancellation.</div>
                    </div>
                    <div class="general-policy-item">
                        <div class="policy-number">8</div>
                        <div><strong>Feedback:</strong> To better serve you, a feedback form is available <a
                                href="{{ url('user-feedback') }}">here</a> for users to report or suggest improvements.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Note -->
        <div class="highlight-note">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.25rem; color: var(--warning);"></i>
                <div>
                    <h5>Important Note</h5>
                    <p><strong>All bookings are processed on a first-approved, first-served basis.</strong> Early submission does not guarantee approval — requests are confirmed only upon official approval from the designated offices. Please monitor your email for updates regarding your booking status. For detailed procedures, eligibility requirements, and real-time availability, call VPA's local landline 3266 or visit the office.</p>
                </div>
            </div>
        </div>

        <!-- Fee Guidelines Section -->
        <h2 class="section-title mt-4">Booking Fee Discounts & Waiver Eligibility</h2>

        <!-- Category: University Students, Organizations & Staff -->
        <div class="category-section">
            <div class="category-header">
                <hr class="w-100 position-absolute" style="border-top: 1px solid var(--border); margin: 0;">
                <h5>University Students, Organizations & Staff</h5>
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
                            <span class="discount-badge"><i class="fas fa-check-circle"></i>100% Free</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For official academic activities by faculty, departments, and recognized academic units
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues, PA equipment, staff overtime: <strong>Free</strong></li>
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
                            <span class="discount-badge"><i class="fas fa-check-circle"></i>100% Free</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For official university-wide events organized by the Administration
                            </div>
                            <div class="policy-details">
                                <ul>
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
                            <span class="discount-badge warning"><i class="fas fa-tag"></i>75% Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For accredited student organizations (excluding UDAY activities)
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>75% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

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
                            <span class="discount-badge warning"><i class="fas fa-tag"></i>75% Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For student activities that don't charge participation fees
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>75% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong></li>
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
                            <span class="discount-badge"><i class="fas fa-tag"></i>50% Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For student activities that charge registration or entrance fees
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>50% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category: Alumni -->
        <div class="category-section">
            <div class="category-header">
                <hr class="w-100 position-absolute" style="border-top: 1px solid var(--border); margin: 0;">
                <h5>Alumni</h5>
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
                            <span class="discount-badge"><i class="fas fa-check-circle"></i>100% Free</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For official class reunions organized by alumni batches
                            </div>
                            <div class="policy-details">
                                <ul>
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
                            <span class="discount-badge warning"><i class="fas fa-tag"></i>30% Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For events organized by alumni associations/groups (non-reunion)
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>30% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong></li>
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
                            <span class="discount-badge"><i class="fas fa-tag"></i>20% Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For alumni hosting personal celebrations (weddings, birthdays, etc.)
                            </div>
                            <div class="policy-details">
                                <ul>
                                    <li><i class="fas fa-circle"></i> Venues & PA equipment: <strong>20% off</strong></li>
                                    <li><i class="fas fa-circle"></i> LED screen: <strong>Paid</strong></li>
                                    <li><i class="fas fa-circle"></i> Employee overtime: <strong>Paid by organizer</strong></li>
                                    <li><i class="fas fa-circle"></i> Other required fees apply</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category: External -->
        <div class="category-section">
            <div class="category-header">
                <hr class="w-100 position-absolute" style="border-top: 1px solid var(--border); margin: 0;">
                <h5>External Users</h5>
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
                            <span class="discount-badge danger"><i class="fas fa-times-circle"></i>No Discount</span>
                            <div class="small text-muted mb-2 pb-2 border-bottom">
                                <i class="fas fa-user-check me-1"></i> For non-CPU affiliates, outside organizations, and commercial users
                            </div>
                            <div class="policy-details">
                                <ul>
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
            <p class="mb-0">&copy; {{ date('Y') }} Central Philippine University. All rights reserved.</p>
        </footer>
    </main>
@endsection