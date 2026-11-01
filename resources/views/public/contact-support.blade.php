@extends('layouts.app')

@section('title', 'Contact Support - CPU Booking')

@push('styles')
    <style>
     
        .support-wrapper {
            font-family: 'DM Sans', sans-serif;
        }
        
        .support-wrapper h1, 
        .support-wrapper h4 {
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.3px;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: var(--cpu-primary-light);
            color: var(--cpu-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
            margin: 0 auto 1rem auto;
        }
    </style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="support-wrapper bg-white p-4 p-md-5 rounded-4 border" style="border-color: #e9ecef !important; font-size: 0.95rem;">
                
                <div class="text-center mb-5">
                    <h1 class="mb-2 text-primary fw-bold" style="font-size: 1.75rem;">Contact Support</h1>
                    <p class="text-muted" style="font-size: 0.9rem;">For assistance with bookings, payments, or permit-related <br>concerns, please contact the relevant university offices below.</p>
                </div>

                <div class="row g-4 text-center">
                    <!-- Venue & PA Office -->
                    <div class="col-md-4">
                        <div class="contact-card h-100 p-4 rounded-4 border bg-light">
                            <div class="icon-box">
                                <i class="fas fa-building"></i>
                            </div>
                            <h4 class="fw-bold mb-2" style="font-size: 1.1rem; color: #333;">Venues & PA</h4>
                            <p class="text-muted mb-1" style="font-size: 0.85rem;">Vice President of<br> Administration</p>
                            <p class="fw-semibold mb-0" style="color: var(--cpu-primary);">Local 3266</p>
                        </div>
                    </div>

                    <!-- Equipment -->
                    <div class="col-md-4">
                        <div class="contact-card h-100 p-4 rounded-4 border bg-light">
                            <div class="icon-box">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h4 class="fw-bold mb-2" style="font-size: 1.1rem; color: #333;">Equipment</h4>
                            <p class="text-muted mb-1" style="font-size: 0.85rem;">Educational Media<br> Center</p>
                            <p class="fw-semibold mb-0" style="color: var(--cpu-primary);">Local 3270</p>
                        </div>
                    </div>

                    <!-- System Support -->
                    <div class="col-md-4">
                        <div class="contact-card h-100 p-4 rounded-4 border bg-light">
                            <div class="icon-box">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <h4 class="fw-bold mb-2" style="font-size: 1.1rem; color: #333;">Technical Help</h4>
                            <p class="text-muted mb-1" style="font-size: 0.85rem;">University Computer <br>Service Center</p>
                            <p class="fw-semibold mb-0" style="color: var(--cpu-primary);">support@cpu.edu.ph</p>
                        </div>
                    </div>
                </div>

                <hr class="my-5">

                <div class="text-center">
                    <h4 class="fw-bold mb-3" style="font-size: 1.1rem; color: #333;">Visit Our Office</h4>
                    <p class="text-muted mb-1" style="font-size: 0.9rem;">Central Philippine University</p>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Lopez Jaena St., Jaro, Iloilo City, 5000</p>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection