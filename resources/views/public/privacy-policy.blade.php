@extends('layouts.app')

@section('title', 'Privacy Policy - CPU Booking')

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
                
                <h1 class="mb-3 text-primary fw-bold" style="font-size: 1.75rem;">Privacy Policy</h1>
                <p class="text-muted mb-4" style="font-size: 0.85rem;">Last updated: {{ date('F d, Y') }}</p>
                
                <hr class="mb-4">

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">1. Information We Collect</h4>
                <p class="mb-3 text-secondary">When you use the Central Philippine University (CPU) Booking System, we may collect the following information to process your reservations:</p>
                <ul class="mb-4 text-secondary">
                    <li class="mb-2"><strong>Personal Information:</strong> Name, student/staff ID number, email address, and contact number.</li>
                    <li class="mb-2"><strong>Booking Details:</strong> Facilities, equipment, or services requested, dates, times, and purpose of the booking.</li>
                    <li class="mb-2"><strong>Usage Data:</strong> Log data, IP addresses, and browser information used to access the system.</li>
                </ul>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">2. How We Use Your Information</h4>
                <p class="mb-3 text-secondary">The information collected is strictly used for:</p>
                <ul class="mb-4 text-secondary">
                    <li class="mb-2">Processing, approving, and managing your booking requests.</li>
                    <li class="mb-2">Communicating updates, confirmations, or changes regarding your reservations.</li>
                    <li class="mb-2">Ensuring the security and integrity of university assets and facilities.</li>
                    <li class="mb-2">Generating internal analytics to improve system performance and resource allocation.</li>
                </ul>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">3. Data Sharing and Disclosure</h4>
                <p class="mb-4 text-secondary">We do not sell or rent your personal data. Your information may only be shared with authorized university personnel (e.g., facility managers, security) for the purpose of facilitating your approved bookings.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">4. Data Retention</h4>
                <p class="mb-4 text-secondary">Your booking records will be retained in accordance with the university's institutional retention policies. Personal data will be securely disposed of when no longer necessary for the purposes for which it was collected.</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">5. Your Rights</h4>
                <p class="mb-4 text-secondary">Under the Data Privacy Act of 2012, you have the right to access, correct, or request the deletion of your personal data. To exercise these rights, please contact the university's Data Protection Officer (DPO).</p>

                <h4 class="mt-4 mb-3" style="font-size: 1.1rem; font-weight: 600; color: #333;">6. Changes to This Policy</h4>
                <p class="mb-0 text-secondary">We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated revision date.</p>
            
            </div>
        </div>
    </div>
</div>
@endsection