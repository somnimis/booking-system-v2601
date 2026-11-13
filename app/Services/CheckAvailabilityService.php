<?php

namespace App\Services;

use App\Models\RequisitionForm;
use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\CalendarEvent;
use App\Models\RequestedService;
use Carbon\Carbon;

class CheckAvailabilityService
{
    private const ACTIVE_STATUSES = ['Pending Approval', 'Verifying Payment', 'Awaiting Payment', 'Reserved'];
    private const BLOCKING_STATUSES = ['Awaiting Payment', 'Reserved', 'Verifying Payment'];

    /** Used when a record / payload has no cleanup_hours value. */
    private const DEFAULT_CLEANUP_HOURS = 4;

    /** Sanity clamp so a bad value (e.g. -5 or 99999) can't break the math. */
    private const MAX_CLEANUP_HOURS = 168;

    /**
     * The SQL pre-filter must be wider than the request, otherwise bookings whose
     * *cleanup window* spills into the request are never loaded.
     * MAX_CLEANUP_HOURS / 24 = 7 days, +1 day of slack.
     */
    private const PREFILTER_BUFFER_DAYS = 8;

    // ---------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------

    public function checkAll(array $payload): array
    {
        $errors = $this->validatePayload($payload);
        if (!empty($errors)) {
            return [
                'available' => false,
                'errors' => $errors,
                'hard_conflicts' => [],
                'soft_conflicts' => [],
            ];
        }

        $startDate = $this->normalizeDate($payload['start_date']);
        $endDate = $this->normalizeDate($payload['end_date']);
        $allDay = $this->toBool($payload['all_day'] ?? false);
        $startTime = $allDay ? '00:00:00' : $this->normalizeTime($payload['start_time'] ?? null);
        $endTime = $allDay ? '23:59:59' : $this->normalizeTime($payload['end_time'] ?? null);
        $currentRequestId = $payload['current_request_id'] ?? null;
        $cleanupHours = $this->resolveCleanup($payload['cleanup_hours'] ?? null);

        $hardConflicts = [];
        $softConflicts = [];

        // Collapse duplicate items so the same equipment listed twice is summed,
        // and the same facility/service is only checked once.
        $facilityIds = [];
        $serviceIds = [];
        $equipment = []; // id => ['quantity' => int, 'name' => string|null]

        foreach ($payload['items'] ?? [] as $item) {
            switch ($item['type'] ?? null) {
                case 'facility':
                    $facilityIds[$item['facility_id']] = true;
                    break;
                case 'service':
                    $serviceIds[$item['service_id']] = true;
                    break;
                case 'equipment':
                    $id = $item['equipment_id'];
                    $equipment[$id]['quantity'] = ($equipment[$id]['quantity'] ?? 0) + (int) ($item['quantity'] ?? 1);
                    $equipment[$id]['name'] = $equipment[$id]['name'] ?? ($item['name'] ?? null);
                    break;
            }
        }

        foreach (array_keys($facilityIds) as $facilityId) {
            $conflicts = $this->checkFacilityAvailability(
                $facilityId,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $allDay,
                $currentRequestId,
                $cleanupHours
            );

            if (!empty($conflicts)) {
                $hardConflicts[] = [
                    'type' => 'facility',
                    'id' => $facilityId,
                    'name' => $conflicts[0]['name'] ?? 'Unknown Facility',
                    'conflicts' => $conflicts,
                ];
            }
        }

        foreach ($equipment as $equipmentId => $info) {
            $requested = $info['quantity'];
            $result = $this->checkEquipmentAvailability(
                $equipmentId,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $allDay,
                $currentRequestId,
                $cleanupHours
            );

            if ($result['available'] < $requested) {
                $hardConflicts[] = [
                    'type' => 'equipment',
                    'id' => $equipmentId,
                    'name' => $info['name'] ?? 'Unknown Equipment',
                    'available' => $result['available'],
                    'requested' => $requested,
                    'message' => "Only {$result['available']} available, requested {$requested}",
                    'conflicts' => $result['conflicts'],
                ];
            }
        }

        foreach (array_keys($serviceIds) as $serviceId) {
            $conflicts = $this->checkServiceAvailability(
                $serviceId,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $allDay,
                $currentRequestId,
                $cleanupHours
            );

            if (!empty($conflicts)) {
                $softConflicts[] = [
                    'type' => 'service',
                    'id' => $serviceId,
                    'name' => $conflicts[0]['name'] ?? 'Unknown Service',
                    'conflicts' => $conflicts,
                ];
            }
        }

        return [
            'available' => empty($hardConflicts),
            'hard_conflicts' => $hardConflicts,
            'soft_conflicts' => $softConflicts,
        ];
    }

