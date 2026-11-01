@extends('layouts.app')

@section('title', 'User Guide - CPU Booking')

@push('styles')
    <style>   
        .guide-wrapper {
            font-family: 'DM Sans', sans-serif;
        }
        
        .guide-wrapper h1, 
        .guide-wrapper h2, 
        .guide-wrapper h3 {
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.3px;
        }

        .guide-text, .guide-list {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #4d4d4a;
        }

        .guide-heading {
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .guide-subheading {
            font-weight: 600;
            color: #333;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .guide-list li {
            margin-bottom: 0.5rem;
        }

        .guide-list strong {
            color: #1f1f1f;
        }

        .callout {
            background: #f8f9fa;
            border-left: 4px solid var(--cpu-primary);
            padding: 1rem;
            border-radius: 0 6px 6px 0;
            margin: 1.5rem 0;
            color: #4d4d4a;
            font-size: 0.85rem;
        }

        /* Responsive image spacing */
        .guide-img {
            margin: 1rem 0 1.5rem 0;
        }
    </style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="guide-wrapper bg-white p-4 p-md-5 rounded-4 border" style="border-color: #e9ecef !important;">
                
                <h1 class="mb-2 text-primary fw-bold" style="font-size: 1.75rem;">User Guide</h1>
                <p class="text-muted mb-5" style="font-size: 0.9rem;">Learn how to track your bookings, upload receipts, and manage your official permits.</p>

                <!-- Quick Start -->
                <div class="guide-section mb-5" style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; border: 1px solid #e9ecef;">
                    <h2 class="guide-subheading mt-0 mb-3" style="font-size: 1.15rem;">How to Book (Quick Start)</h2>
                    <ol class="guide-list mb-0" style="padding-left: 1.25rem;">
                        <li>Browse the <a href="{{ url('/booking-catalog') }}" class="text-decoration-none fw-semibold" style="color: var(--cpu-primary);">Booking Catalog</a> and add venues or equipment to your booking cart.</li>
                        <li>Go to the reservation form via <a href="{{ url('/reservation-form') }}" class="text-decoration-none fw-semibold" style="color: var(--cpu-primary);">Book Now</a> or your cart.</li>
                        <li>Fill in the required booking details and check item availability for your selected timeslot.</li>
                        <li>Read the reservation policies before submitting your request.</li>
                    </ol>
                </div>

                <!-- SECTION 1: Checking Bookings -->
                <div class="guide-section mb-5">
                    <h2 class="guide-heading" style="font-size: 1.25rem;">1. Checking Your Booking Status</h2>
                    <p class="guide-text">
                        You can check the status of your requests at any time without needing to log in.
                        Go to the <strong>Your Bookings</strong> page and enter either your <strong>Access Code</strong> (e.g., REQ-ABC123)
                        or the <strong>Email Address</strong> you used when making the booking.
                    </p>

                    <img src="{{ asset('assets/user-guide/1.png') }}" class="img-fluid rounded border guide-img w-100">

                    <h3 class="guide-subheading">Understanding Your Booking Card</h3>
                    <p class="guide-text">Once your booking appears, you will see a card with the following details:</p>
                    <ul class="guide-list mb-4">
                        <li><strong>Status Badge:</strong> Shows where your booking is in the process (e.g., Pending Approval, Awaiting Payment, Reserved).</li>
                        <li><strong>Details:</strong> Shows your event title, purpose, schedule, and total fee.</li>
                        <li><strong>Actions:</strong> You can <strong>Upload Receipt</strong> (if awaiting payment) or <strong>Cancel Request</strong> (if it hasn't been finalized yet).</li>
                    </ul>

                    <img src="{{ asset('assets/user-guide/2.png') }}" class="img-fluid rounded border guide-img w-100">
                </div>

                <!-- SECTION 2: Uploading Receipt -->
                <div class="guide-section mb-5">
                    <h2 class="guide-heading" style="font-size: 1.25rem;">2. Uploading Your Payment Receipt</h2>
                    <p class="guide-text">
                        If your booking status is <strong>Awaiting Payment</strong>, you will see an <strong>Upload Receipt</strong>
                        button on your booking card. Click this button to open the upload window.
                    </p>

                    <img src="{{ asset('assets/user-guide/3.png') }}" class="img-fluid rounded border guide-img w-100">

                    <p class="guide-text">
                        You can drag and drop your receipt file directly into the dotted box, or click the box to browse your device.
                        Once selected, click the <strong>Upload</strong> button. Accepted formats are JPG, PNG, or PDF up to 5MB.
                    </p>
                    <div class="callout">
                        <i class="fas fa-info-circle me-1"></i> After uploading, your booking status will automatically change to
                        <strong>Verifying Payment</strong> while our staff reviews it.
                    </div>
                </div>

                <!-- SECTION 3: Permits -->
                <div class="guide-section mb-5">
                    <h2 class="guide-heading" style="font-size: 1.25rem;">3. Viewing & Downloading Permits</h2>
                    <p class="guide-text">
                        Once your booking is finalized and paid, an <strong>Official Receipt</strong> button will appear on your booking card.
                        Clicking this will open your Facility Use Permit page.
                    </p>

                    <img src="{{ asset('assets/user-guide/4.png') }}" class="img-fluid rounded border guide-img w-100">

                    <h3 class="guide-subheading">Why do I have multiple permits?</h3>
                    <p class="guide-text">
                        If you booked multiple venues (e.g., a Gym and a Classroom) or borrowed equipment (e.g., Sound System),
                        the system generates a <strong>separate permit for each item</strong>.
                    </p>
                    <div class="callout">
                        <i class="fas fa-info-circle me-1"></i>
                        You will need to present the specific permit to the specific ground personnel for that venue or item.
                    </div>

                    <img src="{{ asset('assets/user-guide/5.png') }}" class="img-fluid rounded border guide-img w-100">

                    <h3 class="guide-subheading">How to navigate the Permit Page</h3>
                    <ul class="guide-list mb-0">
                        <li><strong>The Sidebar (Left):</strong> Lists all the permits generated for your event. Click on a venue or item to view its specific permit.</li>
                        <li><strong>The Permit Preview (Right):</strong> Shows the official document with your event details and the digital signatures of the approving administrators.</li>
                        <li><strong>The Receipt (Bottom Left):</strong> Click "View Receipt" to expand a breakdown of the fees you paid.</li>
                    </ul>
                </div>

                <!-- SECTION 4: Downloading -->
                <div class="guide-section mb-0">
                    <h2 class="guide-heading" style="font-size: 1.25rem;">4. Downloading Your Permits</h2>
                    <p class="guide-text">
                        You have two ways to save your permits to your device. You can present the downloaded image on your phone, or print it out to present on the day of your event.
                    </p>

                    <div class="d-flex flex-column flex-md-row align-items-center gap-4 mt-4">
                        <div class="flex-grow-1">
                            <h3 class="guide-subheading mt-0 mb-2" style="font-size: 1.1rem;">
                                Download a Single Permit
                            </h3>
                            <p class="guide-text mb-3">
                                Hover your mouse over the venue/item name in the left sidebar or select it. A <strong>Download Icon</strong> will appear. Click it to save that specific permit.
                            </p>
                            
                            <h3 class="guide-subheading mt-0 mb-2" style="font-size: 1.1rem;">
                                Download All at Once
                            </h3>
                            <p class="guide-text mb-0">
                                Click the blue <strong>Download All</strong> button located at the bottom of the left sidebar. The system will automatically capture every permit and bundle them into a single <code>.zip</code> file for you to download.
                            </p>
                        </div>
                        <div style="max-width: 250px; flex-shrink: 0;">
                            <img src="{{ asset('assets/user-guide/6.png') }}" class="img-fluid rounded border shadow-sm">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection