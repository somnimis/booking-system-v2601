@php
    $half = ceil(count($services) / 2);
    $columns = [array_slice($services, 0, $half), array_slice($services, $half)];
@endphp

@foreach($columns as $column)
    <div class="col-md-6">
        @foreach($column as $service)
            @php
                $checked = in_array($service->service_id, $selectedIds);
                $fee = (float) ($service->service_fee ?? 0);
                $feeText = $fee > 0 ? " (₱" . number_format($fee, 2) . ")" : "";
            @endphp
            <div class="form-check mb-2">
                <input class="form-check-input service-checkbox" type="checkbox" value="{{ $service->service_id }}" id="service_{{ $service->service_id }}" {{ $checked ? 'checked' : '' }}>
                <label class="form-check-label" for="service_{{ $service->service_id }}">
                    {{ $service->service_name }}{{ $feeText }}
                </label>
            </div>
        @endforeach
    </div>
@endforeach