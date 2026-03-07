<?php

namespace App\Services;
use App\Models\RequisitionForm;
use App\Models\EquipmentItem;
use App\Models\RequestedEquipment;
use App\Models\FormStatus;
use Illuminate\Support\Facades\Log;

class CheckAvailabilityService
{
    // Helper method to check facility availability
    public function checkFacilityAvailability($facilityId, $startDate, $endDate, $startTime, $endTime, $allDay = false)
    {
        // If all-day, ignore specific times - just check date overlap
        if ($allDay) {
            return RequisitionForm::whereHas('requestedFacilities', function ($query) use ($facilityId) {
                $query->where('facility_id', $facilityId);
            })
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                })
                ->whereIn('status_id', function ($query) {
                    $query->select('status_id')
                        ->from('form_statuses')
                        ->whereIn('status_name', ['Pending Approval', 'Scheduled', 'Ongoing']);
                })
                ->get()
                ->map(function ($form) {
                    return [
                        'request_id' => $form->request_id,
                        'date' => $form->start_date . ' - ' . $form->end_date,
                        'time' => $form->all_day ? 'All Day' : ($form->start_time . ' - ' . $form->end_time)
                    ];
                })
                ->toArray();
        }

        // Normal time-based conflict check
        return RequisitionForm::whereHas('requestedFacilities', function ($query) use ($facilityId) {
            $query->where('facility_id', $facilityId);
        })
            ->where(function ($query) use ($startDate, $endDate, $startTime, $endTime) {
                $query->where(function ($q) use ($startDate, $endDate, $startTime, $endTime) {
                    // Check for overlapping date/time ranges
                    $q->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate)
                        ->where(function ($subQ) use ($startTime, $endTime) {
                        $subQ->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
                })
                    // Also check if existing booking is all-day (conflicts with any time on that day)
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('all_day', true)
                            ->where('start_date', '<=', $endDate)
                            ->where('end_date', '>=', $startDate);
                    });
            })
            ->whereIn('status_id', function ($query) {
                $query->select('status_id')
                    ->from('form_statuses')
                    ->whereIn('status_name', ['Pending Approval', 'Scheduled', 'Ongoing']);
            })
            ->get()
            ->map(function ($form) {
                return [
                    'request_id' => $form->request_id,
                    'date' => $form->start_date . ' - ' . $form->end_date,
                    'time' => $form->all_day ? 'All Day' : ($form->start_time . ' - ' . $form->end_time)
                ];
            })
            ->toArray();
    }

    /**
     * Check equipment availability for a given time period
     */
    public function checkEquipmentAvailability($equipmentId, $startDate, $endDate, $allDay = false): int
    {
        $totalAvailable = EquipmentItem::where('equipment_id', $equipmentId)
            ->where('status_id', 1)
            ->whereIn('condition_id', [1, 2, 3])
            ->count();

        if ($allDay) {
            $existingBookings = RequestedEquipment::where('equipment_id', $equipmentId)
                ->whereHas('requisitionForm', function ($q) use ($startDate, $endDate) {
                    $q->whereIn('status_id', function ($sq) {
                        $sq->select('status_id')
                            ->from('form_statuses')
                            ->whereIn('status_name', ['Pending Approval', 'Scheduled', 'Ongoing']);
                    })
                        ->where(function ($dateQ) use ($startDate, $endDate) {
                            $dateQ->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                        });
                })
                ->sum('quantity');

            return $totalAvailable - $existingBookings;
        }

        return $totalAvailable; // You can enhance this for time-based checking
    }

    /**
     * Get overlapping requests for a form
     */
    public function getOverlappingRequests($currentForm): array
    {
        try {
            $currentFacilityIds = $currentForm->requestedFacilities->pluck('facility_id')->toArray();
            $currentEquipmentIds = $currentForm->requestedEquipment->pluck('equipment_id')->toArray();

            if (empty($currentFacilityIds) && empty($currentEquipmentIds)) {
                return [];
            }

            $excludedStatuses = FormStatus::whereIn('status_name', [
                'Returned',
                'Late Return',
                'Completed',
                'Rejected',
                'Cancelled'
            ])->pluck('status_id');

            $overlappingRequests = RequisitionForm::where('request_id', '!=', $currentForm->request_id)
                ->whereNotIn('status_id', $excludedStatuses)
                ->where(function ($query) use ($currentFacilityIds, $currentEquipmentIds, $currentForm) {
                    if (!empty($currentFacilityIds)) {
                        $query->whereHas('requestedFacilities', function ($q) use ($currentFacilityIds, $currentForm) {
                            $q->whereIn('facility_id', $currentFacilityIds);
                        })->where(function ($dateQ) use ($currentForm) {
                            $this->addScheduleOverlapCondition($dateQ, $currentForm);
                        });
                    }

                    if (!empty($currentEquipmentIds)) {
                        $query->orWhereHas('requestedEquipment', function ($q) use ($currentEquipmentIds, $currentForm) {
                            $q->whereIn('equipment_id', $currentEquipmentIds);
                        })->where(function ($dateQ) use ($currentForm) {
                            $this->addScheduleOverlapCondition($dateQ, $currentForm);
                        });
                    }
                })
                ->with(['formStatus', 'requestedFacilities.facility', 'requestedEquipment.equipment'])
                ->get()
                ->map(fn($form) => $this->formatOverlappingForm($form, $currentForm));

            return $overlappingRequests->toArray();
        } catch (\Exception $e) {
            Log::error('Error finding overlapping requests', [
                'request_id' => $currentForm->request_id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Format overlapping form for response
     */
    private function formatOverlappingForm($form, $currentForm): array
    {
        $formattedStartTime = $form->all_day ? 'All Day' : $form->start_time;
        $formattedEndTime = $form->all_day ? 'All Day' : $form->end_time;

        $formattedStartDateTime = $form->all_day
            ? date('M j, Y', strtotime($form->start_date)) . ' (All Day)'
            : date('M j, Y', strtotime($form->start_date)) . ' ' . date('g:i A', strtotime($form->start_time));

        $formattedEndDateTime = $form->all_day
            ? date('M j, Y', strtotime($form->end_date)) . ' (All Day)'
            : date('M j, Y', strtotime($form->end_date)) . ' ' . date('g:i A', strtotime($form->end_time));

        return [
            'request_id' => $form->request_id,
            'requester_name' => $form->first_name . ' ' . $form->last_name,
            'status' => $form->formStatus->status_name,
            'schedule' => [
                'start_date' => $form->start_date,
                'end_date' => $form->end_date,
                'start_time' => $form->start_time,
                'end_time' => $form->end_time,
                'all_day' => $form->all_day,
                'formatted_start_time' => $formattedStartTime,
                'formatted_end_time' => $formattedEndTime,
                'formatted_start_datetime' => $formattedStartDateTime,
                'formatted_end_datetime' => $formattedEndDateTime,
            ],
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
     * Add schedule overlap condition to query
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