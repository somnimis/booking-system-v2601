{{--
Server-rendered booking card.
Consumed by UserBookingsService::search() via view()->render().
$data shape = RequisitionFormatterService::formatPublicForm() merged with
RequisitionFormatterService::getStatusTracking().
--}}
@php
    $status  = $data['form_status'];
    $fee     = (float) ($data['fees']['approved_fee'] ?? $data['fees']['tentative_fee'] ?? 0);
    $track   = $data['status_tracking'] ?? [];
    $statusName = $status->status_name;

    $canUpload = $statusName === 'Awaiting Payment';
    $canCancel = in_array($statusName, ['Pending Approval', 'Scheduled', 'Awaiting Payment']);
    
    // Show receipt if it exists and status is Completed or Reserved
    $hasReceipt = !empty($data['official_receipt_num']) 
                  && in_array($statusName, ['Completed', 'Reserved']);

    $hasClosure = !empty($track['is_closed'])
        && (!empty($track['closed_at']) || !empty($track['closure_reason'] ?? null));
@endphp

<div class="card-responsive booking-card" data-status="{{ $statusSlug }}">
    <div class="card-responsive-header">
        <span class="request-id">
            Request #{{ str_pad($data['request_id'], 4, '0', STR_PAD_LEFT) }}
        </span>
        <span class="status-badge-responsive" style="background-color: {{ $status->color_code }}; color: #fff;">
            {{ $statusName }}
        </span>
    </div>

    <div class="card-responsive-body">
        <div class="row g-3">
            {{-- Left column: event & schedule --}}
            <div class="col-12 col-md-6">
                <h6 class="fw-bold mb-2">
                    {{ $data['event_title'] ?: 'Booking #' . $data['request_id'] }}
                </h6>
                @if(!empty($data['event_details']))
                    <p class="mb-2 text-muted small">{{ $data['event_details'] }}</p>
                @endif

                @if(!empty($data['organization_name']))
                    <p class="mb-1"><strong>Organization:</strong> {{ $data['organization_name'] }}</p>
                @endif

                <p class="mb-1"><strong>Requester:</strong> {{ $data['first_name'] }} {{ $data['last_name'] }}</p>
                <p class="mb-1"><strong>Purpose:</strong> {{ $data['purpose']->purpose_name ?? 'N/A' }}</p>
                <p class="mb-1"><strong>Schedule:</strong> {{ $data['formatted_schedule'] }}</p>
                <p class="mb-0"><strong>Participants:</strong> {{ $data['num_participants'] }}</p>
            </div>

            {{-- Right column: requested items + total fee --}}
            <div class="col-12 col-md-6">
                {{-- Facilities --}}
                <p class="mb-1"><strong>Facilities:</strong></p>
                @php $facilities = $data['requested_facilities'] ?? []; @endphp
                @if(count($facilities) === 0)
                    <p class="no-items">None</p>
                @else
                    <ul class="mb-2">
                        @foreach($facilities as $f)
                            <li>{{ $f['facility_name'] }}</li>
                        @endforeach
                    </ul>
                @endif

                {{-- Equipment --}}
                <p class="mb-1"><strong>Equipment:</strong></p>
                @php $equipment = $data['requested_equipment'] ?? []; @endphp
                @if(count($equipment) === 0)
                    <p class="no-items">None</p>
                @else
                    <ul class="mb-2">
                        @foreach($equipment as $e)
                            <li>{{ $e['equipment_name'] }} (×{{ $e['quantity'] }})</li>
                        @endforeach
                    </ul>
                @endif

                {{-- Services --}}
                <p class="mb-1"><strong>Services:</strong></p>
                @php $services = $data['fees']['breakdown']['services'] ?? []; @endphp
                @if(count($services) === 0)
                    <p class="no-items">None</p>
                @else
                    <ul class="mb-0">
                        @foreach($services as $s)
                            <li>{{ $s['name'] }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="total-fee">
                    <strong>Total Fee</strong>
                    <span class="fee-amount">₱{{ number_format($fee, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Closure details: only when actually closed --}}
        @if($hasClosure)
            <hr class="my-3">
            <div class="closure-details">
                <p class="mb-1"><strong>Closed:</strong>
                    {{ $track['closed_at'] ? \Carbon\Carbon::parse($track['closed_at'])->format('F j, Y g:i A') : '—' }}
                </p>
                @if(!empty($track['closure_reason']))
                    <p class="mb-0"><strong>Reason:</strong> {{ $track['closure_reason'] }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="card-responsive-footer">
        @if($hasReceipt)
            <a href="{{ route('official-receipt.generate', $data['request_id']) }}" 
               target="_blank" 
               class="btn-receipt" 
               style="text-decoration: none;">
                Official Receipt
            </a>
        @endif

        @if($canUpload)
            <button class="btn-success" onclick="showUploadModal({{ $data['request_id'] }})">
                Upload Receipt
            </button>
        @endif
        @if($canCancel)
            <button class="btn-danger" onclick="showCancelModal({{ $data['request_id'] }})">
                Cancel Request
            </button>
        @endif
    </div>
</div>