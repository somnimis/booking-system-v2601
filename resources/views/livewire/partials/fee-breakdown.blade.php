<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0" style="border-color: #e9ecef;">
        <thead class="bg-light">
            <tr class="text-muted small" style="border-bottom: 1px solid #dee2e6;">
                <th style="width: 30%;">Resource</th>
                <th class="text-center" style="width: 15%;">Type</th>
                <th class="text-center" style="width: 15%;">Rate</th>
                <th class="text-center" style="width: 15%;">Duration</th>
                <th class="text-end" style="width: 12%;">Base Fee</th>
                <th class="text-end" style="width: 13%;">Total</th>
            </tr>
        </thead>
        <tbody>
            {{-- Facilities --}}
            @foreach($feeBreakdown['breakdown']['facilities'] as $fee)
                <tr class="small" style="border-color: #e9ecef;">
                    <td>{{ $fee['name'] }}</td>
                    <td class="text-center text-muted">Facility</td>
                    <td class="text-center text-muted">{{ $fee['rate_type'] }}</td>
                    <td class="text-center text-muted">{{ $fee['duration_text'] ? str_replace(' hours', 'h', $fee['duration_text']) : '-' }}</td>
                    <td class="text-end text-muted">₱{{ number_format($fee['unit_price'], 2) }}</td>
                    <td class="text-end fw-semibold">₱{{ number_format($fee['fee'], 2) }}</td>
                </tr>
            @endforeach

            {{-- Equipment --}}
            @foreach($feeBreakdown['breakdown']['equipment'] as $fee)
                <tr class="small" style="border-color: #e9ecef;">
                    <td>{{ $fee['name'] }} <span class="text-muted">x{{ $fee['quantity'] }}</span></td>
                    <td class="text-center text-muted">Equipment</td>
                    <td class="text-center text-muted">{{ $fee['rate_type'] }}</td>
                    <td class="text-center text-muted">{{ $fee['duration_text'] ? str_replace(' hours', 'h', $fee['duration_text']) : '-' }}</td>
                    <td class="text-end text-muted">₱{{ number_format($fee['unit_price'], 2) }}</td>
                    <td class="text-end fw-semibold">₱{{ number_format($fee['fee'], 2) }}</td>
                </tr>
            @endforeach

            {{-- Services --}}
            @foreach($feeBreakdown['breakdown']['services'] as $fee)
                <tr class="small" style="border-color: #e9ecef;">
                    <td>{{ $fee['name'] }}</td>
                    <td class="text-center text-muted">Service</td>
                    <td class="text-center text-muted">Per Event</td>
                    <td class="text-center text-muted">-</td>
                    <td class="text-end text-muted">₱{{ number_format($fee['unit_price'], 2) }}</td>
                    <td class="text-end fw-semibold">₱{{ number_format($fee['fee'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-light">
            <tr style="border-color: #dee2e6;">
                <td colspan="5" class="text-first fw-bold pt-3 pb-3 fs-6">Total Amount</td>
                <td class="text-end fw-bold text-primary pt-3 pb-3 fs-5">₱{{ number_format($feeBreakdown['approved_fee'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>