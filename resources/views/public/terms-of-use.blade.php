@extends('layouts.app')

@section('title', 'Terms of Use - CPU Booking')

@section('content')
<style>  
    .legal-wrapper {
        font-family: 'DM Sans', sans-serif;
    }
    .legal-wrapper h1, 
    .legal-wrapper h4 {
        font-family: 'DM Sans', sans-serif;
        letter-spacing: -0.3px;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="legal-wrapper bg-white p-4 p-md-5 rounded-4 border" style="border-color: #e9ecef !important; font-size: 0.95rem;">
                
                <h1 class="mb-3 text-primary fw-bold" style="font-size: 1.75rem;">Terms of Use</h1>
                <p class="text-muted mb-4" style="font-size: 0.85rem;">Last updated: {{ date('F d, Y') }}</p>
                
                <hr class="mb-4">

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">1. Acceptance of Terms</h4>
                <p class="mb-4 text-secondary">By accessing and using the CPU Booking System, you agree to comply with these Terms of Use, as well as the university's general rules and the operational guidelines outlined in our <a href="{{ url('/policies') }}">Booking Policies</a>. If you do not agree, please refrain from using the system.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">2. Eligibility and Account Security</h4>
                <p class="mb-4 text-secondary">This system is intended for use by currently enrolled students, faculty, and authorized university staff. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">3. Facility and Equipment Usage</h4>
                <ul class="mb-4 text-secondary">
                    <li class="mb-2">Users must treat all facilities, equipment, and services with care and respect.</li>
                    <li class="mb-2">Any damages, losses, or missing items caused by negligence or misuse will be the financial responsibility of the user or the organization they represent.</li>
                    <li class="mb-2">Equipment must be returned to the designated drop-off points in the same condition it was received. Late returns may result in penalties or account restrictions.</li>
                </ul>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">4. Prohibited Conduct</h4>
                <p class="mb-4 text-secondary">You agree not to use the system for any unlawful purposes, to impersonate others, to submit fraudulent payment receipts, or to make false bookings. The university reserves the right to cancel bookings and suspend accounts that violate these terms.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">5. Limitation of Liability</h4>
                <p class="mb-4 text-secondary">Central Philippine University is not liable for any personal injury, loss, or damage to personal property incurred while using university facilities or equipment, unless caused by gross negligence on the part of the university. Users utilize the facilities at their own risk.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">6. Modifications</h4>
                <p class="mb-0 text-secondary">The university reserves the right to modify these terms at any time. Continued use of the system after changes are posted constitutes your acceptance of the modified terms.</p>

            </div>
        </div>
    </div>
</div>
@endsection