<?php

namespace App\Services;

use App\Models\RequisitionForm;
use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\FormStatus;
use App\Models\CalendarEvent;
use App\Models\RequestedService;
use App\Models\ExtraService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckAvailabilityService
{
    private const ACTIVE_STATUSES = ['Pending Approval', 'Awaiting Payment', 'Reserved'];

    /**
     * Single source of truth for availability checks (public + admin).
     *
     * Normalized input:
     *   [
     *     'start_date' => 'Y-m-d',
     *     'end_date'   => 'Y-m-d',
     *     'start_time' => 'H:i'|null,
     *     'end_time'   => 'H:i'|null,
     *     'all_day'    => bool,
     *     'items'      => [
     *       ['type' => 'facility',  'facility_id'  => int],
     *       ['type' => 'equipment', 'equipment_id' => int, 'quantity' => int],
     *       ['type' => 'service',   'service_id'   => int],
     *     ],
     *     'current_request_id' => int|null,
     *   ]
     *
     * Returns:
     *   [
     *     'available'       => bool,          // false only when hard_conflicts exist
     *     'hard_conflicts'  => array,         // facility conflicts + equipment shortages (block submit)
     *     'soft_conflicts'  => array,         // service conflicts (warnings only)
     *   ]
     *
     * Notes:
     *  - Services are soft-blocking: surfaced as warnings, do not prevent submission.
     *  - Grace period (+4h cleanup) applies to facilities only (via checkFacilityAvailability).
     *  - Equipment is quantity-based; no grace period.
     */
    public function checkAll(array $payload): array
    {
        $startDate = $payload['start_date'];
        $endDate = $payload['end_date'];
        $allDay = (bool) ($payload['all_day'] ?? false);
        $startTime = $allDay ? '00:00:00' : ($payload['start_time'] ?? null);
        $endTime = $allDay ? '23:59:59' : ($payload['end_time'] ?? null);
        $currentRequestId = $payload['current_request_id'] ?? null;

        $hardConflicts = [];
        $softConflicts = [];

        foreach ($payload['items'] ?? [] as $item) {
            $type = $item['type'] ?? null;

            if ($type === 'facility') {
                $conflicts = $this->checkFacilityAvailability(
                    $item['facility_id'],
                    $startDate,
                    $endDate,
                    $startTime,
                    $endTime,
                    $allDay,
                    $currentRequestId
                );

                if (!empty($conflicts)) {
                    $hardConflicts[] = [
                        'type' => 'facility',
                        'id' => $item['facility_id'],
                        'name' => $conflicts[0]['name'] ?? 'Unknown Facility',
                        'conflicts' => $conflicts,
                    ];
                }
            } elseif ($type === 'equipment') {
                $available = $this->checkEquipmentAvailability(
                    $item['equipment_id'],
                    $startDate,
                    $endDate,
                    $allDay,
                    $currentRequestId
                );

                $requested = $item['quantity'] ?? 1;

                if ($available < $requested) {
                    $hardConflicts[] = [
                        'type' => 'equipment',
                        'id' => $item['equipment_id'],
                        'name' => $item['name'] ?? 'Unknown Equipment',
                        'available' => $available,
                        'requested' => $requested,
                        'message' => "Only {$available} available, requested {$requested}",
                    ];
                }
            } elseif ($type === 'service') {
                $conflicts = $this->checkServiceAvailability(
                    $item['service_id'],
                    $startDate,
                    $endDate,
                    $startTime,
                    $endTime,
                    $allDay,
                    $currentRequestId
                );

                if (!empty($conflicts)) {
                    $softConflicts[] = [
                        'type' => 'service',
                        'id' => $item['service_id'],
                        'name' => $conflicts[0]['name'] ?? 'Unknown Service',
                        'conflicts' => $conflicts,
                    ];
                }
            }
        }

        return [
            'available' => empty($hardConflicts),
            'hard_conflicts' => $hardConflicts,
            'soft_conflicts' => $softConflicts,
        ];
    }

    /**
     * Check if a requisition form has any facilities
     */
    private function hasFacilities($form): bool
    {
        if ($form instanceof RequisitionForm) {
            $exists = $form->requestedFacilities()->exists();
            \Log::info('hasFacilities check', [
                'request_id' => $form->request_id,
                'has_facilities' => $exists
            ]);
            return $exists;
        }
        return false;
    }

    /**
     * Check facility availability including requisition forms and calendar events
     */
    public function checkFacilityAvailability($facilityId, $startDate, $endDate, $startTime, $endTime, $allDay = false, $currentRequestId = null)
    {
        $conflicts = [];

        // 1. Check requisition forms
        $requisitionConflicts = $this->checkRequisitionFacilityConflicts(
            $facilityId,
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $allDay,
            $currentRequestId
        );
        $conflicts = array_merge($conflicts, $requisitionConflicts);

        // 2. Check calendar events
        $calendarConflicts = $this->checkCalendarFacilityConflicts(
            $facilityId,
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $allDay
        );
        $conflicts = array_merge($conflicts, $calendarConflicts);

        return $conflicts;
    }

    /**
     * Check service availability from both requisition forms and calendar events.
     *
     * Services are exclusive time-slot resources (no quantity model), so any
     * overlapping active booking on the same service is a hard conflict.
     *
     * @return array List of conflict entries (empty if available).
     */
    public function checkServiceAvailability($serviceId, $startDate, $endDate, $startTime, $endTime, $allDay = false, $currentRequestId = null): array
    {
        $conflicts = [];

        // 1. Requisition forms (requested_services)
        $requisitionQuery = RequestedService::where('service_id', $serviceId)
            ->whereHas('requisitionForm', function ($q) use ($currentRequestId, $startDate, $endDate, $startTime, $endTime, $allDay) {
                $q->whereIn('status_id', function ($sq) {
                    $sq->select('status_id')
                        ->from('form_statuses')
                        ->whereIn('status_name', self::ACTIVE_STATUSES);
                });

                if ($currentRequestId) {
                    $q->where('request_id', '!=', $currentRequestId);
                }

                $this->addDateOverlapCondition($q, $startDate, $endDate, $startTime, $endTime, $allDay, false);
            })
            ->with(['requisitionForm.formStatus', 'service']);

        foreach ($requisitionQuery->get() as $requested) {
            $form = $requested->requisitionForm;

            $conflicts[] = [
                'type' => 'service',
                'id' => $requested->service_id,
                'name' => $requested->service->service_name ?? 'Unknown Service',
                'source' => 'requisition',
                'status' => $form->formStatus->status_name ?? null,
                'request_id' => $form->request_id,
                'event_id' => null,
                'schedule' => [
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'start_time' => $form->start_time,
                    'end_time' => $form->end_time,
                    'all_day' => $form->all_day,
                ],
                'conflict_reason' => 'This service is already booked for an overlapping time slot',
            ];
        }

        // 2. Calendar events (event_services)
        // Mirrors checkCalendarFacilityConflicts() but without grace period logic,
        // since services don't need cleanup buffers.
        $calendarQuery = CalendarEvent::whereHas('services', function ($q) use ($serviceId) {
            $q->where('event_services.service_id', $serviceId);
        });

        // Date-overlap narrowing only — time filtering happens in PHP (DB-dialect-agnostic).
        $calendarQuery->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        $reqStart = Carbon::parse("$startDate $startTime");
        $reqEnd = Carbon::parse("$endDate $endTime");

        foreach ($calendarQuery->get() as $event) {
            if (!$event->all_day && !$allDay) {
                $evStart = Carbon::parse("{$event->start_date} {$event->start_time}");
                $evEnd = Carbon::parse("{$event->end_date} {$event->end_time}");
                if (!($reqStart < $evEnd && $reqEnd > $evStart)) {
                    continue;
                }
            }

            $conflicts[] = [
                'type' => 'service',
                'id' => $serviceId,
                'name' => $event->services->firstWhere('service_id', $serviceId)->service_name ?? 'Unknown Service',
                'source' => 'calendar_event',
                'status' => null,
                'request_id' => null,
                'event_id' => $event->event_id,
                'conflict_reason' => $event->all_day
                    ? 'This service is reserved for an all-day school event'
                    : 'This service is reserved for a school event during this time',
                'schedule' => [
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'all_day' => $event->all_day,
                ],
            ];
        }

        return $conflicts;
    }

    /**
     * Check facility conflicts in requisition forms
     */
    private function checkRequisitionFacilityConflicts($facilityId, $startDate, $endDate, $startTime, $endTime, $allDay, $currentRequestId = null)
    {
        $query = RequisitionForm::whereHas('requestedFacilities', function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId);
        })
            ->whereIn('status_id', function ($q) {
                $q->select('status_id')->from('form_statuses')->whereIn('status_name', self::ACTIVE_STATUSES);
            });

        if ($currentRequestId) {
            $query->where('request_id', '!=', $currentRequestId);
        }

        $this->addDateOverlapCondition($query, $startDate, $endDate, $startTime, $endTime, $allDay, true);

        $results = $query->get();

        // Filter out non-overlapping time slots and apply grace period logic
        $filtered = $results->filter(function ($form) use ($startDate, $endDate, $startTime, $endTime, $allDay) {
            // 1. All-day existing booking blocks everything on those dates
            if ($form->all_day) {
                return true;
            }

            // 2. Request is all-day, blocks all timed bookings on those dates
            if ($allDay) {
                return true;
            }

            // 3. Direct time overlap check
            $reqStart = Carbon::parse("$startDate $startTime");
            $reqEnd = Carbon::parse("$endDate $endTime");
            $exStart = Carbon::parse("{$form->start_date} {$form->start_time}");
            $exEnd = Carbon::parse("{$form->end_date} {$form->end_time}");

            if ($reqStart < $exEnd && $reqEnd > $exStart) {
                return true;
            }

            // 4. Grace period check (only for facilities)
            // If request starts after existing ends, check if the gap is less
            // than the existing form's cleanup_hours.
            if ($reqStart >= $exEnd) {
                $cleanupHours = $form->cleanup_hours ?? 4;
                $graceEnd = $exEnd->copy()->addHours($cleanupHours);

                if ($reqStart->lt($graceEnd)) {
                    return true; // Blocked by grace period
                }
            }

            return false;
        });

        \Log::info('CheckAvailabilityService: Facility conflict check', [
            'facility_id' => $facilityId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'found_count' => $filtered->count(),
        ]);

        return $filtered->map(function ($form) use ($startDate, $startTime) {
            $hasFacilities = $this->hasFacilities($form);
            $needsGracePeriod = $hasFacilities;

            $isBlocking = in_array($form->formStatus->status_name, ['Awaiting Payment', 'Reserved']);
            $isWarning = $form->formStatus->status_name === 'Pending Approval';

            return [
                'type' => 'facility',
                'id' => $form->requestedFacilities->first()->facility_id ?? null,
                'name' => $form->requestedFacilities->first()->facility->facility_name ?? 'Unknown',
                'event_title' => $form->event_title,
                'source' => 'requisition',
                'status' => $form->formStatus->status_name,
                'request_id' => $form->request_id,
                'event_id' => null,
                'has_facilities' => $hasFacilities,
                'needs_grace_period' => $needsGracePeriod,
                'conflict_reason' => $this->determineConflictReason($form, $startDate, $startTime, $needsGracePeriod),
                'schedule' => [
                    'start_date' => $form->start_date,
                    'end_date' => $form->end_date,
                    'start_time' => $form->start_time,
                    'end_time' => $form->end_time,
                    'all_day' => $form->all_day,
                ],
                'severity' => $isBlocking ? 'block' : ($isWarning ? 'warning' : 'info'),
                'can_proceed' => !$isBlocking,
            ];
        })->toArray();
    }
    /**
     * Check facility conflicts in calendar events
     */
    private function checkCalendarFacilityConflicts($facilityId, $startDate, $endDate, $startTime, $endTime, $allDay)
    {
        $query = CalendarEvent::whereHas('venues', function ($q) use ($facilityId) {
            $q->where('event_venues.facility_id', $facilityId);
        });

        // Date-overlap narrowing only — time filtering happens in PHP (DB-dialect-agnostic).
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        $reqStart = Carbon::parse("$startDate $startTime");
        $reqEnd = Carbon::parse("$endDate $endTime");

        return $query->get()
            ->filter(function ($event) use ($allDay, $reqStart, $reqEnd) {
                // All-day on either side always conflicts.
                if ($event->all_day || $allDay) {
                    return true;
                }

                $evStart = Carbon::parse("{$event->start_date} {$event->start_time}");
                $evEnd = Carbon::parse("{$event->end_date} {$event->end_time}");

                return $reqStart < $evEnd && $reqEnd > $evStart;
            })
            ->map(function ($event) {
                return [
                    'type' => 'facility',
                    'id' => $event->venues->first()->facility_id ?? null,
                    'name' => $event->venues->first()->facility->facility_name ?? 'Unknown',
                    'source' => 'calendar_event',
                    'status' => null,
                    'request_id' => null,
                    'event_id' => $event->event_id,
                    'has_facilities' => true,
                    'needs_grace_period' => true,
                    'conflict_reason' => $event->all_day
                        ? 'This facility is booked for an all-day school event'
                        : 'This facility is booked for a school event during this time',
                    'schedule' => [
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'all_day' => $event->all_day,
                    ],
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Sum of equipment quantity already booked via calendar events
     * for the given window. Time filtering is PHP-side (DB-dialect-agnostic).
     */
    private function getCalendarEquipmentBookedQuantity($equipmentId, $startDate, $endDate, $startTime, $endTime, $allDay): int
    {
        $query = CalendarEvent::whereHas('equipment', function ($q) use ($equipmentId) {
            $q->where('event_equipment.equipment_id', $equipmentId);
        });

        // Date-overlap narrowing only.
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        $reqStart = Carbon::parse("$startDate $startTime");
        $reqEnd = Carbon::parse("$endDate $endTime");

        return $query->with('equipment')->get()
            ->filter(function ($event) use ($allDay, $reqStart, $reqEnd) {
                if ($event->all_day || $allDay) {
                    return true;
                }
                $evStart = Carbon::parse("{$event->start_date} {$event->start_time}");
                $evEnd = Carbon::parse("{$event->end_date} {$event->end_time}");
                return $reqStart < $evEnd && $reqEnd > $evStart;
            })
            ->sum(function ($event) use ($equipmentId) {
                $pivot = $event->equipment->firstWhere('equipment_id', $equipmentId);
                return $pivot ? (int) $pivot->pivot->quantity : 0;
            });
    }
    /**
     * Check equipment availability including requisition forms and calendar events
     */
    public function checkEquipmentAvailability($equipmentId, $startDate, $endDate, $allDay = false, $currentRequestId = null): int
    {
        $totalAvailable = EquipmentItem::where('equipment_id', $equipmentId)
            ->where('status_id', 1)
            ->whereIn('condition_id', [1, 2, 3])
            ->count();

        $existingBookings = 0;

        // 1. Requisition forms
        $requisitionQuery = RequestedEquipment::where('equipment_id', $equipmentId)
            ->whereHas('requisitionForm', function ($q) use ($startDate, $endDate, $currentRequestId, $allDay) {
                $q->whereIn('status_id', function ($sq) {
                    $sq->select('status_id')
                        ->from('form_statuses')
                        ->whereIn('status_name', self::ACTIVE_STATUSES);
                });

                if ($currentRequestId) {
                    $q->where('request_id', '!=', $currentRequestId);
                }

                $this->addDateOverlapCondition($q, $startDate, $endDate, null, null, $allDay, false);
            });

        $existingBookings += $requisitionQuery->sum('quantity');

        // 2. Calendar events (equipment is date-granular; times are no-ops)
        $existingBookings += $this->getCalendarEquipmentBookedQuantity(
            $equipmentId,
            $startDate,
            $endDate,
            '00:00:00',
            '23:59:59',
            $allDay
        );

        return max(0, $totalAvailable - $existingBookings);
    }


    /**
     * Add date overlap condition to query.
     *
     * Note: Because MySQL time math with bindings is brittle, we pull
     * date-overlapping candidates here and let the caller filter time
     * overlap (and grace period) accurately with Carbon.
     */
    private function addDateOverlapCondition($query, $startDate, $endDate, $startTime, $endTime, $allDay, $applyGracePeriod = false)
    {
        // Base overlap: dates must intersect
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        // If it's an all-day request OR the query is for equipment (null times),
        // date overlap is sufficient.
        if ($allDay || is_null($startTime) || is_null($endTime)) {
            return;
        }

        // For timed requests, we need to evaluate time overlap in PHP later.
        // We can still fetch all date-overlapping records (the dataset is small
        // enough per-facility that filtering post-query is performant).
    }

    /**
     * Determine conflict reason for display
     */
    private function determineConflictReason($existingItem, $newStartDate, $newStartTime, $needsGracePeriod): string
    {
        if ($needsGracePeriod && !$existingItem->all_day) {
            $existingEnd = Carbon::parse($existingItem->end_date . ' ' . $existingItem->end_time);
            $cleanupHours = $existingItem->cleanup_hours ?? 4;
            $graceEnd = $existingEnd->copy()->addHours($cleanupHours);
            $newStart = Carbon::parse("$newStartDate $newStartTime");

            if ($newStart->lt($graceEnd)) {
                return "Venue requires {$cleanupHours}-hour cleanup before next booking. Available after " . $graceEnd->format('g:i A');
            }
        }

        if ($existingItem->formStatus && $existingItem->formStatus->status_name === 'Pending Approval') {
            return "Another request for this item is pending approval";
        }

        return "This time slot conflicts with an existing booking";
    }

    /**
     * Compile conflicts into a Blade-rendered HTML string.
     * Strictly server-side formatting to keep the frontend dumb.
     */
    public function formatConflictsToHtml(array $conflictItems): string
    {
        return view('partials.conflict-content', [
            'conflictItems' => $conflictItems
        ])->render();
    }
}