    public function checkFacilityAvailability(
        $facilityId,
        $startDate,
        $endDate,
        $startTime,
        $endTime,
        $allDay = false,
        $currentRequestId = null,
        $cleanupHours = null
    ) {
        $request = $this->requestWindow($startDate, $endDate, $startTime, $endTime, $allDay, $cleanupHours);

        return array_merge(
            $this->checkRequisitionFacilityConflicts($facilityId, $request, $currentRequestId),
            $this->checkCalendarFacilityConflicts($facilityId, $request)
        );
    }

    public function checkServiceAvailability(
        $serviceId,
        $startDate,
        $endDate,
        $startTime,
        $endTime,
        $allDay = false,
        $currentRequestId = null,
        $cleanupHours = null
    ): array {
        $request = $this->requestWindow($startDate, $endDate, $startTime, $endTime, $allDay, $cleanupHours);
        [$from, $to] = $this->prefilterRange($request);
        $conflicts = [];

        $rows = RequestedService::where('service_id', $serviceId)
            ->whereHas('requisitionForm', function ($q) use ($currentRequestId, $from, $to) {
                $this->applyActiveStatusFilter($q);
                if ($currentRequestId) {
                    $q->where('request_id', '!=', $currentRequestId);
                }
                $q->where('start_date', '<=', $to)->where('end_date', '>=', $from);
            })
            ->with(['requisitionForm.formStatus', 'service'])
            ->get();

        foreach ($rows as $requested) {
            $form = $requested->requisitionForm;
            if (!$form)
                continue;

            $existing = $this->recordWindow($form);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $conflicts[] = [
                'type' => 'service',
                'id' => $requested->service_id,
                'name' => $requested->service->service_name ?? 'Unknown Service',
                'source' => 'requisition',
                'status' => $form->formStatus->status_name ?? null,
                'request_id' => $form->request_id,
                'event_id' => null,
                'schedule' => $this->scheduleOf($form),
                'conflict_reason' => $this->conflictReason($form->formStatus->status_name ?? null, $existing),
            ];
        }

        $events = CalendarEvent::whereHas('services', fn($q) => $q->where('event_services.service_id', $serviceId))
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->with('services')
            ->get();

        foreach ($events as $event) {
            $existing = $this->recordWindow($event);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $conflicts[] = [
                'type' => 'service',
                'id' => $serviceId,
                'name' => $event->services->firstWhere('service_id', $serviceId)->service_name ?? 'Unknown Service',
                'source' => 'calendar_event',
                'status' => null,
                'request_id' => null,
                'event_id' => $event->event_id,
                'conflict_reason' => $this->truthy($event->all_day)
                    ? 'This service is reserved for an all-day school event'
                    : 'This service is reserved for a school event during this time',
                'schedule' => $this->scheduleOf($event),
            ];
        }

        return $conflicts;
    }

