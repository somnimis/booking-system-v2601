@extends('layouts.app')

@section('title', 'User Guide - CPU Booking')

@push('styles')
    <style>
        /* Guide-specific typography and layout */
        .guide-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1rem;
        }

        .guide-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: #1f1f1f;
            font-size: 2rem;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        .guide-subtitle {
            color: #787774;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .guide-section {
            margin-bottom: 3.5rem;
        }

        .guide-heading {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            color: #1f1f1f;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e6e6e4;
        }

        /* Specifically target the inline overrides you used in the HTML */
        .guide-heading[style*="font-size: 1.1rem"] {
            font-size: 1rem !important;
        }

        .guide-text {
            color: #4d4d4a;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .guide-text,
        .guide-list {
            font-size: 0.9rem;
            /* Reduced from 1rem */
            line-height: 1.5;
        }

        .guide-list {
            color: #4d4d4a;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            padding-left: 1.25rem;
        }

        .guide-list li {
            margin-bottom: 0.5rem;
        }

        .guide-list strong {
            color: #1f1f1f;
        }

        .image-placeholder {
            background: #fafaf9;
            border: 2px dashed #dcdcd8;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 1.5rem 0;
            color: #b3b3af;
            font-size: 0.9rem;
        }

        .image-placeholder i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .callout {
            background: #f3f4f6;
            border-left: 4px solid #234370;
            padding: 1rem;
            border-radius: 0 6px 6px 0;
            margin: 1.5rem 0;
            color: #4d4d4a;
            font-size: 0.85rem;
        }
    </style>
@endpush

@section('content')
    <main class="guide-container">

        <h1 class="guide-title">User Guide</h1>
        <p class="guide-subtitle">Learn how to track your bookings, upload receipts, and manage your official permits.</p>

        <!-- Quick Start (from tooltip) -->
        <div class="guide-section"
            style="background: #f9fafb; padding: 2rem; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h2 class="guide-heading"
                style="font-size: 1.25rem; border-bottom: none; padding-bottom: 0; margin-bottom: 1rem;">How to Book (Quick
                Start)</h2>
            <ol class="guide-list mb-0">
                <li>Browse the <a href="{{ url('/booking-catalog') }}" class="text-decoration-none fw-semibold"
                        style="color: #234370;">Booking Catalog</a> and add venues or equipment to your booking cart.</li>
                <li>Go to the reservation form via <a href="{{ url('/reservation-form') }}"
                        class="text-decoration-none fw-semibold" style="color: #234370;">Book Now</a> or your cart.</li>
                <li>Fill in the required booking details and check item availability for your selected timeslot.</li>
                <li>Read the reservation policies before submitting your request.</li>
            </ol>
        </div>
        <!-- SECTION 1: Checking Bookings -->
        <div class="guide-section">
            <h2 class="guide-heading">1. Checking Your Booking Status</h2>
            <p class="guide-text">
                You can check the status of your requests at any time without needing to log in.
                Go to the <strong>Your Bookings</strong> page and enter either your <strong>Access Code</strong> (e.g.,
                REQ-ABC123)
                or the <strong>Email Address</strong> you used when making the booking.
            </p>

            <!-- IMAGE: Search bar empty state -->
            <div>
                <img src="{{ asset('assets/user-guide/1.png') }}" class="img-fluid rounded border">
            </div>

            <h3 class="guide-heading" style="border: none; padding: 0; margin-top: 2rem;">Understanding
                Your Booking Card</h3>
            <p class="guide-text">Once your booking appears, you will see a card with the following details:</p>
            <ul class="guide-list">
                <li><strong>Status Badge:</strong> Shows where your booking is in the process (e.g., Pending Approval,
                    Awaiting Payment, Reserved).</li>
                <li><strong>Details:</strong> Shows your event title, purpose, schedule, and total fee.</li>
                <li><strong>Actions:</strong> You can <strong>Upload Receipt</strong> (if awaiting payment) or
                    <strong>Cancel Request</strong> (if it hasn't been finalized yet).
                </li>
            </ul>

            <!-- IMAGE: Search results showing a card -->
            <div>
                <img src="{{ asset('assets/user-guide/2.png') }}" class="img-fluid rounded border">
            </div>
        </div>

        <!-- SECTION 2: Uploading Receipt -->
        <div class="guide-section">
            <h2 class="guide-heading">2. Uploading Your Payment Receipt</h2>
            <p class="guide-text">
                If your booking status is <strong>Awaiting Payment</strong>, you will see an <strong>Upload Receipt</strong>
                button on your booking card. Click this button to open the upload window.
            </p>

            <!-- IMAGE: Upload Modal -->
            <div>
                <img src="{{ asset('assets/user-guide/3.png') }}" class="img-fluid rounded border">
            </div>
            <br>

            <p class="guide-text">
                You can drag and drop your receipt file directly into the dotted box, or click the box to browse your
                device.
                Once selected, click the <strong>Upload</strong> button. Accepted formats are JPG, PNG, or PDF up to 5MB.
            </p>
            <div class="callout">
                <i class="fas fa-info-circle me-1"></i> After uploading, your booking status will automatically change to
                <strong>Verifying Payment</strong> while our staff reviews it.
            </div>
        </div>

        <!-- SECTION 3: Permits -->
        <div class="guide-section">
            <h2 class="guide-heading">3. Viewing & Downloading Permits</h2>
            <p class="guide-text">
                Once your booking is finalized and paid, an <strong>Official Receipt</strong> button will appear on your
                booking card.
                Clicking this will open your Facility Use Permit page.
            </p>

            <!-- IMAGE: Booking card with Official Receipt button -->
            <div>
                <img src="{{ asset('assets/user-guide/4.png') }}" class="img-fluid rounded border">
            </div>

            <h3 class="guide-heading" style="font-size: 1.1rem; border: none; padding: 0; margin-top: 2rem;">Why do I have
                multiple permits?</h3>
            <p class="guide-text">
                If you booked multiple venues (e.g., a Gym and a Classroom) or borrowed equipment (e.g., Sound System),
                the system generates a <strong>separate permit for each item</strong>.
            </p>
            <div class="callout">
                <i class="fas fa-info-circle me-1"></i>
                You will need to present the specific permit to the specific ground personnel for that venue or item.
            </div>

            <!-- IMAGE: Master-detail layout -->
            <div>
                <img src="{{ asset('assets/user-guide/5.png') }}" class="img-fluid rounded border">
            </div>

            <h3 class="guide-heading" style="border: none; padding: 0; margin-top: 2rem;">How to navigate
                the Permit Page</h3>
            <ul class="guide-list">
                <li><strong>The Sidebar (Left):</strong> Lists all the permits generated for your event. Click on a venue or
                    item to view its specific permit.</li>
                <li><strong>The Permit Preview (Right):</strong> Shows the official document with your event details and the
                    digital signatures of the approving administrators.</li>
                <li><strong>The Receipt (Bottom Left):</strong> Click "View Receipt" to expand a breakdown of the fees you
                    paid.</li>
            </ul>
        </div>

        <!-- SECTION 4: Downloading -->
        <div class="guide-section">
            <h2 class="guide-heading">4. Downloading Your Permits</h2>
            <p class="guide-text">
                You have two ways to save your permits to your device. You can present the downloaded image on your phone,
                or print it out to present on the day of your event.
            </p>

            <div class="d-flex flex-column flex-md-row align-items-center gap-4 mt-4 mb-5">
                <div class="flex-grow-1">
                    <h3 class="guide-heading" style="font-size: 1.1rem; border: none; padding: 0; margin-top: 0;">
                        Download a Single Permit
                    </h3>
                    <p class="guide-text mb-0">
                        Hover your mouse over the venue/item name in the left sidebar or select it. A <strong>Download Icon</strong> will
                        appear.
                        Click it to save that specific permit.
                    </p>
                    
                    <!-- Added margin-top here for spacing -->
                    <h3 class="guide-heading" style="font-size: 1.1rem; border: none; padding: 0; margin-top: 1.5rem;">
                        Download All at Once
                    </h3>
                    <p class="guide-text mb-0">
                        Click the blue <strong>Download All</strong> button located at the bottom of the left sidebar.
                        The system will automatically capture every permit and bundle them into a single <code>.zip</code>
                        file for you to download.
                    </p>
                </div>
                <div style="max-width: 250px; flex-shrink: 0;">
                    <img src="{{ asset('assets/user-guide/6.png') }}" class="img-fluid rounded border shadow-sm">
                </div>
            </div>
        </div>

    </main>
@endsection