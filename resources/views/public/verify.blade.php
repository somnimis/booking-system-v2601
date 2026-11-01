@extends('layouts.app')

@section('title', 'Verify Permit')

@section('content')
<main class="container py-5" style="max-width: 560px;">
    <div class="card border-0 mt-5">
        <div class="card-body p-4 text-center">
            <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>

            <h4 class="fw-semibold mb-1">Permit Verification</h4>
            <p class="text-muted small mb-4">
                Enter the Control Number printed at the bottom of the permit.
            </p>

            <form method="POST" action="{{ route('verify.submit') }}">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" name="control_num"
                        class="form-control"
                        placeholder="e.g. CPU-2026-A1B2" required>
                    <button class="btn btn-primary px-3" type="submit">
                        Verify
                    </button>
                </div>
            </form>

            @if(session('error'))
                <div class="alert alert-danger mt-4 small fw-semibold">
                    <i class="fas fa-times-circle me-1"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success mt-4 text-start">
                    <h6 class="fw-semibold mb-3 text-center">
                        <i class="fas fa-check-circle me-1"></i>
                        {{ session('success') }}
                    </h6>

                    @php $permit = session('permit'); @endphp

                    <div class="bg-light p-3 rounded border mb-3 small">
                        <p class="mb-2">
                            <strong>Control No:</strong> {{ $permit->control_num }}
                        </p>
                        <p class="mb-2">
                            <strong>Event:</strong> {{ $permit->event_title }}
                        </p>
                        <p class="mb-2">
                            <strong>Organization:</strong> {{ $permit->organization_name }}
                        </p>
                        <p class="mb-2">
                            <strong>Date:</strong> {{ \Carbon\Carbon::parse($permit->start_date)->format('M j, Y') }}
                        </p>
                        <p class="mb-0">
                            <strong>Status:</strong>
                            <span class="badge bg-primary">{{ $permit->formStatus->status_name }}</span>
                        </p>
                    </div>

                    <a href="{{ route('official-receipt.generate', $permit->request_id) }}"
                        target="_blank" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-external-link-alt me-1"></i>
                        View Full Permit
                    </a>

                    <p class="text-muted text-center small mt-2 mb-0">
                        This permit is active in the CPU Booking System.
                    </p>
                </div>
            @endif
        </div>
    </div>
</main>
@endsection