    public function checkEquipmentAvailability(
        $equipmentId,
        $startDate,
        $endDate,
        $startTime = null,
        $endTime = null,
        $allDay = false,
        $currentRequestId = null,
        $cleanupHours = null
    ): array {
        $request = $this->requestWindow($startDate, $endDate, $startTime, $endTime, $allDay, $cleanupHours);
        [$from, $to] = $this->prefilterRange($request);

        $totalAvailable = EquipmentItem::where('equipment_id', $equipmentId)
            ->where('status_id', 1)
            ->whereIn('condition_id', [1, 2, 3])
            ->count();

        $conflicts = [];
        $usage = []; // list of ['start' => Carbon, 'end' => Carbon, 'qty' => int]

        $rows = RequestedEquipment::where('equipment_id', $equipmentId)
            ->whereHas('requisitionForm', function ($q) use ($from, $to, $currentRequestId) {
                $this->applyActiveStatusFilter($q);
                if ($currentRequestId) {
                    $q->where('request_id', '!=', $currentRequestId);
                }
                $q->where('start_date', '<=', $to)->where('end_date', '>=', $from);
            })
            ->with('requisitionForm.formStatus')
            ->get();

        foreach ($rows as $row) {
            $form = $row->requisitionForm;
            if (!$form)
                continue;

            $existing = $this->recordWindow($form);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $qty = max(0, (int) ($row->quantity ?? 1));
            $usage[] = ['start' => $existing['start'], 'end' => $existing['blockedUntil'], 'qty' => $qty];

            $conflicts[] = [
                'type' => 'equipment',
                'id' => $equipmentId,
                'name' => $form->event_title, // Used as the event title in UI
                'source' => 'requisition',
                'status' => $form->formStatus->status_name ?? null,
                'request_id' => $form->request_id,
                'event_id' => null,
                'event_title' => $form->event_title,
                'quantity' => $qty,
                'conflict_reason' => $this->conflictReason($form->formStatus->status_name ?? null, $existing),
                'schedule' => $this->scheduleOf($form),
            ];
        }

        $events = CalendarEvent::whereHas('equipment', fn($q) => $q->where('event_equipment.equipment_id', $equipmentId))
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->with('equipment')
            ->get();

        foreach ($events as $event) {
            $existing = $this->recordWindow($event);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $pivot = $event->equipment->firstWhere('equipment_id', $equipmentId);
            $qty = $pivot ? max(0, (int) ($pivot->pivot->quantity ?? 1)) : 0;
            $usage[] = ['start' => $existing['start'], 'end' => $existing['blockedUntil'], 'qty' => $qty];

            $conflicts[] = [
                'type' => 'equipment',
                'id' => $equipmentId,
                'name' => $event->title ?? 'School Event',
                'source' => 'calendar_event',
                'status' => null,
                'request_id' => null,
                'event_id' => $event->event_id,
                'event_title' => $event->title ?? 'School Event',
                'quantity' => $qty,
                'conflict_reason' => $this->truthy($event->all_day)
                    ? 'This equipment is reserved for an all-day school event'
                    : 'This equipment is reserved for a school event during this time',
                'schedule' => $this->scheduleOf($event),
            ];
        }

        $peak = $this->peakConcurrentUsage($usage, $request['start'], $request['blockedUntil']);

        return [
            'available' => max(0, $totalAvailable - $peak),
            'conflicts' => $conflicts,
        ];
    }

    public function formatConflictsToHtml(array $conflictItems): string
    {
        return view('partials.conflict-content', [
            'conflictItems' => $conflictItems,
        ])->render();
    }

    // ---------------------------------------------------------------------
    // Facility conflict helpers
    // ---------------------------------------------------------------------

    private function checkRequisitionFacilityConflicts($facilityId, array $request, $currentRequestId = null): array
    {
        [$from, $to] = $this->prefilterRange($request);

        $query = RequisitionForm::whereHas('requestedFacilities', fn($q) => $q->where('facility_id', $facilityId))
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->with(['formStatus', 'requestedFacilities.facility']);

        $this->applyActiveStatusFilter($query);

        if ($currentRequestId) {
            $query->where('request_id', '!=', $currentRequestId);
        }

        $out = [];
        foreach ($query->get() as $form) {
            $existing = $this->recordWindow($form);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $status = $form->formStatus->status_name ?? null;
            $isBlocking = in_array($status, self::BLOCKING_STATUSES, true);
            $isWarning = $status === 'Pending Approval';

            // Report the facility that was actually checked, not just the form's first one.
            $matched = $form->requestedFacilities->firstWhere('facility_id', $facilityId);

            $out[] = [
                'type' => 'facility',
                'id' => $matched->facility_id ?? $facilityId,
                'name' => $matched->facility->facility_name ?? 'Unknown',
                'event_title' => $form->event_title,
                'source' => 'requisition',
                'status' => $status,
                'request_id' => $form->request_id,
                'event_id' => null,
                'has_facilities' => true,
                'needs_grace_period' => true,
                'conflict_reason' => $this->conflictReason($status, $existing),
                'schedule' => $this->scheduleOf($form),
                'severity' => $isBlocking ? 'block' : ($isWarning ? 'warning' : 'info'),
                'can_proceed' => !$isBlocking,
            ];
        }

        return $out;
    }

