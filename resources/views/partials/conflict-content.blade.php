@php
    $facilities = collect($conflictItems)->where('type', 'facility');
    $services = collect($conflictItems)->where('type', 'service');
    $equipment = collect($conflictItems)->where('type', 'equipment');
@endphp

<div class="mb-3">

    <div class="d-flex flex-column gap-2">

        {{-- Facilities --}}
        @foreach($facilities as $item)
            <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-bold">
                        {{ $item['name'] }}
                    </div>
                    <span class="badge  bg-info-subtle text-info-emphasis">Facility</span>
                </div>

                @if(!empty($item['conflicts']))
                    <div class="small text-muted mb-2">Events that conflict with this resource:</div>

                    <div class="d-flex flex-column gap-2">
                        @foreach($item['conflicts'] as $conflict)
                            @php
                                $sched = $conflict['schedule'] ?? [];
                                $isAllDay = $sched['all_day'] ?? false;
                                $date = isset($sched['start_date'])
                                    ? \Carbon\Carbon::parse($sched['start_date'])->format('M j, Y')
                                    : 'Unknown';

                                $time = 'All Day';

                                if (!$isAllDay && isset($sched['start_time'], $sched['end_time'])) {
                                    $start = \Carbon\Carbon::parse($sched['start_time'])->format('g:i a');
                                    $end = \Carbon\Carbon::parse($sched['end_time'])->format('g:i a');
                                    $time = "{$start} - {$end}";
                                }
                            @endphp

                            <div class="bg-light rounded-2 p-2 small">
                                <div class="fw-semibold text-dark">
                                    {{ $conflict['event_title'] ?? 'Reserved' }}
                                </div>

                                <div class="text-muted mt-1">
                                    {{ $date }} &bull; {{ $time }}
                                </div>

                                @if(!empty($conflict['conflict_reason']))
                                    <div class="mt-1">
                                        <i class="bi bi-info-circle me-1 text-secondary"></i>
                                        {{ $conflict['conflict_reason'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Services --}}
        @foreach($services as $item)
            <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold text-dark">
                        <i class="bi bi-tools me-1"></i>
                        {{ $item['name'] }}
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Service</span>
                </div>
            </div>
        @endforeach

        {{-- Equipment --}}
        @foreach($equipment as $item)
            <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">
                        <i class="bi bi-cpu me-1"></i>
                        {{ $item['name'] }}
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis">Equipment</span>
                </div>

                @if(!empty($item['message']))
                    <div class="small mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ $item['message'] }}
                    </div>
                @endif
            </div>
        @endforeach

    </div>
</div>