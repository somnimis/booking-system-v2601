<div class="fee-items">
    @foreach(['facility' => 'Facilities', 'equipment' => 'Equipment', 'service' => 'Services'] as $type => $label)
        @php $typeItems = collect($breakdown)->where('type', $type); @endphp
        @if($typeItems->isNotEmpty())
            <div class="fee-section mb-3">
                <p class="mb-2 text-primary fw-bold">{{ $label }}</p>
                @foreach($typeItems as $item)
                    <div class="fee-item d-flex justify-content-between mb-2">
                        <span>{{ $item['name'] }}</span>
                        <strong>₱{{ number_format($item['total_fee'], 2) }}</strong>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

    <div class="total-fee mt-4 pt-3 border-top d-flex justify-content-between">
        <h6 class="mb-0 fw-bold">Total Amount</h6>
        <h6 class="mb-0 fw-bold">₱{{ number_format($total, 2) }}</h6>
    </div>
</div>