@extends('layouts.app')

@section('title', 'Verify Permit - CPU Booking')

@push('styles')
    <style>
   
        .verify-wrapper {
            font-family: 'DM Sans', sans-serif;
        }
        
        .verify-wrapper h1, 
        .verify-wrapper h4, 
        .verify-wrapper h6 {
            font-family: 'DM Sans', sans-serif;
            letter-spacing: -0.3px;
        }

        .form-control-verify {
            font-size: 0.95rem;
            height: 48px;
            border-radius: 8px 0 0 8px;
            border: 1px solid #e9ecef;
        }
        
        .form-control-verify:focus {
            border-color: var(--cpu-primary);
            box-shadow: 0 0 0 0.25rem var(--cpu-primary-light);
        }

        .btn-verify {
            border-radius: 0 8px 8px 0;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0 1.5rem;
            background-color: var(--cpu-primary);
            border-color: var(--cpu-primary);
        }
        
        .btn-verify:hover {
            background-color: var(--cpu-primary-hover);
            border-color: var(--cpu-primary-hover);
        }
    </style>
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            
            <!-- Reused the standard app card style -->
            <div class="verify-wrapper bg-white p-4 p-md-5 rounded-4  border" style="border-color: #e9ecef !important;">
                
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-shield-alt fa-lg"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-primary" style="font-size: 1.5rem;">Permit Verification</h4>
                    <p class="text-muted" style="font-size: 0.95rem;">
                        Enter the Control Number printed at the bottom of the permit.
                    </p>
                </div>

                <form method="POST" action="{{ route('verify.submit') }}" class="mb-2">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="control_num"
                            class="form-control form-control-verify"
                            placeholder="e.g. CPU-2026-A1B2" required>
                        <button class="btn btn-primary btn-verify" type="submit">
                            Verify
                        </button>
                    </div>
                </form>

                @if(session('error'))
                    <div class="alert alert-danger mt-4 small fw-semibold rounded-3 border-0" style="background-color: #fee8e8; color: #991b1b;">
                        <i class="fas fa-times-circle me-1"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success mt-4 text-start rounded-3 border-0 p-4" style="background-color: #e8f7ef;">
                        <h6 class="fw-bold mb-3 text-center" style="color: #166534;">
                            <i class="fas fa-check-circle me-1"></i>
                            {{ session('success') }}
                        </h6>

                        @php $permit = session('permit'); @endphp

                        <div class="bg-white p-3 rounded-3 shadow-sm border" style="border-color: #e9ecef !important;">
                            <p class="mb-2 text-secondary" style="font-size: 0.9rem;">
                                <strong class="text-dark">Control No:</strong> {{ $permit->control_num }}
                            </p>
                            <p class="mb-2 text-secondary" style="font-size: 0.9rem;">
                                <strong class="text-dark">Event:</strong> {{ $permit->event_title }}
                            </p>
                            <p class="mb-2 text-secondary" style="font-size: 0.9rem;">
                                <strong class="text-dark">Organization:</strong> {{ $permit->organization_name }}
                            </p>
                            <p class="mb-2 text-secondary" style="font-size: 0.9rem;">
                                <strong class="text-dark">Date:</strong> {{ \Carbon\Carbon::parse($permit->start_date)->format('M j, Y') }}
                            </p>
                            <p class="mb-0 text-secondary" style="font-size: 0.9rem;">
                                <strong class="text-dark">Status:</strong>
                                <span class="badge rounded-pill ms-1" style="background-color: var(--cpu-primary);">{{ $permit->formStatus->status_name }}</span>
                            </p>
                        </div>

                        <a href="{{ route('official-receipt.generate', $permit->request_id) }}"
                            target="_blank" class="btn btn-primary w-100 mt-3 fw-semibold" style="border-radius: 8px;">
                            <i class="fas fa-external-link-alt me-1"></i>
                            View Full Permit
                        </a>

                        <p class="text-muted text-center mt-3 mb-0" style="font-size: 0.8rem;">
                            This permit is active in the CPU Booking System.
                        </p>
                    </div>
                @endif
                
            </div>
        </div>
    </div>
</div>
@endsection