    private function checkCalendarFacilityConflicts($facilityId, array $request): array
    {
        [$from, $to] = $this->prefilterRange($request);

        $events = CalendarEvent::whereHas('venues', fn($q) => $q->where('event_venues.facility_id', $facilityId))
            ->where('start_date', '<=', $to)
            ->where('end_date', '>=', $from)
            ->with('venues.facility')
            ->get();

        $out = [];
        foreach ($events as $event) {
            $existing = $this->recordWindow($event);
            if (!$this->windowsOverlap($existing, $request))
                continue;

            $venue = $event->venues->firstWhere('facility_id', $facilityId);

            $out[] = [
                'type' => 'facility',
                'id' => $venue->facility_id ?? $facilityId,
                'name' => $venue->facility->facility_name ?? 'Unknown',
                'source' => 'calendar_event',
                'status' => null,
                'request_id' => null,
                'event_id' => $event->event_id,
                'has_facilities' => true,
                'needs_grace_period' => true,
                'conflict_reason' => $this->truthy($event->all_day)
                    ? 'This facility is booked for an all-day school event'
                    : 'This facility is booked for a school event during this time',
                'schedule' => $this->scheduleOf($event),
            ];
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Time-window math
    // ---------------------------------------------------------------------

    /**
     * Build the occupied window for a booking:
     *   start        - first moment the resource is in use
     *   end          - end of use (exclusive)
     *   blockedUntil - end + cleanup (exclusive)
     *
     * All-day bookings occupy 00:00 of the first day up to 00:00 of the day AFTER
     * the last day. Non-all-day records with missing times fail safe to full days,
     * so bad data blocks rather than silently allowing double-bookings.
     */
    private function buildWindow($startDate, $endDate, $startTime, $endTime, bool $allDay, float $cleanupHours): array
    {
        $startDay = Carbon::parse($this->normalizeDate($startDate))->startOfDay();
        $endDay = Carbon::parse($this->normalizeDate($endDate))->startOfDay();

        $startTime = $allDay ? null : $this->normalizeTime($startTime);
        $endTime = $allDay ? null : $this->normalizeTime($endTime);

        $start = $startTime ? Carbon::parse($startDay->toDateString() . ' ' . $startTime) : $startDay->copy();
        $end = $endTime ? Carbon::parse($endDay->toDateString() . ' ' . $endTime) : $endDay->copy()->addDay();

        // Corrupt record (end <= start): treat as occupying through the end of its end date.
        if ($end <= $start) {
            $end = $endDay->copy()->addDay();
            if ($end <= $start) {
                $end = $start->copy()->addDay();
            }
        }

        return [
            'start' => $start,
            'end' => $end,
            'blockedUntil' => $end->copy()->addMinutes((int) round($cleanupHours * 60)),
        ];
    }

    private function requestWindow($startDate, $endDate, $startTime, $endTime, $allDay, $cleanupHours): array
    {
        return $this->buildWindow(
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $this->toBool($allDay),
            $this->resolveCleanup($cleanupHours)
        );
    }

    /** Window for an existing RequisitionForm or CalendarEvent. */
    private function recordWindow($record): array
    {
        return $this->buildWindow(
            $record->start_date, $record->end_date,
            $record->start_time, $record->end_time,
            $this->truthy($record->all_day),
            // Use the DB's cleanup_hours if it exists (RequisitionForm), otherwise fallback to the constant (CalendarEvent)
            $record->cleanup_hours ?? self::DEFAULT_CLEANUP_HOURS
        );
    }

    /**
     * Two bookings collide when their occupied windows (use + cleanup) intersect.
     * This is symmetric: the new booking's cleanup must finish before an existing
     * booking starts, and the existing booking's cleanup must finish before the new
     * one starts. Half-open intervals, so back-to-back with 0 cleanup is allowed.
     */
    private function windowsOverlap(array $a, array $b): bool
    {
        return $a['start'] < $b['blockedUntil'] && $b['start'] < $a['blockedUntil'];
    }

    /** Date range used for the SQL pre-filter, widened to include cleanup spillover. */
    private function prefilterRange(array $request): array
    {
        return [
            $request['start']->copy()->subDays(self::PREFILTER_BUFFER_DAYS)->toDateString(),
            $request['blockedUntil']->copy()->addDays(self::PREFILTER_BUFFER_DAYS)->toDateString(),
        ];
    }

    /** Maximum number of units in use at the same moment within [from, to). */
    private function peakConcurrentUsage(array $usage, Carbon $from, Carbon $to): int
    {
        $points = [];
        foreach ($usage as $u) {
            if ($u['qty'] <= 0)
                continue;
            $s = max($u['start']->getTimestamp(), $from->getTimestamp());
            $e = min($u['end']->getTimestamp(), $to->getTimestamp());
            if ($s >= $e)
                continue;
            $points[] = [$s, $u['qty']];
            $points[] = [$e, -$u['qty']];
        }

        // At equal timestamps process releases (negative) before acquisitions.
        usort($points, fn($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);

        $current = 0;
        $peak = 0;
        foreach ($points as [, $delta]) {
            $current += $delta;
            $peak = max($peak, $current);
        }

        return $peak;
    }

    // ---------------------------------------------------------------------
    // Validation & normalization
    // ---------------------------------------------------------------------

    private function validatePayload(array $payload): array
    {
        $errors = [];

        if (empty($payload['start_date']) || empty($payload['end_date'])) {
            return ['start_date and end_date are required.'];
        }

        try {
            $startDay = Carbon::parse($this->normalizeDate($payload['start_date']))->startOfDay();
            $endDay = Carbon::parse($this->normalizeDate($payload['end_date']))->startOfDay();
        } catch (\Throwable $e) {
            return ['start_date or end_date is not a valid date.'];
        }

        if ($endDay < $startDay) {
            $errors[] = 'End date cannot be before the start date.';
        }

        $allDay = $this->toBool($payload['all_day'] ?? false);

        if (!$allDay) {
            $st = $this->normalizeTime($payload['start_time'] ?? null);
            $et = $this->normalizeTime($payload['end_time'] ?? null);

            if (!$st || !$et) {
                $errors[] = 'Start time and end time are required unless the booking is all day.';
            } elseif (empty($errors)) {
                $start = Carbon::parse($startDay->toDateString() . ' ' . $st);
                $end = Carbon::parse($endDay->toDateString() . ' ' . $et);
                if ($end <= $start) {
                    $errors[] = 'End date/time must be after the start date/time.';
                }
                if (!$this->toBool($payload['allow_past'] ?? false) && $start < now()) {
                    $errors[] = 'The start date/time is in the past.';
                }
            }
        } elseif (!$this->toBool($payload['allow_past'] ?? false) && $startDay < now()->startOfDay()) {
            $errors[] = 'The start date is in the past.';
        }

        if (
            isset($payload['cleanup_hours']) && $payload['cleanup_hours'] !== '' &&
            (!is_numeric($payload['cleanup_hours']) || $payload['cleanup_hours'] < 0)
        ) {
            $errors[] = 'cleanup_hours must be a non-negative number.';
        }

        foreach ($payload['items'] ?? [] as $i => $item) {
            $type = $item['type'] ?? null;
            $n = $i + 1;

            if ($type === 'facility') {
                if (empty($item['facility_id']))
                    $errors[] = "Item #{$n}: facility_id is required.";
            } elseif ($type === 'service') {
                if (empty($item['service_id']))
                    $errors[] = "Item #{$n}: service_id is required.";
            } elseif ($type === 'equipment') {
                if (empty($item['equipment_id']))
                    $errors[] = "Item #{$n}: equipment_id is required.";
                $qty = $item['quantity'] ?? 1;
                if (!is_numeric($qty) || (int) $qty < 1 || (int) $qty != $qty) {
                    $errors[] = "Item #{$n}: quantity must be a whole number of at least 1.";
                }
            } else {
                $errors[] = "Item #{$n}: unknown item type.";
            }
        }

        return $errors;
    }

    /** Accepts 'Y-m-d', 'Y-m-d H:i:s', Carbon, or DateTime and returns 'Y-m-d'. */
    private function normalizeDate($value): string
    {
        return Carbon::parse($value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value)->toDateString();
    }

    /** Accepts '09:00', '09:00:00', '9:00 AM', Carbon, etc. Returns 'H:i:s' or null. */
    private function normalizeTime($value): ?string
    {
        if ($value === null || $value === '')
            return null;

        try {
            return Carbon::parse($value instanceof \DateTimeInterface ? $value->format('H:i:s') : (string) $value)->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function resolveCleanup($value): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return (float) self::DEFAULT_CLEANUP_HOURS;
        }

        return (float) max(0, min(self::MAX_CLEANUP_HOURS, (float) $value));
    }

    private function toBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function truthy($value): bool
    {
        return $value !== null && $this->toBool($value);
    }

    private function applyActiveStatusFilter($query): void
    {
        $query->whereIn('status_id', function ($sq) {
            $sq->select('status_id')->from('form_statuses')->whereIn('status_name', self::ACTIVE_STATUSES);
        });
    }

    private function scheduleOf($record): array
    {
        return [
            'start_date' => $record->start_date,
            'end_date' => $record->end_date,
            'start_time' => $record->start_time,
            'end_time' => $record->end_time,
            'all_day' => $record->all_day,
        ];
    }

    private function conflictReason(?string $status, array $existing): string
    {
        if ($status === 'Pending Approval') {
            return 'Another request for this item is pending approval.';
        }

        return 'This time slot conflicts with an existing booking. Available after '
            . $existing['blockedUntil']->format('M j, g:i A')
            . ' (includes cleanup time).';
    }
}