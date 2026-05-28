<?php

namespace App\Services;

use App\Models\RequisitionForm;
use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\FormStatus;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckAvailabilityService
{
    private const GRACE_PERIOD_HOURS = 4;
    private const ACTIVE_STATUSES = ['Pending Approval', 'Awaiting Payment', 'Reserved'];
    private const EXCLUDED_STATUSES = ['Completed', 'Rejected', 'Cancelled'];

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
     * Add grace period to a datetime
     */
    private function addGracePeriod($date, $time): Carbon
    {
        return Carbon::parse("$date $time")->addHours(self::GRACE_PERIOD_HOURS);
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
     * Check facility conflicts in requisition forms
     */
    private function checkRequisitionFacilityConflicts($facilityId, $startDate, $endDate, $startTime, $endTime, $allDay, $currentRequestId = null)
    {
        $query = RequisitionForm::whereHas('requestedFacilities', function ($q) use ($facilityId) {
            $q->where('facility_id', $facilityId);
        })
            ->whereIn('status_id', function ($q) {
                $q->select('status_id')
                    ->from('form_statuses')
                    ->whereIn('status_name', self::ACTIVE_STATUSES);
            });

        if ($currentRequestId) {
            $query->where('request_id', '!=', $currentRequestId);
        }

        $this->addDateOverlapCondition($query, $startDate, $endDate, $startTime, $endTime, $allDay, true);

        $results = $query->get();

        // Debug log - remove after testing
        \Log::info('CheckAvailabilityService: Facility conflict check', [
            'facility_id' => $facilityId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'all_day' => $allDay,
            'current_request_id' => $currentRequestId,
            'found_count' => $results->count(),
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        return $results->map(function ($form) use ($startDate, $startTime) {
            $hasFacilities = $this->hasFacilities($form);
            $needsGracePeriod = $hasFacilities;

            // Determine if this is a block or warning
            $isBlocking = in_array($form->formStatus->status_name, ['Awaiting Payment', 'Reserved']);
            $isWarning = $form->formStatus->status_name === 'Pending Approval';

            return [
                'type' => 'facility',
                'id' => $form->requestedFacilities->first()->facility_id ?? null,
                'name' => $form->requestedFacilities->first()->facility->facility_name ?? 'Unknown',
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

        $this->addDateOverlapCondition($query, $startDate, $endDate, $startTime, $endTime, $allDay, false);

        return $query->get()->map(function ($event) {
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
                'conflict_reason' => 'This facility is booked for a school event',
                'schedule' => [
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'all_day' => $event->all_day,
                ]
            ];
        })->toArray();
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

        // 1. Check requisition forms
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

        // 2. Check calendar events
        $calendarQuery = CalendarEvent::whereHas('equipment', function ($q) use ($equipmentId) {
            $q->where('event_equipment.equipment_id', $equipmentId);
        });

        $this->addDateOverlapCondition($calendarQuery, $startDate, $endDate, null, null, $allDay, false);

        $calendarBookings = $calendarQuery->get()->sum(function ($event) use ($equipmentId) {
            $pivot = $event->equipment->firstWhere('equipment_id', $equipmentId);
            return $pivot ? $pivot->pivot->quantity : 0;
        });

        $existingBookings += $calendarBookings;

        return max(0, $totalAvailable - $existingBookings);
    }

    /**
     * Get all overlapping requests and events for a form
     */
    public function getOverlappingRequests($currentForm): array
    {
        try {
            $currentFacilityIds = $currentForm->requestedFacilities->pluck('facility_id')->toArray();
            $currentEquipmentIds = $currentForm->requestedEquipment->pluck('equipment_id')->toArray();

            if (empty($currentFacilityIds) && empty($currentEquipmentIds)) {
                return [];
            }

            $overlaps = [];

            // Check facility overlaps
            if (!empty($currentFacilityIds)) {
                $overlaps = array_merge($overlaps, $this->getFacilityOverlaps($currentForm, $currentFacilityIds));
            }

            // Check equipment overlaps
            if (!empty($currentEquipmentIds)) {
                $overlaps = array_merge($overlaps, $this->getEquipmentOverlaps($currentForm, $currentEquipmentIds));
            }

            return $overlaps;
        } catch (\Exception $e) {
            Log::error('Error finding overlapping requests', [
                'request_id' => $currentForm->request_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get facility overlaps from both requisitions and calendar events
     */
    private function getFacilityOverlaps($currentForm, array $facilityIds): array
    {
        $overlaps = [];

        // Requisition forms
        $requisitionOverlaps = RequisitionForm::where('request_id', '!=', $currentForm->request_id)
            ->whereNotIn('status_id', function ($q) {
                $q->select('status_id')->from('form_statuses')->whereIn('status_name', self::EXCLUDED_STATUSES);
            })
            ->whereHas('requestedFacilities', function ($q) use ($facilityIds) {
                $q->whereIn('facility_id', $facilityIds);
            })
            ->where(function ($dateQ) use ($currentForm) {
                $this->addScheduleOverlapCondition($dateQ, $currentForm);
            })
            ->with(['formStatus', 'requestedFacilities.facility', 'requestedEquipment.equipment'])
            ->get();

        foreach ($requisitionOverlaps as $form) {
            $hasFacilities = $this->hasFacilities($form);
            $overlaps[] = $this->formatOverlappingForm($form, $currentForm, 'requisition', $hasFacilities);
        }

        // Calendar events
        $calendarOverlaps = CalendarEvent::whereHas('venues', function ($q) use ($facilityIds) {
            $q->whereIn('event_venues.facility_id', $facilityIds);
        })
            ->where(function ($dateQ) use ($currentForm) {
                $this->addScheduleOverlapCondition($dateQ, $currentForm);
            })
            ->with('venues.facility')
            ->get();

        foreach ($calendarOverlaps as $event) {
            $overlaps[] = $this->formatOverlappingEvent($event, $currentForm);
        }

        return $overlaps;
    }

    /**
     * Get equipment overlaps from both requisitions and calendar events
     */
    private function getEquipmentOverlaps($currentForm, array $equipmentIds): array
    {
        $overlaps = [];

        // Requisition forms
        $requisitionOverlaps = RequisitionForm::where('request_id', '!=', $currentForm->request_id)
            ->whereNotIn('status_id', function ($q) {
                $q->select('status_id')->from('form_statuses')->whereIn('status_name', self::EXCLUDED_STATUSES);
            })
            ->whereHas('requestedEquipment', function ($q) use ($equipmentIds) {
                $q->whereIn('equipment_id', $equipmentIds);
            })
            ->where(function ($dateQ) use ($currentForm) {
                $this->addScheduleOverlapCondition($dateQ, $currentForm);
            })
            ->with(['formStatus', 'requestedFacilities.facility', 'requestedEquipment.equipment'])
            ->get();

        foreach ($requisitionOverlaps as $form) {
            $hasFacilities = $this->hasFacilities($form);
            $overlaps[] = $this->formatOverlappingForm($form, $currentForm, 'requisition', $hasFacilities);
        }

        // Calendar events
        $calendarOverlaps = CalendarEvent::whereHas('equipment', function ($q) use ($equipmentIds) {
            $q->whereIn('equipment_id', $equipmentIds);
        })
            ->where(function ($dateQ) use ($currentForm) {
                $this->addScheduleOverlapCondition($dateQ, $currentForm);
            })
            ->with('equipment')
            ->get();

        foreach ($calendarOverlaps as $event) {
            $overlaps[] = $this->formatOverlappingEvent($event, $currentForm);
        }

        return $overlaps;
    }

    /**
     * Format overlapping form for response
     */
    private function formatOverlappingForm($form, $currentForm, $source, $hasFacilities): array
    {
        $needsGracePeriod = $hasFacilities && $this->hasFacilities($currentForm);
        $conflictReason = $this->determineConflictReason($form, $currentForm->start_date, $currentForm->start_time, $needsGracePeriod);

        return [
            'source' => $source,
            'request_id' => $form->request_id,
            'requester_name' => $form->first_name . ' ' . $form->last_name,
            'status' => $form->formStatus->status_name,
            'has_facilities' => $hasFacilities,
            'needs_grace_period' => $needsGracePeriod,
            'conflict_reason' => $conflictReason,
            'schedule' => $this->formatSchedule($form),
            'overlap_severity' => $this->calculateOverlapSeverity($currentForm, $form),
            'shared_facilities' => $form->requestedFacilities
                ->whereIn('facility_id', $currentForm->requestedFacilities->pluck('facility_id'))
                ->pluck('facility.facility_name')
                ->unique()
                ->values()
                ->toArray(),
            'shared_equipment' => $form->requestedEquipment
                ->whereIn('equipment_id', $currentForm->requestedEquipment->pluck('equipment_id'))
                ->groupBy('equipment.equipment_name')
                ->map(fn($group) => $group->first()->equipment->equipment_name . ' (×' . $group->sum('quantity') . ')')
                ->values()
                ->toArray()
        ];
    }

    /**
     * Format overlapping calendar event for response
     */
    private function formatOverlappingEvent($event, $currentForm): array
    {
        return [
            'source' => 'calendar_event',
            'event_id' => $event->event_id,
            'event_name' => $event->event_name,
            'status' => null,
            'has_facilities' => true,
            'needs_grace_period' => true,
            'conflict_reason' => 'This is a scheduled school event',
            'schedule' => $this->formatSchedule($event),
            'overlap_severity' => 'high',
            'shared_facilities' => $event->venues->pluck('facility.facility_name')->toArray(),
            'shared_equipment' => $event->equipment->pluck('equipment_name')->toArray()
        ];
    }

    /**
     * Format schedule from either form or event
     */
    private function formatSchedule($item): array
    {
        $allDay = $item->all_day ?? false;

        return [
            'start_date' => $item->start_date,
            'end_date' => $item->end_date,
            'start_time' => $item->start_time ?? null,
            'end_time' => $item->end_time ?? null,
            'all_day' => $allDay,
            'formatted_start' => $allDay
                ? date('M j, Y', strtotime($item->start_date)) . ' (All Day)'
                : date('M j, Y', strtotime($item->start_date)) . ' ' . date('g:i A', strtotime($item->start_time)),
            'formatted_end' => $allDay
                ? date('M j, Y', strtotime($item->end_date)) . ' (All Day)'
                : date('M j, Y', strtotime($item->end_date)) . ' ' . date('g:i A', strtotime($item->end_time)),
        ];
    }
/**
 * Add date overlap condition to query - Rewritten to avoid SQL syntax issues
 */
private function addDateOverlapCondition($query, $startDate, $endDate, $startTime, $endTime, $allDay, $applyGracePeriod = false)
{
    if ($allDay) {
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
        return;
    }

    // For equipment checks, startTime and endTime might be null
    if (is_null($startTime) || is_null($endTime)) {
        $query->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
        return;
    }

    $query->where(function ($q) use ($startDate, $endDate, $startTime, $endTime, $applyGracePeriod) {
        // Date range must overlap
        $q->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
        
        if ($applyGracePeriod) {
            // For grace period, check if the request is within 4 hours after existing booking
            $q->where(function ($graceQ) use ($startDate, $startTime) {
                // Same day grace period check
                $graceQ->whereDate('end_date', '=', $startDate)
                    ->whereRaw("HOUR(TIMEDIFF(?, TIME(end_time))) < 4", [$startTime])
                    ->whereRaw("TIME(?) > TIME(end_time)", [$startTime]);
            })->orWhere(function ($laterQ) use ($startDate) {
                // Existing booking ends after our start date
                $laterQ->whereDate('end_date', '>', $startDate);
            });
        } else {
            // Direct time overlap
            $q->where(function ($timeQ) use ($startTime, $endTime) {
                $timeQ->whereRaw("TIME(start_time) < ?", [$endTime])
                    ->whereRaw("TIME(end_time) > ?", [$startTime]);
            });
        }
        
        // Also include all-day events as conflicts
        $q->orWhere(function ($allDayQ) use ($startDate, $endDate) {
            $allDayQ->where('all_day', true)
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate);
        });
    });
}

    /**
     * Add schedule overlap condition to query (existing method)
     */
    private function addScheduleOverlapCondition($query, $currentForm): void
    {
        if ($currentForm->all_day) {
            $query->where('start_date', '<=', $currentForm->end_date)
                ->where('end_date', '>=', $currentForm->start_date);
        } else {
            $query->where(function ($q) use ($currentForm) {
                $q->where('start_date', '<=', $currentForm->end_date)
                    ->where('end_date', '>=', $currentForm->start_date)
                    ->where(function ($timeQ) use ($currentForm) {
                        $timeQ->where('start_time', '<', $currentForm->end_time)
                            ->where('end_time', '>', $currentForm->start_time);
                    })
                    ->orWhere(function ($allDayQ) use ($currentForm) {
                        $allDayQ->where('all_day', true)
                            ->where('start_date', '<=', $currentForm->end_date)
                            ->where('end_date', '>=', $currentForm->start_date);
                    });
            });
        }
    }

    /**
     * Determine conflict reason for display
     */
    private function determineConflictReason($existingItem, $newStartDate, $newStartTime, $needsGracePeriod): string
    {
        if ($needsGracePeriod && !$existingItem->all_day) {
            $existingEnd = Carbon::parse($existingItem->end_date . ' ' . $existingItem->end_time);
            $graceEnd = $existingEnd->copy()->addHours(self::GRACE_PERIOD_HOURS);
            $newStart = Carbon::parse("$newStartDate $newStartTime");

            if ($newStart->lt($graceEnd)) {
                return "Venue requires 4-hour cleanup before next booking. Available after " . $graceEnd->format('g:i A');
            }
        }

        if ($existingItem->formStatus && $existingItem->formStatus->status_name === 'Pending Approval') {
            return "Another request for this item is pending approval";
        }

        return "This time slot conflicts with an existing booking";
    }

    /**
     * Calculate overlap severity between two forms
     */
    private function calculateOverlapSeverity($form1, $form2): string
    {
        $datesOverlap = !($form1->end_date < $form2->start_date || $form1->start_date > $form2->end_date);

        if (!$datesOverlap) {
            return 'none';
        }

        if ($form1->start_date === $form2->start_date && $form1->end_date === $form2->end_date) {
            if ($form1->all_day || $form2->all_day) {
                return 'high';
            } else {
                $timeOverlap = !($form1->end_time <= $form2->start_time || $form1->start_time >= $form2->end_time);
                return $timeOverlap ? 'high' : 'medium';
            }
        }

        return 'medium';
    }
}